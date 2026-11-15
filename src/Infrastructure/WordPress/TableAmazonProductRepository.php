<?php
/**
 * Amazon's data per ASIN and store in {prefix}affibloom_amazon_products.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Amazon\AmazonProduct;
use Affibloom\Domain\Amazon\AmazonProductRepository;

/**
 * Amazon's data per ASIN and store in `{prefix}affibloom_amazon_products` (C1 D5): Lasso's lasso_amazon_products
 * columns and key (ASIN + country), without rating and reviews (never written, R26), `is_manual` becoming `source`.
 * Text columns hold Lasso's sizes or more: the name 2,000 characters, links and the image as text. The column names
 * are Affibloom's (asin, name, price, image for amazon_id, default_product_name, latest_price, default_image); Task 26
 * maps Lasso's. No index on last_updated until the refresh job (C1E) reads by it.
 */
final class TableAmazonProductRepository implements AmazonProductRepository {

	/** The table's name without the site's prefix. */
	public const TABLE = 'affibloom_amazon_products';

	/** Its definition, as dbDelta reads it (Infrastructure\Schema\Table). */
	public const COLUMNS = "asin varchar(20) NOT NULL,
country char(2) NOT NULL DEFAULT 'us',
name varchar(2000) NOT NULL DEFAULT '',
price varchar(32) NOT NULL DEFAULT '',
currency varchar(5) NOT NULL DEFAULT 'USD',
base_url text NOT NULL,
monetized_url text NOT NULL,
image text NOT NULL,
out_of_stock tinyint(1) NOT NULL DEFAULT 0,
is_prime tinyint(1) NOT NULL DEFAULT 0,
savings_amount varchar(32) NOT NULL DEFAULT '',
savings_percent int(11) NOT NULL DEFAULT 0,
savings_basis varchar(32) NOT NULL DEFAULT '',
features text NOT NULL,
source varchar(10) NOT NULL DEFAULT 'creators',
last_updated datetime NOT NULL,
price_amount decimal(12,2) DEFAULT NULL,
basis_amount decimal(12,2) DEFAULT NULL,
PRIMARY KEY  (asin,country)";

	/**
	 * The stored row for an ASIN on a store, or null.
	 *
	 * @param string $asin    The ASIN.
	 * @param string $country The store's country code.
	 */
	public function find( string $asin, string $country ): ?AmazonProduct {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, read once per add or refresh.
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE asin = %s AND country = %s', $wpdb->prefix . self::TABLE, $asin, $country ), ARRAY_A );
		if ( ! is_array( $row ) ) {
			return null;
		}

		$features = json_decode( (string) $row['features'], true );

		return new AmazonProduct(
			(string) $row['asin'],
			(string) $row['country'],
			(string) $row['name'],
			(string) $row['price'],
			(string) $row['currency'],
			(string) $row['base_url'],
			(string) $row['monetized_url'],
			(string) $row['image'],
			'1' === (string) $row['out_of_stock'],
			(string) $row['savings_amount'],
			(int) $row['savings_percent'],
			(string) $row['savings_basis'],
			is_array( $features ) ? array_values( array_filter( $features, 'is_string' ) ) : array(),
			(string) $row['source'],
			(string) $row['last_updated'],
			'1' === (string) $row['is_prime'],
			is_numeric( $row['price_amount'] ?? null ) ? (float) $row['price_amount'] : null,
			is_numeric( $row['basis_amount'] ?? null ) ? (float) $row['basis_amount'] : null
		);
	}

	/**
	 * Stores a row, replacing the one for the same ASIN and store, keeping its price and image when the new ones are
	 * empty (R27).
	 *
	 * @param AmazonProduct $product The row.
	 */
	public function save( AmazonProduct $product ): void {
		global $wpdb;

		$product = $product->over( $this->find( $product->asin, $product->country ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table; nothing caches it.
		$wpdb->replace(
			$wpdb->prefix . self::TABLE,
			array(
				'asin'            => $product->asin,
				'country'         => $product->country,
				'name'            => $product->name,
				'price'           => $product->price,
				'currency'        => $product->currency,
				'base_url'        => $product->base_url,
				'monetized_url'   => $product->monetized_url,
				'image'           => $product->image,
				'out_of_stock'    => $product->out_of_stock ? 1 : 0,
				'savings_amount'  => $product->savings_amount,
				'savings_percent' => $product->savings_percent,
				'savings_basis'   => $product->savings_basis,
				'features'        => (string) wp_json_encode( $product->features ),
				'source'          => $product->source,
				'last_updated'    => $product->last_updated,
				'is_prime'        => $product->is_prime ? 1 : 0,
				// NULL when Amazon gave no number ($wpdb writes a null value as NULL).
				'price_amount'    => $product->price_amount,
				'basis_amount'    => $product->basis_amount,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%f', '%f' )
		);
	}
}
