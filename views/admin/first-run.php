<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

use MintLMS\Infrastructure\Admin\FirstRunRedirect;
?>
<div class="wrap mint-lms-admin-wrap mint-m-0 mint-p-0">
	<?php echo MintLMS\Infrastructure\Ui\UiRoot::open( 'admin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div class="mint-grid mint-min-h-screen mint-grid-cols-1 md:mint-grid-cols-[repeat(auto-fit,minmax(360px,1fr))]">

			<div class="mint-flex mint-flex-col mint-justify-between mint-p-10 mint-px-10">
				<div>
					<div class="mint-flex mint-items-center mint-gap-[11px]">
						<div class="mint-flex mint-h-7 mint-w-7 mint-shrink-0 mint-items-center mint-justify-center mint-rounded-lg mint-bg-accent mint-outline mint-outline-[2.5px] mint-outline-offset-[-1px] mint-outline-bg">
							<div class="mint-h-[10px] mint-w-[10px] mint-rounded-[3px] mint-border-2 mint-border-neutral-50"></div>
						</div>
						<span class="mint-text-[17px] mint-font-semibold mint-tracking-tight mint-text-ink"><?php esc_html_e( 'Mint LMS', 'mint-lms' ); ?></span>
					</div>

					<h1 class="mint-m-0 mint-mt-14 mint-max-w-[420px] mint-text-[44px] mint-font-semibold mint-leading-[48px] mint-tracking-[-0.04em] mint-text-ink">
						<?php esc_html_e( 'Teach what you already know.', 'mint-lms' ); ?>
					</h1>
					<p class="mint-m-0 mint-mt-4 mint-max-w-[420px] mint-text-[19px] mint-leading-[30px] mint-text-ink-2">
						<?php esc_html_e( 'Structured lessons, clear progress, and a focused learning experience — without configuring fifty settings first.', 'mint-lms' ); ?>
					</p>

					<div class="mint-mt-11 mint-flex mint-flex-col mint-gap-7">
						<?php
						$features = array(
							array(
								'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 3L4 8v8l8 5 8-5V8l-8-5z"/><path d="M12 12l8-5M12 12v9M12 12L4 7"/></svg>',
								'title' => __( 'Course builder', 'mint-lms' ),
								'desc'  => __( 'Drag-and-drop sections and lessons with a visual editor.', 'mint-lms' ),
							),
							array(
								'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
								'title' => __( 'Student progress', 'mint-lms' ),
								'desc'  => __( 'See who finished what — completion rates, time spent, and streaks.', 'mint-lms' ),
							),
							array(
								'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>',
								'title' => __( 'Works in minutes', 'mint-lms' ),
								'desc'  => __( 'No config maze. Create a course, add lessons, publish.', 'mint-lms' ),
							),
						);
						foreach ( $features as $f ) :
							?>
							<div class="mint-flex mint-items-start mint-gap-4">
								<div class="mint-flex mint-h-[38px] mint-w-[38px] mint-shrink-0 mint-items-center mint-justify-center mint-rounded-lg mint-bg-accent-wash mint-text-accent">
									<?php echo $f['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
								<div>
									<div class="mint-text-base mint-font-semibold mint-leading-[22px] mint-text-ink"><?php echo esc_html( $f['title'] ); ?></div>
									<div class="mint-mt-[2px] mint-text-sm mint-leading-[22px] mint-text-ink-2"><?php echo esc_html( $f['desc'] ); ?></div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<p class="mint-mt-12 mint-text-sm mint-text-ink-3">
					<?php esc_html_e( 'Trusted by educators who want simplicity over bloat.', 'mint-lms' ); ?>
				</p>
			</div>

			<div class="mint-flex mint-items-center mint-justify-center mint-bg-tint-pane mint-p-10 mint-px-10">
				<div class="mint-w-full mint-max-w-[400px]">
					<span class="mint-text-over mint-font-semibold mint-uppercase mint-text-accent"><?php esc_html_e( "LET'S START", 'mint-lms' ); ?></span>
					<h2 class="mint-m-0 mint-mt-3 mint-text-h2 mint-font-semibold mint-text-ink">
						<?php esc_html_e( 'What are you teaching?', 'mint-lms' ); ?>
					</h2>

					<form method="post" action="<?php echo esc_url( FirstRunRedirect::startGuidedUrl() ); ?>" class="mint-mt-8">
						<label for="mint-first-run-title" class="mint-mb-2 mint-block mint-text-base mint-font-semibold mint-text-ink">
							<?php esc_html_e( 'Course name', 'mint-lms' ); ?>
						</label>
						<input
							id="mint-first-run-title"
							type="text"
							name="course_title"
							placeholder="<?php echo esc_attr__( 'e.g. Introduction to Photography', 'mint-lms' ); ?>"
							class="mint-box-border mint-block mint-h-[50px] mint-w-full mint-rounded-lg mint-border-[1.5px] mint-border-[#CBC8DD] mint-bg-bg mint-px-4 mint-text-[17px] mint-text-ink focus:mint-border-accent focus:mint-shadow-focus focus:mint-outline-none"
						/>

						<button type="submit" class="mint-mt-5 mint-inline-flex mint-h-12 mint-w-full mint-items-center mint-justify-center mint-rounded-lg mint-bg-accent mint-text-[17px] mint-font-semibold mint-text-neutral-50 hover:mint-bg-accent-hover focus:mint-shadow-focus focus:mint-outline-none">
							<?php esc_html_e( 'Create your first course', 'mint-lms' ); ?>
						</button>
					</form>

					<div class="mint-mt-4 mint-text-center">
						<a
							href="<?php echo esc_url( FirstRunRedirect::skipFirstRunUrl() ); ?>"
							class="mint-inline-flex mint-h-auto mint-items-center mint-justify-center mint-rounded-lg mint-bg-transparent mint-px-4 mint-py-2 mint-text-sm mint-font-medium mint-text-ink-2 mint-no-underline hover:mint-bg-bg-subtle hover:mint-text-ink"
						><?php esc_html_e( 'Skip for now', 'mint-lms' ); ?></a>
					</div>
				</div>
			</div>

		</div>

	<?php echo MintLMS\Infrastructure\Ui\UiRoot::close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
