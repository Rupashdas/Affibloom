<?php
/**
 * Which parts of a box one display type shows.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

use InvalidArgumentException;

/**
 * Which parts of a box one display type shows.
 *
 * Lasso's Elements panel has an eleventh row, Disclosure, which is not stored here: it reads and writes the
 * site-wide disclosure settings (show_disclosure, _grid, _list, _table; requirements B5), added in Task 10E.
 */
final class Elements {

	/**
	 * The ten stored elements, in the order Lasso's panel lists them.
	 *
	 * @var list<string>
	 */
	public const KEYS = array( 'badge', 'image', 'title', 'price', 'prime', 'desc', 'proscons', 'primary', 'secondary', 'timestamp' );

	/**
	 * Use defaults() or from_array().
	 *
	 * @param array<string, bool> $flags One flag per key.
	 */
	private function __construct( private readonly array $flags ) {}

	/**
	 * Every element shown, Lasso's default for every type (Helper::get_display_elements_defaults).
	 */
	public static function defaults(): self {
		return new self( array_fill_keys( self::KEYS, true ) );
	}

	/**
	 * Reads stored flags. Only real booleans count; anything missing or malformed is shown.
	 *
	 * @param array<string, mixed> $stored Stored flags.
	 */
	public static function from_array( array $stored ): self {
		$flags = array_fill_keys( self::KEYS, true );
		foreach ( self::KEYS as $key ) {
			if ( isset( $stored[ $key ] ) && is_bool( $stored[ $key ] ) ) {
				$flags[ $key ] = $stored[ $key ];
			}
		}

		return new self( $flags );
	}

	/**
	 * Whether an element is shown.
	 *
	 * @param string $key One of KEYS.
	 * @throws InvalidArgumentException For a key Elements does not have; always a programming error.
	 */
	public function visible( string $key ): bool {
		if ( ! array_key_exists( $key, $this->flags ) ) {
			throw new InvalidArgumentException( "Unknown element: $key" );
		}

		return $this->flags[ $key ];
	}

	/**
	 * A copy with one element shown or hidden.
	 *
	 * @param string $key   One of KEYS.
	 * @param bool   $shown Whether to show it.
	 * @throws InvalidArgumentException For a key Elements does not have; always a programming error.
	 */
	public function with( string $key, bool $shown ): self {
		if ( ! array_key_exists( $key, $this->flags ) ) {
			throw new InvalidArgumentException( "Unknown element: $key" );
		}

		return new self( array_merge( $this->flags, array( $key => $shown ) ) );
	}

	/**
	 * How many elements are shown, for the panel's summary.
	 */
	public function shown(): int {
		return count( array_filter( $this->flags ) );
	}

	/**
	 * The flags, for storage.
	 *
	 * @return array<string, bool>
	 */
	public function to_array(): array {
		return $this->flags;
	}
}
