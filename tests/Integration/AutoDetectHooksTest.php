<?php
namespace Affibloom\Tests\Integration;

use Affibloom\Application\Monetize\AffiliateDetector;
use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Domain\Monetize\NetworkRegistry;
use Affibloom\Infrastructure\Schema\Table;
use Affibloom\Infrastructure\WordPress\OptionSettingsRepository;
use Affibloom\Infrastructure\WordPress\TableDetectedLinkRepository;
use Affibloom\Interface\Hooks\AutoDetectHooks;
use WP_UnitTestCase;

/**
 * Detection when a post is indexed (F35 R10-R12, D3, D23; C2 AC2, AC5, AC7; Task C2B). Runs only where wp-env is
 * available (progress.md, deviation 10).
 */
final class AutoDetectHooksTest extends WP_UnitTestCase {

	private AutoDetectHooks $hooks;

	private OptionSettingsRepository $settings;

	protected function setUp(): void {
		parent::setUp();
		( new Table( TableDetectedLinkRepository::TABLE, TableDetectedLinkRepository::COLUMNS ) )->create();
		$this->settings = new OptionSettingsRepository();
		$this->hooks    = new AutoDetectHooks(
			new AffiliateDetector( NetworkRegistry::from_array( require dirname( __DIR__, 2 ) . '/resources/networks.php' ), new TableDetectedLinkRepository() ),
			$this->settings
		);
		wp_clear_scheduled_hook( AutoDetectHooks::EVENT );
	}

	private function auto_detect( bool $on ): void {
		$this->settings->save( $this->settings->get()->with( array( 'auto_detect_links' => $on ) ) );
	}

	private static function rows(): array {
		$url = 'https://mizunousa.sjv.io/jR4ZQP';
		return array( new LinkLocation( 1, 'https://site.example/p/', 'content', 1, 0, 'external', 'Text', $url, md5( $url ) ) );
	}

	private static function recorded(): int {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $wpdb->prefix . TableDetectedLinkRepository::TABLE ) );
	}

	public function test_a_network_link_is_recorded_once_and_the_run_scheduled(): void {
		$this->auto_detect( true );

		$this->hooks->indexed( 1, self::rows() );
		$this->hooks->indexed( 1, self::rows() );

		self::assertSame( 1, self::recorded() );
		self::assertNotFalse( wp_next_scheduled( AutoDetectHooks::EVENT ) );
	}

	public function test_with_the_setting_off_nothing_is_recorded_and_switching_off_clears_the_run(): void {
		$this->auto_detect( true );
		$this->hooks->indexed( 1, self::rows() );
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $wpdb->prefix . TableDetectedLinkRepository::TABLE ) );

		$this->auto_detect( false );
		$this->hooks->switched( array( 'auto_detect_links' => true ), array( 'auto_detect_links' => false ) );
		$this->hooks->indexed( 1, self::rows() );

		self::assertSame( 0, self::recorded() );
		self::assertFalse( wp_next_scheduled( AutoDetectHooks::EVENT ) );
	}
}
