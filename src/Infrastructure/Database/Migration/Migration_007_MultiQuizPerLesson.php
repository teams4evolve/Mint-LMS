<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Migration;

use MintLMS\Infrastructure\Database\Schema;

/**
 * Allow multiple quizzes per lesson on the legacy quizzes table.
 */
final class Migration_007_MultiQuizPerLesson implements MigrationInterface {

	public function version(): string {
		return '007';
	}

	public function up( \wpdb $wpdb ): void {
		$table = Schema::validateTable( Schema::quizzesTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$index = $wpdb->get_row( "SHOW INDEX FROM {$table} WHERE Key_name = 'lesson_id'" );
		if ( null !== $index ) {
			$wpdb->query( "ALTER TABLE {$table} DROP INDEX lesson_id" );
		}

		$sortIndex = $wpdb->get_row( "SHOW INDEX FROM {$table} WHERE Key_name = 'lesson_sort'" );
		if ( null === $sortIndex ) {
			$wpdb->query( "ALTER TABLE {$table} ADD KEY lesson_sort (lesson_id, sort_order)" );
		}
		// phpcs:enable
	}
}
