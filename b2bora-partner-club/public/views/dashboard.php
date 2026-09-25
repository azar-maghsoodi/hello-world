<?php
/**
 * Front-end dashboard markup for the [b2bora_partner_club] shortcode.
 * Variables ($balance, $progress, $rewards, $history, $missions,
 * $redemptions, $user_id) are set by B2Bora_PC_Shortcodes::render_dashboard().
 *
 * @package B2Bora_Partner_Club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_level = $progress['current_level'];
$next_level    = $progress['next_level'];
?>
<section class="b2bora-pc-dashboard" aria-label="<?php esc_attr_e( 'B2Bora Partner Club dashboard', 'b2bora-partner-club' ); ?>">
	<header class="b2bora-pc-header">
		<h2 class="b2bora-pc-title"><?php esc_html_e( 'B2Bora Partner Club', 'b2bora-partner-club' ); ?></h2>
	</header>

	<div class="b2bora-pc-summary">
		<div class="b2bora-pc-points-card">
			<span class="b2bora-pc-points-label"><?php esc_html_e( 'Your Points', 'b2bora-partner-club' ); ?></span>
			<span class="b2bora-pc-points-value"><?php echo esc_html( number_format_i18n( $balance ) ); ?></span>
		</div>

		<div class="b2bora-pc-level-card">
			<span class="b2bora-pc-level-name">
				<?php
				echo esc_html(
					$current_level
						/* translators: %s: level name */
						? sprintf( __( '%s Partner', 'b2bora-partner-club' ), $current_level['name'] )
						: __( 'Partner', 'b2bora-partner-club' )
				);
				?>
			</span>

			<?php if ( $next_level ) : ?>
				<div class="b2bora-pc-progress" role="progressbar" aria-valuenow="<?php echo esc_attr( $progress['progress_pct'] ); ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?php esc_attr_e( 'Progress to next level', 'b2bora-partner-club' ); ?>">
					<div class="b2bora-pc-progress-bar" style="width: <?php echo esc_attr( $progress['progress_pct'] ); ?>%;"></div>
				</div>
				<span class="b2bora-pc-progress-text">
					<?php
					printf(
						/* translators: 1: points needed, 2: next level name */
						esc_html__( '%1$s points to %2$s (%3$s%%)', 'b2bora-partner-club' ),
						esc_html( number_format_i18n( $progress['points_needed'] ) ),
						esc_html( $next_level['name'] ),
						esc_html( $progress['progress_pct'] )
					);
					?>
				</span>
			<?php else : ?>
				<span class="b2bora-pc-progress-text"><?php esc_html_e( 'You have reached the highest partner level.', 'b2bora-partner-club' ); ?></span>
			<?php endif; ?>
		</div>
	</div>

	<section class="b2bora-pc-section" aria-labelledby="b2bora-pc-rewards-heading">
		<h3 id="b2bora-pc-rewards-heading" class="b2bora-pc-section-title"><?php esc_html_e( 'Available Rewards', 'b2bora-partner-club' ); ?></h3>

		<?php if ( empty( $rewards ) ) : ?>
			<p class="b2bora-pc-empty"><?php esc_html_e( 'No rewards are available right now. Check back soon.', 'b2bora-partner-club' ); ?></p>
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
							<span class="b2bora-pc-reward-cost"><?php echo esc_html( sprintf( /* translators: %s: points cost */ __( '%s points', 'b2bora-partner-club' ), number_format_i18n( (int) $reward['points_cost'] ) ) ); ?></span>
						</div>
						<button
							type="button"
							class="b2bora-pc-button b2bora-pc-redeem-button"
							data-reward-id="<?php echo esc_attr( $reward['id'] ); ?>"
							<?php disabled( ! $can_afford ); ?>
						>
							<?php esc_html_e( 'Redeem', 'b2bora-partner-club' ); ?>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="b2bora-pc-section" aria-labelledby="b2bora-pc-activity-heading">
		<h3 id="b2bora-pc-activity-heading" class="b2bora-pc-section-title"><?php esc_html_e( 'Recent Activity', 'b2bora-partner-club' ); ?></h3>

		<?php if ( empty( $history ) ) : ?>
			<p class="b2bora-pc-empty"><?php esc_html_e( 'No activity yet. Your points will appear here once your first order is confirmed.', 'b2bora-partner-club' ); ?></p>
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
		<h3 id="b2bora-pc-missions-heading" class="b2bora-pc-section-title"><?php esc_html_e( 'Missions', 'b2bora-partner-club' ); ?></h3>

		<?php if ( empty( $missions ) ) : ?>
			<p class="b2bora-pc-empty"><?php esc_html_e( 'No active missions right now.', 'b2bora-partner-club' ); ?></p>
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
									? __( 'Completed', 'b2bora-partner-club' )
									: sprintf( /* translators: %s: bonus points */ __( '+%s points', 'b2bora-partner-club' ), number_format_i18n( (int) $mission['bonus_points'] ) )
							);
							?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="b2bora-pc-section" aria-labelledby="b2bora-pc-redemptions-heading">
		<h3 id="b2bora-pc-redemptions-heading" class="b2bora-pc-section-title"><?php esc_html_e( 'Redeemed Rewards', 'b2bora-partner-club' ); ?></h3>

		<?php if ( empty( $redemptions ) ) : ?>
			<p class="b2bora-pc-empty"><?php esc_html_e( 'You have not redeemed any rewards yet.', 'b2bora-partner-club' ); ?></p>
		<?php else : ?>
			<ul class="b2bora-pc-redemptions-list">
				<?php foreach ( $redemptions as $row ) : ?>
					<li class="b2bora-pc-redemption-item">
						<span class="b2bora-pc-redemption-name"><?php echo esc_html( $row['reward_name'] ? $row['reward_name'] : __( 'Reward', 'b2bora-partner-club' ) ); ?></span>
						<span class="b2bora-pc-redemption-status b2bora-pc-status-<?php echo esc_attr( $row['status'] ); ?>"><?php echo esc_html( ucfirst( $row['status'] ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<div class="b2bora-pc-notice" role="status" aria-live="polite" hidden></div>
</section>
