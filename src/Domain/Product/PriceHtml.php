<?php
/**
 * A price's markup made ready to store, as Lasso stores it (F1 requirements 9, S3).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * Lasso's `sanitize_price_html_for_storage()` before its `wp_kses()`: `<script>` and `<style>` blocks are dropped with
 * their content, and `color: rgb(…)` / `color: rgba(…)` become `color: #rrggbb` (an rgba colour blended on white), so
 * every stored price colour is a hex colour (`normalize_price_html_colors_for_storage()`). The allowed tags are applied
 * by the caller, which can call WordPress.
 */
final class PriceHtml {

	private const BLOCKS = '@<(script|style)[^>]*?>.*?</\\1>@si';

	private const RGBA = '/\bcolor\s*:\s*rgba\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*([0-9.]+)\s*\)/i';

	private const RGB = '/\bcolor\s*:\s*rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/i';

	/**
	 * The markup with Lasso's two rules applied, rgba first so an rgba colour is not half read as rgb.
	 *
	 * @param string $html The price as the editor sent it.
	 */
	public static function normalised( string $html ): string {
		$html = (string) preg_replace( self::BLOCKS, '', $html );
		if ( '' === $html ) {
			return $html;
		}

		$html = (string) preg_replace_callback(
			self::RGBA,
			static function ( array $m ): string {
				$alpha = max( 0.0, min( 1.0, (float) $m[4] ) );
				$blend = static fn( string $channel ): int => (int) round( $alpha * self::channel( $channel ) + ( 1 - $alpha ) * 255 );

				return sprintf( 'color: #%02x%02x%02x', $blend( $m[1] ), $blend( $m[2] ), $blend( $m[3] ) );
			},
			$html
		);

		return (string) preg_replace_callback(
			self::RGB,
			static fn( array $m ): string => sprintf( 'color: #%02x%02x%02x', self::channel( $m[1] ), self::channel( $m[2] ), self::channel( $m[3] ) ),
			$html
		);
	}

	/**
	 * One colour channel, kept within 0-255.
	 *
	 * @param string $value Digits as written.
	 */
	private static function channel( string $value ): int {
		return max( 0, min( 255, (int) $value ) );
	}
}
