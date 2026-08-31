<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Student\Dto\StudentCourseItemDto;
use MintLMS\Infrastructure\Ui\MintUi;

/** @var list<StudentCourseItemDto> $courses */
$courses   = $courses ?? array();
$playerUrl = $playerUrl ?? home_url( '/' );

$user      = wp_get_current_user();
$firstName = $user->first_name ?: $user->display_name ?: '';
$greeting  = MintUi::greeting();

$continueCourse = null;
foreach ( $courses as $c ) {
	if ( ! $c->isComplete && $c->progressPct > 0 ) {
		$continueCourse = $c;
		break;
	}
}
if ( null === $continueCourse && array() !== $courses ) {
	foreach ( $courses as $c ) {
		if ( ! $c->isComplete ) {
			$continueCourse = $c;
			break;
		}
	}
}

$totalLessons   = 8;
$doneLessons    = $continueCourse ? (int) round( $continueCourse->progressPct / 100 * $totalLessons ) : 0;

$pageTitle = __( 'My learning', 'mint-lms' );
include MINTLMS_PATH . 'views/student/partials/student-header.php';
?>

<div class="mint-student-page">

	<div class="mint-student-hero">
		<h1 class="mint-student-greeting">
			<?php echo esc_html( $greeting . ', ' . $firstName ); ?>
		</h1>
		<?php if ( null !== $continueCourse ) : ?>
			<p class="mint-student-lead">
				<?php
				printf(
					/* translators: 1: progress percentage, 2: course title */
					esc_html__( 'You\'re %1$d%% through %2$s.', 'mint-lms' ),
					(int) round( $continueCourse->progressPct ),
					esc_html( $continueCourse->title )
				);
				?>
			</p>
		<?php endif; ?>
	</div>

	<?php if ( null !== $continueCourse ) :
		$continueLessonId = $continueCourse->lastLessonId ?? $continueCourse->firstLessonId;
		$continueUrl      = $continueLessonId
			? add_query_arg( array( 'mint_course' => (string) $continueCourse->courseId, 'mint_lesson' => (string) $continueLessonId ), $playerUrl )
			: $playerUrl;
		$pct = (int) round( $continueCourse->progressPct );
	?>
		<div class="mint-continue-banner">
			<div class="mint-overline mint-continue-banner__overline"><?php esc_html_e( 'CONTINUE WHERE YOU LEFT OFF', 'mint-lms' ); ?></div>
			<div class="mint-continue-banner__title"><?php echo esc_html( $continueCourse->title ); ?></div>
			<div class="mint-continue-banner__meta">
				<?php
				printf(
					/* translators: 1: completed lesson number, 2: total lessons */
					esc_html__( 'Lesson %1$d of %2$d', 'mint-lms' ),
					absint( $doneLessons ),
					absint( $totalLessons )
				);
				?>
			</div>
			<div class="mint-progress mint-progress--inverse">
				<div class="mint-progress__fill" style="width:<?php echo esc_attr( (string) absint( $pct ) ); ?>%"></div>
			</div>
			<a href="<?php echo esc_url( $continueUrl ); ?>" class="mint-btn mint-btn--on-accent">
				<?php esc_html_e( 'Continue lesson', 'mint-lms' ); ?>
			</a>
		</div>
	<?php endif; ?>

	<?php if ( array() === $courses ) : ?>
		<div class="mint-student-empty">
			<p class="mint-student-lead"><?php esc_html_e( 'No courses in progress. Enroll in a course to start learning.', 'mint-lms' ); ?></p>
		</div>
	<?php else : ?>
		<div class="mint-overline mint-overline--spaced"><?php esc_html_e( 'YOUR COURSES', 'mint-lms' ); ?></div>
		<div class="mint-student-grid">
			<?php foreach ( $courses as $idx => $course ) :
				$hue     = MintUi::hueByIndex( $idx );
				$initial = MintUi::courseInitial( $course->title );
				$pctVal  = (int) round( $course->progressPct );

				$targetLesson = $course->isComplete
					? $course->firstLessonId
					: ( $course->lastLessonId ?? $course->firstLessonId );
				$actionUrl = $targetLesson
					? add_query_arg( array( 'mint_course' => (string) $course->courseId, 'mint_lesson' => (string) $targetLesson ), $playerUrl )
					: $playerUrl;
			?>
				<a href="<?php echo esc_url( $actionUrl ); ?>" class="mint-course-card">
					<div class="mint-course-card__banner" style="background:<?php echo esc_attr( $hue['bg'] ); ?>">
						<span class="mint-course-card__initial" style="color:<?php echo esc_attr( $hue['ink'] ); ?>"><?php echo esc_html( $initial ); ?></span>
					</div>
					<div class="mint-course-card__body">
						<div class="mint-course-card__title"><?php echo esc_html( $course->title ); ?></div>
						<div class="mint-progress mint-progress--sm">
							<div class="mint-progress__fill" style="width:<?php echo esc_attr( (string) absint( $pctVal ) ); ?>%"></div>
						</div>
						<div class="mint-progress-pct"><?php echo esc_html( (string) absint( $pctVal ) ); ?>%</div>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
