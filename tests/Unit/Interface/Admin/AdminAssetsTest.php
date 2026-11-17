<?php
namespace Affibloom\Tests\Unit\Interface\Admin;

use Affibloom\Interface\Admin\AdminAssets;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * Meadow's bundled fonts (Task MD1; design system 0b.5): loaded on Affibloom's screens only, before the admin styles
 * that use them, and from the plugin itself.
 */
final class AdminAssetsTest extends TestCase {
	/** @var array<string, array{0: string, 1: list<string>}> */
	private array $styles = array();

	protected function setUp(): void {
		parent::setUp();
		$this->styles = array();
		Functions\when( 'wp_enqueue_style' )->alias(
			function ( string $handle, string $src = '', array $deps = array() ): void {
				$this->styles[ $handle ] = array( $src, $deps );
			}
		);
		Functions\when( 'wp_enqueue_script' )->justReturn( null );
		Functions\when( 'wp_enqueue_media' )->justReturn( null );
	}

	private static function assets(): AdminAssets {
		return new AdminAssets( 'https://site.test/wp-content/plugins/affibloom/', '0.1.0' );
	}

	public function test_an_affibloom_screen_loads_the_bundled_fonts_before_the_admin_styles(): void {
		self::assets()->enqueue( 'toplevel_page_affibloom' );

		self::assertSame( 'https://site.test/wp-content/plugins/affibloom/assets/admin/fonts.css', $this->styles['affibloom-fonts'][0] );
		self::assertContains( 'affibloom-fonts', $this->styles['affibloom-admin'][1] );
	}

	public function test_the_product_page_in_a_frame_is_marked_for_the_quick_detail(): void {
		$_GET = array(
			'page'            => 'affibloom-product',
			'affibloom_frame' => '1',
		);
		self::assertSame( 'folded affibloom-framed', self::assets()->framed( 'folded' ) );

		$_GET = array( 'page' => 'affibloom-product' );
		self::assertSame( 'folded', self::assets()->framed( 'folded' ) );

		$_GET = array(
			'page'            => 'affibloom',
			'affibloom_frame' => '1',
		);
		self::assertSame( 'folded', self::assets()->framed( 'folded' ) );
		$_GET = array();
	}

	public function test_another_admin_page_loads_nothing(): void {
		self::assets()->enqueue( 'edit.php' );

		self::assertSame( array(), $this->styles );
	}
}
