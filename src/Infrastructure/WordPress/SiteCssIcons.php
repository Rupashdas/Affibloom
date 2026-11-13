<?php
/**
 * The icons the site's Additional CSS defines.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\Icons\SiteIcons;
use Affibloom\Domain\Icons\CssIconRules;
use Affibloom\Domain\Icons\Icon;
use Affibloom\Domain\InvalidField;

/**
 * The icons the site's Additional CSS (Appearance → Customize, the active theme's custom_css post) defines, with the
 * media library item each image is, when it is one.
 */
final class SiteCssIcons implements SiteIcons {

	/**
	 * The site's icons, in the CSS's order. The image is kept as the CSS writes it (the CSS keeps drawing it);
	 * only the attachment lookup uses an absolute link, since attachment_url_to_postid() compares against the uploads
	 * URL. A rule whose image breaks Icon's rule is skipped.
	 *
	 * @return list<Icon>
	 */
	public function found(): array {
		$icons = array();
		foreach ( CssIconRules::parse( wp_get_custom_css() ) as [ $class, $url ] ) {
			$absolute = 1 === preg_match( '#^https?://#i', $url ) ? $url : home_url( '/' . ltrim( $url, '/' ) );
			try {
				$icons[] = Icon::imported( $class, $url, attachment_url_to_postid( $absolute ) );
			} catch ( InvalidField ) {
				continue;
			}
		}

		return $icons;
	}
}
