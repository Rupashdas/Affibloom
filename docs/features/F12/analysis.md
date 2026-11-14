# F12 Click counts and where used: analysis

Status: Approved with Task 6 (gate 2), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

**Tasks:** 33–36.

## Depends on

- ADR-005 (daily totals).
- F1, F4, F10.

## Data touched

- Tables `{prefix}affibloom_clicks` (kept 400 days) and `{prefix}affibloom_usage`; transient `affibloom_c_<hash>` (30-second repeat guard).

## Entry points and hooks

- `POST /clicks` (public); `clicks.js`; `save_post`, trash and delete hooks; `wp affibloom usage rebuild`.

## Facts from Phase 1

- In-content links tied to a Lasso record: 676 in 39 posts, not 1,504 (Task 4).
- Lasso lists drafts as locations ("sweet new post"). Decided: drafts, pending, scheduled and private posts count, with their status shown (decision 6).
- Lasso allows deleting a product in use after a warning; Affibloom refuses it (409).
- The Clicks column keeps Lasso's fixed 30 days (U5 rejected).

## Risks

- Abuse of the public route: bot filter, repeat guard, always 204.
- Page caching is fine: counting happens in the browser.

## Locations (where used) — 2026-11-14

**Tasks:** 36A (Dashboard Locations column, count, sort, clock), 36B (the Locations tab list), 36C (preview and
Clicks column), 36D (Placements filter), 36E (delete blocked when in use). They replace Task 36 and follow F21's 35A-35I.
The plan's `{prefix}affibloom_usage` table above is replaced by F21's `{prefix}affibloom_link_locations`
(corrected 2026-11-14); `usage rebuild` is `wp affibloom links rebuild`.

**Depends on:** F21 (index, D2 `display_ref`, D6 statuses, D13 location attribute), F23 (group members), F25 (table
members), F19 (Dashboard list, filters panel, saved views), F1 17A/17B (delete routes), the clicks half (per-location
clicks).

**Request paths:**

```
GET /affibloom/v1/dashboard/products?…&order=count&placements=has|no            (36A, 36D)
  → DashboardController → ProductService::page() → LocationCounts::for_products( ids )
       LinkLocationsTable::counts_for( ids ): tied rows + display_ref rows via F23/F25 members, indexed hosts only
GET admin.php?page=affibloom-product&id=N&tab=locations                          (36B)
GET /affibloom/v1/products/{id}/locations?page=&order=post_title&dir=&search=  → { html, total, hosts, url }
  → LocationsController (Capabilities::PRODUCTS) → LocationService::page( id, 10 hosts )
GET /affibloom/v1/locations/{row}/preview → { html }                            (36C)
DELETE /affibloom/v1/products/{id}, DELETE /affibloom/v1/products {ids}          (36E)
  → ProductService::delete() → LocationCounts > 0 → ProductInUse → 409 affibloom_in_use
GET /affibloom/v1/products/{id}/locations/hosts → the Hold Up list               (36E)
```

**Layers:** Domain `LinkIndex\LocationPage`, `LinkIndex\HostGroup`; Application `Locations\LocationService`,
`Locations\LocationCounts`, `Products\ProductInUse`; Infrastructure `Tables\LinkLocationsTable` (counts, pages,
hosts); Interface `Rest\LocationsController`, `ProductScreen` (tab), templates `product-locations.php`, partials
`location-row.php`, `location-group-row.php`, `location-preview-modal.php`, `hold-up-modal.php`, script
`product-locations.js`.

**Tests:** unit `LocationCounts` (direct + display rows), host paging; integration `DeleteInUseTest` (409, nothing
trashed); E2E F12 AC2, AC3 (path corrected), AC20-AC26.

## Clicks — 2026-11-14

**Tasks:** 33A (click script and `POST /affibloom/v1/clicks`, storage), 33B (bot filter and repeat guard), 33C
(retention on `affibloom_daily`), 33D (Settings → General: Page and Link-Level Tracking), 34A (Dashboard Clicks column
and sort), 34B (Clicks (30 days) tile), 34C (clicks per location on the Locations tab), 34D (import Lasso's last synced
figures, N1). They replace Phase 7's Tasks 33-34.

