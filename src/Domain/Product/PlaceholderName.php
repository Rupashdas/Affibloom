<?php
/**
 * Lasso's placeholder product names.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * Lasso_Affiliate_Link::is_placeholder_link_title(): no name, Lasso's DEFAULT_TITLE "Add a Link Title" or its slug,
 * DEFAULT_AMAZON_NAME "Amazon", or the Hub's "Lasso". A product named so takes the new Amazon product's name when its
 * Primary URL moves to another ASIN, even with "Keep title when URL replaced" on (F1 requirements 9, S8).
 */
final class PlaceholderName {

	private const NAMES = array( 'Add a Link Title', 'Amazon', 'Lasso' );

	private const SLUG = 'add-a-link-title';

	/**
	 * Whether the name is one of Lasso's placeholders. Names compare exactly, as Lasso's `===`; the slug without case,
	 * as Lasso lower-cases the name before comparing it with sanitize_title( DEFAULT_TITLE ).
	 *
	 * @param string $name A product name.
	 */
	public static function is( string $name ): bool {
		$name = trim( $name );

		return '' === $name || in_array( $name, self::NAMES, true ) || self::SLUG === strtolower( $name );
	}
}
