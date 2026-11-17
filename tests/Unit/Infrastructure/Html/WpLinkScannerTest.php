<?php
namespace Affibloom\Tests\Unit\Infrastructure\Html;

use Affibloom\Domain\LinkIndex\LinkCandidate;
use Affibloom\Infrastructure\Html\WpLinkScanner;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * The anchors the index keeps (F21 R24, R25; Task 35A), read with WordPress's own HTML API and block parser, loaded
 * from this site's wp-includes (they need no other part of WordPress).
 */
final class WpLinkScannerTest extends TestCase {

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		$includes = dirname( __DIR__, 7 ) . '/wp-includes/';
		if ( ! class_exists( \WP_HTML_Tag_Processor::class, false ) ) {
			require_once $includes . 'class-wp-token-map.php';
			foreach ( array( 'html5-named-character-references', 'class-wp-html-attribute-token', 'class-wp-html-span', 'class-wp-html-text-replacement', 'class-wp-html-decoder', 'class-wp-html-tag-processor' ) as $file ) {
				require_once $includes . 'html-api/' . $file . '.php';
			}
		}
		if ( ! class_exists( \WP_Block_Parser::class, false ) ) {
			foreach ( array( 'class-wp-block-parser-block', 'class-wp-block-parser-frame', 'class-wp-block-parser' ) as $file ) {
				require_once $includes . $file . '.php';
			}
		}
	}

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'parse_blocks' )->alias( static fn( string $html ): array => ( new \WP_Block_Parser() )->parse( $html ) );
		Functions\when( '_doing_it_wrong' )->justReturn( null );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/** @return list<string> */
	private static function hrefs( string $html ): array {
		return array_map( static fn( LinkCandidate $link ): string => $link->href, ( new WpLinkScanner() )->scan( $html ) );
	}

	public function test_an_anchor_gives_its_attributes_text_and_image(): void {
		$links = ( new WpLinkScanner() )->scan( '<p><a href=" https://a.test/?x=1&amp;y=2 " rel="nofollow" target="_blank">Buy <b>now</b></a> <a href="https://b.test"><img src="i.jpg"></a></p>' );

		self::assertEquals(
			array(
				new LinkCandidate( 'https://a.test/?x=1&y=2', 'nofollow', '_blank', false, 'Buy now' ),
				new LinkCandidate( 'https://b.test', '', '', true, '' ),
			),
			$links
		);
	}

	public function test_hrefs_lasso_skips_are_skipped(): void {
		self::assertSame( array( 'https://kept.test' ), self::hrefs( '<a href="#x">a</a><a href="[c]">b</a><a href="MAILTO:a@b.c">c</a><a href="tel:1">d</a><a href="">e</a><a>f</a><a href="https://kept.test">g</a>' ) );
	}

	public function test_ignored_anchor_classes_and_ancestors_are_skipped(): void {
		$html = '<a class="x affibloom-scan-ignore" href="https://1.test">1</a>'
			. '<div class="schema-faq-section"><p><a href="https://2.test">2</a></p></div>'
			. '<a href="https://3.test">3</a>';

		self::assertSame( array( 'https://3.test' ), self::hrefs( $html ) );
	}

	public function test_ignored_blocks_are_left_out_whole_and_nested_blocks_kept(): void {
		$html = '<!-- wp:rank-math/faq-block --><div><a href="https://faq.test">F</a></div><!-- /wp:rank-math/faq-block -->'
			. '<!-- wp:group --><div><!-- wp:paragraph --><p><a href="https://inner.test">I</a></p><!-- /wp:paragraph --></div><!-- /wp:group -->'
			. '<p><a href="https://free.test">F</a></p>';

		self::assertSame( array( 'https://inner.test', 'https://free.test' ), self::hrefs( $html ) );
	}

	public function test_content_without_an_anchor_is_not_parsed(): void {
		self::assertSame( array(), self::hrefs( '<!-- wp:paragraph --><p>No links.</p><!-- /wp:paragraph -->' ) );
	}
}
