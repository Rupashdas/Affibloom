<?php
namespace Affibloom\Tests\Unit\Interface;

use Affibloom\Application\Icons\IconService;
use Affibloom\Application\Icons\SiteIcons;
use Affibloom\Domain\Icons\Icon;
use Affibloom\Domain\Icons\IconRepository;
use Affibloom\Domain\Icons\IconSet;
use Affibloom\Interface\IconStyles;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

final class IconStylesTest extends TestCase {
	public function test_only_added_icons_get_css_shaped_like_the_sites_own(): void {
		$set = ( new IconSet( array( Icon::imported( 'medal-icon', '/m.svg', 1 ) ) ) )->add( 'Star', 'https://example.com/s.svg', 2 );

		$css = IconStyles::css( $set );

		self::assertStringNotContainsString( 'medal-icon', $css );
		self::assertStringContainsString(
			'span.star-icon{content:url("https://example.com/s.svg");object-fit:contain;width:21px;height:21px;margin-right:3px;position:relative;top:2px}',
			$css
		);
		self::assertStringContainsString( '@media (max-width:768px){span.star-icon{width:15px;height:15px}}', $css );
	}

	public function test_no_added_icon_prints_nothing(): void {
		self::assertSame( '', IconStyles::css( new IconSet( array( Icon::imported( 'medal-icon', '/m.svg', 1 ) ) ) ) );
	}

	/**
	 * A preview frame (the product page's, Display's) gets the site's Additional CSS, which draws the imported icons,
	 * then the added icons' rules; nothing in it can close the frame's style element.
	 */
	public function test_a_preview_frame_gets_the_sites_css_and_the_added_icons(): void {
		Functions\when( 'wp_get_custom_css' )->justReturn( "span.medal-icon{content:url('/m.svg')}</style><script>" );
		$repo = new class() implements IconRepository {
			public function load(): ?IconSet {
				return ( new IconSet( array( Icon::imported( 'medal-icon', '/m.svg', 1 ) ) ) )->add( 'Star', 'https://example.com/s.svg', 2 );
			}
			public function save( IconSet $set ): void {}
		};
		$site = new class() implements SiteIcons {
			public function found(): array {
				return array();
			}
		};

		$css = ( new IconStyles( new IconService( $repo, $site ) ) )->preview_css();

		self::assertStringStartsWith( "span.medal-icon{content:url('/m.svg')}", $css );
		self::assertStringContainsString( 'span.star-icon{content:url("https://example.com/s.svg")', $css );
		self::assertStringNotContainsString( '<', $css );
	}
}
