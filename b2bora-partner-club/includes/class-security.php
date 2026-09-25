<?php
/**
 * Security helpers: capability, nonce and sanitisation shortcuts used
 * throughout the plugin so every admin/AJAX entry point checks access
 * the same way.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2Bora_PC_Security
 */
class B2Bora_PC_Security {

	/**
	 * Capability required to manage the Partner Club (view admin screens,
	 * edit rewards/levels/missions, adjust points manually).
	 */
	const CAP_MANAGE = 'manage_woocommerce';

	/**
	 * Nonce action used for all admin-side POST forms.
	 */
	const NONCE_ADMIN_ACTION = 'b2bora_pc_admin_action';

	/**
	 * Nonce action used for front-end AJAX requests (redemption, etc.).
	 */
	const NONCE_FRONTEND_ACTION = 'b2bora_pc_frontend_action';

	/**
	 * Whether the current user may administer the Partner Club.
	 *
	 * @return bool
	 */
	public static function current_user_can_manage() {
		/**
		 * Filter the capability required to manage B2Bora Partner Club.
		 *
		 * @param string $capability Capability name.
		 */
		$capability = apply_filters( 'b2bora_pc_manage_capability', self::CAP_MANAGE );

		return current_user_can( $capability );
	}

	/**
	 * Verify an admin nonce and capability together, dying with a 403 if
	 * either check fails. Intended for admin-post.php form handlers.
	 *
	 * @param string $action Specific nonce action suffix.
	 */
	public static function verify_admin_request( $action = '' ) {
		$nonce_action = self::NONCE_ADMIN_ACTION . ( $action ? '_' . $action : '' );

		if ( ! self::current_user_can_manage() ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'b2bora-partner-club' ), 403 );
		}

		check_admin_referer( $nonce_action );
	}

	/**
	 * Verify an AJAX nonce for a logged-in, capability-checked request.
	 *
	 * @param string $action    Nonce action suffix.
	 * @param string $query_arg Request field carrying the nonce.
	 *
	 * @return bool
	 */
	public static function verify_ajax_manage_request( $action = '', $query_arg = 'nonce' ) {
		if ( ! self::current_user_can_manage() ) {
			return false;
		}

		$nonce_action = self::NONCE_ADMIN_ACTION . ( $action ? '_' . $action : '' );

		return (bool) check_ajax_referer( $nonce_action, $query_arg, false );
	}

	/**
	 * Verify an AJAX nonce for any logged-in customer (frontend dashboard
	 * actions such as reward redemption). Does not check any elevated
	 * capability; callers must still check the target user_id matches the
	 * current user unless the caller itself is privileged.
	 *
	 * @param string $action    Nonce action suffix.
	 * @param string $query_arg Request field carrying the nonce.
	 *
	 * @return bool
	 */
	public static function verify_ajax_customer_request( $action = '', $query_arg = 'nonce' ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$nonce_action = self::NONCE_FRONTEND_ACTION . ( $action ? '_' . $action : '' );

		return (bool) check_ajax_referer( $nonce_action, $query_arg, false );
	}

	/**
	 * Sanitise and validate a WordPress user ID.
	 *
	 * @param mixed $user_id Raw user id.
	 *
	 * @return int 0 if invalid/unknown user.
	 */
	public static function sanitize_user_id( $user_id ) {
		$user_id = absint( $user_id );

		if ( $user_id <= 0 || ! get_userdata( $user_id ) ) {
			return 0;
		}

		return $user_id;
	}
}
