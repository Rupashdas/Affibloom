<?php
/**
 * What Amazon's answers tell link health.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Amazon;

/**
 * Availability handed to link health (C1 R55-R57, D11; Task C1H): out of stock and not found exist only from product
 * data, so every fetch of an ASIN in a store says what it learned, for every product of that ASIN and store.
 */
interface LinkHealth {

	/**
	 * Amazon gave the item: out of stock (`000`) or in stock (any open issue is resolved, R57).
	 *
	 * @param string $asin     The ASIN.
	 * @param string $country  The store's country code.
	 * @param bool   $in_stock Whether Amazon can sell it now.
	 */
	public function found( string $asin, string $country, bool $in_stock ): void;

	/**
	 * Amazon does not know the item (`404`).
	 *
	 * @param string $asin    The ASIN.
	 * @param string $country The store's country code.
	 */
	public function not_found( string $asin, string $country ): void;
}
