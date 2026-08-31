<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Admin\ViewRenderer;

$renderer = new ViewRenderer();
?>
<div class="wrap mint-lms-admin-wrap">
	<?php echo MintLMS\Infrastructure\Ui\UiRoot::open( 'admin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div x-data="mintGuidedCourse()" style="min-height:100vh;display:flex;flex-direction:column;background:var(--mint-bg)">

			<!-- Progress bar -->
			<div style="height:4px;background:#DCDAEA;flex:none">
				<div style="height:100%;background:var(--mint-accent);transition:width 300ms ease-out;border-radius:0 2px 2px 0" :style="'width:' + (step / 3 * 100) + '%'"></div>
			</div>

			<!-- Header -->
			<header style="height:64px;flex:none;border-bottom:1px solid rgba(15,14,26,0.08);display:flex;align-items:center;justify-content:space-between;padding:0 28px">
				<!-- Wizard steps -->
				<div class="mint-wizard-steps">
					<template x-for="i in 3" :key="i">
						<div style="display:contents">
							<div
								class="mint-wizard-step"
								:class="i < step ? 'mint-wizard-step--done' : (i === step ? 'mint-wizard-step--active' : 'mint-wizard-step--pending')"
								style="width:30px;height:30px;font-size:14px"
							>
								<template x-if="i < step">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
								</template>
								<template x-if="i >= step">
									<span x-text="i"></span>
								</template>
							</div>
							<template x-if="i < 3">
								<div
									class="mint-wizard-connector"
									:class="i < step ? 'mint-wizard-connector--done' : ''"
								></div>
							</template>
						</div>
					</template>
				</div>

				<button type="button" class="mint-btn mint-btn--ghost mint-btn--sm" @click="skip()" :disabled="loading">
					<?php esc_html_e( 'Skip setup', 'mint-lms' ); ?>
				</button>
			</header>

			<!-- Content area -->
			<div style="flex:1;display:flex;align-items:flex-start;justify-content:center;padding:56px 28px 40px">
				<div style="max-width:580px;width:100%">

					<!-- Step overline -->
					<span class="mint-overline" style="color:var(--mint-accent)" x-text="stepLabel"></span>

					<!-- Title -->
					<h1 style="font-size:38px;line-height:44px;letter-spacing:-0.035em;font-weight:600;color:var(--mint-ink);margin:12px 0 0" x-text="stepTitle"></h1>

					<!-- Subtitle per step -->
					<p style="font-size:19px;line-height:30px;color:var(--mint-ink-2);margin:12px 0 0" x-show="step === 1">
						<?php esc_html_e( 'Students see this on your course page.', 'mint-lms' ); ?>
					</p>
					<p style="font-size:19px;line-height:30px;color:var(--mint-ink-2);margin:12px 0 0" x-show="step === 2" x-cloak>
						<?php esc_html_e( 'Every course needs at least one section and lesson.', 'mint-lms' ); ?>
					</p>
					<p style="font-size:19px;line-height:30px;color:var(--mint-ink-2);margin:12px 0 0" x-show="step === 3" x-cloak>
						<?php esc_html_e( 'Publish now or keep editing as a draft.', 'mint-lms' ); ?>
					</p>

					<!-- Loading -->
					<div x-show="loading" x-cloak style="padding:48px 0;text-align:center;font-size:16px;color:var(--mint-ink-2)">
						<?php esc_html_e( 'Saving…', 'mint-lms' ); ?>
					</div>

					<!-- Step 1: course info -->
					<template x-if="step === 1 && !loading">
						<div style="margin-top:36px;display:flex;flex-direction:column;gap:24px">
							<div>
								<label for="mint-guided-course-title" style="font-size:16px;font-weight:600;color:var(--mint-ink);display:block;margin-bottom:8px">
									<?php esc_html_e( 'Course title', 'mint-lms' ); ?> <span style="color:var(--mint-danger)">*</span>
								</label>
								<input
									id="mint-guided-course-title"
									type="text"
									x-model="courseTitle"
									class="mint-input"
									style="height:50px;font-size:17px"
									placeholder="<?php echo esc_attr__( 'Introduction to Photography', 'mint-lms' ); ?>"
								/>
							</div>
							<div>
								<label for="mint-guided-course-description" style="font-size:16px;font-weight:600;color:var(--mint-ink);display:block;margin-bottom:8px">
									<?php esc_html_e( 'Description', 'mint-lms' ); ?>
								</label>
								<p style="font-size:15px;color:var(--mint-ink-2);margin:0 0 8px"><?php esc_html_e( 'A short summary shown on the course landing page.', 'mint-lms' ); ?></p>
								<textarea
									id="mint-guided-course-description"
									x-model="courseDescription"
									rows="4"
									class="mint-textarea"
									placeholder="<?php echo esc_attr__( 'What will students learn?', 'mint-lms' ); ?>"
								></textarea>
							</div>
						</div>
					</template>

					<!-- Step 2: section + lesson -->
					<template x-if="step === 2 && !loading">
						<div style="margin-top:36px;display:flex;flex-direction:column;gap:24px">
							<div>
								<label for="mint-guided-section-title" style="font-size:16px;font-weight:600;color:var(--mint-ink);display:block;margin-bottom:8px">
									<?php esc_html_e( 'Section name', 'mint-lms' ); ?> <span style="color:var(--mint-danger)">*</span>
								</label>
								<p style="font-size:15px;color:var(--mint-ink-2);margin:0 0 8px"><?php esc_html_e( 'Group related lessons together.', 'mint-lms' ); ?></p>
								<input
									id="mint-guided-section-title"
									type="text"
									x-model="sectionTitle"
									class="mint-input"
									style="height:50px;font-size:17px"
									placeholder="<?php echo esc_attr__( 'Getting started', 'mint-lms' ); ?>"
								/>
							</div>
							<div>
								<label for="mint-guided-lesson-title" style="font-size:16px;font-weight:600;color:var(--mint-ink);display:block;margin-bottom:8px">
									<?php esc_html_e( 'First lesson', 'mint-lms' ); ?> <span style="color:var(--mint-danger)">*</span>
								</label>
								<p style="font-size:15px;color:var(--mint-ink-2);margin:0 0 8px"><?php esc_html_e( 'You can add more lessons later in the builder.', 'mint-lms' ); ?></p>
								<input
									id="mint-guided-lesson-title"
									type="text"
									x-model="lessonTitle"
									class="mint-input"
									style="height:50px;font-size:17px"
									placeholder="<?php echo esc_attr__( 'Welcome & overview', 'mint-lms' ); ?>"
								/>
							</div>
						</div>
					</template>

					<!-- Step 3: publish -->
					<template x-if="step === 3 && !loading">
						<div style="margin-top:48px;text-align:center">
							<div style="display:inline-flex;width:64px;height:64px;align-items:center;justify-content:center;border-radius:16px;background:var(--mint-success-wash);color:var(--mint-success);margin-bottom:20px">
								<svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
							</div>
							<p style="font-size:20px;font-weight:600;color:var(--mint-ink);margin:0" x-text="courseTitle"></p>
							<p style="font-size:16px;color:var(--mint-ink-2);margin:8px 0 0"><?php esc_html_e( 'Ready to go live', 'mint-lms' ); ?></p>
						</div>
					</template>

					<!-- Error -->
					<div x-show="error" x-cloak style="margin-top:24px;padding:14px 18px;border-radius:10px;background:var(--mint-danger-wash);font-size:15px;color:var(--mint-danger)" x-text="error"></div>
				</div>
			</div>

			<!-- Footer -->
			<footer style="flex:none;border-top:1px solid rgba(15,14,26,0.08);padding:16px 28px;display:flex;align-items:center;justify-content:space-between">
				<div>
					<button
						type="button"
						class="mint-btn mint-btn--ghost"
						x-show="step > 1"
						x-cloak
						@click="prevStep()"
						:disabled="loading"
					><?php esc_html_e( 'Back', 'mint-lms' ); ?></button>
				</div>

				<div style="display:flex;align-items:center;gap:16px">
					<template x-if="step < 3">
						<div style="display:flex;align-items:center;gap:16px">
							<span style="font-size:15px;color:var(--mint-ink-3)"><?php esc_html_e( 'Takes about 2 minutes', 'mint-lms' ); ?></span>
							<button type="button" class="mint-btn mint-btn--primary" @click="nextStep()" :disabled="loading">
								<span x-text="loading ? '<?php echo esc_js( __( 'Saving…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Continue', 'mint-lms' ) ); ?>'"></span>
							</button>
						</div>
					</template>
					<template x-if="step === 3">
						<div style="display:flex;gap:10px">
							<button type="button" class="mint-btn mint-btn--secondary" @click="finish(false)" :disabled="loading"><?php esc_html_e( 'Save draft', 'mint-lms' ); ?></button>
							<button type="button" class="mint-btn mint-btn--primary" @click="finish(true)" :disabled="loading"><?php esc_html_e( 'Publish', 'mint-lms' ); ?></button>
						</div>
					</template>
				</div>
			</footer>

		</div>

		<?php echo $renderer->component( 'toast' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo MintLMS\Infrastructure\Ui\UiRoot::close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
<style>.mint-lms-admin-wrap{margin:0;padding:0}</style>
