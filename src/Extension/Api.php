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
	 * @param string            $version    Affibloom's version.
	 * @param AdminNavigation   $navigation The screens, which the menu lists.
	 * @param ExtensionScreen   $pages      The added pages.
	 * @param ProductAccess     $products   What a helper may do with products.
	 * @param SettingsAccess    $settings   What a helper may do with settings.
	 * @param DisplayAccess     $display    What a helper may do with the Display settings.
	 * @param FieldAccess       $fields     What a helper may do with fields.
	 * @param GroupAccess       $groups     What a helper may do with groups.
	 * @param AmazonAccess      $amazon     What a helper may do with the Amazon product data.
	 * @param ViewAccess        $views      What a helper may do with saved Dashboard views and the tour.
	 * @param VariantAccess     $variants   What a helper may do with a product's URL variants.
	 * @param IssueAccess       $issues     What a helper may do with link issues and their dismissals.
	 * @param TableAccess       $tables     What a helper may do with comparison tables.
	 * @param OpportunityAccess $opportunities What a helper may do with dismissed opportunities.
	 * @param BlockAccess       $blocks     What a helper may do with the Product box block (Task LM2).
	 * @param AiTextAccess      $ai_texts   What a helper may do with the AI text display's stored texts (Task LM5).
	 */
	public function __construct( private string $version, private AdminNavigation $navigation, private ExtensionScreen $pages, private ProductAccess $products, private SettingsAccess $settings, private DisplayAccess $display, private FieldAccess $fields, private GroupAccess $groups, private AmazonAccess $amazon, private ViewAccess $views, private VariantAccess $variants, private IssueAccess $issues, private TableAccess $tables, private OpportunityAccess $opportunities, private BlockAccess $blocks, private AiTextAccess $ai_texts ) {}

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
	 * Every group, `{ id, name, slug, description }` (Task 26F).
	 *
	 * @return list<array{id: int, name: string, slug: string, description: string}>
	 */
	public function groups(): array {
		return $this->groups->all();
	}

	/**
	 * Creates a group through the Groups screen's rules (Task 26F); its slug comes from its name.
	 *
	 * @param string $name        Its name (it may not repeat).
	 * @param string $description Its description.
	 * @return int Its ID.
	 * @throws \InvalidArgumentException When the name is refused.
	 */
	public function create_group( string $name, string $description = '' ): int {
		return $this->groups->create( $name, $description );
	}

	/**
	 * Changes a group's name and description; its slug is kept (Task 26F).
	 *
	 * @param int    $id          The group.
	 * @param string $name        Its name.
	 * @param string $description Its description.
	 * @throws \InvalidArgumentException When there is no such group or the name is refused.
	 */
	public function update_group( int $id, string $name, string $description ): void {
		$this->groups->update( $id, $name, $description );
	}

	/**
	 * A group's published products, in its order (Task 26F).
	 *
	 * @param int $id The group.
	 * @return list<int>
	 */
	public function group_members( int $id ): array {
		return $this->groups->members( $id );
	}

	/**
	 * Makes a group's products these published ones, in this order (Task 26F): missing ones are added, others taken out.
	 *
	 * @param int       $id       The group.
	 * @param list<int> $products Product IDs, in order.
	 * @throws \InvalidArgumentException When there is no such group or a product is not published.
	 */
	public function set_group_members( int $id, array $products ): void {
		$this->groups->set_members( $id, $products );
	}

	/**
	 * A cached Amazon product, `{ name, price, currency, base_url, monetized_url, image, out_of_stock, savings_amount,
	 * savings_percent, savings_basis, features, is_prime, last_updated, source }`, or null (Task 26L).
	 *
	 * @param string $asin    The ASIN.
	 * @param string $country The store, such as "us".
	 * @return array<string, mixed>|null
	 */
	public function amazon_product( string $asin, string $country ): ?array {
		return $this->amazon->product( $asin, $country );
	}

	/**
	 * Stores a cached Amazon product another plugin had, marked `imported` (Task 26L): the box shows it until
	 * Affibloom's own refresh replaces it.
	 *
	 * @param string               $asin    The ASIN.
	 * @param string               $country The store.
	 * @param array<string, mixed> $data    The fields amazon_product() gives, `source` aside.
	 * @throws \InvalidArgumentException When the ASIN or the store is not one.
	 */
	public function import_amazon_product( string $asin, string $country, array $data ): void {
		$this->amazon->import( $asin, $country, $data );
	}

	/**
	 * A user's saved Dashboard views, each `{ id, name, filters: { s, link_type, per_page, group, filter, placements,
	 * orderby, order, comparison_table } }` (Task 26M).
	 *
	 * @param int $user The user's ID.
	 * @return list<array{id: string, name: string, filters: array<string, string|int>}>
	 */
	public function views( int $user ): array {
		return $this->views->views( $user );
	}

	/**
	 * Saves a Dashboard view for a user in the shape views() gives, replacing the one with its ID (Task 26M). A filter
	 * value Affibloom does not know falls back to its default, as on the Dashboard.
	 *
	 * @param int          $user The user's ID.
	 * @param array<mixed> $view The view.
	 * @throws \InvalidArgumentException When it is not a view, or the name or the count is refused ("name: view_name_empty").
	 */
	public function save_view( int $user, array $view ): void {
		$this->views->save( $user, $view );
	}

	/**
	 * Whether the Dashboard's first-visit tour was ended for every user (Task 26M).
	 */
	public function tour_done(): bool {
		return $this->views->tour_done();
	}

	/**
	 * Ends the Dashboard's first-visit tour for every user, as its Skip does (Task 26M).
	 */
	public function finish_tour(): void {
		$this->views->finish_tour();
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
	 * A published or retired product's cloaked address, `{ slug, old_slugs, cloak, retired }`, or null when there is no
	 * such product (Task 26G; a retired product's address still answers, the cutover rehearsal's F2).
	 *
	 * @param int $id The product.
	 * @return array{slug: string, old_slugs: list<string>, cloak: bool, retired: bool}|null
	 */
	public function product_address( int $id ): ?array {
		return $this->products->address( $id );
	}

	/**
	 * The product that answers at a slug, as its slug or an old one; 0 when none (Task 26G).
	 *
	 * @param string $slug The slug.
	 */
	public function product_at_slug( string $slug ): int {
		return $this->products->holder( $slug );
	}

	/**
	 * Sets a published or retired product's slug, the old slugs that keep answering and its cloaking switch; a retired
	 * product stays retired (Task 26G; the cutover rehearsal's F2).
	 *
	 * @param int          $id        The product.
	 * @param string       $slug      Its slug.
	 * @param list<string> $old_slugs Its old slugs.
	 * @param bool         $cloak     Its cloaking switch.
	 * @throws \InvalidArgumentException When a slug is not one or another product answers at it ("slug: taken (…)").
	 */
	public function set_product_address( int $id, string $slug, array $old_slugs, bool $cloak ): void {
		$this->products->set_address( $id, $slug, $old_slugs, $cloak );
	}

	/**
	 * A published product's URL variants, each `{ name, key, kind }` (`permalink` or `amazon_tag`), or null when there
	 * is no such product (Task 26H).
	 *
	 * @param int $id The product.
	 * @return list<array{name: string, key: string, kind: string}>|null
	 */
	public function product_variants( int $id ): ?array {
		return $this->variants->all( $id );
	}

	/**
	 * Makes a product's URL variants this list of `{ name, key }`, through the Variants tab's rules; every row takes the
	 * product's kind (Task 26H).
	 *
	 * @param int                                      $id   The product.
	 * @param list<array{name?: string, key?: string}> $rows The rows.
	 * @throws \InvalidArgumentException When a row is refused ("0.key: taken_by_post").
	 */
	public function set_product_variants( int $id, array $rows ): void {
		$this->variants->set( $id, $rows );
	}

	/**
	 * A product's link-health row `{ issue_type, checked_url, detection_date, resolved, resolved_at, last_checked_at }`
	 * (UTC, '' for none), or null when it has none (Task 26K).
	 *
	 * @param int $id The product.
	 * @return array{issue_type: string, checked_url: string, detection_date: string, resolved: bool, resolved_at: string, last_checked_at: string}|null
	 */
	public function link_issue( int $id ): ?array {
		return $this->issues->get( $id );
	}

	/**
	 * Stores a published product's link-health row as link_issue() gives it (Task 26K).
	 *
	 * @param int                  $id  The product.
	 * @param array<string, mixed> $row The row.
	 * @throws \InvalidArgumentException When there is no such product, or the code or the last check is not one.
	 */
	public function import_link_issue( int $id, array $row ): void {
		$this->issues->import( $id, $row );
	}

	/**
	 * Until when a product's issue bar (`broken` or `out-of-stock`) stays closed, a Unix time; 0 when open (Task 26K).
	 *
	 * @param int    $id  The product.
	 * @param string $bar The bar.
	 */
	public function issue_dismissed_until( int $id, string $bar ): int {
		return $this->issues->dismissed_until( $id, $bar );
	}

	/**
	 * Closes a product's issue bar until a Unix time (Task 26K).
	 *
	 * @param int    $id    The product.
	 * @param string $bar   `broken` or `out-of-stock`.
	 * @param int    $until The time.
	 */
	public function dismiss_issue( int $id, string $bar, int $until ): void {
		$this->issues->dismiss( $id, $bar, $until );
	}

	/**
	 * A comparison table whole, `{ name, style, theme, show_headers, show_field_name, products: [{ id, badge }],
	 * groups: [{ id, fields }], values: { product: { field: value } } }`, or null when there is none (Task 26I).
	 *
	 * @param int $id The table.
	 * @return array{name: string, style: string, theme: string, show_headers: bool, show_field_name: bool, products: list<array{id: int, badge: string}>, groups: list<array{id: string, fields: list<int>}>, values: array<int, array<int, string>>}|null
	 */
	public function table( int $id ): ?array {
		return $this->tables->get( $id );
	}

	/**
	 * Stores a comparison table whole, in table()'s shape: a new one when $id is 0, else in place of that table
	 * (Task 26I). A button cell is its JSON, as the builder stores it.
	 *
	 * @param int                  $id   The table to replace; 0 for a new one.
	 * @param array<string, mixed> $data The table.
	 * @return int The table's ID.
	 * @throws \InvalidArgumentException When the name is blank or a product or a field does not exist.
	 */
	public function put_table( int $id, array $data ): int {
		return $this->tables->put( $id, $data );
	}

	/**
	 * Whether every place a post holds a link is dismissed on the Opportunities screens; null when the link index has
	 * none (Task 26N).
	 *
	 * @param int    $post The post.
	 * @param string $url  The link.
	 */
	public function opportunity_dismissed( int $post, string $url ): ?bool {
		return $this->opportunities->dismissed( $post, $url );
	}

	/**
	 * Dismisses every place a post holds a link on the Opportunities screens, as Dismiss does; it stays dismissed across
	 * rescans (Task 26N).
	 *
	 * @param int    $post The post.
	 * @param string $url  The link.
	 * @return int How many places were dismissed; 0 when the link index has none.
	 */
	public function dismiss_opportunity( int $post, string $url ): int {
		return $this->opportunities->dismiss( $post, $url );
	}

	/**
	 * The Product box block's attributes for a display named by `[affibloom …]`'s attributes (`type`, `productId`,
	 * `groupId`, `tableId`, `overrides`, and the schema panel's), with the attribute names the block would lose; null for
	 * attributes that name no product, group or table (Task LM2; F10 D16).
	 *
	 * @param array<int|string, mixed> $attributes The shortcode's attributes, as WordPress parses them.
	 * @return array{attributes: array<string, mixed>, lost: list<string>}|null
	 */
	public function block_for_shortcode( array $attributes ): ?array {
		return $this->blocks->from_shortcode( $attributes );
	}

	/**
	 * The AI text display's stored text for a prompt and format, or null (Task LM5; F30 D14).
	 *
	 * @param string $prompt The shortcode's `prompt`.
	 * @param string $format Its `format`: `html` or `text` ('' is `html`).
	 */
	public function ai_text( string $prompt, string $format ): ?string {
		return $this->ai_texts->find( $prompt, $format );
	}

	/**
	 * Keeps the AI text display's text for a prompt and format, as another plugin cached it (Task LM5); Affibloom never
	 * asks a service for one.
	 *
	 * @param string $prompt The shortcode's `prompt`.
	 * @param string $format Its `format`: `html` or `text` ('' is `html`).
	 * @param string $text   The text.
	 */
	public function store_ai_text( string $prompt, string $format, string $text ): void {
		$this->ai_texts->store( $prompt, $format, $text );
	}

	/**
	 * The original URL of a post's link: its own URL until set_original_link() gives another (Task 26Q); null when the
	 * link index has none.
	 *
	 * @param int    $post The post.
	 * @param string $url  The link as the content has it now.
	 */
	public function original_link( int $post, string $url ): ?string {
		return $this->opportunities->original_url( $post, $url );
	}

	/**
	 * Gives a post's link the URL it had before another plugin rewrote it, so Monetize's switch can turn it back to that
	 * URL at render time (Tasks 26Q, GF6; F22 N3, E17). A place given one before keeps it, so a second run changes nothing.
	 *
	 * @param int    $post     The post.
	 * @param string $url      The link as the content has it now.
	 * @param string $original The link before it was rewritten.
	 * @return int How many places were given it; 0 when the link index has none.
	 */
	public function set_original_link( int $post, string $url, string $original ): int {
		return $this->opportunities->set_original_url( $post, $url, $original );
	}

	/**
	 * Each place a post holds a link in the link index (Task LM6): `product`, the product its own address ties it to (0
	 * for none); `assigned`, the product an editor assigned it (0 for none); `original`, whether it is turned back to its
	 * original URL.
	 *
	 * @param int    $post The post.
	 * @param string $url  The link.
	 * @return list<array{product: int, assigned: int, original: bool}> Empty when the link index has none.
	 */
	public function link_ties( int $post, string $url ): array {
		return $this->opportunities->ties( $post, $url );
	}

	/**
	 * Ties a post's link to a product (Task LM6; as Opportunities' Assign, F22 N1-A): every place its own address ties
	 * to another product or to none. A place an editor assigned, or turned back to its original URL, keeps that; the
	 * assignment is kept across a rescan.
	 *
	 * @param int    $post    The post.
	 * @param string $url     The link.
	 * @param int    $product The product.
	 * @return int How many places were tied.
	 */
	public function tie_link( int $post, string $url, int $product ): int {
		return $this->opportunities->tie( $post, $url, $product );
	}

	/**
	 * Clears a post's link's assignment to a product, where it has one (Task LM6's revert).
	 *
	 * @param int    $post    The post.
	 * @param string $url     The link.
	 * @param int    $product The product.
	 * @return int How many places were cleared.
	 */
	public function untie_link( int $post, string $url, int $product ): int {
		return $this->opportunities->untie( $post, $url, $product );
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
