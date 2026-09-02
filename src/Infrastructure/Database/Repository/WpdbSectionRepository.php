<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Repository;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Lesson\Lesson;
use MintLMS\Domain\Section\Section;
use MintLMS\Domain\Section\SectionRepositoryInterface;
use MintLMS\Infrastructure\Database\Schema;

final class WpdbSectionRepository implements SectionRepositoryInterface {

	public function __construct(
		private \wpdb $wpdb,
	) {
	}

	public function findById( int $id ): ?Section {
		$table = Schema::validateTable( Schema::sectionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT id, course_id, title, sort_order, created_at
				FROM {$table}
				WHERE id = %d",
				$id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		if ( null === $row ) {
			return null;
		}

		return $this->mapRowToSection( $row );
	}

	public function findByCourseId( int $courseId ): array {
		$table = Schema::validateTable( Schema::sectionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT id, course_id, title, sort_order, created_at
				FROM {$table}
				WHERE course_id = %d
				ORDER BY sort_order ASC, id ASC",
				$courseId
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		$sections = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$sections[] = $this->mapRowToSection( $row );
			}
		}

		return $sections;
	}

	public function save( Section $section ): Section {
		$table = Schema::validateTable( Schema::sectionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		if ( 0 === $section->id ) {
			$created = $section->createdAt->format( 'Y-m-d H:i:s' );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $this->wpdb->insert(
				$table,
				array(
					'course_id'  => $section->courseId,
					'title'      => $section->title,
					'sort_order' => $section->sortOrder,
					'created_at' => $created,
				),
				array( '%d', '%s', '%d', '%s' )
			);

			if ( false === $inserted ) {
				throw new \RuntimeException( 'Failed to insert section.' );
			}

			return new Section(
				(int) $this->wpdb->insert_id,
				$section->courseId,
				$section->title,
				$section->sortOrder,
				$section->createdAt,
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $this->wpdb->update(
			$table,
			array(
				'title'      => $section->title,
				'sort_order' => $section->sortOrder,
			),
			array( 'id' => $section->id ),
			array( '%s', '%d' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			throw new \RuntimeException( 'Failed to update section.' );
		}

		return $section;
	}

	public function delete( int $id ): bool {
		$table = Schema::validateTable( Schema::sectionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $this->wpdb->delete(
			$table,
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $deleted && $deleted > 0;
	}

	public function reorder( int $courseId, array $sectionIds ): void {
		$table = Schema::validateTable( Schema::sectionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		foreach ( $sectionIds as $index => $sectionId ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->wpdb->update(
				$table,
				array( 'sort_order' => $index ),
				array(
					'id'        => $sectionId,
					'course_id' => $courseId,
				),
				array( '%d' ),
				array( '%d', '%d' )
			);
		}
	}

	public function nextSortOrder( int $courseId ): int {
		$table = Schema::validateTable( Schema::sectionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$max = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT MAX(sort_order) FROM {$table} WHERE course_id = %d",
				$courseId
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		if ( null === $max || '' === $max ) {
			return 0;
		}

		return (int) $max + 1;
	}

	public function loadStructureRows( int $courseId ): array {
		$sectionsTable = Schema::validateTable( Schema::sectionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );
		$lessonsTable  = Schema::validateTable( Schema::lessonsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT
					s.id AS section_id,
					s.course_id AS section_course_id,
					s.title AS section_title,
					s.sort_order AS section_sort_order,
					s.created_at AS section_created_at,
					l.id AS lesson_id,
					l.section_id AS lesson_section_id,
					l.course_id AS lesson_course_id,
					l.title AS lesson_title,
					l.slug AS lesson_slug,
					l.content AS lesson_content,
					l.video_url AS lesson_video_url,
					l.attachment_id AS lesson_attachment_id,
					l.is_preview AS lesson_is_preview,
					l.available_after_days AS lesson_available_after_days,
					l.sort_order AS lesson_sort_order,
					l.created_at AS lesson_created_at,
					l.updated_at AS lesson_updated_at
				FROM {$sectionsTable} s
				LEFT JOIN {$lessonsTable} l ON l.section_id = s.id
				WHERE s.course_id = %d
				ORDER BY s.sort_order ASC, s.id ASC, l.sort_order ASC, l.id ASC",
				$courseId
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		$structure = array();

		if ( ! is_array( $rows ) ) {
			return $structure;
		}

		foreach ( $rows as $row ) {
			$section = new Section(
				(int) $row->section_id,
				(int) $row->section_course_id,
				(string) $row->section_title,
				(int) $row->section_sort_order,
				new \DateTimeImmutable( (string) $row->section_created_at ),
			);

			$lesson = null;

			if ( null !== $row->lesson_id && '' !== $row->lesson_id ) {
				$lesson = $this->mapStructureRowToLesson( $row );
			}

			$structure[] = array(
				'section' => $section,
				'lesson'  => $lesson,
			);
		}

		return $structure;
	}

	private function mapRowToSection( object $row ): Section {
		return new Section(
			(int) $row->id,
			(int) $row->course_id,
			(string) $row->title,
			(int) $row->sort_order,
			new \DateTimeImmutable( (string) $row->created_at ),
		);
	}

	private function mapStructureRowToLesson( object $row ): Lesson {
		$attachmentId = null;

		if ( null !== $row->lesson_attachment_id && '' !== $row->lesson_attachment_id ) {
			$attachmentId = (int) $row->lesson_attachment_id;
		}

		$videoUrl = null;
		if ( null !== $row->lesson_video_url && '' !== $row->lesson_video_url ) {
			$videoUrl = (string) $row->lesson_video_url;
		}

		$availableAfterDays = null;
		if ( null !== $row->lesson_available_after_days && '' !== $row->lesson_available_after_days ) {
			$availableAfterDays = (int) $row->lesson_available_after_days;
		}

		return new Lesson(
			(int) $row->lesson_id,
			(int) $row->lesson_section_id,
			(int) $row->lesson_course_id,
			(string) $row->lesson_title,
			(string) $row->lesson_slug,
			(string) ( $row->lesson_content ?? '' ),
			$videoUrl,
			$attachmentId,
			(bool) (int) $row->lesson_is_preview,
			$availableAfterDays,
			(int) $row->lesson_sort_order,
			new \DateTimeImmutable( (string) $row->lesson_created_at ),
			new \DateTimeImmutable( (string) $row->lesson_updated_at ),
		);
	}
}
