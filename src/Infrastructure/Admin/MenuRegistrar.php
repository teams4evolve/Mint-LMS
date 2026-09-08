<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

final class MenuRegistrar {

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'registerMenu' ) );
	}

	public function registerMenu(): void {
		add_menu_page(
			__( 'Mint LMS', 'mint-lms' ),
			__( 'Mint LMS', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms',
			array( $this, 'renderDashboard' ),
			'dashicons-welcome-learn-more',
			30
		);

		add_submenu_page(
			'mint-lms',
			__( 'Dashboard', 'mint-lms' ),
			__( 'Dashboard', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms',
			array( $this, 'renderDashboard' )
		);

		add_submenu_page(
			'mint-lms',
			__( 'Courses', 'mint-lms' ),
			__( 'Courses', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms-courses',
			array( $this, 'renderCourses' )
		);

		add_submenu_page(
			'mint-lms',
			__( 'Lessons', 'mint-lms' ),
			__( 'Lessons', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms-lessons',
			array( $this, 'renderLessons' )
		);

		add_submenu_page(
			'mint-lms',
			__( 'Quizzes', 'mint-lms' ),
			__( 'Quizzes', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms-quizzes',
			array( $this, 'renderQuizzes' )
		);

		add_submenu_page(
			'mint-lms',
			__( 'Questions', 'mint-lms' ),
			__( 'Questions', 'mint-lms' ),
			'edit_mintlms_courses',
			'edit.php?post_type=' . PostTypes::QUESTION
		);

		add_submenu_page(
			'mint-lms',
			__( 'Students', 'mint-lms' ),
			__( 'Students', 'mint-lms' ),
			'enroll_mintlms_students',
			'mint-lms-course-students',
			array( $this, 'renderCourseStudents' )
		);

		add_submenu_page(
			'mint-lms',
			__( 'Reports', 'mint-lms' ),
			__( 'Reports', 'mint-lms' ),
			'view_mintlms_reports',
			'mint-lms-reports',
			array( $this, 'renderReports' )
		);

		add_submenu_page(
			'',
			__( 'Guided first course', 'mint-lms' ),
			__( 'Guided first course', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms-guided-course',
			array( $this, 'renderGuidedCourse' )
		);
	}

	public function renderDashboard(): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to access Mint LMS.', 'mint-lms' ) );
		}

		if ( ! FirstRunState::isFirstRunComplete() ) {
			include MINTLMS_PATH . 'views/admin/first-run.php';
			return;
		}

		include MINTLMS_PATH . 'views/admin/dashboard.php';
	}

	public function renderCourses(): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to access courses.', 'mint-lms' ) );
		}

		include MINTLMS_PATH . 'views/admin/courses/list.php';
	}

	public function renderLessons(): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to access lessons.', 'mint-lms' ) );
		}

		include MINTLMS_PATH . 'views/admin/lessons/list.php';
	}

	public function renderQuizzes(): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to access quizzes.', 'mint-lms' ) );
		}

		include MINTLMS_PATH . 'views/admin/quizzes/list.php';
	}

	public function renderCourseStudents(): void {
		if ( ! current_user_can( 'enroll_mintlms_students' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage enrollments.', 'mint-lms' ) );
		}

		include MINTLMS_PATH . 'views/admin/courses/students.php';
	}

	public function renderGuidedCourse(): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to access Mint LMS.', 'mint-lms' ) );
		}

		include MINTLMS_PATH . 'views/admin/guided-course.php';
	}

	public function renderReports(): void {
		if ( ! current_user_can( 'view_mintlms_reports' ) ) {
			wp_die( esc_html__( 'You do not have permission to view reports.', 'mint-lms' ) );
		}

		include MINTLMS_PATH . 'views/admin/reports.php';
	}
}
