<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Repository;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Lesson\LessonRepositoryInterface;
use MintLMS\Domain\Section\Section;
use MintLMS\Domain\Section\SectionRepositoryInterface;
use MintLMS\Infrastructure\Database\Schema;

final class WpdbSectionRepository implements SectionRepositoryInterface {

	public function __construct(
		private \wpdb $wpdb,
		private ?LessonRepositoryInterface $lessons = null,
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

	public function loadStructureRows( int $courseId, bool $publishedOnly = false ): array {
		$sections  = $this->findByCourseId( $courseId );
		$structure = array();

		foreach ( $sections as $section ) {
			$sectionLessons = null !== $this->lessons
				? $this->lessons->findBySectionId( $section->id, $publishedOnly )
				: array();

			if ( array() === $sectionLessons ) {
				$structure[] = array(
					'section' => $section,
					'lesson'  => null,
				);
				continue;
			}

			foreach ( $sectionLessons as $lesson ) {
				$structure[] = array(
					'section' => $section,
					'lesson'  => $lesson,
				);
			}
		}

		// Course lessons with no section (section_id = 0) — independent of sections.
		if ( null !== $this->lessons ) {
			$ungrouped = $this->lessons->findByCourseIdAndSectionId( $courseId, 0, $publishedOnly );
			if ( array() !== $ungrouped ) {
				$virtual = new Section(
					0,
					$courseId,
					'',
					-1,
					new \DateTimeImmutable( '@0' ),
				);
				foreach ( $ungrouped as $lesson ) {
					$structure[] = array(
						'section' => $virtual,
						'lesson'  => $lesson,
					);
				}
			}
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
}
