<?php
namespace Affibloom\Tests\Unit\Interface\Admin;

use Affibloom\Application\Shell\NoticeBoard;
use Affibloom\Interface\Admin\AdminNavigation;
use Affibloom\Interface\Admin\CommandPalette;
use Affibloom\Interface\Template;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * "Search links" (F38 R65, D19; Task ON9): on Affibloom's screens and the posts' lists and editors, for people who may
 * open products; its dialog printed only where it was loaded.
 */
final class CommandPaletteTest extends TestCase {
	/** @var list<string> */
	private array $scripts = array();

	protected function setUp(): void {
		parent::setUp();
		$this->scripts = array();
		Functions\when( 'wp_enqueue_style' )->justReturn();
		Functions\when( 'wp_enqueue_script' )->alias(
			function ( string $handle ): void {
				$this->scripts[] = $handle;
			}
		);
		Functions\when( 'admin_url' )->alias( static fn( string $path ): string => 'https://site.test/wp-admin/' . $path );
		Functions\when( 'add_query_arg' )->alias( static fn( string $key, string $value, string $url ): string => $url . '?' . $key . '=' . $value );
	}

	/** A template folder whose palette prints its product address. */
	private static function palette(): CommandPalette {
		$dir = sys_get_temp_dir() . '/affibloom-palette-test';
		if ( ! is_dir( $dir . '/admin/partials' ) ) {
			mkdir( $dir . '/admin/partials', 0777, true );
		}
		file_put_contents( $dir . '/admin/partials/palette.php', '<?php echo "PALETTE " . $view["product_url"];' );
		$template = new Template( $dir, new AdminNavigation(), new NoticeBoard( array(), static function (): void {} ) );

		return new CommandPalette( $template, 'https://site.test/wp-content/plugins/affibloom/', '0.1.0' );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function screens(): array {
		return array(
			'Dashboard'      => array( 'toplevel_page_affibloom' ),
			'Settings'       => array( 'affibloom_page_affibloom-settings' ),
			'Posts list'     => array( 'edit.php' ),
			'post editor'    => array( 'post.php' ),
			'new post'       => array( 'post-new.php' ),
		);
	}

	/**
	 * @dataProvider screens
	 */
	public function test_it_loads_and_prints_its_dialog_on_its_screens( string $hook ): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		$palette = self::palette();

		$palette->enqueue( $hook );
		ob_start();
		$palette->dialog();

		self::assertSame( array( 'affibloom-palette' ), $this->scripts );
		self::assertSame( 'PALETTE https://site.test/wp-admin/admin.php?page=affibloom-product', ob_get_clean() );
	}

	public function test_another_screen_gets_nothing(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		$palette = self::palette();

		$palette->enqueue( 'index.php' );
		ob_start();
		$palette->dialog();

		self::assertSame( array(), $this->scripts );
		self::assertSame( '', ob_get_clean() );
	}

	public function test_someone_who_may_not_open_products_gets_nothing(): void {
		Functions\expect( 'current_user_can' )->with( 'affibloom_products' )->andReturn( false );
		$palette = self::palette();

		$palette->enqueue( 'post.php' );
		ob_start();
		$palette->dialog();

		self::assertSame( array(), $this->scripts );
		self::assertSame( '', ob_get_clean() );
	}
}
