<?php
/**
 * A post on Content Opportunities.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Opportunity;

/**
 * A row of Content Opportunities (F22 R24, R26; Task O3): a published post with indexed links, when it last changed,
 * how many links it holds and how many of them are tied to a product.
 */
final class ContentRow {

	/**
	 * Describes the row.
	 *
	 * @param int    $host_id   The post.
	 * @param string $title     Its title.
	 * @param string $url       Its permalink, as indexed.
	 * @param string $modified  When it last changed, "Y-m-d H:i:s" (site time).
	 * @param int    $links     Its indexed links.
	 * @param int    $monetized Those tied to a product.
	 */
	public function __construct(
		public readonly int $host_id,
		public readonly string $title,
		public readonly string $url,
		public readonly string $modified,
		public readonly int $links,
		public readonly int $monetized,
	) {}
}
