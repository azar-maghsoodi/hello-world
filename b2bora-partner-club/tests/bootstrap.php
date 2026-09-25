<?php
/**
 * Minimal test bootstrap: stubs the small slice of WordPress/WooCommerce
 * APIs the plugin's classes call, backed by an in-memory SQLite database
 * for the custom tables. This is NOT a replacement for running the plugin
 * against a real WordPress + MySQL install (see tests/README.md), but it
 * lets the plugin's actual business logic - the ledger, idempotency,
 * levels, redemptions, missions, refund proration - be executed and
 * asserted against automatically, without any network access or WP core
 * checkout.
 */

error_reporting( E_ALL & ~E_DEPRECATED );

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'ARRAY_A', 'ARRAY_A' );

// ---------------------------------------------------------------------
// In-memory state used by the stubs below.
// ---------------------------------------------------------------------
$GLOBALS['b2bora_test_options']     = array();
$GLOBALS['b2bora_test_transients']  = array();
$GLOBALS['b2bora_test_users']       = array();
$GLOBALS['b2bora_test_current_uid'] = 0;
$GLOBALS['b2bora_test_actions']     = array();

// ---------------------------------------------------------------------
// WP_Error
// ---------------------------------------------------------------------
class WP_Error {
	public $code;
	public $message;

	public function __construct( $code = '', $message = '' ) {
		$this->code    = $code;
		$this->message = $message;
	}

	public function get_error_message() {
		return $this->message;
	}

	public function get_error_code() {
		return $this->code;
	}
}

function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}

// ---------------------------------------------------------------------
// Sanitisation / escaping stand-ins (good enough for CLI test purposes;
// production code still runs against real WordPress implementations).
// ---------------------------------------------------------------------
function absint( $n ) { return abs( (int) $n ); }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) ); }
function sanitize_email( $s ) { return filter_var( trim( (string) $s ), FILTER_SANITIZE_EMAIL ); }
function wp_kses_post( $s ) { return (string) $s; }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_html__( $s, $d = null ) { return $s; }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_url( $s ) { return $s; }
function esc_js( $s ) { return $s; }
function esc_textarea( $s ) { return $s; }
function wp_unslash( $s ) { return $s; }
function wc_clean( $s ) { return is_array( $s ) ? array_map( 'sanitize_text_field', $s ) : sanitize_text_field( $s ); }

// ---------------------------------------------------------------------
// i18n stand-ins.
// ---------------------------------------------------------------------
function __( $s, $d = null ) { return $s; }
function _e( $s, $d = null ) { echo $s; }
function esc_html_e( $s, $d = null ) { echo esc_html( $s ); }
function load_plugin_textdomain( ...$args ) { return true; }

// ---------------------------------------------------------------------
// Hooks (simplified: filters are ignored -- tests don't register any --
// and actions are recorded so tests can assert one fired).
// ---------------------------------------------------------------------
function add_action( ...$args ) { return true; }
function add_filter( ...$args ) { return true; }
function apply_filters( $tag, $value ) { return $value; }
function do_action( $tag, ...$args ) {
	$GLOBALS['b2bora_test_actions'][ $tag ][] = $args;
}
function did_action_args( $tag ) {
	return isset( $GLOBALS['b2bora_test_actions'][ $tag ] ) ? $GLOBALS['b2bora_test_actions'][ $tag ] : array();
}

// ---------------------------------------------------------------------
// Options / transients (plain in-memory arrays).
// ---------------------------------------------------------------------
function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['b2bora_test_options'] ) ? $GLOBALS['b2bora_test_options'][ $key ] : $default;
}
function update_option( $key, $value ) {
	$GLOBALS['b2bora_test_options'][ $key ] = $value;
	return true;
}
function delete_option( $key ) {
	unset( $GLOBALS['b2bora_test_options'][ $key ] );
	return true;
}
function wp_parse_args( $args, $defaults ) {
	return array_merge( $defaults, (array) $args );
}
function get_transient( $key ) {
	$row = $GLOBALS['b2bora_test_transients'][ $key ] ?? null;
	if ( ! $row ) {
		return false;
	}
	if ( $row['expires'] > 0 && $row['expires'] < time() ) {
		unset( $GLOBALS['b2bora_test_transients'][ $key ] );
		return false;
	}
	return $row['value'];
}
function set_transient( $key, $value, $ttl = 0 ) {
	$GLOBALS['b2bora_test_transients'][ $key ] = array( 'value' => $value, 'expires' => $ttl > 0 ? time() + $ttl : 0 );
	return true;
}
function delete_transient( $key ) {
	unset( $GLOBALS['b2bora_test_transients'][ $key ] );
	return true;
}

