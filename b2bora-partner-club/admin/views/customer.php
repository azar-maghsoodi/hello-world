<?php
/**
 * Admin: Single customer detail + manual adjustment form.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = isset( $_GET['user_id'] ) ? B2Bora_PC_Security::sanitize_user_id( $_GET['user_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$user    = $user_id ? get_userdata( $user_id ) : false;

if ( ! $user ) {
	echo '<div class="wrap"><p>' . esc_html__( 'Customer not found.', 'b2bora-partner-club' ) . '</p></div>';
	return;
}

$balance     = B2Bora_PC_Points::get_balance( $user_id );
$progress    = B2Bora_PC_Levels::get_progress_for_user( $user_id );
$history     = B2Bora_PC_Points::get_history( $user_id, 50 );
$redemptions = B2Bora_PC_Redemptions::get_for_user( $user_id, 20 );
?>
<div class="wrap b2bora-pc-admin">
	<h1>
		<?php
		printf(
			/* translators: %s: customer display name */
			esc_html__( 'Customer: %s', 'b2bora-partner-club' ),
			esc_html( $user->display_name )
		);
		?>
	</h1>
	<p>
		<strong><?php esc_html_e( 'Email:', 'b2bora-partner-club' ); ?></strong> <?php echo esc_html( $user->user_email ); ?>
		&nbsp;|&nbsp;
		<strong><?php esc_html_e( 'Balance:', 'b2bora-partner-club' ); ?></strong> <?php echo esc_html( number_format_i18n( $balance ) ); ?>
		&nbsp;|&nbsp;
		<strong><?php esc_html_e( 'Level:', 'b2bora-partner-club' ); ?></strong> <?php echo esc_html( $progress['current_level'] ? $progress['current_level']['name'] : '—' ); ?>
	</p>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved.', 'b2bora-partner-club' ); ?></p></div>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Manual Adjustment', 'b2bora-partner-club' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="b2bora-pc-admin-form">
		<?php wp_nonce_field( B2Bora_PC_Security::NONCE_ADMIN_ACTION . '_manual_adjustment' ); ?>
		<input type="hidden" name="action" value="b2bora_pc_manual_adjustment" />
		<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>" />
		<table class="form-table">
			<tr>
				<th><label for="points"><?php esc_html_e( 'Points (use a negative number to deduct)', 'b2bora-partner-club' ); ?></label></th>
				<td><input type="number" id="points" name="points" step="1" required /></td>
			</tr>
			<tr>
				<th><label for="reason"><?php esc_html_e( 'Reason (required)', 'b2bora-partner-club' ); ?></label></th>
				<td><input type="text" id="reason" name="reason" class="regular-text" required /></td>
			</tr>
		</table>
		<?php submit_button( __( 'Apply Adjustment', 'b2bora-partner-club' ) ); ?>
	</form>

	<h2><?php esc_html_e( 'Transaction History', 'b2bora-partner-club' ); ?></h2>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Date', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Type', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Points', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Balance After', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Description', 'b2bora-partner-club' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $history ) ) : ?>
			<tr><td colspan="5"><?php esc_html_e( 'No transactions yet.', 'b2bora-partner-club' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $history as $row ) : ?>
				<tr>
					<td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row['created_at'] ) ); ?></td>
					<td><?php echo esc_html( $row['type'] ); ?></td>
					<td class="<?php echo $row['points'] >= 0 ? 'b2bora-pc-positive' : 'b2bora-pc-negative'; ?>">
						<?php echo esc_html( ( $row['points'] >= 0 ? '+' : '' ) . number_format_i18n( (int) $row['points'] ) ); ?>
					</td>
					<td><?php echo esc_html( number_format_i18n( (int) $row['balance_after'] ) ); ?></td>
					<td><?php echo esc_html( $row['description'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

	<h2><?php esc_html_e( 'Rewards Redeemed', 'b2bora-partner-club' ); ?></h2>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Date', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Reward', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Points Spent', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Status', 'b2bora-partner-club' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $redemptions ) ) : ?>
			<tr><td colspan="4"><?php esc_html_e( 'No redemptions yet.', 'b2bora-partner-club' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $redemptions as $row ) : ?>
				<tr>
					<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $row['created_at'] ) ); ?></td>
					<td><?php echo esc_html( $row['reward_name'] ? $row['reward_name'] : __( '(deleted reward)', 'b2bora-partner-club' ) ); ?></td>
					<td><?php echo esc_html( number_format_i18n( (int) $row['points_spent'] ) ); ?></td>
					<td><?php echo esc_html( ucfirst( $row['status'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
