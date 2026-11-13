<?php
/**
 * The Affibloom admin menu.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Module;

/**
 * The Affibloom admin menu.
 */
final class AdminMenu implements Module {

	public const SLUG = 'affibloom';

	/**
	 * Builds the menu.
	 *
	 * @param DashboardScreen $dashboard The Dashboard screen.
	 * @param ProductScreen   $product   The product page (Task 14A).
	 * @param SettingsScreen  $settings  The Settings screen.
	 */
	public function __construct(
		private DashboardScreen $dashboard,
		private ProductScreen $product,
		private SettingsScreen $settings,
	) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_pages' ) );
		add_action( 'admin_head', array( $this, 'hide_product_page' ) );
	}

	/**
	 * Adds the top-level menu; its first item is renamed "Dashboard", as in Lasso.
	 */
	public function add_pages(): void {
		add_menu_page(
			__( 'Affibloom', 'affibloom' ),
			__( 'Affibloom', 'affibloom' ),
			Capabilities::PRODUCTS,
			self::SLUG,
			array( $this->dashboard, 'render' ),
			'dashicons-cart',
			26
		);
		add_submenu_page(
			self::SLUG,
			__( 'Dashboard', 'affibloom' ),
			__( 'Dashboard', 'affibloom' ),
			Capabilities::PRODUCTS,
			self::SLUG,
			array( $this->dashboard, 'render' )
		);
		// The product page is a submenu page, so WordPress checks the capability and keeps "Affibloom" highlighted;
		// its guard runs on the load hook, before any output, so a missing product can still be redirected.
		$product_hook = add_submenu_page(
			self::SLUG,
			__( 'Product', 'affibloom' ),
			__( 'Product', 'affibloom' ),
			Capabilities::PRODUCTS,
			ProductScreen::SLUG,
			array( $this->product, 'render' )
		);
		if ( false !== $product_hook ) {
			add_action( 'load-' . $product_hook, array( $this->product, 'guard' ) );
		}
		add_submenu_page(
			self::SLUG,
			__( 'Settings', 'affibloom' ),
			__( 'Settings', 'affibloom' ),
			Capabilities::SETTINGS,
			SettingsScreen::SLUG,
			array( $this->settings, 'render' )
		);
	}

	/**
	 * The product page is reached from the Dashboard, never from the menu, as Lasso's link details page. Removed
	 * after admin_menu, so the page stays registered (reachable, capability-checked) but is not listed.
	 */
	public function hide_product_page(): void {
		remove_submenu_page( self::SLUG, ProductScreen::SLUG );
	}
}
