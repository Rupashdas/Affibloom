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

	private int $next_id = 100;

	/** Thrown by the save of the Nth new product (1-based; 0 = never): a failure the real repository can have. */
	public ?\Throwable $failure = null;
	public int $fail_at         = 0;
	private int $new_saves      = 0;

	public function save( Product $product ): Product {
		if ( null === $product->id && null !== $this->failure && $this->fail_at === ++$this->new_saves ) {
			throw $this->failure;
		}
		if ( null !== $product->id && null !== $this->get( $product->id ) ) {
			// An update, as wp_update_post(): the same product, now the most recently changed (the end of the list).
			$this->saved   = array_values( array_filter( $this->saved, static fn( Product $saved ): bool => $saved->id !== $product->id ) );
			$this->saved[] = $product;
			return $product;
		}
		// IDs never repeat, as a post's: one that was trashed or deleted is not handed out again.
		$stored        = $product->with( array( 'id' => $this->next_id++ ) );
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

	public function find_by_plain_key( string $key ): ?Product {
		foreach ( $this->saved as $product ) {
			if ( \Affibloom\Domain\Product\PlainUrlKey::of( $product->url ) === $key ) {
				return $product;
			}
		}
		return null;
	}

	/** @var array<string, bool> The last value set for each link switch, to check what was asked. */
	public array $flags_set = array();

	public function set_flag_for_all( string $field, bool $value ): int {
		if ( ! in_array( $field, array( 'new_tab', 'nofollow', 'sponsored', 'show_disclosure' ), true ) ) {
			throw new \InvalidArgumentException( 'Not a link switch.' );
		}
		$this->flags_set[ $field ] = $value;
		foreach ( $this->saved as $id => $product ) {
			$this->saved[ $id ] = $product->with( array( $field => $value ) );
		}
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

	/** @var array<int, int> Retired product => the product it points to. */
	public array $retired = array();

	/** A retired product leaves the published list, as its status takes it out of every published query. */
	public function retire( Product $product, int $to ): void {
		$this->retired[ (int) $product->id ] = $to;
		$this->gone[ (int) $product->id ]    = $product;
		$this->saved                         = array_values( array_filter( $this->saved, static fn( Product $saved ): bool => $saved->id !== $product->id ) );
	}

	/** @var array<int, Product> Trashed and retired products, by ID: what find_deleted() can find and revive() brings back. */
	public array $gone = array();

	/** @param list<string> $url_keys */
	public function find_deleted( array $url_keys, string $asin, string $country, string $search_key ): ?Product {
		$newest = $this->gone;
		krsort( $newest );
		foreach ( array( 'url', 'amazon', 'search' ) as $arm ) {
			foreach ( $newest as $product ) {
				$match = match ( $arm ) {
					'url'    => in_array( $product->url->key(), $url_keys, true ),
					'amazon' => '' !== $asin && ProductType::Amazon === $product->type && 0 === strcasecmp( $product->asin, $asin ) && $product->amazon_country === $country,
					'search' => '' !== $search_key && 0 === strcasecmp( $product->search_key, $search_key ),
				};
				if ( $match ) {
					return $product;
				}
			}
		}
		return null;
	}

	/** Published again, the pointer gone, and the most recently changed (the end of the list). */
	public function revive( Product $product ): Product {
		$id = (int) $product->id;
		unset( $this->gone[ $id ], $this->retired[ $id ] );
		$this->saved[] = $product;
		return $product;
	}

	/** @var list<int> Deleted product IDs, in order. */
	public array $deleted = array();

	/** When false, delete() refuses, as wp_delete_post() can. */
	public bool $deletes = true;

	/** A deleted product is gone, as wp_delete_post( id, true ) leaves nothing. */
	public function delete( int $id ): bool {
		if ( ! $this->deletes ) {
			return false;
		}
		$this->deleted[] = $id;
		$this->saved     = array_values( array_filter( $this->saved, static fn( Product $saved ): bool => $saved->id !== $id ) );
		return true;
	}

	/** @var list<int> Trashed product IDs, in order. */
	public array $trashed = array();

	/** A trashed product leaves the published list, as the trash takes it out of every query; refused as delete() is. */
	public function trash( int $id ): bool {
		$product = $this->get( $id );
		if ( ! $this->deletes || null === $product ) {
			return false;
		}
		$this->trashed[]   = $id;
		$this->gone[ $id ] = $product;
		$this->saved       = array_values( array_filter( $this->saved, static fn( Product $saved ): bool => $saved->id !== $id ) );
		return true;
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
