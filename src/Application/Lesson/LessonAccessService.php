<?php
declare(strict_types=1);

namespace MintLMS\Application\Lesson;

use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Domain\Drip\DripAccessEvaluator;
use MintLMS\Domain\Enrollment\EnrollmentRepositoryInterface;
use MintLMS\Domain\Lesson\LessonRepositoryInterface;
use MintLMS\Domain\Progress\ProgressRepositoryInterface;
use MintLMS\Domain\Shared\Clock;

final class LessonAccessService {

	public function __construct(
		private LessonRepositoryInterface $lessonRepository,
		private EnrollmentRepositoryInterface $enrollmentRepository,
		private ProgressRepositoryInterface $progressRepository,
		private Clock $clock,
	) {
	}

	public function assertCanAccessLesson( int $userId, int $lessonId, bool $allowPreviewWithoutEnrollment = true ): void {
		$lesson = $this->lessonRepository->findById( $lessonId );

		if ( null === $lesson ) {
			throw new NotFoundException( 'Lesson not found.' );
		}

		$isEnrolled = $userId > 0 && $this->progressRepository->isUserEnrolled( $userId, $lesson->courseId );

		if ( ! $isEnrolled ) {
			if ( $allowPreviewWithoutEnrollment && $lesson->isPreview ) {
				return;
			}

			throw new ForbiddenException( 'You must be enrolled to access this lesson.' );
		}

		if ( $this->isDripLocked( $userId, $lessonId ) ) {
			throw new ForbiddenException( 'This lesson is not available yet.' );
		}
	}

	public function isDripLocked( int $userId, int $lessonId ): bool {
		$lesson = $this->lessonRepository->findById( $lessonId );

		if ( null === $lesson || $userId <= 0 ) {
			return false;
		}

		if ( ! $this->progressRepository->isUserEnrolled( $userId, $lesson->courseId ) ) {
			return false;
		}

		$enrollment = $this->enrollmentRepository->findByUserAndCourse( $userId, $lesson->courseId );

		return DripAccessEvaluator::isLocked(
			$lesson->availableAfterDays,
			$enrollment?->enrolledAt,
			$this->clock->now(),
			true,
		);
	}
}
