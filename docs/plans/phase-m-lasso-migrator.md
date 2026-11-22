# Affibloom Lasso Migrator — Phase M — Implementation Plan

> **How to run this plan:** inline, in one working session, task by task, in the order below. Do not hand tasks to subagents. When a step is finished, change its `- [ ]` to `- [x]`.
>
> **Explain as you go:** before each task, say in two or three sentences what the task builds, why it is needed now, and how it will be checked. After the task, say what changed and what the verification showed. No vibe coding: every line written must be one you can explain.
>
> **Shared rules:** the architecture, working method, conventions and checks in [`00-overview.md`](00-overview.md) apply to this plugin too. Read it first.

## Status

Planned 2026-11-17 (the user; deviation 146). The user, 2026-11-17: "All of the migration functionalities should be
developed as a migration plugin, not our core feature. Affibloom is a standalone affiliate plugin." Phase M runs
**after Phase 8 is signed off and before Phase 9** (the cutover): every Affibloom feature the families map into exists
by then. The moved tasks keep their IDs (25A-26P, 26G-26N, 29A-29E); the new tasks are LM1-LM10.

## What the plugin is

- **Affibloom Lasso Migrator** (slug `affibloom-lasso-migrator`): a separate WordPress plugin that moves one site from
  Lasso to Affibloom and is then deleted. Affibloom needs nothing from it and names nothing of Lasso (Phase 5, LC1-LC2).
- Header `Requires Plugins: affibloom`; with an older Affibloom than it was built for, it shows a notice and does nothing.
- Folder `wp-content/plugins/affibloom-lasso-migrator/`, its own private GitHub repository (created in LM1 with the
  user's OK), version, `CHANGELOG.md` and release zip. Namespace `AffibloomLassoMigrator\`, text domain
  `affibloom-lasso-migrator`, prefix `affibloom_lasso_`. Affibloom's conventions and tools: PHP 8.3, the four layers,
  one composition root, PHPUnit 9.6 with Brain Monkey, phpcs (WordPress standard), PHPStan; its Playwright ACs live in
  Affibloom's `tests/E2E` (skipped when the migrator is not active).
- Three jobs, each with a dry run, a report and a way back:
  1. **Data:** Lasso's records, settings and every other family become Affibloom's (25A-26P).
  2. **Content:** every `[lasso]` shortcode, `affiliate-plugin/lasso` block, `data-lasso-*` stamp and `.lasso-*`
     Additional CSS rule becomes Affibloom's own format; nothing of Lasso is left (LM2-LM9).
  3. **Proof and removal:** parity, the rollback drill, then Finish; the plugin is deleted and the site runs on
     Affibloom alone (29A-29E, LM10).
- It reads Lasso's data with SELECT only and never changes it (ADR-2): the way back is to revert the content and
  switch Lasso on again. It writes Affibloom's data only through Affibloom's extension API (LM1), never Affibloom's
  tables or meta directly, and posts only through its own `ContentChangeWriter` (LM3), with a copy of each.
- Staging has no SSH: every step has an admin entry point (25D, 26O, LM8).

## Reading the moved sections (they were written for Affibloom's core)

- `src/`, `tests/`, `templates/` and `assets/` are the migrator's folders; a class written as
  `Affibloom\Application\Migration\X` is `AffibloomLassoMigrator\Application\X`, and so on through the layers.
- Affibloom's classes a section names (`ProductRepository`, `ProductService`, `SettingsService`, `GroupService`,
  `FieldsService`, `SavedViewService`, F24's stores and `CloakHandOver`, F27's variants, F21's index, F25's tables,
  F11's issues, F22's opportunities) are reached through the extension API. A section's "modify <Affibloom file>"
  becomes a method added to the API, committed in Affibloom's repository with the task's ID.
- `wp affibloom migrate …` is `wp affibloom-lasso migrate …`; `/affibloom/v1/migration/*` is
  `/affibloom-lasso/v1/*`; the "Migrate from Lasso" page is the migrator's page, under Affibloom's menu (LM1).
- The options a section names `affibloom_migration*` are the migrator's (`affibloom_lasso_migration*`) and go with
  LM10. The ID map `_affibloom_lasso_id` and `affibloom_lasso_import` (written by the one-time import, deviation 87)
  are the migrator's map until LM10 removes them.
- The one-time "Import from Lasso" button is already gone from Affibloom (LC1); 26A adopts the products it made, and
  26O has nothing to retire.
- "Phase 5", "Phase 6", "Phase 7" in a moved section mean Affibloom's features built there; all are done first.
- A *Changed 2026-11-17* note under a task's heading wins over the section's text.

## Decisions for this phase

