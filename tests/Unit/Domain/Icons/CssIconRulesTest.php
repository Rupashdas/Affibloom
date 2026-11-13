<?php
namespace Affibloom\Tests\Unit\Domain\Icons;

use Affibloom\Domain\Icons\CssIconRules;
use PHPUnit\Framework\TestCase;

final class CssIconRulesTest extends TestCase {
	/**
	 * The live site's Additional CSS, from "/*edit icons here*\/" to its end marker, with the Lasso rule before it
	 * (tests/fixtures/icons/site-additional-css.css, copied from the Local copy on 2026-11-13).
	 */
	public function test_the_sites_six_icon_rules_are_found_in_order(): void {
		$css = (string) file_get_contents( dirname( __DIR__, 3 ) . '/fixtures/icons/site-additional-css.css' );

		self::assertSame(
			array(
				array( 'pricetag-icon', '/wp-content/uploads/2023/06/pricetag-icon.svg' ),
				array( 'medal-icon', '/wp-content/uploads/2023/06/medal-icon.svg' ),
				array( 'full-review-icon', '/wp-content/uploads/2023/04/full-review-icon.svg' ),
				array( 'amazon-icon', '/wp-content/uploads/2023/06/amazon-icon.svg' ),
				// Written without its leading slash on the site; kept as written.
				array( 'alsogood-icon', 'wp-content/uploads/2023/06/alsogood-icon.svg' ),
				array( 'youtube-icon', '/wp-content/uploads/2023/04/youtube-icon.svg' ),
			),
			CssIconRules::parse( $css )
		);
	}

	public function test_rules_that_are_not_icons_are_ignored(): void {
		$css = 'span.badge { content: url(a.svg); } .x span.y-icon { content: url(b.svg); } '
			. 'span.z-icon { color: red; } /* span.c-icon { content: url(c.svg); } */ span.d-icon{content:url("d.svg")}';

		self::assertSame( array( array( 'd-icon', 'd.svg' ) ), CssIconRules::parse( $css ) );
	}

	public function test_a_class_defined_twice_counts_once(): void {
		$css = 'span.a-icon { content: url(1.svg); } span.a-icon { content: url(2.svg); }';

		self::assertSame( array( array( 'a-icon', '1.svg' ) ), CssIconRules::parse( $css ) );
	}
}
