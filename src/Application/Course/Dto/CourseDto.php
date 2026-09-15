<?php
declare(strict_types=1);

namespace MintLMS\Application\Course\Dto;

use MintLMS\Domain\Course\Course;

defined( 'ABSPATH' ) || exit;

final readonly class CourseDto {

	/**
	 * @param list<int> $prerequisiteCourseIds
	 */
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
		public string $authorName = '',
		public string $category = '',
		public bool $emailOnPublish = true,
		public bool $studentComplete = true,
		public bool $certificate = false,
		public string $progression = 'linear',
		public bool $expireAccess = false,
		public int $expireAccessDays = 30,
		public bool $prerequisitesEnabled = false,
		public array $prerequisiteCourseIds = array(),
		public string $prerequisiteCompare = 'ANY',
		public ?string $accessStartAt = null,
		public ?string $accessEndAt = null,
		public int $seatLimit = 0,
	) {
	}

	public static function fromCourse( Course $course, ?array $stats = null, string $authorName = '', string $category = '' ): self {
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

		$settings = $course->settings;

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
			$authorName,
			$category,
			$settings->emailOnPublish,
			$settings->studentComplete,
			$settings->certificate,
			$settings->progression,
			$settings->expireAccess,
			$settings->expireAccessDays,
			$settings->prerequisitesEnabled,
			$settings->prerequisiteCourseIds,
			$settings->prerequisiteCompare,
			$settings->accessStartAt,
			$settings->accessEndAt,
			$settings->seatLimit,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'id'                     => $this->id,
			'title'                  => $this->title,
			'slug'                   => $this->slug,
			'description'            => $this->description,
			'featuredImageId'        => $this->featuredImageId,
			'status'                 => $this->status,
			'enrollmentType'         => $this->enrollmentType,
			'authorId'               => $this->authorId,
			'authorName'             => $this->authorName,
			'instructor'             => $this->authorName,
			'category'               => $this->category,
			'createdAt'              => $this->createdAt,
			'updatedAt'              => $this->updatedAt,
			'lessonCount'            => $this->lessonCount,
			'studentCount'           => $this->studentCount,
			'completionRate'         => $this->completionRate,
			'emailOnPublish'         => $this->emailOnPublish,
			'studentComplete'        => $this->studentComplete,
			'certificate'            => $this->certificate,
			'progression'            => $this->progression,
			'expireAccess'           => $this->expireAccess,
			'expireAccessDays'       => $this->expireAccessDays,
			'prerequisitesEnabled'   => $this->prerequisitesEnabled,
			'prerequisiteCourseIds'  => $this->prerequisiteCourseIds,
			'prerequisiteCompare'    => $this->prerequisiteCompare,
			'accessStartAt'          => $this->accessStartAt,
			'accessEndAt'            => $this->accessEndAt,
			'seatLimit'              => $this->seatLimit,
		);
	}
}
