<?php
/**
 * When the geni.us clean-up job runs next.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

/**
 * When the geni.us clean-up job runs next (C1P): a background run that goes on below a product ID, or none.
 */
interface GeniusCleanupSchedule {

	/**
	 * Queues a run for the geni.us products below this ID, now; 0 starts from the newest.
	 *
	 * @param int $before The lowest product ID done so far; 0 for none.
	 */
	public function next( int $before ): void;

	/**
	 * Removes any queued run.
	 */
	public function cancel(): void;
}
