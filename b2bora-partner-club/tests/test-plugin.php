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
// 12b. Pallet order mission: detected from the real "Pack" attribute
// (pa_pack = palet/pallet) recorded on an order line item, not a
// quantity guess.
// ---------------------------------------------------------------------
function test_pallet_order_mission_completes_when_a_pallet_pack_is_bought() {
	b2bora_test_register_user( 1 );

	$mission_id = B2Bora_PC_Missions::save_mission( array(
		'name'         => 'Pallet Order',
		'type'         => B2Bora_PC_Missions::TYPE_PALLET_ORDER,
		'bonus_points' => 400,
	) );

	$order = b2bora_make_order( 4001, 1, 900.00 );
	$order->items[] = new B2Bora_Test_Order_Item( new B2Bora_Test_Product( 101 ), 1, array( 'pa_pack' => 'palet' ) );

	B2Bora_PC_Missions::evaluate_for_order( 1, 4001, $order );

	assert_true( B2Bora_PC_Missions::is_completed_by_user( $mission_id, 1 ), 'pallet mission completes when a line item has pa_pack = palet' );
}

function test_pallet_order_mission_matches_the_english_pack_term_too() {
	b2bora_test_register_user( 1 );

	$mission_id = B2Bora_PC_Missions::save_mission( array(
		'name'         => 'Pallet Order',
		'type'         => B2Bora_PC_Missions::TYPE_PALLET_ORDER,
		'bonus_points' => 400,
	) );

	$order = b2bora_make_order( 4002, 1, 900.00 );
	$order->items[] = new B2Bora_Test_Order_Item( new B2Bora_Test_Product( 102 ), 1, array( 'pa_pack' => 'pallet' ) );

	B2Bora_PC_Missions::evaluate_for_order( 1, 4002, $order );

	assert_true( B2Bora_PC_Missions::is_completed_by_user( $mission_id, 1 ), 'pallet mission also completes for the English "pallet" term slug' );
}

