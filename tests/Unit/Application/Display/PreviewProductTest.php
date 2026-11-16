<?php
namespace Affibloom\Tests\Unit\Application\Display;

use Affibloom\Application\Box\BoxBuilder;
use Affibloom\Application\Display\PreviewProduct;
use Affibloom\Domain\Display\DisplaySettings;
use Affibloom\Domain\Settings\Settings;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's Display preview sample (F5 R95; Task 10D).
 */
final class PreviewProductTest extends TestCase {

	/**
	 * The sample draws Lasso's values: name, picture, $15.99, Prime, badge, description, the demo Pros & Cons (not on
	 * Grid) and a second button to `#`.
	 */
	public function test_the_sample_is_lassos(): void {
		$view = ( new BoxBuilder( 'https://site.test/no.webp' ) )->build(
			PreviewProduct::product( 'https://site.test/essentialism.jpg' ),
			DisplaySettings::defaults()->with_advanced( true ),
			Settings::from_array( array() ),
			PreviewProduct::overrides( 'Cactus', false )
		);

		self::assertSame( 'Essentialism: The Disciplined Pursuit of Less', $view->title );
		self::assertSame( 'https://site.test/essentialism.jpg', $view->image_src );
		self::assertSame( '$15.99', $view->price );
		self::assertTrue( $view->prime );
		self::assertSame( 'Our Pick', $view->badge );
		self::assertStringStartsWith( "The Way of the Essentialist isn't about getting more done", (string) $view->description );
		self::assertSame( array( 'Pros', 'Cons' ), array_map( static fn( $f ) => $f->name, $view->fields ) );
		self::assertSame( '#', $view->buttons[1]->attributes['href'] );
		self::assertStringContainsString( 'affibloom-cactus', $view->classes );
		self::assertFalse( PreviewProduct::overrides( 'Lab', true )->show_pros_cons );
	}
}
