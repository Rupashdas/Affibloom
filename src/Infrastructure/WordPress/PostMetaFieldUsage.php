<?php
/**
 * How many products have each field, read from the products' own field rows.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Fields\FieldUsage;
use Affibloom\Domain\Fields\FieldUsageRepository;

/**
 * How many products have each field. A product's fields are one serialized list in `_affibloom_fields`, so the rows are
 * read and counted here (a few hundred products); an unsaved draft is left out, the trash is counted (Lasso's Usage).
 */
final class PostMetaFieldUsage implements FieldUsageRepository {

	/**
	 * How many products have each field.
	 */
	public function usage(): FieldUsage {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- a count across every product's meta has no WordPress API, and the answer is wanted fresh.
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT m.meta_value FROM {$wpdb->postmeta} AS m INNER JOIN {$wpdb->posts} AS p ON p.ID = m.post_id WHERE m.meta_key = %s AND p.post_type = %s AND p.post_status <> 'auto-draft'",
				'_affibloom_fields',
				ProductPostType::NAME
			)
		);
		// phpcs:enable

		$counts = array();
		foreach ( $rows as $stored ) {
			$list = maybe_unserialize( $stored );
			if ( ! is_array( $list ) ) {
				continue;
			}
			// A field counts once per product, however many rows name it.
			$seen = array();
			foreach ( $list as $row ) {
				if ( is_array( $row ) && is_int( $row['field'] ?? null ) ) {
					$seen[ $row['field'] ] = true;
				}
			}
			foreach ( array_keys( $seen ) as $field ) {
				$counts[ $field ] = ( $counts[ $field ] ?? 0 ) + 1;
			}
		}

		return new FieldUsage( $counts );
	}
}
