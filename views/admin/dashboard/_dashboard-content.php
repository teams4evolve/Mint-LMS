<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

use MintLMS\Application\Admin\Dto\CreatorDashboardDto;
use MintLMS\Application\Admin\Dto\DashboardCourseRowDto;
use MintLMS\Infrastructure\Admin\ViewRenderer;

/** @var CreatorDashboardDto $dashboard */
/** @var ViewRenderer $renderer */
/** @var string $firstName */
/** @var bool $showDetails */
/** @var int $userId */

$greeting    = MintLMS\Infrastructure\Ui\MintUi::greeting();
$builderUrl  = admin_url( 'admin.php?page=mint-lms-builder&course_id=' );
$settingsUrl = admin_url( 'admin.php?page=mint-lms-course-edit&course_id=' );
$studentsUrl = admin_url( 'admin.php?page=mint-lms-course-students&course_id=' );
$metrics     = $dashboard->metrics;

$hue_tile_classes = array(
	1 => 'mint-bg-hue-1 mint-text-hue-1i',
	2 => 'mint-bg-hue-2 mint-text-hue-2i',
	3 => 'mint-bg-hue-3 mint-text-hue-3i',
	4 => 'mint-bg-hue-4 mint-text-hue-4i',
	5 => 'mint-bg-hue-5 mint-text-hue-5i',
	6 => 'mint-bg-hue-6 mint-text-hue-6i',
);
?>
<?php if ( $dashboard->isEmpty ) : ?>
<div class="mint-py-[120px] mint-pb-[140px] mint-max-w-[520px]">
	<div class="mint-mb-7 mint-flex mint-h-11 mint-w-11 mint-items-center mint-justify-center mint-rounded-xl mint-bg-accent-wash">
		<svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="mint-text-accent" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="4.5" width="17" height="15" rx="3"/><path d="M9 4.5v15M13 9.5h4M13 13.5h4"/></svg>
	</div>
	<div class="mint-text-display mint-font-semibold mint-tracking-tight mint-text-ink"><?php esc_html_e( "Let's build your", 'mint-lms' ); ?><br /><?php esc_html_e( 'first course', 'mint-lms' ); ?></div>
	<p class="mint-mt-4 mint-text-body mint-text-ink-3"><?php esc_html_e( 'Most teachers finish theirs in under 30 minutes.', 'mint-lms' ); ?></p>
	<div class="mint-mt-8 mint-flex mint-items-center mint-gap-3">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=mint-lms-guided-course' ) ); ?>" class="mint-inline-flex mint-h-control-lg mint-items-center mint-justify-center mint-rounded-lg mint-bg-accent mint-px-[18px] mint-text-base mint-font-semibold mint-text-neutral-50 mint-no-underline hover:mint-bg-accent-hover"><?php esc_html_e( 'Create your first course', 'mint-lms' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=mint-lms-guided-course' ) ); ?>" class="mint-inline-flex mint-h-control-lg mint-items-center mint-justify-center mint-rounded-lg mint-bg-transparent mint-px-[18px] mint-text-base mint-font-semibold mint-text-ink-2 mint-no-underline hover:mint-bg-bg-subtle hover:mint-text-ink"><?php esc_html_e( 'See an example', 'mint-lms' ); ?></a>
	</div>
</div>
<?php else : ?>
<div
	x-data="{
		showDetails: <?php echo $showDetails ? 'true' : 'false'; ?>,
		toggleDetails() {
			this.showDetails = !this.showDetails;
			fetch(mintLmsAdmin.restBase + '/user-pref', {
				method: 'POST',
				headers: {'Content-Type':'application/json','X-WP-Nonce': mintLmsAdmin.nonce},
				body: JSON.stringify({key:'mint_lms_show_details', value: this.showDetails ? '1' : '0'})
			}).catch(()=>{});
		}
	}"
