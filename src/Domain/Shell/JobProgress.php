<?php
/**
 * One background job that is running.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Shell;

/**
 * A running job as Automations lists it (F38 R40, R42; Task ON3): its name in Lasso's words and how far it is.
 */
final class JobProgress {

	/**
	 * Describes the job.
	 *
	 * @param string $name  Lasso's name for the job ("Bulk Add Links").
	 * @param int    $done  Items done.
	 * @param int    $total Items in all.
	 */
	public function __construct(
		public readonly string $name,
		public readonly int $done,
		public readonly int $total,
	) {}

	/**
	 * "<name> (<done>/<total>)", the popover's line.
	 */
	public function line(): string {
		return $this->name . ' (' . $this->done . '/' . $this->total . ')';
	}
}
