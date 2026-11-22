<?php
namespace Affibloom\Tests\Unit\Extension;

use Affibloom\Application\Shell\NoticeBoard;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductService;
use Affibloom\Extension\Api;
use Affibloom\Application\Settings\SettingsService;
use Affibloom\Extension\ProductAccess;
use Affibloom\Extension\SettingsAccess;
use Affibloom\Extension\ReadyAction;
use Affibloom\Interface\Admin\AdminNavigation;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\ExtensionScreen;
use Affibloom\Interface\Admin\NavItem;
use Affibloom\Interface\Template;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemoryAmazonProducts;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;
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

	/** Product access on Affibloom's in-memory fakes. */
	public static function access(): ProductAccess {
		$resolver = new LinkResolver( new FakePageFetcher() );
		$settings = new InMemorySettingsRepository();
		$links    = new LinkNormaliser( $resolver, $settings );
		$products = new InMemoryProductRepository();

		return new ProductAccess( new ProductService( $products, $resolver, $settings, new ProductInput( $links ), $links, InMemoryAmazonProducts::without_amazon() ), $products, static function (): void {} );
	}

	/** Settings access on an in-memory store. */
	public static function settings( ?InMemorySettingsRepository $store = null ): SettingsAccess {
		return new SettingsAccess( new SettingsService( $store ?? new InMemorySettingsRepository() ) );
	}

	public function test_saved_settings_keep_the_switches_not_given_and_refuse_a_bad_value(): void {
		Functions\when( 'sanitize_text_field' )->alias( 'trim' );
		$store = new InMemorySettingsRepository( array( 'nofollow' => true, 'sponsored' => true ) );
		$api   = new Api( '1.2.3', $this->navigation, new ExtensionScreen( new Template( __DIR__, $this->navigation, new NoticeBoard( array(), static function (): void {} ) ) ), self::access(), self::settings( $store ) );

		self::assertSame( array(), $api->save_settings( 'general', array( 'sponsored' => false ) ) );
		self::assertTrue( $api->settings()['nofollow'], 'A switch not given keeps its value.' );
		self::assertFalse( $api->settings()['sponsored'] );

		self::assertSame( array( 'tracking_id' ), $api->save_settings( 'amazon', array( 'tracking_id' => 'not a tag!' ) ) );
		self::assertSame( array(), $api->save_settings( 'import', array( 'tracking_id_blacklist' => array( 'old-20' ) ) ) );
		self::assertSame( array( 'old-20' ), $api->settings()['tracking_id_blacklist'] );
	}

	private function api(): Api {
		$template = new Template( __DIR__, $this->navigation, new NoticeBoard( array(), static function (): void {} ) );

		return new Api( '1.2.3', $this->navigation, new ExtensionScreen( $template ), self::access(), self::settings() );
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

	public function test_an_unlisted_page_is_registered_but_hidden_and_its_capability_is_the_settings_one(): void {
		$template = new Template( __DIR__, $this->navigation, new NoticeBoard( array(), static function (): void {} ) );
		$pages    = new ExtensionScreen( $template );
		$api      = new Api( '1.2.3', $this->navigation, $pages, self::access(), self::settings() );

		$api->add_page( 'affibloom-helper', static fn (): string => 'Helper', static fn (): string => '', static function (): void {}, false );

		self::assertSame( array( 'affibloom-helper' ), $pages->unlisted() );
		self::assertNotNull( $this->navigation->current( 'affibloom-helper' ) );
		self::assertSame( Capabilities::SETTINGS, $api->capability() );
	}

	public function test_check_product_answers_with_affibloom_s_save_rules(): void {
		Functions\when( 'sanitize_text_field' )->alias( 'trim' );
		self::assertSame( array( 'name' => 'required' ), $this->api()->check_product( array( 'name' => '' ) ) );
		self::assertSame( array(), $this->api()->check_product( array( 'name' => 'Shoe' ) ) );
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
