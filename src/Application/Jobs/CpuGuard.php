<?php
/**
 * Whether background work waits for a busy server, and how far apart its batches run.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Jobs;

use Affibloom\Domain\Settings\SettingsRepository;
use Closure;

/**
 * Settings → General's Performance and support Cron fields as they act on Affibloom's background batches (F11 R7,
 * R31-R33, D21; Task 32C): a server at or above "the maximum CPU level" makes the batch wait (an unreadable load, or a
 * level above 100, never does: edge cases 11, 12); "Time interval (hours)" spaces a job's chained batches that many
 * hours apart (0-3, Lasso's clamp). The link index's batches (F21 D12) and the link check's share it.
 */
final class CpuGuard {

	/** How long a busy server makes a batch wait, in seconds (D21). */
	public const BUSY_WAIT = 60;

	/**
	 * Builds the guard.
	 *
	 * @param Closure(): ?float  $load     The server's load in percent; null when it cannot be read.
	 * @param SettingsRepository $settings The CPU threshold.
	 */
	public function __construct( private Closure $load, private SettingsRepository $settings ) {}

	/**
	 * Whether the server is too busy for a batch now.
	 */
	public function busy(): bool {
		$threshold = $this->settings->get()->int( 'cpu_threshold' );
		$load      = ( $this->load )();

		return null !== $load && $threshold <= 100 && $load >= $threshold;
	}

	/**
	 * Seconds between a job's chained batches: the Cron interval's hours (0-3).
	 *
	 * @param SettingsRepository $settings The settings.
	 */
	public static function spacing( SettingsRepository $settings ): int {
		return max( 0, min( 3, $settings->get()->int( 'cron_interval' ) ) ) * 3600;
	}
}
