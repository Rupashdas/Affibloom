<?php
/**
 * Turns a Lasso record into an Affibloom product, by Lasso's own reading rules.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Migration;

use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\Display\Theme;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Migration\LassoRecord;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Button;
use Affibloom\Domain\Product\Buttons;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;

/**
 * Lasso's record → an unsaved Affibloom product, field by field, as Lasso_Affiliate_Link::get_lasso_url() and
 * get_lasso_thumbnail() read the record to show its box (the mapping table of Phase 5, Task 25). Used by the one-time
 * staging import; Tasks 25-26 build the full migration with its dry run and report on the same rules.
 */
final class LassoRecordMapper {

	/** Lasso's "no thumbnail" image (LASSO_DEFAULT_THUMBNAIL), which is not the product's own. */
	private const PLACEHOLDER_IMAGE = 'lasso-no-thumbnail';

	/**
	 * The product, or null when the record has no link at all (Task 4 found 9 such records: a title and nothing else).
	 *
	 * @param LassoRecord $record The record.
	 * @throws InvalidField When the record's link is not a link.
	 */
	public function map( LassoRecord $record ): ?Product {
		$raw = $this->link( $record );
		if ( '' === $raw ) {
			return null;
		}

		$url    = AffiliateUrl::from_string( $raw );
		$amazon = AmazonUrl::from( $url );
		$on     = static fn( string $key ): bool => 1 === (int) $record->meta( $key );
		// Lasso's Amazon product: an Amazon link whose url_details row names the product; else the ASIN in the link. The
		// store is kept only with an ASIN, as the repository stores them.
		$asin    = '';
		$country = '';
		if ( null !== $amazon ) {
			$asin = 'amazon' === $record->product_type && '' !== $record->product_id ? $record->product_id : $amazon->asin();
			if ( '' !== $asin ) {
				$country = '' !== $record->country ? $record->country : $amazon->country();
			}
		}
		$cached = null !== $amazon && null !== $record->amazon;
		$image  = $this->image( $record, $cached );

		return new Product(
			id: null,
			name: $this->name( $record, $url, $amazon ),
			url: $url,
			type: null === $amazon ? ProductType::Other : ProductType::Amazon,
			new_tab: $on( 'open_new_tab' ),
			nofollow: $on( 'enable_nofollow' ),
			sponsored: $on( 'enable_sponsored' ),
			show_disclosure: $on( 'show_disclosure' ),
			asin: $asin,
			amazon_country: $country,
			search_key: null === $amazon ? '' : $amazon->search_key(),
			image_url: $image[0],
			button_text: $record->meta( 'buy_btn_text' ),
			buttons: new Buttons(
				array(
					new Button( trim( $record->meta( 'second_btn_url' ) ), $record->meta( 'second_btn_text' ), $on( 'open_new_tab2' ), $on( 'enable_nofollow2' ) ),
				)
			),
			theme: '' === $record->meta( 'custom_theme' ) ? null : Theme::from( $record->meta( 'custom_theme' ) ),
			badge: $record->meta( 'badge_text' ),
			// An Amazon product's box shows Amazon's last price, not the meta (get_lasso_url: $price = $amazon_product['price']).
			price: $cached ? $record->amazon( 'latest_price' ) : $record->meta( 'price' ),
			show_price: '' === $record->meta( 'show_price' ) ? null : $on( 'show_price' ),
			description: $this->description( $record ),
			disclosure: $record->meta( 'disclosure_text' ),
			prime: $cached && 1 === (int) $record->amazon( 'is_prime' ),
			image_id: $image[1],
		);
	}

	/**
	 * The destination: the cached Amazon product's monetized link (or its base link), else url_details' redirect,
	 * then the custom redirect, then the final URL.
	 *
	 * @param LassoRecord $record The record.
	 */
	private function link( LassoRecord $record ): string {
		$candidates = array(
			$record->amazon( 'monetized_url' ),
			$record->amazon( 'base_url' ),
			$record->redirect_url,
			$record->meta( 'lasso_custom_redirect' ),
			$record->meta( 'lasso_final_url' ),
		);
		foreach ( $candidates as $candidate ) {
			if ( '' !== trim( $candidate ) ) {
				return trim( $candidate );
			}
		}

		return '';
	}

	/**
	 * The title with HTML entities decoded. A record titled just "Amazon" (Lasso's name before it fetched the product)
	 * takes the cached product's name; an empty title takes the name the product page would start with.
	 *
	 * @param LassoRecord    $record The record.
	 * @param AffiliateUrl   $url    Its link.
	 * @param AmazonUrl|null $amazon Its link as Amazon's, or null.
	 */
	private function name( LassoRecord $record, AffiliateUrl $url, ?AmazonUrl $amazon ): string {
		$name = trim( html_entity_decode( $record->title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		if ( 'Amazon' === $name && '' !== trim( $record->amazon( 'default_product_name' ) ) ) {
			return trim( $record->amazon( 'default_product_name' ) );
		}
		if ( '' === $name ) {
			return null === $amazon ? $url->site_name() : $amazon->starting_name();
		}

		return $name;
	}

	/**
	 * Lasso's get_lasso_thumbnail(): the custom thumbnail, else the post thumbnail ("large"), else the imported thumbnail URL,
	 * which an Amazon product's cached image replaces. Lasso's "no thumbnail" placeholder becomes no image.
	 *
	 * @param LassoRecord $record The record.
	 * @param bool        $cached The record is an Amazon product with a cached row.
	 * @return array{0: string, 1: int} The image URL and its media library ID (0 when it is not one).
	 */
	private function image( LassoRecord $record, bool $cached ): array {
		$image = array( '', 0 );
		if ( '' !== $record->meta( 'lasso_custom_thumbnail' ) ) {
			$image = array( trim( $record->meta( 'lasso_custom_thumbnail' ) ), 0 );
		} elseif ( '' !== $record->thumbnail_url ) {
			$image = array( $record->thumbnail_url, $record->thumbnail_id );
		} else {
			$image = array( trim( $cached ? $record->amazon( 'default_image' ) : $record->meta( 'lasso_thumbnail_url' ) ), 0 );
		}

		return str_contains( $image[0], self::PLACEHOLDER_IMAGE ) ? array( '', 0 ) : $image;
	}

	/**
	 * The description, hidden as Lasso hides it: when "show description" is off and the text is 10 characters or
	 * fewer (get_lasso_url: a longer text is always shown; a missing switch means on).
	 *
	 * @param LassoRecord $record The record.
	 */
	private function description( LassoRecord $record ): string {
		$text = $record->meta( 'affiliate_desc' );
		if ( '0' === $record->meta( 'show_description' ) && strlen( $text ) <= 10 ) {
			return '';
		}

		return $text;
	}
}
