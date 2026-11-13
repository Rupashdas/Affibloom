<?php
/**
 * Where a link leads and what to call it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

/**
 * Where a link leads and what to call it.
 */
final class ResolvedLink {

	/**
	 * Builds the result.
	 *
	 * @param string $url   The final destination (the link itself when it could not be followed).
	 * @param string $title The name for a new product.
	 */
	public function __construct(
		public readonly string $url,
		public readonly string $title,
	) {}
}
