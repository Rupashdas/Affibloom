# F11 Link health: analysis

Status: rewritten 2026-11-14 from a sweep of Lasso 369 (link health, the Alert column, and every Lasso cron event and
background process). Gate 2 of 2026-11-12 (Task 6) kept where the source agrees; corrections are in
`requirements.md` (marked "corrected 2026-11-14"). Gate 4 for Tasks 30A-32E: waiting. These tasks replace Phase 6's
Tasks 30-32.

**Tasks:** 30A (the daily check pass and its storage), 30B (the check on product save), 30C (Amazon products through
the product API, dormant until C1), 31A (the Alert column in every state, and its sort), 31B (the Alert filter tabs,
counters and the Broken links tile), 31C (the product page's issue notice and its dismissal), 31D (Export CSV of issue
links), 32A (`wp affibloom check-links`), 32B (the broken-links admin notice), 32C (Settings → General: Performance and
Cron fields), 32D (the WP-Cron fallback from admin pages), 32E (the background activity indicator). Tasks: Phase 6 file (TL1-TL2 moved to Phase 2).

## Depends on

- ADR-004 (WP-Cron: a daily event plus chained single events, guarded by a lock; still "Proposed": approve with gate
  4), ADR-001 (products are posts), ADR-007 (PHP-rendered screens, plain scripts), ADR-008 (Aurora look, Lasso's
  arrangement).
- F1: products, their primary destination (`url`) and `ProductService::create()/update()` (30B follows the save).
- F19: the Dashboard list, its list route (13D), the filters panel and saved views (13E), the row partial (13C).
- F17: "Days before out of stock alerts" (`days_before_out_of_stock_alerts`, 0-30) and the Amazon "locations only"
  setting (`amazon_pricing_locations_only`); F11 reads both.
- C1 (Amazon Creators API credentials): 30C's Amazon branch ships dormant behind it.
- F21 (content link index): the "only products used in content" rule (R5) needs it; until F21 exists the pass checks
  every published non-Amazon product (D5).
- F22 (opportunities): fills the lightbulb count in the Alert cell and adds its tab (31A/31B leave the slots).
- Existing code reused: `Infrastructure\WordPress\WpCronBackgroundJob` (single events + `spawn_cron()`),
  `Domain\Amazon\AmazonUrl` (is this an Amazon product, its ASIN), `Interface\Admin\Capabilities`,
  `Interface\Admin\Notice`, `Rest\DashboardController`, `Admin\DashboardUrl`, `Domain\Dashboard\SavedViews`.

## Cron and background-job inventory (all of Lasso 369)

Every scheduled event and background process in Lasso, so that no job is unowned. Sources:
`classes/class-lasso-cron.php` 123-199 (`CRONS`, `CRONS_LEAN`, `CRON_INTERVAL_VALUES`), 260-323 (handlers: registered
only while `lasso_license_status` = 1 and, for most, only after the first full scan, `lasso_force_to_run_new_scan` =
'false'), 365-453 (which events are active), 4926-6987 (handlers); `classes/background_processing/*.php`;
`classes/class-verbiage.php` 14-33; `libs/background_processing/lasso-wp-background-process.php`.
"Cloud" = `LASSO_LINK` = `https://lasso.link` (`admin/lasso-constant.php` 786-787). "Content" = rewrites
`post_content` or post meta of posts.

### A. Scheduled events (WP-Cron)

