<?php
/**
 * What a shortcode draws.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Shortcode;

/**
 * The display a shortcode asks for (F31 A8, A10, A11 `type`, `style`). Task 18S1 draws the single box, 18S4 the lone
 * button and image; 18S5-18S7 and the DT tasks add the other kinds.
 */
enum DisplayKind: string {

	case Single = 'single';
	case Button = 'button';
	case Image  = 'image';

	/**
	 * The kind in a shortcode's attributes: `type="button"`, `type="image"` or `"image-link"` (AmaLinks Pro's), or
	 * `style="image"`; anything else is the single box.
	 *
	 * @param array<string, string> $attributes Repaired attributes.
	 */
	public static function of( array $attributes ): self {
		$type = strtolower( $attributes['type'] ?? '' );
		if ( 'button' === $type ) {
			return self::Button;
		}
		if ( 'image' === $type || 'image-link' === $type || 'image' === strtolower( $attributes['style'] ?? '' ) ) {
			return self::Image;
		}

		return self::Single;
	}
}
