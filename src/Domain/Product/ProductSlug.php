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
		return (string) preg_replace( '/(-\d+)(-\d+)+$/', '', self::form( $typed ) );
	}

	/**
	 * A new product's slug (F24 R3, D11): its name's, with `amzn-` in front for an Amazon product, cut to the column's
	 * 200 characters; WordPress makes it unique among products when it is stored.
	 *
	 * @param string $name   The new product's name.
	 * @param bool   $amazon Whether it is an Amazon product.
	 */
	public static function for_new( string $name, bool $amazon ): string {
		return substr( ( $amazon ? 'amzn-' : '' ) . self::form( $name ), 0, 200 );
	}

	/**
	 * Lasso's slug form: anything that is not a letter or digit one hyphen, none at the ends, lower case.
	 *
	 * @param string $text A name or what the Permalink field holds.
	 */
	private static function form( string $text ): string {
		return strtolower( trim( (string) preg_replace( '/[\W_]+/u', '-', trim( $text ) ), '-' ) );
	}
}
