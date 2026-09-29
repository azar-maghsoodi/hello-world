<?php
/**
 * Multi-language content storage for admin-entered fields (reward and
 * mission names/descriptions, level names/benefits) that have no
 * language dimension of their own in the schema - unlike the fixed
 * dashboard labels in B2Bora_PC_Translations, these are free-form content
 * an admin types per row.
 *
 * Stores a small JSON map of language => text inside the SAME existing
 * text/varchar column, with no database migration: a value with only one
 * language is stored as a plain string exactly as before (fully
 * backward compatible with every row saved before this existed), and a
 * value with two or more languages is stored as JSON. Reading either
 * shape back is transparent to callers via decode().
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2Bora_PC_Multilang
 */
class B2Bora_PC_Multilang {

	/**
	 * Encode a set of per-language values for storage. Empty languages
	 * are dropped. A single remaining language is stored as a plain
	 * string (no JSON overhead, and identical to how the field was
	 * stored before multi-language support existed); two or more are
	 * stored as a JSON object.
	 *
	 * @param array<string, string> $per_language Language code => text.
	 *
	 * @return string
	 */
	public static function encode( array $per_language ) {
		$clean = array();

		foreach ( $per_language as $lang => $text ) {
			$lang = sanitize_key( $lang );
			$text = trim( (string) $text );

			if ( '' === $lang || '' === $text ) {
				continue;
			}

			$clean[ $lang ] = $text;
		}

		if ( empty( $clean ) ) {
			return '';
		}

		if ( 1 === count( $clean ) ) {
			return (string) reset( $clean );
		}

		return (string) wp_json_encode( $clean );
	}

	/**
	 * Resolve a stored value to plain text for one language: a saved
	 * translation for that language if present, otherwise the first
	 * available language rather than showing nothing. A plain (legacy,
	 * single-language) string is returned as-is for every language.
	 *
	 * @param string      $raw      Stored column value.
	 * @param string|null $language Language code; defaults to the current one.
	 *
	 * @return string
	 */
	public static function decode( $raw, $language = null ) {
		$raw = (string) $raw;
		if ( '' === $raw ) {
			return '';
		}

		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) || empty( $decoded ) ) {
			return $raw;
		}

		$language = $language ? sanitize_key( $language ) : self::current_language();

		if ( isset( $decoded[ $language ] ) && '' !== $decoded[ $language ] ) {
			return (string) $decoded[ $language ];
		}

		return (string) reset( $decoded );
	}

	/**
	 * Every stored language => text pair for a value, for pre-filling an
	 * admin edit form. A plain (legacy) string is wrapped under the
	 * current language's key, so editing it and adding another language
	 * doesn't lose the original text.
	 *
	 * @param string $raw Stored column value.
	 *
	 * @return array<string, string>
	 */
	public static function get_all( $raw ) {
		$raw = (string) $raw;
		if ( '' === $raw ) {
			return array();
		}

		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) && ! empty( $decoded ) ) {
			return $decoded;
		}

		return array( self::current_language() => $raw );
	}

	/**
	 * Sanitise and encode a field that may arrive from a form either as a
	 * flat string (a programmatic caller, or a single-language site) or
	 * as a per-language array (name[en], name[ro], ... from the admin
	 * edit forms). Used by the Rewards/Levels/Missions save methods so
	 * each one doesn't repeat this branch.
	 *
	 * @param mixed    $raw       Raw input value.
	 * @param callable $sanitizer Sanitisation function applied to each string (e.g. 'sanitize_text_field').
	 *
	 * @return string
	 */
	public static function sanitize_input( $raw, callable $sanitizer ) {
		if ( is_array( $raw ) ) {
			$per_language = array();
			foreach ( $raw as $lang => $text ) {
				$per_language[ $lang ] = call_user_func( $sanitizer, $text );
			}

			return self::encode( $per_language );
		}

		return call_user_func( $sanitizer, (string) $raw );
	}

	/**
	 * Languages an admin edit form should show one input per: every
	 * active Polylang language, or just a single default language when
	 * Polylang isn't active (in which case multi-language storage is
	 * never engaged - the field just behaves as a plain string, exactly
	 * as before).
	 *
	 * @return string[]
	 */
	public static function get_admin_languages() {
		$languages = class_exists( 'B2Bora_PC_Translations' ) ? B2Bora_PC_Translations::get_available_languages() : array();

		return empty( $languages ) ? array( 'en' ) : $languages;
	}

	/**
	 * The language to decode with when the caller doesn't specify one.
	 * Reuses B2Bora_PC_Translations' current-language logic (Polylang if
	 * active, 'en' otherwise) so both classes agree on "current language".
	 *
	 * @return string
	 */
	private static function current_language() {
		return class_exists( 'B2Bora_PC_Translations' ) ? B2Bora_PC_Translations::current_language() : 'en';
	}
}
