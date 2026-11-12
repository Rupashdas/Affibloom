<?php
/**
 * How one display type arranges a box, on desktop and, where the editor chose, on phones.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

use InvalidArgumentException;

/**
 * How one display type arranges a box.
 *
 * Lasso's rules, kept exactly (Helper::get_display_layout_defaults, normalize_display_layout_bucket,
 * canonicalize_display_layout, coerce_display_layout_image_position, get_display_layout_mobile_base,
 * normalize_display_layout_mobile_overrides, get_display_layout_mobile_for_type):
 * - image position belongs to the orientation: left/right side by side, top/bottom stacked;
 * - a grid card is always stacked, and grid has no phone orientation;
 * - phones start from desktop with the buttons centred, and the editor's phone overrides on top;
 * - an image-position override is kept in the vocabulary of the phone orientation.
 * One deliberate tightening: every desktop key is validated, where Lasso's server checks only three of them.
 */
final class Layout {

	/**
	 * The options of each key, in the order the rail shows them.
	 *
	 * @var array<string, list<string>>
	 */
	public const OPTIONS = array(
		'orientation'    => array( 'stacked', 'side-by-side' ),
		'imagePosition'  => array( 'left', 'right', 'top', 'bottom' ),
		'align'          => array( 'left', 'center', 'right' ),
		'buttonAlign'    => array( 'spread', 'left', 'center', 'right' ),
		'ctaWidth'       => array( 'hug', 'full' ),
		'mobileOverflow' => array( 'stack', 'swipe' ),
		'imageFit'       => array( 'contain', 'cover' ),
	);

	/**
	 * The keys a phone override may change.
	 *
	 * @var list<string>
	 */
	public const PHONE_KEYS = array( 'orientation', 'imagePosition', 'align', 'buttonAlign', 'ctaWidth', 'imageFit' );

	/**
	 * Lasso's defaults for single and list.
	 *
	 * @var array<string, string>
	 */
	private const DEFAULTS = array(
		'orientation'    => 'side-by-side',
		'imagePosition'  => 'right',
		'align'          => 'left',
		'buttonAlign'    => 'spread',
		'ctaWidth'       => 'hug',
		'mobileOverflow' => 'stack',
		'imageFit'       => 'contain',
	);

	/**
	 * Lasso's differences for grid.
	 *
	 * @var array<string, string>
	 */
	private const GRID_DEFAULTS = array(
		'orientation'   => 'stacked',
		'imagePosition' => 'top',
		'align'         => 'center',
	);

	/**
	 * Use defaults() or from_array().
	 *
	 * @param bool                  $grid    Whether this is the grid's layout.
	 * @param array<string, string> $desktop A complete, valid desktop layout.
	 * @param array<string, string> $phone   The phone overrides, keyed by PHONE_KEYS.
	 */
	private function __construct(
		private readonly bool $grid,
		private readonly array $desktop,
		private readonly array $phone
	) {}

	/**
	 * Lasso's default layout for a display type.
	 *
	 * @param DisplayType $type The display type.
	 */
	public static function defaults( DisplayType $type ): self {
		$grid = $type->is( DisplayType::grid() );

		return new self( $grid, self::type_defaults( $grid ), array() );
	}

	/**
	 * Reads a stored layout: unknown or missing values take the type's default, image position is coerced to the
	 * orientation, grid is stacked, and phone overrides are validated the same way.
	 *
	 * @param DisplayType          $type   The display type.
	 * @param array<string, mixed> $stored Stored layout, with an optional `mobile` array of overrides.
	 */
	public static function from_array( DisplayType $type, array $stored ): self {
		$layout = self::defaults( $type );
		foreach ( array_keys( self::OPTIONS ) as $key ) {
			if ( isset( $stored[ $key ] ) && is_string( $stored[ $key ] ) ) {
				$layout = $layout->with( $key, $stored[ $key ] );
			}
		}

		$mobile = isset( $stored['mobile'] ) && is_array( $stored['mobile'] ) ? $stored['mobile'] : array();
		// Orientation first: an image-position override is coerced against the phone orientation.
		foreach ( self::PHONE_KEYS as $key ) {
			if ( isset( $mobile[ $key ] ) && is_string( $mobile[ $key ] ) && in_array( $mobile[ $key ], self::OPTIONS[ $key ], true ) ) {
				$layout = $layout->with_phone( $key, $mobile[ $key ] );
			}
		}

		return $layout;
	}

	/**
	 * One desktop value.
	 *
	 * @param string $key A key of OPTIONS.
	 * @throws InvalidArgumentException For a key Layout does not have; always a programming error.
	 */
	public function value( string $key ): string {
		if ( ! array_key_exists( $key, $this->desktop ) ) {
			throw new InvalidArgumentException( "Unknown layout key: $key" );
		}

		return $this->desktop[ $key ];
	}

