<?php
/**
 * A product's slug as an editor types it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * Lasso's Permalink rules (F24 R4, R8, D11, D12): anything that is not a letter or digit becomes one hyphen, hyphens
 * at the ends go, the case is lowered as WordPress stores post names, and a trailing repeat such as `-2-2-2` is cut back
 * to its base (the uniqueness suffix is WordPress's to add again). An empty result asks for the name's slug.
 */
final class ProductSlug {

	/**
	 * The slug to store, or '' for "the name's slug".
	 *
	 * @param string $typed What the Permalink field holds.
	 */
	public static function clean( string $typed ): string {
		$slug = strtolower( trim( (string) preg_replace( '/[\W_]+/u', '-', trim( $typed ) ), '-' ) );

		return (string) preg_replace( '/(-\d+)(-\d+)+$/', '', $slug );
	}
}
