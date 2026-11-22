<?php
/**
 * Affibloom's extension API for helper plugins.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Extension;

use Affibloom\Interface\Admin\AdminNavigation;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\ExtensionScreen;
use Affibloom\Interface\Admin\NavItem;
use Closure;

/**
 * The one supported way into Affibloom for a helper plugin (Task LM1; `docs/extension-api.md`). A helper gets it from
 * the action `affibloom_ready` and calls only these methods, never Affibloom's classes or tables. It grows with its
 * callers: each method is added by the task that first calls it.
 */
final class Api {

	/**
	 * Where an added page is listed: Lasso's menu has Import there, before Settings (AdminNavigation::MENU).
	 */
	private const PAGE_PLACE = 'import';

	/**
	 * Builds the API.
	 *
	 * @param string          $version    Affibloom's version (AFFIBLOOM_VERSION).
	 * @param AdminNavigation $navigation The screens in Lasso's order, which the menu lists.
	 * @param ExtensionScreen $pages      The added pages, printed in Affibloom's design.
	 */
	public function __construct( private string $version, private AdminNavigation $navigation, private ExtensionScreen $pages ) {}

	/**
	 * Affibloom's version, for a helper that needs a newer one.
	 */
	public function version(): string {
		return $this->version;
	}

	/**
	 * Adds a page to Affibloom's menu, before Settings, for those who may change settings. Affibloom prints its header,
	 * the page head with the title and subtitle, and its footer; the body prints the rest inside the page body. One
	 * page: a second call takes the first one's place. The texts are closures because this runs on plugins_loaded,
	 * before translations load.
	 *
	 * @param string            $slug     The admin page slug, starting with "affibloom-".
	 * @param Closure(): string $title    The menu label and the page title, translated when called.
	 * @param Closure(): string $subtitle The line under the title, translated when called.
	 * @param Closure(): void   $body     Prints the page's content, escaped.
	 */
	public function add_page( string $slug, Closure $title, Closure $subtitle, Closure $body ): void {
		$this->pages->add( $slug, $title, $subtitle, $body );
		$this->navigation->register( new NavItem( self::PAGE_PLACE, $slug, $title, '', Capabilities::SETTINGS ) );
	}
}
