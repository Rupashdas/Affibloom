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
 * site-wide disclosure settings (show_disclosure, _grid, _list, _table; requirements B5).
 *
 * Price follows the site-wide Show Price setting until the Elements panel is saved with it (requirements B5a;
 * Lasso's display.php fills a missing price from show_price). Until then it is not stored, so it keeps following.
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
	 * @param array<string, bool> $flags           One flag per key.
	 * @param bool                $price_inherited Whether Price still follows Show Price.
	 */
	private function __construct( private readonly array $flags, private readonly bool $price_inherited ) {}

	/**
	 * Every element shown, Lasso's default for every type (Helper::get_display_elements_defaults).
	 */
	public static function defaults(): self {
		return new self( array_fill_keys( self::KEYS, true ), true );
	}

	/**
	 * Reads stored flags. Only real booleans count; anything missing or malformed is shown, except Price, which
	 * then follows Show Price.
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

		return new self( $flags, ! ( isset( $stored['price'] ) && is_bool( $stored['price'] ) ) );
	}

	/**
	 * A copy where Price shows what Show Price says, when Price has not been saved yet.
	 *
	 * @param bool $show_price The site-wide Show Price setting.
	 */
	public function with_show_price( bool $show_price ): self {
		if ( ! $this->price_inherited ) {
			return $this;
		}

		return new self( array_merge( $this->flags, array( 'price' => $show_price ) ), true );
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

		return new self( array_merge( $this->flags, array( $key => $shown ) ), $this->price_inherited && 'price' !== $key );
	}

	/**
	 * How many elements are shown, for the panel's summary.
	 */
	public function shown(): int {
		return count( array_filter( $this->flags ) );
	}

	/**
	 * The flags, for storage. Price is left out while it follows Show Price, so it keeps following.
	 *
	 * @return array<string, bool>
	 */
	public function to_array(): array {
		return $this->price_inherited ? array_diff_key( $this->flags, array( 'price' => true ) ) : $this->flags;
	}
}
