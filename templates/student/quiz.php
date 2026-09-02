<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

use MintLMS\Application\Quiz\Dto\QuizDto;

/** @var QuizDto|null $quiz */
/** @var bool $quizRequired */
/** @var bool $hasPassedQuiz */
/** @var bool $isEnrolled */
$quiz          = $quiz ?? null;
$quizRequired  = $quizRequired ?? false;
$hasPassedQuiz = $hasPassedQuiz ?? true;
$isEnrolled    = $isEnrolled ?? false;

if ( ! $quizRequired || null === $quiz || ! $isEnrolled ) {
	return;
}
?>
<div
	class="mint-quiz-panel"
	style="border:1px solid var(--mint-border);border-radius:var(--mint-r-xl);padding:24px;margin-bottom:32px"
	x-data="mintLessonQuiz({
		quizId: <?php echo (int) $quiz->id; ?>,
		passPercent: <?php echo (int) $quiz->passPercent; ?>,
		hasPassed: <?php echo $hasPassedQuiz ? 'true' : 'false'; ?>,
		questions: <?php echo wp_json_encode( array_map( static fn( $q ) => $q->toArray(), $quiz->questions ) ); ?>
	})"
>
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
		<h2 class="mint-t-over" style="margin:0"><?php echo esc_html( $quiz->title ); ?></h2>
		<span style="font-size:14px;color:var(--mint-ink-2)">
			<?php
			printf(
				/* translators: %d: minimum pass percentage */
				esc_html__( 'Pass: %d%%', 'mint-lms' ),
				(int) $quiz->passPercent
			);
			?>
		</span>
	</div>

	<template x-if="hasPassed">
		<div style="padding:16px;background:#E8F6EE;border-radius:8px;margin-bottom:16px">
			<p style="margin:0;font-size:15px;color:#0B7A57;font-weight:500" x-text="mintLmsStudent.i18n.quizPassed"></p>
		</div>
	</template>

	<template x-if="!hasPassed && !submitted">
		<div style="display:flex;flex-direction:column;gap:20px">
			<template x-for="(question, qIndex) in questions" :key="question.id">
				<div style="padding:16px;background:var(--mint-bg-subtle);border-radius:8px">
					<p style="font-size:16px;font-weight:600;color:var(--mint-ink);margin:0 0 12px" x-text="(qIndex + 1) + '. ' + question.prompt"></p>
					<template x-if="question.type === 'true_false'">
						<div style="display:flex;gap:12px">
							<label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:15px">
								<input type="radio" :name="'q-' + question.id" value="true" x-model="answers[question.id]" />
								<?php esc_html_e( 'True', 'mint-lms' ); ?>
							</label>
							<label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:15px">
								<input type="radio" :name="'q-' + question.id" value="false" x-model="answers[question.id]" />
								<?php esc_html_e( 'False', 'mint-lms' ); ?>
							</label>
						</div>
					</template>
					<template x-if="question.type === 'mcq'">
						<div style="display:flex;flex-direction:column;gap:8px">
							<template x-for="(option, oIndex) in question.options" :key="oIndex">
								<label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:15px">
									<input type="radio" :name="'q-' + question.id" :value="option" x-model="answers[question.id]" />
									<span x-text="option"></span>
								</label>
							</template>
						</div>
					</template>
				</div>
			</template>
			<div>
				<button type="button" class="mint-btn mint-btn--primary mint-btn--sm" @click="submitQuiz()" :disabled="submitting">
					<span x-text="submitting ? mintLmsStudent.i18n.submittingQuiz : mintLmsStudent.i18n.submitQuiz"></span>
				</button>
			</div>
		</div>
	</template>

	<template x-if="submitted && !hasPassed">
		<div>
			<div style="padding:16px;background:#FEF3F2;border-radius:8px;margin-bottom:16px">
				<p style="margin:0 0 4px;font-size:15px;color:#B42318;font-weight:500" x-text="mintLmsStudent.i18n.quizFailed"></p>
				<p style="margin:0;font-size:14px;color:var(--mint-ink-2)" x-text="Math.round(scorePercent) + '% — ' + mintLmsStudent.i18n.quizRetry"></p>
			</div>
			<button type="button" class="mint-btn mint-btn--secondary mint-btn--sm" @click="retry()">
				<?php esc_html_e( 'Try again', 'mint-lms' ); ?>
			</button>
		</div>
	</template>

	<p x-show="error" x-text="error" style="margin-top:12px;font-size:14px;color:#B42318"></p>
</div>
