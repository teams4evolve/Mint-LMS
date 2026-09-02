<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Migration;

use MintLMS\Infrastructure\Database\Schema;

final class Migration_003_Drip implements MigrationInterface {

	public function version(): string {
		return '003';
	}

	public function up( \wpdb $wpdb ): void {
		$table = Schema::validateTable( Schema::lessonsTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Custom table migration; table name validated via Schema::validateTable().
		$columns = $wpdb->get_col( $wpdb->prepare( 'DESC %i', $table ), 0 );

		if ( ! is_array( $columns ) || ! in_array( 'available_after_days', $columns, true ) ) {
			$wpdb->query(
				$wpdb->prepare(
					'ALTER TABLE %i ADD COLUMN available_after_days int(10) unsigned NULL DEFAULT NULL AFTER is_preview',
					$table
				)
			);
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	}
}
