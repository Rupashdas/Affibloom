<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Monetize\AffiliateDetector;
use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Domain\Monetize\DetectedLink;
use Affibloom\Domain\Monetize\DetectedLinkRepository;
use Affibloom\Domain\Monetize\NetworkRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Recording a post's network links (F35 R10, R12, D3; C2 AC2, AC4, AC7; Task C2B).
 */
final class AffiliateDetectorTest extends TestCase {

	/** @var list<array{string, string, int}> */
	private array $recorded = array();

	private function detector(): AffiliateDetector {
		$repo = new class( $this->recorded ) implements DetectedLinkRepository {
			public function __construct( private array &$recorded ) {}

			public function record( string $url, DetectedLink $link, int $product_id ): void {
				$this->recorded[] = array( $url, $link->network->integration, $product_id );
			}
		};

		return new AffiliateDetector( NetworkRegistry::from_array( require dirname( __DIR__, 3 ) . '/resources/networks.php' ), $repo );
	}

	private static function row( string $url, string $type = 'external', string $display = 'Text', int $product = 0 ): LinkLocation {
		return new LinkLocation( 7, 'https://site.example/p/', 'content', 1, $product, $type, $display, $url, md5( $url ) );
	}

	public function test_network_links_are_recorded_once_and_untied_ones_counted(): void {
		$pending = $this->detector()->detect(
			array(
				self::row( 'https://mizunousa.sjv.io/jR4ZQP' ),
				self::row( 'https://mizunousa.sjv.io/jR4ZQP' ),
				self::row( 'https://www.awin1.com/cread.php?awinmid=1&awinaffid=2', 'product', 'Text', 40 ),
			)
		);

		self::assertSame( 1, $pending );
		self::assertSame(
			array( array( 'https://mizunousa.sjv.io/jR4ZQP', 'Impact', 0 ), array( 'https://www.awin1.com/cread.php?awinmid=1&awinaffid=2', 'Awin', 40 ) ),
			$this->recorded
		);
	}

	public function test_amazon_plain_internal_registry_only_and_displays_record_nothing(): void {
		$pending = $this->detector()->detect(
			array(
				self::row( 'https://www.amazon.com/dp/B0TEST0001' ),
				self::row( 'https://shop.example/item' ),
				self::row( 'https://site.example/about/', 'internal' ),
				self::row( 'https://everflow.io/abc' ),
				self::row( 'https://mizunousa.sjv.io/abc', 'external', 'Single Display' ),
			)
		);

		self::assertSame( 0, $pending );
		self::assertSame( array(), $this->recorded );
	}
}
