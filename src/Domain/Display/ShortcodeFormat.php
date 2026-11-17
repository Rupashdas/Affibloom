<?php
/**
 * A block's display as `[affibloom]` text, and back.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

use Affibloom\Domain\Shortcode\DisplayKind;

/**
 * The block's shortcode box (F4 R7, R41, R49; D4; Task 24C): a display written as the shortcode that draws the same,
 * in Lasso's order (the target first: `id`, `type`, `group`; then the overrides in the catalogue's order), and a typed
 * shortcode's attributes read back as a display. What the shortcode feature reads, this writes (F31).
 */
final class ShortcodeFormat {

	/** The types that show a group. */
	private const GROUP_TYPES = array( 'grid', 'list', 'gallery' );

	/**
	 * The display as shortcode text; '' when it names nothing.
	 *
	 * @param string               $type       The block's type ('' or `single` the single box).
	 * @param int                  $product    Its product's ID, for the product types.
	 * @param string               $group_slug Its group's slug, for the group types.
	 * @param array<string, mixed> $overrides  Its overrides, under Lasso's names.
	 */
	public static function format( string $type, int $product, string $group_slug, array $overrides ): string {
		$type  = '' === $type ? 'single' : $type;
		$parts = array();
		if ( in_array( $type, self::GROUP_TYPES, true ) ) {
			if ( '' === $group_slug ) {
				return '';
			}
			$parts = array(
				'type'  => $type,
				'group' => $group_slug,
			);
		} elseif ( $product > 0 ) {
			$parts = array( 'id' => (string) $product );
			if ( 'single' !== $type ) {
				$parts['type'] = $type;
			}
		} else {
			return '';
		}

		$text = '';
		foreach ( $parts + OverrideCatalog::overrides( $type, $overrides ) as $name => $value ) {
			$text .= ' ' . $name . '="' . $value . '"';
		}

		return '[affibloom' . $text . ']';
	}

	/**
	 * A typed shortcode's attributes (repaired, ShortcodeAttributes) as a display: its type, its product's ID or its group's
	 * slug, and its overrides. Null for one that names no product or group, or a kind the block does not draw.
	 *
	 * @param array<string, string> $attributes The repaired attributes.
	 * @return array{type: string, product: int, group: string, overrides: array<string, string>}|null
	 */
	public static function read( array $attributes ): ?array {
		$kind = DisplayKind::of( $attributes );
		$type = match ( $kind ) {
			DisplayKind::Single  => 'single',
			DisplayKind::Button  => 'button',
			DisplayKind::Image   => 'image',
			DisplayKind::Grid    => 'grid',
			DisplayKind::List    => 'list',
			DisplayKind::Gallery => 'gallery',
			default              => '',
		};
		if ( '' === $type ) {
			return null;
		}

		$group   = in_array( $type, self::GROUP_TYPES, true ) ? trim( $attributes['group'] ?? $attributes['categories'] ?? $attributes['category'] ?? '' ) : '';
		$product = '' === $group && ctype_digit( trim( $attributes['id'] ?? '' ) ) ? (int) trim( $attributes['id'] ) : 0;
		if ( 0 === $product && '' === $group ) {
			return null;
		}

		return array(
			'type'      => $type,
			'product'   => $product,
			'group'     => $group,
			'overrides' => OverrideCatalog::overrides( $type, $attributes ),
		);
	}
}
