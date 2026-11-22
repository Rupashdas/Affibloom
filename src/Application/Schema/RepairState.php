<?php
/**
 * How often the tables were repaired (Task TL4).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Schema;

/**
 * How often the tables were repaired, and when last (F37 R12; Task TL4).
 */
interface RepairState {

	/**
	 * The repairs so far and the time of the last one.
	 *
	 * @return array{count: int, last: int}
	 */
	public function get(): array;

	/**
	 * Records a repair.
	 *
	 * @param int $count The repairs so far, this one counted.
	 * @param int $last  Its time.
	 */
	public function save( int $count, int $last ): void;
}
