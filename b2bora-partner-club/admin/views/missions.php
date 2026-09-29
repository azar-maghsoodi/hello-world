<?php
/**
 * Admin: Missions management.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edit_id  = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$editing  = $edit_id ? B2Bora_PC_Missions::get_mission( $edit_id ) : null;
$missions = B2Bora_PC_Missions::get_missions();
$types    = B2Bora_PC_Missions::get_types();
$base_url = admin_url( 'admin.php?page=b2bora-pc-missions' );
$languages    = B2Bora_PC_Multilang::get_admin_languages();
$name_values  = B2Bora_PC_Multilang::get_all( $editing['name'] ?? '' );
$desc_values  = B2Bora_PC_Multilang::get_all( $editing['description'] ?? '' );
?>
<div class="wrap b2bora-pc-admin">
	<h1><?php esc_html_e( 'Missions', 'b2bora-partner-club' ); ?></h1>
	<p class="description"><?php esc_html_e( 'A mission awards its bonus points to a customer once, the first time they meet its target. Automatic detection runs whenever an order is confirmed.', 'b2bora-partner-club' ); ?></p>

	<h2><?php echo $editing ? esc_html__( 'Edit Mission', 'b2bora-partner-club' ) : esc_html__( 'Add New Mission', 'b2bora-partner-club' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="b2bora-pc-admin-form">
		<?php wp_nonce_field( B2Bora_PC_Security::NONCE_ADMIN_ACTION . '_mission' ); ?>
		<input type="hidden" name="action" value="b2bora_pc_save_mission" />
		<?php if ( $editing ) : ?>
			<input type="hidden" name="id" value="<?php echo esc_attr( $editing['id'] ); ?>" />
		<?php endif; ?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Name', 'b2bora-partner-club' ); ?></th>
				<td>
					<?php foreach ( $languages as $lang ) : ?>
						<p>
							<label for="name-<?php echo esc_attr( $lang ); ?>"><?php echo count( $languages ) > 1 ? esc_html( strtoupper( $lang ) . ':' ) : ''; ?></label>
							<input type="text" id="name-<?php echo esc_attr( $lang ); ?>" name="name[<?php echo esc_attr( $lang ); ?>]" class="regular-text" <?php echo 1 === count( $languages ) ? 'required' : ''; ?> value="<?php echo esc_attr( $name_values[ $lang ] ?? '' ); ?>" />
						</p>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Description', 'b2bora-partner-club' ); ?></th>
				<td>
					<?php foreach ( $languages as $lang ) : ?>
						<p>
							<label for="description-<?php echo esc_attr( $lang ); ?>"><?php echo count( $languages ) > 1 ? esc_html( strtoupper( $lang ) . ':' ) : ''; ?></label><br />
							<textarea id="description-<?php echo esc_attr( $lang ); ?>" name="description[<?php echo esc_attr( $lang ); ?>]" class="large-text" rows="3"><?php echo esc_textarea( $desc_values[ $lang ] ?? '' ); ?></textarea>
						</p>
					<?php endforeach; ?>
					<?php if ( count( $languages ) > 1 ) : ?>
						<p class="description"><?php esc_html_e( 'At least one language is required; leave others blank to fall back to the first filled-in language.', 'b2bora-partner-club' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><label for="type"><?php esc_html_e( 'Type', 'b2bora-partner-club' ); ?></label></th>
				<td>
					<select id="type" name="type">
						<?php foreach ( $types as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $editing['type'] ?? '', $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="target"><?php esc_html_e( 'Target', 'b2bora-partner-club' ); ?></label></th>
				<td><input type="number" id="target" name="target" min="0" step="1" value="<?php echo esc_attr( $editing['target'] ?? 0 ); ?>" />
					<p class="description"><?php esc_html_e( 'Meaning depends on type: number of orders (order_count) or number of distinct brands (brand_count). Ignored for first_order/pallet_order.', 'b2bora-partner-club' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="bonus_points"><?php esc_html_e( 'Bonus Points', 'b2bora-partner-club' ); ?></label></th>
				<td><input type="number" id="bonus_points" name="bonus_points" min="0" step="1" required value="<?php echo esc_attr( $editing['bonus_points'] ?? 0 ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="start_date"><?php esc_html_e( 'Start Date', 'b2bora-partner-club' ); ?></label></th>
				<td><input type="date" id="start_date" name="start_date" value="<?php echo esc_attr( $editing['start_date'] ?? '' ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="end_date"><?php esc_html_e( 'End Date', 'b2bora-partner-club' ); ?></label></th>
				<td><input type="date" id="end_date" name="end_date" value="<?php echo esc_attr( $editing['end_date'] ?? '' ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Active', 'b2bora-partner-club' ); ?></th>
				<td><label><input type="checkbox" name="active" value="1" <?php checked( ! isset( $editing ) || ! empty( $editing['active'] ) ); ?> /> <?php esc_html_e( 'Mission is live', 'b2bora-partner-club' ); ?></label></td>
			</tr>
		</table>
		<?php submit_button( $editing ? __( 'Update Mission', 'b2bora-partner-club' ) : __( 'Add Mission', 'b2bora-partner-club' ) ); ?>
	</form>

	<h2><?php esc_html_e( 'All Missions', 'b2bora-partner-club' ); ?></h2>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Type', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Target', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Bonus', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Active', 'b2bora-partner-club' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'b2bora-partner-club' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $missions ) ) : ?>
			<tr><td colspan="6"><?php esc_html_e( 'No missions yet.', 'b2bora-partner-club' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $missions as $mission ) : ?>
				<tr>
					<td><?php echo esc_html( B2Bora_PC_Multilang::decode( $mission['name'] ) ); ?></td>
					<td><?php echo esc_html( $types[ $mission['type'] ] ?? $mission['type'] ); ?></td>
					<td><?php echo esc_html( number_format_i18n( (int) $mission['target'] ) ); ?></td>
					<td><?php echo esc_html( number_format_i18n( (int) $mission['bonus_points'] ) ); ?></td>
					<td><?php echo $mission['active'] ? esc_html__( 'Yes', 'b2bora-partner-club' ) : esc_html__( 'No', 'b2bora-partner-club' ); ?></td>
					<td>
						<a href="<?php echo esc_url( add_query_arg( 'edit', $mission['id'], $base_url ) ); ?>"><?php esc_html_e( 'Edit', 'b2bora-partner-club' ); ?></a>
						&nbsp;|&nbsp;
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="b2bora-pc-inline-form" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this mission permanently?', 'b2bora-partner-club' ) ); ?>');">
							<?php wp_nonce_field( B2Bora_PC_Security::NONCE_ADMIN_ACTION . '_mission' ); ?>
							<input type="hidden" name="action" value="b2bora_pc_delete_mission" />
							<input type="hidden" name="id" value="<?php echo esc_attr( $mission['id'] ); ?>" />
							<button type="submit" class="button-link button-link-delete"><?php esc_html_e( 'Delete', 'b2bora-partner-club' ); ?></button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
