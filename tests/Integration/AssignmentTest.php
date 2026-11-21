<?php
namespace Affibloom\Tests\Integration;

use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Infrastructure\Schema\Table;
use Affibloom\Infrastructure\WordPress\TableLinkLocationRepository;
use WP_UnitTestCase;

/**
 * Monetize at render time (F22 N1-A, D10; Task O10): the assignment survives a re-index of the post, moves the tie a
 * rescan copied with it, and goes when the anchor's address changes. Runs only where wp-env is available
 * (progress.md, deviation 10).
 */
final class AssignmentTest extends WP_UnitTestCase {

	private TableLinkLocationRepository $locations;

	protected function setUp(): void {
		parent::setUp();
		( new Table( TableLinkLocationRepository::TABLE, TableLinkLocationRepository::COLUMNS ) )->create();
		$this->locations = new TableLinkLocationRepository();
	}

	private static function row( int $host, string $url ): LinkLocation {
		return new LinkLocation( $host, 'https://site.example/p/', 'content', 1, 0, 'external', 'Text', $url, md5( $url ), domain: 'shop.example' );
	}

	private static function id_of( int $host ): int {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE host_id = %d', $wpdb->prefix . TableLinkLocationRepository::TABLE, $host ) );
	}

	private function only( int $host ): LinkLocation {
		return $this->locations->find( self::id_of( $host ) );
	}

	public function test_an_assignment_survives_a_reindex_and_unassigning_drops_the_copied_tie(): void {
		$host = self::factory()->post->create();
		$this->locations->replace_host( $host, array( self::row( $host, 'https://shop.example/a' ) ) );
		self::assertTrue( $this->locations->set_assigned( self::id_of( $host ), 77 ) );
		$this->locations->replace_host( $host, array( self::row( $host, 'https://shop.example/a' ) ) );
		$kept = $this->only( $host );
		self::assertSame( array( 77, 77 ), array( $kept->assigned_product_id, $kept->product_id ) );

		$this->locations->set_assigned( self::id_of( $host ), 0 );
		self::assertSame( array( 0, 0 ), array( $this->only( $host )->assigned_product_id, $this->only( $host )->product_id ) );
	}

	public function test_a_changed_address_drops_the_assignment(): void {
		$host = self::factory()->post->create();
		$this->locations->replace_host( $host, array( self::row( $host, 'https://shop.example/a' ) ) );
		$this->locations->set_assigned( self::id_of( $host ), 77 );

		$this->locations->replace_host( $host, array( self::row( $host, 'https://shop.example/b' ) ) );

		self::assertSame( 0, $this->only( $host )->assigned_product_id );
	}

	public function test_a_gone_row_is_not_found(): void {
		self::assertFalse( $this->locations->set_assigned( 999999, 5 ) );
	}
}
