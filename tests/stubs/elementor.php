<?php
/**
 * The few Elementor classes the plugin's widget uses (Task ED2), with Elementor 4.4's signatures: for PHPStan, and for
 * the unit tests, which load it in place of Elementor. Its controls and settings are kept in arrays so a test can read
 * what the widget registered. Never loaded at run time: the real classes are Elementor's.
 *
 * @package Affibloom
 */

// phpcs:ignoreFile -- a signature stub, not plugin code.

namespace Elementor;

class Controls_Manager {
	const TAB_CONTENT = 'content';
	const TEXT        = 'text';
	const TEXTAREA    = 'textarea';
	const SWITCHER    = 'switcher';
	const HIDDEN      = 'hidden';
	const SELECT      = 'select';
}

abstract class Widget_Base {
	/** @var array<string, array<string, mixed>> */
	private array $controls = array();

	/** @var array<string, mixed> */
	private array $settings;

	/**
	 * @param array<string, mixed> $data
	 * @param array<string, mixed>|null $args
	 */
	public function __construct( $data = array(), $args = null ) {
		$this->settings = is_array( $data['settings'] ?? null ) ? $data['settings'] : array();
	}

	/** @return string */
	abstract public function get_name();

	/** @return string */
	public function get_title() {
		return '';
	}

	/** @return string */
	public function get_icon() {
		return '';
	}

	/** @return list<string> */
	public function get_keywords() {
		return array();
	}

	/** @return list<string> */
	public function get_categories() {
		return array( 'basic' );
	}

	/** @return void */
	protected function register_controls() {}

	/** @return void */
	protected function render() {}

	/** @return void */
	protected function content_template() {}

	/** @return void */
	public function render_plain_content() {}

	/**
	 * @param string               $section_id
	 * @param array<string, mixed> $args
	 * @return void
	 */
	public function start_controls_section( $section_id, array $args = array() ) {
		$this->controls[ $section_id ] = $args + array( 'type' => 'section' );
	}

	/** @return void */
	public function end_controls_section() {}

	/**
	 * @param string               $id
	 * @param array<string, mixed> $args
	 * @param array<string, mixed> $options
	 * @return bool
	 */
	public function add_control( $id, array $args, $options = array() ) {
		$this->controls[ $id ] = $args;

		return true;
	}

	/**
	 * @param string|null $control_id
	 * @return mixed
	 */
	public function get_controls( $control_id = null ) {
		if ( array() === $this->controls ) {
			$this->register_controls();
		}

		return null === $control_id ? $this->controls : ( $this->controls[ $control_id ] ?? null );
	}

	/**
	 * @param string|null $setting_key
	 * @return mixed
	 */
	public function get_settings_for_display( $setting_key = null ) {
		return null === $setting_key ? $this->settings : ( $this->settings[ $setting_key ] ?? null );
	}

	/**
	 * @param string      $setting
	 * @param string|null $repeater_name
	 * @param int|null    $index
	 * @return void
	 */
	final public function print_unescaped_setting( $setting, $repeater_name = null, $index = null ) {
		echo $this->settings[ $setting ] ?? '';
	}

	/** Runs the protected render, as Elementor's print_element does. */
	public function stub_render(): string {
		ob_start();
		$this->render();

		return (string) ob_get_clean();
	}

	/** Runs the protected content template, as Elementor's print_template does. */
	public function stub_template(): string {
		ob_start();
		$this->content_template();

		return (string) ob_get_clean();
	}
}

class Widgets_Manager {
	/** @var list<Widget_Base> */
	public array $registered = array();

	/** @return bool */
	public function register( Widget_Base $widget_instance ) {
		$this->registered[] = $widget_instance;

		return true;
	}
}
