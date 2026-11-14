# Affibloom — Phase 5: Lasso compatibility and migration — Implementation Plan

> **How to run this plan:** inline, in one working session, task by task, in the order below. Do not hand tasks to subagents. When a step is finished, change its `- [ ]` to `- [x]`.
>
> **Explain as you go:** before each task, say in two or three sentences what the task builds, why it is needed now, and how it will be checked. After the task, say what changed and what the verification showed. No vibe coding: every line written must be one you can explain.
>
> **Shared rules:** the architecture, working method, decisions, global constraints, file structure and API contract are in [`00-overview.md`](00-overview.md). Read it first.

## Status

Planned; gate 4 given by the user 2026-11-14 for every open task. One phase per session; do not start Phase 5 until Phase 4's exit check is met and signed off. Every run in this phase happens on the local copy, then on staging (Task 40); production comes only in Task 41. F10 is the highest-risk feature; ADR-3 was revised 2026-11-14 (no conversion; Affibloom registers `affiliate-plugin/lasso`).

## Scope and features

- F10 — Lasso's data migrated (dry run, products, settings, fields, groups, variants, Amazon, saved views and the tour and education copies, admin run, checks, parity; no content conversion, decision A1): `wp-content/plugins/affibloom/docs/features/F10/` (`requirements.md`, `analysis.md`).
- F31 (legacy part) — `[lasso]`, `[lasso-attr]` and the competitors' shortcodes with Lasso off: `wp-content/plugins/affibloom/docs/features/F31/`. The `[affibloom]` shortcode itself (18S1-18S14) is Phase 3. Task 27E (`[lasso type="table"]`) moves to Phase 7.
- F24 — link cloaking, redirects, permalinks, old addresses: `wp-content/plugins/affibloom/docs/features/F24/`.
- F34 — content changes, History, repair tools (no tasks: decisions A1, A2 and the N-items are recorded; its requirements say why): `wp-content/plugins/affibloom/docs/features/F34/`.
- F33 — import from other plugins (IM1-IM19; IM11 and IM20 move to Phase 7): `wp-content/plugins/affibloom/docs/features/F33/`.
- F21 — the content link index (35A-35G, 35I; 35H and 35J after the cloaking block; moved here from Phase 7 on 2026-11-14 because Phase 5's imports and parity need it; the Locations screens 36A-36E stay in Phase 7): `wp-content/plugins/affibloom/docs/features/F21/`.
- F9 (part) — Task 9J, the Link Index card: `wp-content/plugins/affibloom/docs/features/F9/`.
- F38 (part) — Tasks ON3 (Automations) and ON4 (link index notices): `wp-content/plugins/affibloom/docs/features/F38/`.
- F27 — URL variants V1-V5 (moved here from Phase 2 on 2026-11-14, after the cloaking tasks they depend on): `wp-content/plugins/affibloom/docs/features/F27/`.
- F4 (part) — Task ED6, editing legacy `affiliate-plugin/lasso` blocks (decision A1; moved here from Phase 4 on 2026-11-14): `wp-content/plugins/affibloom/docs/features/F4/`.

## Decisions that change this phase

- **F29, any number of buttons (the user, 2026-11-13; register U12):** a product has an ordered list of buttons after the primary (`Product::$buttons`, Task 14H). Task 26A maps Lasso's `second_btn_url`, `second_btn_text`, `open_new_tab2`, `enable_nofollow2` to the first entry of `_affibloom_buttons` (F29 B8); `secondary_url` / `secondary_text` shortcode overrides apply to that first entry (B6).

Decided 2026-11-12; reasons in `docs/decisions/task-6-decisions.md` in the plugin repository. Apply them when the task is worked, and record any code that differs from the text below as a deviation in `progress.md`.

- Task 26A: `check_duplicate_link` is not imported (no `allow_duplicates`, decision 2); button texts and badges keep their icon spans (decision 5). (F10 N5 below asks to reverse decision 2.)
- Tasks 18S2 / 27A: `title_type` accepts `h2`-`h6`; anything else (the site's one use, "Best Lightweight") is ignored and logged, not turned into a badge (decision 7). (F31 N2 below asks to accept `h1` too.)
- The one-time "Import from Lasso" Dashboard button (commit 447bf1b, `progress.md` deviation 87) stays until 26O retires it.
- Staging has no SSH: every migration step needs an admin entry point (25D, 26O).

**Decisions (the user, 2026-11-14):** each item below is the user's answer, or the recommendation, which the user accepted.

- **Decided (the user, 2026-11-14), A1:** register `affiliate-plugin/lasso` ourselves (Task ED6) and convert nothing; Tasks 28A-28E are dropped; ADR-3 revised.
- **Decided (the user, 2026-11-14), E1 (revised): start from zero.** No click figures are imported from Lasso; Task 26J is dropped.
- **Decided (the user accepted the recommendation, 2026-11-14):** never copy the old PA-API key pair; copy Creators credentials if present, shown only as "set" (F10 N3, recommended: as stated).
- **Decided (the user accepted the recommendation, 2026-11-14):** keep skipping the 9 records with no link and list them, or create them from the ASIN in their slug (F10 N4, recommended: skip and list).
- **Decided (the user accepted the recommendation, 2026-11-14):** reverse Task 6 decision 2 and import "Allow duplicate destinations" (`check_duplicate_link`) once F9 builds the control (F10 N5, recommended: reverse it).
- **Decided (the user accepted the recommendation, 2026-11-14):** migrate stale Amazon values (name, image, price, Prime) as the products' own until C1 has credentials (F10 N6, recommended: as decided in deviation 18).
- **Decided (the user accepted the recommendation, 2026-11-14):** `[lasso prompt]` prints Lasso's cached AI text, else nothing; `[affibloom]` offers no `prompt` (F31 N1, recommended: as stated; task 27F).
- **Decided (the user accepted the recommendation, 2026-11-14):** accept `title_type="h1"` (F31 N2, recommended: accept).
- **Decided (the user accepted the recommendation, 2026-11-14):** links that only other plugins render (Recipe Maker, Mediavine Create, TablePress, Beaver Builder, Thrive) keep the attributes stored when Lasso last scanned where no output filter reaches them (F21 N1, recommended: accept; task 35E).
- **Decided (the user accepted the recommendation, 2026-11-14):** Lasso's display conversions in content (other plugins' shortcodes to `[lasso]`, SiteStripe iframes to a product box, a `[lasso]` in an image block to an image) are done at render time (F21 N2, recommended: render time; tasks 27A-27I, 27X1-27X8, 35J). F21 N3 (keep Lasso's stamps) is the F34 N3 item above.
- **Decided (the user accepted the recommendation, 2026-11-14):** the variant-slug refusal wording, "That slug is already a Lasso link." (F27 N1, recommended: "That slug is already a product's link."; decides Task V2's message).
- **Decided (the user accepted the recommendation, 2026-11-14):** keep Lasso's `compact="hide"` no-op for `[lasso]`; `[affibloom]` accepts `true` only (F31 N3, recommended: as stated).
- **Decided (the user accepted the recommendation, 2026-11-14):** build Easy Affiliate Links' REST and block takeover, active only with EAFL (F31 N4, recommended: build it; task 27X6).
- **Decided (the user accepted the recommendation, 2026-11-14):** an old slug answers exactly as the current one (F24 N1, recommended: yes; tasks CL3, CL10).
- **Decided (the user accepted the recommendation, 2026-11-14):** old cloak prefixes stay in a history and keep answering (F24 N2, recommended: yes; tasks CL5, CL10).
- **Decided (the user accepted the recommendation, 2026-11-14):** flag and slug changes reach stored content only at render time, not in other plugins' stored HTML (F24 N3, recommended: accept).
- **Decided (the user accepted the recommendation, 2026-11-14):** do not copy Lasso's switching off of `wp_old_slug_redirect` (F24 N4, recommended: do not copy).
- **Decided (the user accepted the recommendation, 2026-11-14):** leave Lasso's cloud in the redirect out; keep the local `sid` to sub-ID rewrite (F24 N5, recommended: as stated).
- **Decided (the user accepted the recommendation, 2026-11-14):** longer cloaked paths (`/a/b/<slug>/`) are not matched (F24 N6, recommended: accept).
- **Decided (the user accepted the recommendation, 2026-11-14):** wording of the slug-conflict warning (F24 N7, recommended: "Permalink <slug> is being used by <type>. This product's link will not open until you change it.").
- **Decided (the user accepted the recommendation, 2026-11-14):** drop Lasso's History toggle and 14-day purge (F34 N1, recommended: drop both).
- **Decided (the user accepted the recommendation, 2026-11-14):** Lasso's 18 history rows are not shown or revertible (F34 N2, recommended: accept).
- **Decided (the user, 2026-11-14), A2:** keep the `data-lasso-*` stamps; H3-H4 are dropped.
- **Decided (the user, 2026-11-14), A3:** monetize at render time plus "Edit in post" (F22 O10); nothing rewrites the post.
- **Decided (the user accepted the recommendation, 2026-11-14):** not built because nothing rewrites: Fix Overwritten Links, the Yoast FAQ fix, scan-time content fixes, the data scripts (F34 N5, recommended: accept all).
- **Decided (the user accepted the recommendation, 2026-11-14):** reverting from History refuses a post edited since; `--force` stays in WP-CLI (F34 N6, recommended: accept).
- **Decided (the user accepted the recommendation, 2026-11-14):** imports do not rewrite posts; shortcodes render through F31's handlers and old addresses redirect (F33 N1, recommended: accept).
- **Decided (the user accepted the recommendation, 2026-11-14):** Revert deletes the imported product, refused when Affibloom's blocks use it (F33 N2, recommended: as stated).
- **Decided (the user accepted the recommendation, 2026-11-14):** import copies instead of moves (F33 N3, recommended: copy, with the message to deactivate the source).
- **Decided (the user accepted the recommendation, 2026-11-14):** import Amazon items the source never cached as "Amazon" with no price until C1 has credentials (F33 N4, recommended: import them).
- **Decided (the user accepted the recommendation, 2026-11-14):** leave out the site-specific Pretty Links and ACF repairs (F33 N5, recommended: leave out).
- **Decided (the user accepted the recommendation, 2026-11-14):** after IM2, import the one link guide 3297 uses (or all 49 ThirstyAffiliates links) on staging and close Task 41 step 8 that way (F33 N6, recommended: as stated).

## Tasks in this phase

Grouped by feature, in the order they are worked. A task depends only on tasks in its own or an earlier phase, except where Cross-phase notes say which part waits.

**F10 — Lasso's data, migrated: dry run, products, settings, fields and the admin run**

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
- [ ] Task 26O: Run from the admin page, in batches
- [ ] Task 26P: Check the migrated products against the baselines

**F21 — the content link index (moved here from Phase 7; 35H and 35J come after the cloaking block)**

- [ ] Task 35A: The index of anchors in post content — `wp affibloom links rebuild`
- [ ] Task 35B: Displays in the index — shortcodes and blocks
- [ ] Task 35C: Saving, trashing and deleting a post updates its rows — `save_post`
- [ ] Task 35D: Custom Link Detection — Settings → General
- [ ] Task 35E: Links in builders and recipe plugins — `wp affibloom links rebuild --post`
- [ ] Task 35F: The background rebuild and the daily pass — WP-Cron `affibloom_link_index_batch`
- [ ] Task 35G: Settings → General — Link Index and Performance

**F9 (part) — the Link Index card**

- [ ] Task 9J: Link Index card

**F38 (part) — Automations and link index notices**

- [ ] Task ON3: Automations — what is running in the background
- [ ] Task ON4: Link index notices

**F21 — a product's save and repoint**

- [ ] Task 35I: A product's save and repoint keep its rows — `PATCH /products/{id}`

**F31 — `[lasso]` and the competitors' shortcodes with Lasso off (legacy part)**

- [ ] Task 27A: `[lasso]` alias — the single box
- [ ] Task ED6: Edit legacy `affiliate-plugin/lasso` blocks (F4's task, moved here from Phase 4; decision A1: built, no conversion)
- [ ] Task 27B: `[lasso]` button, image, link, SiteStripe and fields
- [ ] Task 27C: `[lasso amazon_url|geni_url]`
- [ ] Task 27D: `[lasso]` group displays
- Task 27E: moved to Phase 7 (`[lasso type="table"]`; needs F25 T12 and 26I)
- [ ] Task 27F: `[lasso prompt]` — Lasso's cached AI text (needs F31 N1)
- [ ] Task 27G: Lasso's render-time content repairs
- [ ] Task 27H: `[lasso]` in widgets and on AMP
- [ ] Task 27I: `[lasso-attr]`
- [ ] Task 27X1: `[amazon]` and `[aawp]`
- [ ] Task 27X2: `[amalinkspro]`
- [ ] Task 27X3: EasyAzon links
- [ ] Task 27X4: EasyAzon image, CTA and info block
- [ ] Task 27X5: Earnist
- [ ] Task 27X6: Easy Affiliate Links (needs F31 N4)
- [ ] Task 27X7: ThirstyAffiliates
- [ ] Task 27X8: `[lasso]` back to the original plugin's shortcode

**F24 — Link cloaking, redirects and permalinks (with F10's slugs step 26G)**

- [ ] Task CL1: The redirect for a cloaked path
- [ ] Task CL2: Query strings through the redirect
- [ ] Task CL3: Permalink and Link Cloaking on the product page
- [ ] Task CL4: A new product's slug and cloaking
- [ ] Task CL5: The General settings take effect
- [ ] Task CL6: The public link in front-end output
- [ ] Task CL7: The Dashboard row's link
- [ ] Task CL8: Products in the block editor's link search
- [ ] Task CL9: Products in the classic editor's link dialog
- [ ] Task CL10: Old addresses, and Lasso's hand-over
- [ ] Task CL11: A pasted cloaked URL finds its product

**F10 — the slugs step, after F24's stores exist**

- [ ] Task 26G: Cloaked addresses — `wp affibloom migrate --only=slugs`

**F27 — URL variants (moved here from Phase 2; after the cloaking tasks)**

- [ ] Task V1: The Variants tab
- [ ] Task V2: Add, edit, remove and save variants
- [ ] Task V3: A variant permalink answers
- [ ] Task V4: Amazon variant tags are kept
- [ ] Task V5: Find products by their variants on the Dashboard

**F10 — URL variants migration, after V5**

- [ ] Task 26H: URL variants — `wp affibloom migrate --only=variants`

**F21 — render-time link attributes and displays (after 26G)**

- [ ] Task 35H: Render-time link attributes — `the_content`
- [ ] Task 35J: SiteStripe, image-block and Kadence displays at render time — `render_block`

**Dropped by the user's decisions (2026-11-14): 28A-28E (A1), H1-H4 (A1 and A2), 29D (needs the conversion).** No post
content is converted or stripped. Sections: `archive/phase-5-superseded.md`.

**F33 — Import from other plugins**

- [ ] Task IM1: The Import screen, with ThirstyAffiliates' links
- [ ] Task IM2: Import one, revert one — ThirstyAffiliates
- [ ] Task IM3: Import All and Revert All in the background
- [ ] Task IM4: Pretty Links
- [ ] Task IM5: Lasso Lite / Simple URLs
- [ ] Task IM6: Earnist
- [ ] Task IM7: Affiliate URLs
- [ ] Task IM8: Easy Affiliate Links
- [ ] Task IM9: AAWP products
- [ ] Task IM10: AAWP lists
- Task IM11: moved to Phase 7 (AAWP tables; needs F25's tables)
- [ ] Task IM12: EasyAzon
- [ ] Task IM13: AmaLinks Pro
- [ ] Task IM14: SiteStripe
- [ ] Task IM15: WP Recipe Maker
- [ ] Task IM16: Skimlinks, Genius, Tinyurl, Go.
- [ ] Task IM17: Lasso as a source
- Task IM18: moved to Phase 7, after 36B (settled 2026-11-14)
- [ ] Task IM19: Import notices and the first-build lock
- Task IM20: moved to Phase 7 (WP All Import; its Export-CSV half needs F11 31D)

**F10 — parity and the rollback drill**

- [ ] Task 29A: Parity — every box on every guide
- [ ] Task 29B: Parity — whole pages
- Task 29D: dropped (decision A1)
- [ ] Task 29E: Rollback drill

## Cross-phase notes

**Earlier phases (named; all must be done first):**

- Phase 2: F1's products and Add Link (Tasks 11A-17D, 14H buttons, 12 Amazon link service, 11B duplicate rule, 15A `PATCH` and the Share kit); F9 (Task 9 and 9B, 9D, 9G, 9H, 9I, 9K, 9L: the controls and keys that 35D, 35G and 9J build on); F16/F17 (10A-10F); F19 (13C, 13D, 13E saved views); F23 Groups G1-G5 (26F, H1, IM10, 35B); F26 (14C, FL1-FL5; 26E); TL1 and TL2 (activation and `Schema\{Migrator,Table}`: the tables of 35A and V1 use them); ON1, ON2 (for ON3, ON4); C1A-C1D, C1I, C1K-C1P (26L).
- Phase 3: the box (Tasks 18A-22E, and the DT* displays) for 26P, 27A, 29A, 35J; `[affibloom]` shortcode tasks 18S1-18S9 for 27A-27D and 27H (the renderer, `LegacyAddress`'s targets, `WidgetShortcodes`, `AmpBoxCss`); 18S4-18S7 for 27B-27D; 18S5 (text-link shortcode) for CL6.
- Phase 4: the block, Tasks 23A-23G, and 24A, 24C (ED6, which moved here from Phase 4).

**Resolved by the moves of 2026-11-14 (the earlier "waiting on Phase 7" items):**

- **The link index is in this phase** (35A-35G, 9J, ON3, ON4, 35I after the F10 core; 35H and 35J after 26G), so these no longer wait: IM13 (35B), IM14 (35J), IM15 (35E), IM16 (35A's `affiliate_platform` column; no longer UNRESOLVED), IM19's first-build lock (35F), IM2's use of 35I (repoint keeps rows), CL6's later reader 35H (it follows 26G in this phase). 26C maps F21's scan keys with 35D and 35G.
- **IM18 still waits for Phase 7:** it reads the index (here) but its Locations query is F12 36B, which stays in Phase 7 with 36A-36E. The task stays unticked until 36B exists.
- **35A's table** includes the columns other features need: `affiliate_platform` (IM16) and `assigned_product_id` (F22 N1-A, kept across a rescan); the F21 draft did not list them, they come from F21's analysis and F22's analysis. **35C** fires the event `affibloom_link_index_indexed` that F35 (Phase 7) listens to.
- **ED6 moved here from Phase 4** (right after 27A, which it depends on). Decision A1 (the user, 2026-11-14): Affibloom registers `affiliate-plugin/lasso` itself and converts nothing; ED6 is built; Tasks 28A-28E, 29D, F10 AC3 and AC25-AC28, 29E's convert/revert steps and F34 H1-H4 are dropped (H1-H2 listed only conversion changes; H3-H4 are decision A2).
- **27E, IM11 and IM20 moved to Phase 7** (text in `archive/moving-to-phase-7.md`): 27E (`[lasso type="table"]`) needs F25 T12 and 26I; IM11 (AAWP tables) needs F25's tables; IM20's Export-CSV half needs F11 31D. Until 27E exists a `[lasso type="table"]` prints nothing (the draft's rule for an unknown table). The table parts of 27X1 and 28D are folded into one note: the table branch is built in 27E (Phase 7); until then boxes of type `table` stay unconverted and are reported "waiting for F25".
- **26H after 26G:** 26H (variants migration) now follows V5 and 26G (it needed 26G); the order is CL1-CL11, 26G, V1-V5, 26H, then 35H and 35J.
- **V1-V5 moved here from Phase 2** (after the cloaking tasks): V1 reads CL3's public link, V2 uses CL3's old slugs, V3 adds its arm to CL1's `CloakResolver`; their stubs are gone.
- **26C (settings keys):** 26C maps the General keys that exist in Phase 5; F21's scan keys are mapped with 35D and 35G here; F22, F35 and C3 keys are mapped by 26N in Phase 7.
- **26M (saved views, tour flag, education boxes):** the filters of F11 (Phase 6) and F25 (Phase 7) do not exist when 26M runs; an unknown filter is dropped with a note. 26M also copies `lasso_dashboard_tour_done` (for F38 ON8, Phase 8) and the closed education boxes (for F38 ON5, Phase 8; key `affibloom_settings['education_hidden']`, settled over F22's `affibloom_hidden_intros`).
- **Settings overlap settled (2026-11-14):** 9H/9I/9L (Phase 2) build the controls under F9 D3's keys; 35D and 35G build only the effect, 9J the card's figures; no second card.
- **ON4:** `RescanAfterImportNotice` reads F33's import (IM2, IM3, later in this phase); until then it shows nothing.

**Later phases (which part waits, and how):**

- **26I, 26J, 26K, 26N** are not in this phase: 26K moves to Phase 6, 26N and 26I to Phase 7; 26J is dropped (decision E1). `wp affibloom migrate --dry-run` reports their families as "waiting for <feature>" (25A-25C) and `--only` refuses them until built.
- **29C** (parity of every link, and no call to Lasso) is Phase 7. **29D** is dropped (decision A1). **29E** (rollback drill) depends on 29A-29B.
- **IM18** needs F12 36B (above); nothing else in the import tasks waits for a later phase.
- **The one-time "Import from Lasso" Dashboard button** (commit 447bf1b, `progress.md` deviation 87) is retired by **26O** (the Dashboard card, `LassoImportController`, `LassoImportService` and its tests are removed); 26A adopts products it created (`affibloom_lasso_import` option).
- **Staging has no SSH:** every migration step needs the admin entry point: **25D** (the report), **26O** (the run, in batches).
- **Phase 9 (Tasks 40-42)** reads `docs/cutover/rollback.md` (29E) and the fingerprint tool (26A).

**UNRESOLVED names:** none. Names checked against the phase map and found: every task id named in this phase (including F21 35A-35J, F12 36B, F11 31D, F25 T12, F9 9B/9D/9G/9H/9I/9K/9L, F23 G1-G5, C1A-C1P, 13C-13E, 18S1-18S9, 23A-23G, TL2).

---

## Tasks: F10 — Lasso's data, migrated: dry run, products, settings, fields and the admin run

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

## Tasks: F21 — the content link index (moved here from Phase 7 on 2026-11-14; 35H and 35J follow the cloaking block)

> Written 2026-11-14 from a sweep of Lasso 369's link index (`docs/features/F21/requirements.md`, F21 analysis in
> `F21/analysis.md`). They replace Phase 7's Tasks 35A-35C. Gate 4: waiting. Every task: outside-in, its
> acceptance test first and red, `composer lint`, `composer analyse`, unit tests, the check by hand at 1280 and 375 px
> where there is a screen, one commit, pushed. No task writes post content (F21 D3). Order in this phase: 35A, 35B, 35C,
> 35D, 35E, 35F, 35G, 9J, ON3, ON4, 35I here (after the F10 core); 35H and 35J after Task 26G.

### Task 35A: The index of anchors in post content — `wp affibloom links rebuild`

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

- [ ] **Step 1: Failing tests.** AC1 on a fixture post (text, image, internal, external, `#`, `mailto:`, an ignored
  class, a FAQ block, a 1,200-character anchor), rows read through `wp eval`, `post_content` md5 unchanged. Red: no command.
- [ ] **Step 2: Domain** (R2's exact flag rule, R25's types, platforms, occurrence).
- [ ] **Step 3: Resolver** (F21 D8 order; published only; lasso.to → untied).
- [ ] **Step 4: Table (TL2's Migrator), repository** (`replace_host` in a transaction, `is_ignored` and `assigned_product_id` kept, D10).
- [ ] **Step 5: Scanner, source, indexer, command.**
- [ ] **Step 6: Run and look.** `--dry-run` on this copy; compare with requirements §2 per host; record differences.
- [ ] **Step 7: Commit** — "Index every link in post content".

---

### Task 35B: Displays in the index — shortcodes and blocks

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

- [ ] **Step 1: Failing tests.** AC2 per shortcode family, a grid with a group, the blocks; red: no display rows.
- [ ] **Step 2: Shortcodes.**
- [ ] **Step 3: Blocks.**
- [ ] **Step 4: Run and look** (223 Single and 2 Table rows here).
- [ ] **Step 5: Commit** — "Index boxes and group displays".

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

- [ ] **Step 1: Failing tests.** AC3 (save, schedule, draft, trash, restore, delete) and the event fired once per host; red: no hooks.
- [ ] **Step 2: Hooks and the event.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Keep a post's link rows in step with the post".

---

### Task 35D: Custom Link Detection — Settings → General

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

- [ ] **Step 1: Failing tests.** AC4 with a test CPT: a post of the chosen type is indexed, one of another type is not; red: the scan ignores the keys.
- [ ] **Step 2: Keys and source.**
- [ ] **Step 3: The effect (the rebuild on change).**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Choose custom post types and fields to scan".

---

### Task 35E: Links in builders and recipe plugins — `wp affibloom links rebuild --post`

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

- [ ] **Step 1: Failing tests.** AC5: a fixture per source (plugin absent → skipped); red: no rows for these hosts.
- [ ] **Step 2: Sources.**
- [ ] **Step 3: Hooks.**
- [ ] **Step 4: Record the output filters (N1).**
- [ ] **Step 5: Run and look.** `wp affibloom links rebuild --post=<id>` on a fixture post per available plugin; none is active on this site, so the E2E skips are listed.
- [ ] **Step 6: Commit** — "Index links in builders and recipe plugins".

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

- [ ] **Step 1: Failing tests.** AC6 (batches, resume, CPU skip, daily pass); red: no cron module.
- [ ] **Step 2: State and batches.**
- [ ] **Step 3: Cron module.**
- [ ] **Step 4: Run and look.** `wp cron event run affibloom_link_index_batch` on this copy; `wp affibloom links status` before and after.
- [ ] **Step 5: Commit** — "Rebuild the link index in the background".

---

### Task 35G: Settings → General — Link Index and Performance

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

- [ ] **Step 1: Failing tests.** AC7 (status, rebuild, 409 while running, the keys followed); red: no route.
- [ ] **Step 2: Routes and status.**
- [ ] **Step 3: The keys' effect and the dialog's call.**
- [ ] **Step 4: Run and look** (1280, 375 px).
- [ ] **Step 5: Commit** — "Show and rebuild the link index from Settings".

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

- [ ] **Step 1: Failing test.** AC13 against 35G's route: empty body at 0, figures after an index, progress and ETA while building; red: no card.
- [ ] **Step 2: Card and poller.**
- [ ] **Step 3: Run and look.** On Local after the first index: the card at 1280 and 375 px.
- [ ] **Step 4: Commit** — "Show the link index on Settings → General".

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

- [ ] **Step 1: Failing tests** (AC7: a bulk add of three links started through REST; the count, the line, the idle
  text).
- [ ] **Step 2: Board and status.**
- [ ] **Step 3: Route, header, script.**
- [ ] **Step 4: Run and look.** At 1280 and 375 px: start a bulk add, see the count and the line, wait for the idle text.
- [ ] **Step 5: Commit** — "Show running background jobs in the header".

---

### Task ON4: Link index notices

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

- [ ] **Step 1: Failing tests.** AC8: the notice during a build with its link; the after-import notice; red: no providers.
- [ ] **Step 2: Providers.**
- [ ] **Step 3: ETA refresh.**
- [ ] **Step 4: Run and look.** Start a rebuild on Local: the notice on a WordPress admin page, the link to Settings; at 1280 and 375 px.
- [ ] **Step 5: Commit** — "Tell editors while the link index is being built".

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

- [ ] **Step 1: Failing tests.** AC9: a new Primary URL, a repoint; rows follow, `post_content` unchanged; red: rows keep the old URL.
- [ ] **Step 2: Calls.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Move a product's link rows with its URL and repoint".

---

## Tasks: F31 — `[lasso]` and the competitors' shortcodes with Lasso off (legacy part)

### Task 27A: `[lasso]` alias — the single box

**Feature:** F31 (R1 `lasso`, R4-R6, R14, R20, A1-A5, A15-A17, A53, A54, D3, D4; AC15); F10 (AC2)

**Why:** with Lasso off, WordPress renders the 250 blocks' saved `[lasso …]`; something must answer (ADR-3).

**Depends on:** 25A (`LassoIdMap`), 26A (products migrated), 18S1-18S3 (the renderer and every single-box attribute).

**User action:** with Lasso deactivated, each of the 34 guides shows every box as before: badge, description, title,
`title_url`, `title_type`, image, buttons, `data-lasso-id`; reactivating Lasso hands `[lasso]` back at once.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Legacy\LassoShortcode` (`init` 20, only without Lasso) | WordPress |
| `Application\Legacy\LegacyAddress` (slug > id > link_id > ref; repoint) | `LassoShortcode` |
| `Infrastructure\Lasso\LassoLocationMap` (`link_id` with no `id`) | `LegacyAddress` |
| R6 (AAWP box description from the location's original shortcode) | `LegacyAddress` |

**Files:** create the above, `tests/Integration/LassoShortcodeTest.php` (the two tests of the superseded Task 27A, now in the archive, and one per address rule); modify `Plugin.php`, `BoxAssets.php` (`[lasso`, the legacy block).

- [ ] **Step 1: Failing tests** (the superseded Task 27A's two tests, plus `ref` only, `link_id` only, `slug`, repoint, `&quot;` values, a positional attribute).
- [ ] **Step 2: Alias and address.**
- [ ] **Step 3: Run and look** (Lasso off, one guide beside its baseline; Lasso on again).
- [ ] **Step 4: Commit** — "Render [lasso] boxes from migrated products when Lasso is off".

---

### Task ED6: Edit legacy `affiliate-plugin/lasso` blocks (decision A1, the user, 2026-11-14)

> Moved here from Phase 4 on 2026-11-14, word for word. It depends on 27A only (the `[lasso]` alias, just above); 27B-27D are not needed and 27E is in Phase 7. It replaces the dropped conversion 28A-28E (decision A1).

**Feature:** F4 (R1-R4, R50; AC21)

**Why:** without Lasso's script the 248 existing blocks show as "unsupported" in the editor; this registers the block
name so they stay editable, with no conversion of `post_content` (N2).

**Depends on:** 24A, 24C; Task 27A (`[lasso]` compatibility, earlier in this phase), which the "renders unchanged" half of AC21 uses. Decided: built (the user, 2026-11-14).

**User action:** existing `affiliate-plugin/lasso` blocks open as working blocks (preview, sidebar, **Edit Display**)
and render unchanged on the site; the block is hidden from the inserter. The 2 bare shortcodes get no editor UI (R50).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Editor\LegacyLassoBlock` (`register_block_type( 'affiliate-plugin/lasso', supports.inserter=false )`, Lasso's attributes and save format) | `Plugin` |
| the legacy block's `edit` reusing the panel, sidebar, preview | the editor |

**Files:** create `LegacyLassoBlock.php`, `blocks/legacy-lasso/`; modify `Plugin.php`; tests
`tests/E2E/acceptance/F4.spec.ts` (AC21, fixture post 2622), `tests/Integration/LegacyBlockTest.php`.

- [ ] **Step 1: Failing test** (fixture post 2622: the block opens without the "unsupported" notice; the sidebar
  shows the badge; unchanged render).
- [ ] **Step 2: Register the name, hidden from the inserter, Lasso's save format.** Save canonicalises as R3.
- [ ] **Step 3: Run and look.** By hand with post 2622 and one of the 248.
- [ ] **Step 4: Commit** — "Edit the site's existing Lasso blocks".

---

### Task 27B: `[lasso]` button, image, link, SiteStripe and fields

**Feature:** F31 (A10-A14, A40-A43, R10, R11; AC16)

**Why:** with Lasso off, the lone button, image and link forms, SiteStripe, inline fields and the AAWP-style link attributes must still draw (R10, R11).

**Depends on:** 27A, 18S4, 18S5.

**User action:** `[lasso type="button|image|image-link|link"]`, `style="image"`, `sitestripe="true"`, `fields="<x>"
value="<part>"` draw as in Lasso; AAWP-style `link="<ASIN>"` or EasyAzon `identifier="<ASIN>"` (without `align`) give
the plain link.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LegacyAddress` maps `fields` + `value` to `DisplayKind::Field` and A41-A43 to `DisplayKind::Link` | `LassoShortcode` |

**Files:** F31 **AC16**; `LegacyAddressTest`. Create the classes named above; modify `src/Plugin.php` where a Module is registered.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Mapping.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Render [lasso] buttons, images, links and inline fields".

---

### Task 27C: `[lasso amazon_url|geni_url]`

**Feature:** F31 (A6, A7; AC17)

**Why:** `amazon_url` and `geni_url` place a product without knowing its ID; saved `[lasso]` shortcodes may use them.

**Depends on:** 27A, 18S6.

**User action:** both draw the product they name, or nothing.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LegacyAddress` passes `amazon_url` and `geni_url` to 18S6's lookups | `LassoShortcode` |

**Files:** F31 **AC17**. Create the classes named above; modify `src/Plugin.php` where a Module is registered.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Mapping.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Render [lasso amazon_url] and [lasso geni_url]".

---

### Task 27D: `[lasso]` group displays

**Feature:** F31 (A8, R12; AC18)

**Why:** the saved `[lasso type=grid|list|gallery]` shortcodes draw the migrated group.

**Depends on:** 27A, 18S7, 26F (groups by slug).

**User action:** `[lasso type="grid|list|gallery" category="<slug>"]` (`categories=`) draws the migrated group.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LegacyAddress` maps the Lasso category slug to the migrated group (26F's `group_map`) | `LassoShortcode` |

**Files:** F31 **AC18**. Create the classes named above; modify `src/Plugin.php` where a Module is registered.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Mapping.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Render [lasso] group displays".

---

> Task 27E (`[lasso type="table"]`) moved to Phase 7 on 2026-11-14 (it needs F25's table renderer and 26I); its text is in `archive/moving-to-phase-7.md`.

---

### Task 27F: `[lasso prompt]` — Lasso's cached AI text

> **Needs F31 N1 answered first.**

**Feature:** F31 (R15, A9, N1; AC20)

**Why:** `[lasso prompt]` asks Lasso's cloud; with Lasso off the shortcode must answer without a request (R15, N1).

**Depends on:** 27A; **N1 answered.**

**User action:** `[lasso prompt="…"]` with no product shows the text Lasso cached for it in
`div.lasso-container.lasso-ai`, else nothing; no request leaves the site.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Infrastructure\Lasso\LassoAiCache` (the transient key rule), `templates/front/ai-text.php` | `LassoShortcode` |

**Files:** F31 **AC20**; `LassoAiCacheTest`. Create the classes named above; modify `src/Plugin.php` where a Module is registered.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Cache read and template.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Show Lasso's cached AI text for [lasso prompt]".

---

### Task 27G: Lasso's render-time content repairs

**Feature:** F31 (R2 CDATA, R22, D7; AC21)

**Why:** Lasso repairs stored content when it renders it (R2, R22); the same repairs, at render time only, keep pages identical with Lasso off.

**Depends on:** 27A.

**User action:** with Lasso off, a post whose content holds `[lasso ref=">[lasso ref="` or `href=""` renders as with
Lasso (`href="#"`); content with `shopifycdn.com` loses the CDATA markers; the stored post is unchanged.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Legacy\LegacyContentFilter` (`the_content` at 1 and 10, only with Lasso off) | WordPress |

**Files:** F31 **AC21**; integration test asserting `post_content` unchanged.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Filter.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Apply Lasso's content repairs at render time".

---

### Task 27H: `[lasso]` in widgets and on AMP

**Feature:** F31 (R16, R17 for `[lasso]`; AC22)

**Why:** widgets and AMP pages draw `[lasso]` boxes in Lasso; the modules built for `[affibloom]` (18S9) serve `[lasso]` too.

**Depends on:** 27A, 18S9 (the modules; second user).

**User action:** as AC9, for `[lasso]`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `WidgetShortcodes` and `AmpBoxCss` (18S9) also fire for `[lasso` | WordPress |

**Files:** F31 **AC22**. Modify the two modules' registration in `src/Plugin.php`.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Wire.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Handle [lasso] in widgets and on AMP pages".

---

### Task 27I: `[lasso-attr]`

**Feature:** F31 (R3; AC23)

**Why:** `[lasso-attr]` prints a stored product attribute; with Lasso off a bare shortcode must not appear on the page.

**Depends on:** nothing but Lasso off (no product involved).

**User action:** `[lasso-attr id="<post>" attr_name="<name>"]` prints the stored value with line breaks
(`wp_kses_post`), or nothing.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Legacy\LassoAttrShortcode`, `Infrastructure\Lasso\LassoAttributeMeta` | WordPress (`init`) |

**Files:** F31 **AC23**. Create the classes named above; modify `src/Plugin.php`.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Shortcode.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Answer [lasso-attr] when Lasso is off".

---

### Task 27X1: `[amazon]` and `[aawp]`

> The table branch is built in 27E (Phase 7): `table`, and an imported AAWP table drawing the migrated table.

**Feature:** F31 (R23, R6; AC24; the R21 AAWP table branch is built in 27E, Phase 7)

**Why:** posts written for AAWP carry `[amazon]` and `[aawp]` shortcodes that Lasso takes over; they must keep drawing without Lasso (R23).

**Depends on:** 27A, 27B (fields).

**User action:** `link`, `box`, `fields` (with `value`): a migrated product's box, link or value (a box's
enclosed content as its description); AAWP active → AAWP renders; neither
→ `<a href="<tagged Amazon URL>" target="_blank" rel="nofollow">title|link_title|button_text|"Amazon product"</a>`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Legacy\AawpShortcode` | WordPress |
| `Application\Legacy\CompetitorProduct::by_asin()`, `Infrastructure\Lasso\LassoRevertLookup` | `AawpShortcode` (and 27X2-27X7) |

**Files:** F31 **AC24**; `CompetitorProductTest`. Create the classes named above; modify `src/Plugin.php`.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Handler.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Answer [amazon] and [aawp] from migrated products".

---

### Task 27X2: `[amalinkspro]`

**Feature:** F31 (R24; AC25)

**Why:** posts written for AmaLinks Pro carry `[amalinkspro]` shortcodes that Lasso takes over (R24).

**Depends on:** 27X1 (`CompetitorProduct`).

**User action:** the ASIN from `apilink` → the product's box; else AmaLinks Pro renders; else the enclosed content.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Legacy\AmaLinksShortcode` | WordPress |

**Files:** F31 **AC25**. Create the class named above; modify `src/Plugin.php`; integration test `tests/Integration/AmaLinksShortcodeTest.php`.

- [ ] **Step 1: Failing tests.** AC25 (an `[amalinkspro apilink]` shortcode: the product's box; AmaLinks Pro active: it renders; neither: the enclosed content) in `tests/E2E/acceptance/F31.spec.ts` and the integration or unit test named under Files, written first; expected red: the shortcode prints as text.
- [ ] **Step 2: Implementation.** `AmaLinksShortcode`, using `CompetitorProduct`.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Answer [amalinkspro] from migrated products".

---

### Task 27X3: EasyAzon links

**Feature:** F31 (R25 link; AC26)

**Why:** EasyAzon link shortcodes in posts are answered by Lasso (R25); they must keep drawing.

**Depends on:** 27X1.

**User action:** `[easyazon_link]`, `[easyazon-link]`, `[simpleazon-link]` (`asin`/`identifier`): no
`easyazon_item_<asin>` → nothing; a migrated product → its link/box; else EasyAzon renders; else
`<a href target="_blank" rel="nofollow">content or item title</a>`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Legacy\EasyAzonShortcodes` (the link names) | WordPress |

**Files:** Create the class named above; modify `src/Plugin.php`; integration test `tests/Integration/EasyAzonShortcodesTest.php`.

- [ ] **Step 1: Failing tests.** AC26 (`[easyazon_link]`, `[easyazon-link]`, `[simpleazon-link]`: nothing without `easyazon_item_<asin>`; a migrated product; EasyAzon active; the plain link) in `tests/E2E/acceptance/F31.spec.ts` and the integration or unit test named under Files, written first; expected red: the shortcode prints as text.
- [ ] **Step 2: Implementation.** `EasyAzonShortcodes` (the link names).
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Answer EasyAzon link shortcodes".

---

### Task 27X4: EasyAzon image, CTA and info block

**Feature:** F31 (R25 image/cta/infoblock; AC27)

**Why:** EasyAzon image, call-to-action and info-block shortcodes are answered by Lasso (R25).

**Depends on:** 27X3.

**User action:** the 9 names → the product's image, button or box; else EasyAzon renders; else the shortcode's own
text, as Lasso prints it.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the other names in `EasyAzonShortcodes` | WordPress |

**Files:** F31 **AC27**. Modify the class named above; integration test `tests/Integration/EasyAzonShortcodesTest.php`.

- [ ] **Step 1: Failing tests.** AC27 (the 9 names: the product's image, button or box; EasyAzon active; the shortcode's own text) in `tests/E2E/acceptance/F31.spec.ts` and the integration or unit test named under Files, written first; expected red: the shortcode prints as text.
- [ ] **Step 2: Implementation.** the other names in `EasyAzonShortcodes`.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Answer EasyAzon image, CTA and info block shortcodes".

---

### Task 27X5: Earnist

**Feature:** F31 (R26; AC28)

**Why:** Earnist shortcodes in posts are answered by Lasso (R26).

**Depends on:** 27X1 (`LassoRevertLookup`).

**User action:** `[earnist id]` → the product's box; `[earnist_link id]text` → a `nofollow`, new-tab link; unknown →
nothing.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Legacy\EarnistShortcodes` | WordPress |

**Files:** F31 **AC28**. Create the class named above; modify `src/Plugin.php`; integration test `tests/Integration/EarnistShortcodesTest.php`.

- [ ] **Step 1: Failing tests.** AC28 (`[earnist id]`, `[earnist_link id]text`, an unknown id) in `tests/E2E/acceptance/F31.spec.ts` and the integration or unit test named under Files, written first; expected red: the shortcode prints as text.
- [ ] **Step 2: Implementation.** `EarnistShortcodes`, using `LassoRevertLookup`.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Answer Earnist shortcodes".

---

### Task 27X6: Easy Affiliate Links

> **Needs F31 N4 answered first.**

**Feature:** F31 (R27, N4; AC29)

**Why:** Easy Affiliate Links' shortcode and block are answered by Lasso for migrated records (R27); N4 decides whether it is built.

**Depends on:** 27A; **N4 answered.**

**User action:** only with EAFL active: `[eafl id text]` and EAFL's block show a migrated record as
`<span class="lasso">`/`<div class="lasso">` + the link; EAFL's own links unchanged; EAFL's REST routes serve both.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Legacy\EaflShortcode` (shortcode, block render, REST) | WordPress, only with EAFL active |

**Files:** Create the class named above; modify `src/Plugin.php`; integration test `tests/Integration/EaflShortcodeTest.php`.

- [ ] **Step 1: Failing tests.** AC29 (with EAFL active: `[eafl id text]`, EAFL's block, EAFL's REST routes; EAFL's own links unchanged) in `tests/E2E/acceptance/F31.spec.ts` and the integration or unit test named under Files, written first; expected red: the shortcode prints as text.
- [ ] **Step 2: Implementation.** `EaflShortcode`: shortcode, block render and REST routes.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Answer Easy Affiliate Links for migrated records".

---

### Task 27X7: ThirstyAffiliates

**Feature:** F31 (R28; AC30)

**Why:** ThirstyAffiliates shortcodes and `<ta>` tags in posts are answered by Lasso (R28); 5 hidden Nelio variants are on this site.

**Depends on:** 27A, 27G (the content filter).

**User action:** `[thirstylink linkid|ids]text` → a migrated record's link; else ThirstyAffiliates renders; else a link
to `_ta_destination_url` with the post's title. `<ta linkid href>` tags (5 hidden Nelio variants here) → Lasso's
`<a>`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Legacy\ThirstyShortcode` | WordPress |
| the `<ta>` pass in `LegacyContentFilter` (no new parser: `WP_HTML_Tag_Processor`) | `the_content` |

**Files:** F31 **AC30**. Create the class named above; modify `src/Plugin.php`; integration test `tests/Integration/ThirstyShortcodeTest.php`.

- [ ] **Step 1: Failing tests.** AC30 (`[thirstylink linkid|ids]text`; the 5 hidden Nelio `<ta>` variants) in `tests/E2E/acceptance/F31.spec.ts` and the integration or unit test named under Files, written first; expected red: the shortcode prints as text.
- [ ] **Step 2: Implementation.** `ThirstyShortcode` and the `<ta>` pass in `LegacyContentFilter`.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Answer ThirstyAffiliates shortcodes and tags".

---

### Task 27X8: `[lasso]` back to the original plugin's shortcode

**Feature:** F31 (R21, D7; AC31)

**Why:** a `[lasso]` whose record was never migrated must still reach its original plugin's shortcode (R21), as Lasso does.

**Depends on:** 27X1-27X7, 27G.

**User action:** a `[lasso]` with AAWP, EasyAzon, ThirstyAffiliates or AmaLinks identifiers whose record was never
migrated renders that plugin's shortcode (Lasso's conversion rules), at render time only.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the conversion step in `LegacyContentFilter`; `Domain\Shortcode\OriginalShortcode` (the pure rules of Lasso's `covert_lasso_shortcode_to_original_shortcode`) | `the_content` |

**Files:** F31 **AC31**;
`OriginalShortcodeTest`. Create the class named above; modify `src/Plugin.php`.

- [ ] **Step 1: Failing tests.** AC31 (a `[lasso]` with AAWP, EasyAzon, ThirstyAffiliates or AmaLinks identifiers whose record was never migrated) in `tests/E2E/acceptance/F31.spec.ts` and the integration or unit test named under Files, written first; expected red: the shortcode prints as text.
- [ ] **Step 2: Implementation.** `OriginalShortcode` (pure rules) and the conversion step of `LegacyContentFilter`.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Give unmigrated [lasso] boxes back to their original plugin".

---

## Tasks: F24 — Link cloaking, redirects and permalinks (with F10's slugs step 26G)

### Task CL1: The redirect for a cloaked path

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

- [ ] **Step 1: Failing tests.** AC1 (a product made through WP-CLI with a slug and a destination; `curl -I` sees 302, the three headers), AC2 (a page with the same slug; a trashed product; an Amazon product's `amzn-` slug), AC3 (the prefix set through WP-CLI). Unit: every R17 case. Expected red: 404.
- [ ] **Step 2: `CloakPath`, `PublicLink::destination()`.**
- [ ] **Step 3: Resolver, repository, guard.**
- [ ] **Step 4: The Module and its filters.**
- [ ] **Step 5: Run and look.** Checks; by hand the five products and the retired 14771 on a copy with Affibloom's migrated data (or made by WP-CLI until 26G).
- [ ] **Step 6: Commit** — "Answer cloaked product addresses as Lasso does".

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

- [ ] **Step 1: Failing tests** (AC4; unit per rule).
- [ ] **Step 2: `RedirectQuery` and the data file.**
- [ ] **Step 3: Wire into the redirect.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Pass query strings through cloaked links".

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

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Domain rules.**
- [ ] **Step 3: PATCH, resource, warning.**
- [ ] **Step 4: The partial and scripts.**
- [ ] **Step 5: Run and look** (1280, 375 px; keyboard).
- [ ] **Step 6: Commit** — "Edit a product's permalink and link cloaking, as Lasso does".

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

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Domain and service.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Give new products Lasso's slug and cloaking default".

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

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: History and override arm.**
- [ ] **Step 3: Resolver arm.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Apply the cloaked link prefix and cloaking override".

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

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Wire.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Link displays to the product's public link".

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

## Tasks: F10 — the slugs step, after F24's stores exist

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

## Tasks: F27 — URL variants (moved here from Phase 2 on 2026-11-14)

> Written 2026-11-14 from a sweep of Lasso 369 (`docs/features/F27/requirements.md`, analysis in `F27/analysis.md`).
> 0 variants on this site; built in full under the full-scope rule. Gate 4: waiting. Every task: outside-in, its
> acceptance test first and red, `composer lint`, `composer analyse`, unit tests, the check by hand at 1280 and 375 px,
> one commit, pushed.

---

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

## Tasks: F10 — URL variants, after F27 and 26G

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

## Tasks: F21 — render-time link attributes and displays (after the cloaking block and 26G)

### Task 35H: Render-time link attributes — `the_content`

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

## Tasks: F10 — converting legacy blocks; F34 — content changes (dropped 2026-11-14)

Tasks 28A-28E and H1-H4 are dropped by the user's decisions A1 and A2 (no conversion, `data-lasso-*` stamps kept).
Their sections are in [`archive/phase-5-superseded.md`](archive/phase-5-superseded.md), under "Dropped 2026-11-14 by the
user's decision A1" and "... A2".

---

## Tasks: F33 — Import from other plugins

### Task IM1: The Import screen, with ThirstyAffiliates' links

**Feature:** F33 (R1-R10, R42, R43 listing, D6, D12; AC1, AC2, AC10 listing)

**Why:** the screen and its list are the entry point for every source; ThirstyAffiliates comes first because this copy
holds 49 of its links (real data, no fixture needed for the first look).

**Depends on:** F19's header and list patterns (13C/13D), `Capabilities::PRODUCTS`.

**User action:** Affibloom → **Import**: "Import", **Bulk Import** (disabled until IM3), the source select ("All
Sources", "Thirsty Affiliates"), "Search URLs to Import", the card (Link Title, Import Target, Source, two cells), ten a
page, pending first; each row's **Import** (disabled until IM2). Search, source and page redraw in place and stay in
the address. No rows: an empty card and "All Sources" only.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Import\{ImportSource, ImportRow, ImportList, ImportQuery}` | `ImportService` |
| `Application\Import\ImportService::list()` | `ImportScreen`, `ImportController` |
| `Infrastructure\Import\ThirstySource::rows()` (R42) | `ImportSources` |
| `Admin\ImportScreen`, submenu "Import" in `AdminMenu` | WordPress |
| `GET /import/items` in `Rest\ImportController` | `import.js` |
| `templates/admin/import.php`, partials `import-list.php`, `import-row.php`; `assets/admin/import.js` | the screen, the route |

**Files:** the above; `tests/E2E/acceptance/F33.spec.ts` (AC1, AC2, AC10's list), `tests/Unit/Domain/Import/
ImportListTest.php`, `tests/Integration/Import/ThirstySourceTest.php`; modify `src/Plugin.php`,
`src/Interface/Admin/{AdminMenu,AdminAssets}.php`, `assets/admin/admin.css`, `docs/ui/design-system.md` (Import row).

- [ ] **Step 1: Failing tests.** AC1 against the site's 49 rows (order, columns, 100-character cut, search, filter, the address); AC2 with the rows' meta hidden in a test site. Unit: sort and page. Expected red: no Import submenu.
- [ ] **Step 2: Domain and service.**
- [ ] **Step 3: ThirstySource rows.**
- [ ] **Step 4: Screen, route, templates, script.**
- [ ] **Step 5: Run and look.** Checks; by hand: list, search, filter, paging, 375 px.
- [ ] **Step 6: Commit** — "Add the Import screen with ThirstyAffiliates' links".

---

### Task IM2: Import one, revert one — ThirstyAffiliates

**Feature:** F33 (R11-R26, R43, D1, D2, D4-D8; AC3, AC4, AC5, AC10)

**Why:** the import itself: a product from the source's data, its record, its old address redirecting and the source's
shortcode drawing it, with no post rewritten; and the way back.

**Depends on:** IM1; F1/F18 `ProductService::create()`; F23 `GroupService::resolve()`; **F24's old-address list**;
F31 27X7 (`ThirstyShortcode`, `CompetitorProduct`); F21 35I (repoint keeps rows, earlier in this phase).

**User action:** **Import** → "Import This Link?" (Cancel · Import; no Esc) → the row ticks (and any row of the same
item); refused → "Import failed.". The imported row shows **Revert** → "Revert This Import?" (Keep · Revert) → the
product is deleted, the row returns to Import; a product Affibloom's blocks use → "This product is used in N places."
(N2). `/recommends/<slug>/` answers 301 to the product while ThirstyAffiliates is inactive.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Import\{ImportedLink, ImportRecord, ImportRecordRepository, OldAddress}`, `Source\ThirstyAddress` (D7) | `ImportService` |
| `ImportService::import(), revert()` (D8 adopt, groups, record, old address, repoint) | `ImportController` |
| `ThirstySource::item()` (R43) | `ImportService` |
| `PostMetaImportRecordRepository` (D5 keys) | `ImportService`, `ImportedProducts` |
| `Application\Import\ImportedProducts` | F31's `CompetitorProduct` (Thirsty first) |
| `POST /import/items`, `DELETE /import/items/{source}/{ref}` | `import.js` |
| partial `import-dialogs.php` (the two confirms) | `ImportScreen` |

**Files:** the above; F33 **AC3-AC5, AC10**; unit `ImportedLinkTest`, `OldAddressTest`, `ThirstyAddressTest`,
`ImportServiceTest`; integration `ThirstySourceTest` (item).

- [ ] **Step 1: Failing tests.** AC3-AC5 with post 6741: product fields, group "Volleyball Shoes", the 301, a `[thirstylink linkid="6741"]` test post draws the product, `post_content` hashes unchanged; revert; the adopted case.
- [ ] **Step 2: Domain** (link, record, address rules).
- [ ] **Step 3: Service and repository.**
- [ ] **Step 4: Routes, dialogs, script.**
- [ ] **Step 5: Run and look.**
- [ ] **Step 6: Commit** — "Import and revert a ThirstyAffiliates link without touching posts".

---

### Task IM3: Import All and Revert All in the background

**Feature:** F33 (R27-R36, D11, D13; AC6, AC7)

**Why:** sources hold hundreds of links; Lasso imports them in a background job that survives the page.

**Depends on:** IM2; `WpCronBackgroundJob` (exists).

**User action:** **Bulk Import** → "Checking" → (job running) "Currently bulk importing links. When this is complete,
you can import more." (OK) or "Import All Links?" (Cancel · Import All) → "Updating Your Site", a progress line "N of
M" polled every 5 s, the rows tick; the page may be closed. `&bulk-revert=1` shows **Bulk Revert** → "Revert All
Links?" (Cancel · Revert All, red). Both use the source select's value.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Import\ImportRun` | `BulkImportService` |
| `Application\Import\BulkImportService::start(), status(), batch()` | the routes, the cron |
| `OptionImportRunRepository` (`affibloom_import_run`) | the service |
| `GET` / `POST /import/bulk` | `import.js` |
| WP-Cron `affibloom_import_batch` (registered while a job runs) | WordPress |
| the processing, confirm-all and progress dialogs in `import-dialogs.php` | `ImportScreen` |

**Files:** the above; F33 **AC6, AC7**; unit `ImportRunTest`, `BulkImportServiceTest`.

- [ ] **Step 1: Failing tests** (49 Thirsty rows imported by the job driven through `wp cron event run`; revert all; one job at a time; a refused row counted, no loop).
- [ ] **Step 2: Run model, service, repository, cron.**
- [ ] **Step 3: Routes, dialogs, polling.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Import and revert all links in the background".

---

### Task IM4: Pretty Links

**Feature:** F33 (R44; AC11)

**Why:** sites coming from Pretty Links need their links, groups and old addresses in the library (R44).

**Depends on:** IM2 (IM3 serves it unchanged).

**User action:** with Pretty Links' data (Pro's `prli_links.link_cpt_id`), "Pretty Links" appears in the select; its rows
import with name, slug (last segment), URL, description, NoFollow, Sponsored, groups from categories and tags; the old
address (with `base_slug_prefix`) redirects.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Infrastructure\Import\PrettyLinksSource` (rows, item), `Domain\Import\Source\PrettyLinkAddress` | `ImportSources`, `ImportService` |

**Files:** F33 **AC11**; `PrettyLinkAddressTest`; integration `PrettyLinksSourceTest` (fixture: the table and posts
created in the test). Create the class(es) named above and register the source in `ImportSources` (`src/Plugin.php`).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Address rule and adapter.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Import Pretty Links".

---

### Task IM5: Lasso Lite / Simple URLs

**Feature:** F33 (R45; AC12 import half)

**Why:** sites coming from Lasso Lite or Simple URLs need every Lite field carried over (R45).

**Depends on:** IM2.

**User action:** with Simple URLs (or Lasso Lite) active, its `surl` links are rows; each import carries every Lite field
(button text, disclosure, description, price, show price, second button, four toggles, badge, custom image) and Lite's
cached Amazon data; Lite links that came from Lasso are not listed.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Infrastructure\Import\SimpleUrlsSource` (Lite meta, then Lite settings, then Affibloom defaults: R45's order) | `ImportSources` |

**Files:** F33 **AC12**; integration `SimpleUrlsSourceTest`. Create the class(es) named above and register the source in `ImportSources` (`src/Plugin.php`).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Adapter.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Import Lasso Lite and Simple URLs links".

---

### Task IM6: Earnist

**Feature:** F33 (R46, D10; AC13)

**Why:** Earnist's links must be importable with their price, button text and groups (R46).

**Depends on:** IM2.

**User action:** `earnist` posts (any status, not `old_status`) are rows; an import carries URL, description, price, button
text, the thumbnail or `_earn_image_url`, and the post's term names as groups.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Infrastructure\Import\EarnistSource` | `ImportSources` |

**Files:** F33 **AC13**;
integration `EarnistSourceTest`. Create the class(es) named above and register the source in `ImportSources` (`src/Plugin.php`).

- [ ] **Step 1: Failing tests.** AC13 (`earnist` posts of any status except `old_status`; URL, description, price, button text, thumbnail or `_earn_image_url`, term names as groups) in `tests/E2E/acceptance/F33.spec.ts` and the integration or unit test named under Files, written first; expected red: the source is not offered on the Import screen.
- [ ] **Step 2: Implementation.** the class(es) in the table above, registered in `ImportSources`.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Import Earnist links".

---

### Task IM7: Affiliate URLs

**Feature:** F33 (R47, D10; AC14)

**Why:** Affiliate URLs' links must be importable with their excerpt, thumbnail and groups (R47).

**Depends on:** IM2.

**User action:** `affiliate_url` posts with a redirect are rows; an import carries URL, excerpt, thumbnail, groups.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Infrastructure\Import\AffiliateUrlsSource` | `ImportSources` |

**Files:** F33 **AC14**. Create the class(es) named above and register the source in `ImportSources` (`src/Plugin.php`).

- [ ] **Step 1: Failing tests.** AC14 (`affiliate_url` posts with a redirect; URL, excerpt, thumbnail, groups) in `tests/E2E/acceptance/F33.spec.ts` and the integration or unit test named under Files, written first; expected red: the source is not offered on the Import screen.
- [ ] **Step 2: Implementation.** the class(es) in the table above, registered in `ImportSources`.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Import Affiliate URLs links".

---

### Task IM8: Easy Affiliate Links

**Feature:** F33 (R48; AC15)

**Why:** Easy Affiliate Links' text links must be importable with their link settings and categories (R48).

**Depends on:** IM2; F31 27X6 (`[eafl]`) for the shortcode half.

**User action:** text-type `easy_affiliate_link` posts are rows; an import keeps the defined name and carries URL,
description, new tab / NoFollow ("default" from `eafl_settings`), Sponsored, `eafl_category` groups.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Infrastructure\Import\EaflSource`, `Domain\Import\Source\EaflTarget` | `ImportSources` |

**Files:** Create the class(es) named above and register the source in `ImportSources` (`src/Plugin.php`).

- [ ] **Step 1: Failing tests.** AC15 (text-type `easy_affiliate_link` posts; defined name, URL, description, new tab, NoFollow, Sponsored, `eafl_category` groups) in `tests/E2E/acceptance/F33.spec.ts` and the integration or unit test named under Files, written first; expected red: the source is not offered on the Import screen.
- [ ] **Step 2: Implementation.** the class(es) in the table above, registered in `ImportSources`.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Import Easy Affiliate Links".

---

### Task IM9: AAWP products

**Feature:** F33 (R49, R58, D9; AC16)

**Why:** AAWP's products must become Amazon products from AAWP's own cache, with no call to the cloud (R49, R58).

**Depends on:** IM2; F31 27X1 (`[amazon]`, `[aawp]`); F18's Amazon product.

**User action:** with AAWP's `aawp_products`, its products are rows (target `[amazon link="<ASIN>"]`); an import makes an
Amazon product from AAWP's cache (title, price, image, Prime, features, savings, rating, reviews), Sponsored on,
AAWP's button text; with no cache, "Amazon" and the tagged link (D9).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Infrastructure\Import\AawpProductSource` | `ImportSources` |
| `ImportedProducts::by_asin()` | 27X1's `CompetitorProduct` |

**Files:** F33 **AC16**; integration `AawpProductSourceTest`. Create the class(es) named above and register the source in `ImportSources` (`src/Plugin.php`).

- [ ] **Step 1: Failing tests.** AC16 (an AAWP product with a cache; one without: "Amazon" and the tagged link) in `tests/E2E/acceptance/F33.spec.ts` and the integration or unit test named under Files, written first; expected red: the source is not offered on the Import screen.
- [ ] **Step 2: Implementation.** the class(es) in the table above, registered in `ImportSources`.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Import AAWP products".

---

### Task IM10: AAWP lists

**Feature:** F33 (R50; AC17)

**Why:** AAWP's product lists must become groups of products (R50).

**Depends on:** IM9; F23 groups.

**User action:** each AAWP list is a row (target `[amazon bestseller|new|link="<keywords>" items="<n>"]`); Import imports
every ASIN and puts the products in a group named after the keywords; Revert reverts each ASIN (kept when another
record or table uses it).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Infrastructure\Import\AawpListSource` (an item that expands to ASINs; the group's term-meta record) | `ImportSources` |

**Files:** F33 **AC17**. Create the class(es) named above and register the source in `ImportSources` (`src/Plugin.php`).

- [ ] **Step 1: Failing tests.** AC17 (an AAWP list: every ASIN imported, a group named after the keywords; revert keeps an ASIN another record uses) in `tests/E2E/acceptance/F33.spec.ts` and the integration or unit test named under Files, written first; expected red: the source is not offered on the Import screen.
- [ ] **Step 2: Implementation.** the class(es) in the table above, registered in `ImportSources`.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Import AAWP lists into groups".

---

> Task IM11 (AAWP tables) moved to Phase 7 on 2026-11-14 (it needs F25's tables); its text is in `archive/moving-to-phase-7.md`.

---

### Task IM12: EasyAzon

**Feature:** F33 (R52, D9; AC19)

**Why:** EasyAzon's stored items must become Amazon products (R52).

**Depends on:** IM2; F31 27X3-27X4.

**User action:** `easyazon_item_*` options are rows (target `[easyazon_link identifier="<ASIN>"]<title>[/easyazon_link]`);
an import makes an Amazon product from the option; an item with no identifier is refused.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Infrastructure\Import\EasyAzonSource`, `Domain\Import\Source\EasyAzonItem` | `ImportSources` |

**Files:** F33 **AC19**; `EasyAzonItemTest`. Create the class(es) named above and register the source in `ImportSources` (`src/Plugin.php`).

- [ ] **Step 1: Failing tests.** AC19 (`easyazon_item_*` options; an item with no identifier is refused) in `tests/E2E/acceptance/F33.spec.ts` and the integration or unit test named under Files, written first; expected red: the source is not offered on the Import screen.
- [ ] **Step 2: Implementation.** the class(es) in the table above, registered in `ImportSources`.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Import EasyAzon items".

---

### Task IM13: AmaLinks Pro

**Feature:** F33 (R53, D14, D18; AC20)

**Why:** the ASINs in AmaLinks Pro shortcodes must become Amazon products (R53).

**Depends on:** IM2; F21 35B (shortcodes in the index, this phase); F31 27X2.

**User action:** each ASIN of an `[amalinkspro]` in posts is a row (target the shortcode); an import makes the Amazon
product (title from anchor text or cache); the AmaLinks filter shows imported rows too.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Infrastructure\Import\AmaLinksSource` (reads F21's rows) | `ImportSources` |

**Files:** Create the class(es) named above and register the source in `ImportSources` (`src/Plugin.php`).

- [ ] **Step 1: Failing tests.** AC20 (ASINs of `[amalinkspro]` in posts; the AmaLinks filter shows imported rows) in `tests/E2E/acceptance/F33.spec.ts` and the integration or unit test named under Files, written first; expected red: the source is not offered on the Import screen.
- [ ] **Step 2: Implementation.** the class(es) in the table above, registered in `ImportSources`.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Import AmaLinks Pro products".

---

### Task IM14: SiteStripe

**Feature:** F33 (R54, D18; AC21)

**Why:** the products in SiteStripe markup must become Amazon products (R54).

**Depends on:** IM2; F21 35J (SiteStripe in the index).

**User action:** each SiteStripe product in posts is a row ("<ASIN> Image" / "<ASIN> Product Box"); Import makes the Amazon
product; Revert is disabled, as Lasso's.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Infrastructure\Import\SiteStripeSource`, `Domain\Import\Source\SiteStripeTitle` | `ImportSources` |

**Files:** F33 **AC21**; `SiteStripeTitleTest`. Create the class(es) named above and register the source in `ImportSources` (`src/Plugin.php`).

- [ ] **Step 1: Failing tests.** AC21 (SiteStripe products in posts; Revert disabled) in `tests/E2E/acceptance/F33.spec.ts` and the integration or unit test named under Files, written first; expected red: the source is not offered on the Import screen.
- [ ] **Step 2: Implementation.** the class(es) in the table above, registered in `ImportSources`.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Import SiteStripe products".

---

### Task IM15: WP Recipe Maker

**Feature:** F33 (R55, D8, D18; AC22)

**Why:** WP Recipe Maker's links must be importable as products (R55).

**Depends on:** IM2; F21 35E (recipe links in the index).

**User action:** with WP Recipe Maker active, each distinct recipe link is a row; a URL that is already a product links to
it (adopted).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Infrastructure\Import\WprmSource` | `ImportSources` |

**Files:** F33 **AC22**. Create the class(es) named above and register the source in `ImportSources` (`src/Plugin.php`).

- [ ] **Step 1: Failing tests.** AC22 (a recipe link; a URL that is already a product is adopted) in `tests/E2E/acceptance/F33.spec.ts` and the integration or unit test named under Files, written first; expected red: the source is not offered on the Import screen.
- [ ] **Step 2: Implementation.** the class(es) in the table above, registered in `ImportSources`.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Import WP Recipe Maker links".

---

### Task IM16: Skimlinks, Genius, Tinyurl, Go.

**Feature:** F33 (R56, D18; AC23)

**Why:** links of Skimlinks, Genius, Tinyurl and Go. found in posts must be importable as products (R56).

**Depends on:** IM2; 35A's `affiliate_platform` column of the link index.

**User action:** platform links in posts (tied to no product) are rows, one per URL, titled after the domain; Import makes a
product (`is_import_platform`); Revert deletes it.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Infrastructure\Import\PlatformSource`, `Domain\Import\Source\PlatformTitle` | `ImportSources` |

**Files:** F33 **AC23**; `PlatformTitleTest`. Create the class(es) named above and register the source in `ImportSources` (`src/Plugin.php`).

- [ ] **Step 1: Failing tests.** AC23 (one row per URL, titled after the domain; `is_import_platform`; revert) in `tests/E2E/acceptance/F33.spec.ts` and the integration or unit test named under Files, written first; expected red: the source is not offered on the Import screen.
- [ ] **Step 2: Implementation.** the class(es) in the table above, registered in `ImportSources`.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Import Skimlinks, Genius, Tinyurl and Go. links".

---

### Task IM17: Lasso as a source

**Feature:** F33 (D3; AC24)

**Why:** Lasso's own records are a source on the Import screen, so one record can be imported through the same screen (D3).

**Depends on:** IM2; F10 26A (products step, `LassoIdMap`, `LassoRecordMapper`).

**User action:** while Lasso's data is on the site, "Lasso" is in the select; its rows are Lasso records with no product;
Import runs F10's product migration for that record; Revert deletes that product.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Import\LassoImportSource` (an `ImportSource` over F10's classes; no second mapper) | `ImportSources` |

**Files:** F33 **AC24**; F10 AC7 rerun. Create the class(es) named above and register the source in `ImportSources` (`src/Plugin.php`).

- [ ] **Step 1: Failing tests.** AC24 ("Lasso" in the select; import runs F10's product migration for one record; revert deletes the product; F10 AC7 rerun) in `tests/E2E/acceptance/F33.spec.ts` and the integration or unit test named under Files, written first; expected red: the source is not offered on the Import screen.
- [ ] **Step 2: Implementation.** the class(es) in the table above, registered in `ImportSources`.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Offer Lasso's records on the Import screen".

---

### Task IM18: moved to Phase 7 (needs F12 36B; settled 2026-11-14)

---

### Task IM19: Import notices and the first-build lock

**Feature:** F33 (R10, R38-R41, D15, D16; AC2 lock, AC9, AC12 notice)

**Why:** Lasso tells the user when imports are possible, pending or blocked; the same notices guard Affibloom's index (R10, R38-R41).

**Depends on:** IM3; F9 9K
(`disable_import_notice`); F21 35F (the first build and the re-resolve).

**User action:** the suggestion banner on Affibloom screens (not Import) while an active source has pending rows and the
switch is off; "We are creating an index of your links. This will be complete in <time>. Please wait until then to
begin any Imports." after a bulk import while the index re-resolves; "Check back soon." / "Affibloom is indexing your
links. When this is complete, you can import links from other plugins." during the first build; the Lite-from-Lasso
notice on Import.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Import\ImportNotices`, `Admin\ImportNoticesModule`, `affibloom_import_reindex_notice` | `admin_notices`, the Import screen |

**Files:** F33 **AC9**, AC2's lock, AC12's notice. Create the classes named above; modify `src/Plugin.php`.

- [ ] **Step 1: Failing tests.** AC9, AC2's lock and AC12's notice (the suggestion banner; the re-index notice; the first-build lock; the Lite-from-Lasso notice) in `tests/E2E/acceptance/F33.spec.ts` and the integration or unit test named under Files, written first; expected red: the control, notice or hook does not exist.
- [ ] **Step 2: Implementation.** the route, dialog or hook in the table above.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Show Lasso's import notices".

---

> Task IM20 (WP All Import) moved to Phase 7 on 2026-11-14 (its Export-CSV half needs F11 31D); its text is in `archive/moving-to-phase-7.md`.

---

## Tasks: F10 — parity and the rollback drill

### Task 29A: Parity — every box on every guide

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

### Task 29D: dropped (decision A1)

Parity in converted and reverted modes needs the conversion, which is dropped. The section is in
`archive/phase-5-superseded.md`.

---

### Task 29E: Rollback drill

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

## Exit check

The phase is done when all of these hold on the local copy (then staging, Task 40), and the user has signed it off:

- **F10:** the dry run accounts for all 265 records and every Lasso table (AC1, AC4-AC6); the migration of products, settings, fields, groups, variants, Amazon data, saved views and the tour and education copies is idempotent and updatable (AC7-AC15, AC19, AC20, AC22); Lasso's fingerprint is unchanged by every write task (AC23); every migrated product gives the baseline values (AC24); with Lasso off all 34 guides match the baselines (AC2: 29A, 29B). Not built (decision A1): AC3, AC25-AC28 and AC31. The rollback drill passes (AC32, 29E). Not in this phase: AC16 (26I), AC18 (26K), AC21 (26N), AC29-AC30 (29C).
- **F31 (legacy part):** AC15-AC31 (27A-27I, 27X1-27X8), except AC19 (27E, moved to Phase 7), AC20 (N1 accepted: 27F), AC29 (N4 accepted: 27X6).
- **F4 (ED6):** AC21 (ED6, with its "renders unchanged" half on 27A).
- **F21 (the index):** AC1-AC10 (35A-35J; AC4 and AC7 with 9I, 9H and 9L's controls), and the F9 AC13 (9J), F38 AC7 (ON3) and AC8 (ON4) that moved here. F12's AC (36A-36E) stay in Phase 7.
- **F27:** AC1-AC8 (V1-V5), with F10 AC15 (26H).
- **F24:** AC1-AC15 (CL1-CL11 and 26G for AC14).
- **F34:** no tasks (decisions A1, A2): AC1-AC8 are not built.
- **F33:** AC1-AC25 less AC18 (IM11) and AC25 (IM20), which move to Phase 7; AC8 (IM18) waits for F12 36B (Phase 7).

Sign-off: the user has seen the migration report, the parity result and the redirect results and signs the phase off. Nothing in Phase 6 starts before that.

## Coverage

Every requirement and acceptance criterion of this phase's features and the task that owns it. "(Phase n)" marks a task outside this phase.

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

### F31 (legacy part; the `[affibloom]` rows are Phase 3)

| Requirement | Task |
|---|---|
| R1 `lasso` / `lasso-attr` / competitors | 27A / 27I / 27X1-27X7 (each its names) |
| R2 widget, CDATA, content, attrs filter | 27H / 27G / 27G, 27X8 / 27A |
| R2 Elementor key filter (`builder_content_data`, the "Lasso Shortcode" widget, `class-elementor.php`) | F4 ED2 (Phase 4) — the key filter as well as the widget (assigned 2026-11-14) |
| R3 | 27I |
| R4, R5 | 18S1 (Phase 3), re-tested in 27A |
| R6 | 27A |
| A3, A4, A15-A17, A53, A54 | 27A (A17 also 18S9, Phase 3) |
| A6, A7 | 27C (18S6, Phase 3) |
| A8, A44-A49 | 27D (18S7, Phase 3) |
| A9 | 27F (N1) |
| A10-A14 | 27B (18S4, 18S5, Phase 3) |
| A41-A43 | 27B |
| A50, A51 | 27E (Phase 7) |
| R10 | 27B (18S4, Phase 3) |
| R11 | 27B (18S5, Phase 3) |
| R12 | 27D (18S7, Phase 3) |
| R15 | 27F (N1) |
| R16-R19 | 27H (18S9, Phase 3) |
| R21 | 27X8 (render-time conversion), 27E (the AAWP table branch of 27X1; Phase 7) |
| R22 | 27G (`<ta>`: 27X7) |
| R23 / R24 / R25 / R26 / R27 / R28 | 27X1 / 27X2 / 27X3, 27X4 / 27X5 / 27X6 / 27X7 |
| R34 | their screens (display modal F4; Display settings F16; product preview F1; tables; MCP): not owned here |
| R35 | F4 (Phase 4), section 5: not owned here |
| AC15-AC23 | 27A-27I in order (AC19: 27E, Phase 7) |
| AC24-AC31 | 27X1-27X8 in order |

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
| R6 (old slugs), D19; AC7, AC14 | CL10 (the migration half: 26G) |
| R36; AC15 | CL11 |
| R2 | CL1 (by D2: no rewrite needed) |
| R18, R29 | the import feature (F33), not F24 |
| R20, R34, R37 | not built, D17 (same visible result) |
| R32 | the quick-detail panel's owner (F22/F4), reusing CL3's partial |
| R33 | CL3 (a lasso.to URL never gets the flag) |
| R35 | uninstall (F9 R64) |
| R14 (scanner writes into posts, coupons meta, tables, importer) | F21 35H (render time), F25 (tables), the import feature; never a content write |

### F34

| R / AC | Owner |
|---|---|
| R1-R5 (Lasso's writer) | not built: decision A1 (no content writer exists) |
| R6, R7, R8 | F21 35H (this phase; render-time attributes; F1 D13) |
| R9 | F1 15C (repoint, no content write) |
| R10 | F1 Task 36 (D34: block only) |
| R11, R12, R13 | F22 O10 (Phase 7; render-time assignment, pending F22 N1 / F34 N4) |
| R14 | none: keyword linking left out (deviation 52, the user's) |
| R15, R16 | F35 (auto-monetize, Phase 7) with F21 35H |
| R17, R44, R45, R46 | F33 (IM1-IM20) |
| R18 | not built: decision A2 (stamps kept) and F9 N2 |
| R19 | F21 35G |
| R20 | F24 (old prefixes redirect; CL5, CL10) |
| R21 | C1 / F24 (the Amazon tab's geni.us setting) |
| R22 | F23 G4 (D6) |
| R23 | F4 MCP1 / N5 (not built) |
| R24 | F4 N6 (not built) |
| R25, R47-R51, R53, R54 | none: not built (N5, waiting for the user) |
| R52 | 27A's parser (D15: tolerance in memory); 28A dropped (decision A1) |
| R55-R58 | F10 26A-26P (the migration reads Lasso's upgraded data; nothing here) |
| R59 | F1 (retired ASINs) and F21 D8 |
| R26 | F31 N4 (EAFL takeover; 27X6); nothing here |
| R27-R36 | not built: decision A1 (H1 dropped) |
| R37-R43 | not built: decision A1 (H2 dropped) |
| AC1, AC5, AC8 | not built: decision A1 |
| AC2, AC3, AC4 | not built: decision A1 |
| AC6 | not built: decision A2 |
| AC7 | not built: decision A2 |

R25 and R47-R54 (but R52) have no task because N5 recommends building none; R18 is not built (decision A2). R11-R13 are F22 O10's (the F34 draft said "no task ID yet"; F22's task list now has O10).

### F33

| Requirement | Task |
|---|---|
| R1-R10, R42 | IM1 (R10 lock: IM19) |
| R11-R26, R43 | IM2 |
| R27-R36 | IM3 |
| R37 | IM18 (waits for F12 36B, Phase 7) |
| R38-R41 | IM19 |
| R44 | IM4 |
| R45 | IM5 |
| R46 | IM6 |
| R47 | IM7 |
| R48 | IM8 |
| R49 | IM9 |
| R50 | IM10 |
| R51 | IM11 (→ Phase 7) |
| R52 | IM12 |
| R53 | IM13 |
| R54 | IM14 |
| R55 | IM15 |
| R56 | IM16 |
| R57 | IM20 (→ Phase 7) |
| R58 | IM9 (D9: no cloud fetch; C1 refresh) |
| R59 | not built ("Needs the user" 5) |
| R60 | F38 (onboarding; Phase 8) |
| R61 | not built (D19) |
| R62 | C: never (Lasso cloud) |
| AC1, AC2 | IM1 (AC2's lock: IM19) |
| AC3-AC5, AC10 | IM2 |
| AC6, AC7 | IM3 |
| AC8 | IM18 (waits for F12 36B, Phase 7) |
| AC9 | IM19 |
| AC11 | IM4 |
| AC12 | IM5 (notice: IM19) |
| AC13 | IM6 |
| AC14 | IM7 |
| AC15 | IM8 |
| AC16 | IM9 |
| AC17 | IM10 |
| AC18 | IM11 (→ Phase 7) |
| AC19 | IM12 |
| AC20 | IM13 |
| AC21 | IM14 |
| AC22 | IM15 |
| AC23 | IM16 |
| AC24 | IM17 |
| AC25 | IM20 (→ Phase 7) |

Unowned inside F33: none. R59 and R61 are left out by decision, R60 belongs to F38, R62 is Lasso's cloud.

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
| F21 R41, R42, R44-R47, AC10 | 35J (R41's shortcode conversion: 27A-27I, 27X1-27X8; R42, R44: not built, D3) |
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
| F27 the copy of Lasso's rows | 26H |

### F4 (ED6; moved here from Phase 4)

| R / AC | Task |
|---|---|
| F4 R50, AC21 | ED6 (R1-R4 of the legacy block, if N2) |
