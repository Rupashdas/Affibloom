<?php
/**
 * Lasso's rule for the Prime logo.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * When a box shows the Prime logo (C1 R46, R47, D9, N2; decision C4; F5 R20, D7): Lasso shows it on every Amazon product
 * with an ASIN while the site's "Show Prime Logo In Displays" is on, not by the product's real Prime status. Affibloom
 * keeps the product page's switch as an opt-out that is on unless an editor turned it off.
 */
final class PrimeRule {

	/**
	 * Whether the logo shows.
	 *
	 * @param bool   $site    The site's Show Prime Logo In Displays.
	 * @param bool   $amazon  The product is an Amazon product.
	 * @param string $asin    Its ASIN ('' none).
	 * @param bool   $product The product's own switch (on unless turned off).
	 */
	public static function shows( bool $site, bool $amazon, string $asin, bool $product ): bool {
		return $site && $amazon && '' !== trim( $asin ) && $product;
	}
}
