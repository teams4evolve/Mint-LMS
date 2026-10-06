<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * Native WP lesson editor (post.php) ↔ Mint lesson builder.
 *
 * Default edit surface is classic WP. Mint builder opens via Publish box link.
 */
final class LessonPostEditUi {

	public function register(): void {
		add_action( 'post_submitbox_misc_actions', array( $this, 'renderSubmitBoxLink' ) );
	}

	public function renderSubmitBoxLink( \WP_Post $post ): void {
		if ( PostTypes::LESSON !== $post->post_type || $post->ID <= 0 ) {
			return;
		}

		$builderUrl = self::lessonBuilderUrl( (int) $post->ID );

		echo '<div class="misc-pub-section mint-lms-lesson-submitbox">';
		echo '<a class="button button-primary" style="width:100%;text-align:center;" href="' . esc_url( $builderUrl ) . '">';
		echo esc_html__( 'Open Lesson In Mint LMS Builder', 'mint-lms' );
		echo '</a>';
		echo '</div>';
	}

	/**
	 * Canonical Mint URL for editing a lesson in the custom builder.
	 */
	public static function lessonBuilderUrl( int $lessonId ): string {
		if ( $lessonId <= 0 ) {
			return PostTypes::listUrl( PostTypes::LESSON );
		}

		$courseId = (int) get_post_meta( $lessonId, PostTypes::META_COURSE_ID, true );

		if ( $courseId <= 0 ) {
			return admin_url(
				'admin.php?page=mint-lms-lesson-edit&lesson_id=' . $lessonId . '&from=lessons'
			);
		}

		return admin_url(
			'admin.php?page=mint-lms-builder&course_id=' . $courseId
			. '&lesson_id=' . $lessonId
			. '&from=lessons'
		);
	}
}
