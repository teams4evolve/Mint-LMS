<?php
declare(strict_types=1);

namespace MintLMS\Application\Course\Dto;

use MintLMS\Domain\Course\Course;

defined( 'ABSPATH' ) || exit;

final readonly class CourseDto {

	public function __construct(
		public int $id,
		public string $title,
		public string $slug,
		public string $description,
		public ?int $featuredImageId,
		public string $status,
		public string $enrollmentType,
		public int $authorId,
		public string $createdAt,
		public string $updatedAt,
		public int $lessonCount = 0,
		public int $studentCount = 0,
		public ?int $completionRate = null,
	) {
	}

	public static function fromCourse( Course $course, ?array $stats = null ): self {
		$lessonCount    = 0;
		$studentCount   = 0;
		$completionRate = null;

		if ( null !== $stats ) {
			$lessonCount    = (int) ( $stats['lesson_count'] ?? 0 );
			$studentCount   = (int) ( $stats['students'] ?? 0 );
			$completionRate = isset( $stats['completion_pct'] ) && null !== $stats['completion_pct']
				? (int) $stats['completion_pct']
				: null;
		}

		return new self(
			$course->id,
			$course->title,
			$course->slug,
			$course->description,
			$course->featuredImageId,
			$course->status->value,
			$course->enrollmentType->value,
			$course->authorId,
			$course->createdAt->format( 'c' ),
			$course->updatedAt->format( 'c' ),
			$lessonCount,
			$studentCount,
			$completionRate,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'id'              => $this->id,
			'title'           => $this->title,
			'slug'            => $this->slug,
			'description'     => $this->description,
			'featuredImageId' => $this->featuredImageId,
			'status'          => $this->status,
			'enrollmentType'  => $this->enrollmentType,
			'authorId'        => $this->authorId,
			'createdAt'       => $this->createdAt,
			'updatedAt'       => $this->updatedAt,
			'lessonCount'     => $this->lessonCount,
			'studentCount'    => $this->studentCount,
			'completionRate'  => $this->completionRate,
		);
	}
}
