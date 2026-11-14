<?php
/**
 * The plugin's install records as options.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\PluginState;

/**
 * The plugin's install records as options: affibloom_version (lasso_version) and affibloom_activated_at
 * (lasso_plugin_activated_at, not autoloaded, as Lasso stores it).
 */
final class OptionPluginState implements PluginState {

	public const VERSION      = 'affibloom_version';
	public const ACTIVATED_AT = 'affibloom_activated_at';

	/**
	 * Stores the plugin version and the time of this activation.
	 *
	 * @param string $version The running plugin version.
	 */
	public function record_activation( string $version ): void {
		update_option( self::VERSION, $version );
		update_option( self::ACTIVATED_AT, time(), false );
	}

	/**
	 * Rebuilds the site's rewrite rules on the next request.
	 */
	public function flush_rewrite_rules(): void {
		flush_rewrite_rules();
	}
}
