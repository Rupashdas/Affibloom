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
use Affibloom\Application\Products\BulkAddService;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductService;
use Affibloom\Application\Settings\SettingsService;
use Affibloom\Infrastructure\Http\WpPageFetcher;
use Affibloom\Infrastructure\WordPress\OptionBulkRunRepository;
use Affibloom\Infrastructure\WordPress\OptionDisplaySettingsRepository;
use Affibloom\Infrastructure\WordPress\OptionSettingsRepository;
use Affibloom\Infrastructure\WordPress\PostTypeProductRepository;
use Affibloom\Infrastructure\WordPress\ProductPostType;
use Affibloom\Infrastructure\WordPress\UserMetaSavedViewRepository;
use Affibloom\Infrastructure\WordPress\WpCronBackgroundJob;
use Affibloom\Interface\Cron\BulkAddCron;
use Affibloom\Interface\Admin\AdminAssets;
use Affibloom\Interface\Admin\AdminMenu;
use Affibloom\Interface\Admin\DashboardScreen;
use Affibloom\Interface\Admin\PerPage;
use Affibloom\Interface\Admin\DisplayController;
use Affibloom\Interface\Admin\DisplayScreen;
use Affibloom\Interface\Admin\Notice;
use Affibloom\Interface\Admin\SettingsController;
use Affibloom\Interface\Admin\SettingsScreen;
use Affibloom\Interface\Rest\BulkAddController;
use Affibloom\Interface\Rest\DashboardController;
use Affibloom\Interface\Rest\ProductResource;
use Affibloom\Interface\Rest\ProductsController;
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
		$settings = new SettingsService( $this->settings_repository() );
		$display  = new DisplaySettingsService( new OptionDisplaySettingsRepository(), $this->settings_repository() );
		$notice   = new Notice();
		$products = new ProductService( new PostTypeProductRepository(), new LinkResolver( new WpPageFetcher() ), $this->settings_repository() );
		$bulk     = new BulkAddService( $products, new OptionBulkRunRepository(), new WpCronBackgroundJob( BulkAddCron::HOOK ) );
		$per_page = new PerPage();
		$views    = new SavedViewService( new UserMetaSavedViewRepository() );

		return array(
			new ProductPostType(),
			new AdminMenu(
				new DashboardScreen( $this->template(), $notice, $products, $per_page, $views ),
				new SettingsScreen(
					$this->template(),
					$settings,
					new DisplayScreen( $this->template(), $display, $settings, $notice ),
					$notice
				)
			),
			new AdminAssets( plugin_dir_url( $this->file ), AFFIBLOOM_VERSION ),
			new SettingsController( $settings ),
			new DisplayController( $display, $settings ),
			new ProductsController( $products, new ProductResource() ),
			new BulkAddController( $bulk ),
			new DashboardController( $products, $per_page, $views, $this->template() ),
			new BulkAddCron( $bulk ),
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
