<?php
namespace Affibloom\Tests\Unit;

use Affibloom\Plugin;

final class PluginTest extends TestCase {
	public function test_boot_loads_translations_on_init(): void {
		Plugin::boot( '/plugins/affibloom/affibloom.php' );

		self::assertNotFalse( has_action( 'init', 'Affibloom\Plugin->load_textdomain()' ) );
	}
}
