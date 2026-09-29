<?php
/**
 * Front-end dashboard markup for the [b2bora_partner_club] shortcode.
 * Variables ($balance, $progress, $rewards, $history, $missions,
 * $redemptions, $user_id) are set by B2Bora_PC_Shortcodes::render_dashboard().
 *
 * All visible text goes through B2Bora_PC_Translations::get() rather than
 * a direct __()/esc_html_e() call, so admins can override any string per
 * Polylang language from Partner Club -> Translations without editing
 * code; each lookup still falls back to the built-in English default (and
 * to a real .mo translation of that default, if one is ever added).
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_level = $progress['current_level'];
$next_level    = $progress['next_level'];

$t = static function ( $key ) {
	return B2Bora_PC_Translations::get( $key );
};
?>
<section class="b2bora-pc-dashboard" aria-label="<?php echo esc_attr( $t( 'dashboard_aria_label' ) ); ?>">
	<header class="b2bora-pc-header">
		<h2 class="b2bora-pc-title"><?php echo esc_html( $t( 'dashboard_title' ) ); ?></h2>
	</header>

	<div class="b2bora-pc-summary">
		<div class="b2bora-pc-points-card">
			<span class="b2bora-pc-points-label"><?php echo esc_html( $t( 'your_points_label' ) ); ?></span>
			<span class="b2bora-pc-points-value"><?php echo esc_html( number_format_i18n( $balance ) ); ?></span>
		</div>

		<div class="b2bora-pc-level-card">
			<span class="b2bora-pc-level-name">
				<?php
				echo esc_html(
					$current_level
						? sprintf( $t( 'level_name_format' ), $current_level['name'] )
						: $t( 'level_name_fallback' )
				);
				?>
			</span>

			<?php if ( $next_level ) : ?>
				<div class="b2bora-pc-progress" role="progressbar" aria-valuenow="<?php echo esc_attr( $progress['progress_pct'] ); ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?php echo esc_attr( $t( 'progress_aria_label' ) ); ?>">
					<div class="b2bora-pc-progress-bar" style="width: <?php echo esc_attr( $progress['progress_pct'] ); ?>%;"></div>
				</div>
				<span class="b2bora-pc-progress-text">
					<?php
					echo esc_html(
						sprintf(
							$t( 'progress_text_format' ),
							number_format_i18n( $progress['points_needed'] ),
							$next_level['name'],
							$progress['progress_pct']
						)
					);
					?>
				</span>
			<?php else : ?>
				<span class="b2bora-pc-progress-text"><?php echo esc_html( $t( 'max_level_text' ) ); ?></span>
			<?php endif; ?>
		</div>
	</div>

	<section class="b2bora-pc-section" aria-labelledby="b2bora-pc-rewards-heading">
		<h3 id="b2bora-pc-rewards-heading" class="b2bora-pc-section-title"><?php echo esc_html( $t( 'rewards_heading' ) ); ?></h3>

		<?php if ( empty( $rewards ) ) : ?>
			<p class="b2bora-pc-empty"><?php echo esc_html( $t( 'rewards_empty' ) ); ?></p>
		<?php else : ?>
			<ul class="b2bora-pc-rewards-list">
				<?php foreach ( $rewards as $reward ) :
					$can_afford = $balance >= (int) $reward['points_cost'];
					?>
					<li class="b2bora-pc-reward-card">
						<div class="b2bora-pc-reward-info">
							<span class="b2bora-pc-reward-name"><?php echo esc_html( $reward['name'] ); ?></span>
							<?php if ( ! empty( $reward['description'] ) ) : ?>
								<span class="b2bora-pc-reward-description"><?php echo esc_html( $reward['description'] ); ?></span>
							<?php endif; ?>
							<span class="b2bora-pc-reward-cost"><?php echo esc_html( sprintf( $t( 'reward_points_format' ), number_format_i18n( (int) $reward['points_cost'] ) ) ); ?></span>
						</div>
						<button
							type="button"
							class="b2bora-pc-button b2bora-pc-redeem-button"
							data-reward-id="<?php echo esc_attr( $reward['id'] ); ?>"
							<?php disabled( ! $can_afford ); ?>
						>
							<?php echo esc_html( $t( 'redeem_button' ) ); ?>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="b2bora-pc-section" aria-labelledby="b2bora-pc-activity-heading">
		<h3 id="b2bora-pc-activity-heading" class="b2bora-pc-section-title"><?php echo esc_html( $t( 'activity_heading' ) ); ?></h3>

		<?php if ( empty( $history ) ) : ?>
			<p class="b2bora-pc-empty"><?php echo esc_html( $t( 'activity_empty' ) ); ?></p>
		<?php else : ?>
			<ul class="b2bora-pc-activity-list">
				<?php foreach ( $history as $row ) : ?>
					<li class="b2bora-pc-activity-item">
						<span class="<?php echo $row['points'] >= 0 ? 'b2bora-pc-positive' : 'b2bora-pc-negative'; ?>">
							<?php echo esc_html( ( $row['points'] >= 0 ? '+' : '' ) . number_format_i18n( (int) $row['points'] ) ); ?>
						</span>
						<span class="b2bora-pc-activity-description"><?php echo esc_html( $row['description'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="b2bora-pc-section" aria-labelledby="b2bora-pc-missions-heading">
		<h3 id="b2bora-pc-missions-heading" class="b2bora-pc-section-title"><?php echo esc_html( $t( 'missions_heading' ) ); ?></h3>

		<?php if ( empty( $missions ) ) : ?>
			<p class="b2bora-pc-empty"><?php echo esc_html( $t( 'missions_empty' ) ); ?></p>
		<?php else : ?>
			<ul class="b2bora-pc-missions-list">
				<?php foreach ( $missions as $mission ) :
					$completed = B2Bora_PC_Missions::is_completed_by_user( $mission['id'], $user_id );
					?>
					<li class="b2bora-pc-mission-item <?php echo $completed ? 'b2bora-pc-mission-completed' : ''; ?>">
						<div class="b2bora-pc-mission-info">
							<span class="b2bora-pc-mission-name"><?php echo esc_html( $mission['name'] ); ?></span>
							<?php if ( ! empty( $mission['description'] ) ) : ?>
								<span class="b2bora-pc-mission-description"><?php echo esc_html( $mission['description'] ); ?></span>
							<?php endif; ?>
						</div>
						<span class="b2bora-pc-mission-bonus">
							<?php
							echo esc_html(
								$completed
									? $t( 'mission_completed' )
									: sprintf( $t( 'mission_bonus_format' ), number_format_i18n( (int) $mission['bonus_points'] ) )
							);
							?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="b2bora-pc-section" aria-labelledby="b2bora-pc-redemptions-heading">
		<h3 id="b2bora-pc-redemptions-heading" class="b2bora-pc-section-title"><?php echo esc_html( $t( 'redemptions_heading' ) ); ?></h3>

		<?php if ( empty( $redemptions ) ) : ?>
			<p class="b2bora-pc-empty"><?php echo esc_html( $t( 'redemptions_empty' ) ); ?></p>
		<?php else : ?>
			<ul class="b2bora-pc-redemptions-list">
				<?php foreach ( $redemptions as $row ) : ?>
					<li class="b2bora-pc-redemption-item">
						<span class="b2bora-pc-redemption-name"><?php echo esc_html( $row['reward_name'] ? $row['reward_name'] : $t( 'redemption_reward_fallback' ) ); ?></span>
						<span class="b2bora-pc-redemption-status b2bora-pc-status-<?php echo esc_attr( $row['status'] ); ?>"><?php echo esc_html( ucfirst( $row['status'] ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<div class="b2bora-pc-notice" role="status" aria-live="polite" hidden></div>
</section>
