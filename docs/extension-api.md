# Affibloom extension API

The one supported way for a helper plugin to use Affibloom (Task LM1). A helper calls only what is listed here, never
Affibloom's classes, options or tables directly. The API grows with its callers: a method is added by the task that
first needs it, listed here and covered by Affibloom's unit tests (`tests/Unit/Extension/`).

## Getting the API

Affibloom fires the action `affibloom_ready` once, on `plugins_loaded`, with an `Affibloom\Extension\Api`. Hook it when
your plugin's file loads; it works whichever plugin loads first.

```php
add_action( 'affibloom_ready', static function ( \Affibloom\Extension\Api $api ): void {
	if ( version_compare( $api->version(), '1.0.0-rc.1', '<' ) ) {
		return; // Too old: say so in a notice and do nothing.
	}
	// … use $api.
} );
```

If the action never fires, Affibloom is inactive or older than the API. Put `Requires Plugins: affibloom` in your
plugin's header, so WordPress will not activate the helper without Affibloom.

## Methods

| Method | Since | What it does |
|---|---|---|
| `version(): string` | LM1 | Affibloom's version (`AFFIBLOOM_VERSION`), for `version_compare()`. |
| `add_page( string $slug, Closure $title, Closure $subtitle, Closure $body, bool $listed = true ): void` | LM1 (`$listed` 25D) | Adds one page to Affibloom's menu, before Settings, for users who may change Affibloom's settings. See below. |
| `capability(): string` | 25D | The capability an added page asks for, for the helper's own form or download on that page (`current_user_can()`). |
| `import_product( array $fields, string $created = '', string $modified = '' ): int` | 26A | A new published product copied from a record: the fields as `check_product()` takes them, stored with Save Changes' rules but as given (no request, no other product reused, no tracking-ID rule, nothing fetched from Amazon); an Amazon link makes an Amazon product. Throws `InvalidArgumentException` naming the refused fields. |
| `product_fields( int $id ): ?array` | 26A | A product's fields in the same shape, or null. |
| `update_product( int $id, array $fields ): void` | 26B | Changes a published product's fields, as Save Changes does. |
| `retire_product( int $id, int $to ): void` | 26A | Retires a product pointing at the product that took its place (0 for none). |
| `trash_product( int $id ): void` | 26A | Moves a product to the trash. |
| `date_product( int $id, string $created, string $modified ): void` | 26A | Writes a product's dates as given (after a status change, which stamps "now"). |
| `settings(): array` | 26C | Every setting's current value (`affibloom_settings`' keys, `developer.md` section 8). |
| `save_settings( string $tab, array $values ): array` | 26C | Saves some settings of `general`, `display`, `amazon`, `logs` or `import` (the tracking-ID blacklist, the default picture and `education_hidden`, the Opportunities intro boxes closed for good: `links`, `domains`, `content`; none shown on a screen) through the screen's rules; a switch not given keeps its value. Returns the keys refused. |
| `display_settings(): array` | 26D | Every display type's settings: `{ columns, single: { theme, palette, elements, density, layout }, grid: …, list: … }`. |
| `save_display( string $type, array $config ): array` | 26D | Saves one type's settings through the Display screen's rules, in the shape `display_settings()` reads them; a part not given keeps its value; `columns` is the grid's. Returns the colours and labels refused. |
| `field_definitions(): array` | 26E | Every field `{ id, name, type, description, built_in }`: the built-in ones with Lasso's IDs (1-3, 99989-99994), then the created ones. |
| `create_field( string $name, string $type, string $description = '' ): int` | 26E | Creates a field through the Fields screen's rules; returns its ID. |
| `rename_field( int $id, string $name ): void` | 26E | Renames a field (a built-in one takes a name only). |
| `product_field_rows( int $product ): ?array` | 26E | A published product's field rows `{ field, value, visible, show_name }` in display order, or null. |
| `set_product_field_rows( int $product, array $rows ): void` | 26E | Sets a published product's field rows as the product page saves them: fields attached, rows not given taken off, Primary Rating first. |
| `groups(): array` | 26F | Every group `{ id, name, slug, description }`. |
| `create_group( string $name, string $description = '' ): int` | 26F | Creates a group through the Groups screen's rules; its slug comes from its name. |
| `update_group( int $id, string $name, string $description ): void` | 26F | Changes a group's name and description; the slug is kept. |
| `group_members( int $id ): array` | 26F | A group's published products, in its order. |
| `set_group_members( int $id, array $products ): void` | 26F | Makes a group's products these published ones, in this order. |
| `amazon_product( string $asin, string $country ): ?array` | 26L | A cached Amazon product `{ name, price, currency, base_url, monetized_url, image, out_of_stock, savings_amount, savings_percent, savings_basis, features, is_prime, last_updated, source }`, or null. |
| `import_amazon_product( string $asin, string $country, array $data ): void` | 26L | Stores a cached Amazon product another plugin had, marked `imported`, until Affibloom's own refresh replaces it. |
| `views( int $user ): array` | 26M | A user's saved Dashboard views, each `{ id, name, filters: { s, link_type, per_page, group, filter, placements, orderby, order, comparison_table } }`. |
| `save_view( int $user, array $view ): void` | 26M | Saves a view in the shape `views()` gives, replacing the one with its ID; a filter value Affibloom does not know falls back to its default. Throws `InvalidArgumentException` when the name or the count (30) is refused. |
| `tour_done(): bool` | 26M | Whether the Dashboard's first-visit tour was ended for every user. |
| `finish_tour(): void` | 26M | Ends the Dashboard's first-visit tour for every user, as its Skip does. |
| `product_address( int $id ): ?array` | 26G | A product's cloaked address `{ slug, old_slugs, cloak }`, or null. |
| `product_at_slug( string $slug ): int` | 26G | The product that answers at a slug, as its slug or an old one; 0 when none. |
| `set_product_address( int $id, string $slug, array $old_slugs, bool $cloak ): void` | 26G | Sets a product's slug, the old slugs that keep answering and its cloaking switch. A slug another product answers at, as its slug or an old one, is refused (`InvalidArgumentException`, "slug: taken (…)"). |
| `product_variants( int $id ): ?array` | 26H | A published product's URL variants, each `{ name, key, kind }` (`permalink` or `amazon_tag`), or null. |
| `set_product_variants( int $id, array $rows ): void` | 26H | Makes a product's variants this list of `{ name, key }` through the Variants tab's rules; every row takes the product's kind. Throws `InvalidArgumentException` at the first row refused ("0.key: taken_by_post"). |
| `link_issue( int $id ): ?array` | 26K | A product's link-health row `{ issue_type, checked_url, detection_date, resolved, resolved_at, last_checked_at }` (UTC, '' for none), or null. |
| `import_link_issue( int $id, array $row ): void` | 26K | Stores a published product's link-health row in that shape, with no failure waiting for confirmation, so the Alert column shows it before Affibloom's own first check. |
| `issue_dismissed_until( int $id, string $bar ): int` | 26K | Until when a product's issue bar (`broken` or `out-of-stock`) stays closed, a Unix time; 0 when open. |
| `dismiss_issue( int $id, string $bar, int $until ): void` | 26K | Closes a product's issue bar until that Unix time. |
| `table( int $id ): ?array` | 26I | A comparison table whole: `{ name, style, theme, show_headers, show_field_name, products: [{ id, badge }], groups: [{ id, fields }], values: { product: { field: value } } }`, or null. |
| `put_table( int $id, array $data ): int` | 26I | Stores a table whole in `table()`'s shape: new when `$id` is 0, else in place of that table. A button cell is its JSON, as the builder stores it. Throws `InvalidArgumentException` when the name is blank or a product or field does not exist. |
| `opportunity_dismissed( int $post, string $url ): ?bool` | 26N | Whether every place a post holds a link is dismissed on the Opportunities screens; null when the link index has none. |
| `dismiss_opportunity( int $post, string $url ): int` | 26N | Dismisses every place a post holds a link, as Dismiss does (it stays dismissed across rescans); returns how many. |
| `block_for_shortcode( array $attributes ): ?array` | LM2 | The Product box block's attributes for `[affibloom …]`'s attributes (`type`, `productId`, `groupId`, `tableId`, `overrides`, the schema panel's), with `lost`: the names the block would not keep (an unknown name, or a toggle's value other than `hide`/`enable`); null when they name no product, group or table. |
| `original_link( int $post, string $url ): ?string` | 26Q | The original URL of a post's link: its own URL until `set_original_link()` gives another; null when the link index has none. |
| `set_original_link( int $post, string $url, string $original ): int` | 26Q, GF6 | Gives every place a post holds a link the URL it had before another plugin rewrote it, so Opportunities → a post's links can turn it back to that URL at render time; a place given one before keeps it. Returns how many. |
| `check_product( array $fields ): array` | 25A | What the product page's Save Changes would refuse in these fields, nothing stored: field => reason code (`required`, `invalid_url`), empty when all are accepted. Fields are the product page's: `name`, `url`, `new_tab`, `nofollow`, `sponsored`, `show_disclosure`, `button_text`, `badge`, `buttons` (a list of `url`, `text`, `new_tab`, `nofollow`), `theme`, `image_url`, `image_id`, `price`, `show_price`, `description`, `disclosure`, `prime`. A field not sent is not checked. |

