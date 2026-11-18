<?php
namespace Affibloom\Tests\Unit\Application\Jobs;

use Affibloom\Application\Jobs\CpuGuard;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Whether a background batch waits for the server (Task 32C; F11 R7, R33, D21; edge cases 11, 12): at or above "the
 * maximum CPU level" it waits; an unreadable load or a threshold above 100 never waits.
 */
final class CpuGuardTest extends TestCase {

	private static function guard( ?float $load, int $threshold ): CpuGuard {
		return new CpuGuard( static fn(): ?float => $load, new InMemorySettingsRepository( array( 'cpu_threshold' => $threshold ) ) );
	}

	public function test_a_server_at_or_over_the_threshold_is_busy(): void {
		$this->assertTrue( self::guard( 80.0, 80 )->busy() );
		$this->assertTrue( self::guard( 95.5, 65 )->busy() );
		$this->assertFalse( self::guard( 64.9, 65 )->busy() );
	}

	public function test_an_unreadable_load_never_waits(): void {
		$this->assertFalse( self::guard( null, 50 )->busy() );
	}

	public function test_a_threshold_above_100_means_never_throttled(): void {
		$this->assertFalse( self::guard( 100.0, 150 )->busy() );
	}

	public function test_the_spacing_is_the_cron_interval_in_seconds(): void {
		$this->assertSame( 7200, CpuGuard::spacing( new InMemorySettingsRepository( array( 'cron_interval' => 2 ) ) ) );
		$this->assertSame( 0, CpuGuard::spacing( new InMemorySettingsRepository() ) );
		$this->assertSame( 10800, CpuGuard::spacing( new InMemorySettingsRepository( array( 'cron_interval' => 9 ) ) ) );
	}
}
