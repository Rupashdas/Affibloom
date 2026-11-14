<?php
/**
 * One page of the field library (F1 Task 14C; F26 Task FL1).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Fields;

/**
 * One page of fields: the product page's "Add from library" tab and the Fields list.
 */
final class FieldPage {

	/**
	 * Builds the page.
	 *
	 * @param list<Field> $items    The fields on this page.
	 * @param int         $total    How many fields match.
	 * @param int         $page     This page, from 1.
	 * @param int         $pages    How many pages there are.
	 * @param int         $per_page Fields a page.
	 */
	public function __construct(
		public readonly array $items,
		public readonly int $total,
		public readonly int $page,
		public readonly int $pages,
		public readonly int $per_page = 10,
	) {}

	/**
	 * The first field's number in the whole list: the "1" in "1-10 of 11"; 0 when nothing matches.
	 */
	public function first(): int {
		return 0 === $this->total ? 0 : ( $this->page - 1 ) * $this->per_page + 1;
	}

	/**
	 * The last field's number in the whole list: the "10" in "1-10 of 11"; 0 when nothing matches.
	 */
	public function last(): int {
		return 0 === $this->total ? 0 : min( $this->page * $this->per_page, $this->total );
	}
}
