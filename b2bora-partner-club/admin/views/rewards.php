<?php
/**
 * Admin: Rewards catalogue management + redemptions review tab.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tab          = isset( $_GET['tab'] ) && 'redemptions' === $_GET['tab'] ? 'redemptions' : 'catalogue'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$edit_id      = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$editing      = $edit_id ? B2Bora_PC_Rewards::get_reward( $edit_id ) : null;
$rewards      = B2Bora_PC_Rewards::get_rewards();
$types        = B2Bora_PC_Rewards::get_types();
$base_url     = admin_url( 'admin.php?page=b2bora-pc-rewards' );
?>
<div class="wrap b2bora-pc-admin">
	<h1><?php esc_html_e( 'Rewards', 'b2bora-partner-club' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo esc_url( $base_url ); ?>" class="nav-tab <?php echo 'catalogue' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Catalogue', 'b2bora-partner-club' ); ?></a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'redemptions', $base_url ) ); ?>" class="nav-tab <?php echo 'redemptions' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Redemptions', 'b2bora-partner-club' ); ?></a>
	</h2>

	<?php if ( 'redemptions' === $tab ) : ?>

		<?php $redemptions = B2Bora_PC_Redemptions::get_all(); ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'b2bora-partner-club' ); ?></th>
					<th><?php esc_html_e( 'Customer', 'b2bora-partner-club' ); ?></th>
					<th><?php esc_html_e( 'Reward', 'b2bora-partner-club' ); ?></th>
					<th><?php esc_html_e( 'Points', 'b2bora-partner-club' ); ?></th>
					<th><?php esc_html_e( 'Status', 'b2bora-partner-club' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'b2bora-partner-club' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $redemptions ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No redemptions yet.', 'b2bora-partner-club' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $redemptions as $row ) :
					$user = get_userdata( (int) $row['user_id'] );
					?>
					<tr>
						<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $row['created_at'] ) ); ?></td>
						<td><?php echo esc_html( $user ? $user->display_name : '#' . $row['user_id'] ); ?></td>
						<td><?php echo esc_html( $row['reward_name'] ? $row['reward_name'] : __( '(deleted reward)', 'b2bora-partner-club' ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $row['points_spent'] ) ); ?></td>
						<td><?php echo esc_html( ucfirst( $row['status'] ) ); ?></td>
						<td>
							<?php if ( 'pending' === $row['status'] ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="b2bora-pc-inline-form">
									<?php wp_nonce_field( B2Bora_PC_Security::NONCE_ADMIN_ACTION . '_redemption' ); ?>
									<input type="hidden" name="action" value="b2bora_pc_approve_redemption" />
									<input type="hidden" name="id" value="<?php echo esc_attr( $row['id'] ); ?>" />
									<button type="submit" class="button button-small"><?php esc_html_e( 'Approve', 'b2bora-partner-club' ); ?></button>
								</form>
							<?php endif; ?>
							<?php if ( 'approved' === $row['status'] ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="b2bora-pc-inline-form">
									<?php wp_nonce_field( B2Bora_PC_Security::NONCE_ADMIN_ACTION . '_redemption' ); ?>
									<input type="hidden" name="action" value="b2bora_pc_use_redemption" />
									<input type="hidden" name="id" value="<?php echo esc_attr( $row['id'] ); ?>" />
									<button type="submit" class="button button-small"><?php esc_html_e( 'Mark Used', 'b2bora-partner-club' ); ?></button>
								</form>
							<?php endif; ?>
							<?php if ( in_array( $row['status'], array( 'pending', 'approved' ), true ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="b2bora-pc-inline-form" onsubmit="return confirm('<?php echo esc_js( __( 'Cancel this redemption and refund the points?', 'b2bora-partner-club' ) ); ?>');">
									<?php wp_nonce_field( B2Bora_PC_Security::NONCE_ADMIN_ACTION . '_redemption' ); ?>
									<input type="hidden" name="action" value="b2bora_pc_cancel_redemption" />
									<input type="hidden" name="id" value="<?php echo esc_attr( $row['id'] ); ?>" />
									<button type="submit" class="button button-small button-link-delete"><?php esc_html_e( 'Cancel & Refund', 'b2bora-partner-club' ); ?></button>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>

	<?php else : ?>

		<h2><?php echo $editing ? esc_html__( 'Edit Reward', 'b2bora-partner-club' ) : esc_html__( 'Add New Reward', 'b2bora-partner-club' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="b2bora-pc-admin-form">
			<?php wp_nonce_field( B2Bora_PC_Security::NONCE_ADMIN_ACTION . '_reward' ); ?>
			<input type="hidden" name="action" value="b2bora_pc_save_reward" />
			<?php if ( $editing ) : ?>
				<input type="hidden" name="id" value="<?php echo esc_attr( $editing['id'] ); ?>" />
			<?php endif; ?>
			<table class="form-table">
				<tr>
					<th><label for="name"><?php esc_html_e( 'Name', 'b2bora-partner-club' ); ?></label></th>
					<td><input type="text" id="name" name="name" class="regular-text" required value="<?php echo esc_attr( $editing['name'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="description"><?php esc_html_e( 'Description', 'b2bora-partner-club' ); ?></label></th>
					<td><textarea id="description" name="description" class="large-text" rows="3"><?php echo esc_textarea( $editing['description'] ?? '' ); ?></textarea></td>
				</tr>
				<tr>
					<th><label for="points_cost"><?php esc_html_e( 'Points Cost', 'b2bora-partner-club' ); ?></label></th>
					<td><input type="number" id="points_cost" name="points_cost" min="0" step="1" required value="<?php echo esc_attr( $editing['points_cost'] ?? 0 ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="reward_type"><?php esc_html_e( 'Reward Type', 'b2bora-partner-club' ); ?></label></th>
					<td>
						<select id="reward_type" name="reward_type">
							<?php foreach ( $types as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $editing['reward_type'] ?? '', $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="reward_value"><?php esc_html_e( 'Reward Value', 'b2bora-partner-club' ); ?></label></th>
					<td><input type="number" id="reward_value" name="reward_value" step="0.01" value="<?php echo esc_attr( $editing['reward_value'] ?? 0 ); ?>" />
						<p class="description"><?php esc_html_e( 'E.g. the credit amount for an order_credit reward, in your store currency.', 'b2bora-partner-club' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="sort_order"><?php esc_html_e( 'Sort Order', 'b2bora-partner-club' ); ?></label></th>
					<td><input type="number" id="sort_order" name="sort_order" step="1" value="<?php echo esc_attr( $editing['sort_order'] ?? 0 ); ?>" /></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Active', 'b2bora-partner-club' ); ?></th>
					<td><label><input type="checkbox" name="active" value="1" <?php checked( ! isset( $editing ) || ! empty( $editing['active'] ) ); ?> /> <?php esc_html_e( 'Reward is available for redemption', 'b2bora-partner-club' ); ?></label></td>
				</tr>
			</table>
			<?php submit_button( $editing ? __( 'Update Reward', 'b2bora-partner-club' ) : __( 'Add Reward', 'b2bora-partner-club' ) ); ?>
		</form>

		<h2><?php esc_html_e( 'All Rewards', 'b2bora-partner-club' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'b2bora-partner-club' ); ?></th>
					<th><?php esc_html_e( 'Type', 'b2bora-partner-club' ); ?></th>
					<th><?php esc_html_e( 'Points', 'b2bora-partner-club' ); ?></th>
					<th><?php esc_html_e( 'Value', 'b2bora-partner-club' ); ?></th>
					<th><?php esc_html_e( 'Active', 'b2bora-partner-club' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'b2bora-partner-club' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $rewards ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No rewards yet.', 'b2bora-partner-club' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $rewards as $reward ) : ?>
					<tr>
						<td><?php echo esc_html( $reward['name'] ); ?></td>
						<td><?php echo esc_html( $types[ $reward['reward_type'] ] ?? $reward['reward_type'] ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $reward['points_cost'] ) ); ?></td>
						<td><?php echo esc_html( $reward['reward_value'] ); ?></td>
						<td><?php echo $reward['active'] ? esc_html__( 'Yes', 'b2bora-partner-club' ) : esc_html__( 'No', 'b2bora-partner-club' ); ?></td>
						<td>
							<a href="<?php echo esc_url( add_query_arg( 'edit', $reward['id'], $base_url ) ); ?>"><?php esc_html_e( 'Edit', 'b2bora-partner-club' ); ?></a>
							&nbsp;|&nbsp;
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="b2bora-pc-inline-form" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this reward permanently?', 'b2bora-partner-club' ) ); ?>');">
								<?php wp_nonce_field( B2Bora_PC_Security::NONCE_ADMIN_ACTION . '_reward' ); ?>
								<input type="hidden" name="action" value="b2bora_pc_delete_reward" />
								<input type="hidden" name="id" value="<?php echo esc_attr( $reward['id'] ); ?>" />
								<button type="submit" class="button-link button-link-delete"><?php esc_html_e( 'Delete', 'b2bora-partner-club' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>

	<?php endif; ?>
</div>
