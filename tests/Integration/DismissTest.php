<?php
namespace Affibloom\Tests\Integration;

use Affibloom\Application\Opportunities\OpportunityService;
use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Infrastructure\Schema\Table;
use Affibloom\Infrastructure\WordPress\TableLinkLocationRepository;
use Affibloom\Infrastructure\WordPress\TableOpportunityRepository;
use WP_UnitTestCase;

/**
 * Dismiss on the link index (F22 R44-R49, D10, D11; Task O7): kept across a re-index of the host, undone, and refused
 * for a row that is gone. Runs only where wp-env is available (progress.md, deviation 10).
 */
final class DismissTest extends WP_UnitTestCase {

	private TableLinkLocationRepository $locations;

	private OpportunityService $service;

	protected function setUp(): void {
		parent::setUp();
		( new Table( TableLinkLocationRepository::TABLE, TableLinkLocationRepository::COLUMNS ) )->create();
		$this->locations = new TableLinkLocationRepository();
		$this->service   = new OpportunityService( new TableOpportunityRepository(), $this->locations );
	}

	private static function row( int $host ): LinkLocation {
		$url = 'https://shop.example/a';
		return new LinkLocation( $host, 'https://site.example/p/', 'post_content', 1, 0, 'link', '', $url, md5( $url ), domain: 'shop.example' );
	}

	private function id_of( int $host ): int {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE host_id = %d', $wpdb->prefix . TableLinkLocationRepository::TABLE, $host ) );
	}

	public function test_a_dismissal_survives_a_reindex_and_undo_brings_it_back(): void {
		$host = self::factory()->post->create();
		$this->locations->replace_host( $host, array( self::row( $host ) ) );

		self::assertTrue( $this->service->dismiss( $this->id_of( $host ) ) );
		$this->locations->replace_host( $host, array( self::row( $host ) ) );
		self::assertTrue( $this->locations->find( $this->id_of( $host ) )->is_ignored );

		self::assertTrue( $this->service->restore( $this->id_of( $host ) ) );
		self::assertFalse( $this->locations->find( $this->id_of( $host ) )->is_ignored );
	}

	public function test_a_gone_row_is_not_found(): void {
		self::assertFalse( $this->service->dismiss( 999999 ) );
	}
}
