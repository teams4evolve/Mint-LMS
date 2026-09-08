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

$btnSecondary = 'mint-inline-flex mint-h-[38px] mint-items-center mint-justify-center mint-rounded-md mint-border-[1.5px] mint-border-[#B9B6CE] mint-bg-white mint-px-[15px] mint-text-[15px] mint-font-semibold mint-text-ink mint-no-underline mint-transition-colors mint-duration-hover hover:mint-bg-[#F4F3F8] focus-visible:mint-outline-none focus-visible:mint-shadow-[0_0_0_3px_rgba(63,0,255,0.32)]';
$btnPrimary   = 'mint-settings-save mint-inline-flex mint-h-[38px] mint-items-center mint-justify-center mint-rounded-md mint-border-0 mint-bg-cta mint-px-4 mint-text-[15px] mint-font-semibold mint-text-cta-ink mint-transition-colors mint-duration-hover focus-visible:mint-outline-none';

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
		'maxWidthClass' => 'mint-max-w-[1000px]',
		'content'       => $content,
	)
);
