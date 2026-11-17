<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Domain\Variant\Variant;
use Affibloom\Domain\Variant\VariantKind;
use Affibloom\Domain\Variant\VariantRepository;

/**
 * URL variants in memory, with each permalink's parent slug given by the test.
 */
final class InMemoryVariantRepository implements VariantRepository {

	/** @var list<Variant> */
	public array $saved = array();

	/**
	 * @param array<string, string> $parent_slugs A permalink key => its product's slug.
	 */
	public function __construct( private array $parent_slugs = array() ) {}

	public function for_parent( int $product_id ): array {
		return array_values( array_filter( $this->saved, static fn( Variant $variant ): bool => $product_id === $variant->product_id ) );
	}

	public function replace( int $product_id, array $variants ): array {
		$this->saved = array_merge( array_values( array_filter( $this->saved, static fn( Variant $variant ): bool => $product_id !== $variant->product_id ) ), $variants );
		return $this->for_parent( $product_id );
	}

	public function parent_slug_for_permalink( string $key ): ?string {
		return $this->parent_slugs[ $key ] ?? null;
	}

	public function amazon_tag_exists( string $tag ): bool {
		foreach ( $this->saved as $variant ) {
			if ( VariantKind::AmazonTag === $variant->kind && $tag === $variant->key ) {
				return true;
			}
		}
		return false;
	}
}
