<?php
/**
 * Removes everything Affibloom stores (Task 38).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Lifecycle\DataInventory;
use wpdb;

/**
 * Removes everything Affibloom stores (Task 38; F37 R14-R19, N1), as DataInventory lists it: the products and tables
 * (every status, with their meta), the groups, the custom tables, the options, transients, user meta and cron events,
 * any `_affibloom_` post meta left on other posts, and the log folder. Run by uninstall.php only when an administrator
 * turned on "Delete all Affibloom data when the plugin is deleted". No post's content is touched: boxes in posts stop
 * drawing, as the Uninstall card warns (Lasso removes nothing on delete; this removal is Affibloom's own).
 */
final class DataEraser {

	/** Posts deleted per query, so a large library does not load at once. */
	private const BATCH = 100;

	/**
	 * Builds the eraser.
	 *
	 * @param wpdb $db WordPress's database.
	 */
	public function __construct( private wpdb $db ) {}

	/**
	 * Removes it all.
	 */
	public function run(): void {
		$this->posts();
		$this->terms();
		$this->tables();
		$this->options();
		$this->user_meta();
		$this->cron();
		$this->post_meta();
		$this->logs();
	}

	/**
	 * Every product and table in every status (trash and retired too), with its meta and term links.
	 */
	private function posts(): void {
		$types = DataInventory::POST_TYPES;
		do {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared -- every status, which WP_Query cannot ask for; one placeholder per post type.
			$ids = $this->db->get_col( $this->db->prepare( 'SELECT ID FROM %i WHERE post_type IN (' . implode( ',', array_fill( 0, count( $types ), '%s' ) ) . ') LIMIT %d', $this->db->posts, ...array_merge( $types, array( self::BATCH ) ) ) );
			foreach ( $ids as $id ) {
				wp_delete_post( (int) $id, true );
			}
			$full = count( $ids ) === self::BATCH;
		} while ( $full );
	}

	/**
	 * Every group term with its meta. The plugin is not running during an uninstall, so the taxonomy is registered
	 * for the time it takes: WordPress cannot delete a term of a taxonomy it does not know.
	 */
	private function terms(): void {
		foreach ( DataInventory::TAXONOMIES as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				register_taxonomy( $taxonomy, DataInventory::POST_TYPES, array( 'public' => false ) );
			}
			$ids = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);
			foreach ( is_array( $ids ) ? $ids : array() as $id ) {
				wp_delete_term( (int) $id, $taxonomy );
			}
		}
	}

	/**
	 * The custom tables.
	 */
	private function tables(): void {
		foreach ( DataInventory::TABLES as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- removing Affibloom's own tables, by the inventory's fixed names.
			$this->db->query( $this->db->prepare( 'DROP TABLE IF EXISTS %i', $this->db->prefix . $table ) );
		}
	}

	/**
	 * The options, the named transients, and the transients named by a prefix (with their timeout rows).
	 */
	private function options(): void {
		foreach ( DataInventory::OPTIONS as $option ) {
			delete_option( $option );
		}
		foreach ( DataInventory::TRANSIENTS as $transient ) {
			delete_transient( $transient );
		}
		foreach ( DataInventory::TRANSIENT_PREFIXES as $prefix ) {
			foreach ( array( '_transient_', '_transient_timeout_' ) as $kind ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- transients named by a key cannot be listed through the API.
				$this->db->query( $this->db->prepare( 'DELETE FROM %i WHERE option_name LIKE %s', $this->db->options, $this->db->esc_like( $kind . $prefix ) . '%' ) );
			}
		}
		wp_cache_flush();
	}

	/**
	 * The user meta, for every user.
	 */
	private function user_meta(): void {
		foreach ( DataInventory::USER_META as $key ) {
			delete_metadata( 'user', 0, $key, '', true );
		}
	}

	/**
	 * Every scheduled event of the cron hooks.
	 */
	private function cron(): void {
		foreach ( DataInventory::CRON_HOOKS as $hook ) {
			wp_unschedule_hook( $hook );
		}
	}

	/**
	 * Any `_affibloom_` post meta left (on posts that are not Affibloom's; none is written by design).
	 */
	private function post_meta(): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- a sweep by key prefix, which the meta API cannot do.
		$this->db->query( $this->db->prepare( 'DELETE FROM %i WHERE meta_key LIKE %s', $this->db->postmeta, $this->db->esc_like( DataInventory::POST_META_PREFIX ) . '%' ) );
	}

	/**
	 * The log folder in uploads (TL3), if there is one.
	 */
	private function logs(): void {
		$dir = trailingslashit( (string) wp_upload_dir( null, false )['basedir'] ) . DataInventory::LOG_FOLDER;
		if ( ! is_dir( $dir ) ) {
			return;
		}
		global $wp_filesystem;
		require_once ABSPATH . 'wp-admin/includes/file.php';
		if ( WP_Filesystem() ) {
			$wp_filesystem->rmdir( $dir, true );
		}
	}
}
