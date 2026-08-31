<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Database\Schema;

final class FirstRunState {

	public const OPTION_FIRST_RUN_COMPLETE     = 'mintlms_first_run_complete';
	public const OPTION_GUIDED_COURSE_COMPLETE = 'mintlms_guided_course_complete';

	public static function isFirstRunComplete(): bool {
		return (bool) get_option( self::OPTION_FIRST_RUN_COMPLETE, false );
	}

	public static function isGuidedCourseComplete(): bool {
		return (bool) get_option( self::OPTION_GUIDED_COURSE_COMPLETE, false );
	}

	public static function markFirstRunStarted(): void {
		update_option( self::OPTION_FIRST_RUN_COMPLETE, true, false );
	}

	public static function markOnboardingComplete(): void {
		update_option( self::OPTION_FIRST_RUN_COMPLETE, true, false );
		update_option( self::OPTION_GUIDED_COURSE_COMPLETE, true, false );
	}

	public static function userCourseCount( int $userId ): int {
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb || $userId <= 0 ) {
			return 0;
		}

		$table = Schema::coursesTable( $wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE author_id = %d",
				$userId
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return (int) $count;
	}

	public static function shouldOfferGuidedFlow( int $userId ): bool {
		return self::isFirstRunComplete()
			&& ! self::isGuidedCourseComplete()
			&& 0 === self::userCourseCount( $userId );
	}
}
