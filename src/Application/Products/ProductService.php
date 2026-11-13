<?php
/**
 * Product use cases.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;
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
	 * @param SettingsRepository $settings Site settings (keep_genius_formatting).
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
	 *   duplicate too.
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

		if ( $url->is_geni_us() && $resolved->url !== $url->value() && ! $this->settings->get()->bool( 'keep_genius_formatting' ) ) {
			$url      = AffiliateUrl::from_string( $resolved->url );
			$existing = $this->products->find_by_url_key( $url->key() );
			if ( null !== $existing ) {
				return new AddedLink( $existing, true );
			}
		}

		return new AddedLink( $this->products->save( Product::draft( $resolved->title, $url ) ), false );
	}
}
