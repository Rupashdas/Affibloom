<?php
/**
 * The link check in the header's background jobs.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkHealth;

use Affibloom\Application\Shell\JobStatus;
use Affibloom\Domain\LinkHealth\CheckPassRepository;
use Affibloom\Domain\Shell\JobProgress;

/**
 * "Checking for broken links" (F11 R36, D23; Lasso's Verbiage name; Task 32E): while the day's pass has products
 * queued, its products checked of those queued, in the header's Automations list (ON3).
 */
final class LinkCheckJobStatus implements JobStatus {

	/**
	 * Builds the status.
	 *
	 * @param CheckPassRepository $passes The daily pass.
	 */
	public function __construct( private CheckPassRepository $passes ) {}

	/**
	 * The pass's progress while it runs.
	 */
	public function progress(): ?JobProgress {
		$pass = $this->passes->get();

		return array() === $pass->queue ? null : new JobProgress( 'Checking for broken links', $pass->checked, $pass->total );
	}
}
