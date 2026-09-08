<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

use MintLMS\Application\Student\Dto\StudentCourseOverviewDto;
use MintLMS\Infrastructure\Ui\MintUi;

/** @var StudentCourseOverviewDto $overview */
$overview      = $overview;
$playerUrl     = $playerUrl ?? home_url( '/' );
$dashboardUrl  = $dashboardUrl ?? home_url( '/' );
$enrollUrl     = $enrollUrl ?? '';
$enrollMessage = $enrollMessage ?? '';
$featuredUrl   = $featuredUrl ?? null;
$purchaseUrl   = $purchaseUrl ?? '';
$productPrice  = $productPrice ?? '';

$startLessonId = $overview->lastLessonId ?? $overview->firstLessonId;
$continueUrl   = $startLessonId
	? add_query_arg( array( 'mint_course' => (string) $overview->courseId, 'mint_lesson' => (string) $startLessonId ), $playerUrl )
	: $playerUrl;

$pct          = null !== $overview->progressPct ? (int) round( $overview->progressPct ) : 0;
$totalLessons = 0;
$doneLessons  = 0;
foreach ( $overview->structure->sections as $section ) {
	$totalLessons += count( $section->lessons );
}
if ( $totalLessons > 0 && null !== $overview->progressPct ) {
	$doneLessons = (int) round( $overview->progressPct / 100 * $totalLessons );
}

$huePalette         = array(
	array( 'bg' => 'mint-bg-hue-1', 'text' => 'mint-text-hue-1i' ),
);
$courseHue          = $huePalette[0];
$initial            = MintUi::courseInitial( $overview->title );
$completedLessonIds = $overview->completedLessonIds;
$currentLessonId    = $overview->isEnrolled
	? ( $overview->lastLessonId ?? $overview->firstLessonId )
	: null;

$pageTitle = $overview->title;
include MINTLMS_PATH . 'views/student/partials/student-header.php';
?>

<?php if ( '' !== $enrollMessage ) : ?>
	<div class="mint-max-w-content mint-mx-auto mint-px-4 sm:mint-px-6 mint-mb-4">
		<div class="mint-rounded-lg mint-border mint-border-success/20 mint-bg-success-wash mint-px-4 mint-py-3 mint-text-sm mint-font-medium mint-text-success" role="status"><?php echo esc_html( $enrollMessage ); ?></div>
	</div>
