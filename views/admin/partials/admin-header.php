<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

$newCourseUrl  = $newCourseUrl ?? admin_url( 'admin.php?page=mint-lms-guided-course' );
$pageLabel     = $pageLabel ?? '';
$headerActions = $headerActions ?? '';
?>
<header class="mint-admin-header">
	<div class="mint-admin-header__inner">
		<div class="mint-admin-brand">
			<div class="mint-admin-brand__mark">
				<div class="mint-admin-brand__mark-inner"></div>
			</div>
			<span class="mint-admin-brand__name"><?php esc_html_e( 'Mint LMS', 'mint-lms' ); ?></span>
			<?php if ( '' !== $pageLabel ) : ?>
				<div class="mint-admin-brand__sep"></div>
				<span class="mint-admin-brand__page"><?php echo esc_html( $pageLabel ); ?></span>
			<?php endif; ?>
		</div>
		<?php if ( '' !== $headerActions ) : ?>
			<div class="mint-admin-header__actions">
				<?php echo $headerActions; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller-constructed button markup. ?>
			</div>
		<?php else : ?>
			<div class="mint-admin-header__actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mint-lms-courses&focus=search' ) ); ?>" class="mint-btn mint-btn--icon mint-btn--ghost" aria-label="<?php esc_attr_e( 'Search courses', 'mint-lms' ); ?>">
					<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="var(--mint-ink-3)" stroke-width="1.85" stroke-linecap="round" aria-hidden="true"><circle cx="9" cy="9" r="5.5"/><path d="m13.2 13.2 3 3"/></svg>
				</a>
				<a href="<?php echo esc_url( $newCourseUrl ); ?>" class="mint-btn mint-btn--primary mint-btn--xs">
					<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M10 4.5v11M4.5 10h11"/></svg>
					<?php esc_html_e( 'New course', 'mint-lms' ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
</header>
