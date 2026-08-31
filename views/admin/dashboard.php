<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Admin\CreatorDashboardService;
use MintLMS\Infrastructure\Admin\MintUiDashboardPresentation;
use MintLMS\Infrastructure\Admin\ViewRenderer;
use MintLMS\Infrastructure\Database\Repository\WpdbAdminDashboardRepository;
use MintLMS\Infrastructure\Database\Repository\WpdbCourseRepository;
use MintLMS\Infrastructure\Ui\MintUi;
use MintLMS\Infrastructure\User\WpUserLookup;

global $wpdb;

$renderer = new ViewRenderer();
$userId   = get_current_user_id();
$user     = wp_get_current_user();
$showDetails = MintUi::showDetailsPreference( $userId );

$dashboardService = new CreatorDashboardService(
	new WpdbCourseRepository( $wpdb ),
	new WpdbAdminDashboardRepository( $wpdb ),
	new MintUiDashboardPresentation(),
	new WpUserLookup(),
);

$dashboard = $dashboardService->getDashboard( $userId );

$content = $renderer->render(
	'admin/dashboard/_dashboard-content',
	array(
		'dashboard'   => $dashboard,
		'firstName'   => $user->first_name ?: $user->display_name,
		'showDetails' => $showDetails,
		'userId'      => $userId,
	)
);

$renderer->echo(
	'admin/layout',
	array(
		'content' => $content,
	)
);
