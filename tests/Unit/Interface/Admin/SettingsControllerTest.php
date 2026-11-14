<?php
namespace Affibloom\Tests\Unit\Interface\Admin;

use Affibloom\Interface\Admin\SettingsController;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

final class SettingsControllerTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'admin_url' )->justReturn( 'https://site.test/wp-admin/' );
		// WordPress returns the fallback for a host that is not the site's.
		Functions\when( 'wp_validate_redirect' )->alias( static fn( $url, $fallback ) => str_starts_with( $url, 'https://site.test/' ) ? $url : $fallback );
	}

	public function test_an_admin_address_is_followed_after_the_save(): void {
		self::assertSame( 'https://site.test/wp-admin/admin.php?page=affibloom', SettingsController::admin_target( 'https://site.test/wp-admin/admin.php?page=affibloom' ) );
	}

	public function test_a_foreign_host_falls_back_to_the_tab(): void {
		self::assertSame( '', SettingsController::admin_target( 'https://evil.example/wp-admin/' ) );
	}

	public function test_a_page_outside_the_admin_falls_back_to_the_tab(): void {
		self::assertSame( '', SettingsController::admin_target( 'https://site.test/some-post/' ) );
		self::assertSame( '', SettingsController::admin_target( '' ) );
	}
}
