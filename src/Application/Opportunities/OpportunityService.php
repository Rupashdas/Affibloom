<?php
/**
 * The Opportunities screens' lists.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Opportunities;

use Affibloom\Application\Clicks\ClickReport;
use Affibloom\Domain\Opportunity\ContentFilter;
use Affibloom\Domain\Opportunity\OpportunityPage;
use Affibloom\Domain\Opportunity\OpportunityQuery;
use Affibloom\Domain\Opportunity\OpportunityRepository;

/**
 * F22's lists over the link index (Tasks O1-O9). Link Opportunities: untied links to a domain the site already has a
 * product for (D4).
 */
final class OpportunityService {

	/**
	 * Builds the service.
	 *
	 * @param OpportunityRepository $opportunities The queries.
	 */
	public function __construct( private OpportunityRepository $opportunities ) {}

	/**
	 * One page of Link Opportunities.
	 *
	 * @param OpportunityQuery $query The search, sort, page and product.
	 */
	public function links( OpportunityQuery $query ): OpportunityPage {
		return $this->opportunities->links( $query );
	}

	/**
	 * One page of Domain Opportunities (R17-R19; Task O2).
	 *
	 * @param OpportunityQuery $query The search, sort and page.
	 */
	public function domains( OpportunityQuery $query ): OpportunityPage {
		return $this->opportunities->domains( $query );
	}

	/**
	 * One page of Detected Links for a domain (R20; Task O2).
	 *
	 * @param string           $domain The base domain.
	 * @param OpportunityQuery $query  The search, sort and page.
	 */
	public function domain_links( string $domain, OpportunityQuery $query ): OpportunityPage {
		return $this->opportunities->domain_links( strtolower( trim( $domain ) ), $query );
	}

	/**
	 * One page of Content Opportunities (R24-R26; Task O3); Min link clicks reads F12's last 30 days (D17).
	 *
	 * @param OpportunityQuery $query  The search, sort and page.
	 * @param ContentFilter    $filter The filter bar.
	 * @param int              $now    Now, as a Unix time.
	 */
	public function content( OpportunityQuery $query, ContentFilter $filter, int $now ): OpportunityPage {
		return $this->opportunities->content( $query, $filter, ClickReport::since( $now ) );
	}

	/**
	 * One page of a post's links (R29-R31; Task O4).
	 *
	 * @param int              $post  The post, 0 for every published post.
	 * @param OpportunityQuery $query The search and page.
	 */
	public function post_links( int $post, OpportunityQuery $query ): OpportunityPage {
		return $this->opportunities->post_links( max( 0, $post ), $query );
	}
}
