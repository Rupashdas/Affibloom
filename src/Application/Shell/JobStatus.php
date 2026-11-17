<?php
/**
 * One feature's background job, for Automations.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Shell;

use Affibloom\Domain\Shell\JobProgress;

/**
 * A feature's background job (F38 D13: a registry each job's feature fills).
 */
interface JobStatus {

	/**
	 * How the job stands while it runs; null when it is not running.
	 */
	public function progress(): ?JobProgress;
}
