<?php
namespace Affibloom\Tests\Unit\Domain\Settings;

use Affibloom\Domain\Settings\PerformanceMode;
use PHPUnit\Framework\TestCase;

final class PerformanceModeTest extends TestCase {
	public function test_the_presets_are_lassos(): void {
		self::assertSame(
			array(
				'cpu'      => 80,
				'interval' => 0,
				'limit'    => 2,
			),
			PerformanceMode::preset( 'power' )
		);
		self::assertSame( 65, PerformanceMode::preset( 'balanced' )['cpu'] );
		self::assertSame(
			array(
				'cpu'      => 50,
				'interval' => 1,
				'limit'    => 1,
			),
			PerformanceMode::preset( 'light' )
		);
	}

	public function test_custom_has_no_preset_and_unknown_is_not_a_mode(): void {
		self::assertNull( PerformanceMode::preset( 'custom' ) );
		self::assertTrue( PerformanceMode::is_mode( 'custom' ) );
		self::assertFalse( PerformanceMode::is_mode( 'turbo' ) );
	}

	/** Loading a page never rewrites a stored value (D13): a site with Balanced and 80 keeps both. */
	public function test_a_stored_mode_with_another_cpu_level_is_not_corrected(): void {
		$settings = \Affibloom\Domain\Settings\Settings::from_array(
			array(
				'performance_mode' => 'balanced',
				'cpu_threshold'    => 80,
			)
		);

		self::assertSame( 'balanced', $settings->string( 'performance_mode' ) );
		self::assertSame( 80, $settings->int( 'cpu_threshold' ) );
	}
}
