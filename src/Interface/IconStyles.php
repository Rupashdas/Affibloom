<?php
/**
 * Prints the CSS for the icons added in Affibloom (F28).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface;

use Affibloom\Application\Icons\IconService;
use Affibloom\Domain\Icons\Icon;
use Affibloom\Domain\Icons\IconSet;
use Affibloom\Module;

/**
 * Prints the CSS for the icons added in Affibloom, on the site's pages (F28 requirements B2). Imported icons are drawn
 * by the site's own Additional CSS, so they get nothing here and keep their exact look (ADR-6). Nothing is printed when
 * no icon has been added. The admin gets the same CSS with the first admin screen that shows a box (the preview).
 */
final class IconStyles implements Module {

	/** The empty style handle the CSS is attached to. */
	private const HANDLE = 'affibloom-icons';

	/**
	 * Builds the printer.
	 *
	 * @param IconService $icons The icon set.
	 */
	public function __construct( private IconService $icons ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Adds the CSS to the page, when there is any.
	 */
	public function enqueue(): void {
		$css = self::css( $this->icons->all() );
		if ( '' === $css ) {
			return;
		}
		wp_register_style( self::HANDLE, false, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- inline only, no file.
		wp_enqueue_style( self::HANDLE );
		wp_add_inline_style( self::HANDLE, $css );
	}

	/**
	 * The CSS for the set's added icons, shaped like the site's own icon rules: 21×21, 3px right margin, raised 2px,
	 * 15×15 at 768px and below. Classes and links are safe inside it: Icon's rules allow no character that could end
	 * the selector or the url("…").
	 *
	 * @param IconSet $set The icon set.
	 */
	public static function css( IconSet $set ): string {
		$added = array_filter( $set->all(), static fn( Icon $icon ): bool => ! $icon->imported );

		$css = '';
		foreach ( $added as $icon ) {
			$css .= 'span.' . $icon->css_class . '{content:url("' . $icon->url . '");object-fit:contain;width:21px;height:21px;'
				. "margin-right:3px;position:relative;top:2px}\n";
			$css .= '@media (max-width:768px){span.' . $icon->css_class . "{width:15px;height:15px}}\n";
		}

		return $css;
	}
}
