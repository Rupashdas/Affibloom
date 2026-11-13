<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Domain\Fields\ProductFields;
use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductPage;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Product\SearchTerm;

/**
 * Products kept in memory, numbered from 100, found by their link's key, their ASIN and store, or
 * their search key. Shared by the product and bulk add tests.
 */
final class InMemoryProductRepository implements ProductRepository {
	/** @var list<Product> */
	public array $saved = array();

	public function save( Product $product ): Product {
		$stored        = $product->with( array( 'id' => 100 + count( $this->saved ) ) );
		$this->saved[] = $stored;
		return $stored;
	}

	/** Newest saved first, which is this fake's stand-in for "most recently changed". */
	public function page( SearchTerm $term, LinkKind $link_kind, int $page, int $per_page ): ProductPage {
		$all = array_values(
			array_filter(
				array_reverse( $this->saved ),
				static fn( Product $product ): bool => LinkKind::All === $link_kind
					|| ( LinkKind::Amazon === $link_kind ) === ( ProductType::Amazon === $product->type )
			)
		);
		if ( ! $term->is_empty() ) {
			$all = array_values(
				array_filter(
					$all,
					static fn( Product $product ): bool => false !== stripos( $product->name, $term->text() )
				)
			);
		}

		return new ProductPage(
			array_values( array_slice( $all, ( max( 1, $page ) - 1 ) * $per_page, $per_page ) ),
			count( $all ),
			max( 1, $page ),
			$per_page
		);
	}

	public function get( int $id ): ?Product {
		foreach ( $this->saved as $product ) {
			if ( $product->id === $id ) {
				return $product;
			}
		}
		return null;
	}

	/** @var array<int, ProductFields> */
	public array $fields = array();

	public function fields( int $id ): ?ProductFields {
		return null === $this->get( $id ) ? null : ( $this->fields[ $id ] ?? ProductFields::from_array( array() ) );
	}

	public function save_fields( int $id, ProductFields $fields ): void {
		$this->fields[ $id ] = $fields;
	}

	public function count(): int {
		return count( $this->saved );
	}

	public function find_by_url_key( string $key ): ?Product {
		foreach ( $this->saved as $product ) {
			if ( $product->url->key() === $key ) {
				return $product;
			}
		}
		return null;
	}

	/** Compared without case, as MySQL's collation does. */
	public function find_by_search_key( string $key ): ?Product {
		foreach ( $this->saved as $product ) {
			if ( '' !== $product->search_key && 0 === strcasecmp( $product->search_key, $key ) ) {
				return $product;
			}
		}
		return null;
	}

	/** Re-saving makes a product the most recently changed, as wp_update_post() does: it moves to the end of the list. */
	public function rename( Product $product, string $name ): Product {
		foreach ( $this->saved as $index => $saved ) {
			if ( $saved->id === $product->id ) {
				$renamed = $saved->with( array( 'name' => $name ) );
				unset( $this->saved[ $index ] );
				$this->saved   = array_values( $this->saved );
				$this->saved[] = $renamed;
				return $renamed;
			}
		}
		return $product;
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
