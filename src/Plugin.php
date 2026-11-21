<?php
/**
 * Composition root: builds the plugin's objects and hands them to WordPress.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom;

use Affibloom\Application\Clicks\ClickReport;
use Affibloom\Application\Clicks\ClickRetention;
use Affibloom\Application\Clicks\ClickTracker;
use Affibloom\Application\Opportunities\IntroBoxes;
use Affibloom\Application\Opportunities\OpportunityService;
use Affibloom\Infrastructure\WordPress\Builders\BuilderSource;
use Affibloom\Infrastructure\WordPress\Builders\MvCreateSource;
use Affibloom\Infrastructure\WordPress\Builders\TablePressSource;
use Affibloom\Infrastructure\WordPress\Builders\ThriveSource;
use Affibloom\Infrastructure\WordPress\Builders\WprmSource;
use Affibloom\Infrastructure\WordPress\OptionRequestLedger;
use Affibloom\Application\Amazon\GuardedCatalog;
use Affibloom\Infrastructure\WordPress\TableClickStore;
use Affibloom\Infrastructure\WordPress\ProductDomainBackfill;
use Affibloom\Infrastructure\WordPress\TableOpportunityRepository;
use Affibloom\Infrastructure\WordPress\TransientRepeatGuard;
use Affibloom\Infrastructure\WordPress\WpPostDirectory;
use Affibloom\Interface\Admin\OpportunitiesScreen;
use Affibloom\Interface\Cron\AmazonRefreshCron;
use Affibloom\Infrastructure\WordPress\PostMetaDueAmazonData;
use Affibloom\Infrastructure\WordPress\OptionRefreshRunRepository;
use Affibloom\Application\Amazon\PriceRefreshService;
use Affibloom\Application\Amazon\AmazonDataService;
use Affibloom\Application\Box\BoxBuilder;
use Affibloom\Application\Shortcode\ShortcodeRenderer;
use Affibloom\Application\Box\RepointResolver;
use Affibloom\Application\Amazon\AmazonSearchService;
use Affibloom\Application\Amazon\BulkSyncService;
use Affibloom\Application\Amazon\CredentialsService;
use Affibloom\Application\Dashboard\SavedViewService;
use Affibloom\Application\Display\AiTextService;
use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Fields\FieldsService;
use Affibloom\Application\Groups\GroupService;
use Affibloom\Application\Icons\IconService;
use Affibloom\Application\Cloak\CloakResolver;
use Affibloom\Application\Lifecycle;
use Affibloom\Application\LinkIndex\RenderedLinks;
use Affibloom\Application\LinkIndex\DisplayResolver;
use Affibloom\Application\LinkIndex\LinkIndexer;
use Affibloom\Application\LinkIndex\LinkIndexBuildingNotice;
use Affibloom\Application\LinkIndex\LinkIndexJobStatus;
use Affibloom\Application\LinkIndex\LinkIndexRebuild;
use Affibloom\Application\Products\BulkAddJobStatus;
use Affibloom\Application\Shell\JobBoard;
use Affibloom\Application\LinkIndex\LinkIndexStatus;
use Affibloom\Application\LinkIndex\ProductResolver;
use Affibloom\Domain\LinkIndex\LinkClassifier;
use Affibloom\Application\Products\BulkAddService;
use Affibloom\Application\Products\GeniusCleanupService;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductService;
use Affibloom\Application\Settings\LinkDefaultsService;
use Affibloom\Application\Settings\SettingsService;
use Affibloom\Application\Variants\VariantService;
use Affibloom\Application\Shell\NoticeBoard;
use Affibloom\Application\Shell\UpdateAvailableNotice;
use Affibloom\Application\Upgrade\Upgrader;
use Affibloom\Infrastructure\Http\CreatorsApiCatalog;
use Affibloom\Infrastructure\Http\WpPageFetcher;
use Affibloom\Infrastructure\Data\FileNetworkSubIds;
use Affibloom\Infrastructure\Html\WpLinkScanner;
use Affibloom\Infrastructure\Schema\Migrator;
use Affibloom\Infrastructure\Schema\Table;
use Affibloom\Infrastructure\WordPress\BoxDetector;
use Affibloom\Infrastructure\WordPress\CronRegistry;
use Affibloom\Infrastructure\WordPress\GroupTaxonomy;
use Affibloom\Infrastructure\WordPress\OptionBulkRunRepository;
use Affibloom\Infrastructure\WordPress\OptionBulkSyncRunRepository;
use Affibloom\Infrastructure\WordPress\OptionCredentialsRepository;
use Affibloom\Infrastructure\WordPress\OptionDisplaySettingsRepository;
use Affibloom\Infrastructure\WordPress\OptionAiTextRepository;
use Affibloom\Infrastructure\WordPress\OptionFieldRepository;
use Affibloom\Infrastructure\WordPress\PostMetaFieldUsage;
use Affibloom\Infrastructure\WordPress\OptionIconRepository;
use Affibloom\Infrastructure\WordPress\OptionLock;
use Affibloom\Infrastructure\WordPress\OptionPluginState;
use Affibloom\Infrastructure\WordPress\OptionSettingsRepository;
use Affibloom\Infrastructure\WordPress\PluginUpdates;
use Affibloom\Infrastructure\WordPress\PostMetaPendingAmazonSync;
use Affibloom\Infrastructure\WordPress\PostTypeProductRepository;
use Affibloom\Infrastructure\WordPress\ProductPostType;
use Affibloom\Infrastructure\WordPress\SiteCssIcons;
use Affibloom\Infrastructure\WordPress\HostQuery;
use Affibloom\Infrastructure\WordPress\PostContentSource;
use Affibloom\Infrastructure\WordPress\TableAmazonProductRepository;
use Affibloom\Infrastructure\WordPress\TableLinkLocationRepository;
use Affibloom\Infrastructure\WordPress\TableVariantRepository;
use Affibloom\Infrastructure\WordPress\WpSlugTaken;
use Affibloom\Infrastructure\WordPress\TransientSearchResults;
use Affibloom\Infrastructure\WordPress\TermGroupRepository;
use Affibloom\Infrastructure\WordPress\UserMetaBannerDismissals;
use Affibloom\Infrastructure\WordPress\UserMetaSavedViewRepository;
use Affibloom\Infrastructure\WordPress\WpDetectionChoices;
use Affibloom\Infrastructure\WordPress\WpCronBackgroundJob;
use Affibloom\Infrastructure\WordPress\WpCronGeniusCleanupSchedule;
use Affibloom\Interface\Cli\AmazonCommand;
use Affibloom\Interface\Cli\LinksCommand;
use Affibloom\Interface\Cli\CheckLinksCommand;
use Affibloom\Application\Jobs\CpuGuard;
use Affibloom\Application\Jobs\DueJobs;
use Affibloom\Infrastructure\WordPress\WpScheduledEvents;
use Affibloom\Infrastructure\WordPress\WpCronHealth;
use Affibloom\Application\Jobs\JobSlots;
use Affibloom\Interface\Admin\BrokenLinksNotice;
use Affibloom\Interface\Front\ClickScript;
use Affibloom\Interface\Front\GaTag;
use Affibloom\Interface\Cron\ClickRetentionCron;
use Affibloom\Interface\PublicLinks;
use Affibloom\Infrastructure\WordPress\WpSlugConflicts;
use Affibloom\Interface\Front\CloakedLinkRedirect;
use Affibloom\Interface\Front\DisplayRenderFilter;
use Affibloom\Interface\Front\LinkAttributesFilter;
use Affibloom\Infrastructure\WordPress\WpPostSlugGuard;
use Affibloom\Interface\Rest\ClicksController;
use Affibloom\Interface\Rest\JobsController;
use Affibloom\Interface\Rest\LinkIndexController;
use Affibloom\Interface\Cron\LinkIndexCron;
use Affibloom\Interface\Cron\LinkCheckCron;
use Affibloom\Application\LinkHealth\CatalogProductCheck;
use Affibloom\Application\LinkHealth\LinkCheckService;
use Affibloom\Application\LinkHealth\LinkCheckJobStatus;
use Affibloom\Application\LinkHealth\AmazonLinkHealth;
use Affibloom\Application\LinkHealth\LinkIssueQuery;
use Affibloom\Application\Locations\LocationCounts;
use Affibloom\Application\Locations\LocationPreview;
use Affibloom\Application\Locations\LocationService;
use Affibloom\Application\LinkHealth\IssueLinksExport;
use Affibloom\Interface\Rest\DashboardExportController;
use Affibloom\Infrastructure\Http\HttpLinkStatusChecker;
use Affibloom\Infrastructure\WordPress\OptionCheckPass;
use Affibloom\Infrastructure\WordPress\TableLinkIssueRepository;
use Affibloom\Infrastructure\WordPress\PostMetaAlertDismissals;
use Affibloom\Interface\Rest\OpportunitiesController;
use Affibloom\Interface\Rest\ProductAlertsController;
use Affibloom\Infrastructure\WordPress\WpCronProductLinkChecks;
use Affibloom\Infrastructure\System\CpuLoad;
use Affibloom\Infrastructure\WordPress\OptionLinkIndexState;
use Affibloom\Interface\Hooks\LinkIndexHooks;
use Affibloom\Interface\Cli\UpgradeCommand;
use Affibloom\Interface\Cron\AmazonBulkSyncCron;
use Affibloom\Interface\Cron\BulkAddCron;
use Affibloom\Interface\Cron\GeniusCleanupCron;
use Affibloom\Interface\Admin\AdminAssets;
use Affibloom\Interface\Admin\AdminMenu;
use Affibloom\Interface\Admin\AdminNavigation;
use Affibloom\Interface\Admin\AmazonNotices;
use Affibloom\Interface\Admin\SiteStripeNotice;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\ClassicLinkSearch;
use Affibloom\Interface\Admin\DashboardScreen;
use Affibloom\Interface\Admin\FieldScreen;
use Affibloom\Interface\Admin\FieldsScreen;
use Affibloom\Interface\Admin\ForeignNotices;
use Affibloom\Interface\Admin\GroupScreen;
use Affibloom\Interface\Admin\GroupsScreen;
use Affibloom\Interface\Admin\PerPage;
use Affibloom\Interface\Admin\ProductScreen;
use Affibloom\Interface\Admin\DisplayScreen;
use Affibloom\Interface\Admin\HelpTips;
use Affibloom\Interface\Admin\IconChoices;
use Affibloom\Interface\Admin\NavItem;
use Affibloom\Interface\Admin\Notice;
use Affibloom\Interface\Admin\SettingsScreen;
use Affibloom\Interface\Block\ProductBoxBlock;
use Affibloom\Interface\Editor\ClassicButton;
use Affibloom\Interface\Editor\EditorAssets;
use Affibloom\Interface\Editor\ElementorWidget;
use Affibloom\Interface\Front\AffibloomShortcode;
use Affibloom\Interface\Front\BoxAssets;
use Affibloom\Interface\Front\SchemaMarkup;
use Affibloom\Interface\Front\WidgetShortcodes;
use Affibloom\Interface\Front\BoxHtml;
use Affibloom\Interface\Front\DisplayHtml;
use Affibloom\Interface\Rest\AmazonController;
use Affibloom\Interface\Rest\BulkAddController;
use Affibloom\Interface\Rest\DashboardController;
use Affibloom\Interface\Rest\FieldsController;
use Affibloom\Interface\Rest\GroupsController;
use Affibloom\Interface\Rest\ProductFieldsController;
use Affibloom\Interface\Rest\ProductResource;
use Affibloom\Interface\Rest\ProductLinkSearch;
use Affibloom\Interface\Rest\ProductsController;
use Affibloom\Interface\Rest\LocationPreviewController;
use Affibloom\Interface\Rest\LocationsController;
use Affibloom\Interface\Rest\VariantsController;
use Affibloom\Interface\Rest\DisplayPreviewController;
use Affibloom\Interface\Rest\EditorController;
use Affibloom\Interface\Rest\ProductPreviewController;
use Affibloom\Interface\Rest\IconsController;
use Affibloom\Interface\Rest\SettingsRestController;
use Affibloom\Interface\IconStyles;
use Affibloom\Interface\Template;
use Affibloom\Interface\UpgradeHooks;
use Affibloom\Module;
use DateTimeImmutable;
use DateTimeZone;
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
	 * The broken-links notice, built once.
	 *
	 * @var BrokenLinksNotice|null
	 */
	private ?BrokenLinksNotice $broken_links_notice = null;

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
	 * The Creators API inside Amazon's limits (C1Q6a), shared by every Amazon caller; built on first use.
	 *
	 * @var GuardedCatalog|null
	 */
	private ?GuardedCatalog $catalog = null;

	/**
	 * The link indexer, shared by the rebuild and the save hooks (F21); built on first use.
	 *
	 * @var LinkIndexer|null
	 */
	private ?LinkIndexer $link_indexer = null;

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
		return new Lifecycle(
			self::cron_registry(),
			new OptionPluginState(),
			$upgrader,
			AFFIBLOOM_VERSION,
			// Task 35F: the link index's first rebuild, as Lasso's activation queues its full scan (R15). An event, since
			// the modules may not be registered in the activation request.
			static function (): void {
				if ( false === wp_next_scheduled( LinkIndexCron::START ) ) {
					wp_schedule_single_event( time(), LinkIndexCron::START );
				}
			}
		);
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
			// Task 35A: the content link index (F21 D1).
			4 => static function (): void {
				( new Table( TableLinkLocationRepository::TABLE, TableLinkLocationRepository::COLUMNS ) )->create();
			},
			// Task V1: the products' URL variants (F27 D1).
			5 => static function (): void {
				( new Table( TableVariantRepository::TABLE, TableVariantRepository::COLUMNS ) )->create();
			},
			// Task 30A: the products' link-health rows (F11 D1).
			6 => static function (): void {
				( new Table( TableLinkIssueRepository::TABLE, TableLinkIssueRepository::COLUMNS ) )->create();
			},
			// Task 33A: the click counts (F12 D2, ADR-005 with location_id).
			7 => static function (): void {
				( new Table( TableClickStore::TABLE, TableClickStore::COLUMNS ) )->create();
			},
			// Task O1: every stored product's base domain, which later saves write (F22 D5).
			8 => static function (): void {
				ProductDomainBackfill::run();
			},
		);
	}

	/**
	 * The builders and recipe plugins whose places the link index reads while they are active (F21 R23, R32; Task 35E).
	 * Elementor and Beaver Builder need none: both keep a rendered copy in `post_content`.
	 *
	 * @return list<BuilderSource>
	 */
	private static function builders(): array {
		return array( new ThriveSource(), new TablePressSource(), new WprmSource(), new MvCreateSource() );
	}

	/**
	 * Every scheduled hook: each cron module's HOOKS. A new cron module adds its list here.
	 */
	private static function cron_registry(): CronRegistry {
		return new CronRegistry( BulkAddCron::HOOKS, AmazonBulkSyncCron::HOOKS, AmazonRefreshCron::HOOKS, GeniusCleanupCron::HOOKS, LinkIndexCron::HOOKS, LinkCheckCron::HOOKS );
	}

	/**
	 * The Creators API inside Amazon's limits (C1Q6a): one guard and one record for every caller.
	 */
	private function catalog(): GuardedCatalog {
		$this->catalog ??= new GuardedCatalog( new CreatorsApiCatalog(), new OptionRequestLedger() );
		return $this->catalog;
	}

	/**
	 * Amazon's data on products (C1C, C1D, C1I).
	 */
	private function amazon_data(): AmazonDataService {
		$this->amazon_data ??= new AmazonDataService( new OptionCredentialsRepository(), $this->catalog(), new TableAmazonProductRepository(), new PostTypeProductRepository(), new AmazonLinkHealth( $this->link_checks(), new TableLinkIssueRepository(), new PostTypeProductRepository() ) );
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
			new LinkNormaliser( new LinkResolver( new WpPageFetcher() ), $this->settings_repository(), new TableVariantRepository() ),
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
		$settings  = new SettingsService(
			$this->settings_repository(),
			$detection,
			// Task 35D: a change of what is scanned starts a background rebuild (Task 35F).
			fn() => $this->link_index_rebuild()->start()
		);
		$display   = new DisplaySettingsService(
			new OptionDisplaySettingsRepository(),
			$this->settings_repository(),
			// F5 R53: Lasso's lasso_display_v2_frontend_enabled under Affibloom's name decides the engine last.
			static fn( bool $customised ): bool => (bool) apply_filters( 'affibloom_display_advanced_enabled', $customised )
		);
		$notice   = new Notice();
		$resolver = new LinkResolver( new WpPageFetcher() );
		$links    = new LinkNormaliser( $resolver, $this->settings_repository(), new TableVariantRepository() );
		$catalog  = $this->catalog();
		$data     = $this->amazon_data();
		// A post's tied links, read once per request for both render-time filters (Tasks 35H, 35J).
		$rendered   = new RenderedLinks( new TableLinkLocationRepository(), new PostTypeProductRepository() );
		$cloaked    = new CloakedLinkRedirect( new CloakResolver( new PostTypeProductRepository(), new TableVariantRepository() ), $this->settings_repository(), new WpPostSlugGuard(), new FileNetworkSubIds( dirname( $this->file ) . '/data/network-subids.php' ) );
		$products   = new ProductService( new PostTypeProductRepository(), $resolver, $this->settings_repository(), new ProductInput( $links ), $links, $data, new TableLinkLocationRepository(), $cloaked->product_at( ... ), new WpCronProductLinkChecks( LinkCheckCron::PRODUCT ) );
		$variants   = new VariantService( new TableVariantRepository(), new WpSlugTaken( new PostTypeProductRepository() ) );
		$bulk       = new BulkAddService( $products, new OptionBulkRunRepository(), new WpCronBackgroundJob( BulkAddCron::HOOK ) );
		$genius     = new GeniusCleanupService( new PostTypeProductRepository(), $products, new WpCronGeniusCleanupSchedule( GeniusCleanupCron::HOOK ) );
		$per_page   = new PerPage();
		$views      = new SavedViewService( new UserMetaSavedViewRepository() );
		$icons      = new IconService( new OptionIconRepository(), new SiteCssIcons() );
		$choices    = new IconChoices( $icons );
		$icon_css   = new IconStyles( $icons );
		$fields     = new FieldsService( new OptionFieldRepository(), new PostTypeProductRepository(), new PostMetaFieldUsage() );
		$groups     = new GroupService( new TermGroupRepository(), new PostTypeProductRepository() );
		$amazon     = new CredentialsService( new OptionCredentialsRepository(), $catalog, $this->settings_repository() );
		$search     = new AmazonSearchService( $data, $catalog, $this->settings_repository(), new TransientSearchResults() );
		$upgrader   = self::upgrader();
		$click_js   = new ClickScript( plugin_dir_url( $this->file ), AFFIBLOOM_VERSION, $this->settings_repository() );
		$box_assets = new BoxAssets( dirname( $this->file ) . '/', plugin_dir_url( $this->file ), AFFIBLOOM_VERSION, $display, $this->settings_repository(), new BoxDetector(), $click_js );
		// The display modal's assets: the block editor's, the Classic editor's and Elementor's (ED2) share them.
		$editor_assets = new EditorAssets( plugin_dir_url( $this->file ), AFFIBLOOM_VERSION, $this->template(), $box_assets );

		$public_links = new PublicLinks( $this->settings_repository() );
		$box_builder  = new BoxBuilder( plugin_dir_url( $this->file ) . 'assets/front/images/no-thumbnail.webp', static fn( string $url ): int => url_to_postid( $url ), null, $public_links->for( ... ), new TableVariantRepository() );
		$box_html     = new BoxHtml( dirname( $this->file ) . '/templates/front', $box_assets );
		$display_html = new DisplayHtml( dirname( $this->file ) . '/templates/front', $box_html, $box_assets );
		// The block draws its Button and Image displays through the shortcode (Task 23C).
		$shortcode = new AffibloomShortcode(
			new ShortcodeRenderer( new PostTypeProductRepository(), new RepointResolver( new PostTypeProductRepository() ), new OptionFieldRepository(), new TableAmazonProductRepository(), $display, $this->settings_repository(), $box_builder, $groups ),
			$box_html,
			$display_html,
			new AiTextService( new OptionAiTextRepository() )
		);

		return array(
			new ProductPostType(),
			new GroupTaxonomy(),
			new ProductBoxBlock(
				// Built by `npm run build` (wp-scripts, Task 23A): block.json with the editor script and style.
				dirname( $this->file ) . '/build/product-box',
				new RepointResolver( new PostTypeProductRepository() ),
				$display,
				$this->settings_repository(),
				$box_builder,
				$box_html,
				new PostTypeProductRepository(),
				new OptionFieldRepository(),
				new TableAmazonProductRepository(),
				$shortcode,
				$groups
			),
			$box_assets,
			new ProductPreviewController( $products, new PostTypeProductRepository(), new OptionFieldRepository(), new TableAmazonProductRepository(), $display, $this->settings_repository(), $box_builder, $box_html, $box_assets, $icon_css ),
			$click_js,
			new GaTag( $this->settings_repository() ),
			new ClickRetentionCron( new ClickRetention( new TableClickStore() ) ),
			new ClicksController( new ClickTracker( new PostTypeProductRepository(), new WpPostDirectory(), new TableClickStore(), new TransientRepeatGuard(), $this->settings_repository() ) ),
			new LocationPreviewController( new LocationPreview( new TableLinkLocationRepository(), new PostContentSource( $this->host_query(), $this->settings_repository(), self::builders() ) ), $display, $this->settings_repository(), $box_assets, $icon_css ),
			new DisplayPreviewController( $display, $this->settings_repository(), $box_builder, $box_html, $box_assets, plugin_dir_url( $this->file ) . 'assets/admin/images/', $icon_css, $display_html ),
			$shortcode,
			new WidgetShortcodes(),
			$cloaked,
			new LinkAttributesFilter( $rendered, new PublicLinks( $this->settings_repository() ) ),
			new DisplayRenderFilter( $rendered, $this->settings_repository() ),
			new SchemaMarkup( new RepointResolver( new PostTypeProductRepository() ), new PostTypeProductRepository(), new TableAmazonProductRepository(), $this->settings_repository(), $box_builder ),
			new AdminMenu(
				$this->navigation(),
				new DashboardScreen( $this->template(), $products, $per_page, $views, $notice, $groups, new PublicLinks( $this->settings_repository() ), $this->link_issue_query(), $this->location_counts(), new ClickReport( new TableClickStore() ) ),
				new ProductScreen( $this->template(), $products, $display, $choices, $settings, $fields, $notice, $groups, $data, new PublicLinks( $this->settings_repository() ), $variants, $this->link_issue_query(), $this->location_service(), $this->location_counts(), $this->opportunity_service() ),
				new SettingsScreen(
					$this->template(),
					$settings,
					new DisplayScreen( $this->template(), $display, $settings, $notice, $choices ),
					$notice,
					$detection,
					$amazon,
					$this->link_index_status()
				),
				new GroupsScreen( $this->template(), $groups, $notice ),
				new GroupScreen( $this->template(), $groups, $notice ),
				new FieldsScreen( $this->template(), $fields, $notice ),
				new FieldScreen( $this->template(), $fields, $notice ),
				new OpportunitiesScreen( $this->template(), $this->opportunity_service(), $this->intro_boxes(), $products )
			),
			new AdminAssets( plugin_dir_url( $this->file ), AFFIBLOOM_VERSION ),
			$editor_assets,
			new ClassicButton( plugin_dir_url( $this->file ), AFFIBLOOM_VERSION ),
			new ElementorWidget( dirname( $this->file ) . '/', plugin_dir_url( $this->file ), AFFIBLOOM_VERSION, $editor_assets, $box_assets ),
			new EditorController( new PostTypeProductRepository(), new TableAmazonProductRepository(), $fields, $groups ),
			new AmazonController( $amazon, $data, $this->bulk_sync(), $search ),
			new SettingsRestController( new LinkDefaultsService( new PostTypeProductRepository() ), $settings, $display, $amazon, $genius, $this->template() ),
			new HelpTips( $settings ),
			new ProductsController( $products, new ProductResource( new PublicLinks( $this->settings_repository() ) ), $groups, $search, new WpSlugConflicts() ),
			new BulkAddController( $bulk ),
			new VariantsController( $products, $variants, $this->template() ),
			new OpportunitiesController( $this->opportunity_service(), $this->intro_boxes(), $this->template() ),
			new LocationsController( $products, $this->location_service(), $this->template() ),
			new ProductLinkSearch( new PublicLinks( $this->settings_repository() ), new PostTypeProductRepository() ),
			new ClassicLinkSearch( new PostTypeProductRepository(), new PublicLinks( $this->settings_repository() ) ),
			new DashboardController( $products, $per_page, $views, $this->template(), $groups, new PublicLinks( $this->settings_repository() ), $this->link_issue_query(), $this->location_counts(), new ClickReport( new TableClickStore() ) ),
			new DashboardExportController(
				new IssueLinksExport(
					new PostTypeProductRepository(),
					new TableLinkIssueRepository(),
					$fields,
					static fn( array $ids ): array => array_map( static fn( array $named ): array => array_map( static fn( $group ): string => wp_specialchars_decode( $group->name, ENT_QUOTES ), $named ), $groups->for_products( $ids ) ),
					static fn( array $ids ): array => ( new TableLinkLocationRepository() )->counts_for( $ids )
				),
				$this->link_issue_query(),
				$groups
			),
			new BulkAddCron( $bulk ),
			new AmazonBulkSyncCron( $this->bulk_sync() ),
			new GeniusCleanupCron( $genius ),
			new AmazonRefreshCron(
				new PriceRefreshService(
					new PostMetaDueAmazonData(),
					new OptionRefreshRunRepository(),
					new WpCronBackgroundJob( AmazonRefreshCron::PASS ),
					$this->amazon_data(),
					$this->settings_repository(),
					$this->catalog()
				)
			),
			new IconsController( $icons, $choices, $this->template() ),
			new FieldsController( $fields, $this->template() ),
			new GroupsController( $groups, $this->template() ),
			new LinkIndexController( $this->link_index_status(), $this->link_index_rebuild() ),
			new JobsController(
				new JobBoard(
					array(
						new BulkAddJobStatus( new OptionBulkRunRepository(), static fn(): int => time() ),
						new LinkIndexJobStatus( new OptionLinkIndexState() ),
						new LinkCheckJobStatus( new OptionCheckPass() ),
					)
				),
				new DueJobs( new WpScheduledEvents(), self::cron_registry()->hooks(), $this->settings_repository() ),
				new WpCronHealth()
			),
			new ProductFieldsController( $fields, $this->template() ),
			$icon_css,
			$this->upgrade_hooks(),
			new UpgradeCommand( $upgrader ),
			new AmazonCommand( $amazon, $catalog ),
			new LinksCommand( $this->link_index_rebuild(), new TableLinkLocationRepository(), new OptionLinkIndexState() ),
			new LinkIndexHooks( $this->link_indexer(), new TableLinkLocationRepository(), $this->host_query() ),
			new LinkIndexCron( $this->link_index_rebuild(), new OptionLinkIndexState(), new OptionLock( 'affibloom_link_index_lock', 60 ), new WpCronBackgroundJob( LinkIndexCron::BATCH ), $this->cpu_guard(), $this->settings_repository(), $this->job_slots() ),
			new LinkCheckCron( $this->link_checks(), new OptionLock( 'affibloom_link_check_lock', 60 ), $this->cpu_guard(), $this->job_slots(), new WpCronBackgroundJob( LinkCheckCron::BATCH ) ),
			new CheckLinksCommand( $this->link_checks(), new PostTypeProductRepository() ),
			$this->broken_links_notice(),
			new ProductAlertsController( $this->link_issue_query(), new PostTypeProductRepository() ),
			new ForeignNotices( $this->navigation() ),
		);
	}

	/**
	 * "N product links look broken." (F11 D20, Task 32B): one instance, a notice provider and a module.
	 */
	private function broken_links_notice(): BrokenLinksNotice {
		$this->broken_links_notice ??= new BrokenLinksNotice( $this->link_issue_query() );

		return $this->broken_links_notice;
	}

	/**
	 * Whether a background batch waits for a busy server (Task 32C; F21 D12).
	 */
	private function cpu_guard(): CpuGuard {
		$cpu = new CpuLoad();

		return new CpuGuard( static fn(): ?float => $cpu->percent(), $this->settings_repository() );
	}

	/**
	 * Processes Execute Limit's two slots (Task 32C).
	 */
	private function job_slots(): JobSlots {
		return new JobSlots( array( new OptionLock( 'affibloom_job_slot_1', 5 * 60 ), new OptionLock( 'affibloom_job_slot_2', 5 * 60 ) ), $this->settings_repository() );
	}

	/**
	 * The Locations column's counts and clock (F12 L1-L3; Task 36A).
	 */
	private function location_counts(): LocationCounts {
		return new LocationCounts( new TableLinkLocationRepository(), new OptionLinkIndexState() );
	}

	/**
	 * The Opportunities lists (F22; Task O1).
	 */
	private function opportunity_service(): OpportunityService {
		return new OpportunityService( new TableOpportunityRepository() );
	}

	/**
	 * The Opportunities screens' intro boxes (F22 D8; Task O1).
	 */
	private function intro_boxes(): IntroBoxes {
		return new IntroBoxes( $this->settings_repository() );
	}

	/**
	 * The product page's Locations tab and its route (F12; Task 36B).
	 */
	private function location_service(): LocationService {
		return new LocationService( new TableLinkLocationRepository(), new ClickReport( new TableClickStore() ) );
	}

	/**
	 * Link health as the screens read it (F11, Task 31A).
	 */
	private function link_issue_query(): LinkIssueQuery {
		return new LinkIssueQuery(
			new TableLinkIssueRepository(),
			$this->settings_repository(),
			static fn(): DateTimeImmutable => new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ),
			new PostTypeProductRepository(),
			new CatalogProductCheck( new OptionCredentialsRepository(), $this->catalog() ),
			static fn(): int => (int) get_option( OptionPluginState::ACTIVATED_AT, 0 ),
			new PostMetaAlertDismissals()
		);
	}

	/**
	 * The daily link check (F11, Task 30A).
	 */
	private function link_checks(): LinkCheckService {
		return new LinkCheckService(
			new TableLinkIssueRepository(),
			new OptionCheckPass(),
			new HttpLinkStatusChecker(),
			new CatalogProductCheck( new OptionCredentialsRepository(), $this->catalog() ),
			new WpCronBackgroundJob( LinkCheckCron::BATCH ),
			$this->settings_repository(),
			static fn(): DateTimeImmutable => new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ),
			static function ( int $product_id, string $url, string $issue_type ): void {
				do_action( 'affibloom_link_issue_detected', $product_id, $url, $issue_type );
			}
		);
	}

	/**
	 * The link index's rebuild (F21, Task 35A): every indexed post through the indexer.
	 */
	private function link_index_rebuild(): LinkIndexRebuild {
		return new LinkIndexRebuild( $this->host_query(), $this->link_indexer(), new OptionLinkIndexState(), new WpCronBackgroundJob( LinkIndexCron::BATCH ), new TableLinkLocationRepository() );
	}

	/**
	 * The link indexer (F21, Tasks 35A-35C): a post's content, the scanner, the product and group lookups, and the
	 * event after each host is written.
	 */
	private function link_indexer(): LinkIndexer {
		if ( null !== $this->link_indexer ) {
			return $this->link_indexer;
		}
		$links = new ProductResolver( new PostTypeProductRepository() );

		$this->link_indexer = new LinkIndexer(
			new PostContentSource( $this->host_query(), $this->settings_repository(), self::builders() ),
			new WpLinkScanner( $this->settings_repository() ),
			$links,
			new DisplayResolver( new PostTypeProductRepository(), new TermGroupRepository(), $links ),
			new LinkClassifier( site_url() ),
			new TableLinkLocationRepository(),
			static function ( int $host, array $rows ): void {
				do_action( 'affibloom_link_index_indexed', $host, $rows );
			}
		);

		return $this->link_indexer;
	}

	/**
	 * The Link Index card's figures (F21 R51; Tasks 35G, 9J).
	 */
	private function link_index_status(): LinkIndexStatus {
		return new LinkIndexStatus( new OptionLinkIndexState(), new TableLinkLocationRepository() );
	}

	/**
	 * The posts the link index covers (F21 D6, D7; Task 35D's chosen types).
	 */
	private function host_query(): HostQuery {
		return new HostQuery( $this->settings_repository(), self::builders() );
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
	 * then C1's (the bulk Amazon sync banner, the SiteStripe notice), then F21's while the link index builds (ON4). F11
	 * adds its provider here. A provider that fails is
	 * reported to the debug log.
	 */
	private function notice_board(): NoticeBoard {
		$this->notice_board ??= new NoticeBoard(
			array(
				new UpdateAvailableNotice( new PluginUpdates( plugin_basename( $this->file ) ) ),
				$this->upgrade_hooks(),
				new AmazonNotices( $this->bulk_sync() ),
				new SiteStripeNotice( $this->settings_repository() ),
				new LinkIndexBuildingNotice( new OptionLinkIndexState(), admin_url( 'admin.php?page=affibloom-settings&tab=general#affibloom-card-link-index' ) ),
				$this->broken_links_notice(),
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
			$this->navigation->register( new NavItem( 'opportunities', OpportunitiesScreen::SLUG, static fn (): string => __( 'Opportunities', 'affibloom' ), 'bulb', Capabilities::PRODUCTS ) );
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
