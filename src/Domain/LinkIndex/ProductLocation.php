<?php
/**
 * One place a product is used, as its Locations tab lists it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

use Affibloom\Domain\Display\ShortcodeFormat;

/**
 * One row of a product's Locations tab (F12 L7; Task 36B): the index row's ID, how it is shown (its display type) and
 * what "Link with Context" prints: the anchor text of a text link, the address of an image link, the shortcode or
 * block of a display.
 */
final class ProductLocation {

	/** A text link. */
	public const TEXT = 'text';

	/** A link around an image. */
	public const IMAGE = 'image';

	/** A display of one product. */
	public const SINGLE = 'single';

	/** Group displays, by their layout. */
	public const GRID    = 'grid';
	public const LIST    = 'list';
	public const GALLERY = 'gallery';

	/** How the index writes a block display's "URL": the block's name, a space, its addressing attributes as JSON. */
	private const BLOCK = 'affibloom/product-box ';

	/**
	 * Describes the row.
	 *
	 * @param int         $id           The index row's ID.
	 * @param string      $display_type DisplayType's string.
	 * @param string      $url          The link, or a display's markup.
	 * @param string|null $anchor_text  The link's text.
	 * @param string|null $display_ref  "group:<id>" for a group display.
	 * @param string      $group_slug   That group's slug, so a group block can be shown as its shortcode; '' unknown.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $display_type,
		public readonly string $url,
		public readonly ?string $anchor_text = null,
		public readonly ?string $display_ref = null,
		public readonly string $group_slug = '',
	) {}

	/**
	 * What kind of place it is (Lasso's Type column, class-html-helper.php 622-659): a text or image link, a single
	 * display, or a group display by its layout.
	 */
	public function kind(): string {
		return match ( $this->display_type ) {
			DisplayType::TEXT => self::TEXT,
			DisplayType::IMAGE, DisplayType::SITESTRIPE_IMAGE => self::IMAGE,
			'Grid' => self::GRID,
			'List' => self::LIST,
			'Gallery' => self::GALLERY,
			default => self::SINGLE,
		};
	}

	/**
	 * Whether it is a display (its markup is what the tab prints) rather than a link.
	 */
	public function is_display(): bool {
		return ! in_array( $this->kind(), array( self::TEXT, self::IMAGE ), true );
	}

	/**
	 * "Link with Context": a text link's text (its address when it has none), an image link's address, a display's
	 * shortcode. A block reads as the shortcode it equals, as Lasso shows a display's shortcode (F12 L7; Task 39A
	 * finding UX1); one that names nothing that can be written (its group gone) keeps its markup.
	 */
	public function context(): string {
		if ( self::TEXT === $this->kind() && null !== $this->anchor_text && '' !== $this->anchor_text ) {
			return $this->anchor_text;
		}
		if ( str_starts_with( $this->url, self::BLOCK ) ) {
			$block = json_decode( substr( $this->url, strlen( self::BLOCK ) ), true );
			$text  = is_array( $block ) ? ShortcodeFormat::format(
				is_string( $block['type'] ?? null ) ? $block['type'] : '',
				is_int( $block['productId'] ?? null ) ? $block['productId'] : 0,
				$this->group_slug,
				array()
			) : '';
			if ( '' !== $text ) {
				return $text;
			}
		}

		return $this->url;
	}
}
