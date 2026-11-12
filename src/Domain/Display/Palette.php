<?php
/**
 * The colours and button labels of one display type.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

use InvalidArgumentException;

/**
 * The colours and button labels of one display type.
 *
 * Values are kept as given: Lasso stores CSS keywords as well as hex colours ("black", "white"), and the
 * button labels carry the site's icon markup. Cleaning belongs to the application layer, which knows about
 * WordPress; this object only knows the shape.
 */
final class Palette {

	/**
	 * The colour keys, in the order Lasso's form shows them.
	 *
	 * @var list<string>
	 */
	public const COLOR_KEYS = array( 'main', 'title', 'background', 'button_text', 'pros', 'cons', 'button', 'secondary_button' );

	/**
	 * The text keys.
	 *
	 * @var list<string>
	 */
	public const TEXT_KEYS = array( 'primary_button_text', 'secondary_button_text', 'badge_text' );

	/**
	 * Lasso's defaults for every type (admin/lasso-constant.php 184-224).
	 *
	 * @var array<string, string>
	 */
	public const DEFAULTS = array(
		'main'                  => 'black',
		'title'                 => 'black',
		'background'            => 'white',
		'button'                => '#22BAA0',
		'secondary_button'      => '#22BAA0',
		'button_text'           => 'white',
		'pros'                  => '#22BAA0',
		'cons'                  => '#E06470',
		'primary_button_text'   => 'Buy Now',
		'secondary_button_text' => 'Our Review',
		'badge_text'            => '',
	);

	/**
	 * Use from_array() or defaults().
	 *
	 * @param array<string, string> $values Complete set of values.
	 */
	private function __construct( private readonly array $values ) {}

	/**
	 * The default palette.
	 */
	public static function defaults(): self {
		return new self( self::DEFAULTS );
	}

	/**
	 * Builds a palette from stored data. Unknown keys are dropped, missing keys take the default, and a value
	 * that is not a string is ignored, so malformed data can never fatal (requirements, edge case 4).
	 *
	 * @param array<string, mixed> $stored Stored values.
	 */
	public static function from_array( array $stored ): self {
		$values = self::DEFAULTS;
		foreach ( $values as $key => $default ) {
			if ( isset( $stored[ $key ] ) && is_string( $stored[ $key ] ) ) {
				$values[ $key ] = $stored[ $key ];
			}
		}

		return new self( $values );
	}

	/**
	 * A copy with one value changed.
	 *
	 * @param string $key   One of COLOR_KEYS or TEXT_KEYS.
	 * @param string $value The new value, already cleaned.
	 * @throws InvalidArgumentException For a key the palette does not have; always a programming error.
	 */
	public function with( string $key, string $value ): self {
		if ( ! array_key_exists( $key, self::DEFAULTS ) ) {
			throw new InvalidArgumentException( "Unknown palette key: $key" );
		}

		return new self( array_merge( $this->values, array( $key => $value ) ) );
	}

	/**
	 * One value.
	 *
	 * @param string $key One of COLOR_KEYS or TEXT_KEYS.
	 * @throws InvalidArgumentException For a key the palette does not have; always a programming error.
	 */
	public function value( string $key ): string {
		if ( ! array_key_exists( $key, $this->values ) ) {
			throw new InvalidArgumentException( "Unknown palette key: $key" );
		}

		return $this->values[ $key ];
	}

	/**
	 * Every value, for storage.
	 *
	 * @return array<string, string>
	 */
	public function to_array(): array {
		return $this->values;
	}
}
