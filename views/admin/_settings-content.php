<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

use MintLMS\Infrastructure\Admin\ViewRenderer;
use MintLMS\Infrastructure\Setup\PageSettings;

/** @var ViewRenderer $renderer */
/** @var PageSettings $pageSettings */
/** @var list<array{key: string, label: string, done: bool, detail: string}> $checklist */

$dashboardId = $pageSettings->getDashboardPageId();
$catalogId   = $pageSettings->getCatalogPageId();
$playerId    = $pageSettings->getPlayerPageId();
$certTemplate = (string) get_option( 'mintlms_certificate_template', '' );
$doneCount   = count( array_filter( $checklist, static fn( array $item ): bool => $item['done'] ) );
$totalCount  = count( $checklist );
$pagesCreated = isset( $_GET['mintlms_pages'] ) && '1' === sanitize_text_field( wp_unslash( (string) $_GET['mintlms_pages'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="mint-settings-shell">
	<?php if ( $pagesCreated ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Student pages created or updated.', 'mint-lms' ); ?></p></div>
	<?php endif; ?>
	<div class="mint-settings-head">
		<h1 class="mint-page-title"><?php esc_html_e( 'Settings', 'mint-lms' ); ?></h1>
		<p class="mint-page-subtitle">
			<?php esc_html_e( 'Configure student-facing pages and LMS defaults.', 'mint-lms' ); ?>
		</p>
	</div>

	<div class="mint-card mint-settings-checklist">
		<div class="mint-card__header">
			<h2 class="mint-card__title"><?php esc_html_e( 'Setup checklist', 'mint-lms' ); ?></h2>
			<span class="mint-badge mint-badge--neutral">
				<?php
				printf(
					/* translators: 1: completed items, 2: total items */
					esc_html__( '%1$d / %2$d complete', 'mint-lms' ),
					absint( $doneCount ),
					absint( $totalCount )
				);
				?>
			</span>
		</div>
		<ul class="mint-checklist">
			<?php foreach ( $checklist as $item ) : ?>
				<li class="mint-checklist__item <?php echo $item['done'] ? 'is-done' : 'is-pending'; ?>">
					<span class="mint-checklist__icon" aria-hidden="true">
						<?php if ( $item['done'] ) : ?>
							<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
						<?php else : ?>
							<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="10" cy="10" r="8"/></svg>
						<?php endif; ?>
					</span>
					<div class="mint-checklist__body">
						<div class="mint-checklist__label"><?php echo esc_html( $item['label'] ); ?></div>
						<div class="mint-checklist__detail"><?php echo esc_html( $item['detail'] ); ?></div>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<form id="mintlms-create-pages-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
		<?php wp_nonce_field( 'mintlms_create_pages' ); ?>
		<input type="hidden" name="action" value="mintlms_create_pages" />
	</form>

	<form method="post" action="options.php" class="mint-settings-form">
		<?php settings_fields( 'mintlms_settings' ); ?>

		<div class="mint-card mint-settings-section">
			<h2 class="mint-card__title"><?php esc_html_e( 'Student pages', 'mint-lms' ); ?></h2>
			<p class="mint-card__desc"><?php esc_html_e( 'Choose which WordPress pages host Mint LMS shortcodes. Pages are created automatically on activation.', 'mint-lms' ); ?></p>

			<div class="mint-settings-fields">
				<div class="mint-field">
					<label for="mintlms_page_dashboard" class="mint-field__label"><?php esc_html_e( 'Dashboard page', 'mint-lms' ); ?></label>
					<?php
					wp_dropdown_pages(
						array(
							'name'              => esc_attr( PageSettings::OPTION_DASHBOARD ),
							'id'                => 'mintlms_page_dashboard',
							'selected'          => absint( $dashboardId ),
							'show_option_none'  => esc_html__( '— Select —', 'mint-lms' ),
							'option_none_value' => '0',
							'class'             => 'mint-field__select',
						)
					);
					?>
					<p class="mint-field__help"><?php esc_html_e( 'Uses [mint_lms_dashboard] — student home and continue learning.', 'mint-lms' ); ?></p>
				</div>

				<div class="mint-field">
					<label for="mintlms_page_catalog" class="mint-field__label"><?php esc_html_e( 'Course catalog page', 'mint-lms' ); ?></label>
					<?php
					wp_dropdown_pages(
						array(
							'name'              => esc_attr( PageSettings::OPTION_CATALOG ),
							'id'                => 'mintlms_page_catalog',
							'selected'          => absint( $catalogId ),
							'show_option_none'  => esc_html__( '— Select —', 'mint-lms' ),
							'option_none_value' => '0',
							'class'             => 'mint-field__select',
						)
					);
					?>
					<p class="mint-field__help"><?php esc_html_e( 'Uses [mint_lms_catalog] — browse courses and view course details.', 'mint-lms' ); ?></p>
				</div>

				<div class="mint-field">
					<label for="mintlms_page_player" class="mint-field__label"><?php esc_html_e( 'Course player page', 'mint-lms' ); ?></label>
					<?php
					wp_dropdown_pages(
						array(
							'name'              => esc_attr( PageSettings::OPTION_PLAYER ),
							'id'                => 'mintlms_page_player',
							'selected'          => absint( $playerId ),
							'show_option_none'  => esc_html__( '— Select —', 'mint-lms' ),
							'option_none_value' => '0',
							'class'             => 'mint-field__select',
						)
					);
					?>
					<p class="mint-field__help"><?php esc_html_e( 'Uses [mint_lms_player] — lesson viewer for enrolled students.', 'mint-lms' ); ?></p>
				</div>
			</div>
			<p class="mint-field__help mint-settings-inline-action">
				<button type="submit" form="mintlms-create-pages-form" class="button button-secondary"><?php esc_html_e( 'Create or repair student pages', 'mint-lms' ); ?></button>
			</p>
		</div>

		<div class="mint-card mint-settings-section">
			<h2 class="mint-card__title"><?php esc_html_e( 'Certificate template', 'mint-lms' ); ?></h2>
			<p class="mint-card__desc"><?php esc_html_e( 'HTML shown when a student downloads their certificate. Placeholders: {{student_name}}, {{course_name}}, {{completion_date}}.', 'mint-lms' ); ?></p>
			<div class="mint-settings-fields">
				<div class="mint-field">
					<label for="mintlms_certificate_template" class="mint-field__label"><?php esc_html_e( 'Template HTML', 'mint-lms' ); ?></label>
					<textarea
						id="mintlms_certificate_template"
						name="mintlms_certificate_template"
						class="mint-field__textarea large-text code"
						rows="12"
					><?php echo esc_textarea( $certTemplate ); ?></textarea>
				</div>
			</div>
		</div>

		<div class="mint-card mint-settings-section">
			<h2 class="mint-card__title"><?php esc_html_e( 'Enrollment defaults', 'mint-lms' ); ?></h2>
			<p class="mint-card__desc"><?php esc_html_e( 'Default behavior for new courses. Individual courses can override this.', 'mint-lms' ); ?></p>

			<fieldset class="mint-settings-fields">
				<legend class="screen-reader-text"><?php esc_html_e( 'Default enrollment', 'mint-lms' ); ?></legend>
				<label class="mint-radio-row">
					<input
						type="radio"
						name="<?php echo esc_attr( PageSettings::OPTION_DEFAULT_ENROLLMENT ); ?>"
						value="open"
						<?php checked( $pageSettings->getDefaultEnrollment(), 'open' ); ?>
					/>
					<span><?php esc_html_e( 'Open — students can self-enroll', 'mint-lms' ); ?></span>
				</label>
				<label class="mint-radio-row">
					<input
						type="radio"
						name="<?php echo esc_attr( PageSettings::OPTION_DEFAULT_ENROLLMENT ); ?>"
						value="manual"
						<?php checked( $pageSettings->getDefaultEnrollment(), 'manual' ); ?>
					/>
					<span><?php esc_html_e( 'Manual — admin must enroll students', 'mint-lms' ); ?></span>
				</label>
			</fieldset>
		</div>

		<div class="mint-card mint-settings-section">
			<h2 class="mint-card__title"><?php esc_html_e( 'Email notifications', 'mint-lms' ); ?></h2>
			<p class="mint-card__desc"><?php esc_html_e( 'Toggle automated emails. Email templates are managed separately.', 'mint-lms' ); ?></p>

			<div class="mint-settings-fields mint-settings-fields--inline">
				<input type="hidden" name="<?php echo esc_attr( PageSettings::OPTION_EMAIL_ENROLL ); ?>" value="0" />
				<label class="mint-checkbox-row">
					<input
						type="checkbox"
						name="<?php echo esc_attr( PageSettings::OPTION_EMAIL_ENROLL ); ?>"
						value="1"
						<?php checked( $pageSettings->isEmailEnrollEnabled() ); ?>
					/>
					<span><?php esc_html_e( 'Send email when a student enrolls', 'mint-lms' ); ?></span>
				</label>
				<input type="hidden" name="<?php echo esc_attr( PageSettings::OPTION_EMAIL_COMPLETE ); ?>" value="0" />
				<label class="mint-checkbox-row">
					<input
						type="checkbox"
						name="<?php echo esc_attr( PageSettings::OPTION_EMAIL_COMPLETE ); ?>"
						value="1"
						<?php checked( $pageSettings->isEmailCompleteEnabled() ); ?>
					/>
					<span><?php esc_html_e( 'Send email when a student completes a course', 'mint-lms' ); ?></span>
				</label>
			</div>
		</div>

		<?php submit_button( __( 'Save settings', 'mint-lms' ) ); ?>
	</form>
</div>
