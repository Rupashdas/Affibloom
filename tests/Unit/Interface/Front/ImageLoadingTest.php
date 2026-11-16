<?php
namespace Affibloom\Tests\Unit\Interface\Front;

use Affibloom\Interface\Front\ImageLoading;
use PHPUnit\Framework\TestCase;

/**
 * A box picture's lazy loading (F30 R27; Task DT5).
 */
final class ImageLoadingTest extends TestCase {

	/**
	 * The browser's, else Ezoic's class (checked first, as Lasso does), else WP Rocket's.
	 */
	public function test_the_attribute_follows_the_lazy_load_plugins(): void {
		self::assertSame( 'loading="lazy"', ImageLoading::of( false, false ) );
		self::assertSame( 'class="ezlazyload"', ImageLoading::of( true, false ) );
		self::assertSame( 'class="ezlazyload"', ImageLoading::of( true, true ) );
		self::assertSame( 'class="rocket-lazyload"', ImageLoading::of( false, true ) );
	}
}
