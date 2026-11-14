<?php
namespace Affibloom\Tests\Integration;

use Affibloom\Interface\Admin\AdminNavigation;
use Affibloom\Interface\Admin\ForeignNotices;
use Affibloom\Interface\Admin\NavItem;
use WP_UnitTestCase;

/**
 * F38 R15, D9, AC4 (Task ON2): other plugins' notices and header callbacks are removed on Affibloom's pages only;
 * WordPress's admin bar stays.
 */
final class ForeignNoticesTest extends WP_UnitTestCase {

	private function module(): ForeignNotices {
		$navigation = new AdminNavigation();
		$navigation->register( new NavItem( 'dashboard', 'affibloom', static fn (): string => 'Dashboard', 'home', 'edit_posts', array( 'affibloom-product' ) ) );
		return new ForeignNotices( $navigation );
	}

	private static function foreign(): void {
		echo 'foreign';
	}

	public function tear_down(): void {
		unset( $_GET['page'] );
		parent::tear_down();
	}

	public function test_an_affibloom_page_keeps_no_foreign_notice_and_only_the_admin_bar_in_the_header(): void {
		add_action( 'admin_notices', array( self::class, 'foreign' ) );
		add_action( 'all_admin_notices', array( self::class, 'foreign' ) );
		add_action( 'in_admin_header', array( self::class, 'foreign' ) );
		$_GET['page'] = 'affibloom-product';

		$this->module()->remove();

		self::assertFalse( has_action( 'admin_notices' ) );
		self::assertFalse( has_action( 'all_admin_notices' ) );
		self::assertFalse( has_action( 'in_admin_header', array( self::class, 'foreign' ) ) );
		self::assertSame( 0, has_action( 'in_admin_header', 'wp_admin_bar_render' ) );
	}

	public function test_another_page_keeps_them(): void {
		add_action( 'admin_notices', array( self::class, 'foreign' ) );
		$_GET['page'] = 'some-other-plugin';

		$this->module()->remove();

		self::assertSame( 10, has_action( 'admin_notices', array( self::class, 'foreign' ) ) );
	}

	public function test_it_runs_late_in_admin_head(): void {
		$module = $this->module();
		$module->register();

		self::assertSame( 99999, has_action( 'admin_head', array( $module, 'remove' ) ) );
	}
}
