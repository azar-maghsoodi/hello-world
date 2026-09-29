<?php
/**
 * Uninstall: remove only this plugin's own option. User/order language meta
 * is deliberately kept (it is customer data other code may rely on).
 *
 * @package B2Bora_Multilingual_Emails
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'b2bora_ml_debug' );
