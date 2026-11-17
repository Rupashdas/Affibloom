<?php
/**
 * URL variants in their own table.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Variant\Variant;
use Affibloom\Domain\Variant\VariantKind;
use Affibloom\Domain\Variant\VariantRepository;

/**
 * The products' URL variants (F27 D1): Lasso's `lasso_url_variants` columns and keys under Affibloom's name, one table
 * because a variant's key is looked up across all products (R18, R19).
 */
final class TableVariantRepository implements VariantRepository {

	public const TABLE = 'affibloom_url_variants';

	public const COLUMNS = "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
parent_id bigint(20) unsigned NOT NULL,
name text NOT NULL,
variant_key varchar(191) NOT NULL,
variant_kind varchar(20) NOT NULL,
created_at datetime NOT NULL,
updated_at datetime NOT NULL,
PRIMARY KEY  (id),
UNIQUE KEY parent_key (parent_id,variant_key),
KEY parent_id (parent_id),
KEY key_kind (variant_key,variant_kind)";

	/**
	 * A product's variants, in the order they were stored.
	 *
	 * @param int $parent The product's ID.
	 * @return list<Variant>
	 */
	public function for_parent( int $parent ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, read once per product page.
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT id, parent_id, name, variant_key, variant_kind FROM %i WHERE parent_id = %d ORDER BY id', $wpdb->prefix . self::TABLE, $parent ), ARRAY_A );

		return array_map( array( self::class, 'variant' ), is_array( $rows ) ? array_values( $rows ) : array() );
	}

	/**
	 * A stored row as a variant; a kind that is not one of the two reads as a permalink.
	 *
	 * @param array<string, mixed> $row The row.
	 */
	private static function variant( array $row ): Variant {
		return new Variant(
			(int) $row['id'],
			(int) $row['parent_id'],
			(string) $row['name'],
			(string) $row['variant_key'],
			VariantKind::tryFrom( (string) $row['variant_kind'] ) ?? VariantKind::Permalink,
		);
	}
}
