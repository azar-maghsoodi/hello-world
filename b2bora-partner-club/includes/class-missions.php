<?php
/**
 * Missions engine. V1 keeps this intentionally simple: a mission is a
 * one-time, per-customer achievement that awards bonus points the first
 * (and only) time it is completed. Completion is always recorded as a
 * points ledger transaction with a mission-specific reference key, which
 * is what makes completion idempotent.
 *
 * Supported types in V1: first_order, order_count, brand_count,
 * pallet_order. Automatic detection runs from B2Bora_PC_Orders whenever an
 * order is confirmed.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2Bora_PC_Missions
 */
class B2Bora_PC_Missions {

	const TYPE_FIRST_ORDER  = 'first_order';
	const TYPE_ORDER_COUNT  = 'order_count';
	const TYPE_BRAND_COUNT  = 'brand_count';
	const TYPE_PALLET_ORDER = 'pallet_order';

	/**
	 * Supported mission types and labels.
	 *
	 * @return array
	 */
	public static function get_types() {
		return array(
			self::TYPE_FIRST_ORDER  => __( 'First Order', 'b2bora-partner-club' ),
			self::TYPE_ORDER_COUNT  => __( 'Order Count', 'b2bora-partner-club' ),
			self::TYPE_BRAND_COUNT  => __( 'Brand Variety', 'b2bora-partner-club' ),
			self::TYPE_PALLET_ORDER => __( 'Pallet Order', 'b2bora-partner-club' ),
		);
	}

	/**
	 * List missions.
	 *
	 * @param bool $active_only Only currently-active missions (respects start/end dates).
	 *
	 * @return array
	 */
	public static function get_missions( $active_only = false ) {
		global $wpdb;
		$table = B2Bora_PC_Database::missions_table();

		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = is_array( $rows ) ? $rows : array();

		if ( ! $active_only ) {
			return $rows;
		}

		return array_values( array_filter( $rows, array( __CLASS__, 'is_currently_active' ) ) );
	}

