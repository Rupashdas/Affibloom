<?php
namespace Affibloom\Tests\Unit\Infrastructure\Schema;

use Affibloom\Application\Upgrade\StepFailed;
use Affibloom\Infrastructure\Schema\Migrator;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * Numbered schema and data steps, gated by affibloom_db_version (Task TL2, F37 D2).
 */
final class MigratorTest extends TestCase {
	private int $stored = 0;
	/** @var list<int> */
	private array $ran = array();

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'get_option' )->alias(
			fn( string $name, mixed $fallback = false ): mixed => 'affibloom_db_version' === $name ? $this->stored : $fallback
		);
		Functions\when( 'update_option' )->alias(
			function ( string $name, mixed $value ): bool {
				self::assertSame( 'affibloom_db_version', $name );
				$this->stored = $value;
				return true;
			}
		);
	}

	private function step( int $number ): callable {
		return function () use ( $number ): void {
			$this->ran[] = $number;
		};
	}

	public function test_only_the_steps_after_the_stored_version_run_in_order_and_each_is_recorded(): void {
		$this->stored = 1;
		$migrator     = new Migrator( array( 3 => $this->step( 3 ), 1 => $this->step( 1 ), 2 => $this->step( 2 ) ) );

		self::assertTrue( $migrator->pending() );
		self::assertSame( array( 2, 3 ), $migrator->run() );
		self::assertSame( array( 2, 3 ), $this->ran );
		self::assertSame( 3, $this->stored );
		self::assertFalse( $migrator->pending() );
		self::assertSame( array(), $migrator->run() );
	}

	public function test_no_steps_means_nothing_is_pending(): void {
		self::assertFalse( ( new Migrator( array() ) )->pending() );
	}

	public function test_a_failed_step_keeps_the_version_of_the_last_step_that_finished(): void {
		$failing  = static function (): void {
			throw new \RuntimeException( 'Table full' );
		};
		$migrator = new Migrator( array( 1 => $this->step( 1 ), 2 => $failing, 3 => $this->step( 3 ) ) );

		try {
			$migrator->run();
			self::fail( 'The failed step was not reported.' );
		} catch ( StepFailed $failed ) {
			self::assertSame( 2, $failed->step );
			self::assertSame( 'Table full', $failed->getMessage() );
		}
		self::assertSame( 1, $this->stored );
		self::assertSame( array( 1 ), $this->ran );
	}
}
