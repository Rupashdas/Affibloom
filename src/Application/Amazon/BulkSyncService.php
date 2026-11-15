<?php
/**
 * Bulk Amazon sync: Amazon's data for the Amazon products that have none, in the background.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Amazon;

use Affibloom\Application\Products\BackgroundJob;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Domain\Amazon\BulkSyncRun;
use Affibloom\Domain\Amazon\Marketplace;
use Affibloom\Domain\Amazon\BulkSyncRunRepository;
use Affibloom\Domain\Amazon\PendingAmazonSync;
use Affibloom\Domain\Product\PlaceholderName;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\SettingsRepository;
use Closure;
use Throwable;

/**
 * Bulk Amazon sync (C1 R40-R44, D12; Lasso_Process_Bulk_Sync_Amazon): the banner's state per person, a run of at most
 * 500 pending products started from it, synced one at a time in the background (WP-Cron, ADR-4) with Lasso's one
 * second between products, and the run's progress and summary. A run is refused while one is going, when nothing is
 * pending, and without credentials.
 */
final class BulkSyncService {

	/** Seconds one background pass may run; WP Background Processing's limit, which Lasso's queue uses. */
	private const BUDGET_SECONDS = 20.0;

	/** Lasso's TASK_PACING_SECONDS: one product a second, Amazon's request rate. */
	private const PACING_SECONDS = 1;

	/**
	 * The clock, in seconds.
	 *
	 * @var Closure(): float
	 */
	private Closure $clock;

	/**
	 * Waits between two products.
	 *
	 * @var Closure(int): void
	 */
	private Closure $pause;

	/**
	 * Builds the service.
	 *
	 * @param PendingAmazonSync     $pending    The products without Amazon's data.
	 * @param BulkSyncRunRepository $runs       Where the run is kept.
	 * @param BannerDismissals      $dismissals Who hid the banner for which pending set.
	 * @param BackgroundJob         $job        What runs process() later.
	 * @param AmazonDataService     $data       Amazon's data on one product.
	 * @param ProductRepository     $library    The products.
	 * @param LinkNormaliser        $links      Where an Amazon short link leads, and the link as stored.
	 * @param SettingsRepository    $settings   The site's settings, for the tag rule.
	 * @param (Closure(): float)    $clock      Seconds now; microtime() unless a test gives another.
	 * @param (Closure(int): void)  $pause      Waits that many seconds; sleep() unless a test gives another.
	 */
	public function __construct(
		private PendingAmazonSync $pending,
		private BulkSyncRunRepository $runs,
		private BannerDismissals $dismissals,
		private BackgroundJob $job,
		private AmazonDataService $data,
		private ProductRepository $library,
		private LinkNormaliser $links,
		private SettingsRepository $settings,
		?Closure $clock = null,
		?Closure $pause = null,
	) {
		$this->clock = $clock ?? static fn(): float => microtime( true );
		$this->pause = $pause ?? static function ( int $seconds ): void {
			sleep( $seconds );
		};
	}

	/**
	 * The banner for a person, or null when it is hidden (R41): nothing is pending, or they dismissed this pending set.
	 *
	 * @param int $user The person's user ID.
	 * @return array{pending: int, running: bool, can_sync: bool}|null
	 */
	public function banner( int $user ): ?array {
		$ids = $this->pending->ids();
		if ( array() === $ids || $this->dismissals->get( $user ) === self::fingerprint( $ids ) ) {
			return null;
		}

		return array(
			'pending'  => count( $ids ),
			'running'  => $this->running(),
			'can_sync' => $this->data->can_ask_amazon(),
		);
	}

