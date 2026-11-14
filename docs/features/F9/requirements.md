# F9 Settings → General: requirements

Status: rewritten 2026-11-14 from a sweep of Lasso 369's General tab (`admin/views/settings/general.php`, its save path
and every reader of every key). Gates 1-2 of 2026-11-12 (Task 6) stand for AC1-AC3; the rest is new. Gate 4 for Tasks
9B-9M: waiting. Tools and diagnostics that Lasso reaches from Settings (Logs, DB Status, Permissions, Uninstall,
activation, upgrades) are F37.

## 1. User stories and acceptance criteria

- As the site owner, I set the defaults that new products take, and the site-wide switches Lasso keeps on its
  General tab, on one screen with Lasso's sections, labels and help texts; a product can still change its own.

- **AC1** New products take the site defaults (New Window / Tab, NoFollow / NoIndex, Sponsored, Show Disclosure;
  and, once their features exist, Link Cloaking and Detect Opportunities). (Task 9, 11E; R30-R33)
- **AC2** Box links carry `rel` and `target` by the Lasso rules. (Task 18; R33)
- **AC3** Settings save and show again. (Task 9, extended by each 9x task for its own fields; R40-R46)
- **AC4** The General tab has Lasso's structure: two columns of cards (left: Google Analytics, Cloaked Link Prefix,
  Smart CSS Loading, Permissions, Uninstall; right: Link Index, Custom Link Detection, Performance, Link Defaults,
  Notifications), switches left of their labels with Lasso's help texts, Save Changes at the bottom right, disabled
  until something changes. (9B; R1-R3, D2)
- **AC5** Link Defaults shows eight switches in Lasso's order with Lasso's defaults on a fresh install (all on), and
  the site's values after the import (Sponsored off, the rest on). (9B; R30-R39, D4)
- **AC6** Turning one of New Window / Tab, NoFollow / NoIndex, Sponsored, Show Disclosure on or off asks "Update Link
  Settings" — "Lasso will <enable|disable> the option <label>" (without the product name); **Only New Links** closes
  the dialog and changes nothing yet; **Override All Links** sets that value on every existing product at once.
  (9D; R47-R50)
- **AC7** With "Allow duplicate destinations" off, adding a link whose final destination (without its query string)
  matches an existing product opens that product with "This product already exists. Please update the Primary URL or
  add a new link."; with it on, only the pasted link is compared. (9C; R36, F18 D2)
