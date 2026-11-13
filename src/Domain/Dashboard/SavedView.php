<?php
/**
 * A named list on the Dashboard: a search, the filters and a page size an editor can come back to.
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

	/**
	 * Builds the view. SavedViews checks the name.
	 *
	 * @param string   $id        A unique ID, given by whoever creates the view.
	 * @param string   $name      What the pill says.
	 * @param string   $term      The search text, cleaned; '' for none.
	 * @param LinkKind $link_kind The link-type filter.
	 * @param int      $per_page  Products per page.
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $name,
		public readonly string $term,
		public readonly LinkKind $link_kind,
		public readonly int $per_page,
	) {}

	/**
	 * Whether this view shows the same list as these values, so its pill can be marked as the one in use.
	 *
	 * @param string   $term      The search text, cleaned.
	 * @param LinkKind $link_kind The link-type filter.
	 * @param int      $per_page  Products per page.
	 */
	public function shows( string $term, LinkKind $link_kind, int $per_page ): bool {
		return $this->term === $term && $this->link_kind === $link_kind && $this->per_page === $per_page;
	}
}
