<?php
/**
 * Admin CSS and JavaScript, on Affibloom screens only.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Module;

/**
 * Admin CSS and JavaScript, on Affibloom screens only.
 */
final class AdminAssets implements Module {

	/**
	 * Builds the module.
	 *
	 * @param string $plugin_url URL of the plugin folder, with a trailing slash.
	 * @param string $version    Plugin version, used to bust caches.
	 */
	public function __construct( private string $plugin_url, private string $version ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'admin_body_class', array( $this, 'framed' ) );
	}

	/**
	 * The product page inside the editor's Quick Detail (F4 R26, Task 23G): with `affibloom_frame=1` its body gets
	 * `affibloom-framed`, which shows only the form and the live preview (admin.css). A look only: the page, its checks and
	 * its scripts are the product page's.
	 *
	 * @param string $classes The body's classes.
	 */
	public function framed( string $classes ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a display switch for a screen the capability already guards.
		if ( isset( $_GET['page'], $_GET['affibloom_frame'] ) && ProductScreen::SLUG === $_GET['page'] ) {
			$classes .= ' affibloom-framed';
		}

		return $classes;
	}

	/**
	 * Enqueues assets when the current screen is one of ours.
	 *
	 * @param string $hook_suffix Current admin page hook, e.g. "toplevel_page_affibloom".
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( ! str_contains( $hook_suffix, 'affibloom' ) ) {
			return;
		}
		// Meadow's bundled fonts (Task MD1), before the styles that name them; served from the plugin, no other host.
		wp_enqueue_style( 'affibloom-fonts', $this->plugin_url . 'assets/admin/fonts.css', array(), $this->version );
		wp_enqueue_style( 'affibloom-admin', $this->plugin_url . 'assets/admin/admin.css', array( 'affibloom-fonts' ), $this->version );
		// The General tab's own parts, and "Disable Help Tooltips" (Tasks 9B-9K), which reaches every screen.
		wp_enqueue_style( 'affibloom-settings-general', $this->plugin_url . 'assets/admin/settings-general.css', array( 'affibloom-admin' ), $this->version );
		// The Fields screens and the product page's field rows (Tasks FL1-FL5).
		wp_enqueue_style( 'affibloom-fields', $this->plugin_url . 'assets/admin/fields.css', array( 'affibloom-admin' ), $this->version );
		// Help tips, Save-when-changed, modals and "Add A New Link". wp-api-fetch carries the REST root and nonce in wp-admin.
		wp_enqueue_script( 'affibloom-admin', $this->plugin_url . 'assets/admin/admin.js', array( 'wp-api-fetch' ), $this->version, true );
		// The bulk Amazon sync banner in the notice region of every screen (Task C1I); it stops at once without the banner.
		wp_enqueue_script( 'affibloom-amazon-bulk-sync', $this->plugin_url . 'assets/admin/amazon-bulk-sync.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
		// The header's Automations on every screen (Task ON3).
		wp_enqueue_script( 'affibloom-shell', $this->plugin_url . 'assets/admin/shell.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );

		// The Dashboard's own script: selection (Task 13C), the list without reloads (13D), filters and views (13E), and
		// the quick edit (16A), whose image comes from the media library and whose toast and save dialog are admin.js's.
		if ( 'toplevel_page_' . AdminMenu::SLUG === $hook_suffix ) {
			wp_enqueue_media();
			wp_enqueue_script( 'affibloom-dashboard', $this->plugin_url . 'assets/admin/dashboard.js', array( 'affibloom-admin', 'wp-api-fetch', 'affibloom-group-picker' ), $this->version, true );
		}

		// The group picker: the product page, the Dashboard's quick edit and bulk Add to Group (Tasks G3, G5).
		if ( 'toplevel_page_' . AdminMenu::SLUG === $hook_suffix || str_contains( $hook_suffix, ProductScreen::SLUG ) ) {
			wp_enqueue_script( 'affibloom-group-picker', $this->plugin_url . 'assets/admin/group-picker.js', array( 'affibloom-admin' ), $this->version, true );
		}

		// The Groups list in place, and the new-group form (Task G1). The hook suffix of both pages holds "affibloom-group".
		if ( str_contains( $hook_suffix, GroupScreen::SLUG ) ) {
			wp_enqueue_script( 'affibloom-groups', $this->plugin_url . 'assets/admin/groups.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
			// A group's Links tab: its order, Remove and "Add to Group" (Task G4); it stops at once on any other page.
			wp_enqueue_script( 'affibloom-drag-rows', $this->plugin_url . 'assets/admin/drag-rows.js', array(), $this->version, true );
			wp_enqueue_script( 'affibloom-product-search', $this->plugin_url . 'assets/admin/product-search.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
			wp_enqueue_script( 'affibloom-group-members', $this->plugin_url . 'assets/admin/group-members.js', array( 'affibloom-admin', 'wp-api-fetch', 'affibloom-drag-rows', 'affibloom-product-search' ), $this->version, true );
		}

		// The Fields list in place (Task FL1) and the field form (Task FL2); the hook suffix of both Fields pages holds
		// "affibloom-field".
		if ( str_contains( $hook_suffix, FieldScreen::SLUG ) ) {
			wp_enqueue_script( 'affibloom-in-place-list', $this->plugin_url . 'assets/admin/in-place-list.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
			wp_enqueue_script( 'affibloom-fields', $this->plugin_url . 'assets/admin/fields.js', array( 'affibloom-in-place-list' ), $this->version, true );
		}

		// Each settings tab's own controls.
		$tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chooses what to load only.
		$scripts = array(
			'display' => 'display-settings',
			'amazon'  => 'amazon-settings',
		);
		// Every Settings tab warns before unsaved changes are lost (Task 9E, decision C7), and General has its own rules
		// (Tasks 9D, 9F-9H). The General tab is the one without a tab in the address.
		if ( str_contains( $hook_suffix, SettingsScreen::SLUG ) ) {
			wp_enqueue_script( 'affibloom-settings-guard', $this->plugin_url . 'assets/admin/settings-guard.js', array( 'affibloom-admin' ), $this->version, true );
			// Save Changes and Reset Settings over REST behind "Updating Your Site", staying on the page (F9 R41; deviation 143).
			wp_enqueue_script( 'affibloom-settings-save', $this->plugin_url . 'assets/admin/settings-save.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
			if ( '' === $tab || 'general' === $tab ) {
				wp_enqueue_script( 'affibloom-general-settings', $this->plugin_url . 'assets/admin/general-settings.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
			}
			// Amazon: the Auto-Monetization switch's checks and "Enable Auto-Amazon" (F35 R22; Task AM3).
			if ( 'amazon' === $tab ) {
				wp_enqueue_script( 'affibloom-amazon-auto-monetize', $this->plugin_url . 'assets/admin/amazon-auto-monetize.js', array( 'affibloom-admin' ), $this->version, true );
			}
		}
		// The icon picker on button texts: Settings → Display and the product page (F28, Task 14G).
		if ( 'display' === $tab || str_contains( $hook_suffix, ProductScreen::SLUG ) ) {
			wp_enqueue_script( 'affibloom-icon-picker', $this->plugin_url . 'assets/admin/icon-picker.js', array( 'affibloom-admin' ), $this->version, true );
		}

		// The product page's additional buttons: add, drag, remove (F29, Task 14H); its image from the media library or a
		// pasted link (Task 14B).
		if ( str_contains( $hook_suffix, ProductScreen::SLUG ) ) {
			wp_enqueue_media();
			// Buttons, custom fields and features are put in order by dragging (Task FL5; the user, 2026-11-16).
			wp_enqueue_script( 'affibloom-drag-rows', $this->plugin_url . 'assets/admin/drag-rows.js', array(), $this->version, true );
			wp_enqueue_script( 'affibloom-product', $this->plugin_url . 'assets/admin/product.js', array( 'affibloom-admin', 'affibloom-drag-rows' ), $this->version, true );
			// Custom fields: the rows and the "Add Field" dialog (Task 14C).
			wp_enqueue_script( 'affibloom-product-fields', $this->plugin_url . 'assets/admin/product-fields.js', array( 'affibloom-admin', 'wp-api-fetch', 'affibloom-drag-rows' ), $this->version, true );
			wp_enqueue_script( 'affibloom-product-buttons', $this->plugin_url . 'assets/admin/product-buttons.js', array( 'affibloom-admin', 'affibloom-icon-picker', 'affibloom-drag-rows' ), $this->version, true );
			// Save Changes: the form through PATCH /products/{id}, its dialog and toasts (Task 15A).
			wp_enqueue_script( 'affibloom-product-save', $this->plugin_url . 'assets/admin/product-save.js', array( 'affibloom-admin', 'wp-api-fetch', 'affibloom-group-picker' ), $this->version, true );
			// The Variants tab: its search and Copy (F27, Task V1).
			wp_enqueue_script( 'affibloom-variants', $this->plugin_url . 'assets/admin/variants.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
			// The Locations tab: its list in place and the group rows (F12, Task 36B).
			// The Opportunities badge follows a save (F22 R34; Task O5).
			wp_enqueue_script( 'affibloom-product-opportunity-count', $this->plugin_url . 'assets/admin/product-opportunity-count.js', array( 'wp-api-fetch' ), $this->version, true );
			// The Opportunities tab: its list in place and the preview (F22 R35, Task O5).
			if ( 'opportunities' === $tab ) {
				wp_enqueue_script( 'affibloom-in-place-list', $this->plugin_url . 'assets/admin/in-place-list.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
				wp_enqueue_script( 'affibloom-location-preview', $this->plugin_url . 'assets/admin/location-preview.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
				// Monetize's product search (Task O10), shared with Add to Group.
				wp_enqueue_script( 'affibloom-product-search', $this->plugin_url . 'assets/admin/product-search.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
				wp_enqueue_script( 'affibloom-opportunities', $this->plugin_url . 'assets/admin/opportunities.js', array( 'affibloom-admin', 'affibloom-in-place-list', 'affibloom-location-preview', 'affibloom-product-search' ), $this->version, true );
			}
			if ( 'locations' === $tab ) {
				wp_enqueue_script( 'affibloom-in-place-list', $this->plugin_url . 'assets/admin/in-place-list.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
				wp_enqueue_script( 'affibloom-location-preview', $this->plugin_url . 'assets/admin/location-preview.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
				wp_enqueue_script( 'affibloom-product-locations', $this->plugin_url . 'assets/admin/product-locations.js', array( 'affibloom-in-place-list', 'affibloom-location-preview' ), $this->version, true );
			}
		}

		// Opportunities (F22, Task O1): the lists in place, the preview and the intro boxes.
		if ( str_contains( $hook_suffix, OpportunitiesScreen::SLUG ) ) {
			wp_enqueue_script( 'affibloom-in-place-list', $this->plugin_url . 'assets/admin/in-place-list.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
			wp_enqueue_script( 'affibloom-location-preview', $this->plugin_url . 'assets/admin/location-preview.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
			// Monetize's product search (Task O10), shared with Add to Group.
			wp_enqueue_script( 'affibloom-product-search', $this->plugin_url . 'assets/admin/product-search.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
			wp_enqueue_script( 'affibloom-opportunities', $this->plugin_url . 'assets/admin/opportunities.js', array( 'affibloom-admin', 'affibloom-in-place-list', 'affibloom-location-preview', 'affibloom-product-search' ), $this->version, true );
		}

		// The "Button icons" card chooses its image from the media library (F28, Task 14F).
		if ( 'display' === $tab ) {
			wp_enqueue_media();
		}
		// After admin.js: the Display tab's icon card takes its media frame from there (Task 16A).
		if ( isset( $scripts[ $tab ] ) ) {
			wp_enqueue_script( 'affibloom-' . $scripts[ $tab ], $this->plugin_url . 'assets/admin/' . $scripts[ $tab ] . '.js', array( 'affibloom-admin' ), $this->version, true );
		}
	}
}
