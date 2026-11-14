<?php
namespace Affibloom\Tests\Unit\Application\Upgrade;

use Affibloom\Application\Upgrade\Migrations;
use Affibloom\Application\Upgrade\StepFailed;
use Affibloom\Application\Upgrade\UpgradeLock;
use Affibloom\Application\Upgrade\Upgrader;
use Affibloom\Application\Upgrade\UpgradeResult;
use PHPUnit\Framework\TestCase;

/**
 * The upgrade routine (Task TL2, F37 D2): pending steps run once, under a lock, which is always released.
 */
final class UpgraderTest extends TestCase {
	/** @var list<string> What happened, in order. */
	private array $log = array();
	private bool $free = true;
	private bool $pending = true;
	private ?StepFailed $fails = null;

	private function upgrader(): Upgrader {
		$test  = $this;
		$lock  = new class( $test ) implements UpgradeLock {
			public function __construct( private UpgraderTest $test ) {}
			public function acquire(): bool {
				return $this->test->event( 'acquire' ) && $this->test->free();
			}
			public function release(): void {
				$this->test->event( 'release' );
			}
		};
		$steps = new class( $test ) implements Migrations {
			public function __construct( private UpgraderTest $test ) {}
			public function pending(): bool {
				return $this->test->is_pending();
			}
			public function run(): array {
				$this->test->event( 'run' );
				return $this->test->outcome();
			}
		};
		return new Upgrader( $lock, $steps );
	}

	public function event( string $what ): bool {
		$this->log[] = $what;
		return true;
	}

	public function free(): bool {
		return $this->free;
	}

	public function is_pending(): bool {
		return $this->pending;
	}

	/** @return list<int> */
	public function outcome(): array {
		if ( null !== $this->fails ) {
			throw $this->fails;
		}
		return array( 1, 2 );
	}

	public function test_nothing_pending_takes_no_lock(): void {
		$this->pending = false;

		$result = $this->upgrader()->run();

		self::assertSame( UpgradeResult::NOTHING, $result->status );
		self::assertSame( array(), $this->log );
	}

	public function test_pending_steps_run_under_the_lock_and_the_lock_is_released(): void {
		$result = $this->upgrader()->run();

		self::assertSame( UpgradeResult::DONE, $result->status );
		self::assertSame( array( 1, 2 ), $result->steps );
		self::assertSame( array( 'acquire', 'run', 'release' ), $this->log );
	}

	public function test_a_second_request_skips_while_the_lock_is_held(): void {
		$this->free = false;

		$result = $this->upgrader()->run();

		self::assertSame( UpgradeResult::LOCKED, $result->status );
		self::assertSame( array( 'acquire' ), $this->log );
	}

	public function test_a_failed_step_is_reported_and_the_lock_is_released(): void {
		$this->fails = new StepFailed( 3, new \RuntimeException( 'Table full' ) );

		$result = $this->upgrader()->run();

		self::assertSame( UpgradeResult::FAILED, $result->status );
		self::assertSame( 3, $result->failed_step );
		self::assertSame( 'Table full', $result->message );
		self::assertSame( array( 'acquire', 'run', 'release' ), $this->log );
	}
}
