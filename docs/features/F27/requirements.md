# F27 URL variants: requirements

Status: gates 1-2 written 2026-11-14 from a sweep of Lasso 369 (area 10 of
`docs/plans/archive/dashboard-completion-plan.md`). Gate 4 for Tasks V1-V5: given by the user 2026-11-14.

## 1. User stories and acceptance criteria

- As an editor, I give one product extra addresses that all lead to its destination — for a merchant link another
  cloaked permalink (`/<variant>/`), for an Amazon product another tracking ID — so I can tell channels apart without
  making a second product.

- **AC1** A product's page has the tab **Variants `<n>`** (the count of its variants) after Details, Locations and
  Opportunities; it shows Name, **Permalink** (or **Tracking ID** for an Amazon product), Copy and Edit, with the help
  tips; with no variants, "No variants yet", "Create a variant to get a separate permalink or Amazon tracking ID that
  still points to this link’s primary destination." and **Add Variant**. While loading, a spinner. (V1, R4-R9, R25)
- **AC2** The toolbar's **Search** narrows the rows as you type (name, key or URL, any case) without a request. (V1, R5)
- **AC3** **Add Variant** adds a row in edit mode named after the product ("New variant" / "Amazon variant" without
  a name), key `<slug>-2` (the first free `-n`; `variant-2` without a slug) or the site's Amazon tracking ID; **Save**
  stores the whole list and the row shows its name, its URL (`<home>/<key>/`, or the destination with `tag=<key>`)
  and its key; the tab count follows. (V2, R10, R11, R14, R16)
- **AC4** Refusals, shown under the key field: an empty key "Permalink and Tracking ID cannot be empty."; the same key
  twice "Duplicate variant slug or tracking ID in the same list."; the product's own slug "Variant permalink cannot
  match this link’s primary permalink."; another product's variant "That permalink is already used by another link’s
  variant."; a post's or page's slug "That slug is already used by another page or link."; another product's slug
  "That slug is already a Lasso link." (wording N1). Nothing is stored on a refusal. (V2, R15)
- **AC5** **Remove** asks "Remove this variant?" ("Its cloaked URL or tracking branch will stop working. This cannot be
  undone.", Cancel / Remove) and stores the list without it. **Copy** puts the row's URL on the clipboard and says
  "Copied!". (V2, R8, R12, D5)
- **AC6** `GET /<variant key>/` answers like the product's own address (F24 AC1: 302 to its destination, its headers,
  its query pass-through); a product whose slug is that key wins; the cloaked prefix does not apply to variants.
  (V3, R18, R24)
- **AC7** An Amazon URL whose `tag` is any product's Amazon variant keeps that tag when Affibloom formats it (on save,
  on Add Link and in output) instead of the site's tracking ID. (V4, R19)
- **AC8** The Dashboard search finds a product by a variant's name or key, and by a variant's number. (V5, R20)

## 2. Evidence

Read in full: `models/class-url-variants.php` (367), `pages/url-variants/class-ajax.php` (98),
`admin/views/dashboard/url-variants.php` (55), `admin/assets/js/url-variants.js` (606),
`admin/views/modals/url-variant-remove.php`. Read in part: `admin/views/dashboard/header.php` 120-145, 245-330 (the
tab and toolbar); `pages/class-hook.php` 1136-1175 (`lassoVariantsData`), 380-390 (the page); `classes/class-redirect.php`
205-232; `classes/class-post-type.php` 479-507; `classes/class-lasso-amazon-api.php` 36-62, 1466-1560
(`get_amazon_product_url`); `classes/class-lasso-db.php` 80-135 (the Dashboard search); `classes/class-lasso-affiliate-link.php`
763-807, 2537-2660 (no variant arm); greps for `Url_Variants`, `url_variants`, `LASSO_URL_VARIANTS`,
`apply_amazon_tag_to_url`, `amazon_variant_tag_exists`, `lassoVariantsData` over the whole source (callers: the
redirect, `custom_post_request`, the Amazon formatter, the Dashboard search, the header, the page hook, the activator
and DB update).

**This copy, 2026-11-14:** `wp_lasso_url_variants` has **0 rows**; the site's Amazon tracking ID is set (F17), so R19
would apply to any variant tag. Variants are unused here and built in full (everything Lasso does is in scope).

## 3. What Lasso does

