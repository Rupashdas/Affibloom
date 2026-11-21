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
			PreviewProduct::product( 'https://site.test/images/' ),
			DisplaySettings::defaults()->with_advanced( true ),
			Settings::from_array( array() ),
			PreviewProduct::overrides( 'Cactus', false )
		);

		self::assertSame( 'Essentialism: The Disciplined Pursuit of Less', $view->title );
		self::assertSame( 'https://site.test/images/essentialism.jpg', $view->image_src );
		self::assertSame( '$15.99', $view->price );
		self::assertTrue( $view->prime );
		self::assertSame( 'Our Pick', $view->badge );
		self::assertStringStartsWith( "The Way of the Essentialist isn't about getting more done", (string) $view->description );
		self::assertSame( array( 'Pros', 'Cons' ), array_map( static fn( $f ) => $f->name, $view->fields ) );
		self::assertSame( '#', $view->buttons[1]->attributes['href'] );
		self::assertStringContainsString( 'affibloom-cactus', $view->classes );
		self::assertFalse( PreviewProduct::overrides( 'Lab', true )->show_pros_cons );
	}

	/**
	 * Grid draws one sample per column, at most three, List all three (R95): Atomic Habits and Effortless follow Essentialism with their
	 * own price and picture and no badge.
	 */
	public function test_grid_draws_a_sample_per_column(): void {
		self::assertSame( array( 1, 1, 2, 3, 3 ), array_map( array( PreviewProduct::class, 'in_grid' ), array( 0, 1, 2, 3, 4 ) ) );
		self::assertSame( 3, PreviewProduct::in_list() );

		$view = ( new BoxBuilder( 'https://site.test/no.webp' ) )->build(
			PreviewProduct::product( 'https://site.test/images/', 2 ),
			DisplaySettings::defaults()->with_advanced( true ),
			Settings::from_array( array() ),
			PreviewProduct::overrides( 'Cactus', true, 2 )
		);

		self::assertSame( 'Effortless: Make It Easier to Do What Matters Most', $view->title );
		self::assertSame( 'https://site.test/images/effortless.jpg', $view->image_src );
		self::assertSame( '$14.99', $view->price );
		self::assertSame( '', $view->badge );
		self::assertSame( 'Atomic Habits: A Proven Way to Build Good Habits', PreviewProduct::product( '', 1 )->name );
	}
}
