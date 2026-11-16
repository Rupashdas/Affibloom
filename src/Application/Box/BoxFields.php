<?php
/**
 * A product's custom fields as a box reads them.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Box;

use Affibloom\Domain\Fields\FieldLibrary;
use Affibloom\Domain\Fields\ProductFields;

/**
 * The product's field rows and the library that names and types them (F5 R14, R23, R30-R36): the stars (21A) and the
 * fields block (21B) read both.
 */
final class BoxFields {

	/**
	 * Builds the pair.
	 *
	 * @param ProductFields $values  The product's rows (`_affibloom_fields`).
	 * @param FieldLibrary  $library Every field.
	 */
	public function __construct(
		public readonly ProductFields $values,
		public readonly FieldLibrary $library,
	) {}
}
