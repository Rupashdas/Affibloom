<?php
/**
 * One screen in Affibloom's navigation.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Closure;

/**
 * One screen in Affibloom's navigation: its place in Lasso's order (the key), its page, its label and icon, who may
 * open it, and the detail pages that belong to it (a product page belongs to the Dashboard; F38 R3).
 */
final class NavItem {

	/**
	 * Builds the item.
	 *
	 * @param string            $key        Its place in Lasso's order (AdminNavigation::TABS, ::MENU).
	 * @param string            $slug       The admin page slug the tab and the menu item open.
	 * @param Closure(): string $label      The translated label. A closure because the items are built when the
	 *                                      plugin loads, before WordPress loads translations on init.
	 * @param string            $icon       The header tab's icon (templates/admin/partials/icon.php).
	 * @param string            $capability Who sees the tab and the menu item.
	 * @param list<string>      $pages      Detail page slugs that mark this item current.
	 */
	public function __construct(
		public readonly string $key,
		public readonly string $slug,
		private Closure $label,
		public readonly string $icon,
		public readonly string $capability,
		public readonly array $pages = array(),
	) {}

	/**
	 * The label, translated now.
	 */
	public function label(): string {
		return ( $this->label )();
	}

	/**
	 * Whether the page is this item's own or one of its detail pages.
	 *
	 * @param string $page An admin page slug.
	 */
	public function owns( string $page ): bool {
		return $page === $this->slug || in_array( $page, $this->pages, true );
	}
}
