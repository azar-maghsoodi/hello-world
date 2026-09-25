<?php
/**
 * Functional tests for the core Partner Club logic, run against the
 * stubbed WordPress + SQLite environment set up in bootstrap.php.
 *
 * Each test_* function is auto-discovered and run in isolation (state is
 * reset before every test). Assertions throw on failure so run.php can
 * report a clear pass/fail per test.
 */

function assert_true( $condition, $message ) {
	if ( ! $condition ) {
		throw new Exception( "Assertion failed: {$message}" );
	}
}

function assert_equal( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		throw new Exception( "Assertion failed: {$message} (expected " . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) . ')' );
	}
}

function b2bora_make_order( $id, $user_id, $total, $tax = 0.0, $shipping = 0.0, $shipping_tax = 0.0 ) {
	$order                 = new WC_Order( $id, $user_id );
	$order->total          = $total;
	$order->total_tax      = $tax;
	$order->shipping_total = $shipping;
	$order->shipping_tax   = $shipping_tax;
	b2bora_test_register_order( $order );
	return $order;
}

// ---------------------------------------------------------------------
// 1. First order awards correct points.
// ---------------------------------------------------------------------
function test_first_order_awards_correct_points() {
	b2bora_test_register_user( 1 );
	b2bora_make_order( 1001, 1, 450.00 );

	B2Bora_PC_Orders::handle_order_completed( 1001 );

	// 450 eligible EUR * default rate (1 point/EUR) = 450 points, plus the
	// one-time welcome bonus (default 500) since this is the user's first order.
	assert_equal( 450 + 500, B2Bora_PC_Points::get_balance( 1 ), 'balance after first order + welcome bonus' );

	$order_txn = B2Bora_PC_Points::get_by_reference( 'order_points_1001' );
	assert_true( null !== $order_txn, 'order transaction recorded' );
	assert_equal( 450, (int) $order_txn['points'], 'order points amount' );
}

// ---------------------------------------------------------------------
// 2. Same order cannot award twice.
// ---------------------------------------------------------------------
function test_order_points_are_idempotent() {
	b2bora_test_register_user( 1 );
	b2bora_make_order( 1001, 1, 450.00 );

	B2Bora_PC_Orders::handle_order_completed( 1001 );
	$balance_after_first = B2Bora_PC_Points::get_balance( 1 );

	B2Bora_PC_Orders::handle_order_completed( 1001 );
	B2Bora_PC_Orders::handle_order_completed( 1001 );

	assert_equal( $balance_after_first, B2Bora_PC_Points::get_balance( 1 ), 'balance unchanged after repeat calls' );
}

// ---------------------------------------------------------------------
// 3. Welcome bonus awarded only once.
// ---------------------------------------------------------------------
function test_welcome_bonus_awarded_once() {
	b2bora_test_register_user( 1 );

	B2Bora_PC_Orders::maybe_award_welcome_bonus( 1 );
	B2Bora_PC_Orders::maybe_award_welcome_bonus( 1 );
	B2Bora_PC_Orders::maybe_award_welcome_bonus( 1 );

	assert_equal( 500, B2Bora_PC_Points::get_balance( 1 ), 'welcome bonus only applied once' );
	assert_equal( 1, B2Bora_PC_Points::count_transactions_of_type( 1, B2Bora_PC_Points::TYPE_WELCOME_BONUS ), 'exactly one welcome bonus row' );
}

// ---------------------------------------------------------------------
// 4. Reorder bonus works.
// ---------------------------------------------------------------------
function test_reorder_bonus_awarded_within_window() {
	b2bora_test_register_user( 1 );
	b2bora_make_order( 1001, 1, 100.00 );
	B2Bora_PC_Orders::handle_order_completed( 1001 );

	// Move the clock forward 10 days (within the default 30 day window).
	$GLOBALS['b2bora_test_now'] += 10 * DAY_IN_SECONDS;

	b2bora_make_order( 1002, 1, 100.00 );
	B2Bora_PC_Orders::handle_order_completed( 1002 );

	$expected = 100 + 500 /* welcome */ + 100 + 300 /* reorder */;
	assert_equal( $expected, B2Bora_PC_Points::get_balance( 1 ), 'balance includes reorder bonus' );
	assert_true( B2Bora_PC_Points::reference_exists( 'reorder_bonus_order_1002' ), 'reorder bonus transaction exists' );
}

