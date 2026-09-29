<?php
/**
 * Admin-manageable reward types. Unlike a mission's `type`, a reward's
 * `reward_type` is never read by any detection or fulfilment logic - it
 * is purely a label an admin picks from a dropdown, used only for
 * display and light categorisation (see B2Bora_PC_Redemptions: a
 * redemption is always a manual, staff-actioned record regardless of
 * type). That makes it safe to let admins freely add, rename and delete
 * these labels, unlike mission types.
 *
 * Stored as a single wp_option (slug => label), seeded on first read
 * with the plugin's original three built-in types so every reward
 * saved before this screen existed keeps resolving to the same label
 * with no migration needed.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2Bora_PC_Reward_Types
 */
class B2Bora_PC_Reward_Types {

	const OPTION_KEY = 'b2bora_pc_reward_types';

	/**
	 * The plugin's original built-in types, used to seed the option the
	 * first time it's read and as a last-resort fallback if every type
	 * is ever deleted.
	 *
	 * @return array<string, string>
	 */
	private static function seed_defaults() {
		return array(
			'order_credit'  => __( 'Order Credit', 'b2bora-partner-club' ),
			'free_box'      => __( 'Free Box', 'b2bora-partner-club' ),
			'partner_offer' => __( 'Partner Offer', 'b2bora-partner-club' ),
		);
	}

	/**
	 * All reward types, slug => label.
	 *
	 * @return array<string, string>
	 */
	public static function get_types() {
		$stored = get_option( self::OPTION_KEY, null );

		if ( ! is_array( $stored ) || empty( $stored ) ) {
			$stored = self::seed_defaults();
			update_option( self::OPTION_KEY, $stored );
		}

		return $stored;
	}

	/**
	 * Add a new reward type from an admin-entered label. The slug is
	 * derived from the label and made unique automatically; it is never
	 * shown for editing, since existing rewards reference it directly.
	 *
	 * @param string $label Human-readable label.
	 *
	 * @return string|false The new type's slug on success, false if the label was empty.
	 */
	public static function add_type( $label ) {
		$label = sanitize_text_field( $label );

		if ( '' === $label ) {
			return false;
		}

		$types = self::get_types();
		$slug  = self::unique_slug( sanitize_title( $label ), $types );

		$types[ $slug ] = $label;
		update_option( self::OPTION_KEY, $types );

		return $slug;
	}

	/**
	 * Rename an existing type's label. The slug never changes, so every
	 * reward already using it keeps resolving correctly.
	 *
	 * @param string $slug  Existing type slug.
	 * @param string $label New label.
	 *
	 * @return bool
	 */
	public static function rename_type( $slug, $label ) {
		$slug  = sanitize_key( $slug );
		$label = sanitize_text_field( $label );
		$types = self::get_types();

		if ( '' === $label || ! array_key_exists( $slug, $types ) ) {
			return false;
		}

		$types[ $slug ] = $label;
		update_option( self::OPTION_KEY, $types );

		return true;
	}

	/**
	 * Delete a type. Refuses to delete a type that's still used by at
	 * least one reward (so no reward is ever left pointing at a type
	 * that no longer exists), and refuses to delete the last remaining
	 * type (so the dropdown is never empty).
	 *
	 * @param string $slug Type slug.
	 *
	 * @return bool True on success, false if the type doesn't exist, is
	 *              in use, or is the last remaining type.
	 */
	public static function delete_type( $slug ) {
		$slug  = sanitize_key( $slug );
		$types = self::get_types();

		if ( ! array_key_exists( $slug, $types ) ) {
			return false;
		}

		if ( count( $types ) <= 1 ) {
			return false;
		}

		if ( class_exists( 'B2Bora_PC_Rewards' ) && B2Bora_PC_Rewards::count_by_type( $slug ) > 0 ) {
			return false;
		}

		unset( $types[ $slug ] );
		update_option( self::OPTION_KEY, $types );

		return true;
	}

	/**
	 * Turn a candidate slug into one that doesn't collide with an
	 * existing type, appending -2, -3, etc. as needed.
	 *
	 * @param string               $base     Candidate slug (already run through sanitize_title()).
	 * @param array<string, string> $existing Existing slug => label map.
	 *
	 * @return string
	 */
	private static function unique_slug( $base, array $existing ) {
		$base = '' !== $base ? $base : 'type';
		$slug = $base;
		$i    = 2;

		while ( array_key_exists( $slug, $existing ) ) {
			$slug = $base . '-' . $i;
			++$i;
		}

		return $slug;
	}
}
