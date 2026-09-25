<?php
/**
 * Admin: Settings screen.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = B2Bora_PC_Settings::all();
$currency = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '';
?>
<div class="wrap b2bora-pc-admin">
	<h1><?php esc_html_e( 'Partner Club Settings', 'b2bora-partner-club' ); ?></h1>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'b2bora-partner-club' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( B2Bora_PC_Security::NONCE_ADMIN_ACTION . '_settings' ); ?>
		<input type="hidden" name="action" value="b2bora_pc_save_settings" />

		<h2><?php esc_html_e( 'Points Earning', 'b2bora-partner-club' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="points_per_currency_unit"><?php esc_html_e( 'Points per currency unit', 'b2bora-partner-club' ); ?></label></th>
				<td>
					<input type="number" id="points_per_currency_unit" name="b2bora_pc[points_per_currency_unit]" min="0" step="1" value="<?php echo esc_attr( $settings['points_per_currency_unit'] ); ?>" />
					<p class="description"><?php echo esc_html( sprintf( /* translators: %s: currency symbol, if WooCommerce is active */ __( 'Integer points awarded per 1 unit of eligible order value%s.', 'b2bora-partner-club' ), $currency ? ' (' . $currency . ')' : '' ) ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Exclude from eligible amount', 'b2bora-partner-club' ); ?></th>
				<td>
					<label><input type="checkbox" name="b2bora_pc[exclude_tax]" value="1" <?php checked( $settings['exclude_tax'] ); ?> /> <?php esc_html_e( 'Tax', 'b2bora-partner-club' ); ?></label><br />
					<label><input type="checkbox" name="b2bora_pc[exclude_shipping]" value="1" <?php checked( $settings['exclude_shipping'] ); ?> /> <?php esc_html_e( 'Shipping', 'b2bora-partner-club' ); ?></label><br />
					<label><input type="checkbox" name="b2bora_pc[exclude_refunded]" value="1" <?php checked( $settings['exclude_refunded'] ); ?> /> <?php esc_html_e( 'Refunded amount', 'b2bora-partner-club' ); ?></label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Bonuses', 'b2bora-partner-club' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Welcome bonus', 'b2bora-partner-club' ); ?></th>
				<td>
					<label><input type="checkbox" name="b2bora_pc[welcome_bonus_enabled]" value="1" <?php checked( $settings['welcome_bonus_enabled'] ); ?> /> <?php esc_html_e( 'Enabled', 'b2bora-partner-club' ); ?></label>
					&nbsp; <input type="number" name="b2bora_pc[welcome_bonus_points]" min="0" step="1" value="<?php echo esc_attr( $settings['welcome_bonus_points'] ); ?>" /> <?php esc_html_e( 'points, awarded once on the customer\'s first confirmed order.', 'b2bora-partner-club' ); ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Reorder bonus', 'b2bora-partner-club' ); ?></th>
				<td>
					<label><input type="checkbox" name="b2bora_pc[reorder_bonus_enabled]" value="1" <?php checked( $settings['reorder_bonus_enabled'] ); ?> /> <?php esc_html_e( 'Enabled', 'b2bora-partner-club' ); ?></label>
					&nbsp; <input type="number" name="b2bora_pc[reorder_bonus_points]" min="0" step="1" value="<?php echo esc_attr( $settings['reorder_bonus_points'] ); ?>" /> <?php esc_html_e( 'points if the next order is confirmed within', 'b2bora-partner-club' ); ?>
					<input type="number" name="b2bora_pc[reorder_window_days]" min="1" step="1" value="<?php echo esc_attr( $settings['reorder_window_days'] ); ?>" style="width:70px;" /> <?php esc_html_e( 'days.', 'b2bora-partner-club' ); ?>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Order Integration', 'b2bora-partner-club' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'WooCommerce fallback', 'b2bora-partner-club' ); ?></th>
				<td>
					<label><input type="checkbox" name="b2bora_pc[enable_wc_completed_fallback]" value="1" <?php checked( $settings['enable_wc_completed_fallback'] ); ?> /> <?php esc_html_e( 'Also award points when a standard WooCommerce order transitions to "Completed".', 'b2bora-partner-club' ); ?></label>
					<p class="description"><?php esc_html_e( 'Enabled by default: the installed B2B Cart to Order plugin has no confirmation event of its own, so staff manually marking an order "Completed" in WooCommerce -> Orders is the genuine confirmation signal today. Turn this off only if B2B Cart to Order (or another plugin) is updated to fire b2bora_order_completed at its own real confirmation point.', 'b2bora-partner-club' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Refund reversal', 'b2bora-partner-club' ); ?></th>
				<td><label><input type="checkbox" name="b2bora_pc[enable_refund_reversal]" value="1" <?php checked( $settings['enable_refund_reversal'] ); ?> /> <?php esc_html_e( 'Automatically reverse points when an order is refunded.', 'b2bora-partner-club' ); ?></label></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Points Expiration', 'b2bora-partner-club' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Expiration', 'b2bora-partner-club' ); ?></th>
				<td>
					<label><input type="checkbox" name="b2bora_pc[points_expiration_enabled]" value="1" <?php checked( $settings['points_expiration_enabled'] ); ?> /> <?php esc_html_e( 'Enabled', 'b2bora-partner-club' ); ?></label>
					&nbsp; <?php esc_html_e( 'after', 'b2bora-partner-club' ); ?>
					<input type="number" name="b2bora_pc[points_expiration_days]" min="1" step="1" value="<?php echo esc_attr( $settings['points_expiration_days'] ); ?>" /> <?php esc_html_e( 'days.', 'b2bora-partner-club' ); ?>
					<p class="description"><?php esc_html_e( 'V1 exposes this setting for forward compatibility; no automated expiration job runs points down yet. Use manual adjustments if you need to expire points today.', 'b2bora-partner-club' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Levels & Redemptions', 'b2bora-partner-club' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="level_basis"><?php esc_html_e( 'Level basis', 'b2bora-partner-club' ); ?></label></th>
				<td>
					<select id="level_basis" name="b2bora_pc[level_basis]">
						<option value="lifetime" <?php selected( $settings['level_basis'], 'lifetime' ); ?>><?php esc_html_e( 'Lifetime points earned', 'b2bora-partner-club' ); ?></option>
						<option value="balance" <?php selected( $settings['level_basis'], 'balance' ); ?>><?php esc_html_e( 'Current point balance', 'b2bora-partner-club' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Redemption approval', 'b2bora-partner-club' ); ?></th>
				<td><label><input type="checkbox" name="b2bora_pc[redemption_requires_approval]" value="1" <?php checked( $settings['redemption_requires_approval'] ); ?> /> <?php esc_html_e( 'New redemptions start as "pending" and require admin approval before use.', 'b2bora-partner-club' ); ?></label></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Uninstall', 'b2bora-partner-club' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Delete data on uninstall', 'b2bora-partner-club' ); ?></th>
				<td>
					<label><input type="checkbox" name="b2bora_pc[delete_data_on_uninstall]" value="1" <?php checked( $settings['delete_data_on_uninstall'] ); ?> /> <?php esc_html_e( 'Permanently delete all Partner Club tables and settings when the plugin is deleted from Plugins screen.', 'b2bora-partner-club' ); ?></label>
					<p class="description"><?php esc_html_e( 'Off by default. Loyalty data is never deleted automatically; this must be explicitly enabled here before an uninstall will remove anything.', 'b2bora-partner-club' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save Settings', 'b2bora-partner-club' ) ); ?>
	</form>
</div>
