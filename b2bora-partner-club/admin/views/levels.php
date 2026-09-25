<?php
/**
 * Admin: Partner Levels management.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edit_id  = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$editing  = $edit_id ? B2Bora_PC_Levels::get_level( $edit_id ) : null;
$levels   = B2Bora_PC_Levels::get_levels( true );
$base_url = admin_url( 'admin.php?page=b2bora-pc-levels' );
?>
<div class="wrap b2bora-pc-admin">
	<h1><?php esc_html_e( 'Partner Levels', 'b2bora-partner-club' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Levels are based on a customer\'s qualifying points (configurable in Settings as lifetime points or current balance).', 'b2bora-partner-club' ); ?></p>

	<h2><?php echo $editing ? esc_html__( 'Edit Level', 'b2bora-partner-club' ) : esc_html__( 'Add New Level', 'b2bora-partner-club' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="b2bora-pc-admin-form">
		<?php wp_nonce_field( B2Bora_PC_Security::NONCE_ADMIN_ACTION . '_level' ); ?>
		<input type="hidden" name="action" value="b2bora_pc_save_level" />
		<?php if ( $editing ) : ?>
			<input type="hidden" name="id" value="<?php echo esc_attr( $editing['id'] ); ?>" />
		<?php endif; ?>
		<table class="form-table">
			<tr>
				<th><label for="name"><?php esc_html_e( 'Name', 'b2bora-partner-club' ); ?></label></th>
				<td><input type="text" id="name" name="name" class="regular-text" required value="<?php echo esc_attr( $editing['name'] ?? '' ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="minimum_points"><?php esc_html_e( 'Minimum Points', 'b2bora-partner-club' ); ?></label></th>
				<td><input type="number" id="minimum_points" name="minimum_points" min="0" step="1" required value="<?php echo esc_attr( $editing['minimum_points'] ?? 0 ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="benefits"><?php esc_html_e( 'Benefits', 'b2bora-partner-club' ); ?></label></th>
				<td><textarea id="benefits" name="benefits" class="large-text" rows="3"><?php echo esc_textarea( $editing['benefits'] ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="sort_order"><?php esc_html_e( 'Sort Order', 'b2bora-partner-club' ); ?></label></th>
				<td><input type="number" id="sort_order" name="sort_order" step="1" value="<?php echo esc_attr( $editing['sort_order'] ?? 0 ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Active', 'b2bora-partner-club' ); ?></th>
				<td><label><input type="checkbox" name="active" value="1" <?php checked( ! isset( $editing ) || ! empty( $editing['active'] ) ); ?> /> <?php esc_html_e( 'Level is in use', 'b2bora-partner-club' ); ?></label></td>
			</tr>
		</table>
		<?php submit_button( $editing ? __( 'Update Level', 'b2bora-partner-club' ) : __( 'Add Level', 'b2bora-partner-club' ) ); ?>
	</form>

	<h2><?php esc_html_e( 'All Levels', 'b2bora-partner-club' ); ?></h2>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Minimum Points', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Active', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'b2bora-partner-club' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $levels ) ) : ?>
			<tr><td colspan="4"><?php esc_html_e( 'No levels configured yet.', 'b2bora-partner-club' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $levels as $level ) : ?>
				<tr>
					<td><?php echo esc_html( $level['name'] ); ?></td>
					<td><?php echo esc_html( number_format_i18n( (int) $level['minimum_points'] ) ); ?></td>
					<td><?php echo $level['active'] ? esc_html__( 'Yes', 'b2bora-partner-club' ) : esc_html__( 'No', 'b2bora-partner-club' ); ?></td>
					<td>
						<a href="<?php echo esc_url( add_query_arg( 'edit', $level['id'], $base_url ) ); ?>"><?php esc_html_e( 'Edit', 'b2bora-partner-club' ); ?></a>
						&nbsp;|&nbsp;
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="b2bora-pc-inline-form" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this level permanently?', 'b2bora-partner-club' ) ); ?>');">
							<?php wp_nonce_field( B2Bora_PC_Security::NONCE_ADMIN_ACTION . '_level' ); ?>
							<input type="hidden" name="action" value="b2bora_pc_delete_level" />
							<input type="hidden" name="id" value="<?php echo esc_attr( $level['id'] ); ?>" />
							<button type="submit" class="button-link button-link-delete"><?php esc_html_e( 'Delete', 'b2bora-partner-club' ); ?></button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
