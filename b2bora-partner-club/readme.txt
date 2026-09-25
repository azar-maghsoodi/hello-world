=== B2Bora Partner Club ===
Contributors: b2bora
Tags: woocommerce, b2b, loyalty, rewards, wholesale
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A B2B loyalty and rewards program for B2Bora wholesale partners: points on confirmed orders, partner levels, missions and reward redemptions.

== Description ==

B2Bora Partner Club is an independent loyalty layer for the B2Bora wholesale FMCG site. It does not add or change any checkout flow. Points are only ever awarded when an order is explicitly confirmed/completed by the existing B2Bora order workflow (via the `b2bora_order_completed` action), never simply because a WooCommerce order object was created.

Features:

* Configurable points-per-currency-unit earning rate, with tax/shipping/refund exclusions.
* Append-only points ledger (custom database table) with idempotent, duplicate-proof awarding.
* One-time welcome bonus and time-windowed reorder bonus.
* Admin-configurable partner levels based on lifetime or current points.
* Simple, extensible missions engine (first order, order count, brand variety, pallet order).
* Reward catalogue and an internal redemption workflow (no auto-generated coupons in V1).
* Full admin UI: dashboard, customers, transactions, rewards, levels, missions, settings, tools.
* Responsive `[b2bora_partner_club]` customer dashboard shortcode.
* Works safely with WooCommerce or Polylang absent (shows a notice, never fatals).

== Installation ==

1. Upload the `b2bora-partner-club` folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Visit "Partner Club" in the admin menu to review Settings, default Levels and the default Reward.
4. Add the `[b2bora_partner_club]` shortcode to a page (e.g. an account/dashboard page) so customers can see their points.
5. Wire up order confirmation: see "Integration" below.

== Integration ==

The plugin never assumes a created WooCommerce order should earn points. The B2Bora order/cart plugin (or whichever code marks an order as confirmed) must call:

`do_action( 'b2bora_order_completed', $order_id );`

at the exact point an order becomes confirmed. Equivalently, it can call:

`B2Bora_Partner_Club_Loyalty::award_order_points( $order_id );`

Both are idempotent and safe to call more than once for the same order.

== Changelog ==

= 1.0.0 =
* Initial release.
