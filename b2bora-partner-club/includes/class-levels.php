<?php
/**
 * Partner level configuration and lookup. Levels are entirely
 * admin-configurable; nothing is hard-coded beyond the defaults seeded on
 * activation (see B2Bora_PC_Database::seed_defaults()).
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2Bora_PC_Levels
 */
class B2Bora_PC_Levels {

	/**
	 * All active levels, ordered from lowest to highest minimum_points.
	 *
	 * @param bool $include_inactive Include deactivated levels.
	 *
	 * @return array
	 */
	public static function get_levels( $include_inactive = false ) {
		global $wpdb;
		$table = B2Bora_PC_Database::levels_table();

		$where = $include_inactive ? '' : 'WHERE active = 1';

		$rows = $wpdb->get_results( "SELECT * FROM {$table} {$where} ORDER BY minimum_points ASC, sort_order ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Fetch a single level by ID.
	 *
	 * @param int $level_id Level ID.
	 *
	 * @return array|null
	 */
	public static function get_level( $level_id ) {
		global $wpdb;
		$table = B2Bora_PC_Database::levels_table();

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $level_id ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ? $row : null;
	}

	/**
	 * Create or update a level. Pass an `id` key to update.
	 *
	 * @param array $data Level fields.
	 *
	 * @return int|false Level ID on success, false on failure.
	 */
	public static function save_level( array $data ) {
		global $wpdb;
		$table = B2Bora_PC_Database::levels_table();

		$fields = array(
			'name'           => isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '',
			'minimum_points' => isset( $data['minimum_points'] ) ? absint( $data['minimum_points'] ) : 0,
			'benefits'       => isset( $data['benefits'] ) ? wp_kses_post( $data['benefits'] ) : '',
			'sort_order'     => isset( $data['sort_order'] ) ? absint( $data['sort_order'] ) : 0,
			// Default to active when the key is omitted entirely (e.g. a
			// programmatic caller); admin forms always send an explicit
			// 0 or 1 for this field, so their behaviour is unaffected.
			'active'         => array_key_exists( 'active', $data ) ? ( empty( $data['active'] ) ? 0 : 1 ) : 1,
		);

		if ( '' === $fields['name'] ) {
			return false;
		}

		$formats = array( '%s', '%d', '%s', '%d', '%d' );

		if ( ! empty( $data['id'] ) ) {
			$updated = $wpdb->update( $table, $fields, array( 'id' => absint( $data['id'] ) ), $formats, array( '%d' ) );
			return false === $updated ? false : absint( $data['id'] );
		}

		$inserted = $wpdb->insert( $table, $fields, $formats );

		return $inserted ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Delete a level.
	 *
	 * @param int $level_id Level ID.
	 *
	 * @return bool
	 */
	public static function delete_level( $level_id ) {
		global $wpdb;
		$table = B2Bora_PC_Database::levels_table();

		return (bool) $wpdb->delete( $table, array( 'id' => absint( $level_id ) ), array( '%d' ) );
	}

	/**
	 * Determine a user's current level based on their qualifying points
	 * (lifetime or balance, per settings).
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array|null Level row, or null if no active levels exist.
	 */
	public static function get_level_for_user( $user_id ) {
		$points = B2Bora_PC_Points::get_qualifying_points( $user_id );
		$levels = self::get_levels();

		if ( empty( $levels ) ) {
			return null;
		}

		$current = $levels[0];

		foreach ( $levels as $level ) {
			if ( $points >= (int) $level['minimum_points'] ) {
				$current = $level;
			}
		}

		return $current;
	}

	/**
	 * Get the next level above the user's current one, if any.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array|null
	 */
	public static function get_next_level_for_user( $user_id ) {
		$points = B2Bora_PC_Points::get_qualifying_points( $user_id );
		$levels = self::get_levels();

		foreach ( $levels as $level ) {
			if ( (int) $level['minimum_points'] > $points ) {
				return $level;
			}
		}

		return null;
	}

	/**
	 * Build a full progress summary for the customer dashboard/admin view.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array {
	 *     @type array|null $current_level  Current level row.
	 *     @type array|null $next_level     Next level row, or null if already at the top.
	 *     @type int        $points         Qualifying points.
	 *     @type int        $points_needed  Points needed to reach the next level (0 if none).
	 *     @type float      $progress_pct   Progress toward the next level, 0-100.
	 * }
	 */
	public static function get_progress_for_user( $user_id ) {
		$points        = B2Bora_PC_Points::get_qualifying_points( $user_id );
		$current_level = self::get_level_for_user( $user_id );
		$next_level    = self::get_next_level_for_user( $user_id );

		$points_needed = $next_level ? max( 0, (int) $next_level['minimum_points'] - $points ) : 0;

		$progress_pct = 100.0;

		if ( $next_level && (int) $next_level['minimum_points'] > 0 ) {
			// Progress is expressed simply as "points / next level's
			// threshold", matching how it is shown on the dashboard
			// (e.g. "7,450 / 10,000 - 74.5%"), not as a proportion of the
			// band between the current and next level.
			$progress_pct = ( $points / (int) $next_level['minimum_points'] ) * 100;
			$progress_pct = max( 0.0, min( 100.0, $progress_pct ) );
		}

		return array(
			'current_level' => $current_level,
			'next_level'    => $next_level,
			'points'        => $points,
			'points_needed' => $points_needed,
			'progress_pct'  => round( $progress_pct, 1 ),
		);
	}
}
