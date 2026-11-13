<?php
/**
 * One Lasso product record, as read from Lasso's tables.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Migration;

/**
 * One published Lasso record (a `lasso-urls` post), with what Lasso reads to show it: its post meta, its
 * `lasso_url_details` row and, for an Amazon product, its `lasso_amazon_products` row. Read only; nothing here
 * writes to Lasso.
 */
final class LassoRecord {

	/**
	 * Builds a record.
	 *
	 * @param int                        $id            The Lasso post ID.
	 * @param string                     $title         post_title, as stored (HTML entities not decoded).
	 * @param string                     $date          post_date.
	 * @param string                     $modified      post_modified (the Dashboard's order).
	 * @param array<string, string>      $meta          The post meta Lasso reads, key => value; a missing key is ''.
	 * @param string                     $redirect_url  lasso_url_details.redirect_url.
	 * @param string                     $product_type  lasso_url_details.product_type ("amazon", or '').
	 * @param string                     $product_id    lasso_url_details.product_id (an Amazon product's ASIN).
	 * @param string                     $country       lasso_url_details.country.
	 * @param array<string, string>|null $amazon        The lasso_amazon_products row, column => value; null when none.
	 * @param string                     $thumbnail_url The post thumbnail's "large" URL; '' when the post has none.
	 * @param int                        $thumbnail_id  The post thumbnail's attachment ID; 0 when none.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $title,
		public readonly string $date,
		public readonly string $modified,
		public readonly array $meta = array(),
		public readonly string $redirect_url = '',
		public readonly string $product_type = '',
		public readonly string $product_id = '',
		public readonly string $country = '',
		public readonly ?array $amazon = null,
		public readonly string $thumbnail_url = '',
		public readonly int $thumbnail_id = 0,
	) {}

	/**
	 * A post meta value, '' when Lasso stored none (get_post_meta( …, true ) gives '' then too).
	 *
	 * @param string $key The meta key.
	 */
	public function meta( string $key ): string {
		return $this->meta[ $key ] ?? '';
	}

	/**
	 * A column of the Amazon row, '' when there is no row or no value.
	 *
	 * @param string $column The column.
	 */
	public function amazon( string $column ): string {
		return null === $this->amazon ? '' : (string) ( $this->amazon[ $column ] ?? '' );
	}
}
