<?php
/**
 * The Dashboard list's sort.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * The column the Dashboard is sorted by and how (F19 R1, R2, D8-D10; Task 13K): none (Lasso's most recently changed
 * first), Alert by its issue's date, Locations by its count, Clicks by its 30-day total; `asc` or `desc`. The address
 * carries it as `orderby` and `order`; anything else is the default.
 *
 * A header click moves on Lasso's cycle (R2): Alert starts ascending, then descending, then off (D10: a real off, not
 * Lasso's one-load descending defect); Locations and Clicks start descending and alternate for ever; clicking another
 * column starts that column afresh.
 */
final class ListOrder {

	public const ALERT     = 'alert';
	public const LOCATIONS = 'locations';
	public const CLICKS    = 'clicks';
	/** The Opportunities tab's Alert sort (F22 R51, R52; F19 R5, R9; Task O8): its products by their opportunity count. */
	public const OPPORTUNITY_COUNT = 'opportunity_count';

	public const ASC  = 'asc';
	public const DESC = 'desc';

	/** Each sortable column's first direction (R1's `data-order-init`). */
	private const FIRST = array(
		self::ALERT             => self::ASC,
		self::LOCATIONS         => self::DESC,
		self::CLICKS            => self::DESC,
		self::OPPORTUNITY_COUNT => self::DESC,
	);

	/**
	 * Builds the sort; values outside the lists are the default.
	 *
	 * @param string $column    A sortable column, or '' for the default order.
	 * @param string $direction `asc` or `desc`.
	 */
	public function __construct( string $column = '', string $direction = '' ) {
		$valid           = isset( self::FIRST[ $column ] ) && in_array( $direction, array( self::ASC, self::DESC ), true );
		$this->column    = $valid ? $column : '';
		$this->direction = $valid ? $direction : '';
	}

	/**
	 * The column, or '' for the default order.
	 *
	 * @var string
	 */
	public readonly string $column;

	/**
	 * `asc` or `desc`; '' for the default order.
	 *
	 * @var string
	 */
	public readonly string $direction;

	/**
	 * The sort an address asks for.
	 *
	 * @param string $orderby The `orderby` value.
	 * @param string $order   The `order` value.
	 */
	public static function from_input( string $orderby, string $order ): self {
		return new self( $orderby, $order );
	}

	/**
	 * The sort a tab allows (F19 R5, R10): the opportunity count outside the Opportunities tab is the default order, as
	 * Lasso substitutes Last Updated; every other sort stands.
	 *
	 * @param string $tab The list's alert tab (AlertListing::OPPORTUNITIES or another).
	 */
	public function for_tab( string $tab ): self {
		return self::OPPORTUNITY_COUNT === $this->column && 'opportunities' !== $tab ? new self() : $this;
	}

	/**
	 * Whether the list is in its default order.
	 */
	public function is_default(): bool {
		return '' === $this->column;
	}

	/**
	 * This column's direction, or '' when the list is not sorted by it.
	 *
	 * @param string $column A sortable column.
	 */
	public function direction_of( string $column ): string {
		return $column === $this->column ? $this->direction : '';
	}

	/**
	 * The sort after a click on this column's header (R2, D10).
	 *
	 * @param string $column A sortable column.
	 */
	public function next( string $column ): self {
		$current = $this->direction_of( $column );
		if ( '' === $current ) {
			return new self( $column, self::FIRST[ $column ] ?? '' );
		}
		if ( self::ALERT === $column && self::DESC === $current ) {
			return new self();
		}

		return new self( $column, self::ASC === $current ? self::DESC : self::ASC );
	}
}
