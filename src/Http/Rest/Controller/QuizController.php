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
use MintLMS\Application\Quiz\QuestionAnswerCodec;
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
			'/quizzes/(?P<id>\d+)/attach',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'attach' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
					'args'                => array(
						'lesson_id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/quizzes/(?P<id>\d+)/detach',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'detach' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/questions/(?P<id>\d+)/attach',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'attachQuestion' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
					'args'                => array(
						'quiz_id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/questions/(?P<id>\d+)/detach',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'detachQuestion' ),
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
				'id'          => (int) $post->ID,
				'title'       => $post->post_title !== '' ? $post->post_title : __( 'New Quiz', 'mint-lms' ),
				'lessonId'    => $lessonId,
				'lessonTitle' => $lessonId > 0 ? (string) get_the_title( $lessonId ) : '',
				'sectionId'   => $sectionId,
				'courseId'    => $courseId,
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

	public function attach( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId   = $this->authorization->getCurrentUserId();
			$quizId   = (int) $request->get_param( 'id' );
			$lessonId = (int) $request->get_param( 'lesson_id' );

			$quiz = $this->quizService->attachToLesson( $quizId, $lessonId, $userId );

			return ApiResponse::success( $this->quizPayload( $quiz ) );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function detach( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$quizId = (int) $request->get_param( 'id' );

			$quiz = $this->quizService->detachFromLesson( $quizId, $userId );

			return ApiResponse::success( $this->quizPayload( $quiz ) );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function attachQuestion( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId     = $this->authorization->getCurrentUserId();
			$questionId = (int) $request->get_param( 'id' );
			$quizId     = (int) $request->get_param( 'quiz_id' );

			$quiz = $this->quizService->attachQuestionToQuiz( $questionId, $quizId, $userId );

			return ApiResponse::success( $this->quizPayload( $quiz ) );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function detachQuestion( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId     = $this->authorization->getCurrentUserId();
			$questionId = (int) $request->get_param( 'id' );

			$quiz = $this->quizService->detachQuestionFromQuiz( $questionId, $userId );

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

			if ( $request->offsetExists( 'featured_image_id' ) ) {
				$questions = $quiz->questions;
				$last      = end( $questions );
				if ( $last instanceof \MintLMS\Application\Quiz\Dto\QuizQuestionDto && $last->id > 0 ) {
					$this->saveQuestionFeaturedImage( $last->id, (int) $request->get_param( 'featured_image_id' ) );
					if ( $request->offsetExists( 'settings' ) && is_array( $request->get_param( 'settings' ) ) ) {
						$this->saveQuestionSettings( $last->id, $request->get_param( 'settings' ) );
					}
				}
			} elseif ( $request->offsetExists( 'settings' ) && is_array( $request->get_param( 'settings' ) ) ) {
				$questions = $quiz->questions;
				$last      = end( $questions );
				if ( $last instanceof \MintLMS\Application\Quiz\Dto\QuizQuestionDto && $last->id > 0 ) {
					$this->saveQuestionSettings( $last->id, $request->get_param( 'settings' ) );
				}
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

	public function updateQuestion( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId     = $this->authorization->getCurrentUserId();
			$quizId     = (int) $request->get_param( 'id' );
			$questionId = (int) $request->get_param( 'question_id' );

			$correctAnswer = null;
			if ( $request->offsetExists( 'correct_answer' ) ) {
				$rawCorrect = $request->get_param( 'correct_answer' );
				$correctAnswer = is_array( $rawCorrect )
					? QuestionAnswerCodec::encodeMulti( array_map( 'strval', $rawCorrect ) )
					: (string) $rawCorrect;
			}

			$dto = new UpdateQuestionDto(
				$request->offsetExists( 'type' ) ? (string) $request->get_param( 'type' ) : null,
				$request->offsetExists( 'prompt' ) ? (string) $request->get_param( 'prompt' ) : null,
				$request->offsetExists( 'options' ) && is_array( $request->get_param( 'options' ) )
					? array_values( array_map( 'strval', $request->get_param( 'options' ) ) )
					: null,
				$correctAnswer,
			);

			$quiz = $this->quizService->updateQuestion( $quizId, $questionId, $dto, $userId );

			if ( $request->offsetExists( 'featured_image_id' ) ) {
				$this->saveQuestionFeaturedImage( $questionId, (int) $request->get_param( 'featured_image_id' ) );
			}

			if ( $request->offsetExists( 'settings' ) && is_array( $request->get_param( 'settings' ) ) ) {
				$this->saveQuestionSettings( $questionId, $request->get_param( 'settings' ) );
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
			$raw     = $request->get_param( 'answers' );
			$answers = array();

			if ( is_array( $raw ) ) {
				foreach ( $raw as $questionId => $answer ) {
					if ( is_array( $answer ) ) {
						$answers[ (int) $questionId ] = QuestionAnswerCodec::encodeMulti( array_map( 'strval', $answer ) );
					} else {
						$answers[ (int) $questionId ] = (string) $answer;
					}
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
					return in_array( (string) $value, QuizQuestion::types(), true );
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
				'required' => $requireAll,
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
		$type    = (string) $request->get_param( 'type' );
		$opts    = is_array( $options ) ? array_values( array_map( 'strval', $options ) ) : array();
		$correct = QuestionAnswerCodec::normalizeForType( $type, $request->get_param( 'correct_answer' ), $opts );

		return new CreateQuestionDto(
			$type,
			(string) $request->get_param( 'prompt' ),
			$opts,
			$correct,
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
		$data['status']           = $this->mintPostStatus( (string) get_post_status( $quiz->id ) );

		$thumbId = (int) get_post_thumbnail_id( $quiz->id );
		if ( $thumbId > 0 ) {
			$data['featuredImageId']  = $thumbId;
			$url                      = wp_get_attachment_image_url( $thumbId, 'large' );
			$data['featuredImageUrl'] = is_string( $url ) ? $url : '';
		}

		if ( isset( $data['questions'] ) && is_array( $data['questions'] ) ) {
			foreach ( $data['questions'] as $index => $question ) {
				if ( ! is_array( $question ) || empty( $question['id'] ) ) {
					continue;
				}
				$questionId = (int) $question['id'];
				$qThumbId   = (int) get_post_thumbnail_id( $questionId );
				$data['questions'][ $index ]['featuredImageId']  = $qThumbId > 0 ? $qThumbId : null;
				$data['questions'][ $index ]['featuredImageUrl'] = '';
				$data['questions'][ $index ]['status']           = $this->mintPostStatus( (string) get_post_status( $questionId ) );
				if ( $qThumbId > 0 ) {
					$qUrl = wp_get_attachment_image_url( $qThumbId, 'large' );
					$data['questions'][ $index ]['featuredImageUrl'] = is_string( $qUrl ) ? $qUrl : '';
				}
				$data['questions'][ $index ]['settings'] = $this->readQuestionSettings( $questionId );
			}
		}

		return $data;
	}

	private function mintPostStatus( string $wpStatus ): string {
		return match ( $wpStatus ) {
			'publish' => 'published',
			'private', PostTypes::STATUS_ARCHIVED => 'archived',
			'trash'   => 'trashed',
			default   => 'draft',
		};
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

	private function saveQuestionFeaturedImage( int $questionId, int $attachmentId ): void {
		if ( $questionId <= 0 ) {
			return;
		}

		if ( $attachmentId <= 0 ) {
			delete_post_thumbnail( $questionId );
			return;
		}

		if ( 'attachment' !== get_post_type( $attachmentId ) ) {
			return;
		}

		set_post_thumbnail( $questionId, $attachmentId );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function defaultQuestionSettings(): array {
		return array(
			'freePreview'       => false,
			'attachmentId'      => 0,
			'attachmentName'    => '',
			'attachmentUrl'     => '',
			'allowHtml'         => array(),
			'submitMethod'      => 'Text Box',
			'gradingMode'       => 'Not Graded, No Points Awarded',
			'points'            => 1,
			'extraTf'           => array(),
			'displayTitle'      => '',
			'answerTypePending' => false,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function readQuestionSettings( int $questionId ): array {
		$raw = get_post_meta( $questionId, PostTypes::META_QUESTION_SETTINGS, true );
		if ( ! is_array( $raw ) ) {
			return $this->defaultQuestionSettings();
		}

		$settings = array_merge( $this->defaultQuestionSettings(), $raw );
		$settings['freePreview']    = ! empty( $settings['freePreview'] );
		$settings['attachmentId']   = absint( $settings['attachmentId'] ?? 0 );
		$settings['attachmentName'] = sanitize_text_field( (string) ( $settings['attachmentName'] ?? '' ) );
		$settings['attachmentUrl']  = esc_url_raw( (string) ( $settings['attachmentUrl'] ?? '' ) );
		$settings['allowHtml']      = isset( $settings['allowHtml'] ) && is_array( $settings['allowHtml'] )
			? array_map( static fn( $v ): bool => (bool) $v, array_values( $settings['allowHtml'] ) )
			: array();
		$settings['submitMethod']   = in_array( (string) ( $settings['submitMethod'] ?? '' ), array( 'Text Box', 'Upload' ), true )
			? (string) $settings['submitMethod']
			: 'Text Box';
		$allowedGrading = array(
			'-- Select --',
			'Not Graded, No Points Awarded',
			'Not Graded, Full Points Awarded',
			'Graded, Full Points Awarded',
		);
		$settings['gradingMode'] = in_array( (string) ( $settings['gradingMode'] ?? '' ), $allowedGrading, true )
			? (string) $settings['gradingMode']
			: 'Not Graded, No Points Awarded';
		$settings['points'] = max( 0, absint( $settings['points'] ?? 1 ) );
		$settings['extraTf'] = $this->sanitizeExtraTfList( $settings['extraTf'] ?? array() );
		$settings['displayTitle'] = sanitize_text_field( (string) ( $settings['displayTitle'] ?? '' ) );
		$settings['answerTypePending'] = ! empty( $settings['answerTypePending'] );

		return $settings;
	}

	/**
	 * @param mixed $list
	 * @return list<array<string, mixed>>
	 */
	private function sanitizeExtraTfList( mixed $list ): array {
		if ( ! is_array( $list ) ) {
			return array();
		}

		$clean = array();
		foreach ( $list as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$answer = (string) ( $item['correctAnswer'] ?? '' );
			if ( ! in_array( $answer, array( 'true', 'false', '' ), true ) ) {
				$answer = '';
			}
			$clean[] = array(
				'id'             => absint( $item['id'] ?? 0 ) ?: null,
				'prompt'         => sanitize_textarea_field( (string) ( $item['prompt'] ?? '' ) ),
				'correctAnswer'  => $answer,
				'freePreview'    => ! empty( $item['freePreview'] ),
				'attachmentId'   => absint( $item['attachmentId'] ?? 0 ),
				'attachmentName' => sanitize_text_field( (string) ( $item['attachmentName'] ?? '' ) ),
				'attachmentUrl'  => esc_url_raw( (string) ( $item['attachmentUrl'] ?? '' ) ),
			);
		}

		return $clean;
	}

	/**
	 * @param array<string, mixed> $settings
	 */
	private function saveQuestionSettings( int $questionId, array $settings ): void {
		if ( $questionId <= 0 ) {
			return;
		}

		$clean = $this->readQuestionSettings( $questionId );
		if ( array_key_exists( 'freePreview', $settings ) ) {
			$clean['freePreview'] = ! empty( $settings['freePreview'] );
		}
		if ( array_key_exists( 'attachmentId', $settings ) ) {
			$clean['attachmentId'] = absint( $settings['attachmentId'] );
		}
		if ( array_key_exists( 'attachmentName', $settings ) ) {
			$clean['attachmentName'] = sanitize_text_field( (string) $settings['attachmentName'] );
		}
		if ( array_key_exists( 'attachmentUrl', $settings ) ) {
			$clean['attachmentUrl'] = esc_url_raw( (string) $settings['attachmentUrl'] );
		}
		if ( array_key_exists( 'allowHtml', $settings ) && is_array( $settings['allowHtml'] ) ) {
			$clean['allowHtml'] = array_map( static fn( $v ): bool => (bool) $v, array_values( $settings['allowHtml'] ) );
		}
		if ( array_key_exists( 'submitMethod', $settings ) ) {
			$method = (string) $settings['submitMethod'];
			$clean['submitMethod'] = in_array( $method, array( 'Text Box', 'Upload' ), true ) ? $method : 'Text Box';
		}
		if ( array_key_exists( 'gradingMode', $settings ) ) {
			$mode = (string) $settings['gradingMode'];
			$allowed = array(
				'-- Select --',
				'Not Graded, No Points Awarded',
				'Not Graded, Full Points Awarded',
				'Graded, Full Points Awarded',
			);
			$clean['gradingMode'] = in_array( $mode, $allowed, true ) ? $mode : $clean['gradingMode'];
		}
		if ( array_key_exists( 'points', $settings ) ) {
			$clean['points'] = max( 0, absint( $settings['points'] ) );
		}
		if ( array_key_exists( 'extraTf', $settings ) ) {
			$clean['extraTf'] = $this->sanitizeExtraTfList( $settings['extraTf'] );
		}
		if ( array_key_exists( 'displayTitle', $settings ) ) {
			$clean['displayTitle'] = sanitize_text_field( (string) $settings['displayTitle'] );
		}
		if ( array_key_exists( 'answerTypePending', $settings ) ) {
			$clean['answerTypePending'] = ! empty( $settings['answerTypePending'] );
		}

		update_post_meta( $questionId, PostTypes::META_QUESTION_SETTINGS, $clean );
	}
}
