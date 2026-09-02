<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

/** @var MintLMS\Infrastructure\Admin\ViewRenderer $renderer */
/** @var int $courseId */

$coursesUrl = admin_url( 'admin.php?page=mint-lms-courses' );
?>
<div
	id="mint-course-builder"
	class="mint-builder-shell"
	x-data="courseBuilder(<?php echo esc_attr( (string) $courseId ); ?>)"
	x-init="init()"
>
	<!-- ═══ Toolbar (60 px) ═══ -->
	<div class="mint-builder-toolbar">
		<div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1">
			<a href="<?php echo esc_url( $coursesUrl ); ?>"
			   class="mint-btn mint-btn--ghost mint-btn--icon mint-btn--sm"
			   title="<?php echo esc_attr__( 'Back to courses', 'mint-lms' ); ?>"
			   style="flex-shrink:0">
				<svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
				</svg>
			</a>
			<span style="font-size:16px;font-weight:600;color:var(--mint-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0"
				  x-text="course.title || '<?php echo esc_js( __( 'Untitled course', 'mint-lms' ) ); ?>'"></span>
			<span class="mint-chip"
				  x-show="course.status"
				  x-text="statusLabel(course.status)"></span>
		</div>

		<div style="display:flex;align-items:center;gap:12px;flex-shrink:0">
			<span style="font-size:15px;color:var(--mint-ink-2)" x-show="saveStatus === 'saving'" x-cloak><?php echo esc_html__( 'Saving…', 'mint-lms' ); ?></span>
			<span style="font-size:15px;color:var(--mint-accent)" x-show="saveStatus === 'saved'" x-cloak><?php echo esc_html__( 'Saved', 'mint-lms' ); ?></span>
			<a :href="previewUrl"
			   target="_blank"
			   rel="noopener noreferrer"
			   class="mint-btn mint-btn--secondary mint-btn--xs"><?php echo esc_html__( 'Preview', 'mint-lms' ); ?></a>
			<button type="button"
					class="mint-btn mint-btn--primary mint-btn--xs"
					@click="publishCourse()"
					:disabled="publishing || course.status === 'published'">
				<span x-show="!publishing"><?php echo esc_html__( 'Publish', 'mint-lms' ); ?></span>
				<span x-show="publishing" x-cloak><?php echo esc_html__( 'Publishing…', 'mint-lms' ); ?></span>
			</button>
		</div>
	</div>

	<!-- ═══ Loading ═══ -->
	<div x-show="loading" x-cloak style="flex:1;display:flex;align-items:center;justify-content:center">
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

	<!-- ═══ Error ═══ -->
	<div x-show="error && !loading" x-cloak style="flex:1;display:flex;align-items:center;justify-content:center;padding:32px">
		<div style="text-align:center">
			<p style="font-size:18px;font-weight:600;color:var(--mint-ink);margin:0 0 8px"><?php echo esc_html__( 'Could not load course', 'mint-lms' ); ?></p>
			<p style="font-size:15px;color:var(--mint-ink-2);margin:0 0 16px" x-text="error"></p>
			<button type="button" class="mint-btn mint-btn--secondary mint-btn--xs" @click="loadStructure()"><?php echo esc_html__( 'Try again', 'mint-lms' ); ?></button>
		</div>
	</div>

	<!-- ═══ Builder body ═══ -->
	<div x-show="!loading && !error" x-cloak class="mint-builder-body">

		<!-- ── Left: Contents tree pane (296 px) ── -->
		<aside class="mint-builder-tree">
			<div class="mint-builder-tree__head">
				<span><?php echo esc_html__( 'CONTENTS', 'mint-lms' ); ?></span>
				<span style="margin-left:6px"
					  x-text="'· ' + sections.reduce((n, s) => n + s.lessons.length, 0) + ' <?php echo esc_js( __( 'lessons', 'mint-lms' ) ); ?>'"></span>
			</div>

			<!-- Scrollable tree -->
			<div class="mint-builder-tree__scroll" id="mint-sections-sortable">
				<template x-if="sections.length === 0">
					<div class="mint-builder-tree__empty">
						<p><?php echo esc_html__( 'No sections yet', 'mint-lms' ); ?></p>
						<button type="button" class="mint-btn mint-btn--primary mint-btn--xs" :disabled="addingSection" @click="addSection()"><?php echo esc_html__( 'Add first section', 'mint-lms' ); ?></button>
					</div>
				</template>

				<template x-for="(section, sIndex) in sections" :key="section.id">
					<div :data-section-id="section.id" style="margin-bottom:2px">
						<!-- Section row -->
						<div class="mint-builder-tree__row"
							 :class="{ 'is-selected': isSelected('section', section.id) }"
							 @click="selectItem('section', section.id)">
							<span class="mint-handle-section mint-builder-tree__icon-btn"
								  style="opacity:0.5"
								  title="<?php echo esc_attr__( 'Drag to reorder', 'mint-lms' ); ?>">
								<svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
							</span>
							<button type="button"
									class="mint-builder-tree__toggle"
									@click.stop="toggleSection(section.id)"
									:aria-expanded="isExpanded(section.id)">
								<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
									 :style="isExpanded(section.id) ? 'transform:rotate(90deg);transition:transform 150ms ease' : 'transition:transform 150ms ease'">
									<path d="M7 5l5 5-5 5"/>
								</svg>
							</button>
							<div style="flex:1;min-width:0" @click.stop>
								<template x-if="editingKey !== 'section-' + section.id">
									<span class="mint-builder-tree__label mint-builder-tree__label--section"
										  @dblclick="startEdit('section', section.id, section.title)"
										  x-text="section.title"></span>
								</template>
								<template x-if="editingKey === 'section-' + section.id">
									<input type="text"
										   class="mint-builder-tree__edit-input"
										   style="font-weight:600"
										   x-model="editingValue"
										   @keydown.enter="commitEdit('section', section.id)"
										   @keydown.escape="cancelEdit()"
										   @blur="commitEdit('section', section.id)"
										   x-ref="editInput" />
								</template>
							</div>
							<span class="mint-builder-tree__count"
								  x-text="section.lessons.length"></span>
						</div>

						<!-- Lessons list (expanded) -->
						<div x-show="isExpanded(section.id)"
							 class="mint-lessons-sortable"
							 :data-section-id="section.id">
							<template x-for="(lesson, lIndex) in section.lessons" :key="lesson.id">
								<div :data-lesson-id="lesson.id"
									 class="mint-builder-tree__row mint-builder-tree__row--lesson"
									 :class="{ 'is-selected is-selected--lesson': isSelected('lesson', lesson.id) }"
									 @click="selectItem('lesson', lesson.id, section.id)">
									<span class="mint-handle-lesson mint-builder-tree__icon-btn"
										  style="opacity:0.4"
										  title="<?php echo esc_attr__( 'Drag to reorder', 'mint-lms' ); ?>">
										<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
									</span>
									<!-- Type icon: written vs video -->
									<span class="mint-builder-tree__type-icon">
										<template x-if="!lesson.videoUrl">
											<svg width="19" height="19" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4">
												<rect x="3" y="2" width="14" height="16" rx="3" />
												<line x1="6.5" y1="6.5" x2="13.5" y2="6.5" />
												<line x1="6.5" y1="10" x2="13.5" y2="10" />
												<line x1="6.5" y1="13.5" x2="10" y2="13.5" />
											</svg>
										</template>
										<template x-if="lesson.videoUrl">
											<svg width="19" height="19" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4">
												<rect x="3" y="2" width="14" height="16" rx="3" />
												<polygon points="8.5,6.5 13.5,10 8.5,13.5" fill="currentColor" stroke="none" />
											</svg>
										</template>
									</span>
									<!-- Title -->
									<div style="flex:1;min-width:0" @click.stop>
										<template x-if="editingKey !== 'lesson-' + lesson.id">
											<span class="mint-builder-tree__label"
												  :class="{ 'is-selected': isSelected('lesson', lesson.id) }"
												  @dblclick="startEdit('lesson', lesson.id, lesson.title)"
												  x-text="lesson.title"></span>
										</template>
										<template x-if="editingKey === 'lesson-' + lesson.id">
											<input type="text"
												   class="mint-builder-tree__edit-input"
												   x-model="editingValue"
												   @keydown.enter="commitEdit('lesson', lesson.id)"
												   @keydown.escape="cancelEdit()"
												   @blur="commitEdit('lesson', lesson.id)" />
										</template>
									</div>
								</div>
							</template>

							<!-- Add lesson row -->
							<button type="button"
									class="mint-builder-tree__add-lesson"
									:disabled="addingLesson"
									@click="addLesson(section.id)">
								<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
									<line x1="10" y1="4" x2="10" y2="16"/><line x1="4" y1="10" x2="16" y2="10"/>
								</svg>
								<?php echo esc_html__( 'Add lesson', 'mint-lms' ); ?>
							</button>
						</div>
					</div>
				</template>
			</div>

			<!-- Add section (pinned below hairline) -->
			<div class="mint-builder-tree__footer">
				<button type="button"
						class="mint-builder-tree__add-btn"
						:disabled="addingSection"
						@click="addSection()">
					<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
						<line x1="10" y1="4" x2="10" y2="16"/><line x1="4" y1="10" x2="16" y2="10"/>
					</svg>
					<?php echo esc_html__( 'Add section', 'mint-lms' ); ?>
				</button>
			</div>
		</aside>

		<!-- ── Right: Editor pane ── -->
		<section class="mint-builder-editor">

			<!-- Nothing selected → empty state -->
			<template x-if="!selected">
				<div style="display:flex;align-items:center;justify-content:center;height:100%">
					<div style="text-align:center">
						<div style="width:64px;height:64px;background:var(--mint-accent-wash);border-radius:16px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center">
							<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--mint-accent)" stroke-width="1.5" stroke-linecap="round">
								<path d="M9 5l7 7-7 7"/>
							</svg>
						</div>
						<p style="font-size:17px;font-weight:600;color:var(--mint-ink);margin:0"><?php echo esc_html__( 'Pick a lesson to edit', 'mint-lms' ); ?></p>
						<p style="font-size:15px;color:var(--mint-ink-3);margin:6px 0 0"><?php echo esc_html__( 'Select from the contents pane', 'mint-lms' ); ?></p>
					</div>
				</div>
			</template>

			<!-- Section selected -->
			<template x-if="selected && selected.type === 'section'">
				<div style="max-width:var(--mint-prose-max);padding:48px 40px 96px;margin:0 auto">
					<span class="mint-t-over" style="display:block;margin-bottom:24px"><?php echo esc_html__( 'SECTION', 'mint-lms' ); ?></span>
					<div style="margin-bottom:24px">
						<input type="text"
							   style="width:100%;font-size:38px;line-height:44px;letter-spacing:-0.035em;font-weight:600;color:var(--mint-ink);border:none;border-bottom:2px solid transparent;background:transparent;outline:none;padding:4px 0;font-family:var(--mint-font);transition:border-color var(--mint-t-hover) ease-out"
							   @mouseenter="$el.style.borderBottomColor = 'rgba(15,14,26,0.12)'"
							   @mouseleave="if (document.activeElement !== $el) $el.style.borderBottomColor = 'transparent'"
							   @focus="$el.style.borderBottomColor = 'var(--mint-accent)'"
							   @blur="$el.style.borderBottomColor = 'transparent'"
							   x-model="selectedSection.title"
							   @input="debouncedSaveSection(selectedSection)"
							   placeholder="<?php echo esc_attr__( 'Section title', 'mint-lms' ); ?>" />
					</div>

					<!-- Section footer -->
					<div style="border-top:1px solid var(--mint-rule);padding-top:20px;margin-top:40px">
						<button type="button"
								class="mint-btn mint-btn--danger-outline mint-btn--xs"
								@click="deleteSection(selected.id)">
							<?php echo esc_html__( 'Delete section', 'mint-lms' ); ?>
						</button>
					</div>
				</div>
			</template>

			<!-- Lesson selected -->
			<div x-show="selected && selected.type === 'lesson'" x-cloak style="display:flex;flex-direction:column;height:100%" x-data="{lessonTab: 'written'}" x-init="lessonTab = (selectedLesson && selectedLesson.videoUrl) ? 'video' : 'written'">
					<!-- Scrollable editor area -->
					<div style="flex:1;overflow-y:auto">
						<div style="max-width:var(--mint-prose-max);padding:48px 40px 96px;margin:0 auto">

							<!-- Breadcrumb -->
							<nav style="display:flex;align-items:center;gap:6px;font-size:15px;color:var(--mint-ink-2);margin-bottom:16px">
								<span x-text="sections.find(s => s.lessons.some(l => l.id === selected.id))?.title || '<?php echo esc_js( __( 'Section', 'mint-lms' ) ); ?>'"></span>
								<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M7 5l5 5-5 5"/></svg>
								<span style="color:var(--mint-ink)" x-text="selectedLesson.title"></span>
							</nav>

							<!-- Lesson title (editable, h1 size) -->
							<div style="margin-bottom:24px">
								<input type="text"
									   style="width:100%;font-size:38px;line-height:44px;letter-spacing:-0.035em;font-weight:600;color:var(--mint-ink);border:none;border-bottom:2px solid transparent;background:transparent;outline:none;padding:4px 0;font-family:var(--mint-font);transition:border-color var(--mint-t-hover) ease-out"
									   @mouseenter="$el.style.borderBottomColor = 'rgba(15,14,26,0.12)'"
									   @mouseleave="if (document.activeElement !== $el) $el.style.borderBottomColor = 'transparent'"
									   @focus="$el.style.borderBottomColor = 'var(--mint-accent)'"
									   @blur="$el.style.borderBottomColor = 'transparent'"
									   x-model="selectedLesson.title"
									   @input="debouncedSaveLesson(selectedLesson)"
									   placeholder="<?php echo esc_attr__( 'Lesson title', 'mint-lms' ); ?>" />
							</div>

							<!-- Type switch (segmented control) -->
							<div style="margin-bottom:24px">
								<div class="mint-segmented">
									<button type="button" class="mint-segmented__item"
											:class="{'is-active': lessonTab === 'written'}"
											@click="lessonTab = 'written'">
										<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="3" y="2" width="14" height="16" rx="3"/><line x1="6.5" y1="6.5" x2="13.5" y2="6.5"/><line x1="6.5" y1="10" x2="13.5" y2="10"/><line x1="6.5" y1="13.5" x2="10" y2="13.5"/></svg>
										<?php echo esc_html__( 'Written', 'mint-lms' ); ?>
									</button>
									<button type="button" class="mint-segmented__item"
											:class="{'is-active': lessonTab === 'video'}"
											@click="lessonTab = 'video'">
										<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="2" y="4" width="16" height="12" rx="2.5"/><polygon points="8.5,7.5 13.5,10 8.5,12.5" fill="currentColor" stroke="none"/></svg>
										<?php echo esc_html__( 'Video', 'mint-lms' ); ?>
									</button>
									<button type="button" class="mint-segmented__item"
											:class="{'is-active': lessonTab === 'quiz'}"
											@click="lessonTab = 'quiz'">
										<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4"><circle cx="10" cy="10" r="7"/><path d="M7.5 8.5a2.5 2.5 0 014.5 1.5c0 1.5-2.5 1.5-2.5 3"/><circle cx="10" cy="14.5" r="0.5" fill="currentColor"/></svg>
										<?php echo esc_html__( 'Quiz', 'mint-lms' ); ?>
									</button>
								</div>
							</div>

							<!-- Editor frame: Written content -->
							<div x-show="lessonTab === 'written'"
								 style="border:1px solid var(--mint-border);border-radius:var(--mint-r-xl);overflow:hidden;margin-bottom:32px">
								<div style="border-bottom:1px solid var(--mint-rule);padding:8px 14px;display:flex;align-items:center;gap:8px;background:var(--mint-bg)">
									<span style="font-size:14px;color:var(--mint-ink-3)"><?php echo esc_html__( 'Content', 'mint-lms' ); ?></span>
								</div>
								<div style="padding:0" class="mint-wp-editor">
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

							<!-- Editor frame: Video -->
							<div x-show="lessonTab === 'video'"
								 style="border:1px solid var(--mint-border);border-radius:var(--mint-r-xl);overflow:hidden;margin-bottom:32px">
								<div style="padding:20px;display:flex;flex-direction:column;gap:16px">
									<div>
										<label class="mint-label mint-label--sm" style="margin-bottom:6px"><?php echo esc_html__( 'Video URL', 'mint-lms' ); ?></label>
										<input type="url" class="mint-input"
											   x-model="selectedLesson.videoUrl"
											   @input="debouncedSaveLesson(selectedLesson)"
											   placeholder="https://youtube.com/…" />
									</div>
									<div>
										<label class="mint-label mint-label--sm" style="margin-bottom:6px"><?php echo esc_html__( 'Description', 'mint-lms' ); ?></label>
										<textarea class="mint-textarea"
												  style="min-height:120px"
												  x-model="selectedLesson.content"
												  @input="debouncedSaveLesson(selectedLesson)"
												  placeholder="<?php echo esc_attr__( 'Optional description…', 'mint-lms' ); ?>"></textarea>
									</div>
								</div>
							</div>

							<!-- Editor frame: Quiz -->
							<div x-show="lessonTab === 'quiz'"
								 style="border:1px solid var(--mint-border);border-radius:var(--mint-r-xl);overflow:hidden;margin-bottom:32px">
								<div style="padding:20px;display:flex;flex-direction:column;gap:16px">
									<div x-show="quizLoading" style="font-size:15px;color:var(--mint-ink-3)"><?php echo esc_html__( 'Loading quiz…', 'mint-lms' ); ?></div>

									<template x-if="!quizLoading && lessonQuiz">
										<div style="display:flex;flex-direction:column;gap:16px">
											<div style="display:flex;gap:16px;flex-wrap:wrap">
												<div style="flex:1;min-width:200px">
													<label class="mint-label mint-label--sm"><?php echo esc_html__( 'Quiz title', 'mint-lms' ); ?></label>
													<input type="text" class="mint-input" x-model="lessonQuiz.title" />
												</div>
												<div style="width:120px">
													<label class="mint-label mint-label--sm"><?php echo esc_html__( 'Pass %', 'mint-lms' ); ?></label>
													<input type="number" class="mint-input" min="0" max="100" x-model.number="lessonQuiz.passPercent" />
												</div>
											</div>

											<div style="display:flex;gap:8px;flex-wrap:wrap">
												<button type="button" class="mint-btn mint-btn--secondary mint-btn--xs" @click="addQuizQuestion('mcq')"><?php echo esc_html__( 'Add MCQ', 'mint-lms' ); ?></button>
												<button type="button" class="mint-btn mint-btn--secondary mint-btn--xs" @click="addQuizQuestion('true_false')"><?php echo esc_html__( 'Add True/False', 'mint-lms' ); ?></button>
												<button type="button" class="mint-btn mint-btn--primary mint-btn--xs" @click="saveQuiz()" :disabled="quizSaving"><?php echo esc_html__( 'Save quiz', 'mint-lms' ); ?></button>
												<button type="button" class="mint-btn mint-btn--danger-outline mint-btn--xs" x-show="lessonQuiz.id" @click="deleteQuiz()"><?php echo esc_html__( 'Delete quiz', 'mint-lms' ); ?></button>
											</div>

											<template x-for="(question, qIndex) in lessonQuiz.questions" :key="question.id || question._key">
												<div style="border:1px solid var(--mint-rule);border-radius:8px;padding:16px">
													<div style="display:flex;justify-content:space-between;margin-bottom:12px">
														<span class="mint-t-over" x-text="'<?php echo esc_js( __( 'Question', 'mint-lms' ) ); ?> ' + (qIndex + 1)"></span>
														<button type="button" style="border:none;background:none;color:var(--mint-ink-3);cursor:pointer;font-size:14px" @click="deleteQuizQuestion(question)"><?php echo esc_html__( 'Remove', 'mint-lms' ); ?></button>
													</div>
													<textarea class="mint-textarea" style="min-height:80px;margin-bottom:12px" x-model="question.prompt" placeholder="<?php echo esc_attr__( 'Question prompt…', 'mint-lms' ); ?>"></textarea>

													<template x-if="question.type === 'true_false'">
														<div style="display:flex;gap:16px;margin-bottom:12px">
															<label style="display:flex;align-items:center;gap:6px;font-size:15px">
																<input type="radio" :name="'tf-' + (question.id || question._key)" value="true" x-model="question.correctAnswer" />
																<?php echo esc_html__( 'True is correct', 'mint-lms' ); ?>
															</label>
															<label style="display:flex;align-items:center;gap:6px;font-size:15px">
																<input type="radio" :name="'tf-' + (question.id || question._key)" value="false" x-model="question.correctAnswer" />
																<?php echo esc_html__( 'False is correct', 'mint-lms' ); ?>
															</label>
														</div>
													</template>

													<template x-if="question.type === 'mcq'">
														<div style="display:flex;flex-direction:column;gap:8px;margin-bottom:12px">
															<template x-for="(option, oIndex) in question.options" :key="oIndex">
																<div style="display:flex;align-items:center;gap:8px">
																	<input type="radio" :name="'mcq-' + (question.id || question._key)" :value="option" x-model="question.correctAnswer" />
																	<input type="text" class="mint-input" style="flex:1" x-model="question.options[oIndex]" :placeholder="'<?php echo esc_js( __( 'Option', 'mint-lms' ) ); ?> ' + (oIndex + 1)" />
																	<button type="button" style="border:none;background:none;color:var(--mint-ink-3);cursor:pointer" @click="removeMcqOption(question, oIndex)">×</button>
																</div>
															</template>
															<button type="button" class="mint-btn mint-btn--ghost mint-btn--xs" x-show="question.options.length < 4" @click="addMcqOption(question)"><?php echo esc_html__( 'Add option', 'mint-lms' ); ?></button>
														</div>
													</template>

													<button type="button" class="mint-btn mint-btn--secondary mint-btn--xs" @click="saveQuizQuestion(question)" :disabled="quizSaving"><?php echo esc_html__( 'Save question', 'mint-lms' ); ?></button>
												</div>
											</template>

											<p x-show="lessonQuiz.questions.length === 0" style="font-size:15px;color:var(--mint-ink-3);margin:0"><?php echo esc_html__( 'No questions yet. Add MCQ or True/False questions above.', 'mint-lms' ); ?></p>
										</div>
									</template>
								</div>
							</div>

							<!-- Attachment & Free preview -->
							<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:32px">
								<div style="display:flex;align-items:center;gap:12px">
									<button type="button" class="mint-btn mint-btn--secondary mint-btn--xs" @click="pickAttachment()">
										<?php echo esc_html__( 'Choose file', 'mint-lms' ); ?>
									</button>
									<span style="font-size:14px;color:var(--mint-ink-3)"
										  x-text="selectedLesson.attachmentId ? '<?php echo esc_js( __( 'Attached', 'mint-lms' ) ); ?> (ID: ' + selectedLesson.attachmentId + ')' : '<?php echo esc_js( __( 'No attachment', 'mint-lms' ) ); ?>'"></span>
									<button type="button"
											x-show="selectedLesson.attachmentId"
											style="font-size:14px;color:var(--mint-ink-3);border:none;background:none;cursor:pointer;text-decoration:underline"
											@click="clearAttachment()"><?php echo esc_html__( 'Remove', 'mint-lms' ); ?></button>
								</div>
								<div style="display:flex;align-items:center;gap:10px">
									<label style="font-size:15px;font-weight:500;color:var(--mint-ink-2)"><?php echo esc_html__( 'Free preview', 'mint-lms' ); ?></label>
									<button type="button" role="switch"
											:aria-checked="selectedLesson.isPreview"
											class="mint-toggle"
											:class="{'is-on': selectedLesson.isPreview}"
											@click="togglePreview()">
										<span class="mint-toggle__knob"></span>
									</button>
								</div>
							</div>

							<div style="margin-bottom:32px">
								<label class="mint-label mint-label--sm" style="margin-bottom:6px"><?php echo esc_html__( 'Available days after enrollment', 'mint-lms' ); ?></label>
								<input type="number"
									   class="mint-input"
									   min="0"
									   step="1"
									   x-model.number="selectedLesson.availableAfterDays"
									   @input="debouncedSaveLesson(selectedLesson)"
									   placeholder="<?php echo esc_attr__( 'Leave empty for immediate access', 'mint-lms' ); ?>" />
								<p style="font-size:14px;color:var(--mint-ink-3);margin:6px 0 0"><?php echo esc_html__( 'Students can access this lesson after this many days from enrollment.', 'mint-lms' ); ?></p>
							</div>

						</div>
					</div>

					<!-- Footer bar (pinned) -->
					<div style="flex-shrink:0;border-top:1px solid var(--mint-rule);padding:14px 24px;display:flex;align-items:center;justify-content:space-between;background:var(--mint-bg)">
						<button type="button"
								class="mint-btn mint-btn--danger-outline mint-btn--xs"
								style="height:38px"
								@click="deleteLesson(selectedLesson.id, selected.sectionId)">
							<?php echo esc_html__( 'Delete lesson', 'mint-lms' ); ?>
						</button>
						<div style="display:flex;align-items:center;gap:10px">
							<button type="button"
									class="mint-btn mint-btn--secondary mint-btn--xs"
									style="height:38px"
									@click="saveLesson(selectedLesson)">
								<?php echo esc_html__( 'Save draft', 'mint-lms' ); ?>
							</button>
							<button type="button"
									class="mint-btn mint-btn--dark mint-btn--xs"
									style="height:38px"
									@click="saveLesson(selectedLesson); let _s = sections.find(s => s.lessons.some(l => l.id === selected.id)); if (_s) { let _i = _s.lessons.findIndex(l => l.id === selected.id); if (_i < _s.lessons.length - 1) selectItem('lesson', _s.lessons[_i+1].id, _s.id); }">
								<?php echo esc_html__( 'Save and next', 'mint-lms' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>

		</section>
	</div>

	<?php echo ( new MintLMS\Infrastructure\Admin\ViewRenderer() )->component( 'toast' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
