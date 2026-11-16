<?php
/**
 * Everything a group's list prints.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Box;

/**
 * A group shown as a list (F30 R21, R22): the `<ol>` or `<ul>` with its bullets' classes and the List settings'
 * attributes, one card per product. No list-level disclosure.
 */
final class ListView {

	/**
	 * Builds the view.
	 *
	 * @param string                $anchor  The list's `id`.
	 * @param string                $tag     `ol` or `ul`.
	 * @param string                $classes The list's classes.
	 * @param array<string, string> $wrapper The list's attributes (customised settings, without the price and disclosure
	 *                                       flags).
	 * @param list<BoxView>         $cards   The cards in the group's order.
	 */
	public function __construct(
		public readonly string $anchor,
		public readonly string $tag,
		public readonly string $classes,
		public readonly array $wrapper,
		public readonly array $cards,
	) {}
}
