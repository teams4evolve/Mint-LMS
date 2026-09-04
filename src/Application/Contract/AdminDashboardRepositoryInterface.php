<?php
declare(strict_types=1);

namespace MintLMS\Application\Contract;

interface AdminDashboardRepositoryInterface {

	/**
	 * @param list<int> $courseIds
	 * @return array<string, mixed>
	 */
	public function getAuthorStats( int $authorId, array $courseIds ): array;

	/**
	 * @param list<int> $courseIds
	 * @return array<int, array{students: int, completion_pct: int|null, lesson_count: int}>
	 */
	public function getCourseStatsBatch( array $courseIds ): array;
}
