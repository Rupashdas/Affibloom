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

/**
 * Product use cases.
 */
final class ProductService {

	/**
	 * Builds the service.
	 *
	 * @param ProductRepository $products Product storage.
	 */
	public function __construct( private ProductRepository $products ) {}

	/**
	 * "Add A New Link" with one link. A published product with the same link is returned instead of a new one, as
	 * Lasso's is_lasso_url_exist() does with check_duplicate_link on (its default and the live value). A new product
	 * is named after the link's site, as Lasso names it before its cloud answers (Lasso_Helper::get_title_by_url).
	 *
	 * @param string $input What was pasted.
	 * @throws InvalidField When the input is empty or not a link.
	 */
	public function create_from_url( string $input ): AddedLink {
		$url = AffiliateUrl::from_string( $input );

		$existing = $this->products->find_by_url_key( $url->key() );
		if ( null !== $existing ) {
			return new AddedLink( $existing, true );
		}

		return new AddedLink( $this->products->save( Product::draft( $url->site_name(), $url ) ), false );
	}
}
