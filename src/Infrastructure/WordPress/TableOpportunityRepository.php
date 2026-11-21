<?php
/**
 * The opportunity queries over the link index.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\LinkIndex\ProductLocation;
use Affibloom\Domain\Opportunity\AmazonDomains;
use Affibloom\Domain\Opportunity\BaseDomain;
use Affibloom\Domain\Opportunity\DetectedLink;
use Affibloom\Domain\Opportunity\DomainCount;
use Affibloom\Domain\Opportunity\Opportunity;
use Affibloom\Domain\Opportunity\OpportunityPage;
use Affibloom\Domain\Opportunity\OpportunityQuery;
use Affibloom\Domain\Opportunity\OpportunityRepository;

/**
 * Lasso's opportunity queries (F22 R8, D4; `get_link_opportunities_query`) on `{prefix}affibloom_link_locations`: the
 * index row (l), its host post (h), the products whose `_affibloom_domain` is the row's domain (dm, p) and their Detect
 * Opportunities (`_affibloom_detect_opportunities`, absent = on; dop). Two queries a page: the total and the rows.
 */
final class TableOpportunityRepository implements OpportunityRepository {

	/** Each sortable column's SQL (R10; an allow-list, so no input reaches ORDER BY). */
	private const SORT = array(
		'url'        => 'l.url',
		'post_title' => 'h.post_title',
		'suggestion' => 'p.post_title',
	);

	/**
	 * One page of Link Opportunities.
	 *
	 * @param OpportunityQuery $query The search, sort, page and product.
	 */
	public function links( OpportunityQuery $query ): OpportunityPage {
		global $wpdb;

		// This site's own links are never an opportunity (D4).
		$excluded = array_merge( array( BaseDomain::of( home_url() ) ), AmazonDomains::EXCLUDED );
		$where    = "l.product_id = 0 AND l.assigned_product_id = 0 AND l.is_ignored = 0 AND l.domain IS NOT NULL AND l.domain <> ''
			AND l.domain NOT IN (" . implode( ',', array_fill( 0, count( $excluded ), '%s' ) ) . ") AND COALESCE(dop.meta_value, '1') <> '0'";
		$args     = $excluded;
		$words    = $query->words();
		if ( array() !== $words ) {
			// Lasso's search: the words in order, anything between them (R7, R9).
			$like   = self::like( $words );
			$where .= ' AND ( l.url LIKE %s OR l.domain LIKE %s )';
			$args[] = $like;
			$args[] = $like;
		}
		if ( $query->product > 0 ) {
			$where .= ' AND p.ID = %d';
			$args[] = $query->product;
		}
		$from  = $wpdb->prepare(
			"FROM %i AS l
			JOIN %i AS h ON h.ID = l.host_id
			JOIN %i AS dm ON dm.meta_key = '_affibloom_domain' AND dm.meta_value = l.domain
			JOIN %i AS p ON p.ID = dm.post_id AND p.post_type = %s AND p.post_status = 'publish'
			LEFT JOIN %i AS dop ON dop.post_id = p.ID AND dop.meta_key = '_affibloom_detect_opportunities'",
			$wpdb->prefix . TableLinkLocationRepository::TABLE,
			$wpdb->posts,
			$wpdb->postmeta,
			$wpdb->posts,
			ProductPostType::NAME,
			$wpdb->postmeta
		);
		$order = '' === $query->orderby ? 'l.id ASC, p.ID ASC' : self::SORT[ $query->orderby ] . ( 'desc' === $query->order ? ' DESC' : ' ASC' ) . ', l.id ASC, p.ID ASC';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Affibloom's own table, one screen; $from is prepared, $where holds only placeholders, $order is from the allow-list.
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) {$from} WHERE {$where}", ...$args ) );
		$shell = new OpportunityPage( array(), $total, $query->page );
		$rows  = 0 === $total ? array() : $wpdb->get_results(
			$wpdb->prepare(
				"SELECT l.id, l.url, l.domain, l.display_type, l.anchor_text, l.display_ref, l.host_id, l.host_url, h.post_title AS host_title, p.ID AS product_id, p.post_title AS product_name
				{$from} WHERE {$where} ORDER BY {$order} LIMIT %d OFFSET %d",
				...array_merge( $args, array( OpportunityPage::PER_PAGE, $shell->offset() ) )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		$items = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$items[] = new Opportunity(
				self::place( $row ),
				(string) $row['domain'],
				(int) $row['host_id'],
				(string) $row['host_title'],
				(string) $row['host_url'],
				(int) $row['product_id'],
				(string) $row['product_name']
			);
		}

		return new OpportunityPage( $items, $total, $shell->current() );
	}

