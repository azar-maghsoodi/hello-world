<?php
/**
 * Reward redemption workflow: verifies balance, deducts points through the
 * ledger (never directly), and tracks the redemption record through its
 * pending -> approved -> used lifecycle (or cancelled, which refunds the
 * points via a reversing transaction).
 *
 * B2Bora does not use a normal retail checkout, so V1 never auto-generates
 * a WooCommerce coupon: redemption creates an internal record that staff
 * action manually (e.g. applying an order credit to the customer's next
 * confirmed order).
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2Bora_PC_Redemptions
 */
class B2Bora_PC_Redemptions {

	const STATUS_PENDING   = 'pending';
	const STATUS_APPROVED  = 'approved';
	const STATUS_USED      = 'used';
	const STATUS_CANCELLED = 'cancelled';

	/**
	 * Redeem a reward for a user. This is the only method that should
	 * create redemption rows outside of admin edits.
	 *
	 * @param int $user_id   User ID.
	 * @param int $reward_id Reward ID.
	 *
	 * @return int|WP_Error Redemption ID on success, WP_Error otherwise.
	 */
	public static function redeem( $user_id, $reward_id ) {
		$user_id   = B2Bora_PC_Security::sanitize_user_id( $user_id );
		$reward_id = absint( $reward_id );

		if ( ! $user_id ) {
			return new WP_Error( 'invalid_user', __( 'Invalid user.', 'b2bora-partner-club' ) );
		}

		$reward = B2Bora_PC_Rewards::get_reward( $reward_id );
		if ( ! $reward || empty( $reward['active'] ) ) {
			return new WP_Error( 'invalid_reward', __( 'This reward is not available.', 'b2bora-partner-club' ) );
		}

		$cost = absint( $reward['points_cost'] );

		// Short-lived per-user lock to stop a double form submit / double
		// AJAX click from creating two redemption rows before the first
		// one has finished processing.
		$lock_key = 'b2bora_pc_redeem_lock_' . $user_id;
		if ( get_transient( $lock_key ) ) {
			return new WP_Error( 'redemption_in_progress', __( 'A redemption is already being processed. Please wait a moment and try again.', 'b2bora-partner-club' ) );
		}
		set_transient( $lock_key, 1, 15 );

		$balance = B2Bora_PC_Points::get_balance( $user_id );

		if ( $balance < $cost ) {
			delete_transient( $lock_key );
			return new WP_Error( 'insufficient_points', __( 'You do not have enough points to redeem this reward.', 'b2bora-partner-club' ) );
		}

		global $wpdb;
		$table = B2Bora_PC_Database::redemptions_table();

		$requires_approval = (bool) B2Bora_PC_Settings::get( 'redemption_requires_approval' );
		$initial_status    = $requires_approval ? self::STATUS_PENDING : self::STATUS_APPROVED;
		$now               = current_time( 'mysql' );

		$inserted = $wpdb->insert(
			$table,
			array(
				'user_id'      => $user_id,
				'reward_id'    => $reward_id,
				'points_spent' => $cost,
				'status'       => $initial_status,
				'created_at'   => $now,
				'approved_at'  => $requires_approval ? null : $now,
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			delete_transient( $lock_key );
			B2Bora_PC_Logger::error( "Failed to insert redemption row for user #{$user_id}, reward #{$reward_id}: {$wpdb->last_error}" );
			return new WP_Error( 'db_error', __( 'Could not process the redemption. Please try again.', 'b2bora-partner-club' ) );
		}

		$redemption_id = (int) $wpdb->insert_id;

		$transaction_id = B2Bora_PC_Points::record_transaction(
			$user_id,
			B2Bora_PC_Points::TYPE_REDEMPTION,
			-$cost,
			array(
				'reference_key' => "redemption_{$redemption_id}",
				/* translators: %s: reward name */
				'description'   => sprintf( __( 'Redeemed: %s', 'b2bora-partner-club' ), $reward['name'] ),
			)
		);

		if ( ! $transaction_id ) {
			// Balance changed between our check and the ledger write (e.g.
			// a concurrent redemption). Roll back the redemption row so we
			// never show a redemption that was never actually paid for.
			$wpdb->delete( $table, array( 'id' => $redemption_id ), array( '%d' ) );
			delete_transient( $lock_key );
			return new WP_Error( 'insufficient_points', __( 'You do not have enough points to redeem this reward.', 'b2bora-partner-club' ) );
		}

		delete_transient( $lock_key );

		B2Bora_PC_Logger::info( "User #{$user_id} redeemed reward #{$reward_id} ('{$reward['name']}') for {$cost} points (redemption #{$redemption_id})." );

		/**
		 * Fires after a reward has been successfully redeemed.
		 *
		 * @param int   $redemption_id Redemption ID.
		 * @param int   $user_id       User ID.
		 * @param array $reward        Reward row.
		 */
		do_action( 'b2bora_pc_reward_redeemed', $redemption_id, $user_id, $reward );

		return $redemption_id;
	}

	/**
	 * Move a pending redemption to approved.
	 *
	 * @param int $redemption_id Redemption ID.
	 *
	 * @return bool
	 */
	public static function approve( $redemption_id ) {
		return self::transition( $redemption_id, self::STATUS_PENDING, self::STATUS_APPROVED, array( 'approved_at' => current_time( 'mysql' ) ) );
	}

	/**
	 * Mark an approved redemption as used (fulfilled).
	 *
	 * @param int $redemption_id Redemption ID.
	 *
	 * @return bool
	 */
	public static function mark_used( $redemption_id ) {
		return self::transition( $redemption_id, self::STATUS_APPROVED, self::STATUS_USED, array( 'used_at' => current_time( 'mysql' ) ) );
	}

	/**
	 * Cancel a pending or approved redemption and refund the spent points
	 * via a reversing transaction (the original redemption transaction is
	 * never modified or deleted).
	 *
	 * @param int $redemption_id Redemption ID.
	 *
	 * @return bool
	 */
	public static function cancel( $redemption_id ) {
		$redemption = self::get_redemption( $redemption_id );

		if ( ! $redemption || ! in_array( $redemption['status'], array( self::STATUS_PENDING, self::STATUS_APPROVED ), true ) ) {
			return false;
		}

		$reversal_reference = "redemption_cancel_{$redemption_id}";
		if ( B2Bora_PC_Points::reference_exists( $reversal_reference ) ) {
			return false;
		}

		$refunded = B2Bora_PC_Points::record_transaction(
			(int) $redemption['user_id'],
			B2Bora_PC_Points::TYPE_MANUAL,
			(int) $redemption['points_spent'],
			array(
				'reference_key' => $reversal_reference,
				'description'   => __( 'Redemption cancelled - points refunded', 'b2bora-partner-club' ),
			)
		);

		if ( ! $refunded ) {
			return false;
		}

		global $wpdb;
		$table = B2Bora_PC_Database::redemptions_table();

		return false !== $wpdb->update(
			$table,
			array( 'status' => self::STATUS_CANCELLED ),
			array( 'id' => absint( $redemption_id ) ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Shared helper for simple status transitions guarded by the expected
	 * current status, so an "approve" can't be replayed on an already-used
	 * or already-cancelled redemption.
	 *
	 * @param int    $redemption_id    Redemption ID.
	 * @param string $expected_status  Required current status.
	 * @param string $new_status       Status to move to.
	 * @param array  $extra_fields     Extra columns to set (already safe values).
	 *
	 * @return bool
	 */
	private static function transition( $redemption_id, $expected_status, $new_status, array $extra_fields = array() ) {
		global $wpdb;
		$table = B2Bora_PC_Database::redemptions_table();

		$fields  = array_merge( array( 'status' => $new_status ), $extra_fields );
		$formats = array_fill( 0, count( $fields ), '%s' );

		$updated = $wpdb->update(
			$table,
			$fields,
			array(
				'id'     => absint( $redemption_id ),
				'status' => $expected_status,
			),
			$formats,
			array( '%d', '%s' )
		);

		return (bool) $updated;
	}

	/**
	 * Fetch a single redemption.
	 *
	 * @param int $redemption_id Redemption ID.
	 *
	 * @return array|null
	 */
	public static function get_redemption( $redemption_id ) {
		global $wpdb;
		$table = B2Bora_PC_Database::redemptions_table();

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $redemption_id ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ? $row : null;
	}

	/**
	 * Redemptions for a single user, most recent first.
	 *
	 * @param int $user_id User ID.
	 * @param int $limit   Max rows.
	 *
	 * @return array
	 */
	public static function get_for_user( $user_id, $limit = 20 ) {
		global $wpdb;
		$table = B2Bora_PC_Database::redemptions_table();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*, rw.name AS reward_name FROM {$table} r LEFT JOIN " . B2Bora_PC_Database::rewards_table() . ' rw ON rw.id = r.reward_id WHERE r.user_id = %d ORDER BY r.id DESC LIMIT %d', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				absint( $user_id ),
				absint( $limit )
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * All redemptions (admin listing), optionally filtered by status.
	 *
	 * @param string $status Optional status filter.
	 * @param int    $limit  Max rows.
	 *
	 * @return array
	 */
	public static function get_all( $status = '', $limit = 50 ) {
		global $wpdb;
		$table   = B2Bora_PC_Database::redemptions_table();
		$rewards = B2Bora_PC_Database::rewards_table();

		if ( $status ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT r.*, rw.name AS reward_name FROM {$table} r LEFT JOIN {$rewards} rw ON rw.id = r.reward_id WHERE r.status = %s ORDER BY r.id DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					sanitize_key( $status ),
					absint( $limit )
				),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT r.*, rw.name AS reward_name FROM {$table} r LEFT JOIN {$rewards} rw ON rw.id = r.reward_id ORDER BY r.id DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					absint( $limit )
				),
				ARRAY_A
			);
		}

		return is_array( $rows ) ? $rows : array();
	}
}
