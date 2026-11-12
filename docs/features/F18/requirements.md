# F18 Adding links ("Add A New Link"): requirements

Status: **draft, gate 1**, 2026-11-13, written under the full-Lasso scope rule (`progress.md` deviation 24)
before Task 11 is built.

Evidence, all read in this session from the read-only reference copy `D:\lasso\lasso` (Lasso **369**, the same
version as the copy installed on this site):

| What | Where |
|---|---|
| The modal, its markup and all of its JavaScript | `admin/views/modals/url-add.php` (2,330 lines) |
| The three header entry points | `admin/views/header-new.php` 199-227 |
| The empty-state entry point | `admin/views/education/dashboard.php` 10 |
| The AJAX endpoint | `pages/class-ajax.php::lasso_add_a_new_link` 917-1012 |
| The bulk progress endpoint | `pages/class-ajax.php::lasso_heartbeat` 1885-1899 |
| The add itself | `classes/class-lasso-affiliate-link.php::lasso_add_a_new_link` 815-1071 |
| Duplicate detection | `classes/class-lasso-affiliate-link.php::is_lasso_url_exist` 763-807, `::get_lasso_post_id_by_url` 2537+ |
| The duplicate response | `::check_error_and_response_ajax` 1983-2021, `admin/views/dashboard/url-details.php` 2597-2600 |
| Storage and required fields | `::save_lasso_url` 1073+ |
| Per-link defaults for a new link | `classes/class-lasso-affiliate-link.php` 140-175 |
| Bulk queue | `classes/background_processing/class-lasso-process-bulk-add-links.php` |
| URL normalisation | `classes/class-helper.php::add_https` 1876-1922, `::validate_url` 164-174, `::format_url_before_requesting` 137-142, `::get_redirect_final_target` 368+ |
| The live settings | `tests/fixtures/inventory/settings.json` (Task 4 inventory) |
| The screen as the client sees it | `tests/fixtures/baselines/admin/add-link-modal.jpg` (Task 5) |

**What this feature is:** every way a product can be created from a link — the modal and its three modes
(single URL, bulk, Marketplace), the editor variant, the normalisation and duplicate rules that decide what is
stored, and what the editor sees afterwards. It does **not** cover the product page (F1, Tasks 14-15), the
dashboard list (Task 13) or the quick edit (Task 16).

---

## 1. Entry points

| # | Trigger | Where | Opens |
|---|---|---|---|
| 1 | "Add New Link" split button, `.header-add-new-link-btn` | the header bar, on every Lasso admin page | the modal, URL tab, single-line input |
| 2 | Caret → "Bulk add links" / "Import many URLs at once", `.header-bulk-add-links-btn` | the same split button's dropdown | the modal, URL tab, **textarea** mode |
| 3 | Caret → "Marketplace" / "Discover products to add as links", `.header-marketplace-add-links-btn` | the same dropdown | the modal, **Marketplace** tab |
| 4 | "Add a Link" | the dashboard's empty state (`education/dashboard.php`) | the modal, URL tab |
| 5 | The editor's own add control | the post editor (`$is_from_editor` branch, modal markup 130-215) | the same two tabs, **no** bulk mode |
| 6 | `?flip_url=<url>&old_id=&old_url=` on the dashboard | Lasso Hub "flip an Amazon link" | the modal opens **and submits by itself** |
| 7 | **Shift + Enter**, anywhere on the dashboard | a keyboard shortcut, no visible control | toggles the single input into the bulk textarea and back |

The split button is replaced by an "Update Account" link when the licence is not valid
(`Lasso_License::get_license_status()`); that branch is Lasso's own business logic and has no Affibloom meaning.

## 2. The modal, control by control (URL tab)

Lasso's modal is one `#url-add` dialog whose title, subtitle and input change with the mode.

