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
		$counts = array();
		foreach ( $this->products( '' ) as $fields ) {
			foreach ( $fields as $field ) {
				$counts[ $field ] = ( $counts[ $field ] ?? 0 ) + 1;
			}
		}

		return new FieldUsage( $counts );
	}

	/**
	 * The published products that have this field, by name.
	 *
	 * @param int $field The field's ID.
	 * @return list<int>
	 */
	public function published_with( int $field ): array {
		$ids = array();
		foreach ( $this->products( 'publish' ) as $product => $fields ) {
			if ( in_array( $field, $fields, true ) ) {
				$ids[] = $product;
			}
		}

		return $ids;
	}

	/**
	 * Each product's field IDs, once each, in the order of the products' names; every status but an unsaved draft, or just
	 * one.
	 *
	 * @param string $status A post status; '' for every status.
	 * @return array<int, list<int>> Field IDs by product ID.
	 */
	private function products( string $status ): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- a read across every product's meta has no WordPress API, and the answer is wanted fresh.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT m.post_id, m.meta_value FROM {$wpdb->postmeta} AS m INNER JOIN {$wpdb->posts} AS p ON p.ID = m.post_id WHERE m.meta_key = %s AND p.post_type = %s AND p.post_status <> 'auto-draft' AND ( %s = '' OR p.post_status = %s ) ORDER BY p.post_title ASC, p.ID ASC",
				'_affibloom_fields',
				ProductPostType::NAME,
				$status,
				$status
			)
		);
		// phpcs:enable

		$products = array();
		foreach ( (array) $rows as $row ) {
			$list = maybe_unserialize( $row->meta_value );
			if ( ! is_array( $list ) ) {
				continue;
			}
			// A field counts once per product, however many rows name it.
			$seen = array();
			foreach ( $list as $item ) {
				if ( is_array( $item ) && is_int( $item['field'] ?? null ) ) {
					$seen[ $item['field'] ] = true;
				}
			}
			$products[ (int) $row->post_id ] = array_keys( $seen );
		}

		return $products;
	}
}
