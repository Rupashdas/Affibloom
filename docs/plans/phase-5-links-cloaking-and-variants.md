# Affibloom — Phase 5: Links — the link index, cloaking and URL variants — Implementation Plan

> **How to run this plan:** inline, in one working session, task by task, in the order below. Do not hand tasks to subagents. When a step is finished, change its `- [ ]` to `- [x]`.
>
> **Explain as you go:** before each task, say in two or three sentences what the task builds, why it is needed now, and how it will be checked. After the task, say what changed and what the verification showed. No vibe coding: every line written must be one you can explain.
>
> **Shared rules:** the architecture, working method, decisions, global constraints, file structure and API contract are in [`00-overview.md`](00-overview.md). Read it first.

## Status

Planned. **Rewritten 2026-11-17 (the user; deviation 146):** Affibloom is a standalone affiliate plugin. It will be
installed new on many sites and replaces Lasso on one. Everything that reads Lasso's data or converts Lasso's content
moved to a separate plugin, the **Affibloom Lasso Migrator** ([Phase M](phase-m-lasso-migrator.md)), which runs once
on VolleyballVault and is then deleted. Compatibility with other affiliate plugins (F31's competitors' shortcodes, F33's
imports) is dropped. This phase builds core features only, and starts by taking the Lasso code already built out of
core (LC1, LC2). Phase 4 was signed off 2026-11-17.

## Scope and features

- **Core without Lasso** — Tasks LC1, LC2 (deviation 146).
- **F21** — the content link index (35A-35J): `wp-content/plugins/affibloom/docs/features/F21/`. The Locations screens 36A-36E stay in Phase 7.
- **F9 (part)** — Task 9J, the Link Index card: `docs/features/F9/`.
- **F38 (part)** — Tasks ON3 (Automations) and ON4 (link index notices): `docs/features/F38/`.
- **F24** — link cloaking, redirects, permalinks, old addresses (CL1-CL11): `docs/features/F24/`.
- **F27** — URL variants (V1-V5): `docs/features/F27/`.

