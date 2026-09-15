<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Migration;

use MintLMS\Infrastructure\Database\Schema;
use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * Remap legacy enrollment "open" (self-enroll) → "free".
 * New "open" means public browse (LD-style).
 */
final class Migration_008_CourseAccessSettings implements MigrationInterface {

	public function version(): string {
		return '008';
	}

	public function up( \wpdb $wpdb ): void {
		$courses = Schema::coursesTable( $wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		if ( $this->tableExists( $wpdb, $courses ) ) {
			$courses = Schema::validateTable( $courses, $wpdb->prefix );
			$wpdb->query(
				"UPDATE {$courses} SET enrollment_type = 'free' WHERE enrollment_type = 'open'"
			);
		}

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND meta_value = %s",
				'free',
				PostTypes::META_ENROLLMENT_TYPE,
				'open'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$option = get_option( \MintLMS\Infrastructure\Setup\PageSettings::OPTION_DEFAULT_ENROLLMENT, '' );
		if ( 'open' === $option || '' === $option || false === $option ) {
			update_option( \MintLMS\Infrastructure\Setup\PageSettings::OPTION_DEFAULT_ENROLLMENT, 'free', false );
		}
	}

	private function tableExists( \wpdb $wpdb, string $table ): bool {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		return is_string( $found ) && $found === $table;
	}
}
