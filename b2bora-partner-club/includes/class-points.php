<?php
/**
 * Points ledger engine. This is the single place allowed to write to the
 * b2bora_points_transactions table; every balance-changing event in the
 * plugin (order points, bonuses, missions, redemptions, refunds, manual
 * adjustments) goes through record_transaction() so idempotency and the
 * "no negative balance" rule are enforced in one place.
 *
 * All point amounts are stored and calculated as integers. No floating
 * point arithmetic is used for balances.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2Bora_PC_Points
 */
class B2Bora_PC_Points {

	/**
	 * Known transaction types. Kept as constants so callers/admin screens
	 * do not sprinkle raw strings around the codebase.
	 */
	const TYPE_ORDER            = 'order';
	const TYPE_WELCOME_BONUS    = 'welcome_bonus';
	const TYPE_REORDER_BONUS    = 'reorder_bonus';
	const TYPE_MISSION          = 'mission';
	const TYPE_REFERRAL         = 'referral';
	const TYPE_MANUAL           = 'manual_adjustment';
	const TYPE_REDEMPTION       = 'redemption';
	const TYPE_REFUND           = 'refund';
	const TYPE_EXPIRATION       = 'expiration';

	/**
	 * Whether a transaction already exists for the given idempotency key.
	 *
	 * @param string $reference_key Unique reference key, e.g. "order_points_123".
	 *
	 * @return bool
	 */
	public static function reference_exists( $reference_key ) {
		if ( empty( $reference_key ) ) {
			return false;
		}

		global $wpdb;
		$table = B2Bora_PC_Database::transactions_table();

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE reference_key = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$reference_key
			)
		);

		return null !== $existing;
	}

	/**
	 * Current point balance for a user (the balance_after of their most
	 * recent ledger row, or 0 if they have none).
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int
	 */
	public static function get_balance( $user_id ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return 0;
		}

		global $wpdb;
		$table = B2Bora_PC_Database::transactions_table();

		$balance = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT balance_after FROM {$table} WHERE user_id = %d ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id
			)
		);

		return null === $balance ? 0 : (int) $balance;
	}

	/**
	 * Lifetime (all-time earned, ignoring later spends/reversals) points
	 * for a user. Used as the default basis for partner level calculation.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int
	 */
	public static function get_lifetime_points( $user_id ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return 0;
		}

		global $wpdb;
		$table = B2Bora_PC_Database::transactions_table();

		$sum = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(points) FROM {$table} WHERE user_id = %d AND points > 0", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id
			)
		);

		return null === $sum ? 0 : (int) $sum;
	}

	/**
	 * Qualifying points used for level calculation, based on the
	 * configured level_basis setting ("lifetime" or "balance").
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int
	 */
	public static function get_qualifying_points( $user_id ) {
		$basis = B2Bora_PC_Settings::get( 'level_basis', 'lifetime' );

		return 'balance' === $basis ? self::get_balance( $user_id ) : self::get_lifetime_points( $user_id );
	}

	/**
	 * Record a ledger transaction. This is the only method that writes to
	 * the points ledger.
	 *
	 * Idempotency: when $reference_key is provided, a row is only ever
	 * inserted once for that key. If the key already exists this returns
	 * false without creating a duplicate and without erroring, so callers
	 * can safely call this multiple times for the same logical event
	 * (e.g. a webhook retry).
	 *
	 * Negative balance protection: when the resulting balance would go
	 * below zero, the write is rejected unless $allow_negative is true.
	 *
	 * @param int    $user_id        User ID.
	 * @param string $type           One of the TYPE_* constants.
	 * @param int    $points         Signed integer point delta (positive to add, negative to deduct).
	 * @param array  $args {
	 *     Optional context.
	 *
	 *     @type int|null $order_id       Related WooCommerce order ID.
	 *     @type string   $description    Human readable description.
	 *     @type string   $reference_key  Unique idempotency key.
	 *     @type bool     $allow_negative Allow the balance to go negative (never used by redemptions).
	 * }
	 *
	 * @return int|false Transaction ID on success, false on failure/duplicate.
	 */
	public static function record_transaction( $user_id, $type, $points, array $args = array() ) {
		$user_id = B2Bora_PC_Security::sanitize_user_id( $user_id );
		$points  = (int) $points;
		$type    = sanitize_key( $type );

		if ( ! $user_id ) {
			B2Bora_PC_Logger::warning( 'record_transaction rejected: invalid user_id.' );
			return false;
		}

		if ( 0 === $points ) {
			return false;
		}

		$order_id       = isset( $args['order_id'] ) ? absint( $args['order_id'] ) : null;
		$description    = isset( $args['description'] ) ? sanitize_text_field( $args['description'] ) : '';
		$reference_key  = isset( $args['reference_key'] ) ? sanitize_text_field( $args['reference_key'] ) : null;
		$allow_negative = ! empty( $args['allow_negative'] );

		if ( $reference_key && self::reference_exists( $reference_key ) ) {
			B2Bora_PC_Logger::info( "Skipped duplicate transaction for reference '{$reference_key}' (user #{$user_id})." );
			return false;
		}

		global $wpdb;
		$table = B2Bora_PC_Database::transactions_table();

		// Serialise balance reads/writes per request using a DB transaction
		// so two near-simultaneous requests for the same user cannot both
		// read the same starting balance.
		$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$current_balance = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT balance_after FROM {$table} WHERE user_id = %d ORDER BY id DESC LIMIT 1 FOR UPDATE", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id
			)
		);
		$current_balance = null === $current_balance ? 0 : (int) $current_balance;
		$new_balance      = $current_balance + $points;

		if ( $new_balance < 0 && ! $allow_negative ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			B2Bora_PC_Logger::warning( "Rejected transaction that would leave user #{$user_id} with a negative balance ({$new_balance})." );
			return false;
		}

		$inserted = $wpdb->insert(
			$table,
			array(
				'user_id'       => $user_id,
				'order_id'      => $order_id,
				'type'          => $type,
				'points'        => $points,
				'balance_after' => $new_balance,
				'description'   => $description,
				'reference_key' => $reference_key,
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			// A duplicate-key error here means a concurrent request won the
			// race on the same reference_key; treat it as an already
			// recorded event rather than a hard failure.
			if ( $reference_key && false !== strpos( (string) $wpdb->last_error, 'Duplicate entry' ) ) {
				B2Bora_PC_Logger::info( "Concurrent duplicate transaction avoided for reference '{$reference_key}'." );
				return false;
			}

			B2Bora_PC_Logger::error( "Failed to insert points transaction for user #{$user_id}: {$wpdb->last_error}" );
			return false;
		}

		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$transaction_id = (int) $wpdb->insert_id;

		B2Bora_PC_Logger::info(
			sprintf(
				'%s %d points (%s) for user #%d%s. New balance: %d.',
				$points >= 0 ? 'Awarded' : 'Deducted',
				abs( $points ),
				$type,
				$user_id,
				$order_id ? " (order #{$order_id})" : '',
				$new_balance
			)
		);

		/**
		 * Fires after a points ledger transaction has been recorded.
		 *
		 * @param int   $transaction_id Ledger row ID.
		 * @param int   $user_id        User ID.
		 * @param int   $points         Signed point delta.
		 * @param array $args           Original context array.
		 */
		do_action( 'b2bora_pc_points_transaction_recorded', $transaction_id, $user_id, $points, $args );

		return $transaction_id;
	}

	/**
	 * Fetch a page of ledger rows for a user, most recent first.
	 *
	 * @param int $user_id User ID.
	 * @param int $limit   Max rows.
	 * @param int $offset  Offset for pagination.
	 *
	 * @return array
	 */
	public static function get_history( $user_id, $limit = 20, $offset = 0 ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return array();
		}

		global $wpdb;
		$table = B2Bora_PC_Database::transactions_table();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d ORDER BY id DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				absint( $limit ),
				absint( $offset )
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Fetch a single transaction by its reference key, if any.
	 *
	 * @param string $reference_key Reference key.
	 *
	 * @return array|null
	 */
	public static function get_by_reference( $reference_key ) {
		global $wpdb;
		$table = B2Bora_PC_Database::transactions_table();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE reference_key = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$reference_key
			),
			ARRAY_A
		);

		return $row ? $row : null;
	}

	/**
	 * Most recent ledger row of a given type for a user (e.g. the last
	 * 'order' transaction, used to evaluate the reorder-bonus window).
	 *
	 * @param int    $user_id User ID.
	 * @param string $type    Transaction type.
	 *
	 * @return array|null
	 */
	public static function get_last_transaction_of_type( $user_id, $type ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return null;
		}

		global $wpdb;
		$table = B2Bora_PC_Database::transactions_table();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d AND type = %s ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				sanitize_key( $type )
			),
			ARRAY_A
		);

		return $row ? $row : null;
	}

	/**
	 * Count how many ledger rows of a given type exist for a user. Used by
	 * mission types such as "order_count".
	 *
	 * @param int    $user_id User ID.
	 * @param string $type    Transaction type.
	 *
	 * @return int
	 */
	public static function count_transactions_of_type( $user_id, $type ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return 0;
		}

		global $wpdb;
		$table = B2Bora_PC_Database::transactions_table();

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND type = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				sanitize_key( $type )
			)
		);
	}

	/**
	 * Site-wide aggregate: total positive points ever issued.
	 *
	 * @return int
	 */
	public static function get_total_points_issued() {
		global $wpdb;
		$table = B2Bora_PC_Database::transactions_table();

		$sum = $wpdb->get_var( "SELECT SUM(points) FROM {$table} WHERE points > 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return null === $sum ? 0 : (int) $sum;
	}

	/**
	 * Site-wide aggregate: total points redeemed (absolute value).
	 *
	 * @return int
	 */
	public static function get_total_points_redeemed() {
		global $wpdb;
		$table = B2Bora_PC_Database::transactions_table();

		$sum = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(points) FROM {$table} WHERE type = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				self::TYPE_REDEMPTION
			)
		);

		return null === $sum ? 0 : abs( (int) $sum );
	}

	/**
	 * Count of distinct users with at least one ledger row (Partner Club
	 * "members").
	 *
	 * @return int
	 */
	public static function get_total_members() {
		global $wpdb;
		$table = B2Bora_PC_Database::transactions_table();

		return (int) $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Most recent transactions across all users, for the admin dashboard.
	 *
	 * @param int $limit Max rows.
	 *
	 * @return array
	 */
	public static function get_recent_transactions( $limit = 10 ) {
		global $wpdb;
		$table = B2Bora_PC_Database::transactions_table();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				absint( $limit )
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}
}
