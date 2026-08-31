<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Student\Dto\StudentCourseItemDto;
use MintLMS\Infrastructure\Ui\MintUi;

/** @var list<StudentCourseItemDto> $courses */
$courses   = $courses ?? array();
$filter    = $filter ?? 'all';
$pageUrl   = $pageUrl ?? home_url( '/' );
$playerUrl = $playerUrl ?? home_url( '/' );

$totalCount    = count( $courses );
$finishedCount = 0;
foreach ( $courses as $c ) {
	if ( $c->isComplete ) {
		++$finishedCount;
	}
}

$pageTitle = __( 'My courses', 'mint-lms' );
$userName  = '';
include MINTLMS_PATH . 'views/student/partials/student-header.php';
?>

<div class="mint-student-page">

	<div class="mint-student-hero">
		<h1 class="mint-student-greeting">
			<?php esc_html_e( 'My courses', 'mint-lms' ); ?>
		</h1>
		<p class="mint-student-lead">
			<?php
			printf(
				/* translators: 1: total course count, 2: finished course count */
				esc_html__( '%1$d courses · %2$d finished', 'mint-lms' ),
				absint( $totalCount ),
				absint( $finishedCount )
			);
			?>
		</p>
	</div>

	<?php if ( array() === $courses ) : ?>
		<div class="mint-student-empty">
			<p class="mint-student-lead"><?php esc_html_e( 'No courses found. Enroll in a course to get started.', 'mint-lms' ); ?></p>
		</div>
	<?php else : ?>
		<div class="mint-student-grid">
			<?php foreach ( $courses as $idx => $course ) :
				$hue     = MintUi::hueByIndex( $idx );
				$initial = MintUi::courseInitial( $course->title );
				$pctVal  = (int) round( $course->progressPct );
				$isDone  = $course->isComplete;

				$targetLesson = $isDone
					? $course->firstLessonId
					: ( $course->lastLessonId ?? $course->firstLessonId );
				$actionUrl = $targetLesson
					? add_query_arg( array( 'mint_course' => (string) $course->courseId, 'mint_lesson' => (string) $targetLesson ), $playerUrl )
					: $playerUrl;

				$ctaLabel = $isDone ? __( 'Review course', 'mint-lms' ) : __( 'Continue', 'mint-lms' );
			?>
				<div class="mint-course-card mint-course-card--static">
					<div class="mint-course-card__banner mint-course-card__banner--tall" style="background:<?php echo esc_attr( $hue['bg'] ); ?>">
						<span class="mint-course-card__initial mint-course-card__initial--lg" style="color:<?php echo esc_attr( $hue['ink'] ); ?>"><?php echo esc_html( $initial ); ?></span>
						<?php if ( $isDone ) : ?>
							<div class="mint-course-card__done-badge">
								<svg width="14" height="14" viewBox="0 0 20 20" fill="#FFFFFF" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
							</div>
						<?php endif; ?>
					</div>
					<div class="mint-course-card__body mint-course-card__body--tall">
						<div class="mint-course-card__title mint-course-card__title--lg"><?php echo esc_html( $course->title ); ?></div>
						<div class="mint-course-card__meta"><?php echo esc_html( $course->enrollmentType ); ?></div>
						<div class="mint-progress mint-progress--md">
							<div class="mint-progress__fill" style="width:<?php echo esc_attr( (string) absint( $pctVal ) ); ?>%;background:<?php echo $isDone ? 'var(--mint-success)' : 'var(--mint-accent)'; ?>"></div>
						</div>
						<a href="<?php echo esc_url( $actionUrl ); ?>" class="mint-btn mint-btn--<?php echo $isDone ? 'secondary' : 'primary'; ?> mint-course-card__cta">
							<?php echo esc_html( $ctaLabel ); ?>
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