| Control | Text / markup | Rule |
|---|---|---|
| Title `#url-add-modal-title` | "Add A New Link" / "Bulk Add Links" / "Lasso Marketplace" | set per mode, reset to the first on close |
| Subtitle `#url-add-modal-subtitle` | "Enter the affiliate link you would like to track." / "Enter one URL per line (up to 10). Links are created in the background—you can leave this page while they process." / "Search the Marketplace, select a product, then click Add Link." | as above |
| Tabs | "URL", "Marketplace" | `role="tablist"`, `aria-selected`, the modal widens on Marketplace |
| Input `#add-new-url-box` | `<input type="text">`, placeholder `https://www.example.com/affiliate-id` | **no label at all**, only the placeholder |
| Bulk input | the same id, replaced by `<textarea rows="7" wrap="off">` | Shift+Enter, or entry point 2 |
| Hint `#multi-links-hint` | "Enter each new link on a separate line (Shift + Enter)" | shown in single mode, hidden in bulk mode |
| Counter `#max-link-wrapper` | "Remaining links: 10" | bulk mode only; counts non-blank lines down from 10, floor 0 |
| Preview `#multi-links-preview-wrapper` | an `<ol>` of the typed links | shown on blur of the textarea |
| Edit pencil `#edit-multi-links` | pencil icon | turns the preview back into the textarea |
| Progress `#progress-bar-multi-links` | striped animated bar | bulk only; starts at 3%, then the heartbeat's percent |
| Error line `.js-error` | server or client message, red | hidden again on the next submit |
| Submit `.lasso-url-add-submit-btn` | "Add Link" with a plus icon | becomes a spinner while in flight; after a server error becomes **"Continue Anyway"** with `data-continue=1` |
| Reload `.btn-reload` | "Reload" with a sync icon | replaces Add Link while a bulk run is already in progress |

Closing the modal resets the tab, the Marketplace selection, the title and subtitle, and turns a textarea back
into the single input.

## 3. What Lasso actually accepts — the plan's validation is wrong

This is the single biggest correction the sweep found.

- **Client side there is no URL validation.** The modal's `save_lasso_url()` (JS, line 1501) refuses only an
  empty value, with the message **"URL is incorrect."** Nothing checks the scheme, spaces or shape.
- **`Lasso_Helper::add_https()` repairs instead of refusing.** A string with a dot and no scheme gets
  `https://` put in front of it, after leading slashes are stripped. So `mizunousa.com/wave` is **accepted**
  and stored as `https://mizunousa.com/wave`. It also rewrites eleven known broken prefixes
  (`https://https://`, `http:/https://`, `]https://`, `[gift_item link=&quot;https://`, and so on) to
  `https://`, returns `mailto:` and e-mail values untouched, returns anything starting with `[` untouched, and
  returns `/<host>` when the parsed host contains `.html`.
- **`Lasso_Helper::validate_url()` is not on this path.** It is used only for the `flip_url` query argument.
- Server side the only refusals are "No data to save." (empty URL) and, inside `save_lasso_url`,
  "Name and Primary Destination URL are required."

**Consequence for the plan:** Task 11's user action ("A link without `https://`, or one with spaces in it, is
refused inside the modal with a message"), the integration test `test_an_invalid_link_is_reported_not_created`
with the input `mizunousa.com/wave`, and F3 AC2's "This is not a valid link." all describe behaviour Lasso does
**not** have. Lasso would create a product from that input. This needs a decision (section 13, D1).

## 4. Normalisation, in order, before anything is stored

1. `trim()`.
2. `add_https()` — section 3.
3. `format_url_before_requesting()` — another `trim()`.
4. **geni.us**: if the base domain is `geni.us` the original is remembered as `old_genius_url`; when the
   setting `keep_genius_formatting` is **off** the stored URL is replaced by the resolved final URL.
   Live: **on**.
5. **Amazon short links** (`amzn.to`): the stored URL becomes the resolved final URL.
6. `Lasso_Amazon_Api::format_amazon_url()` on both the URL and the final URL.
7. `Lasso_Helper::get_redirect_final_target( $url, true, true )` — **an outgoing HTTP request** that follows
   redirects and returns the final URL and the page title. Several shortcuts avoid it: a cached Amazon
   short-link target, a Lasso short link resolved from local data, and an embedded destination in a `u=` or
   `url=` parameter, which is how every Impact link on this site resolves without a request.
