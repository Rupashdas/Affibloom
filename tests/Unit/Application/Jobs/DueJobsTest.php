<?php
namespace Affibloom\Tests\Unit\Application\Jobs;

use Affibloom\Application\Jobs\DueJobs;
use Affibloom\Application\Jobs\ScheduledEvents;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * The admin pages' fallback where WP-Cron cannot run (Task 32D; F11 R34, D22): due Affibloom events run inline, the
 * oldest first, at most "Maximum cronjobs at a time" (1-3) per call; other plugins' events are left alone.
 */
final class DueJobsTest extends TestCase {

	/**
	 * @param list<array{hook: string, time: int, args: array<int, mixed>}> $events
	 */
	private static function events( array $events ): ScheduledEvents {
		return new class( $events ) implements ScheduledEvents {
			/** @var list<string> */
			public array $ran = array();

			public function __construct( private array $events ) {}

			public function due( array $hooks, int $now ): array {
				$due = array_values( array_filter( $this->events, static fn( array $event ): bool => in_array( $event['hook'], $hooks, true ) && $event['time'] <= $now ) );
				usort( $due, static fn( array $a, array $b ): int => $a['time'] <=> $b['time'] );
				return $due;
			}

			public function run( array $event ): void {
				$this->ran[] = $event['hook'];
			}
		};
	}

	public function test_due_events_run_oldest_first_up_to_the_manual_limit(): void {
		$events = self::events(
			array(
				array( 'hook' => 'affibloom_link_check_batch', 'time' => 90, 'args' => array() ),
				array( 'hook' => 'affibloom_daily', 'time' => 50, 'args' => array() ),
				array( 'hook' => 'other_plugin_job', 'time' => 10, 'args' => array() ),
				array( 'hook' => 'affibloom_bulk_add', 'time' => 70, 'args' => array() ),
				array( 'hook' => 'affibloom_link_index_batch', 'time' => 500, 'args' => array() ),
			)
		);
		$jobs = new DueJobs( $events, array( 'affibloom_daily', 'affibloom_link_check_batch', 'affibloom_bulk_add', 'affibloom_link_index_batch' ), new InMemorySettingsRepository( array( 'manual_process_limit' => 2 ) ) );

		$this->assertSame( array( 'affibloom_daily', 'affibloom_bulk_add' ), $jobs->run_due( 100 ) );
		$this->assertSame( array( 'affibloom_daily', 'affibloom_bulk_add' ), $events->ran );
	}

	public function test_the_limit_is_clamped_to_lassos_range(): void {
		$events = self::events( array_map( static fn( int $i ): array => array( 'hook' => 'affibloom_daily', 'time' => $i, 'args' => array() ), range( 1, 5 ) ) );

		$this->assertCount( 3, ( new DueJobs( $events, array( 'affibloom_daily' ), new InMemorySettingsRepository( array( 'manual_process_limit' => 9 ) ) ) )->run_due( 100 ) );
		$this->assertCount( 1, ( new DueJobs( $events, array( 'affibloom_daily' ), new InMemorySettingsRepository( array( 'manual_process_limit' => 0 ) ) ) )->run_due( 100 ) );
	}
}
