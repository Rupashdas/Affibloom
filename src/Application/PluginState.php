<?php
/**
 * The plugin's own install records, and the site's rewrite rules.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application;

/**
 * The plugin's own install records, and the site's rewrite rules.
 */
interface PluginState {

	/**
	 * Stores the plugin version and the time of this activation.
	 *
	 * @param string $version The running plugin version.
	 */
	public function record_activation( string $version ): void;

	/**
	 * Rebuilds the site's rewrite rules on the next request.
	 */
	public function flush_rewrite_rules(): void;
}
