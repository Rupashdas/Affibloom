<?php
namespace Affibloom\Tests\Unit\Domain\Product;

use Affibloom\Domain\Product\BulkRun;
use PHPUnit\Framework\TestCase;

final class BulkRunTest extends TestCase {
	private const NOW = 1_800_000_000;

	public function test_a_run_keeps_the_first_ten_non_blank_lines(): void {
		$lines = array( '  https://a.example/1 ', '', '   ' );
		for ( $i = 2; $i <= 12; $i++ ) {
			$lines[] = "https://a.example/$i";
		}

		$run = BulkRun::start( $lines, self::NOW );

		self::assertSame( 10, $run->total );
		self::assertSame( 0, $run->done );
		self::assertSame( 'https://a.example/1', $run->next() );
		self::assertCount( 10, $run->pending );
		self::assertSame( 'https://a.example/10', $run->pending[9] );
	}

	public function test_each_step_takes_the_next_link_and_counts_it(): void {
		$run = BulkRun::start( array( 'a.example/1', 'a.example/2' ), self::NOW )->advanced();

		self::assertSame( 1, $run->done );
		self::assertSame( 'a.example/2', $run->next() );
		self::assertSame( 50, $run->percent() );
		self::assertTrue( $run->is_running( self::NOW ) );

		$run = $run->advanced();
		self::assertNull( $run->next() );
		self::assertSame( 100, $run->percent() );
		self::assertFalse( $run->is_running( self::NOW ) );
	}

	public function test_a_link_that_failed_is_recorded_and_still_counts_as_handled(): void {
		$run = BulkRun::start( array( 'a.example/1', 'a.example/2' ), self::NOW )->advanced( 'a.example/1' );

		self::assertSame( array( 'a.example/1' ), $run->failed );
		self::assertSame( 1, $run->done );
		self::assertSame( 'a.example/2', $run->next() );
		self::assertEquals( $run, BulkRun::from_array( $run->to_array() ) );
	}

	public function test_a_stored_run_without_a_failed_list_has_none(): void {
		$run = BulkRun::from_array( array( 'pending' => array( 'a.example/2' ), 'total' => 2, 'done' => 1, 'started_at' => self::NOW ) );

		self::assertSame( array(), $run->failed );
	}

	public function test_percent_is_rounded(): void {
		$run = BulkRun::start( array( 'a.example/1', 'a.example/2', 'a.example/3' ), self::NOW )->advanced();

		self::assertSame( 33, $run->percent() );
	}

	public function test_a_run_ages_out_after_six_hours_as_in_lasso(): void {
		$run = BulkRun::start( array( 'a.example/1' ), self::NOW );

		self::assertTrue( $run->is_running( self::NOW + 6 * 3600 - 1 ) );
		self::assertFalse( $run->is_running( self::NOW + 6 * 3600 ) );
	}

	public function test_it_survives_storage(): void {
		$run = BulkRun::start( array( 'a.example/1', 'a.example/2' ), self::NOW )->advanced();

		self::assertEquals( $run, BulkRun::from_array( $run->to_array() ) );
		self::assertNull( BulkRun::from_array( array( 'pending' => 'nope' ) ) );
	}
}
