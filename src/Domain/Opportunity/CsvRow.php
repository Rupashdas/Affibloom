<?php
/**
 * One line of the Content Opportunities CSV.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Opportunity;

use Affibloom\Domain\Export\SpreadsheetCell;

/**
 * One unmonetized anchor in Lasso's Content Opportunities CSV (F22 R60, R61, D19; Task O9): Lasso's 18 headers, unchanged,
 * so spreadsheets made from Lasso's export keep working; text normalised as Lasso's (tags stripped, entities decoded,
 * spaces collapsed); every cell guarded against formulas.
 */
final class CsvRow {

	/** Lasso's header row (`get_headers()`), in order. */
	public const HEADERS = array(
		'post_title',
		'post_url',
		'post_id',
		'post_type',
		'post_categories',
		'post_modified',
		'content_link_clicks',
		'anchor_text',
		'anchor_context',
		'destination_url',
		'link_domain',
		'link_type',
		'display_type',
		'link_location_id',
		'unmonetized_link_count',
		'suggested_product_title',
		'suggested_product_id',
		'suggested_product_edit_url',
	);

	/**
	 * Builds the row.
	 *
	 * @param array<string, string|int> $values By header; a header left out is an empty cell.
	 */
	public function __construct( private array $values ) {}

	/**
	 * The cells in the headers' order, as text, guarded.
	 *
	 * @return list<string>
	 */
	public function cells(): array {
		return array_map( fn( string $header ): string => SpreadsheetCell::guard( (string) ( $this->values[ $header ] ?? '' ) ), self::HEADERS );
	}

	/**
	 * Lasso's `normalize_anchor_text()`: HTML entities decoded, tags (and script or style bodies) stripped, runs of
	 * white space made one space, trimmed.
	 *
	 * @param string $html The text, HTML allowed.
	 */
	public static function text( string $html ): string {
		$text = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- the domain has no WordPress calls; script and style bodies go first, as wp_strip_all_tags() does.
		$text = strip_tags( (string) preg_replace( '@<(script|style)[^>]*?>.*?</\1>@si', '', $text ) );

		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}

	/**
	 * The suggestion's title (R60): the product's, else "Amazon Associates" for one of Amazon's domains, else none.
	 *
	 * @param string $product_title The lowest-ID published product with the link's domain; '' when none.
	 * @param string $domain        The link's base domain.
	 */
	public static function suggestion( string $product_title, string $domain ): string {
		if ( '' !== $product_title ) {
			return $product_title;
		}

		return in_array( strtolower( $domain ), AmazonDomains::ALL, true ) ? 'Amazon Associates' : '';
	}
}
