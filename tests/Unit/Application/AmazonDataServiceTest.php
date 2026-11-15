<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Amazon\AmazonDataService;
use Affibloom\Domain\Amazon\AmazonProduct;
use Affibloom\Domain\Amazon\Asin;
use Affibloom\Domain\Amazon\CatalogItem;
use Affibloom\Domain\Amazon\Credentials;
use Affibloom\Domain\Amazon\Refusal;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Domain\Amazon\CatalogItemTest;
use Affibloom\Tests\Unit\Fakes\FakeAmazonCatalog;
use Affibloom\Tests\Unit\Fakes\InMemoryAmazonProducts;
use Affibloom\Tests\Unit\Fakes\InMemoryCredentials;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Application\Amazon\RefreshFailed;
use PHPUnit\Framework\TestCase;

/**
 * What a new Amazon product takes from Amazon on add (C1C; R27, R32, D7; AC1, AC2), and Refresh (C1D; R35; AC4).
 */
final class AmazonDataServiceTest extends TestCase {
	private FakeAmazonCatalog $amazon;
	private InMemoryAmazonProducts $rows;
	private InMemoryProductRepository $library;
	private InMemoryCredentials $credentials;

	private function service( bool $configured = true, string $answer = 'ok' ): AmazonDataService {
		$this->amazon  = new FakeAmazonCatalog( $answer );
		$this->rows    = new InMemoryAmazonProducts();
		$this->library = new InMemoryProductRepository();
		$credentials  = $configured ? new Credentials( 'id', 'secret', 'tag-20', '3.1' ) : new Credentials();
		$this->credentials = new InMemoryCredentials( $credentials );
		return new AmazonDataService( $this->credentials, $this->amazon, $this->rows, $this->library );
	}

	private static function draft( string $asin = 'B0STUBITEM', string $country = 'us' ): Product {
		return Product::draft( 'Starting Name', AffiliateUrl::from_string( "https://www.amazon.com/dp/$asin?tag=vbvault-20" ), Settings::from_array( array() ) )->with(
			array(
				'type'           => ProductType::Amazon,
				'asin'           => $asin,
				'amazon_country' => $country,
			)
		);
	}

	private function item( string $asin = 'B0STUBITEM', string $fixture = 'getItems-ok.json' ): void {
		$item = CatalogItemTest::recorded( $fixture );
		$item['asin'] = $asin;
		$this->amazon->items[ $asin ] = CatalogItem::from_creators( $item );
	}

	public function test_a_new_product_takes_amazons_name_and_image_and_the_data_is_stored(): void {
		$service = $this->service();
		$this->item();

		$product = $service->fill( self::draft() );

		self::assertSame( 'Mikasa V200W Official Game Volleyball', $product->name );
		self::assertSame( 'https://m.media-amazon.com/images/I/71stubLarge.jpg', $product->image_url );
		$row = $this->rows->find( 'B0STUBITEM', 'us' );
		self::assertNotNull( $row );
		self::assertSame( array( '$69.95', 'USD', false, 'creators' ), array( $row->price, $row->currency, $row->out_of_stock, $row->source ) );
		// The numbers the discount rule compares (C1Q3).
		self::assertSame( array( 69.95, 79.99 ), array( $row->price_amount, $row->basis_amount ) );
		self::assertSame( 'https://www.amazon.com/dp/B0STUBITEM', $row->base_url );
		self::assertSame( 'https://www.amazon.com/dp/B0STUBITEM?tag=vbvault-20', $row->monetized_url );
		self::assertMatchesRegularExpression( '/^\d{4}-\d\d-\d\d \d\d:\d\d:\d\d$/', $row->last_updated );
	}

