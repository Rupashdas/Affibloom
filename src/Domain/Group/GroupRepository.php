<?php
/**
 * Where groups are kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Group;

use Affibloom\Domain\Product\SearchTerm;

/**
 * Where groups are kept (ADR-001: a taxonomy on the product post type).
 */
interface GroupRepository {

	/**
	 * One page of groups, newest first, with the number of published products in each.
	 *
	 * @param SearchTerm $term     What to look for in the name; an empty term lists every group.
	 * @param int        $page     Page number, from 1.
	 * @param int        $per_page Groups per page.
	 */
	public function page( SearchTerm $term, int $page, int $per_page ): GroupPage;

	/**
	 * Whether a group already has this name, compared as WordPress compares term names.
	 *
	 * @param string $name The name.
	 */
	public function name_taken( string $name ): bool;

	/**
	 * Makes a group. The caller has checked the name.
	 *
	 * @param string $name        The name.
	 * @param string $description The description.
	 * @return Group|null The new group, or null when storage refused it.
	 */
	public function create( string $name, string $description ): ?Group;

	/**
	 * One group by its ID, or null when there is none.
	 *
	 * @param int $id The group's ID.
	 */
	public function find( int $id ): ?Group;
}
