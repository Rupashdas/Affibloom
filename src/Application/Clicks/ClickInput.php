<?php
/**
 * A click as the script sends it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Clicks;

use Affibloom\Domain\Clicks\Button;

/**
 * The beacon's body (F12 D3; Task 33A): JSON `{ p, h, l, b }` (product, host post, location, button), at most 1 KB.
 * Anything else is no click (edge case 2).
 */
final class ClickInput {

	/** The largest body read (edge case 2). */
	public const MAX_BYTES = 1024;

	/**
	 * Describes the click.
	 *
	 * @param int    $product_id The product.
	 * @param int    $post_id    The post; 0 when none.
	 * @param int    $location   The link index row; 0 when none.
	 * @param Button $button     What was clicked.
	 */
	public function __construct(
		public readonly int $product_id,
		public readonly int $post_id,
		public readonly int $location,
		public readonly Button $button,
	) {}

	/**
	 * The click a body describes, or null.
	 *
	 * @param string $body The request's raw body.
	 */
	public static function from_body( string $body ): ?self {
		if ( '' === $body || strlen( $body ) > self::MAX_BYTES ) {
			return null;
		}
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			return null;
		}
		$number = static fn( string $key ): int => isset( $data[ $key ] ) && ( is_int( $data[ $key ] ) || ( is_string( $data[ $key ] ) && ctype_digit( $data[ $key ] ) ) ) ? (int) $data[ $key ] : -1;
		$button = Button::from_input( $data['b'] ?? null );
		$parts  = array( $number( 'p' ), max( 0, $number( 'h' ) ), max( 0, $number( 'l' ) ) );
		if ( $parts[0] <= 0 || null === $button ) {
			return null;
		}

		return new self( $parts[0], $parts[1], $parts[2], $button );
	}
}
