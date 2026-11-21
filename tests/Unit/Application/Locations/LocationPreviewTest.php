<?php
namespace Affibloom\Tests\Unit\Application\Locations;

use Affibloom\Application\LinkIndex\ContentSource;
use Affibloom\Application\Locations\LocationPreview;
use Affibloom\Domain\LinkIndex\HostContent;
use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Tests\Unit\Fakes\InMemoryLinkLocations;
use PHPUnit\Framework\TestCase;

/**
 * Link with Context (F12 L10, D24; Task 36C).
 */
final class LocationPreviewTest extends TestCase {

	private InMemoryLinkLocations $rows;

	private LocationPreview $preview;

	protected function setUp(): void {
		$this->rows = new InMemoryLinkLocations();
		$this->rows->replace_host(
			10,
			array(
				self::row( 'Text', 'https://example.com/a', 2, 'meta:review' ),
				self::row( 'Grid', '[affibloom type="grid" group="shoes"]', 1, 'content' ),
			)
		);
		$content       = new class() implements ContentSource {
			public function read( int $host_id ): ?HostContent {
				return 10 === $host_id ? new HostContent(
					10,
					'https://example.test/?p=10',
					array(
						'content'     => '<p>[affibloom type="grid" group="shoes"]</p>',
						'meta:review' => '<p>One <a href="https://example.com/a">a</a>.</p><p>Two <a href="https://example.com/a">a</a>.</p>',
					)
				) : null;
			}
		};
		$this->preview = new LocationPreview( $this->rows, $content );
	}

	public function test_a_link_comes_with_its_paragraph_from_its_own_source(): void {
		$found = $this->preview->of( 1 );

		self::assertNotNull( $found );
		self::assertFalse( $found['display'] );
		self::assertSame( 'Two <a href="https://example.com/a" class="affibloom-preview__hit">a</a>.', $found['snippet'] );
	}

	public function test_a_display_is_left_to_the_caller_and_a_gone_row_is_null(): void {
		$found = $this->preview->of( 2 );

		self::assertNotNull( $found );
		self::assertTrue( $found['display'] );
		self::assertNull( $found['snippet'] );
		self::assertNull( $this->preview->of( 99 ) );
	}

	private static function row( string $type, string $url, int $occurrence, string $source ): LinkLocation {
		return new LinkLocation(
			host_id: 10,
			host_url: 'https://example.test/?p=10',
			source: $source,
			occurrence: $occurrence,
			product_id: 7,
			link_type: 'product',
			display_type: $type,
			url: $url,
			url_md5: md5( $url ),
			detected_at: '2026-11-21 10:00:00',
		);
	}
}
