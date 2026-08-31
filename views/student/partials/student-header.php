<?php
declare(strict_types=1);
defined( 'ABSPATH' ) || exit;
$pageTitle    = $pageTitle ?? '';
$user         = wp_get_current_user();
$userName     = $userName ?? ($user->display_name ?: '');
$userInitials = $userInitials ?? MintLMS\Infrastructure\Ui\MintUi::initials($userName);
$hue          = MintLMS\Infrastructure\Ui\MintUi::hueByIndex(1);
?>
<div class="mint-student-header">
	<div class="mint-student-header__title"><?php echo esc_html($pageTitle); ?></div>
	<div class="mint-student-header__meta">
		<?php if ( '' !== $userName ) : ?>
		<div class="mint-student-header__user"><?php echo esc_html($userName); ?></div>
		<?php endif; ?>
		<div class="mint-avatar mint-avatar--lg" style="background:<?php echo esc_attr($hue['bg']); ?>;color:<?php echo esc_attr($hue['ink']); ?>"><?php echo esc_html($userInitials); ?></div>
	</div>
</div>
