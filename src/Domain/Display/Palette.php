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
	 * Lasso's defaults for every type (admin/lasso-constant.php 184-224), as hex.
	 *
	 * Lasso writes the first four as the CSS keywords "black" and "white"; Affibloom stores the same colours as
	 * hex, so the colour picker and the stored value always agree (the user, 2026-11-13). Keywords are still
	 * accepted when they are typed or migrated from Lasso, so no existing value is lost.
	 *
	 * @var array<string, string>
	 */
	public const DEFAULTS = array(
		'main'                  => '#000000',
		'title'                 => '#000000',
		'background'            => '#ffffff',
		'button'                => '#22baa0',
		'secondary_button'      => '#22baa0',
		'button_text'           => '#ffffff',
		'pros'                  => '#22baa0',
		'cons'                  => '#e06470',
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
	 * Whether another palette holds the same colours and labels (F5 R53): colours compared as values (a CSS colour name
	 * as its hex, three-digit hex in full, case ignored), labels as text — Lasso's normalize_display_color_for_compare.
	 *
	 * @param Palette $other The other palette.
	 */
	public function same_as( Palette $other ): bool {
		foreach ( self::COLOR_KEYS as $key ) {
			if ( self::comparable( $this->value( $key ) ) !== self::comparable( $other->value( $key ) ) ) {
				return false;
			}
		}
		foreach ( self::TEXT_KEYS as $key ) {
			if ( $this->value( $key ) !== $other->value( $key ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * A colour in the form Lasso compares: the basic CSS names as hex, `#abc` as `#aabbcc`, lower case.
	 *
	 * @param string $colour A stored colour.
	 */
	private static function comparable( string $colour ): string {
		$named  = array(
			'black'   => '#000000',
			'silver'  => '#c0c0c0',
			'gray'    => '#808080',
			'grey'    => '#808080',
			'white'   => '#ffffff',
			'maroon'  => '#800000',
			'red'     => '#ff0000',
			'purple'  => '#800080',
			'fuchsia' => '#ff00ff',
			'green'   => '#008000',
			'lime'    => '#00ff00',
			'olive'   => '#808000',
			'yellow'  => '#ffff00',
			'navy'    => '#000080',
			'blue'    => '#0000ff',
			'teal'    => '#008080',
			'aqua'    => '#00ffff',
		);
		$colour = strtolower( trim( $colour ) );
		if ( isset( $named[ $colour ] ) ) {
			return $named[ $colour ];
		}
		if ( 1 === preg_match( '/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $colour, $m ) ) {
			return '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3];
		}

		return $colour;
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
