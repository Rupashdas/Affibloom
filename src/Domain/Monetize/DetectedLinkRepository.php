<?php
/**
 * Where detected affiliate links are kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Monetize;

/**
 * The detected network links (F35 R10, R11, D4; Task C2B; Lasso's `lasso_auto_monetize`): one row per URL, with what the
 * link carries and the product made for it (0 until one is).
 */
interface DetectedLinkRepository {

	/**
	 * Records a link once per URL (R10); a row already there keeps what it had, and is tied to the product when it has
	 * none yet and one is given (C2 AC2, AC7).
	 *
	 * @param string       $url        The link as written.
	 * @param DetectedLink $link       What it carries.
	 * @param int          $product_id The product it already is; 0 when none.
	 */
	public function record( string $url, DetectedLink $link, int $product_id ): void;
}
