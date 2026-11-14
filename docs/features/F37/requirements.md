# F37 Tools and diagnostics: requirements

Status: written 2026-11-14 from a sweep of Lasso 369 (Settings → Logs, DB Status, PHP compatibility, Uninstall,
activation, deactivation, upgrades, Permissions, licence and feature flags). Gate 4 for Tasks TL1-TL7: waiting.

## 1. User stories and acceptance criteria

- As the site owner, I can see what the plugin logged, check and repair its tables, choose who may use it, and
  activate, upgrade, deactivate or delete it without leaving broken schedules or stray data.

- **AC1** Activating Affibloom creates or updates its tables, records its version and activation time and flushes
  rewrite rules; deactivating clears every Affibloom cron hook and flushes rewrite rules; nothing else is removed.
  (TL1; R20-R25)
- **AC2** After an update, the first admin page load runs the pending schema and data steps once (a lock prevents two
  runs), records the new version and flushes rewrite rules. (TL2; R26-R30)
- **AC3** `admin.php?page=affibloom-logs` (hidden page, Settings band) shows "Enable Logs" and, read only, the newest
  log of each type: Import Log, Link Discovery, Amazon Processing, Amazon Product API Errors, Link Alerts, Duplicate
  Slug Changes, SQL Errors, with Lasso's descriptions; Save Changes stores the switch. (TL3; R1-R8)
- **AC4** With logging off only forced entries are written; logs older than 14 days are deleted daily; log files are
  not reachable from the web. (TL3; R4-R6, D3)
- **AC5** `admin.php?page=affibloom-db-status` lists each Affibloom table with the result of creating or updating it
  ("Table already exists." or what changed); a table that is missing or lacks a column is recreated automatically at
  most 3 times an hour. (TL4; R9-R13)
- **AC6** Deleting the plugin removes nothing unless "Delete all Affibloom data when the plugin is deleted" is on
  (Task 38); the General tab's Uninstall card holds that switch and lists the posts that still use Affibloom; no post
  is rewritten. (TL5, Task 38; R14-R19, N1)
- **AC7** Permissions: "Select the minimum user role that can access Affibloom." with Administrator, Editor, Author,
  Contributor, Subscriber; the chosen role and above see the menu and screens; only administrators can change it.
  (TL6; R31-R35, N2)
- **AC8** A server below the required PHP or WordPress version shows "Affibloom needs PHP 8.1 and WordPress 6.5 or
  newer. It has not started." and nothing else runs. (built in Task 1; R36)
- **AC9** The admin footer shows "Version <n>". (TL7; R37)

## 2. Evidence

Read in full: `admin/views/settings/{logs,db-status,php-compatibility,uninstall,header}.php`,
`admin/views/rows/db-table-row.php`, `admin/views/modals/{remove-attributes-confirm,stop-lasso-analytics-confirm}.php`,
`classes/class-log.php`, `class-activator.php`, `class-deactivator.php`, `class-update-db.php`,
`class-cache-per-process.php`, `class-config.php`, `class-encrypt.php`, `class-verbiage.php`, `class-beacon-proxy.php`,
`class-lasso-license.php`, `class-lasso-lapsed-install-policy.php`, `class-launch-darkly.php`, `class-lasso-init.php`,
`pages/install/class-ajax.php`, `affiliate-plugin.php`, `admin/views/footer.php`; `class-lasso-db-script.php` outline;
`models/class-model.php` 880-1060, 1210-1222; `class-helper.php` 900-935 (`write_log`), 6170-6184;
`class-lasso-cron.php` 325-340, 5666-5700; `class-setting.php` 769-791, 1259-1289; `pages/class-hook.php` 280-560,
2040-2098; `pages/class-ajax.php` 1513-1716 (status, crons), 1883-1912 (heartbeat); `class-post-type.php` 309-360;
`lasso-page.js` (Automations panel). Affibloom: `affibloom.php`, `src/Plugin.php`, `Capabilities.php`, `AdminMenu.php`,
Task 38 (phase 8), decision 1 (`task-6-decisions.md`).

