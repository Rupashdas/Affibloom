<?php
/**
 * An affiliate product: what a box shows and where its buttons go.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

use Affibloom\Domain\Settings\Settings;

/**
 * An affiliate product: what a box shows and where its buttons go. Later tasks add its other fields as their
 * screens need them.
 */
final class Product {

	/**
	 * Builds a product. Use draft() for a new one.
	 *
	 * @param int|null     $id              Post ID; null until it is saved.
	 * @param string       $name            Name, shown as the box title.
	 * @param AffiliateUrl $url             Primary destination.
	 * @param ProductType  $type            Kind of merchant.
	 * @param bool         $new_tab         The primary link opens in a new tab.
	 * @param bool         $nofollow        The primary link carries rel="nofollow".
	 * @param bool         $sponsored       Links carry rel="sponsored".
	 * @param bool         $show_disclosure The box shows the disclosure.
	 * @param string       $asin            Amazon's product ID, as written in the link; '' for other links.
	 * @param string       $amazon_country  The Amazon store's country code ("us", "gb", …); '' for other links.
	 * @param string       $search_key      An Amazon search page's "<host>|<keywords>" (AmazonUrl::search_key()), or ''.
	 * @param string       $image_url       The product's image, as a URL; '' when it has none. Read by the Dashboard's
	 *                                      Image column (Task 13C); edited on the product page (Task 14).
	 * @param string       $button_text     The primary button's text; '' means the Display setting's text (Lasso's
	 *                                      buy_btn_text, whose placeholder is the site's).
	 * @param Buttons      $buttons         The buttons added to the default one (whose link is $url), in order, and where
	 *                                      the default one sits among them (F29, Task 14H).
	 */
	public function __construct(
		public readonly ?int $id,
		public readonly string $name,
		public readonly AffiliateUrl $url,
		public readonly ProductType $type,
		public readonly bool $new_tab,
		public readonly bool $nofollow,
		public readonly bool $sponsored,
		public readonly bool $show_disclosure,
		public readonly string $asin = '',
		public readonly string $amazon_country = '',
		public readonly string $search_key = '',
		public readonly string $image_url = '',
		public readonly string $button_text = '',
		public readonly Buttons $buttons = new Buttons( array() ),
	) {}

	/**
	 * A new, unsaved product with the site's link defaults as they are now. Lasso copies them when it creates a link
	 * (save_lasso_url reads get_lasso_url( 0 )'s new-record values and writes them as meta), so a later change of a
	 * site default does not reach products that already exist. The secondary button's defaults, Detect
	 * Opportunities, link cloaking and Commission Upgrades join this copy with their settings (Task 11E).
	 *
	 * @param string       $name     Name.
	 * @param AffiliateUrl $url      Primary destination.
	 * @param Settings     $defaults The site's settings.
	 */
	public static function draft( string $name, AffiliateUrl $url, Settings $defaults ): self {
		return new self(
			null,
			$name,
			$url,
			ProductType::Other,
			$defaults->bool( 'new_tab' ),
			$defaults->bool( 'nofollow' ),
			$defaults->bool( 'sponsored' ),
			$defaults->bool( 'show_disclosure' ),
		);
	}

	/**
	 * A copy with some properties changed, e.g. the ID once stored.
	 *
	 * @param array<string, mixed> $changes Property name => new value.
	 */
	public function with( array $changes ): self {
		return new self( ...array_merge( get_object_vars( $this ), $changes ) );
	}
}
