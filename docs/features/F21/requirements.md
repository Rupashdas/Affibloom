# F21 Content link index and render-time link attributes: requirements

Status: gates 1-2 written 2026-11-14 from a sweep of Lasso 369's link index (area 1 of
`docs/plans/archive/dashboard-completion-plan.md`; the where-used half is F12's "Locations" sections). Gate 4 for
Tasks 35A-35J: waiting. These tasks replace Phase 7's Task 35.

## 1. User stories and acceptance criteria

- As the site owner, every link and display in my content is known: which post, which product (if any), its text,
  type, domain and flags, so Locations, opportunities and the delete rule are true.
- As an editor, when I change a product's link, New Window, NoFollow, Sponsored or cloaking, every text link to that
  product in my posts follows at once, without my posts being rewritten.

- **AC1** `wp affibloom links rebuild` indexes every anchor of the posts, pages and reusable blocks in the indexed
  statuses: one row per anchor with host post, link type (Product / Internal / External), display type (Text /
  Image-only anchor text), anchor text, URL, domain, NoFollow, New Window, ASIN, tracking ID, country, affiliate
  platform and product; links in ignored blocks and classes, `#…`, `[…]`, `mailto:`, `tel:` and empty hrefs are not
  indexed; it prints a summary; `post_content` of every post is byte-identical before and after. (35A, R1-R4, R22, R24,
  R25, R36)
- **AC2** The rebuild indexes displays: `[lasso …]` (Single, Button, Image, Grid, List, Gallery, Table by its `type`),
  the `affiliate-plugin/lasso` block, Affibloom's `affibloom/product-box` block and the other plugins' shortcodes and
  the AAWP block of R27-R29, each with its product; a group or table display records which group or table it shows.
  (35B, R5, R6, R27-R29)
- **AC3** Saving a post (publish, future, draft, pending, private) rewrites that post's rows only; a link removed from
  the post loses its row; trashing or deleting the post removes its rows; restoring re-indexes it. (35C, R10-R13)
- **AC4** Settings → General **Custom Link Detection**: a custom post type chosen in "Custom Post Types:" is indexed;
  an ACF field chosen in "Custom Fields (ACF):" (shown only when ACF is active) is indexed on its post; the Link
  Index rebuilds after a change. (35D, R22, R33, R51)
- **AC5** Links in Elementor data, Beaver Builder data, Thrive Architect content, TablePress cells, Kadence button and
  testimonial blocks, WP Recipe Maker recipes, Mediavine Create cards, `<ta>` tags and SiteStripe images and iframes
  (when "SiteStripe scan" is on) are indexed with Lasso's link types, each only when its plugin is active. (35E,
  R23, R26, R30-R32)
- **AC6** The full rebuild runs in WP-Cron batches (scheduled once on activation and from the Rebuild button), resumes
  after a timeout, honours the CPU threshold and the cron interval floor, and a daily pass re-indexes stale posts and
  drops rows whose host post no longer exists. (35F, R14-R18, R21)
- **AC7** Settings → General **Link Index** shows "Lasso tracks every link on your site." in Affibloom's words, then
  Build Progress / ETA while a rebuild runs, else Last Updated / Links Indexed (nothing when 0), refreshed every 5 s;
  **Rebuild Database** asks "Ready to rebuild?" and starts the rebuild; **Performance** (Power, Balanced, Light, Custom;
  the CPU field only for Custom) is saved. (35G, R51-R58)
- **AC8** On the front end, a text or image link in a post that the index ties to a product is printed with that
  product's current link, `target` (New Window), `rel` (nofollow, sponsored, noopener) and Affibloom's click
  attributes, while the stored `post_content` still holds the old values. (35H, R37-R44, D13-D15)
- **AC9** Saving a product changes nothing in posts, yet its rows' URL follows a new Primary URL, and a repoint moves
  the retired product's rows (and their Locations) to the product it points to. (35I, R60, R61)
- **AC10** SiteStripe iframes and images, an `[lasso]` inside a core image block and Kadence button attributes of a
  tied link are drawn at render time as Lasso's scan would have stored them. (35J, R45-R47)

## 2. Evidence

All read in full: `classes/class-link-location.php`; `models/class-link-locations.php`, `class-link-locations-map.php`,
`class-content.php`, `class-metadata.php`, `class-link-updates.php`, `class-link-syncing.php`;
`classes/background_processing/class-lasso-process-scan-link.php`, `class-lasso-process-link-database.php` (its five
classes), `class-lasso-process.php`; `classes/class-post.php`, `class-simple-html-parser.php`,
`classes/interfaces/class-dom-parser.php` (interface, 1-60); `pages/settings-general/class-ajax.php` 30-605, 1005-1037;
`admin/views/settings/general.php`; `admin/views/modals/remove-attributes-confirm.php`;
`admin/assets/js/lasso-helper.js` 775-804. Read in the parts this subsystem uses: `classes/class-lasso-cron.php` 1-610,
1000-6988 (every scan, write and cron function listed in the R table); `classes/class-lasso-db.php` 340-460, 1026-1394,
1453-1856, 3514-3530, 4351-4410, 4572-4794, 5010-5435, 5880-5925; `classes/class-helper.php` 55-155, 740-758,
1020-1110, 1160-1235, 1395-1413, 1510-1645, 6605-6627; `classes/class-html-helper.php` 614-659;
`classes/class-lasso-affiliate-link.php` 440-482 (the public link), 1796-1918 (save triggers, Keep Original URL),
2537-2666 (`get_lasso_post_id_by_url`); `classes/class-lasso-init.php` 250-264; `pages/class-hook.php` 53-260,
1255-1296; `pages/post-type/class-hook.php` 20-60; `pages/class-ajax.php` 105-311, 1560-1716; `models/class-table-details.php`
245-296; `classes/class-setting-enum.php` 108-135; `admin/lasso-constant.php` (defaults); `classes/class-enum.php` 47.
Callers found by grep for `scan_post_page`, `link_database`, `check_all_posts_pages`, `update_link_in_post`,
`force_to_run_new_scan`, `keep_original_url`, `LASSO_CONTENT_DB`, `META_LINK_LOCATION_NAME`.

