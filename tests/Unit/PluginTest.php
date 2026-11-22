<?php
namespace Affibloom\Tests\Unit;

use Affibloom\Plugin;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;

final class PluginTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'plugin_dir_url' )->justReturn( 'https://example.test/wp-content/plugins/affibloom/' );
		// The update notice's source names the plugin as WordPress does (Task ON2).
		Functions\when( 'plugin_basename' )->justReturn( 'affibloom/affibloom.php' );
		// The link index's classifier tells the site's own links by its address (Task 35A).
		Functions\when( 'site_url' )->justReturn( 'https://example.test' );
		// The link index notice links to the Link Index card (Task ON4).
		Functions\when( 'admin_url' )->alias( static fn( string $path = '' ): string => 'https://example.test/wp-admin/' . $path );
	}

	/** Task ON2: other plugins' notices leave Affibloom's pages late in admin_head. */
	public function test_boot_hides_other_plugins_notices_on_affibloom_pages(): void {
		Plugin::boot( '/plugins/affibloom/affibloom.php' );

		self::assertSame( 99999, has_action( 'admin_head', 'Affibloom\Interface\Admin\ForeignNotices->remove()' ) );
	}

	public function test_boot_loads_translations_on_init(): void {
		Plugin::boot( '/plugins/affibloom/affibloom.php' );

		self::assertNotFalse( has_action( 'init', 'Affibloom\Plugin->load_textdomain()' ) );
	}

	public function test_boot_registers_the_admin_menu_and_its_assets(): void {
		Plugin::boot( '/plugins/affibloom/affibloom.php' );

		self::assertNotFalse( has_action( 'admin_menu', 'Affibloom\Interface\Admin\AdminMenu->add_pages()' ) );
		self::assertNotFalse( has_action( 'admin_enqueue_scripts', 'Affibloom\Interface\Admin\AdminAssets->enqueue()' ) );
		self::assertNotFalse( has_action( 'admin_head', 'Affibloom\Interface\Admin\AdminMenu->hide_detail_pages()' ) );
	}

	public function test_boot_runs_the_upgrade_on_admin_pages_and_watches_the_version_on_init(): void {
		Plugin::boot( '/plugins/affibloom/affibloom.php' );

		self::assertSame( 10, has_action( 'admin_init', 'Affibloom\Interface\UpgradeHooks->upgrade()' ) );
		self::assertSame( 20, has_action( 'init', 'Affibloom\Interface\UpgradeHooks->watch_version()' ) );
	}

	/**
	 * The product page is a submenu page, so WordPress checks the capability and the Dashboard stays highlighted; it
	 * is taken out of the menu after admin_menu, as Lasso's link details page is never listed. Its guard runs on the
	 * page's load hook, before any output, so a missing product can still be redirected. A group's page (Task G1) is
	 * handled the same way, and so is a field's (Task FL2). The lists are listed in Lasso's order, Fields included
	 * (Task ON1, F38 R11).
	 */
	public function test_the_product_page_and_the_logs_page_are_registered_hidden(): void {
		Functions\when( '__' )->returnArg();
		Functions\when( 'add_menu_page' )->justReturn( 'toplevel_page_affibloom' );
		$slugs = array();
		Functions\expect( 'add_submenu_page' )
			->times( 12 )
			->andReturnUsing(
				static function ( string $parent, string $title, string $menu, string $cap, string $slug ) use ( &$slugs ): string {
					$slugs[] = $slug;
					return 'affibloom_page_' . $slug;
				}
			);
		Functions\expect( 'remove_submenu_page' )->once()->with( 'affibloom', 'affibloom-product' );
		Functions\expect( 'remove_submenu_page' )->once()->with( 'affibloom', 'affibloom-group' );
		Functions\expect( 'remove_submenu_page' )->once()->with( 'affibloom', 'affibloom-field' );
		Functions\expect( 'remove_submenu_page' )->once()->with( 'affibloom', 'affibloom-table' );
		Functions\expect( 'remove_submenu_page' )->once()->with( 'affibloom', 'affibloom-logs' );
		Functions\expect( 'remove_submenu_page' )->once()->with( 'affibloom', 'affibloom-db-status' );
		// Brain Monkey records hooks without running them: run these two callbacks as WordPress would.
		$run = static function ( callable $callback ): void {
			$callback();
		};
		Actions\expectAdded( 'admin_menu' )->whenHappen( $run );
		Actions\expectAdded( 'admin_head' )->whenHappen( $run );

		Plugin::boot( '/plugins/affibloom/affibloom.php' );

		self::assertSame(
			array( 'affibloom', 'affibloom-opportunities', 'affibloom-tables', 'affibloom-groups', 'affibloom-fields', 'affibloom-settings', 'affibloom-product', 'affibloom-group', 'affibloom-field', 'affibloom-table', 'affibloom-logs', 'affibloom-db-status' ),
			$slugs
		);
		self::assertNotFalse( has_action( 'load-affibloom_page_affibloom-product', 'Affibloom\Interface\Admin\ProductScreen->guard()' ) );
		self::assertNotFalse( has_action( 'load-affibloom_page_affibloom-group', 'Affibloom\Interface\Admin\GroupScreen->guard()' ) );
		self::assertNotFalse( has_action( 'load-affibloom_page_affibloom-field', 'Affibloom\Interface\Admin\FieldScreen->guard()' ) );
	}
}
