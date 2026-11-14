<?php
namespace Affibloom\Tests\Unit\Interface;

use Affibloom\Interface\Admin\AdminNavigation;
use Affibloom\Interface\Admin\NavItem;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * F38 R2, R3, R11 (D1, D3, D4): one list in Lasso's order for the header and the WordPress menu, and the page groups
 * that decide which tab and menu item is current.
 */
final class AdminNavigationTest extends TestCase {

	private static function item( string $key, string $slug, array $pages = array() ): NavItem {
		return new NavItem( $key, $slug, static fn (): string => ucfirst( $key ), 'home', 'edit_posts', $pages );
	}

	/** Registered out of order, the way screens are built; every Lasso screen, as each task will add it. */
	private static function full(): AdminNavigation {
		$navigation = new AdminNavigation();
		foreach ( array( 'history', 'settings', 'import', 'fields', 'groups', 'tables', 'opportunities', 'dashboard' ) as $key ) {
			$navigation->register( self::item( $key, 'affibloom-' . $key ) );
		}
		return $navigation;
	}

	private static function keys( array $items ): array {
		return array_map( static fn ( NavItem $item ): string => $item->key, $items );
	}

	public function test_the_header_lists_lassos_tabs_in_order_then_settings(): void {
		self::assertSame( array( 'dashboard', 'opportunities', 'tables', 'groups', 'settings' ), self::keys( self::full()->tabs() ) );
	}

	public function test_the_menu_lists_lassos_submenu_in_order(): void {
		self::assertSame(
			array( 'dashboard', 'opportunities', 'tables', 'groups', 'fields', 'import', 'settings', 'history' ),
			self::keys( self::full()->menu() )
		);
	}

	/** A screen that is not built yet has no tab and no menu item: never a dead link (D1). */
	public function test_only_registered_screens_are_listed(): void {
		$navigation = new AdminNavigation();
		$navigation->register( self::item( 'settings', 'affibloom-settings' ) );
		$navigation->register( self::item( 'fields', 'affibloom-fields' ) );
		$navigation->register( self::item( 'dashboard', 'affibloom' ) );

		self::assertSame( array( 'dashboard', 'settings' ), self::keys( $navigation->tabs() ) );
		self::assertSame( array( 'dashboard', 'fields', 'settings' ), self::keys( $navigation->menu() ) );
	}

	/** R3: a detail page belongs to its list's item, so the Dashboard tab is current on a product page. */
	public function test_the_current_item_owns_the_page_or_its_detail_pages(): void {
		$navigation = new AdminNavigation();
		$navigation->register( self::item( 'dashboard', 'affibloom', array( 'affibloom-product' ) ) );
		$navigation->register( self::item( 'groups', 'affibloom-groups', array( 'affibloom-group' ) ) );

		self::assertSame( 'dashboard', $navigation->current( 'affibloom' )?->key );
		self::assertSame( 'dashboard', $navigation->current( 'affibloom-product' )?->key );
		self::assertSame( 'groups', $navigation->current( 'affibloom-group' )?->key );
		self::assertNull( $navigation->current( 'some-other-plugin' ) );
		self::assertNull( $navigation->current( '' ) );
	}

	/** A screen outside Lasso's list has no place in it, so registering one is a mistake, not a silent omission. */
	public function test_a_key_outside_lassos_list_is_refused(): void {
		$this->expectException( InvalidArgumentException::class );
		( new AdminNavigation() )->register( self::item( 'analytics', 'affibloom-analytics' ) );
	}

	public function test_the_label_is_read_when_shown(): void {
		$calls = 0;
		$item  = new NavItem(
			'dashboard',
			'affibloom',
			static function () use ( &$calls ): string {
				++$calls;
				return 'Dashboard';
			},
			'home',
			'edit_posts'
		);

		self::assertSame( 0, $calls );
		self::assertSame( 'Dashboard', $item->label() );
		self::assertSame( 1, $calls );
	}
}
