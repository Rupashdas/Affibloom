<?php
/**
 * Every background job that is running.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Shell;

use Affibloom\Domain\Shell\JobProgress;

/**
 * Automations' list (F38 R40-R42, D13; Task ON3): the running jobs of the features that registered one, in their order.
 */
final class JobBoard {

	/**
	 * Builds the board.
	 *
	 * @param list<JobStatus> $jobs Each feature's job.
	 */
	public function __construct( private array $jobs ) {}

	/**
	 * The jobs that are running now.
	 *
	 * @return list<JobProgress>
	 */
	public function running(): array {
		$running = array();
		foreach ( $this->jobs as $job ) {
			$progress = $job->progress();
			if ( null !== $progress ) {
				$running[] = $progress;
			}
		}

		return $running;
	}
}
