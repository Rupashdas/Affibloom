<?php
/**
 * One Amazon item as the Creators API describes it, read into the fields Affibloom uses.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * One Amazon item from getItems, read as Lasso's normalize_item() reads it (C1 R11, R12): the title, the first of the
 * large, medium and small images, the first listing's display price and availability, the features and the detail page.
 * The savings and their basis are read from the same listing price; Lasso's Creators client does not read them (its
 * discounts came from its cloud, which Affibloom does not use), and their shape follows Amazon's OffersV2 price
 * (`savings.money`, `savings.percentage`, `savingBasis.money`), not verified against a live answer yet.
 */
final class CatalogItem {

	/**
	 * Builds the item.
	 *
	 * @param string       $asin            The item's ASIN.
	 * @param string       $title           Its title, or ''.
	 * @param string       $image           Its image link, or ''.
	 * @param string       $price           Its display price ("$69.95"), or ''.
	 * @param string       $currency        The price's ISO currency ("USD"), or ''.
	 * @param string       $savings         The display saving ("$10.04"), or ''.
	 * @param int          $savings_percent The saving in per cent, or 0.
	 * @param string       $basis           The display price the saving is from ("$79.99"), or ''.
	 * @param Availability $availability    Whether it can be bought now.
	 * @param list<string> $features        Its feature lines.
	 * @param string       $url             Its detail page, or ''.
	 */
	public function __construct(
		public readonly string $asin,
		public readonly string $title,
		public readonly string $image,
		public readonly string $price,
		public readonly string $currency,
		public readonly string $savings,
		public readonly int $savings_percent,
		public readonly string $basis,
		public readonly Availability $availability,
		public readonly array $features,
		public readonly string $url
	) {}

	/**
	 * Reads an item of getItems' `itemsResult.items`.
	 *
	 * @param array<array-key, mixed> $item The item, decoded as arrays.
	 */
	public static function from_creators( array $item ): self {
		$image = '';
		foreach ( array( 'large', 'medium', 'small' ) as $size ) {
			$image = self::text( $item, 'images', 'primary', $size, 'url' );
			if ( '' !== $image ) {
				break;
			}
		}

		$listing = self::at( $item, 'offersV2', 'listings', 0 );
		$price   = self::at( $listing, 'price' );
		// OffersV2 keeps the price in price.money; Lasso also accepts a bare price.displayAmount.
		$display = self::text( $price, 'money', 'displayAmount' );
		$display = '' === $display ? self::text( $price, 'displayAmount' ) : $display;

		$percent = self::at( $price, 'savings' )['percentage'] ?? 0;

		$features = array_values( array_filter( self::at( $item, 'itemInfo', 'features', 'displayValues' ), 'is_string' ) );

		return new self(
			self::text( $item, 'asin' ),
			self::text( $item, 'itemInfo', 'title', 'displayValue' ),
			$image,
			$display,
			self::text( $price, 'money', 'currency' ),
			self::text( $price, 'savings', 'money', 'displayAmount' ),
			is_numeric( $percent ) ? (int) $percent : 0,
			self::text( $price, 'savingBasis', 'money', 'displayAmount' ),
			array() === $listing ? Availability::none() : Availability::from_listing( self::text( $listing, 'availability', 'type' ), self::text( $listing, 'availability', 'message' ) ),
			$features,
			self::text( $item, 'detailPageURL' )
		);
	}

	/**
	 * The array at a path, or an empty one.
	 *
	 * @param array<array-key, mixed> $data A decoded answer.
	 * @param string|int              ...$path Keys.
	 * @return array<array-key, mixed>
	 */
	private static function at( array $data, string|int ...$path ): array {
		foreach ( $path as $key ) {
			if ( ! isset( $data[ $key ] ) || ! is_array( $data[ $key ] ) ) {
				return array();
			}
			$data = $data[ $key ];
		}

		return $data;
	}

	/**
	 * The text at a path, or ''.
	 *
	 * @param array<array-key, mixed> $data A decoded answer.
	 * @param string                  ...$path Keys; the last one holds the text.
	 */
	private static function text( array $data, string ...$path ): string {
		$last  = array_pop( $path );
		$value = self::at( $data, ...$path )[ $last ] ?? '';

		return is_string( $value ) ? trim( $value ) : '';
	}
}
