<?php
/**
 * Plugin Name:       B2Bora Partner Club
 * Plugin URI:        https://b2bora.com/
 * Description:       B2B loyalty and rewards program for B2Bora wholesale partners. Awards points on confirmed orders, tracks partner levels, missions and reward redemptions.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            B2Bora
 * Text Domain:       b2bora-partner-club
 * Domain Path:       /languages
 *
 * @package B2Bora_Partner_Club
 */

// Block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core plugin constants. All constants are prefixed with B2BORA_PC_
 * to avoid collisions with other plugins/themes on the site.
 */
define( 'B2BORA_PC_VERSION', '1.0.0' );
define( 'B2BORA_PC_DB_VERSION', '1.0.0' );
define( 'B2BORA_PC_FILE', __FILE__ );
define( 'B2BORA_PC_PATH', plugin_dir_path( __FILE__ ) );
define( 'B2BORA_PC_URL', plugin_dir_url( __FILE__ ) );
define( 'B2BORA_PC_BASENAME', plugin_basename( __FILE__ ) );
define( 'B2BORA_PC_TEXT_DOMAIN', 'b2bora-partner-club' );

/**
 * Autoload plugin classes.
 *
 * The plugin intentionally uses a small explicit require map instead of a
 * PSR-4 autoloader: it keeps load order obvious and avoids adding a
 * Composer dependency to a production site for a handful of classes.
 */
function b2bora_pc_load_files() {
	$includes = array(
		'includes/class-security.php',
		'includes/class-logger.php',
		'includes/class-settings.php',
		'includes/class-database.php',
		'includes/class-points.php',
		'includes/class-levels.php',
		'includes/class-rewards.php',
		'includes/class-redemptions.php',
		'includes/class-missions.php',
		'includes/class-orders.php',
		'includes/class-loyalty-api.php',
	);

	foreach ( $includes as $file ) {
		$path = B2BORA_PC_PATH . $file;
		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}

	// Admin-only and public-only files are loaded conditionally.
	if ( is_admin() ) {
		require_once B2BORA_PC_PATH . 'includes/class-admin.php';
	}

	require_once B2BORA_PC_PATH . 'public/class-shortcodes.php';
	require_once B2BORA_PC_PATH . 'public/class-ajax.php';

	require_once B2BORA_PC_PATH . 'includes/class-plugin.php';
}
b2bora_pc_load_files();

/**
 * Declare compatibility with WooCommerce's custom order tables (HPOS).
 * The plugin never queries wp_posts/wp_postmeta directly for orders -
 * every read goes through wc_get_order()/WC_Order's own CRUD methods
 * (see class-orders.php) - so it is compatible whether HPOS is enabled
 * or the site still uses the legacy posts-table storage.
 */
function b2bora_pc_declare_hpos_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			B2BORA_PC_FILE,
			true
		);
	}
}
add_action( 'before_woocommerce_init', 'b2bora_pc_declare_hpos_compatibility' );

/**
 * Boot the plugin once all plugins are loaded, so we can reliably detect
 * WooCommerce and other dependencies.
 */
function b2bora_pc_init() {
	B2Bora_PC_Plugin::instance()->init();
}
add_action( 'plugins_loaded', 'b2bora_pc_init' );

/**
 * Activation: create/upgrade database tables and seed default data.
 * Uses register_activation_hook() + dbDelta() as required; never destroys
 * existing loyalty data on reactivation.
 */
function b2bora_pc_activate() {
	require_once B2BORA_PC_PATH . 'includes/class-database.php';
	B2Bora_PC_Database::install();
}
register_activation_hook( __FILE__, 'b2bora_pc_activate' );

/**
 * Deactivation intentionally does nothing destructive: tables and options
 * are preserved so the plugin can be safely reactivated later.
 */
function b2bora_pc_deactivate() {
	// Intentionally left blank: no data removal on deactivation.
}
register_deactivation_hook( __FILE__, 'b2bora_pc_deactivate' );
