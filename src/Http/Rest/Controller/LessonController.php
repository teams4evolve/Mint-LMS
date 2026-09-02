<?php
declare(strict_types=1);

namespace MintLMS\Http\Rest\Controller;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Application\Lesson\Dto\CreateLessonDto;
use MintLMS\Application\Lesson\Dto\ReorderLessonsDto;
use MintLMS\Application\Lesson\Dto\UpdateLessonDto;
use MintLMS\Application\Lesson\LessonService;
use MintLMS\Infrastructure\Http\RestContentSanitizer;
use MintLMS\Http\Rest\Response\ApiResponse;

final class LessonController {

	private const NAMESPACE = 'mintlms/v1';

	public function __construct(
		private LessonService $lessonService,
		private AuthorizationInterface $authorization,
	) {
	}

	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/sections/(?P<id>\d+)/lessons',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
					'args'                => $this->createArgs(),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/lessons/(?P<id>\d+)',
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
			'/sections/(?P<id>\d+)/lessons/reorder',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'reorder' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
					'args'                => $this->reorderArgs(),
				),
			)
		);
	}

	public function canManageCourses(): bool {
		$userId = $this->authorization->getCurrentUserId();

		return $userId > 0 && $this->authorization->canCreateCourse( $userId );
	}

	public function create( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId    = $this->authorization->getCurrentUserId();
			$sectionId = (int) $request->get_param( 'id' );

			$dto = new CreateLessonDto(
				(string) $request->get_param( 'title' ),
				$this->nullableString( $request->get_param( 'slug' ) ),
				(string) ( $request->get_param( 'content' ) ?? '' ),
				$this->nullableString( $request->get_param( 'video_url' ) ),
				$this->nullableInt( $request->get_param( 'attachment_id' ) ),
				(bool) $request->get_param( 'is_preview' ),
			);

			$lesson = $this->lessonService->create( $sectionId, $dto, $userId );

			return ApiResponse::success( $lesson->toArray(), 201 );
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

			$lesson = $this->lessonService->get( $id, $userId );

			return ApiResponse::success( $lesson->toArray() );
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

			$dto = new UpdateLessonDto(
				$request->offsetExists( 'title' ) ? (string) $request->get_param( 'title' ) : null,
				$request->offsetExists( 'slug' ) ? $this->nullableString( $request->get_param( 'slug' ) ) : null,
				$request->offsetExists( 'content' ) ? (string) $request->get_param( 'content' ) : null,
				$request->offsetExists( 'video_url' ) ? $this->nullableString( $request->get_param( 'video_url' ) ) : null,
				$request->offsetExists( 'attachment_id' ) ? $this->nullableInt( $request->get_param( 'attachment_id' ) ) : null,
				$request->offsetExists( 'is_preview' ) ? (bool) $request->get_param( 'is_preview' ) : null,
				$request->offsetExists( 'available_after_days' ) ? $this->nullableNonNegativeInt( $request->get_param( 'available_after_days' ) ) : null,
				$request->offsetExists( 'available_after_days' ),
			);

			$lesson = $this->lessonService->update( $id, $dto, $userId );

			return ApiResponse::success( $lesson->toArray() );
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

			$this->lessonService->delete( $id, $userId );

			return ApiResponse::success( null, 204 );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function reorder( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId    = $this->authorization->getCurrentUserId();
			$sectionId = (int) $request->get_param( 'id' );
			$ids       = $request->get_param( 'ids' );

			$dto = new ReorderLessonsDto( is_array( $ids ) ? array_map( 'intval', $ids ) : array() );

			$this->lessonService->reorder( $sectionId, $dto, $userId );

			return ApiResponse::success( null, 204 );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function createArgs(): array {
		return array(
			'title'         => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'slug'          => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_title',
			),
			'content'       => array(
				'type'              => 'string',
				'sanitize_callback' => array( RestContentSanitizer::class, 'richText' ),
			),
			'video_url'     => array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
			),
			'attachment_id' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'is_preview'    => array(
				'type'    => 'boolean',
				'default' => false,
			),
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function updateArgs(): array {
		return array(
			'title'         => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'slug'          => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_title',
			),
			'content'       => array(
				'type'              => 'string',
				'sanitize_callback' => array( RestContentSanitizer::class, 'richText' ),
			),
			'video_url'     => array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
			),
			'attachment_id' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'is_preview'    => array(
				'type' => 'boolean',
			),
			'available_after_days' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function reorderArgs(): array {
		return array(
			'ids' => array(
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => static function ( mixed $value ): bool {
					if ( ! is_array( $value ) ) {
						return false;
					}

					foreach ( $value as $id ) {
						if ( ! is_numeric( $id ) || (int) $id <= 0 ) {
							return false;
						}
					}

					return true;
				},
				'sanitize_callback' => static function ( mixed $value ): array {
					if ( ! is_array( $value ) ) {
						return array();
					}

					return array_map( 'absint', $value );
				},
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

	private function nullableNonNegativeInt( mixed $value ): ?int {
		if ( null === $value || '' === $value ) {
			return null;
		}

		return max( 0, (int) $value );
	}
}
