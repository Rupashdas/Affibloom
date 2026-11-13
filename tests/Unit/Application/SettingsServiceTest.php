<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Tests\Unit\TestCase;
use Affibloom\Application\Settings\SettingsService;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;

final class SettingsServiceTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\Functions\when( 'esc_url_raw' )->alias(
			static fn( $u ) => preg_match( '#^https?://#', (string) $u ) ? (string) $u : ''
		);
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

	/**
	 * The product page's offer (Lasso's amazon-url-detected notice, Task 14D) sets the tracking ID alone, by the Amazon
	 * tab's rule, leaving every other setting as it was.
	 */
	public function test_the_tracking_id_alone_is_set_by_the_amazon_tabs_rule(): void {
		$repo = $this->repository();
		$svc  = new SettingsService( $repo );
		$svc->update_tab( 'amazon', array( 'shorten_amazon_links' => '1', 'multiple_tracking_ids' => '1' ) );

		self::assertTrue( $svc->set_tracking_id( 'vbvault-20' ) );
		self::assertSame( 'vbvault-20', $repo->get()->string( 'tracking_id' ) );
		self::assertTrue( $repo->get()->bool( 'shorten_amazon_links' ) );
		self::assertTrue( $repo->get()->bool( 'multiple_tracking_ids' ) );

		self::assertFalse( $svc->set_tracking_id( 'not a tag' ) );
		self::assertFalse( $svc->set_tracking_id( '' ) );
		self::assertSame( 'vbvault-20', $repo->get()->string( 'tracking_id' ) );
	}

	public function test_custom_css_keeps_the_rules_and_loses_any_markup(): void {
		$repo = $this->repository();
		( new SettingsService( $repo ) )->update_tab( 'display', array( 'custom_css' => ".box { color: red; }</style><script>alert(1)</script>" ) );

		$css = $repo->get()->string( 'custom_css' );
		self::assertStringContainsString( '.box { color: red; }', $css );
		self::assertStringNotContainsString( '<', $css ); // Lasso's format_css allowlist: "</style>" can never be written
	}

	public function test_a_brag_url_must_be_a_link_or_empty(): void {
		$repo    = $this->repository();
		$service = new SettingsService( $repo );

		self::assertSame( array(), array_intersect( array( 'brag_url' ), $service->update_tab( 'display', array( 'brag_url' => '' ) ) ) );
		self::assertContains( 'brag_url', $service->update_tab( 'display', array( 'brag_url' => 'javascript:alert(1)' ) ) );
	}

	public function test_resetting_the_display_tab_restores_its_defaults_only(): void {
		$repo    = $this->repository();
		$service = new SettingsService( $repo );
		$service->update_tab( 'display', array( 'show_price' => '', 'custom_css' => 'a{}' ) ); // show_price off
		$service->update_tab( 'general', array( 'new_tab' => '' ) );                          // a General value

		$service->reset_tab( 'display' );

		self::assertTrue( $repo->get()->bool( 'show_price' ) );        // Lasso's default
		self::assertSame( '', $repo->get()->string( 'custom_css' ) );
		self::assertFalse( $repo->get()->bool( 'new_tab' ) );          // General untouched
	}

	public function test_tracking_ids_follow_lassos_rule(): void {
		$repo    = $this->repository();
		$service = new SettingsService( $repo );

		foreach ( array( 'vbvault-20', 'my_tag.us-21', 'tag-123', '' ) as $valid ) {
			self::assertSame( array(), array_intersect( array( 'tracking_id' ), $service->update_tab( 'amazon', array( 'tracking_id' => $valid ) ) ), $valid );
		}
		foreach ( array( 'vbvault', 'tag-1', 'tag-1234', 'bad tag-20' ) as $invalid ) {
			self::assertContains( 'tracking_id', $service->update_tab( 'amazon', array( 'tracking_id' => $invalid ) ), $invalid );
		}
	}

	public function test_the_whitelist_is_a_sorted_set_and_refuses_an_invalid_id(): void {
		$repo    = $this->repository();
		$service = new SettingsService( $repo );

		$service->update_tab( 'amazon', array( 'tracking_id_whitelist' => 'b-20 a-20  b-20' ) );
		self::assertSame( array( 'a-20', 'b-20' ), $repo->get()->list( 'tracking_id_whitelist' ) );

		self::assertContains( 'tracking_id_whitelist', $service->update_tab( 'amazon', array( 'tracking_id_whitelist' => 'a-20 nope' ) ) );
		self::assertSame( array( 'a-20', 'b-20' ), $repo->get()->list( 'tracking_id_whitelist' ) ); // kept
	}

	public function test_a_removed_whitelist_id_is_added_to_the_blacklist(): void {
		$repo    = $this->repository();
		$service = new SettingsService( $repo );
		$service->update_tab( 'amazon', array( 'tracking_id_whitelist' => 'a-20 b-20 c-20' ) );

		$service->update_tab( 'amazon', array( 'tracking_id_whitelist' => 'b-20' ) );
		$service->update_tab( 'amazon', array( 'tracking_id_whitelist' => '' ) );

		self::assertSame( array( 'a-20', 'b-20', 'c-20' ), $repo->get()->list( 'tracking_id_blacklist' ) );
	}

	public function test_out_of_stock_days_take_zero_to_thirty(): void {
		$repo    = $this->repository();
		$service = new SettingsService( $repo );

		$service->update_tab( 'amazon', array( 'days_before_out_of_stock_alerts' => '7' ) );
		self::assertSame( 7, $repo->get()->int( 'days_before_out_of_stock_alerts' ) );
		self::assertContains( 'days_before_out_of_stock_alerts', $service->update_tab( 'amazon', array( 'days_before_out_of_stock_alerts' => '31' ) ) );
		self::assertSame( 7, $repo->get()->int( 'days_before_out_of_stock_alerts' ) );
	}

	public function test_the_amazon_defaults_are_lassos(): void {
		$settings = $this->repository()->get();

		self::assertTrue( $settings->bool( 'multiple_tracking_ids' ) );
		self::assertFalse( $settings->bool( 'auto_monetize_amazon' ) );
		self::assertTrue( $settings->bool( 'update_pricing_daily' ) );
		self::assertTrue( $settings->bool( 'commission_upgrades' ) );
		self::assertFalse( $settings->bool( 'scan_sitestripe_in_post_content' ) );
		self::assertSame( array(), $settings->list( 'tracking_id_whitelist' ) );
	}

	public function test_a_field_that_was_not_posted_keeps_its_value(): void {
		$repo    = $this->repository();
		$service = new SettingsService( $repo );
		$service->update_tab( 'amazon', array( 'tracking_id_whitelist' => 'a-20' ) );

		// Allow Multiple Tracking IDs off: the browser does not post the disabled whitelist.
		$refused = $service->update_tab( 'amazon', array( 'multiple_tracking_ids' => '' ) );

		self::assertSame( array(), $refused );
		self::assertSame( array( 'a-20' ), $repo->get()->list( 'tracking_id_whitelist' ) );
		self::assertFalse( $repo->get()->bool( 'multiple_tracking_ids' ) );
	}
}
