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
use RuntimeException;

/**
 * The products' URL variants (F27 D1): Lasso's `lasso_url_variants` columns and keys under Affibloom's name, one table
 * because a variant's key is looked up across all products (R18, R19).
 */
final class TableVariantRepository implements VariantRepository {

	public const TABLE = 'affibloom_url_variants';

	public const COLUMNS = 'id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
parent_id bigint(20) unsigned NOT NULL,
name text NOT NULL,
variant_key varchar(191) NOT NULL,
variant_kind varchar(20) NOT NULL,
created_at datetime NOT NULL,
updated_at datetime NOT NULL,
PRIMARY KEY  (id),
UNIQUE KEY parent_key (parent_id,variant_key),
KEY parent_id (parent_id),
KEY key_kind (variant_key,variant_kind)';

	/**
	 * A product's variants, in the order they were stored.
	 *
	 * @param int $product_id The product's ID.
	 * @return list<Variant>
	 */
	public function for_parent( int $product_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, read once per product page.
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT id, parent_id, name, variant_key, variant_kind FROM %i WHERE parent_id = %d ORDER BY id', $wpdb->prefix . self::TABLE, $product_id ), ARRAY_A );

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

	/**
	 * Stores a product's whole list in place of the old one (R16): in a transaction, the old rows deleted and each new
	 * one inserted; any failure rolls it all back. IDs are new after every save, as Lasso's.
	 *
	 * @param int           $product_id   The product's ID.
	 * @param list<Variant> $variants The list.
	 * @return list<Variant>
	 * @throws RuntimeException When the list cannot be stored; nothing changed.
	 */
	public function replace( int $product_id, array $variants ): array {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;
		$now   = current_time( 'mysql', true );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, written once per save.
		$wpdb->query( 'START TRANSACTION' );
		$ok = false !== $wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE parent_id = %d', $table, $product_id ) );
		foreach ( $variants as $variant ) {
			$ok = $ok && false !== $wpdb->insert(
				$table,
				array(
					'parent_id'    => $product_id,
					'name'         => $variant->name,
					'variant_key'  => $variant->key,
					'variant_kind' => $variant->kind->value,
					'created_at'   => $now,
					'updated_at'   => $now,
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s' )
			);
		}
		if ( ! $ok ) {
			$wpdb->query( 'ROLLBACK' );
			throw new RuntimeException( 'Could not save variants.' );
		}
		$wpdb->query( 'COMMIT' );
		// phpcs:enable

		return $this->for_parent( $product_id );
	}

	/**
	 * The slug of the published or retired product a permalink variant's key belongs to, or null (R18; Task V3): the key
	 * as typed in the path, or its sanitize_title form, as Lasso looks it up; the oldest row wins.
	 *
	 * @param string $key A path's last part.
	 */
	public function parent_slug_for_permalink( string $key ): ?string {
		global $wpdb;

		$clean = sanitize_title( $key );
		if ( '' === trim( $key ) ) {
			return null;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, only for an address no post or product answers.
		$slug = $wpdb->get_var( $wpdb->prepare( 'SELECT p.post_name FROM %i AS v INNER JOIN %i AS p ON p.ID = v.parent_id WHERE v.variant_key IN ( %s, %s ) AND v.variant_kind = %s AND p.post_type = %s AND p.post_status IN ( %s, %s ) ORDER BY v.id LIMIT 1', $wpdb->prefix . self::TABLE, $wpdb->posts, $key, $clean, VariantKind::Permalink->value, ProductPostType::NAME, 'publish', ProductPostType::RETIRED ) );

		return is_string( $slug ) && '' !== $slug ? $slug : null;
	}
}
