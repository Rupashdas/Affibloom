<?php
/**
 * A lock that lets one request at a time run the upgrade.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Upgrade;

/**
 * A lock that lets one request at a time run the upgrade.
 */
interface UpgradeLock {

	/**
	 * Takes the lock. False when another request holds it and it has not expired.
	 */
	public function acquire(): bool;

	/**
	 * Gives the lock back.
	 */
	public function release(): void;
}
