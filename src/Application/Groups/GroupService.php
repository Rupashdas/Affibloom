<?php
/**
 * Group use cases (F23).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Groups;

use Affibloom\Domain\Group\Group;
use Affibloom\Domain\Group\GroupOrder;
use Affibloom\Domain\Group\GroupPage;
use Affibloom\Domain\Group\GroupRepository;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\SearchTerm;

/**
 * Group use cases: the list, a group's details, its products and their order, and the groups of a product.
 */
final class GroupService {

	/** Lasso's page size for the Groups list (`lasso_get_groups`). */
	public const PER_PAGE = 10;

	/**
	 * Builds the service.
	 *
	 * @param GroupRepository   $groups   Where groups are kept.
	 * @param ProductRepository $products Where products are kept: a group's members are shown as products.
	 */
	public function __construct( private GroupRepository $groups, private ProductRepository $products ) {}

	/**
	 * One page of the list, newest first, searched by name with spaces as wildcards (R2).
	 *
	 * @param SearchTerm $term What was typed in "Search Groups".
	 * @param int        $page Page number, from 1.
	 */
	public function list( SearchTerm $term, int $page ): GroupPage {
		return $this->groups->page( $term, max( 1, $page ), self::PER_PAGE );
	}

	/**
	 * Every group by name, for the picker.
	 *
	 * @return list<Group>
	 */
	public function all(): array {
		return $this->groups->all();
	}

	/**
	 * One group with its published products counted, or null when there is none.
	 *
	 * @param int $id The group's ID.
	 */
	public function get( int $id ): ?Group {
		$group = $id > 0 ? $this->groups->find( $id ) : null;
		if ( null === $group ) {
			return null;
		}

		return new Group( $group->id, $group->name, $group->slug, $group->description, count( $this->groups->member_ids( $id ) ) );
	}

	/**
	 * The Group filter to use for a value that came from an address or a saved view: all groups (0) and "Not in Groups"
	 * (-1) as they are, a group's ID only while the group exists, else all groups (F23 edge case 5).
	 *
	 * @param int $value The asked-for filter.
	 */
	public function choose_filter( int $value ): int {
		if ( $value <= 0 ) {
			return $value < 0 ? -1 : 0;
		}

		return null === $this->groups->find( $value ) ? 0 : $value;
	}

	/**
	 * Makes a group (R7). The name is required and may not repeat (D3); the description may be empty.
	 *
	 * @param string $name        The name as typed; surrounding space is dropped.
	 * @param string $description The description as typed.
	 * @throws InvalidField With "group_name_required" or "group_name_taken" on the field "name".
	 */
	public function create( string $name, string $description ): Group {
		$name = $this->checked_name( $name, 0 );

		$group = $this->groups->create( $name, trim( $description ) );
		if ( null === $group ) {
			// Storage refused a name the check let through (WordPress also compares slugs): the same sentence.
			throw new InvalidField( 'name', 'group_name_taken' );
		}

		return $group;
	}

	/**
	 * Changes a group's name and description; the slug is kept (R7).
	 *
	 * @param int    $id          The group's ID.
	 * @param string $name        The name as typed.
	 * @param string $description The description as typed.
	 * @throws GroupNotFound When there is no such group.
	 * @throws InvalidField  As create().
	 */
	public function update( int $id, string $name, string $description ): Group {
		if ( null === $this->groups->find( $id ) ) {
			throw new GroupNotFound( 'No such group.' );
		}
		$name = $this->checked_name( $name, $id );

		$group = $this->groups->update( $id, $name, trim( $description ) );
		if ( null === $group ) {
			throw new InvalidField( 'name', 'group_name_taken' );
		}

		return $group;
	}

	/**
	 * Deletes a group, never one that has products (R8, D4): the server checks the published products at this moment.
	 *
	 * @param int $id The group's ID.
	 * @throws GroupNotFound When there is no such group.
	 * @throws GroupInUse    When it has published products.
	 */
	public function delete( int $id ): void {
		if ( null === $this->groups->find( $id ) ) {
			throw new GroupNotFound( 'No such group.' );
		}
		if ( array() !== $this->groups->member_ids( $id ) ) {
			throw new GroupInUse( 'The group has products.' );
		}
		$this->groups->delete( $id );
	}

	/**
	 * A group's products in the group's order (D12).
	 *
	 * @param int $id The group's ID.
	 * @return list<Product>
	 */
	public function members( int $id ): array {
		$products = array();
		foreach ( $this->ordered( $id ) as $product_id ) {
			$product = $this->products->get( $product_id );
			if ( null !== $product ) {
				$products[] = $product;
			}
		}

		return $products;
	}

	/**
	 * Whether a product is in a group.
	 *
	 * @param int $id      The group's ID.
	 * @param int $product The product's ID.
	 */
	public function has_member( int $id, int $product ): bool {
		return in_array( $product, $this->groups->member_ids( $id ), true );
	}

	/**
	 * Puts a product in a group, last (R20).
	 *
	 * @param int $id      The group's ID.
	 * @param int $product The product's ID.
	 * @return Product The product, for its row.
	 * @throws GroupNotFound  When there is no such group, or no published product.
	 * @throws AlreadyInGroup When it is already in the group.
	 */
	public function add( int $id, int $product ): Product {
		$found = $this->products->get( $product );
		if ( null === $this->groups->find( $id ) || null === $found ) {
			throw new GroupNotFound( 'No such group or product.' );
		}
		if ( $this->has_member( $id, $product ) ) {
			throw new AlreadyInGroup( 'Link already exists.' );
		}
		$this->groups->add_to_groups( array( $product ), array( $id ) );
		$this->place_last( $id, $product );

		return $found;
	}

