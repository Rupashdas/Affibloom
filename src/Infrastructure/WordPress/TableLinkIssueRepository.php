<?php
/**
 * The products' link-health rows in {prefix}affibloom_link_issues.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\LinkHealth\CheckTarget;
use Affibloom\Domain\LinkHealth\LinkIssue;
use Affibloom\Domain\LinkHealth\LinkIssueRepository;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

/**
 * `{prefix}affibloom_link_issues` (F11 D1; analysis, "Data"): Lasso's lasso_url_issues in Affibloom's names, one row
 * per product, without is_ignored and total_404_request (D3), with D4's failure columns. Times are UTC. The products
 * the check covers are read from wp_posts and the product meta: published Affibloom products that are not Amazon
 * products (type `amazon`, or a destination on one of Amazon's hosts, so no Amazon URL is ever requested, AC2); while
 * the product API is configured, also Amazon products (type `amazon`) with an ASIN (D8); with "locations only", only
 * those with a row in the link index (R5).
 */
final class TableLinkIssueRepository implements LinkIssueRepository {

	/** The table's name without the site's prefix. */
	public const TABLE = 'affibloom_link_issues';

	/** Its definition, as dbDelta reads it (Infrastructure\Schema\Table). */
	public const COLUMNS = "product_id bigint(20) unsigned NOT NULL,
issue_type char(3) NOT NULL DEFAULT '',
checked_url varchar(2000) NOT NULL DEFAULT '',
detection_date datetime DEFAULT NULL,
resolved tinyint(1) NOT NULL DEFAULT 0,
resolved_at datetime DEFAULT NULL,
last_checked_at datetime NOT NULL,
failures tinyint(3) unsigned NOT NULL DEFAULT 0,
first_failed_at datetime DEFAULT NULL,
pending_type char(3) DEFAULT NULL,
PRIMARY KEY  (product_id),
KEY state (resolved,issue_type,detection_date),
KEY last_checked_at (last_checked_at)";

	/** Amazon's hosts in a destination (AffiliateUrl's list), as a MySQL regular expression. */
	private const AMAZON_DESTINATION = '^https?://([^/?#]*[.@])?(amazon[.][a-z.]+|amazon-adsystem[.]com|amzn[.](com|to)|amzlink[.]to)([:/?#]|$)';

	private const TIME = 'Y-m-d H:i:s';

	/**
	 * A product's row, or null.
	 *
	 * @param int $product_id The product.
	 */
	public function get( int $product_id ): ?LinkIssue {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table; read once per check.
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE product_id = %d', self::table(), $product_id ), ARRAY_A );

		return is_array( $row ) ? self::issue( $row ) : null;
	}

	/**
	 * Stores a row, replacing the product's.
	 *
	 * @param LinkIssue $issue The row.
	 * @throws RuntimeException When the database refuses the write.
	 */
	public function save( LinkIssue $issue ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, written once per check.
		$written = $wpdb->replace(
			self::table(),
			array(
				'product_id'      => $issue->product_id,
				'issue_type'      => $issue->issue_type,
				'checked_url'     => $issue->checked_url,
				'detection_date'  => self::time( $issue->detection_date ),
				'resolved'        => $issue->resolved ? 1 : 0,
				'resolved_at'     => self::time( $issue->resolved_at ),
				'last_checked_at' => self::time( $issue->last_checked_at ),
				'failures'        => $issue->failures,
				'first_failed_at' => self::time( $issue->first_failed_at ),
				'pending_type'    => $issue->pending_type,
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s' )
		);
		if ( false === $written ) {
			throw new RuntimeException( "The link check of product {$issue->product_id} could not be stored: {$wpdb->last_error}" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- never printed as HTML.
		}
	}

	/**
	 * Every covered product, in Lasso's order (R6).
	 *
	 * @param bool $locations_only Only products used in content.
	 * @param bool $amazon         Amazon products with an ASIN too.
	 * @return list<int>
	 */
	public function due( bool $locations_only, bool $amazon ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- covered() is prepared; once a day.
		$ids = $wpdb->get_col( 'SELECT p.ID ' . self::covered( $locations_only, $amazon ) . ' ORDER BY i.product_id IS NULL, i.resolved ASC, i.last_checked_at ASC, p.ID ASC' );

		return self::ids( $ids );
	}

	/**
	 * Covered products waiting for their second check, last checked at or before the given time.
	 *
	 * @param DateTimeImmutable $checked_before The latest last check.
	 * @param bool              $locations_only Only products used in content.
	 * @param int               $limit          At most this many.
	 * @return list<int>
	 */
	public function confirmations( DateTimeImmutable $checked_before, bool $locations_only, int $limit ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- covered() is prepared.
		$ids = $wpdb->get_col( 'SELECT p.ID ' . self::covered( $locations_only, false ) . $wpdb->prepare( ' AND i.failures > 0 AND i.last_checked_at <= %s ORDER BY i.last_checked_at ASC LIMIT %d', self::time( $checked_before ), $limit ) );

		return self::ids( $ids );
	}

	/**
	 * The earliest last check among covered products waiting for their second check.
	 *
	 * @param bool $locations_only Only products used in content.
	 */
	public function next_confirmation( bool $locations_only ): ?DateTimeImmutable {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- covered() is prepared.
		$time = $wpdb->get_var( 'SELECT MIN(i.last_checked_at) ' . self::covered( $locations_only, false ) . ' AND i.failures > 0' );

		return self::date( $time );
	}

	/**
	 * What the check of a covered product asks about; null when it is not covered.
	 *
	 * @param int  $product_id The product.
	 * @param bool $amazon     Amazon products with an ASIN are covered.
	 */
	public function destination( int $product_id, bool $amazon ): ?CheckTarget {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- covered() is prepared; read once per check.
		$found = $wpdb->get_row( "SELECT p.ID, u.meta_value AS url, IF(t.meta_value = 'amazon', a.meta_value, '') AS asin, c.meta_value AS country " . self::covered( false, $amazon ) . $wpdb->prepare( ' AND p.ID = %d LIMIT 1', $product_id ), ARRAY_A );
		if ( ! is_array( $found ) ) {
			return null;
		}
		$asin = (string) $found['asin'];

		return new CheckTarget( (string) $found['url'], $asin, '' === $asin ? '' : (string) $found['country'] );
	}

	/**
	 * The Amazon products of an ASIN in a store, whatever their status (R17).
	 *
	 * @param string $asin    The ASIN.
	 * @param string $country The store's country code.
	 * @return list<int>
	 */
	public function with_asin( string $asin, string $country ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- the product meta, once per refreshed ASIN.
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.ID FROM %i p
				JOIN %i a ON a.post_id = p.ID AND a.meta_key = '_affibloom_asin' AND a.meta_value = %s
				JOIN %i c ON c.post_id = p.ID AND c.meta_key = '_affibloom_amazon_country' AND c.meta_value = %s
				WHERE p.post_type = %s",
				$wpdb->posts,
				$wpdb->postmeta,
				$asin,
				$wpdb->postmeta,
				$country,
				ProductPostType::NAME
			)
		);

		return self::ids( $ids );
	}

