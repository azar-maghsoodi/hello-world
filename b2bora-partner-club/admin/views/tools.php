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

	<h2><?php esc_html_e( 'Reconcile Balances', 'b2bora-partner-club' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Compares each customer\'s current balance (the latest ledger entry, used everywhere in the plugin) against a balance recomputed independently by summing every ledger entry from scratch. The two should always match; a mismatch means a bug or a manual database edit corrupted the running total for that customer at some point. This never changes anything by itself.', 'b2bora-partner-club' ); ?>
	</p>

	<?php
	$member_ids = B2Bora_PC_Points::get_all_member_ids();
	$rows       = array();

	foreach ( $member_ids as $member_id ) {
		$ledger_balance   = B2Bora_PC_Points::get_recomputed_balance( $member_id );
		$recorded_balance = B2Bora_PC_Points::get_balance( $member_id );

		$rows[] = array(
			'user_id'          => $member_id,
			'ledger_balance'   => $ledger_balance,
			'recorded_balance' => $recorded_balance,
			'mismatch'         => $ledger_balance !== $recorded_balance,
		);
	}

	usort( $rows, static function ( $a, $b ) {
		return (int) $b['mismatch'] <=> (int) $a['mismatch'];
	} );

	$mismatch_count = count( array_filter( $rows, static fn( $row ) => $row['mismatch'] ) );
	?>

	<?php if ( $mismatch_count > 0 ) : ?>
		<div class="notice notice-warning inline"><p>
			<?php
			printf(
				/* translators: %d: number of customers with a mismatched balance */
				esc_html( _n( '%d customer has a balance mismatch.', '%d customers have a balance mismatch.', $mismatch_count, 'b2bora-partner-club' ) ),
				(int) $mismatch_count
			);
			?>
		</p></div>
	<?php endif; ?>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Customer', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Ledger Balance', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Recorded Balance', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Status', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'b2bora-partner-club' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $rows ) ) : ?>
			<tr><td colspan="5"><?php esc_html_e( 'No members yet.', 'b2bora-partner-club' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $rows as $row ) :
				$user = get_userdata( $row['user_id'] );
				?>
				<tr>
					<td><?php echo esc_html( $user ? $user->display_name : '#' . $row['user_id'] ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $row['ledger_balance'] ) ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $row['recorded_balance'] ) ); ?></td>
					<td>
						<?php if ( $row['mismatch'] ) : ?>
							<strong class="b2bora-pc-negative"><?php esc_html_e( 'MISMATCH', 'b2bora-partner-club' ); ?></strong>
						<?php else : ?>
							<?php esc_html_e( 'OK', 'b2bora-partner-club' ); ?>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $row['mismatch'] ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="b2bora-pc-inline-form" onsubmit="return confirm('<?php echo esc_js( __( 'Create a correcting transaction so this customer\'s balance matches the ledger total? This cannot be undone.', 'b2bora-partner-club' ) ); ?>');">
								<?php wp_nonce_field( B2Bora_PC_Security::NONCE_ADMIN_ACTION . '_repair_balance' ); ?>
								<input type="hidden" name="action" value="b2bora_pc_repair_balance" />
								<input type="hidden" name="user_id" value="<?php echo esc_attr( $row['user_id'] ); ?>" />
								<input type="hidden" name="reason" value="<?php echo esc_attr__( 'Reconcile Balances tool: corrected to match independently recomputed ledger total.', 'b2bora-partner-club' ); ?>" />
								<button type="submit" class="button button-small"><?php esc_html_e( 'Repair Balance', 'b2bora-partner-club' ); ?></button>
							</form>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
