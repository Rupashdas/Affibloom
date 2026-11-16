<?php
namespace Affibloom\Tests\Unit\Domain\Display;

use PHPUnit\Framework\TestCase;
use Affibloom\Domain\Display\DisplayConfig;
use Affibloom\Domain\Display\DisplaySettings;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Domain\Display\Theme;

final class DisplaySettingsTest extends TestCase {
	public function test_each_type_keeps_its_own_configuration(): void {
		$settings = DisplaySettings::defaults()->with_config(
			DisplayType::single(),
			DisplayConfig::defaults( DisplayType::single() )->with_theme( Theme::from( 'Llama' ) )
		);

		self::assertSame( 'Llama', $settings->config( DisplayType::single() )->theme()->name() );
		self::assertSame( 'Cactus', $settings->config( DisplayType::grid() )->theme()->name() );
	}

	public function test_a_malformed_option_falls_back_to_the_defaults(): void {
		$settings = DisplaySettings::from_array(
			array(
				'single' => array( 'theme' => 'Lab' ),
				'grid'   => 'not an array',
			)
		);

		self::assertSame( 'Lab', $settings->config( DisplayType::single() )->theme()->name() );
		self::assertSame( 'Cactus', $settings->config( DisplayType::grid() )->theme()->name() );
		self::assertSame( 'Buy Now', $settings->config( DisplayType::list() )->palette()->value( 'primary_button_text' ) );
	}

	public function test_it_survives_a_round_trip_through_storage(): void {
		$before = DisplaySettings::defaults()->with_config(
			DisplayType::list(),
			DisplayConfig::defaults( DisplayType::list() )->with_palette( DisplayConfig::defaults( DisplayType::list() )->palette()->with( 'main', '#123456' ) )
		);

		$after = DisplaySettings::from_array( $before->to_array() );

		self::assertSame( '#123456', $after->config( DisplayType::list() )->palette()->value( 'main' ) );
		self::assertSame( $before->to_array(), $after->to_array() );
	}

	/**
	 * F5 R53, F16 B1 (Task 18K): Lasso's defaults keep the classic box; any saved difference turns advanced rendering
	 * on; colours and labels alone, the same on every type, do not.
	 */
	public function test_any_saved_difference_turns_advanced_rendering_on(): void {
		self::assertFalse( DisplaySettings::defaults()->customised() );
		self::assertFalse( DisplaySettings::defaults()->advanced() );

		$on = static fn( array $stored ): bool => DisplaySettings::from_array( $stored )->customised();
		self::assertTrue( $on( array( 'single' => array( 'density' => array( 'preset' => 'spacious' ) ) ) ) );
		self::assertTrue( $on( array( 'grid' => array( 'density' => array( 'preset' => 'custom', 'values' => array( 'gap' => 9 ) ) ) ) ) );
		self::assertFalse( $on( array( 'single' => array( 'density' => array( 'preset' => 'custom', 'values' => array() ) ) ) ) );
		self::assertTrue( $on( array( 'columns' => 2 ) ) );
		self::assertTrue( $on( array( 'list' => array( 'layout' => array( 'align' => 'center' ) ) ) ) );
		self::assertTrue( $on( array( 'single' => array( 'layout' => array( 'mobile' => array( 'align' => 'center' ) ) ) ) ) );
		self::assertTrue( $on( array( 'single' => array( 'elements' => array( 'badge' => false ) ) ) ) );
		self::assertTrue( $on( array( 'single' => array( 'elements' => array( 'price' => false ) ) ) ) );

		// Colours: the same on every type keeps classic; Grid or List apart from Single turns it on; a colour name and
		// its hex are the same colour.
		$same = array( 'palette' => array( 'main' => '#22BAA0' ) );
		self::assertFalse( $on( array( 'single' => $same, 'grid' => $same, 'list' => $same ) ) );
		self::assertTrue( $on( array( 'single' => $same, 'grid' => array( 'palette' => array( 'main' => '#000000' ) ), 'list' => $same ) ) );
		$black = array( 'palette' => array( 'title' => 'black' ) );
		self::assertFalse( $on( array( 'single' => $black, 'grid' => array( 'palette' => array( 'title' => '#000' ) ), 'list' => $black ) ) );
	}

	/**
	 * F16 B13: the engine as decided last (the filter) wins over the saved settings, and survives copies.
	 */
	public function test_the_decided_engine_wins_and_survives_copies(): void {
		$custom = DisplaySettings::from_array( array( 'columns' => 2 ) );

		self::assertFalse( $custom->with_advanced( false )->advanced() );
		self::assertTrue( DisplaySettings::defaults()->with_advanced( true )->advanced() );
		self::assertFalse( $custom->with_advanced( false )->with_columns( 1 )->advanced() );
	}
}
