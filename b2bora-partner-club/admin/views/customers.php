<?php
/**
 * Admin: Customers list with search.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$user_query_args = array(
	'number' => 50,
	'fields' => array( 'ID', 'user_email', 'display_name' ),
);

if ( '' !== $search ) {
	if ( is_numeric( $search ) ) {
		$user_query_args['include'] = array( absint( $search ) );
	} else {
		$user_query_args['search']         = '*' . $search . '*';
		$user_query_args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
	}
}

$users = get_users( $user_query_args );
?>
<div class="wrap b2bora-pc-admin">
	<h1><?php esc_html_e( 'Partner Club Customers', 'b2bora-partner-club' ); ?></h1>

	<form method="get" action="">
		<input type="hidden" name="page" value="b2bora-pc-customers" />
		<p class="search-box">
			<label class="screen-reader-text" for="b2bora-pc-customer-search"><?php esc_html_e( 'Search customers', 'b2bora-partner-club' ); ?></label>
			<input type="search" id="b2bora-pc-customer-search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Name, email or user ID', 'b2bora-partner-club' ); ?>" />
			<?php submit_button( __( 'Search Customers', 'b2bora-partner-club' ), '', '', false ); ?>
		</p>
	</form>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Customer', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Email', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Points Balance', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Level', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Orders', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Last Activity', 'b2bora-partner-club' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $users ) ) : ?>
			<tr><td colspan="6"><?php esc_html_e( 'No customers found.', 'b2bora-partner-club' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $users as $user ) :
				$balance     = B2Bora_PC_Points::get_balance( $user->ID );
				$level       = B2Bora_PC_Levels::get_level_for_user( $user->ID );
				$order_count = B2Bora_PC_Points::count_transactions_of_type( $user->ID, B2Bora_PC_Points::TYPE_ORDER );
				$history     = B2Bora_PC_Points::get_history( $user->ID, 1 );
				$last        = $history ? mysql2date( get_option( 'date_format' ), $history[0]['created_at'] ) : '—';
				?>
				<tr>
					<td>
						<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'b2bora-pc-customer', 'user_id' => $user->ID ), admin_url( 'admin.php' ) ) ); ?>">
							<?php echo esc_html( $user->display_name ); ?>
						</a>
					</td>
					<td><?php echo esc_html( $user->user_email ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $balance ) ); ?></td>
					<td><?php echo esc_html( $level ? $level['name'] : '—' ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $order_count ) ); ?></td>
					<td><?php echo esc_html( $last ); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
