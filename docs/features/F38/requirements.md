# F38 Admin shell: requirements

Status: gates 1-2 written 2026-11-14 from a sweep of Lasso 369's admin shell — navigation, menu, footer, notices,
education boxes, help, the onboarding wizard, the product tour and the command palette (new feature F38; analysis in
`analysis.md`; tasks in the phase files in `affibloom-docs/plans/` (task index in `00-overview.md`); ON10 dropped as a duplicate of F9 9E). Gate 4 for Tasks ON1-ON10: given by the user 2026-11-14.

The shell belongs to no single screen. Screens fill it: each screen's task adds its own nav tab and menu entry
(F23 G1 already plans Groups'), and each feature writes its own notices on the framework built here.

## 1. User stories and acceptance criteria

- As an editor, I move between Affibloom's screens the way I moved between Lasso's, I see what is running in the
  background, I am told what changed or failed, and I find any product from any screen with one shortcut.
- As the person who installs Affibloom on a new site, I am walked through the defaults once.

- **AC1** The Affibloom header shows, left to right, **Dashboard, Opportunities, Tables, Groups** (each once its screen
  exists), then **Settings**; the tab of the screen's group is marked current (`aria-current="page"`): Dashboard for the
  Dashboard and every product page; Opportunities for every opportunities page; Groups for the list and a group;
  Tables for the list and a table; Settings for every Settings tab. Below 992 px the tabs fold behind a **Menu**
  button. No Analytics or Marketplace tab. (ON1; R1-R4, R6, D1-D3)
- **AC2** The WordPress menu has **Affibloom** at Lasso's place (position 20) with **Dashboard, Opportunities, Tables,
  Groups, Fields, Import, Settings, History** in that order, each once its screen exists; detail pages (a product, a
  group, a table, a field, Settings' other tabs) are reachable but not listed, and on them the right item is
  highlighted. Browser titles: "Dashboard", "Link Details" on a product page, each other screen its own name. (ON1;
  R10-R14, D4-D6)
- **AC3** Every Affibloom page ends with the version, "Version <x.y.z>". (ON1; R16, D7)
- **AC4** Other plugins' admin notices are not shown on Affibloom's pages; they show again on WordPress's own pages.
  (ON2; R15, D9)
- **AC5** Notices appear in one region under the header, newest last; a script can add a success, error or warning
  notice; a failure without a message of its own says "An unexpected error has occurred please try again later.".
  (ON2; R17-R20, D8, D10)
- **AC6** When WordPress reports an update of Affibloom, every Affibloom page says "There's a new version available.
  **Update now**." and the link starts WordPress's own update of the plugin. (ON2; R24, D11)
- **AC7** The header's **Automations** button shows how many background jobs are running (spinning, with a count); a
  click opens "Automations": each running job as "<name> (<done>/<total>)", or "Silence is a source of great strength
  - Lao Tzu" when none runs; a click elsewhere closes it; the count refreshes every 15 s for 5 minutes. (ON3; R40-R44)
- **AC8** While the link index is being built, every Affibloom page says "We're rounding up a list of your links. **See
  the progress**." (to Settings → General); after an import, until its rescan ends, "We are creating an index of your
  links. This will be complete in <time>. Please wait until then to begin any Imports.". (ON4; R25, R26)
- **AC9** An education box on an opportunities screen closes with ×, stays closed after a reload, comes back with
  `&reseteducation=1`, and is not shown at all while **Disable Help Tooltips** is on. (ON5; R33-R36)
- **AC10** On a new site, the first visit to Affibloom after activation opens the setup wizard: a step bar (Theme,
  Customize, Amazon, Analytics, [Imports,] Done), "Choose a Default Display Box Theme" with the eight themes; picking
  one opens "Customize Your Displays" with the live box and Lasso's colour, text and switch fields; **Save and
  Continue** saves, **Skip** moves on, **← Change Themes** goes back, a done step in the bar can be clicked to return.
  (ON6; R45-R52, N1)
- **AC11** The wizard's Amazon, Analytics and Imports steps show Lasso's fields and save as AC10; "You're Ready" →
  **Go to Dashboard**; the wizard does not open by itself again. (ON7; R53-R58)
- **AC12** The first time anyone opens the Dashboard, a guided tour points at Add New Link, the Dashboard tab, the
  search box, Filters (the panel opens), the filters panel, Opportunities, Tables, Groups — skipping any that is not on
  the page — with Back, Next, Done and Skip; Done or Skip ends it for every user; Escape or a click on the backdrop do
  not end it. (ON8; R59-R64, D18)
- **AC13** **Ctrl+Shift+L** (⌘⇧L on a Mac) on any Affibloom page, in the post editor or on another post type's editor
  opens "Search links" (not while typing in a text field outside it); it lists ten products at once, filters as I type,
  arrows move through the results, Enter or a click opens the product page, the same shortcut or Escape closes it and
  focus goes back; with no match, "No links found."; the Dashboard's search box shows the shortcut for my system. (ON9;
  R65-R72)
- **AC14** Leaving a Settings tab with changes not saved asks "Unsaved Changes" (Leave Without Saving, Save Changes,
  ×), as the product page does. (ON10; R73-R75)

## 2. Evidence

All read: `admin/views/header-new.php` (1-251), `admin/views/footer.php` (1-339); all 23 files in
`admin/views/notifications/`; all 11 in `admin/views/education/`; all 8 in `admin/views/onboarding/`;
`admin/views/modals/{unsaved-changes,url-delete,url-delete-reject,license-activation}.php`,
`admin/views/components/checkbox.php`; `admin/assets/js/{onboarding,command-palette,lasso-product-tour,lasso-helper,
lasso-page,lasso}.js` (whole), `lasso-modal.js` 1-80; `classes/{class-product-tour,class-verbiage,class-activator}.php`
(whole); `pages/install/class-ajax.php` (whole); `affiliate-plugin.php` 50-116; `pages/class-hook.php` 49-640
(hooks, redirect, menu, organize menu), 837-870 (foreign notices), 955-1072 (scripts, palette data);
`classes/class-post-type.php` 70-140, 300-416 (menu, submenu order, titles); `pages/class-ajax.php` 66-88 (actions),
855-893, 1176-1240 (`lasso_search_attributes`), 1320-1345, 1600-1700, 1746-1881; `classes/background_processing/
class-lasso-process.php` 640-708; `classes/class-helper.php` 6170-6184, 6229-6370 (review card), 7036-7076 (palette
labels); `classes/class-enum.php` 15-50; `classes/class-setting.php` 681-687, 1152-1182, 1215-1256, 1351-1356;
`admin/assets/js/settings.js` 131-169, 2379-2470, 2576-2704, 2706-2967, 3290-3440, 3730-3794; `admin/assets/js/
view-js.php` 2550-2600, 2895-2935; `admin/views/opportunities/header.php` 70-100; `admin/views/settings/general.php`
520-547; `admin/lasso-constant.php` 89, 138-139, 783-795; grep of every `support.getlasso.co` link (29) and of
`admin_bar_menu` / `plugin_action_links` (none of Lasso's own).

Affibloom checked: `docs/ui/{screen-inventory,workflows,design-system}.md`, `docs/spec/interface.md` 1-40;
`src/Interface/Admin/{AdminMenu,AdminAssets,Notice}.php`; `templates/admin/partials/{header,toast,toast-template,help,
soon,unsaved-modal}.php`; `assets/admin/admin.js` (modal, menu, toast); roadmap screen map and U3, U6, U9, U10;
F23 D9; F9 R51-R54 and section 5; F37 R18, R40; F18 D5; F19 requirements (palette row).

**This copy, 2026-11-14** (read only, `--skip-plugins`): Lasso 369 active but `lasso_license_status` 0, so every Lasso
page redirects to its wizard here (R5). Tour finished: site option `lasso_dashboard_tour_done` = 1 and older user meta
of the same name for users 2 and 12. Review card: milestone 250 snoozed 2026-10-01 (263 published links, first
install 2023-05-23), never disabled; `lasso_review_allow_notification` = "0" (no reader in 369). Auto-Amazon notice
off (`lasso_enable_auto_amazon_notification` 0; `auto_monetize_amazon` true). `enable_scan_notice_after_import` 0.
No Lasso update offered. No import-source plugin active. `general_disable_tooltip`, `general_disable_notification`,
`general_disable_amazon_notifications` all false; no education box hidden. `intercom_user_jwt` set (Intercom would
load). `dismiss_out_of_stock` on 5 products (F11/C1); `lasso_dismiss_amazon_bulk_sync_banner_v2` for user 2 (C1).
`lasso_activate_first_time` 1. **On production's configuration no shell notice would show today** besides what C1,
F11 and F33 own.

## 3. What Lasso does

| # | Rule | Source |
|---|---|---|
| R1 | **Header** on every Lasso screen (32 views include it): logo (to the Dashboard), nav, then Automations icon, Help menu, Add New Link; then the notification region `#lasso_notifications`, the Automations popover `#lasso-sync-content`, and the Add a New Link modal | header-new.php 143-251 |
| R2 | **Nav, in order:** Dashboard (`page=dashboard`), Opportunities (`page=program-opportunities`), Tables, Groups, **Analytics** (`https://app.getlasso.co/analytics/`, new tab, external icon), **Marketplace** (`https://app.getlasso.co/plus/`, new tab). There is **no Settings tab** (Settings is in the WordPress menu) | header-new.php 154-173 |
| R3 | **Active tab:** Dashboard for `dashboard, url-details, url-links, url-variants`; Opportunities for `link-, keyword-, program-, content-opportunities, content-links, domain-opportunities, domain-links`; Groups for `groups, group-details, group-urls`; Tables for `tables, table-details`; a Settings flag is computed and never printed | header-new.php 29-76 |
| R4 | **Add New Link** split button: main opens the single-link modal; caret "More ways to add links" opens a menu: "Bulk add links" / "Import many URLs at once", "Marketplace" / "Discover products to add as links". Shown only with an active licence; otherwise an **Update Account** button to `https://app.getlasso.co/login` | header-new.php 199-233; lasso-constant.php 783 |
| R5 | **Not built (decision B1, 2026-11-14: no licence gate).** **Licence gate:** on any Lasso page, no `page` → Dashboard (licensed) or `install`; unlicensed → every page except install/uninstall redirects to `install`; licensed → install and uninstall redirect to the Dashboard. The licence is checked against Lasso's servers when not active | class-hook.php 286-314 |
| R6 | **Menu button** (below 992 px) toggles the nav (`aria-label` "Menu") | header-new.php 235-238 |
| R7 | **Not built (decision B2, 2026-11-14).** **Help menu** (`?`, "Help"): "Support docs" (`https://support.getlasso.co/en/`), "Lasso affiliate program" (`https://getlasso.co/affiliate-program/`), both new tab | header-new.php 182-196 |
| R8 | **Not built (decision B1, 2026-11-14).** **Telemetry on every Lasso screen:** Segment (`cdn.segment.com`, identify with name, admin email, version, flags, keyword count), PostHog, Sentry (`browser.sentry-cdn.com`, `ingest.sentry.io`; on a Lasso JS error or a PHP error it **shows Sentry's report dialog** to the user); Intercom widget (`widget.intercom.io`, app `az01idfr`) when `intercom_user_jwt` is set, with the user's email and site counts | header-new.php 98-134; footer.php 40-45, 167-339; lasso-constant.php 89, 790-795 |
| R9 | **Not built (decision B2, 2026-11-14).** **"Learn" links:** 29 links to `support.getlasso.co` (page headers Dashboard, Groups, Fields, Tables, Import, Opportunities, Settings Display/Amazon; the product page; the wizard's steps; education boxes; the display modal; quick detail) | grep admin/views |
| R10 | **WordPress menu:** the post type's menu "Lasso", `menu_position` 20, white SVG icon; pages registered with `add_submenu_page` under `edit.php?post_type=lasso-urls`, capability `lasso_permission_level` (users without it lose the whole menu) | class-post-type.php 91-130; class-hook.php 335-569 |
| R11 | **Submenu order (licensed):** Dashboard (2), Opportunities (4, renamed from Program Opportunities), Tables (5), Groups (6), Fields (7), Import (8), Settings (10), History (11, only when the audit-log flag is on), then **Support** (90, `support.getlasso.co`), **Account** (100, `app.getlasso.co/account/`), **Analytics** (105), **Marketplace** (110), external ones forced to a new tab; every other registered page (Configure, Add New URL, Links, Opportunities (link), Variants, Group Details, Group URLs, Field Details, Field URLs, the opportunity pages, Settings - Display/Amazon/Import/Logs/DB Status, Fix Overwritten Links, History Detail, Table Details) is dropped from the list but stays reachable. **Unlicensed:** Configure (20), Uninstall (25) and a Settings slot (30; no settings page is registered then) | class-post-type.php 309-399; class-hook.php 340-527 |
| R12 | **Highlight:** on a Lasso page with no current submenu item the second item is marked current; on Settings pages the "Settings" item is | class-hook.php 610-634 |
| R13 | **Titles:** the submenu title is the page title; `admin_title` puts "Dashboard" before the title on the Dashboard and "Link Details" on a product page (string concatenation, no separator; the visible result UNVERIFIED in a browser) | class-post-type.php 406-416 |
| R14 | No admin-bar entry and no plugin-row action link of Lasso's own; the update checker (Lasso's update server) adds "View details" / "Check for updates" to the plugin row | grep; libs/plugin_update_checker Ui.php 24-25; affiliate-plugin.php 104-115 |
| R15 | **Foreign notices removed** on Lasso pages: `remove_all_actions( 'admin_notices' / 'all_admin_notices' )` (and network/user notices on multisite), and every third-party `in_admin_header` callback | class-hook.php 194-196, 837-870 |
| R16 | **Footer:** "Version 369" badge (and an RC commit hash when `checksum.txt` exists); `update_footer` is emptied on **all** admin pages | footer.php 47-57; class-hook.php 72 |
| R17 | **Notification region** `#lasso_notifications`: full-width coloured bars (green, red, orange, purple, dark purple), centred text, most with a × that collapses them; footer notices are appended on page load | header-new.php 243-245; footer.php 59-165 |
| R18 | **Script notices:** `successScreen / errorScreen / warningScreen(message, template, name)` fetch a template by `lasso_get_notification_template` (GET; `lasso_ajax` nonce; `edit_posts`; template must be a file in `notifications/`, else 400 "Invalid template."; params message, name, alert_id, alert_bg) and append it | lasso-helper.js 630-716; pages/class-ajax.php 1793-1843 |
| R19 | **save-success:** "**Yee haw!** Your <name> saved." (link, keyword, group, field); **save-fail:** "**Try again.** For some reason your <name> didn't save." (link, keyword, settings, group, field); **default-template:** the message in a bar of the given colour | save-success.php; save-fail.php; default-template.php; view-js.php 830, 2976, 2989; settings.js 2457, 3132-3134, 3226-3229 |
| R20 | **AJAX failure:** "An unexpected error has occurred please try again later." unless the response carries a string; full-screen loading overlay with the Lasso icon (`loadingScreen`) | lasso-helper.js 679-758 |
| R21 | **ga-warning:** "Please Enter GA Tracking ID Before Enable Click Tracking" (orange, ×) | ga-warning.php; settings.js 3686 |
| R22 | **Dead templates** (included nowhere, or commented out): `disable-plugin` ("Disable the <plugin> to avoid redirect conflicts."), `import-running` ("We're importing your links into Lasso. See the progress."), `license-expired` ("Your license key is not valid. Renew it now to continue using Lasso."), `amazon-not-configured` | the files; grep |
| R23 | **Notices owned elsewhere** (recorded, not built here): import-suggestion, lite-imported-from-pro (F33); auto-amazon, amazon-credentials-update, amazon-url-detected, amazon-auto-monetize-{contextual,import,settings-card,status-inner}, amazon-bulk-sync-pending, amazon-not-configured (C1); link_detail_notification — "This product may be out of stock." / "Your URL is broken.", dismissed 7 days in post meta `dismiss_out_of_stock` / `dismiss_broken` (F11, C1) | footer.php 71-139; pages/class-ajax.php 867-893, 1320-1345 |
| R24 | **lasso-update-available:** when `get_plugin_updates()` lists Lasso: "There's a new version of Lasso. **Update now**." → `update.php?action=upgrade-plugin&plugin=…` with WordPress's nonce; dark purple, no × | footer.php 62-69; lasso-update-available.php; class-setting.php 1351-1356 |
| R25 | **link-build-running:** while the Link Database process runs (`lasso_get_stats_in_report_page()['build'] >= 0`): "We're rounding up a list of your links. **See the progress**." → Settings → General; no × | footer.php 149-155; link-build-running.php; class-setting.php 1215-1256 |
| R26 | **link-build-running-after-importing:** when option `enable_scan_notice_after_import` is '1' and the force-scan job is scheduled with a total: "We are creating an index of your links. This will be complete in <ETA>. Please wait until then to begin any Imports." (orange; the ETA refreshed by the Automations poll, R43) | footer.php 157-163; link-build-running-after-importing.php; lasso-page.js 367-369 |
| R27 | **Not built (decision B3, 2026-11-14).** **Review card** (corner dialog): 30 days after first install, once per link milestone (25, 100, 250, 500, 1000, 2500, 5000), 30-day cooldown after any close, time re-ask every 120 days at most 3 times; stats tiles (links cloaked, posts monetized, clicks in 30 days ≥ 100); steps "Nice work so far" → "It's awesome!" (review on WordPress.org of Lasso Lite) / "I'm having some trouble" (opens Intercom) / "I'm testing things out" (snooze) / "Don't ask again" (disable); `?lasso_review_preview=1` forces it for admins | request-review.php; class-helper.php 6229-6356; class-enum.php 15-34 |
| R28 | **Not built (decision B3, 2026-11-14).** Review actions `lasso_review_snooze`, `lasso_disable_review`: `lasso_ajax` nonce, `edit_posts`; options `lasso_review_card_{milestone,snoozed_at,time_asks,disabled}`, `lasso_first_install_date` (set on first check) | pages/class-ajax.php 1848-1881; class-helper.php 6255-6310 |
| R29 | **Education boxes:** an image, a heading, a paragraph and (most) a Learn link: Dashboard empty "Get Started by Adding a Link" (+ "Add a Link" opens the modal); Groups empty; Locations empty "Add This Link Anywhere On Your Website"; Tables list empty "Create Beautiful Comparison Tables"; table details "Add a Product or Group to get started."; link opportunities "Turn Any Existing Link Into a Lasso Link" and its empty row "No opportunities found."; keyword "Create New Links Fast With Keywords" and "No opportunities found. Add a keyword to get started."; content "Identify And Fix Your Under-Monetized Content"; domain "Find Site-Wide Monetization Opportunities" | education/*.php; view-js.php 2570-2586; opportunities/header.php 87-98; tables/components/table-comparision.php 80; pages/table-details/class-ajax.php 780 |
| R30 | Empty-state boxes (Dashboard, Groups, Locations, Tables, table details, empty rows) are drawn when a list returns none; they have no close | view-js.php 2568-2586 |
| R31 | The four opportunity boxes have a ×; domain.php hides itself on click without saving, and is also wired to the saving close (R33) | education/domain.php 23-30; view-js.php 2905-2934 |
| R32 | Box texts name Lasso ("Turn Any Existing Link Into a Lasso Link", "Lasso will find every mention", "add your new affiliate URL to Lasso", "Lasso will track every location") | education/*.php |
| R33 | **Close saves:** `lasso_hide_education_box` (POST; `lasso_ajax` nonce; `edit_posts`) writes setting `<education_page>` = 'hide' — the key is taken from the request unchecked | pages/class-ajax.php 1749-1768 |
| R34 | A box shows unless its key (`link-, keyword-, content-, domain-opportunities`) is 'hide' | opportunities/header.php 84-98 |
| R35 | `?reseteducation` on an opportunities page clears the four keys | opportunities/header.php 76-82 |
| R36 | **Disable Help Tooltips** (`general_disable_tooltip`, F9) hides every `[data-tooltip]` tip and info icon (CSS on every Lasso page) **and** every opportunities education box | header-new.php 20-27; opportunities/header.php 87; view-js.php 2907-2911 |
| R37 | **Help tips** are CSS pseudo-elements of `data-tooltip` on labels; no focus, no screen-reader text (screen inventory) | header-new.php 20-27; screen-inventory.md |
| R38 | **Unsaved-changes modal** "Unsaved Changes" / "You have unsaved changes that will be lost if you leave this page. What would you like to do?" / **Leave Without Saving**, **Save Changes**, ×; static backdrop, no Escape | unsaved-changes.php |
| R39 | Other shared modals: url-delete ("Delete This Link?"), url-delete-reject ("Hold Up" …) — the product page's (F1 17A); license-activation ("Activating Lasso" / "Just a moment while we validate your license key.", progress); the generic confirm modal (`lasso_generate_modal`, default "Remove "This Product"" / "If removed, you won't be able to get its back.", Cancel, OK) and a progress modal; the checkbox component (a toggle and its label) | modals/*.php; lasso-helper.js 172-517; components/checkbox.php |
| R40 | **Automations:** header icon "Background sync" with a count badge; a click toggles a 320 px popover under it (kept in the viewport; a click elsewhere closes it): header "Automations", one line per job "<name> (<completed>/<total>)", or "Silence is a source of great strength - Lao Tzu" | header-new.php 178-181; lasso-page.js 1-16, 259-384 |
| R41 | `get_list_background_processing` (GET; `lasso_ajax` nonce; `edit_posts`): for each class of `Verbiage::PROCESS_DESCRIPTION` (filter `lasso_all_processes`) a job is listed when scheduled with work remaining (counts toward `running_total`) or when its queue is waiting for a manual start | class-lasso-process.php 648-708 |
| R42 | **Job names:** Checking for broken links, Syncing data, Importing links, Updating links (Link Database), Updating links (Build), Saving link changes, Removing attributes, Updating shortcodes, Reverting imports, Scanning for keywords, Updating links (Scan), Updating Amazon data, Adding Amazon data, Updating cloaked link prefixes, Scan all links, Bulk Add Links, Bulk Sync Amazon, Auto Monetize | class-verbiage.php 14-33 |
| R43 | **Polling:** at load, then every 15 s, stopping after 5 min (30 min while the Amazon import backlog drains); a result younger than 15 s from another tab is reused (localStorage `lasso_bg_process_data`, `lasso_bg_process_time`); count > 0 spins the icon and shows the badge; the force-scan ETA fills `.force-scan-eta` | lasso-page.js 1-257 |
| R44 | A job waiting for a manual start triggers `lasso_cron_handle_manually`; `lasso_check_crons` runs crons when WP-Cron does not (F11/F37) | lasso-page.js 386-448; pages/class-ajax.php 1600-1695 |
| R45 | **Wizard** = page `install` ("Configure"), reachable only while unlicensed (R5); purple full-height page, logo, white card; on load it calls `lasso_trigger_cron` | onboarding/index.php; class-hook.php 341-357 |
| R46 | **Activation:** no redirect after activation; `Activator::init` creates tables, default data, sets `auto_monetize_amazon` true, `lasso_force_to_run_new_scan`, `lasso_plugin_activated_at`, crons; the wizard is reached through the licence gate | class-activator.php 36-57, 137-139; affiliate-plugin.php 57-76 |
| R47 | **Step bar:** Activate, Theme, Customize, Amazon, Analytics, Imports, Done (Imports only when `should_show_import_page()`); done steps marked complete; a click on an earlier step goes back | steps.php; onboarding.js 40-55 |
| R48 | **Not built (decision B1, 2026-11-14: no Activate step).** **Activate:** "Let's Get Started" / "First, enter your license key. You can find it on your Lasso Account Page."; field "Enter your license key"; **Activate Lasso**; empty → "Please enter your license key."; failure → the server's message (else "This license key doesn't work. Double check and try again."); success → Theme step; `lasso_activate_license` checks the licence with Lasso's servers, copies the account's Amazon tracking ID when none is set, starts the link database, sets `lasso_activate_first_time`, crons | onboarding/index.php 43-56; settings.js 2576-2668; pages/install/class-ajax.php 35-91 |
| R49 | **Theme:** "Choose a Default Display Box Theme" / "This is an example of how a product will look on your site with Lasso."; eight cards Cactus, Flow, Lab, Money, Cutter, Geek, Llama, Splash (image + name badge); a click selects it, renders the live box with it, sets `theme_name` and opens Customize | theme.php; settings.js 2851-2879 |
| R50 | **Customize:** "Customize Your Displays" / "Don't worry, you can easily change all of these settings later."; live box; Badge, Title, Background, Button + Badge Text, Pros, Cons colours; Show Price, Show Disclosure; Primary Button text (placeholder "Buy Now") + colour; Secondary Button text ("Learn More") + colour; Disclosure textarea; each with its Display settings help text; every change re-renders the box; **← Change Themes**, **Skip →**, **Save and Continue →** | display.php; settings.js 2706-2779, 2904-2919, 2969-3080 |
| R51 | Customize's Save and Continue **does not save** (it re-renders and moves on); the next step's save sends the fields of every step on the page (`fetchAllOptions` reads all `.lasso-admin-settings-form` fields), so a skipped step's edits are saved too, and the Customize edits are lost if every later step is skipped | settings.js 131-169, 2904-2967 |
| R52 | Saves go through `lasso_store_settings` (the Settings save, F9) without the progress modal; a failure shows "Error!" or save-fail "settings" | settings.js 2379-2460 |
| R53 | **Amazon:** "Amazon Associates" + Learn; card "Amazon Associates Accounts" (Tracking ID for This Site, placeholder "tracking-20", "This is an invalid Tracking ID"); "Auto-Monetize Amazon Links" + Learn, "Enable Amazon Auto-Monetization", error line; "Amazon Product Data API": Default Tracking ID (locale), Creators API help link, Credential ID, Secret, Partner tag, Version, **Validate**; "Amazon Preferences": Show Prime Logo In Displays, Show Discount Pricing, Commission Upgrades (`auto_upgrade_eligible_links`, always ticked), Update Amazon pricing daily; the auto-monetize flip modal | onboarding/amazon.php |
| R54 | **Analytics:** "Google Analytics" + Learn; "Tracking ID or Property ID"; Click Tracking, Pageview, IP Anonymization switches (disabled until click tracking is on) with their tips | onboarding/analytics.php |
| R55 | **Imports:** "Imports" + Learn; Bulk Import, "All Sources", "Search URLs to Import", list Link Title / Import Target / Plugin, pager, the import modals; **Continue →** (F33's list) | onboarding/imports.php |
| R56 | **Done:** "You're Ready" / "That's it! You're now ready to add Lasso Displays to your site." / **Go to Dashboard** (a link that also fires a save of all fields) | done.php; settings.js 2922-2935 |
| R57 | Every wizard step is tracked to Segment | settings.js 2602-2967 |
| R58 | `onboarding/imports.php` 224 computes the default country as `! $x ?? '1'` (a boolean) — a bug with no effect on the step's list | imports.php 224 |
| R59 | **Product tour** (Intro.js) on `page=dashboard` only, while site option `lasso_dashboard_tour_done` is unset or '0' | class-product-tour.php 28-105; class-hook.php 1010-1011 |
| R60 | **Not built (decision B10, 2026-11-14: no Chat step).** **Steps:** #lasso-header-add "Create your first Lasso link here. Paste any URL to start customizing and sharing it."; Dashboard tab "Your Dashboard lists every Lasso link. Filter, search, and open any link to edit it."; search "Search by link name or URL. Use the keyboard shortcut shown next to the box for a faster jump."; Filters toggle "Open Filters to narrow links by group, type, placements, and comparison tables. Changes apply as you toggle."; filters panel "Pick group, link type, placements, or a comparison table here. Save a view when you find a filter set you reuse."; Opportunities "Opportunities identify links that aren't monetized yet or could be earning higher commissions."; Tables "Tables are comparison layouts you can embed on posts. Build once, reuse across your site."; Groups "Create Groups to organize related links so you can manage and display them together."; Help "Need a hand? Open Help for support docs anytime."; Chat "Need additional help? Chat with the Lasso team anytime by clicking this button." (only with the Intercom launcher) | class-product-tour.php 137-193 |
| R61 | Buttons Back, Next, Done, Skip; bullets; overlay 0.55; page interaction disabled; scroll so the tooltip is in view; a step whose element is missing is dropped; the filters panel opens before its two steps | lasso-product-tour.js 112-311 |
| R62 | Only Done or Skip persist (`lasso_pro_dismiss_dashboard_tour`: `lasso_ajax` nonce; `manage_options` or the Lasso permission → option '1', not autoloaded); Escape and the overlay do not close the tour | lasso-product-tour.js 20-29, 268-288; pages/class-ajax.php 1774-1788 |
| R63 | The state is **site-wide**: one user's Done or Skip ends it for all (the script's and the hook's comments say user meta; the code is a site option) | class-product-tour.php 24-50 |
| R64 | **Not built (decision B10, 2026-11-14).** The tour starts after waiting up to 2.5 s for the Intercom launcher | lasso-product-tour.js 313-328 |
| R65 | **Command palette:** loaded on every Lasso page, the post editor and other post types' editors | class-hook.php 1033-1072 |
| R66 | **Shortcut** Ctrl+Shift+L, ⌘⇧L on a Mac (`navigator.platform` starts with "Mac"); toggles; ignored while typing in a text input, textarea or select outside the palette | command-palette.js 35-82, 406-424 |
| R67 | **Dialog:** title "Search Lasso links", × "Close", input placeholder "Search by link name or URL…", hint "Shortcut: <keys>. Press again to close.", results list "Search results"; "Loading…", "No links found." | class-hook.php 1055-1071; class-helper.php 7036-7076; command-palette.js 98-136 |
| R68 | Opening clears the input and searches "" at once (the ten most recently modified links); typing searches after 280 ms; an older answer is dropped; closing aborts the request | command-palette.js 277-396 |
| R69 | **Search** `lasso_search_attributes` (POST; `lasso_ajax` nonce; `edit_posts`): **title only** (`post_title LIKE`, spaces as wildcards) despite the placeholder, ordered by `post_modified` desc, ten per page; each result: thumbnail (40 px), name, permalink (an Amazon product's monetized URL), edit URL | pages/class-ajax.php 1176-1240 |
| R70 | **Keys:** ↓ from the input to the first result, ↓/↑ between results, ↑ from the first back to the input; Enter or a click opens the result's edit page; Escape closes; Tab is trapped; focus returns to where it was | command-palette.js 156-209, 426-472 |
| R71 | Every `[data-lasso-cp-shortcut]` (the Dashboard search box) is filled with the shortcut for the user's system | command-palette.js 84-92; dashboard.php 72 |
| R72 | The palette has **no commands** beyond link search: nothing in it acts on Dashboard state | command-palette.js 1-498 |
| R73 | **Settings leave guard:** on a page with the unsaved modal and a settings form, a change compared with the loaded values asks before a link leaves (not `#`, `javascript:`, `_blank`, links inside the modal or the save modal); the browser's own prompt on unload; Save Changes saves, then follows the link | settings.js 3326-3439 |
| R74 | The Settings pages that include the modal: General (and Display, Amazon through the same script) | general.php 795; settings.js 3399-3402 |
| R75 | The product page and the Dashboard's quick edit have their own guards (F1, built 15D, 16B) | url-details.php 1667-1721; view-js.php 693, 1010 |
| R76 | Pagination memory: a list's page number is kept in localStorage `lasso_pagination` per page (and post) and restored on the next visit unless the address has `#page-N` | lasso-helper.js 359-377, 592-629 |
| R77 | `lasso.js` rewrites the WordPress submenu links from `admin.php?page=` to `edit.php?post_type=lasso-urls&page=` (only with ReactPress); `lasso_load_final` moves Lasso to the end of `active_plugins` on any activation | lasso.js; affiliate-plugin.php 87-102 |

## 4. Decisions

Settled by Claude from Lasso and the records (none drops anything of Lasso's own function or reverses a user decision):

- **D1 Header order is Lasso's**, Dashboard, Opportunities, Tables, Groups, then Affibloom's **Settings** tab (the
  approved screen map; Lasso reaches Settings from the WordPress menu only). A tab appears with its screen's task
  (F22, F25, F23 G1), never as a dead link; ON1 builds the list they join.
- **D2 Opportunities opens the link opportunities screen**, since Lasso's target, programme opportunities, is cloud
  (deviation 52); the tab is current on every opportunities page Affibloom has (R3).
- **D3 The current tab follows Lasso's page groups** (R3); product pages already mark Dashboard.
- **D4 The WordPress menu** keeps Lasso's position 20 (Affibloom sits at 26 today) and submenu order (R11) for the
  screens Affibloom has; detail pages are registered and hidden as the product page is now. The icon stays Affibloom's.
- **D5 Highlight:** a hidden page names its parent menu item (`parent_file` / `submenu_file`) instead of Lasso's
  script (R12).
- **D6 Browser titles** are the screen names, "Link Details" for a product page (R13 without the concatenation).
- **D7 Footer:** "Version <AFFIBLOOM_VERSION>" on every Affibloom page (R16); no build hash (no RC packaging).
- **D8 One notice region** `#affibloom-notices` under the header for persistent notices, in the design system's Notice;
  the existing toasts stay for "saved" and "failed" messages (U6 wording, each feature's own text). Lasso's generic
  failure text (R19 save-fail, R20) is the fallback when a failure has no message: "Try again. Your <name> didn't
  save." becomes each screen's own sentence; "An unexpected error has occurred please try again later." is kept.
- **D9 Foreign notices are removed on Affibloom pages** as Lasso does (R15), on `admin_head` late and on
  `in_admin_header` except WordPress core's admin bar.
- **D10 Notices are PHP-rendered** by registered providers (condition, text, action, dismissal); no AJAX template
  fetch (R18): a script notice is built in the browser from a `<template>`, as the toast is. Lasso's unchecked
  template parameter is not reproduced.
- **D11 Update notice:** shown when `get_plugin_updates()` lists Affibloom, with WordPress's nonce'd upgrade link;
  wording without the product name. Affibloom has no update server, so it shows only if one is ever configured.
- **D12 Dead templates** (R22) are not built: they never show in Lasso.
- **D13 Automations** lists Affibloom's own background jobs from a registry each job's feature fills (first: bulk add,
  F18); same counts, names in Lasso's words where the job matches (R42), polling and cross-tab reuse as R43 through
  `GET /affibloom/v1/jobs`. The manual-start fallback (R44) is F11's.
- **D14 Link index notices** (R25, R26) read F21's index job; "See the progress" goes to F21's Link Index card.
- **D15 Education boxes:** a shared component (image slot, heading, text, optional action, optional close); each box
  is built by its screen's feature with Lasso's words, "Lasso" removed (R32); a close is stored **per site, as
  Lasso's** (setting keys), in `affibloom_settings['education_hidden']` with an allow-list of box keys
  (R33's unchecked key fixed); `&reseteducation=1` clears them; Disable Help Tooltips hides them (R36). Keyword boxes
  are not built (keyword opportunities are excluded, deviation 52).
- **D16 Help tips** stay Affibloom's accessible "?" tips (U3); F9's 9K hides them when Disable Help Tooltips is on.
- **D17 The wizard saves what each step shows:** Save and Continue saves that step's fields (including Customize,
  R51's lost-edits bug fixed); Skip saves nothing; through the Settings save routes F9, F16, C1 and C3 already build.
  Telemetry (R57) is not built.
- **D18 Tour:** Affibloom's own tour component (no Intro.js, ADR-7), Lasso's steps and words without "Lasso", site-wide
  state `affibloom_tour_done` (R63); only Done and Skip persist. Task 26 copies Lasso's `lasso_dashboard_tour_done`
  (this site's team has finished it). Steps whose target is missing are dropped (R61), so Help and Chat drop with N3/N5.
- **D19 Palette:** Affibloom's dialog in the design system, Lasso's keys and behaviour (R65-R71), title "Search
  links"; search through the planned `GET /affibloom/v1/products?search=` (F23 D10), which matches the name and the
  link, as the placeholder already promises (Lasso searches the title only, R69); ten results, newest change first.
  F19 shows the shortcut in the Dashboard search box (interface.md's "not built" note is withdrawn). Nothing in the
  palette changes Dashboard state (R72).
- **D20 Settings get the leave guard** (R73-R74) with the existing `unsaved-modal.php`.
- **D21 Not built, no loss:** `lasso.js`'s link rewrite and `lasso_load_final` (R77): Affibloom's pages are
  `admin.php` pages and need no load order; the generic confirm and progress modals (R39) are the design system's
  modal and save dialog; the checkbox component is `toggle.php`.

**Needs the user** (recommendation first):

- **N1 The wizard's trigger.** Lasso opens it only while unlicensed and starts with licence activation (R5, R45, R48,
  cloud). Recommended: build it without the Activate step at `admin.php?page=affibloom-setup`; open it once after
  Affibloom's first activation on a site with no Affibloom settings and no Lasso data to import (so it never opens by
  itself on this site); no menu entry afterwards, as Lasso's Configure disappears once licensed.
  → **Decided 2026-11-14:** D13: the user accepted the recommendation
- **N2 Licence and account content** — the Activate step and its modal ("Activating Lasso"), license-expired,
  **Update Account**, the WordPress menu's Account, the header's and menu's Analytics and Marketplace, the update
  checker's row links (R2, R4, R11, R14, R22, R48; all `app.getlasso.co` or Lasso's update server). Recommended: omit
  (rule C).
  → **Decided 2026-11-14:** B1: omitted.
- **N3 Lasso's help and marketing links** — the Help menu ("Support docs", "Lasso affiliate program"), the menu's
  Support item, and all 29 "Learn" / "See How To…" links (R7, R9, R11). Recommended: omit; boxes keep their text; the
  tour's Help step drops. (F23 D9 omitted Groups' Learn on deviation 52; this asks for the approval it needs.)
  → **Decided 2026-11-14:** B2: omitted.
- **N4 The review card** (R27-R28): it asks for a WordPress.org review of Lasso Lite and sends trouble to Lasso's
  Intercom. Recommended: omit.
  → **Decided 2026-11-14:** B3: omitted.
- **N5 Intercom chat, Segment, PostHog and Sentry** (R8, the tour's Chat step R60, R64): all send data to third
  parties; Sentry shows its own report dialog. Recommended: omit (rule C); no replacement.
  → **Decided 2026-11-14:** B1 and B10: omitted.
- **N6 WordPress's footer version** (R16): Lasso blanks it on every admin page. Recommended: blank it on Affibloom's
  pages only (the rest of wp-admin is not Affibloom's).
  → **Decided 2026-11-14:** D12: the user accepted the recommendation
- **N7 The wizard's Amazon step "Commission Upgrades"** (R53, Lasso brand deals, cloud): recommended omit, as C1
  decides for Settings → Amazon.
  → **Decided 2026-11-14:** B4: omitted.

## 5. Scope that needs a home

- Each screen's education box or empty state with Lasso's words (R29): Dashboard (built, 13D), Groups (F23 G1),
  Locations (F12), Tables list and table details (F25), link and domain opportunities and their empty rows (F22),
  content opportunities (F22, or C if its data is cloud — UNVERIFIED here).
- Tab and menu entries: Opportunities (F22), Tables (F25), Groups (F23 G1), Fields (F26), Import (F33), History (F34).
- Jobs that join Automations: link checks (F11), link index and rescans (F21), imports and reverts (F33), bulk add
  (F18, first), Amazon updates (C1, dormant).
- Pagination memory across visits (R76) → F19 and each list screen.
- Lasso's plugin-conflict code on its pages (class-hook.php 83-228: removing other plugins' scripts and styles, WP
  Rocket, Gutenberg intercept) → **unowned**; Affibloom loads its own assets only on its pages and needs none of it
  unless a conflict is found (recommend: no task).
- The manual cron fallback (R44) → F11 (F37 section 5); F11's drafts have no task for it yet.
- Task 26 copies `lasso_dashboard_tour_done` and the four `*-opportunities` hide keys.

## 6. Edge cases

1. A notice provider throws: that notice is skipped and logged; the page still renders.
2. Two tabs open: Automations reuses a result younger than 15 s (R43); a job finishing between polls shows on the next.
3. The Automations route fails or returns 403: the icon stays still, no error shown (Lasso's silent failure).
4. A user without `Capabilities::PRODUCTS`: no Affibloom menu, no palette, no Automations (route 403).
5. Palette: the shortcut pressed in the post title field does nothing; in the palette's own input it closes it; a slow
   answer for an older query is dropped; an empty site shows "No links found.".
6. Tour: a step's target hidden (narrow screen, a tab not built yet) is skipped; with no steps left, no tour.
7. Wizard: leaving half-way keeps what was saved; opening it again starts at Theme; a save failure keeps the step open
   with the toast's error.
8. Education close while offline: the box hides at once; the failed save brings it back on the next visit.
9. `?reseteducation` without the capability does nothing.
10. Update notice when the update needs credentials (FTP): WordPress's own update screen asks for them.
