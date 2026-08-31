<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Infrastructure\Admin\ViewRenderer;
use MintLMS\Plugin;

/** @var ViewRenderer $renderer */
/** @var int $courseId */

$students      = array();
$pagination    = array(
	'current'  => 1,
	'total'    => 1,
	'base_url' => admin_url( 'admin.php?page=mint-lms-course-students&course_id=' . $courseId ),
);
$error         = '';
$courseTitle    = '';
$totalStudents = 0;
$totalFinished = 0;

if ( $courseId <= 0 ) {
	$error = '';
} else {
	try {
		$userId = get_current_user_id();
		$page   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$result = Plugin::enrollmentService()->listStudentsForCourse( $courseId, $page, 20, $userId );

		$totalStudents = $result->total;

		foreach ( $result->students as $index => $student ) {
			$user  = get_userdata( $student->userId );
			$name  = $user instanceof \WP_User ? $user->display_name : sprintf(
				/* translators: %d: user ID */
				__( 'User #%d', 'mint-lms' ),
				$student->userId
			);
			$email = $user instanceof \WP_User ? $user->user_email : '';

			$progressPct = (int) round( $student->progressPct );
			if ( 100 === $progressPct ) {
				++$totalFinished;
			}

			$hueIdx   = ( $index % 6 ) + 1;
			$initials = mb_strtoupper( mb_substr( $name, 0, 1 ) );

			$students[] = array(
				'name'         => esc_html( $name ),
				'email'        => esc_html( $email ),
				'initials'     => $initials,
				'hueIdx'       => $hueIdx,
				'progressPct'  => $progressPct,
				'enrolled'     => esc_html( wp_date( get_option( 'date_format' ), strtotime( $student->enrolledAt ) ) ),
				'enrollmentId' => $student->enrollmentId,
			);
		}

		$perPage    = max( 1, $result->perPage );
		$totalPages = max( 1, (int) ceil( $result->total / $perPage ) );
		$pagination = array(
			'current'  => $result->page,
			'total'    => $totalPages,
			'base_url' => admin_url( 'admin.php?page=mint-lms-course-students&course_id=' . $courseId ),
		);

		global $wpdb;
		if ( $wpdb instanceof \wpdb ) {
			$table = $wpdb->prefix . 'mintlms_courses';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$courseTitle = (string) $wpdb->get_var(
				$wpdb->prepare( "SELECT title FROM {$table} WHERE id = %d", $courseId )
			);
		}
	} catch ( NotFoundException $exception ) {
		$error = $exception->getMessage();
	} catch ( ForbiddenException $exception ) {
		$error = $exception->getMessage();
	}
}

$studentsPageUrl = admin_url( 'admin.php?page=mint-lms-course-students' );
?>

<?php if ( $courseId <= 0 ) : ?>
<!-- ── No course selected — show course picker ──────────── -->
<div
	style="max-width:560px;margin:0 auto;padding:48px var(--mint-page-pad) 96px"
	x-data="{
		courses: [],
		loading: true,
		search: '',
		get filtered() {
			if (!this.search) return this.courses;
			const q = this.search.toLowerCase();
			return this.courses.filter(c => c.title.toLowerCase().includes(q));
		}
	}"
	x-init="
		fetch(mintLmsAdmin.restBase + '/courses?per_page=100', {
			headers: { 'X-WP-Nonce': mintLmsAdmin.nonce }
		})
		.then(r => r.json())
		.then(json => { courses = json.data?.items || []; loading = false; })
		.catch(() => { loading = false; })
	"
>
	<h1 style="font-size:48px;line-height:52px;font-weight:600;letter-spacing:-.04em;color:var(--mint-ink);margin:0 0 8px">
		<?php echo esc_html__( 'Students', 'mint-lms' ); ?>
	</h1>
	<p style="font-size:19px;line-height:28px;color:var(--mint-ink-2);margin:0 0 32px">
		<?php echo esc_html__( 'Pick a course to view its students.', 'mint-lms' ); ?>
	</p>

	<!-- Search -->
	<input
		type="text"
		class="mint-input"
		style="max-width:380px;margin-bottom:20px;border-width:1.5px;border-color:#B9B6CE;border-radius:8px"
		placeholder="<?php echo esc_attr__( 'Search courses…', 'mint-lms' ); ?>"
		x-model="search"
	/>

	<div x-show="loading" style="padding:24px 0;color:var(--mint-ink-3);font-size:15px">
		<?php echo esc_html__( 'Loading courses…', 'mint-lms' ); ?>
	</div>

	<div x-show="!loading && filtered.length === 0" x-cloak style="padding:24px 0;color:var(--mint-ink-3);font-size:15px">
		<?php echo esc_html__( 'No courses found.', 'mint-lms' ); ?>
	</div>

	<div x-show="!loading" x-cloak style="display:flex;flex-direction:column;gap:4px">
		<template x-for="course in filtered" :key="course.id">
			<a
				:href="'<?php echo esc_url( $studentsPageUrl ); ?>&course_id=' + course.id"
				class="mint-row"
				style="display:flex;align-items:center;gap:14px;padding:12px 14px;text-decoration:none;color:var(--mint-ink)"
			>
				<span
					class="mint-tile mint-tile--sm"
					:style="'background:var(--mint-hue-' + ((course.id % 6) + 1) + '-bg);color:var(--mint-hue-' + ((course.id % 6) + 1) + '-ink)'"
					x-text="course.title ? course.title.charAt(0).toUpperCase() : '?'"
				></span>
				<span style="font-size:16px;font-weight:600;letter-spacing:-.01em" x-text="course.title"></span>
			</a>
		</template>
	</div>
