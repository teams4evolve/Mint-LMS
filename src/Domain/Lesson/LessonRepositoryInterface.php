<?php
declare(strict_types=1);

namespace MintLMS\Domain\Lesson;

interface LessonRepositoryInterface {

	public function findById( int $id ): ?Lesson;

	public function findBySlugAndCourseId( string $slug, int $courseId ): ?Lesson;

	/**
	 * @return list<Lesson>
	 */
	public function findBySectionId( int $sectionId ): array;

	public function save( Lesson $lesson ): Lesson;

	public function delete( int $id ): bool;

	public function deleteBySectionId( int $sectionId ): void;

	/**
	 * @param list<int> $lessonIds Ordered lesson IDs for the section.
	 */
	public function reorder( int $sectionId, array $lessonIds ): void;

	public function nextSortOrder( int $sectionId ): int;
}