	public function test_stored_data_for_the_asin_and_store_is_reused_without_asking_amazon(): void {
		$service = $this->service();
		$this->rows->save( new AmazonProduct( 'B0STUBITEM', 'gb', 'Stored name', '£5.00', 'GBP', 'b', 'old', 'https://m.media-amazon.com/x.jpg', false, '', 0, '', array(), 'lasso', '2026-11-09 00:11:00' ) );

		$product = $service->fill( self::draft( 'B0STUBITEM', 'gb' ) );

		self::assertSame( 'Stored name', $product->name );
		self::assertSame( 'https://www.amazon.com/dp/B0STUBITEM?tag=vbvault-20', $this->rows->find( 'B0STUBITEM', 'gb' )?->monetized_url, 'stored again with the new link' );
		self::assertSame( 'lasso', $this->rows->find( 'B0STUBITEM', 'gb' )?->source );
	}

	public function test_another_store_is_another_row(): void {
		$service = $this->service();
		$this->rows->save( new AmazonProduct( 'B0STUBITEM', 'gb', 'UK name', '', 'GBP', 'b', 'm', 'https://m.media-amazon.com/x.jpg', false, '', 0, '', array(), 'lasso', '2026-11-09 00:11:00' ) );
		$this->item();

		self::assertSame( 'Mikasa V200W Official Game Volleyball', $service->fill( self::draft( 'B0STUBITEM', 'us' ) )->name );
	}

	public function test_without_credentials_nothing_is_asked_and_the_product_keeps_its_name(): void {
		$service = $this->service( false );
		$this->item();

		$product = $service->fill( self::draft() );

		self::assertSame( array( 'Starting Name', '' ), array( $product->name, $product->image_url ) );
		self::assertSame( array(), $this->rows->rows );
	}

	public function test_not_found_or_an_error_keeps_the_name_and_stores_nothing(): void {
		$service = $this->service(); // No item set: NotFound.
		self::assertSame( 'Starting Name', $service->fill( self::draft() )->name );
		self::assertSame( array(), $this->rows->rows );
	}

	public function test_an_item_without_an_image_names_the_product_but_is_not_stored(): void {
		$service = $this->service();
		$item    = CatalogItemTest::recorded( 'getItems-ok.json' );
		unset( $item['images'] );
		$this->amazon->items['B0STUBITEM'] = CatalogItem::from_creators( $item );

		$product = $service->fill( self::draft() );

		self::assertSame( 'Mikasa V200W Official Game Volleyball', $product->name );
		self::assertSame( '', $product->image_url );
		self::assertSame( array(), $this->rows->rows, 'Lasso refuses a row without an image' );
	}

	public function test_a_product_without_an_asin_is_left_alone(): void {
		$service = $this->service();
		$search  = Product::draft( 'Amazon: volleyball', AffiliateUrl::from_string( 'https://www.amazon.com/s?k=volleyball' ), Settings::from_array( array() ) );

		self::assertSame( $search, $service->fill( $search ) );
	}

	public function test_a_newer_row_keeps_the_older_price_and_image_when_its_own_are_empty(): void {
		$older = new AmazonProduct( 'A', 'us', 'Old', '$1.00', 'USD', 'b', 'm', 'https://m.media-amazon.com/old.jpg', false, '', 0, '', array(), 'lasso', '2026-01-01 00:00:00' );
		$newer = new AmazonProduct( 'A', 'us', 'New', '', 'USD', 'b', 'm', '', true, '', 0, '', array(), 'creators', '2026-11-15 00:00:00' );

		$merged = $newer->over( $older );

		self::assertSame( array( 'New', '$1.00', 'https://m.media-amazon.com/old.jpg', true ), array( $merged->name, $merged->price, $merged->image, $merged->out_of_stock ) );
		self::assertFalse( AmazonProduct::is_image_link( 'http://example.com/x.jpg' ) );
		self::assertFalse( AmazonProduct::is_image_link( 'data:image/png;base64,AAA' ) );
	}

	public function test_a_newer_row_keeps_lassos_prime_flag(): void {
		$older = new AmazonProduct( 'A', 'us', 'Old', '', 'USD', 'b', 'm', 'https://m.media-amazon.com/x.jpg', false, '', 0, '', array(), 'lasso', '2026-01-01 00:00:00', true );
		$newer = new AmazonProduct( 'A', 'us', 'New', '', 'USD', 'b', 'm', 'https://m.media-amazon.com/x.jpg', false, '', 0, '', array(), 'creators', '2026-11-15 00:00:00' );

		self::assertTrue( $newer->over( $older )->is_prime );
		self::assertTrue( $older->linked( 'other' )->is_prime );
	}

