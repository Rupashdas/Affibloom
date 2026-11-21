<?php
namespace Affibloom\Tests\Unit\Application\Opportunities;

use Affibloom\Application\Opportunities\OpportunityService;
use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Domain\Opportunity\OpportunityRepository;
use Affibloom\Tests\Unit\Fakes\InMemoryLinkLocations;
use PHPUnit\Framework\TestCase;

/**
 * Dismiss and its Undo (F22 R44-R49, D10-D12, D20; Task O7).
 */
final class OpportunityServiceTest extends TestCase {

	private InMemoryLinkLocations $locations;

	private OpportunityService $service;

	protected function setUp(): void {
		$this->locations = new InMemoryLinkLocations();
		$this->locations->rows[42] = array( new LinkLocation( 42, 'https://site.example/post/', 'post_content', 1, 0, 'link', '', 'https://shop.example/a', md5( 'https://shop.example/a' ) ) );
		$this->service = new OpportunityService( $this->createStub( OpportunityRepository::class ), $this->locations );
	}

	public function test_dismiss_sets_the_flag_and_undo_clears_it(): void {
		self::assertTrue( $this->service->dismiss( 1 ) );
		self::assertSame( array( 1 => true ), $this->locations->ignored );

		self::assertTrue( $this->service->restore( 1 ) );
		self::assertSame( array( 1 => false ), $this->locations->ignored );
	}

	public function test_a_gone_row_is_not_found(): void {
		self::assertFalse( $this->service->dismiss( 9 ) );
		self::assertFalse( $this->service->restore( 9 ) );
		self::assertSame( array(), $this->locations->ignored );
	}

	public function test_the_host_is_named_for_the_edit_post_check(): void {
		self::assertSame( 42, $this->service->host_of( 1 ) );
		self::assertSame( 0, $this->service->host_of( 9 ) );
	}

	/** The Dashboard's lightbulb (F22 R52-R54, D13; Task O8): posts on the Opportunities tab, rows elsewhere. */
	public function test_per_product_asks_for_posts_on_the_tab_and_rows_elsewhere(): void {
		$repo = $this->createMock( OpportunityRepository::class );
		$repo->expects( self::exactly( 2 ) )->method( 'counts_for' )
			->willReturnCallback( static fn( array $ids, bool $hosts ): array => $hosts ? array( 7 => 2 ) : array( 7 => 5 ) );
		$service = new OpportunityService( $repo, $this->locations );

		self::assertSame( array( 7 => 2 ), $service->per_product( array( 7, 8 ), true ) );
		self::assertSame( array( 7 => 5 ), $service->per_product( array( 7, 8 ), false ) );
	}
}
