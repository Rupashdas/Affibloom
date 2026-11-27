# Plugin Check and the WordPress.org directory (2026-11-28)

The user asked for the plugin to be run through **Plugin Check** and made ready for a WordPress.org release.

## How it was run

Plugin Check 2.2.0, installed on Local, against **the built release**, not the working folder: `affibloom.zip` is
unpacked into `wp-content/plugins/affibloom-dist/` and checked under the plugin's real slug, so the text domain matches
and no development file is judged.

```bash
npm run build && npm run plugin-zip          # the release, from a --no-dev vendor tree
wp plugin check affibloom-dist --slug=affibloom --format=json
```

Checking the working folder instead reports a few hundred findings that do not exist in a release: every
`TextDomainMismatch` (the folder's name is not the text domain), `hidden_files` (`.editorconfig`, `.gitignore`,
`.distignore`, `.gitattributes`, `.wp-env.json`, `.phpunit.result.cache`), `application_detected` (`phpcs.xml.dist`,
`phpstan.neon.dist`, the two `phpunit*.xml.dist`) and `compressed_files` (`affibloom.zip` and the builds in `.local/`).
None of them is in the zip: `package.json`'s `files` is a whitelist.

## Fixed for the release

| What Plugin Check said | What was done |
|---|---|
| `library_core_files`: `assets/front/copy-clipboard.js` | The file is ours, not a library; its **name** matched one WordPress ships. Renamed to `assets/front/copy-field.js` (the handle, classes and markup are unchanged, so the box's pixels are too). |
| `missing_direct_file_access_protection`: `resources/networks.php` | It already refused a direct request, but with `defined( 'ABSPATH' ) || defined( 'AFFIBLOOM_VERSION' ) || exit;`, which the check does not read. It now carries WordPress's own guard; the unit bootstrap defines `ABSPATH`. |
| `load_plugin_textdomain()` discouraged | Removed with its hook. WordPress loads a plugin's translations itself since 4.6, and from the plugin's own `languages/` since 6.7; the header's Text Domain and Domain Path are what it needs. |
| `SuppressFilters_suppress_filters` (8 errors) | Kept, each with a one-line `phpcs:ignore` saying why: all eight are exact lookups by slug or meta behind cloaked links and the slug guards. A filter that hides a post would let two addresses answer as one. |
| `readme_mismatched_header_requires` | The header said `Requires at least: 7.1.4`, the readme `7.1`. Both say `7.1` now, the form WordPress.org reads. |
| Minified code without its source | `blocks/` and `webpack.config.js` now ship beside `build/`, and the readme's **Source code** section says how to build them. |
| No external services section | The readme now has one, as the guidelines require: the Amazon Product Advertising API, Google Analytics (only with a Measurement ID), and the link check's requests to the sites you link to, each with what is sent, when, and the service's terms and privacy pages. |
| The header's description named one site | It is the plugin's own description now, as in the readme. |

## What the check still reports, and why it stays

**33 errors, `WordPress.Security.EscapeOutput.ExceptionNotEscaped`.** The sniff fires on every `throw` whose first
argument is not an escaping call — including `throw new RefreshFailed( RefreshFailed::NOT_FOUND )`, where the argument
is a code, not a message. Of the 33: 26 are in `src/Domain/`, where **no WordPress function may be called** (the
plugin's layering, `plans/00-overview.md`), so `esc_html()` is not available there; the rest pass a code, an array of
errors, or an English literal. None of these values is echoed: a message reaches a reader only through `WP_Error` in a
REST answer (JSON) or a template, which escapes at the point of output. The one exception message outside the Domain
that carried a value, `SettingsService`'s `LogicException`, is escaped where it is built, as `Template::render()` does.

**672 warnings, `PrefixAllGlobals.NonPrefixedVariableFound`,** all in `templates/`. The sniff reads a template as a
file at global scope. Every template is `include`d **inside `Template::render()`** (`src/Interface/Template.php`), so
`$view`, `$product` and the rest are that method's variables and never touch the global scope. Renaming 672 locals
would change nothing but the names.

**26 warnings, `PluginCheck.Security.DirectDB.UnescapedDBParameter`.** Affibloom's own tables. Each query is built from
a fixed string in the plugin's own code and run through `$wpdb->prepare()` with `%i` for the table and placeholders for
every value; the sniff cannot follow the SQL into the variable it is assigned to. The equivalent WPCS sniffs already
carry documented `phpcs:ignore` lines at those calls.

**1 warning, `PostNotIn_exclude`,** in `FieldsController`: `exclude` lists the few field IDs already on the product, so
the picker does not offer them twice. The list is short by construction.

## Before an actual submission

These are the user's to decide or do; none is code:

1. **The plugin becomes public.** Affibloom is written for one site today (its migrator from Lasso is a separate,
   private plugin and must stay out of the directory). A release means supporting it for everyone.
2. **The slug** `affibloom` must be free on WordPress.org, and the **Contributors** line (`devrupash`) must be a real
   WordPress.org account.
3. **Tested up to** must name the WordPress version current on the day of submission.
4. The directory's assets — icon, banner and screenshots — live in the SVN `assets/` folder, not in the zip.
5. **Trademarks:** the readme's `amazon` tag and the Amazon feature names describe an integration, which the
   guidelines allow, but the reviewer may still ask for the tag to go. The plugin's own name and slug carry no
   trademark.
6. A support page or email for the directory listing.
