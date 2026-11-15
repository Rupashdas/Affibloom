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
use Affibloom\Domain\Amazon\Credentials;
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
	 * Whether Amazon can be asked: the credentials are configured (R1, D4). The product page's Refresh and the bulk sync
	 * banner's button are enabled by it.
	 */
	public function can_ask_amazon(): bool {
		return null !== $this->usable();
	}

	/**
	 * The credentials Amazon may be asked with: all four set (R1, D4) and not refused by Amazon (C1Q0); else null.
	 */
	private function usable(): ?Credentials {
		$credentials = $this->credentials->get();

		return $credentials->configured() && null === $this->credentials->refusal() ? $credentials : null;
	}

	/**
	 * Bulk sync for one Amazon product with an ASIN (Lasso's sync_amazon_metadata_for_post, R44): the stored row for
	 * its ASIN and store when it has a name ("Amazon data already present."), else Amazon's answer, stored and read
	 * back. Either way the product takes the image link when it has no image, and Amazon's title when its name is a
	 * placeholder (Lasso: when it has none; an Affibloom product always has one, decision 11).
	 *
	 * @param Product $product A stored Amazon product.
	 */
	public function sync( Product $product ): SyncOutcome {
		$asin = Asin::from( $product->asin );
		if ( null === $asin ) {
			return SyncOutcome::failed( SyncOutcome::UNRESOLVED );
		}
		$country = self::country( $product );

		$stored = $this->products->find( $asin->value, $country );
		if ( null !== $stored && '' !== $stored->name ) {
			$this->apply( $product, $stored );
			return SyncOutcome::succeeded( SyncOutcome::PRESENT );
		}

		$credentials = $this->usable();
		if ( null === $credentials ) {
			return SyncOutcome::failed( SyncOutcome::FAILED );
		}
		try {
			$item = $this->catalog->item( $credentials, $asin, $country );
		} catch ( CatalogError $error ) {
			return SyncOutcome::failed( CatalogError::NOT_FOUND === $error->kind ? SyncOutcome::NOT_FOUND : SyncOutcome::FAILED );
		}

		$data = AmazonProduct::from_item( $item, $asin, $country, $product->url->value(), gmdate( 'Y-m-d H:i:s' ) );
		// Lasso's "incomplete" is a title or image missing, which is also what its table refuses (complete()).
		if ( ! $data->complete() ) {
			return SyncOutcome::failed( SyncOutcome::INCOMPLETE );
		}
		$this->products->save( $data );
		$stored = $this->products->find( $asin->value, $country );
		if ( null === $stored || '' === $stored->name ) {
			return SyncOutcome::failed( SyncOutcome::UNVERIFIED );
		}

		$this->apply( $product, $stored );
		return SyncOutcome::succeeded( SyncOutcome::SYNCED );
	}

	/**
	 * Gives a product a stored row's image link when it has no image (Lasso's should_apply_amazon_sync_custom_thumbnail:
	 * no featured image and no custom one), and the row's name when its own is a placeholder.
	 *
	 * @param Product       $product A stored Amazon product.
	 * @param AmazonProduct $data    Its stored row.
	 */
	private function apply( Product $product, AmazonProduct $data ): void {
		if ( 0 === $product->image_id && '' === $product->image_url && AmazonProduct::is_image_link( $data->image ) ) {
			$product = $this->library->set_image( $product, $data->image );
		}
		if ( '' !== $data->name && PlaceholderName::is( $product->name ) ) {
			$this->library->rename( $product, $data->name );
		}
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
		$credentials = $this->usable();
		if ( null === $credentials ) {
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
		$credentials = $this->usable();
		if ( null === $credentials ) {
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
