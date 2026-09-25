<?php
/**
 * Main plugin bootstrap/orchestrator. Wires together the independently
 * loaded classes once WordPress has finished loading all plugins, so
 * WooCommerce (and other optional integrations such as Polylang) can be
 * reliably detected first.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2Bora_PC_Plugin
 */
class B2Bora_PC_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var B2Bora_PC_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return B2Bora_PC_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Boot the plugin. Safe to call even when WooCommerce or Polylang are
	 * absent: WooCommerce-specific behaviour is skipped (with an admin
	 * notice), and nothing in the plugin calls Polylang functions
	 * directly, so its absence is transparent.
	 */
	public function init() {
		load_plugin_textdomain( B2BORA_PC_TEXT_DOMAIN, false, dirname( B2BORA_PC_BASENAME ) . '/languages' );

		B2Bora_PC_Database::maybe_upgrade();

		if ( is_admin() && class_exists( 'B2Bora_PC_Admin' ) ) {
			B2Bora_PC_Admin::init();
		}

		B2Bora_PC_Shortcodes::init();
		B2Bora_PC_Ajax::init();

		if ( $this->is_woocommerce_active() ) {
			B2Bora_PC_Orders::init();
		} else {
			add_action( 'admin_notices', array( $this, 'render_missing_woocommerce_notice' ) );
		}
	}

	/**
	 * Whether WooCommerce is active and loaded. Checked defensively via
	 * class_exists() rather than assuming plugin load order.
	 *
	 * @return bool
	 */
	public function is_woocommerce_active() {
		return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_order' );
	}

	/**
	 * Admin notice shown when WooCommerce is not active.
	 */
	public function render_missing_woocommerce_notice() {
		if ( ! B2Bora_PC_Security::current_user_can_manage() ) {
			return;
		}
		?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'B2Bora Partner Club requires WooCommerce.', 'b2bora-partner-club' ); ?></p>
		</div>
		<?php
	}
}
