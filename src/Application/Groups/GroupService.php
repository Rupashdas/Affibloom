<?php
/**
 * Group use cases (F23).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Groups;

use Affibloom\Domain\Group\Group;
use Affibloom\Domain\Group\GroupPage;
use Affibloom\Domain\Group\GroupRepository;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\SearchTerm;

/**
 * Group use cases: the list and a new group (Task G1); the rest of F23 joins it task by task.
 */
final class GroupService {

	/** Lasso's page size for the Groups list (`lasso_get_groups`). */
	public const PER_PAGE = 10;

	/**
	 * Builds the service.
	 *
	 * @param GroupRepository $groups Where groups are kept.
	 */
	public function __construct( private GroupRepository $groups ) {}

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
	 * One group, or null when there is none: the screen sends an unknown ID to the list (analysis, request paths).
	 *
	 * @param int $id The group's ID.
	 */
	public function get( int $id ): ?Group {
		return $id > 0 ? $this->groups->find( $id ) : null;
	}

	/**
	 * Makes a group (R7). The name is required and may not repeat (D3); the description may be empty.
	 *
	 * @param string $name        The name as typed; surrounding space is dropped.
	 * @param string $description The description as typed.
	 * @throws InvalidField With "group_name_required" or "group_name_taken" on the field "name".
	 */
	public function create( string $name, string $description ): Group {
		$name = trim( $name );
		if ( '' === $name ) {
			throw new InvalidField( 'name', 'group_name_required' );
		}
		if ( $this->groups->name_taken( $name ) ) {
			throw new InvalidField( 'name', 'group_name_taken' );
		}

		$group = $this->groups->create( $name, trim( $description ) );
		if ( null === $group ) {
			// Storage refused a name the check let through (WordPress also compares slugs): the same sentence.
			throw new InvalidField( 'name', 'group_name_taken' );
		}

		return $group;
	}
}
