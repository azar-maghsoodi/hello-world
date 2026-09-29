<?php
/**
 * Self-service translations for the customer-facing dashboard
 * ([b2bora_partner_club] shortcode). B2Bora manages per-language content
 * by hand rather than compiled .mo files (see the other B2Bora plugins on
 * this site), so this gives admins a screen to type in the dashboard's
 * label text per Polylang language, with the same shortcode
 * auto-switching based on the page's current language - no separate
 * shortcode per language needed.
 *
 * Falls back cleanly with no Polylang installed: only the single default
 * (English) set of strings is ever used in that case.
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2Bora_PC_Translations
 */
class B2Bora_PC_Translations {

	const OPTION_KEY = 'b2bora_pc_translations';

	/**
	 * Canonical list of every overridable dashboard string: its built-in
	 * English default, an admin-facing label, and (where relevant) a hint
	 * about the placeholders the string must keep.
	 *
	 * @return array<string, array{default: string, label: string, hint?: string}>
	 */
	public static function get_string_definitions() {
		return array(
			'dashboard_aria_label'      => array(
				'default' => __( 'B2Bora Partner Club dashboard', 'b2bora-partner-club' ),
				'label'   => __( 'Dashboard region label (screen readers)', 'b2bora-partner-club' ),
			),
			'dashboard_title'           => array(
				'default' => __( 'B2Bora Partner Club', 'b2bora-partner-club' ),
				'label'   => __( 'Dashboard title', 'b2bora-partner-club' ),
			),
			'your_points_label'        => array(
				'default' => __( 'Your Points', 'b2bora-partner-club' ),
				'label'   => __( '"Your Points" label', 'b2bora-partner-club' ),
			),
			'level_name_format'        => array(
				'default' => __( '%s Partner', 'b2bora-partner-club' ),
				'label'   => __( 'Level name (with a level)', 'b2bora-partner-club' ),
				'hint'    => __( 'Keep %s where the level name (e.g. "Premium") goes.', 'b2bora-partner-club' ),
			),
			'level_name_fallback'      => array(
				'default' => __( 'Partner', 'b2bora-partner-club' ),
				'label'   => __( 'Level name (no level configured)', 'b2bora-partner-club' ),
			),
			'progress_aria_label'      => array(
				'default' => __( 'Progress to next level', 'b2bora-partner-club' ),
				'label'   => __( 'Progress bar label (screen readers)', 'b2bora-partner-club' ),
			),
			'progress_text_format'     => array(
				'default' => __( '%1$s points to %2$s (%3$s%%)', 'b2bora-partner-club' ),
				'label'   => __( 'Progress text', 'b2bora-partner-club' ),
				'hint'    => __( 'Keep %1$s (points needed), %2$s (next level name) and %3$s (percent) in this order.', 'b2bora-partner-club' ),
			),
			'max_level_text'           => array(
				'default' => __( 'You have reached the highest partner level.', 'b2bora-partner-club' ),
				'label'   => __( 'Message shown at the top level', 'b2bora-partner-club' ),
			),
			'rewards_heading'          => array(
				'default' => __( 'Available Rewards', 'b2bora-partner-club' ),
				'label'   => __( 'Rewards section heading', 'b2bora-partner-club' ),
			),
			'rewards_empty'            => array(
				'default' => __( 'No rewards are available right now. Check back soon.', 'b2bora-partner-club' ),
				'label'   => __( 'Rewards empty message', 'b2bora-partner-club' ),
			),
			'reward_points_format'     => array(
				'default' => __( '%s points', 'b2bora-partner-club' ),
				'label'   => __( 'Reward cost label', 'b2bora-partner-club' ),
				'hint'    => __( 'Keep %s where the points number goes.', 'b2bora-partner-club' ),
			),
			'redeem_button'            => array(
				'default' => __( 'Redeem', 'b2bora-partner-club' ),
				'label'   => __( 'Redeem button', 'b2bora-partner-club' ),
			),
			'activity_heading'         => array(
				'default' => __( 'Recent Activity', 'b2bora-partner-club' ),
				'label'   => __( 'Activity section heading', 'b2bora-partner-club' ),
			),
			'activity_empty'           => array(
				'default' => __( 'No activity yet. Your points will appear here once your first order is confirmed.', 'b2bora-partner-club' ),
				'label'   => __( 'Activity empty message', 'b2bora-partner-club' ),
			),
			'missions_heading'         => array(
				'default' => __( 'Missions', 'b2bora-partner-club' ),
				'label'   => __( 'Missions section heading', 'b2bora-partner-club' ),
			),
			'missions_empty'           => array(
				'default' => __( 'No active missions right now.', 'b2bora-partner-club' ),
				'label'   => __( 'Missions empty message', 'b2bora-partner-club' ),
			),
			'mission_completed'        => array(
				'default' => __( 'Completed', 'b2bora-partner-club' ),
				'label'   => __( 'Mission completed label', 'b2bora-partner-club' ),
			),
			'mission_bonus_format'     => array(
				'default' => __( '+%s points', 'b2bora-partner-club' ),
				'label'   => __( 'Mission bonus label', 'b2bora-partner-club' ),
				'hint'    => __( 'Keep %s where the bonus points number goes.', 'b2bora-partner-club' ),
			),
			'redemptions_heading'      => array(
				'default' => __( 'Redeemed Rewards', 'b2bora-partner-club' ),
				'label'   => __( 'Redemption history heading', 'b2bora-partner-club' ),
			),
			'redemptions_empty'        => array(
				'default' => __( 'You have not redeemed any rewards yet.', 'b2bora-partner-club' ),
				'label'   => __( 'Redemption history empty message', 'b2bora-partner-club' ),
			),
			'redemption_reward_fallback' => array(
				'default' => __( 'Reward', 'b2bora-partner-club' ),
				'label'   => __( 'Fallback name for a deleted reward', 'b2bora-partner-club' ),
			),
			'logged_out_message'       => array(
				'default' => __( 'Please log in to view your B2Bora Partner Club dashboard.', 'b2bora-partner-club' ),
				'label'   => __( 'Logged-out message', 'b2bora-partner-club' ),
			),
			'login_button'             => array(
				'default' => __( 'Log In', 'b2bora-partner-club' ),
				'label'   => __( 'Log in button', 'b2bora-partner-club' ),
			),
			'js_confirm_redeem'        => array(
				'default' => __( 'Redeem this reward for the points shown?', 'b2bora-partner-club' ),
				'label'   => __( 'Redeem confirmation popup', 'b2bora-partner-club' ),
			),
			'js_error'                 => array(
				'default' => __( 'Something went wrong. Please try again.', 'b2bora-partner-club' ),
				'label'   => __( 'Generic error message', 'b2bora-partner-club' ),
			),
		);
	}

