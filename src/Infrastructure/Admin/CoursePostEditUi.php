<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * Native WP course editor (post.php) ↔ Mint Course settings / builder.
 *
 * Default edit surface is classic WP. Mint custom UI opens via "Open in builder".
 */
final class CoursePostEditUi {

	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'registerMetaBoxes' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'renderSubmitBoxLink' ) );
		add_action( 'admin_notices', array( $this, 'renderEditNotice' ) );
	}

	public function registerMetaBoxes(): void {
		add_meta_box(
			'mint-lms-course-builder',
			__( 'Mint LMS', 'mint-lms' ),
			array( $this, 'renderMetaBox' ),
			PostTypes::COURSE,
			'side',
			'high'
		);
	}

	public function renderMetaBox( \WP_Post $post ): void {
		if ( PostTypes::COURSE !== $post->post_type || $post->ID <= 0 ) {
			return;
		}

		$settingsUrl = $this->courseSettingsUrl( (int) $post->ID );
		$builderUrl  = $this->courseBuilderUrl( (int) $post->ID );

		echo '<p class="mint-lms-course-metabox__copy">';
		echo esc_html__( 'Use Mint Course settings for enrollment, commerce, and structure.', 'mint-lms' );
		echo '</p>';
		echo '<p>';
		echo '<a class="button button-primary button-large" href="' . esc_url( $settingsUrl ) . '">';
		echo esc_html__( 'Open in builder', 'mint-lms' );
		echo '</a>';
		echo '</p>';
		echo '<p class="description">';
		echo '<a href="' . esc_url( $builderUrl ) . '">';
		echo esc_html__( 'Open course content tree', 'mint-lms' );
		echo '</a>';
		echo '</p>';
	}

	public function renderSubmitBoxLink( \WP_Post $post ): void {
		if ( PostTypes::COURSE !== $post->post_type || $post->ID <= 0 ) {
			return;
		}

		$settingsUrl = $this->courseSettingsUrl( (int) $post->ID );

		echo '<div class="misc-pub-section mint-lms-course-submitbox">';
		echo '<a class="button button-primary" style="width:100%;text-align:center;" href="' . esc_url( $settingsUrl ) . '">';
		echo esc_html__( 'Open in builder', 'mint-lms' );
		echo '</a>';
		echo '</div>';
	}

	public function renderEditNotice(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || PostTypes::COURSE !== $screen->post_type || 'post' !== $screen->base ) {
			return;
		}

		$postId = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice link.
		if ( $postId <= 0 ) {
			return;
		}

		$settingsUrl = $this->courseSettingsUrl( $postId );

		echo '<div class="notice notice-info"><p>';
		echo esc_html__( 'This is the WordPress course editor.', 'mint-lms' );
		echo ' ';
		echo '<a href="' . esc_url( $settingsUrl ) . '"><strong>';
		echo esc_html__( 'Open in builder', 'mint-lms' );
		echo '</strong></a>';
		echo ' ';
		echo esc_html__( 'for Mint Course settings and content structure.', 'mint-lms' );
		echo '</p></div>';
	}

	private function courseSettingsUrl( int $courseId ): string {
		return admin_url( 'admin.php?page=mint-lms-course-edit&course_id=' . $courseId );
	}

	private function courseBuilderUrl( int $courseId ): string {
		return admin_url( 'admin.php?page=mint-lms-builder&course_id=' . $courseId );
	}
}
