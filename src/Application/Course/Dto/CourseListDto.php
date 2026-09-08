<?php
declare(strict_types=1);

namespace MintLMS\Application\Course\Dto;

final readonly class CourseListDto {

	/**
	 * @param list<CourseDto> $courses
	 */
	public function __construct(
		public array $courses,
		public int $total,
		public int $page,
		public int $perPage,
		public int $trashTotal = 0,
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'items'      => array_map(
				static fn( CourseDto $course ): array => $course->toArray(),
				$this->courses
			),
			'total'      => $this->total,
			'page'       => $this->page,
			'perPage'    => $this->perPage,
			'trashTotal' => $this->trashTotal,
		);
	}
}
