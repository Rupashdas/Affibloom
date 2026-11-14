# F10 Existing content keeps working: requirements

Status: gates 1-2 rewritten 2026-11-14 from a sweep of Lasso 369's data model and of this copy's database (feature
F10, widened to **everything Lasso stores**). The 2026-11-12 text (approved with Task 6; decisions in
`docs/decisions/task-6-decisions.md`) is kept: AC1-AC3 keep their numbers and meaning. Gate 4 for Tasks 25A-29E: given by the user 2026-11-14.

## 1. User stories and acceptance criteria

- As the site owner, all 250 existing boxes in 34 guides keep displaying when Lasso is switched off, and every product
  moves over automatically. (2026-11-12)
- As the site owner, **everything I set up in Lasso** moves over: every product field, the settings of all three
  tabs, groups, custom fields, tables, link variants, cloaked addresses, click counts, link alerts and saved views.
- As the developer running the cutover, I see before anything is written what will happen to every Lasso record and
  setting, I can run the move again at any time, I can run it on staging without SSH, and I can go back to Lasso with
  its data exactly as it was.

Task 7 turned AC1-AC3 into `tests/E2E/acceptance/F10.spec.ts`; AC4-AC32 join it with their tasks.

**Dry run and report**

- **AC1** The migration report accounts for all 263 records. (25A; R4-R6. 2026-11-14: the 263 are the published
  records; the report also lists the 2 unpublished ones, so all 265 `lasso-urls` posts have a line: here 254 "create",
  9 "skip: no link", 1 "retire", 1 "trash".)
- **AC4** `wp affibloom migrate --dry-run --only=settings,display` lists **every key** of Lasso's settings option with
  its Affibloom setting and value, or the reason it is not carried ("waiting for <feature>", "Lasso cloud", "not
  used"); no secret's value is printed; nothing is written. (25B; R20, table F)
- **AC5** `wp affibloom migrate --dry-run` covers every family, and ends with a "left behind" list that names every
  Lasso table and option group it does not copy, with its row count and its reason; the families' and the list's
  tables together are all of Lasso's tables on the site. (25C; tables A, G)
- **AC6** Affibloom → **Migrate from Lasso** (`admin.php?page=affibloom-migrate`) shows the same report as cards per
  family, with "Download report (CSV)"; opening the page writes nothing. (25D)

**The migration** (each family: `wp affibloom migrate --only=<family>`, and the admin page's Run)

- **AC7** `--only=products` creates one product for every Lasso record that has a link, with the record's ID in
  `_affibloom_lasso_id` and Lasso's dates; running it again reports "0 to create" and changes nothing; products the
  one-time staging import made are recognised, not duplicated. (26A; R8-R10, D7)
- **AC8** A retired Lasso record (`lasso_delete`) becomes a retired product that points at its target's product; a
  trashed record becomes a product in the trash; a record with no link becomes nothing and is listed. (26A; R5-R6, D5-D6)
- **AC9** `--update`: a record changed in Lasso since its migration changes its product; a product edited in Affibloom
  since is left as it is and reported "changed on both sides", unless `--force`. (26B; D8)
- **AC10** `--only=settings`: Settings → General and → Amazon show the site's Lasso values (here: Sponsored off, Show
  Price off, tracking ID `vbvault-20` with its whitelist, the Amazon switches as in table F); a second run reports
  "unchanged". (26C; R20, table F)
- **AC11** `--only=display`: Settings → Display shows, for Single, Grid and List each, Lasso's theme (here Lab, Cutter,
  Cutter), colours, labels, density, elements and layout, and the column count. (26D; table F S22-S28)
- **AC12** `--only=fields`: the site's own field definitions exist (here "DETAILS"), built-in fields keep a changed
  name (here "Primary Rating"), and every product has its fields with value, order, "visible" and "show field name"
  as in Lasso; a box with a rating shows the baseline's stars. (26E; R12)
- **AC13** `--only=groups`: every Lasso group is a group with the same name, slug, description, products and order.
  (26F; F23 R1, R12)
- **AC14** `--only=slugs`: with Lasso off, the address `/<slug>/` of every cloaked link, and every old slug, answers
  with the redirect Lasso gave (same status, same destination). (26G; R4, R8, table D C6)
- **AC15** `--only=variants`: every URL variant (address or Amazon tag) of a product works as it did. (26H; R15)
- **AC16** `--only=tables`: every comparison table exists with its products, their order, badges, columns and values,
  and `[lasso type="table" id=N]` shows it. (26I; R14)
