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
			$like   = '%' . implode( '%', array_map( static fn( string $word ): string => $wpdb->esc_like( $word ), $words ) ) . '%';
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
				new ProductLocation( (int) $row['id'], (string) $row['display_type'], (string) $row['url'], null === $row['anchor_text'] ? null : (string) $row['anchor_text'], null === $row['display_ref'] ? null : (string) $row['display_ref'] ),
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
}
