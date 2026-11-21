<?php
/**
 * A link found in a post.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Opportunity;

use Affibloom\Domain\LinkIndex\ProductLocation;

/**
 * A row of Detected Links and of a post's links (F22 R20, R21; Tasks O2, O4): an untied link and the post it is in.
 */
final class DetectedLink {

	/**
	 * Describes the row.
	 *
	 * @param ProductLocation $place      The link: its index row, how it is shown, its address and text.
	 * @param int             $host_id    The post it is in.
	 * @param string          $host_title The post's title.
	 * @param string          $host_url   The post's permalink, as indexed.
	 */
	public function __construct(
		public readonly ProductLocation $place,
		public readonly int $host_id,
		public readonly string $host_title,
		public readonly string $host_url,
	) {}
}
