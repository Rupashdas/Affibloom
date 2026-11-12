<?php
/**
 * Where the current bulk add is kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * Where the current bulk add is kept. There is one at a time, as in Lasso.
 */
interface BulkRunRepository {

	/**
	 * The current or last run, or null when there has been none.
	 */
	public function get(): ?BulkRun;

	/**
	 * Replaces the stored run.
	 *
	 * @param BulkRun $run The run.
	 */
	public function save( BulkRun $run ): void;
}
