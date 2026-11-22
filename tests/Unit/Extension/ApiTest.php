<?php
namespace Affibloom\Tests\Unit\Extension;

use Affibloom\Application\Shell\NoticeBoard;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductService;
use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Fields\FieldsService;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Extension\AmazonAccess;
use Affibloom\Extension\Api;
use Affibloom\Application\Groups\GroupService;
use Affibloom\Extension\FieldAccess;
use Affibloom\Extension\GroupAccess;
use Affibloom\Extension\DisplayAccess;
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
use Affibloom\Tests\Unit\Fakes\InMemoryDisplaySettingsRepository;
use Affibloom\Tests\Unit\Fakes\InMemoryFieldRepository;
use Affibloom\Tests\Unit\Fakes\InMemoryFieldUsage;
use Affibloom\Tests\Unit\Fakes\InMemoryGroupRepository;
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

	/** Display access on in-memory stores. */
	public static function display(): DisplayAccess {
		return new DisplayAccess( new DisplaySettingsService( new InMemoryDisplaySettingsRepository(), new InMemorySettingsRepository() ) );
	}

	/** Field access on in-memory stores, the product given published. */
	public static function fields( ?InMemoryProductRepository $products = null ): FieldAccess {
		$products ??= new InMemoryProductRepository();

		return new FieldAccess( new FieldsService( new InMemoryFieldRepository(), $products, new InMemoryFieldUsage( $products ) ) );
	}

	public function test_fields_are_created_renamed_and_a_product_s_rows_set_in_order(): void {
		Functions\when( 'esc_html' )->returnArg();
		$products = new InMemoryProductRepository();
		$product  = (int) $products->save( Product::draft( 'Shoe', AffiliateUrl::from_string( 'https://www.example.com/shoe' ), Settings::from_array( array() ) ) )->id;
		$fields   = self::fields( $products );

		$details = $fields->create( 'DETAILS', 'text', '' );
		$fields->rename( 1, 'Our Rating' );
		$fields->set_rows(
			$product,
			array(
				array(
					'field' => $details,
					'value' => 'Light',
				),
				array(
					'field'     => 1,
					'value'     => '4.5',
					'visible'   => false,
					'show_name' => true,
				),
			)
		);

		self::assertSame( 'Our Rating', $fields->definitions()[0]['name'] );
		self::assertSame( array( 1, $details ), array_column( $fields->rows( $product ), 'field' ), 'Primary Rating comes first, as Lasso fixes its place.' );
		self::assertFalse( $fields->rows( $product )[0]['visible'] );
		self::assertNull( $fields->rows( 999 ) );

		$this->expectException( \InvalidArgumentException::class );
		$fields->set_rows( $product, array( array( 'field' => 4242, 'value' => 'x' ) ) );
	}

	/** Group access on in-memory stores. */
	public static function groups( ?InMemoryProductRepository $products = null ): GroupAccess {
		$products ??= new InMemoryProductRepository();

		return new GroupAccess( new GroupService( new InMemoryGroupRepository(), $products ) );
	}

	public function test_a_group_is_created_and_its_products_set_in_order(): void {
		Functions\when( 'esc_html' )->returnArg();
		$products = new InMemoryProductRepository();
		$draft    = static fn ( string $name ): int => (int) $products->save( Product::draft( $name, AffiliateUrl::from_string( 'https://www.example.com/' . $name ), Settings::from_array( array() ) ) )->id;
		$a        = $draft( 'a' );
		$b        = $draft( 'b' );
		$c        = $draft( 'c' );
		$groups   = self::groups( $products );

		$id = $groups->create( 'Best shoes', 'Our picks' );
		$groups->set_members( $id, array( $b, $a ) );
		self::assertSame( array( $b, $a ), $groups->members( $id ) );

		$groups->set_members( $id, array( $c, $b ) );
		self::assertSame( array( $c, $b ), $groups->members( $id ), 'Added, taken out and ordered as given.' );
		self::assertSame( 'Best shoes', $groups->all()[0]['name'] );

		$this->expectException( \InvalidArgumentException::class );
		$groups->create( 'Best shoes', '' );
	}

	/** Amazon access on an in-memory store. */
	public static function amazon(): AmazonAccess {
		return new AmazonAccess( new InMemoryAmazonProducts() );
	}

	public function test_a_cached_amazon_product_is_imported_marked_imported(): void {
		Functions\when( 'esc_html' )->returnArg();
		$amazon = self::amazon();
		self::assertNull( $amazon->product( 'B0TEST0001', 'us' ) );

		$amazon->import(
			'B0TEST0001',
			'us',
			array(
				'name'         => 'Shoe',
				'price'        => '$89.99',
				'is_prime'     => true,
				'last_updated' => '2026-10-01 10:00:00',
			)
		);

		$stored = $amazon->product( 'B0TEST0001', 'us' );
		self::assertSame( 'Shoe', $stored['name'] );
		self::assertSame( 'imported', $stored['source'] );
		self::assertTrue( $stored['is_prime'] );

		$this->expectException( \InvalidArgumentException::class );
		$amazon->import( 'nope', 'us', array() );
	}

	public function test_a_display_type_is_saved_in_the_shape_it_is_read_and_a_bad_colour_is_refused(): void {
		$api = $this->api();

		$refused = $api->save_display(
			'grid',
			array(
				'theme'    => 'Cutter',
				'palette'  => array(
					'main'   => '#111111',
					'button' => 'not a colour!',
				),
				'elements' => array_fill_keys( array( 'badge', 'image', 'title', 'price', 'prime', 'desc', 'proscons', 'primary', 'secondary' ), true ) + array( 'timestamp' => false ),
				'layout'   => array( 'align' => 'left' ),
				'columns'  => 2,
			)
		);

		$grid = $api->display_settings()['grid'];
		self::assertSame( array( 'button' ), $refused );
		self::assertSame( 'Cutter', $grid['theme'] );
		self::assertSame( '#111111', $grid['palette']['main'] );
		self::assertFalse( $grid['elements']['timestamp'] );
		self::assertSame( 'left', $grid['layout']['align'] );
		self::assertSame( 2, $api->display_settings()['columns'] );
	}

	public function test_saved_settings_keep_the_switches_not_given_and_refuse_a_bad_value(): void {
		Functions\when( 'sanitize_text_field' )->alias( 'trim' );
		$store = new InMemorySettingsRepository( array( 'nofollow' => true, 'sponsored' => true ) );
		$api   = new Api( '1.2.3', $this->navigation, new ExtensionScreen( new Template( __DIR__, $this->navigation, new NoticeBoard( array(), static function (): void {} ) ) ), self::access(), self::settings( $store ), self::display(), self::fields(), self::groups(), self::amazon() );

		self::assertSame( array(), $api->save_settings( 'general', array( 'sponsored' => false ) ) );
		self::assertTrue( $api->settings()['nofollow'], 'A switch not given keeps its value.' );
		self::assertFalse( $api->settings()['sponsored'] );

		self::assertSame( array( 'tracking_id' ), $api->save_settings( 'amazon', array( 'tracking_id' => 'not a tag!' ) ) );
		self::assertSame( array(), $api->save_settings( 'import', array( 'tracking_id_blacklist' => array( 'old-20' ) ) ) );
		self::assertSame( array( 'old-20' ), $api->settings()['tracking_id_blacklist'] );
	}

	private function api(): Api {
		$template = new Template( __DIR__, $this->navigation, new NoticeBoard( array(), static function (): void {} ) );

		return new Api( '1.2.3', $this->navigation, new ExtensionScreen( $template ), self::access(), self::settings(), self::display(), self::fields(), self::groups(), self::amazon() );
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
		$api      = new Api( '1.2.3', $this->navigation, $pages, self::access(), self::settings(), self::display(), self::fields(), self::groups(), self::amazon() );

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
