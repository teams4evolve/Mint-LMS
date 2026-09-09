<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Repository;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Lesson\Lesson;
use MintLMS\Domain\Lesson\LessonRepositoryInterface;
use MintLMS\Infrastructure\Database\Schema;

final class WpdbLessonRepository implements LessonRepositoryInterface {

	public function __construct(
		private \wpdb $wpdb,
	) {
	}

	public function findById( int $id ): ?Lesson {
		$table = Schema::validateTable( Schema::lessonsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT id, section_id, course_id, title, slug, content, video_url, attachment_id, is_preview, available_after_days, sort_order, created_at, updated_at
				FROM {$table}
				WHERE id = %d",
				$id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		if ( null === $row ) {
			return null;
		}

		return $this->mapRowToLesson( $row );
	}

	public function findBySlugAndCourseId( string $slug, int $courseId ): ?Lesson {
		$table = Schema::validateTable( Schema::lessonsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT id, section_id, course_id, title, slug, content, video_url, attachment_id, is_preview, available_after_days, sort_order, created_at, updated_at
				FROM {$table}
				WHERE slug = %s AND course_id = %d",
				$slug,
				$courseId
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		if ( null === $row ) {
			return null;
		}

		return $this->mapRowToLesson( $row );
	}

	public function findBySectionId( int $sectionId, bool $publishedOnly = false ): array {
		$table = Schema::validateTable( Schema::lessonsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// Legacy table store has no draft flag — treat all rows as available.
		unset( $publishedOnly );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT id, section_id, course_id, title, slug, content, video_url, attachment_id, is_preview, available_after_days, sort_order, created_at, updated_at
				FROM {$table}
				WHERE section_id = %d
				ORDER BY sort_order ASC, id ASC",
				$sectionId
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		$lessons = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$lessons[] = $this->mapRowToLesson( $row );
			}
		}

		return $lessons;
	}

	public function save( Lesson $lesson ): Lesson {
		$table = Schema::validateTable( Schema::lessonsTable( $this->wpdb->prefix ), $this->wpdb->prefix );
		$now   = $lesson->updatedAt->format( 'Y-m-d H:i:s' );

		if ( 0 === $lesson->id ) {
			$created = $lesson->createdAt->format( 'Y-m-d H:i:s' );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $this->wpdb->insert(
				$table,
				array(
					'section_id'    => $lesson->sectionId,
					'course_id'     => $lesson->courseId,
					'title'         => $lesson->title,
					'slug'          => $lesson->slug,
					'content'       => $lesson->content,
					'video_url'     => $lesson->videoUrl,
					'attachment_id' => $lesson->attachmentId,
					'is_preview'           => $lesson->isPreview ? 1 : 0,
					'available_after_days' => $lesson->availableAfterDays,
					'sort_order'           => $lesson->sortOrder,
					'created_at'    => $created,
					'updated_at'    => $now,
				),
				array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s' )
			);

			if ( false === $inserted ) {
				throw new \RuntimeException( 'Failed to insert lesson.' );
			}

			return new Lesson(
				(int) $this->wpdb->insert_id,
				$lesson->sectionId,
				$lesson->courseId,
				$lesson->title,
				$lesson->slug,
				$lesson->content,
				$lesson->videoUrl,
				$lesson->attachmentId,
				$lesson->isPreview,
				$lesson->availableAfterDays,
				$lesson->sortOrder,
				$lesson->createdAt,
				$lesson->updatedAt,
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $this->wpdb->update(
			$table,
			array(
				'title'                => $lesson->title,
				'slug'                 => $lesson->slug,
				'content'              => $lesson->content,
				'video_url'            => $lesson->videoUrl,
				'attachment_id'        => $lesson->attachmentId,
				'is_preview'           => $lesson->isPreview ? 1 : 0,
				'available_after_days' => $lesson->availableAfterDays,
				'sort_order'           => $lesson->sortOrder,
				'updated_at'    => $now,
			),
			array( 'id' => $lesson->id ),
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			throw new \RuntimeException( 'Failed to update lesson.' );
		}

		return $lesson;
	}

	public function delete( int $id ): bool {
		$table = Schema::validateTable( Schema::lessonsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $this->wpdb->delete(
			$table,
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $deleted && $deleted > 0;
	}

	public function deleteBySectionId( int $sectionId ): void {
		$table = Schema::validateTable( Schema::lessonsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->wpdb->delete(
			$table,
			array( 'section_id' => $sectionId ),
			array( '%d' )
		);
	}

	public function reorder( int $sectionId, array $lessonIds ): void {
		$table = Schema::validateTable( Schema::lessonsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		foreach ( $lessonIds as $index => $lessonId ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->wpdb->update(
				$table,
				array( 'sort_order' => $index ),
				array(
					'id'         => $lessonId,
					'section_id' => $sectionId,
				),
				array( '%d' ),
				array( '%d', '%d' )
			);
		}
	}

	public function nextSortOrder( int $sectionId ): int {
		$table = Schema::validateTable( Schema::lessonsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$max = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT MAX(sort_order) FROM {$table} WHERE section_id = %d",
				$sectionId
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		if ( null === $max || '' === $max ) {
			return 0;
		}

		return (int) $max + 1;
	}

	private function mapRowToLesson( object $row ): Lesson {
		$attachmentId = null;

		if ( null !== $row->attachment_id && '' !== $row->attachment_id ) {
			$attachmentId = (int) $row->attachment_id;
		}

		$videoUrl = null;
		if ( null !== $row->video_url && '' !== $row->video_url ) {
			$videoUrl = (string) $row->video_url;
		}

		return new Lesson(
			(int) $row->id,
			(int) $row->section_id,
			(int) $row->course_id,
			(string) $row->title,
			(string) $row->slug,
			(string) ( $row->content ?? '' ),
			$videoUrl,
			$attachmentId,
			(bool) (int) $row->is_preview,
			$this->nullableInt( $row->available_after_days ?? null ),
			(int) $row->sort_order,
			new \DateTimeImmutable( (string) $row->created_at ),
			new \DateTimeImmutable( (string) $row->updated_at ),
		);
	}

	private function nullableInt( mixed $value ): ?int {
		if ( null === $value || '' === $value ) {
			return null;
		}

		return (int) $value;
	}
}
