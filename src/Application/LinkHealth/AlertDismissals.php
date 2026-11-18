<?php
/**
 * Until when a product page's issue bar stays closed.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkHealth;

/**
 * Until when a product page's issue bar stays closed (F11 R29, D17; Lasso's dismiss_broken and dismiss_out_of_stock
 * post meta): per product and per bar, a Unix time.
 */
interface AlertDismissals {

	/** The broken-link bar. */
	public const BROKEN = 'broken';

	/** The out-of-stock bar. */
	public const OUT_OF_STOCK = 'out-of-stock';

	/**
	 * Until when the bar stays closed; 0 when it was never closed.
	 *
	 * @param int    $product_id The product.
	 * @param string $bar        self::BROKEN or self::OUT_OF_STOCK.
	 */
	public function until( int $product_id, string $bar ): int;

	/**
	 * Closes the bar until the given time.
	 *
	 * @param int    $product_id The product.
	 * @param string $bar        self::BROKEN or self::OUT_OF_STOCK.
	 * @param int    $until      Unix time.
	 */
	public function close( int $product_id, string $bar, int $until ): void;
}
