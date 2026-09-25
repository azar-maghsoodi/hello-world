<?php
/**
 * Thin wrapper around the WooCommerce logger so the rest of the plugin
 * never has to worry about WooCommerce being absent.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2Bora_PC_Logger
 */
class B2Bora_PC_Logger {

	const SOURCE = 'b2bora-partner-club';

	/**
	 * Log an informational operational message.
	 *
	 * @param string $message Human readable message. Never include PII
	 *                        beyond user/order IDs, and never include
	 *                        payment or password data.
	 */
	public static function info( $message ) {
		self::log( 'info', $message );
	}

	/**
	 * Log a warning (e.g. rejected duplicate award, invalid input).
	 *
	 * @param string $message Message.
	 */
	public static function warning( $message ) {
		self::log( 'warning', $message );
	}

	/**
	 * Log an error.
	 *
	 * @param string $message Message.
	 */
	public static function error( $message ) {
		self::log( 'error', $message );
	}

	/**
	 * Write to the WooCommerce logger when available, otherwise fall back
	 * to error_log() so nothing is silently lost.
	 *
	 * @param string $level   PSR-3 style level.
	 * @param string $message Message.
	 */
	private static function log( $level, $message ) {
		$message = 'B2Bora Partner Club: ' . $message;

		if ( function_exists( 'wc_get_logger' ) ) {
			$logger = wc_get_logger();
			$logger->log( $level, $message, array( 'source' => self::SOURCE ) );
			return;
		}

		if ( function_exists( 'error_log' ) ) {
			error_log( '[' . strtoupper( $level ) . '] ' . $message );
		}
	}
}
