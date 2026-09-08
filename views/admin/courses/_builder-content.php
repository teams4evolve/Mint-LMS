<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

/** @var MintLMS\Infrastructure\Admin\ViewRenderer $renderer */
/** @var int $courseId */

$coursesUrl = admin_url( 'admin.php?page=mint-lms-courses' );
$lessonsUrl = admin_url( 'admin.php?page=mint-lms-lessons' );
$quizzesUrl = admin_url( 'admin.php?page=mint-lms-quizzes' );

$inputClass    = 'mint-settings-input mint-block mint-w-full mint-rounded-md mint-border-[1.5px] mint-border-[#B9B6CE] mint-bg-white mint-px-[14px] mint-text-[17px] mint-leading-7 mint-text-ink focus:mint-outline-none';
$textareaClass = $inputClass . ' mint-min-h-[110px] mint-resize-y mint-py-[13px]';
$labelClass    = 'mint-mb-1.5 mint-block mint-text-[13px] mint-font-bold mint-uppercase mint-tracking-[0.08em] mint-text-ink-2';
?>
<div
	id="mint-course-builder"
	class="mint-builder-shell"
	x-data="courseBuilder(<?php echo esc_attr( (string) $courseId ); ?>)"
	x-init="init()"
>
	<header class="mint-builder-toolbar">
		<div class="mint-flex mint-min-w-0 mint-items-center mint-gap-[14px]">
			<template x-if="fromQuizzes">
				<a
					href="<?php echo esc_url( $quizzesUrl ); ?>"
					class="mint-builder-back-lessons mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2 mint-rounded-lg mint-px-2 mint-py-1.5 mint-no-underline mint-text-ink hover:mint-bg-[#F4F3F8]"
					aria-label="<?php echo esc_attr__( 'Back to quizzes', 'mint-lms' ); ?>"
				>
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mint-shrink-0"><path d="M12.5 4.5 7 10l5.5 5.5"/></svg>
					<span class="mint-truncate mint-text-base mint-font-semibold mint-tracking-[-0.015em]"><?php esc_html_e( 'Back to quizzes', 'mint-lms' ); ?></span>
				</a>
			</template>
			<template x-if="!fromQuizzes && (fromLessons || selected?.type === 'lesson' || selected?.type === 'quiz')">
				<a
					href="<?php echo esc_url( $lessonsUrl ); ?>"
					class="mint-builder-back-lessons mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2 mint-rounded-lg mint-px-2 mint-py-1.5 mint-no-underline mint-text-ink hover:mint-bg-[#F4F3F8]"
					aria-label="<?php echo esc_attr__( 'Back to lessons', 'mint-lms' ); ?>"
				>
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mint-shrink-0"><path d="M12.5 4.5 7 10l5.5 5.5"/></svg>
					<span class="mint-truncate mint-text-base mint-font-semibold mint-tracking-[-0.015em]"><?php esc_html_e( 'Back to lessons', 'mint-lms' ); ?></span>
				</a>
			</template>
			<template x-if="!fromQuizzes && !fromLessons && selected?.type !== 'lesson' && selected?.type !== 'quiz'">
				<div class="mint-flex mint-min-w-0 mint-items-center mint-gap-[14px]">
					<a
						href="<?php echo esc_url( $coursesUrl ); ?>"
						class="mint-builder-back"
						aria-label="<?php echo esc_attr__( 'Back to courses', 'mint-lms' ); ?>"
					>
						<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.5 4.5 7 10l5.5 5.5"/></svg>
					</a>
					<div class="mint-flex mint-min-w-0 mint-items-center mint-gap-2.5">
						<div
							class="mint-truncate mint-text-base mint-font-semibold mint-tracking-[-0.015em] mint-text-ink"
							x-text="course.title || '<?php echo esc_js( __( 'Untitled course', 'mint-lms' ) ); ?>'"
						></div>
						<span
							class="mint-status-chip"
							x-show="course.status"
							x-text="statusLabel(course.status)"
						></span>
					</div>
				</div>
			</template>
		</div>

		<div class="mint-flex mint-shrink-0 mint-items-center mint-gap-2.5">
			<div class="mint-mr-1 mint-text-[15px] mint-text-ink-2" x-text="saveStateLabel()" x-show="saveStateLabel()" x-cloak></div>
			<a
				:href="previewUrl"
				target="_blank"
				rel="noopener noreferrer"
				class="mint-builder-btn-preview"
			><?php esc_html_e( 'Preview', 'mint-lms' ); ?></a>
			<button
				type="button"
				class="mint-builder-btn-publish"
				@click="headerPrimaryAction()"
				:disabled="headerPrimaryDisabled()"
			>
				<template x-if="selected?.type === 'lesson'">
					<span>
						<span x-show="saveStatus !== 'saving'"><?php esc_html_e( 'Publish', 'mint-lms' ); ?></span>
						<span x-show="saveStatus === 'saving'" x-cloak><?php esc_html_e( 'Saving…', 'mint-lms' ); ?></span>
					</span>
				</template>
				<template x-if="selected?.type === 'quiz'">
					<span>
						<span x-show="!quizSaving"><?php esc_html_e( 'Save changes', 'mint-lms' ); ?></span>
						<span x-show="quizSaving" x-cloak><?php esc_html_e( 'Saving…', 'mint-lms' ); ?></span>
					</span>
				</template>
				<template x-if="selected?.type !== 'lesson' && selected?.type !== 'quiz'">
					<span>
						<span x-show="!publishing"><?php esc_html_e( 'Publish', 'mint-lms' ); ?></span>
						<span x-show="publishing" x-cloak><?php esc_html_e( 'Publishing…', 'mint-lms' ); ?></span>
					</span>
				</template>
			</button>
		</div>
	</header>

	<div x-show="loading" x-cloak class="mint-flex mint-flex-1 mint-items-center mint-justify-center">
		<?php
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'skeleton',
			array(
				'lines' => 6,
				'attrs' => 'class="mint-w-full mint-max-w-md mint-px-6"',
			)
		);
		?>
	</div>

	<div x-show="error && !loading" x-cloak class="mint-flex mint-flex-1 mint-items-center mint-justify-center mint-p-8">
		<div class="mint-text-center">
			<p class="mint-m-0 mint-mb-2 mint-text-h3 mint-font-semibold mint-text-ink"><?php esc_html_e( 'Could not load course', 'mint-lms' ); ?></p>
			<p class="mint-m-0 mint-mb-4 mint-text-sm mint-text-ink-2" x-text="error"></p>
			<button type="button" class="mint-builder-btn-secondary" @click="loadStructure()"><?php esc_html_e( 'Try again', 'mint-lms' ); ?></button>
		</div>
	</div>

	<div x-show="!loading && !error" x-cloak class="mint-builder-body">
		<aside class="mint-builder-tree">
			<template x-if="fromQuizzes">
				<div class="mint-flex mint-h-full mint-min-h-0 mint-flex-col">
					<div class="mint-builder-tree__search">
						<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="#5C5C77" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8.5" cy="8.5" r="5.5"/><path d="M16 16l-3.5-3.5"/></svg>
						<input
							type="search"
							class="mint-builder-tree__search-input"
							placeholder="<?php echo esc_attr__( 'Search Quiz', 'mint-lms' ); ?>"
							x-model="quizSearch"
							aria-label="<?php echo esc_attr__( 'Search Quiz', 'mint-lms' ); ?>"
						/>
					</div>

					<div class="mint-builder-tree__scroll mint-flex-1">
						<template x-if="filteredCourseQuizzes.length === 0">
							<div class="mint-builder-tree__empty">
								<p><?php esc_html_e( 'No quizzes yet', 'mint-lms' ); ?></p>
								<button type="button" class="mint-builder-empty__cta" :disabled="addingCourseQuiz" @click="addCourseQuiz()"><?php esc_html_e( 'Add first quiz', 'mint-lms' ); ?></button>
							</div>
						</template>

						<template x-for="quiz in filteredCourseQuizzes" :key="quiz.id">
							<div class="mint-mb-1">
								<div
									class="mint-tree-lesson"
									:class="isCourseQuizSelected(quiz) ? 'is-active' : ''"
									@click="selectQuiz(quiz.lessonId, quiz.sectionId || null)"
								>
									<div class="mint-tree-lesson__icon mint-tree-lesson__icon--quiz">
										<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
											<circle cx="10" cy="10" r="7"/>
											<path d="M8 10.2 9.3 11.5 12.4 8.3"/>
										</svg>
									</div>
									<div class="mint-min-w-0 mint-flex-1">
										<span
											class="mint-tree-lesson__title mint-block mint-truncate mint-text-base mint-font-medium mint-text-ink"
											:class="isCourseQuizSelected(quiz) ? 'mint-font-semibold' : ''"
											x-text="quiz.title || '<?php echo esc_js( __( 'New Quiz', 'mint-lms' ) ); ?>'"
										></span>
									</div>
								</div>

								<button
									type="button"
									class="mint-tree-add-lesson"
									x-show="isCourseQuizSelected(quiz)"
									x-cloak
									@click.stop="addQuizQuestion('mcq')"
								>
									<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
									<?php esc_html_e( 'Add Question', 'mint-lms' ); ?>
								</button>
							</div>
						</template>
					</div>

					<div class="mint-builder-tree__footer">
						<button
							type="button"
							class="mint-tree-add-section mint-w-full"
							:disabled="addingCourseQuiz"
							@click="addCourseQuiz()"
						>
							<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
							<?php esc_html_e( 'Add Quiz', 'mint-lms' ); ?>
						</button>
					</div>
				</div>
			</template>

			<template x-if="!fromQuizzes">
				<div class="mint-flex mint-h-full mint-min-h-0 mint-flex-col">
			<div class="mint-builder-tree__head">
				<span><?php esc_html_e( 'Contents', 'mint-lms' ); ?></span>
				<span class="mint-builder-tree__head-count" x-text="totalLessonCount() + ' <?php echo esc_js( __( 'lessons', 'mint-lms' ) ); ?>'"></span>
			</div>

			<div class="mint-builder-tree__scroll" id="mint-sections-sortable">
						<template x-if="sections.length === 0">
					<div class="mint-builder-tree__empty">
						<p><?php esc_html_e( 'No sections yet', 'mint-lms' ); ?></p>
						<button type="button" class="mint-builder-empty__cta" :disabled="addingLesson" @click="createSectionWithLesson()"><?php esc_html_e( 'Add a lesson', 'mint-lms' ); ?></button>
						<button type="button" class="mint-tree-add-section mint-mt-3 mint-w-full" :disabled="addingSection" @click="addSection()"><?php esc_html_e( 'Add section', 'mint-lms' ); ?></button>
					</div>
				</template>

				<template x-for="(section, sIndex) in sections" :key="section.id">
					<div :data-section-id="section.id" class="mint-mb-1.5">
						<div
							class="mint-tree-section"
							@click="selectItem('section', section.id)"
						>
							<span class="mint-handle-section" title="<?php echo esc_attr__( 'Drag to reorder', 'mint-lms' ); ?>">
								<svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
							</span>
							<button
								type="button"
								class="mint-builder-tree__toggle"
								@click.stop="toggleSection(section.id)"
								:aria-expanded="isExpanded(section.id)"
							>
								<svg
									width="18"
									height="18"
									viewBox="0 0 20 20"
									fill="none"
									stroke="#33334A"
									stroke-width="2.1"
									stroke-linecap="round"
									stroke-linejoin="round"
									class="mint-transition-transform"
									:class="isExpanded(section.id) ? '' : 'mint--rotate-90'"
									aria-hidden="true"
								><path d="M5.5 8 10 12.5 14.5 8"/></svg>
							</button>
							<div class="mint-min-w-0 mint-flex-1" @click.stop>
								<template x-if="editingKey !== 'section-' + section.id">
									<span
										class="mint-builder-tree__label mint-builder-tree__label--section"
										@dblclick="startEdit('section', section.id, section.title)"
										x-text="section.title"
									></span>
								</template>
								<template x-if="editingKey === 'section-' + section.id">
									<input
										type="text"
										class="mint-builder-tree__edit-input"
										x-model="editingValue"
										@keydown.enter="commitEdit('section', section.id)"
										@keydown.escape="cancelEdit()"
										@blur="commitEdit('section', section.id)"
										x-ref="editInput"
									/>
								</template>
							</div>
							<span class="mint-builder-tree__count" x-text="section.lessons.length"></span>
						</div>

						<div
							x-show="isExpanded(section.id)"
							class="mint-lessons-sortable"
							:data-section-id="section.id"
						>
							<template x-for="(lesson, lIndex) in section.lessons" :key="lesson.id">
								<div
									:data-lesson-id="lesson.id"
									class="mint-tree-lesson"
									:class="(isSelected('lesson', lesson.id) || isQuizLessonSelected(lesson.id)) ? 'is-active' : ''"
									@click="selectItem('lesson', lesson.id, section.id)"
								>
									<span class="mint-handle-lesson" title="<?php echo esc_attr__( 'Drag to reorder', 'mint-lms' ); ?>">
										<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
									</span>
									<div
										class="mint-tree-lesson__icon"
										:class="lesson.videoUrl ? 'mint-tree-lesson__icon--video' : ''"
									>
										<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
											<rect x="3" y="3.5" width="14" height="13" rx="3"/>
											<path x-show="!lesson.videoUrl" d="M6.5 8h7M6.5 11.5h4"/>
											<path x-show="lesson.videoUrl" d="m8.5 7.5 4 2.5-4 2.5z"/>
										</svg>
									</div>
									<div class="mint-min-w-0 mint-flex-1" @click.stop>
										<template x-if="editingKey !== 'lesson-' + lesson.id">
											<span
												class="mint-tree-lesson__title mint-block mint-truncate mint-text-base mint-font-medium mint-text-ink"
												:class="(isSelected('lesson', lesson.id) || isQuizLessonSelected(lesson.id)) ? 'mint-font-semibold' : ''"
												@dblclick="startEdit('lesson', lesson.id, lesson.title)"
												x-text="lesson.title"
											></span>
										</template>
										<template x-if="editingKey === 'lesson-' + lesson.id">
											<input
												type="text"
												class="mint-builder-tree__edit-input"
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
								class="mint-tree-add-lesson"
								:disabled="addingLesson && !quizEditorActive"
								@click="onTreeAddLesson(section.id)"
							>
								<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
								<span x-text="treeAddLessonLabel()"></span>
							</button>
						</div>
					</div>
				</template>
			</div>

			<div class="mint-builder-tree__footer">
				<button
					type="button"
					class="mint-tree-add-section mint-w-full"
					:disabled="addingSection && !quizEditorActive"
					@click="onTreeAddSection()"
				>
					<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
					<span x-text="treeAddSectionLabel()"></span>
				</button>
			</div>
				</div>
			</template>
		</aside>

		<section class="mint-builder-editor mint-flex mint-flex-col">
			<template x-if="!selected">
				<div class="mint-builder-empty">
					<div class="mint-max-w-[380px] mint-text-center">
						<div class="mint-builder-empty__icon">
							<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 6.5h15M4.5 12h15M4.5 17.5h9"/></svg>
						</div>
						<div class="mint-builder-empty__title"><?php esc_html_e( 'Pick a lesson to edit', 'mint-lms' ); ?></div>
						<div class="mint-builder-empty__copy"><?php esc_html_e( 'Choose one from the left, or add your first lesson to get going.', 'mint-lms' ); ?></div>
						<button
							type="button"
							class="mint-builder-empty__cta"
							:disabled="addingLesson"
							@click="createSectionWithLesson()"
						><?php esc_html_e( 'Add a lesson', 'mint-lms' ); ?></button>
					</div>
				</div>
			</template>

			<template x-if="selected && selected.type === 'section' && selectedSection">
				<div class="mint-mx-auto mint-flex mint-h-full mint-w-full mint-max-w-prose mint-flex-col mint-px-10 mint-pb-24 mint-pt-12">
					<div class="mint-flex mint-items-center mint-gap-2 mint-text-[13px] mint-font-bold mint-uppercase mint-tracking-[0.06em] mint-text-[#0B4F3F]">
						<div class="mint-flex mint-h-[22px] mint-w-[22px] mint-items-center mint-justify-center mint-rounded-md mint-bg-[#E8FFF3]">
							<svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
						</div>
						<?php esc_html_e( 'Section', 'mint-lms' ); ?>
					</div>

					<input
						type="text"
						class="mint-section-title-input"
						x-model="selectedSection.title"
						@input="debouncedSaveSection(selectedSection)"
						placeholder="<?php echo esc_attr__( 'New Section', 'mint-lms' ); ?>"
						aria-label="<?php echo esc_attr__( 'Section title', 'mint-lms' ); ?>"
					/>
					<div class="mint-mt-2 mint-text-[15px] mint-text-ink-2"><?php esc_html_e( 'Click the title to rename it.', 'mint-lms' ); ?></div>

					<div class="mint-mt-8 mint-rounded-[14px] mint-border mint-border-[#E4E1F0] mint-bg-[#FAF9FE] mint-px-5 mint-py-[18px]">
						<div class="mint-text-[15px] mint-font-semibold mint-text-ink" x-text="sectionLessonSummary()"></div>
						<div class="mint-mt-1 mint-text-[15px] mint-text-ink-3"><?php esc_html_e( 'Drag lessons in the sidebar to reorder them, or add another one below.', 'mint-lms' ); ?></div>
					</div>

					<div class="mint-mt-8 mint-flex mint-flex-wrap mint-items-center mint-justify-between mint-gap-4 mint-border-t mint-border-rule mint-pt-5">
						<button
							type="button"
							class="mint-builder-delete"
							@click="deleteSection(selected.id)"
						><?php esc_html_e( 'Delete section', 'mint-lms' ); ?></button>
						<button
							type="button"
							class="mint-builder-btn-publish !mint-h-[38px] !mint-rounded-[9px] !mint-px-4 !mint-text-[15px]"
							@click="saveSection(selectedSection, { toast: true })"
						><?php esc_html_e( 'Save changes', 'mint-lms' ); ?></button>
					</div>
				</div>
			</template>

			<div
				x-show="selected && selected.type === 'quiz'"
				x-cloak
				class="mint-flex mint-h-full mint-flex-col"
			>
				<div class="mint-min-h-0 mint-flex-1 mint-overflow-y-auto">
					<div class="mint-mx-auto mint-w-full mint-max-w-prose mint-px-10 mint-pb-24 mint-pt-12">
						<div x-show="quizLoading" class="mint-text-[15px] mint-text-ink-3"><?php esc_html_e( 'Loading quiz…', 'mint-lms' ); ?></div>

						<template x-if="!quizLoading && lessonQuiz">
							<div>
								<nav class="mint-flex mint-items-center mint-gap-2.5 mint-text-[15px] mint-text-ink-2">
									<span x-text="breadcrumbSectionTitle()"></span>
									<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
									<span x-text="breadcrumbQuizLabel()"></span>
								</nav>

								<input
									type="text"
									class="mint-lesson-title-input mint-mt-3.5"
									x-model="lessonQuiz.title"
									@input="syncCourseQuizTitle()"
									placeholder="<?php echo esc_attr__( 'New Quiz', 'mint-lms' ); ?>"
								/>
								<div class="mint-mt-2 mint-text-[15px] mint-text-ink-2"><?php esc_html_e( 'Click the title to rename it.', 'mint-lms' ); ?></div>

								<div class="mint-quiz-type-chip mint-mt-8">
									<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="M8 10.2 9.3 11.5 12.4 8.3"/></svg>
									<?php esc_html_e( 'Quiz', 'mint-lms' ); ?>
								</div>

								<div class="mint-quiz-panel mint-mt-5">
									<div class="mint-quiz-panel__pass-field">
										<label class="mint-lesson-field-label" for="mint-quiz-editor-pass"><?php esc_html_e( 'Pass %', 'mint-lms' ); ?></label>
										<input
											id="mint-quiz-editor-pass"
											type="number"
											class="mint-lesson-field-input mint-lesson-field-input--sm"
											min="0"
											max="100"
											x-model.number="lessonQuiz.passPercent"
										/>
									</div>

									<div x-show="lessonQuiz.questions.length === 0" class="mint-quiz-empty"><?php esc_html_e( 'No questions yet.', 'mint-lms' ); ?></div>

									<template x-for="(question, qIndex) in lessonQuiz.questions" :key="question.id || question._key">
										<div class="mint-quiz-question">
											<div class="mint-quiz-question__head">
												<div class="mint-quiz-question__label" x-text="'<?php echo esc_js( __( 'Question', 'mint-lms' ) ); ?> ' + (qIndex + 1) + ' · ' + (question.type === 'mcq' ? '<?php echo esc_js( __( 'Multiple choice', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'True / False', 'mint-lms' ) ); ?>')"></div>
												<button type="button" class="mint-builder-delete" @click="deleteQuizQuestion(question)"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
											</div>

											<textarea
												class="mint-lesson-field-textarea mint-lesson-field-textarea--question"
												x-model="question.prompt"
												placeholder="<?php echo esc_attr__( 'Question prompt…', 'mint-lms' ); ?>"
											></textarea>

											<template x-if="question.type === 'mcq'">
												<div>
													<div class="mint-quiz-options">
														<template x-for="(option, oIndex) in question.options" :key="oIndex">
															<div class="mint-quiz-option-row">
																<button
																	type="button"
																	class="mint-quiz-option-mark"
																	:class="question.correctAnswer === question.options[oIndex] && question.options[oIndex] !== '' ? 'is-selected' : ''"
																	:aria-pressed="question.correctAnswer === question.options[oIndex] && question.options[oIndex] !== '' ? 'true' : 'false'"
																	:title="'<?php echo esc_js( __( 'Mark as correct', 'mint-lms' ) ); ?>'"
																	@click="question.correctAnswer = question.options[oIndex]"
																></button>
																<input
																	type="text"
																	class="mint-lesson-field-input mint-lesson-field-input--option"
																	x-model="question.options[oIndex]"
																	:placeholder="'<?php echo esc_js( __( 'Option', 'mint-lms' ) ); ?> ' + (oIndex + 1)"
																/>
																<button type="button" class="mint-quiz-option-remove" @click="removeMcqOption(question, oIndex)" aria-label="<?php echo esc_attr__( 'Remove option', 'mint-lms' ); ?>">×</button>
															</div>
														</template>
													</div>
													<button
														type="button"
														class="mint-quiz-add-option"
														x-show="question.options.length < 4"
														@click="addMcqOption(question)"
													><?php esc_html_e( '+ Add option', 'mint-lms' ); ?></button>
												</div>
											</template>

											<template x-if="question.type === 'true_false'">
												<div class="mint-quiz-tf">
													<button
														type="button"
														class="mint-quiz-tf__pill"
														:class="question.correctAnswer === 'true' ? 'is-selected' : ''"
														@click="question.correctAnswer = 'true'"
													>
														<span class="mint-quiz-tf__dot"></span>
														<?php esc_html_e( 'True is correct', 'mint-lms' ); ?>
													</button>
													<button
														type="button"
														class="mint-quiz-tf__pill"
														:class="question.correctAnswer === 'false' ? 'is-selected' : ''"
														@click="question.correctAnswer = 'false'"
													>
														<span class="mint-quiz-tf__dot"></span>
														<?php esc_html_e( 'False is correct', 'mint-lms' ); ?>
													</button>
												</div>
											</template>

											<button type="button" class="mint-quiz-save-question" @click="saveQuizQuestion(question)" :disabled="quizSaving"><?php esc_html_e( 'Save question', 'mint-lms' ); ?></button>
										</div>
									</template>
								</div>

								<div class="mint-quiz-rules mint-mt-7">
									<div class="mint-text-[17px] mint-font-bold mint-tracking-[-0.01em] mint-text-ink"><?php esc_html_e( 'Rules / progression', 'mint-lms' ); ?></div>
									<div class="mint-mt-[3px] mint-text-sm mint-text-ink-3"><?php esc_html_e( 'Controls how students take and complete this quiz.', 'mint-lms' ); ?></div>

									<div class="mint-mt-[22px] mint-flex mint-items-start mint-justify-between mint-gap-4">
										<div class="mint-text-[15px] mint-font-semibold mint-text-ink"><?php esc_html_e( 'Restrict Quiz Retakes', 'mint-lms' ); ?></div>
										<button
											type="button"
											role="switch"
											class="mint-lesson-preview-toggle"
											:class="lessonQuiz.settings.restrictRetakes ? 'is-on' : ''"
											:aria-pressed="lessonQuiz.settings.restrictRetakes ? 'true' : 'false'"
											@click="lessonQuiz.settings.restrictRetakes = !lessonQuiz.settings.restrictRetakes"
										><span class="mint-lesson-preview-toggle__knob"></span></button>
									</div>

									<div x-show="lessonQuiz.settings.restrictRetakes" x-cloak class="mint-quiz-rules__nested mint-mt-4">
										<div>
											<label class="mint-lesson-field-label" for="mint-quiz-retries"><?php esc_html_e( 'Number of Retries Allowed', 'mint-lms' ); ?></label>
											<input
												id="mint-quiz-retries"
												type="number"
												min="0"
												class="mint-lesson-field-input mint-lesson-field-input--sm mint-max-w-[160px]"
												x-model.number="lessonQuiz.settings.retriesAllowed"
											/>
										</div>
										<div class="mint-mt-4">
											<label class="mint-lesson-field-label" for="mint-quiz-retries-to"><?php esc_html_e( 'Retries Applicable To', 'mint-lms' ); ?></label>
											<select
												id="mint-quiz-retries-to"
												class="mint-lesson-field-input mint-lesson-field-input--sm mint-max-w-[320px]"
												x-model="lessonQuiz.settings.retriesApplicableTo"
											>
												<option value="all"><?php esc_html_e( 'All users', 'mint-lms' ); ?></option>
											</select>
										</div>
										<button type="button" class="mint-quiz-save mint-mt-4" @click="saveQuiz()" :disabled="quizSaving"><?php esc_html_e( 'Save quiz', 'mint-lms' ); ?></button>
									</div>

									<div class="mint-quiz-rules__divider"></div>

									<button
										type="button"
										class="mint-quiz-checkrow"
										@click="lessonQuiz.settings.questionCompletion = !lessonQuiz.settings.questionCompletion"
									>
										<span
											class="mint-quiz-checkbox"
											:class="lessonQuiz.settings.questionCompletion ? 'is-checked' : ''"
											aria-hidden="true"
										>
											<svg x-show="lessonQuiz.settings.questionCompletion" width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="#FFFFFF" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 10.5 8.5 14 15 6.5"/></svg>
										</span>
										<span class="mint-text-[15px] mint-font-semibold mint-text-ink"><?php esc_html_e( 'Question Completion', 'mint-lms' ); ?></span>
										<span class="mint-text-sm mint-text-ink-3"><?php esc_html_e( '— all questions required to complete', 'mint-lms' ); ?></span>
									</button>

									<div class="mint-quiz-rules__divider"></div>

									<div class="mint-flex mint-items-center mint-justify-between mint-gap-4">
										<div class="mint-text-[15px] mint-font-semibold mint-text-ink"><?php esc_html_e( 'Time Limit', 'mint-lms' ); ?></div>
										<button
											type="button"
											role="switch"
											class="mint-lesson-preview-toggle"
											:class="lessonQuiz.settings.timeLimitEnabled ? 'is-on' : ''"
											:aria-pressed="lessonQuiz.settings.timeLimitEnabled ? 'true' : 'false'"
											@click="lessonQuiz.settings.timeLimitEnabled = !lessonQuiz.settings.timeLimitEnabled"
										><span class="mint-lesson-preview-toggle__knob"></span></button>
									</div>

									<div x-show="lessonQuiz.settings.timeLimitEnabled" x-cloak class="mint-quiz-rules__nested mint-mt-4">
										<div class="mint-lesson-field-label"><?php esc_html_e( 'Automatically Submit After', 'mint-lms' ); ?></div>
										<div class="mint-quiz-time mint-mt-2">
											<input
												type="text"
												class="mint-quiz-time__part"
												maxlength="2"
												x-model="lessonQuiz.settings.timeLimitHours"
												@blur="lessonQuiz.settings.timeLimitHours = clampTimePart(lessonQuiz.settings.timeLimitHours)"
												aria-label="<?php echo esc_attr__( 'Hours', 'mint-lms' ); ?>"
											/>
											<span class="mint-quiz-time__sep">:</span>
											<input
												type="text"
												class="mint-quiz-time__part"
												maxlength="2"
												x-model="lessonQuiz.settings.timeLimitMinutes"
												@blur="lessonQuiz.settings.timeLimitMinutes = clampTimePart(lessonQuiz.settings.timeLimitMinutes)"
												aria-label="<?php echo esc_attr__( 'Minutes', 'mint-lms' ); ?>"
											/>
											<span class="mint-quiz-time__sep">:</span>
											<input
												type="text"
												class="mint-quiz-time__part"
												maxlength="2"
												x-model="lessonQuiz.settings.timeLimitSeconds"
												@blur="lessonQuiz.settings.timeLimitSeconds = clampTimePart(lessonQuiz.settings.timeLimitSeconds)"
												aria-label="<?php echo esc_attr__( 'Seconds', 'mint-lms' ); ?>"
											/>
										</div>
									</div>

									<div class="mint-mt-[22px] mint-flex mint-justify-end">
										<button type="button" class="mint-quiz-save" @click="saveQuiz()" :disabled="quizSaving"><?php esc_html_e( 'Save quiz', 'mint-lms' ); ?></button>
									</div>
								</div>

								<section class="mint-mt-7 mint-grid mint-gap-[14px]">
									<div class="mint-text-[13px] mint-font-bold mint-uppercase mint-tracking-[0.08em] mint-text-ink-2">
										<?php esc_html_e( 'Set featured image · Quiz', 'mint-lms' ); ?>
									</div>

									<div
										x-show="!lessonQuiz.featuredImageUrl"
										class="mint-cover-placeholder mint-flex mint-h-[200px] mint-items-center mint-justify-center mint-rounded-[14px] mint-border-[1.5px] mint-border-dashed mint-border-[#A79FE0]"
									>
										<span class="mint-font-mono mint-text-[13px] mint-text-ink-2"><?php esc_html_e( 'featured image · 1200×675', 'mint-lms' ); ?></span>
									</div>
									<div
										x-show="lessonQuiz.featuredImageUrl"
										x-cloak
										class="mint-h-[200px] mint-overflow-hidden mint-rounded-[14px] mint-border-[1.5px] mint-border-[#DAD7E6]"
									>
										<img :src="lessonQuiz.featuredImageUrl" alt="" class="mint-h-full mint-w-full mint-object-cover" />
									</div>

									<p class="mint-m-0 mint-text-base mint-leading-[26px] mint-text-ink-2">
										<?php esc_html_e( 'Optional. Shown on the quiz card and results screen.', 'mint-lms' ); ?>
									</p>

									<div class="mint-flex mint-flex-wrap mint-gap-2.5">
										<button
											type="button"
											class="mint-settings-action mint-inline-flex mint-h-[42px] mint-cursor-pointer mint-items-center mint-justify-center mint-rounded-md mint-border-0 mint-bg-cta mint-px-4 mint-text-base mint-font-semibold mint-text-cta-ink"
											@click="pickQuizFeaturedImage()"
										><?php esc_html_e( 'Upload image', 'mint-lms' ); ?></button>
										<button
											type="button"
											class="mint-settings-action mint-inline-flex mint-h-[42px] mint-cursor-pointer mint-items-center mint-justify-center mint-rounded-md mint-border-0 mint-bg-cta mint-px-[15px] mint-text-base mint-font-semibold mint-text-cta-ink"
											x-show="lessonQuiz.featuredImageId"
											x-cloak
											@click="clearQuizFeaturedImage()"
										><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
									</div>
								</section>

								<div class="mint-mt-8 mint-flex mint-flex-wrap mint-items-center mint-justify-between mint-gap-4 mint-border-t mint-border-rule mint-pt-5">
									<button type="button" class="mint-builder-delete" @click="deleteQuiz()"><?php esc_html_e( 'Delete quiz', 'mint-lms' ); ?></button>
									<button
										type="button"
										class="mint-builder-btn-publish !mint-h-[38px] !mint-rounded-[9px] !mint-px-4 !mint-text-[15px]"
										@click="saveQuiz()"
										:disabled="quizSaving"
									><?php esc_html_e( 'Save changes', 'mint-lms' ); ?></button>
								</div>
							</div>
						</template>
					</div>
				</div>
			</div>

			<div
				x-show="selected && selected.type === 'lesson'"
				x-cloak
				class="mint-flex mint-h-full mint-flex-col"
			>
				<div class="mint-min-h-0 mint-flex-1 mint-overflow-y-auto">
					<div class="mint-mx-auto mint-w-full mint-max-w-prose mint-px-10 mint-pb-24 mint-pt-12">
						<nav class="mint-flex mint-items-center mint-gap-2.5 mint-text-[15px] mint-text-ink-2">
							<span x-text="breadcrumbSectionTitle()"></span>
							<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
							<span x-text="breadcrumbLessonLabel()"></span>
						</nav>

						<input
							type="text"
							class="mint-lesson-title-input mint-mt-3.5"
							x-model="selectedLesson.title"
							@input="debouncedSaveLesson(selectedLesson)"
							placeholder="<?php echo esc_attr__( 'New Lesson', 'mint-lms' ); ?>"
						/>
						<div class="mint-mt-2 mint-text-[15px] mint-text-ink-2"><?php esc_html_e( 'Click the title to rename it.', 'mint-lms' ); ?></div>

						<div class="mint-segmented mint-mt-8">
							<button type="button" class="mint-segmented__item" :class="lessonTab === 'written' ? 'is-active' : ''" @click="lessonTab = 'written'">
								<svg width="19" height="19" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 5.5h11M4.5 10h11M4.5 14.5h7"/></svg>
								<?php esc_html_e( 'Written', 'mint-lms' ); ?>
							</button>
							<button type="button" class="mint-segmented__item" :class="lessonTab === 'video' ? 'is-active' : ''" @click="lessonTab = 'video'">
								<svg width="19" height="19" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="4" width="15" height="12" rx="3"/><path d="m8.5 7.5 4.5 2.5-4.5 2.5z"/></svg>
								<?php esc_html_e( 'Video', 'mint-lms' ); ?>
							</button>
							<button type="button" class="mint-segmented__item" :class="lessonTab === 'quiz' ? 'is-active' : ''" @click="selectQuiz(selectedLesson.id, selected.sectionId)">
								<svg width="19" height="19" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="M8 10.2 9.3 11.5 12.4 8.3"/></svg>
								<?php esc_html_e( 'Quiz', 'mint-lms' ); ?>
							</button>
						</div>

						<div x-show="lessonTab === 'written'" x-cloak>
							<div class="mint-mt-[22px] mint-flex mint-flex-wrap mint-items-center mint-justify-between mint-gap-3">
								<button type="button" class="mint-lesson-add-media" @click="addMediaToEditor()">
									<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="3.5" width="15" height="13" rx="3"/><circle cx="7" cy="8" r="1.4"/><path d="m4 14 4-3.5 3 2 4.5-4"/></svg>
									<?php esc_html_e( 'Add media', 'mint-lms' ); ?>
								</button>
								<div class="mint-lesson-view-toggle">
									<button type="button" class="mint-lesson-view-toggle__item" :class="editorView === 'visual' ? 'is-active' : ''" @click="setEditorView('visual')"><?php esc_html_e( 'Visual', 'mint-lms' ); ?></button>
									<button type="button" class="mint-lesson-view-toggle__item" :class="editorView === 'code' ? 'is-active' : ''" @click="setEditorView('code')"><?php esc_html_e( 'Code', 'mint-lms' ); ?></button>
								</div>
							</div>

							<div class="mint-editor-frame mint-mt-3" :class="editorView === 'code' ? 'is-code' : 'is-visual'">
								<div
									x-show="editorView === 'visual'"
									x-cloak
									class="mint-lesson-editor-toolbar mint-lesson-editor-toolbar--visual"
									@click.outside="formatMenuOpen = false"
								>
									<div class="mint-lesson-format">
										<button
											type="button"
											class="mint-lesson-format__trigger"
											@click="formatMenuOpen = !formatMenuOpen"
											:aria-expanded="formatMenuOpen ? 'true' : 'false'"
										>
											<span x-text="formatLabel"></span>
											<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
										</button>
										<div class="mint-lesson-format__menu" x-show="formatMenuOpen" x-cloak>
											<template x-for="option in formatOptions" :key="option.value">
												<button
													type="button"
													class="mint-lesson-format__option"
													:style="`font-size: ${option.size}; font-weight: ${option.weight}`"
													@click="applyFormat(option)"
													x-text="option.label"
												></button>
											</template>
										</div>
									</div>
									<div class="mint-lesson-editor-toolbar__divider" aria-hidden="true"></div>
									<button type="button" class="mint-lesson-editor-tool" style="font-weight: 700" @click="runEditorCommand('bold')" title="<?php echo esc_attr__( 'Bold', 'mint-lms' ); ?>">B</button>
									<button type="button" class="mint-lesson-editor-tool" style="font-weight: 500; font-style: italic" @click="runEditorCommand('italic')" title="<?php echo esc_attr__( 'Italic', 'mint-lms' ); ?>">I</button>
									<button type="button" class="mint-lesson-editor-tool" style="font-weight: 600" @click="applyFormat({ label: 'Heading 2', value: 'h2' })" title="<?php echo esc_attr__( 'Heading 2', 'mint-lms' ); ?>">H2</button>
									<button type="button" class="mint-lesson-editor-tool" style="font-weight: 600" @click="runEditorCommand('InsertUnorderedList')" title="<?php echo esc_attr__( 'Bullet list', 'mint-lms' ); ?>">•</button>
									<button type="button" class="mint-lesson-editor-tool" style="font-weight: 500" @click="runEditorCommand('InsertOrderedList')" title="<?php echo esc_attr__( 'Numbered list', 'mint-lms' ); ?>">1.</button>
									<button type="button" class="mint-lesson-editor-tool" @click="insertEditorLink()" title="<?php echo esc_attr__( 'Link', 'mint-lms' ); ?>">🔗</button>
								</div>

								<div
									x-show="editorView === 'code'"
									x-cloak
									class="mint-lesson-editor-toolbar mint-lesson-editor-toolbar--code"
								>
									<template x-for="tag in codeTags" :key="tag">
										<button
											type="button"
											class="mint-lesson-code-chip"
											@click="insertCodeTag(tag)"
											x-text="tag"
										></button>
									</template>
								</div>

								<div class="mint-wp-editor">
									<?php
									wp_editor(
										'',
										'mint_lesson_content',
										array(
											'textarea_rows' => 14,
											'media_buttons' => false,
											'teeny'         => false,
											'quicktags'     => true,
											'editor_height' => 220,
											'tinymce'       => array(
												'toolbar1'      => '',
												'toolbar2'      => '',
												'menubar'       => false,
												'statusbar'     => false,
												'content_style' => 'body { font-family: Aeonik, "General Sans", -apple-system, "Segoe UI", Helvetica, sans-serif; font-size: 17px; line-height: 30px; color: #0F0E1A; padding: 12px 8px; margin: 0; } p { margin: 0 0 18px; }',
											),
										)
									);
									?>
								</div>
							</div>
						</div>

						<div x-show="lessonTab === 'video'" x-cloak class="mint-lesson-video mint-mt-[22px]">
							<div>
								<label class="mint-lesson-field-label" for="mint-lesson-video-url"><?php esc_html_e( 'Video URL', 'mint-lms' ); ?></label>
								<input
									id="mint-lesson-video-url"
									type="url"
									class="mint-lesson-field-input"
									x-model="selectedLesson.videoUrl"
									@input="debouncedSaveLesson(selectedLesson)"
									placeholder="https://youtube.com/…"
								/>
							</div>
							<div>
								<label class="mint-lesson-field-label" for="mint-lesson-video-desc"><?php esc_html_e( 'Description', 'mint-lms' ); ?></label>
								<textarea
									id="mint-lesson-video-desc"
									class="mint-lesson-field-textarea"
									x-model="selectedLesson.content"
									@input="debouncedSaveLesson(selectedLesson)"
									placeholder="<?php echo esc_attr__( 'Optional description…', 'mint-lms' ); ?>"
								></textarea>
							</div>
						</div>

						<div x-show="lessonTab === 'quiz'" x-cloak class="mint-mt-[22px]">
							<div class="mint-quiz-panel">
								<div class="mint-text-[15px] mint-text-ink-2"><?php esc_html_e( 'Opening quiz editor…', 'mint-lms' ); ?></div>
							</div>
						</div>

						<div class="mint-mt-6 mint-flex mint-flex-wrap mint-items-center mint-gap-4">
							<button type="button" class="mint-lesson-choose-file" @click="pickAttachment()"><?php esc_html_e( 'Choose file', 'mint-lms' ); ?></button>
							<div class="mint-text-[15px] mint-text-ink-3" x-text="selectedLesson.attachmentId ? ('<?php echo esc_js( __( 'Attached', 'mint-lms' ) ); ?> · ID ' + selectedLesson.attachmentId) : '<?php echo esc_js( __( 'No attachment', 'mint-lms' ) ); ?>'"></div>
							<button type="button" class="mint-builder-delete" x-show="selectedLesson.attachmentId" x-cloak @click="clearAttachment()"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
							<div class="mint-min-w-[12px] mint-flex-1"></div>
							<div class="mint-text-[15px] mint-font-medium mint-text-ink"><?php esc_html_e( 'Free preview', 'mint-lms' ); ?></div>
							<button
								type="button"
								role="switch"
								class="mint-lesson-preview-toggle"
								:class="selectedLesson.isPreview ? 'is-on' : ''"
								:aria-pressed="selectedLesson.isPreview ? 'true' : 'false'"
								@click="togglePreview()"
							><span class="mint-lesson-preview-toggle__knob"></span></button>
						</div>

						<section class="mint-mt-6 mint-grid mint-gap-[14px]">
							<div class="mint-text-[13px] mint-font-bold mint-uppercase mint-tracking-[0.08em] mint-text-ink-2">
								<?php esc_html_e( 'Set featured image', 'mint-lms' ); ?>
							</div>

							<div
								x-show="!selectedLesson.featuredImageUrl"
								class="mint-cover-placeholder mint-flex mint-h-[200px] mint-items-center mint-justify-center mint-rounded-[14px] mint-border-[1.5px] mint-border-dashed mint-border-[#A79FE0]"
							>
								<span class="mint-font-mono mint-text-[13px] mint-text-ink-2"><?php esc_html_e( 'featured image · 1200×675', 'mint-lms' ); ?></span>
							</div>
							<div
								x-show="selectedLesson.featuredImageUrl"
								x-cloak
								class="mint-h-[200px] mint-overflow-hidden mint-rounded-[14px] mint-border-[1.5px] mint-border-[#DAD7E6]"
							>
								<img :src="selectedLesson.featuredImageUrl" alt="" class="mint-h-full mint-w-full mint-object-cover" />
							</div>

							<p class="mint-m-0 mint-text-base mint-leading-[26px] mint-text-ink-2">
								<?php esc_html_e( 'Optional. Shown with this lesson. Landscape works best.', 'mint-lms' ); ?>
							</p>

							<div class="mint-flex mint-flex-wrap mint-gap-2.5">
								<button
									type="button"
									class="mint-settings-action mint-inline-flex mint-h-[42px] mint-cursor-pointer mint-items-center mint-justify-center mint-rounded-md mint-border-0 mint-bg-cta mint-px-4 mint-text-base mint-font-semibold mint-text-cta-ink"
									@click="pickLessonFeaturedImage()"
								><?php esc_html_e( 'Upload image', 'mint-lms' ); ?></button>
								<button
									type="button"
									class="mint-settings-action mint-inline-flex mint-h-[42px] mint-cursor-pointer mint-items-center mint-justify-center mint-rounded-md mint-border-0 mint-bg-cta mint-px-[15px] mint-text-base mint-font-semibold mint-text-cta-ink"
									x-show="selectedLesson.featuredImageId"
									x-cloak
									@click="clearLessonFeaturedImage()"
								><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
							</div>
						</section>

						<div class="mint-mt-[26px]">
							<label class="mint-block mint-text-[13px] mint-font-semibold mint-uppercase mint-tracking-[0.06em] mint-text-ink-3" for="mint-lesson-drip"><?php esc_html_e( 'Available days after enrollment', 'mint-lms' ); ?></label>
							<input
								id="mint-lesson-drip"
								type="number"
								class="mint-lesson-drip-input"
								min="0"
								step="1"
								x-model.number="selectedLesson.availableAfterDays"
								@input="debouncedSaveLesson(selectedLesson)"
								placeholder="<?php echo esc_attr__( 'Leave empty for immediate access', 'mint-lms' ); ?>"
							/>
							<p class="mint-m-0 mint-mt-1.5 mint-text-sm mint-text-ink-3"><?php esc_html_e( 'Students can access this lesson after this many days from enrollment.', 'mint-lms' ); ?></p>
						</div>

						<div class="mint-mt-8 mint-flex mint-flex-wrap mint-items-center mint-justify-between mint-gap-4 mint-border-t mint-border-rule mint-pt-5">
							<button type="button" class="mint-builder-delete" @click="deleteLesson(selectedLesson.id, selected.sectionId)"><?php esc_html_e( 'Delete lesson', 'mint-lms' ); ?></button>
							<div class="mint-flex mint-items-center mint-gap-2.5">
								<button type="button" class="mint-builder-btn-secondary" @click="saveLesson(selectedLesson)"><?php esc_html_e( 'Save draft', 'mint-lms' ); ?></button>
								<button type="button" class="mint-builder-btn-publish !mint-h-[38px] !mint-rounded-[9px] !mint-px-4 !mint-text-[15px]" @click="saveAndNext()"><?php esc_html_e( 'Save and next lesson', 'mint-lms' ); ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>

	<?php echo ( new MintLMS\Infrastructure\Admin\ViewRenderer() )->component( 'toast' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