<?php endif; ?>
<?php if ( isset( $_GET['mint_enroll_error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
	<div class="mint-max-w-content mint-mx-auto mint-px-4 sm:mint-px-6 mint-mb-4">
		<div class="mint-rounded-lg mint-border mint-border-danger/20 mint-bg-danger-wash mint-px-4 mint-py-3 mint-text-sm mint-font-medium mint-text-danger" role="alert"><?php esc_html_e( 'Unable to enroll. This course may require purchase or manual enrollment.', 'mint-lms' ); ?></div>
	</div>
<?php endif; ?>

<div class="mint-max-w-content mint-mx-auto mint-px-4 sm:mint-px-6 mint-pb-10">
	<div class="mint-grid mint-grid-cols-1 lg:mint-grid-cols-[1fr_280px] mint-gap-8 lg:mint-gap-10">

		<div class="mint-min-w-0">

			<div class="mint-mb-3 mint-text-over mint-font-semibold mint-uppercase mint-tracking-widest mint-text-accent">
				<?php echo esc_html( strtoupper( $overview->enrollmentType ) ); ?>
			</div>

			<h1 class="mint-text-h1 mint-font-semibold mint-text-ink mint-tracking-tight">
				<?php echo esc_html( $overview->title ); ?>
			</h1>

			<?php if ( '' !== $overview->description ) : ?>
				<div class="mint-mt-4 mint-text-prose mint-text-ink-2 [&_p]:mint-mb-4 [&_p:last-child]:mint-mb-0">
					<?php echo wp_kses_post( wpautop( $overview->description ) ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $overview->isEnrolled && null !== $overview->progressPct ) : ?>
				<div class="mint-mt-6 mint-flex mint-items-center mint-gap-4">
					<progress
						class="mint-block mint-h-2 mint-min-w-0 mint-flex-1 mint-appearance-none mint-overflow-hidden mint-rounded-full mint-bg-bg-track [&::-moz-progress-bar]:mint-rounded-full [&::-moz-progress-bar]:mint-bg-accent [&::-webkit-progress-bar]:mint-rounded-full [&::-webkit-progress-bar]:mint-bg-bg-track [&::-webkit-progress-value]:mint-rounded-full [&::-webkit-progress-value]:mint-bg-accent"
						value="<?php echo esc_attr( (string) absint( $pct ) ); ?>"
						max="100"
						aria-label="<?php esc_attr_e( 'Course progress', 'mint-lms' ); ?>"
					></progress>
					<span class="mint-shrink-0 mint-text-sm mint-font-medium mint-tabular-nums mint-text-ink-2">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: progress percentage, 2: completed lessons, 3: total lessons */
								__( '%1$d%% · %2$d of %3$d', 'mint-lms' ),
								absint( $pct ),
								absint( $doneLessons ),
								absint( $totalLessons )
							)
						);
						?>
					</span>
				</div>
			<?php endif; ?>

			<div class="mint-mt-6 mint-flex mint-flex-wrap mint-gap-3">
				<?php if ( $overview->isEnrolled ) : ?>
					<a href="<?php echo esc_url( $continueUrl ); ?>" class="mint-inline-flex mint-items-center mint-justify-center mint-h-control-md mint-px-5 mint-text-sm mint-font-medium mint-rounded-md mint-bg-accent mint-text-neutral-50 hover:mint-bg-accent-hover mint-transition-colors mint-duration-hover mint-no-underline">
						<?php echo null !== $overview->progressPct && $overview->progressPct > 0
							? esc_html__( 'Continue course', 'mint-lms' )
							: esc_html__( 'Start course', 'mint-lms' ); ?>
					</a>
				<?php elseif ( $overview->canEnroll ) : ?>
					<a href="<?php echo esc_url( $enrollUrl ); ?>" class="mint-inline-flex mint-items-center mint-justify-center mint-h-control-md mint-px-5 mint-text-sm mint-font-medium mint-rounded-md mint-bg-accent mint-text-neutral-50 hover:mint-bg-accent-hover mint-transition-colors mint-duration-hover mint-no-underline">
						<?php esc_html_e( 'Enroll for free', 'mint-lms' ); ?>
					</a>
				<?php elseif ( 'paid' === $overview->enrollmentType && '' !== $purchaseUrl ) : ?>
					<a href="<?php echo esc_url( $purchaseUrl ); ?>" class="mint-inline-flex mint-items-center mint-justify-center mint-h-control-md mint-px-5 mint-text-sm mint-font-medium mint-rounded-md mint-bg-accent mint-text-neutral-50 hover:mint-bg-accent-hover mint-transition-colors mint-duration-hover mint-no-underline">
						<?php
						echo '' !== $productPrice
							? esc_html(
								sprintf(
									/* translators: %s: formatted product price */
									__( 'Buy course — %s', 'mint-lms' ),
									function_exists( 'wc_price' )
										? wp_strip_all_tags( wc_price( (float) $productPrice ) )
										: $productPrice
								)
							)
							: esc_html__( 'Buy course', 'mint-lms' );
						?>
					</a>
				<?php elseif ( 'paid' === $overview->enrollmentType ) : ?>
					<p class="mint-text-sm mint-text-ink-3"><?php esc_html_e( 'This course requires purchase. The product is not available yet.', 'mint-lms' ); ?></p>
				<?php elseif ( ! $overview->isLoggedIn ) : ?>
					<a href="<?php echo esc_url( wp_login_url( get_permalink() ?: home_url( '/' ) ) ); ?>" class="mint-inline-flex mint-items-center mint-justify-center mint-h-control-md mint-px-5 mint-text-sm mint-font-medium mint-rounded-md mint-bg-accent mint-text-neutral-50 hover:mint-bg-accent-hover mint-transition-colors mint-duration-hover mint-no-underline">
						<?php esc_html_e( 'Log in to enroll', 'mint-lms' ); ?>
					</a>
				<?php else : ?>
					<p class="mint-text-sm mint-text-ink-3"><?php esc_html_e( 'This course requires manual enrollment.', 'mint-lms' ); ?></p>
				<?php endif; ?>
			</div>

			<section class="mint-mt-10">
				<?php foreach ( $overview->structure->sections as $sIdx => $section ) :
					$sectionLessonCount = count( $section->lessons );
					?>
					<div class="mint-mb-6 mint-overflow-hidden mint-rounded-xl mint-border mint-border-rule mint-bg-bg">
						<div class="mint-flex mint-items-center mint-justify-between mint-gap-4 mint-border-b mint-border-rule mint-bg-bg-subtle mint-px-5 mint-py-4">
							<h3 class="mint-text-h3 mint-font-semibold mint-text-ink"><?php echo esc_html( $section->title ); ?></h3>
							<span class="mint-shrink-0 mint-text-xs mint-font-medium mint-text-ink-3"><?php
							printf(
								/* translators: %d: number of lessons in the section */
								esc_html__( '%d lessons', 'mint-lms' ),
								absint( $sectionLessonCount )
							);
							?></span>
						</div>
						<div class="mint-divide-y mint-divide-rule">
							<?php foreach ( $section->lessons as $lIdx => $lesson ) :
								$hasContent  = '' !== $lesson->content || null !== $lesson->videoUrl;
								$accessible  = ( $overview->isEnrolled || $lesson->isPreview ) && ! $lesson->isDripLocked;
								$isCompleted = in_array( $lesson->id, $completedLessonIds, true );
								$isCurrent   = $accessible && null !== $currentLessonId && $lesson->id === $currentLessonId && ! $isCompleted;
								$isLocked    = ! $accessible;

								if ( $isCurrent ) {
									$rowClass = 'mint-bg-accent-wash';
								} elseif ( $isLocked ) {
									$rowClass = 'mint-opacity-60';
								} else {
									$rowClass = 'hover:mint-bg-bg-hover';
								}
								?>
								<div class="mint-flex mint-items-start mint-gap-3 mint-px-5 mint-py-4 mint-transition-colors mint-duration-hover <?php echo esc_attr( $rowClass ); ?>">
									<?php if ( $isCompleted ) : ?>
										<div class="mint-mt-0.5 mint-flex mint-h-8 mint-w-8 mint-shrink-0 mint-items-center mint-justify-center mint-rounded-full mint-bg-success-wash mint-text-success">
											<svg class="mint-h-4 mint-w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
										</div>
									<?php elseif ( $isCurrent ) : ?>
										<div class="mint-mt-0.5 mint-flex mint-h-8 mint-w-8 mint-shrink-0 mint-items-center mint-justify-center mint-rounded-full mint-bg-accent mint-text-neutral-50">
											<svg class="mint-h-3.5 mint-w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
										</div>
									<?php elseif ( $isLocked ) : ?>
										<div class="mint-mt-0.5 mint-flex mint-h-8 mint-w-8 mint-shrink-0 mint-items-center mint-justify-center mint-rounded-full mint-bg-bg-subtle mint-text-ink-3">
											<svg class="mint-h-3.5 mint-w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
										</div>
									<?php else : ?>
										<div class="mint-mt-0.5 mint-flex mint-h-8 mint-w-8 mint-shrink-0 mint-items-center mint-justify-center mint-rounded-full mint-bg-bg-subtle mint-text-ink-3">
											<svg class="mint-h-3.5 mint-w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
										</div>
									<?php endif; ?>
									<div class="mint-min-w-0 mint-flex-1">
										<?php if ( $accessible && $hasContent && $overview->isEnrolled ) : ?>
											<a href="<?php echo esc_url( add_query_arg( array( 'mint_course' => (string) $overview->courseId, 'mint_lesson' => (string) $lesson->id ), $playerUrl ) ); ?>" class="mint-text-sm mint-font-medium mint-text-ink hover:mint-text-accent mint-no-underline mint-transition-colors mint-duration-hover"><?php echo esc_html( $lesson->title ); ?></a>
										<?php elseif ( $accessible && $hasContent && $lesson->isPreview ) : ?>
											<a href="<?php echo esc_url( add_query_arg( array( 'mint_course' => (string) $overview->courseId, 'mint_lesson' => (string) $lesson->id ), $playerUrl ) ); ?>" class="mint-text-sm mint-font-medium mint-text-accent hover:mint-text-accent-hover mint-no-underline mint-transition-colors mint-duration-hover"><?php echo esc_html( $lesson->title ); ?></a>
										<?php else : ?>
											<span class="mint-text-sm mint-font-medium mint-text-ink-2"><?php echo esc_html( $lesson->title ); ?></span>
										<?php endif; ?>
										<?php if ( $lesson->isPreview ) : ?>
											<span class="mint-ml-2 mint-inline-flex mint-items-center mint-rounded-sm mint-bg-accent-wash mint-px-2 mint-py-0.5 mint-text-xs mint-font-medium mint-text-accent"><?php esc_html_e( 'Preview', 'mint-lms' ); ?></span>
										<?php endif; ?>
										<?php if ( $lesson->isDripLocked && is_string( $lesson->dripMessage ) && '' !== $lesson->dripMessage ) : ?>
											<span class="mint-ml-2 mint-text-xs mint-text-ink-3"><?php echo esc_html( $lesson->dripMessage ); ?></span>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</section>
		</div>

		<aside class="mint-min-w-0">
			<?php if ( is_string( $featuredUrl ) && '' !== $featuredUrl ) : ?>
				<img src="<?php echo esc_url( $featuredUrl ); ?>" alt="" class="mint-mb-6 mint-w-full mint-rounded-xl mint-border mint-border-rule mint-object-cover mint-aspect-video" />
			<?php else : ?>
				<div class="mint-mb-6 mint-flex mint-aspect-video mint-w-full mint-items-center mint-justify-center mint-rounded-xl mint-border mint-border-rule <?php echo esc_attr( $courseHue['bg'] ); ?>">
					<span class="mint-text-display mint-font-semibold mint-leading-none <?php echo esc_attr( $courseHue['text'] ); ?>"><?php echo esc_html( $initial ); ?></span>
				</div>
			<?php endif; ?>

			<div class="mint-mb-4 mint-text-over mint-font-semibold mint-uppercase mint-tracking-widest mint-text-ink-3"><?php esc_html_e( 'THIS COURSE INCLUDES', 'mint-lms' ); ?></div>
			<ul class="mint-space-y-3">
				<li class="mint-flex mint-items-center mint-gap-3 mint-text-sm mint-text-ink-2">
					<svg class="mint-h-[18px] mint-w-[18px] mint-shrink-0 mint-text-ink-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
					<?php
					printf(
						/* translators: %d: total number of lessons in the course */
						esc_html__( '%d lessons', 'mint-lms' ),
						absint( $totalLessons )
					);
					?>
				</li>
				<li class="mint-flex mint-items-center mint-gap-3 mint-text-sm mint-text-ink-2">
					<svg class="mint-h-[18px] mint-w-[18px] mint-shrink-0 mint-text-ink-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
					<?php
					printf(
						/* translators: %d: number of sections in the course */
						esc_html__( '%d sections', 'mint-lms' ),
						absint( count( $overview->structure->sections ) )
					);
					?>
				</li>
				<li class="mint-flex mint-items-center mint-gap-3 mint-text-sm mint-text-ink-2">
					<svg class="mint-h-[18px] mint-w-[18px] mint-shrink-0 mint-text-ink-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
					<?php esc_html_e( 'Self-paced', 'mint-lms' ); ?>
				</li>
			</ul>
		</aside>
	</div>
</div>
