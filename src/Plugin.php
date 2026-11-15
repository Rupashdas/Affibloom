<?php
/**
 * Composition root: builds the plugin's objects and hands them to WordPress.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom;

use Affibloom\Application\Amazon\AmazonDataService;
use Affibloom\Application\Amazon\BulkSyncService;
use Affibloom\Application\Amazon\CredentialsService;
use Affibloom\Application\Dashboard\SavedViewService;
use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Fields\FieldsService;
use Affibloom\Application\Groups\GroupService;
use Affibloom\Application\Icons\IconService;
use Affibloom\Application\Lifecycle;
use Affibloom\Application\Migration\LassoImportService;
use Affibloom\Application\Products\BulkAddService;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductService;
use Affibloom\Application\Settings\LinkDefaultsService;
use Affibloom\Application\Settings\SettingsService;
use Affibloom\Application\Shell\NoticeBoard;
use Affibloom\Application\Shell\UpdateAvailableNotice;
use Affibloom\Application\Upgrade\Upgrader;
use Affibloom\Infrastructure\Http\CreatorsApiCatalog;
use Affibloom\Infrastructure\Http\WpPageFetcher;
use Affibloom\Infrastructure\Lasso\WpLassoSource;
use Affibloom\Infrastructure\Schema\Migrator;
use Affibloom\Infrastructure\Schema\Table;
use Affibloom\Infrastructure\WordPress\CronRegistry;
use Affibloom\Infrastructure\WordPress\GroupTaxonomy;
use Affibloom\Infrastructure\WordPress\OptionBulkRunRepository;
use Affibloom\Infrastructure\WordPress\OptionBulkSyncRunRepository;
use Affibloom\Infrastructure\WordPress\OptionCredentialsRepository;
use Affibloom\Infrastructure\WordPress\OptionDisplaySettingsRepository;
use Affibloom\Infrastructure\WordPress\OptionFieldRepository;
use Affibloom\Infrastructure\WordPress\PostMetaFieldUsage;
use Affibloom\Infrastructure\WordPress\OptionIconRepository;
use Affibloom\Infrastructure\WordPress\OptionLock;
use Affibloom\Infrastructure\WordPress\OptionPluginState;
use Affibloom\Infrastructure\WordPress\OptionSettingsRepository;
use Affibloom\Infrastructure\WordPress\PluginUpdates;
use Affibloom\Infrastructure\WordPress\PostMetaLassoImportLog;
use Affibloom\Infrastructure\WordPress\PostMetaPendingAmazonSync;
use Affibloom\Infrastructure\WordPress\PostTypeProductRepository;
use Affibloom\Infrastructure\WordPress\ProductPostType;
use Affibloom\Infrastructure\WordPress\SiteCssIcons;
use Affibloom\Infrastructure\WordPress\TableAmazonProductRepository;
use Affibloom\Infrastructure\WordPress\TermGroupRepository;
use Affibloom\Infrastructure\WordPress\UserMetaBannerDismissals;
use Affibloom\Infrastructure\WordPress\UserMetaSavedViewRepository;
use Affibloom\Infrastructure\WordPress\WpDetectionChoices;
use Affibloom\Infrastructure\WordPress\WpCronBackgroundJob;
use Affibloom\Interface\Cli\AmazonCommand;
use Affibloom\Interface\Cli\UpgradeCommand;
use Affibloom\Interface\Cron\AmazonBulkSyncCron;
use Affibloom\Interface\Cron\BulkAddCron;
use Affibloom\Interface\Admin\AdminAssets;
use Affibloom\Interface\Admin\AdminMenu;
use Affibloom\Interface\Admin\AdminNavigation;
use Affibloom\Interface\Admin\AmazonNotices;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\DashboardScreen;
use Affibloom\Interface\Admin\FieldScreen;
use Affibloom\Interface\Admin\FieldsScreen;
use Affibloom\Interface\Admin\ForeignNotices;
use Affibloom\Interface\Admin\GroupScreen;
use Affibloom\Interface\Admin\GroupsScreen;
use Affibloom\Interface\Admin\PerPage;
use Affibloom\Interface\Admin\ProductScreen;
use Affibloom\Interface\Admin\DisplayController;
use Affibloom\Interface\Admin\DisplayScreen;
use Affibloom\Interface\Admin\HelpTips;
use Affibloom\Interface\Admin\IconChoices;
use Affibloom\Interface\Admin\IconController;
use Affibloom\Interface\Admin\LassoImportController;
use Affibloom\Interface\Admin\NavItem;
use Affibloom\Interface\Admin\Notice;
use Affibloom\Interface\Admin\SettingsController;
use Affibloom\Interface\Admin\SettingsScreen;
use Affibloom\Interface\Admin\TrackingIdController;
use Affibloom\Interface\Rest\AmazonController;
use Affibloom\Interface\Rest\BulkAddController;
use Affibloom\Interface\Rest\DashboardController;
use Affibloom\Interface\Rest\FieldsController;
use Affibloom\Interface\Rest\GroupsController;
use Affibloom\Interface\Rest\ProductFieldsController;
use Affibloom\Interface\Rest\ProductResource;
use Affibloom\Interface\Rest\ProductsController;
use Affibloom\Interface\Rest\SettingsRestController;
use Affibloom\Interface\IconStyles;
use Affibloom\Interface\Template;
use Affibloom\Interface\UpgradeHooks;
use Affibloom\Module;
use Throwable;

/**
 * Starts the plugin once WordPress has loaded it.
 */
