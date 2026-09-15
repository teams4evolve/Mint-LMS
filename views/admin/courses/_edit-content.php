<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

/** @var MintLMS\Infrastructure\Admin\ViewRenderer $renderer */
/** @var int $courseId */

$coursesUrl = \MintLMS\Infrastructure\PostType\PostTypes::listUrl( \MintLMS\Infrastructure\PostType\PostTypes::COURSE );

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
		<?php
		$builderUrl = admin_url( 'admin.php?page=mint-lms-builder&course_id=' . $courseId );
		$treeUrl    = admin_url( 'admin.php?page=mint-lms-builder&course_id=' . $courseId . '&view=tree' );
		?>
		<nav class="mint-course-switcher" aria-label="<?php echo esc_attr__( 'Course navigation', 'mint-lms' ); ?>">
			<a
				class="mint-course-switcher__home"
				:href="coursesDashboardUrl()"
				@click="rememberCoursesTab(form.status)"
				aria-label="<?php echo esc_attr__( 'Back to courses', 'mint-lms' ); ?>"
			>
				<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<rect x="4" y="4" width="12" height="12" rx="2.5" />
				</svg>
			</a>

			<div class="mint-course-switcher__links">
				<button
					type="button"
					class="mint-course-switcher__link"
					:class="editPanel === 'page' ? 'is-active' : ''"
					:aria-current="editPanel === 'page' ? 'page' : null"
					@click="setEditPanel('page')"
				>
					<?php esc_html_e( 'Course Page', 'mint-lms' ); ?>
				</button>
				<button
					type="button"
					class="mint-course-switcher__link"
					:class="editPanel === 'settings' ? 'is-active' : ''"
					:aria-current="editPanel === 'settings' ? 'page' : null"
					@click="setEditPanel('settings')"
				>
					<?php esc_html_e( 'Course Settings', 'mint-lms' ); ?>
				</button>
				<a class="mint-course-switcher__link" href="<?php echo esc_url( $builderUrl ); ?>">
					<?php esc_html_e( 'Course Builder', 'mint-lms' ); ?>
				</a>
				<a class="mint-course-switcher__link" href="<?php echo esc_url( $treeUrl ); ?>">
					<?php esc_html_e( 'Course Content Tree', 'mint-lms' ); ?>
				</a>
			</div>

			<span class="mint-course-switcher__title" x-text="form.title || '<?php echo esc_attr__( 'Untitled Course', 'mint-lms' ); ?>'"></span>
		</nav>

		<nav class="mint-flex mint-items-center mint-gap-3 mint-pb-2 mint-pt-8 mint-text-base mint-text-ink-2">
			<a :href="coursesDashboardUrl()" @click="rememberCoursesTab(form.status)" class="mint-font-medium mint-text-ink-2 mint-no-underline hover:mint-text-ink"><?php esc_html_e( 'Courses', 'mint-lms' ); ?></a>
			<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="#33334A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
			<span class="mint-truncate" x-text="form.title || '<?php echo esc_attr__( 'Untitled', 'mint-lms' ); ?>'"></span>
		</nav>

		<div class="mint-course-settings-layout" :class="editPanel === 'settings' ? 'is-settings-only' : ''">
			<div class="mint-course-settings-main">
				<h1
					x-show="editPanel === 'page'"
					x-cloak
					class="mint-m-0 mint-mt-6 mint-pb-8 mint-text-[48px] mint-font-semibold mint-leading-[52px] mint-tracking-[-0.04em] mint-text-ink"
				>
					<?php esc_html_e( 'Course Page', 'mint-lms' ); ?>
				</h1>
				<h1
					x-show="editPanel === 'settings'"
					x-cloak
					class="mint-m-0 mint-mt-6 mint-pb-8 mint-text-[48px] mint-font-semibold mint-leading-[52px] mint-tracking-[-0.04em] mint-text-ink"
				>
					<?php esc_html_e( 'Course settings', 'mint-lms' ); ?>
				</h1>

				<form class="mint-w-full mint-pb-[72px]" @submit.prevent="save()">
					<div class="mint-grid mint-gap-8">

						<div x-show="editPanel === 'page'" x-cloak class="mint-grid mint-gap-5">
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

							<section class="mint-grid mint-gap-[14px] mint-border-t-[1.5px] mint-border-[#DAD7E6] mint-pt-5">
								<div class="mint-text-[13px] mint-font-bold mint-uppercase mint-tracking-[0.08em] mint-text-ink-2">
									<?php esc_html_e( 'Cover image', 'mint-lms' ); ?>
								</div>

								<div
									x-show="!featuredImageUrl"
									class="mint-cover-frame mint-cover-placeholder mint-flex mint-items-center mint-justify-center mint-rounded-[14px] mint-border-[1.5px] mint-border-dashed mint-border-[#A79FE0]"
								>
									<span class="mint-font-mono mint-text-[13px] mint-text-ink-2"><?php esc_html_e( 'cover image · 800×800', 'mint-lms' ); ?></span>
								</div>
								<div
									x-show="featuredImageUrl"
									x-cloak
									class="mint-cover-frame mint-overflow-hidden mint-rounded-[14px] mint-border-[1.5px] mint-border-[#DAD7E6]"
								>
									<img :src="featuredImageUrl" alt="" class="mint-h-full mint-w-full mint-object-cover" />
								</div>

								<p class="mint-m-0 mint-text-base mint-leading-[26px] mint-text-ink-2">
									<?php esc_html_e( 'Optional. Shown on the student dashboard and course page. A square image looks best.', 'mint-lms' ); ?>
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
						</div>

						<div x-show="editPanel === 'settings'" x-cloak class="mint-settings-stack">

						<section class="mint-settings-card">
							<header class="mint-settings-card__header">
								<h2 class="mint-settings-card__title"><?php esc_html_e( 'How students join', 'mint-lms' ); ?></h2>
							</header>
							<div class="mint-settings-card__body">
								<div class="mint-settings-choice-grid">
									<?php
									$enrollment_options = array(
										array(
											'value' => 'open',
											'label' => __( 'Open for everyone', 'mint-lms' ),
											'desc'  => __( 'Browse without signing up.', 'mint-lms' ),
										),
										array(
											'value' => 'free',
											'label' => __( 'Free — login to join', 'mint-lms' ),
											'desc'  => __( 'Students join after login.', 'mint-lms' ),
										),
										array(
											'value' => 'manual',
											'label' => __( 'Invite only', 'mint-lms' ),
											'desc'  => __( 'Only you add students.', 'mint-lms' ),
										),
										array(
											'value' => 'paid',
											'label' => __( 'Paid (WooCommerce)', 'mint-lms' ),
											'desc'  => __( 'Buy a product to join.', 'mint-lms' ),
										),
									);
									foreach ( $enrollment_options as $opt ) :
										?>
									<button
										type="button"
										class="mint-settings-choice"
										:class="form.enrollmentType === '<?php echo esc_attr( $opt['value'] ); ?>' ? 'is-selected' : ''"
										@click="form.enrollmentType = '<?php echo esc_attr( $opt['value'] ); ?>'"
									>
										<span class="mint-settings-choice__radio" aria-hidden="true"></span>
										<span class="mint-settings-choice__copy">
											<span class="mint-settings-choice__label"><?php echo esc_html( $opt['label'] ); ?></span>
											<span class="mint-settings-choice__desc"><?php echo esc_html( $opt['desc'] ); ?></span>
										</span>
									</button>
									<?php endforeach; ?>
								</div>

								<div
									x-show="woocommerceAvailable && (form.enrollmentType === 'paid' || commerce.productId)"
									x-cloak
									class="mint-settings-nested"
								>
									<div class="mint-settings-nested__title"><?php esc_html_e( 'WooCommerce product', 'mint-lms' ); ?></div>
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
									<?php esc_html_e( 'WooCommerce is not active.', 'mint-lms' ); ?>
								</p>
							</div>
						</section>

						<section class="mint-settings-card">
							<header class="mint-settings-card__header">
								<h2 class="mint-settings-card__title"><?php esc_html_e( 'Lesson order', 'mint-lms' ); ?></h2>
							</header>
							<div class="mint-settings-card__body">
								<div class="mint-settings-choice-grid mint-settings-choice-grid--2">
									<button
										type="button"
										class="mint-settings-choice"
										:class="form.progression === 'linear' ? 'is-selected' : ''"
										@click="form.progression = 'linear'"
									>
										<span class="mint-settings-choice__radio" aria-hidden="true"></span>
										<span class="mint-settings-choice__copy">
											<span class="mint-settings-choice__label"><?php esc_html_e( 'One step at a time', 'mint-lms' ); ?></span>
											<span class="mint-settings-choice__desc"><?php esc_html_e( 'Finish each lesson before the next.', 'mint-lms' ); ?></span>
										</span>
									</button>
									<button
										type="button"
										class="mint-settings-choice"
										:class="form.progression === 'freeform' ? 'is-selected' : ''"
										@click="form.progression = 'freeform'"
									>
										<span class="mint-settings-choice__radio" aria-hidden="true"></span>
										<span class="mint-settings-choice__copy">
											<span class="mint-settings-choice__label"><?php esc_html_e( 'Explore freely', 'mint-lms' ); ?></span>
											<span class="mint-settings-choice__desc"><?php esc_html_e( 'Open any lesson anytime.', 'mint-lms' ); ?></span>
										</span>
									</button>
								</div>
							</div>
						</section>

						<section class="mint-settings-card">
							<header class="mint-settings-card__header mint-settings-card__header--row">
								<h2 class="mint-settings-card__title"><?php esc_html_e( 'Required courses first', 'mint-lms' ); ?></h2>
								<button
									type="button"
									class="mint-settings-toggle"
									:class="form.prerequisitesEnabled ? 'mint-settings-toggle--on' : 'mint-settings-toggle--off'"
									:aria-pressed="form.prerequisitesEnabled ? 'true' : 'false'"
									@click="form.prerequisitesEnabled = !form.prerequisitesEnabled"
								>
									<span class="mint-settings-toggle__knob"></span>
								</button>
							</header>
							<div x-show="form.prerequisitesEnabled" x-cloak class="mint-settings-card__body">
								<div class="mint-settings-choice-grid mint-settings-choice-grid--2">
									<button
										type="button"
										class="mint-settings-choice"
										:class="form.prerequisiteCompare === 'ANY' ? 'is-selected' : ''"
										@click="form.prerequisiteCompare = 'ANY'"
									>
										<span class="mint-settings-choice__radio" aria-hidden="true"></span>
										<span class="mint-settings-choice__copy">
											<span class="mint-settings-choice__label"><?php esc_html_e( 'Any one', 'mint-lms' ); ?></span>
											<span class="mint-settings-choice__desc"><?php esc_html_e( 'One completed course is enough.', 'mint-lms' ); ?></span>
										</span>
									</button>
									<button
										type="button"
										class="mint-settings-choice"
										:class="form.prerequisiteCompare === 'ALL' ? 'is-selected' : ''"
										@click="form.prerequisiteCompare = 'ALL'"
									>
										<span class="mint-settings-choice__radio" aria-hidden="true"></span>
										<span class="mint-settings-choice__copy">
											<span class="mint-settings-choice__label"><?php esc_html_e( 'All of them', 'mint-lms' ); ?></span>
											<span class="mint-settings-choice__desc"><?php esc_html_e( 'Every selected course required.', 'mint-lms' ); ?></span>
										</span>
									</button>
								</div>

								<div class="mint-prereq-list">
									<template x-for="course in prerequisiteChoices" :key="course.id">
										<button
											type="button"
											class="mint-prereq-row"
											:class="isPrerequisiteSelected(course.id) ? 'is-selected' : ''"
											@click="togglePrerequisite(course.id)"
										>
											<span class="mint-prereq-row__check" aria-hidden="true"></span>
											<span class="mint-prereq-row__title" x-text="course.title"></span>
										</button>
									</template>
									<p x-show="prerequisiteChoices.length === 0" class="mint-m-0 mint-text-sm mint-text-ink-2">
										<?php esc_html_e( 'No other courses yet.', 'mint-lms' ); ?>
									</p>
								</div>
							</div>
						</section>

						<section class="mint-settings-card">
							<header class="mint-settings-card__header">
								<h2 class="mint-settings-card__title"><?php esc_html_e( 'Access limits', 'mint-lms' ); ?></h2>
							</header>
							<div class="mint-settings-card__body mint-settings-card__body--rows">
								<div class="mint-settings-row">
									<div class="mint-settings-row__main">
										<div class="mint-settings-row__label"><?php esc_html_e( 'Days after joining', 'mint-lms' ); ?></div>
										<p class="mint-settings-row__hint"><?php esc_html_e( 'Access ends after this many days.', 'mint-lms' ); ?></p>
									</div>
									<div class="mint-settings-row__control mint-settings-row__control--inline">
										<button
											type="button"
											class="mint-settings-toggle"
											:class="form.expireAccess ? 'mint-settings-toggle--on' : 'mint-settings-toggle--off'"
											:aria-pressed="form.expireAccess ? 'true' : 'false'"
											@click="form.expireAccess = !form.expireAccess"
										>
											<span class="mint-settings-toggle__knob"></span>
										</button>
										<input
											x-show="form.expireAccess"
											x-cloak
											id="mint-expire-days"
											type="number"
											min="1"
											max="3650"
											aria-label="<?php echo esc_attr__( 'Days of access', 'mint-lms' ); ?>"
											class="<?php echo esc_attr( $inputClass ); ?> mint-h-[42px] mint-w-[88px]"
											x-model.number="form.expireAccessDays"
										/>
									</div>
								</div>

								<div class="mint-settings-row">
									<div class="mint-settings-row__main">
										<div class="mint-settings-row__label"><?php esc_html_e( 'Course dates', 'mint-lms' ); ?></div>
										<p class="mint-settings-row__hint"><?php esc_html_e( 'Optional. Leave blank for no limit.', 'mint-lms' ); ?></p>
									</div>
									<div class="mint-settings-row__control mint-settings-row__control--dates">
										<label class="mint-settings-date">
											<span><?php esc_html_e( 'Opens', 'mint-lms' ); ?></span>
											<input id="mint-access-start" type="date" class="<?php echo esc_attr( $inputClass ); ?> mint-h-[42px]" x-model="form.accessStartAt" />
										</label>
										<label class="mint-settings-date">
											<span><?php esc_html_e( 'Closes', 'mint-lms' ); ?></span>
											<input id="mint-access-end" type="date" class="<?php echo esc_attr( $inputClass ); ?> mint-h-[42px]" x-model="form.accessEndAt" />
										</label>
									</div>
								</div>

								<div class="mint-settings-row">
									<div class="mint-settings-row__main">
										<div class="mint-settings-row__label"><?php esc_html_e( 'Seat limit', 'mint-lms' ); ?></div>
										<p class="mint-settings-row__hint"><?php esc_html_e( '0 = unlimited self-joins.', 'mint-lms' ); ?></p>
									</div>
									<div class="mint-settings-row__control">
										<input
											type="number"
											min="0"
											aria-label="<?php echo esc_attr__( 'Seat limit', 'mint-lms' ); ?>"
											class="<?php echo esc_attr( $inputClass ); ?> mint-h-[42px] mint-w-[88px]"
											x-model.number="form.seatLimit"
										/>
									</div>
								</div>
							</div>
						</section>

						<section class="mint-settings-card">
							<header class="mint-settings-card__header">
								<h2 class="mint-settings-card__title"><?php esc_html_e( 'Extras', 'mint-lms' ); ?></h2>
							</header>
							<div class="mint-settings-card__body mint-settings-card__body--rows">
								<template x-for="option in options" :key="option.key">
									<div class="mint-settings-row">
										<div class="mint-settings-row__main">
											<div
												class="mint-settings-row__label"
												:class="option.on ? '' : 'is-muted'"
												x-text="option.label"
											></div>
										</div>
										<div class="mint-settings-row__control">
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
									</div>
								</template>
							</div>
						</section>

						</div>

					</div>
				</form>
			</div>

			<aside
				x-show="editPanel === 'page'"
				x-cloak
				class="mint-course-sidebar"
				aria-label="<?php echo esc_attr__( 'Course status', 'mint-lms' ); ?>"
			>
				<div class="mint-course-sidebar__intro">
					<p class="mint-course-sidebar__kicker"><?php esc_html_e( 'Course status', 'mint-lms' ); ?></p>
					<p class="mint-course-sidebar__hint" x-text="statusHint()"></p>
				</div>

				<button
					type="button"
					class="mint-course-sidebar__visibility"
					:class="'is-' + form.status"
					@click="visibilityOpen = !visibilityOpen"
					:aria-expanded="visibilityOpen ? 'true' : 'false'"
				>
					<span class="mint-course-sidebar__visibility-copy">
						<span class="mint-course-sidebar__visibility-label"><?php esc_html_e( 'Who can see it', 'mint-lms' ); ?></span>
						<span class="mint-course-sidebar__visibility-state" x-text="statusBadgeLabel()"></span>
					</span>
					<span class="mint-course-sidebar__visibility-chevron" :class="visibilityOpen ? 'is-open' : ''" aria-hidden="true">
						<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 8 5 5 5-5" /></svg>
					</span>
				</button>

				<div
					x-show="visibilityOpen"
					x-cloak
					class="mint-course-sidebar__chooser"
					role="group"
					aria-label="<?php echo esc_attr__( 'Who can see it', 'mint-lms' ); ?>"
				>
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
						class="mint-course-sidebar__choice"
						:class="form.status === '<?php echo esc_attr( $opt['value'] ); ?>' ? 'is-selected' : ''"
						@click="form.status = '<?php echo esc_attr( $opt['value'] ); ?>'"
					>
						<span
							class="mint-settings-radio"
							:class="form.status === '<?php echo esc_attr( $opt['value'] ); ?>' ? 'mint-settings-radio--on' : ''"
							role="radio"
							:aria-checked="form.status === '<?php echo esc_attr( $opt['value'] ); ?>' ? 'true' : 'false'"
						></span>
						<span>
							<span class="mint-course-sidebar__choice-title"><?php echo esc_html( $opt['label'] ); ?></span>
							<span class="mint-course-sidebar__choice-desc"><?php echo esc_html( $opt['desc'] ); ?></span>
						</span>
					</label>
					<?php endforeach; ?>
				</div>

				<div class="mint-course-sidebar__facts">
					<div class="mint-course-sidebar__fact">
						<span class="mint-course-sidebar__fact-label"><?php esc_html_e( 'Last updated', 'mint-lms' ); ?></span>
						<span class="mint-course-sidebar__fact-value" x-text="formatPublishDate()"></span>
					</div>
					<div class="mint-course-sidebar__fact">
						<span class="mint-course-sidebar__fact-label"><?php esc_html_e( 'URL slug', 'mint-lms' ); ?></span>
						<span class="mint-course-sidebar__fact-value mint-course-sidebar__fact-value--mono" x-text="meta.slug || '—'"></span>
					</div>
					<div class="mint-course-sidebar__fact">
						<span class="mint-course-sidebar__fact-label"><?php esc_html_e( 'Author', 'mint-lms' ); ?></span>
						<span class="mint-course-sidebar__fact-value" x-text="meta.authorName || '—'"></span>
					</div>
					<div class="mint-course-sidebar__fact">
						<span class="mint-course-sidebar__fact-label"><?php esc_html_e( 'Revisions', 'mint-lms' ); ?></span>
						<span class="mint-course-sidebar__fact-value" x-text="meta.revisionCount"></span>
					</div>
				</div>

				<button
					type="button"
					class="mint-course-sidebar__trash"
					@click="deleteCourse()"
				><?php esc_html_e( 'Move to trash', 'mint-lms' ); ?></button>
			</aside>
		</div>
	</div>
</div>
