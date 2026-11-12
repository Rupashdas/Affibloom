<?php
/**
 * Work that runs after the request that asked for it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

/**
 * Work that runs after the request that asked for it (ADR-4: WP-Cron).
 */
interface BackgroundJob {

	/**
	 * Makes sure the job runs soon. Calling it again while it is waiting does no harm.
	 */
	public function dispatch(): void;
}
