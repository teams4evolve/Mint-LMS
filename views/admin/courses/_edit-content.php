<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

/** @var MintLMS\Infrastructure\Admin\ViewRenderer $renderer */
/** @var int $courseId */

$builderUrl = admin_url( 'admin.php?page=mint-lms-builder&course_id=' . $courseId );
$coursesUrl = admin_url( 'admin.php?page=mint-lms-courses' );
?>
<div
	style="max-width:560px;margin:0 auto;padding:48px var(--mint-page-pad) 96px"
	x-data="courseEdit(<?php echo esc_attr( (string) $courseId ); ?>)"
	x-init="init()"
	@mint-save-course.window="save()"
>
	<!-- Loading -->
	<div x-show="loading" x-cloak style="padding-top:40px">
		<?php
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'skeleton',
			array( 'variant' => 'card' )
		);
		?>
	</div>

	<!-- Error -->
	<div x-show="error && !loading" x-cloak style="padding-top:40px">
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

	<!-- Main form -->
	<div x-show="!loading && !error" x-cloak>

		<!-- Breadcrumb -->
		<nav style="font-size:16px;line-height:24px;color:var(--mint-ink-2);margin-bottom:12px">
			<a href="<?php echo esc_url( $coursesUrl ); ?>"
			   style="color:var(--mint-ink-2);text-decoration:none"
			   onmouseover="this.style.color='var(--mint-ink)'"
			   onmouseout="this.style.color='var(--mint-ink-2)'"
			><?php echo esc_html__( 'Courses', 'mint-lms' ); ?></a>
			<span style="margin:0 6px;opacity:.5">›</span>
			<span x-text="form.title || '<?php echo esc_attr__( 'Untitled', 'mint-lms' ); ?>'"></span>
		</nav>

		<!-- Page title -->
		<h1 style="font-size:48px;line-height:52px;font-weight:600;letter-spacing:-.04em;color:var(--mint-ink);margin:0 0 44px">
			<?php echo esc_html__( 'Course settings', 'mint-lms' ); ?>
		</h1>

		<form @submit.prevent="save()">

			<!-- ── Course name ─────────────────────────────── -->
			<div>
				<label for="mint-course-title" class="mint-label">
					<?php echo esc_html__( 'Course name', 'mint-lms' ); ?>
				</label>
				<input
					id="mint-course-title"
					type="text"
					class="mint-input"
					style="height:50px;border-width:1.5px;border-color:#B9B6CE;border-radius:8px"
					x-model="form.title"
					required
				/>
				<span class="mint-helper"><?php echo esc_html__( 'Students see this on their dashboard.', 'mint-lms' ); ?></span>
			</div>

			<!-- ── Short description ───────────────────────── -->
			<div style="border-top:1.5px solid #DAD7E6;padding-top:32px;margin-top:32px">
				<label for="mint-course-desc" class="mint-label">
					<?php echo esc_html__( 'Short description', 'mint-lms' ); ?>
				</label>
				<textarea
					id="mint-course-desc"
					class="mint-textarea"
					style="min-height:110px;border-width:1.5px;border-color:#B9B6CE;border-radius:8px"
					x-model="form.description"
				></textarea>
				<span class="mint-helper"><?php echo esc_html__( 'Two sentences is plenty.', 'mint-lms' ); ?></span>
			</div>

			<!-- ── Cover image ─────────────────────────────── -->
			<div style="border-top:1.5px solid #DAD7E6;padding-top:32px;margin-top:32px">
				<span class="mint-t-over" style="display:block;margin-bottom:16px">
					<?php echo esc_html__( 'Cover image', 'mint-lms' ); ?>
				</span>

				<!-- Image placeholder / preview -->
				<div
					x-show="!featuredImageUrl"
					style="height:200px;border-radius:14px;border:1.5px dashed #A79FE0;display:flex;align-items:center;justify-content:center;background:var(--mint-tint)"
				>
					<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--mint-ink-3)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="opacity:.45">
						<rect x="3" y="3" width="18" height="18" rx="3"/>
						<circle cx="9" cy="9" r="1.5"/>
						<path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
					</svg>
				</div>
				<div
					x-show="featuredImageUrl"
					x-cloak
					style="height:200px;border-radius:14px;overflow:hidden"
				>
					<img :src="featuredImageUrl" alt="" style="width:100%;height:100%;object-fit:cover" />
				</div>

				<div style="display:flex;align-items:center;gap:10px;margin-top:14px">
					<button type="button" class="mint-btn mint-btn--secondary mint-btn--sm" @click="pickImage()">
						<?php echo esc_html__( 'Upload', 'mint-lms' ); ?>
					</button>
					<button
						type="button"
						class="mint-btn mint-btn--ghost mint-btn--sm"
						style="color:var(--mint-ink-3)"
						x-show="form.featuredImageId"
						x-cloak
						@click="clearImage()"
					>
						<?php echo esc_html__( 'Remove', 'mint-lms' ); ?>
					</button>
				</div>
			</div>

			<!-- ── Who can see it ──────────────────────────── -->
			<div style="border-top:1.5px solid #DAD7E6;padding-top:32px;margin-top:32px">
				<span class="mint-t-over" style="display:block;margin-bottom:20px">
					<?php echo esc_html__( 'Who can see it', 'mint-lms' ); ?>
				</span>

				<div style="display:flex;flex-direction:column;gap:18px">
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
						style="display:flex;align-items:flex-start;gap:14px;cursor:pointer"
						@click="form.status = '<?php echo esc_attr( $opt['value'] ); ?>'"
					>
						<span
							class="mint-radio"
							:class="{ 'is-selected': form.status === '<?php echo esc_attr( $opt['value'] ); ?>' }"
							role="radio"
							tabindex="0"
							:aria-checked="form.status === '<?php echo esc_attr( $opt['value'] ); ?>' ? 'true' : 'false'"
							@keydown.space.prevent="form.status = '<?php echo esc_attr( $opt['value'] ); ?>'"
							style="margin-top:2px"
						></span>
						<span>
							<span style="display:block;font-size:16px;font-weight:600;color:var(--mint-ink);line-height:24px">
								<?php echo esc_html( $opt['label'] ); ?>
							</span>
							<span style="display:block;font-size:15px;color:var(--mint-ink-2);line-height:22px;margin-top:2px">
								<?php echo esc_html( $opt['desc'] ); ?>
							</span>
						</span>
					</label>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- ── Enrollment ──────────────────────────────── -->
			<div style="border-top:1.5px solid #DAD7E6;padding-top:32px;margin-top:32px">
				<span class="mint-t-over" style="display:block;margin-bottom:20px">
					<?php echo esc_html__( 'Enrollment', 'mint-lms' ); ?>
				</span>

				<div style="display:flex;flex-direction:column;gap:18px">
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
						style="display:flex;align-items:flex-start;gap:14px;cursor:pointer"
						@click="form.enrollmentType = '<?php echo esc_attr( $opt['value'] ); ?>'"
					>
						<span
							class="mint-radio"
							:class="{ 'is-selected': form.enrollmentType === '<?php echo esc_attr( $opt['value'] ); ?>' }"
							role="radio"
							tabindex="0"
							:aria-checked="form.enrollmentType === '<?php echo esc_attr( $opt['value'] ); ?>' ? 'true' : 'false'"
							@keydown.space.prevent="form.enrollmentType = '<?php echo esc_attr( $opt['value'] ); ?>'"
							style="margin-top:2px"
						></span>
						<span>
							<span style="display:block;font-size:16px;font-weight:600;color:var(--mint-ink);line-height:24px">
								<?php echo esc_html( $opt['label'] ); ?>
							</span>
							<span style="display:block;font-size:15px;color:var(--mint-ink-2);line-height:22px;margin-top:2px">
								<?php echo esc_html( $opt['desc'] ); ?>
							</span>
						</span>
					</label>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- ── Delete ──────────────────────────────────── -->
			<div style="border-top:1.5px solid #DAD7E6;padding-top:32px;margin-top:32px">
				<span class="mint-t-over" style="display:block;margin-bottom:8px;color:var(--mint-danger)">
					<?php echo esc_html__( 'Delete', 'mint-lms' ); ?>
				</span>
				<p style="font-size:16px;color:var(--mint-ink-2);line-height:24px;margin:0 0 16px">
					<?php echo esc_html__( 'Permanently remove this course and all its content. This action cannot be undone.', 'mint-lms' ); ?>
				</p>
				<button type="button" class="mint-btn mint-btn--danger-outline mint-btn--sm" @click="deleteCourse()">
					<?php echo esc_html__( 'Delete this course', 'mint-lms' ); ?>
				</button>
			</div>

		</form>
	</div>
</div>
