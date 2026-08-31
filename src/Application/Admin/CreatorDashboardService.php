<?php
declare(strict_types=1);

namespace MintLMS\Application\Admin;

use MintLMS\Application\Admin\Dto\CreatorDashboardDto;
use MintLMS\Application\Admin\Dto\DashboardActivityItemDto;
use MintLMS\Application\Admin\Dto\DashboardCourseRowDto;
use MintLMS\Application\Admin\Dto\DashboardMetricsDto;
use MintLMS\Application\Admin\Dto\NeedsYouCourseDto;
use MintLMS\Application\Contract\AdminDashboardRepositoryInterface;
use MintLMS\Application\Contract\DashboardPresentationInterface;
use MintLMS\Application\Contract\UserLookupInterface;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Course\CourseStatus;

final class CreatorDashboardService {

	public function __construct(
		private CourseRepositoryInterface $courseRepository,
		private AdminDashboardRepositoryInterface $dashboardRepository,
		private DashboardPresentationInterface $presentation,
		private UserLookupInterface $userLookup,
	) {
	}

	public function getDashboard( int $authorId ): CreatorDashboardDto {
		$courses   = $this->courseRepository->list( 1, 100, $authorId );
		$courseIds = array_map( static fn( $c ) => $c->id, $courses['courses'] );
		$stats     = $this->dashboardRepository->getAuthorStats( $authorId, $courseIds );
		$rows      = array();
		$index     = 0;
		$needsYou  = null;

		foreach ( $courses['courses'] as $course ) {
			$courseStats = $stats['courses'][ $course->id ] ?? array(
				'students'       => 0,
				'completion_pct' => null,
				'lesson_count'   => 0,
				'empty_sections' => 0,
			);

			$hue = $this->presentation->hueByIndex( $index );
			++$index;

			$isDraft = CourseStatus::Published !== $course->status;
			$pct     = $courseStats['completion_pct'];

			$rows[] = new DashboardCourseRowDto(
				$course->id,
				$course->title,
				$course->status->value,
				$isDraft ? '—' : (string) $courseStats['students'],
				$isDraft ? null : $pct,
				$this->presentation->editedLabel( $course->updatedAt ),
				$this->courseMetaLine( $courseStats, $course->status->value ),
				$hue['bg'],
				$hue['ink'],
				$this->presentation->courseInitial( $course->title ),
			);

			if ( null === $needsYou && $isDraft ) {
				$blocker = $this->blockerMessage( $courseStats );
				if ( null !== $blocker ) {
					$needsYou = new NeedsYouCourseDto(
						$course->id,
						$course->title,
						$blocker,
						$this->presentation->editedLabel( $course->updatedAt ),
					);
				}
			}
		}

		if ( null === $needsYou && array() !== $rows ) {
			foreach ( $courses['courses'] as $course ) {
				$courseStats = $stats['courses'][ $course->id ] ?? array();
				$blocker     = $this->blockerMessage( $courseStats );
				if ( null !== $blocker ) {
					$needsYou = new NeedsYouCourseDto(
						$course->id,
						$course->title,
						$blocker,
						$this->presentation->editedLabel( $course->updatedAt ),
					);
					break;
				}
			}
		}

		$activity = $this->buildActivity( $stats['activity'] ?? array() );

		return new CreatorDashboardDto(
			array() === $rows,
			new DashboardMetricsDto(
				$stats['students_total'],
				$stats['finished_pct'],
				$stats['published_count'],
				$stats['draft_count'],
				$stats['lessons_done_7d'],
				$stats['students_delta'],
				$stats['finished_delta'],
			),
			$rows,
			$needsYou,
			$activity,
			$stats['signal_line'],
		);
	}

	/**
	 * @param array<string, mixed> $courseStats
	 */
	private function blockerMessage( array $courseStats ): ?string {
		if ( ( $courseStats['lesson_count'] ?? 0 ) === 0 ) {
			return 'No lessons yet';
		}

		if ( ( $courseStats['empty_sections'] ?? 0 ) > 0 ) {
			return 'A section has no lessons yet';
		}

		return null;
	}

	/**
	 * @param array<string, mixed> $courseStats
	 */
	private function courseMetaLine( array $courseStats, string $status ): string {
		$parts = array();

		$lessonCount = (int) ( $courseStats['lesson_count'] ?? 0 );

		if ( $lessonCount > 0 ) {
			$parts[] = 1 === $lessonCount
				? '1 lesson'
				: sprintf( '%d lessons', $lessonCount );
		}

		$parts[] = 'published' === $status ? 'published' : 'draft';

		return implode( ' · ', $parts );
	}

	/**
	 * @param list<array<string, mixed>> $raw
	 * @return list<DashboardActivityItemDto>
	 */
	private function buildActivity( array $raw ): array {
		$items = array();
		$index = 0;

		foreach ( $raw as $row ) {
			$name = $this->userLookup->getDisplayName( (int) $row['user_id'] ) ?? 'A student';
			$hue  = $this->presentation->hueByIndex( $index );
			++$index;
			$course   = (string) ( $row['course_title'] ?? '' );
			$sentence = '';

			if ( 'completed' === ( $row['type'] ?? '' ) ) {
				$sentence = sprintf( '%1$s finished %2$s', $name, $course );
			} elseif ( 'enrolled' === ( $row['type'] ?? '' ) ) {
				$sentence = sprintf( '%1$s enrolled in %2$s', $name, $course );
			} else {
				$sentence = sprintf( '%1$s made progress in %2$s', $name, $course );
			}

			$items[] = new DashboardActivityItemDto(
				$this->presentation->initials( $name ),
				$sentence,
				$this->presentation->relativeTimeShort( new \DateTimeImmutable( (string) $row['occurred_at'] ) ),
				$hue['bg'],
				$hue['ink'],
			);
		}

		return $items;
	}
}
