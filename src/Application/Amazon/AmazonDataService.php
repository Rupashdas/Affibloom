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
use Affibloom\Domain\Amazon\CatalogItem;
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
	 * The scheduled refresh of one stored row (C1Q4b; Lasso's lasso_cron_update_amazon item, R39): fetches the item and
	 * stores Amazon's data only, never a product's name, image, description or features (D7). A newer answer keeps the
	 * older price when it has none (R27). Not found, or an answer Lasso would not store (no image), only moves the
	 * row's time on, so it is not asked again for 24 hours.
	 *
	 * @param string $asin    The ASIN.
	 * @param string $country The store's country code.
	 * @param string $link    A product link for the ASIN, stored with a new row.
	 * @return bool False when Amazon could not be asked or failed (the row is left as it was), else true.
	 */
	public function refresh_row( string $asin, string $country, string $link ): bool {
		$credentials = $this->usable();
		$item_asin   = Asin::from( $asin );
		if ( null === $credentials || null === $item_asin ) {
			return null !== $credentials;
		}

		$now = gmdate( 'Y-m-d H:i:s' );
		try {
			$item = $this->item_for( $credentials, $item_asin, $country );
		} catch ( CatalogError $error ) {
			if ( CatalogError::NOT_FOUND !== $error->kind ) {
				return false;
			}
			$this->products->touch( $item_asin->value, $country, $now );
			return true;
		}

		$data = AmazonProduct::from_item( $item, $item_asin, $country, $link, $now );
		if ( $data->complete() ) {
			$this->products->save( $data );
		} else {
			$this->products->touch( $item_asin->value, $country, $now );
		}

		return true;
	}

	/**
	 * Items fetched ahead (C1Q6b), by "ASIN|country": the item, or null when Amazon did not give it. Used once.
	 *
	 * @var array<string, CatalogItem|null>
	 */
	private array $prefetched = array();

	/**
	 * Asks Amazon for up to ten items per store in one request each (C1Q6b) and keeps the answers for sync() and
	 * refresh_row(), so a job of many products spends a tenth of the requests. Items already kept are not asked again.
	 *
	 * @param list<array{asin: string, country: string}> $wanted The ASINs and stores, in order.
	 * @throws CatalogError When a request fails as a whole (Throttled among them).
	 */
	public function prefetch( array $wanted ): void {
		$credentials = $this->usable();
		if ( null === $credentials ) {
			return;
		}

		$by_country = array();
		foreach ( $wanted as $row ) {
			$asin = Asin::from( $row['asin'] );
			if ( null !== $asin && ! $this->is_prefetched( $asin->value, $row['country'] ) ) {
				$by_country[ $row['country'] ][ $asin->value ] = $asin;
			}
		}
		foreach ( $by_country as $country => $asins ) {
			foreach ( array_chunk( array_values( $asins ), 10 ) as $chunk ) {
				$found = $this->catalog->items( $credentials, $chunk, (string) $country );
				foreach ( $chunk as $asin ) {
					$this->prefetched[ $asin->value . '|' . $country ] = $found[ $asin->value ] ?? null;
				}
			}
		}
	}

	/**
	 * Whether an item's answer is kept from prefetch().
	 *
	 * @param string $asin    The ASIN.
	 * @param string $country The store's country code.
	 */
	public function is_prefetched( string $asin, string $country ): bool {
		return array_key_exists( strtoupper( $asin ) . '|' . $country, $this->prefetched );
	}

	/**
	 * One item: the answer kept from prefetch() (used once), else a request.
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @param Asin        $asin        The item.
	 * @param string      $country     The store's country code.
	 * @throws CatalogError NotFound for an item Amazon did not give, or as the request throws.
	 */
	private function item_for( Credentials $credentials, Asin $asin, string $country ): CatalogItem {
		$key = $asin->value . '|' . $country;
		if ( ! array_key_exists( $key, $this->prefetched ) ) {
			return $this->catalog->item( $credentials, $asin, $country );
		}
		$item = $this->prefetched[ $key ];
		unset( $this->prefetched[ $key ] );
		if ( null === $item ) {
			throw new CatalogError( CatalogError::NOT_FOUND, 'Product not found.' );
		}

		return $item;
	}

	/**
	 * Whether Amazon can be asked: the credentials are configured (R1, D4). The product page's Refresh and the bulk sync
	 * banner's button are enabled by it.
	 */
	public function can_ask_amazon(): bool {
		return null !== $this->usable();
	}

	/**
	 * The credentials Amazon may be asked with: all four set (R1, D4) and not refused by Amazon (C1Q0); else null. The
	 * Amazon search (C1M) asks with them too.
	 */
	public function usable(): ?Credentials {
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
			$item = $this->item_for( $credentials, $asin, $country );
		} catch ( CatalogError $error ) {
			if ( CatalogError::THROTTLED === $error->kind ) {
				return SyncOutcome::throttled( $error->retry_after );
			}
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
		// Features as the image: only for a product that has none (C1Q4).
		if ( array() === $product->features && array() !== $data->features ) {
			$product = $this->library->set_features( $product, $data->features );
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
		// Amazon's bullets replace the product's Features when Amazon gives some (C1Q4); the description is not touched.
		if ( array() !== $item->features ) {
			$product = $this->library->set_features( $product, $item->features );
		}
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
		if ( array() !== $data->features ) {
			$changes['features'] = $data->features;
		}
		if ( '' !== $data->name ) {
			$changes['name'] = $data->name;
		}
		if ( AmazonProduct::is_image_link( $data->image ) ) {
			$changes['image_url'] = $data->image;
		}

		return $draft->with( $changes );
	}

	/**
	 * Stores an item Amazon gave elsewhere (a search result, C1M; Lasso's persist_amazon_product_from_selection, R71) when
	 * the store has no data for it yet and the item is complete, so the next add takes it and asks Amazon nothing (R32).
	 *
	 * @param CatalogItem $item    The item.
	 * @param string      $country The store's country code.
	 */
	public function keep( CatalogItem $item, string $country ): void {
		$asin = Asin::from( $item->asin );
		if ( null === $asin || null !== $this->products->find( $asin->value, $country ) ) {
			return;
		}
		$data = AmazonProduct::from_item( $item, $asin, $country, 'https://' . Marketplace::host( $country ) . '/dp/' . $asin->value, gmdate( 'Y-m-d H:i:s' ) );
		if ( $data->complete() ) {
			$this->products->save( $data );
		}
	}

	/**
	 * The name stored for a product's ASIN and store, or '' (R34; no request to Amazon).
	 *
	 * @param Product $product An Amazon product.
	 */
	public function stored_name( Product $product ): string {
		return $this->stored( $product )->name ?? '';
	}

	/**
	 * The Amazon data stored for a product's ASIN and store, or null (C1Q5: the product page's Amazon Data card; no
	 * request to Amazon).
	 *
	 * @param Product $product An Amazon product.
	 */
	public function stored( Product $product ): ?AmazonProduct {
		$asin = Asin::from( $product->asin );

		return null === $asin ? null : $this->products->find( $asin->value, self::country( $product ) );
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
