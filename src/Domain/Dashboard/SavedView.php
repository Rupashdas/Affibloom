<?php
/**
 * A saved Dashboard view.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Dashboard;

use Affibloom\Domain\Product\LinkKind;

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
	 * @param string   $id        A unique ID, given by whoever creates the view.
	 * @param string   $name      What the pill says.
	 * @param string   $term      The search text, cleaned; '' for none.
	 * @param LinkKind $link_kind The link-type filter.
	 * @param int      $per_page  Products per page.
	 * @param int      $group     The Group filter: 0 for all groups, -1 for products in no group, else a group's ID (F23 R22).
	 * @param string   $filter    The alert tab (F11 R25; Task 31B): 'broken-links', 'out-of-stock' or ''.
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $name,
		public readonly string $term,
		public readonly LinkKind $link_kind,
		public readonly int $per_page,
		public readonly int $group = 0,
		public readonly string $filter = '',
	) {}

	/**
	 * Whether this view shows the same list as these values, so its pill can be marked as the one in use.
	 *
	 * @param string   $term      The search text, cleaned.
	 * @param LinkKind $link_kind The link-type filter.
	 * @param int      $per_page  Products per page.
	 * @param int      $group     The Group filter.
	 * @param string   $filter    The alert tab.
	 */
	public function shows( string $term, LinkKind $link_kind, int $per_page, int $group = 0, string $filter = '' ): bool {
		return $this->term === $term && $this->link_kind === $link_kind && $this->per_page === $per_page && $this->group === $group && $this->filter === $filter;
	}
}
