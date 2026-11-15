<?php
/**
 * Where Amazon's data for each ASIN and store is kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * Where Amazon's data for each ASIN and store is kept (C1 D5).
 */
interface AmazonProductRepository {

	/**
	 * The stored row for an ASIN on a store, or null.
	 *
	 * @param string $asin    The ASIN.
	 * @param string $country The store's country code.
	 */
	public function find( string $asin, string $country ): ?AmazonProduct;

	/**
	 * Stores a row, replacing the one for the same ASIN and store.
	 *
	 * @param AmazonProduct $product The row; only a complete one (AmazonProduct::complete()) is passed.
	 */
	public function save( AmazonProduct $product ): void;

	/**
	 * Moves a row's last-updated time on and changes nothing else (C1Q4b; Lasso's job does this for an item Amazon has
	 * not found, so it is not asked again for 24 hours, R39). No row, nothing happens.
	 *
	 * @param string $asin    The ASIN.
	 * @param string $country The store's country code.
	 * @param string $now     The time, "Y-m-d H:i:s" in UTC.
	 */
	public function touch( string $asin, string $country, string $now ): void;
}
