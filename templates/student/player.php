<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Student\Dto\StudentPlayerContextDto;
use MintLMS\Infrastructure\Ui\MintUi;

/** @var StudentPlayerContextDto $context */
/** @var callable(int): string $lessonUrl */
$context       = $context;
$lessonUrl     = $lessonUrl;
$dashboardUrl  = $dashboardUrl ?? home_url( '/' );
$attachmentUrl = $attachmentUrl ?? null;
$videoEmbed    = $videoEmbed ?? '';

$completedIds = $context->completedLessonIds;
$lesson       = $context->currentLesson;
$pct          = (int) round( $context->progressPct );
?>
<div
	x-data="mintCoursePlayer({
		courseId: <?php echo (int) $context->courseId; ?>,
		lessonId: <?php echo (int) $lesson->id; ?>,
		progressPct: <?php echo (float) $context->progressPct; ?>,
		isComplete: <?php echo $context->isCurrentLessonComplete ? 'true' : 'false'; ?>,
		completedIds: <?php echo wp_json_encode( $completedIds ); ?>,
		isEnrolled: <?php echo $context->isEnrolled ? 'true' : 'false'; ?>,
		quizRequired: <?php echo $context->quizRequired ? 'true' : 'false'; ?>,
		hasPassedQuiz: <?php echo $context->hasPassedQuiz ? 'true' : 'false'; ?>
	})"
