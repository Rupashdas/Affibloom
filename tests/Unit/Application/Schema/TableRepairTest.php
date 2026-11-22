<?php
namespace Affibloom\Tests\Unit\Application\Schema;

use Affibloom\Application\Logs\Logger;
use Affibloom\Application\Schema\RepairState;
use Affibloom\Application\Schema\Schema;
use Affibloom\Application\Schema\TableRepair;
use Affibloom\Domain\Logs\LogType;
use PHPUnit\Framework\TestCase;

/**
 * F37 R12, AC5 (Task TL4): an SQL error on an Affibloom table is logged, forced; one that says a table or a column is
 * missing, or that collations clash, recreates the tables, at most three times, then once an hour.
 */
final class TableRepairTest extends TestCase {

	/** @var list<array{0: string, 1: string, 2: bool}> */
	private array $lines = array();
	private int $recreated = 0;
	/** @var array{count: int, last: int} */
	private array $state = array( 'count' => 0, 'last' => 0 );

	private function repair( int $now ): TableRepair {
		$log    = new class( $this->lines ) implements Logger {
			/** @param list<array{0: string, 1: string, 2: bool}> $lines */
			public function __construct( private array &$lines ) {}
			public function write( LogType $type, string $message, bool $force = false ): void {
				$this->lines[] = array( $type->value, $message, $force );
			}
		};
		$schema = new class( $this->recreated ) implements Schema {
			public function __construct( private int &$recreated ) {}
			public function recreate(): array {
				++$this->recreated;
				return array( 'affibloom_url_variants' => array( 'Created table wp_affibloom_url_variants' ) );
			}
		};
		$state  = new class( $this->state ) implements RepairState {
			/** @param array{count: int, last: int} $state */
			public function __construct( private array &$state ) {}
			public function get(): array {
				return $this->state;
			}
			public function save( int $count, int $last ): void {
				$this->state = array( 'count' => $count, 'last' => $last );
			}
		};

		return new TableRepair( $schema, $state, $log, static fn(): int => $now );
	}

	public function test_a_missing_table_is_logged_and_the_tables_recreated(): void {
		self::assertTrue( $this->repair( 1000 )->after_error( "Table 'local.wp_affibloom_url_variants' doesn't exist", 'SELECT * FROM wp_affibloom_url_variants' ) );

		self::assertSame( 1, $this->recreated );
		self::assertSame( array( 'count' => 1, 'last' => 1000 ), $this->state );
		self::assertSame( array( 'sql_errors', "Table 'local.wp_affibloom_url_variants' doesn't exist; query: SELECT * FROM wp_affibloom_url_variants", true ), $this->lines[0] );
		self::assertSame( array( 'sql_errors', 'Tables repaired. affibloom_url_variants: Created table wp_affibloom_url_variants', true ), $this->lines[1] );
	}

	public function test_another_error_is_logged_and_repairs_nothing(): void {
		self::assertFalse( $this->repair( 1000 )->after_error( 'Duplicate entry', 'INSERT INTO wp_affibloom_clicks …' ) );

		self::assertSame( 0, $this->recreated );
		self::assertCount( 1, $this->lines );
	}

	public function test_three_repairs_then_one_an_hour(): void {
		foreach ( array( 1000, 1001, 1002 ) as $now ) {
			self::assertTrue( $this->repair( $now )->after_error( "Unknown column 'x' in 'field list'", 'q' ) );
		}
		self::assertFalse( $this->repair( 1003 )->after_error( "Unknown column 'x' in 'field list'", 'q' ) );
		self::assertFalse( $this->repair( 1002 + 3599 )->after_error( 'Illegal mix of collations', 'q' ) );
		self::assertTrue( $this->repair( 1002 + 3600 )->after_error( 'Illegal mix of collations', 'q' ) );
		self::assertSame( 4, $this->recreated );
	}
}
