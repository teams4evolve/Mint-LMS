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

$courseHue = MintUi::hueByIndex( 0 );
$initial   = MintUi::courseInitial( $overview->title );
$completedLessonIds = $overview->completedLessonIds;
$currentLessonId    = $overview->isEnrolled
	? ( $overview->lastLessonId ?? $overview->firstLessonId )
	: null;

$pageTitle = $overview->title;
include MINTLMS_PATH . 'views/student/partials/student-header.php';
?>

<?php if ( '' !== $enrollMessage ) : ?>
	<div class="mint-alert-wrap">
		<div class="mint-alert mint-alert--success" role="status"><?php echo esc_html( $enrollMessage ); ?></div>
	</div>
<?php endif; ?>
<?php if ( isset( $_GET['mint_enroll_error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
	<div class="mint-alert-wrap">
		<div class="mint-alert mint-alert--danger" role="alert"><?php esc_html_e( 'Unable to enroll. This course may require manual enrollment.', 'mint-lms' ); ?></div>
	</div>
<?php endif; ?>

<div class="mint-student-layout">

	<div class="mint-student-main">

		<div class="mint-t-over mint-t-over--accent-spaced">
			<?php echo esc_html( strtoupper( $overview->enrollmentType ) ); ?>
		</div>

		<h1 class="mint-student-title">
			<?php echo esc_html( $overview->title ); ?>
		</h1>

		<?php if ( '' !== $overview->description ) : ?>
			<div class="mint-lead-text">
				<?php echo wp_kses_post( wpautop( $overview->description ) ); ?>
			</div>
		<?php endif; ?>

		<?php if ( $overview->isEnrolled && null !== $overview->progressPct ) : ?>
			<div class="mint-progress-row">
				<div class="mint-progress">
					<div class="mint-progress__fill" style="width:<?php echo esc_attr( (string) absint( $pct ) ); ?>%"></div>
				</div>
				<span class="mint-progress-label">
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

		<div class="mint-enroll-actions">
			<?php if ( $overview->isEnrolled ) : ?>
				<a href="<?php echo esc_url( $continueUrl ); ?>" class="mint-btn mint-btn--primary">
					<?php echo null !== $overview->progressPct && $overview->progressPct > 0
						? esc_html__( 'Continue course', 'mint-lms' )
						: esc_html__( 'Start course', 'mint-lms' ); ?>
				</a>
			<?php elseif ( $overview->canEnroll ) : ?>
				<a href="<?php echo esc_url( $enrollUrl ); ?>" class="mint-btn mint-btn--primary">
					<?php esc_html_e( 'Enroll for free', 'mint-lms' ); ?>
				</a>
			<?php elseif ( ! $overview->isLoggedIn ) : ?>
				<a href="<?php echo esc_url( wp_login_url( get_permalink() ?: home_url( '/' ) ) ); ?>" class="mint-btn mint-btn--primary">
					<?php esc_html_e( 'Log in to enroll', 'mint-lms' ); ?>
				</a>
			<?php else : ?>
				<p class="mint-text-muted"><?php esc_html_e( 'This course requires manual enrollment.', 'mint-lms' ); ?></p>
			<?php endif; ?>
		</div>

		<section>
			<?php foreach ( $overview->structure->sections as $sIdx => $section ) :
				$sectionLessonCount = count( $section->lessons );
			?>
				<div class="mint-curriculum-section">
					<div class="mint-curriculum-section__head">
						<h3 class="mint-curriculum-section__title"><?php echo esc_html( $section->title ); ?></h3>
						<span class="mint-curriculum-section__count"><?php
						printf(
							/* translators: %d: number of lessons in the section */
							esc_html__( '%d lessons', 'mint-lms' ),
							absint( $sectionLessonCount )
						);
						?></span>
					</div>
					<div class="mint-lesson-list">
						<?php foreach ( $section->lessons as $lIdx => $lesson ) :
							$hasContent = '' !== $lesson->content || null !== $lesson->videoUrl;
							$accessible = ( $overview->isEnrolled || $lesson->isPreview ) && ! $lesson->isDripLocked;
							$isCompleted = in_array( $lesson->id, $completedLessonIds, true );
							$isCurrent   = $accessible && null !== $currentLessonId && $lesson->id === $currentLessonId && ! $isCompleted;
							$isLocked    = ! $accessible;
							$rowClass    = 'mint-lesson-row';
							if ( $isCurrent ) {
								$rowClass .= ' is-current';
							}
							if ( $isLocked ) {
								$rowClass .= ' is-locked';
							}
						?>
							<div class="<?php echo esc_attr( $rowClass ); ?>">
								<?php if ( $isCompleted ) : ?>
									<div class="mint-lesson-icon mint-lesson-icon--done">
										<svg width="16" height="16" viewBox="0 0 20 20" fill="#0B7A57" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
									</div>
								<?php elseif ( $isCurrent ) : ?>
									<div class="mint-lesson-icon mint-lesson-icon--current">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="#FFFFFF" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
									</div>
								<?php elseif ( $isLocked ) : ?>
									<div class="mint-lesson-icon">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--mint-ink-3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
									</div>
								<?php else : ?>
									<div class="mint-lesson-icon">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--mint-ink-3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
									</div>
								<?php endif; ?>
								<div style="flex:1;min-width:0">
									<?php if ( $accessible && $hasContent && $overview->isEnrolled ) : ?>
										<a href="<?php echo esc_url( add_query_arg( array( 'mint_course' => (string) $overview->courseId, 'mint_lesson' => (string) $lesson->id ), $playerUrl ) ); ?>" class="mint-lesson-link"><?php echo esc_html( $lesson->title ); ?></a>
									<?php elseif ( $accessible && $hasContent && $lesson->isPreview ) : ?>
										<a href="<?php echo esc_url( add_query_arg( array( 'mint_course' => (string) $overview->courseId, 'mint_lesson' => (string) $lesson->id ), $playerUrl ) ); ?>" class="mint-lesson-link mint-lesson-link--preview"><?php echo esc_html( $lesson->title ); ?></a>
									<?php else : ?>
										<span class="mint-lesson-link"><?php echo esc_html( $lesson->title ); ?></span>
									<?php endif; ?>
									<?php if ( $lesson->isPreview ) : ?>
										<span class="mint-lesson-preview-label"><?php esc_html_e( 'Preview', 'mint-lms' ); ?></span>
									<?php endif; ?>
									<?php if ( $lesson->isDripLocked && is_string( $lesson->dripMessage ) && '' !== $lesson->dripMessage ) : ?>
										<span class="mint-lesson-preview-label"><?php echo esc_html( $lesson->dripMessage ); ?></span>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</section>
	</div>

	<aside class="mint-student-aside">
		<?php if ( is_string( $featuredUrl ) && '' !== $featuredUrl ) : ?>
			<img src="<?php echo esc_url( $featuredUrl ); ?>" alt="" class="mint-course-feature" />
		<?php else : ?>
			<div class="mint-course-feature__placeholder" style="background:<?php echo esc_attr( $courseHue['bg'] ); ?>">
				<span class="mint-course-feature__initial" style="color:<?php echo esc_attr( $courseHue['ink'] ); ?>"><?php echo esc_html( $initial ); ?></span>
			</div>
		<?php endif; ?>

		<div class="mint-t-over" style="margin-bottom:14px"><?php esc_html_e( 'THIS COURSE INCLUDES', 'mint-lms' ); ?></div>
		<ul class="mint-feature-list">
			<li class="mint-feature-list__item">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--mint-ink-3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
				<?php
				printf(
					/* translators: %d: total number of lessons in the course */
					esc_html__( '%d lessons', 'mint-lms' ),
					absint( $totalLessons )
				);
				?>
			</li>
			<li class="mint-feature-list__item">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--mint-ink-3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
				<?php
				printf(
					/* translators: %d: number of sections in the course */
					esc_html__( '%d sections', 'mint-lms' ),
					absint( count( $overview->structure->sections ) )
				);
				?>
			</li>
			<li class="mint-feature-list__item">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--mint-ink-3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
				<?php esc_html_e( 'Self-paced', 'mint-lms' ); ?>
			</li>
		</ul>
	</aside>
</div>