	/**
	 * Whether a mission row is active right now (active flag + date window).
	 *
	 * @param array $mission Mission row.
	 *
	 * @return bool
	 */
	public static function is_currently_active( array $mission ) {
		if ( empty( $mission['active'] ) ) {
			return false;
		}

		$today = current_time( 'Y-m-d' );

		if ( ! empty( $mission['start_date'] ) && $today < $mission['start_date'] ) {
			return false;
		}

		if ( ! empty( $mission['end_date'] ) && $today > $mission['end_date'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Fetch a single mission.
	 *
	 * @param int $mission_id Mission ID.
	 *
	 * @return array|null
	 */
	public static function get_mission( $mission_id ) {
		global $wpdb;
		$table = B2Bora_PC_Database::missions_table();

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $mission_id ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ? $row : null;
	}

	/**
	 * Create or update a mission. Pass an `id` key to update.
	 *
	 * @param array $data Mission fields.
	 *
	 * @return int|false Mission ID on success, false on failure.
	 */
	public static function save_mission( array $data ) {
		global $wpdb;
		$table = B2Bora_PC_Database::missions_table();

		$name = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		if ( '' === $name ) {
			return false;
		}

		$type = isset( $data['type'] ) ? sanitize_key( $data['type'] ) : self::TYPE_FIRST_ORDER;
		if ( ! array_key_exists( $type, self::get_types() ) ) {
			return false;
		}

		$now = current_time( 'mysql' );

		$fields = array(
			'name'         => $name,
			'description'  => isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : '',
			'type'         => $type,
			'target'       => isset( $data['target'] ) ? absint( $data['target'] ) : 0,
			'bonus_points' => isset( $data['bonus_points'] ) ? absint( $data['bonus_points'] ) : 0,
			'start_date'   => ! empty( $data['start_date'] ) ? sanitize_text_field( $data['start_date'] ) : null,
			'end_date'     => ! empty( $data['end_date'] ) ? sanitize_text_field( $data['end_date'] ) : null,
			// Default to active when the key is omitted entirely (e.g. a
			// programmatic caller); admin forms always send an explicit
			// 0 or 1 for this field, so their behaviour is unaffected.
			'active'       => array_key_exists( 'active', $data ) ? ( empty( $data['active'] ) ? 0 : 1 ) : 1,
			'updated_at'   => $now,
		);

		$formats = array( '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s' );

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
	 * Delete a mission.
	 *
	 * @param int $mission_id Mission ID.
	 *
	 * @return bool
	 */
	public static function delete_mission( $mission_id ) {
		global $wpdb;
		$table = B2Bora_PC_Database::missions_table();

		return (bool) $wpdb->delete( $table, array( 'id' => absint( $mission_id ) ), array( '%d' ) );
	}

	/**
	 * Idempotency key for a user completing a given mission. Public so
	 * dashboard code can check completion without duplicating the format.
	 *
	 * @param int $mission_id Mission ID.
	 * @param int $user_id    User ID.
	 *
	 * @return string
	 */
	public static function reference_key( $mission_id, $user_id ) {
		return "mission_{$mission_id}_user_{$user_id}";
	}

	/**
	 * Whether a user has already completed (been rewarded for) a mission.
	 *
	 * @param int $mission_id Mission ID.
	 * @param int $user_id    User ID.
	 *
	 * @return bool
	 */
	public static function is_completed_by_user( $mission_id, $user_id ) {
		return B2Bora_PC_Points::reference_exists( self::reference_key( $mission_id, $user_id ) );
	}

	/**
	 * Award a mission's bonus points to a user. Guarded by the ledger's
	 * unique reference_key, so this is always safe to call speculatively.
	 *
	 * @param array $mission Mission row.
	 * @param int   $user_id User ID.
	 * @param int   $order_id Related order, if any (for context in the description only).
	 *
	 * @return bool True if newly awarded, false if already completed or invalid.
	 */
	private static function award( array $mission, $user_id, $order_id = 0 ) {
		if ( (int) $mission['bonus_points'] <= 0 ) {
			return false;
		}

		$transaction_id = B2Bora_PC_Points::record_transaction(
			$user_id,
			B2Bora_PC_Points::TYPE_MISSION,
			(int) $mission['bonus_points'],
			array(
				'order_id'      => $order_id ? absint( $order_id ) : null,
				'reference_key' => self::reference_key( $mission['id'], $user_id ),
				/* translators: %s: mission name */
				'description'   => sprintf( __( 'Mission completed: %s', 'b2bora-partner-club' ), $mission['name'] ),
			)
		);

		return (bool) $transaction_id;
	}

	/**
	 * Automatic mission detection, run once per confirmed order.
	 *
	 * @param int      $user_id  User ID.
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order    Order object.
	 */
	public static function evaluate_for_order( $user_id, $order_id, $order ) {
		$missions = self::get_missions( true );

		foreach ( $missions as $mission ) {
			if ( self::is_completed_by_user( $mission['id'], $user_id ) ) {
				continue;
			}

			$completed = false;

			switch ( $mission['type'] ) {
				case self::TYPE_FIRST_ORDER:
					$completed = 1 === B2Bora_PC_Points::count_transactions_of_type( $user_id, B2Bora_PC_Points::TYPE_ORDER );
					break;

				case self::TYPE_ORDER_COUNT:
					$target    = max( 1, (int) $mission['target'] );
					$completed = B2Bora_PC_Points::count_transactions_of_type( $user_id, B2Bora_PC_Points::TYPE_ORDER ) >= $target;
					break;

				case self::TYPE_BRAND_COUNT:
					$target    = max( 1, (int) $mission['target'] );
					$completed = self::count_distinct_brands( $order ) >= $target;
					break;

				case self::TYPE_PALLET_ORDER:
					$completed = self::order_is_pallet_order( $order );
					break;
			}

			/**
			 * Filter whether a mission should be considered completed for
			 * this order/user, allowing the B2Bora order plugin to plug in
			 * custom mission types without editing this file.
			 *
			 * @param bool     $completed Whether the mission is complete.
			 * @param array    $mission   Mission row.
			 * @param int      $user_id   User ID.
			 * @param WC_Order $order     Order object.
			 */
			$completed = (bool) apply_filters( 'b2bora_pc_mission_completed', $completed, $mission, $user_id, $order );

			if ( $completed ) {
				self::award( $mission, $user_id, $order_id );
			}
		}
	}

	/**
	 * Count distinct product "brands" on an order.
	 *
	 * NOT VERIFIED: as of this writing, none of the plugins active on
	 * B2Bora (WooCommerce core, Polylang, Elementor, Rank Math, Easy
	 * Currency, Themewant Product Gallery) were found to register a brand
	 * taxonomy, and the actual product catalogue was not inspected to
	 * confirm whether brand is modelled as a taxonomy at all (vs. a plain
	 * attribute, a category, or nothing). This tries the two most common
	 * WooCommerce conventions in order - a dedicated `product_brand`
	 * taxonomy (used by several brand plugins), then the attribute
	 * taxonomy WooCommerce would auto-create for a "Brand" product
	 * attribute (`pa_brand`) - before falling back to product categories,
	 * which are almost certainly the wrong grouping for "brand variety"
	 * but are guaranteed to exist. Do not enable a `brand_count` mission
	 * in production before confirming which of these (if any) matches the
	 * real catalogue, or supplying the right one via the
	 * `b2bora_pc_brand_taxonomy` filter.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return int
	 */
	private static function count_distinct_brands( $order ) {
		$candidates = array( 'product_brand', 'pa_brand', 'product_cat' );
		$default    = 'product_cat';

		foreach ( $candidates as $candidate ) {
			if ( taxonomy_exists( $candidate ) ) {
				$default = $candidate;
				break;
			}
		}

		$taxonomy = apply_filters( 'b2bora_pc_brand_taxonomy', $default );

		$terms = array();

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}

			$product_terms = get_the_terms( $product->get_id(), $taxonomy );
			if ( is_array( $product_terms ) ) {
				foreach ( $product_terms as $term ) {
					$terms[ $term->term_id ] = true;
				}
			}
		}

		return count( $terms );
	}

	/**
	 * Whether an order should be treated as a "pallet" order.
	 *
	 * NOT VERIFIED - this cannot be detected reliably today. The B2B Cart
	 * to Order plugin's source was inspected directly (class-b2b-
	 * product-fields.php): it stores `_b2b_bax`, `_b2b_case` and
	 * `_b2b_pallet` as free-text *product* meta ("Bax/Case/Pallet
	 * packaging information", plain text inputs, not validated as
	 * numeric) describing how that product is packaged - there is no
	 * per-*order* field anywhere in that plugin recording whether a given
	 * order/request was placed "by the box" vs. "by the pallet". A
	 * previous version of this file guessed at an order meta key
	 * (`_b2bora_order_format`) that does not actually exist anywhere in
	 * the installed plugin; that guess has been removed.
	 *
	 * Until B2Bora defines and records this distinction somewhere (e.g. a
	 * future field on the order, or a rule based on the per-product
	 * `_b2b_pallet` text once its format is standardised), the only
	 * available proxy is total quantity ordered, which is a rough
	 * heuristic and not a verified business rule. Do not enable a
	 * `pallet_order` mission in production without deciding on and
	 * testing a real rule first - either raise this with whoever
	 * maintains B2B Cart to Order, or supply an accurate check via the
	 * `b2bora_pc_mission_completed` filter, which runs instead of this
	 * method entirely when it returns a non-null-equivalent value.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return bool
	 */
	private static function order_is_pallet_order( $order ) {
		$threshold      = (int) apply_filters( 'b2bora_pc_pallet_order_quantity_threshold', 50 );
		$total_quantity = 0;

		foreach ( $order->get_items() as $item ) {
			$total_quantity += (int) $item->get_quantity();
		}

		return $total_quantity >= $threshold;
	}
}
