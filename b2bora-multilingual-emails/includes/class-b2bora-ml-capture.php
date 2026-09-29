<?php
/**
 * Persists the language when it is known: at registration and at order creation.
 *
 * @package B2Bora_Multilingual_Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class B2Bora_ML_Capture {

	public static function init(): void {
		// Fires inside wp_insert_user(), i.e. BEFORE WooCommerce's
		// woocommerce_created_customer -> "new account" email, so the
		// language is stored by the time that email is built.
		add_action( 'user_register', array( __CLASS__, 'save_user_language' ), 5, 1 );

		// Fires on the first save of any order (classic + HPOS). The B2B
		// plugin calls wc_create_order() (which saves) before it changes
		// status, so this always runs before any status email.
		add_action( 'woocommerce_new_order', array( __CLASS__, 'save_order_language' ), 5, 2 );
	}

	/**
	 * Store the language a new account registered in. Never overwrites.
	 *
	 * @param int $user_id New user ID.
	 */
	public static function save_user_language( $user_id ): void {
		$user_id = absint( $user_id );

		if ( 0 === $user_id || '' !== B2Bora_ML_Language::normalize( get_user_meta( $user_id, B2Bora_ML_Language::META_KEY, true ) ) ) {
			return;
		}

		// Accounts created by staff in wp-admin, CLI imports, etc. have no
		// customer language to record; they resolve lazily through the
		// fallback chain instead of being stamped with the admin's language.
		$language = B2Bora_ML_Language::current_request_language();

		if ( '' === $language ) {
			return;
		}

		update_user_meta( $user_id, B2Bora_ML_Language::META_KEY, $language );

		B2Bora_ML_Debug::log(
			array(
				'event'     => 'user_language_saved',
				'user_id'   => $user_id,
				'user_lang' => $language,
			)
		);
	}

	/**
	 * Freeze the language on a new order. Never overwrites.
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order    Order (optional).
	 */
	public static function save_order_language( $order_id, $order = null ): void {
		if ( ! $order instanceof WC_Order ) {
			$order = function_exists( 'wc_get_order' ) ? wc_get_order( absint( $order_id ) ) : false;
		}

		if ( ! $order instanceof WC_Order || '' !== B2Bora_ML_Language::normalize( $order->get_meta( B2Bora_ML_Language::META_KEY, true ) ) ) {
			return;
		}

		// The language the customer is using right now (B2B AJAX posts it).
		// In wp-admin this is '' and the order is left unstamped; its emails
		// then use the customer's language via the fallback chain.
		$language = B2Bora_ML_Language::current_request_language();

		if ( '' === $language ) {
			return;
		}

		$order->update_meta_data( B2Bora_ML_Language::META_KEY, $language );
		$order->save_meta_data();

		B2Bora_ML_Debug::log(
			array(
				'event'      => 'order_language_saved',
				'order_id'   => $order->get_id(),
				'order_lang' => $language,
			)
		);
	}
}