>
	<div class="mint-player-header">
		<div class="mint-player-header__trail">
			<a href="<?php echo esc_url( $dashboardUrl ); ?>" class="mint-breadcrumb__link"><?php echo esc_html( $context->courseTitle ); ?></a>
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--mint-ink-3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0"><polyline points="9 18 15 12 9 6"/></svg>
			<span class="mint-breadcrumb__current"><?php echo esc_html( $lesson->title ); ?></span>
		</div>
		<?php if ( $context->isEnrolled ) : ?>
			<div class="mint-player-progress">
				<span class="mint-player-progress__label" x-text="Math.round(progressPct) + '% complete'"></span>
				<div class="mint-player-progress__track">
					<div class="mint-progress__fill" style="height:100%;transition:width 300ms ease-out" :style="'width:' + Math.round(progressPct) + '%'"></div>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<div class="mint-student-layout mint-student-layout--player">

		<main class="mint-player-main">

			<?php if ( '' !== $videoEmbed ) : ?>
				<div class="mint-video-frame">
					<?php echo $videoEmbed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php else : ?>
				<div class="mint-video-frame mint-video-frame--empty">
					<div class="mint-video-play-btn">
						<svg width="28" height="28" viewBox="0 0 24 24" fill="#FFFFFF" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
					</div>
				</div>
			<?php endif; ?>

			<h1 class="mint-lesson-heading"><?php echo esc_html( $lesson->title ); ?></h1>

			<?php if ( '' !== $lesson->content ) : ?>
				<div class="mint-t-prose mint-lesson-prose">
					<?php echo wp_kses_post( wpautop( $lesson->content ) ); ?>
				</div>
			<?php endif; ?>

			<?php if ( is_string( $attachmentUrl ) && '' !== $attachmentUrl ) : ?>
				<div style="margin-bottom:24px">
					<a href="<?php echo esc_url( $attachmentUrl ); ?>" class="mint-btn mint-btn--secondary mint-btn--sm" download>
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
						<?php esc_html_e( 'Download attachment', 'mint-lms' ); ?>
					</a>
				</div>
			<?php endif; ?>

			<?php
			$quiz          = $context->quiz;
			$quizRequired  = $context->quizRequired;
			$hasPassedQuiz = $context->hasPassedQuiz;
			$isEnrolled    = $context->isEnrolled;
			include MINTLMS_PATH . 'templates/student/quiz.php';
			?>

			<div class="mint-lesson-nav">
				<div>
					<?php if ( null !== $context->previousLessonId ) : ?>
						<a href="<?php echo esc_url( $lessonUrl( $context->previousLessonId ) ); ?>" class="mint-btn mint-btn--secondary">
							<?php esc_html_e( '← Previous', 'mint-lms' ); ?>
						</a>
					<?php endif; ?>
				</div>
				<div>
					<?php if ( $context->isEnrolled ) : ?>
						<button
							type="button"
							class="mint-btn mint-btn--primary"
							:class="{ 'mint-btn--primary': !isComplete }"
							:style="isComplete ? 'background:#E8F6EE;color:#0B7A57;cursor:default' : ''"
							:disabled="isComplete || marking || (quizRequired && !hasPassedQuiz)"
							@click="markComplete()"
						>
							<span x-text="marking ? mintLmsStudent.i18n.marking : (isComplete ? mintLmsStudent.i18n.completed : (<?php echo null !== $context->nextLessonId ? 'true' : 'false'; ?> ? '<?php echo esc_js( __( 'Mark complete & continue', 'mint-lms' ) ); ?>' : mintLmsStudent.i18n.markComplete))"></span>
						</button>
					<?php endif; ?>
				</div>
			</div>
		</main>

		<aside class="mint-student-aside mint-student-aside--curriculum">
			<div class="mint-overline mint-curriculum-overline"><?php esc_html_e( 'CURRICULUM', 'mint-lms' ); ?></div>

			<?php foreach ( $context->structure->sections as $section ) : ?>
				<div class="mint-curriculum-block">
					<h3 class="mint-curriculum-section-label"><?php echo esc_html( $section->title ); ?></h3>
					<?php foreach ( $section->lessons as $sectionLesson ) :
						$isCurrent   = $sectionLesson->id === $lesson->id;
						$isCompleted = in_array( $sectionLesson->id, $completedIds, true );
						$accessible  = ( $context->isEnrolled || $sectionLesson->isPreview ) && ! $sectionLesson->isDripLocked;
						$hasContent  = '' !== $sectionLesson->content || null !== $sectionLesson->videoUrl || $sectionLesson->isDripLocked;
						$linkClass   = 'mint-curriculum-link' . ( $isCurrent ? ' is-current' : '' );
					?>
						<?php if ( $accessible && $hasContent ) : ?>
							<a
								href="<?php echo esc_url( $lessonUrl( $sectionLesson->id ) ); ?>"
								class="<?php echo esc_attr( $linkClass ); ?>"
								<?php echo $isCurrent ? 'aria-current="page"' : ''; ?>
							>
								<div
									class="mint-lesson-icon mint-lesson-icon--sm"
									:style="completedIds.includes(<?php echo (int) $sectionLesson->id; ?>) ? 'background:#E8F6EE' : '<?php echo $isCurrent ? 'background:var(--mint-accent)' : 'background:var(--mint-bg-subtle)'; ?>'"
								>
									<template x-if="completedIds.includes(<?php echo (int) $sectionLesson->id; ?>)">
										<svg width="14" height="14" viewBox="0 0 20 20" fill="#0B7A57" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
									</template>
									<template x-if="!completedIds.includes(<?php echo (int) $sectionLesson->id; ?>)">
										<?php if ( $isCurrent ) : ?>
											<svg width="12" height="12" viewBox="0 0 24 24" fill="#FFFFFF" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
										<?php else : ?>
											<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--mint-ink-3)" stroke-width="2" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
										<?php endif; ?>
									</template>
								</div>
								<span class="mint-curriculum-link__title"><?php echo esc_html( $sectionLesson->title ); ?></span>
							</a>
						<?php else : ?>
							<div class="mint-curriculum-locked" title="<?php echo esc_attr( $sectionLesson->dripMessage ?? '' ); ?>">
								<div class="mint-lesson-icon mint-lesson-icon--sm">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--mint-ink-3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
								</div>
								<span class="mint-curriculum-locked__title"><?php echo esc_html( $sectionLesson->title ); ?></span>
								<?php if ( $sectionLesson->isDripLocked && is_string( $sectionLesson->dripMessage ) && '' !== $sectionLesson->dripMessage ) : ?>
									<span class="mint-curriculum-locked__meta"><?php echo esc_html( $sectionLesson->dripMessage ); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</aside>
	</div>
</div>
