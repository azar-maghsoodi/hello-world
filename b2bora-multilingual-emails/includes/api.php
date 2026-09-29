<?php
/**
 * Public API. Safe to call from any other B2Bora plugin (e.g. Partner Club).
 *
 * @package B2Bora_Multilingual_Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'b2bora_get_user_language' ) ) {
	/**
	 * Language of a user: saved _b2bora_language -> user's own locale ->
	 * current visitor (only if it is this user / unknown) -> 'en'.
	 * Read-only; never writes.
	 *
	 * @param int $user_id User ID (0 = current visitor).
	 * @return string 'en' or 'ro'.
	 */
	function b2bora_get_user_language( $user_id = 0 ) {
		return B2Bora_ML_Language::user_language( absint( $user_id ) );
	}
}

if ( ! function_exists( 'b2bora_get_order_language' ) ) {
	/**
	 * Language of an order: its own _b2bora_language -> customer's language -> 'en'.
	 *
	 * @param WC_Order|int $order Order or order ID.
	 * @return string 'en' or 'ro'.
	 */
	function b2bora_get_order_language( $order ) {
		return B2Bora_ML_Language::order_language( $order );
	}
}

if ( ! function_exists( 'b2bora_get_email_language' ) ) {
	/**
	 * The language an email about this order / user should use.
	 * Order wins over user (an order stays in the language it was placed in).
	 *
	 * @param int $user_id  User ID or 0.
	 * @param int $order_id Order ID or 0.
	 * @return string 'en' or 'ro'.
	 */
	function b2bora_get_email_language( $user_id = 0, $order_id = 0 ) {
		return B2Bora_ML_Language::email_language( absint( $user_id ), absint( $order_id ) );
	}
}

if ( ! function_exists( 'b2bora_switch_email_locale' ) ) {
	/**
	 * Temporarily switch the WordPress locale to a language ('en' / 'ro').
	 * ALWAYS pair with b2bora_restore_email_locale() using the returned token:
	 *
	 *     $token = b2bora_switch_email_locale( b2bora_get_email_language( $user_id ) );
	 *     // ... build and send the email ...
	 *     b2bora_restore_email_locale( $token );
	 *
	 * Nesting is safe.
	 *
	 * @param string $language 'en' or 'ro'.
	 * @return array Opaque token for b2bora_restore_email_locale().
	 */
	function b2bora_switch_email_locale( $language ) {
		return B2Bora_ML_Locale::switch_to( (string) $language );
	}
}

if ( ! function_exists( 'b2bora_restore_email_locale' ) ) {
	/**
	 * Undo b2bora_switch_email_locale().
	 *
	 * @param array $token Token returned by b2bora_switch_email_locale().
	 */
	function b2bora_restore_email_locale( $token ) {
		B2Bora_ML_Locale::restore( $token );
	}
}

if ( ! function_exists( 'b2bora_translate_string' ) ) {
	/**
	 * Translate a B2Bora string using Polylang String Translations.
	 * Returns the original string if Polylang is inactive or there is no translation.
	 *
	 * @param string $string   English source string.
	 * @param string $language 'en' / 'ro' ('' = the language of the open email switch, else current).
	 * @return string
	 */
	function b2bora_translate_string( $string, $language = '' ) {
		$string   = (string) $string;
		$language = B2Bora_ML_Language::normalize( (string) $language );

		if ( '' === $language ) {
			$language = B2Bora_ML_Locale::active_language();
		}

		if ( '' === $language ) {
			$language = B2Bora_ML_Language::polylang_current();
		}

		if ( '' === $string || '' === $language || ! function_exists( 'pll_translate_string' ) ) {
			return $string;
		}

		$translated = pll_translate_string( $string, $language );

		return is_string( $translated ) && '' !== $translated ? $translated : $string;
	}
}