	/**
	 * Starts a run with the pending products (Lasso's process(), R42-R43).
	 *
	 * @return array{running: bool, percent: int, total: int, counts: array{succeeded: int, skipped: int, failed: int}, failures: list<array{id: int, reason: string}>, completed: bool, pending: int}
	 * @throws BulkSyncRefused When one is running, Amazon cannot be asked, or nothing is pending.
	 */
	public function start(): array {
		if ( $this->running() ) {
			throw new BulkSyncRefused( BulkSyncRefused::RUNNING );
		}
		if ( ! $this->data->can_ask_amazon() ) {
			throw new BulkSyncRefused( BulkSyncRefused::NOT_CONFIGURED );
		}
		$ids = $this->pending->ids( BulkSyncRun::LIMIT );
		if ( array() === $ids ) {
			throw new BulkSyncRefused( BulkSyncRefused::NOTHING );
		}

		$this->runs->save( BulkSyncRun::start( $ids, (int) ( $this->clock )() ) );
		$this->job->dispatch();

		return $this->status();
	}

	/**
	 * How the last run stands, and how many products are pending now (Lasso's get_status_payload). While it is going,
	 * the job is woken too, as bulk add's progress does: a pass WordPress's spawn lock delayed runs while the banner
	 * watches.
	 *
	 * @return array{running: bool, percent: int, total: int, counts: array{succeeded: int, skipped: int, failed: int}, failures: list<array{id: int, reason: string}>, completed: bool, pending: int}
	 */
	public function status(): array {
		$run     = $this->runs->get();
		$running = null !== $run && $run->is_running( (int) ( $this->clock )() );
		if ( $running ) {
			$this->job->dispatch();
		}

		return array(
			'running'   => $running,
			'percent'   => null === $run ? 100 : $run->percent(),
			'total'     => null === $run ? 0 : $run->total,
			'counts'    => null === $run ? array(
				BulkSyncRun::SUCCEEDED => 0,
				BulkSyncRun::SKIPPED   => 0,
				BulkSyncRun::FAILED    => 0,
			) : $run->counts,
			'failures'  => null === $run ? array() : $run->failures,
			'completed' => null !== $run && '' !== $run->completed_at,
			'pending'   => count( $this->pending->ids() ),
		);
	}

	/**
	 * Hides the banner for a person until the pending set changes (Lasso's dismiss_banner_for_user).
	 *
	 * @param int $user The person's user ID.
	 */
	public function dismiss( int $user ): void {
		$ids = $this->pending->ids();
		if ( $user > 0 && array() !== $ids ) {
			$this->dismissals->set( $user, self::fingerprint( $ids ) );
		}
	}

	/**
	 * One background pass: syncs products one at a time, saving the run after each and pausing a second between them,
	 * until none remain or the budget is spent; then wakes the job again if products remain. Nothing a product raises
	 * stops the run: it is counted failed.
	 */
	public function process(): void {
		$started  = ( $this->clock )();
		$deadline = $started + self::BUDGET_SECONDS;

		while ( true ) {
			$run = $this->runs->get();
			$id  = null !== $run && $run->is_running( (int) $started ) ? $run->next() : null;
			if ( null === $run || null === $id ) {
				return;
			}

			try {
				$this->prefetch( $run->pending );
				$outcome = $this->sync( $id );
			} catch ( CatalogError $throttled ) {
				$outcome = SyncOutcome::throttled( $throttled->retry_after );
			} catch ( Throwable $unexpected ) {
				unset( $unexpected );
				$outcome = SyncOutcome::failed( SyncOutcome::FAILED );
			}
			// Amazon asked to wait (C1Q6b): the product waits too, and the run resumes after the wait.
			if ( SyncOutcome::THROTTLED === $outcome->reason ) {
				$this->job->dispatch( $outcome->retry_after );
				return;
			}
			$run = $run->advanced( $outcome->kind, $outcome->reason, gmdate( 'Y-m-d H:i:s', (int) ( $this->clock )() ) );
			$this->runs->save( $run );
			if ( null === $run->next() ) {
				return;
			}

			( $this->pause )( self::PACING_SECONDS );
			if ( ( $this->clock )() >= $deadline ) {
				$this->job->dispatch();
				return;
			}
		}
	}

