<?php
/**
 * How a link or display is shown in the content.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * Lasso's display types (F21 R25, R27, D1: its strings, so the opportunity queries port as written): anchors are Text
 * or Image-only anchor text; a display is named by its type, a text link and any other kind counting as Single.
 */
final class DisplayType {

	/** An anchor with text. */
	public const TEXT = 'Text';

	/** An anchor around an image. */
	public const IMAGE = 'Image-only anchor text';

	/** A SiteStripe image (R31). */
	public const SITESTRIPE_IMAGE = 'Image';

	/** A SiteStripe iframe (R31). */
	public const SITESTRIPE_BOX = 'Product Box';

	/** A display's label by its type, as the shortcode's `type` or the block's writes it. */
	private const DISPLAYS = array(
		'button'  => 'Button',
		'image'   => 'Image',
		'grid'    => 'Grid',
		'list'    => 'List',
		'gallery' => 'Gallery',
	);

	/**
	 * A display's label: Button, Image, Grid, List or Gallery, else Single (R27: `link` and others are Single).
	 *
	 * @param string $type The display's type ("" for the single box).
	 */
	public static function of_display( string $type ): string {
		return self::DISPLAYS[ strtolower( $type ) ] ?? 'Single';
	}
}