	/**
	 * Deletes the rows of products that no longer exist (R4).
	 *
	 * @return int How many were deleted.
	 */
	public function delete_orphans(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, once a day.
		$deleted = $wpdb->query( $wpdb->prepare( 'DELETE i FROM %i i LEFT JOIN %i p ON p.ID = i.product_id AND p.post_type = %s WHERE p.ID IS NULL', self::table(), $wpdb->posts, ProductPostType::NAME ) );

		return (int) $deleted;
	}

	/**
	 * The FROM and WHERE of the covered products, with each one's row (alias i), type (t), destination (u), ASIN (a) and
	 * store (c).
	 *
	 * @param bool $locations_only Only products with a row in the link index.
	 * @param bool $amazon         Amazon products with an ASIN too.
	 */
	private static function covered( bool $locations_only, bool $amazon ): string {
		global $wpdb;

		$sql = $wpdb->prepare(
			"FROM %i p
			LEFT JOIN %i t ON t.post_id = p.ID AND t.meta_key = '_affibloom_type'
			LEFT JOIN %i u ON u.post_id = p.ID AND u.meta_key = '_affibloom_url'
			LEFT JOIN %i a ON a.post_id = p.ID AND a.meta_key = '_affibloom_asin'
			LEFT JOIN %i c ON c.post_id = p.ID AND c.meta_key = '_affibloom_amazon_country'
			LEFT JOIN %i i ON i.product_id = p.ID
			WHERE p.post_type = %s AND p.post_status = 'publish'
			AND ( ( COALESCE(t.meta_value, '') <> 'amazon' AND COALESCE(u.meta_value, '') NOT REGEXP %s )
				OR ( %d = 1 AND t.meta_value = 'amazon' AND COALESCE(a.meta_value, '') <> '' ) )",
			$wpdb->posts,
			$wpdb->postmeta,
			$wpdb->postmeta,
			$wpdb->postmeta,
			$wpdb->postmeta,
			self::table(),
			ProductPostType::NAME,
			self::AMAZON_DESTINATION,
			$amazon ? 1 : 0
		);
		if ( $locations_only ) {
			$sql .= $wpdb->prepare( ' AND EXISTS (SELECT 1 FROM %i l WHERE l.product_id = p.ID)', $wpdb->prefix . TableLinkLocationRepository::TABLE );
		}

		return $sql;
	}

	/**
	 * The table's full name.
	 */
	private static function table(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Product IDs from a column, each once, in order (a product with duplicate meta rows would repeat).
	 *
	 * @param array<int, mixed> $ids The column.
	 * @return list<int>
	 */
	private static function ids( array $ids ): array {
		return array_values( array_unique( array_map( 'intval', $ids ) ) );
	}

	/**
	 * A row as a LinkIssue.
	 *
	 * @param array<string, mixed> $row The row.
	 */
	private static function issue( array $row ): LinkIssue {
		return new LinkIssue(
			(int) $row['product_id'],
			(string) $row['issue_type'],
			(string) $row['checked_url'],
			self::date( $row['detection_date'] ),
			1 === (int) $row['resolved'],
			self::date( $row['resolved_at'] ),
			self::date( $row['last_checked_at'] ) ?? new DateTimeImmutable( '@0' ),
			(int) $row['failures'],
			self::date( $row['first_failed_at'] ),
			null === $row['pending_type'] ? null : (string) $row['pending_type'],
		);
	}

	/**
	 * A stored UTC time, or null.
	 *
	 * @param mixed $value The column's value.
	 */
	private static function date( mixed $value ): ?DateTimeImmutable {
		if ( ! is_string( $value ) || '' === $value ) {
			return null;
		}
		$date = DateTimeImmutable::createFromFormat( self::TIME, $value, new DateTimeZone( 'UTC' ) );

		return false === $date ? null : $date;
	}

	/**
	 * A time as stored (UTC), or null.
	 *
	 * @param DateTimeImmutable|null $time The time.
	 */
	private static function time( ?DateTimeImmutable $time ): ?string {
		return $time?->setTimezone( new DateTimeZone( 'UTC' ) )->format( self::TIME );
	}
}
