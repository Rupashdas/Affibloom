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
	 * Every group by name, without counts: the picker's choices.
	 *
	 * @return list<Group>
	 */
	public function all(): array;

	/**
	 * Whether a group other than this one already has this name, compared as WordPress compares term names.
	 *
	 * @param string $name   The name.
	 * @param int    $except A group to leave out (the one being renamed); 0 for none.
	 */
	public function name_taken( string $name, int $except = 0 ): bool;

	/**
	 * The ID of the group with this exact name, or null.
	 *
	 * @param string $name The name.
	 */
	public function find_id_by_name( string $name ): ?int;

	/**
	 * The ID of the group with this slug, or null (a group display's `group`, F30 D1).
	 *
	 * @param string $slug The slug.
	 */
	public function find_id_by_slug( string $slug ): ?int;

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

	/**
	 * Changes a group's name and description; its slug stays (R7).
	 *
	 * @param int    $id          The group's ID.
	 * @param string $name        The name.
	 * @param string $description The description.
	 * @return Group|null The group as stored, or null when storage refused it.
	 */
	public function update( int $id, string $name, string $description ): ?Group;

	/**
	 * Deletes a group; its products stay.
	 *
	 * @param int $id The group's ID.
	 * @return bool False when storage refused.
	 */
	public function delete( int $id ): bool;

	/**
	 * The published products in a group.
	 *
	 * @param int $id The group's ID.
	 * @return list<int> Product IDs, in no particular order.
	 */
	public function member_ids( int $id ): array;

	/**
	 * The stored order of a group (GroupOrder ranks the members by it).
	 *
	 * @param int $id The group's ID.
	 * @return list<int>
	 */
	public function saved_order( int $id ): array;

	/**
	 * Stores a group's order.
	 *
	 * @param int       $id    The group's ID.
	 * @param list<int> $order Product IDs in display order.
	 */
	public function save_order( int $id, array $order ): void;

	/**
	 * The groups of each product, by name.
	 *
	 * @param list<int> $products Product IDs.
	 * @return array<int, list<Group>> Product ID => its groups (every ID is a key).
	 */
	public function groups_of( array $products ): array;

	/**
	 * Makes a product's groups exactly these.
	 *
	 * @param int       $product The product.
	 * @param list<int> $groups  Group IDs.
	 */
	public function set_groups( int $product, array $groups ): void;

	/**
	 * Puts products into groups and leaves the groups they have.
	 *
	 * @param list<int> $products Product IDs.
	 * @param list<int> $groups   Group IDs.
	 */
	public function add_to_groups( array $products, array $groups ): void;

	/**
	 * Takes products out of one group; the products stay.
	 *
	 * @param int       $id       The group's ID.
	 * @param list<int> $products Product IDs.
	 */
	public function remove_from_group( int $id, array $products ): void;
}
