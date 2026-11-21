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
}
