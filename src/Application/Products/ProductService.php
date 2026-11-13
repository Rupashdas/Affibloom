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
	 * - with keep_genius_formatting off, a geni.us link is stored as its destination, which is then checked for a
	 *   duplicate too;
	 * - a new product takes the site's link defaults as they are now (Task 11E);
	 * - an Amazon link with an ASIN is the published Amazon product with that ASIN on that store, if there is one
	 *   (get_lasso_post_id_by_url's product-ID lookup, after the stored-link one; Task 12B);
	 * - an Amazon product link is stored as Lasso stores it, typed Amazon with its ASIN and country (Task 12A).
	 *
	 * @param string $input What was pasted.
	 * @throws InvalidField      When the input is empty or not a link.
	 * @throws RuntimeException When the product cannot be stored.
	 */
	public function create_from_url( string $input ): AddedLink {
		$url = AffiliateUrl::from_string( $input );

		$existing = $this->products->find_by_url_key( $url->key() );
		if ( null !== $existing ) {
			return new AddedLink( $existing, true );
		}

		$resolved = $this->links->resolve( $url );
		$settings = $this->settings->get();

		if ( $url->is_geni_us() && $resolved->url !== $url->value() && ! $settings->bool( 'keep_genius_formatting' ) ) {
			$url      = AffiliateUrl::from_string( $resolved->url );
			$existing = $this->products->find_by_url_key( $url->key() );
			if ( null !== $existing ) {
				return new AddedLink( $existing, true );
			}
		}

		$amazon = AmazonUrl::from( $url );
		if ( null === $amazon || '' === $amazon->asin() ) {
			return new AddedLink( $this->products->save( Product::draft( $resolved->title, $url, $settings ) ), false );
		}

		$existing = $this->products->find_amazon( $amazon->asin(), $amazon->country() );
		if ( null !== $existing ) {
			return new AddedLink( $existing, true );
		}

		return new AddedLink( $this->products->save( $this->amazon_draft( $resolved->title, $amazon, $settings ) ), false );
	}

	/**
	 * A new Amazon product: its link monetized (Lasso_Amazon_Api::get_amazon_product_url()) and typed with its ASIN
	 * and store (save_lasso_url()'s Amazon fields).
	 *
	 * @param string    $name     Its name.
	 * @param AmazonUrl $amazon   Its link, which has an ASIN.
	 * @param Settings  $settings The site's settings, read once for this add.
	 */
	private function amazon_draft( string $name, AmazonUrl $amazon, Settings $settings ): Product {
		$stored = $amazon->monetized( TrackingIds::from_settings( $settings ), $settings->bool( 'shorten_amazon_links' ) );

		return Product::draft( $name, $stored, $settings )->with(
			array(
				'type'           => ProductType::Amazon,
				'asin'           => $amazon->asin(),
				'amazon_country' => $amazon->country(),
			)
		);
	}
}
