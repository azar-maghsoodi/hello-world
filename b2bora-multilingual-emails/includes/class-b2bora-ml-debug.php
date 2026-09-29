<?php
/**
 * Optional debug logging + a small Settings page.
 *
 * Off by default. Enable with either:
 *   define( 'B2BORA_ML_EMAIL_DEBUG', true );   // wp-config.php
 * or Settings -> B2Bora Multilingual Emails.
 * Output goes to the PHP error log (wp-content/debug.log when WP_DEBUG_LOG is on).
 *
 * Only whitelisted, non-sensitive fields are ever logged: never passwords,
 * reset keys/tokens, addresses, or payment data.
 *
 * @package B2Bora_Multilingual_Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class B2Bora_ML_Debug {

	private const OPTION = 'b2bora_ml_debug';

	/** Fields allowed in a log line. */
	private const ALLOWED = array( 'event', 'email', 'hook', 'user_id', 'user_lang', 'order_id', 'order_lang', 'pll_lang', 'lang', 'locale', 'switched', 'restored' );

	public static function init(): void {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
			add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
		}
	}

	public static function enabled(): bool {
		if ( defined( 'B2BORA_ML_EMAIL_DEBUG' ) ) {
			return (bool) B2BORA_ML_EMAIL_DEBUG;
		}

		return '1' === (string) get_option( self::OPTION, '0' );
	}

	/**
	 * Write one debug line (no-op unless enabled).
	 *
	 * @param array<string,scalar> $data Fields; anything not whitelisted is dropped.
	 */
	public static function log( array $data ): void {
		if ( ! self::enabled() ) {
			return;
		}

		$clean = array();

		foreach ( self::ALLOWED as $key ) {
			if ( isset( $data[ $key ] ) && is_scalar( $data[ $key ] ) ) {
				$clean[ $key ] = is_bool( $data[ $key ] ) ? $data[ $key ] : sanitize_text_field( (string) $data[ $key ] );
			}
		}

		$clean['pll_lang'] = $clean['pll_lang'] ?? ( function_exists( 'pll_current_language' ) ? (string) pll_current_language() : 'n/a' );

		error_log( 'B2Bora ML: ' . wp_json_encode( $clean ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- opt-in debug output.
	}

	/* ------------------------------------------------------------------ *
	 * Settings page
	 * ------------------------------------------------------------------ */

	public static function add_page(): void {
		add_options_page(
			__( 'B2Bora Multilingual Emails', 'b2bora-multilingual-emails' ),
			__( 'B2Bora Multilingual Emails', 'b2bora-multilingual-emails' ),
			'manage_options',
			'b2bora-ml-emails',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register_setting(): void {
		register_setting(
			'b2bora_ml',
			self::OPTION,
			array(
				'type'              => 'string',
				'default'           => '0',
				'sanitize_callback' => static function ( $value ) {
					return '1' === (string) $value ? '1' : '0';
				},
			)
		);
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$locales   = array();
		$installed = function_exists( 'get_available_languages' ) ? get_available_languages() : array();

		foreach ( B2Bora_ML_Language::supported() as $language ) {
			$locale      = B2Bora_ML_Language::locale_for( $language );
			$wc_mo       = WP_LANG_DIR . '/plugins/woocommerce-' . $locale . '.mo';
			$locales[]   = array(
				'language'  => $language,
				'locale'    => $locale,
				'core_pack' => 'en_US' === $locale || in_array( $locale, $installed, true ),
				'wc_pack'   => 'en_US' === $locale || file_exists( $wc_mo ),
			);
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'B2Bora Multilingual Emails', 'b2bora-multilingual-emails' ); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'b2bora_ml' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Debug logging', 'b2bora-multilingual-emails' ); ?></th>
						<td>
							<?php if ( defined( 'B2BORA_ML_EMAIL_DEBUG' ) ) : ?>
								<p><?php esc_html_e( 'Controlled by the B2BORA_ML_EMAIL_DEBUG constant in wp-config.php.', 'b2bora-multilingual-emails' ); ?></p>
							<?php else : ?>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>" value="1" <?php checked( get_option( self::OPTION, '0' ), '1' ); ?> />
									<?php esc_html_e( 'Log the language / locale chosen for each email to the PHP error log (no passwords, tokens or addresses).', 'b2bora-multilingual-emails' ); ?>
								</label>
							<?php endif; ?>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2><?php esc_html_e( 'Language pack status', 'b2bora-multilingual-emails' ); ?></h2>
			<p><?php esc_html_e( 'Translations only appear in an email if the language pack for that locale is installed.', 'b2bora-multilingual-emails' ); ?></p>
			<table class="widefat striped" style="max-width:640px">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Language', 'b2bora-multilingual-emails' ); ?></th>
						<th><?php esc_html_e( 'Locale', 'b2bora-multilingual-emails' ); ?></th>
						<th><?php esc_html_e( 'WordPress pack', 'b2bora-multilingual-emails' ); ?></th>
						<th><?php esc_html_e( 'WooCommerce pack', 'b2bora-multilingual-emails' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $locales as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row['language'] ); ?></td>
						<td><?php echo esc_html( $row['locale'] ); ?></td>
						<td><?php echo $row['core_pack'] ? '&#10003;' : '&#10007;'; ?></td>
						<td><?php echo $row['wc_pack'] ? '&#10003;' : '&#10007;'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p>
				<?php
				echo esc_html(
					function_exists( 'pll_languages_list' )
						? __( 'Polylang detected.', 'b2bora-multilingual-emails' )
						: __( 'Polylang not detected: everything falls back to English.', 'b2bora-multilingual-emails' )
				);
				?>
			</p>
		</div>
		<?php
	}
}
