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
	 * The plugin version this site last ran; '' before the first activation.
	 */
	public function stored_version(): string;

	/**
	 * Stores the running plugin version.
	 *
	 * @param string $version The running plugin version.
	 */
	public function record_version( string $version ): void;

	/**
	 * Stores the time of this activation.
	 */
	public function record_activation_time(): void;

	/**
	 * Rebuilds the site's rewrite rules on the next request.
	 */
	public function flush_rewrite_rules(): void;
}
