<?php
/**
 * Rebuilds the link index.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkIndex;

use Affibloom\Application\Products\BackgroundJob;
use Affibloom\Domain\LinkIndex\LinkIndexStats;
use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Domain\LinkIndex\RebuildStateRepository;

/**
 * The rebuild (F21 D5, D11, D18, R14-R16, R21): inline from WP-CLI, or in the background as chained batches (ADR-4:
 * each batch schedules the next through the background job; the state says how far it is, so a batch that died is
 * resumed from there), and a daily pass that drops the rows of posts that are no longer indexed and indexes the posts
 * changed in the last day.
 */
final class LinkIndexRebuild {

	/** The daily pass looks back a little over a day, so a late run misses nothing. */
	private const DAILY_WINDOW = 25 * 3600;

	/**
	 * Builds the rebuild.
	 *
	 * @param Hosts                  $hosts   The posts to index.
	 * @param LinkIndexer            $indexer Indexes one.
	 * @param RebuildStateRepository $state   Where a background run stands.
	 * @param BackgroundJob          $job     Runs the next batch soon.
	 * @param LinkLocationRepository $rows    The index, for the daily pass.
	 */
	public function __construct(
		private Hosts $hosts,
		private LinkIndexer $indexer,
		private RebuildStateRepository $state,
		private BackgroundJob $job,
		private LinkLocationRepository $rows,
	) {}

	/**
	 * Indexes every post, or the one given, in this request, and says what was found.
	 *
	 * @param int  $post_id One post, or 0 for all.
	 * @param bool $dry_run True to only report.
	 */
	public function run_inline( int $post_id = 0, bool $dry_run = false ): LinkIndexStats {
		$hosts = 0;
		$rows  = array();
		foreach ( $post_id > 0 ? array( $post_id ) : $this->hosts->ids() as $host ) {
			$found = $this->indexer->index( $host, $dry_run );
			if ( null !== $found ) {
				++$hosts;
				array_push( $rows, ...$found );
			}
		}

		return LinkIndexStats::of( $hosts, $rows );
	}

	/**
	 * Starts a full background rebuild (R15). False when one is already running (35G's "A rebuild is already running.").
	 */
	public function start(): bool {
		$state = $this->state->get();
		if ( $state->running() ) {
			return false;
		}
		$this->state->save( $state->started( count( $this->hosts->ids() ), gmdate( 'Y-m-d H:i:s' ) ) );
		$this->job->dispatch();

		return true;
	}

	/**
	 * Indexes the next batch of a running rebuild and schedules the one after; finishes the run after the last.
	 *
	 * @return bool Whether more batches follow.
	 */
	public function next_batch(): bool {
		$state = $this->state->get();
		if ( ! $state->running() ) {
			return false;
		}

		$batch = array_slice( $this->hosts->ids(), $state->done, self::batch_size( $state->total ) );
		$rows  = 0;
		foreach ( $batch as $host ) {
			$rows += count( $this->indexer->index( $host ) ?? array() );
		}
		$state = $state->advanced( count( $batch ), $rows );

		if ( array() === $batch || $state->done >= $state->total ) {
			$now = gmdate( 'Y-m-d H:i:s' );
			$this->state->save( $state->finished( $now, (int) strtotime( $now ) - (int) strtotime( $state->started_at ) ) );
			return false;
		}
		$this->state->save( $state );
		$this->job->dispatch();

		return true;
	}

	/**
	 * The daily pass (R21): rows of posts that are no longer indexed go; posts changed in the last day are indexed.
	 */
	public function daily(): void {
		$live = array_flip( $this->hosts->ids() );
		foreach ( $this->rows->host_ids() as $host ) {
			if ( ! isset( $live[ $host ] ) ) {
				$this->rows->delete_host( $host );
			}
		}
		foreach ( $this->hosts->changed_since( gmdate( 'Y-m-d H:i:s', time() - self::DAILY_WINDOW ) ) as $host ) {
			$this->indexer->index( $host );
		}
	}

	/**
	 * Lasso's batch size (R16): up to 500 posts, half of them, between 100 and 250; more, a fifth, between 200 and 500.
	 *
	 * @param int $total Posts in the run.
	 */
	public static function batch_size( int $total ): int {
		return $total <= 500
			? (int) min( 250, max( 100, ceil( $total / 2 ) ) )
			: (int) min( 500, max( 200, ceil( $total / 5 ) ) );
	}
}
