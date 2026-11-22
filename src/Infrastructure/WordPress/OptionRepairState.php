<?php
/**
 * How often the tables were repaired, in an option (Task TL4).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\Schema\RepairState;

/**
 * How often the tables were repaired, in `affibloom_repair` (F37 R12; Task TL4), not autoloaded: read only after an
 * SQL error.
 */
final class OptionRepairState implements RepairState {

	/** The option. */
	public const OPTION = 'affibloom_repair';

	/**
	 * The repairs so far and the time of the last one.
	 *
	 * @return array{count: int, last: int}
	 */
	public function get(): array {
		$stored = get_option( self::OPTION, array() );
		$stored = is_array( $stored ) ? $stored : array();

		return array(
			'count' => (int) ( $stored['count'] ?? 0 ),
			'last'  => (int) ( $stored['last'] ?? 0 ),
		);
	}

	/**
	 * Records a repair.
	 *
	 * @param int $count The repairs so far.
	 * @param int $last  The last one's time.
	 */
	public function save( int $count, int $last ): void {
		update_option(
			self::OPTION,
			array(
				'count' => $count,
				'last'  => $last,
			),
			false
		);
	}
}
