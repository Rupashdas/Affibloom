# F19 Dashboard list: analysis

Status: **draft, gate 2**, 2026-11-13, written with the requirements and on the code Tasks 8-12E built.

**Tasks:** 13A, 13B (Phase 2). The screen, its header, band, card and toast exist (Tasks 8, 9A); the product
repository, `Product` and `AffiliateUrl` exist (Tasks 11A-12E); `ProductService` is already built once in
`Plugin::modules()` for the REST controller.

## 1. How Lasso does it

```
GET edit.php?post_type=lasso-urls (dashboard.php)
  prints the shell: title, summary stats, search box, saved-view bar, Filters panel,
  the table header, an empty #report-content, the pager footer
  → view-js.php runs get_data_via_ajax( page, limit, order_by, order_type )
       POST admin-ajax.php action=lasso_report_urls
         Ajax::lasso_report_urls
           search  = trim, spaces → '%', wrapped in %…%
           filters = Dashboard_Saved_Views::build_list_extra_where_sql( $post )
           order   = post_modified | detection_date | count | clicks   (default post_modified DESC)
           limit   = 10 | 25 | 50                                      (default 10)
           Lasso_DB::get_dashboard_query_without_filter
             1) SELECT p.ID … WHERE post_type AND post_status='publish' <search> <filters>
                ORDER BY <order> LIMIT/OFFSET          the page's IDs
             2) SELECT the row data for exactly those IDs, joined to locations,
                opportunities, url_issues and url_details (clicks)
           Lasso_DB::get_dashboard_link_count( search )  the total
         ← JSON { data: rows, post: { order_by, order_type }, total: { total, … } }
       JS builds a row from url-row.php per record, fills #report-content,
       simplePagination draws the page links, the range line reads "1-10 of 263"
```

The page number is never in the URL (a `#page-N` hash, then `localStorage.lasso_pagination`); the page size is in
`sessionStorage`; the search **is** in the URL (`?search=`).

## 2. What Affibloom builds

Decision D1: the list is rendered by PHP on each request, so the search, the page and the page size are query
arguments and the screen has no JavaScript of its own. Quick edit (Task 16) adds the only JavaScript this screen
needs, as Lasso has it.

```
GET admin.php?page=affibloom[&s=<term>][&paged=<n>][&per_page=10|25|50]
  → DashboardScreen::render()
       PerPage::from_request( $_GET )          user meta affibloom_per_page, 10 by default
       SearchTerm::from_input( $_GET['s'] )    Lasso's cleaning, the wildcards, the shapes   (13B)
       ProductService::list( $term, $page, $per_page )
         ProductRepository::page( $term, $page, $per_page ): ProductPage
           PostTypeProductRepository: WP_Query( post_type, publish, paged, posts_per_page,
             orderby=modified, order=DESC, s=<term> )
             + a posts_search filter for this query only: Lasso's OR clause   (13B)
  ← templates/admin/dashboard.php
       partials/product-row.php per product, partials/pager.php under the table
```

### 2.1 Domain (no WordPress calls)

- **`Domain\Product\ProductPage`** (13A): `items`, `total`, `page`, `per_page`; `pages()`, `first()`, `last()` for
  the range line, `is_empty()`.
- **`Domain\Product\SearchTerm`** (13B), the search rules with no storage in them:
  - `from_input( string ): self` — Lasso's cleaning: every character that is not `A-Za-z0-9+-.#`, whitespace or
    CJK becomes a space, then trim and collapse.
  - `is_empty(): bool`, `text(): string` (what goes back in the box and the URL).
  - `like(): string` — spaces to `%`, wrapped in `%…%`, for a `LIKE`.
  - `post_id(): ?int` — the term when it is all digits.
  - `asin(): ?string` — the term upper-cased when it matches `^B[A-Z0-9]{8,}$`.
- Unit-testable with Brain Monkey: neither class calls WordPress.

### 2.2 Application