- **AC17** (not built: decision E1: start from zero) `--only=clicks`: the Dashboard's Clicks column shows each product's Lasso total. (26J; R11)
- **AC18** `--only=issues`: a product Lasso shows as broken or out of stock shows the same alert before Affibloom's
  first check; a dismissed notice stays dismissed. (26K; R16)
- **AC19** `--only=amazon`: per-country tracking IDs, and each Amazon product's cached data (name, image, price,
  currency, savings, Prime, stock, last update), are in Affibloom's Amazon store. (26L; R17)
- **AC20** `--only=views`: each editor's saved Dashboard views are in Affibloom's views menu and filter the same
  products. (26M; R22)
- **AC21** `--only=opportunities`: each product's "Detect Opportunities" switch, every dismissed opportunity and the
  scan's post types are as in Lasso. (26N; R18)
- **AC22** On the admin page, **Run** migrates family by family in batches with a progress line; a second tab is told
  a migration is running; a run the server cut off continues from where it stopped; the page then shows the result.
  The Dashboard's one-time "Import from Lasso" card is gone. (26O; D13)
- **AC23** No run changes Lasso's data: the fingerprint of Lasso's tables, `lasso-urls` posts and meta, terms and
  `lasso_settings` is the same before and after. (every write task; first tested in 26A; R2, D17)
- **AC24** `tools/compare-migration.php` finds, for every one of the 250 baseline boxes, the same title, badge,
  description, image, Prime, disclosure and buttons (link, text, `rel`, `target`) from the migrated product; a
  difference that stays is written in `docs/parity.md`. (26P)

**Legacy block conversion**

- **AC3** (not built: decision A1) Legacy blocks convert, and can be reverted. (28B; revert restores the stored content byte for byte.)
- **AC25** (not built: decision A1) `wp affibloom convert-legacy-blocks --dry-run` lists, per post, the boxes it would convert and the boxes
  that stay as they are, each with its reason; nothing is written. (28A)
- **AC26** (not built: decision A1) A bare `[lasso …]` shortcode (in a shortcode block, a paragraph or classic content) is converted by the
  same rules, or reported. (28C)
- **AC27** (not built: decision A1) Boxes of the other display types (button, image, grid, list, gallery, table) convert to their Affibloom
  blocks, or stay and are reported while that block does not exist. (28D)
- **AC28** (not built: decision A1) The admin page converts and reverts, one post per request, behind the same lock and a backup tick. (28E)

**Parity and rollback**

- **AC2** With Lasso off, all 34 guides match their baselines and every button `href` matches. (29A: every box's
  values and buttons; 29B: the whole page at 1280 and 390 px.)
- **AC29** With Lasso off, **every link** in each guide's article has the `href` it had, and every cloaked address
  redirects as before. (29C)
- **AC30** With Lasso off, no guide requests anything from Lasso's services. (29C)
- **AC31** (not built: decision A1) AC2 and AC29 hold again after the blocks are converted, and after the conversion is reverted. (29D)
- **AC32** Rollback: with Affibloom off and Lasso on again, the guides match the baselines and Lasso's fingerprint is
  unchanged. (29E)

## 2. Evidence

Lasso 369 (`D:\lasso\lasso`), all read: `classes/class-activator.php`, `class-deactivator.php`, `class-update-db.php`,
`class-lasso-db-script.php`, `class-setting.php`, `class-setting-enum.php`, `class-post-type.php`,
`class-lasso-repoint-merge.php`, `class-dashboard-saved-views.php`, `admin/lasso-constant.php`; every file in
`models/` (the DDL, defaults and upgrade steps of each; `class-model.php`'s table plumbing; the query helpers of the
table models skimmed); `classes/class-lasso-affiliate-link.php` 1-105 (constants), 105-632 (`get_lasso_url`),
756-807 (`is_lasso_url_exist`), 1085-1897 (`save_lasso_url`), 2116-2196 (retiring a duplicate), 2795-2868 (thumbnail);
`classes/class-lasso-db.php` 4007-4126 (`update_url_details`, the issue upsert) and its function list for the tables
each query reads; `classes/class-lasso-cron.php` 120-200 (the cron list), 5833-5902 (click sync);
`classes/class-enum.php` (option names); `classes/class-helper.php` 6170-6205 (option prefix, sync secret);
`pages/class-ajax.php` 867-900 (dismissals), 1941-1975 (bulk remove); `pages/url-details/class-ajax.php` 269-283
(delete). Greps over the whole plugin for `update_post_meta` / `get_post_meta` / `delete_post_meta`, `update_option` /
`get_option` / `Helper::update_option`, `*_user_meta`, `*_term_meta`, `*_transient`, `MetaData::update_metadata`,
`lasso_delete`, and each settings key of table F.

