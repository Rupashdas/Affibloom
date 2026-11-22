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
	 * @param string                                               $version       Affibloom's version.
	 * @param AdminNavigation                                      $navigation    The screens, which the menu lists.
	 * @param ExtensionScreen                                      $pages         The added pages.
	 * @param Closure(array<string, mixed>): array<string, string> $product_check ProductService::check().
	 */
	public function __construct( private string $version, private AdminNavigation $navigation, private ExtensionScreen $pages, private Closure $product_check ) {}

	/**
	 * Affibloom's version, for a helper that needs a newer one.
	 */
	public function version(): string {
		return $this->version;
	}

	/**
	 * What Affibloom would refuse in a product's fields, nothing stored (Task 25A): the product page's Save Changes
	 * rules, so a helper can report a value Affibloom will not take before it writes anything. Fields are the product
	 * page's (`name`, `url`, `description`, `buttons`, …; `docs/extension-api.md`); a field not sent is not checked.
	 *
	 * @param array<string, mixed> $fields Field => value.
	 * @return array<string, string> Field => reason code (`required`, `invalid_url`, …); empty when all are accepted.
	 */
	public function check_product( array $fields ): array {
		return ( $this->product_check )( $fields );
	}

	/**
	 * Adds a page to Affibloom's menu, before Settings, for those who may change settings. Affibloom prints its header,
	 * the page head with the title and subtitle, and its footer; the body prints the rest inside the page body. One
	 * page: a second call takes the first one's place. The texts are closures because this runs on plugins_loaded,
	 * before translations load. An unlisted page (Task 25D) is reached by its address only, as a detail page.
	 *
	 * @param string            $slug     The admin page slug, starting with "affibloom-".
	 * @param Closure(): string $title    The menu label and the page title, translated when called.
	 * @param Closure(): string $subtitle The line under the title, translated when called.
	 * @param Closure(): void   $body     Prints the page's content, escaped.
	 * @param bool              $listed   Whether the menu lists it.
	 */
	public function add_page( string $slug, Closure $title, Closure $subtitle, Closure $body, bool $listed = true ): void {
		$this->pages->add( $slug, $title, $subtitle, $body, $listed );
		$this->navigation->register( new NavItem( self::PAGE_PLACE, $slug, $title, '', Capabilities::SETTINGS ) );
	}

	/**
	 * The capability an added page asks for (who may change Affibloom's settings), for a helper's own form or download
	 * on that page (Task 25D).
	 */
	public function capability(): string {
		return Capabilities::SETTINGS;
	}
}
