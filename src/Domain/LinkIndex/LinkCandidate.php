<?php
/**
 * One link found in a host's content, before it is classified.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * One anchor as the scanner found it (F21 R25): its attributes as written (entities decoded), whether it holds an
 * image, and its text with the white space collapsed.
 */
final class LinkCandidate {

	/**
	 * Describes the link.
	 *
	 * @param string $href      The `href`, trimmed.
	 * @param string $rel       The `rel`, as written.
	 * @param string $target    The `target`, as written.
	 * @param bool   $has_image Whether an `<img>` sits inside the anchor.
	 * @param string $text      The anchor's text.
	 */
	public function __construct(
		public readonly string $href,
		public readonly string $rel = '',
		public readonly string $target = '',
		public readonly bool $has_image = false,
		public readonly string $text = '',
	) {}
}
