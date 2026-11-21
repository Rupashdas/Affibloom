<?php
namespace Affibloom\Tests\Integration;

use Affibloom\Infrastructure\WordPress\AmazonImportSchedule;
use WP_UnitTestCase;

/**
 * The Amazon import's event (F35 D11, D23; Task AM1): every five minutes once started, gone once stopped. Runs only
 * where wp-env is available (progress.md, deviation 10).
 */
final class AmazonImportCronTest extends WP_UnitTestCase {

	public function test_the_event_runs_every_five_minutes_once_started_and_goes_when_stopped(): void {
		add_filter( 'cron_schedules', array( AmazonImportSchedule::class, 'interval' ) );
		$schedule = new AmazonImportSchedule();

		$schedule->start();
		$schedule->start();
		self::assertSame( AmazonImportSchedule::INTERVAL, wp_get_schedule( AmazonImportSchedule::HOOK ) );
		self::assertSame( 300, wp_get_schedules()[ AmazonImportSchedule::INTERVAL ]['interval'] );

		$schedule->stop();
		self::assertFalse( wp_next_scheduled( AmazonImportSchedule::HOOK ) );
	}
}
