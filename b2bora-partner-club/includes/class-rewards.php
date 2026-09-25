<?php
/**
 * Rewards catalogue CRUD. V1 reward types: order_credit, free_box,
 * partner_offer. Redemption logic itself lives in B2Bora_PC_Redemptions.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2Bora_PC_Rewards
 */
class B2Bora_PC_Rewards {

	const TYPE_ORDER_CREDIT  = 'order_credit';
	const TYPE_FREE_BOX      = 'free_box';
	const TYPE_PARTNER_OFFER = 'partner_offer';

	/**
	 * Supported reward types and their labels.
	 *
	 * @return array
	 */
	public static function get_types() {
		return array(
			self::TYPE_ORDER_CREDIT  => __( 'Order Credit', 'b2bora-partner-club' ),
			self::TYPE_FREE_BOX      => __( 'Free Box', 'b2bora-partner-club' ),
			self::TYPE_PARTNER_OFFER => __( 'Partner Offer', 'b2bora-partner-club' ),
		);
	}

	/**
	 * List rewards.
	 *
	 * @param bool $active_only Only return active rewards.
	 *
	 * @return array
	 */
	public static function get_rewards( $active_only = false ) {
		global $wpdb;
		$table = B2Bora_PC_Database::rewards_table();

		$where = $active_only ? 'WHERE active = 1' : '';

		$rows = $wpdb->get_results( "SELECT * FROM {$table} {$where} ORDER BY sort_order ASC, points_cost ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Fetch a single reward.
	 *
	 * @param int $reward_id Reward ID.
	 *
	 * @return array|null
	 */
	public static function get_reward( $reward_id ) {
		global $wpdb;
		$table = B2Bora_PC_Database::rewards_table();

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $reward_id ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ? $row : null;
	}

	/**
	 * Create or update a reward. Pass an `id` key to update.
	 *
	 * @param array $data Reward fields.
	 *
	 * @return int|false Reward ID on success, false on failure.
	 */
	public static function save_reward( array $data ) {
		global $wpdb;
		$table = B2Bora_PC_Database::rewards_table();

		$name = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		if ( '' === $name ) {
			return false;
		}

		$type = isset( $data['reward_type'] ) ? sanitize_key( $data['reward_type'] ) : self::TYPE_ORDER_CREDIT;
		if ( ! array_key_exists( $type, self::get_types() ) ) {
			$type = self::TYPE_ORDER_CREDIT;
		}

		$now = current_time( 'mysql' );

		$fields = array(
			'name'         => $name,
			'description'  => isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : '',
			'points_cost'  => isset( $data['points_cost'] ) ? absint( $data['points_cost'] ) : 0,
			'reward_type'  => $type,
			'reward_value' => isset( $data['reward_value'] ) ? (float) $data['reward_value'] : 0,
			// Default to active when the key is omitted entirely (e.g. a
			// programmatic caller); admin forms always send an explicit
			// 0 or 1 for this field, so their behaviour is unaffected.
			'active'       => array_key_exists( 'active', $data ) ? ( empty( $data['active'] ) ? 0 : 1 ) : 1,
			'sort_order'   => isset( $data['sort_order'] ) ? absint( $data['sort_order'] ) : 0,
			'updated_at'   => $now,
		);

		$formats = array( '%s', '%s', '%d', '%s', '%f', '%d', '%d', '%s' );

		if ( ! empty( $data['id'] ) ) {
			$updated = $wpdb->update( $table, $fields, array( 'id' => absint( $data['id'] ) ), $formats, array( '%d' ) );
			return false === $updated ? false : absint( $data['id'] );
		}

		$fields['created_at'] = $now;
		$formats[]            = '%s';

		$inserted = $wpdb->insert( $table, $fields, $formats );

		return $inserted ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Delete a reward.
	 *
	 * @param int $reward_id Reward ID.
	 *
	 * @return bool
	 */
	public static function delete_reward( $reward_id ) {
		global $wpdb;
		$table = B2Bora_PC_Database::rewards_table();

		return (bool) $wpdb->delete( $table, array( 'id' => absint( $reward_id ) ), array( '%d' ) );
	}

	/**
	 * Toggle a reward's active flag.
	 *
	 * @param int  $reward_id Reward ID.
	 * @param bool $active    New active state.
	 *
	 * @return bool
	 */
	public static function set_active( $reward_id, $active ) {
		global $wpdb;
		$table = B2Bora_PC_Database::rewards_table();

		return false !== $wpdb->update(
			$table,
			array( 'active' => $active ? 1 : 0, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => absint( $reward_id ) ),
			array( '%d', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Count active rewards, used on the admin dashboard.
	 *
	 * @return int
	 */
	public static function count_active() {
		global $wpdb;
		$table = B2Bora_PC_Database::rewards_table();

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE active = 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}