| # | Hook | Schedule | What it does | Tables / data | Cloud | Content | Affibloom owner |
|---|---|---|---|---|---|---|---|
| E1 | `lasso_cron_check_issue` | `lasso_five_minutes`; one pass a day (`lasso_check_issue_date`) | queues the next 100 products for the link check (`Lasso_Process_Check_Issue`) | reads posts, `lasso_url_details`, `lasso_link_locations(_map)`; writes `lasso_url_issues`; deletes orphan `lasso_url_issues`/`lasso_url_details` rows | yes: non-200 answers get a second opinion from `lasso.link/link/status/` (class-helper.php 250-256, 316); Amazon/extend products via product API or `lasso.link` | no | **F11** (30A, 30C) |
| E2 | `lasso_cron_update_amazon` | hourly; only with `amazon_update_pricing_hourly` | `Lasso_Process_Update_Amazon`: 100-500 Amazon products an hour (`ceil(due/20)`): price, image, stock; files 404/000/200 issues (`update_amazon_pricing` 5105-5153) | `lasso_amazon_products`, `lasso_url_details`, `lasso_url_issues` | Creators API, else `lasso.link` (BLS) | no | **C1** (refresh); its issue filing calls F11's recorder (30C) |
| E3 | `lasso_cron_add_amazon` | `lasso_five_minutes`; only with `auto_monetize_amazon` | `Lasso_Process_Add_Amazon::import()`: products for Amazon links in content, link locations pointed at them | `lasso_link_locations`, posts, `lasso_amazon_products` | product API / `lasso.link` | link-location rows; `wp_update_post` (add-amazon 171-239) | **F35** |
| E4 | `lasso_hourly_event_hook` | hourly | `hourly_process` → `uncloak_amazon_link` (only with `auto_monetize_amazon`): resolves Amazon short links in content and monetizes them | `lasso_link_locations`, `lasso_amazon_shortened_url`, meta `lasso_custom_redirect` | `lasso.link/link/final-url/` (class-helper.php 489) | **yes**: `update_link_in_post` (5071) | **F35** (render time, no rewrite) |
| E5 | `lasso_auto_monetize` | daily; only with `auto_monetize_affiliates` | `Lasso_Process_Auto_Monetize`: products for the URLs in the auto-monetize table ("Does not change the original URL on post content", 62) | `lasso_auto_monetize`, posts | final-URL lookups | no | **F35** |
| E6 | `lasso_scan_all_links` | `lasso_ten_minutes` (15 min in `CRON_INTERVAL_VALUES`) | `Lasso_Process_Build_Link::link_database_limit()`: scans 100 changed posts, writes the link index, applies Lasso's link settings to content | `lasso_link_locations(_map)`, `lasso_metadata`, `lasso_post_content_history`, `lasso_content` | final-URL lookups (cron 2747) | **yes**: `update_post_content` (1573, 5305-5371), ACF (6594), WPRM / Beaver Builder meta | **F21** (index only) |
| E7 | `lasso_force_scan_all_links` | `lasso_fifteen_minutes` while `lasso_force_to_run_new_scan` = 'true' (then only E7, E13, E19 run) | `Lasso_Process_Force_Scan_All_Posts::force_to_run_new_scan(500)`: first full scan | as E6 | as E6 | **yes** | **F21** |
| E8 | `lasso_cron_import_all` | `lasso_five_minutes` while `lasso_import_all_enable` = 1 | `Lasso_Process_Import_All::import()` | posts, `lasso_revert`, link locations | no | **yes** | **F33** |
| E9 | `lasso_cron_revert_all` | `lasso_five_minutes` while `lasso_revert_all_enable` = 1 | `Lasso_Process_Revert_All::revert()` | posts, `lasso_revert` | no | **yes** | **F33** |
| E10 | `lasso_daily_clean_link_locations_hook` | daily | `Lasso_DB::clean_link_locations()` (rows of unsupported post types, db 5320-5325); `sync_existing_link_locations()`; log files > 14 days; `lasso_post_content_history` > 14 days (6606-6617) | `lasso_link_locations`, `lasso_post_content_history`, `logs/*.log` | yes (`lasso.link/data-sync`) for the cloud part | no | **F21** (index), **F34** (14-day retention); logs: none (no log files); cloud part: never |
| E11 | `lasso_check_completed_process` | daily | `error_log` when the scan's last completion is > 3 days old with work queued (6906-6927) | options | no | no | **F21** |
| E12 | `lasso_remove_duplicate_processes` | hourly | deletes duplicate `…_batch_…` queue options; `check_empty_process()` | `wp_options` | no | no | **F11** (30A's runner makes duplicates impossible; no job) |
| E13 | `lasso_cron_fix_missed_schedule_posts` | hourly (also lean, force-scan) | `Lasso_DB::publish_missed_schedule_posts()`: `future` → `publish` when the time has passed (6980-6982) | `wp_posts.post_status` | no | status only | **unowned: requirements N5** |
| E14 | `lasso_cron_remove_genius_formatting` | daily, unless `keep_genius_formatting` | `Lasso_Process_Remove_Genius_Formatting`: re-saves geni.us products 10 at a time | posts, `lasso_url_details` | final-URL lookups | indirect (a save queues `Scan_Link`) | **F17 / C1**; the rewrite: none (F1 D13) |
| E15 | `lasso_daily_data_sync_content` | daily | content stats to Lasso | `lasso_content` | yes (`/data-sync`) | no | **cloud: never** |
| E16 | `lasso_hourly_data_sync_link_issues` | hourly | `lasso_url_issues` to Lasso | `lasso_url_issues` | yes (`/data-sync`) | no | **cloud: never** |
| E17 | `lasso_daily_data_sync_link_locations` | daily | link locations to Lasso | `lasso_link_locations` | yes (`PUT /data-sync/data`) | no | **cloud: never** |
| E18 | `lasso_daily_data_sync_lasso_links` | daily | products to Lasso | posts | yes (`/data-sync`) | no | **cloud: never** |
| E19 | `lasso_cron_get_js_domain` | daily (also lean, force-scan) | click-script domain into `js_domain`, `full_js_domain` | options | yes (`/api/js-domain`) | no | **cloud: never** (F12 serves its own script) |
| E20 | `lasso_daily_data_sync_affiliate_programs` | daily | Lasso's affiliate-program catalogue | `lasso_affiliate_programs` | yes (`/data-sync/data`) | no | **cloud: never** (F22 to say where program data comes from) |
| E21 | `lasso_daily_data_sync_plugins` | daily | the site's plugin list | none | yes (`/server/update-plugins`) | no | **cloud: never** |
| E22 | `lasso_update_license_status` | daily (lean) | `Lasso_License::check_user_license()` | options | yes | no | **cloud: never** |
| E23 | `lasso_sync_metadata` | daily | settings and WP-Cron health to Lasso | none | yes | no | **cloud: never** |
| E24 | `lasso_sync_and_encrypt_amazon_api` | daily (lean), only with verified Creators credentials | sends the Amazon credentials to Lasso, encrypted (6483-6521) | settings | yes (`/encrypt/amazon`) | no | **cloud: never** |
| E25 | `lasso_cron_validate_amazon_affiliate_url` | daily, only with `auto_upgrade_eligible_links` | `Lasso_Process_Update_Aff_Plus` (Marketplace) | `lasso_url_details` | yes (`/affiliate-plus/wp/validate-links`) | no | **cloud: never** |
| E26 | `lasso_cron_sync_link_from_cloud` | hourly, only with `auto_upgrade_eligible_links` | pulls `/lasso-links/changes/v2` (50 × 10 pages) into `lasso_link_syncing`; `Lasso_Process_Sync_Link_From_Cloud_To_WP` updates products | `lasso_link_syncing`, posts, `lasso_amazon_products` | yes | no | **cloud: never** |
| E27 | `lasso_cron_get_snippet` | daily | downloads `connect-snippet.min.js` | plugin file | yes (`/api/snippet/performance`) | no | **cloud: never** |
| E28 | `lasso_cron_sync_clicks` | daily | replaces all click counts with Lasso's (zeroes first) | `lasso_url_details.clicks` | yes (`/clicks`) | no | **cloud: never** (F12 counts locally) |
| E29 | `wp_<action>_cron` (one per process class) | `wp_<action>_cron_interval` (5 min) while queued | the library's healthcheck (`handle_cron_healthcheck`); set on `dispatch()`, cleared on `complete()` | `wp_options` batches, `…_process_lock` | no | as the process | owner of each process (table B) |
| E30 | `lasso_cron_link_update` | never scheduled | constant only (`lasso-constant.php` 80) | — | — | — | none (dead) |
| E31 | leftovers on this copy: `lasso_daily_data_sync_authors`, `lasso_monthly_data_sync_authors`, `lasso_monthly_data_sync_content`, `lasso_monthly_data_sync_link_locations`, `lasso_monthly_data_sync_lasso_links` | daily / `lasso_monthly` | no handler in 369 (older cloud syncs) | — | — | — | **F33** (cutover clean-up) |

Non-cron hooks that start background work: `save_post` (1000) → `Lasso_Process_Scan_Links_Post_Save` (**F21**);
`mv_create_card_post_publish` → same (**F21**); `deleted_post` → deletes the post's link locations (**F21**);
`Setting_Enum::HOOK_AFTER_SAVED_SETTINGS` → reschedules all when `cron_time_interval` or `auto_monetize_amazon` is
saved (**F11**, 32C). Custom schedules (1004-1046): `lasso_five_minutes`, `lasso_ten_minutes`,
`lasso_fifteen_minutes`, `lasso_half_hour`, `lasso_1_hour`, `lasso_2_hour`, `lasso_3_hour`, `lasso_monthly`. Daily and
hourly events run at a minute hashed from `home_url()` (UTC 01:00-23:59, never hour 0; 647-743); background calls to
`lasso.link` sleep 0-499 ms (806-860); "Lasso Lean" (LaunchDarkly flag) keeps only `CRONS_LEAN`. All cloud fleet
tuning: Affibloom keeps ADR-4's 03:00 site-time event (requirements D2).

### B. Background processes (`Lasso_Process` subclasses)

Shared runner (`class-lasso-process.php`): a queue in `wp_options` (`wp_<action>_batch_<md5>`), a loopback POST to
`admin-ajax.php?action=wp_<action>` (non-blocking, 0.01 s; `wp_ajax_nopriv_` with a nonce), a site-transient lock (30 s,
467-474), 10 s of work per request (33, 96-98), memory 90 %, a CPU guard (435-458), at most 2 running at once when
`processes_execute_limit` is on (718-757; the rest waits in `lasso_processes_queue` by priority, 787-873), a 6-hour
age-out with 3 restart attempts (301-322), progress options `…_total_count`, `…_start_time`, `…_completed_time`.

| Class (action) | Started by | What it does | Cloud | Content | Owner |
|---|---|---|---|---|---|
| `Lasso_Process_Check_Issue` (`lasso_check_issue_process`) | E1 | `check_issues()` per product | yes | no | **F11** |
| `Lasso_Process_Update_Amazon` | E2 | Amazon refresh, files issues | API / BLS | no | **C1** (+ F11 recorder) |
| `Lasso_Process_Add_Amazon` (`lasso_add_amazon_link`) | E3 | Amazon auto-monetize import | API / BLS | link locations | **F35** |
| `Lasso_Process_Auto_Monetize` | E5 | auto-monetize table → products | final URLs | no | **F35** |
| `Lasso_Process_Link_Database` | settings save (class-setting.php 1227), import (`import-urls/class-hook.php` 180), install (`install/class-ajax.php` 67) | content scan of given posts | final URLs | **yes** | **F21** |
| `Lasso_Process_Build_Link` | E6 | incremental content scan | final URLs | **yes** | **F21** |
| `Lasso_Process_Force_Scan_All_Posts` | E7, `lasso_trigger_cron` | full scan with ETA | final URLs | **yes** | **F21** |
| `Lasso_Process_Scan_Links_Post_Save` | `save_post` | scan of the saved post | final URLs | **yes** | **F21** |
| `Lasso_Process_Scan_Link` | product save (`class-lasso-affiliate-link.php` 1810), group remove (`groups/class-ajax.php` 255) | rescans posts using a product | no | **yes** | **F21** (no rewrite: F1 D13, F23 D6) |
| `Lasso_Process_Build_Rewrite_Slug_Links_In_Posts` | link-prefix change (`settings-general/class-ajax.php` 420) | rewrites cloaked links to the new prefix | no | **yes** | **F21** (render time) |
| `Lasso_Process_Import_All`, `Lasso_Process_Revert_All` | E8, E9 | import / revert | no | **yes** | **F33** |
| `Lasso_Process_Replace_Shortcode` | `Lasso_Import` (class-import.php 150, 260, 273) | replaces imported shortcodes | no | **yes** | **F33** |
| `Lasso_Process_Remove_Attribute` | Settings → General "Uninstall Lasso" | strips Lasso attributes from posts | no | **yes** | **F33** (cutover) |
| `Lasso_Process_Update_Category_For_Imported_Pretty_Links` | method 6473, hooked to nothing | Pretty Links categories → groups | no | no | **F33** (dead) |
| `Lasso_Process_Pretty_Link_Final_Url` | nothing (its map is read by `pages/fix/class-hook.php` 166-187) | Pretty Links final URLs | final URLs | no | **F33** (dead) |
| `Lasso_Process_Remove_Genius_Formatting` | E14 | geni.us re-save | final URLs | indirect | **F17 / C1** |
| `Lasso_Process_Bulk_Add_Links` | Add Links modal (`pages/class-ajax.php` 967, 998) | bulk add | final URLs | no | **F1** (built: `BulkAddCron`) |
| `Lasso_Process_Bulk_Sync_Amazon` | banner + `lasso_bulk_sync_amazon_*` | Amazon metadata for pending products | API / BLS | no | **C1** |
| `Lasso_Process_Scan_Keyword` | nothing | keyword mentions | no | — | **excluded** (keyword opportunities) |
| `…_Data_Sync_Content`, `…_Link_Issues`, `…_Link_Location`, `…_Lasso_Links`, `…_Authors` (no starter), `…_Sync_Link_From_Cloud_To_WP`, `…_Update_Aff_Plus` | E15-E18, E26, E25 | cloud syncs | yes | no | **cloud: never** |

Admin AJAX around them: `get_list_background_processing` (**F11** 32E), `lasso_cron_handle_manually` and
`lasso_check_crons` (**F11** 32D), `lasso_trigger_cron` (rebuild the index, **F21**), `lasso_get_status` (**F33**/**F21**),
`lasso_heartbeat` (bulk add **F1**, bulk Amazon sync **C1**). Table `lasso_background_process_runs` (run metrics, 50
newest kept; written by the link scans only, 50 rows here): **F21**; F11 keeps progress in its option (requirements D11).

**Result:** every event and process is owned, cloud (never) or excluded by the user, except E13 (requirements N5).

## Data

| Where | What | From Lasso |
|---|---|---|
| Table `{prefix}affibloom_link_issues` (30A; replaces Phase 6's `affibloom_link_checks`) | one row per product: `product_id` PK, `issue_type` char(3) (`200`, `000`, HTTP code), `checked_url` varchar(2000), `detection_date` datetime NULL, `resolved` tinyint, `resolved_at` datetime NULL, `last_checked_at` datetime, `failures` tinyint, `first_failed_at` datetime NULL, `pending_type` char(3) NULL; keys (`resolved`,`issue_type`,`detection_date`), (`last_checked_at`) | `lasso_url_issues`; `is_ignored` (no writer in 369) and `total_404_request` (cloud ration) not kept |
| `Domain\LinkHealth\IssueDefinitions` | the 41 codes with label and Affibloom's one-line description | `lasso_url_issue_definitions` (41 rows, no reader in 369) |
| Option `affibloom_link_check` (no autoload) | the pass: `date` (site day), `started_at`, `total`, `checked` | `lasso_check_issue_page`, `lasso_check_issue_date`, `wp_lasso_check_issue_process_*` |
| Transient `affibloom_link_check_lock` (60 s) | one batch at a time | `wp_lasso_check_issue_process_process_lock` (30 s) |
| Transient `affibloom_link_issue_counts` (1 h, deleted on every write) | counts for the notice (32B) | none |
| Post meta `_affibloom_dismiss_broken`, `_affibloom_dismiss_out_of_stock` | Unix time until which the product page bar stays hidden | `dismiss_broken`, `dismiss_out_of_stock` |
| Settings (existing store, `affibloom_settings`) | `performance_mode` (power/balanced/light/custom, default balanced), `cpu_threshold`, `cron_interval` (0-3), `manual_process_limit` (1-3), `process_limit` — F9 D3 names (settled 2026-11-14) | Lasso: `general_performance_mode`, `cpu_threshold`, `cron_time_interval`, `manually_background_process_limit`, `processes_execute_limit` (Task 26C) |
| Cron | `affibloom_daily` (03:00 site time), `affibloom_link_check_batch` (single, chained), `affibloom_link_check_product` (single, args `[id]`) | E1, E29 |
| Action | `affibloom_link_issue_detected( $product_id, $url, $issue_type )` | `lasso_issue_detection` |

Migration (F33, Task 26): `lasso_url_issues.id` → `product_id` through the ID map; `issue_slug` → `checked_url`;
`issue_resolved_dt` → `last_checked_at` (Lasso overwrites it on every failing check, cron 1144-1162) and, for resolved
rows, `resolved_at`; dismiss meta → the `_affibloom_` keys.

## Entry points and request paths

```
WP-Cron affibloom_daily (03:00 site time)                                                     (30A)
  → Interface\Cron\LinkCheckCron::start() → LinkCheckService::start_pass()
       passed today already? stop; delete orphan rows; option { date, total } → dispatch batch
WP-Cron affibloom_link_check_batch                                                            (30A, 30C)
  → LinkCheckCron::batch()   lock or stop; CPU over threshold → retry in 60 s (32C)
  → LinkCheckService::run_batch( 10 s, 100 )
       LinkIssueRepository::due( now, 100 )   published, order R6, suspects 1 h after their failure (D4)
       per product: blank → 404 | Amazon + C1 → AmazonProductCheck | Amazon, no C1 → skipped | else
       HttpLinkStatusChecker (wp_safe_remote_get, GET, 25 s, 5 redirects, WordPress user agent, 32 KB)
       → LinkHealthPolicy::apply() → LinkIssueRepository::save() → do_action( affibloom_link_issue_detected )
       more due → dispatch again (after cron_time_interval hours when set)
POST|PATCH /affibloom/v1/products[/{id}]  (existing) → after a save that needs it:
       schedule affibloom_link_check_product [id] now (+ the confirmation 1 h later)                (30B)
GET admin.php?page=affibloom[&filter=broken-links|out-of-stock|opportunities]                     (31A, 31B)
GET /affibloom/v1/dashboard/products?…&filter=&order_by=detection_date   (13D)
  → DashboardController → LinkIssueQuery::for_products( ids ), ::counts( search, filters )
  ← rows: alert { broken, out_of_stock, since, days, checked_ok, pending, amazon_unchecked }
    total: broken_link_count, out_of_stock_count, warmup, amazon_api
GET admin.php?page=affibloom-product&id=N → ProductScreen → LinkIssueQuery::notices( id )        (31C)
POST /affibloom/v1/products/{id}/alerts/dismiss { type }   → 200 { dismiss_until }
       400 affibloom_invalid_alert_type, 404 affibloom_product_not_found
GET /affibloom/v1/dashboard/export?filter=&search=&order_by=&order_type=&group=&link_kind=&placements=
    &comparison_table=&_wpnonce=   → text/csv attachment affibloom-<filter>-<Y-m-d>.csv          (31D)
       400 affibloom_export_filter "Export is only available on broken-links and out-of-stock views."
wp affibloom check-links [--all] [--product=<id>] [--format=table|json|csv]                       (32A)
admin_notices (index.php, Affibloom screens) → BrokenLinksNotice → LinkIssueQuery::counts()      (32B)
POST /affibloom/v1/settings/general { performance_mode, cpu_threshold, cron_interval,
  manual_process_limit, process_limit }  → reschedule when the interval changed (32C)
POST /affibloom/v1/jobs/run-due → 200 { status: true, message: "Cron is working well." }
                                   | 200 { status: false, message: "WP cron is disabled", ran: [hooks] }   (32D)
GET /affibloom/v1/jobs → 200 { running_total, items: [ { name, completed, total } ] }              (32E)
```

## Layers

- **Domain** `LinkHealth\IssueType`, `LinkHealth\LinkIssue`, `LinkHealth\CheckResult`, `LinkHealth\LinkHealthPolicy`
  (pure transitions), `LinkHealth\IssueDefinitions`, `LinkHealth\AlertWindow` (grace days), `LinkHealth\AlertState`
  (a row's Alert cell), `LinkHealth\LinkIssueRepository` (interface).
- **Application** `LinkHealth\LinkCheckService`, `LinkHealth\LinkStatusChecker` (port), `LinkHealth\AmazonProductCheck`
  (port, C1), `LinkHealth\LinkIssueQuery`, `LinkHealth\IssueLinksExport`, `Jobs\JobStatus`, `Jobs\DueJobs`,
  `Jobs\CpuGuard` (port), `Jobs\JobSchedule`.
- **Infrastructure** `Http\HttpLinkStatusChecker`, `Schema\{Table, Migrator}` (first built here), `Tables\LinkIssueTable`,
  `WordPress\WpCpuLoad`, `WordPress\WpCronHealth`, `Amazon\CreatorsProductCheck` (dormant).
- **Interface** `Cron\LinkCheckCron`, `Cli\CheckLinksCommand`, `Admin\BrokenLinksNotice`, `Rest\DashboardExportController`,
  `Rest\ProductAlertsController`, `Rest\JobsController`; partials `alert-cell.php`, `alert-tabs.php`, `issue-notice.php`,
  `jobs-indicator.php`; `dashboard.js`, `product-save.js`, `admin.js`.

## Risks

- **Amazon is most of the library** (233 of 263 products; all 112 open issues here are Amazon). Without C1 the HTTP
  check covers 23 products (16 plain, 7 Walmart). The screens say so (requirements D7).
- **Merchants that refuse robots** (403/429/503): the two-check rule (AC1, D4) reduces noise; Lasso's cloud second
  opinion is gone; N1 asks which codes alert. Affibloom sends WordPress's own user agent, as Lasso's check does (D9).
- **Traffic-driven WP-Cron:** 32D's admin-page fallback; ADR-4 accepts a late start.
- **A 25 s request in a 10 s budget:** the budget is checked between requests; one batch can run ~35 s; the lock (60 s)
  outlasts it.
- **CPU reading** needs `exec` (Windows `wmic`) or `/proc/stat`; without them the load reads 0, never throttled (Lasso).
- **Big CSVs:** streamed in pages of 100.

## Security

- `Capabilities::PRODUCTS` on the Dashboard data, export, dismiss and jobs routes; `Capabilities::SETTINGS` on settings;
  REST nonce through `wp.apiFetch`; the CSV GET checks `_wpnonce`.
- Only stored product destinations are requested; `wp_safe_remote_get`, 25 s, 5 redirects, 32 KB read.
- CSV cells starting `=`, `+`, `@`, or `-` not followed by a digit get a leading tab (Lasso's guard).
- Lasso's runner accepted unauthenticated loopback calls; Affibloom's jobs run only from WP-Cron, WP-CLI or an
  authenticated REST call.
- No post content is written.

## Tests

- **Unit:** `LinkHealthPolicyTest` (first 200 inserts a checked row; one failure → pending, not open; two in a row →
  open with the first failure's date; 200 → resolved, type kept; failure after resolved → new date; blank → 404 at once;
  API NotFound/000 at once; URL changed → failures reset; request not executed → no change), `AlertStateTest` (every
  state; "1 day" on the day of detection), `AlertWindowTest`, `IssueDefinitionsTest`, `LinkCheckServiceTest` (order,
  budget, Amazon skipped without C1), `IssueLinksExportTest`, `CpuGuardTest`, `DueJobsTest`.
- **Integration:** `LinkIssueTableTest` (dbDelta schema, due query, counts with search and filters, orphan clean-up).
- **E2E** `tests/E2E/acceptance/F11.spec.ts` with the stub (`affibloom-stub/v1/status/<code>`): AC1-AC5 (30A; AC1's
  display parts in 31A/31B), AC6 (30B), AC7 (30C, fake C1 client), AC8, AC9, AC11 (31A), AC10 (31B), AC12 (31C), AC13
  (31D), AC14 (32A), AC15 (32B), AC16 (32C), AC17 (32D), AC18 (32E).
