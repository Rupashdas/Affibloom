<?php
namespace Affibloom\Tests\Unit\Domain\Amazon;

use Affibloom\Domain\Amazon\DiscountPrice;
use Affibloom\Domain\Amazon\PriceFormat;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's Amazon price and discount formats (C1 R50, R51; Task C1F).
 */
final class PriceFormatTest extends TestCase {

	/**
	 * Symbol first with `1,234.50`, SEK `1 234,50 kr`, comma-decimal currencies after, ₹ in front, unknown codes `$`.
	 */
	public function test_prices_are_written_in_each_currencys_style(): void {
		self::assertSame( '$1,234.50', PriceFormat::format( 1234.5, 'USD' ) );
		self::assertSame( '£37.41', PriceFormat::format( 37.41, 'GBP' ) );
		self::assertSame( '1 234,50 kr', PriceFormat::format( 1234.5, 'SEK' ) );
		self::assertSame( '1.234,50 €', PriceFormat::format( 1234.5, 'EUR' ) );
		self::assertSame( '₹999.00', PriceFormat::format( 999, 'INR' ) );
		self::assertSame( '$5.00', PriceFormat::format( 5, 'XYZ' ) );
	}

	/**
	 * The basis only when above the price, in the price's style.
	 */
	public function test_the_basis_shows_only_above_the_price(): void {
		self::assertSame( '$70.00', DiscountPrice::basis( 37.41, 70.0, 'USD' ) );
		self::assertSame( '79,99 €', DiscountPrice::basis( 69.95, 79.99, 'EUR' ) );
		self::assertSame( '', DiscountPrice::basis( 70.0, 70.0, 'USD' ) );
		self::assertSame( '', DiscountPrice::basis( 37.41, null, 'USD' ) );
	}
}
