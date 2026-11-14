# F33 Import from other plugins: requirements

Status: gates 1-2 written 2026-11-14 from a sweep of Lasso 369's Import subsystem (Lasso's Import screen, its AJAX,
the import and revert jobs, the importable-links query, the notifications, the onboarding step and the WP All Import
hook). New feature: the feature plan's exclusion "Imports from other affiliate plugins" is withdrawn (everything Lasso
does is in scope, 2026-11-13). Gate 4 for Tasks IM1-IM20: given by the user 2026-11-14.

F33 is **not** F10. F10 moves Lasso's own data into Affibloom once (all families, CLI, report). F33 is Lasso's
feature for taking links over from *other* affiliate plugins, which Affibloom offers too; **Lasso becomes one more
source** in its list (IM17), served by F10's product mapper (D3).

## 1. User stories and acceptance criteria

- As a site owner moving from another link plugin, I see every link that plugin left on my site, take them over one by
  one or all at once, and the links and shortcodes already in my posts keep working without my posts being edited.
- As a site owner who changed my mind, I give an imported link back (Revert), one or all, and the product disappears.

- **AC1** Affibloom → **Import** lists the importable links of every source found on the site, ten to a page: Link
  Title (100 characters, then "..."), Import Target (the source's shortcode, else its address as a link, 100
  characters), Source, a "See It" cell and an Import / Revert cell; pending rows first, then by source and title.
  "Search URLs to Import" matches title or slug, spaces as wildcards; "All Sources" and one entry per source with
  links narrow the list; search, source and page are kept in the address. (IM1; R1-R9)
- **AC2** With no importable links, the list is empty and the source menu offers only "All Sources"; while the link
  index is being built for the first time the screen shows "Check back soon." instead of the list. (IM1, IM19; R10, R40)
- **AC3** **Import** on a row asks "Import This Link?"; Import creates a product from the source's data (field by field
  as section 3 for that source), the row's button becomes a tick, and the same source item elsewhere on the page ticks
  too; a refusal shows "Import failed." and changes nothing. (IM2; R11-R18, R20)
- **AC4** After an import, the source's old address (`/recommends/<slug>/`, `/<pretty-link slug>/`, …) answers with a
  301 to the product's address, and the source's shortcodes in posts draw the product, both with the source plugin
  inactive; no post's stored content changed. (IM2; R19, R21, D1, D2)
- **AC5** An imported row shows **Revert** (and **See It** when the product has locations); Revert asks "Revert This
  Import?" (Keep / Revert); Revert deletes the product the import created (a product the import only linked to is
  kept), the old address and the shortcodes fall back to what they did before, and the row returns to Import. A
  SiteStripe row's Revert is disabled. (IM2; R22-R26, D4)
- **AC6** **Bulk Import** first checks for a running bulk job ("Checking"): if one runs, "Currently bulk importing
  links. When this is complete, you can import more." with OK; else "Import All Links?" → Import All queues every
  pending row of the chosen source (or all sources) in the background, batches of 100, with "Updating Your Site" and
  "Importing and updating your links. For large bulk imports this may take some time with no progress bar movement.";
  the rows on the page tick. Closing the page does not stop it. (IM3; R27-R33)
- **AC7** With `&bulk-revert=1` in the address, **Bulk Revert** appears; "Revert All Links?" → Revert All reverts every
  imported item of the chosen source (or all) in the background; starting one bulk job stops the other. (IM3; R34-R36)
- **AC8** **See It** on an imported row opens "Import Locations": the posts that link to the product (title to its
  editor, address, type icon with "This is <type>."), six to a page, the page remembered per product. (IM18; R37)
- **AC9** On every Affibloom screen except Import, while a source has pending links and "Disable Import
  Notifications" is off, the banner "We found links you can upgrade. We recommend importing those links. Click here to
  import" shows (× closes it); after a bulk import finishes, "We are creating an index of your links. This will be
  complete in <time>. Please wait until then to begin any Imports." shows while the index is rebuilt. (IM19; R38-R41)
- **AC10** ThirstyAffiliates: each `thirstylink` post with a destination becomes a product: title, slug, destination,
  first image, Sponsored from its rel tags, groups from its categories; its old address uses `ta_link_prefix`. On this
  copy the 49 links are listed and `/recommends/adidas-dame-8-all-volleyball/` redirects once imported. (IM1, IM2;
  R42-R43, D7)
