<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

/** @var MintLMS\Infrastructure\Admin\ViewRenderer $renderer */
/** @var int $courseId */
/** @var int $lessonId */

$courseId = isset( $courseId ) ? (int) $courseId : 0;
$lessonId = isset( $lessonId ) ? (int) $lessonId : 0;

$coursesUrl   = \MintLMS\Infrastructure\PostType\PostTypes::listUrl( \MintLMS\Infrastructure\PostType\PostTypes::COURSE );
$lessonsUrl   = \MintLMS\Infrastructure\PostType\PostTypes::listUrl( \MintLMS\Infrastructure\PostType\PostTypes::LESSON );
$quizzesUrl   = \MintLMS\Infrastructure\PostType\PostTypes::listUrl( \MintLMS\Infrastructure\PostType\PostTypes::QUIZ );
$questionsUrl = \MintLMS\Infrastructure\PostType\PostTypes::listUrl( \MintLMS\Infrastructure\PostType\PostTypes::QUESTION );

$inputClass    = 'mint-settings-input mint-block mint-w-full mint-rounded-md mint-border-[1.5px] mint-border-[#B9B6CE] mint-bg-white mint-px-[14px] mint-text-[17px] mint-leading-7 mint-text-ink focus:mint-outline-none';
$textareaClass = $inputClass . ' mint-min-h-[110px] mint-resize-y mint-py-[13px]';
$labelClass    = 'mint-mb-1.5 mint-block mint-text-[13px] mint-font-bold mint-uppercase mint-tracking-[0.08em] mint-text-ink-2';
?>
<div
	id="mint-course-builder"
	class="mint-builder-shell"
	x-data="courseBuilder(<?php echo (int) $courseId; ?>, { lessonId: <?php echo (int) $lessonId; ?> })"
	x-init="init()"
