<?php
/**
 * The Amazon short-link map's table.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Monetize\ShortLinkRepository;

/**
 * `{prefix}affibloom_amazon_short_links` (F35 D14; Task AM2): Lasso's `lasso_amazon_shortened_url` with the tries the
 * three-attempt rule counts.
 */
final class TableAmazonShortLinkRepository implements ShortLinkRepository {

	/** The table, without the prefix. */
	public const TABLE = 'affibloom_amazon_short_links';

	/** Its definition, as dbDelta reads it (Infrastructure\Schema\Table). */
	public const COLUMNS = 'id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
short_url varchar(255) NOT NULL,
final_url text NOT NULL,
product_id bigint(20) unsigned NOT NULL DEFAULT 0,
tries tinyint(3) unsigned NOT NULL DEFAULT 0,
resolved_at datetime DEFAULT NULL,
PRIMARY KEY  (id),
UNIQUE KEY short_url (short_url),
KEY product_id (product_id)';

	/**
	 * A short link's row, or null.
	 *
	 * @param string $short_url The short link.
	 * @return array{final: string, product: int, tries: int}|null
	 */
	public function find( string $short_url ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, by its unique key.
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT final_url, product_id, tries FROM %i WHERE short_url = %s', $wpdb->prefix . self::TABLE, $short_url ), ARRAY_A );

		return is_array( $row ) ? array(
			'final'   => (string) $row['final_url'],
			'product' => (int) $row['product_id'],
			'tries'   => (int) $row['tries'],
		) : null;
	}

	/**
	 * Writes a short link's row.
	 *
	 * @param string $short_url The short link.
	 * @param string $final_url Where it leads; '' while unresolved.
	 * @param int    $product   The product made for it; 0 for none.
	 * @param int    $tries     Failed attempts so far.
	 */
	public function save( string $short_url, string $final_url, int $product, int $tries ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, one row.
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (short_url, final_url, product_id, tries, resolved_at) VALUES (%s, %s, %d, %d, %s)
				ON DUPLICATE KEY UPDATE final_url = VALUES(final_url), product_id = VALUES(product_id), tries = VALUES(tries), resolved_at = VALUES(resolved_at)',
				$wpdb->prefix . self::TABLE,
				$short_url,
				$final_url,
				max( 0, $product ),
				max( 0, min( 255, $tries ) ),
				gmdate( 'Y-m-d H:i:s' )
			)
		);
	}
}
