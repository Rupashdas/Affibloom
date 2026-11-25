<?php
namespace Affibloom\Tests\Unit\Infrastructure\WordPress;

use Affibloom\Domain\Settings\Settings;
use Affibloom\Infrastructure\WordPress\OptionSettingsRepository;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * Decision B4 reversed and deviation 168 (2026-11-25; schema steps 13 and 14): the removed settings are no settings, and
 * the cleanup takes their stored values out of `affibloom_settings`.
 */
final class OptionSettingsRepositoryTest extends TestCase {

	public function test_the_cleanup_removes_the_three_keys_and_keeps_the_rest(): void {
		$stored = array(
			'tracking_id'                    => 'vbvault-20',
			'commission_upgrades'            => true,
			'mobile_deep_linking'            => false,
			'international_click'            => true,
			'shorten_amazon_links'           => false,
			'performance_mode'               => 'balanced',
			'show_disclosure_list'           => true,
			'force_change_from_link_details' => true,
			'cpu_threshold'                  => 80,
		);
		Functions\when( 'get_option' )->justReturn( $stored );
		$written = null;
		Functions\expect( 'update_option' )->once()->andReturnUsing(
			static function ( string $option, array $value, bool $autoload ) use ( &$written ): bool {
				self::assertSame( OptionSettingsRepository::OPTION, $option );
				self::assertTrue( $autoload );
				$written = $value;
				return true;
			}
		);

		OptionSettingsRepository::remove_retired();

		self::assertSame(
			array(
				'tracking_id'          => 'vbvault-20',
				'shorten_amazon_links' => false,
				'cpu_threshold'        => 80,
			),
			$written
		);
	}

	public function test_the_cleanup_writes_nothing_when_no_key_is_stored(): void {
		Functions\when( 'get_option' )->justReturn( array( 'tracking_id' => 'vbvault-20' ) );
		Functions\expect( 'update_option' )->never();

		OptionSettingsRepository::remove_retired();
		self::assertTrue( true );
	}

	public function test_the_three_keys_are_no_settings(): void {
		foreach ( OptionSettingsRepository::RETIRED as $key ) {
			self::assertArrayNotHasKey( $key, Settings::DEFAULTS );
			self::assertArrayNotHasKey( $key, Settings::from_array( array( $key => true ) )->to_array() );
		}
	}
}