8. For Amazon, the site's tracking ID is applied (`get_amazon_product_url`).

Step 7 is why `https://mizuno.pxf.io/c/…?u=https%3A%2F%2Fmizunousa.com%2Fwave` keeps its own URL but is
*compared* against the decoded merchant URL.

## 5. Duplicates

Lasso does not refuse a duplicate; it sends the editor to the product that already exists.

**The setting** `check_duplicate_link` (General settings; **live value: on**) decides what is compared:

| Setting | Compared |
|---|---|
| **on** (live) | the pasted URL only — two affiliate links that resolve to the same merchant page are two products |
| off | the **final** URL with its query string removed, then `url_without_arguments`, then the pasted URL |

Exceptions that keep the query string even when the setting is off: Amazon search pages, Amazon URLs with no
ASIN, "extend product" pages with no product id, and the domains in
`Setting_Enum::DOMAIN_ALLOW_KEEP_FINAL_URL_PARAMS`.

**The comparison normalises** the URL on both sides: `trim( $url, '/' )`, so a trailing slash never makes a new
product, and `&amp;` becomes `&`.

**On a hit** (`lasso_add_a_new_link` 886-918): the existing post is forced back to `publish`, its title is
repaired if it is an Amazon error title, the response carries `is_duplicate: true`, the browser goes to
`…&page=url-details&post_id=<existing>&is_duplicate=true`, and that page shows a warning:
**"This product already exists. Please update the Primary URL or add a new link."**

**Conflict with an earlier decision.** Task 6 decision 2 says Affibloom has "no duplicate setting, the same
link is always refused". Lasso has the setting, and refusing is not what it does. Decision needed (D2).

## 6. Title, image and product data at creation

| Case | What Lasso does |
|---|---|
| Amazon URL with an ASIN | the Amazon product from its own table; if absent, fetch from Lasso's cloud; `NotFound` gives the name `DEFAULT_AMAZON_NAME`, the image `LASSO_DEFAULT_THUMBNAIL` and status 404 |
| "Extend product" (Walmart and the other supported merchants) | the same, through `Lasso_Extend_Product` |
| Amazon search or category page | the search phrase becomes the title (`get_search_page_title`) |
| Anything else | `Lasso_Helper::get_url_status_code()` against Lasso's cloud returns `title` / `productName` / `pageTitle`, `imgUrl` and a status; the title "Robot Check" is thrown away |
| Nothing resolved | the title falls back to the permalink, then to `get_title_by_url()` (the domain) |

The permalink is `sanitize_title( $title )`, lower-cased.

**This is the part Affibloom cannot copy as written:** every path above goes through Lasso's licensed cloud.
Deviation 18 already settled how Affibloom gets Amazon data; the non-Amazon path (title and image for an
ordinary merchant link) has no equivalent yet. Decision needed (D3).

## 7. What a new product is given

From the `$affiliate_link` array (1026-1041) and the new-record defaults (140-175):

| Field | Value for a new link |
|---|---|
| name | the resolved title (section 6) |
| URL | the normalised URL (section 4) |
| description, primary button text, secondary button text, secondary URL, price, badge text | empty |
| image | the resolved image, else the site's `default_thumbnail` |
| permalink | `sanitize_title( title )` |
| post type / status | `lasso-urls` / `publish` |
| is_opportunity | the `detect_opportunities` setting — live **on** |
| boosting_enabled | the `auto_upgrade_eligible_links` setting — live **on** |
| link_cloaking | the `link_cloaking` setting |
| nofollow, new tab, nofollow 2, new tab 2, sponsored | **the General settings at the moment of creation, stored on the product.** `save_lasso_url` reads `get_lasso_url( 0, true )` (line 1203), whose new-record values are the settings (lines 159-163: `enable_nofollow`, `open_new_tab`, `open_new_tab2`, `enable_nofollow2`, `enable_sponsored`), and writes them as meta at insert (lines 1421-1435). A later change of a site default does not reach products that already exist |

