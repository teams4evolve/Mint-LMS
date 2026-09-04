<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

/** @var MintLMS\Infrastructure\Admin\ViewRenderer $renderer */
/** @var int $courseId */

$coursesUrl = admin_url( 'admin.php?page=mint-lms-courses' );

$btnPrimary       = 'mint-inline-flex mint-h-control mint-items-center mint-justify-center mint-rounded-md mint-border-0 mint-bg-accent mint-px-[13px] mint-text-sm mint-font-semibold mint-text-neutral-50 hover:mint-bg-accent-hover focus-visible:mint-shadow-focus disabled:mint-cursor-not-allowed disabled:mint-opacity-[0.45]';
$btnSecondary     = 'mint-inline-flex mint-h-control mint-items-center mint-justify-center mint-rounded-md mint-border mint-border-control mint-bg-bg mint-px-[13px] mint-text-sm mint-font-semibold mint-text-ink mint-no-underline hover:mint-bg-bg-subtle focus-visible:mint-shadow-focus disabled:mint-cursor-not-allowed disabled:mint-opacity-[0.45]';
$btnGhost         = 'mint-inline-flex mint-h-control mint-items-center mint-justify-center mint-rounded-md mint-border-0 mint-bg-transparent mint-px-[13px] mint-text-sm mint-font-semibold mint-text-ink-2 hover:mint-bg-bg-subtle hover:mint-text-ink focus-visible:mint-shadow-focus disabled:mint-cursor-not-allowed disabled:mint-opacity-[0.45]';
$btnDangerOutline = 'mint-inline-flex mint-h-control mint-items-center mint-justify-center mint-rounded-md mint-border-[1.5px] mint-border-[#D98B84] mint-bg-bg mint-px-[13px] mint-text-sm mint-font-semibold mint-text-danger hover:mint-bg-danger-wash focus-visible:mint-shadow-focus-danger disabled:mint-cursor-not-allowed disabled:mint-opacity-[0.45]';
$btnDark          = 'mint-inline-flex mint-h-control mint-items-center mint-justify-center mint-rounded-md mint-border-0 mint-bg-bg-ink mint-px-[13px] mint-text-sm mint-font-semibold mint-text-neutral-50 hover:mint-opacity-[0.86] focus-visible:mint-shadow-focus disabled:mint-cursor-not-allowed disabled:mint-opacity-[0.45]';
$btnGhostIcon     = 'mint-inline-flex mint-h-control mint-w-control mint-shrink-0 mint-items-center mint-justify-center mint-rounded-md mint-border-0 mint-bg-transparent mint-text-ink-2 mint-no-underline hover:mint-bg-bg-subtle hover:mint-text-ink focus-visible:mint-shadow-focus';

$inputClass       = 'mint-block mint-w-full mint-rounded-lg mint-border mint-border-control mint-bg-bg mint-px-[14px] mint-py-[11px] mint-text-base mint-text-ink focus:mint-border-accent focus:mint-shadow-focus focus:mint-outline-none';
$textareaClass    = $inputClass . ' mint-min-h-[120px] mint-resize-y';
$labelClass       = 'mint-mb-1.5 mint-block mint-text-xs mint-font-semibold mint-text-ink';
$titleInputClass  = 'mint-w-full mint-border-0 mint-border-b-2 mint-border-transparent mint-bg-transparent mint-px-0 mint-py-1 mint-font-sans mint-text-h1 mint-font-semibold mint-text-ink mint-outline-none mint-transition-colors hover:mint-border-rule focus:mint-border-accent';
$treeEditInput    = 'mint-w-full mint-rounded-md mint-border mint-border-control mint-bg-bg mint-px-2 mint-py-1 mint-text-sm mint-text-ink focus:mint-border-accent focus:mint-shadow-focus focus:mint-outline-none';
$treeRowBase      = 'mint-flex mint-cursor-pointer mint-items-center mint-gap-1 mint-rounded-md mint-py-1.5 mint-pr-2 mint-text-sm mint-transition-colors';
$segmentBtn       = 'mint-inline-flex mint-items-center mint-gap-2 mint-rounded-md mint-px-3 mint-py-1.5 mint-text-sm mint-font-medium mint-transition-colors';
?>
<div
	id="mint-course-builder"
	class="mint-flex mint-h-[calc(100vh-32px)] mint-min-h-[calc(100vh-32px)] mint-flex-col mint-bg-bg"
	x-data="courseBuilder(<?php echo esc_attr( (string) $courseId ); ?>)"
	x-init="init()"
