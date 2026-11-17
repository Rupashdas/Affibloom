<?php
/**
 * Lasso's checks before a product's variants are stored.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Variant;

use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\Product;

/**
 * F27 R15 (Lasso's class-url-variants.php 231-284), row by row, the first refusal winning: an empty key, a key twice in
 * the list; for permalinks also the product's own slug, another product's variant, a post or page, another product's
 * slug or old slug (D7). Amazon tags are unique per product only. The refusal names the row's key field,
 * `variants.<i>.key`, under which the tab shows it.
 */
final class VariantRules {

	/**
	 * Refuses the list at its first problem.
	 *
	 * @param list<array{name: string, key: string}> $rows    The list as it will be stored, keys normalised.
	 * @param Product                                $product The product.
	 * @param VariantKind                            $kind    The product's kind.
	 * @param SlugTaken                              $taken   Who else answers at a slug.
	 * @throws InvalidField At the first row Lasso refuses, with the reason code.
	 */
	public static function check( array $rows, Product $product, VariantKind $kind, SlugTaken $taken ): void {
		$seen       = array();
		$product_id = (int) $product->id;
		foreach ( $rows as $index => $row ) {
			$key    = $row['key'];
			$field  = 'variants.' . $index . '.key';
			$reason = match ( true ) {
				'' === $key                     => 'variant_key_empty',
				isset( $seen[ $key ] )          => 'variant_key_twice',
				VariantKind::AmazonTag === $kind => null,
				$key === $product->slug         => 'variant_key_own_slug',
				$taken->by_other_variant( $key, $product_id ) => 'variant_key_other_variant',
				$taken->by_post( $key )         => 'variant_key_post',
				$taken->by_other_product( $key, $product_id ) => 'variant_key_product',
				default                         => null,
			};
			if ( null !== $reason ) {
				throw new InvalidField( $field, $reason );
			}
			$seen[ $key ] = true;
		}
	}
}