Affibloom, read: `docs/plans/00-overview.md` (decisions, "API and data contract"), Phase 5 (Tasks 25, 26,
28, 29), `progress.md` deviations 11, 16, 18, 24, 87, `docs/decisions/ADR-001…003.md`, every
`docs/features/*/analysis.md` "Data" section, `src/Application/Migration/*`, `src/Domain/Migration/*`,
`src/Infrastructure/Lasso/WpLassoSource.php`, `src/Infrastructure/WordPress/*` (stored keys), `src/Domain/Product/Product.php`,
`src/Domain/Settings/Settings.php`, `src/Domain/Display/{DisplaySettings,DisplayConfig}.php`, `src/Domain/Fields/*`
(shapes), `src/Interface/Admin/LassoImportController.php`, `tools/README.md`, `tools/seed-from-lasso.php`,
`tests/fixtures/inventory/*`, `tests/E2E/acceptance/F10.spec.ts`.

**This copy, 2026-11-14** (SELECTs only; Lasso inactive): 29 `wp_lasso_*` tables; 265 `lasso-urls` posts (263
published, 1 `trash`, 1 `lasso_delete` pointing at 14770); 256 records with Lasso data (233 Amazon, 7 Walmart, 16
other), 9 published posts with a title only (14837-14845, slugs `amzn-<asin>`); 226 Amazon records with a cached
row, 7 without; 183 second-button texts and 60 second-button links; 238 records with link cloaking on (231 Amazon,
7 other), 6 in-content links to 3 cloaked addresses, 4 old slugs; 1 group with no products; 10 fields (1 custom,
1 renamed), 5 products with a rating; 3 tables with 9 products, used by 2 shortcodes in one draft; no variants;
256 issue rows (113 open) and 5 dismissed notices; 2,839 clicks as totals; `lasso_settings` with 102 keys (96 have a
default; 7 are secrets or account data); 80-odd other `lasso_*` options, all job, licence or sync state except the
tracking-ID blacklist; no saved views; 248 Lasso blocks and 2 bare shortcodes in 34 published posts, 1 more block in
a draft, none in synced patterns; 2,667 links stamped `data-lasso-id`, 676 of them `data-lasso-lid`. Affibloom's side
holds test leftovers (254 products with `_affibloom_lasso_id` from a trial import; no `affibloom_display` option).
The full table: `analysis.md`, "Data".

## 3. What Lasso does

