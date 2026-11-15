<?php
/**
 * Where the bulk Amazon sync run is kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * Where the bulk Amazon sync run is kept: one run at a time, the last one kept for its summary.
 */
interface BulkSyncRunRepository {

	/**
	 * The stored run, or null.
	 */
	public function get(): ?BulkSyncRun;

	/**
	 * Replaces the stored run.
	 *
	 * @param BulkSyncRun $run The run.
	 */
	public function save( BulkSyncRun $run ): void;
}