	/**
	 * The desktop layout.
	 *
	 * @return array<string, string>
	 */
	public function desktop(): array {
		return $this->desktop;
	}

	/**
	 * The phone overrides the editor set; keys not in it follow the phone base.
	 *
	 * @return array<string, string>
	 */
	public function phone(): array {
		return $this->phone;
	}

	/**
	 * A copy with one desktop value changed. An unknown value is the type's default; image position is coerced to
	 * the orientation; grid stays stacked.
	 *
	 * @param string $key   A key of OPTIONS.
	 * @param string $value The new value.
	 * @throws InvalidArgumentException For a key Layout does not have; always a programming error.
	 */
	public function with( string $key, string $value ): self {
		if ( ! array_key_exists( $key, self::OPTIONS ) ) {
			throw new InvalidArgumentException( "Unknown layout key: $key" );
		}

		$desktop         = $this->desktop;
		$desktop[ $key ] = in_array( $value, self::OPTIONS[ $key ], true ) ? $value : self::type_defaults( $this->grid )[ $key ];
		if ( $this->grid ) {
			$desktop['orientation'] = 'stacked';
		}
		$desktop['imagePosition'] = self::coerce( $desktop['imagePosition'], $desktop['orientation'] );

		return new self( $this->grid, $desktop, $this->phone );
	}

	/**
	 * A copy with one phone override set. A key that is not a phone key, a value that is not an option, and
	 * grid's orientation are ignored; an image position is kept in the phone orientation's vocabulary.
	 *
	 * @param string $key   One of PHONE_KEYS.
	 * @param string $value The new value.
	 */
	public function with_phone( string $key, string $value ): self {
		$allowed = in_array( $key, self::PHONE_KEYS, true ) && in_array( $value, self::OPTIONS[ $key ], true );
		if ( ! $allowed || ( $this->grid && 'orientation' === $key ) ) {
			return $this;
		}

		$phone         = $this->phone;
		$phone[ $key ] = $value;
		if ( isset( $phone['imagePosition'] ) ) {
			$phone['imagePosition'] = self::coerce( $phone['imagePosition'], $phone['orientation'] ?? $this->phone_base()['orientation'] );
		}

		return new self( $this->grid, $this->desktop, $phone );
	}

	/**
	 * A copy without phone overrides: phones follow the phone base again ("Use desktop layout").
	 */
	public function clear_phone(): self {
		return new self( $this->grid, $this->desktop, array() );
	}

	/**
	 * The layout phones use: the phone base with the editor's overrides on top, image position coerced to the
	 * resulting orientation.
	 *
	 * @return array<string, string>
	 */
	public function phone_effective(): array {
		$phone                  = array_merge( $this->phone_base(), $this->phone );
		$phone['imagePosition'] = self::coerce( $phone['imagePosition'], $phone['orientation'] );

		return $phone;
	}

	/**
	 * The layout, for storage, in Lasso's shape: the desktop keys, and `mobile` only when it holds overrides.
	 *
	 * @return array<string, string|array<string, string>>
	 */
	public function to_array(): array {
		$stored = $this->desktop;
		if ( array() !== $this->phone ) {
			$stored['mobile'] = $this->phone;
		}

		return $stored;
	}

	/**
	 * Where phones start: desktop's phone keys with the buttons centred, and grid stacked.
	 *
	 * @return array<string, string>
	 */
	private function phone_base(): array {
		$base                = array_intersect_key( $this->desktop, array_flip( self::PHONE_KEYS ) );
		$base['buttonAlign'] = 'center';
		if ( $this->grid ) {
			$base['orientation'] = 'stacked';
		}

		return $base;
	}

	/**
	 * Lasso's defaults for single and list, or for grid.
	 *
	 * @param bool $grid Whether these are the grid's defaults.
	 * @return array<string, string>
	 */
	private static function type_defaults( bool $grid ): array {
		return $grid ? array_merge( self::DEFAULTS, self::GRID_DEFAULTS ) : self::DEFAULTS;
	}

	/**
	 * An image position in the vocabulary of an orientation (Helper::coerce_display_layout_image_position).
	 *
	 * @param string $position    left, right, top or bottom.
	 * @param string $orientation side-by-side or stacked.
	 */
	private static function coerce( string $position, string $orientation ): string {
		if ( 'side-by-side' === $orientation ) {
			return match ( $position ) {
				'top', 'left' => 'left',
				default       => 'right',
			};
		}

		return match ( $position ) {
			'right', 'bottom' => 'bottom',
			default           => 'top',
		};
	}
}
