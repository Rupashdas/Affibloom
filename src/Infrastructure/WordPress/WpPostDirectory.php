<?php
/**
 * Which posts readers can see, from WordPress.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\Clicks\PostDirectory;

/**
 * WordPress's own answer (F12 edge cases 1, 4; Task 33A): a published post of a public type; previews, drafts and
 * private posts are not.
 */
final class WpPostDirectory implements PostDirectory {

	/**
	 * Whether the post is published and public.
	 *
	 * @param int $post_id The post.
	 */
	public function is_public( int $post_id ): bool {
		return is_post_publicly_viewable( $post_id );
	}
}
