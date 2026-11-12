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
	 */
	public function __construct( private DashboardScreen $dashboard ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_pages' ) );
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
	}
}
