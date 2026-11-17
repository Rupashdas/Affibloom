<?php
namespace Affibloom\Tests\Unit\Interface\Hooks;

use Affibloom\Application\LinkIndex\ContentSource;
use Affibloom\Application\LinkIndex\DisplayResolver;
use Affibloom\Application\LinkIndex\LinkIndexer;
use Affibloom\Application\LinkIndex\LinkScanner;
use Affibloom\Application\LinkIndex\ProductResolver;
use Affibloom\Domain\LinkIndex\HostContent;
use Affibloom\Domain\LinkIndex\LinkClassifier;
use Affibloom\Domain\LinkIndex\LinkIndexStats;
use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Infrastructure\WordPress\HostQuery;
use Affibloom\Interface\Hooks\LinkIndexHooks;
use Affibloom\Tests\Unit\Fakes\InMemoryGroupRepository;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;
use WP_Post;

/**
 * A post's rows follow the post (F21 R10-R12, D5, D6, edge case 1; Task 35C).
 */
final class LinkIndexHooksTest extends TestCase {

	/** @var list<string> What the index was asked: "replace 7", "delete 7". */
	public array $calls = array();

	/** @var list<int> The hosts the event was fired for. */
	public array $events = array();

	private function hooks( bool $indexed_status = true, array $types = array() ): LinkIndexHooks {
		$test    = $this;
		$content = new class( $indexed_status ) implements ContentSource {
			public function __construct( private bool $indexed ) {}
			public function read( int $host_id ): ?HostContent {
				return $this->indexed ? new HostContent( $host_id, 'https://site.test/p/', array( 'content' => '' ) ) : null;
			}
		};
		$scanner = new class() implements LinkScanner {
			public function scan( string $html ): array {
				return array();
			}
			public function displays( string $html ): array {
				return array();
			}
		};
		$rows    = new class( $test ) implements LinkLocationRepository {
			public function __construct( private LinkIndexHooksTest $test ) {}
			public function replace_host( int $host_id, array $rows ): void {
				$this->test->calls[] = 'replace ' . $host_id;
			}
			public function delete_host( int $host_id ): void {
				$this->test->calls[] = 'delete ' . $host_id;
			}
			public function stats(): LinkIndexStats {
				return new LinkIndexStats();
			}
			public function host_ids(): array {
				return array();
			}
		};
		$products = new InMemoryProductRepository();
		$links    = new ProductResolver( $products );
		$indexer  = new LinkIndexer(
			$content,
			$scanner,
			$links,
			new DisplayResolver( $products, new InMemoryGroupRepository(), $links ),
			new LinkClassifier( 'https://site.test' ),
			$rows,
			function ( int $host ) use ( $test ): void {
				$test->events[] = $host;
			}
		);

		return new LinkIndexHooks( $indexer, $rows, new HostQuery( new InMemorySettingsRepository( array( 'scan_post_types' => $types ) ) ) );
	}

	private static function post( int $id, string $type = 'post' ): WP_Post {
		$post            = new WP_Post( (object) array() );
		$post->ID        = $id;
		$post->post_type = $type;
		return $post;
	}

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		// No unit test loads WordPress; the hooks only read a post's ID and type.
		if ( ! class_exists( WP_Post::class ) ) {
			eval( 'final class WP_Post { public $ID = 0; public $post_type = "post"; public function __construct( $post ) {} }' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- a test double for a WordPress class.
		}
	}

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_is_post_revision' )->justReturn( false );
		Functions\when( 'wp_is_post_autosave' )->justReturn( false );
	}

	public function test_a_saved_post_is_indexed_once_per_request_and_the_event_fires(): void {
		$hooks = $this->hooks();
		$hooks->saved( 7, self::post( 7 ) );
		$hooks->saved( 7, self::post( 7 ) );

		self::assertSame( array( 'replace 7' ), $this->calls );
		self::assertSame( array( 7 ), $this->events );
	}

	public function test_revisions_autosaves_and_other_types_are_not_hosts(): void {
		Functions\when( 'wp_is_post_revision' )->alias( static fn( int $id ): bool => 8 === $id );
		$hooks = $this->hooks();
		$hooks->saved( 8, self::post( 8 ) );
		$hooks->saved( 9, self::post( 9, 'affibloom_product' ) );

		self::assertSame( array(), $this->calls );
	}

	public function test_a_trashed_post_loses_its_rows_and_a_deleted_one_always_does(): void {
		Functions\when( 'get_post' )->alias( static fn( int $id ): WP_Post => self::post( $id ) );
		$hooks = $this->hooks( false );
		$hooks->changed( 7 );
		$hooks->deleted( 7 );

		self::assertSame( array( 'delete 7', 'delete 7' ), $this->calls );
		self::assertSame( array(), $this->events );
	}

	public function test_a_chosen_custom_post_type_is_a_host(): void {
		$this->hooks( true, array( 'recipe' ) )->saved( 9, self::post( 9, 'recipe' ) );

		self::assertSame( array( 'replace 9' ), $this->calls );
	}
}
