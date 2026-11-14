# F34 Content changes, history and repair tools: requirements

Status: gates 1-2 written 2026-11-14 from a sweep of Lasso 369 (every path that rewrites stored content, Post Content
History, the content side of Revert, Fix Overwritten Links, `class-fix.php`, `pages/fix/class-hook.php` and
`class-lasso-db-script.php`; `docs/plans/archive/dashboard-completion-plan.md`, "Content rewriting/history/repair").
**Decided 2026-11-14 (the user): A1, no legacy block conversion; A2, the `data-lasso-*` stamps stay. Tasks H1-H4 are dropped: this feature has no tasks and builds nothing.** Why: History, Revert and the clean-up exist only to list and undo content changes, and Affibloom makes none (the standing rule); with no conversion (ADR-003 revised) and no stamp removal there is nothing to list or revert. The requirements below are kept as the record of what Lasso does.

The standing rule is the user's: **nothing Affibloom builds ever edits post content** (deviation 52). This feature is
the complete list of what Lasso rewrites and what each rewrite is for (section 3, B), what Affibloom does instead
(D1's table), and the screens that exist only because Lasso rewrites. The one content change Affibloom makes is the
operator-run legacy block conversion (ADR-003, F10 Tasks 28A-28E, F10 "Needs the user" 1); this feature gives it
Lasso's History screens (list, diff, revert), and does the same for the optional `data-lasso-*` clean-up at retirement
if the user wants that clean-up (N3).

## 1. User stories and acceptance criteria

- As the site owner, I can see every post whose stored content Affibloom changed, what changed and when, and put any
  of them back, so a content change is never a one-way door.
- As the site owner, I know which of Lasso's rewrites Affibloom does not make and what (if anything) readers or
  editors lose by it, and I decide each loss myself.

- **AC1** (not built: decision A1) Affibloom → **History** lists the content changes Affibloom made (a converted post; a cleaned post when H3
  exists), newest first, ten to a page: Post ID, Post Name (edit and view icons), Post Type, Modified date (date and
  time on two lines), Action (**View**, **Revert**); "Search History" finds by post title, spaces as wildcards; the
  column headings sort; with none, "Looks like we're coming up empty."; a page number past the last opens page 1.
  (H1, R31-R36, D5-D7)
- **AC2** (not built: decision A1) **View** opens "View Post Content History": Post Name with its edit and view links, Post ID (the post's ID),
  Post Type, Modified Date, and "Content changed" with the difference between the content before and after the change
  (insertions and deletions marked, whole unchanged runs collapsed); an unknown or missing ID opens the History list.
  (H2, R37-R39, D8)
- **AC3** (not built: decision A1) **Revert** (on a row or on the detail page) asks "Revert this post?" ("The post's content goes back to what
  it was before this change. A revision of the current content is kept." / Cancel, Revert), shows
  "Saving your changes." with the bar, puts the post's content back byte for byte through `wp_update_post` (a revision
  is made), removes the change's record and says "Content reverted for “<post name>”."; the row leaves the list.
  (H2, R40-R43, D9-D11)
- **AC4** (not built: decision A1) A post edited after its change is not reverted from the screen: "“<post name>” was edited after this change.
  Revert it from WP-CLI with --force, or edit it by hand." and nothing is written; a post with a later change
  (clean-up after conversion) says "Revert the later change of this post first.". (H2, D10)
- **AC5** (not built: decision A1) History reads only Affibloom's records: Lasso's 18 `lasso_post_content_history` rows are not listed and the
  table is not touched (fingerprint unchanged). (H1, D3, N2)
- **AC6** (not built: decision A2) `wp affibloom lasso-attributes strip --dry-run` lists, per post, how many `data-lasso-id`,
  `data-lasso-name` and `data-lasso-lid` attributes it would remove (and the posts it skips, with the reason); without
  `--dry-run` it removes exactly those attributes and nothing else (every other byte equal), keeps a stored copy and a
  revision per post, records the change for History, and refuses to run before the Lasso migration has finished;
  `--revert` puts each post back byte for byte. (H3, R18, D14)
- **AC7** (not built: decision A2) Affibloom → History → **Remove Lasso attributes** card: the dry-run figures, a backup tick
  ("I have a database backup from today"), **Remove** one post per request with progress, behind the migration lock;
  the cleaned posts appear in the History list as "Lasso attributes removed". (H4, R18, D14)
- **AC8** (not built: decision A1) No other Affibloom code path writes `post_content`: a check over `src/` finds `wp_update_post` /
  `wp_insert_post` with content, `$wpdb` writes to `posts`, and `post_content` filters only in the converter, the
  clean-up and the revert. (H1, D2)

## 2. Evidence

All read in full: `classes/class-post-content-history.php`; `models/class-post-content-history.php`;
`admin/views/history/post_content.php`, `post_content_detail.php`; `admin/views/rows/post-content-history-row.php`;
`admin/assets/js/lasso-history.js`; `pages/post-content-history-detail/class-ajax.php`; `models/class-revert.php`;
`classes/background_processing/class-lasso-process-revert-all.php`, `class-lasso-process-replace-shortcode.php`,
`class-lasso-process-remove-attribute.php`, `class-lasso-process-remove-genius-formatting.php`;
`admin/views/modals/revert-confirm.php`, `revert-all-confirm.php`, `remove-attributes-confirm.php`;
`classes/class-fix-overwritten-links.php`; `pages/fix-overwritten-links/class-ajax.php`;
`admin/views/tools/fix-overwritten-links.php`; `admin/views/rows/fix-overwritten-links-row.php`;
`classes/class-fix.php` (1-330 read, 330-711 TablePress repair skimmed); `pages/fix/class-hook.php`;
`classes/class-lasso-repoint-merge.php` 105-211; `classes/class-post.php` 90-255; `classes/class-elementor.php`
174-240, 448-533; `classes/class-keyword.php` (every writer); `classes/class-mcp-insert-display.php` 107-295.
Read in the parts this feature uses: `classes/class-lasso-cron.php` 328-341, 1178-1200, 1650-1700, 4640-4905,
5020-5100, 5290-5480, 6600-6620, 6975-6988; `classes/class-lasso-db-script.php` (function list, every `UPDATE` /
`DELETE`); `classes/class-lasso-db.php` 5725-5810; `classes/class-helper.php` 1600-1640, 2610-2635, 5442-5455;
`classes/class-launch-darkly.php` 39-95; `classes/class-verbiage.php` 18-130; `classes/class-import.php` 60-160,
250-280; `classes/class-lasso-affiliate-link.php` 1140-1160, 1796-1825, 3595-3606;
`classes/class-lasso-shortcode.php` 1725-1782; `classes/background_processing/class-lasso-process-auto-monetize.php`
44-88; `classes/class-lasso-amazon-api.php` 1383-1420; `pages/class-ajax.php` 109-175, 520-610, 1017-1170, 1440-1450;
`pages/url-details/class-ajax.php` 29-100, 400-500; `pages/settings-general/class-ajax.php` 150-300, 455-480,
1005-1040; `pages/class-hook.php` 470-512, 1198-1214; `classes/class-post-type.php` 335-355;
`admin/views/settings/general.php` 540-625; `admin/views/settings/uninstall.php` 25-140;
`admin/views/dashboard/url-details.php` 2150-2330; `admin/assets/js/view-js.php` 2280-2295, 2500-2615, 2620-2650,
2790-2860, 3051-3120; `admin/assets/js/lasso-monetize-modal.js` 60-120; `admin/lasso-constant.php` 46, 376, 786.
The scanner's write-back is F21's evidence (F21 R37-R50), rechecked here at the lines named in section 3.

**This copy, 2026-11-14** (SELECTs only, `--skip-plugins`; note: Local's `active_plugins` lists Lasso as active):

- `wp_lasso_post_content_history`: **18 rows for 17 post IDs**, 2026-10-27 11:54 to 2026-11-06 00:04, 7.8 MB (both
  columns). Two rows (15086, 15097) belong to posts that no longer exist, so Lasso's list (an `INNER JOIN`) would show
  16. Post 3906 has two identical rows written in the same second. What changed: **first stamping** of three guides
  (5149: 0 → 38 `data-lasso-id`, 21 `data-lasso-lid`/`-name`; 4531: 0 → 63; 4910: 0 → 64); **one stamp added** to an
  internal link (5733, 5532); **an Amazon ASIN replaced in a button's href** (4375: B0CWHXVT5Z → B0CWHZ3WVR; 3297:
  B0CNKT39C6 → B0H4VR3ZH9; these are `wp_lasso_link_updates` rows 1-2); **parser side effects**: `viewBox` lowercased
  to `viewbox` in inline SVG (9119), `&amp;` decoded to `&` inside hrefs (5824), 1-3 bytes elsewhere. No post's live
  content equals its `old_value` (nothing was ever reverted); 7 equal `new_value`, the rest were edited later.
- Rows older than 14 days are still there because Lasso's daily clean (R35) has not run on this copy.
- `enable_history` = true (Lasso's default); the cloud flag `audit_log` has no cached value (screens hidden).
- `wp_lasso_revert`: **0**. `wp_lasso_link_updates`: **3** (retired ASIN → new ASIN; not a content log: R62).
- Stamps in post, page and reusable-block content: **`data-lasso-id` 2,791 in 180 posts** (134 published posts, 2
  drafts, 13 pages, 31 reusable blocks), **`data-lasso-name` 698 and `data-lasso-lid` 698 in 49 posts**; no other
  `data-lasso-*` name; no `data-old-href`, no `<keyword>`, no `data-keyword-location-id`; 1 `lasso-scan-ignore`; 5
  revisions (of 2 posts) also hold stamps. No Elementor or Thrive data with stamps.
- 250 `affiliate-plugin/lasso` blocks (all statuses but trash, reusable blocks included): none with invalid JSON
  attributes, none whose block attributes miss a shortcode attribute, none double-encoded, none inside `core/html`.
- 1 post with a Yoast FAQ block (5506), with no links and no stamps. 5 `nab_hidden` posts hold 40 `<ta>` tags
  (ThirstyAffiliates). No Divi or Kadence blocks; no `/lasso-urls/` hrefs; `rewrite_slug` empty.
- Fix Overwritten Links candidates ("New and Used…" titles or slugs): **0** (170 records carry `link_id` meta).
- No tracked keywords, no keyword locations, no geni.us products, no Amazon Affiliate Plus rows, no MCP receipts;
  `keep_genius_formatting`, `auto_monetize_amazon` and `auto_monetize_affiliates` are all on.
- Affibloom: no `_affibloom_pre_conversion` meta yet (Task 28B is not built).

## 3. What Lasso does

### A. The writer every path uses

| # | Rule | Source |
|---|---|---|
| R1 | **`Lasso_Cron::update_post_content( post, content, before = null )`** writes `wp_posts.post_content` with `$wpdb` (no `wp_update_post`: no revision, no `save_post`, no `post_modified` change, no kses); with `before` the `UPDATE` matches `BINARY post_content = before` and returns false when the row changed meanwhile; `clean_post_cache()` before and after; then `Post_Content_History::track_changes()` | class-lasso-cron.php 5305-5371 |
| R2 | **Thrive Architect:** when Thrive exists and the post has `tcb_editor_enabled`, the content is read from and written to Thrive's meta instead, and recorded the same way | 5310-5328; class-helper.php 5465-5510 |
| R3 | **Elementor** content is written to `_elementor_data` (`update_metadata`, `wp_slash( wp_json_encode() )`): mode `monetize` replaces one stamped anchor's href (and rel, target, `noopener`); mode `remove` strips `data-lasso-id`/`-name` from every `editor` setting; `update_links()` re-stamps `editor` and `html` settings after a scan (href, `data-lasso-id`, `-name`, target, rel) | class-elementor.php 174-240, 448-533; class-post.php 98-250; class-lasso-cron.php 3050, 4531-4545 |
| R4 | **No cache purge** beyond `clean_post_cache()`; no page-cache plugin is told (UNVERIFIED: searched the writer and its callers for `litespeed`, `wp_cache_flush`, `do_action( 'lasso_` after writes; none found) | 5305-5371 |
| R5 | Several paths parse and re-serialise the whole post with Lasso's DOM parser, so bytes Lasso did not mean to touch change too (this copy: `viewBox` → `viewbox`, `&amp;` → `&` in hrefs); `Helper::update_link_in_post` also runs the whole post through `wp_kses_post()` | class-helper.php 1605-1634; this copy's history rows |

### B. Every path that rewrites stored content

"Writer" is R1 unless named. Before → after examples are this copy's or Lasso's own comments.

| # | Path, trigger | What changes (before → after) | Purpose | Source |
|---|---|---|---|---|
| R6 | **The link scanner** (save of a post, the cron and force scans, Rebuild Database, Start Link Indexing): every scanned post | stamps (`<a href="…amazon.com/dp/B0F572WQGY?tag=vbvault-20" …>` → `… data-lasso-id="15484" data-lasso-lid="15075" data-lasso-name="Apex Sports Volleyball Spike Trainer">`), rel/target from the product, `noopener` on every `_blank`, https, canonical Amazon URLs with the site's tag, a tied link's href → the product's public link, shortcodes rewritten with `link_id`/`id`/`ref`, other plugins' shortcodes ↔ `[lasso]`, block repairs (section E); Elementor, Beaver, ACF, WPRM, Mediavine, Kadence stores too | the index's identity, and posts' links following the product | F21 R37-R47 (class-lasso-cron.php 1482-1600, 2589-3030, 2176-2344) |
| R7 | **"Updating Your Site" after a product save** when New Window, NoFollow (either), Sponsored, Link Cloaking, the Primary URL or the slug changed: `Lasso_Process_Scan_Link` re-scans every post that links to the product | for the product's stamped anchors: `target` `_blank`/`_self`, href = the permalink (Amazon: the Primary URL with its tracking ID), then R6 | text links follow the product's settings | class-lasso-affiliate-link.php 1798-1812; class-lasso-cron.php 4722-4800; F1 S9 |
| R8 | **A retired ASIN** (`lasso_link_updates`, F1 history): the scan maps an Amazon href of the old ASIN to the product, and R6/R7 write the product's link | `…/dp/B0CWHXVT5Z?tag=vbvault-20` → `…/dp/B0CWHZ3WVR?tag=vbvault-20` (post 4375, 2026-10-29) | readers reach the live product | class-lasso-affiliate-link.php 2537-2666; this copy |
| R9 | **Repoint / merge** (`lasso_confirm_repoint_merge`, `edit_post` on both, `lasso_ajax`) | **nothing in content**: link-location and map rows move to the target, the source gets `lasso_repointed_to_lasso_id` and status `lasso_delete`; "Does not rewrite host post_content or shortcodes" | one product for one URL | class-lasso-repoint-merge.php 105-211; url-details/class-ajax.php 70-98 |
| R10 | **Force delete** ("Hold Up" → Delete This Link): `lasso_remove_links_and_shortcodes_from_post` per post (`lasso_ajax` nonce, `edit_post` on post and link) | every `[lasso … id="<id>" …]` removed; each `<a>` whose href equals the product's public link gets the Primary URL instead (text and other attributes kept); then the post's location rows are deleted | delete a product that is in use | url-details/class-ajax.php 446-489; class-lasso-cron.php 1657-1697; F1 X8 |
| R11 | **Monetize a link** (Opportunities' toggle → "Monetize" in the link modal: `lasso_popup_monetize_link`, `edit_posts` then `edit_post`) | the stamped anchor's href → the chosen product's URL, target from the product; then a re-scan (R6); Elementor mode `monetize` | turn a plain link into an affiliate link | pages/class-ajax.php 1017-1060; class-lasso-cron.php 4653-4712; lasso-monetize-modal.js 60-120 |
| R12 | **Monetize an Amazon link** (`monetize_amazon_link`): an `amzn.` short link is resolved, the product found or created, the href set to the cached monetized URL | `https://amzn.to/x` → `https://www.amazon.com/dp/<ASIN>?tag=…` | Amazon links carry the site's tag | pages/class-ajax.php 1112-1170; class-lasso-amazon-api.php 1383-1420 |
| R13 | **Unmonetize** (`lasso_unmonetized_link`) | the anchor's href goes back to the location's `original_link_slug`, then a re-scan | undo R11/R12 | pages/class-ajax.php 1072-1110 |
| R14 | **Keyword linking** (keyword opportunities: add/delete keyword, monetize, unmonetize): `<keyword data-keyword-id="N">word</keyword>` wrapped around matches, replaced by `<a data-keyword-location-id="N" href rel target>`, unwrapped again | `word` ↔ `<keyword …>word</keyword>` ↔ `<a …>word</a>` | link a product's keywords | class-keyword.php 43-80, 226-325, 415-523 |
| R15 | **Auto-monetize Amazon (hourly, `auto_monetize_amazon`)** `uncloak_amazon_link`: each Amazon short link in the index whose target is a different product → the long monetized URL, via `Helper::update_link_in_post` (whole post through `wp_kses_post`, R5) | `https://amzn.to/x` → `https://www.amazon.com/dp/<ASIN>?tag=…` | tag every Amazon link | class-lasso-cron.php 4926-4935, 5028-5097; class-helper.php 1605-1634 |
| R16 | **Auto-monetize affiliates** (`auto_monetize_affiliates`): during the scan each href is offered to the network classes, which create products (`link_cloaking` 0, "Does not change the original URL on post content"); the scan then ties and rewrites as R6 | as R6 | affiliate networks' links become products | pages/fix/class-hook.php 61-63, 747-766; class-lasso-process-auto-monetize.php 44-88 |
| R17 | **Import and import revert** (F33): an imported link's old URL → the product's public link everywhere (`update_url_everywhere`), other plugins' shortcodes → `[lasso]` (`Lasso_Process_Replace_Shortcode`, "Updating shortcodes", a re-scan of each post that holds them); revert writes the old URL back and the scan turns `[lasso]` back into the old plugin's shortcode | `[thirstylink ids="12"]` ↔ `[lasso id="…" …]`; `/recommends/x/` ↔ the product's link | one plugin for every link | class-import.php 130-160, 250-280; class-lasso-cron.php 1183-1198; process-replace-shortcode.php 41-95 |
| R18 | **Uninstall Lasso / "Remove Lasso Attributes"** (`lasso_remove_lasso_attributes`, settings nonce, the settings capability; Settings → General, or `uninstall.php` with `check_all`): disables every process, then each post in the index: removes `data-lasso-id` and `data-lasso-name` (always; `data-lasso-lid` is **not** removed), optionally sets each stamped anchor's href to the product's Primary URL ("Remove all Lasso link cloaking."), optionally deletes every `affiliate-plugin/lasso` block and every `[lasso]` ("Remove all Lasso displays and shortcodes."); deletes the post's index rows; Elementor mode `remove`; re-queues while rows remain; "Removal Progress" / "ETA" / "We're removing Lasso attributes in the background, feel free to do something else." / "All Lasso displays, links, and attributes have been removed. You can now deactivate and delete the Lasso plugin." | `<a … data-lasso-id="8734" data-lasso-name="ASICS Upcourt 6" data-lasso-lid="13133">` → `<a … data-lasso-lid="13133">` | leave clean HTML after Lasso | process-remove-attribute.php 47-127; settings-general/class-ajax.php 150-245; class-lasso-cron.php 4807-4900; remove-attributes-confirm.php; general.php 597-624; F9 R63-R64 |
| R19 | **"Start Link Indexing"** (`lasso_rescan_lasso_attributes`): re-enables processes and starts the link database scan (R6) — undoes R18's stop | as R6 | resume after a removal | settings-general/class-ajax.php 1014-1037 |
| R20 | **Cloaked Link Prefix change**: every published and draft post re-scanned and its cloaked links rewritten to the new prefix | `/recommends/x/` → `/go/x/` | links follow the prefix | F9 R18 |
| R21 | **Keep geni.us formatting off** (Amazon tab): `Lasso_Process_Remove_Genius_Formatting` re-saves every product whose Primary URL is a geni.us link with the resolved URL; the save's R7 scan rewrites posts | geni.us link → the destination | uncloak geni.us | process-remove-genius-formatting.php; settings-general/class-ajax.php 455-475; class-lasso-affiliate-link.php 1142-1160, 3595-3606 |
| R22 | **Group removal** re-scans the group's posts (F23 R18) | as R6 | — | F23 R18 |
| R23 | **MCP insert display** (`lasso/v1` MCP routes, HMAC from Lasso's cloud): preview → confirm inserts an `affiliate-plugin/lasso` block (end, or after the n-th top-level heading) with compare-and-swap on a content hash, a revision first (`save_pre_write_revision`), `post_modified` bumped, an undo receipt (transient + post meta) holding the old content; undo refuses when content changed since ("Post content changed since confirm; undo refused.") | content + one Lasso block | AI agent inserts a display | class-mcp-insert-display.php 107-295, 642-756; F4 R76-R79 |
| R24 | **Elementor after save** and Elementor shortcode fixes (`fix_shortcode_elementor`) | `_elementor_data` as R3 | — | class-elementor.php 301-419; F4 N6 |
| R25 | **Yoast FAQ on every post save** (`wp_insert_post_data`, not only in a scan): a post with `yoast/faq-block` gets its `questions` attribute rebuilt from the saved HTML when empty, and `data-lasso-id/-name/-lid` and `data-old-href` stripped inside the FAQ (HTML and `questions` JSON) | stamps inside FAQ answers removed | repair Lasso's own stamps, which break Yoast's block validation | pages/fix/class-hook.php 74, 1251-1616 |
| R26 | **Not a post-content writer:** Easy Affiliate Links' link posts' searchable `post_content` (EAFL takeover); a Lasso link's own `post_content` (kept '' or as is) | other plugins' and Lasso's own records | — | class-lasso-shortcode.php 1725-1782; pages/class-ajax.php 1440-1450 |

### C. Post Content History

| # | Rule | Source |
|---|---|---|
| R27 | **Storage:** `{prefix}lasso_post_content_history (id, object_id, old_value longtext, new_value longtext, updated_date datetime)`, created on activation and by the update to v277 | models/class-post-content-history.php 26-58; class-activator.php 103; class-update-db.php 211 |
| R28 | **What is recorded:** every R1 write (and Thrive's) calls `track_changes( post, old, new )`: nothing when the setting `enable_history` is off; nothing when old and new are equal (`compare_string`); **nothing when the post already has a row** — only the **first** change per post is kept (`is_object_existed`), and the v277 update deleted every row but the first of each post | class-post-content-history.php 232-257, 289-294; models 64-82; class-lasso-db-script.php 602-605 |
| R29 | Recording does **not** depend on the cloud flag: `enable_history` defaults to **true** (`lasso-constant.php` 376), so every Lasso site records; only the screens and the toggle are behind the flag | class-post-content-history.php 232-234; lasso-constant.php 376 |
| R30 | **Kept 14 days:** the daily clean (`lasso_clean_link_locations`) deletes rows whose `updated_date` is before 23:59:59 fourteen days ago | class-lasso-cron.php 328-341, 6606-6618 |
| R31 | **The cloud flag:** the History submenu ("History", position 11), both pages and the Settings toggle appear only when `Lasso_Launch_Darkly::enable_audit_log()` is true — a request to `https://lasso.link/api/launch-darkly/flags` with the account's e-mail (encrypted), cached; default false | class-launch-darkly.php 39-95; class-post-type.php 344-346; pages/class-hook.php 498-511; general.php 550-559; lasso-constant.php 786 |
| R32 | **Settings → General → "Audit"** (behind R31): "Toggle this if you want to track changes.", toggle **Post Content History** (`enable_history`) | general.php 550-559 |
| R33 | **The list** (`page=post-content-history`, "History - Post Content"): title "Post Content History"; "Search History" (`p.post_title LIKE`, spaces → `%`); columns **Post ID** (sort `p.ID`), **Post Name** (sort), **Post Type** (sort; the type's plural label), **Modified date** (sort, default `h.updated_date desc`, tie `h.id`), **Action**; ten a page (`Helper::paginate`; a page past the last falls back to page 1); rows by `lasso_report_urls` (`lasso_ajax`, `edit_posts`), `INNER JOIN` posts (rows of deleted posts are not listed) | post_content.php; pages/class-ajax.php 109-175, 565-605; class-lasso-db.php 5760-5781; class-helper.php 5442-5455 |
| R34 | **A row:** Post ID, Post Name (edit and view icons, hidden by `d-none`), Post Type, Modified date (`date_i18n( 'd M, Y<br>H:i:s' )`), **View** (green) → the detail page, **Revert** (red) | post-content-history-row.php; class-helper.php 2617-2633 |
| R35 | **Empty list:** the generic "Looks like we're coming up empty." | view-js.php 2599-2607 |
| R36 | `edit_url` = `get_edit_post_link`, `view_url` = `get_permalink`, `post_type` = the type object's label | class-helper.php 2617-2633 |
| R37 | **Detail** (`page=post-content-history-detail&id=<history id>`): an unknown ID redirects to the Dashboard; "View Post Content History"; Post Name with edit and view icons (new tab); "Post ID:" shows the **history** ID (a Lasso bug); "Post Type:"; "Modified Date:" on one line | post_content_detail.php 13-55 |
| R38 | **The diff:** Google's diff-match-patch (`libs/google-diff_match_patch`), `diff_main( old, new )`, `diff_cleanupSemantic`, `diff_prettyHtml` (insertions and deletions marked) with the ¶ marks removed, under "Content changed" | post_content_detail.php 57-96; pages/class-hook.php 1206-1208 |
| R39 | The detail's **Revert** button (red) is hidden until the script runs | post_content_detail.php 64-72, 98 |
| R40 | **Revert** (row or detail; **no confirmation**): the save dialog `#url-save` with "Saving your changes." and the bar → `lasso_revert_post_content` (`lasso_ajax`; `edit_post` on the post, else 403 "Unauthorized."; an unknown ID → `status false`, "History ID: <id> does not exist.") → success "Successfully reverted content for "<post name>"." or the error bar | post_content_detail.php 98-145; lasso-history.js; post-content-history-detail/class-ajax.php 26-79 |
| R41 | **What revert writes:** `clean_post_cache`, then `$wpdb->update( posts, post_content = old_value )` — no revision, no edited-since check, no save hooks, the history row **kept**; Thrive and Elementor stores not restored; the next scan re-stamps the post | class-post-content-history.php 264-281 |
| R42 | Because only the first change is kept (R28), Revert puts the post back to **before Lasso's first rewrite**, dropping every later edit by anyone | R28, R41 |
| R43 | After a revert the list is not redrawn (the row stays) | lasso-history.js |

### D. Revert (the content side; the screens are F33's)

| # | Rule | Source |
|---|---|---|
| R44 | `lasso_revert (id, lasso_id, post_data, old_uri, plugin, revert_dt)`: one row per link imported from another plugin; Revert This Import ("Revert This Import?" / "This will transfer the link from Lasso back to your old plugin. Note: this action removes the link from Lasso and can not be undone." / Keep, Revert) and Revert All ("Revert All Links?" / "This will transfer all imported links from Lasso into their original plugins. Note: this action removes the links from Lasso." / Cancel, Revert All) | models/class-revert.php; revert-confirm.php; revert-all-confirm.php |
| R45 | Revert All runs `Lasso_Process_Revert_All` (100 a page, `lasso_revert_all_enable`, `lasso_revert_all_filter_plugin`, cron `LASSO_CRON_REVERT_ALL`); each item `process_single_link_revert`; content is changed only through R17 (old URL back, shortcodes back), then a full re-scan | process-revert-all.php; class-import.php 130-160 |
| R46 | Import's revert is **not** Post Content History's revert: the two share only R1 | — |

### E. Repair tools

| # | Rule | Source |
|---|---|---|
| R47 | **Fix Overwritten Links** (`page=fix-overwritten-links`, no menu entry): "Review links whose titles or slugs were overwritten by Hub→WP Link Sync, then restore the proposed custom values."; "Fix" select (Title & slug, Title only, Slug only), **Fix selected**, **Fix all** (black); columns select-all, Current (title, /slug/), Proposed (green), Pages, Details (**Open**); empty "No overwritten links found." / "High-confidence Hub→WP title/slug overwrites will appear here." | tools/fix-overwritten-links.php; fix-overwritten-links-row.php; view-js.php 2589-2597 |
| R48 | **Candidates:** a published Lasso link titled "New and Used%" or slugged "new-and-used-%" that shares its `link_id` meta with another published link not so named; proposal = the other's title and slug; Pages = its location count; sorted by Pages desc | class-fix-overwritten-links.php (detection SQL) |
| R49 | `lasso_get_overwritten_links` / `lasso_fix_overwritten_links` (`lasso_ajax`, `manage_options` or Lasso's permission level): per ID `edit_post`; slug fix first trashes the sibling when it has no locations, keeps `lasso_old_slug`, then `wp_update_post` title/slug; skipped reasons `not_a_candidate`, `cannot_edit_post`, `invalid_post`, `cannot_trash_sibling`, `nothing_to_update`; then a full re-scan. Messages: "Select at least one link to fix.", "Fix all listed links (<scope>)? This will start Scan all links afterward." (`window.confirm`), "Fixed N link(s). Scan all links has been started.", "Unable to fix overwritten links." | pages/fix-overwritten-links/class-ajax.php; view-js.php 3051-3118 |
| R50 | **Scan-time content fixes** (`FILTER_FIX_CONTENT_BEFORE_SCANNING`, run before every scan's write-back): Kadence list items (restore the item's original href/target/rel, strip stamps, add `lasso-scan-ignore`), Yoast FAQ rehydration and stamp stripping, Lasso blocks unwrapped from `core/html` (SUPPORT_SITES), Divi `value` quotes re-encoded | pages/fix/class-hook.php 46-52, 305-410, 1026-1250 |
| R51 | **Per-anchor fixes** (`FILTER_ATAG_AT_FIRST`, inside the scan): Amazon Affiliate Plus ASIN replacement, href shortcode (SUPPORT_SITES), revert an Amazon "sponsored" URL to the stored original, auto-detect affiliate links (R16), drop `/lasso-urls/` when there is no prefix; seven more are commented out | 53-70, 563-990 |
| R52 | **Lasso block fixes** (`FILTER_FIX_LASSO_BLOCK_IN_GUTENBERG`): `&amp;quot;` → `&quot;` (and lt, gt, amp), invalid block JSON rebuilt from the inner `[lasso]`, block attributes completed from the shortcode's, Data443 IRP block markup, Yoast FAQ (again) | 71-83, 226-505, 1617-1660 |
| R53 | Other hooks: `get_final_url_domain_bls` (which URLs Lasso resolves through its link service), `fix_tablepress` (SUPPORT_SITES), `fix_lasso_shortcode_label` (EasyAzon label, SUPPORT_SITES) | 91-98, 525-540, 1663-1690; class-fix.php 328-711 |
| R54 | `class-fix.php`: `fix_origin_url_has_been_changed_to_destination_url_issue` (besserklettern.com), `fix_multi_asins_issue` (Amazon cache rows), keyword and `amazon_url` JSON repairs, `fix_shortcode_lossing_shortcode_attributes_value` (registration commented out), TablePress repair (SUPPORT_SITES) | class-fix.php; class-verbiage.php 35-127 |

### F. One-off data scripts (`class-lasso-db-script.php`)

| # | Rule | Source |
|---|---|---|
| R55 | Table creation for every Lasso table (20 `create_*`) and default data (issue definitions, tracking default, default fields) | 56-224, 660-725 |
| R56 | Version upgrades of Lasso's own tables and meta: Amazon country columns and keys, postmeta country, URL issues (198), timestamps (228), category order key (229), url details (231, 288, 291), indexes (240), trimmed primary and second URLs (263), history dedupe (277), link locations (278, 282, 284), chewy image https (288), table fields (field groups, title field, defaults), AAWP ratings | 224-660, 733-876 |
| R57 | Lasso records only: `add_prefix_to_lasso_amazon_permalink` (`amzn-` slugs), `fix_incorrect_post_type` (`-urls` → `lasso-urls`), `fix_incorrect_post_meta_key` (`_custom_redirect` → `lasso_custom_redirect` …), `fix_incorrect_domain_in_affiliate_programs`, demo-description clean-up (#5679), Hub shortlink display meta (#5421), `delete_duplicate_lasso_posts`; lookups `get_post_by_post_name`, `get_post_by_old_slug`, `get_post_by_title` | 878-1239; class-update-db.php 408-495 |
| R58 | **None of these scripts writes `post_content` of posts or pages** | every `UPDATE` / `DELETE` in the file |

### G. Related tables

| # | Rule | Source |
|---|---|---|
| R59 | `lasso_link_updates` records a retired product key → its replacement (product IDs, types, countries, source and target link), read when resolving an old ASIN (R8); it is not a content log | models/class-link-updates.php 27-354; class-lasso-repoint-merge.php 205 |

## 4. Decisions

Settled by Claude from Lasso and the records:

- **D1 What Affibloom does for each rewriting path** (none of them writes content; "loss" is what a reader or editor
  would see differently; every loss is listed under "Needs the user", owned by the feature named):

  | Path | Affibloom instead | Lost | Owner |
  |---|---|---|---|
  | R6 scan stamps | the index reads Lasso's stamps and keeps its own identity (occurrence); the stamps stay | none for readers | F21 D3-D4, N3 |
  | R6/R7 rel, target, href, tag | the `the_content` filter prints the product's current link, target and rel at render time | the stored HTML (and the editor) keeps the old values; outputs outside the filters (F21 N1) | F21 D13-D15, N1; F1 D13 |
  | R8 retired ASIN | render time maps the old ASIN through the retired-ASIN record | as above | F21 D8, D13 |
  | R9 repoint | the same: no content write | none | F1 D14 |
  | R10 force delete | "Hold Up" blocks the delete | deleting a product in use | F1 D34 (the user's) |
  | R11-R13 monetize / unmonetize | an assignment (location → product) stored by the opportunity, applied at render time; unmonetize deletes it | the editor still shows the old URL | **F22** (N4 here) |
  | R14 keywords | left out | keyword linking | deviation 52 (the user's) |
  | R15 Amazon auto-monetize | render time: Amazon short links of a product print its tagged link (F21 D14) | the editor shows the short link | F35 / F21 |
  | R16 affiliate auto-monetize | products are created; the render-time filter ties the links | as R6/R7 | F35 |
  | R17 import / import revert | other plugins' shortcodes render through compatibility shortcodes; old URLs redirect | the stored shortcodes stay the other plugin's | F33, F31, F24 |
  | R18 Uninstall Lasso | not built; stamps stay (N3); the optional clean-up is H3/H4 if approved | removing stamps, cloaking and displays from HTML | F9 N2, N3 here |
  | R19 Start Link Indexing | the Link Index rebuild (reads only) | none | F21 |
  | R20 prefix change | old prefixed addresses keep redirecting | none for readers | F9 N4, F24 |
  | R21 geni.us | the product's link is stored resolved or not by the Amazon setting; posts are not touched | the editor keeps geni.us hrefs | C1 / F24 |
  | R22 group removal | no scan | none | F23 D6 |
  | R23 MCP insert | not built (cloud-signed) | an agent inserting displays | F4 N5 |
  | R24 Elementor | not built | Elementor-stored links keep old attributes where no filter reaches | F4 N6 |
  | R25 Yoast FAQ on save | not built: Affibloom adds no stamps, and this site's FAQ holds none | none here | N5 here |
  | Legacy block conversion | **the one content change**: operator-run, stored copy, revision, revert; History lists it | — | F10 28A-28E, this feature |

- **D2 One door for content writes.** Every Affibloom write of `post_content` goes through one class,
  `ContentChangeWriter` (Infrastructure): `wp_update_post` (a revision, `save_post`, `post_modified`, kses as for the
  user), only when the row still equals the content read (compare inside the write, as R1's `BINARY` rule, checked
  again after `wp_update_post` reads it), and it records the change (D4). Its callers are the converter (28B), the
  revert (H2) and the clean-up (H3). AC8's check proves there is no other.
- **D3 Lasso's 18 history rows are not copied or shown** (F10 D14 kept): they are Lasso's record of its own rewrites,
  every post was edited since, and a revert to them would drop months of edits (R42). The table stays untouched until
  9.6. The user decides the reverse (N2).
- **D4 The record of a change** is post meta on the changed post, one per kind: `_affibloom_pre_conversion` (F10's
  stored copy, unchanged) with **`_affibloom_conversion`** `{ at (UTC), by (user ID), after_md5, boxes }`, and for the
  clean-up `_affibloom_pre_cleanup` with `_affibloom_cleanup` `{ at, by, after_md5, removed }`. `after_md5` is what
  28B's own "edited since" refusal (F10 D16) needs, so it is asked of 28B (section 5). One record per post per kind,
  the first copy kept (F10 edge case 11), as Lasso keeps the first (R28).
- **D5 History is always there**, not behind Lasso's cloud flag (R31: **C — never**, `lasso.link/api/launch-darkly`):
  submenu **History** after Settings (Lasso's position 11), `admin.php?page=affibloom-history`; detail
  `admin.php?page=affibloom-history&post=<id>&kind=conversion|cleanup` (the post's ID, not a record ID: R37's bug is
  not copied). Capability `Capabilities::SETTINGS` for the screens; revert also needs `edit_post` on the post (R40).
- **D6 The list is Lasso's** (R33-R36): the same columns, sorts (default Modified date, newest first), search by post
  title with spaces as wildcards, ten a page, page-past-the-end → page 1, the generic empty text, View and Revert.
  The edit and view icons are shown (Lasso hides them with a stray `d-none`; the detail shows them). A row also says
  what the change was ("Legacy blocks converted (N)", "Lasso attributes removed (N)") under Post Name, because
  Affibloom has two kinds and Lasso one. It loads in place through REST as the Groups list (F23 D13).
- **D7 Rows of posts that no longer exist** are not listed (Lasso's `INNER JOIN`); a trashed post is listed with
  "(Trash)" after its title and cannot be reverted until restored.
- **D8 The diff without a library** (ADR-7: plain scripts): the server renders it with WordPress's `wp_text_diff()`
  (Text_Diff, line by line, both columns, the changed words marked by core's inline renderer), under "Content
  changed". Lasso's character-level diff-match-patch is not loaded.
- **D9 Revert asks first.** Lasso reverts on one click (R40); Affibloom's revert replaces the post's content, so it
  asks "Revert this post?" / "The post's content goes back to what it was before this change. A revision of the
  current content is kept." / Cancel, **Revert** (red), the shared delete-dialog markup. Then Lasso's "Saving your
  changes." bar, and "Content reverted for “<post name>”." (U6's plain wording for Lasso's "Successfully reverted
  content for …").
- **D10 Revert refuses what Lasso would silently overwrite** (R41-R42): when the post's content no longer has the
  change's `after_md5` it answers 409 `affibloom_edited_since` (AC4's text); a later change of another kind on the
  same post must be reverted first (409 `affibloom_later_change`). WP-CLI's `--force` (28B) is the only override.
- **D11 After a revert** the change's two meta are deleted, the row leaves the list (Lasso leaves it, R43), and the
  index of that post is rebuilt (F21's on-save indexing runs from `save_post`).
- **D12 Kept until reverted or until 9.6** (N1 for Lasso's 14-day purge): the stored copies are the rollback path
  (F10 D17).
- **D13 The Settings "Audit" toggle is not built**: recording is not optional, because the record is the revert's copy
  (N1).
- **D14 The clean-up (only with N3)** removes exactly `data-lasso-id`, `data-lasso-name` and `data-lasso-lid` from
  anchors (Lasso's R18 removes the first two and leaves `-lid`), with `WP_HTML_Tag_Processor` so no other byte
  changes (R5's side effects are not copied); posts, pages, reusable blocks, any status but trash; revisions are not
  touched; it refuses before the Lasso migration has finished (Task 26: the ID map and the dismissed rows need the
  stamps, F21 D4); it never removes displays or cloaking (R18's two options: F9 N2).
- **D15 Repair tools are not built** (N5): each repairs damage that only Lasso's writes or Lasso's cloud make; this
  copy has none of that damage. The legacy-block parser (Task 27, 28A) reads Lasso blocks tolerant of R52's malformed
  forms (invalid JSON, attributes only in the shortcode, double encoding, a `core/html` wrapper), **in memory only**;
  this copy has none of them, so Task 27/28A test them with fixtures.

**Needs the user** (each drops or replaces part of Lasso, or edits post content):

- **N1 Lasso's history settings and purge are dropped:** the "Audit → Post Content History" toggle (R32, default on)
  and the 14-day purge (R30). **Recommendation:** drop both; Affibloom records only its own operator-run changes, and
  the copy must outlive 14 days to be a rollback (F10 D17).
  → **Decided 2026-11-14:** D10: the user accepted the recommendation
- **N2 Lasso's 18 history rows are not shown or revertible** (R33-R43 for Lasso's rows). **Recommendation:** accept
  (D3): every post was edited after Lasso's change, so a revert would lose those edits; the rows stay in Lasso's table
  for rollback until 9.6.
  → **Decided 2026-11-14:** D10: the user accepted the recommendation
- **N3 Removing Lasso's stamps from content at retirement** (roadmap 9.6, Lasso's R18 in part). It edits 180 posts
  (2,791 + 698 + 698 attributes) and conflicts with F21 N3 (keep the stamps). **Recommendation:** keep the stamps (F21
  N3) and do **not** build H3-H4 now; decide at Phase 9. If the user wants the clean-up, H3-H4 are drafted: operator
  run, after migration, stored copy, revision, revert, History.
  → **Decided 2026-11-14:** A2: keep the stamps; no clean-up (H3-H4 dropped).
- **N4 Monetize / unmonetize do not rewrite the post** (R11-R13): F22 must give the same reader-visible result by an
  assignment applied at render time; the editor and the stored HTML keep the old URL. **Recommendation:** accept;
  F22 owns it (section 5).
  → **Decided 2026-11-14:** A3: monetize at render time plus "Edit in post" (F22 O10).
- **N5 Not built because nothing rewrites:** Fix Overwritten Links (R47-R49: damage from Lasso's Hub sync, **C —
  never**; 0 candidates here), the Yoast FAQ fix on every save (R25: repairs Lasso's stamps; the FAQ here has none),
  the scan-time content, anchor and block fixes (R50-R54: they run only inside Lasso's write-back; the SUPPORT_SITES
  ones only on other customers' domains, F37 N4), and the data scripts (R55-R58: Lasso's own tables and records;
  migration reads the upgraded data as it is, F10). **Recommendation:** accept all; D15 keeps the parser tolerance.
  → **Decided 2026-11-14:** A9: the user accepted the recommendation
- **N6 Reverting from the screen refuses edited posts** (D10), where Lasso overwrites them (R41). **Recommendation:**
  accept; `--force` stays in WP-CLI.
  → **Decided 2026-11-14:** D9: the user accepted the recommendation

## 5. Scope that needs a home

- **F10 Task 28B** writes `_affibloom_conversion` `{ at, by, after_md5, boxes }` beside `_affibloom_pre_conversion` and
  writes through `ContentChangeWriter` (D2, D4); 28E's Revert uses the same service as H2. The converter's tolerance of
  R52's forms (D15) belongs to 28A and Task 27's parser.
- **F22 Opportunities:** "Monetize" / "Unmonetize" of a link (R11-R13) as a stored assignment that F21's render filter
  applies (N4); Lasso's `monetize_amazon_link` resolution of `amzn.` links (R12) without Lasso's cloud.
- **F21:** the render filter (D13-D15) is what replaces R6-R8, R11-R16; it must read F22's assignments; the index is
  rebuilt for a post after a revert (D11).
- **F9 / F37:** "Uninstall Lasso" stays not built (F9 N2); if N3 is approved the clean-up lives on History (H4), not on
  Settings.
- **F33 Import:** import and revert screens (R44-R45) and their content effect (R17), done without rewriting.
- **Phase 9 (9.6):** dropping Lasso's tables (including `lasso_post_content_history`) after 90 days; the decision on
  N3 is taken there; deleting Affibloom's stored copies (D12) is a step of 9.6.

## 6. Edge cases

1. A post converted, then edited in the editor: revert answers 409 (D10); the list still shows it; WP-CLI `--force`
   reverts it and the edits are lost (the revision of the edited content remains).
2. Two administrators revert the same post: the second finds no record (404 `affibloom_no_change`, "This post has no
   change to revert.").
3. A post deleted for good after its change: its meta goes with it; nothing is listed (D7).
4. The stored copy is larger than the current content (blocks removed): byte-for-byte restore through
   `wp_update_post` with `wp_slash`, kses as for the current user; a user without `unfiltered_html` would lose markup,
   so the route requires `unfiltered_html` (single site: administrators) or refuses with 403 (AC3's "byte for byte").
5. Revisions disabled (`WP_POST_REVISIONS` false): the stored copy still makes the revert possible; the dialog's
   "A revision of the current content is kept." is left out in that case.
6. A page cache (LiteSpeed on production): `wp_update_post` fires `save_post`, which the cache plugin purges on; no
   extra purge is coded.
7. The clean-up (N3) on a post whose stamped anchor sits inside block attributes (JSON, `"`-encoded): only HTML
   attributes are removed; JSON-encoded stamps are counted and reported, not touched.
8. A thousand-change list: ten a page, sorted in SQL over the meta (`meta_key` index).
