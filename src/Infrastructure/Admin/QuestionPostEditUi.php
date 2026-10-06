<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;
use MintLMS\Infrastructure\PostType\QuestionLinkResolver;

/**
 * Native WP question editor (post.php) ↔ Mint question builder.
 *
 * Default edit surface is classic WP. Mint builder opens via Publish box link.
 */
final class QuestionPostEditUi {

	public function register(): void {
		add_action( 'post_submitbox_misc_actions', array( $this, 'renderSubmitBoxLink' ) );
	}

	public function renderSubmitBoxLink( \WP_Post $post ): void {
		if ( PostTypes::QUESTION !== $post->post_type || $post->ID <= 0 ) {
			return;
		}

		$url = self::questionBuilderUrl( (int) $post->ID );

		echo '<div class="misc-pub-section">';
		echo '<a class="button button-primary" style="width:100%;text-align:center;" href="' . esc_url( $url ) . '">';
		echo esc_html__( 'Open Question In Mint LMS Builder', 'mint-lms' );
		echo '</a>';
		echo '</div>';
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
