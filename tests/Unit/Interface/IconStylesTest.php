<?php
namespace Affibloom\Tests\Unit\Interface;

use Affibloom\Domain\Icons\Icon;
use Affibloom\Domain\Icons\IconSet;
use Affibloom\Interface\IconStyles;
use PHPUnit\Framework\TestCase;

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
}
