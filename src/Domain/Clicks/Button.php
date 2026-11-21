<?php
/**
 * Which part of a product was clicked.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Clicks;

/**
 * The clicked element (F12 R7, D4; Task 33A): a box's button by its place (`1` the primary, `2` the secondary, `3` and
 * up F29's added buttons, Lasso's at most 99), its `title` or `image` link, or an in-content `link`.
 */
final class Button {

	/** The named elements. */
	public const NAMES = array( 'title', 'image', 'link' );

	/**
	 * Builds the button; use from_input().
	 *
	 * @param string $value Its stored value.
	 */
	private function __construct( public readonly string $value ) {}

	/**
	 * The button a click names, or null for anything else.
	 *
	 * @param mixed $value The click's `b`.
	 */
	public static function from_input( mixed $value ): ?self {
		if ( ! is_string( $value ) && ! is_int( $value ) ) {
			return null;
		}
		$value = (string) $value;
		if ( in_array( $value, self::NAMES, true ) || 1 === preg_match( '/^[1-9][0-9]?$/', $value ) ) {
			return new self( $value );
		}

		return null;
	}
}