| # | Rule | Source |
|---|---|---|
| R1 | **Storage:** table `lasso_url_variants` — `id`, `parent_lasso_id`, `name` (text), `variant_key` (varchar 191), `variant_kind` (varchar 20), `created_at`, `updated_at`; unique (`parent_lasso_id`, `variant_key`); indexes on parent and (`variant_key`, `variant_kind`); made on activation and DB update | class-url-variants.php 25-72; class-activator.php 104; class-update-db.php 464 |
| R2 | **Two kinds**, chosen from the parent at each request: an Amazon Product parent (`link_type`) takes `amazon_tag` rows, any other `permalink` rows | 17-18, 210-221 |
| R3 | **Keys:** trimmed; `permalink` through `sanitize_title`, `amazon_tag` through `sanitize_text_field` | 80-86 |
| R4 | **The tab:** "Variants" with a count badge (all the parent's rows) in the product's sub-navigation on every product tab (`page=url-variants&post_id=N`, keeping `return_to`) | header.php 137-139, 245-297 |
| R5 | **Toolbar** (right of the tabs, on this tab only): **Add Variant** (plus icon) and a search box, placeholder "Search"; the search filters the loaded rows in the browser by name, key or URL, ignoring case | header.php 306-319; url-variants.js 72-81, 548-553 |
| R6 | **Columns:** Name (tip "Label for this variant; the shareable URL appears under the title."), the second column "Permalink" (tip "Slug segment for this variant’s cloaked URL.") or "Tracking ID" (tip "Amazon associate tag for this variant."), Copy (tip "Copy the full variant URL to your clipboard."), Edit | url-variants.php 23-41; url-variants.js 92-101 |
| R7 | **States:** a spinner while loading; with no rows the head is hidden and "No variants yet", "Create a variant to get a separate permalink or Amazon tracking ID that still points to this link’s primary destination." and an **Add Variant** button show; a failed load shows the empty state (message to the console) | url-variants.js 104-123, 371-430; pages/class-hook.php 1170-1171 |
| R8 | **A row** (view): the name in bold, the URL under it as a link (new tab), the key; **Copy** (copies the URL, no feedback); **Edit**; **Remove** (red) | url-variants.js 185-282 |
| R9 | **The URL:** permalink → `site_url + '/' + key + '/'` (no prefix); Amazon → the parent's destination with `tag=` replaced, or appended, by the key (`encodeURIComponent`); the bare destination when the key is empty | url-variants.js 52-70 |
| R10 | **Add Variant:** a new row at the end, in edit mode, focused and scrolled to; name = the product's name, else "Amazon variant" / "New variant"; key = for Amazon the site's tracking ID, else `<slug>-<n>` from 2 up, the first not in the list (`variant` when the product has no slug) | url-variants.js 33-50, 507-530 |
| R11 | **Edit / Save:** Edit turns the row into two inputs; Save (a spinner, disabled while saving) takes the inputs and stores the **whole list**; one save at a time; a refusal is shown under that row's key field (`role="alert"`, cleared when typing), else as an alert; a good save reloads the list from the answer | url-variants.js 297-330, 140-180, 437-489 |
| R12 | **Remove:** "Remove this variant?" / "Its cloaked URL or tracking branch will stop working. This cannot be undone." / Cancel, **Remove** → the list without that row is stored; a failure alerts and reloads | url-variant-remove.php; url-variants.js 336-361 |
| R13 | **`lasso_get_url_variants`** (`parent_lasso_id`, `nonce` of `lasso_ajax`): bad nonce → 403 "Invalid nonce."; no `edit_post` on the parent → 403 "Unauthorized."; success `{ variants: [rows by id], kind }` | pages/url-variants/class-ajax.php 31-62 |
| R14 | **`lasso_save_url_variants`** (`variants` = JSON of `{ name, variant_key }`): not a list → 400 "Invalid variants payload."; no kind → 400 "Could not determine link type for variants."; a refusal (R15, R16) → 400 with its message; success `{ variants }` | 67-97 |
| R15 | **Validation**, first failure wins: "Invalid link." (no ID) / "Invalid Lasso link." (not a product) / "Invalid variant data." / "Permalink and Tracking ID cannot be empty." / "Duplicate variant slug or tracking ID in the same list."; for permalinks also "Variant permalink cannot match this link’s primary permalink.", "That permalink is already used by another link’s variant.", "That slug is already used by another page or link." (a post or page, published or draft), "That slug is already a Lasso link." (another product's address). Amazon tags are unique only per product | class-url-variants.php 231-284 |
| R16 | **Store:** in a transaction, all the parent's rows deleted, then each inserted (name through `wp_kses_post`, key normalised, kind the expected one); a failure rolls back with "Could not update variants." / "Could not save variants." / (duplicate key) "That permalink or tracking ID is already used for this link."; IDs are new after every save; no limit on the number | 294-346 |
| R17 | **Script data:** `isAmazon`, `defaultName`, `baseSlug`, `siteUrl`, `amazonTrackingId`, `parentTargetUrl`, `parentPublicLink`, `parentLassoId`, and the texts | pages/class-hook.php 1139-1173 |
| R18 | **Redirect:** when no product's slug is the path, a `permalink` row whose key is the path (or `sanitize_title` of it) makes the path answer as its parent (the same response, F24 R21); the main query is swapped the same way; `amazon_tag` rows are never looked up by path; with a cloaked prefix set, variants still answer at the root | class-redirect.php 205-232; class-post-type.php 479-507; class-url-variants.php 135-151 |
| R19 | **Amazon tags:** the Amazon formatter keeps a link's `tag` when it is on the whitelist **or is the key of any product's `amazon_tag` row**, instead of replacing it with the site's tracking ID (per-request cache) — on save, on Add Link, in displays and in Lasso's content scan | class-lasso-amazon-api.php 39-62, 1514-1521 |
| R20 | **Dashboard search:** a product matches when one of its variants' name or key contains the term; an all-digit term also matches a variant's ID | class-lasso-db.php 96-127 |
| R21 | **Nowhere else:** the URL lookup behind Add Link's duplicate rule and the content scan (`get_lasso_post_id_by_url`, `is_lasso_url_exist`) has no variant arm, so a variant permalink in a post is not tied to its product in Locations, and pasting one into Add Link follows it to its destination; displays print the parent's public link | class-lasso-affiliate-link.php 763-807, 2537-2660 (no `Url_Variants`); grep |
| R22 | `apply_amazon_tag_to_url()` exists but nothing calls it | class-url-variants.php 355-366 |
| R23 | **A product that changes kind** (Amazon ↔ other) keeps its rows with their old kind; the tab shows them under the new kind's column and URL rule, and the next save stores them all as the new kind | 210-221; url-variants.js 410-417 |
| R24 | **Saving a product's own Permalink** does not check variant keys; at R18 the product wins | class-lasso-affiliate-link.php 1692-1694; class-redirect.php 205-224 |
| R25 | **Rows in edit mode** that were not saved are sent with their last stored values when another row is saved | url-variants.js 437-445, 297-327 |

## 4. Decisions

Settled from Lasso and the records:

- **D1 Storage:** a table `{prefix}affibloom_url_variants` with Lasso's columns and keys (`parent_id`), made by the
  plugin's schema step; one table because R18 and R19 look keys up across all products.
- **D2 Routes:** `GET /affibloom/v1/products/{id}/variants` → `{ data: { variants, kind } }`;
  `PUT /affibloom/v1/products/{id}/variants { variants: [ { name, key } ] }` → 200 `{ data: { variants } }`, 422
  `affibloom_variant_invalid` with `data.fields` per row (`variants.<i>.key`), 404 `affibloom_product_not_found`;
  `Capabilities::PRODUCTS`, REST nonce (Lasso's `edit_post` and `lasso_ajax` nonce replaced, as everywhere).
- **D3 The tab** is a tab of Affibloom's product page (`…&tab=variants`), with Lasso's count badge on every tab.
- **D4 Variant URLs stay at the root** (`<home>/<key>/`), as Lasso serves them (R9, R18), whatever the prefix.
- **D5 Copy** says "Copied!" for 1.5 s, as the Share kit (Lasso's Copy gives no feedback; nothing is lost).
- **D6 Remove** uses the shared `delete-modal.php` with Lasso's title and body.
- **D7 Uniqueness** also checks F24's old slugs (`_affibloom_old_slugs`), which answer as products (F24 N1).
- **D8 No new matching:** like Lasso, variants are not used by Add Link's duplicate rule, the link index or displays
  (R21). The F24 resolver gets a variant arm; the Amazon formatter gets the tag arm; the Dashboard search gets its arm.
- **D9 Migration:** Task 26H (F10) copies the rows (0 here) with parent IDs mapped.

**Needs the user:**

- **N1 "That slug is already a Lasso link."** names Lasso; the wording rule makes it "That slug is already a
  product's link." Recommendation: that.
  → **Decided 2026-11-14:** D6: the user accepted the recommendation: "That slug is already a product's link."

## 5. Scope that needs a home

- **Task 26H** (F10): the copy of `lasso_url_variants`.
- **Locations of variant links** (R21): Lasso does not tie them; nothing to own unless the user wants more.

## 6. Edge cases

1. A key typed with spaces or capitals becomes its `sanitize_title` form; the stored form is shown after Save.
2. Two editors on one product: the last whole-list save wins (Lasso's rule).
3. A product deleted for good: its variants are deleted with it (Lasso leaves them; a variant of a missing product
   would redirect nowhere); a retired product keeps them and they answer as it does.
4. A page published later with a variant's slug: the page wins (F24 R16).
5. An empty list saved: all variants are removed.
6. An Amazon tag variant with the site's own tracking ID (Add Variant's suggestion) changes nothing in R19.
