<?php
/**
 * Prints a group's grid.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

use Affibloom\Application\Box\BoxView;
use Affibloom\Application\Box\GridView;

/**
 * Prints a grid (F30 R16, D1): `templates/front/grid.php`, each card through the box printer.
 */
final class GridHtml {

	/**
	 * Builds the printer.
	 *
	 * @param string  $templates The `templates/front` folder.
	 * @param BoxHtml $boxes     The box printer, for the cards.
	 */
	public function __construct( private readonly string $templates, private readonly BoxHtml $boxes ) {}

	/**
	 * The grid's HTML.
	 *
	 * @param GridView $grid The grid.
	 */
	public function render( GridView $grid ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- the included template reads $grid.
		$card = fn( BoxView $view ): string => $this->boxes->render( $view );

		ob_start();
		try {
			include $this->templates . '/grid.php';
		} finally {
			$html = (string) ob_get_clean();
		}

		return $html;
	}
}
