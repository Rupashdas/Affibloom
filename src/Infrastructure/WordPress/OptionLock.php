<?php
/**
 * A lock as one option holding its expiry time.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\Upgrade\UpgradeLock;

/**
 * A lock as one option holding its expiry time, as Lasso's lasso_db_migration_lock_v2: add_option() only creates a
 * missing option, so two requests cannot both take it. A lock left by a request that died expires and is cleared.
 */
final class OptionLock implements UpgradeLock {

	/**
	 * Builds the lock.
	 *
	 * @param string $name    The option's name.
	 * @param int    $seconds How long a lock lasts if its request dies before releasing it.
	 */
	public function __construct( private string $name, private int $seconds ) {}

	/**
	 * Takes the lock. False when another request holds it and it has not expired.
	 */
	public function acquire(): bool {
		if ( $this->add() ) {
			return true;
		}
		if ( (int) get_option( $this->name ) >= time() ) {
			return false;
		}
		delete_option( $this->name );
		return $this->add();
	}

	/**
	 * Gives the lock back.
	 */
	public function release(): void {
		delete_option( $this->name );
	}

	/**
	 * Creates the option with this lock's expiry, not autoloaded; false when it exists.
	 */
	private function add(): bool {
		return add_option( $this->name, time() + $this->seconds, '', false );
	}
}
