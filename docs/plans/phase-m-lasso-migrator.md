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

- [x] Task 25A: The dry run — `wp affibloom migrate --dry-run`, products
- [x] Task 25B: The dry run — settings
- [x] Task 25C: The dry run — every other family, and what is left behind
- [x] Task 25D: The report on an admin page
- [x] Task 26A: Products — `wp affibloom migrate --only=products`
- [x] Task 26B: Products again — `--update`
- [x] Task 26C: Settings — General and Amazon
- [x] Task 26D: Settings — Display for Single, Grid and List
- [x] Task 26E: Custom fields — definitions and values
- [x] Task 26F: Groups — `wp affibloom migrate --only=groups`
- [x] Task 26L: Amazon — `wp affibloom migrate --only=amazon`
- [x] Task 26M: Saved Dashboard views — `wp affibloom migrate --only=views`
- [x] Task 26G: Cloaked addresses — `wp affibloom migrate --only=slugs`
- [x] Task 26H: URL variants — `wp affibloom migrate --only=variants`
- [x] Task 26K: Link issues and dismissals — `wp affibloom migrate --only=issues` (from Phase 6)
- [x] Task 26I: Comparison tables — `wp affibloom migrate --only=tables` (from Phase 7)
- [x] Task 26N: Opportunity switches, dismissals and the new features' settings — `--only=opportunities` (from Phase 7)
- [x] Task 26O: Run from the admin page, in batches
- [x] Task 26P: Check the migrated products against the baselines
- [x] Task 26Q: Lasso's original links — `--only=locations` (added 2026-11-23, deviation 156; after GF6)

**F10 — Lasso's content becomes Affibloom's**

- [x] Task LM2: The conversion's dry run — every Lasso item in content
- [x] Task LM3: Convert Lasso's blocks, keep a copy, revert
- [x] Task LM4: Bare shortcodes, widgets and Elementor data
- [x] Task LM5: The other display types, groups and tables
- [x] Task LM6: Lasso's stamps in content
- [x] Task LM6A: The blocks Lasso's scan rewrote (deviation 160)
- [x] Task LM7: The site's Lasso CSS rules
- [x] Task LM8: The conversion from the admin page
- [x] Task LM9: Nothing of Lasso is left

**F10 — parity, the rollback drill and removal**

- [x] Task 29A: Parity — every box on every guide
- [x] Task 29B: Parity — whole pages
- [x] Task 29C: Parity — every link, and no call to Lasso (from Phase 7)
- [x] Task 29D: Parity — converted, and reverted
- [x] Task 29E: Rollback drill
- [x] Task LM10: Finish the migration and remove the plugin

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

