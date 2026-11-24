<?php
namespace Affibloom\Tests\Unit\Infrastructure\System;

use Affibloom\Infrastructure\System\CpuLoad;
use Affibloom\Tests\Unit\TestCase;

/**
 * The server's load for the CPU guard (Task 32C; F21 D12).
 */
final class CpuLoadTest extends TestCase {

	/** A processor list written for one test. */
	private string $cpuinfo = '';

	protected function tearDown(): void {
		if ( '' !== $this->cpuinfo && is_file( $this->cpuinfo ) ) {
			unlink( $this->cpuinfo );
		}
		parent::tearDown();
	}

	/**
	 * A cpuinfo file listing this many processors.
	 *
	 * @param int $processors How many.
	 */
	private function cpuinfo( int $processors ): string {
		$this->cpuinfo = (string) tempnam( sys_get_temp_dir(), 'cpuinfo' );
		file_put_contents( $this->cpuinfo, str_repeat( "processor\t: 0\nmodel name\t: x\n\n", $processors ) );

		return $this->cpuinfo;
	}

	public function test_the_load_is_the_one_minute_average_over_the_processors(): void {
		$load = new CpuLoad( $this->cpuinfo( 4 ), static fn(): array => array( 2.0, 1.0, 1.0 ) );

		self::assertSame( 50.0, $load->percent() );
	}

	public function test_a_host_that_hides_its_processors_gives_no_load(): void {
		// The staging rehearsal (2026-11-24): CloudLinux hides /proc/cpuinfo while the load average is the server's (23),
		// so one assumed processor made every batch wait for good.
		$load = new CpuLoad( sys_get_temp_dir() . '/affibloom-no-cpuinfo', static fn(): array => array( 23.34, 22.94, 23.52 ) );

		self::assertNull( $load->percent() );
	}

	public function test_a_host_without_a_load_average_gives_no_load(): void {
		self::assertNull( ( new CpuLoad( $this->cpuinfo( 2 ), static fn(): bool => false ) )->percent() );
	}
}
