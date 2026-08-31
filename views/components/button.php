<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$variant = $variant ?? 'primary';
$label   = $label ?? '';
$type    = $type ?? 'button';
$size    = $size ?? '';
$attrs   = $attrs ?? '';

$classes = 'mint-btn';

if ( 'primary' === $variant ) {
	$classes .= ' mint-btn--primary';
} elseif ( 'secondary' === $variant ) {
	$classes .= ' mint-btn--secondary';
} elseif ( 'ghost' === $variant ) {
	$classes .= ' mint-btn--ghost';
} elseif ( 'danger' === $variant ) {
	$classes .= ' mint-btn--danger';
}

if ( 'sm' === $size ) {
	$classes .= ' mint-btn--sm';
} elseif ( 'lg' === $size ) {
	$classes .= ' mint-btn--lg';
}
?>
<button type="<?php echo esc_attr( $type ); ?>" class="<?php echo esc_attr( $classes ); ?>" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo esc_html( $label ); ?>
</button>