| # | Rule | Source |
|---|---|---|
| R1 | **Activation** creates 29 tables with `dbDelta` and fills three with defaults: 41 issue definitions, 13 Amazon countries (no tracking IDs), 9 built-in fields. It sets `lasso_version`, `lasso_plugin_activated_at`, `lasso_force_to_run_new_scan`, the setting `auto_monetize_amazon` = true, and schedules 29 cron hooks | class-activator.php 36-57, 83-147; models' `add_default_data()` |
| R2 | **Deactivation** only clears the cron hooks and resets two options. Lasso has no uninstall file or hook: its tables, options, posts, meta and terms stay when it is switched off or deleted | class-deactivator.php 19-35; no `uninstall.php`, no `register_uninstall_hook` in the plugin |
| R3 | **Schema upgrades** run on Lasso's admin page loads (not AJAX, not cron), under the lock option `lasso_db_migration_lock_v2`, gated by `lasso_version` and `lasso_rc_version`: tables are re-created, columns added or dropped, data rewritten (`lasso_table_title_field` dropped at 298; `amazon_product_id` meta split into ASIN + `amazon_country` at 362). So Lasso's tables can differ between sites and between two days on one site | class-update-db.php 38-83, 130-538; class-lasso-db-script.php 258-507 |
| R4 | **A link is a post** of type `lasso-urls`: `post_title` = name, `post_name` = the slug of its cloaked address (Amazon links get `amzn-`), `post_content` '', status `publish`. The type is public unless `restrict_prefix`, supports title and thumbnail, has the taxonomy `lasso-cat`, and takes its rewrite prefix from the setting `rewrite_slug` | class-post-type.php 91-147; class-lasso-affiliate-link.php 1406-1411; class-lasso-db-script.php 922-946 |
| R5 | **Statuses.** `publish`: live. `trash`: the Dashboard's bulk delete (`wp_update_post` to `trash`, only for links with no locations). Deleted for good: "Delete" on the link page (`wp_delete_post( id, true )`). `lasso_delete`: retired by a merge or as a hub-sync duplicate; the post keeps its data and gains `lasso_repointed_to_lasso_id`. `get_lasso_url()` gives real data only for `publish` | pages/class-ajax.php 1941-1975; pages/url-details/class-ajax.php 269-283; class-lasso-repoint-merge.php 181-199; class-lasso-affiliate-link.php 217, 2116-2196 |
| R6 | **A retired link resolves to its target**: follow `lasso_repointed_to_lasso_id` up to 50 hops, stopping at a missing or wrong-type target or a loop. A shortcode's link is found by `id`, else `link_id` (a link-location row), else `ref` (the slug, any status). A merge moves the link's locations to the target, records the change in `lasso_link_updates` and never rewrites post content | class-lasso-repoint-merge.php 29-104, 115-210 |
| R7 | **Refusals on save:** "No data to save.", "Name and Primary Destination URL are required.", "This link is already exists. Please try again with a different URL." (or the repoint offer when the other link is published). Duplicate check: with "Allow duplicate destinations" on, the entered link is compared; off, the final destination without its query string | class-lasso-affiliate-link.php 1124-1140, 1258-1303, 763-807 |
| R8 | **What a save writes.** Meta: `lasso_custom_redirect`, `lasso_final_url`, `lasso_shortcode`, `affiliate_desc`, `price`, `lasso_custom_thumbnail`, `enable_nofollow`, `open_new_tab`, `enable_nofollow2`, `open_new_tab2`, `link_cloaking`, `custom_theme`, `disclosure_text`, `badge_text`, `buy_btn_text`, `second_btn_url`, `second_btn_text`, `show_price`, `show_disclosure`, `show_description`, `enable_sponsored`, `link_id`, `is_aff_plus`, `boosting_enabled`, `is_import_platform`, `old_genius_url`, `old_second_btn_genius_url`, `affiliate_link_type`; for Amazon `amazon_product_id`, `amazon_country`, `amazon_product_last_updated`; for other stores `extend_product_type`, `extend_product_id`, `extend_product_last_updated`. Then the `lasso_url_details` row; `url_without_arguments`; the groups and their order rows; the thumbnail (`set_post_thumbnail`, `lasso_custom_thumbnail` = its URL, `lasso_thumbnail_id`); `lasso_old_slug` when the slug changed; a row in `lasso_link_updates` when the product ID changed | class-lasso-affiliate-link.php 1412-1444, 1572-1582, 1649-1657, 1696-1830; class-lasso-db.php 4007-4081 |
| R9 | **How Lasso reads a link for its box** (the mapping must give the same): a flag is on only when `1 === intval( meta )`; `show_price` '' means the setting; an empty button text or disclosure means the setting's; the description is shown when it is longer than 10 characters, whatever `show_description` says, and a missing `show_description` means on. An Amazon link with a cached row takes Amazon's monetized link as its destination, Amazon's price, Amazon's description when its own is empty, and Amazon's name when its title is just "Amazon". Another store's cached price is used when the link's own price is empty. Destination otherwise: `lasso_url_details.redirect_url`, healed from `lasso_custom_redirect`, then `lasso_final_url`. `rel`: `nofollow`, then `noopener` when it opens a new tab, then `sponsored`; the second button gets `sponsored` only with its own nofollow | class-lasso-affiliate-link.php 217-482 |
| R10 | **The image:** `lasso_custom_thumbnail`; else the featured image ("large"); else `lasso_thumbnail_url`, else the attachment `lasso_thumbnail_id`, and for an Amazon link the cached Amazon image; Lasso's own placeholder file means "none"; an own-site `http://` image is served as `https://` | class-lasso-affiliate-link.php 2795-2847 |
| R11 | **Not migrated (decision E1, 2026-11-14): start from zero.** **Clicks** are not recorded on the site. Once a day Lasso asks its cloud (`https://lasso.link/clicks`, with the site URL) for a total per link, sets every `lasso_url_details.clicks` to 0 and writes the totals it got. There is no per-day or per-post history locally | class-lasso-cron.php 5833-5902; models/class-url-details.php 92, 100 |
| R12 | **Fields.** Definitions in `lasso_fields` (name ≤ 80, type, description, order; the 9 built-ins are re-asserted on upgrade but keep an edited name). A product's fields in `lasso_field_mapping` (one row per product and field: value ≤ 1000 characters, order, visible; a new rating starts at 3.5). "Show field name" is a row in `lasso_metadata` (`Field_Mapping`, `show_field_name_<field id>`). Field 1 is the box's star rating, drawn in a fixed place | models/class-fields.php 16-24, 77-87, 109-150; class-field-mapping.php 49-56, 103-126, 169-184; class-lasso-affiliate-link.php 488-498 |
| R13 | **Groups:** taxonomy `lasso-cat`; order in `lasso_category_order` (link, group slug, position) | F23 requirements R1, R12; models/class-category-order.php 43-48 |
| R14 | **Tables:** `lasso_table_details` (title, style `Row` or `Column`, theme, show title, horizontal headers; "show field name" in `lasso_metadata`), `lasso_table_mapping` (a product in a table: order, own title, title visible, badge), `lasso_table_field_group` (per table and product: a column group, a field, order), `lasso_table_field_group_detail` (the value per product, field and group: text; a button as JSON `{button_text, url}`; an image as a URL). A placed table is the shortcode `[lasso type="table" id=<table id>]` | models/class-table-*.php (DDL); models/class-metadata.php 87-120 |
| R15 | **URL variants:** `lasso_url_variants` (parent link, name, key, kind). A `permalink` variant is one more cloaked slug for the link, unique across the site; an `amazon_tag` variant is one more tracking ID for an Amazon link. Keys are normalised (`sanitize_title` / `sanitize_text_field`); a link's variants are replaced as a set, in a transaction | models/class-url-variants.php 17-18, 53-65, 80-86, 231-346; class-post-type.php 486-507 |
| R16 | **Issues:** one row per link in `lasso_url_issues` (HTTP status, `000` = out of stock, `200` = fine; first detection kept until it is resolved; resolved flag and date; ignored flag). Dismissing a link-page notice writes the post meta `dismiss_out_of_stock` or `dismiss_broken` with the time | models/class-url-issues.php 49-62; class-lasso-db.php 4101-4126; pages/class-ajax.php 867-900 |
| R17 | **Amazon data:** `lasso_amazon_products` (one row per ASIN and store: name, price, links, image, stock, Prime, currency, savings, features, rating, reviews, manual flag, last update); `lasso_extend_products` (other stores); `lasso_amazon_tracking_ids` (13 countries, one tracking ID each); the option `lasso_amazon_tracking_id_blacklist`; `lasso_link_updates` (ASIN replacements); `lasso_amazon_shortened_url` (short-link cache); `lasso_amazon_affiliate_plus` (cloud upgrades) | models (DDL); class-helper.php 5039-5084 |
| R18 | **The link index:** `lasso_link_locations` (one row per link found in content; `post_id` is the Lasso link, `detection_id` the post it is in; `is_ignored` = a dismissed opportunity), `lasso_link_locations_map`, `lasso_content` (counts per post), the keyword tables. The scan stamps `data-lasso-id` (the row) and, for Lasso links, `data-lasso-lid` (the link) into post content; `lasso_url_details.is_opportunity` is each link's "Detect Opportunities"; the settings `cpt_support` and `custom_fields_support` say what is scanned | models/class-link-locations.php 64-103; tools/README.md (the stamps); class-setting.php 1126-1129 |
| R19 | **Import and history:** `lasso_revert` (links imported from other plugins, with their old address, which keeps redirecting); `lasso_post_content_history` (the whole old and new content each time Lasso rewrote a post, while `enable_history` is on) | models/class-revert.php 49-64; class-post-content-history.php 45-56; class-post-type.php 461-477 |
| R20 | **Settings** are one autoloaded option, `lasso_settings`. Every read merges the 96 defaults under the stored values, then: `show_disclosure_list` and `_table` default from `show_disclosure`; `display_density`, `display_elements`, `display_layout`, `display_colors`, `theme_name` are decoded if they are JSON text and deep-merged with defaults per display type; flat `display_color_*` and label keys fill Single only when `display_colors` was never stored; `disclosure` is removed from the elements; listed keys are forced to bool or int; an empty primary or secondary button text, disclosure, default tracking country or brag link falls back to its default; an empty `cpt_support` to its default; empty entries leave the tracking-ID whitelist. A save merges the new values into the whole option | class-setting.php 694-744, 802-887, 1021-1143; admin/lasso-constant.php 107-386 |
| R21 | **Other options** are single values named `lasso_<name>` (through `Helper::update_option`) or `wp_lasso_<job>_…`: versions and one-time flags, licence and account, cloud sync state, job state, notices. Only `lasso_amazon_tracking_id_blacklist` is site configuration | class-helper.php 6170-6184; class-enum.php 20-49; table G |
| R22 | **Per user:** `lasso_dashboard_saved_views` (up to 30 views; name ≤ 80; filters: search, alert tab, group, link kind, placements, comparison table, order, page size; a deleted group falls back to "all"), `lasso_dashboard_tour_done`, `lasso_dismiss_amazon_bulk_sync_banner_v2` | class-dashboard-saved-views.php 20-27, 124-187, 223-274 |
| R23 | **Cloud-only data** kept locally: `lasso_affiliate_programs` (Lasso's catalogue, synced daily), `lasso_auto_monetize` (programmes Lasso matched to the site's links), `lasso_link_syncing`, the licence, snippet and sync options, the meta `link_id`, `is_aff_plus`, `boosting_enabled`, `lasso_shortcode` | models (DDL); class-lasso-affiliate-link.php 39-66; table G |

