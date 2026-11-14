# Affibloom — progress

Update this file at the end of every task and every session. Newest session log entry first.

## Status by phase (verified against the code, 2026-11-14)

The status below was checked against the code by the full planning pass (deviation 89; audits in the session log).
Every phase is planned in full; **gate 4 was given for the whole plan on 2026-11-14** with the answers in
[`open-decisions.md`](open-decisions.md). The task lists are in `plans/phase-<n>-*.md`; the index is in
`plans/00-overview.md`.

| Phase | Name | Done | Open | Status |
|---|---|---|---|---|
| 0 | Foundation and tooling | 1-3 | — | **Done**, signed off 2026-11-12 (CI never ran: deviation 10; the integration suite has never run) |
| 1 | Behaviour specs and baselines | 4-7 | — | **Done**, signed off 2026-11-12 |
| 2 | Settings and product library | 2X0, 8, 9, 9A, 10A-10C, 10E, 10F, 11A-11E, 12A-12E, 13A-13E, 14A-14D, 14F-14H, 15A-15D, 16A-16C, 17A-17D (+ the one-time Lasso import button, deviation 87) | 45: 2X1-2X5, G1-G5, 9B-9L (not 9J), FL1-FL5, TL1-TL2, 13F-13J, ON1-ON2, C1A-C1P (Phase 2 part) | **In progress.** Verified: everything done is built as recorded, except the corrections now planned as 2X1-2X5, 13F-13H, FL5 (deviation 89) |
| 3 | Product displays | — | 50: 18A-18K, 19A, 20A-20D, 21A-21C, 22A-22E, 10D, 14E, C1F-C1G, 18S1-18S12, 18S14, DT1-DT10 | Planned |
| 4 | Editors | — | 18: 23A-23G, 24A-24E, ED1-ED5, MCP1 | Planned |
| 5 | Lasso compatibility and migration | — | 92: 25A-26P (core), 35A-35J and 9J (link index), ON3-ON4, 27A-27X8, ED6, CL1-CL11, V1-V5, 28A-28E, H1-H4, IM1-IM19 (not IM11, IM18), 29A-29E (not 29C) | Planned |
| 6 | Link health | — | 13: 30A-30C, 31A-31D, 32A-32E, 26K | Planned |
| 7 | Locations, clicks, opportunities, auto-detect, tables | — | 63: 36A-36E, 33A-34C, C3A-C3C, 13K, 26J, C1E, C1H, O1-O10, C2A-C2C, AM1-AM6, 26N, T1-T17, 18S13, 26I, 27E, IM11, IM20, IM18, 29C | Planned |
| 8 | Hardening and documentation | — | 16: 37A-37D, 38, TL3-TL7 (not TL5, in 38), ON5-ON9, 39, 39A | Planned |
| 9 | Staging rollout and cutover | — | 3: 40-42 | Planned (rewritten 2026-11-14) |

## Next up

1. **Gate 4 given for the whole plan** (the user, 2026-11-14 walkthrough; answers in `open-decisions.md`). Building
   resumes task by task in the phase files' order.
2. **Tasks 2X0 and 2X3 are done; 2X2 is written and passes, its quiet double F1 run is still open**; 2X1 awaits the user's run. Then 2X4-2X6, then Groups G1-G5.
3. Standing rule (the user, 2026-11-14): **Affibloom works with no Lasso dependency and no Lasso branding.**
4. Lasso is inactive on the Local copy since 2026-11-14 (backup `app/affibloom-backup-2026-11-14-before-lasso-deactivate.sql`).
   The Local copy still needs a reset before the next full test run.
5. Open, from the client: C1 Creators API credentials; the optional GA4 check.
6. Pending, no action unless the user brings it up: CI has never run (deviation 10).

## Earlier "Next up" notes (history, before 2026-11-14)

The status table as it stood before the 2026-11-14 pass (commit hashes per task):

| Phase | Tasks | Status |
|---|---|---|
| 0 Foundation and tooling | 1–3 | **Done, signed off by the user 2026-11-12 with one condition:** the CI run is still pending (GitHub billing); see deviation 10 |
| 1 Behaviour specs and baselines | 4–7 | **Done, signed off by the user 2026-11-12:** Task 4 `7d9d00e`, Task 5 `ebcbb62`, Task 6 `b0e7abc` + decisions `6f15ea1`, Task 7 `ed934a9` |
| 2 Settings and product library | 8–17 | **In progress:** Task 8 `aa4da3c`, Task 9 `8035d66`, Task 9A `6ec0f2e` + Aurora `469829a`; Task 10 superseded by 10A–10F (full-Lasso scope, deviation 24); **10A `a6ea427`, hex defaults `2049291`, 10B `87e6ccc`, 10C `a978948`, 10E `51a4584`, tips `3412dbd`, 10F `87620f1` done**; 10D waits for Task 18x; Task 11 superseded by 11A-11E (F18, gate 4 given, deviation 34; docs `13a01dc`); **11A `597fd4a`, 11B `8f41d04`, 11C `b89ae01`, 11D `f1067d7`, 11E `f830fea` done** (deviations 35-40); Task 12 superseded by 12A-12E (F2 gates 1-2 re-run, deviation 42; gate 4 given 2026-11-13; docs `4fc8a28`); **12A `5dcfa7a`, 12B `b1db8c5`, 12C `a4b3c52`, 12D `5d1d634`, 12E `e839339` done** (deviations 43-47): Task 12 complete; Task 13 done through 13A `99a4fba`, 13B `ad508f4`, 13C `b2ab7b3`, 13D `f32c89f`, 13E `00dc1af` (F19; deviations 48-54); spacing `ee6743c`; Add Link endings `6d9a5ef`; Task 14 superseded by 14A-14E (deviation 57; docs `18544e4`), **14A `b3686ab`** (deviation 58); F28 docs `0ced689`, **14F `7c69cf7`** (deviation 60), **14G `c799ff5`** (deviation 61); F29 docs `d546fbf`, **14H `b50ace2`** (deviation 64); F19 fix `6a743ed`; **14B `9640685`** (deviation 67); **14C `0a24290`** (deviation 68); **14D `0ee6801`** (deviation 69); Task 15 superseded by 15A-15D (deviation 70), gate 4 given 2026-11-13; **15A `59ee8da`** (deviation 71), **15B `7e77a89`** (deviation 72), **15C `0da4b16`** (deviation 73), **15D `b3202d7`** (deviation 74): **Task 15 complete**; Task 16 superseded by 16A-16C (deviation 76), gate 4 given 2026-11-13; **16A `852cb00`** (deviation 78), redesign `bf282a7` (79), **16B `674c24c`** (80), **16C `6471087`** (81): **Task 16 complete**; Groups (F23) gates 1-2 and Tasks G1-G5 written (deviation 88), waiting for gate 4; Task 17 superseded by 17A-17D (deviation 82; D33-D34 the user's answers), gate 4 given 2026-11-13; **17A `d7a0493`** (deviation 83), **17B `f61e12c`** (deviation 84), **17C `c86378f`** (deviation 85), **17D `82b6015`** (deviation 86): **Task 17 complete** |
| 3 Product box rendering | 18–22 | Not started |
| 4 Editor block | 23–24 | Not started |
| 5 Lasso compatibility and migration | 25–29 | Not started |
| 6 Link health | 30–32 | Not started |
| 7 Click counts and where-used | 33–36 | Not started |
| 8 Hardening and documentation | 37–39 | Not started |
| 9 Staging rollout and cutover | 40–42 | Not started |

**Groups swept (2026-11-14, deviation 88):** `docs/features/F23/{requirements,analysis}.md` (R1-R24, D1-D13,
AC1-AC10) and **Tasks G1-G5** in the Phase 2 file, after Task 17. Nothing in it needed the user's answer (no part of
Lasso dropped; the post scan on remove is the standing no-rewrite rule). **Waiting for gate 4**; then G1.

**Staging build for the client (2026-11-13, deviation 87):** `app/affibloom-staging-review.zip` with a one-time
"Import from Lasso" button on the Dashboard (`447bf1b`). The user uploads it to https://staging.volleyballvault.com/.
Phase 2's next step below is unchanged.

**Task 17 swept (deviation 82): superseded by 17A-17D**, F1 requirements section 11 (X1-X11, D33-D43, AC4 rewritten,
AC21-AC23) and analysis "Deleting". The user answered the two questions that drop or reverse something: **D33 "Lasso's
split"** (the product page deletes for good, the Dashboard's selection bar trashes) and **D34 "Block only"** (Task 36
blocks a product in use; Lasso's force delete rewrites posts and is not built). **Gate 4 given 2026-11-13** ("Approve,
start 17A"). **17A done (`d7a0493`, deviation 83); 17B done (`f61e12c`, deviation 84); 17C done (`c86378f`, deviation 85);
17D done (`82b6015`, deviation 86): **Task 17 is complete.** Next in Phase 2: **area 0 of
`plans/archive/dashboard-completion-plan.md`, the Groups foundation (F23)**, which starts with its gates 1-2 (a sweep of
Lasso's Groups) and owes the Groups field on the product page and in the quick edit. **The user is handling** ("I'll handle it", 2026-11-13):
`affibloom.php` was changed outside Claude's sessions (requirements raised to WP 7.1.4 / PHP 8.3, reformatted;
lint fails on it, 31 errors), and a page of ten test products was bulk-deleted on the Dashboard at 20:43:18 while
Claude's run was going (deviation 86). **Local needs a reset** before the user's next full run (test products left: the library shows 47). Also found: 13C's Select All across pages is not Lasso's (17B corrects it, D36), and 15C's retire puts
products in the trash, which WordPress empties after 30 days (17C gives them `affibloom_retired`, D41).
The lines below are history.

**Task 15 swept (deviation 70): superseded by 15A-15D, gate 4 given 2026-11-13. 15A done (`59ee8da`, deviation 71).**
**15B done (`7e77a89`, deviation 72)** with two test fixes (`bae3c86`, `4e3b939`). **15C done (`0da4b16`, deviation 73).**
**15D done (`b3202d7`, deviation 74); F1 AC16 passed (the user, 2026-11-13). Task 15 is complete.**
**Next: Task 16** (Dashboard quick edit), then 17; both predate the full-scope rule, so each starts with a sweep.
**Task 16 swept (deviation 76): superseded by 16A-16C** (F1 requirements section 10, analysis "Dashboard quick edit").
**Gate 4 given 2026-11-13** ("Approve, start 16A"); D21 (a duplicate link from quick edit opens 15C's offer, where Lasso's
Dashboard wrongly says "saved") stands as written. **16A done (`852cb00`, deviation 78); redesign `bf282a7` (deviation 79); 16B done (`674c24c`, deviation 80); 16C done (`6471087`, deviation 81): Task 16 complete.** Next: Task 17 (deleting a
product), which predates the full-scope rule, so it starts with a sweep.
**The user's F1 runs (2026-11-13):** the full run, 47 passed and 11 failed, all explained
(two test faults and a test race, fixed; AC4 and F10-F12 wait for their tasks); the rerun of F1 AC2, AC13, AC14, AC6 and
F16 AC6, 5 passed; **F1 AC15 passed** (the user, 2026-11-13). **F1 AC16 passed.** Reset Local before the next run. **Open
item from the sweep:** Affibloom's Settings tabs have no unsaved-changes guard (Lasso's do; U7) — for the General
settings pass. The older lines below are history.

**Any number of buttons (F29, deviations 63-64): Task 14H done.** Next: 14B (the display card), then 14C, 14D, 15.
**Full acceptance run (deviation 65): 59 passed, 3 failed**; F19 AC9 and AC12 since fixed (`6a743ed`, deviation 66); F1 AC6 was a one-off. **14B, 14C and 14D done (deviations 67-69): Task 14 complete in Phase 2** (14E, the live preview, runs in Phase 3). **Next: Task 15, in a new session (the user, 2026-11-13).** Its text predates the full-scope rule, so start with a sweep
of Lasso's save path, as Tasks 11-14 did: `lasso_save_lasso_url` (`pages/url-details/class-ajax.php` and
`Lasso_Affiliate_Link::save_lasso_url`), the save progress dialog ("Updating Your Site"), the unsaved-changes dialog
(B8), the "This Product Already Exists" repoint offer (it rewrites posts: nothing Affibloom builds edits post content,
so ask), and the Amazon handling of the Primary and button URLs (F29 D5). Task 15 must also save everything 14A-14H
show: the buttons list and `primary_position` (F29), theme, image and `image_id`, badge, price and `show_price`,
description and price only when edited (14B), disclosure, the toggles, and Permalink once cloaking exists. Button icons (F28) are done: 14F and 14G (deviations 59-61). Then 14B.
**Also open:** F19 AC9 and AC12 fail on the code since 13D (deviation 58).
**Task 14 is now 14A-14E (deviation 57), gate 4 given 2026-11-13**: 14A done; 14B-14D in order (14E in Phase 3), then 15-17, then the Groups area and the rest of
`plans/archive/dashboard-completion-plan.md` (deviation 52). **The Dashboard UI is finished** (13C `b2ab7b3`, 13D `f32c89f`,
13E `00dc1af`; deviation 54), the admin is more spacious (`ee6743c`, deviation 55) and Add Link ends as Lasso's
(`6d9a5ef`, deviation 56). **The user's runs of F19 (14 tests), F18 and F2 are still to come** — F18 and F2 changed
their expectations. Task 14 also closes the last Add Link difference: Lasso opens a non-Amazon product's page. **Task 13 is complete:** 13A
`99a4fba` (deviation 49; the user's F19 run: 4 passed, 6 skipped — AC1-AC4 green) and 13B `ad508f4`
(deviation 50), checks green (lint, analyse, 275 unit tests), both checked by hand on Local. **The user's F19
run of all ten tests is still to come**, AC5-AC10 for the first time; the copy holds no products after the
user's reset, and five drafts (15126-15130) left by the 13B check, which the list does not show.
Gates 1-2 for the dashboard are `docs/features/F19/{requirements,analysis}.md` (`5d65ab7`), gate 4 given
2026-11-13 (`561b4fc`).
**Carried forward: Affibloom's own shortcode** (the user, 2026-11-13) needs a task in Phase 3 after Task 18, and
the Dashboard row's `Shortcode` button comes with the Actions cell in Task 14.
**Task 12 is complete:** 12A-12E done (`5dcfa7a`, `b1db8c5`, `a4b3c52`, `5d1d634`,
`e839339`; deviations 42-47); the user's F2 run, all eight passed. Then 14, 15, 16, 17 in order. The General settings pass owes F18's `check_duplicate_link` toggle and its off mode (deviation
36). **F18: all 10 acceptance tests passed** (the user's run, 2026-11-13, after `cbb2e06`); the Local reset that
follows is fixed (deviation 41) and the copy is back at the snapshot.

0. **SCOPE CHANGED 2026-11-13 (the user): everything Lasso does is in scope.** No feature, nested option, control
   or behaviour may be excluded or simplified without the user's explicit approval. Every feature must be planned
   with its complete functionality before it is built. The audit of what the plan missed is
   `reports/2026-11-13-lasso-full-scope-audit.md`; deviation 24 records what changes. **Task 10 is paused after
   Step 4** and the plan is being corrected first. The old "Next up" item below is superseded until then.
   **Sequencing: area by area** (the user, 2026-11-13). Area 1 is the **Display system**: gates 1-2 are written
   (`docs/features/F16/requirements.md` and `analysis.md` in the plugin repo), the phase file marks Task 10
   superseded, and Tasks 10A-10F are listed there **waiting for the user's approval at gate 4**. After Display:
   Amazon settings (10F is its first piece), then General settings, the product page, then the subsystems with
   no tasks at all (Tables, Groups, Fields, Opportunities, Import, history and revert, redirects and cloaking,
   auto-monetization, Analytics).
   **Gate 4 given by the user 2026-11-13** for the Display feature: Tasks 10A-10F are written in the phase file.
   10D (the live preview) runs in Phase 3 right after Task 18x, because it renders the real box template.
   **Next action: Task 10A**, Step 1. The F16 sweep also corrected the draft (control visibility rules, the
   Lists & Tables toggle writing two keys, `custom_css_default`, and that Lasso does sanitise Custom CSS through
   `format_css`).

1. ~~**Phase 2, Task 10** (Settings → Display and Amazon tabs), in the approved design system
   (`docs/ui/design-system.md`, Task 9A done; deviation 22): new components (text fields, colour inputs, field errors)
   come from the design doc, never from the plan's Lasso CSS. Each Display/Amazon section is its own card; text
   settings use the design's text-field and error specs. Apply the phase file's "Decisions from Task 6" and its ADR-8
   block for Task 10
   (button texts accept `<span class="…">` only) and deviation 20: put back the refused-keys loop in
   `SettingsService::update_tab()` and widen `clean()` with the new cleaners; add `Settings::string()` (its first
   caller); add the tab labels to `templates/admin/settings.php`'s `$labels`; use Lasso 370's Display fields, labels
   and order (`docs/spec/interface.md`), laid out and styled by the design system. Signed-in E2E runs are the user's (VS Code terminal, from the plugin folder, PowerShell
   5.1: no `&&`); Claude checks by WP-CLI and in the browser pane, where the user is signed in.
   Still open for the client (not blocking Phase 2): Creators API credentials (C1) and a GA4 check of Lasso's event
   names (C3); see the plugin's `docs/decisions/client-and-ui.md`.~~ (superseded by item 0)
3. Pending, no action unless the user brings it up: CI has never run (GitHub billing). The workflow file is on
   `main`, so pushes to `main` may create failed `startup_failure` runs on GitHub; that is expected.

## Task 1 — what was done (2026-11-12, claude.ai session)

- Commit `c7b808f` "Start the Affibloom plugin with its tooling" (author Rupashdas, no co-author line),
  pushed to `origin/main` on https://github.com/Rupashdas/Affibloom.
- Files: `affibloom.php`, `src/Plugin.php`, `composer.json`, `phpcs.xml.dist`, `phpstan.neon.dist`,
  `phpunit.xml.dist`, `package.json`, `package-lock.json`, `.wp-env.json`, `.gitignore`, `.editorconfig`,
  `CHANGELOG.md`, `tests/Unit/{bootstrap,TestCase,PluginTest}.php`.
- Verified in a Linux container (PHP 8.3, Composer platform 8.1): the unit test failed first
  (`Class "Affibloom\Plugin" not found`), then passed (`OK (1 test, 1 assertion)`); PHPCS clean;
  PHPStan level 6 `[OK] No errors` after the two fixes below. Tool versions resolved: PHPUnit 9.6.38,
  PHPStan 2.3.5, WPCS 3.4.1, PHPCSUtils 1.2.3, phpstan-wordpress 2.0.4, wordpress-stubs 7.1.2,
  Brain Monkey 2.7.0, Yoast polyfills 2.0.5.
- Not yet verified: activation on a real WordPress (Step 7).

## Deviations from the plan text (keep these in mind in later tasks)

1. **Repository location.** The plan says `D:\affibloom`. The repo is
   `wp-content/plugins/affibloom` inside this Local site, so the plugin runs next to the real Lasso.
   Wherever the plan says `D:\affibloom`, read this path.
2. **PHPStan fixes in Task 1** (the plan's code failed `composer analyse`):
   - `affibloom_requirements_notice()` got a `@return void` docblock instead of a `: void` return type, so
     `affibloom.php` stays parseable by old PHP and can show its notice.
   - `phpstan.neon.dist` has one `ignoreErrors` entry: identifier `booleanOr.leftAlwaysFalse`, path
     `affibloom.php`, `count: 1`, with a comment. PHPStan analyses as PHP 8.1+ (Composer platform), so it
     sees the runtime `PHP_VERSION` check as always false; the check exists for older servers and stays.
3. **wp-env was not run.** `package.json` and `.wp-env.json` are committed as written (CI uses them from
   Task 3), but the session that wrote them could not run Docker. Local activation (Step 7) replaces the
   wp-env check for Task 1.
4. **`composer.lock` is committed** (`8732ff4`, "Lock development dependency versions"). Its download URLs
   are Packagist's normal GitHub ones (no mirror), so it is safe for CI.
5. **`vendor/` now holds the full dev install** (PHPUnit, PHPCS, PHPStan, …) from `composer install` on this
   machine. The earlier runtime-only autoloader is gone. `vendor/` stays gitignored.
6. **Plugin header author.** The plan's `affibloom.php` says `Author: VolleyballVault`. The user changed it
   in `7102461` ("Renamed ownership") to `Author: Devrupash` and added `Author URI: https://devrupash.com`.
   Keep the user's header wherever later tasks show `affibloom.php`.
7. **Task 2 rewritten for Local (D1 = b, approved by the user 2026-11-12).** The phase file now holds the
   adapted text. In short: `tools/local-wp.sh` runs Local's bundled WP-CLI from any terminal (it puts
   PHP's `ImageMagick` folder on PATH, which fixes the cause of a `php_imagick.dll` startup warning on
   stdout); the safety constants go into the site's `wp-config.php` (`WP_HTTP_BLOCK_EXTERNAL`,
   `DISABLE_WP_CRON`, plus empty `SENTRY_DSN` and `LASSO_POSTHOG_API_KEY`, because Lasso's Sentry SDK
   sends over its own curl client, which the HTTP block does not cover); `reset-local.sh` imports a
   cleaned `.local/snapshot.sql` and refuses to run without those constants; Playwright defaults to
   `http://volleyballvault.local`. Dropped: `make-wp-env-override.mjs` and the production search-replace.
   Task 1 Step 7's check by hand moves into Task 2 Step 3. Knock-on in Task 3: the CI e2e job sets
   `WP_BASE_URL: http://localhost:8888`.
   During the task: `local-wp.sh` gained `MYSQL_TCP_PORT` (mysql otherwise tried port 3306); the
   production search-replace was not run (only two plugin options matched, no content); the smoke test and
   `vvadmin` creation were done by the user (Claude does not enter credentials on a `.local` host).
8. **`.gitattributes` added** (`* text=auto eol=lf`, commit `196f0f1`), not in the plan. Git on this machine
   has `core.autocrlf=true` system-wide, so checkouts would get CRLF, which breaks `tools/*.sh` under bash
   and PHPCS's line-ending rule. It matches `.editorconfig`'s `end_of_line = lf`.
9. **Task 3 workflow fixes** (found before the first run, each confirmed on this machine):
   - The `integration-and-e2e` job checks out into `path: affibloom` with `working-directory: affibloom`.
     wp-env names the plugin folder `path.basename(<checkout>)` (`@wordpress/env/lib/config/parse-source-string.js`),
     which on GitHub is `Affibloom`; Linux is case-sensitive, so `--env-cwd=wp-content/plugins/affibloom`
     and `affibloom/affibloom.php` would miss. `cache-dependency-path` and the report path follow.
   - `--pass-with-no-tests` on the CI Playwright run: every E2E test so far is `@local`, and Playwright
     exits 1 on "No tests found" (checked locally).
   - `WP_BASE_URL: http://localhost:8888` (deviation 7).
   - `npm run test:integration` cannot run on this machine (no Docker, D1 = b); its first run is in CI.
   - Actions kept at the plan's `@v4` (they run on Node 20; current majors are v7). Change only if CI
     warns or fails on them.
   - **Branch protection (Step 4) not set up:** GitHub answers "Upgrade to GitHub Pro or make this
     repository public" for this private repo on a Free account. The user chose D3 (c): the branch + PR +
     green CI routine is kept by discipline instead.
10. **Simple workflow, no CI (the user's decision, 2026-11-12).** GitHub Actions is blocked by the account's
    billing ("recent account payments have failed or your spending limit needs to be increased"), and the
    user does not want to spend more time on CI. PR #1 was merged on the local checks (lint, analyse, unit,
    smoke): `main` fast-forwarded to `77492e2`. The integration suite (`tests/Integration`, needs wp-env) has
    never run. From now on: develop locally, run the local checks, commit on `main` (one commit per task),
    push to GitHub as backup. No pull requests, no CI work, no new infrastructure. The checklist in
    `.github/pull_request_template.md` is used as a self-review in each task summary. Every later task's
    branch, pull request and CI steps in the plan are skipped; integration tests the plan adds are written
    but only run if a working wp-env/CI exists. `CLAUDE.md` and the `/next-task` command say this.

11. **Task 4 (inventory), changes to the plan's code and figures** (details in the plugin's `tools/README.md`):
    - Entry point is `bash tools/local-wp.sh eval-file tools/inventory.php` (D1 = b), not `npm run wp -- …`.
    - **Secret filter widened** to `…|access_key|jwt|^site_id$`. The plan's regex left Lasso's Amazon access
      key ID, its cloud site ID and an Intercom JWT carrying the account owner's email in `settings.json`.
    - The summary classifies a record as Amazon by `url_details.product_type`, and prints records with no link
      and a cached-data line. The plan's code counted only records with a cached Amazon row (226/37).
    - Two alignment fixes by `phpcbf`. `tools/` is linted, not analysed (the plan adds it to PHPCS only).
    - **Figures differ from the plan:** 233 Amazon / 21 other / **9 records with no link** (14837–14845:
      title only, no Lasso data), not 242/21; 2 not published, not 1; **676 links tied to a record in 39 posts,
      not 1,504.** 1,504 is not in the analysis (which says 3,530 stamped links on 147 URLs = the
      `data-lasso-id` stamp: 2,667 links on 147 posts here). Phase 7 (Tasks 33, 35) quotes 1,504: use the
      inventory's 676. Tasks 25–26 must skip and report the 9 empty records; 7 Amazon records have no cached
      Amazon row.
    - **Task 27's attribute map gained `title_url` and `title_type`** (in use on 4 and 1 boxes), as the plan
      requires. `title_type="Best Lightweight"` on one box is an editing mistake; ask at gate 4.

12. **`tools/local-wp.sh` reads the MySQL port from Local's `my.cnf`** instead of the Task 2 default `10005`
    (phase-0 plan, Task 2 code). Local moved the site to 10006 after a restart and `reset-local.sh` failed before
    touching the database. Separate commit, not part of a plan task.

