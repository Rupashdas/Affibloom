<?php
/**
 * The struck basis price of an Amazon product.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * Lasso's discount (C1 R51; Lasso_Amazon_Api::build_discount_pricing_html): the basis, two decimals, in the price's
 * style (symbol first with `1,234.50`, or `1.234,50 €` after), when it is above the price; '' otherwise. The box wraps it
 * in `<strike>` (the price partial).
 */
final class DiscountPrice {

	/**
	 * The basis as Lasso prints it, or ''.
	 *
	 * @param float|null $price    The price.
	 * @param float|null $basis    The list or was price.
	 * @param string     $currency The price's ISO code.
	 */
	public static function basis( ?float $price, ?float $basis, string $currency ): string {
		if ( null === $price || null === $basis || round( $price, 2 ) >= round( $basis, 2 ) ) {
			return '';
		}

		$symbol = PriceFormat::symbol( $currency );

		return PriceFormat::comma_decimals( $symbol )
			? number_format( $basis, 2, ',', '.' ) . ' ' . $symbol
			: $symbol . number_format( $basis, 2, '.', ',' );
	}
}
