<?php
/**
 * What a host post holds for the index.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * A host post (F21 D6, D7) and the HTML of each place in it that is scanned, by source ("content" in Task 35A; ACF
 * fields and builders add theirs in 35D and 35E).
 */
final class HostContent {

	/**
	 * Describes the host.
	 *
	 * @param int                   $id      The post.
	 * @param string                $url     Its permalink.
	 * @param array<string, string> $sources The HTML by source.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $url,
		public readonly array $sources,
	) {}
}
