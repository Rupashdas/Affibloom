<?php
/**
 * Mediavine Create cards in a post.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress\Builders;

use WP_Post;

/**
 * F21 R32 (Lasso's scan_mediavine_create): each Create card a post embeds (`[mv_create key="<id>"]` or a `mv/recipe`
 * block's `id`), or the card an `mv_create` post is, adds its description (`{prefix}mv_creations.description`) as the
 * place `mv:<id>`; link type `MvCreate` is the classifier's. Read while Mediavine Create is active.
 */
final class MvCreateSource implements BuilderSource {

	/**
	 * The post's places with its cards' descriptions.
	 *
	 * @param WP_Post               $post    The post.
	 * @param array<string, string> $sources Its places so far.
	 * @return array<string, string>
	 */
	public function read( WP_Post $post, array $sources ): array {
		if ( ! ActivePlugins::any( 'mediavine-create/mediavine-create.php' ) ) {
			return $sources;
		}
		global $wpdb;

		$table = $wpdb->prefix . 'mv_creations';
		$ids   = self::card_ids( (string) $post->post_content );
		if ( 'mv_create' === $post->post_type ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Create's own table, once per indexed card.
			$ids[] = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE object_id = %d LIMIT 1', $table, $post->ID ) );
		}
		foreach ( array_unique( array_filter( $ids ) ) as $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- as above, once per card.
			$description = $wpdb->get_var( $wpdb->prepare( 'SELECT description FROM %i WHERE id = %d LIMIT 1', $table, $id ) );
			if ( is_string( $description ) && '' !== trim( $description ) ) {
				$sources[ 'mv:' . $id ] = $description;
			}
		}

		return $sources;
	}

	/**
	 * Create keeps its cards in its own table.
	 */
	public function host_type(): ?string {
		return null;
	}

	/**
	 * The cards a post embeds.
	 *
	 * @param string $content The post's content.
	 * @return list<int>
	 */
	public static function card_ids( string $content ): array {
		$ids = array();
		if ( preg_match_all( '/\[mv_create\b[^\]]*\bkey=[\'"]?(\d+)/i', $content, $found ) ) {
			$ids = array_map( 'intval', $found[1] );
		}
		if ( preg_match_all( '/<!--\s*wp:mv\/recipe\s+(\{.*?\})\s*\/?-->/s', $content, $blocks ) ) {
			foreach ( $blocks[1] as $json ) {
				$attributes = json_decode( $json, true );
				if ( is_array( $attributes ) && isset( $attributes['id'] ) ) {
					$ids[] = (int) $attributes['id'];
				}
			}
		}

		return $ids;
	}
}
