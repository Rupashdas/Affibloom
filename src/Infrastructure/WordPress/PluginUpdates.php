<?php
/**
 * WordPress's list of plugin updates, for Affibloom.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\Shell\UpdateSource;

/**
 * WordPress's list of plugin updates (the update_plugins transient, through get_plugin_updates()), for Affibloom.
 */
final class PluginUpdates implements UpdateSource {

	/**
	 * Builds the source.
	 *
	 * @param string $plugin Affibloom's plugin file, as WordPress names it (affibloom/affibloom.php).
	 */
	public function __construct( private string $plugin ) {}

	/**
	 * Whether WordPress lists an update of Affibloom and the current user may install it.
	 */
	public function available(): bool {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return false;
		}
		if ( ! function_exists( 'get_plugin_updates' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}

		return isset( get_plugin_updates()[ $this->plugin ] );
	}

	/**
	 * The link WordPress's Plugins screen uses for "update now".
	 */
	public function update_url(): string {
		return wp_nonce_url(
			self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . rawurlencode( $this->plugin ) ),
			'upgrade-plugin_' . $this->plugin
		);
	}
}
