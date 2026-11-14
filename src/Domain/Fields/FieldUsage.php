<?php
/**
 * How many products have each field (F26 Task FL1).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Fields;

/**
 * How many products have each field: Lasso's Usage column (`count(distinct posts)` through the field mapping, any
 * status, trash included; F26 R9).
 */
final class FieldUsage {

	/**
	 * Builds the counts.
	 *
	 * @param array<int, int> $counts Products by field ID; a field no product has is absent.
	 */
	public function __construct( private array $counts ) {}

	/**
	 * How many products have this field.
	 *
	 * @param int $field The field's ID.
	 */
	public function of( int $field ): int {
		return $this->counts[ $field ] ?? 0;
	}
}
