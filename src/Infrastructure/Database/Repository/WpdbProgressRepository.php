<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Repository;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Progress\CompleteLessonResult;
use MintLMS\Domain\Progress\ProgressRepositoryInterface;
use MintLMS\Domain\Progress\ProgressSummary;
use MintLMS\Domain\Shared\UserId;
use MintLMS\Infrastructure\Database\Schema;

final class WpdbProgressRepository implements ProgressRepositoryInterface {

	public function __construct(
		private \wpdb $wpdb,
	) {
	}

	public function findLessonCourseId( int $lessonId ): ?int {
		$table = Schema::lessonsTable( $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$courseId = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT course_id FROM {$table} WHERE id = %d",
				$lessonId
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		if ( null === $courseId ) {
			return null;
		}

		return (int) $courseId;
	}

	public function isUserEnrolled( int $userId, int $courseId ): bool {
		$table = Schema::enrollmentsTable( $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$found = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT id FROM {$table} WHERE user_id = %d AND course_id = %d AND status = %s",
				$userId,
				$courseId,
				'active'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		return null !== $found;
	}

	public function isLessonComplete( int $userId, int $lessonId ): bool {
		$table = Schema::progressTable( $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$found = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT id FROM {$table} WHERE user_id = %d AND lesson_id = %d",
				$userId,
				$lessonId
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		return null !== $found;
	}

	public function completeLesson(
		int $userId,
		int $lessonId,
		int $courseId,
		\DateTimeImmutable $completedAt,
	): CompleteLessonResult {
		$this->beginTransaction();

		try {
			$wasNewlyCompleted = false;

			if ( ! $this->isLessonComplete( $userId, $lessonId ) ) {
				$this->insertProgress( $userId, $lessonId, $courseId, $completedAt );
				$wasNewlyCompleted = true;
			}

			$summary             = $this->recalculateSummaryInternal( $userId, $courseId, $completedAt, $lessonId );
			$courseJustCompleted = $this->markEnrollmentCompletedIfNeeded( $userId, $courseId, $summary, $completedAt );

			$this->commitTransaction();

			return new CompleteLessonResult( $wasNewlyCompleted, $summary, $courseJustCompleted );
		} catch ( \Throwable $exception ) {
			$this->rollbackTransaction();
			throw $exception;
		}
	}

	public function uncompleteLesson(
		int $userId,
		int $lessonId,
		\DateTimeImmutable $updatedAt,
	): void {
		$courseId = $this->findLessonCourseId( $lessonId );

		if ( null === $courseId ) {
			return;
		}

		$this->beginTransaction();

		try {
			$progressTable = Schema::progressTable( $this->wpdb->prefix );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->wpdb->delete(
				$progressTable,
				array(
					'user_id'   => $userId,
					'lesson_id' => $lessonId,
				),
				array( '%d', '%d' )
			);

			$this->recalculateSummaryInternal( $userId, $courseId, $updatedAt, null );
			$this->clearEnrollmentCompleted( $userId, $courseId );

			$this->commitTransaction();
		} catch ( \Throwable $exception ) {
			$this->rollbackTransaction();
			throw $exception;
		}
	}

	public function getSummary( int $userId, int $courseId ): ?ProgressSummary {
		$table = Schema::progressSummaryTable( $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT user_id, course_id, lessons_done, lessons_total, pct_complete, last_lesson_id, updated_at
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

		return $this->mapRowToSummary( $row );
	}

	public function recalculateSummary(
		int $userId,
		int $courseId,
		\DateTimeImmutable $updatedAt,
		?int $lastLessonId = null,
	): ProgressSummary {
		$this->beginTransaction();

		try {
			$summary = $this->recalculateSummaryInternal( $userId, $courseId, $updatedAt, $lastLessonId );
			$this->commitTransaction();

			return $summary;
		} catch ( \Throwable $exception ) {
			$this->rollbackTransaction();
			throw $exception;
		}
	}

	public function getCompletedLessonIds( int $userId, int $courseId ): array {
		$progressTable = Schema::progressTable( $this->wpdb->prefix );
		$lessonsTable  = Schema::lessonsTable( $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT p.lesson_id
				FROM {$progressTable} p
				INNER JOIN {$lessonsTable} l ON l.id = p.lesson_id
				WHERE p.user_id = %d AND p.course_id = %d AND l.course_id = %d
				ORDER BY l.sort_order ASC, l.id ASC",
				$userId,
				$courseId,
				$courseId
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( 'intval', $rows );
	}

	public function countLessonsInCourse( int $courseId ): int {
		$table = Schema::lessonsTable( $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$count = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE course_id = %d",
				$courseId
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		return (int) $count;
	}

	public function onLessonDeleted( int $lessonId, \DateTimeImmutable $updatedAt ): void {
		$courseId = $this->findLessonCourseId( $lessonId );

		if ( null === $courseId ) {
			return;
		}

		$progressTable = Schema::progressTable( $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$userIds = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT DISTINCT user_id FROM {$progressTable} WHERE lesson_id = %d",
				$lessonId
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		$this->beginTransaction();

		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->wpdb->delete(
				$progressTable,
				array( 'lesson_id' => $lessonId ),
				array( '%d' )
			);

			if ( is_array( $userIds ) ) {
				foreach ( $userIds as $userId ) {
					$this->recalculateSummaryInternal( (int) $userId, $courseId, $updatedAt, null );
					$this->clearEnrollmentCompleted( (int) $userId, $courseId );
				}
			}

			$this->commitTransaction();
		} catch ( \Throwable $exception ) {
			$this->rollbackTransaction();
			throw $exception;
		}
	}

	private function insertProgress(
		int $userId,
		int $lessonId,
		int $courseId,
		\DateTimeImmutable $completedAt,
	): void {
		$table = Schema::progressTable( $this->wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $this->wpdb->insert(
			$table,
			array(
				'user_id'      => $userId,
				'lesson_id'    => $lessonId,
				'course_id'    => $courseId,
				'status'       => 'completed',
				'completed_at' => $completedAt->format( 'Y-m-d H:i:s' ),
			),
			array( '%d', '%d', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			throw new \RuntimeException( 'Failed to record lesson progress.' );
		}
	}

	private function recalculateSummaryInternal(
		int $userId,
		int $courseId,
		\DateTimeImmutable $updatedAt,
		?int $lastLessonId,
	): ProgressSummary {
		$lessonsTotal = $this->countLessonsInCourse( $courseId );
		$lessonsDone  = $this->countCompletedLessons( $userId, $courseId );
		$pctComplete  = $this->calculatePercent( $lessonsDone, $lessonsTotal );

		if ( null === $lastLessonId ) {
			$lastLessonId = $this->findLastCompletedLessonId( $userId, $courseId );
		}

		$table = Schema::progressSummaryTable( $this->wpdb->prefix );
		$now   = $updatedAt->format( 'Y-m-d H:i:s' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$replaced = $this->wpdb->replace(
			$table,
			array(
				'user_id'        => $userId,
				'course_id'      => $courseId,
				'lessons_done'   => $lessonsDone,
				'lessons_total'  => $lessonsTotal,
				'pct_complete'   => $pctComplete,
				'last_lesson_id' => $lastLessonId,
				'updated_at'     => $now,
			),
			array( '%d', '%d', '%d', '%d', '%f', '%d', '%s' )
		);

		if ( false === $replaced ) {
			throw new \RuntimeException( 'Failed to update progress summary.' );
		}

		return new ProgressSummary(
			UserId::fromInt( $userId ),
			$courseId,
			$lessonsDone,
			$lessonsTotal,
			$pctComplete,
			$lastLessonId,
			$updatedAt,
		);
	}

	private function countCompletedLessons( int $userId, int $courseId ): int {
		$progressTable = Schema::progressTable( $this->wpdb->prefix );
		$lessonsTable  = Schema::lessonsTable( $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$count = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*)
				FROM {$progressTable} p
				INNER JOIN {$lessonsTable} l ON l.id = p.lesson_id
				WHERE p.user_id = %d AND p.course_id = %d AND l.course_id = %d",
				$userId,
				$courseId,
				$courseId
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		return (int) $count;
	}

	private function findLastCompletedLessonId( int $userId, int $courseId ): ?int {
		$table = Schema::progressTable( $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$lessonId = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT lesson_id FROM {$table}
				WHERE user_id = %d AND course_id = %d
				ORDER BY completed_at DESC, id DESC
				LIMIT 1",
				$userId,
				$courseId
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		if ( null === $lessonId ) {
			return null;
		}

		return (int) $lessonId;
	}

	private function markEnrollmentCompletedIfNeeded(
		int $userId,
		int $courseId,
		ProgressSummary $summary,
		\DateTimeImmutable $completedAt,
	): bool {
		if ( ! $summary->isCourseComplete() ) {
			return false;
		}

		$table = Schema::enrollmentsTable( $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$existing = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT completed_at FROM {$table} WHERE user_id = %d AND course_id = %d",
				$userId,
				$courseId
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		if ( null !== $existing && '' !== $existing ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->wpdb->update(
			$table,
			array( 'completed_at' => $completedAt->format( 'Y-m-d H:i:s' ) ),
			array(
				'user_id'   => $userId,
				'course_id' => $courseId,
			),
			array( '%s' ),
			array( '%d', '%d' )
		);

		return true;
	}

	private function clearEnrollmentCompleted( int $userId, int $courseId ): void {
		$table = Schema::enrollmentsTable( $this->wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->wpdb->update(
			$table,
			array( 'completed_at' => null ),
			array(
				'user_id'   => $userId,
				'course_id' => $courseId,
			),
			array( '%s' ),
			array( '%d', '%d' )
		);
	}

	private function calculatePercent( int $lessonsDone, int $lessonsTotal ): float {
		if ( $lessonsTotal <= 0 ) {
			return 0.0;
		}

		return round( ( $lessonsDone / $lessonsTotal ) * 100, 2 );
	}

	private function mapRowToSummary( object $row ): ProgressSummary {
		$lastLessonId = null;

		if ( null !== $row->last_lesson_id && '' !== $row->last_lesson_id ) {
			$lastLessonId = (int) $row->last_lesson_id;
		}

		return new ProgressSummary(
			UserId::fromInt( (int) $row->user_id ),
			(int) $row->course_id,
			(int) $row->lessons_done,
			(int) $row->lessons_total,
			(float) $row->pct_complete,
			$lastLessonId,
			new \DateTimeImmutable( (string) $row->updated_at ),
		);
	}

	private function beginTransaction(): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->wpdb->query( 'START TRANSACTION' );
	}

	private function commitTransaction(): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->wpdb->query( 'COMMIT' );
	}

	private function rollbackTransaction(): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->wpdb->query( 'ROLLBACK' );
	}
}
