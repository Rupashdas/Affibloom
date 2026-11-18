<?php
/**
 * Whether a plugin is active.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress\Builders;

/**
 * Lasso's `get_is_plugin_active()`: the plugin file among the site's (or the network's) active plugins. Read on the
 * front end too, where `is_plugin_active()` is not loaded.
 */
final class ActivePlugins {

	/**
	 * Whether one of these plugin files is active.
	 *
	 * @param string ...$files Plugin files, e.g. "tablepress/tablepress.php".
	 */
	public static function any( string ...$files ): bool {
		$active = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		return array() !== array_intersect( $files, $active );
	}
}