### Depends on

- ADR-005 (daily totals; gains `location_id`, D2), ADR-007, ADR-008.
- F1/F19: the Dashboard list, its sort and saved views (13C-13E), the product row partial. F4/F16: the box HTML and
  `data-affibloom-btn` on its links (Phase 3). F29: further buttons. F21: the location IDs (`data-affibloom-location`,
  F21 D13) and repoint (R60). F12 Locations: the tab 36B (34C fills its Clicks column). Task 26: the import (34D).
  Settings General (Task 9) for 33D. C3 builds on 33A's script.

### Data

| Where | What | From Lasso |
|---|---|---|
| Table `{prefix}affibloom_clicks` (`day` date, `product_id`, `post_id`, `location_id` bigint unsigned, `button` varchar(8), `clicks` int unsigned; PK `(day, product_id, post_id, location_id, button)`, KEY `product_day`, KEY `location_day (location_id, day)`) | daily totals, 400 days | `lasso_url_details.clicks` (one 30-day number per product; imported by 34D as one row per product, `post_id 0`, `location_id 0`, `button 'lasso'`, `day` = last sync date) |
| Option `affibloom_settings[click_tracking]` (bool, default true) | counting on/off | `performance_event_tracking` |
| Transient `affibloom_c_<32 hex>` (30 s) | repeat guard | none (Lasso has none) |
| ~~Option `affibloom_lasso_clicks_synced_on` (Y-m-d)~~ (not built: decision E1) | the day stamp used by 34D (dropped) | none; taken from production's last `lasso_cron_sync_clicks` run (cron log or `_transient`/option evidence at cutover) or the export date |

Not mapped (cloud): `js_domain`, `full_js_domain`, `connect-snippet.min.js`, `lasso_clicks_loc_*`, `stop_lasso_analytics`,
`segment_analytics`, `lsid` cookie.

### Entry points and request paths

```
Front end (33A, 33B, 33D)
  ClickScript::maybe_enqueue()  ← BoxAssets::enqueue(), the_content (999) seeing data-affibloom-id / data-lasso-lid
    only when settings click_tracking (or C3's ga_click_tracking) is on
    → assets/front/clicks.js + inline window.affibloomClicks = { endpoint, post, count: bool, ga: {...C3} }
  click / auxclick(button 1) on a[data-affibloom-id], a[data-lasso-lid]  (capture)
    → sendBeacon( /wp-json/affibloom/v1/clicks, { product, lasso, location, lasso_location, post, button } )

POST /affibloom/v1/clicks   public, no nonce; args integers ≥ 0, button enum 1..9|title|image|link   → 204 always
  → ClicksController::record() → ClickTracker::record( ClickInput )
       settings off → stop;  BotFilter::is_bot( UA ) → stop                         (33B)
       product = id, or LassoIdLookup::product_for( lasso ); RepointLookup::target_for( product )
       ProductRepository::get() published, PostDirectory::is_public( post ) else stop
       location = location, or LocationLookup::for_lasso_location( lasso_location ), else 0
       RepeatGuard::first_in( sha256( ip|ua|product|post|button|day|salt ), 30 )   (33B)
       ClickStore::increment( day, product, post, location, button )

affibloom_daily (03:00) → ClickRetention::run() → ClickStore::delete_before( today − 400 d )           (33C)

GET admin.php?page=affibloom-settings&tab=general   section "Advanced Click Tracking"            (33D)
  PATCH /affibloom/v1/settings { click_tracking }   (the existing settings route)

GET admin.php?page=affibloom[&orderby=clicks&order=desc|asc]; GET /affibloom/v1/dashboard/products?orderby=clicks   (34A)
  → DashboardScreen / DashboardController → ProductService::page( … sort clicks ) →
       PostTypeProductRepository::page(): LEFT JOIN (SELECT product_id, SUM(clicks) c FROM clicks WHERE day >= %s
       GROUP BY product_id) ORDER BY IFNULL(c,0) <dir>, ID DESC
  → ClickReport::totals_for( page IDs, 30 )   one query; the row partial prints the bold number
  tile (34B): ClickReport::total_since( 30 ) over published products

GET /affibloom/v1/products/{id}/locations?…  (36B)  gains `clicks` per location:                    (34C)
  ClickReport::by_location( product, location IDs, 30 )

wp affibloom import lasso --step=clicks  (NOT BUILT, decision E1: start from zero)   reads lasso_url_details.clicks, writes 34D's rows,  (34D)
  idempotent (replaces the 'lasso' rows), refuses when the Lasso table is missing
```

