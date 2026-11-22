# Entry points (Task 37A inventory)

Generated 2026-11-22 from the running Local site, not from the analyses: the REST server's route table
(`tests/E2E/fixtures/entry-points.php`, which `tests/E2E/security.spec.ts` reads on every run), a grep of `src/` and
`affibloom.php` for hooks, and `wp cron event list`. The verdicts are in [`security.md`](security.md). When a route is
added or removed, `security.spec.ts`'s first test fails until this file and its count are updated.

**Capabilities** (`Interface\Admin\Capabilities`): `PRODUCTS` and `SETTINGS` are `manage_options` (administrators;
TL6 makes them the Permissions setting); `edit_posts` is `INSERT_BOXES`, anyone who writes posts, to place displays
(F4 D8). Every route with a capability also needs WordPress's REST nonce (cookie authentication).

## REST routes: `/wp-json/affibloom/v1` (102 route and method pairs)

The index `/affibloom/v1` itself is WordPress's, public, and lists the routes. A method listed by two handlers of one
route is answered by the first (WordPress's rule), so it appears once.

| Method | Route | Who may call |
|---|---|---|
| `POST` | `/products/(?P<id>\d+)/preview` | PRODUCTS |
| `POST` | `/clicks` | anyone (public) |
| `GET` | `/locations/(?P<id>\d+)/preview` | PRODUCTS |
| `POST` | `/display-preview` | SETTINGS |
| `POST` | `/editor/preview` | edit_posts |
| `GET` | `/editor/schema` | edit_posts, and edit_post on the post when given |
| `POST` | `/editor/shortcode` | edit_posts |
| `POST` | `/editor/schema/rating` | PRODUCTS |
| `POST` | `/amazon/validate` | SETTINGS |
| `POST` | `/products/(?P<id>\d+)/amazon/refresh` | PRODUCTS |
| `GET` | `/amazon/bulk-sync` | PRODUCTS |
| `POST` | `/amazon/bulk-sync` | PRODUCTS |
| `POST` | `/notices/sitestripe/dismiss` | SETTINGS |
| `GET` | `/amazon/search` | PRODUCTS |
| `POST` | `/amazon/bulk-sync/dismiss` | PRODUCTS |
| `POST` | `/settings/link-defaults/apply` | SETTINGS |
| `POST` | `/settings/tabs/(?P<tab>[a-z_]+)` | SETTINGS |
| `POST` | `/settings/display` | SETTINGS |
| `POST` | `/settings/display/reset` | SETTINGS |
| `POST` | `/settings/tracking-id` | SETTINGS |
| `GET` | `/products` | edit_posts |
| `POST` | `/products` | PRODUCTS |
| `DELETE` | `/products` | PRODUCTS |
| `POST` | `/products/(?P<id>\d+)` | PRODUCTS |
| `PUT` | `/products/(?P<id>\d+)` | PRODUCTS |
| `PATCH` | `/products/(?P<id>\d+)` | PRODUCTS |
| `DELETE` | `/products/(?P<id>\d+)` | PRODUCTS |
| `POST` | `/products/(?P<id>\d+)/repoint` | PRODUCTS |
| `POST` | `/products/bulk` | PRODUCTS |
| `GET` | `/products/bulk` | PRODUCTS |
| `POST` | `/products/(?P<id>\d+)/variants` | PRODUCTS |
| `PUT` | `/products/(?P<id>\d+)/variants` | PRODUCTS |
| `PATCH` | `/products/(?P<id>\d+)/variants` | PRODUCTS |
| `GET` | `/opportunities/links` | PRODUCTS |
| `GET` | `/opportunities/domains` | PRODUCTS |
| `GET` | `/opportunities/domains/(?P<domain>[a-z0-9.-]+)/links` | PRODUCTS |
| `GET` | `/opportunities/content` | PRODUCTS |
| `GET` | `/opportunities/posts/(?P<post>\d+)/links` | PRODUCTS |
| `POST` | `/opportunities/locations/(?P<row>\d+)/dismiss` | PRODUCTS, and edit_post on the host post |
| `DELETE` | `/opportunities/locations/(?P<row>\d+)/dismiss` | PRODUCTS, and edit_post on the host post |
| `POST` | `/opportunities/locations/(?P<row>\d+)/assignment` | PRODUCTS, and edit_post on the host post |
| `PUT` | `/opportunities/locations/(?P<row>\d+)/assignment` | PRODUCTS, and edit_post on the host post |
| `PATCH` | `/opportunities/locations/(?P<row>\d+)/assignment` | PRODUCTS, and edit_post on the host post |
| `DELETE` | `/opportunities/locations/(?P<row>\d+)/assignment` | PRODUCTS, and edit_post on the host post |
| `POST` | `/opportunities/intros/(?P<box>[a-z]+)` | PRODUCTS |
| `GET` | `/amazon-import` | PRODUCTS |
| `POST` | `/amazon-import/dismiss` | PRODUCTS |
| `GET` | `/tables/(?P<id>\d+)/preview` | PRODUCTS |
| `POST` | `/tables/(?P<id>\d+)/clone` | PRODUCTS |
| `GET` | `/tables/(?P<id>\d+)/locations` | PRODUCTS |
| `PUT` | `/tables/(?P<id>\d+)/order` | PRODUCTS |
| `POST` | `/tables/(?P<id>\d+)/groups/(?P<group>[A-Za-z0-9_-]+)/fields/(?P<field>\d+)/move` | PRODUCTS |
| `POST` | `/tables/(?P<id>\d+)/groups` | PRODUCTS |
| `DELETE` | `/tables/(?P<id>\d+)/groups/(?P<group>[A-Za-z0-9_-]+)` | PRODUCTS |
| `POST` | `/tables/(?P<id>\d+)/groups/(?P<group>[A-Za-z0-9_-]+)/fields` | PRODUCTS |
| `DELETE` | `/tables/(?P<id>\d+)/groups/(?P<group>[A-Za-z0-9_-]+)/fields/(?P<field>\d+)` | PRODUCTS |
| `PUT` | `/tables/(?P<id>\d+)/values/(?P<product>\d+)/(?P<field>\d+)` | PRODUCTS |
| `POST` | `/tables/(?P<id>\d+)/products` | PRODUCTS |
| `PATCH` | `/tables/(?P<id>\d+)/products/(?P<product>\d+)` | PRODUCTS |
| `DELETE` | `/tables/(?P<id>\d+)/products/(?P<product>\d+)` | PRODUCTS |
| `PATCH` | `/tables/(?P<id>\d+)` | PRODUCTS |
| `DELETE` | `/tables/(?P<id>\d+)` | PRODUCTS |
| `POST` | `/tables` | PRODUCTS |
| `GET` | `/tables` | PRODUCTS or edit_posts |
| `GET` | `/opportunities/content/export` | PRODUCTS |
| `GET` | `/products/(?P<id>\d+)/locations` | PRODUCTS |
| `GET` | `/dashboard/products` | PRODUCTS |
| `POST` | `/dashboard/views` | PRODUCTS |
| `DELETE` | `/dashboard/views/(?P<id>[A-Za-z0-9_-]{1,64})` | PRODUCTS |
| `GET` | `/dashboard/export` | PRODUCTS |
| `GET` | `/products/export` | PRODUCTS (Task 38) |
| `POST` | `/setup/done` | SETTINGS (Task ON7) |
| `POST` | `/icons` | SETTINGS |
| `DELETE` | `/icons/(?P<class>[a-z0-9_-]+)` | SETTINGS |
| `POST` | `/fields/(?P<id>\d+)` | PRODUCTS |
| `PUT` | `/fields/(?P<id>\d+)` | PRODUCTS |
| `PATCH` | `/fields/(?P<id>\d+)` | PRODUCTS |
| `DELETE` | `/fields/(?P<id>\d+)` | PRODUCTS |
| `GET` | `/fields/list` | PRODUCTS |
| `GET` | `/fields` | PRODUCTS |
| `POST` | `/fields` | PRODUCTS |
| `GET` | `/groups` | edit_posts |
| `POST` | `/groups` | PRODUCTS |
| `POST` | `/groups/(?P<id>\d+)` | PRODUCTS |
| `PUT` | `/groups/(?P<id>\d+)` | PRODUCTS |
| `PATCH` | `/groups/(?P<id>\d+)` | PRODUCTS |
| `DELETE` | `/groups/(?P<id>\d+)` | PRODUCTS |
| `POST` | `/groups/(?P<id>\d+)/products` | PRODUCTS |
| `DELETE` | `/groups/(?P<id>\d+)/products` | PRODUCTS |
| `POST` | `/groups/(?P<id>\d+)/order` | PRODUCTS |
| `PUT` | `/groups/(?P<id>\d+)/order` | PRODUCTS |
| `PATCH` | `/groups/(?P<id>\d+)/order` | PRODUCTS |
| `POST` | `/products/groups` | PRODUCTS |
| `GET` | `/link-index` | SETTINGS |
| `POST` | `/link-index/rebuild` | SETTINGS |
| `GET` | `/jobs` | PRODUCTS |
| `POST` | `/jobs/run-due` | PRODUCTS |
| `POST` | `/products/(?P<id>\d+)/fields` | PRODUCTS |
| `PUT` | `/products/(?P<id>\d+)/fields` | PRODUCTS |
| `PATCH` | `/products/(?P<id>\d+)/fields` | PRODUCTS |
| `DELETE` | `/products/(?P<id>\d+)/fields/(?P<field>\d+)` | PRODUCTS |
| `POST` | `/products/(?P<id>\d+)/alerts/dismiss` | PRODUCTS and edit_post on the product |
## admin-post.php and admin-ajax.php

| Hook | Whose | Who may call |
|---|---|---|
| `wp_ajax_add-affibloom_group` | WordPress core (added for every taxonomy; Affibloom's screens never call it) | the taxonomy's `edit_terms`, `manage_options` (`GroupTaxonomy`), and core's nonce |

Affibloom has **no `admin_post_*` and no `wp_ajax_*` handler of its own**: every save goes over REST (deviation 143).
The plan's list named `affibloom_save_settings`, `affibloom_tracking_id`, `affibloom_add_icon`, `affibloom_remove_icon`
and `affibloom_export`; they are `POST /settings/tabs/{tab}`, `POST /settings/tracking-id`, `POST /icons`,
`DELETE /icons/{class}` and `GET /dashboard/export` above. Task 38 adds the product export.

## Admin screens (all `manage_options`, through `add_menu_page` / `add_submenu_page`)

`affibloom` (Dashboard), `affibloom-opportunities` (Links, Domains, Content tabs), `affibloom-tables`,
`affibloom-groups`, `affibloom-fields`, `affibloom-settings` (General, Display, Amazon tabs), and the hidden detail
pages `affibloom-product`, `affibloom-group`, `affibloom-field`, `affibloom-table` (each with a `guard()` for a missing
ID), `affibloom-logs`, `affibloom-db-status` and `affibloom-setup` (Settings capability, Tasks TL3, TL4, ON6).

## Public entry points (readers, no sign-in)

| Entry point | Code | Writes |
|---|---|---|
| `POST /affibloom/v1/clicks` | `Rest\ClicksController`, `Application\Clicks\ClickTracker` | one count row per valid click |
| The cloaked link (`template_redirect`, priority 5) | `Front\CloakedLinkRedirect` | nothing (fires `affibloom_before_link_redirect`) |
| The `[affibloom]` shortcode (`init`) | `Front\AffibloomShortcode` | nothing |
| The block `affibloom/product-box` (server render) | `Block\ProductBoxBlock` | nothing |
| `the_content` 20: link attributes and cloaked addresses | `Front\LinkAttributesFilter` (also `widget_text`, `widget_block_content`, `tablepress_table_output`) | nothing |
| `the_content` 21: SiteStripe images and iframes | `Front\DisplayRenderFilter` | nothing |
| `the_content` 999: the click script, where a box is drawn | `Front\ClickScript` | nothing |
| The Elementor widget "Affibloom" (only with Elementor active) | `Editor\ElementorDisplayWidget` | nothing |
| `redirect_canonical`, Rank Math's redirect guard | `Front\CloakedLinkRedirect` | nothing |

There is no `[lasso]` shortcode in Affibloom (deviation 146; the migrator converts boxes, Phase M).

## Cron hooks (`CronRegistry`, from each cron module's `HOOKS`)

| Hook | Kind | Started by |
|---|---|---|
| `affibloom_daily` | daily | `LinkCheckCron::schedule_daily` (on `init` when missing) |
| `affibloom_link_check_batch` | single, chained | the daily pass, `POST /jobs/run-due` |
| `affibloom_link_check_product` | single | a product's save, and its recheck an hour later (`CONFIRM_AFTER`) |
| `affibloom_amazon_refresh` | hourly | `AmazonRefreshCron::schedule` |
| `affibloom_amazon_refresh_pass` | single, chained | the hourly event |
| `affibloom_amazon_bulk_sync` | single, chained | `POST /amazon/bulk-sync` |
| `affibloom_amazon_import` | every 5 min while there is work | `AmazonImportSchedule` (Auto-Monetization) |
| `affibloom_genius_cleanup` | single | `GeniusCleanupService` (C1P, the Geniuslink clean-up run) |
| `affibloom_link_index_start`, `affibloom_link_index_batch` | single, chained | activation (the first rebuild), `POST /link-index/rebuild` |
| `affibloom_link_index_daily` | daily | `LinkIndexCron::schedule_daily` |
| `affibloom_bulk_add` | single, chained | `POST /products/bulk` |
| `affibloom_auto_detect`, `affibloom_auto_detect_daily` | single / daily | a post's save, `AutoDetectCron::schedule_daily` |

Present on Local today (`wp cron event list`): `affibloom_daily`, `affibloom_amazon_refresh`,
`affibloom_link_index_daily`, `affibloom_auto_detect_daily`; the chained events exist only while they have work.
`affibloom_import_batch` from the plan's list does not exist (no imports, deviation 146).

## WP-CLI (only from a shell on the server)

`wp affibloom amazon get`, `wp affibloom check-links`, `wp affibloom links`, `wp affibloom upgrade`.

## Hooks on every request (`init`, `admin_init`)

`init`: the post types, the group taxonomy, the block, the shortcode, the text domain, and the four "schedule if
missing" calls above (they add an event, no work). `admin_init` (WordPress also runs it for a visitor's
`admin-ajax.php` and `admin-post.php`): `UpgradeHooks::upgrade` (skips AJAX and cron; runs a pending schema upgrade
once, under a lock) and `LinkIndexCron::catch_up` (since finding S4: only for a user with `PRODUCTS`).