**This copy, 2026-11-14:** 29 `wp_lasso_*` tables, all InnoDB and `utf8mb4_unicode_520_ci` like `wp_posts` (healthy);
`lasso_version` 369, `lasso_rc_version` 369.20261104, no migration lock; `lasso_recreate_table_time` 2,
`lasso_next_time_recreate_table` 0. Log folder `wp-content/plugins/lasso/logs/`: 26 files 2026-11-05..13
(build_link_database, url_issue_check, process_sync_*, scan_links, link_database_force_scan_all_posts) although
`enable_logs` is off (forced entries). `lasso_disable_processes` 0, `lasso_remove_attribute_completed` 0, uninstall
flags unset (never run). `lasso_license_status` 0; LaunchDarkly cache: only `lasso-lean` = off (expired). 5
administrators, no other users with roles.

## 3. What Lasso does

| # | Rule | Source |
|---|---|---|
| R1 | **Logs page** `page=settings-logs` (hidden; only by URL), H1 "Logs", the Settings tabs with none active | class-hook.php 486-490; settings/header.php 28-30; class-post-type.php 309-353 |
| R2 | "Enable Logs" switch (`enable_logs`, default off) and Save Changes (posts with an empty tab) | logs.php 30-39, 108-113 |
| R3 | Seven read-only boxes, newest file of each type (`logs/*_<type>.log`, newest by name): Import Log "All the plugin import connections." (import); Link Discovery "Every time Lasso discovers and stores a new link on your site." (link_database — also matches `*_build_link_database`); Amazon Processing "Each time Lasso updates your Amazon links." (cron — written by nothing); Amazon Product API Errors "Any errors that occur when trying to hit the Amazon Product API." (amazon_api_error); Link Alerts "Every time Lasso runs a health check against a URL." (url_issue_check); Lasso Duplicate Slug Changes "Any slug conflicts we detect when changing a permalink." (duplicated_slug); SQL Errors "Any database errors that occur." (sql_errors) | logs.php 41-102; class-setting.php 1264-1289 |
| R4 | `write_log( message, type, date, force )`: skipped when logging is off unless forced; file `logs/<Y_m_d>_<type>.log` in the plugin folder, line `[d/M/Y:H:i:s] (<script>) <message>`; arrays printed | class-helper.php 904-934; class-log.php |
| R5 | Forced entries: SQL errors, table recreation, process memory baselines, some error logs | class-model.php 930-954; class-lasso-process.php 508 |
| R6 | Logs older than 14 days are deleted by the daily clean-up cron | class-lasso-cron.php 328-339, 5669-5700 |
| R7 | No download, clear or search of logs | (absent; grep) |
| R8 | The folder is inside the plugin (web-reachable; `.gitignore` only) and is lost on every update | class-log.php 73; this copy |
| R9 | **DB Status page** `page=settings-db` (hidden), H1 "Lasso Tables"; columns "Table Name", "Output" | db-status.php; header.php 32-34 |
| R10 | Loading the page runs the create script of 26 tables (dbDelta + collation repair to `wp_posts`'s) and prints each result ("Table already exists.", dbDelta's message, "Failed to update table collation.", "No output returned"); 3 tables are not listed (url_variants, background_process_runs, link_updates) | db-status.php 18-89; class-model.php 1002-1030, 1210-1222 |
| R11 | `rows/db-table-row.php` (toggle per table) is not used anywhere | grep |
| R12 | **Self-repair:** a query error "doesn't exist", "Illegal mix of collations" or "Unknown column" on a Lasso table recreates all tables, at most 3 times, then once an hour; logged to sql_errors | class-model.php 930-986 |
| R13 | Creation passes log their errors instead of repairing | class-model.php 902-937 |
| R14 | **Deleting Lasso removes nothing:** there is no `uninstall.php` or uninstall hook | affiliate-plugin.php 75-76 |
| R15 | "Uninstall Lasso" on General (F9 R63-R65) rewrites posts; also offered on the **Uninstall page** (`page=uninstall`, only while the licence is inactive): "Uninstall Plugin", "If you want to uninstall Lasso, we recommend that you remove all data attributes from all your links first.", **Remove Lasso Attributes**; after it ran: "Lasso attributes are removed." | uninstall.php 30-53 |
| R16 | Uninstall page, licence inactive: "Want to keep your Lasso links and displays but stop Lasso's click tracking and analytics?" **Stop Lasso Analytics** → "Stop Getting Analytics?" … → switches off performance tracking, GA click tracking, local store, deep links, link boosting, Amazon and affiliate auto-monetize, syncs to Lasso | uninstall.php 38-97; stop-lasso-analytics-confirm.php; settings-general/class-ajax.php 250-299 |
| R17 | **Deactivation:** clears every hook in `Lasso_Cron::CRONS`, resets `disable_processes` and `remove_attribute_completed`, flushes rewrite rules | class-deactivator.php; affiliate-plugin.php 69-73 |
| R18 | Licence inactive → every Lasso page redirects to `page=install` (onboarding) or the Uninstall page; licence active → those two redirect to the Dashboard | class-hook.php 286-314, 353-357 |
| R19 | All processes disabled (after a removal run) stops every background job | class-lasso-process.php 617-622 |
| R20 | **Activation:** store version; create all 29 tables; default data (issue definitions, Amazon tracking defaults, default fields); `auto_monetize_amazon` on; force a full scan; `lasso_plugin_activated_at`; schedule crons; flush rewrite rules; download the cloud snippet | class-activator.php 36-146; affiliate-plugin.php 57-63 |
| R21 | Lasso moves itself last in `active_plugins` on any activation | affiliate-plugin.php 87-102 |
| R22 | First 24 h after activation the Dashboard says totals may still be scanning in | class-activator.php 65-71 |
| R23 | Auto-update for Lasso is switched on once (`lasso_auto_update`) | class-lasso-init.php 229-251 |
| R24 | Licence activation (`lasso_activate_license`): stores the key, cloud getinfo (may return a tracking ID), default data, cloud status; then index, first-activation flag, crons | pages/install/class-ajax.php |
| R25 | Text domain `lasso-urls` loaded on `plugins_loaded` | class-lasso-init.php 212-214 |
| R26 | **Upgrades:** `Update_DB` runs on admin requests only (not AJAX or cron) under a 5-minute lock option; steps gated by `lasso_version` (< n) and by an RC version; then `lasso_version` = current | class-update-db.php 38-83, 130-549 |
| R27 | Version change seen on `init` → rewrite rules flushed and version stored; also after the plugin is updated through the upgrader | class-hook.php 2040-2098 |
| R28 | Updates come from Lasso's server (`lasso.link/server/update`, plugin-update-checker), plugin info dialog from it too; "lean" flag blocks auto-update for active licences | affiliate-plugin.php 104-115; class-lasso-init.php 294-681; class-lasso-license.php 152-167; class-hook.php 1887-1898 |
| R29 | Footer notice when an update is available ("lasso-update-available") | footer.php 62-69 |
| R30 | Per-request cache of settings and other lookups (`Cache_Per_Process`) | class-cache-per-process.php |
| R31 | **Permissions:** `lasso_permission` → capability: Administrator `manage_access` (no role has it, so administrators only, via the `manage_options` short-circuit), Editor `edit_others_posts`, Author `publish_posts`, **Subscriber `edit_posts`, Contributor `read`** (swapped: Contributor opens it to subscribers) | class-setting.php 769-791 |
| R32 | The permission gates the whole Lasso menu and every page (non-administrators get the mapped capability; no access removes the menu) | class-hook.php 535-570 |
| R33 | It also gates settings saves, stats, licence, uninstall actions, Fix Overwritten Links, content-opportunity export, tour dismissal; most other AJAX handlers check `edit_posts` | settings-general/class-ajax.php 308-317; pages/class-ajax.php 1777, 2641-2650; fix-overwritten-links/class-ajax.php 20-30 |
| R34 | Only `manage_options` users can change the permission (dropped silently from others' saves) | settings-general/class-ajax.php 365-370 |
| R35 | Default "Administrator" | lasso-constant.php 365 |
| R36 | `php-compatibility.php` ("Your PHP version (x) is not compatible with Lasso, please upgrade your PHP to the newer versions.") is included by nothing in 369 | grep |
| R37 | Footer: "Version <n>" badge and the build hash when present | footer.php 47-57 |
| R38 | **Cloud and telemetry, not tools:** licence (`/license/status`, `/server/getinfo`, daily status cron), lapsed-install policy, LaunchDarkly flags (`lasso.link/api/launch-darkly/flags`, cached 1 h), Encrypt (RSA/AES for Lasso requests), Sentry, Intercom, Segment/PostHog, Beacon_Proxy (forwards click beacons to `codedrink.com/js/e` — click analytics, not a support widget) | the classes named |
| R39 | **Feature flags** (all default off): `auto_monetize` (no caller), `audit_log` (Audit section, History pages and menu), `lasso-lean` (lean cron set, blocks auto-update), `lasso-ai-display` (AI Text display type and its route), `site-stripe` (no caller) | class-launch-darkly.php; callers |
| R40 | `Verbiage::PROCESS_DESCRIPTION` names each background job for the header "Automations" list; `SUPPORT_SITES` gates fixes to other customers' domains (none is this site) | class-verbiage.php; lasso-page.js |
| R41 | `lasso_get_status` has no caller in 369; `lasso_heartbeat` reports bulk add (no nonce) and bulk Amazon sync progress | pages/class-ajax.php 1516-1557, 1886-1912 |

## 4. Decisions

- **D1 Activation/deactivation** as R20/R17 for what Affibloom has: Migrator, version, activated time, rewrite flush;
  deactivation clears every `affibloom_*` cron hook (from one list each cron module registers). No reordering of
  plugins, no auto-update switch, no snippet download (R21, R23: change other plugins' or WordPress's settings; R20
  snippet: C).
- **D2 Upgrades** as R26/R27: `affibloom_db_version` with numbered steps, run on admin requests under a 5-minute lock,
  and on activation and `wp affibloom upgrade`; rewrite flush when `AFFIBLOOM_VERSION` changes. No update server
  (R28: C); updates stay zip uploads.
- **D3 Logs** keep Lasso's types, page, switch, 14-day retention and newest-file view, written to
  `wp-content/uploads/affibloom-logs/` with an `index.php` and a `.htaccess` deny (Lasso's folder is public and lost on
  update). Affibloom's types: import (F33), link_database (F21), amazon (C1), amazon_api_error (C1), url_issue_check
  (F11), duplicated_slug (F24), sql_errors (TL4). No download or clear (Lasso has none).
- **D4 DB Status** lists every Affibloom table (all, not a subset) and runs the Migrator's create step on load, as
  Lasso; self-repair as R12 (3 times, then hourly).
- **D5 Feature flags:** no cloud flags; each flagged feature's owner decides it as a Lasso feature (`audit_log` → F34,
  `lasso-ai-display` → F30, lean → F11 not built).
