<?php
declare(strict_types=1);

namespace MintLMS\Http\Rest\Controller;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Application\Quiz\Dto\CreateQuestionDto;
use MintLMS\Application\Quiz\Dto\CreateQuizDto;
use MintLMS\Application\Quiz\Dto\SubmitQuizAttemptDto;
use MintLMS\Application\Quiz\Dto\UpdateQuestionDto;
use MintLMS\Application\Quiz\Dto\UpdateQuizDto;
use MintLMS\Application\Quiz\QuizService;
use MintLMS\Domain\Quiz\QuizQuestion;
use MintLMS\Http\Rest\Response\ApiResponse;
use MintLMS\Infrastructure\PostType\PostTypes;

final class QuizController {

	private const NAMESPACE = 'mintlms/v1';

	public function __construct(
		private QuizService $quizService,
		private AuthorizationInterface $authorization,
	) {
	}

	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/quizzes',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'listByCourse' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/lessons/(?P<id>\d+)/quiz',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getByLesson' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
					'args'                => $this->createQuizArgs(),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/quizzes/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get' ),
					'permission_callback' => array( $this, 'canReadQuiz' ),
				),
				array(
					'methods'             => 'PATCH',
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
					'args'                => $this->updateQuizArgs(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/quizzes/(?P<id>\d+)/questions',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'addQuestion' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
					'args'                => $this->questionArgs(),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/quizzes/(?P<id>\d+)/questions/(?P<question_id>\d+)',
			array(
				array(
					'methods'             => 'PATCH',
					'callback'            => array( $this, 'updateQuestion' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
					'args'                => $this->questionArgs( false ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'deleteQuestion' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/quizzes/(?P<id>\d+)/attempt',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'submitAttempt' ),
					'permission_callback' => array( $this, 'canTrackProgress' ),
					'args'                => $this->attemptArgs(),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/quizzes/(?P<id>\d+)/attempts',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getAttempts' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
				),
			)
		);
	}

	public function canManageCourses(): bool {
		$userId = $this->authorization->getCurrentUserId();

		return $userId > 0 && $this->authorization->canCreateCourse( $userId );
	}

	public function canTrackProgress(): bool {
		return $this->authorization->getCurrentUserId() > 0;
	}

	public function canReadQuiz(): bool {
		return $this->canManageCourses() || $this->canTrackProgress();
	}

	public function listByCourse( \WP_REST_Request $request ): \WP_REST_Response {
		$courseId = (int) $request->get_param( 'id' );

		if ( $courseId <= 0 ) {
			return ApiResponse::error( 'validation_error', 'Invalid course id.', 400 );
		}

		$query = new \WP_Query(
			array(
				'post_type'              => PostTypes::QUIZ,
				'post_status'            => array( 'publish', 'draft', 'private' ),
				'posts_per_page'         => 200,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Course-scoped quiz list for builder sidebar.
					array(
						'key'     => PostTypes::META_COURSE_ID,
						'value'   => $courseId,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		$items = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$lessonId  = (int) get_post_meta( $post->ID, PostTypes::META_LESSON_ID, true );
			$sectionId = $lessonId > 0 ? (int) get_post_meta( $lessonId, PostTypes::META_SECTION_ID, true ) : 0;

			$items[] = array(
				'id'        => (int) $post->ID,
				'title'     => $post->post_title !== '' ? $post->post_title : __( 'New Quiz', 'mint-lms' ),
				'lessonId'  => $lessonId,
				'sectionId' => $sectionId,
				'courseId'  => $courseId,
			);
		}

		return ApiResponse::success( array( 'items' => $items ) );
	}

	public function getByLesson( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId   = $this->authorization->getCurrentUserId();
			$lessonId = (int) $request->get_param( 'id' );
			$quiz     = $this->quizService->getByLessonId( $lessonId, $userId );

			if ( null === $quiz ) {
				return ApiResponse::success( null );
			}

			return ApiResponse::success( $this->quizPayload( $quiz ) );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function create( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId   = $this->authorization->getCurrentUserId();
			$lessonId = (int) $request->get_param( 'id' );

			$dto = new CreateQuizDto(
				(string) $request->get_param( 'title' ),
				(int) ( $request->get_param( 'pass_percent' ) ?? 70 ),
				is_array( $request->get_param( 'questions' ) ) ? $request->get_param( 'questions' ) : null,
			);

			$quiz = $this->quizService->create( $lessonId, $dto, $userId );

			return ApiResponse::success( $this->quizPayload( $quiz ), 201 );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function get( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$id     = (int) $request->get_param( 'id' );
			$forStudent = ! $this->canManageCourses();

			$quiz = $this->quizService->get( $id, $userId, $forStudent );

			return ApiResponse::success( $this->quizPayload( $quiz ) );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function update( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$id     = (int) $request->get_param( 'id' );

			$dto = new UpdateQuizDto(
				$request->offsetExists( 'title' ) ? (string) $request->get_param( 'title' ) : null,
				$request->offsetExists( 'pass_percent' ) ? (int) $request->get_param( 'pass_percent' ) : null,
			);

			$quiz = $this->quizService->update( $id, $dto, $userId );

			if ( $request->offsetExists( 'settings' ) && is_array( $request->get_param( 'settings' ) ) ) {
				$this->saveQuizSettings( $quiz->id, $request->get_param( 'settings' ) );
			}

			if ( $request->offsetExists( 'featured_image_id' ) ) {
				$this->saveQuizFeaturedImage( $quiz->id, (int) $request->get_param( 'featured_image_id' ) );
			}

			return ApiResponse::success( $this->quizPayload( $quiz ) );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function delete( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$id     = (int) $request->get_param( 'id' );

			$this->quizService->delete( $id, $userId );

			return ApiResponse::success( null, 204 );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function addQuestion( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$id     = (int) $request->get_param( 'id' );

			$dto = $this->questionDtoFromRequest( $request );

			$quiz = $this->quizService->addQuestion( $id, $dto, $userId );

			return ApiResponse::success( $this->quizPayload( $quiz ) );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function updateQuestion( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId     = $this->authorization->getCurrentUserId();
			$quizId     = (int) $request->get_param( 'id' );
			$questionId = (int) $request->get_param( 'question_id' );

			$dto = new UpdateQuestionDto(
				$request->offsetExists( 'type' ) ? (string) $request->get_param( 'type' ) : null,
				$request->offsetExists( 'prompt' ) ? (string) $request->get_param( 'prompt' ) : null,
				$request->offsetExists( 'options' ) && is_array( $request->get_param( 'options' ) )
					? array_values( array_map( 'strval', $request->get_param( 'options' ) ) )
					: null,
				$request->offsetExists( 'correct_answer' ) ? (string) $request->get_param( 'correct_answer' ) : null,
			);

			$quiz = $this->quizService->updateQuestion( $quizId, $questionId, $dto, $userId );

			return ApiResponse::success( $this->quizPayload( $quiz ) );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function deleteQuestion( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId     = $this->authorization->getCurrentUserId();
			$quizId     = (int) $request->get_param( 'id' );
			$questionId = (int) $request->get_param( 'question_id' );

			$quiz = $this->quizService->deleteQuestion( $quizId, $questionId, $userId );

			return ApiResponse::success( $this->quizPayload( $quiz ) );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function submitAttempt( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$id     = (int) $request->get_param( 'id' );
			$raw    = $request->get_param( 'answers' );
			$answers = array();

			if ( is_array( $raw ) ) {
				foreach ( $raw as $questionId => $answer ) {
					$answers[ (int) $questionId ] = (string) $answer;
				}
			}

			$dto     = new SubmitQuizAttemptDto( $answers );
			$attempt = $this->quizService->submitAttempt( $id, $dto, $userId );

			return ApiResponse::success( $attempt->toArray() );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function getAttempts( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId   = $this->authorization->getCurrentUserId();
			$id       = (int) $request->get_param( 'id' );
			$attempts = $this->quizService->getAttempts( $id, $userId );

			return ApiResponse::success(
				array_map(
					static fn( $attempt ): array => $attempt->toArray(),
					$attempts
				)
			);
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function createQuizArgs(): array {
		return array(
			'title'        => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'pass_percent' => array(
				'type'              => 'integer',
				'default'           => 70,
				'sanitize_callback' => 'absint',
			),
			'questions'    => array(
				'type' => 'array',
			),
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function updateQuizArgs(): array {
		return array(
			'title'              => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'pass_percent'       => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'featured_image_id'  => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'settings'           => array(
				'type' => 'object',
			),
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function questionArgs( bool $requireAll = true ): array {
		$args = array(
			'type'            => array(
				'type'              => 'string',
				'required'          => $requireAll,
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => static function ( mixed $value ): bool {
					return in_array( (string) $value, array( QuizQuestion::TYPE_MCQ, QuizQuestion::TYPE_TRUE_FALSE ), true );
				},
			),
			'prompt'          => array(
				'type'              => 'string',
				'required'          => $requireAll,
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'options'         => array(
				'type'     => 'array',
				'required' => false,
			),
			'correct_answer'  => array(
				'type'              => 'string',
				'required'          => $requireAll,
				'sanitize_callback' => 'sanitize_text_field',
			),
		);

		return $args;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function attemptArgs(): array {
		return array(
			'answers' => array(
				'type'     => 'object',
				'required' => true,
			),
		);
	}

	private function questionDtoFromRequest( \WP_REST_Request $request ): CreateQuestionDto {
		$options = $request->get_param( 'options' );

		return new CreateQuestionDto(
			(string) $request->get_param( 'type' ),
			(string) $request->get_param( 'prompt' ),
			is_array( $options ) ? array_values( array_map( 'strval', $options ) ) : array(),
			(string) $request->get_param( 'correct_answer' ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function quizPayload( \MintLMS\Application\Quiz\Dto\QuizDto $quiz ): array {
		$data = $quiz->toArray();
		$data['settings']         = $this->readQuizSettings( $quiz->id );
		$data['featuredImageId']  = null;
		$data['featuredImageUrl'] = '';

		$thumbId = (int) get_post_thumbnail_id( $quiz->id );
		if ( $thumbId > 0 ) {
			$data['featuredImageId']  = $thumbId;
			$url                      = wp_get_attachment_image_url( $thumbId, 'large' );
			$data['featuredImageUrl'] = is_string( $url ) ? $url : '';
		}

		return $data;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function defaultQuizSettings(): array {
		return array(
			'restrictRetakes'     => false,
			'retriesAllowed'      => 0,
			'retriesApplicableTo' => 'all',
			'questionCompletion'  => false,
			'timeLimitEnabled'    => false,
			'timeLimitHours'      => '00',
			'timeLimitMinutes'    => '00',
			'timeLimitSeconds'    => '00',
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function readQuizSettings( int $quizId ): array {
		$raw = get_post_meta( $quizId, PostTypes::META_QUIZ_SETTINGS, true );
		if ( ! is_array( $raw ) ) {
			return $this->defaultQuizSettings();
		}

		return array_merge( $this->defaultQuizSettings(), $raw );
	}

	/**
	 * @param array<string, mixed> $settings
	 */
	private function saveQuizSettings( int $quizId, array $settings ): void {
		$defaults = $this->defaultQuizSettings();
		$clean    = array(
			'restrictRetakes'     => ! empty( $settings['restrictRetakes'] ),
			'retriesAllowed'      => absint( $settings['retriesAllowed'] ?? 0 ),
			'retriesApplicableTo' => sanitize_key( (string) ( $settings['retriesApplicableTo'] ?? 'all' ) ),
			'questionCompletion'  => ! empty( $settings['questionCompletion'] ),
			'timeLimitEnabled'    => ! empty( $settings['timeLimitEnabled'] ),
			'timeLimitHours'      => $this->sanitizeTimePart( $settings['timeLimitHours'] ?? '00' ),
			'timeLimitMinutes'    => $this->sanitizeTimePart( $settings['timeLimitMinutes'] ?? '00' ),
			'timeLimitSeconds'    => $this->sanitizeTimePart( $settings['timeLimitSeconds'] ?? '00' ),
		);

		if ( ! in_array( $clean['retriesApplicableTo'], array( 'all' ), true ) ) {
			$clean['retriesApplicableTo'] = 'all';
		}

		update_post_meta( $quizId, PostTypes::META_QUIZ_SETTINGS, array_merge( $defaults, $clean ) );
	}

	private function sanitizeTimePart( mixed $value ): string {
		$digits = preg_replace( '/\D+/', '', (string) $value );
		if ( ! is_string( $digits ) || '' === $digits ) {
			return '00';
		}

		return str_pad( substr( $digits, 0, 2 ), 2, '0', STR_PAD_LEFT );
	}

	private function saveQuizFeaturedImage( int $quizId, int $attachmentId ): void {
		if ( $attachmentId <= 0 ) {
			delete_post_thumbnail( $quizId );
			return;
		}

		if ( 'attachment' !== get_post_type( $attachmentId ) ) {
			return;
		}

		set_post_thumbnail( $quizId, $attachmentId );
	}
}
