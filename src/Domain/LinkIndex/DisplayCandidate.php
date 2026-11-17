<?php
/**
 * One display found in a host's content, before it is tied to its product or group.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * An `[affibloom]` shortcode or an `affibloom/product-box` block (F21 R27, deviation 146): its display type, what it
 * names (a product by ID, slug or link; a group by ID or slug), a link display's label, and its markup, which is its
 * identity in the index (its "URL").
 */
final class DisplayCandidate {

	/**
	 * Describes the display.
	 *
	 * @param string      $display_type DisplayType's label (Single, Button, …).
	 * @param string      $markup       The shortcode as written, or the block's name and its addressing attributes.
	 * @param int         $product_id   The product's ID, 0 when none.
	 * @param string      $product_slug The product's slug, '' when none (it wins over the ID, F31 A1).
	 * @param string      $product_link `amazon_url` or `geni_url`, '' when none.
	 * @param int         $group_id     The group's term ID (the block), 0 when none.
	 * @param string      $group_slug   The group's slug (the shortcode), '' when none.
	 * @param string|null $label        A text link's label.
	 */
	public function __construct(
		public readonly string $display_type,
		public readonly string $markup,
		public readonly int $product_id = 0,
		public readonly string $product_slug = '',
		public readonly string $product_link = '',
		public readonly int $group_id = 0,
		public readonly string $group_slug = '',
		public readonly ?string $label = null,
	) {}
}