	/**
	 * The language code to use right now: the current Polylang language
	 * when Polylang is active, otherwise a fixed default. Never assumes
	 * Polylang is installed.
	 *
	 * @return string
	 */
	public static function current_language() {
		if ( function_exists( 'pll_current_language' ) ) {
			$lang = pll_current_language();
			if ( $lang ) {
				return $lang;
			}
		}

		return 'en';
	}

	/**
	 * Every language slug Polylang currently has configured, or an empty
	 * array when Polylang is not active.
	 *
	 * @return string[]
	 */
	public static function get_available_languages() {
		if ( ! function_exists( 'pll_languages_list' ) ) {
			return array();
		}

		$languages = pll_languages_list();

		return is_array( $languages ) ? $languages : array();
	}

	/**
	 * Resolve one string for a language: a saved admin override first,
	 * then the built-in default (itself still passed through
	 * WordPress's own translation functions above, so a real .mo file
	 * still works as an additional fallback layer if one is ever added).
	 *
	 * @param string      $key      One of get_string_definitions()'s keys.
	 * @param string|null $language Language code; defaults to the current one.
	 *
	 * @return string
	 */
	public static function get( $key, $language = null ) {
		$definitions = self::get_string_definitions();
		if ( ! isset( $definitions[ $key ] ) ) {
			return '';
		}

		$language = $language ? sanitize_key( $language ) : self::current_language();
		$stored   = get_option( self::OPTION_KEY, array() );

		if ( isset( $stored[ $language ][ $key ] ) && '' !== $stored[ $language ][ $key ] ) {
			return $stored[ $language ][ $key ];
		}

		return $definitions[ $key ]['default'];
	}

	/**
	 * Every string for a language, keyed the same as
	 * get_string_definitions(), for pre-filling the admin form (falls
	 * back to the default wherever no override is saved).
	 *
	 * @param string $language Language code.
	 *
	 * @return array<string, string>
	 */
	public static function get_all_for_language( $language ) {
		$values = array();

		foreach ( array_keys( self::get_string_definitions() ) as $key ) {
			$values[ $key ] = self::get( $key, $language );
		}

		return $values;
	}

	/**
	 * Save the overrides for one language. Only known string keys are
	 * accepted; anything else in $values is silently dropped.
	 *
	 * @param string $language Language code.
	 * @param array  $values   Raw key => text values (already unslashed by the caller).
	 *
	 * @return bool
	 */
	public static function save( $language, array $values ) {
		$language = sanitize_key( $language );
		if ( '' === $language ) {
			return false;
		}

		$definitions = self::get_string_definitions();
		$sanitized   = array();

		foreach ( $definitions as $key => $definition ) {
			if ( isset( $values[ $key ] ) && '' !== trim( (string) $values[ $key ] ) ) {
				$sanitized[ $key ] = sanitize_text_field( $values[ $key ] );
			}
		}

		$all               = get_option( self::OPTION_KEY, array() );
		$all               = is_array( $all ) ? $all : array();
		$all[ $language ]  = $sanitized;

		return update_option( self::OPTION_KEY, $all );
	}
}
