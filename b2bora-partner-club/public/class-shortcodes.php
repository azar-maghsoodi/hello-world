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
		add_filter( 'body_class', array( __CLASS__, 'maybe_add_account_body_class' ) );
	}

	/**
	 * Add WooCommerce's own "woocommerce-page"/"woocommerce-account" body
	 * classes on pages that show the shortcode's logged-out login/register
	 * form. Confirmed by comparing this site's real /my-account/ page
	 * markup against the shortcode's output: the form HTML and the
	 * WooCommerce/theme stylesheets are identical on both, but the real
	 * account page's <body> carries these two extra classes and this
	 * page's does not - the theme's account-page CSS is scoped to them,
	 * which is why the embedded form otherwise renders unstyled. Must run
	 * on the `body_class` filter (fired in the theme header, before the
	 * page content/shortcode itself executes) rather than from inside
	 * render_dashboard(), which runs too late to affect the already-output
	 * body tag.
	 *
	 * @param string[] $classes Existing body classes.
	 *
	 * @return string[]
	 */
	public static function maybe_add_account_body_class( $classes ) {
		if ( is_user_logged_in() ) {
			return $classes;
		}

		$post = get_queried_object();
		if ( ! ( $post instanceof WP_Post ) || ! self::page_contains_shortcode( $post ) ) {
			return $classes;
		}

		$classes[] = 'woocommerce-page';
		$classes[] = 'woocommerce-account';

		// The active theme's own account-page styling (the white card,
		// green buttons, 2-column login/register layout) is scoped to
		// "body.ekomart-wc.woocommerce-account" specifically, not just
		// "woocommerce-account" - confirmed by reading the theme's real
		// woocommerce.css. Every other rule using .ekomart-wc also
		// requires a second class we are not adding here (.woocommerce,
		// .single-product, .woocommerce-cart, .woocommerce-checkout), so
		// this only switches on the account-page block, nothing else.
		$classes[] = 'ekomart-wc';

		return $classes;
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
					'confirmRedeem' => B2Bora_PC_Translations::get( 'js_confirm_redeem' ),
					'error'         => B2Bora_PC_Translations::get( 'js_error' ),
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
				<p><?php echo esc_html( B2Bora_PC_Translations::get( 'logged_out_message' ) ); ?></p>
				<?php if ( shortcode_exists( 'woocommerce_my_account' ) ) : ?>
					<div class="b2bora-pc-login-form">
						<?php
						// WooCommerce's own login/register form (the exact
						// same one the store's My Account page uses), so it
						// inherits the theme's styling and, if the store
						// allows account creation, shows a register form
						// alongside login - rather than sending the visitor
						// away to wp-login.php.
						echo do_shortcode( '[woocommerce_my_account]' );
						?>
					</div>
				<?php else : ?>
					<a class="b2bora-pc-button" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php echo esc_html( B2Bora_PC_Translations::get( 'login_button' ) ); ?></a>
				<?php endif; ?>
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
