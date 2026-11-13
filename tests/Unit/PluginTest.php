<?php
namespace Affibloom\Tests\Unit;

use Affibloom\Plugin;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;

final class PluginTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'plugin_dir_url' )->justReturn( 'https://example.test/wp-content/plugins/affibloom/' );
	}

	public function test_boot_loads_translations_on_init(): void {
		Plugin::boot( '/plugins/affibloom/affibloom.php' );

		self::assertNotFalse( has_action( 'init', 'Affibloom\Plugin->load_textdomain()' ) );
	}

	public function test_boot_registers_the_admin_menu_and_its_assets(): void {
		Plugin::boot( '/plugins/affibloom/affibloom.php' );

		self::assertNotFalse( has_action( 'admin_menu', 'Affibloom\Interface\Admin\AdminMenu->add_pages()' ) );
		self::assertNotFalse( has_action( 'admin_enqueue_scripts', 'Affibloom\Interface\Admin\AdminAssets->enqueue()' ) );
		self::assertNotFalse( has_action( 'admin_head', 'Affibloom\Interface\Admin\AdminMenu->hide_product_page()' ) );
	}

	/**
	 * The product page is a submenu page, so WordPress checks the capability and the Dashboard stays highlighted; it
	 * is taken out of the menu after admin_menu, as Lasso's link details page is never listed. Its guard runs on the
	 * page's load hook, before any output, so a missing product can still be redirected.
	 */
	public function test_the_product_page_is_registered_hidden_and_guarded(): void {
		Functions\when( '__' )->returnArg();
		Functions\when( 'add_menu_page' )->justReturn( 'toplevel_page_affibloom' );
		Functions\expect( 'add_submenu_page' )
			->times( 3 )
			->andReturnUsing(
				static fn( string $parent, string $title, string $menu, string $cap, string $slug ): string => 'affibloom_page_' . $slug
			);
		Functions\expect( 'remove_submenu_page' )->once()->with( 'affibloom', 'affibloom-product' );
		// Brain Monkey records hooks without running them: run these two callbacks as WordPress would.
		$run = static function ( callable $callback ): void {
			$callback();
		};
		Actions\expectAdded( 'admin_menu' )->whenHappen( $run );
		Actions\expectAdded( 'admin_head' )->whenHappen( $run );

		Plugin::boot( '/plugins/affibloom/affibloom.php' );

		self::assertNotFalse( has_action( 'load-affibloom_page_affibloom-product', 'Affibloom\Interface\Admin\ProductScreen->guard()' ) );
	}
}
