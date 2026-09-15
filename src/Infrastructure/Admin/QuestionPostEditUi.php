<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;
use MintLMS\Infrastructure\PostType\QuestionLinkResolver;

/**
 * Native WP question editor (post.php) ↔ Mint question builder.
 */
final class QuestionPostEditUi {

	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'registerMetaBoxes' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'renderSubmitBoxLink' ) );
		add_action( 'admin_notices', array( $this, 'renderEditNotice' ) );
	}

	public function registerMetaBoxes(): void {
		add_meta_box(
			'mint-lms-question-builder',
			__( 'Mint LMS', 'mint-lms' ),
			array( $this, 'renderMetaBox' ),
			PostTypes::QUESTION,
			'side',
			'high'
		);
	}

	public function renderMetaBox( \WP_Post $post ): void {
		if ( PostTypes::QUESTION !== $post->post_type || $post->ID <= 0 ) {
			return;
		}

		$url = self::questionBuilderUrl( (int) $post->ID );

		echo '<p>';
		echo esc_html__( 'Use the Mint question builder for answer types, options, and quiz placement.', 'mint-lms' );
		echo '</p>';
		echo '<p>';
		echo '<a class="button button-primary button-large" href="' . esc_url( $url ) . '">';
		echo esc_html__( 'Open in question builder', 'mint-lms' );
		echo '</a>';
		echo '</p>';
	}

	public function renderSubmitBoxLink( \WP_Post $post ): void {
		if ( PostTypes::QUESTION !== $post->post_type || $post->ID <= 0 ) {
			return;
		}

		$url = self::questionBuilderUrl( (int) $post->ID );

		echo '<div class="misc-pub-section">';
		echo '<a class="button button-primary" style="width:100%;text-align:center;" href="' . esc_url( $url ) . '">';
		echo esc_html__( 'Open in question builder', 'mint-lms' );
		echo '</a>';
		echo '</div>';
	}

	public function renderEditNotice(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || PostTypes::QUESTION !== $screen->post_type || 'post' !== $screen->base ) {
			return;
		}

		$postId = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $postId <= 0 ) {
			return;
		}

		$url = self::questionBuilderUrl( $postId );

		echo '<div class="notice notice-info"><p>';
		echo esc_html__( 'This is the WordPress question editor.', 'mint-lms' );
		echo ' ';
		echo '<a href="' . esc_url( $url ) . '"><strong>';
		echo esc_html__( 'Open in question builder', 'mint-lms' );
		echo '</strong></a>';
		echo '</p></div>';
	}

	/**
	 * Routes through the quiz resolver with question_id so Mint opens the question surface.
	 */
	public static function questionBuilderUrl( int $questionId ): string {
		if ( $questionId <= 0 ) {
			return PostTypes::listUrl( PostTypes::QUESTION );
		}

		$quizId = ( new QuestionLinkResolver() )->findQuizId( $questionId );

		if ( $quizId <= 0 ) {
			return admin_url( 'admin.php?page=mint-lms-edit-quiz&question_id=' . $questionId );
		}

		return admin_url(
			'admin.php?page=mint-lms-edit-quiz&quiz_id=' . $quizId
			. '&question_id=' . $questionId
		);
	}
}
