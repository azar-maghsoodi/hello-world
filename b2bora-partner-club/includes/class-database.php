<?php
/**
 * Database schema installation, upgrades and table name helpers.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2Bora_PC_Database
 */
class B2Bora_PC_Database {

	const DB_VERSION_OPTION = 'b2bora_pc_db_version';

	/**
	 * Full table name for the points ledger.
	 *
	 * @return string
	 */
	public static function transactions_table() {
		global $wpdb;
		return $wpdb->prefix . 'b2bora_points_transactions';
	}

	/**
	 * Full table name for rewards catalogue.
	 *
	 * @return string
	 */
	public static function rewards_table() {
		global $wpdb;
		return $wpdb->prefix . 'b2bora_rewards';
	}

	/**
	 * Full table name for reward redemptions.
	 *
	 * @return string
	 */
	public static function redemptions_table() {
		global $wpdb;
		return $wpdb->prefix . 'b2bora_redemptions';
	}

	/**
	 * Full table name for partner levels.
	 *
	 * @return string
	 */
	public static function levels_table() {
		global $wpdb;
		return $wpdb->prefix . 'b2bora_levels';
	}

	/**
	 * Full table name for missions.
	 *
	 * @return string
	 */
	public static function missions_table() {
		global $wpdb;
		return $wpdb->prefix . 'b2bora_missions';
	}

	/**
	 * Run (or re-run) installation. Safe to call multiple times: dbDelta()
	 * only creates/alters tables to match the schema, and default data
	 * inserts are guarded so nothing is duplicated.
	 */
	public static function install() {
		self::create_tables();
		self::seed_defaults();
		update_option( self::DB_VERSION_OPTION, B2BORA_PC_DB_VERSION );
	}

	/**
	 * Compare the stored DB version against the plugin's expected version
	 * and re-run installation if they differ. Hooked to `plugins_loaded`
	 * so upgrades are picked up even without a manual reactivation.
	 */
	public static function maybe_upgrade() {
		$installed = get_option( self::DB_VERSION_OPTION, '' );

		if ( $installed !== B2BORA_PC_DB_VERSION ) {
			self::install();
		}
	}

	/**
	 * Create or update all custom tables using dbDelta(). dbDelta() has
	 * strict formatting requirements (two spaces after PRIMARY KEY, each
	 * field on its own line, etc.) which is why this method is verbose.
	 */
	private static function create_tables() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$transactions = self::transactions_table();
		$sql_transactions = "CREATE TABLE {$transactions} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			order_id BIGINT UNSIGNED NULL,
			type VARCHAR(32) NOT NULL,
			points BIGINT NOT NULL,
			balance_after BIGINT NOT NULL,
			description TEXT NULL,
			reference_key VARCHAR(191) NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY reference_key (reference_key),
			KEY user_id (user_id),
			KEY order_id (order_id),
			KEY type (type),
			KEY user_id_created_at (user_id, created_at)
		) {$charset_collate};";
		dbDelta( $sql_transactions );

		$rewards       = self::rewards_table();
		$sql_rewards = "CREATE TABLE {$rewards} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL,
			description TEXT NULL,
			points_cost BIGINT UNSIGNED NOT NULL DEFAULT 0,
			reward_type VARCHAR(32) NOT NULL DEFAULT 'order_credit',
			reward_value DECIMAL(15,2) NOT NULL DEFAULT 0,
			active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
			sort_order INT NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY active (active),
			KEY sort_order (sort_order)
		) {$charset_collate};";
		dbDelta( $sql_rewards );

		$redemptions       = self::redemptions_table();
		$sql_redemptions = "CREATE TABLE {$redemptions} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			reward_id BIGINT UNSIGNED NOT NULL,
			points_spent BIGINT UNSIGNED NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			approved_at DATETIME NULL,
			used_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY reward_id (reward_id),
			KEY status (status)
		) {$charset_collate};";
		dbDelta( $sql_redemptions );

		$levels       = self::levels_table();
		$sql_levels = "CREATE TABLE {$levels} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL,
			minimum_points BIGINT UNSIGNED NOT NULL DEFAULT 0,
			benefits TEXT NULL,
			sort_order INT NOT NULL DEFAULT 0,
			active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
			PRIMARY KEY  (id),
			KEY minimum_points (minimum_points),
			KEY active (active)
		) {$charset_collate};";
		dbDelta( $sql_levels );

		$missions       = self::missions_table();
		$sql_missions = "CREATE TABLE {$missions} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL,
			description TEXT NULL,
			type VARCHAR(32) NOT NULL,
			target BIGINT UNSIGNED NOT NULL DEFAULT 0,
			bonus_points BIGINT UNSIGNED NOT NULL DEFAULT 0,
			start_date DATE NULL,
			end_date DATE NULL,
			active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY type (type),
			KEY active (active)
		) {$charset_collate};";
		dbDelta( $sql_missions );
	}

	/**
	 * Seed default levels and a default reward the first time the plugin
	 * is installed. Guarded by an option flag so re-activating the plugin
	 * (or upgrading) never creates duplicates.
	 */
	private static function seed_defaults() {
		if ( get_option( 'b2bora_pc_defaults_seeded' ) ) {
			return;
		}

		global $wpdb;

		$levels_table = self::levels_table();
		$existing_levels = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$levels_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( 0 === $existing_levels ) {
			$default_levels = array(
				array( 'name' => __( 'Starter', 'b2bora-partner-club' ), 'minimum_points' => 0, 'sort_order' => 1 ),
				array( 'name' => __( 'Partner', 'b2bora-partner-club' ), 'minimum_points' => 2000, 'sort_order' => 2 ),
				array( 'name' => __( 'Premium', 'b2bora-partner-club' ), 'minimum_points' => 5000, 'sort_order' => 3 ),
				array( 'name' => __( 'Preferred', 'b2bora-partner-club' ), 'minimum_points' => 10000, 'sort_order' => 4 ),
			);

			foreach ( $default_levels as $level ) {
				$wpdb->insert(
					$levels_table,
					array(
						'name'           => $level['name'],
						'minimum_points' => $level['minimum_points'],
						'benefits'       => '',
						'sort_order'     => $level['sort_order'],
						'active'         => 1,
					),
					array( '%s', '%d', '%s', '%d', '%d' )
				);
			}
		}

		$rewards_table = self::rewards_table();
		$existing_rewards = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$rewards_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( 0 === $existing_rewards ) {
			$wpdb->insert(
				$rewards_table,
				array(
					'name'         => __( '€20 Order Credit', 'b2bora-partner-club' ),
					'description'  => __( 'Redeem points for €20 credit towards your next wholesale order.', 'b2bora-partner-club' ),
					'points_cost'  => 2000,
					'reward_type'  => 'order_credit',
					'reward_value' => 20,
					'active'       => 1,
					'sort_order'   => 1,
					'created_at'   => current_time( 'mysql' ),
					'updated_at'   => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%d', '%s', '%f', '%d', '%d', '%s', '%s' )
			);
		}

		update_option( 'b2bora_pc_defaults_seeded', 1 );
	}
}
