<?php
/**
 * Amazon's limits around every Creators API call.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Amazon;

use Affibloom\Domain\Amazon\Asin;
use Affibloom\Domain\Amazon\CatalogItem;
use Affibloom\Domain\Amazon\Credentials;
use Affibloom\Domain\Amazon\RequestLedger;
use Affibloom\Domain\Amazon\SearchPage;
use Closure;

/**
 * Amazon's limits around every Creators API call (C1Q6a; Amazon's "API rates": one request a second, 8,640 a day at
 * first, 429 ThrottleException with a wait). After a 429 no call goes out until Amazon's wait is over; calls are at
 * least a second apart, whoever makes them; item calls are counted per UTC day and stop at the quota. A refused call is
 * a Throttled CatalogError with the seconds left, so add and Refresh fall back (AC2) and the jobs stop. Lasso has none
 * of this (R15: a sleep in its jobs only).
 */
final class GuardedCatalog implements AmazonCatalog {

	/** Amazon's starting quota of requests a day (8,640 TPD). */
	public const DAILY_LIMIT = 8640;

	/** Calls kept for people's adds and Refresh: the hourly refresh stops this short of the quota. */
	public const RESERVE = 864;

	/** Amazon's rate: one request a second. */
	private const MIN_GAP_SECONDS = 1.0;

	/**
	 * The clock, in seconds.
	 *
	 * @var Closure(): float
	 */
	private Closure $clock;

	/**
	 * Waits between two calls.
	 *
	 * @var Closure(float): void
	 */
	private Closure $pause;

	/**
	 * Builds the guard.
	 *
	 * @param AmazonCatalog          $inner  The Creators API.
	 * @param RequestLedger          $ledger The site's record of its calls.
	 * @param (Closure(): float)     $clock  Seconds now; microtime() unless a test gives another.
	 * @param (Closure(float): void) $pause  Waits that long; usleep() unless a test gives another.
	 */
	public function __construct( private AmazonCatalog $inner, private RequestLedger $ledger, ?Closure $clock = null, ?Closure $pause = null ) {
		$this->clock = $clock ?? static fn(): float => microtime( true );
		$this->pause = $pause ?? static function ( float $seconds ): void {
			usleep( (int) ( $seconds * 1000000 ) );
		};
	}

	/**
	 * Validate's token check, inside the wait and the pace (not counted: a token is not a catalogue request).
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @throws CatalogError When throttled, or as the Creators API throws.
	 */
	public function verify( Credentials $credentials ): void {
		$this->guarded( fn() => $this->inner->verify( $credentials ), false );
	}

	/**
	 * One item, inside the wait, the pace and the quota.
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @param Asin        $asin        The item.
	 * @param string      $country     The store's country code.
	 * @throws CatalogError When throttled, or as the Creators API throws.
	 */
	public function item( Credentials $credentials, Asin $asin, string $country ): CatalogItem {
		return $this->guarded( fn() => $this->inner->item( $credentials, $asin, $country ), true );
	}

	/**
	 * Up to ten items in one request, inside the wait, the pace and the quota (counted once, as Amazon counts it).
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @param list<Asin>  $asins       One to ten ASINs.
	 * @param string      $country     The store's country code.
	 * @return array<string, CatalogItem>
	 * @throws CatalogError When throttled, or as the Creators API throws.
	 */
	public function items( Credentials $credentials, array $asins, string $country ): array {
		return $this->guarded( fn() => $this->inner->items( $credentials, $asins, $country ), true );
	}

	/**
	 * One search page, inside the wait, the pace and the quota (a catalogue request, so it counts).
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @param string      $keywords    What to search for.
	 * @param int         $page        The page.
	 * @param string      $country     The store's country code.
	 * @throws CatalogError When throttled, or as the Creators API throws.
	 */
	public function search( Credentials $credentials, string $keywords, int $page, string $country ): SearchPage {
		return $this->guarded( fn() => $this->inner->search( $credentials, $keywords, $page, $country ), true );
	}

	/**
	 * Amazon's answer as received, inside the wait, the pace and the quota.
	 *
	 * @param Credentials       $credentials Configured credentials.
	 * @param Asin              $asin        The item.
	 * @param string            $country     The store's country code.
	 * @param list<string>|null $resources   The resource paths; null for every one.
	 * @return array{status: int, body: string}
	 * @throws CatalogError When throttled, or as the Creators API throws.
	 */
	public function raw( Credentials $credentials, Asin $asin, string $country, ?array $resources ): array {
		return $this->guarded( fn() => $this->inner->raw( $credentials, $asin, $country, $resources ), true );
	}

	/**
	 * Item calls left today (UTC) under the quota.
	 */
	public function left_today(): int {
		$now = ( $this->clock )();

		return max( 0, self::DAILY_LIMIT - $this->ledger->get()->on_day( gmdate( 'Y-m-d', (int) $now ) )->count );
	}

	/**
	 * Runs a call inside the wait, the pace and the quota, and keeps Amazon's wait when it answers 429.
	 *
	 * @template T
	 * @param Closure(): T $call   The call.
	 * @param bool         $counts Whether it counts against the day's quota.
	 * @return T
	 * @throws CatalogError When throttled, or as the call throws.
	 */
	private function guarded( Closure $call, bool $counts ): mixed {
		$now = ( $this->clock )();
		$log = $this->ledger->get()->on_day( gmdate( 'Y-m-d', (int) $now ) );
		if ( $now < $log->blocked_until ) {
			$left = (int) ceil( $log->blocked_until - $now );
			throw new CatalogError( CatalogError::THROTTLED, "Amazon asked to wait {$left} more seconds.", '', $left ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- a number; escaped where it is printed.
		}
		if ( $counts && $log->count >= self::DAILY_LIMIT ) {
			$left = (int) ( strtotime( 'tomorrow', (int) $now ) - $now );
			throw new CatalogError( CatalogError::THROTTLED, "The day's Amazon quota is used up.", '', max( 1, $left ) );
		}

		$wait = $log->last_at + self::MIN_GAP_SECONDS - $now;
		if ( $wait > 0 ) {
			( $this->pause )( $wait );
			$now = ( $this->clock )();
		}
		$this->ledger->save( $log->called( $now, $counts ) );

		try {
			return $call();
		} catch ( CatalogError $error ) {
			if ( CatalogError::THROTTLED === $error->kind ) {
				$this->ledger->save( $this->ledger->get()->blocked( (int) ceil( $now ) + max( 1, $error->retry_after ) ) );
			}
			throw $error;
		}
	}
}
