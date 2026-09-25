<?php
/**
 * Uninstall handler.
 *
 * WordPress only executes this file when the plugin is deleted from the
 * Plugins screen (never on simple deactivation). By default it does
 * nothing: loyalty data is never removed automatically. Data is only
 * deleted when the admin has explicitly enabled "Delete data on
 * uninstall" in Settings beforehand - that setting is the required
 * explicit confirmation.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$settings = get_option( 'b2bora_pc_settings', array() );

if ( empty( $settings['delete_data_on_uninstall'] ) ) {
	// No explicit confirmation: keep every table, option and transient.
	return;
}

global $wpdb;

$tables = array(
	$wpdb->prefix . 'b2bora_points_transactions',
	$wpdb->prefix . 'b2bora_rewards',
	$wpdb->prefix . 'b2bora_redemptions',
	$wpdb->prefix . 'b2bora_levels',
	$wpdb->prefix . 'b2bora_missions',
);

foreach ( $tables as $table ) {
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

delete_option( 'b2bora_pc_settings' );
delete_option( 'b2bora_pc_db_version' );
delete_option( 'b2bora_pc_defaults_seeded' );
