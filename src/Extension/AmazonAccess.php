<?php
/**
 * What the extension API may do with the Amazon product data.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Extension;

use Affibloom\Domain\Amazon\AmazonProduct;
use Affibloom\Domain\Amazon\AmazonProductRepository;
use InvalidArgumentException;

/**
 * What the extension API may do with Affibloom's Amazon product data (Task 26L): read a cached product, and store one
 * another plugin had, marked `imported`, so the box shows it until Affibloom's own refresh replaces it.
 */
final class AmazonAccess {

	/** The fields a cached product has, in the store's names. */
	public const FIELDS = array( 'name', 'price', 'currency', 'base_url', 'monetized_url', 'image', 'out_of_stock', 'savings_amount', 'savings_percent', 'savings_basis', 'features', 'is_prime', 'last_updated', 'source' );

	/**
	 * Builds the access.
	 *
	 * @param AmazonProductRepository $amazon The Amazon product store.
	 */
	public function __construct( private AmazonProductRepository $amazon ) {}

	/**
	 * A cached product, or null when the store has none.
	 *
	 * @param string $asin    The ASIN.
	 * @param string $country The store, such as "us".
	 * @return array<string, mixed>|null
	 */
	public function product( string $asin, string $country ): ?array {
		$product = $this->amazon->find( $asin, $country );
		if ( null === $product ) {
			return null;
		}

		return array(
			'name'            => $product->name,
			'price'           => $product->price,
			'currency'        => $product->currency,
			'base_url'        => $product->base_url,
			'monetized_url'   => $product->monetized_url,
			'image'           => $product->image,
			'out_of_stock'    => $product->out_of_stock,
			'savings_amount'  => $product->savings_amount,
			'savings_percent' => $product->savings_percent,
			'savings_basis'   => $product->savings_basis,
			'features'        => $product->features,
			'is_prime'        => $product->is_prime,
			'last_updated'    => $product->last_updated,
			'source'          => $product->source,
		);
	}

	/**
	 * Stores a cached product another plugin had, marked `imported`.
	 *
	 * @param string               $asin    The ASIN (ten letters and digits).
	 * @param string               $country The store, such as "us".
	 * @param array<string, mixed> $data    Any of FIELDS but `source`; `last_updated` as "Y-m-d H:i:s" UTC.
	 * @throws InvalidArgumentException When the ASIN or the store is not one.
	 */
	public function import( string $asin, string $country, array $data ): void {
		if ( 1 !== preg_match( '/^[A-Z0-9]{10}$/', $asin ) || 1 !== preg_match( '/^[a-z]{2}$/', $country ) ) {
			throw new InvalidArgumentException( esc_html( "Not an ASIN and store: $asin, $country." ) );
		}
		$text     = static fn ( string $key ): string => is_scalar( $data[ $key ] ?? null ) ? (string) $data[ $key ] : '';
		$features = is_array( $data['features'] ?? null ) ? array_values( array_filter( $data['features'], 'is_string' ) ) : array();

		$this->amazon->save(
			new AmazonProduct(
				$asin,
				$country,
				$text( 'name' ),
				$text( 'price' ),
				$text( 'currency' ),
				$text( 'base_url' ),
				$text( 'monetized_url' ),
				$text( 'image' ),
				! empty( $data['out_of_stock'] ),
				$text( 'savings_amount' ),
				(int) $text( 'savings_percent' ),
				$text( 'savings_basis' ),
				$features,
				'imported',
				$text( 'last_updated' ),
				! empty( $data['is_prime'] )
			)
		);
	}
}
