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
