<?php
/**
 * The editor's Custom CSS, as it may reach a page.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Settings;

/**
 * Custom CSS through Lasso's own allowlist (Helper::format_css; F16 AC10): it is printed inside a style element, so
 * `<` is removed and `</style>` can never be written; the characters CSS needs stay. Used when the setting is saved
 * and again when it is printed (Task 18B), so a value that came in another way (the Lasso import) is cleaned too.
 */
final class CustomCss {

	/**
	 * The CSS with every character outside the allowlist removed.
	 *
	 * @param string $raw Raw CSS.
	 */
	public static function clean( string $raw ): string {
		return trim( (string) preg_replace( '~[^a-zA-Z0-9\-\_\{\}\@\#\!\;\:\.\,\s\(\)\%\[\]=*"\'\~\|\^\$/>+?&]~', '', $raw ) );
	}
}
