<?php
namespace Affibloom\Tests\Integration;

use WP_UnitTestCase;

final class PluginLoadsTest extends WP_UnitTestCase {
	public function test_plugin_boots_inside_wordpress(): void {
		self::assertTrue( defined( 'AFFIBLOOM_VERSION' ) );
		self::assertTrue( class_exists( \Affibloom\Plugin::class ) );
	}

	public function test_deactivating_and_activating_raises_no_errors(): void {
		deactivate_plugins( 'affibloom/affibloom.php' );
		$result = activate_plugin( 'affibloom/affibloom.php' );

		self::assertNull( $result );
	}
}
