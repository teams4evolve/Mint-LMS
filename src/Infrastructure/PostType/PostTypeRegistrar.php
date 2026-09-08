<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\PostType;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Domain\Lesson\Lesson;
use MintLMS\Domain\Quiz\Quiz;
use MintLMS\Domain\Section\Section;
use MintLMS\Infrastructure\Database\Repository\WpdbSectionRepository;
use MintLMS\Infrastructure\Database\Repository\WpPostCourseRepository;
use MintLMS\Infrastructure\Database\Repository\WpPostLessonRepository;
use MintLMS\Infrastructure\Database\Repository\WpPostQuizRepository;

final class PostTypeRegistrar {

	public function register(): void {
		add_action( 'init', array( $this, 'registerPostTypes' ) );
		add_action( 'init', array( $this, 'registerStatuses' ), 11 );
		add_action( 'admin_menu', array( $this, 'registerStarterPages' ) );
		add_action( 'admin_init', array( $this, 'redirectLegacyCptLists' ) );
		add_action( 'load-post.php', array( $this, 'redirectContentEditToBuilder' ) );
		add_action( 'load-post-new.php', array( $this, 'redirectContentCreateToBuilder' ) );
		add_action( 'load-admin_page_mint-lms-new-lesson', array( $this, 'bootstrapNewLesson' ) );
		add_action( 'load-admin_page_mint-lms-new-quiz', array( $this, 'bootstrapNewQuiz' ) );
		add_action( 'load-admin_page_mint-lms-edit-quiz', array( $this, 'bootstrapEditQuiz' ) );
	}

