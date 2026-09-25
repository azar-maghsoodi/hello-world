<?php
/**
 * Admin: All transactions listing.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$transactions = B2Bora_PC_Points::get_recent_transactions( 100 );
?>
<div class="wrap b2bora-pc-admin">
	<h1><?php esc_html_e( 'Points Transactions', 'b2bora-partner-club' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Showing the 100 most recent transactions across all customers. This ledger is append-only: corrections are made with reversing entries, never by editing or deleting rows.', 'b2bora-partner-club' ); ?></p>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Date', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Customer', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Order', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Type', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Points', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Balance After', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Description', 'b2bora-partner-club' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $transactions ) ) : ?>
			<tr><td colspan="7"><?php esc_html_e( 'No transactions yet.', 'b2bora-partner-club' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $transactions as $row ) :
				$user = get_userdata( (int) $row['user_id'] );
				?>
				<tr>
					<td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row['created_at'] ) ); ?></td>
					<td>
						<?php if ( $user ) : ?>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'b2bora-pc-customer', 'user_id' => $user->ID ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $user->display_name ); ?></a>
						<?php else : ?>
							#<?php echo esc_html( $row['user_id'] ); ?>
						<?php endif; ?>
					</td>
					<td><?php echo $row['order_id'] ? esc_html( '#' . $row['order_id'] ) : '—'; ?></td>
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
</div>
