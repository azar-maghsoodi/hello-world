<?php
/**
 * Admin menu registration and admin-post.php form handlers. All handlers
 * verify capability + nonce via B2Bora_PC_Security before touching data.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2Bora_PC_Admin
 */
class B2Bora_PC_Admin {

	const MENU_SLUG = 'b2bora-pc-dashboard';

	/**
	 * Register WordPress hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

		$actions = array(
			'save_settings',
			'save_reward',
			'delete_reward',
			'save_level',
			'delete_level',
			'save_mission',
			'delete_mission',
			'manual_adjustment',
			'approve_redemption',
			'use_redemption',
			'cancel_redemption',
			'reinstall_tables',
		);

		foreach ( $actions as $action ) {
			add_action( 'admin_post_b2bora_pc_' . $action, array( __CLASS__, 'handle_' . $action ) );
		}
	}

	/**
	 * Only load the plugin's small admin stylesheet on its own screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( false === strpos( (string) $hook, self::MENU_SLUG ) && false === strpos( (string) $hook, 'b2bora-pc-' ) ) {
			return;
		}

		wp_enqueue_style( 'b2bora-pc-admin', B2BORA_PC_URL . 'assets/css/partner-club.css', array(), B2BORA_PC_VERSION );
	}

	/**
	 * Register the top-level menu and submenus.
	 */
	public static function register_menu() {
		$capability = apply_filters( 'b2bora_pc_manage_capability', B2Bora_PC_Security::CAP_MANAGE );

		add_menu_page(
			__( 'B2Bora Partner Club', 'b2bora-partner-club' ),
			__( 'Partner Club', 'b2bora-partner-club' ),
			$capability,
			self::MENU_SLUG,
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-awards',
			56
		);

		$submenus = array(
			self::MENU_SLUG        => array( __( 'Dashboard', 'b2bora-partner-club' ), 'render_dashboard' ),
			'b2bora-pc-customers'   => array( __( 'Customers', 'b2bora-partner-club' ), 'render_customers' ),
			'b2bora-pc-transactions' => array( __( 'Transactions', 'b2bora-partner-club' ), 'render_transactions' ),
			'b2bora-pc-rewards'     => array( __( 'Rewards', 'b2bora-partner-club' ), 'render_rewards' ),
			'b2bora-pc-levels'      => array( __( 'Levels', 'b2bora-partner-club' ), 'render_levels' ),
			'b2bora-pc-missions'    => array( __( 'Missions', 'b2bora-partner-club' ), 'render_missions' ),
			'b2bora-pc-settings'    => array( __( 'Settings', 'b2bora-partner-club' ), 'render_settings' ),
			'b2bora-pc-tools'       => array( __( 'Tools', 'b2bora-partner-club' ), 'render_tools' ),
		);

		foreach ( $submenus as $slug => $item ) {
			add_submenu_page( self::MENU_SLUG, $item[0], $item[0], $capability, $slug, array( __CLASS__, $item[1] ) );
		}

		// Hidden screen (not in the menu) for the customer detail view.
		add_submenu_page( null, __( 'Customer Detail', 'b2bora-partner-club' ), '', $capability, 'b2bora-pc-customer', array( __CLASS__, 'render_customer_detail' ) );
	}

	/**
	 * Guard every render_* method with a capability check as defence in
	 * depth (WordPress already enforces this for menu pages, but the
	 * pages can theoretically be reached directly).
	 */
	private static function guard() {
		if ( ! B2Bora_PC_Security::current_user_can_manage() ) {
			wp_die( esc_html__( 'You are not allowed to access this page.', 'b2bora-partner-club' ), 403 );
		}
	}

	// ---------------------------------------------------------------
	// Render methods - each simply includes its view template.
	// ---------------------------------------------------------------

	/** Render the Dashboard screen. */
	public static function render_dashboard() {
		self::guard();
		require B2BORA_PC_PATH . 'admin/views/dashboard.php';
	}

	/** Render the Customers list screen. */
	public static function render_customers() {
		self::guard();
		require B2BORA_PC_PATH . 'admin/views/customers.php';
	}

