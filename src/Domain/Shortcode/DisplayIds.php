<?php
/**
 * The products a post's single boxes draw, read from its content (Task 37B).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Shortcode;

/**
 * The products a post's single boxes draw, read from its content before it renders (Task 37B): the Product box
 * blocks of a single box, button or image (`productId`, F4 D3) and the `[affibloom id="…"]` shortcodes that are not a
 * group or a table. Groups and tables read their products together already. Each ID once, in the order found.
 */
final class DisplayIds {

	/** The block types and shortcode `type`s that draw one product (DisplayRequest::from_block). */
	private const SINGLE = array( '', 'single', 'button', 'image' );

	/**
	 * The product IDs, each once.
	 *
	 * @param string $content A post's content, before blocks and shortcodes render.
	 * @return list<int>
	 */
	public static function products( string $content ): array {
		$ids = array();
		if ( preg_match_all( '#<!--\s+wp:affibloom/product-box\s+(\{.*?\})\s+/?-->#s', $content, $blocks ) ) {
			foreach ( $blocks[1] as $json ) {
				$attributes = json_decode( $json, true );
				if ( is_array( $attributes ) && in_array( (string) ( $attributes['type'] ?? '' ), self::SINGLE, true ) ) {
					$ids[] = (int) ( $attributes['productId'] ?? 0 );
				}
			}
		}
		if ( preg_match_all( '/\[affibloom(\s[^\]]*)?\]/', $content, $shortcodes ) ) {
			foreach ( $shortcodes[1] as $text ) {
				$attributes = self::attributes( $text );
				if ( in_array( strtolower( $attributes['type'] ?? '' ), self::SINGLE, true ) ) {
					$ids[] = ctype_digit( $attributes['id'] ?? '' ) ? (int) $attributes['id'] : 0;
				}
			}
		}

		return array_values( array_unique( array_filter( $ids, static fn( int $id ): bool => $id > 0 ) ) );
	}

	/**
	 * A shortcode's attributes, as WordPress's shortcode_parse_atts() reads the quoted and bare forms.
	 *
	 * @param string $text The text between the tag's name and its closing bracket.
	 * @return array<string, string>
	 */
	private static function attributes( string $text ): array {
		$attributes = array();
		preg_match_all( '/([\w-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s\'"\]]+))/', $text, $pairs, PREG_SET_ORDER );
		foreach ( $pairs as $pair ) {
			$attributes[ strtolower( $pair[1] ) ] = $pair[2] . ( $pair[3] ?? '' ) . ( $pair[4] ?? '' );
		}

		return $attributes;
	}
}
