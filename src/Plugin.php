<?php
/**
 * Composition root: builds the plugin's objects and hands them to WordPress.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom;

use Affibloom\Application\Dashboard\SavedViewService;
use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Fields\FieldsService;
use Affibloom\Application\Groups\GroupService;
use Affibloom\Application\Icons\IconService;
use Affibloom\Application\Migration\LassoImportService;
use Affibloom\Application\Products\BulkAddService;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductService;
use Affibloom\Application\Settings\LinkDefaultsService;
use Affibloom\Application\Settings\SettingsService;
use Affibloom\Infrastructure\Http\WpPageFetcher;
use Affibloom\Infrastructure\Lasso\WpLassoSource;
use Affibloom\Infrastructure\WordPress\GroupTaxonomy;
use Affibloom\Infrastructure\WordPress\OptionBulkRunRepository;
use Affibloom\Infrastructure\WordPress\OptionDisplaySettingsRepository;
use Affibloom\Infrastructure\WordPress\OptionFieldRepository;
use Affibloom\Infrastructure\WordPress\PostMetaFieldUsage;
use Affibloom\Infrastructure\WordPress\OptionIconRepository;
use Affibloom\Infrastructure\WordPress\OptionSettingsRepository;
use Affibloom\Infrastructure\WordPress\PostMetaLassoImportLog;
use Affibloom\Infrastructure\WordPress\PostTypeProductRepository;
use Affibloom\Infrastructure\WordPress\ProductPostType;
use Affibloom\Infrastructure\WordPress\SiteCssIcons;
use Affibloom\Infrastructure\WordPress\TermGroupRepository;
use Affibloom\Infrastructure\WordPress\UserMetaSavedViewRepository;
use Affibloom\Infrastructure\WordPress\WpDetectionChoices;
use Affibloom\Infrastructure\WordPress\WpCronBackgroundJob;
use Affibloom\Interface\Cron\BulkAddCron;
use Affibloom\Interface\Admin\AdminAssets;
use Affibloom\Interface\Admin\AdminMenu;
use Affibloom\Interface\Admin\DashboardScreen;
use Affibloom\Interface\Admin\FieldScreen;
use Affibloom\Interface\Admin\FieldsScreen;
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
use Affibloom\Interface\Admin\Notice;
use Affibloom\Interface\Admin\SettingsController;
use Affibloom\Interface\Admin\SettingsScreen;
use Affibloom\Interface\Admin\TrackingIdController;
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
use Affibloom\Module;

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
		$products  = new ProductService( new PostTypeProductRepository(), $resolver, $this->settings_repository(), new ProductInput( $links ), $links );
		$bulk      = new BulkAddService( $products, new OptionBulkRunRepository(), new WpCronBackgroundJob( BulkAddCron::HOOK ) );
		$per_page  = new PerPage();
		$views     = new SavedViewService( new UserMetaSavedViewRepository() );
		$icons     = new IconService( new OptionIconRepository(), new SiteCssIcons() );
		$choices   = new IconChoices( $icons );
		$fields    = new FieldsService( new OptionFieldRepository(), new PostTypeProductRepository(), new PostMetaFieldUsage() );
		$import    = new LassoImportService( new WpLassoSource(), new PostMetaLassoImportLog(), new PostTypeProductRepository() );
		$groups    = new GroupService( new TermGroupRepository() );

		return array(
			new ProductPostType(),
			new GroupTaxonomy(),
			new AdminMenu(
				new DashboardScreen( $this->template(), $products, $per_page, $views, $notice, $import ),
				new ProductScreen( $this->template(), $products, $display, $choices, $settings, $fields, $notice ),
				new SettingsScreen(
					$this->template(),
					$settings,
					new DisplayScreen( $this->template(), $display, $settings, $notice, $choices ),
					$notice,
					$detection
				),
				new GroupsScreen( $this->template(), $groups, $notice ),
				new GroupScreen( $this->template(), $groups, $notice ),
				new FieldsScreen( $this->template(), $fields, $notice ),
				new FieldScreen( $this->template(), $fields, $notice )
			),
			new AdminAssets( plugin_dir_url( $this->file ), AFFIBLOOM_VERSION ),
			new SettingsController( $settings ),
			new SettingsRestController( new LinkDefaultsService( new PostTypeProductRepository() ) ),
			new HelpTips( $settings ),
			new DisplayController( $display, $settings ),
			new ProductsController( $products, new ProductResource() ),
			new BulkAddController( $bulk ),
			new DashboardController( $products, $per_page, $views, $this->template() ),
			new BulkAddCron( $bulk ),
			new IconController( $icons ),
			new TrackingIdController( $settings ),
			new LassoImportController( $import ),
			new FieldsController( $fields, $this->template() ),
			new GroupsController( $groups, $this->template() ),
			new ProductFieldsController( $fields, $this->template() ),
			new IconStyles( $icons ),
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
		$this->template ??= new Template( dirname( $this->file ) . '/templates' );
		return $this->template;
	}

	/**
	 * Loads translations from /languages.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'affibloom', false, dirname( plugin_basename( $this->file ) ) . '/languages' );
	}
}
