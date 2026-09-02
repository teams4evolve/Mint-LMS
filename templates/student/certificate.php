<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

$certificateHtml = $certificateHtml ?? '';
$downloadUrl     = $downloadUrl ?? '';
?>
<div class="mint-certificate-page">
	<div class="mint-certificate-page__actions no-print">
		<?php if ( '' !== $downloadUrl ) : ?>
			<a href="<?php echo esc_url( $downloadUrl ); ?>" class="mint-btn mint-btn--primary" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Open printable certificate', 'mint-lms' ); ?>
			</a>
		<?php endif; ?>
		<button type="button" class="mint-btn mint-btn--secondary" onclick="window.print()">
			<?php esc_html_e( 'Print', 'mint-lms' ); ?>
		</button>
	</div>

	<div class="mint-certificate-printable">
		<?php echo $certificateHtml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Admin-controlled template with escaped placeholders. ?>
	</div>
</div>

<style>
@media print {
	.no-print { display: none !important; }
	body { background: #fff; }
	.mint-certificate-printable {
		margin: 0 auto;
		max-width: 800px;
		padding: 48px;
		border: 2px solid #111;
	}
}
.mint-certificate-page__actions {
	display: flex;
	gap: 12px;
	margin-bottom: 24px;
}
.mint-certificate-printable {
	background: #fff;
	border: 1px solid var(--mint-border, #ddd);
	border-radius: 12px;
	padding: 48px;
	text-align: center;
}
.mint-certificate__title {
	font-size: 2rem;
	margin: 0 0 1rem;
}
.mint-certificate__body {
	font-size: 1.125rem;
	line-height: 1.6;
}
</style>
