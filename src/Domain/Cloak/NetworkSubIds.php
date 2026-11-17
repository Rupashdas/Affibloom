<?php
/**
 * Which sub-ID parameter an affiliate network's link takes.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Cloak;

/**
 * The networks Lasso knows (F24 R22, D7): a link of one of them takes a reader's `sid` as its first sub-ID parameter.
 */
interface NetworkSubIds {

	/**
	 * The link's network's first sub-ID parameter ("subId1", "clickref", …), or null when the link is no known network's
	 * or its network takes none.
	 *
	 * @param string $url A destination.
	 */
	public function first_for( string $url ): ?string;
}
