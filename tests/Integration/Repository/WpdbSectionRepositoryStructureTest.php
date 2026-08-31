<?php
declare(strict_types=1);

namespace MintLMS\Tests\Integration\Repository;

use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Domain\Lesson\Lesson;
use MintLMS\Domain\Section\Section;
use MintLMS\Infrastructure\Database\Repository\WpdbCourseRepository;
use MintLMS\Infrastructure\Database\Repository\WpdbLessonRepository;
use MintLMS\Infrastructure\Database\Repository\WpdbSectionRepository;
use PHPUnit\Framework\TestCase;

final class WpdbSectionRepositoryStructureTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('ABSPATH') || !isset($GLOBALS['wpdb']) || !$GLOBALS['wpdb'] instanceof \wpdb) {
            $this->markTestSkipped('WordPress test environment not available.');
        }
    }

    public function test_load_structure_rows_uses_single_query_and_builds_course_tree(): void
    {
        global $wpdb;

        $courseRepository = new WpdbCourseRepository($wpdb);
        $sectionRepository = new WpdbSectionRepository($wpdb);
        $lessonRepository = new WpdbLessonRepository($wpdb);
        $now = new \DateTimeImmutable('2026-02-01 12:00:00');

        $course = $courseRepository->save(new Course(
            0,
            'Structure Course',
            'structure-course-' . wp_generate_password(6, false),
            'Structure integration test.',
            null,
            CourseStatus::Draft,
            EnrollmentType::Open,
            1,
            $now,
            $now,
        ));

        $section = $sectionRepository->save(new Section(
            0,
            $course->id,
            'Section A',
            0,
            $now,
        ));

        $lesson = $lessonRepository->save(new Lesson(
            0,
            $section->id,
            $course->id,
            'Lesson A',
            'lesson-a-' . wp_generate_password(4, false),
            'Lesson content',
            null,
            null,
            false,
            null,
            0,
            $now,
            $now,
        ));

        $queryCountBefore = is_array($wpdb->queries ?? null) ? count($wpdb->queries) : null;

        $rows = $sectionRepository->loadStructureRows($course->id);

        if (null !== $queryCountBefore && is_array($wpdb->queries)) {
            $this->assertSame(1, count($wpdb->queries) - $queryCountBefore);
        }

        $this->assertCount(1, $rows);
        $this->assertSame('Section A', $rows[0]['section']->title);
        $this->assertNotNull($rows[0]['lesson']);
        $this->assertSame('Lesson A', $rows[0]['lesson']->title);

        $lessonRepository->delete($lesson->id);
        $sectionRepository->delete($section->id);
        $courseRepository->delete($course->id);
    }
}
