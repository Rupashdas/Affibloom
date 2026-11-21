<?php
/**
 * A product's place in a comparison table.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Table;

/**
 * A product in a table (F25 R1's mapping row; D1): the product and the badge text shown on its header cell.
 */
final class TableProduct {

	/**
	 * Builds the entry.
	 *
	 * @param int    $id    The product.
	 * @param string $badge Its badge in this table; '' for none.
	 */
	public function __construct( public readonly int $id, public readonly string $badge = '' ) {}
}
