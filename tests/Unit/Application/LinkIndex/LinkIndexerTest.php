<?php
namespace Affibloom\Tests\Unit\Application\LinkIndex;

use Affibloom\Application\LinkIndex\ContentSource;
use Affibloom\Application\LinkIndex\Hosts;
use Affibloom\Application\LinkIndex\LinkIndexer;
use Affibloom\Application\LinkIndex\LinkIndexRebuild;
use Affibloom\Application\LinkIndex\LinkScanner;
use Affibloom\Application\LinkIndex\ProductResolver;
use Affibloom\Domain\LinkIndex\HostContent;
use Affibloom\Domain\LinkIndex\LinkCandidate;
use Affibloom\Domain\LinkIndex\LinkClassifier;
use Affibloom\Domain\LinkIndex\LinkIndexStats;
use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;
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
		};

		return new LinkIndexer( $content, $scanner, new ProductResolver( $this->products ), new LinkClassifier( 'https://site.test' ), $rows );
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
		};
		$stats = ( new LinkIndexRebuild( $hosts, $this->indexer() ) )->run_inline();

		self::assertSame( array( 1, 3, 1, 1, array( 'external' => 2, 'product' => 1 ) ), array( $stats->hosts, $stats->links, $stats->tied, $stats->products, $stats->by_type ) );
	}
}