final class Plugin {

	/**
	 * Absolute path of affibloom.php.
	 *
	 * @var string
	 */
	private string $file;

	/**
	 * Shared template renderer, built on first use.
	 *
	 * @var Template|null
	 */
	private ?Template $template = null;

	/**
	 * The screens in Lasso's order, shared by the menu and the header; built on first use.
	 *
	 * @var AdminNavigation|null
	 */
	private ?AdminNavigation $navigation = null;

	/**
	 * The notices for the region under the header, shared by every screen; built on first use.
	 *
	 * @var NoticeBoard|null
	 */
	private ?NoticeBoard $notice_board = null;

	/**
	 * The upgrade's hooks: a module, and a notice provider when a step failed; built on first use.
	 *
	 * @var UpgradeHooks|null
	 */
	private ?UpgradeHooks $upgrade_hooks = null;

	/**
	 * Bulk Amazon sync: a module's and the notice board's (its banner); built on first use.
	 *
	 * @var BulkSyncService|null
	 */
	private ?BulkSyncService $bulk_sync = null;

	/**
	 * Amazon's data on products, shared by the add flow, the product page and bulk sync; built on first use.
	 *
	 * @var AmazonDataService|null
	 */
	private ?AmazonDataService $amazon_data = null;

	/**
	 * Settings storage, shared by every reader in a request.
	 *
	 * @var OptionSettingsRepository|null
	 */
	private ?OptionSettingsRepository $settings_repository = null;

	/**
	 * Use Plugin::boot().
	 *
	 * @param string $file Absolute path of affibloom.php.
	 */
	private function __construct( string $file ) {
		$this->file = $file;
	}

	/**
	 * Entry point, called once from affibloom.php.
	 *
	 * @param string $file Absolute path of affibloom.php.
	 */
	public static function boot( string $file ): void {
		( new self( $file ) )->register();
	}

	/**
	 * Activation hook (register_activation_hook in affibloom.php).
	 */
	public static function activate(): void {
		self::lifecycle( self::upgrader() )->activate();
	}

	/**
	 * Deactivation hook (register_deactivation_hook in affibloom.php).
	 */
	public static function deactivate(): void {
		self::lifecycle( self::upgrader() )->deactivate();
	}

	/**
	 * What activation, deactivation and a version change do. It needs none of the request's other modules, so the
	 * activation hooks build it on its own.
	 *
	 * @param Upgrader $upgrader The pending steps.
	 */
	private static function lifecycle( Upgrader $upgrader ): Lifecycle {
		return new Lifecycle( self::cron_registry(), new OptionPluginState(), $upgrader, AFFIBLOOM_VERSION );
	}

	/**
	 * The upgrade routine: Lasso's five-minute lock, and the numbered steps.
	 */
	private static function upgrader(): Upgrader {
		return new Upgrader( new OptionLock( 'affibloom_upgrade_lock', 5 * 60 ), new Migrator( self::schema_steps() ) );
	}

