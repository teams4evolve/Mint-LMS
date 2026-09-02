<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

use MintLMS\Infrastructure\Ui\MintUi;

/** @var array<string, mixed> $summary */

$activity = is_array( $summary['activity'] ?? null ) ? $summary['activity'] : array();
?>
<div class="mint-list-shell">
	<div class="mint-list-head">
		<div>
			<h1 class="mint-page-title"><?php esc_html_e( 'Reports', 'mint-lms' ); ?></h1>
			<p class="mint-page-subtitle"><?php esc_html_e( 'Overview of courses, enrollments, and recent activity.', 'mint-lms' ); ?></p>
		</div>
	</div>

	<div class="mint-metric-strip" style="margin-top:24px">
		<?php
		$metrics = array(
			array(
				'label' => __( 'Courses', 'mint-lms' ),
				'value' => (string) absint( $summary['total_courses'] ?? 0 ),
				'sub'   => __( 'total courses', 'mint-lms' ),
			),
			array(
				'label' => __( 'Students', 'mint-lms' ),
				'value' => (string) absint( $summary['total_students'] ?? 0 ),
				'sub'   => __( 'active enrollments', 'mint-lms' ),
			),
			array(
				'label' => __( 'Enrollments', 'mint-lms' ),
				'value' => (string) absint( $summary['total_enrollments'] ?? 0 ),
				'sub'   => __( 'all time', 'mint-lms' ),
			),
			array(
				'label' => __( 'Completions', 'mint-lms' ),
				'value' => (string) absint( $summary['total_completions'] ?? 0 ),
				'sub'   => __( 'courses finished', 'mint-lms' ),
			),
		);

		foreach ( $metrics as $cell ) :
			?>
			<div class="mint-metric-cell">
				<div class="mint-metric-label"><?php echo esc_html( $cell['label'] ); ?></div>
				<div class="mint-t-stat"><?php echo esc_html( $cell['value'] ); ?></div>
				<div class="mint-t-xs mint-text-muted"><?php echo esc_html( $cell['sub'] ); ?></div>
			</div>
		<?php endforeach; ?>
	</div>

	<div style="margin-top:32px">
		<div class="mint-section-label"><?php esc_html_e( 'Lessons completed', 'mint-lms' ); ?></div>
		<div class="mint-t-stat" style="margin-top:8px"><?php echo esc_html( (string) absint( $summary['lessons_completed'] ?? 0 ) ); ?></div>
		<p class="mint-t-sm mint-text-muted" style="margin-top:4px"><?php esc_html_e( 'Individual lesson completions recorded across all courses.', 'mint-lms' ); ?></p>
	</div>

	<?php if ( array() !== $activity ) : ?>
	<div class="mint-activity-section" style="margin-top:40px">
		<div class="mint-section-label"><?php esc_html_e( 'Recent activity', 'mint-lms' ); ?></div>
		<?php
		$index = 0;
		foreach ( $activity as $row ) :
			if ( ! is_array( $row ) ) {
				continue;
			}
			$user = get_userdata( (int) ( $row['user_id'] ?? 0 ) );
			$name = $user instanceof WP_User ? $user->display_name : __( 'A student', 'mint-lms' );
			$hue  = MintUi::hueByIndex( $index );
			++$index;
			$course = (string) ( $row['course_title'] ?? '' );
			$type   = (string) ( $row['type'] ?? '' );
			if ( 'completed' === $type ) {
				$text = sprintf(
					/* translators: 1: student name, 2: course title */
					__( '%1$s finished %2$s', 'mint-lms' ),
					$name,
					$course
				);
			} elseif ( 'enrolled' === $type ) {
				$text = sprintf(
					/* translators: 1: student name, 2: course title */
					__( '%1$s enrolled in %2$s', 'mint-lms' ),
					$name,
					$course
				);
			} else {
				$text = sprintf(
					/* translators: 1: student name, 2: course title */
					__( '%1$s made progress in %2$s', 'mint-lms' ),
					$name,
					$course
				);
			}
			$when = isset( $row['occurred_at'] ) ? MintUi::relativeTimeShort( new DateTimeImmutable( (string) $row['occurred_at'] ) ) : '';
			?>
			<div class="mint-activity-row">
				<div class="mint-avatar mint-avatar--sm" style="background:<?php echo esc_attr( $hue['bg'] ); ?>;color:<?php echo esc_attr( $hue['ink'] ); ?>"><?php echo esc_html( MintUi::initials( $name ) ); ?></div>
				<div class="mint-t-sm mint-activity-row__text"><?php echo esc_html( $text ); ?></div>
				<div class="mint-t-xs mint-tabular mint-activity-row__when"><?php echo esc_html( $when ); ?></div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php else : ?>
	<p class="mint-t-body mint-text-muted" style="margin-top:40px"><?php esc_html_e( 'No recent activity yet.', 'mint-lms' ); ?></p>
	<?php endif; ?>
</div>