	/**
	 * Hidden admin entrypoints for Add New Lesson / Quiz (more reliable than post-new.php).
	 */
	public function registerStarterPages(): void {
		add_submenu_page(
			'',
			__( 'New Lesson', 'mint-lms' ),
			__( 'New Lesson', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms-new-lesson',
			'__return_null'
		);

		add_submenu_page(
			'',
			__( 'New Quiz', 'mint-lms' ),
			__( 'New Quiz', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms-new-quiz',
			'__return_null'
		);

		add_submenu_page(
			'',
			__( 'Edit Quiz', 'mint-lms' ),
			__( 'Edit Quiz', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms-edit-quiz',
			'__return_null'
		);
	}

	public function bootstrapNewLesson(): void {
		$this->bootstrapStarter( 'lesson' );
	}

	public function bootstrapNewQuiz(): void {
		$this->bootstrapStarter( 'quiz' );
	}

	/**
	 * Quizzes list Edit → resolve course/lesson (repair meta / untrash) → builder.
	 */
	public function bootstrapEditQuiz(): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to edit content.', 'mint-lms' ) );
		}

		$quizId = isset( $_GET['quiz_id'] ) ? absint( $_GET['quiz_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect gate.

		if ( $quizId <= 0 ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mint-lms-quizzes' ) );
			exit;
		}

		try {
			$target = $this->resolveQuizBuilderTarget( $quizId );
		} catch ( \Throwable $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Surface resolve failure without white-screen.
			error_log( 'Mint LMS edit quiz resolve failed: ' . $e->getMessage() );
			wp_safe_redirect( admin_url( 'admin.php?page=mint-lms-quizzes' ) );
			exit;
		}

		if ( null === $target ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mint-lms-quizzes' ) );
			exit;
		}

		wp_safe_redirect(
			admin_url(
				'admin.php?page=mint-lms-builder&course_id=' . $target['course_id']
				. '&lesson_id=' . $target['lesson_id']
				. '&quiz_id=' . $quizId
				. '&from=quizzes&tab=quiz'
			)
		);
		exit;
	}

	/**
	 * @param 'lesson'|'quiz' $type
	 */
	private function bootstrapStarter( string $type ): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to create content.', 'mint-lms' ) );
		}

		try {
			if ( 'quiz' === $type ) {
				$created = $this->createNewQuizStarter();
				wp_safe_redirect(
					admin_url(
						'admin.php?page=mint-lms-builder&course_id=' . $created['course_id']
						. '&lesson_id=' . $created['lesson_id']
						. '&quiz_id=' . $created['quiz_id']
						. '&from=quizzes&tab=quiz'
					)
				);
				exit;
			}

			// Add New Lesson → empty builder. User chooses Add section or Add lesson.
			$created = $this->createEmptyCourseStarter();
			wp_safe_redirect(
				admin_url(
					'admin.php?page=mint-lms-builder&course_id=' . $created['course_id']
					. '&from=lessons'
				)
			);
			exit;
		} catch ( \Throwable $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Surface create failure without white-screen.
			error_log( 'Mint LMS new content starter failed: ' . $e->getMessage() );
			$fallback = 'quiz' === $type ? 'mint-lms-quizzes' : 'mint-lms-lessons';
			wp_safe_redirect( admin_url( 'admin.php?page=' . $fallback ) );
			exit;
		}
	}

	public function registerPostTypes(): void {
		// Use standard post caps. Do NOT remap edit_post → edit_mintlms_courses:
		// that turns the Mint menu primitive into a meta cap and breaks current_user_can().
		$common = array(
			'public'              => false,
			'publicly_queryable'  => false,
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		);

		// Courses stay hidden — Mint uses the custom Courses screen.
		register_post_type(
			PostTypes::COURSE,
			array_merge(
				$common,
				array(
					'labels'       => $this->labels(
						__( 'Courses', 'mint-lms' ),
						__( 'Course', 'mint-lms' )
					),
					'show_ui'      => false,
					'show_in_menu' => false,
					'supports'     => array( 'title', 'editor', 'thumbnail', 'author' ),
				)
			)
		);

		register_post_type(
			PostTypes::LESSON,
			array_merge(
				$common,
				array(
					'labels'       => $this->labels(
						__( 'Lessons', 'mint-lms' ),
						__( 'Lesson', 'mint-lms' )
					),
					'show_ui'      => true,
					'show_in_menu' => false,
					'supports'     => array( 'title', 'editor', 'thumbnail', 'author' ),
				)
			)
		);

		register_post_type(
			PostTypes::QUIZ,
			array_merge(
				$common,
				array(
					'labels'       => $this->labels(
						__( 'Quizzes', 'mint-lms' ),
						__( 'Quiz', 'mint-lms' )
					),
					'show_ui'      => true,
					'show_in_menu' => false,
					'supports'     => array( 'title', 'author' ),
				)
			)
		);

		register_post_type(
			PostTypes::QUESTION,
			array_merge(
				$common,
				array(
					'labels'       => $this->labels(
						__( 'Questions', 'mint-lms' ),
						__( 'Question', 'mint-lms' )
					),
					'show_ui'      => true,
					'show_in_menu' => false,
					'supports'     => array( 'title', 'editor', 'author' ),
				)
			)
		);
	}

	public function registerStatuses(): void {
		register_post_status(
			PostTypes::STATUS_ARCHIVED,
			array(
				'label'                     => _x( 'Archived', 'course status', 'mint-lms' ),
				'public'                    => false,
				'internal'                  => true,
				'protected'                 => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => false,
			)
		);
	}

	/**
	 * Native WP CPT lists → Mint A8 / A9 screens.
	 */
	public function redirectLegacyCptLists(): void {
		global $pagenow;

		if ( 'edit.php' !== $pagenow ) {
			return;
		}

		$postType = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( (string) $_GET['post_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect.

		if ( PostTypes::LESSON === $postType ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mint-lms-lessons' ) );
			exit;
		}

		if ( PostTypes::QUIZ === $postType ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mint-lms-quizzes' ) );
			exit;
		}
	}

	/**
	 * Classic WP lesson / quiz editor → Mint course builder.
	 * Does not intercept trash / delete / restore — those must stay native WP actions.
	 */
	public function redirectContentEditToBuilder(): void {
		$action = 'edit';

		if ( isset( $_REQUEST['action'] ) && is_string( $_REQUEST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Gate only; WP handles trash nonce.
			$action = sanitize_key( wp_unslash( $_REQUEST['action'] ) );
		}

		if ( in_array( $action, array( 'trash', 'delete', 'untrash', 'deletepermanently' ), true ) ) {
			return;
		}

		$postId = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect gate.

		if ( $postId <= 0 ) {
			return;
		}

		$post = get_post( $postId );

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			return;
		}

		if ( PostTypes::LESSON === $post->post_type ) {
			$this->redirectLessonPost( $postId );
			return;
		}

		if ( PostTypes::QUIZ === $post->post_type ) {
			$this->redirectQuizPost( $postId );
		}
	}

	/**
	 * "Add New Lesson / Quiz" → starter content in the builder.
	 */
	public function redirectContentCreateToBuilder(): void {
		$postType = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( (string) $_GET['post_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect gate.

		if ( PostTypes::LESSON !== $postType && PostTypes::QUIZ !== $postType ) {
			return;
		}

		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			return;
		}

		try {
			if ( PostTypes::QUIZ === $postType ) {
				$created = $this->createNewQuizStarter();
				wp_safe_redirect(
					admin_url(
						'admin.php?page=mint-lms-builder&course_id=' . $created['course_id']
						. '&lesson_id=' . $created['lesson_id']
						. '&quiz_id=' . $created['quiz_id']
						. '&from=quizzes&tab=quiz'
					)
				);
				exit;
			}

			$created = $this->createEmptyCourseStarter();
		} catch ( \Throwable $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Surface create failure without white-screen.
			error_log( 'Mint LMS new content starter failed: ' . $e->getMessage() );
			$fallback = PostTypes::QUIZ === $postType ? 'mint-lms-quizzes' : 'mint-lms-lessons';
			wp_safe_redirect( admin_url( 'admin.php?page=' . $fallback ) );
			exit;
		}

		wp_safe_redirect(
			admin_url(
				'admin.php?page=mint-lms-builder&course_id=' . $created['course_id']
				. '&from=lessons'
			)
		);
		exit;
	}

	private function redirectLessonPost( int $postId ): void {
		$courseId = (int) get_post_meta( $postId, PostTypes::META_COURSE_ID, true );

		if ( $courseId <= 0 ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mint-lms-lessons' ) );
			exit;
		}

		wp_safe_redirect(
			admin_url(
				'admin.php?page=mint-lms-builder&course_id=' . $courseId . '&lesson_id=' . $postId . '&from=lessons'
			)
		);
		exit;
	}

	private function redirectQuizPost( int $postId ): void {
		try {
			$target = $this->resolveQuizBuilderTarget( $postId );
		} catch ( \Throwable $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Surface resolve failure without white-screen.
			error_log( 'Mint LMS quiz edit redirect failed: ' . $e->getMessage() );
			$target = null;
		}

		if ( null === $target ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mint-lms-quizzes' ) );
			exit;
		}

		wp_safe_redirect(
			admin_url(
				'admin.php?page=mint-lms-builder&course_id=' . $target['course_id']
				. '&lesson_id=' . $target['lesson_id']
				. '&quiz_id=' . $postId
				. '&from=quizzes&tab=quiz'
			)
		);
		exit;
	}

	/**
	 * Ensure a quiz has usable course/lesson links for the builder.
	 *
	 * @return array{course_id: int, lesson_id: int}|null
	 */
	private function resolveQuizBuilderTarget( int $quizId ): ?array {
		return ( new QuizLinkResolver() )->ensureForBuilder(
			$quizId,
			fn (): array => $this->createNewLessonStarter()
		);
	}

	/**
	 * Empty course only — used by Add New Lesson (no auto section/lesson).
	 *
	 * @return array{course_id: int}
	 */
	private function createEmptyCourseStarter(): array {
		$now    = new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
		$userId = get_current_user_id();
		if ( $userId <= 0 ) {
			$userId = 1;
		}

		$courseRepo = new WpPostCourseRepository();
		$course     = $courseRepo->save(
			new Course(
				0,
				__( 'Untitled Course', 'mint-lms' ),
				'untitled-course-' . wp_generate_password( 6, false, false ),
				'',
				null,
				CourseStatus::Draft,
				EnrollmentType::Open,
				$userId,
				$now,
				$now,
			)
		);

		return array(
			'course_id' => $course->id,
		);
	}

	/**
	 * Course + section + lesson — used by quiz starter only.
	 *
	 * @return array{course_id: int, lesson_id: int}
	 */
	private function createNewLessonStarter(): array {
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			throw new \RuntimeException( 'Database unavailable.' );
		}

		$now    = new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
		$userId = get_current_user_id();
		if ( $userId <= 0 ) {
			$userId = 1;
		}

		$created     = $this->createEmptyCourseStarter();
		$lessonRepo  = new WpPostLessonRepository();
		$sectionRepo = new WpdbSectionRepository( $wpdb, $lessonRepo );

		$section = $sectionRepo->save(
			new Section(
				0,
				$created['course_id'],
				__( 'New Section', 'mint-lms' ),
				0,
				$now,
			)
		);

		$lesson = $lessonRepo->save(
			new Lesson(
				0,
				$section->id,
				$created['course_id'],
				__( 'New Lesson', 'mint-lms' ),
				'new-lesson-' . wp_generate_password( 4, false, false ),
				'',
				null,
				null,
				false,
				null,
				0,
				$now,
				$now,
			)
		);

		return array(
			'course_id' => $created['course_id'],
			'lesson_id' => $lesson->id,
		);
	}

	/**
	 * @return array{course_id: int, lesson_id: int, quiz_id: int}
	 */
	private function createNewQuizStarter(): array {
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			throw new \RuntimeException( 'Database unavailable.' );
		}

		$created  = $this->createNewLessonStarter();
		$quizRepo = new WpPostQuizRepository( $wpdb );

		$quiz = $quizRepo->saveQuiz(
			new Quiz(
				0,
				$created['lesson_id'],
				$created['course_id'],
				__( 'New Quiz', 'mint-lms' ),
				80,
				0,
			)
		);

		return array(
			'course_id' => $created['course_id'],
			'lesson_id' => $created['lesson_id'],
			'quiz_id'   => $quiz->id,
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function labels( string $plural, string $singular ): array {
		return array(
			'name'               => $plural,
			'singular_name'      => $singular,
			'add_new'            => __( 'Add New', 'mint-lms' ),
			/* translators: %s: singular post type label */
			'add_new_item'       => sprintf( __( 'Add New %s', 'mint-lms' ), $singular ),
			/* translators: %s: singular post type label */
			'edit_item'          => sprintf( __( 'Edit %s', 'mint-lms' ), $singular ),
			/* translators: %s: singular post type label */
			'new_item'           => sprintf( __( 'New %s', 'mint-lms' ), $singular ),
			/* translators: %s: singular post type label */
			'view_item'          => sprintf( __( 'View %s', 'mint-lms' ), $singular ),
			/* translators: %s: plural post type label */
			'search_items'       => sprintf( __( 'Search %s', 'mint-lms' ), $plural ),
			/* translators: %s: plural post type label */
			'not_found'          => sprintf( __( 'No %s found.', 'mint-lms' ), strtolower( $plural ) ),
			/* translators: %s: plural post type label */
			'not_found_in_trash' => sprintf( __( 'No %s found in Trash.', 'mint-lms' ), strtolower( $plural ) ),
			'menu_name'          => $plural,
			'all_items'          => $plural,
		);
	}
}
