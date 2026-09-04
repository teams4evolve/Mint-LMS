<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

/** @var MintLMS\Infrastructure\Admin\ViewRenderer $renderer */
/** @var int $courseId */

$builderUrl = admin_url( 'admin.php?page=mint-lms-builder&course_id=' . $courseId );
$coursesUrl = admin_url( 'admin.php?page=mint-lms-courses' );

$inputClass    = 'mint-block mint-w-full mint-rounded-lg mint-border mint-border-[#B9B6CE] mint-bg-bg mint-px-[14px] mint-py-[11px] mint-text-base mint-text-ink focus:mint-border-accent focus:mint-shadow-focus focus:mint-outline-none';
$sectionBorder = 'mint-mt-8 mint-border-t-[1.5px] mint-border-[#DAD7E6] mint-pt-8';
$radioBase     = 'mint-mt-[2px] mint-h-[22px] mint-w-[22px] mint-shrink-0 mint-cursor-pointer mint-rounded-full mint-border-2 mint-border-[#9C99B5] mint-bg-bg mint-transition-colors focus:mint-outline-none focus:mint-shadow-focus';
?>
<div
	class="mint-mx-auto mint-max-w-[560px] mint-px-7 mint-pb-24 mint-pt-12"
	x-data="courseEdit(<?php echo esc_attr( (string) $courseId ); ?>)"
	x-init="init()"
	@mint-save-course.window="save()"
