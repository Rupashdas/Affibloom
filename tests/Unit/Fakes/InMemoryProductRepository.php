<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;

/**
 * Products kept in memory, numbered from 100, found by their link's key or their ASIN and store. Shared by the product and bulk add tests.
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

	/** Every saved product counts as published; the ASIN is compared without case, as MySQL's collation does. */
	public function find_amazon( string $asin, string $country ): ?Product {
		foreach ( $this->saved as $product ) {
			if ( ProductType::Amazon === $product->type && 0 === strcasecmp( $product->asin, $asin ) && $product->amazon_country === $country ) {
				return $product;
			}
		}
		return null;
	}
}