## 4. Decisions

Settled by Claude from Lasso and the records (none drops anything Lasso does or reverses a user decision):

- **D1 Copy, never adopt** (ADR-002, kept): each Lasso record becomes a new product with the record's ID in
  `_affibloom_lasso_id`; Lasso's posts, meta, tables and options are only read. `LassoIdMap` answers "which product is
  Lasso link N" for the shortcode, `data-lasso-lid`, tables, variants, clicks and issues.
- **D2 Render and convert** (ADR-003, kept): `[lasso]` and the legacy block render from the migrated data while Lasso
  is off (Task 27); the conversion is a separate, reversible step ("Needs the user" 1).
- **D3 One command, in families.** `wp affibloom migrate [--dry-run] [--only=<families>] [--update] [--force]
  [--report=<file>]`; with no `--only`, every family in a fixed order (analysis). The plan's `--settings` flag becomes
  the families `settings` and `display`. A family whose feature is not installed reports "waiting for <feature>" and
  writes nothing, so the command is useful from 26A on and grows with each feature.
- **D4 The mapping is Lasso's reading rule, not its raw meta** (R9, R10): what a migrated box shows is what Lasso
  showed. So: missing flags are off; a description Lasso hides is migrated empty (F1 D20); an Amazon record takes the
  cached link, price, Prime and (for a title "Amazon") name; texts keep their icon `<span>` (Task 6 decision 5).
