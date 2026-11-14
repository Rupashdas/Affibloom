<?php
/**
 * Where the products that have each field are found (F26 Task FL1).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Fields;

/**
 * Where the products that have each field are found: the Fields list's Usage column and, from Task FL3, the rule that a
 * field in use is never deleted. A product's fields are kept on the product itself (`_affibloom_fields`), so this
 * reads across products.
 */
interface FieldUsageRepository {

	/**
	 * How many products have each field, in any status except an unsaved draft (Lasso counts the trash too).
	 */
	public function usage(): FieldUsage;
}
