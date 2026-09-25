<?php
/**
 * Order integration layer. Listens for the custom `b2bora_order_completed`
 * action (the primary integration point with B2Bora's own order-request
 * workflow) and, optionally, standard WooCommerce order-completed events.
 *
 * IMPORTANT: a WooCommerce order being *created* never awards points by
 * itself. B2Bora orders are requests that may be confirmed manually later,
 * so points are only ever awarded when this class is told an order is
 * confirmed/completed.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2Bora_PC_Orders
 */
class B2Bora_PC_Orders {

	/**
	 * Register hooks. Called from the main plugin bootstrap only when
	 * WooCommerce is active.
	 */
	public static function init() {
		// Primary integration point: the existing B2Bora order workflow
		// (email/WhatsApp/manual confirmation) should call this action once
		// an order is confirmed. See README for the exact call.
		add_action( 'b2bora_order_completed', array( __CLASS__, 'handle_order_completed' ), 10, 1 );

		// Optional fallback for a standard WooCommerce "completed" status,
		// only wired up when explicitly enabled in Settings because most
		// B2Bora orders do not go through a normal checkout completion.
		if ( B2Bora_PC_Settings::get( 'enable_wc_completed_fallback' ) ) {
			add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'handle_order_completed' ), 10, 1 );
		}

		if ( B2Bora_PC_Settings::get( 'enable_refund_reversal' ) ) {
			add_action( 'woocommerce_order_refunded', array( __CLASS__, 'handle_order_refunded' ), 10, 2 );
		}
	}

	/**
	 * Handle an order being confirmed/completed by the B2Bora workflow.
	 *
	 * Steps (per spec): validate order, get customer, check for an
	 * existing award, calculate eligible amount, calculate points, create
	 * the transaction, update balance (implicit via the ledger), record
	 * the source order, log the event.
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public static function handle_order_completed( $order_id ) {
		$order_id = absint( $order_id );

		if ( ! $order_id || ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || ! is_a( $order, 'WC_Abstract_Order' ) ) {
			B2Bora_PC_Logger::warning( "b2bora_order_completed fired for order #{$order_id} but it could not be loaded." );
			return;
		}

		$user_id = $order->get_customer_id();
		if ( ! $user_id ) {
			B2Bora_PC_Logger::info( "Order #{$order_id} has no registered customer; skipping points." );
			return;
		}

		$reference_key = "order_points_{$order_id}";

		if ( B2Bora_PC_Points::reference_exists( $reference_key ) ) {
			B2Bora_PC_Logger::info( "Order #{$order_id} already has points awarded; skipping." );
			return;
		}

		// Look up the customer's previous order award BEFORE recording this
		// one, so the reorder-bonus window is measured against their prior
		// order rather than the one we are about to insert.
		$previous_order_transaction = B2Bora_PC_Points::get_last_transaction_of_type( $user_id, B2Bora_PC_Points::TYPE_ORDER );
		$is_first_order              = null === $previous_order_transaction;

		$currency = $order->get_currency();
		self::maybe_warn_about_currency_mismatch( $order_id, $currency );

		$eligible_amount = self::get_eligible_order_amount( $order );
		$points          = self::calculate_points_for_amount( $eligible_amount );

		if ( $points <= 0 ) {
			B2Bora_PC_Logger::info( "Order #{$order_id} has no eligible amount ({$eligible_amount}); no points awarded." );
			return;
		}

		$transaction_id = B2Bora_PC_Points::record_transaction(
			$user_id,
			B2Bora_PC_Points::TYPE_ORDER,
			$points,
			array(
				'order_id'      => $order_id,
				'reference_key' => $reference_key,
				/* translators: 1: order number */
				'description'   => sprintf( __( 'Order #%d', 'b2bora-partner-club' ), $order->get_order_number() ),
			)
		);

		if ( ! $transaction_id ) {
			// record_transaction() already logged the reason (duplicate,
			// invalid user, or would-be-negative balance, which cannot
			// happen for a positive award but is checked defensively).
			return;
		}

		B2Bora_PC_Logger::info( "Awarded {$points} points for order #{$order_id} to user #{$user_id} (eligible amount: {$eligible_amount} {$currency})." );

		if ( $is_first_order ) {
			self::maybe_award_welcome_bonus( $user_id );
		} else {
			self::maybe_award_reorder_bonus( $user_id, $order_id, $previous_order_transaction );
		}

		if ( class_exists( 'B2Bora_PC_Missions' ) ) {
			B2Bora_PC_Missions::evaluate_for_order( $user_id, $order_id, $order );
		}

		/**
		 * Fires after order points (and any resulting bonuses) have been
		 * processed for an order.
		 *
		 * @param int $order_id Order ID.
		 * @param int $user_id  Customer user ID.
		 * @param int $points   Points awarded for the order itself.
		 */
		do_action( 'b2bora_pc_order_points_awarded', $order_id, $user_id, $points );
	}

	/**
	 * NOT VERIFIED: whether "Easy Currency" (active on B2Bora) changes the
	 * actual stored order currency/total for orders placed while browsing
	 * in a non-base currency, or only changes the front-end display. This
	 * plugin applies one flat "points per currency unit" rate with no
	 * conversion (per spec: "Currency conversion should not be silently
	 * performed"), which is only correct if every order is always stored
	 * in the shop's own base currency. Rather than guess, this logs a
	 * clearly visible warning the first time an order in a different
	 * currency is seen, so a real occurrence is caught and investigated
	 * instead of silently mis-awarding points at the wrong rate.
	 *
	 * @param int    $order_id Order ID, for the log message.
	 * @param string $currency Order's currency code.
	 */
	private static function maybe_warn_about_currency_mismatch( $order_id, $currency ) {
		if ( ! function_exists( 'get_woocommerce_currency' ) ) {
			return;
		}

		$base_currency = get_woocommerce_currency();

		if ( $currency && $base_currency && $currency !== $base_currency ) {
			B2Bora_PC_Logger::warning(
				"Order #{$order_id} is in currency '{$currency}' but the store's base currency is '{$base_currency}'. " .
				'Points are calculated on the raw order total with no currency conversion - verify this order\'s totals are actually in the base currency (e.g. a multi-currency plugin only changing display) before trusting the awarded points.'
			);
		}
	}

	/**
	 * Award the one-time welcome bonus, if enabled and not already given.
	 *
	 * @param int $user_id User ID.
	 */
	public static function maybe_award_welcome_bonus( $user_id ) {
		if ( ! B2Bora_PC_Settings::get( 'welcome_bonus_enabled' ) ) {
			return;
		}

		$points = absint( B2Bora_PC_Settings::get( 'welcome_bonus_points' ) );
		if ( $points <= 0 ) {
			return;
		}

		$reference_key = "welcome_bonus_{$user_id}";
		if ( B2Bora_PC_Points::reference_exists( $reference_key ) ) {
			return;
		}

		B2Bora_PC_Points::record_transaction(
			$user_id,
			B2Bora_PC_Points::TYPE_WELCOME_BONUS,
			$points,
			array(
				'reference_key' => $reference_key,
				'description'   => __( 'Welcome bonus', 'b2bora-partner-club' ),
			)
		);
	}

	/**
	 * Award the reorder bonus when the current order falls within the
	 * configured window of the customer's previous order.
	 *
	 * @param int        $user_id                     User ID.
	 * @param int        $order_id                    Current order ID (used for the idempotency key).
	 * @param array|null $previous_order_transaction Ledger row of the previous order award.
	 */
	public static function maybe_award_reorder_bonus( $user_id, $order_id, $previous_order_transaction ) {
		if ( ! B2Bora_PC_Settings::get( 'reorder_bonus_enabled' ) || empty( $previous_order_transaction ) ) {
			return;
		}

		$points = absint( B2Bora_PC_Settings::get( 'reorder_bonus_points' ) );
		if ( $points <= 0 ) {
			return;
		}

		$window_days = absint( B2Bora_PC_Settings::get( 'reorder_window_days' ) );

		$previous_time = strtotime( $previous_order_transaction['created_at'] );
		if ( false === $previous_time ) {
			return;
		}

		$days_since_previous = ( current_time( 'timestamp' ) - $previous_time ) / DAY_IN_SECONDS; // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

		if ( $days_since_previous > $window_days ) {
			return;
		}

		$reference_key = "reorder_bonus_order_{$order_id}";
		if ( B2Bora_PC_Points::reference_exists( $reference_key ) ) {
			return;
		}

		B2Bora_PC_Points::record_transaction(
			$user_id,
			B2Bora_PC_Points::TYPE_REORDER_BONUS,
			$points,
			array(
				'order_id'      => $order_id,
				'reference_key' => $reference_key,
				'description'   => __( 'Reorder bonus', 'b2bora-partner-club' ),
			)
		);
	}

	/**
	 * Reverse points when an order is refunded (fully or partially). The
	 * reversal is proportional to the refunded amount relative to the
	 * order's eligible amount, and never touches the original transaction
	 * row (a new negative row is created instead).
	 *
	 * @param int $order_id  Order ID.
	 * @param int $refund_id Refund order ID (WC_Order_Refund).
	 */
	public static function handle_order_refunded( $order_id, $refund_id ) {
		$order_id  = absint( $order_id );
		$refund_id = absint( $refund_id );

		if ( ! $order_id || ! $refund_id || ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$original_reference = "order_points_{$order_id}";
		$original            = B2Bora_PC_Points::get_by_reference( $original_reference );

		if ( ! $original ) {
			// No points were ever awarded for this order, nothing to reverse.
			return;
		}

		$reversal_reference = "refund_{$refund_id}";
		if ( B2Bora_PC_Points::reference_exists( $reversal_reference ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$refund = wc_get_order( $refund_id );
		if ( ! $refund ) {
			return;
		}

		$order_eligible_amount = self::get_eligible_order_amount( $order, false );
		if ( $order_eligible_amount <= 0 ) {
			return;
		}

		// WC_Order_Refund totals are stored as negative numbers.
		$refunded_amount = abs( (float) $refund->get_amount() );
		$refunded_amount = min( $refunded_amount, $order_eligible_amount );

		$points_awarded      = (int) $original['points'];
		$proportional_points = (int) floor( ( $refunded_amount / $order_eligible_amount ) * $points_awarded );

		// Cap this reversal so that, across any number of separate partial
		// refunds on the same order, the cumulative amount reversed can
		// never exceed the points that order originally awarded (e.g. a
		// duplicate refund event or an over-refund beyond the order total
		// must not push the reversal past what was actually awarded).
		$already_reversed = B2Bora_PC_Points::get_absolute_sum_for_order_and_type( $order_id, B2Bora_PC_Points::TYPE_REFUND );
		$remaining        = max( 0, $points_awarded - $already_reversed );
		$proportional_points = min( $proportional_points, $remaining );

		if ( $proportional_points <= 0 ) {
			return;
		}

		$user_id = (int) $original['user_id'];

		B2Bora_PC_Points::record_transaction(
			$user_id,
			B2Bora_PC_Points::TYPE_REFUND,
			-$proportional_points,
			array(
				'order_id'       => $order_id,
				'reference_key'  => $reversal_reference,
				/* translators: 1: order number */
				'description'    => sprintf( __( 'Refund for order #%d', 'b2bora-partner-club' ), $order->get_order_number() ),
				'allow_negative' => true, // A refund reversal must go through even if it would (in edge cases) dip below zero.
			)
		);
	}

	/**
	 * Calculate the eligible order amount, honouring the exclude tax /
	 * shipping / refunded settings. Returned as a float in the order's
	 * currency; callers converting to points should do so via
	 * calculate_points_for_amount() to keep the integer math in one place.
	 *
	 * @param WC_Order $order            Order object.
	 * @param bool     $exclude_refunded Whether to subtract already-refunded amounts (disable when computing the base for a refund reversal itself).
	 *
	 * @return float
	 */
	public static function get_eligible_order_amount( $order, $exclude_refunded = true ) {
		$total = (float) $order->get_total();

		if ( B2Bora_PC_Settings::get( 'exclude_tax' ) ) {
			$total -= (float) $order->get_total_tax();
		}

		if ( B2Bora_PC_Settings::get( 'exclude_shipping' ) ) {
			$total -= (float) $order->get_shipping_total();
			$total -= (float) $order->get_shipping_tax();
		}

		if ( $exclude_refunded && B2Bora_PC_Settings::get( 'exclude_refunded' ) ) {
			$total -= (float) $order->get_total_refunded();
		}

		/**
		 * Filter the eligible order amount used for points calculation.
		 *
		 * @param float    $total Eligible amount.
		 * @param WC_Order $order Order object.
		 */
		$total = (float) apply_filters( 'b2bora_pc_eligible_order_amount', $total, $order );

		return max( 0.0, $total );
	}

	/**
	 * Convert an eligible currency amount into integer points using the
	 * configured rate. Arithmetic is done in integer minor units (cents)
	 * to avoid floating point drift; the single float->int conversion is
	 * an intentional, unavoidable rounding of the monetary amount, not of
	 * the points themselves.
	 *
	 * @param float $amount Eligible amount in major currency units.
	 *
	 * @return int
	 */
	public static function calculate_points_for_amount( $amount ) {
		$rate = absint( B2Bora_PC_Settings::get( 'points_per_currency_unit', 1 ) );
		if ( $rate <= 0 || $amount <= 0 ) {
			return 0;
		}

		$amount_cents = (int) round( $amount * 100 );

		return intdiv( $amount_cents * $rate, 100 );
	}
}
