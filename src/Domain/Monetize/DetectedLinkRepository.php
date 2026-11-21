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

	/**
	 * Rows still without a published product (R13, D5; Task C2C): never made, or whose product was deleted or is no longer
	 * published, oldest first.
	 *
	 * @param int $limit At most this many.
	 * @return array<int, string> The link, by row ID.
	 */
	public function without_product( int $limit ): array;

	/**
	 * Records the product made for a row (C2 AC7).
	 *
	 * @param int $id         The row.
	 * @param int $product_id The product.
	 */
	public function set_product( int $id, int $product_id ): void;

	/**
	 * The product recorded for a link (F35 R15's lookup; Task C2C); 0 when none.
	 *
	 * @param string $url The link as written.
	 */
	public function product_for( string $url ): int;
}
