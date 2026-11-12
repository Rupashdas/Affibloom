<?php
namespace Affibloom\Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use Affibloom\Application\Settings\SettingsService;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;

final class SettingsServiceTest extends TestCase {
	private function repository(): SettingsRepository {
		return new class() implements SettingsRepository {
			public Settings $stored;
			public function __construct() {
				$this->stored = Settings::from_array( array() );
			}
			public function get(): Settings {
				return $this->stored;
			}
			public function save( Settings $settings ): void {
				$this->stored = $settings;
			}
		};
	}

	public function test_an_unticked_checkbox_turns_a_setting_off(): void {
		$repo    = $this->repository();
		$refused = ( new SettingsService( $repo ) )->update_tab( 'general', array( 'new_tab' => '1' ) );

		self::assertSame( array(), $refused );
		self::assertTrue( $repo->get()->bool( 'new_tab' ) );
		self::assertFalse( $repo->get()->bool( 'nofollow' ) ); // was true by default, not sent
	}

	public function test_an_unknown_tab_is_refused(): void {
		$this->expectException( \InvalidArgumentException::class );
		( new SettingsService( $this->repository() ) )->update_tab( 'nope', array() );
	}
}
