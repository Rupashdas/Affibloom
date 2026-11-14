# F24 Link cloaking, redirects and permalinks: analysis

Status: gate 2 written 2026-11-14 with `requirements.md`. Gate 4 for Tasks CL1-CL11: waiting.

**Tasks:** CL1 (the redirect for a cloaked path), CL2 (query pass-through), CL3 (Permalink and Link Cloaking on the
product page; the public link on that page), CL4 (a new product's slug and flag), CL5 (the General settings take
effect), CL6 (the public link in front-end output), CL7 (the Dashboard row's link), CL8 (block editor link search),
CL9 (classic editor link search), CL10 (old addresses and Lasso's hand-over), CL11 (a pasted cloaked URL finds its
product). Area 7: before Phase 9's cutover; CL1 and CL10 are cutover blockers.

## Depends on

- ADR-001 (products are private posts), ADR-007, ADR-008; F1 (product page, `PATCH /products/{id}`, Share kit,
  `ProductResource`, D5's placeholder public link); F2 (Amazon URL formatting and the tag rule); F18/Task 11 (Add Link,
  `LinkResolver`, duplicate rule); F9 (keys `link_cloaking` 9B, `cloak_prefix` 9G, `restrict_prefix` 9L, the
  "Update Link Settings" dialog 9D); F19 (Dashboard row); F5 (box and text-link rendering); F21 35H (render-time
  links, the first non-admin reader of `PublicLink`); F10 26G (writes this feature's data).

## Data

| Where | What | From Lasso |
|---|---|---|
| `wp_posts.post_name` of `affibloom_product` | the slug | `post_name` of `lasso-urls` (copied as is) |
| meta `_affibloom_cloak` | `'1'`/`'0'` | meta `link_cloaking` |
| meta `_affibloom_old_slugs` | earlier slugs, newest last (N1) | `lasso_old_slug` (non-empty, ≠ current) |
| setting `cloak_prefix` (in `affibloom_settings`) | '' or a `sanitize_title` value | `rewrite_slug` |
| setting `link_cloaking` | default for new products, true | `link_cloaking` |
| setting `restrict_prefix` | false; kept for parity (it changes nothing for a private type) | `restrict_prefix` |
| option `affibloom_cloak_prefix_history` | earlier prefixes (N2) | none (Lasso keeps none) |
| plugin file `data/network-subids.php` | network → sub-ID parameter names (33) | `files/affiliates.json` `sub_ids` |

No table, no transient, no rewrite rule.

## Entry points and request paths

```
GET /<slug>/  or  /<prefix>/<slug>/  [?query]                                            (CL1, CL2, CL10)
  → Interface\Front\CloakedLinkRedirect (Module; template_redirect 5; not is_admin())
      CloakPath::from_request( path, home path, prefix )    pure: subdirectory, static guard, tag/category guard,
                                                            one segment or prefix + one (R16-R17, D4)
      PostSlugGuard (WordPress: post/page publish|draft with that slug → return)          (R16)
      CloakResolver::product_for( CloakPath )
          ProductRepository::find_by_slug( slug, [publish, retired] )   → else old slugs / old prefixes (CL10)
          → else UrlVariants (F27 V3)
      PublicLink::destination( product, settings )                    (D6)
      RedirectQuery::apply( destination, request params, referrer, NetworkSubIds )   pure (CL2)
      do_action( 'affibloom_before_link_redirect', id, referrer )
      wp_redirect( url, 302, 'Affibloom' ) + X-Robots-Tag + Cache-Control; exit
  filters: redirect_canonical (R24), rank_math/redirection/pre_search (R25)

GET admin.php?page=affibloom-product&id=N                                                  (CL3)
  ← templates/admin/product.php: partial `permalink-cloak.php` (Permalink + Link Cloaking, D14), heading link and
    Share kit from ProductResource `public_link`
PATCH /affibloom/v1/products/{id} { …, slug, cloak }
  → ProductsController (Capabilities::PRODUCTS) → ProductInput (`slug` string, `cloak` bool)
  → ProductService::update() → ProductSlug::clean() (R4) → PostTypeProductRepository::save() (post_name through
    wp_update_post → wp_unique_post_slug), old slug appended to `_affibloom_old_slugs` when it changed (N1)
  ← 200 ProductResource::full() + `public_link`, `slug`, `warnings: [ { code: affibloom_slug_in_use, slug, type } ]`
     422 data.fields.slug? (never: an empty slug takes the name's, D12)

POST /affibloom/v1/products { url, … }                                                     (CL4, CL11)
  → LinkResolver: own-domain path → CloakResolver (duplicate, R36) → 409 duplicate as today (F18)
  → ProductService::create(): ProductSlug::for_new( name, is_amazon ) (R3), cloak = settings link_cloaking (R12)

POST /affibloom/v1/settings (General tab, F9)                                              (CL5)
  → SettingsService::save(): cloak_prefix changed → previous appended to `affibloom_cloak_prefix_history` (N2)
POST /affibloom/v1/settings/override { option: link_cloaking, value }   (F9 9D's route; this feature's arm)
  → ProductRepository::set_meta_for_all( '_affibloom_cloak', '1'|'0' )   (every product, D15)

Front-end render (CL6): Display renderer (F5) and text-link shortcode take `PublicLink::for( product )`.
GET /affibloom/v1/dashboard/products …                                                     (CL7)  row `public_link`
GET /wp/v2/search?type=post&search=…  (block editor)                                       (CL8)
  → filter wp_rest_search_handlers: Interface\Rest\ProductSearchHandler (WP_REST_Search_Handler, type 'post',
    subtype 'affibloom_product'; edit_posts) → ProductRepository::search() → url = PublicLink
POST admin-ajax.php?action=wp-link-ajax (classic)                                          (CL9)
  → filter wp_link_query (append products, permalink = PublicLink; edit_posts)
```

## Layers

- **Domain** `Cloak\CloakPath` (parse and match, pure), `Cloak\CloakPrefix` (sanitize rule, history),
  `Cloak\PublicLink` (R13: destination and public link from a product, the settings and the kept-domain list),
  `Cloak\KeptDomains` (R13's list + `keep_original_url`), `Cloak\RedirectQuery` (R22, pure), `Cloak\NetworkSubIds`
  (interface; data from the plugin file), `Product\ProductSlug` (R3, R4: `for_new()`, `clean()`).
- **Application** `Cloak\CloakResolver` (path → product: slug, old slug, old prefix; variants added by F27),
  `ProductService` (slug and flag on create/update), `SettingsService` (prefix history), a `SlugConflicts` port
  (another post with the slug; R5).
- **Infrastructure** `WordPress\PostTypeProductRepository` (`find_by_slug()`, `find_by_old_slug()`,
  `set_meta_for_all()`), `WordPress\WpSlugConflicts` (R5's query), `WordPress\WpPostSlugGuard`,
  `Data\FileNetworkSubIds`.
- **Interface** `Front\CloakedLinkRedirect` (Module), `Rest\ProductSearchHandler`, `Admin\ClassicLinkSearch` (Module),
  `ProductsController`/`ProductInput`/`ProductResource` (`slug`, `cloak`, `public_link`, warnings),
  partial `templates/admin/partials/permalink-cloak.php`, `assets/admin/product.js` (R8's typing rule, the heading
  line), `Messages` (`slug_in_use`).

## Risks

- **Hook order:** other plugins redirect at `template_redirect` too; priority 5 is Lasso's, so the order relative to
  them is unchanged at cutover. Rank Math's redirector (module off here) is covered by R25's filter.
- **A page published later with a product's slug** silently takes the address (R16); the conflict warning appears only
  when the product is saved. The Dashboard alert for it would be new (not proposed).
- **Lasso still active on the same site** before cutover: both handlers would match; Affibloom's must not register
  while Lasso is active (the cutover plan's switch), or it simply loses the race at priority 5 by registration order.
- **Performance:** one indexed `post_name` query per request that reaches the 404 path; nothing for normal pages
  (the post/page guard runs only when the path has one or two segments).

## Security

- The redirect is open only to the product's stored destination (no user-supplied target); query parameters are
  appended with `add_query_arg`-equivalent building and the result passed to `wp_redirect` (not `wp_safe_redirect`:
  destinations are external by design).
- `PATCH` and the override: `Capabilities::PRODUCTS` / `SETTINGS`, REST nonce; the slug through `sanitize_title`.
- Editor search: `edit_posts` (INSERT_BOXES), products' names escaped by core.
- No post content is written (D13).

## Tests

- **Unit:** `CloakPathTest` (every R17 case: prefix/no prefix, tag/category, subdirectory, static paths, three
  segments refused), `PublicLinkTest` (R13 table: Amazon, flag on/off, kept domain, import platform, keep_original_url),
  `RedirectQueryTest` (R22 every rule), `ProductSlugTest` (R3, R4), `CloakPrefixTest`.
- **Integration:** `CloakedLinkRedirectTest` (WP test suite: 302, headers, post/page guard, statuses, prefix),
  `ProductSearchHandlerTest`, settings history.
- **E2E acceptance `tests/E2E/acceptance/F24.spec.ts`:** AC1-AC4 by HTTP requests against Local (no redirect
  following); AC5-AC7 on the product page at 1280 and 375 px; AC8 and AC15 through Add Link; AC9 on the General tab;
  AC10 a test post's box; AC11 the Dashboard; AC12 the block editor's link popover; AC13 the classic dialog; AC14 after
  `wp affibloom migrate --only=slugs` on a fresh copy: the seven content links' addresses each 302 to Lasso's
  destination.
