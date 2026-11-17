<?php
/**
 * Reads a host post's content for the index.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkIndex;

use Affibloom\Domain\LinkIndex\HostContent;

/**
 * A host post's scanned places (F21 D6, D7).
 */
interface ContentSource {

	/**
	 * The host and its HTML by source, or null when the post is not indexed (missing, another type, a status
	 * outside D6's, a revision).
	 *
	 * @param int $host_id The post.
	 */
	public function read( int $host_id ): ?HostContent;
}
