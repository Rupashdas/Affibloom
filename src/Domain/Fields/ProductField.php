<?php
/**
 * A custom field on one product (F1, Task 14C).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Fields;

/**
 * A custom field on one product: Lasso's lasso_field_mapping row (value, field_visible) and, for Primary Rating, its
 * "show field name" metadata.
 */
final class ProductField {

	/**
	 * Builds a field row.
	 *
	 * @param int    $field_id  The field's ID.
	 * @param string $value     The value, as typed (several lines for long text and lists).
	 * @param bool   $visible   Shown in the box ("Show this field in your display by default").
	 * @param bool   $show_name Its name shown in the box (Primary Rating only, as in Lasso).
	 */
	public function __construct(
		public readonly int $field_id,
		public readonly string $value,
		public readonly bool $visible = true,
		public readonly bool $show_name = false,
	) {}
}
