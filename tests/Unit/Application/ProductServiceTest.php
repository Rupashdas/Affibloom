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
	 * A repository that keeps products in memory and numbers them from 100.
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
		};
	}

	public function test_a_link_becomes_one_saved_product_named_after_its_site(): void {
		$repo = $this->repository();
		$link = 'https://mizuno.pxf.io/c/123/456/789?u=https%3A%2F%2Fmizunousa.com%2Fwave';

		$product = ( new ProductService( $repo ) )->create_from_url( " $link " );

		self::assertSame( 100, $product->id );
		self::assertSame( 'Pxf', $product->name );
		self::assertSame( $link, $product->url->value() );
		self::assertSame( ProductType::Other, $product->type );
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