- [x] **Step 1: Failing tests.** AC1 (the command's summary names 263 and 265 and the four actions). Unit: mapper rows of R9-R10 not yet covered; a report's sums; statuses. Expected red: no `affibloom` command.
- [x] **Step 2: The command and the report.**
- [x] **Step 3: `ProductsStep::plan()`, the ID map, the source's statuses.**
- [x] **Step 4: Run and look.** The summary above on Local; the CSV opens; `git status` of the database: nothing written (row counts before and after).
- [x] **Step 5: Commit** — "Report what the Lasso migration will do to every record".

*Note (2026-11-22):* built in the migrator as `wp affibloom-lasso migrate --dry-run [--only] [--report=<csv>]`:
`Domain\Lasso\{LassoRecord, LassoSource}`, `Domain\Migration\{Family, Action, MigrationLine, MigrationReport,
MigratedProducts, ProductRules}`, `Application\{Step, ProductsStep, MigrationService, LassoRecordMapper}`,
`Infrastructure\Lasso\{WpLassoSource, LassoIdMap, LassoInstall}`, `Infrastructure\Affibloom\ApiProductRules`; the command
registers itself (no `CliCommands` for one command). Errors (D19) come from Affibloom's own Save rules through the new
`Api::check_product()` (`ProductService::check()`, sharing `update()`'s checks). An empty title takes the link's host;
Lasso's AAWP feature descriptions are not used. `php-stubs/wp-cli-stubs` does not install beside WordPress 7's stubs,
so PHPStan reads `tests/stubs/wp-cli.php`, as Affibloom does. Local: `265 records: 2 to create, 9 skipped, 1 to retire,
1 to trash, 252 already migrated, 0 errors (263 published)`; the 252 are the one-time import's products (169 published,
83 in the trash, D7); the plan's "254 to create" is a site without that import. Row counts and checksums of
`wp_posts`/`wp_postmeta` unchanged; the CSV has 265 lines. AC1 asserts the totals, not the create count.

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

- [x] **Step 1: Failing tests.**
- [x] **Step 2: `LassoSettings` and the source.**
- [x] **Step 3: The mappers and steps.**
- [x] **Step 4: Run and look** (no secret value in the output or the CSV).
- [x] **Step 5: Commit** — "Report where every Lasso setting goes".

*Note (2026-11-22):* in the migrator: `Domain\Lasso\LassoSettings` (Lasso's defaults and R20's fallbacks),
`Application\{SettingsMapper, DisplayMapper, SettingsStep, ValueText}`, `LassoSource::settings()`; one `SettingsStep` class
serves both families. Targets are Affibloom's setting names (F9 analysis); the verdicts of analysis table F are brought up
to date now every feature is built: nothing is "waiting". Left with a reason: the 7 secrets (their values dropped as the
option is read, D10), brag mode (deviation 95), `enable_history` (F34 N1), `enable_nofollow2`/`open_new_tab2` (no
Affibloom setting), cloud, screen-state and unused keys. An empty or unknown `lasso_permission` reads as administrators
only, as Lasso fails closed. Display: one line per type and field, the columns, and the flat keys and upgrade flag left
with why. A summary per family. Local: `settings: 86 keys: 58 to import, 28 left, 0 errors`, `display: 28 keys: 16 to
import, 12 left` (102 stored keys and the blacklist, which is flagged for listing the site's own tracking ID). The
migrator's tests carry Lasso's key names only, no site values (its repository is public). Found and fixed: JSON output
ran into the summary line. AC4 passes.

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

- [x] **Step 1: Failing tests.**
- [x] **Step 2: Inventory and verdicts.**
- [x] **Step 3: Run and look** (29 tables accounted for).
- [x] **Step 4: Commit** — "Account for every Lasso table and option in the dry run".

*Note (2026-11-22):* in the migrator: `Domain\Lasso\LassoInventory` (`LassoSource::inventory()`, COUNT queries only),
`Domain\Migration\DataItem`, `Application\{LeftBehind, WaitingStep}`, `Action::Waiting`. Each family still to come has a
`WaitingStep` (found N; the task that moves it), which its task replaces. The closing list, printed only when every
family is planned, gives every Lasso table and the other kinds of data (posts, old slugs, terms, user and post meta,
option groups) a verdict: the family that moves it, or why it is left; an unknown table is an error and exit 1. Clicks
are not a family (E1). Local: all 29 tables have a verdict; `Left behind: 18 of 39 kinds of Lasso data (16508 rows)`.
AC5 passes.

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

- [x] **Step 1: Failing test.**
- [x] **Step 2: Screen and template.**
- [x] **Step 3: The download.**
- [x] **Step 4: Run and look.**
- [x] **Step 5: Commit** — "Show the migration report in the admin".

*Note (2026-11-22):* the page is the migrator's (`admin.php?page=affibloom-lasso-migrator`, LM1), not a submenu in
Affibloom's `AdminMenu`: `MigrationScreen` prints `templates/admin/migrate.php` with `assets/admin/migrate.css` on
Affibloom's tokens (a brand-25 summary card with Download report (CSV), a card per family with its numbers or "Comes
with Task 26X" and what the site holds, error lines when any, then Lasso's data with its verdict pills; stacked rows on
a phone). The download is admin-post `affibloom_lasso_migration_report` (`ReportDownload`, nonce, the page's
capability), sharing `Interface\ReportCsv` with `--report`. Affibloom's API gained `add_page( …, $listed )` (the entry
hides without Lasso's data; the address says "No Lasso data found.") and `capability()`. Checked at 1280 px in Brave and
375 px headless (no sideways scroll); the CSV has 427 rows; a forged nonce gets 403. AC6 and LM1's E2E pass.

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

- [x] **Step 1: Failing tests.**
- [x] **Step 2: The fingerprint tool.**
- [x] **Step 3: `run()`, the log.**
- [x] **Step 4: Run and look** (Dashboard: 254 products in Lasso's order; a retired one opens its target; the fingerprint equal).
- [x] **Step 5: Commit** — "Migrate Lasso's products, once".

*Note (2026-11-22):* `wp affibloom-lasso migrate --only=products` (the run is the command without `--dry-run`).
Affibloom's API gained `import_product()` (`ProductService::import()`: Save Changes' rules, the link stored as given, no
request, no merge, no tag rule, no Amazon fetch; an Amazon link makes an Amazon product), `product_fields()`,
`update_product()`, `retire_product()`, `trash_product()`, `date_product()` (`Extension\ProductAccess`,
`WpPostDates`). In the migrator: `RunStep`, `ProductsStep::run()` (one decision per record shared with the dry run;
retired records after their targets; a trashed or retired product dated again after its status change, which stamps
"now"), `MigrationService::run()`, `OptionMigrationLog` (`affibloom_lasso_migration`; the old `affibloom_lasso_import`
is not read: adoption goes by `_affibloom_lasso_id`). The migrator writes only its own meta on products:
`_affibloom_lasso_id` and `_affibloom_migrated` (the hashes of what it gave and what Affibloom stored, for 26B).
`tools/lasso-fingerprint.php` (run with `wp eval-file`; no `.sh`). Unit tests with in-memory fakes instead of the
integration suite (no wp-env). Local: `2 created, 9 skipped, 1 retired, 1 in the trash, 252 unchanged (252 adopted)`;
again `0 created, 256 unchanged`; the fingerprint equal; the retired product's box shows its target. AC7, AC8, AC23
pass.

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

- [x] **Step 1: Failing tests.**
- [x] **Step 2: The rule.**
- [x] **Step 3: Run and look.**
- [x] **Step 4: Commit** — "Refresh migrated products from Lasso without losing edits".

*Note (2026-11-22):* `--update [--force]` on `wp affibloom-lasso migrate` (and with `--dry-run`, the preview).
`ProductsStep::refresh()` compares the record's fields now with the mark's `given` hash (Lasso changed) and the product's
fields with its `stored` hash (Affibloom changed): Lasso only → `update_product()` and a new mark; both → kept and
reported unless `--force`; a product no longer published in Affibloom is left as it is (Affibloom reads and updates
published products only, so nothing trashed is brought back); a record deleted in Lasso is reported, its product kept;
new records are created. Affibloom's `ProductAccess::update()` turns a `DuplicateLink` into the API's refusal
(`update()` now declares it). Unit tests with fakes (no integration suite). Local: `--update` gives `256 unchanged` (no
false change); AC9 changes record 13250's badge in Lasso, checks all three cases and puts it back; Lasso's fingerprint
is equal to the one before the batch. AC1's test corrected: it now holds before and after the migration (to make +
already made = 256).

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

- [x] **Step 1: Failing tests.**
- [x] **Step 2: The step.**
- [x] **Step 3: Run and look** (the two tabs).
- [x] **Step 4: Commit** — "Import Lasso's General and Amazon settings".

*Note (2026-11-22):* `wp affibloom-lasso migrate --only=settings [--update]`. Affibloom's API gained `settings()` and
`save_settings( tab, values )` (`Extension\SettingsAccess`, `SettingsService::import()`: the values given and every
switch of the tab at its current value, so nothing is turned off by being left out; a hidden `import` tab saves the
tracking-ID blacklist and the default picture, which no screen shows). The migrator's `SettingsStep` (settings only now;
`DisplayStep` is display's) compares each mapped value with Affibloom's: the same is unchanged; a different one is
written on the first run (D9) or with `--update`, else kept and reported; a refused value is an error line. Lasso's
`cpt_support` maps without `post`, `page`, `wp_block`, which Affibloom always scans; `enable_logs` goes through the Logs
tab. Local: 9, then 2 imported (Sponsored off, Show Price off, GA on with its ID, CPU 80, the blacklist), then 58
unchanged. Lasso has Auto-Monetize Amazon on, so the import turns Affibloom's on; on Local, where real Amazon
credentials are kept, it is switched off again after each run and by AC10. AC10 passes.

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

- [x] **Step 1: Failing tests.**
- [x] **Step 2: The step.**
- [x] **Step 3: Run and look.**
- [x] **Step 4: Commit** — "Import Lasso's Display settings for every display type".