	/** Render a single customer's detail screen. */
	public static function render_customer_detail() {
		self::guard();
		require B2BORA_PC_PATH . 'admin/views/customer.php';
	}

	/** Render the Transactions screen. */
	public static function render_transactions() {
		self::guard();
		require B2BORA_PC_PATH . 'admin/views/transactions.php';
	}

	/** Render the Rewards management screen. */
	public static function render_rewards() {
		self::guard();
		require B2BORA_PC_PATH . 'admin/views/rewards.php';
	}

	/** Render the Levels management screen. */
	public static function render_levels() {
		self::guard();
		require B2BORA_PC_PATH . 'admin/views/levels.php';
	}

	/** Render the Missions management screen. */
	public static function render_missions() {
		self::guard();
		require B2BORA_PC_PATH . 'admin/views/missions.php';
	}

	/** Render the Settings screen. */
	public static function render_settings() {
		self::guard();
		require B2BORA_PC_PATH . 'admin/views/settings.php';
	}

	/** Render the Tools screen. */
	public static function render_tools() {
		self::guard();
		require B2BORA_PC_PATH . 'admin/views/tools.php';
	}

	// ---------------------------------------------------------------
	// Form handlers (admin-post.php).
	// ---------------------------------------------------------------

	/**
	 * Redirect back to an admin screen with a status query arg, used after
	 * every handler so the page can show a success/error notice.
	 *
	 * @param string $page   Submenu slug.
	 * @param array  $extra  Extra query args.
	 */
	private static function redirect( $page, array $extra = array() ) {
		$url = add_query_arg( array_merge( array( 'page' => $page ), $extra ), admin_url( 'admin.php' ) );
		wp_safe_redirect( $url );
		exit;
	}