>
	<div x-show="loading" x-cloak class="mint-pt-10">
		<?php
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'skeleton',
			array( 'variant' => 'card' )
		);
		?>
	</div>

	<div x-show="error && !loading" x-cloak class="mint-pt-10">
		<?php
		$mintlms_retry_button = $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'button',
			array(
				'variant' => 'secondary',
				'label'   => esc_html__( 'Try again', 'mint-lms' ),
				'type'    => 'button',
				'attrs'   => '@click="init()"',
			)
		);
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'error-state',
			array(
				'title'        => esc_html__( 'Could not load course', 'mint-lms' ),
				'messageAttrs' => 'x-text="error"',
				'retry'        => $mintlms_retry_button, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer component HTML.
			)
		);
		?>
	</div>

	<div x-show="!loading && !error" x-cloak>

		<nav class="mint-mb-3 mint-text-base mint-leading-6 mint-text-ink-2">
			<a href="<?php echo esc_url( $coursesUrl ); ?>" class="mint-text-ink-2 mint-no-underline hover:mint-text-ink"><?php echo esc_html__( 'Courses', 'mint-lms' ); ?></a>
			<span class="mint-mx-[6px] mint-opacity-50">›</span>
			<span x-text="form.title || '<?php echo esc_attr__( 'Untitled', 'mint-lms' ); ?>'"></span>
		</nav>

		<h1 class="mint-m-0 mint-mb-11 mint-text-[48px] mint-font-semibold mint-leading-[52px] mint-tracking-[-0.04em] mint-text-ink">
			<?php echo esc_html__( 'Course settings', 'mint-lms' ); ?>
		</h1>

		<form @submit.prevent="save()">

			<div>
				<label for="mint-course-title" class="mint-mb-2 mint-block mint-text-base mint-font-semibold mint-leading-6 mint-text-ink">
					<?php echo esc_html__( 'Course name', 'mint-lms' ); ?>
				</label>
				<input
					id="mint-course-title"
					type="text"
					class="<?php echo esc_attr( $inputClass ); ?> mint-h-[50px] mint-border-[1.5px]"
					x-model="form.title"
					required
				/>
				<span class="mint-mt-2 mint-block mint-text-sm mint-text-ink-3"><?php echo esc_html__( 'Students see this on their dashboard.', 'mint-lms' ); ?></span>
			</div>

			<div class="<?php echo esc_attr( $sectionBorder ); ?>">
				<label for="mint-course-desc" class="mint-mb-2 mint-block mint-text-base mint-font-semibold mint-leading-6 mint-text-ink">
					<?php echo esc_html__( 'Short description', 'mint-lms' ); ?>
				</label>
				<textarea
					id="mint-course-desc"
					class="<?php echo esc_attr( $inputClass ); ?> mint-min-h-[110px] mint-resize-y mint-border-[1.5px]"
					x-model="form.description"
				></textarea>
				<span class="mint-mt-2 mint-block mint-text-sm mint-text-ink-3"><?php echo esc_html__( 'Two sentences is plenty.', 'mint-lms' ); ?></span>
			</div>

			<div class="<?php echo esc_attr( $sectionBorder ); ?>">
				<span class="mint-mb-4 mint-block mint-text-over mint-font-semibold mint-uppercase mint-text-ink-3">
					<?php echo esc_html__( 'Cover image', 'mint-lms' ); ?>
				</span>

				<div
					x-show="!featuredImageUrl"
					class="mint-flex mint-h-[200px] mint-items-center mint-justify-center mint-rounded-[14px] mint-border-[1.5px] mint-border-dashed mint-border-[#A79FE0] mint-bg-tint"
				>
					<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="mint-text-ink-3 mint-opacity-45" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<rect x="3" y="3" width="18" height="18" rx="3"/>
						<circle cx="9" cy="9" r="1.5"/>
						<path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
					</svg>
				</div>
				<div
					x-show="featuredImageUrl"
					x-cloak
					class="mint-h-[200px] mint-overflow-hidden mint-rounded-[14px]"
				>
					<img :src="featuredImageUrl" alt="" class="mint-h-full mint-w-full mint-object-cover" />
				</div>

				<div class="mint-mt-[14px] mint-flex mint-items-center mint-gap-[10px]">
					<?php
					echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
						'button',
						array(
							'variant' => 'secondary',
							'size'    => 'sm',
							'label'   => esc_html__( 'Upload', 'mint-lms' ),
							'type'    => 'button',
							'attrs'   => '@click="pickImage()"',
						)
					);
					?>
					<button
						type="button"
						class="mint-inline-flex mint-h-control-md mint-items-center mint-justify-center mint-rounded-md mint-bg-transparent mint-px-[14px] mint-text-sm mint-font-semibold mint-text-ink-3 hover:mint-bg-bg-subtle"
						x-show="form.featuredImageId"
						x-cloak
						@click="clearImage()"
					>
						<?php echo esc_html__( 'Remove', 'mint-lms' ); ?>
					</button>
				</div>
			</div>

			<div class="<?php echo esc_attr( $sectionBorder ); ?>">
				<span class="mint-mb-5 mint-block mint-text-over mint-font-semibold mint-uppercase mint-text-ink-3">
					<?php echo esc_html__( 'Who can see it', 'mint-lms' ); ?>
				</span>

				<div class="mint-flex mint-flex-col mint-gap-[18px]">
					<?php
					$visibilityOptions = array(
						array(
							'value' => 'published',
							'label' => __( 'Live', 'mint-lms' ),
							'desc'  => __( 'Visible to everyone. Students can enrol.', 'mint-lms' ),
						),
						array(
							'value' => 'draft',
							'label' => __( 'Draft', 'mint-lms' ),
							'desc'  => __( 'Only you can see it. Useful while building.', 'mint-lms' ),
						),
						array(
							'value' => 'archived',
							'label' => __( 'Hidden', 'mint-lms' ),
							'desc'  => __( 'Unlisted. Only direct link holders can access it.', 'mint-lms' ),
						),
					);
					foreach ( $visibilityOptions as $opt ) :
					?>
					<label
						class="mint-flex mint-cursor-pointer mint-items-start mint-gap-[14px]"
						@click="form.status = '<?php echo esc_attr( $opt['value'] ); ?>'"
					>
						<span
							class="<?php echo esc_attr( $radioBase ); ?>"
							:class="form.status === '<?php echo esc_attr( $opt['value'] ); ?>' ? 'mint-border-[7px] mint-border-accent' : ''"
							role="radio"
							tabindex="0"
							:aria-checked="form.status === '<?php echo esc_attr( $opt['value'] ); ?>' ? 'true' : 'false'"
							@keydown.space.prevent="form.status = '<?php echo esc_attr( $opt['value'] ); ?>'"
						></span>
						<span>
							<span class="mint-block mint-text-base mint-font-semibold mint-leading-6 mint-text-ink">
								<?php echo esc_html( $opt['label'] ); ?>
							</span>
							<span class="mint-mt-[2px] mint-block mint-text-sm mint-leading-[22px] mint-text-ink-2">
								<?php echo esc_html( $opt['desc'] ); ?>
							</span>
						</span>
					</label>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="<?php echo esc_attr( $sectionBorder ); ?>">
				<span class="mint-mb-5 mint-block mint-text-over mint-font-semibold mint-uppercase mint-text-ink-3">
					<?php echo esc_html__( 'Enrollment', 'mint-lms' ); ?>
				</span>

				<div class="mint-flex mint-flex-col mint-gap-[18px]">
					<?php
					$enrollmentOptions = array(
						array(
							'value' => 'open',
							'label' => __( 'Open enrollment', 'mint-lms' ),
							'desc'  => __( 'Students can enroll themselves from the course page.', 'mint-lms' ),
						),
						array(
							'value' => 'manual',
							'label' => __( 'Manual enrollment', 'mint-lms' ),
							'desc'  => __( 'Only students you add can access the course.', 'mint-lms' ),
						),
					);
					foreach ( $enrollmentOptions as $opt ) :
						?>
					<label
						class="mint-flex mint-cursor-pointer mint-items-start mint-gap-[14px]"
						@click="form.enrollmentType = '<?php echo esc_attr( $opt['value'] ); ?>'"
					>
						<span
							class="<?php echo esc_attr( $radioBase ); ?>"
							:class="form.enrollmentType === '<?php echo esc_attr( $opt['value'] ); ?>' ? 'mint-border-[7px] mint-border-accent' : ''"
							role="radio"
							tabindex="0"
							:aria-checked="form.enrollmentType === '<?php echo esc_attr( $opt['value'] ); ?>' ? 'true' : 'false'"
							@keydown.space.prevent="form.enrollmentType = '<?php echo esc_attr( $opt['value'] ); ?>'"
						></span>
						<span>
							<span class="mint-block mint-text-base mint-font-semibold mint-leading-6 mint-text-ink">
								<?php echo esc_html( $opt['label'] ); ?>
							</span>
							<span class="mint-mt-[2px] mint-block mint-text-sm mint-leading-[22px] mint-text-ink-2">
								<?php echo esc_html( $opt['desc'] ); ?>
							</span>
						</span>
					</label>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="<?php echo esc_attr( $sectionBorder ); ?>">
				<span class="mint-mb-2 mint-block mint-text-over mint-font-semibold mint-uppercase mint-text-danger">
					<?php echo esc_html__( 'Delete', 'mint-lms' ); ?>
				</span>
				<p class="mint-m-0 mint-mb-4 mint-text-base mint-leading-6 mint-text-ink-2">
					<?php echo esc_html__( 'Permanently remove this course and all its content. This action cannot be undone.', 'mint-lms' ); ?>
				</p>
				<?php
				echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
					'button',
					array(
						'variant' => 'danger-outline',
						'size'    => 'sm',
						'label'   => esc_html__( 'Delete this course', 'mint-lms' ),
						'type'    => 'button',
						'attrs'   => '@click="deleteCourse()"',
					)
				);
				?>
			</div>

		</form>
	</div>
</div>
