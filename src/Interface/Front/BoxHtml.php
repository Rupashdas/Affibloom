<?php
/**
 * Prints a box's view with its layout's template.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

use Affibloom\Application\Box\BoxView;
use Affibloom\Domain\Box\LayoutFamily;

/**
 * Prints a box (F5 D1): `templates/front/<layout>.php` with the element partials. Every box caller prints through it.
 */
final class BoxHtml {

	/**
	 * Builds the printer.
	 *
	 * @param string    $templates The `templates/front` folder.
	 * @param BoxAssets $assets    The box's stylesheets and scripts, which every drawn box makes sure of (F5 R47).
	 */
	public function __construct( private readonly string $templates, private readonly BoxAssets $assets ) {}

	/**
	 * The box's HTML.
	 *
	 * The 2-box and 6-box templates come with their themes (Tasks 18E, 18F); until then those themes print the 3-box
	 * markup.
	 *
	 * @param BoxView $view The box.
	 */
	public function render( BoxView $view ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- the included template reads $view.
		// A box the page detection missed (a widget, a theme template) still gets its CSS (Task 18C).
		$this->assets->late_enqueue();

		$file = $this->templates . '/' . $view->family->value . '.php';
		if ( ! is_readable( $file ) ) {
			$file = $this->templates . '/' . LayoutFamily::ThreeBox->value . '.php';
		}

		ob_start();
		try {
			include $file;
		} finally {
			$html = (string) ob_get_clean();
		}

		return $html;
	}
}
