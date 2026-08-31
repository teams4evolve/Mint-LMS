<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

final class ViewRenderer {

	/**
	 * Render a view file and return the HTML string.
	 *
	 * @param array<string, mixed> $vars Variables available inside the view.
	 */
	public function render( string $view, array $vars = array() ): string {
		$viewPath = MINTLMS_PATH . 'views/' . ltrim( $view, '/' ) . '.php';

		if ( ! is_readable( $viewPath ) ) {
			return '';
		}

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Controlled internal view variables only.
		extract( $vars, EXTR_SKIP );

		ob_start();
		include $viewPath;

		return (string) ob_get_clean();
	}

	/**
	 * Render a reusable component partial.
	 *
	 * @param array<string, mixed> $vars Variables passed to the component.
	 */
	public function component( string $name, array $vars = array() ): string {
		return $this->render( 'components/' . $name, $vars );
	}

	/**
	 * Render a view and echo the result.
	 *
	 * @param array<string, mixed> $vars Variables available inside the view.
	 */
	public function echo( string $view, array $vars = array() ): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Views/components escape their own output.
		echo $this->render( $view, $vars );
	}
}