>
	<div class="mint-pt-10 mint-pb-12">
		<div class="mint-text-display mint-font-semibold mint-tracking-tight mint-text-ink"><?php echo esc_html( $greeting . ', ' . $firstName ); ?></div>
		<p class="mint-mt-[14px] mint-text-body mint-text-ink-3"><?php echo esc_html( $dashboard->signalLine ); ?></p>
	</div>

	<div class="mint-flex mint-flex-wrap mint-items-stretch mint-border-b mint-border-rule">
		<?php
		$metricCells = array(
			array(
				'label' => __( 'Students', 'mint-lms' ),
				'value' => (string) $metrics->studentsTotal,
				'delta' => $metrics->studentsDelta > 0 ? sprintf( '+%d this week', $metrics->studentsDelta ) : '',
				'sub'   => sprintf(
					/* translators: %d: published course count */
					_n( 'enrolled across %d course', 'enrolled across %d courses', $metrics->publishedCount, 'mint-lms' ),
					absint( $metrics->publishedCount )
				),
			),
			array(
				'label' => __( 'Finished', 'mint-lms' ),
				'value' => null !== $metrics->finishedPct ? round( $metrics->finishedPct ) . '%' : '—',
				'delta' => $metrics->finishedDelta > 0 ? sprintf( '+%d pts', $metrics->finishedDelta ) : '',
				'sub'   => __( 'of students who started', 'mint-lms' ),
			),
			array(
				'label' => __( 'Published', 'mint-lms' ),
				'value' => (string) $metrics->publishedCount,
				'delta' => $metrics->draftCount > 0 ? sprintf( '%d drafts', $metrics->draftCount ) : '',
				'sub'   => sprintf(
					/* translators: %d: total course count */
					__( 'of %d courses total', 'mint-lms' ),
					absint( $metrics->publishedCount + $metrics->draftCount )
				),
			),
			array(
				'label' => __( 'Lessons done', 'mint-lms' ),
				'value' => (string) $metrics->lessonsDone7d,
				'delta' => __( 'last 7 days', 'mint-lms' ),
				'sub'   => __( 'across all students', 'mint-lms' ),
			),
		);

		foreach ( $metricCells as $cellIndex => $cell ) :
		?>
			<div class="mint-min-w-0 mint-flex-[1_1_165px] mint-border-l mint-border-rule-faint mint-px-7 mint-py-6 first:mint-border-l-0 first:mint-pl-0">
				<div class="mint-text-sm mint-font-semibold mint-leading-5 mint-text-hue-1i"><?php echo esc_html( $cell['label'] ); ?></div>
				<div class="mint-mt-3 mint-flex mint-items-baseline mint-gap-2">
					<div class="mint-text-stat mint-font-semibold mint-tracking-tight mint-text-ink"><?php echo esc_html( $cell['value'] ); ?></div>
					<?php if ( '' !== $cell['delta'] ) : ?>
						<div x-show="showDetails" x-cloak class="mint-text-xs mint-text-ink-3"><?php echo esc_html( $cell['delta'] ); ?></div>
					<?php endif; ?>
				</div>
				<div class="mint-text-xs mint-text-ink-3"><?php echo esc_html( $cell['sub'] ); ?></div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( null !== $dashboard->needsYou ) : ?>
	<div class="mint-mt-6 mint-flex mint-flex-wrap mint-items-center mint-justify-between mint-gap-7 mint-rounded-[14px] mint-border mint-border-tint-line mint-bg-tint mint-px-[22px] mint-py-5">
		<div class="mint-flex mint-min-w-0 mint-flex-[1_1_320px] mint-items-baseline mint-gap-3">
			<div class="mint-shrink-0 mint-text-sm mint-font-semibold mint-leading-5 mint-text-accent"><?php esc_html_e( 'Needs you', 'mint-lms' ); ?></div>
			<div class="mint-min-w-0">
				<div class="mint-text-h3 mint-font-semibold mint-text-ink"><?php echo esc_html( $dashboard->needsYou->title ); ?></div>
				<div class="mint-mt-[3px] mint-text-sm mint-text-ink-3"><?php echo esc_html( $dashboard->needsYou->blocker . ' · draft · edited ' . $dashboard->needsYou->editedLabel ); ?></div>
			</div>
		</div>
		<a href="<?php echo esc_url( $builderUrl . $dashboard->needsYou->courseId ); ?>" class="mint-inline-flex mint-h-control-md mint-items-center mint-justify-center mint-rounded-md mint-bg-bg-ink mint-px-[14px] mint-text-sm mint-font-semibold mint-text-neutral-50 mint-no-underline hover:mint-opacity-[0.86]"><?php esc_html_e( 'Continue editing', 'mint-lms' ); ?></a>
	</div>
	<?php endif; ?>

	<div class="mint-pt-11">
		<div class="mint-mb-[6px] mint-flex mint-items-baseline mint-justify-between mint-gap-4">
			<div class="mint-text-sm mint-font-semibold mint-leading-5 mint-text-ink-2"><?php esc_html_e( 'Courses', 'mint-lms' ); ?></div>
			<div class="mint-flex mint-items-center mint-gap-[14px]">
				<button type="button" @click="toggleDetails()" class="mint-inline-flex mint-h-[30px] mint-items-center mint-gap-[5px] mint-rounded-sm mint-border mint-border-control mint-bg-transparent mint-px-[10px] mint-pl-[7px] mint-text-sm mint-font-medium mint-text-ink-3 mint-transition-colors mint-duration-hover hover:mint-bg-bg-subtle" :class="{ 'mint-bg-bg-subtle mint-text-ink': showDetails }">
					<svg class="mint-transition-transform mint-duration-menu" :class="{ 'mint-rotate-180': showDetails }" width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
					<span x-text="showDetails ? '<?php echo esc_js( __( 'Hide details', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Show details', 'mint-lms' ) ); ?>'"></span>
				</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mint-lms-courses' ) ); ?>" class="mint-text-sm mint-font-medium mint-text-ink-3 mint-no-underline"><?php
				printf(
					/* translators: %d: number of courses */
					esc_html__( 'All %d', 'mint-lms' ),
					absint( count( $dashboard->courses ) )
				);
				?></a>
			</div>
		</div>

		<div class="mint-mt-[6px] mint-grid mint-grid-cols-[minmax(0,1fr)_78px_132px_84px] mint-items-center mint-gap-6 mint-rounded-lg mint-bg-tint mint-px-4 mint-py-[13px]">
			<div class="mint-text-xs mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Course', 'mint-lms' ); ?></div>
			<div class="mint-text-xs mint-text-right mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Students', 'mint-lms' ); ?></div>
			<div class="mint-text-xs mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Finished', 'mint-lms' ); ?></div>
			<div class="mint-text-xs mint-text-right mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Edited', 'mint-lms' ); ?></div>
		</div>

		<?php foreach ( $dashboard->courses as $courseIndex => $course ) :
			$isDraft  = 'published' !== $course->status;
			$pct      = $course->completionPct;
			$pctLabel = null === $pct ? '—' : round( $pct ) . '%';
			$pctWidth = null === $pct ? 0 : (int) round( $pct );
			$hueClass = $hue_tile_classes[ ( $courseIndex % 6 ) + 1 ];
		?>
		<a href="<?php echo esc_url( $builderUrl . $course->id ); ?>" tabindex="0" class="mint-grid mint-grid-cols-[minmax(0,1fr)_78px_132px_84px] mint-items-center mint-gap-6 mint-border-t mint-border-[#EDEBF7] mint-px-4 mint-py-[15px] mint-text-inherit mint-no-underline mint-outline-none">
			<div class="mint-flex mint-min-w-0 mint-items-center mint-gap-[14px]">
				<div class="mint-flex mint-h-11 mint-w-11 mint-shrink-0 mint-items-center mint-justify-center mint-rounded-xl mint-text-lg mint-font-bold mint-tracking-tight <?php echo esc_attr( $hueClass ); ?>"><?php echo esc_html( $course->initial ); ?></div>
				<div class="mint-min-w-0">
					<div class="mint-truncate mint-text-row mint-font-semibold mint-text-ink"><?php echo esc_html( $course->title ); ?></div>
					<div x-show="showDetails" x-cloak class="mint-mt-[3px] mint-text-xs mint-text-ink-3"><?php echo esc_html( $course->metaLine ); ?></div>
				</div>
			</div>
			<div class="mint-tabular-nums mint-text-right mint-text-base mint-text-ink-2"><?php echo esc_html( $course->studentsLabel ); ?></div>
			<div class="mint-flex mint-items-center mint-gap-[10px]">
				<div class="mint-min-w-[44px] mint-flex-1 mint-h-[9px] mint-overflow-hidden mint-rounded-full mint-bg-bg-track">
					<div class="mint-h-full mint-rounded-full <?php echo null === $pct ? 'mint-w-0 mint-bg-bg-track' : 'mint-bg-accent'; ?> mint-w-[<?php echo esc_attr( (string) $pctWidth ); ?>%]"></div>
				</div>
				<div class="mint-w-[42px] mint-tabular-nums mint-text-right mint-text-sm mint-font-medium <?php echo null === $pct ? 'mint-text-ink-3' : 'mint-text-ink'; ?>"><?php echo esc_html( $pctLabel ); ?></div>
			</div>
			<div class="mint-tabular-nums mint-text-right mint-text-sm mint-text-ink-3"><?php echo esc_html( $course->editedLabel ); ?></div>
		</a>
		<?php endforeach; ?>
	</div>

	<?php if ( array() !== $dashboard->activity ) : ?>
	<div x-show="showDetails" x-cloak class="mint-px-0 mint-pb-24 mint-pt-12">
		<div class="mint-mb-[10px] mint-text-sm mint-font-semibold mint-leading-5 mint-text-ink-2"><?php esc_html_e( 'Recent activity', 'mint-lms' ); ?></div>
		<?php foreach ( $dashboard->activity as $activityIndex => $item ) :
			$activityHue = $hue_tile_classes[ ( $activityIndex % 6 ) + 1 ];
		?>
		<div class="mint-grid mint-grid-cols-[32px_minmax(0,1fr)_92px] mint-items-center mint-gap-[14px] mint-border-t mint-border-rule-soft mint-py-[13px]">
			<div class="mint-flex mint-h-8 mint-w-8 mint-shrink-0 mint-items-center mint-justify-center mint-rounded-full mint-text-[13px] mint-font-bold <?php echo esc_attr( $activityHue ); ?>"><?php echo esc_html( $item->initials ); ?></div>
			<div class="mint-min-w-0 mint-truncate mint-text-sm mint-text-ink-2"><?php echo esc_html( $item->text ); ?></div>
			<div class="mint-tabular-nums mint-text-right mint-text-xs mint-text-[#82829C]"><?php echo esc_html( $item->when ); ?></div>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<div x-show="!showDetails" class="mint-pb-24"></div>
</div>
<?php endif; ?>
