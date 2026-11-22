<?php
/**
 * A saved Dashboard view.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Dashboard;

use Affibloom\Domain\LinkHealth\AlertListing;
use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Product\ListOrder;
use Affibloom\Domain\Product\Placement;
use Affibloom\Domain\Product\TableFilter;

/**
 * A named list on the Dashboard (Lasso's saved view, Dashboard_Saved_Views): a search, the filters and a page size.
 * Lasso's view also keeps the sort and the alert tab; those join this object with the tasks that bring them
 * (Tasks 31, 34, 36).
 */
final class SavedView {

	/** The Group filter's value for "Not in Groups" (Lasso's Dashboard_Saved_Views::GROUP_TERM_NOT_IN). */
	public const GROUP_NOT_IN = -1;

	/**
	 * Builds the view. SavedViews checks the name.
	 *
	 * @param string      $id        A unique ID, given by whoever creates the view.
	 * @param string      $name      What the pill says.
	 * @param string      $term      The search text, cleaned; '' for none.
	 * @param LinkKind    $link_kind The link-type filter.
	 * @param int         $per_page  Products per page.
	 * @param int         $group     The Group filter: 0 for all groups, -1 for products in no group, else a group's ID (F23 R22).
	 * @param string      $filter    The alert tab (F11 R25; Task 31B): 'broken-links', 'out-of-stock' or ''.
	 * @param Placement   $placement The Placements filter (F12 L13, Task 36D; Lasso's `dashboard_placements`).
	 * @param ListOrder   $order     The header sort (F19 R10, D12; Task 13K).
	 * @param TableFilter $tables  The Comparison table filter (F25 R59, Task T16; Lasso's `dashboard_comparison_table`).
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $name,
		public readonly string $term,
		public readonly LinkKind $link_kind,
		public readonly int $per_page,
		public readonly int $group = 0,
		public readonly string $filter = '',
		public readonly Placement $placement = Placement::Any,
		public readonly ListOrder $order = new ListOrder(),
		public readonly TableFilter $tables = TableFilter::Any,
	) {}

	/**
	 * A view from its stored shape, `{ id, name, filters: { s, link_type, per_page, group, filter, placements, orderby,
	 * order, comparison_table } }` (`affibloom_saved_views`); null for a row that is not a view, as Lasso's
	 * normalize_record() skips it. Used by the repository and the extension API (Task 26M).
	 *
	 * @param array<mixed> $row A stored row.
	 */
	public static function from_stored( array $row ): ?self {
		if ( ! isset( $row['id'], $row['name'] ) || ! is_string( $row['id'] ) || ! is_string( $row['name'] ) ) {
			return null;
		}
		$filters = isset( $row['filters'] ) && is_array( $row['filters'] ) ? $row['filters'] : array();
		$text    = static fn ( string $key ): string => isset( $filters[ $key ] ) && is_string( $filters[ $key ] ) ? $filters[ $key ] : '';
		// The alert tab (Task 31B), or the Opportunities tab (F22, Task O8).
		$tab = in_array( $text( 'filter' ), AlertListing::TABS, true ) ? $text( 'filter' ) : '';

		return new self(
			$row['id'],
			$row['name'],
			$text( 's' ),
			LinkKind::from_input( $text( 'link_type' ) ),
			isset( $filters['per_page'] ) && is_numeric( $filters['per_page'] ) ? abs( (int) $filters['per_page'] ) : 0,
			isset( $filters['group'] ) && is_numeric( $filters['group'] ) ? max( -1, (int) $filters['group'] ) : 0,
			$tab,
			Placement::from_input( $text( 'placements' ) ),
			ListOrder::from_input( $text( 'orderby' ), $text( 'order' ) )->for_tab( $tab ),
			TableFilter::from_input( $text( 'comparison_table' ) )
		);
	}

	/**
	 * The view in its stored shape.
	 *
	 * @return array{id: string, name: string, filters: array<string, string|int>}
	 */
	public function to_stored(): array {
		return array(
			'id'      => $this->id,
			'name'    => $this->name,
			'filters' => array(
				's'                => $this->term,
				'link_type'        => $this->link_kind->value,
				'per_page'         => $this->per_page,
				'group'            => $this->group,
				'filter'           => $this->filter,
				'placements'       => $this->placement->value,
				'orderby'          => $this->order->column,
				'order'            => $this->order->direction,
				'comparison_table' => $this->tables->value,
			),
		);
	}

	/**
	 * Whether this view shows the same list as these values, so its pill can be marked as the one in use.
	 *
	 * @param string      $term      The search text, cleaned.
	 * @param LinkKind    $link_kind The link-type filter.
	 * @param int         $per_page  Products per page.
	 * @param int         $group     The Group filter.
	 * @param string      $filter    The alert tab.
	 * @param Placement   $placement The Placements filter.
	 * @param ListOrder   $order     The header sort.
	 * @param TableFilter $tables  The Comparison table filter.
	 */
	public function shows( string $term, LinkKind $link_kind, int $per_page, int $group = 0, string $filter = '', Placement $placement = Placement::Any, ListOrder $order = new ListOrder(), TableFilter $tables = TableFilter::Any ): bool {
		return $this->term === $term && $this->link_kind === $link_kind && $this->per_page === $per_page && $this->group === $group && $this->filter === $filter && $this->placement === $placement && $this->tables === $tables
			&& $this->order->column === $order->column && $this->order->direction === $order->direction;
	}
}
