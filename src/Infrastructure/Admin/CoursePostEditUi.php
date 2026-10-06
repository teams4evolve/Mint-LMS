<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * Native WP course editor (Gutenberg / classic) ↔ Mint Course builder.
 *
 * Default Add New / Edit surface is WordPress. Mint builder opens via sidebar link.
 */
final class CoursePostEditUi {

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
		if ( PostTypes::COURSE === $post_type ) {
			return true;
		}

		return $use_block_editor;
	}

	public function registerBuilderMetaBox(): void {
		add_meta_box(
			'mintlms-course-builder-link',
			__( 'Mint LMS', 'mint-lms' ),
			array( $this, 'renderBuilderMetaBox' ),
			PostTypes::COURSE,
			'side',
			'high'
		);
	}

	/**
	 * @param \WP_Post $post Post being edited.
	 */
	public function renderBuilderMetaBox( \WP_Post $post ): void {
		if ( ! PostTypes::hasExplicitSave( $post ) ) {
			echo '<p>' . esc_html__( 'Save the course as a draft or publish it first, then open it in Mint LMS Builder.', 'mint-lms' ) . '</p>';
			return;
		}

		$this->renderBuilderButton( (int) $post->ID );
	}

	public function renderSubmitBoxLink( \WP_Post $post ): void {
		if ( PostTypes::COURSE !== $post->post_type || ! PostTypes::hasExplicitSave( $post ) ) {
			return;
		}

		echo '<div class="misc-pub-section mint-lms-course-submitbox">';
		$this->renderBuilderButton( (int) $post->ID );
		echo '</div>';
	}

	private function renderBuilderButton( int $courseId ): void {
		echo '<a class="button button-primary" style="width:100%;text-align:center;box-sizing:border-box;" href="' . esc_url( $this->courseBuilderUrl( $courseId ) ) . '">';
		echo esc_html__( 'Open Course In Mint LMS Builder', 'mint-lms' );
		echo '</a>';
	}

	private function courseBuilderUrl( int $courseId ): string {
		return admin_url( 'admin.php?page=mint-lms-builder&course_id=' . $courseId );
	}
}
