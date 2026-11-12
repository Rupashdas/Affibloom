<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use PHPUnit\Framework\TestCase;

final class ProductServiceTest extends TestCase {
	/**
	 * A repository that keeps products in memory, numbers them from 100 and finds them by key.
	 */
	private function repository(): ProductRepository {
		return new class() implements ProductRepository {
			/** @var list<Product> */
			public array $saved = array();
			public function save( Product $product ): Product {
				$stored        = $product->with( array( 'id' => 100 + count( $this->saved ) ) );
				$this->saved[] = $stored;
				return $stored;
			}
			public function find_by_url_key( string $key ): ?Product {
				foreach ( $this->saved as $product ) {
					if ( $product->url->key() === $key ) {
						return $product;
					}
				}
				return null;
			}
		};
	}

	public function test_a_link_becomes_one_saved_product_named_after_its_site(): void {
		$repo = $this->repository();
		$link = 'https://mizuno.pxf.io/c/123/456/789?u=https%3A%2F%2Fmizunousa.com%2Fwave';

		$added = ( new ProductService( $repo ) )->create_from_url( " $link " );

		self::assertFalse( $added->already_existed );
		self::assertSame( 100, $added->product->id );
		self::assertSame( 'Pxf', $added->product->name );
		self::assertSame( $link, $added->product->url->value() );
		self::assertSame( ProductType::Other, $added->product->type );
		self::assertCount( 1, $repo->saved );
	}

	public function test_a_link_it_already_has_returns_that_product_and_saves_nothing(): void {
		$repo    = $this->repository();
		$service = new ProductService( $repo );
		$service->create_from_url( 'https://www.example.com/a' );

		$added = $service->create_from_url( 'WWW.EXAMPLE.COM/A/' );

		self::assertTrue( $added->already_existed );
		self::assertSame( 100, $added->product->id );
		self::assertCount( 1, $repo->saved );
	}

	public function test_a_refused_link_saves_nothing(): void {
		$repo = $this->repository();

		try {
			( new ProductService( $repo ) )->create_from_url( 'not a link' );
			self::fail( 'Expected InvalidField.' );
		} catch ( InvalidField $error ) {
			self::assertSame( 'invalid_url', $error->reason );
		}
		self::assertCount( 0, $repo->saved );
	}
}
