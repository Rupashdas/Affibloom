# F22 Opportunities: analysis

Status: gate 2 written 2026-11-14 with `requirements.md`. Gate 4 for Tasks O1-O10: waiting (O10 waits for N1).

**Tasks:** O1 (the Opportunities nav and Link Opportunities), O2 (Domains and Detected Links), O3 (Content), O4 (a
post's links), O5 (the product page's Opportunities tab and Detect Opportunities switch), O6 (the General setting and
its override), O7 (Dismiss and Undo), O8 (the Dashboard: tile, filter tab, lightbulb), O9 (Export CSV), O10 (Monetize
at render time; form set by N1). They follow F21's 35A-35I and F12's 36C in Phase 7.

## Depends on

- F21 35A-35C (the index table, the rebuild, indexing on save) and 35H (the render filter, for O10); F21 D10 is built
  here (O7) on 35C's replace-per-host code.
- F12 36C (`GET /affibloom/v1/locations/{row}/preview`); F12's click store for D17 (O3, O9; hidden until it exists).
- F1 14A-14B (the product page's tabs and display card placeholders), 15A (`PATCH /products/{id}`).
- F19 13C-13E (the Dashboard list route, the tiles, the Alert column placeholder).
- F9 (Link Defaults, the override dialog of R47 there). F18 (Add a Link, quick-detail) for O10. F23 D10 (the product
  search route) for O10.
- ADR-001, ADR-007 (PHP-rendered screens, plain scripts, `wp.apiFetch`), ADR-008 (Aurora look, Lasso's arrangement).

## Data

| Where | What | Lasso's key |
|---|---|---|
| `{prefix}affibloom_link_locations` (F21) | read: `id`, `host_id`, `host_url`, `source`, `occurrence`, `product_id`, `link_type`, `display_type`, `anchor_text`, `url`, `original_url`, `domain`, `lasso_location_id`; read and written: `is_ignored` (O7); under N1-A written: `assigned_product_id` (O10, new column, int, default 0, kept across rescans like `is_ignored`) | `lasso_link_locations` (`detection_id`, `detection_slug`, `post_id`, `link_slug`, `original_link_slug`, `link_slug_domain`, `is_ignored`) |
| Product meta `_affibloom_domain` | the product URL's host without `www.` (D5), written on save, filled by the upgrade step | `lasso_url_details.base_domain` |
| Product meta `_affibloom_detect_opportunities` | '1' / '0' (D9) | `lasso_url_details.is_opportunity` |
| Settings key `detect_opportunities` (bool, default true) in `affibloom_settings` | Link Defaults (F9) | `lasso_settings.detect_opportunities` |
| `affibloom_settings['education_hidden']` (settled 2026-11-14; was option `affibloom_hidden_intros`, F38 owns the boxes) | list of `links`, `domains`, `content` (D8) | `lasso_settings[link-opportunities|content-opportunities|domain-opportunities]` = 'hide' |
| none | Programs, keywords (D14, D15) | `lasso_affiliate_programs`, `lasso_tracked_keywords`, `lasso_keyword_locations` |

Indexes: F21's `(product_id, is_ignored, domain)` serves D4; `(host_id)` serves O4 and the CSV; meta queries on
`_affibloom_domain` run over ≤ 300 products and are read once per request into a domain → product IDs map.

## Entry points and request paths

```
GET admin.php?page=affibloom-opportunities[&tab=links][&product=N][&s=…&orderby=…&order=…&paged=N]        (O1)
  → AdminMenu (Capabilities::PRODUCTS) → OpportunitiesScreen::render()
  → OpportunityService::links( OpportunityQuery ) → OpportunityRepository::link_page()
       (D4: index rows ⋈ products by domain, Detect on, published; one row per location × product)
  ← templates/admin/opportunities.php (header tab, sub-tabs, "N Found", search, intro box, table, pager)
GET /affibloom/v1/opportunities/links?search=&orderby=&order=&page=&product=      → { html, url, total }      (O1)
POST /affibloom/v1/opportunities/intros/{box}                                     → 204                       (O1)

GET admin.php?page=affibloom-opportunities&tab=domains                       Domains            (O2)
GET admin.php?page=affibloom-opportunities&tab=domain&domain=<d>             Detected Links › d (O2)
GET /affibloom/v1/opportunities/domains?search=&orderby=&order=&page=        → { html, url, total }
GET /affibloom/v1/opportunities/domains/{domain}/links?search=&orderby=&order=&page=  → { html, url, total }
  → OpportunityService::domains() / domain_links() → OpportunityRepository (R17-R20; D16's quirk)

GET admin.php?page=affibloom-opportunities&tab=content[&show=&type=&category=&min_clicks=]   (O3)
GET /affibloom/v1/opportunities/content?…                                    → { html, url, total, export_count }
  → OpportunityService::content( ContentFilter ) → OpportunityRepository::content_page(), export_count()

GET admin.php?page=affibloom-opportunities&tab=post&post=<id>                Content › post      (O4)
GET /affibloom/v1/opportunities/posts/{id}/links?…                           → { html, url, total }

GET admin.php?page=affibloom-product&id=N&tab=opportunities                  the product's tab   (O5)
GET /affibloom/v1/opportunities/links?product=N&context=product              → { html, url, total }
PATCH /affibloom/v1/products/{id} { …, detect_opportunities: bool }          → ProductResource::full() gains
                                                                               detect_opportunities, counts.opportunities
POST /affibloom/v1/settings { …, detect_opportunities }                      (O6, F9's SettingsController)
POST /affibloom/v1/settings/override { key: "detect_opportunities", value }  → 200 { updated }    (O6, F9's route)
  → ProductService::set_detect_opportunities_for_all( bool )

POST   /affibloom/v1/opportunities/locations/{row}/dismiss                    → 200 { count }      (O7)
DELETE /affibloom/v1/opportunities/locations/{row}/dismiss                    → 200 (Undo)
  404 affibloom_location_not_found · 403 affibloom_forbidden (no edit_post on the host)
  → OpportunityService::dismiss() / restore() → LinkLocationRepository::set_ignored()

GET /affibloom/v1/dashboard/products?…&filter=opportunities&orderby=opportunity_count     (O8, F19's route)
  → DashboardController (F19) → OpportunityCounter::products_with_opportunities(), per_product( ids, Mode )
     tile total in the list response `totals.opportunities`

GET /affibloom/v1/opportunities/content/export?…&_wpnonce=                   → 200 text/csv (stream)  (O9)
  400 { code: affibloom_export_invalid, message } · 500 affibloom_export_stream
  → ContentExportController → ContentExport::stream( ContentFilter, sort, handle ) → OpportunityRepository::export_batch()

PUT    /affibloom/v1/opportunities/locations/{row}/assignment { product }     → 200 { html: row }   (O10, N1-A)
DELETE /affibloom/v1/opportunities/locations/{row}/assignment                 → 200 { html: row }
  409 affibloom_location_tied (a row tied by its own URL, N3) · 404 · 403
  → OpportunityService::assign() / unassign() → LinkLocationRepository::set_assignment()
  render: F21 Front\LinkAttributesFilter reads assigned_product_id before its own resolver (35H)
```

## Layers

- **Domain** `Opportunity\Opportunity` (location, host, product suggestion, type icon data), `Opportunity\DomainCount`,
  `Opportunity\ContentRow`, `Opportunity\OpportunityQuery` (search, sort allow-list per list, page; D3, D6),
  `Opportunity\ContentFilter` (R25 normalisation), `Opportunity\SiteHost` (D-edge 5), `Opportunity\AmazonDomains`
  (Lasso's 21; pure), `Opportunity\BaseDomain` (R39's rule; pure), `Opportunity\LinkTypeIcon` (R12; pure),
  `Opportunity\CsvRow` (R60-R61: columns, normalising, the formula guard; pure), `Opportunity\OpportunityRepository`
  (interface).
- **Application** `Opportunities\OpportunityService` (links, domains, domain links, content, post links, dismiss,
  restore, assign, unassign), `Opportunities\OpportunityCounter` (D13: tile, per product by hosts or rows),
  `Opportunities\ContentExport`, `Opportunities\IntroBoxes`; `ProductService` writes the domain and the flag on save.
- **Infrastructure** `Tables\OpportunityQueries` (the `$wpdb` repository over F21's table and product meta),
  `WordPress\OptionHiddenIntros`, `WordPress\ProductDomainBackfill` (the upgrade step); `PostTypeProductRepository`
  gains `_affibloom_domain`, `_affibloom_detect_opportunities`; F21's `LinkLocationsTable` gains `set_ignored()`,
  `set_assignment()` and keeps both on replace.
- **Interface** `Admin\OpportunitiesScreen`, the header's Opportunities tab, `Admin\ProductScreen` (the tab), the
  display card's switch; `Rest\OpportunitiesController`, `Rest\ContentExportController`; `DashboardController` and
  `ProductsController` additions; templates `opportunities.php`, partials `opportunity-subnav.php`,
  `opportunity-intro.php`, `opportunity-link-row.php`, `domain-row.php`, `domain-link-row.php`,
  `content-row.php`, `post-link-row.php`, `dismiss-modal.php`, `monetize-modal.php`; scripts `opportunities.js`
  (lists in place, intro close, dismiss, monetize), the product page's in `product.js`.

## Risks

- **The location × product join** multiplies rows (233 Amazon products share amazon.com, excluded; other domains have
  ≤ 9 here). Counts are `COUNT(*)` of the joined set, as Lasso's; pages stay cheap.
- **Lasso parity of numbers:** this copy gives Links 27 (Lasso 30, D4), tile 9 (Lasso 12, D13), Domains 98, Content
  178/169, export 2,061. O1-O9's acceptance tests assert these on a fixture built from Lasso's rows.
- **Render-time assignment (N1-A)** is invisible in the editor; "Edit in post" and the row's green state are the
  editor's cues. The identity rule (F21 D4) decides when an assignment survives an edit: an edited anchor URL is a new
  row, and the assignment drops — the row is an opportunity again, which is the honest result.
- **Context lines** without Lasso's stamp: the preview and the CSV's `anchor_context` find the anchor by F21's
  occurrence when no `data-lasso-id` exists (F12 D24).

## Security

- `Capabilities::PRODUCTS` on the screen and every route; row actions also check `edit_post` on the host (D20).
- Every id `absint`; a location row is used only after it is read from the table; domains are compared as stored,
  never concatenated (Lasso's R45 concatenation is not repeated); search through `esc_like` and `prepare`.
- CSV: R61's guard on every cell; filename fixed; `nocache_headers()`; the nonce is the REST nonce.
- No post content, post meta of hosts or other plugins' data is written (the standing decision; N1).

## Tests

- **Unit:** `BaseDomain`, `AmazonDomains`, `LinkTypeIcon`, `OpportunityQuery` (allow-lists, defaults, page clamp),
  `ContentFilter`, `CsvRow` (normalising, guard), `OpportunityCounter` (hosts vs rows).
- **Integration** (WordPress test suite, F21's table with fixture rows copied from this site's Mizuno and Amazon
  rows): D4's rule (27 rows; deleted product, Detect off, Amazon domain, own host excluded), Domains 98-style grouping,
  Content filters, export rows and order, dismiss surviving a re-index (D10), assignment kept and dropped.
- **E2E acceptance** `tests/E2E/acceptance/F22.spec.ts`: AC1-AC2 (O1), AC3 (O2), AC4 (O3, O4), AC5-AC6 (O5), AC7
  (O6), AC8 (O7), AC9 (O8), AC10 (O9), AC11 (O10, including a byte comparison of `post_content` before and after).
