<?php
/**
 * Bulk add: up to ten links, added in the background.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\BulkRun;
use Affibloom\Domain\Product\BulkRunRepository;
use Closure;
use RuntimeException;

/**
 * Bulk add (F18 section 8): up to ten links, each added as a single link would be, in the background so the editor
 * can leave the page. Invalid lines and duplicates are skipped silently, as in Lasso.
 */
final class BulkAddService {

	/** Seconds one background pass may run; WP Background Processing's limit, which Lasso's queue uses. */
	private const BUDGET_SECONDS = 20.0;

	/**
	 * The clock, in seconds.
	 *
	 * @var Closure(): float
	 */
	private Closure $clock;

	/**
	 * Builds the service.
	 *
	 * @param ProductService     $products What adds one link.
	 * @param BulkRunRepository  $runs     Where the run is kept.
	 * @param BackgroundJob      $job      What runs process() later.
	 * @param (Closure(): float) $clock    Seconds now; microtime() unless a test gives another.
	 */
	public function __construct(
		private ProductService $products,
		private BulkRunRepository $runs,
		private BackgroundJob $job,
		?Closure $clock = null,
	) {
		$this->clock = $clock ?? static fn(): float => microtime( true );
	}

	/**
	 * Starts a run with the first ten non-blank lines.
	 *
	 * @param list<string> $lines What was typed, one link per line.
	 * @return int How many links the run has.
	 * @throws BulkAddRunning When a run is still going.
	 * @throws InvalidField   With "urls"/"empty" when no line has a link.
	 */
	public function start( array $lines ): int {
		$now     = (int) ( $this->clock )();
		$current = $this->runs->get();
		if ( null !== $current && $current->is_running( $now ) ) {
			throw new BulkAddRunning();
		}

		$run = BulkRun::start( $lines, $now );
		if ( 0 === $run->total ) {
			throw new InvalidField( 'urls', 'empty' );
		}

		$this->runs->save( $run );
		$this->job->dispatch();
		return $run->total;
	}

	/**
	 * How the run stands. While it is going, the job is woken too: a run that WordPress's spawn lock delayed, or whose
	 * next pass no visitor has triggered, finishes while the editor watches.
	 *
	 * @return array{running: bool, percent: int}
	 */
	public function progress(): array {
		$run = $this->runs->get();
		if ( null === $run || ! $run->is_running( (int) ( $this->clock )() ) ) {
			return array(
				'running' => false,
				'percent' => 100,
			);
		}

		$this->job->dispatch();
		return array(
			'running' => true,
			'percent' => $run->percent(),
		);
	}

	/**
	 * One background pass: adds links one at a time, saving the run after each, until none remain or the budget is
	 * spent; then wakes the job again if links remain. A link handled twice (two passes at once) is a duplicate the
	 * second time, so it creates nothing.
	 */
	public function process(): void {
		$started  = ( $this->clock )();
		$deadline = $started + self::BUDGET_SECONDS;

		while ( true ) {
			$run  = $this->runs->get();
			$link = null !== $run && $run->is_running( (int) $started ) ? $run->next() : null;
			if ( null === $run || null === $link ) {
				return;
			}

			try {
				$this->products->create_from_url( $link );
			} catch ( InvalidField | RuntimeException $skipped ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Lasso skips a link it cannot add, silently.
				unset( $skipped );
			}

			$run = $run->advanced();
			$this->runs->save( $run );

			if ( null !== $run->next() && ( $this->clock )() >= $deadline ) {
				$this->job->dispatch();
				return;
			}
		}
	}
}
