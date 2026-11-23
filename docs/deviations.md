# Affibloom — deviations from the plan text

Moved verbatim from `progress.md` on 2026-11-15. Add a new deviation at the end, with the next number, **only when the plan's design or a later task changes**; small fixes go in a *Note* under the task (deviation 129). Keep each to about 10 lines: what differs, why, the checks.

## Index

- 1. Repository location.
- 2. PHPStan fixes in Task 1
- 3. wp-env was not run.
- 4. `composer.lock` is committed
- 5. `vendor/` now holds the full dev install
- 6. Plugin header author.
- 7. Task 2 rewritten for Local (D1 = b, approved by the user 2026-11-12).
- 8. `.gitattributes` added
- 9. Task 3 workflow fixes
- 10. Simple workflow, no CI (the user's decision, 2026-11-12).
- 11. Task 4 (inventory), changes to the plan's code and figures
- 12. `tools/local-wp.sh` reads the MySQL port from Local's `my.cnf`
- 13. Task 5 (baselines), changes to the plan
- 14. Client points 1–3 are in scope
- 15. Task 7 (acceptance suite), changes to the plan:
- 16. Task 6 decisions (2026-11-12), made under the user's delegation
- 17. Task 7 verification.
- 18. Amazon product data without credentials (the user, 2026-11-12; plugin decision 11).
- 19. Task 8 (admin shell), changes to the plan's code:
- 20. Task 9 (2026-11-12), changes to the plan's code:
- 21. Admin visual design: Affibloom's own (the user, 2026-11-12; ADR-8).
- 22. Task 9A (2026-11-13), how it was done:
- 23. Aurora replaces the first design (the user, 2026-11-13).
- 24. Full Lasso scope (the user, 2026-11-13).
- 25. ADR-8 amended (the user, 2026-11-13): Lasso's structure, Affibloom's colours and type.
- 26. Task 10A (2026-11-13), changes to the plan's code:
- 27. Palette defaults are hex (the user, 2026-11-13).
- 28. Task 10B (2026-11-13), changes to the plan's text, from reading Lasso's code:
- 29. Task 10C (2026-11-13), changes to the plan's text, from reading Lasso's layout code:
- 30. Task 10E (2026-11-13), changes to the plan's text, from reading Lasso's code:
- 31. Help tips float (2026-11-13, `3412dbd`).
- 32. Amazon tab decisions (the user, 2026-11-13):
- 33. Task 10F (2026-11-13), the Amazon tab in full
- 34. Task 11 superseded by 11A-11E (2026-11-13), from a sweep of Lasso's add flow
- 35. Task 11A (2026-11-13, `597fd4a`), changes to the old Task 11 code it was cut from:
- 36. Task 11B (2026-11-13, `8f41d04`; F18 note `6121249`), from Lasso's lookup code
- 37. Tasks 11C-11E written in full (2026-11-13), at the user's prompt
- 38. Task 11C (2026-11-13, `b89ae01`), bulk add,
- 39. Task 11D (2026-11-13), naming a new product,
- 40. Task 11E (2026-11-13, `f830fea`), the site's link defaults on a new product
- 41. `tools/reset-local.sh` rewritten (2026-11-13, the user's report: "it deletes some tables even reset users data").
- 42. Task 12 superseded by 12A-12E (2026-11-13), from a sweep of Lasso's Amazon code and the live Amazon records
- 43. Task 12A (2026-11-13, `5dcfa7a`), an Amazon product link stored as Lasso stores it
- 44. Task 12B (2026-11-13, `b1db8c5`), the same Amazon product twice
- 45. Task 12C (2026-11-13, `a4b3c52`), Amazon search pages and other Amazon pages
- 46. Task 12D (2026-11-13, `5d1d634`), Amazon short links
- 47. Task 12E (2026-11-13, `e839339`), a bare ASIN and the starting name
- 48. Task 13 superseded by 13A-13B (2026-11-13), from a sweep of Lasso's dashboard
- 49. Task 13A (2026-11-13), the Dashboard list
- 50. Task 13B (2026-11-13), the Dashboard search
- 51. The Local copy seeded from Lasso (2026-11-13, `d401a43`), and a parity audit of product management.
- 52. No Lasso cloud, ever; credentials later (the user, 2026-11-13).
- 53. The Dashboard UI is finished before Tasks 14-17 (the user, 2026-11-13).
- 54. Tasks 13C-13E built (2026-11-13): the Dashboard UI finished
- 55. Every admin screen more spacious (2026-11-13, `ee6743c`, the user: "the dashboard page looks compact. we need to make all admin pages spacio…
- 56. Add Link ends as Lasso's does (2026-11-13, `6d9a5ef`, the user: "Check how add link feature is working in lasso plugin. we need exactly same…
- 57. Task 14 superseded by 14A-14E (2026-11-13), from a sweep of Lasso's product page
- 58. Task 14A (2026-11-13, `b3686ab`), the product page opens
- 59. Button icons, F28 (2026-11-13, docs `0ced689`), the user's next prompt after 14A
- 60. Task 14F (2026-11-13, `7c69cf7`), the button icon set
- 61. Task 14G (2026-11-13, `c799ff5`), the icon picker
- 62. Next requirement (the user, 2026-11-13, during 14G): any number of buttons.
- 63. Any number of buttons, F29 (2026-11-13, docs `d546fbf`), the user's requirement after 14G.
- 64. Task 14H (2026-11-13, `b50ace2`), any number of buttons on the product page
- 65. Full acceptance run of everything built (2026-11-13, the user: "Before going to next task, I want to run the untested tasks").
- 66. F19 AC9 and AC12 fixed (2026-11-13, `6a743ed`), the user: "Fix F19 first".
- 67. Task 14B (2026-11-13, `9640685`), the product page's display card
- 68. Task 14C (2026-11-13, `0a24290`), custom fields on the product page
- 69. Task 14D (2026-11-13, `0ee6801`), the Share kit and the tracking-ID offer
- 70. Task 15 superseded by 15A-15D (2026-11-13), from a sweep of Lasso's save path
- 71a. Two broken button icons fixed (2026-11-13, the user: "two icons are shows broken. full review and youtube. fix it.")
- 71. Task 15A (2026-11-13, `59ee8da`), Save Changes stores the Details form
- 72. Task 15B (2026-11-13, `7e77a89`), a new Primary URL on Save Changes
- 73. Task 15C (2026-11-13, `0da4b16`), "This Product Already Exists" and the repoint
- 74. Task 15D (2026-11-13, `b3202d7`), unsaved changes on the product page
- 75. Bulk add: Shift + Enter in the textarea starts a new line (2026-11-13, the user's bug report).
- 76. Task 16 superseded by 16A-16C (2026-11-13), from a sweep of Lasso's Dashboard quick edit
- 77. Bulk add: one click on Add Link from the textarea submits (2026-11-13, found while adding three links in Brave).
- 78. Task 16A (2026-11-13, `852cb00`), the Dashboard row's quick edit
- 79. The quick edit redesigned in Lasso's card layout (2026-11-13, `bf282a7`; the user: "The UI of the Quick Edit panel is very poor. Redesign it…
- 80. Task 16B (2026-11-13, `674c24c`), unsaved changes on the Dashboard
- 81. Task 16C (2026-11-13, `6471087`), the same link as another product, from the quick edit
- 82. Task 17 superseded by 17A-17D (2026-11-13), from a sweep of Lasso's delete paths
- 83. Task 17A (2026-11-13, `d7a0493`), Delete This Link deletes for good
- 84. Task 17B (2026-11-13, `f61e12c`), the selection bar's Delete moves the ticked rows to the trash
- 85. Task 17C (2026-11-13, `c86378f`), retired products get the status `affibloom_retired`
- 86. Task 17D (2026-11-13, `82b6015`), a deleted product comes back when its link is added again
- 87. A staging build for the client's review, with a one-time Lasso import (2026-11-13, `447bf1b`), outside the phase order.
- 88. Groups (F23) swept and planned (2026-11-14), area 0 of the completion plan
- 89. The full planning pass (2026-11-14): every phase planned in full before any more building.
- 90. Task 2X0 (2026-11-14), PHP 8.3 and WordPress 7.1.4 everywhere.
- 91. Task 2X1 (2026-11-14), the product page's four gaps; done (the user's F1 and F29 runs, below; the 375 px look still owed).
- 92. Task 2X3 (2026-11-14), Display colours accept every CSS colour Lasso accepts; done, the user's F16 run passed.
- 93. Task 2X4 (2026-11-14), a kept redirect that ends at Amazon is an Amazon product; built; F2 and F18 passed in Claude's runs (deviation 95a).
- 94. Task 2X5 (2026-11-14), bulk add past a failing link and "Continue Anyway"; built; F18 passed in Claude's run (deviation 95a).
- 95. Task 2X6 (2026-11-14), brag mode removed; built; F16 passed in Claude's run (deviation 95a).
- 95a. Claude runs the signed-in specs itself (the user, 2026-11-14).
- 96. Tasks 9B-9L (2026-11-14), Settings → General in Lasso's structure; built in one session at the user's word ("9B to 9L one by one, run the te…
- 97. Task G1 (2026-11-14), the Groups list and a new group; done, checked by Claude's own runs.
- 98. Tasks G2-G5 (2026-11-14), the rest of Groups (F23); built in one session at the user's word ("Let's do G2-G5 ... run the test for all these …
- 99. Task TL1 (2026-11-14), activation and deactivation (F37 AC1); done, checked on Local with WP-CLI.
- 100. Task TL2 (2026-11-14), the upgrade routine (F37 AC2); done, checked on Local.
- 101. Task ON1 (2026-11-14), navigation in Lasso's order; done, checked by Claude's own runs.
- 102. Tasks 13F-13J (2026-11-14), the Dashboard's second pass (F19); five tasks in one session at the user's word ("Let's do the Dashboard, second…
- 103. Task ON2 (2026-11-14), the notice region, foreign notices and the update notice; done, checked by Claude's own runs.
- 104. Settings save the Lasso way (2026-11-14; the user: "The settings pages are saving with page reload. but lasso work differently. Investigate …
- 105. Task FL5 (2026-11-14), the product page's field rows corrected to Lasso's rules (F26 AC6-AC7); done, checked by Claude's own runs.
- 106. The Filters panel remembers every close (2026-11-14; the user: the Dashboard "keeping open the filter settings").
- 107. Phase 2 audit (2026-11-15; the user: "thoroughly re-review Phase 2 ... I switched between two different accounts").
- 108. The product page's Button Text no longer squeezed (2026-11-15; the user: "Yes, fix it", after deviation 107 named it).
- 109. Even gaps between Settings cards (2026-11-15; the user: "also fix the uneven card gaps at 375px").
- 110. Task C1A: the Product Data API card and Validate (2026-11-15).
- 111. Faster checks: test tiers, faster WP-CLI, one-call setup, automatic restore after runs (2026-11-15; the user: "Steps 1–6").
- 112. Task C1B (2026-11-15), the catalogue client behind `wp affibloom amazon get`; built from the reverted first try; savings read unverified.
- 113. Task C1C (2026-11-15), Amazon data on add: the table (Affibloom's column names, no last_updated index yet), the Table class, NotFound keeps the starting name, R34 on read; run-state restores Affibloom's tables and leaves the schema version.
- 114. Task C1D (2026-11-15), Refresh on the product page: image as meta only, placeholder rename on the server, the page keeps a typed name; the image row wraps.
- 115. Narrower batch-end runs and shorter records (2026-11-15; the user: "All three"); Local's PHP workers are hard-coded.
- 116. Task C1I (2026-11-15), bulk Amazon sync: pending without Lasso's last-updated arm (229 here until Task 26 copies the rows), a placeholder counts as no name, REST + WP-Cron.
- 117. Full Amazon product data, C1Q0-C1Q6, before C1K (the user, 2026-11-15): features field, C1E's price job brought forward, no variations.
- 118. Task C1Q0 findings (2026-11-15): the credentials were cleared by a real token refusal (07:46:58) and by a test run around the user's Validate (16:51).
- 119. Task C1Q0 (2026-11-15): refused credentials kept and marked (not cleared, R22), the stub answers only test credentials, the run restore keeps a person's.
- 120. Task C1Q1 (2026-11-15): real Creators API answers recorded for 3 ASINs; `--resources` added; Amazon returns no ratings.
- 121. Task C1Q2 (2026-11-15): 25 resources asked; ItemDetails and OfferDetails; the buy-box listing read.
- 122. C1Q3 narrowed (the user, 2026-11-15): store only needed data; two numeric price columns. C1Q2's second commit noted.
- 123. Task C1Q3 (2026-11-15): `price_amount`, `basis_amount` through upgrade step 3; AC1 extended but not seen red.
- 124. Task C1Q4 split; C1Q4a (2026-11-15): Features field (JSON meta, editable list), filled on add, bulk sync, Refresh; C1K first try stashed.
- 125. Task C1Q4b (2026-11-15): hourly `affibloom_amazon_refresh`, Lasso's batch, fixed queue, 20 s passes; no Locations arm or 404 issue yet.
- 126. Task C1Q5 (2026-11-15): the Amazon Data card (titled so, not "Details"), stored row only, read-only, outside the form.
- 127. Task C1Q6 split; C1Q6a (2026-11-15): `GuardedCatalog` (429 wait, one call a second, 8,640 a day), Throttled kind, the job's reserve.
- 128. Task C1Q6b (2026-11-15): `items()` (ten ASINs a request), prefetch for bulk sync and the job, throttled bulk sync waits; stub and run-state fixes; AC2 fails on C1Q6a too.
- 129. Lean working method (the user, 2026-11-15): few records, few test runs, no save/restore or backups on Local.
- 130. Tests per task cut to ~20 s (the user, 2026-11-15): no red run; lint, analyse and Playwright once per batch.
- 131. The user tests each batch by hand from Claude's guide; Playwright only at the phase exit; PHPStan cache in `.local/`; English.
- 132. Task 18A (2026-11-16): the box's data attributes and properties get Affibloom names too (`data-affibloom-id`, `data-hide-*`, `--affibloom-*`); table in tokens.md.
- 133. Add Link (2026-11-16, the user): every single add opens the product's page, Amazon too; deviation 56's Amazon reload was a dead Lasso branch.
- 134. Product page (2026-11-16, the user): buttons, custom fields and features reorder by dragging a grip only, as Lasso; Move up/down removed.
- 135. Task 18B (2026-11-16): the `variants/` baselines predate Lasso 370 (live badge `top: 0`); 18D recaptures them; AC1 adds the site's CSS.
- 136. Task 18C (2026-11-16): box CSS in the admin (R52) moves to the previews 10D/14E; the copy script to 21C.
- 137. Task 18D (2026-11-16): baselines drawn on Local with Lasso 370 (staging's Lasso is unlicensed); shortcode cells; variants unchanged, so deviation 135's cause is wrong: AC1's gap is Affibloom's CSS.
- 138. F5 AC8-AC10 (the user, 2026-11-16): the theme tasks check the `plain` and `badge` baselines; two-buttons, no-image, rating, pros-cons and all-fields move to 19A, 20A, 21A, 21B.
- 139. Task 18S14 (2026-11-16): the schema reads `[affibloom]` now; `[lasso]` joins it in 27A.
- 140. Task DT1 (2026-11-16): the display baselines are drawn on Local with Lasso 370, not staging; three settings modes.
- 141. Settings → Display (the user, 2026-11-16): a full save stays on the page; Lasso reloads.
- 142. Admin design (the user, 2026-11-16): Meadow replaces Aurora; restyle Tasks MD1-MD7 (Batches 4.M1-4.M5) before F4.
- 143. Saving (the user, 2026-11-16/17): every settings screen saves over REST and stays on the page; no save without JavaScript.
- 144. Settings → Display (the user, 2026-11-17): Single, Grid and List switch in place and one save stores every type, as in Lasso.
- 145. Loading states (the user, 2026-11-17): content that waits for the server shows a skeleton; actions show `.is-busy`.
- 146. Standalone Affibloom (the user, 2026-11-17): the Lasso migration and content conversion move to a separate Affibloom Lasso Migrator plugin (Phase M); no other plugins' compatibility; Phase 5 core only; A1 and A2 reversed.
- 147. Task 30A (2026-11-18): the link check covers only products used in content while "locations only" is on (F21's index exists); a first failure on an unchecked product is a row with code `''`, not an issue.
- 148. Task 30C (2026-11-18): Amazon products are checked through C1's API whenever its credentials work; a down API or refused credentials check nothing; C1E must leave them to its refresh (R5).
- 149. Task 31D (2026-11-19): the export's three Lasso-named columns are `fields`, `custom_redirect`, `custom_thumbnail` (no Lasso names in Affibloom).
- 150. The user (2026-11-20): Show Features on the product page draws its bullets in the box's description block; the preview frames load the site's icon CSS.
- 151. Table builder rework (the user, 2026-11-21): live drag-and-drop by grips and whole cards, keyboard drag, a selection inspector, an icon picker for badges and buttons; Move buttons and the button dialog go.
- 152. A Fields tab in the header (the user, 2026-11-22): Dashboard, Opportunities, Tables, Groups, Fields, Settings; Lasso's header has none.
- 153. The setup wizard's "no Lasso data" test is the migrator's (`affibloom_setup_wanted`, Phase M); wizard steps save through hidden tabs.
- 154. Phase 8's close (the user, 2026-11-22): no GitHub release or `v1.0.0-rc.1` tag; the open decisions follow Lasso (box colours, CSV without BOM); the editors' run, the guides' review and the sign-offs done as Lasso's workflow, by Claude at the user's word.
- 155. Task 24F added (the user, 2026-11-23): the Share kit's Copy block, promised for Phase 4 (F1 D4, deviation 69) but in no task; built after Phase 8's sign-off.
- 156. Gap fixes after Phase 8 (the user, 2026-11-23): Tasks GF1-GF7 (Batches 8.G1-8.G4) and Phase M's 26Q, from the Lasso/Affibloom gap audit.
- 157. Task GF6 (2026-11-23): a turned-back link is the column `use_original` (schema step 12), not `assigned_product_id = -1`, so untied-link queries stay as they are.
- 158. Task 24G added (the user, 2026-11-23): an icon picker for badge and button texts in Customize Display and the Elementor widget; stored with single quotes.
- 159. A Lasso box Affibloom's block cannot hold becomes [affibloom] in a Shortcode block (the user, 2026-11-23); F10 D16 changed.
- 160. Task LM6A added (the user, 2026-11-23): the blocks Lasso's scan rewrote (GenerateBlocks `rel`, some images) fail the editor's check once LM6 removes the stamps; repaired apart from LM6.
- 161. 26B changed (the user, 2026-11-23): `--update --force` gives a product adopted from the one-time import Lasso's values, its Amazon price kept (Task 29A).
- 162. Affibloom's box prints the stored title, not Lasso's cleaned one ("Men’s", not "Men s"; the user, 2026-11-23; Tasks 29A, 29B).
- 163. Task LM10 (the user, 2026-11-23): no tag for the migrator's release, as 154; Finish keeps one mark, `affibloom_lasso_finished`, until uninstall, so nothing runs again.

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

71a. **Two broken button icons fixed (2026-11-13, the user: "two icons are shows broken. full review and youtube. fix
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

77. **Bulk add: one click on Add Link from the textarea submits (2026-11-13, found while adding three links in Brave).**
    Pressing the button blurred the textarea, the blur swapped it for the shorter preview, and the button moved
    away before mouseup, so the first click did nothing. Lasso has a mousedown workaround for the same thing. Now the
    button's mousedown keeps the focus in the textarea, and `addMany` shows the preview itself once the confirm is
    accepted. F18 AC6 gains a final step: one click from the textarea reaches the confirm (dismissed).

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

91. **Task 2X1 (2026-11-14), the product page's four gaps; done (the user's F1 and F29 runs, below; the 375 px look still owed).** Red: the new
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
    - **Rerun:** AC2, AC14 and AC12 passed (the trace-file errors were environmental). AC10's dialog half passed; its
      failure stub let PUT and DELETE through, because `wp.apiFetch` sends them as POST with `X-HTTP-Method-Override`;
      the stub now reads that header (a test fault, not a code fault). AC10 then passed (the user's run): **Task 2X1 is done.** The 375 px look is still owed (a resize through the Brave extension did not change the viewport).
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

93. **Task 2X4 (2026-11-14), a kept redirect that ends at Amazon is an Amazon product; built; F2 and F18 passed
    in Claude's runs (deviation 95a).** `create_from_url()` now asks `kept_redirect()` when the pasted link is not Amazon and the resolver
    found a different final URL (a geni.us link with "Keep geni.us formatting" on): if the final URL is Amazon, the
    product is typed Amazon with the ASIN, store and search key of where it ends, named from that link, and the stored
    link is the one pasted. A product already in the library for that ASIN is found first (a duplicate, as Lasso's
    `is_lasso_url_exist` uses the final URL), and a trashed or retired one is revived. A redirect that ends elsewhere stays
    `other`. This **reverses `test_geni_us_is_kept_as_pasted…`, which asserted type `other`** (written in 12D); the plan's
    task text overrules it. Lines 376-380 in the task text are now elsewhere in the file. Red first (type and a missing
    duplicate); 453 unit tests pass. Not checked by hand: it needs a stubbed redirect and a signed-in page.

94. **Task 2X5 (2026-11-14), bulk add past a failing link and "Continue Anyway"; built; F18 passed in Claude's run (deviation 95a).**
    - `BulkAddService::process()` keeps Lasso's silence for a refused or duplicate link (`InvalidField`,
      `RuntimeException`) and now also catches any other `Throwable`: the link goes into the new `BulkRun::failed` list
      (stored with the run, empty for a run stored before) and the run moves on. Nothing reads the list yet; Lasso's bulk
      run reports nothing (F18 section 8), so no screen shows it. It is there so a failure is recorded, not swallowed.
      The fake repository gained `failure`/`fail_at` to throw on the Nth new product.
    - **"Continue Anyway" (the user's choice, 2026-11-14): the click sends the same link again.** Lasso opens a new link
      page with the typed URL filled in; Affibloom's product page opens only existing products, so a retry was chosen
      over creating a product from a link that failed. After a server error in the single add the button reads
      "Continue Anyway" with `data-continue="1"`; typing in the field, closing the dialog or submitting puts "Add Link"
      back (Lasso does the same on change, paste and keypress). Bulk-mode errors do not use it (Lasso's `!form_add_link_is_text_area()`).
    - New F18 case (route stub answers the POST with 500). Not run: the spec needs a sign-in; it loads (`--list`).

95. **Task 2X6 (2026-11-14), brag mode removed; built; F16 passed in Claude's run (deviation 95a).** The two controls, the keys
    `enable_brag_mode` and `brag_url` (defaults and the Display tab's field list) and the old test are gone; a posted
    value is not stored, and a value already stored is not read, so the next save drops it. `SettingsService`'s `url`
    cleaner, whose only caller was `brag_url`, went with it. New unit case and a new F16 case ("no brag control");
    F16's fixture no longer lists the keys. 456 unit tests, lint and PHPStan clean. Not looked at in a browser at 1280
    and 375 px (signed out).

95a. **Claude runs the signed-in specs itself (the user, 2026-11-14).** With the saved session and
    `.local/no-login.config.ts` (no sign-in is typed). 2X4-2X6: F2, F16 and F18 pass, 31 tests (1 skipped, the Phase 3
    box test). My new "Continue Anyway" case first failed because its route stub missed the REST URL (it now matches
    `/affibloom/v1/products` with or without a query). Two full runs also showed failures that passed alone: another
    session's unfinished Groups work in the same tree briefly broke the site (a WordPress critical error), and runs
    overlapped. So the "waiting for the user's run" notes in deviations 93-95 are settled; no browser look at 375 px yet.

96. **Tasks 9B-9L (2026-11-14), Settings → General in Lasso's structure; built in one session at the user's word
    ("9B to 9L one by one, run the tests at the end"), waiting for the user's signed-in F9 run.** Built: two columns of
    cards (9B), the unsaved-changes guard on all three Settings tabs (9E), duplicates off (9C), Update Link Settings (9D),
    Google Analytics (9F), prefix and Smart CSS (9G), Performance (9H), Custom Link Detection (9I), Notifications (9K)
    and the `&support=1` view (9L). Checks run once at the end: 495 unit tests, `composer lint` and `composer analyse`
    clean; the three tabs render without PHP notices when rendered through WP-CLI as an administrator. **Not run:** the
    14 F9 acceptance tests (the browser pane is signed out; they are written and listed), and not looked at in a
    browser at 1280 and 375 px. Differences from the plan's text:
    - **Not built, no caller yet:** `new_tab2` / `nofollow2` (R31: defaults of a second button with no control; F29's
      buttons carry their own flags); `rebuild-index-modal.php` and the Rebuild Database button (F21's route is Task
      35G); `Segment Analytics` (cloud, decision B1).
    - **`_affibloom_url_plain_key` is written on every save of a non-Amazon product**, not only while duplicates are
      off, so switching the setting off later finds products saved since. Products stored before this task have no key
      (Lasso is the same: its `url_without_arguments` is written on save); the Lasso import saves through the
      repository, so imported products get one. `PlainUrlKey::of()` is empty for an Amazon link.
    - **Files moved off the shared ones** (two other sessions were editing `admin.css`, `admin.js`, `AdminAssets.php`):
      the guard is `assets/admin/settings-guard.js` (not `admin.js`) and the styles are `assets/admin/settings-general.css`;
      the "?" tips are hidden by a body class from a new `Interface\Admin\HelpTips` module (not `AdminAssets`), because
      `AdminAssets` has no settings. The guard's comparison is not shared with `product-save.js`: that one also watches
      rich fields and extra buttons, so the two are not identical.
    - **The support fields are saved only when the form carries `settings[_support]`**, otherwise an ordinary save would
      read their absent checkboxes as "off". `SettingsController` keeps `support=1` in the redirect.
    - **Sponsored's fresh-install default is now on** (D4); `Settings::DEFAULTS` and `ProductTest` changed. Custom
      Link Detection is a set of chip-styled checkboxes, not select2; a refused list keeps the old choice.
    - **Server-side GA rule:** Click Tracking is stored off without an ID, Pageview and IP Anonymization off without
      Click Tracking, as the form does. Performance presets are applied by the page, not the server, so a stored
      Balanced with 80 is never rewritten (D13).
    - **Plugin.php and AdminAssets.php** were committed with only this task's hunks; the other sessions' uncommitted
      changes in them stay uncommitted.

97. **Task G1 (2026-11-14), the Groups list and a new group; done, checked by Claude's own runs.** Built outside-in from
    `tests/E2E/acceptance/F23.spec.ts` (AC1, AC2: five tests). The header has Lasso's **Groups** tab after Dashboard, the
    menu lists Groups (a group's page is registered hidden and guarded, as the product page is), the list is Lasso's
    (Name, Description cut at 237 characters, Links = published members, newest first, ten a page, "Search Groups" with
    spaces as wildcards, Lasso's empty state) and redraws in place through `GET /groups`; **Add New Group** saves through
    `POST /groups` with "Updating Group" and opens the new group's Details tab with "Group saved.". Differences from the
    plan's text:
    - **No `GroupInvalid`:** the two refusals are the existing `InvalidField` (field `name`, reasons `group_name_required`
      and `group_name_taken`), which `FieldError` already turns into the 422 the contract describes; a second exception
      type would have no caller that tells them apart.
    - **`GroupService::get()` and `GroupRepository::find()` come from G2 into G1**, and the Details tab is built **read
      only** with its Save Changes switched off ("Soon"): AC2 ends on the group's Details tab, so the page a save lands
      on had to exist. G2 makes it editable and adds Delete This Group.
    - **The save dialog's title is a parameter:** `affibloomSaveDialog.open( text, title )` (admin.js) so it can say
      Lasso's "Updating Group"; its default is still "Updating Your Site".
    - **Lasso's cut counts characters, not bytes** (`Group::list_description()`; Lasso's SQL `LENGTH` counts bytes and
      can cut a multibyte character in half). The list prints descriptions escaped (Lasso prints them raw).
    - **A group's name is found through `term_exists()`**, which also compares the slug the name would make, as
      `wp_insert_term()` refuses on it; a refusal that gets past the check (`create()` null) shows the same sentence.
    - **The Groups list has no page-size select** (Lasso's is ten a page): its footer is the range and the page links.
    - **`PluginTest` changed:** the menu now has five submenu pages, two of them removed from the menu (product, group).
    - **F23.spec.ts waits for the page's load** after the click on Add New Group: a click on a link does not wait for the
      page's scripts, and the form is sent by one of them (a first run failed on it twice, then passed five times in a row).
    - **Playwright runs by Claude shared `test-results/` with another session's and failed once** ("no such file ...
      .playwright-artifacts"); later runs used `--output=<scratchpad>`.
    Checks: 466 unit tests, `composer lint` and `composer analyse` clean on this task's files (the rest of the tree was
    mid-edit by another session at the time); F23 5 passed twice in a row; F19 and `admin-shell` 15 passed, 1 skipped (the
    header changed); looked at 1280 and 375 px (list, new-group form with its error, Details tab with the toast).
    **Content width:** another session's change (then uncommitted, since committed as `38b8695`) makes 1320px the default for every screen and removes
    `.affibloom-page--wide`; the Groups templates were written with that class and now carry none. Until that change is
    committed, the committed CSS still gives them the old 1080px default.

98. **Tasks G2-G5 (2026-11-14), the rest of Groups (F23); built in one session at the user's word ("Let's do G2-G5 ...
    run the test for all these steps at once"), tested together after the last one.** G2 a group's Details (edit, slug
    kept, Delete This Group with "Delete This Group?" and "Hold Up", the server's own 409), G3 the group picker on the
    product page and the Dashboard's Groups column, G4 the Links tab (order by drag or Move up / Move down, Remove one or
    the selected, Add a Link), G5 the quick edit's picker, the Group filter (in the address, the saved views and the
    pills) and the bar's Add to Group. Differences from the plan's text:
    - **One commit for the four tasks**, not one each: they share `GroupService`, `GroupsController`, `group.php`,
      `groups.js` and the Dashboard files, so they cannot be told apart in the tree.
    - **`GroupService` takes the product repository** (a group's members are shown as products), and `ProductsController`,
      `ProductScreen`, `DashboardScreen` and `DashboardController` take `GroupService`. **`groups` is not in
      `ProductInput`/`ProductService::update()`:** `ProductsController::update()` stores the product, then the groups
      (a refused product save stores none); `ProductResource::full()` gets the groups as a second argument.
    - **`ProductService::search()` / `ProductRepository::search()` are not new:** `GET /products?search=&page=&group=`
      uses `ProductService::list()` (the Dashboard's rule), ten a page, with an `in_group` flag.
    - **The picker lives in `assets/admin/group-picker.js`**, not `admin.js` (another session was editing it); the page's
      groups are one `affibloom-group-choices` JSON block (`partials/group-choices.php`), printed once per page.
    - **New classes:** `AlreadyInGroup`, `GroupInUse`, `GroupNotFound`, `GroupOrder` (rank, append, without, move);
      `ProductRepository::page()` and `ProductService::list()` take a trailing `$group = 0` (0 all, -1 "Not in Groups",
      else a term ID); `SavedView` has `group` and `GROUP_NOT_IN`; a saved view whose group is gone shows all groups.
    - **A group opens on its Links tab** (the list row's link has no tab), Details after a save, as Lasso.
    - **Not done / left for later:** a group made in the quick edit or the bar is not added to the open filter's select
      until the page is reloaded; the Links tab's "Links n" count and the Delete button's count are kept up to date by
      script only; Copy Shortcode stays "Coming soon" (D5).
    - **375 px header:** the shared header pushed the page 6px past the screen; at 782px and below the wordmark is
      visually hidden (the logo stays, the text is for screen readers), the tab gap is 2px and the header gap 12px.

99. **Task TL1 (2026-11-14), activation and deactivation (F37 AC1); done, checked on Local with WP-CLI.** Differences
    from the plan's text:
    - **The coordination point is moot:** the user's `affibloom.php` edit was settled and committed in Task 2X0 (open
      decision F7's answer), so the two hook lines were added on top of the committed file.
    - **Two ports, so `Application\Lifecycle` makes no WordPress call:** `Application\Schedules` (`clear()`, implemented by
      `Infrastructure\WordPress\CronRegistry`) and `Application\PluginState` (`record_activation()`,
      `flush_rewrite_rules()`, implemented by the new `Infrastructure\WordPress\OptionPluginState`, which owns the option
      names `affibloom_version` and `affibloom_activated_at`).
    - **How each cron module lists its hooks:** `BulkAddCron::HOOKS` (a constant); `Plugin::cron_registry()` builds the
      `CronRegistry` from each module's `HOOKS`, so a new cron module adds its list there. `Plugin::activate()` and
      `Plugin::deactivate()` are static and build only the lifecycle, not the request's modules.
    - **`affibloom_activated_at` is the latest activation's time, not autoloaded**, as Lasso's
      `lasso_plugin_activated_at` (class-activator.php line 53); `affibloom_version` is autoloaded, as `lasso_version`.
    - **The outermost test that runs here is `tests/Unit/LifecycleHooksTest.php`** (through `Plugin::activate()` /
      `deactivate()`, red on the missing methods). `tests/Integration/LifecycleTest.php` is written, not run (no
      wp-env, deviation 10).
    - **Not in TL1:** the Migrator step on activation and the version watch are TL2's.

100. **Task TL2 (2026-11-14), the upgrade routine (F37 AC2); done, checked on Local.** Differences from the plan's text:
    - **`Infrastructure\Schema\Table` is not built yet:** nothing calls it until C1C's `affibloom_amazon_products`
      (the rule "nothing is created before it has a caller"); C1C builds it, with dbDelta and wp_posts' collation
      (F37 analysis). TL2 builds `Migrator` and its numbered steps only; `Plugin::schema_steps()` is empty for now.
    - **The classes, by layer:** Application `Upgrade\{Upgrader, UpgradeResult, StepFailed}` and two ports,
      `Upgrade\UpgradeLock` (`Infrastructure\WordPress\OptionLock`, option `affibloom_upgrade_lock` holding its expiry,
      not autoloaded, 5 minutes, as Lasso's `lasso_db_migration_lock_v2`) and `Upgrade\Migrations`
      (`Infrastructure\Schema\Migrator`, option `affibloom_db_version`). Interface `UpgradeHooks` (`admin_init`, not AJAX or
      cron; `init` at 20 for the version watch) and `Cli\UpgradeCommand`. The version watch is
      `Lifecycle::watch_version()`; `PluginState` gained `stored_version()` and `record_version()`, and
      `record_activation()` became `record_version()` + `record_activation_time()`.
    - **Each step is recorded as it finishes** (Lasso records its version once at the end): a failed step keeps the
      version of the last step that finished, is retried on the next run, and the steps before it do not run again.
      Any `Throwable` from a step becomes `StepFailed`.
    - **`pending()` is checked before the lock,** so an admin page with nothing to do writes no option.
    - **A failed step does not break the page** (Lasso's `finally` lets the error through): the lock is released, and
      `UpgradeHooks` shows an error notice to users with `Capabilities::SETTINGS` ("Affibloom could not finish updating
      its data (step n: error). It will try again on the next admin page."). `wp affibloom upgrade` exits 1 with the step.
    - **PHPStan reads a hand-written `tests/stubs/wp-cli.php`** (`scanFiles` in `phpstan.neon.dist`) for the three
      `WP_CLI` methods: `php-stubs/wp-cli-stubs` cannot be installed (Packagist unreachable, environment.md).
    - **Open, for ON2:** on Affibloom's own screens every `admin_notices` notice (this one too) is moved by core's
      `common.js` into a hidden modal's heading, because the screens have no `.wp-header-end`; it shows on core screens
      (seen on Plugins). ON2 (the notice region) places it.
    - `tests/Integration/UpgraderTest.php` is written, not run (no wp-env, deviation 10).

101. **Task ON1 (2026-11-14), navigation in Lasso's order; done, checked by Claude's own runs.** The user asked for ON1
    and ON2 in one session ("Do Task ON1 and Task ON2"), overriding the one-task rule for this session. Differences from
    the plan's text:
    - **No `admin_title` filter:** the product page is registered with the title "Link Details", and WordPress builds
      the browser title from the registered title before `admin_head` removes the page from the list, so the title
      reads "Link Details ‹ …" without a filter (R13, D6). The other screens' titles are their menu names.
    - **The header gets the navigation from `Template::navigation()`:** `Template` takes the `AdminNavigation` in its
      constructor (one instance, built in `Plugin::navigation()`), so no screen passes tabs in its view; the templates
      no longer pass `current` either: the header asks the navigation which item owns `?page=` (`AdminMenu::requested_page()`,
      also used by the `parent_file`, `submenu_file` and `update_footer` filters).
    - **`NavItem`'s label is a closure,** because the items are built when the plugin loads and `__()` before `init`
      would load translations too early. `AdminNavigation::register()` refuses a key outside Lasso's list.
    - **Fields is listed in the WordPress menu now** (Lasso's R11, AC2): FL1 had hidden it as well as leaving it out of
      the header; F26 D13 only keeps it out of the header, which stays so (a menu item without a tab).
    - **Also in this task:** the logo and wordmark link to the Dashboard (R1, not in the plan's table); WordPress's
      version line is blanked on Affibloom's pages only (N6); the wordmark shows again at 375px, since the folded tabs
      leave room for it (the 782px rule that hid it went with the tab labels' rule).
    - **Checks:** unit 556 (`AdminNavigationTest` 6, `PluginTest` now asserts the submenu order), lint, analyse; F38
      AC1-AC3 4 passed; `admin-shell.spec` 2 and F23 AC1 passed. `admin-design.spec` failed twice outside the header,
      not from this task: the Dashboard's "first card over the band" check (y 578, the Dashboard's tiles and filters
      changed since) and Settings' "Sponsored" row chip (9B-9L's new rows); its header assertions passed. F1 and F19
      were not run: another session was changing the Dashboard and F19 in this tree at the time; F1's two header
      lookups (`.affibloom-header__nav a` with "Settings") still match at 1280px.

102. **Tasks 13F-13J (2026-11-14), the Dashboard's second pass (F19); five tasks in one session at the user's word
    ("Let's do the Dashboard, second pass (F19) from Task 13F to Task 13J"), one commit each:** 13F `c0b7021`, 13G
    `67a5562`, 13H `a09a1f1`, 13I `7c400e9`, 13J `2c9a0da`. Before them, at the user's choice, the uncommitted Groups
    work G2-G5 (deviation 98 and its session-log entry said committed; it was not) was committed alone as `97c1ac0`, without TL2's files.
    Differences from the plan's text:
    - **13F:** the hidden inputs come from a new `DashboardUrl::args()` (the list's arguments, each left out at its
      default; `to()` builds on it), passed to `list-args.php` as `args` + `except`. The panel's GET form wraps Group and
      Link type (G5 added Group); Apply, ×, Save as and the footer are shown or hidden by `no-js` CSS. Two specificity
      fixes: `.affibloom-admin [hidden] { display: none !important }` needed a stronger `!important` for the panel, and
      `.affibloom-button` (later in the file) needed one more class on `.affibloom-no-js`. A `?post_id=` with no
      published product shows the empty-search message. `post_id` rides in the script's list state; saved views do not
      store it, as Lasso's do not.
    - **13G:** the seed writes no product through `save()`, so it writes `_affibloom_button_url` itself (not re-run on
      Local). **Products stored before 13G get the rows from upgrade step 1** in `Plugin::schema_steps()` (TL2's
      Migrator; `PostTypeProductRepository::index_all_button_urls()`, sharing one private writer with `save()`); the
      first step is therefore not C1C's table, which takes the next number. On Local (backup
      `app/affibloom-backup-2026-11-14-before-13g.sql`) the step ran on an admin request: 213 products with buttons, 91
      link rows, every product's rows equal to `Buttons::urls()`. `createProduct()` in `tests/E2E/utils.ts` writes the
      rows too. `LifecycleHooksTest` now stubs `affibloom_db_version` so activation runs no step.
    - **13H:** AC10's sample of modified times is the five oldest products (the newest are other tests' and were
      deleted mid-run once). The mutation check (pager forced to one more page) made AC4 fail as it should. Two full F19
      runs in a row kept the order of all 552 products; both runs also had scattered failures (AC2-AC16), because
      another session's Playwright run and the ON1 edits shared the copy at the time (Local answered 502 once); the
      failing tests passed when rerun alone.
    - **13I:** the code was written before AC17/AC18 (the site was down from another session's half-done edit and the
      runs held the spec file); each test was then shown to fail with its change taken out (ties `ID ASC`; `counts()`
      not called). AC18 compares the tile with the database's count, not "count − 1": other runs add products meanwhile.
      `ProductListTest` has the tie case; the integration suite still does not run here (deviation 10).
    - **13J:** `SavedViewService::delete()` throws `ViewNotFound` (it changed nothing before); `DashboardController`
      answers 404 `affibloom_not_found` with `Messages` `view_not_found`. The five client texts are `data-*` attributes on
      the panel, not in `Messages` (the script needs them in the page). Save is disabled while it runs (no spinner).
      "All links" is told apart as the first pill (pills carry no ID). `SavedViewServiceTest` is new.

103. **Task ON2 (2026-11-14), the notice region, foreign notices and the update notice; done, checked by Claude's own
    runs.** Same session as ON1 (the user asked for both). Differences from the plan's text:
    - **A notice is a key, not words:** `Domain\Shell\ShellNotice` holds a key, a tone (success, error, warning,
      update), values, a link and whether it has a ×; `Interface\NoticeText` gives the words, with %s where the link
      goes, as `Messages` does for reason codes (Application has no WordPress calls). `NoticeText` is its own class
      because `Messages.php` held another session's uncommitted edits.
    - **The region is rendered by the header partial** through `Template::notice_board()` (as ON1's
      `navigation()`), so no screen template changed; `partials/notices.php` and `partials/shell-notice.php`.
    - **The port is `Application\Shell\UpdateSource`**, implemented by `Infrastructure\WordPress\PluginUpdates`
      (`get_plugin_updates()`, `update_plugins` capability, WordPress's nonce'd `update.php` link).
    - **A failing provider is reported with `wp_trigger_error()`** (only with WP_DEBUG), injected into `NoticeBoard` as a
      closure.
    - **TL2's open item is closed:** `UpgradeHooks` is also a `NoticeProvider`, so a failed upgrade step shows in the
      region on Affibloom's pages (where `ForeignNotices` removes every admin_notices callback) and stays an admin
      notice on WordPress's pages; both use `NoticeText`.
    - **`ForeignNotices`** removes admin_notices, all_admin_notices, network_admin_notices, user_admin_notices and
      in_admin_header on Affibloom's pages at admin_head 99999, then puts back `wp_admin_bar_render` (priority 0).
    - **Script notices:** `affibloomNotice( tone, message )` returns the notice; an unknown tone is a warning; an error
      without a string gets Lasso's fallback, another tone without words adds nothing. Every × (PHP or script) removes
      its notice without saving anything (Lasso's collapse) and moves the focus to the next ×.
    - **Design:** tokens success-700 `#136C3A`, success-50 `#E7F6ED`, danger-50 `#FDECEF`; "Notice tones" and "Notice
      region" in the design system. The region is a navy strip between the header and the band.
    - **Tests:** `NoticeBoardTest` (3), `UpdateAvailableNoticeTest` (2), an `UpgradeHooksTest` case, a `PluginTest` case;
      `tests/Integration/ForeignNoticesTest.php` is written, not run (no wp-env, deviation 10). F38 AC4 copies
      `tests/E2E/fixtures/foreign-notice.php` into `wp-content/mu-plugins` for the test and removes it after; AC6 keeps the
      site's `update_plugins` transient in a test option and puts it back.

104. **Settings save the Lasso way (2026-11-14; the user: "The settings pages are saving with page reload. but lasso
    work differently. Investigate the issue and fix it.").** Outside the task list; reverses F9 decision D5. Lasso's
    Save Changes (settings.js 2379-2464; F9 R41) sends the form by AJAX behind "Updating Your Site" and then calls
    `location.reload()`; its Display Reset Settings (2139-2168) does the same with "Resetting Display Settings".
    Affibloom posted the forms to admin-post.php and redirected, with no dialog. Asked which to build: the user chose
    **"Exactly as Lasso"** (dialog, then the same address again, the toast kept) over saving with no reload at all.
    - **Server:** `SettingsController` and `DisplayController` (save and reset) also hook `wp_ajax_<action>`, the same
      handlers; the new `Interface/Admin/SaveAnswer::go()` redirects a plain form and answers an AJAX request with
      `{ redirect_to }` (the address it would have redirected to). Nonces, capability checks and refused-field
      handling are unchanged.
    - **Browser:** `assets/admin/settings-save.js` (every Settings tab) sends a `form[data-ajax-save]` with `fetch`
      behind admin.js's save dialog, line "Saving your changes." (Lasso's url-save.php line describes a link scan
      Affibloom does not run, as F1 D13), then loads the answer's address; the dialog stays up until the new page
      shows. After the load it takes `affibloom_notice` and `refused` out of the address (`history.replaceState`), so
      the address is the editor's own and a reload does not toast again. A failure closes the dialog and shows Lasso's
      "Try again. For some reason your settings didn't save." (reset: "Error resetting display settings.") as a red
      toast; the edits stay; `settings-guard.js` hears `affibloom:save-failed`, guards again and drops `redirect_to`.
      Without JavaScript the forms post to admin-post.php as before.
    - **Templates:** the three Settings templates carry the save dialog and toast template; the forms carry
      `data-ajax-save` and their texts.
    - **Tests:** F9 AC17 (new, first named AC16 and renamed 2026-11-15, deviation 107: dialog, admin-ajax request, toast, clean address on General, Amazon and Display/Colors);
      `tests/E2E/settings.ts` `saveSettings()` waits for the reload, used by F9 and F16 where a test reads the option
      or leaves right after Save. F16 11/11, F17 5/5, F28 7/7, F9 AC17 passed. F9 AC2, AC3, AC7, AC8, AC9 and AC15 fail
      for reasons outside the save path (AC3: the Sponsored toggle's Update Link Settings dialog covers Save; AC8: the
      first "Dashboard" link is WordPress's; AC15: "Cron" matches two headings), seen while other sessions ran
      Playwright on the same site; not changed here.

105. **Task FL5 (2026-11-14), the product page's field rows corrected to Lasso's rules (F26 AC6-AC7); done, checked by
    Claude's own runs.** Built outside-in from F26 AC6-AC7 (red: the padded lines stored as typed, no grip), then
    `FieldValueTest` and a `ProductFieldsTest` case (red: no `FieldValue`). Differences from the plan's text:
    (a) **The drag is shared code:** the field rows are the second pointer-events drag, so Groups' drag was taken out of
    `group-members.js` into `assets/admin/drag-rows.js` (`window.affibloomDragRows`), which both pages now load; the
    plan's file list names only `product-fields.js`. F23 AC5 (the group drag) and AC10 passed after the change.
    (b) **The reason code is `field_value_long`**, not the analysis's `too_long`, after FL2's `field_description_long`;
    the answer is as planned: 422 `affibloom_invalid_field`, `data.fields["rows.<field>"]` = "Keep this value under
    1,000 characters.". One long value refuses the whole save (nothing stored), as `update()` stores all rows at once.
    (c) Renamed built-ins on the rows and the wildcard library search were already right since FL1/FL3; FL5 only adds
    their AC6 checks. (d) While a row is dragged, the Move buttons' disabled states update on the drop, not during
    the drag. (e) Test detail: `wp.apiFetch` sends PUT as POST with `X-HTTP-Method-Override`, so AC6-AC7 wait on that
    header; the drag test scrolls the list into view first (the rows are below the fold at 1280x900).

106. **The Filters panel remembers every close (2026-11-14; the user: the Dashboard "keeping open the filter
    settings").** F19 R17/D16 followed Lasso: `affibloom_filters_open` was written by the Filters button and × only,
    so a panel closed with Escape or an outside click (the usual ways, since it is a card over the table) reopened on
    every load. Now Escape and an outside click write `'0'` too (`dashboard.js`); the Filters button still writes
    `'1'`. Deviates from Lasso's R17 and amends D16 (F19 requirements). AC20 changed first (red: open after Escape and a
    reload), then green. Users who already have `'1'` stored see it open once more, until they close it any way.

107. **Phase 2 audit (2026-11-15; the user: "thoroughly re-review Phase 2 ... I switched between two different accounts").**
    Phase 2 was built by several sessions, some at once and on two accounts; the records had drifted from each other.
    The code was sound (lint, PHPStan, 571 unit tests clean; tree clean, `main` level with GitHub). What was found and
    done:
    - **Records.** Deviation numbers 71 and 96 were each used twice: the button-icon fix is now **71a** and "Claude runs
      the signed-in specs itself" **95a** (moved after 95); 77 now follows 76; citations in the plan, this file and
      phase 9 follow. The status table, "Next up" and the headings of deviations 91, 93-95 (still "waiting for the
      user's run") were brought up to date; the plan's "Tasks 2X1-2X5" heading, its Status section and the notes that
      `affibloom.php` fails lint (settled in 2X0) were corrected.
    - **Ticks.** 9B-9L were ticked with their "Run and look" steps open and F9 failing (AC2, AC3, AC7, AC8, AC9, AC15,
      deviation 104); 2X4 was ticked with its by-hand step open; 2X2's and 13H's double runs, G2-G5's F23 results and
      FL1-FL4's look were never recorded. All are now made and noted on their steps (below).
    - **F9 (`F9.spec.ts`), six test faults, no code fault:** AC2 needs the box (Phase 3; now `test.fixme`, as the exit
      check moves it); AC3 never answered 9D's "Update Link Settings" dialog, which covered Save; AC7 compared whole
      addresses (the second add lands on `&is_duplicate=1`) and its text matched the hidden repoint dialog too; AC8
      clicked WordPress's own "Dashboard" link (now the header's) and filled Custom CSS in a closed accordion (now
      Colors' Primary button text); AC9 clicked the switch's label, which Playwright treats as the disabled switch
      (now the row, as an editor clicks it); AC15's "Cron" also matched "Cronjob Manually" (now exact). **The AJAX
      save test, written as a second "AC16", is AC17** in `F9.spec.ts` and `F9/requirements.md` (AC16 is the Uninstall
      card, Phase 8); the exit check lists it.
    - **F1 (`F1.spec.ts`), a test race:** AC16 and AC19 started `page.reload()` without awaiting it and dismissed its
      beforeunload, which sometimes failed with "Object with guid response@… was not bound in the connection" (seen in
      2 of 4 full runs and 1 of 3 alone). The page now reloads itself (`page.evaluate`); both then passed 4/4 alone.
    - **Runs (quiet: no other session, one Playwright run at a time):** F9 14 passed, 1 skipped; F23 14/14; F1 twice
      in a row, 27/27 each, the 863 products that existed before in the same order after both; F16 11 passed, 1
      skipped; F19 20/20 (one run). One earlier F1 pass of this audit failed AC16 while Claude's own screenshot script
      ran beside it (two Playwright processes; Claude's error, rerun).
    - **By hand:** 9D's Override All Links on all 1,025 products through the real service, counted and restored; 9H a
      real General save keeps Balanced + 80; 9K the keyboard reaches a "?" tip with its name and text; 2X4 with a
      temporary mu-plugin stub (a geni.us link answering 301 to Amazon): type `amazon`, ASIN set, link kept as
      pasted, Amazon Image and no Permalink; Settings (all tabs, `&support=1`), the Fields screens and the product page
      at 1280 and 375 px.
    - **Fix (the user, 2026-11-15: "also fix the Button alignment clipping at 375px"):** in the Display rail the
      option groups (`.affibloom-options`) and steppers shrank below their content in a narrow row and `overflow:
      hidden` cut them ("Rig…" for Right). `.affibloom-stepper-row` now wraps and the groups never shrink
      (`flex: none`, `margin-left: auto`): a row too narrow for both puts the control on its own line, on the right
      (Button alignment and Description lines at 375 px); 1280 px unchanged; no group clipped at either width.
    - **Commits that had no record:** `38b8695` (1320px content width on every page; deviation 97 called it another
      session's uncommitted change), `afc43dd` (Custom Link Detection lists public post types only; its chips renamed
      off a clashing class), `98d9105` (the product price editor laid out as Lasso's: format buttons in the label row,
      one-line box, `$99.99` placeholder), `5e89914` and `7f286a3` (the group form card fitted to its fields and
      centred), `8fa4c8d` (F23 AC9 reopens the Filters panel the saved view's pill closes). Deviation 104's commit is
      `9109716`, 106's `e0d8256`. FL1-FL4 (`89ef637`, `b366954`, `ba6e821`, `e05a9c7`) and 2X2 (its test is in 2X1's
      `9a6edf3`) have no deviation entry of their own; nothing known about them differs from the plan.
    - **Shared commits, as recorded:** 9B-9L `8277caf`, G2-G5 `97c1ac0`; part of 2X1 is in 2X0's `7c73290`.
    - **Not done:** `tools/reset-local.sh` was not run (its snapshot predates the seeded library and the saved
      sign-in; "Next up" 4). The copy holds the test data of these runs (saved views "AC9 view-…", "F1 AC10 …" fields
      and products). **Seen, not changed:** at 1280 px the product page's Button 1 row squeezed the Button Text field
      to about 55px beside the Primary Destination URL (fixed the same day, deviation 108); Settings → General's cards leave uneven gaps when the columns
      stack at 375 px (fixed the same day, deviation 109).

108. **The product page's Button Text no longer squeezed (2026-11-15; the user: "Yes, fix it", after deviation 107 named
    it).** Lasso's button row is the link and the button text side by side, 8 : 4 (`url-details.php` 201, 208), in the
    5 : 7 page split, which Affibloom keeps. Affibloom's Button Text also holds the icon button (F28) inside a padded
    button block (F29), so its third left the words 96px at 1920px, 57px at 1280px and 32px at 1100px: measured, at
    every desktop width. A wider share would squeeze the link instead. **Change (`admin.css`):** the buttons list is a
    size container, and when it is under 554px (a pair under 520px, whose third would give the words under ~120px)
    each button's link and text stack, each full width; wider (the one-column layout at 960px and below while
    the block is wide enough: a 726px pair at 900px), the row stays side by side as Lasso's. At 1280px the words now get 279px. Both block types (Button 1 and an added
    button) checked at 1280, 900 and 375 px; no sideways scroll. No markup or script changed.
    - Checks: F28 6/6, F29 2/2 and F1 27/27 passed (quiet runs); no PHP changed, so lint, PHPStan and the unit tests stand as in deviation 107.

109. **Even gaps between Settings cards (2026-11-15; the user: "also fix the uneven card gaps at 375px").** Measured: on
    General and Amazon, cards in one column were 56px apart, but 28px where the stacked columns meet. Cause: Task 9's
    `.affibloom-settings .affibloom-card + .affibloom-card { margin-top: 28px }` (written before the two columns of
    7c) adds to the column's own 28px flex gap. So it was not only a phone fault: inside each column the gap was 56px at
    every width. **Change (`admin.css`):** `.affibloom-columns__column > .affibloom-card + .affibloom-card` has no top
    margin, so the column's gap (`--affibloom-space-section`) alone spaces its cards. Measured after: every gap 28px on
    General and Amazon at 375 and 1280 px; Display (no columns) keeps its rule. Looked at 375 and 1280 px.
    - Checks: F17 5/5, F16 11 passed and 1 skipped, F9 14 passed and 1 skipped (quiet runs; the skips are Phase 3); no PHP changed.

110. **Task C1A: the Product Data API card and Validate (2026-11-15).** The user asked for C1A-C1P in one session (an
    exception to the one-task rule, the user's choice), then paused the run after C1A because another session has work
    to apply first. Built as planned (R16-R25, D2-D4, D16), with these departures:
    - **The Amazon stub lives in the plugin, not in mu-plugins (the user, 2026-11-15).** `tests/E2E/amazon-stub.php` is
      loaded by `affibloom.php` only when `wp_get_environment_type()` is `local` (production and staging are not), and
      acts only while the option `affibloom_test_amazon_stub` names a scenario (`ok`, `reject`, `unavailable`,
      `throttle`); it counts what it answers in `affibloom_test_amazon_requests` (AC7's "nothing sent"). The plan's
      `tests/E2E/mu/amazon-stub.php` and copying into `wp-content/mu-plugins` are dropped.
    - **`AmazonCatalog::verify()` instead of `token()`:** Validate must always ask Amazon (Lasso's verify_credentials()
      never reads its cache), so C1A has an uncached check; the token cache (R8) comes with C1B, its first reader.
      `CatalogError` (planned for C1B) is created now, because the check is its first thrower (AuthFailed, HttpError).
    - **Words live in the Interface:** `CredentialsNotValidated` carries a reason (`amazon_missing`, `_rejected`,
      `_unavailable`); `Messages` and `AmazonController` hold Lasso's sentences. Save Changes' R23 warnings are two
      notice codes (`amazon-unverified-kept`, `-cleared`); the toast partial gained the failure look for them, and a
      failure toast now stays 9 s instead of 4 s (R23's warning is two sentences).
    - **No "Learn" links** (Lasso's help site; no Lasso branding): the intro's link goes to Amazon's Associates
      Central (`affiliate-program.amazon.com`).
    - **`default_country` was never built in 12A** (the plan's "depends on F2 12A's `default_country` reader"): C1A
      adds the setting (default `us`, saved with the tab, Lasso's 18 stores). `AmazonUrl::from_bare_asin()` still goes
      to amazon.com; making it read the setting is not in C1A's text and is left for the user to place.
    - **C1 AC2 was a stale test:** since Task 14A's endings, an Amazon add stays on the Dashboard with the new row on top
      (Lasso's), so AC2 now checks the stored product, not a product page. AC3 and AC5 read the old
      `amazon_api_*` keys in `affibloom_settings` and were rewritten for `affibloom_amazon_credentials`.
    - **The C1 spec puts the site's own credentials back:** while C1A was checked, credentials that look real (not
      validated) appeared in `affibloom_amazon_credentials`, entered outside this session. The spec now saves the option
      and the Default Tracking ID before the run and restores them after (checked: identical afterwards); a copy is in
      `.local/credentials-backup-2026-11-15.json` (gitignored).
    - Checks: C1 6 passed, 3 skipped (AC1, AC4: C1C/C1D; AC6: Phase 7), red first (no card; AC2's stale ending);
      unit 589 (Marketplace, Credentials, CredentialsService new); lint and PHPStan clean. Looked at 1280 and 375 px
      (fields were cramped at first; now 20px apart); no sideways scroll.

111. **Faster checks: test tiers, faster WP-CLI, one-call setup, automatic restore after runs (2026-11-15).** The user:
    testing took far longer than coding (runs of over an hour), so we analysed the workflow and the user chose all six
    changes ("Steps 1–6"). Measured first: unit 1.8 s, PHPStan 2.1 s, `composer lint` 34 s, one `tools/local-wp.sh`
    call 2.3 s; the browser suite (179 tests, one worker) is where the time goes. A tooling change; no plugin code changed.
    - **Test tiers** (`plans/batches.md` rule 5, `/next-task` steps 4-5): while coding, unit + PHPStan + lint; at a
      task's end, those plus **only that task's acceptance tests** (`-g "<AC or task>"`, `-x` while fixing) and the
      by-hand look if its UI changed; at a batch's end, the specs of every feature the batch touched, once, plus the 375
      and 1280 px looks; the full suite only at a phase's exit check. "Twice in a row" only to chase a flaky test.
    - **Lint cache:** `phpcs.xml.dist` caches results in `.local/phpcs-cache.json`; a repeat `composer lint` 34 s → 1.9 s
      (PHPCS's `parallel` needs pcntl, which Windows PHP lacks, so it is not set).
    - **WP-CLI ~45% faster:** `tools/local-wp.sh` gives PHP an OPcache file cache in `.local/opcache` (CLI OPcache was
      thrown away after every call), 2.3 s → 1.3 s a call. Checked: an edited included file is compiled again.
      `OPCACHE_FILE_CACHE=` (empty) turns it off.
    - **One call per setup helper:** `createProduct()` makes one call (`tests/E2E/fixtures/create-product.php`: the
      post, its meta and the `_affibloom_button_url` rows; it was 1 + one per button) and `setSettings()` one
      (`set-settings.php`; it was get + update). Same stored result: the input is `wp_slash()`ed as `wp post create`
      does. Known, unchanged: backslashes in a `wp()` argument are lost between Node, Git Bash and Windows PHP (they
      were before too).
    - **Automatic restore** (`tests/E2E/fixtures/run-state.php`, `run-state.ts`, `run-state-teardown.ts`, first in
      `globalSetup` and as `globalTeardown`): before a run it saves the highest post and term IDs, every `affibloom*`
      option row, every product's status and slug, and the active plugins (`.local/e2e-run-state.json`); after it, it
      deletes every post and term above those IDs, writes the option rows back as they were (and deletes options the
      run added), restores the products' status and slug and the plugins. So a run needs no database backup, no
      credentials copy and no reset, and a failing test leaves nothing. Guards: a state file that is not whole is
      refused (a missing ID must never mean "delete every post"); a leftover from a killed run is restored before the
      next save, but one over 6 hours old is refused for a person to decide. Not covered: edits to the meta or content
      of posts that existed before the run, users, comments, and Affibloom tables (add them when a task makes one).
      Checked: by hand (a product, settings, a post, a trashed product and a group, then restore: same highest ID,
      254 published products, the same hash of every `affibloom*` option) and on three real runs.
    - **Fail fast:** test time 60 s → 45 s and `actionTimeout` 10 s, so a missing element fails in 10 s. A 15 s
      page-load limit was tried and dropped: **Local runs only two PHP workers** (`conf/php/php-fpm.d/www.conf`,
      `pm.max_children = 2`) and a page load sometimes waits ~16 s behind another request (seen in F23's trace: the
      Dashboard's document took 15.9 s; the same page takes ~1.2 s otherwise). More workers is Local's setting, the
      user's call.
    - **`tools/shot.cjs`** replaces the one-off `.local/shot-*.cjs`: `node tools/shot.cjs "wp-admin/admin.php?page=…"
      [--widths=1280,375] [--selector=…] [--out=…]`, signed in with the saved session; says when a page scrolls sideways.
    - **F23 AC5 fixed (the user: "Yes, fix AC5"):** it reloaded the page before the Move down save had finished, so
      the old order showed; it failed the same way with the old helpers (a test race, not this change). It now waits
      for the order save (`POST …/groups/<id>/order`, which wp.apiFetch sends for a PUT) before each reload: AC5 3/3,
      F23 14/14. F23 AC3's toast failed once in three runs. F5 fails as expected (the box is Phase 3).
    - Records: `environment.md` (tooling, Known issues), `batches.md`, `/next-task` (both copies), `CLAUDE.md`'s quality
      bar, and the memory note on running tests.

112. **Task C1B: the catalogue client, behind `wp affibloom amazon get` (2026-11-15).** Batch 2.1, at the user's word
    ("Start Batch 2.1"). Built from the first try's code (`3a2be33`, kept on `backup/before-revert-to-21f7d03`; the
    revert of 2026-11-15 was about process, not this code), read through line by line, the tests brought in first and
    seen red. As planned (R7-R13, D1, D6; AC9), with these departures:
    - **Savings are read, unverified.** Lasso's Creators client reads no savings (its discounts came from its cloud,
      R3-R5, not used), so `CatalogItem` reads them from the same listing price (`savings.money.displayAmount`,
      `savings.percentage`, `savingBasis.money.displayAmount`, Amazon's OffersV2 shape); no extra resource is asked
      for. Check against a live answer when credentials work.
    - **Normalising is in the Domain** (`CatalogItem::from_creators()`, `Availability`), tested without WordPress. The
      client keeps the request, the token cache (R8: transient `affibloom_amazon_token_` + `Credentials::cache_key()`,
      a hash of id, version and secret) and Lasso's errors; a failed token inside an item request is AuthFailed (Lasso).
      The price amount and `parent_asin` (R11) are not read yet: nothing uses them before C1C.
    - **`Asin` refuses more than Lasso:** letters and digits, 1-20 (the cache key's width); Lasso refuses only empty. The
      command prints Lasso's "InvalidAsin: ASIN is required." for both.
    - **"Not set up"** is the command's word for incomplete credentials (Lasso's CredentialsMissing names Lasso's
      settings); an unknown `--country` lists the 18 stores. `CredentialsService::default_country()` is public (was
      `country()`), read at run time.
    - **The stub answers getItems** (`getItems-ok`; `-empty` for an ASIN starting B0NOTFOUND; `-oos` for B0OUTSTOCK) and
      refuses the catalogue in "reject" too, as a token cached in an earlier scenario still reaches it.
    - **Left out of the first try (not C1B's):** its fix for a save from an Amazon tab opened before the card existed
      (it emptied the Partner tag and Version) and its follow-up commit `111d207` (the C1A review's five findings).
      Both stay on the backup branch for the user to place.
    - **Found:** the site's `affibloom_amazon_credentials` are empty. They were set in
      `app/affibloom-backup-2026-11-15-before-revert-cleanup.sql` and already empty in
      `app/affibloom-backup-2026-11-15-before-run-state-check.sql` (before any test run of this session); cause
      unknown. Restoring them is the user's call.
    - Checks: AC9 red first ("'amazon' is not a registered subcommand") and unit tests red (21 errors, classes
      missing); then AC9 passed; unit 610 (Asin, Availability, CatalogItem, CreatorsApiCatalog with the fixtures,
      AmazonCommand); lint and PHPStan clean. By hand: `wp affibloom amazon get B00JMB3FHS` says "Not set up" (exit 1),
      `--country=xx` lists the stores, `wp help affibloom amazon get` shows the usage.

113. **Task C1C: filling a new Amazon product on add (2026-11-15).** Batch 2.1, at the user's "Go". Built from the
    reverted first try (`9974c8a`, on `backup/before-revert-to-21f7d03`), read through, the tests brought in first and
    seen red. As planned (R26-R28, R30, R32, R34, D5, D7; AC1, AC2), with these departures:
    - **`Infrastructure\Schema\Table` is built here** (TL2 left it for its first caller, deviation 100): dbDelta, the
      table takes `wp_posts`' character set and collation (read from `information_schema`), and a table still missing
      after dbDelta fails the step, so the Migrator retries it. Upgrade step 2 creates `{prefix}affibloom_amazon_products`;
      on Local it ran by itself on the first admin request of the test run (backup
      `app/affibloom-backup-2026-11-15-before-c1c-redo.sql`).
    - **Columns:** Lasso's, under Affibloom's names (`asin`, `name`, `price`, `image` for `amazon_id`,
      `default_product_name`, `latest_price`, `default_image`; Task 26 maps them), `currency` kept, `is_manual` as
      `source` (D5); the image and both links are `text` (Lasso: varchar 10000, which a utf8mb4 row cannot hold);
      savings and basis are display strings ("$10.04") as the Creators answer gives them (deviation 112); `base_url` is
      the store's `/dp/<ASIN>` page, `monetized_url` the product's stored link. `CatalogItem` gains the price's currency.
      **No `last_updated` index** (the first try had one): nothing reads by it until C1E's job, which adds it.
    - **NotFound keeps the starting name** (decision 11's name from the link), where Lasso names the product "Amazon":
      Lasso has no starting name, and "Amazon" would replace a better one. Any failure (no credentials, unavailable,
      throttled, refused, not found) leaves the product as before C1C (AC2). The 404 link issue is C1H's (Phase 7).
    - **Stored data is reused without credentials** (R32 reuses the row before any request), so after Task 26 copies
      Lasso's 501 rows, a re-added ASIN gets its name and image without the API. A reused row is stored again with the
      new link (R32).
    - **An item without an image** names the product but is not stored (R27 refuses the row). A row is stored with any
      http(s) or `data:` image (R27); a product takes only an https image. A "0" price is stored as none (R27). The row
      is kept under the ASIN that was asked for, not the one Amazon answers with (a variation's parent would make every
      later add of that ASIN miss). `is_prime` (history only, R14) is carried, and a newer row keeps the older one's.
    - **R34 is in `ProductService::get()`** (the product page): a product named exactly "Amazon" takes the stored name
      and keeps it, as Lasso writes `post_title` while reading. A product with no store reads the `us` row, as `fill()`
      does (one helper; the first try read the empty store there). The box (Phase 3) reads products another way;
      Task 18's reader should call the same rule.
    - **`ProductService` takes `AmazonDataService`** as a sixth constructor argument; the ProductService tests get one
      with no credentials (`InMemoryAmazonProducts::without_amazon()`).
    - **Bulk add** can now spend up to two Creators requests per Amazon link (token, cached; item) inside its time
      budget, unpaced, as Lasso's add. Noted, not changed.
    - **The test run's restore (deviation 111) is extended** (`tests/E2E/fixtures/run-state.php`): it saved and put back
      `affibloom_db_version`, so after the run the version read 1 while step 2's table stayed; and it left the rows a run
      added to the table. Now the schema's options (`affibloom_db_version`, `affibloom_upgrade_lock`) are neither saved
      nor restored, and every `{prefix}affibloom_*` table gets its saved rows back (the run's deleted). Checked by hand
      (a row added after `save` was gone after `restore`, the version stayed 2). The version was set to 2 by hand once,
      to match the table the first run made.
    - **C1 spec:** AC1 was `test.fixme`; it adds through the modal with the stub, checks the name, the image link and the
      stored row, then re-adds the ASIN and sees no request. AC2 covers no credentials, an unavailable Amazon and a
      not-found ASIN. `addAmazon()` reads the ASIN after `/dp/`.
    - Checks: AC1 red first (the name from the slug, "some slug words"), AC2 red (no request reached Amazon); then both
      passed; unit 624 (AmazonDataService, ProductServiceAmazon new); lint and PHPStan clean. By hand: an add through
      `POST /affibloom/v1/products` with the stub took Amazon's name and image link, one request; the product page at
      1280 and 375 px shows them. Seen, not C1C's: the Amazon Image field is narrow beside Apply and Refresh (C1D works
      on that control).

114. **Task C1D: Refresh on the product page (2026-11-15).** Batch 2.1, after C1C. Built from the reverted first try
    (`cfd9d32`, on `backup/before-revert-to-21f7d03`, its review findings already fixed there), read through, the tests
    brought in first and seen red. As planned (R35, R36, D7; AC4, AC7 page), with these departures:
    - **What Refresh writes:** the image link as post meta only (`ProductRepository::set_image()`, new), clearing the
      media library item, and the name through `rename()` only when the stored name is a placeholder (`PlaceholderName`),
      as Lasso writes `lasso_custom_thumbnail` and calls `wp_update_post` only to rename. So a refreshed product keeps
      its place in the Dashboard's "most recently changed". The stored Amazon row (price, stock, savings) is replaced
      from the answer; Refresh always asks Amazon, even with a stored row. The answer carries `renamed`.
    - **The page follows the server's rename** (changed from the first try, which copied Lasso's placeholder list into
      `product.js`): the Name field takes Amazon's title only when the server renamed and the field still holds the name
      the page loaded with (`defaultValue`), so a name typed and not saved is kept, with one copy of the rule. The
      unsaved-changes guard counts the image fields as saved, and the name only when it was renamed
      (`affibloom:fields-saved` in `product-save.js`).
    - **The price Refresh shows lasts until reload:** the product page's price field shows the product's own price;
      showing Amazon's stored price there (and in the box) is C1F's, Phase 3 (AC11).
    - **Messages:** Lasso's "Amazon fetch failed (<code>)." for every Creators error (Amazon's own detail is not shown)
      and "Could not fetch Amazon product image." for an answer without an https image (Lasso then still renames a
      placeholder; here the whole refresh is refused, an edge case). 404 "This product no longer exists.", 422 "Refresh
      is for Amazon products with an ASIN." (Lasso's name Lasso), 409 "Needs the Amazon API".
    - **Help text:** with Refresh on, the Amazon Image tip says "Click Refresh to grab an updated image from Amazon, or
      paste an image link and click Apply, or click the preview image to choose from your Media Library." (Lasso:
      "Click the button below to grab an updated image from Amazon."; decision 11 keeps the field editable, so Apply
      stays). An Amazon product without an ASIN (a search or store page) gets no Refresh and the plain help.
    - **The enabled button needs configured credentials** (D4), not validated ones; without them it stays disabled with
      "Needs the Amazon API" (AC7 page, 14B's button, unchanged).
    - **The image row wraps** (`admin.css`, `.affibloom-image-field__actions`): the link field showed about six
      characters beside Apply and Refresh at 1280 px (14B's layout, seen in C1C's look). The field now asks for 12rem
      and the two buttons move together to their own line when the column is narrower.
    - Not changed: `AmazonController::can_edit_products()` is one line on `Capabilities::PRODUCTS`, as
      `ProductsController::can_manage()`; no REST-level unit test (the route is covered by AC4).
    - Checks: AC4 red first (Refresh disabled), AC7 page already green (14B's button); then both passed; unit 629
      (AmazonDataService refresh tests new); lint and PHPStan clean. By hand: `POST /products/{id}/amazon/refresh` with
      the stub answered the image, "$69.95", `renamed` false; the image row at 1280 and 375 px before and after the
      wrap fix.

115. **Narrower batch-end runs and shorter records (2026-11-15).** C1C and C1D took ~20 min together, but the batch-end
    check then ran all of F1 (rule 5 said "each spec of every feature the batch touched"). The user chose all three
    speed-ups ("All three"). `batches.md` rule 5, `/next-task`, `CLAUDE.md` and the memory note now say: at a batch's
    end, only the tests covering what the batch changed (`-g`), never whole specs; whole specs only at a phase's exit.
    Records: a deviation at most ~10 lines, a session-log entry at most ~4 (this file's header). **Not done: more PHP
    workers.** On Windows Local starts two `php-cgi.exe`, hard-coded in its `PhpService.js` (`requiredPorts`), so
    `www.conf` does not apply; patching Local's files (overwritten by its updates) is the user's call. Docs only.

116. **Task C1I: bulk Amazon sync (2026-11-15).** As R40-R44 and D12, with these differences:
    - **Pending** has no arm for Lasso's `amazon_product_last_updated`: a stored row with a name for the ASIN and the
      product's store is the record of having Amazon's data, so no `_affibloom_amazon_synced` meta is made. On this copy
      229 are pending (Affibloom's table is empty) where Lasso counts 3; Task 26's copy of Lasso's rows brings it to 3.
    - **Name:** Lasso sets the title only when empty; an Affibloom product always has one, so a placeholder counts as
      empty (as Refresh). No forced re-save of every synced product. "Failed to persist" folds into "verify".
    - Words without "Lasso" (D16): "Invalid product ID."; no start lock (the running check, as bulk add); the banner's
      reason line "Needs the Amazon API" is shown, not only described. `can_refresh()` became `can_ask_amazon()`.
    - Checks: AC7 banner and AC14 red first (no pending query), then green; unit 643; lint, PHPStan clean; by hand at
      1280 and 375 px (the chip sat above the words at 375 px; fixed).

117. **Full Amazon product data: Tasks C1Q0-C1Q6, before C1K and C1L (the user, 2026-11-15).** Fetch every relevant
    Creators API field on add and Refresh (no Lasso cloud, no other service); Description and a new **Features**
    field (meta JSON, editable, no front-end display yet) stay separate, nothing copied; prices kept current by a
    scheduled refresh (C1E brought forward into C1Q4, less its Locations arm, which stays in Phase 7) with numeric
    prices, last good data kept, throttling and quota respected; ratings stay the Primary Rating field if Amazon gives
    none; variations excluded. Order Q0 → Q6, the user approving each. Written in the phase file, `batches.md`
    (2.1Q0-2.1Q6) and `docs/features/C1/full-amazon-data-proposal.md`. Found while planning: an uncommitted first try of
    C1K in the working tree (6 files); left untouched, out of every Q commit.

118. **Task C1Q0 findings: why the Local credentials end up empty (2026-11-15).** From MySQL's binary log (row images,
    read masked: credential class and a short hash, never a value). One genuine Credential ID was entered at 04:09:52
    and validated against Amazon at 05:46:13 (stub off). **07:46:58:** one lone write replaced the validated credentials
    with the all-empty shape that only `CredentialsService::check()` writes when Amazon refuses a token (Lasso's R22);
    Amazon's reason and the action (Validate or Save Changes) were not recorded. **16:51:23:** the user's Validate
    landed inside a Playwright run (16:50:29-16:51:42): the stub ("ok") answered it, so "Validated" was false; a test
    deleted the option at 16:51:38 and the run's restore wrote back its start state, empty. Proposed fix (C1Q0, waits for
    the user): record every clear's cause and show it on the card; the stub answers only the suite's test credentials;
    the restore keeps credentials a person changed during a run; keep the credentials on a refusal (a deviation from R22).

119. **Task C1Q0: Amazon credentials kept (2026-11-15).** The user: "Keep them, mark refused". A refused token no longer
    clears the credentials (Lasso's R22): `CredentialsService::check()` stores them with a `Refusal` (Amazon's "HTTP 401,
    invalid_client" from the token answer, and the UTC time) in the option's `refusal` key; the card shows "Refused by
    Amazon" and the reason line; `AmazonDataService` (add, Refresh, bulk sync) and `wp affibloom amazon get` ask Amazon
    nothing while refused; a successful Validate or Save lifts it. Save Changes' toast key is `amazon-unverified-refused`.
    Tests: the stub answers only the suite's test Credential IDs and its own token; the after-run restore keeps a
    person's credentials. AC8's rejection checks moved to a new C1Q0 test (AC8 hit the 45 s limit). Checks: unit 646 red
    then green; C1 AC8 x2 and both C1Q0 tests passed; lint, PHPStan clean; by hand the restore rule and the card at 1280
    and 375 px (the time kept on one line). The C1K first try's hunks stay unstaged.

120. **Task C1Q1: real Creators API answers as fixtures (2026-11-15).** `wp affibloom amazon get <asin> --raw [--all |
    --resources=<paths>]` prints Amazon's body as received (an error body too, then "HTTP n"); `--all` asks the 30 paths of
    Amazon's getItems table; `--resources` (not in the plan) was needed to try `customerReviews` on its own.
    `CreatorsApiCatalog::get_items()` is shared by `item()` and `raw()`. Recorded live, partner tag replaced by
    `yourtag-20`: `live-B000IXRCTQ-all` (in stock, LIST_PRICE saving), `live-B0DHLWFD84-all` (WAS_PRICE), `live-B0F7RNL68R-all`
    (OUT_OF_STOCK, no price), `live-B000IXRCTQ-customerReviews`. Findings: **no ratings or review counts** (the request is
    accepted, nothing comes back); no deal at the time; dimensions are numbers; 5-7 variant images; seller, condition,
    buy box, parent ASIN, brand, model, EAN/UPC, category and rank present. Checks: unit 649 (raw x2, real answers
    read by today's `CatalogItem`); C1 AC9 red then green; lint, PHPStan clean.

121. **Task C1Q2: read every relevant resource (2026-11-15).** `item()` asks 25 paths (Lasso's 7 and the rest Affibloom
    reads; not contentInfo, contentRating, technicalInfo, tradeInInfo, loyaltyPoints). `CatalogItem` gains `ItemDetails`
    (brand, manufacturer, model, part number, warranty, colour, size, unit count, dimensions "3 inches" in Amazon's order,
    product group, EAN/UPC/ISBN, category = first ranked browse node with its rank, store-wide rank, parent ASIN, every
    large image) and `OfferDetails` (price, list/was price and saving as numbers, basis type, seller, condition, buy box,
    deal badge, end, access). The listing read is the buy-box winner, else the first (Lasso: the first). The command
    prints them; the stub answers the three recorded ASINs with Amazon's real answers. Checks: unit 654 red then green
    (two of my expectations corrected to the recorded values: size "Official", Amazon's dimension order); AC9 red then
    green; live `amazon get B0DHLWFD84` printed every line; lint, PHPStan clean. Nothing stored yet (C1Q3).

122. **C1Q3 narrowed: store only the Amazon data that is needed (the user, 2026-11-15).** "Store only necessary data.
    not full amazon data." The row already holds what its readers use (C1C-C1D name and image, C1F price block, C1H
    stock, C1Q4 features and the job's last updated, Task 26 Lasso's columns); C1Q3 adds only `price_amount` and
    `basis_amount`, the numbers C1F's discount rule compares. Brand, model, seller, condition, deal, other images,
    parent ASIN and category are read (C1Q2) and printed by the command, not stored; C1Q5's card shows the stored fields.
    Also: C1Q2 landed in two commits, `5fea6c6` and `829d8b5` (its AC9 check and the stub's recorded answers, after
    the partial staging of the two files shared with the uncommitted C1K first try failed).

123. **Task C1Q3: Amazon prices stored as numbers (2026-11-15).** Upgrade step 3 runs `Table::create()` again; dbDelta
    added `price_amount` and `basis_amount` (decimal(12,2), NULL) on Local (`affibloom_db_version` 3; backup
    `app/affibloom-backup-2026-11-15-before-c1q3.sql`); a new install gets them from step 2. `AmazonProduct` maps them
    from `CatalogItem::$offer` (a 0 price is none, as R27); a newer row without a price keeps the older price and its
    number. Checks: unit 655, the two new unit checks red first; C1 AC1 (its stored row now checks both numbers) passed,
    but was **not run before the code**, so not seen red; lint, PHPStan clean; the columns checked on Local.

124. **Task C1Q4 split; C1Q4a: the Features field (2026-11-15).** Split for size: C1Q4a here, C1Q4b (the scheduled
    price refresh) next. Before it, the uncommitted C1K first try was stashed at the user's word (`stash@{0}`, "C1K first
    try …"), as it changes the same four files. Product `features` (list), post meta `_affibloom_features` (JSON);
    `ProductRepository::set_features()` writes meta only; `ProductInput` takes plain-text lines, trimmed, empties dropped;
    the save route accepts `features`. Add takes Amazon's bullets; bulk sync fills an empty list only (as the image);
    Refresh replaces them when Amazon gives some and the page's list follows. The description is never touched. The
    page: Features after Description, rows with Move up, Move down, Remove, Add Feature, labels renumbered. Checks: E2E
    AC1, AC4 (extended) and a new C1Q4 test red first, then green (two runs stalled on Local's workers and were rerun);
    unit 657, the unit checks written after the code; lint, PHPStan clean; 1280 and 375 px on the user's Lenovo product.

125. **Task C1Q4b: the scheduled Amazon refresh (2026-11-15; C1E brought forward).** Hourly `affibloom_amazon_refresh`,
    scheduled on `init` while "Update Amazon pricing daily" is on, cleared when off; passes on `affibloom_amazon_refresh_pass`
    (both in the deactivation list). A run takes `RefreshPolicy::batch()` (R37) of the due pairs (`PostMetaDueAmazonData`:
    published Amazon products with an ASIN, row missing or older than 24 h, oldest first, missing first) as a fixed queue
    (option `affibloom_amazon_refresh`), so a row that cannot be refreshed is not picked again in the same run; 20 s passes,
    one item a second. `AmazonDataService::refresh_row()` writes the stored row only (D7): not found or no image moves
    `last_updated` on (`touch()`, R39); any other Amazon failure ends the run, rows untouched. Not yet: the Locations arm
    (F21, stays in C1E), the 404/000 issues (C1H), back-off (C1Q6). Checks: unit 664 (policy, service) and C1 AC6/AC10 red
    then green (its request count corrected: a cached token); lint, PHPStan clean; the event seen scheduled on Local. No
    live run (WP-Cron is off on Local; it would spend ~20 API calls).

126. **Task C1Q5: the Amazon Data card (2026-11-15).** Titled "Amazon Data" (the plan said "Amazon Details"; it shows the
    stored data only, deviation 122). An Amazon product with an ASIN gets a full-width, read-only card under the form
    (never sent by Save Changes): Amazon title, price, list price, saving and per cent, currency, availability, image,
    number of features, source ("Creators API" or "Imported", no Lasso name), last updated in UTC; without a row "No
    Amazon data yet". `AmazonDataService::stored()` reads the row (no request); `stored_name()` now uses it. Checks: the
    C1Q5 E2E red then green (its value locator fixed: dt to dd by XPath); unit 665; lint, PHPStan clean; 1280 and 375 px
    on the user's Lenovo product (bottom padding fixed with the cards' `--fields` body).

127. **Task C1Q6 split; C1Q6a: Amazon's limits (2026-11-15).** Split for size; C1Q6b (ten ASINs per request, per-item
    errors) is next. `GuardedCatalog` wraps the Creators API for every caller (one instance, `Plugin::catalog()`) with a
    `RequestLog` in the option `affibloom_amazon_rate`: after a 429 nothing goes out until Amazon's wait ends; calls are
    at least a second apart; item calls are counted per UTC day and stop at 8,640. A new `CatalogError` kind `Throttled`
    with `retry_after` (getItems: `retryAfterSeconds`, else Retry-After, else 60 s; token: Retry-After, else 300 s);
    a throttled token inside an item call stays Throttled (Lasso: AuthFailed); Throttled counts as unavailable (Validate
    keeps the credentials). The hourly refresh caps its batch at the day's calls left less a reserve of 864. Not yet:
    bulk sync still marks each item failed while throttled (C1Q6b). Checks: unit 671 red first (one older test moved
    from a 429 to a 400 example); C1Q6a E2E red then green, C1 AC1, AC2, AC8 x2 green; lint, PHPStan clean (a dead 429
    branch removed); a live call counted on Local. The tests' `stub()` clears the ledger.

128. **Task C1Q6b: ten ASINs per request (2026-11-15).** `AmazonCatalog::items()` (one getItems for up to ten ASINs of a
    store; per-item errors such as ItemNotAccessible and left-out ASINs are missing, read as not found), guarded and
    counted once. `AmazonDataService::prefetch()` keeps the answers for `sync()` and `refresh_row()`; the job and bulk sync
    fetch the next ten (same store; bulk sync only products without stored data) when the next one was not fetched yet,
    so they spend a tenth of the requests; their one-a-second pacing is kept. A throttled bulk sync no longer fails
    products: `SyncOutcome::throttled()` and `BackgroundJob::dispatch( $delay )` resume the run after Amazon's wait. The
    hourly event's first run is now an hour after it is scheduled. Found and fixed: the stub's C1Q0 isolation check sat
    after its counter and its "unavailable"/"throttle" answers (it counted passed requests and answered anyone in those
    two modes); the stub answers every ASIN of a request; a test run's save clears Affibloom's background events (a
    refresh started by WP-Cron during a run held one of Local's two workers). Checks: unit 675 red first; C1 spec 16 of 18
    passed, then AC8 passed alone; **AC2 times out on Local and fails the same on the C1Q6a commit** (run with this work
    stashed), so it is the environment (WP-Cron work of other plugins due on page loads), not this task; lint, PHPStan clean.

129. **Lean working method (the user, 2026-11-15).** Tasks took too long in records, test runs and database restores;
    the Local database is dummy data. Records: per task only the phase file's ticks and an optional 1–3 line *Note*;
    a numbered deviation only when the plan's design or a later task changes; `progress.md` once per batch;
    `progress-log.md` closed. Checks: unit tests and `phpcs`/`phpstan` on changed files while coding; the full
    lint, analyse and unit suite once per task; Playwright only for a screen or flow change, the task's AC only, green
    once; review against the PR template once per batch. Playwright no longer saves and restores the site:
    `run-state.ts` and its teardown are gone, `quiet-jobs.ts` only clears Affibloom's background events (C1Q6b's
    reason); `run-state.php` stays as an opt-in tool. Runs now leave their products, options (test Amazon
    credentials too) and drafts made by `isolate-amazon-pending.php`. Rules in `CLAUDE.md` and `batches.md`.

130. **Tests per task cut to about 20 seconds (the user, 2026-11-15).** Waiting on test commands was the main delay.
    No separate red run: the outermost test is still written first, and runs after the code. Per task only the unit
    tests, `phpcs` on the changed files and one look at the changed screen. `composer lint`, `composer analyse` and the
    batch's Playwright ACs run once, at the batch's end; a failure there is fixed and committed as "Fix <what>
    (<task ID>)". Full suite at a phase's exit, as before. Rules in `CLAUDE.md`, `batches.md` rule 5, `next-task`.

131. **The user tests each batch by hand (the user, 2026-11-15).** A batch's end still took long, almost all of it in
    Playwright (each `wp()` call loads WordPress through Git Bash, 1.3–2.3 s) and the 375/1280 px screenshots; lint,
    PHPStan and the unit tests take about 10 s together with warm caches. At a batch's end only those three run; then
    Claude gives a numbered manual test guide (screen, action, expected result, a 375 px check) and the user tests by
    hand; reported problems are fixed as "Fix <what> (<task ID>)". Playwright ACs are still written per task and run at
    the phase exit check or on request. PHPStan's `tmpDir` moved to `.local/phpstan` (cold 31 s, warm 4 s), so a
    cleared system temp folder no longer costs a full analysis. Claude talks in English from now on.

132. **Task 18A (2026-11-16): the box's Affibloom names, beyond the class names.** ADR-6 also renames Lasso's data
    attributes and properties, so later tasks use these: `data-lasso-lid` → `data-affibloom-id` (the product, F12's
    click key), `data-lasso-name` → `data-affibloom-name`, `data-lasso-box-trackable` → `data-affibloom-box-trackable`,
    `data-v2-hide-<el>` → `data-hide-<el>`, `--lasso-*` → `--affibloom-*`, `lasso-ds-v2-frontend` →
    `affibloom-advanced`, `lasso-container` → `affibloom-box`, `clear` → `affibloom-clear`; `data-layout-*` and
    `data-density-*` keep Lasso's names. Lasso's `data-lasso-id` (link location) is not printed: F21's
    `data-affibloom-location` takes its place where a location is known; 19A's `data-lasso-button` is F12's
    `data-affibloom-btn`. Full table: `docs/ui/tokens.md`, "Product box". The F5 spec asserts these names (the plan's
    "Lasso's classes plus `.affibloom-box`" predates decision C1). Also: `RepointResolver` (Application\Box) and
    `ProductRepository::repointed_to()` hold the repoint walk 18S1 reuses; `Product` gained `show_description` and
    `slug`; until 18E/18F every theme prints the 3-box template.

133. **Add Link opens the product's page, Amazon links too (the user, 2026-11-16: "when adding single products in
    lasso, after adding its opening that product page").** Deviation 56 read `url-add.php` 1624's `res.data['is_amazon']`
    branch as Lasso's ending for an Amazon add, but `check_error_and_response_ajax()` (class-lasso-affiliate-link.php
    2004-2015) never sends a top-level `is_amazon`, so the branch is dead: Lasso opens the product's page for every
    single add, with `is_duplicate` for a found one. `POST /products { url }` now answers that for every link; the
    Amazon tab's selections and Bulk add still end on the Dashboard (url-add.php 1600). Integration test and F2/F18 specs
    updated (not run).

134. **Buttons, custom fields and features reorder by dragging, as Lasso's rows (the user, 2026-11-16).** The product
    page's Move up / Move down buttons (14H's buttons, FL5's fields, C1Q4's features) are gone; each row has a grip
    (`.affibloom-grip`, a button) dragged by pointer through `drag-rows.js`, which now also moves a row with Arrow Up /
    Arrow Down on its focused grip. The user chose "drag only, like Lasso" over keeping small arrow buttons, knowing it
    leaves no one-click alternative to dragging (WCAG 2.2 SC 2.5.7, design-system rule 3). The group Links tab (G4)
    keeps its Move buttons. Checked by a signed-in run at 1280 px (pointer and keyboard, fields saved 200); F1, F26,
    F29 and C1 specs updated (not run). **The drag looks like Trello's (the user, same day):** the held row floats
    under the pointer, tilted 2deg with a deep shadow, a grey slot its size marks where it lands, the other rows slide
    aside (150 ms) and the row glides into the slot on release; the page scrolls near the window's edges; no motion
    with "reduce motion". The group Links rows share it (same `drag-rows.js`).

135. **Task 18B (2026-11-16): the box's baselines are out of date, and the site's own CSS is part of them.** Measured
    element by element, Affibloom's Lab box is within 1-3 px of today's live box (517 px tall against 515), but AC1 is
    off by 7 % (16 px shorter) against `variants/*.png` of 2026-11-12: production has since run Lasso 370, whose
    advanced badge rule is `top: 0` (369: `-18px`); `box-layout.css` follows 370, as readers see it. **Task 18D also
    recaptures the seven `variants/` (staging, the approved F1 session), and AC1 passes against those.** Readers' boxes
    also carry the site's own CSS (description padding 5/10 px and its font size, badge size and its corner, the
    display's shadow), which is the site's, not Lasso's: Affibloom's stylesheet leaves it out (ADR-6) and AC1 adds it
    to the page, as Task 41 will translate it (`SITE_CSS` in `F5.spec.ts`; the description rule is not in the site's
    Additional CSS, so Task 40's list must look for it in the theme settings). `Infrastructure\WordPress\CustomCss` is
    `Domain\Settings\CustomCss` (pure allowlist). Left: the button with the site's icon is 3 px taller than live.

136. **Task 18C (2026-11-16): two of Lasso's loading rules move to the tasks that need them.** (1) **R52, the box CSS
    on every admin page:** the admin already uses `.affibloom-image` and `.affibloom-price` for its own fields, and
    the box's unscoped rules would restyle them; the previews (Tasks 10D, 14E) load the box CSS on their screens,
    scoped to the preview. (2) **Lasso's copy-to-clipboard script on every page (R45):** with no behaviour until the
    Copy to Clipboard field exists it would be an empty file, so Task 21C adds `copy-clipboard.js` and its loading;
    until then F5 AC2 holds with no Affibloom script at all on a page without a box. Also: Jetpack's
    `jetpack_photon_skip_for_url` is set once a box is drawn, as Lasso's shortcode does; the AMP CSS is
    `assets/front/box-amp.css` (Lasso's `lasso-amp.css`, Single Lab rules), printed on `amp_post_template_css`.

137. **Task 18D (2026-11-16): the baselines are drawn on Local with Lasso 370, by box attributes.** Lasso is not
    activated on staging (every Lasso screen shows "Let's Get Started", so Display settings cannot be read or saved
    there) and production is read only, so the user copied Lasso 370 from staging into Local's `plugins/lasso`
    (active). Each matrix cell is `[lasso id=… theme=… <attributes>]` in a post (no product's Display Theme is set);
    `tools/lasso-capture.php` saves each group's settings through Lasso's own code and undoes everything after
    (`lasso_settings` compared, `display_errors` off for the run: Local shows PHP warnings, Lasso 370 raises one for an
    attribute rating). 247 boxes in `themes/<group>/` (PNGs 22.1 MB). **Deviation 135 corrected:** the seven variants
    recaptured with Lasso 370 are byte-identical to Task 5's (staging's differ only by its 1 px wider column), so the
    old baselines were right and F5 AC1's 7 % (Affibloom 518 px against Lasso's 534 px) is in Affibloom's 18B CSS.

138. **F5 AC8-AC10 check the `plain` and `badge` theme baselines only (the user, 2026-11-16: "Yes, split them").** The
    `themes/site/` states other than those two need features later tasks build, so each of those tasks checks its
    state for all 8 themes, as F5 AC1's variants are already split: **19A** `two-buttons`, **20A** `no-image` (the
    box's `image_url="hide"`), **21A** `rating`, **21B** `pros-cons` and `all-fields`. The theme tasks (18E-18G) check
    `plain` and `badge` at 1280 and 390 px. The capture's `site` page was rebuilt once on Local with Lasso 370
    (`tools/lasso-capture.php` setup and undo, `lasso_settings` restored) to measure where each box started; F5's
    `THEME_TOP` holds those offsets, so the boxes rasterise at Lasso's sub-pixel positions (as AC1's `BASELINE_TOP`).

139. **Task 18S14 (2026-11-16): the schema reads `[affibloom]` now; `[lasso]` joins in 27A.** F31 D12 has the schema
    read both shortcodes. While Lasso is active it prints its own schema for `[lasso]`, and Affibloom draws nothing
    for it (AC15), so reading `[lasso]` now would print a second JSON-LD. `SchemaMarkup::SHORTCODES` holds
    `affibloom`; **Task 27A** adds `lasso` when Affibloom registers that shortcode (Lasso inactive).

140. **Task DT1 (2026-11-16): the display baselines are drawn on Local with Lasso 370, not staging.** F30 AC1 and DT1
    named staging and a group and draft page made there; Lasso is locked on staging (deviation 137), so the capture
    runs on Local as 18D's does: a throwaway Lasso group of products 14722-14725 and one post, under the site's
    settings, the site's with Keep Site Stripe UI on, and Lasso's defaults (for DT3), all undone after
    (`tools/README.md`, "Display baselines"). DT2-DT8 compare against `tests/fixtures/baselines/displays/`; the
    content column there is 768 px wide at 1280 px, as on every Local post.

141. **Settings → Display: a full save stays on the page (the user, 2026-11-16: "Stay on the page would be good").**
    Lasso saves the Display tab by AJAX and then reloads the page (`settings.js` 2444-2445); Affibloom did the same
    (F9 R41), so the box preview went blank until it was drawn again. Now the Display form carries
    `data-save-in-place`: when everything was saved the dialog closes, "Settings saved." shows as the toast and the
    unsaved-changes guard takes the form as saved (`affibloom:saved`). A partial save (a value refused), Reset Settings
    and the guard's save-then-leave still load the server's address, which shows what was stored. The other Settings
    tabs keep Lasso's reload. Both previews also open in their loading state, so "The box preview appears here." shows
    only when no box could be drawn. `tests/E2E/settings.ts` waits for the save's answer, then the toast or the reload.

142. **Admin design: Meadow replaces Aurora (the user, 2026-11-16).** Aurora's navy band felt heavy (four-line titles,
    wrapping tiles, Save at the foot of a 2,800px page); of five clickable prototypes (Meadow, Console, Split View,
    Canvas, Nightshift) the user chose **Meadow** and approved its answers: 1320px column, bundled Inter and Plus Jakarta
    Sans, no Discard button, the restyle before Phase 4's F4 tasks. Spec: `docs/ui/design-system.md` section 0b
    (sections 1-8 are Aurora's record until MD7); ADR-008 Amendments. Unchanged: ADR-008's rule, section 0 (Display),
    the box, U9/U10, the tested class names. Tasks MD1-MD7, Batches 4.M1-4.M5; each rewrites its part of
    `admin-design.spec.ts`. Planning settled: one Save Changes (the footer becomes the sticky save bar), no new preview
    toggle, the summary card and the stat cards' filters come with their data's tasks (31B, 34B, 36B, O8),
    `.affibloom-tile*` stays. Meadow is a look: it holds whether the admin stays PHP (ADR-7) or a React admin is approved.

143. **One save behaviour: every settings screen saves over REST and stays on the page (the user, 2026-11-16: "We
    just need to standardize the saving process across all settings"; React dropped, the admin stays PHP, ADR-7).**
    Settings → General and Amazon posted to `admin-ajax.php` and reloaded (Lasso's way, F9 R41); Display stayed
    (deviation 141) but reloaded after a partial save; Button icons (add, remove) and the product page's tracking-ID
    "Yes" were plain `admin-post.php` forms. Now all go to `affibloom/v1`, as the product, group and field screens
    do: `POST settings/tabs/{tab}`, `settings/display`, `settings/display/reset`, `settings/tracking-id`
    (SettingsRestController) and `POST icons`, `DELETE icons/{class}` (IconsController). Each answer carries the
    toast's words (`Notice::text`) and the refused keys; settings-save.js marks a refused field (`#affibloom-setting-
    <key>`, its `-error` text from `data-refused`), keeps what was typed so it can be corrected, and on General and
    Amazon (`data-refresh-values`) fetches the page in the background so the other text fields show the stored, cleaned
    values; the Amazon answer redraws the Product Data API card; Disable Help Tooltips sets its body class at once; the
    icon list and every picker gain or lose the icon; the tracking-ID offer goes. Only Reset Settings still loads the
    page afterwards (every Display field changes). Removed: SettingsController, DisplayController, IconController,
    TrackingIdController, SaveAnswer and the `?refused=` address. **No save without JavaScript** (the user, 2026-11-17,
    "Drop it"), as on the product page: F28 AC6 now checks only the plain field. Tests changed: F9 AC17 (no reload on
    any tab), F17 AC2 (the typed ID stays, marked, with "the previous one was kept"), F28 AC2/AC3/AC6, C1 AC8 (the last
    toast), `tests/E2E/settings.ts` (waits for the save's toast). Found on the way: an emptied whitelist box kept its
    error until it lost focus, so Save Changes moved under the pointer; the error now goes as the box empties.

144. **Settings → Display: Single, Grid and List switch without a reload, and one save stores every type (the user,
    2026-11-17: "when changing single, grid, list view … its still reloading the page … not working exactly like
    lasso").** Lasso keeps one form for the three types and swaps them in the page (`settings.js` 2073-2087); Affibloom's
    type links loaded `&type=…`. Now each type's part of the rail (theme row, Layout & spacing, Colors & labels;
    `display-type-region.php`) is printed for all three, the shown type live and the others in a `<template>`, and
    their fields are named `display[<type>][…]`. Choosing a type sets the shown part aside with its edits and scripts,
    puts the chosen one in (set up on first show), updates `type` in the address (a reload opens it again, where Lasso
    uses sessionStorage) and redraws the preview; Save Changes sends every type that was shown (`form.affibloomFields`),
    and `settings/display` saves each. The leave guard compares the same fields, a type shown for the first time counts
    as stored (`affibloom:fields-added`), and each layout is compared and sent as its desktop rows, so the Mobile view
    is never taken for a change. Below 992px the preview stays on Mobile with Desktop disabled, as in Lasso
    (`settings.js` 1276-1300). Without JavaScript the type links still open each type's page. Found on the way: an open
    icon menu closed on any scroll, including the page's own when a preview finished its height (F28 AC4 failed in
    a full run); it now follows its button and closes only when the button leaves the screen. Tests: F16 adds this
    deviation's test; F28 AC6 reads the new field name.

145. **Loading states: skeletons for content, busy buttons for actions (the user, 2026-11-17).** The field library
    ("Add from library") showed nothing while it loaded and nothing when it failed; the product page's and Settings →
    Display's previews showed the picture faded under a small spinner. A sweep of every admin request (43) found the
    group page's and Add Link's Amazon searches empty while waiting too, and Create Field and a field's Remove sendable
    twice. Now: `affibloomSkeleton( list, n, picture )` (admin.js) draws row shapes in a waiting list; both previews show
    `partials/box-skeleton.php` until the first box, then the list's moving bar on a redraw; the Dashboard, Groups and
    Fields lists turn their rows to skeleton bars while they reload (CSS on `.is-loading`, scripts unchanged); actions
    keep or gain `.is-busy`. Rule for later tasks: content that waits shows a skeleton of itself, an action its busy
    button; no bare spinners. Checked by `tests/E2E/loading-states.spec.ts` (3 tests, answers held back).

146. **Affibloom is standalone; the Lasso migration is a separate plugin (the user, 2026-11-17).** The user: "Since the
    plugin will replace lasso on only one website and will install newly on a lot websites, so we shouldn't include
    lasso to affibloom migration as a core feature. maybe we can develop the migration as a separate helper plugin";
    "I want every existing [lasso] shortcode across all posts and pages to be replaced with the equivalent AffiBloom
    shortcode. All existing content must be migrated to AffiBloom's native format, so the Lasso helper plugin is no
    longer needed and can be safely removed. … Do not leave any Lasso shortcodes or legacy Lasso blocks behind. so we
    don't need any other affiliiate plugins compatibilities"; "All of the migration functiionalties should be developed
    as a migration plugin. not our core feature. Affibloom is a standalone affiliate plugin. no need other plugin's
    compatibilities." What changed in the plan:
    - **Phase M, new** (`plans/phase-m-lasso-migrator.md`, after Phase 8, before Phase 9): the plugin
      `affibloom-lasso-migrator` in its own repository (LM1, with Affibloom's extension API `affibloom_ready`); F10's
      data tasks 25A-26P, 26G, 26H moved from Phase 5, 26K from Phase 6, 26I, 26N and 29C from Phase 7; the content
      conversion LM2-LM9 (blocks to `affibloom/product-box`, bare `[lasso]` to `[affibloom]`, widgets and Elementor
      data, the other display types and tables, the `data-lasso-*` stamps removed with their ties kept as
      `assigned_product_id`, the `.lasso-*` Additional CSS translated, the admin run, the leftover check); parity 29A-29E
      with 29D restored; LM10 Finish and removal. **Decisions A1 and A2 are reversed** (conversion and stamp removal come
      back as LM2-LM8; ED6 and 27A-27I are not built). ADR-2, ADR-3 and ADR-6 updated in the overview.
    - **Phase 5 rewritten** (`plans/phase-5-links-cloaking-and-variants.md`, the old file removed): LC1 and LC2 take the
      Lasso code already built out of core (the one-time import of deviation 87, the Lasso box markers, ED5's editor
      script, Lasso's and other affiliate plugins' post types, the "Lasso" placeholder name, `lasso-br-code`, the
      `lasso-*` admin classes; `NoLassoInCoreTest` keeps it out), then F21, F9 9J, F38 ON3-ON4, F24 and F27 with
      *Changed 2026-11-17* notes where a task read Lasso data or markup (35A, 35B, 35D, 35E, 35G, ON4, CL1, CL10, V2,
      35H, 35J).
    - **Dropped** (archived in `plans/archive/phase-5-superseded.md`): 27A-27I and ED6 (Affibloom renders no Lasso
      shortcode or block), 27X1-27X8 and IM1-IM20 (other affiliate plugins), 27E (the migrator converts
      `[lasso type="table"]`). F4 ED5 (built) is withdrawn by LC2. Integrations that are not affiliate plugins stay
      (Elementor, Classic Editor, AMP, Rank Math, ACF, Kadence, 35E's builders and recipe plugins).
    - Phases 6-9 adjusted: Phase 7's core tasks read Affibloom's own render-time attributes instead of `data-lasso-*`;
      Phase 8's wizard loses its Imports step and its reviews cover Affibloom only; Phase 9 installs both plugins,
      converts the content before Lasso is switched off, rolls back by reverting the content first, and Task 42 runs
      Finish and deletes the migrator. ThirstyAffiliates' one `/recommends/` link (guide 3297) gets a Rank Math redirect
      at cutover instead of IM2.
    - Batches re-cut in `plans/batches.md` (Phase 5: 17 batches; Phase M: 20 batches).

147. **Link check: locations only, and the unconfirmed row (Task 30A, 2026-11-18).** D5 applies R5's "locations only"
    rule "once F21's index exists"; the index was built in Phase 5, so with `pricing_locations_only` on (the default)
    the pass checks only products with a row in `affibloom_link_locations`. Products never requested: type `amazon` or a
    destination on an Amazon host (AC2). A product whose first-ever check fails gets a row with `issue_type` `''`,
    `failures` 1 and `pending_type` set (D4: not shown); it is not an issue. **Later tasks:** 31A-31D, 32A and 32B count
    an issue as `resolved = 0 AND issue_type <> ''` (broken: also `<> '000'`) and "checked" as `issue_type <> ''`
    (`LinkIssue::is_open()`). The pass keeps its queue of product IDs in `affibloom_link_check` (D11's option).

148. **Amazon products in the link check (Task 30C, 2026-11-18).** C1's product client exists, so 30C's adapter
    (`CatalogProductCheck`) is live: while the Creators API credentials are configured and not refused, the daily pass
    and the save check ask the API about every covered Amazon product (type `amazon` with an ASIN), one request each,
    against C1's daily quota. R16 kept, except that HttpError, Throttled and AuthFailed check nothing (Lasso: 200, which
    would clear a real issue); ApiError stays 200 (Lasso's ignore list). **Later task, C1E (Phase 7):** when "Update
    Amazon pricing daily" is on, the refresh files Amazon results through `LinkCheckService::record()` and
    `resolve_out_of_stock()`, and the pass must then leave Amazon products to it (R5: `due( …, false )`), so no item is
    asked twice a day. Local holds the site's real credentials: a pass there asks Amazon for real.

149. **Export CSV column names (Task 31D, 2026-11-19).** D18 keeps Lasso's 24 headers; three of them name Lasso
    (`lasso_fields`, `lasso_custom_redirect`, `lasso_custom_thumbnail`), which the standing rule (no Lasso dependency
    or branding, 2026-11-14; deviation 146, guarded by `NoLassoInCoreTest`) forbids in Affibloom. They are `fields`,
    `custom_redirect` and `custom_thumbnail`; the other 21 names, the order and the values are Lasso's. A WP All Import
    template made for Lasso's file maps those three columns again. Task 38 reuses the same writer.

150. **Show Features, and icons in the preview frames (the user, 2026-11-20).** The user asked for the features in the
    product page's preview and a Show switch like the other fields'. The product has `show_features` (meta
    `_affibloom_show_features`, "1"/"0", off when missing, so boxes drawn before keep their look); on, `BoxBuilder`
    puts its bullets in `BoxView::$features` while the Description element shows, and `partials/features.php` prints
    them as a `ul` inside the description block (Single boxes and list cards; a compact grid card drops them), where
    box.css's description list rules draw them. Lasso has no such element, so no Lasso baseline covers it. The two
    preview routes add `IconStyles::preview_css()` to their CSS: the site's Additional CSS (the imported icons' rules)
    and the added icons', since an admin page has neither and the button and badge icons did not show. Checked by unit
    tests (BoxBuilder, ProductInput, IconStyles) and on product 20767 in the browser.

151. **Table builder rework (the user, 2026-11-21).** The user: the builder "feels less polished and is not as
    user-friendly" than Lasso's; improve drag-and-drop, selection, customization and the editing workflow, and give
    badges and buttons a proper icon picker. An audit of T3-T15 found, besides the look: a value typed just before a
    move, add or remove showed its old text after the redraw (the save landed after the server drew the builder); the
    price and description editors wrote into elements a redraw had replaced; one failed write dropped every queued
    write; a stale revision left the page failing with no way out; the redraw reset the grid's scroll and focus; a new
    table ignored Theme and the switches; badges showed their `<span>` markup. The changes:
    - **Writes** (D4 kept): a structural write first sends every value still waiting to be saved, so the redraw has
      them; a failed write fails alone; a stale revision shows "Reload" and stops; a redraw keeps the grid's scroll,
      the focus and the selection. On a new table, Theme and the switches wait (disabled) until it is named.
    - **Drag-and-drop** (D9 changed, as deviation 134 did for the product page): Lasso's live sortable without jQuery
      UI. A product, a row (Vertical) or column (Horizontal) and a field move under the pointer while the others make
      room (each grid cell is re-placed live by `grid-row`/`grid-column`, animated), and the drop saves the order.
      It looks as every drag in the plugin (drag-rows.js; the user, 2026-11-21): the held card, column or row floats
      under the pointer tilted with a deep shadow while grey slots its size mark where it will land. Rows and
      columns get a gutter with their own grip, instead of the first product's cell. A field card drags by its grip
      or its body (not from its inputs, editors, image or buttons, Lasso's cancel list) to another place in its cell
      or to another row/column, for every product at once. The grid scrolls while dragging near its edge; Escape
      cancels. Keyboard: a grip is a button; Space or Enter lifts, the arrows move, Space or Enter drops, Escape puts
      back, each step announced. The Move up/down and Move to previous/next buttons go.
    - **Selection and the inspector**: clicking a product, a field or a row selects it (outlined; the same field is
      marked in every product) and a panel beside the grid (under it on narrow screens) shows what can be changed:
      a product's badge (icon and words) and removal; a field's name, its moves and removal from every product; a
      button's text with its icon and, for the Secondary, its address (R35's dialog goes; the rule and its sentences
      stay); a row's removal. Escape or clicking empty space clears it.
    - **Icon picker** (badges and button texts; F28's library): a searchable grid of the library's icons (their
      pictures and names), "No icon", the current one marked, the keyboard (arrows, Enter, Escape) and, for those who
      may change settings, "Add icon" from the media library (F28's POST /icons). The stored form stays F28's
      `<span class="<class>-icon"></span> <words>` (D3): the reader's box, the site's CSS and migrated content read it,
      and nobody types it any more. The canvas draws a badge or button with its icon's picture.
    - **Add Product** and **Add Group** keep their dialog open and mark what was added "Added" (R17 closed it after
      one); a product already in the table reads "Added" (Lasso's "already exists" sentence stays for a direct request).
    - Checked: F25 AC3-AC12, AC15 and AC19 in Playwright (AC5, AC9, AC11, AC12 rewritten for the inspector and the
      drag; AC11b new for the icon picker).
    - **Structure**: table-builder.js becomes a small core (the write queue, the redraw, the selection) and modules
      for the settings, the cells, the drag, the inspector and the icon picker, sharing one `affibloomTableBuilder`.

152. **A Fields tab in the header (the user, 2026-11-22).** A visual review found that the Fields list and a field's
    page marked no header tab, so nothing showed where you were (F38 AC1 had kept Lasso's header, which has no Fields
    tab). The user chose a Fields tab between Groups and Settings, current on the list and on a field
    (`AdminNavigation::TABS`). F38 AC1 and its acceptance test say so; the WordPress menu is unchanged. The reader's
    comparison table was reviewed at the same time and stays as Lasso's (25px names, labels on empty values).

153. **The setup wizard's "no Lasso data" test is the migrator's (Task ON6, 2026-11-22).** D13 opens the wizard once
    after the first activation on a site with no Affibloom settings and no Lasso data. Core may not read Lasso's data
    (deviation 146, `NoLassoInCoreTest`), so activation marks the wizard due (`affibloom_setup` = pending) only when
    `affibloom_settings` is missing, and the first-visit redirect asks `apply_filters( 'affibloom_setup_wanted', true )`.
    **Later task, Phase M (LM1 or LM10):** the Affibloom Lasso Migrator answers false on a site with Lasso's data, and
    may set `affibloom_setup` to `done` itself. Each wizard step saves only its own fields through SettingsService's
    hidden tabs (`setup_display`, `setup_amazon`, `setup_analytics`), so no step switches off a checkbox it does not show.

154. **Phase 8 closes as Lasso would (the user, 2026-11-22).** The user: "We do not need a GitHub release or the
    v1.0.0-rc.1 pre-release tag. For all remaining Phase 8 tasks, proceed exactly as Lasso has handled them. Follow the
    same approach, workflow, and standards for the pending decisions, documentation reviews, editor testing, UX
    sign-off, and final phase sign-off." What changed in the plan:
    - **Task 39 step 7 dropped:** no tag, no GitHub release. The release zip is built locally (`npm run plugin-zip`,
      `docs/developer.md` section 10) and installed through wp-admin, as before.
    - **A8 (37C), the box buttons' contrast:** Lasso's colours stay (#22BAA0 under white text, set under Settings →
      Display, where the site can change them); the finding is accepted, not fixed.
    - **Export CSV:** UTF-8 without a byte-order mark, as Lasso's export (F22 R62); Excel users open it through Data →
      From Text/CSV.
    - **Task 39A step 3, the editors' run:** run by Claude in Brave on Local, the five top tasks of `ui/workflows.md`
      counted against Lasso's steps (`reviews/ux.md`); **step 5 and Phase 8's sign-off** at the user's word above.
    - **Task 39 step 2, the guides' review:** checked against the screens during that run; two statements corrected
      (the Dashboard search runs on Enter; Rebuild Database is in the support view).

155. **Task 24F added: the Share kit's Copy block (the user, 2026-11-23).** F1 D4 and Task 14D (deviation 69) left
    Copy block "Coming soon" until the editor block existed, in Phase 4; no Phase 4 task took it, so it stayed disabled
    after Phases 4-8 were signed off. Built as Task 24F (`phase-4-editor-block.md`): it copies the block the editor
    saves for a Single, `<!-- wp:affibloom/product-box {"type":"single","productId":<id>} /-->`.

156. **Gap fixes after Phase 8 (the user, 2026-11-23: "Yes, plan the batch").** A read of Lasso's code against
    Affibloom's found three items the records promised and no task built (E17's original-URL override, Keep Original
    URL's reader, a bare ASIN's store) and four Lasso behaviours with no record (8 revisions, Kadence's FAQ schema
    repairs, four hooks, Restrict Prefix's no-op). They are Tasks GF1-GF7 (`phase-8-hardening-and-documentation.md`,
    Batches 8.G1-8.G4) and Phase M's Task 26Q (Lasso's original links into the index, which GF6 needs; Batch M.10a).
    Phase 8 stays signed off; the gap batches run before Phase M's remaining batches, each at the user's word
    (phase order, `batches.md` rule 7, bent for core fixes the migrator does not depend on, except 26Q after GF6).

157. **Task GF6 (2026-11-23): a link turned back to its original URL is a column, not `assigned_product_id = -1`.**
    The plan's -1 would have hidden the row from the 26 queries that read an untied link as
    `assigned_product_id = 0`. A column `use_original` (schema step 12) lets a rescan store the row as an ordinary
    untied link described by its original URL (domain, Amazon IDs), so it is an opportunity again, as in Lasso after
    Unmonetize. Off is the existing DELETE route; a product assigned later clears the flag. 26Q's API is unchanged.

158. **Task 24G added: icons in the editors' badge and button texts (the user, 2026-11-23: "we need a solution for
    this", then "Go, as proposed").** Lasso's Customize Display has plain text fields; Affibloom's product page,
    Display and table builder have F28's icon picker, the editors had none, and R40's quote rule broke a typed span.
    The editors store `<span class='<class>'></span> <words>` (single quotes, which R40 and the shortcode keep); the
    box prints it as before. The block reuses the table builder's icon grid; Elementor gets a dropdown per text
    (Elementor is inactive on the site); "Add icon" stays in Settings → Display. Task in `phase-4-editor-block.md`.

159. **A Lasso box Affibloom's block cannot hold becomes `[affibloom …]` in a Shortcode block (the user, 2026-11-23:
    "Yes, as a shortcode").** F10 D16 kept such a box as Lasso's while Affibloom still drew `[lasso]` (Task 27,
    dropped by deviation 146); after the cutover it would draw nothing. The converter (LM2's dry run, LM3's run) now
    writes a Lasso block whose box the Product box block cannot draw, or could not hold whole (`rating="4.5"`, `pros`, a
    `type="link"`), as `<!-- wp:shortcode -->[affibloom …]<!-- /wp:shortcode -->`, which reads every attribute. Only an
    unknown product, group or table, or an empty box, still blocks. Migrator `5c7e187`.

160. **Task LM6A added: the blocks Lasso's scan rewrote (the user, 2026-11-23: "I agree with your recommendation").**
    LM6 removes the stamps as planned, but its promise that the cleaned posts open without "This block contains
    unexpected or invalid content" does not hold: on Local 969 GenerateBlocks buttons in 92 posts (and about 30 image,
    headline and FAQ blocks) fail the editor's check once their stamps are gone. Lasso's scan rewrote `rel` on links
    opening in a new tab (dropping `noreferrer`, `class-lasso-cron.php` "fix tabnabbing"), and Lasso's editor script
    makes a stamped block save as stored, so the mismatch shows only without stamps; with Lasso off it shows either
    way. The repair changes bytes beyond the stamps and needs each block's save rules, so it is its own task (LM6A,
    Batch M.14a); LM6 keeps "every other byte unchanged". Migrator `60ddcf4`.

161. **26B changed: `--update --force` gives an adopted product Lasso's values (the user, 2026-11-23: "Restore Lasso's
    values").** The one-time import (deviation 87) made products with its own rules (a missing toggle read as off, so
    New Window off; Prime from the cached row; a short URL kept), and 26A adopted them as they were; 26B never touched
    them while Lasso's record did not change. On Local that was ~790 of 973 parity differences (29A); staging ran the same
    import. Now the mark keeps `adopted` (a mark written before says it when the one-time import ran and did not skip the
    record, from its `affibloom_lasso_import`, the migrator's key); with `--update --force` an adopted product takes
    Lasso's values where they differ, Affibloom's Amazon price (`price`, `show_price`) kept, once: its mark then says it
    is not adopted. Without `--force` nothing changes. A person's edit since the adoption cannot be told from an Amazon
    refresh on an older mark, so `--force` takes Lasso's side there, as it does for an edit on both sides. Local: 254
    products restored (New Window 252, Prime 30, URL 13, a few names, images, one description, disclosure, theme).

162. **Affibloom's box prints a product's title as stored, not as Lasso's box cleaned it (the user, 2026-11-23: "Keep
    the real title").** Lasso's box passes every title through `remove_unexpected_characters_from_post_title()`, which
    turns any character outside plain ASCII punctuation (curly quotes and apostrophes, dashes) into a space: readers see
    "Mizuno Wave Momentum Men s Volleyball Shoes", "Tachikara TB-18 The Setter". Affibloom prints "Men’s" and
    "“The Setter”". 5 boxes on 3 guides differ (Task 29A's values, 29B's pages); written in `docs/parity.md`, listed in
    the parity suite's `EXPLAINED` and `EXPLAINED_PAGES`.

163. **Task LM10: no release tag, and Finish keeps one mark (the user, 2026-11-23: "Go, as recommended").** The plan's
    Step 4 tags the migrator's release; as Phase 8 had none (deviation 154), none is made. Finish erases every option and
    meta the migrator wrote but sets `affibloom_lasso_finished`: without the ID map a second migration would duplicate
    every product, so once finished the page only says to delete the plugin and the routes and commands (but `finish`)
    are not started. Uninstall removes the mark too. On Local the plugin is removed with
    `wp plugin uninstall affibloom-lasso-migrator --deactivate --skip-delete`, as deleting it would delete its repository.
