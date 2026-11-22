<?php
namespace Affibloom\Tests\Unit\Domain\Lifecycle;

use Affibloom\Domain\Lifecycle\DataInventory;
use Affibloom\Infrastructure\Schema\AffibloomTables;
use Affibloom\Infrastructure\Schema\Table;
use PHPUnit\Framework\TestCase;

/**
 * Task 38: the list of everything Affibloom stores, which the opt-in removal deletes. Each list is non-empty, has no
 * repeat, and every name is Affibloom's own, so the removal can never touch another plugin's data.
 */
final class DataInventoryTest extends TestCase {

	/**
	 * @return array<string, array{0: list<string>, 1: string}>
	 */
	public static function lists(): array {
		return array(
			'options'          => array( DataInventory::OPTIONS, '/^affibloom_[a-z0-9_]+$/' ),
			'transients'       => array( DataInventory::TRANSIENTS, '/^affibloom_[a-z0-9_]+$/' ),
			'transient groups' => array( DataInventory::TRANSIENT_PREFIXES, '/^affibloom_[a-z0-9_]+_$/' ),
			'user meta'        => array( DataInventory::USER_META, '/^affibloom_[a-z0-9_]+$/' ),
			'post types'       => array( DataInventory::POST_TYPES, '/^affibloom_[a-z]+$/' ),
			'taxonomies'       => array( DataInventory::TAXONOMIES, '/^affibloom_[a-z]+$/' ),
			'tables'           => array( DataInventory::TABLES, '/^affibloom_[a-z_]+$/' ),
			'cron hooks'       => array( DataInventory::CRON_HOOKS, '/^affibloom_[a-z_]+$/' ),
		);
	}

	/**
	 * @dataProvider lists
	 *
	 * @param list<string> $names   A list of the inventory.
	 * @param string       $pattern What each name must look like.
	 */
	public function test_each_list_is_non_empty_unique_and_affibloom_s( array $names, string $pattern ): void {
		self::assertNotEmpty( $names );
		self::assertSame( $names, array_values( array_unique( $names ) ) );
		foreach ( $names as $name ) {
			self::assertMatchesRegularExpression( $pattern, $name );
		}
	}

	public function test_the_tables_the_repair_creates_are_the_tables_the_removal_drops(): void {
		$names = array_map( static fn( Table $table ): string => $table->name(), AffibloomTables::all() );
		sort( $names );
		$listed = DataInventory::TABLES;
		sort( $listed );

		self::assertSame( $listed, $names );
	}

	public function test_post_meta_and_the_log_folder_are_affibloom_s(): void {
		self::assertSame( '_affibloom_', DataInventory::POST_META_PREFIX );
		self::assertSame( 'affibloom-logs', DataInventory::LOG_FOLDER );
	}
}
