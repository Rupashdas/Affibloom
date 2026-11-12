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
			DisplayConfig::defaults()->with_theme( Theme::from( 'Llama' ) )
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
			DisplayConfig::defaults()->with_palette( DisplayConfig::defaults()->palette()->with( 'main', '#123456' ) )
		);

		$after = DisplaySettings::from_array( $before->to_array() );

		self::assertSame( '#123456', $after->config( DisplayType::list() )->palette()->value( 'main' ) );
		self::assertSame( $before->to_array(), $after->to_array() );
	}
}
