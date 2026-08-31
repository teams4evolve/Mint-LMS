<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$message     = $message ?? __( 'This lesson is part of a course that requires enrollment. Enroll to unlock all lessons.', 'mint-lms' );
$overviewUrl = $overviewUrl ?? home_url( '/' );
$isLoggedIn  = $isLoggedIn ?? false;
$loginUrl    = $loginUrl ?? wp_login_url();
$enrollUrl   = $enrollUrl ?? $overviewUrl;
?>
<div class="mint-edge-screen">
	<div class="mint-edge-panel">
		<div class="mint-icon-well mint-icon-well--md mint-icon-well--warn">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#7A4E08" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
		</div>
		<h1 class="mint-edge-title"><?php esc_html_e( 'This lesson is locked', 'mint-lms' ); ?></h1>
		<p class="mint-edge-text" role="alert"><?php echo esc_html( $message ); ?></p>
		<div class="mint-edge-actions">
			<a href="<?php echo esc_url( $enrollUrl ); ?>" class="mint-btn mint-btn--primary">
				<?php esc_html_e( 'Enroll', 'mint-lms' ); ?>
			</a>
			<?php if ( ! $isLoggedIn ) : ?>
				<a href="<?php echo esc_url( $loginUrl ); ?>" class="mint-btn mint-btn--secondary">
					<?php esc_html_e( 'Sign in', 'mint-lms' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>
