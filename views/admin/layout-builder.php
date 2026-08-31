<?php
declare(strict_types=1);
defined( 'ABSPATH' ) || exit;
$content = $content ?? '';
?>
<div class="wrap mint-lms-admin-wrap mint-lms-builder-page">
	<?php echo MintLMS\Infrastructure\Ui\UiRoot::open( 'admin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php // Toast component rendered inside builder content ?>
	<?php echo MintLMS\Infrastructure\Ui\UiRoot::close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
<style>
	.mint-lms-builder-page{margin:0!important;padding:0!important}
	.mint-lms-builder-page #wpbody-content{padding-bottom:0}
</style>
