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
use Affibloom\Application\Box\ListView;

/**
 * Prints a group display (F30 R16, R21, D1): a grid (`templates/front/grid.php`, each card through the box printer)
 * or a list (`list.php` with its cards, `list-card.php`).
 */
final class GridHtml {

	/**
	 * Builds the printer.
	 *
	 * @param string    $templates The `templates/front` folder.
	 * @param BoxHtml   $boxes     The box printer, for the grid's cards.
	 * @param BoxAssets $assets    The box's stylesheets and scripts, for the list's cards (not printed by BoxHtml).
	 */
	public function __construct( private readonly string $templates, private readonly BoxHtml $boxes, private readonly BoxAssets $assets ) {}

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

	/**
	 * The list's HTML. The box stylesheets come with it, as with every box drawn (F5 R47).
	 *
	 * @param ListView $group_list The list.
	 */
	public function list( ListView $group_list ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- the included template reads $group_list.
		$this->assets->late_enqueue();
		foreach ( $group_list->cards as $listed ) {
			$this->assets->late_enqueue( $listed->image_src );
		}

		ob_start();
		try {
			include $this->templates . '/list.php';
		} finally {
			$html = (string) ob_get_clean();
		}

		return $html;
	}
}