- **D5 Statuses** (F1 D14, D43, kept): `publish` → `publish`; `lasso_delete` → `affibloom_retired` with
  `_affibloom_repointed_to` = the target's product (migrated first; a missing target leaves the product retired with
  no pointer, and a note); `trash` → `trash` through `wp_update_post`.
- **D6 A record with no link is not migrated** (ADR-002, kept): the 9 are listed with their ID, title and the ASIN in
  their slug ("Needs the user" 4).
- **D7 Running again is safe.** A record that has a product (any status, found by `_affibloom_lasso_id`) is
  "unchanged"; products made by the one-time staging import or the development seed are adopted. Two products with
  one Lasso ID stop the family with an error naming both.
- **D8 `--update` takes Lasso's side until cutover, but never loses an Affibloom edit.** At migration each product
  stores `_affibloom_migrated` (a hash of the values it was given). With `--update`, a record whose mapped values
  differ from that hash updates the product, **if** the product still has the values it was given; a product changed
  in Affibloom since is skipped and reported "changed on both sides". `--force` takes Lasso's side for those too.
- **D9 Settings are read the way Lasso reads them** (R20: defaults merged, normalised, the flat-colour fallback) and
  written through `SettingsService` and `DisplayService`, so the same validation applies as on the screens. The first
  run writes; a later run reports "unchanged" or, with `--update`, the keys that differ.
- **D10 Secrets and account data are never read**: the licence, site ID, Intercom token, sync secret and the old
  PA-API key pair. C1's Creators credentials: "Needs the user" 3.
