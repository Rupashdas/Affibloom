<?php
namespace Affibloom\Tests\Integration;

use Affibloom\Application\Upgrade\Upgrader;
use Affibloom\Application\Upgrade\UpgradeResult;
use Affibloom\Infrastructure\Schema\Migrator;
use Affibloom\Infrastructure\WordPress\OptionLock;
use WP_UnitTestCase;

/**
 * The upgrade routine against real options (Task TL2, F37 AC2). Runs only where wp-env is available (progress.md,
 * deviation 10).
 */
final class UpgraderTest extends WP_UnitTestCase {
	/** @var list<int> */
	private array $ran = array();

	protected function setUp(): void {
		parent::setUp();
		delete_option( 'affibloom_db_version' );
		delete_option( 'affibloom_upgrade_lock' );
	}

	/**
	 * @param array<int, callable(): void> $steps
	 */
	private function upgrader( array $steps ): Upgrader {
		return new Upgrader( new OptionLock( 'affibloom_upgrade_lock', 300 ), new Migrator( $steps ) );
	}

	private function step( int $number ): callable {
		return function () use ( $number ): void {
			$this->ran[] = $number;
		};
	}

	public function test_steps_run_once_in_order_and_the_lock_is_gone_after(): void {
		$upgrader = $this->upgrader( array( 2 => $this->step( 2 ), 1 => $this->step( 1 ) ) );

		self::assertSame( array( 1, 2 ), $upgrader->run()->steps );
		self::assertSame( UpgradeResult::NOTHING, $upgrader->run()->status );
		self::assertSame( array( 1, 2 ), $this->ran );
		self::assertSame( 2, (int) get_option( 'affibloom_db_version' ) );
		self::assertFalse( get_option( 'affibloom_upgrade_lock' ) );
	}

	public function test_a_second_request_skips_and_a_stale_lock_is_cleared(): void {
		add_option( 'affibloom_upgrade_lock', time() + 120, '', false );
		self::assertSame( UpgradeResult::LOCKED, $this->upgrader( array( 1 => $this->step( 1 ) ) )->run()->status );

		update_option( 'affibloom_upgrade_lock', time() - 1 );
		self::assertSame( UpgradeResult::DONE, $this->upgrader( array( 1 => $this->step( 1 ) ) )->run()->status );
	}

	public function test_a_failed_step_keeps_the_version_and_releases_the_lock(): void {
		$failing = static function (): void {
			throw new \RuntimeException( 'Table full' );
		};

		$result = $this->upgrader( array( 1 => $this->step( 1 ), 2 => $failing ) )->run();

		self::assertSame( UpgradeResult::FAILED, $result->status );
		self::assertSame( 1, (int) get_option( 'affibloom_db_version' ) );
		self::assertFalse( get_option( 'affibloom_upgrade_lock' ) );
	}
}
