<?php
namespace Affibloom\Tests\Unit\Domain\LinkIndex;

use Affibloom\Domain\LinkIndex\LocationSnippet;
use PHPUnit\Framework\TestCase;

/**
 * Link with Context's paragraph (F12 L10, D24; Task 36C).
 */
final class LocationSnippetTest extends TestCase {

	public function test_the_paragraph_that_holds_the_nth_link_with_the_link_marked(): void {
		$html = '<!-- wp:paragraph --><p>First <a href="https://example.com/ball?tag=x&amp;y=1">ball</a>.</p><!-- /wp:paragraph -->'
			. '<ul><li>Then the <strong>second</strong> <a class="x" href="https://example.com/ball?tag=x&amp;y=1">ball, é</a> here.</li></ul>';

		self::assertSame( 'First <a href="https://example.com/ball?tag=x&amp;y=1" class="affibloom-preview__hit">ball</a>.', LocationSnippet::of( $html, 'https://example.com/ball?tag=x&y=1', 1 ) );
		self::assertSame( 'Then the <strong>second</strong> <a class="x affibloom-preview__hit" href="https://example.com/ball?tag=x&amp;y=1">ball, é</a> here.', LocationSnippet::of( $html, 'https://example.com/ball?tag=x&y=1', 2 ) );
	}

	public function test_a_link_outside_any_block_gives_the_text_around_it(): void {
		self::assertSame( 'Loose <a href="https://example.com/a" class="affibloom-preview__hit">a</a> text', LocationSnippet::of( 'Loose <a href="https://example.com/a">a</a> text', 'https://example.com/a', 1 ) );
	}

	public function test_nothing_when_the_content_no_longer_holds_the_link(): void {
		self::assertNull( LocationSnippet::of( '<p><a href="https://example.com/a">a</a></p>', 'https://example.com/a', 2 ) );
		self::assertNull( LocationSnippet::of( '<p>No links.</p>', 'https://example.com/a', 1 ) );
		self::assertNull( LocationSnippet::of( '', 'https://example.com/a', 1 ) );
	}
}
