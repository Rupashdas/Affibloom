<?php
namespace Affibloom\Tests\Unit\Application\LinkIndex;

use Affibloom\Application\LinkIndex\ContentSource;
use Affibloom\Application\LinkIndex\DisplayResolver;
use Affibloom\Application\LinkIndex\Hosts;
use Affibloom\Application\LinkIndex\LinkIndexer;
use Affibloom\Application\LinkIndex\LinkIndexRebuild;
use Affibloom\Application\LinkIndex\LinkScanner;
use Affibloom\Application\LinkIndex\ProductResolver;
use Affibloom\Application\Products\BackgroundJob;
use Affibloom\Domain\LinkIndex\DisplayCandidate;
use Affibloom\Domain\LinkIndex\HostContent;
use Affibloom\Domain\LinkIndex\LinkCandidate;
use Affibloom\Domain\LinkIndex\LinkClassifier;
use Affibloom\Domain\LinkIndex\LinkIndexStats;
use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Domain\LinkIndex\RebuildState;
use Affibloom\Domain\LinkIndex\RebuildStateRepository;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Tests\Unit\Fakes\InMemoryGroupRepository;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use PHPUnit\Framework\TestCase;

/**
 * One host's rows and the rebuild (F21 R25, R36, D4, D8; Task 35A).
 */
final class LinkIndexerTest extends TestCase {

	/** @var array<int, list<\Affibloom\Domain\LinkIndex\LinkLocation>> */
	private array $stored = array();

	/** @var list<int> */
	private array $deleted = array();

	private InMemoryProductRepository $products;

	protected function setUp(): void {
		parent::setUp();
		$this->products = new InMemoryProductRepository();
	}

	private function indexer(): LinkIndexer {
		$content = new class() implements ContentSource {
			public function read( int $host_id ): ?HostContent {
				return 7 === $host_id ? new HostContent( 7, 'https://site.test/p/', array( 'content' => 'a b a' ) ) : null;
			}
		};
		$scanner = new class() implements LinkScanner {
			public function scan( string $html ): array {
				return array_map( static fn( string $name ): LinkCandidate => new LinkCandidate( 'https://shop.test/' . $name ), explode( ' ', $html ) );
			}
			public function displays( string $html ): array {
				return array();
			}
		};
		$test = $this;
		$rows = new class( $test ) implements LinkLocationRepository {
			public function __construct( private LinkIndexerTest $test ) {}
			public function replace_host( int $host_id, array $rows ): void {
				$this->test->stored( $host_id, $rows );
			}
			public function delete_host( int $host_id ): void {
				$this->test->deleted( $host_id );
			}
			public function stats(): LinkIndexStats {
				return new LinkIndexStats();
			}
			public function product_locations( int $product_id, string $search, string $order, int $page, int $per_page ): \Affibloom\Domain\LinkIndex\LocationPage {
				return new \Affibloom\Domain\LinkIndex\LocationPage( array(), 0, 1 );
			}

			public function find( int $id ): ?\Affibloom\Domain\LinkIndex\LinkLocation {
				return null;
			}

			public function counts_for( array $product_ids ): array {
				return array();
			}

			public function host_links( int $host_id ): array {
				return array();
			}
			public function pin_product( int $product_id ): void {}
			public function move_product( int $from, int $to ): void {}
			public function host_ids(): array {
				return array();
			}
		};

		$links = new ProductResolver( $this->products );

		return new LinkIndexer( $content, $scanner, $links, new DisplayResolver( $this->products, new InMemoryGroupRepository(), $links ), new LinkClassifier( 'https://site.test' ), $rows );
	}

	/** @param list<\Affibloom\Domain\LinkIndex\LinkLocation> $rows */
	public function stored( int $host_id, array $rows ): void {
		$this->stored[ $host_id ] = $rows;
	}

	public function deleted( int $host_id ): void {
		$this->deleted[] = $host_id;
	}

