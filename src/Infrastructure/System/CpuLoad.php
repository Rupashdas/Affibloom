<?php
/**
 * How busy the server's processors are.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\System;

use Closure;

/**
 * The server's load as a percentage of its processors (F21 D12; Lasso's cpu_threshold): the one-minute load average
 * divided by the number of processors. Null where PHP cannot tell, so the CPU threshold is then not applied: Windows has
 * no `sys_getloadavg`, and a host that hides `/proc/cpuinfo` (CloudLinux, seen on staging 2026-11-24) gives no
 * processor count while its load average is the whole server's.
 */
final class CpuLoad {

	/**
	 * Builds the reader.
	 *
	 * @param string                  $cpuinfo Where Linux lists its processors.
	 * @param (Closure(): mixed)|null $loadavg The load averages; PHP's `sys_getloadavg` when null.
	 */
	public function __construct( private string $cpuinfo = '/proc/cpuinfo', private ?Closure $loadavg = null ) {}

	/**
	 * The load in percent, or null when it cannot be read.
	 */
	public function percent(): ?float {
		$load = $this->load_averages();
		if ( ! is_array( $load ) || ! isset( $load[0] ) ) {
			return null;
		}
		$processors = $this->processors();
		if ( 0 === $processors ) {
			return null;
		}

		return 100 * (float) $load[0] / $processors;
	}

	/**
	 * The load averages, or false when PHP cannot read them.
	 *
	 * @return mixed
	 */
	private function load_averages(): mixed {
		if ( null !== $this->loadavg ) {
			return ( $this->loadavg )();
		}

		return function_exists( 'sys_getloadavg' ) ? sys_getloadavg() : false;
	}

	/**
	 * How many processors Linux lists, 0 when the list cannot be read.
	 */
	private function processors(): int {
		if ( ! is_readable( $this->cpuinfo ) ) {
			return 0;
		}
		$info = (string) file_get_contents( $this->cpuinfo ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local system file.

		return (int) preg_match_all( '/^processor\s*:/m', $info );
	}
}
