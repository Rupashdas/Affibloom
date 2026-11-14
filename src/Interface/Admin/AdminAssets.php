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
		wp_enqueue_style( 'affibloom-admin', $this->plugin_url . 'assets/admin/admin.css', array(), $this->version );
		// The General tab's own parts, and "Disable Help Tooltips" (Tasks 9B-9K), which reaches every screen.
		wp_enqueue_style( 'affibloom-settings-general', $this->plugin_url . 'assets/admin/settings-general.css', array( 'affibloom-admin' ), $this->version );
		// Help tips, Save-when-changed, modals and "Add A New Link". wp-api-fetch carries the REST root and nonce in wp-admin.
		wp_enqueue_script( 'affibloom-admin', $this->plugin_url . 'assets/admin/admin.js', array( 'wp-api-fetch' ), $this->version, true );

		// The Dashboard's own script: selection (Task 13C), the list without reloads (13D), filters and views (13E), and
		// the quick edit (16A), whose image comes from the media library and whose toast and save dialog are admin.js's.
		if ( 'toplevel_page_' . AdminMenu::SLUG === $hook_suffix ) {
			wp_enqueue_media();
			wp_enqueue_script( 'affibloom-dashboard', $this->plugin_url . 'assets/admin/dashboard.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
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
			if ( '' === $tab || 'general' === $tab ) {
				wp_enqueue_script( 'affibloom-general-settings', $this->plugin_url . 'assets/admin/general-settings.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
			}
		}
		// The icon picker on button texts: Settings → Display and the product page (F28, Task 14G).
		if ( 'display' === $tab || str_contains( $hook_suffix, ProductScreen::SLUG ) ) {
			wp_enqueue_script( 'affibloom-icon-picker', $this->plugin_url . 'assets/admin/icon-picker.js', array( 'affibloom-admin' ), $this->version, true );
		}

		// The product page's additional buttons: add, move, remove (F29, Task 14H); its image from the media library or a
		// pasted link (Task 14B).
		if ( str_contains( $hook_suffix, ProductScreen::SLUG ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'affibloom-product', $this->plugin_url . 'assets/admin/product.js', array( 'affibloom-admin' ), $this->version, true );
			// Custom fields: the rows and the "Add Field" dialog (Task 14C).
			wp_enqueue_script( 'affibloom-product-fields', $this->plugin_url . 'assets/admin/product-fields.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
			wp_enqueue_script( 'affibloom-product-buttons', $this->plugin_url . 'assets/admin/product-buttons.js', array( 'affibloom-admin', 'affibloom-icon-picker' ), $this->version, true );
			// Save Changes: the form through PATCH /products/{id}, its dialog and toasts (Task 15A).
			wp_enqueue_script( 'affibloom-product-save', $this->plugin_url . 'assets/admin/product-save.js', array( 'affibloom-admin', 'wp-api-fetch' ), $this->version, true );
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
