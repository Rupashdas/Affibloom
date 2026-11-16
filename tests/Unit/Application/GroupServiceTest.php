<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Groups\GroupService;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Application\Groups\AlreadyInGroup;
use Affibloom\Application\Groups\GroupInUse;
use Affibloom\Application\Groups\GroupNotFound;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Fakes\InMemoryGroupRepository;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use PHPUnit\Framework\TestCase;

final class GroupServiceTest extends TestCase {
	private InMemoryGroupRepository $groups;
	private InMemoryProductRepository $products;

	protected function setUp(): void {
		parent::setUp();
		$this->groups   = new InMemoryGroupRepository();
		$this->products = new InMemoryProductRepository();
	}

	private function service(): GroupService {
		return new GroupService( $this->groups, $this->products );
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

	private function product( string $name ): int {
		return (int) $this->products->save( Product::draft( $name, AffiliateUrl::from_string( 'https://www.example.com/' . strtolower( $name ) ), Settings::from_array( array() ) ) )->id;
	}

	/** @param list<\Affibloom\Domain\Product\Product> $items */
	private function ids( array $items ): array {
		return array_map( static fn( $p ) => (int) $p->id, $items );
	}

	public function test_an_update_keeps_the_slug_and_refuses_another_groups_name(): void {
		$a = $this->service()->create( 'Shoes', '' );
		$b = $this->service()->create( 'Pads', '' );

		$changed = $this->service()->update( $a->id, 'Best Shoes', 'New text' );
		self::assertSame( 'shoes', $changed->slug );
		self::assertSame( 'Best Shoes', $changed->name );
		// Saving a group under its own name is not a clash.
		self::assertSame( 'Pads', $this->service()->update( $b->id, 'Pads', 'x' )->name );

		$this->expectException( InvalidField::class );
		$this->service()->update( $b->id, 'best shoes', '' );
	}

	public function test_updating_or_deleting_an_unknown_group_is_not_found(): void {
		$this->expectException( GroupNotFound::class );
		$this->service()->update( 99, 'X', '' );
	}

	public function test_a_group_with_products_cannot_be_deleted_and_an_empty_one_can(): void {
		$group = $this->service()->create( 'Shoes', '' );
		$this->service()->add( $group->id, $this->product( 'One' ) );

		try {
			$this->service()->delete( $group->id );
			self::fail( 'A group in use must not be deleted.' );
		} catch ( GroupInUse $in_use ) {
			self::assertNotNull( $this->groups->find( $group->id ) );
		}

		$empty = $this->service()->create( 'Empty', '' );
		$this->service()->delete( $empty->id );
		self::assertNull( $this->groups->find( $empty->id ) );
	}

	public function test_a_new_member_comes_last_and_a_member_twice_is_refused(): void {
		$group = $this->service()->create( 'Shoes', '' );
		$one   = $this->product( 'One' );
		$two   = $this->product( 'Two' );
		$this->service()->add( $group->id, $one );
		$this->service()->add( $group->id, $two );

		self::assertSame( array( $one, $two ), $this->ids( $this->service()->members( $group->id ) ) );
		$this->expectException( AlreadyInGroup::class );
		$this->service()->add( $group->id, $one );
	}

	public function test_reorder_and_remove_change_the_list_not_the_products(): void {
		$group = $this->service()->create( 'Shoes', '' );
		$one   = $this->product( 'One' );
		$two   = $this->product( 'Two' );
		$three = $this->product( 'Three' );
		foreach ( array( $one, $two, $three ) as $id ) {
			$this->service()->add( $group->id, $id );
		}

		$this->service()->reorder( $group->id, array( $three, $one, 999 ) );
		// 999 is no member and is ignored; the member left out comes last.
		self::assertSame( array( $three, $one, $two ), $this->ids( $this->service()->members( $group->id ) ) );

		$this->service()->remove( $group->id, array( $one ) );
		self::assertSame( array( $three, $two ), $this->ids( $this->service()->members( $group->id ) ) );
		self::assertNotNull( $this->products->get( $one ) );
		self::assertSame( 2, $this->service()->get( $group->id )?->count );
	}

	public function test_resolve_takes_ids_and_names_and_makes_new_groups(): void {
		$shoes = $this->service()->create( 'Shoes', '' );

		$ids = $this->service()->resolve( array( $shoes->id, 'shoes', 'Fresh Group', 999, '' ) );

		self::assertSame( $shoes->id, $ids[0] );
		self::assertCount( 2, $ids );
		self::assertSame( 'Fresh Group', $this->groups->find( $ids[1] )?->name );
	}

	public function test_a_products_groups_become_exactly_the_chosen_ones(): void {
		$a       = $this->service()->create( 'A', '' );
		$b       = $this->service()->create( 'B', '' );
		$product = $this->product( 'One' );
		$other   = $this->product( 'Other' );
		$this->service()->add( $a->id, $other );

		$this->service()->set_for_product( $product, array( $a->id, $b->id ) );
		self::assertSame( array( $other, $product ), $this->ids( $this->service()->members( $a->id ) ) );

		$this->service()->set_for_product( $product, array( $b->id ) );
		self::assertSame( array( $other ), $this->ids( $this->service()->members( $a->id ) ) );
		self::assertSame( array( $other ), $this->groups->saved_order( $a->id ) );
		self::assertSame( array( $product ), $this->ids( $this->service()->members( $b->id ) ) );
	}

	public function test_append_adds_groups_and_keeps_the_ones_a_product_has(): void {
		$a   = $this->service()->create( 'A', '' );
		$b   = $this->service()->create( 'B', '' );
		$one = $this->product( 'One' );
		$two = $this->product( 'Two' );
		$this->service()->add( $a->id, $one );

		$given = $this->service()->append( array( $one, $two, 999 ), array( $b->id ) );

		self::assertSame( 2, $given );
		self::assertSame( array( $one ), $this->ids( $this->service()->members( $a->id ) ) );
		self::assertSame( array( $one, $two ), $this->ids( $this->service()->members( $b->id ) ) );
	}

	/**
	 * F30 R6, R15, D2 (Task DT2): a group display's products by the group's slug, in its order or newest first, the
	 * first `limit`; an unknown slug shows none.
	 */
	public function test_a_group_display_shows_its_products_by_slug(): void {
		$group = $this->service()->create( 'Best Shoes', '' );
		$one   = $this->product( 'One' );
		$two   = $this->product( 'Two' );
		$three = $this->product( 'Three' );
		foreach ( array( $two, $one, $three ) as $id ) {
			$this->service()->add( $group->id, $id );
		}

		self::assertSame( array( $two, $one, $three ), $this->ids( $this->service()->shown( 'best-shoes', null, false ) ) );
		self::assertSame( array( $two, $one ), $this->ids( $this->service()->shown( 'best-shoes', 2, false ) ) );
		self::assertSame( array( $three, $two, $one ), $this->ids( $this->service()->shown( 'best-shoes', null, true ) ) );
		self::assertSame( array(), $this->service()->shown( 'unknown', null, false ) );
	}
}
