<?php
namespace Affibloom\Tests\Unit\Interface\Editor;

use Affibloom\Interface\Editor\ClassicButton;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * The Classic editor's button (F4 R60-R61, Task ED1): only while the Classic Editor or Disable Gutenberg plugin is
 * active, as Lasso's is_classic_editor().
 */
final class ClassicButtonTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'add_query_arg' )->alias( static fn( string $key, string $value, string $url ): string => $url . '?' . $key . '=' . $value );
	}

	private static function button(): ClassicButton {
		return new ClassicButton( 'https://site.test/wp-content/plugins/affibloom/', '0.1.0' );
	}

	public function test_without_a_classic_editor_plugin_tinymce_is_left_alone(): void {
		Functions\when( 'get_option' )->justReturn( array( 'affibloom/affibloom.php' ) );

		self::assertSame( array( 'bold' ), self::button()->buttons( array( 'bold' ) ) );
		self::assertSame( array(), self::button()->plugins( array() ) );
	}

	public function test_with_the_classic_editor_or_disable_gutenberg_the_button_joins_the_toolbar(): void {
		foreach ( array( 'classic-editor/classic-editor.php', 'disable-gutenberg/disable-gutenberg.php' ) as $plugin ) {
			Functions\when( 'get_option' )->justReturn( array( $plugin ) );

			self::assertSame( array( 'bold', ClassicButton::PLUGIN ), self::button()->buttons( array( 'bold' ) ) );
			self::assertSame(
				array( ClassicButton::PLUGIN => 'https://site.test/wp-content/plugins/affibloom/assets/editor/classic-button.js?ver=0.1.0' ),
				self::button()->plugins( array() )
			);
		}
	}
}
