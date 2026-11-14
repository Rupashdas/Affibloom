<?php
/**
 * One page of the Fields list with each field's usage (F26 Task FL1).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Fields;

use Affibloom\Domain\Fields\FieldPage;
use Affibloom\Domain\Fields\FieldUsage;

/**
 * One page of the Fields list with each field's usage: what the Fields screen and its in-place list draw.
 */
final class FieldListing {

	/**
	 * Builds the listing.
	 *
	 * @param FieldPage  $page  The fields on this page.
	 * @param FieldUsage $usage How many products have each field.
	 */
	public function __construct(
		public readonly FieldPage $page,
		public readonly FieldUsage $usage,
	) {}
}
