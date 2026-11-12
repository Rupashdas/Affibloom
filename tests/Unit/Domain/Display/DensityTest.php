<?php
namespace Affibloom\Tests\Unit\Domain\Display;

use PHPUnit\Framework\TestCase;
use Affibloom\Domain\Display\Density;

/**
 * Lasso's rules: Helper::normalize_display_density_values() for stored data, and settings.js
 * clamp_density_stepper_value() / adjust_density_stepper() for edits.
 */
final class DensityTest extends TestCase {
	public function test_a_preset_applies_its_value_map(): void {
		$density = Density::preset( 'spacious' );

		self::assertSame( 'spacious', $density->preset_name() );
		self::assertSame( 32, $density->value( 'gap' ) );
		self::assertSame( 26, $density->value( 'padding' ) );
		self::assertSame( 30, $density->value( 'titleSize' ) );
		// Presets are not clamped to the stepper range: Lasso's spacious image height is above the maximum.
		self::assertSame( 320, $density->value( 'imageHeight' ) );
	}

	public function test_an_unknown_preset_is_balanced(): void {
		self::assertSame( 'balanced', Density::preset( 'roomy' )->preset_name() );
		self::assertSame( 20, Density::preset( 'roomy' )->value( 'gap' ) );
	}

	public function test_a_stored_named_preset_ignores_the_stored_values(): void {
		$density = Density::from_array(
			array(
				'preset' => 'spacious',
				'values' => array( 'gap' => 99 ),
			)
		);

		self::assertSame( 32, $density->value( 'gap' ) ); // the preset's map wins, as in Lasso
	}

	public function test_stored_custom_values_are_kept_unclamped_over_balanced(): void {
		$density = Density::from_array(
			array(
				'preset' => 'custom',
				'values' => array(
					'imageHeight' => 320,
					'gap'         => 'not a number',
				),
			)
		);

		self::assertSame( 'custom', $density->preset_name() );
		self::assertSame( 320, $density->value( 'imageHeight' ) ); // stored data is not clamped
		self::assertSame( 20, $density->value( 'gap' ) );          // junk falls back to balanced
		self::assertSame( 26, $density->value( 'titleSize' ) );    // missing falls back to balanced
	}

	public function test_an_edit_clamps_to_the_range_and_makes_the_preset_custom(): void {
		$density = Density::preset( 'balanced' )->with_value( 'padding', 99 );

		self::assertSame( 48, $density->value( 'padding' ) );
		self::assertSame( 'custom', $density->preset_name() );
		self::assertSame( 0, $density->with_value( 'gap', -5 )->value( 'gap' ) );
		// An edit is not snapped to the step: the stepper moves by the step, the model only clamps.
		self::assertSame( 25, $density->with_value( 'gap', 25 )->value( 'gap' ) );
	}

	public function test_stepping_a_preset_value_above_the_maximum_brings_it_into_range(): void {
		// spacious imageHeight 320, step 4: Lasso's stepper sends 316 or 324, and both clamp to 280.
		self::assertSame( 280, Density::preset( 'spacious' )->with_value( 'imageHeight', 316 )->value( 'imageHeight' ) );
	}

	public function test_line_clamps_take_one_to_three_or_zero_for_all(): void {
		self::assertSame( 2, Density::preset( 'balanced' )->with_value( 'titleLines', 2 )->value( 'titleLines' ) );
		self::assertSame( 0, Density::preset( 'balanced' )->with_value( 'descLines', 9 )->value( 'descLines' ) );
	}

	public function test_an_unknown_value_key_is_a_programming_error(): void {
		$this->expectException( \InvalidArgumentException::class );
		Density::preset( 'balanced' )->with_value( 'nope', 1 );
	}

	public function test_it_survives_a_round_trip_through_storage(): void {
		$before = Density::preset( 'compact' )->with_value( 'titleSize', 28 );
		$after  = Density::from_array( $before->to_array() );

		self::assertSame( 'custom', $after->preset_name() );
		self::assertSame( 28, $after->value( 'titleSize' ) );
		self::assertSame( 12, $after->value( 'gap' ) ); // compact's gap, carried into custom
	}
}
