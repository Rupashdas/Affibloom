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
	 * @param string $before The cutoff, "Y-m-d H:i:s" in UTC.
	 */
	public function count( string $before ): int;

	/**
	 * The due pairs, oldest first, each with a product link for the ASIN.
	 *
	 * @param string $before The cutoff, "Y-m-d H:i:s" in UTC.
	 * @param int    $limit  At most this many.
	 * @return list<array{asin: string, country: string, link: string}>
	 */
	public function due( string $before, int $limit ): array;
}
