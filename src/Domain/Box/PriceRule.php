<?php
/**
 * Lasso's rules for a box's price and Prime logo.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Box;

/**
 * When a box prints the price (F5 R18-R19, R28; Lasso's single.php 278-284 and Helper is_display_v2_price_visible_for_link).
 * The struck basis price (R21) is C1F's, behind the site's Show Discount Pricing; Prime is C1G's PrimeRule.
 */
final class PriceRule {

	/**
	 * Whether the price value is printed (R19): the product's Show Price, else the site's; and only with a price.
	 *
	 * @param string    $price      The product's price as stored.
	 * @param bool|null $product    The product's Show Price; null when never stored.
	 * @param bool      $site       The site's Show Price.
	 */
	public static function prints( string $price, ?bool $product, bool $site ): bool {
		return '' !== $price && ( $product ?? $site );
	}

	/**
	 * Whether the advanced box's Price element shows (R28, Lasso #5997): the site's Price element, or the product's own
	 * Show Price saved on — the site element is only the default.
	 *
	 * @param bool      $element The Single type's Price element.
	 * @param bool|null $product The product's Show Price; null when never stored.
	 */
	public static function element_shows( bool $element, ?bool $product ): bool {
		return $element || true === $product;
	}
}
