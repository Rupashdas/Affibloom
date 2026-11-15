<?php
/**
 * An ASIN as the Creators API is asked for it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * An ASIN as the Creators API is asked for it (C1 R9): trimmed and upper-cased, as Lasso's fetch_product() does. Lasso
 * only refuses an empty one; here it must also be letters and digits that fit the cache's key (D5: varchar 20), so
 * nothing else reaches Amazon's request body.
 */
final class Asin {

	/**
	 * Use from().
	 *
	 * @param string $value The ASIN.
	 */
	private function __construct( public readonly string $value ) {}

	/**
	 * The ASIN, or null for anything that is not one.
	 *
	 * @param string $input What was given.
	 */
	public static function from( string $input ): ?self {
		$value = strtoupper( trim( $input ) );

		return 1 === preg_match( '/^[A-Z0-9]{1,20}$/', $value ) ? new self( $value ) : null;
	}
}
