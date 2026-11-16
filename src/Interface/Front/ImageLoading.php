<?php
/**
 * The lazy-loading attribute of a box's picture.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

/**
 * How a box's picture asks to load late (F30 R27; Lasso's Html_Helper::build_img_lazyload_attributes): the browser's
 * `loading="lazy"`, or with Ezoic active `class="ezlazyload"`, with WP Rocket's image lazy-load on
 * `class="rocket-lazyload"`, for those plugins to pick up. Lasso prints the class as the image's second `class`
 * attribute, as here. The Single boxes, the lone image, the SiteStripe card and the inline thumbnail use it; Lasso's
 * list card keeps `loading="lazy"`.
 */
final class ImageLoading {

	/**
	 * This request's attribute, once worked out.
	 *
	 * @var string|null
	 */
	private static ?string $current = null;

	/**
	 * The attribute for this site.
	 */
	public static function attribute(): string {
		if ( null === self::$current ) {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$rocket        = get_option( 'wp_rocket_settings', array() );
			self::$current = self::of(
				is_plugin_active( 'ezoic-integration/ezoic-integration.php' ),
				is_plugin_active( 'wp-rocket/wp-rocket.php' ) && is_array( $rocket ) && ! empty( $rocket['lazyload'] )
			);
		}

		return self::$current;
	}

	/**
	 * The attribute for the plugins found: Ezoic first, as Lasso checks it first.
	 *
	 * @param bool $ezoic  Ezoic is active.
	 * @param bool $rocket WP Rocket is active with its image lazy-load on.
	 */
	public static function of( bool $ezoic, bool $rocket ): string {
		if ( $ezoic ) {
			return 'class="ezlazyload"';
		}

		return $rocket ? 'class="rocket-lazyload"' : 'loading="lazy"';
	}
}
