<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$value = max( 0, (int) ( $value ?? 0 ) );
$max   = max( 1, (int) ( $max ?? 100 ) );
$label = $label ?? '';
$attrs = $attrs ?? '';

$percent = min( 100, (int) round( ( $value / $max ) * 100 ) );
?>
<div class="mint-w-full" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( '' !== $label ) : ?>
		<div class="mint-flex mint-justify-between mint-mb-1">
			<span class="mint-text-sm mint-font-medium mint-text-neutral-700"><?php echo esc_html( $label ); ?></span>
			<span class="mint-text-sm mint-text-neutral-500"><?php echo esc_html( (string) $percent ); ?>%</span>
		</div>
	<?php endif; ?>
	<div
		class="mint-w-full mint-h-2 mint-bg-neutral-200 mint-rounded-full mint-overflow-hidden"
		role="progressbar"
		aria-valuenow="<?php echo esc_attr( (string) $value ); ?>"
		aria-valuemin="0"
		aria-valuemax="<?php echo esc_attr( (string) $max ); ?>"
	>
		<div
			class="mint-h-full mint-bg-accent mint-rounded-full mint-transition-all mint-duration-300"
			style="width: <?php echo esc_attr( (string) $percent ); ?>%"
		></div>
	</div>
</div>