- **D6 PHP compatibility:** built (Task 1 guard in `affibloom.php`); Lasso's unused view is not copied.
- **D7 Footer version** "Version <n>" in Affibloom's admin footer.

**Needs the user**

- **N1 Lasso's uninstall rewrites posts** (R15, F9 R64). Recommended: not built; the Uninstall card holds Task 38's
  opt-in (an Affibloom addition — Lasso deletes nothing) and lists the posts using Affibloom with edit links.
- **N2 Permissions vs decision 1.** Recommended: build Lasso's select (TL6) with the corrected mapping (Contributor
  `edit_posts`, Subscriber `read`), `PRODUCTS` and `SETTINGS` follow it, only administrators change it; live value
  Administrator keeps today's behaviour.
- **N3 Lasso cloud items** (R16 Stop Lasso Analytics, R24 licence activation, R28 updates, R38, R39 flag service): not
  built (rule C). Recommended: confirm.
- **N4 `SUPPORT_SITES`** fixes run only on the listed domains, none of them this site. Recommended: not built.

## 5. Scope that needs a home

- Automations list and manual cron fallback (R40, `lasso_check_crons`, `lasso_cron_handle_manually`) → F11 (header
  piece F38).
- Bulk add / bulk Amazon progress (R41) → F18 / C1. Onboarding redirect (R18) → F38.
- Each feature registers its table with TL4's schema list and its cron hooks with TL1's list.

## 6. Edge cases

1. Two admin requests after an update: the lock makes the second skip; a stale lock (older than 5 minutes) is cleared.
2. A failed step leaves the version unchanged so it runs again; the lock is released in `finally`.
3. Uploads folder not writable: logging silently stops; the Logs page says the folder cannot be written.
4. Permission set to Editor and an Editor saves Settings: the permission field is ignored for them.
5. Deactivate while a background job runs: its hook is cleared; the job's state option stays for the next activation.
