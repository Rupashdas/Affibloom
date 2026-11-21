<?php
/**
 * Which posts readers can see.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Clicks;

/**
 * Whether a post is one readers can open (F12 edge cases 1, 4; Task 33A): a click from a preview or a private post is
 * not counted.
 */
interface PostDirectory {

	/**
	 * Whether the post is published and public.
	 *
	 * @param int $post_id The post.
	 */
	public function is_public( int $post_id ): bool;
}
