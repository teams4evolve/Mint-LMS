<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

/** @var MintLMS\Infrastructure\Admin\ViewRenderer $renderer */

$hue_map = array(
	array( 'var(--mint-hue-1-bg)', 'var(--mint-hue-1-ink)' ),
	array( 'var(--mint-hue-2-bg)', 'var(--mint-hue-2-ink)' ),
	array( 'var(--mint-hue-3-bg)', 'var(--mint-hue-3-ink)' ),
	array( 'var(--mint-hue-4-bg)', 'var(--mint-hue-4-ink)' ),
	array( 'var(--mint-hue-5-bg)', 'var(--mint-hue-5-ink)' ),
	array( 'var(--mint-hue-6-bg)', 'var(--mint-hue-6-ink)' ),
);
?>
<div
	x-data="coursesList()"
	x-init="init()"
	class="mint-list-shell"
>
	<div class="mint-list-head">
		<div class="mint-list-head__row">
			<div>
				<h1 class="mint-page-title"><?php esc_html_e( 'Courses', 'mint-lms' ); ?></h1>
				<p class="mint-page-subtitle" x-cloak x-show="!loading && !error">
					<span x-text="total"></span> <?php esc_html_e( 'courses', 'mint-lms' ); ?>
					<span class="mint-page-subtitle__sep">·</span>
					<span x-text="courses.filter(c => c.status === 'published').length"></span> <?php esc_html_e( 'live', 'mint-lms' ); ?>
				</p>
			</div>
			<input
				type="text"
				class="mint-search-input"
				x-model="search"
				@input.debounce.400ms="loadCourses(1)"
				placeholder="<?php echo esc_attr__( 'Search courses…', 'mint-lms' ); ?>"
			/>
		</div>

		<div class="mint-filter-tabs" role="tablist">
			<?php
			$tabs = array(
				'all'       => __( 'All', 'mint-lms' ),
				'published' => __( 'Live', 'mint-lms' ),
				'draft'     => __( 'Drafts', 'mint-lms' ),
				'archived'  => __( 'Hidden', 'mint-lms' ),
			);
			foreach ( $tabs as $value => $label ) :
				?>
				<button
					type="button"
					role="tab"
					class="mint-filter-tab"
					@click="setFilter('<?php echo esc_js( $value ); ?>')"
					:aria-selected="statusFilter === '<?php echo esc_js( $value ); ?>'"
					:class="{ 'is-active': statusFilter === '<?php echo esc_js( $value ); ?>' }"
				><?php echo esc_html( $label ); ?></button>
			<?php endforeach; ?>
		</div>
	</div>

	<div x-show="loading" x-cloak class="mint-list-block">
		<?php
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'skeleton',
			array( 'lines' => 5 )
		);
		?>
	</div>

	<!-- Error state -->
	<div x-show="error && !loading" x-cloak class="mint-list-block">
		<?php
		$mintlms_retry_button = $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'button',
			array(
				'variant' => 'secondary',
				'label'   => esc_html__( 'Try again', 'mint-lms' ),
				'type'    => 'button',
				'attrs'   => '@click="loadCourses(page)"',
			)
		);
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'error-state',
			array(
				'title'        => esc_html__( 'Could not load courses', 'mint-lms' ),
				'messageAttrs' => 'x-text="error"',
				'retry'        => $mintlms_retry_button, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer component HTML.
			)
		);
		?>
	</div>

	<div x-show="!loading && !error" x-cloak class="mint-list-block" style="margin-top:16px">

		<template x-if="courses.length === 0 && !loading">
			<div class="mint-list-empty">
				<p class="mint-list-empty__title"><?php esc_html_e( 'No courses yet', 'mint-lms' ); ?></p>
				<p class="mint-list-empty__text"><?php esc_html_e( 'Create your first course to start building your curriculum.', 'mint-lms' ); ?></p>
				<button type="button" class="mint-btn mint-btn--primary" @click="createCourse()"><?php esc_html_e( 'Create your first course', 'mint-lms' ); ?></button>
			</div>
		</template>

		<template x-if="courses.length > 0">
			<div>
				<div class="mint-courses-list-grid mint-courses-list-grid--header">
					<span><?php esc_html_e( 'Course', 'mint-lms' ); ?></span>
					<span><?php esc_html_e( 'Status', 'mint-lms' ); ?></span>
					<span class="mint-text-right"><?php esc_html_e( 'Students', 'mint-lms' ); ?></span>
					<span class="mint-text-right"><?php esc_html_e( 'Finished', 'mint-lms' ); ?></span>
					<span class="mint-text-right"><?php esc_html_e( 'Edited', 'mint-lms' ); ?></span>
				</div>

				<template x-for="(course, idx) in courses" :key="course.id">
					<a
						:href="builderUrl(course.id)"
						class="mint-row mint-courses-list-grid mint-courses-list-grid--row"
						tabindex="0"
					>
						<div class="mint-course-cell">
							<div
								class="mint-tile mint-tile--md"
								:style="'background:' + tileColor(idx).bg + ';color:' + tileColor(idx).ink"
								x-text="course.title ? course.title.charAt(0).toUpperCase() : '?'"
								style="border-radius:12px"
							></div>
							<div style="min-width:0">
								<div class="mint-t-h3 mint-truncate" x-text="course.title"></div>
								<div class="mint-t-sm mint-text-secondary" style="margin-top:2px" x-text="course.lessonCount ? course.lessonCount + ' lessons' : ''"></div>
							</div>
						</div>

						<div>
							<span class="mint-badge" :class="statusBadgeClass(course.status)" x-text="statusLabel(course.status)"></span>
						</div>

						<div class="mint-t-row mint-tabular mint-text-right" x-text="course.studentCount || '—'"></div>

						<div class="mint-list-finished-col">
							<div class="mint-progress">
								<div class="mint-progress__fill" :style="'width:' + (course.completionRate || 0) + '%'"></div>
							</div>
							<span class="mint-t-base mint-tabular" x-text="(course.completionRate || 0) + '%'"></span>
						</div>

						<div class="mint-t-base mint-tabular mint-text-secondary mint-text-right" x-text="formatDate(course.updatedAt)"></div>
					</a>
				</template>

				<div class="mint-list-footer">
					<?php esc_html_e( 'Showing all', 'mint-lms' ); ?> <span x-text="total"></span> <?php esc_html_e( 'courses', 'mint-lms' ); ?>
				</div>

				<div x-show="totalPages > 1" class="mint-list-pagination">
					<p class="mint-list-pagination__info">
						<?php esc_html_e( 'Page', 'mint-lms' ); ?> <span x-text="page"></span> <?php esc_html_e( 'of', 'mint-lms' ); ?> <span x-text="totalPages"></span>
					</p>
					<div class="mint-list-pagination__actions">
						<button type="button" class="mint-btn mint-btn--secondary mint-btn--sm" @click.prevent="loadCourses(page - 1)" :disabled="page <= 1"><?php esc_html_e( 'Previous', 'mint-lms' ); ?></button>
						<button type="button" class="mint-btn mint-btn--secondary mint-btn--sm" @click.prevent="loadCourses(page + 1)" :disabled="page >= totalPages"><?php esc_html_e( 'Next', 'mint-lms' ); ?></button>
					</div>
				</div>
			</div>
		</template>
	</div>
</div>
