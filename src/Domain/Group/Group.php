<?php
/**
 * A group of products: Lasso's link group (F23).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Group;

/**
 * A group of products: Lasso's link group (F23, requirements R1). A name, a slug WordPress makes from it, a free-text
 * description and the number of published products in it.
 */
final class Group {

	/** Lasso's cut for the list's Description column (`Lasso_DB::get_groups_query`). */
	public const LIST_DESCRIPTION_LENGTH = 237;

	/**
	 * Builds the group.
	 *
	 * @param int    $id          The group's ID.
	 * @param string $name        Its name.
	 * @param string $slug        Its slug; never changed after the group is made.
	 * @param string $description Its description, empty when it has none.
	 * @param int    $count       Published products in it.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $name,
		public readonly string $slug,
		public readonly string $description,
		public readonly int $count,
	) {}

	/**
	 * The description as the list shows it: the first 237 characters and "...", as Lasso cuts it. Lasso counts bytes
	 * (MySQL's LENGTH) and so can cut a multibyte character in half; characters are counted here.
	 */
	public function list_description(): string {
		if ( mb_strlen( $this->description ) <= self::LIST_DESCRIPTION_LENGTH ) {
			return $this->description;
		}

		return mb_substr( $this->description, 0, self::LIST_DESCRIPTION_LENGTH ) . '...';
	}
}
