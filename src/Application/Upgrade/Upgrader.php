<?php
/**
 * Runs the pending schema and data steps once (F37 D2).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Upgrade;

/**
 * Runs the pending schema and data steps once (F37 D2), as Lasso's Update_DB: under a lock, so two requests never run
 * them together, and the lock is given back however the run ends.
 */
final class Upgrader {

	/**
	 * Builds the upgrader.
	 *
	 * @param UpgradeLock $lock       One run at a time.
	 * @param Migrations  $migrations The numbered steps.
	 */
	public function __construct( private UpgradeLock $lock, private Migrations $migrations ) {}

	/**
	 * Runs what is pending. Checked before the lock, so a request with nothing to do writes nothing.
	 */
	public function run(): UpgradeResult {
		if ( ! $this->migrations->pending() ) {
			return UpgradeResult::nothing();
		}
		if ( ! $this->lock->acquire() ) {
			return UpgradeResult::locked();
		}

		try {
			return UpgradeResult::done( $this->migrations->run() );
		} catch ( StepFailed $failed ) {
			return UpgradeResult::failed( $failed->step, $failed->getMessage() );
		} finally {
			$this->lock->release();
		}
	}
}
