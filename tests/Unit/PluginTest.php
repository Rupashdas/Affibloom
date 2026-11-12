<?php
namespace Affibloom\Tests\Unit;

use Affibloom\Plugin;
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
	}
}
