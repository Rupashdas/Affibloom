<?php
/**
 * A comparison-table cell's value, cleaned for its field's kind.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Products\ProductInput;
use Affibloom\Domain\Fields\Field;
use Affibloom\Domain\Fields\FieldType;
use Affibloom\Domain\Product\PriceHtml;

/**
 * F25 D11 (Tasks T6-T8): values are cleaned on save where Lasso stores them as sent (R30). A rating is a number from 1
 * to 5 with one decimal; Price keeps F1's price allow-list; an image is an address; long text and lists lose their
 * empty lines; every other value keeps the HTML a post may hold (the site's `<span class="medal-icon">` icons).
 */
final class TableCellValue {

	/**
	 * The value to store.
	 *
	 * @param Field $field The cell's field.
	 * @param mixed $value What the builder sent.
	 */
	public static function clean( Field $field, mixed $value ): string {
		$value = is_scalar( $value ) && ! is_bool( $value ) ? trim( (string) $value ) : '';

		return match ( $field->type ) {
			FieldType::Rating   => '' === $value || ! is_numeric( $value ) ? '' : (string) round( min( 5.0, max( 1.0, (float) $value ) ), 1 ),
			FieldType::Number   => sanitize_text_field( $value ),
			FieldType::Image    => esc_url_raw( $value ),
			FieldType::Label    => wp_kses( PriceHtml::normalised( $value ), ProductInput::PRICE_TAGS ),
			FieldType::Textarea, FieldType::BulletedList, FieldType::NumberedList => wp_kses_post( implode( "\n", array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $value ) ) ), static fn( string $line ): bool => '' !== $line ) ) ),
			default             => wp_kses_post( $value ),
		};
	}
}
