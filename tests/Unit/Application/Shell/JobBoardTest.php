<?php
namespace Affibloom\Tests\Unit\Application\Shell;

use Affibloom\Application\LinkIndex\LinkIndexJobStatus;
use Affibloom\Application\Products\BulkAddJobStatus;
use Affibloom\Application\Shell\JobBoard;
use Affibloom\Domain\LinkIndex\RebuildState;
use Affibloom\Domain\LinkIndex\RebuildStateRepository;
use Affibloom\Domain\Product\BulkRun;
use Affibloom\Domain\Product\BulkRunRepository;
use Affibloom\Domain\Shell\JobProgress;
use PHPUnit\Framework\TestCase;

/**
 * Automations' list (F38 R40-R42, D13; Task ON3).
 */
final class JobBoardTest extends TestCase {

	private static function runs( ?BulkRun $run ): BulkRunRepository {
		return new class( $run ) implements BulkRunRepository {
			public function __construct( private ?BulkRun $run ) {}
			public function get(): ?BulkRun {
				return $this->run;
			}
			public function save( BulkRun $run ): void {}
		};
	}

	private static function state( RebuildState $state ): RebuildStateRepository {
		return new class( $state ) implements RebuildStateRepository {
			public function __construct( private RebuildState $state ) {}
			public function get(): RebuildState {
				return $this->state;
			}
			public function save( RebuildState $state ): void {}
		};
	}

	public function test_only_running_jobs_are_listed_with_lassos_names_and_counts(): void {
		$now   = 1_000_000;
		$board = new JobBoard(
			array(
				new BulkAddJobStatus( self::runs( BulkRun::start( array( 'https://a.test', 'https://b.test', 'https://c.test' ), $now ) ), static fn(): int => $now ),
				new LinkIndexJobStatus( self::state( ( new RebuildState() )->started( 1105, '2026-11-18 10:00:00' )->advanced( 221, 900 ) ) ),
			)
		);

		self::assertSame( array( 'Bulk Add Links (0/3)', 'Scan all links (221/1105)' ), array_map( static fn( JobProgress $job ): string => $job->line(), $board->running() ) );
	}

	public function test_no_job_runs_on_a_quiet_site(): void {
		$board = new JobBoard( array( new BulkAddJobStatus( self::runs( null ), static fn(): int => 0 ), new LinkIndexJobStatus( self::state( new RebuildState() ) ) ) );

		self::assertSame( array(), $board->running() );
	}
}
