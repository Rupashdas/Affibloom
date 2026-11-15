<?php
/**
 * What an Amazon item is, beyond its title, as the Creators API describes it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * What an Amazon item is (C1Q2): its maker, model, size and colour, codes, category and rank, parent ASIN and every
 * image, read from getItems' itemInfo, browseNodeInfo, images and parentASIN. A group Amazon leaves out stays empty.
 * Lasso reads none of it (R11).
 */
final class ItemDetails {

	/**
	 * Builds the details.
	 *
	 * @param string                $brand         Brand (byLineInfo.brand), or ''.
	 * @param string                $manufacturer  Manufacturer, or ''.
	 * @param string                $model         Model (manufactureInfo.model), or ''.
	 * @param string                $part_number   Part number, or ''.
	 * @param string                $warranty      Warranty, or ''.
	 * @param string                $color         Colour (productInfo.color), or ''.
	 * @param string                $size          Size, or ''.
	 * @param int|null              $unit_count    Number of items in the pack, or null.
	 * @param array<string, string> $dimensions    Item dimensions by name ("height" => "3 inches"), in Amazon's order.
	 * @param string                $product_group Amazon's product group (classifications), or ''.
	 * @param list<string>          $eans          EANs.
	 * @param list<string>          $upcs          UPCs.
	 * @param list<string>          $isbns         ISBNs.
	 * @param string                $category      The first browse node with a rank (its display name), else the first.
	 * @param int|null              $category_rank The item's rank in that category, or null.
	 * @param int|null              $sales_rank    Its rank in the whole store (websiteSalesRank), or null.
	 * @param string                $parent_asin   The parent ASIN of a variation, or ''.
	 * @param list<string>          $images        Every image link, large, the primary first.
	 */
	public function __construct(
		public readonly string $brand = '',
		public readonly string $manufacturer = '',
		public readonly string $model = '',
		public readonly string $part_number = '',
		public readonly string $warranty = '',
		public readonly string $color = '',
		public readonly string $size = '',
		public readonly ?int $unit_count = null,
		public readonly array $dimensions = array(),
		public readonly string $product_group = '',
		public readonly array $eans = array(),
		public readonly array $upcs = array(),
		public readonly array $isbns = array(),
		public readonly string $category = '',
		public readonly ?int $category_rank = null,
		public readonly ?int $sales_rank = null,
		public readonly string $parent_asin = '',
		public readonly array $images = array(),
	) {}
}
