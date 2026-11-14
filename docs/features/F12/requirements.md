# F12 Click counts and where used: requirements

Status: Approved with Task 6 (gate 1), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

## User stories

- As the site owner, I see how often each product is clicked and which posts use it.
- As an editor, I cannot delete a product that is still in use.

## Acceptance criteria

- **AC1** A click on a box button raises that product's Clicks.
- **AC2** The Locations tab lists the posts that use the product.
- **AC3** A product in use cannot be deleted.

Task 7 turns these into `tests/E2E/acceptance/F12.spec.ts`.

## Clicks — sweep of Lasso 369, 2026-11-14

Status: written 2026-11-14 from a sweep of Lasso 369's click counting and tracking (area 5 of the dashboard
completion plan). Gate 4 for Tasks 33A-33D and 34A-34D: waiting. This section owns AC1 and AC4-AC19. Google Analytics
events are C3 (`docs/features/C3/`).

**The answer to "where do Lasso's click numbers come from":** from **Lasso's cloud**, never from the site. The plugin
counts nothing. A visitor's click is sent by Lasso's script to `codedrink.com/js/e` (through the site's `/js/e` proxy);
once a day the cron `lasso_cron_sync_clicks` fetches `https://lasso.link/clicks`, sets every `lasso_url_details.clicks`
to 0 and writes the cloud's numbers (R1-R4). The Locations tab's per-location clicks come live from
`https://lasso.link/clicks/locations` (R11). The 2,839 on this copy is the sum of that column: the cloud's last snapshot,
which Lasso itself labels "last 30 days" (R12). It is not lifetime and not a history: no per-day, per-post or
per-location click is stored anywhere in the site's database.

### Acceptance criteria (Clicks)

- **AC1** (kept) A click on a box button raises that product's Clicks. (33A)
- **AC4** Clicks on every product element Lasso's script follows are counted for the product: a box's first and second
  buttons, its title and image links, further buttons (F29), and in-content links stamped `data-lasso-lid` or
  `data-affibloom-id`; a link without a product is not counted. Middle-click and Ctrl/Cmd-click count; the link opens
  as before. (33A, R5-R7)
- **AC5** Each click is stored as a daily total by product, host post, location and button; the route always answers
  204 and sets no cookie. (33A, R8, D2, D4)
- **AC6** A click whose user agent is a known bot, or an empty user agent, is not counted. (33B, R9, D5)
- **AC7** A second click on the same product, post and button by the same visitor within 30 seconds is not counted;
  after 30 seconds it is. Nothing identifying the visitor is stored. (33B, D6)
- **AC8** Click rows older than 400 days are removed by the daily job. (33C, ADR-005)
- **AC9** Settings → General → **Advanced Click Tracking**, toggle **Page and Link-Level Tracking** (on by default,
  imported from Lasso): off, no click is counted and the click script is not printed; on, counting resumes. (33D, R14,
  D7)
- **AC10** The Dashboard's **Clicks** column (after Locations) shows each product's clicks in the last 30 days as a
  bold number (0 when none). (34A, R10, D8)
- **AC11** The Clicks header sorts: first click descending, then ascending; the caret shows the applied order; the sort
  is kept in the address and in saved views. (34A, R10)
- **AC12** The Dashboard tile **Clicks (30 days)** shows the sum of the column's 30-day figures over all published
  products. (34B, U9)
- **AC13** The Locations tab's **Clicks** column ("Total clicks in the last 30 days.") shows each location's clicks in
  the last 30 days, 0 when none; a group row shows its locations' sum. (34C, R11; Locations AC23)
- **AC14** Clicks on a trashed or retired product are not counted; a repointed product's clicks are counted for its
  target (the click arrives with the old ID). (33A, D9)
- **AC15** With Lasso's last synced figures imported (N1), the Dashboard shows them on the cutover day and they leave
  the 30-day window 30 days after the date of that sync. (34D, R12, N1)
- **AC16** The page keeps working when the click route fails or is blocked (ad blockers): no error is shown, the link
  opens. (33A, R6)
- **AC17** Cached pages count: the script and its settings are the same for every visitor and read nothing per user.
  (33A, D3)
- **AC18** Nothing Affibloom does on a click calls Lasso's servers, `codedrink.com`, `lasso.to`, Segment, PostHog or
  Sentry. (33A, R15-R20)
- **AC19** The click script loads only on pages with a product element (a box, a display or a stamped link). (33A, D3)

### Evidence (Clicks)

All read: `models/class-url-details.php` (the `clicks` column, 92-100); `classes/class-lasso-cron.php` 123-199 (the
schedules), 260-323 (handlers, licence gate), 5802-5902 (`lasso_get_snippet`, `lasso_sync_clicks`,
`persist_url_details_clicks_from_sync_items`), 6937-6970 (`lasso_cron_get_js_domain`); `pages/class-ajax.php` 82,
125-245 (the Dashboard sort), 2512-2565 (`lasso_get_location_clicks`); `classes/class-lasso-db.php` 497-528, 573 (sort and
select of `ud.clicks`; the same at 755, 836, 908, 935-993, 1044, 1147); `classes/class-helper.php` 6324-6356
(`get_review_stats`), 6595-6607 (`build_lsid`), 6853-6939 (snippet helpers); `classes/class-setting.php` 995-1016
(`is_lasso_performance_tracking_enabled`), 1070-1115, 1389-1408; `pages/class-hook.php` 1630-1797 (`lasso_event_tracking`,
the loader and init), 1974-2097 (rewrites, `exclude_lasso_performance_js_from_rocket_cache`); `pages/class-hook-frontend.php`
(all); `pages/redirect/class-hook.php` 22-27; `classes/class-redirect.php` (all; `serve_connect_snippet`,
`serve_beacon_proxy`, `pass_through_url_parameters`); `classes/class-beacon-proxy.php` (all); `classes/class-affiliates.php`
(all; `track_click`); `libraries/class-lasso-url.php` 207-287 (the display attributes); `classes/class-lasso-shortcode.php`
496; `admin/assets/js/connect-snippet.min.js` (217,836 bytes, minified: constants, `LSAFFEvents` from `sendEvent` to
`handleRedirect`, the loader and embed code; the bot list and Sentry bundle skimmed); `admin/assets/js/view-js.php`
360-385, 2060-2097, 2316-2346, 2383-2396; `admin/views/dashboard/dashboard.php` 113-124; `admin/views/rows/url-row.php`
170-173; `admin/views/rows/link-display-row.php`; `admin/views/dashboard/url-links.php`; `admin/views/settings/general.php`
20-45, 139-225, 317-332; `admin/views/settings/uninstall.php`; `admin/views/modals/stop-lasso-analytics-confirm.php`;
`pages/settings-general/class-ajax.php` 247-299, 319-458; `admin/views/notifications/request-review.php`;
`admin/views/header-new.php` 78-171; `admin/lasso-constant.php` (all).

**This copy, 2026-11-14:** `SUM(lasso_url_details.clicks)` = **2,839**, all on published products; 176 products have
clicks > 0 (top five 186, 109, 90, 86, 77), 87 have 0. No per-day, per-post or per-location click data exists (no table
holds any; no `lasso_clicks_loc_*` transient). `lasso_cron_sync_clicks` is not scheduled (Lasso inactive), so the
snapshot's date is unknown. Settings: `performance_event_tracking` true, `stop_lasso_analytics` false,
`analytics_enable_click_tracking` true, `analytics_google_tracking_id` "371244810", IP anonymization and pageview true,
`international_click` true, `segment_analytics` true.

### What Lasso does (Clicks)

| # | Rule | Source |
|---|---|---|
| R1 | **Storage:** one figure per product, `lasso_url_details.clicks` (bigint, default 0, index `idx_ud_clicks`); no click log, no dates, no post or location | class-url-details.php 84-100 |
| R2 | **Writer, the only one:** cron `lasso_cron_sync_clicks` (daily; registered only with an active licence and no forced scan running) GETs `LASSO_LINK/clicks?<AES-encrypted {url: site_url, ver: Ymd}>` with Lasso's licence headers; non-200 or an empty list → nothing changes | class-lasso-cron.php 138, 182, 271-295, 5833-5859; lasso-constant.php 86, 786-788 |
| R3 | **Apply:** `UPDATE … SET clicks = 0` for every row, then `INSERT (lasso_id, clicks) … ON DUPLICATE KEY UPDATE`, so a product the cloud omits drops to 0 | class-lasso-cron.php 5861-5902 |
| R4 | **The period** is the cloud's; the plugin's own texts call it 30 days ("Total clicks in the last 30 days.", "clicks in 30 days", "Clicks (last 30 days, synced from the Lasso API)"). The cloud's query is UNVERIFIED (not in the plugin) | url-links.php 40-41; request-review.php 13-20 |
| R5 | **The front-end script:** in `wp_footer` (not admin, not Elementor preview, filter `lasso_event_tracking`), printed when tracking is on (R14) or GA click tracking is on with an ID (C3): a loader tries two sources with a 2.5 s timeout each — the cloud script (`js_domain` option, default `https://js.codedrink.com/snippet.min.js`, refreshed daily from `lasso.link/api/js-domain`) and the site's own copy (`/?<md5(domain)>=1&ver=…` or `/js/snippet.min.js`, serving `admin/assets/js/connect-snippet.min.js`, refreshed daily from `lasso.link/api/snippet/performance`); the local copy goes first when it knows the `/js/e` proxy; then `LSAFFEvents.init({lsid, pid, ipa, performance, matching, mobile_deep_linking, international_click})` | class-hook.php 1633-1797; class-hook-frontend.php 50; class-redirect.php 31-86; class-lasso-cron.php 5805-5828, 6943-6970; class-helper.php 6853-6939 |
| R6 | **What it listens to:** one `click` listener on `document` (capture) for any `<a>` (from the target, its closest `a`, or the composed path); every link is sent, not only Lasso's; no `auxclick`; failures are swallowed (Sentry) | connect-snippet.min.js `resolveClickAnchor`, `lassoClickTracking` |
| R7 | **Event type:** `data-lasso-box-trackable="true"` (every display link Lasso prints: buttons, title, image) → "Lasso Display Secondary Click" with class `lasso-button-2`, else "Lasso Display Primary Click"; `data-lasso-name` without it (text links) → "Lasso Link Click"; else "Internal Click" (same origin) or "External Click" | connect-snippet `getEventName`; class-lasso-url.php 214-287; class-lasso-shortcode.php 496 |
| R8 | **Payload** (POST, `text/plain` JSON `{n:"click", u, d, r, w, h, p}`) to `<apiHost>/js/e`: `lasso_title`, `lasso_href` (with a generated `LCID_xxxxxxxxxx` sub-ID added for known networks, or `lcid=` on own-domain links), `lasso_text`, `lasso_badge`, `raw_href`, `lid` (`data-lasso-lid`), `lasso_lid` (`data-lasso-id`, the location), `tag`, `class`, `hierarchy`, `cx`, `cy`, `sw`, `sh`, `type`, `lsid` (cookie `lsid`, one day), `pid`, `ipa`, `gclid`, `d`, `pt`, `js_*`; not sent on localhost; the href carries the sub-ID for 500 ms | connect-snippet `sendClickEvent`, `sendEvent`, `addLassoClickIdToSubIDUrlArgument`, `init` |
| R9 | **Bots:** the script carries a bot list and an `isBot()` but never calls it; any filtering is the cloud's (UNVERIFIED) | connect-snippet `isBot`, `lassoEventsLoad` |
| R10 | **Dashboard Clicks column:** header "Clicks" after "Locations", sortable `clicks` (`data-order-init="desc"`), no tooltip; cell `IFNULL(ud.clicks,0)` in bold; sort `IFNULL(ud.clicks,0)` on every list query; allowed in saved views | dashboard.php 120; url-row.php 170-173; view-js.php 369; class-lasso-db.php 503-504, 518-522, 573; pages/class-ajax.php 156, 184; class-dashboard-saved-views.php 129 |
| R11 | **Clicks per location:** `lasso_get_location_clicks` (nonce `lasso_ajax`, `edit_posts`; 400 "A valid Lasso link ID is required.") GETs `LASSO_LINK/clicks/locations?lasso_id=`, caches `lasso_clicks_loc_<id>` 15 min (filter `lasso_location_clicks_cache_ttl`, min 60 s; failures not cached); the tab maps `location_id → clicks` onto rows by `link_id`, 0 when missing | pages/class-ajax.php 2512-2565; view-js.php 2093-2096, 2316-2346, 2394 |
| R12 | **Other readers of the figure:** the review card's "clicks in 30 days" tile (published products, shown from 100); the content-opportunities CSV's `content_link_clicks` and its "Min link clicks" filter (sum over a post's linked products) | class-helper.php 6324-6356; class-enum.php 32; class-content-opportunities-csv-export.php 223-229, 374-378 |
| R13 | **No export of clicks** from the Dashboard; no header counter shows clicks (the header's Analytics tab links to `app.getlasso.co/analytics/`) | header-new.php 167-169; dashboard.php |
| R14 | **Setting:** General → "Advanced Click Tracking" / "Send click tracking data to Analytics for easy-to-understand analytics." (link to `app.getlasso.co/analytics/`) / toggle **Page and Link-Level Tracking** (`performance_event_tracking`, default true; tooltip "When enabled, Lasso can help you understand what pages and links drive conversions on your site."); hidden on Lasso's start-up plan, where tracking is forced on; also forced on for a lapsed licence unless stopped (R16); saving it on clears `stop_lasso_analytics` | general.php 209-225; class-setting.php 1003-1016; settings-general/class-ajax.php 453-455; lasso-constant.php 131 |
| R15 | **Beacon proxy:** `POST /js/e` (or `?lasso_js_e=1`) on the site forwards a JSON body ≤ 16 KB to `https://codedrink.com/js/e` with the visitor's IP (`X-Lasso-Client-IP`), user agent and language; 405 for other methods, 400 for bad bodies, 200 on upstream failure | class-redirect.php 88-166; class-beacon-proxy.php |
| R16 | **Stop Lasso Analytics** (Settings → Uninstall, only with an inactive licence): "Want to keep your Lasso links and displays but stop Lasso's click tracking and analytics?" → modal "Stop Getting Analytics?" (list: Page and link-level tracking, Internationalization, Mobile deep linking, Higher commissions; "Keep Lasso Processing" / "Stop Lasso Analytics") → `lasso_stop_lasso_analytics` (`lasso_settings_save`; 403 "You do not have permission to update settings." / "Stop Lasso Analytics is only available when your license is not active.") turns off tracking, GA clicks, international clicks, deep linking, auto-upgrade and both auto-monetize settings; "Done — Lasso Analytics has been stopped."; reload after 3 s | uninstall.php 38-97; stop-lasso-analytics-confirm.php; settings-general/class-ajax.php 247-299 |
| R17 | **Click-time redirects:** Amazon, `amzn.to` and Genius links go through `https://lasso.to/amazon?url=…&matching=` (with the click's data) and `lasso.link` links are rewritten to `lasso.to`; `data-lasso-link` is stamped on such links at load | connect-snippet `lassoDetectLinks`, `buildLeanRedirectAmazonUrl`, `rewriteLassoLinkHostToLassoTo`, `handleRedirect` |
| R18 | **Server-side sub-ID click:** a cloaked link opened with `?sid=` from another site writes the sid into the network's first sub-ID and POSTs `{n:"click", type:"External Click", …}` to `LASSO_LINK/events/tracking`; the redirect itself counts nothing locally (hook `lasso_before_link_redirect`) | class-redirect.php 286, 416-522; class-affiliates.php 213-254 |
| R19 | **Other script features:** Sentry error reporting, a `scroll` event (defined, never called), a Lasso Lite "realtime ingest" beacon, the `embed.min.js` loader for `.lasso-embed` | connect-snippet `captureError`, `sendScrollDepthEvent`, `maybeSendRealtimeIngestBeacon`, `lassoLoadEmbedJs` |
| R20 | **Admin telemetry:** Segment (`cdn.segment.com`, key `LASSO_SEGMENT_ANALYTIC_ID`, `identify` with the admin's name and e-mail, `ga_connected`), PostHog (`ph.getlasso.co`), Sentry DSN, LaunchDarkly flags (`lasso.link/api/launch-darkly/flags`); General → Optional Settings → "Segment Analytics" | header-new.php 98-134; lasso-constant.php 89-99, 790-805; class-product-analytics.php 60; class-launch-darkly.php 59; general.php 317-332 |

**Classification:** R1-R4, R11, R15-R20 are **C — never (Lasso cloud)**: no figure Lasso shows can be reproduced
without Lasso's servers. What Affibloom builds is the visible result (R5-R7, R10-R14 as screens and behaviour) with its
own local counting.

### Decisions (Clicks)

Settled from Lasso and the records:

- **D1 Count locally.** Lasso's numbers exist only in its cloud (R2-R4, R11). Affibloom counts on the site, which the
  records already decided (ADR-005, Task 33); the figures shown keep Lasso's places and period.
- **D2 Storage** (ADR-005, extended): `{prefix}affibloom_clicks (day, product_id, post_id, location_id, button,
  clicks)`, daily totals, kept 400 days. `location_id` is new: the Locations tab's per-location clicks (R11, Locations
  D25) need it; it is F21's location ID from `data-affibloom-location`, else Lasso's `data-lasso-id`, else 0. ADR-005
  gains the column (it is still "Proposed").
- **D3 The script** is Affibloom's `clicks.js`: one capture listener for `click` and `auxclick` (middle button), on
  `a[data-affibloom-id], a[data-lasso-lid]` only (R6 sends every link to the cloud; counting other links would count
  nothing a product owns), `navigator.sendBeacon` to `POST /affibloom/v1/clicks`, no cookie, no sub-ID rewriting, no
  redirect; printed only on pages with a product element; identical for every visitor (page caching safe).
- **D4 Buttons:** `1`, `2`, `3`-`n` (F29), `title`, `image`, `link` (R7's distinctions, plus F29's). Primary/secondary
  are kept apart so C3's event names follow from the same attribute.
- **D5 Bot filter** on the server (R9 leaves it to the cloud): empty user agent or the Phase 7 pattern.
- **D6 Repeat guard** 30 seconds by a salted hash in a transient (Task 33's plan); nothing about the visitor is kept.
- **D7 The setting** keeps Lasso's section, label and default: "Advanced Click Tracking" / **Page and Link-Level
  Tracking**, key `click_tracking` (from `performance_event_tracking`); the help text without Lasso's cloud: "Count
  clicks on product links and boxes, shown on the Dashboard and each product's Locations tab." and the tooltip "When
  enabled, you can see which pages and links your readers click." Lasso's plan rules (R14) have no counterpart.
- **D8 The period** is a fixed 30 days (U5 rejected), the current UTC day included.
- **D9 Products:** a click is counted for a published product (trash and retired excluded); a click carrying a
  retired product's ID that was repointed is counted for the target (F1/F21 repoint).
- **D10 Not built (C — never):** R15 proxy, R16 Stop Lasso Analytics (it stops Lasso's cloud; Affibloom's toggle D7
  covers the local meaning), R17 lean redirects, R18's cloud call, R19, R20, the header's Analytics link (deviation 52).
- **D11 The review card** (R12) is Lasso's marketing ask: not built (deviation 52's rule for Lasso self-promotion).
  The content-opportunities CSV's click column is the opportunities feature's (section "Scope that needs a home").

### Needs the user (Clicks)

- **N1 Lasso's 2,839 clicks.** They are the cloud's 30-day snapshot at the last sync, not a history (R1-R4). Options:
  (a) import each product's figure as one row dated the day of production's last Lasso sync (`post 0`, button
  `lasso`), so the Dashboard matches Lasso at cutover and the figures age out after 30 days; (b) start at 0.
  **Recommendation (a)**, run in Task 26 against production's values at cutover (Task 42); this copy's numbers are
  stale. Earlier plans' "copy the table" is wrong (Corrections).
- **N2 Visitor bot traffic** Lasso's cloud may have filtered differently (UNVERIFIED), so Affibloom's numbers will not
  equal Lasso's after cutover. **Recommendation:** accept; say so in the cutover notes.

### Edge cases (Clicks)

1. The route is public: unknown or non-numeric IDs, a missing post, a post that is not public → ignored, 204.
2. Bodies over 1 KB or not JSON → 204, nothing stored.
3. Two clicks in the same second from two visitors → both counted (the upsert is atomic).
4. Logged-in users are counted like any visitor (Lasso's script does not skip them); post previews are not public
   posts and are not counted.
5. A product deleted for good: its rows are removed with it (F1 delete).
6. Day boundaries use UTC; the 30-day sum covers today and the 29 days before.
7. `sendBeacon` missing → `fetch` with `keepalive`; both missing → nothing, the link still opens.

## Locations (where used) — sweep of Lasso 369, 2026-11-14

Status: written 2026-11-14 from the link index sweep (F21; area 2 of the dashboard completion plan). Gate 4 for Tasks
36A-36E: waiting. This section owns AC2 and AC3 and adds AC20-AC26 (AC numbers 4-19 are left to the clicks half).
The index it reads is F21's.

### Acceptance criteria (Locations)

- **AC2** (kept) The Locations tab lists the posts that use the product. (36B)
- **AC3** (kept; corrected 2026-11-14: Lasso asks for the count *before* any dialog — a product in use opens "Hold Up",
  not "Delete This Link?" — so the spec's path changes in 36E) A product in use cannot be deleted. (36E)
- **AC20** The Dashboard's **Locations** column shows the product's count — its tied rows plus the group and table
  displays that show it, hosts in the trash excluded — as a link to its Locations tab; it sorts (first click
  descending); a clock "Processing. Please check back in 24 hours." shows instead while the first full index has not
  finished. (36A, L1-L3)
- **AC21** The product page's **Locations `<n>`** tab: columns Content, Link with Context, Type, Clicks with Lasso's
  tooltips; ten host posts a page; a post with one location is one row, with several an expandable "(N locations)"
  row; Content sorts; search finds by post title or link text; with none, "Add This Link Anywhere On Your Website".
  (36B, L4-L9, L12)
- **AC22** Clicking a row's link text or type icon opens the preview with the link in its paragraph (a display: the
  display drawn). (36C, L10)
- **AC23** The Clicks column shows that location's clicks in the last 30 days (0 when none). (36C, L11)
- **AC24** The filters panel's **Placements** (Any placement, In content, Not in content) narrows the list with the
  count's rule, counts as a filter and is kept in saved views. (36D, L13)
- **AC25** In use: the row's select box is disabled with "You can't delete a URL if it has active links or
  displays"; a bulk delete with any product in use deletes nothing and says so; the product page's Delete This Link
  shows "Hold Up" with the posts and only **Keep This Link**; `DELETE` answers 409 `affibloom_in_use`. (36E, L14-L16)
- **AC26** Drafts, scheduled, pending and private posts are listed with their status and count as use (decision 6).
  (36B)

### Evidence (Locations)

All read: `admin/views/dashboard/url-links.php`, `header.php` (121-145 the count, 259-300 the tabs, 320-326 search);
`admin/views/rows/link-display-row.php`, `link-display-post-group-row.php`, `link-row.php`, `content-link-row.php`,
`url-row.php` (74-87, 160-168); `admin/views/modals/link-preview.php`, `url-delete-reject.php`;
`admin/views/education/locations.php`; `admin/views/dashboard/dashboard.php` 113-205; `admin/views/dashboard/url-details.php`
2083-2396; `admin/assets/js/view-js.php` 32-41, 1719-1840, 1936-2140, 2316-2447, 2584-2585, 2740-2788;
`pages/class-ajax.php` 296-311, 584-848, 1941-1981, 2518-2565; `pages/url-details/class-ajax.php` 294-505;
`models/class-link-locations.php` 113-289; `classes/class-lasso-db.php` 364-389, 1087-1092; `classes/class-html-helper.php`
622-659; `classes/class-helper.php` 1515-1596; `classes/class-dashboard-saved-views.php` 79-99, 349-356.

**This copy, 2026-11-14:** 200 products have tied rows (921 rows in 49 hosts); 8 more products are reached only
through 2 table displays (map); 63 published products have no location; no host is trashed; 2 drafts hold 36 rows.

### What Lasso does (Locations)

| # | Rule | Source |
|---|---|---|
| L1 | **The count:** rows with `post_id` = the product, plus map rows (group/table displays) joined to existing rows, excluding rows whose `detection_slug` is empty or contains `__trashed`; the same sum feeds the Dashboard column, the tab's badge and the delete check (`total_locations_by_lasso_id`, `get_dashboard_location_counts_sql`); with an inactive licence every row shows 0 | class-link-locations.php 113-213; class-lasso-db.php 364-389; pages/class-ajax.php 753-755 |
| L2 | **Dashboard column** "Locations" (sortable `count`, first click desc): the count, linking to `page=url-links&post_id=N` | dashboard.php 119; url-row.php 160-168; class-lasso-db.php 1087-1092 |
| L3 | **The clock** (`fa-clock`, "Processing. Please check back in 24 hours.") replaces the count when the product was published < 24 h ago, its link check has not run and the count is 0 | pages/class-ajax.php 625-641; url-row.php 162-165 |
| L4 | **The tab:** heading and sub-nav Details, **Locations `<n>`**, Opportunities, Variants; right, "Search"; card header Content ("This is the post or page this link was found in.", sortable `post_title`), Link with Context ("Click to see a snippet of this link as it appears on your site."), Type, Clicks ("Total clicks in the last 30 days."); the pager and range line | url-links.php 15-57; header.php 259-326 |
| L5 | **Paging by host post:** 10 hosts a page (pageSize 1-100), hosts ordered by `MAX(<sort>)` (default `post_title` desc), every row of those hosts by `link_id`; the total is the host count | class-link-locations.php 221-289; pages/class-ajax.php 296-311; view-js.php 32-41 |
| L6 | **Grouping:** one location → one row; several → a parent row (toggle "Toggle locations for this post", title, "(N locations)", permalink, "Expand to view each location"), collapsed, with nested rows that hide the Content column; a row with no valid host stands alone | view-js.php 2378-2447; link-display-post-group-row.php |
| L7 | **A row:** Content = host title (to its editor, new tab) and permalink; Link with Context = the anchor text (text link), the image URL (image link) or the shortcode text (display); Type = icon and "This is a text link." / "an image link" / "a single link display" / "a group of links display" (grid, list, gallery, table icons) / "a keyword mention"; Clicks | link-display-row.php; class-html-helper.php 622-659 |
| L8 | **Sort:** a sortable header cycles asc → desc (first click asc unless `data-order-init`) | view-js.php 1764-1799 |
| L9 | **Search:** the box sends the term but the server ignores it for this list | pages/class-ajax.php 296-311 |
| L10 | **Preview** (`#link-preview`): text and image links show the stored content's line holding `data-lasso-id="N"`, else the anchor rebuilt as a link; displays are drawn with `do_shortcode`; AAWP as text; links in the preview open in a new tab | pages/class-ajax.php 757-820; class-helper.php 1515-1596; view-js.php 2740-2788 |
| L11 | **Clicks:** `lasso_get_location_clicks` (`lasso_ajax`, `edit_posts`; 400 "A valid Lasso link ID is required.") fetches `LASSO_LINK/clicks/locations?lasso_id=` from Lasso's cloud, cached 15 min — **C — never (Lasso cloud)** | pages/class-ajax.php 2518-2565; view-js.php 2316-2346 |
| L12 | **Empty:** "Add This Link Anywhere On Your Website" / "You can see where this link appears on your website. Lasso will track every location right here so you know where your links are." / "See How To Add Lasso Links to Your Site" (help site) | education/locations.php |
| L13 | **Placements filter:** "Placements": "Any placement", "In content", "Not in content" (`has_placements` / `no_placements`), EXISTS with L1's rule; counts as a filter; saved views key `dashboard_placements` | dashboard.php 174-181; class-dashboard-saved-views.php 79-99, 349-356 |
| L14 | **Dashboard box:** with a count ≠ 0 the select box is disabled with "You can't delete a URL if it has active links or displays"; bulk delete refuses everything when any count ≠ 0 ("Invalid Lasso Post ID!") | url-row.php 74-87; pages/class-ajax.php 1941-1981 |
| L15 | **Product page:** `get_lasso_url_location_count` (nonce `lasso_ajax`, `edit_posts` + `edit_post`; 400 "Invalid Lasso post."; `{status, location_count, error: "Lasso Post ID was not found."}`) → 0: "Delete This Link?"; > 0: "Hold Up" (F1 X8) | url-details/class-ajax.php 299-331; url-details.php 2083-2108 |
| L16 | **Force delete** (F1 X8): `lasso_delete_post_in_table`, `lasso_delete_link_from_all_groups`, `lasso_get_post_ids_by_lasso_id` (hosts of tied rows), `lasso_remove_links_and_shortcodes_from_post` (removes `[lasso … id=N]` and turns the product's public-link hrefs into its target URL in `post_content`, then deletes the rows), `lasso_delete_post` | url-details/class-ajax.php 361-489; class-lasso-cron.php 1657-1696 |

### Decisions (Locations)

- **D20 The count** is L1 over F21's index: tied rows plus displays whose `display_ref` group or table holds the
  product (F21 D2), hosts in the indexed statuses only (trashed hosts have no rows, F21 D6). The licence rule is not
  copied (no licence).
- **D21 The clock** means what Lasso's does, "not known yet": shown for a 0 count while the first full index has not
  finished (F21's state). Affibloom indexes on save, so Lasso's 24-hour window after publishing would show a clock
  over a count that is already right.
- **D22 Pages:** the tab is `admin.php?page=affibloom-product&id=N&tab=locations`, drawn by PHP and redrawn in place
  through `GET /affibloom/v1/products/{id}/locations?page=&order=&search=` (`{ html, total, url }`), as the Groups
  list (F23 D13).
- **D23 Search works** (L9 is a dead box): host title, anchor text and URL.
- **D24 The preview** is built from the index and the stored content: the paragraph holding the location (by Lasso's
  stamp or F21's occurrence), escaped except its links; displays drawn by Affibloom's box. Nothing is written.
- **D25 Clicks per location** are counted locally (F21 D13's `data-affibloom-location`; the clicks half stores them);
  the help link to Lasso's site is left out (deviation 52).
- **D26 Delete:** F1 D34 (the user: block only) — "Hold Up" lists the posts and offers only Keep This Link; the
  routes refuse with 409 `affibloom_in_use` and "You can't delete a URL if it has active links or displays."; L16 is
  not built.
- **D27 Repoint** moves the rows (F21 R60), so a retired product shows 0 and its target the sum.

**Needs the user (Locations):** none beyond F1 D34, already answered.

### Edge cases (Locations)

1. A product shown only by a group display: counted and listed with the display's host (D20).
2. A host with 100+ locations: one group row; the page holds 10 hosts whatever their row counts.
3. A product deleted while its tab is open: the route answers 404 and the tab says so.
4. The index rebuilt while the tab is open: the next page or sort reads the new rows.
