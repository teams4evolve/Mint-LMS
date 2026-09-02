<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

$message = $message ?? __( 'You need to sign in before you can access this course content.', 'mint-lms' );
?>
<div class="mint-edge-screen">
	<div class="mint-edge-panel">
		<div class="mint-icon-well mint-icon-well--md mint-icon-well--accent">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--mint-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
		</div>
		<h1 class="mint-edge-title"><?php esc_html_e( 'Sign in to continue', 'mint-lms' ); ?></h1>
		<p class="mint-edge-text"><?php echo esc_html( $message ); ?></p>
		<a
			href="<?php echo esc_url( wp_login_url( get_permalink() ?: home_url( '/' ) ) ); ?>"
			class="mint-btn mint-btn--primary"
		>
			<?php esc_html_e( 'Sign in', 'mint-lms' ); ?>
		</a>
	</div>
</div>
