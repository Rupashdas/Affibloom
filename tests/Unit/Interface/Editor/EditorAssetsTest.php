<?php
namespace Affibloom\Tests\Unit\Interface\Editor;

use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Shell\NoticeBoard;
use Affibloom\Domain\Display\DisplaySettingsRepository;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Infrastructure\WordPress\BoxDetector;
use Affibloom\Interface\Admin\AdminNavigation;
use Affibloom\Interface\Editor\EditorAssets;
use Affibloom\Interface\Front\BoxAssets;
use Affibloom\Interface\Template;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * The display modal on block-editor screens (Task 23A): Meadow's styles, the modal's own after them, and the address
 * of its script for the block, which loads it on first use.
 */
final class EditorAssetsTest extends TestCase {
	/** @var array<string, array{0: string, 1: list<string>}> */
	private array $styles = array();

	/** @var array<string, string> */
	private array $inline = array();

	protected function setUp(): void {
		parent::setUp();
		$this->styles = array();
		$this->inline = array();
		Functions\when( 'wp_enqueue_style' )->alias(
			function ( string $handle, string $src = '', array $deps = array() ): void {
				$this->styles[ $handle ] = array( $src, $deps );
			}
		);
		Functions\when( 'wp_add_inline_script' )->alias(
			function ( string $handle, string $code ): void {
				$this->inline[ $handle ] = $code;
			}
		);
		Functions\when( 'add_query_arg' )->alias(
			static fn( string $key, string $value, string $url ): string => $url . '?' . $key . '=' . $value
		);
		Functions\when( 'wp_json_encode' )->alias( static fn( $data ): string => (string) json_encode( $data, JSON_UNESCAPED_SLASHES ) );
		Functions\when( 'wp_enqueue_script' )->justReturn();
		Functions\stubTranslationFunctions();
	}

	/** A template folder whose display modal prints a marker. */
	private static function template(): Template {
		$dir = sys_get_temp_dir() . '/affibloom-editor-assets-test';
		if ( ! is_dir( $dir . '/editor' ) ) {
			mkdir( $dir . '/editor', 0777, true );
		}
		file_put_contents( $dir . '/editor/display-modal.php', '<?php echo "MODAL";' );
		return new Template( $dir, new AdminNavigation(), new NoticeBoard( array(), static function (): void {} ) );
	}

	private static function assets(): EditorAssets {
		$settings = \Mockery::mock( SettingsRepository::class );
		$box      = new BoxAssets( '/plugin/', 'https://site.test/', '0.1.0', new DisplaySettingsService( \Mockery::mock( DisplaySettingsRepository::class ), $settings ), $settings, new BoxDetector() );

		$icons = static fn(): array => array(
			array(
				'class'   => 'medal-icon',
				'name'    => 'Medal',
				'src'     => 'https://site.test/medal.svg',
				'missing' => false,
			),
		);

		return new EditorAssets( 'https://site.test/wp-content/plugins/affibloom/', '0.1.0', self::template(), $box, $icons );
	}

	/** Task 24G: the sidebar's badge and button texts learn the icon library and the picker's words. */
	public function test_the_block_learns_the_icon_library_for_its_badge_and_button_texts(): void {
		$scripts = array();
		Functions\when( 'wp_enqueue_script' )->alias(
			function ( string $handle, string $src, array $deps ) use ( &$scripts ): void {
				$scripts[ $handle ] = array( $src, $deps );
			}
		);
		self::assets()->enqueue();

		$code   = $this->inline[ EditorAssets::BLOCK_SCRIPT ];
		$config = json_decode( substr( $code, strlen( 'window.affibloomEditor = ' ), -1 ), true );
		self::assertSame( 'medal-icon', $config['icons'][0]['class'] );
		self::assertSame( 'No icon', $config['iconLabels']['none'] );
		self::assertSame( 'Icon for %1$s: %2$s', $config['iconLabels']['button'] );
		$single = array_column( $config['overrides']['single'], 'icon', 'name' );
		self::assertTrue( $single['badge'] );
		self::assertFalse( $single['title'] );
		// The table builder's icon grid, without admin.js: "Add icon" is not offered in the editor.
		self::assertSame( array( 'https://site.test/wp-content/plugins/affibloom/assets/admin/icon-popover.js', array( 'wp-api-fetch' ) ), $scripts['affibloom-icon-popover'] );
		self::assertSame( array( array( 'class' => 'medal-icon', 'name' => 'Medal', 'src' => 'https://site.test/medal.svg', 'missing' => false ) ), self::assets()->icons() );
	}

