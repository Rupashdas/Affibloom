<?php
namespace Affibloom\Tests\Integration;

use Affibloom\Domain\LinkHealth\LinkIssue;
use Affibloom\Infrastructure\Schema\Table;
use Affibloom\Infrastructure\WordPress\ProductPostType;
use Affibloom\Infrastructure\WordPress\TableLinkIssueRepository;
use Affibloom\Infrastructure\WordPress\TableLinkLocationRepository;
use DateTimeImmutable;
use DateTimeZone;
use WP_UnitTestCase;

/**
 * `{prefix}affibloom_link_issues` and the covered products (Task 30A; F11 D1, D5, R4, R6). Runs only where wp-env is
 * available (progress.md, deviation 10).
 */
final class LinkIssueTableTest extends WP_UnitTestCase {

	private TableLinkIssueRepository $issues;

	protected function setUp(): void {
		parent::setUp();
		( new Table( TableLinkIssueRepository::TABLE, TableLinkIssueRepository::COLUMNS ) )->create();
		( new Table( TableLinkLocationRepository::TABLE, TableLinkLocationRepository::COLUMNS ) )->create();
		$this->issues = new TableLinkIssueRepository();
	}

	private function product( string $url, string $type = 'other', string $status = 'publish' ): int {
		return self::factory()->post->create(
			array(
				'post_type'   => ProductPostType::NAME,
				'post_status' => $status,
				'meta_input'  => array(
					'_affibloom_url'  => $url,
					'_affibloom_type' => $type,
				),
			)
		);
	}

	private static function at( string $time ): DateTimeImmutable {
		return new DateTimeImmutable( $time, new DateTimeZone( 'UTC' ) );
	}

	public function test_a_row_is_stored_and_read_back(): void {
		$id  = $this->product( 'https://shop.example/a' );
		$row = new LinkIssue( $id, '', 'https://shop.example/a', null, false, null, self::at( '2026-11-18 03:00:00' ), 1, self::at( '2026-11-18 03:00:00' ), '404' );

		$this->issues->save( $row );

		self::assertEquals( $row, $this->issues->get( $id ) );
	}

	public function test_only_published_products_that_are_not_amazon_are_covered_in_lassos_order(): void {
		$never    = $this->product( 'https://shop.example/never' );
		$open     = $this->product( 'https://shop.example/open' );
		$resolved = $this->product( 'https://shop.example/resolved' );
		$amazon = $this->product( 'https://www.amazon.com/dp/B0TEST0001', 'amazon' );
		update_post_meta( $amazon, '_affibloom_asin', 'B0TEST0001' );
		update_post_meta( $amazon, '_affibloom_amazon_country', 'us' );
		$this->product( 'https://amzn.to/abc' );
		$this->product( 'https://shop.example/draft', 'other', 'draft' );
		$this->issues->save( new LinkIssue( $open, '404', 'x', self::at( '2026-11-01 00:00:00' ), false, null, self::at( '2026-11-17 00:00:00' ) ) );
		$this->issues->save( new LinkIssue( $resolved, '200', 'x', null, true, null, self::at( '2026-11-16 00:00:00' ) ) );

		self::assertSame( array( $open, $resolved, $never ), $this->issues->due( false, false ) );
		self::assertSame( array( $open, $resolved, $never, $amazon ), $this->issues->due( false, true ), 'With the product API.' );
		self::assertSame( array(), $this->issues->due( true, true ), 'No product is used in content.' );
		self::assertSame( 'https://shop.example/never', $this->issues->destination( $never, true )->url );
		self::assertFalse( $this->issues->destination( $never, true )->is_amazon() );
		self::assertNull( $this->issues->destination( $amazon, false ) );
		self::assertSame( array( 'B0TEST0001', 'us' ), array( $this->issues->destination( $amazon, true )->asin, $this->issues->destination( $amazon, true )->country ) );
		self::assertSame( array( $amazon ), $this->issues->with_asin( 'B0TEST0001', 'us' ) );
	}

	public function test_second_checks_are_found_by_their_last_check(): void {
		$id = $this->product( 'https://shop.example/a' );
		$this->issues->save( new LinkIssue( $id, '', 'x', null, false, null, self::at( '2026-11-18 03:00:00' ), 1, self::at( '2026-11-18 03:00:00' ), '404' ) );

		self::assertSame( array(), $this->issues->confirmations( self::at( '2026-11-18 02:59:59' ), false, 100 ) );
		self::assertSame( array( $id ), $this->issues->confirmations( self::at( '2026-11-18 03:00:00' ), false, 100 ) );
		self::assertEquals( self::at( '2026-11-18 03:00:00' ), $this->issues->next_confirmation( false ) );
	}

	public function test_rows_of_deleted_products_go_and_trashed_ones_stay(): void {
		$gone    = $this->product( 'https://shop.example/gone' );
		$trashed = $this->product( 'https://shop.example/trashed' );
		foreach ( array( $gone, $trashed ) as $id ) {
			$this->issues->save( new LinkIssue( $id, '404', 'x', self::at( '2026-11-01 00:00:00' ), false, null, self::at( '2026-11-17 00:00:00' ) ) );
		}
		wp_delete_post( $gone, true );
		wp_trash_post( $trashed );

		self::assertSame( 1, $this->issues->delete_orphans() );
		self::assertNull( $this->issues->get( $gone ) );
		self::assertNotNull( $this->issues->get( $trashed ) );
		self::assertNull( $this->issues->destination( $trashed, true ) );
	}
}
