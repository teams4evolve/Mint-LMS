<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Admin\FirstRunRedirect;
?>
<div class="wrap mint-lms-admin-wrap">
	<?php echo MintLMS\Infrastructure\Ui\UiRoot::open( 'admin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(360px,1fr));min-height:100vh">

			<!-- Left: hero column -->
			<div style="padding:48px 40px;display:flex;flex-direction:column;justify-content:space-between">
				<div>
					<!-- Brand -->
					<div style="display:flex;align-items:center;gap:11px">
						<div style="width:28px;height:28px;border-radius:8px;background:var(--mint-accent);display:flex;align-items:center;justify-content:center;flex:none;outline:2.5px solid #FFFFFF;outline-offset:-1px">
							<div style="width:10px;height:10px;border:2px solid #FFFFFF;border-radius:3px"></div>
						</div>
						<span style="font-size:17px;font-weight:600;letter-spacing:-0.015em;color:var(--mint-ink)"><?php esc_html_e( 'Mint LMS', 'mint-lms' ); ?></span>
					</div>

					<!-- Headline -->
					<h1 style="font-size:44px;line-height:48px;letter-spacing:-0.04em;font-weight:600;color:var(--mint-ink);margin:56px 0 0;max-width:420px">
						<?php esc_html_e( 'Teach what you already know.', 'mint-lms' ); ?>
					</h1>
					<p style="font-size:19px;line-height:30px;color:var(--mint-ink-2);margin:16px 0 0;max-width:420px">
						<?php esc_html_e( 'Structured lessons, clear progress, and a focused learning experience — without configuring fifty settings first.', 'mint-lms' ); ?>
					</p>

					<!-- Feature bullets -->
					<div style="margin-top:44px;display:flex;flex-direction:column;gap:28px">
						<?php
						$features = array(
							array(
								'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 3L4 8v8l8 5 8-5V8l-8-5z"/><path d="M12 12l8-5M12 12v9M12 12L4 7"/></svg>',
								'title' => __( 'Course builder', 'mint-lms' ),
								'desc'  => __( 'Drag-and-drop sections and lessons with a visual editor.', 'mint-lms' ),
							),
							array(
								'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
								'title' => __( 'Student progress', 'mint-lms' ),
								'desc'  => __( 'See who finished what — completion rates, time spent, and streaks.', 'mint-lms' ),
							),
							array(
								'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>',
								'title' => __( 'Works in minutes', 'mint-lms' ),
								'desc'  => __( 'No config maze. Create a course, add lessons, publish.', 'mint-lms' ),
							),
						);
						foreach ( $features as $f ) :
							?>
							<div style="display:flex;align-items:flex-start;gap:16px">
								<div style="width:38px;height:38px;border-radius:10px;background:var(--mint-accent-wash);color:var(--mint-accent);display:flex;align-items:center;justify-content:center;flex:none">
									<?php echo $f['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
								<div>
									<div style="font-size:16px;font-weight:600;color:var(--mint-ink);line-height:22px"><?php echo esc_html( $f['title'] ); ?></div>
									<div style="font-size:15px;color:var(--mint-ink-2);line-height:22px;margin-top:2px"><?php echo esc_html( $f['desc'] ); ?></div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<p style="font-size:15px;color:var(--mint-ink-3);margin-top:48px">
					<?php esc_html_e( 'Trusted by educators who want simplicity over bloat.', 'mint-lms' ); ?>
				</p>
			</div>

			<!-- Right: form column -->
			<div style="padding:48px 40px;background:#FAF8FF;display:flex;align-items:center;justify-content:center">
				<div style="max-width:400px;width:100%">
					<span class="mint-overline" style="color:var(--mint-accent)"><?php esc_html_e( "LET'S START", 'mint-lms' ); ?></span>
					<h2 style="font-size:28px;line-height:34px;letter-spacing:-0.025em;font-weight:600;color:var(--mint-ink);margin:12px 0 0">
						<?php esc_html_e( 'What are you teaching?', 'mint-lms' ); ?>
					</h2>

					<form method="post" action="<?php echo esc_url( FirstRunRedirect::startGuidedUrl() ); ?>" style="margin-top:32px">
						<label for="mint-first-run-title" style="font-size:16px;font-weight:600;color:var(--mint-ink);display:block;margin-bottom:8px">
							<?php esc_html_e( 'Course name', 'mint-lms' ); ?>
						</label>
						<input
							id="mint-first-run-title"
							type="text"
							name="course_title"
							placeholder="<?php echo esc_attr__( 'e.g. Introduction to Photography', 'mint-lms' ); ?>"
							style="display:block;width:100%;height:50px;padding:0 16px;border:1.5px solid #CBC8DD;border-radius:10px;font-family:var(--mint-font);font-size:17px;color:var(--mint-ink);background:#FFFFFF;outline:none;transition:border-color 120ms ease-out,box-shadow 120ms ease-out;box-sizing:border-box"
							onfocus="this.style.borderColor='var(--mint-accent)';this.style.boxShadow='var(--mint-focus)'"
							onblur="this.style.borderColor='#CBC8DD';this.style.boxShadow='none'"
						/>

						<button type="submit" class="mint-btn mint-btn--primary" style="width:100%;height:48px;margin-top:20px;font-size:17px;border-radius:10px">
							<?php esc_html_e( 'Create your first course', 'mint-lms' ); ?>
						</button>
					</form>

					<div style="text-align:center;margin-top:16px">
						<a
							href="<?php echo esc_url( admin_url( 'admin.php?page=mint-lms-courses' ) ); ?>"
							class="mint-btn mint-btn--ghost"
							style="font-size:15px;font-weight:500;height:auto;padding:8px 16px"
						><?php esc_html_e( 'Skip for now', 'mint-lms' ); ?></a>
					</div>
				</div>
			</div>

		</div>

	<?php echo MintLMS\Infrastructure\Ui\UiRoot::close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
<style>.mint-lms-admin-wrap{margin:0;padding:0}</style>
