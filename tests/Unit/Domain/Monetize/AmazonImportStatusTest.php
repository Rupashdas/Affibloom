<?php
namespace Affibloom\Tests\Unit\Domain\Monetize;

use Affibloom\Application\Amazon\BannerDismissals;
use Affibloom\Application\Monetize\AmazonImportStatusService;
use Affibloom\Domain\Monetize\AmazonImportStatus;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Fakes\InMemoryLinkLocations;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * The Amazon import's status and notice (F35 R29, R30, R35; D19; Task AM4).
 */
final class AmazonImportStatusTest extends TestCase {

	public function test_work_left_is_pending_with_its_counts_and_none_is_complete(): void {
		$pending = new AmazonImportStatus( 91, 7, 3015, true, 100, 37 );
		self::assertTrue( $pending->busy() );
		self::assertSame( 'pending_91_7', $pending->fingerprint() );
		self::assertSame( 37, $pending->percent() );

		$done = new AmazonImportStatus( 0, 98, 3015 );
		self::assertTrue( $done->complete() );
		self::assertSame( 'complete_98', $done->fingerprint() );
		self::assertSame( 0, $done->percent() );
	}

	public function test_a_run_without_a_total_shows_five_percent_and_is_not_complete(): void {
		$running = new AmazonImportStatus( 0, 7, 10, true );
		self::assertFalse( $running->complete() );
		self::assertSame( 5, $running->percent() );
		self::assertSame( array( 'running', 'queue_count', 'dashboard_count', 'links_indexed', 'complete', 'batch_total', 'batch_completed', 'batch_percent' ), array_keys( $running->to_array() ) );
	}

	public function test_the_notice_shows_while_on_with_work_and_until_dismissed_for_these_counts(): void {
		$rows               = new InMemoryLinkLocations();
		$rows->amazon       = array( array( 'asin' => 'B0X', 'country' => 'us', 'domain' => 'amazon.com', 'tag' => '' ) );
		$settings           = new InMemorySettingsRepository();
		$settings->stored   = Settings::from_array( array( 'auto_monetize_amazon' => true ) );
		$dismissals         = new class() implements BannerDismissals {
			public array $by = array();
			public function get( int $user ): string {
				return $this->by[ $user ] ?? '';
			}
			public function set( int $user, string $fingerprint ): void {
				$this->by[ $user ] = $fingerprint;
			}
		};
		$service = new AmazonImportStatusService( $rows, new InMemoryProductRepository(), $settings, $dismissals, static fn(): array => array() );

		self::assertNotNull( $service->notice( 3 ) );
		$service->dismiss( 3 );
		self::assertNull( $service->notice( 3 ) );
		self::assertNotNull( $service->notice( 4 ) );

		$rows->amazon[] = array( 'asin' => 'B0Y', 'country' => 'us', 'domain' => 'amazon.com', 'tag' => '' );
		self::assertNotNull( $service->notice( 3 ), 'the counts changed' );

		$settings->stored = Settings::from_array( array( 'auto_monetize_amazon' => false ) );
		self::assertNull( $service->notice( 4 ) );
	}
}
