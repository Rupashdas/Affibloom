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
 * The Affibloom admin menu: Lasso's place (after Pages) and submenu order (F38 R10-R12, D4, D5), from the navigation
 * the header uses too.
 */
final class AdminMenu implements Module {

	public const SLUG = 'affibloom';

	/**
	 * Lasso's menu position (its post type's menu_position). Pages has 20 too, so WordPress puts Affibloom right after it.
	 */
	private const POSITION = 20;

	/**
	 * What each listed page prints, by slug.
	 *
	 * @var array<string, callable>
	 */
	private array $screens;

	/**
	 * Builds the menu.
	 *
	 * @param AdminNavigation          $navigation The screens in Lasso's order (Task ON1).
	 * @param DashboardScreen          $dashboard  The Dashboard screen.
	 * @param ProductScreen            $product    The product page (Task 14A).
	 * @param SettingsScreen           $settings   The Settings screen.
	 * @param GroupsScreen             $groups     The Groups list (Task G1).
	 * @param GroupScreen              $group      One group, or a new one (Task G1).
	 * @param FieldsScreen             $fields     The Fields list (Task FL1).
	 * @param FieldScreen              $field      One field, or a new one (Task FL2).
	 * @param OpportunitiesScreen|null $opportunities Opportunities (F22, Task O1).
	 * @param TablesScreen|null        $tables     The Tables list (F25, Task T1).
	 * @param TableScreen|null         $table      A table's builder (F25, Task T3).
	 * @param LogsScreen|null          $logs       The hidden Logs page (F37, Task TL3).
	 * @param DbStatusScreen|null      $db_status  The hidden DB Status page (F37, Task TL4).
	 * @param SetupScreen|null         $setup      The setup wizard (F38, Task ON6).
	 * @param ExtensionScreen|null     $extensions Pages helper plugins add through the extension API (Task LM1).
	 */
	public function __construct(
		private AdminNavigation $navigation,
		DashboardScreen $dashboard,
		private ProductScreen $product,
		SettingsScreen $settings,
		GroupsScreen $groups,
		private GroupScreen $group,
		FieldsScreen $fields,
		private FieldScreen $field,
		?OpportunitiesScreen $opportunities = null,
		?TablesScreen $tables = null,
		private ?TableScreen $table = null,
		private ?LogsScreen $logs = null,
		private ?DbStatusScreen $db_status = null,
		private ?SetupScreen $setup = null,
		private ?ExtensionScreen $extensions = null,
	) {
		$this->screens = array(
			self::SLUG           => array( $dashboard, 'render' ),
			GroupsScreen::SLUG   => array( $groups, 'render' ),
			FieldsScreen::SLUG   => array( $fields, 'render' ),
			SettingsScreen::SLUG => array( $settings, 'render' ),
		);
		if ( null !== $opportunities ) {
			$this->screens[ OpportunitiesScreen::SLUG ] = array( $opportunities, 'render' );
		}
		if ( null !== $tables ) {
			$this->screens[ TablesScreen::SLUG ] = array( $tables, 'render' );
		}
	}

