<?php
/**
 * Composition root: builds the plugin's objects and hands them to WordPress.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom;

use Affibloom\Interface\Admin\AdminAssets;
use Affibloom\Interface\Admin\AdminMenu;
use Affibloom\Interface\Admin\DashboardScreen;
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
		return array(
			new AdminMenu( new DashboardScreen( $this->template() ) ),
			new AdminAssets( plugin_dir_url( $this->file ), AFFIBLOOM_VERSION ),
		);
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
