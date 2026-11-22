<?php
/**
 * What the extension API may do with groups.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Extension;

use Affibloom\Application\Groups\GroupNotFound;
use Affibloom\Application\Groups\GroupService;
use Affibloom\Domain\Group\Group;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\Product;
use InvalidArgumentException;

/**
 * What the extension API may do with groups (Task 26F): read them, create one, change its name and description, and
 * read or set its products in order, through the Groups screens' own rules.
 */
final class GroupAccess {

	/**
	 * Builds the access.
	 *
	 * @param GroupService $groups The Groups screens' rules.
	 */
	public function __construct( private GroupService $groups ) {}

	/**
	 * Every group.
	 *
	 * @return list<array{id: int, name: string, slug: string, description: string}>
	 */
	public function all(): array {
		return array_map(
			static fn ( Group $group ): array => array(
				'id'          => $group->id,
				'name'        => $group->name,
				'slug'        => $group->slug,
				'description' => $group->description,
			),
			$this->groups->all()
		);
	}

	/**
	 * Creates a group.
	 *
	 * @param string $name        Its name (it may not repeat).
	 * @param string $description Its description.
	 * @return int Its ID.
	 * @throws InvalidArgumentException When the name is refused.
	 */
	public function create( string $name, string $description ): int {
		try {
			return $this->groups->create( $name, $description )->id;
		} catch ( InvalidField $refused ) {
			throw new InvalidArgumentException( esc_html( $refused->field . ': ' . $refused->reason ) );
		}
	}

	/**
	 * Changes a group's name and description; its slug is kept.
	 *
	 * @param int    $id          The group.
	 * @param string $name        Its name.
	 * @param string $description Its description.
	 * @throws InvalidArgumentException When there is no such group or the name is refused.
	 */
	public function update( int $id, string $name, string $description ): void {
		try {
			$this->groups->update( $id, $name, $description );
		} catch ( InvalidField $refused ) {
			throw new InvalidArgumentException( esc_html( $refused->field . ': ' . $refused->reason ) );
		} catch ( GroupNotFound ) {
			throw new InvalidArgumentException( esc_html( "No group $id." ) );
		}
	}

	/**
	 * A group's published products, in its order.
	 *
	 * @param int $id The group.
	 * @return list<int>
	 */
	public function members( int $id ): array {
		return array_map( static fn ( Product $product ): int => (int) $product->id, $this->groups->members( $id ) );
	}

	/**
	 * Makes a group's products these published ones, in this order: missing ones added, others taken out.
	 *
	 * @param int       $id       The group.
	 * @param list<int> $products Product IDs, in order.
	 * @throws InvalidArgumentException When there is no such group or a product is not published.
	 */
	public function set_members( int $id, array $products ): void {
		try {
			$current = $this->members( $id );
			$this->groups->remove( $id, array_values( array_diff( $current, $products ) ) );
			foreach ( array_diff( $products, $current ) as $product ) {
				$this->groups->add( $id, $product );
			}
			$this->groups->reorder( $id, $products );
		} catch ( GroupNotFound ) {
			throw new InvalidArgumentException( esc_html( "No group $id, or a product that is not published." ) );
		}
	}
}
