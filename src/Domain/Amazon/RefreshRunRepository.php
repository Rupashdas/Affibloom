<?php
/**
 * Where the scheduled Amazon refresh run is kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * Where the scheduled refresh run is kept between its passes (C1Q4b).
 */
interface RefreshRunRepository {

	/**
	 * The run in progress, or null.
	 */
	public function get(): ?RefreshRun;

	/**
	 * Keeps the run, or ends it (null).
	 *
	 * @param RefreshRun|null $run The run.
	 */
	public function save( ?RefreshRun $run ): void;
}
