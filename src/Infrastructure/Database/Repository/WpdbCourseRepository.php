<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Repository;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Infrastructure\Database\Schema;

final class WpdbCourseRepository implements CourseRepositoryInterface {

	public function __construct(
		private \wpdb $wpdb,
	) {
	}

	public function findById( int $id ): ?Course {
		$table = Schema::coursesTable( $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT id, title, slug, description, featured_image_id, status, enrollment_type, author_id, created_at, updated_at
				FROM {$table}
				WHERE id = %d",
				$id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		if ( null === $row ) {
			return null;
		}

		return $this->mapRowToCourse( $row );
	}

	public function findBySlug( string $slug ): ?Course {
		$table = Schema::coursesTable( $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT id, title, slug, description, featured_image_id, status, enrollment_type, author_id, created_at, updated_at
				FROM {$table}
				WHERE slug = %s",
				$slug
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		if ( null === $row ) {
			return null;
		}

		return $this->mapRowToCourse( $row );
	}

	public function save( Course $course ): Course {
		$table = Schema::coursesTable( $this->wpdb->prefix );
		$now   = $course->updatedAt->format( 'Y-m-d H:i:s' );

		if ( 0 === $course->id ) {
			$created = $course->createdAt->format( 'Y-m-d H:i:s' );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $this->wpdb->insert(
				$table,
				array(
					'title'             => $course->title,
					'slug'              => $course->slug,
					'description'       => $course->description,
					'featured_image_id' => $course->featuredImageId,
					'status'            => $course->status->value,
					'enrollment_type'   => $course->enrollmentType->value,
					'author_id'         => $course->authorId,
					'created_at'        => $created,
					'updated_at'        => $now,
				),
				array( '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s' )
			);

			if ( false === $inserted ) {
				throw new \RuntimeException( 'Failed to insert course.' );
			}

			return new Course(
				(int) $this->wpdb->insert_id,
				$course->title,
				$course->slug,
				$course->description,
				$course->featuredImageId,
				$course->status,
				$course->enrollmentType,
				$course->authorId,
				$course->createdAt,
				$course->updatedAt,
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $this->wpdb->update(
			$table,
			array(
				'title'             => $course->title,
				'slug'              => $course->slug,
				'description'       => $course->description,
				'featured_image_id' => $course->featuredImageId,
				'status'            => $course->status->value,
				'enrollment_type'   => $course->enrollmentType->value,
				'updated_at'        => $now,
			),
			array( 'id' => $course->id ),
			array( '%s', '%s', '%s', '%d', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			throw new \RuntimeException( 'Failed to update course.' );
		}

		return $course;
	}

	public function delete( int $id ): bool {
		$table = Schema::coursesTable( $this->wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $this->wpdb->delete(
			$table,
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $deleted && $deleted > 0;
	}

	public function list( int $page, int $perPage, ?int $authorId = null, ?CourseStatus $status = null, ?string $search = null ): array {
		$table  = Schema::coursesTable( $this->wpdb->prefix );
		$offset = ( $page - 1 ) * $perPage;

		$where  = array( '1=1' );
		$params = array();

		if ( null !== $authorId ) {
			$where[]  = 'author_id = %d';
			$params[] = $authorId;
		}

		if ( null !== $status ) {
			$where[]  = 'status = %s';
			$params[] = $status->value;
		}

		if ( null !== $search && '' !== trim( $search ) ) {
			$where[]  = 'title LIKE %s';
			$params[] = '%' . $this->wpdb->esc_like( trim( $search ) ) . '%';
		}

		$whereSql = implode( ' AND ', $where );

		$countSql = "SELECT COUNT(*) FROM {$table} WHERE {$whereSql}";
		$listSql  = "SELECT id, title, slug, description, featured_image_id, status, enrollment_type, author_id, created_at, updated_at
			FROM {$table}
			WHERE {$whereSql}
			ORDER BY created_at DESC
			LIMIT %d OFFSET %d";

		if ( array() !== $params ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $this->wpdb->get_var( $this->wpdb->prepare( $countSql, ...$params ) );
			$rows  = $this->wpdb->get_results(
				$this->wpdb->prepare( $listSql, ...array_merge( $params, array( $perPage, $offset ) ) )
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		} else {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $this->wpdb->get_var( $countSql );
			$rows  = $this->wpdb->get_results(
				$this->wpdb->prepare( $listSql, $perPage, $offset )
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		}

		$courses = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$courses[] = $this->mapRowToCourse( $row );
			}
		}

		return array(
			'courses' => $courses,
			'total'   => $total,
		);
	}

	private function mapRowToCourse( object $row ): Course {
		$featuredImageId = null;

		if ( null !== $row->featured_image_id && '' !== $row->featured_image_id ) {
			$featuredImageId = (int) $row->featured_image_id;
		}

		return new Course(
			(int) $row->id,
			(string) $row->title,
			(string) $row->slug,
			(string) ( $row->description ?? '' ),
			$featuredImageId,
			CourseStatus::from( (string) $row->status ),
			EnrollmentType::from( (string) $row->enrollment_type ),
			(int) $row->author_id,
			new \DateTimeImmutable( (string) $row->created_at ),
			new \DateTimeImmutable( (string) $row->updated_at ),
		);
	}
}
