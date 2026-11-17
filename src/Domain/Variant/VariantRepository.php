<?php
/**
 * Where URL variants are kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Variant;

/**
 * The products' URL variants (F27 R1; table `affibloom_url_variants`).
 */
interface VariantRepository {

	/**
	 * A product's variants, in the order they were stored.
	 *
	 * @param int $product_id The product's ID.
	 * @return list<Variant>
	 */
	public function for_parent( int $product_id ): array;

	/**
	 * Stores a product's whole list in place of the old one (R16), all or nothing.
	 *
	 * @param int           $product_id   The product's ID.
	 * @param list<Variant> $variants The list.
	 * @return list<Variant> The list as stored, with its new IDs.
	 * @throws \RuntimeException When the list cannot be stored; nothing changed.
	 */
	public function replace( int $product_id, array $variants ): array;
}
