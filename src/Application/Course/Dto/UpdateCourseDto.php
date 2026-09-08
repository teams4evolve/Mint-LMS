<?php
declare(strict_types=1);

namespace MintLMS\Application\Course\Dto;

use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;

final readonly class UpdateCourseDto {

	public function __construct(
		public ?string $title = null,
		public ?string $slug = null,
		public ?string $description = null,
		public bool $updateFeaturedImage = false,
		public ?int $featuredImageId = null,
		public ?EnrollmentType $enrollmentType = null,
		public ?CourseStatus $status = null,
		public ?bool $emailOnPublish = null,
		public ?bool $studentComplete = null,
		public ?bool $certificate = null,
	) {
	}
}
