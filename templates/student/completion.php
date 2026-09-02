<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

use MintLMS\Application\Student\Dto\StudentPlayerContextDto;

/** @var StudentPlayerContextDto $context */
$context        = $context;
$dashboardUrl   = $dashboardUrl ?? home_url( '/' );
$overviewUrl    = $overviewUrl ?? home_url( '/' );
$certificateUrl = $certificateUrl ?? null;
?>
<div class="mint-edge-screen--accent">
	<div class="mint-edge-panel mint-edge-panel--wide">
		<div class="mint-icon-well mint-icon-well--md mint-icon-well--on-accent">
			<svg width="26" height="26" viewBox="0 0 24 24" fill="#FFFFFF" aria-hidden="true"><path d="M12 2L9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2z"/></svg>
		</div>
		<h1 class="mint-edge-title--on-accent"><?php esc_html_e( 'Course complete!', 'mint-lms' ); ?></h1>
		<p class="mint-edge-text--on-accent">
			<?php
			printf(
				/* translators: %s: course title */
				esc_html__( 'You have completed %s.', 'mint-lms' ),
				esc_html( $context->courseTitle )
			);
			?>
		</p>
		<p class="mint-edge-meta--on-accent">
			<?php
			printf(
				/* translators: %d: number of completed lessons */
				esc_html__( '%d lessons completed.', 'mint-lms' ),
				absint( count( $context->completedLessonIds ) )
			);
			?>
		</p>
		<a href="<?php echo esc_url( $dashboardUrl ); ?>" class="mint-btn mint-btn--on-accent">
			<?php esc_html_e( 'Back to my courses', 'mint-lms' ); ?>
		</a>
		<?php if ( is_string( $certificateUrl ) && '' !== $certificateUrl ) : ?>
			<a href="<?php echo esc_url( $certificateUrl ); ?>" class="mint-btn mint-btn--secondary mint-btn--on-accent" target="_blank" rel="noopener noreferrer" style="margin-left:12px">
				<?php esc_html_e( 'Download certificate', 'mint-lms' ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
