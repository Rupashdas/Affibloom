<?php
/**
 * One page of the field library (F1, Task 14C).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Fields;

/**
 * One page of the field library, for the "Add from library" tab.
 */
final class FieldPage {

	/**
	 * Builds the page.
	 *
	 * @param list<Field> $items The fields on this page.
	 * @param int         $total How many fields match.
	 * @param int         $page  This page, from 1.
	 * @param int         $pages How many pages there are.
	 */
	public function __construct(
		public readonly array $items,
		public readonly int $total,
		public readonly int $page,
		public readonly int $pages,
	) {}
}
