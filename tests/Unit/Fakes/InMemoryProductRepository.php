<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;

/**
 * Products kept in memory, numbered from 100, found by their link's key. Shared by the product and bulk add tests.
 */
final class InMemoryProductRepository implements ProductRepository {
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
}
