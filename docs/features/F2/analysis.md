# F2 Amazon links: analysis

Status: **gate 2 re-run** with the requirements (2026-11-13), on the code that Tasks 11A-11E built. Replaces the
Task 6 version, which assumed the old Task 11 code (`create_from_urls()`, `urls[1-50]`, an `invalid` list).

**Tasks:** 12A-12E (Phase 2). The tracking-ID settings exist (10F).

## 1. How Lasso does it

```
lasso_add_a_new_link( $url )
  add_https, trim                                            (11B: AffiliateUrl::repair)
  get_redirect_final_target( $url )                          Amazon product link: no request, itself
                                                             short link: wp_remote_get → its destination
  short link → $url = its destination                       (save stores the destination)
  title = "Amazon"
  format_amazon_url: smile.amazon. → amazon.
  is_lasso_url_exist( $url, $final )
    get_lasso_post_id_by_url: stored link … ASIN + country … search keywords
    non-product Amazon page: get_amazon_product_url( link ) → the stored-link lookup again
    → found: Sorry!/wrong! re-title, publish, open it
  $url = get_amazon_product_url( $final )                    the tag rule, shorten on/off, kept parameters
  ASIN: cloud/Creators API product data → title, image      (C1)
  save_lasso_url: url_details( product_id, country, product_type=amazon ), amazon_product_id,
                  lasso_amazon_product_country, affiliate_link_type
```

## 2. What Affibloom builds

### 2.1 Domain (no WordPress calls)

- **`Domain\Amazon\AmazonUrl`**, made from an Amazon `AffiliateUrl` (or null for any other):
  - `asin()`, `country()`, `is_short_link()`, `search_keywords()`, `is_protected()`;
  - `monetized( TrackingIds, bool $shorten ): AffiliateUrl`: sections 3.1-3.5 of the requirements;
  - `starting_name()`: the slug, "Amazon: <keywords>" or "Amazon" (12E);
  - `search_key()`: `<host>|<keywords>` (12C);
  - `from_bare_asin( string ): ?AffiliateUrl` (12E).
- **`Domain\Amazon\TrackingIds`**: the site's Tracking ID, Allow Multiple Tracking IDs and the whitelist;
  `tag_for( string $pasted ): string` (requirements 3.3). Built by the service from `Settings`.
- `ProductType::Amazon`; `Product` gains `asin`, `amazon_country`, `search_key` (empty for other links), each
  added by the task that first stores it.

The ASIN pattern, the country table and the kept-parameter list are Lasso's, copied into constants with the source
named beside them, as 11D did with the host list.

### 2.2 Application

`ProductService::create_from_url()` keeps its shape (the duplicate check before any request, 11B) and gains an
Amazon branch between parsing and naming:

```
input → bare ASIN? (12E) → AffiliateUrl::from_string
     → stored-link lookup (11B)
     → LinkResolver::resolve()          short link followed to its destination (12D)
     → Amazon destination?  AmazonUrl
          ASIN + country lookup (12B), search-key lookup (12C), retagged-link lookup (12C)
          → match: re-title (12C), AddedLink( existing, true )
          monetized( TrackingIds, shorten )                      (12A)
          Product::draft( starting_name, url, settings ) with type, ASIN, country, search key
     → otherwise as now (11D, 11E)
```

`LinkResolver` (11D) stops treating every Amazon link as final: a short link is fetched (12D). Its "Amazon" name
for Amazon links moves to `AmazonUrl::starting_name()` (12E).

### 2.3 Infrastructure

- `PostTypeProductRepository`: meta `_affibloom_type = amazon` (exists), `_affibloom_asin`,
  `_affibloom_amazon_country` (12A); `find_amazon( asin, country )` (12B, a `meta_query` on both keys, published
  only); `_affibloom_search_key` and `find_by_search_key()` (12C, where the single-meta lookup becomes a private
  helper shared with `find_by_url_key()`, its second use).
- `WpPageFetcher` (11D): stops at the first redirect into an Amazon **store**, so a short link's own host is
  requested and its destination is never fetched (12D). Today it stops at any Amazon host, which a first hop to
  `amzn.to` cannot pass.

### 2.4 Interface

Nothing new. `POST /affibloom/v1/products` and the modal are unchanged; the stored link, type and name differ.
`ProductResource` returns `url` as stored, so the E2E tests read the result from the route and the meta.

## 3. Data touched

| Key | Value |
|---|---|
| `_affibloom_url`, `_affibloom_url_key` | the monetized link and its key |
| `_affibloom_type` | `amazon` for any Amazon destination |
| `_affibloom_asin` | as Lasso extracts it, case kept; MySQL's collation compares it without case, as Lasso's |
| `_affibloom_amazon_country` | `us`, `gb`, … |
| `_affibloom_search_key` | `amazon.com|nike kd14` (Lasso's `base_domain`: the host without `www.`) for a search page, else absent |
| Option `affibloom_settings` (read) | `tracking_id`, `multiple_tracking_ids`, `tracking_id_whitelist`, `shorten_amazon_links` |

## 4. Risks

- **External HTTP is blocked on this copy** (`WP_HTTP_BLOCK_EXTERNAL`), so a real `amzn.to` link cannot be
  followed here: the unresolved case is what Local shows, and what AC7 checks end to end. The followed case is
  covered by unit tests with `FakePageFetcher` and checked by hand through the REST server in WP-CLI, with a
  `pre_http_request` filter in the `wp eval` script answering `amzn.to` with a 301 (as 11D logged its requests).
  No stub plugin is added to the site (no new infrastructure).
- **The licence.** No Amazon store page is requested. Following `amzn.to` requests Amazon's shortener, which
  answers with a redirect and no product content; the plan's Task 12 already planned it and the overview's
  constraint is about product data. Recorded so the security review (Task 37) can check it.
- **Byte-for-byte links.** Lasso writes kept parameters as parsed (`name=value`, not re-encoded) and the shorten-off
  and page queries with `rawurlencode`; tests compare exact strings from the live data.
- **The live data is tag-first.** Migration (Task 26) copies Lasso's stored links unchanged; nothing here rewrites
  existing products.

## 5. Security

- Only Lasso's Amazon hosts are Amazon's; the tag rule never adds a tag to a non-Amazon link.
- A followed short link has the 11D fetcher's limits (five hops, 5 s, 1 MB, redirects followed by hand, stop
  before an Amazon store).
- Protected attribution links are stored untouched, so Amazon's own attribution is not broken.

## 6. Tasks (gate 3, `docs/plans/phase-2-settings-and-product-library.md`)

| Task | Entry point | Builds |
|---|---|---|
| 12A | add an Amazon product link | `AmazonUrl` (ASIN, country, protected, `monetized`), `TrackingIds`, type and meta |
| 12B | add the same product again | ASIN + country lookup |
| 12C | add an Amazon search page or store page | page monetizing, search key, retagged lookup, re-title |
| 12D | add an `amzn.to` link | short links followed to the store, unresolved kept |
| 12E | add a bare ASIN; the new product's name | bare ASIN, starting name (decision 11) |
