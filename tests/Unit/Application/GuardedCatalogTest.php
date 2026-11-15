<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Amazon\CatalogError;
use Affibloom\Application\Amazon\GuardedCatalog;
use Affibloom\Domain\Amazon\Asin;
use Affibloom\Domain\Amazon\CatalogItem;
use Affibloom\Domain\Amazon\Credentials;
use Affibloom\Domain\Amazon\RequestLedger;
use Affibloom\Domain\Amazon\RequestLog;
use Affibloom\Tests\Unit\Fakes\FakeAmazonCatalog;
use PHPUnit\Framework\TestCase;

/**
 * Amazon's limits around every Creators API call (C1Q6a): the wait after a 429, one call a second, the day's quota.
 */
final class GuardedCatalogTest extends TestCase {
	private FakeAmazonCatalog $amazon;
	private ?RequestLog $log = null;
	private float $now;
	/** @var list<float> */
	private array $paused = array();

	private function guard(): GuardedCatalog {
		$this->amazon = new FakeAmazonCatalog();
		$this->amazon->items['B0AAAAAAAA'] = CatalogItem::from_creators( array( 'asin' => 'B0AAAAAAAA' ) );
		$this->now    = (float) strtotime( '2026-11-15 12:00:00 UTC' );
		$test         = $this;
		$ledger       = new class( $test ) implements RequestLedger {
			public function __construct( private GuardedCatalogTest $test ) {}
			public function get(): RequestLog {
				return $this->test->log() ?? new RequestLog( '', 0, 0.0, 0 );
			}
			public function save( RequestLog $log ): void {
				$this->test->keep( $log );
			}
		};

		return new GuardedCatalog(
			$this->amazon,
			$ledger,
			fn(): float => $this->now,
			function ( float $seconds ): void {
				$this->paused[] = $seconds;
				$this->now     += $seconds;
			}
		);
	}

	public function log(): ?RequestLog {
		return $this->log;
	}

	public function keep( RequestLog $log ): void {
		$this->log = $log;
	}

	private static function credentials(): Credentials {
		return new Credentials( 'id', 'secret', 'tag-20', '3.1' );
	}

	private function ask( GuardedCatalog $guard ): void {
		$guard->item( self::credentials(), Asin::from( 'B0AAAAAAAA' ), 'us' );
	}

	public function test_calls_are_at_least_a_second_apart_and_counted_for_the_day(): void {
		$guard = $this->guard();
		$this->ask( $guard );
		$this->now += 0.25;
		$this->ask( $guard );

		self::assertSame( array( 0.75 ), $this->paused );
		self::assertSame( array( '2026-11-15', 2 ), array( $this->log?->day, $this->log?->count ) );
		self::assertSame( 8640 - 2, $guard->left_today() );
	}

	public function test_a_429_makes_every_call_wait_for_amazons_time_without_asking(): void {
		$guard                    = $this->guard();
		$this->amazon->throttled  = 120;
		try {
			$this->ask( $guard );
			self::fail( 'a throttled call passed' );
		} catch ( CatalogError $error ) {
			self::assertSame( array( CatalogError::THROTTLED, 120 ), array( $error->kind, $error->retry_after ) );
		}

		$this->amazon->throttled = 0;
		$this->now              += 60;
		$asked                   = $this->amazon->asked;
		try {
			$this->ask( $guard );
			self::fail( 'a call went out during the wait' );
		} catch ( CatalogError $error ) {
			self::assertSame( array( CatalogError::THROTTLED, 60 ), array( $error->kind, $error->retry_after ) );
		}
		self::assertSame( $asked, $this->amazon->asked, 'nothing asked during the wait' );

		$this->now += 61;
		$this->ask( $guard );
		self::assertSame( $asked + 1, $this->amazon->asked );
	}

	public function test_the_days_quota_stops_calls_until_the_next_day(): void {
		$guard     = $this->guard();
		$this->log = new RequestLog( '2026-11-15', GuardedCatalog::DAILY_LIMIT, 0.0, 0 );
		try {
			$this->ask( $guard );
			self::fail( 'a call beyond the quota passed' );
		} catch ( CatalogError $error ) {
			self::assertSame( CatalogError::THROTTLED, $error->kind );
		}
		self::assertSame( 0, $guard->left_today() );

		$this->now += 13 * 3600; // The next UTC day.
		$this->ask( $guard );
		self::assertSame( array( '2026-11-16', 1 ), array( $this->log?->day, $this->log?->count ) );
	}

	public function test_a_throttled_call_is_not_unavailable_credentials_wise(): void {
		self::assertTrue( ( new CatalogError( CatalogError::THROTTLED, 'busy', '', 60 ) )->unavailable() );
	}
}