	/**
	 * The schema and data steps by number. Append a step with the next number; never renumber or remove one, because
	 * affibloom_db_version records the last number a site finished.
	 *
	 * @return array<int, callable(): void>
	 */
	private static function schema_steps(): array {
		return array(
			// Task 13G: the Dashboard's search reads added buttons' links from their own rows; products stored before
			// then get them once.
			1 => static function (): void {
				( new PostTypeProductRepository() )->index_all_button_urls();
			},
			// Task C1C: Amazon's data per ASIN and store (C1 D5).
			2 => static function (): void {
				( new Table( TableAmazonProductRepository::TABLE, TableAmazonProductRepository::COLUMNS ) )->create();
			},
			// Task C1Q3: the price and the list price as numbers. dbDelta adds the columns to step 2's table (a new
			// install already has them from step 2, and this changes nothing there).
			3 => static function (): void {
				( new Table( TableAmazonProductRepository::TABLE, TableAmazonProductRepository::COLUMNS ) )->create();
			},
		);
	}

	/**
	 * Every scheduled hook: each cron module's HOOKS. A new cron module adds its list here.
	 */
	private static function cron_registry(): CronRegistry {
		return new CronRegistry( BulkAddCron::HOOKS, AmazonBulkSyncCron::HOOKS );
	}

	/**
	 * Amazon's data on products (C1C, C1D, C1I).
	 */
	private function amazon_data(): AmazonDataService {
		$this->amazon_data ??= new AmazonDataService( new OptionCredentialsRepository(), new CreatorsApiCatalog(), new TableAmazonProductRepository(), new PostTypeProductRepository() );
		return $this->amazon_data;
	}

	/**
	 * Bulk Amazon sync (C1I): its job runs as the cron module's event; its links are followed as the add flow follows them.
	 */
	private function bulk_sync(): BulkSyncService {
		$this->bulk_sync ??= new BulkSyncService(
			new PostMetaPendingAmazonSync(),
			new OptionBulkSyncRunRepository(),
			new UserMetaBannerDismissals(),
			new WpCronBackgroundJob( AmazonBulkSyncCron::HOOK ),
			$this->amazon_data(),
			new PostTypeProductRepository(),
			new LinkNormaliser( new LinkResolver( new WpPageFetcher() ), $this->settings_repository() ),
			$this->settings_repository()
		);
		return $this->bulk_sync;
	}

	/**
	 * Hooks the plugin into WordPress.
	 */
	private function register(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		foreach ( $this->modules() as $module ) {
			$module->register();
		}
	}

	/**
	 * Everything that hooks into WordPress, with its collaborators.
	 *
	 * @return list<Module>
	 */
	private function modules(): array {
		$detection = new WpDetectionChoices();
		$settings  = new SettingsService( $this->settings_repository(), $detection );
		$display   = new DisplaySettingsService( new OptionDisplaySettingsRepository(), $this->settings_repository() );
		$notice    = new Notice();
		$resolver  = new LinkResolver( new WpPageFetcher() );
		$links     = new LinkNormaliser( $resolver, $this->settings_repository() );
		$catalog   = new CreatorsApiCatalog();
		$data      = $this->amazon_data();
		$products  = new ProductService( new PostTypeProductRepository(), $resolver, $this->settings_repository(), new ProductInput( $links ), $links, $data );
		$bulk      = new BulkAddService( $products, new OptionBulkRunRepository(), new WpCronBackgroundJob( BulkAddCron::HOOK ) );
		$per_page  = new PerPage();
		$views     = new SavedViewService( new UserMetaSavedViewRepository() );
		$icons     = new IconService( new OptionIconRepository(), new SiteCssIcons() );
		$choices   = new IconChoices( $icons );
		$fields    = new FieldsService( new OptionFieldRepository(), new PostTypeProductRepository(), new PostMetaFieldUsage() );
		$import    = new LassoImportService( new WpLassoSource(), new PostMetaLassoImportLog(), new PostTypeProductRepository() );
		$groups    = new GroupService( new TermGroupRepository(), new PostTypeProductRepository() );
		$amazon    = new CredentialsService( new OptionCredentialsRepository(), $catalog, $this->settings_repository() );
		$upgrader  = self::upgrader();

		return array(
			new ProductPostType(),
			new GroupTaxonomy(),
			new AdminMenu(
				$this->navigation(),
				new DashboardScreen( $this->template(), $products, $per_page, $views, $notice, $import, $groups ),
				new ProductScreen( $this->template(), $products, $display, $choices, $settings, $fields, $notice, $groups, $data ),
				new SettingsScreen(
					$this->template(),
					$settings,
					new DisplayScreen( $this->template(), $display, $settings, $notice, $choices ),
					$notice,
					$detection,
					$amazon
				),
				new GroupsScreen( $this->template(), $groups, $notice ),
				new GroupScreen( $this->template(), $groups, $notice ),
				new FieldsScreen( $this->template(), $fields, $notice ),
				new FieldScreen( $this->template(), $fields, $notice )
			),
			new AdminAssets( plugin_dir_url( $this->file ), AFFIBLOOM_VERSION ),
			new SettingsController( $settings, $amazon ),
			new AmazonController( $amazon, $data, $this->bulk_sync() ),
			new SettingsRestController( new LinkDefaultsService( new PostTypeProductRepository() ) ),
			new HelpTips( $settings ),
			new DisplayController( $display, $settings ),
			new ProductsController( $products, new ProductResource(), $groups ),
			new BulkAddController( $bulk ),
			new DashboardController( $products, $per_page, $views, $this->template(), $groups ),
			new BulkAddCron( $bulk ),
			new AmazonBulkSyncCron( $this->bulk_sync() ),
			new IconController( $icons ),
			new TrackingIdController( $settings ),
			new LassoImportController( $import ),
			new FieldsController( $fields, $this->template() ),
			new GroupsController( $groups, $this->template() ),
			new ProductFieldsController( $fields, $this->template() ),
			new IconStyles( $icons ),
			$this->upgrade_hooks(),
			new UpgradeCommand( $upgrader ),
			new AmazonCommand( $amazon, $catalog ),
			new ForeignNotices( $this->navigation() ),
		);
	}

