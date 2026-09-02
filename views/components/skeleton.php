<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

$lines   = max( 1, (int) ( $lines ?? 3 ) );
$variant = $variant ?? 'text';
$attrs   = $attrs ?? '';
?>
<div class="mint-animate-pulse mint-space-y-3" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( 'card' === $variant ) : ?>
		<div class="mint-skeleton-block"></div>
	<?php elseif ( 'avatar' === $variant ) : ?>
		<div class="mint-flex mint-items-center mint-gap-3">
			<div class="mint-skeleton-avatar"></div>
			<div class="mint-flex-1 mint-space-y-2">
				<div class="mint-skeleton-line mint-w-1/3"></div>
				<div class="mint-skeleton-line mint-w-1/2"></div>
			</div>
		</div>
	<?php else : ?>
		<?php for ( $i = 0; $i < $lines; $i++ ) : ?>
			<div class="mint-skeleton-line <?php echo 0 === $i % 3 ? 'mint-w-full' : ( 1 === $i % 3 ? 'mint-w-5/6' : 'mint-w-4/6' ); ?>"></div>
		<?php endfor; ?>
	<?php endif; ?>
</div>
