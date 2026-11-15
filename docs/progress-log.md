# Affibloom — progress log

Moved verbatim from `progress.md` on 2026-11-15. Add each session's entry at the top of the Session log.

## Session log

- **2026-11-15 (Claude Code, faster checks, at the user's word "Steps 1–6"):** deviation 111. Test tiers (`batches.md`
  rule 5, `/next-task`, `CLAUDE.md`): per task only its own acceptance tests, feature specs per batch, the full suite
  per phase. Lint cache (34 s → 1.9 s), WP-CLI OPcache file cache (2.3 s → 1.3 s a call), `createProduct()` and
  `setSettings()` one call each, Playwright runs save and restore the site themselves (`run-state.php`), test 45 s and
  click 10 s limits, `tools/shot.cjs`. Checked by hand and on three runs (site identical after each). F23 AC5's reload
  race fixed (waits for the order save; F23 14/14); Local has only two PHP workers (~16 s stalls). Backup
  `app/affibloom-backup-2026-11-15-before-run-state-check.sql`. **Next: Batch 2.1 (C1B, C1C, C1D), when the user says go.**

- **2026-11-15 (Claude Code, the Local database brought in line with `21f7d03`, at the user's word):** the user:
  everything in the Local database is dummy data; clean it up to match the reverted code. Backup first:
  `app/affibloom-backup-2026-11-15-before-revert-cleanup.sql`. Done:
  - **C1C's leftovers removed:** `wp_affibloom_amazon_products` dropped, `affibloom_db_version` set back to **1**
    (the code's only Migrator step), so C1C's redo creates its table as step 2. The C1A stub's test options
    (`affibloom_test_amazon_stub`, `affibloom_test_amazon_requests`) deleted; with them gone the stub stays off.
  - **The library re-seeded:** all 259 products (205 published, 54 trashed, 6 of them test products) deleted for
    good with their meta, then `tools/seed-from-lasso.php` run (deviation 51): **254 published**, one per non-empty
    Lasso record (263 less the 9 empty ones, which it lists as skipped), with Lasso's dates, so the Dashboard's order is
    the seed's again. The user said 252; that was the count left after C1's run removed two seeded products, and the
    original seed is 254 (deviation 51), so 254 it is. F9 AC6's "new tab off on every product" went with the old rows.
  - **Kept as they were:** the Amazon credentials and Partner tag (C1A's card), `affibloom_settings`, the fields and
    icons, the Lasso import's "finished" option (254 imported, which the seed matches) and the finished bulk-add
    job. Product IDs changed (now from 16925); no test, record or term points at an old ID.
  - **Checked:** 254 published, 254 distinct `_affibloom_lasso_id`, nothing in the trash, no orphan meta or Group
    terms, no `wp_affibloom_*` table, `Migrator::pending()` false, `GET /affibloom/v1/products` total 254. No
    E2E run (tests make and keep their data; the user asked for a clean library). No code changed.

- **2026-11-15 (Claude Code, revert to `21f7d03`, at the user's word):** `main` was reset to `21f7d03` and
  force-pushed (the user chose a hard reset over a restore commit). The 16 commits after it (`b50079f`..`3a71ed8`)
  are off `main` and GitHub; they are kept only on the local branch `backup/before-revert-to-21f7d03`. Taken out:
  - **Code:** the first try of **C1B** (`3a2be33`, `111d207`: the Creators API client, `wp affibloom amazon get`,
    the Amazon card's saved-login and save-warning fixes), **C1C** (`9974c8a`: name and image on add, the
    `Table` class, the Amazon products table), **C1D** (`cfd9d32`: Refresh) and the single-add redirect change
    (`a5a52c7`). None of it was ticked in the phase file at `21f7d03`; C1B, C1C and C1D stay open, **Batch 2.1 is next**.
  - **Process:** the side-work agents (`b50079f`), the two lanes and `local-wp.sh`'s checkout lookup (`10b0353`,
    `72e3bc4`, `b4abda8`), the test-data sweep and its revert (`d0c2825`, `00f2835`), their log entries
    (`e490358`, `247f7b7`, `c4022f0`, `c2ebe96`) and the undo of the records split and batches (`3a71ed8`). So
    the split records and **one batch per session** (`plans/batches.md`) stand, as at `21f7d03`. Outside the repo,
    the web-root `CLAUDE.md`, the live `.claude/commands/` and the memory note were brought back in line with that.
  - **The Local database was not reverted** (no backup was asked for or made): `affibloom_db_version` is **2** and
    `wp_affibloom_amazon_products` exists with 21 rows, both from the reverted C1C. The code at `21f7d03` knows only
    step 1, so nothing runs. **When C1C is redone,** its table step must still run on this copy: drop the table and
    set the version back to 1 (after a backup, the user's call), or check that the step's columns match the
    leftover table. The Amazon credentials and Partner tag saved since C1A are still stored.
  - **Library on the Local copy (2026-11-15, after the revert):** 205 published products and 54 in the trash (252
    were seeded). The dropped log entries said every product was set to `trash` at about 05:52, by a cause not
    known, and that the seeded library was later put back from
    `app/affibloom-backup-2026-11-15-before-c1c.sql` and 1,088 test products deleted by hand; two seeded
    products (15283, 15520) were removed for good by C1's run. Restoring the library is the user's call.
  - Checks on the reverted code: `composer lint` clean, `composer analyse` no errors, unit **589 passed** (as C1A
    left it). The records checked against the code: C1A built and ticked, nothing of C1B-C1P ticked, every path
    named in `progress.md`, `environment.md`, `batches.md`, the commands and `CLAUDE.md` exists. No E2E run.
    Docs only, no new code.

- **2026-11-15 (Claude Code, faster implementation, at the user's request):** `progress.md` split into `progress.md`,
  `deviations.md` and `progress-log.md` (`29a4cae`); `/next-task` reads only the current task's section. Then, at the
  user's word ("let's do change 2, batching small tasks"), one batch per session: `plans/batches.md` holds the rules
  and 139 batches for the 252 open tasks (each in exactly one, checked by script; MCP1 is dropped). `CLAUDE.md`,
  `/next-task` and the memory note updated. Docs only, no code.

- **2026-11-15 (Claude Code, Task C1A, at the user's word "All of C1 here", paused after C1A at the user's word):**
  Settings → Amazon has the Product Data API card (Default Tracking ID, Credential ID, Secret, Partner tag, Version,
  Validate, the state chip); `POST /amazon/validate`; Save Changes validates unvalidated credentials (R23); the
  secret and Credential ID are never printed. Deviation 110 (stub inside the plugin at the user's request). C1 6
  passed, 3 skipped; unit 589; lint and PHPStan clean; looked at 1280 and 375 px. **Next: C1B, when the user says go.**

- **2026-11-15 (Claude Code, the Settings card gaps, at the user's word):** cards in a Settings column were 56px apart
  (an old card-after-card margin on top of the column's gap), 28px where the columns meet; now 28px everywhere on
  General and Amazon. Deviation 109. F17 5/5, F16 11 passed and 1 skipped, F9 14 passed and 1 skipped (quiet runs; the skips are Phase 3); no PHP changed.

- **2026-11-15 (Claude Code, the product page's Button Text, at the user's word):** the link and the button text stack
  when the button block is too narrow for both (a container query on the buttons list), so Button Text is no longer
  ~55px wide at 1280 px; Lasso's side-by-side row stays where it fits. Deviation 108. F28 6/6, F29 2/2 and F1 27/27 passed (quiet runs); no PHP changed, so lint, PHPStan and the unit tests stand as in deviation 107.

- **2026-11-15 (Claude Code, Phase 2 audit at the user's request):** the whole phase checked against the code, git
  and these records; deviation 107. Records reconciled (deviations 71a and 95a, status, Next up, stale headings and
  plan notes); F9's six failing tests were test faults and are fixed (AC2 waits for Phase 3; the save test renamed
  AC17); F1's AC16/AC19 reload race fixed. Quiet runs: F9 14 + 1 skipped, F23 14/14, F1 27/27 twice with the order
  kept, F16 11 + 1 skipped, F19 20/20. By hand: 9D on 1,025 products, 9H, 9K, 2X4 (stubbed redirect), the 375 px looks.
  The user also asked to fix Display's Button alignment clipped at 375 px: `admin.css`, checked at 375 and 1280 px.
  Lint, PHPStan and 571 unit tests clean. Every Phase 2 task but C1A-C1P is done; next: C1A.

- **2026-11-14 (Claude Code, Filters panel fix at the user's request):** the Dashboard's Filters panel no longer
  reopens on every load after being closed with Escape or an outside click; every close now clears
  `affibloom_filters_open`; deviation 106. F19 AC20 updated first (red), then passed; `node --check` clean. No PHP
  changed, so PHPCS and PHPStan were not rerun; the rest of F19 was not run (it writes test data).

- **2026-11-14 (Claude Code, Task FL5, a session started from another):** the product page's field rows now store each
  value with its lines trimmed and blank lines dropped (`Domain\Fields\FieldValue`), refuse a value over 1,000
  characters with 422 and the words under its row, and are dragged by a grip (Primary Rating has none) as well as
  moved by the buttons; deviation 105. Lint (project), PHPStan level 6 and 571 unit tests clean; F26 15/15, F1 AC10
  (2) and F23 AC5 and AC10 passed; rows seen at 1280, 900 and 375 px with a refused value, mid-drag and the keyboard
  move. A check product (16160) was made and deleted after a backup (`.local/backup-2026-11-14-before-fl5-check.sql`).
  Other sessions shared the tree, so the commit holds only FL5's hunks.

- **2026-11-14 (Claude Code, settings save fix at the user's request):** Settings → General, Display (and its Reset)
  and Amazon now save as Lasso does: AJAX behind "Updating Your Site", then the same address again with the toast;
  deviation 104 (reverses F9 D5; the user chose "Exactly as Lasso"). Started from F9 AC17 (then named AC16; red: no dialog). Unit 571,
  PHPStan clean, PHPCS clean on the changed files; F16, F17, F28 and F9 AC17 passed; dialog and toast seen in a
  screenshot. Other sessions shared the tree and ran Playwright at the same time, so the commit holds only this
  change's hunks. **Open:** F9 AC2, AC3, AC7, AC8, AC9, AC15 fail for reasons outside the save path.

- **2026-11-14 (Claude Code, Task ON2; the same session as ON1):** the notice region, foreign notices and the update
  notice (F38 AC4-AC6); deviation 103. Started from `NoticeBoardTest`, `UpdateAvailableNoticeTest`, an `UpgradeHooks`
  case and F38 AC4-AC6 (red: classes missing; the fixture's notice showed on the Dashboard; no `affibloomNotice`; no
  update notice). Unit 563, lint and analyse clean; F38 AC1-AC6 7 passed; looked at 1280 and 375px with the update
  notice and three script notices (the transient put back after). The commit holds only this task's hunks (another
  session shared the tree). Next: the user's choice; Phase 2's open tasks are 2X1-2X5, FL5 and C1A-C1P.

- **2026-11-14 (Claude Code, Tasks 13F-13J in one session at the user's word):** the Dashboard's second pass (F19);
  deviation 102. First, at the user's choice, the uncommitted G2-G5 work was committed alone (`97c1ac0`). Then 13F
  `c0b7021` (filters without JavaScript, `?post_id=`), 13G `67a5562` (search by added buttons' links; upgrade step 1),
  13H `a09a1f1` (AC4/AC10 on a full library), 13I `7c400e9` (stable order, live counts), 13J `2c9a0da` (saved-view
  resets, messages, panel memory). Each started from its acceptance test, red for the right reason; 562 unit tests,
  lint and PHPStan clean on these tasks' files (another session's new `ShellNotice.php` fails lint). Seen at 1280 and
  375 px with and without JavaScript. Three other sessions (TL2, ON1, ON2 and Settings work) shared the tree, so each
  commit holds only its task's hunks. **Open:** a quiet full F19 run; Local needs a reset before the user's next run.

- **2026-11-14 (Claude Code, records for Tasks FL1-FL4; the user: "Run F26 and update the records for FL1-FL4"):**
  FL1-FL4 had been built and pushed earlier the same day (`89ef637` Fields list, `b366954` new field, `ba6e821` edit
  and delete, `e05a9c7` Links tab) without plan ticks, a session-log entry or deviation notes. This session only
  checked them and wrote the records; no code changed. F26 `tests/E2E/acceptance/F26.spec.ts`: **13 passed** (AC1-AC5,
  AC2's product-page case). A first run failed 7 cases on `ENOENT` trace files only, because another session's F16 run
  cleared `test-results` at the same time; the rerun used its own `--output` and `--trace=off`. F1 AC10 (both cases)
  passed. `composer test:unit` 556 OK, `composer analyse` and `composer lint` clean (run on the shared working tree,
  which also held the ON1/ON2 session's uncommitted files). Ticked FL1-FL4 and their steps in the phase file.
  **Not recovered:** any differences from the plan's text made while building FL1-FL4 (none were recorded) and the
  by-hand look at 1280/900/375 px, which this session did not repeat. Next for Fields: FL5.

- **2026-11-14 (Claude Code, Task ON1; the user asked for ON1 and ON2 in this session):** navigation in Lasso's order
  (F38 AC1-AC3); deviation 101. Started from `AdminNavigationTest` (red: classes missing) and `F38.spec.ts` AC1-AC3
  (red: no Menu button, the menu at 26 after Pages' neighbour, no footer). Built `NavItem`, `AdminNavigation`, the
  menu at position 20 in Lasso's order with the highlight filters, the header from the navigation with the Menu button,
  and the footer on all nine screens. Unit, lint, analyse clean; F38 AC1-AC3 passed; looked at 1280, 900 and 375px
  (Groups, folded and open). Another session was working on the Dashboard in the same tree, so the commit holds only
  this task's hunks. Next: ON2.

- **2026-11-14 (Claude Code, Task TL2, same session as TL1 at the user's word "Go with TL2"):** the upgrade routine
  (F37 AC2); deviation 100. Started from unit tests for `Upgrader`, `Migrator`, `OptionLock`, `UpgradeHooks` and
  `UpgradeCommand` (red: classes missing). 544 unit tests, `composer lint` and `composer analyse` clean. On Local
  (backup `app/affibloom-backup-2026-11-14-before-tl2.sql`) with a temporary probe step, removed after: two admin page
  loads ran step 1 once, lock released; `wp affibloom upgrade` said "Nothing to do", and after `affibloom_db_version`
  was reset it ran step 1; a failing step 2 kept the version at 1, released the lock, showed the notice on Plugins and
  made the command exit 1; a stored version of 0.0.9 was flushed (a marker rule disappeared) and replaced by 0.1.0 on
  the next request, and an unchanged version did not flush. Probe options deleted, rewrite rules flushed. Next: 13F.

- **2026-11-14 (Claude Code, Task TL1):** activation and deactivation (F37 AC1); deviation 99. Started from
  `tests/Unit/LifecycleHooksTest.php` (red: no `Plugin::activate()`/`deactivate()`), then `Lifecycle`, its two ports,
  `CronRegistry`, `OptionPluginState`, `BulkAddCron::HOOKS` and the two hooks in `affibloom.php`. 527 unit tests,
  `composer lint` and `composer analyse` clean. On Local (backup `app/affibloom-backup-2026-11-14-before-tl1.sql`):
  an `affibloom_bulk_add` event scheduled, `wp plugin deactivate affibloom` → no `affibloom_*` event left;
  `wp plugin activate affibloom` → `affibloom_version` 0.1.0, `affibloom_activated_at` set (autoload off); 528
  products and `affibloom_settings` unchanged. Another session's Groups work (G2-G5) was uncommitted in the same tree,
  so the commit holds only this task's hunks. Next: Task TL2 (upgrade routine).

- **2026-11-14 (Claude Code, Tasks G2-G5 in one session at the user's word):** Groups finished (F23 AC3-AC10) and the
  header's 6px overflow at 375px fixed; deviation 98. Tests were written first for all four, the code followed, and the
  suites ran once at the end (results in the commit message and below). Another session was working in the same tree
  (the Fields area), so the commit holds only these tasks' hunks.

- **2026-11-14 (Claude Code, Task G1):** the Groups list and the new-group form (F23 AC1-AC2); deviation 97. Another
  session was working in the same tree (Tasks 9B-9L and the width change), so the commit holds only this task's hunks.
  Started from `F23.spec.ts`, then the domain (`Group`, `GroupPage`, `GroupRepository`), `GroupService`, the taxonomy
  and repository, the screens, the REST route and the script. 466 unit tests, lint and PHPStan clean on this task's
  files; F23 passed twice in a row; F19 and `admin-shell` passed. **Open:** the user's own run of F23 if wanted. Next:
  Task G2 (a group's Details: edit and delete).
- **2026-11-14 (Claude Code, Tasks 9B-9L in one session at the user's word; two other sessions were working on 2X4-2X6 and
  G1 in the same tree):** Settings → General rebuilt in Lasso's two-column structure with all of F9's cards except the
  Link Index (9J, Phase 5), Permissions and Uninstall (F37). Deviation 96. One commit; the other sessions' changes were
  left out of it. 495 unit tests, lint and PHPStan clean; **F9's 14 acceptance tests not run** (signed out).
  **Open: the user's signed-in F9 run**, the look at 1280 and 375 px, and a DB-backed check of Override All Links (82
  products, count the meta before and after). Next: Groups G1-G5 (another session), then FL1.

- **2026-11-14 (Claude Code, Tasks 2X4, 2X5, 2X6, three in one session at the user's word):** 2X4 `6fdb829` a kept
  redirect to Amazon is an Amazon product; 2X5 `48ef102` a bulk run records and skips any failing link, and the add
  button reads "Continue Anyway" after a server error (retries the same link, the user's choice); 2X6 `4e34621` brag
  mode removed. Each started from a failing unit test; 456 unit tests, `composer lint` and `composer analyse` clean.
  Deviations 93-95. Run by Claude afterwards (deviation 95a): F2, F16 and F18 pass with the new cases. Next: Groups G1-G5.
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
- **2026-11-14 (Claude Code, Task 2X1):** the four product-page gaps built and checked by lint, PHPStan, 432 unit tests, a
  WP-CLI render, a look in Brave and the user's F1 and F29 runs, all green after two test-stub fixes (deviation 91). Done;
  only the 375 px look is owed. Next: 2X2.
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

- **2026-11-13 (Claude Code, broken icons):** the user reported Full review and YouTube broken; fixed by deviation 71a
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
