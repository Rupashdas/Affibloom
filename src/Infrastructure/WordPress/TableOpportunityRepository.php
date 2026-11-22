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
use Affibloom\Domain\Opportunity\ContentFilter;
use Affibloom\Domain\Opportunity\ContentRow;
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

	/**
	 * The products with an opportunity, read once in this request (product_ids()); null until read. Only the Dashboard
	 * list reads it, and nothing in that request writes the index (dismissing and assigning are requests of their own).
	 *
	 * @var list<int>|null
	 */
	private static ?array $with_opportunities = null;

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

		$where = self::rule();
		$args  = self::excluded();
		$words = $query->words();
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
				"SELECT l.id, IF( l.use_original = 1, l.original_url, l.url ) AS url, l.domain, l.display_type, l.anchor_text, l.display_ref, l.host_id, l.host_url, h.post_title AS host_title, p.ID AS product_id, p.post_title AS product_name
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
	 * How many Link Opportunities each product has (F22 R52, R53, D13; Task O8): D4's rows, or the distinct posts they
	 * are in (the Dashboard's Opportunities tab counts posts, its other tabs rows, as Lasso).
	 *
	 * @param list<int> $product_ids The products.
	 * @param bool      $hosts       Count distinct posts instead of rows.
	 * @return array<int, int> By product ID; a product without any is left out.
	 */
	public function counts_for( array $product_ids, bool $hosts ): array {
		global $wpdb;

		$ids = array_values( array_filter( array_map( 'intval', $product_ids ) ) );
		if ( array() === $ids ) {
			return array();
		}
		$count = $hosts ? 'COUNT(DISTINCT l.host_id)' : 'COUNT(*)';
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Affibloom's own table, one page of products; the rule and the ID list hold only placeholders.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT dm.post_id AS product, {$count} AS found FROM %i AS l
				JOIN %i AS h ON h.ID = l.host_id
				JOIN %i AS dm ON dm.meta_key = '_affibloom_domain' AND dm.meta_value = l.domain
					AND dm.post_id IN (" . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ")
				LEFT JOIN %i AS dop ON dop.post_id = dm.post_id AND dop.meta_key = '_affibloom_detect_opportunities'
				WHERE " . self::rule() . ' GROUP BY dm.post_id',
				$wpdb->prefix . TableLinkLocationRepository::TABLE,
				$wpdb->posts,
				$wpdb->postmeta,
				...array_merge( $ids, array( $wpdb->postmeta ), self::excluded() )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		$counts = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$counts[ (int) $row['product'] ] = (int) $row['found'];
		}

		return $counts;
	}

	/**
	 * A product's Link Opportunities as an SQL value, for the Dashboard's Opportunities sort (F22 R50-R52, D13; Task
	 * O8): the posts with an opportunity for the product in the column given, 0 for none. The product's own status is
	 * the outer query's (the Dashboard lists published products). The counts are made once for every product
	 * (self::per_product_sql()) and looked up per row: a count made per row scanned the index's untied links once per
	 * product, ~0.9 s for 740 products against ~0.3 s (Task 37B, finding P3).
	 *
	 * @param string $id_column The product ID column of the outer query, e.g. "wp_posts.ID".
	 */
	public static function count_sql( string $id_column ): string {
		return 'COALESCE( ( SELECT afo.posts FROM ' . self::per_product_sql() . " AS afo WHERE afo.product = {$id_column} ), 0 )";
	}

	/**
	 * The products with at least one Link Opportunity: the Opportunities tab and its count on every Dashboard list
	 * (F22 R50, D13; Task O8). Read once per request and kept, as one list request asks for it up to three times (the
	 * tab's count, the tab's page and its total); one set, not a test per product (Task 37B, finding P3: ~1 s per use
	 * became ~0.2 s once).
	 *
	 * @return list<int>
	 */
	public static function product_ids(): array {
		if ( null === self::$with_opportunities ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Affibloom's own table; per_product_sql() is prepared; kept for the request.
			self::$with_opportunities = array_map( 'intval', $wpdb->get_col( 'SELECT afo.product FROM ' . self::per_product_sql() . ' AS afo' ) );
		}

		return self::$with_opportunities;
	}

	/**
	 * D4's rule grouped by product: each product with an opportunity and the number of posts it has one in, as a
	 * derived table (`product`, `posts`).
	 */
	private static function per_product_sql(): string {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- the rule holds only placeholders.
		$sql = (string) $wpdb->prepare(
			"( SELECT dm.post_id AS product, COUNT(DISTINCT l.host_id) AS posts FROM %i AS l
			JOIN %i AS h ON h.ID = l.host_id
			JOIN %i AS dm ON dm.meta_key = '_affibloom_domain' AND dm.meta_value = l.domain
			LEFT JOIN %i AS dop ON dop.post_id = dm.post_id AND dop.meta_key = '_affibloom_detect_opportunities'
			WHERE " . self::rule() . ' GROUP BY dm.post_id )',
			$wpdb->prefix . TableLinkLocationRepository::TABLE,
			$wpdb->posts,
			$wpdb->postmeta,
			$wpdb->postmeta,
			...self::excluded()
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		return $sql;
	}

	/**
	 * D4's rule for an index row (l) and its product's Detect Opportunities (dop): untied, not dismissed, with a
	 * domain that is neither this site's nor one of Amazon's; placeholders for self::excluded().
	 */
	private static function rule(): string {
		return "l.product_id = 0 AND l.assigned_product_id = 0 AND l.is_ignored = 0 AND l.domain IS NOT NULL AND l.domain <> ''
			AND l.domain NOT IN (" . implode( ',', array_fill( 0, count( self::excluded() ), '%s' ) ) . ") AND COALESCE(dop.meta_value, '1') <> '0'";
	}

	/**
	 * The domains that are never an opportunity (D4): this site's own, and Amazon's.
	 *
	 * @return list<string>
	 */
	private static function excluded(): array {
		return array_merge( array( BaseDomain::of( home_url() ) ), AmazonDomains::EXCLUDED );
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
		// What could monetize each domain (R19): "Amazon Associates" for any of Lasso's 23 Amazon domains, short links
		// included, ahead of a product's name (pages/class-ajax.php 732-737); else the name of a product of that
		// domain, any status.
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
			$items[] = new DomainCount( $domain, (int) $row['links'], in_array( $domain, AmazonDomains::ALL, true ) ? 'Amazon Associates' : ( $names[ $domain ] ?? '' ) );
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
	 * One page of a post's links (R29, R31): its Text and Image-only rows, tied or not, in a published post; a search
	 * matches the URL or the text; product links first, then internal, then external (Lasso's link type descending),
	 * then the index's order.
	 *
	 * @param int              $post  The post, 0 for every published post.
	 * @param OpportunityQuery $query The search and page.
	 */
	public function post_links( int $post, OpportunityQuery $query ): OpportunityPage {
		$where = "h.post_status = 'publish' AND l.display_type IN ('Text', 'Image-only anchor text')";
		$args  = array();
		if ( $post > 0 ) {
			$where .= ' AND l.host_id = %d';
			$args[] = $post;
		}
		$words = $query->words();
		if ( array() !== $words ) {
			$where .= ' AND ( l.url LIKE %s OR l.anchor_text LIKE %s )';
			$args[] = self::like( $words );
			$args[] = self::like( $words );
		}

		return $this->detected( $where, $args, 'l.link_type DESC, l.id ASC', $query->page );
	}

	/**
	 * One page of Content Opportunities (R24-R26, D17, D18): each published post with index rows, its link count and
	 * how many are tied; a search matches the title; the filter narrows by unmonetized links, type (posts, pages; all
	 * types includes reusable blocks), category and the 30-day clicks of the products tied in it. Default order: the
	 * most recently changed first.
	 *
	 * @param OpportunityQuery $query  The search, sort and page.
	 * @param ContentFilter    $filter The filter bar.
	 * @param string           $since  The first UTC day of the clicks counted, "Y-m-d".
	 */
	public function content( OpportunityQuery $query, ContentFilter $filter, string $since ): OpportunityPage {
		global $wpdb;

		[ $group, $args ] = $this->content_hosts( $query, $filter, $since, $filter->min_clicks > 0 );
		$order            = self::content_order( $query, '' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Affibloom's own table, one screen; the parts are prepared, $group holds only placeholders, $order is from the allow-list.
		$total = (int) $wpdb->get_var( array() === $args ? "SELECT COUNT(*) FROM ( {$group} ) AS hosts" : $wpdb->prepare( "SELECT COUNT(*) FROM ( {$group} ) AS hosts", ...$args ) );
		$shell = new OpportunityPage( array(), $total, $query->page );
		$rows  = 0 === $total ? array() : $wpdb->get_results( $wpdb->prepare( "{$group} ORDER BY {$order}, l.host_id DESC LIMIT %d OFFSET %d", ...array_merge( $args, array( OpportunityPage::PER_PAGE, $shell->offset() ) ) ), ARRAY_A );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		$items = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$items[] = new ContentRow( (int) $row['host_id'], (string) $row['post_title'], (string) $row['host_url'], (string) $row['post_modified'], (int) $row['links'], (int) $row['monetized'] );
		}

		return new OpportunityPage( $items, $total, $shell->current() );
	}

	/**
	 * How many anchors the Content Opportunities CSV would write for the list as searched and filtered (R58; Task O9).
	 *
	 * @param OpportunityQuery $query  The search and sort.
	 * @param ContentFilter    $filter The filter bar.
	 * @param string           $since  The first day of the clicks counted, UTC "Y-m-d".
	 */
	public function export_count( OpportunityQuery $query, ContentFilter $filter, string $since ): int {
		global $wpdb;

		[ $from, $where, $args ] = $this->export_from( $query, $filter, $since );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Affibloom's own table; $from is built from prepared parts and placeholders.
		return (int) $wpdb->get_var( array() === $args ? "SELECT COUNT(*) {$from} WHERE {$where}" : $wpdb->prepare( "SELECT COUNT(*) {$from} WHERE {$where}", ...$args ) );
	}

	/**
	 * The Content Opportunities CSV's posts (R59, R60; Task O9): those Content lists as searched and filtered, in its
	 * order, each with its post type, clicks and unmonetized count; one query for the whole file.
	 *
	 * @param OpportunityQuery $query  The search and sort.
	 * @param ContentFilter    $filter The filter bar.
	 * @param string           $since  The first day of the clicks counted, UTC "Y-m-d".
	 * @return list<array<string, string>> Each: host_id, post_title, host_url, post_type, post_modified, clicks, unmonetized.
	 */
	public function export_posts( OpportunityQuery $query, ContentFilter $filter, string $since ): array {
		global $wpdb;

		[ $group, $args ] = $this->content_hosts( $query, $filter, $since, true );
		$order            = self::content_order( $query, 'hosts.' );
		$sql              = "SELECT hosts.host_id, hosts.post_title, hosts.host_url, h.post_type, hosts.post_modified, hosts.clicks, hosts.links - hosts.monetized AS unmonetized
			FROM ( {$group} ) AS hosts" . $wpdb->prepare( ' JOIN %i AS h ON h.ID = hosts.host_id', $wpdb->posts ) . " ORDER BY {$order}, hosts.host_id DESC";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Affibloom's own table; $sql is built from prepared parts and placeholders, $order from the allow-list.
		$rows = $wpdb->get_results( array() === $args ? $sql : $wpdb->prepare( $sql, ...$args ), ARRAY_A );

		return self::strings( $rows );
	}

	/**
	 * Some posts' anchors for the CSV (R59): their untied Text and Image-only rows, dismissed ones too, by post then row.
	 *
	 * @param list<int> $host_ids The posts.
	 * @return list<array<string, string>> Each: id, host_id, anchor_text, url, domain, link_type, display_type, source, occurrence.
	 */
	public function export_links( array $host_ids ): array {
		global $wpdb;

		$ids = array_values( array_filter( array_map( 'intval', $host_ids ) ) );
		if ( array() === $ids ) {
			return array();
		}
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Affibloom's own table; the ID list holds only placeholders.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT l.id, l.host_id, l.anchor_text, IF( l.use_original = 1, l.original_url, l.url ) AS url, l.domain, l.link_type, l.display_type, l.source, l.occurrence FROM %i AS l
				WHERE l.host_id IN (' . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ") AND l.product_id = 0 AND l.assigned_product_id = 0
					AND l.display_type IN ('Text', 'Image-only anchor text')
				ORDER BY l.host_id, l.id",
				$wpdb->prefix . TableLinkLocationRepository::TABLE,
				...$ids
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		return self::strings( $rows );
	}

	/**
	 * Each domain's suggested product for the CSV (R60): the lowest-ID published product with that domain.
	 *
	 * @return array<string, array{id: int, title: string}> By domain.
	 */
	public function suggestions(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one read per export.
		$rows     = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT first.domain, p.ID, p.post_title FROM (
					SELECT dm.meta_value AS domain, MIN(dm.post_id) AS id FROM %i AS dm
					JOIN %i AS sp ON sp.ID = dm.post_id AND sp.post_type = %s AND sp.post_status = 'publish'
					WHERE dm.meta_key = '_affibloom_domain' AND dm.meta_value <> '' GROUP BY dm.meta_value
				) AS first JOIN %i AS p ON p.ID = first.id",
				$wpdb->postmeta,
				$wpdb->posts,
				ProductPostType::NAME,
				$wpdb->posts
			),
			ARRAY_A
		);
		$products = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$products[ (string) $row['domain'] ] = array(
				'id'    => (int) $row['ID'],
				'title' => (string) $row['post_title'],
			);
		}

		return $products;
	}

	/**
	 * Some posts' categories for the CSV (R60): their names, A to Z, joined with ", ".
	 *
	 * @param list<int> $host_ids The posts.
	 * @return array<int, string> By post; a post without categories is left out.
	 */
	public function categories( array $host_ids ): array {
		global $wpdb;

		$ids = array_values( array_filter( array_map( 'intval', $host_ids ) ) );
		if ( array() === $ids ) {
			return array();
		}
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- WordPress's term tables; the ID list holds only placeholders.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tr.object_id, GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') AS names FROM %i AS tr
				JOIN %i AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'category'
				JOIN %i AS t ON t.term_id = tt.term_id
				WHERE tr.object_id IN (" . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ') GROUP BY tr.object_id',
				$wpdb->term_relationships,
				$wpdb->term_taxonomy,
				$wpdb->terms,
				...$ids
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		$names = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$names[ (int) $row['object_id'] ] = (string) $row['names'];
		}

		return $names;
	}

	/**
	 * Database rows as text.
	 *
	 * @param mixed $rows What get_results() gave.
	 * @return list<array<string, string>>
	 */
	private static function strings( mixed $rows ): array {
		return array_values( array_map( static fn( array $row ): array => array_map( 'strval', $row ), is_array( $rows ) ? $rows : array() ) );
	}

	/**
	 * The export count's FROM and WHERE (R58): the Content list's posts (hosts), their untied Text and Image-only rows
	 * (l), and the post (h).
	 *
	 * @param OpportunityQuery $query  The search and sort.
	 * @param ContentFilter    $filter The filter bar.
	 * @param string           $since  The first day of the clicks counted.
	 * @return array{0: string, 1: string, 2: list<string|int>} The FROM, the WHERE (placeholders only), and their values.
	 */
	private function export_from( OpportunityQuery $query, ContentFilter $filter, string $since ): array {
		global $wpdb;

		[ $group, $args ] = $this->content_hosts( $query, $filter, $since, $filter->min_clicks > 0 );
		$from             = "FROM ( {$group} ) AS hosts"
			. $wpdb->prepare( ' JOIN %i AS l ON l.host_id = hosts.host_id', $wpdb->prefix . TableLinkLocationRepository::TABLE );
		$where            = "l.product_id = 0 AND l.assigned_product_id = 0 AND l.display_type IN ('Text', 'Image-only anchor text')";

		return array( $from, $where, $args );
	}

	/**
	 * Content Opportunities' posts as one grouped query (R24-R26): published posts with their link count, monetized
	 * count and, when asked, their tied products' clicks in the 30 days (D17); the search, Post type, Category,
	 * Show and Min link clicks applied. Shared by the list and the CSV (Task O9).
	 *
	 * @param OpportunityQuery $query  The search.
	 * @param ContentFilter    $filter The filter bar.
	 * @param string           $since  The first day of the clicks counted.
	 * @param bool             $clicks Count the clicks (the Min link clicks filter, or the CSV's column).
	 * @return array{0: string, 1: list<string|int>} The SQL, placeholders only, and its values.
	 */
	private function content_hosts( OpportunityQuery $query, ContentFilter $filter, string $since, bool $clicks ): array {
		global $wpdb;

		$table  = $wpdb->prefix . TableLinkLocationRepository::TABLE;
		$join   = '';
		$where  = "h.post_status = 'publish'";
		$args   = array();
		$having = array();
		if ( '' !== $filter->post_type ) {
			$where .= ' AND h.post_type = %s';
			$args[] = $filter->post_type;
		}
		if ( $filter->category > 0 ) {
			$join = $wpdb->prepare( " JOIN %i AS ctr ON ctr.object_id = h.ID JOIN %i AS ctt ON ctt.term_taxonomy_id = ctr.term_taxonomy_id AND ctt.taxonomy = 'category' AND ctt.term_id = %d", $wpdb->term_relationships, $wpdb->term_taxonomy, $filter->category );
		}
		$words = $query->words();
		if ( array() !== $words ) {
			$where .= ' AND h.post_title LIKE %s';
			$args[] = self::like( $words );
		}
		if ( ContentFilter::UNMONETIZED === $filter->show ) {
			$having[] = 'links > monetized';
		}
		$sum = '0';
		if ( $clicks ) {
			// The tied products' clicks in the 30 days, each product once (Lasso sums url_details.clicks; D17).
			$sum = $wpdb->prepare(
				'( SELECT COALESCE(SUM(c.clicks), 0) FROM %i AS c WHERE c.day >= %s AND c.product_id IN ( SELECT IF(t.assigned_product_id > 0, t.assigned_product_id, t.product_id) FROM %i AS t WHERE t.host_id = l.host_id AND ( t.product_id > 0 OR t.assigned_product_id > 0 ) ) )',
				$wpdb->prefix . TableClickStore::TABLE,
				$since,
				$table
			);
		}
		if ( $filter->min_clicks > 0 ) {
			$having[] = $wpdb->prepare( 'clicks >= %d', $filter->min_clicks );
		}
		$having = array() === $having ? '' : ' HAVING ' . implode( ' AND ', $having );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $sum and $join are prepared above (table names, a date, an ID: no %).
		$select = $wpdb->prepare(
			"SELECT l.host_id, h.post_title, h.post_modified, MAX(l.host_url) AS host_url, COUNT(*) AS links,
				SUM(l.product_id > 0 OR l.assigned_product_id > 0) AS monetized, {$sum} AS clicks
			FROM %i AS l JOIN %i AS h ON h.ID = l.host_id{$join}",
			$table,
			$wpdb->posts
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array( "{$select} WHERE {$where} GROUP BY l.host_id, h.post_title, h.post_modified{$having}", $args );
	}

	/**
	 * Content's ORDER BY (R26): its four sortable columns, newest first by default; an allow-list, so no input reaches
	 * the SQL.
	 *
	 * @param OpportunityQuery $query The sort.
	 * @param string           $alias '' inside the grouped query, 'hosts.' outside it.
	 */
	private static function content_order( OpportunityQuery $query, string $alias ): string {
		$direction = 'asc' === $query->order ? 'ASC' : 'DESC';
		$inner     = '' === $alias;

		return match ( $query->orderby ) {
			'post_modified'   => ( $inner ? 'h.' : $alias ) . 'post_modified ' . $direction,
			'detection_title' => ( $inner ? 'h.' : $alias ) . 'post_title ' . $direction,
			'detection_slug'  => $alias . 'host_url ' . $direction,
			'count'           => $alias . 'links ' . $direction,
			default           => ( $inner ? 'h.' : $alias ) . 'post_modified DESC',
		};
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

		$from = $wpdb->prepare( 'FROM %i AS l JOIN %i AS h ON h.ID = l.host_id LEFT JOIN %i AS ap ON ap.ID = l.assigned_product_id', $wpdb->prefix . TableLinkLocationRepository::TABLE, $wpdb->posts, $wpdb->posts );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Affibloom's own table, one screen; $from is prepared, $where holds only placeholders, $order is from an allow-list.
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) {$from} WHERE {$where}", ...$args ) );
		$shell = new OpportunityPage( array(), $total, $page );
		$rows  = 0 === $total ? array() : $wpdb->get_results(
			$wpdb->prepare( "SELECT l.id, IF( l.use_original = 1, l.original_url, l.url ) AS url, l.domain, l.display_type, l.anchor_text, l.display_ref, l.host_id, l.host_url, h.post_title AS host_title, ( l.product_id > 0 OR l.assigned_product_id > 0 ) AS tied, ( l.product_id > 0 AND l.assigned_product_id = 0 AND l.original_url <> l.url ) AS restorable, l.assigned_product_id AS assigned, ap.post_title AS assigned_name {$from} WHERE {$where} ORDER BY {$order} LIMIT %d OFFSET %d", ...array_merge( $args, array( OpportunityPage::PER_PAGE, $shell->offset() ) ) ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		$items = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$items[] = new DetectedLink( self::place( $row ), (int) $row['host_id'], (string) $row['host_title'], (string) $row['host_url'], '1' === (string) $row['tied'], (string) $row['domain'], (int) $row['assigned'], (string) $row['assigned_name'], '1' === (string) $row['restorable'] );
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
