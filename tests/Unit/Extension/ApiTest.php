<?php
namespace Affibloom\Tests\Unit\Extension;

use Affibloom\Application\Shell\NoticeBoard;
use Affibloom\Extension\Api;
use Affibloom\Extension\ReadyAction;
use Affibloom\Interface\Admin\AdminNavigation;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\ExtensionScreen;
use Affibloom\Interface\Admin\NavItem;
use Affibloom\Interface\Template;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Actions;

/**
 * Task LM1: a helper plugin gets the Api once, on plugins_loaded, with Affibloom's version, and can add one page to
 * Affibloom's menu, where Lasso lists Import, for those who may change settings.
 */
final class ApiTest extends TestCase {

	private AdminNavigation $navigation;

	protected function setUp(): void {
		parent::setUp();
		$this->navigation = new AdminNavigation();
	}

	private function api(): Api {
		$template = new Template( __DIR__, $this->navigation, new NoticeBoard( array(), static function (): void {} ) );

		return new Api( '1.2.3', $this->navigation, new ExtensionScreen( $template ) );
	}

	public function test_affibloom_ready_fires_once_on_plugins_loaded_with_the_api(): void {
		$api   = $this->api();
		$ready = new ReadyAction( $api );
		$ready->register();
		self::assertNotFalse( has_action( 'plugins_loaded', array( $ready, 'fire' ) ) );

		Actions\expectDone( 'affibloom_ready' )->once()->with( $api );
		$ready->fire();
	}

	public function test_the_api_tells_affibloom_s_version(): void {
		self::assertSame( '1.2.3', $this->api()->version() );
	}

	public function test_an_added_page_is_listed_before_settings_for_those_who_may_change_settings(): void {
		$this->navigation->register( new NavItem( 'settings', 'affibloom-settings', static fn (): string => 'Settings', 'settings', Capabilities::SETTINGS ) );
		$this->api()->add_page( 'affibloom-helper', static fn (): string => 'Helper', static fn (): string => 'What it does.', static function (): void {} );

		self::assertSame( array( 'affibloom-helper', 'affibloom-settings' ), array_map( static fn ( NavItem $item ): string => $item->slug, $this->navigation->menu() ) );
		$item = $this->navigation->current( 'affibloom-helper' );
		self::assertNotNull( $item );
		self::assertSame( 'Helper', $item->label() );
		self::assertSame( Capabilities::SETTINGS, $item->capability );
		self::assertSame( array( 'affibloom-settings' ), array_map( static fn ( NavItem $item ): string => $item->slug, $this->navigation->tabs() ), 'An added page is a menu item, not a header tab.' );
	}
}
