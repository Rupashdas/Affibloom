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
