<?php
/**
 * Affibloom's screens in Lasso's order, for the header and the WordPress menu.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use InvalidArgumentException;

/**
 * Affibloom's screens in Lasso's order (F38 D1, D4). Each screen's task registers its item; the header and the menu
 * list only what is registered, so a tab never appears before its screen exists.
 */
final class AdminNavigation {

	/**
	 * The header's order: Lasso's tabs (header-new.php 154-173, without Analytics and Marketplace), then Settings.
	 */
	private const TABS = array( 'dashboard', 'opportunities', 'tables', 'groups', 'settings' );

	/**
	 * The WordPress submenu's order (Lasso's class-post-type.php 309-399, without its external links).
	 */
	private const MENU = array( 'dashboard', 'opportunities', 'tables', 'groups', 'fields', 'import', 'settings', 'history' );

	/**
	 * The registered items by key.
	 *
	 * @var array<string, NavItem>
	 */
	private array $items = array();

	/**
	 * Adds a screen.
	 *
	 * @param NavItem $item The screen's item.
	 * @throws InvalidArgumentException When its key has no place in Lasso's order.
	 */
	public function register( NavItem $item ): void {
		if ( ! in_array( $item->key, self::MENU, true ) ) {
			throw new InvalidArgumentException( 'This screen has no place in the navigation order.' );
		}
		$this->items[ $item->key ] = $item;
	}

	/**
	 * The header's tabs, in order.
	 *
	 * @return list<NavItem>
	 */
	public function tabs(): array {
		return $this->in_order( self::TABS );
	}

	/**
	 * The WordPress submenu's items, in order.
	 *
	 * @return list<NavItem>
	 */
	public function menu(): array {
		return $this->in_order( self::MENU );
	}

	/**
	 * The item the page belongs to, or null when it is not an Affibloom page.
	 *
	 * @param string $page An admin page slug.
	 */
	public function current( string $page ): ?NavItem {
		foreach ( $this->items as $item ) {
			if ( $item->owns( $page ) ) {
				return $item;
			}
		}

		return null;
	}

	/**
	 * The registered items among the keys, in the keys' order.
	 *
	 * @param list<string> $keys An order.
	 * @return list<NavItem>
	 */
	private function in_order( array $keys ): array {
		$items = array();
		foreach ( $keys as $key ) {
			if ( isset( $this->items[ $key ] ) ) {
				$items[] = $this->items[ $key ];
			}
		}

		return $items;
	}
}
