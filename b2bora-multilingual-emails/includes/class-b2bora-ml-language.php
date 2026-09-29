<?php
/**
 * Language resolution: which language (en / ro) does a user / order / request belong to?
 *
 * Pure lookups. Nothing here writes to the database and nothing runs on
 * normal page loads; it is only called when an email, registration or order
 * is being processed.
 *
 * @package B2Bora_Multilingual_Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class B2Bora_ML_Language {

	/** Meta key used on both users and orders. */
	public const META_KEY = '_b2bora_language';

	/** Final fallback. */
	public const FALLBACK = 'en';

	/**
	 * True while we read the user's real "locale" meta, so our own
	 * get_user_metadata filter (see B2Bora_ML_Locale) does not recurse.
	 *
	 * @var bool
	 */
	public static bool $reading_raw = false;

	/** @var array<string,string>|null slug => WordPress locale, from Polylang. */
	private static ?array $polylang_map = null;

	/**
	 * Languages this plugin handles. Filterable for a future third language.
	 *
	 * @return string[]
	 */
	public static function supported(): array {
		$languages = apply_filters( 'b2bora_ml_supported_languages', array( 'en', 'ro' ) );

		if ( ! is_array( $languages ) || empty( $languages ) ) {
			return array( 'en', 'ro' );
		}

		return array_values( array_filter( array_map( 'strval', $languages ) ) );
	}

	/**
	 * Normalise "ro", "RO", "ro_RO", "ro-RO" to a supported slug, or ''.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function normalize( $value ): string {
		if ( ! is_string( $value ) || '' === $value ) {
			return '';
		}

		$parts = preg_split( '/[_-]/', strtolower( trim( $value ) ) );
		$slug  = is_array( $parts ) ? (string) $parts[0] : '';

		return in_array( $slug, self::supported(), true ) ? $slug : '';
	}

	/**
	 * Polylang's slug => locale map, via its public API only.
	 * Empty when Polylang is inactive or not ready yet (then not cached).
	 *
	 * @return array<string,string>
	 */
	private static function map(): array {
		if ( null !== self::$polylang_map ) {
			return self::$polylang_map;
		}

		$map = array();

		if ( function_exists( 'pll_languages_list' ) ) {
			$slugs   = pll_languages_list( array( 'fields' => 'slug' ) );
			$locales = pll_languages_list( array( 'fields' => 'locale' ) );

			if ( is_array( $slugs ) && is_array( $locales ) && count( $slugs ) === count( $locales ) ) {
				$map = array_combine( $slugs, $locales );
			}
		}

		if ( ! empty( $map ) ) {
			self::$polylang_map = $map;
		}

		return $map;
	}

	/**
	 * WordPress locale for a language slug. Uses the locale configured in
	 * Polylang -> Languages (authoritative), with a safe static fallback.
	 *
	 * @param string $language en|ro.
	 * @return string e.g. "ro_RO".
	 */
	public static function locale_for( string $language ): string {
		$language = self::normalize( $language );
		$language = '' === $language ? self::FALLBACK : $language;

		$static = array(
			'en' => 'en_US',
			'ro' => 'ro_RO',
		);
		$map    = self::map();
		$locale = $map[ $language ] ?? ( $static[ $language ] ?? '' );

		if ( '' === $locale ) {
			$locale = get_locale();
		}

		return (string) apply_filters( 'b2bora_ml_locale', $locale, $language );
	}

	/**
	 * Language slug for a WordPress locale ("ro_RO" -> "ro"), or ''.
	 *
	 * @param string $locale Locale.
	 * @return string
	 */
	public static function language_from_locale( string $locale ): string {
		if ( '' === $locale ) {
			return '';
		}

		$slug = array_search( $locale, self::map(), true );

		return false !== $slug ? self::normalize( (string) $slug ) : self::normalize( $locale );
	}

	/**
	 * Polylang's current front-end language, or ''.
	 *
	 * @return string
	 */
	public static function polylang_current(): string {
		if ( ! function_exists( 'pll_current_language' ) ) {
			return '';
		}

		return self::normalize( (string) pll_current_language() );
	}

	/**
	 * Language of the page the request came from (for AJAX requests, whose
	 * own URL carries no language segment). Uses Polylang's links model.
	 *
	 * @return string
	 */
	private static function language_from_referer(): string {
		$referer = wp_get_referer();

		if ( ! $referer || ! function_exists( 'PLL' ) ) {
			return '';
		}

		$pll = PLL();

		if ( ! is_object( $pll ) || ! isset( $pll->links_model ) || ! method_exists( $pll->links_model, 'get_language_from_url' ) ) {
			return '';
		}

		$language = $pll->links_model->get_language_from_url( $referer );

		return self::normalize( is_object( $language ) ? (string) $language->slug : (string) $language );
	}

	/**
	 * True while the B2B Cart to Order plugin's own AJAX endpoint runs.
	 *
	 * @return bool
	 */
	private static function is_b2b_ajax(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing check.
		return isset( $_GET['wc-ajax'] ) && 'b2b_send_cart_order' === sanitize_key( wp_unslash( $_GET['wc-ajax'] ) );
	}

	/**
	 * The language the visitor is on right now.
	 *
	 * - B2B order AJAX: the 'lang' POST field that plugin sends (its endpoint
	 *   URL has no language segment, so Polylang alone would say the default).
	 * - Other AJAX: language of the referring page.
	 * - Front end: pll_current_language().
	 * - wp-admin: '' (the admin's language says nothing about the customer).
	 *
	 * @return string en|ro or ''.
	 */
	public static function current_request_language(): string {
		if ( self::is_b2b_ajax() && isset( $_POST['lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- whitelisted to supported slugs; the B2B handler verifies its own nonce before creating an order.
			$posted = self::normalize( sanitize_key( wp_unslash( $_POST['lang'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( '' !== $posted ) {
				return $posted;
			}
		}

		$is_ajax = wp_doing_ajax() || ! empty( $_GET['wc-ajax'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( is_admin() && ! $is_ajax ) {
			return '';
		}

		if ( $is_ajax ) {
			$from_referer = self::language_from_referer();

			if ( '' !== $from_referer ) {
				return $from_referer;
			}
		}

		return self::polylang_current();
	}

	/**
	 * The user's own WordPress "locale" preference mapped to a language, or ''.
	 * Read raw (bypassing our virtual-locale filter).
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	public static function explicit_user_locale( int $user_id ): string {
		self::$reading_raw = true;
		$locale            = get_user_meta( $user_id, 'locale', true );
		self::$reading_raw = false;

		return is_string( $locale ) ? $locale : '';
	}

	/**
	 * Language for a user.
	 *
	 * 1. Saved _b2bora_language.
	 * 2. The user's own WordPress/Polylang locale preference.
	 * 3. The current visitor language - only when that visitor plausibly IS this
	 *    user (same logged-in user, no user, or $allow_request for flows the
	 *    user starts themselves, e.g. lost-password).
	 * 4. English.
	 *
	 * Never writes anything.
	 *
	 * @param int  $user_id       User ID (0 = unknown).
	 * @param bool $allow_request Allow the request language as a fallback.
	 * @return string en|ro
	 */
	public static function user_language( int $user_id, bool $allow_request = false ): string {
		if ( $user_id > 0 ) {
			$saved = self::normalize( get_user_meta( $user_id, self::META_KEY, true ) );

			if ( '' !== $saved ) {
				return $saved;
			}

			$from_locale = self::language_from_locale( self::explicit_user_locale( $user_id ) );

			if ( '' !== $from_locale ) {
				return $from_locale;
			}
		}

		if ( $allow_request || 0 === $user_id || get_current_user_id() === $user_id ) {
			$current = self::current_request_language();

			if ( '' !== $current ) {
				return $current;
			}
		}

		return self::FALLBACK;
	}

	/**
	 * Language for an order.
	 *
	 * 1. The order's own _b2bora_language (frozen at creation).
	 * 2. The customer's language (account, else account matching billing email).
	 * 3. English.
	 *
	 * @param WC_Order|int $order            Order or ID.
	 * @param int          $fallback_user_id Optional user to use if the order has no customer.
	 * @return string en|ro
	 */
	public static function order_language( $order, int $fallback_user_id = 0 ): string {
		if ( ! $order instanceof WC_Order ) {
			$order = function_exists( 'wc_get_order' ) ? wc_get_order( absint( $order ) ) : false;
		}

		if ( ! $order instanceof WC_Order ) {
			return $fallback_user_id > 0 ? self::user_language( $fallback_user_id ) : self::FALLBACK;
		}

		$saved = self::normalize( $order->get_meta( self::META_KEY, true ) );

		if ( '' !== $saved ) {
			return $saved;
		}

		$user_id = (int) $order->get_customer_id();

		if ( 0 === $user_id ) {
			$user_id = $fallback_user_id;
		}

		if ( 0 === $user_id ) {
			$billing_email = $order->get_billing_email();

			if ( $billing_email && is_email( $billing_email ) ) {
				$user = get_user_by( 'email', $billing_email );
				$user_id = $user ? (int) $user->ID : 0;
			}
		}

		return $user_id > 0 ? self::user_language( $user_id ) : self::FALLBACK;
	}

	/**
	 * Language for an email: order first, then user, then the request, then English.
	 *
	 * @param int $user_id  User ID or 0.
	 * @param int $order_id Order ID or 0.
	 * @return string en|ro
	 */
	public static function email_language( int $user_id = 0, int $order_id = 0 ): string {
		if ( $order_id > 0 ) {
			return self::order_language( $order_id, $user_id );
		}

		return self::user_language( $user_id, true );
	}
}