- **`ProductService::list( SearchTerm $term, int $page, int $per_page ): ProductPage`** — one call through to the
  repository. `ProductService::PER_PAGE_DEFAULT = 10` and `PER_PAGE_OPTIONS = [ 10, 25, 50 ]` (Lasso's
  `get_dashboard_page_size_options`), used by the screen and the repository's clamp.

### 2.3 Infrastructure

- **`PostTypeProductRepository::page()`** uses `WP_Query`, which gives paging, `found_posts` and a primed meta
  cache, so hydrating the page's products costs no extra query. Lasso's own two-step (IDs, then data) exists
  because its row needs four joins; Affibloom's row needs only post meta, which `WP_Query` has already primed.
- The search is added as a **`posts_search` filter registered for this query only** (13B): the query carries
  `affibloom_search`, the filter checks it on the `WP_Query` it is handed and returns Lasso's OR clause instead of
  WordPress's word-by-word title-and-content search. `$wpdb->prepare` builds every arm, so no value reaches SQL
  unprepared and PHPCS needs no exception.

```sql
 AND ( p.post_title LIKE %s
    OR EXISTS ( SELECT 1 FROM wp_postmeta m
                 WHERE m.post_id = p.ID
                   AND m.meta_key IN ( '_affibloom_url', '_affibloom_asin' )
                   AND m.meta_value LIKE %s )
    [ OR p.ID = %d ]                              -- an all-digits term
    [ OR EXISTS ( … meta_key = '_affibloom_asin' AND m.meta_value = %s ) ] )  -- an ASIN-shaped term
```

- `_affibloom_url2` joins the `meta_key IN (…)` list with Task 15, which creates it (requirements D4).

### 2.4 Interface

- **`DashboardScreen`** gains `ProductService $products` and `PerPage $per_page` after `$template`. It reads `s`,
  `paged` and `per_page` from the request, hands the view the `ProductPage`, the term and the per-page choice, and
  nothing else. No capability check: WordPress has already made it (`Capabilities::PRODUCTS`).
- **`Interface\Admin\PerPage`** (13A): `from_request( array $get ): int` — a value in the request that is one of
  the three options is stored in user meta `affibloom_per_page` and used; otherwise the stored value, otherwise 10.
- **Templates:** `admin/dashboard.php` grows the toolbar, the table and the pager; `partials/product-row.php` is
  one row; `partials/pager.php` is the per-page select, the range line and `paginate_links()`.
- **`paginate_links()`** with `end_size = 2`, `mid_size = 1` gives Lasso 370's `1 2 3 … 26 27`, and `prev_text` /
  `next_text` are the Aurora chevrons with screen-reader text, as Lasso's. It prints nothing for a single page,
  which is Lasso's rule too.

## 3. Data touched

| Where | What |
|---|---|
| `wp_posts` | published `affibloom_product` rows, read; `post_modified` is the sort |
| `wp_postmeta` | `_affibloom_url`, `_affibloom_asin` read by the search; the row reads `_affibloom_url` |
| `wp_usermeta` | `affibloom_per_page`, written when the editor picks a page size |

Nothing is written except that one preference. The screen is read-only.

## 4. Entry points and hooks

- `admin.php?page=affibloom` (already registered by `AdminMenu`, Task 8).
- `posts_search`, added and removed around the one query (13B).
- No REST route, no cron, no AJAX.

## 5. Risks

- **A `LIKE '%…%'` search cannot use an index.** At 263 products on this site, and with the `postmeta` arm
  restricted to two meta keys, the cost is a scan of a few hundred rows. Lasso runs the same shape on the same
  data. If a far larger library ever needed it, the answer is a search index, not a different query here.
- **`posts_search` is a global filter.** It is registered immediately before the query and removed immediately
  after, and it checks that the query it is handed carries `affibloom_search`, so no other query can be changed by
  it.
- **Page numbers beyond the last page** give an empty list. The pager still shows the range for the last page, as
  Lasso's range function clamps it; the template does the same clamp.
- **`per_page` from the request is not trusted:** only 10, 25 and 50 are accepted, as Lasso's handler does.

