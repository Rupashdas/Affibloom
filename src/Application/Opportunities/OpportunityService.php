<?php
/**
 * The Opportunities screens' lists.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Opportunities;

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
}