- **D11 Clicks** come over as each product's total at the time of the migration ("Needs the user" 2 for how they are
  shown); a later run replaces that opening figure, it never adds to it.
- **D12 Issues** come over as the product's last known status (code, first seen, ignored), marked "from Lasso";
  Affibloom's first own check replaces it. Lasso's 41 fixed texts are not copied (F11 has its own words).
- **D13 The admin entry point is its own page**, Affibloom → Migrate from Lasso (`Capabilities::SETTINGS`), shown while
  Lasso's data exists on the site: the dry-run report, then Run in batches of 25 behind a lock. It replaces the
  Dashboard's one-time import card and `LassoImportService` (deviation 87), whose products it adopts (D7).
- **D14 Lasso's content history is not copied.** The 18 rows are Lasso's record of its own rewrites; Affibloom
  rewrites nothing. They stay in Lasso's table, which rollback and F34's gate can still read.
- **D15 Derived data is rebuilt, not copied**: the link index, content counts, duplicate keys, short-link cache. The
  `data-lasso-id` stamps stay in the content, unread; `data-lasso-lid` is read through the ID map.
- **D16 A box converts only when nothing is lost.** The converter maps each display type to its Affibloom block; a
  box with an attribute the block cannot hold, or whose product is unknown, stays exactly as it is (it still renders
  through Task 27) and is reported with the reason. The original content is stored once per post
  (`_affibloom_pre_conversion`), the save makes a revision, and revert refuses a post that was edited after its
  conversion unless `--force`.
- **D17 Rollback is switching plugins.** Affibloom writes only its own posts, meta, terms, options and tables; a
  fingerprint tool (row counts and checksums of everything in tables A-H that is Lasso's) proves it before and after
  every run (AC23, AC32). After a conversion, rollback is revert first, then Affibloom off, Lasso on.
- **D18 The report** has one line per item: family, Lasso ID or key, name, action (`create`, `update`, `unchanged`,
  `skip`, `retire`, `trash`, `waiting`, `error`), target, note; a summary per family; and the left-behind list. CSV
  and JSON; the same lines feed the admin page.
- **D19 Lasso's values that Affibloom's validation refuses** (a link that is not a link, a text over a limit) do not
  stop the run: the item is an `error` line with the field and the reason, the rest continues, and the exit code is 1.
- **D20 Task 6's decisions for this phase are kept**: `title_type` handling is Task 27's; button texts and badges keep
  their icon spans. Decision 2 (`check_duplicate_link` not imported): "Needs the user" 5.

**Needs the user**

1. **The conversion rewrites post content; the standing rule says Affibloom never does.** ADR-003 and AC3 (approved
   2026-11-12) convert the 248 legacy blocks at cutover. *Recommendation:* keep it as the one exception, because it
   is not a feature that runs: an administrator starts it, per post or for all, with a stored copy, a revision and a
   revert, and nothing breaks if it is never run (Task 27 keeps rendering the old blocks). The alternative is to drop
   Tasks 28A-28E and AC3 and leave the old blocks in place for good.
   → **Decided 2026-11-14:** A1: register Lasso's block name `affiliate-plugin/lasso` (Task ED6) and convert nothing; Tasks 28A-28E and AC3, AC25-AC28 are dropped; ADR-003 revised.
2. **Click history cannot be migrated.** Lasso keeps only a total per link, fetched from its cloud (2,839 clicks
   here); days and posts exist only in Lasso's cloud, which Affibloom may not call. *Recommendation:* carry each
   total as an opening figure ("before Affibloom") so the Clicks column does not restart at zero; the alternative is
   to start every product at 0.
   → **Decided 2026-11-14:** E1 (revised the same day): start from zero; no click figures are imported (Dashboard Clicks and GA start at cutover); Task 26J is dropped; the click-totals family is not migrated.
3. **Amazon credentials in Lasso's settings.** The old PA-API key pair is stored; the Creators API fields C1 needs are
   empty. *Recommendation:* never copy the PA-API pair (Affibloom does not use that API); copy the Creators
   credentials if a site has them (shown only as "set"); ask the client for Creators credentials, as C1 already says.
   → **Decided 2026-11-14:** E3: the user accepted the recommendation
4. **The 9 records with no link** each carry an ASIN in their slug (`amzn-b0f9hzl44v` …). No box, link or location
   uses them. *Recommendation:* keep skipping them and list them, so the client can add the ones still wanted; the
   alternative is to create them as Amazon products from the slug.
   → **Decided 2026-11-14:** E4: the user accepted the recommendation
5. **"Allow duplicate destinations"** (`check_duplicate_link`, on on the live site) is a Lasso General setting that
   Task 6 decision 2 left out, reading it as "refuse duplicates". In the source it chooses **what** counts as a
   duplicate (on: the entered link; off: the final destination). *Recommendation:* F9 builds the control (full scope)
   and the migration imports it; that reverses decision 2.
   → **Decided 2026-11-14:** the user accepted the recommendation: F9 builds the control and the migration imports it (reverses Task 6 decision 2).
6. **Stale Amazon values without credentials.** The cached name, image, price and Prime flag become the product's
   own values and stop refreshing at cutover until C1 has credentials. *Recommendation:* migrate them as decided
   (deviation 18), keep the site's Show Price off as it is, and say so in the admin guide.
   → **Decided 2026-11-14:** E5: the user accepted the recommendation

## 5. Scope that needs a home

- **Cloaked addresses (F24)** are a cutover blocker, not an extra: 6 live in-content links depend on 3 of them, and 4
  old slugs still redirect. F24 must exist before 29C and Task 41.
- **Tables (F25), variants (F27):** their stores must keep Lasso's table ID and variant key findable (an ID map), or
  `[lasso type="table" id=N]` and variant addresses break. Their analyses must name that map.
- **Lasso's content history (F34):** whether Affibloom shows Lasso's 18 old rows, given it writes none (D14).
- **Import from other plugins (F33):** `lasso_revert` and the importer's options are empty here; F33 owns them.
- **Lasso's Performance and notification settings** (S7, S8, S10, S11 of table F): F9's sweep decides which
  controls exist; the migration imports whichever do.
- **The overview's "API and data contract"** is out of date (table I of the analysis): its product-meta list, the
  display colours in `affibloom_settings`, `allow_duplicates`, `_affibloom_rating`, and the `--settings` flag.
- **Uninstall (Task 38):** Affibloom's uninstall must list the new stores this feature adds (`affibloom_migration`,
  `_affibloom_migrated`, `_affibloom_pre_conversion`).
