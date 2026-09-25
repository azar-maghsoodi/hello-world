<?php
/**
 * Public service-layer facade for other plugins (in particular B2Bora's
 * own B2B order/cart plugin) to integrate with the Partner Club without
 * needing to know about its internal class structure.
 *
 * Preferred integration is still the `b2bora_order_completed` action;
 * this class exists for callers that prefer a direct method call, e.g.:
 *
 *     if ( class_exists( 'B2Bora_Partner_Club_Loyalty' ) ) {
 *         B2Bora_Partner_Club_Loyalty::award_order_points( $order_id );
 *     }
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2Bora_Partner_Club_Loyalty
 */
class B2Bora_Partner_Club_Loyalty {

	/**
	 * Award points for a confirmed/completed order. Equivalent to firing
	 * `do_action( 'b2bora_order_completed', $order_id )`; idempotent.
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public static function award_order_points( $order_id ) {
		if ( ! class_exists( 'B2Bora_PC_Orders' ) ) {
			return;
		}

		B2Bora_PC_Orders::handle_order_completed( $order_id );
	}

	/**
	 * Reverse points for a refunded order/refund pair.
	 *
	 * @param int $order_id  WooCommerce order ID.
	 * @param int $refund_id WooCommerce refund ID.
	 */
	public static function reverse_order_points( $order_id, $refund_id ) {
		if ( ! class_exists( 'B2Bora_PC_Orders' ) ) {
			return;
		}

		B2Bora_PC_Orders::handle_order_refunded( $order_id, $refund_id );
	}

	/**
	 * Get a customer's current point balance.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int
	 */
	public static function get_balance( $user_id ) {
		return B2Bora_PC_Points::get_balance( $user_id );
	}

	/**
	 * Get a customer's current partner level.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array|null Level row, or null if no levels are configured.
	 */
	public static function get_level( $user_id ) {
		return class_exists( 'B2Bora_PC_Levels' ) ? B2Bora_PC_Levels::get_level_for_user( $user_id ) : null;
	}

	/**
	 * Apply a manual point adjustment (admin tools only; this does not
	 * itself perform a capability check, callers must have already
	 * verified the acting user is authorised).
	 *
	 * @param int    $user_id User ID.
	 * @param int    $points  Signed point delta.
	 * @param string $reason  Required human-readable reason.
	 *
	 * @return int|false Transaction ID, or false on failure.
	 */
	public static function manual_adjustment( $user_id, $points, $reason ) {
		if ( empty( $reason ) ) {
			return false;
		}

		return B2Bora_PC_Points::record_transaction(
			$user_id,
			B2Bora_PC_Points::TYPE_MANUAL,
			(int) $points,
			array( 'description' => sanitize_text_field( $reason ) )
		);
	}
}
