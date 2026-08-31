<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Admin\ViewRenderer;

/** @var int $courseId */

$renderer   = new ViewRenderer();
$builderUrl = admin_url( 'admin.php?page=mint-lms-builder&course_id=' . $courseId );

$content = $renderer->render(
	'admin/courses/_edit-content',
	array(
		'renderer' => $renderer,
		'courseId'  => $courseId,
	)
);

$headerActions = '<a href="' . esc_url( $builderUrl ) . '" class="mint-btn mint-btn--secondary mint-btn--xs" style="text-decoration:none">'
	. esc_html__( 'Open builder', 'mint-lms' )
	. '</a>'
	. '<button type="button" class="mint-btn mint-btn--primary mint-btn--xs" onclick="window.dispatchEvent(new CustomEvent(\'mint-save-course\'))">'
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
