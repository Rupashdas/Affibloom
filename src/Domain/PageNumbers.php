<?php
/**
 * A list page's numbers.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain;

/**
 * The page arithmetic of a paged list (one page for an empty list; a page past the end is the last page), for a class
 * with `$total`, `$page` and `$per_page`. Taken out of LocationPage on its second use (OpportunityPage, Task O1).
 */
trait PageNumbers {

	/**
	 * Number of pages; 1 when there is nothing to show.
	 */
	public function pages(): int {
		return max( 1, (int) ceil( $this->total / max( 1, $this->per_page ) ) );
	}

	/**
	 * This page's number, never past the last page and never below 1.
	 */
	public function current(): int {
		return max( 1, min( $this->page, $this->pages() ) );
	}

	/**
	 * The first item's number in the whole list ("1" in "1-10 of 11"); 0 when empty.
	 */
	public function first(): int {
		return 0 === $this->total ? 0 : ( $this->current() - 1 ) * $this->per_page + 1;
	}

	/**
	 * The last item's number in the whole list ("10" in "1-10 of 11"); 0 when empty.
	 */
	public function last(): int {
		return 0 === $this->total ? 0 : min( $this->current() * $this->per_page, $this->total );
	}

	/**
	 * Where the page starts in the whole list, for the query.
	 */
	public function offset(): int {
		return ( $this->current() - 1 ) * $this->per_page;
	}
}
