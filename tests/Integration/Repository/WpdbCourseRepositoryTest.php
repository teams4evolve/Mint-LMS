<?php
declare(strict_types=1);

namespace MintLMS\Tests\Integration\Repository;

use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Infrastructure\Database\Repository\WpdbCourseRepository;
use PHPUnit\Framework\TestCase;

final class WpdbCourseRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('ABSPATH') || !isset($GLOBALS['wpdb']) || !$GLOBALS['wpdb'] instanceof \wpdb) {
            $this->markTestSkipped('WordPress test environment not available.');
        }
    }

    public function test_save_and_find_by_id_round_trip(): void
    {
        global $wpdb;

        $repository = new WpdbCourseRepository($wpdb);
        $now = new \DateTimeImmutable('2026-02-01 12:00:00');

        $course = new Course(
            0,
            'Integration Course',
            'integration-course-' . wp_generate_password(6, false),
            'Created during integration test.',
            null,
            CourseStatus::Draft,
            EnrollmentType::Open,
            1,
            $now,
            $now,
        );

        $saved = $repository->save($course);

        $this->assertGreaterThan(0, $saved->id);

        $found = $repository->findById($saved->id);

        $this->assertNotNull($found);
        $this->assertSame('Integration Course', $found->title);
        $this->assertSame($saved->slug, $found->slug);

        $repository->delete($saved->id);
    }
}
