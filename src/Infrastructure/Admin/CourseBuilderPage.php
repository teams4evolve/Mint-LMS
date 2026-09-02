<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

final class CourseBuilderPage {

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'registerPages' ) );
		add_action( 'load-admin_page_mint-lms-builder', array( $this, 'setBuilderTitle' ) );
		add_action( 'load-admin_page_mint-lms-course-edit', array( $this, 'setEditTitle' ) );
	}

	public function setBuilderTitle(): void {
		global $title;
		$title = __( 'Course Builder', 'mint-lms' );
	}

	public function setEditTitle(): void {
		global $title;
		$title = __( 'Edit Course', 'mint-lms' );
	}

	public function registerPages(): void {
		add_submenu_page(
			'',
			__( 'Course Builder', 'mint-lms' ),
			__( 'Course Builder', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms-builder',
			array( $this, 'renderBuilder' )
		);

		add_submenu_page(
			'',
			__( 'Edit Course', 'mint-lms' ),
			__( 'Edit Course', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms-course-edit',
			array( $this, 'renderEdit' )
		);
	}

	public function renderBuilder(): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to access the course builder.', 'mint-lms' ) );
		}

		$courseId = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $courseId <= 0 ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mint-lms-courses' ) );
			exit;
		}

		$renderer = new ViewRenderer();
		$renderer->echo(
			'admin/courses/builder',
			array(
				'renderer' => $renderer,
				'courseId' => $courseId,
			)
		);
	}

	public function renderEdit(): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to edit courses.', 'mint-lms' ) );
		}

		$courseId = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $courseId <= 0 ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mint-lms-courses' ) );
			exit;
		}

		$renderer = new ViewRenderer();
		$renderer->echo(
			'admin/courses/edit',
			array(
				'renderer' => $renderer,
				'courseId' => $courseId,
			)
		);
	}
}
