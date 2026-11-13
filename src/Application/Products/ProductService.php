<?php
/**
 * Product use cases.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\Amazon\TrackingIds;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;
use RuntimeException;

/**
 * Product use cases.
 */
final class ProductService {

	/**
	 * Builds the service.
	 *
	 * @param ProductRepository  $products Product storage.
	 * @param LinkResolver       $links    Where a new link leads and what to call it.
	 * @param SettingsRepository $settings Site settings (keep_genius_formatting, the link defaults, the Amazon tag rule).
	 */
	public function __construct(
		private ProductRepository $products,
		private LinkResolver $links,
		private SettingsRepository $settings,
	) {}

	/**
	 * "Add A New Link" with one link (Lasso_Affiliate_Link::lasso_add_a_new_link):
	 * - a published product with the same link is returned instead of a new one (is_lasso_url_exist with
	 *   check_duplicate_link on, its default and the live value), before any request is made;
	 * - otherwise the link is resolved and the product named as Lasso names it (Task 11D);
	 * - an Amazon short link, and a geni.us link with keep_genius_formatting off, is stored as its destination, which
	 *   is then checked for a duplicate too (Task 12D; Lasso: $url = $get_final_url);
	 * - an Amazon store link is then looked for in Lasso's order (Tasks 12B, 12C) and, when new, stored as Lasso
	 *   stores it (Tasks 12A, 12C);
	 * - a product that is found keeps its name, except for Lasso's "Sorry!" re-title (Task 12C);
	 * - a new product takes the site's link defaults as they are now (Task 11E).
	 *
	 * @param string $input What was pasted.
	 * @throws InvalidField      When the input is empty or not a link.
	 * @throws RuntimeException When the product cannot be stored.
	 */
	public function create_from_url( string $input ): AddedLink {
		$url = AffiliateUrl::from_string( $input );

		$existing = $this->products->find_by_url_key( $url->key() );
		if ( null !== $existing ) {
			return $this->found( $existing, $url );
		}

		$resolved = $this->links->resolve( $url );
		$settings = $this->settings->get();

		if ( $resolved->url !== $url->value() && $this->stored_as_destination( $url, $settings ) ) {
			$url      = AffiliateUrl::from_string( $resolved->url );
			$existing = $this->products->find_by_url_key( $url->key() );
			if ( null !== $existing ) {
				return $this->found( $existing, $url );
			}
		}

		$amazon = AmazonUrl::from( $url );
		if ( null === $amazon ) {
			return new AddedLink( $this->products->save( Product::draft( $resolved->title, $url, $settings ) ), false );
		}

		$stored   = $amazon->monetized( TrackingIds::from_settings( $settings ), $settings->bool( 'shorten_amazon_links' ) );
		$existing = $this->existing_amazon( $amazon, $url, $stored );
		if ( null !== $existing ) {
			return $this->found( $existing, $url );
		}

		return new AddedLink( $this->products->save( $this->amazon_draft( $resolved->title, $amazon, $stored, $settings ) ), false );
	}

	/**
	 * Whether the link is replaced by where it leads before it is stored: an Amazon short link always
	 * (lasso_add_a_new_link: is_amazon_shortened_url), a geni.us link while Keep Geni.us Formatting is off.
	 *
	 * @param AffiliateUrl $url      The pasted link.
	 * @param Settings     $settings The site's settings.
	 */
	private function stored_as_destination( AffiliateUrl $url, Settings $settings ): bool {
		if ( AmazonUrl::is_short( $url ) ) {
			return true;
		}
		return $url->is_geni_us() && ! $settings->bool( 'keep_genius_formatting' );
	}

	/**
	 * The published product an Amazon link already is, after the stored-link lookup found nothing
	 * (get_lasso_post_id_by_url, then is_lasso_url_exist; F2 requirements 7):
	 * - a link with an ASIN: the Amazon product with that ASIN on that store;
	 * - a search page: the product whose search has the same keywords on the same store;
	 * - any page without an ASIN: the product stored with the link as it would be stored now (the tag rule applied).
	 *
	 * @param AmazonUrl    $amazon The link as an Amazon link.
	 * @param AffiliateUrl $url    The link as pasted (after the repair).
	 * @param AffiliateUrl $stored The link as it would be stored.
	 */
	private function existing_amazon( AmazonUrl $amazon, AffiliateUrl $url, AffiliateUrl $stored ): ?Product {
		if ( '' !== $amazon->asin() ) {
			return $this->products->find_amazon( $amazon->asin(), $amazon->country() );
		}

		$existing = '' !== $amazon->search_key() ? $this->products->find_by_search_key( $amazon->search_key() ) : null;
		if ( null === $existing && $stored->key() !== $url->key() ) {
			$existing = $this->products->find_by_url_key( $stored->key() );
		}
		return $existing;
	}

	/**
	 * A product the link already is. Lasso renames a match its cloud had named after an Amazon error page ("Sorry!…",
	 * "…went wrong!") to get_search_page_title() of the new link. Lasso's operator precedence also renames a non-Amazon
	 * match whose name holds " wrong!"; here the rule needs an Amazon link, as Lasso's own comment says.
	 *
	 * @param Product      $existing The product found.
	 * @param AffiliateUrl $url      The link that found it.
	 * @throws RuntimeException When the new name cannot be stored.
	 */
	private function found( Product $existing, AffiliateUrl $url ): AddedLink {
		$amazon = AmazonUrl::from( $url );
		if ( null !== $amazon && ( str_contains( $existing->name, 'Sorry!' ) || str_contains( $existing->name, ' wrong!' ) ) ) {
			$existing = $this->products->rename( $existing, $amazon->search_page_title() );
		}
		return new AddedLink( $existing, true );
	}

	/**
	 * A new Amazon product (save_lasso_url()'s Amazon fields): typed Amazon; with an ASIN, the ASIN and its store; a
	 * search page, its search key.
	 *
	 * @param string       $name     Its name.
	 * @param AmazonUrl    $amazon   Its link, as an Amazon link.
	 * @param AffiliateUrl $stored   The link to store.
	 * @param Settings     $settings The site's settings, read once for this add.
	 */
	private function amazon_draft( string $name, AmazonUrl $amazon, AffiliateUrl $stored, Settings $settings ): Product {
		$asin = $amazon->asin();

		return Product::draft( $name, $stored, $settings )->with(
			array(
				'type'           => ProductType::Amazon,
				'asin'           => $asin,
				'amazon_country' => '' === $asin ? '' : $amazon->country(),
				'search_key'     => $amazon->search_key(),
			)
		);
	}
}