- **AC8** Changing a field and then following a link, or closing the tab, asks "Unsaved Changes" ("You have unsaved
  changes that will be lost if you leave this page. What would you like to do?"); "Leave Without Saving" goes,
  "Save Changes" saves and then goes, × stays; with nothing changed, nothing is asked. (9E; R44-R46)
- **AC9** Google Analytics: the Tracking ID field ("Tracking ID or Property ID"); Click Tracking, Pageview and IP
  Anonymization are disabled while the field is empty and are switched off when it is emptied; switching Pageview on
  asks "Enable Pageview"; clicking the disabled Click Tracking warns "Please Enter GA Tracking ID Before Enable Click
  Tracking". (9F; R10-R15)
- **AC10** Cloaked Link Prefix: what is typed becomes lower case with `-` for anything not a letter, digit or `_`… as
  Lasso's keyup rule, and is stored through `sanitize_title`; an empty field means no prefix. Smart CSS Loading is one
  switch "Enable Smart CSS Loading". (9G; R16-R19)
- **AC11** Performance: Performance Mode (Power, Balanced, Light, Custom); choosing a preset sets the CPU threshold
  (80/65/50) and, in the support view, the cron interval and process limit; the `%` CPU field shows only for Custom.
  (9H; R22-R24)
- **AC12** Custom Link Detection lists the site's post types except the always-scanned and excluded ones; "Custom
  Fields (ACF)" shows only when ACF is active; an empty choice falls back to the defaults. (9I; R25-R27)
- **AC13** Link Index shows "Last Updated" and "Links Indexed" (or "Build Progress", "ETA" and "We're indexing your
  links in the background, feel free to do something else." while a build runs), refreshed every 5 seconds; with
  nothing indexed the card body is empty. (9J; R20-R21)
- **AC14** Notifications: "Disable Configure Amazon Notification", "Disable Help Tooltips", "Disable Import
  Notifications"; Disable Help Tooltips hides the "?" tips on Affibloom's screens. (9K; R51-R54)
- **AC15** `&support=1` adds the support sections: Keep Original URL (one domain a line), Cron (interval hours 0-3),
  Cronjob Manually (1-3), Optional Settings (Restrict Prefix, Processes Execute Limit, Font Awesome JS Render SVG,
  Disable AAWP Description) and "Rebuild Database" with "Ready to rebuild?". (9L; R55-R62)
- **AC16** The Uninstall card holds the opt-in "Delete all Affibloom data when the plugin is deleted" and the list of
  posts that still use Affibloom; nothing rewrites a post. (TL5 in F37; R63-R67, Needs the user N2)

## 2. Evidence

Read in full: `admin/views/settings/general.php` (802), `admin/views/settings/header.php`,
`admin/views/modals/{unsaved-changes,remove-attributes-confirm,override-display,url-save,license-activation}.php`,
`admin/views/notifications/{default-template,save-fail,save-success,ga-warning}.php`,
`pages/settings-general/class-ajax.php` (1-605, 1007-1310; 607-1006 is Creators API, F17/C1),
`classes/class-setting.php` (1409), `classes/class-setting-enum.php`, `admin/lasso-constant.php` 107-386, 783-824,
`admin/assets/js/setting-general.js`, `settings.js` 80-260, 2138-2254, 2378-2465, 2575-2705, 3286-3794,
`lasso-helper.js` 630-805. Followed out: `class-hook.php` 535-570 (menu gate), 636-682 (Smart CSS, Font Awesome),
1633-1740 (GA output), `class-helper.php` 59-122 (post types), 743-760, 1025-1070 (Link Index stats), 1160-1240, 1320-1340
(prefix), 4764-4783 (ACF), 5227-5348 (Smart CSS test), `class-post-type.php` 88-130, 540-556, `class-lasso-cron.php`
123-260, 455-587 (cron interval), 6515-6560, `class-lasso-process.php` 425-460, 617-735, `class-lasso-affiliate-link.php`
150-170, 285-320, 425-470, 755-800, 1725-1745, 2430-2455, `models/class-url-details.php` 169-177,
`class-lasso-process-link-database.php` 1-120, 330-440, 522-587, `class-product-analytics.php`, `footer.php`,
`header-new.php` 1-120, `opportunities/header.php` 82-95, `view-js.php` 2905-2915, `pages/fix/class-hook.php` 60-80.
Affibloom: `src/Domain/Settings/Settings.php`, `SettingsService.php`, `SettingsScreen.php`, `SettingsController.php`,
`templates/admin/settings.php`, `settings-amazon.php`, `partials/{settings-band,unsaved-modal}.php`,
`tests/E2E/acceptance/F9.spec.ts`, Task 9 (phase 2), deviations 20, 22, 32, 33, 36, F18 section 5 and D2,
`docs/decisions/task-6-decisions.md`, audit Finding 2, baselines `settings-general-1/2/3.jpg` (production, Lasso 370).

**This copy, 2026-11-14** (`lasso_settings`, = `tests/fixtures/inventory/settings.json`): GA ID `371244810`, Click
Tracking, Pageview, IP Anonymization on; Page and Link-Level Tracking on; prefix empty; Smart CSS off; Keep Original
URL empty; Permissions Administrator; cron interval 0; process limit 2; Segment on; Restrict Prefix, Processes Execute
Limit, Font Awesome SVG, Disable AAWP off; post types `post, page`; custom fields none (ACF not installed); Performance
Mode Balanced but **CPU 80** (the Power value; Lasso never rewrites the field on load); New Window, NoFollow on,
**Sponsored off**, Show Disclosure on, duplicates allowed (on), cloaking on, opportunities on, auto-detect on; the
three notification switches off; `enable_history` on; also stored by accident: `count_all_pages_posts` "105" (the
hidden field) and `undefined` "". Products: 256 with `open_new_tab=1`, `enable_nofollow=1`, `show_disclosure=1`;
`enable_sponsored` 1 on 5, 0 on 211; `link_cloaking` 1 on 238, 0 on 18; `is_opportunity` 1 on 254, 0 on 2. Link Index:
3,015 rows in `lasso_link_locations`, last 2026-11-09 (production screenshot: 3,015, "November 12, 2026 12:06 am UTC").
5 administrators, no other roles. Lasso is listed in `active_plugins` on this copy and `lasso_license_status` is 0.

## 3. What Lasso does

Storage: every key below lives in the one option `lasso_settings` (`LASSO_SETTINGS`), defaults in `SETTINGS_DEFAULT`,
read through `Setting::lasso_get_settings()` (cached per request, types forced 1064-1129).

| # | Rule | Source |
|---|---|---|
| R1 | **Page:** `edit.php?post_type=lasso-urls&page=settings-general`, menu title "Settings"; H1 "General" with "Learn" (help site); tabs right: **General, Display, Amazon** (Logs and DB Status are hidden pages, Import is its own menu item) | header.php; class-hook.php 461-495; class-post-type.php 341 |
| R2 | **Layout:** one form, two columns of white cards; left: License Key, Google Analytics, Advanced Click Tracking, Cloaked Link Prefix, Smart CSS Loading, (Keep Original URL), Permissions, Uninstall Lasso, (Cron, Lasso Cronjob Manually, Optional Settings); right: Link Index, Custom Link Detection, Performance, Link Defaults, Notifications, (Audit); "Save Changes" under the cards on the right. Parenthesised sections are conditional (R55, R67, R68) | general.php 143-571 |
| R3 | Help texts are tooltips on an info icon (`data-tooltip`) | general.php passim |
| R4 | **License Key:** "Your license key is active/not active. Access your Lasso account to manage installs.", key field, **Activate** → `lasso_reactivate_license` → "Activating Lasso" / "Just a moment while we validate your license key." → "Your license key is active." or the server's error; calls `lasso.link/license/status`, `/server/getinfo`; the key is removed from every settings save | general.php 156-170; class-ajax 51-84; settings.js 166, 2670-2704; class-lasso-license.php |
| R5 | **Every General save also calls `check_license()`** (Lasso's cloud) | class-ajax 517-519 |
| R6 | **Advanced Click Tracking** (hidden on the Startup plan): "Send click tracking data to Analytics for easy-to-understand analytics.", switch "Page and Link-Level Tracking" (`performance_event_tracking`, default on), tip "When enabled, Lasso can help you understand what pages and links drive conversions on your site."; it loads Lasso's cloud snippet (`js.codedrink.com`) and is forced on for lapsed licences; saving it on clears `stop_lasso_analytics` | general.php 209-225; class-hook.php 1692-1740; class-setting.php 1003-1016; class-ajax 453-455 |
| R7 | The settings payload of every save is sent to Segment/PostHog (`lasso_segment_tracking`, secrets redacted) | settings.js 2416-2419, 3783-3794 |
| R8 | `lasso_after_saved_settings` fires after every save with the posted options | class-ajax 592 |
| R9 | Saving answers `{ options, status, amazon_valid, error_msg, creators_validation_* }` | class-ajax 594-604 |
| R10 | **Google Analytics:** "Send click tracking data to Google Analytics.", tip "Enter a Tracking ID (UA-) or a Product ID (G-). Or, enter them both and separate them with a comma. You can find them in Google Analytics > Admin > Property Settings."; field `analytics_google_tracking_id`, placeholder "Tracking ID or Property ID", default "" | general.php 172-182 |
| R11 | "Click Tracking" (`analytics_enable_click_tracking`, default off), tip "Enable to begin tracking Lasso Link clicks in Google Analytics."; "Pageview" (`analytics_enable_send_pageview`, off), tip "If you have Google Analytics installed outside of Lasso, keep this disabled."; "IP Anonymization" (`analytics_enable_ip_anonymization`, off), tip "When enabled, we won't report visitors' IP addresses for GDPR compliance." | general.php 183-205 |
| R12 | Click Tracking is disabled while the ID is empty (on load); Pageview and IP Anonymization are disabled while Click Tracking is off; typing an ID enables Click Tracking; emptying the ID unticks and disables all three; unticking Click Tracking unticks and disables the other two | general.php 36-45; settings.js 3695-3730 |
| R13 | Clicking the disabled Click Tracking marks the ID field and shows the orange "Please Enter GA Tracking ID Before Enable Click Tracking" (once) | settings.js 3676-3693; ga-warning.php |
| R14 | Ticking Pageview opens "Enable Pageview" — "If you want Lasso to report pageviews to Google Analytics, click Enable. If you have Google Analytics installed outside of Lasso, keep this disabled." — Enable keeps it; cancel unticks | setting-general.js |
| R15 | **Readers:** with Click Tracking on and an ID, every front-end page (not admin, not Elementor preview, filter `lasso_event_tracking`) prints gtag.js for the first ID and `gtag('config', id, { send_page_view, anonymize_ip })` for each comma-separated ID; the Lasso snippet that sends click events loads then too; the header and Intercom report "ga_connected" | class-hook.php 1633-1740; class-setting.php 1392-1408; header-new.php 90 |
| R16 | **Cloaked Link Prefix:** "Add subdirectory to cloaked links. Most leave this empty.", tip "For no prefix leave this field empty.", "Example: https://domain.com/recommends/link-name/"; field `rewrite_slug`, default "" | general.php 227-237 |
| R17 | On keyup the value becomes `trim().toLowerCase().replace(/[\W_]+/g, "-")`; the server stores `sanitize_title( value )` | general.php 785-789; class-ajax 414-416 |
| R18 | **Decision A7, 2026-11-14: no rewrite; old prefixes keep answering.** **On change** of the prefix the server starts `Lasso_Process_Build_Rewrite_Slug_Links_In_Posts`: every published and draft post is rescanned and its cloaked links rewritten to the new prefix (post content written). No `flush_rewrite_rules()` on save. **Readers:** the post type's rewrite slug, permalinks of links (`remove_custom_slug`), URL lookups and the redirect | class-ajax 418-422; process-link-database 522-587; class-lasso-cron 1482-1573; class-post-type 94-128, 540-556; class-helper 1331; class-lasso-affiliate-link 2602-2645; class-lasso-db 3980 |
| R19 | **Decision C5, 2026-11-14: on by default for a fresh install; the migration switches it on.** **Smart CSS Loading:** "Skip display CSS on pages without Lasso displays or tables to improve page speed. Purge page cache after enabling.", switch "Enable Smart CSS Loading" (`smart_css_loading`, off). **Reader:** on, the front end skips display CSS and CSS variables unless the page is not singular (always loads), or the post has a display in the link index, display markers in content (`[lasso`, `affiliate-plugin/lasso`, `lasso-display`, `lasso-table`, `type="table"`) or Elementor data with one | general.php 239-256; class-hook.php 641-669; class-helper 5227-5348 |
| R20 | **Link Index:** "Lasso tracks every link on your site."; stats cards **Last Updated** (newest `detection_date` or `lasso_checked`) and **Links Indexed** (rows in the link index) — or, while the build runs, **Build Progress** "<n> completed of <total> total", **ETA** and "We're indexing your links in the background, feel free to do something else."; nothing when 0 links are indexed | general.php 396-414; class-ajax 89-205; class-helper 1025-1070 |
| R21 | The cards refresh every 5 s (`lasso_get_stats`, nonce `lasso_settings_save`, permission level); "Start Link Indexing" shows only while all processes are disabled (after an uninstall run, R64) → `lasso_rescan_lasso_attributes` re-enables processes and starts the index ("Working... Please be patient." / "The processes are running.") | lasso-helper.js 775-804; general.php 405-409, 761-783; class-ajax 1014-1037 |
| R22 | **Performance:** "Set the maximum CPU level that Lasso will run at.", tip "Lasso will wait until your CPU drops below this level to continue with its updates."; "Performance Mode:" select Power, Balanced, Light, Custom (`general_performance_mode`, default balanced); CPU field `cpu_threshold` with "%" (default 65) shown only for Custom | general.php 105-130, 432-448 |
| R23 | Changing the mode applies presets: Power 80/0/2, Balanced 65/0/2, Light 50/1/1 (CPU, `cron_time_interval`, `manually_background_process_limit`; the last two only when their support fields are on the page); page load only shows/hides, never rewrites | general.php 669-707 |
| R24 | **Readers:** `cpu_threshold` → background jobs pause between items when ≤ 50 or when the load reaches it; `general_performance_mode` has no reader (the v338 upgrade wrote a separate `lasso_general_performance_mode` option) | class-lasso-process 433-458; class-update-db 389-391 |
| R25 | **Custom Link Detection:** "Scan for links and shortcodes in custom locations.", tip "Additional locations where Lasso will discovering links."; "Custom Post Types:" multi-select (placeholder "Select post types", typed values allowed) of all post types except `post`, `page`, `wp_block` (always scanned) and the excluded list (`lasso-urls`, `pretty-link`, `affiliate_url`, attachment, revision, nav_menu_item, custom_css, customize_changeset, oembed_cache, user_request, wp_block, acf-field-group, acf-field, wpadc, wpcf7_contact_form, elementor_snippet, elementor_font, elementor_icons) | general.php 81-93, 416-424; class-helper 59-105 |
| R26 | "Custom Fields (ACF):" multi-select ("Select fields") of every `acf-field` post (name → label), shown only when ACF is active | general.php 95-103, 424-429; class-helper 4764-4783 |
| R27 | Stored `cpt_support` (default `post, page, wp_block`), `custom_fields_support` (default none); empty → default. **Readers:** which post types the link index scans, saves trigger scans for, revisions are kept for (8), keyword and shortcode lookups; which ACF fields are scanned (and rewritten) | class-ajax 520-521; class-setting 1126-1129; class-helper 110-122; class-hook 322-330; class-lasso-cron 349, 5451, 5910, 6567-6595; class-lasso-db 4816, 5031, 5102, 5316 |
| R30 | **Link Defaults:** "Set the default attributes for new links."; switches in order: New Window / Tab (`open_new_tab`, default on, "When enabled, users who click this link will have it loaded in a new tab."), NoFollow / NoIndex (`enable_nofollow`, on, "When enabled, this link will be set to nofollow. This indicates to Google that it's an affiliate link."), Sponsored (`enable_sponsored`, **on**, "When enabled, this link will be set to sponsored."), Show Disclosure (`show_disclosure`, on, "When enabled, this link will show the disclosure."), Allow duplicate destinations (`check_duplicate_link`, on, "Allows you to have links from different networks to the same product."), Link Cloaking (`link_cloaking`, on, "Toggle on to brand your link with your domain. Toggle off to show readers your Primary Destination URL."), Detect Opportunities (`detect_opportunities`, on, "When off, Link Opportunities won’t show for affected links. Doesn’t change how Lasso finds opportunities in the background."), Auto-Detect Affiliate Links (`auto_monetize_affiliates`, on, "Add all detected affiliate links to your dashboard as individual products.") | general.php 450-517; lasso-constant 152, 163-168, 172, 382-383 |
| R31 | `open_new_tab2` and `enable_nofollow2` (both on) are defaults for a new link's second button with no control anywhere | lasso-constant 165-166; class-lasso-affiliate-link 161-162 |
| R32 | **A new link stores** new tab, nofollow, sponsored, new tab 2, nofollow 2, cloaking and `is_opportunity` from these settings at creation; a later change reaches existing links only through R47 | class-lasso-affiliate-link 156-164, 1167-1173, 1421-1437 |
| R33 | `rel` = `nofollow` (if on) + `noopener` (if new tab) + `sponsored` (if on); second button adds `sponsored` only with nofollow 2; `target` `_blank`/`_self`; cloaking off (or Amazon) → the public link is the destination | class-lasso-affiliate-link 438-466 |
| R34 | `show_disclosure` is shared with the Display tab (saved by both) | class-ajax 549, 566; class-setting 955 |
| R35 | (= F18 section 5) `check_duplicate_link` **on**: the pasted URL is compared | class-lasso-affiliate-link 755-800 |
| R36 | `check_duplicate_link` **off**: the final URL without its query (except Amazon search/no-ASIN pages, extend-product pages without id, `DOMAIN_ALLOW_KEEP_FINAL_URL_PARAMS`) is compared, then `url_without_arguments` meta, then the pasted URL; on save of a non-Amazon link that meta is written; inactive (non-published) matches resolved too | class-lasso-affiliate-link 755-800, 1732-1742, 2440-2452 |
| R37 | **Readers of `link_cloaking`:** new links' default; the public link of a box/link; auto-detected links are added with cloaking off | class-lasso-affiliate-link 164, 313, 460-466, 1205-1298; process-auto-monetize 63 |
| R38 | **Readers of `detect_opportunities`:** a new link's `is_opportunity` (opportunity lists filter on it) | class-lasso-affiliate-link 156, 1033, 1173, 1721; class-lasso-db 419-1932 |
| R39 | **Readers of `auto_monetize_affiliates`:** the daily auto-monetize job and the content scan's affiliate-link detection; synced to Lasso's cloud | class-lasso-cron 6524-6530, 6544; pages/fix/class-hook 69-71 |
| R40 | **Save button:** disabled in the markup, enabled once scripts load; turns red while anything differs from the loaded values | general.php 568; settings.js 2233-2254, 3739 |
| R41 | **Save:** "Updating Your Site" dialog with a progress bar and "Just checking all the affected links and/or keywords on your site to make sure everything's updated."; `lasso_store_settings` (nonce `lasso_settings_save`, permission level), `tab` = the active tab's text; every input of the form is posted, a checkbox as `true`/`false` (disabled ones too); success → page reload (no toast); `success:false` → "Error!" after 1.5 s; HTTP failure → "Try again. For some reason your settings didn't save." | settings.js 2379-2464; url-save.php; save-fail.php |
| R42 | Server: empty settings → "No settings to save."; `'true'`/`'false'` → booleans, other strings trimmed, arrays kept; `lasso_permission` dropped unless `manage_options`; options merged over stored values | class-ajax 334-370, 458 |
| R43 | General-only rules: `cpt_support` absent → `post, page`; `keep_original_url` lines with `http(s)://` stripped, kept only if `https://<line>` is a URL containing a dot; `manually_background_process_limit` clamped 1-3 | class-ajax 517-541 |
| R44 | **Extended (decision C7, 2026-11-14): the guard is on every Settings tab (General, Display, Amazon), not General only.** **Unsaved-changes guard (General only among the Settings tabs):** the loaded values are the baseline; compared field by field (booleans 1/0, lists sorted) | settings.js 179-214, 3337-3349; general.php 795 |
| R45 | `beforeunload` prompt while changed; any `a[href]` click (not `#…`, `javascript:`, `target=_blank`, or inside the two dialogs) opens "Unsaved Changes" (static backdrop) | settings.js 3399-3439; unsaved-changes.php |
| R46 | × cancels; "Leave Without Saving" goes; "Save Changes" saves (R41) and then goes; a refused save forgets the pending navigation | settings.js 3358-3397, 2436-2448 |
| R47 | **Override dialog:** changing New Window / Tab, NoFollow / NoIndex, Sponsored, Show Disclosure or Link Cloaking opens "Update Link Settings" — "Lasso will **enable/disable** the option **<label>**" — **Only New Links** (closes) / **Override All Links** | general.php 630-647; override-display.php |
| R48 | Override All Links posts `lasso_override_display_option { option_name, option_value }` **at once, before and independent of Save**: sets that post meta to 1/0 on every link post (any status), inserting it where missing; no feedback (console only) | override-display.php 37-57; class-ajax 1042-1107 |
| R49 | Detect Opportunities opens the same dialog; its override sets `is_opportunity` on every `lasso_url_details` row | general.php 649-661; class-url-details 169-177 |
| R50 | The dialog is not offered for Allow duplicate destinations or Auto-Detect Affiliate Links | general.php 644-661 |
| R51 | **Notifications:** "Toggle which notifications you want enabled."; Disable Configure Amazon Notification (`general_disable_amazon_notifications`), Disable Help Tooltips (`general_disable_tooltip`), Disable Import Notifications (`general_disable_notification`); all off; no tips | general.php 519-547 |
| R52 | `general_disable_amazon_notifications` has **no reader** in Lasso 369 (the "auto Amazon" notice uses its own dismissal option) | grep; footer.php 95-116 |
| R53 | `general_disable_tooltip` hides every `[data-tooltip]` and info icon on Lasso's admin pages and the Opportunities education boxes | header-new.php 20-26; opportunities/header.php 87; view-js.php 2908 |
| R54 | `general_disable_notification` hides the "import suggestion" notice (other plugins to import from) | footer.php 76-81 |
| R55 | **Support view** (`&support` in the URL) adds Keep Original URL, Cron, Lasso Cronjob Manually, Optional Settings, the Uninstall section even after removal, and "Rebuild Database" | general.php 259-270, 280, 295-390, 411-413 |
| R56 | **Keep Original URL:** "We don't convert the affiliate URLs to destination URLs", "Example: domain.com, not https://domain.com/"; textarea, one domain a line (`keep_original_url`, default none). **Reader:** these domains join `DOMAIN_ALLOW_ORIGINAL_URL_IN_LASSO_DETAIL` (link details keep the affiliate URL instead of the final one) | general.php 258-270; settings.js 2398-2400; class-lasso-affiliate-link 1907-1909 |
| R57 | **Cron:** "Time interval (hours)", number 0-3 (`cron_time_interval`, default 0). **Reader:** 1-3 floors every Lasso cron that runs more often than that many hours; 0 = native schedules; saving it reschedules | general.php 296-305; class-lasso-cron 225-253, 460-587 |
| R58 | **Lasso Cronjob Manually:** "Maximum Lasso cronjob at a time.", tip "This feature runs only when the system's cronjob does not work."; number 1-3 (`manually_background_process_limit`, default 2). **Reader:** the browser-driven fallback that runs due crons and stalled jobs when WP-Cron fails | general.php 307-315; class-ajax(pages) 1564-1696; class-lasso-process 663-676 |
| R59 | **Optional Settings:** Segment Analytics (`segment_analytics`, on): Lasso's own Segment/PostHog admin analytics | general.php 317-331; class-product-analytics; class-hook 1198 |
| R60 | Restrict Prefix (`restrict_prefix`, off): the link post type is registered `public => false` | general.php 334-346; class-post-type 96-112 |
| R61 | Processes Execute Limit (`processes_execute_limit`, off): when on, a job waits in Lasso's queue while too many run | general.php 348-360; class-lasso-process 718-735 |
| R62 | Font Awesome JS Render SVG (`fontawesome_js_svg`, off): front end sets `FontAwesomeConfig.autoReplaceSvg`; Disable AAWP Description (`disable_aawp_description`, off): Amazon features are not used as an empty description | general.php 362-388; class-hook 671-680; class-lasso-amazon-api 2018 |
| R63 | **Uninstall Lasso** (shown while processes run, during removal, or in support view): "Caution: Click this button only if you want to remove Lasso from your site. This cannot be undone.", red **Uninstall Lasso** → "Uninstall Lasso?" dialog (R64) | general.php 279-293; remove-attributes-confirm.php |
| R64 | Dialog: "If you are canceling your subscription, you do not need to run this." … "Only run this if you want to:" — Remove all data-lasso-ids from the HTML (on, fixed), Remove all Lasso link cloaking., Remove all Lasso displays and shortcodes.; "⚠ This process cannot be undone. ⚠"; Keep My Displays / Uninstall Lasso → `lasso_remove_lasso_attributes` → disables all processes and **rewrites every post in the link index** (strips `data-lasso-id/name`, optionally puts the primary URL in `href`, optionally deletes Lasso blocks and `[lasso]` shortcodes), clears its index rows | general.php 597-624; class-ajax 210-245; process-remove-attribute; class-lasso-cron 4807-4900 |
| R65 | Progress: "Removal Progress", ETA, "We're removing Lasso attributes in the background…"; done: "Finished", "All Lasso displays, links, and attributes have been removed. You can now deactivate and delete the Lasso plugin." | class-ajax 156-193 |
| R66 | "Rebuild Database" (support) → "Ready to rebuild?" / "By rebuilding your link index, you'll start with a clean slate." / Cancel, Confirm → `lasso_trigger_cron` (resets and forces a full rescan) → "Working... Please be patient." | general.php 411-413, 576-592, 709-740; pages/class-ajax 1701-1716 |
| R67 | **Built (decision C3, 2026-11-14): TL6, default Administrator, with Lasso's Contributor/Subscriber swap fixed.** **Permissions:** "Select the minimum user role that can access Lasso."; select Administrator, Editor, Author, Contributor, Subscriber (`lasso_permission`, default Administrator) — behaviour in F37 | general.php 56-72, 272-277 |
| R68 | **Audit** (only when Lasso's cloud flag `audit_log` is on; absent on production): "Toggle this if you want to track changes.", switch "Post Content History" (`enable_history`, on) — what it records is F34 | general.php 549-559; class-post-content-history 233 |
| R69 | Hidden input `count_all_pages_posts` (published posts/pages containing `<a `) is saved as a setting by accident | general.php 154; class-helper 743-760 |

## 4. Decisions

Settled by Claude from Lasso and the records:

- **D1 Scope of F9:** every control of R1-R69 gets a verdict (analysis, "Verdicts"). The controls are built in F9;
  what a control switches is built by the feature that owns that behaviour, which reads the stored key.
- **D2 Layout:** Lasso's structure (R2), as the Amazon tab (deviation 32): two columns of cards, switches left of
  their labels with "?" help tips, Save Changes at the bottom right, disabled until a change (`data-save-when-changed`,
  the Amazon tab's script). Supersedes the Aurora single-column General (deviation 22 kept it "until its own pass").
- **D3 Keys:** Affibloom's names in `affibloom_settings` (existing style: `new_tab`, `nofollow`, `sponsored`,
  `show_disclosure`); new: `allow_duplicates` (← `check_duplicate_link`), `link_cloaking`, `detect_opportunities`,
  `auto_detect_links`, `ga_tracking_id`, `ga_click_tracking`, `ga_pageview`, `ga_anonymize_ip`, `link_prefix`,
  `smart_css`, `keep_original_domains`, `scan_post_types`, `scan_custom_fields`, `performance_mode`, `cpu_threshold`,
  `cron_interval`, `manual_process_limit`, `restrict_prefix`, `process_limit`, `fontawesome_svg`,
  `no_feature_description`, `disable_amazon_notice`, `disable_help_tips`, `disable_import_notice`. Task 26 maps
  Lasso's keys.
- **D4 Fresh-install defaults are Lasso's** (R30): Sponsored defaults **on** (Affibloom has off: corrected); the
  import brings the site's off.
- **D5 Save path:** the existing `admin-post.php` form (Task 9, 10F): one page load, the toast "Settings saved."
  (U6), refused fields marked. Lasso's dialog and reload give the same result; its "Error!" bars map to the
  partly-saved notice and WordPress's error page.
- **D6 Nothing is sent anywhere on save** (R5, R7: Lasso's cloud — C).
- **D7 Override All Links** is a REST call made at once from the dialog, as Lasso (R48), `POST
  /affibloom/v1/settings/link-defaults/apply { key, value }`, with a toast "All products updated." (Lasso shows
  nothing; plain feedback added) and products in any status. The keys it takes are the product fields that exist;
  Link Cloaking and Detect Opportunities join when F24 and F22 add those fields.
- **D8 The unsaved guard** is Lasso's (R44-R46) on General; "Save Changes" in it submits the form with a
  `redirect_to` (a validated admin URL) so the save is followed by the navigation.
- **D9 Prefix input:** Lasso's keyup rule and `sanitize_title`. What a change does to links is F24's (no post
  rewrite; see Needs the user N4).
- **D10 GA switches** keep Lasso's enable/disable rules, warning and Pageview dialog; what they print is C3's.
- **D11 Support view** stays behind `&support=1`, as Lasso, with the same sections (minus Segment, C).
- **D12 Dead and accidental keys are not copied:** `count_all_pages_posts`, `undefined`,
  `general_performance_mode`'s twin option; `general_disable_amazon_notifications` is built (a control) and its
  consumer is F38's Amazon notice (Lasso reads nothing; Affibloom makes it do what its label says — no loss).
- **D13 Performance Mode** keeps Lasso's rule that load never rewrites values (so this site keeps Balanced + 80).

**Needs the user** (recommendation first):

- **N1 Lasso cloud items are not built:** License Key, Advanced Click Tracking ("Page and Link-Level Tracking"),
  Segment Analytics, the settings sync to Lasso and the payload to Segment/PostHog (R4-R7, R59). Recommended: confirm
  (standing rule C).
  → **Decided 2026-11-14:** B1: left out.
- **N2 "Uninstall Lasso" rewrites posts** (R63-R65). Recommended: not built; the Uninstall card holds Task 38's opt-in
  data removal and lists the posts that still use Affibloom (from F21's index) so the owner can remove blocks by
  hand (F37 TL5). "Start Link Indexing" (R21) then never applies.
  → **Decided 2026-11-14:** A8: the user accepted the recommendation
- **N3 Permissions select vs decision 1** (administrators only): Lasso lets the owner open the whole plugin, and
  save settings, to Editors and below. Recommended: build it (F37 TL6); live value Administrator changes nothing.
  → **Decided 2026-11-14:** C3: build the Permissions select (TL6), default Administrator, fixing Lasso's swap.
- **N4 Changing the Cloaked Link Prefix** makes Lasso rewrite every post (R18). Recommended (F24): no rewrite; old
  prefixed URLs keep redirecting.
  → **Decided 2026-11-14:** A7: the user accepted the recommendation
- **N5 Smart CSS Loading vs the overview rule** "box CSS loads only on pages that show a box" (= Smart CSS on).
  Lasso's default and the live value are off (CSS on every page). Recommended: build the switch, import off (no
  visible change at cutover); F5 reads it.
  → **Decided 2026-11-14:** C5: on by default and the migration switches it on; "import it off" is not taken.
- **N6 GA (C3)**: Lasso prints gtag.js with the stored ID; the C3 outline says Affibloom stores no ID. The live ID
  `371244810` is not a GA4 measurement ID. Recommended: build the four controls (F9) and let the C3 sweep decide the
  output.
  → **Decided 2026-11-14:** C6: build the GA controls and the gtag.js loader in full; import Lasso's values; events renamed (see C3 N2).
- **N7 Unsaved guard on Display and Amazon:** Lasso has it on General only. Recommended: opt them in too (one attribute
  each; U7 was approved as "parity").
  → **Decided 2026-11-14:** C7: the unsaved-changes guard is on every Settings tab.

## 5. Scope that needs a home

| Behaviour | Owner |
|---|---|
| GA output and events (R15) | C3 |
| Prefix in URLs, redirect, permalinks, Restrict Prefix (R18, R60) | F24 |
| Link cloaking per product, its override key (R37, R47) | F24 |
| Opportunities flag, its override key (R38, R49) | F22 |
| Auto-detect job (R39) | F35 / C2 |
| Duplicate off mode (R36) | 9C here (owed by F18 D2) |
| Smart CSS test, Font Awesome flag (R19, R62) | F5 (box assets), F28 (icons) |
| Link index stats, rebuild, post types and fields to scan (R20-R21, R25-R27, R66) | F21 |
| CPU threshold, cron interval floor, manual cron fallback, process limit (R24, R57, R58, R61) | F11 |
| Keep Original URL (R56) | F18 / F1 (final-URL handling on link details) |
| Disable AAWP Description (R62) | C1 |
| Help tips, Amazon notice, import notice (R52-R54) | F38 |
| Post Content History (R68) | F34 |
| Permissions, Uninstall card (R63-R67) | F37 |

## 6. Edge cases

1. A field that was not posted keeps its value (Task 10F rule); a disabled GA switch posts nothing and stays off.
2. CPU threshold not a whole number 1-100 → refused, old value kept (Lasso stores anything and reads `abs(int)`).
3. Cron interval outside 0-3 or process limit outside 1-3 → clamped as Lasso (R43) for the limit; interval refused.
4. Prefix that slugifies to empty → no prefix. A prefix equal to an existing page slug: F24 warns.
5. Override All Links on 263 products: one query per key; a second click while one runs is ignored.
6. Two administrators saving: the last save wins, field by field for the posted fields.
7. Post types removed from WordPress after being chosen stay stored and are ignored by F21.
8. Unsaved guard with a refused save: the navigation is dropped and the refused fields are marked.