Moved to Phase M (the migrator): F10 (25A-26P, 26G, 26H; 26K from Phase 6; 26I, 26N, 29C from Phase 7; 29A, 29B,
29E), and the conversion of Lasso's content (LM2-LM9, built on the rules F31 wrote for `[lasso]`). Dropped (archive):
27A-27I and ED6 (Affibloom no longer renders Lasso's shortcode or block; the content is converted instead), 27X1-27X8
and IM1-IM20 (other affiliate plugins), 27E (the migrator converts `[lasso type="table"]`). F34 still has no tasks.

## Rules for every task in this phase (deviation 146)

- Core reads no Lasso data (`lasso-urls` posts, `lasso_*` options and meta, `wp_lasso_*` tables) and no Lasso markup
  (`[lasso`, `affiliate-plugin/lasso`, `data-lasso-*`, `lasso-*` classes). `NoLassoInCoreTest` (LC1) enforces it.
- Where a section below quotes "this copy's" figures, they describe VolleyballVault's content; the tests make their own
  fixtures (products, posts with `[affibloom]` boxes, `affibloom/product-box` blocks and plain links) through WP-CLI
  or REST. The site's own figures are checked again after the migration, in Phase M.
- No other affiliate plugin is read or recognised (ThirstyAffiliates' `<ta>`, AAWP, EasyAzon, Pretty Links, and so on).
  Integrations that are not affiliate plugins stay as planned (Elementor, Classic Editor, AMP, Rank Math, ACF, Kadence,
  the builders and recipe plugins of 35E).
- A *Changed 2026-11-17* note under a task's heading says what differs from the section's text; the note wins.

## Decisions that change this phase

- **Decided (the user accepted the recommendation, 2026-11-14):** links that only other plugins render (Recipe Maker, Mediavine Create, TablePress, Beaver Builder, Thrive) keep the attributes stored when Lasso last scanned where no output filter reaches them (F21 N1, recommended: accept; task 35E).
- **Decided (the user accepted the recommendation, 2026-11-14; narrowed 2026-11-17, deviation 146):** SiteStripe iframes and Kadence buttons of tied links are drawn at render time (F21 N2; task 35J). Lasso's own conversions in content (other plugins' shortcodes to `[lasso]`, a `[lasso]` in an image block) are not Affibloom's: the migrator converts Lasso's content (Phase M, LM2-LM5), and other plugins are dropped.
- **Decided (the user accepted the recommendation, 2026-11-14):** the variant-slug refusal wording, "That slug is already a Lasso link." (F27 N1, recommended: "That slug is already a product's link."; decides Task V2's message). **Changed 2026-11-17 (deviation 146):** the message is "That slug is already a product's link." (no Lasso name in Affibloom).
- **Decided (the user accepted the recommendation, 2026-11-14):** an old slug answers exactly as the current one (F24 N1, recommended: yes; tasks CL3, CL10).
- **Decided (the user accepted the recommendation, 2026-11-14):** old cloak prefixes stay in a history and keep answering (F24 N2, recommended: yes; tasks CL5, CL10).
- **Decided (the user accepted the recommendation, 2026-11-14):** flag and slug changes reach stored content only at render time, not in other plugins' stored HTML (F24 N3, recommended: accept).
- **Decided (the user accepted the recommendation, 2026-11-14):** do not copy Lasso's switching off of `wp_old_slug_redirect` (F24 N4, recommended: do not copy).
- **Decided (the user accepted the recommendation, 2026-11-14):** leave Lasso's cloud in the redirect out; keep the local `sid` to sub-ID rewrite (F24 N5, recommended: as stated).
- **Decided (the user accepted the recommendation, 2026-11-14):** longer cloaked paths (`/a/b/<slug>/`) are not matched (F24 N6, recommended: accept).
- **Decided (the user accepted the recommendation, 2026-11-14):** wording of the slug-conflict warning (F24 N7, recommended: "Permalink <slug> is being used by <type>. This product's link will not open until you change it.").
- **Decided (the user, 2026-11-14), A3:** monetize at render time plus "Edit in post" (F22 O10); nothing rewrites the post.

The migration decisions of 2026-11-12 and 2026-11-14 (F10 N3-N6, F29's button mapping, Task 6 decisions, F31 N1-N3,
E1, F34 N1, N2, N5, N6) are in Phase M; decisions A1 and A2 were reversed there on 2026-11-17; F31 N4 and F33 N1-N6
lapsed with their tasks.

## Tasks in this phase

Grouped by feature, in the order they are worked.

**Core without Lasso**

- [x] Task LC1: Take the one-time Lasso import out of core
- [x] Task LC2: Take Lasso's markup out of core

**F21 — the content link index**

- [x] Task 35A: The index of anchors in post content — `wp affibloom links rebuild`
- [x] Task 35B: Displays in the index — shortcodes and blocks
- [x] Task 35C: Saving, trashing and deleting a post updates its rows — `save_post`
- [x] Task 35D: Custom Link Detection — Settings → General
- [ ] Task 35E: Links in builders and recipe plugins — `wp affibloom links rebuild --post` (SiteStripe and Elementor done 2026-11-18; the other plugins wait for the user's answer)
- [x] Task 35F: The background rebuild and the daily pass — WP-Cron `affibloom_link_index_batch`
- [x] Task 35G: Settings → General — Link Index and Performance

**F9 (part) — the Link Index card**

- [x] Task 9J: Link Index card

**F38 (part) — Automations and link index notices**

- [x] Task ON3: Automations — what is running in the background
- [x] Task ON4: Link index notices

**F21 — a product's save and repoint**

- [x] Task 35I: A product's save and repoint keep its rows — `PATCH /products/{id}`

**F24 — Link cloaking, redirects and permalinks**

- [x] Task CL1: The redirect for a cloaked path
- [x] Task CL2: Query strings through the redirect
- [x] Task CL3: Permalink and Link Cloaking on the product page
- [x] Task CL4: A new product's slug and cloaking
- [x] Task CL5: The General settings take effect
- [x] Task CL6: The public link in front-end output
- [ ] Task CL7: The Dashboard row's link
- [ ] Task CL8: Products in the block editor's link search
- [ ] Task CL9: Products in the classic editor's link dialog
- [ ] Task CL10: Old addresses (Lasso's hand-over moved to Phase M, 26G)
- [ ] Task CL11: A pasted cloaked URL finds its product

**F27 — URL variants**

- [ ] Task V1: The Variants tab
- [ ] Task V2: Add, edit, remove and save variants
- [ ] Task V3: A variant permalink answers
- [ ] Task V4: Amazon variant tags are kept
- [ ] Task V5: Find products by their variants on the Dashboard

**F21 — render-time link attributes and displays (after the cloaking block)**

- [ ] Task 35H: Render-time link attributes — `the_content`
- [ ] Task 35J: SiteStripe, image-block and Kadence displays at render time — `render_block`

## Cross-phase notes

**Earlier phases (all done):** Phase 2: F1's products, Add Link and `PATCH` (11A-17D, 15A, the Share kit), F2's Amazon
link rules, F9's controls and keys (9B, 9D, 9G, 9H, 9I, 9K, 9L: 35D, 35G, 9J build on them), F23 Groups (G1-G5: 35B),
TL1 and TL2 (`Schema\{Migrator,Table}`: the tables of 35A and V1), ON1 and ON2 (ON3, ON4). Phase 3: the box and the
`[affibloom]` shortcode (18A-22E, 18S1-18S9; 18S5's text link for CL6). Phase 4: the block (23A-23G).

**Inside this phase:** 35A's table holds `affiliate_platform` and `assigned_product_id` (kept across a rescan; F22 N1-A
in Phase 7 and the migrator's LM6 write it); 35C fires `affibloom_link_index_indexed` (F35, Phase 7, listens). V1 reads
CL3's public link, V2 uses CL3's old slugs, V3 adds its arm to CL1's `CloakResolver`; 35H reads CL6's `PublicLink`.

**Later phases:** Phase 7's Locations (36A-36E), clicks (33A: counts `a[data-affibloom-id]`, printed by 35H) and
opportunities read the index. Phase M writes Lasso's slugs, flags and old slugs (26G), variants (26H) and Lasso's ties
(LM6) into these stores through Affibloom's extension API (LM1); nothing in this phase waits for it.

---

## Tasks: core without Lasso (deviation 146)

### Task LC1: Take the one-time Lasso import out of core

**Feature:** core without Lasso (the user, 2026-11-17; deviation 146)

**Why:** Affibloom is a standalone affiliate plugin, installed new on many sites; reading Lasso's records is the job of
the separate migrator plugin (Phase M), whose 26A adopts the products this import made.

**Depends on:** nothing.

**User action:** the Dashboard no longer offers "Import your N products from Lasso". Products imported earlier stay as
they are, with their meta; the Amazon data card shows "Imported" for their cached data, as before.

**What this task removes and creates:**

| Removed (with their unit and E2E tests) | Was called by |
|---|---|
| `Application\Migration\{LassoImportService, LassoRecordMapper}`, `Domain\Migration\{LassoRecord, LassoSource, LassoImportLog, ImportResult}`, `Infrastructure\Lasso\WpLassoSource`, `Infrastructure\WordPress\PostMetaLassoImportLog` | `Plugin`, `DashboardScreen` |
| `Interface\Admin\LassoImportController`, `templates/admin/partials/lasso-import.php`, the import form in `assets/admin/dashboard.js`, `Notice` codes `lasso-imported` and `lasso-import-running`, `DashboardScreen`'s `lasso_pending` | the Dashboard |

| New | Called by |
|---|---|
| `tests/Unit/Architecture/NoLassoInCoreTest.php`: no file in `src/`, `templates/`, `assets/` or `blocks/` names Lasso outside comments (an allow-list holds LC2's files until LC2 empties it) | PHPUnit |
| an `Upgrader` step: the Amazon data rows' source `lasso` becomes `imported`; the card reads `imported` | `UpgradeHooks` |

The data stays in the database: the meta `_affibloom_lasso_id` and the option `affibloom_lasso_import` are no longer
read by Affibloom; the migrator reads them (Phase M, 26A). Comments that cite Lasso's code as the behaviour a feature
copies stay: they are not shipped behaviour.

**Files:** remove the above; modify `src/Plugin.php`, `src/Interface/Admin/{DashboardScreen,Notice}.php`,
`templates/admin/dashboard.php`, `templates/admin/partials/amazon-data-card.php`, `assets/admin/dashboard.js`,
`src/Application/Upgrade/Upgrader.php`; create the architecture test.

- [x] **Step 1: Test.** `NoLassoInCoreTest` (LC2's files allowed for now) and the Upgrader step's unit test.
- [x] **Step 2: Remove the import and its wiring; rename the Amazon source.**
- [x] **Step 3: Run and look.** The Dashboard at 1280 px: no import card; a product imported earlier opens as before.
- [x] **Step 4: Commit** — "Take the one-time Lasso import out of core".

*Note (2026-11-18):* no `Upgrader` step: nothing ever wrote the Amazon source `lasso` (Local: 175 rows, all
`creators`), so only the card's check and `AmazonProduct`'s doc changed to `imported`. The import card's CSS went too.
`NoLassoInCoreTest` scans PHP with the tokenizer and JS/CSS without comments; its allow-list holds LC2's 7 files.

---

### Task LC2: Take Lasso's markup out of core

**Feature:** core without Lasso (deviation 146); F4 ED5 and F5 R46 withdrawn

**Why:** after the migration no post holds Lasso's shortcodes, blocks or stamps (Phase M converts them), so Affibloom
needs no code that recognises them; on a new site they never exist.

**Depends on:** LC1.

**User action:** none visible on a site without Lasso content. (On Local, Lasso is still active and draws its own
content until Phase M.)

**What this task removes and changes:**

| Removed or changed | Where |
|---|---|
| Lasso's markers in `BoxDetector` (`[lasso`, `affiliate-plugin/lasso`, `lasso-display`, `lasso-table`, Elementor's `lasso_shortcode`); the box CSS loads for Affibloom's block and shortcode only | `Infrastructure\WordPress\BoxDetector` |
| `[lasso` in the widget marking (`origin="widget"` for `[affibloom` only) | `Interface\Front\WidgetShortcodes` |
| ED5's `assets/editor/core-link-attributes.js`, its enqueue in `EditorAssets`, F4 AC20 (the migrator removes the stamps from content, LM6) | `assets/editor`, `Interface\Editor\EditorAssets`, `tests/E2E/acceptance/F4.spec.ts` |
| the post types `lasso-urls`, `pretty-link`, `affiliate_url` in the detection exclusions (other affiliate plugins') | `Infrastructure\WordPress\WpDetectionChoices` |
| "Lasso" as a placeholder product name (only "Add a Link Title", its slug and "Amazon" stay) | `Domain\Product\PlaceholderName` |
| `lasso-br-code` in a shortcode's description becomes Affibloom's own line-break code (the one its pros and cons read, if it fits; recorded in a *Note*); the migrator writes it (LM2) | `Domain\Box\BoxOverrides` |
| the admin class names `lasso-dashboard`, `lasso-dashboard-views-bar`, `lasso-dashboard-pager`, `lasso-dashboard-filters-panel`, `lasso-create-group-modal`, `lasso-add-links-to-group-modal` become `affibloom-…`, with their CSS and script selectors | `templates/admin`, `assets/admin` |

**Files:** the above and their unit tests (`BoxDetectorTest`, `WidgetShortcodesTest`, `PlaceholderNameTest`,
`BoxOverridesTest`); `NoLassoInCoreTest`'s allow-list emptied.

- [x] **Step 1: Tests.** `NoLassoInCoreTest` with an empty allow-list; the changed unit tests.
- [x] **Step 2: The changes.**
- [x] **Step 3: Run and look.** A post with an `[affibloom]` box loads the box CSS; the Dashboard, the Groups dialogs
  and a product page at 1280 px look as before.
- [x] **Step 4: Commit** — "Take Lasso's markup out of core".

*Note (2026-11-18):* the line-break code is `-br-` (what `[affibloom]`'s pros and cons already read). The admin
`lasso-*` class names were already gone (only comments named them); `BoxDetector`'s `type="table"` marker went with
Lasso's, and `AdminNavigation`'s exception no longer names Lasso. Checked: a post with `[affibloom]` loads the box CSS.

---

## Tasks: F21 — the content link index (moved here from Phase 7 on 2026-11-14; 35H and 35J follow the cloaking block)

> Written 2026-11-14 from a sweep of Lasso 369's link index (`docs/features/F21/requirements.md`, F21 analysis in
> `F21/analysis.md`). They replace Phase 7's Tasks 35A-35C. Gate 4: waiting. Every task: outside-in, its
> acceptance test first and red, `composer lint`, `composer analyse`, unit tests, the check by hand at 1280 and 375 px
> where there is a screen, one commit, pushed. No task writes post content (F21 D3). Order in this phase: 35A, 35B, 35C,
> 35D, 35E, 35F, 35G, 9J, ON3, ON4, 35I; 35H and 35J after the cloaking block (deviation 146).

### Task 35A: The index of anchors in post content — `wp affibloom links rebuild`

> **Changed 2026-11-17 (deviation 146):** no dependency on 25A or 26A; the resolver ties a link by its own URL, ASIN or
> cloaked path, then by `assigned_product_id`; it reads no Lasso ID map and no `data-lasso-*` stamp, and `lasso.to` is an
> ordinary external link. The fixtures are made by the test (products and posts through WP-CLI); Step 6 compares with
> requirements §2 only as far as Local's content still matches (Lasso's content is converted in Phase M).

**Feature:** F21 (R1-R4, R13, R22, R24, R25, R36, R58 partly; D1, D3-D9, D18; AC1)

**Why:** everything that says where a product is used, and every opportunity, reads this index.

**Depends on:** F1 products and `ProductRepository` (Phase 2); 25A's `LassoIdMap` and 26A (ADR-2's Lasso ID map, earlier in this phase); Phase 2 Settings; TL2's `Schema\{Migrator,Table}` (Phase 2).

**User action:** a shell user runs `wp affibloom links rebuild [--post=<id>] [--dry-run]` and sees "Indexed N posts:
M links (T tied to P products)." and with `--dry-run` the per-type counts and no writes; `wp affibloom links status`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Tables\LinkLocationsTable`: the table's schema step, added to TL2's `Schema\{Migrator,Table}` (Phase 2). Its columns are those of F21's analysis and include `affiliate_platform` (F33 IM16 reads it) and `assigned_product_id` (int, default 0, kept across a rescan like `is_ignored`; F22 N1-A / O10 reads and writes it) | `Plugin` on `plugins_loaded`/activation, the indexer |
| Domain `LinkIndex\{LinkLocation, LocationKey, LinkType, DisplayType, LinkClassifier, AffiliatePlatform, LinkCandidate, LinkLocationRepository}` | the indexer |
| `Application\LinkIndex\{LinkIndexer, LinkIndexRebuild::run_inline, ProductResolver, ContentSource}` | `LinksCommand` |
| `Html\LinkScanner` (anchors), `Content\PostContentSource`, `WordPress\HostQuery` | `LinkIndexer` |
| `Cli\LinksCommand` (`rebuild`, `status`) | WP-CLI |

**Files:** the above; `src/Plugin.php`; tests `tests/Unit/Domain/LinkIndex/{LinkClassifierTest,AffiliatePlatformTest,LocationKeyTest}.php`,
`tests/Unit/Application/ProductResolverTest.php`, `tests/Integration/LinkIndexerTest.php`, `tests/E2E/acceptance/F21.spec.ts` (AC1).

- [x] **Step 1: Failing tests.** AC1 on a fixture post (text, image, internal, external, `#`, `mailto:`, an ignored
  class, a FAQ block, a 1,200-character anchor), rows read through `wp eval`, `post_content` md5 unchanged. Red: no command.
- [x] **Step 2: Domain** (R2's exact flag rule, R25's types, platforms, occurrence).
- [x] **Step 3: Resolver** (F21 D8 order; published only; lasso.to → untied).
- [x] **Step 4: Table (TL2's Migrator), repository** (`replace_host` in a transaction, `is_ignored` and `assigned_product_id` kept, D10).
- [x] **Step 5: Scanner, source, indexer, command.**
- [x] **Step 6: Run and look.** `--dry-run` on this copy; compare with requirements §2 per host; record differences.
- [x] **Step 7: Commit** — "Index every link in post content".

*Note (2026-11-18):* built as planned, with Affibloom's names: the table is `TableLinkLocationRepository` (schema step 4) and the
scanner `Infrastructure\Html\WpLinkScanner`; no `lasso_location_id` column (deviation 146), no retired-ASIN arm (core
keeps no ASIN history). Internal means the site's address followed by `/`, `?`, `#`, `:` or the end (Lasso's prefix test
also took `site.test.other.com`). The scanner's unit test loads WordPress's HTML API and block parser from
`wp-includes`; AC1's E2E replaces the integration test (the suite never runs). On this copy: 1,105 posts, 3,555 links,
only 217 tied to 8 products, because the one-time import stored Lasso's Amazon products as "other" without ASINs;
Phase M (26A, 29C) checks the figures again.

---

### Task 35B: Displays in the index — shortcodes and blocks

> **Changed 2026-11-17 (deviation 146):** only Affibloom's displays are indexed: `[affibloom]` and the
> `affibloom/product-box` block. `affiliate-plugin/lasso`, `aawp/aawp-block`, `[lasso id]` and the import tables are not
> read (the migrator converts Lasso's boxes, LM2-LM5; other plugins are dropped). Step 4 counts the test's fixtures.

**Feature:** F21 (R5, R6, R27-R29; D1, D2; AC2)

**Why:** boxes and group displays are locations too; the delete rule and Tables' counts need them.

**Depends on:** 35A; F23 (groups, G1-G5, Phase 2) for `display_ref = group:`; Tables (F25, Phase 7) only for reading `table:` later.

**User action:** as 35A; the summary gains displays per type.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LinkScanner` shortcodes (`get_shortcode_regex`, `"` decoding) and blocks (`parse_blocks`: `affibloom/product-box`, `affiliate-plugin/lasso`, `aawp/aawp-block`) | `LinkIndexer` |
| `DisplayType` mapping (R27), `display_ref` | the scanner |

**Files:** scanner, resolver (ASIN, `[lasso id]`, import table lookups), tests (unit per shortcode family; integration
grid with a group; E2E F21 AC2).

- [x] **Step 1: Failing tests.** AC2 per shortcode family, a grid with a group, the blocks; red: no display rows.
- [x] **Step 2: Shortcodes.**
- [x] **Step 3: Blocks.**
- [x] **Step 4: Run and look** (223 Single and 2 Table rows here).
- [x] **Step 5: Commit** — "Index boxes and group displays".

*Note (2026-11-18):* displays are Affibloom's shortcode and block only (deviation 146), resolved by a new `DisplayResolver` (slug, ID, then
`amazon_url`/`geni_url`; group by ID or slug); a display's "URL" is its markup (the shortcode as written, or the block's
name and addressing attributes), so repeats count by occurrence. Tables (`table:`) wait for F25. Step 4 was run on a
fixture post (Single, Button, Image, Grid and List rows; the group displays carry `group:<id>`), not on Lasso's boxes.

---

### Task 35C: Saving, trashing and deleting a post updates its rows — `save_post`

**Feature:** F21 (R10-R12; D5, D6; AC3); decision 6

**Why:** the index must be right the moment a post changes, with no queue.

**Depends on:** 35A, 35B.

**User action:** an editor saves, schedules, drafts, trashes, restores or deletes a post; its Locations follow at once.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Hooks\LinkIndexHooks` (Module: `save_post` 20, `trashed_post`, `untrashed_post`, `deleted_post`; once per post per request; no revisions/autosaves) | WordPress |
| `LinkLocationRepository::delete_host()` | the hooks |
| the event `affibloom_link_index_indexed` (fired by the hooks and by the indexer after a host's rows are written, with the host id; F35's auto-monetize listens to it) | F35 (Phase 7) |

**Files:** the module, `Plugin.php`, `tests/Integration/LinkIndexHooksTest.php`, E2E F21 AC3.

- [x] **Step 1: Failing tests.** AC3 (save, schedule, draft, trash, restore, delete) and the event fired once per host; red: no hooks.
- [x] **Step 2: Hooks and the event.**
- [x] **Step 3: Run and look.**
- [x] **Step 4: Commit** — "Keep a post's link rows in step with the post".

*Note (2026-11-18):* the event is fired by the indexer (a callback `Plugin` turns into `do_action`), once per written host, for saves and
rebuilds alike. A deleted post's rows are removed directly: at `deleted_post` WordPress's cache still returns the post,
so indexing it wrote the rows again (seen on Local and fixed). Checked through WP-CLI: create 2 rows, edit 1, private 1,
trash 0, restore 1, delete 0. Only the indexed post types are handled on save.

---

### Task 35D: Custom Link Detection — Settings → General

> **Changed 2026-11-17 (deviation 146):** "26C's map gains these keys" is Phase M's 26C (it maps Lasso's keys to these).

> Settled 2026-11-14: 9H/9I/9L build the controls and store them under F9 D3's keys; 35D/35G build only the effect (the scan reads those keys) and 9J the Link Index card's figures — no second card.

**Feature:** F21 (R22, R33, R53, R56 for its keys; D7; AC4)

**Why:** Lasso scans chosen post types and ACF fields; the site owner chooses them here.

**Depends on:** 35C; the Settings screen's General tab and 9I (the card and its keys, Phase 2).

**User action:** "Custom Link Detection" ("Scan for links and shortcodes in custom locations.", the tooltip): "Custom
Post Types:" (multi-select without Lasso's exclusions and `affibloom_product`, "Select post types"), "Custom Fields
(ACF):" only when ACF is active ("Select fields"); Save Changes; a change starts a rebuild (35F once built; inline
before). The controls are 9I's; this task makes the scan follow them.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `HostQuery` reads 9I's keys (F9 D3's `scan_post_types`, `scan_custom_fields`; the draft's own names `link_index_post_types`, `link_index_acf_fields` are not built); 26C's map gains these keys | `LinkIndexer` |
| `Content\AcfFieldsSource` (meta key ends with the field name, R33) | `LinkIndexer` |
| a change of either key starts a rebuild | `SettingsService` |

**Files:** the above; tests (unit validation; E2E F21 AC4 with a test CPT).

- [x] **Step 1: Failing tests.** AC4 with a test CPT: a post of the chosen type is indexed, one of another type is not; red: the scan ignores the keys.
- [x] **Step 2: Keys and source.**
- [x] **Step 3: The effect (the rebuild on change).**
- [x] **Step 4: Run and look.**
- [x] **Step 5: Commit** — "Choose custom post types and fields to scan".

*Note (2026-11-18):* `HostQuery` reads the chosen types (its `types()` also serves the source and the save hooks); ACF fields are read in
`PostContentSource` (no separate `AcfFieldsSource`: one place per host) as `acf:<meta key>`. The rebuild on change is a
callback `SettingsService` gets; until 35F it runs inline (about 9 s for Local's 1,105 posts in the save request).
Checked in Brave: ticking a test post type and saving indexed its post. Local now lists Lasso's `lasso-urls` there
(LC2 dropped Lasso's exclusion; it is another plugin's public type).

---

### Task 35E: Links in builders and recipe plugins — `wp affibloom links rebuild --post`

> **Changed 2026-11-17 (deviation 146):** ThirstyAffiliates' `<ta>` tags are not scanned (no other affiliate plugin is
> read). The page builders, recipe plugins, TablePress, Kadence and SiteStripe stay: they are not affiliate plugins.

**Feature:** F21 (R23, R26, R30-R32; D3, D15 for their output filters; N1; AC5)

**Why:** full scope: Lasso indexes these when their plugins are active.

**Depends on:** 35A-35C.

**User action:** none new; rows appear for such posts with Lasso's link types.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Content\{ElementorSource, BeaverSource, ThriveSource, TablePressSource, WprmSource, MvCreateSource}`; `<ta>`, Kadence, SiteStripe (setting `sitestripe_scan`) in `LinkScanner` | `LinkIndexer` |
| Elementor `elementor/document/after_save`, Mediavine `mv_create_card_post_publish` in `LinkIndexHooks` | the plugins |
| a list of each plugin's output filter (for 35H), recorded in the analysis | 35H |

**Files:** sources, scanner, hooks; integration tests with fixtures per source (plugins absent → skipped).

- [x] **Step 1: Failing tests.** AC5: a fixture per source (plugin absent → skipped); red: no rows for these hosts.
- [x] **Step 2: Sources.**
- [x] **Step 3: Hooks.**
- [x] **Step 4: Record the output filters (N1).**
- [x] **Step 5: Run and look.** `wp affibloom links rebuild --post=<id>` on a fixture post per available plugin; none is active on this site, so the E2E skips are listed.
- [x] **Step 6: Commit** — "Index links in builders and recipe plugins".

*Note (2026-11-18):* **part built; the rest waits for the user's answer** (`open-decisions.md`, "Non-affiliate integrations"). Built:
SiteStripe images and iframes (setting `scan_sitestripe_in_post_content`; `Domain\LinkIndex\SiteStripe` reads Lasso's
`asin(s)`, `tag`/`tracking_id`, `region`/`marketplace`), link type `sitestripe`, display Image or Product Box, tied by
the Amazon link they name (checked on Local: both tied to product 20331; off, none). Elementor needs no reader: it writes
its plain copy (with the Affibloom widget's shortcode, ED2) into `post_content`, which is scanned, and its save runs
`save_post`; reading `_elementor_data` too would count every link twice. Not built yet (none installed on this site):
Kadence buttons and testimonials, Beaver Builder, Thrive, TablePress, WP Recipe Maker, Mediavine Create, and the list of
their output filters for 35H.

---

### Task 35F: The background rebuild and the daily pass — WP-Cron `affibloom_link_index_batch`

**Feature:** F21 (R14-R18, R21; D5, D11, D12; AC6)

**Why:** a whole-site rebuild must not run in a web request; stale rows of deleted hosts must go.

**Depends on:** 35A-35E.

**User action:** none (35G starts it); activation schedules the first full rebuild.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LinkIndexRebuild::start(), next_batch()`, `WordPress\OptionLinkIndexState`, `System\CpuLoad` | `LinkIndexCron`, 35G |
| `Cron\LinkIndexCron` (Module: batch chain with lock; daily pass; cron interval floor; catch-up runner limit) | WP-Cron, admin page loads when WP-Cron is off |

**Files:** the above; unit tests (batch sizing as R16, state, CPU skip); integration (a stopped batch resumes); E2E
F21 AC6 via `wp cron event run`.

- [x] **Step 1: Failing tests.** AC6 (batches, resume, CPU skip, daily pass); red: no cron module.
- [x] **Step 2: State and batches.**
- [x] **Step 3: Cron module.**
- [x] **Step 4: Run and look.** `wp cron event run affibloom_link_index_batch` on this copy; `wp affibloom links status` before and after.
- [x] **Step 5: Commit** — "Rebuild the link index in the background".

*Note (2026-11-18):* batches are Lasso's sizes (R16), chained through the existing `BackgroundJob` (WP-Cron single events) behind a 60 s
`OptionLock`; a busy server (load ≥ `cpu_threshold`) makes the batch wait a minute; `CpuLoad` returns nothing on
Windows, so Local never waits (D12). The daily pass drops rows of posts no longer indexed and re-indexes posts changed in
the last 25 hours, and restarts a broken chain. Activation queues `affibloom_link_index_start` as an event (the modules
may not be registered then); 35D's settings change now starts the background run. The cron interval floor has nothing to
floor (no recurring job below daily). Checked on Local: 5 batches of 221 for 1,105 posts, then idle (31 s).

---

### Task 35G: Settings → General — Link Index and Performance

> **Changed 2026-11-17 (deviation 146):** "26C's map gains these keys" is Phase M's 26C.

> Settled 2026-11-14: 9H/9I/9L build the controls and store them under F9 D3's keys; 35D/35G build only the effect (the scan reads those keys) and 9J the Link Index card's figures — no second card.

**Feature:** F21 (R51, R52, R54-R58; D12, D17; AC7); requirements §5 (Keep Original URL saved here)

**Why:** the owner sees and restarts the index and sets how hard it may work.

**Depends on:** 35F; 9H, 9L (the Performance card and the support cards, Phase 2).

**User action:** "Link Index": "Affibloom tracks every link on your site."; Build Progress / ETA and "We're indexing
your links in the background, feel free to do something else." while running, else Last Updated / Links Indexed
(nothing at 0), refreshed every 5 s (the card's figures are 9J's); **Rebuild Database** → "Ready to rebuild?" / "By rebuilding your link index, you'll
start with a clean slate." / Cancel, Confirm → "Rebuild started." or "A rebuild is already running."; "Performance"
(mode presets; CPU % only for Custom); with `&support=1`: Keep Original URL, Cron "Time interval (hours)" 0-3,
"Maximum cronjob at a time" 1-3. The controls are 9H's and 9L's; this task makes the index follow them and adds the rebuild route.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Rest\LinkIndexController` (`GET /link-index`, `POST /link-index/rebuild`, 409 `affibloom_rebuild_running`) | `settings.js`, 9J's card, 9L's Rebuild Database dialog |
| `LinkIndexStatus`; the index reads 9H's and 9L's keys (F9 D3's `performance_mode`, `cpu_threshold`, `keep_original_domains`, `cron_interval`, `manual_process_limit`; the draft's `keep_original_url`, `cron_interval_hours`, `catch_up_limit` are not built; R56 rules); 26C's map gains these keys | `LinkIndexController`, `LinkIndexCron` |
| the rebuild dialog's call to the route in `settings.js` | `SettingsScreen` |

**Files:** the above; `docs/ui/design-system.md` (stat tiles row); tests unit (R56), E2E F21 AC7.

- [x] **Step 1: Failing tests.** AC7 (status, rebuild, 409 while running, the keys followed); red: no route.
- [x] **Step 2: Routes and status.**
- [x] **Step 3: The keys' effect and the dialog's call.**
- [x] **Step 4: Run and look** (1280, 375 px).
- [x] **Step 5: Commit** — "Show and rebuild the link index from Settings".

*Note (2026-11-18):* the routes and the "Ready to rebuild?" dialog with its script are built; the Link Index card, its Rebuild Database
button (support view) and its 5 s poll are 9J's (next), so the dialog is looked at there. The keys' effects: CPU threshold
and the catch-up limit are 35F's; Keep Original URL is read by the public link (CL6, 35H); the cron interval has nothing to
floor (35F). Checked with `rest_do_request`: 200 with the figures, 202 then 409 `affibloom_rebuild_running`, 401 for a
visitor; the started run finished in the background.

---

### Task 9J: Link Index card

**Feature:** F9 (R20-R21; AC13; behaviour F21)

**Why:** the owner must see on Settings → General whether the index is built and how far it is, as Lasso's Link Index card shows.

**Depends on:** 9B (the General tab, Phase 2); 35G's `GET /affibloom/v1/link-index` (the draft named `/link-index/status`; 35G's route is the one built).

**User action:** "Last Updated", "Links Indexed"; while building "Build Progress", "ETA", the background sentence;
updates every 5 s; nothing indexed → empty card body. ("Start Link Indexing" only exists after N2's removal run, which
is not built.)

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `partials/settings-general-link-index.php` (the one Link Index card; no second card is built by 35G), poller in `general-settings.js` | `settings.php` |

**Files:** create `templates/admin/partials/settings-general-link-index.php`; modify `templates/admin/settings.php`, `assets/admin/general-settings.js`; test `tests/E2E/acceptance/F9.spec.ts` (AC13).

- [x] **Step 1: Failing test.** AC13 against 35G's route: empty body at 0, figures after an index, progress and ETA while building; red: no card.
- [x] **Step 2: Card and poller.**
- [x] **Step 3: Run and look.** On Local after the first index: the card at 1280 and 375 px.
- [x] **Step 4: Commit** — "Show the link index on Settings → General".

*Note (2026-11-18):* the card is the right column's first; its words come from one `Interface\Admin\LinkIndexText` (also the route's), the
fact list shares the Amazon data card's style (`.affibloom-facts`), and the support view's Rebuild Database opens 35G's
dialog. Checked in Brave at 1280 px: Last Updated and Links Indexed (4,606); Rebuild Database → "Ready to rebuild?" →
Confirm → Build Progress "0 completed of 1,105 total", then "221 completed", ETA 1 minute, from the 5 s poll. A first
look found the support flag missing from the card's data (a PHP warning), fixed before the commit.

---

### Task ON3: Automations — what is running in the background

**Feature:** F38 (R40-R43; D13; AC7)

**Why:** bulk add already runs after the request (F18); link checks, the index and imports will. Lasso's header shows
them; editors wait on them.

**Depends on:** ON1 (Phase 2); F18's `BulkAddService` (Phase 2; first job); 35F (the index job joins the board).

**User action:** the header's **Automations** icon spins with a count while a job runs; a click opens "Automations"
with "Bulk Add Links (3/10)", or "Silence is a source of great strength - Lao Tzu"; a click elsewhere closes it; the
count updates every 15 s for five minutes.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Shell\{JobStatus, JobBoard}`, `Domain\Shell\JobProgress` | `JobsController` |
| `Application\Products\BulkAddJobStatus` (name "Bulk Add Links") | `JobBoard` |
| `Interface\Rest\JobsController` `GET /affibloom/v1/jobs` (PRODUCTS) | `shell.js` |
| header button + popover; `assets/admin/shell.js` (poll, 5-min stop, 15-s cross-tab reuse, viewport placement) | `header.php`, `AdminAssets` |

**Files:** the classes above; tests `JobBoardTest`, a controller test, F38 AC7; modify `header.php`,
`AdminAssets.php`, `Plugin.php`, `admin.css`, `design-system.md` (popover).

- [x] **Step 1: Failing tests** (AC7: a bulk add of three links started through REST; the count, the line, the idle
  text).
- [x] **Step 2: Board and status.**
- [x] **Step 3: Route, header, script.**
- [x] **Step 4: Run and look.** At 1280 and 375 px: start a bulk add, see the count and the line, wait for the idle text.
- [x] **Step 5: Commit** — "Show running background jobs in the header".

*Note (2026-11-18):* the board holds Bulk Add Links (F18) and the link index rebuild as Lasso's "Scan all links" (its full scan); C1I's bulk
Amazon sync keeps its own banner and can join later. The popover reuses the design system's menu (a click elsewhere
closes it); the button is an outlined icon beside Add New Link (a first look had it as a second green button after the
tabs). Checked in Brave at 1280 px: during a rebuild the icon carries "1" and the menu reads "Scan all links (0/1105)".

---

### Task ON4: Link index notices

> **Changed 2026-11-17 (deviation 146):** only `LinkIndexBuildingNotice` is built. `RescanAfterImportNotice` and its
> sentence are not: imports from other plugins are dropped (F33), and the migrator's run starts an ordinary rebuild,
> which the building notice covers.

**Feature:** F38 (R25, R26; D14; AC8)

**Why:** while the index builds, Locations and opportunities are incomplete; Lasso tells the editor on every page.

**Depends on:** ON2 (Phase 2), ON3; 35F (the index job) and 9J (its Link Index card); F33's import (IM2, IM3, later in this phase) for the after-import rescan: `RescanAfterImportNotice` shows nothing until an import exists.

**User action:** during a build: "We're rounding up a list of your links. **See the progress**." (→ Settings →
General's Link Index); after an import, until its rescan ends: "We are creating an index of your links. This will be
complete in <time>. Please wait until then to begin any Imports.", the time refreshed by the Automations poll.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Shell\{LinkIndexBuildingNotice, RescanAfterImportNotice}` (NoticeProviders reading F21's job status) | `NoticeBoard` |
| the ETA in `JobProgress` | `shell.js` |

**Files:** the two providers; unit tests; F38 AC8; modify `Plugin.php`, `shell.js`.

- [x] **Step 1: Failing tests.** AC8: the notice during a build with its link; the after-import notice; red: no providers.
- [x] **Step 2: Providers.**
- [x] **Step 3: ETA refresh.**
- [x] **Step 4: Run and look.** Start a rebuild on Local: the notice on a WordPress admin page, the link to Settings; at 1280 and 375 px.
- [x] **Step 5: Commit** — "Tell editors while the link index is being built".

*Note (2026-11-18):* `LinkIndexBuildingNotice` is in Application with the index (F21's job), a warning-toned notice without ×, its link
to the Link Index card; the after-import notice is not built (deviation 146). Checked in Brave at 1280 px: during a
rebuild the Dashboard shows "We're rounding up a list of your links. See the progress." under the header.

---

### Task 35I: A product's save and repoint keep its rows — `PATCH /products/{id}`

**Feature:** F21 (R19, R60, R61; D16; AC9); F1 D14

**Why:** a new Primary URL or a repoint must move the index, not posts.

**Depends on:** 35A; F1 15A, 15C (Phase 2).

**User action:** an editor saves a new URL or confirms a repoint; Locations follow; no post changes.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LinkLocationRepository::set_url_for_product(), move_product()` | `ProductService::update()`, `repoint()` |

**Files:** `ProductService.php`, repository; integration test; E2E F21 AC9.

- [x] **Step 1: Failing tests.** AC9: a new Primary URL, a repoint; rows follow, `post_content` unchanged; red: rows keep the old URL.
- [x] **Step 2: Calls.**
- [x] **Step 3: Run and look.**
- [x] **Step 4: Commit** — "Move a product's link rows with its URL and repoint".

*Note (2026-11-18):* built as a pin, not as the planned `set_url_for_product()`: a row's identity is the link as the post holds it (D4), so
rewriting its URL would lose it at the next rescan, and the post's old link would no longer resolve. A new Primary URL
pins the product's rows (`assigned_product_id`, which a rescan keeps and which now wins over the resolver); a repoint
(`move_product()`) moves tied and pinned rows to the target, pinned there. Checked on Local through the REST routes:
tied → PATCH → pinned to the product → repoint → tied and pinned to the target; the post unchanged.

---

## Tasks: F24 — Link cloaking, redirects and permalinks

### Task CL1: The redirect for a cloaked path

> **Changed 2026-11-17 (deviation 146):** Step 5 checks products made through WP-CLI; the site's five cloaked products
> and the retired 14771 are checked after the migration, in Phase M (26G).

**Feature:** F24 (R1, R15-R17, R19, R21, R24-R27; D2-D6, D16, D17; AC1-AC3)

**Why:** seven links in this site's posts point to `/<slug>/` addresses Lasso answers; without this they 404 at cutover.

**Depends on:** F1 (products, retired status 17C); F2 (Amazon URL formatting for the destination).

**User action:** a reader opens `/aircat/` and lands on aircatvolleyball.com (302). A page with the same slug is shown
instead; `/tag/x/` stays the tag; an unknown path is the 404; with a prefix only `/<prefix>/<slug>/` answers.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Front\CloakedLinkRedirect` (Module; `template_redirect` 5, `redirect_canonical`, Rank Math pre-search) | `Plugin` |
| `Domain\Cloak\CloakPath` (R16-R17), `Domain\Cloak\PublicLink::destination()` (destination half of R13) | the redirect |
| `Application\Cloak\CloakResolver::product_for()` (slug only; CL10 and F27 V3 add arms) | the redirect |
| `PostTypeProductRepository::find_by_slug( slug, statuses )`, `WpPostSlugGuard` | the resolver |

**Files:** create the above, `tests/E2E/acceptance/F24.spec.ts` (AC1-AC3), `tests/Unit/Domain/Cloak/CloakPathTest.php`,
`tests/Integration/CloakedLinkRedirectTest.php`; modify `src/Plugin.php`, `PostTypeProductRepository.php`.

- [x] **Step 1: Failing tests.** AC1 (a product made through WP-CLI with a slug and a destination; `curl -I` sees 302, the three headers), AC2 (a page with the same slug; a trashed product; an Amazon product's `amzn-` slug), AC3 (the prefix set through WP-CLI). Unit: every R17 case. Expected red: 404.
- [x] **Step 2: `CloakPath`, `PublicLink::destination()`.**
- [x] **Step 3: Resolver, repository, guard.**
- [x] **Step 4: The Module and its filters.**
- [x] **Step 5: Run and look.** Checks; by hand the five products and the retired 14771 on a copy with Affibloom's migrated data (or made by WP-CLI until 26G).
- [x] **Step 6: Commit** — "Answer cloaked product addresses as Lasso does".

*Note (2026-11-18):* built as planned; the product gained `cloak` (meta `_affibloom_cloak`, missing = off), read for the robots header and
stored on every save; CL3 adds its control. The redirect also cancels WordPress's canonical guess for a product path
(R24) and runs first inside Rank Math's redirect check (R25). Checked with curl on Local: 302 with `X-Redirect-By`,
`X-Robots-Tag`, `Cache-Control`; unknown path 404; `/about/` stays the page; prefix `go`: `/go/<slug>/` 302, bare and
`/other/` 404; retired 302; trashed (`__trashed` slug) 404.

---

### Task CL2: Query strings through the redirect

**Feature:** F24 (R22, R23; D7, N5; AC4)

**Why:** UTM and sub-ID parameters on cloaked links reach the merchant in Lasso; dropping them loses attribution.

**Depends on:** CL1.

**User action:** `/aircat/?utm_source=ig` lands on the destination with `utm_source=ig`; `?sid=x` from another site
becomes the network's sub-ID; `{url}` in a destination becomes the referring page.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Cloak\RedirectQuery::apply()` (pure, R22 without the cloud call) | `CloakedLinkRedirect` |
| `Domain\Cloak\NetworkSubIds` (interface), `Infrastructure\Data\FileNetworkSubIds`, `data/network-subids.php` (33 networks) | `RedirectQuery` |

**Files:** the above; `tests/Unit/Domain/Cloak/RedirectQueryTest.php` (every R22 rule), F24 AC4.

- [x] **Step 1: Failing tests** (AC4; unit per rule).
- [x] **Step 2: `RedirectQuery` and the data file.**
- [x] **Step 3: Wire into the redirect.**
- [x] **Step 4: Run and look.**
- [x] **Step 5: Commit** — "Pass query strings through cloaked links".

*Note (2026-11-18):* the data file holds 32 networks (Lasso's 33 with sub-IDs less TradeTracker, which Lasso never rewrites) and Impact's
805 domains, generated from Lasso's two JSON files; Post Affiliate Pro keeps Lasso's own rule in `FileNetworkSubIds`.
`lcid` is dropped, never appended (N5). Checked with curl on Local through a CJ link with a YouTube referrer:
`?utm_source=ig&sid=yt&foo=1&doing_wp_cron=9&lcid=z` → `…?url=x&utm_source=ig&sid=yt&foo=1` (CJ's sub-ID is `sid`).

---

### Task CL3: Permalink and Link Cloaking on the product page

> **Needs F24 N1 and N7 answered first.**

**Feature:** F24, F1 (R4-R11, R13, R14; D9-D14; AC5-AC7; F1 D4, D5 replaced)

**Why:** the two "Coming soon" controls of F1 D4; the page's public link (heading, Share kit) follows the real rule.

**Depends on:** CL1; F1 15A (`PATCH`), the Share kit; N1 and N7 answered.

**User action:** on a non-Amazon product: **Permalink** (typing normalises; the heading line shows
`<home>/(<prefix>/)<slug>/`) and **Link Cloaking**; Save Changes stores both; another product's slug becomes
`-2`; a page's slug saves with the warning; the heading link, the preview and **Copy shareable URL** give the public
link. Amazon products show neither control.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Product\ProductSlug::clean()` (R4), `Domain\Cloak\PublicLink::for()` (R13 in full), `Domain\Cloak\KeptDomains` | `ProductService`, `ProductResource` |
| `slug`, `cloak` in `ProductInput` / `ProductsController::field_args()`; `public_link`, `slug`, `warnings` in `ProductResource` | `product-save.js` |
| `SlugConflicts` port, `WpSlugConflicts` (R5), `Messages::slug_in_use` (N7's wording) | `ProductService::update()` |
| old slug appended to `_affibloom_old_slugs` on change (N1) | `ProductService::update()` |
| partial `templates/admin/partials/permalink-cloak.php`; the typing rule and heading line in `product.js` | `product.php` (and the quick-detail panel later) |

**Files:** the above; `templates/admin/product.php`, `templates/admin/partials/share-kit.php`,
`assets/admin/{product,product-save}.js`, `docs/ui/design-system.md` (warning toast with a link); tests F24 AC5-AC7,
F1 AC2, AC7, AC11, AC16 rerun; unit `ProductSlugTest`, `PublicLinkTest`.

- [x] **Step 1: Failing tests.**
- [x] **Step 2: Domain rules.**
- [x] **Step 3: PATCH, resource, warning.**
- [x] **Step 4: The partial and scripts.**
- [x] **Step 5: Run and look** (1280, 375 px; keyboard).
- [x] **Step 6: Commit** — "Edit a product's permalink and link cloaking, as Lasso does".

*Note (2026-11-18):* `PublicLink::for()` needs no `KeptDomains`: the stored Primary URL already is the destination the save made (Keep
Original URL and geni.us kept as typed), so R13's kept-URL branch gives the same address. The conflict check and N7's
warning sit in the controller (`WpSlugConflicts`), not behind a port in the service; the toast shows the text, the
edit link rides in the answer. The typing rule keeps a trailing hyphen while typing and trims on leaving the field
(Lasso's trim on every key swallows a space, seen in Brave); the heading line shows the address in lower case, as it
will be stored. Checked in Brave at 1280 px: "Air Cat Pro" → `air-cat-pro`, Link Cloaking on, heading link the
cloaked address, old slugs kept, `/air-cat-pro/` 302; a page's slug saved with the warning.

---

### Task CL4: A new product's slug and cloaking

**Feature:** F24, F18 (R3, R12; D11; AC8)

**Why:** Lasso gives every new link a slug from its name (`amzn-` for Amazon) and the site's cloaking default.

**Depends on:** CL3 (`ProductSlug`); Task 11 (Add Link); F9 9B (`link_cloaking` key).

**User action:** **Add Link** with a merchant URL makes a product whose slug is its name's and whose Link Cloaking
equals the General setting; an Amazon URL's slug starts with `amzn-`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `ProductSlug::for_new( name, is_amazon )`; `Product::draft()` gains `cloak` from the settings | `ProductService::create()`, `BulkAddService` |

**Files:** `ProductService.php`, `Product.php`, `BulkAddService.php`; tests F24 AC8, F18's Add Link specs rerun.

- [x] **Step 1: Failing tests.**
- [x] **Step 2: Domain and service.**
- [x] **Step 3: Run and look.**
- [x] **Step 4: Commit** — "Give new products Lasso's slug and cloaking default".

*Note (2026-11-18):* the draft takes the name's slug and the General Link Cloaking setting; the Amazon draft's slug starts with `amzn-`;
the repository inserts the slug and WordPress makes it unique. Bulk add goes through the same create path, so it needs
no change. Checked on Local through `POST /products`: a merchant link → `example-27`, cloak 1; an Amazon link →
`amzn-mikasa-volleyball`, cloak 1.

---

### Task CL5: The General settings take effect

> **Needs F24 N2 answered first.**

**Feature:** F24, F9 (R30, R31; D15, N2; AC9)

**Why:** F9 builds the fields and the dialog; this task makes the prefix and Override All Links change the links.

**Depends on:** CL1, CL3; F9 9B, 9D, 9G; N2 answered.

**User action:** a new **Cloaked Link Prefix** changes every printed cloaked URL at once; old-prefix addresses keep
answering (N2). Switching **Link Cloaking** → "Update Link Settings" → **Override All Links** sets every product's flag.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| prefix history (`CloakPrefix`, `affibloom_cloak_prefix_history`) in `SettingsService::save()` | `SettingsController` |
| the `link_cloaking` arm of 9D's override (`PostTypeProductRepository::set_meta_for_all()`) | 9D's route |
| `CloakResolver`: old-prefix arm | `CloakedLinkRedirect` |

**Files:** the above; tests F24 AC9, F9 AC10 rerun; unit `CloakPrefixTest`.

- [x] **Step 1: Failing tests.**
- [x] **Step 2: History and override arm.**
- [x] **Step 3: Resolver arm.**
- [x] **Step 4: Run and look.**
- [x] **Step 5: Commit** — "Apply the cloaked link prefix and cloaking override".

*Note (2026-11-18):* *Note:* the history is a settings key (`cloak_prefix_history`), set by `SettingsService::update_tab('general')`; the old-prefix arm is a loop in `CloakedLinkRedirect::target()` (`CloakResolver` unchanged). The override maps `link_cloaking` → the `cloak` flag in `LinkDefaultsService`. Checked on Local: '' → go → shop, `/slug/`, `/go/slug/`, `/shop/slug/` all 302; Override All Links set 511 products off and on.

---

### Task CL6: The public link in front-end output

**Feature:** F24, F5 (R13, R14; D9, D13; AC10)

**Why:** Lasso's boxes and text links print the cloaked URL for cloaked products; F5's renderer uses the destination
until this exists.

**Depends on:** CL3 (`PublicLink`); F5 18A-18K (the box), the text-link shortcode (18S/27); F21 35H (later in this phase, after 26G) then reads it too.

**User action:** a post's box and text-link shortcode for a cloaked product link to `/<slug>/`; Amazon products to
Amazon; the post's stored content is unchanged.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `PublicLink::for()` injected into the display view model and the text-link renderer | F5's renderer, the shortcode, 35H |

**Files:** F5's view-model builder; tests F24 AC10 (content md5 unchanged), F5 box baselines rerun.

- [x] **Step 1: Failing test.**
- [x] **Step 2: Wire.**
- [x] **Step 3: Run and look.**
- [x] **Step 4: Commit** — "Link displays to the product's public link".

*Note (2026-11-18):* *Note:* wired in `BoxBuilder` (a `public_link` closure from `PublicLinks::for()`), which every box, button and text link builds through; a box's own `primary_url` is printed as given (Lasso's single.php/button.php). Checked on Local with do_shortcode: box, button and link print `/example-27/`; an Amazon product prints Amazon.

---

### Task CL7: The Dashboard row's link

**Feature:** F24, F19 (R14; AC11)

**Why:** the Dashboard's row link must open the address readers use, not the raw destination.

**Depends on:** CL3.

**User action:** each row's external link opens the public link.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `public_link` in the Dashboard list partial (`product-row.php`, D25 of F1 replaced) | `DashboardScreen`, the list route |

**Files:** `templates/admin/partials/product-row.php`, `src/Interface/Admin/DashboardScreen.php`, the list route; tests F24 **AC11**, F19's list specs rerun.

- [ ] **Step 1: Failing test** (AC11).
- [ ] **Step 2: Row.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Show the public link on the Dashboard".

---

### Task CL8: Products in the block editor's link search

**Feature:** F24 (R28; D18; AC12)

**Why:** in Lasso an editor finds a product in Gutenberg's link popover and inserts its public link.

**Depends on:** CL3 (`PublicLink`); `ProductRepository::search()` (F23 G4 / F19).

**User action:** typing a product's name in a link popover lists it ("Product"); choosing it inserts the public link.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Rest\ProductSearchHandler` (`WP_REST_Search_Handler`; `edit_posts`) via `wp_rest_search_handlers` | `/wp/v2/search` |

**Files:** the above, `Plugin.php`; tests F24 AC12, integration `ProductSearchHandlerTest`.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Handler.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Find products in the block editor's link search".

---

### Task CL9: Products in the classic editor's link dialog

**Feature:** F24 (R28; D18; AC13)

**Why:** editors using the classic editor must find a product and its public link as block-editor users do (R28).

**Depends on:** CL8 (search).

**User action:** the classic "Insert/edit link" search lists products with their
public link.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Admin\ClassicLinkSearch` (Module; `wp_link_query`) | WordPress |

**Files:** `src/Interface/Admin/ClassicLinkSearch.php`, `src/Plugin.php`; tests F24 **AC13**, integration `tests/Integration/ClassicLinkSearchTest.php`.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Filter.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Find products in the classic link dialog".

---

### Task CL10: Old addresses, and Lasso's hand-over

> **Changed 2026-11-17 (deviation 146):** this task builds the old-slug arm and the old-prefix history only.
> `CloakHandOver` (adopting Lasso's slugs, flags and settings) moves to Phase M's 26G, which writes the same stores
> through Affibloom's extension API (LM1); AC14 is Phase M's. The commit stays "Keep old cloaked addresses answering".

> **Needs F24 N1 and N2 answered first.**

**Feature:** F24, F10 (R6; D1, D19, N1, N2; AC7, AC14)

**Why:** renamed slugs and old prefixes must keep answering when no post is rewritten; and Task 26G needs the stores
this feature reads.

**Depends on:** CL1, CL3, CL5; N1/N2 answered. **Task 26G (F10) writes these stores; it is not part of this task.**

**User action:** `/aircat-volleyball-serving-machine/` (10001's old slug) and `/<old prefix>/<slug>/` answer like the
current address; after `wp affibloom migrate --only=slugs` the seven links in posts 5428, 4436, 4375 answer.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `CloakResolver`: old-slug arm (`PostTypeProductRepository::find_by_old_slug()`) | `CloakedLinkRedirect` |
| `Application\Cloak\CloakHandOver::adopt( product, slug, cloak, old_slugs )`, `adopt_settings( prefix, cloak, restrict )` (no flag is ever turned on; collisions returned) | Task 26G's `SlugsStep` |

**Files:** the above; tests F24 AC7 (old slug), AC14 (after 26G); unit `CloakHandOverTest`.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Resolver arm.**
- [ ] **Step 3: Hand-over service.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Keep old cloaked addresses answering".

---

### Task CL11: A pasted cloaked URL finds its product

**Feature:** F24, F18 (R36; AC15)

**Why:** the Add Link duplicate rule must treat a pasted address of this site's own cloaked link as the product it answers for (R36).

**Depends on:** CL1 (`CloakPath`, resolver); Task 11B (duplicate rule).

**User action:** **Add Link** with `https://<site>/aircat/` shows Lasso's duplicate result for that product.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LinkResolver`: own-domain path → `CloakResolver::product_for()` | `ProductService::create()`, `BulkAddService` |

**Files:** `LinkResolver`, `ProductService.php`, `BulkAddService.php`; tests F24 **AC15**, F18's Add Link duplicate specs rerun.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Resolver arm.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Recognise the site's own cloaked links when adding".

---

## Tasks: F27 — URL variants (moved here from Phase 2 on 2026-11-14)

> Written 2026-11-14 from a sweep of Lasso 369 (`docs/features/F27/requirements.md`, analysis in `F27/analysis.md`).
> 0 variants on this site; built in full under the full-scope rule. Gate 4: waiting. Every task: outside-in, its
> acceptance test first and red, `composer lint`, `composer analyse`, unit tests, the check by hand at 1280 and 375 px,
> one commit, pushed.

### Task V1: The Variants tab

**Feature:** F27 (R1, R2, R4-R9, R17, R23; D1, D3; AC1, AC2)

**Why:** the product page's Variants tab is "Coming soon" (F1 D4); Lasso lists a product's variants there.

**Depends on:** F1 (product page and its tabs, Phase 2); F24 CL3 (the product's slug and public link in `ProductResource`, earlier in this phase); TL2 (`Schema\{Migrator,Table}`, Phase 2).

**User action:** a product's page shows **Variants `<n>`** on every tab; the tab lists Name (URL under it), Permalink
or Tracking ID, Copy, Edit, with the tips; **Search** narrows the rows as you type; with none, Lasso's empty state
and **Add Variant**; a spinner while loading.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| table `affibloom_url_variants` (its schema step, added to TL2's `Migrator`) | `Upgrader` (TL2) on upgrade |
| `Domain\Variant\{Variant, VariantKind, VariantUrl, VariantRepository}`, `TableVariantRepository::for_parent(), count_for_parent()` | `VariantService` |
| `Application\Variants\VariantService::list(), count()` | `ProductScreen`, `VariantsController` |
| `GET /products/{id}/variants` in `VariantsController` | `variants.js` |
| the tab and count in `product.php`; partial `variants-tab.php`; `assets/admin/variants.js` (draw, search, states) | `ProductScreen` |

**Files:** the above; `src/Plugin.php`, `src/Interface/Admin/{ProductScreen,AdminAssets}.php`,
`docs/ui/design-system.md` (variants table row); tests `tests/E2E/acceptance/F27.spec.ts` (AC1, AC2; rows made
through WP-CLI `eval` into the table), unit `VariantUrlTest`.

- [ ] **Step 1: Failing tests.** Expected red: the tab is "Coming soon".
- [ ] **Step 2: Table (TL2's Migrator), domain, repository, service.** The parent link is CL3's public link (slug and prefix).
- [ ] **Step 3: Route, tab, partial, script.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Show a product's URL variants, as Lasso does".

---

### Task V2: Add, edit, remove and save variants

> **Changed 2026-11-17 (deviation 146):** the slug refusal reads "That slug is already a product's link." (no Lasso name
> in Affibloom, standing rule of 2026-11-14).

**Feature:** F27 (R3, R10-R16, R24, R25; D2, D5-D7, N1; AC3-AC5)

**Why:** Lasso edits variants in place and stores the whole list, refusing keys that would clash.

**Depends on:** V1; F24 CL3 (old slugs, `_affibloom_old_slugs`) for the uniqueness rule; N1 answered. The refusal "already a product's link / page" checks live posts, pages, products, the other variants and old slugs.

**User action:** **Add Variant** adds a row in edit mode (Lasso's name and key suggestion); **Save** stores the list
(spinner); a refusal shows under the key; **Remove** asks Lasso's question and stores the list without it; **Copy**
says "Copied!"; the tab count follows.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Variant\{VariantKey, VariantRules}` (R15 in order), `SlugTaken` port, `WpSlugTaken` | `VariantService::replace()` |
| `TableVariantRepository::replace()` (transaction) | the service |
| `PUT /products/{id}/variants`, `VariantInvalid` → 422 field errors; `Messages` (R15's texts, N1) | `variants.js` |
| edit/save/add/remove/copy in `variants.js`; the shared `delete-modal.php` (its next user) | the tab |

**Files:** the above; tests F27 AC3-AC5, unit `VariantRulesTest`, `VariantKeyTest`, integration
`TableVariantRepositoryTest`.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Rules and replace.**
- [ ] **Step 3: Route and script.**
- [ ] **Step 4: Run and look** (keyboard only; 375 px).
- [ ] **Step 5: Commit** — "Edit a product's URL variants".

---

### Task V3: A variant permalink answers

**Feature:** F27, F24 (R18, R24; D4; AC6)

**Why:** a permalink variant is a second cloaked address of its product.

**Depends on:** V2 (rows exist); F24 CL1 (resolver and redirect, earlier in this phase; CL1's own table says F27 V3 adds an arm).

**User action:** `/<variant key>/` lands on the product's destination with the product's headers and pass-through; a
product with that slug wins; the prefix does not apply.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `TableVariantRepository::parent_by_permalink_key()`; the variant arm of `CloakResolver` | `CloakedLinkRedirect` |

**Files:** the above; tests F27 AC6, F24 AC1-AC3 rerun.

- [ ] **Step 1: Failing tests.** An integration test for `parent_by_permalink_key()`; AC6's E2E case; F24 AC1-AC3 rerun.
- [ ] **Step 2: Repository lookup and the arm.** `TableVariantRepository::parent_by_permalink_key()` and the variant arm of `CloakResolver`.
- [ ] **Step 3: Run and look** (`curl -I` on a variant's permalink).
- [ ] **Step 4: Commit** — "Answer URL variant permalinks".

---

### Task V4: Amazon variant tags are kept

**Feature:** F27, F2 (R19; AC7)

**Why:** Lasso's formatter keeps a tag that is saved as a variant instead of replacing it with the site's tracking ID.

**Depends on:** V2; F2's `LinkNormaliser` tag rule; F17 (whitelist).

**User action:** saving an Amazon product (or Add Link) with `tag=<a variant's tag>` keeps that tag; boxes print it.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `TableVariantRepository::amazon_tag_exists()` (request cache) behind the normaliser's tag-allowed check | `LinkNormaliser`, the display formatter |

**Files:** `LinkNormaliser.php` and its port; tests F27 AC7, F2 AC rerun.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Port and arm.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Keep Amazon tags saved as URL variants".

---

### Task V5: Find products by their variants on the Dashboard

**Feature:** F27, F19 (R20; AC8; F19 D4's variant arm)

**Why:** Lasso's Dashboard search finds a product by its variants; F19 D4 left that arm for this feature.

**Depends on:** V1; F19's search (13D).

**User action:** searching a variant's name, key or number lists its product.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the variants `EXISTS` arms in `PostTypeProductRepository::page()` | the Dashboard list route |

**Files:** the repository; tests F27 AC8, F19 search specs rerun.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Arms.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Search the Dashboard by URL variants".

---

## Tasks: F21 — render-time link attributes and displays (after the cloaking block)

### Task 35H: Render-time link attributes — `the_content`

> **Changed 2026-11-17 (deviation 146):** a tied link gets Affibloom's own attributes at render time,
> `data-affibloom-id` (the product) and `data-affibloom-location` (its row), which F12's click script counts (33A,
> Phase 7); nothing is stored in the post and no `data-lasso-*` attribute is read.

**Feature:** F21 (R37-R40 by their visible result; D13-D15; AC8); F1 D13

**Why:** Lasso rewrites posts so text links follow a product; Affibloom prints the same at render time.

**Depends on:** 35A-35C; F1 public link and flags (Phase 2); F24 CL1-CL11 (earlier in this phase) for cloaked links: the filter reads `PublicLink::for()` (CL6).

**User action:** a reader sees a tied link with the product's current URL, `target`, `rel`; the stored post is unchanged.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| Domain `LinkIndex\RenderedAttributes` (pure) | `LinkAttributesFilter` |
| `Front\LinkAttributesFilter` (Module: `the_content` 20, `widget_block_content`, `widget_text`, `acf/format_value`, `elementor/frontend/the_content`, plus 35E's filters; reads F22's `assigned_product_id` before its own resolver when F22 N1-A is chosen) | WordPress |
| `RenderedLinks::for_host()` (rows by key, request cache) | the filter |

**Files:** the above; unit tests (every R38-R39 case); integration `LinkAttributesFilterTest`; E2E F21 AC8 (change a
product's NoFollow; the post shows it; content md5 unchanged).

- [ ] **Step 1: Failing tests.** AC8: a tied link shows the product's current attributes, an untied link is untouched, the stored post unchanged; red: no filter.
- [ ] **Step 2: `RenderedAttributes`.**
- [ ] **Step 3: The filter.**
- [ ] **Step 4: Run and look** (a guide on this copy: 662 Amazon links keep tag and `rel`).
- [ ] **Step 5: Commit** — "Give products' text links their current attributes at render time".

---

### Task 35J: SiteStripe, image-block and Kadence displays at render time — `render_block`

> **Changed 2026-11-17 (deviation 146):** the `[lasso]`-in-an-image-block case is not built (the migrator converts it,
> LM5), and the dependency on 27A-27I and 27X1-27X8 is gone. SiteStripe and Kadence stay; AC10 loses its `[lasso]` case.

**Feature:** F21 (R41 by the compatibility shortcodes, 27A-27I and 27X1-27X8, R45-R47; N2; AC10)

**Why:** Lasso converts these in storage; Affibloom draws the same at render time.

**Depends on:** 35E, 35H; Phase 3's box (Tasks 18A-22E); the user's N2 answer.

**User action:** with "SiteStripe scan" on, a tied SiteStripe iframe shows the product box and an image shows the
product image; a `[lasso]` in an image block shows the image; a Kadence button of a tied link shows the product's link.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Front\DisplayRenderFilter` (Module: `render_block` for `core/image`, `kadence/singlebtn`; `the_content` for SiteStripe) | WordPress |

**Files:** create `src/Front/DisplayRenderFilter.php`; modify `src/Plugin.php`; integration tests; E2E F21 AC10.

- [ ] **Step 1: Failing tests.** AC10: a tied SiteStripe iframe, an image block, a Kadence button; red: they print as stored.
- [ ] **Step 2: Filter.**
- [ ] **Step 3: Run and look.** One post of each kind on Local at 1280 and 375 px.
- [ ] **Step 4: Commit** — "Draw SiteStripe and block displays from products at render time".

---

## Exit check

The phase is done when all of these hold on the local copy and the user has signed it off:

- **Core without Lasso:** `NoLassoInCoreTest` passes with an empty allow-list (LC1, LC2); `grep -ri lasso` over `src/`,
  `templates/`, `assets/` and `blocks/` finds comments only.
- **F21 (the index):** AC1-AC10 (35A-35J; AC2 without the Lasso and AAWP families, AC5 without `<ta>`, AC10 without
  the `[lasso]` image-block case; AC4 and AC7 with 9I, 9H and 9L's controls), and F9 AC13 (9J), F38 AC7 (ON3) and AC8
  (ON4, the building notice only). F12's AC (36A-36E) stay in Phase 7.
- **F27:** AC1-AC8 (V1-V5). F10 AC15 (26H) is Phase M's.
- **F24:** AC1-AC13 and AC15 (CL1-CL11). AC14 (after 26G) is Phase M's.

Sign-off: the user has seen the index, the redirects and the variants on Local and signs the phase off. Nothing in
Phase 6 starts before that.

## Coverage

Every requirement and acceptance criterion of this phase's features and the task that owns it. "(Phase n)" marks a task outside this phase.

### Core without Lasso (deviation 146)

| What | Task |
|---|---|
| the one-time Lasso import (deviation 87) removed; the Amazon source renamed | LC1 |
| Lasso's markers, ED5's editor script (F4 R80-R84, AC20 withdrawn), other plugins' post types, Lasso's placeholder name and line-break code, `lasso-*` admin classes | LC2 |

### F21 (the index; F12's Locations rows stay in Phase 7)

| R / AC | Task |
|---|---|
| F21 R1-R4, R13, R22, R24, R25, R36, AC1 | 35A |
| F21 R5, R6, R27-R29, AC2 | 35B |
| F21 R10-R12, AC3 | 35C |
| F21 R33, R53, AC4 | 35D (the controls: 9I) |
| F21 R23, R26, R30-R32, AC5 | 35E |
| F21 R14-R18, R21, AC6 | 35F |
| F21 R51, R52, R54-R58, AC7 | 35G (the controls: 9H, 9L) |
| F21 R37-R40, R43, R48, R49, AC8 | 35H (by render-time result; writes not built, D3) |
| F21 R19, R60, R61, AC9 | 35I |
| F21 R41, R42, R44-R47, AC10 | 35J (R41's shortcode conversion: Phase M LM2-LM5 for Lasso's, other plugins' dropped; R42, R44: not built, D3) |
| F21 R7, R9, R35, R50 | not built: C (R7, R9), keyword opportunities left out (R35), nothing to undo (R50) — recorded decisions |
| F21 R8 | 35A (resolver follows retired ASINs; the table is F1's) |
| F21 R20 | 35C/35I/35D (each trigger's Affibloom equivalent: save hooks, product save, settings change); prefix change: F24 |
| F21 R59 | owners named (F12, F22, F25, C1) |
| F12 L1-L16, AC2, AC3, AC20-AC26 | 36A-36E (→ Phase 7) |

F21 R34 (WP Coupons discount URL rewritten in meta) has no row and no visible link in content; it is a meta write (D3) and is **unowned at render time**: the coupon plugin prints it; 35E records whether a filter exists (N1).

### F9 (Link Index card) and F38 (ON3, ON4)

| R / AC | Task |
|---|---|
| F9 R20-R21, AC13 | 9J |
| F38 R40-R43, AC7 | ON3 |
| F38 R25, R26, AC8 | ON4 |

### F24

| R / AC | Task |
|---|---|
| R1, R15-R17, R19, R21, R24-R27; AC1-AC3 | CL1 |
| R22, R23 (C, by leaving out); AC4 | CL2 |
| R4-R11, R13, R14 (product page, Share kit, preview); AC5-AC7 (AC7's old-slug half: CL10) | CL3 |
| R3, R12; AC8 | CL4 |
| R30, R31; AC9 | CL5 |
| R14 (displays, text links); AC10 | CL6 |
| R14 (Dashboard); AC11 | CL7 |
| R28 (block editor); AC12 | CL8 |
| R28 (classic); AC13 | CL9 |
| R6 (old slugs), D19; AC7, AC14 | CL10 (the migration half: 26G, Phase M) |
| R36; AC15 | CL11 |
| R2 | CL1 (by D2: no rewrite needed) |
| R18, R29 | not built: imports from other plugins are dropped (deviation 146) |
| R20, R34, R37 | not built, D17 (same visible result) |
| R32 | the quick-detail panel's owner (F22/F4), reusing CL3's partial |
| R33 | CL3 (a lasso.to URL never gets the flag) |
| R35 | uninstall (F9 R64) |
| R14 (scanner writes into posts, coupons meta, tables, importer) | F21 35H (render time), F25 (tables); never a content write |

### F27 (URL variants; moved here from Phase 2)

| R / AC | Task |
|---|---|
| F27 R1, R2, R4-R9, R17, R23; AC1, AC2 | V1 |
| F27 R3, R10-R16, R24, R25; AC3-AC5 | V2 |
| F27 R18; AC6 | V3 |
| F27 R19; AC7 | V4 |
| F27 R20; AC8 | V5 |
| F27 R21 | no task: Lasso has no such matching (D8) |
| F27 R22 | no task: unused code in Lasso |
| F27 the copy of Lasso's rows | 26H (Phase M) |
