<?php
declare(strict_types=1);

namespace MintLMS\Frontend;

defined( 'ABSPATH' ) || exit;

final class TemplateLoader {

	/**
	 * @param array<string, mixed> $vars
	 */
	public function render( string $template, array $vars = array() ): string {
		$path = $this->locate( $template );

		if ( ! is_readable( $path ) ) {
			return '';
		}

		ob_start();

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Scoped template variables.
		extract( $vars, EXTR_SKIP );
		include $path;

		return (string) ob_get_clean();
	}

	public function locate( string $template ): string {
		$template = ltrim( $template, '/' );

		$themePaths = array(
			get_stylesheet_directory() . '/mint-lms/' . $template,
			get_template_directory() . '/mint-lms/' . $template,
		);

		foreach ( $themePaths as $themePath ) {
			if ( is_readable( $themePath ) ) {
				return $themePath;
			}
		}

		return MINTLMS_PATH . 'templates/' . $template;
	}
}
