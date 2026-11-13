<?php
/**
 * Renders a PHP template from templates/ with a prepared $view array.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface;

use InvalidArgumentException;

/**
 * Renders a PHP template from templates/ with a prepared $view array.
 */
final class Template {

	/**
	 * Builds the renderer.
	 *
	 * @param string $dir Absolute path of templates/.
	 */
	public function __construct( private string $dir ) {}

	/**
	 * Prints a template. Inside it, $view holds the values and $this renders partials.
	 *
	 * @param string               $name Path below templates/, without ".php".
	 * @param array<string, mixed> $view Values the template prints. Escaping happens in the template.
	 * @throws InvalidArgumentException When the template does not exist.
	 */
	public function render( string $name, array $view = array() ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- the included template reads $view.
		$file = $this->dir . '/' . $name . '.php';
		if ( ! is_readable( $file ) ) {
			throw new InvalidArgumentException( esc_html( "Template not found: $name" ) );
		}
		include $file;
	}

	/**
	 * A template's output as a string, for a REST response that carries markup (the Dashboard's list, Task 13D),
	 * so the page and the response share one template.
	 *
	 * @param string               $name Path below templates/, without ".php".
	 * @param array<string, mixed> $view Values the template prints.
	 * @throws InvalidArgumentException When the template does not exist.
	 */
	public function fetch( string $name, array $view = array() ): string {
		ob_start();
		try {
			$this->render( $name, $view );
		} finally {
			$html = (string) ob_get_clean();
		}

		return $html;
	}
}