function test_reorder_bonus_not_awarded_outside_window() {
	b2bora_test_register_user( 1 );
	b2bora_make_order( 1001, 1, 100.00 );
	B2Bora_PC_Orders::handle_order_completed( 1001 );

	// 31 days later: outside the default 30 day window.
	$GLOBALS['b2bora_test_now'] += 31 * DAY_IN_SECONDS;

	b2bora_make_order( 1002, 1, 100.00 );
	B2Bora_PC_Orders::handle_order_completed( 1002 );

	assert_true( ! B2Bora_PC_Points::reference_exists( 'reorder_bonus_order_1002' ), 'no reorder bonus outside window' );
}

// ---------------------------------------------------------------------
// 5. Reorder bonus cannot duplicate.
// ---------------------------------------------------------------------
function test_reorder_bonus_is_idempotent() {
	b2bora_test_register_user( 1 );
	b2bora_make_order( 1001, 1, 100.00 );
	B2Bora_PC_Orders::handle_order_completed( 1001 );

	$order2 = b2bora_make_order( 1002, 1, 100.00 );
	B2Bora_PC_Orders::handle_order_completed( 1002 );
	$balance_after_first = B2Bora_PC_Points::get_balance( 1 );

	// Directly re-invoke the reorder-bonus check for the same order id.
	$previous = B2Bora_PC_Points::get_last_transaction_of_type( 1, B2Bora_PC_Points::TYPE_ORDER );
	B2Bora_PC_Orders::maybe_award_reorder_bonus( 1, 1002, $previous );

	assert_equal( $balance_after_first, B2Bora_PC_Points::get_balance( 1 ), 'reorder bonus not duplicated for same order' );
}

// ---------------------------------------------------------------------
// 6. Reward redemption deducts correct points.
// ---------------------------------------------------------------------
function test_redemption_deducts_points() {
	b2bora_test_register_user( 1 );
	B2Bora_PC_Points::record_transaction( 1, B2Bora_PC_Points::TYPE_MANUAL, 2000, array( 'description' => 'seed' ) );

	$reward_id = B2Bora_PC_Rewards::save_reward( array( 'name' => '€20 Order Credit', 'points_cost' => 2000, 'reward_type' => 'order_credit', 'reward_value' => 20 ) );

	$redemption_id = B2Bora_PC_Redemptions::redeem( 1, $reward_id );

	assert_true( ! is_wp_error( $redemption_id ), 'redemption succeeds' );
	assert_equal( 0, B2Bora_PC_Points::get_balance( 1 ), 'points deducted fully' );

	$redemption = B2Bora_PC_Redemptions::get_redemption( $redemption_id );
	assert_equal( 2000, (int) $redemption['points_spent'], 'points_spent recorded correctly' );
}

// ---------------------------------------------------------------------
// 7. Cannot redeem without enough points.
// ---------------------------------------------------------------------
function test_cannot_redeem_without_enough_points() {
	b2bora_test_register_user( 1 );
	$reward_id = B2Bora_PC_Rewards::save_reward( array( 'name' => 'Expensive Reward', 'points_cost' => 5000, 'reward_type' => 'order_credit', 'reward_value' => 50 ) );

	$result = B2Bora_PC_Redemptions::redeem( 1, $reward_id );

	assert_true( is_wp_error( $result ), 'redemption rejected' );
	assert_equal( 0, B2Bora_PC_Points::get_balance( 1 ), 'balance untouched' );
}

