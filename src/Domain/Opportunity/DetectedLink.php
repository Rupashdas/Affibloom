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
 * A row of Detected Links and of a post's links (F22 R20, R21, R29, R30; Tasks O2, O4): a link, the post it is in, and
 * whether it is tied to a product (a post's links list tied ones too, their Monetize on).
 */
final class DetectedLink {

	/**
	 * Describes the row.
	 *
	 * @param ProductLocation $place      The link: its index row, how it is shown, its address and text.
	 * @param int             $host_id    The post it is in.
	 * @param string          $host_title The post's title.
	 * @param string          $host_url   The post's permalink, as indexed.
	 * @param bool            $tied       Whether the link is tied to a product.
	 * @param string          $domain     The link's base domain; '' for a product or internal link.
	 * @param int             $assigned   The product Monetize tied it to at render time (Task O10); 0 when none, and
	 *                                    when $tied is true with 0 the link is tied by its own address.
	 * @param string          $assigned_name That product's name.
	 */
	public function __construct(
		public readonly ProductLocation $place,
		public readonly int $host_id,
		public readonly string $host_title,
		public readonly string $host_url,
		public readonly bool $tied = false,
		public readonly string $domain = '',
		public readonly int $assigned = 0,
		public readonly string $assigned_name = '',
	) {}
}