## 6. Security

- `Capabilities::PRODUCTS` (`manage_options`) on the screen, by `AdminMenu`.
- `s`, `paged` and `per_page` are read with `sanitize_text_field( wp_unslash( … ) )` and `absint`; they are a
  read-only list filter, so no nonce (`WordPress.Security.NonceVerification.Recommended` is disabled for those
  lines with that reason, as Task 8 does for the notice).
- Every value in the search SQL goes through `$wpdb->prepare`; `%` and `_` in a term are escaped with
  `$wpdb->esc_like()` before the wildcards are put in, which Lasso does not do.
- Everything the templates print is escaped: `esc_html` for the name, `esc_url` for the link and the page links
  through `wp_kses_post` (`paginate_links()` returns markup).
- The product's destination link opens in a new tab with `rel="noopener noreferrer"`, as Lasso's row does.

## Second pass — 2026-11-14

TASKS
- 13F The list keeps every argument without JavaScript; ?post_id=  (Phase 2, now)        R30; D17, D24; AC15
- 13G The search finds added buttons' links only (14H correction) (Phase 2, now)        D4; AC16
- 13H AC4 and AC10 tests that run on a full library (test-only correction) (Phase 2)     AC4, AC10
- 13I Stable order and counts that follow the list (Phase 2, now)                       R19, R33; D9, D18; AC17, AC18
- 13J Saved views and the panel as Lasso's (Phase 2, now)                               R12-R17; D13-D16; AC19, AC20
- 13K Sort the list by a column header, Alert first (Phase 6, after F11 31A)           R1-R11; D8-D12; AC21-AC23
- 13L The command palette, Ctrl+Shift+L (Phase 2 end, after G4)                         R20-R27; D19-D22; AC24, AC25
- 13M The Dashboard tour (Phase 2 end, after G1 and 13J)                                R28, R29; D23; AC26

DEPENDS ON
- Built: 13A-13E, 16A-16C (guard), 17B (bulk delete), 14H (buttons). F23 G1 (Groups nav), G4 (GET /products?search=).
- F11 30A (open issue rows) and 31A (Alert cell) before 13K. F12 34A and F21 36A add their sort arms after 13K.
- F11 31B (tab), G5 (group), F21 36D (placements), F25 T16 (comparison table) add their argument to the same
  DashboardUrl / list route / SavedView / Reset / no-JS hidden inputs that 13F and 13K generalise.

DATA
| Affibloom | Lasso | Notes |
| user meta affibloom_saved_views (built) + keys orderby, order (13K) | lasso_dashboard_saved_views: order_by, order_type | 26M migrates; detection_date→alert, count→locations, clicks→clicks, post_modified/''→none |
| user meta affibloom_per_page (built) | sessionStorage lasso_dashboard_page_size | |
| localStorage affibloom_filters_open '1'/'0' (13J) | localStorage lasso_dashboard_filters_panel_open | per browser |
| post meta _affibloom_button_url, one row per added button's URL (13G) | post meta second_btn_url | derived on save from _affibloom_buttons |
| option affibloom_dashboard_tour_done '1' (13M), autoload no | option lasso_dashboard_tour_done | F10 should migrate (N1) |
| — | localStorage lasso_pagination, #page-N hash | replaced by paged= (13D) |

ENTRY POINTS AND REQUEST PATHS
- GET admin.php?page=affibloom[&s][&link_type][&paged][&per_page][&orderby&order][&post_id]
  → DashboardScreen::render() → ListOrder::from_input (13K), post_id (13F) → ProductService::list(ListQuery)
  → ProductRepository::page() → PostTypeProductRepository (WP_Query + posts_where search + posts_orderby arm)
  → templates/admin/dashboard.php (forms with hidden list args 13F; sortable header links 13K).
- GET /affibloom/v1/dashboard/products (built 13D) gains orderby (enum alert|locations|clicks|''), order (enum asc|desc|''),
  post_id (integer ≥0); answer gains library (13I), orderby, order (13K). 400 rest_invalid_param on a bad enum.
  Capabilities::PRODUCTS.