>
	<nav class="mint-builder-nav" aria-label="<?php echo esc_attr__( 'Builder contents', 'mint-lms' ); ?>">
		<div
			class="mint-builder-nav__item"
			:class="navTabActive('section') ? 'is-active' : ''"
			tabindex="0"
			@click="toggleNavMenu('section')"
			@mouseenter="openNavMenu('section')"
			@mouseleave="closeNavMenu('section')"
			:style="{ color: navTabInk('section') }"
		>
			<span><?php esc_html_e( 'Section', 'mint-lms' ); ?></span>
			<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="{ transform: 'rotate(' + navChevronDeg('section') + 'deg)' }"><path d="m5 8 5 5 5-5" /></svg>
			<div class="mint-builder-nav__menu" x-show="navMenuOpen('section')" x-cloak @click.stop>
				<div class="mint-builder-nav__heading"><?php esc_html_e( 'Section', 'mint-lms' ); ?></div>
				<button type="button" class="mint-builder-nav__option" @click="navOpenOverview('section')">
					<span class="mint-builder-nav__icon mint-builder-nav__icon--section" aria-hidden="true">
						<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#5B2BFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5h12M4 10h12M4 14.5h8" /></svg>
					</span>
					<span class="mint-truncate"><?php esc_html_e( 'Overview', 'mint-lms' ); ?></span>
				</button>
				<button type="button" class="mint-builder-nav__add" :disabled="addingSection || isStandalone || !courseId" @click="navAddSection()">
					<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10" /></svg>
					<?php esc_html_e( 'Add new section', 'mint-lms' ); ?>
				</button>
			</div>
		</div>

		<div
			class="mint-builder-nav__item"
			:class="navTabActive('lesson') ? 'is-active' : ''"
			tabindex="0"
			@click="toggleNavMenu('lesson')"
			@mouseenter="openNavMenu('lesson')"
			@mouseleave="closeNavMenu('lesson')"
			:style="{ color: navTabInk('lesson') }"
		>
			<span><?php esc_html_e( 'Lesson', 'mint-lms' ); ?></span>
			<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="{ transform: 'rotate(' + navChevronDeg('lesson') + 'deg)' }"><path d="m5 8 5 5 5-5" /></svg>
			<div class="mint-builder-nav__menu" x-show="navMenuOpen('lesson')" x-cloak @click.stop>
				<div class="mint-builder-nav__heading"><?php esc_html_e( 'Lesson', 'mint-lms' ); ?></div>
				<button type="button" class="mint-builder-nav__option" @click="navOpenOverview('lesson')">
					<span class="mint-builder-nav__icon mint-builder-nav__icon--lesson" aria-hidden="true">
						<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#1A5AA8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="14" height="12" rx="2.5" /></svg>
					</span>
					<span class="mint-truncate"><?php esc_html_e( 'Overview', 'mint-lms' ); ?></span>
				</button>
				<button type="button" class="mint-builder-nav__add" :disabled="addingLesson || isStandalone || !courseId" @click="navAddLesson()">
					<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10" /></svg>
					<?php esc_html_e( 'Add new lesson', 'mint-lms' ); ?>
				</button>
			</div>
		</div>

		<div
			class="mint-builder-nav__item"
			:class="navTabActive('quiz') ? 'is-active' : ''"
			tabindex="0"
			@click="toggleNavMenu('quiz')"
			@mouseenter="openNavMenu('quiz')"
			@mouseleave="closeNavMenu('quiz')"
			:style="{ color: navTabInk('quiz') }"
		>
			<span><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></span>
			<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="{ transform: 'rotate(' + navChevronDeg('quiz') + 'deg)' }"><path d="m5 8 5 5 5-5" /></svg>
			<div class="mint-builder-nav__menu" x-show="navMenuOpen('quiz')" x-cloak @click.stop>
				<div class="mint-builder-nav__heading"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></div>
				<button type="button" class="mint-builder-nav__option" @click="navOpenOverview('quiz')">
					<span class="mint-builder-nav__icon mint-builder-nav__icon--quiz" aria-hidden="true">
						<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 10.5 8 14 15.5 5.5" /></svg>
					</span>
					<span class="mint-truncate"><?php esc_html_e( 'Overview', 'mint-lms' ); ?></span>
				</button>
				<button type="button" class="mint-builder-nav__add" :disabled="addingCourseQuiz || quizSaving" @click="navAddQuiz()">
					<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10" /></svg>
					<?php esc_html_e( 'Add new quiz', 'mint-lms' ); ?>
				</button>
			</div>
		</div>

		<div
			class="mint-builder-nav__item"
			:class="navTabActive('question') ? 'is-active' : ''"
			tabindex="0"
			@click="toggleNavMenu('question')"
			@mouseenter="openNavMenu('question')"
			@mouseleave="closeNavMenu('question')"
			:style="{ color: navTabInk('question') }"
		>
			<span><?php esc_html_e( 'Question', 'mint-lms' ); ?></span>
			<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="{ transform: 'rotate(' + navChevronDeg('question') + 'deg)' }"><path d="m5 8 5 5 5-5" /></svg>
			<div class="mint-builder-nav__menu" x-show="navMenuOpen('question')" x-cloak @click.stop>
				<div class="mint-builder-nav__heading"><?php esc_html_e( 'Question', 'mint-lms' ); ?></div>
				<button type="button" class="mint-builder-nav__option" @click="navOpenOverview('question')">
					<span class="mint-builder-nav__icon mint-builder-nav__icon--question" aria-hidden="true">?</span>
					<span class="mint-truncate"><?php esc_html_e( 'Overview', 'mint-lms' ); ?></span>
				</button>
				<button type="button" class="mint-builder-nav__add" @click="navAddQuestion()">
					<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10" /></svg>
					<?php esc_html_e( 'Add new question', 'mint-lms' ); ?>
				</button>
			</div>
		</div>

		<div
			class="mint-builder-nav__item"
			tabindex="0"
			@click="toggleNavMenu('tree')"
			@mouseenter="openNavMenu('tree')"
			:style="{ color: navTabInk('tree') }"
		>
			<span><?php esc_html_e( 'Course Content Tree', 'mint-lms' ); ?></span>
			<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="{ transform: 'rotate(' + navChevronDeg('tree') + 'deg)' }"><path d="m5 8 5 5 5-5" /></svg>
			<template x-if="navMenuOpen('tree')">
				<div>
					<div class="mint-builder-nav__overlay" @click.stop="closeNavMenu('tree')"></div>
					<div class="mint-builder-nav__tree" @click.stop>
						<div class="mint-builder-nav__heading"><?php esc_html_e( 'Course Content Tree', 'mint-lms' ); ?></div>

						<div class="mint-builder-nav__level"><?php esc_html_e( 'Course', 'mint-lms' ); ?></div>
						<div class="mint-builder-nav__tree-head">
							<span class="mint-builder-nav__icon mint-builder-nav__icon--course" aria-hidden="true">
								<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#1A5AA8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3.5" width="14" height="13" rx="2.5" /><path d="M8 3.5v13" /></svg>
							</span>
							<span class="mint-truncate mint-font-bold" x-text="course.title || '<?php echo esc_js( __( 'Untitled course', 'mint-lms' ) ); ?>'"></span>
						</div>

						<template x-if="navUngroupedSection() && (navUngroupedSection().lessons || []).length">
							<div>
								<div class="mint-builder-nav__level"><?php esc_html_e( 'Lesson', 'mint-lms' ); ?></div>
								<template x-for="les in (navUngroupedSection().lessons || [])" :key="'nav-tree-ules-' + les.id">
									<div>
										<button type="button" class="mint-builder-nav__tree-row" @click.stop="toggleLesson(les.id)">
											<span class="mint-builder-nav__grip" aria-hidden="true">⠿⠿</span>
											<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#5C5C77" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="{ transform: 'rotate(' + (isLessonExpanded(les.id) ? 90 : 0) + 'deg)' }"><path d="m7 5 6 5-6 5" /></svg>
											<span class="mint-builder-nav__icon mint-builder-nav__icon--lesson" aria-hidden="true">
												<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#1A5AA8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="14" height="12" rx="2.5" /></svg>
											</span>
											<span class="mint-truncate mint-flex-1" x-text="les.title || '<?php echo esc_js( __( 'Untitled lesson', 'mint-lms' ) ); ?>'"></span>
											<span class="mint-builder-nav__badge" x-text="lessonChildCountLabel(les)"></span>
										</button>

										<div x-show="isLessonExpanded(les.id)" class="mint-builder-nav__tree-children">
											<div class="mint-builder-nav__level" x-show="sidebarQuizzesForLesson(les).length"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></div>
											<template x-for="qz in sidebarQuizzesForLesson(les)" :key="'nav-tree-uqz-' + qz.id">
												<div>
													<button type="button" class="mint-builder-nav__tree-row" @click.stop="toggleQuiz(qz.id)">
														<span class="mint-builder-nav__grip" aria-hidden="true">⠿⠿</span>
														<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#5C5C77" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="{ transform: 'rotate(' + (isQuizExpanded(qz.id) ? 90 : 0) + 'deg)' }"><path d="m7 5 6 5-6 5" /></svg>
														<span class="mint-builder-nav__icon mint-builder-nav__icon--quiz" aria-hidden="true">
															<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 10.5 8 14 15.5 5.5" /></svg>
														</span>
														<span class="mint-truncate mint-flex-1" x-text="qz.title || '<?php echo esc_js( __( 'Untitled quiz', 'mint-lms' ) ); ?>'"></span>
														<span class="mint-builder-nav__badge" x-text="quizChildCountLabel(qz)"></span>
													</button>

													<div x-show="isQuizExpanded(qz.id)" class="mint-builder-nav__tree-children">
														<div class="mint-builder-nav__level" x-show="sidebarQuestionsForQuiz(qz).length"><?php esc_html_e( 'Question', 'mint-lms' ); ?></div>
														<template x-for="qt in sidebarQuestionsForQuiz(qz)" :key="'nav-tree-uqt-' + qt.id">
															<button type="button" class="mint-builder-nav__tree-row mint-builder-nav__tree-row--leaf" @click.stop="selectNavQuestion({ ...qt, quizId: qz.id, lessonId: qz.lessonId, sectionId: 0 })">
																<span class="mint-builder-nav__icon mint-builder-nav__icon--question" aria-hidden="true">?</span>
																<span class="mint-truncate" x-text="qt.title || '<?php echo esc_js( __( 'Untitled question', 'mint-lms' ) ); ?>'"></span>
															</button>
														</template>
													</div>
												</div>
											</template>
										</div>
									</div>
								</template>
							</div>
						</template>

						<div class="mint-builder-nav__level" x-show="navSectionList().length > 0"><?php esc_html_e( 'Section', 'mint-lms' ); ?></div>

						<template x-for="sec in navSectionList()" :key="'nav-tree-sec-' + sec.id">
							<div>
								<button
									type="button"
									class="mint-builder-nav__tree-row"
									@click.stop="toggleSection(sec.id)"
								>
									<span class="mint-builder-nav__grip" aria-hidden="true">⠿⠿</span>
									<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#5C5C77" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="{ transform: 'rotate(' + (isExpanded(sec.id) ? 90 : 0) + 'deg)' }"><path d="m7 5 6 5-6 5" /></svg>
									<span class="mint-builder-nav__icon mint-builder-nav__icon--section" aria-hidden="true">
										<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#5B2BFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5h12M4 10h12M4 14.5h8" /></svg>
									</span>
									<span class="mint-truncate mint-flex-1" x-text="sec.title || '<?php echo esc_js( __( 'Untitled section', 'mint-lms' ) ); ?>'"></span>
									<span class="mint-builder-nav__badge" x-text="sectionChildCountLabel(sec)"></span>
								</button>

								<div x-show="isExpanded(sec.id)" class="mint-builder-nav__tree-children">
									<div class="mint-builder-nav__level" x-show="(sec.lessons || []).length"><?php esc_html_e( 'Lesson', 'mint-lms' ); ?></div>
									<template x-for="les in (sec.lessons || [])" :key="'nav-tree-les-' + les.id">
										<div>
											<button type="button" class="mint-builder-nav__tree-row" @click.stop="toggleLesson(les.id)">
												<span class="mint-builder-nav__grip" aria-hidden="true">⠿⠿</span>
												<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#5C5C77" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="{ transform: 'rotate(' + (isLessonExpanded(les.id) ? 90 : 0) + 'deg)' }"><path d="m7 5 6 5-6 5" /></svg>
												<span class="mint-builder-nav__icon mint-builder-nav__icon--lesson" aria-hidden="true">
													<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#1A5AA8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="14" height="12" rx="2.5" /></svg>
												</span>
												<span class="mint-truncate mint-flex-1" x-text="les.title || '<?php echo esc_js( __( 'Untitled lesson', 'mint-lms' ) ); ?>'"></span>
												<span class="mint-builder-nav__badge" x-text="lessonChildCountLabel(les)"></span>
											</button>

											<div x-show="isLessonExpanded(les.id)" class="mint-builder-nav__tree-children">
												<div class="mint-builder-nav__level" x-show="sidebarQuizzesForLesson(les).length"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></div>
												<template x-for="qz in sidebarQuizzesForLesson(les)" :key="'nav-tree-qz-' + qz.id">
													<div>
														<button type="button" class="mint-builder-nav__tree-row" @click.stop="toggleQuiz(qz.id)">
															<span class="mint-builder-nav__grip" aria-hidden="true">⠿⠿</span>
															<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#5C5C77" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="{ transform: 'rotate(' + (isQuizExpanded(qz.id) ? 90 : 0) + 'deg)' }"><path d="m7 5 6 5-6 5" /></svg>
															<span class="mint-builder-nav__icon mint-builder-nav__icon--quiz" aria-hidden="true">
																<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 10.5 8 14 15.5 5.5" /></svg>
															</span>
															<span class="mint-truncate mint-flex-1" x-text="qz.title || '<?php echo esc_js( __( 'Untitled quiz', 'mint-lms' ) ); ?>'"></span>
															<span class="mint-builder-nav__badge" x-text="quizChildCountLabel(qz)"></span>
														</button>

														<div x-show="isQuizExpanded(qz.id)" class="mint-builder-nav__tree-children">
															<div class="mint-builder-nav__level" x-show="sidebarQuestionsForQuiz(qz).length"><?php esc_html_e( 'Question', 'mint-lms' ); ?></div>
															<template x-for="qt in sidebarQuestionsForQuiz(qz)" :key="'nav-tree-qt-' + qt.id">
																<button type="button" class="mint-builder-nav__tree-row mint-builder-nav__tree-row--leaf" @click.stop="selectNavQuestion({ ...qt, quizId: qz.id, lessonId: qz.lessonId, sectionId: qz.sectionId || sec.id })">
																	<span class="mint-builder-nav__icon mint-builder-nav__icon--question" aria-hidden="true">?</span>
																	<span class="mint-truncate" x-text="qt.title || '<?php echo esc_js( __( 'Untitled question', 'mint-lms' ) ); ?>'"></span>
																</button>
															</template>
														</div>
													</div>
												</template>
											</div>
										</div>
									</template>
								</div>
							</div>
						</template>
					</div>
				</div>
			</template>
		</div>
	</nav>

	<header class="mint-builder-toolbar">
		<div class="mint-flex mint-min-w-0 mint-items-center mint-gap-[14px]">
			<template x-if="fromQuestions && !fromBuilderOrigin">
				<a
					:href="listDashboardUrl('questions')"
					class="mint-builder-back-lessons mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2 mint-rounded-lg mint-px-2 mint-py-1.5 mint-no-underline mint-text-ink hover:mint-bg-[#F4F3F8]"
					aria-label="<?php echo esc_attr__( 'Back to questions', 'mint-lms' ); ?>"
					@click="rememberOriginTab()"
				>
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mint-shrink-0"><path d="M12.5 4.5 7 10l5.5 5.5"/></svg>
					<span class="mint-truncate mint-text-base mint-font-semibold mint-tracking-[-0.015em]"><?php esc_html_e( 'Back to questions', 'mint-lms' ); ?></span>
				</a>
			</template>
			<template x-if="fromQuizzes && !fromQuestions && !fromBuilderOrigin">
				<a
					:href="listDashboardUrl('quizzes')"
					class="mint-builder-back-lessons mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2 mint-rounded-lg mint-px-2 mint-py-1.5 mint-no-underline mint-text-ink hover:mint-bg-[#F4F3F8]"
					aria-label="<?php echo esc_attr__( 'Back to quizzes', 'mint-lms' ); ?>"
					@click="rememberOriginTab()"
				>
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mint-shrink-0"><path d="M12.5 4.5 7 10l5.5 5.5"/></svg>
					<span class="mint-truncate mint-text-base mint-font-semibold mint-tracking-[-0.015em]"><?php esc_html_e( 'Back to quizzes', 'mint-lms' ); ?></span>
				</a>
			</template>
			<template x-if="fromBuilderOrigin && fromQuizzes">
				<a
					:href="parentCourseEditorUrl()"
					class="mint-builder-back-lessons mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2 mint-rounded-lg mint-px-2 mint-py-1.5 mint-no-underline mint-text-ink hover:mint-bg-[#F4F3F8]"
					aria-label="<?php echo esc_attr__( 'Back to course', 'mint-lms' ); ?>"
					@click="goToParentCourse($event)"
				>
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mint-shrink-0"><path d="M12.5 4.5 7 10l5.5 5.5"/></svg>
					<span class="mint-truncate mint-text-base mint-font-semibold mint-tracking-[-0.015em]" x-text="breadcrumbCourseTitle() || '<?php echo esc_js( __( 'Back to course', 'mint-lms' ) ); ?>'"></span>
				</a>
			</template>
			<template x-if="fromLessons && !fromQuizzes">
				<a
					:href="listDashboardUrl('lessons')"
					class="mint-builder-back-lessons mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2 mint-rounded-lg mint-px-2 mint-py-1.5 mint-no-underline mint-text-ink hover:mint-bg-[#F4F3F8]"
					aria-label="<?php echo esc_attr__( 'Back to lessons', 'mint-lms' ); ?>"
					@click="rememberOriginTab()"
				>
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mint-shrink-0"><path d="M12.5 4.5 7 10l5.5 5.5"/></svg>
					<span class="mint-truncate mint-text-base mint-font-semibold mint-tracking-[-0.015em]"><?php esc_html_e( 'Back to lessons', 'mint-lms' ); ?></span>
				</a>
			</template>
			<template x-if="!fromQuizzes && !fromLessons">
				<div class="mint-flex mint-min-w-0 mint-items-center mint-gap-[14px]">
					<a
						:href="listDashboardUrl('courses')"
						class="mint-builder-back"
						aria-label="<?php echo esc_attr__( 'Back to courses', 'mint-lms' ); ?>"
						@click="rememberOriginTab()"
					>
						<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.5 4.5 7 10l5.5 5.5"/></svg>
					</a>
					<div class="mint-flex mint-min-w-0 mint-items-center mint-gap-2.5">
						<a
							class="mint-truncate mint-text-base mint-font-semibold mint-tracking-[-0.015em] mint-text-ink mint-no-underline hover:mint-underline"
							:href="courseSettingsUrl()"
							@click="goToCourseSettings($event)"
							x-text="course.title || '<?php echo esc_js( __( 'Untitled course', 'mint-lms' ) ); ?>'"
						></a>
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
				href="#"
				class="mint-builder-btn-preview"
				@click.prevent="openBuilderPreview($event)"
			><?php esc_html_e( 'Preview', 'mint-lms' ); ?></a>
			<button
				type="button"
				class="mint-builder-btn-secondary"
				@click="headerSaveAction()"
				:disabled="headerSaveDisabled()"
			>
				<span x-show="saveStatus !== 'saving' && !quizSaving"><?php esc_html_e( 'Save changes', 'mint-lms' ); ?></span>
				<span x-show="saveStatus === 'saving' || quizSaving" x-cloak><?php esc_html_e( 'Saving…', 'mint-lms' ); ?></span>
			</button>
			<button
				type="button"
				class="mint-builder-btn-publish"
				x-show="headerShowsLive()"
				x-cloak
				@click="headerLiveAction()"
				:disabled="headerLiveDisabled()"
			>
				<span x-show="!publishing"><?php esc_html_e( 'Live', 'mint-lms' ); ?></span>
				<span x-show="publishing" x-cloak><?php esc_html_e( 'Going live…', 'mint-lms' ); ?></span>
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
			<template x-if="fromQuestions && !showCourseContentsTree()">
				<div class="mint-flex mint-h-full mint-min-h-0 mint-flex-col">
					<div class="mint-builder-tree__head">
						<span x-text="isStandalone && !showSidebarQuizParent() ? '<?php echo esc_js( __( 'Question', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Contents', 'mint-lms' ) ); ?>'"></span>
					</div>

					<div class="mint-builder-tree__scroll mint-flex-1">
						<template x-if="sidebarHierarchyQuizzes().length === 0">
							<div class="mint-builder-tree__empty">
								<p><?php esc_html_e( 'No questions yet', 'mint-lms' ); ?></p>
								<button type="button" class="mint-builder-empty__cta" :disabled="addingCourseQuiz" @click="openLessonQuestionEditor()"><?php esc_html_e( 'Add first question', 'mint-lms' ); ?></button>
							</div>
						</template>

						<div class="mint-course-tree mint-tree--mint-blue">
							<template x-for="quiz in sidebarHierarchyQuizzes()" :key="'hq-' + quiz.id">
								<div class="mint-tree-branch">
									<a
										class="mint-tree-item mint-tree-item--course"
										x-show="breadcrumbCourseTitle()"
										:href="courseSettingsUrl()"
										@click="goToCourseSettings($event)"
									>
										<span class="mint-tree-item__icon mint-tree-item__icon--course" aria-hidden="true">
											<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
												<path d="M4 5.5c0-.8.7-1.5 1.5-1.5H10v12H5.5A1.5 1.5 0 0 1 4 14.5v-9Z"/>
												<path d="M16 5.5c0-.8-.7-1.5-1.5-1.5H10v12h4.5a1.5 1.5 0 0 0 1.5-1.5v-9Z"/>
											</svg>
										</span>
										<span class="mint-tree-item__body">
											<span class="mint-tree-item__kind"><?php esc_html_e( 'Course', 'mint-lms' ); ?></span>
											<span class="mint-tree-item__title mint-truncate" x-text="breadcrumbCourseTitle()"></span>
										</span>
									</a>

									<div class="mint-tree-branch__children" :class="(breadcrumbCourseTitle() || (showSidebarLessonParent() && isLinkedCourseQuiz(quiz))) ? '' : 'mint-tree-branch__children--root'">
										<a
											class="mint-tree-item mint-tree-item--lesson"
											x-show="showSidebarLessonParent() && isLinkedCourseQuiz(quiz)"
											:href="parentLessonEditorUrlFor(quiz.lessonId)"
											@click="goToQuizParentLesson(quiz, $event)"
										>
											<span class="mint-tree-item__icon mint-tree-item__icon--lesson" aria-hidden="true">
												<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
													<rect x="3.5" y="3.5" width="13" height="13" rx="2.5"/>
													<path d="M6.5 8h7M6.5 11.5h4.5"/>
												</svg>
											</span>
											<span class="mint-tree-item__body">
												<span class="mint-tree-item__kind"><?php esc_html_e( 'Lesson', 'mint-lms' ); ?></span>
												<span class="mint-tree-item__title mint-truncate" x-text="quizParentLessonTitle(quiz) || '<?php echo esc_js( __( 'Lesson', 'mint-lms' ) ); ?>'"></span>
											</span>
										</a>

										<div class="mint-tree-branch__children" :class="((showSidebarLessonParent() && isLinkedCourseQuiz(quiz)) || (showSidebarQuizParent() && isLinkedCourseQuiz(quiz))) ? '' : 'mint-tree-branch__children--root'">
											<a
												class="mint-tree-item mint-tree-item--quiz"
												x-show="showSidebarQuizParent() && isLinkedCourseQuiz(quiz)"
												:href="parentQuizEditorUrlFor(quiz.lessonId, quiz.id)"
												@click="goToQuestionParentQuiz({ lessonId: quiz.lessonId, quizId: quiz.id, sectionId: quiz.sectionId }, $event)"
											>
												<span class="mint-tree-item__icon mint-tree-item__icon--quiz" aria-hidden="true">
													<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
														<circle cx="10" cy="10" r="7"/>
														<path d="M7.5 10.2 8.9 11.6 12.6 8"/>
													</svg>
												</span>
												<span class="mint-tree-item__body">
													<span class="mint-tree-item__kind"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></span>
													<span class="mint-tree-item__title mint-truncate" x-text="quiz.title || '<?php echo esc_js( __( 'New Quiz', 'mint-lms' ) ); ?>'"></span>
												</span>
											</a>

											<div class="mint-tree-branch__children" :class="showSidebarQuizParent() ? '' : 'mint-tree-branch__children--root'" x-show="sidebarQuestionsForQuizFiltered(quiz).length">
												<template x-for="question in sidebarQuestionsForQuizFiltered(quiz)" :key="'hq-q-' + question.id">
													<button
														type="button"
														class="mint-tree-item mint-tree-item--question"
														:class="isCourseQuestionSelected(question) ? 'is-active' : ''"
														@click="openSidebarQuestion(question, quiz)"
													>
														<span class="mint-tree-item__icon mint-tree-item__icon--question" aria-hidden="true">
															<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
																<circle cx="10" cy="10" r="7"/>
																<path d="M8 7.8c.3-1.1 1.2-1.8 2.2-1.8 1.2 0 2.1.8 2.1 1.9 0 .9-.5 1.4-1.3 1.9-.6.3-.9.7-.9 1.4"/>
																<circle cx="10" cy="14.1" r="0.85" fill="currentColor" stroke="none"/>
															</svg>
														</span>
														<span class="mint-tree-item__body">
															<span class="mint-tree-item__kind"><?php esc_html_e( 'Question', 'mint-lms' ); ?></span>
															<span class="mint-tree-item__title mint-truncate" x-text="question.title || '<?php echo esc_js( __( 'New Question', 'mint-lms' ) ); ?>'"></span>
														</span>
													</button>
												</template>
											</div>
										</div>
									</div>
								</div>
							</template>
						</div>
					</div>

					<div class="mint-builder-tree__footer">
						<button
							type="button"
							class="mint-tree-add-section mint-w-full"
							:disabled="addingCourseQuiz"
							@click="openLessonQuestionEditor()"
						>
							<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
							<?php esc_html_e( 'Add Question', 'mint-lms' ); ?>
						</button>
						<div
							class="mint-mt-3 mint-assoc-card"
							x-show="isStandalone && !showSidebarQuizParent() && (activeQuestion?.id || selected?.questionId)"
							x-cloak
						>
							<h2 class="mint-assoc-card__title"><?php esc_html_e( 'Add to a quiz', 'mint-lms' ); ?></h2>
							<p class="mint-assoc-card__copy"><?php esc_html_e( 'Attach this question to an existing quiz.', 'mint-lms' ); ?></p>
							<label class="mint-assoc-card__label"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></label>
														<div
								class="mint-assoc-select"
								:class="open ? 'is-open' : ''"
								x-data="{ open: false }"
								@click.outside="open = false"
							>
								<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
									<span
										class="mint-assoc-select__value"
										:class="!attach.quizId ? 'is-placeholder' : ''"
										x-text="attachQuizLabel() || 'Select a quiz…'"
									></span>
									<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
								</button>
								<div class="mint-assoc-select__menu" x-show="open" x-cloak>
									<button
										type="button"
										class="mint-assoc-select__option"
										:class="!attach.quizId ? 'is-active' : ''"
										@click="pickAttachQuiz(0); open = false"
									><?php esc_html_e( 'Select a quiz…', 'mint-lms' ); ?></button>
									<template x-for="q in attachQuizzes" :key="q.id">
										<button
											type="button"
											class="mint-assoc-select__option"
											:class="Number(attach.quizId) === Number(q.id) ? 'is-active' : ''"
											@click="pickAttachQuiz(q.id); open = false"
											x-text="q.title"
										></button>
									</template>
								</div>
							</div>
							<button
								type="button"
								class="mint-builder-btn-publish"
								@click="attachStandaloneQuestionToQuiz()"
								:disabled="!attach.quizId || attaching"
							>
								<span x-text="attaching ? '<?php echo esc_js( __( 'Adding…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Add to quiz', 'mint-lms' ) ); ?>'"></span>
							</button>
						</div>
						<div
							class="mint-mt-3 mint-assoc-card"
							x-show="showSidebarQuizParent() && (activeQuestion?.id || selected?.questionId)"
							x-cloak
						>
							<div class="mint-assoc-card__badge"><?php esc_html_e( 'Linked quiz', 'mint-lms' ); ?></div>
							<div class="mint-assoc-card__current mint-truncate" x-text="breadcrumbQuizLabel() || '<?php echo esc_js( __( 'Quiz', 'mint-lms' ) ); ?>'"></div>
							<template x-if="!attach.changing">
								<div class="mint-assoc-card__actions">
									<button type="button" class="mint-lesson-choose-file" :disabled="attaching" @click="startChangeQuiz()"><?php esc_html_e( 'Change', 'mint-lms' ); ?></button>
									<button type="button" class="mint-assoc-card__remove" :disabled="attaching" @click="detachQuestionFromQuiz()"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
								</div>
							</template>
							<template x-if="attach.changing">
								<div class="mint-mt-3">
									<label class="mint-assoc-card__label"><?php esc_html_e( 'New quiz', 'mint-lms' ); ?></label>
																		<div
										class="mint-assoc-select"
										:class="open ? 'is-open' : ''"
										x-data="{ open: false }"
										@click.outside="open = false"
									>
										<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
											<span
												class="mint-assoc-select__value"
												:class="!attach.quizId ? 'is-placeholder' : ''"
												x-text="attachQuizLabel() || 'Select a quiz…'"
											></span>
											<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
										</button>
										<div class="mint-assoc-select__menu" x-show="open" x-cloak>
											<button
												type="button"
												class="mint-assoc-select__option"
												:class="!attach.quizId ? 'is-active' : ''"
												@click="pickAttachQuiz(0); open = false"
											><?php esc_html_e( 'Select a quiz…', 'mint-lms' ); ?></button>
											<template x-for="q in attachQuizzes" :key="'chg-q-' + q.id">
												<button
													type="button"
													class="mint-assoc-select__option"
													:class="Number(attach.quizId) === Number(q.id) ? 'is-active' : ''"
													@click="pickAttachQuiz(q.id); open = false"
													x-text="q.title"
												></button>
											</template>
										</div>
									</div>
									<div class="mint-assoc-card__actions">
										<button type="button" class="mint-builder-btn-publish" :disabled="!attach.quizId || attaching" @click="attachStandaloneQuestionToQuiz()">
											<span x-text="attaching ? '<?php echo esc_js( __( 'Saving…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Save', 'mint-lms' ) ); ?>'"></span>
										</button>
										<button type="button" class="mint-assoc-card__cancel" :disabled="attaching" @click="cancelChangeAssociation()"><?php esc_html_e( 'Cancel', 'mint-lms' ); ?></button>
									</div>
								</div>
							</template>
						</div>
					</div>
				</div>
			</template>

			<template x-if="fromQuizzes && !fromQuestions && !showCourseContentsTree()">
				<div class="mint-flex mint-h-full mint-min-h-0 mint-flex-col">
					<div class="mint-builder-tree__head">
						<span x-text="isStandalone && !showSidebarLessonParent() ? '<?php echo esc_js( __( 'Quiz', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Contents', 'mint-lms' ) ); ?>'"></span>
					</div>

					<div class="mint-builder-tree__scroll mint-flex-1">
						<template x-if="filteredCourseQuizzes.length === 0">
							<div class="mint-builder-tree__empty">
								<p><?php esc_html_e( 'No quizzes yet', 'mint-lms' ); ?></p>
								<button
									type="button"
									class="mint-builder-empty__cta"
									x-show="!isStandalone"
									:disabled="addingCourseQuiz"
									@click="addCourseQuiz()"
								><?php esc_html_e( 'Add first quiz', 'mint-lms' ); ?></button>
							</div>
						</template>

						<div class="mint-course-tree mint-tree--mint-blue">
							<template x-for="quiz in filteredCourseQuizzes" :key="quiz.id">
								<div class="mint-tree-branch">
									<a
										class="mint-tree-item mint-tree-item--course"
										x-show="breadcrumbCourseTitle()"
										:href="courseSettingsUrl()"
										@click="goToCourseSettings($event)"
									>
										<span class="mint-tree-item__icon mint-tree-item__icon--course" aria-hidden="true">
											<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
												<path d="M4 5.5c0-.8.7-1.5 1.5-1.5H10v12H5.5A1.5 1.5 0 0 1 4 14.5v-9Z"/>
												<path d="M16 5.5c0-.8-.7-1.5-1.5-1.5H10v12h4.5a1.5 1.5 0 0 0 1.5-1.5v-9Z"/>
											</svg>
										</span>
										<span class="mint-tree-item__body">
											<span class="mint-tree-item__kind"><?php esc_html_e( 'Course', 'mint-lms' ); ?></span>
											<span class="mint-tree-item__title mint-truncate" x-text="breadcrumbCourseTitle()"></span>
										</span>
									</a>

									<div class="mint-tree-branch__children" :class="(breadcrumbCourseTitle() || (showSidebarLessonParent() && isLinkedCourseQuiz(quiz))) ? '' : 'mint-tree-branch__children--root'">
										<a
											class="mint-tree-item mint-tree-item--lesson"
											x-show="showSidebarLessonParent() && isLinkedCourseQuiz(quiz)"
											:href="parentLessonEditorUrlFor(quiz.lessonId)"
											@click="goToQuizParentLesson(quiz, $event)"
										>
											<span class="mint-tree-item__icon mint-tree-item__icon--lesson" aria-hidden="true">
												<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
													<rect x="3.5" y="3.5" width="13" height="13" rx="2.5"/>
													<path d="M6.5 8h7M6.5 11.5h4.5"/>
												</svg>
											</span>
											<span class="mint-tree-item__body">
												<span class="mint-tree-item__kind"><?php esc_html_e( 'Lesson', 'mint-lms' ); ?></span>
												<span class="mint-tree-item__title mint-truncate" x-text="quizParentLessonTitle(quiz) || '<?php echo esc_js( __( 'Lesson', 'mint-lms' ) ); ?>'"></span>
											</span>
										</a>

										<div class="mint-tree-branch__children" :class="(showSidebarLessonParent() && isLinkedCourseQuiz(quiz)) ? '' : 'mint-tree-branch__children--root'">
											<button
												type="button"
												class="mint-tree-item mint-tree-item--quiz"
												:class="isCourseQuizSelected(quiz) ? 'is-active' : ''"
												@click="selectQuiz(quiz.lessonId, quiz.sectionId || null, quiz.id)"
											>
												<span class="mint-tree-item__icon mint-tree-item__icon--quiz" aria-hidden="true">
													<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
														<circle cx="10" cy="10" r="7"/>
														<path d="M7.5 10.2 8.9 11.6 12.6 8"/>
													</svg>
												</span>
												<span class="mint-tree-item__body">
													<span class="mint-tree-item__kind"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></span>
													<span class="mint-tree-item__title mint-truncate" x-text="quiz.title || '<?php echo esc_js( __( 'New Quiz', 'mint-lms' ) ); ?>'"></span>
												</span>
											</button>

											<div class="mint-tree-branch__children" x-show="(fromBuilderOrigin || isCourseQuizSelected(quiz)) && sidebarQuestionsForQuiz(quiz).length">
												<template x-for="question in sidebarQuestionsForQuiz(quiz)" :key="'qq-' + question.id">
													<button
														type="button"
														class="mint-tree-item mint-tree-item--question"
														:class="isCourseQuestionSelected(question) ? 'is-active' : ''"
														@click.stop="openSidebarQuestion(question, quiz)"
													>
														<span class="mint-tree-item__icon mint-tree-item__icon--question" aria-hidden="true">
															<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
																<circle cx="10" cy="10" r="7"/>
																<path d="M8 7.8c.3-1.1 1.2-1.8 2.2-1.8 1.2 0 2.1.8 2.1 1.9 0 .9-.5 1.4-1.3 1.9-.6.3-.9.7-.9 1.4"/>
																<circle cx="10" cy="14.1" r="0.85" fill="currentColor" stroke="none"/>
															</svg>
														</span>
														<span class="mint-tree-item__body">
															<span class="mint-tree-item__kind"><?php esc_html_e( 'Question', 'mint-lms' ); ?></span>
															<span class="mint-tree-item__title mint-truncate" x-text="question.title || '<?php echo esc_js( __( 'New Question', 'mint-lms' ) ); ?>'"></span>
														</span>
													</button>
												</template>
											</div>
										</div>
									</div>
								</div>
							</template>
						</div>
					</div>

					<div class="mint-builder-tree__footer">
						<button
							type="button"
							class="mint-tree-add-section mint-w-full"
							x-show="selected?.type === 'quiz' && lessonQuiz?.id"
							x-cloak
							@click="openLessonQuestionEditor()"
						>
							<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
							<?php esc_html_e( 'Add Question', 'mint-lms' ); ?>
						</button>
						<button
							type="button"
							class="mint-tree-add-section mint-w-full mint-mt-2"
							x-show="!isStandalone && !fromBuilderOrigin"
							:disabled="addingCourseQuiz"
							@click="addCourseQuiz()"
						>
							<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
							<?php esc_html_e( 'Add Quiz', 'mint-lms' ); ?>
						</button>
						<div
							class="mint-mt-3 mint-assoc-card"
							x-show="isStandalone && !showSidebarLessonParent() && lessonQuiz?.id"
							x-cloak
						>
							<h2 class="mint-assoc-card__title"><?php esc_html_e( 'Add to a lesson', 'mint-lms' ); ?></h2>
							<p class="mint-assoc-card__copy"><?php esc_html_e( 'Attach this quiz to an existing lesson.', 'mint-lms' ); ?></p>
							<label class="mint-assoc-card__label"><?php esc_html_e( 'Lesson', 'mint-lms' ); ?></label>
														<div
								class="mint-assoc-select"
								:class="open ? 'is-open' : ''"
								x-data="{ open: false }"
								@click.outside="open = false"
							>
								<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
									<span
										class="mint-assoc-select__value"
										:class="!attach.lessonId ? 'is-placeholder' : ''"
										x-text="attachLessonLabel() || 'Select a lesson…'"
									></span>
									<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
								</button>
								<div class="mint-assoc-select__menu" x-show="open" x-cloak>
									<button
										type="button"
										class="mint-assoc-select__option"
										:class="!attach.lessonId ? 'is-active' : ''"
										@click="pickAttachLesson(0); open = false"
									><?php esc_html_e( 'Select a lesson…', 'mint-lms' ); ?></button>
									<template x-for="l in attachLessons" :key="l.id">
										<button
											type="button"
											class="mint-assoc-select__option"
											:class="{
												'is-active': Number(attach.lessonId) === Number(l.id),
												'is-disabled': !!l.taken
											}"
											:disabled="!!l.taken"
											@click="pickAttachLesson(l.id); open = false"
											x-text="l.taken ? (l.title + ' (has quiz)') : l.title"										></button>
									</template>
								</div>
							</div>
							<button
								type="button"
								class="mint-builder-btn-publish"
								@click="attachStandaloneQuizToLesson()"
								:disabled="!attach.lessonId || attaching"
							>
								<span x-text="attaching ? '<?php echo esc_js( __( 'Adding…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Add to lesson', 'mint-lms' ) ); ?>'"></span>
							</button>
						</div>
						<div
							class="mint-mt-3 mint-assoc-card"
							x-show="showSidebarLessonParent() && lessonQuiz?.id"
							x-cloak
						>
							<div class="mint-assoc-card__badge"><?php esc_html_e( 'Linked lesson', 'mint-lms' ); ?></div>
							<div class="mint-assoc-card__current mint-truncate" x-text="breadcrumbParentLessonTitle() || breadcrumbQuizContextTitle() || '<?php echo esc_js( __( 'Lesson', 'mint-lms' ) ); ?>'"></div>
							<template x-if="!attach.changing">
								<div class="mint-assoc-card__actions">
									<button type="button" class="mint-lesson-choose-file" :disabled="attaching" @click="startChangeLesson()"><?php esc_html_e( 'Change', 'mint-lms' ); ?></button>
									<button type="button" class="mint-assoc-card__remove" :disabled="attaching" @click="detachQuizFromLesson()"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
								</div>
							</template>
							<template x-if="attach.changing">
								<div class="mint-mt-3">
									<label class="mint-assoc-card__label"><?php esc_html_e( 'New lesson', 'mint-lms' ); ?></label>
																		<div
										class="mint-assoc-select"
										:class="open ? 'is-open' : ''"
										x-data="{ open: false }"
										@click.outside="open = false"
									>
										<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
											<span
												class="mint-assoc-select__value"
												:class="!attach.lessonId ? 'is-placeholder' : ''"
												x-text="attachLessonLabel() || 'Select a lesson…'"
											></span>
											<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
										</button>
										<div class="mint-assoc-select__menu" x-show="open" x-cloak>
											<button
												type="button"
												class="mint-assoc-select__option"
												:class="!attach.lessonId ? 'is-active' : ''"
												@click="pickAttachLesson(0); open = false"
											><?php esc_html_e( 'Select a lesson…', 'mint-lms' ); ?></button>
											<template x-for="l in attachLessons" :key="'chg-l-' + l.id">
												<button
													type="button"
													class="mint-assoc-select__option"
													:class="{
														'is-active': Number(attach.lessonId) === Number(l.id),
														'is-disabled': !!l.taken
													}"
													:disabled="!!l.taken"
													@click="pickAttachLesson(l.id); open = false"
													x-text="l.taken ? (l.title + ' (has quiz)') : l.title"												></button>
											</template>
										</div>
									</div>
									<div class="mint-assoc-card__actions">
										<button type="button" class="mint-builder-btn-publish" :disabled="!attach.lessonId || attaching" @click="attachStandaloneQuizToLesson()">
											<span x-text="attaching ? '<?php echo esc_js( __( 'Saving…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Save', 'mint-lms' ) ); ?>'"></span>
										</button>
										<button type="button" class="mint-assoc-card__cancel" :disabled="attaching" @click="cancelChangeAssociation()"><?php esc_html_e( 'Cancel', 'mint-lms' ); ?></button>
									</div>
								</div>
							</template>
						</div>
					</div>
				</div>
			</template>

			<template x-if="showCourseContentsTree() || (!fromQuizzes && !fromQuestions)">
				<div class="mint-flex mint-h-full mint-min-h-0 mint-flex-col">
			<div class="mint-builder-tree__head">
				<span x-text="isStandalone ? '<?php echo esc_js( __( 'Lesson', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Contents', 'mint-lms' ) ); ?>'"></span>
				<span class="mint-builder-tree__head-count" x-show="!isStandalone" x-text="contentsSummaryLabel()"></span>
			</div>

			<div class="mint-builder-tree__search" x-show="!isStandalone" x-cloak>
				<label class="mint-builder-tree__search-field">
					<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<circle cx="9" cy="9" r="5.5"/>
						<path d="m13.5 13.5 3 3"/>
					</svg>
					<input
						type="search"
						class="mint-builder-tree__search-input"
						x-model="contentsSearch"
						:placeholder="contentsSearchPlaceholder()"
						:aria-label="contentsSearchPlaceholder()"
						autocomplete="off"
					/>
				</label>
			</div>

			<div class="mint-builder-tree__scroll">
				<div class="mint-course-tree mint-tree--mint-blue" x-show="!isStandalone" x-cloak>
					<a
						class="mint-tree-item mint-tree-item--course"
						:href="courseSettingsUrl()"
						@click="goToCourseSettings($event)"
					>
						<span class="mint-tree-item__icon mint-tree-item__icon--course" aria-hidden="true">
							<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
								<path d="M4 5.5c0-.8.7-1.5 1.5-1.5H10v12H5.5A1.5 1.5 0 0 1 4 14.5v-9Z"/>
								<path d="M16 5.5c0-.8-.7-1.5-1.5-1.5H10v12h4.5a1.5 1.5 0 0 0 1.5-1.5v-9Z"/>
							</svg>
						</span>
						<span class="mint-tree-item__body">
							<span class="mint-tree-item__kind"><?php esc_html_e( 'Course', 'mint-lms' ); ?></span>
							<span class="mint-tree-item__title mint-truncate" x-text="breadcrumbCourseTitle() || '<?php echo esc_js( __( 'Untitled course', 'mint-lms' ) ); ?>'"></span>
						</span>
					</a>

					<div class="mint-course-tree__sections" id="mint-sections-sortable">
						<template x-if="filteredContentsSections().length === 0">
							<div class="mint-builder-tree__empty">
								<p x-text="contentsSearchQuery() ? contentsSearchEmptyLabel() : '<?php echo esc_js( __( 'No content yet', 'mint-lms' ) ); ?>'"></p>
								<button type="button" class="mint-builder-empty__cta" x-show="!contentsSearchQuery()" :disabled="addingLesson" @click="createCourseLesson()"><?php esc_html_e( 'Add a lesson', 'mint-lms' ); ?></button>								<button type="button" class="mint-tree-add-section mint-mt-3 mint-w-full" x-show="!contentsSearchQuery()" :disabled="addingSection" @click="addSection()"><?php esc_html_e( 'Add section', 'mint-lms' ); ?></button>
							</div>
						</template>

						<template x-for="(section, sIndex) in filteredContentsSections()" :key="'course-sec-' + section.id">
							<div
								:data-section-id="Number(section.id) > 0 ? section.id : false"
								:data-ungrouped="Number(section.id) === 0 ? '1' : false"
								class="mint-tree-branch"
								:class="Number(section.id) === 0 ? 'mint-tree-branch--ungrouped' : ''"
							>
								<div
									x-show="Number(section.id) > 0"
									class="mint-tree-item mint-tree-item--section"
									:class="isSelected('section', section.id) ? 'is-active' : ''"
									@click="selectItem('section', section.id)"
								>
									<span class="mint-handle-section" title="<?php echo esc_attr__( 'Drag to reorder', 'mint-lms' ); ?>">
										<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
									</span>
									<button
										type="button"
										class="mint-builder-tree__toggle"
										@click.stop="toggleSection(section.id)"
										:aria-expanded="isSectionVisibleInTree(section.id)"
									>
										<svg
											width="16"
											height="16"
											viewBox="0 0 20 20"
											fill="none"
											stroke="currentColor"
											stroke-width="2.1"
											stroke-linecap="round"
											stroke-linejoin="round"
											class="mint-transition-transform"
											:class="isSectionVisibleInTree(section.id) ? '' : 'mint--rotate-90'"
											aria-hidden="true"
										><path d="M5.5 8 10 12.5 14.5 8"/></svg>
									</button>
									<span class="mint-tree-item__icon mint-tree-item__icon--section" aria-hidden="true">
										<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
											<path d="M4 6.5h12M4 10h12M4 13.5h8"/>
										</svg>
									</span>
									<span class="mint-tree-item__body mint-min-w-0 mint-flex-1" @click.stop>
										<template x-if="editingKey !== 'section-' + section.id">
											<span
												class="mint-tree-item__title mint-truncate"
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
									</span>
									<span class="mint-tree-item__meta" x-text="sectionChildCountLabel(section)"></span>
								</div>

								<div
									x-show="isSectionVisibleInTree(section.id)"
									class="mint-lessons-sortable mint-tree-branch__children"
									:data-section-id="section.id"
								>
									<template x-for="(lesson, lIndex) in filteredLessonsForSection(section)" :key="lesson.id">
										<div :data-lesson-id="lesson.id" class="mint-tree-branch mint-tree-branch--lesson">
											<div
												class="mint-tree-item mint-tree-item--lesson"
												:class="isSelected('lesson', lesson.id) ? 'is-active' : ''"
												@click="selectItem('lesson', lesson.id, section.id)"
											>
												<span class="mint-handle-lesson" title="<?php echo esc_attr__( 'Drag to reorder', 'mint-lms' ); ?>">
													<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
												</span>
												<button
													type="button"
													class="mint-builder-tree__toggle"
													@click.stop="toggleLesson(lesson.id)"
													:aria-expanded="isLessonVisibleInTree(lesson.id)"
												>
													<svg
														width="16"
														height="16"
														viewBox="0 0 20 20"
														fill="none"
														stroke="currentColor"
														stroke-width="2.1"
														stroke-linecap="round"
														stroke-linejoin="round"
														class="mint-transition-transform"
														:class="isLessonVisibleInTree(lesson.id) ? '' : 'mint--rotate-90'"
														aria-hidden="true"
													><path d="M5.5 8 10 12.5 14.5 8"/></svg>
												</button>
												<span
													class="mint-tree-item__icon mint-tree-item__icon--lesson"
													:class="lesson.videoUrl ? 'is-video' : ''"
													aria-hidden="true"
												>
													<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
														<rect x="3.5" y="3.5" width="13" height="13" rx="2.5"/>
														<path x-show="!lesson.videoUrl" d="M6.5 8h7M6.5 11.5h4.5"/>
														<path x-show="lesson.videoUrl" d="m8.5 7.5 4 2.5-4 2.5z"/>
													</svg>
												</span>
												<span class="mint-tree-item__body mint-min-w-0 mint-flex-1" @click.stop>
													<template x-if="editingKey !== 'lesson-' + lesson.id">
														<span
															class="mint-tree-item__title mint-truncate"
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
												</span>
												<span class="mint-tree-item__meta" x-text="lessonChildCountLabel(lesson)"></span>
											</div>

											<template x-if="isLessonVisibleInTree(lesson.id) && filteredQuizzesForLesson(lesson).length">
												<div class="mint-tree-branch__children">
													<template x-for="quiz in filteredQuizzesForLesson(lesson)" :key="'lq-' + quiz.id">
														<div class="mint-tree-branch">
															<div
																class="mint-tree-item mint-tree-item--quiz"
																:class="isCourseQuizSelected(quiz) ? 'is-active' : ''"
																@click="openSidebarQuiz(quiz, lesson, section.id)"
															>
																<button
																	type="button"
																	class="mint-builder-tree__toggle"
																	@click.stop="toggleQuiz(quiz.id)"
																	:aria-expanded="isQuizVisibleInTree(quiz.id)"
																>
																	<svg
																		width="16"
																		height="16"
																		viewBox="0 0 20 20"
																		fill="none"
																		stroke="currentColor"
																		stroke-width="2.1"
																		stroke-linecap="round"
																		stroke-linejoin="round"
																		class="mint-transition-transform"
																		:class="isQuizVisibleInTree(quiz.id) ? '' : 'mint--rotate-90'"
																		aria-hidden="true"
																	><path d="M5.5 8 10 12.5 14.5 8"/></svg>
																</button>
																<span class="mint-tree-item__icon mint-tree-item__icon--quiz" aria-hidden="true">
																	<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
																		<path d="M5 10.5 8.5 14 15 6.5"/>
																	</svg>
																</span>
																<span class="mint-tree-item__body mint-min-w-0 mint-flex-1">
																	<span class="mint-tree-item__title mint-truncate" x-text="quiz.title || '<?php echo esc_js( __( 'New Quiz', 'mint-lms' ) ); ?>'"></span>
																</span>
																<span class="mint-tree-item__meta" x-text="quizChildCountLabel(quiz)"></span>
															</div>
															<div class="mint-tree-branch__children" x-show="isQuizVisibleInTree(quiz.id) && filteredQuestionsForQuiz(quiz).length">
																<template x-for="question in filteredQuestionsForQuiz(quiz)" :key="'lq-q-' + question.id">
																	<button
																		type="button"
																		class="mint-tree-item mint-tree-item--question"
																		:class="isCourseQuestionSelected(question) ? 'is-active' : ''"
																		@click.stop="openSidebarQuestion(question, quiz)"
																	>
																		<span class="mint-tree-item__icon mint-tree-item__icon--question" aria-hidden="true">
																			<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
																				<path d="M7.6 7.4c.35-1.15 1.25-1.9 2.4-1.9 1.35 0 2.35.9 2.35 2.15 0 1.05-.55 1.6-1.45 2.15-.7.4-1 .8-1 1.55"/>
																				<circle cx="10" cy="14.4" r="0.9" fill="currentColor" stroke="none"/>
																			</svg>
																		</span>
																		<span class="mint-tree-item__body mint-min-w-0 mint-flex-1">
																			<span class="mint-tree-item__title mint-truncate" x-text="question.title || '<?php echo esc_js( __( 'New Question', 'mint-lms' ) ); ?>'"></span>
																		</span>
																	</button>
																</template>
															</div>
														</div>
													</template>
												</div>
											</template>
										</div>
									</template>

									<button
										type="button"
										class="mint-tree-add-lesson"
										:disabled="addingLesson && !quizEditorActive"
										@click="onTreeAddLesson(section.id)"
									>
										<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
										<span x-text="treeAddLessonLabel()"></span>
									</button>
								</div>
							</div>
						</template>
					</div>
				</div>

				<template x-if="isStandalone">
					<div class="mint-course-tree mint-tree--mint-blue">
						<template x-if="sections.length === 0">
							<div class="mint-builder-tree__empty">
								<p><?php esc_html_e( 'No lessons yet', 'mint-lms' ); ?></p>
							</div>
						</template>
						<template x-for="(section, sIndex) in sections" :key="'standalone-sec-' + section.id">
							<div :data-section-id="section.id">
								<template x-for="(lesson, lIndex) in section.lessons" :key="lesson.id">
									<div :data-lesson-id="lesson.id" class="mint-tree-branch">
										<div
											class="mint-tree-item mint-tree-item--lesson"
											:class="isSelected('lesson', lesson.id) ? 'is-active' : ''"
											@click="selectItem('lesson', lesson.id, section.id)"
										>
											<button
												type="button"
												class="mint-builder-tree__toggle"
												@click.stop="toggleLesson(lesson.id)"
												:aria-expanded="isLessonExpanded(lesson.id)"
											>
												<svg
													width="16"
													height="16"
													viewBox="0 0 20 20"
													fill="none"
													stroke="currentColor"
													stroke-width="2.1"
													stroke-linecap="round"
													stroke-linejoin="round"
													class="mint-transition-transform"
													:class="isLessonExpanded(lesson.id) ? '' : 'mint--rotate-90'"
													aria-hidden="true"
												><path d="M5.5 8 10 12.5 14.5 8"/></svg>
											</button>
											<span
												class="mint-tree-item__icon mint-tree-item__icon--lesson"
												:class="lesson.videoUrl ? 'is-video' : ''"
												aria-hidden="true"
											>
												<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
													<rect x="3.5" y="3.5" width="13" height="13" rx="2.5"/>
													<path x-show="!lesson.videoUrl" d="M6.5 8h7M6.5 11.5h4.5"/>
													<path x-show="lesson.videoUrl" d="m8.5 7.5 4 2.5-4 2.5z"/>
												</svg>
											</span>
											<span class="mint-tree-item__body mint-min-w-0 mint-flex-1" @click.stop>
												<template x-if="editingKey !== 'lesson-' + lesson.id">
													<span
														class="mint-tree-item__title mint-truncate"
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
											</span>
											<span class="mint-tree-item__meta" x-text="lessonChildCountLabel(lesson)"></span>
										</div>

										<div
											class="mint-tree-branch__children"
											x-show="isLessonExpanded(lesson.id) && sidebarQuizzesForLesson(lesson).length"
										>
											<template x-for="quiz in sidebarQuizzesForLesson(lesson)" :key="'slq-' + quiz.id">
												<div class="mint-tree-branch">
													<div
														class="mint-tree-item mint-tree-item--quiz"
														:class="isCourseQuizSelected(quiz) ? 'is-active' : ''"
														@click="openSidebarQuiz(quiz, lesson, section.id)"
													>
														<button
															type="button"
															class="mint-builder-tree__toggle"
															@click.stop="toggleQuiz(quiz.id)"
															:aria-expanded="isQuizExpanded(quiz.id)"
														>
															<svg
																width="16"
																height="16"
																viewBox="0 0 20 20"
																fill="none"
																stroke="currentColor"
																stroke-width="2.1"
																stroke-linecap="round"
																stroke-linejoin="round"
																class="mint-transition-transform"
																:class="isQuizExpanded(quiz.id) ? '' : 'mint--rotate-90'"
																aria-hidden="true"
															><path d="M5.5 8 10 12.5 14.5 8"/></svg>
														</button>
														<span class="mint-tree-item__icon mint-tree-item__icon--quiz" aria-hidden="true">
															<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
																<path d="M5 10.5 8.5 14 15 6.5"/>
															</svg>
														</span>
														<span class="mint-tree-item__body mint-min-w-0 mint-flex-1">
															<span class="mint-tree-item__title mint-truncate" x-text="quiz.title || '<?php echo esc_js( __( 'New Quiz', 'mint-lms' ) ); ?>'"></span>
														</span>
														<span class="mint-tree-item__meta" x-text="quizChildCountLabel(quiz)"></span>
													</div>

													<div class="mint-tree-branch__children" x-show="isQuizExpanded(quiz.id) && sidebarQuestionsForQuiz(quiz).length">
														<template x-for="question in sidebarQuestionsForQuiz(quiz)" :key="'slq-q-' + question.id">
															<button
																type="button"
																class="mint-tree-item mint-tree-item--question"
																:class="isCourseQuestionSelected(question) ? 'is-active' : ''"
																@click.stop="openSidebarQuestion(question, quiz)"
															>
																<span class="mint-tree-item__icon mint-tree-item__icon--question" aria-hidden="true">
																	<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
																		<path d="M7.6 7.4c.35-1.15 1.25-1.9 2.4-1.9 1.35 0 2.35.9 2.35 2.15 0 1.05-.55 1.6-1.45 2.15-.7.4-1 .8-1 1.55"/>
																		<circle cx="10" cy="14.4" r="0.9" fill="currentColor" stroke="none"/>
																	</svg>
																</span>
																<span class="mint-tree-item__body mint-min-w-0 mint-flex-1">
																	<span class="mint-tree-item__title mint-truncate" x-text="question.title || '<?php echo esc_js( __( 'New Question', 'mint-lms' ) ); ?>'"></span>
																</span>
															</button>
														</template>
													</div>
												</div>
											</template>
										</div>
									</div>
								</template>
							</div>
						</template>
					</div>
				</template>
			</div>

			<div class="mint-builder-tree__footer mint-px-3 mint-pb-3" x-show="!isStandalone" x-cloak>
				<button
					type="button"
					class="mint-tree-add-section mint-mb-3 mint-w-full"
					x-show="(fromQuizzes || fromQuestions) && lessonQuiz?.id"
					x-cloak
					@click="openLessonQuestionEditor()"
				>
					<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
					<?php esc_html_e( 'Add Question', 'mint-lms' ); ?>
				</button>
				<div
					class="mint-assoc-card mint-mb-3"
					x-show="fromQuizzes && !fromQuestions && showSidebarLessonParent() && lessonQuiz?.id"
					x-cloak
				>
					<div class="mint-assoc-card__badge"><?php esc_html_e( 'Linked lesson', 'mint-lms' ); ?></div>
					<div class="mint-assoc-card__current mint-truncate" x-text="breadcrumbParentLessonTitle() || breadcrumbQuizContextTitle() || '<?php echo esc_js( __( 'Lesson', 'mint-lms' ) ); ?>'"></div>
					<div class="mint-assoc-card__actions" x-show="!attach.changing" x-cloak>
						<button type="button" class="mint-lesson-choose-file" :disabled="attaching" @click="startChangeLesson()"><?php esc_html_e( 'Change', 'mint-lms' ); ?></button>
						<button type="button" class="mint-assoc-card__remove" :disabled="attaching" @click="detachQuizFromLesson()"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
					</div>
					<div class="mint-mt-3" x-show="attach.changing" x-cloak>
						<label class="mint-assoc-card__label"><?php esc_html_e( 'New lesson', 'mint-lms' ); ?></label>
						<div
							class="mint-assoc-select"
							:class="open ? 'is-open' : ''"
							x-data="{ open: false }"
							@click.outside="open = false"
						>
							<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
								<span
									class="mint-assoc-select__value"
									:class="!attach.lessonId ? 'is-placeholder' : ''"
									x-text="attachLessonLabel() || 'Select a lesson…'"
								></span>
								<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
							</button>
							<div class="mint-assoc-select__menu" x-show="open" x-cloak>
								<button
									type="button"
									class="mint-assoc-select__option"
									:class="!attach.lessonId ? 'is-active' : ''"
									@click="pickAttachLesson(0); open = false"
								><?php esc_html_e( 'Select a lesson…', 'mint-lms' ); ?></button>
								<template x-for="l in attachLessons" :key="'tree-chg-l-' + l.id">
									<button
										type="button"
										class="mint-assoc-select__option"
										:class="{
											'is-active': Number(attach.lessonId) === Number(l.id),
											'is-disabled': !!l.taken
										}"
										:disabled="!!l.taken"
										@click="pickAttachLesson(l.id); open = false"
										x-text="l.taken ? (l.title + ' (has quiz)') : l.title"									></button>
								</template>
							</div>
						</div>
						<div class="mint-assoc-card__actions">
							<button type="button" class="mint-builder-btn-publish" :disabled="!attach.lessonId || attaching" @click="attachStandaloneQuizToLesson()">
								<span x-text="attaching ? '<?php echo esc_js( __( 'Saving…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Save', 'mint-lms' ) ); ?>'"></span>
							</button>
							<button type="button" class="mint-assoc-card__cancel" :disabled="attaching" @click="cancelChangeAssociation()"><?php esc_html_e( 'Cancel', 'mint-lms' ); ?></button>
						</div>
					</div>
				</div>
				<div
					class="mint-assoc-card mint-mb-3"
					x-show="fromQuestions && showSidebarQuizParent() && (activeQuestion?.id || selected?.questionId)"
					x-cloak
				>
					<div class="mint-assoc-card__badge"><?php esc_html_e( 'Linked quiz', 'mint-lms' ); ?></div>
					<div class="mint-assoc-card__current mint-truncate" x-text="breadcrumbQuizLabel() || '<?php echo esc_js( __( 'Quiz', 'mint-lms' ) ); ?>'"></div>
					<div class="mint-assoc-card__actions" x-show="!attach.changing" x-cloak>
						<button type="button" class="mint-lesson-choose-file" :disabled="attaching" @click="startChangeQuiz()"><?php esc_html_e( 'Change', 'mint-lms' ); ?></button>
						<button type="button" class="mint-assoc-card__remove" :disabled="attaching" @click="detachQuestionFromQuiz()"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
					</div>
					<div class="mint-mt-3" x-show="attach.changing" x-cloak>
						<label class="mint-assoc-card__label"><?php esc_html_e( 'New quiz', 'mint-lms' ); ?></label>
						<div
							class="mint-assoc-select"
							:class="open ? 'is-open' : ''"
							x-data="{ open: false }"
							@click.outside="open = false"
						>
							<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
								<span
									class="mint-assoc-select__value"
									:class="!attach.quizId ? 'is-placeholder' : ''"
									x-text="attachQuizLabel() || 'Select a quiz…'"
								></span>
								<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
							</button>
							<div class="mint-assoc-select__menu" x-show="open" x-cloak>
								<button
									type="button"
									class="mint-assoc-select__option"
									:class="!attach.quizId ? 'is-active' : ''"
									@click="pickAttachQuiz(0); open = false"
								><?php esc_html_e( 'Select a quiz…', 'mint-lms' ); ?></button>
								<template x-for="q in attachQuizzes" :key="'tree-chg-q-' + q.id">
									<button
										type="button"
										class="mint-assoc-select__option"
										:class="Number(attach.quizId) === Number(q.id) ? 'is-active' : ''"
										@click="pickAttachQuiz(q.id); open = false"
										x-text="q.title"
									></button>
								</template>
							</div>
						</div>
						<div class="mint-assoc-card__actions">
							<button type="button" class="mint-builder-btn-publish" :disabled="!attach.quizId || attaching" @click="attachStandaloneQuestionToQuiz()">
								<span x-text="attaching ? '<?php echo esc_js( __( 'Saving…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Save', 'mint-lms' ) ); ?>'"></span>
							</button>
							<button type="button" class="mint-assoc-card__cancel" :disabled="attaching" @click="cancelChangeAssociation()"><?php esc_html_e( 'Cancel', 'mint-lms' ); ?></button>
						</div>
					</div>
				</div>
				<div
					class="mint-assoc-card mint-mb-3"
					x-show="selected?.type === 'lesson' && selectedLesson?.id && !fromQuizzes"
				>
					<div class="mint-assoc-card__badge"><?php esc_html_e( 'Linked course', 'mint-lms' ); ?></div>
					<div class="mint-assoc-card__current mint-truncate" x-text="breadcrumbCourseTitle() || '<?php echo esc_js( __( 'Course', 'mint-lms' ) ); ?>'"></div>
					<template x-if="!attach.changing">
						<div class="mint-assoc-card__actions">
							<button type="button" class="mint-lesson-choose-file" :disabled="attaching" @click="startChangeCourse()"><?php esc_html_e( 'Change', 'mint-lms' ); ?></button>
							<button type="button" class="mint-assoc-card__remove" :disabled="attaching" @click="detachLessonFromCourse()"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
						</div>
					</template>
					<template x-if="attach.changing">
						<div class="mint-mt-3">
							<label class="mint-assoc-card__label"><?php esc_html_e( 'New course', 'mint-lms' ); ?></label>
														<div
								class="mint-assoc-select"
								:class="open ? 'is-open' : ''"
								x-data="{ open: false }"
								@click.outside="open = false"
							>
								<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
									<span
										class="mint-assoc-select__value"
										:class="!attach.courseId ? 'is-placeholder' : ''"
										x-text="attachCourseLabel() || 'Select a course…'"
									></span>
									<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
								</button>
								<div class="mint-assoc-select__menu" x-show="open" x-cloak>
									<button
										type="button"
										class="mint-assoc-select__option"
										:class="!attach.courseId ? 'is-active' : ''"
										@click="pickAttachCourse(0); open = false"
									><?php esc_html_e( 'Select a course…', 'mint-lms' ); ?></button>
									<template x-for="c in attachCourses" :key="'chg-c-' + c.id">
										<button
											type="button"
											class="mint-assoc-select__option"
											:class="Number(attach.courseId) === Number(c.id) ? 'is-active' : ''"
											@click="pickAttachCourse(c.id); open = false"
											x-text="c.title"
										></button>
									</template>
								</div>
							</div>
							<template x-if="attach.courseId">
								<div class="mint-mt-3">
									<label class="mint-assoc-card__label"><?php esc_html_e( 'Section', 'mint-lms' ); ?></label>
																		<div
										class="mint-assoc-select"
										:class="open ? 'is-open' : ''"
										x-data="{ open: false }"
										@click.outside="open = false"
									>
										<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
											<span
												class="mint-assoc-select__value"
												:class="false ? 'is-placeholder' : ''"
												x-text="attachSectionLabel() || '<?php echo esc_js( __( 'No section', 'mint-lms' ) ); ?>'"
											></span>
											<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
										</button>
										<div class="mint-assoc-select__menu" x-show="open" x-cloak>
											<button
												type="button"
												class="mint-assoc-select__option"
												:class="!attach.sectionId ? 'is-active' : ''"
												@click="pickAttachSection(0); open = false"
											><?php esc_html_e( 'No section', 'mint-lms' ); ?></button>											<template x-for="s in attachSections" :key="'chg-s-' + s.id">
												<button
													type="button"
													class="mint-assoc-select__option"
													:class="Number(attach.sectionId) === Number(s.id) ? 'is-active' : ''"
													@click="pickAttachSection(s.id); open = false"
													x-text="s.title"
												></button>
											</template>
										</div>
									</div>
								</div>
							</template>
							<div class="mint-assoc-card__actions">
								<button type="button" class="mint-builder-btn-publish" :disabled="!attach.courseId || attaching" @click="attachStandaloneToCourse()">
									<span x-text="attaching ? '<?php echo esc_js( __( 'Saving…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Save', 'mint-lms' ) ); ?>'"></span>
								</button>
								<button type="button" class="mint-assoc-card__cancel" :disabled="attaching" @click="cancelChangeAssociation()"><?php esc_html_e( 'Cancel', 'mint-lms' ); ?></button>
							</div>
						</div>
					</template>
				</div>
				<button
					type="button"
					class="mint-tree-add-section mint-w-full"
					x-show="!fromQuizzes && !quizEditorActive && !questionEditorActive"
					:disabled="addingLesson"
					@click="createCourseLesson()"
				>
					<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
					<span><?php esc_html_e( 'Add lesson', 'mint-lms' ); ?></span>
				</button>
				<button
					type="button"
					class="mint-tree-add-section mint-w-full"
					x-show="!fromQuizzes"
					:disabled="addingSection && !quizEditorActive"
					@click="onTreeAddSection()"
				>
					<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
					<span x-text="treeAddSectionLabel()"></span>
				</button>
			</div>

			<div class="mint-builder-tree__footer mint-px-3 mint-pb-3" x-show="isStandalone" x-cloak>
				<button
					type="button"
					class="mint-tree-add-section mint-mb-3 mint-w-full"
					x-show="selected?.type === 'lesson' && selectedLesson?.id"
					:disabled="quizSaving || addingCourseQuiz"
					@click="openLessonQuizEditor(selectedLesson.id, selected.sectionId)"
				>
					<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
					<?php esc_html_e( 'Add Quiz', 'mint-lms' ); ?>
				</button>
				<div class="mint-assoc-card">
					<h2 class="mint-assoc-card__title"><?php esc_html_e( 'Add to a course', 'mint-lms' ); ?></h2>
					<p class="mint-assoc-card__copy"><?php esc_html_e( 'Attach this lesson (and its quiz, if any) to a course. Section is optional.', 'mint-lms' ); ?></p>
					<label class="mint-assoc-card__label"><?php esc_html_e( 'Course', 'mint-lms' ); ?></label>
										<div
						class="mint-assoc-select"
						:class="open ? 'is-open' : ''"
						x-data="{ open: false }"
						@click.outside="open = false"
					>
						<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
							<span
								class="mint-assoc-select__value"
								:class="!attach.courseId ? 'is-placeholder' : ''"
								x-text="attachCourseLabel() || 'Select a course…'"
							></span>
							<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
						</button>
						<div class="mint-assoc-select__menu" x-show="open" x-cloak>
							<button
								type="button"
								class="mint-assoc-select__option"
								:class="!attach.courseId ? 'is-active' : ''"
								@click="pickAttachCourse(0); open = false"
							><?php esc_html_e( 'Select a course…', 'mint-lms' ); ?></button>
							<template x-for="c in attachCourses" :key="c.id">
								<button
									type="button"
									class="mint-assoc-select__option"
									:class="Number(attach.courseId) === Number(c.id) ? 'is-active' : ''"
									@click="pickAttachCourse(c.id); open = false"
									x-text="c.title"
								></button>
							</template>
						</div>
					</div>
					<template x-if="attach.courseId">
						<div class="mint-mt-3">
							<label class="mint-assoc-card__label"><?php esc_html_e( 'Section', 'mint-lms' ); ?></label>
														<div
								class="mint-assoc-select"
								:class="open ? 'is-open' : ''"
								x-data="{ open: false }"
								@click.outside="open = false"
							>
								<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
									<span
										class="mint-assoc-select__value"
										:class="false ? 'is-placeholder' : ''"
										x-text="attachSectionLabel() || '<?php echo esc_js( __( 'No section', 'mint-lms' ) ); ?>'"
									></span>
									<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
								</button>
								<div class="mint-assoc-select__menu" x-show="open" x-cloak>
									<button
										type="button"
										class="mint-assoc-select__option"
										:class="!attach.sectionId ? 'is-active' : ''"
										@click="pickAttachSection(0); open = false"
									><?php esc_html_e( 'No section', 'mint-lms' ); ?></button>									<template x-for="s in attachSections" :key="s.id">
										<button
											type="button"
											class="mint-assoc-select__option"
											:class="Number(attach.sectionId) === Number(s.id) ? 'is-active' : ''"
											@click="pickAttachSection(s.id); open = false"
											x-text="s.title"
										></button>
									</template>
								</div>
							</div>
							<button
								type="button"
								class="mint-builder-btn-publish"
								@click="attachStandaloneToCourse()"
								:disabled="!attach.courseId || attaching"
							>
								<span x-text="attaching ? '<?php echo esc_js( __( 'Adding…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Add to course', 'mint-lms' ) ); ?>'"></span>
							</button>
						</div>
					</template>
				</div>
			</div>
				</div>
			</template>
		</aside>

		<section class="mint-builder-editor mint-flex mint-flex-col">
			<template x-if="!selected">
				<div class="mint-builder-empty" :class="overviewKind ? 'mint-builder-empty--overview' : ''">
					<template x-if="overviewKind">
						<div class="mint-builder-overview">
							<div class="mint-builder-overview__head">
								<div class="mint-builder-overview__title" x-text="overviewTitle()"></div>
								<div class="mint-builder-overview__copy" x-text="overviewCopy()"></div>
							</div>
							<label class="mint-builder-overview__search">
								<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
									<circle cx="9" cy="9" r="5.5"/>
									<path d="m13.5 13.5 3 3"/>
								</svg>
								<input
									type="search"
									class="mint-builder-overview__search-input"
									x-model="overviewSearch"
									:placeholder="overviewSearchPlaceholder()"
									:aria-label="overviewSearchPlaceholder()"
									autocomplete="off"
								/>
							</label>
							<div class="mint-builder-overview__list">
								<template x-if="overviewItems().length === 0">
									<div class="mint-builder-overview__empty" x-text="overviewEmptyLabel()"></div>
								</template>
								<template x-for="item in overviewItems()" :key="'ov-' + overviewKind + '-' + item.id">
									<button type="button" class="mint-builder-overview__row" @click="openOverviewItem(item)">
										<span class="mint-builder-overview__row-icon" aria-hidden="true" x-html="overviewItemIcon(item.kind)"></span>
										<span class="mint-builder-overview__row-body">
											<span class="mint-builder-overview__row-title mint-truncate" x-text="item.title || overviewUntitledLabel()"></span>
											<span class="mint-builder-overview__row-meta mint-truncate" x-show="item.meta" x-text="item.meta"></span>
										</span>
									</button>
								</template>
							</div>
							<button
								type="button"
								class="mint-builder-empty__cta"
								:disabled="overviewAddDisabled()"
								@click="overviewAddNew()"
								x-text="overviewAddLabel()"
							></button>
						</div>
					</template>
					<template x-if="!overviewKind">
						<div class="mint-max-w-[380px] mint-text-center">
							<template x-if="hasRealSections()">
								<div>
									<div class="mint-builder-empty__icon">
										<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="4.5" width="16" height="15" rx="2.5"/><path d="M8 4.5v15"/></svg>
									</div>
									<div class="mint-builder-empty__title"><?php esc_html_e( 'Add a lesson', 'mint-lms' ); ?></div>
									<div class="mint-builder-empty__copy"><?php esc_html_e( 'Use Course Content Tree to browse, or add a lesson to keep building your course.', 'mint-lms' ); ?></div>
									<button
										type="button"
										class="mint-builder-empty__cta"
										x-show="!isStandalone"
										:disabled="addingLesson"
										@click="addLessonToFirstSection()"
									><?php esc_html_e( 'Add lesson', 'mint-lms' ); ?></button>
								</div>
							</template>
							<template x-if="!hasRealSections()">
								<div>
									<div class="mint-builder-empty__icon">
										<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 6.5h15M4.5 12h15M4.5 17.5h9"/></svg>
									</div>
									<div class="mint-builder-empty__title"><?php esc_html_e( 'Start with a section', 'mint-lms' ); ?></div>
									<div class="mint-builder-empty__copy"><?php esc_html_e( 'Add a section first, then add lessons, quizzes, and questions.', 'mint-lms' ); ?></div>
									<button
										type="button"
										class="mint-builder-empty__cta"
										x-show="!isStandalone"
										:disabled="addingSection"
										@click="addSection()"
									><?php esc_html_e( 'Add section', 'mint-lms' ); ?></button>
								</div>
							</template>
						</div>
					</template>
				</div>
			</template>

			<template x-if="selected && selected.type === 'section' && selectedSection">
				<div class="mint-builder-editor__canvas mint-flex mint-h-full mint-w-full mint-flex-col">
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
						<div class="mint-mt-1 mint-text-[15px] mint-text-ink-3"><?php esc_html_e( 'Add a lesson to this section, or keep building from the top menu.', 'mint-lms' ); ?></div>
						<button
							type="button"
							class="mint-builder-empty__cta mint-mt-4"
							:disabled="addingLesson"
							@click="addLesson(selected.id)"
						><?php esc_html_e( 'Add lesson', 'mint-lms' ); ?></button>
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
					<div class="mint-builder-editor__canvas">
						<div x-show="quizLoading" class="mint-text-[15px] mint-text-ink-3"><?php esc_html_e( 'Loading quiz…', 'mint-lms' ); ?></div>

						<template x-if="!quizLoading && lessonQuiz">
							<div>
								<nav class="mint-flex mint-items-center mint-gap-2.5 mint-text-[15px] mint-text-ink-2" aria-label="<?php echo esc_attr__( 'Quiz location', 'mint-lms' ); ?>">
									<template x-if="breadcrumbCourseTitle()">
										<span class="mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2.5">
											<a
												class="mint-breadcrumb__link mint-truncate"
												:href="parentCourseEditorUrl()"
												@click="goToParentCourse($event)"
												x-text="breadcrumbCourseTitle()"
											></a>
											<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
										</span>
									</template>
									<template x-if="breadcrumbSectionTitle()">
										<span class="mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2.5">
											<button
												type="button"
												class="mint-breadcrumb__link mint-truncate"
												@click="selectItem('section', selected.sectionId)"
												x-text="breadcrumbSectionTitle()"
											></button>
											<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
										</span>
									</template>
									<template x-if="showSidebarLessonParent() && breadcrumbParentLessonTitle()">
										<span class="mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2.5">
											<a
												class="mint-breadcrumb__link mint-truncate"
												:href="parentLessonEditorUrl()"
												@click="goToParentLesson($event)"
												x-text="breadcrumbParentLessonTitle()"
											></a>
											<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
										</span>
									</template>
									<span class="mint-breadcrumb__current" x-text="breadcrumbQuizLabel()"></span>
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
												<div class="mint-quiz-question__label" x-text="'<?php echo esc_js( __( 'Question', 'mint-lms' ) ); ?> ' + (qIndex + 1) + ' · ' + questionTypeLabel(question)"></div>
												<button type="button" class="mint-builder-delete" @click="deleteQuizQuestion(question)"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
											</div>

											<textarea
												class="mint-lesson-field-textarea mint-lesson-field-textarea--question"
												x-model="question.prompt"
												placeholder="<?php echo esc_attr__( 'Write your question', 'mint-lms' ); ?>"
											></textarea>

											<template x-if="question.type === 'mcq' || question.type === 'mcq_multi'">
												<div>
													<div class="mint-quiz-options">
														<template x-for="(option, oIndex) in question.options" :key="oIndex">
															<div class="mint-quiz-option-row">
																<button
																	type="button"
																	class="mint-quiz-option-mark"
																	:class="{
																		'is-selected': isMcqOptionCorrect(question, question.options[oIndex]),
																		'mint-quiz-option-mark--multi': question.type === 'mcq_multi'
																	}"
																	:aria-pressed="isMcqOptionCorrect(question, question.options[oIndex]) ? 'true' : 'false'"
																	:title="'<?php echo esc_js( __( 'Mark as correct', 'mint-lms' ) ); ?>'"
																	@click="toggleMcqCorrect(question, question.options[oIndex])"
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
														x-show="question.options.length < 6"
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

											<template x-if="question.type === 'essay'">
												<div class="mint-quiz-essay mint-mt-3">
													<p class="mint-m-0 mint-text-[15px] mint-text-ink-2">
														<?php esc_html_e( 'Students write a free-text answer. Not auto-scored.', 'mint-lms' ); ?>
													</p>
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

								<div
									class="mint-assoc-card mint-mt-8"
									x-show="isStandalone && !showSidebarLessonParent() && lessonQuiz?.id"
									x-cloak
								>
									<h2 class="mint-assoc-card__title"><?php esc_html_e( 'Add to a lesson', 'mint-lms' ); ?></h2>
									<p class="mint-assoc-card__copy"><?php esc_html_e( 'Attach this quiz to an existing lesson.', 'mint-lms' ); ?></p>
									<label class="mint-assoc-card__label"><?php esc_html_e( 'Lesson', 'mint-lms' ); ?></label>
									<div
										class="mint-assoc-select"
										:class="open ? 'is-open' : ''"
										x-data="{ open: false }"
										@click.outside="open = false"
									>
										<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
											<span
												class="mint-assoc-select__value"
												:class="!attach.lessonId ? 'is-placeholder' : ''"
												x-text="attachLessonLabel() || 'Select a lesson…'"
											></span>
											<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
										</button>
										<div class="mint-assoc-select__menu" x-show="open" x-cloak>
											<button
												type="button"
												class="mint-assoc-select__option"
												:class="!attach.lessonId ? 'is-active' : ''"
												@click="pickAttachLesson(0); open = false"
											><?php esc_html_e( 'Select a lesson…', 'mint-lms' ); ?></button>
											<template x-for="l in attachLessons" :key="'ed-ql-' + l.id">
												<button
													type="button"
													class="mint-assoc-select__option"
													:class="Number(attach.lessonId) === Number(l.id) ? 'is-active' : ''"
															@click="pickAttachLesson(l.id); open = false"
															x-text="l.title"
												></button>
											</template>
										</div>
									</div>
									<button
										type="button"
										class="mint-builder-btn-publish mint-mt-3"
										@click="attachStandaloneQuizToLesson()"
										:disabled="!attach.lessonId || attaching"
									>
										<span x-text="attaching ? '<?php echo esc_js( __( 'Adding…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Add to lesson', 'mint-lms' ) ); ?>'"></span>
									</button>
								</div>

								<div
									class="mint-assoc-card mint-mt-8"
									x-show="showSidebarLessonParent() && lessonQuiz?.id"
									x-cloak
								>
									<div class="mint-assoc-card__badge"><?php esc_html_e( 'Linked lesson', 'mint-lms' ); ?></div>
									<div class="mint-assoc-card__current mint-truncate" x-text="breadcrumbParentLessonTitle() || breadcrumbQuizContextTitle() || '<?php echo esc_js( __( 'Lesson', 'mint-lms' ) ); ?>'"></div>
									<template x-if="!attach.changing">
										<div class="mint-assoc-card__actions">
											<button type="button" class="mint-lesson-choose-file" :disabled="attaching" @click="startChangeLesson()"><?php esc_html_e( 'Change', 'mint-lms' ); ?></button>
											<button type="button" class="mint-assoc-card__remove" :disabled="attaching" @click="detachQuizFromLesson()"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
										</div>
									</template>
									<template x-if="attach.changing">
										<div class="mint-mt-3">
											<label class="mint-assoc-card__label"><?php esc_html_e( 'New lesson', 'mint-lms' ); ?></label>
											<div
												class="mint-assoc-select"
												:class="open ? 'is-open' : ''"
												x-data="{ open: false }"
												@click.outside="open = false"
											>
												<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
													<span
														class="mint-assoc-select__value"
														:class="!attach.lessonId ? 'is-placeholder' : ''"
														x-text="attachLessonLabel() || 'Select a lesson…'"
													></span>
													<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
												</button>
												<div class="mint-assoc-select__menu" x-show="open" x-cloak>
													<button
														type="button"
														class="mint-assoc-select__option"
														:class="!attach.lessonId ? 'is-active' : ''"
														@click="pickAttachLesson(0); open = false"
													><?php esc_html_e( 'Select a lesson…', 'mint-lms' ); ?></button>
													<template x-for="l in attachLessons" :key="'ed-chg-l-' + l.id">
														<button
															type="button"
															class="mint-assoc-select__option"
															:class="Number(attach.lessonId) === Number(l.id) ? 'is-active' : ''"
															@click="pickAttachLesson(l.id); open = false"
															x-text="l.title"
														></button>
													</template>
												</div>
											</div>
											<div class="mint-assoc-card__actions">
												<button type="button" class="mint-builder-btn-publish" :disabled="!attach.lessonId || attaching" @click="attachStandaloneQuizToLesson()">
													<span x-text="attaching ? '<?php echo esc_js( __( 'Saving…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Save', 'mint-lms' ) ); ?>'"></span>
												</button>
												<button type="button" class="mint-assoc-card__cancel" :disabled="attaching" @click="cancelChangeAssociation()"><?php esc_html_e( 'Cancel', 'mint-lms' ); ?></button>
											</div>
										</div>
									</template>
								</div>

								<div class="mint-mt-8 mint-border-t mint-border-rule mint-pt-5">
									<button
										type="button"
										class="mint-quiz-action mint-mb-4"
										@click="openLessonQuestionEditor()"
										:disabled="quizSaving || !lessonQuiz?.id"
									>
										<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10" /></svg>
										<?php esc_html_e( 'Add new question', 'mint-lms' ); ?>
									</button>
									<div class="mint-flex mint-flex-wrap mint-items-center mint-justify-between mint-gap-4">
										<button type="button" class="mint-builder-delete" @click="deleteQuiz()"><?php esc_html_e( 'Delete quiz', 'mint-lms' ); ?></button>
										<button
											type="button"
											class="mint-builder-btn-publish !mint-h-[38px] !mint-rounded-[9px] !mint-px-4 !mint-text-[15px]"
											@click="saveQuiz()"
											:disabled="quizSaving"
										><?php esc_html_e( 'Save changes', 'mint-lms' ); ?></button>
									</div>
								</div>
							</div>
						</template>
					</div>
				</div>
			</div>

			<div
				x-show="selected && selected.type === 'question'"
				x-cloak
				class="mint-flex mint-h-full mint-flex-col"
			>
				<div class="mint-min-h-0 mint-flex-1 mint-overflow-y-auto">
					<div class="mint-builder-editor__canvas">
						<div x-show="quizLoading" class="mint-text-[15px] mint-text-ink-3"><?php esc_html_e( 'Loading question…', 'mint-lms' ); ?></div>

						<template x-if="!quizLoading && activeQuestion">
							<div>
								<nav class="mint-flex mint-items-center mint-gap-2.5 mint-text-[15px] mint-text-ink-2" aria-label="<?php echo esc_attr__( 'Question location', 'mint-lms' ); ?>">
									<template x-if="breadcrumbCourseTitle()">
										<span class="mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2.5">
											<a
												class="mint-breadcrumb__link mint-truncate"
												:href="parentCourseEditorUrl()"
												@click="goToParentCourse($event)"
												x-text="breadcrumbCourseTitle()"
											></a>
											<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
										</span>
									</template>
									<template x-if="breadcrumbSectionTitle()">
										<span class="mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2.5">
											<button
												type="button"
												class="mint-breadcrumb__link mint-truncate"
												@click="selectItem('section', selected.sectionId)"
												x-text="breadcrumbSectionTitle()"
											></button>
											<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
										</span>
									</template>
									<template x-if="showSidebarLessonParent() && breadcrumbParentLessonTitle()">
										<span class="mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2.5">
											<a
												class="mint-breadcrumb__link mint-truncate"
												:href="parentLessonEditorUrl()"
												@click="goToParentLesson($event)"
												x-text="breadcrumbParentLessonTitle()"
											></a>
											<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
										</span>
									</template>
									<template x-if="showSidebarQuizParent() && breadcrumbQuizLabel()">
										<span class="mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2.5">
											<a
												class="mint-breadcrumb__link mint-truncate"
												:href="parentQuizEditorUrl()"
												@click="goToParentQuiz($event)"
												x-text="breadcrumbQuizLabel()"
											></a>
											<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
										</span>
									</template>
									<span class="mint-breadcrumb__current" x-text="breadcrumbQuestionLabel()"></span>
								</nav>

								<input
									type="text"
									class="mint-lesson-title-input mint-mt-3.5"
									x-model="activeQuestion.title"
									@input="syncActiveQuestionTitle()"
									placeholder="<?php echo esc_attr__( 'New Question', 'mint-lms' ); ?>"
								/>
								<div class="mint-mt-2 mint-text-[15px] mint-text-ink-2"><?php esc_html_e( 'Click the title to rename it.', 'mint-lms' ); ?></div>

								<div class="mint-quiz-type-chip mint-mt-8">
									<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="M7.5 10.2 8.8 11.5 12.5 8"/></svg>
									<?php esc_html_e( 'Question', 'mint-lms' ); ?>
								</div>

								<div class="mint-mt-[22px] mint-text-[13px] mint-font-bold mint-uppercase mint-tracking-[0.08em] mint-text-ink-2"><?php esc_html_e( 'Answer Type', 'mint-lms' ); ?></div>
								<div class="mint-mt-3 mint-flex mint-flex-wrap mint-gap-2.5">
									<button type="button" class="mint-question-type-btn" :class="questionAnswerReady && activeQuestion.type === 'mcq_multi' ? 'is-active' : ''" @click="setQuestionAnswerType('mcq_multi')"><?php esc_html_e( '+ Add MCQ (Multiple Choice)', 'mint-lms' ); ?></button>
									<button type="button" class="mint-question-type-btn" :class="questionAnswerReady && activeQuestion.type === 'mcq' ? 'is-active' : ''" @click="setQuestionAnswerType('mcq_single')"><?php esc_html_e( '+ Add MCQ (Single Choice)', 'mint-lms' ); ?></button>
									<button type="button" class="mint-question-type-btn" :class="questionAnswerReady && activeQuestion.type === 'true_false' ? 'is-active' : ''" @click="setQuestionAnswerType('true_false')"><?php esc_html_e( '+ Add True/False', 'mint-lms' ); ?></button>
									<button type="button" class="mint-question-type-btn" :class="questionAnswerReady && activeQuestion.type === 'essay' ? 'is-active' : ''" @click="setQuestionAnswerType('essay')"><?php esc_html_e( '+ Add Essay Answer', 'mint-lms' ); ?></button>
								</div>

								<div class="mint-quiz-question mint-mt-[22px]" x-show="questionAnswerReady" x-cloak>
									<div class="mint-qeditor__head">
										<div class="mint-qeditor__label" x-text="'<?php echo esc_js( __( 'Question', 'mint-lms' ) ); ?> · ' + questionTypeLabel(activeQuestion)"></div>
										<button type="button" class="mint-qeditor__remove" @click="deleteActiveQuestion()"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
									</div>

									<div class="mint-qeditor__media-row">
										<button type="button" class="mint-lesson-add-media" @click="addMediaToQuestionPrompt()">
											<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="3.5" width="15" height="13" rx="3"/><circle cx="7" cy="8" r="1.4"/><path d="m4 14 4-3.5 3 2 4.5-4"/></svg>
											<?php esc_html_e( 'Add media', 'mint-lms' ); ?>
										</button>
										<div class="mint-lesson-view-toggle">
											<button type="button" class="mint-lesson-view-toggle__item" :class="questionEditorView === 'visual' ? 'is-active' : ''" @click="questionEditorView = 'visual'"><?php esc_html_e( 'Visual', 'mint-lms' ); ?></button>
											<button type="button" class="mint-lesson-view-toggle__item" :class="questionEditorView === 'code' ? 'is-active' : ''" @click="questionEditorView = 'code'"><?php esc_html_e( 'Code', 'mint-lms' ); ?></button>
										</div>
									</div>

									<div class="mint-qeditor__frame" :class="questionEditorView === 'code' ? 'is-code' : 'is-visual'">
										<div class="mint-qeditor__toolbar" x-show="questionEditorView === 'visual'" x-cloak @click.outside="questionFormatMenuOpen = false">
											<div class="mint-lesson-format">
												<button type="button" class="mint-lesson-format__trigger" @click="questionFormatMenuOpen = !questionFormatMenuOpen" :aria-expanded="questionFormatMenuOpen ? 'true' : 'false'">
													<span x-text="questionFormatLabel"></span>
													<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
												</button>
												<div class="mint-lesson-format__menu" x-show="questionFormatMenuOpen" x-cloak>
													<template x-for="option in formatOptions" :key="'qfmt-' + option.value">
														<button type="button" class="mint-lesson-format__option" :style="`font-size: ${option.size}; font-weight: ${option.weight}`" @click="applyQuestionFormat(option)" x-text="option.label"></button>
													</template>
												</div>
											</div>
											<div class="mint-lesson-editor-toolbar__divider" aria-hidden="true"></div>
											<button type="button" class="mint-lesson-editor-tool" style="font-weight: 700" @click="wrapQuestionPrompt('<strong>', '</strong>')">B</button>
											<button type="button" class="mint-lesson-editor-tool" style="font-weight: 500; font-style: italic" @click="wrapQuestionPrompt('<em>', '</em>')">I</button>
											<button type="button" class="mint-lesson-editor-tool" style="font-weight: 600" @click="wrapQuestionPrompt('<h2>', '</h2>')">H2</button>
											<button type="button" class="mint-lesson-editor-tool" style="font-weight: 600" @click="wrapQuestionPrompt('<ul><li>', '</li></ul>')">•</button>
											<button type="button" class="mint-lesson-editor-tool" style="font-weight: 500" @click="wrapQuestionPrompt('<ol><li>', '</li></ol>')">1.</button>
											<button type="button" class="mint-lesson-editor-tool" @click="insertQuestionLink()">🔗</button>
										</div>
										<div class="mint-qeditor__toolbar mint-qeditor__toolbar--code" x-show="questionEditorView === 'code'" x-cloak>
											<template x-for="tag in codeTags" :key="'qcode-' + tag">
												<button type="button" class="mint-lesson-code-chip" @click="insertQuestionCodeTag(tag)" x-text="tag"></button>
											</template>
										</div>
										<textarea
											id="mint-question-prompt"
											class="mint-qeditor__textarea"
											x-model="activeQuestion.prompt"
											placeholder="<?php echo esc_attr__( 'Write your question', 'mint-lms' ); ?>"
										></textarea>
									</div>

									<div class="mint-qeditor__attach-row">
										<button type="button" class="mint-qeditor__choose-file" @click="chooseQuestionAttachment()"><?php esc_html_e( 'Choose file', 'mint-lms' ); ?></button>
										<div class="mint-qeditor__attach-label" x-text="activeQuestion.settings.attachmentName || '<?php echo esc_js( __( 'No attachment', 'mint-lms' ) ); ?>'"></div>
										<div class="mint-qeditor__attach-spacer"></div>
										<div class="mint-qeditor__preview-label"><?php esc_html_e( 'Free preview', 'mint-lms' ); ?></div>
										<button
											type="button"
											class="mint-qeditor__switch"
											role="switch"
											:aria-checked="activeQuestion.settings.freePreview ? 'true' : 'false'"
											:class="activeQuestion.settings.freePreview ? 'is-on' : ''"
											@click="activeQuestion.settings.freePreview = !activeQuestion.settings.freePreview"
										><span class="mint-qeditor__switch-knob"></span></button>
									</div>

									<template x-if="activeQuestion.type === 'mcq' || activeQuestion.type === 'mcq_multi'">
										<div class="mint-qeditor__mcq">
											<template x-for="(option, oIndex) in activeQuestion.options" :key="'opt-' + oIndex">
												<div class="mint-qeditor__option-card">
													<div class="mint-qeditor__option-table">
														<div class="mint-qeditor__option-cols mint-qeditor__option-cols--head">
															<div class="mint-qeditor__option-cell mint-qeditor__option-cell--head"><?php esc_html_e( 'Options', 'mint-lms' ); ?></div>
															<div class="mint-qeditor__option-cell mint-qeditor__option-cell--head"><?php esc_html_e( 'Answer', 'mint-lms' ); ?></div>
														</div>
														<div class="mint-qeditor__option-cols">
															<div class="mint-qeditor__option-cell mint-qeditor__option-cell--side">
																<button type="button" class="mint-qeditor__correct-row" @click="toggleMcqCorrect(activeQuestion, activeQuestion.options[oIndex])">
																	<span
																		class="mint-qeditor__correct-mark"
																		:class="{
																			'is-selected': isMcqOptionCorrect(activeQuestion, activeQuestion.options[oIndex]),
																			'is-multi': activeQuestion.type === 'mcq_multi'
																		}"
																	>
																		<svg x-show="activeQuestion.type === 'mcq_multi' && isMcqOptionCorrect(activeQuestion, activeQuestion.options[oIndex])" width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 10.5 8.5 14 15 6.5"/></svg>
																		<span x-show="activeQuestion.type !== 'mcq_multi' && isMcqOptionCorrect(activeQuestion, activeQuestion.options[oIndex])" class="mint-qeditor__correct-dot"></span>
																	</span>
																	<span><?php esc_html_e( 'Correct', 'mint-lms' ); ?></span>
																</button>
																<button type="button" class="mint-qeditor__correct-row" @click="toggleOptionAllowHtml(oIndex, !activeQuestion.settings.allowHtml[oIndex])">
																	<span class="mint-qeditor__html-box" :class="activeQuestion.settings.allowHtml[oIndex] ? 'is-checked' : ''"></span>
																	<span><?php esc_html_e( 'Allow HTML', 'mint-lms' ); ?></span>
																</button>
															</div>
															<div class="mint-qeditor__option-cell">
																<input
																	type="text"
																	class="mint-qeditor__answer-input"
																	x-model="activeQuestion.options[oIndex]"
																	:placeholder="'<?php echo esc_js( __( 'Option', 'mint-lms' ) ); ?> ' + (oIndex + 1)"
																/>
															</div>
														</div>
													</div>
													<div class="mint-qeditor__option-actions">
														<button type="button" class="mint-qeditor__btn-solid" @click="removeMcqOption(activeQuestion, oIndex)"><?php esc_html_e( 'Delete answer', 'mint-lms' ); ?></button>
														<button type="button" class="mint-qeditor__btn-outline" @click="addMediaToQuestionOption(oIndex)"><?php esc_html_e( 'Add Media', 'mint-lms' ); ?></button>
														<button type="button" class="mint-qeditor__btn-outline" @click="moveMcqOption(activeQuestion, oIndex, oIndex === activeQuestion.options.length - 1 ? -1 : 1)"><?php esc_html_e( 'Move', 'mint-lms' ); ?></button>
													</div>
												</div>
											</template>
											<button type="button" class="mint-qeditor__btn-solid mint-qeditor__btn-solid--wide" x-show="activeQuestion.options.length < 6" @click="addMcqOption(activeQuestion)"><?php esc_html_e( 'Add new answer', 'mint-lms' ); ?></button>
										</div>
									</template>

									<template x-if="activeQuestion.type === 'true_false'">
										<div class="mint-qeditor__tf">
											<div class="mint-quiz-tf">
												<button type="button" class="mint-quiz-tf__pill" :class="activeQuestion.correctAnswer === 'true' ? 'is-selected' : ''" @click="activeQuestion.correctAnswer = 'true'">
													<span class="mint-quiz-tf__dot"></span>
													<?php esc_html_e( 'True is correct', 'mint-lms' ); ?>
												</button>
												<button type="button" class="mint-quiz-tf__pill" :class="activeQuestion.correctAnswer === 'false' ? 'is-selected' : ''" @click="activeQuestion.correctAnswer = 'false'">
													<span class="mint-quiz-tf__dot"></span>
													<?php esc_html_e( 'False is correct', 'mint-lms' ); ?>
												</button>
											</div>

											<template x-for="(extra, exIndex) in activeQuestion.extraTf" :key="extra._key || ('ex-' + exIndex)">
												<div class="mint-qeditor__tf-extra">
													<div class="mint-qeditor__media-row mint-qeditor__media-row--compact">
														<button type="button" class="mint-lesson-add-media mint-lesson-add-media--sm" @click="addMediaToExtraTfPrompt(exIndex)">
															<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="3.5" width="15" height="13" rx="3"/><circle cx="7" cy="8" r="1.4"/><path d="m4 14 4-3.5 3 2 4.5-4"/></svg>
															<?php esc_html_e( 'Add media', 'mint-lms' ); ?>
														</button>
														<div class="mint-lesson-view-toggle mint-lesson-view-toggle--sm">
															<button type="button" class="mint-lesson-view-toggle__item" :class="(extra.editorView || 'visual') === 'visual' ? 'is-active' : ''" @click="extra.editorView = 'visual'"><?php esc_html_e( 'Visual', 'mint-lms' ); ?></button>
															<button type="button" class="mint-lesson-view-toggle__item" :class="extra.editorView === 'code' ? 'is-active' : ''" @click="extra.editorView = 'code'"><?php esc_html_e( 'Code', 'mint-lms' ); ?></button>
														</div>
													</div>

													<div class="mint-qeditor__frame mint-qeditor__frame--sm">
														<div class="mint-qeditor__toolbar mint-qeditor__toolbar--sm" x-show="(extra.editorView || 'visual') === 'visual'">
															<button type="button" class="mint-lesson-format__trigger mint-lesson-format__trigger--sm" disabled>
																<span><?php esc_html_e( 'Paragraph', 'mint-lms' ); ?></span>
																<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
															</button>
															<div class="mint-lesson-editor-toolbar__divider" aria-hidden="true"></div>
															<button type="button" class="mint-lesson-editor-tool" style="font-weight: 700" @click="wrapExtraTfPrompt(exIndex, '<strong>', '</strong>')">B</button>
															<button type="button" class="mint-lesson-editor-tool" style="font-weight: 500; font-style: italic" @click="wrapExtraTfPrompt(exIndex, '<em>', '</em>')">I</button>
															<button type="button" class="mint-lesson-editor-tool" style="font-weight: 600" @click="wrapExtraTfPrompt(exIndex, '<h2>', '</h2>')">H2</button>
															<button type="button" class="mint-lesson-editor-tool" style="font-weight: 600" @click="wrapExtraTfPrompt(exIndex, '<ul><li>', '</li></ul>')">•</button>
															<button type="button" class="mint-lesson-editor-tool" style="font-weight: 500" @click="wrapExtraTfPrompt(exIndex, '<ol><li>', '</li></ol>')">1.</button>
															<button type="button" class="mint-lesson-editor-tool" @click="insertExtraTfLink(exIndex)">🔗</button>
														</div>
														<textarea
															class="mint-qeditor__textarea mint-qeditor__textarea--sm"
															:id="'mint-extra-tf-prompt-' + exIndex"
															x-model="extra.prompt"
															placeholder="<?php echo esc_attr__( 'Question prompt…', 'mint-lms' ); ?>"
														></textarea>
													</div>

													<div class="mint-qeditor__attach-row mint-qeditor__attach-row--sm">
														<button type="button" class="mint-qeditor__choose-file mint-qeditor__choose-file--sm" @click="chooseExtraTfAttachment(exIndex)"><?php esc_html_e( 'Choose file', 'mint-lms' ); ?></button>
														<div class="mint-qeditor__attach-label" x-text="extra.attachmentName || '<?php echo esc_js( __( 'No attachment', 'mint-lms' ) ); ?>'"></div>
														<div class="mint-qeditor__attach-spacer"></div>
														<div class="mint-qeditor__preview-label"><?php esc_html_e( 'Free preview', 'mint-lms' ); ?></div>
														<button
															type="button"
															class="mint-qeditor__switch mint-qeditor__switch--sm"
															role="switch"
															:aria-checked="extra.freePreview ? 'true' : 'false'"
															:class="extra.freePreview ? 'is-on' : ''"
															@click="extra.freePreview = !extra.freePreview"
														><span class="mint-qeditor__switch-knob"></span></button>
													</div>

													<div class="mint-quiz-tf">
														<button type="button" class="mint-quiz-tf__pill" :class="extra.correctAnswer === 'true' ? 'is-selected' : ''" @click="extra.correctAnswer = 'true'">
															<span class="mint-quiz-tf__dot"></span>
															<?php esc_html_e( 'True is correct', 'mint-lms' ); ?>
														</button>
														<button type="button" class="mint-quiz-tf__pill" :class="extra.correctAnswer === 'false' ? 'is-selected' : ''" @click="extra.correctAnswer = 'false'">
															<span class="mint-quiz-tf__dot"></span>
															<?php esc_html_e( 'False is correct', 'mint-lms' ); ?>
														</button>
													</div>

													<button type="button" class="mint-qeditor__btn-solid" @click="removeExtraTfAnswer(exIndex)"><?php esc_html_e( 'Delete answer', 'mint-lms' ); ?></button>
												</div>
											</template>

											<div class="mint-qeditor__tf-actions mint-qeditor__tf-actions--stack">
												<button type="button" class="mint-qeditor__btn-solid" @click="addExtraTfAnswer()"><?php esc_html_e( '+ Add new answer', 'mint-lms' ); ?></button>
												<button
													type="button"
													class="mint-qeditor__btn-solid"
													x-show="!activeQuestion.extraTf || activeQuestion.extraTf.length === 0"
													@click="deleteBaseTfAnswer()"
												><?php esc_html_e( 'Delete answer', 'mint-lms' ); ?></button>
												<button type="button" class="mint-qeditor__btn-solid" @click="saveActiveQuestion()" :disabled="quizSaving"><?php esc_html_e( 'Save question', 'mint-lms' ); ?></button>
											</div>
										</div>
									</template>

									<template x-if="activeQuestion.type === 'essay'">
										<div class="mint-qeditor__essay">
											<div class="mint-qeditor__essay-head">
												<div class="mint-qeditor__essay-title"><?php esc_html_e( 'Answers (required)', 'mint-lms' ); ?></div>
												<div class="mint-qeditor__essay-chevrons" aria-hidden="true">
													<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5 10 7.5 15 12.5"/></svg>
													<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7.5 10 12.5 15 7.5"/></svg>
												</div>
											</div>

											<div class="mint-qeditor__essay-hint"><?php esc_html_e( 'How should the user submit their answer?', 'mint-lms' ); ?></div>
											<div class="mint-qeditor__dropdown" @click.outside="essaySubmitOpen = false">
												<button type="button" class="mint-qeditor__dropdown-trigger" @click="essaySubmitOpen = !essaySubmitOpen">
													<span x-text="activeQuestion.settings.submitMethod || 'Text Box'"></span>
													<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
												</button>
												<div class="mint-qeditor__dropdown-menu" x-show="essaySubmitOpen" x-cloak>
													<button type="button" class="mint-qeditor__dropdown-item" :class="activeQuestion.settings.submitMethod !== 'Upload' ? 'is-active' : ''" @click="setEssaySubmitMethod('Text Box')"><?php esc_html_e( 'Text Box', 'mint-lms' ); ?></button>
													<button type="button" class="mint-qeditor__dropdown-item" :class="activeQuestion.settings.submitMethod === 'Upload' ? 'is-active' : ''" @click="setEssaySubmitMethod('Upload')"><?php esc_html_e( 'Upload', 'mint-lms' ); ?></button>
												</div>
											</div>

											<p class="mint-qeditor__essay-note"><?php esc_html_e( "If you set the question to be graded and the student doesn't have enough points to proceed, they will be blocked from continuing the course until the question has been graded.", 'mint-lms' ); ?></p>
											<p class="mint-qeditor__essay-note mint-qeditor__essay-note--tight"><?php esc_html_e( 'The student can progress if they have enough points to move on.', 'mint-lms' ); ?></p>
											<div class="mint-qeditor__essay-hint"><?php esc_html_e( 'How should the answer to this question be marked and graded upon quiz submission?', 'mint-lms' ); ?></div>

											<div class="mint-qeditor__dropdown mint-qeditor__dropdown--wide" @click.outside="essayGradingOpen = false">
												<button type="button" class="mint-qeditor__dropdown-trigger" @click="essayGradingOpen = !essayGradingOpen">
													<span x-text="activeQuestion.settings.gradingMode || 'Not Graded, No Points Awarded'"></span>
													<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
												</button>
												<div class="mint-qeditor__dropdown-menu" x-show="essayGradingOpen" x-cloak>
													<template x-for="mode in essayGradingModes" :key="mode">
														<button type="button" class="mint-qeditor__dropdown-item" :class="activeQuestion.settings.gradingMode === mode ? 'is-active' : ''" @click="setEssayGradingMode(mode)" x-text="mode"></button>
													</template>
												</div>
											</div>

											<div class="mint-qeditor__points">
												<div class="mint-qeditor__essay-head">
													<div class="mint-qeditor__essay-title"><?php esc_html_e( 'Points (required)', 'mint-lms' ); ?></div>
													<div class="mint-qeditor__essay-chevrons" aria-hidden="true">
														<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5 10 7.5 15 12.5"/></svg>
														<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7.5 10 12.5 15 7.5"/></svg>
													</div>
												</div>
												<div class="mint-qeditor__essay-hint mint-qeditor__essay-hint--sm"><?php esc_html_e( 'Points for this question (Standard is 1 point)', 'mint-lms' ); ?></div>
												<div class="mint-qeditor__points-control">
													<span x-text="activeQuestion.settings.points"></span>
													<div class="mint-qeditor__points-spinners">
														<button type="button" class="mint-qeditor__points-btn" @click="bumpEssayPoints(1)" aria-label="<?php echo esc_attr__( 'Increase points', 'mint-lms' ); ?>">
															<svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12 10 7 15 12"/></svg>
														</button>
														<button type="button" class="mint-qeditor__points-btn" @click="bumpEssayPoints(-1)" aria-label="<?php echo esc_attr__( 'Decrease points', 'mint-lms' ); ?>">
															<svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8 10 13 15 8"/></svg>
														</button>
													</div>
												</div>
											</div>
										</div>
									</template>

									<button
										type="button"
										class="mint-quiz-save-question"
										x-show="activeQuestion.type !== 'true_false'"
										@click="saveActiveQuestion()"
										:disabled="quizSaving"
									><?php esc_html_e( 'Save question', 'mint-lms' ); ?></button>
								</div>

								<section class="mint-mt-7 mint-grid mint-gap-[14px]">
									<div class="mint-text-[13px] mint-font-bold mint-uppercase mint-tracking-[0.08em] mint-text-ink-2">
										<?php esc_html_e( 'Set featured image · Question', 'mint-lms' ); ?>
									</div>

									<div
										x-show="!activeQuestion.featuredImageUrl"
										class="mint-cover-placeholder mint-flex mint-h-[200px] mint-items-center mint-justify-center mint-rounded-[14px] mint-border-[1.5px] mint-border-dashed mint-border-[#98FBCB]"
									>
										<span class="mint-font-mono mint-text-[13px] mint-text-ink-2"><?php esc_html_e( 'featured image · 1200×675', 'mint-lms' ); ?></span>
									</div>
									<div
										x-show="activeQuestion.featuredImageUrl"
										x-cloak
										class="mint-h-[200px] mint-overflow-hidden mint-rounded-[14px] mint-border-[1.5px] mint-border-[#DAD7E6]"
									>
										<img :src="activeQuestion.featuredImageUrl" alt="" class="mint-h-full mint-w-full mint-object-cover" />
									</div>

									<p class="mint-m-0 mint-text-base mint-leading-[26px] mint-text-ink-2">
										<?php esc_html_e( 'Optional. Shown on the question card and results screen.', 'mint-lms' ); ?>
									</p>

									<div class="mint-flex mint-flex-wrap mint-gap-2.5">
										<button
											type="button"
											class="mint-settings-action mint-inline-flex mint-h-[42px] mint-cursor-pointer mint-items-center mint-justify-center mint-rounded-md mint-border-0 mint-bg-cta mint-px-4 mint-text-base mint-font-semibold mint-text-cta-ink"
											@click="pickQuestionFeaturedImage()"
										><?php esc_html_e( 'Upload image', 'mint-lms' ); ?></button>
										<button
											type="button"
											class="mint-settings-action mint-inline-flex mint-h-[42px] mint-cursor-pointer mint-items-center mint-justify-center mint-rounded-md mint-border-0 mint-bg-cta mint-px-[15px] mint-text-base mint-font-semibold mint-text-cta-ink"
											x-show="activeQuestion.featuredImageId"
											x-cloak
											@click="clearQuestionFeaturedImage()"
										><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
									</div>
								</section>

								<div
									class="mint-assoc-card mint-mt-8"
									x-show="isStandalone && !showSidebarQuizParent() && (activeQuestion?.id || selected?.questionId)"
									x-cloak
								>
									<h2 class="mint-assoc-card__title"><?php esc_html_e( 'Add to a quiz', 'mint-lms' ); ?></h2>
									<p class="mint-assoc-card__copy"><?php esc_html_e( 'Attach this question to an existing quiz.', 'mint-lms' ); ?></p>
									<label class="mint-assoc-card__label"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></label>
									<div
										class="mint-assoc-select"
										:class="open ? 'is-open' : ''"
										x-data="{ open: false }"
										@click.outside="open = false"
									>
										<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
											<span
												class="mint-assoc-select__value"
												:class="!attach.quizId ? 'is-placeholder' : ''"
												x-text="attachQuizLabel() || 'Select a quiz…'"
											></span>
											<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
										</button>
										<div class="mint-assoc-select__menu" x-show="open" x-cloak>
											<button
												type="button"
												class="mint-assoc-select__option"
												:class="!attach.quizId ? 'is-active' : ''"
												@click="pickAttachQuiz(0); open = false"
											><?php esc_html_e( 'Select a quiz…', 'mint-lms' ); ?></button>
											<template x-for="q in attachQuizzes" :key="'ed-aq-' + q.id">
												<button
													type="button"
													class="mint-assoc-select__option"
													:class="Number(attach.quizId) === Number(q.id) ? 'is-active' : ''"
													@click="pickAttachQuiz(q.id); open = false"
													x-text="q.title"
												></button>
											</template>
										</div>
									</div>
									<button
										type="button"
										class="mint-builder-btn-publish mint-mt-3"
										@click="attachStandaloneQuestionToQuiz()"
										:disabled="!attach.quizId || attaching"
									>
										<span x-text="attaching ? '<?php echo esc_js( __( 'Adding…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Add to quiz', 'mint-lms' ) ); ?>'"></span>
									</button>
								</div>

								<div
									class="mint-assoc-card mint-mt-8"
									x-show="showSidebarQuizParent() && (activeQuestion?.id || selected?.questionId)"
									x-cloak
								>
									<div class="mint-assoc-card__badge"><?php esc_html_e( 'Linked quiz', 'mint-lms' ); ?></div>
									<div class="mint-assoc-card__current mint-truncate" x-text="breadcrumbQuizLabel() || '<?php echo esc_js( __( 'Quiz', 'mint-lms' ) ); ?>'"></div>
									<template x-if="!attach.changing">
										<div class="mint-assoc-card__actions">
											<button type="button" class="mint-lesson-choose-file" :disabled="attaching" @click="startChangeQuiz()"><?php esc_html_e( 'Change', 'mint-lms' ); ?></button>
											<button type="button" class="mint-assoc-card__remove" :disabled="attaching" @click="detachQuestionFromQuiz()"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
										</div>
									</template>
									<template x-if="attach.changing">
										<div class="mint-mt-3">
											<label class="mint-assoc-card__label"><?php esc_html_e( 'New quiz', 'mint-lms' ); ?></label>
											<div
												class="mint-assoc-select"
												:class="open ? 'is-open' : ''"
												x-data="{ open: false }"
												@click.outside="open = false"
											>
												<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
													<span
														class="mint-assoc-select__value"
														:class="!attach.quizId ? 'is-placeholder' : ''"
														x-text="attachQuizLabel() || 'Select a quiz…'"
													></span>
													<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
												</button>
												<div class="mint-assoc-select__menu" x-show="open" x-cloak>
													<button
														type="button"
														class="mint-assoc-select__option"
														:class="!attach.quizId ? 'is-active' : ''"
														@click="pickAttachQuiz(0); open = false"
													><?php esc_html_e( 'Select a quiz…', 'mint-lms' ); ?></button>
													<template x-for="q in attachQuizzes" :key="'ed-chg-q-' + q.id">
														<button
															type="button"
															class="mint-assoc-select__option"
															:class="Number(attach.quizId) === Number(q.id) ? 'is-active' : ''"
															@click="pickAttachQuiz(q.id); open = false"
															x-text="q.title"
														></button>
													</template>
												</div>
											</div>
											<div class="mint-assoc-card__actions">
												<button type="button" class="mint-builder-btn-publish" :disabled="!attach.quizId || attaching" @click="attachStandaloneQuestionToQuiz()">
													<span x-text="attaching ? '<?php echo esc_js( __( 'Saving…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Save', 'mint-lms' ) ); ?>'"></span>
												</button>
												<button type="button" class="mint-assoc-card__cancel" :disabled="attaching" @click="cancelChangeAssociation()"><?php esc_html_e( 'Cancel', 'mint-lms' ); ?></button>
											</div>
										</div>
									</template>
								</div>

								<div class="mint-mt-8 mint-flex mint-flex-wrap mint-items-center mint-justify-between mint-gap-4 mint-border-t mint-border-rule mint-pt-5">
									<button type="button" class="mint-builder-delete" @click="deleteActiveQuestion()"><?php esc_html_e( 'Delete question', 'mint-lms' ); ?></button>
									<button
										type="button"
										class="mint-builder-btn-publish !mint-h-[38px] !mint-rounded-[9px] !mint-px-4 !mint-text-[15px]"
										@click="saveActiveQuestion()"
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
					<div class="mint-builder-editor__canvas">
						<nav class="mint-flex mint-items-center mint-gap-2.5 mint-text-[15px] mint-text-ink-2" aria-label="<?php echo esc_attr__( 'Lesson location', 'mint-lms' ); ?>">
							<template x-if="breadcrumbCourseTitle()">
								<span class="mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2.5">
									<a
										class="mint-breadcrumb__link mint-truncate"
										:href="parentCourseEditorUrl()"
										@click="goToParentCourse($event)"
										x-text="breadcrumbCourseTitle()"
									></a>
									<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
								</span>
							</template>
							<template x-if="breadcrumbSectionTitle()">
								<span class="mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2.5">
									<button
										type="button"
										class="mint-breadcrumb__link mint-truncate"
										@click="selectItem('section', selected.sectionId)"
										x-text="breadcrumbSectionTitle()"
									></button>
									<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
								</span>
							</template>
							<span class="mint-breadcrumb__current" x-text="selectedLesson?.title || breadcrumbLessonLabel()"></span>
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
							<button
								type="button"
								class="mint-segmented__item"
								x-show="!isStandalone"
								:class="lessonTab === 'quiz' ? 'is-active' : ''"
								@click="openLessonQuizEditor(selectedLesson.id, selected.sectionId)"
							>
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

						<div class="mint-mt-8 mint-border-t mint-border-rule mint-pt-5">
							<div
								class="mint-assoc-card mint-mb-5"
								x-show="isStandalone"
								x-cloak
							>
								<h2 class="mint-assoc-card__title"><?php esc_html_e( 'Add to a course', 'mint-lms' ); ?></h2>
								<p class="mint-assoc-card__copy"><?php esc_html_e( 'Attach this lesson (and its quiz, if any) to a course. Section is optional.', 'mint-lms' ); ?></p>
								<label class="mint-assoc-card__label"><?php esc_html_e( 'Course', 'mint-lms' ); ?></label>
								<div
									class="mint-assoc-select"
									:class="open ? 'is-open' : ''"
									x-data="{ open: false }"
									@click.outside="open = false"
								>
									<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
										<span
											class="mint-assoc-select__value"
											:class="!attach.courseId ? 'is-placeholder' : ''"
											x-text="attachCourseLabel() || 'Select a course…'"
										></span>
										<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
									</button>
									<div class="mint-assoc-select__menu" x-show="open" x-cloak>
										<button
											type="button"
											class="mint-assoc-select__option"
											:class="!attach.courseId ? 'is-active' : ''"
											@click="pickAttachCourse(0); open = false"
										><?php esc_html_e( 'Select a course…', 'mint-lms' ); ?></button>
										<template x-for="c in attachCourses" :key="'ed-c-' + c.id">
											<button
												type="button"
												class="mint-assoc-select__option"
												:class="Number(attach.courseId) === Number(c.id) ? 'is-active' : ''"
												@click="pickAttachCourse(c.id); open = false"
												x-text="c.title"
											></button>
										</template>
									</div>
								</div>
								<template x-if="attach.courseId">
									<div class="mint-mt-3">
										<label class="mint-assoc-card__label"><?php esc_html_e( 'Section', 'mint-lms' ); ?></label>
										<div
											class="mint-assoc-select"
											:class="open ? 'is-open' : ''"
											x-data="{ open: false }"
											@click.outside="open = false"
										>
											<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
												<span
													class="mint-assoc-select__value"
													x-text="attachSectionLabel() || '<?php echo esc_js( __( 'No section', 'mint-lms' ) ); ?>'"
												></span>
												<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
											</button>
											<div class="mint-assoc-select__menu" x-show="open" x-cloak>
												<button
													type="button"
													class="mint-assoc-select__option"
													:class="!attach.sectionId ? 'is-active' : ''"
													@click="pickAttachSection(0); open = false"
												><?php esc_html_e( 'No section', 'mint-lms' ); ?></button>
												<template x-for="s in attachSections" :key="'ed-s-' + s.id">
													<button
														type="button"
														class="mint-assoc-select__option"
														:class="Number(attach.sectionId) === Number(s.id) ? 'is-active' : ''"
														@click="pickAttachSection(s.id); open = false"
														x-text="s.title"
													></button>
												</template>
											</div>
										</div>
										<button
											type="button"
											class="mint-builder-btn-publish mint-mt-3"
											@click="attachStandaloneToCourse()"
											:disabled="!attach.courseId || attaching"
										>
											<span x-text="attaching ? '<?php echo esc_js( __( 'Adding…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Add to course', 'mint-lms' ) ); ?>'"></span>
										</button>
									</div>
								</template>
							</div>
							<button type="button" class="mint-builder-delete" @click="deleteLesson(selectedLesson.id, selected.sectionId)"><?php esc_html_e( 'Delete lesson', 'mint-lms' ); ?></button>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>

	<?php echo ( new MintLMS\Infrastructure\Admin\ViewRenderer() )->component( 'toast' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