The last row is what Task 11's integration test asserts (`_affibloom_nofollow` is `'1'` on the created post),
so the plan matches Lasso here.

## 8. Bulk add

- **Ten links maximum**, enforced twice: the textarea counter trims the list to 10, and the endpoint runs
  `array_slice( $links, 0, 10 )`.
- Blank lines are dropped (`array_filter` on `trim`).
- A browser `confirm()` first: **"Add these links in the background? You can stay on the dashboard while they
  are created."**
- The links are pushed to a WordPress background queue (`Lasso_Process_Bulk_Add_Links`, action
  `lasso_bulk_add_links`), which calls the same add for each one. Two options carry the progress:
  `lasso_bulk_total_links` and `lasso_bulk_add_link`.
- The modal polls `lasso_heartbeat` with `type=bulk_add_links` **every 3 seconds** and sets the bar to
  `round( done / total * 100 )`, or 5% while the total is 0. At 100% it sends the browser to the dashboard.
- While a run is in flight, opening bulk mode shows the textarea **disabled**, two rows high, with the
  placeholder "Your bulk add is still processing. Try again in a few minutes.", and a **Reload** button in
  place of Add Link.
- **Failures and duplicates in a bulk run are silent.** Nothing is reported; the editor sees the dashboard.
- `lasso_heartbeat` checks **neither a nonce nor a capability** for `bulk_add_links`, though it does for
  `bulk_sync_amazon`. Affibloom will not copy that; see section 11.

## 9. The Marketplace tab

Lasso's own product catalogue, served by its cloud through `lasso_pro_marketplace_search`: a search box
(minimum 2 characters, debounced), a result list with image, title, price and a per-row "Add Link", bulk
checkboxes with a 10-product ceiling and an "Add %d Links" submit label, pagination, and the status strings
"Searching…", "No products found.", "Marketplace search failed. Try again." and "You can select up to 10
products.". A selection is persisted as an Amazon product before the link is created
(`Pro_Marketplace_Catalog::persist_amazon_product_from_selection`).

This is one of the audit's "things that cannot simply be rebuilt" (Lasso's own service). Decision needed (D5).

## 10. After the add

| Situation | What happens |
|---|---|
| Single add from the header button | redirect to the product page `…&page=url-details&post_id=<new>` |
| Single add of an **Amazon** link | redirect to the **dashboard** instead |
| From the editor, or from a monetize screen (`content-links`, `keyword-opportunities`, `domain-links`, `url-opportunities`) | no redirect — the quick-detail modal opens on the new product |
| Duplicate | the existing product's page with `&is_duplicate=true` and the warning of section 5 |
| Server error | the message in `.js-error`; the button becomes "Continue Anyway" |
| Any add | `lasso_segment_tracking( 'Lasso Link Created' )` fires — Lasso's own analytics, not ours |

There is no toast and no "1 product added." counter anywhere in Lasso's add flow. Task 11's plan text invents
both.

## 11. Permissions and nonces

| | Lasso | Affibloom (decision 1) |
|---|---|---|
| The screen | the `lasso_permission` setting, live **Administrator** | `Capabilities::PRODUCTS` = `manage_options` |
| The add endpoint | `check_ajax_referer( 'lasso_ajax', 'security' )` **plus `current_user_can( 'edit_posts' )`** | the REST nonce plus `Capabilities::PRODUCTS` |
| The progress endpoint | nothing | the REST nonce plus `Capabilities::PRODUCTS` |

So in Lasso an Author could create products through the endpoint even though the menu is hidden from them.
Affibloom keeps `manage_options` on every route (decision 1), which is what Task 11's
`test_an_author_may_not_add_products` expects. This is a deliberate difference, not a missing feature.

## 12. Acceptance criteria

