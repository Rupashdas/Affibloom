<?php
/**
 * Thrive Architect's copy of a post.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress\Builders;

use WP_Post;

/**
 * F21 R23 (Lasso's get_thrive_plugin_post_content): a post edited with Thrive Architect (`tcb_editor_enabled`) is read
 * from `tve_updated_post` instead of `post_content`; a Thrive landing page from its template's copy; an empty post from
 * Thrive's `page` meta. Thrive prints the same through `the_content`, so render time keys it as "content".
 */
final class ThriveSource implements BuilderSource {

	/**
	 * The post's places, "content" being Thrive's copy.
	 *
	 * @param WP_Post               $post    The post.
	 * @param array<string, string> $sources Its places so far.
	 * @return array<string, string>
	 */
	public function read( WP_Post $post, array $sources ): array {
		if ( ! self::active() ) {
			return $sources;
		}
		$meta    = static fn( string $key ): string => is_string( get_post_meta( $post->ID, $key, true ) ) ? (string) get_post_meta( $post->ID, $key, true ) : '';
		$content = $sources['content'] ?? '';
		if ( '' !== $meta( 'tcb_editor_enabled' ) ) {
			$thrive  = $meta( 'tve_updated_post' );
			$content = '' !== $thrive ? htmlspecialchars_decode( $thrive, ENT_NOQUOTES ) : $content;
		} elseif ( '' === trim( $content ) && '' !== $meta( 'page' ) ) {
			$content = $meta( 'page' );
		}
		$template = $meta( 'tve_landing_page' );
		if ( '' !== $meta( 'tve_landing_set' ) && '' !== $template ) {
			$content = $meta( 'tve_updated_post_' . $template );
		}
		$sources['content'] = $content;

		return $sources;
	}

	/**
	 * Thrive keeps its links in posts, not a type of its own.
	 */
	public function host_type(): ?string {
		return null;
	}

	/**
	 * Thrive Architect, or Thrive's theme, which carries it.
	 */
	private static function active(): bool {
		return ActivePlugins::any( 'thrive-visual-editor/thrive-visual-editor.php' ) || 'thrive-theme' === get_template();
	}
}
