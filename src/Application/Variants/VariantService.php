<?php
/**
 * A product's URL variants.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Variants;

use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Variant\Variant;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Variant\SlugTaken;
use Affibloom\Domain\Variant\VariantKind;
use Affibloom\Domain\Variant\VariantRepository;
use Affibloom\Domain\Variant\VariantRules;
use RuntimeException;

/**
 * The Variants tab's use cases (F27): a product's variants, for the tab and its count, and the save of its whole list.
 */
final class VariantService {

	/**
	 * Builds the service.
	 *
	 * @param VariantRepository $variants The stored variants.
	 * @param SlugTaken         $taken    Who else answers at a slug, for the checks (R15).
	 */
	public function __construct( private VariantRepository $variants, private SlugTaken $taken ) {}

	/**
	 * A product's variants, in the order they were stored.
	 *
	 * @param Product $product The product.
	 * @return list<Variant>
	 */
	public function list( Product $product ): array {
		return $this->variants->for_parent( (int) $product->id );
	}

	/**
	 * Stores the product's whole list (R11, R16) after Lasso's checks (R15): every row takes the product's kind now (R23).
	 *
	 * @param Product                                $product The product.
	 * @param list<array{name: string, key: string}> $rows    The list, its keys normalised for the kind.
	 * @return list<Variant> The list as stored.
	 * @throws InvalidField      At the first row Lasso refuses.
	 * @throws RuntimeException When the list cannot be stored.
	 */
	public function replace( Product $product, array $rows ): array {
		$kind = VariantKind::of( $product );
		VariantRules::check( $rows, $product, $kind, $this->taken );
		$product_id = (int) $product->id;

		return $this->variants->replace(
			$product_id,
			array_map( static fn( array $row ): Variant => new Variant( 0, $product_id, $row['name'], $row['key'], $kind ), $rows )
		);
	}

	/**
	 * Forgets a deleted product's variants (edge case 3).
	 *
	 * @param int $product_id The product's ID.
	 */
	public function forget( int $product_id ): void {
		$this->variants->replace( $product_id, array() );
	}
}
