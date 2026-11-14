<?php
/**
 * Hides the "?" help tips when Settings → General says so.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Settings\SettingsService;
use Affibloom\Module;

/**
 * Hides the "?" help tips on Affibloom's screens when "Disable Help Tooltips" is on (Task 9K; F9 R53): a body class the
 * stylesheet reads. The setting reaches every screen through this one class, so no template asks for it.
 */
final class HelpTips implements Module {

	/** The body class that hides the tips. */
	public const BODY_CLASS = 'affibloom-no-tips';

	/**
	 * Builds the module.
	 *
	 * @param SettingsService $settings Settings use cases.
	 */
	public function __construct( private SettingsService $settings ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Adds the class on Affibloom's screens when the tips are disabled.
	 *
	 * @param string $classes Space-separated classes, which WordPress gives to this filter.
	 */
	public function body_class( string $classes ): string {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( null === $screen || ! str_contains( $screen->id, 'affibloom' ) || ! $this->settings->current()->bool( 'disable_help_tips' ) ) {
			return $classes;
		}

		return trim( $classes . ' ' . self::BODY_CLASS );
	}
}
