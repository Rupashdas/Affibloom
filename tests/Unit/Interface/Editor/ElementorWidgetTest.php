<?php
namespace Affibloom\Tests\Unit\Interface\Editor;

use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Shell\NoticeBoard;
use Affibloom\Domain\Display\DisplaySettingsRepository;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Infrastructure\WordPress\BoxDetector;
use Affibloom\Interface\Admin\AdminNavigation;
use Affibloom\Interface\Editor\EditorAssets;
use Affibloom\Interface\Editor\ElementorDisplayWidget;
use Affibloom\Interface\Editor\ElementorWidget;
use Affibloom\Interface\Front\BoxAssets;
use Affibloom\Interface\Template;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;
use Elementor\Widgets_Manager;

require_once dirname( __DIR__, 3 ) . '/stubs/elementor.php';

/**
 * Affibloom in Elementor (F4 R65-R71, Task ED2): the widget is registered for Elementor, and its assets load in
 * Elementor's editor only, for those who may place displays.
 */
final class ElementorWidgetTest extends TestCase {
	/** @var array<string, array{0: string, 1: list<string>}> */
	private array $scripts = array();

	/** @var array<string, string> */
	private array $inline = array();

	private string $dir = '';

	protected function setUp(): void {
		parent::setUp();
		$this->scripts = array();
		$this->inline  = array();
		$this->dir     = sys_get_temp_dir() . '/affibloom-elementor-test/';
		if ( ! is_dir( $this->dir . 'build' ) ) {
			mkdir( $this->dir . 'build', 0777, true );
		}
		file_put_contents( $this->dir . 'build/elementor.asset.php', "<?php return array( 'dependencies' => array( 'wp-api-fetch' ), 'version' => 'abc' );" );
		Functions\when( 'wp_enqueue_script' )->alias(
			function ( string $handle, string $src = '', array $deps = array() ): void {
				$this->scripts[ $handle ] = array( $src, $deps );
			}
		);
		Functions\when( 'wp_add_inline_script' )->alias(
			function ( string $handle, string $code ): void {
				$this->inline[ $handle ] = $code;
			}
		);
		Functions\when( 'wp_enqueue_style' )->justReturn();
		Functions\when( 'add_query_arg' )->alias( static fn( string $key, string $value, string $url ): string => $url . '?' . $key . '=' . $value );
		Functions\when( 'wp_json_encode' )->alias( static fn( $data ): string => (string) json_encode( $data ) );
		Functions\stubTranslationFunctions();
	}

	private function widget(): ElementorWidget {
		if ( ! is_dir( $this->dir . 'templates/editor' ) ) {
			mkdir( $this->dir . 'templates/editor', 0777, true );
		}
		file_put_contents( $this->dir . 'templates/editor/display-modal.php', '<?php echo "MODAL";' );
		$template = new Template( $this->dir . 'templates', new AdminNavigation(), new NoticeBoard( array(), static function (): void {} ) );
		$settings = \Mockery::mock( SettingsRepository::class );
		$box      = new BoxAssets( '/plugin/', 'https://site.test/', '0.1.0', new DisplaySettingsService( \Mockery::mock( DisplaySettingsRepository::class ), $settings ), $settings, new BoxDetector() );
		$assets   = new EditorAssets( 'https://site.test/p/', '0.1.0', $template, $box );

		return new ElementorWidget( $this->dir, 'https://site.test/p/', '0.1.0', $assets, $box );
	}

	public function test_elementor_gets_the_affibloom_widget(): void {
		$widgets = new Widgets_Manager();

		$this->widget()->widgets( $widgets );

		self::assertCount( 1, $widgets->registered );
		self::assertInstanceOf( ElementorDisplayWidget::class, $widgets->registered[0] );
	}

	public function test_the_editor_gets_the_built_script_after_the_modal_config(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		$this->widget()->enqueue_editor();

		self::assertSame( 'https://site.test/p/build/elementor.js', $this->scripts[ ElementorWidget::HANDLE ][0] );
		self::assertSame( array( 'wp-api-fetch', 'elementor-editor' ), $this->scripts[ ElementorWidget::HANDLE ][1] );
		self::assertStringStartsWith( 'window.affibloomEditor = ', $this->inline[ ElementorWidget::HANDLE ] );
		self::assertStringContainsString( '"elementor":', $this->inline[ ElementorWidget::HANDLE ] );
	}

	public function test_nothing_loads_for_someone_who_may_not_place_displays_or_before_the_build(): void {
		Functions\when( 'current_user_can' )->justReturn( false );
		$this->widget()->enqueue_editor();
		self::assertSame( array(), $this->scripts );

		Functions\when( 'current_user_can' )->justReturn( true );
		unlink( $this->dir . 'build/elementor.asset.php' );
		$this->widget()->enqueue_editor();
		self::assertSame( array(), $this->scripts );
	}

	public function test_the_editor_footer_prints_the_modal_for_those_who_may_place_displays(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		ob_start();
		$this->widget()->print_modal();
		self::assertSame( 'MODAL', ob_get_clean() );

		Functions\when( 'current_user_can' )->justReturn( false );
		ob_start();
		$this->widget()->print_modal();
		self::assertSame( '', ob_get_clean() );
	}
}