	private function product( string $url, ProductType $type = ProductType::Other, string $asin = '' ): int {
		return (int) $this->products->save( new Product( null, 'P', AffiliateUrl::from_string( $url ), $type, true, true, false, true, $asin, '' !== $asin ? 'us' : '' ) )->id;
	}

	public function test_a_host_s_links_are_tied_numbered_and_stored(): void {
		$id   = $this->product( 'https://shop.test/a/' );
		$rows = $this->indexer()->index( 7 );

		self::assertSame( array( $id, 0, $id ), array_map( static fn( $row ): int => $row->product_id, (array) $rows ) );
		self::assertSame( array( 1, 1, 2 ), array_map( static fn( $row ): int => $row->occurrence, (array) $rows ) );
		self::assertCount( 3, $this->stored[7] );
	}

	public function test_a_dry_run_writes_nothing(): void {
		$this->indexer()->index( 7, true );
		$this->indexer()->index( 8, true );

		self::assertSame( array(), $this->stored );
		self::assertSame( array(), $this->deleted );
	}

	public function test_a_post_that_is_not_indexed_loses_its_rows(): void {
		self::assertNull( $this->indexer()->index( 8 ) );
		self::assertSame( array( 8 ), $this->deleted );
	}

	public function test_the_resolver_finds_a_product_by_its_link_its_plain_link_then_its_asin(): void {
		$plain  = $this->product( 'https://shop.test/plain?ref=1' );
		$amazon = $this->product( 'https://www.amazon.com/dp/B0F21TEST1', ProductType::Amazon, 'B0F21TEST1' );
		$found  = new ProductResolver( $this->products );

		self::assertSame( $plain, $found->resolve( 'https://shop.test/plain?utm=x' ) );
		self::assertSame( $amazon, $found->resolve( 'https://www.amazon.com/Some-Name/dp/B0F21TEST1?tag=x-20' ) );
		self::assertSame( 0, $found->resolve( 'not a link' ) );
		self::assertSame( 0, $found->resolve( 'https://elsewhere.test/' ) );
	}

	public function test_the_rebuild_counts_hosts_links_and_tied_products(): void {
		$this->product( 'https://shop.test/b' );
		$hosts = new class() implements Hosts {
			public function ids(): array {
				return array( 7, 8 );
			}
			public function changed_since( string $gmt ): array {
				return array();
			}
		};
		$stats = $this->rebuild( $hosts )->run_inline();

		self::assertSame( array( 1, 3, 1, 1, array( 'external' => 2, 'product' => 1 ) ), array( $stats->hosts, $stats->links, $stats->tied, $stats->products, $stats->by_type ) );
	}

	public function test_a_display_s_product_is_found_by_slug_then_id_then_link_and_its_group_by_id_or_slug(): void {
		$groups  = new InMemoryGroupRepository();
		$group   = $groups->create( 'Shoes', '' );
		$id      = (int) $this->products->save( ( new Product( null, 'P', AffiliateUrl::from_string( 'https://shop.test/p' ), ProductType::Other, true, true, false, true ) )->with( array( 'slug' => 'my-p' ) ) )->id;
		$resolve = new DisplayResolver( $this->products, $groups, new ProductResolver( $this->products ) );

		self::assertSame( $id, $resolve->product( new DisplayCandidate( 'Single', 'x', 999, 'my-p' ) ) );
		self::assertSame( $id, $resolve->product( new DisplayCandidate( 'Single', 'x', $id ) ) );
		self::assertSame( $id, $resolve->product( new DisplayCandidate( 'Single', 'x', 0, '', 'https://shop.test/p' ) ) );
		self::assertSame( 0, $resolve->product( new DisplayCandidate( 'Single', 'x', 999 ) ) );
		self::assertSame( 'group:' . $group?->id, $resolve->reference( new DisplayCandidate( 'Grid', 'x', 0, '', '', (int) $group?->id ) ) );
		self::assertSame( 'group:' . $group?->id, $resolve->reference( new DisplayCandidate( 'Grid', 'x', 0, '', '', 0, (string) $group?->slug ) ) );
		self::assertNull( $resolve->reference( new DisplayCandidate( 'Grid', 'x', 0, '', '', 0, 'gone' ) ) );
	}

