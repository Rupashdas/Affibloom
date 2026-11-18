<?php
/**
 * "Processes Execute Limit": at most two of Affibloom's background batches at once.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Jobs;

use Affibloom\Application\Upgrade\UpgradeLock;
use Affibloom\Domain\Settings\SettingsRepository;

/**
 * Settings → General's support toggle "Processes Execute Limit" (F11 R31, R33, D21; Task 32C): while it is on, at most
 * two of Affibloom's background batches run at once, each holding one of two slots (locks that expire if their request
 * dies); a batch that finds both taken waits. Off, every batch runs (each job still one at a time by its own lock).
 */
final class JobSlots {

	/**
	 * Builds the slots.
	 *
	 * @param list<UpgradeLock>  $slots    The two slots.
	 * @param SettingsRepository $settings The toggle.
	 */
	public function __construct( private array $slots, private SettingsRepository $settings ) {}

	/**
	 * Runs the work in a free slot, or at once while the limit is off.
	 *
	 * @param callable(): mixed $work The batch (its return value is not used).
	 * @return bool False when every slot was taken and the work did not run.
	 */
	public function run( callable $work ): bool {
		if ( ! $this->settings->get()->bool( 'process_limit' ) ) {
			$work();
			return true;
		}
		foreach ( $this->slots as $slot ) {
			if ( ! $slot->acquire() ) {
				continue;
			}
			try {
				$work();
			} finally {
				$slot->release();
			}
			return true;
		}

		return false;
	}
}
