<?php
namespace Affibloom\Tests\Unit\Domain\Cloak;

use Affibloom\Domain\Cloak\PublicLink;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductSlug;
use Affibloom\Domain\Product\ProductType;
use PHPUnit\Framework\TestCase;

/**
 * The public link and the Permalink's rules (F24 R4, R8, R13, D9-D12; Task CL3).
 */
final class PublicLinkTest extends TestCase {

	private static function product( bool $cloak, ProductType $type = ProductType::Other, string $slug = 'aircat' ): Product {
		$url = ProductType::Amazon === $type ? 'https://www.amazon.com/dp/B0F24TEST1?tag=t-20' : 'https://www.aircat.test/shop';

		return ( new Product( 7, 'AirCat', AffiliateUrl::from_string( $url ), $type, true, true, false, true ) )->with(
			array(
				'slug'  => $slug,
				'cloak' => $cloak,
			)
		);
	}

	public function test_a_cloaked_product_shows_its_address_with_the_prefix(): void {
		self::assertSame( 'https://site.test/aircat/', PublicLink::for( self::product( true ), 'https://site.test/', '' ) );
		self::assertSame( 'https://site.test/go/aircat/', PublicLink::for( self::product( true ), 'https://site.test', '/go/' ) );
	}

	public function test_without_cloaking_or_for_amazon_the_destination(): void {
		self::assertSame( 'https://www.aircat.test/shop', PublicLink::for( self::product( false ), 'https://site.test', '' ) );
		self::assertSame( 'https://www.amazon.com/dp/B0F24TEST1?tag=t-20', PublicLink::for( self::product( true, ProductType::Amazon ), 'https://site.test', '' ) );
	}

	public function test_the_permalink_is_lassos_slug_form_without_a_repeated_suffix(): void {
		self::assertSame( 'my-shoe-2', ProductSlug::clean( '  My Shoe_2!! ' ) );
		self::assertSame( 'shoe', ProductSlug::clean( 'shoe-2-2-2' ) );
		self::assertSame( 'shoe-2', ProductSlug::clean( 'shoe-2' ) );
		self::assertSame( '', ProductSlug::clean( ' -- ' ) );
	}

	public function test_a_new_product_s_slug_is_its_name_s_with_amzn_for_amazon(): void {
		self::assertSame( 'mikasa-v200w', ProductSlug::for_new( 'Mikasa V200W', false ) );
		self::assertSame( 'amzn-mikasa-v200w', ProductSlug::for_new( 'Mikasa V200W', true ) );
		self::assertSame( 200, strlen( ProductSlug::for_new( str_repeat( 'a', 300 ), true ) ) );
	}
}
