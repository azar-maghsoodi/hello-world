<?php
/**
 * Front-end AJAX endpoints. Every handler authenticates the user,
 * verifies a nonce, and only ever acts on the currently logged-in user's
 * own data - customers can never pass a different user_id to affect
 * someone else's points.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2Bora_PC_Ajax
 */
class B2Bora_PC_Ajax {

	/**
	 * Register AJAX hooks. Registered for logged-in users only; there is
	 * intentionally no `wp_ajax_nopriv_` counterpart since redemption
	 * requires an account.
	 */
	public static function init() {
		add_action( 'wp_ajax_b2bora_pc_redeem_reward', array( __CLASS__, 'handle_redeem_reward' ) );
	}

	/**
	 * Redeem a reward for the current user.
	 */
	public static function handle_redeem_reward() {
		if ( ! B2Bora_PC_Security::verify_ajax_customer_request( 'redeem' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh the page and try again.', 'b2bora-partner-club' ) ), 403 );
		}

		$reward_id = isset( $_POST['reward_id'] ) ? absint( $_POST['reward_id'] ) : 0;

		if ( ! $reward_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid reward.', 'b2bora-partner-club' ) ), 400 );
		}

		$result = B2Bora_PC_Redemptions::redeem( get_current_user_id(), $reward_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Reward redeemed successfully.', 'b2bora-partner-club' ),
				'balance' => B2Bora_PC_Points::get_balance( get_current_user_id() ),
			)
		);
	}
}
