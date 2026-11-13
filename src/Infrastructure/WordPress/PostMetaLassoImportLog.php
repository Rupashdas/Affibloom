<?php
/**
 * The one-time Lasso import's log: `_affibloom_lasso_id` on each product, and an option once it has finished.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Migration\ImportResult;
use Affibloom\Domain\Migration\LassoImportLog;
use Affibloom\Domain\Migration\LassoRecord;

/**
 * Each imported product carries its Lasso record's ID in `_affibloom_lasso_id` (the key Tasks 25-26 and the
 * development seed use too), and the finished import is the `affibloom_lasso_import` option.
 */
final class PostMetaLassoImportLog implements LassoImportLog {

	/** The option that says the import has finished. */
	public const OPTION = 'affibloom_lasso_import';

	/** The product meta that names its Lasso record. */
	private const META = '_affibloom_lasso_id';

	/**
	 * The Lasso IDs that already have a product, in any status (a product deleted since is not brought back).
	 *
	 * @return list<int>
	 */
	public function imported_lasso_ids(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- read once per import.
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT m.meta_value FROM {$wpdb->postmeta} AS m INNER JOIN {$wpdb->posts} AS p ON p.ID = m.post_id
				WHERE m.meta_key = %s AND p.post_type = %s",
				self::META,
				ProductPostType::NAME
			)
		);

		return array_values( array_unique( array_map( 'intval', $ids ) ) );
	}

	/**
	 * Names the product's Lasso record, and gives it the record's dates, so the Dashboard lists the products in
	 * Lasso's order (most recently changed first). wp_insert_post() stamps post_modified with "now" whatever it is
	 * given, so the dates are written to the row afterwards.
	 *
	 * @param int         $product_id The product's post ID.
	 * @param LassoRecord $record     Its record.
	 */
	public function link( int $product_id, LassoRecord $record ): void {
		global $wpdb;

		update_post_meta( $product_id, self::META, (string) $record->id );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- post_modified cannot be set through wp_insert_post().
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_date'         => $record->date,
				'post_date_gmt'     => get_gmt_from_date( $record->date ),
				'post_modified'     => $record->modified,
				'post_modified_gmt' => get_gmt_from_date( $record->modified ),
			),
			array( 'ID' => $product_id ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
		clean_post_cache( $product_id );
	}

	/**
	 * The finished import, or null.
	 */
	public function result(): ?ImportResult {
		$stored = get_option( self::OPTION, array() );

		return is_array( $stored ) ? ImportResult::from_array( $stored ) : null;
	}

	/**
	 * Records the finished import; not autoloaded, since only the Dashboard reads it.
	 *
	 * @param ImportResult $result What it did.
	 */
	public function save_result( ImportResult $result ): void {
		update_option( self::OPTION, $result->to_array(), false );
	}
}
