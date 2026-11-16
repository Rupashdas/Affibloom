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
	 * @param string                $classes     The display wrapper's classes.
	 * @param array<string, string> $wrapper     The display wrapper's other attributes (DisplayPresentation).
	 * @param string                $badge       The badge text; '' for none.
	 * @param array<string, string> $link        The image and title links' attributes (the box's title URL, R76).
	 * @param string                $image_src   The image's address.
	 * @param string                $image_alt   The image's alternative text.
	 * @param string                $title       The name, decoded; '' prints no title.
	 * @param bool                  $title_linked The title is a link (Link from Display Title), else a span.
	 * @param string                $title_tag   The tag wrapping the title (R79); '' none.
	 * @param string                $price       The price to print, small HTML; '' when it does not show.
	 * @param bool                  $price_block The price block prints (a shown price, or Prime).
	 * @param bool                  $prime       The Prime logo prints.
	 * @param string|null           $description The description, rich text; null prints no description block.
	 * @param list<BoxButton>       $buttons     The buttons, in order.
	 * @param string|null           $disclosure  The disclosure text; null when it does not show.
	 */
	public function __construct(
		public readonly LayoutFamily $family,
		public readonly string $anchor,
		public readonly string $classes,
		public readonly array $wrapper,
		public readonly string $badge,
		public readonly array $link,
		public readonly string $image_src,
		public readonly string $image_alt,
		public readonly string $title,
		public readonly bool $title_linked,
		public readonly string $title_tag,
		public readonly string $price,
		public readonly bool $price_block,
		public readonly bool $prime,
		public readonly ?string $description,
		public readonly array $buttons,
		public readonly ?string $disclosure,
	) {}
}
