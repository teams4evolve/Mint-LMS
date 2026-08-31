<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Admin\ViewRenderer;

/** @var int $courseId */

$renderer = new ViewRenderer();

$content = $renderer->render(
	'admin/courses/_builder-content',
	array(
		'renderer' => $renderer,
		'courseId'  => $courseId,
	)
);

$renderer->echo(
	'admin/layout-builder',
	array(
		'content' => $content,
	)
);
