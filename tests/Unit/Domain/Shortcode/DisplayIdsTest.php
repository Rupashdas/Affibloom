<?php
namespace Affibloom\Tests\Unit\Domain\Shortcode;

use Affibloom\Domain\Shortcode\DisplayIds;
use PHPUnit\Framework\TestCase;

/**
 * Task 37B: the products a post's single boxes will draw, read from its content before the blocks and shortcodes
 * render, so they can be loaded in one go.
 */
final class DisplayIdsTest extends TestCase {

	public function test_blocks_and_shortcodes_give_their_products_once_each(): void {
		$content = '<!-- wp:affibloom/product-box {"productId":12} /-->'
			. '<p>Text</p><!-- wp:affibloom/product-box {"productId":34,"type":"button","overrides":{"badge":"Best"}} /-->'
			. "<!-- wp:shortcode -->\n[affibloom id=\"56\" badge=\"Runner Up\"]\n<!-- /wp:shortcode -->"
			. "[affibloom id='12'] [affibloom type=\"image\" id=78]";

		self::assertSame( array( 12, 34, 56, 78 ), DisplayIds::products( $content ) );
	}

	public function test_groups_tables_and_other_text_give_none(): void {
		$content = '<!-- wp:affibloom/product-box {"type":"grid","groupId":5} /-->'
			. '<!-- wp:affibloom/product-box {"type":"table","tableId":20787} /-->'
			. '[affibloom type="grid" group="pair-grid"] [affibloom type="table" id="20787"] [affibloom_other id="9"]'
			. '<!-- wp:paragraph --><p>[lasso id="9701"]</p><!-- /wp:paragraph -->';

		self::assertSame( array(), DisplayIds::products( $content ) );
	}
}
