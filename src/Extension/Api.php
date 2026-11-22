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
	 * @param string          $version    Affibloom's version.
	 * @param AdminNavigation $navigation The screens, which the menu lists.
	 * @param ExtensionScreen $pages      The added pages.
	 * @param ProductAccess   $products   What a helper may do with products.
	 */
	public function __construct( private string $version, private AdminNavigation $navigation, private ExtensionScreen $pages, private ProductAccess $products ) {}

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
		return $this->products->check( $fields );
	}

	/**
	 * A new published product copied from a record (Task 26A): the fields as check_product() takes them, stored with
	 * Save Changes' rules but as given: no request, no other product reused, no tracking-ID rule, nothing fetched from
	 * Amazon. An Amazon link makes an Amazon product.
	 *
	 * @param array<string, mixed> $fields   Field => value; `name` and `url` needed.
	 * @param string               $created  Its created date, "Y-m-d H:i:s" in the site's time; '' for now.
	 * @param string               $modified Its changed date, the same way.
	 * @return int The product's ID.
	 * @throws \InvalidArgumentException When a field is refused ("url: invalid_url").
	 */
	public function import_product( array $fields, string $created = '', string $modified = '' ): int {
		return $this->products->import( $fields, $created, $modified );
	}

	/**
	 * A product's fields as the product page holds them (the keys check_product() takes), or null when there is none.
	 *
	 * @param int $id The product.
	 * @return array<string, mixed>|null
	 */
	public function product_fields( int $id ): ?array {
		return $this->products->fields( $id );
	}

	/**
	 * Changes a published product's fields, as the product page's Save Changes does (Task 26B).
	 *
	 * @param int                  $id     The product.
	 * @param array<string, mixed> $fields Field => value; a field not sent keeps its value.
	 * @throws \InvalidArgumentException When a field is refused or there is no such published product.
	 */
	public function update_product( int $id, array $fields ): void {
		$this->products->update( $id, $fields );
	}

	/**
	 * Retires a product, pointing at the product that took its place (0 for none), as a merge does (Task 26A).
	 *
	 * @param int $id The product.
	 * @param int $to The product it points at.
	 * @throws \InvalidArgumentException When there is no such product.
	 */
	public function retire_product( int $id, int $to ): void {
		$this->products->retire( $id, $to );
	}

	/**
	 * Writes a product's created and changed dates as given (Task 26A): a record's dates kept after its product was
	 * retired or trashed, which stamps "now".
	 *
	 * @param int    $id       The product.
	 * @param string $created  "Y-m-d H:i:s" in the site's time.
	 * @param string $modified The same way.
	 */
	public function date_product( int $id, string $created, string $modified ): void {
		$this->products->date( $id, $created, $modified );
	}

	/**
	 * Moves a product to the trash (Task 26A).
	 *
	 * @param int $id The product.
	 * @throws \InvalidArgumentException When it could not be trashed.
	 */
	public function trash_product( int $id ): void {
		$this->products->trash( $id );
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
