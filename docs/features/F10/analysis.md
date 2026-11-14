# F10 Existing content keeps working: analysis

Status: gate 2 rewritten 2026-11-14 from a sweep of Lasso 369's data model (`requirements.md`); the 2026-11-12 text
(approved with Task 6; decisions in `docs/decisions/task-6-decisions.md`) is superseded, its decisions kept. Gate 4
for Tasks 25A-29E: waiting.

**Tasks** (drafts: `F10-tasks.md`; each family of the migration is a separately testable step of
`wp affibloom migrate --only=<family>` and of the admin page's Run button):

- **25A** dry run: the command, the report, the products family · **25B** dry run: settings · **25C** dry run: every
  other family and the "left behind" inventory · **25D** the report on an admin page (staging has no SSH)
- **26A** products · **26B** products again (`--update`) · **26C** settings: General and Amazon · **26D** settings:
  Display for Single, Grid, List · **26E** custom fields · **26F** groups and their order · **26G** cloaked slugs ·
  **26H** URL variants · **26I** comparison tables · **26J** click totals (dropped: decision E1) · **26K** link issues and dismissals ·
  **26L** Amazon tracking IDs and cached product data · **26M** saved views · **26N** opportunity flags and scan
  scope · **26O** the admin page's Run, in batches · **26P** the check against the baselines
- **28A** conversion dry run (single boxes) · **28B** convert, stored copy, revert · **28C** bare shortcodes ·
  **28D** the other display types · **28E** conversion from the admin page
- **29A** parity: every box · **29B** parity: whole pages · **29C** parity: every link · **29D** parity: converted ·
  **29E** rollback drill
- then Task 41 (cutover). Task 27 (`[lasso]` with Lasso off) is the shortcode sweep's.

## Depends on

- ADR-001 (products are posts), ADR-002 (copy with an ID map; Lasso's data only read), ADR-003 (render and convert).
- F1, F5–F8 (the 2026-11-12 list), and, per data family, what must exist before its migration task can run:

| Family (`--only=`) | Task | Must exist first |
|---|---|---|
| `products` | 26A, 26B | F1 (Tasks 11-17D: the product, statuses `affibloom_retired` and `trash`), F29 (14H: `_affibloom_buttons`), F2 (Task 12: ASIN, country, search key). **Built.** F28 icons need no migration: the icon classes come from the site's own CSS, and texts keep their `<span>` |
| `settings` | 26C | F9 General (Task 9, and the F9 sweep's tasks for the keys of table F marked F9), F17 Amazon (10F). A key whose feature is not built is reported "waiting for <feature>" and imported by a later run |
| `display` | 26D | F16 (10A-10E: `affibloom_display`). **Built** |
| `fields` | 26E | F26 (14C: `affibloom_fields`, `_affibloom_fields`). **Built** for product fields; the table-only fields wait for F25 |
| `groups` | 26F | F23 (G1-G4) |
| `slugs` | 26G | F24 (cloaking: the redirect of `/<slug>/`, old slugs, the rewrite prefix) |
| `variants` | 26H | F27 (URL variants) |
| `tables` | 26I | F25 (comparison tables), 26E, 26A |
| `clicks` | not migrated: decision E1 (26J dropped; start from zero) | F12 (Task 33's click store, Task 34's column) |
| `issues` | 26K | F11 (Tasks 30-31: link checks and the Alert column) |
| `amazon` | 26L | F17 (10F, the tracking-ID table), C1 (the product-data store; dormant without credentials) |
| `views` | 26M | F19 (13E: saved views), F23 G5 (the Group filter), F11 (the alert tabs), F25 (the table filter) |
| `opportunities` | 26N | F21 (link index), F22 (link and domain opportunities) |
| conversion | 28A-28E | Task 23 (the block), Task 27 (what stays a shortcode still renders), 26A; 28D: the blocks for button, image, grid, list, gallery (Phase 3-4 displays) and table (F25) |
| parity | 29A-29E | Tasks 18-22, 27, 26A, 26C, 26D, 26E; 29C: F24 and 26G; the baselines (Task 5; the screenshots are not in git) |

## Data

Touched (the 2026-11-12 summary, still true): read only, Lasso's posts, meta, tables and options; written, products
with `_affibloom_lasso_id`, each feature's own store, and `_affibloom_pre_conversion` on converted posts.

What F10 itself adds to Affibloom's data:

| Where | What |
|---|---|
| Product meta `_affibloom_lasso_id` (exists) | the Lasso record's post ID; the ID map |
| Product meta `_affibloom_migrated` (new, 26A) | a hash of the values the migration gave the product, so `--update` can tell an Affibloom edit from a Lasso edit (D8) |
| Option `affibloom_migration` (new, 26A; not autoloaded) | `{ started, finished, lasso_version, families: { <family>: { done, total, errors } }, field_map: { <Lasso field id>: <Affibloom id> }, group_map, table_map }`; takes over `affibloom_lasso_import` |
| Option `affibloom_migration_lock` (new, 26O) | the running migration or conversion; given up after 10 minutes |
| Content post meta `_affibloom_pre_conversion` (28B) | the post's content before its conversion, stored once |

**The complete inventory of what Lasso stores, and where each item goes.** Verdicts: **M** = migrated, the Affibloom
store exists · **P <feature>** = planned: migrated by the task named, once that feature exists · **D** = derived
data: Affibloom rebuilds it, nothing is copied · **C** = never: state of Lasso's cloud, licence or account · **X** =
not migrated: a cache, log, job state or one-time flag · **N** = not Lasso's (another plugin's data on Lasso's
posts). Every item has a row. Counts: this copy, 2026-11-14.

### A. Custom tables (29 on this copy; DDL in `models/class-<name>.php`, created by `Activator::create_lasso_table()`)

| # | Table · rows here (with real data) | Columns | Written by → read by | Affibloom | Verdict |
|---|---|---|---|---|---|
| T1 | `lasso_url_details` · 256 (256) | `lasso_id` PK, `redirect_url` (the destination Lasso sends to), `base_domain`, `is_opportunity` (Detect Opportunities for this link; 254 on, 2 off), `product_id` (ASIN or store product ID), `product_type` (`amazon` 233, `walmart` 7, '' 16), `country` (all `us`), `search_text` (Amazon search key; 4), `clicks` (a total; 2,839 over all rows) | `Lasso_DB::update_url_details()` on every save; `clicks` by `lasso_sync_clicks` (cloud) → `get_lasso_url()`, Dashboard, opportunities | `redirect_url` → `_affibloom_url` (R9); `product_id` / `country` → `_affibloom_asin`, `_affibloom_amazon_country`; `search_text` → `_affibloom_search_key`; `base_domain` not stored (derived from the URL) | **M** 26A · `is_opportunity` **P F22** 26N · `clicks` **P F12** 26J · `product_type` other than `amazon` (Walmart, 7) **P C1** 26L |
| T2 | `lasso_amazon_products` · 501 (226 belong to a record) | PK (`amazon_id`, `country`); `default_product_name`, `latest_price`, `base_url`, `monetized_url`, `default_image`, `out_of_stock`, `is_prime`, `currency`, `savings_amount`, `savings_percent`, `savings_basis`, `features` (JSON list; all `[]` here), `is_manual`, `rating` (0 here), `reviews`, `last_updated` | Lasso's Amazon jobs (its cloud and PA-API) and `save_lasso_url()` → the box, the out-of-stock alert | `monetized_url` → `_affibloom_url`; `default_image` → `_affibloom_image_url` (R10); `latest_price` → `_affibloom_price`; `is_prime` → `_affibloom_prime`; `default_product_name` → the name when the title is "Amazon" | **M** 26A (the box values of the 226 linked rows) · the full row (currency, savings, features, rating, reviews, `last_updated`, `out_of_stock`) **P C1** 26L · the 275 rows no record uses: **X** (counted in the report) |
| T3 | `lasso_extend_products` · 4 (2 with a price) | PK (`product_id`, `product_type`, `country`); `default_product_name`, `latest_price`, `base_url`, `default_image`, `out_of_stock`, `is_manual`, `last_updated` | Lasso's fetch of other stores' product pages → the price fallback in `get_lasso_url()` 363-377 | price → `_affibloom_price` when the record's own `price` is empty | **M** 26A (the price fallback) · the row itself **P C1** 26L |
| T4 | `lasso_fields` · 10 (1 custom: 99995 "DETAILS", text) | `id`, `field_name` (≤ 80), `field_type` (`text`, `copy_clipboard`, `label`, `textarea`, `number`, `rating`, `button`, `image`, `bulleted_list`, `numbered_list`, `editor`, `product_name`), `field_description`, `order` | activation (9 built-ins: 1 rating, 2 Pros, 3 Cons, 99989 Description, 99990 Product name, 99991 Product Image, 99992 Primary Button, 99993 Secondary Button, 99994 Price) and the Fields screen → product page, box, tables | custom rows → option `affibloom_fields` (new IDs from 100; the map is kept in the migration log); a **renamed built-in** (1 is "Primary Rating" here; Lasso's default is "Our Rating") keeps its name; 99989-99994 are table columns | **M** 26E (custom definitions, built-in names) · 99989-99994 **P F25** 26I |
| T5 | `lasso_field_mapping` · 5 (5: field 1 on 5 products) | PK (`lasso_id`, `field_id`); `field_value` (≤ 1000), `field_order`, `field_visible` | the product page's Fields → box, tables | `_affibloom_fields` `[{field, value, visible, show_name}]` in `field_order` | **M** 26E |
| T6 | `lasso_metadata` · 4 | `id`, `object_id`, `type`, `meta_key`, `meta_value` (≤ 255); unique (`object_id`, `type`, `meta_key`). Types: `Field_Mapping` / `show_field_name_<field id>` (2 here), `Link_Locations` / `Table` → table ID (2 here), `Table_Details` / `show_field_name`, `Table_Field_Group_Detail` / `lasso_webp_thumbnail` | field and table code | `Field_Mapping` → `show_name` in `_affibloom_fields`; `Table_Details`, `Table_Field_Group_Detail` → the table's store; `Link_Locations` / `Table` is index data | **M** 26E (`Field_Mapping`) · **P F25** 26I · `Link_Locations` **D** (F21 finds table displays itself) |
| T7 | `lasso_category_order` · 0 | PK (`item_id` = link, `parent_slug` = group slug); `term_order` | save, the group's Links tab | term meta `_affibloom_order` (F23 D1) | **P F23** 26F |
| T8 | `lasso_url_variants` · 0 | `id`, `parent_lasso_id`, `name`, `variant_key` (a slug, or an Amazon tag), `variant_kind` (`permalink` / `amazon_tag`), `created_at`, `updated_at`; unique (parent, key) | the URL Variants page → the redirect, the click | F27's store (not designed) | **P F27** 26H |
| T9 | `lasso_table_details` · 3 | `id`, `title`, `style` (`Row` / `Column`), `theme`, `show_title`, `show_headers_horizontal` | Tables screens → `[lasso type="table" id=N]` | F25's store | **P F25** 26I |
| T10 | `lasso_table_mapping` · 9 | `id`, `title`, `table_id`, `lasso_id`, `order`, `title_visible`, `badge_text` (icon markup kept) | Tables screens | F25 | **P F25** 26I |
| T11 | `lasso_table_field_group` · 35 | `id`, `table_id`, `lasso_id`, `field_group_id` (`group_<uniqid>`), `field_id`, `order` | Tables screens | F25 | **P F25** 26I |
| T12 | `lasso_table_field_group_detail` · 37 (36 with a value) | `id`, `lasso_id`, `field_id`, `field_group_id`, `order`, `field_value` (text; a button is JSON `{button_text, url}`; an image is a URL) | Tables screens | F25 | **P F25** 26I |
| T13 | `lasso_table_title_field` · absent | dropped by Lasso at v298 (`class-update-db.php` 278-283); its data was moved to T12 | — | — | **X** (a site still below 298 has it: counted, not read) |
| T14 | `lasso_url_issues` · 256 (113 open: 109 out of stock `000`, 3 `404`, 1 `400`) | `id` PK = the link, `detection_date`, `issue_type` (HTTP status; `000` = out of stock; `200` = fine), `issue_slug`, `issue_resolved`, `is_ignored`, `issue_resolved_dt`, `total_404_request` | Lasso's issue check (`check_issues`) and its cloud sync → Dashboard alerts, link-page notices | `{prefix}affibloom_link_checks` (F11): latest status per product, first seen, resolved, ignored | **P F11** 26K (open and ignored rows; `200` and resolved rows as "last checked fine") |
| T15 | `lasso_url_issue_definitions` · 41 | `id`, `issue_type`, `issue_title`, `issue_description` (fixed texts per HTTP code) | activation (`add_default_data`) → issue labels | words in `Messages` (F11) | **X** (static; F11 carries the texts in code) |
| T16 | `lasso_amazon_tracking_ids` · 13 (0 with an ID) | `id`, `country_code`, `country_name`, `amazon_domain`, `pa_endpoint`, `tracking_id` | activation (13 countries), Settings → Amazon → `get_amazon_tracking_ids()` | F17's per-country tracking IDs (10F) | **P F17** 26L (rows with an ID; none here) |
| T17 | `lasso_amazon_shortened_url` · 0 | `shortened_url` PK, `final_url`, `lasso_id` (amzn.to → the resolved link) | Lasso's short-link resolver | Affibloom resolves short links itself (`ShortLinkResolver`) | **X** (cache) |
| T18 | `lasso_amazon_affiliate_plus` · 0 | `id` (old `ASIN_country`), `new_id`, `created_at` | Lasso's cloud "flip" (commission upgrades) | — | **C** |
| T19 | `lasso_link_updates` · 3 | `id`, old (`product_id`, `product_type`, `country`), new (the same three), `source_lasso_id`, `target_lasso_id`, timestamps: an ASIN replaced on a link, or a merge | `save_lasso_url()` 1727-1730, `Lasso_Repoint_Merge::merge()` 205 → Lasso's scan retargets old links | F21's index (old ASIN → product), so a text link to the old ASIN still counts for the product | **P F21** 26N |
| T20 | `lasso_link_locations` · 3,015 (923 Lasso: 667 text, 223 Single, 31 image, 2 Table; 1,304 internal; 788 external) | `id` (stamped into content as `data-lasso-id`), `detection_date`, `link_type`, `display_type`, `anchor_text`, `post_id` (**the link**), `link_slug`, `link_slug_domain`, `md5_link_slug`, `detection_id` (**the post it is in**), `detection_slug`, `tracking_id`, `product_id`, `country`, `no_follow`, `new_window`, `is_ignored` (a dismissed opportunity; 0 here), `original_link_slug`, `affiliate_platform` | Lasso's content scan | F21's link index, rebuilt from content | **D** (F21) · rows with `is_ignored` = 1 **P F22** 26N |
| T21 | `lasso_link_locations_map` · 18 (8 links) | (`link_location_id`, `lasso_id`), timestamps: more links one location counts for | scan, merge | F21 | **D** |
| T22 | `lasso_content` · 214 | per post: `id`, `post_type`, `title`, `permalink`, `last_modified`, `author`, counts of words, headings, images, links, displays, grids, lists, tables, `created_at` | content sync job (sent to Lasso's cloud) | F21 / F22 compute their own counts | **D** |
| T23 | `lasso_tracked_keywords` · 0 | `id`, `lasso_id`, `keyword` | keyword opportunities | — | **X** (keyword opportunities are left out, the user's decision; counted if not empty) |
| T24 | `lasso_keyword_locations` · 0 | `id`, `lasso_id`, `keyword`, `detection_id`, `is_ignored` | keyword scan | — | **X** (as T23) |
| T25 | `lasso_auto_monetize` · 16 (15 Impact, 1 Post Affiliate Pro; all tied to a link) | `id`, `domain`, `deep_link`, `integration`, `affiliate_id`, `advertiser_id`, `url`, `lasso_id`, `url_encrypt` | Lasso's auto-monetize (`classes/auto-monetize`) | the 16 links are products already (26A); the rows record how Lasso detected them | **P F35 / C2** (the detection rules are code; the rows are counted, not copied) |
| T26 | `lasso_revert` · 0 | `id`, `lasso_id`, `post_data`, `old_uri`, `plugin`, `revert_dt`: a link imported from another plugin and its old address | Lasso's Import → the old address redirects (`get_lasso_link_from_old_link`) | F33's import log; `old_uri` → F24's old-address redirects | **P F33** (with 26G for `old_uri`) |
| T27 | `lasso_post_content_history` · 18 (17 posts; 3.9 MB) | `id`, `object_id` (the post), `old_value`, `new_value` (whole post content), `updated_date` | Lasso, each time it rewrote a post (`enable_history`) → History screens, revert | F34 (content history). Affibloom rewrites no content, so it writes no such rows | **P F34** (D14: left in Lasso's table until F34's gate) |
| T28 | `lasso_affiliate_programs` · 15,470 | Lasso's cloud catalogue of affiliate programmes: `id`, `post_title`, `permalink`, `niche`, `type`, `commission_rate`, `lasso_partner_rate`, `lasso_partner_badge`, `signup_page`, `cookie_duration`, `description`, `rating`, `additional_links`, `primary_domain`, `post_modified`, `created_at`, `image_url` | daily sync from Lasso's cloud | — | **C** |
| T29 | `lasso_link_syncing` · 0 | `link_id`, `data`, `modified_at`, `status`: links waiting to be synced from Lasso's hub | cloud sync | — | **C** |
| T30 | `lasso_background_process_runs` · 50 | job run log (`process_class`, `action`, counts, timings) | Lasso's job runner | — | **X** (log) |

### B. The post type `lasso-urls` (265 posts here: 263 `publish`, 1 `trash`, 1 `lasso_delete`)

| # | Field | Lasso | Affibloom | Verdict |
|---|---|---|---|---|
| P1 | `ID` | named by every `[lasso id=…]`, `data-lasso-lid`, tables, variants | `_affibloom_lasso_id` on the product (the ID map; ADR-002) | **M** 26A |
| P2 | `post_title` | the name (HTML entities stored) | `post_title`, entities decoded; "Amazon" → the cached product's name | **M** 26A |
| P3 | `post_name` | the cloaked address `/<slug>/` (Amazon links: `amzn-…`), and `[lasso ref=…]` | the product's `post_name` (F1: slug = Permalink) | **P F24** 26G (26A stores it when it is free; 26G owns collisions and the redirect) |
| P4 | `post_status` `publish` | live | `publish` | **M** 26A |
| P5 | `post_status` `trash` (1: 15113, an empty "add-a-link-title" record) | the Dashboard's bulk delete | `trash` through `wp_update_post` (F1 D43), so adding the link again brings it back | **M** 26A |
| P6 | `post_status` `lasso_delete` (1: 14771 → 14770) | retired by a merge or a hub-sync duplicate | `affibloom_retired` with `_affibloom_repointed_to` = the mapped target | **M** 26A |
| P7 | `post_date`, `post_modified` | the Dashboard's order | written to the product's row after insert | **M** 26A |
| P8 | `post_content`, `post_excerpt`, `post_author`, `guid` | '' or unused | — | **X** |
| P9 | 9 published posts with no meta and no T1 row (14837-14845; slug `amzn-<asin>`) | a title only | no product; listed in the report | **M** 26A (skipped and reported; D6) |

### C. Post meta on `lasso-urls` (posts that have the key · of those, with a value)

| # | Key | Here | Meaning (writer: `save_lasso_url()` 1412-1444 unless said) | Affibloom | Verdict |
|---|---|---|---|---|---|
| K1 | `lasso_custom_redirect` | 256 · 256 | the link as entered and normalised (third choice for the destination) | `_affibloom_url` | **M** 26A |
| K2 | `lasso_final_url` | 256 · 256 | where it ends after redirects (last choice) | `_affibloom_url` | **M** 26A |
| K3 | `affiliate_link_type` | 256 · 256 (226 "Amazon Product", 30 "Basic Link") | the stored type | `_affibloom_type` (decided from the URL, not from this key) | **M** 26A |
| K4 | `affiliate_desc` | 256 · 198 | description (HTML) | `_affibloom_description` | **M** 26A |
| K5 | `show_description` | 256 (22 "0") | hide the description (ignored when it is longer than 10 characters) | not stored: a description Lasso hides is migrated empty (F1 D20) | **M** 26A |
| K6 | `price` | 256 · 186 | the price as typed (HTML allowed) | `_affibloom_price` (an Amazon record takes T2's price) | **M** 26A |
| K7 | `show_price` | 256 (216 "0", 40 '') | per-link Show Price; '' = the setting | `_affibloom_show_price` ('' = the setting) | **M** 26A |
| K8 | `lasso_custom_thumbnail` | 256 · 241 | image URL (28 in the media library, 209 on Amazon's image host) | `_affibloom_image_url` | **M** 26A |
| K9 | `_thumbnail_id` | 18 | the featured image (set when an image is picked from the library) | `_affibloom_image_id` and its URL ("large") | **M** 26A |
| K10 | `lasso_thumbnail_id` | 7 | the picked attachment's ID (save 1793); a fallback image for imported links | `_affibloom_image_id` when K9 is absent | **M** 26A (the present mapper does not read it) |
| K11 | `lasso_thumbnail_url` | 0 | the image of a link imported from another plugin | `_affibloom_image_url` (fallback) | **M** 26A |
| K12 | `lasso_webp_thumbnail` | 131 | a WebP copy of the image in uploads, used by tables (`models/class-table-field-group-detail.php` 379-400) | F25's image rule | **P F25** 26I (counted; the box does not read it) |
| K13 | `buy_btn_text` | 256 · 230 | primary button text ('' = the setting) | `_affibloom_button_text` | **M** 26A |
| K14 | `second_btn_url`, `second_btn_text` | 256 · 60 / 183 | the second button | the first entry of `_affibloom_buttons` (F29 B8) | **M** 26A |
| K15 | `open_new_tab`, `enable_nofollow` | 256 · 256 (all "1") | the primary link's flags (`1 === intval`) | `_affibloom_new_tab`, `_affibloom_nofollow` | **M** 26A |
| K16 | `open_new_tab2`, `enable_nofollow2` | 256 · 256 | the second button's flags | in `_affibloom_buttons[0]` | **M** 26A |
| K17 | `enable_sponsored` | 256 (5 "1") | rel="sponsored" | `_affibloom_sponsored` | **M** 26A |
| K18 | `link_cloaking` | 256 (238 "1": 231 Amazon, 7 other) | show `/<slug>/` instead of the destination (never used for Amazon) | F24's per-product flag | **P F24** 26G |
| K19 | `custom_theme` | 256 · 0 | per-link theme ('' = the display type's) | `_affibloom_theme` | **M** 26A |
| K20 | `disclosure_text` | 256 · 76 | per-link disclosure ('' = the setting) | `_affibloom_disclosure` | **M** 26A |
| K21 | `show_disclosure` | 256 (all "1") | show the disclosure | `_affibloom_show_disclosure` | **M** 26A |
| K22 | `badge_text` | 256 · 198 | badge (icon markup allowed) | `_affibloom_badge` | **M** 26A |
| K23 | `amazon_product_id`, `amazon_country` | 226 / 152 | ASIN and store (Lasso reads T1) | `_affibloom_asin`, `_affibloom_amazon_country` (from T1, else from the URL) | **M** 26A |
| K24 | `amazon_product_last_updated` | 226 | when Lasso last saved the Amazon data | C1's "last refreshed" | **P C1** 26L |
| K25 | `extend_product_type`, `extend_product_id`, `extend_product_last_updated` | 4 | another store's product (Walmart) | C1's store product | **P C1** 26L |
| K26 | `lasso_old_slug` | 17 · 4 | the previous slug after a rename (save 1814-1816) | F24: old addresses that still redirect | **P F24** 26G |
| K27 | `_wp_old_slug`, `_wp_desired_post_slug`, `_edit_lock` | 4 / 1 / 7 | WordPress's own | `_wp_old_slug` as K26; the others not copied | **P F24** 26G · **X** |
| K28 | `lasso_repointed_to_lasso_id` | 1 | the link this retired link was merged into | `_affibloom_repointed_to` (mapped) | **M** 26A |
| K29 | `url_without_arguments` | 0 | duplicate-check key while "Allow duplicate destinations" is off (save 1733-1742) | `_affibloom_url_key` (computed by Affibloom) | **D** |
| K30 | `dismiss_out_of_stock`, `dismiss_broken` | 5 / 0 | a link-page notice dismissed (Unix time; `pages/class-ajax.php` 867-900) | F11's dismissal | **P F11** 26K |
| K31 | `boosting_enabled` | 112 (all "1") | "Commission Upgrades" for this link (a Lasso cloud service) | — | **C** (the value is counted) |
| K32 | `is_aff_plus`, `link_id`, `lasso_shortcode` | 161 / 170 / 56 (all 0 or '') | Lasso hub: upgraded-link flag, hub link ID, lasso.to short code | — | **C** |
| K33 | `is_import_platform`, `old_genius_url`, `old_second_btn_genius_url` | 139 / 138 / 138 (all 0 or '') | Genius and Skimlinks import state | F33 | **P F33** (nothing here) |
| K34 | `custom_css` | 0 | per-link CSS, read by `get_lasso_url()` 290 (no writer in 369) | — | **X** (counted if any) |
| K35 | `lasso_mcp_insert_rcv_<id>` (on content posts) | 0 | receipt of an insert made through Lasso's MCP server (`class-mcp-insert-display.php` 705) | — | **C** |
| K36 | `rank_math_*`, `wpil_*` | 264 / 4 | Rank Math and Link Whisper | — | **N** |
| K37 | legacy `lasso_redirect_url`, `affiliate_homepage`, `is_amazon_page` | 0 | pre-v231 meta, moved to T1 (`models/class-url-details.php` 123-148) | — | **X** |

### D. What Lasso stores on other posts, and in content

| # | Item | Here | Affibloom | Verdict |
|---|---|---|---|---|
| C1 | `<!-- wp:affiliate-plugin/lasso -->` blocks wrapping `[lasso …]` | 248 in 34 published posts (one more in a draft) | rendered by Task 27; converted by 28A-28D with a stored copy | **M** 28x (operator-run; "Needs the user" 1) |
| C2 | bare `[lasso …]` shortcodes | 2 | Task 27; 28C | **M** |
| C3 | `[lasso type="table" id=N]` | 2, in one draft (9492) | F25; 28D | **P F25** |
| C4 | `data-lasso-id="<T20 row>"` on links (every scanned link, internal ones too) | 2,667 links; 178 published posts and pages, 31 synced patterns | left in the content, inert | **X** (content is never edited) |
| C5 | `data-lasso-lid="<link ID>"`, `data-lasso-name` on links to a Lasso link | 676 links, 39 posts, 9 patterns | F21 and F12 resolve it through the ID map (`LassoIdMap`), as `[lasso id=…]` | **M** (read, never rewritten) |
| C6 | links to cloaked addresses (`https://<site>/<slug>/`) | 6 links to 3 links' slugs, in 3 posts | must keep redirecting with Lasso off | **P F24** 26G (blocks cutover) |
| C7 | post meta Lasso writes on **content posts** | none here (only `lasso_mcp_insert_rcv_*`, K35) | — | **C** |
| C8 | attachments: images Lasso uploaded or sideloaded, WebP copies | 28 library images in use | stay in the media library; referenced by URL and ID | **M** 26A (no file is copied) |
| C9 | the site's Additional CSS (`custom_css` post) that styles Lasso's class names | 1 | the reader output keeps Lasso's markup | **N** (the site's own; nothing to migrate) |

### E. Taxonomy and term meta

| # | Item | Here | Affibloom | Verdict |
|---|---|---|---|---|
| X1 | taxonomy `lasso-cat` (name, slug, description) and each link's terms | 1 term ("best volyball shoe"), 0 links | `affibloom_group` terms and memberships | **P F23** 26F |
| X2 | `lasso-lite-cat` (Lasso Lite's taxonomy, read by the importer) | 0 | — | **P F33** |
| X3 | term meta | Lasso writes none on its own terms (only on WP Recipe Maker's, `class-lasso-cron.php` 3876-4307) | — | **N** |

### F. The option `lasso_settings` (autoloaded; 102 keys live, 96 in `SETTINGS_DEFAULT`, `admin/lasso-constant.php` 107-386)

Target: **S** = option `affibloom_settings` (`Domain\Settings\Settings`), **Dp** = option `affibloom_display`
(`Domain\Display\DisplaySettings`: one set per type key `single`, `grid`, `list`, plus `columns`). "(default → live)".

| # | Lasso key | Affibloom | Verdict |
|---|---|---|---|
| S1 | `enable_nofollow` (true → true), `open_new_tab` (true → true), `enable_sponsored` (true → **false**), `show_disclosure` (true → true) | S `nofollow`, `new_tab`, `sponsored`, `show_disclosure` | **M** 26C |
| S2 | `enable_nofollow2`, `open_new_tab2` (true → true) | no key yet: the second button's defaults | **P F9** 26C |
| S3 | `check_duplicate_link` (true → true): "Allow duplicate destinations" | no key (Task 6 decision 2 removed `allow_duplicates`) | **P F9** 26C (conflict, table I) |
| S4 | `link_cloaking` (true → true), `rewrite_slug` ('' → ''), `restrict_prefix` (false → false) | F24's settings | **P F24** 26G |
| S5 | `detect_opportunities` (true → true) | F22 | **P F22** 26N |
| S6 | `cpt_support` (post, page, wp_block → **post, page**), `custom_fields_support` ([] → []) | F21: which post types and custom fields are scanned | **P F21** 26N |
| S7 | `lasso_permission` ("Administrator" → same) | `Capabilities` are constants (decision 1) | **P F9** (reported; a value other than Administrator is flagged for a person) |
| S8 | `general_disable_notification`, `general_disable_tooltip` (false → false) | F9 General | **P F9** 26C |
| S9 | `enable_history` (true → true) | F34 | **P F34** |
| S10 | `enable_logs` (false), `cpu_threshold` (65 → 80), `cron_time_interval` (0), `manually_background_process_limit` (2), `processes_execute_limit` (false), `general_performance_mode` ("balanced"; also option `lasso_general_performance_mode` = "custom") | F9 General → Performance, if its sweep keeps the controls (they tune Lasso's job runner) | **P F9** (reported) |
| S11 | `smart_css_loading` (false), `fontawesome_js_svg` (false) | F9 / F5: load the box CSS only where a box is; the icon font | **P F9** 26C |
| S12 | `keep_original_url` ([]), `auto_monetize_affiliates` (true → true) | F35 / C2: auto-detect affiliate links (on), and the keep-original-URL domains | **P F35** 26C |
| S13 | `analytics_enable_click_tracking` (false → **true**) | C3's "Click Tracking" | **P C3** 26C |
| S14 | `analytics_google_tracking_id` ('' → "371244810"), `analytics_enable_ip_anonymization` (→ true), `analytics_enable_send_pageview` (→ true) | not stored: Affibloom loads no Analytics script (C3, decided 2026-11-12) | **X** (reported) |
| S15 | `performance_event_tracking` (true), `stop_lasso_analytics` (false), `segment_analytics` (true) | Lasso's cloud analytics | **C** |
| S16 | `amazon_tracking_id` ('' → "vbvault-20"), `amazon_multiple_tracking_id` (true → true), `amazon_tracking_id_whitelist` ([] → ["vbvault-20"]) | S `tracking_id`, `multiple_tracking_ids`, `tracking_id_whitelist` | **M** 26C |
| S17 | `auto_monetize_amazon` (false → true), `enable_amazon_prime` (true), `show_amazon_discount_pricing` (false), `auto_upgrade_eligible_links` (true), `amazon_update_pricing_hourly` (true → **false**), `amazon_pricing_locations_only` (true), `force_change_from_link_details` (false), `keep_title_when_url_replaced` (true), `shorten_amazon_affiliate_links` (true), `amazon_mobile_deep_linking` (true), `international_click` (true), `keep_genius_formatting` (true), `enable_site_stripe_post_content_scan` (false), `days_before_out_of_stock_alerts` (0) | S `auto_monetize_amazon`, `show_prime`, `show_discount_pricing`, `commission_upgrades`, `update_pricing_daily`, `pricing_locations_only`, `force_change_from_link_details`, `keep_title_when_url_replaced`, `shorten_amazon_links`, `mobile_deep_linking`, `international_click`, `keep_genius_formatting`, `scan_sitestripe_in_post_content`, `days_before_out_of_stock_alerts` (in that order) | **M** 26C |
| S18 | `amazon_default_tracking_country` ("us"), `general_disable_amazon_notifications` (false), `disable_aawp_description` (false) | no key yet | **P F17** 26C (`disable_aawp_description`: **P F33**) |
| S19 | `amazon_creators_credential_id`, `amazon_creators_secret`, `amazon_creators_version`, `amazon_creators_partner_tag` ('' → '') | C1's credentials | **P C1** 26L ("Needs the user" 3) |
| S20 | `amazon_access_key_id`, `amazon_secret_key` (set on this copy) | Amazon's old PA-API, which Affibloom does not call | **X** (never read; "Needs the user" 3) |
| S21 | `show_price` (true → **false**), `show_disclosure_grid` (false), `show_disclosure_list` (true), `show_disclosure_table` (true), `keep_site_stripe_ui` (false), `link_from_display_title` (true), `disclosure_text`, `enable_brag_mode` (false), `lasso_affiliate_URL` ("https://getlasso.co/"), `custom_css` ('') | S `show_price`, `show_disclosure_grid`, `show_disclosure_list`, `show_disclosure_table`, `keep_site_stripe_ui`, `link_from_display_title`, `disclosure_text`, `enable_brag_mode`, `brag_url` (Lasso's own sign-up address is not carried: it stays ''), `custom_css` | **M** 26C |
| S22 | `theme_name` {single, grid, list} (Cactus ×3 → **Lab, Cutter, Cutter**) | Dp `<type>.theme` | **M** 26D |
| S23 | `display_colors` {type: main, title, background, button, secondary_button, button_text, pros, cons, primary_button_text, secondary_button_text, badge_text} | Dp `<type>.palette` (the colours and the type's labels) | **M** 26D |
| S24 | `display_density` {type: preset, values: gap, padding, spaceBetween, titleSize, titleLines, descSize, descLines, priceSize, buttonSize, imageHeight} (balanced → Single "spacious") | Dp `<type>.density` | **M** 26D |
| S25 | `display_elements` {type: badge, image, title, price, prime, desc, proscons, primary, secondary, timestamp} | Dp `<type>.elements` (`disclosure` is not an element: Lasso drops it, `class-setting.php` 865-878) | **M** 26D |
| S26 | `display_layout` {columns; type: orientation, imagePosition, align, buttonAlign, ctaWidth, mobileOverflow, imageFit} | Dp `columns`, `<type>.layout` | **M** 26D |
| S27 | flat legacy `display_color_main`, `_title`, `_background`, `_button`, `_secondary_button`, `_button_text`, `_pros`, `_cons`, `primary_button_text`, `secondary_button_text`, `badge_text` | a mirror of Single's set; read **only** when `display_colors` is missing (Lasso's own rule, `class-setting.php` 856-859) | **M** 26D (as the fallback) |
| S28 | `display_advanced_customization` (false), `display_advanced_customization_migrated` | Lasso's one-time upgrade flag: true and not yet migrated ⇒ every type's preset is "custom" (`class-setting.php` 970-993) | **M** 26D (the rule); the flag itself **X** |
| S29 | `default_thumbnail` (Lasso's placeholder image file) | the box's "no image" rule (F5) | **X** (a file inside Lasso's folder) |
| S30 | `custom_css_default` ('') | appended to the box CSS (`class-helper.php` 4474) | **P F16** 26D (joined to `custom_css` when not empty) |
| S31 | `license_serial`, `site_id`, `intercom_user_jwt` | Lasso account | **C** (never read by the migration) |
| S32 | `io_import_data_from`, `io_import_options`, `io_import_options_revert` | Import screen state | **X** |
| S33 | `general_bad_link_check`, `cron_prioritize_check`, `amazon_add_tracking_id_to_attribution`, `enable_webp`, `undefined`, `count_all_pages_posts` | no reader in Lasso 369, or a scan counter | **X** (`enable_webp`: counted with K12) |
| S34 | `display_type` ("Single") | the Display screen's selected type | **X** (screen state) |

### G. Other options, transients and cron

| # | Option(s) | Meaning | Verdict |
|---|---|---|---|
| O1 | `lasso_version` (369), `lasso_rc_version`, `lasso_db_migration_lock_v2`, `lasso_recreate_table_time`, `lasso_next_time_recreate_table`, `lasso_move_title_to_group_detail`, `set_table_fields_default_value`, `lasso_is_truncate_amazon_affiliate_plus_table`, `lasso_is_update_correct_product_id`, `lasso_force_scan_all_posts`, `lasso_fixed_lasso_text_domain`, `lasso_delete_duplicate_lasso_posts`, `lasso_lasso_cron_load_slot_rescheduled_v2` | schema version and one-time upgrade flags | **X** (`lasso_version` is read: the report names the Lasso version it read) |
| O2 | `lasso_license_status`, `lasso_license_email`, `lasso_license_hash`, `lasso_end_date`, `lasso_is_startup_plan`, `lasso_install_count`, `lasso_first_install_date`, `lasso_activate_first_time`, `lasso_plugin_activated_at`, `lasso_external_sync_secret`, `lasso_old_update`, `lasso_auto_update` | licence, account, updater | **C** |
| O3 | `lasso_js_domain`, `lasso_full_js_domain`, `lasso_schema_cache_*` (4), `process_sync_*_schema` and `process_sync_*_submission_type` (10), `lasso_submission_date_*` (5), `lasso_affiliate_programs_last_modified`, `lasso_sync_affiliate_programs_index` | Lasso's cloud snippet and data sync | **C** |
| O4 | `lasso_amazon_tracking_id_blacklist` (["vbvault-20"]) | tracking IDs Lasso must not treat as the site's (`class-helper.php` 5039-5084) | **M** 26C → S `tracking_id_blacklist` (the live value lists the site's own ID: flagged in the report) |
| O5 | `lasso_amazon_valid` (1), `lasso_enable_auto_amazon_notification`, `lasso_amazon_credentials_*`, `lasso_amazon_creators_verified_signature` | Amazon credential check state and notices | **X** (C1 validates its own credentials) |
| O6 | `lasso_uninstall_remove_ids`, `lasso_uninstall_remove_cloaking`, `lasso_uninstall_remove_displays` | choices of Lasso's "remove attributes" tool | **X** |
| O7 | `lasso_review_*` (5), `lasso_dashboard_tour_done`, `lasso_enable_scan_notice_after_import` | review card, product tour, notices | **X** |
| O8 | `lasso_processes_queue`, `lasso_disable_processes`, `lasso_remove_attribute_completed`, `lasso_force_to_run_new_scan`, `lasso_force_to_run_scan_link`, `lasso_scan_all_posts`, `lasso_*_page`, `lasso_*_date`, `lasso_bulk_*`, `lasso_cron_is_failed_time`, `lasso_import_all_*`, `lasso_revert_all_*`, `wp_lasso_*_completed_time` (20), `wp_lasso_*_worker_time_limit_sec`, `wp_lasso_bulk_sync_amazon_*` | job runner state | **X** |
| O9 | transients `lasso_launch_darkly_*` (site), process locks, short-link cache, MCP receipts | feature flags from Lasso's cloud, locks, caches | **C** / **X** |
| O10 | cron hooks (29 in `Lasso_Cron::CRONS`; 5 still scheduled here) | Lasso's jobs; cleared on deactivation | **X** |
| O11 | other plugins' options Lasso reads: `aawp_output`, `aawp_api`, `lassolite_settings`, `eafl_settings`, `prlipro_options` | import sources | **P F33** |

### H. User meta

| # | Key | Here | Affibloom | Verdict |
|---|---|---|---|---|
| U1 | `lasso_dashboard_saved_views`: up to 30 `{id, name ≤ 80, filters{search, filter, dashboard_group_term_id, dashboard_link_kind, dashboard_placements, dashboard_comparison_table, order_by, order_type, pageSize}}` | none | `affibloom_saved_views` `{id, name, filters{s, link_type, per_page, …}}` (13E; the group, alert tab, placements, table and order keys join with their features) | **P F19** 26M |
| U2 | `lasso_dashboard_tour_done`, `lasso_dismiss_amazon_bulk_sync_banner_v2` | 2 / 1 users | — | **X** |
| U3 | `manageedit-lasso-urlscolumnshidden` (and `_default`) | 5 users | WordPress's list-table preference for Lasso's post type | **N** |

### I. Where two Affibloom documents disagree about storage (settle before 26A)

| # | Subject | One says | The other says | The code |
|---|---|---|---|---|
| 1 | Second button | overview "Data" and F6 analysis: `_affibloom_url2`, `_button_text2`, `_new_tab2`, `_nofollow2` | F29 analysis and Phase 5's header: `_affibloom_buttons` and `_affibloom_primary_position` | `_affibloom_buttons` (`PostTypeProductRepository` 57-72) |
| 2 | Rating | overview "Data", F8 analysis and Phase 5 Task 25's table: `_affibloom_rating` (one value) | F1 analysis: field 1 in `_affibloom_fields`, with `visible` and `show_name` | `_affibloom_fields` (137-147); there is no `_affibloom_rating` |
| 3 | Display colours and labels | overview "Data" and Phase 5 Task 26's settings map: `color_main` … and `primary_button_text` in `affibloom_settings` | F16: one set per display type | option `affibloom_display` (`OptionDisplaySettingsRepository`) |
| 4 | Duplicates | overview "Data": `allow_duplicates`; Phase 5 Task 26's table: "`check_duplicate_link` → `allow_duplicates` (inverted …)" | Task 6 decision 2 and Phase 5's header: not imported | no key. Lasso's control is "Allow duplicate destinations" (on = allow; not inverted) |
| 5 | Product meta list | overview "Data" (20 keys) | — | the list lacks `_affibloom_amazon_country`, `_search_key`, `_theme`, `_price`, `_show_price`, `_image_id`, `_buttons`, `_primary_position`, `_fields`, `_repointed_to`, and names four keys that no longer exist |
| 6 | Saved views | `UserMetaSavedViewRepository`'s comment: "nothing to carry over" | full scope (deviation 24): everything Lasso stores is migrated | 26M migrates them (none on this site) |
| 7 | The staging import | deviation 87: "not Tasks 25-26" | its Dashboard card and `affibloom_lasso_import` option are in the code | 26A adopts its `_affibloom_lasso_id` links; 26O removes the card |
| 8 | WP-CLI flags | overview: `migrate [--dry-run] [--update] [--settings] [--report=<file>]` | this analysis: `--only=<family>` replaces `--settings` | not built |

## Entry points and request paths

`[lasso]` and the `affiliate-plugin/lasso` block, rendered only while Lasso is inactive, are Task 27's (the
shortcode sweep). F10's own entry points:

```
wp affibloom migrate [--dry-run] [--only=<family>[,<family>…]] [--update] [--force] [--report=<file.csv|.json>]
                                                                                       (25A-25C, 26A-26N)
  → cli_init: CliCommands → MigrateCommand::__invoke()
  → MigrationService::plan( families )            dry run: every family's Step::plan(); nothing is written
     or MigrationService::run( families, update ) each family's Step::run(), in the fixed order below
       LassoSource (SELECT only): records(), settings(), fields(), groups(), tables(), variants(), issues(), …
       LassoIdMap::product_for( lasso id )         `_affibloom_lasso_id` → product ID, in any status
       per family: its mapper (pure) → the owning feature's repository or service
  ← MigrationReport: one line per item { family, Lasso key or ID, action, target, note }, a summary per family, and
     the "left behind" list (verdicts C and X, with counts). Exit code 1 when a family has an error.
  Family order: settings, display, fields (definitions), products, fields (values), groups, slugs, variants, tables,
  clicks, issues, amazon, views, opportunities. A family whose feature is not installed reports
  "waiting for <feature>" and writes nothing.
  Actions: create | update | unchanged | skip (with the reason) | retire | trash | waiting | error.

GET admin.php?page=affibloom-migrate                                                                  (25D)
  → AdminMenu (Capabilities::SETTINGS) → MigrationScreen::render() → MigrationService::plan( all )
  ← templates/admin/migrate.php: a card per family (found, to create, to update, skipped, waiting), the left-behind
     list, "Download report (CSV)" (admin-post `affibloom_migration_report`), the Run button, the last run's result

POST /affibloom/v1/migration/run { family, cursor, update }                                           (26O)
  → MigrationController (SETTINGS, REST nonce) → MigrationRunner::batch( family, cursor, 25 )
       option lock `affibloom_migration_lock` (taken over after 10 minutes); option `affibloom_migration`
       { started, finished, lasso_version, families: { <family>: { done, total, errors } }, field_map, group_map }
  ← 200 { family, cursor | null, done, total, lines }   409 affibloom_migration_running   422 affibloom_unknown_family
  migrate.js walks the families in order, one batch per request, and draws the progress.

wp eval-file tools/compare-migration.php                                                              (26P)
  → every box of tests/fixtures/baselines/boxes.json against its migrated product (LassoIdMap + the box builder):
    title, badge, description, image, Prime, disclosure, each button's href, text, rel, target

wp affibloom convert-legacy-blocks [--dry-run] [--post=<id>] [--revert]                               (28A-28D)
  → ConvertBlocksCommand → LegacyBlockConverter::convert( content ) → ConversionResult
       parse_blocks → affiliate-plugin/lasso (and, from 28C, core/shortcode and bare shortcodes) → the Affibloom block
       of its display type, or left exactly as it is, with a reason
  → write: `_affibloom_pre_conversion` (once per post), wp_update_post (a revision)
  → revert: content ← the stored copy; the meta deleted
POST /affibloom/v1/migration/convert { cursor, revert }   (28E; the same lock; one post per request)

npm run test:e2e -- parity   [PARITY_MODE=converted]                                                  (29A-29D)
bash tools/lasso-fingerprint.sh                                                                       (29E)
```

## Layers

- **Domain** `Migration\LassoRecord` (exists; gains `status`, `slug`, `fields`, `terms`, `issue`), `Migration\LassoSource`
  (exists; gains the other families' reads), `Migration\Family` (enum), `Migration\MigrationLine`,
  `Migration\MigrationReport`, `Migration\LassoSettings` (the option as Lasso reads it: defaults merged, R20's
  normalising rules), `Migration\LegacyBox` (a parsed `[lasso]`), `Migration\ConversionResult`. No WordPress calls.
- **Application** `Migration\LassoRecordMapper` (exists; extended to R9-R10 in full), `Migration\SettingsMapper`,
  `Migration\DisplayMapper`, `Migration\FieldMapper`, one `Migration\Step` per family (`ProductsStep`, `SettingsStep`,
  …; `plan()` and `run()`), `Migration\MigrationService` (order, report), `Migration\MigrationRunner` (batches, lock),
  `Migration\LegacyBlockConverter`. `LassoImportService` is removed by 26O.
- **Infrastructure** `Lasso\WpLassoSource` (exists; SELECT only; a missing table or column reads as empty),
  `Lasso\LassoIdMap` (`_affibloom_lasso_id` lookups, cached per request; its second caller is Task 27's shortcode),
  `WordPress\OptionMigrationLog` (takes over `PostMetaLassoImportLog`'s option; the meta key stays).
- **Interface** `Cli\{CliCommands, MigrateCommand, ConvertBlocksCommand}`, `Admin\MigrationScreen`,
  `Rest\MigrationController`, `templates/admin/migrate.php`, `assets/admin/migrate.js`. `LassoImportController` and
  the Dashboard's import card are removed by 26O.

## Facts from Phase 1 (kept; checked again 2026-11-14)

- 250 boxes: 248 blocks and 2 bare shortcodes, 11 attributes in use; `title_url` and `title_type` added to Task 27 (Task 4).
- 9 records have no link and are skipped; 7 Amazon records have no cached data. (Both confirmed: 14837-14845 have no
  meta and no `lasso_url_details` row; 7 of 233 Amazon rows have no `lasso_amazon_products` row.)
- One record serves four different boxes on `best-womens-volleyball-shoes` through per-box title, image and URL overrides.
- New on 2026-11-14: 3 comparison tables with 9 products (used only in a draft); one custom field ("DETAILS") and a
  renamed built-in ("Primary Rating"); 6 in-content links to 3 cloaked addresses; 4 old slugs; 113 open link issues
  and 5 dismissed notices; 2,839 clicks as per-link totals; no Lasso boxes inside synced patterns; no saved views,
  variants, group members or per-country tracking IDs.

## Risks

- Editors keep adding products in Lasso until cutover: `--update` re-runs the migration. Every family must be safe to
  run any number of times; `tools/inventory.php` is run again before Tasks 40 and 41.
- The guide screenshots are not in git: run the baseline capture before Task 29.
- **Six in-content links use cloaked addresses** (`/aircat/`, `/total-attack-volleyball-serving-machine/`,
  `/under-armour-official-store-free-shipping-available-2/`). With Lasso off they are 404 until F24 and 26G exist. The
  plan's parity run compared buttons only and would not have seen it: 29C checks every link.
- **Amazon values go stale.** The price, image, name and Prime flag copied from Lasso's cache are a snapshot; Amazon
  wants them refreshed within 24 hours. The site shows no price (`show_price` false, element `price` off) and the
  images are Amazon-hosted links. C1 owns the refresh.
- **Tables and variants have no Affibloom store yet.** F25 and F27 must keep an ID map (Lasso table ID, variant key),
  so `[lasso type="table" id=N]` and variant addresses keep working; 26I and 26H wait for them.
- **Time limits on staging.** 265 records took about 30 s in one request (deviation 87): the admin run works in
  batches of 25 with a cursor.
- **Lasso's own upgrades** run on its admin pages (R3) and may change its tables between two runs: the source reads
  columns by name and treats a missing column or table as empty.
- **Staging already holds 254 products** from the one-time import. 26A must adopt them through
  `_affibloom_lasso_id`, not create 254 more.
- **Conversion edits post content**, the one place in the plugin that does. It is operator-run, per post, with a
  stored copy and a revision ("Needs the user" 1).

## Security

- WP-CLI, and (new) an admin page and REST routes for staging, which has no SSH: `Capabilities::SETTINGS`
  (`manage_options`), the REST nonce through `wp.apiFetch`; the report download is an `admin-post` action with its own
  nonce. A database backup before each write run: the page shows the reminder and wants a tick before Run.
- Lasso's data is never written. It is read with `SELECT` only, through `$wpdb->prepare()`; no Lasso class is loaded,
  so the migration works with Lasso active or not and can never start a Lasso job or a call to its cloud.
- Secrets: `license_serial`, `site_id`, `intercom_user_jwt`, `amazon_access_key_id`, `amazon_secret_key`,
  `lasso_license_*`, `lasso_external_sync_secret` are never read (the source's key list leaves them out); C1's
  credentials appear in the report as "set" or "empty" only.
- Every migrated text goes through the same cleaners as the product page (`ProductInput`; the `wp_kses` rules for
  description, price, badge and button texts), so a hostile value in Lasso's data cannot reach a page unescaped.

## Tests

- **Unit:** `LassoRecordMapper` (every row of R9 and R10 against records from `tests/fixtures/inventory/products.json`:
  the flags, the four URL choices, the "Amazon" title, the 10-character description rule, the image order with
  `lasso_thumbnail_id`, the Amazon and store price, the three statuses); `SettingsMapper` and `DisplayMapper` against
  `settings.json` (every key of table F: its target and value, or its verdict; the flat-colour fallback; the "custom"
  preset rule); `FieldMapper`; the report's sums; `LegacyBlockConverter` (each display type, each attribute that makes
  a box stay a shortcode).
- **Integration:** each `Step::run()` twice against a seeded Lasso data set (created once, nothing the second time);
  `--update` and its "changed on both sides" rule; the lock; Lasso's rows unchanged (the fingerprint).
- **E2E acceptance** (`tests/E2E/acceptance/F10.spec.ts`, `@local`): AC1 (25A), AC3 (28B), AC4-AC6 (25B-25D),
  AC7-AC9 (26A, 26B), AC10-AC11 (26C, 26D), AC12-AC21 (26E-26N; each skipped until its feature exists), AC22 (26O),
  AC23 (every write task), AC24 (26P), AC25-AC28 (28A, 28C, 28D, 28E), AC2 and AC29-AC31 (29A-29D,
  `tests/Visual/parity.spec.ts`), AC32 (29E).