	/**
	 * The admin page being shown, '' outside one (`?page=`, which WordPress also reads to pick the page).
	 */
	public static function requested_page(): string {
		return isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- which page is shown, no action behind it.
	}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_pages' ) );
		add_action( 'admin_head', array( $this, 'hide_detail_pages' ) );
		add_filter( 'parent_file', array( $this, 'parent_file' ) );
		add_filter( 'submenu_file', array( $this, 'submenu_file' ) );
		// After core_update_footer (priority 10), which prints WordPress's version.
		add_filter( 'update_footer', array( $this, 'update_footer' ), 11 );
	}

	/**
	 * Adds the top-level menu and the listed pages in Lasso's order, then the detail pages, which are registered so
	 * WordPress checks the capability and runs their guards, and are taken off the list on admin_head.
	 */
	public function add_pages(): void {
		add_menu_page(
			__( 'Affibloom', 'affibloom' ),
			__( 'Affibloom', 'affibloom' ),
			Capabilities::PRODUCTS,
			self::SLUG,
			$this->screens[ self::SLUG ],
			'dashicons-cart',
			self::POSITION
		);
		// A helper's page (Task LM1) joins on plugins_loaded, after this menu was built.
		$screens = $this->screens + ( null === $this->extensions ? array() : $this->extensions->screens() );
		foreach ( $this->navigation->menu() as $item ) {
			add_submenu_page( self::SLUG, $item->label(), $item->label(), $item->capability, $item->slug, $screens[ $item->slug ] );
		}

		// Lasso's title for a product page (R13, D6); its guard runs on the load hook, before any output, so a missing
		// product can still be redirected.
		$this->add_detail_page( __( 'Link Details', 'affibloom' ), ProductScreen::SLUG, array( $this->product, 'render' ), array( $this->product, 'guard' ) );
		$this->add_detail_page( __( 'Group', 'affibloom' ), GroupScreen::SLUG, array( $this->group, 'render' ), array( $this->group, 'guard' ) );
		$this->add_detail_page( __( 'Field', 'affibloom' ), FieldScreen::SLUG, array( $this->field, 'render' ), array( $this->field, 'guard' ) );
		if ( null !== $this->table ) {
			$this->add_detail_page( __( 'Table', 'affibloom' ), TablesScreen::TABLE_SLUG, array( $this->table, 'render' ), array( $this->table, 'guard' ) );
		}
		// Lasso's hidden Settings pages, reached by their address (F37 R1, R9).
		if ( null !== $this->logs ) {
			$this->add_detail_page( __( 'Logs', 'affibloom' ), LogsScreen::SLUG, array( $this->logs, 'render' ), static function (): void {}, Capabilities::SETTINGS );
		}
		if ( null !== $this->setup ) {
			$this->add_detail_page( __( 'Setup', 'affibloom' ), SetupScreen::SLUG, array( $this->setup, 'render' ), static function (): void {}, Capabilities::SETTINGS );
		}
		if ( null !== $this->db_status ) {
			$this->add_detail_page( __( 'DB Status', 'affibloom' ), DbStatusScreen::SLUG, array( $this->db_status, 'render' ), static function (): void {}, Capabilities::SETTINGS );
		}
	}

	/**
	 * The detail pages are reached from their lists, never from the menu, as Lasso's. Removed after admin_menu, so
	 * they stay registered (reachable, capability-checked) but are not listed.
	 */
	public function hide_detail_pages(): void {
		remove_submenu_page( self::SLUG, ProductScreen::SLUG );
		remove_submenu_page( self::SLUG, GroupScreen::SLUG );
		remove_submenu_page( self::SLUG, FieldScreen::SLUG );
		remove_submenu_page( self::SLUG, TablesScreen::TABLE_SLUG );
		remove_submenu_page( self::SLUG, LogsScreen::SLUG );
		remove_submenu_page( self::SLUG, DbStatusScreen::SLUG );
		remove_submenu_page( self::SLUG, SetupScreen::SLUG );
	}

	/**
	 * Keeps Affibloom's menu open on every Affibloom page.
	 *
	 * @param string|null $parent_file The menu WordPress would open.
	 */
	public function parent_file( ?string $parent_file ): ?string {
		return null === $this->navigation->current( self::requested_page() ) ? $parent_file : self::SLUG;
	}

	/**
	 * Marks the list a detail page belongs to (D5): Dashboard on a product page, Groups on a group, Fields on a field.
	 *
	 * @param string|null $submenu_file The item WordPress would mark.
	 */
	public function submenu_file( ?string $submenu_file ): ?string {
		return $this->navigation->current( self::requested_page() )->slug ?? $submenu_file;
	}

	/**
	 * Blanks WordPress's version line on Affibloom's pages only (N6); Affibloom's own footer shows its version (D7).
	 *
	 * @param string $content The footer's right-hand text.
	 */
	public function update_footer( string $content ): string {
		return null === $this->navigation->current( self::requested_page() ) ? $content : '';
	}

	/**
	 * Registers a page that is reached from a list and not listed itself.
	 *
	 * @param string   $title  The browser title.
	 * @param string   $slug   The page slug.
	 * @param callable $render What the page prints.
	 * @param callable $guard  Runs before any output.
	 * @param string   $capability Who may open it.
	 */
	private function add_detail_page( string $title, string $slug, callable $render, callable $guard, string $capability = Capabilities::PRODUCTS ): void {
		$hook = add_submenu_page( self::SLUG, $title, $title, $capability, $slug, $render );
		if ( false !== $hook ) {
			add_action( 'load-' . $hook, $guard );
		}
	}
}
