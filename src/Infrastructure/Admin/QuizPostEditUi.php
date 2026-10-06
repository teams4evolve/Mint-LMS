<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * Native WP quiz editor (post.php) ↔ Mint quiz builder.
 *
 * Default edit surface is classic WP. Mint builder opens via Publish box link.
 */
final class QuizPostEditUi {

	public function register(): void {
		add_action( 'post_submitbox_misc_actions', array( $this, 'renderSubmitBoxLink' ) );
	}

	public function renderSubmitBoxLink( \WP_Post $post ): void {
		if ( PostTypes::QUIZ !== $post->post_type || $post->ID <= 0 ) {
			return;
		}

		$url = self::quizBuilderUrl( (int) $post->ID );

		echo '<div class="misc-pub-section">';
		echo '<a class="button button-primary" style="width:100%;text-align:center;" href="' . esc_url( $url ) . '">';
		echo esc_html__( 'Open Quiz In Mint LMS Builder', 'mint-lms' );
		echo '</a>';
		echo '</div>';
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
