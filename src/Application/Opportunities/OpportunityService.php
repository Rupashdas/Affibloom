<?php
/**
 * The Opportunities screens' lists.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Opportunities;

use Affibloom\Application\Clicks\ClickReport;
use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Opportunity\ContentFilter;
use Affibloom\Domain\Opportunity\OpportunityPage;
use Affibloom\Domain\Opportunity\OpportunityQuery;
use Affibloom\Domain\Opportunity\OpportunityRepository;
use Closure;

/**
 * F22's lists over the link index (Tasks O1-O9), Dismiss and its Undo (Task O7), Monetize and Unmonetize (Task O10). Link Opportunities: untied links to a domain the site already has a
 * product for (D4).
 */
final class OpportunityService {

	/**
	 * Builds the service.
	 *
	 * @param OpportunityRepository  $opportunities The queries.
	 * @param LinkLocationRepository $locations     The link index, for Dismiss and Undo (Task O7) and Monetize (O10).
	 * @param ProductRepository|null $products      Monetize's product, which must be published (Task O10).
	 * @param Closure|null           $reindex       Indexes one post again: fn( int $host_id ): mixed (Unmonetize, O10).
	 */
	public function __construct( private OpportunityRepository $opportunities, private LinkLocationRepository $locations, private ?ProductRepository $products = null, private ?Closure $reindex = null ) {}

	/**
	 * Monetize (R64-R69, N1-A; Task O10): the link prints the product's link, `target` and `rel` at render time and
	 * counts as tied everywhere; the stored post does not change. A link tied by its own address is the post's to
	 * change (N3); a product that is not published cannot be chosen.
	 *
	 * @param int $row        The row's ID.
	 * @param int $product_id The product.
	 * @return string '' when done; 'gone', 'in_content' or 'no_product' otherwise.
	 */
	public function assign( int $row, int $product_id ): string {
		$place = $this->locations->find( $row );
		if ( null === $place ) {
			return 'gone';
		}
		// Tied by its own address (an assignment shows as a tie too once the post is rescanned).
		if ( $place->product_id > 0 && 0 === $place->assigned_product_id ) {
			return 'in_content';
		}
		if ( null === $this->products?->get( $product_id ) ) {
			return 'no_product';
		}
		$this->locations->set_assigned( $row, $product_id );

		return '';
	}

	/**
	 * Unmonetize (R72, N1-A; Task O10): the assignment is cleared, so readers see the stored link again; the post is
	 * indexed again, so the link is tied by its own address if it has one. A link tied by its own address is turned
	 * back to the URL it had before another plugin rewrote it (Lasso's original_link_slug; F22 N3, E17; Task GF6), at
	 * render time; without an original that differs it stays the post's to change.
	 *
	 * @param int $row The row's ID.
	 * @return string '' when done; 'gone' or 'in_content' otherwise.
	 */
	public function unassign( int $row ): string {
		$place = $this->locations->find( $row );
		if ( null === $place ) {
			return 'gone';
		}
		$own_tie = $place->product_id > 0 && 0 === $place->assigned_product_id;
		if ( $own_tie ? ! $this->locations->set_original( $row ) : ! $this->locations->set_assigned( $row, 0 ) ) {
			return $own_tie ? 'in_content' : 'gone';
		}
		if ( null !== $this->reindex ) {
			( $this->reindex )( $place->host_id );
		}

		return '';
	}

	/**
	 * The post a row of the index is in, for the routes' `edit_post` check (D20); 0 when the row is gone.
	 *
	 * @param int $row The row's ID.
	 */
	public function host_of( int $row ): int {
		return $this->locations->find( $row )->host_id ?? 0;
	}

	/**
	 * Dismisses an opportunity (R44-R46, D12): the row leaves Link Opportunities, the product tab and their counts, and
	 * stays dismissed across rescans (D10). Domains, Detected Links, Content and a post's links still list it.
	 *
	 * @param int $row The row's ID.
	 * @return bool False when the row is gone.
	 */
	public function dismiss( int $row ): bool {
		return $this->locations->set_ignored( $row, true );
	}

	/**
	 * Brings a dismissed opportunity back: the notice's Undo (D11).
	 *
	 * @param int $row The row's ID.
	 * @return bool False when the row is gone.
	 */
	public function restore( int $row ): bool {
		return $this->locations->set_ignored( $row, false );
	}

	/**
	 * One page of Link Opportunities.
	 *
	 * @param OpportunityQuery $query The search, sort, page and product.
	 */
	public function links( OpportunityQuery $query ): OpportunityPage {
		return $this->opportunities->links( $query );
	}

	/**
	 * Each Dashboard row's opportunity count, the lightbulb's number (R52-R54, D13; Task O8): distinct posts on the
	 * Opportunities tab, rows on the others, as Lasso.
	 *
	 * @param list<int> $product_ids The rows' products.
	 * @param bool      $by_hosts    The Opportunities tab is on screen.
	 * @return array<int, int> By product ID; a product without any is left out.
	 */
	public function per_product( array $product_ids, bool $by_hosts ): array {
		return $this->opportunities->counts_for( $product_ids, $by_hosts );
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
