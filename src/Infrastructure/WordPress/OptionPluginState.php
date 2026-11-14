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
 * The plugin's install records as options: affibloom_version (lasso_version, autoloaded: it is read on every
 * request) and affibloom_activated_at (lasso_plugin_activated_at, not autoloaded, as Lasso stores it).
 */
final class OptionPluginState implements PluginState {

	public const VERSION      = 'affibloom_version';
	public const ACTIVATED_AT = 'affibloom_activated_at';

	/**
	 * The plugin version this site last ran; '' before the first activation.
	 */
	public function stored_version(): string {
		return (string) get_option( self::VERSION, '' );
	}

	/**
	 * Stores the running plugin version.
	 *
	 * @param string $version The running plugin version.
	 */
	public function record_version( string $version ): void {
		update_option( self::VERSION, $version );
	}

	/**
	 * Stores the time of this activation.
	 */
	public function record_activation_time(): void {
		update_option( self::ACTIVATED_AT, time(), false );
	}

	/**
	 * Rebuilds the site's rewrite rules on the next request.
	 */
	public function flush_rewrite_rules(): void {
		flush_rewrite_rules();
	}
}