>
	<!-- Toolbar -->
	<div class="mint-flex mint-h-header mint-shrink-0 mint-items-center mint-justify-between mint-border-b mint-border-rule mint-px-6">
		<div class="mint-flex mint-min-w-0 mint-flex-1 mint-items-center mint-gap-3">
			<a
				href="<?php echo esc_url( $coursesUrl ); ?>"
				class="<?php echo esc_attr( $btnGhostIcon ); ?>"
				title="<?php echo esc_attr__( 'Back to courses', 'mint-lms' ); ?>"
			>
				<svg class="mint-h-5 mint-w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
				</svg>
			</a>
			<span
				class="mint-min-w-0 mint-truncate mint-text-base mint-font-semibold mint-text-ink"
				x-text="course.title || '<?php echo esc_js( __( 'Untitled course', 'mint-lms' ) ); ?>'"
			></span>
			<span
				class="mint-inline-flex mint-shrink-0 mint-items-center mint-rounded-full mint-bg-bg-subtle mint-px-2 mint-py-0.5 mint-text-xs mint-font-medium mint-text-ink-2"
				x-show="course.status"
				x-text="statusLabel(course.status)"
			></span>
		</div>

		<div class="mint-flex mint-shrink-0 mint-items-center mint-gap-3">
			<span class="mint-text-sm mint-text-ink-2" x-show="saveStatus === 'saving'" x-cloak><?php echo esc_html__( 'Saving…', 'mint-lms' ); ?></span>
			<span class="mint-text-sm mint-text-accent" x-show="saveStatus === 'saved'" x-cloak><?php echo esc_html__( 'Saved', 'mint-lms' ); ?></span>
			<a
				:href="previewUrl"
				target="_blank"
				rel="noopener noreferrer"
				class="<?php echo esc_attr( $btnSecondary ); ?>"
			><?php echo esc_html__( 'Preview', 'mint-lms' ); ?></a>
			<button
				type="button"
				class="<?php echo esc_attr( $btnPrimary ); ?>"
				@click="publishCourse()"
				:disabled="publishing || course.status === 'published'"
			>
				<span x-show="!publishing"><?php echo esc_html__( 'Publish', 'mint-lms' ); ?></span>
				<span x-show="publishing" x-cloak><?php echo esc_html__( 'Publishing…', 'mint-lms' ); ?></span>
			</button>
		</div>
	</div>

	<!-- Loading -->
	<div x-show="loading" x-cloak class="mint-flex mint-flex-1 mint-items-center mint-justify-center">
		<?php
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'skeleton',
			array(
				'lines' => 6,
				'attrs' => 'class="mint-w-full mint-max-w-md mint-px-6"',
			)
		);
		?>
	</div>

	<!-- Error -->
	<div x-show="error && !loading" x-cloak class="mint-flex mint-flex-1 mint-items-center mint-justify-center mint-p-8">
		<div class="mint-text-center">
			<p class="mint-m-0 mint-mb-2 mint-text-h3 mint-font-semibold mint-text-ink"><?php echo esc_html__( 'Could not load course', 'mint-lms' ); ?></p>
			<p class="mint-m-0 mint-mb-4 mint-text-sm mint-text-ink-2" x-text="error"></p>
			<button type="button" class="<?php echo esc_attr( $btnSecondary ); ?>" @click="loadStructure()"><?php echo esc_html__( 'Try again', 'mint-lms' ); ?></button>
		</div>
	</div>

	<!-- Builder body -->
	<div x-show="!loading && !error" x-cloak class="mint-flex mint-min-h-0 mint-flex-1">

		<!-- Contents tree -->
		<aside class="mint-flex mint-w-tree mint-shrink-0 mint-flex-col mint-border-r mint-border-rule mint-bg-tint-pane">
			<div class="mint-flex mint-shrink-0 mint-items-center mint-border-b mint-border-rule mint-px-4 mint-py-3 mint-text-over mint-font-semibold mint-uppercase mint-tracking-wider mint-text-ink-3">
				<span><?php echo esc_html__( 'CONTENTS', 'mint-lms' ); ?></span>
				<span class="mint-ml-1.5" x-text="'· ' + sections.reduce((n, s) => n + s.lessons.length, 0) + ' <?php echo esc_js( __( 'lessons', 'mint-lms' ) ); ?>'"></span>
			</div>

			<div class="mint-min-h-0 mint-flex-1 mint-overflow-y-auto mint-p-2" id="mint-sections-sortable">
				<template x-if="sections.length === 0">
					<div class="mint-flex mint-flex-col mint-items-center mint-gap-3 mint-px-4 mint-py-10 mint-text-center">
						<p class="mint-m-0 mint-text-sm mint-text-ink-3"><?php echo esc_html__( 'No sections yet', 'mint-lms' ); ?></p>
						<button type="button" class="<?php echo esc_attr( $btnPrimary ); ?>" :disabled="addingSection" @click="addSection()"><?php echo esc_html__( 'Add first section', 'mint-lms' ); ?></button>
					</div>
				</template>

				<template x-for="(section, sIndex) in sections" :key="section.id">
					<div :data-section-id="section.id" class="mint-mb-0.5">
						<!-- Section row -->
						<div
							class="<?php echo esc_attr( $treeRowBase ); ?> mint-px-2"
							:class="isSelected('section', section.id) ? 'mint-bg-accent-wash mint-text-accent' : 'mint-text-ink hover:mint-bg-bg-subtle'"
							@click="selectItem('section', section.id)"
						>
							<span
								class="mint-handle-section mint-flex mint-shrink-0 mint-cursor-grab mint-items-center mint-justify-center mint-opacity-50"
								title="<?php echo esc_attr__( 'Drag to reorder', 'mint-lms' ); ?>"
							>
								<svg class="mint-h-3.5 mint-w-3.5" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
							</span>
							<button
								type="button"
								class="mint-flex mint-shrink-0 mint-items-center mint-justify-center mint-rounded mint-border-0 mint-bg-transparent mint-p-0 mint-text-current hover:mint-bg-black/5"
								@click.stop="toggleSection(section.id)"
								:aria-expanded="isExpanded(section.id)"
							>
								<svg
									class="mint-h-[18px] mint-w-[18px] mint-transition-transform mint-duration-menu"
									:class="isExpanded(section.id) ? 'mint-rotate-90' : ''"
									viewBox="0 0 20 20"
									fill="none"
									stroke="currentColor"
									stroke-width="2"
									stroke-linecap="round"
									stroke-linejoin="round"
									aria-hidden="true"
								>
									<path d="M7 5l5 5-5 5"/>
								</svg>
							</button>
							<div class="mint-min-w-0 mint-flex-1" @click.stop>
								<template x-if="editingKey !== 'section-' + section.id">
									<span
										class="mint-block mint-truncate mint-font-semibold"
										@dblclick="startEdit('section', section.id, section.title)"
										x-text="section.title"
									></span>
								</template>
								<template x-if="editingKey === 'section-' + section.id">
									<input
										type="text"
										class="<?php echo esc_attr( $treeEditInput ); ?> mint-font-semibold"
										x-model="editingValue"
										@keydown.enter="commitEdit('section', section.id)"
										@keydown.escape="cancelEdit()"
										@blur="commitEdit('section', section.id)"
										x-ref="editInput"
									/>
								</template>
							</div>
							<span
								class="mint-shrink-0 mint-rounded-full mint-bg-bg-subtle mint-px-1.5 mint-py-0.5 mint-text-xs mint-tabular-nums mint-text-ink-3"
								x-text="section.lessons.length"
							></span>
						</div>

						<!-- Lessons -->
						<div
							x-show="isExpanded(section.id)"
							class="mint-lessons-sortable"
							:data-section-id="section.id"
						>
							<template x-for="(lesson, lIndex) in section.lessons" :key="lesson.id">
								<div
									:data-lesson-id="lesson.id"
									class="<?php echo esc_attr( $treeRowBase ); ?> mint-pl-6"
									:class="isSelected('lesson', lesson.id) ? 'mint-bg-accent-wash mint-text-accent' : 'mint-text-ink-2 hover:mint-bg-bg-subtle'"
									@click="selectItem('lesson', lesson.id, section.id)"
								>
									<span
										class="mint-handle-lesson mint-flex mint-shrink-0 mint-cursor-grab mint-items-center mint-justify-center mint-opacity-40"
										title="<?php echo esc_attr__( 'Drag to reorder', 'mint-lms' ); ?>"
									>
										<svg class="mint-h-3 mint-w-3" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
									</span>
									<span class="mint-flex mint-shrink-0 mint-items-center mint-text-current">
										<template x-if="!lesson.videoUrl">
											<svg class="mint-h-[19px] mint-w-[19px]" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
												<rect x="3" y="2" width="14" height="16" rx="3" />
												<line x1="6.5" y1="6.5" x2="13.5" y2="6.5" />
												<line x1="6.5" y1="10" x2="13.5" y2="10" />
												<line x1="6.5" y1="13.5" x2="10" y2="13.5" />
											</svg>
										</template>
										<template x-if="lesson.videoUrl">
											<svg class="mint-h-[19px] mint-w-[19px]" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
												<rect x="3" y="2" width="14" height="16" rx="3" />
												<polygon points="8.5,6.5 13.5,10 8.5,13.5" fill="currentColor" stroke="none" />
											</svg>
										</template>
									</span>
									<div class="mint-min-w-0 mint-flex-1" @click.stop>
										<template x-if="editingKey !== 'lesson-' + lesson.id">
											<span
												class="mint-block mint-truncate"
												@dblclick="startEdit('lesson', lesson.id, lesson.title)"
												x-text="lesson.title"
											></span>
										</template>
										<template x-if="editingKey === 'lesson-' + lesson.id">
											<input
												type="text"
												class="<?php echo esc_attr( $treeEditInput ); ?>"
												x-model="editingValue"
												@keydown.enter="commitEdit('lesson', lesson.id)"
												@keydown.escape="cancelEdit()"
												@blur="commitEdit('lesson', lesson.id)"
											/>
										</template>
									</div>
								</div>
							</template>

							<button
								type="button"
								class="mint-flex mint-w-full mint-items-center mint-gap-2 mint-rounded-md mint-border-0 mint-bg-transparent mint-py-2 mint-pl-10 mint-pr-2 mint-text-sm mint-font-medium mint-text-ink-3 hover:mint-bg-bg-subtle hover:mint-text-accent disabled:mint-opacity-[0.45]"
								:disabled="addingLesson"
								@click="addLesson(section.id)"
							>
								<svg class="mint-h-[18px] mint-w-[18px]" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
									<line x1="10" y1="4" x2="10" y2="16"/><line x1="4" y1="10" x2="16" y2="10"/>
								</svg>
								<?php echo esc_html__( 'Add lesson', 'mint-lms' ); ?>
							</button>
						</div>
					</div>
				</template>
			</div>

			<div class="mint-shrink-0 mint-border-t mint-border-rule mint-p-3">
				<button
					type="button"
					class="mint-flex mint-w-full mint-items-center mint-justify-center mint-gap-2 mint-rounded-lg mint-border mint-border-dashed mint-bg-transparent mint-py-2.5 mint-text-sm mint-font-semibold mint-text-ink-2 hover:mint-border-control hover:mint-bg-bg-subtle hover:mint-text-ink disabled:mint-opacity-[0.45]"
					:disabled="addingSection"
					@click="addSection()"
				>
					<svg class="mint-h-4 mint-w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
						<line x1="10" y1="4" x2="10" y2="16"/><line x1="4" y1="10" x2="16" y2="10"/>
					</svg>
					<?php echo esc_html__( 'Add section', 'mint-lms' ); ?>
				</button>
			</div>
		</aside>

		<!-- Editor pane -->
		<section class="mint-flex mint-min-w-0 mint-flex-1 mint-flex-col mint-bg-bg">

			<!-- Empty state -->
			<template x-if="!selected">
				<div class="mint-flex mint-h-full mint-items-center mint-justify-center">
					<div class="mint-text-center">
						<div class="mint-mx-auto mint-mb-4 mint-flex mint-h-16 mint-w-16 mint-items-center mint-justify-center mint-rounded-xl mint-bg-accent-wash">
							<svg class="mint-h-7 mint-w-7 mint-text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
								<path d="M9 5l7 7-7 7"/>
							</svg>
						</div>
						<p class="mint-m-0 mint-text-row mint-font-semibold mint-text-ink"><?php echo esc_html__( 'Pick a lesson to edit', 'mint-lms' ); ?></p>
						<p class="mint-m-0 mint-mt-1.5 mint-text-sm mint-text-ink-3"><?php echo esc_html__( 'Select from the contents pane', 'mint-lms' ); ?></p>
					</div>
				</div>
			</template>

			<!-- Section editor -->
			<template x-if="selected && selected.type === 'section'">
				<div class="mint-mx-auto mint-max-w-prose mint-px-10 mint-pb-24 mint-pt-12">
					<span class="mint-mb-6 mint-block mint-text-over mint-font-semibold mint-uppercase mint-text-ink-3"><?php echo esc_html__( 'SECTION', 'mint-lms' ); ?></span>
					<div class="mint-mb-6">
						<input
							type="text"
							class="<?php echo esc_attr( $titleInputClass ); ?>"
							x-model="selectedSection.title"
							@input="debouncedSaveSection(selectedSection)"
							placeholder="<?php echo esc_attr__( 'Section title', 'mint-lms' ); ?>"
						/>
					</div>
					<div class="mint-mt-10 mint-border-t mint-border-rule mint-pt-5">
						<button
							type="button"
							class="<?php echo esc_attr( $btnDangerOutline ); ?>"
							@click="deleteSection(selected.id)"
						>
							<?php echo esc_html__( 'Delete section', 'mint-lms' ); ?>
						</button>
					</div>
				</div>
			</template>

			<!-- Lesson editor -->
			<div
				x-show="selected && selected.type === 'lesson'"
				x-cloak
				class="mint-flex mint-h-full mint-flex-col"
				x-data="{lessonTab: 'written'}"
				x-init="lessonTab = (selectedLesson && selectedLesson.videoUrl) ? 'video' : 'written'"
			>
				<div class="mint-min-h-0 mint-flex-1 mint-overflow-y-auto">
					<div class="mint-mx-auto mint-max-w-prose mint-px-10 mint-pb-24 mint-pt-12">

						<nav class="mint-mb-4 mint-flex mint-items-center mint-gap-1.5 mint-text-sm mint-text-ink-2">
							<span x-text="sections.find(s => s.lessons.some(l => l.id === selected.id))?.title || '<?php echo esc_js( __( 'Section', 'mint-lms' ) ); ?>'"></span>
							<svg class="mint-h-[15px] mint-w-[15px] mint-shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 5l5 5-5 5"/></svg>
							<span class="mint-text-ink" x-text="selectedLesson.title"></span>
						</nav>

						<div class="mint-mb-6">
							<input
								type="text"
								class="<?php echo esc_attr( $titleInputClass ); ?>"
								x-model="selectedLesson.title"
								@input="debouncedSaveLesson(selectedLesson)"
								placeholder="<?php echo esc_attr__( 'Lesson title', 'mint-lms' ); ?>"
							/>
						</div>

						<!-- Segmented control -->
						<div class="mint-mb-6">
							<div class="mint-inline-flex mint-rounded-lg mint-bg-bg-subtle mint-p-1">
								<button
									type="button"
									class="<?php echo esc_attr( $segmentBtn ); ?>"
									:class="lessonTab === 'written' ? 'mint-bg-bg mint-text-ink mint-shadow-menu' : 'mint-text-ink-2 hover:mint-text-ink'"
									@click="lessonTab = 'written'"
								>
									<svg class="mint-h-4 mint-w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><rect x="3" y="2" width="14" height="16" rx="3"/><line x1="6.5" y1="6.5" x2="13.5" y2="6.5"/><line x1="6.5" y1="10" x2="13.5" y2="10"/><line x1="6.5" y1="13.5" x2="10" y2="13.5"/></svg>
									<?php echo esc_html__( 'Written', 'mint-lms' ); ?>
								</button>
								<button
									type="button"
									class="<?php echo esc_attr( $segmentBtn ); ?>"
									:class="lessonTab === 'video' ? 'mint-bg-bg mint-text-ink mint-shadow-menu' : 'mint-text-ink-2 hover:mint-text-ink'"
									@click="lessonTab = 'video'"
								>
									<svg class="mint-h-4 mint-w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><rect x="2" y="4" width="16" height="12" rx="2.5"/><polygon points="8.5,7.5 13.5,10 8.5,12.5" fill="currentColor" stroke="none"/></svg>
									<?php echo esc_html__( 'Video', 'mint-lms' ); ?>
								</button>
								<button
									type="button"
									class="<?php echo esc_attr( $segmentBtn ); ?>"
									:class="lessonTab === 'quiz' ? 'mint-bg-bg mint-text-ink mint-shadow-menu' : 'mint-text-ink-2 hover:mint-text-ink'"
									@click="lessonTab = 'quiz'"
								>
									<svg class="mint-h-4 mint-w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="M7.5 8.5a2.5 2.5 0 014.5 1.5c0 1.5-2.5 1.5-2.5 3"/><circle cx="10" cy="14.5" r="0.5" fill="currentColor"/></svg>
									<?php echo esc_html__( 'Quiz', 'mint-lms' ); ?>
								</button>
							</div>
						</div>

						<!-- Written -->
						<div x-show="lessonTab === 'written'" class="mint-mb-8 mint-overflow-hidden mint-rounded-xl mint-border mint-border-control">
							<div class="mint-flex mint-items-center mint-gap-2 mint-border-b mint-border-rule mint-bg-bg mint-px-3.5 mint-py-2">
								<span class="mint-text-xs mint-text-ink-3"><?php echo esc_html__( 'Content', 'mint-lms' ); ?></span>
							</div>
							<div class="mint-wp-editor">
								<?php
								wp_editor(
									'',
									'mint_lesson_content',
									array(
										'textarea_rows' => 14,
										'media_buttons' => true,
										'teeny'         => false,
										'quicktags'     => true,
										'editor_height' => 280,
									)
								);
								?>
							</div>
						</div>

						<!-- Video -->
						<div x-show="lessonTab === 'video'" class="mint-mb-8 mint-overflow-hidden mint-rounded-xl mint-border mint-border-control">
							<div class="mint-flex mint-flex-col mint-gap-4 mint-p-5">
								<div>
									<label class="<?php echo esc_attr( $labelClass ); ?>"><?php echo esc_html__( 'Video URL', 'mint-lms' ); ?></label>
									<input
										type="url"
										class="<?php echo esc_attr( $inputClass ); ?>"
										x-model="selectedLesson.videoUrl"
										@input="debouncedSaveLesson(selectedLesson)"
										placeholder="https://youtube.com/…"
									/>
								</div>
								<div>
									<label class="<?php echo esc_attr( $labelClass ); ?>"><?php echo esc_html__( 'Description', 'mint-lms' ); ?></label>
									<textarea
										class="<?php echo esc_attr( $textareaClass ); ?>"
										x-model="selectedLesson.content"
										@input="debouncedSaveLesson(selectedLesson)"
										placeholder="<?php echo esc_attr__( 'Optional description…', 'mint-lms' ); ?>"
									></textarea>
								</div>
							</div>
						</div>

						<!-- Quiz -->
						<div x-show="lessonTab === 'quiz'" class="mint-mb-8 mint-overflow-hidden mint-rounded-xl mint-border mint-border-control">
							<div class="mint-flex mint-flex-col mint-gap-4 mint-p-5">
								<div x-show="quizLoading" class="mint-text-sm mint-text-ink-3"><?php echo esc_html__( 'Loading quiz…', 'mint-lms' ); ?></div>

								<template x-if="!quizLoading && lessonQuiz">
									<div class="mint-flex mint-flex-col mint-gap-4">
										<div class="mint-flex mint-flex-wrap mint-gap-4">
											<div class="mint-min-w-[200px] mint-flex-1">
												<label class="<?php echo esc_attr( $labelClass ); ?>"><?php echo esc_html__( 'Quiz title', 'mint-lms' ); ?></label>
												<input type="text" class="<?php echo esc_attr( $inputClass ); ?>" x-model="lessonQuiz.title" />
											</div>
											<div class="mint-w-[120px]">
												<label class="<?php echo esc_attr( $labelClass ); ?>"><?php echo esc_html__( 'Pass %', 'mint-lms' ); ?></label>
												<input type="number" class="<?php echo esc_attr( $inputClass ); ?>" min="0" max="100" x-model.number="lessonQuiz.passPercent" />
											</div>
										</div>

										<div class="mint-flex mint-flex-wrap mint-gap-2">
											<button type="button" class="<?php echo esc_attr( $btnSecondary ); ?>" @click="addQuizQuestion('mcq')"><?php echo esc_html__( 'Add MCQ', 'mint-lms' ); ?></button>
											<button type="button" class="<?php echo esc_attr( $btnSecondary ); ?>" @click="addQuizQuestion('true_false')"><?php echo esc_html__( 'Add True/False', 'mint-lms' ); ?></button>
											<button type="button" class="<?php echo esc_attr( $btnPrimary ); ?>" @click="saveQuiz()" :disabled="quizSaving"><?php echo esc_html__( 'Save quiz', 'mint-lms' ); ?></button>
											<button type="button" class="<?php echo esc_attr( $btnDangerOutline ); ?>" x-show="lessonQuiz.id" @click="deleteQuiz()"><?php echo esc_html__( 'Delete quiz', 'mint-lms' ); ?></button>
										</div>

										<template x-for="(question, qIndex) in lessonQuiz.questions" :key="question.id || question._key">
											<div class="mint-rounded-lg mint-border mint-border-rule mint-p-4">
												<div class="mint-mb-3 mint-flex mint-items-center mint-justify-between">
													<span class="mint-text-over mint-font-semibold mint-uppercase mint-text-ink-3" x-text="'<?php echo esc_js( __( 'Question', 'mint-lms' ) ); ?> ' + (qIndex + 1)"></span>
													<button type="button" class="<?php echo esc_attr( $btnGhost ); ?> !mint-h-auto !mint-px-2 !mint-py-1" @click="deleteQuizQuestion(question)"><?php echo esc_html__( 'Remove', 'mint-lms' ); ?></button>
												</div>
												<textarea class="<?php echo esc_attr( $textareaClass ); ?> mint-mb-3 mint-min-h-[80px]" x-model="question.prompt" placeholder="<?php echo esc_attr__( 'Question prompt…', 'mint-lms' ); ?>"></textarea>

												<template x-if="question.type === 'true_false'">
													<div class="mint-mb-3 mint-flex mint-gap-4">
														<label class="mint-flex mint-cursor-pointer mint-items-center mint-gap-1.5 mint-text-sm mint-text-ink">
															<input type="radio" :name="'tf-' + (question.id || question._key)" value="true" x-model="question.correctAnswer" />
															<?php echo esc_html__( 'True is correct', 'mint-lms' ); ?>
														</label>
														<label class="mint-flex mint-cursor-pointer mint-items-center mint-gap-1.5 mint-text-sm mint-text-ink">
															<input type="radio" :name="'tf-' + (question.id || question._key)" value="false" x-model="question.correctAnswer" />
															<?php echo esc_html__( 'False is correct', 'mint-lms' ); ?>
														</label>
													</div>
												</template>

												<template x-if="question.type === 'mcq'">
													<div class="mint-mb-3 mint-flex mint-flex-col mint-gap-2">
														<template x-for="(option, oIndex) in question.options" :key="oIndex">
															<div class="mint-flex mint-items-center mint-gap-2">
																<input type="radio" :name="'mcq-' + (question.id || question._key)" :value="option" x-model="question.correctAnswer" />
																<input type="text" class="<?php echo esc_attr( $inputClass ); ?> mint-flex-1" x-model="question.options[oIndex]" :placeholder="'<?php echo esc_js( __( 'Option', 'mint-lms' ) ); ?> ' + (oIndex + 1)" />
																<button type="button" class="<?php echo esc_attr( $btnGhost ); ?> !mint-h-control !mint-w-control !mint-p-0" @click="removeMcqOption(question, oIndex)">×</button>
															</div>
														</template>
														<button type="button" class="<?php echo esc_attr( $btnGhost ); ?>" x-show="question.options.length < 4" @click="addMcqOption(question)"><?php echo esc_html__( 'Add option', 'mint-lms' ); ?></button>
													</div>
												</template>

												<button type="button" class="<?php echo esc_attr( $btnSecondary ); ?>" @click="saveQuizQuestion(question)" :disabled="quizSaving"><?php echo esc_html__( 'Save question', 'mint-lms' ); ?></button>
											</div>
										</template>

										<p x-show="lessonQuiz.questions.length === 0" class="mint-m-0 mint-text-sm mint-text-ink-3"><?php echo esc_html__( 'No questions yet. Add MCQ or True/False questions above.', 'mint-lms' ); ?></p>
									</div>
								</template>
							</div>
						</div>

						<!-- Attachment & preview -->
						<div class="mint-mb-8 mint-flex mint-flex-wrap mint-items-center mint-justify-between mint-gap-4">
							<div class="mint-flex mint-flex-wrap mint-items-center mint-gap-3">
								<button type="button" class="<?php echo esc_attr( $btnSecondary ); ?>" @click="pickAttachment()">
									<?php echo esc_html__( 'Choose file', 'mint-lms' ); ?>
								</button>
								<span
									class="mint-text-xs mint-text-ink-3"
									x-text="selectedLesson.attachmentId ? '<?php echo esc_js( __( 'Attached', 'mint-lms' ) ); ?> (ID: ' + selectedLesson.attachmentId + ')' : '<?php echo esc_js( __( 'No attachment', 'mint-lms' ) ); ?>'"
								></span>
								<button
									type="button"
									x-show="selectedLesson.attachmentId"
									class="<?php echo esc_attr( $btnGhost ); ?> !mint-h-auto !mint-px-0 !mint-underline"
									@click="clearAttachment()"
								><?php echo esc_html__( 'Remove', 'mint-lms' ); ?></button>
							</div>
							<div class="mint-flex mint-items-center mint-gap-2.5">
								<span class="mint-text-sm mint-font-medium mint-text-ink-2"><?php echo esc_html__( 'Free preview', 'mint-lms' ); ?></span>
								<button
									type="button"
									role="switch"
									:aria-checked="selectedLesson.isPreview ? 'true' : 'false'"
									@click="togglePreview()"
									class="mint-relative mint-inline-flex mint-h-6 mint-w-11 mint-shrink-0 mint-cursor-pointer mint-rounded-full mint-border-2 mint-border-transparent mint-transition-colors focus:mint-outline-none focus:mint-ring-2 focus:mint-ring-accent focus:mint-ring-offset-2"
									:class="selectedLesson.isPreview ? 'mint-bg-accent' : 'mint-bg-neutral-300'"
								>
									<span
										class="mint-pointer-events-none mint-inline-block mint-h-5 mint-w-5 mint-transform mint-rounded-full mint-bg-neutral-50 mint-shadow-sm mint-transition-transform"
										:class="selectedLesson.isPreview ? 'mint-translate-x-5' : 'mint-translate-x-0'"
									></span>
								</button>
							</div>
						</div>

						<div class="mint-mb-8">
							<label class="<?php echo esc_attr( $labelClass ); ?>"><?php echo esc_html__( 'Available days after enrollment', 'mint-lms' ); ?></label>
							<input
								type="number"
								class="<?php echo esc_attr( $inputClass ); ?>"
								min="0"
								step="1"
								x-model.number="selectedLesson.availableAfterDays"
								@input="debouncedSaveLesson(selectedLesson)"
								placeholder="<?php echo esc_attr__( 'Leave empty for immediate access', 'mint-lms' ); ?>"
							/>
							<p class="mint-m-0 mint-mt-1.5 mint-text-xs mint-text-ink-3"><?php echo esc_html__( 'Students can access this lesson after this many days from enrollment.', 'mint-lms' ); ?></p>
						</div>

					</div>
				</div>

				<!-- Footer -->
				<div class="mint-flex mint-shrink-0 mint-items-center mint-justify-between mint-border-t mint-border-rule mint-bg-bg mint-px-6 mint-py-3.5">
					<button
						type="button"
						class="<?php echo esc_attr( $btnDangerOutline ); ?> mint-h-control-md"
						@click="deleteLesson(selectedLesson.id, selected.sectionId)"
					>
						<?php echo esc_html__( 'Delete lesson', 'mint-lms' ); ?>
					</button>
					<div class="mint-flex mint-items-center mint-gap-2.5">
						<button
							type="button"
							class="<?php echo esc_attr( $btnSecondary ); ?> mint-h-control-md"
							@click="saveLesson(selectedLesson)"
						>
							<?php echo esc_html__( 'Save draft', 'mint-lms' ); ?>
						</button>
						<button
							type="button"
							class="<?php echo esc_attr( $btnDark ); ?> mint-h-control-md"
							@click="saveAndNext()"
						>
							<?php echo esc_html__( 'Save and next', 'mint-lms' ); ?>
						</button>
					</div>
				</div>
			</div>

		</section>
	</div>

	<?php echo ( new MintLMS\Infrastructure\Admin\ViewRenderer() )->component( 'toast' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
