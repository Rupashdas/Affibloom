<?php
namespace Affibloom\Tests\Unit\Interface\Editor;

use Affibloom\Interface\Editor\ElementorDisplayWidget;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

require_once dirname( __DIR__, 3 ) . '/stubs/elementor.php';

/**
 * The widget "Affibloom" (F4 R65-R70, Task ED2): the shortcode is its setting and what the page renders; every display
 * type's overrides are its controls, shown for the shortcode's type.
 */
final class ElementorDisplayWidgetTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\stubTranslationFunctions();
		Functions\stubEscapeFunctions();
	}

	public function test_it_is_affibloom_in_the_basic_category(): void {
		$widget = new ElementorDisplayWidget();

		self::assertSame( 'affibloom', $widget->get_name() );
		self::assertSame( 'Affibloom', $widget->get_title() );
		self::assertSame( array( 'basic' ), $widget->get_categories() );
		self::assertContains( 'shortcode', $widget->get_keywords() );
	}

	public function test_its_controls_are_the_shortcode_the_type_and_each_types_overrides(): void {
		$controls = ( new ElementorDisplayWidget() )->get_controls();

		self::assertSame( 'textarea', $controls[ ElementorDisplayWidget::SHORTCODE ]['type'] );
		self::assertSame( 'Enter your shortcode', $controls[ ElementorDisplayWidget::SHORTCODE ]['label'] );
		self::assertSame( 'hidden', $controls[ ElementorDisplayWidget::TYPE ]['type'] );

		// A text field, shown for its type only.
		self::assertSame( 'text', $controls['title_single']['type'] );
		self::assertSame( array( ElementorDisplayWidget::TYPE => 'single' ), $controls['title_single']['condition'] );
		self::assertSame( array( ElementorDisplayWidget::TYPE => 'grid' ), $controls['title_grid']['condition'] );
		self::assertSame( 'Product title', $controls['title_single']['label'] );
		// Toggles are switchers at their defaults: price on unless hidden, a grid's disclosure off unless enabled.
		self::assertSame( 'switcher', $controls['price_single']['type'] );
		self::assertSame( 'yes', $controls['price_single']['default'] );
		self::assertSame( '', $controls['disclosure_grid']['default'] );
		// The description is a text area, as the block's.
		self::assertSame( 'textarea', $controls['description_single']['type'] );
	}

	/** Task 24G: each badge or button text has an icon dropdown of the library, before it; a grid's badge has none. */
	public function test_badge_and_button_texts_have_an_icon_dropdown(): void {
		ElementorDisplayWidget::use_icons(
			static fn(): array => array(
				array(
					'class'   => 'medal-icon',
					'name'    => 'Medal',
					'src'     => '',
					'missing' => false,
				),
			)
		);
		$controls = ( new ElementorDisplayWidget() )->get_controls();
		$names    = array_keys( $controls );

		self::assertSame( 'select', $controls['badge_icon_single']['type'] );
		self::assertSame( 'Icon for Badge', $controls['badge_icon_single']['label'] );
		self::assertSame(
			array(
				''           => 'No icon',
				'medal-icon' => 'Medal',
			),
			$controls['badge_icon_single']['options']
		);
		self::assertSame( array( ElementorDisplayWidget::TYPE => 'single' ), $controls['badge_icon_single']['condition'] );
		self::assertSame( array_search( 'badge_single', $names, true ) - 1, array_search( 'badge_icon_single', $names, true ) );
		self::assertArrayHasKey( 'primary_text_icon_button', $controls );
		self::assertArrayHasKey( 'secondary_text_icon_single', $controls );
		self::assertArrayNotHasKey( 'badge_icon_grid', $controls );
	}

	/**
	 * Task 37A, finding S2: the field is the editor's text, printed on the page; an Author without unfiltered_html
	 * must not get a script onto it, as WordPress keeps one out of their post content.
	 */
	public function test_the_field_is_kept_to_post_html_before_its_shortcodes_run(): void {
		Functions\expect( 'wp_kses_post' )->once()->with( '[affibloom id="7"]<script>alert(1)</script>' )->andReturn( '[affibloom id="7"]alert(1)' );
		Functions\when( 'shortcode_unautop' )->returnArg();
		Functions\expect( 'do_shortcode' )->once()->with( '[affibloom id="7"]alert(1)' )->andReturn( '<div class="affibloom-box">box</div>alert(1)' );
		$widget = new ElementorDisplayWidget( array( 'settings' => array( ElementorDisplayWidget::SHORTCODE => '[affibloom id="7"]<script>alert(1)</script>' ) ) );

		self::assertSame( '<div class="affibloom-box">box</div>alert(1)', $widget->stub_render() );
	}

	public function test_the_page_renders_the_shortcode_and_plain_content_prints_it(): void {
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\expect( 'shortcode_unautop' )->once()->with( '[affibloom id="7"]' )->andReturn( '[affibloom id="7"]' );
		Functions\expect( 'do_shortcode' )->once()->with( '[affibloom id="7"]' )->andReturn( '<div class="affibloom-box">box</div>' );
		$widget = new ElementorDisplayWidget( array( 'settings' => array( ElementorDisplayWidget::SHORTCODE => '[affibloom id="7"]' ) ) );

		self::assertSame( '<div class="affibloom-box">box</div>', $widget->stub_render() );

		ob_start();
		$widget->render_plain_content();
		self::assertSame( '[affibloom id="7"]', ob_get_clean() );
	}

	public function test_the_canvas_draws_the_blocks_panel(): void {
		$template = ( new ElementorDisplayWidget() )->stub_template();

		self::assertStringContainsString( 'Choose a Link to display.', $template );
		self::assertStringContainsString( 'data-affibloom-preview', $template );
		self::assertStringContainsString( 'Update Display', $template );
		self::assertStringContainsString( 'Edit Display', $template );
		self::assertStringContainsString( 'Select a New Display', $template );
		self::assertStringContainsString( 'Add a Display', $template );
	}
}
