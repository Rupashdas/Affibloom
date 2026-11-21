<?php
/**
 * The Amazon data the scheduled refresh should fetch again.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * The ASINs and stores whose Amazon data is due (C1 R38; C1Q4b): published Amazon products with an ASIN whose stored
 * row is missing or older than the cutoff, oldest first (missing first). Lasso's "Only update products with Locations"
 * arm needs the link index (F21) and is C1E's.
 */
interface DueAmazonData {

	/**
	 * How many ASIN and store pairs are due.
	 *
	 * @param string $before    The cutoff, "Y-m-d H:i:s" in UTC.
	 * @param bool   $used_only Only products used in content (C1 R38's "Only update products with Locations"; Task C1E).
	 */
	public function count( string $before, bool $used_only = false ): int;

	/**
	 * The due pairs, oldest first, each with a product link for the ASIN.
	 *
	 * @param string $before    The cutoff, "Y-m-d H:i:s" in UTC.
	 * @param int    $limit     At most this many.
	 * @param bool   $used_only Only products used in content (Task C1E).
	 * @return list<array{asin: string, country: string, link: string}>
	 */
	public function due( string $before, int $limit, bool $used_only = false ): array;
}
