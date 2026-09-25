<?php
/**
 * Front-end shortcodes.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2Bora_PC_Shortcodes
 */
class B2Bora_PC_Shortcodes {

	/**
	 * Register shortcodes and the assets they need.
	 */
	public static function init() {
		add_shortcode( 'b2bora_partner_club', array( __CLASS__, 'render_dashboard' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_assets' ) );
	}

	/**
	 * Enqueue the dashboard CSS/JS only on pages that actually use the
	 * shortcode, keeping the footprint on the rest of the (Ekomart) site
	 * at zero. This must run on `wp_enqueue_scripts` (before `wp_head`)
	 * rather than from inside render_dashboard() itself: styles enqueued
	 * after `wp_head` has already fired are never printed, since only
	 * scripts (not styles) get a second pass at `wp_footer`.
	 *
	 * Detecting "does this page use the shortcode" ahead of time is not
	 * just a plain `has_shortcode( $post->post_content, ... )` check:
	 * B2Bora builds pages with Elementor, which stores its content as
	 * JSON in the `_elementor_data` post meta rather than in
	 * `post_content` - a shortcode added via Elementor's own Shortcode
	 * widget would never be found by `has_shortcode()` alone. This checks
	 * both.
	 */
	public static function maybe_enqueue_assets() {
		if ( ! is_singular() ) {
			return;
		}

		global $post;
		if ( ! $post || ! self::page_contains_shortcode( $post ) ) {
			return;
		}

		wp_enqueue_style( 'b2bora-pc-frontend', B2BORA_PC_URL . 'assets/css/partner-club.css', array(), B2BORA_PC_VERSION );
		wp_enqueue_script( 'b2bora-pc-frontend', B2BORA_PC_URL . 'assets/js/partner-club.js', array(), B2BORA_PC_VERSION, true );

		wp_localize_script(
			'b2bora-pc-frontend',
			'b2boraPartnerClub',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( B2Bora_PC_Security::NONCE_FRONTEND_ACTION . '_redeem' ),
				'i18n'    => array(
					'confirmRedeem' => __( 'Redeem this reward for the points shown?', 'b2bora-partner-club' ),
					'error'         => __( 'Something went wrong. Please try again.', 'b2bora-partner-club' ),
				),
			)
		);
	}

	/**
	 * Whether a given post's content - through the normal editor or
	 * through Elementor - contains the [b2bora_partner_club] shortcode.
	 *
	 * @param WP_Post $post Post object.
	 *
	 * @return bool
	 */
	private static function page_contains_shortcode( $post ) {
		if ( has_shortcode( (string) $post->post_content, 'b2bora_partner_club' ) ) {
			return true;
		}

		$elementor_data = get_post_meta( $post->ID, '_elementor_data', true );

		return is_string( $elementor_data ) && false !== strpos( $elementor_data, 'b2bora_partner_club' );
	}

	/**
	 * Render the [b2bora_partner_club] shortcode.
	 *
	 * @return string
	 */
	public static function render_dashboard() {
		if ( ! is_user_logged_in() ) {
			ob_start();
			?>
			<div class="b2bora-pc-dashboard b2bora-pc-logged-out">
				<p><?php esc_html_e( 'Please log in to view your B2Bora Partner Club dashboard.', 'b2bora-partner-club' ); ?></p>
				<a class="b2bora-pc-button" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log In', 'b2bora-partner-club' ); ?></a>
			</div>
			<?php
			return ob_get_clean();
		}

		$user_id = get_current_user_id();

		$balance     = B2Bora_PC_Points::get_balance( $user_id );
		$progress    = B2Bora_PC_Levels::get_progress_for_user( $user_id );
		$rewards     = B2Bora_PC_Rewards::get_rewards( true );
		$history     = B2Bora_PC_Points::get_history( $user_id, 10 );
		$missions    = B2Bora_PC_Missions::get_missions( true );
		$redemptions = B2Bora_PC_Redemptions::get_for_user( $user_id, 10 );

		ob_start();
		require B2BORA_PC_PATH . 'public/views/dashboard.php';
		return ob_get_clean();
	}
}