- POST /affibloom/v1/dashboard/views (built) gains orderby, order (13K). DELETE /dashboard/views/{id}: unknown id →
  404 affibloom_not_found "Saved view not found." (13J).
- GET /affibloom/v1/products?search=&page=&per_page=10 (G4; 13L is the second caller) answer per item: id, name,
  link, thumbnail, edit_url (13L adds the fields G4 lacks).
- POST /affibloom/v1/dashboard/tour → 200 { data: { done: true } } (13M); PRODUCTS; REST nonce.
- Keyboard Ctrl/Cmd+Shift+L on any admin screen where AdminAssets enqueues palette.js for a PRODUCTS user (13L).

LAYERS
- Domain: Product\ListOrder (column enum Modified|Alert|Locations|Clicks, direction; from_input(); next(column):
  Lasso's cycle R2; is_default()) — no WordPress. Product\ListQuery (term, link kind, page, per page, order,
  post_id) — introduced when 13K adds the sixth list argument (second use of the argument bundle in route and
  screen). Dashboard\SavedView gains ListOrder (13K). Dashboard\TourSteps (step ids + texts, filter by present
  targets is JS) only if the server renders the steps (13M decides: data in PHP via wp_localize_script).
- Application: ProductService::list(ListQuery); ProductService::count() (built) used in the list answer (13I);
  SavedViewService::delete() throws ViewNotFound (13J); DashboardTourService::done()/dismiss() over a
  TourRepository (OptionTourRepository).
- Infrastructure: PostTypeProductRepository::page() orderby clause (posts_clauses filter scoped by a query var, as
  search_filter): modified → post_modified DESC, ID DESC; alert → F11's issue join, NULLs last; save() writes
  _affibloom_button_url (13G).
- Interface: DashboardScreen, DashboardController, DashboardUrl::to(ListQuery), partials product-list (header links),
  pager/search/filters forms (hidden args), saved-views, filters-panel (Apply in .no-js), dashboard.js (load() list
  args, sort clicks, counts, pills, panel storage), assets/admin/palette.js + partials/palette.php (13L),
  assets/admin/tour.js + Rest\DashboardTourController (13M); AdminAssets enqueues palette on every admin screen for
  PRODUCTS users.

RISKS
- Sort arms with joins (alert, locations, clicks) inside WP_Query: use posts_clauses scoped to the query var, never
  global; each arm adds ID DESC. Large libraries: LEFT JOIN on indexed post IDs; 263 rows here.
- Five features add list arguments; without one ListQuery/DashboardUrl every form and test drifts again (13E's bug).
- The palette loads on editor screens: keep it tiny (no dependencies but wp-api-fetch), lazy-build the dialog, never
  bind the shortcut while the focus is in a text field (R21).
- 13G changes stored meta: existing Local test products need the seed tool re-run; no site has Affibloom data
  before migration (F10 writes through save()).
- Tour targets change with later screens: look up targets at start, skip missing ones.

SECURITY
- Every route PRODUCTS; REST nonce via wp.apiFetch. orderby/order/link_type enums at the route; the screen maps
  unknown values to the default (sanitize_key, allowlist). ORDER BY built only from the enum map, never from input.
- Palette and tour texts are escaped by textContent; result URLs set with esc_url on the server and as href only.
- The tour option is site-wide; only PRODUCTS users can set it.

TESTS
- Unit: ListOrderTest (cycle R2 incl. Alert off, from_input fallbacks), SavedViewsTest (order stored), ButtonUrls
  derivation, DashboardTourService, SearchTerm unchanged.
- Integration (WP test suite, when available): ProductListTest gains ties and orderby arms.
- E2E F19.spec.ts: AC15 (javaScriptEnabled: false context), AC16-AC26; AC4 and AC10 rewritten (13H); F12/F21 rerun
  F19 AC21-AC23 shape for their columns.
