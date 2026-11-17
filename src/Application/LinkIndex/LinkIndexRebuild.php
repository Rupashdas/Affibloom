<?php
/**
 * Rebuilds the link index.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkIndex;

use Affibloom\Domain\LinkIndex\LinkIndexStats;

/**
 * The rebuild (F21 D5, D18): every indexed post, or one, indexed in this request. The background run in batches is
 * Task 35F's.
 */
final class LinkIndexRebuild {

	/**
	 * Builds the rebuild.
	 *
	 * @param Hosts       $hosts   The posts to index.
	 * @param LinkIndexer $indexer Indexes one.
	 */
	public function __construct( private Hosts $hosts, private LinkIndexer $indexer ) {}

	/**
	 * Indexes every post, or the one given, and says what was found.
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
}
