<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit\Domain\Course;

use MintLMS\Domain\Course\CourseAccessRules;
use MintLMS\Domain\Course\CourseSettings;
use PHPUnit\Framework\TestCase;

final class CourseAccessRulesTest extends TestCase {

	public function test_linear_blocks_until_previous_complete(): void {
		$settings = CourseSettings::fromArray( array( 'progression' => 'linear' ) );

		$this->assertFalse(
			CourseAccessRules::isLessonBlockedByProgression( $settings, 1, array( 1, 2, 3 ), array() )
		);
		$this->assertTrue(
			CourseAccessRules::isLessonBlockedByProgression( $settings, 2, array( 1, 2, 3 ), array() )
		);
		$this->assertFalse(
			CourseAccessRules::isLessonBlockedByProgression( $settings, 2, array( 1, 2, 3 ), array( 1 ) )
		);
	}

	public function test_freeform_never_blocks(): void {
		$settings = CourseSettings::fromArray( array( 'progression' => 'freeform' ) );

		$this->assertFalse(
			CourseAccessRules::isLessonBlockedByProgression( $settings, 3, array( 1, 2, 3 ), array() )
		);
	}

	public function test_prerequisites_any_and_all(): void {
		$any = CourseSettings::fromArray(
			array(
				'prerequisitesEnabled'  => true,
				'prerequisiteCourseIds' => array( 10, 20 ),
				'prerequisiteCompare'   => 'ANY',
			)
		);
		$all = CourseSettings::fromArray(
			array(
				'prerequisitesEnabled'  => true,
				'prerequisiteCourseIds' => array( 10, 20 ),
				'prerequisiteCompare'   => 'ALL',
			)
		);

		$this->assertTrue(
			CourseAccessRules::prerequisitesMet( $any, static fn( int $id ): bool => 10 === $id )
		);
		$this->assertFalse(
			CourseAccessRules::prerequisitesMet( $all, static fn( int $id ): bool => 10 === $id )
		);
		$this->assertTrue(
			CourseAccessRules::prerequisitesMet( $all, static fn( int $id ): bool => true )
		);
	}

	public function test_expire_and_seats(): void {
		$settings = CourseSettings::fromArray(
			array(
				'expireAccess'     => true,
				'expireAccessDays' => 7,
				'seatLimit'        => 2,
			)
		);
		$start = new \DateTimeImmutable( '2026-01-01 00:00:00' );
		$exp   = CourseAccessRules::resolveExpiresAt( $settings, $start );

		$this->assertSame( '2026-01-08', $exp?->format( 'Y-m-d' ) );
		$this->assertTrue( CourseAccessRules::isSeatLimitReached( $settings, 2 ) );
		$this->assertFalse( CourseAccessRules::isSeatLimitReached( $settings, 1 ) );
	}
}
