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
}