	public function test_the_modal_styles_follow_meadows_fonts_and_admin_styles(): void {
		self::assets()->enqueue();

		self::assertContains( 'affibloom-fonts', $this->styles['affibloom-admin'][1] );
		self::assertSame( 'https://site.test/wp-content/plugins/affibloom/assets/editor/display-modal.css', $this->styles['affibloom-display-modal'][0] );
		self::assertContains( 'affibloom-admin', $this->styles['affibloom-display-modal'][1] );
	}

	public function test_the_block_learns_where_the_modal_script_is_and_its_sidebars_fields(): void {
		self::assets()->enqueue();

		$code = $this->inline[ EditorAssets::BLOCK_SCRIPT ];
		self::assertStringStartsWith( 'window.affibloomEditor = ', $code );
		$config = json_decode( substr( $code, strlen( 'window.affibloomEditor = ' ), -1 ), true );
		self::assertSame( 'https://site.test/wp-content/plugins/affibloom/assets/editor/display-modal.js?ver=0.1.0', $config['modalScript'] );
		// The sidebar's catalogue (Task 24A): the Button type's fields with their labels.
		self::assertSame( array( 'theme', 'button_type', 'primary_url', 'primary_text', 'anchor_id' ), array_column( $config['overrides']['button'], 'name' ) );
		self::assertSame( 'Button url', $config['overrides']['button'][2]['label'] );
	}

	public function test_the_modal_is_printed_on_editor_screens_only(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		$classic       = \Mockery::mock();
		$classic->base = 'post';
		$classic->shouldReceive( 'is_block_editor' )->andReturn( false );
		Functions\when( 'get_current_screen' )->justReturn( $classic );
		Functions\when( 'get_option' )->justReturn( array( 'affibloom/affibloom.php' ) );
		ob_start();
		self::assets()->print_modal();
		self::assertSame( '', ob_get_clean() );

		// With the Classic Editor plugin active, its post screen gets the modal for the toolbar button (Task ED1).
		Functions\when( 'get_option' )->justReturn( array( 'classic-editor/classic-editor.php' ) );
		ob_start();
		self::assets()->print_modal();
		self::assertSame( 'MODAL', ob_get_clean() );

		$block = \Mockery::mock();
		$block->shouldReceive( 'is_block_editor' )->andReturn( true );
		Functions\when( 'get_current_screen' )->justReturn( $block );
		ob_start();
		self::assets()->print_modal();
		self::assertSame( 'MODAL', ob_get_clean() );
	}

	public function test_rank_maths_analysis_reads_the_displays_where_its_analyzer_is_registered(): void {
		$scripts = array();
		Functions\when( 'wp_enqueue_script' )->alias(
			function ( string $handle, string $src, array $deps ) use ( &$scripts ): void {
				$scripts[ $handle ] = array( $src, $deps );
			}
		);
		$block = \Mockery::mock();
		$block->shouldReceive( 'is_block_editor' )->andReturn( true );
		Functions\when( 'get_current_screen' )->justReturn( $block );

		Functions\when( 'wp_script_is' )->justReturn( false );
		self::assets()->enqueue_rank_math();
		self::assertSame( array(), $scripts );

		Functions\when( 'wp_script_is' )->justReturn( true );
		self::assets()->enqueue_rank_math();
		self::assertSame(
			array( 'https://site.test/wp-content/plugins/affibloom/assets/editor/rank-math.js', array( 'wp-hooks', 'rank-math-analyzer' ) ),
			$scripts['affibloom-rank-math']
		);
	}
}
