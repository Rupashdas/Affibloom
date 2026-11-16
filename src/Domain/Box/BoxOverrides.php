<?php
/**
 * What one box asks of its product: which product, and the box's own texts and values.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Box;

/**
 * One box's overrides (F5 R65-R89, F7), read from the block's attributes or from Lasso's shortcode attribute names (one
 * mapping, used by the block, `[affibloom]` 18S2 and `[lasso]` 27A). A text field is '' when the box does not set it
 * and `hide` (Lasso's word) when the box hides it. Task 20A holds the texts and the image, 20B the theme, buttons,
 * tracking ID, disclosure, anchor and phone layout; 20C adds the rest. Lasso's `ga` and `brag` are read nowhere (R87).
 */
final class BoxOverrides {

	/**
	 * Lasso's word for "hide this element".
	 */
	public const HIDE = 'hide';

	/**
	 * Lasso's line break code in a description attribute (LASSO_BR_CODE).
	 */
	private const BR_CODE = 'lasso-br-code';

	/**
	 * The tags a title may be wrapped in (R12, R79). Lasso prints any value unchecked; Affibloom keeps the headings and
	 * the plain containers, so an attribute cannot add markup.
	 *
	 * @var list<string>
	 */
	private const TITLE_TAGS = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'span' );

	/**
	 * Use from_block() or from_shortcode().
	 *
	 * @param int    $product_id  The product's ID; 0 when none was given.
	 * @param string $badge       The badge (R66): '' the product's, `hide`, or the text.
	 * @param string $description The description (R67): '' the product's, `hide`, or the text (it forces the block on).
	 * @param string $title       The name (R68): '' the product's, `hide`, or the text.
	 * @param string $title_url   Where the title and the image link (R76); '' the product's link.
	 * @param string $title_type  The tag wrapping the title (R79); '' none.
	 * @param string $image_url   The picture (R69): '' the product's, `hide`, or an address.
	 * @param string $image_alt   The picture's alternative text (R69); '' the name.
	 * @param string $theme       The box's theme (R65); '' the product's or the display type's.
	 * @param string $primary_url Button 1's link, and the title's and the image's (R75); '' the product's.
	 * @param string $primary_text Button 1's text (R75): '' the product's, `hide`, or the text.
	 * @param string $secondary_url The second button's link (R75); '' the product's first added button's.
	 * @param string $secondary_text The second button's text (R75): '' the button's own, `hide`, or the text.
	 * @param string $tracking_id The Amazon tag for the box's Amazon links (R82); '' the stored ones.
	 * @param string $disclosure_text The disclosure (R73): '' the product's rule; a text replaces it and shows it.
	 * @param string $anchor_id   The container's `id` (R81); '' Lasso's anchor.
	 * @param string $link_id     The link location the box stands for (R9, R81); '' none.
	 * @param string $style       `mobile` or `desktop` (R80): the layout drawn at any width; '' the viewport's.
	 */
	private function __construct(
		public readonly int $product_id,
		public readonly string $badge = '',
		public readonly string $description = '',
		public readonly string $title = '',
		public readonly string $title_url = '',
		public readonly string $title_type = '',
		public readonly string $image_url = '',
		public readonly string $image_alt = '',
		public readonly string $theme = '',
		public readonly string $primary_url = '',
		public readonly string $primary_text = '',
		public readonly string $secondary_url = '',
		public readonly string $secondary_text = '',
		public readonly string $tracking_id = '',
		public readonly string $disclosure_text = '',
		public readonly string $anchor_id = '',
		public readonly string $link_id = '',
		public readonly string $style = '',
	) {}

	/**
	 * Reads the block's attributes (`affibloom/product-box`): Lasso's values under camelCase names, each hide a switch
	 * of its own (`hideBadge`, …). A missing or malformed ID is 0, which draws nothing.
	 *
	 * @param array<string, mixed> $attributes The block's attributes.
	 */
	public static function from_block( array $attributes ): self {
		$text = static function ( string $name, string $hide = '' ) use ( $attributes ): string {
			if ( '' !== $hide && true === ( $attributes[ $hide ] ?? false ) ) {
				return self::HIDE;
			}

			return is_string( $attributes[ $name ] ?? null ) ? trim( $attributes[ $name ] ) : '';
		};

		return self::make(
			$attributes['productId'] ?? 0,
			array(
				'badge'           => $text( 'badge', 'hideBadge' ),
				'description'     => $text( 'description', 'hideDescription' ),
				'title'           => $text( 'title', 'hideTitle' ),
				'title_url'       => $text( 'titleUrl' ),
				'title_type'      => $text( 'titleType' ),
				'image_url'       => $text( 'imageUrl', 'hideImage' ),
				'image_alt'       => $text( 'imageAlt' ),
				'theme'           => $text( 'theme' ),
				'primary_url'     => $text( 'primaryUrl' ),
				'primary_text'    => $text( 'primaryText' ),
				'secondary_url'   => $text( 'secondaryUrl' ),
				'secondary_text'  => $text( 'secondaryText' ),
				'tracking_id'     => $text( 'trackingId' ),
				'disclosure_text' => $text( 'disclosureText' ),
				'anchor_id'       => $text( 'anchorId' ),
				'link_id'         => $text( 'linkId' ),
				'style'           => $text( 'style' ),
			)
		);
	}

	/**
	 * Reads Lasso's shortcode attributes (`id`, `badge`, `description`, `title`, `title_url`, `title_type`,
	 * `image_url`, `image_alt`, `theme`, `primary_url`, `primary_text`, `secondary_url`, `secondary_text`, `tracking_id`,
	 * `disclosure_text`, `anchor_id`, `link_id`, `style`, `origin`), as `[lasso]` and `[affibloom]` take them.
	 *
	 * @param array<string, mixed> $attributes The shortcode's attributes.
	 */
	public static function from_shortcode( array $attributes ): self {
		$text = static fn( string $name ): string => is_scalar( $attributes[ $name ] ?? null ) ? trim( (string) $attributes[ $name ] ) : '';

		return self::make(
			$attributes['id'] ?? 0,
			array(
				'badge'           => $text( 'badge' ),
				'description'     => $text( 'description' ),
				'title'           => $text( 'title' ),
				'title_url'       => $text( 'title_url' ),
				'title_type'      => $text( 'title_type' ),
				'image_url'       => $text( 'image_url' ),
				'image_alt'       => $text( 'image_alt' ),
				'theme'           => $text( 'theme' ),
				'primary_url'     => $text( 'primary_url' ),
				'primary_text'    => $text( 'primary_text' ),
				'secondary_url'   => $text( 'secondary_url' ),
				'secondary_text'  => $text( 'secondary_text' ),
				'tracking_id'     => $text( 'tracking_id' ),
				'disclosure_text' => $text( 'disclosure_text' ),
				'anchor_id'       => $text( 'anchor_id' ),
				'link_id'         => $text( 'link_id' ),
				// A box in a text widget (Lasso's widget filter adds origin="widget") draws the phone layout.
				'style'           => '' === $text( 'style' ) && 'widget' === $text( 'origin' ) ? 'mobile' : $text( 'style' ),
			)
		);
	}

	/**
	 * Whether a field is set to Lasso's `hide`.
	 *
	 * @param string $value One of the text fields.
	 */
	public static function hides( string $value ): bool {
		return self::HIDE === $value;
	}

	/**
	 * The overrides from read values: the description's line break code becomes `<br>` (Lasso's shortcode, 258-259);
	 * a title tag outside TITLE_TAGS is dropped.
	 *
	 * @param mixed                 $id     The product ID as given.
	 * @param array<string, string> $values The text fields, keyed by property.
	 */
	private static function make( mixed $id, array $values ): self {
		$type = strtolower( $values['title_type'] );

		return new self(
			is_numeric( $id ) ? max( 0, (int) $id ) : 0,
			badge: $values['badge'],
			description: str_replace( self::BR_CODE, '<br>', $values['description'] ),
			title: $values['title'],
			title_url: $values['title_url'],
			title_type: in_array( $type, self::TITLE_TAGS, true ) ? $type : '',
			image_url: $values['image_url'],
			image_alt: $values['image_alt'],
			theme: $values['theme'],
			primary_url: $values['primary_url'],
			primary_text: $values['primary_text'],
			secondary_url: $values['secondary_url'],
			secondary_text: $values['secondary_text'],
			tracking_id: $values['tracking_id'],
			disclosure_text: $values['disclosure_text'],
			anchor_id: $values['anchor_id'],
			link_id: $values['link_id'],
			// R80: only Lasso's two words count, in any casing.
			style: in_array( strtolower( $values['style'] ), array( 'mobile', 'desktop' ), true ) ? strtolower( $values['style'] ) : '',
		);
	}
}
