<?php
/**
 * How a link is stored: the site's Amazon tag rule, and a button's link as Lasso stores its second button's.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\Amazon\TrackingIds;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;

/**
 * The link rules the add flow and the product page share. `tagged()` was the add flow's own Amazon step (Task 12A);
 * it moved here when the product page's buttons became its second user (Task 15A). `typed()` and `follows()` were the
 * add flow's first steps; they moved here when the product page's Primary URL became their second user (Task 15B).
 */
final class LinkNormaliser {

	/**
	 * Builds the rules.
	 *
	 * @param LinkResolver       $links    Follows a geni.us link to where it leads.
	 * @param SettingsRepository $settings The tracking ID rule, "Shorten Amazon links", "Keep geni.us formatting".
	 */
	public function __construct( private LinkResolver $links, private SettingsRepository $settings ) {}

	/**
	 * An Amazon link with the site's tag rule applied (Lasso's get_amazon_product_url with $monetize): the tag
	 * whitelist, "Shorten Amazon links"; short and protected links are left as they are.
	 *
	 * @param AmazonUrl $amazon   The link.
	 * @param Settings  $settings The site's settings.
	 */
	public function tagged( AmazonUrl $amazon, Settings $settings ): AffiliateUrl {
		return $amazon->monetized( TrackingIds::from_settings( $settings ), $settings->bool( 'shorten_amazon_links' ) );
	}

	/**
	 * A link as typed: a bare ASIN becomes the default store's product link (Task 12E), anything else a link with
	 * `https://` added where it has no scheme (Lasso's add_https).
	 *
	 * @param string $input What was typed or pasted.
	 * @throws InvalidField When it is empty, or neither a link nor a bare ASIN.
	 */
	public function typed( string $input ): AffiliateUrl {
		return AmazonUrl::from_bare_asin( $input ) ?? AffiliateUrl::from_string( $input );
	}

	/**
	 * Whether a link is stored as where it leads rather than as typed: an Amazon short link (Task 12D), and a geni.us
	 * link while "Keep geni.us formatting" is off (Lasso's remove_genius_cloaking_from_url).
	 *
	 * @param AffiliateUrl $url      The link as typed.
	 * @param Settings     $settings The site's settings.
	 */
	public function follows( AffiliateUrl $url, Settings $settings ): bool {
		return AmazonUrl::is_short( $url ) || ( $url->is_geni_us() && ! $settings->bool( 'keep_genius_formatting' ) );
	}

	/**
	 * A Primary URL as the add flow stores it (F1 requirements 9, S7): followed when `follows()` says so, then the
	 * site's tag rule when it leads to Amazon. One request at most, and only for a link that is followed.
	 *
	 * @param AffiliateUrl $typed    The link as typed (`typed()`).
	 * @param Settings     $settings The site's settings.
	 */
	public function primary( AffiliateUrl $typed, Settings $settings ): NormalizedLink {
		$destination = $this->follows( $typed, $settings ) ? $this->destination( $typed ) : $typed;
		$amazon      = AmazonUrl::from( $destination );

		return new NormalizedLink( $typed, null === $amazon ? $destination : $this->tagged( $amazon, $settings ), $amazon );
	}

	/**
	 * A button's link as Lasso stores `second_btn_url` (save_lasso_url 1152-1160, 1383; F1 requirements 9 S4, F29 D5):
	 * trimmed; a geni.us link becomes where it leads unless "Keep geni.us formatting" is on; `https://` added; an
	 * Amazon link tagged (an Amazon short link stays as typed and is not followed); then esc_url_raw. Never refused:
	 * something that is not a link is stored cleaned, as Lasso stores it.
	 *
	 * @param string $raw The field as typed.
	 */
	public function button( string $raw ): string {
		$typed = trim( $raw );
		if ( '' === $typed ) {
			return '';
		}

		try {
			$url = AffiliateUrl::from_string( $typed );
		} catch ( InvalidField $not_a_link ) {
			return esc_url_raw( AffiliateUrl::repair( $typed ) );
		}

		$settings = $this->settings->get();
		if ( $url->is_geni_us() && ! $settings->bool( 'keep_genius_formatting' ) ) {
			$url = $this->destination( $url );
		}

		$amazon = AmazonUrl::from( $url );
		if ( null !== $amazon ) {
			$url = $this->tagged( $amazon, $settings );
		}

		return esc_url_raw( $url->value() );
	}

	/**
	 * Where a link leads, or the link itself when that is not a link (Lasso: validate_url on the final target).
	 *
	 * @param AffiliateUrl $url The link.
	 */
	private function destination( AffiliateUrl $url ): AffiliateUrl {
		try {
			return AffiliateUrl::from_string( $this->links->resolve( $url )->url );
		} catch ( InvalidField $not_a_link ) {
			return $url;
		}
	}
}
