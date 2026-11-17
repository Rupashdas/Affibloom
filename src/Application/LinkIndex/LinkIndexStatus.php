<?php
/**
 * The Link Index card's figures.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkIndex;

use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Domain\LinkIndex\RebuildStateRepository;

/**
 * What Settings → General's Link Index shows (F21 R51, D12; Tasks 35G, 9J): while a rebuild runs, how far it is and
 * its ETA (the time per post so far times the posts left); otherwise when the index was last written and how many links
 * it holds.
 */
final class LinkIndexStatus {

	/**
	 * Builds the status.
	 *
	 * @param RebuildStateRepository $state Where a rebuild stands.
	 * @param LinkLocationRepository $rows  The index.
	 */
	public function __construct( private RebuildStateRepository $state, private LinkLocationRepository $rows ) {}

	/**
	 * The figures: status (idle, running), done, total, eta (seconds, null before the first batch), last_updated (UTC,
	 * '' when never) and links_indexed.
	 *
	 * @param int $now Now, as a Unix time.
	 * @return array{status: string, done: int, total: int, eta: int|null, last_updated: string, links_indexed: int}
	 */
	public function figures( int $now ): array {
		$state = $this->state->get();
		$stats = $this->rows->stats();
		$eta   = null;
		if ( $state->running() && $state->done > 0 ) {
			$spent = max( 0, $now - (int) strtotime( $state->started_at . ' UTC' ) );
			$eta   = (int) round( $spent / $state->done * max( 0, $state->total - $state->done ) );
		}

		return array(
			'status'        => $state->status,
			'done'          => $state->done,
			'total'         => $state->total,
			'eta'           => $eta,
			'last_updated'  => $stats->latest,
			'links_indexed' => $stats->links,
		);
	}
}
