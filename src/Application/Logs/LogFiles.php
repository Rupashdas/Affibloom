<?php
/**
 * The stored logs, to read and to prune (Task TL3).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Logs;

use Affibloom\Domain\Logs\LogType;

/**
 * The stored logs, to read and to prune (F37 R3, R6; Task TL3).
 */
interface LogFiles {

	/** Days a log is kept (Lasso's clean-up). */
	public const KEEP_DAYS = 14;

	/**
	 * The newest log of a type, as text, or '' when there is none.
	 *
	 * @param LogType $type Which log.
	 */
	public function newest( LogType $type ): string;

	/**
	 * Deletes the logs older than KEEP_DAYS.
	 *
	 * @param int $now The time, as a Unix timestamp.
	 * @return int How many files were deleted.
	 */
	public function prune( int $now ): int;
}
