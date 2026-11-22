# Compatibility review (Task 37D, roadmap 8.4)

Reviewed 2026-11-22 on the Local copy (WordPress 7.1.4, Local's PHP 8.3.37, nginx, MySQL 8.4) with the site's
plugins as Local has them (`wp plugin list`). Test: `tests/E2E/compat.spec.ts` (3 tests, green). This document feeds
Task 41's cutover runbook.

## Findings

| # | Finding | Verdict |
|---|---|---|
| C1 | **LiteSpeed Cache's "Load JS Deferred: Delayed"** (production's setting, `optm-js_defer` = 2, copied to Local) held `clicks.js` and its settings line until the reader's first interaction, so **the first click on a box, often the reader's first act on the page, was not counted** (spec: the click's request never left). | **Fixed in Affibloom**, so the runbook needs no LiteSpeed exclusion: `ClickScript` marks its script and its settings line `data-no-defer="1"` and `data-no-optimize="1"` (LiteSpeed's own bypass attributes, `optimize.cls.php`) and `data-cfasync="false"` (Cloudflare Rocket Loader), through WordPress's `wp_script_attributes` / `wp_inline_script_attributes`. The script is 2 KB and needs nothing else. Tests: `ClickScriptTest`; spec "with scripts delayed … the first click is counted". |
| C2 | Page caching cannot run on Local: LiteSpeed caches pages only behind LiteSpeed's web server (or QUIC.cloud); Local serves with nginx. | **Not run here.** Boxes are drawn into the page's HTML (no per-visitor content; the click script reads the product from the link), so a cached page draws the same box and counts clicks through REST, which LiteSpeed does not cache. To see on staging (smoke test, below). |
| C3 | The box's JSON-LD and Rank Math's. | **No duplicate:** on a post with one box with review schema, Rank Math prints its graph (Organization, WebSite, WebPage, BlogPosting, BreadcrumbList) and Affibloom one `Product` with its `Review` and `Offer`, as Lasso does. Rank Math's sitemap is switched off on Local (`/sitemap_index.xml` is 404); Affibloom's post types are not public (`public` false, no rewrite), so no sitemap can list them. |
| C4 | Lasso and Affibloom active together (the cutover window). | **No fatal, no duplicate:** a post with a Lasso box and an Affibloom box draws each once (spec). `[lasso]` is answered by Lasso only: Affibloom has no `[lasso]` shortcode (deviation 146); the migrator converts boxes (Phase M). Both load their front assets on every page (both draw boxes in the sidebar). |
| C5 | GenerateBlocks: the guides are GenerateBlocks layouts; the performance pages (`tests/E2E/fixtures/perf-guides.php`, the real guide with Affibloom's boxes) draw every box inside them, and the box's own layout (`box-layout.css`) is scoped to `.affibloom-box`. | Pass (spec, performance and accessibility runs on those pages). |
| C6 | Mediavine: the Control Panel plugin is inactive on Local and its ads come from Mediavine's script (blocked in tests). | **Not run here**; staging check: an ad never inside `.affibloom-box`. The box is one block-level element, and Mediavine inserts between top-level content blocks. |
| C7 | Admin: other plugins' notices on Affibloom's screens (ON2). | Pass: hidden on Affibloom's screens only (`ForeignNotices`, ON2's acceptance tests); Affibloom's admin assets load only on its screens and are not removed by the active plugins (all admin runs of 37A-37C). |

## PHP

| PHP | Where | Result |
|---|---|---|
| 8.3.37 | Local's site (production runs 8.3) | All E2E runs of 37A-37D green; `composer analyse` (PHPStan, `phpVersion` 80300) no errors. |
| 8.5.1 | the machine's CLI | `composer lint` clean; the unit suite (1,164 tests) green with `error_reporting=-1`; all 669 PHP files of `src/`, `templates/` and `affibloom.php` compile with no deprecation (8.4/8.5 deprecations such as implicit nullable parameters show at compile time). |
| 8.3.37 | the empty test site, the release zip | The smoke test below: green. Staging's run is the cutover's. |
| 8.2 | — | Below the minimum (`Requires PHP: 8.3`, decision F7): WordPress refuses to activate the plugin. |

## WordPress

Local and staging run 7.1.4, which is also the minimum (decision F7), so the minimum is covered by every run here.
The integration suite needs wp-env (Docker), which this machine lacks; these stay unrun until a machine with wp-env:
`AmazonImportCronTest`, `AssignmentTest`, `AutoDetectHooksTest`, `BulkAddTest`, `ContentExportTest`, `DismissTest`,
`ForeignNoticesTest`, `LifecycleTest`, `LinkIssueTableTest`, `PluginLoadsTest`, `ProductBoxBlockTest`,
`ProductListTest`, `ProductsCreateTest`, `UpgraderTest` (`tests/Integration/`). Plugin Check's required-version and
deprecated-function checks (Task 37A) found nothing.

## The smoke test on a fresh site (plan step 5)

**Run 2026-11-22 with Task 39's install check**, on the user's empty Local site `volleyballvault-testing.local`
(WordPress 7.1.5, PHP 8.3.37, no other plugin), with the release zip `affibloom.zip` (1.0.0-rc.1):

| Check | Result |
|---|---|
| Install and activate the zip (`wp plugin install … --activate`) | Activated; `affibloom_version` 1.0.0-rc.1, schema 11, all seven tables, the four daily or hourly events, the wizard due (`affibloom_setup` pending). |
| The wizard on the first Affibloom page | Opened by itself on `page=affibloom-groups`; Theme, Customize, Amazon, Analytics; **Go to Dashboard** ended it. |
| The Dashboard and the tour | The Dashboard opened with the tour; Skip ended it. |
| Add a product | Five through `POST /products` (Add A New Link's route) and named through `PATCH /products/{id}`: 201 and 200. |
| Place a box; a guide draws it | A post with the block and `[affibloom id]`: both boxes drawn for a visitor; the cloaked link answers 302 to the destination with `X-Robots-Tag: noindex, nofollow`. |
| A click is counted | A visitor's click on the box title: `POST /clicks` 204, one row (product 5, post 10, `title`). With headless Chromium's own user agent the click is dropped as a bot, as designed. |
| Logs and DB Status open | Both open; DB Status lists the seven tables. |
| Plugin Check on the zip | No new finding; the release's run is triaged in `security.md`. |

PHP 8.3 is covered by this run and by every run on Local. **Staging's run** (Lasso active there, LiteSpeed's page
cache, C2) is the cutover's (Tasks 40-41).
