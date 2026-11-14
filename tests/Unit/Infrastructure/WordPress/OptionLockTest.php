<?php
namespace Affibloom\Tests\Unit\Infrastructure\WordPress;

use Affibloom\Infrastructure\WordPress\OptionLock;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * The upgrade lock (Task TL2): Lasso's lasso_db_migration_lock_v2, one option holding its expiry, five minutes.
 */
final class OptionLockTest extends TestCase {
	/** @var array<string, mixed> */
	private array $options = array();

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'add_option' )->alias(
			function ( string $name, mixed $value, string $unused = '', string|bool $autoload = true ): bool {
				self::assertFalse( $autoload );
				if ( array_key_exists( $name, $this->options ) ) {
					return false;
				}
				$this->options[ $name ] = $value;
				return true;
			}
		);
		Functions\when( 'get_option' )->alias( fn( string $name ): mixed => $this->options[ $name ] ?? false );
		Functions\when( 'delete_option' )->alias(
			function ( string $name ): bool {
				unset( $this->options[ $name ] );
				return true;
			}
		);
	}

	public function test_a_free_lock_is_taken_for_five_minutes_and_released(): void {
		$lock   = new OptionLock( 'affibloom_upgrade_lock', 300 );
		$before = time();

		self::assertTrue( $lock->acquire() );
		self::assertGreaterThanOrEqual( $before + 300, $this->options['affibloom_upgrade_lock'] );
		self::assertLessThanOrEqual( time() + 300, $this->options['affibloom_upgrade_lock'] );

		$lock->release();
		self::assertArrayNotHasKey( 'affibloom_upgrade_lock', $this->options );
	}

	public function test_a_held_lock_is_refused(): void {
		$this->options['affibloom_upgrade_lock'] = time() + 120;

		self::assertFalse( ( new OptionLock( 'affibloom_upgrade_lock', 300 ) )->acquire() );
	}

	public function test_a_lock_older_than_five_minutes_is_cleared_and_taken(): void {
		$this->options['affibloom_upgrade_lock'] = time() - 1;

		self::assertTrue( ( new OptionLock( 'affibloom_upgrade_lock', 300 ) )->acquire() );
		self::assertGreaterThan( time(), $this->options['affibloom_upgrade_lock'] );
	}
}