- **AC1** An ordinary affiliate link pasted into "Add A New Link" becomes a product, kept exactly as pasted,
  and its product page opens.
- **AC2** A bare domain (`mizunousa.com/wave`) is accepted and stored with `https://` in front of it.
- **AC3** The same link added twice does not create a second product: the editor lands on the existing
  product's page with the "already exists" warning.
- **AC4** The same link with a trailing slash is the same link.
- **AC5** An empty box is refused in the modal with a message, and nothing is created.
- **AC6** Shift + Enter turns the input into the bulk textarea, and the counter falls as lines are typed.
- **AC7** Ten links pasted into the textarea are created; an eleventh line is ignored.
- **AC8** A bulk run shows a progress bar and ends on the dashboard with all the products present.
- **AC9** Starting a second bulk run while one is in flight is refused in the modal, with a Reload button.
- **AC10** A product created by the add flow carries the site's link defaults (nofollow, new tab, sponsored)
  as the box renders them.
- **AC11** An Author cannot create a product through the endpoint (403).

## 13. Open decisions for the user (gate 4)

| | Decision | Why it cannot be settled from Lasso |
|---|---|---|
| **D1** | **Invalid links — decided (b)** (Claude, 2026-11-13, from Lasso's code; the user may override). Lasso's repair is kept in full (`https://` added to a bare domain, the broken-prefix table), an empty box is refused with Lasso's "URL is incorrect.", and **a string that is still not an `http(s)` URL with a host after the repair** is refused with "This is not a valid link." Lasso would turn such a string into a product with a broken link; the refusal removes no Lasso capability, so it is an improvement that conflicts with neither. F3 AC2 keeps its wording for that case; Task 11's `mizunousa.com/wave` example moves to AC2 (accepted). | Lasso has no behaviour to copy for a non-URL string |
| **D2** | **Duplicates — the user, 2026-11-13: follow Lasso.** The `check_duplicate_link` setting is kept (Lasso default and live value: on) with both comparison modes, and a repeated link opens the existing product with "This product already exists. Please update the Primary URL or add a new link." **Task 6 decision 2 is withdrawn.** The setting's control belongs to the General settings pass; Task 11B reads it. | the two rules the user had given pointed opposite ways |
| **D3** | **Title and image for a non-Amazon link — decided (a)** (Claude, 2026-11-13). Affibloom reproduces what the editor sees in Lasso — a name and an image filled in — with its own means: the embedded destination of a `u=`/`url=` parameter first (no request, as Lasso), then one `wp_remote_get` of the final page with a short timeout, reading `og:title`/`<title>` and `og:image`; "Robot Check"-style titles are thrown away as Lasso does; any failure falls back to the domain (`get_title_by_url`). On this Local copy external HTTP is blocked, so the fallback is what the tests see. Amazon stays with deviation 18 and Task 12. | the service does not exist for us; deviation 18 only settled Amazon |
| **D3b** | **Bulk add runs in the background — decided** (Claude, 2026-11-13): Lasso's promise "you can leave this page while they process", the ten-link ceiling, the 3-second progress and the "still processing" state are all kept, so the run is a server-side queue, not ten requests from the browser. | the full-scope rule; a browser-driven loop would drop the promise |
| ~~D4~~ | **Link defaults — settled by Lasso:** stored on the product at creation (section 7). | — |
| **D5** | **The Marketplace tab — the user, 2026-11-13: rebuilt as an Amazon product search with C1.** The tab, its search, results, per-row "Add Link", the ten-product bulk selection and its status strings are planned with client point C1 (Creators API credentials), whose search is the data source. **The flip entry point (6) is dropped**: it exists only for Lasso Hub. | it is Lasso's own catalogue service |
| **D6** | **The editor modal — placed, not dropped** (Claude, 2026-11-13): entry point 5 is built with the editor block in Phase 4 (Tasks 23-24), on the same `POST /products` route. **The flip entry point** (6) is Lasso Hub's, a cloud service, so it goes with D5. | they are entry points to this feature but owned elsewhere |