- **Reversed 2026-11-17 (the user): decisions A1 and A2.** The user: "I want every existing [lasso] shortcode across
  all posts and pages to be replaced with the equivalent AffiBloom shortcode. All existing content must be migrated to
  AffiBloom's native format, so the Lasso helper plugin is no longer needed and can be safely removed. … Do not leave
  any Lasso shortcodes or legacy Lasso blocks behind." The conversion (was 28A-28E) and the stamp removal (was H3-H4)
  come back as LM2-LM6 and LM8; 29D comes back; ED6 and 27A-27I are not built (their attribute rules are the
  conversion's, LM2).
- **Target format (settled from the request):** an `affiliate-plugin/lasso` block becomes Affibloom's
  `affibloom/product-box` block with the same overrides; a `[lasso …]` in a shortcode block becomes that block too; a
  bare `[lasso …]` in other text, a widget or Elementor data becomes `[affibloom …]`.
- **Lasso's stamps (settled):** `data-lasso-id`, `data-lasso-lid` and `data-lasso-name` are removed; nothing new is
  stamped into posts (A3 stands: Affibloom prints its own attributes at render time, 35H). A tie the index would not
  find from the link itself is kept as the row's `assigned_product_id` (LM6).
- **Nothing is left behind:** an item that cannot be converted is reported, and Finish (LM10) refuses until it is
  converted or an editor removes it (LM9's check).
- The copy and the way back: each changed post keeps its content before the change and a revision; revert is exact; a
  post edited since is refused unless `--force` (F34 N6). No History screen (F34 N1, N2 stand): the migrator's page
  lists the converted posts while it is installed.

Earlier migration decisions, unchanged (moved from Phase 5):

- **F29, any number of buttons (the user, 2026-11-13; register U12):** a product has an ordered list of buttons after the primary (`Product::$buttons`, Task 14H). Task 26A maps Lasso's `second_btn_url`, `second_btn_text`, `open_new_tab2`, `enable_nofollow2` to the first entry of `_affibloom_buttons` (F29 B8); `secondary_url` / `secondary_text` shortcode overrides apply to that first entry (B6).

Decided 2026-11-12; reasons in `docs/decisions/task-6-decisions.md` in the plugin repository. Apply them when the task is worked, and record any code that differs from the text below as a deviation in `progress.md`.

- Task 26A: `check_duplicate_link` is not imported (no `allow_duplicates`, decision 2); button texts and badges keep their icon spans (decision 5). (F10 N5 below asks to reverse decision 2.)
- Tasks 18S2 / 27A: `title_type` accepts `h2`-`h6`; anything else (the site's one use, "Best Lightweight") is ignored and logged, not turned into a badge (decision 7). (F31 N2 below asks to accept `h1` too.)
- **Decided (the user, 2026-11-14), E1 (revised): start from zero.** No click figures are imported from Lasso; Task 26J is dropped.
- **Decided (the user accepted the recommendation, 2026-11-14):** never copy the old PA-API key pair; copy Creators credentials if present, shown only as "set" (F10 N3, recommended: as stated).
- **Decided (the user accepted the recommendation, 2026-11-14):** keep skipping the 9 records with no link and list them, or create them from the ASIN in their slug (F10 N4, recommended: skip and list).
- **Decided (the user accepted the recommendation, 2026-11-14):** reverse Task 6 decision 2 and import "Allow duplicate destinations" (`check_duplicate_link`) once F9 builds the control (F10 N5, recommended: reverse it).
- **Decided (the user accepted the recommendation, 2026-11-14):** migrate stale Amazon values (name, image, price, Prime) as the products' own until C1 has credentials (F10 N6, recommended: as decided in deviation 18).
- **Decided (the user accepted the recommendation, 2026-11-14):** `[lasso prompt]` prints Lasso's cached AI text, else nothing; `[affibloom]` offers no `prompt` (F31 N1, recommended: as stated; task 27F).
- **Decided (the user accepted the recommendation, 2026-11-14):** accept `title_type="h1"` (F31 N2, recommended: accept).
- **Decided (the user accepted the recommendation, 2026-11-14):** keep Lasso's `compact="hide"` no-op for `[lasso]`; `[affibloom]` accepts `true` only (F31 N3, recommended: as stated).
- **Decided (the user accepted the recommendation, 2026-11-14):** drop Lasso's History toggle and 14-day purge (F34 N1, recommended: drop both).
- **Decided (the user accepted the recommendation, 2026-11-14):** Lasso's 18 history rows are not shown or revertible (F34 N2, recommended: accept).
- **Decided (the user accepted the recommendation, 2026-11-14):** not built because nothing rewrites: Fix Overwritten Links, the Yoast FAQ fix, scan-time content fixes, the data scripts (F34 N5, recommended: accept all).
- **Decided (the user accepted the recommendation, 2026-11-14):** reverting from History refuses a post edited since; `--force` stays in WP-CLI (F34 N6, recommended: accept).

## Tasks in this phase

**The plugin**

- [x] Task LM1: The plugin, and Affibloom's extension API

**F10 — Lasso's data**

- [ ] Task 25A: The dry run — `wp affibloom migrate --dry-run`, products
- [ ] Task 25B: The dry run — settings
- [ ] Task 25C: The dry run — every other family, and what is left behind
- [ ] Task 25D: The report on an admin page
- [ ] Task 26A: Products — `wp affibloom migrate --only=products`
- [ ] Task 26B: Products again — `--update`
- [ ] Task 26C: Settings — General and Amazon
- [ ] Task 26D: Settings — Display for Single, Grid and List
- [ ] Task 26E: Custom fields — definitions and values
- [ ] Task 26F: Groups — `wp affibloom migrate --only=groups`
- [ ] Task 26L: Amazon — `wp affibloom migrate --only=amazon`
- [ ] Task 26M: Saved Dashboard views — `wp affibloom migrate --only=views`
- [ ] Task 26G: Cloaked addresses — `wp affibloom migrate --only=slugs`
- [ ] Task 26H: URL variants — `wp affibloom migrate --only=variants`
- [ ] Task 26K: Link issues and dismissals — `wp affibloom migrate --only=issues` (from Phase 6)
- [ ] Task 26I: Comparison tables — `wp affibloom migrate --only=tables` (from Phase 7)
- [ ] Task 26N: Opportunity switches, dismissals and the new features' settings — `--only=opportunities` (from Phase 7)
- [ ] Task 26O: Run from the admin page, in batches
- [ ] Task 26P: Check the migrated products against the baselines

**F10 — Lasso's content becomes Affibloom's**

- [ ] Task LM2: The conversion's dry run — every Lasso item in content
- [ ] Task LM3: Convert Lasso's blocks, keep a copy, revert
- [ ] Task LM4: Bare shortcodes, widgets and Elementor data
- [ ] Task LM5: The other display types, groups and tables
- [ ] Task LM6: Lasso's stamps in content
- [ ] Task LM7: The site's Lasso CSS rules
- [ ] Task LM8: The conversion from the admin page
- [ ] Task LM9: Nothing of Lasso is left

**F10 — parity, the rollback drill and removal**

- [ ] Task 29A: Parity — every box on every guide
- [ ] Task 29B: Parity — whole pages
- [ ] Task 29C: Parity — every link, and no call to Lasso (from Phase 7)
- [ ] Task 29D: Parity — converted, and reverted
- [ ] Task 29E: Rollback drill
- [ ] Task LM10: Finish the migration and remove the plugin

## Cross-phase notes

- **Needs:** Phases 2-8 done and signed off; in particular F11 (26K), F25's tables (26I, LM5), F22, F35 and C3 (26N),
  F24 and F27's stores (26G, 26H), F21's index (LM6) and Phase 8's release candidate (Task 39), which the migrator's
  release is built against.
- **Phase 9** runs this plugin on staging (40) and production (41); Task 42 deletes it after the monitoring window, with
  Lasso.
- Affibloom's extension API (LM1) is the only coupling. Affibloom's own tests never need the migrator.

---

## Tasks: the plugin

### Task LM1: The plugin, and Affibloom's extension API

**Feature:** Phase M (deviation 146)

**Added by deviation 153 (Task ON6, 2026-11-22):** on a site with Lasso's data the migrator answers
`affibloom_setup_wanted` with false (and may set `affibloom_setup` to `done`), so Affibloom's setup wizard does not open by
itself there (F38 D13).

**Why:** the migration lives outside Affibloom, so it needs its own plugin and one supported way into Affibloom's
services, instead of reaching into Affibloom's classes or tables.

**Depends on:** Phase 8 signed off; the user's OK to create the private GitHub repository.

**User action:** Plugins lists "Affibloom Lasso Migrator". Activated beside Affibloom, Affibloom's menu gains
**Migrate from Lasso** (an empty page, "Run the dry run first.", until 25D). Without Affibloom, WordPress refuses to
activate it (Requires Plugins); with an older Affibloom it shows "Affibloom Lasso Migrator needs Affibloom <version>
or later." and does nothing.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| Affibloom: `Affibloom\Extension\Api` (its version, and only the methods a caller needs) and the action `affibloom_ready` (once, on `plugins_loaded`, with the Api); a documented way to add a submenu page in Affibloom's design (`AdminMenu`); `docs/extension-api.md` | the migrator's `Plugin` |
| the migrator: `affibloom-lasso-migrator.php`, `composer.json`, `phpcs.xml.dist`, `phpstan.neon.dist`, `phpunit.xml.dist`, `.gitignore`, `CHANGELOG.md`, `README.md`, `src/Plugin.php` (composition root), `Interface\Admin\MigrationScreen` (the empty page) | WordPress |

**The API grows with its callers.** Each later task adds only the methods it calls, in Affibloom's repository, with the
task's ID in the commit. Methods are typed, listed in `docs/extension-api.md` and covered by Affibloom's unit tests;
nothing in Affibloom names Lasso or the migrator.

**Files:** the above; tests: Affibloom `tests/Unit/Extension/ApiTest.php` (fires once, version); the migrator's
`tests/Unit/PluginTest.php` (refuses an old version); E2E (activate, the menu item, the empty page).

- [x] **Step 1: Failing tests.**
- [x] **Step 2: Affibloom's Api, the action and the submenu hook.**
- [x] **Step 3: The migrator's scaffold, its repository and its checks** (`composer lint`, `analyse`, `test:unit`).
- [x] **Step 4: Run and look.** The Plugins list; the empty page at 1280 px in Affibloom's design.
- [x] **Step 5: Commit** — Affibloom: "Open an extension API for helper plugins (LM1)"; the migrator: "Start the
  Affibloom Lasso Migrator plugin".

*Note (2026-11-22):* the user's repository is `Rupashdas/lasso-to-affibloom`, **public** at the user's word (the plan
said private), cloned into the planned folder
`affibloom-lasso-migrator`. `affibloom_ready` is fired by its own module, `Extension\ReadyAction`; `Api::add_page()` lists
the page at Lasso's Import place (before Settings) for `Capabilities::SETTINGS`, printed by `Interface\Admin\ExtensionScreen`
(`templates/admin/extension.php`). The migrator needs Affibloom 1.0.0-rc.1 or later and answers `affibloom_setup_wanted`
with false while `lasso_version` exists; its unit tests and PHPStan read the Api from `../affibloom`. E2E:
`tests/E2E/acceptance/LM.spec.ts`. Checked: unit tests (Affibloom 1,207, migrator 6), lint, analyse, the page and the
Plugins list at 1280 px.

---

## Tasks: F10 — Lasso's data (moved from Phases 5, 6 and 7)

### Task 25A: The dry run — `wp affibloom migrate --dry-run`, products

**Feature:** F10 (R4-R6, R9, R10; D1, D3-D7, D18, D19; AC1)

**Why:** before anything is written, a report must say what becomes of every Lasso record.

**Depends on:** F1 (Tasks 11-17D), F29 (14H), F2 (Task 12): built. The existing `LassoRecordMapper`, `WpLassoSource`.

**User action:** the developer runs `wp affibloom migrate --dry-run [--report=migration.csv]`. A table with one line
per `lasso-urls` post (Lasso ID, name, type, action, target, note) and a summary: here `265 records: 254 to create, 9
skipped (no link), 1 to retire, 1 to trash, 0 already migrated, 0 errors (263 published). Nothing was written.` The
other families print "not built yet". With no Lasso data: "No Lasso data found."

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Cli\{CliCommands, MigrateCommand}` (`--dry-run`, `--only`, `--report`) | `cli_init`, `Plugin` |
| `Domain\Migration\{Family, MigrationLine, MigrationReport}` | `MigrationService`, the command |
| `Application\Migration\{MigrationService::plan(), Step, ProductsStep::plan()}` | the command |
| `Infrastructure\Lasso\LassoIdMap::product_for()` | `ProductsStep` (later 27A, 26E-26O) |
| `WpLassoSource::ids()` for every status; `LassoRecord` gains `status`, `slug` | `ProductsStep` |
| `LassoRecordMapper`: the image order with `lasso_thumbnail_id` (R10), the other store's price (R9), the retire pointer | `ProductsStep` |
| `php-stubs/wp-cli-stubs` | `composer analyse` |

**Files:** create the classes above, `tests/Unit/Application/{ProductsStepTest,MigrationReportTest}.php`; modify
`src/Plugin.php`, `LassoRecordMapper.php`, `WpLassoSource.php`, `LassoRecord.php`, `composer.json`,
`phpstan.neon.dist`, `tests/Unit/Application/LassoRecordMapperTest.php`, `tests/E2E/acceptance/F10.spec.ts` (AC1 as
corrected).

- [ ] **Step 1: Failing tests.** AC1 (the command's summary names 263 and 265 and the four actions). Unit: mapper rows of R9-R10 not yet covered; a report's sums; statuses. Expected red: no `affibloom` command.
- [ ] **Step 2: The command and the report.**
- [ ] **Step 3: `ProductsStep::plan()`, the ID map, the source's statuses.**
- [ ] **Step 4: Run and look.** The summary above on Local; the CSV opens; `git status` of the database: nothing written (row counts before and after).
- [ ] **Step 5: Commit** — "Report what the Lasso migration will do to every record".

---

### Task 25B: The dry run — settings

**Feature:** F10 (R20, R21, table F; D9, D10; AC4)

**Why:** 102 keys in one option: the report must show each key's destination or why it has none.

**Depends on:** 25A; F9 (Task 9), F16 (10A-10E), F17 (10F): built.

**User action:** `wp affibloom migrate --dry-run --only=settings,display` prints every key of `lasso_settings` and
the tracking-ID blacklist: key, Lasso's value, the Affibloom setting and value, or the reason ("waiting for F24",
"Lasso cloud", "not used by Lasso 369", "secret: not read"). Summary: `102 keys: N to import, N waiting, N left`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Migration\LassoSettings` (defaults, R20's normalising, the secret list) | the two mappers |
| `Application\Migration\{SettingsMapper, DisplayMapper, SettingsStep::plan(), DisplayStep::plan()}` | `MigrationService` |
| `LassoSource::settings()` (never returns the secret keys) | the steps |

**Files:** the classes; `tests/Unit/Application/{SettingsMapperTest,DisplayMapperTest}.php` (against
`tests/fixtures/inventory/settings.json`: every key has a target or a verdict; the flat-colour fallback; the
"custom" preset rule); F10 spec AC4.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: `LassoSettings` and the source.**
- [ ] **Step 3: The mappers and steps.**
- [ ] **Step 4: Run and look** (no secret value in the output or the CSV).
- [ ] **Step 5: Commit** — "Report where every Lasso setting goes".

---

### Task 25C: The dry run — every other family, and what is left behind

**Feature:** F10 (tables A-H; D15; AC5)

**Why:** "everything Lasso stores" needs a count per table and option group, also for what is not copied.

**Depends on:** 25A, 25B.

**User action:** `wp affibloom migrate --dry-run` now ends with a line per remaining family (groups, slugs, variants,
tables, clicks, issues, amazon, views, opportunities: found N, "waiting for <feature>" or the plan) and "Left
behind": every Lasso table and option group not copied, with rows and reason. A Lasso table on the site that the
inventory does not know is an error line.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LassoSource::inventory()` (tables present, row counts, option groups) | `MigrationService::plan()` |
| `Application\Migration\LeftBehind` (the verdict list of analysis tables A, G, H) | the report |
| a `plan()` that counts for each family not built yet | `MigrationService` |

**Files:** the above; unit test: every table of analysis A has a family or a verdict; F10 spec AC5.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Inventory and verdicts.**
- [ ] **Step 3: Run and look** (29 tables accounted for).
- [ ] **Step 4: Commit** — "Account for every Lasso table and option in the dry run".

---

### Task 25D: The report on an admin page

**Feature:** F10 (D13, D18; AC6)

**Why:** staging has no SSH; the client's review needs the report in wp-admin.

**Depends on:** 25A-25C.

**User action:** Affibloom → **Migrate from Lasso** (shown while Lasso data exists): a card per family (found, to
create, to update, skipped, waiting), the left-behind list, "Download report (CSV)". No Run button yet. No Lasso
data: the entry is hidden and the page says "No Lasso data found."

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Admin\MigrationScreen`, the submenu in `AdminMenu` (`Capabilities::SETTINGS`) | WordPress |
| `templates/admin/migrate.php`, its CSS, the design-system row | the screen |
| admin-post `affibloom_migration_report` (nonce; CSV) | the download link |

**Files:** the above; F10 spec AC6; by hand at 1280 and 375 px.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Screen and template.**
- [ ] **Step 3: The download.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Show the migration report in the admin".

---

### Task 26A: Products — `wp affibloom migrate --only=products`

**Feature:** F10 (R4-R10, tables B, C; D1, D4-D7, D17, D19; AC7, AC8, AC23)

**Why:** the products are what every box, link and later family hangs on.

**Depends on:** 25A. F1, F29, F2.

**User action:** `wp affibloom migrate --only=products`: `254 created, 9 skipped, 1 retired, 1 in the trash`. Again:
`0 created, 256 unchanged`. On a site with the one-time import's products: `254 unchanged (adopted)`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `MigrationService::run()`, `ProductsStep::run()` (targets of retired records first; `_affibloom_lasso_id`, `_affibloom_migrated`, dates; status through the repository's retire and trash) | the command |
| `Infrastructure\WordPress\OptionMigrationLog` (option `affibloom_migration`; reads the old `affibloom_lasso_import`) | the service |
| `tools/lasso-fingerprint.sh` (+ `.php`: counts and checksums of Lasso's tables, posts, meta, terms, options) | every write task, 29E |

**Files:** the above; `tests/Integration/ProductsStepTest.php` (twice; statuses; adoption; two products with one
Lasso ID → error); F10 spec AC7, AC8, AC23.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: The fingerprint tool.**
- [ ] **Step 3: `run()`, the log.**
- [ ] **Step 4: Run and look** (Dashboard: 254 products in Lasso's order; a retired one opens its target; the fingerprint equal).
- [ ] **Step 5: Commit** — "Migrate Lasso's products, once".

---

### Task 26B: Products again — `--update`

**Feature:** F10 (D8; AC9)

**Why:** Lasso stays live until cutover; the move is repeated then.

**Depends on:** 26A.

**User action:** `wp affibloom migrate --only=products --update [--force]`: `N updated, N unchanged, N changed on
both sides (kept)`; new Lasso records are created; a record deleted in Lasso is reported, its product kept.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `ProductsStep::run( update )` with the hash comparison | `MigrationService::run()` |
| `--update` and `--force` on the command | `MigrateCommand` |

**Files:** `ProductsStep.php`, `MigrateCommand.php`; integration test (Lasso-side change, Affibloom-side change,
both, `--force`); F10 spec AC9.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: The rule.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Refresh migrated products from Lasso without losing edits".

---

### Task 26C: Settings — General and Amazon

**Feature:** F10 (R20, R21; table F S1-S21, O4; D9, D10; AC10)

**Why:** the General and Amazon tabs must show the site's own values from the first run; a missing boolean read as off would change links and boxes at cutover.

**Depends on:** 25B; F9, F17 (10F). 26C maps the General keys that exist in Phase 5; F21's scan keys are mapped with 35D and 35G in this phase (each adds its keys to this step's map); F22, F35 and C3 keys are mapped by 26N in Phase 7.

**User action:** `wp affibloom migrate --only=settings`: the keys imported, waiting and left. Settings → General and
→ Amazon then show the site's values. Again: "unchanged".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Migration\SettingsStep::run()` through `SettingsService` (a complete set per tab, built from current values, so a missing boolean is not read as off) | `MigrationService::run()` |

**Files:** `SettingsStep.php`; integration test (Lasso's real booleans; second run; `--update`); F10 spec AC10.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: The step.**
- [ ] **Step 3: Run and look** (the two tabs).
- [ ] **Step 4: Commit** — "Import Lasso's General and Amazon settings".

---

### Task 26D: Settings — Display for Single, Grid and List

**Feature:** F10 (table F S22-S30; AC11)

**Why:** the three display types must look at cutover as they do in Lasso, which needs Lasso's theme, colours, labels and layout in Display settings.

**Depends on:** 25B; F16 (10A-10E).

**User action:** `--only=display`; Settings → Display shows per type Lasso's theme, colours, labels, density,
elements, layout, and the columns.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Migration\DisplayStep::run()` through `DisplayService` | `MigrationService::run()` |

**Files:** `DisplayStep.php`; integration test; F10 spec AC11; by hand: the three types against the production
screenshots (`tests/fixtures/baselines/admin`).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: The step.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Import Lasso's Display settings for every display type".

---

### Task 26E: Custom fields — definitions and values

**Feature:** F10 (R12; tables A T4-T6; AC12)

**Why:** the box's stars and the product page's fields come from Lasso's custom fields, so definitions and values have to move together.

**Depends on:** 26A; F26 (14C). Fields 99989-99994 are 26I's.

**User action:** `--only=fields`: `1 field created (DETAILS), 1 built-in renamed, 5 products' fields set`. The product
page's Fields and the box's stars match Lasso.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Migration\{FieldMapper, FieldsStep}` (definitions before products' values; `field_map` in the log) | `MigrationService` |
| `LassoSource::fields()` | `FieldsStep` |

**Files:** the above; unit and integration tests; F10 spec AC12.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Mapper, step, source.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Migrate Lasso's custom fields and their values".

---

### Task 26F: Groups — `wp affibloom migrate --only=groups`

**Feature:** F10 (R13; tables T7, X1; AC13)

**Why:** a product's group membership and the order of the groups' products are part of what Lasso stores; the group displays and the Dashboard filter depend on them.

**Depends on:** 26A; F23 G1-G4 (Phase 2).

**User action:** `wp affibloom migrate --only=groups` prints `N groups created, N memberships set`. The Groups list and each group's products show Lasso's membership in Lasso's order. Run again: unchanged.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LassoSource::groups()` (the `lasso-cat` terms, their memberships and `lasso_category_order`) | `GroupsStep` |
| `Application\Migration\{GroupMapper, GroupsStep}`: `plan()`, `run()`, idempotent, `--update`; writes `affibloom_group` terms and `_affibloom_order` through F23's `GroupService`; IDs mapped in `group_map` of the log | `MigrationService` |

**Files:** the classes above; `tests/Unit/Application/GroupMapperTest.php`, `tests/Integration/GroupsStepTest.php`; F10 spec AC13.

- [ ] **Step 1: Failing tests.** AC13 (twice: second run unchanged; the order; `group_map`). Expected red: no `groups` family.
- [ ] **Step 2: Source and mapper.**
- [ ] **Step 3: The step.**
- [ ] **Step 4: Run and look.** The Groups list and one group against Lasso's; the fingerprint equal.
- [ ] **Step 5: Commit** — "Migrate Lasso's groups and their order".

---

### Task 26L: Amazon — `wp affibloom migrate --only=amazon`

**Feature:** F10 (R17; T2, T3, T16, K24, K25, S19; AC19)

**Why:** the per-country tracking IDs decide the tag of every Amazon link, and the cached product rows become the products' own values at cutover.

**Depends on:** 26A; F17 (10F); C1A-C1D, C1I, C1K-C1P (Phase 2); F10 "Needs the user" 3 and 6 answered.

**User action:** `wp affibloom migrate --only=amazon` prints `N tracking IDs, N cached product rows`. Settings → Amazon shows the tracking IDs per country; Creators credentials, if the site has them, show only as "set". Run again: unchanged.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LassoSource::amazon()` (the tracking IDs per country, the cached product rows, the Creators credentials; never the PA-API pair or any secret) | `AmazonStep` |
| `Application\Migration\{AmazonMapper, AmazonStep}`: `plan()`, `run()`, idempotent, `--update`; writes to F17's tracking-ID table (10F) and C1's store | `MigrationService` |

**Files:** the classes above; `tests/Unit/Application/AmazonMapperTest.php`, `tests/Integration/AmazonStepTest.php`; F10 spec AC19.

- [ ] **Step 1: Failing tests.** AC19 (tracking IDs per country; cached rows; no secret read; second run unchanged). Expected red: no `amazon` family.
- [ ] **Step 2: Source and mapper.**
- [ ] **Step 3: The step.**
- [ ] **Step 4: Run and look.** Settings → Amazon; no secret in the output or the CSV; the fingerprint equal.
- [ ] **Step 5: Commit** — "Migrate Lasso's Amazon tracking IDs and product data".

---

### Task 26M: Saved Dashboard views — `wp affibloom migrate --only=views`

**Feature:** F10 (R22; U1; AC20); F38 ON5 and ON8 (the two copies below)

**Why:** an editor's saved Dashboard views are part of their daily work; they must not vanish at cutover.

**Depends on:** 26F; F19 13E (saved views, Phase 2). The filters of G5, F11 and F25 do not exist yet when this task runs (see Cross-phase notes): a view with such a filter keeps its other filters and the unknown one is dropped with a note.

**User action:** `wp affibloom migrate --only=views` prints `N views migrated, N filters dropped`. The Dashboard's saved views list shows them. Run again: unchanged. The same step also copies `lasso_dashboard_tour_done` into `affibloom_tour_done` (F38 ON8: the tour does not start again for an editor who finished it) and the closed education boxes (`link-`, `content-`, `domain-opportunities` = 'hide', F38 ON5) into `affibloom_settings['education_hidden']`, the settled key (it replaces F22's `affibloom_hidden_intros`; Task 38 removes it).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LassoSource::views()` (`lasso_dashboard_saved_views`) | `ViewsStep` |
| `Application\Migration\{ViewsMapper, ViewsStep}`: `plan()`, `run()`, idempotent, `--update`; group IDs mapped through `group_map`; writes `affibloom_saved_views` through 13E's service | `MigrationService` |
| in `ViewsStep`: the copy of `lasso_dashboard_tour_done` to `affibloom_tour_done` and of the closed education boxes to `affibloom_settings['education_hidden']` (`LassoSource::views()` reads both options too) | `MigrationService` |

**Files:** the classes above; `tests/Unit/Application/ViewsMapperTest.php`, `tests/Integration/ViewsStepTest.php`; F10 spec AC20.

- [ ] **Step 1: Failing tests.** AC20 (a view with a group filter; a view with an unknown filter; second run unchanged), the tour flag copied, a closed education box copied and left closed. Expected red: no `views` family.
- [ ] **Step 2: Source and mapper.**
- [ ] **Step 3: The step.**
- [ ] **Step 4: Run and look.** The Dashboard's saved views beside Lasso's; the tour does not start; a closed education box stays closed; the fingerprint equal.
- [ ] **Step 5: Commit** — "Migrate saved Dashboard views".

---

### Task 26G: Cloaked addresses — `wp affibloom migrate --only=slugs`

**Feature:** F10 (R4, R8; P3, K18, K26, K27, S4, C6; AC14)

**Why:** every product's address, the old-address list and the cloaking settings must carry over so the seven cloaked links in this site's posts keep answering at cutover.

**Depends on:** CL1, CL3, CL5 and CL10 (F24, earlier in this phase: the stores and `CloakHandOver`); 26A.

**User action:** `wp affibloom migrate --only=slugs` prints `N slugs adopted, N cloaking flags set, N old addresses, collisions: N`. Afterwards the seven links in posts 5428, 4436 and 4375 answer (F24 AC14). Run again: unchanged.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LassoSource::slugs()` (`post_name`, `link_cloaking`, `lasso_old_slug`, `_wp_old_slug`, `rewrite_slug`, `restrict_prefix`, `lasso_revert.old_uri`) | `SlugsStep` |
| `Application\Migration\{SlugsMapper, SlugsStep}`: `plan()`, `run()`, idempotent, `--update`; hands the results to F24's `CloakHandOver::adopt()` and `adopt_settings()`; collisions are reported | `MigrationService` |

**Files:** the classes above; `tests/Unit/Application/SlugsMapperTest.php`, `tests/Integration/SlugsStepTest.php`; F10 spec AC14; F24 AC7 (old slug) and AC14 rerun.

- [ ] **Step 1: Failing tests.** AC14 (the seven links answer after the step; collisions reported; second run unchanged). Expected red: no `slugs` family.
- [ ] **Step 2: Source and mapper.**
- [ ] **Step 3: The step, through `CloakHandOver`.**
- [ ] **Step 4: Run and look.** `curl -I` on the seven addresses; the fingerprint equal.
- [ ] **Step 5: Commit** — "Keep Lasso's cloaked addresses working".

---

### Task 26H: URL variants — `wp affibloom migrate --only=variants`

**Feature:** F10 (R15; T8; AC15)

**Why:** the URL variants Lasso keeps for a product (F27) must exist on the migrated product, or pages that link to a variant lose it.

**Depends on:** 26A; F27 V1-V5 (just above, this phase); 26G (earlier in this phase).

**User action:** `wp affibloom migrate --only=variants` prints `N variants created for N products`. A product's Variants tab shows them. Run again: unchanged.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LassoSource::variants()` (`lasso_url_variants`) | `VariantsStep` |
| `Application\Migration\{VariantsMapper, VariantsStep}`: `plan()`, `run()`, idempotent, `--update`; writes through F27's `VariantService` | `MigrationService` |

**Files:** the classes above; `tests/Unit/Application/VariantsMapperTest.php`, `tests/Integration/VariantsStepTest.php`; F10 spec AC15.

- [ ] **Step 1: Failing tests.** AC15 (variants created; second run unchanged). Expected red: no `variants` family.
- [ ] **Step 2: Source and mapper.**
- [ ] **Step 3: The step.**
- [ ] **Step 4: Run and look.** A product's Variants tab against Lasso's; the fingerprint equal.
- [ ] **Step 5: Commit** — "Migrate Lasso's URL variants".

---

### Task 26K: Link issues and dismissals — `wp affibloom migrate --only=issues`

**Feature:** F10 (R16; tables A T14 and K30; D12; AC18, AC23)

**Why:** Lasso shows 113 open alerts here (109 out of stock, 3 broken, 1 error); without this task the Alert column
starts empty until Affibloom's own first check, and a notice an editor dismissed would come back.

**Depends on:** 26A (Phase 5: products and the Lasso ID map) and F11's table and recorder: 30A (table), 31A (the
Alert column that shows the result), 31C (the dismissal keys). 30C's recorder for Amazon rows. The command and its
`MigrationService` are Phase 5's (25A).

**User action:** `wp affibloom migrate --only=issues`: `N issues carried over, N dismissals carried over`, and again:
`0 created, N unchanged`. Without F11 installed it prints "waiting for F11" and writes nothing. The Dashboard's Alert
column and the Broken links and Out of stock counts show what Lasso showed, before any Affibloom check has run.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LassoSource::issues()` on `WpLassoSource` (SELECT only; a missing table reads as empty): `lasso_url_issues` rows and the `dismiss_out_of_stock` / `dismiss_broken` meta | `IssuesStep` |
| `Application\Migration\IssueMapper` (pure): Lasso row → F11 issue row | `IssuesStep` |
| `Application\Migration\IssuesStep` (`plan()`, `run()`, idempotent, `--update`) | `MigrationService`, in its fixed family order |

**Files:** create `IssueMapper.php`, `IssuesStep.php`, `tests/Unit/Application/IssueMapperTest.php`,
`tests/Integration/IssuesStepTest.php`; modify `WpLassoSource.php`, `LassoSource.php`, `MigrationService.php`,
`Plugin.php`; spec AC18 (and AC23 through `tools/lasso-fingerprint.sh` before and after).

Mapping (F10 D12, F11 analysis "Data"): `lasso_url_issues.id` → `product_id` through the ID map; `issue_type` (HTTP
status; `000` = out of stock; `200` = fine) → `issue_type`; `issue_slug` → `checked_url`; `detection_date` →
`detection_date`; `issue_resolved` and `issue_resolved_dt` → `resolved`, `resolved_at`; the last failing check
(Lasso overwrites `issue_resolved_dt` on every failing check) → `last_checked_at`; `failures` 0. `is_ignored` and
`total_404_request` are not kept (F11 D3). The dismiss meta → `_affibloom_dismiss_out_of_stock` and
`_affibloom_dismiss_broken`. Rows whose product is not migrated are skipped with a note. The 41 fixed texts are not
copied (D12).

- [ ] **Step 1: Failing tests.** Unit: `IssueMapperTest` over rows from the inventory fixtures (open `000`, open `404`,
  open `400`, resolved `200`). Integration: seed a Lasso data set; `--only=issues` twice (created once, `unchanged` the
  second time); the Alert state of a carried row; a dismissed notice stays dismissed. Expected red: the family reports
  "waiting".
- [ ] **Step 2: Source and mapper** (`LassoSource::issues()`, `IssueMapper`).
- [ ] **Step 3: The step** (`plan()`, `run()`, `--update`; writes through F11's issue repository and the product
  meta; "waiting for F11" when its table is absent).
- [ ] **Step 4: Run and look.** `tools/lasso-fingerprint.sh` before and after (equal); `wp affibloom migrate
  --only=issues`; open the Dashboard: the same 109 out of stock and 3 broken as Lasso, the same dates; a product's page
  shows its bar unless it was dismissed. At 1280 and 375 px.
- [ ] **Step 5: Commit:** "Carry Lasso's link alerts over".

---

### Task 26I: Comparison tables — `wp affibloom migrate --only=tables`

**Feature:** F10 (R14; analysis tables T4 (fields 99989-99994), T6, T9-T12, K12; AC16)

**Why:** Lasso's three comparison tables (all Horizontal, used only by the draft post 9492) must exist in Affibloom with their products, order, badges, columns and values before cutover; the migration carries everything Lasso stores.

**Depends on:** F25 T1-T17 (earlier in this phase: the table store, the builder's write paths, the reader's renderer); 26A, 26E (Phase 5); F26's built-in table fields (99989-99994, Phase 2).

**User action:** `wp affibloom migrate --only=tables` prints one line per table and a summary (here `3 tables created`); run again `0 created, 3 unchanged`. The Tables list shows them and each opens in the builder with its products, columns, badges and values; with `[affibloom type="table" id=…]` the reader's table matches Lasso's. Before F25 exists the family reports "waiting for F25" and writes nothing.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LassoSource::tables()` (the four table tables and their metadata) | `TablesStep` |
| `Application\Migration\TableMapper` (pure: Lasso rows to F25's `Table` objects, IDs mapped through the product map) | `TablesStep` |
| `Application\Migration\TablesStep::plan()`, `run()` (writes through F25's `TableService`; records `table_map` in `OptionMigrationLog`; idempotent; `--update`) | `MigrationService`, `MigrateCommand` |

**Files:** create `TableMapper.php`, `TablesStep.php`; modify `LassoSource.php`, `WpLassoSource.php`; tests `tests/Unit/Application/TableMapperTest.php`, `tests/Integration/TablesStepTest.php` (twice; the three tables of this copy), `tests/E2E/acceptance/F10.spec.ts` (AC16).

- [ ] **Step 1: Failing tests.** AC16: the three tables with their products, order, badges, columns, values; a second run changes nothing; the fingerprint of Lasso's tables equal before and after (AC23). Expected red: the family reports "waiting for F25".
- [ ] **Step 2: Source and mapper.**
- [ ] **Step 3: The step** (through `TableService`, so F25's validation applies).
- [ ] **Step 4: Run and look.** On Local: `--only=tables`; the Tables list; tables 2 and 3 in the builder and in the preview against Lasso's.
- [ ] **Step 5: Commit** — "Migrate Lasso's comparison tables".

---

### Task 26N: Opportunity switches, dismissals and the new features' settings — `wp affibloom migrate --only=opportunities`

**Feature:** F10 (R18; analysis tables T1 `is_opportunity`, T19, T20 `is_ignored`, S5, S6; AC21)

**Why:** each product's Detect Opportunities switch, the opportunities an editor dismissed, the ASIN replacements and the scan scope are Lasso's data; without them dismissed suggestions would return and every product would be re-enabled. **Scope (coordinator, 2026-11-14):** this task also carries the settings keys that F21, F22, F35 and C3 add: Lasso's scan keys (Custom Post Types, ACF fields, performance, Keep Original URL, cron interval), opportunity keys, auto-detect keys and the four Google Analytics keys. Where 26C already imports a key (it reports "waiting for <feature>" until the key exists), 26N re-runs that import for the keys the features of this phase and Phase 5 have since built.

**Depends on:** F21 35A-35C and the F21 settings (Phase 5); F22 O5 (the product switch), O6 (the setting), O7 (dismissals); F35 (C2B/AM3 keys); C3A (GA keys); 26A, 26C (Phase 5). The F22 tasks come later in this phase's order (see Cross-phase notes): until they exist the family reports "waiting for F22" and writes nothing, so it can run in parts.

**User action:** `wp affibloom migrate --only=opportunities` reports, per part, what it set (`N products' switch`, `N dismissed opportunities`, ASIN replacements, scan scope, the settings keys of F21, F22, F35 and C3) or "waiting for <feature>"; running again reports "unchanged". The Dashboard and Link Opportunities then show the same opportunities Lasso's did, with the dismissed ones still gone, and Settings → General shows the imported values.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LassoSource::opportunities()` | `OpportunitiesStep` |
| `Application\Migration\{OpportunityMapper, OpportunitiesStep}` (`plan()`, `run()`, idempotent, `--update`; writes through F22's service, F21's `is_ignored` identity rule, and `SettingsService` for the keys of F21, F22, F35 and C3) | `MigrationService`, `MigrateCommand` |

**Files:** create the mapper and step; modify `LassoSource.php`, `WpLassoSource.php`; tests `tests/Unit/Application/OpportunityMapperTest.php`, `tests/Integration/OpportunitiesStepTest.php`, `tests/E2E/acceptance/F10.spec.ts` (AC21).

- [ ] **Step 1: Failing tests.** AC21: each product's switch, every dismissed opportunity, the replacements and the listed settings keys carried over; a second run changes nothing; Lasso's fingerprint equal (AC23). Expected red: "waiting for F22".
- [ ] **Step 2: Source and mapper.**
- [ ] **Step 3: The step** (the switch and the dismissals after O5-O7 exist; the settings keys as their features exist).
- [ ] **Step 4: Run and look.** On Local: the command; the Dashboard's opportunities tile; Settings → General; a dismissed row stays dismissed after a rebuild of its host.
- [ ] **Step 5: Commit** — "Migrate Lasso's opportunity switches, dismissals and scan settings".

---

### Task 26O: Run from the admin page, in batches

**Feature:** F10 (D13; AC22)

**Why:** staging and production runs without SSH; one request cannot hold 265 records everywhere.

**Depends on:** 25D, 26A-26E.

**User action:** on Migrate from Lasso: a backup reminder with a tick, **Run migration** (and "Update from Lasso"
after a first run). A progress line per family ("Products 75 of 265"); a second tab: "A migration is already
running."; a cut-off run continues on the next press; then the result and the report link. The Dashboard's
"Import from Lasso" card no longer exists.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Migration\MigrationRunner::batch()` (25 items, cursor, lock `affibloom_migration_lock`) | the route |
| `Rest\MigrationController` `POST /migration/run` (SETTINGS) | `migrate.js` |
| `assets/admin/migrate.js` | the page |
| removal of `LassoImportController`, `LassoImportService`, `ImportResult`, the Dashboard card and its notices | — |

**Files:** the above; `src/Plugin.php`, `templates/admin/dashboard.php`, `Notice`; F10 spec AC22; unit tests for the
runner's cursor and lock; the import's tests removed with it.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Runner and route.**
- [ ] **Step 3: The page's script.**
- [ ] **Step 4: Remove the one-time import.**
- [ ] **Step 5: Run and look** (two tabs; kill a request mid-run).
- [ ] **Step 6: Commit** — "Run the Lasso migration from the admin, in batches".

---

### Task 26P: Check the migrated products against the baselines

**Feature:** F10 (AC24)

**Why:** the migration is only trustworthy if every migrated product gives the values Lasso's box gives; this tool shows each difference.

**Depends on:** 26A, 26C, 26D, 26E; Tasks 18A-22E (the box builder).

**User action:** `bash tools/local-wp.sh eval-file tools/compare-migration.php`: per baseline box, the differences
(field, Lasso's value, ours), and `250 boxes: N equal, N different`. Each difference is fixed at its cause or
written in `docs/parity.md`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `tools/compare-migration.php`, `docs/parity.md`, `tools/README.md` | `bash tools/local-wp.sh eval-file tools/compare-migration.php`; Task 29A |

**Files:** `tools/compare-migration.php`, `docs/parity.md`, `tools/README.md`.

- [ ] **Step 1: The tool** (red: differences listed).
- [ ] **Step 2: Fix causes, with a test each.**
- [ ] **Step 3: Run and look.** `bash tools/local-wp.sh eval-file tools/compare-migration.php` ends `250 boxes: N equal, N different`; every difference is fixed or written in `docs/parity.md`.
- [ ] **Step 4: Commit** — "Prove every migrated product gives Lasso's box values".

---

## Tasks: F10 — Lasso's content becomes Affibloom's (decisions A1 and A2 reversed, 2026-11-17)

> Written 2026-11-17 from the archived Tasks 28A-28E, 27A-27I and H3-H4 (`archive/phase-5-superseded.md`) and
> F31's requirements (`docs/features/F31/requirements.md`: the meaning of every `[lasso]` attribute, R- and A-rows).
> The same rules that were to render `[lasso]` now decide what each item becomes. Order: LM2-LM9, after 26P.

### Task LM2: The conversion's dry run — every Lasso item in content

**Feature:** F10 (D2, D16; AC25); F31 (the R- and A-rows, as conversion rules)

**Why:** before any post is touched, every Lasso item on the site is listed with what it becomes, or why it cannot be
converted yet.

**Depends on:** 26A-26P (every family migrated, so each `id`, group and table has its Affibloom twin); Affibloom's block
(Phase 4) and `[affibloom]` (Phase 3) attribute sets, through the API.

**User action:** `wp affibloom-lasso convert --dry-run [--post=<id>]` lists, per post (every status but revisions and
autosaves; posts, pages, reusable blocks and the post types Lasso scanned), each `affiliate-plugin/lasso` block,
`[lasso …]` and `[lasso-attr …]`, and what it becomes (`affibloom/product-box` with its overrides, or `[affibloom …]`)
or why it stays (an unknown product, an attribute with no Affibloom equivalent, a display type not mapped). Summary:
"N posts, N items: N to convert, N blocked." Nothing is written. On this copy: 34 published posts with 250 shortcodes
in 34 blocks, 2 bare shortcodes, and the draft 9492's tables.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Conversion\{LassoItem, LassoAttributes, AffibloomTarget, Blocked}` (pure: parse an item; map each attribute to `[affibloom]`'s attribute or the block's override: `id` and `link_id` through the ID map, `ref` and `rel` dropped, `lasso-br-code` to Affibloom's code (LC2), `title_type` h1-h6, `compact="hide"` dropped as the no-op it is (F31 N3), Lasso's render-time repairs of 27G applied to the values) | the converter |
| `Application\Conversion\ContentConverter::plan()`; `Infrastructure\WordPress\ContentHosts` (the posts and their items) | `ConvertCommand` |
| `Interface\Cli\ConvertCommand` (`wp affibloom-lasso convert --dry-run`) | WP-CLI |

**Files:** the above; unit tests per attribute family (fixtures from Affibloom's `tests/fixtures/inventory/boxes.json`:
the 11 attributes in use and their 44 values with `'`); F10 AC25.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: The domain mapping.**
- [ ] **Step 3: The converter's plan and the command.**
- [ ] **Step 4: Run and look.** The 34 posts: every item to convert, or blocked with a reason that can be acted on.
- [ ] **Step 5: Commit** — "Report how each Lasso box becomes Affibloom's".

---

### Task LM3: Convert Lasso's blocks, keep a copy, revert

**Feature:** F10 (D16, D17; AC3)

**Why:** the conversion changes posts, so each changed post must be put back exactly if the cutover is rolled back.

**Depends on:** LM2.

**User action:** without `--dry-run`, each post's convertible `affiliate-plugin/lasso` blocks become
`affibloom/product-box` blocks; the post keeps `_affibloom_lasso_pre_conversion` (its content before) and a revision.
`--revert [--post=<id>] [--force]` restores the stored content exactly and deletes the copy; a post edited since its
conversion is refused unless `--force`. A converted guide shows the same boxes (the full proof is 29A-29D).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `ContentConverter::run(), revert()`; the lock `affibloom_lasso_convert_lock` | `ConvertCommand`, LM8 |
| `Infrastructure\WordPress\ContentChangeWriter` (`wp_update_post` with a revision, the copy meta, `restore()`) | the converter, LM4-LM7 |
| the block's stored form through Affibloom's API (its attributes, `serialize_block`) | the converter |

**Files:** the above; `tests/Integration/BlockConversionTest.php`; F10 AC3.

- [ ] **Step 1: Failing tests.** Convert, revert byte-equal, refuse after an edit, `--force`.
- [ ] **Step 2: Write and revert.**
- [ ] **Step 3: Run and look.** One guide in the editor before and after (no invalid-block notice); the front page
  unchanged at 1280 px.
- [ ] **Step 4: Commit** — "Convert Lasso blocks to Affibloom's, with a copy to go back to".

---

### Task LM4: Bare shortcodes, widgets and Elementor data

**Feature:** F10 (AC26); F31 (`origin="widget"`, the widget rows of 27H)

**Why:** `[lasso …]` also sits outside Lasso's block: in shortcode blocks, paragraphs, classic content, widgets and
Elementor's data. None may be left.

**Depends on:** LM3.

**User action:** a `[lasso …]` in a shortcode block becomes the product-box block; in a paragraph, classic content, a
text or block widget, or `_elementor_data` (the shortcode text and Lasso's `lasso_shortcode` widget) it becomes
`[affibloom …]` (Affibloom's Elementor widget for the widget, ED2); `[lasso-attr …]` becomes Affibloom's field display,
or is reported. Each keeps a copy (widgets and Elementor data too) and reverts as LM3. On this copy: 2 bare, none in
widgets or Elementor, no `[lasso-attr]`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the shortcode-block, bare-shortcode, widget and Elementor rules in `ContentConverter`; `WidgetHosts`, `ElementorHosts` | `ConvertCommand` |

**Files:** the converter and its tests; F10 AC26.

- [ ] **Step 1: Failing tests,** one per host.
- [ ] **Step 2: The rules and the hosts.**
- [ ] **Step 3: Run and look** (the 2 on this site).
- [ ] **Step 4: Commit** — "Convert bare Lasso shortcodes, widgets and Elementor data".

---

### Task LM5: The other display types, groups and tables

**Feature:** F10 (AC27; AC16 with 26I); F31's display rows (27B-27F's rules)

**Why:** Lasso's other displays must become Affibloom's displays, not stay as `[lasso]`.

**Depends on:** LM3; Affibloom's display types (Phase 3, DT1-DT9), group displays (F23) and tables (F25, Phase 7);
26F, 26I.

**User action:** `type="button|image|link|grid|list|gallery|sitestripe|table"` become Affibloom's display of that type
(the block's display type, or `[affibloom type=…]`) with the mapped group or table; `amazon_url` and `geni_url` (27C)
become the product found by its link, else are reported; `prompt` (27F) becomes Affibloom's AI text display with
Lasso's cached text when it has one, else is reported. On this copy: none, except the draft 9492's two
`type="table"` (the table "Volleyball Shoe", migrated by 26I).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| one mapping per display type in `Domain\Conversion` | `ContentConverter` |

**Files:** the mappings, a test per type; F10 AC27.

- [ ] **Step 1: Failing tests, per type.**
- [ ] **Step 2: The mappings.**
- [ ] **Step 3: Run and look** (the draft 9492 at 1280 px).
- [ ] **Step 4: Commit** — "Convert Lasso's other display types, groups and tables".

---

### Task LM6: Lasso's stamps in content

**Feature:** F34 (R18 in part, D2, D4, D14; AC6); F21 (R3, R37)

**Why:** Lasso wrote `data-lasso-*` attributes into the links of 180 posts (2,790 `data-lasso-id`, 698
`data-lasso-lid` and `data-lasso-name` on this copy). Left in place they are Lasso's leftovers, and the block editor
flags image and button blocks that carry them (ED5 is gone from Affibloom, LC2).

**Depends on:** LM3 (the writer and the copy); Affibloom's index (Phase 5, 35A) through the API.

**User action:** `wp affibloom-lasso stamps --dry-run` prints, per post, the attributes to remove and the ties to keep;
without `--dry-run` it removes `data-lasso-id`, `data-lasso-lid`, `data-lasso-name`, Kadence `singlebtn`'s
`data-lasso-id` and AAWP's `lasso-ll-do-not-delete-*` class (none here), every other byte unchanged. Before removing a
`data-lasso-lid`, it asks Affibloom's index which product the link resolves to by itself; when that is not the stamped
product, it sets the row's `assigned_product_id` to it (kept across a rescan), so the link stays tied. Copy and revert
as LM3; revert also clears the assignments it made. Afterwards those posts open in the editor without "This block
contains unexpected or invalid content".

*Note (2026-11-18, from Phase 5):* Lasso's `lasso-scan-ignore` class also becomes `affibloom-scan-ignore` here, the
class Affibloom's scanner skips (35A/LC2), so the links an editor kept out of Lasso's scan stay out of Affibloom's.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Conversion\LassoStamps` (pure: the counts and the cleaned HTML; JSON-encoded stamps handled) | `StampCleaner` |
| `Application\Conversion\StampCleaner::plan(), run(), revert()`; `Interface\Cli\StampsCommand` | WP-CLI, LM8 |
| Affibloom's API: the index's resolve for a link and the `assigned_product_id` write | `StampCleaner` |

**Files:** the above; unit `LassoStampsTest` (fixtures from three guides: GenerateBlocks buttons, a reusable block,
inline SVG); integration; F34 AC6; the former F4 AC20 as this task's E2E (a cleaned guide opens without an
invalid-block notice).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Domain, cleaner, ties.**
- [ ] **Step 3: The command.**
- [ ] **Step 4: Run and look.** Three guides: the front end unchanged, the editor without notices, the index's tied
  count unchanged.
- [ ] **Step 5: Commit** — "Remove Lasso's stamps from content, keeping its ties".

---

### Task LM7: The site's Lasso CSS rules

**Feature:** ADR-6 (C1, 2026-11-14: the site's `.lasso-*` Additional CSS rules translated at cutover)

**Why:** the site's Additional CSS styles Lasso's class names (33 rules on this copy); Affibloom's box uses its own, so
the rules must follow, or the styling is lost.

**Depends on:** LM3 (the copy and revert pattern); the class map of Affibloom's box (Phase 3 templates).

**User action:** `wp affibloom-lasso css --dry-run` prints each `.lasso-*` rule of the active theme's Additional CSS
with its Affibloom selector, and the selectors with no Affibloom equivalent; without `--dry-run` it writes the
translated CSS (a copy of the old CSS kept; `--revert`). The guides look the same (29B).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Conversion\LassoCssMap` (Lasso class → Affibloom class), `Application\Conversion\CssTranslator`, `Interface\Cli\CssCommand` | WP-CLI, LM8 |

**Files:** the above; unit tests (every one of the 33 rules); integration (the `custom_css` post).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Map and translator.**
- [ ] **Step 3: The command.**
- [ ] **Step 4: Run and look** (a guide whose box the CSS changes, at 1280 and 375 px).
- [ ] **Step 5: Commit** — "Translate the site's Lasso CSS rules to Affibloom's classes".

---

### Task LM8: The conversion from the admin page

**Feature:** F10 (AC28); F34 (AC7)

**Why:** staging has no SSH (F10 D13): the conversion, the stamps and the CSS must run, and revert, from wp-admin.

**Depends on:** 26O (the page and its batch pattern), LM3-LM7.

**User action:** "Migrate from Lasso" gains a **Content** card: the dry-run figures (boxes, stamps, CSS rules,
blocked items with links), a required tick "I have a database backup from today", **Convert** (one post per request,
"N of M posts", resumes after a reload), then the converted posts' list with **Revert** per post and **Revert All**;
all behind the migration lock ("Another migration or conversion is running.").

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `POST /affibloom-lasso/v1/convert`, `/convert/revert` (`cursor`, `backup_confirmed`) | `migrate.js` |
| the Content card and list in `templates/admin/migrate.php`; the run in `assets/admin/migrate.js` | the page |

**Files:** the above; F10 AC28, F34 AC7.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Routes and batches.**
- [ ] **Step 3: Card, list and run.**
- [ ] **Step 4: Run and look** (1280 and 375 px; a reload mid-run resumes).
- [ ] **Step 5: Commit** — "Convert and revert Lasso content from the admin".

---

### Task LM9: Nothing of Lasso is left

**Feature:** F10 (the user's rule of 2026-11-17: no Lasso shortcode or block left behind)

**Why:** the plugin may be removed only when the site's content holds nothing that needs Lasso or the migrator.

**Depends on:** LM3-LM7.

**User action:** `wp affibloom-lasso verify` and the page's **Check** card count `[lasso`, `[lasso-attr`,
`wp:affiliate-plugin/lasso`, `data-lasso-` and Lasso's class names in post content of every status (revisions aside),
reusable blocks, widgets, Elementor data and the ACF fields Affibloom scans (35D), and `.lasso-` in Additional CSS;
and check that every Affibloom box in content names an existing product. All zero: "Nothing of Lasso is left in your
content." Else the list, with edit links.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Conversion\LeftoverCheck`, `Interface\Cli\VerifyCommand`, the Check card | WP-CLI, the page, LM10 |

**Files:** the above; integration test (one leftover per host is found); E2E (the card's two states).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: The check.**
- [ ] **Step 3: Command and card.**
- [ ] **Step 4: Run and look.** On Local after LM3-LM7: all zero.
- [ ] **Step 5: Commit** — "Check that no Lasso content is left".

---

## Tasks: F10 — parity, the rollback drill and removal

### Task 29A: Parity — every box on every guide

> **Changed 2026-11-17 (deviation 146):** compares Lasso's baselines with the site after the migration **and the content
> conversion** (LM3-LM7), with Lasso off and this plugin deactivated, so Affibloom alone draws every box. Depends on
> LM3-LM7 instead of 27A. The suite's modes: `lasso` (baselines), `converted` (this task), `reverted` (29D).

**Feature:** F10 (AC2, first half)

**Why:** the first half of F10's exit: with Lasso off, every box on every guide shows what it showed with Lasso on.

**Depends on:** Tasks 18A-22E (the box), 27A; 26A, 26C, 26D, 26E, 26P.

**User action:** `npm run test:e2e -- parity`: resets Local, migrates, switches Lasso off, and for each of the 34
guides compares every box with `baselines/boxes.json` (title, badge, description, image, rating, Prime, disclosure,
each button's href, text, rel, target), in page order.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `tests/Visual/parity.spec.ts` (the values part), `docs/parity.md` | `npm run test:e2e -- parity` |

**Files:** `tests/Visual/parity.spec.ts` (the values part), `docs/parity.md`.

- [ ] **Step 1: The suite, red.** `tests/Visual/parity.spec.ts` (values part): reset Local, migrate, Lasso off, the 34 guides against `baselines/boxes.json`.
- [ ] **Step 2: Fix causes.** A test for each cause, or the difference written in `docs/parity.md`.
- [ ] **Step 3: Run and look.** `npm run test:e2e -- parity`: all 34 guides pass; open one guide beside its baseline.
- [ ] **Step 4: Commit** — "Prove every box matches Lasso with Lasso off".

---

### Task 29B: Parity — whole pages

> **Changed 2026-11-17 (deviation 146):** the screenshots are taken in the `converted` mode of 29A.

**Feature:** F10 (AC2, second half)

**Why:** equal boxes are not yet equal pages; layout, fonts and order must match too.

**Depends on:** 29A; the baseline screenshots re-captured (they are not in git).

**User action:** `npm run test:e2e -- parity` also takes full-page screenshots of the 34 guides at 1280 and 390 px with Lasso off and compares them with the baselines within the agreed tolerance; the diffs are written to `test-results/parity`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the page-screenshot part of `tests/Visual/parity.spec.ts` | `npm run test:e2e -- parity` |

**Files:** `tests/Visual/parity.spec.ts`, `docs/parity.md`.

- [ ] **Step 1: The suite part, red.**
- [ ] **Step 2: Fix causes, or record the difference in `docs/parity.md`.**
- [ ] **Step 3: Run and look.** `npm run test:e2e -- parity`; open the diffs in `test-results/parity`.
- [ ] **Step 4: Commit** — "Prove every guide looks the same with Lasso off".

---

### Task 29C: Parity — every link, and no call to Lasso

**Feature:** F10 (AC29, AC30)

**Why:** Phase 5's 29A and 29B compare boxes and whole pages; this proves the other half of "nothing changes for readers": every `<a href>` in each guide's article goes where it went, the cloaked addresses still redirect, and nothing calls Lasso's services. It stays last in the order because it uses everything built before it.

**Depends on:** 29A (Phase 5); F24 and 26G (cloaked addresses, Phase 5); F21's index and render-time attributes (35A-35C, 35H, now Phase 5).

**User action:** `npm run test:e2e -- parity` now also compares, for each of the 34 guides, every `<a href>` of the article (the 676 in-content links) with `tests/fixtures/inventory/links.json` and a new all-links baseline captured with Lasso on, before the reset; each own-site cloaked address is requested and its redirect compared; no request goes to `codedrink.com`, `lasso.link` or `getlasso.co`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the all-links capture tool (`tools/`, documented in `tools/README.md`) and its baseline `tests/fixtures/baselines/links-all.json` | the parity suite |
| the links part of `tests/Visual/parity.spec.ts` (hrefs, redirects, request log) | `npm run test:e2e -- parity` |

**Files:** create the capture tool and baseline; modify `tests/Visual/parity.spec.ts`, `docs/parity.md`, `tools/README.md`.

- [ ] **Step 1: Failing tests.** The suite's links part on the 34 guides with Lasso off; red until the cloaked addresses and the render-time attributes agree with the baseline.
- [ ] **Step 2: The capture tool** (run once with Lasso on, before the reset) and the baseline.
- [ ] **Step 3: Fix causes** (each with a test at the owning feature), or record in `docs/parity.md`.
- [ ] **Step 4: Run and look.** The whole parity suite twice; no request to Lasso's hosts in the log.
- [ ] **Step 5: Commit** — "Prove every link still goes where it went".

---

### Task 29D: Parity — converted, and reverted

> **Restored 2026-11-17 (deviation 146):** the conversion is built again (LM3-LM7), so its proof is too.

**Feature:** F10 (AC31)

**Why:** the conversion must not change what readers see, and reverting it must give the original pages back.

**Depends on:** LM3-LM7, 29A, 29B, 29C.

**User action:** `PARITY_MODE=converted npm run test:e2e -- parity` (content converted, Lasso off, the migrator
deactivated) and `PARITY_MODE=reverted npm run test:e2e -- parity` (content reverted, Lasso on) run 29A-29C; both equal
the baselines, or only the differences accepted in `docs/parity.md`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the `PARITY_MODE` switch in `tests/Visual/parity.spec.ts` (converts or reverts through the migrator's command first) | `npm run test:e2e -- parity` |

**Files:** `tests/Visual/parity.spec.ts`, `docs/parity.md`.

- [ ] **Step 1: The two modes, red.**
- [ ] **Step 2: Fix causes, or record the difference in `docs/parity.md`.**
- [ ] **Step 3: Run and look.** Both modes pass on Local.
- [ ] **Step 4: Commit** — "Prove parity survives conversion and revert".

---

### Task 29E: Rollback drill

> **Changed 2026-11-17 (deviation 146):** going back now has two steps: revert the content (LM8's Revert All, or
> `wp affibloom-lasso convert --revert`), then switch Affibloom off and Lasso on. The drill checks both: after the
> revert every post is byte-equal to its copy, and Lasso's fingerprint is unchanged. `docs/cutover/rollback.md` gives
> the two steps in that order.

**Feature:** F10 (D17; AC32)

**Why:** going back to Lasso must be rehearsed before production (Task 41) depends on it.

**Depends on:** 29A-29B.

**User action:** take Lasso's fingerprint; migrate; switch Affibloom off and Lasso on; check the guides against the baselines; take the fingerprint again: equal. The steps are written up as `docs/cutover/rollback.md` for Tasks 40-41.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `docs/cutover/rollback.md` | the people running Tasks 40-41 |

**Files:** `docs/cutover/rollback.md`; `tools/lasso-fingerprint.sh` (26A) is used, not changed.

- [ ] **Step 1: The drill, written as a checklist that fails on a differing fingerprint.**
- [ ] **Step 2: Run it on Local; fix causes.**
- [ ] **Step 3: Run and look.** The guides against the baselines with Lasso back on; the two fingerprints equal.
- [ ] **Step 4: Commit** — "Rehearse going back to Lasso".

---

### Task LM10: Finish the migration and remove the plugin

**Feature:** F10 (the user's rule of 2026-11-17: the helper can be safely removed)

**Why:** once the cutover is signed off, the migrator's copies, map and options are dead weight, and the site must run
on Affibloom alone.

**Depends on:** LM9, 29A-29E. On production it runs in Task 42, after the monitoring window and the client's sign-off.

**User action:** **Finish migration** on the page (and `wp affibloom-lasso finish`): refused unless LM9's check is
clean ("Convert or remove the items listed under Check first."); a dialog says that after this the way back is the
database backup only; Finish deletes the conversion copies, the ID map meta `_affibloom_lasso_id`, the option
`affibloom_lasso_import`, and the migrator's options, logs and locks, and asks to deactivate and delete the plugin.
Uninstall removes anything left. Lasso's own data is not touched (Task 42 handles it).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Finish`, `Interface\Cli\FinishCommand`, the Finish card; `uninstall.php` | the page, WP-CLI, WordPress |

**Files:** the above; integration test (nothing of the migrator left in options or meta); E2E: after Finish and
deletion, `PARITY_MODE=converted` passes with Affibloom alone.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Finish and uninstall.**
- [ ] **Step 3: Run and look.** On Local: Finish, delete the plugin, three guides and the Dashboard at 1280 px.
- [ ] **Step 4: Commit** — "Finish the migration and leave nothing behind"; tag the migrator's release.

---

## Exit check

The phase is done when all of these hold on the local copy (then staging, Task 40), and the user has signed it off:

- **Data:** the dry run accounts for all 265 records and every Lasso table (AC1, AC4-AC6); every family is migrated,
  idempotent and updatable (AC7-AC22, with AC16 26I, AC18 26K, AC21 26N); Lasso's fingerprint is unchanged by every write
  task (AC23); every migrated product gives the baseline values (AC24).
- **Content:** LM9 reports nothing of Lasso left; the conversion and its revert (AC3, AC25-AC28; F34 AC6, AC7); every
  guide matches Lasso's baselines with the content converted, Lasso off and the migrator deactivated (AC2: 29A, 29B;
  AC29-AC30: 29C), and again after reverting with Lasso on (AC31: 29D).
- **Rollback and removal:** the drill passes (AC32, 29E); on a copy of Local, Finish and deletion leave Affibloom alone
  passing the `converted` parity (LM10).
- **Review:** Phase 8's security checklist (Task 37A) passes over the migrator's routes, commands and writes; its
  `composer lint`, `analyse` and `test:unit` pass; its release zip installs on a copy beside Affibloom's release.

Sign-off: the user has seen the migration report, the conversion report, LM9's check and the parity result, and signs
the phase off. Phase 9 does not start before that.

## Coverage

"(Phase n)" marks an Affibloom feature outside this phase.

### F10

| Requirement | Task |
|---|---|
| R1-R3 (what exists, what stays, schema drift) | 25C (inventory), 26A (fingerprint) |
| R4-R6, R9, R10 | 25A, 26A |
| R7 | 26A (edge case 3; not applied to migrated records) |
| R8 | 26A (product meta), 26F (groups), 26G (slugs), 26N (link updates, Phase 7) |
| R11 | not built: decision E1 (26J dropped) |
| R12 | 26E |
| R13 | 26F |
| R14 | 26I (Phase 7) |
| R15 | 26H |
| R16 | 26K (Phase 6) |
| R17 | 26L |
| R18 | 26N (Phase 7) |
| R19 | 26G (`old_uri`); F33, F34 (not F10's) |
| R20, R21 | 25B, 26C, 26D |
| R22 | 26M |
| R23 | 25C (left behind) |
| AC1 | 25A |
| AC2 | 29A, 29B |
| AC3 | not built: decision A1 |
| AC4 | 25B |
| AC5 | 25C |
| AC6 | 25D |
| AC7, AC8 | 26A |
| AC9 | 26B |
| AC10 | 26C |
| AC11 | 26D |
| AC12 | 26E |
| AC13 | 26F |
| AC14 | 26G |
| AC15 | 26H |
| AC16 | 26I (Phase 7) |
| AC17 | not built: decision E1 (26J dropped) |
| AC18 | 26K (Phase 6) |
| AC19 | 26L |
| AC20 | 26M |
| AC21 | 26N (Phase 7) |
| AC22 | 26O |
| AC23 | 26A (then every write task) |
| AC24 | 26P |
| AC25 | not built: decision A1 |
| AC26 | not built: decision A1 |
| AC27 | not built: decision A1 |
| AC28 | not built: decision A1 |
| AC29, AC30 | 29C (Phase 7) |
| AC31 | not built: decision A1 (29D dropped) |
| AC32 | 29E |

Unowned by F10 (the draft's own list, homes named): `lasso_revert` and the importer's options (F33); `lasso_post_content_history` (F34: not built, F34 N2); the settings of table F marked F9 whose controls do not exist yet (F9's sweep, Phase 2); dropping Lasso's tables after cutover (Task 42). **UNOWNED:** none beyond these named homes.

**Changed 2026-11-17 (deviation 146):** the F10 table above was written for Phase 5. Its "not built (decision A1)"
rows are built now: AC3 and D16-D17 by LM3, AC25 by LM2, AC26 by LM4, AC27 by LM5, AC28 by LM8, AC31 by 29D. Rows
naming 27A-27I or ED6 are LM2-LM5's rules.

### F31 (legacy part), F34 and F4 ED6

| R / AC | Task |
|---|---|
| F31 `[lasso]` and `[lasso-attr]` rows (R1-R31, A1-A41, AC15-AC28, AC30, AC31) | LM2-LM5 (as conversion rules: each attribute's meaning decides its Affibloom target) |
| F31 the competitors' rows (27X1-27X8; AC29) | dropped (deviation 146) |
| F34 R18, AC6 | LM6 |
| F34 AC7 | LM8 |
| F34 AC1-AC5, AC8 (History) | not built (F34 N1, N2); the migrator's page lists the converted posts while installed |
| F4 R50, AC21 (ED6) | not built: no `affiliate-plugin/lasso` block is left (LM3) |
| ADR-6's `.lasso-*` Additional CSS | LM7 |
