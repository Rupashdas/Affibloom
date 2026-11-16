<?php
/**
 * The Primary Rating's stars in a box.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Box;

/**
 * The stars of a box (F5 R14, F8): the rating as stored (the `--rating` property), its value with one decimal and the
 * field's name when its row shows the name.
 */
final class BoxStars {

	/**
	 * Builds the stars.
	 *
	 * @param string $rating The rating as stored, for `--rating`.
	 * @param string $value  The value printed, one decimal (Lasso's show_decimal_field_rate).
	 * @param string $label  The field's name; '' when the row does not show it.
	 */
	public function __construct(
		public readonly string $rating,
		public readonly string $value,
		public readonly string $label,
	) {}
}