// ---------------------------------------------------------------------
// Users.
// ---------------------------------------------------------------------
function b2bora_test_register_user( $id, $email = '', $name = '' ) {
	$GLOBALS['b2bora_test_users'][ $id ] = (object) array(
		'ID'           => $id,
		'user_email'   => $email ?: "user{$id}@example.com",
		'display_name' => $name ?: "User {$id}",
	);
}
function get_userdata( $id ) {
	return $GLOBALS['b2bora_test_users'][ (int) $id ] ?? false;
}
function is_user_logged_in() { return $GLOBALS['b2bora_test_current_uid'] > 0; }
function get_current_user_id() { return $GLOBALS['b2bora_test_current_uid']; }
function current_user_can( $cap ) { return true; } // Capability system itself is WP core; not re-tested here.

// ---------------------------------------------------------------------
// Time.
// ---------------------------------------------------------------------
$GLOBALS['b2bora_test_now'] = time();
function current_time( $type = 'mysql' ) {
	$now = $GLOBALS['b2bora_test_now'];
	return 'timestamp' === $type ? $now : ( 'Y-m-d' === $type ? gmdate( 'Y-m-d', $now ) : gmdate( 'Y-m-d H:i:s', $now ) );
}

// ---------------------------------------------------------------------
// A tiny wpdb-compatible shim backed by SQLite (via PDO), so real SQL
// written against MySQL syntax in the plugin runs unmodified except for
// stripping the one MySQL-only clause used ("FOR UPDATE"), which SQLite
// does not need in a single-threaded test process.
// ---------------------------------------------------------------------
class B2Bora_Test_WPDB {
	public $prefix = 'wp_';
	public $insert_id = 0;
	public $last_error = '';

	/** @var PDO */
	private $pdo;

	public function __construct() {
		$this->pdo = new PDO( 'sqlite::memory:' );
		$this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	}

	public function get_charset_collate() { return ''; }

	public function query( $sql ) {
		$sql = trim( $sql );
		if ( in_array( strtoupper( $sql ), array( 'START TRANSACTION', 'COMMIT', 'ROLLBACK' ), true ) ) {
			// SQLite transactions aren't exercised in this single-process
			// synchronous test run; treat as a no-op boundary.
			return true;
		}

		try {
			return $this->pdo->exec( $this->translate( $sql ) );
		} catch ( PDOException $e ) {
			$this->last_error = $e->getMessage();
			return false;
		}
	}

	private function translate( $sql ) {
		return preg_replace( '/\s+FOR UPDATE\s*$/i', '', $sql );
	}

