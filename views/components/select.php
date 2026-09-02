<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

$name     = $name ?? '';
$id       = $id ?? $name;
$label    = $label ?? '';
$value    = $value ?? '';
$options  = $options ?? array();
$required = $required ?? false;
$error    = $error ?? '';
$attrs    = $attrs ?? '';

$selectClasses = 'mint-block mint-w-full mint-px-3 mint-py-2 mint-text-sm mint-text-neutral-900 mint-bg-neutral-50 mint-border mint-border-neutral-300 mint-rounded-md focus:mint-outline-none focus:mint-ring-2 focus:mint-ring-accent focus:mint-border-accent disabled:mint-opacity-50';

if ( '' !== $error ) {
	$selectClasses .= ' mint-border-neutral-800';
}
?>
<div class="mint-space-y-1">
	<?php if ( '' !== $label ) : ?>
		<label for="<?php echo esc_attr( $id ); ?>" class="mint-block mint-text-sm mint-font-medium mint-text-neutral-700">
			<?php echo esc_html( $label ); ?>
			<?php if ( $required ) : ?>
				<span class="mint-text-neutral-800" aria-hidden="true">*</span>
			<?php endif; ?>
		</label>
	<?php endif; ?>
	<select
		name="<?php echo esc_attr( $name ); ?>"
		id="<?php echo esc_attr( $id ); ?>"
		class="<?php echo esc_attr( $selectClasses ); ?>"
		<?php echo $required ? 'required' : ''; ?>
		<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller supplies validated attributes. ?>
	>
		<?php foreach ( $options as $optionValue => $optionLabel ) : ?>
			<option value="<?php echo esc_attr( (string) $optionValue ); ?>" <?php selected( (string) $value, (string) $optionValue ); ?>>
				<?php echo esc_html( (string) $optionLabel ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php if ( '' !== $error ) : ?>
		<p class="mint-text-xs mint-text-neutral-800" role="alert"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>
</div>
