<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Tests\Unit\TestCase;
use Affibloom\Application\Settings\SettingsService;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;

final class SettingsServiceTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\Functions\when( 'sanitize_text_field' )->alias( static fn( $s ) => trim( strip_tags( (string) $s ) ) );
	}

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

	public function test_an_invalid_tracking_id_keeps_the_old_value(): void {
		$repo = $this->repository();
		$svc  = new SettingsService( $repo );
		$svc->update_tab( 'amazon', array( 'tracking_id' => 'vbvault-20' ) );

		$refused = $svc->update_tab( 'amazon', array( 'tracking_id' => 'vbvault' ) );

		self::assertSame( array( 'tracking_id' ), $refused );
		self::assertSame( 'vbvault-20', $repo->get()->string( 'tracking_id' ) );
	}

}
