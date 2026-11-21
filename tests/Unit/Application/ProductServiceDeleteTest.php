<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Tests\Unit\Fakes\InMemoryAmazonProducts;

use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductInUse;
use Affibloom\Application\Products\ProductNotFound;
use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Tests\Unit\Fakes\InMemoryLinkLocations;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;

/**
 * "Delete This Link" on the product page (Task 17A; F1 requirements 11, X3, D33): a published product is deleted for
 * good, as Lasso's wp_delete_post( id, true ); anything else is not found and nothing is deleted.
 */
final class ProductServiceDeleteTest extends TestCase {
	private InMemoryProductRepository $repo;

	private InMemoryLinkLocations $locations;

	protected function setUp(): void {
		parent::setUp();
		$this->repo      = new InMemoryProductRepository();
		$this->locations = new InMemoryLinkLocations();
	}

	private function service(): ProductService {
		$settings = new InMemorySettingsRepository( array() );
		$resolver = new LinkResolver( new FakePageFetcher() );
		$links    = new LinkNormaliser( $resolver, $settings );

		return new ProductService( $this->repo, $resolver, $settings, new ProductInput( $links ), $links, InMemoryAmazonProducts::without_amazon(), $this->locations );
	}

	private function product( string $name ): Product {
		return $this->repo->save( Product::draft( $name, AffiliateUrl::from_string( 'https://www.example.com/' . $name ), Settings::from_array( array() ) ) );
	}

	public function test_a_product_is_deleted_and_the_others_stay(): void {
		$gone = $this->product( 'gone' );
		$kept = $this->product( 'kept' );

		$this->service()->delete( (int) $gone->id );

		self::assertSame( array( (int) $gone->id ), $this->repo->deleted );
		self::assertNull( $this->repo->get( (int) $gone->id ) );
		self::assertNotNull( $this->repo->get( (int) $kept->id ) );
	}

	public function test_an_unknown_id_is_not_found_and_nothing_is_deleted(): void {
		$this->product( 'kept' );

		try {
			$this->service()->delete( 999 );
			self::fail( 'An unknown ID must be refused.' );
		} catch ( ProductNotFound $missing ) {
			self::assertSame( array(), $this->repo->deleted );
		}
	}

	public function test_a_refused_delete_is_reported_and_the_product_stays(): void {
		$kept                = $this->product( 'kept' );
		$this->repo->deletes = false;

		$this->expectException( \RuntimeException::class );
		try {
			$this->service()->delete( (int) $kept->id );
		} finally {
			self::assertNotNull( $this->repo->get( (int) $kept->id ) );
		}
	}

	public function test_no_id_is_not_found(): void {
		$this->expectException( ProductNotFound::class );
		$this->service()->delete( 0 );
	}

	/** The Dashboard's bulk delete (Task 17B; X7, D39): the published products among the IDs go to the trash, once each. */
	public function test_the_ticked_products_go_to_the_trash_and_others_are_skipped(): void {
		$one   = $this->product( 'one' );
		$two   = $this->product( 'two' );
		$kept  = $this->product( 'kept' );
		$taken = $this->service()->trash( array( (int) $one->id, 999, (int) $two->id, (int) $one->id ) );

		self::assertSame( array( (int) $one->id, (int) $two->id ), $taken );
		self::assertSame( array( (int) $one->id, (int) $two->id ), $this->repo->trashed );
		self::assertNull( $this->repo->get( (int) $one->id ) );
		self::assertNotNull( $this->repo->get( (int) $kept->id ) );
	}

	public function test_a_product_already_in_the_trash_is_skipped(): void {
		$one = $this->product( 'one' );
		$this->service()->trash( array( (int) $one->id ) );

		self::assertSame( array(), $this->service()->trash( array( (int) $one->id ) ) );
		self::assertSame( array( (int) $one->id ), $this->repo->trashed );
	}

	public function test_a_product_the_storage_refuses_is_not_reported_as_trashed(): void {
		$one                 = $this->product( 'one' );
		$this->repo->deletes = false;

		self::assertSame( array(), $this->service()->trash( array( (int) $one->id ) ) );
		self::assertNotNull( $this->repo->get( (int) $one->id ) );
	}

	/** Task 36E (F12 L14-L16, D26; F1 D34, D35): a product a post still uses is never deleted, alone or in a batch. */
	public function test_a_product_in_use_is_refused_and_kept(): void {
		$used = $this->product( 'used' );
		$this->use_in_a_post( (int) $used->id );

		try {
			$this->service()->delete( (int) $used->id );
			self::fail( 'A product in use must be refused.' );
		} catch ( ProductInUse $refused ) {
			self::assertSame( array( (int) $used->id ), $refused->ids );
		}
		self::assertSame( array(), $this->repo->deleted );
		self::assertNotNull( $this->repo->get( (int) $used->id ) );
	}

	public function test_a_batch_with_any_product_in_use_trashes_nothing(): void {
		$free = $this->product( 'free' );
		$used = $this->product( 'used' );
		$this->use_in_a_post( (int) $used->id );

		try {
			$this->service()->trash( array( (int) $free->id, (int) $used->id ) );
			self::fail( 'A batch with a product in use must be refused.' );
		} catch ( ProductInUse $refused ) {
			self::assertSame( array( (int) $used->id ), $refused->ids );
		}
		self::assertNotNull( $this->repo->get( (int) $free->id ) );
		self::assertSame( array( (int) $free->id ), $this->service()->trash( array( (int) $free->id ) ) );
	}

	private function use_in_a_post( int $product_id ): void {
		$this->locations->replace_host(
			50,
			array(
				new LinkLocation(
					host_id: 50,
					host_url: 'https://example.test/?p=50',
					source: 'content',
					occurrence: 1,
					product_id: $product_id,
					link_type: 'product',
					display_type: 'Single',
					url: '[affibloom id="' . $product_id . '"]',
					url_md5: md5( (string) $product_id ),
					detected_at: '2026-11-21 10:00:00',
				),
			)
		);
	}
}
