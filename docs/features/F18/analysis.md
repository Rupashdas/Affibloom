# F18 Adding links: technical analysis

Status: **draft, gate 2**, 2026-11-13. Requirements: `requirements.md` in this folder.

## 1. How Lasso does it

### 1.1 The request path

```
header "Add New Link"  →  #url-add (Bootstrap modal)
  → JS save_lasso_url()            client check: empty only
  → POST admin-ajax.php            action=lasso_add_a_new_link, security=<lasso_ajax nonce>
       Lasso_Ajax::lasso_add_a_new_link()        edit_posts
         link is an array?  → Lasso_Process_Bulk_Add_Links::process()   queue, returns at once
         marketplace?       → Pro_Marketplace_Catalog, then the single path
         otherwise          → Lasso_Affiliate_Link::lasso_add_a_new_link()
              add_https → format_url_before_requesting → geni.us → amazon shortlink
              → format_amazon_url → get_redirect_final_target (HTTP)
              → is_lasso_url_exist()  → hit: re-publish, respond is_duplicate
              → Amazon / extend-product / cloud title+image
              → save_lasso_url()      wp_insert_post + url_details row + meta
  ← JSON { post_id, is_duplicate?, … }
  → JS: redirect to the product page, or to the dashboard for Amazon, or open the quick-detail modal
```

### 1.2 Storage

One `lasso-urls` post (`post_status` `publish`, `post_name` from the title), one row in Lasso's `url_details`
table (the URL, the final URL, the base domain, the product id and type) and post meta, written through
`meta_input` at insert. The per-link toggles (nofollow, new tab, both buttons, sponsored) are written then too,
copied from the General settings as they stand at that moment (requirements section 7).

### 1.3 The background queue

`Lasso_Process_Bulk_Add_Links extends Lasso_Process` (a WP-Background-Processing style queue over
`wp_options` plus a dispatch request). `process()` refuses to start while a run is already in flight, resets
`lasso_bulk_add_link` to 0, stores `lasso_bulk_total_links`, pushes each link and dispatches. `task()` calls
the same single add and increments the counter. `get_progress()` is the percentage the heartbeat returns.

## 2. What Affibloom builds

Four layers, dependencies inward, as `00-overview.md` requires. Nothing is created before it has a caller.

### 2.1 Domain (no WordPress calls)

| Class | Why |
|---|---|
| `Domain\InvalidField` | the one shape for "this field is wrong": field, reason code, message. First caller `AffiliateUrl`, then every later validator |
| `Domain\Product\AffiliateUrl` | the normalisation of requirements section 4 that does not need the network: trim, the `add_https` repair, the broken-prefix table, and `url_key()` — lower case, no trailing slash, `&amp;` decoded — which is the duplicate key |
| `Domain\Product\ProductType` | `amazon` or `other`, decided from the host |
| `Domain\Product\Product` | the record: name, URL, type, image, description, the button and badge fields, the per-link toggles. `Product::draft()` builds a new one from a URL, a name and the site's defaults |
| `Domain\Product\ProductRepository` | the interface the application depends on: `save()`, `get()`, `find_by_url_key()` |

### 2.2 Application

| Class | Why |
|---|---|
| `Application\Products\ProductService::create_from_urls()` | one line at a time: parse, look for a duplicate, name the product, save. Returns created, duplicate and invalid lists so both the REST route and the background worker can use it |
| `Application\Products\ProductNamer` (interface) | "what do we call this product?" — the decision D3 answer behind one seam, so the add path does not depend on how a title is found |

### 2.3 Infrastructure

| Class | Why |
|---|---|
| `Infrastructure\WordPress\ProductPostType` | registers `affibloom_product` on `init` |
| `Infrastructure\WordPress\PostTypeProductRepository` | `wp_insert_post` plus the `_affibloom_*` meta of `docs/spec/interface.md` section 4; `find_by_url_key()` is a meta query on `_affibloom_url_key` |
| `Infrastructure\WordPress\BulkAddQueue` | only if the user keeps Lasso's background bulk add (decision in section 3) |

