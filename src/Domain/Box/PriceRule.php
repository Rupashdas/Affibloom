<?php
/**
 * Lasso's rules for a box's price and Prime logo.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Box;

/**
 * When a box prints the price and the Prime logo (F5 R18-R20, R28, D7; Lasso's single.php 278-284 and Helper
 * is_display_v2_price_visible_for_link). The struck basis price (R21) is C1F's, behind the site's Show Discount Pricing.
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

	/**
	 * Whether the Prime logo prints (R20, D7, decision C4): an Amazon product, the site's Show Prime on, and the
	 * product's own Prime switch on (the opt-out that defaults on).
	 *
	 * @param bool $amazon  The product is an Amazon product.
	 * @param bool $site    The site's Show Prime.
	 * @param bool $product The product's Prime switch.
	 */
	public static function prime( bool $amazon, bool $site, bool $product ): bool {
		return $amazon && $site && $product;
	}
}
