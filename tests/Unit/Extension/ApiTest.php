<?php
namespace Affibloom\Tests\Unit\Extension;

use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Domain\LinkIndex\LocationKey;
use Affibloom\Extension\OpportunityAccess;
use Affibloom\Tests\Unit\Fakes\InMemoryLinkLocations;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Domain\Table\TablePage;
use Affibloom\Domain\Table\Table;
use Affibloom\Domain\Table\TableRepository;
use Affibloom\Extension\TableAccess;
use Affibloom\Application\LinkHealth\AlertDismissals;
use Affibloom\Extension\IssueAccess;
use Affibloom\Tests\Unit\Fakes\InMemoryLinkIssues;
use Affibloom\Application\Variants\VariantService;
use Affibloom\Domain\Variant\SlugTaken;
use Affibloom\Extension\VariantAccess;
use Affibloom\Tests\Unit\Fakes\InMemoryVariantRepository;
use Affibloom\Application\Dashboard\SavedViewService;
use Affibloom\Application\Shell\TourState;
use Affibloom\Domain\Dashboard\SavedViewRepository;
use Affibloom\Domain\Dashboard\SavedViews;
use Affibloom\Extension\ViewAccess;
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

	/** Opportunity access on an in-memory link index. */
	public static function opportunities( ?InMemoryLinkLocations $locations = null ): OpportunityAccess {
		return new OpportunityAccess( $locations ?? new InMemoryLinkLocations() );
	}

	public function test_a_post_s_link_is_dismissed_by_its_url(): void {
		$locations = new InMemoryLinkLocations();
		$url       = 'https://www.amazon.com/dp/B0TEST0001';
		$locations->replace_host( 7, array( new LinkLocation( 7, 'https://site.example/p/', 'content', 1, 0, 'external', 'Text', $url, LocationKey::md5( $url ) ) ) );
		$access = self::opportunities( $locations );

		self::assertFalse( $access->dismissed( 7, $url ) );
		self::assertNull( $access->dismissed( 7, 'https://other.example/' ) );
		self::assertSame( 1, $access->dismiss( 7, $url . '/' ) );
		self::assertSame( array( 1 => true ), $locations->ignored );
		self::assertSame( 0, $access->dismiss( 8, $url ) );
	}

	/** Table access on an in-memory store; fields up to 99999 exist. */
	public static function tables( ?InMemoryProductRepository $products = null ): TableAccess {
		$store = new class() implements TableRepository {
			/** @var array<int, Table> */
			public array $tables = array();
			public function get( int $id ): ?Table {
				return $this->tables[ $id ] ?? null;
			}
			public function save( Table $table ): Table {
				$saved                       = $table->with( array( 'id' => $table->id ?? count( $this->tables ) + 1 ) );
				$this->tables[ $saved->id ] = $saved;

				return $saved;
			}
			public function page( SearchTerm $term, int $page, int $per_page, bool $newest = false ): TablePage {
				throw new \LogicException( 'Not used.' );
			}
			public function count(): int {
				return count( $this->tables );
			}
			public function name_taken( string $name ): bool {
				return false;
			}
			public function delete( int $id ): void {
				unset( $this->tables[ $id ] );
			}
			public function product_ids(): array {
				return array();
			}
			public function containing( int $product ): array {
				return array();
			}
		};

		return new TableAccess( $store, static fn ( int $field ): bool => $field <= 99999, $products ?? new InMemoryProductRepository() );
	}

	public function test_a_table_is_stored_whole_and_read_back_and_a_missing_product_is_refused(): void {
		Functions\when( 'esc_html' )->returnArg();
		$products = new InMemoryProductRepository();
		$id       = (int) $products->save( Product::draft( 'Ball', AffiliateUrl::from_string( 'https://shop.example/ball' ), Settings::from_array( array() ) ) )->id;
		$tables   = self::tables( $products );
		$table    = array(
			'name'            => 'Best Volleyball',
			'style'           => 'row',
			'theme'           => 'Cactus',
			'show_headers'    => true,
			'show_field_name' => false,
			'products'        => array(
				array(
					'id'    => $id,
					'badge' => 'Best Overall',
				),
			),
			'groups'          => array(
				array(
					'id'     => 'group_1',
					'fields' => array( 99991, 99990 ),
				),
			),
			'values'          => array( $id => array( 99990 => 'Ball' ) ),
		);

		$table_id = $tables->put( 0, $table );

		self::assertSame( $table, $tables->get( $table_id ) );
		$this->expectException( \InvalidArgumentException::class );
		$tables->put( 0, array( 'products' => array( array( 'id' => 999 ) ) ) + $table );
	}

	/** Issue access on in-memory rows and dismissals. */
	public static function issues( ?InMemoryProductRepository $products = null ): IssueAccess {
		$dismissals = new class() implements AlertDismissals {
			/** @var array<string, int> */
			public array $until = array();
			public function until( int $product_id, string $bar ): int {
				return $this->until[ "$product_id:$bar" ] ?? 0;
			}
			public function close( int $product_id, string $bar, int $until ): void {
				$this->until[ "$product_id:$bar" ] = $until;
			}
		};

		return new IssueAccess( new InMemoryLinkIssues(), $dismissals, $products ?? new InMemoryProductRepository() );
	}

	public function test_a_link_issue_and_a_dismissal_are_stored_as_given(): void {
		Functions\when( 'esc_html' )->returnArg();
		$products = new InMemoryProductRepository();
		$id       = (int) $products->save( Product::draft( 'Ball', AffiliateUrl::from_string( 'https://shop.example/ball' ), Settings::from_array( array() ) ) )->id;
		$issues   = self::issues( $products );
		$row      = array(
			'issue_type'      => '000',
			'checked_url'     => 'https://shop.example/ball',
			'detection_date'  => '2026-10-01 08:00:00',
			'resolved'        => false,
			'resolved_at'     => '',
			'last_checked_at' => '2026-11-01 09:00:00',
		);

		$issues->import( $id, $row );
		$issues->dismiss( $id, 'out-of-stock', 1900000000 );

		self::assertSame( $row, $issues->get( $id ) );
		self::assertSame( 1900000000, $issues->dismissed_until( $id, 'out-of-stock' ) );
		self::assertSame( 0, $issues->dismissed_until( $id, 'broken' ) );
		$this->expectException( \InvalidArgumentException::class );
		$issues->import( 999, $row );
	}

	/** Variant access on in-memory variants, nothing else answering at a key. */
	public static function variants( ?InMemoryProductRepository $products = null ): VariantAccess {
		$taken = new class() implements SlugTaken {
			public function by_other_variant( string $key, int $product_id ): bool {
				return false;
			}
			public function by_post( string $key ): bool {
				return 'about' === $key;
			}
			public function by_other_product( string $key, int $product_id ): bool {
				return false;
			}
		};

		return new VariantAccess( new VariantService( new InMemoryVariantRepository(), $taken ), $products ?? new InMemoryProductRepository() );
	}

	public function test_a_product_s_variants_are_set_and_read_and_a_key_a_post_has_is_refused(): void {
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'sanitize_title' )->alias( 'strtolower' );
		Functions\when( 'esc_html' )->returnArg();
		$products = new InMemoryProductRepository();
		$id       = (int) $products->save( Product::draft( 'Ball', AffiliateUrl::from_string( 'https://shop.example/ball' ), Settings::from_array( array() ) ) )->id;
		$variants = self::variants( $products );

		$variants->set( $id, array( array( 'name' => 'Blue', 'key' => 'Ball-Blue' ) ) );

		self::assertSame( array( array( 'name' => 'Blue', 'key' => 'ball-blue', 'kind' => 'permalink' ) ), $variants->all( $id ) );
		self::assertNull( $variants->all( 999 ) );
		$this->expectException( \InvalidArgumentException::class );
		$variants->set( $id, array( array( 'name' => 'About', 'key' => 'about' ) ) );
	}

	/** View access on in-memory views and tour flag. */
	public static function views(): ViewAccess {
		$repository = new class() implements SavedViewRepository {
			/** @var array<int, SavedViews> */
			public array $stored = array();

			public function for_user( int $user ): SavedViews {
				return $this->stored[ $user ] ?? new SavedViews();
			}

			public function save( int $user, SavedViews $views ): void {
				$this->stored[ $user ] = $views;
			}
		};
		$tour       = new class() implements TourState {
			public bool $done = false;

			public function done(): bool {
				return $this->done;
			}

			public function finish(): void {
				$this->done = true;
			}
		};

		return new ViewAccess( new SavedViewService( $repository ), $tour );
	}

	public function test_a_view_is_saved_in_the_shape_it_is_read_an_unknown_filter_falls_back_and_the_tour_ends(): void {
		Functions\when( 'esc_html' )->returnArg();
		$api  = $this->api();
		$view = array(
			'id'      => 'v1',
			'name'    => 'Amazon shoes',
			'filters' => array(
				's'                => 'shoes',
				'link_type'        => 'amazon',
				'per_page'         => 25,
				'group'            => 12,
				'filter'           => 'no-such-tab',
				'placements'       => '',
				'orderby'          => '',
				'order'            => '',
				'comparison_table' => '',
			),
		);

		$api->save_view( 3, $view );

		$stored = $api->views( 3 );
		self::assertCount( 1, $stored );
		self::assertSame( 'shoes', $stored[0]['filters']['s'] );
		self::assertSame( 'amazon', $stored[0]['filters']['link_type'] );
		self::assertSame( 12, $stored[0]['filters']['group'] );
		self::assertSame( '', $stored[0]['filters']['filter'] );
		self::assertSame( array(), $api->views( 4 ) );

		self::assertFalse( $api->tour_done() );
		$api->finish_tour();
		self::assertTrue( $api->tour_done() );

		$this->expectException( \InvalidArgumentException::class );
		$api->save_view( 3, array( 'id' => 'v2', 'name' => '' ) );
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
		$api   = new Api( '1.2.3', $this->navigation, new ExtensionScreen( new Template( __DIR__, $this->navigation, new NoticeBoard( array(), static function (): void {} ) ) ), self::access(), self::settings( $store ), self::display(), self::fields(), self::groups(), self::amazon(), self::views(), self::variants(), self::issues(), self::tables(), self::opportunities() );

		self::assertSame( array(), $api->save_settings( 'general', array( 'sponsored' => false ) ) );
		self::assertTrue( $api->settings()['nofollow'], 'A switch not given keeps its value.' );
		self::assertFalse( $api->settings()['sponsored'] );

		self::assertSame( array( 'tracking_id' ), $api->save_settings( 'amazon', array( 'tracking_id' => 'not a tag!' ) ) );
		self::assertSame( array(), $api->save_settings( 'import', array( 'tracking_id_blacklist' => array( 'old-20' ) ) ) );
		self::assertSame( array( 'old-20' ), $api->settings()['tracking_id_blacklist'] );
		self::assertSame( array( 'education_hidden' ), $api->save_settings( 'import', array( 'education_hidden' => array( 'links', 'keywords' ) ) ) );
		self::assertSame( array(), $api->save_settings( 'import', array( 'education_hidden' => array( 'links', 'content' ) ) ) );
		self::assertSame( array( 'content', 'links' ), $api->settings()['education_hidden'] );
	}

	private function api(): Api {
		$template = new Template( __DIR__, $this->navigation, new NoticeBoard( array(), static function (): void {} ) );

		return new Api( '1.2.3', $this->navigation, new ExtensionScreen( $template ), self::access(), self::settings(), self::display(), self::fields(), self::groups(), self::amazon(), self::views(), self::variants(), self::issues(), self::tables(), self::opportunities() );
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
		$api      = new Api( '1.2.3', $this->navigation, $pages, self::access(), self::settings(), self::display(), self::fields(), self::groups(), self::amazon(), self::views(), self::variants(), self::issues(), self::tables(), self::opportunities() );

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
