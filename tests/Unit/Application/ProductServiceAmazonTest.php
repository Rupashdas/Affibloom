<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Amazon\AmazonDataService;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\Amazon\AmazonProduct;
use Affibloom\Domain\Amazon\CatalogItem;
use Affibloom\Domain\Amazon\Credentials;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Domain\Amazon\CatalogItemTest;
use Affibloom\Tests\Unit\Fakes\FakeAmazonCatalog;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemoryAmazonProducts;
use Affibloom\Tests\Unit\Fakes\InMemoryCredentials;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;

/**
 * Amazon's data through ProductService (C1C): a product named exactly "Amazon" takes its stored name when read (R34);
 * a Primary URL changed to another ASIN takes the new product's image, and its name per Keep Title (C1O; R33, AC16).
 */
final class ProductServiceAmazonTest extends TestCase {
	private InMemoryProductRepository $repo;
	private InMemoryAmazonProducts $rows;
	private FakeAmazonCatalog $amazon;

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\Functions\when( 'sanitize_text_field' )->alias( static fn( $s ) => trim( strip_tags( (string) $s ) ) );
	}

	/**
	 * @param array<string, mixed> $settings   Settings changed from the defaults.
	 * @param bool                 $configured Whether the Creators API credentials are set.
	 */
	private function service( array $settings = array(), bool $configured = false ): ProductService {
		$this->repo   = new InMemoryProductRepository();
		$this->rows   = new InMemoryAmazonProducts();
		$this->amazon = new FakeAmazonCatalog();
		$resolver     = new LinkResolver( new FakePageFetcher() );
		$repository   = new InMemorySettingsRepository( $settings + array( 'tracking_id' => 'vbvault-20' ) );
		$links        = new LinkNormaliser( $resolver, $repository );
		$credentials  = new InMemoryCredentials( $configured ? new Credentials( 'id', 'secret', 'tag-20', '3.1' ) : new Credentials() );
		return new ProductService( $this->repo, $resolver, $repository, new ProductInput( $links ), $links, new AmazonDataService( $credentials, $this->amazon, $this->rows, $this->repo ) );
	}

	private function stored( string $name ): Product {
		return $this->repo->save(
			Product::draft( $name, AffiliateUrl::from_string( 'https://www.amazon.com/dp/B0STUBITEM' ), Settings::from_array( array() ) )->with(
				array(
					'type'           => ProductType::Amazon,
					'asin'           => 'B0STUBITEM',
					'amazon_country' => 'us',
				)
			)
		);
	}

	/** Amazon has the recorded item under this ASIN. */
	private function amazon_has( string $asin ): void {
		$item                         = CatalogItemTest::recorded( 'getItems-ok.json' );
		$item['asin']                 = $asin;
		$this->amazon->items[ $asin ] = CatalogItem::from_creators( $item );
	}

	/** Save Changes with a Primary URL to another ASIN. */
	private static function replace_link( ProductService $service, Product $product ): Product {
		return $service->update( (int) $product->id, array( 'url' => 'https://www.amazon.com/Tachikara-Ball/dp/B07WPQR123' ) );
	}

	public function test_a_product_named_amazon_takes_and_keeps_the_stored_name(): void {
		$service = $this->service();
		$this->rows->save( new AmazonProduct( 'B0STUBITEM', 'us', 'Mikasa V200W', '', 'USD', 'b', 'm', 'https://m.media-amazon.com/x.jpg', false, '', 0, '', array(), 'lasso', '2026-11-09 00:11:00' ) );
		$id = (int) $this->stored( 'Amazon' )->id;

		self::assertSame( 'Mikasa V200W', $service->get( $id )?->name );
		self::assertSame( 'Mikasa V200W', $this->repo->get( $id )?->name, 'stored, as Lasso writes post_title' );
	}

	public function test_any_other_name_or_no_stored_data_leaves_the_product_alone(): void {
		$service = $this->service();
		$id      = (int) $this->stored( 'Amazon' )->id;
		self::assertSame( 'Amazon', $service->get( $id )?->name, 'no stored data' );

		$this->rows->save( new AmazonProduct( 'B0STUBITEM', 'us', 'Mikasa V200W', '', 'USD', 'b', 'm', 'https://m.media-amazon.com/x.jpg', false, '', 0, '', array(), 'lasso', '2026-11-09 00:11:00' ) );
		$other = (int) $this->stored( 'Amazon: volleyball' )->id;
		self::assertSame( 'Amazon: volleyball', $service->get( $other )?->name, 'only exactly "Amazon", as Lasso' );
	}

	public function test_a_product_without_a_store_reads_the_us_row(): void {
		$service = $this->service();
		$this->rows->save( new AmazonProduct( 'B0STUBITEM', 'us', 'Mikasa V200W', '', 'USD', 'b', 'm', 'https://m.media-amazon.com/x.jpg', false, '', 0, '', array(), 'lasso', '2026-11-09 00:11:00' ) );
		$product = $this->repo->save( $this->stored( 'Amazon' )->with( array( 'amazon_country' => '' ) ) );

		self::assertSame( 'Mikasa V200W', $service->get( (int) $product->id )?->name, 'as fill(): the default store, us' );
	}

	public function test_the_stored_row_is_read_for_the_products_asin_and_store(): void {
		$this->service();
		$data = new AmazonDataService( new InMemoryCredentials(), new FakeAmazonCatalog(), $this->rows, new InMemoryProductRepository() );
		$this->rows->save( new AmazonProduct( 'B0STUBITEM', 'us', 'Mikasa V200W', '$1.00', 'USD', 'b', 'm', 'https://m.media-amazon.com/x.jpg', false, '', 0, '', array(), 'lasso', '2026-11-09 00:11:00' ) );

		self::assertSame( '$1.00', $data->stored( $this->stored( 'Mine' ) )?->price );
		self::assertNull( $data->stored( $this->stored( 'Mine' )->with( array( 'asin' => '' ) ) ) );
	}

	public function test_another_asin_takes_amazons_image_and_keeps_a_real_name_while_keep_title_is_on(): void {
		$service = $this->service( array(), true );
		$this->amazon_has( 'B07WPQR123' );

		$saved = self::replace_link( $service, $this->repo->save( $this->stored( 'My own words' )->with( array( 'image_url' => 'https://example.com/old.jpg' ) ) ) );

		self::assertSame( 'My own words', $saved->name );
		self::assertSame( 'https://m.media-amazon.com/images/I/71stubLarge.jpg', $saved->image_url );
		self::assertSame( 'https://m.media-amazon.com/images/I/71stubLarge.jpg', $this->repo->get( (int) $saved->id )?->image_url, 'saved' );
		self::assertNotNull( $this->rows->find( 'B07WPQR123', 'us' ), 'the new item is stored, as Lasso updates its row' );
	}

	public function test_with_keep_title_off_or_a_placeholder_name_amazons_title_is_taken(): void {
		$service = $this->service( array( 'keep_title_when_url_replaced' => false ), true );
		$this->amazon_has( 'B07WPQR123' );
		self::assertSame( 'Mikasa V200W Official Game Volleyball', self::replace_link( $service, $this->stored( 'My own words' ) )->name );

		$service = $this->service( array(), true );
		$this->amazon_has( 'B07WPQR123' );
		self::assertSame( 'Mikasa V200W Official Game Volleyball', self::replace_link( $service, $this->stored( 'Amazon' ) )->name );
	}

	public function test_stored_data_for_the_new_asin_is_used_without_asking_amazon(): void {
		$service = $this->service( array( 'keep_title_when_url_replaced' => false ), true );
		$this->rows->save( new AmazonProduct( 'B07WPQR123', 'us', 'Stored name', '', 'USD', 'b', 'm', 'https://m.media-amazon.com/stored.jpg', false, '', 0, '', array(), 'lasso', '2026-11-09 00:11:00' ) );

		$saved = self::replace_link( $service, $this->stored( 'My own words' ) );

		self::assertSame( array( 'Stored name', 'https://m.media-amazon.com/stored.jpg', 0 ), array( $saved->name, $saved->image_url, $this->amazon->asked ) );
	}

	public function test_without_amazon_data_only_the_title_rule_applies(): void {
		$service = $this->service( array( 'keep_title_when_url_replaced' => false ) );

		$saved = self::replace_link( $service, $this->repo->save( $this->stored( 'My own words' )->with( array( 'image_url' => 'https://example.com/old.jpg' ) ) ) );

		self::assertSame( array( 'Tachikara Ball', '' ), array( $saved->name, $saved->image_url ) );
	}

	public function test_a_media_library_image_still_wins(): void {
		$service = $this->service( array(), true );
		$this->amazon_has( 'B07WPQR123' );

		$saved = self::replace_link(
			$service,
			$this->repo->save(
				$this->stored( 'Amazon' )->with(
					array(
						'image_id'  => 42,
						'image_url' => 'https://example.com/media.jpg',
					)
				)
			)
		);

		self::assertSame( array( 42, 'https://example.com/media.jpg' ), array( $saved->image_id, $saved->image_url ) ); // S6
	}

	/**
	 * F5 R100-R102 (Task 14E): the preview applies the form's changes as Save does, keeps the stored link and saves
	 * nothing; a refused value leaves the stored product.
	 */
	public function test_the_preview_applies_the_form_without_saving(): void {
		\Brain\Monkey\Functions\when( 'wp_kses' )->returnArg( 1 );
		$service = $this->service();
		$stored  = $this->stored( 'Ligra' );

		$preview = $service->preview( (int) $stored->id, array( 'badge' => 'Typed Badge', 'theme' => 'Money', 'url' => 'https://www.example.com/other' ) );
		self::assertSame( 'Typed Badge', $preview->badge );
		self::assertSame( 'Money', $preview->theme?->name() );
		self::assertSame( $stored->url->value(), $preview->url->value() );
		self::assertSame( '', $this->repo->get( (int) $stored->id )->badge );

		self::assertSame( 'Ligra', $service->preview( (int) $stored->id, array( 'name' => '' ) )->name );
	}
}
