<?php
/**
 * Prints a box's view with its layout's template.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

use Affibloom\Application\Box\BoxView;

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
	 * @param BoxView $view The box.
	 */
	public function render( BoxView $view ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- the included template reads $view.
		// A box the page detection missed (a widget, a theme template) still gets its CSS (Task 18C); Jetpack keeps its
		// image original (F31 D11).
		$this->assets->late_enqueue( $view->image_src );

		ob_start();
		try {
			include $this->templates . '/' . $view->family->value . '.php';
		} finally {
			$html = (string) ob_get_clean();
		}

		return $html;
	}
}