### 2.4 Interface

| Class / file | Why |
|---|---|
| `Interface\Rest\ProductsController::create()` | `POST /affibloom/v1/products`, `Capabilities::PRODUCTS`, REST nonce |
| `Interface\Rest\ProductResource` | one product as JSON |
| `Interface\Messages` | the user-facing strings in one place |
| `Interface\Admin\Notice` | the dashboard notice after a redirect; taken out of `SettingsScreen` on its second use, as the plan says |
| `templates/admin/partials/add-modal.php` | the dialog, in the Affibloom design system (ADR-8), with Lasso's labels, hint and modes |
| `assets/admin/admin.js` | open and close, Shift+Enter, the counter, submit, the error line |

The modal is Affibloom's own design, Lasso's structure: the title and subtitle, the single/bulk switch, the
hint, the counter and the "Add Link" button all keep Lasso's wording (requirements section 2); the colours,
type and spacing come from `docs/ui/design-system.md`.

## 3. Risks and decisions to confirm

1. **D1–D6 in the requirements are open.** D1 (what counts as invalid), D2 (duplicates and the
   `check_duplicate_link` setting) and D3 (where a non-Amazon title comes from) each change the code of the
   first task, so they are answered before it starts.
2. **The interface spec contradicts Lasso in three places** and needs the same gate-4 ruling:
   `urls: string[1–50]` against Lasso's hard limit of **10**; duplicates reported **inside the modal**
   against Lasso's redirect to the existing product; and "Marketplace excluded, so no tabs".
   `docs/spec/interface.md` is updated to whatever is decided.
3. **No network on this copy.** `WP_HTTP_BLOCK_EXTERNAL` is on (Task 2), so any title/image fetch must be
   behind the `ProductNamer` seam and must degrade to the domain name. The acceptance tests must not depend
   on an outgoing request.
4. **The background queue is a subsystem of its own.** Lasso's is a full queue with age-out, dispatch and
   logging. If the user keeps it, the honest size is its own task; a synchronous bulk add of ten links is the
   smaller alternative and would still meet AC7 and AC8, but not AC9's "leave the page" promise.
5. **`lasso_heartbeat` has no nonce and no capability check.** Affibloom's progress route gets both. Recorded
   so the difference is deliberate.
6. **Lasso's analytics call** (`lasso_segment_tracking`) is not built; C3 covers the site's own GA4 events.

## 4. Proposed tasks (gate 3)

Task 11 as written in `phase-2-settings-and-product-library.md` covers roughly the first of these. The split
follows the same shape as 10A–10F: each task ends at an entry point that has been run and seen.

| Task | What it builds | Acceptance |
|---|---|---|
| **11A** | The product exists: the post type, `Product`, `AffiliateUrl`, `InvalidField`, the repository, `ProductService::create_from_urls()` for one URL, `POST /products`, and the modal in single-URL mode with its error line. No title lookup — the name is the domain | AC1, AC5, AC11 |
| **11B** | Normalisation and duplicates: the `add_https` repair table, `url_key()`, `find_by_url_key()`, and whatever D1 and D2 decide (refusal messages, or the redirect and the "already exists" warning, and the setting if it is kept) | AC2, AC3, AC4 |
| **11C** | Bulk add: Shift+Enter and the header menu, the textarea, the counter, the preview and pencil, the confirm, the ten-link ceiling, a WP-Cron background run (D3b) with the progress bar and the "still processing" state | AC6, AC7, AC8, AC9 |
| **11D** | Naming (D3): `LinkResolver` — an embedded destination's site, else the page title after redirects, Lasso's placeholder titles and fallbacks, Amazon never requested, no image; the `keep_genius_formatting` rule | AC1 |
| **11E** | The site's link defaults copied onto a new product at creation, as Lasso does | AC10 |

The full task text, with Lasso's rules and what each task creates, is in the phase file
(`phase-2-settings-and-product-library.md`, Tasks 11C–11E).

Task 12 ("Adding an Amazon link or ASIN") stays as the plan has it and follows 11D, because it is the Amazon
half of the same naming seam.
