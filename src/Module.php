<?php
/**
 * Something that hooks itself into WordPress.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom;

/**
 * Something that hooks itself into WordPress.
 */
interface Module {

	/**
	 * Adds the module's actions and filters. Called once, while the plugin boots.
	 */
	public function register(): void;
}
