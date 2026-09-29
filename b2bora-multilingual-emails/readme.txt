=== B2Bora Multilingual Emails ===
Requires PHP: 8.2 | Requires WooCommerce | Works with Polylang (free); degrades to English without it.

Sends the EXISTING customer emails (WooCommerce + WordPress core) in the customer's saved language. It never sends an email itself.

== Public API (for Partner Club or any other plugin) ==
b2bora_get_user_language( $user_id )          -> 'en'|'ro'   saved _b2bora_language > user locale > current visitor (if same user) > 'en'
b2bora_get_order_language( $order )           -> 'en'|'ro'   order _b2bora_language > customer's language > 'en'
b2bora_get_email_language( $user_id, $order_id ) -> 'en'|'ro' order wins over user
b2bora_switch_email_locale( $language )       -> token       temporary switch_to_locale(); nesting-safe
b2bora_restore_email_locale( $token )                        ALWAYS call this with the token
b2bora_translate_string( $string, $language ) -> string      Polylang String Translations lookup
Filters: b2bora_ml_supported_languages, b2bora_ml_locale, b2bora_ml_strings.

Example (future Partner Club email):
  $token = b2bora_switch_email_locale( b2bora_get_email_language( $user_id ) );
  wp_mail( $to, b2bora_translate_string( 'Your points have been updated.' ), $body );
  b2bora_restore_email_locale( $token );

== Debug ==
define( 'B2BORA_ML_EMAIL_DEBUG', true ); or Settings > B2Bora Multilingual Emails. Logs to the PHP error log. Never logs passwords, tokens or addresses.