	/**
	 * The settings repository.
	 */
	private function settings_repository(): OptionSettingsRepository {
		$this->settings_repository ??= new OptionSettingsRepository();
		return $this->settings_repository;
	}

	/**
	 * The template renderer for templates/.
	 */
	private function template(): Template {
		$this->template ??= new Template( dirname( $this->file ) . '/templates', $this->navigation(), $this->notice_board() );
		return $this->template;
	}

	/**
	 * The notices for the region under the header (F38 D8, D10), in this order: the update notice, a failed upgrade,
	 * then C1's (the bulk Amazon sync banner). F11, F21 and F33 add their providers here. A provider that fails is
	 * reported to the debug log.
	 */
	private function notice_board(): NoticeBoard {
		$this->notice_board ??= new NoticeBoard(
			array(
				new UpdateAvailableNotice( new PluginUpdates( plugin_basename( $this->file ) ) ),
				$this->upgrade_hooks(),
				new AmazonNotices( $this->bulk_sync() ),
			),
			static function ( Throwable $error ): void {
				wp_trigger_error( '', 'Affibloom skipped a notice: ' . $error->getMessage(), E_USER_WARNING );
			}
		);
		return $this->notice_board;
	}

	/**
	 * The upgrade's hooks, one instance: WordPress runs them, and the notice board reads their failure.
	 */
	private function upgrade_hooks(): UpgradeHooks {
		if ( null === $this->upgrade_hooks ) {
			$upgrader            = self::upgrader();
			$this->upgrade_hooks = new UpgradeHooks( $upgrader, self::lifecycle( $upgrader ) );
		}

		return $this->upgrade_hooks;
	}

	/**
	 * The screens in Lasso's order (F38 D1, D4). A screen's task adds its item here; Opportunities, Tables, Import and
	 * History join when their screens are built (F22, F25, F33, F34).
	 */
	private function navigation(): AdminNavigation {
		if ( null === $this->navigation ) {
			$this->navigation = new AdminNavigation();
			$this->navigation->register( new NavItem( 'dashboard', AdminMenu::SLUG, static fn (): string => __( 'Dashboard', 'affibloom' ), 'home', Capabilities::PRODUCTS, array( ProductScreen::SLUG ) ) );
			$this->navigation->register( new NavItem( 'groups', GroupsScreen::SLUG, static fn (): string => __( 'Groups', 'affibloom' ), 'layers', Capabilities::PRODUCTS, array( GroupScreen::SLUG ) ) );
			// A menu item only: Lasso's header has no Fields tab (F26 D13).
			$this->navigation->register( new NavItem( 'fields', FieldsScreen::SLUG, static fn (): string => __( 'Fields', 'affibloom' ), 'list', Capabilities::PRODUCTS, array( FieldScreen::SLUG ) ) );
			$this->navigation->register( new NavItem( 'settings', SettingsScreen::SLUG, static fn (): string => __( 'Settings', 'affibloom' ), 'settings', Capabilities::SETTINGS ) );
		}

		return $this->navigation;
	}

	/**
	 * Loads translations from /languages.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'affibloom', false, dirname( plugin_basename( $this->file ) ) . '/languages' );
	}
}
