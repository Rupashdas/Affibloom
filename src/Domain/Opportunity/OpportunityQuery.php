<?php
/**
 * What a list of opportunities is asked for.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Opportunity;

/**
 * Link Opportunities' arguments (F22 R7, R9, R10, D6; Task O1): the search (the link or its domain; spaces are
 * wildcards), the sort (an allow-list: URL, Content, Suggestion; none is the index's order, D6), the page and Lasso's
 * "Filter by Link" (one product).
 */
final class OpportunityQuery {

	/** The sortable columns. */
	public const COLUMNS = array( 'url', 'post_title', 'suggestion' );

	/**
	 * The column, or '' for the default order.
	 *
	 * @var string
	 */
	public readonly string $orderby;

	/**
	 * `asc` or `desc`; '' with the default order.
	 *
	 * @var string
	 */
	public readonly string $order;

	/**
	 * Builds the query; a sort outside the allow-list is the default order.
	 *
	 * @param string $search  What the editor typed, trimmed.
	 * @param string $orderby A sortable column, or ''.
	 * @param string $order   `asc` or `desc`.
	 * @param int    $page    The page, from 1.
	 * @param int    $product One product's opportunities only; 0 for all.
	 */
	public function __construct(
		public readonly string $search = '',
		string $orderby = '',
		string $order = '',
		public readonly int $page = 1,
		public readonly int $product = 0,
	) {
		$valid         = in_array( $orderby, self::COLUMNS, true ) && in_array( $order, array( 'asc', 'desc' ), true );
		$this->orderby = $valid ? $orderby : '';
		$this->order   = $valid ? $order : '';
	}

	/**
	 * The search as a LIKE pattern body: spaces become wildcards (Lasso, R7); the caller escapes and wraps it.
	 *
	 * @return list<string> The words, in order.
	 */
	public function words(): array {
		$words = preg_split( '/\s+/', trim( $this->search ) );

		return array_values( array_filter( false === $words ? array() : $words, static fn( string $word ): bool => '' !== $word ) );
	}

	/**
	 * The sort after a click on this column's header (R7): ascending first, then it turns round.
	 *
	 * @param string $column A sortable column.
	 * @return array{0: string, 1: string} The column and its direction.
	 */
	public function next( string $column ): array {
		return array( $column, $column === $this->orderby && 'asc' === $this->order ? 'desc' : 'asc' );
	}
}
