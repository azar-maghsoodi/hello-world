<?php
/**
 * Admin: Tools / diagnostics screen.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$db_version = get_option( B2Bora_PC_Database::DB_VERSION_OPTION, __( 'not installed', 'b2bora-partner-club' ) );
?>
<div class="wrap b2bora-pc-admin">
	<h1><?php esc_html_e( 'Partner Club Tools', 'b2bora-partner-club' ); ?></h1>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Done.', 'b2bora-partner-club' ); ?></p></div>
	<?php endif; ?>

	<h2><?php esc_html_e( 'System Status', 'b2bora-partner-club' ); ?></h2>
	<table class="widefat striped" style="max-width:600px;">
		<tbody>
			<tr><td><?php esc_html_e( 'Plugin version', 'b2bora-partner-club' ); ?></td><td><?php echo esc_html( B2BORA_PC_VERSION ); ?></td></tr>
			<tr><td><?php esc_html_e( 'Database schema version', 'b2bora-partner-club' ); ?></td><td><?php echo esc_html( $db_version ); ?></td></tr>
			<tr><td><?php esc_html_e( 'WooCommerce active', 'b2bora-partner-club' ); ?></td><td><?php echo class_exists( 'WooCommerce' ) ? esc_html__( 'Yes', 'b2bora-partner-club' ) : esc_html__( 'No', 'b2bora-partner-club' ); ?></td></tr>
			<tr><td><?php esc_html_e( 'Polylang active', 'b2bora-partner-club' ); ?></td><td><?php echo function_exists( 'pll_current_language' ) ? esc_html__( 'Yes', 'b2bora-partner-club' ) : esc_html__( 'No', 'b2bora-partner-club' ); ?></td></tr>
		</tbody>
	</table>

	<h2><?php esc_html_e( 'Database', 'b2bora-partner-club' ); ?></h2>
	<p><?php esc_html_e( 'Re-run table installation/upgrade. This never deletes existing data; it only creates missing tables or columns.', 'b2bora-partner-club' ); ?></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( B2Bora_PC_Security::NONCE_ADMIN_ACTION . '_tools' ); ?>
		<input type="hidden" name="action" value="b2bora_pc_reinstall_tables" />
		<?php submit_button( __( 'Check / Repair Database Tables', 'b2bora-partner-club' ), 'secondary' ); ?>
	</form>

	<h2><?php esc_html_e( 'Uninstall Behaviour', 'b2bora-partner-club' ); ?></h2>
	<p>
		<?php
		echo B2Bora_PC_Settings::get( 'delete_data_on_uninstall' )
			? esc_html__( 'Deleting this plugin WILL permanently remove all Partner Club data, because "Delete data on uninstall" is enabled in Settings.', 'b2bora-partner-club' )
			: esc_html__( 'Deleting this plugin will keep all Partner Club data intact. Enable "Delete data on uninstall" in Settings if you want a full cleanup instead.', 'b2bora-partner-club' );
		?>
	</p>
</div>