- **AC11** Pretty Links (Pro's `prli_links` with `link_cpt_id`): name, slug (last segment), URL, description,
  NoFollow, Sponsored, groups from categories and tags; old address with `base_slug_prefix`. (IM4; R44)
- **AC12** Lasso Lite / Simple URLs (`surl` posts with `_surl_redirect` or Lite details): every Lite field (button
  text, disclosure, description, price, second button, the four toggles, badge, custom image), its Amazon product's
  cached data, groups from Lite's categories; Lite links that came from Lasso are left out and counted in the "Lasso
  Lite is active. …" notice. (IM5, IM19; R45, R41)
- **AC13** Earnist (`earnist` posts): URL, description, price, button text, image (the post's thumbnail, else
  `_earn_image_url`), groups. (IM6; R46)  **AC14** Affiliate URLs (`affiliate_url` with `_affiliate_url_redirect`):
  URL, excerpt as description, thumbnail, groups. (IM7; R47)
- **AC15** Easy Affiliate Links (`easy_affiliate_link`, type text): URL, description, new tab / NoFollow (with
  "default" read from EAFL's settings), Sponsored, groups from `eafl_category`; the defined name is kept. (IM8; R48)
- **AC16** AAWP products (`aawp_products`): an Amazon product with AAWP's title, price, image, Prime, features,
  savings, rating and reviews, Sponsored on, AAWP's button text; row target `[amazon link="<ASIN>"]`. (IM9; R49)
- **AC17** AAWP lists (`aawp_lists`): every ASIN imported as AC16 and put in a group named after the list's keywords;
  target `[amazon bestseller|new|link="<keywords>" items="<n>"]`. Revert reverts every ASIN of the list. (IM10; R50)
- **AC18** AAWP tables (`aawp_table` posts): a comparison table titled as the AAWP table (column style, Cactus theme,
  no title, horizontal headers), its active products imported (highlight text as badge), AAWP's rows mapped to fields
  (shortcode → text, custom button → button, custom text/HTML → textarea, reviews → number, star rating → rating;
  missing labels "Custom text 1" …); `[amazon table="<id>"]` draws the table. Revert deletes the table. (IM11; R51)
- **AC19** EasyAzon (`easyazon_item_<ASIN>` options): an Amazon product with EasyAzon's title, price, URL and image;
  target `[easyazon_link identifier="<ASIN>"]<title>[/easyazon_link]`; an item without an identifier is "Amazon" with
  no URL and is refused. (IM12; R52)
- **AC20** AmaLinks Pro (`[amalinkspro]` shortcodes found in posts): one row per ASIN, title from the anchor text or
  the cached product; target the shortcode itself. (IM13; R53)
- **AC21** SiteStripe (Amazon SiteStripe embeds found in posts): one row per product, "<ASIN> Image" or
  "<ASIN> Product Box". (IM14; R54)  **AC22** WP Recipe Maker (equipment/ingredient links found in recipes): one row
  per distinct URL. (IM15; R55)  **AC23** Skimlinks, Genius, Tinyurl, Go. links found in posts: one row per URL,
  titled after the domain. (IM16; R56)
- **AC24** Lasso (while Lasso's data is on the site): every Lasso record not yet migrated is a row; Import runs F10's
  product migration for that record (`_affibloom_lasso_id`), Revert deletes that product. (IM17; D3)
- **AC25** With WP All Import active, a CSV row imported into Affibloom's products with Lasso's columns
  (`lasso_custom_redirect`, `lasso_fields` "type >> name >> value||…") sets the product's link and fields, so a file
  made by the Dashboard's Export CSV comes back in. (IM20; R57)

## 2. Evidence

All read in full: `classes/class-import.php` (1-1401); `pages/import-urls/class-ajax.php` (1-219);
`pages/import-urls/class-hook.php` (1-183); `admin/views/import/{import,header}.php`; `admin/views/rows/import-url.php`;
`admin/views/modals/{import-confirm,import-all-confirm,revert-confirm,revert-all-confirm}.php`;
`admin/views/modals/url-save.php` 12-24; `admin/assets/js/settings-import.js` (1-507);
`classes/background_processing/class-lasso-process-{import-all,revert-all,replace-shortcode,pretty-link-final-url,
update-category-for-imported-pretty-links}.php`; `models/class-revert.php` (1-206);
`classes/class-wp-all-import-csv-export.php` (1-640); `admin/views/notifications/{import-running,import-suggestion,
link-build-running-after-importing,lite-imported-from-pro}.php`; `admin/views/onboarding/imports.php`.
Shared code read in part: `classes/class-lasso-db.php` 2131-3093 (revertable and importable queries), 3085-3412
(Pretty Links, process_import, process_revert, AAWP), 5410-5442, 5663-5760, 5816-5828; `classes/class-helper.php`
1163-1230 (`get_rewrite_slug`), 1812-1823, 1930-1945, 2175-2345 (`format_importable_data`, `lasso_unique_post_name`),
4819-4980 (Lite counts), 5442-5454, 6038-6070, 6946-6948; `classes/class-lasso-cron.php` 140-160, 206, 288-289,
404-420, 1183-1197 (`update_url_everywhere`), 1766-2290 (the scan's shortcode conversion), 4967-4984, 6473-6476;
`classes/class-setting.php` 271-281, 1152-1197, 1236-1256, 1294-1345; `classes/class-setting-enum.php` 22, 56-90;
`classes/class-lasso-shortcode.php` 73-175; `pages/class-ajax.php` 109-132, 531-545, 596-603, 900-912, 2330-2385;
`pages/class-hook.php` 258, 1083-1085, 1801-1880 (WP All Import); `pages/fix/class-hook.php` 45-90, 145-195;
`classes/class-post-type.php` 330-345, 445-480; `classes/class-redirect.php` 175-205;
`classes/class-lasso-affiliate-link.php` 2745-2775; `classes/class-verbiage.php` 55-75; `admin/views/footer.php`
30-100, 150-165; `admin/views/settings/general.php` 530-547; `admin/views/header-new.php` 86-127;
`admin/assets/js/view-js.php` 1940-1960, 2100-2135, 2200-2225, 2440-2555, 3115-3145; `admin/assets/js/lasso-helper.js`
86, 689-702; `classes/class-lasso-amazon-api.php` 755-870 (product fetch: Creators API, else Lasso's service).

**This copy, 2026-11-14 (SELECT only):** none of the source plugins is installed (no `simple-urls`, `earnist`,
`thirstyaffiliates`, `pretty-link`, `affiliate-link-automation`, `aawp`, `easyazon`, `amalinkspro`,
`easy-affiliate-links`, `wp-recipe-maker` folders); `lasso_revert` has **0 rows** (Lasso never imported anything here);
no `prli_links`, `aawp_*`, `lasso_lite_*` tables; no `easyazon_item_*` options; no source shortcode in any post
(`[amazon`, `[aawp`, `[amalinkspro`, `[earnist`, `[easyazon_*`, `[simpleazon-*`, `[eafl`, `[thirstylink`: 0); no
`<ta` tags in published content; no link index rows of the AAWP, AmaLink, EasyAzon, Earnist, ThirstyLink, SiteStripe,
WP Recipe Maker or platform kinds. **But ThirstyAffiliates left data:** 49 published `thirstylink` posts (2023-01-09
to 2023-01-25), all with `_ta_destination_url` (31 Amazon), 47 with `_ta_image_ids`, categories "Volleyball Shoes"
(48) and "Uncategorized" (1), the options `ta_link_prefix` = `recommends`, `ta_show_cat_in_slug` empty,
`ta_no_follow` / `ta_new_window` = `yes`, `ta_link_redirect_type` = `302`, and 831 `_ta_*` meta rows. One published
guide (3297, "10 Best Volleyball Shoes For Hitters …") links to `/recommends/adidas-dame-8-all-volleyball/`, the
`thirstylink` post 6741; the draft 9492 links to two more (9263, 9267); 6 link-index rows hold `/recommends/` links,
all tied to no Lasso product. No `thirstylink` slug collides with a Lasso slug. `general_disable_notification` is off;
`enable_scan_notice_after_import` is `0`; no bulk-import options exist. **So Lasso's Import screen on this site would
list the 49 ThirstyAffiliates links** (its query does not require the plugin, R42).

## 3. What Lasso does

| # | Rule | Source |
|---|---|---|
| R1 | **Screen:** submenu "Import" (`edit.php?post_type=lasso-urls&page=import-urls`, eighth in Lasso's submenu); heading "Import", "Learn" (Lasso's help site), **Bulk Import**; a select "All Sources" plus one option per source with rows; a search "Search URLs to Import" | class-post-type.php 338-340; import/header.php |
| R2 | **List card:** header row Link Title, Import Target, Source, two unnamed cells; rows drawn by script into `#report-content` through the shared list request `lasso_report_urls` (`link_type=import-urls`, `edit_posts`, nonce `lasso_ajax`), paging links under the card with a range line | import/import.php 47-66; view-js.php 2537-2549; pages/class-ajax.php 109-132, 531-545 |
| R3 | **Row:** title cut at 100 characters + "..." with the full title as tooltip; target = the source's shortcode when it has one, else the address as a link (new tab, cut at 100); source label; "See It" only when imported and the product has locations; Import, or Revert (red) when imported; a tick icon after an action | rows/import-url.php |
| R4 | **Order and paging:** `ORDER BY check_status, import_source, post_title` (pending first); ten a page (`paginate`, a page past the end falls back to 1) | class-lasso-db.php 3058-3074; class-helper.php 5442-5454 |
| R5 | **Search:** `post_title LIKE` or `post_name LIKE`, spaces → `%`; kept in the address (`search`) | pages/class-ajax.php 122-125, 531-535; view-js.php 1947-1950 |
| R6 | **Source filter:** `import_source LIKE <label>`; changing it writes `filter` to the address, clears notices, goes to page 1; the select is rebuilt from the response's `plugins` with the address's value selected | pages/class-ajax.php 535-537; view-js.php 1953-1955, 3122-3144 |
| R7 | **Sources and labels:** Pretty Links, Thirsty Affiliates, Lasso Lite / Simple URLs, Earnist, Affiliate URLs, AAWP, EasyAzon, Easy Affiliate Links, AmaLinks Pro, WP Recipe Maker; platforms Skimlinks, Genius, Tinyurl, Go.; and SiteStripe. The menu lists only sources with rows (`GROUP BY import_source`), plus Lite when it is co-installed and has links or Lite-from-Pro links | class-setting-enum.php 56-90; class-lasso-db.php 2182-3074; pages/class-ajax.php 900-912; class-helper.php 4939-4961 |
| R8 | **Which sources are queried:** AAWP only when its `aawp_products` table exists (or it is the filter); Pretty Links only when `prli_links.link_cpt_id` exists; Lite only while `simple-urls` is active; WP Recipe Maker only while it is active; Earnist, EasyAzon, AmaLinks Pro, platforms, EAFL, Affiliate URLs, Thirsty Affiliates and SiteStripe **always** (data left by an uninstalled plugin is listed) | class-lasso-db.php 2192-2234, 2312-2336, 2339, 2374-2380, 2430-2734; 3306-3333 |
| R9 | **Counts:** the response carries `total` (all rows) and `total_pending_import`; the script writes "N pending · M total" or "N Found" into `#js-report-result` and the active `.subsubsub` badge — neither element is on the Import page, so nothing shows (UNVERIFIED: looked for `#js-report-result` and `.subsubsub` in import.php, header.php, header-new.php) | pages/class-ajax.php 538-543; view-js.php 2113-2128, 2206-2219 |
| R10 | **First-scan lock:** while Lasso's first activation scan has not finished, the header is hidden and the card shows "Check back soon." / "Lasso is indexing your links. When this is complete, you can import links from other plugins." with a loader; the list is hidden | import/import.php 18-20, 31-45 |
| R11 | **Import one (UI):** Import adds `js-popup-opened` and opens "Import This Link?" / "This will transfer and remove the link or shortcode from your old plugin into Lasso and replace it in all posts." / Cancel (red) · Import; static backdrop, no Esc; Import hides Cancel, shows a loading button, sends the row's `id`, `import_permalink`, `post_type`, `post_title` (kept in a JS array, not in attributes); success → close, every button of the same source and id flips to the tick with a green flash; failure → "Import failed." (red notice) | modals/import-confirm.php; settings-import.js 44-56, 188-204, 346-399, 453-477 |
| R12 | **`lasso_import_single_link`:** POST, `edit_posts`, nonce `lasso_ajax` (`security`) else 403 "Unauthorized."; `import_id` or `post_type` empty → `{ status: false }`; else `{ status, data }` (the import data, including the post object) | pages/import-urls/class-ajax.php 51-57, 105-133 |
| R13 | **Dispatch by post type:** `pretty-link`, `thirstylink`, `surl`, `earnist`, `affiliate_url`, `aawp`, `aawp_list`, `aawp_table`, `easyazon`, `amalinkspro`, `wp_recipe_maker`, `easy_affiliate_link`, `SiteStripe`, and the platform slugs; no data → `false` | class-import.php 180-216 |
| R14 | **The common save (`import_into_lasso`):** slug = the source's post name made unique; the link gets name, URL, slug, price, description, button text (default `primary_button_text`, "Buy Now"), second button URL/text (default the setting), new tab, NoFollow, second new tab/NoFollow, Sponsored, Show Disclosure (each the source's value or the setting), `is_opportunity` 1, badge, custom image URL, `is_import_platform`; groups from `cat_ids`; the thumbnail from `thumbnail_id[0]`; EAFL and Simple URLs keep the given name (`use_defined_affiliate_name`) | class-import.php 287-348 |
| R15 | **Refusals:** an empty name, URL or old address → `false`; a save that returns no Lasso post → `false` (WPRM first reuses an existing Lasso link with the same final URL) | class-import.php 354-385 |
| R16 | **The record (`process_import`):** for post-type sources (Pretty Links, Thirsty, Lite, Earnist, Affiliate URLs, EAFL) **the source's own post is turned into the Lasso link**: `post_type` → `lasso-urls`, `post_name` → the slug, modified now (same post ID); for AAWP, AmaLinks, EasyAzon, WPRM and platforms a new Lasso post. Then a `lasso_revert` row (`lasso_id`, `post_data` = the source post ID or SiteStripe URL or WPRM location id, `old_uri`, `plugin`, `revert_dt`) | class-lasso-db.php 3133-3174; class-import.php 393-400 |
| R17 | **Table `lasso_revert`:** `id`, `lasso_id`, `post_data` text, `old_uri` varchar(500), `plugin` varchar(200), `revert_dt`; keys on `lasso_id`, `old_uri`, `plugin`; `simple-urls/plugin.php` renamed `surl` (v316) | models/class-revert.php 21-50, 145-160 |
| R18 | **"Is imported":** a Lasso post that is published and has a revert row (or, for a table, a row `old_uri = [amazon table="<id>"]`) | class-import.php 1114-1178 |
| R19 | **Content rewrites on import (stored content):** (a) for every source except AAWP, AmaLinks and EasyAzon, every post that links to the old address gets the new Lasso address (`update_url_everywhere` → `update_link_in_post`); (b) for AAWP, Thirsty, AmaLinks and EasyAzon, the link index rows of the old id are pointed at the Lasso post and every post holding them is queued for the scan, which **rewrites the source's shortcode into `[lasso id ref link_id …]`** (AAWP `[amazon table]` → `[lasso type="table" id]`); (c) on two whitelisted sites an ACF field `affiliate_box_sidebar` is rewritten from `[amazon …]` to `[lasso id …]` | class-import.php 248-262; class-lasso-cron.php 1183-1197, 2073-2111, 2237-2290; process-replace-shortcode.php; import-urls/class-hook.php 45-94; class-verbiage.php 70-73 |
| R20 | **After import hooks:** `lasso_after_import_action( id, post_type, lasso_id, is_import_all )`; SiteStripe (single import only, with `enable_site_stripe_post_content_scan`) rescans the posts holding that ASIN; WPRM's index row is pointed at the new post | class-import.php 264-268; import-urls/class-hook.php 165-182; class-lasso-db.php 5678-5688 |
| R21 | **Old addresses keep working:** a front-end request whose host+path (http/https/www/slashes trimmed) equals a revert row's `old_uri` of a Lasso post is 301-redirected to that Lasso link, unless the paths are the same or a WordPress post has that slug; the prefixes of old addresses (e.g. `/recommends/`) are treated as Lasso's own by the link scan | class-post-type.php 445-478; class-redirect.php 175-202; class-lasso-affiliate-link.php 2745-2775; class-helper.php 1163-1230; class-lasso-cron.php 2597-2598 |
| R22 | **Revert one (UI):** "Revert This Import?" / "This will transfer the link from Lasso back to your old plugin. Note: this action removes the link from Lasso and can not be undone." / Keep (green) · Revert (red); static backdrop; failure "Revert failed."; SiteStripe's Revert is disabled | modals/revert-confirm.php; settings-import.js 59-71, 206-222, 401-443; rows/import-url.php 50-53 |
| R23 | **`lasso_revert_single_link`:** POST, same gate; `import_id`, `import_source`, `post_type` → `{ status, data: <the request> }` | pages/import-urls/class-ajax.php 138-156 |
| R24 | **Revert by source:** AAWP list → each ASIN's Lasso post trashed and its row deleted; AAWP table → its row deleted and the Lasso table deleted; AAWP, AmaLinks, EasyAzon, WPRM → the Lasso post trashed, row deleted; SiteStripe → row deleted only; post-type sources → `post_type` back to the plugin's (Pretty Links also gets its slug back), platforms → the Lasso post deleted for good; then `affiliate_link_type` meta deleted, and (not AAWP/AmaLinks/EasyAzon) every post linking to the Lasso address is rewritten back to the old address; AAWP/table/AmaLinks/EasyAzon/Thirsty posts are rescanned so the scan turns `[lasso]` back into the original shortcode; Rank Math's `delete_post` hook is held off during the revert | class-import.php 51-169; class-lasso-db.php 3183-3268; class-lasso-cron.php 2256-2277 |
| R25 | **After revert hook:** `lasso_after_revert_action( id, source )`; the ACF sidebar rewrite back on the whitelisted sites | class-import.php 164; import-urls/class-hook.php 103-141 |
| R26 | **Revert data:** a row is found by `post_data = id OR lasso_id = id` (+ plugin, + old_uri), preferring a row with a numeric `post_data` | models/class-revert.php 62-110 |
| R27 | **Bulk Import (UI):** the button checks `lasso_is_import_all_processing` (GET) showing "Checking"; processing → a dialog "Lasso is currently bulk importing links. When this is complete, you can import more." (OK, green); else "Import All Links?" / "This will transfer and remove all links or shortcodes from your old plugin into Lasso and replace it in all posts." / Cancel · Import All | settings-import.js 1-29, 484-507; modals/import-all-confirm.php |
| R28 | **Import All:** "Updating Your Site" with "Importing and updating your links. For large bulk imports this may take some time with no progress bar movement."; the bar goes 10 % then 100 % and the dialog closes after 1 s whatever the job does; every Import button on the page flips to ticked; a refused start → "Import failed." | settings-import.js 241-291; modals/url-save.php 16-22 |
| R29 | **`lasso_import_all_links`:** sets `lasso_import_all_enable` 1 and `lasso_import_all_process_completed` "false", starts the job with `filter_plugin` (a source label), `{ status: true }` | pages/import-urls/class-ajax.php 62-78 |
| R30 | **The job:** batches of 100 pending rows of the filter (kept in `lasso_import_all_filter_plugin`); nothing pending → the switch off, the cron cleared, a full rescan; else the revert job is stopped, `lasso_import_all_process` fires (Lite stops its own), each row is queued (`import_id`, `post_type`, title, address; WPRM rows keep their index id), the total is the queued count; a batch with nothing queueable stops the job | class-lasso-process-import-all.php 92-185; import-urls/class-hook.php 148-155 |
| R31 | **Continuing:** WP-Cron `lasso_import_all` every five minutes runs the next batch while the switch is on; when no pending row remains: cron cleared, `lasso_import_all_process_completed` "true", `enable_scan_notice_after_import` 1, a full rescan | class-lasso-cron.php 152, 288, 4967-4973; class-lasso-process-import-all.php 196-238 |
| R32 | **`lasso_is_import_all_processing`:** GET; `{ is_processing: switch on AND rows remaining }` (counts, not the process flag, "to cover the case wp-cron is not working") | pages/import-urls/class-ajax.php 206-218 |
| R33 | A bulk import calls the same single import (`is_import_all` true: no SiteStripe rescan) | class-lasso-process-import-all.php 49-73 |
| R34 | **Bulk Revert (UI):** the button is printed only when the address has `bulk-revert`; "Revert All Links?" / "This will transfer all imported links from Lasso into their original plugins. Note: this action removes the links from Lasso." / Cancel · Revert All (red); then as R28 with "Reverting and updating your links. For large bulk reverts this may take some time with no progress bar movement." and "Revert failed." | import/header.php 9, 23-27; modals/revert-all-confirm.php; settings-import.js 293-344 |
| R35 | **`lasso_revert_all_links` and its job:** as R29-R31 with `lasso_revert_all_*` options, `lasso_revert_all` cron, the revertable rows (`lasso_revert` of every supported plugin, or the filter's) in batches of 100, each task reverting one row (an `[amazon table` row reverts the table); it stops the import job | pages/import-urls/class-ajax.php 83-100; class-lasso-process-revert-all.php; class-lasso-db.php 2131-2170 |
| R36 | The import and revert crons are dropped when their `*_process_completed` is "true" | class-lasso-cron.php 404-420 |
| R37 | **See It:** "Import Locations" (large dialog, no buttons, paging): `lasso_get_import_locations` (POST, same gate; `lasso_id`, `page_number`, `limit` 6, `order_by` `post_modified`, `order_type` desc) → `{ status: 1, page, count, datas: [ { edit_post, post_link, post_title, icon_class, type_description } ] }`; header "Content" with tip "This is the post or page this table was found in.", column "Type"; the page is cached per product | settings-import.js 6-14, 73-186; pages/import-urls/class-ajax.php 161-201; lasso-helper.js 86 |
| R38 | **Import suggestion:** on every Lasso page except Import, while a source plugin (of `simple-urls`, `earnist`, `thirstyaffiliates`, `pretty-link`, `affiliate-link-automation`, `aawp`, `easyazon`, `amalinkspro`, `easy-affiliate-links`) is **active** and has pending rows, `general_disable_notification` is off and no Amazon auto-monetize backlog: "We found links you can upgrade. We recommend importing those links into Lasso. Click here to import" (×) | footer.php 71-81; notifications/import-suggestion.php; class-setting.php 271-281, 1152-1197, 1294-1345 |
| R39 | **"Disable Import Notifications"** (Settings → General, `general_disable_notification`, default off) hides R38 | settings/general.php 541-546; lasso-constant.php 138 |
| R40 | **After a bulk import:** while `enable_scan_notice_after_import` is 1 and the force scan is scheduled: "We are creating an index of your links. This will be complete in <ETA>. Please wait until then to begin any Imports."; the link scan sets the option back to 0 when it ends | footer.php 157-163; notifications/link-build-running-after-importing.php; class-lasso-process-link-database.php 701 |
| R41 | **Lite-from-Pro:** on Import, when Lasso Lite is active and `lasso_lite_revert` holds `surl` posts that came from Lasso: "Lasso Lite is active. N link(s) was/were imported to Lasso Lite from Lasso, so it/they may not appear here. To make it/them available again, open Lasso Lite, navigate to Import, and revert that link/those links." (×); `import-running.php` exists but is commented out | footer.php 83-92; notifications/lite-imported-from-pro.php; class-helper.php 4866-4891; notifications/import-running.php |
| R42 | **Thirsty Affiliates rows:** `thirstylink` posts with a non-empty `_ta_destination_url`, not already imported | class-lasso-db.php 2656-2686 |
| R43 | **Thirsty Affiliates data:** title, slug, URL `_ta_destination_url`; Sponsored 1 when `_ta_rel_tags` contains "sponsored", else the setting; groups = names of `thirstylink-category` terms; images `_ta_image_ids`; description ''; old address `get_the_permalink()` (TA's `/<prefix>/<slug>/` while TA is active); `_ta_no_follow`, `_ta_new_window`, redirect type and the rest are not read | class-import.php 457-474, 1062-1083 |
| R44 | **Pretty Links:** rows `pretty-link` posts present in `prli_links`, without `old_status`; data from `prli_links` by `link_cpt_id`: name, slug (last path segment), URL, description, `nofollow`, `sponsored`; groups = names of `pretty-link-category` and `pretty-link-tag` terms; old address home + (`prlipro_options.base_slug_prefix` when the slug lacks it) + slug; Revert restores `prli_links.slug` | class-lasso-db.php 2214-2230, 2338-2371, 3085-3093, 3213-3222; class-import.php 299-309, 420-450, 1063-1078 |
| R45 | **Lasso Lite / Simple URLs:** rows `surl` posts with `_surl_redirect` or a Lite details row, not reverted to Lite from Pro; data `_surl_redirect`, Lite's custom image, button text, disclosure, description, price, show price, second button URL/text, new tab, NoFollow, second new tab/NoFollow, Sponsored, badge (Lite meta, else Lite's settings, else Lasso's); groups from Lite's category; an Amazon URL brings Lite's cached Amazon product (`lasso_lite_amazon_products`) | class-lasso-db.php 2373-2427; class-import.php 481-519, 1232-1263; class-helper.php 6038-6070 |
| R46 | **Earnist:** rows `earnist` posts without `old_status`, any status (`publish` … `trash`); data `_earn_url`, `_earn_description`, `_earn_price`, `_earn_button_text`, the post thumbnail else `_earn_image_url` (made absolute) — **the image URL is computed but never passed to the save** (only `thumbnail_id` is); groups = the post's term-taxonomy IDs of every taxonomy | class-lasso-db.php 2312-2336; class-import.php 526-559, 331, 342, 1090-1106 |
| R47 | **Affiliate URLs:** rows `affiliate_url` posts with `_affiliate_url_redirect`; data that URL, `get_the_excerpt()`, thumbnail, term-taxonomy IDs | class-lasso-db.php 2626-2654; class-import.php 566-581 |
| R48 | **Easy Affiliate Links:** rows `easy_affiliate_link` posts with a meta value `text`; data `eafl_url`, `eafl_description`, `eafl_target` ("default" → `eafl_settings.default_target`, `_blank`), `eafl_nofollow` ("default" → `default_nofollow`, nofollow), `eafl_sponsored` "1"; groups `eafl_category` names; old address `get_permalink()` | class-lasso-db.php 2598-2624; class-import.php 963-993 |
| R49 | **AAWP products:** rows `aawp_products` not yet in `lasso_revert`; data from Lasso's Amazon cache, else AAWP's row (title, price + currency, URL from `urls.basic` tagged, image `m.media-amazon.com/images/I/<first image id>.jpg`, Prime, features, savings, rating, reviews, manual), else a fetch (R58); title "Amazon" when not found; Sponsored 1; button text `aawp_output.button_text`; old address = the ASIN; row target `[amazon link="<ASIN>"]` | class-lasso-db.php 2234-2258, 3340-3371; class-import.php 589-680; class-helper.php 2187-2192 |
| R50 | **AAWP lists:** rows `aawp_lists` (always listed); import: a group named `keywords` (created if missing), each ASIN imported as R49 and the product's groups set to that group (replacing); "imported" when its ASINs are Lasso products or the group exists; target `[amazon bestseller|new|link="<keywords>" items="<items_count>"]`, address `https://amazon.com/s?k=<keywords>` | class-lasso-db.php 2260-2277; class-import.php 687-721; class-helper.php 1930-1945, 2194-2209 |
| R51 | **AAWP tables:** rows published `aawp_table` posts; import: each active product of `_aawp_table_products` imported (highlight text → badge; any failure → no table), field values from `_aawp_table_rows` (F26 R37: shortcode/custom_text/custom_html/custom_button/reviews; star rating → rating field; missing labels numbered), a table (title = AAWP title, column style, Cactus, no title, horizontal headers) with its products, the first product carrying the mapped columns (title, image, price, primary button, rating + custom fields), badges set; revert row `[amazon table="<id>"]` | class-import.php 729-828, 1271-1400; models/class-revert.php 189-204 |
| R52 | **EasyAzon:** rows `easyazon_item_<ASIN>` options; data the option (identifier, title, `ListPrice` or `lowest_price_n`, URL, image 5 or the last, stock 200) stored as Lasso's Amazon product when not cached; no identifier → title "Amazon", empty URL (refused); old address = the ASIN; target `[easyazon_link identifier="<ASIN>"]<title>[/easyazon_link]` | class-lasso-db.php 2429-2457, 5410-5442, 5816-5828; class-import.php 835-885; class-helper.php 2214-2226 |
| R53 | **AmaLinks Pro:** rows from the link index (`display_type` amalinkspro/AMALink) not in revert; id = ASIN (or `asin` attribute), title the anchor text or the cached product name or a fetch; URL the shortcode's `apilink`, else the ASIN's Amazon link; old address = the ASIN; already-logged items show checked and disabled; the imported list's AmaLinks branch is gated on the **Affiliate URLs** filter (a bug: with the AmaLinks filter imported AmaLinks rows are not shown) | class-lasso-db.php 2459-2484, 2844-2897; class-import.php 894-916; class-helper.php 2227-2249 |
| R54 | **SiteStripe:** rows from the link index (`link_type` SiteStripe) grouped by domain, ASIN and tag; title "<ASIN> Image" (`format` starts `_SL` and `id=AsinImage`) or "<ASIN> Product Box"; data the cached product or a fetch; old address = ASIN, `post_data` = the SiteStripe URL; Revert disabled in the UI | class-lasso-db.php 2688-2735, 3019-3056; class-import.php 1002-1031; class-helper.php 2252-2264 |
| R55 | **WP Recipe Maker:** rows from the link index (`link_type` WpRecipeMaker) one per URL (MIN id per md5), not already imported by URL, not already a Lasso post; title the anchor text or URL; an import whose URL is already a Lasso link reuses it | class-lasso-db.php 2486-2533, 2899-2946, 5690-5722; class-import.php 926-954, 362-381 |
| R56 | **Platforms:** rows from the link index with `post_id` 0 and `affiliate_platform` skimlinks/genius/tinyurl/go.; id = md5 of the URL; title from the domain (`get_title_by_url`: the second-level name, dashes to spaces, words capitalised); `is_import_platform` 1; revert deletes the post | class-lasso-db.php 2535-2596, 2948-3017; class-import.php 1039-1053, 135-137; class-helper.php 1812-1823, 2250-2251 |
| R57 | **CSV:** no CSV import on the Import screen. CSV import is **WP All Import's**: Lasso hooks `pmxi_saved_post` and, for the saved post, sets the URL details from `lasso_custom_redirect` (Amazon or other-store product ID and type) and creates/attaches fields from `lasso_fields` ("type >> name >> value", `||` between; text, copy-to-clipboard, textarea, number, rating, bulleted and numbered lists). The matching export is the Dashboard's Export CSV (24 columns, F11 R28) | pages/class-hook.php 258, 1801-1880; class-wp-all-import-csv-export.php |
| R58 | **Cloud:** Amazon data for an ASIN not cached (AAWP fallback, AmaLinks/SiteStripe titles) comes from `fetch_product_info`: Amazon's Creators API when configured, else Lasso's broken-link service — **C — never (Lasso cloud)** for the latter | class-import.php 630, 903, 1013; class-lasso-amazon-api.php 755-870 |
| R59 | **Site-specific repairs (whitelists of other customers' domains):** Pretty Links final-URL map (`lasso_pretty_link_final_url_*` options from following each Pretty Link's redirects; its filter is commented out; the job runs only from the processes list), the one-off "update category for imported Pretty Links" job (three domains), the ACF sidebar rewrite (R19c), `fix_import_revert_post_name` (hook commented out) | process-pretty-link-final-url.php; process-update-category-for-imported-pretty-links.php; pages/fix/class-hook.php 61, 86, 154-195; class-verbiage.php 65-78 |
| R60 | **Onboarding step "Imports"** (step 6 of Lasso's install wizard) repeats the Import screen (Bulk Import, sources, search, the card with column "Plugin", the four modals) with "Continue →" | onboarding/imports.php |
| R61 | **Logs:** the jobs write `bulk_import`, `bulk_revert`, `import_link_replace` and `process_replace_shortcode` logs; Settings → Logs shows an "Import Log" ("All the plugin import connections.") read from a `*_import.log` file (UNVERIFIED which code writes that file: no `write_log( …, 'import' )` found) | class-import.php 143, 253; background jobs; settings/logs.php 41-46 |
| R62 | **Analytics:** `import_possible` sent to Segment/PostHog — Lasso's cloud, **C — never** | header-new.php 86, 104-127 |

## 4. Decisions

Settled by Claude from Lasso and the records:

- **D1 No post content is written.** Lasso's content rewrites (R19, R24's rewrite back, R59's ACF rewrite) are not
  built. The same visible result comes from: the source's shortcodes drawn by F31's compatibility handlers
  (27X1-27X7), which find a product imported by F33 through the import record (D5); the old addresses redirected by
  F24's old-address list (D2); plain links to the old address keep their `href` and are redirected. See "Needs the
  user" 1.
- **D2 Old addresses redirect as Lasso's R21:** each import adds its old address to F24's old-address list for the
  product (301 to the product's address, the same-path and WordPress-slug exceptions kept); Revert removes it. The
  old prefixes (`recommends`, Pretty Links' `base_slug_prefix`) are reported to F21's index so links to them resolve
  to the product, as R21's scan rule.
- **D3 Lasso is one more source.** While Lasso's data is on the site, "Lasso" is a source on the Import screen: its rows
  are Lasso records with no product (F10's `LassoIdMap`), Import runs F10's products step for one record (26A's mapper,
  `_affibloom_lasso_id`), Revert deletes that product. The Import screen and F10 share the source interface
  (`ImportSource`), the import record, the product creation path (`ProductService::create`) and the background runner;
  F10 keeps its CLI, report, `--update` and the other families. The Dashboard's one-time card (deviation 87) is F10's
  to remove (26O).
- **D4 Copy, never move.** Affibloom reads the source and leaves it untouched (F10 D1's rule): no `post_type` flip, no
  slug change in the other plugin, no trashing of the other plugin's data. Revert therefore means **delete the product
  the import created** (permanently, with its import record and old addresses); an import that only linked an existing
  product (D8) removes the record and keeps the product. See "Needs the user" 2 and 3.
- **D5 The import record** is product meta: `_affibloom_import_source` (Lasso's `plugin` value: `thirstylink`,
  `pretty-link`, `surl`, `earnist`, `affiliate_url`, `aawp`, `easyazon`, `amalinkspro`, `easy_affiliate_link`,
  `wp_recipe_maker`, `SiteStripe`, `skimlinks`, …), `_affibloom_import_ref` (the source's id: post ID, ASIN, list ID,
  location id, md5), `_affibloom_import_old_url`, `_affibloom_import_date`, `_affibloom_import_adopted` (D8). A table
  (AAWP) carries the same keys on the table; an AAWP list on its group (term meta).
- **D6 Who:** `Capabilities::PRODUCTS` for the screen and every route (Lasso: `edit_posts`); REST nonce through
  `wp.apiFetch`. Wording without the product name: "This will copy the link or shortcode from your old plugin and use
  it in all posts.", "Currently bulk importing links. When this is complete, you can import more.", "We found links
  you can upgrade. We recommend importing those links. Click here to import", "This will remove the product made from
  this link. Your old plugin's link is untouched." (the revert texts change because D4 changes what Revert does).
- **D7 ThirstyAffiliates' old address without the plugin:** `get_the_permalink()` of a `thirstylink` post gives no
  `/recommends/` address while ThirstyAffiliates is not installed (this copy); the address is then built from
  `ta_link_prefix` (`recommends`; `ta_link_prefix_custom` when the prefix is "custom") and, when `ta_show_cat_in_slug`
  is on, the post's `_ta_category_slug`. Pretty Links: R44's rule.
- **D8 An existing product is not duplicated.** When the source's URL (or ASIN) already belongs to a product, the import
  records itself against that product (`_affibloom_import_adopted` 1) instead of making a second one, as Lasso does for
  WP Recipe Maker (R15); the row shows imported.
- **D9 Amazon data without Lasso's cloud** (R58): the source's own cache is used (AAWP's table, EasyAzon's option, Lite's
  table); otherwise the product is an Amazon product with the ASIN, the title "Amazon" (Lasso's
  `DEFAULT_AMAZON_NAME`) and no price, refreshed by C1 when credentials exist. Nothing is fetched from Amazon pages.
- **D10 Earnist's image URL is used** when the post has no thumbnail (R46: Lasso computes it for that and loses it);
  Earnist and Affiliate URLs groups are the **names** of the post's terms (Lasso passes term-taxonomy IDs that only
  match a group by accident).
- **D11 Bulk jobs in the background:** one job at a time (import or revert; starting one stops the other, R30/R35),
  batches of 100, WP-Cron `affibloom_import_batch` every five minutes as a safety net plus an immediate dispatch, and a
  real progress line (done of total) instead of Lasso's fake bar; "is processing" = the job is on and rows remain
  (R32). The page can be closed.
- **D12 The list is drawn by the server** (ADR-7): the page prints the first list; search, source and page links
  fetch the same partial through REST and keep the address in step, as the Dashboard and Groups (F23 D13).
- **D13 Bulk Revert stays behind `&bulk-revert=1`** as Lasso's (R34): it is a destructive action Lasso hides.
- **D14 The AmaLinks filter bug (R53) is fixed:** imported AmaLinks rows are shown under the AmaLinks filter.
- **D15 After a bulk import** F21's index re-resolves the rows of the imported old addresses and ASINs (35I's repoint),
  and R40's notice shows while that runs; the first-build lock (R10) reads F21's first build.
- **D16 The import notice** (R38-R39) reads F9's `disable_import_notice`; the source must have pending rows (Lasso also
  requires the plugin to be active: kept). It is not shown on the Import screen.
- **D17 WP All Import (R57)** is supported for `affibloom_product` posts with Lasso's column names, so F11's Export CSV
  round-trips; fields through `FieldsService` (F26 section 5).
- **D18 SiteStripe, WP Recipe Maker, AmaLinks Pro and platform rows come from F21's index** (Lasso's link index is
  their only source); those tasks wait for F21.
- **D19 Rank Math's hook juggling (R24) and Lasso's logs (R61) are not copied:** Affibloom deletes its own products
  (no Rank Math link rows for them), and its jobs log to the debug log.

**Needs the user**

1. **Content is not rewritten (R19, R24).** Lasso turns `[amazon]`, `[thirstylink]`, `[amalinkspro]` and `[easyazon_*]`
   into `[lasso]` and old links into new ones inside stored posts, and back on revert. Affibloom leaves posts as they
   are; the shortcodes render through F31's handlers and old addresses redirect (D1, D2). Lost: the stored posts never
   name Affibloom, so with Affibloom deactivated those shortcodes fall back to the source plugin (or nothing) and old
   addresses to whatever answers them — which is the state before the import. **Recommendation: accept.**
   → **Decided 2026-11-14:** A4: the user accepted the recommendation
2. **Revert deletes the product (D4).** Lasso's revert hands the link back to the source plugin (it had taken its post).
   Affibloom never took it, so Revert deletes the imported product, including any edits made to it since; a product
   that an Affibloom block or shortcode already uses would leave that box empty. **Recommendation:** delete
   permanently as Lasso's "can not be undone", but refuse with F21 36E's in-use rule ("This product is used in N
   places") when Affibloom's own blocks use it.
   → **Decided 2026-11-14:** E10: the user accepted the recommendation
3. **Copy instead of move (D4).** Lasso changes the other plugin's posts into Lasso links (they vanish from that
   plugin). Affibloom copies; with the source plugin still active, its own prefix keeps answering its old addresses
   (the 301 of D2 takes over only once it is deactivated). **Recommendation: copy**, and say on the screen "Deactivate
   <plugin> after importing so its addresses come to Affibloom."
   → **Decided 2026-11-14:** E10: the user accepted the recommendation
4. **Amazon items without credentials (D9, R58).** AAWP, AmaLinks Pro and SiteStripe items that the source never
   cached import as "Amazon" with no price until C1's credentials exist. **Recommendation: import them so** (they
   still link, tagged), refresh on C1.
   → **Decided 2026-11-14:** E11: the user accepted the recommendation
5. **Site-specific repairs not built (R59, R19c):** the Pretty Links final-URL map, the one-off Pretty Links category
   job and the ACF sidebar rewrite serve other customers' domains (orlandolocal.com, williambeem.com, suburbiapress.com,
   gamecows.com, …) and do nothing on volleyballvault.com; the category job is covered by the import's own group
   mapping. **Recommendation: leave them out** (recorded here).
   → **Decided 2026-11-14:** E12: the user accepted the recommendation
6. **This site's 49 ThirstyAffiliates links.** ThirstyAffiliates is gone, but its 49 links remain and a published guide
   (3297) links to `/recommends/adidas-dame-8-all-volleyball/`, which the facts recheck lists as a 404 (Task 41 step 8).
   Importing ThirstyAffiliates (IM2) would make that address redirect to a product with the same destination.
   **Recommendation:** after IM2, import the one link the guide uses (or all 49) on staging, with the client's
   approval, and close Task 41 step 8 that way.
   → **Decided 2026-11-14:** E13: the user accepted the recommendation

## 5. Scope that needs a home

- **Old-address redirects** (R21): F24 owns the list and the redirect; F33 adds and removes entries (D2).
- **The shortcodes of the other plugins** (R19's visible result): F31 Tasks 27X1-27X8 own rendering; F33 adds the
  lookup of imported products (`ImportedProducts`) that `CompetitorProduct` consults besides Lasso's records.
- **The link index** (R10, R40, R53-R56, D15): F21 (35A-35J). **Locations** for See It: F12 L-tasks (36B's query).
- **Comparison tables and fields** for AAWP tables (R51): F25 (tables), F26 (`FieldsService`).
- **Onboarding's Imports step** (R60): F38 (navigation and onboarding), not yet swept — unowned until then.
- **"Disable Import Notifications"** is F9's switch (9K); F9 section 5 sends the notice itself to F38 — F33 owns it
  instead (IM19), since it is the Import subsystem's notice.
- **Dashboard Export CSV** (R57's export half): F11 R28 / D18.
- **`lasso_revert`** is listed by F10 as unowned (26G maps its `old_uri` to F24); with 0 rows here, F10 copies none.

## 6. Edge cases

1. Two editors import the same row: the second finds the import record (or D8's existing product) and gets 409
   "Already imported."; no duplicate product.
2. A source row disappears between list and click (plugin data deleted): 404 "This link is no longer in <source>.".
3. A source item with no name, no URL or no old address is refused as R15 (422 "Import failed." with the reason in
   the debug log); in a bulk job it is skipped and counted, and the job does not loop on it (R30's last rule).
4. A slug already taken by another product or post: made unique as R14 (`wp_unique_post_slug`); the old address still
   redirects (D2 uses the old address, not the slug).
5. The source plugin active and inactive: listing works either way (R8); the redirect takes over only when nothing else
   answers the old address (R21's WordPress-slug exception; "Needs the user" 3).
6. A bulk job interrupted (time limit, deactivation): the next cron tick or the next click continues; reactivation
   finds the job option and resumes; Revert of rows imported by a half-finished job works per row.
7. Revert of an AAWP list whose ASIN is also in another list or table: the product is kept while another import record
   or table uses it (D8's adopted rule applied to each ASIN).
8. Huge sources (thousands of Pretty Links): the list query pages in SQL; the bulk job holds IDs, not data.
