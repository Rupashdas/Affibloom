<?php
/**
 * A post that uses a product, with its places.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * One host post on a product's Locations tab (F12 L5, L6; Task 36B): its title, status and permalink, and the places in
 * it that use the product, in the index's order. One place is one row; several are a group row.
 */
final class LocationHost {

	/**
	 * Describes the host.
	 *
	 * @param int                   $id     The post.
	 * @param string                $title  Its title.
	 * @param string                $status Its status (publish, future, draft, pending, private; F21 D6).
	 * @param string                $url    Its permalink, as indexed.
	 * @param list<ProductLocation> $places The places in it.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $title,
		public readonly string $status,
		public readonly string $url,
		public readonly array $places,
	) {}

	/**
	 * Whether the post is published; any other status is shown beside its title (AC26).
	 */
	public function is_published(): bool {
		return 'publish' === $this->status;
	}
}
