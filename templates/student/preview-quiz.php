<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

/** @var array{title: string, meta: string, questions: list<array{id: int, text: string}>, courseTitle: string} $preview */
$preview     = $preview;
$questions   = is_array( $preview['questions'] ?? null ) ? $preview['questions'] : array();
$courseTitle = trim( (string) ( $preview['courseTitle'] ?? '' ) );
$quizTitle   = trim( (string) ( $preview['title'] ?? '' ) );
$previewKind = 'quiz';
$itemTitle   = $quizTitle;
$sectionTitle = '';
include MINTLMS_PATH . 'views/student/partials/preview-header.php';
?>

<div
	class="mint-mx-auto mint-max-w-[700px] mint-px-4 mint-pb-14 sm:mint-px-6"
	x-data="{ open: true }"
>
	<article class="mint-rounded-[18px] mint-border mint-border-[#E4E1F0] mint-bg-white mint-px-7 mint-py-11 mint-shadow-[0_8px_28px_rgba(28,20,80,0.07)] sm:mint-px-12">
		<h1 class="mint-m-0 mint-text-[32px] mint-font-semibold mint-leading-[38px] mint-tracking-[-0.03em] mint-text-ink">
			<?php echo esc_html( '' !== $quizTitle ? $quizTitle : __( 'Quiz', 'mint-lms' ) ); ?>
		</h1>
		<p class="mint-m-0 mint-mt-2 mint-text-[15px] mint-text-[#5C5C77]">
			<?php esc_html_e( 'This is what this quiz looks like to a student, with its questions below.', 'mint-lms' ); ?>
		</p>

		<div class="mint-mt-[22px] mint-overflow-hidden mint-rounded-[10px] mint-border-[1.5px] mint-border-[#E4E1F0] mint-bg-white">
			<div class="mint-flex mint-items-center mint-gap-3 mint-px-4 mint-py-3.5">
				<div class="mint-shrink-0 mint-rounded-full mint-bg-[#E8FFF3] mint-px-2.5 mint-py-1 mint-text-[10px] mint-font-bold mint-uppercase mint-tracking-[0.08em] mint-text-cta-ink"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></div>
				<div class="mint-min-w-0 mint-flex-1">
					<div class="mint-text-[15px] mint-font-semibold mint-text-[#17222B]"><?php echo esc_html( '' !== $quizTitle ? $quizTitle : __( 'Quiz', 'mint-lms' ) ); ?></div>
					<div class="mint-mt-0.5 mint-text-[13px] mint-text-[#5C5C77]"><?php echo esc_html( (string) ( $preview['meta'] ?? '' ) ); ?></div>
				</div>
				<button
					type="button"
					class="mint-inline-flex mint-h-8 mint-shrink-0 mint-items-center mint-gap-1.5 mint-rounded-lg mint-border-0 mint-bg-cta mint-px-3 mint-text-xs mint-font-semibold mint-text-cta-ink hover:mint-bg-cta-hover focus-visible:mint-outline-none focus-visible:mint-shadow-[0_0_0_3px_rgba(11,79,63,0.35)]"
					@click="open = !open"
				>
					<span x-text="open ? '<?php echo esc_js( __( 'Collapse', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Expand', 'mint-lms' ) ); ?>'"><?php esc_html_e( 'Collapse', 'mint-lms' ); ?></span>
					<svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<path :d="open ? 'M5.5 12 10 7.5 14.5 12' : 'M5.5 8 10 12.5 14.5 8'" d="M5.5 12 10 7.5 14.5 12" />
					</svg>
				</button>
			</div>

			<div class="mint-grid mint-gap-1.5 mint-bg-[#F6FBF8] mint-px-3.5 mint-pb-3 mint-pt-1.5" x-show="open" x-cloak>
				<?php if ( array() === $questions ) : ?>
					<div class="mint-ml-1 mint-rounded-[7px] mint-bg-[#F1FFF9] mint-px-3 mint-py-2.5 mint-text-[13px] mint-text-[#5C5C77]"><?php esc_html_e( 'No questions yet.', 'mint-lms' ); ?></div>
				<?php else : ?>
					<?php foreach ( $questions as $question ) : ?>
						<div class="mint-ml-1 mint-flex mint-items-center mint-gap-2.5 mint-rounded-[7px] mint-bg-[#F1FFF9] mint-px-3 mint-py-2.5">
							<div class="mint-shrink-0 mint-rounded-full mint-bg-white mint-px-1.5 mint-py-0.5 mint-text-[9px] mint-font-bold mint-uppercase mint-tracking-[0.06em] mint-text-cta-ink"><?php esc_html_e( 'Q', 'mint-lms' ); ?></div>
							<div class="mint-text-[13px] mint-text-[#17222B]"><?php echo esc_html( (string) ( $question['text'] ?? '' ) ); ?></div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</article>
</div>