### `add_page()`

- `$slug`: the admin page slug (`admin.php?page=<slug>`). Start it with `affibloom-`, so Affibloom's admin CSS and
  scripts load there and its menu stays open.
- `$title`, `$subtitle`: closures that return translated text. They are closures because `affibloom_ready` runs before
  translations load. The title is the menu label and the page title.
- `$body`: a closure that prints the page's content, escaped. Affibloom prints the rest in its own design: the header
  with its tabs and notices, the page head with the title and subtitle, and the footer. The body goes inside
  `.affibloom-page__body`. Use Affibloom's admin classes (`affibloom-card`, `affibloom-empty`, `affibloom-button`; see
  `docs/ui/design-system.md`) so the page looks like Affibloom's other pages.
- There is one page per site: a second call takes the first one's place, in the menu where Lasso lists Import. The page
  is in the WordPress menu only, not in the header's tabs.
- `$listed = false` keeps the page reachable by its address, capability-checked, without a menu entry (as Affibloom's
  detail pages).

## Filters a helper may answer

| Filter | Use |
|---|---|
| `affibloom_setup_wanted` | Return `false` so the setup wizard does not open by itself, for example on a site whose data the helper brings in. |

The other hooks Affibloom fires are listed in [`developer.md`](developer.md), section 3.
