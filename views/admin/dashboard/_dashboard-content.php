<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Admin\Dto\CreatorDashboardDto;
use MintLMS\Application\Admin\Dto\DashboardCourseRowDto;

/** @var CreatorDashboardDto $dashboard */
/** @var string $firstName */
/** @var bool $showDetails */
/** @var int $userId */

$greeting    = MintLMS\Infrastructure\Ui\MintUi::greeting();
$builderUrl  = admin_url( 'admin.php?page=mint-lms-builder&course_id=' );
$settingsUrl = admin_url( 'admin.php?page=mint-lms-course-edit&course_id=' );
$studentsUrl = admin_url( 'admin.php?page=mint-lms-course-students&course_id=' );
$metrics     = $dashboard->metrics;
?>
<?php if ( $dashboard->isEmpty ) : ?>
<div class="mint-dash-empty">
	<div class="mint-icon-well mint-icon-well--sm">
		<svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="var(--mint-accent)" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="4.5" width="17" height="15" rx="3"/><path d="M9 4.5v15M13 9.5h4M13 13.5h4"/></svg>
	</div>
	<div class="mint-t-display"><?php esc_html_e( "Let's build your", 'mint-lms' ); ?><br /><?php esc_html_e( 'first course', 'mint-lms' ); ?></div>
	<p class="mint-t-body"><?php esc_html_e( 'Most teachers finish theirs in under 30 minutes.', 'mint-lms' ); ?></p>
	<div class="mint-actions-row">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=mint-lms-guided-course' ) ); ?>" class="mint-btn mint-btn--primary"><?php esc_html_e( 'Create your first course', 'mint-lms' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=mint-lms-guided-course' ) ); ?>" class="mint-btn mint-btn--ghost"><?php esc_html_e( 'See an example', 'mint-lms' ); ?></a>
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
	<div class="mint-dash-greeting">
		<div class="mint-t-display"><?php echo esc_html( $greeting . ', ' . $firstName ); ?></div>
		<p class="mint-t-body"><?php echo esc_html( $dashboard->signalLine ); ?></p>
	</div>

	<div class="mint-metric-strip">
		<?php
		$metricCells = array(
			array(
				'label' => __( 'STUDENTS', 'mint-lms' ),
				'value' => (string) $metrics->studentsTotal,
				'delta' => $metrics->studentsDelta > 0 ? sprintf( '+%d this week', $metrics->studentsDelta ) : '',
				'sub'   => sprintf(
					/* translators: %d: published course count */
					_n( 'enrolled across %d course', 'enrolled across %d courses', $metrics->publishedCount, 'mint-lms' ),
					absint( $metrics->publishedCount )
				),
			),
			array(
				'label' => __( 'FINISHED', 'mint-lms' ),
				'value' => null !== $metrics->finishedPct ? round( $metrics->finishedPct ) . '%' : '—',
				'delta' => $metrics->finishedDelta > 0 ? sprintf( '+%d pts', $metrics->finishedDelta ) : '',
				'sub'   => __( 'of students who started', 'mint-lms' ),
			),
			array(
				'label' => __( 'PUBLISHED', 'mint-lms' ),
				'value' => (string) $metrics->publishedCount,
				'delta' => $metrics->draftCount > 0 ? sprintf( '%d drafts', $metrics->draftCount ) : '',
				'sub'   => sprintf(
					/* translators: %d: total course count */
					__( 'of %d courses total', 'mint-lms' ),
					absint( $metrics->publishedCount + $metrics->draftCount )
				),
			),
			array(
				'label' => __( 'LESSONS DONE', 'mint-lms' ),
				'value' => (string) $metrics->lessonsDone7d,
				'delta' => __( 'last 7 days', 'mint-lms' ),
				'sub'   => __( 'across all students', 'mint-lms' ),
			),
		);

		foreach ( $metricCells as $cell ) :
		?>
			<div class="mint-metric-cell">
				<div class="mint-overline"><?php echo esc_html( $cell['label'] ); ?></div>
				<div class="mint-stat-row">
					<div class="mint-t-stat"><?php echo esc_html( $cell['value'] ); ?></div>
					<?php if ( '' !== $cell['delta'] ) : ?>
						<div x-show="showDetails" x-cloak class="mint-t-xs mint-text-muted"><?php echo esc_html( $cell['delta'] ); ?></div>
					<?php endif; ?>
				</div>
				<div class="mint-t-xs mint-text-muted"><?php echo esc_html( $cell['sub'] ); ?></div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( null !== $dashboard->needsYou ) : ?>
	<div class="mint-needs-you">
		<div class="mint-needs-you__main">
			<div class="mint-overline"><?php esc_html_e( 'NEEDS YOU', 'mint-lms' ); ?></div>
			<div style="min-width:0">
				<div class="mint-t-h3"><?php echo esc_html( $dashboard->needsYou->title ); ?></div>
				<div class="mint-t-sm mint-needs-you__meta"><?php echo esc_html( $dashboard->needsYou->blocker . ' · draft · edited ' . $dashboard->needsYou->editedLabel ); ?></div>
			</div>
		</div>
		<a href="<?php echo esc_url( $builderUrl . $dashboard->needsYou->courseId ); ?>" class="mint-btn mint-btn--dark mint-btn--sm"><?php esc_html_e( 'Continue editing', 'mint-lms' ); ?></a>
	</div>
	<?php endif; ?>

	<div class="mint-courses-section">
		<div class="mint-section-header">
			<div class="mint-overline"><?php esc_html_e( 'COURSES', 'mint-lms' ); ?></div>
			<div class="mint-section-header__actions">
				<button type="button" @click="toggleDetails()" class="mint-toggle-details-btn" :class="{ 'is-active': showDetails }">
					<svg class="mint-toggle-details-btn__chevron" width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
					<span x-text="showDetails ? '<?php echo esc_js( __( 'Hide details', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Show details', 'mint-lms' ) ); ?>'"></span>
				</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mint-lms-courses' ) ); ?>" class="mint-t-sm mint-section-link"><?php
				printf(
					/* translators: %d: number of courses */
					esc_html__( 'All %d', 'mint-lms' ),
					absint( count( $dashboard->courses ) )
				);
				?></a>
			</div>
		</div>

		<div class="mint-courses-grid__header">
			<div class="mint-t-xs mint-col-header"><?php esc_html_e( 'Course', 'mint-lms' ); ?></div>
			<div class="mint-t-xs mint-col-header mint-text-right"><?php esc_html_e( 'Students', 'mint-lms' ); ?></div>
			<div class="mint-t-xs mint-col-header"><?php esc_html_e( 'Finished', 'mint-lms' ); ?></div>
			<div class="mint-t-xs mint-col-header mint-text-right"><?php esc_html_e( 'Edited', 'mint-lms' ); ?></div>
		</div>

		<?php foreach ( $dashboard->courses as $course ) :
			$isDraft  = 'published' !== $course->status;
			$pct      = $course->completionPct;
			$pctLabel = null === $pct ? '—' : round( $pct ) . '%';
			$pctWidth = null === $pct ? '0' : round( $pct );
			$barColor = null === $pct ? 'var(--mint-track)' : 'var(--mint-accent)';
			$pctInk   = null === $pct ? 'var(--mint-ink-3)' : 'var(--mint-ink)';
		?>
		<a href="<?php echo esc_url( $builderUrl . $course->id ); ?>" tabindex="0" class="mint-row mint-courses-grid__row">
			<div class="mint-course-cell">
				<div class="mint-tile mint-tile--sm" style="background:<?php echo esc_attr( $course->tileBg ); ?>;color:<?php echo esc_attr( $course->tileInk ); ?>"><?php echo esc_html( $course->initial ); ?></div>
				<div style="min-width:0">
					<div class="mint-t-row mint-truncate"><?php echo esc_html( $course->title ); ?></div>
					<div x-show="showDetails" x-cloak class="mint-t-xs mint-text-muted--sm"><?php echo esc_html( $course->metaLine ); ?></div>
				</div>
			</div>
			<div class="mint-t-base mint-tabular mint-text-secondary mint-text-right"><?php echo esc_html( $course->studentsLabel ); ?></div>
			<div class="mint-progress-inline">
				<div class="mint-progress">
					<div class="mint-progress__fill" style="width:<?php echo esc_attr( $pctWidth ); ?>%;background:<?php echo esc_attr( $barColor ); ?>"></div>
				</div>
				<div class="mint-t-sm mint-tabular mint-progress-inline__value" style="color:<?php echo esc_attr( $pctInk ); ?>"><?php echo esc_html( $pctLabel ); ?></div>
			</div>
			<div class="mint-t-sm mint-tabular mint-text-muted mint-text-right"><?php echo esc_html( $course->editedLabel ); ?></div>
		</a>
		<?php endforeach; ?>
	</div>

	<?php if ( array() !== $dashboard->activity ) : ?>
	<div x-show="showDetails" x-cloak class="mint-activity-section">
		<div class="mint-overline"><?php esc_html_e( 'RECENT ACTIVITY', 'mint-lms' ); ?></div>
		<?php foreach ( $dashboard->activity as $item ) : ?>
		<div class="mint-activity-row">
			<div class="mint-avatar mint-avatar--sm" style="background:<?php echo esc_attr( $item->avatarBg ); ?>;color:<?php echo esc_attr( $item->avatarInk ); ?>"><?php echo esc_html( $item->initials ); ?></div>
			<div class="mint-t-sm mint-activity-row__text"><?php echo esc_html( $item->text ); ?></div>
			<div class="mint-t-xs mint-tabular mint-activity-row__when"><?php echo esc_html( $item->when ); ?></div>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<div x-show="!showDetails" class="mint-section-spacer"></div>
</div>
<?php endif; ?>
