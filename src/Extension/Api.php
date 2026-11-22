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
	 * @param SettingsAccess  $settings   What a helper may do with settings.
	 * @param DisplayAccess   $display    What a helper may do with the Display settings.
	 * @param FieldAccess     $fields     What a helper may do with fields.
	 */
	public function __construct( private string $version, private AdminNavigation $navigation, private ExtensionScreen $pages, private ProductAccess $products, private SettingsAccess $settings, private DisplayAccess $display, private FieldAccess $fields ) {}

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
	 * Every setting's current value (Task 26C), by the keys `docs/developer.md` lists for `affibloom_settings`.
	 *
	 * @return array<string, mixed>
	 */
	public function settings(): array {
		return $this->settings->values();
	}

	/**
	 * Saves some settings of one Settings tab through the screen's own rules (Task 26C): `general`, `display`,
	 * `amazon`, or `import` (the tracking-ID blacklist and the default picture, which no screen shows). A switch not given keeps its
	 * value; a value the screen would refuse is not saved and its key is returned.
	 *
	 * @param string               $tab    The tab.
	 * @param array<string, mixed> $values Setting => value.
	 * @return list<string> The settings refused.
	 * @throws \InvalidArgumentException When the tab is unknown.
	 */
	public function save_settings( string $tab, array $values ): array {
		return $this->settings->save( $tab, $values );
	}

	/**
	 * Every display type's settings (Task 26D): `{ columns, single: { theme, palette, elements, density, layout },
	 * grid: …, list: … }`, as Settings → Display shows them.
	 *
	 * @return array<string, mixed>
	 */
	public function display_settings(): array {
		return $this->display->values();
	}

	/**
	 * Saves one display type's settings through the Display screen's rules (Task 26D), given in the shape
	 * display_settings() reads them; a part not given keeps its value, and `columns` is the grid's, site-wide.
	 *
	 * @param string               $type   `single`, `grid` or `list`.
	 * @param array<string, mixed> $config Any of `theme`, `palette`, `elements`, `density`, `layout`, `columns`.
	 * @return list<string> The colours and labels refused (they keep their values).
	 * @throws \InvalidArgumentException When the type is unknown.
	 */
	public function save_display( string $type, array $config ): array {
		return $this->display->save( $type, $config );
	}

	/**
	 * Every field (Task 26E): the built-in ones with Lasso's IDs (1-3, 99989-99994), then the created ones, each
	 * `{ id, name, type, description, built_in }`.
	 *
	 * @return list<array{id: int, name: string, type: string, description: string, built_in: bool}>
	 */
	public function field_definitions(): array {
		return $this->fields->definitions();
	}

	/**
	 * Creates a field through the Fields screen's rules (Task 26E).
	 *
	 * @param string $name        Its name (80 characters at most).
	 * @param string $type        Its kind, such as `text`, `textarea`, `number`, `rating` or `bulleted_list`.
	 * @param string $description Its description.
	 * @return int Its ID.
	 * @throws \InvalidArgumentException When a value is refused.
	 */
	public function create_field( string $name, string $type, string $description = '' ): int {
		return $this->fields->create( $name, $type, $description );
	}

	/**
	 * Renames a field (Task 26E); a built-in field takes a name only.
	 *
	 * @param int    $id   The field.
	 * @param string $name Its name.
	 * @throws \InvalidArgumentException When there is no such field or the name is refused.
	 */
	public function rename_field( int $id, string $name ): void {
		$this->fields->rename( $id, $name );
	}

	/**
	 * A published product's field rows in display order, `{ field, value, visible, show_name }`, or null (Task 26E).
	 *
	 * @param int $product The product.
	 * @return list<array{field: int, value: string, visible: bool, show_name: bool}>|null
	 */
	public function product_field_rows( int $product ): ?array {
		return $this->fields->rows( $product );
	}

	/**
	 * Sets a published product's field rows in this order, as the product page's Fields save them (Task 26E): each
	 * field is attached, rows not given are taken off, a rating is kept to Lasso's steps.
	 *
	 * @param int                                                                      $product The product.
	 * @param list<array{field: int, value: string, visible?: bool, show_name?: bool}> $rows    The rows.
	 * @throws \InvalidArgumentException When the product is not published or a field does not exist.
	 */
	public function set_product_field_rows( int $product, array $rows ): void {
		$this->fields->set_rows( $product, $rows );
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
