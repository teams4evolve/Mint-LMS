<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

/** @var MintLMS\Infrastructure\Admin\ViewRenderer $renderer */
/** @var int $courseId */

$coursesUrl = admin_url( 'admin.php?page=mint-lms-courses' );

$inputClass = 'mint-settings-input mint-block mint-w-full mint-rounded-md mint-border-[1.5px] mint-border-[#B9B6CE] mint-bg-white mint-px-[14px] mint-text-[17px] mint-leading-7 mint-text-ink focus:mint-outline-none';
?>
<div
	x-data="courseEdit(<?php echo esc_attr( (string) $courseId ); ?>)"
	x-init="init()"
	@mint-save-course.window="save()"
	@mint-publish-course.window="publish()"
>
	<div x-show="loading" x-cloak class="mint-pt-10">
		<?php
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'skeleton',
			array( 'variant' => 'card' )
		);
		?>
	</div>

	<div x-show="error && !loading" x-cloak class="mint-pt-10">
		<?php
		$mintlms_retry_button = $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'button',
			array(
				'variant' => 'secondary',
				'label'   => esc_html__( 'Try again', 'mint-lms' ),
				'type'    => 'button',
				'attrs'   => '@click="init()"',
			)
		);
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'error-state',
			array(
				'title'        => esc_html__( 'Could not load course', 'mint-lms' ),
				'messageAttrs' => 'x-text="error"',
				'retry'        => $mintlms_retry_button, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer component HTML.
			)
		);
		?>
	</div>

	<div x-show="!loading && !error" x-cloak>
		<nav class="mint-flex mint-items-center mint-gap-3 mint-pb-2 mint-pt-12 mint-text-base mint-text-ink-2">
			<a :href="coursesDashboardUrl()" @click="rememberCoursesTab(form.status)" class="mint-font-medium mint-text-ink-2 mint-no-underline hover:mint-text-ink"><?php esc_html_e( 'Courses', 'mint-lms' ); ?></a>
			<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="#33334A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
			<span class="mint-truncate" x-text="form.title || '<?php echo esc_attr__( 'Untitled', 'mint-lms' ); ?>'"></span>
		</nav>

		<h1 class="mint-m-0 mint-pb-8 mint-text-[48px] mint-font-semibold mint-leading-[52px] mint-tracking-[-0.04em] mint-text-ink">
			<?php esc_html_e( 'Course settings', 'mint-lms' ); ?>
		</h1>

		<form class="mint-max-w-[560px] mint-pb-[72px]" @submit.prevent="save()">
			<div class="mint-grid mint-gap-8">

				<div class="mint-grid mint-gap-[26px]">
					<div class="mint-grid mint-gap-[9px]">
						<label for="mint-course-title" class="mint-text-base mint-font-semibold mint-text-ink">
							<?php esc_html_e( 'Course name', 'mint-lms' ); ?>
						</label>
						<input
							id="mint-course-title"
							type="text"
							class="<?php echo esc_attr( $inputClass ); ?> mint-h-[50px]"
							x-model="form.title"
							required
						/>
						<p class="mint-m-0 mint-text-[15px] mint-text-ink-2"><?php esc_html_e( 'Students see this on their dashboard.', 'mint-lms' ); ?></p>
					</div>

					<div class="mint-grid mint-gap-[9px]">
						<label for="mint-course-desc" class="mint-text-base mint-font-semibold mint-text-ink">
							<?php esc_html_e( 'Short description', 'mint-lms' ); ?>
						</label>
						<textarea
							id="mint-course-desc"
							class="<?php echo esc_attr( $inputClass ); ?> mint-min-h-[110px] mint-resize-y mint-py-[13px]"
							x-model="form.description"
						></textarea>
						<p class="mint-m-0 mint-text-[15px] mint-text-ink-2"><?php esc_html_e( 'Two sentences is plenty.', 'mint-lms' ); ?></p>
					</div>
				</div>

				<section class="mint-grid mint-gap-[14px] mint-border-t-[1.5px] mint-border-[#DAD7E6] mint-pt-8">
					<div class="mint-text-[13px] mint-font-bold mint-uppercase mint-tracking-[0.08em] mint-text-ink-2">
						<?php esc_html_e( 'Cover image', 'mint-lms' ); ?>
					</div>

					<div
						x-show="!featuredImageUrl"
						class="mint-cover-placeholder mint-flex mint-h-[200px] mint-items-center mint-justify-center mint-rounded-[14px] mint-border-[1.5px] mint-border-dashed mint-border-[#A79FE0]"
					>
						<span class="mint-font-mono mint-text-[13px] mint-text-ink-2"><?php esc_html_e( 'cover image · 1200×675', 'mint-lms' ); ?></span>
					</div>
					<div
						x-show="featuredImageUrl"
						x-cloak
						class="mint-h-[200px] mint-overflow-hidden mint-rounded-[14px] mint-border-[1.5px] mint-border-[#DAD7E6]"
					>
						<img :src="featuredImageUrl" alt="" class="mint-h-full mint-w-full mint-object-cover" />
					</div>

					<p class="mint-m-0 mint-text-base mint-leading-[26px] mint-text-ink-2">
						<?php esc_html_e( 'Optional. Shown on the student dashboard and course page. Landscape works best.', 'mint-lms' ); ?>
					</p>

					<div class="mint-flex mint-flex-wrap mint-gap-2.5">
						<button
							type="button"
							class="mint-settings-action mint-inline-flex mint-h-[42px] mint-cursor-pointer mint-items-center mint-justify-center mint-rounded-md mint-border-0 mint-bg-cta mint-px-4 mint-text-base mint-font-semibold mint-text-cta-ink"
							@click="pickImage()"
						><?php esc_html_e( 'Upload image', 'mint-lms' ); ?></button>
						<button
							type="button"
							class="mint-settings-action mint-inline-flex mint-h-[42px] mint-cursor-pointer mint-items-center mint-justify-center mint-rounded-md mint-border-0 mint-bg-cta mint-px-[15px] mint-text-base mint-font-semibold mint-text-cta-ink"
							@click="clearImage()"
						><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
					</div>
				</section>

				<section class="mint-grid mint-gap-5 mint-border-t-[1.5px] mint-border-[#DAD7E6] mint-pt-8">
					<div class="mint-text-[13px] mint-font-bold mint-uppercase mint-tracking-[0.08em] mint-text-ink-2">
						<?php esc_html_e( 'Who can see it', 'mint-lms' ); ?>
					</div>

					<div class="mint-grid mint-gap-5">
						<?php
						$visibility_options = array(
							array(
								'value' => 'published',
								'label' => __( 'Live', 'mint-lms' ),
								'desc'  => __( 'Anyone you enroll can start it now.', 'mint-lms' ),
							),
							array(
								'value' => 'draft',
								'label' => __( 'Draft', 'mint-lms' ),
								'desc'  => __( "Only you can see it. Students can't open it yet.", 'mint-lms' ),
							),
							array(
								'value' => 'archived',
								'label' => __( 'Hidden', 'mint-lms' ),
								'desc'  => __( 'Enrolled students keep access; nobody new can join.', 'mint-lms' ),
							),
						);
						foreach ( $visibility_options as $opt ) :
							?>
						<label
							class="mint-flex mint-cursor-pointer mint-items-start mint-gap-[13px] mint-rounded-md mint-outline-none focus-within:mint-shadow-[0_0_0_3px_rgba(152,251,203,0.55)]"
							@click="form.status = '<?php echo esc_attr( $opt['value'] ); ?>'"
						>
							<span
								class="mint-settings-radio mint-mt-[3px] mint-shrink-0"
								:class="form.status === '<?php echo esc_attr( $opt['value'] ); ?>' ? 'mint-settings-radio--on' : ''"
								role="radio"
								tabindex="0"
								:aria-checked="form.status === '<?php echo esc_attr( $opt['value'] ); ?>' ? 'true' : 'false'"
								@keydown.space.prevent="form.status = '<?php echo esc_attr( $opt['value'] ); ?>'"
							></span>
							<span>
								<span class="mint-block mint-text-[17px] mint-font-semibold mint-text-ink"><?php echo esc_html( $opt['label'] ); ?></span>
								<span class="mint-mt-0.5 mint-block mint-text-base mint-leading-6 mint-text-ink-2"><?php echo esc_html( $opt['desc'] ); ?></span>
							</span>
						</label>
						<?php endforeach; ?>
					</div>
				</section>

				<section class="mint-grid mint-gap-5 mint-border-t-[1.5px] mint-border-[#DAD7E6] mint-pt-8">
					<div class="mint-text-[13px] mint-font-bold mint-uppercase mint-tracking-[0.08em] mint-text-ink-2">
						<?php esc_html_e( 'Enrollment', 'mint-lms' ); ?>
					</div>

					<div class="mint-grid mint-gap-5">
						<?php
						$enrollment_options = array(
							array(
								'value' => 'open',
								'label' => __( 'Open', 'mint-lms' ),
								'desc'  => __( 'Students can enroll themselves for free.', 'mint-lms' ),
							),
							array(
								'value' => 'manual',
								'label' => __( 'Manual', 'mint-lms' ),
								'desc'  => __( 'Only you (or staff) can enroll students.', 'mint-lms' ),
							),
							array(
								'value' => 'paid',
								'label' => __( 'Paid (WooCommerce)', 'mint-lms' ),
								'desc'  => __( 'Students buy a WooCommerce product to get access.', 'mint-lms' ),
							),
						);
						foreach ( $enrollment_options as $opt ) :
							?>
						<label
							class="mint-flex mint-cursor-pointer mint-items-start mint-gap-[13px] mint-rounded-md mint-outline-none focus-within:mint-shadow-[0_0_0_3px_rgba(152,251,203,0.55)]"
							@click="form.enrollmentType = '<?php echo esc_attr( $opt['value'] ); ?>'"
						>
							<span
								class="mint-settings-radio mint-mt-[3px] mint-shrink-0"
								:class="form.enrollmentType === '<?php echo esc_attr( $opt['value'] ); ?>' ? 'mint-settings-radio--on' : ''"
								role="radio"
								tabindex="0"
								:aria-checked="form.enrollmentType === '<?php echo esc_attr( $opt['value'] ); ?>' ? 'true' : 'false'"
								@keydown.space.prevent="form.enrollmentType = '<?php echo esc_attr( $opt['value'] ); ?>'"
							></span>
							<span>
								<span class="mint-block mint-text-[17px] mint-font-semibold mint-text-ink"><?php echo esc_html( $opt['label'] ); ?></span>
								<span class="mint-mt-0.5 mint-block mint-text-base mint-leading-6 mint-text-ink-2"><?php echo esc_html( $opt['desc'] ); ?></span>
							</span>
						</label>
						<?php endforeach; ?>
					</div>

					<div
						x-show="woocommerceAvailable && (form.enrollmentType === 'paid' || commerce.productId)"
						x-cloak
						class="mint-grid mint-gap-3 mint-rounded-[14px] mint-border-[1.5px] mint-border-[#DAD7E6] mint-bg-[#F7F6FB] mint-p-4"
					>
						<div class="mint-text-[15px] mint-font-semibold mint-text-ink"><?php esc_html_e( 'Sell with WooCommerce', 'mint-lms' ); ?></div>
						<p class="mint-m-0 mint-text-sm mint-leading-5 mint-text-ink-2">
							<?php esc_html_e( 'Creates a “Mint LMS Course” product linked to this course. Buyers are enrolled when the order is processing or completed.', 'mint-lms' ); ?>
						</p>
						<div class="mint-flex mint-flex-wrap mint-items-end mint-gap-3">
							<div class="mint-grid mint-gap-1.5">
								<label for="mint-wc-price" class="mint-text-sm mint-font-semibold mint-text-ink"><?php esc_html_e( 'Price', 'mint-lms' ); ?></label>
								<input
									id="mint-wc-price"
									type="text"
									inputmode="decimal"
									class="<?php echo esc_attr( $inputClass ); ?> mint-h-[42px] mint-w-[140px]"
									x-model="wcPrice"
									placeholder="29.00"
								/>
							</div>
							<button
								type="button"
								class="mint-settings-action mint-inline-flex mint-h-[42px] mint-cursor-pointer mint-items-center mint-justify-center mint-rounded-md mint-border-0 mint-bg-cta mint-px-4 mint-text-base mint-font-semibold mint-text-cta-ink disabled:mint-opacity-50"
								:disabled="creatingProduct || !wcPrice"
								@click="createWooProduct()"
							>
								<span x-text="commerce.productId ? '<?php echo esc_js( __( 'Update product', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Create product', 'mint-lms' ) ); ?>'"></span>
							</button>
							<template x-if="commerce.editUrl">
								<a :href="commerce.editUrl" target="_blank" rel="noopener noreferrer" class="mint-inline-flex mint-h-[42px] mint-items-center mint-text-base mint-font-semibold mint-text-[#0B4F3F] mint-no-underline">
									<?php esc_html_e( 'Edit in WooCommerce', 'mint-lms' ); ?>
								</a>
							</template>
						</div>
						<p x-show="commerce.productId" x-cloak class="mint-m-0 mint-text-sm mint-text-ink-2">
							<span><?php esc_html_e( 'Linked product #', 'mint-lms' ); ?></span><span x-text="commerce.productId"></span>
						</p>
					</div>

					<p
						x-show="!woocommerceAvailable && form.enrollmentType === 'paid'"
						x-cloak
						class="mint-m-0 mint-text-sm mint-text-[#C22B2B]"
					>
						<?php esc_html_e( 'WooCommerce is not active. Install and activate WooCommerce to sell this course.', 'mint-lms' ); ?>
					</p>
				</section>

				<section class="mint-grid mint-gap-5 mint-border-t-[1.5px] mint-border-[#DAD7E6] mint-pt-8">
					<div class="mint-text-[13px] mint-font-bold mint-uppercase mint-tracking-[0.08em] mint-text-ink-2">
						<?php esc_html_e( 'Options', 'mint-lms' ); ?>
					</div>

					<div class="mint-grid mint-gap-5">
						<template x-for="option in options" :key="option.key">
							<div class="mint-flex mint-items-center mint-justify-between mint-gap-4">
								<div
									class="mint-text-[17px] mint-font-medium"
									:class="option.on ? 'mint-text-ink' : 'mint-text-ink-2'"
									x-text="option.label"
								></div>
								<button
									type="button"
									class="mint-settings-toggle"
									:class="option.on ? 'mint-settings-toggle--on' : 'mint-settings-toggle--off'"
									:aria-pressed="option.on ? 'true' : 'false'"
									@click="option.on = !option.on"
								>
									<span class="mint-settings-toggle__knob"></span>
								</button>
							</div>
						</template>
					</div>
				</section>

				<section class="mint-flex mint-flex-wrap mint-items-center mint-justify-between mint-gap-4 mint-border-t-[1.5px] mint-border-[#DAD7E6] mint-pt-8">
					<div>
						<div class="mint-text-[17px] mint-font-semibold mint-text-ink"><?php esc_html_e( 'Delete this course', 'mint-lms' ); ?></div>
						<div class="mint-mt-0.5 mint-text-base mint-text-ink-2" x-text="deleteHint()"></div>
					</div>
					<button
						type="button"
						class="mint-settings-delete mint-inline-flex mint-h-[42px] mint-cursor-pointer mint-items-center mint-justify-center mint-rounded-md mint-border-[1.5px] mint-border-[#D98B84] mint-bg-white mint-px-[18px] mint-text-base mint-font-semibold mint-text-[#C22B2B]"
						@click="deleteCourse()"
					><?php esc_html_e( 'Delete course', 'mint-lms' ); ?></button>
				</section>

			</div>
		</form>
	</div>
</div>
