# B2Bora Partner Club - Test Suite

Run with:

```
php tests/run.php
```

No Composer/PHPUnit install is required. `bootstrap.php` stubs the slice of
WordPress/WooCommerce functions the plugin's classes call, backed by an
in-memory SQLite database standing in for the plugin's custom tables. This
lets the plugin's actual business logic run and be asserted against without
a full WordPress + MySQL environment.

## Coverage vs. the 16 scenarios in the spec

Directly executed against real logic (1-11, 13):

1. First order awards correct points
2. Same order cannot award twice
3. Welcome bonus awarded only once
4. Reorder bonus works (and does not fire outside the window)
5. Reorder bonus cannot duplicate
6. Reward redemption deducts correct points
7. Cannot redeem without enough points
8. Refund reverses points
9. Partial refund reverses correct amount (plus: a refund cannot be reversed twice)
10. Level calculation works
11. Mission cannot reward twice
13. Invalid user IDs are rejected (plus: negative balances are never allowed)

Verified as static/structural safeguards rather than executed end-to-end (12, 15, 16),
because they depend on WordPress's real nonce/capability system or on
actually removing WooCommerce/Polylang from a running site, which this
lightweight harness does not attempt to simulate:

12. Unauthorized users cannot modify points - asserts the AJAX handler
    only ever acts on `get_current_user_id()`, never a client-supplied
    `user_id`, and always verifies a nonce first.
16. Plugin does not fatal-error if Polylang is absent - asserts every
    `pll_*` call in the codebase is guarded by `function_exists()`.
15. Plugin does not fatal-error if WooCommerce is inactive - asserts
    `class-plugin.php` gates all WooCommerce-dependent wiring behind
    `class_exists( 'WooCommerce' )` and that `class-orders.php` guards its
    own use of `wc_get_order()`.

Not covered by this harness (14. "Database tables install correctly", and
"database migration"): `dbDelta()` and the real
`wp-admin/includes/upgrade.php` require a genuine WordPress + MySQL/MariaDB
install to execute meaningfully; this harness uses a hand-written SQLite
mirror of the same columns so the rest of the suite can run.
`test_dbdelta_schema_strings_are_correctly_formatted` statically checks the
CREATE TABLE strings for dbDelta's well-known, easy-to-get-wrong formatting
rules (the two-space `PRIMARY KEY  (id)`), but this is not a substitute for
actually running `dbDelta()`. **Before going live, activate the plugin on a
real WordPress + WooCommerce staging site and confirm the five
`wp_b2bora_*` tables are created with `SHOW TABLES` / `DESCRIBE`, then bump
`B2BORA_PC_DB_VERSION` and reload once to confirm `maybe_upgrade()` re-runs
installation without error or data loss.**

## Additional coverage added during the staging-readiness audit

- Reorder-bonus window boundaries (29 / exactly 30 / 31 days).
- Multiple partial refunds on one order, capped at the order's original
  points (never over-reversed even if refunds exceed the order total).
- Redeeming a non-existent reward id, and a second redemption failing once
  the balance a first redemption just spent is gone (no double-deduction).
- A manual adjustment with an empty reason is rejected outright, and
  `class-admin.php`'s handler is confirmed (by source inspection) to run
  its capability + nonce check before ever calling it.
- The AJAX surface is confirmed (by source inspection) to register exactly
  one action, with no `nopriv` variant, and to never expose another
  customer's transaction or redemption history.
- A balance intentionally corrupted the way the pre-audit race condition
  could have caused (see class-points.php's `GET_LOCK` hardening) is
  detected by the independent recompute and correctly repaired, with the
  repair converging (not re-flagging the same historical drift forever on
  the very next run).

Genuinely NOT VERIFIED by any automated test here, because it requires a
real WordPress + WooCommerce + B2B Cart to Order install with a browser:
end-to-end confirmation that a real B2B order, taken through the actual
plugin's send-order / staff-marks-Completed flow, awards points exactly
once. See the main report for the manual staging test script.
