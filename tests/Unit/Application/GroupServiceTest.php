<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Groups\GroupService;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Tests\Unit\Fakes\InMemoryGroupRepository;
use PHPUnit\Framework\TestCase;

final class GroupServiceTest extends TestCase {
	private InMemoryGroupRepository $groups;

	protected function setUp(): void {
		parent::setUp();
		$this->groups = new InMemoryGroupRepository();
	}

	private function service(): GroupService {
		return new GroupService( $this->groups );
	}

	public function test_a_group_is_made_with_a_trimmed_name_and_its_description(): void {
		$group = $this->service()->create( '  Best Shoes ', ' For the court. ' );

		self::assertSame( 'Best Shoes', $group->name );
		self::assertSame( 'For the court.', $group->description );
		self::assertSame( 1, $this->groups->next - 1 );
	}

	public function test_an_empty_name_is_refused_and_nothing_is_made(): void {
		foreach ( array( '', '   ' ) as $name ) {
			try {
				$this->service()->create( $name, 'text' );
				self::fail( 'An empty name must be refused.' );
			} catch ( InvalidField $error ) {
				self::assertSame( 'name', $error->field );
				self::assertSame( 'group_name_required', $error->reason );
			}
		}
		self::assertSame( array(), $this->groups->groups );
	}

	public function test_a_name_another_group_has_is_refused_without_regard_to_case(): void {
		$this->service()->create( 'Best Shoes', '' );

		try {
			$this->service()->create( 'best shoes', '' );
			self::fail( 'A taken name must be refused.' );
		} catch ( InvalidField $error ) {
			self::assertSame( 'name', $error->field );
			self::assertSame( 'group_name_taken', $error->reason );
		}
		self::assertCount( 1, $this->groups->groups );
	}

	public function test_a_group_is_found_by_its_id_and_an_unknown_id_is_none(): void {
		$made = $this->service()->create( 'Best Shoes', '' );

		self::assertSame( 'Best Shoes', $this->service()->get( $made->id )?->name );
		self::assertNull( $this->service()->get( 999 ) );
		self::assertNull( $this->service()->get( 0 ) );
	}

	public function test_the_list_asks_for_ten_a_page_and_never_below_page_one(): void {
		$this->service()->list( SearchTerm::from_input( 'wave  momentum' ), 0 );

		self::assertSame( array( 'wave momentum', 1, 10 ), $this->groups->asked );
	}

	public function test_the_list_is_newest_first(): void {
		$this->service()->create( 'First', '' );
		$this->service()->create( 'Second', '' );

		$page = $this->service()->list( SearchTerm::from_input( '' ), 1 );

		self::assertSame( array( 'Second', 'First' ), array_map( static fn( $group ) => $group->name, $page->items ) );
	}
}
