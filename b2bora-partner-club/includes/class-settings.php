<?php
/**
 * Central settings store for the Partner Club. All settings live in a
 * single option (b2bora_pc_settings) so activation/upgrades never have to
 * juggle dozens of autoloaded options.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2Bora_PC_Settings
 */
class B2Bora_PC_Settings {

	const OPTION_KEY = 'b2bora_pc_settings';

	/**
	 * Cached settings for the current request.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Default settings. Keep every value here so the plugin always has a
	 * sane configuration even before Settings has ever been saved.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			// Points earning. Expressed as a ratio of two integers -
			// "points_per_amount points per currency_amount_for_points
			// units of eligible currency" - rather than a single rate, so
			// a fractional rate (e.g. 1 point per 3 lei) can be expressed
			// exactly without ever using floating point. The default
			// 1-per-1 is the same as a flat "1 point per unit" rate.
			'points_per_amount'          => 1,
			'currency_amount_for_points' => 1,
			'exclude_tax'                 => 1,     // 1 = tax is not eligible for points.
			'exclude_shipping'            => 1,     // 1 = shipping is not eligible for points.
			'exclude_refunded'            => 1,     // 1 = refunded amount is removed from eligible total.

			// Bonuses.
			'welcome_bonus_enabled'       => 1,
			'welcome_bonus_points'        => 500,
			'reorder_bonus_enabled'       => 1,
			'reorder_bonus_points'        => 300,
			'reorder_window_days'         => 30,

			// Integration.
			// Verified against the installed B2B Cart to Order plugin
			// (v6.0.0, inspected directly): it has no "confirmation" hook
			// of its own — a WooCommerce order (when its "Create
			// WooCommerce Order" setting is on) is created already in
			// "Processing" status the moment the request is sent, and
			// nothing in that plugin ever moves it further. The only
			// reliable "genuinely confirmed" signal available today is
			// staff manually marking the order "Completed" in
			// WooCommerce -> Orders after reviewing/fulfilling it, so the
			// fallback is on by default. If a future version of B2B Cart
			// to Order (or a different plugin) adds its own confirmation
			// action, fire `b2bora_order_completed` from that instead and
			// turn this back off to avoid double integration paths.
			'enable_wc_completed_fallback' => 1,
			'enable_refund_reversal'       => 1,

			// Expiration.
			'points_expiration_enabled'   => 0,
			'points_expiration_days'      => 365,

			// Levels.
			'level_basis'                  => 'lifetime', // 'lifetime' (points ever earned) or 'balance' (current spendable balance).

			// Redemptions.
			'redemption_requires_approval' => 1, // Redemptions start as "pending" until an admin approves them.

			// Uninstall.
			'delete_data_on_uninstall'     => 0,
		);
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array
	 */
	public static function all() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$stored       = get_option( self::OPTION_KEY, array() );
		$stored       = is_array( $stored ) ? $stored : array();
		$stored       = self::migrate_legacy_rate( $stored );
		self::$cache  = wp_parse_args( $stored, self::defaults() );

		return self::$cache;
	}

	/**
	 * One-time, read-path migration for sites that saved settings before
	 * the points rate became a ratio: a stored 'points_per_currency_unit'
	 * (old single-integer rate) with no 'points_per_amount' yet is
	 * translated to the equivalent 1-unit-denominator ratio, so existing
	 * configuration (e.g. a site that set the old field to 400) is never
	 * silently reset to the default. Runs on every read but is a no-op
	 * once the new keys exist, which happens the first time Settings is
	 * saved (or immediately below, via this same translation).
	 *
	 * @param array $stored Raw stored settings.
	 *
	 * @return array
	 */
	private static function migrate_legacy_rate( array $stored ) {
		if ( array_key_exists( 'points_per_currency_unit', $stored ) && ! array_key_exists( 'points_per_amount', $stored ) ) {
			$stored['points_per_amount']          = absint( $stored['points_per_currency_unit'] );
			$stored['currency_amount_for_points']  = 1;
		}

		return $stored;
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback if not found (defaults() already covers known keys).
	 *
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$settings = self::all();

		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}

	/**
	 * Persist a full settings array (already sanitised by the caller).
	 *
	 * @param array $settings New settings.
	 */
	public static function update( array $settings ) {
		$merged = wp_parse_args( $settings, self::defaults() );
		update_option( self::OPTION_KEY, $merged );
		self::$cache = $merged;
	}

	/**
	 * Sanitise a raw $_POST-style array of settings against known keys and
	 * expected types. Unknown keys are dropped.
	 *
	 * @param array $input Raw input.
	 *
	 * @return array Sanitised settings, merged with defaults for missing keys.
	 */
	public static function sanitize( array $input ) {
		$defaults  = self::defaults();
		$sanitized = array();

		foreach ( $defaults as $key => $default_value ) {
			if ( ! array_key_exists( $key, $input ) ) {
				// Checkboxes are absent from $_POST when unchecked.
				$sanitized[ $key ] = in_array( $key, self::boolean_keys(), true ) ? 0 : $default_value;
				continue;
			}

			$raw = $input[ $key ];

			if ( in_array( $key, self::boolean_keys(), true ) ) {
				$sanitized[ $key ] = empty( $raw ) ? 0 : 1;
			} elseif ( 'level_basis' === $key ) {
				$sanitized[ $key ] = in_array( $raw, array( 'lifetime', 'balance' ), true ) ? $raw : 'lifetime';
			} elseif ( 'currency_amount_for_points' === $key ) {
				// The denominator of the ratio can never be zero.
				$sanitized[ $key ] = max( 1, absint( $raw ) );
			} elseif ( in_array( $key, array( 'points_per_amount', 'welcome_bonus_points', 'reorder_bonus_points', 'reorder_window_days', 'points_expiration_days' ), true ) ) {
				$sanitized[ $key ] = max( 0, absint( $raw ) );
			} else {
				$sanitized[ $key ] = sanitize_text_field( wp_unslash( $raw ) );
			}
		}

		return $sanitized;
	}

	/**
	 * Keys that represent boolean on/off settings (rendered as checkboxes).
	 *
	 * @return array
	 */
	public static function boolean_keys() {
		return array(
			'exclude_tax',
			'exclude_shipping',
			'exclude_refunded',
			'welcome_bonus_enabled',
			'reorder_bonus_enabled',
			'enable_wc_completed_fallback',
			'enable_refund_reversal',
			'points_expiration_enabled',
			'redemption_requires_approval',
			'delete_data_on_uninstall',
		);
	}
}
