# F11 Link health: requirements

Status: rewritten 2026-11-14 from a sweep of Lasso 369 (link health, the Alert column, Lasso's cron and background
jobs). Gate 1 of 2026-11-12 (Task 6, `docs/decisions/task-6-decisions.md`) kept; AC1-AC2 keep their numbers and
meaning, with corrections marked. Gate 4 for Tasks 30A-32E: given by the user 2026-11-14. These tasks replace Phase 6's Tasks 30-32.

## 1. User stories and acceptance criteria

- As an editor, I see which products have a dead link or are out of stock, since when, and I can list, sort and export
  them, so I can fix them.
- As an editor, a product's page tells me its link is broken or the product may be out of stock.
- As a developer, I check links on demand from WP-CLI.
- As a site owner, the checks run even where WP-Cron is weak, and I can slow them down on a busy server.

- **AC1** A product link that answers 404 is flagged after two checks and shows in the Alert column and the broken
  filter. (30A detection; 31A column; 31B filter) (corrected 2026-11-14: Lasso flags every answer other than 200, after
  its cloud's second opinion; Affibloom's second check replaces that opinion, at least one hour after the first; which
  other codes alert is N1.)
- **AC2** A blocked Amazon response is not flagged. (30A) (corrected 2026-11-14: Lasso never checks an Amazon product
  over HTTP, it asks the product API; Affibloom never requests Amazon URLs at all, so nothing Amazon answers can flag a
  product; without the Amazon API, Amazon products are not checked and the screens say so, D7.)
- **AC3** At 03:00 site time every published non-Amazon product is checked once, at most 100 a batch, batches chained,
  never two at once; a product that answers 200 shows the green tick "Link status checked. No alert issues detected.";
  running the daily event again the same day checks nothing. (30A)
- **AC4** A product with a blank destination is broken (404) after the pass, without any request. (30A)
- **AC5** A broken product that answers 200 again loses the red icon and gets the tick; when it breaks again its
  "Broken since" date is the new first-failure date. (30A)
- **AC6** Saving a product whose destination changed, or that was last saved more than an hour ago, checks it at once;
  a 404 is confirmed by a second check an hour later and then shows red; a 200 shows the tick. (30B)
- **AC7** With Amazon API credentials (a fake client in tests): a product the API does not find is broken, one with
  quantity 0 is out of stock ("Out of stock since …"), one back in stock is cleared; without credentials Amazon products
  are not checked, have no tick, and the out-of-stock tab says the check needs the Amazon API. (30C)
- **AC8** The Alert cell shows: red unlink "Broken since {Mon DD, YYYY}" linking to the product; orange box "Out of stock
  since {date}" linking to the product; the green lightbulb slot (F22); green tick when checked and clear; grey clock
  "Processing. Please check back in 24 hours." for a product published in the last 24 hours and not yet checked; under
  the icons "N days" ("1 day" on the first day). (31A)
