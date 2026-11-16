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
 * button and image, 18S5 the text link and the inline value (SiteStripe is the single box's `sitestripe`); 18S7 and
 * the DT tasks add the group kinds (DT2 the grid).
 */
enum DisplayKind: string {

	case Single  = 'single';
	case Button  = 'button';
	case Image   = 'image';
	case Link    = 'link';
	case Field   = 'field';
	case Grid    = 'grid';
	case List    = 'list';
	case Gallery = 'gallery';
	case AiText  = 'ai-text';

	/**
	 * The kind in a shortcode's attributes: `type="button"`, `type="image"` or `"image-link"` (AmaLinks Pro's), or
	 * `style="image"`; anything else is the single box.
	 *
	 * @param array<string, string> $attributes Repaired attributes.
	 */
	public static function of( array $attributes ): self {
		$type = strtolower( $attributes['type'] ?? '' );
		// F30 R2 (1): a prompt without a product (no id or slug) is the AI text, before anything else.
		if ( '' !== trim( $attributes['prompt'] ?? '' ) && '' === trim( $attributes['id'] ?? '' ) && '' === trim( $attributes['slug'] ?? '' ) ) {
			return self::AiText;
		}
		// F30 R2: a group (`group`, or Lasso's `category`/`categories`, F31 A8) is a list with type list, a gallery with
		// type gallery, any other type a grid.
		if ( '' !== GroupAttributes::slug( $attributes ) ) {
			return match ( $type ) {
				'list'    => self::List,
				'gallery' => self::Gallery,
				default   => self::Grid,
			};
		}
		if ( 'button' === $type ) {
			return self::Button;
		}
		if ( 'link' === $type ) {
			return self::Link;
		}
		// F31 D2: [affibloom] asks for an inline value with type field and a value part (Lasso: fields and value).
		if ( 'field' === $type ) {
			return self::Field;
		}
		if ( 'image' === $type || 'image-link' === $type || 'image' === strtolower( $attributes['style'] ?? '' ) ) {
			return self::Image;
		}

		return self::Single;
	}
}
