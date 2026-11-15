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
}
