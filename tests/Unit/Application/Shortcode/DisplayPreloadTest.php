<?php
namespace Affibloom\Tests\Unit\Application\Shortcode;

use Affibloom\Application\Shortcode\DisplayPreload;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Tests\Unit\Fakes\InMemoryAmazonProducts;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use PHPUnit\Framework\TestCase;

/**
 * Task 37B: a page's single boxes load their products and those products' Amazon rows once for the page, before the
 * first box draws, instead of a query per box.
 */
final class DisplayPreloadTest extends TestCase {

	public function test_the_products_then_their_amazon_rows_are_read_together(): void {
		$products          = new InMemoryProductRepository();
		$products->saved[] = self::product( 12, ProductType::Amazon, 'B0F8KBT9W6', 'us' );
		$products->saved[] = self::product( 34, ProductType::Other, '', '' );
		$products->saved[] = self::product( 56, ProductType::Amazon, 'B0D2LTTR65', 'gb' );
		$amazon            = new InMemoryAmazonProducts();

		( new DisplayPreload( $products, $amazon ) )->content( '<!-- wp:affibloom/product-box {"productId":12} /--> [affibloom id="34"] [affibloom id="56"] [affibloom id="99"]' );

		self::assertSame( array( array( 12, 34, 56, 99 ) ), $products->primed );
		self::assertSame( array( array( array( 'B0F8KBT9W6', 'us' ), array( 'B0D2LTTR65', 'gb' ) ) ), $amazon->primed );
	}

	public function test_content_without_a_single_box_reads_nothing(): void {
		$products = new InMemoryProductRepository();
		$amazon   = new InMemoryAmazonProducts();

		( new DisplayPreload( $products, $amazon ) )->content( '<p>No box.</p> [affibloom type="grid" group="pair-grid"]' );

		self::assertSame( array(), $products->primed );
		self::assertSame( array(), $amazon->primed );
	}

	private static function product( int $id, ProductType $type, string $asin, string $country ): Product {
		return new Product( $id, 'Shoe ' . $id, AffiliateUrl::from_string( 'https://www.example.com/' . $id ), $type, true, true, false, true, $asin, $country );
	}
}
