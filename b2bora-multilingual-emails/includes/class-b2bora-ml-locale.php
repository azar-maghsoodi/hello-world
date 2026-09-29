<?php
/**
 * Locale switching helpers + the core-WordPress user-email bridge.
 *
 * @package B2Bora_Multilingual_Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class B2Bora_ML_Locale {

	/**
	 * Languages of currently open switches (innermost last). Used so that
	 * translatable admin-typed text can be looked up in the right language.
	 *
	 * @var string[]
	 */
	private static array $active = array();

	/** @var bool */
	private static bool $core_armed = false;

	public static function init(): void {
		/*
		 * WordPress core already wraps customer emails in
		 * switch_to_user_locale( $user_id ): lost-password, password-changed,
		 * email-changed, new-user. It reads the user's "locale" meta. We add
		 * a *virtual* value to that read (see filter_user_locale_meta) only
		 * for customers with no locale of their own, and only after one of
		 * these pass-through filters proves a core user email is being built.
		 * Nothing is stored, nothing runs on ordinary requests.
		 */
		foreach ( array( 'send_retrieve_password_email', 'send_password_change_email', 'send_email_change_email', 'wp_send_new_user_notification_to_user' ) as $hook ) {
			add_filter( $hook, array( __CLASS__, 'arm_core_bridge' ), 1 );
		}

		add_action( 'shutdown', array( __CLASS__, 'restore_all' ), 1 );
	}

	/**
	 * Pass-through filter callback: enable the virtual user locale.
	 *
	 * @param mixed $value Filter value, returned untouched.
	 * @return mixed
	 */
	public static function arm_core_bridge( $value ) {
		if ( ! self::$core_armed && ! ( defined( 'B2BORA_ML_DISABLE_CORE_BRIDGE' ) && B2BORA_ML_DISABLE_CORE_BRIDGE ) ) {
			self::$core_armed = true;
			add_filter( 'get_user_metadata', array( __CLASS__, 'filter_user_locale_meta' ), 10, 4 );
		}

		return $value;
	}

	/**
	 * Virtual "locale" user meta for customers without one.
	 *
	 * Never overrides an explicit locale, and never applies to staff
	 * (anyone who can edit posts or manage WooCommerce), so admin/staff
	 * emails keep the normal site/admin locale.
	 *
	 * @param mixed  $value     Short-circuit value (null = proceed).
	 * @param int    $object_id User ID.
	 * @param string $meta_key  Meta key.
	 * @param bool   $single    Single value requested.
	 * @return mixed
	 */
	public static function filter_user_locale_meta( $value, $object_id, $meta_key, $single ) {
		if ( 'locale' !== $meta_key || ! $single || null !== $value || B2Bora_ML_Language::$reading_raw ) {
			return $value;
		}

		$user_id = absint( $object_id );

		if ( 0 === $user_id || '' !== B2Bora_ML_Language::explicit_user_locale( $user_id ) ) {
			return $value;
		}

		if ( user_can( $user_id, 'edit_posts' ) || user_can( $user_id, 'manage_woocommerce' ) ) {
			return $value;
		}

		$language = B2Bora_ML_Language::user_language( $user_id, true );
		$locale   = B2Bora_ML_Language::locale_for( $language );

		B2Bora_ML_Debug::log(
			array(
				'event'     => 'core_user_email',
				'user_id'   => $user_id,
				'user_lang' => $language,
				'locale'    => $locale,
			)
		);

		return $locale;
	}

	/**
	 * Temporarily switch the WordPress locale to the given language.
	 *
	 * Returns a token that MUST be passed to restore(). Safe to nest: if the
	 * target locale is already active nothing is switched and restore() is a
	 * no-op, so an outer switch is never popped by mistake.
	 *
	 * @param string $language en|ro.
	 * @return array{switched:bool,language:string,locale:string}
	 */
	public static function switch_to( string $language ): array {
		$language = B2Bora_ML_Language::normalize( $language );
		$language = '' === $language ? B2Bora_ML_Language::FALLBACK : $language;
		$locale   = B2Bora_ML_Language::locale_for( $language );
		$switched = function_exists( 'switch_to_locale' ) ? (bool) switch_to_locale( $locale ) : false;

		self::$active[] = $language;

		return array(
			'switched' => $switched,
			'language' => $language,
			'locale'   => $locale,
		);
	}

	/**
	 * Undo a switch_to().
	 *
	 * @param array|null $token Token returned by switch_to().
	 */
	public static function restore( $token ): void {
		if ( ! is_array( $token ) || ! isset( $token['switched'] ) ) {
			return;
		}

		array_pop( self::$active );

		if ( $token['switched'] && function_exists( 'restore_previous_locale' ) ) {
			restore_previous_locale();
		}
	}

	/**
	 * Language of the innermost open switch, or ''.
	 *
	 * @return string
	 */
	public static function active_language(): string {
		return empty( self::$active ) ? '' : (string) end( self::$active );
	}

	/**
	 * Safety net: if anything left a switch open, close it at shutdown.
	 */
	public static function restore_all(): void {
		while ( ! empty( self::$active ) ) {
			array_pop( self::$active );

			if ( function_exists( 'restore_previous_locale' ) && function_exists( 'is_locale_switched' ) && is_locale_switched() ) {
				restore_previous_locale();
			}
		}
	}
}