</div>

<?php elseif ( '' !== $error ) : ?>
<!-- ── Error state ──────────────────────────────────────── -->
<div style="padding:48px var(--mint-page-pad)">
	<?php
	echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
		'error-state',
		array(
			'title'   => __( 'Unable to load students', 'mint-lms' ),
			'message' => esc_html( $error ),
		)
	);
	?>
</div>

<?php else : ?>
<!-- ── Students list ────────────────────────────────────── -->
<div style="max-width:var(--mint-content-max);margin:0 auto;padding:48px var(--mint-page-pad) 96px">

	<!-- Title -->
	<h1 style="font-size:48px;line-height:52px;font-weight:600;letter-spacing:-.04em;color:var(--mint-ink);margin:0 0 6px">
		<?php echo esc_html__( 'Students', 'mint-lms' ); ?>
	</h1>

	<!-- Subtitle -->
	<p style="font-size:19px;line-height:28px;color:var(--mint-ink-2);margin:0 0 32px">
		<?php
		printf(
			/* translators: 1: total count, 2: finished count */
			esc_html__( '%1$s students in this course · %2$s have finished', 'mint-lms' ),
			'<span style="font-variant-numeric:tabular-nums">' . esc_html( (string) $totalStudents ) . '</span>',
			'<span style="font-variant-numeric:tabular-nums">' . esc_html( (string) $totalFinished ) . '</span>'
		);
		?>
	</p>

	<!-- Course picker -->
	<div
		style="display:flex;align-items:center;gap:12px;margin-bottom:28px;flex-wrap:wrap"
		x-data="{ courses: [], loading: true }"
		x-init="
			fetch(mintLmsAdmin.restBase + '/courses?per_page=100', {
				headers: { 'X-WP-Nonce': mintLmsAdmin.nonce }
			})
			.then(r => r.json())
			.then(json => { courses = json.data?.items || []; loading = false; })
			.catch(() => { loading = false; })
		"
	>
		<div style="position:relative;min-width:290px">
			<select
				class="mint-select"
				style="height:44px;border-width:1.5px;border-color:#B9B6CE;border-radius:8px;padding-right:40px"
				onchange="if(this.value) window.location='<?php echo esc_url( $studentsPageUrl ); ?>&course_id='+this.value"
			>
				<option value="" disabled><?php esc_html_e( 'Select a course…', 'mint-lms' ); ?></option>
				<template x-for="course in courses" :key="course.id">
					<option :value="course.id" :selected="course.id === <?php echo (int) $courseId; ?>" x-text="course.title"></option>
				</template>
			</select>
		</div>
	</div>

	<!-- Add student panel -->
	<div
		style="background:var(--mint-tint);border:1px solid var(--mint-tint-line);border-radius:14px;padding:22px;display:flex;align-items:flex-end;gap:12px;margin-bottom:32px;flex-wrap:wrap"
		x-data="{ enrollEmail: '' }"
	>
		<div style="flex:1;min-width:200px">
			<label for="mint-enroll-email" class="mint-label mint-label--sm">
				<?php echo esc_html__( 'Add a student', 'mint-lms' ); ?>
			</label>
			<input
				id="mint-enroll-email"
				type="text"
				class="mint-input"
				style="border-width:1.5px;border-color:#B9B6CE;border-radius:8px;background:#FFFFFF"
				placeholder="<?php echo esc_attr__( 'Email or user ID', 'mint-lms' ); ?>"
				x-model="enrollEmail"
			/>
		</div>
		<button
			type="button"
			class="mint-btn mint-btn--primary"
			id="mint-enroll-student"
			data-mint-course="<?php echo esc_attr( (string) $courseId ); ?>"
		>
			<?php echo esc_html__( 'Add to course', 'mint-lms' ); ?>
		</button>
	</div>

	<?php if ( empty( $students ) ) : ?>
		<?php
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'empty-state',
			array(
				'title'       => __( 'No students enrolled', 'mint-lms' ),
				'description' => __( 'Enroll students manually or share an open enrollment link.', 'mint-lms' ),
			)
		);
		?>
	<?php else : ?>
		<!-- Table -->
		<div style="width:100%">

			<!-- Header row -->
			<div
				class="mint-table-header"
				style="grid-template-columns:minmax(0,1fr) 132px 120px 84px 84px;border-radius:10px;font-size:15px;font-weight:600;color:var(--mint-ink-2)"
			>
				<span><?php echo esc_html__( 'Student', 'mint-lms' ); ?></span>
				<span><?php echo esc_html__( 'Where they are', 'mint-lms' ); ?></span>
				<span><?php echo esc_html__( 'Progress', 'mint-lms' ); ?></span>
				<span><?php echo esc_html__( 'Joined', 'mint-lms' ); ?></span>
				<span></span>
			</div>

			<!-- Data rows -->
			<?php foreach ( $students as $s ) : ?>
			<div
				style="display:grid;grid-template-columns:minmax(0,1fr) 132px 120px 84px 84px;align-items:center;padding:14px 16px;border-bottom:1px solid var(--mint-rule-soft)"
				data-mint-student-row
				data-mint-student-name="<?php echo esc_attr( wp_strip_all_tags( $s['name'] ) ); ?>"
				data-mint-student-email="<?php echo esc_attr( wp_strip_all_tags( $s['email'] ) ); ?>"
				data-mint-student-progress="<?php echo (int) $s['progressPct']; ?>%"
				data-mint-student-joined="<?php echo esc_attr( wp_strip_all_tags( $s['enrolled'] ) ); ?>"
			>
				<!-- Student cell -->
				<div style="display:flex;align-items:center;gap:12px;min-width:0">
					<span
						class="mint-avatar mint-avatar--md"
						style="background:var(--mint-hue-<?php echo (int) $s['hueIdx']; ?>-bg);color:var(--mint-hue-<?php echo (int) $s['hueIdx']; ?>-ink)"
					>
						<?php echo esc_html( $s['initials'] ); ?>
					</span>
					<div style="min-width:0">
						<span style="display:block;font-size:17px;font-weight:600;line-height:24px;color:var(--mint-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
							<?php echo esc_html( $s['name'] ); ?>
						</span>
						<span style="display:block;font-size:15px;line-height:20px;color:var(--mint-ink-2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
							<?php echo esc_html( $s['email'] ); ?>
						</span>
					</div>
				</div>

				<!-- Where they are -->
				<span style="font-size:15px;color:var(--mint-ink-2)">–</span>

				<!-- Progress -->
				<div style="display:flex;align-items:center;gap:8px">
					<div class="mint-progress" style="flex:1;height:9px">
						<div class="mint-progress__fill" style="width:<?php echo (int) $s['progressPct']; ?>%"></div>
					</div>
					<span class="mint-tabular" style="font-size:14px;font-weight:600;color:var(--mint-ink-2);min-width:30px;text-align:right">
						<?php echo (int) $s['progressPct']; ?>%
					</span>
				</div>

				<!-- Joined -->
				<span class="mint-tabular" style="font-size:14px;color:var(--mint-ink-2)">
					<?php echo esc_html( $s['enrolled'] ); ?>
				</span>

				<!-- Remove action -->
				<div style="text-align:right">
					<button
						type="button"
						style="background:none;border:none;cursor:pointer;font-family:var(--mint-font);font-size:16px;font-weight:600;color:var(--mint-danger);padding:4px 0;outline:none"
						data-mint-unenroll="<?php echo esc_attr( (string) $s['enrollmentId'] ); ?>"
						data-mint-course="<?php echo esc_attr( (string) $courseId ); ?>"
					>
						<?php echo esc_html__( 'Remove', 'mint-lms' ); ?>
					</button>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<!-- Pagination -->
		<?php if ( $pagination['total'] > 1 ) : ?>
		<div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:28px">
			<?php for ( $p = 1; $p <= $pagination['total']; $p++ ) : ?>
				<?php if ( $p === $pagination['current'] ) : ?>
					<span class="mint-btn mint-btn--sm" style="background:var(--mint-accent);color:#FFF;pointer-events:none">
						<?php echo (int) $p; ?>
					</span>
				<?php else : ?>
					<a
						href="<?php echo esc_url( $pagination['base_url'] . '&paged=' . $p ); ?>"
						class="mint-btn mint-btn--ghost mint-btn--sm"
						style="text-decoration:none"
					>
						<?php echo (int) $p; ?>
					</a>
				<?php endif; ?>
			<?php endfor; ?>
		</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
<?php endif; ?>