	public ?RebuildState $state = null;

	public int $dispatched = 0;

	/** @var list<int> */
	public array $stored_hosts = array();

	/** @param list<int> $ids */
	private function rebuild( Hosts $hosts ): LinkIndexRebuild {
		$test  = $this;
		$state = new class( $test ) implements RebuildStateRepository {
			public function __construct( private LinkIndexerTest $test ) {}
			public function get(): RebuildState {
				return $this->test->state ?? new RebuildState();
			}
			public function save( RebuildState $state ): void {
				$this->test->state = $state;
			}
		};
		$job   = new class( $test ) implements BackgroundJob {
			public function __construct( private LinkIndexerTest $test ) {}
			public function dispatch( int $delay = 0 ): void {
				++$this->test->dispatched;
			}
		};
		$rows  = new class( $test ) implements LinkLocationRepository {
			public function __construct( private LinkIndexerTest $test ) {}
			public function replace_host( int $host_id, array $rows ): void {}
			public function delete_host( int $host_id ): void {
				$this->test->deleted( $host_id );
			}
			public function pin_product( int $product_id ): void {}
			public function move_product( int $from, int $to ): void {}
			public function host_ids(): array {
				return $this->test->stored_hosts;
			}
			public function stats(): LinkIndexStats {
				return new LinkIndexStats();
			}
			public function product_locations( int $product_id, string $search, string $order, int $page, int $per_page ): \Affibloom\Domain\LinkIndex\LocationPage {
				return new \Affibloom\Domain\LinkIndex\LocationPage( array(), 0, 1 );
			}

			public function find( int $id ): ?\Affibloom\Domain\LinkIndex\LinkLocation {
				return null;
			}

			public function counts_for( array $product_ids ): array {
				return array();
			}

			public function host_links( int $host_id ): array {
				return array();
			}
		};

		return new LinkIndexRebuild( $hosts, $this->indexer(), $state, $job, $rows );
	}

	/** @param list<int> $ids */
	private static function hosts( array $ids ): Hosts {
		return new class( $ids ) implements Hosts {
			/** @param list<int> $ids */
			public function __construct( private array $ids ) {}
			public function ids(): array {
				return $this->ids;
			}
			public function changed_since( string $gmt ): array {
				return array( 7 );
			}
		};
	}

	public function test_lassos_batch_sizes(): void {
		self::assertSame( array( 100, 100, 250, 250, 200, 221, 500 ), array_map( array( LinkIndexRebuild::class, 'batch_size' ), array( 1, 180, 500, 499, 501, 1105, 9000 ) ) );
	}

	public function test_a_background_run_goes_batch_by_batch_and_resumes_from_its_count(): void {
		$rebuild = $this->rebuild( self::hosts( range( 1, 250 ) ) );

		self::assertTrue( $rebuild->start() );
		self::assertFalse( $rebuild->start() );
		self::assertSame( array( 'running', 0, 250, 1 ), array( $this->state?->status, $this->state?->done, $this->state?->total, $this->dispatched ) );

		self::assertTrue( $rebuild->next_batch() );
		self::assertSame( 125, $this->state?->done );
		self::assertFalse( $rebuild->next_batch() );
		self::assertSame( array( 'idle', 250, 2 ), array( $this->state?->status, $this->state?->last['hosts'], $this->dispatched ) );
		self::assertFalse( $rebuild->next_batch() );
	}

	public function test_the_daily_pass_drops_hosts_that_are_gone_and_indexes_changed_ones(): void {
		$this->stored_hosts = array( 7, 99 );
		$this->rebuild( self::hosts( array( 7 ) ) )->daily();

		self::assertSame( array( 99 ), $this->deleted );
		self::assertCount( 3, $this->stored[7] ?? array() );
	}
}