	/** Handle Settings form submission. */
	public static function handle_save_settings() {
		B2Bora_PC_Security::verify_admin_request( 'settings' );

		$input = isset( $_POST['b2bora_pc'] ) ? (array) wp_unslash( $_POST['b2bora_pc'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		B2Bora_PC_Settings::update( B2Bora_PC_Settings::sanitize( $input ) );

		self::redirect( 'b2bora-pc-settings', array( 'updated' => 1 ) );
	}

	/** Handle create/update of a reward. */
	public static function handle_save_reward() {
		B2Bora_PC_Security::verify_admin_request( 'reward' );

		$data = array(
			'id'           => isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0,
			'name'         => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'description'  => isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '',
			'points_cost'  => isset( $_POST['points_cost'] ) ? absint( $_POST['points_cost'] ) : 0,
			'reward_type'  => isset( $_POST['reward_type'] ) ? sanitize_key( $_POST['reward_type'] ) : '',
			'reward_value' => isset( $_POST['reward_value'] ) ? (float) $_POST['reward_value'] : 0,
			'active'       => isset( $_POST['active'] ) ? 1 : 0,
			'sort_order'   => isset( $_POST['sort_order'] ) ? absint( $_POST['sort_order'] ) : 0,
		);

		B2Bora_PC_Rewards::save_reward( $data );

		self::redirect( 'b2bora-pc-rewards', array( 'updated' => 1 ) );
	}

	/** Handle reward deletion. */
	public static function handle_delete_reward() {
		B2Bora_PC_Security::verify_admin_request( 'reward' );

		if ( isset( $_POST['id'] ) ) {
			B2Bora_PC_Rewards::delete_reward( absint( $_POST['id'] ) );
		}

		self::redirect( 'b2bora-pc-rewards', array( 'deleted' => 1 ) );
	}

	/** Handle create/update of a level. */
	public static function handle_save_level() {
		B2Bora_PC_Security::verify_admin_request( 'level' );

		$data = array(
			'id'             => isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0,
			'name'           => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'minimum_points' => isset( $_POST['minimum_points'] ) ? absint( $_POST['minimum_points'] ) : 0,
			'benefits'       => isset( $_POST['benefits'] ) ? wp_kses_post( wp_unslash( $_POST['benefits'] ) ) : '',
			'sort_order'     => isset( $_POST['sort_order'] ) ? absint( $_POST['sort_order'] ) : 0,
			'active'         => isset( $_POST['active'] ) ? 1 : 0,
		);

		B2Bora_PC_Levels::save_level( $data );

		self::redirect( 'b2bora-pc-levels', array( 'updated' => 1 ) );
	}

	/** Handle level deletion. */
	public static function handle_delete_level() {
		B2Bora_PC_Security::verify_admin_request( 'level' );

		if ( isset( $_POST['id'] ) ) {
			B2Bora_PC_Levels::delete_level( absint( $_POST['id'] ) );
		}

		self::redirect( 'b2bora-pc-levels', array( 'deleted' => 1 ) );
	}

	/** Handle create/update of a mission. */
	public static function handle_save_mission() {
		B2Bora_PC_Security::verify_admin_request( 'mission' );

		$data = array(
			'id'           => isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0,
			'name'         => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'description'  => isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '',
			'type'         => isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : '',
			'target'       => isset( $_POST['target'] ) ? absint( $_POST['target'] ) : 0,
			'bonus_points' => isset( $_POST['bonus_points'] ) ? absint( $_POST['bonus_points'] ) : 0,
			'start_date'   => isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '',
			'end_date'     => isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '',
			'active'       => isset( $_POST['active'] ) ? 1 : 0,
		);

		B2Bora_PC_Missions::save_mission( $data );

		self::redirect( 'b2bora-pc-missions', array( 'updated' => 1 ) );
	}

	/** Handle mission deletion. */
	public static function handle_delete_mission() {
		B2Bora_PC_Security::verify_admin_request( 'mission' );

		if ( isset( $_POST['id'] ) ) {
			B2Bora_PC_Missions::delete_mission( absint( $_POST['id'] ) );
		}

		self::redirect( 'b2bora-pc-missions', array( 'deleted' => 1 ) );
	}

	/** Handle a manual point adjustment from a customer's detail screen. */
	public static function handle_manual_adjustment() {
		B2Bora_PC_Security::verify_admin_request( 'manual_adjustment' );

		$user_id = B2Bora_PC_Security::sanitize_user_id( isset( $_POST['user_id'] ) ? $_POST['user_id'] : 0 );
		$points  = isset( $_POST['points'] ) ? (int) $_POST['points'] : 0;
		$reason  = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';

		if ( $user_id && 0 !== $points && '' !== $reason ) {
			B2Bora_Partner_Club_Loyalty::manual_adjustment( $user_id, $points, $reason );
		}

		self::redirect( 'b2bora-pc-customer', array( 'user_id' => $user_id, 'updated' => 1 ) );
	}

	/** Approve a pending redemption. */
	public static function handle_approve_redemption() {
		B2Bora_PC_Security::verify_admin_request( 'redemption' );

		if ( isset( $_POST['id'] ) ) {
			B2Bora_PC_Redemptions::approve( absint( $_POST['id'] ) );
		}

		self::redirect( 'b2bora-pc-rewards', array( 'tab' => 'redemptions', 'updated' => 1 ) );
	}

	/** Mark a redemption as used/fulfilled. */
	public static function handle_use_redemption() {
		B2Bora_PC_Security::verify_admin_request( 'redemption' );

		if ( isset( $_POST['id'] ) ) {
			B2Bora_PC_Redemptions::mark_used( absint( $_POST['id'] ) );
		}

		self::redirect( 'b2bora-pc-rewards', array( 'tab' => 'redemptions', 'updated' => 1 ) );
	}

	/** Cancel a redemption and refund its points. */
	public static function handle_cancel_redemption() {
		B2Bora_PC_Security::verify_admin_request( 'redemption' );

		if ( isset( $_POST['id'] ) ) {
			B2Bora_PC_Redemptions::cancel( absint( $_POST['id'] ) );
		}

		self::redirect( 'b2bora-pc-rewards', array( 'tab' => 'redemptions', 'updated' => 1 ) );
	}

	/** Re-run dbDelta() installation from the Tools screen (safe/idempotent). */
	public static function handle_reinstall_tables() {
		B2Bora_PC_Security::verify_admin_request( 'tools' );

		B2Bora_PC_Database::install();

		self::redirect( 'b2bora-pc-tools', array( 'updated' => 1 ) );
	}
}
