<?php
/**
 * How busy the server's processors are.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\System;

/**
 * The server's load as a percentage of its processors (F21 D12; Lasso's cpu_threshold): the one-minute load average
 * divided by the number of processors. Null where PHP cannot tell (Windows has no `sys_getloadavg`), so the CPU
 * threshold is then not applied.
 */
final class CpuLoad {

	/**
	 * The load in percent, or null when it cannot be read.
	 */
	public function percent(): ?float {
		if ( ! function_exists( 'sys_getloadavg' ) ) {
			return null;
		}
		$load = sys_getloadavg();
		if ( ! is_array( $load ) ) {
			return null;
		}

		return 100 * (float) $load[0] / self::processors();
	}

	/**
	 * How many processors Linux lists, 1 when it cannot be read.
	 */
	private static function processors(): int {
		$info = is_readable( '/proc/cpuinfo' ) ? (string) file_get_contents( '/proc/cpuinfo' ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local system file.

		return max( 1, (int) preg_match_all( '/^processor\s*:/m', $info ) );
	}
}
