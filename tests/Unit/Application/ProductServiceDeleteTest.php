<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductNotFound;
use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;

/**
 * "Delete This Link" on the product page (Task 17A; F1 requirements 11, X3, D33): a published product is deleted for
 * good, as Lasso's wp_delete_post( id, true ); anything else is not found and nothing is deleted.
 */
final class ProductServiceDeleteTest extends TestCase {
	private InMemoryProductRepository $repo;

	protected function setUp(): void {
		parent::setUp();
		$this->repo = new InMemoryProductRepository();
	}

	private function service(): ProductService {
		$settings = new InMemorySettingsRepository( array() );
		$resolver = new LinkResolver( new FakePageFetcher() );
		$links    = new LinkNormaliser( $resolver, $settings );

		return new ProductService( $this->repo, $resolver, $settings, new ProductInput( $links ), $links );
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
}