	public function prepare( $query, ...$args ) {
		if ( 1 === count( $args ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		$pdo = $this->pdo;
		$i   = 0;

		$sql = preg_replace_callback(
			'/%[dsfi]/',
			function ( $m ) use ( &$i, $args, $pdo ) {
				$value = $args[ $i++ ] ?? null;
				switch ( $m[0] ) {
					case '%d':
						return (string) (int) $value;
					case '%f':
						return (string) (float) $value;
					case '%i':
						return (string) $value; // Identifiers: trusted constant table names only.
					default:
						if ( null === $value ) {
							return 'NULL';
						}
						return $pdo->quote( (string) $value );
				}
			},
			$query
		);

		return $sql;
	}

	public function get_var( $sql ) {
		$stmt = $this->pdo->query( $this->translate( $sql ) );
		$row  = $stmt ? $stmt->fetch( PDO::FETCH_NUM ) : false;
		return $row ? $row[0] : null;
	}

	public function get_row( $sql, $output = ARRAY_A ) {
		$stmt = $this->pdo->query( $this->translate( $sql ) );
		$row  = $stmt ? $stmt->fetch( PDO::FETCH_ASSOC ) : false;
		return $row ?: null;
	}

	public function get_results( $sql, $output = ARRAY_A ) {
		$stmt = $this->pdo->query( $this->translate( $sql ) );
		return $stmt ? $stmt->fetchAll( PDO::FETCH_ASSOC ) : array();
	}

	public function insert( $table, $data, $format = null ) {
		$columns = array_keys( $data );
		$sql     = sprintf(
			'INSERT INTO %s (%s) VALUES (%s)',
			$table,
			implode( ', ', $columns ),
			implode( ', ', array_fill( 0, count( $columns ), '?' ) )
		);

		try {
			$stmt = $this->pdo->prepare( $sql );
			$stmt->execute( array_values( $data ) );
			$this->insert_id = (int) $this->pdo->lastInsertId();
			return true;
		} catch ( PDOException $e ) {
			$this->last_error = $e->getMessage();
			return false;
		}
	}

	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		$set   = implode( ', ', array_map( fn( $c ) => "{$c} = ?", array_keys( $data ) ) );
		$cond  = implode( ' AND ', array_map( fn( $c ) => "{$c} = ?", array_keys( $where ) ) );
		$sql   = "UPDATE {$table} SET {$set} WHERE {$cond}";

		try {
			$stmt = $this->pdo->prepare( $sql );
			$stmt->execute( array_merge( array_values( $data ), array_values( $where ) ) );
			return $stmt->rowCount();
		} catch ( PDOException $e ) {
			$this->last_error = $e->getMessage();
			return false;
		}
	}

	public function delete( $table, $where, $where_format = null ) {
		$cond = implode( ' AND ', array_map( fn( $c ) => "{$c} = ?", array_keys( $where ) ) );
		$sql  = "DELETE FROM {$table} WHERE {$cond}";

		try {
			$stmt = $this->pdo->prepare( $sql );
			$stmt->execute( array_values( $where ) );
			return $stmt->rowCount();
		} catch ( PDOException $e ) {
			$this->last_error = $e->getMessage();
			return false;
		}
	}

	public function create_test_schema() {
		$this->pdo->exec( 'CREATE TABLE wp_b2bora_points_transactions (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			user_id INTEGER NOT NULL,
			order_id INTEGER NULL,
			type TEXT NOT NULL,
			points INTEGER NOT NULL,
			balance_after INTEGER NOT NULL,
			description TEXT,
			reference_key TEXT UNIQUE,
			created_at TEXT NOT NULL
		)' );

		$this->pdo->exec( 'CREATE TABLE wp_b2bora_rewards (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			name TEXT NOT NULL,
			description TEXT,
			points_cost INTEGER NOT NULL DEFAULT 0,
			reward_type TEXT NOT NULL DEFAULT "order_credit",
			reward_value REAL NOT NULL DEFAULT 0,
			active INTEGER NOT NULL DEFAULT 1,
			sort_order INTEGER NOT NULL DEFAULT 0,
			created_at TEXT,
			updated_at TEXT
		)' );

		$this->pdo->exec( 'CREATE TABLE wp_b2bora_redemptions (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			user_id INTEGER NOT NULL,
			reward_id INTEGER NOT NULL,
			points_spent INTEGER NOT NULL DEFAULT 0,
			status TEXT NOT NULL DEFAULT "pending",
			created_at TEXT,
			approved_at TEXT,
			used_at TEXT
		)' );

		$this->pdo->exec( 'CREATE TABLE wp_b2bora_levels (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			name TEXT NOT NULL,
			minimum_points INTEGER NOT NULL DEFAULT 0,
			benefits TEXT,
			sort_order INTEGER NOT NULL DEFAULT 0,
			active INTEGER NOT NULL DEFAULT 1
		)' );

		$this->pdo->exec( 'CREATE TABLE wp_b2bora_missions (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			name TEXT NOT NULL,
			description TEXT,
			type TEXT NOT NULL,
			target INTEGER NOT NULL DEFAULT 0,
			bonus_points INTEGER NOT NULL DEFAULT 0,
			start_date TEXT,
			end_date TEXT,
			active INTEGER NOT NULL DEFAULT 1,
			created_at TEXT,
			updated_at TEXT
		)' );
	}

	public function reset() {
		foreach ( array( 'wp_b2bora_points_transactions', 'wp_b2bora_rewards', 'wp_b2bora_redemptions', 'wp_b2bora_levels', 'wp_b2bora_missions' ) as $table ) {
			$this->pdo->exec( "DELETE FROM {$table}" );
		}
	}
}

$GLOBALS['wpdb'] = new B2Bora_Test_WPDB();
$GLOBALS['wpdb']->create_test_schema();

// ---------------------------------------------------------------------
// Minimal WooCommerce order/refund stand-ins used by tests.
// ---------------------------------------------------------------------
class B2Bora_Test_Product {
	private $id;
	public function __construct( $id ) { $this->id = $id; }
	public function get_id() { return $this->id; }
}

class B2Bora_Test_Order_Item {
	private $product;
	private $qty;
	public function __construct( $product, $qty = 1 ) { $this->product = $product; $this->qty = $qty; }
	public function get_product() { return $this->product; }
	public function get_quantity() { return $this->qty; }
}

class WC_Abstract_Order {}

class WC_Order extends WC_Abstract_Order {
	public $id;
	public $customer_id;
	public $total = 0.0;
	public $total_tax = 0.0;
	public $shipping_total = 0.0;
	public $shipping_tax = 0.0;
	public $total_refunded = 0.0;
	public $currency = 'EUR';
	public $meta = array();
	public $items = array();

