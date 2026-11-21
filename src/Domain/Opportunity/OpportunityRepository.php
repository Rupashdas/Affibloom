<?php
/**
 * Where opportunities are found.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Opportunity;

/**
 * The opportunity queries over the link index (F22 D4; Tasks O1-O9).
 */
interface OpportunityRepository {

	/**
	 * One page of Link Opportunities (D4): untied, undismissed links whose domain is a published product's, with that
	 * product's Detect Opportunities on; not this site's host and not Amazon's.
	 *
	 * @param OpportunityQuery $query The search, sort, page and product.
	 */
	public function links( OpportunityQuery $query ): OpportunityPage;

	/**
	 * One page of Domain Opportunities (R17, R18): untied text links grouped by domain, with each domain's count and
	 * what could monetize it (R19).
	 *
	 * @param OpportunityQuery $query The search, sort and page.
	 */
	public function domains( OpportunityQuery $query ): OpportunityPage;

	/**
	 * One page of Detected Links (R20): a domain's untied links.
	 *
	 * @param string           $domain The base domain.
	 * @param OpportunityQuery $query  The search, sort and page.
	 */
	public function domain_links( string $domain, OpportunityQuery $query ): OpportunityPage;

	/**
	 * One page of Content Opportunities (R24, R25): published posts with indexed links, narrowed by the filter.
	 *
	 * @param OpportunityQuery $query  The search, sort and page.
	 * @param ContentFilter    $filter The filter bar.
	 * @param string           $since  The first UTC day of Min link clicks' 30 days, "Y-m-d".
	 */
	public function content( OpportunityQuery $query, ContentFilter $filter, string $since ): OpportunityPage;

	/**
	 * One page of a post's links (R29, R31): its text and image links, tied or not, product links first; every
	 * published post's when $post is 0.
	 *
	 * @param int              $post  The post, 0 for all.
	 * @param OpportunityQuery $query The search and page.
	 */
	public function post_links( int $post, OpportunityQuery $query ): OpportunityPage;

	/**
	 * How many Link Opportunities each product has (R52, R53, D13; Task O8): rows, or the distinct posts they are in.
	 *
	 * @param list<int> $product_ids The products.
	 * @param bool      $hosts       Count distinct posts instead of rows.
	 * @return array<int, int> By product ID; a product without any is left out.
	 */
	public function counts_for( array $product_ids, bool $hosts ): array;

	/**
	 * How many anchors the Content Opportunities CSV would write (R58; Task O9).
	 *
	 * @param OpportunityQuery $query  The search and sort.
	 * @param ContentFilter    $filter The filter bar.
	 * @param string           $since  The first day of the clicks counted, UTC "Y-m-d".
	 */
	public function export_count( OpportunityQuery $query, ContentFilter $filter, string $since ): int;

	/**
	 * The CSV's posts, as Content lists them, with their type, clicks and unmonetized count (R59, R60; Task O9).
	 *
	 * @param OpportunityQuery $query  The search and sort.
	 * @param ContentFilter    $filter The filter bar.
	 * @param string           $since  The first day of the clicks counted, UTC "Y-m-d".
	 * @return list<array<string, string>> Each: host_id, post_title, host_url, post_type, post_modified, clicks, unmonetized.
	 */
	public function export_posts( OpportunityQuery $query, ContentFilter $filter, string $since ): array;

	/**
	 * Some posts' untied Text and Image-only rows, dismissed ones too, by post then row (R59; Task O9).
	 *
	 * @param list<int> $host_ids The posts.
	 * @return list<array<string, string>> Each: id, host_id, anchor_text, url, domain, link_type, display_type, source, occurrence.
	 */
	public function export_links( array $host_ids ): array;

	/**
	 * Each domain's lowest-ID published product (R60; Task O9).
	 *
	 * @return array<string, array{id: int, title: string}> By domain.
	 */
	public function suggestions(): array;

	/**
	 * Some posts' category names, A to Z, joined with ", " (R60; Task O9).
	 *
	 * @param list<int> $host_ids The posts.
	 * @return array<int, string> By post.
	 */
	public function categories( array $host_ids ): array;
}
