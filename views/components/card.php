<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$title   = $title ?? '';
$body    = $body ?? '';
$footer  = $footer ?? '';
$attrs   = $attrs ?? '';
$padding = $padding ?? true;
?>
<div class="mint-bg-neutral-50 mint-border mint-border-neutral-200 mint-rounded-lg mint-shadow-sm <?php echo $padding ? 'mint-p-6' : ''; ?>" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( '' !== $title ) : ?>
		<div class="mint-border-b mint-border-neutral-200 mint-pb-4 mint-mb-4">
			<h3 class="mint-text-lg mint-font-medium mint-text-neutral-900"><?php echo esc_html( $title ); ?></h3>
		</div>
	<?php endif; ?>
	<?php if ( '' !== $body ) : ?>
		<div class="mint-text-sm mint-text-neutral-600">
			<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller provides escaped HTML fragments. ?>
		</div>
	<?php endif; ?>
	<?php if ( '' !== $footer ) : ?>
		<div class="mint-border-t mint-border-neutral-200 mint-pt-4 mint-mt-4">
			<?php echo $footer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	<?php endif; ?>
</div>