13. **Task 5 (baselines), changes to the plan** (details in the plugin's `tools/README.md`):
    - `baselines.config.ts`: the plan's config failed ("Cannot find module ./tests/E2E/global-setup.ts": Playwright
      resolves `globalSetup` against `tools/`). Final config has no global setup and no stored login.
    - Guides captured **signed out** (no admin bar); trackers aborted (GA4, Lasso cloud, PostHog, Sentry); time
      limit 60 min (the plan's 10 min ran out after 51 of 68 guides; a run takes 16 min).
    - **Admin screens from production, view only** (Lasso is unlicensed on Local and its page showed the licence
      key): 19 JPEG views at 1920 px via Claude in Chrome in the user's Brave, licence field and user name hidden by
      CSS, production checked unchanged before/after. The scripted admin test was removed. Not captured: editor
      modal, toast, duplicate notice (wording from source).
    - **Guide PNGs (287 MB) are not in git** (the user's decision): `.gitignore` has `/tests/fixtures/baselines/guides/`.
      Task 29 needs them: run the capture first.

14. **Client points 1–3 are in scope** (the user, 2026-11-12: "if Lasso supports them, include all three").
    Investigated in Lasso 369's source, the live Lasso 370 screens and the Local data; written up in the plugin's
    `docs/features/C1…C3/`. All three are on and in use on the live site: Amazon data through Lasso's cloud
    (`lasso.link`; Creators API credentials empty), 16 auto-detected links since 2024, GA click events (Lasso's ID
    `371244810` is wrong; events most likely reach the site's GA4 `G-K7T3M99G7Q` via Headers & Footers). Updated:
    `00-overview.md` (decisions, C1–C3 outlines, task index), `roadmap.md` (1.6, U3/U7 findings, new **U8**, items
    in phases 2 and 7, the 7.2 "as in Lasso" correction), `feature-plan.md` (features 13–15, Amazon count 233).
    **The Claude Docs originals of the roadmap and the feature plan are now behind these local copies.**
15. **Task 7 (acceptance suite), changes to the plan:**
    - `tests/E2E/utils.ts`: `wp( ...args )` runs `tools/local-wp.sh` through `execFileSync` (no shell string, no
      wp-env) and refuses arguments Git Bash would rewrite (`environment.md`). Added `blockTrackers()` (Local pages
      carry the site's real GA4 tag), `boxBlock()`, `setSettings()` (merges), `unique()`: each has several callers.
    - Specs for C1–C3 as well: 50 tests in 15 files. `test.fixme`: F10 AC2 (Task 29), C1 AC1 and AC4 (need C1's
      recorded-response stub).
    - Every test that asserts an absence first checks the feature exists, so none can pass before it is built.
    - Labels follow `docs/spec/interface.md` (`Edit`, `Delete This Link`, `Search URLs`, …), which gained the
      Dashboard cell selectors (Alert, Locations, Clicks).
    - `playwright.config.ts`: `snapshotPathTemplate` points at `tests/fixtures/baselines/` (checked with a throwaway
      probe: it compares against Lasso's 768×534 Amazon box; the baseline stayed unchanged). Never run
      `--update-snapshots`: it would overwrite Lasso's baselines.
    - F11's stub links use the site's own URL, not `http://localhost` (Local, D1 = b).
    - The project has no TypeScript compiler; the specs load (`--list`) but are not type-checked.

16. **Task 6 decisions (2026-11-12), made under the user's delegation** ("resolve them from the project records, then
    Lasso research; follow requirements and Lasso for UI/UX"). Full reasons: plugin `docs/decisions/task-6-decisions.md`.
    Administrators only (`PRODUCTS`/`SETTINGS` = `manage_options`, `INSERT_BOXES` = `edit_posts`; live site: 4 admins,
    no other roles); no `allow_duplicates` (same link always refused = Lasso with the live toggle on); per-product
    Sponsored; delete on the product page; icon `<span class>` kept in button/badge texts; drafts count as locations;
    `title_type` other than h2–h6 ignored; C2 only Impact and Post Affiliate Pro; tolerance 0.01; Groups stay out.
    U1, U2, U3, U6 approved; U4, U5, U8 rejected; U7 parity. Each later phase file starts with a "Decisions from
    Task 6" block listing the tasks to change. Committed `6f15ea1`.

17. **Task 7 verification.** The user's signed-in run (2026-11-12): 47 failed, 3 skipped (the `test.fixme`s). Every
    failure shown is a missing admin page or label, a missing `wp affibloom` command or a box that does not render,
    except F4 AC1: its inserter search hit six of the site's synced patterns ("Product Box (Not Tested)" …), a
    strict-mode violation in the test; fixed with an exact name match (not re-run: it needs a sign-in). The first
    18 results of that run were overwritten by a signed-out run of Claude's that overlapped it; that run shows F10
    (missing command) and C3 (no box) fail for the right reason; C1, C2 and F1 stop at admin pages that do not
    exist, the same failure the signed-in run showed for F2, F3, F9 and F12. Nothing in `src/` registers a page,
    post type, block, shortcode, route or command yet (checked). Local reset afterwards.

18. **Amazon product data without credentials (the user, 2026-11-12; plugin decision 11).** Lasso's titles and images
    come from its licensed cloud reading Amazon pages, which Affibloom must not copy (Associates license). Adopted:
    migrate Lasso's fetched data (Task 26), a slug-based starting name for new Amazon products (Task 12, F2 AC5) and an
    editable Amazon Image link field (Task 14); C1 (Creators API, daily refresh for Amazon's 24-hour rules) is an upgrade
    once the client sends credentials, not a launch requirement.

19. **Task 8 (admin shell), changes to the plan's code:**
    - `Capabilities::PRODUCTS = 'manage_options'` (decision 1), not `edit_others_posts`. `SETTINGS` and `INSERT_BOXES`
      are added with their first callers (Tasks 9 and 23).
    - `Template::capture()` left out: no caller until the box (Task 18), which adds it (working method, rule 4).
    - `admin.css` uses the measured tokens (`docs/ui/tokens.md`) and Lasso's header rules (`.lasso-header` in
      `lasso-dashboard.css`), as Step 6 asks: **purple** header bar at least 100px tall with centred white bold tabs and a
      white glow on the active one, page `#F7F7FC`, text `#555`, a 1,050px centred column, 2.5rem/800 title. The plan's
      CSS had a white header with a purple underline. Our own rules, no Lasso CSS copied. Found in the look by hand:
      core's `.wrap h1` (23px/400) beat `.affibloom-page__title`, so the title rule is scoped as
      `.affibloom-admin .affibloom-page__title` (now 40px/800).
    - `phpcs.xml.dist`: `PHPCompatibility.Keywords.ForbiddenNames.interfaceFound` excluded for `src/Interface/*` only.
      PHP 8.0+ reads namespaced names as one token, so `Affibloom\Interface\…` is valid (PHP 8.2 `-l` clean, PHPStan clean,
      the page runs); PHPCompatibility 9.3.5, the latest stable release, predates that rule.
    - `Template::render()` carries one `phpcs:ignore` for `UnusedFunctionParameter`: `$view` is read by the included
      template, which the sniff cannot see.
    - Unit suite: `tests/Unit/bootstrap.php` defines `AFFIBLOOM_VERSION` (as `affibloom.php` does) and `PluginTest` stubs
      `plugin_dir_url()`; `boot()` now builds the admin modules. A second test checks the `admin_menu` and
      `admin_enqueue_scripts` wiring.
    - Red step checked through WP-CLI (`get_plugin_page_hook( 'affibloom' )` was `false` for an administrator): the
      Playwright run landed on the login page because the saved session had expired, and Claude does not sign in on `.local`.

20. **Task 9 (2026-11-12), changes to the plan's code:**
    - Red: the user's signed-in F9 AC3 run timed out on "Sorry, you are not allowed to access this page." (no
      `affibloom-settings` page), matching the WP-CLI check.
    - Between Steps 2 and 4 the Local site showed a critical error (`AdminMenu` needed `SettingsScreen` before
      `Plugin::modules()` passed it). Expected mid-task; it boots again since Step 4 (front page 200).
    - No `allow_duplicates` in `Settings::DEFAULTS` or `SettingsService::FIELDS` (decision 2).
    - `Settings::string()` left out: no caller until Task 10's text settings, which adds it (rule 4).
    - **`SettingsService::clean()` returns `bool`, and `update_tab()` returns `array()`** without the plan's null check.
      PHPStan (`return.unusedType`): with only the `bool` cleaner, `clean()` never returns a string or null. **Task 10**
      widens `clean()` to `bool|string|null` with its `text`/`color`/`amazon_tag` cleaners and puts back the plan's
      refused-keys loop (`if ( null === $value ) { $refused[] = $key; continue; }`, `return $refused;`).
    - **Templates follow Lasso's Settings layout** (baseline `settings-general-*.jpg`): the H1 is the tab's name
      ("General"), the General/Display/Amazon tabs sit right of it in purple, the card holds the sections, and "Save
      Changes" sits under the card on the right. The plan had H1 "Settings", tabs below, Save inside the card.
    - **No "Allow duplicate destinations" toggle** (decision 2). Help texts and order are Lasso's
      (`admin/views/settings/general.php`).
    - **Help tip (U3) is a `<button>` plus a `role="tooltip"` text with an id**, which the toggle also references with
      `aria-describedby`; the plan's `role="img"` + `aria-label` span would put the help sentence in an `aria-label`,
      which Playwright's `getByLabel( 'Sponsored' )` also matches ("…set to sponsored."), a strict-mode failure in F9
      AC3. The help partial now takes `{id, text}`.
    - **CSS from Lasso's measured rules** (`lasso-dashboard.css`): toggle 50×25 with a 17px knob; buttons radius
      0.5rem (not the plan's pill), weight 800, shadow, 3px drop on hover; tooltip purple, 235px, weight 800. The
      checkbox lies over the track (so clicks hit it) and is scoped `.affibloom-admin .affibloom-toggle__input` to
      outrank core's `input[type=checkbox]`. Token names are Task 8's measured ones (`--affibloom-text`, `-border`,
      `-tint`, `-dark-green`, new `-light-purple`), not the plan's `-ink`, `-border-light`, `-purple-tint`.
    - Commit message without the plan's "Invalid values keep their old setting." (not true until Task 10's cleaners).

21. **Admin visual design: Affibloom's own (the user, 2026-11-12; ADR-8).** The user decided Affibloom stays identical
    to Lasso in features and functionality but gets its own visual identity. Settled with the user the same day:
    (1) **admin only**: the reader-facing product box keeps the site's design and the pixel checks against Lasso (ADR-6,
    Phases 3 and 5 unchanged); (2) **Lasso's labels, help texts, field/tab/column order and workflow steps stay** (the
    interface spec and acceptance tests are unchanged); colours, typography, layout and styling become Affibloom's;
    (3) **a design task first**: new **Task 9A** (phase 2) proposes `docs/ui/design-system.md` with a preview, the user
    approves it, then Tasks 8–9 are restyled, before Task 10. Documents changed (no code): `plans/00-overview.md`
    (goal, ADR-7 row, new ADR-8 row, task index), `phase-2` (ADR-8 block, Task 9A, look-comparison steps in Tasks 10,
    13, 14, 17), `phase-3`, `phase-4`, `phase-6`, `phase-7`, `phase-8` (ADR-8 notes), `reference/roadmap.md` (UI and UX
    parity section, design reference, 2.0 items, 8.7, quality and test-level lines, decisions table),
    `reference/feature-plan.md` (Build line), `CLAUDE.md`; in the plugin repo `docs/decisions/ADR-008.md` (new),
    `ADR-007.md`, `client-and-ui.md`, `task-6-decisions.md`, `docs/ui/tokens.md` (now Lasso reference only),
    `docs/ui/screen-inventory.md`, `docs/spec/interface.md`, `.github/pull_request_template.md`. Tasks 8 and 9 (`aa4da3c`,
    `8035d66`) stay in Lasso's look until Task 9A. **The Claude Docs originals of the roadmap and feature plan are
    further behind these local copies.**

22. **Task 9A (2026-11-13), how it was done:**
    - Design system proposed with a preview (raspberry pink brand); the user asked for another brand colour and chose
      **ocean blue `#1E5BB8`** from three AA-checked options; the info status moved to cyan `#0E7090` so it is not
      mistaken for the brand. Approved 2026-11-13; `docs/ui/design-system.md` is the source of every admin style.
      Brand tokens are named `--affibloom-brand-*` (not by hue).
    - `admin.css` rewritten from the design system. It holds only the components the two screens use (header, page,
      tabs, card, empty state, settings row, toggle, primary button, toast); secondary/danger buttons, help tip,
      fields, tables, modals and counters are added from the design doc by the tasks that first use them (rule 4).
    - Settings layout: H1 is now **"Settings"** with General/Display/Amazon as tabs below it; one card per section with
      Save in the card footer. Each setting is a row: label, Lasso's help text **shown as a description** and linked
      with `aria-describedby` (U3), switch on the right. `partials/toggle.php` renders that row; the description id is
      `<id>-desc`.
    - **`partials/help.php` removed:** with descriptions shown, nothing calls it. Task 14 (product form) builds the "?"
      tooltip from the design system's Help tip spec; the old version is in `8035d66`.
    - Header: our five-petal logo mark (inline SVG, brand colours via CSS classes), white bar, tabs left.
    - Red: the user's `admin-design` run failed on the purple header and on the missing "Settings" heading.
    - Looked at 1920, 1280 and 782 px in the browser pane: matches the preview; no sideways scroll at 782 px.

23. **Aurora replaces the first design (the user, 2026-11-13).** After `6ec0f2e` the user found the plain design too
    basic and asked for four richer style directions (all ocean blue, AA-checked, one preview page): A Aurora,
    B Midnight (dark), C Editorial (serif, paper), D Bento (side nav, tiles). **The user chose A — Aurora.**
    `docs/ui/design-system.md` rewritten for it: navy `#0B2A5B` header with icon-plus-label navigation, a navy-to-blue
    gradient band holding the title, a subtitle and segmented section tabs, white cards with soft shadows floating 48px
    over the band, icon chips on every setting, gradient primary button and switch, dark toast. Two content additions
    approved with it: **U9** Dashboard overview tiles (Products: Task 13, Broken links: Task 31, Clicks (30 days):
    Task 34) and **U10** a one-line subtitle per page (no eyebrow). Recorded in the roadmap register, the plugin's
    `client-and-ui.md`, the interface spec and the phase 2/6/7 decision blocks.
    Code: `admin.css` rewritten; new `partials/icon.php` (Affibloom's outline icons, printed through `wp_kses` with a
    `path`/`circle` allowlist instead of a `phpcs:ignore`); header, dashboard, settings, toggle row (icon chip) and
    toast templates restyled; `admin-design.spec.ts` rewritten first (red on the user's run: white header where navy
    was expected). Looked at 1440 and 782 px; no sideways scroll. The user's runs: `admin-shell` + `admin-design`
    4 passed, F9 AC3 1 passed.
    **Then centred, like Lasso (the user, 2026-11-13):** the band's content and the body share one centred column of at
    most 1080px (`--affibloom-content-width`); cards fill it (the 820px form cap and `affibloom-card--form` are gone).
    `admin-design` gained the check first (card ≤ 1080px, centred under the band, title aligned with it); the same
    measurement on the left-aligned page showed the card 110px off centre (red), and 0 after the change at 1280, 1920
    and 782 px.

24. **Full Lasso scope (the user, 2026-11-13).** The user rejected the exclusions in the feature plan: *"We must
    include every customization feature available in Lasso, without excluding or simplifying anything. All these
    features are already being used on the live site... For every feature we implement, account for its complete
    functionality, including all nested options, settings, controls, behaviors, and edge cases... Before
    implementation, verify the complete feature scope against Lasso's actual functionality and ensure our plan
    covers everything. If anything is missing, update the plan first."*
    - **Why the first scope was wrong:** the exclusions were argued from the Task 4 block inventory (no box carries
      a `type` or `theme` attribute). Display type, theme, layout, density, typography and element visibility are
      **settings**, not block attributes, so that instrument could not see them. The live `lasso_settings` option
      holds full Single, Grid and List configurations.
    - **Audited 2026-11-13** against Lasso 369's source, the live settings (95 keys) and the production
      screenshots: `reports/2026-11-13-lasso-full-scope-audit.md`. Settings → Display is a visual editor with
      ~50 controls per display type (theme, density presets, 11 element toggles, 8 layout controls, a phone-only
      layout override, 3 spacing steppers, 6 typography controls, image height, 10 colours and labels, plus
      disclosure/behaviour and Custom CSS); General has ~30 controls, Amazon ~20; the product page adds Groups,
      Custom Fields, per-product Display Theme, Price and a rich-text description; and Tables, Groups, Fields,
      Opportunities, Import, Content history and revert, Redirects and cloaking, Auto-monetization and Analytics
      have no tasks at all.
    - **Consequences:** `feature-plan.md`'s "Excluded features" table is withdrawn; gates 1–2 are re-run per
      subsystem before tasks are written; phases 2–5 and 7 are rewritten and new phases added; the roadmap's phase
      list and gate schedule change. The plan grows to roughly three times its current size.
    - **Still open:** the sequencing choice (breadth first vs area by area) and the decisions in the report's
      "things that cannot simply be rebuilt" section (Lasso's cloud and business screens, Amazon data without
      credentials, third-party integrations, AI text, switching cloaking on).

25. **ADR-8 amended (the user, 2026-11-13): Lasso's structure, Affibloom's colours and type.** For the Display
    tab the user asked for the columns, layout and the presentation of the settings and the preview to be
    **exactly like Lasso's**: the split card with the live preview on the left (its own toolbar above it) and the
    380px settings rail on the right, with the theme row, the underline tabs and the accordions in it, and the
    label-left / control-right rows. **Only the colour scheme and the typography stay Affibloom's** (Aurora
    tokens, the design system's type scale). This narrows the earlier ADR-8 wording, which made layout ours too.
    - Applies to the Display screen now; whether the other admin screens follow Lasso's layout as well is open
      and should be asked before they are built or restyled.
    - Lasso's measurements, for the record: split card `min-height: calc(100vh - 280px)`; toolbar 52px with the
      display-type segment left and the device segment right; preview stage centred, frame `max-width: 1280px`;
      rail fixed 380px with a left border; rail header `12px 20px 0` with a bottom border, theme select 160px;
      tabs flex with a 2px underline on the active one; field rows flex `space-between`, control height 34px.
    - `docs/ui/design-system.md` (plugin repo) and the phase-2 ADR-8 block carry the amendment.

26. **Task 10A (2026-11-13), changes to the plan's code:**
    - **Lasso's defaults, not the plan's.** The default theme is **Cactus** (not `Lab`, which is this site's choice)
      and the palette defaults are `black`, `black`, `white`, `#22BAA0`, `#22BAA0`, `white`, `#22BAA0`, `#E06470`,
      `Buy Now`, **`Our Review`**, `` (`admin/lasso-constant.php` 179-224). `F16/requirements.md` was corrected.
    - **The palette does not clean its own values.** The task text had `Palette::from_array()` run the button
      labels through the icon allowlist, but `wp_kses` is a WordPress call and the Domain layer may not make one.
      Cleaning lives in `DisplaySettingsService` (`clean_colour`, `clean_label`), which also accepts Lasso's CSS
      keywords (`black`, `white`) beside hex and refuses anything else.
    - **Colour fields are a text value plus a colour picker** (`assets/admin/display-settings.js`), not an
      `<input type="color">` alone: a colour input cannot hold `black`/`white` and would rewrite them on the first
      save. The text field is what is submitted; the picker is unnamed, keyboard reachable and synced both ways.
      Saved hex is lower-cased by the cleaner (open with the user).
    - **New option `affibloom_display`** (autoloaded) holds the three configurations; the per-type colours and
      button texts left `Settings::DEFAULTS`/`FIELDS`, which left the `color` and `icon_text` cleaners without
      callers, so both were removed with their two unit tests (the behaviour is covered by
      `DisplaySettingsServiceTest`).
    - **Extracted on their second use:** `templates/admin/partials/settings-band.php` and
      `Interface\Admin\SettingsNotice`.
    - **Structure is Lasso's** (deviation 25): split card, 52px toolbar with the type segment, centred preview
      stage (an empty state until Task 10D), 380px rail with the theme row, underline tabs and
      label-left / control-right rows, stacking below 992px as Lasso does. The rail uses the design system's 13px
      small scale because labels wrapped at 14px.
    - The preview frame is empty until Task 10D (Phase 3): it renders the real box template, which Task 18x builds.

27. **Palette defaults are hex (the user, 2026-11-13).** Lasso writes `black`/`white` for four of its default
    colours; Affibloom stores `#000000`/`#ffffff` (and lower-case hex for the rest), so the picker and the stored
    value always agree. Keywords stay valid input and migrate unchanged. Separate commit.

28. **Task 10B (2026-11-13), changes to the plan's text, from reading Lasso's code:**
    - **Density follows Lasso's real rules**, which the task text had wrong: an edit is **not snapped** to the
      step (Lasso's stepper adds the step and only clamps); a **named preset ignores stored values** and means its
      own map (`normalize_display_density_values`); **custom values are stored unclamped**; on save only the values
      that differ from the starting preset are treated as edits and clamped, so spacious's image height 320
      (above the stepper's 280) survives a save that touched something else. A hidden `base` field tells the
      server which preset an edit started from.
    - The instance getter is `preset_name()`: PHP cannot have a static `preset()` and an instance `preset()`.
    - **The Disclosure row of the Elements panel moves to Task 10E**: it writes the site-wide `show_disclosure*`
      keys, which 10E adds. The panel reads "n of 10 visible" until then (Lasso: 11).
    - **The rail has Lasso's two tabs**, Layout & spacing open first; the open tab survives a save and a type
      switch (`panel` in the query), because our save reloads where Lasso's does not.
    - **Every control works without JavaScript**: chips and line clamps are radios, steppers post hidden inputs,
      accordions are `<details>`, both panels show; `display-settings.js` adds Lasso's interactions (chip fills
      the steppers, stepper clamps and makes the preset custom, live summaries).
    - Summaries use Lasso's wording: "n of m visible", "gap / padding px", "size px title", "height px".

29. **Task 10C (2026-11-13), changes to the plan's text, from reading Lasso's layout code:**
    - **Phones do not simply follow desktop.** The phone base is desktop's six keys with **Button alignment `center`**
      and Grid `stacked` (`Helper::get_display_layout_mobile_base`); Lasso's own help text skips this, the behaviour
      is kept. Requirements 2.4 corrected.
    - **Grid's orientation is pinned to `stacked` on the server**, not only hidden in the UI; Grid has no phone
      orientation; an image-position phone override is kept in the phone orientation's vocabulary.
    - Every desktop key is validated (Lasso's server checks only orientation, image position and button
      alignment); unknown values take the type's default. Recorded as edge case 10.
    - Legacy flat layout and Lasso's "old factory" column rule recorded as edge case 11, for migration (Task 26).
    - **The device toggle (Desktop / Mobile) is built now**, not in 10D: the phone layout cannot be edited without
      it. In 10D it also sizes the preview.
    - `DisplayConfig::defaults()` and `from_array()` take the display type (Grid's layout defaults differ);
      `columns` lives at the top of `affibloom_display`, clamped 1-3, as in Lasso.
    - The line-clamp option group became the generic `affibloom-options` on its second use (the layout rows);
      separators come from a 1px gap so a hidden image-position pair leaves no stray border, and
      `.affibloom-admin [hidden]` always hides, whatever `display` a component sets.
    - The radios post the desktop layout, so the form works without JavaScript; in Mobile mode the script shows
      the phone layout in the same rows, writes overrides to hidden fields, and puts the desktop layout back in
      the radios before the form is sent (checked by saving from Mobile mode).
    - Found in passing and recorded for 10E as requirement **B5a**: the Price element's default follows Show Price.

30. **Task 10E (2026-11-13), changes to the plan's text, from reading Lasso's code:**
    - **Lasso's defaults:** Show Price is **on** by default (the task text said off); the other display keys match
      `admin/lasso-constant.php`. Brag mode URL starts **empty** (Lasso's default is its own affiliate sign-up page;
      what the brag icon shows is still an open decision from the audit).
    - **Reset Settings resets exactly Lasso's list** (`Setting::get_display_tab_setting_keys`, 28 keys), after Lasso's
      confirmation text. It includes `show_disclosure`, which is also General's "Show Disclosure", as in Lasso.
      The button appears only with JavaScript, so a reset is never sent without the question; it submits a form of
      its own (nonce field `_affibloom_reset_nonce`, since the page holds two nonces).
    - **Save and Reset sit at the foot of the rail**, as Lasso has them, not in a card footer; Save stays disabled
      until something changes (steppers, chips and the phone reset announce themselves with `affibloom:change`).
    - **B5a built:** Price follows Show Price, for every type, until the Elements panel is saved with it; `Elements`
      leaves an unsaved Price out of storage, and `DisplaySettingsService` reads Show Price (it now takes the
      settings repository).
    - **The Disclosure row** is the Elements panel's eleventh row, between Secondary button and Timestamp; it
      mirrors the Disclosure & behavior toggle of the type being edited (shown, read-only, without JavaScript). One
      "Lists & Tables" toggle writes both `show_disclosure_list` and `show_disclosure_table` (controller).
    - **Custom CSS** uses Lasso's own character allowlist, copied byte for byte from `Helper::format_css`. The
      `CustomCss` printing class listed in the task waits for Phase 3, where the box is its first caller; so does the
      shared confirm-dialog partial (Lasso uses the browser's own confirm for Reset).
    - **Help texts that name Lasso** say the same without the product name; the Brag mode URL help describes the
      field, and Enable Brag Mode has no help, as in Lasso.
    - The Colors & labels panel is Lasso's three accordions (Colors & labels, Disclosure & behavior, Custom CSS);
      accordion chevrons always sit on the right.

31. **Help tips float (2026-11-13, `3412dbd`).** The user's run: AC4, AC6, AC7 timed out because the radios lie
    over their option text (Playwright refuses a click another element receives) — the tests now act on the radios
    by their label; the rail measured 381px — now `box-sizing: border-box`, 380px with its border as Lasso's. The
    user then found **no help tip working**: the rail's rows are not `.affibloom-field`, so since 10A's anchoring
    fix their tips anchored to a distant ancestor (opening off-screen at y -10) and the rail's scroll area and the
    card's clip hid them. Tips now float against the viewport above the "?" (below when there is no room), as
    Lasso's rail does ("floating JS tooltips"), stay while hovered, close on Escape, scroll and resize; hidden tips
    take no space. The floating code sits in `display-settings.js`, the only screen with help tips; it moves to a
    shared admin script with the second screen that has them (Task 14).

32. **Amazon tab decisions (the user, 2026-11-13):** (1) the Amazon tab also takes **Lasso's structure** — two columns
    of cards (left: Amazon Associates Accounts, then C1's Product Data API; right: Auto-Monetize Amazon Links, Amazon
    Preferences), switches left of their labels with "?" help tips, Save at the bottom right — in Affibloom's colours
    and type; (2) an invalid tracking ID is **refused** (the previous ID is kept, "This is an invalid Tracking ID"
    shows), not saved-and-flagged as Lasso does. F17 B6 is confirmed. The General tab keeps the Aurora layout until its
    own full-Lasso pass.

33. **Task 10F (2026-11-13), the Amazon tab in full**, written from a sweep of Lasso's view, save handler and
    defaults (`docs/features/F17/requirements.md`, gate 1) rather than the task's summary, which it corrected:
    - **Lasso's tracking-ID rule** `^[a-zA-Z0-9-_.]+-\d{2,3}$` (case-insensitive) replaces the plan's stricter one, so
      `my_tag.us-21` and `tag-123` are valid; an invalid ID is **refused** (the user's decision, deviation 32), with
      "This is an invalid Tracking ID" while typing and "That was not a valid Tracking ID, so the previous one was
      kept." after a refused save (the field then shows the kept ID, so Lasso's sentence alone would contradict it).
    - **Out-of-stock days take 0-30** (Lasso's field), not 0-365; **Update Amazon pricing daily defaults on** (Lasso's
      key is `amazon_update_pricing_hourly`).
    - **The whitelist is a sorted set**, shown as removable tags (typed or pasted, separated by spaces or commas;
      invalid IDs stay in the box, marked); without JavaScript it is a space-separated field. **Removed IDs go to
      `tracking_id_blacklist`** (Lasso's `amazon_tracking_id_blacklist`), recorded at save time because the removal
      cannot be reconstructed later; Lasso's slip of saving duplicates is not copied.
    - **A field that was not posted keeps its value**; only checkboxes use absence as "off". Found because the
      disabled whitelist (multiple IDs off) is not posted, which would otherwise show the partly-saved notice on
      every save. Lasso's save does the same (`$has_whitelist_field`).
    - `Settings` now holds whole numbers and lists (`int()`, `list()`), and keeps a stored value only when its type
      matches the default's.
    - **Lasso's structure** (deviation 32): two columns of cards, switches left of their labels with help tips, Save
      at the bottom right; the Product Data API card waits for C1, its first reader. Lasso's "Update it in your Lasso
      Account too" note has no equivalent (Lasso cloud).
    - **Extracted on their second use:** `assets/admin/admin.js` (floating help tips, Save disabled until a change,
      opted into with `data-save-when-changed`) and `templates/admin/partials/switch-row.php`. General keeps its
      always-enabled Save until its own full-Lasso pass.
    - Behaviours the settings switch (auto-monetization, link boosting, geni.us removal, price updates, deep links,
      local stores, the blacklist rewrite) are listed in F17 section 4 with the subsystem that owns each.

34. **Task 11 superseded by 11A-11E (2026-11-13), from a sweep of Lasso's add flow** (`docs/features/F18/`, gates
    1-2; the user chose "sweep then build"). Task 11 sits in **Phase 2**, not Phase 3 as first asked. The sweep read
    the whole 2,330-line `admin/views/modals/url-add.php`, the AJAX handler, `lasso_add_a_new_link`,
    `is_lasso_url_exist`, `add_https`, the bulk queue and the live settings. It contradicts the plan in five places:
    - **Lasso refuses only an empty box** ("URL is incorrect."). `add_https()` repairs a bare domain, so
      `mizunousa.com/wave` becomes a product; the plan's invalid-link test and F3 AC2 assert the opposite.
    - **Lasso does not refuse duplicates.** It re-publishes the existing product and opens its page with "This
      product already exists. Please update the Primary URL or add a new link." The setting `check_duplicate_link`
      (live: on) chooses pasted-URL or final-URL comparison. Task 6 decision 2 ("no setting, always refused")
      conflicts with the full-scope rule.
    - **Bulk add is ten links, through a background queue** with a 3-second progress poll, not 1-50 in one request
      (`docs/spec/interface.md` section 2).
    - **There is no toast** and no "1 product added." after an add: a single add opens the product page (or the
      dashboard for Amazon).
    - (Matches the plan:) a new link stores the General settings' nofollow/new-tab/sponsored at creation
      (`save_lasso_url` 1203 and 1421-1435), so the plan's `_affibloom_nofollow = '1'` assertion stands.
    Not in the plan at all: Shift+Enter, the counter, the preview list and pencil, "Continue Anyway", the "still
    processing" state, the Marketplace tab, the editor variant and the flip entry point.
    **Decisions (F18 requirements, section 13):** D1 (Claude) keep Lasso's repair, refuse an empty box and anything
    still not an `http(s)` URL; **D2 (the user) follow Lasso: the `check_duplicate_link` setting and the redirect to
    the existing product — Task 6 decision 2 is withdrawn**; D3 (Claude) Affibloom fetches the page's title and image
    itself, falling back to the domain; D3b (Claude) bulk add is a server-side queue; D4 settled by Lasso; **D5 (the
    user) Marketplace rebuilt as an Amazon search with C1, the flip entry point dropped**; D6 (Claude) the editor
    variant is built with the block in Phase 4. **Gate 4 given by the user 2026-11-13** for 11A-11E.

35. **Task 11A (2026-11-13, `597fd4a`), changes to the old Task 11 code it was cut from:**
    - **One link per request:** `POST /products { url }` → 201 `{ id, name, url, redirect_to }`, 422
      `affibloom_invalid_field` with `data.fields.url`. Lasso sends a single add and a bulk add down separate paths
      (the bulk one into a queue), so bulk gets its own route in 11C instead of `urls[1–50]`.
    - **Lasso's words and rules:** subtitle "Enter the affiliate link you would like to track." (Lasso 369, not the
      plan's older text); an empty box says "URL is incorrect." and sends nothing; a non-URL says "This is not a valid
      link." (D1); no length cap; the name is Lasso's `get_title_by_url` (`Pxf`, `Under Armour`), with a two-letter
      country suffix skipped and a one-label host kept, where Lasso gives "Co" or an empty name.
    - **Transitional until Task 14:** Lasso opens the new product's page. The route returns `redirect_to` = the
      Dashboard with the toast "Link added."; Task 14 changes only that URL. The Dashboard says "No products yet."
      until Task 13.
    - **Moved to the task that first reads them:** `_affibloom_url_key` and the lookup (11B), the link toggles (11E),
      `get()` (14), updating in `save()` (15), "Continue Anyway" (14, it opens the product page). `ProductType` has
      only `Other` until Task 12.
    - **The button sits in the header on every screen** (Lasso's place, design system §5) and the modal is printed
      after the header. At 782px and below it is icon-only, like the navigation: its label overflowed the header by
      31px at 375px and pushed the dialog off screen. `SettingsNotice` became `Notice` (its third screen).
    - **Commit slip:** the staged `git mv SettingsNotice.php Notice.php` went into the docs commit `13a01dc`, so that
      one commit does not load on its own; `597fd4a` completes it and `main` is correct. Not rewritten (pushed
      history; ask the user first).
    - Checks: lint, PHPStan, 77 unit tests green. WP-CLI through the REST server: Author 403, administrator 201 with
      the link byte-for-byte, empty and non-URL 422. Browser pane at 1440, 782 and 375px: focus, Tab trap, Escape,
      backdrop, both messages, a real add and the toast, Settings and Display screens untouched. Database backed up
      first (`app/affibloom-backup-2026-11-13-task11a.sql`); test products removed. Integration test written, not
      run (deviation 10); F18 acceptance run is the user's. **The user's run, 2026-11-13: 3 passed.**

36. **Task 11B (2026-11-13, `8f41d04`; F18 note `6121249`), from Lasso's lookup code** (`is_lasso_url_exist`,
    `get_lasso_post_id_by_url`, `Lasso_DB::get_url_details_by_url`):
    - **Lasso's repair** (`add_https`) in `AffiliateUrl::from_string()`: its eleven broken prefixes in its order,
      `https://` before a value with a dot and no scheme (after its leading slashes), e-mail, `mailto:` and `[…`
      left alone (so refused), a host containing `.html` refused (Lasso makes it a relative path). **Improvement:**
      Lasso only sees a lower-case `http(s)://`, so `HTTPS://x.com` and `ftp://x.com` would become
      `https://HTTPS://…` and `https://ftp://…`; any existing scheme now gets no prefix (and only http/https pass).
    - **The duplicate rule with `check_duplicate_link` on** (default and live): the stored link of **published**
      products, ignoring case (MySQL collation), one trailing slash and `&amp;`, as `_affibloom_url_key`. A trashed
      or draft product is not a match, so the plan's "restore on re-add" idea does not apply (Lasso's own lookup
      filters `post_status = 'publish'`).
    - **Moved, not dropped:** the **off** mode (final destination without its query string) is built with the
      General settings pass, with its toggle, its only way to be switched off. Lasso's other lookup branches (own
      cloaked links, the auto-monetize table, lasso.to) belong to their subsystems; Amazon "Sorry!" re-titling to
      Task 12. Recorded in F18 requirements section 5.
    - **Transitional until Task 14:** a duplicate answers 200 `duplicate: true` with `redirect_to` = the Dashboard
      with Lasso's sentence as an **info toast** (new variant: brand-300 circle, navy "i"; design system Toast row).
      Task 14 points it at the product page, where Lasso shows the warning.
    - Checks: lint, PHPStan, 90 unit tests. WP-CLI through the REST server: bare domain and `//host` repaired,
      `https://https://` repaired, `example.html` and `not a link` refused, the same link with capitals, a trailing
      slash or `&amp;` → 200 with the first product, a trashed product's link → a new product. Browser pane: the
      capitalised, slashed link through the dialog → the info toast, no second product. Test products removed.
      F18 AC2–AC4 written; the user's run pending.

37. **Tasks 11C-11E written in full (2026-11-13), at the user's prompt** ("listed but their details are not
    available"): they had only one-line entries, because 11A and 11B were each written just before they were
    built. Now each has Lasso's rules, the request path, the creates-and-caller table and the steps. Reading Lasso's
    request path for 11D corrected two things:
    - **F18 D3 (Claude's call): no image.** Lasso fetches no image for a non-Amazon link (only its cloud, and only
      for Amazon), so the first version's `og:image` is dropped. The name comes from an embedded destination's site,
      else the page `<title>` with Lasso's cleaning and placeholder list, else the site.
    - **11A's "Pxf" was only Lasso's last fallback.** For the site's Impact links Lasso uses the embedded
      destination's site ("Mizunousa"); F18 AC1's expected name changes in 11D.

    11D also takes `keep_genius_formatting` (from 10F; geni.us stored as its final URL when off). 11E copies the four
    General keys that exist; the secondary-button, opportunity, cloaking and boosting defaults join it with their
    settings. Committed `2f8a3fa` (F18 docs); the phase file is a local working copy.

38. **Task 11C (2026-11-13, `b89ae01`), bulk add,** as written (deviation 37), with these points from the build:
    - **The run** is one option (`affibloom_bulk_add`, not autoloaded) and one WP-Cron single event
      (`affibloom_bulk_add`), started with `spawn_cron()`.
      - **Seen working on this copy** despite `DISABLE_WP_CRON`: three links were done about 20 s after the start,
        with no visitor.
      - **The progress route wakes a waiting run;** a 20 s budget per pass.
      - **No lock of its own:** a link handled twice is a duplicate the second time.
    - **The first ten non-blank lines** make the run, so a junk line counts toward the ten and is then skipped
      silently, as in Lasso.
    - **Extracted on second use:** `Interface\Rest\FieldError` (InvalidField → 422), now shared by both
      controllers.
    - **PHPStan found a real gap:** `ProductRepository::save()` and `ProductService::create_from_url()` now declare
      `RuntimeException` (WordPress refusing the post), which the background pass skips.
    - **The textarea's hidden label** is "Links, one per line", not "URLs". "URL" is a substring of "URLs", and
      Playwright's `getByLabel( 'URL' )` in the F1, F3 and F18 specs would then match both fields.
    - **New design-system rows:** Menu and Progress bar. The header button is now Lasso's split button.
    - **Checks:**
      - lint, PHPStan, 103 unit tests.
      - **WP-CLI through the REST server:** 422 for blank lines, 202 with ten, 409 while running, the event adds
        them, progress 0 → 100.
      - **Browser pane at 1440, 782 and 375px:** the menu (Escape returns the focus), Shift + Enter both ways
        keeping the links, the counter, the preview and pencil, the confirmation (No sends nothing), a real
        three-link run ending on the Dashboard with "Bulk add finished.", the locked state with Reload.
      - **Throttled polling:** the pane runs as a background page when hidden, which delays the 3 s poll to about a
        minute. The handoff still happened.
      - **Cleanup:** test products and the run removed.
    - **Integration test** `BulkAddTest` written, not run (deviation 10). F18 AC6–AC9 await the user's run.
    - **Left in place:** products 15144–15147 ("F18 AC1"–"AC4", "Mizunousa") look like the user's own F18 run after
      11B, so they were not touched.

39. **Task 11D (2026-11-13), naming a new product,** as written (deviation 37), with these points from the build:
    - **Redirects followed by hand, stopping before Amazon** (not in Lasso, which lets `wp_remote_get` follow into
      Amazon's pages).
      - A short or geni.us link that redirects into Amazon is named "Amazon" without Amazon's page being requested
        (the Associates licence; overview, C1).
      - `WpPageFetcher` makes up to five hops and reads at most 1 MB; a title sits at the top.
      - It sends the editor's browser user agent, as Lasso does, and a browser-like one in a WP-Cron bulk run.
    - **Lasso's lists, copied exactly:**
      - **Amazon:** `get_domains()`, 22 hosts, into `AffiliateUrl::is_amazon()`.
      - **geni.us:** `is_geni_us()`, the base domain without the subdomain.
      - **Embedded destinations:** the network rules (ShareASale `urllink`, pntra/wordseed `url`, titan.fitness
        `redirect`, prf.hn `destination:`) and the generic first-link parameter, skipping `referrer`.
    - **The title** is Lasso's `<title>` cleaning (whitespace, "–", 200 characters including the "..."), with
      entities decoded and non-ASCII kept. Lasso's `remove_unexpected_characters` filter is not applied either: with
      non-ASCII kept it would turn a curly apostrophe into a space.
    - **Shared code:**
      - `AffiliateUrl::repair()` is now public, its second caller being `EmbeddedDestination`.
      - The unit fakes (`InMemoryProductRepository`, `FakePageFetcher`, `InMemorySettingsRepository`) moved to
        `tests/Unit/Fakes`, since the product repository fake had two copies.
    - **The expected Impact name is now "Mizunousa":** F18 AC1, the integration test and the unit test all expect
      it.
    - **The Local copy was found half-reset.** The user's `reset-local.sh` import had stopped after 61 of the
      snapshot's 141 tables, at `wp_rank_math_analytics_gsc`, leaving no users, terms or SEO tables. Route calls
      answered 401 and the E2E sign-in could not work. The snapshot is intact at that point; the most likely cause
      is an interrupted terminal or a time-out. Claude re-ran the documented reset to completion (exit 0, 142
      tables, five administrators including `vvadmin`). The browser pane's login did not survive it, and Claude
      does not sign in on a `.local` host.
    - **Checks:**
      - lint and PHPStan; 143 unit tests, with every naming rule run against a fake fetcher, so none touch the
        network.
      - **WP-CLI through the REST server, every outgoing request logged:**
        - Impact link → "Mizunousa", no request, the link stored as pasted
        - Amazon → "Amazon", no request
        - `www.example.com` → one request, refused by the external-HTTP block → "Example"
        - `http://volleyballvault.local/?p=14942` → the 301 followed by hand → the post's title
      - Test products removed.
    - **Not checked here:** the dialog in the browser pane (signed out; 11D changes nothing in it); the integration
      test (no wp-env); F18 (the user's run).

40. **Task 11E (2026-11-13, `f830fea`), the site's link defaults on a new product**, built as written (deviation 37):
    - **What `Product::draft()` copies:** New Window, NoFollow, Sponsored and Show Disclosure from the General
      settings, stored as `_affibloom_new_tab`, `_affibloom_nofollow`, `_affibloom_sponsored` and
      `_affibloom_show_disclosure` (`'1'`/`'0'`).
    - **Reading them back:** a flag is on only when stored as `'1'`, as Lasso reads its own. A product without
      the meta (none exist; products made before 11E were reset away) reads as off.
    - **The settings are read once per request** (`OptionSettingsRepository` keeps what it read, Task 9's design).
      So each add takes the settings as they are at that request, and a bulk pass takes them as they were when the
      pass began. Both match Lasso's "at creation" rule.
      - My first check script ran every step in one PHP process and so saw the first read throughout. It was
        re-run one process per step, as real requests are.
    - **Still to join this copy:** the secondary button's New Window and NoFollow, Detect Opportunities, link
      cloaking and Commission Upgrades join when their settings exist (the phase file's 11E list).
    - **Checks:**
      - lint and PHPStan, 147 unit tests.
      - **WP-CLI through the REST server, one request per step:**
        - with the defaults → New Window 1, NoFollow 1, Sponsored 0, Show Disclosure 1
        - NoFollow off and Sponsored on → 0 / 1 on the next product
        - changing the settings afterwards leaves both products as they were
        - adding the same link again → the duplicate, flags intact
        - a product added after the change takes the new values
      - Test products removed; the settings option back to absent, as before.
      - The integration case is written, not run (deviation 10). F18 AC10 awaits the user's run.
    - **Tasks 11A-11E are all done.** F18 parts owned elsewhere: the Marketplace tab (C1, as an Amazon search), the
      editor's dialog (Phase 4), the `check_duplicate_link` toggle and its off mode (the General settings pass),
      and Amazon naming and short links (Task 12).

41. **`tools/reset-local.sh` rewritten (2026-11-13, the user's report: "it deletes some tables even reset users
    data").** Not a plan task; it replaces Task 2's script.
    - **The cause:** the old script ran `wp db reset` (empty database) and then a ~60 s import. For that minute the
      site really had no users and most tables missing, and an interrupted run (time-out, Ctrl+C, closed terminal)
      left it that way: the half-resets in deviation 39 and the "admin login" session log. The import itself is
      sound: run to completion on a scratch database it loads all 141 tables in 56 s. A first guess, InnoDB's lock
      table filling under `autocommit = 0`, was wrong: `wp_rank_math_analytics_gsc` is MyISAM.
    - **Now:** the snapshot is loaded into `local_affibloom_staging`. It is checked for the snapshot's table count
      (`CREATE TABLE` lines) and at least one administrator. Then one `RENAME TABLE` statement moves the site's
      tables to `local_affibloom_previous` and the staged ones into `local`; MySQL reverses the whole statement if
      a pair fails. `previous` is dropped only after `local` is verified. The site is never empty; the swap takes
      about 6 s.
    - **Also:** a refusal when the snapshot lacks mysqldump's "Dump completed" footer; a lock
      (`.local/reset.lock`, holding the PID; a stale one is cleared once its process is gone); exit messages that
      say whether the site was touched; `</dev/null` on the deactivate loop's WP-CLI call.
    - **Checks (live site, backup first: `app/affibloom-backup-2026-11-13-pre-reset-fix.sql`, 152.7 MB):** a run
      force-killed mid-load (`taskkill /F /T`) left the site at 141 tables, 5 users, 1828 posts; a second run
      during the first was refused; the next run cleared the stale lock and finished in 2 min 7 s: 141 tables,
      5 administrators, 1807 posts (the snapshot's), Lasso and Affibloom active, Mediavine and LiteSpeed off, no
      leftover databases. The user's products 15127 and 15144-15147 went with the reset (they are in the backup).

42. **Task 12 superseded by 12A-12E (2026-11-13), from a sweep of Lasso's Amazon code and the live Amazon records**
    (`docs/features/F2/requirements.md` and `analysis.md` rewritten, gates 1-2; F17 section 4 corrected). The
    sweep read `lasso_add_a_new_link`, `is_lasso_url_exist`, `get_lasso_post_id_by_url`,
    `get_lasso_id_by_product_id_and_type`, `get_amazon_product_url`, `keep_args`, `get_product_id_by_url`, the
    search-page and short-link functions, `get_redirect_final_target` and `save_lasso_url`'s Amazon storage, and
    counted the 233 live Amazon records. It contradicts the plan's Task 12 and the first F2 in six places:
    - **The path is kept.** 225 of 225 live product links keep the pasted path with `tag` first and Lasso's kept
      parameters (`ref_`, `linkCode`, `linkId`, …); none is `/dp/<ASIN>?tag=`. "Shorten Amazon affiliate links"
      decides the query at add time, so F17 section 4 now gives it to Task 12A.
    - **The whitelist counts.** A whitelisted tag is kept while "Allow Multiple Tracking IDs" is on (`maas` always),
      against the overview's "exactly one tag, the site's".
    - **Duplicates are ASIN and country**, not ASIN alone; search pages by keywords and store; other Amazon pages by
      the retagged link; Lasso's "Sorry!" re-title (applied to Amazon products only, Lasso's precedence slip not
      copied).
    - **Nothing is refused** for a missing tracking ID or an unresolved short link: Lasso adds the link (untagged, or
      the short link as pasted). The plan's `no_tracking_id` and `short_link_unresolved` messages are dropped.
    - **A short link is stored as its destination**, not kept, on a new add (Lasso keeps it only when an edit leaves
      the ASIN the same: Task 15). Affibloom follows `amzn.to` with 11D's fetcher and stops at the first redirect
      into a store, so no Amazon store page is requested.
    - **A bare ASIN is not Lasso's** ("This is not a valid link." there); it stays as an addition from the approved
      F2 AC2, with decision 11's slug name. Search pages start as Lasso's own "Amazon: <keywords>" (Claude's call,
      as F18 D3).
    All settled from Lasso and the records (none drops anything Lasso does or reverses a user decision), so no
    question went to the user. Owned elsewhere: product data (C1), keeping a short link on edit (Task 15), the
    short-link map (link rewriting; Task 26 copies Lasso's table), URL-variant tags (URL variants), auto-whitelisting
    (auto-monetization). **Gate 4 given by the user 2026-11-13** ("Approve, start 12A"); the F2 and F17 docs are committed
    on their own before 12A.

43. **Task 12A (2026-11-13, `5dcfa7a`), an Amazon product link stored as Lasso stores it**, built as written, with
    these points from the build:
    - **`AmazonUrl` matches Lasso's ASIN pattern on what follows the host**, so a host in capitals still gives its
      ASIN (Lasso's pattern spells the host case-sensitively). The markers stay case-sensitive, as Lasso's.
    - **Four improvements that leave every live link unchanged**, each in the code's docblock: a short link is
      recognised by its host (Lasso looks for "amzn.to" anywhere in the link); `&amp;` is read as `&` in both
      stored forms (Lasso only when shortening); a fragment is dropped (Lasso drops it when there is a query and
      puts the query after it when there is none, which breaks the tag); kept values are encoded again (Lasso
      writes them decoded). Tracking IDs and every live value are unaffected.
    - **`is_short_link()` is private for now**, with `monetized()` its only caller; 12D makes it public for its
      second caller (the phase file's 12D table lists it as created there).
    - **Extracted on its second use:** `AffiliateUrl::host()` is public; `AmazonUrl` reads the store from it.
    - `_affibloom_asin` and `_affibloom_amazon_country` are written only for an Amazon product with an ASIN, as
      Lasso stores `amazon_product_id` only then. Amazon pages without an ASIN stay type `other` until 12C.
    - **Checks:** lint, PHPStan, 198 unit tests. Database backed up first
      (`app/affibloom-backup-2026-11-13-task12a.sql`). **WP-CLI through the REST server**, one process per
      settings change: the live Mikasa link with another tag → the live form byte for byte; `.co.uk` → country
      `gb`; a tag not on the whitelist replaced, a whitelisted one kept; Shorten off → whole query, tag in place;
      a `maas` link, a non-Amazon link and a search page unchanged; red first (stored as pasted, type `other`).
      Test products removed and `affibloom_settings` back to absent.
    - **Left alone:** product 15127 (`B0D2JJ5WGT`, made by user 12 through the dialog at 08:38) is the user's.
    - **The user's run, 2026-11-13: F2 AC1, AC3, AC8 passed** (and AC4 after 12B). Not checked here: the integration
      case (written, no wp-env); the browser pane (signed out since the reset; 12A changes nothing on screen).

44. **Task 12B (2026-11-13, `b1db8c5`), the same Amazon product twice**, built as written, with these points:
    - **The lookup sits after the stored-link one and after the geni.us destination**, before the new product, for
      an Amazon link with an ASIN; an Amazon product link is never requested, so the match costs no request.
    - **The ASIN is compared without case** by meta_value's collation, as Lasso's `url_details.product_id` is:
      `/gp/aw/d/b0chk12b01` found the `B0CHK12B01` product. The query also requires `_affibloom_type = amazon`, as
      Lasso's requires `product_type = amazon`.
    - **Extracted on its second use:** `PostTypeProductRepository::find_one()`, the published, lowest-ID meta lookup
      that `find_by_url_key()` and `find_amazon()` share (12C's search-key lookup is its third caller).
    - **`createProduct()` in `tests/E2E/utils.ts` corrected:** its `amazon:<asin>` URL key was the old plan's;
      every product's key is now `AffiliateUrl::key()` of its link, and an Amazon product gets
      `_affibloom_amazon_country` `us` unless given. F11 AC2 is its one Amazon caller.
    - **Backups were in the web root:** `wp db export ../…` from the plugin folder wrote the 12A and 12B backups to
      `wp-content/plugins/`. Both moved to `app/`; `environment.md` has the gotcha and an absolute-path example.
    - **The settings option was the user's run's:** the F2 run (08:49-08:50, user 14) left `affibloom_settings`
      at the spec's live values; the 12B checks overwrote and then deleted it, and it was put back byte for byte
      from the 12B backup. Products 15137-15140 (that run) and 15127 (user 12) were left alone.
    - **Checks:** lint, PHPStan, 201 unit tests. Red first through the REST server (`/dp/` then `/gp/product/` made
      two products). Then: `/dp/`, `/gp/product/`, lower-case `/gp/aw/d/` and a slugged link with another tag → one
      product; `.co.uk` → a second (`gb`); the first trashed (`post_status=trash`; WP-CLI will not trash this post
      type without `--force`, which deletes) → a new product. After the `find_one()` refactor, re-run: a plain link
      in capitals with a slash → its product (URL key), an ASIN in two forms → one product, `.de` → another. Test
      products removed.
    - **The user's run, 2026-11-13: F2 4 passed** (AC1, AC3, AC4, AC8). Not checked: the integration case
      (written, no wp-env).

45. **Task 12C (2026-11-13, `a4b3c52`), Amazon search pages and other Amazon pages**, built as written, with these
    points:
    - **The search key is Lasso's `base_domain` and `search_text`**: the host without `www.` (`amazon.com|nike kd14`),
      not the full host the F2 analysis first said (corrected in the same commit). Lasso's fallback query, for rows
      that have no `search_text` yet, matters only to migration: **Task 26 must give migrated search pages their
      `_affibloom_search_key`**.
    - **A page with no tag to set stays as it is.** Lasso's search branch needs a tag; its other-page branch would
      only rewrite the encoding.
    - **The lookups in Lasso's order:** ASIN (12B), then the search key, then the link as it would be stored, the
      last only when that differs from the pasted one.
    - **The re-title** runs on every match, whichever lookup found it, when the pasted link is Amazon's (Lasso's
      `$is_amazon_link`); a non-Amazon " wrong!" product keeps its name (Lasso's precedence slip not copied).
      `ProductRepository::rename()` is its caller.
    - **`AmazonUrl::is_short_link()` is public now:** `ProductService` types only Amazon store links until 12D
      follows short links (its second caller came here, not in 12D).
    - **Tidied on review:** `without_query()` shared by both stored forms (a repeat since 12A); the page branch
      parses its query once.
    - **Checks:** lint, PHPStan, 225 unit tests. Red first (a search page stored as pasted, type `other`). WP-CLI
      through the REST server: the four live search pages → stored byte for byte with their keys; `NIKE  kd14` on
      `/s/ref=nb_sb_noss` → the "Nike KD14" product; a store page with another tag → its product; a product named
      "Sorry! Something went wrong!" found by ASIN → renamed "Amazon"; a non-Amazon "Page went wrong!" → unchanged;
      after the tidy, a search page and a store page with `#top` re-checked. The copy was found unreset after the
      user's F2 run (11 products, settings at the spec's live values); only Claude's products were removed and the
      settings left as found.
    - **Not checked here:** F2 AC6 (the user's run); the integration case (written, no wp-env).

46. **Task 12D (2026-11-13, `5d1d634`), Amazon short links**, built as written, with these points:
    - **The destination replaces the short link before the Amazon lookups**, as Lasso's `$url = $get_final_url`:
      one rule, `ProductService::stored_as_destination()`, now covers short links (always) and geni.us (Keep Geni.us
      Formatting off), and the destination is checked by its link before the ASIN, search and retagged lookups.
    - **The fetcher stops at an Amazon store, not at any Amazon host,** so a short link's own host is requested and
      a short link that leads to another short link is followed on (`amzn.to` → `amzn.to` → `www.amazon.com`).
      Before 12D a geni.us link redirecting through `amzn.to` stopped at `amzn.to`; it now reaches the store link.
    - **An unresolved short link** (no answer or a 403) keeps its link, is typed Amazon, and is named "Amazon" (Lasso's
      `DEFAULT_AMAZON_NAME`), not the site name "Amzn".
    - **Extracted on its third use:** `AmazonUrl::is_short( AffiliateUrl )`, static, for `LinkResolver`,
      `ProductService` and `WpPageFetcher`; 12C's instance `is_short_link()` is gone.
    - **The short-link map is still not built** (F2 requirements 5): pasting the same short link again follows it
      again (two requests in the check) and finds the product by its ASIN.
    - **Checks:** lint, PHPStan, 232 unit tests. Red first (a short link stored as pasted, type `other`). WP-CLI
      through the REST server with a `pre_http_request` filter logging every request and faking
      `amzn.to/t12da` → `amzn.to/t12db` → a slugged product link with another tag: stored as the product link
      monetized, with its ASIN; requests to the two short links only, none to `www.amazon.com`; the same short link
      again → the product (duplicate); an unfaked short link, blocked by the site → kept, typed Amazon, "Amazon"; a
      store link → no request. Re-run after the `is_short()` tidy. Test products removed, settings back to absent
      (the user had reset the copy).
    - **A permission check stopped one clean-up** that would have deleted every listed product; clean-ups now
      delete only the IDs the checks printed.
    - **Not checked here:** F2 AC7 (the user's run); the integration case (written, with a faked redirect; no
      wp-env). The user's re-run of F2 after `8393a19` was not reported back.

47. **Task 12E (2026-11-13, `e839339`), a bare ASIN and the starting name**, built as written, with these points:
    - **A bare ASIN is read before the link** (`AmazonUrl::from_bare_asin()`, upper-cased, then
      `https://www.amazon.com/dp/<ASIN>`), so it also finds an existing product by URL key or ASIN, and bulk add takes
      it on a line (its `create_from_url()` call), checked through the bulk route and its background pass.
    - **The starting name** is `AmazonUrl::starting_name()`: the segment before `/dp/` of a link with an ASIN
      (`+` and `-` as spaces, then percent-decoded, spaces collapsed, case kept), else `search_page_title()`. Only
      `/dp/` carries a slug (decision 11's form); `/Mizuno/gp/product/…` is "Amazon". A followed short link is named
      from its destination's slug.
    - **`LinkResolver` keeps its "Amazon" name**, unlike the task text: a link stored as itself that leads into
      Amazon (geni.us with Keep Geni.us Formatting on, type `other`) is still named "Amazon", as 11D decided; only
      new Amazon products take the starting name. Its comment says so.
    - **A 12A unit test's expected name changed** from "Amazon" to "Mizuno Wave" (its link has that slug), by design.
    - **Checks:** lint, PHPStan, 258 unit tests. Red first (a bare ASIN refused "This is not a valid link."; the slug
      named "Amazon"). WP-CLI through the REST server: `B0CHK12E01` and `014044913x` → `/dp/` links with the tag;
      the slugged link → "Mizuno Wave Momentum 3"; `s?k=nike+kd14+t12e` → "Amazon: nike kd14 t12e"; `BESTSHOE10` →
      422 "This is not a valid link."; a bulk run of a link and a bare ASIN → both added. Claude's six products and
      the bulk run's option removed; the user's F2 products (15133-15141) and the settings left as found.
    - **The user's F2 run after `e839339`: all eight passed** (AC1-AC8). Not checked here: the integration case (no wp-env); the
      dialog in the browser pane (signed out; 12E changes nothing on screen). The user's F2 runs after `8393a19` and
      `5d1d634` were not reported back; the products of one were on the copy (AC6, AC7, AC8 among them).
    - **Task 12 is complete.** Still owned elsewhere (F2 requirements, section 10): product data (C1), keeping a short
      link on edit (Task 15), the short-link map (link rewriting; Task 26 copies Lasso's table and must fill
      `_affibloom_search_key` for migrated search pages), URL-variant tags, auto-whitelisting, the
      `check_duplicate_link` off mode (General settings pass).

48. **Task 13 superseded by 13A-13B (2026-11-13), from a sweep of Lasso's dashboard** (`docs/features/F19/`,
    gates 1-2, a new feature folder; the plan's Task 13 text moved to the phase file's appendix). The sweep read
    `admin/views/dashboard/dashboard.php`, the whole list half of `admin/assets/js/view-js.php` (page size,
    range, pagination, sorting, search, the request, row building, the empty state),
    `Ajax::lasso_report_urls`, `Lasso_DB::build_dashboard_search_term_string`, `::get_dashboard_link_count`,
    `::get_dashboard_query_without_filter`, `Helper::get_dashboard_page_size_options`,
    `Dashboard_Saved_Views`, `url-row.php`, `education/dashboard.php`, `lasso-helper.js`'s page cache and
    `command-palette.js`, with Lasso 370's production screenshots and this copy's data. It contradicts the plan
    in five places:
    - **10 per page, chosen from 10/25/50**, with a per-page select, a `1-10 of 263` range line and page links
      in the card's footer. The plan says 20 and no choice (`docs/spec/interface.md` already said 10).
    - **The order is `post_modified` DESC** (most recently changed), not the plan's `date` DESC.
    - **The search is not a title search.** Lasso cleans the typed text to `A-Za-z0-9+-.#` plus spaces, turns its
      spaces into `%`, wraps it in `%…%` and matches the name, the destination link, the ASIN, the second
      button's link, URL variants, the record's ID and an exact ASIN. The plan's `WP_Query` `s` would find a
      product by neither its link nor its ASIN, which is how an Amazon library is searched.
    - **The row is the name over the whole destination link,** not the plan's name and host.
    - **The screen carries far more than a table:** saved views (per user, up to 30), a filters panel (group,
      link type, placements, comparison table), bulk selection with Delete and Add to Group, Export CSV to WP All
      Import, `Ctrl+Shift+L`, sortable Alert/Locations/Clicks columns and the summary stats as filter tabs. None
      of these is in any task; the roadmap's Screen map does not mention them either.
    **Decisions (F19 requirements, section 6):** D1 (Claude) the list is rendered by PHP, so the page, the page
    size and the search are query arguments, not Lasso's hash plus localStorage plus sessionStorage — ADR-7
    already says PHP-rendered screens, and nothing Lasso's list does is lost; D2 Lasso's order; D3 10 per page
    from Lasso's three options, remembered in user meta instead of sessionStorage; D4 the search arms Affibloom
    has data for now (name, link, ASIN, ID), the second button's link with Task 15 (60 live records have one),
    variants only if that subsystem is built; D5 Lasso's cleaning and wildcards copied exactly; D6 an empty
    library and an empty search say different things (Lasso shows the same card for both); D7 the placeholder
    keeps counting every product.
    **Gate 4 given by the user 2026-11-13**, answering both questions: **Q1 Aurora's shell, Lasso's arrangement**
    (the header, band, centred column and cards stay; inside the card the search sits on a toolbar above the
    table and the pager in the card's footer, as Lasso 370 has it) — this settles the Dashboard only, and
    deviation 25's question stays open for the other screens; **Q2 every capability is scheduled with the data it
    needs and nothing is dropped**, the user adding: *"dont drop the shortcode. we are going to keep the
    shortcode functionalities as well."*
    - **New scope: Affibloom gets a shortcode of its own**, besides the `[lasso]` compatibility handler ADR-3
      already plans. It needs **a task of its own in Phase 3**, after Task 18 renders the box; the Dashboard
      row's `Shortcode` button then comes with the Actions cell (Task 14). The roadmap's Screen map and
      `docs/spec/interface.md` both say Affibloom has no shortcode of its own: **both are now out of date** and
      are corrected when that task is written.
    - The rest of the schedule (F19 requirements, section 8): sortable columns and the stat filter tabs with
      Tasks 31, 34 and 36; the filters panel and saved views in one task after Task 36; bulk selection and bulk
      delete after Tasks 17 and 36; Export CSV in Task 38's CSV export; `Ctrl+Shift+L` as a small Phase 8 task.
    Live data behind the decisions (queried on this copy): 263 Lasso records, 1 Group term with **0**
    assignments, **60** records with a second button URL, **0** URL variants, **0** saved views.

49. **Task 13A (2026-11-13), the Dashboard list**, built as written (deviation 48), with these points from the
    build:
    - **The total comes from its own count query, not WP_Query's `found_posts`.** On this copy's MySQL 8.4,
      `SQL_CALC_FOUND_ROWS` reports **0** when the LIMIT offset is past the last row (checked: `paged=99` gave
      `found_posts=0`, `paged=3` gave 22), so page 99 of a full library rendered the "No products yet." card.
      Lasso counts separately too (`Lasso_DB::get_dashboard_link_count`). Knowing the total first also lets the
      repository **clamp the page to the last one before fetching**, so a page past the end shows the last page
      (`21-22 of 22`) instead of nothing — which is what Lasso's range line does.
    - **`ProductPage::current()`** (the clamped page number) carries that clamp into `first()`, `last()` and the
      pager. The task text's `last()` counted the items on the page, which gave the wrong range for a page past
      the end; the unit test caught it before the class was written.
    - **The empty-library card is chosen by `0 === $products->total`**, not `is_empty()`: a page past the end is
      empty without the library being empty.
    - **`PerPage::choose( int $asked )`**, not the task text's `from_request( array )`: the screen sanitises the
      request (`absint`, with the nonce rule disabled for a read-only list, as Task 8 does) and the class takes
      the number. It remembers the choice in user meta `affibloom_per_page`; 10, 25 and 50 are accepted and
      anything else is ignored, as Lasso's handler does.
    - **`.affibloom-table` is `table-layout: fixed`,** and the name and URL are cut with an ellipsis. Without it
      a long Amazon link widened the table past a phone screen (checked at 375px: no horizontal scroll after).
      The columns of Tasks 14, 31, 34 and 36 take their widths there, as Lasso's grid gives them.
    - **The row's name is not a link yet.** Lasso's name links to the product page, which is Task 14; the add
      flow already redirects to the Dashboard for the same reason (`ProductsController`). Task 14 makes it a link.
    - **F19 AC10 is `test.fixme` until 13B:** an empty library means trashing every product and putting it back,
      which only makes sense once the search's own empty state exists to tell the two apart. The empty card was
      checked by rendering the real template with an empty page.
    - **The check by hand ran through WP-CLI,** because the browser pane on this machine is no longer signed in:
      the screen was rendered for an administrator at 10, 25, 50 and a refused 7 per page, pages 1, 2 and 99,
      and the markup read back (rows, range, page links, tile, empty card). For the look, the same markup with
      the real stylesheets was written to `wp-content/uploads/` and opened signed out, at 1280px and 375px, then
      deleted. The signed-in screen is the user's F19 run.

50. **Task 13B (2026-11-13), the Dashboard search**, built as written (deviation 48), with these points:
    - **A `posts_where` filter, not the task text's `posts_search`.** `posts_search` only runs when WP_Query is
      given `s`, which would also mark the admin list as a search (`is_search`), invite WordPress's relevance
      ordering and let anything else on the site that hooks searches act on it. The filter is added immediately
      before the two queries, removed in a `finally`, and does nothing unless the query carries the term, so it
      cannot reach another query.
    - **`SearchTerm::like()` takes the escaper** (`$wpdb->esc_like()`) as an argument, so the Domain layer escapes
      without knowing what `$wpdb` is. A `%` or `_` an editor types is therefore the character, not a wildcard;
      Lasso escapes neither. In practice the cleaning already removes both (`100%_off` searches for `100 off`).
    - **`ProductRepository::count()`** (via `wp_count_posts`, which WordPress caches) gives the library's size for
      the search placeholder "Search all N links…" and the Products tile, so neither shows the filtered number
      while a search is on (decision D7). Two callers, so it is not built ahead of its use.
    - **Runs of removed characters collapse to one space.** Lasso leaves them, which gives `a%%%b`; in a LIKE that
      matches exactly what `a%b` matches, so only the text put back in the box differs.
    - **`$term` and `$search` are WordPress globals**, so the template's variable is `$typed` (PHPCS
      `WordPress.WP.GlobalVariablesOverride`).
    - **F19 AC10 parks the products as drafts, not in the trash.** WP-CLI refuses to trash `affibloom_product`
      ("Posts of type … do not support being sent to trash"), because the type is not public. The list shows
      published products only, so drafting empties it the same way, and publishing them back is exact.
    - **For Task 17:** `wp_trash_post()` itself works on a product (checked), but `wp_untrash_post()` brings it
      back as a **draft**, which would make a restored product vanish from the Dashboard. Task 17 must restore the
      status itself or filter `wp_untrash_post_status`.
    - **The check by hand** (WP-CLI, five seeded products): "mizuno wave" finds the Mizuno product; the whole
      `amazon.co.uk` link pasted finds its product; the product's ID finds it; `b0cph9npk8` and `B0CPH9NPK8` both
      find the product with that ASIN; "Wave Momentum" finds only "Mizuno Wave Momentum 3" while "Momentum Wave"
      finds only "Momentum Wave Asics" (the wildcards keep word order); `100%_off` matches nothing; a bare `%`
      cleans to an empty term and lists everything; a term that matches nothing shows "No products match your
      search." The search box, the tile (the library's size, not the filtered count) and the no-match card were
      seen at 1280px in a signed-out preview of the real markup.
    - **The Local copy was reset by the user** after the F19 run, so it holds no Affibloom products; the five
      products seeded for this check are left as **drafts** (IDs 15126-15130) and are invisible to the Dashboard.

51. **The Local copy seeded from Lasso (2026-11-13, `d401a43`), and a parity audit of product management.** The
    user asked to see the real products in the Dashboard and for a review of Lasso's product management against
    the plan.
    - **`tools/seed-from-lasso.php`** reads Lasso's records and `lasso_url_details` and writes Affibloom
      products (name, link, type, ASIN, country, both buttons and their flags, image, badge, description,
      disclosure, `_affibloom_lasso_id`), keeping Lasso's `post_date` and `post_modified` so the Dashboard's
      order matches Lasso's. It is idempotent and **is not the migration**: Tasks 25-26 still build
      `wp affibloom migrate` with its dry run, field mapping, report and checks.
    - **Run: 254 seeded, 9 skipped** — exactly the 9 empty records Task 4 found (14837-14845). The Dashboard's
      first page now matches `tests/fixtures/baselines/admin/dashboard-1.jpg` row for row. Backup first:
      `app/affibloom-backup-2026-11-13-pre-seed.sql`.
    - `wp eval-file` runs a file through `eval()`, where `declare( strict_types=1 )` is a fatal error; the tool
      says so where the declare would be (as `tools/inventory.php` has none).
    - The 31 products the user's F19 run left, and the 5 from the 13B check, are parked as **drafts**, so the
      list shows the 254 real ones only.
    - **Parity audit: `reports/2026-11-13-product-management-parity-audit.md`.** Five things have **no task
      anywhere**: **G1 link cloaking** (on site-wide, on all 254 records — it changes every reader-facing link),
      **G2 opportunity discovery** (local, fully rebuildable; 2,094 untied links here), **G3 the content link
      scanner** that G2 and Lasso's Locations count need (Task 35's usage index is narrower), **G4 out-of-stock
      detection** (needs Amazon product data, so C1 credentials), **G5 Groups** (left out on 0 assignments).
      Also found: **Amazon products cannot be checked for broken links without C1** (Lasso uses the product API,
      not an HTTP status), so F11 would cover ~7% of this library; the Alert column's tick, clock and issue age
      are not in Task 31's text; the Image column is not in any task's text; and **Lasso's 2,839 historical
      clicks are not migrated** by any task. The audit's section 6 recommends what to add and in what order.
    - **Correction, same day (audit section 7): G1 is far smaller than the audit first said.** The first version
      read the settings (`link_cloaking: true`, `link_cloaking = 1` on 254 records) and said every reader-facing
      link is cloaked. **Lasso never cloaks an Amazon link** whatever the setting says (`Lasso_Affiliate_Link`
      460-466 and 481; Amazon's terms forbid redirecting their links). Measured here: 233 Amazon products render
      raw; of the 30 non-Amazon products **5** have cloaking on and **3** are placed in content; 6 cloaked links
      exist in post content, across 2 posts. G1 is therefore a small, bounded decision - keep serving those
      published `/<slug>` URLs so they do not 404, and whether the Permalink field returns to the product page -
      not the first thing to plan. The user had chosen "cloaking first" on the overstated version; they were
      told before any of it was built, and the order is theirs to re-pick.
      **Waiting on the user** for how to re-sequence.

52. **No Lasso cloud, ever; credentials later (the user, 2026-11-13).** *"We should implement all the dashboard
    functionalities now which don't need api credential or lasso's own cloud dependency. The tasks which need
    api credential, can be schedule for later. we shouldn't implement any code which is run based on lasso's
    cloud."*
    - **Bucket C, never built:** Lasso's marketplace and **programme opportunities**, Hub short links
      (`lasso.to`), the Lambda data sync (`classes/data_sync/*`), the licence, account and analytics screens,
      and the Amazon data proxy the live site uses today. No code is written that would call any of them.
    - **Bucket B, written now and shipped dormant until the client's Amazon Creators API credentials (C1)
      arrive:** out-of-stock detection with its Alert icon, filter tab and CSV rows; **broken-link detection for
      Amazon products** (233 of 254 here — an HTTP check of an Amazon URL returns 200 for dead products and
      fetching Amazon pages breaks the Associates licence); price, Prime and image refresh.
    - **Bucket A, built now:** the content link index, Locations, link and domain opportunities, non-Amazon
      broken links with the Alert column in full, clicks including Lasso's 2,839 recorded ones, Groups, the
      dashboard controls (sorting, filters panel, saved views, bulk actions, Export CSV, `Ctrl+Shift+L`) and
      link cloaking.
    - The sequence, the areas and their feature numbers are in **`plans/archive/dashboard-completion-plan.md`**; each
      area takes gates 1-2, then tasks in full, then the user's gate 4, as F16-F19 did.
    - **Three things the rule did not settle, answered by the user the same day:** **Groups is built** (the
      column, filter, product-page and quick-edit fields, bulk "Add to Group"), despite 1 term and 0
      assignments; **keyword opportunities are left out**, because Lasso monetizes a keyword by rewriting the
      post's stored content and the project excluded that — **nothing Affibloom builds ever edits post
      content**; and **comparison tables are in scope**, as an area of their own after the dashboard areas,
      which also keeps the filters panel's comparison-table filter.
    - Knock-on: **Groups comes first** (area 0), because Tasks 14 and 16 carry the Groups field; Task 6's
      decision to leave Groups out is withdrawn, and the overview's decisions table says so.

53. **The Dashboard UI is finished before Tasks 14-17 (the user, 2026-11-13).** *"We will complete Tasks 14-17
    first. However, before proceeding, I want you to complete the Dashboard UI. Make the product table fully
    structured with all the columns Lasso has … search is already possible, but you haven't implemented it using
    AJAX. Make sure it works properly without page reloads … use realistic placeholders such as 'Coming Soon'
    (e.g., the Location column) … Don't omit columns or UI elements just because their underlying functionality
    isn't ready yet."* Written as **Tasks 13C-13E** in the phase file; the user's instruction is their go-ahead,
    so no separate gate 4 was asked.
    - **F19 decision D1 is reversed:** the list loads over REST without a page reload, as Lasso's does. ADR-7
      allows it (JavaScript where Lasso uses it). The URL still carries the search, page and size through
      `history.pushState`, so reload, Back and bookmarks keep working, and the page still works without
      JavaScript.
    - **Placeholders, never borrowed data:** Lasso's alerts, locations and clicks are on this copy and
      `_affibloom_lasso_id` would reach them, but Affibloom never reads Lasso's tables at runtime (ADR-2, and
      what the user was told when they asked). A placeholder column has a "Soon" chip in its header and a muted
      dash per cell, read out as "Coming soon"; disabled buttons carry "Coming soon"; the Out of stock tile says
      it needs the Amazon API instead, because it waits for credentials, not for a task.
    - **Order changes back:** Tasks 14-17 come before the Groups area again, at the user's word ("We will complete
      Tasks 14-17 first"). Task 14's and Task 16's Groups field is therefore a placeholder until Groups lands.

54. **Tasks 13C-13E built (2026-11-13): the Dashboard UI finished** (deviation 53), with the user's corrections made
    while it was built.
    - **13C `b2ab7b3`, every Lasso column:** select, Image, Link Name, Groups, Alert, Locations, Clicks, Actions;
      Lasso's selection bar (Select All across pages, Clear Selection; Delete and Add to Group "Coming soon"); five
      tiles (Products live; Broken links, Opportunities and Clicks (30 days) "Coming soon"; Out of stock "Needs the
      Amazon API"). `Product::$image_url` is read for the Image column (its first caller). Found and fixed while
      looking: the Link Name column collapsed (widths now border-box); the "Soon" chips overlapped in narrow headers
      (stacked under the label); the selection bar pushed the rows down under the pointer (it lives in the toolbar
      row now); hover and selection looked alike; **wp-admin draws a white tick on a transparent box, so a ticked
      box looked empty** (the box is drawn in full); the hidden "Coming soon" texts are `position: absolute` and
      widened the page on a phone (the scroller is positioned).
    - **The search moved right of the title in the band** (the user, mid-task: "move the search box at right side
      of the dashboard title wrapper").
    - **13D `f32c89f`, no page reloads** (the user: "search, pagination, filters should be done via ajax"; reverses
      F19 D1): `GET /affibloom/v1/dashboard/products` renders the same list partial the page prints
      (`Template::fetch()`); the script swaps it in, pushes the screen's address (`DashboardUrl`, because inside a
      REST call "the current URL" is the API's and the pager's links pointed there), drops stale answers, shows a
      busy bar and an inline error with Try again, and announces the range. `Ctrl+Shift+L` / `⌘⇧L` focuses the
      search (Lasso's palette is a Phase 8 task). Checked through the REST server (admin 200, subscriber 403, a bad
      page 400, a page past the end clamped) and in the browser with the real answers replayed.
    - **The Dashboard is 1320px wide** (the user asked "full-width or 1320px"; Claude recommended 1320, Lasso's own
      container width, Dashboard only; `.affibloom-page--wide`). Settings stay 1080px so a switch stays near its
      label. design-system.md section 5 says so.
    - **13E `00dc1af`, filters and saved views:** Link type (Amazon / Non-Amazon, `_affibloom_type`) live; Group,
      Placements and Comparison table "Coming soon"; Lasso's labels, help text, "Reset filters" and "Filters apply
      as you toggle"; the count on the button. Saved views per editor in user meta `affibloom_saved_views`, Lasso's
      shape and rules (30 at most, names of 1-80 characters, "Invalid view name.", "Saved view limit reached.");
      pills restore a view, × asks Lasso's "Delete saved view?" first. Domain `SavedView`/`SavedViews` with unit
      tests. **Deviation from Lasso's layout:** the panel is a card under the Filters button, not beside the table,
      because eight columns leave no room. Help text drops the sort and alert tab until they exist.
    - The checks by hand ran through WP-CLI and a signed-out preview (the browser pane is not signed in); F19 has
      14 tests now (AC11-AC14 added), all the user's to run.

55. **Every admin screen more spacious (2026-11-13, `ee6743c`, the user: "the dashboard page looks compact. we need to
    make all admin pages spacious").** Three tokens: `--affibloom-space-gutter` 40px, `--affibloom-space-section`
    28px, `--affibloom-space-card` 32px; table rows 20px padding with 48px images (about 80px, as Lasso's `p-4`
    rows); settings rows 20px; the Amazon tab's toggle rows opened up. The Display rail keeps its Lasso
    measurements (deviation 25). design-system.md section 4 records it.

56. **Add Link ends as Lasso's does (2026-11-13, `6d9a5ef`, the user: "Check how add link feature is working in lasso
    plugin. we need exactly same behavior").** Re-read `url-add.php`'s success handler (1606-1640) and
    `lasso_add_a_new_link`'s duplicate branch (878-905). Fixed: **no "Link added." and no "Bulk add finished."**
    (Lasso shows no message); **an Amazon link, new or found, reloads the Dashboard silently** (Lasso's `is_amazon`
    branch runs before its duplicate branch); **a found product is saved again** (Lasso's `wp_update_post`), so it
    moves to the top of the list (unit test, and the in-memory fake now orders by "last saved"); **the empty
    Dashboard has Lasso's words and its "Add a Link" button**. **Still different until Task 14:** Lasso opens a
    non-Amazon product's page after an add, with its duplicate warning there; until that page exists the Dashboard
    stands in and a non-Amazon duplicate keeps the warning as a toast. F18, F2, F19 and the integration test were
    updated to the new endings.

57. **Task 14 superseded by 14A-14E (2026-11-13), from a sweep of Lasso's product page** (`docs/features/F1/`
    requirements sections 2-8 and analysis rewritten, gates 1-2 re-run; the old text moved to the phase file's
    appendix). The user said "go, phase 2 task 14"; following the full-scope rule, the page was swept before anything
    was built. Read: all 2,673 lines of `admin/views/dashboard/url-details.php`, `dashboard/header.php`,
    `get_lasso_url()`, `upload_thumbnail()`, the custom-field row, the field, save, unsaved and field-delete modals,
    the tracking-ID notice, and this copy's data. It contradicts the old Task 14 in these places:
    - **Rating is not a field of its own.** It is the built-in custom field "Primary Rating" (Lasso's `lasso_fields`
      id 1, on 5 records here, 3.5-4.5, range **1-5**), attached through "Add Custom Field", next to Pros and Cons;
      custom fields save as they are edited, not with Save Changes. → **14C**.
    - **Left out by the old text, all in Lasso and in use:** Permalink (30 non-Amazon), Groups, Display Theme, Price
      and its toggle (186 prices), Detect Opportunities, Link Cloaking, the Share kit, the Locations/Opportunities/
      Variants tabs, the tracking-ID notice. The image label is "Amazon Image" or "Product Image" with Apply.
    - **Lasso saves the whole form when the theme changes** (its preview refresh posts the save payload) and writes an
      applied image link at once. **D2 (Claude):** neither saves; Save Changes stores everything, guarded by the
      unsaved-changes dialog. Nothing Lasso stores is lost.
    - **Settled by earlier rules:** Commission Upgrades, Refresh through Lasso's cloud, the bulk Amazon sync notice,
      opportunity flips and Lasso's help links are left out (deviation 52, bucket C); the "repoint" offer and force
      delete rewrite posts (left for the Task 15 and 17 sweeps; nothing Affibloom builds edits post content).
    - **Placeholders** (deviation 53): Groups, Permalink, Link Cloaking, Detect Opportunities, three tabs, Copy
      shortcode and Copy block; "Needs the Amazon API" on Refresh and the Amazon price. The heading's link and "Copy
      shareable URL" use the destination until link cloaking exists (D5).
    - **New areas in `plans/archive/dashboard-completion-plan.md`:** **Fields** (Lasso's Fields screen; 14C builds the
      definitions and the product page rows) and **URL variants** (0 rows here). 14E (the live preview) runs in
      Phase 3 after Task 18x, beside 10D.
    - The interface spec's product page table and its rating rule follow (plugin repo).
    **Gate 4 given by the user 2026-11-13** ("Approve, start 14A"), answering D1: **Lasso's two cards in Aurora's
    shell**, as the Dashboard. Deviation 25's open question is now settled for the product page too.

58. **Task 14A (2026-11-13, `b3686ab`), the product page opens**, built as written (deviation 57), with these points from the
    build:
    - **Through the service, not the repository:** `ProductScreen` asks `ProductService::get()` (new, one line over
      `ProductRepository::get()`), as `DashboardScreen` goes through `ProductService` (Interface → Application). It also
      takes `DisplaySettingsService` for the placeholders (the Single type's two button texts).
    - **`url2` is a string**, as Lasso keeps `second_btn_url` (trimmed on read); Task 15 validates it.
    - **A missing secondary toggle reads as on**, Lasso's new-record default (`open_new_tab2`, `enable_nofollow2`:
      true, no screen changes them), not the task text's "fall back to the primary value". `Product::draft()` gets the
      same default; `save()` does not write them yet (Task 15 does).
    - **The guard runs on `load-<hook>`** and keeps the product it found for `render()`; a draft, a trashed product,
      another post type or no ID go to the Dashboard (AC8).
    - **Reused, not created:** `partials/switch-row.php` (10F's toggle-left row) for the four toggles; the band is in
      `product.php` (one caller), so no `product-band.php`. New: `partials/field-text.php` (label above, help tip,
      optional "Soon" chip; 14B is its second caller) and the `warning` icon.
    - **Removed, no caller left:** the Dashboard's duplicate toast, `Notice::kind()`, the toast's "info" variant and
      the Dashboard's `Notice` and toast (no redirect lands there with a message any more). A non-Amazon duplicate now
      opens its product with Lasso's sentence in a **Notice** (new design-system row and `--affibloom-warning-*`
      tokens, 6.2:1 on its tint).
    - **Placeholders until their tasks:** Permalink (non-Amazon), Groups, Add Custom Field, the right card ("Preview and
      display fields"), three tabs, Save Changes and Delete This Link (`aria-disabled`, "Coming soon"). The form carries
      `data-not-saved`: `admin.js` stops Enter from sending it until Task 15 (checked: the URL stays the same).
    - **Lasso's Permalink help text** "cloak the the original" is printed without the doubled "the".
    - **Checks:** lint, PHPStan, 288 unit tests (new: draft's secondary defaults, `get()`, the hidden page and its load
      hook). E2E on this machine, signed in through the stored session: **F1 AC1, AC5, AC6, AC8, AC9 red first, then 5
      passed**; **F18 AC1-AC4, AC10 passed** (their endings now on the product page); **F2 8 passed**; **F19 11 passed, 1
      skipped, 2 failed — AC9 and AC12 fail the same way on the code before 14A** (stashed and re-run): AC9's
      `getByText` also matches 13D's screen-reader status line, and AC12's Back leaves the search box filled. Pre-existing
      from 13D, not fixed here. Looked at 1440, 992 and 375px through screenshots with the E2E session (the browser pane
      is not signed in): no sideways scroll after two fixes (the tabs scroll inside the band on a phone; the left card's
      column may shrink below its content); the duplicate Notice above the cards.
    - **Clean-up:** an early run matched F10-F19 as well (`acceptance/F1` is a prefix); its five items were deleted.
      The 44 products the runs made (15425-15470) were deleted; F2's settings were put back to absent. Integration
      test updated (`redirect_to`), not run (deviation 10).

59. **Button icons, F28 (2026-11-13, docs `0ced689`), the user's next prompt after 14A** (*"for the buttons, we should
    have a icon picker"*). Swept before planning (`docs/features/F28/`, gates 1-2):
    - **Lasso has no icon feature.** The icons are the **site's own Additional CSS** (child theme, `custom_css` post
      5977): six `span.<name>-icon { content: url(<media SVG>) … }` rules (amazon, pricetag, medal, full-review,
      alsogood, youtube). Lasso stores and prints the `<span>` as typed (Task 6 decision 5).
    - **17 live texts name classes the CSS does not define** (badge `madel-icon` ×8, `alsogreat-icon` ×5, `alsogood` ×2,
      `tick mark-icon` ×1; button `underarmour-icon` ×1), so readers see no icon there. The `alsogood-icon` rule's URL
      lacks its leading slash (resolves on the home page only). Neither is changed.
    - **The user chose** (2026-11-13): **an Affibloom icon set** in Settings → Display, the site's six imported with their
      CSS left in place and new icons added without CSS (D1); **broken classes flagged, not fixed** (D2).
    - **Claude (D3):** the picker enhances the existing text field; the stored form stays `<span class="x"></span> Text`,
      so every cleaner, route and test is unchanged and the box keeps its look (ADR-6).
    - Register item **U11** (an addition) in the roadmap. **Tasks 14F (the set) and 14G (the picker)** written in the
      phase file, to run before 14B; **gate 4 given by the user 2026-11-13** ("Approve, start 14F").

60. **Task 14F (2026-11-13, `7c69cf7`), the button icon set**, built as written (F28, deviation 59), with these points:
    - **What exists:** `Domain\Icons\{Icon, IconSet, IconRepository, CssIconRules}`, `Application\Icons\{IconService,
      SiteIcons}`, `Infrastructure\WordPress\{OptionIconRepository, SiteCssIcons}`, `Interface\Admin\IconController`
      (admin-post add/remove, `SETTINGS`, nonces), `Interface\IconStyles`, the "Button icons" card under Settings →
      Display's editor (`partials/icon-set.php`) and its media-library button in `display-settings.js`.
    - **The importer reads only whole-rule selectors** `span.<name>-icon { … content: url(…) }`: a descendant such as
      `.x span.y-icon` draws the icon only inside `.x`, so it does not define it (the unit test caught the first
      pattern taking it). Comments are skipped; a class defined twice counts once. Against the live Additional CSS: the
      six icons, in the CSS's order, `alsogood`'s relative link kept as written.
    - **The property is `css_class`**, not `class`: PHPCS warns on a reserved word as a parameter name. The stored key
      stays `class`.
    - **An image link is refused if it could leave `url("…")`** (quote, bracket, backslash, angle, space), so the
      printed CSS needs no escaping; the controller also requires an SVG, PNG or WebP media item the editor can read.
    - **CSS for added icons is printed on the site only.** The admin gets it with the first admin screen that shows a
      box (the preview, 10D/14E): nothing in the admin draws an icon by its class yet.
    - **The card shows each icon on a dark tile**, as on a button: the site's SVGs are white (`#FFFFFF`, `alsogood`
      `#F2F2F2`) and were invisible on the white card. "Full review" and "Youtube" show no picture on this copy: their
      files (`uploads/2023/04`) are not in the Local copy (production has them); the first has an attachment row, the
      second none.
    - **Without JavaScript** the media library cannot open, so Add is refused with the reason (the toast); the list and
      Remove work.
    - **Checks:** lint, PHPStan, 309 unit tests (new: `IconSetTest`, `CssIconRulesTest` over a fixture copied from the
      live CSS, `IconStylesTest`, `IconServiceTest`). E2E: **F28 AC1 and AC2 red first, then 2 passed** (AC2 chooses an
      image in WordPress's media library, finds the CSS on the home page, removes the icon after the confirm); **F16 and
      F17: 14 passed, 1 skipped**. Card looked at 1440 and 375px (no sideways scroll).
    - **Database:** options table backed up first (`app/affibloom-backup-2026-11-13-task14f-options.sql`). The option
      `affibloom_icons` now holds the six imported icons (made by the first visit of the Display tab, as designed); F16's
      leftover `affibloom_settings` was deleted again.

61. **Task 14G (2026-11-13, `c799ff5`), the icon picker**, built as written (F28), with these points:
    - **The stored field is unchanged** (D3): `partials/icon-text-field.php` prints it as before; with JavaScript
      (`assets/admin/icon-picker.js`) it is hidden and written from a picker button and a words box, in the site's form
      `<span class="<class>"></span> <words>`. A text holding other HTML keeps the plain field. `Domain\Icons\IconText`
      splits and composes (13 unit cases, the live shapes among them).
    - **`Interface\Admin\IconChoices`** (extracted on its second use, from 14F's `DisplayScreen::icon_rows()`): the
      rows with an admin-loadable picture, and each picker field's view (current icon, unknown class, the placeholder's
      words). Both screens hand it to their templates.
    - **Where:** Settings → Display's two button texts (every type) and the product page's two Button Text fields
      (`field-text.php` takes `choices`). Badge Text (14B) and quick edit (16) pass `choices` when they are built.
    - **The label points at the words box** once enhanced, so `getByLabel` finds the words. **F16 AC8** typed the whole
      `<span …></span> Buy Now` into the field and expected it back in the same box: the stored text is still exactly
      that, but the field now shows the Amazon icon and "Buy Now", so the test reads the stored field and the picker
      (assertion changed, not the behaviour it guards).
    - **The menu is admin.js's shared Menu**, floating against the viewport (the Display rail scrolls and would clip
      it): opens below its button, or above when there is no room; arrows, Home and End move; Escape closes and
      returns the focus; Tab or a scroll outside the menu closes it. Its name is "Icons for <label>" (the first version
      reused the field's label, so `getByLabel` matched two elements).
    - **An empty field** shows "No icon" and the default's words as its placeholder; it keeps meaning "use the site's
      text", icon included, as before. HTML typed into the words box is stored as typed; such a text then opens as a
      plain field. The Display text rows lost their `maxlength="500"` attribute; the cleaner still refuses longer texts.
    - **Checks:** lint, PHPStan, 322 unit tests. E2E: **F28 AC3 and AC4/AC5 red first; F28 5 passed** (AC6, without
      JavaScript, passed before and after: it guards the plain field). An AC2 race fixed in the test (it now waits for the
      redirect's notice; the row also vanishes while the old page unloads). F16 all passed after AC8's change; F1 AC1,
      AC3, AC5, AC6, AC8, AC9 and F18 passed (F1 AC2 and AC4 are Tasks 15 and 17, not built). Looked at: the open menu on
      both screens, an unknown `madel-icon` shown and listed, keyboard and Escape.
    - **Clean-up:** a leftover icon from an AC2 run, 25 test products (15471-15496), a finished bulk run and the
      settings option the runs re-created were removed; the copy holds only `affibloom_icons`.

62. **Next requirement (the user, 2026-11-13, during 14G): any number of buttons.** *"We should have option to add
    multiple buttons. Currently the lasso has option to add only two buttons."* Lasso has a primary and a secondary
    button (each a URL, a text and two toggles; Display has two button colours and texts per type). Not started: it
    changes the product, the Display settings, the box (ADR-6's pixel parity for today's two), the block and the
    migration, so it gets gates 1-2 (a sweep of how Lasso renders and tracks its two buttons, and how many this site
    uses) and the user's gate 4 before any task.

63. **Any number of buttons, F29 (2026-11-13, docs `d546fbf`), the user's requirement after 14G.** Swept before planning
    (`docs/features/F29/`, gates 1-2):
    - **Lasso has exactly two**: every box template prints `lasso-button-1` and, with a secondary URL, `lasso-button-2`
      (hidden on its own page, without a link when its text is empty); shortcode overrides `primary_*`/`secondary_*`;
      quick edit, tables, Display colours and texts and GA events (`… Primary Click`, `… Secondary Click`) know two.
    - **This site:** 60 of 254 products have a second button, mostly a shoe's men's/women's version (46) or another shop;
      `primary_url` overrides on 12 boxes, `secondary_url` on 1; three posts box the same product twice.
    - **The user chose** (2026-11-13): **as many buttons as needed** (D1), **buttons 3+ look like the secondary** (its
      colour, default text and element toggle; D2), **their clicks send `Lasso Display Secondary Click`** with their own
      text (D3).
    - **Claude:** one ordered list for buttons 2 and up, `Product::$buttons`, replacing 14A's four `url2` fields (D4; no
      production data exists, the seed tool and the migration write the new form); Amazon handling for button links
      follows Task 15's finding for the secondary (D5); quick edit, the Button display, tables and link health stay
      Lasso's (secondary / primary only).
    - **Task 14H** (the product and its page) written in the phase file; notes in phases 3, 5 and 7 for the box, the
      migration and the clicks; register item **U12**. **Gate 4 given by the user 2026-11-13** ("Approve, start 14H").

64. **Task 14H (2026-11-13, `b50ace2`), any number of buttons on the product page**, built with the user's change mid-task:
    - **The user, during the build:** *"By default there is two buttons. Instead keep one button. but that default button
      also be sortable using move up/move down."* Asked one follow-up: **the first button in the list is the box's
      primary** (primary colour, `Primary Click`), whichever it is; the default button's link stays the product's main
      link. Recorded as F29 D6 and D7; AC1-AC5 and B1-B6 rewritten (the box, GA and quick edit follow).
    - **Data:** `_affibloom_buttons` (the added buttons, in order) and `_affibloom_primary_position` (where the default one
      sits, clamped to the list). `Domain\Product\{Button, Buttons}`; `Product::$buttons` replaces 14A's four `url2`
      fields (D4). A button with neither link nor text is dropped; a text without a link is kept (123 Lasso records).
    - **The Local copy** was converted once (postmeta backed up first:
      `app/affibloom-backup-2026-11-13-task14h-postmeta.sql`): 183 products got a button list (60 with a link, the rest
      Lasso's text-only second buttons), the old keys removed; no reader for them stays in the plugin. The seed tool
      writes the new key. The Dashboard search covers `_affibloom_buttons`.
    - **Page:** one `fieldset` per button, "Button <n>"; the default one keeps 14A's ids and Lasso's primary labels and
      help texts, with a "Main link" tag; added ones have Lasso-style labels ("Additional Destination URL", "This text will
      appear in this extra button"). `assets/admin/product-buttons.js`: add from a `<template>`, move, remove (asks when
      the link is filled), renumber names and `primary_position`; new blocks announce `affibloom:enhance`, which the icon
      picker and the help tips now listen for. Without JavaScript one empty added block (in `<noscript>`, outside the
      list) follows the list; the first version kept it inside the list, which broke Move up.
    - **Checks:** lint, PHPStan, 327 unit tests (`ButtonsTest`, `ProductTest`). E2E: the first F29 tests were red for the
      right reason; **after the user's change the tests were rewritten and not run red again** for the new design (the
      code was already half-built). **F29 2 passed; F1 AC1, AC5 (updated for the list), AC6, AC8, AC9 passed; F28 and F18's
      single adds 10 passed.** Looked at 1440 and 375px: one header row per block, no sideways scroll. 26 test products
      removed.
    - **Interface spec:** the secondary rows became one "Buttons" row.

65. **Full acceptance run of everything built (2026-11-13, the user: "Before going to next task, I want to run the
    untested tasks").** One run of F1, F2, F3, F9, F16, F17, F18, F19, F28 and F29 on the Local copy (full database
    backup first: `app/affibloom-backup-2026-11-13-before-full-e2e.sql`), leaving out the three tests of work not built
    yet (F1 AC2 save, Task 15; F1 AC4 delete, Task 17; F9 AC2 the box, Phase 3). Specs of unbuilt features (F4-F8,
    F10-F12, C1-C3) were not run.
    - **64 tests: 59 passed, 2 skipped, 3 failed (13.9 min).** Skipped: F16 AC9 (box, Phase 3) and F19 AC4 (`fixme`).
    - **F19 AC9 and AC12 fail as before** (deviation 61: they fail the same way on the code before 14A): AC9's
      `getByText` also matches 13D's screen-reader status line; AC12's Back leaves the search box filled. Open, from 13D.
    - **F1 AC6 failed once**: the add dialog showed "The response is not a valid JSON response." on a duplicate add. It
      passed 3 of 3 alone right after, the same request through the REST server answers cleanly (201, then 200 with
      `duplicate` and `&is_duplicate=1`, nothing printed), and the trace was lost to the re-run. Treated as a one-off of
      the Local server; watch for it.
    - **Clean-up:** the 51 products the run made, a finished bulk run and `affibloom_settings` removed; the copy holds
      256 published products and only `affibloom_icons`, as before. User 14 (`vvadmin`, the test login) keeps its
      per-page and empty saved views.

66. **F19 AC9 and AC12 fixed (2026-11-13, `6a743ed`), the user: "Fix F19 first".** Both had failed since Task 13D.
    - **AC12, a real bug:** after a search the focus stays in the search box, and `reflect()` left the box alone while
      focused (so a late answer cannot overwrite newer typing). Back and Forward bring back a different search, so the
      box kept the undone term. `reflect( fromHistory )` now always sets it for Back and Forward; a search, a page link
      or a page size still leaves a focused box alone.
    - **AC9, the test:** its `getByText` found both the empty-search card and 13D's screen-reader status line, which
      rightly says the same words. It now looks inside `#affibloom-list`.
    - **F19: 13 passed, 1 skipped** (AC4 skips itself while the library holds more than 50 products). The project has no
      JavaScript lint script. 10 test products removed.

67. **Task 14B (2026-11-13, `9640685`), the product page's display card**, built as written, with these points:
    - **The card, in Lasso's order:** the image frame (the live preview's place, 14E) with "Update Image" (WordPress's
      media library, "Select an Image" / "Select Image", images only); Display Theme ("Default (<Single theme>)" and the
      eight themes) beside the image link ("Amazon Image" or "Product Image", Apply; Refresh disabled for Amazon, "Needs
      the Amazon API"); Badge Text (icon picker, placeholder "Our Pick") beside Price (show toggle; other links a one-line
      rich editor, Amazon read-only with "Amazon prices updated via Amazon API settings"); Description; Disclosure
      (placeholder the site's text); Sponsored, Show Disclosure, Detect Opportunities and Link Cloaking (non-Amazon) as
      "Coming soon", Show Prime Logo (Amazon). Commission Upgrades left out (Lasso's cloud).
    - **Product fields:** `theme` (`?Theme`, null = the type's), `badge`, `price`, `show_price` (`?bool`, null = the site's),
      `description`, `disclosure`, `prime`, `image_id`. **The site's defaults are applied by the screen, not by
      `hydrate()`** (the task text): the product keeps what is stored; `ProductScreen` (now given `SettingsService`)
      resolves Show Price and shows a disclosure equal to the site's text as empty (edge case 3).
    - **Advanced Editor Tools (`tinymce-advanced`, active here) rewrites every editor** with its menu bar (File, Edit,
      View, Insert, Format, Tools, Table) and toolbars. `Interface\Admin\ProductEditors` sets the two product editors'
      toolbars to Lasso's last (`tiny_mce_before_init`, `PHP_INT_MAX`), with no menu bar, and the price editor one line
      (TinyMCE's own 100px minimum overrode WordPress's height). Unit-tested.
    - **The editor normalises some descriptions** (the step's check over the five longest live ones): 3 byte for byte, 2
      with the same words and links but `rgb()` → hex, `&nbsp;` → space and attributes reordered. Nothing is saved yet;
      **Task 15 must store the description and price only when edited** (written into Task 15's text).
    - **Apply checks and shows only** (Lasso's "Image URL is required." / "Invalid image url.", `data:` refused); storing
      waits for Save Changes (D2); a pasted link clears the media item id.
    - **Also:** `switch-row.php` takes `soon` (disabled with the chip); the Show Price switch has a visible "Show Price"
      label (Lasso's has only a tip) so it has a name; the image button's media texts are `data-media-*` (a first
      `data-button` collided with F29's `[data-button]` blocks, which the F29 run caught); TinyMCE shows no "Enter a
      description" placeholder.
    - **Checks:** lint, PHPStan, 330 unit tests (new: display defaults, `ProductEditorsTest`). E2E: **F1 AC5 (right half)
      and AC7 red first, then passed; F1, F28, F29: 15 passed; F18's single adds and F1 AC6/AC8/AC9 passed.** Looked at
      1440 and 375px (Amazon and other products), the media frame, both Apply errors; no sideways scroll. 30 test
      products removed (5 found by ID: a `_` in a `LIKE` pattern is a wildcard); 256 published products again.

68. **Task 14C (2026-11-13, `0a24290`), custom fields on the product page**, built from Lasso's field code
    (`pages/fields/class-ajax.php`, `Field_Mapping::add_field_to_page`, the url-details library branch of
    `lasso_report_urls`, `Html_Helper::render_field_types`, the field row and the two dialogs):
    - **Domain** `Domain\Fields\{Field, FieldType, FieldLibrary, FieldPage, ProductField, ProductFields,
      FieldRepository}`. Built-ins keep Lasso's IDs 1-3 with this site's names ("Primary Rating"; Lasso 369's default
      is "Our Rating"); created fields get IDs from 100 in the option `affibloom_fields` (not autoloaded). A product's
      rows are `_affibloom_fields` in display order; storing them does not touch the post, so the Dashboard order is
      unchanged (Lasso's field saves leave the record alone).
    - **Lasso's rules kept:** an added rating starts at 3.5, every field starts visible with its name hidden; the rating
      is clamped as Lasso's box does (0, empty or not a number → 1.0, above 5 → 5.0, one decimal), here on the server
      too; Primary Rating stays first; "Create Field" stores the field and opens the library (it adds it to no
      product, as Lasso's); the library lists ten a page (Lasso's url-details limit), searched by name in any case;
      the seven types with Lasso's labels (Short Text … Numbered List); the remove question 'Remove "<name>"?' / "If
      removed, you won't be able to get its value back."; every change stored as it is made (D3).
    - **Different, removing nothing:** rows move with **Move up / Move down** instead of Lasso's drag (also by keyboard);
      a field the product has shows "Added" in the library (Lasso's re-add is a no-op); a refused title says why (Lasso
      is silent); long text and lists keep their lines, other values are one line, HTML is stripped; "Manage Fields"
      is "Coming soon" (the Fields area).
    - **Routes:** `GET/POST /fields`, `POST/PUT /products/{id}/fields`, `DELETE /products/{id}/fields/{field}`
      (`PRODUCTS`; 404, 422); add and remove answer with the rows drawn again, a save with the stored list only, so the
      focus stays where it is.
    - **PHPCompatibility 9.3.5 predates enums** and flags `$this` in `FieldType`'s methods; that one sniff is excluded for
      that one file in `phpcs.xml.dist`, with the reason (as deviation 19 did for `Interface` namespaces).
    - **Checks:** lint, PHPStan, 350 unit tests (`FieldLibraryTest`, `ProductFieldsTest`, `FieldsServiceTest`). REST through
      WP-CLI (options and postmeta backed up first: `app/affibloom-backup-2026-11-13-task14c.sql`): logged out 401, an
      empty title and an unknown type 422, a missing product 404, an unknown field 422, a rating of 7 kept as 5.0 and
      moved first, HTML stripped, remove; all undone. **F1 AC10 red first** (no dialog), then passed after making its
      label lookups exact (the row's help button is "Help for Primary Rating"); **F1, F28, F29: 16 passed.** Looked at the
      rows, both dialog tabs and the remove question at 1440 and 375px; the row actions moved into each row's header so
      the value has the full width. 23 test products and the test fields' option removed; 256 products, only
      `affibloom_icons`.

69. **Task 14D (2026-11-13, `0ee6801`), the Share kit and the tracking-ID offer**, built as written, with these points:
    - **Share kit** (`partials/share-kit.php`, a region named "Share kit" under the band): Copy shareable URL (the
      destination until link cloaking, D5), Copy destination URL (the field as typed), Copy disclosure (the field, else
      the site's text); Copy shortcode and Copy block disabled, "Coming soon" (Task 18S, Phase 4). The visible text
      says "Copied!" for 1.5 s while each button's name stays its action.
    - **This Local site is plain http, so browsers give the page no clipboard API**: product.js copies with Lasso's
      selection fallback there (on the real https site the clipboard API is used). F1 AC11 records what either way
      copies instead of reading the clipboard.
    - **Tracking-ID offer** (`partials/tracking-id-notice.php`; Lasso's amazon-url-detected, naming Affibloom): on an
      Amazon product when the site's tracking ID is empty, the link's tag (`AmazonUrl::tag()`, new) with Yes / No, or
      "Set Amazon Tracking ID" (Settings → Amazon) when the link has none. Yes posts to
      `TrackingIdController` (admin-post, `SETTINGS`, nonce), which calls the new `SettingsService::set_tracking_id()`:
      the Amazon tab's rule, the tracking ID alone (an `update_tab( 'amazon', … )` would turn every unposted checkbox
      off). Back on the page: "Tracking ID Saved!" (Lasso's) or a refusal; No hides it for this visit, as Lasso's. The
      product page now prints the toast (`Notice`). **On this copy the offer shows on every Amazon product**, since no
      tracking ID is saved here; production has `vbvault-20`.
    - **F1 requirements gained AC12** for the offer (it had no acceptance criterion).
    - **Checks:** lint, PHPStan, 352 unit tests (new: `set_tracking_id`, `AmazonUrl::tag`). **F1 AC11 and AC12 red first**,
      then passed; **F1, F28, F29, F17: 23 passed.** Looked at 1440 and 375px; No hides the offer. **One page load at
      375px failed inside WordPress's own script and style bundles** (jQuery undefined, no styles); four reloads right
      after were clean, so it is a Local server one-off like F1 AC6's (deviation 65), recorded to watch. 32 test products,
      the test fields' option and F17's settings leftover removed; 256 products, only `affibloom_icons`.

70. **Task 15 superseded by 15A-15D (2026-11-13), from a sweep of Lasso's save path** (`docs/features/F1/`
    requirements section 9 and analysis "Saving", gates 1-2 re-run; the old text moved to the phase file's appendix).
    The user said "Go, Phase 2 - Task 15"; as "Next up" asked, the save path was swept before building. Read:
    `save_lasso_url` (AJAX entry and `Lasso_Affiliate_Link` 1085-1897 with its helpers and cleaners),
    `get_amazon_product_url()`, the repoint merge, the post-save link scan, the page script's save handler, leave
    guard and baseline, and the save, unsaved, success and failure templates. It contradicts the old Task 15 here:
    - **Lasso refuses only an empty Name or Primary URL** ("Name and Primary Destination URL are required."): no
      lengths, no second-URL check, no rating (Primary Rating is a custom field since 14C). Field errors under the
      field keep that one sentence (D11).
    - **Wording:** Lasso's toast is "Yee haw! Your link saved.", not "… product saved."; U6 (approved 2026-11-12) makes
      it "Product saved." (D10). The save also shows "Updating Your Site" (S14).
    - **F29 D5 answered:** every button's link gets the secondary's rule: geni.us to its destination unless kept,
      `https://` added, Amazon links always get the site's tag rule (short and protected links as typed), never
      refused (S4).
    - **D12:** Lasso tags a newly pasted ASIN only on the second save (a lookup quirk); Affibloom stores the
      second-save form at once.
    - **The repoint offer edits no post** (`Lasso_Repoint_Merge`: "Host post_content and shortcodes are not
      rewritten"): the earlier note (deviation 57, "Next up") that it rewrites posts was wrong. It is built (15C,
      D14): the retired product keeps `_affibloom_repointed_to` and goes to the trash; Tasks 18, 27 and 35 got notes to
      follow the pointer. **What does rewrite posts is Lasso's post-save link scan** (S9): not built, the user's rule
      (deviation 52); its effect, text links following the product's attributes, is written into area 1 (F21) as a
      render-time requirement (D13).
    - **Owned elsewhere** (D15, D16): a trashed duplicate's restore-and-refuse rule → Task 17's sweep; the save-time
      link re-check → Task 32 (note written); history, slug and cloaking, Groups, opportunities, locations → their
      areas; affiliate-program logos and "extend product" data → never (Lasso's cloud); `show_description` is derived
      from the description (D20, Task 26 note).
    - **Found missing in Affibloom:** the Settings tabs have no unsaved-changes guard, which Lasso's have (U7 parity).
      Not part of Task 15; open item for the General settings pass.
    No code changed; docs `4f7c25c`. **Gate 4 given by the user 2026-11-13** ("approved. go for it.").

71. **Two broken button icons fixed (2026-11-13, the user: "two icons are shows broken. full review and youtube. fix
   it.")**, F28 D4 and AC7. Cause: the site's Additional CSS (post 5977) linked both to `uploads/2023/04/…svg`, which
   never existed (404 on production as well); no text used them. The user chose "Fix CSS + flag": after a backup
   (`app/affibloom-backup-2026-11-13-icons.sql`) the Local CSS was repointed (Full review → `2023/05/…` attachment
   9501; YouTube → a new white play SVG, attachment 15696, `2026/11/youtube-icon.svg`) and `affibloom_icons` deleted so
   it re-imported. Code: `IconChoices` marks a row `missing` when its file is not there; a shared partial
   `icon-picture.php` draws a warning tile instead of the image in the card and the pickers. **Open for the user:
   production's CSS needs the same two links and the SVG uploaded.** The fixture `tests/fixtures/icons/
   site-additional-css.css` stays the old snapshot (it is production's CSS as it is).
   **Follow-up the same day (the user: "It looks broken again"):** a `reset-local` at about 15:41 reloaded the snapshot
   and undid the database fix. `tools/local-icon-links.php` now re-applies it, run by `reset-local.sh` after every
   reset (idempotent; backup `app/affibloom-backup-2026-11-13-icons-2.sql` before the first run). The YouTube
   attachment is 15130 after this run. Retire the step when production's CSS is fixed and the snapshot refreshed.

71. **Task 15A (2026-11-13, `59ee8da`), Save Changes stores the Details form**, built as written (deviation 70), with these
    points:
    - **`LinkNormaliser` took only the add flow's Amazon step** (`tagged()`), not its geni.us step: `create_from_url()`
      follows a geni.us link with the same request that names the product, so moving it would cost a second request.
      `button()` follows a geni.us link itself (S4).
    - **No repository unit test exists** (the repository is covered by integration tests, which do not run here); the
      storage was checked on Local by a WP-CLI script instead: 422 for an empty name with the title unchanged, 200
      with the button link tagged, the untouched description byte for byte, author and date kept, 400 for a wrong
      type, 404 for an unknown ID, the price's `rgb()` → `#hex` and its link tag dropped.
    - **`save()` writes every field for a new product too**, as Lasso's `meta_input` does, and removes `_affibloom_buttons`
      when no button is left. An update goes through `wp_update_post()` so author, dates and slug are kept.
    - **`ProductInput` throws at once on an empty name** (Lasso's only refusal) instead of collecting refusals; the
      422's message is that sentence.
    - **Lasso's `<p><br></p>` → '' is not copied:** it is Quill's empty content, cleaned in Lasso's script; TinyMCE
      gives ''.
    - **Show Price is stored on every save** (the switch's state), as Lasso sends `show_price` every time; until a save
      the product follows the site's setting.
    - **The failure toast** is the design system's toast with a white cross on danger-700 (design doc's Toast row).
    - `admin.js`'s "Enter must not submit" guard (`data-not-saved`) is gone with its only caller.
    - **Checks:** lint, PHPStan, 380 unit tests (new: `PriceHtmlTest`, `LinkNormaliserTest`, `ProductInputTest`,
      `ProductServiceUpdateTest`). By hand in Brave (the user's choice over the app's pane): an empty name → Lasso's
      sentence under Name, focus there, the failure toast, nothing stored; a save with a renamed product, a moved
      button, a typed `www.amazon.com/dp/…?tag=someone-20`, an empty extra block, badge, theme and Sponsored →
      "Updating Your Site" with "Just saving your URL changes", "Product saved.", the link shown tagged, every value
      in the meta, the empty block not stored, the description untouched; typing in the description and pressing
      Enter in Name → saved, the edited description stored. Test product removed. **F1 AC2 and AC13 not run yet**
      (the user's signed-in run). Database backup before the checks: `app/affibloom-backup-2026-11-13-task15a.sql`.
    - **Two of 15A's files went out in another session's commits:** while 15A was uncommitted, a parallel session working
      in this checkout committed and pushed `e522c60` and `01e04ec` (button icons), which picked up 15A's `admin.css`
      rules (the failure toast, the save dialog) and the design doc's Toast row. They are on `main`; history was not
      rewritten. `59ee8da` holds the rest of 15A. Two sessions should not share one checkout while both commit.

72. **Task 15B (2026-11-13, `7e77a89`), a new Primary URL on Save Changes**, built as written, with these points:
    - **Built in a worktree** (`C:/Users/Rupash/affibloom-wt-15b`, branch `task-15b`, its own copy of `vendor`) while the
      user's F1 run used the live plugin folder, then fast-forwarded into `main` once the run ended; worktree removed,
      branch `task-15b` kept (merged; delete on the user's word).
    - **`LinkNormaliser` gained `typed()`, `follows()` and `primary()`**: the add flow's first steps moved there on their
      second use and `create_from_url()` calls them; its control flow (duplicate checks between steps) is unchanged, so
      adding still makes at most one request. `primary()` returns `NormalizedLink` (typed, stored, the Amazon link).
    - **An unchanged Primary URL is not followed again**; an Amazon one gets the tag rule again (D12). A link that is not
      a link is refused with the add flow's "This is not a valid link." (adding refuses it too); an empty one with S2's
      sentence; an empty name and link are reported together.
    - **The rename uses the name being saved**, as Lasso tests the title in the payload; a media library image stays
      when the ASIN changes, as Lasso's `thumbnail_id` wins.
    - **The reload on a type change is decided in the page** (the answer's type against the page's `data-product-type`)
      rather than a `reload` field in the answer; it lands on the page with `affibloom_notice=product-saved`.
    - **The followed short link is covered by unit tests only** (Local blocks outside requests), as F2 AC7 is.
    - **Checks:** lint, PHPStan, 394 unit tests (new: `PlaceholderNameTest`, `ProductServiceLinkTest`). By hand on
      Local through the PATCH route with WP-CLI (Brave's tab would not open the site this time): another ASIN on
      "Amazon" → renamed "Mikasa V200W Volleyball", ASIN and store updated, image cleared; the same ASIN → name kept;
      another shop → type other, ASIN key deleted; empty name and link → 422 with both; "not a link" → 422. Local's
      tracking ID was empty after the user's run, so retagging was not visible there (unit tests cover it). **The page's
      reload on a type change was not seen by hand**: F1 AC14 covers it, and **passed in the user's rerun** (5 of 5).
    - **Test fixes from the user's run:** F1 AC2 and AC13 type into the icon fields' word boxes (`bae3c86`); F16 AC6
      waits for "Settings saved" before reading the option (`4e3b939`).

73. **Task 15C (2026-11-13, `0da4b16`), "This Product Already Exists" and the repoint**, built as written, with these points:
    - **The duplicate lookup on save is `ProductService::owner()`**: the link as typed, where it leads, then the add
      flow's `existing_amazon()` (its second caller). `create_from_url()` keeps its own order, which checks the typed
      link before any request; the two share `existing_amazon()` and `LinkNormaliser`, not the whole sequence.
      `NormalizedLink` gained `destination` for it.
    - **Nothing is saved on a duplicate**, the other fields included, as Lasso answers before its insert.
    - **The 409 answer carries both products** (id, name, link, page); the dialog's second panel fills from it. Cancel
      and Escape close it and keep the typed link; Cancel takes the focus first, so Enter does not confirm.
    - **Repoint refusals answer 400 with Lasso's sentences** (`RepointRefused`, five reason codes in `Messages`).
    - **The success notice uses Affibloom's `affibloom_notice=repointed`** on the kept product's page, not Lasso's
      `repoint_merged=1`; the words are Lasso's ("Success! Locations now show this link.").
    - **`retire()` writes the pointer before the trash** and checks it, as Lasso does before its soft delete.
    - **Checks:** lint, PHPStan, 402 unit tests (new: `ProductServiceRepointTest`). By hand on Local through the routes
      with WP-CLI (Brave's tab would not open Local again): 409 with both products and nothing saved; mismatch and
      same-ID refusals with Lasso's words; Confirm → `redirect_to` the other product with `repointed`, this product in
      the trash with `_affibloom_repointed_to`; a second Confirm → "Source link is no longer active."; saving the
      retired product → 404. **The dialog itself was not seen by hand**: F1 AC15 covers it, and **passed** in the user's run.

74. **Task 15D (2026-11-13, `b3202d7`), unsaved changes on the product page**, built as written, with these points:
    - **The snapshot is the save's own payload** (`collect()` as JSON): every named field and switch, the buttons and
      their order, and the two editors (in it only when TinyMCE marks them changed). Custom field rows have no names
      and save themselves, so they never count. Taken on `load` and on the next tick (or at once when the page has
      already loaded), and after every save.
    - **`admin.js` now offers its modal to page scripts** (`window.affibloomModal.show/hide`), so "Unsaved Changes" gets
      the design system's focus trap, Escape, backdrop and focus return; the clicked link is the opener.
    - **Links that do not count**, as Lasso's guard: `#…`, `javascript:`, a new tab, any link inside a dialog; also a
      click with a modifier key or a middle button, which opens another tab and loses nothing.
    - **Save Changes in the dialog submits the form** and goes on only after a 200; a refused save, or one that turns
      into "This Product Already Exists", stays. A save that changes the type goes to the link, not the reload.
    - **`beforeunload` stays quiet while saving and after Leave, a repoint or a reload Claude's script starts.**
    - **Not checked by hand:** Brave's tab would not open Local for Claude (three tries this session). F1 AC16 covers the
      four dialog cases and the browser's own prompt, and **passed in the user's run** (2026-11-13). Lint, PHPStan,
      402 unit tests, `node --check` passed.

75. **Bulk add: Shift + Enter in the textarea starts a new line (2026-11-13, the user's bug report).** Lasso (and
    11C, following it) sent Shift + Enter in the bulk textarea back to the single field with the first link, so
    following the hint ("Enter each new link on a separate line (Shift + Enter)") lost every link after the first.
    Now Shift + Enter in the single field opens the textarea with the caret already on line 2, and in the textarea
    Shift + Enter keeps the browser's newline; closing the dialog still goes back to one link. `admin.js` (the
    textarea's keydown handler removed), F18 AC6 rewritten to type three links, F18 requirements entry point 7 noted.
77. **Bulk add: one click on Add Link from the textarea submits (2026-11-13, found while adding three links in Brave).**
    Pressing the button blurred the textarea, the blur swapped it for the shorter preview, and the button moved
    away before mouseup, so the first click did nothing. Lasso has a mousedown workaround for the same thing. Now the
    button's mousedown keeps the focus in the textarea, and `addMany` shows the preview itself once the confirm is
    accepted. F18 AC6 gains a final step: one click from the textarea reaches the confirm (dismissed).

76. **Task 16 superseded by 16A-16C (2026-11-13), from a sweep of Lasso's Dashboard quick edit** (`docs/features/F1/`
    requirements section 10 and analysis "Dashboard quick edit", gates 1-2 re-run; the old text moved to the phase
    file's appendix). Read: `admin/views/rows/url-row.php`, `admin/assets/js/view-js.php` 150-1050, the Dashboard's
    modals, and `save_lasso_url` for a partial payload. It contradicts the old Task 16 here:
    - **The row itself becomes the form** (no second row), with Groups and the link line; **the image comes only from
      the media library** (no typed link); one row at a time; Cancel without asking.
    - **The save is partial** (name, link, image, groups); everything else keeps its stored value, the description
      explicitly. Affibloom's PATCH input is already partial (15A), so no server code changes.
    - **After a save Lasso reloads the current page of the list**, rather than patching the row; the saved product
      moves to the top by F19's order.
    - **An unsaved-changes guard** over every list control, Edit on another row, links and `beforeunload` (16B).
    - **The duplicate case (D21):** Lasso's server answers with the repoint offer, but its Dashboard script reports
      "Your link saved." while nothing was saved. Affibloom opens 15C's "This Product Already Exists" panel (16C).
      Claude's decision from the records (it removes nothing Lasso does); the user may overrule it at gate 4.
    - **The save dialog says "Just saving your URL changes"** (D22): Lasso's Dashboard line describes the link and
      keyword scan Affibloom does not run.
    - **Groups is a "Coming soon" placeholder** (D24), as on the product page; the Groups area owes it in both places.
    - **Not Task 16:** Lasso's "quick detail" modal (`url-quick-detail.php`) of the post editor's display flow and of
      Opportunities (D32) → Phase 4 and area 3.
    - **Shared code** (second use): the toast, the save dialog and its repoint panel, the field errors and the media
      frame move from `product-save.js`/`product.js` into `admin.js` in 16A and 16C.
    No code changed; docs `ab709c9`. **Gate 4 given by the user 2026-11-13** ("Approve, start 16A").

78. **Task 16A (2026-11-13, `852cb00`), the Dashboard row's quick edit**, built as written (deviation 76), with these points:
    - **The unsaved dialog waits for 16B**, its caller ("nothing before its caller"); 16A's table and 16B's Files were
      corrected. The Dashboard gets the save dialog and the toast template; the toast template became a partial
      (`toast-template.php`), now used by the product page and the Dashboard.
    - **The media frame had three users, not two:** the Display tab's icon card (F28) opened its own as well. All three
      now call `admin.js`'s `affibloomMedia.pick( { title, button, types }, then )` (one frame kept per title and
      types); the Settings tab scripts load after `admin.js`. F28 6/6 still pass.
    - **Field errors, shared:** clearing a note now removes only its id from `aria-describedby` (and the attribute when
      nothing else is left); the product page's copy left a stale id when the note was the only description.
    - **A list load during a quick-edit save waits:** after a good save the held list (if any) is loaded instead of the
      current one; after a refused save it is dropped and the row stays open. Until 16B guards the controls, a search or
      page link while a row is open (not saving) redraws the list and the open row goes with it.
    - **The save writes every field** (15A's `save()`): a product whose meta lacked a key (seeded test products) gets
      it with the value it was already read as (`_affibloom_theme` '', `_image_id` 0, `_show_price` '', …). Checked
      by a before/after `wp post meta list` on a product with buttons, badge, price, description and disclosure: only
      the title and those missing keys changed.
    - **Checks:** lint, PHPStan, 402 unit tests, `node --check` on the six scripts. **F1 AC17 and AC18 failed first**
      (Edit is the disabled placeholder), then passed; the whole F1 spec and F28: 24 passed, 1 failed, AC4 (delete, Task
      17). Run from Claude's shell on the saved sign-in through a scratch config without the global setup, so no
      password was typed. By hand (scripted screenshots on the same session): the open row at 1280 px and 375 px, the
      "Update Image" overlay at 4×; the empty-frame icon now hides under the overlay. Backup before the runs:
      `app/affibloom-backup-2026-11-13-task16a.sql`. **Local not reset after the runs** (test products left): a parallel
      session was using the copy.
    - **A parallel session committed in this checkout meanwhile** (`b87a08c`, `e787599`, deviation 77): its commits
      hold only its own lines (8 in `admin.js`); 16A's commit holds only 16A's files.

79. **The quick edit redesigned in Lasso's card layout (2026-11-13, `bf282a7`; the user: "The UI of the Quick Edit panel
    is very poor. Redesign it to follow the same structure and layout as Lasso, while maintaining our own color scheme,
    typography, and visual style")**, during 16B. Built from Lasso's `lasso-dashboard.css` 1846-2000 as well as its
    markup: the row is a brand-50 band holding a white card (shadow, 16px radius); the fields take 2 of 3 columns and
    an image panel 1 of 3, its dashed frame stretched to the fields' height (picture up to 180px, or the icon and "No
    image"), "Update Image" over the whole frame in brand-600 on hover and focus; Groups is a dashed, inactive field
    with Lasso's placeholder "Select a group" and a Soon chip; the link line has a link icon; a footer under a
    divider holds Save Changes and Cancel, right, at least 128px; one column with full-width buttons when narrow. New:
    a hint under the frame ("Click to choose from your Media Library."), since the overlay is invisible on touch
    screens. **The card is sized to the visible part of the scrolling table** (`container-type: inline-size` on the
    scroll area, `100cqi`, sticky left), as the table is 1076px at least. The design doc's "Quick edit row" was
    rewritten. Looked at in screenshots at 1280px, 1024px and 375px: with and without a picture, hovered, focused, with
    the field error. **F19 AC11 updated:** it still expected the placeholder Edit (16A's miss). The user's standing rule
    from this request (best-effort, polished UI on every screen) is in Claude's memory.

80. **Task 16B (2026-11-13, `674c24c`), unsaved changes on the Dashboard**, built as written, with these points:
    - **`guarded( action, opener, redraws, stay )`** wraps the search, page links, page size, link type, Reset filters,
      saved-view pills, another row's Edit, Back/Forward and links; with no change (or while the row is being saved,
      when a list load already waits for the save) it runs at once. `stay` puts back what × must undo: the page
      size's or link type's select value, and after Back the list's address (`pushState` of the address on screen).
    - **Save Changes in the dialog:** an action that draws the list itself (or leaves) runs after the good save; another
      row's Edit runs after the list is drawn again, so the saved row shows what was stored. A refused save keeps the
      row and drops the action, as Lasso's `on_error`.
    - **What counts as a change:** the four values the save sends (name, link, image link, media item) against those
      when the row opened.
    - **The unsaved dialog came to the Dashboard here** (16A left it for its caller); `unsaved-modal.php` notes both
      pages.
    - **Checks:** lint, PHPStan, 402 unit tests, `node --check`. **AC19 failed first** (the search ran without asking),
      then passed after one fault in the test itself (its last step typed the name already stored, so correctly nothing
      asked). F1, F19 and F28: 36 passed, 1 skipped, 3 failed: AC4 (Task 17), F19 AC11 (fixed, see 79) and **F1 AC14,
      which was leftover data, not code:** earlier runs today left products holding AC14's links, so its save was
      refused as a duplicate (15C). Claude deleted those three test products (15146, 15193, 15228); AC14 then passed.
      The suite is not repeatable without a Local reset; **Local still needs one** before the user's next full run.
      By hand: the dialog over the Dashboard, and the focused frame (the empty frame's icon now hides under the overlay).

81. **Task 16C (2026-11-13, `6471087`), the same link as another product, from the quick edit**, built as written (D21), with
    these points:
    - **The repoint panel moved to `admin.js`** (`affibloomRepoint.offer( from, data, url, { cancelled, leave,
      failedText } )`): Cancel, Escape and Confirm are bound once; each page passes where the focus goes after Cancel
      and how it leaves after Confirm (its own `go()`, which quiets its leave guard). `product-save.js` lost 44 lines.
      The product's ID comes from the caller, since the 409's `from` may be null.
    - **The quick edit's 409:** the save's flags are reset at once (no "Updating Your Site" close), the offer opens, and
      the action "Unsaved Changes" was holding is dropped (16B), as for any refused save.
    - **The dialog's question got room** (`.affibloom-repoint__intro`: 8px above, 20px below, 14px type): it sat on the
      first link box. Shared, so the product page's dialog changed too.
    - **The user reset Local mid-task**, which removed AC20's products during Claude's first run and the saved
      sign-in, so Claude could not run AC20 red. The user's run (AC15, AC17-AC20): **AC15, AC17 and AC20 passed** on the
      new code; **AC18 and AC19 failed on fresh-copy assumptions in the tests**, fixed: AC18 picks the newest image by
      date (the media library's order) and opens the "Media Library" tab (WordPress opened "Upload files" on the fresh
      copy); AC19 fills the library to eleven products when it has fewer, so a second page exists (two-minute limit).
      Both then passed from Claude's shell on the user's new sign-in.
    - **Checks:** lint, PHPStan, 402 unit tests, `node --check`. The full F1 and F28 run on the reset copy: 25 passed,
      2 failed: AC4 (Task 17) and AC16 with a Playwright-internal error ("Object … was not bound in the connection",
      the reload-and-dialog race in its last step); AC16 then passed twice on its own. By hand: the offer over the
      Dashboard in a screenshot, before and after the spacing fix.

82. **Task 17 superseded by 17A-17D (2026-11-13), from a sweep of Lasso's delete paths** (`docs/features/F1/`
    requirements section 11 and analysis "Deleting", gates 1-2 re-run; the old text moved to the phase file's
    appendix). Read: modals `url-delete`, `url-delete-reject`; `url-details.php` (footer, leave guard, delete and force
    delete); `lasso_delete_post`, `lasso_bulk_remove_links`, the location count, the force delete's four helpers; the
    Dashboard selection in `view-js.php`; `is_lasso_url_exist` and the add and save duplicate branches;
    `Lasso_Repoint_Merge`; the `lasso_delete` status; WordPress's `wp_scheduled_delete`. It contradicts the old Task 17:
    - **No delete in a Dashboard row.** Lasso deletes from the product page's footer ("Delete This Link") and from the
      selection bar (the ticked rows).
    - **Two kinds of delete:** the page's is permanent (`wp_delete_post( id, true )`, "If deleted, you won't be able to
      get it back."); the bulk one sets the status `trash` through `wp_update_post`, so WordPress's 30-day emptying
      (which reads `wp_trash_post()`'s mark) never removes it. **The user chose Lasso's split (D33)** over the old
      text's trash for both.
    - **In use, Lasso's "Hold Up" offers a force delete** that removes the product's links and shortcodes from every
      post. **The user chose "Block only" (D34):** Task 36 shows the warning with Keep This Link only (its phase file
      has the note).
    - **A trashed or retired record comes back** when its link is added again (Lasso publishes it again); on save the
      match is published and the save refused (S11). 17D builds it; a retired product loses its pointer when it comes
      back (D42, Claude's decision: with Lasso's kept pointer the revived product could never be shown).
    - **Two corrections to built tasks:** 13C's Select All across pages is not Lasso's (its selection is the page's),
      and a bulk delete must not reach rows the editor cannot see (17B, D36); 15C retires to the trash, which
      WordPress empties after 30 days, losing the pointer (17C: status `affibloom_retired`, Lasso's `lasso_delete`;
      no upgrade step, nothing has shipped, D41).
    - **Claude's other decisions (D35-D43):** "Product deleted." / "N products deleted." toasts (D37); a confirmed
      delete quiets the page's leave guard and the bulk Delete goes through 16B's guard (D38); the two routes (D39);
      forward notes in Task 18 (the pointer chain stops at the last product that exists), Task 26 (Lasso's `trash`
      and `lasso_delete` records) and Task 36; the overview's REST and data rows updated.
    - **Said wrongly in the question to the user:** "WordPress empties the trash after 30 days" for the Dashboard's
      delete. That holds for `wp_trash_post()`, not for Lasso's `wp_update_post` (checked in core after the answer);
      D40 records Lasso's way, under which trashed products stay until re-added. Reported to the user.
    No code changed. **Gate 4 given by the user 2026-11-13** ("Approve, start 17A").

83. **Task 17A (2026-11-13, `d7a0493`), Delete This Link deletes for good**, built as written (D33, D37, D38), with these
    points:
    - **The repository reports a refusal as `false`, and the service throws** (the plan's table had
      `PostTypeProductRepository::delete()` throw a `RuntimeException`). With the throw in the repository, PHPStan saw
      the controller's `catch ( RuntimeException )` as dead (the service declared only `ProductNotFound`), and
      declaring both on the service broke WPCS's throw-tag count. `ProductService::delete()` now throws both itself
      and declares both; a unit test covers the refusal.
    - **The Dashboard shows the redirect toast** for the first time: `DashboardScreen` takes `Notice` (Plugin.php) and
      `dashboard.php` renders the `toast` partial; files the plan's list did not name. The dialog carries the
      Dashboard's address and the failure sentence as data attributes; its Delete shows "Deleting…" while busy.
    - **The dialog's first focus is ×** (Close), as every Aurora modal (`showModal`); Escape and Cancel give the focus
      back to Delete This Link.
    - **Checks:** lint, PHPStan, 406 unit tests, `node --check`. **F1 AC4 failed first** (the button was the
      placeholder), then passed; AC2, AC13, AC15 passed; AC16 once hit deviation 81's Playwright-internal race, then
      passed alone; **AC14 failed on leftover data, not code** (as deviation 80): products from earlier runs held its
      link and ASIN. Claude deleted those test products (15150, 15183, 15188, 15190, and 15191 after the passing run);
      AC14 then passed. Run from Claude's shell on the saved sign-in (`.local/no-login.config.ts`), no password typed.
      Routes through `rest_do_request`: anonymous 401; an unknown ID and a page's ID 404 (the page untouched).
    - **By hand** (scripted screenshots on the saved sign-in): the footer, the dialog at 1280 px and 375 px, Escape and
      focus, the busy state, a real delete of a product made for the look (15192): the Dashboard with "Product
      deleted.", no post and no meta left. Backup before the runs: `app/affibloom-backup-2026-11-13-task17a.sql`.

84. **Task 17B (2026-11-13, `f61e12c`), the selection bar's Delete moves the ticked rows to the trash**, built as written
    (X5-X7, D36-D40), with these points:
    - **The IDs are checked by the route's schema** (`array` of integers >= 1, `minItems` 1, `maxItems` 50): WordPress
      answers 400 `rest_invalid_param` / `rest_missing_callback_param`, not the plan's own `affibloom_invalid_ids`; one
      rule fewer to keep. Checked through `rest_do_request`: none, empty, 51, text → 400; anonymous → 401; an unknown ID
      and a page's ID → 200 `{ deleted: [] }`, the page untouched.
    - **No "page before" logic in the script:** the list route already shows the last page when the asked one is past
      it (13A), so the reload is `load( current )`.
    - **The selection is Lasso's** (D36): `selected` IDs only; Select All and the header box tick the page's enabled
      boxes; every `load()` keeps only the IDs drawn (`keepDrawn()`, Lasso's filter after `get_data_via_ajax`). The old
      "all" mode, `total()` and the list card's `data-total` (its only reader) are gone; the stale `data-range` line in
      `product-list.php`'s docblock went with it.
    - **Words:** the count and the toast come from PHP `_n()` one / many templates on the bar (the admin scripts use no
      `wp.i18n`); `delete-modal.php` takes its id, title and sentence (its second use: the product page's and the
      Dashboard's). The bar's Delete is the small danger button (Lasso's red); a design-system row "Selection bar" was
      added, and below 782px the wrapped bar takes the card radius instead of a stretched pill (seen at 375px).
    - **Checks:** lint, PHPStan (one finding fixed: with `$wp_error` true `wp_update_post()` never returns 0), 409 unit
      tests, `node --check`. **F1 AC21 and F19 AC11 failed first** ("2 selected"), then passed; F1 AC4, AC17-AC20 and
      F19 passed, except **F19 AC12 in the long run** (it ran past its 60 s limit on slow WP-CLI calls and reported the
      unfiltered list); alone it passed in 26 s. By hand (scripted screenshots on the saved sign-in): the bar and the
      dialog at 1280 and 375 px; with a changed quick edit, Delete asked "Unsaved Changes" first and Leave opened the
      dialog; "Deleting…"; then "2 products deleted.", the bar hidden, the focus on the list, both `trash`, the third
      `publish`. The look products (15222-15224) deleted afterwards. Backup: `app/affibloom-backup-2026-11-13-task17b.sql`.
    - **Line endings:** files rewritten with Windows Python had CRLF in the working copy (`dashboard.js`,
      `product-list.php`, the F1 and F19 docs, `progress.md`, several plan files; WPCS caught one). The commits were
      not affected (`.gitattributes` `eol=lf`); every such file is LF again, and later scripted edits write bytes.

85. **Task 17C (2026-11-13, `c86378f`), retired products get the status `affibloom_retired`**, built as written (D41):
    - `ProductPostType::RETIRED` registered on `init` (not public, `internal`, out of search and core's lists);
      `retire()` sets it through `wp_update_post( …, true )` after the pointer, in place of `wp_trash_post()`. Every
      lookup, the list and `count()` select `publish` only, so nothing else changed.
    - **AC22 is its own test** through the repoint route (`wp.apiFetch` on the product page, as the dialog's Confirm
      calls it), rather than a line in AC15; AC15 and AC20 now expect `affibloom_retired` too. AC22 failed first
      ("trash"), then passed with AC15 and AC20. Lint, PHPStan, 409 unit tests green.
    - **By hand on Local:** the status is registered ("Retired", not public); the three products these runs retired
      carry it and their pointers, with no `_wp_trash_meta_time`; `wp_count_posts`: publish 64, retired 3, trash 21
      (test products; Local still needs a reset). The four products 15C's earlier runs put in the trash stay there
      (test data; no upgrade step, D41).
    - Notes that still said "in the trash" now name the status: Task 18's 15C note, F1 D14 and the analysis's
      "Saving" path.

86. **Task 17D (2026-11-13, `82b6015`), a deleted product comes back when its link is added again**, built as written
    (X9, D42), with these points:
    - **`find_deleted()` takes a list of link keys** (the typed link, where it leads, the stored form) instead of the
      plan's one key: the add flow checks published products at three points, so the deleted lookup runs once, where
      a product would be created (both the non-Amazon and the Amazon branch), and in `with_link()` when no published
      product owns the link. A published product always wins; within an arm the newest deleted one does (Lasso's
      inactive-record order). `find_one()` gained the statuses as a parameter (published: lowest ID first; deleted:
      newest first), its second use.
    - **`revive()` also puts the slug back:** the trash gave it a `__trashed` suffix and kept the old one in
      `_wp_desired_post_slug` (core's `wp_insert_post`), which `revive()` restores and removes, as `wp_untrash_post()`
      would. Seen by hand (`task-17d-look-gone` back, the meta gone).
    - **The in-memory repository's IDs never repeat now** (a counter instead of `100 + count`): with products leaving
      the list, a new product reused a trashed one's ID and two revive tests passed for the wrong reason.
    - **AC14 uses fresh ASINs and a fresh link per run:** with 17D, a trashed leftover that holds them brings itself
      back and refuses AC14's save, so its fixed values made it depend on the trash. **AC23 has a two-minute limit**
      (about 45 s of WP-CLI checks on Local), as AC19 has.
    - **Checks:** lint (this task's files; see below), PHPStan (a redundant null check removed), 414 unit tests,
      five new ones red then green. **F1 AC23 red** (a new product's page opened), **then green**; F1 AC1, AC6, AC14,
      AC15, AC20-AC22 and all of F18 green. By hand: the quick edit's refusal on the Dashboard (Lasso's sentence
      under Primary Destination URL, the row open, the deleted product published, this one unchanged), screenshot.
      Backup: `app/affibloom-backup-2026-11-13-task17d.sql`.
    - **Found during the run, not Claude's doing:** at 20:43:18 a page of ten products (15226-15239, the top of the
      Dashboard then) was moved to the trash through the selection bar's Delete (they carry `_wp_desired_post_slug`
      and no trash time, the 17B route's mark) while Claude's run was going; AC14's product was among them, which
      failed it once. Local keeps no access log, so the sender is unknown; most likely a check of 17B in a browser.
    - **`affibloom.php` changed outside these sessions** (file time 19:50 today): the header now says "Requires at
      least: 7.1.4" and "Requires PHP: 8.3" (the plan: 6.5 and 8.1) and the file is reformatted without WordPress's
      spacing, so `composer lint` fails on it (31 errors). Left untouched and out of the commit. **The user, 2026-11-13:
      "I'll handle it"**: Claude does not touch it; until it is settled, `composer lint` fails on that file alone.
87. **A staging build for the client's review, with a one-time Lasso import (2026-11-13, `447bf1b`), outside the
    phase order.** The user asked for a zip of what is done so far, to upload to staging
    (https://staging.volleyballvault.com/), and for Lasso's products to be imported once so the client sees real
    products; "No need to implement automatic or recurring migration at this stage". The user chose an **admin button**
    (no SSH on staging) and **the zip carries the user's edited `affibloom.php`** (requires WP 7.1.4 / PHP 8.3).
    - **What it is:** a card on the Dashboard, "Import your N products from Lasso", shown while Lasso has published
      records with no product and the import has not finished. `admin-post.php?action=affibloom_lasso_import`
      (`LassoImportController`, `manage_options` + nonce, an `add_option` lock that a run older than 10 minutes gives
      up) → `LassoImportService::run()` → `WpLassoSource` (SELECTs only: posts, the meta Lasso reads,
      `lasso_url_details`, `lasso_amazon_products`; a missing table reads as empty) → `LassoRecordMapper` (Task 25's
      mapping table and Lasso's `get_lasso_url()` / `get_lasso_thumbnail()`) → `PostTypeProductRepository::save()` →
      `PostMetaLassoImportLog` (`_affibloom_lasso_id`, Lasso's `post_date` and `post_modified` put back, option
      `affibloom_lasso_import` once finished). A run cut off by a time limit is finished by the next click; a finished
      import never runs again. Toast: "Imported 254 products from Lasso. 9 Lasso records have no usable link and were
      left out."
    - **This is not Tasks 25-26** (no dry run, report, settings import, CLI or checks), and it replaces neither. Rules
      that differ from `tools/seed-from-lasso.php`: missing toggles read as **off** (Lasso's `1 === intval()`; the seed
      defaulted new tab, nofollow and disclosure to on), the image falls back to the post thumbnail and then the cached
      Amazon image, Amazon products take Amazon's last price, theme, show price and Prime are carried over, and the URL
      key comes from `AffiliateUrl` instead of the seed's hand-made copy. Not carried over: the rating field (Lasso's
      field 1) and anything Task 26 lists beyond the product's own fields.
    - **The card counts every record (263), the toast explains the 9 left out.** Counting only usable records would
      map all 263 on every Dashboard load.
    - **Lasso active on staging is safe:** its one save hook (`check_all_links_in_post_page`) scans only `cpt_support`
      (`post`, `page` here, plus `wp_block`).
    - **Checks:** lint clean on every file but the user's `affibloom.php`; PHPStan level 6 (a null-safe call on a
      non-null value removed); 431 unit tests, 17 new, red first (classes missing). No E2E test written (the user runs
      signed-in E2E; this button runs once per site). **By hand on Local in Brave:** backup
      `app/affibloom-backup-2026-11-13-pre-lasso-import.sql`; the 123 Affibloom products (test leftovers) deleted to
      look like staging; card shown with 263; click → "Importing…" → after about 30 s the Dashboard with **254
      products**, the card gone, the toast as above. WP-CLI against Lasso: 233 Amazon / 21 other, 183 second buttons,
      198 descriptions, 198 badges, flags equal, 240 images, all 254 keep their Lasso ID and dates; the 9 left out are
      Task 4's empty records 14837-14845. **Local was then restored from that backup.**
    - **The zip:** `app/affibloom-staging-review.zip` (287 KB, 179 files, one `affibloom/` folder, forward-slash
      paths): `affibloom.php` (the user's working copy), `src`, `templates`, `assets` from `447bf1b`, and `vendor/`
      from `composer install --no-dev --classmap-authoritative` (an autoloader only; no dev tools). Built in a scratch
      folder, so the plugin's own `vendor/` keeps the dev tools. All 116 classes load from the built autoloader;
      every PHP file passes `php -l`. Not checked: installing the zip on a WordPress site (no wp-env here).

88. **Groups (F23) swept and planned (2026-11-14), area 0 of the completion plan**, the user's "Continue" after
    Claude named it the next Phase 2 step. Read: Lasso's Groups screens, rows, modals, the four group AJAX handlers,
    the Links tab's script, the save and delete script, the Dashboard's column, filter and bulk Add to Group, the
    taxonomy, the order table and its query, and the product save's group handling (F23 requirements section 2).
    - **What the plan's one line ("the taxonomy, the Dashboard Groups column") left out:** Lasso's Groups list and
      a group's own page with Details (edit, delete with an in-use rule) and Links (drag order, remove one or the
      selected, "Add to Group" search modal), Copy Shortcode, the Group filter in saved views, bulk Add to Group,
      and new groups typed into the product page's and quick edit's field.
    - **This copy:** one Lasso group, empty; no order rows; no group display in any post. Built in full anyway
      (deviation 24).
    - **Claude's decisions (D1-D13), none dropping Lasso:** a taxonomy `affibloom_group` with the order in term meta
      (D1); the delete rule checked by the server (D4); Copy Shortcode "Coming soon" until Task 18S (D5); no post
      rewrite on remove (D6, the standing rule); keyboard Move up / Move down beside drag (D7); one plain-JS group
      picker instead of select2 (D8); the list in place like the Dashboard (D13); the order read against the members,
      so the delete code needs no hook (D12).
    - **Homes elsewhere:** Group displays get a task in Phase 3 (note at Task 18); the editor's group picker, Phase 4;
      Lasso's groups and order, Task 26 (note there).
    - Task numbering **G1-G5** (a new area, not a part of Task 17), listed in the Phase 2 file after Task 17. No code
      changed. Gate 4: waiting.

89. **The full planning pass (2026-11-14): every phase planned in full before any more building.** The user asked to
    finish all research, requirements and task planning for Phases 1-9 at once, because each new task kept uncovering
    requirements and new subtasks. Method (no code changed):
    - **Lasso's whole surface inventoried** (103 AJAX actions, 25 admin pages, 21 shortcodes, 29 tables, every cron
      job) and swept subsystem by subsystem from the source, with this copy's data read SELECT-only: 21 sweeps, each
      writing `docs/features/<ID>/{requirements,analysis}.md` with every rule sourced (`file lines`) and task drafts.
      New features: **F21** content link index, **F22** Opportunities, **F24** cloaking, **F25** Tables, **F26** Fields,
      **F27** URL variants, **F30** display types, **F31** shortcodes, **F33** Import, **F34** content changes and
      history, **F35** auto-monetization, **F37** tools and diagnostics, **F38** admin shell. Rewritten in full: F4,
      F5-F8, F9, F10, F11, F12, C1, C2, C3; second pass of F19.
    - **The built code audited** against the plan (Tasks 1-17D, the import button): 43 of the 50 tasks and items checked were
      complete as recorded (Task 1's working-tree edit and Task 3's unrun CI are the Phase 0 exceptions); the corrections found are tasks **2X1-2X5**, **13F-13J**, **FL5** (Phase 2). Unit 431 tests pass;
      PHPStan level 6 clean; lint fails only on the user's uncommitted `affibloom.php`.
    - **Registers:** 163 forward obligations (about 105 had no task) and 27 conflicts between documents; facts rechecked.
      All now owned by a task or listed in `open-decisions.md`.
    - **Phases 3-9 rewritten from the drafts**; Phase 2 gained its remaining tasks (G1-G5 kept as written); superseded
      text moved word for word to `plans/archive/phase-<n>-superseded.md`. Placement: `plans/00-overview.md` task index.
      Notable placement decisions: the link index (35A-35J) runs in **Phase 5** (import and parity need it); Tables
      (F25) at the end of **Phase 7**; the command palette and tour in **Phase 8** (F38); 18S13, 27E, IM11, IM20 in Phase 7.
    - **Settled by Claude in the pass** (no part of Lasso dropped): shared class names (`BoxOverrides`,
      `Domain\Shortcode\DisplayRequest`, one `SchemaBuilder`, one renderer per display); setting keys (F9 D3's names;
      Lasso's keys are migration sources); the education-box key; duplicates between features (palette ON9, tour ON8,
      Settings guard 9E, sort mechanism 13K, issue-tab export 31D, table shortcode T12).
    - **Facts corrected** (the most consequential): live boxes use Lasso's v2 display engine and 33 `.lasso-*` site CSS
      rules; Lasso shows Prime on every Amazon product while the setting is on; Lasso's click figures come only from its
      cloud (a 30-day snapshot, no history); real link opportunities are 30, not 2,094; 5 products render cloaked
      (7 links to 4 addresses in 3 posts), not 236; 265 `lasso-urls` posts; 3 comparison tables used by one draft; Lasso
      is **active** on this Local copy (licence status 0 keeps its jobs off); only Lasso's General tab has an
      unsaved-changes guard; Lasso's Sponsored default is on; the Phase 5 Task 26 duplicate mapping was inverted.
    - **Waiting for the user:** `open-decisions.md` (about 60 items, each with a recommendation), then gate 4 for the
      whole plan.
90. **Task 2X0 (2026-11-14), PHP 8.3 and WordPress 7.1.4 everywhere.** Red: `composer lint` on the user's `affibloom.php`
    showed 31 errors (30 spacing, fixed by `lint:fix`; the last, a blank line before the file comment, by hand); the old
    gate let PHP 8.1 and WP 6.9 through (checked with `version_compare`). Changes: the runtime check and its notice, the
    header (the user's change, committed here), `composer.json` (`>=8.3`, platform `8.3.0`), `composer.lock` (`composer
    update --lock`: only the hash and the two PHP constraints changed, Packagist URLs), `phpcs.xml.dist` (`testVersion`
    `8.3-`, `minimum_wp_version` `7.1.4`), `phpstan.neon.dist` (`phpVersion: 80300`).
    - **Deviations from the task text:** the `booleanOr.leftAlwaysFalse` ignore entry in `phpstan.neon.dist` was removed:
      at platform 8.3 PHPStan no longer reports it (`ignore.unmatched` otherwise). **`tools/local-wp.sh` pointed at PHP
      8.2.29**, which broke once Local's site php.ini moved to 8.3.36 (extension API mismatch): it now uses 8.3.36 (also
      in `environment.md`). No unit test: the bootstrap file cannot be loaded without WordPress; checked by hand instead.
    - Checks: `composer lint` clean, `composer analyse` no errors, 431 unit tests pass; Local is PHP 8.3.36 / WP 7.1.4,
      the plugin re-activated with no notice, the front page answers 200 and the Dashboard page hook registers for an
      administrator (WP-CLI). The browser pane was signed out, so the Dashboard was not looked at (Claude does not sign in).
    - `CLAUDE.md` (site root, outside git) and `docs/environment.md` updated.

91. **Task 2X1 (2026-11-14), the product page's four gaps; built, waiting for the user's signed-in run.** Red: the new
    unit test failed on the missing `placeholder` setting; the E2E cases (F1 AC5, AC7, AC10) were written first but could
    not be run here (the browser pane was signed out and Claude does not sign in on `.local`), so their red state was not
    seen. Changes: (1) the Description editor says "Enter a description" while empty: TinyMCE 4.9 has no placeholder, so
    `ProductEditors` sets the `placeholder` setting and a `content_style` rule, and `product.js` copies the setting to the
    editor body and toggles `affibloom-empty` (the words are never content); (2) the disabled Refresh is described by
    `#affibloom-refresh-reason` (screen-reader text) as well as its title; (3) Remove on a filled added button asks in the
    shared `delete-modal.php` partial (second use: optional `confirm_label`, `confirm_attr`, `busy_label`, `failed_text`),
    no `window.confirm`; F29's remove step uses the dialog; (4) `product-fields.js`: a failed value, switch or order save
    and a failed add show the failure toast (server message, else the page's), and a failed remove keeps its question open
    with the reason in the dialog (`role="alert"`).
    - Checks: `composer lint` clean, `composer analyse` no errors, 432 unit tests pass; the changed partials and the editor
      settings rendered through WP-CLI. **Not run: the E2E specs and the look at 1280 and 375 px** (Step 3 stays open).
    - **Looked at in Brave (2026-11-14, signed in by the user, product 15270, 1568 px):** the description's placeholder
      attribute is set and the empty state draws "Enter a description" and clears when text is set; Refresh is described by
      "Needs the Amazon API"; Remove opens "Remove this button?" with no browser dialog and Cancel keeps the button; the
      dialog looks right. **Still not seen:** the field-failure toast and the remove-failure message (that product has no
      field; the E2E case stubs them), the 375 px layout, and the E2E runs.
    - **The user's run (F1 + F29, 29 tests):** 25 passed, including the new AC5 and AC7 cases and F29 AC2 with the dialog.
      AC2, AC14 and AC12 ended with Playwright's own `ENOENT` on its trace files in `test-results` (no assertion failed;
      to be rerun alone). The new AC10 case timed out on a fault in the test: it clicked "Remove Button 1", the main
      link, which has no Remove; fixed to Button 2 and awaiting a rerun.
    - **Commit mix-up:** a concurrent Task 2X0 session committed part of this work in `7c73290` (`product.js`,
      `ProductEditors.php`, `F1.spec.ts`, `F29.spec.ts`, `ProductEditorsTest.php`), already pushed; history was not
      rewritten. The rest is in the 2X1 commit. `assets/admin/product-save.js` also carries an uncommitted change that is
      not this task's (`disclosure` removed from its `TEXTS` list): left alone.

92. **Task 2X3 (2026-11-14), Display colours accept every CSS colour Lasso accepts; done, the user's F16 run
    passed.** Lasso's colour fields are plain text inputs (`admin/views/settings/display.php` 598-634) and its save
    path (`Helper::canonicalize_display_colors`) does not validate: it stores the string as typed, and its Spectrum
    picker writes hex, `rgb()`, `rgba()` and `hsl()`. `clean_colour()` therefore keeps: hex of 3, 4, 6 or 8 digits;
    `rgb()`/`rgba()`/`hsl()`/`hsla()` with numbers, percentages or degrees (comma or space separated, optional
    `/ alpha`); a CSS keyword. Stored as typed, lower-cased. Anything else is refused, as before, so a colour cannot
    carry a second declaration, markup or `url()` (the value will be printed into a stylesheet in Phase 3). The
    function no longer calls `sanitize_hex_color` (it refuses 4- and 8-digit hex), so it is pure PHP and the unit
    test's stub for it is gone. **`display-settings.js` needed no behaviour change:** the text field is what is saved,
    and the picker is written to the field only when the editor moves the picker; its `toHex` shows the nearest opaque
    colour (checked in the browser pane for `rgba(0,0,0,.5)`, `#00000080`, `#0008`, `hsl()`, `rgb(0 0 0 / 50%)`); only
    its header comment changed. Red first: 9 of the new 19 unit cases failed (the accepted forms were refused).
    - Checks: `composer lint` clean, `composer analyse` no errors, 451 unit tests pass; through WP-CLI the real service
      saved `RGBA(0,0,0,.5)` as `rgba(0,0,0,.5)` and `#00000080` as typed, and refused `rgb(0,0,0);x` (the test option
      was deleted afterwards). The browser pane was signed out (Claude does not sign in on `.local`), so the
      user ran F16 signed in (2026-11-14): 10 passed, 1 skipped (the Phase 3 box test), including the new "Edge case 7" case.
    - Other sessions have uncommitted work in this tree (2X1): the 2X3 commit holds only its own files.

## Open decisions (ask the user; record the answer here)

**From 2026-11-14 the open decisions are listed in [`open-decisions.md`](open-decisions.md)** (the full planning pass,
deviation 89). The items below are the Phase 0 history.

- **D1 — Settled 2026-11-12 by the user: option (b), adapt Task 2 to Local.** The adapted Task 2 text is
  approved and is in the phase file; see "Deviations", item 7.
  Original question, for reference — **D1 — Local environment for Task 2 onwards.** The plan builds a wp-env (Docker) copy of production in
  `D:\vv-dev\` with `tools/reset-local.sh`, `tools/make-wp-env-override.mjs`, and Playwright pointed at
  `http://localhost:8888`. This machine already has the production copy in **Local**. Options:
  (a) follow the plan: wp-env + Docker (needs Docker Desktop; Local can stay as a manual reference);
  (b) adapt Task 2 to Local: reset script uses Local's WP-CLI/DB, Playwright points at the Local domain,
  `WP_HTTP_BLOCK_EXTERNAL` / `DISABLE_WP_CRON` go into this site's `wp-config.php`.
  Either way, Task 2's safety goals stay: no calls to Lasso's cloud, no cron, scrubbed user data.
  Tasks 3+ (CI) use wp-env on GitHub regardless.
- ~~**D2 — Lasso reference source.**~~ **Settled 2026-11-12:** `D:\lasso\lasso` exists on this machine, so
  the plan's path stands. It stays read only.
- **Note for D1:** Docker is not on the PATH on this machine (checked 2026-11-12), so option (a) needs
  Docker Desktop installed first.
- **D3 — Protecting `main` (Task 3, Step 4).** Branch protection and rulesets need GitHub Pro (or a public
  repo) for this private repository. Options: (a) upgrade the account to Pro; (b) make the repo public;
  (c) no enforcement: keep the branch + pull request + green CI workflow by discipline (Claude never pushes
  to `main` directly from Task 3 on). **Settled 2026-11-12 by the user: (c).** `main` stays unprotected on
  GitHub; every task still runs on its own branch with a pull request, merges only with green CI and the
  user's approval, and nothing is pushed to `main` directly. **Superseded the same day by deviation 10**
  (commit on `main` and push; no pull requests).
- Plan-wide decisions (ADR-1…7, U1–U7, client points 1–3) are listed in `plans/00-overview.md` and get
  recorded as ADRs in Task 6.

## Session log

- **2026-11-14 (Claude Code, Task 2X2):** F1 AC2 now changes every field the page shows (name, Primary URL, button text and
  both toggles, the added button's URL, text and toggles and its position, badge, theme, image URL with Apply, price,
  Show Price, description, disclosure, Show Disclosure, Sponsored), saves, reloads, and checks the page and the stored
  meta (one `wp post meta list` call, so the case stays well inside its minute). Green on the built code, no bug found.
  Shown to fail: with `disclosure` dropped from `product-save.js`'s sent fields the case went red on the disclosure
  box ("Before." instead of the new text); the file was restored. The test change itself was committed inside the 2X1
  session's `9a6edf3`, which swept `F1.spec.ts` in. **Step 2 is only partly met:** two full F1 runs ran while another
  session was running Playwright and editing code in the same tree (`test-results/` clobbered, ENOENT trace errors);
  each run had different failures (AC14/AC16/AC21/AC10, then AC16/AC18/AC8/AC21/AC22), none in AC2, and none from
  anything this task changed. A quiet double run is still to do; the order check was not meaningful (this case only
  touches its own product).
- **2026-11-14 (Claude Code, Task 2X3):** Display colours accept hex of 3/4/6/8 digits, `rgb()`/`rgba()`/`hsl()`/`hsla()`
  and keywords, as Lasso's colour fields do (deviation 92). Lint, PHPStan, 451 unit tests green; saved through the real
  service by WP-CLI. The user's signed-in F16 run passed (10 passed, 1 skipped): Task 2X3 done.
  Next: 2X4 (2X2 is tests only and also open).
- **2026-11-14 (Claude Code, Task 2X1):** the four product-page gaps built and checked by lint, PHPStan, 432 unit tests and
  a WP-CLI render (deviation 91). Waiting for the user's signed-in run of F1 (AC5, AC7, AC10) and F29 and a look at the
  page; Step 3 of the task stays open until then. Next: 2X2.
- **2026-11-14 (Claude Code, Task 2X0):** PHP 8.3 and WordPress 7.1.4 made the minimums everywhere (deviation 90); lint, PHPStan and 431 unit tests green; re-activated on Local (PHP 8.3.36). Committed and pushed.
- **2026-11-14 (Claude Code, full planning pass):** the user asked for all planning and documentation of Phases 1-9 to
  be finished before any more building (deviation 89). Lasso's surface inventoried and swept in 21 subsystem sweeps;
  the built code audited (two audits); obligations and facts registers; every phase file rewritten or completed from
  the sweeps' task drafts; superseded text archived in `plans/archive/`; `open-decisions.md` written. No application
  code changed, no database write (SELECT only). The plugin's `docs/` committed (`0af1846`) and pushed; the planning files (then `affibloom-docs/`, outside git) were moved into the plugin's
  `docs/` later the same day. Twice interrupted by the account's usage limit; every sweep was resumed, none restarted. Then the walkthrough: the user answered
  the open decisions (`open-decisions.md`, "Answers") and gave gate 4 for the whole plan; the answers were applied to
  the phase and feature files (`9881704`, pushed); Lasso deactivated on Local after a backup. Afterwards the user
  ruled that GA events use Affibloom's own names and that no Lasso click history is imported (26J, 34D dropped).

- **2026-11-14 (Claude Code, Groups sweep):** the user said "Continue". Groups (F23) swept: gates 1-2 written,
  Tasks G1-G5 in the Phase 2 file, notes in Phases 3 and 5 and the completion plan (deviation 88). No code changed;
  the F23 docs committed in the plugin repo and pushed. Waiting for gate 4.

- **2026-11-13 (Claude Code, staging build):** the user asked for a staging zip of the work so far and a one-time
  Lasso import for the client's review. **Import button built and committed `447bf1b`** (deviation 87), pushed; zip
  built at `app/affibloom-staging-review.zip`. Checked by hand on Local in Brave (254 imported, 9 left out), then
  Local restored from the backup. Phase 2's next step is unchanged (area 0, Groups).

- **2026-11-13 (Claude Code, Task 17D):** the user said "Start 17D". **Task 17D built and committed `82b6015`**
  (deviation 86), pushed. **Task 17 is complete.** F1 AC23 red then green; the add and repoint tests and F18 green.
  Found: a page of test products bulk-deleted mid-run, and `affibloom.php` changed outside the session (lint fails
  on it); both reported to the user. Local not reset.

- **2026-11-13 (Claude Code, Task 17C):** the user said "Start 17C". **Task 17C built and committed `c86378f`**
  (deviation 85), pushed. F1 AC22 red then green; AC15 and AC20 green. Local not reset.

- **2026-11-13 (Claude Code, Task 17B):** the user said "Start 17B". **Task 17B built and committed `f61e12c`**
  (deviation 84), pushed. F1 AC21 and F19 AC11 red then green; F1 AC4, AC17-AC20 and F19 green (AC12 alone). Local not
  reset.

- **2026-11-13 (Claude Code, Task 17A):** the user gave gate 4 ("Approve, start 17A"). **Task 17A built and committed
  `d7a0493`** (deviation 83), pushed. F1 AC4 red then green; AC2 and AC13-AC16 green (AC14 after deleting leftover test
  products). Local not reset.

- **2026-11-13 (Claude Code, Task 17 sweep):** the user said "Go, Phase 2 - Task 17". The task predated the full-scope
  rule, so Lasso's delete paths were swept first (deviation 82): F1 requirements section 11 and analysis "Deleting"
  written, Task 17 superseded by 17A-17D in the phase file (old text in the appendix), forward notes in Tasks 18, 26
  and 36, the overview's contract rows. The user answered D33 ("Lasso's split") and D34 ("Block only"). No code
  changed; the F1 and F19 docs committed in the plugin repo and pushed. Waiting for gate 4.

- **2026-11-13 (Claude Code, Task 16C):** the user said "Start 16C", reset Local and ran AC15 and AC17-AC20 (3 passed;
  AC18 and AC19 failed on fresh-copy assumptions in the tests, fixed). **Task 16C built and committed `6471087`** (deviation 81),
  pushed. **Task 16 is complete.**

- **2026-11-13 (Claude Code, Task 16B):** the user said "Start 16B", then mid-task asked for the quick edit to be
  redesigned in Lasso's structure with Affibloom's look and for best-effort UI from now on (deviation 79, `bf282a7`;
  memory note saved). **Task 16B built and committed `674c24c`** (deviation 80), pushed. Three leftover AC14 test products deleted;
  Local still needs a reset.

- **2026-11-13 (Claude Code, Task 16A):** the user gave gate 4 ("Approve, start 16A"). **Task 16A built and committed `852cb00`**
  (deviation 78), pushed. F1 AC17-AC18 red then green; F1 and F28 green except AC4 (Task 17). Local not reset.

- **2026-11-13 (Claude Code, Task 16 sweep):** the user said "Go forward with Phase 2 - Task 16". The sweep stopped
  last session was finished (deviation 76): F1 requirements section 10 (Q1-Q12, D21-D32, AC17-AC20) and analysis
  "Dashboard quick edit" written, Task 16 superseded by 16A-16C in the phase file (old text in the appendix). No code
  changed; the F1 docs committed in the plugin repo and pushed. Waiting for gate 4. A parallel session (bulk add bug,
  deviation 75) worked in the same checkout meanwhile; only the F1 docs went into this session's commit.

- **2026-11-13 (Claude Code, bulk add bug):** the user reported that Shift + Enter in the bulk textarea turned it
  back into the single field. Fixed (deviation 75): lint and PHPStan green; checked by hand in Brave on Local (three
  links on three lines, "Remaining links: 7", closing resets to one link; nothing added). F18 AC6 rewritten, awaits
  the user's run. Then, at the user's request, three products added through bulk add in Brave (backup
  `app/affibloom-backup-2026-11-13-bulk-add.sql` first): `example.com/bulk-hand-1..3`, all named "Example", the run
  ended on product 15134; the Dashboard went from 4 to 7. That run showed deviation 77 (Add Link needed two clicks),
  fixed and checked by hand (one click reached the confirm, answered Cancel, nothing added).

- **2026-11-13 (Claude Code, Task 16 sweep, stopped):** after AC16 passed (Task 15 complete), the Task 16 sweep began;
  the user stopped it to continue in a new session. Findings so far are in "Next up". Untracked `vendor.zip` and
  `vendor (2).zip` in the plugin folder are not Claude's; left alone and not committed.

- **2026-11-13 (Claude Code, Task 15D):** the user ran F1 AC15 (passed) and said "start 15D". Built, committed
  `b3202d7` (deviation 74), pushed; its check is the user's F1 AC16 run.

- **2026-11-13 (Claude Code, Task 15C):** the user's rerun passed (5 of 5); the user said "Start 15C". Built, checked
  through the routes on Local, committed `0da4b16` (deviation 73), pushed. AC15 waits for the user's run.

- **2026-11-13 (Claude Code, Task 15B):** the user asked to run F1 first, then to go on while it ran. 15B built in a
  worktree, merged after the run, checked through the route, pushed (`7e77a89`, deviation 72). The run's new failures
  were test faults, fixed (`bae3c86`, `4e3b939`). Rerun and reset are the user's next step.

- **2026-11-13 (Claude Code, Task 15A):** the user gave gate 4 ("approved. go for it."). **Task 15A built and committed
  `59ee8da`** (deviation 71), pushed. The user asked for the check by hand in Brave instead of the app's pane.

- **2026-11-13 (Claude Code, broken icons):** the user reported Full review and YouTube broken; fixed by deviation 71
  (site CSS on Local + "Image missing" flag). Checks: lint, analyse, 380 unit tests, F28 acceptance 6/6 (run from
  Claude's shell with the saved sign-in), screenshots of the card and picker. Committed `e522c60` (it also carried
  9 lines of Task 15A's CSS, the error toast and save modal, from the work in progress in this folder: unused until
  15A lands; left as is, no history rewrite). Then the user asked for the icon library as a grid: CSS only,
  `01e04ec` (design-system "Icon grid"), F28 6/6 again, checked at 1280px and 375px. Both pushed.

- **2026-11-13 (Claude Code, Task 15 sweep):** the user said "Go, Phase 2 - Task 15". Following "Next up", Lasso's
  save path was swept before building (deviation 70): F1 requirements section 9 (S1-S16, D10-D20, AC13-AC16, AC2
  rewritten) and analysis "Saving" written, Task 15 superseded by 15A-15D in the phase file (old text in the
  appendix), forward notes in Tasks 18, 26, 27, 32, 35 and the completion plan's area 1. No code changed; the F1
  docs committed in the plugin repo (`4f7c25c`) and pushed. Waiting for gate 4.

- **2026-11-13 (Claude Code, Task 14 sweep):** the user said "go, phase 2 task 14". The old Task 14 text predated
  the full-scope rule, so Lasso's product page was swept before building (deviation 57): F1 requirements and analysis
  re-run, the interface spec's product page rows updated, Task 14 superseded by 14A-14E written in the phase file,
  two new areas in the completion plan. No code changed. Waiting for gate 4.
  The user gave gate 4 ("Approve, start 14A"; layout: Lasso's two cards in Aurora's shell). **Task 14A built and committed `b3686ab`** (deviation
  58), pushed. Mid-task the user asked for an **icon picker for button texts**, to be the next prompt (Next up).
  The user said "Start icon picker": swept (the icons are the site's CSS, not Lasso's), the user chose an Affibloom icon
  set and flag-only for 17 broken classes, F28 gates 1-2 (`0ced689`) and Tasks 14F-14G written (deviation 59). Waiting
  for gate 4.
  The user gave gate 4 ("Approve, start 14F"). **Task 14F built and committed `7c69cf7`** (deviation 60), pushed.
  The user said "Start 14G": **Task 14G built and committed `c799ff5`** (deviation 61), pushed. Mid-task the user asked to finish testing first and
  then take a new requirement, **any number of buttons** (deviation 62, Next up).
  The user said "Start the sweep": swept Lasso's two buttons and this site's use, the user chose the count, look and
  GA event, F29 gates 1-2 and Task 14H written (deviation 63). Waiting for gate 4.
  The user gave gate 4 ("Approve, start 14H"), then changed it mid-build: one button by default, the default one
  movable, the first one primary. **Task 14H built and committed `b50ace2`** (deviation 64), pushed.
  The user asked to run the untested tasks before the next one: full acceptance run of everything built, 59 passed,
  3 failed (deviation 65).
  The user said "Fix F19 first": AC12's Back bug fixed and AC9's lookup scoped; F19 13 passed (deviation 66).
  The user said "Start 14B": **Task 14B built and committed `9640685`** (deviation 67), pushed.
  The user asked to run 14B's tests first (F1: 8 passed), then 14C: **Task 14C built and committed `0a24290`** (deviation 68), pushed.
  The user asked to run 14C's test (F1: 9 passed), then 14D: **Task 14D built and committed `0ee6801`** (deviation 69), pushed.
  The user asked to test 14D (F1: 11 passed, AC11 and AC12 among them; leftovers removed) and to start Task 15 in a
  new session.

- **2026-11-13 (Claude Code, the Dashboard UI):** the user asked for the Dashboard UI to be finished before Tasks
  14-17, with every Lasso column, search without reloads and placeholders for later work (deviation 53). Tasks
  13C-13E written and built (`b2ab7b3`, `f32c89f`, `00dc1af`). While building, the user moved the search beside the
  title, asked for AJAX search, pages and filters, chose 1320px for the Dashboard, asked for spacious admin pages
  (`ee6743c`) and for Add Link to behave exactly as Lasso's (`6d9a5ef`). Deviations 54-56. The user's E2E runs of
  F19, F18 and F2 are pending. Products parked as drafts by the checks: 15418, 15419.

- **2026-11-13 (Claude Code, Task 13):** the user said "Go ahead with Phase 2 - Task 13". Following "Next up",
  Lasso's dashboard was swept before anything was built (deviation 48): the new feature folder
  `docs/features/F19/{requirements,analysis}.md` (gates 1-2, `5d65ab7`), Task 13 superseded in the phase file
  with its old text moved to the appendix, and Tasks 13A and 13B written in full. **Gate 4 given by the user**
  with two answers: Aurora's shell with Lasso's arrangement, and every unowned dashboard capability scheduled
  with the data it needs — none dropped, the user adding that **the shortcode stays**, which puts Affibloom's
  own shortcode in Phase 3 (`561b4fc`). **Task 13A built and committed `99a4fba`** (deviation 49), pushed; the
  user's F19 run: **4 passed, 6 skipped** (AC1-AC4 green, AC5-AC10 still `test.fixme`). The user then reset the
  Local copy. **Task 13B built and committed `ad508f4`** (deviation 50), pushed; **Task 13 complete**, waiting
  on the user's run of all ten F19 tests.
  The user also asked whether Affibloom uses Lasso's tables: it does not — its own post type, meta, option and
  tables; Lasso's tables are read once, read-only, by the migration (Tasks 25-26), and only
  `_affibloom_lasso_id` is kept, which is what keeps `[lasso]` shortcodes rendering after Lasso is switched off.

- **2026-11-13 (Claude Code, Task 12 sweep):** the user said "Go, Phase 2 task 12". Following "Next up", Lasso's
  Amazon add path was swept before building (deviation 42): F2 requirements and analysis rewritten, F17 section 4
  corrected, Task 12 superseded by 12A-12E written in full in the phase file. The user then gave gate 4 ("Approve, start 12A"); F2 docs committed `4fc8a28`. **Task 12A built
  and committed `5dcfa7a`** (deviation 43), pushed. The user said "Start 12B": **Task 12B built and committed
  `b1db8c5`** (deviation 44), pushed. **The user's F2 run: 4 passed** (AC1, AC3, AC4, AC8).
  The user said "Start 12C": **Task 12C built and committed `a4b3c52`** (deviation 45), pushed. The user's F2
  run: AC1, AC3, AC4, AC6 passed; **AC8 failed on its wait**, not its result: product 15172 held the `maas` link
  exactly as pasted, but the toast check's 5 s ran out while the Dashboard redirect was still loading (it passed
  the run before). `submit()` now waits for the `affibloom_notice=` redirect under the test's timeout first
  (`8393a19`, test only); F2 to be re-run by the user. The user said "Start 12D" (no F2 result
  reported): **Task 12D built and committed `5d1d634`** (deviation 46), pushed. The user said "Start 12E": **Task 12E
  built and committed `e839339`** (deviation 47), pushed; **Task 12 complete.** **The user's F2 run: all eight
  passed** (AC1-AC8), after `e839339`.

- **2026-11-13 (Claude Code, reset fix):** the user reported that `reset-local.sh` deletes tables and users.
  The script was rewritten to load the snapshot into a staging database and swap it in, verified by a forced kill,
  a concurrent run and a full reset (deviation 41). The Local copy is now the snapshot (1807 posts); the products
  made since are in `app/affibloom-backup-2026-11-13-pre-reset-fix.sql`.

- **2026-11-13 (Claude Code, F18 run):** the user's run of F18: 4 passed, 6 failed, all with `spawnSync bash ENOENT`
  (Git Bash not on PowerShell's PATH; environment.md gotcha), not Affibloom faults. The products the run left show
  AC1 ("Mizunousa"), AC2 (`https://` added) and AC7/AC8 (exactly ten products, none for the eleventh) working;
  AC3, AC4 and AC10 never reached their checks, and AC10 changed no settings. At the user's request
  `tests/E2E/utils.ts` now finds Git Bash itself on Windows (`cbb2e06`), checked from PowerShell. **The user's rerun: all 10 F18 tests passed.**

- **2026-11-13 (Claude Code, Task 11E):** the user said "Go". **Task 11E built and committed `f830fea`** (deviation
  40), pushed; Tasks 11A-11E complete. F18 AC10 awaits the user's run.

- **2026-11-13 (Claude Code, admin login):** the user asked for the Local admin login. The copy looked
  half-reset at first (62 tables; no `wp_users`, `wp_usermeta` or the four `wp_term*` tables), so after a safety
  dump those six were restored from `app/affibloom-backup-2026-11-13-task11a.sql`, the extract audited to touch
  no other table. **What was really happening: the Task 11D session's `reset-local.sh` was importing at that
  moment**, so the empty table list was a snapshot of an import in progress, and by the time the safety dump ran
  (06:41) the reset had finished — that dump is therefore a complete 141-table backup, not a half-state one.
  The restore was thus mostly redundant, with one real effect: it kept five `wp_usermeta` rows for user 12
  (`session_tokens`, `twitter`, `facebook`, `additional_profile_urls`, `_new_email`) that the 11-12 snapshot
  predates and the reset had dropped. Everything else matched the snapshot row for row (users 5, terms 25,
  termmeta 5, term_taxonomy 25, term_relationships 214). End state verified: 142 tables, five administrators,
  1807 posts. **Two writers on one database is the lesson** — check for a running `reset-local.sh` before
  repairing the copy, and distrust a table list read while one is running; the script takes no lock.
  The login is **`vvadmin` / `password`** from Task 2's scrub (phase 0, Step 4), confirmed with
  `wp_check_password()` rather than by signing in (`.local` host). The other four accounts still have no
  password, except `Rupash Das`, whose hash the user set on 11-12. A front-end 500 seen mid-check was the
  plugin being edited by the Task 11D session (`Plugin.php` calling `ProductService` with one of three
  arguments); it returned 200 once that edit landed. No plugin, test or database code was changed here.

- **2026-11-13 (Claude Code, Task 11D):** the user said "next", then "Go". The Local copy was found half-reset
  (61 of 141 tables, no users); the documented reset was re-run to completion. **Task 11D built and committed
  `f1067d7`** (deviation 39), pushed. The browser pane is signed out since the reset. The F18 run is the user's.

- **2026-11-13 (Claude Code, Task 11 sweep and Task 11A):** the user asked for "Phase 3 - Task 11"; Task 11 is
  Phase 2's next task, and the user chose to sweep Lasso's add flow before building. Gates 1-2 written as **F18
  Adding links**, Task 11 superseded by 11A-11E (deviation 34). The user answered D2 (follow Lasso on duplicates,
  decision 2 withdrawn) and D5 (Marketplace as an Amazon search with C1) and gave gate 4; Claude settled D1, D3, D3b,
  D6 from Lasso. Docs `13a01dc`. **Task 11A built and committed `597fd4a`** (deviation 35), pushed; the user's F18
  run: 3 passed. **Task 11B built and committed `8f41d04`** (deviation 36), pushed; F18 now has 6 tests, the 3 new
  ones await the user's run. The user then asked for 11C-11E in full: written (deviation 37, F18 D3 corrected:
  no image). **Task 11C built and committed `b89ae01`** (deviation 38), pushed; F18 AC6-AC9 await the user's run.

- **2026-11-13 (Claude Code, help tips and Task 10F):** the user's run of F16 + admin-design: 8 passed, 4 failed (three
  tests clicking text that a radio lies over, the rail 381px); the user then reported no help tip working. Fixed and
  committed `3412dbd` (floating tips, border-box rail, tests act on radios; deviation 31). Task 10F: swept Lasso's
  Amazon tab first (F17 requirements, gate 1), asked the user two decisions (Lasso's two-column structure; refuse an
  invalid tracking ID), then built it (deviations 32, 33). Seen working in the browser pane: live tracking-ID check,
  tags added typed and pasted with the invalid one left marked, a removed ID landing in the blacklist, the disabled
  whitelist kept on save without a false warning, a refused ID kept with a clear message. Green: lint, PHPStan, 62 unit
  tests. New F17 acceptance tests (5) and the updated design test await the user's run. Amazon settings reset.

- **2026-11-13 (Claude Code, Tasks 10C and 10E):** 10C committed `a978948` (layout rows, coercion, phone layout,
  device toggle; deviation 29). The user then asked for 10D and 10E: **10D was not built** — it renders the real
  box template, which Task 18x builds in Phase 3 (raised with the user). 10E built (deviation 30): the site-wide
  Display keys with Lasso's defaults, Custom CSS through Lasso's allowlist, the Disclosure row, Price following
  Show Price, Reset Settings with Lasso's keys and confirmation, Save and Reset at the foot of the rail. Seen
  working in the browser pane: the Disclosure row drives Grid's scope, one toggle writes List and Table, Custom
  CSS loses every `<`, Reset asks and on No changes nothing, on Yes restores Lasso's defaults. Green: lint,
  PHPStan, 56 unit tests. F16 now has 10 acceptance tests (one `fixme` until the box), unrun by the user.

- **2026-11-13 (Claude Code, Task 10B):** palette defaults made hex at the user's request; Task 10B built —
  `Density` and `Elements` with Lasso's actual rules (deviation 28), the Layout & spacing tab with density chips,
  the Elements accordion and the Spacing / Typography / Image steppers and line clamps, Lasso's two rail tabs.
  Green: lint, PHPStan, 36 unit tests. Seen working in the browser pane: Compact fills the steppers, a stepper
  moves 8 → 10 and turns the preset Custom, title lines 2, Price off ("9 of 10 visible"), save stored
  `custom` with exactly those values while Grid stayed `balanced`; stacks below 992px with no sideways scroll.
  Display settings reset to defaults. Committed as two commits, each passing its own tests: hex defaults
  `2049291` (checked alone in a temporary worktree) and 10B `87e6ccc`; pushed. The Playwright files (F16
  AC1/AC4/AC5/AC8, admin-design) await the user's run.

- **2026-11-13 (Claude Code, scope change and Task 10A):** the user ruled that **everything Lasso does is in
  scope** (deviation 24); the Display audit found the tab is a visual editor with ~50 controls per display type
  and that the live site's boxes already render through it. Task 10 was superseded by 10A-10F, gates 1-2 written
  (`docs/features/F16/`), gate 4 given, and the plan files updated. The user then amended ADR-8: **Lasso's
  structure, Affibloom's colours and type** (deviation 25). **Task 10A built and committed:** the display model
  (`DisplayType`, `Theme`, `Palette`, `DisplayConfig`, `DisplaySettings`, repository, service), the screen in
  Lasso's split layout with colour pickers, and the save path (deviation 26). Green: lint, PHPStan, 18 unit
  tests; checked by hand in the browser pane at 1440, 960 and 767px, with a real save and a per-type check
  through WP-CLI. Display settings left at the defaults. **The acceptance run (`F16.spec.ts`, `admin-design.spec.ts`)
  is still the user's to run.**

- **2026-11-13 (Claude Code, Task 10 Steps 1–4, then scope change):** Task 10 built to Step 4 — the two plan unit
  tests plus an icon-markup test (red at "Unknown settings tab."), the 12 Display/Amazon defaults and
  `Settings::string()`, the `display`/`amazon` tabs with four cleaners (`text`, `icon_text` for decision 5,
  `color`, `amazon_tag`) and the refused-keys loop restored, and both tabs rendered as Aurora cards with Lasso
  370's labels and order, a new `partials/help.php` (design-system help tip), `link`/`star` icons and the field
  CSS. Checks green: lint, PHPStan, 7 unit tests, the user's `admin-design` run 3 passed; two defects found by
  hand and fixed (wp-admin's `min-height: 40px` beating the 38px field; hidden tooltips causing a 39px sideways
  scroll). Then the user set the full-Lasso scope rule: Steps 5–6 not run, the plan goes first (deviation 24,
  audit report in `reports/`).

- **2026-11-13 (Claude Code, Aurora):** the user chose Aurora from four style directions, approved U9 (Dashboard
  tiles) and U10 (subtitles), then asked for a centred column like Lasso's. Test-first both times; the user's runs
  4 + 1 passed; lint, analyse, unit green; settings at the defaults. Committed `469829a`, pushed (deviation 23).
  Next: Task 10 in Aurora.

- **2026-11-13 (Claude Code, Phase 2, Task 9A done):** design system proposed with a preview, brand colour changed to
  ocean blue at the user's request, approved. Red: the user's `admin-design` run failed on Lasso's purple header and
  the missing "Settings" heading. Green: lint, analyse, unit (4 tests); the user's runs `admin-shell` + `admin-design`
  4 passed, F9 AC3 1 passed; settings left at the defaults. Looked at 1920/1280/782 px. Deviation 22.

- **2026-11-12 (Claude Code, design decision):** the user stopped after Task 9 and decided on Affibloom's own admin
  design (deviation 21, ADR-8): admin only, Lasso's labels and workflow kept, a design-system task (9A) before Task 10.
  Planning and documentation updated; no code changed. Plugin repo docs committed `120cd3a` and pushed. Next session:
  Task 9A.

- **2026-11-12 (Claude Code, Phase 2, Task 9 done):** Settings → General built step by step with the user's "go" per
  step (deviation 20). Red: the user's F9 AC3 run hit "not allowed to access this page". Green: lint, analyse, unit
  (4 tests; `SettingsServiceTest` also checked by breaking the service), the user's F9 AC3 run `1 passed`; option left
  at the defaults (`sponsored: false`). Looked at in the browser pane at 1920px beside Lasso's baseline: sizes and
  colours match; real Tab presses reach each switch and its help button, whose tooltip opens on focus (U3).

- **2026-11-12 (Claude Code, Phase 2, Task 8 done):** the user started Phase 2 (gate 4 confirmed). Admin shell built:
  `Module`, composition root `modules()`/`template()`, `Capabilities::PRODUCTS`, `AdminMenu`, `AdminAssets`,
  `DashboardScreen`, `Template`, dashboard and header templates, `admin.css` (deviation 19). Red checked through WP-CLI;
  lint, analyse, unit (2 tests) green; the user's E2E run `2 passed`; looked at in the browser pane at 1920px (header
  100px `#5E36CA`, title fixed to 40px/800, CSS absent on `index.php`). Roadmap 2.0 gate line ticked; the rest of 2.0
  follows with Settings (Task 9) and the components' first screens.

- **2026-11-12:** the user adopted the Amazon-data recommendation (deviation 18).
- **2026-11-12 (Lasso Amazon data):** Lasso gets titles/images by sending each product URL to its own licensed cloud
  service (`lasso.link` "Broken Link Service"), which loads the Amazon page; plus an hourly cloud → site sync. No Amazon
  API or shared credentials. Amazon's Associates Policies exclude "data mining, robots…" and limit cached API images to
  links kept 24 h. Options and recommendation (migrate Lasso's data + editor helpers now, Creators API later) in
  `reports/2026-11-12-lasso-amazon-product-data.md`; nothing changed in the plan until the user decides.
- **2026-11-12 (site inspection):** the site itself already holds the GA4 IDs (property `312587692`, stream
  `3472054567`, `G-K7T3M99G7Q` via Headers & Footers) and Amazon's partner tag plus **stored PA-API 5.0 keys** in
  `lasso_settings` (unused by Lasso 369/370, validity untested); Creators API fields empty. C3 needs nothing from the
  client. Report revised: `reports/2026-11-12-account-access-check.md`. The user approved one test request: Amazon answered 403 `AccessDenied`, "Product Advertising API is deprecated";
  the stored keys are unusable, C1 needs Creators API credentials from the client.
- **2026-11-12 (account check):** read-only check of GA4 and Amazon in Brave: no access to the site's GA4 property
  from either Google login; the Amazon login is not connected to an Associates account. Client list in
  `reports/2026-11-12-account-access-check.md`. Phase 2 held for the user's review.
- **2026-11-12:** the user signed off Phase 1.
- **2026-11-12 (Claude Code, Phase 1 finished):** the user delegated the open decisions (records first, then Lasso
  research, then requirements and Lasso for UI/UX); decided and documented (deviation 16, `6f15ea1`). Task 6 approved
  on that basis and ticked. Task 7 checked against the user's signed-in run plus a signed-out run (deviation 17),
  F4 AC1 fixed, committed `ed934a9`. Local reset, lint/analyse/unit green, everything pushed. Phase 1 waits for the
  user's sign-off; next is Phase 2, Task 8, in a new session.
- **2026-11-12 (Claude Code, Phase 1, Tasks 5–7):** Task 5 done (`ebcbb62`): guide capture signed out with trackers
  blocked; Lasso admin screens taken from production view only (Lasso unlicensed on Local; licence key and user name
  hidden; production checked unchanged before and after); guide PNGs kept out of git (the user). `local-wp.sh` reads
  Local's MySQL port (`4087f81`). Task 6 written and committed (`b0e7abc`): 7 ADRs, interface spec checked against
  Lasso 370, UI audit, F1–F12 and C1–C3 folders, client-and-ui.md; **waiting for approval**. The user put client
  points 1–3 in scope after an investigation of Lasso (deviation 14). Task 7 written (50 tests, 15 files),
  **not committed**: waiting for the user to run it (it signs in). Next: the user's approval of Task 6, the Task 7
  run and its check, then the Task 7 commit and Phase 1 sign-off.
- **2026-11-12 (Claude Code, Phase 1, Task 4 done):** the user approved the Phase 1 plan and asked for all of
  Phase 1 in this session. `tools/inventory.php` written, run (250 boxes in 34 posts, 263 published records),
  figures that differ from the plan traced and recorded (deviation 11), secrets found in `settings.json` and
  filtered, Task 27 map extended. lint, analyse, unit green. Commit `7d9d00e`, pushed.
- **2026-11-12 (Claude Code, Task 3 and Phase 0 close):** Task 3 written (integration suite, workflow with
  three fixes found locally, PR template), PR #1 opened. CI never started: GitHub billing block. The user
  chose to merge on local checks and to drop CI work entirely (deviation 10); `main` = `77492e2`, PR #1
  merged. Phase 0 signed off by the user with the CI condition. `CLAUDE.md` and `/next-task` now describe
  the simple workflow. Next: Phase 1 in a new session.
- **2026-11-12 (Claude Code, Task 2 done):** the user created `vvadmin` and saw no Affibloom notice on
  `plugins.php` → Task 1 ticked. Snapshot taken (153 MB, after the scrub and `vvadmin`); `reset-local.sh`
  ran for real in 74 s with everything in place. Smoke test `1 passed` (user's terminal, after
  `npx.cmd playwright install chromium` there). lint, analyse, unit all green. Commits `84b7ff9` (Task 2)
  and `196f0f1` (`.gitattributes`), not pushed.
- **2026-11-12 (Claude Code, Task 2):** D1 = b approved; Task 2 rewritten for Local and started.
  `tools/local-wp.sh` works (needed `MYSQL_TCP_PORT`, see environment.md). Lasso had been switched on
  before the safety constants existed; DB backed up (153 MB, `…/volleyballvault/affibloom-backup-2026-11-12.sql`)
  and the four constants added to `wp-config.php` — outside requests now blocked, own host still 200.
  Users, sessions and comment emails/IPs scrubbed (all real accounts now have no password). Front end:
  guide shows 10 Lasso boxes, no PHP errors, nothing from Affibloom in the logs. Found the `cmd.exe` `%`
  gotcha in `wp db query`.
- **2026-11-12 (Claude Code):** Did the one-time setup from `CLAUDE.md` with the user's permission: copied
  `claude-config/settings.json` to `.claude/settings.json` and `claude-config/commands/*.md` to
  `.claude/commands/` (active from the next Claude Code start), then removed that section from `CLAUDE.md`.
  Checked the machine: `composer test:unit` → `OK (1 test, 1 assertion)`, `composer lint` clean,
  `composer analyse` → `[OK] No errors` (CLI PHP 8.5.1, Composer 2.9.5, Node 22.23.2; `wp` and `docker`
  not on PATH). Recorded the two commits the user made after the claude.ai session (`8732ff4`, `7102461`;
  both pushed, `main` = `origin/main`). D2 settled. **The WordPress MCP connector in Claude Code points at
  production** (`https://volleyballvault.com`, WP 7.1.4): use it for reads only, never writes; it cannot
  show the Local site. Task 1 Step 7 still waits for the user to activate the plugin on Local.
- **2026-11-12 (claude.ai):** Phase 0 started. Task 1 Steps 1–6 and 8 done and pushed; Step 7 open.
  `composer install` on this machine timed out reaching repo.packagist.org. Set up this Claude Code
  memory (`CLAUDE.md`, `docs/…`). `.claude/settings.json` and the `/next-task` and
  `/wrap-up` commands wait in `docs/claude-config/` for the one-time setup in `CLAUDE.md`.
