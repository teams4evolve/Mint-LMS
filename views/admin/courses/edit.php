<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

use MintLMS\Infrastructure\Admin\ViewRenderer;

/** @var ViewRenderer $renderer */
/** @var int $courseId */

$courseId = isset( $courseId ) ? (int) $courseId : 0;

if ( $courseId <= 0 ) {
	$courseId = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

$builderUrl = admin_url( 'admin.php?page=mint-lms-builder&course_id=' . $courseId );

$content = $renderer->render(
	'admin/courses/_edit-content',
	array(
		'renderer' => $renderer,
		'courseId'  => $courseId,
	)
);

$btnSecondary = 'mint-inline-flex mint-h-control mint-items-center mint-justify-center mint-rounded-md mint-border mint-border-control mint-bg-bg mint-px-[13px] mint-text-sm mint-font-semibold mint-text-ink mint-no-underline hover:mint-bg-bg-subtle';
$btnPrimary   = 'mint-inline-flex mint-h-control mint-items-center mint-justify-center mint-rounded-md mint-bg-accent mint-px-[13px] mint-text-sm mint-font-semibold mint-text-neutral-50 mint-border-0 hover:mint-bg-accent-hover';

$headerActions = '<a href="' . esc_url( $builderUrl ) . '" class="' . esc_attr( $btnSecondary ) . '">'
	. esc_html__( 'Open builder', 'mint-lms' )
	. '</a>'
	. '<button type="button" class="' . esc_attr( $btnPrimary ) . '" onclick="window.dispatchEvent(new CustomEvent(\'mint-save-course\'))">'
	. esc_html__( 'Save changes', 'mint-lms' )
	. '</button>';

$renderer->echo(
	'admin/layout',
	array(
		'pageTitle'     => __( 'Course Settings', 'mint-lms' ),
		'pageLabel'     => __( 'Course settings', 'mint-lms' ),
		'activeNav'     => 'courses',
		'headerActions' => $headerActions,
		'content'       => $content,
	)
);
