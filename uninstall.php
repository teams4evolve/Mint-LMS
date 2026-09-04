<?php
/**
 * Uninstall Mint LMS.
 *
 * @package MintLMS
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

require_once __DIR__ . '/vendor/autoload.php';

use MintLMS\Infrastructure\Database\Schema;
use MintLMS\Infrastructure\Setup\PageSettings;

global $wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- Uninstall drops validated custom LMS tables.
foreach ( Schema::allTables( $wpdb->prefix ) as $mintlms_table ) {
	$mintlms_table = Schema::validateTable( $mintlms_table, $wpdb->prefix );
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $mintlms_table ) );
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery

delete_option( 'mintlms_db_version' );
delete_option( 'mintlms_first_run_complete' );
delete_option( 'mintlms_guided_course_complete' );
delete_option( 'mintlms_certificate_template' );

$pageIds = array(
	(int) get_option( PageSettings::OPTION_DASHBOARD, 0 ),
	(int) get_option( PageSettings::OPTION_CATALOG, 0 ),
	(int) get_option( PageSettings::OPTION_PLAYER, 0 ),
);

$deletePages = (bool) get_option( 'mintlms_uninstall_delete_pages', false );

delete_option( PageSettings::OPTION_DASHBOARD );
delete_option( PageSettings::OPTION_CATALOG );
delete_option( PageSettings::OPTION_PLAYER );
delete_option( PageSettings::OPTION_DEFAULT_ENROLLMENT );
delete_option( PageSettings::OPTION_EMAIL_ENROLL );
delete_option( PageSettings::OPTION_EMAIL_COMPLETE );
delete_option( 'mintlms_uninstall_delete_pages' );

if ( $deletePages ) {
	foreach ( array_unique( array_filter( $pageIds ) ) as $pageId ) {
		wp_delete_post( $pageId, true );
	}
}

remove_role( 'mintlms_instructor' );

$caps = array(
	'manage_mintlms',
	'edit_mintlms_courses',
	'edit_others_mintlms_courses',
	'enroll_mintlms_students',
	'view_mintlms_reports',
);

foreach ( array( 'administrator', 'mintlms_instructor' ) as $role_slug ) {
	$role = get_role( $role_slug );
	if ( null === $role ) {
		continue;
	}

	foreach ( $caps as $cap ) {
		$role->remove_cap( $cap );
	}
}
