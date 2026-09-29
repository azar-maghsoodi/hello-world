<?php
/**
 * Puts WooCommerce's EXISTING customer emails into the customer's language.
 *
 * This class never sends an email. It only decides which locale WooCommerce
 * should use while it builds an email it was already going to send, so a
 * duplicate is impossible by construction.
 *
 * How it works (all verified against WooCommerce's own source):
 *
 *  1. Every transactional email is triggered from an action named
 *     "<action>_notification" (list: the 'woocommerce_email_actions' filter),
 *     plus "woocommerce_reset_password_notification" and the admin "resend
 *     order email" path. At priority 1 we note which order / user the event
 *     is about (the "context"); at priority 9999 we drop it again.
 *
 *  2. Each WC_Email::trigger() starts with setup_locale() and ends with
 *     restore_locale(). Those call the filters
 *     'woocommerce_allow_switching_email_locale' and
 *     'woocommerce_allow_restoring_email_locale' (WooCommerce 6.8+). For
 *     CUSTOMER emails only, we perform our own switch_to_locale() there and
 *     return false so WooCommerce does not also switch (its restore would
 *     otherwise pop OUR switch off the locale stack).
 *
 *  3. Text an admin typed into WooCommerce -> Settings -> Emails (subject,
 *     heading, additional content, footer) is stored in a single language.
 *     While an email is being built those values are looked up in Polylang
 *     String Translations. Nothing typed = untouched (WooCommerce's own
 *     translated defaults are used).
 *
 * @package B2Bora_Multilingual_Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class B2Bora_ML_Emails {

	/** WooCommerce email settings that hold admin-typed text. */
	private const TEXT_KEYS = array(
		'subject',
		'heading',
		'additional_content',
		'subject_paid',
		'heading_paid',
		'subject_full',
		'heading_full',
		'subject_partial',
		'heading_partial',
	);

	/**
	 * Stack of event contexts: array{key:string,user:int,order:int,lang:string|null}.
	 *
	 * @var array<int,array>
	 */
	private static array $contexts = array();

	/**
	 * Open switches: array{object:int,token:array,depth:int}.
	 *
	 * @var array<int,array>
	 */
	private static array $open = array();

	public static function init(): void {
		add_filter( 'woocommerce_email_actions', array( __CLASS__, 'wrap_email_actions' ), 9999 );

		add_action( 'woocommerce_reset_password_notification', array( __CLASS__, 'open_reset_context' ), 1, 1 );
		add_action( 'woocommerce_reset_password_notification', array( __CLASS__, 'close_reset_context' ), 9999 );

		add_action( 'woocommerce_before_resend_order_emails', array( __CLASS__, 'open_resend_context' ), 1, 1 );
		add_action( 'woocommerce_after_resend_order_email', array( __CLASS__, 'close_resend_context' ), 9999 );

		add_filter( 'woocommerce_allow_switching_email_locale', array( __CLASS__, 'maybe_switch' ), 10, 2 );
		add_filter( 'woocommerce_allow_restoring_email_locale', array( __CLASS__, 'maybe_restore' ), 10, 2 );

		add_filter( 'woocommerce_email_get_option', array( __CLASS__, 'translate_option' ), 10, 4 );
		add_filter( 'woocommerce_email_footer_text', array( __CLASS__, 'translate_footer' ), 5 );

		if ( is_admin() ) {
			add_action( 'init', array( __CLASS__, 'register_polylang_strings' ), 30 );
		}
	}

	/* ------------------------------------------------------------------ *
	 * Event contexts
	 * ------------------------------------------------------------------ */

	/**
	 * Hook the context markers onto every "<action>_notification" hook
	 * WooCommerce (and any add-on) declares. The list is returned unchanged.
	 *
	 * @param mixed $actions Email action names.
	 * @return mixed
	 */
	public static function wrap_email_actions( $actions ) {
		if ( is_array( $actions ) ) {
			foreach ( $actions as $action ) {
				if ( is_string( $action ) && '' !== $action ) {
					add_action( $action . '_notification', array( __CLASS__, 'open_context' ), 1, 10 );
					add_action( $action . '_notification', array( __CLASS__, 'close_context' ), 9999, 0 );
				}
			}
		}

		return $actions;
	}

	/** Priority-1 marker for "<action>_notification". */
	public static function open_context( ...$args ): void {
		$hook    = current_filter();
		$context = array(
			'key'   => $hook,
			'user'  => 0,
			'order' => 0,
			'lang'  => null,
		);
		$first   = $args[0] ?? null;

		if ( 'woocommerce_created_customer_notification' === $hook ) {
			$context['user'] = absint( $first );
		} elseif ( 'woocommerce_new_customer_note_notification' === $hook ) {
			$context['order'] = is_array( $first ) ? absint( $first['order_id'] ?? 0 ) : 0;
		} elseif ( is_numeric( $first ) && false !== strpos( $hook, 'order' ) ) {
			$context['order'] = absint( $first );
		}

		self::$contexts[] = $context;
	}

	/** Priority-9999 marker for "<action>_notification". */
	public static function close_context(): void {
		self::close( current_filter() );
	}

	/**
	 * Lost-password email. WooCommerce passes the login and reset key; the key
	 * is used ONLY to make the hook signature match and is never stored/logged.
	 *
	 * @param string $user_login User login.
	 */
	public static function open_reset_context( $user_login = '' ): void {
		$user = is_string( $user_login ) && '' !== $user_login ? get_user_by( 'login', $user_login ) : false;

		self::$contexts[] = array(
			'key'   => 'reset',
			'user'  => $user ? (int) $user->ID : 0,
			'order' => 0,
			'lang'  => null,
		);
	}

	public static function close_reset_context(): void {
		self::close( 'reset' );
	}

	/**
	 * Admin "Order actions -> Resend order details to customer": WooCommerce
	 * calls trigger() directly with no "_notification" action, so mark it here.
	 *
	 * @param WC_Order $order Order.
	 */
	public static function open_resend_context( $order = null ): void {
		self::$contexts[] = array(
			'key'   => 'resend',
			'user'  => 0,
			'order' => $order instanceof WC_Order ? (int) $order->get_id() : 0,
			'lang'  => null,
		);
	}

	public static function close_resend_context(): void {
		self::close( 'resend' );
	}

	/**
	 * Pop a context; first close any switch that was opened inside it and
	 * never restored (defensive: a third-party email that skipped restore).
	 *
	 * @param string $key Context key.
	 */
	private static function close( string $key ): void {
		$depth = count( self::$contexts );

		while ( ! empty( self::$open ) && (int) end( self::$open )['depth'] >= $depth ) {
			$entry = array_pop( self::$open );
			B2Bora_ML_Locale::restore( $entry['token'] );
			self::reload_woocommerce_textdomain();
		}

		if ( $depth > 0 && self::$contexts[ $depth - 1 ]['key'] === $key ) {
			array_pop( self::$contexts );
		}
	}

	/* ------------------------------------------------------------------ *
	 * Locale switch around WC_Email::trigger()
	 * ------------------------------------------------------------------ */

	/**
	 * Replaces WC_Email::setup_locale()'s switch for customer emails.
	 *
	 * @param bool     $allow Whether WooCommerce may switch.
	 * @param WC_Email $email Email being triggered.
	 * @return bool
	 */
	public static function maybe_switch( $allow, $email ) {
		if ( ! $email instanceof WC_Email || ! $email->is_customer_email() || empty( self::$contexts ) ) {
			return $allow; // Admin emails and unknown contexts: WooCommerce's default behaviour.
		}

		$index   = array_key_last( self::$contexts );
		$context = self::$contexts[ $index ];

		if ( null === $context['lang'] ) {
			$context['lang']            = B2Bora_ML_Language::email_language( $context['user'], $context['order'] );
			self::$contexts[ $index ] = $context;
		}

		$token = B2Bora_ML_Locale::switch_to( $context['lang'] );

		self::$open[] = array(
			'object' => spl_object_id( $email ),
			'token'  => $token,
			'depth'  => count( self::$contexts ),
		);

		self::reload_woocommerce_textdomain();

		if ( B2Bora_ML_Debug::enabled() ) {
			B2Bora_ML_Debug::log(
				array(
					'event'      => 'email_locale_switched',
					'email'      => $email->id,
					'hook'       => $context['key'],
					'user_id'    => $context['user'],
					'user_lang'  => $context['user'] > 0 ? B2Bora_ML_Language::user_language( $context['user'] ) : '',
					'order_id'   => $context['order'],
					'order_lang' => $context['order'] > 0 ? B2Bora_ML_Language::order_language( $context['order'] ) : '',
					'lang'       => $context['lang'],
					'locale'     => $token['locale'],
					'switched'   => $token['switched'],
				)
			);
		}

		return false; // WooCommerce must not also switch (its restore would pop ours).
	}

	/**
	 * Replaces WC_Email::restore_locale()'s restore for emails we switched.
	 *
	 * @param bool     $allow Whether WooCommerce may restore.
	 * @param WC_Email $email Email that finished.
	 * @return bool
	 */
	public static function maybe_restore( $allow, $email ) {
		if ( ! $email instanceof WC_Email || empty( self::$open ) ) {
			return $allow;
		}

		$id = spl_object_id( $email );

		for ( $i = count( self::$open ) - 1; $i >= 0; $i-- ) {
			if ( self::$open[ $i ]['object'] !== $id ) {
				continue;
			}

			$entry = self::$open[ $i ];
			array_splice( self::$open, $i, 1 );

			B2Bora_ML_Locale::restore( $entry['token'] );
			self::reload_woocommerce_textdomain();

			B2Bora_ML_Debug::log(
				array(
					'event'    => 'email_locale_restored',
					'email'    => $email->id,
					'locale'   => determine_locale(),
					'restored' => true,
				)
			);

			return false;
		}

		return $allow;
	}

	/**
	 * WooCommerce reloads its own text domain when it switches locale; since
	 * we skip its switch we do the same, so its strings follow the locale.
	 */
	private static function reload_woocommerce_textdomain(): void {
		if ( function_exists( 'WC' ) ) {
			$wc = WC();

			if ( is_object( $wc ) && method_exists( $wc, 'load_plugin_textdomain' ) ) {
				$wc->load_plugin_textdomain();
			}
		}
	}

	/* ------------------------------------------------------------------ *
	 * Admin-typed email text -> Polylang String Translations
	 * ------------------------------------------------------------------ */

	/**
	 * Translate admin-typed text of CUSTOMER emails while one is being built.
	 *
	 * @param mixed    $value     Option value.
	 * @param WC_Email $email     Email.
	 * @param mixed    $raw       Unused.
	 * @param string   $key       Option key.
	 * @return mixed
	 */
	public static function translate_option( $value, $email = null, $raw = null, $key = '' ) {
		$language = B2Bora_ML_Locale::active_language();

		if ( '' === $language || ! is_string( $value ) || '' === $value || ! in_array( $key, self::TEXT_KEYS, true ) ) {
			return $value;
		}

		if ( ! $email instanceof WC_Email || ! $email->is_customer_email() ) {
			return $value;
		}

		return b2bora_translate_string( $value, $language );
	}

	/**
	 * Translate the shared email footer text while a customer email is built.
	 *
	 * @param mixed $text Footer text.
	 * @return mixed
	 */
	public static function translate_footer( $text ) {
		$language = B2Bora_ML_Locale::active_language();

		return ( '' !== $language && is_string( $text ) && '' !== $text ) ? b2bora_translate_string( $text, $language ) : $text;
	}

	/**
	 * Strings shipped by this plugin for Polylang. Other B2Bora code (e.g. a
	 * future Partner Club email) can add its own via this filter.
	 *
	 * @return array<string,string> name => English source string.
	 */
	public static function own_strings(): array {
		$strings = array(
			'account_created'        => 'Your B2Bora account has been created.',
			'order_request_received' => 'Your order request has been received.',
		);

		return (array) apply_filters( 'b2bora_ml_strings', $strings );
	}

	/**
	 * Register the strings with Polylang. Only on Polylang's String
	 * Translations screen (Polylang ignores registrations elsewhere), so this
	 * costs nothing on any other request.
	 */
	public static function register_polylang_strings(): void {
		if ( ! function_exists( 'pll_register_string' ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check.

		if ( 'mlang_strings' !== $page ) {
			return;
		}

		foreach ( self::own_strings() as $name => $string ) {
			pll_register_string( (string) $name, (string) $string, 'B2Bora Emails' );
		}

		if ( function_exists( 'WC' ) && is_object( WC() ) && method_exists( WC(), 'mailer' ) ) {
			foreach ( WC()->mailer()->get_emails() as $email ) {
				if ( ! $email instanceof WC_Email || ! $email->is_customer_email() ) {
					continue;
				}

				foreach ( self::TEXT_KEYS as $key ) {
					$value = $email->get_option( $key );

					if ( is_string( $value ) && '' !== trim( $value ) ) {
						pll_register_string( $email->id . ' / ' . $key, $value, 'B2Bora Emails', false !== strpos( $value, "\n" ) );
					}
				}
			}
		}

		$footer = get_option( 'woocommerce_email_footer_text' );

		if ( is_string( $footer ) && '' !== trim( $footer ) ) {
			pll_register_string( 'email footer text', $footer, 'B2Bora Emails' );
		}
	}
}
