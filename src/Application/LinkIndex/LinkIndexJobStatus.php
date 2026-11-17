<?php
/**
 * The link index rebuild as an Automations job.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkIndex;

use Affibloom\Application\Shell\JobStatus;
use Affibloom\Domain\LinkIndex\RebuildStateRepository;
use Affibloom\Domain\Shell\JobProgress;

/**
 * "Scan all links" (F38 R42: Lasso's name for its full scan, which the rebuild is; Task 35F): posts indexed of posts.
 */
final class LinkIndexJobStatus implements JobStatus {

	/**
	 * Builds the status.
	 *
	 * @param RebuildStateRepository $state Where the rebuild stands.
	 */
	public function __construct( private RebuildStateRepository $state ) {}

	/**
	 * The rebuild's progress while it runs.
	 */
	public function progress(): ?JobProgress {
		$state = $this->state->get();

		return $state->running() ? new JobProgress( 'Scan all links', $state->done, $state->total ) : null;
	}
}
