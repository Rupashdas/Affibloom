<?php
namespace Affibloom\Tests\Unit\Application\Locations;

use Affibloom\Application\Locations\LocationCounts;
use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Domain\LinkIndex\RebuildState;
use Affibloom\Domain\LinkIndex\RebuildStateRepository;
use Affibloom\Tests\Unit\Fakes\InMemoryLinkLocations;
use PHPUnit\Framework\TestCase;

/**
 * The Dashboard's Locations count and its clock (F12 L1-L3, D20, D21; Task 36A).
 */
final class LocationCountsTest extends TestCase {

	private InMemoryLinkLocations $rows;

	protected function setUp(): void {
		$this->rows = new InMemoryLinkLocations();
		$this->rows->replace_host( 10, array( self::row( 10, 1, 7 ), self::row( 10, 2, 7 ), self::row( 10, 3, 0, 'group:5' ) ) );
		$this->rows->replace_host( 11, array( self::row( 11, 1, 0, null, 8 ), self::row( 11, 2, 0 ) ) );
		$this->rows->groups = array( 'group:5' => array( 7, 9 ) );
	}

	public function test_counts_tied_rows_assignments_and_the_group_displays_that_show_a_product(): void {
		$counts = new LocationCounts( $this->rows, self::state( '2026-11-20 10:00:00' ) );

		// 7: two tied rows and the group display; 8: one row assigned to it; 9: the group display only; 12: none.
		self::assertSame( array( 7 => 3, 8 => 1, 9 => 1, 12 => 0 ), $counts->of( array( 7, 8, 9, 12 ) ) );
		self::assertSame( array(), $counts->of( array() ) );
	}

	public function test_the_count_is_known_once_a_full_index_has_finished(): void {
		self::assertTrue( ( new LocationCounts( $this->rows, self::state( '2026-11-20 10:00:00' ) ) )->known() );
		self::assertFalse( ( new LocationCounts( $this->rows, self::state( '' ) ) )->known() );
	}

	private static function row( int $host, int $occurrence, int $product, ?string $ref = null, int $assigned = 0 ): LinkLocation {
		return new LinkLocation(
			host_id: $host,
			host_url: 'https://example.test/?p=' . $host,
			source: 'content',
			occurrence: $occurrence,
			product_id: $product,
			link_type: 'product',
			display_type: null === $ref ? 'Text' : 'Grid',
			url: 'https://example.com/' . $occurrence,
			url_md5: md5( 'https://example.com/' . $occurrence ),
			detected_at: '2026-11-20 10:00:00',
			display_ref: $ref,
			assigned_product_id: $assigned,
		);
	}

	private static function state( string $finished_at ): RebuildStateRepository {
		return new class( $finished_at ) implements RebuildStateRepository {
			public function __construct( private string $finished_at ) {}

			public function get(): RebuildState {
				return new RebuildState( finished_at: $this->finished_at );
			}

			public function save( RebuildState $state ): void {}
		};
	}
}
