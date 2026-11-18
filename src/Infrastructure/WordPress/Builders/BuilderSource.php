<?php
/**
 * A builder or recipe plugin's places in a post.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress\Builders;

use WP_Post;

/**
 * Where a plugin keeps a post's links outside `post_content` (F21 R23, R32; Task 35E), read only while the plugin is
 * active (Lasso's rule) and never written (D3). Each reader gets the post's places so far and answers with them
 * changed: it replaces "content" (Thrive, TablePress) or adds places of its own (`wprm:<id>`, `mv:<id>`).
 */
interface BuilderSource {

	/**
	 * The post's places with this plugin's.
	 *
	 * @param WP_Post               $post    The post.
	 * @param array<string, string> $sources Its places so far, by source name.
	 * @return array<string, string>
	 */
	public function read( WP_Post $post, array $sources ): array;

	/**
	 * A post type this plugin's links live in, indexed while it is active (TablePress's tables), or null.
	 */
	public function host_type(): ?string;
}
