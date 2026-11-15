<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Amazon\AmazonDataService;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\Amazon\AmazonProduct;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Fakes\FakeAmazonCatalog;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemoryAmazonProducts;
use Affibloom\Tests\Unit\Fakes\InMemoryCredentials;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Amazon's data through ProductService (C1C): a product named exactly "Amazon" takes its stored name when read (R34).
 */
final class ProductServiceAmazonTest extends TestCase {
	private InMemoryProductRepository $repo;
	private InMemoryAmazonProducts $rows;

	private function service(): ProductService {
		$this->repo = new InMemoryProductRepository();
		$this->rows = new InMemoryAmazonProducts();
		$resolver   = new LinkResolver( new FakePageFetcher() );
		$settings   = new InMemorySettingsRepository();
		$links      = new LinkNormaliser( $resolver, $settings );
		return new ProductService( $this->repo, $resolver, $settings, new ProductInput( $links ), $links, new AmazonDataService( new InMemoryCredentials(), new FakeAmazonCatalog(), $this->rows ) );
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
}
