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
	 * Makes sure the job runs soon, or after a delay (C1Q6b: Amazon's wait). Calling it again while it is waiting does
	 * no harm.
	 *
	 * @param int $delay Seconds to wait first; 0 for now.
	 */
	public function dispatch( int $delay = 0 ): void;
}
