<?php
/**
 * The Elementor widget "Affibloom".
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Editor;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

/**
 * Lasso's "Lasso" widget (F4 R65-R70, Task ED2) under Affibloom's name: its one setting is the display's shortcode,
 * which the page renders; the panel's other controls are each display type's overrides (Customize Display's fields,
 * OverrideTexts), shown for the type the shortcode names. The canvas draws the block's panel (content_template), which
 * assets/editor/elementor.js fills with the preview and wires to the display modal. Elementor loads this class only
 * when it registers its widgets (ElementorWidget).
 */
final class ElementorDisplayWidget extends Widget_Base {

	/** The widget's name in Elementor's data (`widgetType`). */
	public const NAME = 'affibloom';

	/** The shortcode's setting. */
	public const SHORTCODE = 'affibloom_shortcode';

	/** The display type the shortcode names, kept by elementor.js so only that type's controls show (R67). */
	public const TYPE = 'affibloom_type';

	/**
	 * The widget's name.
	 */
	public function get_name(): string {
		return self::NAME;
	}

	/**
	 * Its title in the panel.
	 */
	public function get_title(): string {
		return __( 'Affibloom', 'affibloom' );
	}

	/**
	 * Its icon in the panel: the flower (elementor.css).
	 */
	public function get_icon(): string {
		return 'affibloom-eicon';
	}

	/**
	 * Its category, as Lasso's.
	 *
	 * @return list<string>
	 */
	public function get_categories(): array {
		return array( 'basic' );
	}

	/**
	 * Words the panel's search finds it by (Lasso's, with Affibloom's name).
	 *
	 * @return list<string>
	 */
	public function get_keywords(): array {
		return array( 'affibloom', 'shortcode', 'code', 'display', 'product' );
	}

	/**
	 * The shortcode's text area, the type, and every type's overrides as `<field>_<type>` (R66): a switcher for a
	 * toggle, at its default; a line of text, or the description's text area, otherwise.
	 */
	protected function register_controls(): void {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Affibloom Shortcode', 'affibloom' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			self::SHORTCODE,
			array(
				'label'       => __( 'Enter your shortcode', 'affibloom' ),
				'type'        => Controls_Manager::TEXTAREA,
				'placeholder' => '[affibloom id="123"]',
				'default'     => '',
			)
		);

		$this->add_control(
			self::TYPE,
			array(
				'type'        => Controls_Manager::HIDDEN,
				'default'     => '',
				'render_type' => 'none',
			)
		);

		foreach ( OverrideTexts::catalog() as $type => $fields ) {
			foreach ( $fields as $field ) {
				$toggle = 'toggle' === $field['kind'];
				$this->add_control(
					$field['name'] . '_' . $type,
					array(
						'label'       => $field['label'],
						'type'        => $toggle ? Controls_Manager::SWITCHER : ( 'textarea' === $field['kind'] ? Controls_Manager::TEXTAREA : Controls_Manager::TEXT ),
						'default'     => $toggle && $field['on'] ? 'yes' : '',
						'description' => $field['help'],
						'condition'   => array( self::TYPE => $type ),
						// The canvas shows the shortcode's display: elementor.js rewrites the shortcode, which redraws it.
						'render_type' => 'none',
					)
				);
			}
		}

		$this->end_controls_section();
	}

	/**
	 * The page: the shortcode, rendered (R70). The field is kept to post HTML first (Task 37A, finding S2): it is
	 * whatever the editor typed, and an Author without unfiltered_html must not get a script onto the page through it.
	 */
	protected function render(): void {
		$shortcode = $this->get_settings_for_display( self::SHORTCODE );

		echo do_shortcode( shortcode_unautop( wp_kses_post( is_string( $shortcode ) ? $shortcode : '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- post HTML (wp_kses_post) and the shortcodes' own escaped output.
	}

	/**
	 * Plain content (Elementor's text copy of the page): the shortcode itself.
	 */
	public function render_plain_content(): void {
		$this->print_unescaped_setting( self::SHORTCODE );
	}

	/**
	 * The canvas: the block's panel (R68), a Backbone template. Without a display: the flower, "Choose a Link to
	 * display." and Add a Display; with one: the preview's place, Update Display, Edit Display (shown by elementor.js
	 * when the display has a page to edit) and Select a New Display.
	 */
	protected function content_template(): void {
		?>
		<# var affibloomShown = !! settings.<?php echo esc_js( self::SHORTCODE ); ?>;
			var affibloomLabel = affibloomShown ? '<?php echo esc_js( __( 'Select a New Display', 'affibloom' ) ); ?>' : '<?php echo esc_js( __( 'Add a Display', 'affibloom' ) ); ?>'; #>
		<div class="affibloom-elementor{{ affibloomShown ? ' has-display' : '' }}">
			<# if ( affibloomShown ) { #>
				<div class="affibloom-elementor__stage" data-affibloom-preview></div>
			<# } else { #>
				<span class="affibloom-elementor__mark" aria-hidden="true"></span>
				<p class="affibloom-elementor__prompt"><?php esc_html_e( 'Choose a Link to display.', 'affibloom' ); ?></p>
			<# } #>
			<div class="affibloom-elementor__actions">
				<# if ( affibloomShown ) { #>
					<button type="button" class="affibloom-elementor__button" data-affibloom-action="update"><?php esc_html_e( 'Update Display', 'affibloom' ); ?></button>
					<button type="button" class="affibloom-elementor__button" data-affibloom-action="edit" hidden><?php esc_html_e( 'Edit Display', 'affibloom' ); ?></button>
				<# } #>
				<button type="button" class="affibloom-elementor__button affibloom-elementor__add" data-affibloom-action="add" data-affibloom-label="{{ affibloomLabel }}">{{ affibloomLabel }}</button>
			</div>
		</div>
		<?php
	}
}
