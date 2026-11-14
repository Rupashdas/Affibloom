<?php
namespace Affibloom\Tests\Unit\Interface;

use Affibloom\Interface\Admin\ProductEditors;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

final class ProductEditorsTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Functions\stubTranslationFunctions();
	}

	/**
	 * Advanced Editor Tools (active on this site) gives every editor its menu bar and its toolbars; the product page's
	 * two editors keep Lasso's toolbars instead.
	 */
	public function test_the_product_pages_editors_get_lassos_toolbars_and_no_menu_bar(): void {
		$from_other_plugin = array(
			'menubar'  => 'file edit insert view format table tools',
			'toolbar1' => 'formatselect,bold,italic,table',
			'toolbar2' => 'strikethrough,hr',
			'toolbar3' => 'x',
			'plugins'  => 'lists,table,textcolor',
		);

		$description = ProductEditors::settings( $from_other_plugin, 'affibloom_description' );
		self::assertFalse( $description['menubar'] );
		self::assertSame( 'bold,italic,underline,strikethrough,link,bullist,forecolor,backcolor,removeformat', $description['toolbar1'] );
		self::assertSame( '', $description['toolbar2'] );
		self::assertSame( '', $description['toolbar3'] );
		self::assertSame( 'lists,table,textcolor', $description['plugins'] );

		$price = ProductEditors::settings( $from_other_plugin, 'affibloom_price' );
		self::assertSame( 'bold,italic,underline,strikethrough,forecolor', $price['toolbar1'] );
		// Lasso's price editor is one line.
		self::assertSame( 40, $price['min_height'] );
		self::assertFalse( $price['wp_autoresize_on'] );
		self::assertArrayNotHasKey( 'min_height', $description );
	}

	/**
	 * TinyMCE 4.9 (WordPress) has no placeholder of its own. The description editor carries Lasso's words as a setting
	 * and a style that draws them while the editor is empty; product.js sets the class and the attribute.
	 */
	public function test_the_description_editor_has_a_placeholder_and_the_price_editor_has_none(): void {
		$description = ProductEditors::settings( array( 'content_style' => 'body{margin:0}' ), 'affibloom_description' );
		self::assertSame( 'Enter a description', $description['placeholder'] );
		self::assertStringStartsWith( 'body{margin:0}', $description['content_style'] );
		self::assertStringContainsString( 'body.affibloom-empty::before', $description['content_style'] );
		self::assertStringContainsString( 'attr(data-placeholder)', $description['content_style'] );

		$price = ProductEditors::settings( array(), 'affibloom_price' );
		self::assertArrayNotHasKey( 'placeholder', $price );
		self::assertArrayNotHasKey( 'content_style', $price );
	}

	public function test_every_other_editor_is_left_alone(): void {
		$init = array( 'menubar' => 'file', 'toolbar1' => 'bold' );

		self::assertSame( $init, ProductEditors::settings( $init, 'content' ) );
	}
}
