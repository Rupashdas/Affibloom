<?php
/**
 * The product page's closed issue bars, in post meta.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\LinkHealth\AlertDismissals;

/**
 * Post meta `_affibloom_dismiss_broken` and `_affibloom_dismiss_out_of_stock` (F11 analysis, "Data"; Lasso's
 * dismiss_broken and dismiss_out_of_stock): the Unix time until which the bar stays closed.
 */
final class PostMetaAlertDismissals implements AlertDismissals {

	/**
	 * Until when the bar stays closed; 0 when it was never closed.
	 *
	 * @param int    $product_id The product.
	 * @param string $bar        self::BROKEN or self::OUT_OF_STOCK.
	 */
	public function until( int $product_id, string $bar ): int {
		return (int) get_post_meta( $product_id, self::key( $bar ), true );
	}

	/**
	 * Closes the bar until the given time.
	 *
	 * @param int    $product_id The product.
	 * @param string $bar        self::BROKEN or self::OUT_OF_STOCK.
	 * @param int    $until      Unix time.
	 */
	public function close( int $product_id, string $bar, int $until ): void {
		update_post_meta( $product_id, self::key( $bar ), $until );
	}

	/**
	 * The meta key of a bar.
	 *
	 * @param string $bar self::BROKEN or self::OUT_OF_STOCK.
	 */
	private static function key( string $bar ): string {
		return self::OUT_OF_STOCK === $bar ? '_affibloom_dismiss_out_of_stock' : '_affibloom_dismiss_broken';
	}
}
