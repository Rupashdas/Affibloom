<?php
/**
 * Bulk add as an Automations job.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use Affibloom\Application\Shell\JobStatus;
use Affibloom\Domain\Product\BulkRunRepository;
use Affibloom\Domain\Shell\JobProgress;
use Closure;

/**
 * "Bulk Add Links" (F38 R42; F18's bulk add, the first job of the board): the links added of the links pasted.
 */
final class BulkAddJobStatus implements JobStatus {

	/**
	 * Builds the status.
	 *
	 * @param BulkRunRepository $runs  The bulk run.
	 * @param Closure           $clock Now, as a Unix time (the run's own staleness rule needs it).
	 */
	public function __construct( private BulkRunRepository $runs, private Closure $clock ) {}

	/**
	 * The run's progress while it is going.
	 */
	public function progress(): ?JobProgress {
		$run = $this->runs->get();
		if ( null === $run || ! $run->is_running( (int) ( $this->clock )() ) ) {
			return null;
		}

		return new JobProgress( 'Bulk Add Links', $run->done, $run->total );
	}
}
