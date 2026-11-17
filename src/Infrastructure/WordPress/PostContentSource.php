<?php
/**
 * A host post's content, for the link index.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\LinkIndex\ContentSource;
use Affibloom\Domain\LinkIndex\HostContent;

/**
 * The stored `post_content` of an indexed post (F21 D6, D7), as source "content". It is read, never written (D3).
 */
final class PostContentSource implements ContentSource {

	/**
	 * The host and its content, or null when the post is not indexed.
	 *
	 * @param int $host_id The post.
	 */
	public function read( int $host_id ): ?HostContent {
		$post = get_post( $host_id );
		if ( null === $post || ! in_array( $post->post_type, HostQuery::TYPES, true ) || ! in_array( $post->post_status, HostQuery::STATUSES, true ) ) {
			return null;
		}

		return new HostContent( (int) $post->ID, (string) get_permalink( $post ), array( 'content' => (string) $post->post_content ) );
	}
}
