<?php
/**
 * Admin: Dashboard translations, per Polylang language.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$languages     = B2Bora_PC_Translations::get_available_languages();
$has_polylang  = ! empty( $languages );
$base_url      = admin_url( 'admin.php?page=b2bora-pc-translations' );

if ( ! $has_polylang ) {
	$languages = array( 'en' );
}

$requested_lang = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$current_lang   = in_array( $requested_lang, $languages, true ) ? $requested_lang : $languages[0];

$definitions = B2Bora_PC_Translations::get_string_definitions();
$values      = B2Bora_PC_Translations::get_all_for_language( $current_lang );
?>
<div class="wrap b2bora-pc-admin">
	<h1><?php esc_html_e( 'Dashboard Translations', 'b2bora-partner-club' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Every label shown on the [b2bora_partner_club] customer dashboard, per language. The same shortcode is used everywhere - it automatically shows the text saved here for whichever language the page is in. Leave a field blank to use the built-in English default for it.', 'b2bora-partner-club' ); ?>
	</p>

	<?php if ( ! $has_polylang ) : ?>
		<div class="notice notice-warning inline"><p>
			<?php esc_html_e( 'Polylang was not detected, so the dashboard will always show the English (default) text below regardless of what you save here - there is no per-page language to switch on. Activate Polylang to enable real per-language switching.', 'b2bora-partner-club' ); ?>
		</p></div>
	<?php endif; ?>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Translations saved.', 'b2bora-partner-club' ); ?></p></div>
	<?php endif; ?>

	<?php if ( $has_polylang && count( $languages ) > 1 ) : ?>
		<h2 class="nav-tab-wrapper">
			<?php foreach ( $languages as $lang ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'lang', $lang, $base_url ) ); ?>" class="nav-tab <?php echo $lang === $current_lang ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( strtoupper( $lang ) ); ?></a>
			<?php endforeach; ?>
		</h2>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="b2bora-pc-admin-form">
		<?php wp_nonce_field( B2Bora_PC_Security::NONCE_ADMIN_ACTION . '_translations' ); ?>
		<input type="hidden" name="action" value="b2bora_pc_save_translations" />
		<input type="hidden" name="lang" value="<?php echo esc_attr( $current_lang ); ?>" />

		<table class="form-table">
			<?php foreach ( $definitions as $key => $definition ) : ?>
				<tr>
					<th><label for="str-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $definition['label'] ); ?></label></th>
					<td>
						<input
							type="text"
							id="str-<?php echo esc_attr( $key ); ?>"
							name="strings[<?php echo esc_attr( $key ); ?>]"
							class="large-text"
							value="<?php echo esc_attr( $values[ $key ] ); ?>"
							placeholder="<?php echo esc_attr( $definition['default'] ); ?>"
						/>
						<?php if ( ! empty( $definition['hint'] ) ) : ?>
							<p class="description"><?php echo esc_html( $definition['hint'] ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>

		<?php submit_button( sprintf( /* translators: %s: language code */ __( 'Save %s Translations', 'b2bora-partner-club' ), strtoupper( $current_lang ) ) ); ?>
	</form>
</div>
