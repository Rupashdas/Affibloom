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
	 * @param int $parent The product's ID.
	 * @return list<Variant>
	 */
	public function for_parent( int $parent ): array;
}
