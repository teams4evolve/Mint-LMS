<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

$message = $message ?? __( 'We couldn\'t load this course. Please try again.', 'mint-lms' );
?>
<div class="mint-edge-screen">
	<div class="mint-edge-panel">
		<div class="mint-icon-well mint-icon-well--md mint-icon-well--danger">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--mint-danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
		</div>
		<h1 class="mint-edge-title"><?php esc_html_e( 'Something went wrong', 'mint-lms' ); ?></h1>
		<p class="mint-edge-text" role="alert"><?php echo esc_html( $message ); ?></p>
		<button type="button" class="mint-btn mint-btn--secondary" onclick="window.location.reload()">
			<?php esc_html_e( 'Try again', 'mint-lms' ); ?>
		</button>
	</div>
</div>
