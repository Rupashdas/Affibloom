<?php
/**
 * Everything a group's grid prints.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Box;

/**
 * A group shown as a grid (F30 R16): the wrapper, its cards (Single boxes in the Grid settings, without their own
 * container id) and the one disclosure after them.
 */
final class GridView {

	/**
	 * Builds the view.
	 *
	 * @param string                $anchor     The wrapper's `id`.
	 * @param string                $classes    The wrapper's classes.
	 * @param array<string, string> $wrapper    The wrapper's attributes (customised settings; Lasso leaves out the price flag).
	 * @param list<BoxView>         $cards      The cards in the group's order.
	 * @param string|null           $disclosure The disclosure text after the cards; null when it does not show.
	 * @param int|null              $per_row    Classic settings: the cards in rows of this many (R16); null one row
	 *                                          holding every card (customised: the CSS grid places them).
	 */
	public function __construct(
		public readonly string $anchor,
		public readonly string $classes,
		public readonly array $wrapper,
		public readonly array $cards,
		public readonly ?string $disclosure,
		public readonly ?int $per_row = null,
	) {}
}
