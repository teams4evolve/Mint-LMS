<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Setup\PageSettings;

final class AssetLoader {

	private const ENQUEUE_PRIORITY = 999;

	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminAssets' ), self::ENQUEUE_PRIORITY );
		add_action( 'admin_head', array( $this, 'hideWordPressChrome' ) );
	}

	public function enqueueAdminAssets( string $hookSuffix ): void {
		if ( ! str_contains( $hookSuffix, 'mint-lms' ) ) {
			return;
		}

		$this->enqueueStyles( 'mint-lms-admin' );
		$this->enqueueScripts( 'mint-lms-admin' );

		if ( str_contains( $hookSuffix, 'mint-lms-builder' ) || str_contains( $hookSuffix, 'mint-lms-course-edit' ) ) {
			wp_enqueue_media();
		}

		if ( str_contains( $hookSuffix, 'mint-lms-builder' ) ) {
			wp_enqueue_editor();
		}

		$restBase     = esc_url_raw( rest_url( 'mintlms/v1' ) );
		$pageSettings = new PageSettings();

		wp_localize_script(
			'mint-lms-admin',
			'mintLmsAdmin',
			array(
				'restBase'           => $restBase,
				'nonce'              => wp_create_nonce( 'wp_rest' ),
				'defaultEnrollment'  => $pageSettings->getDefaultEnrollment(),
				'urls'               => array(
					'courses'      => admin_url( 'admin.php?page=mint-lms-courses' ),
					'builder'      => admin_url( 'admin.php?page=mint-lms-builder' ),
					'edit'         => admin_url( 'admin.php?page=mint-lms-course-edit' ),
					'dashboard'    => admin_url( 'admin.php?page=mint-lms' ),
					'playerPage'   => $pageSettings->getPlayerUrl(),
					'catalogPage'  => $pageSettings->getCatalogUrl(),
					'guidedCourse' => admin_url( 'admin.php?page=mint-lms-guided-course' ),
				),
			)
		);

		if ( str_contains( $hookSuffix, 'mint-lms-guided-course' ) ) {
			wp_localize_script(
				'mint-lms-admin',
				'mintLmsGuided',
				array(
					'restBase'    => $restBase,
					'nonce'       => wp_create_nonce( 'wp_rest' ),
					'coursesUrl'  => admin_url( 'admin.php?page=mint-lms-courses' ),
					'builderBase' => admin_url( 'admin.php?page=mint-lms-builder&course_id=' ),
					'i18n'        => array(
						'step'            => __( 'Step', 'mint-lms' ),
						'stepTitles'      => array(
							1 => __( 'Name your course', 'mint-lms' ),
							2 => __( 'Add your first lesson', 'mint-lms' ),
							3 => __( 'Publish your course', 'mint-lms' ),
						),
						'titleRequired'   => __( 'Course title is required.', 'mint-lms' ),
						'sectionRequired' => __( 'Section and lesson titles are required.', 'mint-lms' ),
						'genericError'    => __( 'Something went wrong. Please try again.', 'mint-lms' ),
					),
				)
			);
		}
	}

	public function hideWordPressChrome(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( null === $screen || ! str_contains( (string) $screen->id, 'mint-lms' ) ) {
			return;
		}

		echo '<style>
			.mint-lms-admin-wrap{margin:0;padding:0}
			.mint-lms-admin-wrap>.notice{display:none}
			#wpbody-content{padding-bottom:0}
		</style>';
	}

	private function enqueueStyles( string $handle ): void {
		wp_enqueue_style(
			$handle,
			MINTLMS_URL . 'assets/dist/main.css',
			array(),
			MINTLMS_VERSION
		);

		wp_add_inline_style(
			$handle,
			'#mint-lms-root.mint-lms-ui,#mint-lms-root.mint-lms-student{isolation:isolate;position:relative;z-index:0}'
		);
	}

	private function enqueueScripts( string $handle ): void {
		wp_enqueue_script(
			$handle,
			MINTLMS_URL . 'assets/dist/main.js',
			array(),
			MINTLMS_VERSION,
			true
		);
	}
}
