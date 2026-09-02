<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

use MintLMS\Application\Student\Dto\StudentCatalogCourseDto;
use MintLMS\Infrastructure\Ui\MintUi;

/** @var list<StudentCatalogCourseDto> $courses */
$courses         = $courses ?? array();
$courseUrl       = $courseUrl ?? static fn( int $id ): string => home_url( '/' );
$excerptLength   = 140;
$pageTitle       = __( 'Courses', 'mint-lms' );
$userName        = '';
include MINTLMS_PATH . 'views/student/partials/student-header.php';
?>

<div class="mint-student-page">

	<div class="mint-student-hero">
		<h1 class="mint-student-greeting"><?php esc_html_e( 'Courses', 'mint-lms' ); ?></h1>
		<p class="mint-student-lead"><?php esc_html_e( 'Browse available courses and start learning.', 'mint-lms' ); ?></p>
	</div>

	<?php if ( array() === $courses ) : ?>
		<div class="mint-student-empty">
			<p class="mint-student-lead"><?php esc_html_e( 'No courses are available yet. Check back soon.', 'mint-lms' ); ?></p>
		</div>
	<?php else : ?>
		<div class="mint-student-grid">
			<?php foreach ( $courses as $idx => $course ) :
				$hue         = MintUi::hueByIndex( $idx );
				$initial     = MintUi::courseInitial( $course->title );
				$overviewUrl = $courseUrl( $course->courseId );
				$excerpt     = wp_trim_words( wp_strip_all_tags( $course->description ), 20, '…' );
				if ( '' === $excerpt && strlen( $course->description ) > $excerptLength ) {
					$excerpt = substr( wp_strip_all_tags( $course->description ), 0, $excerptLength ) . '…';
				}
			?>
				<article class="mint-course-card mint-course-card--static">
					<a href="<?php echo esc_url( $overviewUrl ); ?>" class="mint-course-card__link" aria-label="<?php echo esc_attr( $course->title ); ?>">
						<div class="mint-course-card__banner mint-course-card__banner--tall" style="background:<?php echo esc_attr( $hue['bg'] ); ?>">
							<span class="mint-course-card__initial mint-course-card__initial--lg" style="color:<?php echo esc_attr( $hue['ink'] ); ?>"><?php echo esc_html( $initial ); ?></span>
						</div>
					</a>
					<div class="mint-course-card__body mint-course-card__body--tall">
						<h2 class="mint-course-card__title mint-course-card__title--lg">
							<a href="<?php echo esc_url( $overviewUrl ); ?>" class="mint-course-card__title-link"><?php echo esc_html( $course->title ); ?></a>
						</h2>
						<?php if ( '' !== $excerpt ) : ?>
							<p class="mint-course-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
						<?php endif; ?>
						<div class="mint-course-card__meta"><?php echo esc_html( ucfirst( $course->enrollmentType ) ); ?></div>
						<a href="<?php echo esc_url( $overviewUrl ); ?>" class="mint-btn mint-btn--primary mint-course-card__cta">
							<?php esc_html_e( 'View course', 'mint-lms' ); ?>
						</a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