	public function test_lasso_stores_http_and_data_images_but_a_product_shows_only_https(): void {
		$service = $this->service();
		$this->rows->save( new AmazonProduct( 'B0STUBITEM', 'us', 'Old http row', '', 'USD', 'b', 'm', 'http://ecx.images-amazon.com/x.jpg', false, '', 0, '', array(), 'lasso', '2023-08-31 06:20:00' ) );

		$product = $service->fill( self::draft() );

		self::assertSame( 'Old http row', $product->name );
		self::assertSame( '', $product->image_url );
		self::assertStringContainsString( 'tag=vbvault-20', (string) $this->rows->find( 'B0STUBITEM', 'us' )?->monetized_url, 'stored again with the new link' );
	}

	public function test_the_row_is_kept_under_the_asin_asked_for_and_a_zero_price_is_none(): void {
		$service = $this->service();
		$item    = CatalogItemTest::recorded( 'getItems-ok.json' );
		$item['asin'] = 'B0PARENT01';
		$item['offersV2']['listings'][0]['price']['money']['displayAmount'] = '0';
		$this->amazon->items['B0STUBITEM'] = CatalogItem::from_creators( $item );

		$service->fill( self::draft() );

		$row = $this->rows->find( 'B0STUBITEM', 'us' );
		self::assertNotNull( $row );
		self::assertSame( '', $row->price );
		self::assertSame( 'https://www.amazon.com/dp/B0STUBITEM', $row->base_url );
	}

	private function stored( string $name, int $image_id = 0 ): int {
		return (int) $this->library->save( self::draft()->with( array( 'name' => $name, 'image_url' => 'https://example.com/old.jpg', 'image_id' => $image_id ) ) )->id;
	}

	private function refused( callable $refresh ): RefreshFailed {
		try {
			$refresh();
		} catch ( RefreshFailed $refused ) {
			return $refused;
		}
		self::fail( 'Refresh did not refuse' );
	}

	public function test_refresh_takes_the_image_and_keeps_the_editors_name(): void {
		$service = $this->service();
		$this->item();
		$id = $this->stored( 'My own words', 42 );

		$result = $service->refresh( $id );

		self::assertSame( 'My own words', $result['product']->name );
		self::assertSame( array( 'https://m.media-amazon.com/images/I/71stubLarge.jpg', 0 ), array( $result['product']->image_url, $result['product']->image_id ) );
		self::assertSame( 'https://m.media-amazon.com/images/I/71stubLarge.jpg', $this->library->get( $id )?->image_url, 'saved at once' );
		self::assertSame( '$69.95', $result['data']->price );
		self::assertNotNull( $this->rows->find( 'B0STUBITEM', 'us' ) );
		// Amazon's bullets replace the Features; the description is not touched (C1Q4).
		self::assertSame( array( 'FIVB approved for international play', 'Double-layer design for a soft touch' ), $this->library->get( $id )?->features );
		self::assertSame( '', $this->library->get( $id )?->description );
	}

	public function test_refresh_replaces_a_placeholder_name(): void {
		foreach ( array( 'Amazon', '', 'Add a Link Title' ) as $placeholder ) {
			$service = $this->service();
			$this->item();
			$result  = $service->refresh( $this->stored( $placeholder ) );
			self::assertSame( 'Mikasa V200W Official Game Volleyball', $result['product']->name, "'$placeholder'" );
			self::assertTrue( $result['renamed'] );
		}
	}

	public function test_refresh_does_not_move_the_product_to_the_top_unless_it_renames_it(): void {
		$service = $this->service();
		$this->item();
		$refreshed = $this->stored( 'My own words' );
		$newest    = $this->stored( 'Added later' );

		self::assertFalse( $service->refresh( $refreshed )['renamed'] );
		self::assertSame( $newest, end( $this->library->saved )->id, 'Lasso writes the image as post meta only' );
	}

