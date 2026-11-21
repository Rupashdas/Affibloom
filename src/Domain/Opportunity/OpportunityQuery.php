<?php
/**
 * What a list of opportunities is asked for.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Opportunity;

/**
 * An Opportunities list's arguments (F22 R7, R9, R10, R17, R20, R26, D6; Tasks O1-O3): which list, the search (spaces
 * are wildcards), the sort (each list's allow-list; none is the list's default order), the page and Lasso's "Filter by
 * Link" (one product).
 */
final class OpportunityQuery {

	public const LINKS        = 'links';
	public const DOMAINS      = 'domains';
	public const DOMAIN_LINKS = 'domain';
	public const CONTENT      = 'content';

	/** Each list's sortable columns, with the direction a header click starts with (R7: asc unless Lasso's init). */
	public const COLUMNS = array(
		self::LINKS        => array(
			'url'        => 'asc',
			'post_title' => 'asc',
			'suggestion' => 'asc',
		),
		self::DOMAINS      => array(
			'domain' => 'asc',
			'count'  => 'desc',
		),
		self::DOMAIN_LINKS => array(
			'url'        => 'asc',
			'post_title' => 'asc',
		),
		self::CONTENT      => array(
			'post_modified'   => 'asc',
			'detection_title' => 'asc',
			'detection_slug'  => 'asc',
			'count'           => 'desc',
		),
	);

	/**
	 * The column, or '' for the list's default order.
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
	 * Builds the query; a sort outside the list's allow-list is its default order.
	 *
	 * @param string $search  What the editor typed, trimmed.
	 * @param string $orderby A sortable column, or ''.
	 * @param string $order   `asc` or `desc`.
	 * @param int    $page    The page, from 1.
	 * @param int    $product One product's opportunities only; 0 for all (Link Opportunities).
	 * @param string $kind    Which list: LINKS, DOMAINS, DOMAIN_LINKS or CONTENT.
	 */
	public function __construct(
		public readonly string $search = '',
		string $orderby = '',
		string $order = '',
		public readonly int $page = 1,
		public readonly int $product = 0,
		public readonly string $kind = self::LINKS,
	) {
		$valid         = isset( self::COLUMNS[ $kind ][ $orderby ] ) && in_array( $order, array( 'asc', 'desc' ), true );
		$this->orderby = $valid ? $orderby : '';
		$this->order   = $valid ? $order : '';
	}

	/**
	 * The search's words: spaces become wildcards (Lasso, R7); the caller escapes and joins them.
	 *
	 * @return list<string> The words, in order.
	 */
	public function words(): array {
		$words = preg_split( '/\s+/', trim( $this->search ) );

		return array_values( array_filter( false === $words ? array() : $words, static fn( string $word ): bool => '' !== $word ) );
	}

	/**
	 * The sort after a click on this column's header (R7): the column's first direction, then it turns round.
	 *
	 * @param string $column A sortable column of this list.
	 * @return array{0: string, 1: string} The column and its direction.
	 */
	public function next( string $column ): array {
		if ( $column !== $this->orderby ) {
			return array( $column, self::COLUMNS[ $this->kind ][ $column ] ?? 'asc' );
		}

		return array( $column, 'asc' === $this->order ? 'desc' : 'asc' );
	}
}
