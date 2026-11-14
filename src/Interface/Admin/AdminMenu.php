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
	 * @param GroupsScreen    $groups    The Groups list (Task G1).
	 * @param GroupScreen     $group     One group, or a new one (Task G1).
	 * @param FieldsScreen    $fields    The Fields list (Task FL1).
	 */
	public function __construct(
		private DashboardScreen $dashboard,
		private ProductScreen $product,
		private SettingsScreen $settings,
		private GroupsScreen $groups,
		private GroupScreen $group,
		private FieldsScreen $fields,
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
		// Lasso's nav order: Dashboard, then Groups (F23 D9; Opportunities and Tables come with their areas).
		add_submenu_page(
			self::SLUG,
			__( 'Groups', 'affibloom' ),
			__( 'Groups', 'affibloom' ),
			Capabilities::PRODUCTS,
			GroupsScreen::SLUG,
			array( $this->groups, 'render' )
		);
		// One group's page, like the product page: registered for the capability check and the guard, listed nowhere.
		$group_hook = add_submenu_page(
			self::SLUG,
			__( 'Group', 'affibloom' ),
			__( 'Group', 'affibloom' ),
			Capabilities::PRODUCTS,
			GroupScreen::SLUG,
			array( $this->group, 'render' )
		);
		if ( false !== $group_hook ) {
			add_action( 'load-' . $group_hook, array( $this->group, 'guard' ) );
		}
		// The Fields list, like a group's page, is reached from a link ("Manage Fields"), never from the menu (F26 D13).
		add_submenu_page(
			self::SLUG,
			__( 'Fields', 'affibloom' ),
			__( 'Fields', 'affibloom' ),
			Capabilities::PRODUCTS,
			FieldsScreen::SLUG,
			array( $this->fields, 'render' )
		);
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
	 * The product page, a group's page and the Fields list are reached from their lists, never from the menu, as Lasso's link details
	 * page. Removed after admin_menu, so the pages stay registered (reachable, capability-checked) but are not listed.
	 */
	public function hide_product_page(): void {
		remove_submenu_page( self::SLUG, ProductScreen::SLUG );
		remove_submenu_page( self::SLUG, GroupScreen::SLUG );
		remove_submenu_page( self::SLUG, FieldsScreen::SLUG );
	}
}
