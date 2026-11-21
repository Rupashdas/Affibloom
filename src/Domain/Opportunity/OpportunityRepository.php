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
}