	public function __construct( $id, $customer_id ) {
		$this->id          = $id;
		$this->customer_id = $customer_id;
	}

	public function get_id() { return $this->id; }
	public function get_customer_id() { return $this->customer_id; }
	public function get_total() { return $this->total; }
	public function get_total_tax() { return $this->total_tax; }
	public function get_shipping_total() { return $this->shipping_total; }
	public function get_shipping_tax() { return $this->shipping_tax; }
	public function get_total_refunded() { return $this->total_refunded; }
	public function get_currency() { return $this->currency; }
	public function get_order_number() { return (string) $this->id; }
	public function get_meta( $key ) { return $this->meta[ $key ] ?? ''; }
	public function get_items() { return $this->items; }
}

class WC_Order_Refund extends WC_Abstract_Order {
	public $id;
	public $amount;
	public function __construct( $id, $amount ) { $this->id = $id; $this->amount = $amount; }
	public function get_id() { return $this->id; }
	public function get_amount() { return $this->amount; }
}

$GLOBALS['b2bora_test_orders'] = array();
function b2bora_test_register_order( WC_Abstract_Order $order ) {
	$GLOBALS['b2bora_test_orders'][ $order->get_id() ] = $order;
}
function wc_get_order( $id ) {
	return $GLOBALS['b2bora_test_orders'][ (int) $id ] ?? false;
}
function taxonomy_exists( $tax ) { return false; }
function get_the_terms( $post_id, $tax ) { return false; }

// ---------------------------------------------------------------------
// Load the plugin's classes directly (skip the main plugin file, which
// calls register_activation_hook()/plugins_loaded wiring we don't need
// for these unit tests).
// ---------------------------------------------------------------------
define( 'B2BORA_PC_PATH', dirname( __DIR__ ) . '/' );
define( 'B2BORA_PC_URL', 'https://example.test/wp-content/plugins/b2bora-partner-club/' );
define( 'B2BORA_PC_VERSION', 'test' );

require_once B2BORA_PC_PATH . 'includes/class-security.php';
require_once B2BORA_PC_PATH . 'includes/class-logger.php';
require_once B2BORA_PC_PATH . 'includes/class-settings.php';
require_once B2BORA_PC_PATH . 'includes/class-database.php';
require_once B2BORA_PC_PATH . 'includes/class-points.php';
require_once B2BORA_PC_PATH . 'includes/class-levels.php';
require_once B2BORA_PC_PATH . 'includes/class-rewards.php';
require_once B2BORA_PC_PATH . 'includes/class-redemptions.php';
require_once B2BORA_PC_PATH . 'includes/class-missions.php';
require_once B2BORA_PC_PATH . 'includes/class-orders.php';
require_once B2BORA_PC_PATH . 'includes/class-loyalty-api.php';

/**
 * Reset all in-memory/SQLite state between tests so they are independent.
 */
function b2bora_test_reset_state() {
	$GLOBALS['wpdb']->reset();
	$GLOBALS['b2bora_test_options']     = array();
	$GLOBALS['b2bora_test_transients']  = array();
	$GLOBALS['b2bora_test_users']       = array();
	$GLOBALS['b2bora_test_orders']      = array();
	$GLOBALS['b2bora_test_actions']     = array();
	$GLOBALS['b2bora_test_now']         = time();
	$GLOBALS['b2bora_test_current_uid'] = 0;

	// Force B2Bora_PC_Settings to re-read defaults (it caches statically).
	$ref = new ReflectionProperty( 'B2Bora_PC_Settings', 'cache' );
	$ref->setAccessible( true );
	$ref->setValue( null, null );

	B2Bora_PC_Levels::save_level( array( 'name' => 'Starter', 'minimum_points' => 0 ) );
	B2Bora_PC_Levels::save_level( array( 'name' => 'Partner', 'minimum_points' => 2000 ) );
	B2Bora_PC_Levels::save_level( array( 'name' => 'Premium', 'minimum_points' => 5000 ) );
	B2Bora_PC_Levels::save_level( array( 'name' => 'Preferred', 'minimum_points' => 10000 ) );
}
