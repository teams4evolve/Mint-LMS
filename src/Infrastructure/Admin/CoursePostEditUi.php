<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * Native WP course editor (post.php) ↔ Mint Course builder.
 *
 * Default edit surface is classic WP. Mint builder opens via Publish box "Open in builder".
 */
final class CoursePostEditUi {

	public function register(): void {
		add_action( 'post_submitbox_misc_actions', array( $this, 'renderSubmitBoxLink' ) );
	}

	public function renderSubmitBoxLink( \WP_Post $post ): void {
		if ( PostTypes::COURSE !== $post->post_type || $post->ID <= 0 ) {
			return;
		}

		$builderUrl = $this->courseBuilderUrl( (int) $post->ID );

		echo '<div class="misc-pub-section mint-lms-course-submitbox">';
		echo '<a class="button button-primary" style="width:100%;text-align:center;" href="' . esc_url( $builderUrl ) . '">';
		echo esc_html__( 'Open In Mint LMS Builder', 'mint-lms' );
		echo '</a>';
		echo '</div>';
	}

	private function courseBuilderUrl( int $courseId ): string {
		return admin_url( 'admin.php?page=mint-lms-builder&course_id=' . $courseId );
	}
}
