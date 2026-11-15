<?php
namespace Affibloom\Tests\Unit\Domain\Amazon;

use Affibloom\Domain\Amazon\BulkSyncRun;
use PHPUnit\Framework\TestCase;

/**
 * One bulk Amazon sync run (C1I; R43): at most 500 products, the newest 200 failures, the age-out and storage.
 */
final class BulkSyncRunTest extends TestCase {

	public function test_a_run_keeps_the_first_500_products(): void {
		$run = BulkSyncRun::start( range( 1, 600 ), 1000 );

		self::assertSame( 500, $run->total );
		self::assertSame( 1, $run->next() );
		self::assertSame( 0, $run->percent() );
	}

	public function test_it_counts_each_outcome_keeps_the_newest_200_failures_and_completes(): void {
		$run = BulkSyncRun::start( range( 1, 202 ), 1000 );
		$run = $run->advanced( BulkSyncRun::SUCCEEDED, '', '2026-11-15 10:00:00' );
		for ( $i = 0; $i < 201; $i++ ) {
			$run = $run->advanced( BulkSyncRun::FAILED, 'not_found', '2026-11-15 10:00:00' );
		}

		self::assertSame( array( 'succeeded' => 1, 'skipped' => 0, 'failed' => 201 ), $run->counts );
		self::assertCount( 200, $run->failures );
		self::assertSame( 3, $run->failures[0]['id'], 'the oldest failure went' );
		self::assertSame( '2026-11-15 10:00:00', $run->completed_at );
		self::assertSame( 100, $run->percent() );
		self::assertFalse( $run->is_running( 1000 ) );
	}

	public function test_a_run_older_than_six_hours_no_longer_runs(): void {
		$run = BulkSyncRun::start( array( 1, 2 ), 1000 );

		self::assertTrue( $run->is_running( 1000 + 6 * 3600 - 1 ) );
		self::assertFalse( $run->is_running( 1000 + 6 * 3600 ) );
	}

	public function test_it_survives_storage_and_refuses_what_is_not_a_run(): void {
		$run = BulkSyncRun::start( array( 4, 5 ), 1000 )->advanced( BulkSyncRun::FAILED, 'failed', '' );

		self::assertEquals( $run, BulkSyncRun::from_array( $run->to_array() ) );
		self::assertNull( BulkSyncRun::from_array( 'x' ) );
		self::assertNull( BulkSyncRun::from_array( array( 'pending' => 'x', 'total' => 1, 'started_at' => 1 ) ) );
	}
}
