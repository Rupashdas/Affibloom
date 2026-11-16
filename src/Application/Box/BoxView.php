<?php
/**
 * Everything one box prints, decided; the templates only print it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Box;

use Affibloom\Domain\Box\LayoutFamily;

/**
 * Everything one Single box prints (F5 D1). Texts are raw: the templates escape or allowlist each (D4).
 */
final class BoxView {

	/**
	 * Builds the view; use BoxBuilder.
	 *
	 * @param LayoutFamily          $family      Which layout prints it.
	 * @param string                $anchor      The container's `id`.
	 * @param string                $theme       The theme's class (`affibloom-<theme>`), for the lone button's container.
	 * @param string                $classes     The display wrapper's classes.
	 * @param array<string, string> $wrapper     The display wrapper's other attributes (DisplayPresentation).
	 * @param string                $badge       The badge text; '' for none.
	 * @param bool                  $badge_override The Badge element is off, so the badge takes the class the hide rule
	 *                                              skips (R62).
	 * @param array<string, string> $link        The image and title links' attributes (the box's title URL, R76).
	 * @param string                $image_src   The image's address.
	 * @param string                $image_alt   The image's alternative text.
	 * @param bool                  $image_hidden The classic box hides the picture itself (no wrapper flag).
	 * @param string                $title       The name, decoded; '' prints no title.
	 * @param bool                  $title_linked The title is a link (Link from Display Title), else a span.
	 * @param string                $title_tag   The tag wrapping the title (R79); '' none.
	 * @param BoxStars|null         $stars       The Primary Rating's stars (R14); null prints none.
	 * @param string                $price       The price to print, small HTML; '' when it does not show.
	 * @param string                $discount    The struck basis price (R21, R74); '' prints none.
	 * @param bool                  $price_block The price block prints (a shown price, or Prime).
	 * @param bool                  $prime       The Prime logo prints.
	 * @param string|null           $description The description, rich text; null prints no description block.
	 * @param list<BoxField>        $fields      The custom fields block (R23); empty prints none.
	 * @param list<BoxButton>       $buttons     The buttons, in order.
	 * @param string|null           $disclosure  The disclosure text; null when it does not show.
	 * @param string|null           $date        The price timestamp (R29): the last update ('' unknown); null when it does
	 *                                           not show.
	 * @param array<string, string> $inline      A text link's or an inline value's own data (F31 R11, A40; Task 18S5).
	 * @param string                $name        The name as the links show it (the box's title, decoded), whatever the
	 *                                           Title element says: a list card prints it (F30 R22).
	 * @param string                $slug        The product's slug, for a list card's class.
	 */
	public function __construct(
		public readonly LayoutFamily $family,
		public readonly string $anchor,
		public readonly string $theme,
		public readonly string $classes,
		public readonly array $wrapper,
		public readonly string $badge,
		public readonly bool $badge_override,
		public readonly array $link,
		public readonly string $image_src,
		public readonly string $image_alt,
		public readonly bool $image_hidden,
		public readonly string $title,
		public readonly bool $title_linked,
		public readonly string $title_tag,
		public readonly ?BoxStars $stars,
		public readonly string $price,
		public readonly string $discount,
		public readonly bool $price_block,
		public readonly bool $prime,
		public readonly ?string $description,
		public readonly array $fields,
		public readonly array $buttons,
		public readonly ?string $disclosure,
		public readonly ?string $date,
		public readonly array $inline = array(),
		public readonly string $name = '',
		public readonly string $slug = '',
	) {}

	/**
	 * A copy drawn by another template (the shortcode's lone button or image, F31 A10).
	 *
	 * @param LayoutFamily $family The template.
	 */
	public function as_family( LayoutFamily $family ): self {
		return new self( ...array_merge( get_object_vars( $this ), array( 'family' => $family ) ) );
	}

	/**
	 * A copy with a text link's or an inline value's data (F31 R11, A40).
	 *
	 * @param array<string, string> $inline The data.
	 */
	public function with_inline( array $inline ): self {
		return new self( ...array_merge( get_object_vars( $this ), array( 'inline' => $inline ) ) );
	}

	/**
	 * A copy with other buttons (the lone secondary button, F31 A34).
	 *
	 * @param list<BoxButton> $buttons The buttons.
	 */
	public function with_buttons( array $buttons ): self {
		return new self( ...array_merge( get_object_vars( $this ), array( 'buttons' => $buttons ) ) );
	}

	/**
	 * A copy with another container `id` (the shortcode's deterministic anchor, F31 D5).
	 *
	 * @param string $anchor The container's `id`.
	 */
	public function with_anchor( string $anchor ): self {
		return new self( ...array_merge( get_object_vars( $this ), array( 'anchor' => $anchor ) ) );
	}

	/**
	 * A copy without the theme's class (the lone button with a theme name outside the 8, F30 D10).
	 */
	public function without_theme(): self {
		return new self( ...array_merge( get_object_vars( $this ), array( 'theme' => '' ) ) );
	}

	/**
	 * A compact grid card (F30 R13, N1): no description and no fields (its disclosure is already gone, as_card()).
	 */
	public function compact(): self {
		return new self(
			...array_merge(
				get_object_vars( $this ),
				array(
					'description' => null,
					'fields'      => array(),
				)
			)
		);
	}

	/**
	 * A grid's card (F30 R16, R17): no container id of its own and no disclosure (the grid prints one after its cards;
	 * Lasso hides a card's by CSS), the wrapper saying so.
	 */
	public function as_card(): self {
		$wrapper = $this->wrapper;
		if ( array() !== $wrapper ) {
			$style = $wrapper['style'] ?? null;
			unset( $wrapper['style'] );
			$wrapper['data-hide-disclosure'] = '1';
			if ( null !== $style ) {
				$wrapper['style'] = $style;
			}
		}

		return new self(
			...array_merge(
				get_object_vars( $this ),
				array(
					'anchor'     => '',
					'disclosure' => null,
					'wrapper'    => $wrapper,
				)
			)
		);
	}
}
