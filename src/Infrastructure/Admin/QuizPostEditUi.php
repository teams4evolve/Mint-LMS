<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * Native WP quiz editor (post.php) ↔ Mint quiz builder.
 */
final class QuizPostEditUi {

	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'registerMetaBoxes' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'renderSubmitBoxLink' ) );
		add_action( 'admin_notices', array( $this, 'renderEditNotice' ) );
	}

	public function registerMetaBoxes(): void {
		add_meta_box(
			'mint-lms-quiz-builder',
			__( 'Mint LMS', 'mint-lms' ),
			array( $this, 'renderMetaBox' ),
			PostTypes::QUIZ,
			'side',
			'high'
		);
	}

	public function renderMetaBox( \WP_Post $post ): void {
		if ( PostTypes::QUIZ !== $post->post_type || $post->ID <= 0 ) {
			return;
		}

		$url = self::quizBuilderUrl( (int) $post->ID );

		echo '<p>';
		echo esc_html__( 'Use the Mint quiz builder for questions, pass settings, and course placement.', 'mint-lms' );
		echo '</p>';
		echo '<p>';
		echo '<a class="button button-primary button-large" href="' . esc_url( $url ) . '">';
		echo esc_html__( 'Open in quiz builder', 'mint-lms' );
		echo '</a>';
		echo '</p>';
	}

	public function renderSubmitBoxLink( \WP_Post $post ): void {
		if ( PostTypes::QUIZ !== $post->post_type || $post->ID <= 0 ) {
			return;
		}

		$url = self::quizBuilderUrl( (int) $post->ID );

		echo '<div class="misc-pub-section">';
		echo '<a class="button button-primary" style="width:100%;text-align:center;" href="' . esc_url( $url ) . '">';
		echo esc_html__( 'Open in quiz builder', 'mint-lms' );
		echo '</a>';
		echo '</div>';
	}

	public function renderEditNotice(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || PostTypes::QUIZ !== $screen->post_type || 'post' !== $screen->base ) {
			return;
		}

		$postId = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $postId <= 0 ) {
			return;
		}

		$url = self::quizBuilderUrl( $postId );

		echo '<div class="notice notice-info"><p>';
		echo esc_html__( 'This is the WordPress quiz editor.', 'mint-lms' );
		echo ' ';
		echo '<a href="' . esc_url( $url ) . '"><strong>';
		echo esc_html__( 'Open in quiz builder', 'mint-lms' );
		echo '</strong></a>';
		echo '</p></div>';
	}

	/**
	 * Routes through the quiz resolver so lesson/course meta is repaired, then opens Mint quiz UI.
	 */
	public static function quizBuilderUrl( int $quizId ): string {
		if ( $quizId <= 0 ) {
			return PostTypes::listUrl( PostTypes::QUIZ );
		}

		return admin_url( 'admin.php?page=mint-lms-edit-quiz&quiz_id=' . $quizId );
	}
}
