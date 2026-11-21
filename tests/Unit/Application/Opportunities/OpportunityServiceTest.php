<?php
namespace Affibloom\Tests\Unit\Application\Opportunities;

use Affibloom\Application\Opportunities\OpportunityService;
use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Domain\Opportunity\OpportunityRepository;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Settings\Settings;
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

	/** Monetize (F22 R64-R72, N1-A, N3; Task O10). */
	public function test_assign_checks_the_row_its_own_tie_and_the_product(): void {
		$products = $this->createStub( ProductRepository::class );
		$products->method( 'get' )->willReturnCallback(
			static fn( int $id ): ?Product => 5 === $id ? Product::draft( 'Five', AffiliateUrl::from_string( 'https://shop.example/5' ), Settings::from_array( array() ) )->with( array( 'id' => 5 ) ) : null
		);
		$this->locations->rows[43] = array( new LinkLocation( 43, 'https://site.example/b/', 'post_content', 1, 9, 'product', '', 'https://shop.example/9', md5( 'x' ) ) );
		$service = new OpportunityService( $this->createStub( OpportunityRepository::class ), $this->locations, $products );

		self::assertSame( 'gone', $service->assign( 9, 5 ) );
		self::assertSame( 'in_content', $service->assign( 2, 5 ) );
		self::assertSame( 'no_product', $service->assign( 1, 6 ) );
		self::assertSame( '', $service->assign( 1, 5 ) );
		self::assertSame( array( 1 => 5 ), $this->locations->assigned );
	}

	public function test_unassign_clears_the_row_and_indexes_its_post_again(): void {
		$indexed = array();
		$service = new OpportunityService(
			$this->createStub( OpportunityRepository::class ),
			$this->locations,
			null,
			static function ( int $host ) use ( &$indexed ): ?array {
				$indexed[] = $host;
				return null;
			}
		);

		self::assertTrue( $service->unassign( 1 ) );
		self::assertSame( array( 1 => 0 ), $this->locations->assigned );
		self::assertSame( array( 42 ), $indexed );
		self::assertFalse( $service->unassign( 9 ) );
	}
}
