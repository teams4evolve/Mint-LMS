<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

$newCourseUrl  = $newCourseUrl ?? admin_url( 'admin.php?page=mint-lms-guided-course' );
$pageLabel     = $pageLabel ?? '';
$headerActions = $headerActions ?? '';
$searchUrl     = admin_url( 'admin.php?page=mint-lms-courses&focus=search' );
?>
<header class="mint-sticky mint-top-0 mint-z-20 mint-border-b mint-border-rule mint-bg-bg/95 mint-backdrop-blur-sm">
	<div class="mint-mx-auto mint-flex mint-h-header mint-max-w-content mint-items-center mint-justify-between mint-gap-6 mint-px-7">
		<div class="mint-flex mint-min-w-0 mint-items-center mint-gap-3">
			<div class="mint-flex mint-h-[22px] mint-w-[22px] mint-flex-none mint-items-center mint-justify-center mint-rounded-md mint-bg-accent mint-outline mint-outline-2 mint-outline-offset-[-1px] mint-outline-white">
				<div class="mint-size-2 mint-rounded-[2.5px] mint-border-2 mint-border-white"></div>
			</div>
			<span class="mint-text-sm mint-font-semibold mint-tracking-tight mint-text-ink"><?php esc_html_e( 'Mint LMS', 'mint-lms' ); ?></span>
			<?php if ( '' !== $pageLabel ) : ?>
				<div class="mint-mx-0.5 mint-h-4 mint-w-px mint-bg-[#C4C1D6]"></div>
				<span class="mint-truncate mint-text-base mint-text-ink-2"><?php echo esc_html( $pageLabel ); ?></span>
			<?php endif; ?>
		</div>
		<?php if ( '' !== $headerActions ) : ?>
			<div class="mint-flex mint-flex-none mint-items-center mint-gap-2">
				<?php echo $headerActions; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller-constructed button markup. ?>
			</div>
		<?php else : ?>
			<div class="mint-flex mint-flex-none mint-items-center mint-gap-2">
				<a
					href="<?php echo esc_url( $searchUrl ); ?>"
					class="mint-inline-flex mint-h-control mint-w-control mint-items-center mint-justify-center mint-rounded-md mint-border-0 mint-bg-transparent mint-text-ink-2 mint-no-underline mint-transition-colors mint-duration-hover hover:mint-bg-bg-subtle hover:mint-text-ink focus-visible:mint-shadow-focus"
					aria-label="<?php esc_attr_e( 'Search courses', 'mint-lms' ); ?>"
				>
					<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" aria-hidden="true"><circle cx="9" cy="9" r="5.5"/><path d="m13.2 13.2 3 3"/></svg>
				</a>
				<a
					href="<?php echo esc_url( $newCourseUrl ); ?>"
					class="mint-inline-flex mint-h-control mint-items-center mint-justify-center mint-gap-2 mint-rounded-md mint-border-0 mint-bg-accent mint-px-3 mint-text-sm mint-font-semibold mint-text-white mint-no-underline mint-transition-colors mint-duration-hover hover:mint-bg-accent-hover focus-visible:mint-shadow-focus"
				>
					<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M10 4.5v11M4.5 10h11"/></svg>
					<?php esc_html_e( 'New course', 'mint-lms' ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
</header>
