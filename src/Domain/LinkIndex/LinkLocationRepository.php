<?php
/**
 * Where the link index is stored.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * The link index's rows (F21 D1).
 */
interface LinkLocationRepository {

	/**
	 * Replaces a host's rows with these, in one transaction. A row whose key (LocationKey) was stored before keeps its
	 * first URL, its dismissal and its assignment (D10).
	 *
	 * @param int                $host_id The host post.
	 * @param list<LinkLocation> $rows    Its rows now.
	 */
	public function replace_host( int $host_id, array $rows ): void;

	/**
	 * Removes a host's rows (a post that is no longer indexed).
	 *
	 * @param int $host_id The host post.
	 */
	public function delete_host( int $host_id ): void;

	/**
	 * The whole index's figures.
	 */
	public function stats(): LinkIndexStats;
}
