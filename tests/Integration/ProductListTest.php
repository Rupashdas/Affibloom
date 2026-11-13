<?php
namespace Affibloom\Tests\Integration;

use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Infrastructure\WordPress\PostTypeProductRepository;
use WP_UnitTestCase;

/**
 * The Dashboard list's query (Tasks 13A and 13B): published only, most recently changed first, paged,
 * and Lasso's search over the name, the link, the ASIN and the product's ID.
 * Runs only where wp-env is available (progress.md, deviation 10).
 */
final class ProductListTest extends WP_UnitTestCase {

	/**
	 * Three products changed a day apart, plus a draft and a trashed one that must not appear.
	 *
	 * @return array<string, int> Name to post ID.
	 */
	private function seed(): array {
		$ids  = array();
		$when = array(
			'oldest' => '2026-01-01 00:00:00',
			'middle' => '2026-01-02 00:00:00',
			'newest' => '2026-01-03 00:00:00',
		);
		foreach ( $when as $name => $modified ) {
			$ids[ $name ] = self::factory()->post->create(
				array(
					'post_type'         => 'affibloom_product',
					'post_status'       => 'publish',
					'post_title'        => ucfirst( $name ) . ' product',
					'post_date'         => '2026-01-01 00:00:00',
					'post_modified'     => $modified,
					'post_modified_gmt' => $modified,
					'meta_input'        => array( '_affibloom_url' => "https://www.example.com/$name" ),
				)
			);
		}

		foreach ( array( 'draft', 'trash' ) as $status ) {
			self::factory()->post->create(
				array(
					'post_type'   => 'affibloom_product',
					'post_status' => $status,
					'post_title'  => "Hidden $status",
					'meta_input'  => array( '_affibloom_url' => "https://www.example.com/$status" ),
				)
			);
		}

		return $ids;
	}

	public function test_published_products_come_back_most_recently_changed_first(): void {
		$this->seed();

		$page = ( new PostTypeProductRepository() )->page( SearchTerm::from_input( '' ), LinkKind::All, 1, 10 );

		self::assertSame( 3, $page->total );
		self::assertSame(
			array( 'Newest product', 'Middle product', 'Oldest product' ),
			array_map( static fn( $product ) => $product->name, $page->items )
		);
	}

	public function test_a_page_holds_only_its_own_products(): void {
		$this->seed();

		$first  = ( new PostTypeProductRepository() )->page( SearchTerm::from_input( '' ), LinkKind::All, 1, 2 );
		$second = ( new PostTypeProductRepository() )->page( SearchTerm::from_input( '' ), LinkKind::All, 2, 2 );

		self::assertCount( 2, $first->items );
		self::assertCount( 1, $second->items );
		self::assertSame( 3, $second->total );
		self::assertSame( 2, $second->pages() );
		self::assertSame( 'Oldest product', $second->items[0]->name );
	}

	public function test_a_page_past_the_end_is_empty_but_keeps_the_total(): void {
		$this->seed();

		$page = ( new PostTypeProductRepository() )->page( SearchTerm::from_input( '' ), LinkKind::All, 99, 10 );

		self::assertTrue( $page->is_empty() );
		self::assertSame( 3, $page->total );
		self::assertSame( 1, $page->first() );
		self::assertSame( 3, $page->last() );
	}

	public function test_the_search_matches_the_name_the_link_and_the_asin(): void {
		$ids = $this->seed();
		update_post_meta( $ids['middle'], '_affibloom_asin', 'B0CPH9NPK8' );

		$repository = new PostTypeProductRepository();

		self::assertSame( 1, $repository->page( SearchTerm::from_input( 'Newest' ), LinkKind::All, 1, 10 )->total );
		self::assertSame( 1, $repository->page( SearchTerm::from_input( 'example.com/oldest' ), LinkKind::All, 1, 10 )->total );
		self::assertSame( 1, $repository->page( SearchTerm::from_input( 'b0cph9npk8' ), LinkKind::All, 1, 10 )->total );
		self::assertSame( 1, $repository->page( SearchTerm::from_input( (string) $ids['oldest'] ), 1, 10 )->total );
		self::assertSame( 0, $repository->page( SearchTerm::from_input( 'nothing matches' ), LinkKind::All, 1, 10 )->total );
		self::assertSame( 3, $repository->page( SearchTerm::from_input( 'product' ), LinkKind::All, 1, 10 )->total );
	}

	/** The search runs for its own two queries only; the next query must be untouched. */
	public function test_the_search_does_not_leak_into_the_next_query(): void {
		$this->seed();
		$repository = new PostTypeProductRepository();

		$repository->page( SearchTerm::from_input( 'Newest' ), LinkKind::All, 1, 10 );

		self::assertSame( 3, $repository->page( SearchTerm::from_input( '' ), LinkKind::All, 1, 10 )->total );
		self::assertCount( 3, get_posts( array( 'post_type' => 'affibloom_product', 'fields' => 'ids' ) ) );
	}

	public function test_the_library_count_ignores_the_search(): void {
		$this->seed();

		self::assertSame( 3, ( new PostTypeProductRepository() )->count() );
	}

	public function test_the_link_type_filter_splits_amazon_from_the_rest(): void {
		$ids = $this->seed();
		update_post_meta( $ids['newest'], '_affibloom_type', 'amazon' );
		update_post_meta( $ids['middle'], '_affibloom_type', 'other' );
		delete_post_meta( $ids['oldest'], '_affibloom_type' ); // No type at all counts as not Amazon's.

		$repository = new PostTypeProductRepository();
		$all        = SearchTerm::from_input( '' );

		self::assertSame( 1, $repository->page( $all, LinkKind::Amazon, 1, 10 )->total );
		self::assertSame( 2, $repository->page( $all, LinkKind::NonAmazon, 1, 10 )->total );
		self::assertSame( 3, $repository->page( $all, LinkKind::All, 1, 10 )->total );
	}

	public function test_an_empty_library_has_one_page_and_no_range(): void {
		$page = ( new PostTypeProductRepository() )->page( SearchTerm::from_input( '' ), LinkKind::All, 1, 10 );

		self::assertSame( 0, $page->total );
		self::assertSame( 1, $page->pages() );
		self::assertSame( 0, $page->first() );
	}
}