### Layers

- **Domain** `Clicks\{ClickStore (interface: increment, totals_since, total_since, by_location, delete_before,
  replace_imported), BotFilter, Button (enum of allowed values)}`; `Product\LassoIdLookup` (interface; `LassoIdMap`
  implements it).
- **Application** `Clicks\{ClickTracker, ClickInput, ClickReport, ClickRetention, PostDirectory, RepeatGuard,
  LassoClickImport}`.
- **Infrastructure** `Tables\ClickTable` (Migrator schema bump), `WordPress\{WpPostDirectory, TransientRepeatGuard}`,
  `Lasso\LassoClickReader` (reads `wp_lasso_url_details`, SELECT only).
- **Interface** `Rest\ClicksController`, `Front\ClickScript`, `assets/front/clicks.js`; `Admin\DashboardScreen` and
  `Rest\DashboardController` (sort and column), `templates/admin/partials/product-row.php`, the tile partial; settings
  General template (section); `Cli\ImportCommand` step.

### Risks

- **Public route abuse:** anyone can POST. Bot filter, 30 s guard, product and post must be real and public, body cap,
  always 204. A determined script can still inflate counts (Lasso's cloud had the same exposure); accepted.
- **LiteSpeed "delay JS":** a delayed `clicks.js` misses clicks before the first interaction. Task 37 excludes it.
- **Ad blockers** block paths containing "click"/"track"? `/wp-json/affibloom/v1/clicks` may be listed by some
  filters; measure in 33A's hand check; rename the route only if needed (a deviation).
- **Numbers differ from Lasso's** after cutover (N2).
- **Sort cost:** the 30-day SUM join over a few thousand rows per day is cheap at 263 products; `product_day` index.

### Security

- No IP or user agent stored; the guard's hash is salted (`wp_salt('nonce')`) and lives 30 s.
- Inputs `absint`; `button` from an enum; the body is never echoed; no nonce (public, idempotent counter), no cookies.
- Admin reads behind `Capabilities::PRODUCTS`; the import is WP-CLI only and SELECTs Lasso's table.
- Nothing calls Lasso, codedrink.com or lasso.to (AC18).

### Tests

- Unit: `BotFilterTest` (empty UA, bot UAs, browsers), `ClickTrackerTest` (each stop, repoint, location fallback),
  `ButtonTest`, `ClickReportTest` (30-day edge), `LassoClickImportTest` (idempotent, date stamp).
- Integration: `ClicksRouteTest` (204 always; counted, repeat, unknown, bot, unpublished post, setting off),
  `ClickTableTest` (upsert, retention), `DashboardClicksSortTest`.
- E2E `acceptance/F12.spec.ts`: AC1, AC4 (each element, middle-click), AC5, AC7 (repeat), AC9 (toggle), AC10-AC11
  (column, sort), AC12 (tile), AC13 (per location), AC14, AC16 (route blocked), AC18 (no third-party request: route
  every request and assert hosts), AC19 (no script on a page without products). AC6, AC8, AC15, AC17 are integration or
  CLI checks named in the spec as `test.skip` with the pointer.

## Security

- No IP address or other personal data stored; IDs validated.
