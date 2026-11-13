<?php
namespace Affibloom\Tests\Unit\Domain\Product;

use Affibloom\Domain\Product\PriceHtml;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's price rules before wp_kses (sanitize_price_html_for_storage, normalize_price_html_colors_for_storage).
 */
final class PriceHtmlTest extends TestCase {
	public function test_rgb_colours_become_hex(): void {
		self::assertSame(
			'<span style="color: #141414;">$29.99</span>',
			PriceHtml::normalised( '<span style="color: rgb(20, 20, 20);">$29.99</span>' )
		);
	}

	public function test_an_rgba_colour_is_blended_on_white(): void {
		// 0.5 * 0 + 0.5 * 255 = 127.5, rounded to 128 (0x80).
		self::assertSame( '<span style="color: #80ff80">x</span>', PriceHtml::normalised( '<span style="color: rgba(0, 255, 0, 0.5)">x</span>' ) );
	}

	public function test_channels_above_255_are_kept_at_255(): void {
		self::assertSame( 'color: #ff0000', PriceHtml::normalised( 'color: rgb(300, 0, 0)' ) );
	}

	public function test_script_and_style_blocks_go_with_their_content(): void {
		self::assertSame( '<b>$10</b>', PriceHtml::normalised( '<b>$10</b><script>alert(1)</script><style>b{}</style>' ) );
	}

	public function test_a_price_without_colours_is_unchanged(): void {
		self::assertSame( '<p>$99.99</p>', PriceHtml::normalised( '<p>$99.99</p>' ) );
		self::assertSame( '', PriceHtml::normalised( '' ) );
	}
}
