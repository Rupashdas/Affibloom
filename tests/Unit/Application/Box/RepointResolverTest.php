<?php
namespace Affibloom\Tests\Unit\Application\Box;

use Affibloom\Application\Box\RepointResolver;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use PHPUnit\Framework\TestCase;

/**
 * Which product a box draws (Phase 3 decisions, "Kept from old Task 18A"; F1 D14, X10, D41, D43): a published product
 * draws itself; a repointed one is followed, whatever its status, up to 50 hops, stopping at a target that no longer
 * exists; the product reached is drawn only when it is published.
 */
final class RepointResolverTest extends TestCase {

	private InMemoryProductRepository $products;

	protected function setUp(): void {
		$this->products = new InMemoryProductRepository();
	}

	private function add( string $name ): Product {
		return $this->products->save( new Product( null, $name, AffiliateUrl::from_string( 'https://www.example.com/' . $name ), ProductType::Other, true, true, false, true ) );
	}

	private function resolve( int $id ): ?string {
		return ( new RepointResolver( $this->products ) )->product( $id )?->name;
	}

	public function test_a_published_product_draws_itself(): void {
		self::assertSame( 'shoe', $this->resolve( (int) $this->add( 'shoe' )->id ) );
	}

	public function test_a_missing_product_draws_nothing(): void {
		self::assertNull( $this->resolve( 999 ) );
	}

	public function test_a_retired_product_draws_the_one_it_points_to_across_hops(): void {
		$first  = $this->add( 'first' );
		$second = $this->add( 'second' );
		$third  = $this->add( 'third' );
		$this->products->retire( $first, (int) $second->id );
		$this->products->retire( $second, (int) $third->id );

		self::assertSame( 'third', $this->resolve( (int) $first->id ) );
	}

	/**
	 * A target deleted for good: the resolver stops at the retired product, which is not published.
	 */
	public function test_a_pointer_to_a_deleted_product_draws_nothing(): void {
		$old = $this->add( 'old' );
		$new = $this->add( 'new' );
		$this->products->retire( $old, (int) $new->id );
		$this->products->delete( (int) $new->id );

		self::assertNull( $this->resolve( (int) $old->id ) );
	}

	public function test_a_loop_of_pointers_draws_nothing(): void {
		$a = $this->add( 'a' );
		$b = $this->add( 'b' );
		$this->products->retire( $a, (int) $b->id );
		$this->products->retire( $b, (int) $a->id );

		self::assertNull( $this->resolve( (int) $a->id ) );
	}
}
