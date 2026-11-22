<?php
/**
 * Writes a line to a log (Task TL3).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Logs;

use Affibloom\Domain\Logs\LogType;

/**
 * Writes a line to a log (F37 R4, R5; Task TL3). With "Enable Logs" off only forced lines are written: SQL errors and
 * table repairs, as Lasso forces them.
 */
interface Logger {

	/**
	 * Writes one line.
	 *
	 * @param LogType $type    Which log.
	 * @param string  $message The line.
	 * @param bool    $force   Written even with logging off.
	 */
	public function write( LogType $type, string $message, bool $force = false ): void;
}
