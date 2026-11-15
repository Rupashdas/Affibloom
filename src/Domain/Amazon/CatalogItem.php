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
 * large, medium and small images, the listing's display price and availability (the buy-box winner's, else the first:
 * C1Q2), the features and the detail page; since C1Q2 also everything else the item says (details, offer).
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
	 * @param ItemDetails  $details         What the item is: maker, model, codes, category, images (C1Q2).
	 * @param OfferDetails $offer           The listing read: prices as numbers, seller, condition, deal (C1Q2).
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
		public readonly string $url,
		public readonly ItemDetails $details = new ItemDetails(),
		public readonly OfferDetails $offer = new OfferDetails()
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

		$listing = self::listing( $item );
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
			self::text( $item, 'detailPageURL' ),
			self::details( $item ),
			self::offer( $listing )
		);
	}

	/**
	 * The listing to read: Amazon's buy-box winner, else the first (C1Q2; Lasso reads the first, R11).
	 *
	 * @param array<array-key, mixed> $item The item.
	 * @return array<array-key, mixed>
	 */
	private static function listing( array $item ): array {
		$listings = array_values( array_filter( self::at( $item, 'offersV2', 'listings' ), 'is_array' ) );
		foreach ( $listings as $listing ) {
			if ( true === ( $listing['isBuyBoxWinner'] ?? false ) ) {
				return $listing;
			}
		}

		return $listings[0] ?? array();
	}

	/**
	 * What the item is (C1Q2).
	 *
	 * @param array<array-key, mixed> $item The item.
	 */
	private static function details( array $item ): ItemDetails {
		$info       = self::at( $item, 'itemInfo' );
		$dimensions = array();
		foreach ( self::at( $info, 'productInfo', 'itemDimensions' ) as $name => $dimension ) {
			$value = is_array( $dimension ) ? ( $dimension['displayValue'] ?? null ) : null;
			if ( is_string( $name ) && is_array( $dimension ) && ( is_int( $value ) || is_float( $value ) || is_string( $value ) ) ) {
				$dimensions[ $name ] = trim( $value . ' ' . self::text( $dimension, 'unit' ) );
			}
		}

		// The category is the first browse node Amazon ranks the item in, else the first node.
		$nodes    = array_values( array_filter( self::at( $item, 'browseNodeInfo', 'browseNodes' ), 'is_array' ) );
		$category = $nodes[0] ?? array();
		foreach ( $nodes as $node ) {
			if ( null !== self::integer( $node, 'salesRank' ) ) {
				$category = $node;
				break;
			}
		}

		$images = array( self::text( $item, 'images', 'primary', 'large', 'url' ) );
		foreach ( self::at( $item, 'images', 'variants' ) as $variant ) {
			$images[] = is_array( $variant ) ? self::text( $variant, 'large', 'url' ) : '';
		}

		return new ItemDetails(
			brand: self::text( $info, 'byLineInfo', 'brand', 'displayValue' ),
			manufacturer: self::text( $info, 'byLineInfo', 'manufacturer', 'displayValue' ),
			model: self::text( $info, 'manufactureInfo', 'model', 'displayValue' ),
			part_number: self::text( $info, 'manufactureInfo', 'itemPartNumber', 'displayValue' ),
			warranty: self::text( $info, 'manufactureInfo', 'warranty', 'displayValue' ),
			color: self::text( $info, 'productInfo', 'color', 'displayValue' ),
			size: self::text( $info, 'productInfo', 'size', 'displayValue' ),
			unit_count: self::integer( self::at( $info, 'productInfo', 'unitCount' ), 'displayValue' ),
			dimensions: $dimensions,
			product_group: self::text( $info, 'classifications', 'productGroup', 'displayValue' ),
			eans: self::texts( $info, 'externalIds', 'eans', 'displayValues' ),
			upcs: self::texts( $info, 'externalIds', 'upcs', 'displayValues' ),
			isbns: self::texts( $info, 'externalIds', 'isbns', 'displayValues' ),
			category: self::text( $category, 'displayName' ),
			category_rank: self::integer( $category, 'salesRank' ),
			sales_rank: self::integer( self::at( $item, 'browseNodeInfo', 'websiteSalesRank' ), 'salesRank' ),
			parent_asin: self::text( $item, 'parentASIN' ),
			images: array_values( array_unique( array_filter( $images, static fn( string $url ): bool => '' !== $url ) ) ),
		);
	}

	/**
	 * The listing's numbers, seller, condition and deal (C1Q2).
	 *
	 * @param array<array-key, mixed> $listing The listing read.
	 */
	private static function offer( array $listing ): OfferDetails {
		$price = self::at( $listing, 'price' );

		return new OfferDetails(
			price_amount: self::number( self::at( $price, 'money' ), 'amount' ),
			basis_amount: self::number( self::at( $price, 'savingBasis', 'money' ), 'amount' ),
			basis_type: self::text( $price, 'savingBasis', 'savingBasisType' ),
			savings_amount: self::number( self::at( $price, 'savings', 'money' ), 'amount' ),
			seller: self::text( $listing, 'merchantInfo', 'name' ),
			condition: self::text( $listing, 'condition', 'value' ),
			buy_box: true === ( $listing['isBuyBoxWinner'] ?? false ),
			deal_badge: self::text( $listing, 'dealDetails', 'badge' ),
			deal_ends: self::text( $listing, 'dealDetails', 'endTime' ),
			deal_access: self::text( $listing, 'dealDetails', 'accessType' ),
		);
	}

	/**
	 * The number under a key, or null.
	 *
	 * @param array<array-key, mixed> $data A decoded group.
	 * @param string                  $key  The key.
	 */
	private static function number( array $data, string $key ): ?float {
		$value = $data[ $key ] ?? null;

		return is_int( $value ) || is_float( $value ) || ( is_string( $value ) && is_numeric( $value ) ) ? (float) $value : null;
	}

	/**
	 * The whole number under a key, or null.
	 *
	 * @param array<array-key, mixed> $data A decoded group.
	 * @param string                  $key  The key.
	 */
	private static function integer( array $data, string $key ): ?int {
		$value = self::number( $data, $key );

		return null === $value ? null : (int) $value;
	}

	/**
	 * The texts in a list at a path.
	 *
	 * @param array<array-key, mixed> $data A decoded answer.
	 * @param string                  ...$path Keys to the list.
	 * @return list<string>
	 */
	private static function texts( array $data, string ...$path ): array {
		return array_values( array_map( 'trim', array_filter( self::at( $data, ...$path ), 'is_string' ) ) );
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
