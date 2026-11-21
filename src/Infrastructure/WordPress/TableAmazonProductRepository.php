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

	/**
	 * The rows read in this request, by "ASIN|country", a missing one as null (Task 37B: a page's boxes, their schema
	 * and the preview read each row once). Static, as several instances serve one request; save() and touch() forget
	 * the row they write.
	 *
	 * @var array<string, AmazonProduct|null>
	 */
	private static array $read = array();

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
		$key = $asin . '|' . $country;
		if ( ! array_key_exists( $key, self::$read ) ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table; kept for the request in self::$read.
			$row                = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE asin = %s AND country = %s', $wpdb->prefix . self::TABLE, $asin, $country ), ARRAY_A );
			self::$read[ $key ] = is_array( $row ) ? self::product( $row ) : null;
		}

		return self::$read[ $key ];
	}

	/**
	 * Reads these rows with one query and keeps them for the request, a missing one as null (Task 37B).
	 *
	 * @param list<array{0: string, 1: string}> $keys Each an ASIN and its store's country code.
	 */
	public function prime( array $keys ): void {
		$wanted = array();
		foreach ( $keys as $pair ) {
			if ( ! array_key_exists( $pair[0] . '|' . $pair[1], self::$read ) ) {
				$wanted[ $pair[0] . '|' . $pair[1] ] = $pair;
			}
		}
		if ( array() === $wanted ) {
			return;
		}
		global $wpdb;

		$values = array( $wpdb->prefix . self::TABLE );
		foreach ( $wanted as $key => $pair ) {
			array_push( $values, $pair[0], $pair[1] );
			self::$read[ $key ] = null;
		}
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Affibloom's own table; the list holds only placeholders, one pair per key.
		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM %i WHERE (asin, country) IN (' . implode( ', ', array_fill( 0, count( $wanted ), '(%s, %s)' ) ) . ')', ...$values ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		foreach ( (array) $rows as $row ) {
			self::$read[ $row['asin'] . '|' . $row['country'] ] = self::product( $row );
		}
	}

	/**
	 * A stored row as the domain's value.
	 *
	 * @param array<string, mixed> $row The table's columns.
	 */
	private static function product( array $row ): AmazonProduct {
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
	 * Moves a row's last-updated time on and changes nothing else.
	 *
	 * @param string $asin    The ASIN.
	 * @param string $country The store's country code.
	 * @param string $now     The time, "Y-m-d H:i:s" in UTC.
	 */
	public function touch( string $asin, string $country, string $now ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table; nothing caches it.
		$wpdb->update(
			$wpdb->prefix . self::TABLE,
			array( 'last_updated' => $now ),
			array(
				'asin'    => $asin,
				'country' => $country,
			),
			array( '%s' ),
			array( '%s', '%s' )
		);
		unset( self::$read[ $asin . '|' . $country ] );
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
		unset( self::$read[ $product->asin . '|' . $product->country ] );
	}
}
