<?php
/**
 * Lasso's Product structured data for a box.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Box;

/**
 * The Product JSON-LD of the first box that asks for it (F5 R90-R92; Lasso's Hook::lasso_schema_markup_output and
 * Helper::get_pros_cons_items), as an array for `wp_json_encode`. Lasso's quirks are kept: the Review object carries the
 * author whenever Pro/Con notes are added, and the price drops only "$" before it is read as a number.
 */
final class SchemaBuilder {

	/**
	 * The schema.
	 *
	 * @param string       $name        The product's name.
	 * @param string       $image       Its picture's address.
	 * @param string       $price       Its price as stored (the box's `schemaPrice` wins when given).
	 * @param string       $currency    The price's currency.
	 * @param string       $rating      The Primary Rating's value ('' none).
	 * @param list<string> $pros        The Pros field's lines.
	 * @param list<string> $cons        The Cons field's lines.
	 * @param string       $author      The post author's display name.
	 * @param bool         $review      The box enables Review schema (R91).
	 * @param bool         $pros_cons   The box enables Pro/Con schema (R92).
	 * @return array<string, mixed>
	 */
	public static function build( string $name, string $image, string $price, string $currency, string $rating, array $pros, array $cons, string $author, bool $review, bool $pros_cons ): array {
		$schema = array(
			'@context' => 'http://schema.org/',
			'@type'    => 'Product',
			'name'     => $name,
			'image'    => array(
				'@context' => 'https://schema.org',
				'@type'    => 'ImageObject',
				'url'      => $image,
			),
		);
		$entry  = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Review',
			'author'   => array(
				'@type' => 'Person',
				'name'  => $author,
			),
		);

		if ( $review ) {
			$value = '' === $rating ? 0.0 : (float) $rating;
			if ( (int) number_format( $value, 1, '.', '' ) > 0 ) {
				$entry['reviewRating'] = array(
					'@context'    => 'https://schema.org',
					'@type'       => 'Rating',
					'ratingValue' => number_format( $value, 1, '.', '' ),
				);
			}
			$schema['offers'] = array(
				'@type'         => 'Offer',
				'price'         => number_format( (float) str_replace( '$', '', $price ), 2, '.', '' ),
				'priceCurrency' => $currency,
				'availability'  => 'https://schema.org/InStock',
			);
			$schema['review'] = $entry;
		}

		if ( $pros_cons ) {
			foreach ( array(
				'positiveNotes' => $pros,
				'negativeNotes' => $cons,
			) as $key => $lines ) {
				$items = self::items( $lines );
				if ( array() !== $items ) {
					$entry[ $key ] = array(
						'@context'        => 'https://schema.org',
						'@type'           => 'ItemList',
						'numberOfItems'   => count( $items ),
						'itemListElement' => $items,
					);
				}
			}
			$schema['review'] = $entry;
		}

		return $schema;
	}

	/**
	 * The ListItems of one list (Lasso's get_pros_cons_items), empty lines left out.
	 *
	 * @param list<string> $lines The field's lines.
	 * @return list<array<string, mixed>>
	 */
	private static function items( array $lines ): array {
		$items = array();
		foreach ( $lines as $line ) {
			if ( '' === $line ) {
				continue;
			}
			$items[] = array(
				'@context' => 'https://schema.org',
				'@type'    => 'ListItem',
				'position' => count( $items ) + 1,
				'name'     => $line,
			);
		}

		return $items;
	}
}