// ---------------------------------------------------------------------
// 8. Refund reverses points.
// ---------------------------------------------------------------------
function test_full_refund_reverses_points() {
	b2bora_test_register_user( 1 );
	b2bora_make_order( 1050, 1, 500.00 );
	B2Bora_PC_Orders::handle_order_completed( 1050 );

	$balance_before_refund = B2Bora_PC_Points::get_balance( 1 );

	$order  = wc_get_order( 1050 );
	$refund = new WC_Order_Refund( 9001, -500.00 );
	b2bora_test_register_order( $refund );
	$order->total_refunded = 500.00;

	B2Bora_PC_Orders::handle_order_refunded( 1050, 9001 );

	assert_equal( $balance_before_refund - 500, B2Bora_PC_Points::get_balance( 1 ), 'full refund reverses all order points' );

	$original = B2Bora_PC_Points::get_by_reference( 'order_points_1050' );
	assert_equal( 500, (int) $original['points'], 'original transaction untouched' );
}

// ---------------------------------------------------------------------
// 9. Partial refund reverses correct amount.
// ---------------------------------------------------------------------
function test_partial_refund_reverses_proportional_points() {
	b2bora_test_register_user( 1 );
	b2bora_make_order( 1051, 1, 500.00 );
	B2Bora_PC_Orders::handle_order_completed( 1051 );

	$balance_before_refund = B2Bora_PC_Points::get_balance( 1 );

	$order = wc_get_order( 1051 );
	$order->total_refunded = 100.00; // 20% of the 500 eligible amount.
	b2bora_test_register_order( new WC_Order_Refund( 9002, -100.00 ) );

	B2Bora_PC_Orders::handle_order_refunded( 1051, 9002 );

	// 20% of 500 points = 100 points reversed.
	assert_equal( $balance_before_refund - 100, B2Bora_PC_Points::get_balance( 1 ), 'proportional refund reversal' );
}

function test_duplicate_refund_reversal_is_prevented() {
	b2bora_test_register_user( 1 );
	b2bora_make_order( 1052, 1, 500.00 );
	B2Bora_PC_Orders::handle_order_completed( 1052 );

	$order = wc_get_order( 1052 );
	$order->total_refunded = 500.00;
	b2bora_test_register_order( new WC_Order_Refund( 9003, -500.00 ) );

	B2Bora_PC_Orders::handle_order_refunded( 1052, 9003 );
	$balance_after_first_reversal = B2Bora_PC_Points::get_balance( 1 );

	B2Bora_PC_Orders::handle_order_refunded( 1052, 9003 );

	assert_equal( $balance_after_first_reversal, B2Bora_PC_Points::get_balance( 1 ), 'refund reversal cannot be duplicated for the same refund id' );
}

// ---------------------------------------------------------------------
// 10. Level calculation works.
// ---------------------------------------------------------------------
function test_level_calculation() {
	b2bora_test_register_user( 1 );
	B2Bora_PC_Points::record_transaction( 1, B2Bora_PC_Points::TYPE_MANUAL, 7450, array( 'description' => 'seed' ) );

	$progress = B2Bora_PC_Levels::get_progress_for_user( 1 );

	assert_equal( 'Premium', $progress['current_level']['name'], 'current level' );
	assert_equal( 'Preferred', $progress['next_level']['name'], 'next level' );
	assert_equal( 2550, $progress['points_needed'], 'points needed to next level' );
	assert_equal( 74.5, $progress['progress_pct'], 'progress percentage' );
}

// ---------------------------------------------------------------------
// 11. Mission cannot reward twice.
// ---------------------------------------------------------------------
function test_mission_completion_is_idempotent() {
	b2bora_test_register_user( 1 );

	$mission_id = B2Bora_PC_Missions::save_mission( array(
		'name'         => 'First Order',
		'type'         => B2Bora_PC_Missions::TYPE_FIRST_ORDER,
		'bonus_points' => 250,
	) );

	$order = b2bora_make_order( 2001, 1, 50.00 );
	B2Bora_PC_Orders::handle_order_completed( 2001 ); // Triggers mission evaluation once.

	$balance_after_first_order = B2Bora_PC_Points::get_balance( 1 );
	assert_true( B2Bora_PC_Missions::is_completed_by_user( $mission_id, 1 ), 'mission marked complete' );

	// Re-run evaluation directly; must not award a second time.
	B2Bora_PC_Missions::evaluate_for_order( 1, 2001, $order );

	assert_equal( $balance_after_first_order, B2Bora_PC_Points::get_balance( 1 ), 'mission bonus not duplicated' );
}