*Note (2026-11-22):* `wp affibloom-lasso migrate --only=display [--update]`. Affibloom's API gained
`display_settings()` and `save_display( type, config )` (`Extension\DisplayAccess`: a type's settings in the shape they
are read, turned into the Display form's input, saved by `DisplaySettingsService::update_type()`); the test double
`InMemoryDisplaySettingsRepository` is shared now. The migrator's `DisplayStep` compares each type's part with
Affibloom's (every key Lasso gives; the density by its values, as the screen names its preset) and follows 26C's rule
(first run or `--update` writes, else kept). Local: 10 imported (themes Lab, Cutter, Cutter; Lasso's colours with its
purple buttons; elements; Single's layout), then 16 unchanged; Settings → Display matches the production baseline
(Lab, Spacious, side by side, image left). AC11 passes.

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

- [x] **Step 1: Failing tests.**
- [x] **Step 2: Mapper, step, source.**
- [x] **Step 3: Run and look.**
- [x] **Step 4: Commit** — "Migrate Lasso's custom fields and their values".

*Note (2026-11-22):* `wp affibloom-lasso migrate --only=fields [--update]`. Affibloom's API gained
`field_definitions()`, `create_field()`, `rename_field()`, `product_field_rows()`, `set_product_field_rows()`
(`Extension\FieldAccess`; a product's fields are attached first, rows not given are taken off, Primary Rating comes
first as Lasso fixes it); `InMemoryFieldRepository` is a shared test double now. In the migrator: `LassoSource::fields()`
(`LassoFields`), `FieldsStep` in place of the WaitingStep (one class, not a separate `FieldMapper`), the log's
`field_map` (`MigrationLog::map()`/`remember()`). Built-in fields keep Lasso's IDs in Affibloom, so only a changed name
is written (here none: "Primary Rating" is Affibloom's default); a custom field is created once. A product's rows
follow 26C's rule; a product not published in Affibloom is left. Local: DETAILS created (#136), 2 products' ratings
set, 3 skipped (their products are in Affibloom's trash from earlier tests), then 12 unchanged. AC12 passes.

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

- [x] **Step 1: Failing tests.** AC13 (twice: second run unchanged; the order; `group_map`). Expected red: no `groups` family.
- [x] **Step 2: Source and mapper.**
- [x] **Step 3: The step.**
- [x] **Step 4: Run and look.** The Groups list and one group against Lasso's; the fingerprint equal.
- [x] **Step 5: Commit** — "Migrate Lasso's groups and their order".


*Note (2026-11-22):* `wp affibloom-lasso migrate --only=groups [--update]`. Affibloom's API gained `groups()`,
`create_group()`, `update_group()`, `group_members()`, `set_group_members()` (`Extension\GroupAccess`: the members made
exactly the list given, in its order, through `GroupService`). In the migrator: `LassoSource::groups()` (`LassoGroup`:
the term, its records ordered by `lasso_category_order`, then the rest oldest first), `GroupsStep` in place of the
WaitingStep (one class, no separate `GroupMapper`), `group_map` in the log; a group of the same name in Affibloom is
adopted; only published products join; 26C's first-run and `--update` rule. Unit tests with fakes. Local: the one group
("best volyball shoe", no members) created with Lasso's slug, then unchanged; the Groups list shows it. AC13 passes.

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

- [x] **Step 1: Failing tests.** AC19 (tracking IDs per country; cached rows; no secret read; second run unchanged). Expected red: no `amazon` family.
- [x] **Step 2: Source and mapper.**
- [x] **Step 3: The step.**
- [x] **Step 4: Run and look.** Settings → Amazon; no secret in the output or the CSV; the fingerprint equal.
- [x] **Step 5: Commit** — "Migrate Lasso's Amazon tracking IDs and product data".


*Note (2026-11-22):* `wp affibloom-lasso migrate --only=amazon [--update]`. Affibloom's API gained `amazon_product()`
and `import_amazon_product()` (`Extension\AmazonAccess`: a cached product stored marked `imported` through the store's
`save()`). In the migrator: `LassoSource::amazon()` (`LassoAmazon`: the cached rows a record's url details names, the
counts, and whether Creators credentials are set, a yes or no only), `AmazonStep` in place of the WaitingStep (no
separate `AmazonMapper`). A Lasso row goes to Affibloom's store unless Affibloom's is as new or newer (its refresh wins);
26C's rule for a newer Lasso row on a later run. Not copied, each with a line: per-country tracking IDs (Lasso never
writes them, C1 R58; an error if one ever has a value), other-store rows (no store in Affibloom; their prices came with
the products in 26A), and Creators credentials (none here; on a site that has them, an error line asks for them to be
entered by hand, so no secret passes through the migrator, a narrower reading of N3). Local: 226 rows, 25 created, 201
unchanged (Affibloom's data newer), then 226 unchanged. AC19 passes.

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

- [x] **Step 1: Failing tests.** AC20 (a view with a group filter; a view with an unknown filter; second run unchanged), the tour flag copied, a closed education box copied and left closed. Expected red: no `views` family.
- [x] **Step 2: Source and mapper.**
- [x] **Step 3: The step.**
- [x] **Step 4: Run and look.** The Dashboard's saved views beside Lasso's; the tour does not start; a closed education box stays closed; the fingerprint equal.
- [x] **Step 5: Commit** — "Migrate saved Dashboard views".


*Note (2026-11-22):* `wp affibloom-lasso migrate --only=views [--update]`. Affibloom: the stored view shape moved
from `UserMetaSavedViewRepository` into `SavedView::from_stored()`/`to_stored()` (its second use); the API gained
`views()`, `save_view()`, `tour_done()` and `finish_tour()` (`Extension\ViewAccess`); `education_hidden` is saved
through the hidden `import` tab (only IntroBoxes' names). Migrator: `LassoSource::views()` (`LassoViews`: each user's
views, the site's tour flag, the boxes marked `hide`), `ViewsMapper` (group via `group_map`; Lasso's sorts to
Affibloom's columns; an unknown filter, a group not migrated or an oldest-first sort dropped and named in the line's
note), `ViewsStep` (26C's rule; the tour only ended, a box only closed, never reopened; Lasso's keyword box a skip line,
no Affibloom screen). The four `*-opportunities` keys are skip lines in the settings family. Tests are unit tests
(`ViewsMapperTest`, `ViewsStepTest`), as for the other families. Local had no Lasso views: AC20 gives the first admin
two and closes Lasso's link box; created, then unchanged; the Dashboard lists both, the group view opens on group 569,
the Links intro box stays closed; fingerprint equal.

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

- [x] **Step 1: Failing tests.** AC14 (the seven links answer after the step; collisions reported; second run unchanged). Expected red: no `slugs` family.
- [x] **Step 2: Source and mapper.**
- [x] **Step 3: The step, through `CloakHandOver`.**
- [x] **Step 4: Run and look.** `curl -I` on the seven addresses; the fingerprint equal.
- [x] **Step 5: Commit** — "Keep Lasso's cloaked addresses working".


*Note (2026-11-22):* `wp affibloom-lasso migrate --only=slugs [--update]`. Affibloom has no `CloakHandOver`; its API
gained `product_address()`, `product_at_slug()` and `set_product_address()` (`ProductAccess`: slugs in WordPress's
form, `sanitize_title()`, so Lasso's underscores and full length stay; a slug another product answers at is refused).
The prefix and its restriction already move with the settings (26C). In the migrator: `LassoSource::slugs()`
(`LassoAddress`; `__trashed` marks left out), `SlugsStep` (no separate mapper): Lasso's slug becomes the product's,
Affibloom's own stays as an old slug unless it is another record's Lasso address; a product holding another record's
slug moves to `affibloom-tmp-<id>` first and takes its own in the same run (swaps), and gets its address back if it
cannot; Lasso's old slugs another product holds stay with it, named; a retired or trashed product is skipped (saving
would republish it). Local: the one-time import had given 252 products new slugs; 83 linked products were in
Affibloom's trash from earlier tests, three of them the posts' cloaked links (aircat, total-attack, under-armour-…-2),
put back. Result: 171 addresses unchanged after the run, 3 collisions reported (two Affibloom-only `amzn-amazon-*`
products, one retired product holding a record's slug). AC14 passes; fingerprint equal.

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

- [x] **Step 1: Failing tests.** AC15 (variants created; second run unchanged). Expected red: no `variants` family.
- [x] **Step 2: Source and mapper.**
- [x] **Step 3: The step.**
- [x] **Step 4: Run and look.** A product's Variants tab against Lasso's; the fingerprint equal.
- [x] **Step 5: Commit** — "Migrate Lasso's URL variants".


*Note (2026-11-22):* `wp affibloom-lasso migrate --only=variants [--update]`. Affibloom's API gained
`product_variants()` and `set_product_variants()` (`Extension\VariantAccess`: the Variants tab's normalising and
`VariantService::replace()`). In the migrator: `LassoSource::variants()` (`lasso_url_variants` in stored order),
`VariantsStep` (no separate mapper: the rows keep name and key, the kind follows the product as in Lasso, R23): a
product's list is replaced as a whole, 26C's rule for a later run, a refused row an error line. Tests are unit tests.
Local's Lasso had no variants: AC15 gives record 10001 (`/aircat/`) two; created, then unchanged; the Variants tab
shows both in order; fingerprint equal around the run.

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

- [x] **Step 1: Failing tests.** Unit: `IssueMapperTest` over rows from the inventory fixtures (open `000`, open `404`,
  open `400`, resolved `200`). Integration: seed a Lasso data set; `--only=issues` twice (created once, `unchanged` the
  second time); the Alert state of a carried row; a dismissed notice stays dismissed. Expected red: the family reports
  "waiting".
- [x] **Step 2: Source and mapper** (`LassoSource::issues()`, `IssueMapper`).
- [x] **Step 3: The step** (`plan()`, `run()`, `--update`; writes through F11's issue repository and the product
  meta; "waiting for F11" when its table is absent).
- [x] **Step 4: Run and look.** `tools/lasso-fingerprint.sh` before and after (equal); `wp affibloom migrate
  --only=issues`; open the Dashboard: the same 109 out of stock and 3 broken as Lasso, the same dates; a product's page
  shows its bar unless it was dismissed. At 1280 and 375 px.
- [x] **Step 5: Commit:** "Carry Lasso's link alerts over".


*Note (2026-11-22):* `wp affibloom-lasso migrate --only=issues [--update]`. Affibloom's API gained `link_issue()`,
`import_link_issue()`, `issue_dismissed_until()` and `dismiss_issue()` (`Extension\IssueAccess`: F11's table and
`AlertDismissals`; times UTC). In the migrator: `LassoSource::issues()` (`LassoIssues`: the rows and the dismiss
meta), `IssuesStep` with the mapping as `IssuesStep::mapped()` (no separate `IssueMapper`); Affibloom's row wins when
its last check is as new or newer (26L's rule), 26C's rule for a later run; rows of products in Affibloom's trash or
retired, and dismissals already expired in Lasso, are skip lines. F11 is part of Affibloom, so no "waiting for F11"
case. Local: 12 rows created (Lasso's own alerts, such as Salomon Speedcross out of stock for 79 days), 165 where
Affibloom's check is newer, 5 dismissals (all expired; the first run, before that rule, copied them, harmless), then
unchanged. AC18 passes; fingerprint equal.

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

- [x] **Step 1: Failing tests.** AC16: the three tables with their products, order, badges, columns, values; a second run changes nothing; the fingerprint of Lasso's tables equal before and after (AC23). Expected red: the family reports "waiting for F25".
- [x] **Step 2: Source and mapper.**
- [x] **Step 3: The step** (through `TableService`, so F25's validation applies).
- [x] **Step 4: Run and look.** On Local: `--only=tables`; the Tables list; tables 2 and 3 in the builder and in the preview against Lasso's.
- [x] **Step 5: Commit** — "Migrate Lasso's comparison tables".


*Note (2026-11-22):* `wp affibloom-lasso migrate --only=tables [--update]`. Affibloom's API gained `table()` and
`put_table()` (`Extension\TableAccess`: a table read and stored whole through `TableRepository`, the name required,
every product and field checked; not cell by cell through `TableService`'s revision-guarded builder calls). In the
migrator: `LassoSource::tables()` (details, products by `order`, field groups by `order` then as stored, values from
the group details), `TablesStep` with the mapping inside (no separate `TableMapper`): products through the ID map,
custom fields through `field_map`, `table_map` in the log, 26C's rule; an unpublished product is left out and named;
Lasso's Show Title has no Affibloom setting (off in all three here). Local: 3 tables created, then unchanged; four of
their products were in Affibloom's trash from earlier tests and were put back. The builder shows table 2 as in Lasso
(Horizontal, Cactus, badges, description and button cells). AC16 passes; fingerprint equal.

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

- [x] **Step 1: Failing tests.** AC21: each product's switch, every dismissed opportunity, the replacements and the listed settings keys carried over; a second run changes nothing; Lasso's fingerprint equal (AC23). Expected red: "waiting for F22".
- [x] **Step 2: Source and mapper.**
- [x] **Step 3: The step** (the switch and the dismissals after O5-O7 exist; the settings keys as their features exist).
- [x] **Step 4: Run and look.** On Local: the command; the Dashboard's opportunities tile; Settings → General; a dismissed row stays dismissed after a rebuild of its host.
- [x] **Step 5: Commit** — "Migrate Lasso's opportunity switches, dismissals and scan settings".


*Note (2026-11-22):* `wp affibloom-lasso migrate --only=opportunities [--update]`. Affibloom's API gained
`opportunity_dismissed()` and `dismiss_opportunity()` (`Extension\OpportunityAccess`: a post's link found in the index
by `LocationKey::md5()`; dismissed as Dismiss does). In the migrator: `LassoSource::opportunities()`
(`LassoOpportunities`: `lasso_url_details.is_opportunity`, `lasso_link_locations.is_ignored`, `lasso_link_updates`),
`OpportunitiesStep` (no separate mapper): the switch through `update_product( is_opportunity )` with 26C's rule; a
dismissed link dismissed (never brought back; one not indexed yet is a skip line); a retired-ASIN replacement with no
replacement product is a skip line (Lasso resolves nothing either), one with a product an error line to do by hand
(Affibloom has no such table). The scan, opportunity, auto-detect and Analytics keys already move with 26C, which
reports none waiting. With every family built, the empty waiting list left `Plugin.php`. Local: 2 switches off in
Lasso (one product in Affibloom's trash), 1 updated; no dismissed links; 3 replacements, none with a product; then
unchanged. AC21 passes; fingerprint equal.

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

- [x] **Step 1: Failing tests.**
- [x] **Step 2: Runner and route.**
- [x] **Step 3: The page's script.**
- [x] **Step 4: Remove the one-time import.**
- [x] **Step 5: Run and look** (two tabs; kill a request mid-run).
- [x] **Step 6: Commit** — "Run the Lasso migration from the admin, in batches".


*Note (2026-11-22):* Migrate from Lasso gained a "Run the migration" card: a backup tick, **Run migration**, and
**Update from Lasso** once products have run; a progress line ("Comparison tables done: 8 of 12"). One family per
request rather than 25 items with a cursor: the largest, 265 products, takes about 2 s on Local, and every family is
idempotent, so one cut off runs again whole. `MigrationRunner::batch()` (lock `affibloom_lasso_migration_lock`,
`add_option()` as the atomic take, taken over after 10 minutes; the families still to run in
`affibloom_lasso_migration_run`), `Rest\MigrationController` `POST affibloom-lasso-migrator/v1/run` (Affibloom's
settings capability; 409 "A migration is already running."), `assets/admin/migrate.js`. Step 4 had nothing left:
Affibloom's one-time import (`LassoImportController` and the Dashboard card) was removed earlier. Checked in Brave:
the held lock's message, a cut-off run resuming at Groups, the reload with the report. AC22 passes.

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

- [x] **Step 1: The tool** (red: differences listed).
- [x] **Step 2: Fix causes, with a test each.**
- [x] **Step 3: Run and look.** `bash tools/local-wp.sh eval-file tools/compare-migration.php` ends `250 boxes: N equal, N different`; every difference is fixed or written in `docs/parity.md`.
- [x] **Step 4: Commit** — "Prove every migrated product gives Lasso's box values".


*Note (2026-11-23):* `tools/compare-migration.php [fresh]`, `docs/parity.md`, a `tools/README.md` line. The tool
matches each baseline box to its own shortcode by guide and Lasso ID (the fixtures' orders differ) and rewrites
`lasso-br-code` to `-br-` as LM2 will. Its `fresh` mode makes each product from the record through the products
family's mapper and Affibloom's API, then deletes it (Local's products were adopted from the one-time import with
their own values; production has none). One cause fixed: Prime came from the cached row, but Lasso's box shows it for
every Amazon product while its setting is on (`LassoRecordMapper`, with a test). Result `fresh`: 250 boxes, 238 equal,
12 explained in `parity.md` (baseline quotes, the slugs and fields families not run in that mode). 76 linked products
in Affibloom's trash from earlier tests were put back first. Open for the user: whether `--force` should also put
Lasso's values back on adopted products (26B's rule).

---

### Task 26Q: Lasso's original links — `--only=locations` (added 2026-11-23, deviation 156)

**Feature:** F22 N3 (E17), F21 R1 · **Depends on:** Affibloom's Task GF6 (`Api::set_original_link()`), 26A (the ID
map is not needed: rows are matched by post and URL).

**Why:** Lasso's scan rewrote links in posts and kept what was there before in `lasso_link_locations.original_link_slug`
(450 rows here, F21). Affibloom's index starts from the content as it is now, so its `original_url` is the rewritten
link and GF6's "turn back to the original URL" has nothing to offer until the migrator carries Lasso's.

**User action:** `wp affibloom-lasso migrate --only=locations [--dry-run]`, and the same family on Migrate from Lasso:
"450 original links found, N set, M not in Affibloom's index (post changed or not scanned yet)". Opportunities → a
post's links then shows those tied rows with an enabled switch (GF6).

**Code:** a `locations` family: read `detection_id`, `link_slug`, `original_link_slug` where they differ (trailing
slash ignored) and `original_link_slug` is a link; per row `Api::set_original_link( host, link_slug, original )`;
count set, missing and skipped; the report and CSV as the other families (25D). Read only on Lasso's side; running it
twice sets nothing new (GF6's API leaves a row whose `original_url` was already changed).

**Files:** in the migrator repository: the family class, its dry-run reader, the CLI `--only` value, the report row;
unit tests with a fake API; Local run.

- [x] **Step 1: Tests.** - [x] **Step 2: Family, dry run and run.** - [x] **Step 3: Run and look** (Local: dry run,
  run, a second run sets 0; a post's links at 1280 px). - [x] **Step 4: Commit** (migrator) — "Carry Lasso's original
  links into Affibloom's index (26Q)".

*Note:* Affibloom gained `Api::original_link()` for the dry run (`7cc8373`). Lasso's `post_id` is the record, so the post
is `detection_id` (F21 R1); 26N read dismissals from `post_id` and was fixed (migrator `7e8f95b`). Affibloom keys a link
by post and URL, so where Lasso rewrote one link of a post from several originals every place takes the first (a skip
line for the others, in the dry run too). Local: 413 lines; 179 given (5-place links counted once), 178 Lasso display
rows whose original is a shortcode, 52 second originals, 4 posts not indexed yet; a second run 179 unchanged; Migrate
from Lasso lists "Original links"; post 2900's rewritten links have an enabled switch.

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

- [x] **Step 1: Failing tests.**
- [x] **Step 2: The domain mapping.**
- [x] **Step 3: The converter's plan and the command.**
- [x] **Step 4: Run and look.** The 34 posts: every item to convert, or blocked with a reason that can be acted on.
- [x] **Step 5: Commit** — "Report how each Lasso box becomes Affibloom's".

*Note:* what Affibloom's block holds is Affibloom's: `Api::block_for_shortcode()` (`2afe4df`, defaults left out
`57265a5`) gives the block's attributes and the names it would lose (an unknown name, or a toggle with a value it does
not keep, e.g. `rating="4.5"`); the migrator maps Lasso's names, IDs, groups and tables (95fd697). A Lasso block is read
from its `short_code` plus its schema settings; `schema_review_author`/`schema_rating` are dropped as unread (A52).
`[lasso-attr]` is left to LM4 (none here). Local (59 posts, 12 of them test drafts): 335 items, 332 to convert (304
blocks, 28 bare), 3 blocked: an empty Lasso block (draft 19835), `category=""` (19885), `category="pair-grid"`, which
Lasso does not have (18462). A converted block drew its product. The fixture's 250 boxes are all Single.

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

- [x] **Step 1: Failing tests.** Convert, revert byte-equal, refuse after an edit, `--force`.
- [x] **Step 2: Write and revert.**
- [x] **Step 3: Run and look.** One guide in the editor before and after (no invalid-block notice); the front page
  unchanged at 1280 px.
- [x] **Step 4: Commit** — "Convert Lasso blocks to Affibloom's, with a copy to go back to".

*Note:* migrator `d969ed9`. Only the Lasso blocks change (`BlockRewriter`, by their place; every other byte kept); the
block's markup is `serialize_block()`'s; `ContentChangeWriter` saves through wp_update_post() with kses off (WP-CLI has
no user), reads the post back and undoes a save that did not store exactly what was written, and keeps
`_affibloom_lasso_pre_conversion` once plus `_affibloom_lasso_converted_md5` for revert's edit check; lock
`affibloom_lasso_convert_lock`. No integration test (that suite has never run, deviation 10); unit tests with fakes.
Local: post 2900, 10 blocks, editor 10 valid Affibloom blocks, no warning; front end draws the same boxes, but without
Lasso's card shadow, which comes from the site's `.lasso-container` Additional CSS (LM7 translates it). Whole site:
304 blocks in 44 posts, a second run writes nothing, an edited post refused then `--force`d, `--revert` of all: 59 posts
byte-equal to before. **For the cutover:** Lasso's licensed `save_post` hook rescans a post in the background; run the
conversion with Lasso off (Local's is unlicensed, so the hook never ran here). A box the block cannot hold whole
becomes `[affibloom …]` in a Shortcode block (the user, deviation 159; migrator `5c7e187`): a test draft with
`rating="4.5"` converted so, drew Affibloom's box with the rating, reverted, and was deleted.

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

- [x] **Step 1: Failing tests,** one per host.
- [x] **Step 2: The rules and the hosts.**
- [x] **Step 3: Run and look** (the 2 on this site).
- [x] **Step 4: Commit** — "Convert bare Lasso shortcodes, widgets and Elementor data".

*Note:* migrator `ecd7d77`. One scanner (`LassoMarkup`) finds every Lasso item in document order for both the report
and the rewrite (LM2-LM3's parse_blocks() order could differ from the text's for shortcodes in container blocks);
posts, widgets (`widget_text`, `widget_custom_html`, `widget_block`) and Elementor's data are `TextStore`s, each with
its copy and revert (widgets: the whole option in `affibloom_lasso_pre_conversion_widgets`; Elementor: the meta in
`_affibloom_lasso_pre_conversion_elementor`, its CSS and element caches cleared). A Shortcode block holding one
`[lasso]` becomes Affibloom's block; Lasso's Elementor widget becomes Affibloom's (`affibloom_shortcode`, ED2);
`[lasso-attr]` is reported (it reads meta Lasso never writes, F31 R3). Local: 28 not 2 bare items (test posts since):
7 Shortcode blocks, 21 bare; dry run 59 places, 335 items, 332 to convert, 3 blocked, as LM2; convert all (332 in 56
places), posts 21913 and 18233 draw Affibloom's boxes with no shortcode text left, 21913's blocks valid in the editor;
revert all, 59 posts byte-equal. None in widgets or Elementor here: those two stores are unit-tested.

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

- [x] **Step 1: Failing tests, per type.**
- [x] **Step 2: The mappings.**
- [x] **Step 3: Run and look** (the draft 9492 at 1280 px).
- [x] **Step 4: Commit** — "Convert Lasso's other display types, groups and tables".

*Note:* migrator `4291778`, Affibloom `473d343`. The types, groups (`group_map`) and tables (`table_map`) were mapped
by LM2's `LassoAttributes`; what Affibloom's block cannot draw (`link`, SiteStripe, `amazon_url`/`geni_url` without
an ID) becomes `[affibloom …]` in a Shortcode block (deviation 159), and `amazon_url`/`geni_url` stay attributes that
Affibloom resolves at render, as Lasso (F31 A6, A7, D2), instead of being looked up at conversion. AI text: Affibloom's
store was empty ("Task 26 fills it", which no task did); the API gained `ai_text()`/`store_ai_text()`, and a box
converts with Lasso's cached text (its transient `lasso_chatgpt_response_<format><md5(serialize(prompt))>`, copied
when the box is written) or is reported. Unit tests per type and for AI text (none on this site). Draft 9492: its two
tables (22101, 22102) and box drew at 1280 px; reverted byte-equal.

---

### Task LM6: Lasso's stamps in content ✅

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

- [x] **Step 1: Failing tests.**
- [x] **Step 2: Domain, cleaner, ties.**
- [x] **Step 3: The command.**
- [x] **Step 4: Run and look.** Three guides: the front end unchanged, the editor without notices, the index's tied
  count unchanged. *(The editor's notices moved to LM6A, deviation 160.)*
- [x] **Step 5: Commit** — "Remove Lasso's stamps from content, keeping its ties".

*Note:* migrator `60ddcf4`, Affibloom `0370193` (`Api::link_ties/tie_link/untie_link`). Local: 419 posts (4,100
`data-lasso-id`, one inside a Rank Math FAQ's JSON), all cleaned; of 340 tied links the index agreed on 326, the other
14 (a second product of one ASIN, cloaked addresses with no product) were assigned Lasso's; after the run all 340
resolve to Lasso's product. Revert byte-equal for all 419, ties cleared (untie before the revert's save, so the
rescan reads each link by its address). Own copy `_affibloom_lasso_pre_stamps`, so convert and stamps revert apart.
Front end unchanged but for the retied links' product. The editor still flags GenerateBlocks buttons once their
stamps are gone (Lasso's rewritten `rel`): Task LM6A, deviation 160. No integration test (deviation 10).

---

### Task LM6A: The blocks Lasso's scan rewrote ✅

**Feature:** F34 AC6 (the editor without notices); deviation 160

**Why:** Lasso's scan rewrote `rel` on links opening in a new tab (`noopener`, `noreferrer` dropped) and its editor
script makes a stamped block save as stored. With the stamps gone (LM6), or Lasso off, the editor flags those blocks:
on Local 969 `generateblocks/button` (`rel`) in 92 posts, 19 `core/image` (`src`), 4 `generateblocks/headline`, 2
`rank-math/faq-block`, 8 other GenerateBlocks buttons.

**Depends on:** LM6.

**User action:** `wp affibloom-lasso blocks --dry-run` lists, per post, each block the editor would flag and what
would change; without `--dry-run` it rewrites each such block's HTML as the block saves it, only the attribute that
differs; copy and revert as LM3. Afterwards the guides open in the editor without notices.

**Open before it starts:** each block's save rule (GenerateBlocks' `rel` from its attributes, the image `src`
cases), read from the blocks' own source; whether the editor's validator is run in the browser for the dry run.

- [x] **Step 1: The rules, from each block's source.**
- [x] **Step 2: Failing tests, then the repair.**
- [x] **Step 3: Run and look.** The 92 posts' blocks valid in the editor's check (`wp.blocks.parse`), the front end
  unchanged, revert byte-equal.
- [x] **Step 4: Commit** — "Repair the blocks Lasso's scan rewrote".

*Note:* migrator `8aa6680` (and `5bdb8f4`, LM6's JSON fixture, whose escapes had been lost). Measured with Lasso off,
as at the cutover: Lasso's editor script reads a GenerateBlocks button's `target` and `rel` from its HTML, so saves
under Lasso dropped `"target":true`, and its scan rewrote `rel`. The settings now follow the HTML (`target`,
`relNoFollow`, `relSponsored`) and `rel` is GenerateBlocks 2.5's (`nofollow`, `noopener noreferrer`, `sponsored`),
so readers keep what Lasso gave them; a button whose settings would not re-encode exactly is reported, none on Local.
Local: 1,011 buttons in 93 posts repaired; the editor's check (`wp.blocks.parse`) then flags none of 1,038 buttons
but 7 pasted inside a `<p>`; the guides' front end unchanged (tied links print Affibloom's rel), Home's "/about/"
button lost a stray `rel`; reverted byte-equal (`_affibloom_lasso_pre_blocks`). Not Lasso's and left to the editor's
recovery: 33 images with their size in `style`, 17 Rank Math FAQ and 4 GenerateBlocks headline blocks. The revert
report is shared by convert, stamps and blocks (`InterfaceCliRevertReport`). Cutover order: Lasso off, convert,
stamps, blocks.

---

### Task LM7: The site's Lasso CSS rules ✅

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

- [x] **Step 1: Failing tests.**
- [x] **Step 2: Map and translator.**
- [x] **Step 3: The command.**
- [x] **Step 4: Run and look** (a guide whose box the CSS changes, at 1280 and 375 px).
- [x] **Step 5: Commit** — "Translate the site's Lasso CSS rules to Affibloom's classes".

*Note:* migrator `8307d26` (and `9219702`: a full revert deletes the copy's options). The map is `docs/ui/tokens.md`'s table (`lasso-X` is `affibloom-X` where Affibloom prints
that name, with its renames, the URL/anchor/field families, `--lasso-*` and `data-lasso-*`); an unknown name is left and
reported. The site has 32 rules (several inside `@media`; a 33rd is commented out), all translated, every other byte
kept; the store is the theme's `custom_css` post, its copy in `affibloom_lasso_pre_css`. Looked at on the hitters guide,
converted (LM3) with the CSS translated: Affibloom's box gets the site's shadow and badge as production's Lasso box does
(both use the Lab theme; the Cutter rules style no box on production), at 1280 and 375 px; reverted byte-equal.

---

### Task LM8: The conversion from the admin page ✅

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

- [x] **Step 1: Failing test.**
- [x] **Step 2: Routes and batches.**
- [x] **Step 3: Card, list and run.**
- [x] **Step 4: Run and look** (1280 and 375 px; a reload mid-run resumes).
- [x] **Step 5: Commit** — "Convert and revert Lasso content from the admin".

*Note:* migrator `dde475c`. A place is `site` (widgets and the Additional CSS) or `post:<id>` (its boxes and
Elementor data, stamps and buttons: LM3-LM6A in one request); the places are listed when a run starts and kept in
`affibloom_lasso_content_run`, so a reload continues ("A conversion stopped at 24 of 432 places"). The routes are
`affibloom-lasso-migrator/v1/convert` (`backup_confirmed`) and `/convert/revert` (`place`), under 26O's lock and each
step's own lock: 409 beside a migration, a conversion or a command, 400 without the tick. Revert per place keeps a place
changed since (the command's `--force` takes it). Local, Lasso off: 432 places converted from the page at about one a
second, everything but the 3 blocked boxes; one post and then Revert All brought every place back byte-equal. The card
costs the page about 1.4 s warm (3.2 s against 1.7 s). No Playwright spec run (deviation 131).

---

### Task LM9: Nothing of Lasso is left ✅

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

- [x] **Step 1: Failing tests.**
- [x] **Step 2: The check.**
- [x] **Step 3: Command and card.**
- [x] **Step 4: Run and look.** On Local after LM3-LM7: all zero.
- [x] **Step 5: Commit** — "Check that no Lasso content is left".

*Note:* migrator `fc62b8a`. `LassoLeftovers` counts the markers (a block by its opening comment; a class in `class` or
`className`, plain, escaped or JSON-encoded) and reads each Affibloom box's product, group (ID or slug) or table;
`WpContentPlaces` reads only places that name Lasso or Affibloom (posts of every type and status but revisions,
auto-drafts and Lasso's own; Elementor data; the `scan_custom_fields` ACF fields; widgets; the active theme's CSS), with
edit links built directly (get_edit_post_link() gives none in WP-CLI). Lasso's findings come before the boxes'. Local
before converting: 1,422 findings. After LM3-LM7 (Lasso off): Lasso's markers only in the 3 boxes the conversion blocks,
stamps, classes and CSS at zero; not "all zero", because 898 boxes in Affibloom's own E2E test posts ("Acceptance", …)
name products deleted (686) or trashed (107) by earlier test runs; production has no such posts. Then everything was
reverted byte-equal. The card adds about 1 s to the page warm (verify takes 2.5 s with WP-CLI's start). No E2E spec
written (deviation 131, as LM3-LM8).

---

## Tasks: F10 — parity, the rollback drill and removal

### Task 29A: Parity — every box on every guide ✅

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

- [x] **Step 1: The suite, red.** `tests/Visual/parity.spec.ts` (values part): reset Local, migrate, Lasso off, the 34 guides against `baselines/boxes.json`.
- [x] **Step 2: Fix causes.** A test for each cause, or the difference written in `docs/parity.md`.
- [x] **Step 3: Run and look.** `npm run test:e2e -- parity`: all 34 guides pass; open one guide beside its baseline.
- [x] **Step 4: Commit** — "Prove every box matches Lasso with Lasso off".

*Note:* Affibloom `5cb2cd8`, migrator `5df30fb`. No reset (deviation 129): the suite migrates, converts (LM3-LM7), turns
Lasso and the migrator off, reads every box in the browser, and puts the site back. One test with a soft check per guide.
First run: 973 differences. Fixed at the cause: the block's texts were texturized (182; `past_texturize()`, test).
Local's adopted products (~790: New Window, Prime, links, `rel`): deviation 161, `--update --force` restored 254;
Sky Elite's rating: `--only=fields --update`. Left, in `EXPLAINED` and `docs/parity.md`: 5 titles whose quotes the
baseline dropped, and Court Stabil's Local-only rating. Then 34/34 pass (5.2 min). Beside production the values match,
but the layout does not (Lasso's box stacks the image above the title, badge top left; Affibloom's is side by side, as its
migrated Display says): for 29B.

---

### Task 29B: Parity — whole pages ✅

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

- [x] **Step 1: The suite part, red.**
- [x] **Step 2: Fix causes, or record the difference in `docs/parity.md`.**
- [x] **Step 3: Run and look.** `npm run test:e2e -- parity`; open the diffs in `test-results/parity`.
- [x] **Step 4: Commit** — "Prove every guide looks the same with Lasso off".

*Note:* Affibloom `296112c`. Lasso's pages re-captured from Local with Lasso on, before converting (`PARITY_CAPTURE=pages`,
not in git): the 2026-11-12 capture is older than the site's content since. 65 of 68 pages within 0.01; 3 not compared
(another height fails whatever the tolerance), each box measured: the Tachikara title keeps its quotes (deviation 162,
the user's choice: Lasso's box turns curly characters into spaces, so 29A's "baseline dropped the quotes" was wrong and
is corrected), and Lasso's `title_type="Best Lightweight"` printed as a `<best lightweight>` tag. Production stacks its
boxes, Local side by side: a difference of the two sites' Lasso settings, which a migration carries. Diffs in
`test-results/`.

---

### Task 29C: Parity — every link, and no call to Lasso ✅

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

- [x] **Step 1: Failing tests.** The suite's links part on the 34 guides with Lasso off; red until the cloaked addresses and the render-time attributes agree with the baseline.
- [x] **Step 2: The capture tool** (run once with Lasso on, before the reset) and the baseline.
- [x] **Step 3: Fix causes** (each with a test at the owning feature), or record in `docs/parity.md`.
- [x] **Step 4: Run and look.** The whole parity suite twice; no request to Lasso's hosts in the log.
- [x] **Step 5: Commit** — "Prove every link still goes where it went".

*Note:* Affibloom `8338cc3` (the code is in `296112c`, one spec file). The capture is the suite's own
(`PARITY_CAPTURE=links`, tools/README.md), not a separate tool: 2,302 article links, 93 own-site addresses (4 cloaked).
All go where they went, the redirects hold, no request to Lasso's hosts. 72 links reach the same place spelled
another way (a trailing slash, a long or short Amazon URL, a cloaked address or its target): the box and a text link to
the same product trade spellings, because Local's Lasso 370 prints a second button as the linked product's URL and keeps
a text link as scanned, while Affibloom prints them as stored and as F21 R40 says. The suite compares destinations and
lists these; recorded in `docs/parity.md`. The whole suite ran in parts (boxes, pages, links), not twice end to end.
*Corrected in 29D:* the 72 were Affibloom rewriting Lasso's box links during the capture (both plugins on); none
remain.

---

### Task 29D: Parity — converted, and reverted ✅

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

- [x] **Step 1: The two modes, red.**
- [x] **Step 2: Fix causes, or record the difference in `docs/parity.md`.**
- [x] **Step 3: Run and look.** Both modes pass on Local.
- [x] **Step 4: Commit** — "Prove parity survives conversion and revert".

*Note:* the `reverted` mode first showed 143 differences: with both plugins on, Affibloom rewrote the links of Lasso's
boxes. Fixed by the generic filter `affibloom_leave_link`, answered by the migrator for `data-lasso-box-trackable`; the
links baseline captured again. Both modes pass; `lasso` mode added for 29E (`docs/parity.md`, 29D).

---

### Task 29E: Rollback drill ✅

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

- [x] **Step 1: The drill, written as a checklist that fails on a differing fingerprint.**
- [x] **Step 2: Run it on Local; fix causes.**
- [x] **Step 3: Run and look.** The guides against the baselines with Lasso back on; the two fingerprints equal.
- [x] **Step 4: Commit** — "Rehearse going back to Lasso".

*Note:* the checklist is `tools/rollback-drill.sh` (+ `tools/content-fingerprint.php` for the content). Both fingerprints
equal; `lasso` mode passes once two answers the links baseline took from Affibloom are named as Lasso's own
(`LASSO_ALONE`, `docs/parity.md`, 29E). Local put back afterwards (4,895 places unchanged).

---

### Task LM10: Finish the migration and remove the plugin ✅

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

- [x] **Step 1: Failing tests.**
- [x] **Step 2: Finish and uninstall.**
- [x] **Step 3: Run and look.** On Local: Finish, delete the plugin, three guides and the Dashboard at 1280 px.
- [x] **Step 4: Commit** — "Finish the migration and leave nothing behind"; tag the migrator's release.

*Note:* the integration test is a unit drift test (`WpMigratorDataTest`: every stored name in the code is erased; the
migrator has no WordPress test suite) and a look at the database on Local. Finish keeps `affibloom_lasso_finished` until
uninstall, so nothing runs again; no tag (deviation 163). On Local: export, convert, 841 test posts deleted at the user's
word (the check's 901 findings), Finish from the page, `wp plugin uninstall … --skip-delete`: no option or meta of the
migrator left; `PARITY_MODE=finished` (new) passes 3 of 3 with Affibloom alone.

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
