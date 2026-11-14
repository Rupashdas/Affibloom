<?php
/**
 * The plugin's scheduled events, as one set.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application;

/**
 * The plugin's scheduled events, as one set.
 */
interface Schedules {

	/**
	 * Removes every scheduled event of every Affibloom hook.
	 */
	public function clear(): void;
}
