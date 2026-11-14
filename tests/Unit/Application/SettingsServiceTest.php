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
		\Brain\Monkey\Functions\when( 'sanitize_title' )->alias( static fn( $s ) => trim( (string) preg_replace( '/[^a-z0-9_]+/', '-', strtolower( (string) $s ) ), '-' ) );
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

	public function test_brag_mode_is_not_stored_even_when_it_is_sent(): void {
		$repo = $this->repository();
		( new SettingsService( $repo ) )->update_tab( 'display', array( 'enable_brag_mode' => '1', 'brag_url' => 'https://example.com/join' ) );

		$stored = $repo->get()->to_array();
		self::assertArrayNotHasKey( 'enable_brag_mode', $stored ); // decision B9: Affibloom has no brag mode
		self::assertArrayNotHasKey( 'brag_url', $stored );
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

	/** All the General switches, as the form posts them when ticked. */
	private const GENERAL_ON = array(
		'new_tab'              => '1',
		'nofollow'             => '1',
		'sponsored'            => '1',
		'show_disclosure'      => '1',
		'allow_duplicates'     => '1',
		'link_cloaking'        => '1',
		'detect_opportunities' => '1',
		'auto_detect_links'    => '1',
	);

	private function choices(): \Affibloom\Application\Settings\DetectionChoices {
		return new class() implements \Affibloom\Application\Settings\DetectionChoices {
			public function post_types(): array {
				return array(
					'recipe'  => 'Recipes',
					'product' => 'Products',
				);
			}
			public function custom_fields(): array {
				return array( 'review_link' => 'Review link' );
			}
			public function has_acf(): bool {
				return true;
			}
		};
	}

	public function test_a_fresh_install_has_lassos_link_defaults_all_on(): void {
		$settings = $this->repository()->get();

		foreach ( array_keys( self::GENERAL_ON ) as $key ) {
			self::assertTrue( $settings->bool( $key ), $key );
		}
	}

	public function test_allow_duplicates_is_saved_and_can_be_switched_off(): void {
		$repo    = $this->repository();
		$service = new SettingsService( $repo );

		$service->update_tab( 'general', array_diff_key( self::GENERAL_ON, array( 'allow_duplicates' => 1 ) ) );

		self::assertFalse( $repo->get()->bool( 'allow_duplicates' ) );
		self::assertTrue( $repo->get()->bool( 'link_cloaking' ) );
	}

	public function test_google_analytics_switches_are_off_without_a_tracking_id(): void {
		$repo    = $this->repository();
		$service = new SettingsService( $repo );

		$service->update_tab(
			'general',
			array(
				'ga_click_tracking' => '1',
				'ga_pageview'       => '1',
				'ga_anonymize_ip'   => '1',
			)
		);

		self::assertFalse( $repo->get()->bool( 'ga_click_tracking' ) );
		self::assertFalse( $repo->get()->bool( 'ga_pageview' ) );
		self::assertFalse( $repo->get()->bool( 'ga_anonymize_ip' ) );
	}

	public function test_pageview_and_ip_anonymization_need_click_tracking(): void {
		$repo    = $this->repository();
		$service = new SettingsService( $repo );

		$service->update_tab(
			'general',
			array(
				'ga_tracking_id'  => 'G-ABC123',
				'ga_pageview'     => '1',
				'ga_anonymize_ip' => '1',
			)
		);
		self::assertSame( 'G-ABC123', $repo->get()->string( 'ga_tracking_id' ) );
		self::assertFalse( $repo->get()->bool( 'ga_pageview' ) );

		$service->update_tab(
			'general',
			array(
				'ga_tracking_id'    => 'G-ABC123',
				'ga_click_tracking' => '1',
				'ga_pageview'       => '1',
			)
		);
		self::assertTrue( $repo->get()->bool( 'ga_click_tracking' ) );
		self::assertTrue( $repo->get()->bool( 'ga_pageview' ) );
		self::assertFalse( $repo->get()->bool( 'ga_anonymize_ip' ) );

		// Emptying the ID switches all three off.
		$service->update_tab(
			'general',
			array(
				'ga_tracking_id'    => '',
				'ga_click_tracking' => '1',
				'ga_pageview'       => '1',
			)
		);
		self::assertFalse( $repo->get()->bool( 'ga_click_tracking' ) );
		self::assertFalse( $repo->get()->bool( 'ga_pageview' ) );
	}

	public function test_the_prefix_is_stored_as_a_slug(): void {
		$repo = $this->repository();
		( new SettingsService( $repo ) )->update_tab( 'general', array( 'link_prefix' => 'Re Commends!' ) );

		self::assertSame( 're-commends', $repo->get()->string( 'link_prefix' ) );
	}

	public function test_a_prefix_that_slugifies_to_nothing_means_no_prefix(): void {
		$repo    = $this->repository();
		$service = new SettingsService( $repo );
		$service->update_tab( 'general', array( 'link_prefix' => 'go' ) );
		$service->update_tab( 'general', array( 'link_prefix' => '!!!' ) );

		self::assertSame( '', $repo->get()->string( 'link_prefix' ) );
	}

	public function test_smart_css_is_on_for_a_fresh_install_and_an_unticked_box_turns_it_off(): void {
		$repo = $this->repository();
		self::assertTrue( $repo->get()->bool( 'smart_css' ) );

		( new SettingsService( $repo ) )->update_tab( 'general', array() );

		self::assertFalse( $repo->get()->bool( 'smart_css' ) );
	}

	public function test_the_performance_mode_and_cpu_level_are_checked(): void {
		$repo    = $this->repository();
		$service = new SettingsService( $repo );

		self::assertSame( 'balanced', $repo->get()->string( 'performance_mode' ) );
		self::assertSame( 65, $repo->get()->int( 'cpu_threshold' ) );

		$service->update_tab(
			'general',
			array(
				'performance_mode' => 'custom',
				'cpu_threshold'    => '42',
			)
		);
		self::assertSame( 'custom', $repo->get()->string( 'performance_mode' ) );
		self::assertSame( 42, $repo->get()->int( 'cpu_threshold' ) );

		$refused = $service->update_tab(
			'general',
			array(
				'performance_mode' => 'turbo',
				'cpu_threshold'    => '101',
			)
		);
		self::assertEqualsCanonicalizing( array( 'performance_mode', 'cpu_threshold' ), $refused );
		self::assertSame( 'custom', $repo->get()->string( 'performance_mode' ) );
		self::assertSame( 42, $repo->get()->int( 'cpu_threshold' ) );
	}

	public function test_saving_another_field_never_rewrites_a_stored_cpu_level(): void {
		// This site: Balanced with 80 (Lasso never rewrites it on load or on an unrelated save).
		$repo         = $this->repository();
		$repo->stored = $repo->stored->with( array( 'cpu_threshold' => 80 ) );

		( new SettingsService( $repo ) )->update_tab( 'general', array( 'new_tab' => '1' ) );

		self::assertSame( 'balanced', $repo->get()->string( 'performance_mode' ) );
		self::assertSame( 80, $repo->get()->int( 'cpu_threshold' ) );
	}

	public function test_custom_link_detection_takes_only_what_the_site_offers(): void {
		$repo    = $this->repository();
		$service = new SettingsService( $repo, $this->choices() );

		$service->update_tab(
			'general',
			array(
				'scan_post_types'    => array( 'recipe', 'recipe' ),
				'scan_custom_fields' => array( 'review_link' ),
			)
		);
		self::assertSame( array( 'recipe' ), $repo->get()->list( 'scan_post_types' ) );
		self::assertSame( array( 'review_link' ), $repo->get()->list( 'scan_custom_fields' ) );

		// An unknown post type refuses the whole list, and the old choice stays.
		$refused = $service->update_tab( 'general', array( 'scan_post_types' => array( 'recipe', 'ghost' ) ) );
		self::assertSame( array( 'scan_post_types' ), $refused );
		self::assertSame( array( 'recipe' ), $repo->get()->list( 'scan_post_types' ) );

		// Nothing ticked posts the empty value: the choice is cleared, and the defaults apply.
		$service->update_tab( 'general', array( 'scan_post_types' => '' ) );
		self::assertSame( array(), $repo->get()->list( 'scan_post_types' ) );
	}

	public function test_without_choices_no_detection_choice_is_valid(): void {
		$refused = ( new SettingsService( $this->repository() ) )->update_tab( 'general', array( 'scan_post_types' => array( 'recipe' ) ) );

		self::assertSame( array( 'scan_post_types' ), $refused );
	}

	public function test_the_notification_switches_are_saved(): void {
		$repo = $this->repository();
		( new SettingsService( $repo ) )->update_tab( 'general', array( 'disable_help_tips' => '1' ) );

		self::assertTrue( $repo->get()->bool( 'disable_help_tips' ) );
		self::assertFalse( $repo->get()->bool( 'disable_amazon_notice' ) );
		self::assertFalse( $repo->get()->bool( 'disable_import_notice' ) );
	}

	public function test_the_support_fields_are_saved_only_when_the_support_view_posted_them(): void {
		$repo    = $this->repository();
		$service = new SettingsService( $repo );
		$service->update_tab(
			'general',
			array(
				'_support'        => '1',
				'restrict_prefix' => '1',
				'cron_interval'   => '2',
			)
		);
		self::assertTrue( $repo->get()->bool( 'restrict_prefix' ) );
		self::assertSame( 2, $repo->get()->int( 'cron_interval' ) );

		// A save from the ordinary view has none of them: nothing is switched off.
		$service->update_tab( 'general', array( 'new_tab' => '1' ) );
		self::assertTrue( $repo->get()->bool( 'restrict_prefix' ) );
		self::assertSame( 2, $repo->get()->int( 'cron_interval' ) );
	}

	public function test_keep_original_url_keeps_one_valid_domain_a_line(): void {
		$repo = $this->repository();
		( new SettingsService( $repo ) )->update_tab(
			'general',
			array(
				'_support'              => '1',
				'keep_original_domains' => "https://example.com/\nhttp://shop.example.org\nnodots\n\n  geni.us  \nexample.com/",
			)
		);

		self::assertSame( array( 'example.com/', 'shop.example.org', 'geni.us' ), $repo->get()->list( 'keep_original_domains' ) );
	}

	public function test_the_cron_interval_is_refused_outside_0_to_3_and_the_process_limit_is_clamped(): void {
		$repo    = $this->repository();
		$service = new SettingsService( $repo );

		$refused = $service->update_tab(
			'general',
			array(
				'_support'             => '1',
				'cron_interval'        => '4',
				'manual_process_limit' => '9',
			)
		);
		self::assertSame( array( 'cron_interval' ), $refused );
		self::assertSame( 0, $repo->get()->int( 'cron_interval' ) );
		self::assertSame( 3, $repo->get()->int( 'manual_process_limit' ) );

		$service->update_tab(
			'general',
			array(
				'_support'             => '1',
				'manual_process_limit' => '0',
			)
		);
		self::assertSame( 1, $repo->get()->int( 'manual_process_limit' ) );
	}
}
