<?php
/**
 * Amazon's data on Affibloom's products: what a new Amazon product takes from Amazon.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Amazon;

use Affibloom\Domain\Amazon\AmazonProduct;
use Affibloom\Domain\Amazon\AmazonProductRepository;
use Affibloom\Domain\Amazon\Asin;
use Affibloom\Domain\Amazon\CredentialsRepository;
use Affibloom\Domain\Amazon\Marketplace;
use Affibloom\Domain\Product\Product;

/**
 * Amazon's data on Affibloom's products (C1 D7). On add (C1C; Lasso's lasso_add_a_new_link, R32): a new Amazon product
 * with an ASIN takes the stored data for that ASIN and store when there is some, else asks the Creators API (while the
 * credentials are configured, R1, D4) and stores the answer; it takes Amazon's title as its name and the image as a
 * link. Without credentials, or when Amazon fails or has no such product, the product keeps the name its link gave it
 * (decision 11, F2 section 6) and no image, and nothing stops the add (AC2).
 */
final class AmazonDataService {

	/**
	 * Builds the service.
	 *
	 * @param CredentialsRepository   $credentials The Creators API credentials.
	 * @param AmazonCatalog           $catalog     The Creators API.
	 * @param AmazonProductRepository $products    Amazon's data per ASIN and store.
	 */
	public function __construct(
		private CredentialsRepository $credentials,
		private AmazonCatalog $catalog,
		private AmazonProductRepository $products
	) {}

	/**
	 * A new Amazon product with Amazon's name and image, when Amazon's data can be had; else the product as it was.
	 *
	 * @param Product $draft The product about to be stored.
	 */
	public function fill( Product $draft ): Product {
		$asin = Asin::from( $draft->asin );
		if ( null === $asin ) {
			return $draft;
		}

		$country = self::country( $draft );
		$data    = $this->products->find( $asin->value, $country );

		if ( null !== $data ) {
			$data = $data->linked( $draft->url->value() );
		} else {
			$data = $this->fetch( $asin, $country, $draft->url->value() );
			if ( null === $data ) {
				return $draft;
			}
		}
		if ( $data->complete() ) {
			$this->products->save( $data );
		}

		$changes = array();
		if ( '' !== $data->name ) {
			$changes['name'] = $data->name;
		}
		if ( AmazonProduct::is_image_link( $data->image ) ) {
			$changes['image_url'] = $data->image;
		}

		return $draft->with( $changes );
	}

	/**
	 * The name stored for a product's ASIN and store, or '' (R34; no request to Amazon).
	 *
	 * @param Product $product An Amazon product.
	 */
	public function stored_name( Product $product ): string {
		$asin = Asin::from( $product->asin );

		return null === $asin ? '' : $this->products->find( $asin->value, self::country( $product ) )->name ?? '';
	}

	/**
	 * The store a product's Amazon data is kept under: its own, else Lasso's default `us` (R28).
	 *
	 * @param Product $product An Amazon product.
	 */
	private static function country( Product $product ): string {
		return Marketplace::is_country( $product->amazon_country ) ? $product->amazon_country : Marketplace::DEFAULT_COUNTRY;
	}

	/**
	 * Amazon's data for an item, or null when the credentials are not configured or Amazon gives none.
	 *
	 * @param Asin   $asin    The item.
	 * @param string $country The store.
	 * @param string $link    The link the product stores.
	 */
	private function fetch( Asin $asin, string $country, string $link ): ?AmazonProduct {
		$credentials = $this->credentials->get();
		if ( ! $credentials->configured() ) {
			return null;
		}

		try {
			$item = $this->catalog->item( $credentials, $asin, $country );
		} catch ( CatalogError $error ) {
			return null;
		}

		return AmazonProduct::from_item( $item, $asin, $country, $link, gmdate( 'Y-m-d H:i:s' ) );
	}
}
