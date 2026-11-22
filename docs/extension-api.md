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
