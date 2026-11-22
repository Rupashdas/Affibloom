<?php
/**
 * What the extension API may do with a product's URL variants.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Extension;

use Affibloom\Application\Variants\VariantService;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Variant\Variant;
use Affibloom\Domain\Variant\VariantKind;
use InvalidArgumentException;
use RuntimeException;

/**
 * What the extension API may do with a product's URL variants (Task 26H): read them and set the whole list, through
 * the Variants tab's rules (names through wp_kses_post, keys normalised for the product's kind, Lasso's checks).
 */
final class VariantAccess {

	/**
	 * Builds the access.
	 *
	 * @param VariantService    $variants The Variants tab's rules.
	 * @param ProductRepository $products The products.
	 */
	public function __construct( private VariantService $variants, private ProductRepository $products ) {}

	/**
	 * A published product's variants, `{ name, key, kind }` in their stored order; null when there is no such product.
	 *
	 * @param int $product The product.
	 * @return list<array{name: string, key: string, kind: string}>|null
	 */
	public function all( int $product ): ?array {
		$found = $this->products->get( $product );

		return null === $found ? null : array_map(
			static fn ( Variant $variant ): array => array(
				'name' => $variant->name,
				'key'  => $variant->key,
				'kind' => $variant->kind->value,
			),
			$this->variants->list( $found )
		);
	}

	/**
	 * Makes a product's variants this list; every row takes the product's kind.
	 *
	 * @param int                                      $product The product.
	 * @param list<array{name?: string, key?: string}> $rows    The rows.
	 * @throws InvalidArgumentException When there is no such product, a row is refused ("2.key: taken_by_post") or the
	 *                                  list cannot be stored.
	 */
	public function set( int $product, array $rows ): void {
		$found = $this->products->get( $product );
		if ( null === $found ) {
			throw new InvalidArgumentException( esc_html( "No product $product." ) );
		}
		$permalink = VariantKind::Permalink === VariantKind::of( $found );
		$clean     = array();
		foreach ( $rows as $row ) {
			$key     = trim( (string) ( $row['key'] ?? '' ) );
			$clean[] = array(
				'name' => trim( wp_kses_post( (string) ( $row['name'] ?? '' ) ) ),
				'key'  => $permalink ? sanitize_title( $key ) : sanitize_text_field( $key ),
			);
		}
		try {
			$this->variants->replace( $found, $clean );
		} catch ( InvalidField $refused ) {
			throw new InvalidArgumentException( esc_html( $refused->field . ': ' . $refused->reason ) );
		} catch ( RuntimeException ) {
			throw new InvalidArgumentException( 'variants: not_saved' );
		}
	}
}
