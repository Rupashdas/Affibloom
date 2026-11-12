# Interface specification

Status: Proposed (approved together with the rest of Task 6)
Date: 2026-11-12

The names the tests (Task 7) and the code (Tasks 8–36) share: labels, ids and selectors, REST routes, WP-CLI
commands, fields, settings and hooks.

**Sources.** Labels were checked against Lasso's live admin screens (production runs **Lasso 370**, captured
2026-11-12 in `tests/fixtures/baselines/admin/*.jpg`) and, for text a screenshot cannot show (tooltips,
dialogs, toasts, the block editor), against the Lasso 369 source in `D:\lasso\lasso`. Where the plan's
wording differed from Lasso's, Lasso's wording is used here and the change is marked **Changed**.

## 1. Labels and selectors

### Header and Dashboard

| Element | Lasso shows | Label (accessible name) | id / selector | Note |
|---|---|---|---|---|
| Header tabs | Dashboard, Opportunities, Tables, Groups, Analytics, Marketplace | `Dashboard`, `Settings` | `.affibloom-header nav a` | Other tabs belong to excluded features. Lasso reaches Settings from the WordPress menu. |
| Add button | `Add New Link` (split; the caret opens Bulk Add Links) | `Add New Link` | `#affibloom-add-new` | The Add modal takes one link per line, which covers bulk adding. |
| Search | placeholder `Search all 263 links…`, hint `Ctrl+Shift+L` | `Search` (visually hidden), placeholder `Search all <n> links…` | `#affibloom-search` (`name="s"`) | Keyboard shortcut not built. |
| Broken filter | red icon + count, tooltip `See broken URLs` | `Broken links (<n>)` (visible text; the tooltip stays) | `a.affibloom-filter-broken` | **Changed** (U2, approved). |
| Other counters | `See out-of-stock products`, `See opportunities` | — | — | Left out: no Amazon data refresh; Opportunities excluded. |
| Columns | Image, Link Name, Groups, Alert, Locations, Clicks, Actions | Image, Link Name, Alert, Locations, Clicks, Actions | `thead th` | Groups left out: `lasso-cat` has 1 term and 0 assignments on the site. |
| Row | — | — | `tr[data-product-id]` | |
| Alert cell | green tick, or a red icon for a broken link | `Broken link` on the red icon | `td.affibloom-alert`, `.affibloom-alert-broken` | |
| Locations cell | count | — | `td.affibloom-locations` | |
| Clicks cell | count | — | `td.affibloom-clicks` | |
| Row action: quick edit | button `Edit` (title `Quick Edit`), edits the row in place | `Edit` (title `Quick Edit`) | `button.affibloom-quick-edit` | **Changed** from `Quick edit`. |
| Row action: shortcode | button `Shortcode` (copies `[lasso …]`) | — | — | Left out: Affibloom has no shortcode of its own; boxes are placed with the block. |
| Row action: delete | not on the row (see the product page) | — | — | **Changed**: the plan's row `Delete` moves to the product page, as in Lasso (Task 17). |
| Pages | `10` per page, `1-10 of 263`, page numbers | same wording | `.affibloom-pagination` | Page size 10, as Lasso's default. |

### Add A New Link modal

