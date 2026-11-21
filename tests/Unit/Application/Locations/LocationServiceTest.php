<?php
namespace Affibloom\Tests\Unit\Application\Locations;

use Affibloom\Application\Locations\LocationService;
use Affibloom\Tests\Unit\Fakes\InMemoryLinkLocations;
use PHPUnit\Framework\TestCase;

/**
 * The Locations tab's pages (F12 L5, L8, L9, D23; Task 36B).
 */
final class LocationServiceTest extends TestCase {

	public function test_ten_hosts_a_page_by_title_descending_unless_ascending_is_asked(): void {
		$rows    = new InMemoryLinkLocations();
		$service = new LocationService( $rows );

		$service->page( 7, '  shoes ', '', 0 );
		$service->page( 7, '', 'asc', 2 );
		$service->page( 7, '', 'sideways', 3 );

		self::assertSame(
			array(
				array( 7, 'shoes', 'desc', 1, 10 ),
				array( 7, '', 'asc', 2, 10 ),
				array( 7, '', 'desc', 3, 10 ),
			),
			$rows->asked
		);
	}
}
