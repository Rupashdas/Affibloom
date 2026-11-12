<?php
/**
 * The spacing, type sizes and image height of one display type: a named preset, or custom values.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

use InvalidArgumentException;

/**
 * The spacing, type sizes and image height of one display type.
 *
 * Lasso's rules, kept exactly (Helper::normalize_display_density_values, settings.js steppers):
 * - a named preset always means its own value map; any stored values beside it are ignored;
 * - `custom` keeps the stored values, merged over balanced, and does not clamp them;
 * - an edit clamps to the control's range and turns the preset into `custom`; it is not snapped to the step,
 *   because Lasso's stepper adds or subtracts the step and then only clamps.
 */
final class Density {

	public const CUSTOM = 'custom';

	/**
	 * The three presets, as Lasso defines them (Helper::get_display_density_presets).
	 *
	 * @var array<string, array<string, int>>
	 */
	public const PRESETS = array(
		'compact'  => array(
			'gap'          => 12,
			'padding'      => 8,
			'spaceBetween' => 4,
			'titleSize'    => 24,
			'titleLines'   => 0,
			'descSize'     => 16,
			'descLines'    => 0,
			'priceSize'    => 14,
			'buttonSize'   => 15,
			'imageHeight'  => 140,
		),
		'balanced' => array(
			'gap'          => 20,
			'padding'      => 16,
			'spaceBetween' => 10,
			'titleSize'    => 26,
			'titleLines'   => 0,
			'descSize'     => 16,
			'descLines'    => 0,
			'priceSize'    => 16,
			'buttonSize'   => 15,
			'imageHeight'  => 280,
		),
		'spacious' => array(
			'gap'          => 32,
			'padding'      => 26,
			'spaceBetween' => 16,
			'titleSize'    => 30,
			'titleLines'   => 0,
			'descSize'     => 17,
			'descLines'    => 0,
			'priceSize'    => 19,
			'buttonSize'   => 15,
			'imageHeight'  => 320,
		),
	);

	/**
	 * The range and stepper increment of each numeric value (settings.js DISPLAY_DENSITY_STEPPERS).
	 *
	 * @var array<string, array{min: int, max: int, step: int}>
	 */
	public const LIMITS = array(
		'gap'          => array(
			'min'  => 0,
			'max'  => 48,
			'step' => 2,
		),
		'padding'      => array(
			'min'  => 0,
			'max'  => 48,
			'step' => 2,
		),
		'spaceBetween' => array(
			'min'  => 0,
			'max'  => 28,
			'step' => 1,
		),
		'titleSize'    => array(
			'min'  => 11,
			'max'  => 40,
			'step' => 1,
		),
		'descSize'     => array(
			'min'  => 10,
			'max'  => 22,
			'step' => 1,
		),
		'priceSize'    => array(
			'min'  => 10,
			'max'  => 26,
			'step' => 1,
		),
		'buttonSize'   => array(
			'min'  => 10,
			'max'  => 22,
			'step' => 1,
		),
		'imageHeight'  => array(
			'min'  => 48,
			'max'  => 280,
			'step' => 4,
		),
	);

	/**
	 * The line-clamp values and the choices they offer: 1, 2, 3 lines, or 0 for All.
	 *
	 * @var list<string>
	 */
	public const LINE_KEYS = array( 'titleLines', 'descLines' );

	/**
	 * Choices for the line clamps.
	 *
	 * @var list<int>
	 */
	public const LINE_OPTIONS = array( 1, 2, 3, 0 );

	/**
	 * Use preset() or from_array().
	 *
	 * @param string             $preset One of the PRESETS keys, or CUSTOM.
	 * @param array<string, int> $values A complete set of values.
	 */
	private function __construct( private readonly string $preset, private readonly array $values ) {}

	/**
	 * A named preset with its value map. An unknown name is balanced, Lasso's default.
	 *
	 * @param string $name compact, balanced or spacious.
	 */
	public static function preset( string $name ): self {
		$name = isset( self::PRESETS[ $name ] ) ? $name : 'balanced';

		return new self( $name, self::PRESETS[ $name ] );
	}

	/**
	 * Reads stored density as Lasso does: a named preset means its map; anything else is custom values merged
	 * over balanced, kept as they are. A value that is not a number takes balanced's.
	 *
	 * @param array<string, mixed> $stored Stored density, `{preset, values}`.
	 */
	public static function from_array( array $stored ): self {
		$name = isset( $stored['preset'] ) && is_string( $stored['preset'] ) ? $stored['preset'] : 'balanced';
		if ( isset( self::PRESETS[ $name ] ) ) {
			return self::preset( $name );
		}

		$values = self::PRESETS['balanced'];
		$given  = isset( $stored['values'] ) && is_array( $stored['values'] ) ? $stored['values'] : array();
		foreach ( $values as $key => $default ) {
			if ( isset( $given[ $key ] ) && is_numeric( $given[ $key ] ) ) {
				$values[ $key ] = (int) $given[ $key ];
			}
		}

		return new self( self::CUSTOM, $values );
	}

	/**
	 * The preset's name, or `custom`.
	 */
	public function preset_name(): string {
		return $this->preset;
	}

	/**
	 * One value.
	 *
	 * @param string $key A key of the preset maps.
	 * @throws InvalidArgumentException For a key Density does not have; always a programming error.
	 */
	public function value( string $key ): int {
		if ( ! array_key_exists( $key, $this->values ) ) {
			throw new InvalidArgumentException( "Unknown density value: $key" );
		}

		return $this->values[ $key ];
	}

	/**
	 * A copy with one value edited, as a stepper or a line-clamp button edits it: clamped to the control's range
	 * (a line clamp outside 1-3 means All), and the preset becomes `custom`.
	 *
	 * @param string $key   A key of the preset maps.
	 * @param int    $value The new value.
	 * @throws InvalidArgumentException For a key Density does not have; always a programming error.
	 */
	public function with_value( string $key, int $value ): self {
		if ( ! array_key_exists( $key, $this->values ) ) {
			throw new InvalidArgumentException( "Unknown density value: $key" );
		}

		if ( in_array( $key, self::LINE_KEYS, true ) ) {
			$value = in_array( $value, self::LINE_OPTIONS, true ) ? $value : 0;
		} else {
			$value = min( self::LIMITS[ $key ]['max'], max( self::LIMITS[ $key ]['min'], $value ) );
		}

		return new self( self::CUSTOM, array_merge( $this->values, array( $key => $value ) ) );
	}

	/**
	 * Every value.
	 *
	 * @return array<string, int>
	 */
	public function values(): array {
		return $this->values;
	}

	/**
	 * The density, for storage, in Lasso's shape.
	 *
	 * @return array{preset: string, values: array<string, int>}
	 */
	public function to_array(): array {
		return array(
			'preset' => $this->preset,
			'values' => $this->values,
		);
	}
}