| Element | Lasso shows | Label | id / selector | Note |
|---|---|---|---|---|
| Dialog | `Add A New Link`, `Enter the affiliate link you would like to track.` | `Add A New Link` | `#affibloom-add-modal[role=dialog]` | |
| Tabs | `URL`, `Marketplace` | — | — | Marketplace excluded, so no tabs. |
| Field | textarea, placeholder `https://www.example.com/affiliate-id`, no visible label | `URL` (visually hidden) | `#affibloom-add-urls` | Hint below: `Enter each new link on a separate line (Shift + Enter)`. |
| Submit | `Add Link` | `Add Link` | `#affibloom-add-submit` | |
| Duplicate | `This Product Already Exists` (in Lasso, the repoint panel of the product page's save dialog) | `This Product Already Exists` | `.affibloom-duplicate` | Affibloom shows it in the Add modal (F2 AC4). Lasso's repoint ("change all Locations to that link?") is not built. See section 7 for `Allow duplicate destinations`. |

### Product page

| Element | Lasso shows | Label | id / selector | Note |
|---|---|---|---|---|
| Heading | product name, then its URL as a link | same | `h1`, `.affibloom-product-url` | |
| Share kit | five Copy buttons (Lasso 370) | — | — | Left out (new in 370, not in the feature plan). |
| Tabs | `Details`, `Locations <n>`, `Opportunities <n>`, `Variants <n>` | `Details`, `Locations` | `.affibloom-tabs a` | Opportunities and Variants excluded. |
| Name | `Name` | `Name` | `#affibloom-name` | |
| Primary URL | `Primary Destination URL` | same | `#affibloom-url` | |
| Primary button text | `Button Text` | same | `#affibloom-button-text` | |
| Primary new tab | `New Window / Tab` | same | `#affibloom-new-tab` | |
| Primary nofollow | `NoFollow / NoIndex` | same | `#affibloom-nofollow` | |
| Secondary URL | `Secondary Destination URL` | same | `#affibloom-url2` | |
| Secondary button text | `Button Text` | same | `#affibloom-button-text2` | |
| Secondary new tab | `New Window / Tab` | same | `#affibloom-new-tab2` | |
| Secondary nofollow | `NoFollow / NoIndex` | same | `#affibloom-nofollow2` | |
| Groups | `Groups` | — | — | Left out (unused). |
| Rating | custom-field card `Primary Rating`: show toggle, number, stars; `Add Custom Field` | `Primary Rating` | `#affibloom-rating` | **Changed** from `Rating`. Other custom fields left out. |
| Display Theme | `Display Theme` (`Default (Lab)`) | — | — | Left out: one theme in use. |
| Image | Amazon: `Amazon Image` + `Refresh`; others and quick edit: `Update Image` | `Update Image` | `#affibloom-image-button`, `#affibloom-image-url` | Refresh from Amazon depends on client point 1. |
| Badge | `Badge Text` | same | `#affibloom-badge` | |
| Price | `Price` + toggle | — | — | Left out (price is not shown on the site). |
| Description | `Description` (rich text: bold, italic, underline, strike, link, list, colour, highlight, clear) | same | `#affibloom_description` | |
| Disclosure text | `Disclosure` | `Disclosure` | `#affibloom-disclosure` | **Changed** from `Disclosure Text`. |
| Sponsored | `Sponsored` toggle | `Sponsored` | `#affibloom-sponsored` | **Added** (decision 3): in Lasso, in the feature plan (F9) and in the product meta. Tasks 14–15. |
| Show disclosure | `Show Disclosure` | same | `#affibloom-show-disclosure` | |
| Opportunities, upgrades | `Detect Opportunities`, `Commission Upgrades` | — | — | Excluded. |
| Prime | not on Lasso's page (Lasso takes it from its Amazon data) | `Show Prime Logo` | `#affibloom-prime` | Kept from the plan: without an Amazon data fetch (client point 1) the editor sets it. |
| Save | `Save Changes` | same | `#affibloom-save` | `Learn About This Page` left out. |
| Delete | `Delete This Link` | same | `#affibloom-delete` | **Changed** (decision 4): here, not on the Dashboard row, as in Lasso. |
| Delete dialog | `Delete This Link?` / `If deleted, you won't be able to get it back.` / `Cancel`, `Delete` | same | `#affibloom-delete-modal[role=dialog]` | |
| In use | Lasso: `Hold Up` … `This can't be undone. Do you want to proceed?` and allows it | refused: 409 `affibloom_in_use` | — | Deliberately safer than Lasso (F12 AC3). |
| Toast | `Yee haw! Your <name> saved.` | `Product saved.` | `.affibloom-toast` | **Changed** (U6, approved): wording only. |
| Out-of-stock notice | `This product may be out of stock.` | — | — | Left out (no Amazon data refresh). |
| Unsaved changes | browser prompt + in-page `#unsaved-changes` dialog | same behaviour | — | Lasso already does this, so U7 is parity, not an improvement. |

### Locations tab

| Element | Lasso shows | Label | id / selector |
|---|---|---|---|
| Search | `Search` | `Search` | `#affibloom-locations-search` |
| Columns | `Content` (sortable), `Link with Context`, `Type`, `Clicks` | same | `thead th` |
| Rows | one per post: title, URL, `(<n> locations)`, `Expand to view each location` | same | `tr[data-post-id]` |
| Footer | `1-5 of 5` | same | `.affibloom-pagination` |

### Settings

| Element | Lasso shows | Label | id / selector | Note |
|---|---|---|---|---|
| Tabs | `General`, `Display`, `Amazon` | same | `.affibloom-tabs a` | |
| General | `Link Defaults`: `New Window / Tab`, `NoFollow / NoIndex`, `Sponsored`, `Show Disclosure`, `Allow duplicate destinations`, `Link Cloaking`, `Detect Opportunities`, `Auto-Detect Affiliate Links` | `New Window / Tab`, `NoFollow / NoIndex`, `Sponsored`, `Show Disclosure`, `Auto-Detect Affiliate Links` (C2) | `#affibloom-setting-<key>` | `Allow duplicate destinations` left out (decision 2: Affibloom always behaves as Lasso does with it on, the live value). Cloaking and Opportunities excluded. |
| General | `Google Analytics`: ID, `Click Tracking`, `Pageview`, `IP Anonymization` | `Click Tracking` (C3) | `#affibloom-setting-ga_click_tracking` | No ID field: the site's own GA tag receives the events. Pageview and IP anonymisation belong to that tag. |
| Display (Lasso 370: visual editor) | `Colors & labels`: `Badge`, `Title`, `Background`, `Button + Badge Text`, `Primary button text`, `Primary button color`, `Secondary button text`, `Secondary button color`; `Disclosure & behavior`: `Disclosure text`, `Link from Display Title` | the same labels | `#affibloom-setting-<key>` | Lasso 369, which the plan used, has a plain form. The layout editor, Pros/Cons, Price, Grids/Lists and Brag mode are left out. |
| Amazon | `Tracking ID for This Site`; `Show Prime Logo In Displays` | same | `#affibloom-setting-tracking_id`, `#affibloom-setting-show_prime` | |
| Amazon | `Amazon Product Data API`: locale, `Credential ID`, `Secret`, `Partner tag`, `Version`, `Validate` | same (C1) | `#affibloom-setting-amazon_api_<field>`, `#affibloom-amazon-validate` | `Secret` is a password field and is never sent back to the browser. |
| Save | `Save Changes` | same | `#affibloom-settings-save` | |

### Editor

| Element | Lasso shows | Label | id / selector | Note |
|---|---|---|---|---|
| Block | `Lasso` | `Product box` | `affibloom/product-box` | |
| Modal | `Choose a Display Type`: Single, Grid, List, Table, Button, Image, Gallery, AI Text; then search `Search URLs` and `Add` | opens straight to `Search URLs`, then `Add` | `.affibloom-block-modal` | **Changed** (U1, approved): Single is the only type built. Not captured live (it writes a draft on production). |

## 2. REST routes (`/wp-json/affibloom/v1`)

Capabilities (`src/Interface/Admin/Capabilities.php`, decision 1): `PRODUCTS` = `manage_options`, `SETTINGS` =
`manage_options`, `INSERT_BOXES` = `edit_posts`.

| Method | Path | Capability | Purpose |
|---|---|---|---|
| POST | `/products` `{ urls: string[1–50] }` | `PRODUCTS` | Add A New Link. 201 `{ created, duplicates, invalid }` |
| GET | `/products?search=` | `INSERT_BOXES` | block picker (20 newest matches) |
| GET | `/products/{id}` | `INSERT_BOXES` | block sidebar |
| PATCH | `/products/{id}[?preview=1]` | `PRODUCTS` | product page, quick edit. 422 `data.fields`, 404 |
| DELETE | `/products/{id}` | `PRODUCTS` | moves to trash. 409 `affibloom_in_use`, 404 |
| POST | `/clicks` `{ product \| lasso, post, button }` | public | click counter. Always 204 |
| POST | `/products/{id}/check` | `PRODUCTS` | only with U4 |

Success responses are `{ "data": … }`. Errors are a `WP_Error` with an `affibloom_*` code; field errors go in
`data.fields: { <field>: <message> }`.

**admin-post actions:** `affibloom_save_settings` (`SETTINGS`), `affibloom_export` (`PRODUCTS`).

## 3. WP-CLI

- `wp affibloom migrate [--dry-run] [--update] [--settings] [--report=<file>]`
- `wp affibloom convert-legacy-blocks [--dry-run] [--post=<id>] [--revert]`
- `wp affibloom check-links [--all | --product=<id>]`
- `wp affibloom usage rebuild`

## 4. Product fields and validation (Task 15)

| Field | Meta key | Rule | Reason code → message |
|---|---|---|---|
| Name | post title | required, at most 200 characters | `required` → "This field is required."; `too_long` → "This is too long." |
| Primary Destination URL | `_affibloom_url` (+ `_affibloom_url_key`, `_affibloom_type`, `_affibloom_asin`) | a full `http(s)` link; Amazon links normalised to `https://<host>/dp/<ASIN>?tag=<tracking id>` | `invalid_url` → "This is not a valid link. Paste the full address, starting with https://."; `duplicate` → "Another product already uses this link." |
| Button Text (×2) | `_affibloom_button_text`, `_affibloom_button_text2` | at most 60; empty = site default | `too_long` |
| Secondary Destination URL | `_affibloom_url2` | empty, or as the primary URL | `invalid_url` |
| New Window / Tab, NoFollow / NoIndex (×2), Sponsored, Show Disclosure, Show Prime Logo | `_affibloom_new_tab`, `_affibloom_nofollow`, `_affibloom_new_tab2`, `_affibloom_nofollow2`, `_affibloom_sponsored`, `_affibloom_show_disclosure`, `_affibloom_prime` | boolean | — |
| Image | `_affibloom_image_url` | empty, or a full link | `invalid_url` |
| Badge Text | `_affibloom_badge` | at most 60 | `too_long` |
| Description | `_affibloom_description` | `wp_kses_post`, at most 5,000 bytes | `too_long` |
| Primary Rating | `_affibloom_rating` | empty, or 0 to 5 with one decimal | `invalid_rating` → "Enter a rating from 0 to 5." |
| Disclosure | `_affibloom_disclosure` | at most 500; empty = site default | `too_long` |

## 5. Settings (option `affibloom_settings`) and fresh-install defaults

| Tab | Key | Default | Live Lasso value (imported in Task 26) |
|---|---|---|---|
| General | `new_tab` | on | on |
| General | `nofollow` | on | on |
| General | `sponsored` | off | off |
| General | `show_disclosure` | on | on |
| General | (no `allow_duplicates`) | — | Lasso's `check_duplicate_link` is on; Affibloom's fixed behaviour matches it (decision 2) |
| General | `delete_data_on_uninstall` | off (Task 38) | — |
| Display | `primary_button_text` | `Buy Now` | `<span class="amazon-icon"></span> Buy Now` |
| Display | `secondary_button_text` | `Learn More` | `<span class="pricetag-icon"></span> Check Price` |
| Display | `disclosure_text` | `We earn a commission if you make a purchase, at no additional cost to you.` | same |
| Display | `link_from_display_title` | on | on |
| Display | `color_main` (`Badge`) | `#5e36ca` | `#000000` |
| Display | `color_title` | `#000000` | `#000000` |
| Display | `color_background` | `#ffffff` | `#ffffff` |
| Display | `color_button` (`Primary button color`) | `#22baa0` | `#5e3f8c` |
| Display | `color_secondary_button` | `#22baa0` | `#5e3f8c` |
| Display | `color_button_text` (`Button + Badge Text`) | `#ffffff` | `#ffffff` |
| Amazon | `tracking_id` | empty | `vbvault-20` |
| Amazon | `show_prime` | on | on |
| General | `auto_detect_links` (C2) | off | on (`auto_monetize_affiliates`) |
| General | `ga_click_tracking` (C3) | off | on (`analytics_enable_click_tracking`) |
| Amazon | `amazon_api_locale`, `amazon_api_credential_id`, `amazon_api_secret`, `amazon_api_partner_tag`, `amazon_api_version` (C1) | empty (locale `us`) | empty: Lasso uses its own cloud on the site |

Key names for C1–C3 are proposals; each task confirms them when it is written out.

## 6. Hooks for other code

- Action `affibloom_legacy_box_failed( string $why, array $atts )`: a `[lasso]` box could not be rendered.

## 7. Questions this check raised: decided

All decided on 2026-11-12; reasons and sources in `docs/decisions/task-6-decisions.md`.

1. **Who may manage products:** Administrators. `Capabilities::PRODUCTS` and `SETTINGS` are `manage_options`;
   `INSERT_BOXES` stays `edit_posts`, as Lasso's block search requires.
2. **Duplicate links:** no setting. The same link (same key) is always refused; different links are always
   allowed, which is Lasso's behaviour with `Allow duplicate destinations` on, as on the live site.
3. **Sponsored per product:** added to the Details form (Tasks 14–15).
4. **Delete:** on the product page, as in Lasso (Task 17). A product in use is still refused.
5. **Icon markup:** button texts, badge and the Display button texts accept `<span class="…">` only, and the box
   prints them through the same allowlist (Tasks 10, 15, 18, 20, 26).