function test_pallet_order_mission_does_not_complete_for_box_or_unit_packs() {
	b2bora_test_register_user( 1 );

	$mission_id = B2Bora_PC_Missions::save_mission( array(
		'name'         => 'Pallet Order',
		'type'         => B2Bora_PC_Missions::TYPE_PALLET_ORDER,
		'bonus_points' => 400,
	) );

	$order = b2bora_make_order( 4003, 1, 900.00 );
	$order->items[] = new B2Bora_Test_Order_Item( new B2Bora_Test_Product( 103 ), 60, array( 'pa_pack' => 'bucata' ) );
	$order->items[] = new B2Bora_Test_Order_Item( new B2Bora_Test_Product( 104 ), 60, array( 'pa_pack' => 'bax' ) );

	B2Bora_PC_Missions::evaluate_for_order( 1, 4003, $order );

	assert_true( ! B2Bora_PC_Missions::is_completed_by_user( $mission_id, 1 ), 'a large order made only of unit/case packs is not a pallet order, however many units it totals' );
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
// Per-language dashboard translations.
// ---------------------------------------------------------------------
function test_translations_default_and_override() {
	assert_equal( 'Your Points', B2Bora_PC_Translations::get( 'your_points_label', 'ro' ), 'falls back to the English default when no override is saved' );

	B2Bora_PC_Translations::save( 'ro', array( 'your_points_label' => 'Punctele tale', 'redeem_button' => '' ) );

	assert_equal( 'Punctele tale', B2Bora_PC_Translations::get( 'your_points_label', 'ro' ), 'a saved override is returned' );
	assert_equal( 'Redeem', B2Bora_PC_Translations::get( 'redeem_button', 'ro' ), 'a blank submitted value keeps the default rather than saving an empty override' );
	assert_equal( 'Your Points', B2Bora_PC_Translations::get( 'your_points_label', 'en' ), 'an override for one language never leaks into another' );
}

function test_translations_current_language_defaults_to_en_without_polylang() {
	assert_equal( 'en', B2Bora_PC_Translations::current_language(), 'falls back to en when Polylang is not active' );
}

function test_translations_unknown_key_returns_empty_string() {
	assert_equal( '', B2Bora_PC_Translations::get( 'not_a_real_key' ), 'an unknown key returns an empty string rather than erroring' );
}

// ---------------------------------------------------------------------
// Points rate ratio (fractional rates without floating point).
// ---------------------------------------------------------------------
function test_fractional_points_rate_via_ratio() {
	B2Bora_PC_Settings::update( array( 'points_per_amount' => 1, 'currency_amount_for_points' => 3 ) );

	// 1 point per 3 units: 300 units -> exactly 100 points.
	assert_equal( 100, B2Bora_PC_Orders::calculate_points_for_amount( 300 ), '1 point per 3 units on an exact multiple' );

	// 299 units -> floors to 99 points, never rounds up or uses a float.
	assert_equal( 99, B2Bora_PC_Orders::calculate_points_for_amount( 299 ), '1 point per 3 units floors correctly on a non-exact multiple' );
}

function test_points_rate_ratio_matches_old_single_rate_behaviour() {
	// The default 1-per-1 ratio must behave exactly like the old flat rate.
	B2Bora_PC_Settings::update( array( 'points_per_amount' => 400, 'currency_amount_for_points' => 1 ) );

	assert_equal( 400, B2Bora_PC_Orders::calculate_points_for_amount( 1 ), '400 points per 1 unit on a 1-unit order' );
	assert_equal( 200, B2Bora_PC_Orders::calculate_points_for_amount( 0.5 ), '400 points per 1 unit on a half-unit order matches the €0.50 = 200 points example' );
}

function test_legacy_points_rate_setting_migrates_on_read() {
	// Simulate a site that saved settings before the ratio existed: only
	// the old single-integer key is in the stored option.
	update_option( B2Bora_PC_Settings::OPTION_KEY, array( 'points_per_currency_unit' => 400 ) );

	$ref = new ReflectionProperty( 'B2Bora_PC_Settings', 'cache' );
	$ref->setAccessible( true );
	$ref->setValue( null, null );

	assert_equal( 400, B2Bora_PC_Settings::get( 'points_per_amount' ), 'legacy rate is migrated into points_per_amount on read' );
	assert_equal( 1, B2Bora_PC_Settings::get( 'currency_amount_for_points' ), 'legacy rate migrates with a denominator of 1 (equivalent ratio)' );
	assert_equal( 400, B2Bora_PC_Orders::calculate_points_for_amount( 1 ), 'the migrated rate is actually used for the real calculation' );
}

// ---------------------------------------------------------------------
// Reorder-bonus boundary conditions.
// ---------------------------------------------------------------------
function test_reorder_bonus_boundary_29_days() {
	b2bora_test_register_user( 1 );
	b2bora_make_order( 1001, 1, 100.00 );
	B2Bora_PC_Orders::handle_order_completed( 1001 );

	$GLOBALS['b2bora_test_now'] += 29 * DAY_IN_SECONDS;

	b2bora_make_order( 1002, 1, 100.00 );
	B2Bora_PC_Orders::handle_order_completed( 1002 );

	assert_true( B2Bora_PC_Points::reference_exists( 'reorder_bonus_order_1002' ), '29 days is within the 30 day window' );
}

function test_reorder_bonus_boundary_exactly_30_days() {
	b2bora_test_register_user( 1 );
	b2bora_make_order( 1001, 1, 100.00 );
	B2Bora_PC_Orders::handle_order_completed( 1001 );

	$GLOBALS['b2bora_test_now'] += 30 * DAY_IN_SECONDS;

	b2bora_make_order( 1002, 1, 100.00 );
	B2Bora_PC_Orders::handle_order_completed( 1002 );

	assert_true( B2Bora_PC_Points::reference_exists( 'reorder_bonus_order_1002' ), 'exactly 30 days is still within the (inclusive) window' );
}

// ---------------------------------------------------------------------
// Multiple partial refunds on the same order (Phase 9 audit addition).
// ---------------------------------------------------------------------
function test_multiple_partial_refunds_never_exceed_original_points() {
	b2bora_test_register_user( 1 );
	b2bora_make_order( 1060, 1, 500.00 ); // 500 points.
	B2Bora_PC_Orders::handle_order_completed( 1060 );

	$order = wc_get_order( 1060 );

	// First partial refund: -100 of 500.
	$order->total_refunded = 100.00;
	b2bora_test_register_order( new WC_Order_Refund( 9101, -100.00 ) );
	B2Bora_PC_Orders::handle_order_refunded( 1060, 9101 );

	// Second, separate partial refund: another -100 (cumulative 200 of 500).
	$order->total_refunded = 200.00;
	b2bora_test_register_order( new WC_Order_Refund( 9102, -100.00 ) );
	B2Bora_PC_Orders::handle_order_refunded( 1060, 9102 );

	assert_equal( 500 - 200, B2Bora_PC_Points::get_balance( 1 ) - 500 /* welcome bonus */, 'two partial refunds reverse a cumulative 200 points' );

	// A third, over-sized "refund" that would push cumulative reversal
	// past what the order ever awarded must be capped at the remainder.
	$order->total_refunded = 700.00; // More than the order's own total - should never happen, but must not over-reverse.
	b2bora_test_register_order( new WC_Order_Refund( 9103, -500.00 ) );
	B2Bora_PC_Orders::handle_order_refunded( 1060, 9103 );

	$total_reversed = B2Bora_PC_Points::get_absolute_sum_for_order_and_type( 1060, B2Bora_PC_Points::TYPE_REFUND );
	assert_equal( 500, $total_reversed, 'cumulative reversal is capped at the 500 points the order originally awarded' );
}

// ---------------------------------------------------------------------
// Redemption edge cases.
// ---------------------------------------------------------------------
function test_redeem_rejects_invalid_reward_id() {
	b2bora_test_register_user( 1 );
	B2Bora_PC_Points::record_transaction( 1, B2Bora_PC_Points::TYPE_MANUAL, 5000, array( 'description' => 'seed' ) );

	$result = B2Bora_PC_Redemptions::redeem( 1, 999999 );

	assert_true( is_wp_error( $result ), 'redeeming a non-existent reward id is rejected' );
	assert_equal( 5000, B2Bora_PC_Points::get_balance( 1 ), 'balance untouched' );
}

function test_second_redemption_fails_once_balance_is_depleted() {
	b2bora_test_register_user( 1 );
	B2Bora_PC_Points::record_transaction( 1, B2Bora_PC_Points::TYPE_MANUAL, 2000, array( 'description' => 'seed' ) );
	$reward_id = B2Bora_PC_Rewards::save_reward( array( 'name' => '€20 Order Credit', 'points_cost' => 2000, 'reward_type' => 'order_credit', 'reward_value' => 20 ) );

	$first  = B2Bora_PC_Redemptions::redeem( 1, $reward_id );
	$second = B2Bora_PC_Redemptions::redeem( 1, $reward_id );

	assert_true( ! is_wp_error( $first ), 'first redemption succeeds' );
	assert_true( is_wp_error( $second ), 'second redemption on the same (now empty) balance is rejected, never double-deducted' );
	assert_equal( 0, B2Bora_PC_Points::get_balance( 1 ), 'balance cannot go negative from a rapid repeat redemption' );
}

// ---------------------------------------------------------------------
// Manual adjustment.
// ---------------------------------------------------------------------
function test_manual_adjustment_requires_a_reason() {
	b2bora_test_register_user( 1 );

	$result = B2Bora_Partner_Club_Loyalty::manual_adjustment( 1, 500, '' );

	assert_true( false === $result, 'a manual adjustment without a reason is rejected' );
	assert_equal( 0, B2Bora_PC_Points::get_balance( 1 ), 'no points were added' );
}

function test_unauthorized_manual_adjustment_is_blocked_by_capability_check() {
	$source = file_get_contents( B2BORA_PC_PATH . 'includes/class-admin.php' );
	preg_match( '/function handle_manual_adjustment\(\).*?\n\t\}/s', $source, $matches );

	assert_true( ! empty( $matches ), 'handle_manual_adjustment() exists' );
	assert_true( false !== strpos( $matches[0], "verify_admin_request( 'manual_adjustment' )" ), 'manual adjustment is gated by a capability + nonce check before touching any points' );
}

// ---------------------------------------------------------------------
// AJAX surface area (Phase 15: unauthorized customer data access).
// ---------------------------------------------------------------------
function test_ajax_exposes_only_the_redeem_action() {
	$source = file_get_contents( B2BORA_PC_PATH . 'public/class-ajax.php' );

	assert_true( 1 === preg_match_all( '/add_action\(\s*[\'"]wp_ajax_/', $source ), 'exactly one wp_ajax_ handler is registered' );
	assert_true( 0 === preg_match_all( '/add_action\(\s*[\'"]wp_ajax_nopriv_/', $source ), 'no AJAX action is reachable while logged out (redemption requires an account)' );
	assert_true( false === strpos( $source, 'get_history' ) && false === strpos( $source, 'get_for_user' ), 'no AJAX action exposes another customer\'s transaction/redemption history' );
}

// ---------------------------------------------------------------------
// Database schema formatting (Phase 18: "database installation").
// dbDelta() itself requires a real WordPress + MySQL environment (see
// tests/README.md); this statically verifies the SQL strings follow
// dbDelta's well-known, easy-to-get-wrong formatting rules instead.
// ---------------------------------------------------------------------
function test_dbdelta_schema_strings_are_correctly_formatted() {
	$source = file_get_contents( B2BORA_PC_PATH . 'includes/class-database.php' );

	assert_true( (bool) preg_match_all( '/CREATE TABLE/', $source, $m ) && count( $m[0] ) === 5, 'defines exactly 5 tables' );

	// dbDelta requires exactly two spaces between "PRIMARY KEY" and the
	// column list, or it silently fails to detect/create the index.
	assert_true( 0 === preg_match( '/PRIMARY KEY {1}\(/', $source ), 'no PRIMARY KEY definition has only one space (dbDelta would ignore it)' );
	assert_true( 5 === substr_count( $source, 'PRIMARY KEY  (id)' ), 'every table declares its PRIMARY KEY with the two-space spacing dbDelta requires' );
}

// ---------------------------------------------------------------------
// Balance reconciliation (Phase 5 audit addition).
// ---------------------------------------------------------------------
function test_reconciliation_detects_and_repairs_a_corrupted_balance() {
	b2bora_test_register_user( 1 );

	B2Bora_PC_Points::record_transaction( 1, B2Bora_PC_Points::TYPE_MANUAL, 500, array( 'description' => 'row 1' ) );

	// Simulate the historical race-condition bug directly: insert a row
	// whose points are real (+300) but whose cached balance_after was
	// computed from a stale prior balance (as if two requests raced),
	// bypassing record_transaction() the way a bug or manual edit would.
	global $wpdb;
	$wpdb->insert(
		B2Bora_PC_Database::transactions_table(),
		array(
			'user_id'       => 1,
			'order_id'      => null,
			'type'          => 'order',
			'points'        => 300,
			'balance_after' => 300, // Should have been 800 (500 + 300).
			'description'   => 'corrupted row',
			'reference_key' => 'order_points_9999',
			'created_at'    => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s' )
	);

	assert_equal( 300, B2Bora_PC_Points::get_balance( 1 ), 'recorded (cached) balance reflects the corrupted row' );
	assert_equal( 800, B2Bora_PC_Points::get_recomputed_balance( 1 ), 'independently recomputed ledger balance is correct' );

	$repaired = B2Bora_PC_Points::repair_balance( 1, 'test repair' );
	assert_true( false !== $repaired, 'repair creates a transaction' );
	assert_equal( 800, B2Bora_PC_Points::get_balance( 1 ), 'recorded balance now matches the ledger total' );

	// Re-running reconciliation immediately after a repair must converge
	// (not re-flag the same historical drift forever) thanks to the
	// repair-anchored recompute.
	assert_equal( B2Bora_PC_Points::get_balance( 1 ), B2Bora_PC_Points::get_recomputed_balance( 1 ), 'reconciliation converges after repair' );

	// A second repair attempt with nothing left to fix is a safe no-op.
	assert_true( false === B2Bora_PC_Points::repair_balance( 1, 'test repair again' ), 'repairing an already-consistent balance is a no-op' );

	// New activity after the repair is still tracked correctly.
	b2bora_test_register_user( 1 );
	b2bora_make_order( 3001, 1, 50.00 );
	B2Bora_PC_Orders::handle_order_completed( 3001 );
	assert_equal( B2Bora_PC_Points::get_balance( 1 ), B2Bora_PC_Points::get_recomputed_balance( 1 ), 'reconciliation still matches after new activity post-repair' );
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

// ---------------------------------------------------------------------
// Multilang: per-language storage for admin-entered reward/level/
// mission content (JSON-in-existing-column, no schema change).
// ---------------------------------------------------------------------
function test_multilang_single_language_is_stored_as_plain_string() {
	$encoded = B2Bora_PC_Multilang::encode( array( 'en' => 'Gold Level' ) );
	assert_equal( 'Gold Level', $encoded, 'a single language is stored with no JSON overhead' );
}

function test_multilang_multiple_languages_round_trip() {
	$encoded = B2Bora_PC_Multilang::encode(
		array(
			'en' => 'Gold Level',
			'ro' => 'Nivel Auriu',
		)
	);
	assert_true( 'Gold Level' !== $encoded, 'two or more languages are stored as JSON, not a plain string' );
	assert_equal( 'Gold Level', B2Bora_PC_Multilang::decode( $encoded, 'en' ), 'decode returns the requested language' );
	assert_equal( 'Nivel Auriu', B2Bora_PC_Multilang::decode( $encoded, 'ro' ), 'decode returns the other requested language' );
}

function test_multilang_decode_falls_back_to_first_language_when_missing() {
	$encoded = B2Bora_PC_Multilang::encode(
		array(
			'en' => 'Gold Level',
			'ro' => 'Nivel Auriu',
		)
	);
	assert_equal( 'Gold Level', B2Bora_PC_Multilang::decode( $encoded, 'fr' ), 'an unsaved language falls back to the first available one' );
}

function test_multilang_decode_is_backward_compatible_with_legacy_plain_strings() {
	assert_equal( 'Gold Level', B2Bora_PC_Multilang::decode( 'Gold Level', 'ro' ), 'a legacy plain-text value is returned unchanged for any language' );
	assert_equal( '', B2Bora_PC_Multilang::decode( '' ), 'an empty value decodes to an empty string' );
}

function test_multilang_get_all_wraps_legacy_strings_under_current_language() {
	assert_equal( array( 'en' => 'Gold Level' ), B2Bora_PC_Multilang::get_all( 'Gold Level' ), 'a legacy plain-text value is wrapped under the current language when pre-filling an edit form' );
	assert_equal( array(), B2Bora_PC_Multilang::get_all( '' ), 'an empty value has no languages' );

	$encoded = B2Bora_PC_Multilang::encode(
		array(
			'en' => 'Gold Level',
			'ro' => 'Nivel Auriu',
		)
	);
	assert_equal(
		array(
			'en' => 'Gold Level',
			'ro' => 'Nivel Auriu',
		),
		B2Bora_PC_Multilang::get_all( $encoded ),
		'get_all returns every saved language for a JSON-encoded value'
	);
}

function test_multilang_sanitize_input_accepts_flat_string_or_per_language_array() {
	assert_equal( 'Gold Level', B2Bora_PC_Multilang::sanitize_input( '  Gold Level  ', 'sanitize_text_field' ), 'a flat string is run through the given sanitizer' );

	$encoded = B2Bora_PC_Multilang::sanitize_input(
		array(
			'en' => '  Gold Level  ',
			'ro' => '  Nivel Auriu  ',
		),
		'sanitize_text_field'
	);
	assert_equal( 'Gold Level', B2Bora_PC_Multilang::decode( $encoded, 'en' ), 'each array entry is sanitized before encoding (en)' );
	assert_equal( 'Nivel Auriu', B2Bora_PC_Multilang::decode( $encoded, 'ro' ), 'each array entry is sanitized before encoding (ro)' );

	$single = B2Bora_PC_Multilang::sanitize_input( array( 'en' => 'Gold Level', 'ro' => '' ), 'sanitize_text_field' );
	assert_equal( 'Gold Level', $single, 'blank per-language entries are dropped, collapsing back to a plain string when only one remains' );
}

function test_multilang_content_is_translated_through_the_dashboard() {
	b2bora_test_register_user( 1 );

	$reward_id = B2Bora_PC_Rewards::save_reward(
		array(
			'name'         => array(
				'en' => 'Free Shipping',
				'ro' => 'Transport Gratuit',
			),
			'points_cost'  => 100,
			'reward_type'  => 'order_credit',
			'reward_value' => 5,
			'active'       => 1,
		)
	);
	assert_true( false !== $reward_id, 'reward with per-language name saves successfully' );

	$reward = B2Bora_PC_Rewards::get_reward( $reward_id );
	assert_equal( 'Free Shipping', B2Bora_PC_Multilang::decode( $reward['name'], 'en' ), 'stored reward name decodes correctly for en' );
	assert_equal( 'Transport Gratuit', B2Bora_PC_Multilang::decode( $reward['name'], 'ro' ), 'stored reward name decodes correctly for ro' );
}

// ---------------------------------------------------------------------
// Reward Types: admin-manageable list backing the "Reward Type"
// dropdown, seeded once from the plugin's original three built-in
// types so pre-existing rewards keep resolving correctly.
// ---------------------------------------------------------------------
function test_reward_types_are_seeded_with_the_original_three_on_first_read() {
	$types = B2Bora_PC_Reward_Types::get_types();

	assert_equal(
		array( 'order_credit', 'free_box', 'partner_offer' ),
		array_keys( $types ),
		'a fresh install still offers exactly the original three reward types'
	);
}

function test_reward_types_can_be_added_and_are_unique() {
	$slug = B2Bora_PC_Reward_Types::add_type( 'Gift Card' );
	assert_true( false !== $slug, 'adding a new type succeeds' );

	$types = B2Bora_PC_Reward_Types::get_types();
	assert_equal( 'Gift Card', $types[ $slug ], 'the new type is stored with its label' );

	$second_slug = B2Bora_PC_Reward_Types::add_type( 'Gift Card' );
	assert_true( $slug !== $second_slug, 'adding the same label twice gets a distinct slug rather than colliding' );
}

function test_reward_types_add_rejects_an_empty_label() {
	assert_true( false === B2Bora_PC_Reward_Types::add_type( '   ' ), 'a blank label is rejected' );
}

function test_reward_types_can_be_renamed_without_changing_their_slug() {
	$slug = B2Bora_PC_Reward_Types::add_type( 'Gift Card' );

	b2bora_test_register_user( 1 );
	$reward_id = B2Bora_PC_Rewards::save_reward( array( 'name' => 'Amazon Voucher', 'points_cost' => 500, 'reward_type' => $slug ) );

	assert_true( B2Bora_PC_Reward_Types::rename_type( $slug, 'Digital Gift Card' ), 'rename succeeds' );

	$types = B2Bora_PC_Reward_Types::get_types();
	assert_equal( 'Digital Gift Card', $types[ $slug ], 'the label changed' );

	$reward = B2Bora_PC_Rewards::get_reward( $reward_id );
	assert_equal( $slug, $reward['reward_type'], 'the reward still points at the same (unchanged) slug after the rename' );
}

function test_reward_types_cannot_be_deleted_while_in_use() {
	$slug = B2Bora_PC_Reward_Types::add_type( 'Gift Card' );

	b2bora_test_register_user( 1 );
	B2Bora_PC_Rewards::save_reward( array( 'name' => 'Amazon Voucher', 'points_cost' => 500, 'reward_type' => $slug ) );

	assert_true( false === B2Bora_PC_Reward_Types::delete_type( $slug ), 'a type still used by a reward cannot be deleted' );
	assert_true( array_key_exists( $slug, B2Bora_PC_Reward_Types::get_types() ), 'the type is still there' );
}

function test_reward_types_can_be_deleted_once_unused() {
	$slug = B2Bora_PC_Reward_Types::add_type( 'Gift Card' );

	assert_true( B2Bora_PC_Reward_Types::delete_type( $slug ), 'an unused type can be deleted' );
	assert_true( ! array_key_exists( $slug, B2Bora_PC_Reward_Types::get_types() ), 'the type is gone' );
}

function test_reward_types_last_remaining_type_cannot_be_deleted() {
	$types = B2Bora_PC_Reward_Types::get_types();
	foreach ( array_keys( $types ) as $slug ) {
		if ( $slug !== array_key_last( $types ) ) {
			B2Bora_PC_Reward_Types::delete_type( $slug );
		}
	}

	$remaining = B2Bora_PC_Reward_Types::get_types();
	assert_equal( 1, count( $remaining ), 'down to exactly one type' );

	$last_slug = array_key_first( $remaining );
	assert_true( false === B2Bora_PC_Reward_Types::delete_type( $last_slug ), 'the last remaining type cannot be deleted, so the dropdown is never empty' );
}

function test_saving_a_reward_with_an_unknown_type_falls_back_to_a_real_type() {
	b2bora_test_register_user( 1 );

	$reward_id = B2Bora_PC_Rewards::save_reward( array( 'name' => 'Mystery Reward', 'points_cost' => 100, 'reward_type' => 'not_a_real_type' ) );
	assert_true( false !== $reward_id, 'save still succeeds' );

	$reward = B2Bora_PC_Rewards::get_reward( $reward_id );
	assert_true( array_key_exists( $reward['reward_type'], B2Bora_PC_Reward_Types::get_types() ), 'an unrecognised type falls back to one that actually exists' );
}