- **`data-lasso-lid` in content:** F12 (clicks on text links) and F21 (where a product is used) read it through
  `LassoIdMap`; both must say so.
- **Lasso removal (Task 42):** nothing in F10 deletes Lasso's tables or options; whether and when they are dropped
  after cutover is Task 42's decision, and it ends the rollback route.

## 6. Edge cases

1. Lasso is active during a run (staging, production before cutover): the migration reads tables only; a record
   saved in Lasso mid-run is picked up by the next run.
2. A Lasso table or column is missing (an older Lasso, or a site that never had the feature): the family reads it as
   empty and says so in the report.
3. Two Lasso records with the same link (Lasso allows it with "Allow duplicate destinations"): both become products;
   the second is created through the repository, not through "Add a link", so the duplicate refusal does not apply.
4. A retired record whose target is itself retired, missing or in a loop: the pointer is followed as Lasso does (50
   hops); a dead end leaves a retired product with no pointer, and a note.
5. A slug already taken in Affibloom (two records' slugs differing only after sanitising): the product gets
   WordPress's unique slug; the report names it, and 26G decides which address redirects.
6. A value over Affibloom's limits, or HTML its cleaners remove: the cleaned value is stored and the line carries a
   "changed by validation" note with the field.
7. An image that is Lasso's placeholder, or a library image whose attachment is gone: no image; a note.
8. The run is cut off (time limit): the lock expires after 10 minutes; the next run continues, because every item is
   created at most once (D7).
9. Two administrators press Run: the second gets "A migration is already running.".
10. `--update` after the cutover, with Lasso off and its data old: allowed, but the report's first line says how old
    Lasso's newest record is.
11. A post converted, then edited, then reverted: refused with the post's title unless `--force` (D16); a post
    converted twice keeps its first stored copy.
12. A legacy block whose inner shortcode and its `short_code` attribute disagree (the block stores the shortcode
    twice): the box stays as it is and is reported. UNVERIFIED which copy Lasso renders (not read in this sweep; Task
    27's sweep owns the block's rendering).
13. A site with no Lasso data at all: the page and the command say "No Lasso data found." and the menu entry is hidden.
