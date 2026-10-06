<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;
use MintLMS\Infrastructure\PostType\QuestionLinkResolver;

/**
 * Native WP question editor (Gutenberg / classic) ↔ Mint question builder.
 *
 * Default Add New / Edit surface is WordPress. Mint builder opens via sidebar link.
 */
final class QuestionPostEditUi {

	public function register(): void {
		add_filter( 'use_block_editor_for_post_type', array( $this, 'enableBlockEditor' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'registerBuilderMetaBox' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'renderSubmitBoxLink' ) );
	}

	/**
	 * @param bool   $use_block_editor Whether the post type uses the block editor.
	 * @param string $post_type        Post type slug.
	 */
	public function enableBlockEditor( bool $use_block_editor, string $post_type ): bool {
		if ( PostTypes::QUESTION === $post_type ) {
			return true;
		}

		return $use_block_editor;
	}

	public function registerBuilderMetaBox(): void {
		add_meta_box(
			'mintlms-question-builder-link',
			__( 'Mint LMS', 'mint-lms' ),
			array( $this, 'renderBuilderMetaBox' ),
			PostTypes::QUESTION,
			'side',
			'high'
		);
	}

	/**
	 * @param \WP_Post $post Post being edited.
	 */
	public function renderBuilderMetaBox( \WP_Post $post ): void {
		if ( ! PostTypes::hasExplicitSave( $post ) ) {
			echo '<p>' . esc_html__( 'Save the question as a draft or publish it first, then open it in Mint LMS Builder.', 'mint-lms' ) . '</p>';
			return;
		}

		$this->renderBuilderButton( (int) $post->ID );
	}

	public function renderSubmitBoxLink( \WP_Post $post ): void {
		if ( PostTypes::QUESTION !== $post->post_type || ! PostTypes::hasExplicitSave( $post ) ) {
			return;
		}

		echo '<div class="misc-pub-section mint-lms-question-submitbox">';
		$this->renderBuilderButton( (int) $post->ID );
		echo '</div>';
	}

	private function renderBuilderButton( int $questionId ): void {
		echo '<a class="button button-primary" style="width:100%;text-align:center;box-sizing:border-box;" href="' . esc_url( self::questionBuilderUrl( $questionId ) ) . '">';
		echo esc_html__( 'Open Question In Mint LMS Builder', 'mint-lms' );
		echo '</a>';
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
