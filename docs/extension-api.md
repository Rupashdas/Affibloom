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
| `add_page( string $slug, Closure $title, Closure $subtitle, Closure $body ): void` | LM1 | Adds one page to Affibloom's menu, before Settings, for users who may change Affibloom's settings. See below. |

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

## Filters a helper may answer

| Filter | Use |
|---|---|
| `affibloom_setup_wanted` | Return `false` so the setup wizard does not open by itself, for example on a site whose data the helper brings in. |

The other hooks Affibloom fires are listed in [`developer.md`](developer.md), section 3.
