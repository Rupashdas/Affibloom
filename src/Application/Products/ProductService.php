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
	 * "Add A New Link" with one link: a new product named after the link's site, as Lasso names it before its cloud
	 * answers (Lasso_Helper::get_title_by_url).
	 *
	 * @param string $input What was pasted.
	 * @throws InvalidField When the input is empty or not a link.
	 */
	public function create_from_url( string $input ): Product {
		$url = AffiliateUrl::from_string( $input );

		return $this->products->save( Product::draft( $url->site_name(), $url ) );
	}
}
