<?php
/**
 * One button after a product's primary one (F29).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * One button after a product's primary one: Lasso's secondary button, or an additional one (F29 requirements B1-B2).
 * The link is kept as text, as Lasso keeps `second_btn_url`; Task 15 validates what is saved.
 */
final class Button {

	/**
	 * Builds a button.
	 *
	 * @param string $url      Its link, as stored ('' for none: the box then shows no button).
	 * @param string $text     Its text; '' means the display type's secondary button text.
	 * @param bool   $new_tab  Its link opens in a new tab.
	 * @param bool   $nofollow Its link carries rel="nofollow".
	 */
	public function __construct(
		public readonly string $url,
		public readonly string $text,
		public readonly bool $new_tab = true,
		public readonly bool $nofollow = true,
	) {}

	/**
	 * Neither a link nor a text: nothing to keep.
	 */
	public function is_empty(): bool {
		return '' === trim( $this->url ) && '' === trim( $this->text );
	}
}
