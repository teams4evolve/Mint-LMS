<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

use MintLMS\Infrastructure\Admin\ViewRenderer;

$renderer = new ViewRenderer();

$content = $renderer->render(
	'admin/courses/_list-content',
	array(
		'renderer' => $renderer,
	)
);

$renderer->echo(
	'admin/layout',
	array(
		'pageLabel' => __( 'Courses', 'mint-lms' ),
		'content'   => $content,
	)
);
