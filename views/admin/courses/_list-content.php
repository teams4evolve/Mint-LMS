<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

/** @var MintLMS\Infrastructure\Admin\ViewRenderer $renderer */
?>
<div
	x-data="coursesList()"
	x-init="init()"
	class="mint-mx-auto mint-max-w-content mint-px-7"
>
	<div class="mint-pt-10">
		<div class="mint-flex mint-flex-wrap mint-items-start mint-justify-between mint-gap-6">
			<div>
				<h1 class="mint-m-0 mint-text-[48px] mint-font-semibold mint-leading-[52px] mint-tracking-[-0.04em] mint-text-ink"><?php esc_html_e( 'Courses', 'mint-lms' ); ?></h1>
				<p class="mint-mt-[10px] mint-text-[19px] mint-leading-7 mint-text-ink-2" x-cloak x-show="!loading && !error">
					<span x-text="total"></span> <?php esc_html_e( 'courses', 'mint-lms' ); ?>
					<span class="mint-mx-1">·</span>
					<span x-text="courses.filter(c => c.status === 'published').length"></span> <?php esc_html_e( 'live', 'mint-lms' ); ?>
				</p>
			</div>
			<input
				type="text"
				class="mint-search-input mint-h-control-md mint-min-w-[220px] mint-rounded-md mint-border-[1.5px] mint-border-[#B9B6CE] mint-bg-bg mint-px-[13px] mint-text-sm mint-text-ink focus:mint-border-accent focus:mint-shadow-focus focus:mint-outline-none"
				x-model="search"
				@input.debounce.400ms="loadCourses(1)"
				placeholder="<?php echo esc_attr__( 'Search courses…', 'mint-lms' ); ?>"
			/>
		</div>

		<div class="mint-mt-7 mint-flex mint-items-center mint-gap-1" role="tablist">
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
					class="mint-h-control-md mint-cursor-pointer mint-rounded-md mint-border-[1.5px] mint-border-transparent mint-bg-transparent mint-px-[14px] mint-text-sm mint-font-medium mint-leading-[36px] mint-text-ink-2 mint-transition-colors mint-duration-hover hover:mint-bg-bg-subtle"
					@click="setFilter('<?php echo esc_js( $value ); ?>')"
					:aria-selected="statusFilter === '<?php echo esc_js( $value ); ?>'"
					:class="{ 'mint-border-ink mint-bg-bg mint-font-semibold mint-text-ink': statusFilter === '<?php echo esc_js( $value ); ?>' }"
				><?php echo esc_html( $label ); ?></button>
			<?php endforeach; ?>
		</div>
	</div>

	<div x-show="loading" x-cloak class="mint-mt-6">
		<?php
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'skeleton',
			array( 'lines' => 5 )
		);
		?>
	</div>

	<div x-show="error && !loading" x-cloak class="mint-mt-6">
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

	<div x-show="!loading && !error" x-cloak class="mint-mt-4">

		<template x-if="courses.length === 0 && !loading">
			<div class="mint-py-16 mint-text-center">
				<p class="mint-m-0 mint-mb-2 mint-text-h3 mint-font-semibold mint-text-ink"><?php esc_html_e( 'No courses yet', 'mint-lms' ); ?></p>
				<p class="mint-m-0 mint-mb-6 mint-text-base mint-text-ink-2"><?php esc_html_e( 'Create your first course to start building your curriculum.', 'mint-lms' ); ?></p>
				<?php
				echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
					'button',
					array(
						'variant' => 'primary',
						'label'   => esc_html__( 'Create your first course', 'mint-lms' ),
						'type'    => 'button',
						'attrs'   => '@click="createCourse()"',
					)
				);
				?>
			</div>
		</template>

		<template x-if="courses.length > 0">
			<div>
				<div class="mint-grid mint-grid-cols-[minmax(0,1fr)_92px_80px_120px_84px] mint-items-center mint-gap-4 mint-rounded-lg mint-bg-tint mint-px-4 mint-py-[13px] mint-text-sm mint-font-semibold mint-text-ink-2">
					<span><?php esc_html_e( 'Course', 'mint-lms' ); ?></span>
					<span><?php esc_html_e( 'Status', 'mint-lms' ); ?></span>
					<span class="mint-text-right"><?php esc_html_e( 'Students', 'mint-lms' ); ?></span>
					<span class="mint-text-right"><?php esc_html_e( 'Finished', 'mint-lms' ); ?></span>
					<span class="mint-text-right"><?php esc_html_e( 'Edited', 'mint-lms' ); ?></span>
				</div>

				<template x-for="(course, idx) in courses" :key="course.id">
					<a
						:href="builderUrl(course.id)"
						class="mint-grid mint-grid-cols-[minmax(0,1fr)_92px_80px_120px_84px] mint-items-center mint-gap-4 mint-rounded-md mint-border-t mint-border-[#EDEBF7] mint-px-4 mint-py-4 mint-text-inherit mint-no-underline mint-outline-none"
						tabindex="0"
					>
						<div class="mint-flex mint-min-w-0 mint-items-center mint-gap-[14px]">
							<div
								class="mint-flex mint-h-[46px] mint-w-[46px] mint-shrink-0 mint-items-center mint-justify-center mint-rounded-xl mint-text-[19px] mint-font-bold mint-tracking-tight"
								:class="hueClass(idx)"
								x-text="course.title ? course.title.charAt(0).toUpperCase() : '?'"
							></div>
							<div class="mint-min-w-0">
								<div class="mint-truncate mint-text-h3 mint-font-semibold mint-text-ink" x-text="course.title"></div>
								<div class="mint-mt-[2px] mint-text-sm mint-text-ink-2" x-text="course.lessonCount ? course.lessonCount + ' lessons' : ''"></div>
							</div>
						</div>

						<div>
							<span :class="statusBadgeClass(course.status)" x-text="statusLabel(course.status)"></span>
						</div>

						<div class="mint-tabular-nums mint-text-right mint-text-row mint-text-ink" x-text="course.studentCount || '—'"></div>

						<div class="mint-flex mint-flex-col mint-items-end mint-gap-[6px] mint-text-right">
							<progress
								class="mint-progress-bar mint-h-[9px] mint-w-full mint-appearance-none mint-overflow-hidden mint-rounded-full mint-bg-bg-track [&::-webkit-progress-bar]:mint-rounded-full [&::-webkit-progress-bar]:mint-bg-bg-track [&::-webkit-progress-value]:mint-rounded-full [&::-webkit-progress-value]:mint-bg-accent [&::-moz-progress-bar]:mint-rounded-full [&::-moz-progress-bar]:mint-bg-accent"
								:value="Math.min(100, Math.max(0, Number(course.completionRate) || 0))"
								max="100"
							></progress>
							<span class="mint-tabular-nums mint-text-base" x-text="(course.completionRate || 0) + '%'"></span>
						</div>

						<div class="mint-tabular-nums mint-text-right mint-text-base mint-text-ink-2" x-text="formatDate(course.updatedAt)"></div>
					</a>
				</template>

				<div class="mint-px-4 mint-pt-5 mint-text-base mint-text-ink-2">
					<?php esc_html_e( 'Showing all', 'mint-lms' ); ?> <span x-text="total"></span> <?php esc_html_e( 'courses', 'mint-lms' ); ?>
				</div>

				<div x-show="totalPages > 1" class="mint-flex mint-items-center mint-justify-between mint-py-4">
					<p class="mint-m-0 mint-text-sm mint-text-ink-2">
						<?php esc_html_e( 'Page', 'mint-lms' ); ?> <span x-text="page"></span> <?php esc_html_e( 'of', 'mint-lms' ); ?> <span x-text="totalPages"></span>
					</p>
					<div class="mint-flex mint-gap-2">
						<?php
						echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
							'button',
							array(
								'variant' => 'secondary',
								'size'    => 'sm',
								'label'   => esc_html__( 'Previous', 'mint-lms' ),
								'type'    => 'button',
								'attrs'   => '@click.prevent="loadCourses(page - 1)" :disabled="page <= 1"',
							)
						);
						echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
							'button',
							array(
								'variant' => 'secondary',
								'size'    => 'sm',
								'label'   => esc_html__( 'Next', 'mint-lms' ),
								'type'    => 'button',
								'attrs'   => '@click.prevent="loadCourses(page + 1)" :disabled="page >= totalPages"',
							)
						);
						?>
					</div>
				</div>
			</div>
		</template>
	</div>

	<div class="mint-hidden" aria-hidden="true">
		<?php for ( $i = 0; $i <= 100; $i += 5 ) : ?>
			<span class="mint-w-[<?php echo esc_attr( (string) $i ); ?>%]"></span>
		<?php endfor; ?>
	</div>
</div>
