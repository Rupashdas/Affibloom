<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Monetize\AmazonShortLinks;
use Affibloom\Application\Products\FetchedPage;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Domain\Monetize\ShortLinkRepository;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use PHPUnit\Framework\TestCase;

/**
 * Amazon short links resolved once (F35 R28, D14; F35 AC3; Task AM2).
 */
final class AmazonShortLinksTest extends TestCase {

	/** @var array<string, array{final: string, product: int, tries: int}> */
	private array $rows = array();

	private function map(): ShortLinkRepository {
		return new class( $this->rows ) implements ShortLinkRepository {
			public function __construct( private array &$rows ) {}

			public function find( string $short_url ): ?array {
				return $this->rows[ $short_url ] ?? null;
			}

			public function save( string $short_url, string $final_url, int $product, int $tries ): void {
				$this->rows[ $short_url ] = array(
					'final'   => $final_url,
					'product' => $product,
					'tries'   => $tries,
				);
			}
		};
	}

	public function test_a_short_link_is_asked_once_and_its_destination_kept(): void {
		$pages = new FakePageFetcher( array( 'https://amzn.to/abc' => new FetchedPage( 301, 'https://www.amazon.com/dp/B0F35AC3XX?tag=x-20', '' ) ) );
		$short = new AmazonShortLinks( $this->map(), new LinkResolver( $pages ) );

		self::assertSame( 'https://www.amazon.com/dp/B0F35AC3XX?tag=x-20', $short->resolve( 'https://amzn.to/abc' ) );
		self::assertSame( 'https://www.amazon.com/dp/B0F35AC3XX?tag=x-20', $short->resolve( 'https://amzn.to/abc' ) );
		self::assertCount( 1, $pages->asked );

		$short->made( 'https://amzn.to/abc', 12 );
		self::assertSame( 12, $this->rows['https://amzn.to/abc']['product'] );
	}

	public function test_a_link_that_leads_nowhere_is_tried_three_times_then_left(): void {
		$pages = new FakePageFetcher( array( 'https://amzn.to/gone' => null ) );
		$short = new AmazonShortLinks( $this->map(), new LinkResolver( $pages ) );

		for ( $i = 0; $i < 5; $i++ ) {
			self::assertNull( $short->resolve( 'https://amzn.to/gone' ) );
		}

		self::assertCount( 3, $pages->asked );
		self::assertSame( 3, $this->rows['https://amzn.to/gone']['tries'] );
	}
}
