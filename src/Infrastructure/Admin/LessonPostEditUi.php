<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * Native WP lesson editor (post.php) ↔ Mint lesson builder.
 *
 * Default edit surface is classic WP. Mint custom UI opens via "Open in lesson builder".
 */
final class LessonPostEditUi {

	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'registerMetaBoxes' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'renderSubmitBoxLink' ) );
		add_action( 'admin_notices', array( $this, 'renderEditNotice' ) );
	}

	public function registerMetaBoxes(): void {
		add_meta_box(
			'mint-lms-lesson-builder',
			__( 'Mint LMS', 'mint-lms' ),
			array( $this, 'renderMetaBox' ),
			PostTypes::LESSON,
			'side',
			'high'
		);
	}

	public function renderMetaBox( \WP_Post $post ): void {
		if ( PostTypes::LESSON !== $post->post_type || $post->ID <= 0 ) {
			return;
		}

		$builderUrl = self::lessonBuilderUrl( (int) $post->ID );

		echo '<p class="mint-lms-lesson-metabox__copy">';
		echo esc_html__( 'Use the Mint lesson builder for written/video content, quizzes, and course placement.', 'mint-lms' );
		echo '</p>';
		echo '<p>';
		echo '<a class="button button-primary button-large" href="' . esc_url( $builderUrl ) . '">';
		echo esc_html__( 'Open in lesson builder', 'mint-lms' );
		echo '</a>';
		echo '</p>';
	}

	public function renderSubmitBoxLink( \WP_Post $post ): void {
		if ( PostTypes::LESSON !== $post->post_type || $post->ID <= 0 ) {
			return;
		}

		$builderUrl = self::lessonBuilderUrl( (int) $post->ID );

		echo '<div class="misc-pub-section mint-lms-lesson-submitbox">';
		echo '<a class="button button-primary" style="width:100%;text-align:center;" href="' . esc_url( $builderUrl ) . '">';
		echo esc_html__( 'Open in lesson builder', 'mint-lms' );
		echo '</a>';
		echo '</div>';
	}

	public function renderEditNotice(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || PostTypes::LESSON !== $screen->post_type || 'post' !== $screen->base ) {
			return;
		}

		$postId = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice link.
		if ( $postId <= 0 ) {
			return;
		}

		$builderUrl = self::lessonBuilderUrl( $postId );

		echo '<div class="notice notice-info"><p>';
		echo esc_html__( 'This is the WordPress lesson editor.', 'mint-lms' );
		echo ' ';
		echo '<a href="' . esc_url( $builderUrl ) . '"><strong>';
		echo esc_html__( 'Open in lesson builder', 'mint-lms' );
		echo '</strong></a>';
		echo ' ';
		echo esc_html__( 'for the Mint lesson experience.', 'mint-lms' );
		echo '</p></div>';
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
