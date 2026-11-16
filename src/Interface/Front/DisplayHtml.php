<?php
/**
 * Prints the displays that are not one Single box.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

use Affibloom\Application\Box\BoxView;
use Affibloom\Application\Box\GridView;
use Affibloom\Application\Box\ListView;

/**
 * Prints the group displays and the AI text (F30 D1): a grid or gallery (`templates/front/grid.php`, each card through
 * the box printer), a list (`list.php` with its cards, `list-card.php`) and a stored AI text (`ai-text.php`).
 */
final class DisplayHtml {

	/**
	 * Builds the printer.
	 *
	 * @param string    $templates The `templates/front` folder.
	 * @param BoxHtml   $boxes     The box printer, for the grid's cards.
	 * @param BoxAssets $assets    The box's stylesheets and scripts, for the list and the AI text (not printed by
	 *                             BoxHtml).
	 */
	public function __construct( private readonly string $templates, private readonly BoxHtml $boxes, private readonly BoxAssets $assets ) {}

	/**
	 * A grid's or a gallery's HTML.
	 *
	 * @param GridView $grid The grid.
	 */
	public function grid( GridView $grid ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- the included template reads $grid.
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

	/**
	 * A stored AI text's HTML (F30 R43): the text through `wp_kses_post` (D14), in Lasso's three containers.
	 *
	 * @param string $text The stored text.
	 */
	public function ai_text( string $text ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- the included template reads $text.
		$this->assets->late_enqueue();

		ob_start();
		try {
			include $this->templates . '/ai-text.php';
		} finally {
			$html = (string) ob_get_clean();
		}

		return $html;
	}
}
