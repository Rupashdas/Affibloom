<?php
/**
 * What the link check of one product asks about.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkHealth;

/**
 * What the check of one product asks about (F11 D5, D7, D8): its stored primary destination, and, for an Amazon
 * product, its ASIN and store, which the product API is asked about instead of the link.
 */
final class CheckTarget {

	/**
	 * Builds the target.
	 *
	 * @param string $url     The stored primary destination ('' when blank).
	 * @param string $asin    The Amazon product's ASIN; '' for other products.
	 * @param string $country The Amazon store's country code; '' for other products.
	 */
	public function __construct(
		public readonly string $url,
		public readonly string $asin = '',
		public readonly string $country = '',
	) {}

	/**
	 * Whether the product API answers for it.
	 */
	public function is_amazon(): bool {
		return '' !== $this->asin;
	}
}
