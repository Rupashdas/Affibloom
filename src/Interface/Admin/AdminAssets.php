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
		// Help tips and Save-when-changed, shared by the settings screens.
		wp_enqueue_script( 'affibloom-admin', $this->plugin_url . 'assets/admin/admin.js', array(), $this->version, true );

		// Each settings tab's own controls.
		$tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chooses what to load only.
		$scripts = array(
			'display' => 'display-settings',
			'amazon'  => 'amazon-settings',
		);
		if ( isset( $scripts[ $tab ] ) ) {
			wp_enqueue_script( 'affibloom-' . $scripts[ $tab ], $this->plugin_url . 'assets/admin/' . $scripts[ $tab ] . '.js', array(), $this->version, true );
		}
	}
}