	/**
	 * Takes products out of a group; the products stay (R18, D6).
	 *
	 * @param int       $id       The group's ID.
	 * @param list<int> $products Product IDs.
	 * @throws GroupNotFound When there is no such group.
	 */
	public function remove( int $id, array $products ): void {
		if ( null === $this->groups->find( $id ) ) {
			throw new GroupNotFound( 'No such group.' );
		}
		$this->groups->remove_from_group( $id, $products );
		$order = $this->groups->saved_order( $id );
		foreach ( $products as $product ) {
			$order = GroupOrder::without( $order, $product );
		}
		$this->groups->save_order( $id, $order );
	}

	/**
	 * Stores the order the editor made by dragging or with the Move buttons (R19, D7): the members, ranked by the list
	 * sent, so an ID that is not a member is ignored and a member left out comes last.
	 *
	 * @param int       $id  The group's ID.
	 * @param list<int> $ids Product IDs in the new order.
	 * @throws GroupNotFound When there is no such group.
	 */
	public function reorder( int $id, array $ids ): void {
		if ( null === $this->groups->find( $id ) ) {
			throw new GroupNotFound( 'No such group.' );
		}
		$this->groups->save_order( $id, GroupOrder::rank( $this->groups->member_ids( $id ), $ids ) );
	}

	/**
	 * Turns what the picker sent into group IDs (R14): a whole number is a group that exists, a name finds the group of
	 * that name or makes one. A group that does not exist, or a name WordPress refuses, is left out (edge case 4).
	 *
	 * @param list<int|string> $values Group IDs and names.
	 * @return list<int>
	 */
	public function resolve( array $values ): array {
		$ids = array();
		foreach ( $values as $value ) {
			$id = $this->resolve_one( $value );
			if ( null !== $id && ! in_array( $id, $ids, true ) ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * Makes a product's groups exactly these (R14): new ones are joined last, dropped ones leave their order.
	 *
	 * @param int              $product The product's ID.
	 * @param list<int|string> $values  Group IDs and names.
	 */
	public function set_for_product( int $product, array $values ): void {
		$ids    = $this->resolve( $values );
		$before = array_map( static fn( Group $group ): int => $group->id, $this->groups->groups_of( array( $product ) )[ $product ] ?? array() );

		$this->groups->set_groups( $product, $ids );
		foreach ( array_diff( $ids, $before ) as $joined ) {
			$this->place_last( $joined, $product );
		}
		foreach ( array_diff( $before, $ids ) as $left ) {
			$this->groups->save_order( $left, GroupOrder::without( $this->groups->saved_order( $left ), $product ) );
		}
	}

	/**
	 * Adds products to groups and keeps the ones they have (R23).
	 *
	 * @param list<int>        $products Product IDs.
	 * @param list<int|string> $values   Group IDs and names.
	 * @return int How many products were given.
	 */
	public function append( array $products, array $values ): int {
		$ids      = $this->resolve( $values );
		$existing = $this->groups->groups_of( $products );
		$given    = 0;
		foreach ( $products as $product ) {
			if ( null === $this->products->get( $product ) ) {
				continue;
			}
			$before = array_map( static fn( Group $group ): int => $group->id, $existing[ $product ] ?? array() );
			$this->groups->add_to_groups( array( $product ), $ids );
			foreach ( array_diff( $ids, $before ) as $joined ) {
				$this->place_last( $joined, $product );
			}
			++$given;
		}

		return $given;
	}

	/**
	 * The groups of each product on a page, by name, in one query (R16).
	 *
	 * @param list<int> $products Product IDs.
	 * @return array<int, list<Group>>
	 */
	public function for_products( array $products ): array {
		return array() === $products ? array() : $this->groups->groups_of( $products );
	}

	/**
	 * A group's published members in display order.
	 *
	 * @param int $id The group's ID.
	 * @return list<int>
	 */
	private function ordered( int $id ): array {
		return GroupOrder::rank( $this->groups->member_ids( $id ), $this->groups->saved_order( $id ) );
	}

	/**
	 * Stores the order with the product last: the members as they were, then the product.
	 *
	 * @param int $id      The group's ID.
	 * @param int $product The product that has just joined.
	 */
	private function place_last( int $id, int $product ): void {
		$others = array_values( array_diff( $this->groups->member_ids( $id ), array( $product ) ) );
		$this->groups->save_order( $id, GroupOrder::append( GroupOrder::rank( $others, $this->groups->saved_order( $id ) ), $product ) );
	}

	/**
	 * A group's ID for one picker value, or null.
	 *
	 * @param int|string $value An ID or a name.
	 */
	private function resolve_one( int|string $value ): ?int {
		if ( is_int( $value ) || ctype_digit( $value ) ) {
			$found = $this->groups->find( (int) $value );
			if ( null !== $found ) {
				return $found->id;
			}
			if ( is_int( $value ) ) {
				return null;
			}
		}
		$name = trim( (string) $value );
		if ( '' === $name ) {
			return null;
		}
		$id = $this->groups->find_id_by_name( $name );
		if ( null !== $id ) {
			return $id;
		}
		$made = $this->groups->create( $name, '' );

		return null === $made ? null : $made->id;
	}

	/**
	 * The name as stored: trimmed, present and not another group's.
	 *
	 * @param string $name   The name as typed.
	 * @param int    $except The group being renamed; 0 for a new one.
	 * @throws InvalidField With "group_name_required" or "group_name_taken".
	 */
	private function checked_name( string $name, int $except ): string {
		$name = trim( $name );
		if ( '' === $name ) {
			throw new InvalidField( 'name', 'group_name_required' );
		}
		if ( $this->groups->name_taken( $name, $except ) ) {
			throw new InvalidField( 'name', 'group_name_taken' );
		}

		return $name;
	}
}
