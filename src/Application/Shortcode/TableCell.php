<?php
/**
 * One field of one product in a reader's comparison table.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Shortcode;

use Affibloom\Application\Box\BoxView;
use Affibloom\Domain\Fields\Field;
use Affibloom\Domain\Fields\FieldType;
use Affibloom\Domain\Product\Product;

/**
 * A table field as `templates/front/partials/table-field.php` prints it (F25 R48-R50; Task T12): the field, the cell's
 * value, the product and its Single box view (the image and title links, the buttons, the live price and Prime, R50:
 * the box's own link attributes), whether its name label shows (R49), and whether the name carries the Prime logo
 * (a table without a Price field).
 */
final class TableCell {

	/** The Primary Rating, which shows no name label while the header row names it. */
	public const PRIMARY_RATING = 1;

	/** The Price field. */
	public const PRICE = 99994;

	/**
	 * Builds the cell.
	 *
	 * @param Field   $field         The field.
	 * @param string  $value         The table's value for this product.
	 * @param Product $product       The product.
	 * @param BoxView $box           The product's Single box view.
	 * @param bool    $label         The field's name label shows.
	 * @param bool    $prime_in_name The product name carries the Prime logo.
	 */
	public function __construct(
		public readonly Field $field,
		public readonly string $value,
		public readonly Product $product,
		public readonly BoxView $box,
		public readonly bool $label,
		public readonly bool $prime_in_name,
	) {}

	/**
	 * Whether a field's name label shows (R48, R49): a rating's when it is not the Primary Rating and the header row is
	 * off, or with Show Field Name; a price's in a Vertical table only; the image, the name and the buttons never; any
	 * other field's with Show Field Name.
	 *
	 * @param Field $field           The field.
	 * @param bool  $horizontal      The Horizontal layout.
	 * @param bool  $show_headers    Show Headers.
	 * @param bool  $show_field_name Show Field Name.
	 */
	public static function labelled( Field $field, bool $horizontal, bool $show_headers, bool $show_field_name ): bool {
		return match ( true ) {
			FieldType::Rating === $field->type => ( self::PRIMARY_RATING !== $field->id && ! $show_headers ) || $show_field_name,
			self::PRICE === $field->id => ! $horizontal,
			in_array( $field->type, array( FieldType::Image, FieldType::ProductName, FieldType::Button ), true ) => false,
			default => $show_field_name,
		};
	}
}
