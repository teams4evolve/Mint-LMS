<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Repository;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Enrollment\Enrollment;
use MintLMS\Domain\Enrollment\EnrollmentRepositoryInterface;
use MintLMS\Domain\Enrollment\EnrollmentStatus;
use MintLMS\Infrastructure\Database\Schema;

final class WpdbEnrollmentRepository implements EnrollmentRepositoryInterface {

	public function __construct(
		private \wpdb $wpdb,
	) {
	}

	public function findById( int $id ): ?Enrollment {
		$table = Schema::enrollmentsTable( $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT id, user_id, course_id, status, enrolled_at, expires_at, completed_at
				FROM {$table}
				WHERE id = %d",
				$id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		if ( null === $row ) {
			return null;
		}

		return $this->mapRowToEnrollment( $row );
	}

	public function findByUserAndCourse( int $userId, int $courseId ): ?Enrollment {
		$table = Schema::enrollmentsTable( $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT id, user_id, course_id, status, enrolled_at, expires_at, completed_at
				FROM {$table}
				WHERE user_id = %d AND course_id = %d",
				$userId,
				$courseId
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		if ( null === $row ) {
			return null;
		}

		return $this->mapRowToEnrollment( $row );
	}

	public function save( Enrollment $enrollment ): Enrollment {
		$table = Schema::enrollmentsTable( $this->wpdb->prefix );

		$data = array(
			'user_id'      => $enrollment->userId,
			'course_id'    => $enrollment->courseId,
			'status'       => $enrollment->status->value,
			'enrolled_at'  => $enrollment->enrolledAt->format( 'Y-m-d H:i:s' ),
			'expires_at'   => null !== $enrollment->expiresAt ? $enrollment->expiresAt->format( 'Y-m-d H:i:s' ) : null,
			'completed_at' => null !== $enrollment->completedAt ? $enrollment->completedAt->format( 'Y-m-d H:i:s' ) : null,
		);

		if ( 0 === $enrollment->id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $this->wpdb->insert(
				$table,
				$data,
				array( '%d', '%d', '%s', '%s', '%s', '%s' )
			);

			if ( false === $inserted ) {
				throw new \RuntimeException( 'Failed to insert enrollment.' );
			}

			return new Enrollment(
				(int) $this->wpdb->insert_id,
				$enrollment->userId,
				$enrollment->courseId,
				$enrollment->status,
				$enrollment->enrolledAt,
				$enrollment->expiresAt,
				$enrollment->completedAt,
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $this->wpdb->update(
			$table,
			$data,
			array( 'id' => $enrollment->id ),
			array( '%d', '%d', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			throw new \RuntimeException( 'Failed to update enrollment.' );
		}

		return $enrollment;
	}

	public function listByCourse(
		int $courseId,
		int $page,
		int $perPage,
		?EnrollmentStatus $status = null,
	): array {
		$table  = Schema::enrollmentsTable( $this->wpdb->prefix );
		$offset = ( $page - 1 ) * $perPage;

		$where  = array( 'course_id = %d' );
		$params = array( $courseId );

		if ( null !== $status ) {
			$where[]  = 'status = %s';
			$params[] = $status->value;
		}

		$whereSql = implode( ' AND ', $where );

		$countSql = "SELECT COUNT(*) FROM {$table} WHERE {$whereSql}";
		$listSql  = "SELECT id, user_id, course_id, status, enrolled_at, expires_at, completed_at
			FROM {$table}
			WHERE {$whereSql}
			ORDER BY enrolled_at DESC
			LIMIT %d OFFSET %d";

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) $this->wpdb->get_var( $this->wpdb->prepare( $countSql, ...$params ) );
		$rows  = $this->wpdb->get_results(
			$this->wpdb->prepare( $listSql, ...array_merge( $params, array( $perPage, $offset ) ) )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		$enrollments = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$enrollments[] = $this->mapRowToEnrollment( $row );
			}
		}

		return array(
			'enrollments' => $enrollments,
			'total'       => $total,
		);
	}

	public function listByUser(
		int $userId,
		int $page,
		int $perPage,
		?EnrollmentStatus $status = null,
	): array {
		$table  = Schema::enrollmentsTable( $this->wpdb->prefix );
		$offset = ( $page - 1 ) * $perPage;

		$where  = array( 'user_id = %d' );
		$params = array( $userId );

		if ( null !== $status ) {
			$where[]  = 'status = %s';
			$params[] = $status->value;
		}

		$whereSql = implode( ' AND ', $where );

		$countSql = "SELECT COUNT(*) FROM {$table} WHERE {$whereSql}";
		$listSql  = "SELECT id, user_id, course_id, status, enrolled_at, expires_at, completed_at
			FROM {$table}
			WHERE {$whereSql}
			ORDER BY enrolled_at DESC
			LIMIT %d OFFSET %d";

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) $this->wpdb->get_var( $this->wpdb->prepare( $countSql, ...$params ) );
		$rows  = $this->wpdb->get_results(
			$this->wpdb->prepare( $listSql, ...array_merge( $params, array( $perPage, $offset ) ) )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		$enrollments = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$enrollments[] = $this->mapRowToEnrollment( $row );
			}
		}

		return array(
			'enrollments' => $enrollments,
			'total'       => $total,
		);
	}

	public function getProgressPercentagesForCourse( int $courseId, array $userIds ): array {
		if ( array() === $userIds ) {
			return array();
		}

		$table        = Schema::progressSummaryTable( $this->wpdb->prefix );
		$placeholders = implode( ',', array_fill( 0, count( $userIds ), '%d' ) );
		$params       = array_merge( array( $courseId ), $userIds );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT user_id, pct_complete
				FROM {$table}
				WHERE course_id = %d AND user_id IN ({$placeholders})",
				...$params
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		$map = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$map[ (int) $row->user_id ] = (float) $row->pct_complete;
			}
		}

		return $map;
	}

	private function mapRowToEnrollment( object $row ): Enrollment {
		$expiresAt   = null;
		$completedAt = null;

		if ( null !== $row->expires_at && '' !== $row->expires_at ) {
			$expiresAt = new \DateTimeImmutable( (string) $row->expires_at );
		}

		if ( null !== $row->completed_at && '' !== $row->completed_at ) {
			$completedAt = new \DateTimeImmutable( (string) $row->completed_at );
		}

		return new Enrollment(
			(int) $row->id,
			(int) $row->user_id,
			(int) $row->course_id,
			EnrollmentStatus::from( (string) $row->status ),
			new \DateTimeImmutable( (string) $row->enrolled_at ),
			$expiresAt,
			$completedAt,
		);
	}
}
