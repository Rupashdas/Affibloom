<?php
/**
 * WP Recipe Maker recipes in a post.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress\Builders;

use WP_Post;

/**
 * F21 R32 (Lasso's scan_site_stripe_data_wp_recipe_maker): each recipe a post embeds (`[wprm-recipe id]`, a WPRM
 * block's `id`, or `<!--WPRM Recipe <id>-->`), or the recipe a `wprm_recipe` post is, adds the place `wprm:<id>`: its
 * notes, its instructions (a bare Amazon address there counts as a link), its ingredients' notes and links (the
 * recipe's own links when it uses custom ones, else each ingredient term's link and affiliate HTML) and its equipment
 * terms' link and affiliate HTML. Link type `WpRecipeMaker` is the classifier's. Read while WP Recipe Maker is active.
 */
final class WprmSource implements BuilderSource {

	/**
	 * The post's places with its recipes'.
	 *
	 * @param WP_Post               $post    The post.
	 * @param array<string, string> $sources Its places so far.
	 * @return array<string, string>
	 */
	public function read( WP_Post $post, array $sources ): array {
		if ( ! ActivePlugins::any( 'wp-recipe-maker/wp-recipe-maker.php' ) ) {
			return $sources;
		}
		$ids = self::recipe_ids( (string) $post->post_content );
		if ( 'wprm_recipe' === $post->post_type ) {
			$ids[] = (int) $post->ID;
		}
		foreach ( array_unique( array_filter( $ids ) ) as $id ) {
			if ( 'wprm_recipe' !== get_post_type( $id ) ) {
				continue;
			}
			$html = self::recipe_html( $id );
			if ( '' !== trim( $html ) ) {
				$sources[ 'wprm:' . $id ] = $html;
			}
		}

		return $sources;
	}

	/**
	 * Recipes are read through the posts that show them.
	 */
	public function host_type(): ?string {
		return null;
	}

	/**
	 * The recipes a post embeds.
	 *
	 * @param string $content The post's content.
	 * @return list<int>
	 */
	public static function recipe_ids( string $content ): array {
		$ids = array();
		foreach ( array( '/\[wprm-recipe\b[^\]]*\bid\s*=\s*["\']?(\d+)/i', '/<!--\s*WPRM Recipe\s+(\d+)\s*-->/i', '/<!--\s*wp:wp-recipe-maker\/recipe\s+\{[^}]*"id"\s*:\s*(\d+)/i' ) as $pattern ) {
			if ( preg_match_all( $pattern, $content, $found ) ) {
				$ids = array_merge( $ids, array_map( 'intval', $found[1] ) );
			}
		}

		return $ids;
	}

	/**
	 * A recipe's links as one piece of HTML.
	 *
	 * @param int $id The recipe.
	 */
	private static function recipe_html( int $id ): string {
		$parts  = array( self::text( get_post_meta( $id, 'wprm_notes', true ) ) );
		$custom = 'custom' === get_post_meta( $id, 'wprm_ingredient_links_type', true );

		foreach ( self::items( get_post_meta( $id, 'wprm_instructions', true ), 'instructions' ) as $step ) {
			$parts[] = self::amazon_links( self::text( $step['text'] ?? '' ) );
		}
		foreach ( self::items( get_post_meta( $id, 'wprm_ingredients', true ), 'ingredients' ) as $ingredient ) {
			$parts[] = self::text( $ingredient['notes'] ?? '' );
			$name    = self::text( $ingredient['name'] ?? '' );
			if ( $custom ) {
				$parts[] = self::anchor( $ingredient['link']['url'] ?? '', $name );
			} elseif ( isset( $ingredient['id'] ) ) {
				$parts[] = self::term_links( (int) $ingredient['id'], 'wprmp_ingredient_link', 'wprmp_ingredient_affiliate_html', $name );
			}
		}
		$equipment = get_post_meta( $id, 'wprm_equipment', true );
		foreach ( is_array( $equipment ) ? $equipment : array() as $item ) {
			if ( is_array( $item ) && isset( $item['id'] ) ) {
				$parts[] = self::term_links( (int) $item['id'], 'wprmp_equipment_link', 'wprmp_equipment_affiliate_html', self::text( $item['name'] ?? '' ) );
			}
		}

		return implode( "\n", array_filter( $parts ) );
	}

	/**
	 * WPRM's groups (instructions, ingredients) flattened to their items.
	 *
	 * @param mixed  $groups The stored groups.
	 * @param string $key    The groups' list key.
	 * @return list<array<string, mixed>>
	 */
	private static function items( mixed $groups, string $key ): array {
		$items = array();
		foreach ( is_array( $groups ) ? $groups : array() as $group ) {
			foreach ( is_array( $group ) && is_array( $group[ $key ] ?? null ) ? $group[ $key ] : array() as $item ) {
				if ( is_array( $item ) ) {
					$items[] = $item;
				}
			}
		}

		return $items;
	}

	/**
	 * An ingredient's or equipment's term: its link as an anchor and its affiliate HTML.
	 *
	 * @param int    $term_id   The term.
	 * @param string $link_key  Its link meta.
	 * @param string $html_key  Its affiliate HTML meta.
	 * @param string $name      The item's name, the anchor's text.
	 */
	private static function term_links( int $term_id, string $link_key, string $html_key, string $name ): string {
		return self::anchor( get_term_meta( $term_id, $link_key, true ), $name ) . self::text( get_term_meta( $term_id, $html_key, true ) );
	}

	/**
	 * An anchor for a stored link, or '' without one.
	 *
	 * @param mixed  $url  The link.
	 * @param string $text Its text.
	 */
	private static function anchor( mixed $url, string $text ): string {
		return is_string( $url ) && '' !== trim( $url ) ? '<a href="' . esc_url( trim( $url ) ) . '">' . esc_html( $text ) . '</a>' : '';
	}

	/**
	 * A bare Amazon address in an instruction made a link, as Lasso treats it (R32).
	 *
	 * @param string $html The instruction.
	 */
	private static function amazon_links( string $html ): string {
		return (string) preg_replace( '#(?<![="\'>])\bhttps?://(?:www\.)?(?:amazon\.[a-z.]+|amzn\.to)/[^\s<"\']+#i', '<a href="$0">$0</a>', $html );
	}

	/**
	 * A stored value as text, '' when it is not one.
	 *
	 * @param mixed $value The value.
	 */
	private static function text( mixed $value ): string {
		return is_string( $value ) ? $value : '';
	}
}
