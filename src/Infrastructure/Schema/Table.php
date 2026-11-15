<?php
/**
 * One of Affibloom's own database tables, created or brought up to date with dbDelta.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\Schema;

use RuntimeException;

/**
 * One of Affibloom's own database tables (F37 analysis, "Layers": dbDelta, collation to wp_posts). Its definition is
 * the column list WordPress's dbDelta() reads: one column or key per line, two spaces after PRIMARY KEY. create() is run
 * by a numbered Migrator step, and running it again changes nothing, so a later step may call it after a column is
 * added. The table takes wp_posts' character set and collation, so joins with WordPress's tables need no conversion.
 */
final class Table {

	/**
	 * Describes the table.
	 *
	 * @param string $name    Its name without the site's prefix ("affibloom_amazon_products").
	 * @param string $columns Its columns and keys, as dbDelta reads them.
	 */
	public function __construct( private string $name, private string $columns ) {}

	/**
	 * Creates the table, or adds what its definition has and the table lacks.
	 *
	 * @throws RuntimeException When the table is still missing afterwards, so the Migrator step fails and is retried.
	 */
	public function create(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table = $wpdb->prefix . $this->name;
		dbDelta( "CREATE TABLE {$table} (\n{$this->columns}\n) {$this->collation()};" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- a schema check, once per step.
		if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) {
			throw new RuntimeException( "The table {$table} could not be created: {$wpdb->last_error}" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- escaped where it is printed (UpgradeHooks).
		}
	}

	/**
	 * The character set and collation of wp_posts, else the site's default.
	 */
	private function collation(): string {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- a schema read, once per step.
		$collation = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $wpdb->posts ) );
		if ( 1 !== preg_match( '/^([a-z0-9]+)_[a-z0-9_]+$/', $collation, $match ) ) {
			return $wpdb->get_charset_collate();
		}

		return "DEFAULT CHARACTER SET {$match[1]} COLLATE {$collation}";
	}
}
