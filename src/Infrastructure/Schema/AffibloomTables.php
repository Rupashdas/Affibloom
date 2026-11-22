<?php
/**
 * Affibloom's tables as they are defined now (Task TL4).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\Schema;

use Affibloom\Application\Schema\Schema;
use Affibloom\Infrastructure\WordPress\TableAmazonProductRepository;
use Affibloom\Infrastructure\WordPress\TableAmazonShortLinkRepository;
use Affibloom\Infrastructure\WordPress\TableClickStore;
use Affibloom\Infrastructure\WordPress\TableDetectedLinkRepository;
use Affibloom\Infrastructure\WordPress\TableLinkIssueRepository;
use Affibloom\Infrastructure\WordPress\TableLinkLocationRepository;
use Affibloom\Infrastructure\WordPress\TableVariantRepository;

/**
 * Affibloom's tables as they are defined now (F37 R10, D4; Task TL4): each repository's TABLE and COLUMNS, the same
 * definitions the Migrator's steps create. The DB Status page runs them on load and the table repair after an error.
 * A table added to the plugin is added here (DataInventoryTest checks the names match DataInventory::TABLES).
 */
final class AffibloomTables implements Schema {

	/**
	 * Every table.
	 *
	 * @return list<Table>
	 */
	public static function all(): array {
		return array(
			new Table( TableLinkLocationRepository::TABLE, TableLinkLocationRepository::COLUMNS ),
			new Table( TableLinkIssueRepository::TABLE, TableLinkIssueRepository::COLUMNS ),
			new Table( TableClickStore::TABLE, TableClickStore::COLUMNS ),
			new Table( TableAmazonProductRepository::TABLE, TableAmazonProductRepository::COLUMNS ),
			new Table( TableAmazonShortLinkRepository::TABLE, TableAmazonShortLinkRepository::COLUMNS ),
			new Table( TableVariantRepository::TABLE, TableVariantRepository::COLUMNS ),
			new Table( TableDetectedLinkRepository::TABLE, TableDetectedLinkRepository::COLUMNS ),
		);
	}

	/**
	 * Creates every table, or adds what its definition has and the table lacks.
	 *
	 * @return array<string, list<string>> By table name: what changed, empty when nothing.
	 */
	public function recreate(): array {
		$changes = array();
		foreach ( self::all() as $table ) {
			$changes[ $table->name() ] = $table->create();
		}

		return $changes;
	}
}