// ---------------------------------------------------------------------
// 13. Invalid user IDs are rejected.
// ---------------------------------------------------------------------
function test_invalid_user_ids_are_rejected() {
	// Unknown user (never registered) -> record_transaction must refuse.
	$result = B2Bora_PC_Points::record_transaction( 99999, B2Bora_PC_Points::TYPE_MANUAL, 100, array( 'description' => 'x' ) );
	assert_true( false === $result, 'unknown user id rejected' );

	$result_zero = B2Bora_PC_Points::record_transaction( 0, B2Bora_PC_Points::TYPE_MANUAL, 100, array( 'description' => 'x' ) );
	assert_true( false === $result_zero, 'zero user id rejected' );

	assert_equal( 0, B2Bora_PC_Security::sanitize_user_id( 'not-a-number' ), 'non-numeric id sanitised to 0' );
}

function test_negative_balance_is_never_allowed() {
	b2bora_test_register_user( 1 );
	B2Bora_PC_Points::record_transaction( 1, B2Bora_PC_Points::TYPE_MANUAL, 100, array( 'description' => 'seed' ) );

	$result = B2Bora_PC_Points::record_transaction( 1, B2Bora_PC_Points::TYPE_REDEMPTION, -500, array( 'description' => 'over-spend attempt' ) );

	assert_true( false === $result, 'deduction beyond balance rejected' );
	assert_equal( 100, B2Bora_PC_Points::get_balance( 1 ), 'balance unchanged' );
}

// ---------------------------------------------------------------------
// 12 & 16 (structural safeguards, see tests/README.md for scope notes).
// ---------------------------------------------------------------------
function test_ajax_handler_never_trusts_a_client_supplied_user_id() {
	$source = file_get_contents( B2BORA_PC_PATH . 'public/class-ajax.php' );
	assert_true( false === strpos( $source, "_POST['user_id']" ), 'AJAX handler must only ever act on get_current_user_id(), never a posted user_id' );
	assert_true( false !== strpos( $source, 'get_current_user_id()' ), 'AJAX handler uses the authenticated current user' );
	assert_true( false !== strpos( $source, 'verify_ajax_customer_request' ), 'AJAX handler verifies a nonce before acting' );
}

function test_polylang_usage_is_always_guarded() {
	$files = glob( B2BORA_PC_PATH . '{includes,admin/views,public,public/views}/*.php', GLOB_BRACE );
	foreach ( $files as $file ) {
		$source = file_get_contents( $file );
		if ( false === strpos( $source, 'pll_' ) ) {
			continue;
		}
		assert_true(
			false !== strpos( $source, "function_exists( 'pll_" ) || false !== strpos( $source, 'function_exists(\'pll_' ),
			basename( $file ) . ' calls a Polylang function without a function_exists() guard'
		);
	}
}

function test_woocommerce_absence_is_guarded_before_boot() {
	$plugin_source = file_get_contents( B2BORA_PC_PATH . 'includes/class-plugin.php' );
	assert_true( false !== strpos( $plugin_source, "class_exists( 'WooCommerce' )" ), 'plugin checks WooCommerce is active before using it' );
	assert_true( false !== strpos( $plugin_source, 'render_missing_woocommerce_notice' ), 'plugin shows an admin notice instead of assuming WooCommerce' );

	$orders_source = file_get_contents( B2BORA_PC_PATH . 'includes/class-orders.php' );
	assert_true( false !== strpos( $orders_source, "function_exists( 'wc_get_order' )" ), 'order handler guards against WooCommerce functions being undefined' );
}
