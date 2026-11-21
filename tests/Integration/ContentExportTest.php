<?php
namespace Affibloom\Tests\Integration;

use Affibloom\Application\Opportunities\ContentExport;
use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Domain\Opportunity\ContentFilter;
use Affibloom\Domain\Opportunity\CsvRow;
use Affibloom\Domain\Opportunity\OpportunityQuery;
use Affibloom\Infrastructure\Schema\Table;
use Affibloom\Infrastructure\WordPress\TableClickStore;
use Affibloom\Infrastructure\WordPress\TableLinkLocationRepository;
use Affibloom\Infrastructure\WordPress\TableOpportunityRepository;
use Affibloom\Application\LinkIndex\ContentSource;
use Affibloom\Domain\LinkIndex\HostContent;
use WP_UnitTestCase;

/**
 * The Content Opportunities CSV (F22 R58-R61; Task O9): headers, one line per untied Text link, the list's order, the
 * formula guard, and dismissed rows included. Runs only where wp-env is available (progress.md, deviation 10).
 */
final class ContentExportTest extends WP_UnitTestCase {

	private TableLinkLocationRepository $locations;

	protected function setUp(): void {
		parent::setUp();
		( new Table( TableLinkLocationRepository::TABLE, TableLinkLocationRepository::COLUMNS ) )->create();
		( new Table( TableClickStore::TABLE, TableClickStore::COLUMNS ) )->create();
		$this->locations = new TableLinkLocationRepository();
	}

	private static function link( int $host, string $url, string $anchor, int $occurrence = 1, bool $ignored = false ): LinkLocation {
		return new LinkLocation( $host, get_permalink( $host ), 'post_content', $occurrence, 0, 'external', 'Text', $url, md5( $url ), $anchor, (string) wp_parse_url( $url, PHP_URL_HOST ), is_ignored: $ignored );
	}

	private function export(): ContentExport {
		$content = new class() implements ContentSource {
			public function read( int $host_id ): ?HostContent {
				return null;
			}
		};

		return new ContentExport( new TableOpportunityRepository(), $content, static fn( int $id ): string => 'edit/' . $id );
	}

	public function test_the_file_lists_each_untied_text_link_in_the_lists_order(): void {
		$old = self::factory()->post->create( array( 'post_title' => 'Old', 'post_modified' => '2026-01-01 00:00:00' ) );
		$new = self::factory()->post->create( array( 'post_title' => '=cmd', 'post_modified' => '2026-06-01 00:00:00' ) );
		$this->locations->replace_host( $old, array( self::link( $old, 'https://shop.example/a', 'A' ) ) );
		$this->locations->replace_host( $new, array( self::link( $new, 'https://shop.example/b', 'B' ), self::link( $new, 'https://shop.example/c', 'C', 1, true ) ) );

		$query = new OpportunityQuery( '', '', '', 1, 0, OpportunityQuery::CONTENT );
		$rows  = iterator_to_array( $this->export()->rows( $query, new ContentFilter(), time() ), false );

		self::assertSame( CsvRow::HEADERS, $rows[0] );
		self::assertCount( 4, $rows );
		// Newest post first; a title that looks like a formula is text; the dismissed link is still there.
		self::assertSame( array( "\t=cmd", "\t=cmd", 'Old' ), array_column( array_slice( $rows, 1 ), 0 ) );
		self::assertSame( array( 'B', 'C', 'A' ), array_column( array_slice( $rows, 1 ), 7 ) );
		self::assertSame( 3, $this->export()->count( $query, new ContentFilter(), time() ) );
	}
}
