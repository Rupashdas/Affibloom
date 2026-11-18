<?php
/**
 * An Amazon product's link status, asked of Amazon's product API.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkHealth;

use Affibloom\Domain\LinkHealth\CheckResult;

/**
 * An Amazon product's status from the product API (F11 R10, R16, D7, D8): Amazon's pages are never requested.
 */
interface AmazonProductCheck {

	/**
	 * Whether the product API can be asked (credentials configured and not refused).
	 */
	public function is_configured(): bool;

	/**
	 * What the product API says about the item: a certain 200, 000 (no stock) or 404 (not found), or no check when
	 * the API could not be asked.
	 *
	 * @param string $asin    The ASIN.
	 * @param string $country The store's country code.
	 */
	public function status( string $asin, string $country ): CheckResult;
}
