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

	public function getByLesson( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId   = $this->authorization->getCurrentUserId();
			$lessonId = (int) $request->get_param( 'id' );
			$quiz     = $this->quizService->getByLessonId( $lessonId, $userId );

			if ( null === $quiz ) {
				return ApiResponse::success( null );
			}

			return ApiResponse::success( $quiz->toArray() );
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

			return ApiResponse::success( $quiz->toArray(), 201 );
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

			return ApiResponse::success( $quiz->toArray() );
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

			return ApiResponse::success( $quiz->toArray() );
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

			return ApiResponse::success( $quiz->toArray() );
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

			return ApiResponse::success( $quiz->toArray() );
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

			return ApiResponse::success( $quiz->toArray() );
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
			'title'        => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'pass_percent' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
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
}
