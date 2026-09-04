<?php
declare(strict_types=1);

namespace MintLMS\Http\Rest\Controller;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Course\CourseService;
use MintLMS\Application\Course\CourseStructureService;
use MintLMS\Application\Course\Dto\CreateCourseDto;
use MintLMS\Application\Course\Dto\UpdateCourseDto;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Http\Rest\Response\ApiResponse;
use MintLMS\Infrastructure\Http\RestContentSanitizer;
use MintLMS\Infrastructure\Setup\PageSettings;

final class CourseController {

	private const NAMESPACE = 'mintlms/v1';

	public function __construct(
		private CourseService $courseService,
		private CourseStructureService $courseStructureService,
		private AuthorizationInterface $authorization,
	) {
	}

	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/courses',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list' ),
					'permission_callback' => array( $this, 'canListCourses' ),
					'args'                => $this->listArgs(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( $this, 'canCreateCourse' ),
					'args'                => $this->createArgs(),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
				),
				array(
					'methods'             => 'PATCH',
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
					'args'                => $this->updateArgs(),
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
			'/courses/(?P<id>\d+)/structure',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'structure' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/publish',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'publish' ),
				'permission_callback' => array( $this, 'canManageCourses' ),
			)
		);
	}

	public function canListCourses(): bool {
		$userId = $this->authorization->getCurrentUserId();

		return $userId > 0 && $this->authorization->canListCourses( $userId );
	}

	public function canCreateCourse(): bool {
		$userId = $this->authorization->getCurrentUserId();

		return $userId > 0 && $this->authorization->canCreateCourse( $userId );
	}

	public function canManageCourses(): bool {
		$userId = $this->authorization->getCurrentUserId();

		return $userId > 0 && $this->authorization->canCreateCourse( $userId );
	}

	public function list( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$page   = (int) $request->get_param( 'page' );
			$per    = (int) $request->get_param( 'per_page' );
			$status = $request->get_param( 'status' );

			$statusEnum = null;
			if ( is_string( $status ) && '' !== $status ) {
				$statusEnum = CourseStatus::from( $status );
			}

			$search = $request->get_param( 'search' );
			$search = is_string( $search ) && '' !== trim( $search ) ? trim( $search ) : null;

			$result = $this->courseService->list( $page, $per, $userId, $statusEnum, $search );

			return ApiResponse::success( $result->toArray() );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		} catch ( \ValueError $exception ) {
			return ApiResponse::error( 'invalid_status', 'Invalid course status.', 400 );
		}
	}

	public function create( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();

			$enrollmentParam = $request->get_param( 'enrollment_type' );
			$enrollmentType  = is_string( $enrollmentParam ) && '' !== trim( $enrollmentParam )
				? trim( $enrollmentParam )
				: ( new PageSettings() )->getDefaultEnrollment();

			$dto = new CreateCourseDto(
				(string) $request->get_param( 'title' ),
				$this->nullableString( $request->get_param( 'slug' ) ),
				(string) ( $request->get_param( 'description' ) ?? '' ),
				$this->nullableInt( $request->get_param( 'featured_image_id' ) ),
				EnrollmentType::from( $enrollmentType ),
			);

			$course = $this->courseService->create( $dto, $userId );

			return ApiResponse::success( $course->toArray(), 201 );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function get( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$id     = (int) $request->get_param( 'id' );

			$course = $this->courseService->get( $id, $userId );

			return ApiResponse::success( $course->toArray() );
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

			$dto = new UpdateCourseDto(
				$request->offsetExists( 'title' ) ? (string) $request->get_param( 'title' ) : null,
				$request->offsetExists( 'slug' ) ? $this->nullableString( $request->get_param( 'slug' ) ) : null,
				$request->offsetExists( 'description' ) ? (string) $request->get_param( 'description' ) : null,
				$request->offsetExists( 'featured_image_id' ) ? $this->nullableInt( $request->get_param( 'featured_image_id' ) ) : null,
				$request->offsetExists( 'enrollment_type' )
					? EnrollmentType::from( (string) $request->get_param( 'enrollment_type' ) )
					: null,
				$request->offsetExists( 'status' )
					? CourseStatus::from( (string) $request->get_param( 'status' ) )
					: null,
			);

			$course = $this->courseService->update( $id, $dto, $userId );

			return ApiResponse::success( $course->toArray() );
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

			$this->courseService->delete( $id, $userId );

			return ApiResponse::success( null, 204 );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function structure( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$id     = (int) $request->get_param( 'id' );

			$structure = $this->courseStructureService->getStructure( $id, $userId );

			return ApiResponse::success( $structure->toArray() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function publish( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$id     = (int) $request->get_param( 'id' );

			$course = $this->courseService->publish( $id, $userId );

			return ApiResponse::success( $course->toArray() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function listArgs(): array {
		return array(
			'page'     => array(
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'type'              => 'integer',
				'default'           => 20,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
			),
			'status'   => array(
				'type'              => 'string',
				'enum'              => array( 'draft', 'published', 'archived' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'search'   => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function createArgs(): array {
		return array(
			'title'             => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'slug'              => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_title',
			),
			'description'       => array(
				'type'              => 'string',
				'sanitize_callback' => array( RestContentSanitizer::class, 'richText' ),
			),
			'featured_image_id' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'enrollment_type'   => array(
				'type'              => 'string',
				'default'           => 'open',
				'enum'              => array( 'open', 'manual' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function updateArgs(): array {
		return array(
			'title'             => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'slug'              => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_title',
			),
			'description'       => array(
				'type'              => 'string',
				'sanitize_callback' => array( RestContentSanitizer::class, 'richText' ),
			),
			'featured_image_id' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'enrollment_type'   => array(
				'type'              => 'string',
				'enum'              => array( 'open', 'manual' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'status'            => array(
				'type'              => 'string',
				'enum'              => array( 'draft', 'published', 'archived' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	private function nullableString( mixed $value ): ?string {
		if ( null === $value || '' === $value ) {
			return null;
		}

		return (string) $value;
	}

	private function nullableInt( mixed $value ): ?int {
		if ( null === $value || '' === $value ) {
			return null;
		}

		return (int) $value;
	}
}
