<?php
/**
 * Plugin Name:       B2Bora Multilingual Emails
 * Description:       Sends customer-facing WordPress / WooCommerce emails in the customer's saved language (English / Romanian) without Polylang for WooCommerce. Wraps the existing emails in a temporary locale switch; never sends an email of its own.
 * Version:           1.0.0
 * Author:            B2Bora
 * Text Domain:       b2bora-multilingual-emails
 * Requires at least: 6.0
 * Requires PHP:      8.2
 * Requires Plugins:  woocommerce
 *
 * @package B2Bora_Multilingual_Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'B2BORA_ML_VERSION', '1.0.0' );
define( 'B2BORA_ML_FILE', __FILE__ );
define( 'B2BORA_ML_PATH', plugin_dir_path( __FILE__ ) );

require_once B2BORA_ML_PATH . 'includes/class-b2bora-ml-language.php';
require_once B2BORA_ML_PATH . 'includes/class-b2bora-ml-locale.php';
require_once B2BORA_ML_PATH . 'includes/class-b2bora-ml-debug.php';
require_once B2BORA_ML_PATH . 'includes/class-b2bora-ml-capture.php';
require_once B2BORA_ML_PATH . 'includes/class-b2bora-ml-emails.php';
require_once B2BORA_ML_PATH . 'includes/api.php';

/**
 * Boot on plugins_loaded so every dependency (Polylang, WooCommerce) has
 * had the chance to load. Nothing here requires either to be present:
 * every call into them is guarded, and the WooCommerce hooks simply never
 * fire if WooCommerce is inactive.
 */
add_action(
	'plugins_loaded',
	static function () {
		B2Bora_ML_Locale::init();
		B2Bora_ML_Capture::init();
		B2Bora_ML_Emails::init();
		B2Bora_ML_Debug::init();
	},
	20
);
