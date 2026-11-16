<?php
/**
 * Lasso's rule for a box's disclosure.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Box;

/**
 * The disclosure a box prints (F5 R24, R25; Lasso's Helper 3236-3268): the product's own text, trimmed, else the
 * site's; shown when the product's Show Disclosure is on and there is text. The site's "Disclosure in Single Display" is
 * only the default a new product starts with; a box's `disclosure_text` (R73, Task 20B) replaces the text and forces it on.
 */
final class DisclosureRule {

	/**
	 * The text the box prints, or null when the disclosure does not show.
	 *
	 * @param string $product_text The product's own disclosure text.
	 * @param bool   $product_on   The product's Show Disclosure.
	 * @param string $site_text    The site's disclosure text.
	 */
	public static function text( string $product_text, bool $product_on, string $site_text ): ?string {
		$text = trim( $product_text );
		if ( '' === $text ) {
			$text = $site_text;
		}

		return $product_on && '' !== $text ? $text : null;
	}
}
