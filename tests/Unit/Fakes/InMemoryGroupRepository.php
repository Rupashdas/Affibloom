<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Domain\Group\Group;
use Affibloom\Domain\Group\GroupPage;
use Affibloom\Domain\Group\GroupRepository;
use Affibloom\Domain\Product\SearchTerm;

/**
 * Groups kept in an array, for the Application tests. Names compare without case, as WordPress compares term names.
 */
final class InMemoryGroupRepository implements GroupRepository {
	/** @var array<int, Group> */
	public array $groups = array();
	public int $next     = 1;
	/** What the last page() call was asked: term text, page, per page. */
	public array $asked = array();
	/** @var array<int, list<int>> Group ID => member product IDs. */
	public array $members = array();
	/** @var array<int, list<int>> Group ID => stored order. */
	public array $orders = array();

	public function page( SearchTerm $term, int $page, int $per_page ): GroupPage {
		$this->asked = array( $term->text(), $page, $per_page );
		$found       = array_values( array_reverse( $this->groups ) );

		return new GroupPage( array_slice( $found, ( $page - 1 ) * $per_page, $per_page ), count( $found ), $page, $per_page );
	}

	public function all(): array {
		return array_values( $this->groups );
	}

	public function name_taken( string $name, int $except = 0 ): bool {
		foreach ( $this->groups as $group ) {
			if ( $group->id !== $except && 0 === strcasecmp( $group->name, $name ) ) {
				return true;
			}
		}
		return false;
	}

	public function find_id_by_name( string $name ): ?int {
		foreach ( $this->groups as $group ) {
			if ( 0 === strcasecmp( $group->name, $name ) ) {
				return $group->id;
			}
		}
		return null;
	}

	public function find( int $id ): ?Group {
		return $this->groups[ $id ] ?? null;
	}

	public function create( string $name, string $description ): ?Group {
		$id                  = $this->next++;
		$this->groups[ $id ] = new Group( $id, $name, strtolower( str_replace( ' ', '-', $name ) ), $description, 0 );

		return $this->groups[ $id ];
	}

	public function update( int $id, string $name, string $description ): ?Group {
		$old                 = $this->groups[ $id ];
		$this->groups[ $id ] = new Group( $id, $name, $old->slug, $description, 0 );

		return $this->groups[ $id ];
	}

	public function delete( int $id ): bool {
		unset( $this->groups[ $id ], $this->members[ $id ], $this->orders[ $id ] );

		return true;
	}

	public function member_ids( int $id ): array {
		return $this->members[ $id ] ?? array();
	}

	public function saved_order( int $id ): array {
		return $this->orders[ $id ] ?? array();
	}

	public function save_order( int $id, array $order ): void {
		$this->orders[ $id ] = $order;
	}

	public function groups_of( array $products ): array {
		$by = array_fill_keys( $products, array() );
		foreach ( $this->members as $group => $ids ) {
			foreach ( $ids as $product ) {
				if ( isset( $by[ $product ] ) ) {
					$by[ $product ][] = $this->groups[ $group ];
				}
			}
		}

		return $by;
	}

	public function set_groups( int $product, array $groups ): void {
		foreach ( array_keys( $this->members ) as $group ) {
			$this->members[ $group ] = array_values( array_diff( $this->members[ $group ], array( $product ) ) );
		}
		$this->add_to_groups( array( $product ), $groups );
	}

	public function add_to_groups( array $products, array $groups ): void {
		foreach ( $groups as $group ) {
			foreach ( $products as $product ) {
				if ( ! in_array( $product, $this->members[ $group ] ?? array(), true ) ) {
					$this->members[ $group ][] = $product;
				}
			}
		}
	}

	public function remove_from_group( int $id, array $products ): void {
		$this->members[ $id ] = array_values( array_diff( $this->members[ $id ] ?? array(), $products ) );
	}
}
