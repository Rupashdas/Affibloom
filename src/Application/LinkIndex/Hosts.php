<?php
/**
 * Lists the posts the index covers.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkIndex;

/**
 * The host posts of a full rebuild (F21 D6, D7).
 */
interface Hosts {

	/**
	 * Every indexed post's ID, in ID order.
	 *
	 * @return list<int>
	 */
	public function ids(): array;
}
