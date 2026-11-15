<?php
namespace Affibloom\Tests\Unit\Domain\Amazon;

use Affibloom\Domain\Amazon\Asin;
use Affibloom\Domain\Amazon\CatalogItem;
use PHPUnit\Framework\TestCase;

/**
 * Reading getItems' items (C1 R11), against the recorded answers in tests/fixtures/amazon.
 */
final class CatalogItemTest extends TestCase {
	/**
	 * The first item of a recorded getItems answer.
	 *
	 * @return array<array-key, mixed>
	 */
	public static function recorded( string $file ): array {
		$json = strtr( (string) file_get_contents( dirname( __DIR__, 3 ) . '/fixtures/amazon/' . $file ), array( '{{ASIN}}' => 'B0STUBITEM', '{{MARKETPLACE}}' => 'www.amazon.com' ) );
		return json_decode( $json, true )['itemsResult']['items'][0];
	}

	public function test_an_item_in_stock_with_a_saving(): void {
		$item = CatalogItem::from_creators( self::recorded( 'getItems-ok.json' ) );
		self::assertSame( 'B0STUBITEM', $item->asin );
		self::assertSame( 'Mikasa V200W Official Game Volleyball', $item->title );
		self::assertSame( 'https://m.media-amazon.com/images/I/71stubLarge.jpg', $item->image, 'large first' );
		self::assertSame( '$69.95', $item->price );
		self::assertSame( array( '$10.04', 13, '$79.99' ), array( $item->savings, $item->savings_percent, $item->basis ) );
		self::assertTrue( $item->availability->in_stock );
		self::assertSame( array( 'FIVB approved for international play', 'Double-layer design for a soft touch' ), $item->features );
		self::assertStringStartsWith( 'https://www.amazon.com/dp/B0STUBITEM', $item->url );
	}

	public function test_an_item_out_of_stock_with_only_a_small_image_and_no_price(): void {
		$item = CatalogItem::from_creators( self::recorded( 'getItems-oos.json' ) );
		self::assertSame( 'https://m.media-amazon.com/images/I/31stubSmall.jpg', $item->image );
		self::assertSame( '', $item->price );
		self::assertSame( '', $item->savings );
		self::assertFalse( $item->availability->in_stock );
		self::assertSame( 'OUT_OF_STOCK', $item->availability->type );
		self::assertSame( array(), $item->features );
	}

	public function test_a_bare_price_display_amount_is_read_as_lasso_does(): void {
		$item = CatalogItem::from_creators( array( 'asin' => 'X', 'offersV2' => array( 'listings' => array( array( 'price' => array( 'displayAmount' => '£5.00' ) ) ) ) ) );
		self::assertSame( '£5.00', $item->price );
	}

	public function test_an_item_with_no_listing_is_out_of_stock_and_odd_values_are_ignored(): void {
		$item = CatalogItem::from_creators( array( 'asin' => 'X', 'itemInfo' => array( 'title' => array( 'displayValue' => array( 'not text' ) ), 'features' => array( 'displayValues' => array( 'one', 2 ) ) ) ) );
		self::assertSame( '', $item->title );
		self::assertSame( array( 'one' ), $item->features );
		self::assertFalse( $item->availability->in_stock );
	}

	public function test_an_asin_is_trimmed_upper_case_letters_and_digits(): void {
		self::assertSame( 'B0STUBITEM', Asin::from( ' b0stubitem ' )?->value );
		self::assertNull( Asin::from( '' ) );
		self::assertNull( Asin::from( 'not an asin' ) );
		self::assertNull( Asin::from( str_repeat( 'A', 21 ) ) );
	}
}
