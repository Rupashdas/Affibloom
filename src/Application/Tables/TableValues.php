<?php
/**
 * A product's starting value of a table field.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Tables;

use Affibloom\Domain\Fields\ProductFields;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Table\StartingLayout;
use Closure;

/**
 * Lasso's `link_field_group_detail` (F25 R22; Task T4; class-table-mapping.php 294-340): the product's own value of the
 * field when it has one; else Description, Product name, Product Image and Price from the product, the two buttons as
 * Lasso's `{"button_text", "url"}` JSON; else empty. The values are copies: later product edits do not reach them.
 * The Primary Button keeps the product's own text ('' draws the site's default text) and its public link.
 */
final class TableValues {

	private const DESCRIPTION      = 99989;
	private const SECONDARY_BUTTON = 99993;

	/**
	 * Builds the values.
	 *
	 * @param Closure $public_link The product's public link (F24 R13): fn( Product ): string.
	 */
	public function __construct( private Closure $public_link ) {}

	/**
	 * The starting value.
	 *
	 * @param Product            $product The product.
	 * @param ProductFields|null $own     Its custom fields' values; null for none.
	 * @param int                $field   The field.
	 */
	public function for( Product $product, ?ProductFields $own, int $field ): string {
		$value = null === $own ? '' : (string) $own->find( $field )?->value;
		if ( '' !== $value ) {
			return $value;
		}
		$second = $product->buttons->all()[0] ?? null;

		return match ( $field ) {
			self::DESCRIPTION      => $product->description,
			StartingLayout::PRODUCT_NAME     => $product->name,
			StartingLayout::IMAGE            => $product->image_url,
			StartingLayout::PRICE            => $product->price,
			StartingLayout::PRIMARY_BUTTON   => self::button( $product->button_text, (string) ( $this->public_link )( $product ) ),
			self::SECONDARY_BUTTON => null === $second ? '' : self::button( $second->text, $second->url ),
			default                => '',
		};
	}

	/**
	 * A button cell's value, as Lasso stores it (also a table's 'Edit "<button>"', Task T8).
	 *
	 * @param string $text The button's text.
	 * @param string $url  Its address.
	 */
	public static function button( string $text, string $url ): string {
		return (string) wp_json_encode(
			array(
				'button_text' => $text,
				'url'         => $url,
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);
	}
}
