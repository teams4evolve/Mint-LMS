<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * Native WP quiz editor (Gutenberg / classic) ↔ Mint quiz builder.
 *
 * Default Add New / Edit surface is WordPress. Mint builder opens via sidebar link.
 */
final class QuizPostEditUi {

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
		if ( PostTypes::QUIZ === $post_type ) {
			return true;
		}

		return $use_block_editor;
	}

	public function registerBuilderMetaBox(): void {
		add_meta_box(
			'mintlms-quiz-builder-link',
			__( 'Mint LMS', 'mint-lms' ),
			array( $this, 'renderBuilderMetaBox' ),
			PostTypes::QUIZ,
			'side',
			'high'
		);
	}

	/**
	 * @param \WP_Post $post Post being edited.
	 */
	public function renderBuilderMetaBox( \WP_Post $post ): void {
		if ( $post->ID <= 0 ) {
			echo '<p>' . esc_html__( 'Save the quiz first, then open it in Mint LMS Builder.', 'mint-lms' ) . '</p>';
			return;
		}

		$this->renderBuilderButton( (int) $post->ID );
	}

	public function renderSubmitBoxLink( \WP_Post $post ): void {
		if ( PostTypes::QUIZ !== $post->post_type || $post->ID <= 0 ) {
			return;
		}

		echo '<div class="misc-pub-section mint-lms-quiz-submitbox">';
		$this->renderBuilderButton( (int) $post->ID );
		echo '</div>';
	}

	private function renderBuilderButton( int $quizId ): void {
		echo '<a class="button button-primary" style="width:100%;text-align:center;box-sizing:border-box;" href="' . esc_url( self::quizBuilderUrl( $quizId ) ) . '">';
		echo esc_html__( 'Open Quiz In Mint LMS Builder', 'mint-lms' );
		echo '</a>';
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
