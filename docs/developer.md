# Affibloom developer guide

For whoever builds, tests or releases Affibloom. The owner's and editors' guide is [`admin-guide.md`](admin-guide.md).
This file names every entry point and every stored name; `tests/Unit/Docs/DocsCoverageTest.php` fails when the code
gains a REST route, WP-CLI command, hook, shortcode, cron hook or stored name that is not written here.

- **Requires:** WordPress 7.1.4 or later and PHP 8.3 or later (production: WP 7.1.4, PHP 8.3, MariaDB 10.11).
  `affibloom.php` checks both versions before loading anything and shows an admin notice instead of failing.
- **Repository:** private, `https://github.com/Rupashdas/Affibloom`. Work happens on `main`, one commit per task.
- **Text domain** `affibloom`; prefix `affibloom_` for options, hooks and tables; constants `AFFIBLOOM_`; namespace
  `Affibloom\`; CSS classes `affibloom-`.
- **Standalone.** Affibloom has no code that reads, names or calls Lasso (`NoLassoInCoreTest`). Moving a site from Lasso
  is the job of a separate plugin, the Affibloom Lasso Migrator, which talks to Affibloom only through its public API
  (deviation 146).

## 1. Architecture

Four layers; dependencies point inward:

```
Interface  →  Application  →  Domain  ←  Infrastructure
```

| Layer | Folder | Holds | May call WordPress |
|---|---|---|---|
| Domain | `src/Domain/` | value objects, rules and repository interfaces (`Product`, `LinkKind`, `DisplayType`, `DataInventory`, …) | no |
| Application | `src/Application/` | use cases that combine domain objects through interfaces (`ProductService`, `LinkCheckService`, `TableRepair`, …) | no, except through injected interfaces |
| Infrastructure | `src/Infrastructure/` | the interfaces' WordPress implementations: options, post types, custom tables, HTTP, WP-Cron, files (`PostTypeProductRepository`, `TableClickStore`, `FileLog`, …) | yes |
| Interface | `src/Interface/` | what WordPress calls: admin screens (`Admin/`), REST controllers (`Rest/`), front-end filters and the shortcode (`Front/`), the block and editor integrations (`Block/`, `Editor/`), WP-CLI (`Cli/`), cron modules (`Cron/`), hooks (`Hooks/`) | yes |

**One composition root.** `src/Plugin.php` builds every object by hand (constructor injection, no container
library) and returns the list of modules. A module implements `Affibloom\Module` (one method, `register()`, which adds
its WordPress hooks). `affibloom.php` checks the requirements, loads Composer's autoloader and calls
`Plugin::boot()`; activation, deactivation and upgrades go through `Plugin::activate()`, `deactivate()` and
`Interface\UpgradeHooks`.

**Other folders.** `templates/` holds the PHP templates the screens render through `Interface\Template` (escaped on
output; no logic beyond loops and conditions). `assets/admin/` and `assets/front/` hold plain CSS and JavaScript (no
build step). `blocks/` holds the block's source; `npm run build` compiles it to `build/`. `data/` and `resources/`
hold PHP data files (affiliate networks' sub-ID parameters, F24; the network registry, F35). `languages/` holds the
`.pot` file.

**Admin design.** Every admin screen follows `docs/ui/design-system.md` (Meadow, ADR-8): tokens in `admin.css`, the
fonts bundled in `assets/admin/fonts/` (no other host asked). The reader's box keeps the site's design (ADR-6).

## 2. Architecture decisions

The ADRs are in [`docs/decisions/`](decisions/). In short:

| ADR | Decision |
|---|---|
| [ADR-1](decisions/ADR-001.md) | Products are a private post type, `affibloom_product`, with post meta prefixed `_affibloom_`. |
| [ADR-2](decisions/ADR-002.md) | Lasso's records are copied into new products by the migrator plugin; Lasso's data stays untouched for rollback. |
| [ADR-3](decisions/ADR-003.md) | Existing Lasso boxes are converted to Affibloom's block or shortcode by the migrator, with an exact revert; Affibloom renders nothing of Lasso's. |
| [ADR-4](decisions/ADR-004.md) | Background work runs on WP-Cron: a daily event plus chained single events, each guarded by a lock. |
| [ADR-5](decisions/ADR-005.md) | Clicks are stored as daily totals per product, post and button; no IP address or other personal data. |
| [ADR-6](decisions/ADR-006.md) | The box has its own stylesheet and `affibloom-` class names, tuned to the site's existing boxes; no Lasso CSS copied. |
| [ADR-7](decisions/ADR-007.md) | The admin is PHP-rendered screens with a small CSS kit and plain JavaScript; saves go over REST (deviation 143). |
| [ADR-8](decisions/ADR-008.md) | The admin uses Affibloom's own design system; Lasso's features, fields, labels and steps are kept. |

## 3. Extension points

### Hooks Affibloom fires

| Hook | Kind | Arguments | When |
|---|---|---|---|
| `affibloom_before_link_redirect` | action | `int $product_id`, `string $referer` | A cloaked link is about to redirect a reader (`Front\CloakedLinkRedirect`). |
| `affibloom_link_issue_detected` | action | `int $product_id`, `string $url`, `string $issue_type` | The link check files a new issue for a product (broken, out of stock, …). |
| `affibloom_link_index_indexed` | action | `int $post_id`, `array $rows` | The link index has written one post's links. |
| `affibloom_box_failed` | action | `array $attributes`, `int $post_id` | A shortcode or block could not draw its box (no such product, group or table). |
| `affibloom_slug_conflict` | action | `int $product_id`, `string $slug`, `string $post_type` | A product's slug is taken by a post or page, which keeps the address (logged under Duplicate Slug Changes). |
| `affibloom_display_advanced_enabled` | filter | `bool $customised` | Whether the box uses the full display engine; return `false` to force the plain box. |
| `affibloom_setup_wanted` | filter | `bool $wanted` | Whether the setup wizard may open by itself on a new site (deviation 153: a helper plugin returns `false` when it brings its own data). |
| `affibloom_ready` | action | `Affibloom\Extension\Api $api` | Once, on `plugins_loaded`: the extension API for helper plugins (Task LM1). Its methods are in [`extension-api.md`](extension-api.md). |
| `affibloom_event_tracking` | filter | `bool $allowed` | Whether a page prints the click script and GA's tag; return `false` to track nothing there (Lasso's `lasso_event_tracking`; Task GF5). |
| `affibloom_user_ip_address` | filter | `string $address` | The reader's address the click counter's repeat guard uses (`REMOTE_ADDR`); a site behind a proxy returns the forwarded one (Lasso's `lasso_get_user_ip_address`). |
| `affibloom_settings_saved` | action | `string $tab`, `array $settings` | A Settings tab (General, Amazon, Display, a setup step) was saved; every setting as stored (Lasso's `lasso_after_saved_settings`). |

Lasso's `lasso_after_insert_post` has no Affibloom copy: a product is a post of type `affibloom_product`, so
WordPress's own `save_post_affibloom_product` and `wp_after_insert_post` fire for it.

### Shortcode, block and editors

- **`[affibloom …]`** (`Front\AffibloomShortcode`): every display type. `id` or `slug` picks a product, `type`
  picks the display (`single` by default, `button`, `image`, `image-link`, `link`, `field`, `grid`, `list`,
  `gallery`, `table`; a group slug draws a grid, list or gallery), `theme` one of the eight themes (Cactus, Cutter,
  Flow, Geek, Lab, Llama, Money, Splash), and the per-box overrides (`title`, `title_type`, `description`, `badge`,
  `rating`, `button_text`, `button_type`, `field`, `compact`, `columns`, `limit`, `schema_review`,
  `schema_pros_cons`, …). The full list, with Lasso's names where they differ, is in `docs/features/F31/requirements.md`.
- **Block `affibloom/product-box`** (`Block\ProductBoxBlock`, source in `blocks/`): server-rendered through the same
  renderer as the shortcode, so a block and a shortcode with the same settings print the same box.
- **Classic editor:** an "Affibloom" button opens the same display modal (`Editor\ClassicButton`); the classic link
  dialog also finds products (`Admin\ClassicLinkSearch`).
- **Elementor:** an "Affibloom" widget (`Editor\ElementorDisplayWidget`, only when Elementor is active); its control
  `affibloom_shortcode` holds the shortcode.
- **Front-end filters:** `the_content` priority 20 sets products' link attributes and cloaked addresses
  (`Front\LinkAttributesFilter`, also on `widget_text`, `widget_block_content` and `tablepress_table_output`); 21 draws
  SiteStripe images and iframes as boxes (`Front\DisplayRenderFilter`); 999 adds the click script where a box was
  drawn (`Front\ClickScript`).

## 4. REST API

Namespace `/wp-json/affibloom/v1`. Every route with a capability also needs WordPress's REST nonce (cookie
authentication; `wp.apiFetch` adds it). **PRODUCTS** and **SETTINGS** are Affibloom's capabilities
`affibloom_products` and `affibloom_settings`, granted on each request to the role chosen under Settings → General →
Permissions (administrators by default; only administrators change Permissions). `edit_posts` is anyone who writes
posts, to place displays. Errors are `WP_Error` with an `affibloom_*` code and an HTTP status; lists answer
`{ data: { items, total, page, pages } }`.

The list below is generated from [`docs/reviews/entry-points.md`](reviews/entry-points.md) (Task 37A), which
`tests/E2E/security.spec.ts` compares with the running site's route table on every run, and grouped by area.

#### Products: the library, the product page, quick edit, bulk add, export

| Method | Route | Who may call |
|---|---|---|
| POST | `/products/{id}/preview` | PRODUCTS |
| POST | `/products/{id}/amazon/refresh` | PRODUCTS |
| GET | `/products` | edit_posts |
| POST, DELETE | `/products` | PRODUCTS |
| POST, PUT, PATCH, DELETE | `/products/{id}` | PRODUCTS |
| POST | `/products/{id}/repoint` | PRODUCTS |
| POST, GET | `/products/bulk` | PRODUCTS |
| POST, PUT, PATCH | `/products/{id}/variants` | PRODUCTS |
| GET | `/products/{id}/locations` | PRODUCTS |
| GET | `/products/export` | PRODUCTS (Task 38) |
| POST | `/products/groups` | PRODUCTS |
| POST, PUT, PATCH | `/products/{id}/fields` | PRODUCTS |
| DELETE | `/products/{id}/fields/{field}` | PRODUCTS |
| POST | `/products/{id}/alerts/dismiss` | PRODUCTS and edit_post on the product |

#### Dashboard list, saved views, issue CSV

| Method | Route | Who may call |
|---|---|---|
| GET | `/dashboard/products` | PRODUCTS |
| POST | `/dashboard/views` | PRODUCTS |
| DELETE | `/dashboard/views/{id}` | PRODUCTS |
| GET | `/dashboard/export` | PRODUCTS |

#### Fields

| Method | Route | Who may call |
|---|---|---|
| POST, PUT, PATCH, DELETE | `/fields/{id}` | PRODUCTS |
| GET | `/fields/list` | PRODUCTS |
| GET, POST | `/fields` | PRODUCTS |

#### Groups

| Method | Route | Who may call |
|---|---|---|
| GET | `/groups` | edit_posts |
| POST | `/groups` | PRODUCTS |
| POST, PUT, PATCH, DELETE | `/groups/{id}` | PRODUCTS |
| POST, DELETE | `/groups/{id}/products` | PRODUCTS |
| POST, PUT, PATCH | `/groups/{id}/order` | PRODUCTS |

#### Comparison tables: list and builder

| Method | Route | Who may call |
|---|---|---|
| GET | `/tables/{id}/preview` | PRODUCTS |
| POST | `/tables/{id}/clone` | PRODUCTS |
| GET | `/tables/{id}/locations` | PRODUCTS |
| PUT | `/tables/{id}/order` | PRODUCTS |
| POST | `/tables/{id}/groups/{group}/fields/{field}/move` | PRODUCTS |
| POST | `/tables/{id}/groups` | PRODUCTS |
| DELETE | `/tables/{id}/groups/{group}` | PRODUCTS |
| POST | `/tables/{id}/groups/{group}/fields` | PRODUCTS |
| DELETE | `/tables/{id}/groups/{group}/fields/{field}` | PRODUCTS |
| PUT | `/tables/{id}/values/{product}/{field}` | PRODUCTS |
| POST | `/tables/{id}/products` | PRODUCTS |
| PATCH, DELETE | `/tables/{id}/products/{product}` | PRODUCTS |
| PATCH, DELETE | `/tables/{id}` | PRODUCTS |
| POST | `/tables` | PRODUCTS |
| GET | `/tables` | PRODUCTS or edit_posts |

#### Locations: where a product is used

| Method | Route | Who may call |
|---|---|---|
| GET | `/locations/{id}/preview` | PRODUCTS |

#### Block editor: preview, schema, shortcode

| Method | Route | Who may call |
|---|---|---|
| POST | `/editor/preview` | edit_posts |
| GET | `/editor/schema` | edit_posts, and edit_post on the post when given |
| POST | `/editor/shortcode` | edit_posts |
| POST | `/editor/schema/rating` | PRODUCTS |

#### Settings → Display preview

| Method | Route | Who may call |
|---|---|---|
| POST | `/display-preview` | SETTINGS |

#### Settings

| Method | Route | Who may call |
|---|---|---|
| POST | `/settings/link-defaults/apply` | SETTINGS |
| POST | `/settings/tabs/{tab}` | SETTINGS |
| POST | `/settings/display` | SETTINGS |
| POST | `/settings/display/reset` | SETTINGS |
| POST | `/settings/tracking-id` | SETTINGS |

#### Button icons

| Method | Route | Who may call |
|---|---|---|
| POST | `/icons` | SETTINGS |
| DELETE | `/icons/{class}` | SETTINGS |

#### Amazon (Creators API)

| Method | Route | Who may call |
|---|---|---|
| POST | `/amazon/validate` | SETTINGS |
| GET, POST | `/amazon/bulk-sync` | PRODUCTS |
| GET | `/amazon/search` | PRODUCTS |
| POST | `/amazon/bulk-sync/dismiss` | PRODUCTS |

#### Amazon auto-monetization import

| Method | Route | Who may call |
|---|---|---|
| GET | `/amazon-import` | PRODUCTS |
| POST | `/amazon-import/dismiss` | PRODUCTS |

#### Notices

| Method | Route | Who may call |
|---|---|---|
| POST | `/notices/sitestripe/dismiss` | SETTINGS |

#### Link index

| Method | Route | Who may call |
|---|---|---|
| GET | `/link-index` | SETTINGS |
| POST | `/link-index/rebuild` | SETTINGS |

#### Automations (background jobs)

| Method | Route | Who may call |
|---|---|---|
| GET | `/jobs` | PRODUCTS |
| POST | `/jobs/run-due` | PRODUCTS |

#### Opportunities

| Method | Route | Who may call |
|---|---|---|
| GET | `/opportunities/links` | PRODUCTS |
| GET | `/opportunities/domains` | PRODUCTS |
| GET | `/opportunities/domains/{domain}/links` | PRODUCTS |
| GET | `/opportunities/content` | PRODUCTS |
| GET | `/opportunities/posts/{post}/links` | PRODUCTS |
| POST, DELETE | `/opportunities/locations/{row}/dismiss` | PRODUCTS, and edit_post on the host post |
| POST, PUT, PATCH, DELETE | `/opportunities/locations/{row}/assignment` | PRODUCTS, and edit_post on the host post |
| POST | `/opportunities/intros/{box}` | PRODUCTS |
| GET | `/opportunities/content/export` | PRODUCTS |

#### Click counter (public)

| Method | Route | Who may call |
|---|---|---|
| POST | `/clicks` | anyone (public) |

#### Setup wizard

| Method | Route | Who may call |
|---|---|---|
| POST | `/setup/done` | SETTINGS (Task ON7) |

#### Dashboard tour

| Method | Route | Who may call |
|---|---|---|
| POST | `/tour/done` | PRODUCTS (Task ON8) |


## 5. Admin screens, admin-post and AJAX

| Page slug | Screen | Capability |
|---|---|---|
| `affibloom` | Dashboard | PRODUCTS |
| `affibloom-opportunities` | Opportunities (Links, Domains, Content tabs) | PRODUCTS |
| `affibloom-tables`, `affibloom-table` | Tables list, the table builder | PRODUCTS |
| `affibloom-groups`, `affibloom-group` | Groups list, a group's page | PRODUCTS |
| `affibloom-fields`, `affibloom-field` | Fields list, a field's page | PRODUCTS |
| `affibloom-product` | A product's page (`&id=`) | PRODUCTS |
| `affibloom-settings` | Settings (`&tab=general`, `display`, `amazon`) | SETTINGS |
| `affibloom-logs`, `affibloom-db-status`, `affibloom-setup` | Logs, DB Status, the setup wizard (hidden pages) | SETTINGS |

Affibloom has **no `admin_post_*` and no `wp_ajax_*` handler of its own**: every save goes over the REST routes above
(deviation 143). The only AJAX action on its data is WordPress core's `wp_ajax_add-affibloom_group`, which core adds
for every taxonomy and Affibloom's screens never call.

## 6. WP-CLI

| Command | What it does |
|---|---|
| `wp affibloom amazon get <asin> [--country=<cc>] [--raw] [--all] [--resources=<paths>]` | Fetches one Amazon item through the Creators API and prints it (needs the credentials). |
| `wp affibloom check-links [--product=<id>] [--all] [--format=<format>]` | Runs the link check now, for one product or every covered product. |
| `wp affibloom links rebuild [--post=<id>] [--dry-run]` | Rebuilds the link index for every post, page and reusable block, or one post. |
| `wp affibloom links status` | Says how many links the index holds. |
| `wp affibloom links detect <url>` | Says which affiliate network a link belongs to and what it carries. |
| `wp affibloom upgrade` | Runs the pending schema and data steps now (they also run once on `admin_init`, under a lock). |

## 7. Cron

Every event is registered by its module's `HOOKS` constant (`Interface\Cron\CronRegistry`); deactivation clears them.

| Hook | Kind | Started by |
|---|---|---|
| `affibloom_daily` | daily | the link check pass, click retention (400 days) and log pruning |
| `affibloom_link_check_batch` | single, chained | the daily pass, `POST /jobs/run-due`; queued again by an administrator's page load or the daily event when a pass lost its chain |
| `affibloom_link_check_product` | single | a product's save, and its recheck an hour later |
| `affibloom_amazon_refresh` | hourly | the Amazon data refresh (only with "Update Amazon pricing daily" and credentials) |
| `affibloom_amazon_refresh_pass` | single, chained | the hourly event |
| `affibloom_amazon_bulk_sync` | single, chained | `POST /amazon/bulk-sync` |
| `affibloom_amazon_import` | every 5 minutes while there is work | Auto-Monetize Amazon Links |
| `affibloom_genius_cleanup` | single | the Geniuslink clean-up run |
| `affibloom_link_index_start`, `affibloom_link_index_batch` | single, chained | activation (the first build), `POST /link-index/rebuild` |
| `affibloom_link_index_daily` | daily | the index's daily catch-up |
| `affibloom_bulk_add` | single, chained | `POST /products/bulk` |
| `affibloom_auto_detect`, `affibloom_auto_detect_daily` | single / daily | a post's save; the daily detection pass |

WP-Cron runs on page visits. The header's Automations shows what is waiting. When WP-Cron does not answer, an open
Affibloom screen calls `POST /jobs/run-due`, which runs the due jobs itself, up to the "Maximum cronjobs" limit (the
WP-Cron fallback). Two job slots (`affibloom_job_slot_1`, `affibloom_job_slot_2`) keep at most two jobs running at
once.

## 8. Stored data

Everything Affibloom stores is listed in `Domain\Lifecycle\DataInventory` (Task 38); `DataInventoryCoverageTest` fails
when `src/` gains a stored name that is not in it. Uninstalling deletes nothing unless Settings → General → Uninstall
→ "Delete all Affibloom data when the plugin is deleted" is on; then `uninstall.php` removes everything below
(`Infrastructure\WordPress\DataEraser`).

### Options

| Option | Holds |
|---|---|
| `affibloom_settings` | Settings → General, Display's switches and Amazon, one array (`SettingsService::FIELDS` lists the keys per tab) |
| `affibloom_display` | Settings → Display: each display type's theme, colours and elements |
| `affibloom_fields` | the field library (Fields) |
| `affibloom_icons` | the button icon set |
| `affibloom_ai_texts` | AI Text displays' stored texts (shown only; nothing generates them) |
| `affibloom_db_version`, `affibloom_version`, `affibloom_activated_at` | schema version, plugin version, first activation |
| `affibloom_upgrade_lock` | the upgrade's lock |
| `affibloom_amazon_credentials` | the Creators API credentials (not autoloaded) |
| `affibloom_amazon_rate` | the record of Creators API calls, for the daily quota |
| `affibloom_amazon_refresh` | the scheduled Amazon refresh run |
| `affibloom_amazon_bulk_sync` | the bulk Amazon sync's queue and progress |
| `affibloom_amazon_import_batch`, `affibloom_amazon_import_lock` | the auto-monetization import's figures and lock |
| `affibloom_sitestripe_notice` | the "Migrating from Amazon SiteStripe?" notice's state |
| `affibloom_bulk_add` | the bulk add job's queue and progress |
| `affibloom_link_check`, `affibloom_link_check_lock` | the link check pass's queue and lock |
| `affibloom_link_index_state`, `affibloom_link_index_lock` | the link index build's state and lock |
| `affibloom_auto_detect_lock` | the detection pass's lock |
| `affibloom_job_slot_1`, `affibloom_job_slot_2` | the two background job slots |
| `affibloom_repair` | the table self-repair's state (DB Status) |
| `affibloom_setup` | the setup wizard: `pending` or `done` |
| `affibloom_tour_done` | `1` once someone ended the Dashboard tour |

### Transients

| Transient | Holds |
|---|---|
| `affibloom_cron_health` | whether WP-Cron answers (the Automations warning) |
| `affibloom_link_issue_counts` | the broken-links notice's count |
| `affibloom_amazon_token_<key>` | the Creators API access token |
| `affibloom_amazon_search_<key>` | an Amazon search's answer in Add A New Link |
| `affibloom_c_<key>` | a click's repeat guard (no personal data: a hash, kept briefly) |

### User meta

`affibloom_per_page` (the Dashboard's page size), `affibloom_saved_views` (saved views),
`affibloom_bulk_sync_dismissed` and `affibloom_amazon_import_dismissed` (dismissed notices).

### Post types, taxonomy and meta

- `affibloom_product`: the products (private). Status `affibloom_retired` marks a product replaced by another
  ("repoint"): it is kept, not trashed, so old boxes can follow it to its replacement.
- `affibloom_table`: the comparison tables.
- `affibloom_group`: the groups (a taxonomy on products; term meta `_affibloom_order`).
- Meta on products and tables starts with `_affibloom_` (for example `_affibloom_url`, `_affibloom_buttons`,
  `_affibloom_fields`, `_affibloom_table`). Affibloom writes no meta on other posts.

### Custom tables (without the site's prefix)

| Table | Holds |
|---|---|
| `affibloom_link_locations` | the link index: every link, shortcode and block in content, with its product |
| `affibloom_link_issues` | link health: broken and out-of-stock issues |
| `affibloom_clicks` | daily click totals per product, post and button |
| `affibloom_amazon_products` | Amazon item data from the Creators API |
| `affibloom_amazon_short_links` | `amzn.to` links resolved once to their full address |
| `affibloom_url_variants` | a product's URL variants |
| `affibloom_detected_links` | affiliate links found in content and the products made for them |

`Application\Schema\TableRepair` recreates a table that is missing or out of date when an SQL error names it
(DB Status shows the state).

### Files

Logs live in `wp-content/uploads/affibloom-logs/`, one file a day and type, each a `.log.php` that starts with a PHP
`exit` so the web server never shows it. Logging is off until Logs → "Enable logs" is switched on.

### Browser storage

`localStorage` keys `affibloom_table_locations`, `affibloom_jobs` and `affibloom_jobs_at`: per-viewer conveniences,
never needed for a correct page.

## 9. Tests and checks

| Level | Command (plugin folder) | Needs |
|---|---|---|
| Coding standards | `composer lint` (PHPCS, WordPress Coding Standards; `composer lint:fix` fixes spacing) | Composer dev tools |
| Static analysis | `composer analyse` (PHPStan level 6) | Composer dev tools |
| Unit | `composer test:unit` (PHPUnit 9.6 + Brain Monkey, no WordPress; about 1,200 tests in 10 s) | Composer dev tools |
| Integration | `npm run test:integration` (WordPress's test suite in wp-env) | Docker with `@wordpress/env`; **not on the development machine**, so `tests/Integration/` has not run there |
| End to end | `npx playwright test <spec> -g "<AC>"` against a running site (`playwright.config.ts`, `tests/E2E/`) | Node, Playwright's Chromium, a site with Affibloom active; specs change that site's data |
| Accessibility | `npx playwright test a11y` (axe, WCAG 2.2 AA) | as end to end |
| Performance | `npx playwright test performance` (queries, blocking time, weight) | as end to end |
| Security | `npx playwright test security` (every route's permission, the inventory's count) | as end to end |

On the development machine the site is Local's `volleyballvault.local`; WP-CLI runs through `bash tools/local-wp.sh`
(it finds Local's PHP and MySQL), and signed-in specs reuse a saved login with `-c .local/no-login.config.ts`
(`docs/environment.md`). Unit tests mirror `src/` under `tests/Unit/`; each acceptance criterion has its test in
`tests/E2E/acceptance/<feature>.spec.ts`.

## 10. Making a release

1. Run `composer lint`, `composer analyse` and `composer test:unit`; run the end-to-end specs of what changed.
2. Set the version in three places: the plugin header and `AFFIBLOOM_VERSION` in `affibloom.php`, and `version` in
   `package.json`. Add the release to `CHANGELOG.md`.
3. Update the translation template:
   `bash tools/local-wp.sh i18n make-pot . languages/affibloom.pot --exclude=tests,tools,node_modules,vendor,docs`
4. Build and package:

   ```bash
   rm -rf vendor && composer install --no-dev --optimize-autoloader --classmap-authoritative
   npm ci && npm run build
   npm run plugin-zip
   composer install   # the dev tools back
   ```

   `npm run plugin-zip` (`wp-scripts plugin-zip`) packs only what `files` in `package.json` lists (the plugin file,
   `uninstall.php`, `readme.txt`, `src`, `templates`, `assets`, `build`, `data`, `resources`, `composer.json`,
   `vendor`, `languages`; `wp-scripts` also adds `README.md` and `package.json`); `.distignore` says the same for other
   packers. Unzip it and check that `tests`, `tools`, `docs`, `node_modules`
   and `blocks` are absent and `uninstall.php` is present.
5. Install the zip on an empty site (a fresh Local site or staging): it activates, the setup wizard opens, a product
   and a box can be added.
6. Commit, tag `v<version>`, push the tag, and attach the zip to a GitHub release (marked pre-release for a release
   candidate).
