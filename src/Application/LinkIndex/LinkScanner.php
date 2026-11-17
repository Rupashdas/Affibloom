<?php
/**
 * Finds the links in a piece of HTML.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkIndex;

use Affibloom\Domain\LinkIndex\DisplayCandidate;
use Affibloom\Domain\LinkIndex\LinkCandidate;

/**
 * The anchors of some HTML that the index keeps (F21 R24, R25), in document order, and its displays (R27).
 */
interface LinkScanner {

	/**
	 * The links.
	 *
	 * @param string $html Post content or another scanned place.
	 * @return list<LinkCandidate>
	 */
	public function scan( string $html ): array;

	/**
	 * The displays (R27, deviation 146: Affibloom's shortcode and block only), blocks first, then shortcodes.
	 *
	 * @param string $html Post content or another scanned place.
	 * @return list<DisplayCandidate>
	 */
	public function displays( string $html ): array;
}
