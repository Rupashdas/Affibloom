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

	public function page( SearchTerm $term, int $page, int $per_page ): GroupPage {
		$this->asked = array( $term->text(), $page, $per_page );
		$found       = array_values( array_reverse( $this->groups ) );

		return new GroupPage( array_slice( $found, ( $page - 1 ) * $per_page, $per_page ), count( $found ), $page, $per_page );
	}

	public function name_taken( string $name ): bool {
		foreach ( $this->groups as $group ) {
			if ( 0 === strcasecmp( $group->name, $name ) ) {
				return true;
			}
		}
		return false;
	}

	public function find( int $id ): ?Group {
		return $this->groups[ $id ] ?? null;
	}

	public function create( string $name, string $description ): ?Group {
		$id                  = $this->next++;
		$this->groups[ $id ] = new Group( $id, $name, strtolower( str_replace( ' ', '-', $name ) ), $description, 0 );

		return $this->groups[ $id ];
	}
}
