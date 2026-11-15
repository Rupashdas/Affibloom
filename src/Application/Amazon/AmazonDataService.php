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
use Affibloom\Domain\Product\PlaceholderName;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;

/**
 * Amazon's data on Affibloom's products (C1 D7). On add (C1C; Lasso's lasso_add_a_new_link, R32): a new Amazon product
 * with an ASIN takes the stored data for that ASIN and store when there is some, else asks the Creators API (while the
 * credentials are configured, R1, D4) and stores the answer; it takes Amazon's title as its name and the image as a
 * link. Without credentials, or when Amazon fails or has no such product, the product keeps the name its link gave it
 * (decision 11, F2 section 6) and no image, and nothing stops the add (AC2).
 *
 * Refresh (C1D; Lasso's upload_thumbnail for an Amazon product, R35): always asks Amazon, stores the answer, and gives
 * the product the image link (no media library image any more) and, only when its name is a placeholder, Amazon's
 * title, saved at once as Lasso does.
 */
final class AmazonDataService {

	/**
	 * Builds the service.
	 *
	 * @param CredentialsRepository   $credentials The Creators API credentials.
	 * @param AmazonCatalog           $catalog     The Creators API.
	 * @param AmazonProductRepository $products    Amazon's data per ASIN and store.
	 * @param ProductRepository       $library     The products, for Refresh.
	 */
	public function __construct(
		private CredentialsRepository $credentials,
		private AmazonCatalog $catalog,
		private AmazonProductRepository $products,
		private ProductRepository $library
	) {}

	/**
	 * Whether Refresh can ask Amazon: the credentials are configured (R1, D4). The product page enables its button by it.
	 */
	public function can_refresh(): bool {
		return $this->credentials->get()->configured();
	}

	/**
	 * Refresh (R35): Amazon's current data for a product, stored; the product takes the image link, and Amazon's title
	 * when its name is a placeholder, and is saved.
	 *
	 * @param int $id The product.
	 * @return array{product: Product, data: AmazonProduct, renamed: bool} The product, the stored data (price, stock),
	 *                                                              and whether a placeholder name was replaced.
	 * @throws RefreshFailed When there is no such product, it is not an Amazon product with an ASIN, the credentials are
	 *                       not configured, Amazon fails, or its answer has no image.
	 */
	public function refresh( int $id ): array {
		$product = $id > 0 ? $this->library->get( $id ) : null;
		if ( null === $product ) {
			throw new RefreshFailed( RefreshFailed::NOT_FOUND );
		}
		$asin = Asin::from( $product->asin );
		if ( ProductType::Amazon !== $product->type || null === $asin ) {
			throw new RefreshFailed( RefreshFailed::NOT_AMAZON );
		}
		$credentials = $this->credentials->get();
		if ( ! $credentials->configured() ) {
			throw new RefreshFailed( RefreshFailed::NOT_CONFIGURED );
		}

		$country = self::country( $product );
		try {
			$item = $this->catalog->item( $credentials, $asin, $country );
		} catch ( CatalogError $error ) {
			throw new RefreshFailed( RefreshFailed::FAILED, $error->kind ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- a code; the words are the Interface's.
		}

		$data = AmazonProduct::from_item( $item, $asin, $country, $product->url->value(), gmdate( 'Y-m-d H:i:s' ) );
		if ( $data->complete() ) {
			$this->products->save( $data );
			$data = $this->products->find( $asin->value, $country ) ?? $data;
		}
		if ( ! AmazonProduct::is_image_link( $item->image ) ) {
			throw new RefreshFailed( RefreshFailed::NO_IMAGE );
		}

		// Lasso writes the image as post meta only, and touches the post only to rename a placeholder, so a refresh does
		// not move the product to the top of the Dashboard.
		$product = $this->library->set_image( $product, $item->image );
		$renamed = '' !== $item->title && PlaceholderName::is( $product->name );
		if ( $renamed ) {
			$product = $this->library->rename( $product, $item->title );
		}

		return array(
			'product' => $product,
			'data'    => $data,
			'renamed' => $renamed,
		);
	}

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
