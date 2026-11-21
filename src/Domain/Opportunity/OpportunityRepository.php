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
}
