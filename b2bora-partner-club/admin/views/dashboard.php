<?php
/**
 * Admin: Dashboard overview.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$total_members       = B2Bora_PC_Points::get_total_members();
$total_points_issued  = B2Bora_PC_Points::get_total_points_issued();
$total_points_redeemed = B2Bora_PC_Points::get_total_points_redeemed();
$active_rewards       = B2Bora_PC_Rewards::count_active();
$recent_transactions  = B2Bora_PC_Points::get_recent_transactions( 10 );
?>
<div class="wrap b2bora-pc-admin">
	<h1><?php esc_html_e( 'B2Bora Partner Club', 'b2bora-partner-club' ); ?></h1>

	<div class="b2bora-pc-admin-cards">
		<div class="b2bora-pc-admin-card">
			<span class="b2bora-pc-admin-card-label"><?php esc_html_e( 'Total Members', 'b2bora-partner-club' ); ?></span>
			<span class="b2bora-pc-admin-card-value"><?php echo esc_html( number_format_i18n( $total_members ) ); ?></span>
		</div>
		<div class="b2bora-pc-admin-card">
			<span class="b2bora-pc-admin-card-label"><?php esc_html_e( 'Total Points Issued', 'b2bora-partner-club' ); ?></span>
			<span class="b2bora-pc-admin-card-value"><?php echo esc_html( number_format_i18n( $total_points_issued ) ); ?></span>
		</div>
		<div class="b2bora-pc-admin-card">
			<span class="b2bora-pc-admin-card-label"><?php esc_html_e( 'Total Points Redeemed', 'b2bora-partner-club' ); ?></span>
			<span class="b2bora-pc-admin-card-value"><?php echo esc_html( number_format_i18n( $total_points_redeemed ) ); ?></span>
		</div>
		<div class="b2bora-pc-admin-card">
			<span class="b2bora-pc-admin-card-label"><?php esc_html_e( 'Active Rewards', 'b2bora-partner-club' ); ?></span>
			<span class="b2bora-pc-admin-card-value"><?php echo esc_html( number_format_i18n( $active_rewards ) ); ?></span>
		</div>
	</div>

	<h2><?php esc_html_e( 'Recent Transactions', 'b2bora-partner-club' ); ?></h2>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Date', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Customer', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Type', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Points', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Balance After', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Description', 'b2bora-partner-club' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $recent_transactions ) ) : ?>
			<tr><td colspan="6"><?php esc_html_e( 'No transactions yet.', 'b2bora-partner-club' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $recent_transactions as $row ) :
				$user = get_userdata( (int) $row['user_id'] );
				?>
				<tr>
					<td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row['created_at'] ) ); ?></td>
					<td>
						<?php if ( $user ) : ?>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'b2bora-pc-customer', 'user_id' => $user->ID ), admin_url( 'admin.php' ) ) ); ?>">
								<?php echo esc_html( $user->display_name ); ?>
							</a>
						<?php else : ?>
							<?php echo esc_html__( 'Unknown user', 'b2bora-partner-club' ); ?>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $row['type'] ); ?></td>
					<td><?php echo B2Bora_PC_Admin::format_points_html( $row['points'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped by format_points_html(). ?></td>
					<td><?php echo esc_html( number_format_i18n( (int) $row['balance_after'] ) ); ?></td>
					<td><?php echo esc_html( $row['description'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