	/**
	 * Fetches ahead (C1Q6b) when the next product needs Amazon and was not fetched yet: the next ten pending products
	 * with an ASIN and no stored data, in one request per store.
	 *
	 * @param list<int> $pending The run's pending products, in order.
	 * @throws CatalogError When the request fails as a whole; anything but Throttled is left to sync()'s own request.
	 */
	private function prefetch( array $pending ): void {
		$wanted = array();
		foreach ( $pending as $id ) {
			$product = $this->library->get( $id );
			if ( null === $product || '' === $product->asin || null !== $this->data->stored( $product ) ) {
				if ( array() === $wanted ) {
					return; // The next product needs no request.
				}
				continue;
			}
			$country = '' === $product->amazon_country ? Marketplace::DEFAULT_COUNTRY : $product->amazon_country;
			if ( array() === $wanted && $this->data->is_prefetched( $product->asin, $country ) ) {
				return;
			}
			$wanted[] = array(
				'asin'    => $product->asin,
				'country' => $country,
			);
			if ( 10 === count( $wanted ) ) {
				break;
			}
		}

		try {
			$this->data->prefetch( $wanted );
		} catch ( CatalogError $error ) {
			if ( CatalogError::THROTTLED === $error->kind ) {
				throw $error;
			}
		}
	}

	/**
	 * One product (R44): not one, or not Amazon, is said so; an Amazon link without an ASIN is resolved first (an Amazon
	 * short link followed: its product link stored, or a search page's title given to a placeholder name); then
	 * Amazon's data (AmazonDataService::sync()).
	 *
	 * @param int $id The product.
	 */
	public function sync( int $id ): SyncOutcome {
		$product = $this->library->get( $id );
		if ( null === $product ) {
			return SyncOutcome::failed( SyncOutcome::INVALID );
		}
		if ( ProductType::Amazon !== $product->type ) {
			return SyncOutcome::skipped();
		}
		if ( '' === $product->asin ) {
			$resolved = $this->resolve( $product );
			if ( $resolved instanceof SyncOutcome ) {
				return $resolved;
			}
			$product = $resolved;
		}

		return $this->data->sync( $product );
	}

	/**
	 * An Amazon link without an ASIN, followed where Lasso follows it (Lasso's get_redirect_final_target): the product
	 * with the ASIN, store and stored link of where it leads; or, for a search page, the outcome after its link and
	 * placeholder name are updated; or "Could not resolve Amazon ASIN.".
	 *
	 * @param Product $product A stored Amazon product without an ASIN.
	 */
	private function resolve( Product $product ): Product|SyncOutcome {
		$link   = $this->links->primary( $product->url, $this->settings->get() );
		$amazon = $link->amazon;
		if ( null === $amazon ) {
			return SyncOutcome::failed( SyncOutcome::UNRESOLVED );
		}

		if ( '' !== $amazon->asin() ) {
			return $this->library->save(
				$product->with(
					array(
						'url'            => $link->stored,
						'asin'           => $amazon->asin(),
						'amazon_country' => $amazon->country(),
						'search_key'     => '',
					)
				)
			);
		}

		if ( '' === $amazon->search_key() ) {
			return SyncOutcome::failed( SyncOutcome::UNRESOLVED );
		}
		$changes = array(
			'url'        => $link->stored,
			'search_key' => $amazon->search_key(),
		);
		if ( PlaceholderName::is( $product->name ) ) {
			$changes['name'] = $amazon->search_page_title();
		}
		$this->library->save( $product->with( $changes ) );

		return SyncOutcome::succeeded( SyncOutcome::SEARCH_TITLE );
	}

	/**
	 * Whether a run is going.
	 */
	private function running(): bool {
		$run = $this->runs->get();

		return null !== $run && $run->is_running( (int) ( $this->clock )() );
	}

	/**
	 * The pending set's fingerprint, as Lasso's MD5 of the IDs joined by commas.
	 *
	 * @param list<int> $ids The pending products, lowest first.
	 */
	private static function fingerprint( array $ids ): string {
		return md5( implode( ',', $ids ) );
	}
}
