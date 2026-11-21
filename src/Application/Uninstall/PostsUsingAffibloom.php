<?php
/**
 * The posts that still use Affibloom (Task 38).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Uninstall;

/**
 * The posts that still use Affibloom (Task 38; F37 AC6, F9 AC16): the posts whose boxes, displays or links would stop
 * drawing if Affibloom's data were removed, for the Uninstall card.
 */
interface PostsUsingAffibloom {

	/**
	 * The newest posts, and how many there are in all.
	 *
	 * @param int $limit How many IDs to give.
	 * @return array{ids: list<int>, total: int}
	 */
	public function first( int $limit ): array;
}