	public function test_refresh_always_asks_amazon_even_with_stored_data(): void {
		$service = $this->service();
		$this->rows->save( new AmazonProduct( 'B0STUBITEM', 'us', 'Stored', '$1.00', 'USD', 'b', 'm', 'https://m.media-amazon.com/x.jpg', true, '', 0, '', array(), 'lasso', '2023-01-01 00:00:00' ) );
		$this->item();

		$data = $service->refresh( $this->stored( 'Amazon' ) )['data'];

		self::assertSame( array( '$69.95', false, 'creators' ), array( $data->price, $data->out_of_stock, $data->source ) );
	}

	public function test_refresh_refusals(): void {
		$service = $this->service();
		self::assertSame( RefreshFailed::NOT_FOUND, $this->refused( fn() => $service->refresh( 999 ) )->reason );

		$other = (int) $this->library->save( Product::draft( 'Shop', AffiliateUrl::from_string( 'https://shop.example/x' ), Settings::from_array( array() ) ) )->id;
		self::assertSame( RefreshFailed::NOT_AMAZON, $this->refused( fn() => $service->refresh( $other ) )->reason );

		$amazon  = $this->stored( 'Amazon' );
		$refused = $this->refused( fn() => $service->refresh( $amazon ) ); // No item: NotFound.
		self::assertSame( array( RefreshFailed::FAILED, 'NotFound' ), array( $refused->reason, $refused->kind ) );
		self::assertSame( 'https://example.com/old.jpg', $this->library->get( $amazon )?->image_url, 'nothing changed' );

		$item = CatalogItemTest::recorded( 'getItems-ok.json' );
		unset( $item['images'] );
		$this->amazon->items['B0STUBITEM'] = CatalogItem::from_creators( $item );
		self::assertSame( RefreshFailed::NO_IMAGE, $this->refused( fn() => $service->refresh( $this->stored( 'Amazon' ) ) )->reason );

		$unset = $this->service( false );
		self::assertFalse( $unset->can_ask_amazon() );
		self::assertSame( RefreshFailed::NOT_CONFIGURED, $this->refused( fn() => $unset->refresh( $this->stored( 'Amazon' ) ) )->reason );
		self::assertSame( array(), $this->amazon->verified );
	}

	public function test_refused_credentials_ask_amazon_nothing(): void {
		$service = $this->service();
		$this->item();
		$this->credentials->refusal = new Refusal( 'HTTP 401, invalid_client', '2026-11-15 07:46:58' );

		self::assertFalse( $service->can_ask_amazon() );
		self::assertSame( 'Starting Name', $service->fill( self::draft() )->name );
		self::assertSame( array(), $this->rows->rows );
	}

	public function test_a_row_without_a_price_keeps_the_older_price_and_its_number(): void {
		$older = new AmazonProduct( 'A', 'us', 'Old', '$1.00', 'USD', 'b', 'm', 'https://m.media-amazon.com/old.jpg', false, '', 0, '', array(), 'creators', '2026-01-01 00:00:00', false, 1.0, 2.0 );
		$newer = AmazonProduct::from_item( CatalogItem::from_creators( CatalogItemTest::recorded( 'live-B0F7RNL68R-all.json' ) ), Asin::from( 'A' ), 'us', 'm', '2026-11-15 00:00:00' );

		self::assertSame( array( null, null ), array( $newer->price_amount, $newer->basis_amount ), 'out of stock: no price' );
		$merged = $newer->over( $older );
		self::assertSame( array( '$1.00', 1.0, null, true ), array( $merged->price, $merged->price_amount, $merged->basis_amount, $merged->out_of_stock ) );
	}

	public function test_an_add_takes_amazons_features_and_leaves_the_description_alone(): void {
		$service = $this->service();
		$this->item();

		$product = $service->fill( self::draft() );

		self::assertSame( array( 'FIVB approved for international play', 'Double-layer design for a soft touch' ), $product->features );
		self::assertSame( '', $product->description );
	}
}