**This copy, 2026-11-14 (SELECT only):**

| What | Count |
|---|---|
| `wp_lasso_link_locations` | **3,015 rows** in **180 host posts** (detected 2026-05-26 to 2026-11-09) |
| by link type | Internal 1,304 · Lasso 923 · External 788 (no SiteStripe, WpRecipeMaker or MvCreate rows) |
| by link and display type | Internal/Text 1,273 · External/Text 678 · Lasso/Text 667 · Lasso/Single 223 · External/Image-only 110 · Internal/Image-only 31 · Lasso/Image-only 31 · Lasso/Table 2 |
| tied to a product (`post_id > 0`) | **921 rows**, 49 hosts, **200 products** (all published); untied 2,094 rows in 171 hosts (the 2 Lasso/Table rows are untied: tables are attached through the map) |
| `no_follow` / `new_window` | false/false 585 · false/true 2,198 · true/false 232 (no row has both: `no_follow` is "rel is exactly nofollow", R2) |
| `is_ignored` 1 · `country` other than us · `affiliate_platform` set | 0 · 0 · 0 |
| `product_id` (ASIN) set · `tracking_id` set · `anchor_text` NULL | 1,101 · 899 · 267 |
| domains | 98 distinct; NULL 2,335 (Lasso and Internal rows), amazon.com 237, amzn.to 218, then ≤ 13 each |
| hosts | post/publish 2,704 rows in 134 posts · page/publish 186 in 13 · wp_block/publish 89 in 31 · post/draft 36 in 2 |
| `detection_slug` empty or `__trashed` | 0 |
| `link_slug` ≠ `original_link_slug` | 450 (the scan rewrote the link; R2, R37) |
| `md5_link_slug` = `MD5(TRIM(TRAILING '/' FROM link_slug))` | 1,705 of 3,015 (the rest predate v358's rule) |
| `wp_lasso_link_locations_map` | **18 rows**: 4 location IDs × 8 products, all Table displays in post 9492; 10 rows point at 2 location IDs that no longer exist |
| `wp_lasso_content` · `wp_lasso_metadata` (Link_Locations/Table) · `wp_lasso_link_updates` · `wp_lasso_link_syncing` | 214 · 2 · 3 · 0 |
| anchors carrying `data-lasso-id` (publish/draft posts, pages, reusable blocks) | **2,790 in 180 posts** (+5 revisions), every one matching its row; **698** also carry `data-lasso-lid` and `data-lasso-name` (the tied text and image links): rel "nofollow noopener" 674, "nofollow sponsored noopener" 24, target `_blank` 698; href Amazon 662, cloaked on this site 6, other 30 |
| untied stamped anchors | rel "noopener" 1,147, none 583, "nofollow noopener" 354, "nofollow" 7, "noopener noreferrer" 1; target `_blank` 1,500, none 592 |
| shortcodes / blocks | 35 posts hold `[lasso … link_id=…]` (all stamped), 40 hold the `affiliate-plugin/lasso` block; 1 post holds `lasso-scan-ignore` |
| other sources | ACF not active; Elementor not active but 160 posts keep `_elementor_data`; no `wprm_recipe`, `mv_create`, `tablepress_table` posts; no Kadence blocks; no SiteStripe markup; 5 `<ta>` tags, all in `nab_hidden` posts (not indexed); 72 published posts hold GenerateBlocks buttons with a stamp |
| options | `lasso_force_to_run_new_scan` "true", `lasso_license_status` 0, `lasso_disable_processes` 0, `lasso_scan_all_posts` 1, last Build Link run 2026-11-09 |
| settings | `cpt_support` [post, page], `custom_fields_support` [], `keep_original_url` [], `general_performance_mode` balanced, `cpu_threshold` 80, `cron_time_interval` 0, `manually_background_process_limit` 2, `processes_execute_limit` false, `enable_site_stripe_post_content_scan` false, `amazon_multiple_tracking_id` true, `force_change_from_link_details` false |
| product flags (254 published) | New Window on 254, NoFollow on 254, Sponsored on 5, Link Cloaking on 236 |

`wp plugin list --status=active` lists `lasso` as active on this copy (the brief says inactive); its scan hooks are
not registered because `lasso_license_status` is 0 (R10). Nothing was run but SELECTs.

## 3. What Lasso does

| # | Rule | Source |
|---|---|---|
| R1 | **Storage:** `lasso_link_locations` — `id`, `detection_date` (UTC), `link_type` varchar 50, `display_type` varchar 50, `anchor_text` varchar 1500 NULL, `post_id` (the product, 0 when none), `link_slug` text, `link_slug_domain` varchar 150 NULL, `md5_link_slug` char 32, `detection_id` (host post), `detection_slug` varchar 1000 (host permalink at scan time), `tracking_id`, `product_id` (ASIN or other product ID), `country` char 2 default us, `no_follow` and `new_window` ('true'/'false' strings), `is_ignored` int 1, `original_link_slug` text, `affiliate_platform` varchar 64; 15 indexes (link type+domain, product+country, host, post_id, opportunity cover `(post_id, is_ignored, link_slug_domain)`, md5, display type, date) | class-link-locations.php 29-106 |
| R2 | **Filling a row** (`write_link_locations`): date now; `anchor_text`, `tracking_id`, `product_id`, `link_slug_domain` trimmed, '' → NULL; `md5_link_slug` = md5 of the URL without trailing slash; `country` given, else from an Amazon URL with a product ID, else us; `affiliate_platform` = skimlinks (fave.co, go.skimresources.com, go.redirectingat.com, go.effortlessgent.com), geni.us (geni.us, buy.geni.us, urlgeni.us), tinyurl, or any `go.` subdomain; `original_link_slug` = the URL the first time, kept from the existing row on later scans; `REPLACE INTO` by `id`, so a valid stamp keeps the row's ID. `no_follow` is 'true' only when `rel` is exactly `nofollow`; `new_window` only when `target` is exactly `_blank`. There is **no** sponsored column | class-lasso-cron.php 5174-5291, 2840-2841; class-link-location.php 350-450 |
| R3 | **Location identity, the stamp:** an anchor's `data-lasso-id` (a shortcode's `link_id`, a Kadence button's `data-lasso-id` attribute, the AAWP block's class `lasso-ll-do-not-delete-<id>`) names its row. It is reused when the row does not exist yet or belongs to this host and is not already used in this scan; otherwise a new row is made | class-lasso-cron.php 2660-2668, 1823-1841, 6216-6228, 6644-6648; class-enum.php 47 |
| R4 | **A dismissed row** (`is_ignored` 1, set by the Opportunities screens) is not rewritten: the scan only refreshes its date and platform and returns 0 — so it is then not in the scan's kept list and R36 deletes it; the next scan recreates it from the stamp with `is_ignored` 0 (as read; not run) | 5195-5207, 5528-5543 |
| R5 | **Group displays:** for a Grid, List, Gallery or Table display with `category` or `id`, `lasso_link_locations_map (link_location_id, lasso_id, created_at, updated_at)` gets one row per published product of the group (`get_post_ids_by_group_slug`) or table (`get_post_ids_by_table_id`), after deleting the location's old map rows. It is refreshed only when the host is scanned again | class-lasso-cron.php 2149-2174; class-link-locations-map.php 44-59; class-lasso-db.php 1803-1856 |
| R6 | **Table displays** also get `lasso_metadata (object_id = row id, type Link_Locations, meta_key Table, meta_value = table id)`; after each batch the metadata of rows that no longer exist, or are not Lasso Table rows, is deleted. A table's own location count and list read it | class-lasso-cron.php 2333-2336, 5551-5590; class-metadata.php 45-120; class-table-details.php 253-296 |
| R7 | `lasso_content` (per host: type, title, permalink, author, words, h2, images, total links, monetized, internal, incoming internal, displays, grids, lists, tables, competitor shortcodes) is filled after each post's scan; its only readers are Intercom/Segment in Lasso's admin header and footer and the cloud data sync (`LASSO_LINK/data-sync`) | class-lasso-cron.php 5598-5664; class-lasso-db.php 268-293; header-new.php 91-94; data_sync/class-lasso-data-sync.php 303, 345 |
| R8 | `lasso_link_updates` records a product's retired ASIN (F1 S13); the URL→product lookup follows a retired ASIN to its replacement; recording deletes untied rows of the old ASIN | class-link-updates.php 105-353; class-lasso-affiliate-link.php 2657-2666 |
| R9 | `lasso_link_syncing` holds links pulled from Lasso's cloud (hourly `pull_lasso_links`) | class-link-syncing.php; class-lasso-cron.php 6783-6800 |
| R10 | **On save:** `save_post` (priority 1000) → `check_all_links_in_post_page`: once per post per request; skipped for posts built with Elementor; only supported types (R22) and `publish`/`draft`; queues `Lasso_Process_Scan_Links_Post_Save` (background, not inline). Registered only while the licence status is 1 | class-lasso-cron.php 260-279, 5437-5461 |
| R11 | Elementor: `elementor/document/after_save` scans the post inline; Mediavine Create: `mv_create_card_post_publish` scans the card's posts (object, canonical, original, associated) | class-hook.php 1270, 1290-1296; class-lasso-cron.php 5472-5520 |
| R12 | **Delete:** `deleted_post` (priority 100) deletes the host's rows (supported types only). **Trash:** the save hook skips status `trash`, so a trashed post's rows stay and keep counting (the `__trashed` filters of R59 match only slugs stored while trashed); untrash (status draft) re-scans | 278, 348-358, 5451-5453 |
| R13 | A post whose content is empty after reading has all its rows deleted (WPRM / Mediavine CPTs with empty bodies are kept when their plugin is inactive) | 1515-1567 |
| R14 | **Crons** (`add_lasso_cron`: 5, 10, 15, 30 min, 1-3 h, monthly): `lasso_scan_all_links` → `Lasso_Process_Build_Link::link_database_limit()` (diff: posts **without** a row older than 48 h — the SQL's comment says the opposite); `lasso_daily_clean_link_locations`; while `lasso_force_to_run_new_scan` is 'true' only the force scan (15 min), JS domain and missed-schedule crons are scheduled; Lasso Lean keeps five crons; daily/hourly slots are spread by a hash of `home_url` | class-lasso-cron.php 123-199, 365-587, 1004-1046, 1202-1207; class-lasso-db.php 5051-5062 |
| R15 | **Force scan all posts** (activation, Rebuild Database, import/revert all, Fix Overwritten Links, a DB update): published posts only, 500 a page; each finished page queues the next at once; at the end the flag becomes 'false', normal crons are rescheduled, `lasso_scan_all_posts` = 1, the import scan notice is cleared. While it runs, every other link-database process removes itself | class-lasso-process-link-database.php 594-718, 67-73; class-lasso-init.php 256-264; class-activator.php 52 |
| R16 | **Link database runs:** publish + draft; batch = total ≤ 500 ? ceil(total/2) clamped 100-250 : ceil(total/5) clamped 200-500; worker time limit 10 s (≤ 500 posts) or 20 s; one pass a day (page 1 with today's date stops); per item: scan, `lasso_content`, keyword scan; run metrics in `Background_Process_Runs` | 144-172, 330-403, 410-451, 67-117, 208-322 |
| R17 | **The runner** (`Lasso_Process`, wp-background-processing in `wp_options` batches, loopback requests with a nonce): lock 30 s; time and memory limits; `cpu_threshold` (default 65): ≤ 50 → 1 s pause per item, else an item is skipped while load ≥ threshold; with "Processes Execute Limit" on, at most 2 processes run and others wait in a priority queue (force scan 100 … scan link 10), raised by 10 every 24 h; age-out after 6 h with 3 restart attempts; `lasso_disable_processes` stops all; hourly duplicate-batch cleanup | class-lasso-process.php 33-58, 96-98, 255-322, 435-530, 617-643, 718-927; class-lasso-cron.php 6758-6768 |
| R18 | **When WP-Cron fails:** `lasso_check_crons` (admin AJAX, `lasso_ajax`, `edit_posts`) runs due Lasso crons itself, at most `manually_background_process_limit` at a time ("Cron is working well." / "WP cron is disabled"); `lasso_cron_handle_manually` runs an allow-listed process; `get_list_background_processing` lists running processes for the progress UI | pages/class-ajax.php 1564-1696; class-lasso-process.php 648-711 |
| R19 | **A product's save re-scans its posts** when New Window, NoFollow (either button), Sponsored, Link Cloaking, the Primary URL or the slug changed (`Lasso_Process_Scan_Link::scan_post_page` → `scan_link_in_post` per host, then a full scan of the host); removing a product from a group does the same | class-lasso-affiliate-link.php 1798-1812; class-lasso-process-scan-link.php 40-104; class-lasso-cron.php 4722-4800; groups/class-ajax.php 255 |
| R20 | **Other triggers:** import/revert (`update_url_everywhere`), the replace-shortcode process, keyword monetize, opportunity monetize/unmonetize (`update_link_in_post`), a new Cloaked Link Prefix (`Build_Rewrite_Slug_Links_In_Posts`, all posts, cancels its running batch), turning on Amazon auto-monetize (`Build_Link`), the install page | class-lasso-cron.php 1183-1197; pages/class-ajax.php 1048-1159; settings-general/class-ajax.php 381-386, 413-422; install/class-ajax.php 69-71 |
| R21 | **Daily clean:** deletes rows whose host is no longer a post of a supported type; also cloud DWH sync, logs > 14 days, post content history > 14 days. A daily check logs an error when the Build Link run last completed > 3 days ago with work queued | class-lasso-cron.php 328-341, 6906-6927; class-lasso-db.php 5315-5330 |
| R22 | **What is scanned:** post types = setting `cpt_support` ∪ post, page, `wp_block` (always); statuses publish, draft (force scan: publish); candidates: content with `<a `, a SiteStripe domain, the AAWP block, any listed shortcode, TablePress JSON, every `wprm_recipe` / `mv_create`; Thrive's `tve_updated_post` meta when Thrive exists | class-helper.php 61-122; class-lasso-db.php 5051-5162; class-lasso-cron.php 1492 |
| R23 | Thrive Architect: the content is read from (and written to) its meta when `tcb_editor_enabled` | class-lasso-cron.php 1508, 5315-5330; class-helper.php 5465-5510 |
| R24 | **Not scanned:** blocks `tph/`, `editor-blocks/`, `uagb/`, `rank-math/`, `amalinkspro-legacy/`, `yoast/faq`, `wordpress-seo/faq`, `aioseo/faq`, `seopress/faq`, `wpseopress/faq`, `wp-seopress/faq`, `kadence/accordion`, `kadence/pane`, `acf/`; links inside ancestors with class prefixes `schema-faq`, `wp-block-yoast-faq`, `wp-block-rank-math-faq`, `wp-block-aioseo-faq`, `wp-block-seopress-faq`, `wp-block-kadence-accordion`, `kt-accordion-pane`, `wp-block-acf-` (and `kt-blocks`, `wp-block-kadence` when Kadence is too old); anchors with classes `lasso-scan-ignore`, `info-box-link`, `kb-advanced-image-link`, `kt-svg-icon-link`, `kb-section-link-overlay`, `kt-sc-media-link`, `kt-imageoverlay-link` | class-lasso-cron.php 74-116, 1275-1447, 2608-2626 |
| R25 | **Anchors:** skipped when no href, href starts `[` or `#`, `mailto:`, `tel:`; display type "Image-only anchor text" when the anchor holds `<img`, else "Text"; anchor text over 1,000 characters without an image is stripped of tags; link type **Lasso** when the URL resolves to a published product, **Internal** when it starts with `site_url()`, else **External**; domain NULL for Lasso and Internal. URL → product: lasso.to shortlink (cloud), the cloaked permalink (with the prefix rule), the redirect URI, `url_details.redirect_url`, the auto-monetize table, a product slug path, an Amazon ASIN+country, a retired ASIN (R8); plus the ThirstyLink title, EasyAzon `data-identifier`, an old imported plugin's prefix, an Amazon link with the site's tag; a stamped row whose product is still published keeps its product when the URL no longer resolves (#5975) | 2589-2960, 4909-4920; class-lasso-affiliate-link.php 2537-2666 |
| R26 | `<ta>` tags (ThirstyAffiliates): a row with display type `link`; the tag becomes an `<a>` when the product is Lasso's or the plugin is inactive | 2483-2578 |
| R27 | **Shortcodes** `lasso`, `earnist`, `earnist_link`, `easyazon_link`/`easyazon-link`/`simpleazon-link`, `easyazon_image`/`-image`/`-image-link`/`simpleazon-image`, `easyazon_cta`/`-cta`, `easyazon_infoblock`/`_block`/`-block`, `amalinkspro`, `thirstylink`, `amazon`, `aawp` (also inside block attributes, `"`-encoded): display type EasyAzon, AMALink (or its type once imported), ThirstyLink, AAWP, Earnist, or for `lasso` its `type` (button, image, grid, list, gallery, table; `link` and others → Single; label = anchor for `link`); product from `id`, ASIN (`link`/`box`/`fields`, `amazon_url`, `geni_url`), the import table; link type Lasso when the product is Lasso's | class-lasso-cron.php 44-72, 1780-2120; class-link-location.php 226-305 |
| R28 | The `affiliate-plugin/lasso` block's `short_code` attribute is repaired and scanned as R27 | 1341-1373, 4601-4619 |
| R29 | The AAWP block (`wp:aawp/aawp-block`): a row with display AAWP and its ASIN/table | 6628-6753 |
| R30 | Kadence (supported versions): `kadence/singlebtn` attributes (link, text, noFollow, target, `data-lasso-id`) and `kadence/testimonial` content are scanned | 6192-6343 |
| R31 | **SiteStripe** (setting `enable_site_stripe_post_content_scan`, default false): `<img>` on `ws-na`/`ws-eu.amazon-adsystem.com/widgets/q` → link type SiteStripe, display "Image"; `<iframe>` → display "Product Box"; Genesis Custom Blocks image attributes; Elementor and Beaver Builder data | 3061-3286, 4531-4592, 6120-6181 |
| R32 | **Builders and recipe plugins:** Elementor `_elementor_data` (when built with Elementor), Beaver Builder `_fl_builder_data` (html/text settings), TablePress cell JSON, WP Recipe Maker (notes, equipment, equipment terms' affiliate HTML and link, ingredients, ingredient rows and terms, instructions — bare Amazon URLs in instructions treated as links; link type `WpRecipeMaker`), Mediavine Create description (`MvCreate`) — each when its plugin is active | 1606-1639, 1748-1764, 3295-4523, 3462-3702 |
| R33 | **ACF:** for each field name in `custom_fields_support` (+ `wpcf-description-dans-le-listing` with AmaLinks Pro), the host's meta whose key ends with it is scanned as content; rows belong to the host | 6568-6601; class-lasso-db.php 5886-5912 |
| R34 | WP Coupons: `wp_coupons_discount_url` meta that resolves to a product becomes the product's public link (no row) | class-lasso-cron.php 5909-5937 |
| R35 | `<keyword>` tags feed `lasso_keyword_locations` (keyword opportunities) | 2432-2473 |
| R36 | **After each post:** the host's rows not seen in this scan are deleted | 1578, 5528-5543 |
| R37 | **Write-back, the stamp:** every scanned anchor gets `data-lasso-id="<row>"`; `data-old-href` is removed; a tied link gets `data-lasso-lid="<product>"` and `data-lasso-name="<name>"`, an untied link loses both; links in R24's ignored places lose `data-lasso-id`, `data-lasso-name`, `data-old-href` | 2658, 2980-3011, 1259-1266, 2620-2626 |
| R38 | **Write-back, rel and target:** a `1` is removed from every `rel`; a tied link's `rel` is **replaced** by `nofollow` (product NoFollow) + `sponsored` (product Sponsored) and its `target` set to the product's (`_blank` / `_self`); **every** `_blank` link (tied or not) gets `noopener` (and loses `noreferrer`); an empty `rel` is removed | 2985-3027 |
| R39 | **Write-back, href:** `https://` added; an Amazon product link rewritten to its canonical form with the site's tag rule (`get_amazon_product_url`), keeping the original tracking ID when "multiple tracking IDs" is on and "force change from link details" off; SiteStripe URLs normalised; a tied link's href becomes the product's **public link** — the Amazon URL, the uncloaked URL, or the cloaked permalink (R40) — except Amazon "sponsored" URLs, links to WordPress posts/pages and kept lasso.to shortlinks; a cloaked link with a query string keeps its parameters (`pass_through_url_parameters`); old imported-plugin prefixes and EasyAzon / ThirstyLink links are rewritten; an emptied href gets its original back | 2629-2830, 2857-2863, 3020-3023 |
| R40 | **The public link** a tied link takes: Amazon or uncloaked non-Amazon → the Primary URL; cloaked non-Amazon → the permalink; **Keep Original URL** domains (built in: geni.us, primalvid.io, rstyle.me, howl.me, go.skimresources.com, awin1.com, shareasale.com, refer.link, lvnta.com, link.archeraffiliates.com, lasso.link, lasso.to; plus the setting's list) keep the typed URL instead of the redirect's end | class-lasso-affiliate-link.php 443-482, 1905-1918; class-setting-enum.php 111-124 |
| R41 | **Write-back, shortcodes:** each scanned shortcode is rewritten with its attributes sorted, `link_id` = row, `id` and `ref` = the product's ID and slug, `label` (encoded); `[lasso rel="` → `ref`; other plugins' shortcodes become `[lasso …]` once their product is imported, and `[lasso]` reverts to the original plugin's shortcode when not imported; Elementor copies are fixed too | class-lasso-cron.php 1769-1770, 2176-2344 |
| R42 | **Write-back, other:** `fix_content_before_scanning` (closes `<iframe>`, `[gift_item link=` and `[/amazon` fixes, removes `<p>` around block comments, drops the `/lasso-urls/` prefix when there is no Cloaked Link Prefix); Google Sheets `data-sheets-value` re-encoding; the Lasso block's `short_code` repaired; `<ta>` → `<a>` (R26) | 6386-6420, 2362-2424, 1341-1373, 4627-4639 |
| R43 | **How content is written:** `update_post_content` writes `post_content` directly (`$wpdb`), only if the row still equals the body read (`BINARY` compare) when the caller passes it, clears the cache and records Post Content History; Thrive content goes to its meta | 5305-5371 |
| R44 | **Other stores written:** Elementor `_elementor_data`, Beaver `_fl_builder_data`, ACF meta, WPRM meta and term meta, Mediavine `wp_mv_creations.description` (then `publish_creation`), Kadence block attributes, WP Coupons meta | 4531-4592, 6568-6601, 3711-4458, 3678-3702, 6192-6343, 5909-5937 |
| R45 | **SiteStripe write-back:** an iframe tied to a product becomes `[lasso sitestripe="true" sitestripe_scan="true" id ref link_id]`; an image's `src` becomes the product's image at the SiteStripe size | 3167-3175, 3278-3283 |
| R46 | A `[lasso id]` inside a core image block becomes `<img src="<Amazon image>" alt="<name>">` | 6351-6377 |
| R47 | Kadence `singlebtn` of a tied link: `link` = public link, `data-lasso-name`, `noFollow`, `sponsored` from the product; AAWP blocks get the class `lasso-ll-do-not-delete-<row>` or become `[aawp …]` once imported | 6237-6310, 6707-6744 |
| R48 | **A product's re-scan** (`scan_link_in_post`): for that product's stamped anchors in each host, `target` = `_blank`/`_self` and href = the permalink (Amazon: the Primary URL with its tracking ID), then the full scan of R25-R44 | class-lasso-cron.php 4722-4800 |
| R49 | **Monetize / import edits** (`update_link_in_post`): the stamped anchor's href (and text, target) replaced, then re-scan | 4653-4712; class-helper.php 1605-1634 |
| R50 | **Uninstall Lasso** ("Uninstall Lasso?" / "If you are canceling your subscription, you do not need to run this." … toggles "Remove all data-lasso-ids from the HTML." (always on), "Remove all Lasso link cloaking.", "Remove all Lasso displays and shortcodes." / "⚠ This process cannot be undone. ⚠" / Keep My Displays, Uninstall Lasso): a background pass strips the stamps, optionally restores primary URLs and removes Lasso blocks and shortcodes, deletes the rows; then "All Lasso displays, links, and attributes have been removed. You can now deactivate and delete the Lasso plugin." | remove-attributes-confirm.php; settings-general/class-ajax.php 157-245; class-lasso-cron.php 4807-4899 |
| R51 | **Settings → General, right card:** **Link Index** — "Lasso tracks every link on your site."; while the Link Database process runs: "Build Progress" ("<n> completed of <total> total"), "ETA", "We're indexing your links in the background, feel free to do something else."; else "Last Updated" (latest `detection_date`, or the latest Amazon check if later) and "Links Indexed" (all rows), nothing when 0; **Start Link Indexing** only while processes are disabled; **Rebuild Database** only with `&support` | general.php 396-414; settings-general/class-ajax.php 89-205; class-helper.php 1025-1095 |
| R52 | **Rebuild Database** opens "Ready to rebuild?" / "By rebuilding your link index, you'll start with a clean slate." / Cancel, Confirm → `lasso_trigger_cron` (`lasso_ajax`, `edit_posts`) → force scan (R15) → "Working... Please be patient." or "The processes are running."; **Start Link Indexing** → `lasso_rescan_lasso_attributes` (settings nonce, the settings capability; enables processes, `link_database_limit`) with the same two messages | general.php 576-592, 709-783; pages/class-ajax.php 1701-1716; settings-general/class-ajax.php 1014-1037 |
| R53 | **Custom Link Detection** — "Scan for links and shortcodes in custom locations." (tooltip "Additional locations where Lasso will discovering links."): "Custom Post Types:" a multi-select of registered types except Lasso's exclusion list (Lasso's type, pretty-link, affiliate_url, attachment, revision, nav_menu_item, custom_css, customize_changeset, oembed_cache, user_request, wp_block, acf-field-group, acf-field, wpadc, wpcf7_contact_form, elementor_snippet, elementor_font, elementor_icons) and post/page, placeholder "Select post types"; "Custom Fields (ACF):" only when ACF is active, every ACF field, "Select fields" | general.php 81-103, 416-430; class-helper.php 61-105 |
| R54 | **Performance** — "Set the maximum CPU level that Lasso will run at." (tooltip "Lasso will wait until your CPU drops below this level to continue with its updates."): "Performance Mode:" Power, Balanced (default), Light, Custom; the CPU % field shows only for Custom; choosing a mode (not loading the page) sets CPU 80/65/50, cron interval 0/0/1 and manual limit 2/2/1 | general.php 105-130, 432-448, 669-707 |
| R55 | **Support-only (`&support`):** "Keep Original URL" (textarea, "We don't convert the affiliate URLs to destination URLs", "Example: domain.com, not https://domain.com/"); "Cron" "Time interval (hours)" 0-3; "Lasso Cronjob Manually" "Maximum Lasso cronjob at a time." 1-3; Optional Settings toggles (Segment Analytics, Restrict Prefix, Processes Execute Limit, Font Awesome JS Render SVG, Disable AAWP Description) | general.php 258-270, 295-390 |
| R56 | **Saving General:** `cpt_support` defaults to [post, page] when absent; `custom_fields_support` to []; Keep Original URL lines lose `http(s)://` and keep only values that make a valid URL with a dot; manual limit clamped 1-3; a changed `cron_time_interval` reschedules the crons (1-3 h floors every shorter cron) | settings-general/class-ajax.php 517-541; class-lasso-cron.php 236-255, 505-523 |
| R57 | `lasso_get_stats` (settings nonce, the settings capability; "You do not have permission to view background process stats.") is polled every 5 s and redraws the stats and the uninstall progress | settings-general/class-ajax.php 89-205; lasso-helper.js 775-804 |
| R58 | Defaults: `cpu_threshold` 65, `general_performance_mode` balanced, `cron_time_interval` 0, `manually_background_process_limit` 2, `processes_execute_limit` false, `cpt_support` [post, page, wp_block], `custom_fields_support` [], `keep_original_url` [], `enable_site_stripe_post_content_scan` false | lasso-constant.php 127-380 |
| R59 | **Read by others** (owned there): Locations (F12), link and domain opportunities (F22: rows with `post_id` 0, not ignored, domain = a product's), the content reports (F22: `get_content_query` — published hosts, total and tied counts; `get_content_link_query` — Text and Image-only rows; `get_opportunities_query` / `get_opportunities_only_query` — untied Text (or External Text/Image-only) rows per domain; `get_domain_link_query` — untied rows of one domain), Tables' locations (F25, R6), Amazon pricing "locations only" (`amazon_pricing_locations_only`, C1), auto-monetize | class-lasso-db.php 364-389, 1320-1694, 4389-4410 |
| R60 | **Repoint** moves the source product's rows and map rows to the target (F1 S10); posts are not rewritten | class-lasso-repoint-merge.php 115-205 (F1 S10) |
| R61 | A saved Primary URL changes the product's rows' `link_slug` and md5 (`sync_link_locations_url_for_post`); untied rows of the product's lasso.to shortlinks and Amazon short links are attached to it (cloud shortlinks) | class-lasso-affiliate-link.php 1818-1823; class-lasso-db.php 4580-4757 |

The Locations rules (the count, the tab, the filter, the delete rule) are L1-L16 in F12's requirements, "Locations".

## 4. Decisions

Settled by Claude from Lasso and the records:

- **D1 Storage: one table, `{prefix}affibloom_link_locations`,** Lasso's columns in Affibloom's names: `id`,
  `host_id`, `host_url`, `source` (content, acf:`<key>`, elementor, beaver, thrive, tablepress, wprm, mv_create),
  `occurrence`, `product_id` (0 = untied), `link_type` (`product`, `internal`, `external`, `wprm`, `mv_create`,
  `sitestripe`), `display_type` (Lasso's strings, R27, kept so the opportunity queries port as written),
  `display_ref` (`group:<term_id>` or `table:<id>` for group displays), `anchor_text`, `url`, `original_url`,
  `domain`, `url_md5`, `asin`, `tracking_id`, `country`, `no_follow`, `new_window` (tinyint), `is_ignored`,
  `affiliate_platform`, `lasso_location_id` (the stamp, D4), `detected_at`. It supersedes the overview's
  `affibloom_usage`, which indexed only Affibloom's boxes (the parity audit's G3).
- **D2 No map table.** A group or table display's products are read when counted, from `display_ref` and the group
  (F23) or table (F25); Lasso's map is a snapshot that goes stale (10 of this copy's 18 rows point at rows that no
  longer exist). `lasso_metadata`'s Table rows become `display_ref`.
- **D3 Nothing Affibloom builds writes content** (deviation 52; F1 D13): not `post_content`, not post meta (ACF,
  Elementor, Beaver, Thrive, WPRM, WP Coupons), not term meta, not other plugins' tables. The index only reads.
  R37-R50's write-backs are not built; D13-D15 give their visible results at render time; R50 has nothing to undo.
- **D4 Identity without a stamp:** a row is the n-th link with the same URL in the same host and source
  (`occurrence`); a Lasso stamp in the content (2,790 anchors here) is read into `lasso_location_id`, so anything keyed
  by Lasso's location ID (opportunity dismissals, Task 26) maps over. Rows are replaced per host in one transaction.
- **D5 Indexing runs inline on save for that one post** (one post takes milliseconds; Lasso queues it only because
  its runner is shared), once per post per request; the full rebuild runs in WP-Cron batches (ADR-4: a daily event,
  chained batches, a lock), not Lasso's loopback runner or its `wp_options` queues.
- **D6 Statuses:** publish, future, draft, pending, private (decision 6, the user's; Lasso: publish and draft).
  Trashing removes the host's rows and untrashing re-indexes, which is what Lasso's `__trashed` filter intends (R12:
  its rows survive only because its save hook skips the status).
- **D7 Post types:** post, page and `wp_block` always, plus the Custom Post Types setting (Lasso's exclusion list
  with `affibloom_product` for Lasso's type); `cpt_support` is copied by Task 26.
- **D8 URL → product** follows R25's order with Affibloom's own lookups: the `affibloom/product-box` block's
  `productId`; `data-lasso-lid` and `[lasso id]` through the Lasso ID map (ADR-2); the cloaked permalink (F24); the
  product URL (normalised, `url_md5`); an Amazon ASIN + country; a retired ASIN (F1's history, R8). Only published
  products. lasso.to and Hub shortlinks are **C — never (Lasso cloud)** (`get_lasso_id_by_shortcode` only maps
  Hub slugs; `resolve_hub_amazon_shortlink_product_url` calls the Hub): such links index as External.
- **D9 Classification is Lasso's, including its quirks that change data:** `no_follow` = rel exactly "nofollow",
  `new_window` = target exactly "_blank" (R2), so this copy's counts match. Affibloom adds no sponsored column (Lasso
  has none) — the product's Sponsored is a product attribute, applied at render time (D13).
- **D10 Dismissals survive a rescan** (R4 loses them on the second scan): a row keeps `is_ignored` when its identity
  (D4) is unchanged. Owned with F22's dismiss action.
- **D11 Not built (cloud or cloud-only readers):** `lasso_content` (R7: read only by Intercom, Segment and the data
  sync) and `lasso_link_syncing` (R9) — **C — never (Lasso cloud)**; Lasso's `Background_Process_Runs` metrics become
  the Link Index card's figures (last run, posts, duration) in one option. Keyword tags (R35) are left out with keyword
  opportunities (the user, 2026-11-13).
- **D12 Settings → General** gains Lasso's Link Index, Custom Link Detection and Performance sections (R51-R54), and
  behind `&support=1` Keep Original URL, Cron "Time interval (hours)" and "Maximum … cronjob at a time" (R55), with
  Lasso's texts without the product name ("Affibloom tracks every link on your site."). The CPU threshold uses
  `sys_getloadavg()` where PHP has it and is ignored where it does not (Windows); a batch waits for the next cron run
  instead of sleeping. "Maximum cronjob at a time" limits Affibloom's own catch-up runner: when WP-Cron is disabled,
  an admin page load runs at most that many due batches. The screen inventory's "Link Index — Left out" is corrected.
- **D13 Render-time link attributes** (F1 D13): a `the_content` filter (priority 20, after blocks and shortcodes)
  walks anchors with `WP_HTML_Tag_Processor` and, for an anchor the resolver (D8) ties to a product, prints the
  product's public link (R40's rule, with Keep Original URL), `target="_blank"` or none (New Window), and `rel` =
  nofollow + sponsored + noopener exactly as R38 builds it; Amazon sponsored URLs, links to posts and pages and a
  cloaked link's own query string are kept (R39). It adds `data-affibloom-product` and `data-affibloom-location` for
  the click counter (F12's clicks half). Lasso's stamps are left in place (they are invisible and D4 reads them).
- **D14 Untied links at render time** get R38's `noopener` on `_blank` and R39's https and Amazon tag rule for raw
  Amazon links (the site's tag rule, F1 D12), so readers see what Lasso's scan stored; nothing else is changed.
  (corrected 2026-11-14, F35 sweep: Lasso retags an untied Amazon link only when a product has that ASIN, or when the
  link is an Amazon search page; every other raw Amazon link keeps its stored tag. The tag rule applies under that
  gate only.)
- **D15 Where the filter runs:** `the_content` (posts, pages, reusable blocks rendered inside them, REST
  `content.rendered`, feeds), `widget_block_content` and `widget_text`, ACF's `acf/format_value` for the chosen
  fields, Elementor's `elementor/frontend/the_content`. Builders and plugins with their own renderers are listed under
  "Needs the user".
- **D16 A product's save** (R19, R77) touches no post: its rows' `url` follows a new Primary URL, and render time (D13)
  does the rest; a repoint (F1 D14) moves its rows; a group's members are read live (D2).
- **D17 Messages:** "Rebuild started." / "A rebuild is already running." replace "Working... Please be patient." /
  "The processes are running." (U6's plain wording); Lasso's dialog "Ready to rebuild?" is kept.
- **D18 `wp affibloom links`:** `rebuild [--post=<id>] [--dry-run]` and `status`, the overview's `usage rebuild`
  renamed (it now indexes every link).

**Needs the user** (each would lose or replace part of Lasso):

- **N1 Links that only other plugins render.** WP Recipe Maker, Mediavine Create, TablePress, Beaver Builder and
  Thrive print their stored HTML through their own code; where no filter reaches that output (UNVERIFIED for each:
  35E checks their filters), their tied links keep the attributes stored when Lasso last scanned, because Affibloom
  will not rewrite their stores (D3). Lasso rewrote them in place (R44). **Recommendation:** accept; none is used here
  (0 recipes, cards or tables; Elementor is inactive), and 35E hooks every output filter that exists.
  → **Decided 2026-11-14:** A5: the user accepted the recommendation
- **N2 Lasso's display conversions in content** (R41, R45-R47): other plugins' shortcodes turned into `[lasso]`, SiteStripe
  iframes turned into a product box, a `[lasso]` in an image block turned into an image. **Recommendation:** do them at
  render time — Task 27's compatibility shortcodes draw the imported plugins' shortcodes, and 35J draws the SiteStripe
  and image-block cases (setting off and unused here).
  → **Decided 2026-11-14:** A4: the user accepted the recommendation: render time.
- **N3 Lasso's stamps stay in content after cutover** (2,790 anchors): stripping them (R50) is a content edit.
  **Recommendation:** keep them; they are invisible and D4 uses them.
  → **Decided 2026-11-14:** A2: the `data-lasso-*` stamps stay in content; nothing strips them.

## 5. Scope that needs a home

- **Locations** (the count, the tab, the Placements filter, the delete rule): F12's "Locations" sections, Tasks 36A-36E.
- **Opportunities** (link, domain, content reports, dismiss, the Opportunities tab): F22 reads R59's rows; D10 is
  shared with its dismiss action.
- **Clicks per location:** D13 prints `data-affibloom-location`; F12's clicks half records it (Lasso's per-location
  clicks come from its cloud, F12 R67).
- **Tables' location count and list** (R6): F25 reads `display_ref = table:<id>`.
- **Keep Original URL's rule** (R40) is part of the product's public link: F1's public link (and F24's cloaking) read
  the setting that 35G saves.
- **Migration** (Task 26): copy `cpt_support`, `custom_fields_support`, `keep_original_url`, the performance settings and
  dismissed rows' identities; the rows themselves are rebuilt (35A) after the products exist.
- **The screen inventory** (`docs/ui/screen-inventory.md` 129) still says Link Index is left out: corrected by D12.

## 6. Edge cases

1. A post saved twice in one request (Gutenberg's meta box save) is indexed once (D5).
2. A save while the rebuild's batch holds the same post: the per-host transaction makes the later write win; the
   rebuild skips hosts indexed after it started.
3. Malformed HTML: `WP_HTML_Tag_Processor` skips what it cannot read; the row count may differ from Lasso's
   simple_html_dom on broken markup (reported by `links rebuild --dry-run`).
4. A host with 100+ links (this copy: 102, 88, 83): one transaction per host, no row limit.
5. A product trashed, retired or deleted: its rows stay as untied rows until the next index of each host; the
   resolver never ties a link to an unpublished product; a repoint moves rows first (D16).
6. A link whose URL resolves to two products (duplicate destinations allowed): the first published by ID, as Lasso's
   `LIMIT 1` / `get_row`.
7. Shortcodes in block attributes with `"` quotes are decoded before parsing (R27).
8. A rebuild interrupted (timeout, fatal): the lock expires, the next cron run resumes at the stored page.
9. Render time on a page with no tied links: the filter returns early when the content has no `<a ` (cheap path);
   resolution results are cached per request.