	/**
	 * One page of Domain Opportunities (R17-R19): untied `Text` rows by domain, not this site's, with their count; no
	 * dismissal or host filter, as Lasso. A search holding "Opportunities" lists External Text and Image-only rows per
	 * domain, tied or not (R18, kept by D16). Default order: the biggest count first.
	 *
	 * @param OpportunityQuery $query The search, sort and page.
	 */
	public function domains( OpportunityQuery $query ): OpportunityPage {
		global $wpdb;

		$quirk  = str_contains( $query->search, 'Opportunities' );
		$where  = $quirk
			? "l.link_type = 'external' AND l.display_type IN ('Text', 'Image-only anchor text')"
			: "l.product_id = 0 AND l.assigned_product_id = 0 AND l.display_type = 'Text'";
		$where .= " AND l.domain IS NOT NULL AND l.domain <> '' AND l.domain <> %s";
		$args   = array( BaseDomain::of( home_url() ) );
		$words  = $quirk ? array() : $query->words();
		if ( array() !== $words ) {
			$where .= ' AND l.domain LIKE %s';
			$args[] = self::like( $words );
		}
		$table = $wpdb->prefix . TableLinkLocationRepository::TABLE;
		$order = match ( $query->orderby ) {
			'domain' => 'l.domain ' . strtoupper( $query->order ),
			'count'  => 'links ' . strtoupper( $query->order ) . ', l.domain ASC',
			default  => 'links DESC, l.domain ASC',
		};

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Affibloom's own table, one screen; $where holds only placeholders, $order is from the allow-list.
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT l.domain) FROM %i AS l WHERE {$where}", $table, ...$args ) );
		$shell = new OpportunityPage( array(), $total, $query->page );
		$rows  = 0 === $total ? array() : $wpdb->get_results(
			$wpdb->prepare( "SELECT l.domain, COUNT(*) AS links FROM %i AS l WHERE {$where} GROUP BY l.domain ORDER BY {$order} LIMIT %d OFFSET %d", $table, ...array_merge( $args, array( OpportunityPage::PER_PAGE, $shell->offset() ) ) ),
			ARRAY_A
		);
		$rows  = is_array( $rows ) ? $rows : array();
		// What could monetize each domain (R19): the name of a product of that domain, any status.
		$names = array();
		if ( array() !== $rows ) {
			$domains = array_map( static fn( array $row ): string => (string) $row['domain'], $rows );
			$found   = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT m.meta_value AS domain, MIN(p.post_title) AS name FROM %i AS m JOIN %i AS p ON p.ID = m.post_id AND p.post_type = %s WHERE m.meta_key = %s AND m.meta_value IN (' . implode( ',', array_fill( 0, count( $domains ), '%s' ) ) . ') GROUP BY m.meta_value',
					$wpdb->postmeta,
					$wpdb->posts,
					ProductPostType::NAME,
					'_affibloom_domain',
					...$domains
				),
				ARRAY_A
			);
			foreach ( is_array( $found ) ? $found : array() as $named ) {
				$names[ (string) $named['domain'] ] = (string) $named['name'];
			}
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		$items = array();
		foreach ( $rows as $row ) {
			$domain  = (string) $row['domain'];
			$items[] = new DomainCount( $domain, (int) $row['links'], $names[ $domain ] ?? ( AmazonDomains::excludes( $domain ) ? 'Amazon Associates' : '' ) );
		}

		return new OpportunityPage( $items, $total, $shell->current() );
	}

	/**
	 * One page of Detected Links (R20): a domain's untied rows, any link or display type, no dismissal or host filter;
	 * a search matches the post's title or the link's text; default order: the index's.
	 *
	 * @param string           $domain The base domain.
	 * @param OpportunityQuery $query  The search, sort and page.
	 */
	public function domain_links( string $domain, OpportunityQuery $query ): OpportunityPage {
		global $wpdb;

		$where = 'l.product_id = 0 AND l.assigned_product_id = 0 AND l.domain = %s';
		$args  = array( $domain );
		$words = $query->words();
		if ( array() !== $words ) {
			$where .= ' AND ( h.post_title LIKE %s OR l.anchor_text LIKE %s )';
			$args[] = self::like( $words );
			$args[] = self::like( $words );
		}
		$order = match ( $query->orderby ) {
			'url'        => 'l.url ' . strtoupper( $query->order ) . ', l.id ASC',
			'post_title' => 'h.post_title ' . strtoupper( $query->order ) . ', l.id ASC',
			default      => 'l.id ASC',
		};

		return $this->detected( $where, $args, $order, $query->page );
	}

	/**
	 * A page of untied links with their posts, for Detected Links and a post's links.
	 *
	 * @param string           $where The condition, placeholders only.
	 * @param list<string|int> $args  Its values.
	 * @param string           $order The ORDER BY, from an allow-list.
	 * @param int              $page  The page.
	 */
	private function detected( string $where, array $args, string $order, int $page ): OpportunityPage {
		global $wpdb;

		$from = $wpdb->prepare( 'FROM %i AS l JOIN %i AS h ON h.ID = l.host_id', $wpdb->prefix . TableLinkLocationRepository::TABLE, $wpdb->posts );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Affibloom's own table, one screen; $from is prepared, $where holds only placeholders, $order is from an allow-list.
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) {$from} WHERE {$where}", ...$args ) );
		$shell = new OpportunityPage( array(), $total, $page );
		$rows  = 0 === $total ? array() : $wpdb->get_results(
			$wpdb->prepare( "SELECT l.id, l.url, l.display_type, l.anchor_text, l.display_ref, l.host_id, l.host_url, h.post_title AS host_title {$from} WHERE {$where} ORDER BY {$order} LIMIT %d OFFSET %d", ...array_merge( $args, array( OpportunityPage::PER_PAGE, $shell->offset() ) ) ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		$items = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$items[] = new DetectedLink( self::place( $row ), (int) $row['host_id'], (string) $row['host_title'], (string) $row['host_url'] );
		}

		return new OpportunityPage( $items, $total, $shell->current() );
	}

	/**
	 * A LIKE pattern from the search's words: escaped, in order, anything between them (R7).
	 *
	 * @param list<string> $words The words.
	 */
	private static function like( array $words ): string {
		global $wpdb;

		return '%' . implode( '%', array_map( static fn( string $word ): string => $wpdb->esc_like( $word ), $words ) ) . '%';
	}

	/**
	 * A row's link as a place.
	 *
	 * @param array<string, mixed> $row The row.
	 */
	private static function place( array $row ): ProductLocation {
		return new ProductLocation( (int) $row['id'], (string) $row['display_type'], (string) $row['url'], null === $row['anchor_text'] ? null : (string) $row['anchor_text'], null === $row['display_ref'] ? null : (string) $row['display_ref'] );
	}
}