- **AC9** Clicking the Alert header sorts by detection date ascending, then descending, then not at all. (31A)
- **AC10** Above the list, "Broken links (n)", "Out of stock (n)" (and F22's "Opportunities (n)") with Lasso's tooltips;
  a click shows only those products from page 1, oldest issue first, and marks the tab; a second click clears it;
  counts follow the search and the filters panel; a saved view keeps the tab; Reset filters clears it; the address
  carries `filter=`; the Broken links tile (U9) shows the broken count; for 24 hours after activation or migration the
  counts carry a clock and "Totals may update for up to 24 hours while links are checked." (31B)
- **AC11** With "Days before out of stock alerts" set to N, an out-of-stock issue younger than N days shows nowhere:
  not in the Alert cell, the count, the tab, the product page or the export. (31A, used by 31B-31D)
- **AC12** A broken product's page shows the red bar "Your URL is broken."; an out-of-stock one the orange bar "This
  product may be out of stock."; closing a bar hides it for 7 days, also after a reload. (31C)
- **AC13** On the broken and out-of-stock tabs, **Export CSV** downloads `affibloom-<filter>-<YYYY-MM-DD>.csv` with
  Lasso's 24 columns, the rows of the filtered list in its order, `alert_status` "Broken (404)" / "Out of stock"; with
  no rows the button is disabled, titled "No links to export for the current filters."; success says "Exported N
  links."; on other tabs the button is hidden. (31D)
- **AC14** `wp affibloom check-links --product=<id>` checks one product and prints it; `--all` runs the whole pass now
  and prints a table (product, URL, result, issue, since); Amazon without C1 prints "skipped". (32A)
- **AC15** With broken products, users with the products capability see a warning on the WordPress Dashboard and on
  Affibloom screens, "N product links look broken." with "See broken URLs" opening the broken tab; with none, nothing.
  (32B)
- **AC16** Settings → General shows Performance (mode Power, Balanced, Light, Custom; Custom shows the CPU %); choosing a
  mode fills Lasso's presets; with `&support` in the address the Cron interval (0-3 hours), the manual limit (1-3) and
  "Processes Execute Limit" show; saved values reload; a 2-hour interval spaces the check's batches 2 hours apart. (32C)
- **AC17** When WP-Cron cannot run (spawn test fails), opening an Affibloom screen runs due Affibloom jobs from the page
  within the manual limit; when it works the poll stops. (32D)
- **AC18** While the check runs, the header's background icon shows the running count and "Checking for broken links
  (completed/total)"; when idle, Lasso's idle line. (32E)

## 2. Evidence

Read in full: `classes/class-lasso-cron.php` 1-1210 and 4900-6988 (the rest, 1800-4900 and 5983-6455, content
scanning, indexed and searched for scheduling, cloud and write calls); `classes/background_processing/
class-lasso-process.php`, `class-lasso-process-check-issue.php`, `class-lasso-process-update-amazon.php` 40-125, and
every `class-lasso-process-*.php` searched for its starter, cloud calls and writes; `libs/background_processing/
lasso-wp-background-process.php`, `lasso-wp-async-request.php` (key lines); `models/class-url-issues.php`,
`class-url-issue-definitions.php`, `class-background-process-runs.php`; `classes/class-lasso-db.php` 296-655,
1014-1190, 1955-2125, 3270-3290, 4083-4349, 5320-5325, 5605-5634; `classes/class-helper.php` 54, 72, 137, 185-555,
940-1020, 1745-1795, 1967-2039, 6669-6721; `classes/class-lasso-amazon-api.php` 157-163, 755-834, 1943-1983;
`classes/class-extend-product.php` 20-34, 355-357, 426-461; `classes/class-lasso-affiliate-link.php` 189, 353-401,
595-597, 1296-1342, 1820-1842; `classes/class-wp-all-import-csv-export.php` (all); `classes/class-verbiage.php` 14-33;
`classes/class-lasso-init.php` 256-264; `pages/class-ajax.php` 60-103, 105-893, 1318-1363, 1513-1716, 1790-1911,
2327-2385, 2570-2610; `pages/settings-general/class-ajax.php` 525-540; `classes/class-setting.php` 1065-1068;
`admin/views/dashboard/dashboard.php` (all); `admin/views/rows/url-row.php` (all); `admin/views/dashboard/
url-details.php` 1-48, 680-690; `admin/views/dashboard/header.php` 55-95; `admin/views/notifications/
link_detail_notification.php` (all); `admin/views/settings/general.php` 104-135, 285-365, 425-450, 660-745;
`admin/views/settings/amazon.php` 330-342; `admin/assets/js/view-js.php` 180-385, 1185-1262, 1772-1935, 2136-2177 and
the Export CSV handler; `admin/assets/js/lasso-page.js` 1-30, 160-450; `admin/assets/js/lasso-helper.js` 1-20;
`admin/lasso-constant.php` 33, 39, 56-87, 786-787; `pages/class-hook.php` 194-196, 831-853.

**This copy, 2026-11-14** (Lasso inactive; `lasso_license_status` 0): 263 published products; `wp_lasso_url_issues`
256 rows, none orphaned: `000` open 109 / resolved 53; `200` (resolved) 78; `404` open 3 / resolved 5; `400` open 1 (a
trashed product, slug a bare ASIN); `403` resolved 1; `500` resolved 2; `503` resolved 4. Open on published products:
**109 out of stock, 3 broken, all Amazon URLs**. Oldest open out-of-stock 2026-09-05 (70 days), newest 2026-11-09;
`total_404_request` > 0 on 3 rows. 9 published products have no row. The last pass: `lasso_check_issue_date`
2026-11-09, completed 00:11:17 UTC. `lasso_url_details`: 233 amazon, 7 walmart, 16 blank type.
`wp_lasso_url_issue_definitions`: 41 rows (below). `wp_lasso_background_process_runs`: 50 rows (34
`Scan_Links_Post_Save`, 16 `Build_Link` of 1,020 items, 2026-10-24 to 11-09); the check is not instrumented. Cron
events: only 5 orphans (E31 in analysis); Lasso's own events are cleared. Settings: `cron_time_interval` 0,
`manually_background_process_limit` 2, `processes_execute_limit` false, `cpu_threshold` 80,
`days_before_out_of_stock_alerts` 0, `amazon_update_pricing_hourly` false (so Amazon products were checked by E1
through the API), `amazon_pricing_locations_only` true. 5 `dismiss_out_of_stock` meta rows, all expired (latest
2026-06-13). One stale queue option `wp_lasso_bulk_sync_amazon_batch_…`.

The 41 definitions (code, label; descriptions are Mozilla's HTTP texts, 46-573 characters, not copied): 000 Amazon Out
of Stock ("Lasso-specific code for out of stock products on Amazon."); 400 Bad Request; 401 Unauthorized; 402 Payment
Required; 403 Forbidden; 404 Not Found; 405 Method Not Allowed; 406 Not Acceptable; 407 Proxy Authentication Required;
408 Request Timeout; 409 Conflict; 410 Gone; 411 Length Required; 412 Precondition Failed; 413 Payload Too Large; 414
URI Too Long; 415 Unsupported Media Type; 416 Range Not Satisfiable; 417 Expectation Failed; 418 I'm a teapot; 421
Misdirected Request; 422 Unprocessable Entity (WebDAV); 423 Locked (WebDAV); 424 Failed Dependency (WebDAV); 425 Too
Early; 426 Upgrade Required; 428 Precondition Required; 429 Too Many Requests; 431 Request Header Fields Too Large; 451
Unavailable For Legal Reasons; 500 Internal Server Error; 501 Not Implemented; 502 Bad Gateway; 503 Service
Unavailable; 504 Gateway Timeout; 505 HTTP Version Not Supported; 506 Variant Also Negotiates; 507 Insufficient Storage
(WebDAV); 508 Loop Detected (WebDAV); 510 Not Extended; 511 Network Authentication Required.

## 3. What Lasso does

| # | Rule | Source |
|---|---|---|
| R1 | **Storage:** `lasso_url_issues` (`id` = product ID, PK; `detection_date`; `issue_type` varchar(50); `issue_slug` varchar(500); `issue_resolved`; `is_ignored`; `issue_resolved_dt`; `total_404_request`; three keys on resolved/ignored/type/date). One row per product: only the **primary destination** is checked, never the second button | models/class-url-issues.php 26-62; cron 1089-1092 |
| R2 | **Definitions:** `lasso_url_issue_definitions` (id, `issue_type` varchar(3) unique, title, description), 41 rows inserted with `INSERT IGNORE` at activation and update; nothing in 369 reads them | class-url-issue-definitions.php 48-145; class-activator.php 129; class-update-db.php 147 |
| R3 | **Schedule:** `lasso_cron_check_issue` every 5 min (floored by R33). `check_issue()`: age-out check; at the process limit → queued; running → stop; if page = 1 and `lasso_check_issue_date` = today (UTC) → stop; else the next 100 (`LIMIT`), none → page reset to 1, stop; else page + 1, date = today, queue, `set_total`, dispatch. One pass a day, a page per tick | check-issue 17-122 |
| R4 | Before each page: deletes `lasso_url_issues` rows whose ID is not a `lasso-urls` post (any status), and `lasso_url_details` rows not of `lasso-urls`/surl | db 4131-4153 |
| R5 | **Which products:** published; with `amazon_update_pricing_hourly` on, Amazon products with a product ID are left to E2 (placeholder titles '', 'Lasso', default title kept); with `amazon_pricing_locations_only` (default true) only products with a live location (link locations or map, slug not empty or trashed) or a placeholder title | db 4155-4218 |
| R6 | **Order:** `ISNULL(issue_resolved), issue_resolved ASC, issue_resolved_dt ASC`: products with a row first, open before resolved, oldest check first; never-checked last | db 4220-4240 |
| R7 | **Runner:** 10 s of work per request, loopback re-dispatch, 30 s lock, 90 % memory, 5-min healthcheck, priority 10; CPU ≥ `cpu_threshold` (fallback 80) → the item is skipped this round; threshold ≤ 50 → sleep 1 s per item; 6 h age-out, 3 restarts, then the admin page runs it (R35); `lasso_disable_processes` stops all | class-lasso-process.php 33-53, 301-322, 435-530 |
| R8 | Per product: heal a Hub-sync "ghost" shortlink first (cloud); the destination is `target_url` normalised and trimmed | cron 1063-1092 |
| R9 | **Blank destination** → `404`, no request | cron 1094-1095; helper 197-206 |
| R10 | **Amazon product** (link type amazon): `fetch_product_info` (Creators API if configured, else Lasso's cloud); `NotFound` → `404`; otherwise `quantity > 0` → `200`, else `000` (so any other failure without a quantity → `000`) | cron 1097-1106; amazon-api 755-817 |
| R11 | **Extend product** (product type + ID: Chewy, Wayfair, Walmart, Target, Home Depot, Groupon, Best Buy US/CA, Fanatics ×5, Fansedge, Lids …): short/affiliate links resolved first; data from `lasso.link`; `NotFound` → `404`; quantity > 0 → `200`, else `000` | cron 1107-1123; extend-product 20-34, 426-461 |
| R12 | **Everything else:** `get_url_status_code`: BLS-listed domains → cloud; Amazon with ASIN → R16; extend URLs → cloud; else `send_request('get', $url)`: GET, 25 s (`TIME_OUT`), WordPress's default redirects (5), **no headers, so WordPress's own user agent** (the browser string computed at 225-226 is not passed); `WP_Error` → 500; 404 → `404` at once; any other non-200 answer → **cloud** `lasso.link/link/status/` decides; 200 → `200` | helper 214-259, 964-1000 |
| R13 | **Result:** `200` → insert a resolved row of type `200` or set resolved = 1 and its time, keeping type and date; other → action `lasso_issue_detection` (id, url, status) and upsert: new row open dated now; existing: type, slug, resolved = 0, `issue_resolved_dt` = now (every failing check); `detection_date` reset only when it was resolved | cron 1129-1166; db 4101-4126, 4253-4288 |
| R14 | Cloud ration: after 3 cloud 404s for a URL no more cloud calls (`total_404_request`) | helper 271-330; db 5605-5634 |
| R15 | **On product save:** checked unless new, or updated within the hour with the same URL; status from the save's fetch, else R12; "Saved but could not resolve the URL." when invalid or not 200; a new link with a non-200 answer, or Amazon/extend NotFound/000 found while saving, filed without a request (Amazon via R16) | affiliate-link 1305-1342, 1488-1625, 1830-1842 |
| R16 | `check_amazon_link_status`: not configured → 200; success → quantity 0 → `000`, else 200; error in ignore list (ItemNotAccessible, InvalidParameterValue, AccessDeniedException, AccessDenied, TooManyRequestsException, …) → 200; NotFound → 404; empty error → the product's status code (404 → 200); other → 200; no status → 404 | amazon-api 157-163, 1943-1983 |
| R17 | E2 files 404/000/200 through `check_issues(…, true)`; `resolve_issue_by_url` resolves 404 rows by URL; `resolve_product_out_of_stock` resolves `000` rows of every product with that ASIN + country and clears `out_of_stock` | cron 5105-5153; db 4296-4349; amazon-api 1034-1036; extend 355-357 |
| R18 | `is_ignored` has no writer for issues (dismiss writes link/keyword locations); every query filters `is_ignored = 0`. E16 sends issues to the cloud hourly | db 3275-3290; analysis E16 |
| R19 | **Product flags:** out of stock = Amazon product's `out_of_stock`, extend product's, or an open `000` row; broken = an open non-`000` row or a blank destination | affiliate-link 353-401, 595-597 |
| R20 | **Alert header** "Alert", sortable by `detection_date`: asc, desc, off | dashboard.php 118; view-js 1772-1790 |
| R21 | **Row data:** `broken_link` = open non-000 issues (0/1); `out_of_stock` from R19 (not grace-filtered); `issue_detection_since` = `M d, Y` of the latest open issue date (grace-filtered); `issue_detection_days` = days + 1, "day"/"days"; checked = a row of type 200 or resolved; pending = published within 24 h, not checked, not broken/oos; checked-ok = checked, not broken/oos | pages/class-ajax.php 137-246, 612-648, 822-843; helper 6669-6721 |
| R22 | **Cell:** red unlink if `broken_link == 1`, a date, and not the out-of-stock tab; red tooltip "Broken since {date}", links to the product. Orange box if out of stock; tooltip "Out of stock since {date}" (none without a date); links to the product. Green lightbulb if opportunities > 0: "This URL has {n} possible opportunity/opportunities.", links to the product's opportunities. Green tick "Link status checked. No alert issues detected." (role status). Grey clock "Processing. Please check back in 24 hours.". Then the days line. Else empty | url-row.php 112-158 |
| R23 | **Header counters:** hidden until the first load; red "See broken URLs" (unlink + count), orange "See out-of-stock products" (box + count), green "See opportunities" (lightbulb + count), thousands separators; during the 24 h stats warm-up a grey clock per count, a second tooltip line "Totals may update for up to 24 hours while Lasso scans your links.", `aria-busy` | dashboard.php 52-62; view-js 2136-2177 |
| R24 | **Counts:** broken = distinct published products with an open non-000 issue; out of stock = with an open `000` older than the grace days; scoped by search and filters when a filter is set; on a tab the list total is that count | pages/class-ajax.php 248-279; db 1989-2124 |
| R25 | **Tabs:** click → active, page 1, Alert sort set to `detection_date` asc (opportunities: `opportunity_count` desc); clicking the active one clears it (back to `?filter=`); one at a time; export state updates; saved views store and restore it; Reset clears it; a manual change drops the active saved view | view-js 1185-1262, 1850-1931 |
| R26 | **Tab queries:** two-phase (page of IDs, then enrichment); sorts `post_modified`, `detection_date` (nulls last), `count`, `clicks`, `opportunity_count`; issue tabs' search ignores the destination URL | db 434-604, 2042-2051 |
| R27 | **Grace days** `days_before_out_of_stock_alerts` (Settings → Amazon, 0-30, "Days before out of stock alerts", tooltip "Only see out-of-stock alerts after this many days have passed.", stored `abs(int)`): out-of-stock count, tab, date, days and sort; **not** the row's orange icon or the product page bar | amazon.php 330-342; class-setting.php 1068; db 463-478, 1050-1076, 2012-2034 |
| R28 | **Export CSV:** "Export CSV" button only on the broken / out-of-stock tabs; disabled with "No links to export for the current filters." at count 0, else "Export all filtered links to WP All Import CSV"; sends filter, search, sort, group, link kind, placements, table; spinner + "Exporting"; "Exported {n} link(s)."; any failure "Export failed."; server: edit_posts + nonce; other filters → 400 "Export is only available on broken-links and out-of-stock views."; `lasso-{filter}-{Y-m-d}.csv`; 24 columns `title, slug, categories, lasso_fields, lasso_custom_redirect, lasso_custom_thumbnail, buy_btn_text, open_new_tab, enable_nofollow, second_btn_url, second_btn_text, open_new_tab2, enable_nofollow2, custom_theme, badge_text, price, show_price, affiliate_desc, disclosure_text, show_disclosure, enable_sponsored, amazon_asin, alert_status, location_count`; pages of 100 in list order; `alert_status` "Out of stock" / "Checked" / "Broken (%s)"; `lasso_fields` `type >> name >> value` joined by `\|\|`; toggles 0/1; formula guard; "Could not open export stream." | dashboard.php 75-77; view-js 201-271 + handler; pages/class-ajax.php 2330-2385; class-wp-all-import-csv-export.php |
| R29 | **Product page bar:** if broken and not dismissed (`dismiss_broken` > now) → red bar "Your URL is broken." with a close button; if out of stock and not dismissed → orange bar "This product may be out of stock."; both can show; close → meta = now + 7 days; edit_posts + edit_post, nonce; "Invalid post_id or type" | url-details.php 1-48, 686-690; pages/class-ajax.php 867-893, 1322-1363; link_detail_notification.php |
| R30 | No broken-link admin notice; Lasso removes other plugins' notices on its own screens | pages/class-hook.php 194-196, 837-853 |
| R31 | **Support-only fields** (Settings → General with `&support`): "Cron" / "Time interval (hours)" 0-3 (`cron_time_interval`); "Lasso Cronjob Manually" / "Maximum Lasso cronjob at a time." (tooltip "This feature runs only when the system's cronjob does not work.") 1-3 (`manually_background_process_limit`, clamped); toggle "Processes Execute Limit" (`processes_execute_limit`) | general.php 295-358; settings-general/class-ajax.php 536-538 |
| R32 | **Performance** (always): "Set the maximum CPU level that Lasso will run at." (tooltip "Lasso will wait until your CPU drops below this level to continue with its updates."); "Performance Mode:" Power, Balanced, Light, Custom (`general_performance_mode`, default balanced); Custom shows `cpu_threshold` with "%"; a preset fills Power 80/0/2, Balanced 65/0/2, Light 50/1/1 (CPU / interval / manual limit); page load never overwrites | general.php 104-130, 433-447, 668-705 |
| R33 | **Effects:** interval N → every Lasso event shorter than N hours runs every N hours (`lasso_N_hour`); saving it reschedules; the manual limit bounds R34/R35; execute limit → 2 processes at once; CPU → R7 | cron 225-255, 460-587; class-lasso-process.php 718-757 |
| R34 | **`lasso_check_crons`:** admin pages poll every 15 s, at most 5 min, shared across tabs; spawn test OK (cached 1 h) → "Cron is working well.", stop, restart counter reset after 24 h working; else the failure time is stored and every due Lasso event's callback runs inline (rescheduled), stopping at the manual limit → "WP cron is disabled" | pages/class-ajax.php 1564-1647; lasso-page.js 410-450; helper 1973-2039 |
| R35 | **`lasso_cron_handle_manually`:** for an allow-listed process, runs its queue inline when restarts ≥ 3, queue non-empty, no lock, running < limit → "Success." / `trigger_handle`; unknown class → "Class does not exist" | pages/class-ajax.php 1652-1696, 2599-2610; lasso-page.js 386-400 |
| R36 | **Tracker:** `get_list_background_processing` every 15 s for 5 min: `running_total`, items "{name} ({completed}/{total})" (names from Verbiage, e.g. "Checking for broken links"), ETA for the full scan; idle "Silence is a source of great strength - Lao Tzu" | class-lasso-process.php 648-711; lasso-page.js 160-375; verbiage 14-33 |
| R37 | `lasso_trigger_cron` (Settings rebuild): full rescan; "Working... Please be patient." / "The processes are running." → F21 | pages/class-ajax.php 1701-1716; general.php 709-738 |
| R38 | `lasso_get_status` (import / revert / link-database progress) → F33/F21; `lasso_heartbeat` (bulk add progress, no nonce in that branch; bulk Amazon sync) → F1/C1 | pages/class-ajax.php 1516-1557, 1886-1911 |
| R39 | **Cloud:** R12's second opinion, R14, the ghost heal (R8), E16's hourly sync: C, never | as cited |
| R40 | **Inventory:** every event and process in analysis "Cron and background-job inventory" has an owner | analysis |

## 4. Decisions

Settled from Lasso and the records:

- **D1 Storage:** `{prefix}affibloom_link_issues`, one row per product, the primary destination (R1; N4 asks about the
  second button), created by the plugin's `Migrator` (first built here).
- **D2 Schedule (ADR-4):** `affibloom_daily` at 03:00 site time starts one pass a day (R3's once-a-day rule); batches are
  chained `affibloom_link_check_batch` events, lock transient 60 s, 10 s of work and at most 100 products each (R3, R7).
  Lasso's hashed UTC slot, Lean and request pacing are cloud-fleet tuning, not rebuilt.
- **D3** `is_ignored` and `total_404_request` are not kept (R14, R18). The 41 definitions are a domain map with
  Affibloom's own one-line descriptions (Lasso shows them nowhere; its texts are Mozilla's).
- **D4 Two checks (AC1, approved Task 6):** an HTTP failure counts once (`failures` 1, `first_failed_at`, not shown); the
  product is due again an hour later in the same pass; a second failure in a row opens the issue dated
  `first_failed_at` with the second code; a 200 in between resets. Blank destinations and product-API answers open at
  once (certain, as Lasso). Replaces Lasso's cloud second opinion (R12).
- **D5 Which products:** published; Amazon products (`AmazonUrl` with an ASIN) never over HTTP; R5's locations-only rule
  once F21's index exists, all before; order R6; orphan rows deleted at pass start (R4); a trashed product keeps its row.
- **D6 HTTP rule:** 200 → OK; any other answer, or no answer (500, as Lasso), → a failure (N1); a request WordPress
  refused to send (`http_request_not_executed`: blocked hosts, the Local copy) is no check at all.
- **D7 Amazon without C1:** not checked; no tick (the clock only in the 24 h window, as for any unchecked product); the
  out-of-stock tab and its tooltip, the broken tab, and an Amazon product's page carry "Amazon products are checked
  through the Amazon API, which is not connected (Settings → Amazon)."
- **D8 Amazon with C1:** Lasso's `check_amazon_link_status` rule (R16) for the pass and the save; C1's daily refresh
  (E2's equivalent) records through F11's recorder and resolves out-of-stock by ASIN + country (R17).
- **D9 Request:** `wp_safe_remote_get`, GET, 25 s, 5 redirects, 32 KB, WordPress's own user agent (R12, Lasso's).
- **D10** Action `affibloom_link_issue_detected( product_id, url, issue_type )` on every confirmed issue (R13).
- **D11** No run-metrics table; the pass's progress lives in `affibloom_link_check`.
- **D12 Save check (R15):** after a successful create or update that needs it, `affibloom_link_check_product` [id] runs at
  once (the save stays fast); the confirmation runs an hour later (D4). F1 keeps "Saved but could not resolve the URL.".
- **D13 Alert cell:** R22's states, texts and links with Aurora icons; Lasso's date `M d, Y`; days + 1 rule; the
  lightbulb slot hidden until F22 gives counts.
- **D14 Grace days everywhere** (AC11), the icon and the bar included: the help text promises alerts are not seen
  before; Lasso's icon and bar ignoring it is an inconsistency, not a feature.
- **D15 Tabs:** U2 (approved) labels with counts, "Broken links (n)", "Out of stock (n)"; Lasso's tooltips kept; values
  `broken-links`, `out-of-stock` in the address and saved views; U9's "Broken links" tile (approved 2026-11-13). F22
  adds "Opportunities (n)".
- **D16 Warm-up:** 24 h after activation or a migration, the counters show the clock and "Totals may update for up to
  24 hours while links are checked.".
- **D17 Bar:** Lasso's texts, 7 days, both bars, Aurora notice style, `role="status"`, dismiss through REST.
- **D18 Export:** Lasso's 24 headers (WP All Import compatibility and Lasso's help article), filled from Affibloom's
  products; `affibloom-<filter>-<Y-m-d>.csv`; the server's message on failure instead of always "Export failed." (Lasso's
  script swallows it, view-js handler); Task 38 reuses 31D's writer.
- **D19 CLI (Phase 6):** `--all` ignores the once-a-day rule; `--product=<id>`; prints product, URL, result, issue,
  since; Amazon without C1 "skipped (Amazon API not connected)".
- **D20 Admin notice (Phase 6; Affibloom addition, not Lasso, R30):** products capability, WordPress Dashboard and
  Affibloom screens, broken count > 0: "{n} product links look broken." + "See broken URLs".
- **D21 Settings:** R31-R32's fields, presets and the `&support` section, wording without the product name ("Set the
  maximum CPU level that the plugin will run at.", "The plugin will wait until your CPU drops below this level to
  continue with its updates.", "Cronjob Manually", "Maximum cronjobs at a time."); effects on Affibloom's jobs: batch
  spacing N hours, CPU over threshold → the batch waits 60 s, execute limit → 2 Affibloom jobs at once.
- **D22 Fallback:** REST `POST /jobs/run-due`, Lasso's timing and messages, runs due Affibloom events inline up to the
  manual limit; no restart counter (single events and a lock cannot age out).
- **D23 Indicator:** header icon with the running count; "{name} ({completed}/{total})"; names "Checking for broken
  links", "Bulk Add Links" (F1, built), others with their features; Lasso's idle line.
- **D24 No cloud:** R8's heal, R12's second opinion, R14, E16: not built.

### Needs the user

- **N1 Which answers alert.** Lasso: every answer but 200 (after its cloud). Old Phase 6 text: only 404/410; 401, 403,
  405, 429, 5xx, timeouts "inconclusive". *Recommend Lasso's rule with D4's second check:* here such codes were rare (1 ×
  403, 2 × 500, 4 × 503, 1 × 400) and all cleared on later checks; a merchant answering 403 to everyone would otherwise
  never be reported.
  → **Decided 2026-11-14:** E15: the user accepted the recommendation
- **N2 Extend products** (7 Walmart here): stock and "not found" came from Lasso's cloud. *Recommend the plain HTTP check
  (broken only, no out of stock).*
  → **Decided 2026-11-14:** E15: the user accepted the recommendation
- **N3 Lasso's last Amazon results at cutover** (109 out of stock, 3 broken, checked 2026-11-09). *Recommend migrating
  them with D7's line; C1's first check clears or confirms them.*
  → **Decided 2026-11-14:** E15: the user accepted the recommendation
- **N4 Second button:** Lasso checks only the primary destination; Phase 6 also checked button 2. *Recommend Lasso's
  rule.*
  → **Decided 2026-11-14:** E15: the user accepted the recommendation
- **N5 Missed scheduled posts (inventory E13):** Lasso publishes posts that missed their schedule, hourly. *Recommend
  keeping it as Task 32F, or confirming the host's cron makes it unnecessary.*
  → **Decided 2026-11-14:** E14: not built (a Lasso cron job, not affiliate-related).

## 5. Scope that needs a home

- **F21:** content scans (E6, E7, E10 index part, E11; `Link_Database`, `Build_Link`, `Force_Scan`, `Scan_Links_Post_Save`,
  `Scan_Link`, `Build_Rewrite_Slug`), `lasso_trigger_cron` (R37), run metrics. F11 needs F21's "used in content" query.
- **F34:** 14-day content-history retention (E10). **F35:** E3, E4, E5. **C1:** E2, bulk Amazon sync, the data refresh
  that calls F11's recorder; with **F17**, geni.us (E14).
- **F33:** import / revert / replace shortcode / remove attributes ("Uninstall Lasso"), the dead Pretty Links processes,
  E31's orphan events, migration of `lasso_url_issues` and the dismiss meta (N3).
- **F22:** the lightbulb count, its tab, and where program data (E20) comes from. **F12:** clicks counted locally (E19,
  E28 never). **F1:** bulk add and its progress (R38). **Task 38:** reuses 31D's CSV writer.
- **Unowned:** E13 (N5).

## 6. Edge cases

1. A destination that redirects more than 5 times: WordPress's error → no answer → a failure (500).
2. A request blocked by `WP_HTTP_BLOCK_EXTERNAL` or `http_request_host_is_external`: no check (D6); on Local the pass
   records nothing for external links.
3. The destination changes between the two checks: failures reset; the new URL starts at one.
4. A product trashed during a pass: skipped (not published); its row stays; restored, it shows its old state.
5. A product deleted for good: its row goes at the next pass start (R4).
6. Two batches at once (WP-Cron overlap): the lock stops the second; a lock left by a fatal error expires in 60 s.
7. The daily event missing (another plugin cleared it): `LinkCheckCron` reschedules on `init`.
8. Grace days lowered: older out-of-stock issues appear at once; raised: they hide; nothing is rewritten.
9. Export while the list changes: rows are read page by page; a product changed mid-export may appear once or not.
10. Dismissing a bar for a product the user cannot edit: 403; a type other than the two: 400.
11. Settings out of range (interval 7, limit 0, CPU 150): clamped as Lasso clamps (0-3, 1-3, `abs`); CPU above 100 means
    never throttled.
12. The CPU reading unavailable (`exec` disabled): 0, never throttled.
13. C1 credentials removed after issues were filed: Amazon issues stay as last known, with D7's line.
