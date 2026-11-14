# Affibloom — Phase 7: Link index, where-used, clicks, opportunities and tables — Implementation Plan

> **How to run this plan:** inline, in one working session, task by task, in the order below. Do not hand tasks to subagents. When a step is finished, change its `- [ ]` to `- [x]`.
>
> **Explain as you go:** before each task, say in two or three sentences what the task builds, why it is needed now, and how it will be checked. After the task, say what changed and what the verification showed. No vibe coding: every line written must be one you can explain.
>
> **Shared rules:** the architecture, working method, decisions, global constraints, file structure and API contract are in [`00-overview.md`](00-overview.md). Read it first.

## Status

Planned; gate 4 given by the user 2026-11-14 for every open task (tasks 26J and 34D are dropped, decision E1; 3 tasks moved here from Phase 5 have their sections in the Phase 5 file). Phase 6 (link health) must have its exit check met and be signed off first. Written 2026-11-14; the link index itself (F21 Tasks 35A-35J, F9 9J, F38 ON3 and ON4) was moved to Phase 5 by the coordinator the same day, so this phase starts from Locations (36A).

## Scope and features

Phase 7 builds where-used (Locations) on top of the link index that Phase 5 now provides, the click counter, opportunities, auto-monetization and comparison tables. Feature folders are under the plugin's `docs/features/`:

- **F21** content link index: `docs/features/F21/` (here only Tasks 36A, 36D and 36E's Locations half; the index tasks 35A-35J are Phase 5).
- **F12** clicks and Locations (where used): `docs/features/F12/` (Tasks 33A-33D, 34A-34C, 36A-36E; 34D dropped, decision E1).
- **C3** Google Analytics events: `docs/features/C3/` (Tasks C3A-C3C).
- **F19** the Dashboard's second pass, the sorting mechanism only: `docs/features/F19/` (Task 13K).
- **F22** Opportunities: `docs/features/F22/` (Tasks O1-O10).
- **F35** auto-monetization: `docs/features/F35/` (Tasks AM1-AM6).
- **C2** automatic affiliate-link detection, the first slice of F35: `docs/features/C2/` (Tasks C2A-C2C).
- **F25** comparison tables: `docs/features/F25/` (Tasks T1-T17).
- **F31** the table's Copy Shortcode: `docs/features/F31/` (Task 18S13, copied from the Phase 3 file).
- **F10** Lasso migration, parts: `docs/features/F10/` (Tasks 26I tables, 26N opportunities and the new features' settings, 29C parity of every link; 27E is moved here from Phase 5).
- **C1** Amazon product data, parts: `docs/features/C1/` (Tasks C1E scheduled refresh, C1H availability to link health).
- **F33** import from other plugins, parts moved here from Phase 5: Tasks IM11 (AAWP tables) and IM20 (WP All Import).

## Decisions that change this phase

- **F29, any number of buttons (the user, 2026-11-13; register U12):** a product has an ordered list of buttons after the primary (`Product::$buttons`, Task 14H). A click on button 3 or later counts for the product (Task 33A) and, with C3, sends `affibloom_secondary_click` labelled with its own text (the user's decision, F29 D3; event names renamed 2026-11-14).

Decided 2026-11-12; reasons in `docs/decisions/task-6-decisions.md` in the plugin repository. Apply them when the task is worked, and record any code that differs from the text below as a deviation in `progress.md`.

- Tasks 35A-35C (Phase 5) and 36A-36E: posts with status `publish`, `future`, `draft`, `pending` and `private` are indexed (F21 D6); the Locations tab shows the status of any post that is not published; a product used only in a draft counts as in use. The rebuild's comparison with the inventory (676 links) uses the published rows (decision 6).
- Task 34A: the Clicks column keeps Lasso's fixed 30 days (U5 rejected).
- **Superseded:** the 2026-11-12 decision "Task C2: only Impact and Post Affiliate Pro (decision 8); products are created automatically (U8 rejected)". The later full-scope rule governs: C2A-C2C and AM1-AM6 build every network Lasso knows (F35 D1, C2 requirements). U8 stays rejected: products are made without asking.
- **U9 (the user, 2026-11-13):** Task 34B adds the Dashboard's `Clicks (30 days)` overview tile (`.affibloom-tile`, success tint, the sum of the Clicks column's 30 days).
- **ADR-8 (the user, 2026-11-12):** the Clicks and Locations columns, the Locations tab and the disabled delete button use the Affibloom design system (`docs/ui/design-system.md`, Task 9A); the plan's CSS for them is superseded. Lasso's screenshots are compared only for columns, labels and their order.
- **Decided (the user, 2026-11-14), E1 (revised): start from zero.** No click figures are imported from Lasso: the Dashboard's Clicks count and Google Analytics both start from zero at cutover. Tasks 26J and 34D are dropped.
- **Decided (the user accepted the recommendation, 2026-11-14), E2:** accept that bot filtering differs from Lasso's cloud, and say so in the cutover notes.
- **Decided (the user, 2026-11-14), C6:** build Lasso's GA controls and the gtag.js loader in full (Task C3C is built) and import Lasso's settings values; tell the client `371244810` is wrong and Pageview should stay off.
- **Decided (the user, 2026-11-14), C3 N2 / C6 (revised): renamed.** Every Lasso-named GA event and parameter gets an Affibloom name; no backward compatibility with Lasso's GA reports; GA data starts from zero. GA4 rules (snake_case, at most 40 characters): the primary-click event is `affibloom_primary_click`; the secondary-click event is `affibloom_secondary_click` (also for added buttons 3 and up, F29 D3); the in-content link event is `affibloom_link_click`; the four parameters (title, href, text, badge) are `affibloom_title`, `affibloom_href`, `affibloom_text`, `affibloom_badge`; `event_category` and `event_label` keep their meaning (product name, button text).
- **Decided (the user accepted the recommendation, 2026-11-14), D7:** agree: one consistent visible sort state; F19 N1 (decision B10) and N3 (decision D8) belong to Phase 8's ON8 and ON9.
- **Decided (the user, 2026-11-14), A3:** monetize at render time plus "Edit in post" (A plus B's row action; Task O10).
- **Decided (the user accepted the recommendation, 2026-11-14), E16:** Amazon rows open "Create a Link" prefilled; short links are never followed.
- **Decided (the user accepted the recommendation, 2026-11-14), E17:** a link tied by its own URL can be turned back to the original URL at render time only.
- **Decided (the user accepted the recommendation, 2026-11-14), B6:** Program Opportunities are not built.
- **Decided (the user accepted the recommendation, 2026-11-14), E8:** Lasso's naming (page title or placeholder through the add path); C2 AC1 and its E2E title check follow.
- **Decided (the user accepted the recommendation, 2026-11-14), A11:** Amazon short links stay `amzn.to` in stored posts; readers get the full link through the render filter.
- **Decided (the user, 2026-11-14), E9:** the Amazon auto-import stays on; run it and review the list on staging first.
- **Decided (the user accepted the recommendation, 2026-11-14), D11:** the delete names the tables and removes the field from them.
- **Decided (the user, 2026-11-14), F1:** one staging session captures them (draft 9492's preview and one cloned Vertical table, deleted after).
- **Decided (the user accepted the recommendation, 2026-11-14), F2:** Tables stays the last area of this phase.
- **Decided (the user accepted the recommendation, 2026-11-14), E7:** re-read the Associates policies and Creators API terms when C1E starts.
- **Decided (the user, 2026-11-14), A5, A4, A2 (applies to Phase 5's 35E and 35J):** links that only other plugins render keep the attributes stored at Lasso's last scan (A5, accepted); Lasso's display conversions are done at render time (A4); the `data-lasso-*` stamps stay in content after cutover (A2).
- F10's other "Needs the user" items (1, 3-6), F9's, F38's and C1's N1-N3 belong to tasks in other phases. F12's Locations half has none.

## Tasks in this phase

### F21 / F12 — Locations (where used)

- [ ] Task 36A: The Dashboard's Locations column — count, sort, clock
- [ ] Task 36B: The product page's Locations tab
- [ ] Task 36C: Link with Context preview and the Clicks column
- [ ] Task 36D: The Placements filter
- [ ] Task 36E: Deleting a product in use is blocked

### F12 — Clicks

- [ ] Task 33A: A reader clicks a product element — `clicks.js` and `POST /affibloom/v1/clicks`
- [ ] Task 33B: Bots and repeats are not counted
- [ ] Task 33C: Old click rows are removed — `affibloom_daily`
- [ ] Task 33D: Settings → General — Page and Link-Level Tracking
- [ ] Task 34A: The Dashboard's Clicks column, sortable
- [ ] Task 34B: The Clicks (30 days) tile
- [ ] Task 34C: Clicks per location on the Locations tab
- Task 34D: dropped with 26J (decision E1: start from zero)

### C3 — Google Analytics events

- [ ] Task C3A: Settings → General — the Google Analytics section
- [ ] Task C3B: Clicks send Affibloom's Google Analytics events
- [ ] Task C3C: Load gtag.js for the configured IDs (decision C6: built)

### F19 — the Dashboard's sorting mechanism

- [ ] Task 13K: Sort the list by a column header (Alert, Locations, Clicks)

### F10 — migration families: opportunities

- Task 26J: dropped (decision E1: no click figures imported)

### C1 — Amazon: refresh job and availability

- [ ] Task C1E: The scheduled refresh job
- [ ] Task C1H: Availability handed to link health

### F22 — Opportunities

- [ ] Task O1: The Opportunities nav and Link Opportunities
- [ ] Task O2: Domains and Detected Links
- [ ] Task O3: Content Opportunities
- [ ] Task O4: A post's links (Content › post)
- [ ] Task O5: The product page's Opportunities tab and Detect Opportunities
- [ ] Task O6: Detect Opportunities in Settings, and Override All Links
- [ ] Task O7: Dismiss, and Undo
- [ ] Task O8: The Dashboard's tile, filter tab and lightbulb
- [ ] Task O9: Export CSV of content opportunities
- [ ] Task O10: Monetize and unmonetize at render time (N1-A)

### F35 and C2 — auto-detect and Amazon auto-monetization

- [ ] Task C2A: The affiliate-network registry — `wp affibloom links detect <url>`
- [ ] Task C2B: Detecting affiliate links when a post is indexed — `save_post`
- [ ] Task C2C: Products for detected links — WP-Cron `affibloom_auto_detect`
- [ ] Task AM1: The Amazon import — WP-Cron `affibloom_amazon_import`
- [ ] Task AM2: Amazon short links — the import's short-link branch and the map
- [ ] Task AM3: Switching Auto-Monetization on and off — Settings → Amazon
- [ ] Task AM4: The Amazon import notice — every Affibloom page
- [ ] Task AM5: The import status card, the Dashboard notice and the General stats
- [ ] Task AM6: Tracking IDs found in posts join the whitelist — `save_post` through F21

**Migration of the opportunity and auto-detect data (after F22 and F35 exist):**

- [ ] Task 26N: Opportunity switches, dismissals and the new features' settings — `wp affibloom migrate --only=opportunities`

### F25 — Comparison tables (with F31's table Copy Shortcode after T12)

- [ ] Task T1: The Tables list, and `GET /tables`
- [ ] Task T2: Create a table
- [ ] Task T3: The builder page and the table's settings
- [ ] Task T4: Products in a table — add, starting layout, badge, remove
- [ ] Task T5: Add a group to a table
- [ ] Task T6: Rows and columns, the Add Field dialog, plain cells
- [ ] Task T7: Image, price and description cells
- [ ] Task T8: Button information
- [ ] Task T9: Reordering
- [ ] Task T10: Table Locations
- [ ] Task T11: Clone and delete
- [ ] Task T12: The reader's Horizontal table, by shortcode
- [ ] Task 18S13: A table's Copy Shortcode
- [ ] Task T13: The reader's Vertical table
- [ ] Task T14: Phone cards and breakpoints
- [ ] Task T15: Preview Table
- [ ] Task T16: The Dashboard's Comparison table filter
- [ ] Task T17: A deleted product leaves its tables
- [ ] Task 23F: Table — "Choose a Table" in the block (moved here from Phase 4; with 24D's table half)

### F10 — migration family: tables

- [ ] Task 26I: Comparison tables — `wp affibloom migrate --only=tables`

### Moved here from Phase 5 (sections stay in the Phase 5 file until the coordinator moves them)

- [ ] Task 27E: `[lasso type="table"]` (moved here from Phase 5)
- [ ] Task IM11: AAWP tables (moved here from Phase 5)
- [ ] Task IM20: WP All Import (moved here from Phase 5)
- [ ] Task IM18: See It — the locations of an imported product (moved here from Phase 5)

### F10 — parity of every link

- [ ] Task 29C: Parity — every link, and no call to Lasso

## Cross-phase notes

**Overlaps settled 2026-11-14 (coordinator; nothing of Lasso dropped):**

- **GA card:** F9 **9F** (Phase 2) builds the Google Analytics card's controls and stores them; **C3A** adds only what
  9F does not (the event names and the settings' effect on `clicks.js`); C3B sends the events.
- **Custom Link Detection and Performance cards:** F9 9H/9I/9L own the controls and keys; F21 35D/35G (Phase 5) only
  their effect.
- **Page and Link-Level Tracking (33D) vs F9 N1:** no conflict. F9 N1 omits Lasso's cloud tracking; 33D keeps Lasso's
  toggle as the switch for Affibloom's own local counting (key `click_tracking`, imported from
  `performance_event_tracking`), with help text that names no cloud.
- **36C vs 34C:** 36C builds the Link with Context preview and the Locations tab's Clicks column as a cell; **34C**
  fills it with clicks per location. 36C's cell shows 0 until 34C.
- **O1 vs ON5:** O1 builds the Opportunities intro box with the education-box partial and the key
  `affibloom_settings['education_hidden']`; ON5 (Phase 8) reuses that partial for every other screen and adds reset and
  the Disable Help Tooltips rule (rule 5: extracted on the second use).
- **26N** runs after O7 and AM6, so all its sources exist.
- **26J and 34D are dropped (decision E1, 2026-11-14):** no click figures are imported; the Clicks column starts at zero at cutover.
- **35A's `Schema\Migrator`** is built by TL2 (Phase 2); 35A and 30A add their schema steps to it.

### Corrections and facts the whole phase relies on

- **F21 D14 was corrected on 2026-11-14 (the retag gate):** untied Amazon links are retagged at render time only on search pages or when a product has the ASIN (F35 R26). The render-time filter (Phase 5's 35H) follows the corrected D14; AM1 and AM2 here rely on it.
- **`assigned_product_id` (F22 N1-A):** F22's O10 needs this nullable, indexed column on F21's `affibloom_link_locations` table, kept across rescans by identity (F21 D10). **Phase 5's Task 35A must create it.** The F21 draft of 35A lacks it (I did not edit the draft): when 35A is placed in Phase 5, add the column to 35A's "creates" table; O10 here says "no migration here".
- **The event `affibloom_link_index_indexed`:** F35's C2B and AM6 listen to it. It must be fired by F21's `LinkIndexer::index()` at the end of each host (`$host`, `$rows`), i.e. by **35A-35C in Phase 5**. C2B here has no code for it, only tests that assert it fires.
- **The old usage index is superseded:** the table `affibloom_usage`, `wp affibloom usage rebuild`, `UsageIndexer`, `UsageStore`, `UsageHooks` (old Tasks 35A-35C) are replaced by `affibloom_link_locations` and `wp affibloom links rebuild`. The overview (`00-overview.md`) and `tools/README.md` still say `usage rebuild`; they were not touched here and need correcting. The counts are **676 in-content links, not 1,504** (old Task 33's and Tasks 35A-35C's "1,504" figures are retired).
- **Moved here from Phase 5 (coordinator, 2026-11-14), sections still in the Phase 5 file:** Task 27E (`[lasso type="table"]`, waits for T12), Task IM11 (AAWP tables, waits for F25's store) and Task IM20 (WP All Import). They are listed in the task list at the end, before 29C; the coordinator moves their text. **Copied here from Phase 3:** Task 18S13 (a table's Copy Shortcode), placed right after T12.
- **Moved to Phase 5 (coordinator):** F21 Tasks 35A-35J (index, displays, save hooks, custom link detection, builders, background rebuild, Link Index and Performance settings, render-time attributes, product save/repoint, SiteStripe and block displays), F9 9J (Link Index card), F38 ON3 (Automations) and ON4 (index notices). Everything here that says "35x" means the Phase 5 task.

### Earlier phases (named dependencies)

- **Phase 2:** 9B (General layout), 9D (override route), 9E (unsaved guard), 9F (GA card), 9H (Performance card), 9I (Custom Link Detection card), 9L (support view), ON1 (`AdminNavigation`; O1 and T1 register their tabs through it), ON2 (`NoticeBoard`, used by AM4), G1-G5 (F23: group displays in 36A, `GET /products?search=` of G4 used by O10 and T4, `GET /groups` in T5), FL1-FL5 (F26: fields 99989-99994, `POST /fields`), 13A-13J (Dashboard list, filters, saved views, `reset()`), 11A-12E (`ProductService::create_from_url()`, tag rule), 14A-14H (product page tabs, buttons), 15A-15D, 16B (`guarded()`), 17A-17D (delete, bulk), 10F (Amazon tab and switch), C1A-C1D, C1I (C1E needs C1C).
- **Phase 3:** Tasks 18-22 (box engine, `BoxAssets`, `data-affibloom-id` and `data-affibloom-btn` attributes: 33A; `BoxBuilder`), 18S1 and 18S10-18S13 (shortcode dispatcher and Copy Shortcode buttons: T3, T12), F16 (`show_disclosure_table`: T12).
- **Phase 5:** 25A-26E, 26C (settings import: 33D's `performance_event_tracking`, C3A's four GA values), 26G and F24 (cloaked addresses: 29C), 27 (`LassoIdMap`, `[lasso]`), 29A (29C), F33's import (the Link Index notices), plus **35A-35J** (see above).
- **Phase 6:** F11 30A (open issue rows) and 31A (the Alert cell: 13K adds its sort), 30-32 (issue store: C1H), the `affibloom_daily` event registered by F11's `LinkCheckCron` (33C), TL1/TL2 (`Schema\Migrator`, `Schema\Table`: ClickTable, DetectedLinksTable, table post types use them; if their names differ, the Phase 7 tasks follow TL2's).

### Later phases (what waits, and how it is stubbed)

- **Phase 6's F11 31A** shows the Alert cell with a plain header; its sort arm is added by **13K** here (Alert, Locations and Clicks together). Nothing in Phase 6 waits: the header is plain text until 13K.
- **Phase 4's "Choose a Table"** in the editor (F4, F25 R55) waits for **T1's `GET /tables`**; until Phase 7 that picker is not shown.
- **Phase 5's 28D** table part is built in **27E** here (after T12 and 26I); 27E, IM11 and IM20 now sit in this phase.
- **Phase 8:** ON5 (education boxes; needs O1's screen) is to replace O1's own `IntroBoxes` and `POST /opportunities/intros/{box}` by its component (second use) and read F9 9K's `disable_help_tips`; ON6-ON7 (wizard) reuse C3A's GA partial (F38 R53-R58, C3 R13) and C1A's card; ON9 (palette) and ON8 (tour) are not here; TL5 (uninstall list of posts still using Affibloom) reads the F21 index; Task 37 checks LiteSpeed's "delay JavaScript" against `clicks.js`; Task 38's library export is not the opportunities CSV (O9).
- **Phase 9:** Task 42 holds the first Amazon import on staging first (F35 N3), and re-runs 26C and 26N `--update` so the keys added by this phase are imported.
- **Within this phase:** 36C's Clicks cell is a 0 stub until 34C; 36A's and 34A's header sort waits for 13K (their sort tests run there); 26N's F22 parts wait for O5-O7 (26N is listed before them) and its settings keys for each feature's task; 26I waits for T-tasks (it is after them).

### Overlaps

All settled on 2026-11-14: see "Overlaps settled" at the top of these notes. 33C names the `affibloom_daily` hook registered by F11 30A (Phase 6), not a `DailyCron` class.

## Tasks

### Task 36A: The Dashboard's Locations column — count, sort, clock

**Feature:** F12 (L1-L3; D20, D21; AC20)
**Why:** the column has been "Coming soon"; every product's use is known now.
**Depends on:** 35A-35C; F19 Dashboard (13C/13D); F23 for group displays.
**User action:** the column shows the count linking to the Locations tab; sorts (13K adds the header's sort later in this phase); a clock while the first index runs.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Locations\LocationCounts`, `LinkLocationsTable::counts_for()` | `DashboardController`, `ProductService` |
| `order=count` in the list route (its arm is added by 13K) | `dashboard.js` |

**Files:** `product-row.php`, `DashboardController`, repository; tests unit, E2E F12 AC20 (its sort part runs in 13K), F19 rerun.
- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Counts.**
- [ ] **Step 3: Column and clock** (the sort: 13K).
- [ ] **Step 4: Run
  and look.**
- [ ] **Step 5: Commit** — "Show where each product is used on the Dashboard".

---

### Task 36B: The product page's Locations tab

**Feature:** F12 (L4-L9, L12; D22, D23; AC2, AC21, AC26)

**Why:** an editor must see where a product is used before changing or deleting it; Lasso's Locations tab is that list, and every earlier task only counted.
**Depends on:** 36A; the product page (Task 14).
**User action:** "Locations `<n>`": the four columns and tooltips, 10 hosts a page, grouped rows "(N locations)",
Content sorts, search, status labels for unpublished hosts, Lasso's empty state.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Locations\LocationService::page()`, `Rest\LocationsController` (`GET /products/{id}/locations`) | `product-locations.js`, `ProductScreen` |
| templates `product-locations.php`, partials `location-row.php`, `location-group-row.php` | `ProductScreen` |

**Files:** the above; E2E F12 AC2, AC21, AC26.
- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Service and route.**
- [ ] **Step 3: Tab.**
- [ ] **Step 4: Run and
  look.**
- [ ] **Step 5: Commit** — "List a product's locations on its page".

---

### Task 36C: Link with Context preview and the Clicks column

**Feature:** F12 (L10, L11; D24, D25; AC22, AC23)

**Why:** the editor needs to see a link in its paragraph and what it earns before deciding about it; Lasso's tab shows both (L10, L11).
**Depends on:** 36B; 35H's `data-affibloom-location`; the clicks half's per-location store.
**User action:** clicking the link text or icon opens the preview; Clicks shows 30-day clicks per location.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `GET /locations/{row}/preview`, partial `location-preview-modal.php` | `product-locations.js` |
| the Clicks cell: **a stub that prints 0 until 34C** (34C builds `ClickReport::by_location( product, 30 )` and fills the cell, later in this phase) | `LocationService` |

**Files:** the routes, partials and script named above; `tests/E2E/acceptance/F12.spec.ts` (AC22; AC23 turns green in 34C).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Preview.**
- [ ] **Step 3: Clicks cell as the 0 stub** (finished by 34C).
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Preview a location and show its clicks".

---

### Task 36D: The Placements filter

**Feature:** F12 (L13; AC24); F19 13E

**Why:** the Dashboard's filters panel narrows products by where they are used; it needs the index (F12 L13, F19 13E).
**Depends on:** 36A; F19 filters panel and saved views.
**User action:** "Placements": Any placement, In content, Not in content; counts as a filter; kept in views.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `placements` in the list route, `DashboardUrl`, `SavedViews`, repository condition | `dashboard.js` |

**Files:** `DashboardUrl`, `SavedViews`, the repository condition, `filters-panel.php`, `dashboard.js`; `tests/E2E/acceptance/F12.spec.ts` (AC24), the F19 filter specs rerun.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Filter.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** —
  "Filter products by whether they are in content".

---

### Task 36E: Deleting a product in use is blocked

**Feature:** F12 (L14-L16; D26; AC3, AC25); F1 D34, D35, D37

**Why:** deleting a product that posts still use would leave broken boxes and links; Lasso blocks it, and the old plan's rule (F12 AC3) is kept (L14-L16).
**Depends on:** 36A; F1 17A, 17B.
**User action:** disabled select box with the tooltip; bulk delete refuses all with the sentence; Delete This Link
on the page opens "Hold Up" listing the posts with only Keep This Link; the routes answer 409.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Products\ProductInUse`; `ProductService::delete()` / bulk check | `ProductsController` |
| `GET /products/{id}/locations/hosts`, partial `hold-up-modal.php` | the product page |

**Files:** the above; `tests/Integration/DeleteInUseTest.php`; F12.spec.ts AC3 updated (count first), AC25.
- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Service and routes.**
- [ ] **Step 3: Dialog and boxes.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Refuse to delete a product that is in use".

---

### Task 33A: A reader clicks a product element — `clicks.js` and `POST /affibloom/v1/clicks`

**Feature:** F12 (R5-R8 as replaced, R15-R20 not built; D1-D4, D9, D10; AC1, AC4, AC5, AC14, AC16-AC19)

**Why:** Lasso's counts come from its cloud (R2-R4); without a local counter every Clicks figure would be 0 after
cutover.

**Depends on:** the box HTML with `data-affibloom-id` and `data-affibloom-btn` on buttons, title and image (Phase 3,
Tasks 18A-22E; F29 buttons 14H); `LassoIdMap` (25A, Task 27A); F21's `data-affibloom-location` (D2; without it location 0);
repoint lookup (F1/F21).

**User action:** a reader clicks Buy Now, the second or a further button, the title, the image, or an in-content
product link (left, middle, Ctrl/Cmd); the link opens as before; one beacon goes to the site; nothing else is
contacted. With the route blocked, nothing visible happens either.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `assets/front/clicks.js`, `Interface\Front\ClickScript` (`maybe_enqueue`, `the_content` 999) | `BoxAssets::enqueue()`, WordPress |
| `Interface\Rest\ClicksController` (`POST /clicks`, always 204) | `rest_api_init` |
| `Application\Clicks\{ClickTracker, ClickInput, PostDirectory}`; `Domain\Clicks\{ClickStore, Button}`; `Domain\Product\LassoIdLookup` | the controller |
| `Infrastructure\Tables\ClickTable` (schema with `location_id`), `WordPress\WpPostDirectory` | `ClickTracker`, `Migrator` |

**Files:** create the above, `tests/Integration/ClicksRouteTest.php`, `tests/Unit/Application/ClickTrackerTest.php`;
modify `Migrator.php`, `BoxAssets.php`, `LassoIdMap.php`, `LegacyBlockConverter.php` (interface type), `Plugin.php`,
`docs/decisions/ADR-005.md` (the `location_id` column), `tests/E2E/acceptance/F12.spec.ts`.

- [ ] **Step 1: Failing tests.** E2E AC1, AC4 (title, image, button 2, button 3, link, middle-click), AC14, AC16
  (route aborted, link still opens), AC18 (record all request hosts), AC19 (a post without products has no
  `clicks.js`). Integration: counted; unknown product; unpublished post; repointed ID counted for the target; 204 each.
  Red: no route.
- [ ] **Step 2: Domain and table** (`Button` enum `1`-`9`, `title`, `image`, `link`; upsert).
- [ ] **Step 3: Tracker and route** (body ≤ 1 KB, `absint`, no cookie).
- [ ] **Step 4: The script and its enqueue** (sendBeacon, fetch keepalive fallback).
- [ ] **Step 5: Run and look.** DevTools: one `POST …/clicks` 204 per click; the row in the table; no other host.
- [ ] **Step 6: Commit** — "Count clicks on product elements with a first-party script".

---

### Task 33B: Bots and repeats are not counted

**Feature:** F12 (R9 replaced; D5, D6; AC6, AC7)

**Why:** Lasso left filtering to its cloud; a public local counter must filter itself.

**Depends on:** 33A.

**User action:** none visible; a crawler's or an empty user agent's click, and a second click within 30 s on the same
product, post and button, leave the count unchanged; after 30 s a click counts again.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Clicks\BotFilter`, `Application\Clicks\RepeatGuard` (interface), `Infrastructure\WordPress\TransientRepeatGuard` | `ClickTracker` |

**Files:** the above; `tests/Unit/Domain/BotFilterTest.php`; extend `ClicksRouteTest.php`, `ClickTrackerTest.php`,
`F12.spec.ts` (AC7).

- [ ] **Step 1: Failing tests** (Googlebot, empty UA, headless; repeat within 30 s; after expiry by deleting the
  transient).
- [ ] **Step 2: Filter and guard** (salted hash, nothing stored but the 30 s transient).
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Ignore bots and repeat clicks".

---

### Task 33C: Old click rows are removed — `affibloom_daily`

**Feature:** F12 (ADR-005 retention; AC8)

**Why:** daily rows are kept 400 days and no longer (ADR-005).

**Depends on:** 33A; the `affibloom_daily` event (registered by F11's `LinkCheckCron`, Phase 6).

**User action:** none; after the daily job, rows older than 400 days are gone.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Clicks\ClickRetention`, `ClickStore::delete_before()` | the `affibloom_daily` event (Phase 6) |

**Files:** the above, `tests/Integration/ClickRetentionTest.php`; modify `Plugin.php`.

- [ ] **Step 1: Failing test** (rows at 399, 400, 401 days; `wp cron event run affibloom_daily`).
- [ ] **Step 2: Retention.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Remove click rows after 400 days".

---

### Task 33D: Settings → General — Page and Link-Level Tracking

**Feature:** F12 (R14 replaced; D7; AC9)

**Why:** Lasso's toggle switches tracking off; the site owner keeps that switch.

**Depends on:** 33A; Task 9 (General tab, settings route); 26C imports `performance_event_tracking`.

**User action:** General shows "Advanced Click Tracking", the help text and the toggle **Page and Link-Level
Tracking** (on). Off and saved: product pages print no `clicks.js` (unless C3's Click Tracking is on), and the route
stores nothing. On: counting resumes.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `click_tracking` in the settings schema and `SettingsService`; the section in `settings/general.php` | the screen, `PATCH /settings` |
| the check in `ClickScript` and `ClickTracker` | 33A's code |

**Files:** the above; `F12.spec.ts` AC9; `docs/ui/design-system.md` unchanged (existing toggle row).

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Setting, screen, the two checks.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Let the site owner switch click counting off".

---

### Task 34A: The Dashboard's Clicks column, sortable

**Feature:** F12 (R10; D8; AC10, AC11)

**Why:** Lasso's Dashboard shows and sorts clicks; it has been a placeholder since 13C.

**Depends on:** 33A; F19 13C-13E (list, sort arms, saved views).

**User action:** Clicks after Locations: each product's last-30-day clicks in bold, 0 when none. The header sorts
(first click descending, then ascending), the caret shows it, the address and saved views keep `orderby=clicks`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Clicks\ClickReport::totals_for()`, `ClickStore::totals_since()` | `DashboardScreen`, `DashboardController` |
| the `clicks` sort arm (30-day SUM join) and the header's sort: **added by 13K later in this phase**, which takes this task's `totals_since()` join; until then the header is plain text | the list route |
| the column in `product-row.php` and `dashboard.php` header | the list |

**Files:** the above; `tests/Integration/ClickReportTest.php` (30-day edge), `DashboardClicksSortTest.php`; `F12.spec.ts`
AC10 (AC11, the sort, runs in 13K); F19 AC rerun (header changed).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Report** (the sort is 13K's).
- [ ] **Step 3: Column.**
- [ ] **Step 4: Run and look** (1280, 375 px; the sort both ways is checked in 13K).
- [ ] **Step 5: Commit** — "Show and sort 30-day clicks on the Dashboard".

---

### Task 34B: The Clicks (30 days) tile

**Feature:** F12 (U9; AC12)

**Why:** the user approved the overview tiles (U9); this task supplies the clicks number.

**Depends on:** 34A; the tiles row (Task 13 / U9).

**User action:** the Dashboard's tile **Clicks (30 days)** (success tint) shows the sum over published products.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `ClickReport::total_since()`; the tile in the tiles partial | `DashboardScreen` |

**Files:** the above; `F12.spec.ts` AC12.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Total and tile.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Add the 30-day clicks tile".

---

### Task 34C: Clicks per location on the Locations tab

**Feature:** F12 (R11 replaced; Locations D25; AC13, Locations AC23)

**Why:** Lasso's tab shows each location's clicks (from its cloud); Affibloom has them in `location_id`.

**Depends on:** 33A (location IDs stored), 36B (the tab and its route), F21 D13 (stamps).

**User action:** the tab's Clicks column ("Total clicks in the last 30 days.") shows each row's 30-day clicks, 0 when
none; a post's group row shows its locations' sum.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `ClickReport::by_location()`, `ClickStore::by_location()` | the locations route (36B) |
| the Clicks cell in the location row partials | the tab |

**Files:** the above; `F12.spec.ts` AC13 (and Locations AC23 turns green).

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Report.**
- [ ] **Step 3: Cells.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Show each location's clicks".

---

### Task 34D: dropped (decision E1)

No click figures are imported. The section is in `archive/phase-7-superseded.md`.

---

### Task C3A: Settings → General — the Google Analytics section

**Feature:** C3 (R1-R6; D5; AC4-AC7)

**Why:** the site owner sets GA as in Lasso.

**Depends on:** Task 9; 33D (same tab); 26C imports the four values.

**User action:** "Google Analytics", the ID field, Click Tracking, Pageview, IP Anonymization with Lasso's help texts;
toggles disabled until an ID is typed; clearing the ID switches them off; Click Tracking off switches the other two off;
clicking the disabled toggle shows "Please Enter GA Tracking ID Before Enable Click Tracking" and marks the field;
Pageview asks "Enable Pageview" (Enable / Cancel). Save and reload keep the values.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Analytics\GaSettings`; four keys in `SettingsService` | `PATCH /settings` |
| partial `settings/google-analytics.php`; rules and confirm in `assets/admin/settings.js` | the General tab |

**Files:** the above; `tests/Unit/Domain/GaSettingsTest.php`; `C3.spec.ts` AC4-AC7.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Domain and service.**
- [ ] **Step 3: Screen and script.**
- [ ] **Step 4: Run and look** (keyboard; 375 px).
- [ ] **Step 5: Commit** — "Add Lasso's Google Analytics settings".

---

### Task C3B: Clicks send Affibloom's Google Analytics events

**Feature:** C3 (R7-R10, R12; D1-D4, D6; AC1, AC2, AC8, AC9)

**Why:** the site's Google Analytics gets click events. **Decided (the user, 2026-11-14): Affibloom's own event names**, not Lasso's; no backward compatibility with Lasso's GA reports; GA data starts from zero. The client's GA reports and any GA4 conversions must use the new names.

**Depends on:** 33A (script, button attribute), C3A (`ga_click_tracking`), F29 D3.

**User action:** with Click Tracking on, a click sends one event: gtag (`affibloom_primary_click` / `affibloom_secondary_click` (every
button from the second on, F29 D3) / `affibloom_link_click`, with the parameters `affibloom_title`, `affibloom_href`, `affibloom_text`, `affibloom_badge`; `event_category` is the product name, `event_label` the button text), else a `dataLayer` push, else `ga('send', 'event', …)`; off or no library,
nothing; the click still counts.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Analytics\GaEventName`; GA part of `clicks.js`; `ga` block of `ClickScript`'s config | the click handler |

**Files:** the above; `tests/Unit/Domain/GaEventNameTest.php`; `C3.spec.ts` AC1 (extended: title, image, button 3),
AC2, AC8, AC9.

- [ ] **Step 1: Failing tests.** `C3.spec.ts` is code and exists: **its expected event and parameter names change first** (to the names above), so it fails for the right reason; then `GaEventNameTest` and the other cases.
- [ ] **Step 2: Name table (PHP and JS) and sending.**
- [ ] **Step 3: Run and
  look** (stub gtag; then the site's tag on this copy with GA's network blocked).
- [ ] **Step 4: Commit** — "Send
  Affibloom's own Google Analytics click events".

---

### Task C3C: Load gtag.js for the configured IDs (decision C6: built)

**Feature:** C3 (R11; AC10; AC3 replaced)

**Why:** Lasso loads GA itself when Click Tracking and an ID are set.

**Depends on:** C3A.

**User action:** with Click Tracking on and IDs saved, the page loads `gtag.js` for the first ID and configures each
with Pageview and IP Anonymization; otherwise nothing is printed.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Front\GaTag::print()` | `wp_footer` |

**Files:** the above; `C3.spec.ts` AC10 (AC3 rewritten to "with no ID, no GA script").

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: GaTag.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Load Google Analytics for the configured IDs".

---

### Task 13K: Sort the list by a column header (Alert, Locations, Clicks)

**Feature:** F19 (R1-R11; D8-D12; AC21-AC23); takes over F11 31A's "and its sort"; F12 AC11 and the sort part of F12 AC20 (34A's and 36A's arms)

**Why:** Lasso's Alert, Locations and Clicks headers sort the list; F12 34A and F21 36A expect "sort arms" from F19.
Built once here, in Phase 7, after the Alert cell (F11 31A, Phase 6) and the Locations (36A) and Clicks (34A) columns exist, so the three arms are added together; F11 31A, F12 34A and F21 36A add their arms through it.

**Depends on:** F11 30A (open issue rows) and 31A (the Alert cell); 13F (list arguments in forms), 13I (last key),
13J (`reset()`); F12 34A (Clicks column) and F21 36A (Locations column), built earlier in this phase, whose sort arms this task adds.

**User action:** **Alert** in the header is a button-link with the caret area. First click: products with the oldest
open alert first, products without one last, an up caret, `orderby=alert&order=asc` in the address, the same page
number (or the last page). Second click: newest first, a down caret. Third: the default order, no caret, no `orderby`.
Page links, the page size, a search, the Link type filter and Back keep the sort; **Save as** stores it and the pill
restores it; **Reset filters** clears it. Without JavaScript the header is a plain link to the next state. Locations
and Clicks headers sort the same way: Locations by its count (36A), Clicks by its 30-day total (34A).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Product\ListOrder` (column `Modified`/`Alert`/`Locations`/`Clicks`, direction; `from_input()`, `next( column )` with Lasso's cycle and Alert's off, `is_default()`) | screen, route, `SavedView`, the header template |
| `Domain\Product\ListQuery` (term, link kind, order, page, per page, post_id), the sixth argument making the bundle | `ProductService::list()`, `DashboardUrl::to()` |
| the order arm map in `PostTypeProductRepository::page()` (`posts_clauses` scoped by a query var): `modified` → `post_modified <dir>, ID DESC`; `alert` → F11's open-issue join, `IS NULL ASC, detection <dir>, ID DESC`; `locations` → 36A's count (`LinkLocationsTable::counts_for()`, `order=count`); `clicks` → 34A's 30-day SUM join | the list |
| `orderby`, `order` in `GET /dashboard/products` (enums), `DashboardUrl`, `list-args.php`, `SavedView` and its storage, `reset()` | `dashboard.js`, the forms |
| sortable header cells (`aria-sort`, a link to `ListOrder::next()`, caret icon) in `partials/product-list.php` | the list |
| "Save as" tip and Reset title with "sort" (D12) | `filters-panel.php` |

**Files:** create `src/Domain/Product/{ListOrder,ListQuery}.php`; modify `ProductService.php`,
`ProductRepository.php`, `PostTypeProductRepository.php`, `DashboardScreen.php`, `DashboardUrl.php`,
`DashboardController.php`, `Domain/Dashboard/SavedView.php`, `UserMetaSavedViewRepository.php`,
`templates/admin/partials/{product-list,list-args,saved-views,filters-panel}.php`, `assets/admin/dashboard.js`,
`admin.css`, `docs/ui/design-system.md` (sortable header); tests `ListOrderTest`, `SavedViewsTest`,
`F19.spec.ts` AC21-AC23, F11 AC rerun.

- [ ] **Step 1: Failing tests.** AC21-AC23; `ListOrderTest` (the cycle for Alert and for a desc-first column); F12 AC11 (Clicks header) and the sort part of F12 AC20 (Locations header) join the E2E file.
- [ ] **Step 2: Domain.** `ListOrder`, `ListQuery`; `SavedView` holds a `ListOrder` (a stored view without one is the
  default).
- [ ] **Step 3: Repository and route.** The arm map; enums; the answer echoes the applied order.
- [ ] **Step 4: Header, address, forms, views, Reset.**
- [ ] **Step 5: Run and look** (both directions and off, page 2 kept, Back, a view, JavaScript off; 375 px header).
- [ ] **Step 6: Commit** — "Sort the Dashboard by its Alert, Locations and Clicks columns, as Lasso does".

---

### Task 26J: dropped (decision E1)

No click figures are imported. The section is in `archive/phase-7-superseded.md`.

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

### Task C1E: The scheduled refresh job

**Feature:** C1 (R37-R39, D8; AC6, AC10) · **Without credentials:** the event runs and does nothing

**Why:** Lasso keeps Amazon data under 24 hours old (requirements N4).

**Depends on:** C1C; F21's content link index (locations-only arm; until it exists the setting's on-state reads "all").

**User action:** none; "Update Amazon pricing daily" and "Only update products with Locations" switch it.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Amazon\RefreshPolicy`, `AmazonDataService::refreshDue()`, `repository::due()` | `AmazonRefreshCron` |
| `Interface\Cron\AmazonRefreshCron` (`affibloom_amazon_refresh`, hourly; cleared when the switch is off) | WP-Cron |

**Files:** the above; `RefreshPolicyTest.php`; `C1.spec.ts` AC6 (un-fixme), AC10 via `wp cron event run`.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Policy and query.**
- [ ] **Step 3: Cron.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Refresh due Amazon products every hour, as Lasso does".

---

### Task C1H: Availability handed to link health

**Feature:** C1 (R55-R57, D11; AC13) · **Without credentials:** dormant (F11 shows Amazon checks as unavailable)

**Why:** out of stock and not found exist only from product data.

**Depends on:** C1E; F11 Tasks 30A-32E (issue store).

**User action:** none on a screen of its own: when Amazon's data says a product is out of stock or not found, link health shows it (an out-of-stock issue `000`, a broken `404`); a later in-stock fetch resolves it (AC13). With no credentials nothing is recorded and F11 shows Amazon checks as unavailable.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Amazon\LinkHealth` (port) and its adapter to F11's issue store | `AmazonDataService::fill()`, `refresh()`, `refreshDue()` |

**Files:** the port, adapter, `Plugin.php`; `C1.spec.ts` AC13.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Port and adapter.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Record Amazon out-of-stock and not-found results for link health".

---

### Task O1: The Opportunities nav and Link Opportunities

**Feature:** F22 (R1-R16, R74-R80 as omissions; D1-D8, D14, D15, D20, D21; AC1, AC2, AC13)

**Why:** Link Opportunities is the core list — a raw link to a domain the site already has a product for. Everything
else on these screens reuses its header, list machinery and row.

**Depends on:** F21 35A-35C (rows exist and follow saves); F12 36C (the preview route); F19 13D (the in-place list
pattern); `PostTypeProductRepository` (F1).

**User action:** the header's new **Opportunities** tab (after Dashboard) opens "Link Opportunities": sub-tabs
**Links** (active), **Domains**, **Content** (the latter two say "Coming soon" until O2 and O3); "N Found"; "Search";
the intro box "Turn Any Existing Link Into a Product Link" with ×, which closes it for good; the table URL, Content,
Suggestion, Type, Monetize (the switch shows its state, disabled with "Coming soon" until O10), ten a page with "a-b
of N" and page links. Hovering a row shows **Filter by URL** (disabled until O2), **Open in New Window**, **Filter by
Content** (disabled until O4), **Filter by Link** (→ "Link › <product>" with a back arrow). The Type icon opens the
preview. Search and sort redraw the list in place; with none, "No opportunities found.". No Keywords, Programs or
Marketplace tab exists.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Opportunity\{Opportunity, OpportunityQuery, SiteHost, AmazonDomains, BaseDomain, LinkTypeIcon, OpportunityRepository}` | `OpportunityService`, `ProductService` (BaseDomain) |
| `Application\Opportunities\OpportunityService::links()`, `IntroBoxes` | the screen, the routes |
| `Infrastructure\Tables\OpportunityQueries::link_page()` (D4), `WordPress\OptionHiddenIntros` | the service |
| `_affibloom_domain` written by `PostTypeProductRepository::save()` via `ProductService`; `WordPress\ProductDomainBackfill` (upgrade step, once) | product saves; `Plugin` on version change |
| `Admin\OpportunitiesScreen`, the submenu page, the header's Opportunities tab | WordPress |
| `Rest\OpportunitiesController`: `GET /opportunities/links`, `POST /opportunities/intros/{box}` | `opportunities.js` |
| templates `opportunities.php`, partials `opportunity-subnav.php`, `opportunity-intro.php`, `opportunity-link-row.php` | the screen, the route |
| `assets/admin/opportunities.js` (list in place, intro close, the preview from F12's modal) | the screen |

**Files:** create the above, `tests/E2E/acceptance/F22.spec.ts` (AC1, AC2, AC13),
`tests/Unit/Domain/Opportunity/{BaseDomainTest,AmazonDomainsTest,LinkTypeIconTest,OpportunityQueryTest}.php`,
`tests/Integration/Opportunities/LinkOpportunitiesTest.php`; modify `src/Plugin.php`,
`src/Interface/Admin/{AdminMenu,AdminAssets}.php`, `templates/admin/partials/header.php`,
`src/Infrastructure/WordPress/PostTypeProductRepository.php`, `src/Application/Products/ProductService.php`,
`assets/admin/admin.css`, `docs/ui/design-system.md` (opportunity row, intro box), `docs/spec/interface.md` (the
Opportunities rows, corrected from "excluded").

- [ ] **Step 1: Failing tests.** E2E AC1 (27 rows for the fixture; search "luminous" → 18; sort by Suggestion; the
  empty state after dismissing through WP-CLI is O7's — here an empty fixture), AC2 (each hover link's address; the
  editor link; the preview text), AC13 (no Keywords/Programs/Marketplace text anywhere on the screens; no request to
  `getlasso.co`, `lasso.link`). Integration: D4's exclusions one by one. Unit: the four domain classes. Expected red:
  no Opportunities tab.
- [ ] **Step 2: Domain classes and `_affibloom_domain`** (save and backfill; unit and an integration check that 263
  products get their domain).
- [ ] **Step 3: Query, service, routes.** The allow-list `url`, `post_title`, `suggestion`; default location id, product
  id (D6); `esc_like` + spaces as `%`.
- [ ] **Step 4: Screen, templates, script, intro box** with D7's words; the closed box stored (D8).
- [ ] **Step 5: Run and look.** Checks; F19 and F23 specs (the header changed); by hand: list, sort, search, Filter by
  Link and back, intro close and reload, preview, keyboard only, 375 px.
- [ ] **Step 6: Commit** — "Add the Opportunities screen with Lasso's Link Opportunities".

---

### Task O2: Domains and Detected Links

**Feature:** F22 (R17-R23, D3, D7, D16; AC3)

**Why:** the domain view shows where the site links without earning, product or not, and Detected Links is the
drill-down every "Filter by URL" points at.

**Depends on:** O1.

**User action:** **Domains**: the intro box "Find Site-Wide Monetization Opportunities" (×), the table Domain, a
lightbulb column ("This domain has possible opportunities" for a domain with a product or Amazon's), Links, biggest
first; Domain and Links sort; a row opens **Detected Links › <domain>** (back arrow to Domains): URL, Content (hover
Filter by Content), Link (opens the preview), Type, Monetize (disabled until O10); search by post title or anchor
text. O1's Filter by URL now works.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Opportunity\DomainCount`; `OpportunityQueries::domain_page()`, `domain_links_page()` (R17, R18 quirk, R20) | `OpportunityService::domains()`, `domain_links()` |
| `GET /opportunities/domains`, `GET /opportunities/domains/{domain}/links` | `opportunities.js` |
| partials `domain-row.php`, `domain-link-row.php`; the Domains intro box | `OpportunitiesScreen` (`tab=domains`, `tab=domain`) |

**Files:** the above; tests F22 **AC3**, `tests/Integration/Opportunities/DomainOpportunitiesTest.php` (grouping, NULL
and own domain out, the "Opportunities" quirk, the lightbulb rule).

- [ ] **Step 1: Failing tests** (AC3 on the fixture: usa.mizuno.com 3 with a lightbulb, amazon.com 3 with one, an
  unknown domain without; Detected Links' rows and search).
- [ ] **Step 2: Queries and service.**
- [ ] **Step 3: Routes, partials, sub-tab live.**
- [ ] **Step 4: Run and look** (checks; both lists by hand, 375 px).
- [ ] **Step 5: Commit** — "Add Lasso's Domain Opportunities and Detected Links".

---

### Task O3: Content Opportunities

**Feature:** F22 (R24-R28, R32, R33, D3, D7, D17, D18; AC4)

**Why:** the per-post view finds under-monetized posts; its filters are the export's (O9).

**Depends on:** O1; F12's click store for Min link clicks (hidden until it exists, D17).

**User action:** **Content**: the intro box "Identify And Fix Your Under-Monetized Content" (×); the bar Show (All
content / Has unmonetized links), Post type (All types / Posts / Pages), Category (All categories + every category),
Min link clicks (number); the table Last Updated ("Nov 9th, 2026"), Content, Permalink (opens the post), Links,
newest first; a filter change reloads page 1; a row opens the post's links (O4; until then "Coming soon").

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Opportunity\{ContentRow, ContentFilter}`; `OpportunityQueries::content_page()` | `OpportunityService::content()` |
| `GET /opportunities/content` (returns `export_count` from O9 once it exists; 0 before) | `opportunities.js` |
| partials `content-row.php`, `content-filters.php` | `OpportunitiesScreen` (`tab=content`) |

**Files:** the above; tests F22 **AC4**, `tests/Unit/Domain/Opportunity/ContentFilterTest.php`,
`tests/Integration/Opportunities/ContentOpportunitiesTest.php`.

- [ ] **Step 1: Failing tests** (AC4: the fixture's host listed with Links = its row count; "Has unmonetized links";
  Post type Pages hides it; a category filter; Min link clicks hidden without the click store).
- [ ] **Step 2: Filter normalising, query, service.**
- [ ] **Step 3: Route, partials, script.**
- [ ] **Step 4: Run and look** (checks; filters by hand, 375 px).
- [ ] **Step 5: Commit** — "Add Lasso's Content Opportunities with its filters".

---

### Task O4: A post's links (Content › post)

**Feature:** F22 (R29-R31, D3; AC12)

**Why:** Content's rows, and every "Filter by Content", open this list of one post's links.

**Depends on:** O3.

**User action:** **Content › <post>** (back arrow to Content): URL (hover Filter by URL), Link (anchor text or image
URL; opens the preview), Type, Monetize (on for tied links; disabled until O10); product links first, then internal,
then external; search by URL or anchor text. Without a post, every published post's links.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `OpportunityQueries::post_links_page()` | `OpportunityService::post_links()` |
| `GET /opportunities/posts/{id}/links` | `opportunities.js` |
| partial `post-link-row.php` | `OpportunitiesScreen` (`tab=post`) |

**Files:** the above; tests F22 **AC12**, an integration case in `ContentOpportunitiesTest.php`.

- [ ] **Step 1: Failing tests** (AC12: #4375's Text and Image-only rows, tied ones first and on; displays absent).
- [ ] **Step 2: Query, route, partial.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Add a post's links under Content Opportunities".

---

### Task O5: The product page's Opportunities tab and Detect Opportunities

**Feature:** F22 (R34-R39, D9, D20; AC5, AC6)

**Why:** a product shows its own opportunities and can opt out; the switch is the per-product half of D4's rule.

**Depends on:** O1; F1 14A (tabs), 14B (display card placeholder), 15A (`PATCH /products/{id}`).

**User action:** the product page's **Opportunities `<n>`** tab (no longer "Coming soon") lists the product's
opportunities (O1's columns, filter links hidden), with "Search"; the badge equals the list's total and changes after
Save Changes. On the display card, **Detect Opportunities** ("When disabled, Link Opportunities will not show for this
product.") is saved with the product; off, the tab is empty, the badge 0 and the product leaves O1's list.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `_affibloom_detect_opportunities` in `Product`, `ProductInput`, `PostTypeProductRepository`; the default from `detect_opportunities` | `ProductService` |
| `ProductResource::full()` gains `detect_opportunities`, `counts.opportunities` | `product.js` (the badge after save) |
| the tab in `ProductScreen` (`tab=opportunities`), the switch in `product-display-card.php` | WordPress, the product page |
| `GET /opportunities/links?product=N&context=product` (O1's route, product rows) | `opportunities.js` on the product page |

**Files:** the above; tests F22 **AC5**, **AC6**; unit `ProductInputTest` (the flag); F1's spec updated for the tab.

- [ ] **Step 1: Failing tests** (AC5: product #14695's tab lists 3, badge 3; AC6: switch off → 0 here and in O1's list;
  a new product takes the setting).
- [ ] **Step 2: The flag through input, product, repository, resource.**
- [ ] **Step 3: The tab and the switch.**
- [ ] **Step 4: Run and look** (F1 and F22 specs; 375 px).
- [ ] **Step 5: Commit** — "Add the product's Opportunities tab and Detect Opportunities".

---

### Task O6: Detect Opportunities in Settings, and Override All Links

**Feature:** F22 (R41-R43, D7, D9; AC7)

**Why:** the site-wide default and the one-click change for every product, as Lasso's General settings.

**Depends on:** O5; F9 (Link Defaults, the "Update Link Settings" dialog and its override route).

**User action:** Settings → General → Link Defaults gains **Detect Opportunities** ("When off, Link Opportunities won’t
show for affected links. Doesn’t change how Affibloom finds opportunities in the background."). Changing it opens
"Update Link Settings" ("Affibloom will disable the option Detect Opportunities"): **Only New Links** closes;
**Override All Links** sets every product at once. Save Changes stores the setting.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `detect_opportunities` in `Settings`, `SettingsService`, the General template | F9's `SettingsController` |
| `ProductService::set_detect_opportunities_for_all()` (`PostTypeProductRepository::set_meta_for_all()`) | F9's override route with key `detect_opportunities` |

**Files:** the above; tests F22 **AC7**; F9's spec (the new switch).

- [ ] **Step 1: Failing tests** (AC7: Override All Links → every product's meta '0'; Only New Links → unchanged; a new
  product after save takes '0').
- [ ] **Step 2: Setting and override.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Add Detect Opportunities to Link Defaults".

---

### Task O7: Dismiss, and Undo

**Feature:** F22 (R44-R49, D10-D12, D20; AC8)

**Why:** a suggestion the editor rejects must go and stay gone — Lasso's comes back after a rescan.

**Depends on:** O1, O5; F21 35C (replace per host, where D10 keeps the flag).

**User action:** the **×** on a Link Opportunities or product-tab row asks "Dismiss This Opportunity" ("If you dismiss
this, it will no longer appear as an opportunity.", Cancel, **Dismiss**); Dismiss hides the row, the counts fall, and
"Opportunity dismissed." offers **Undo**, which brings it back. Saving the post or rebuilding the index keeps it
dismissed. Domains, Detected Links and Content still list the link (as Lasso).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `POST` / `DELETE /opportunities/locations/{row}/dismiss` | `opportunities.js` |
| `OpportunityService::dismiss()`, `restore()` (`edit_post` on the host) | the routes |
| `LinkLocationsTable::set_ignored()`; F21's replace keeps `is_ignored` by identity (D10) | the service; F21's indexer |
| partial `dismiss-modal.php` (the shared confirm dialog), `Messages` `opportunity_dismissed` | the screen |

**Files:** the above; tests F22 **AC8**; integration `DismissTest.php` (dismiss, re-index the host, still dismissed;
undo; 404 for a gone row).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Repository, the F21 carry-over, service, routes.**
- [ ] **Step 3: Dialog, notice with Undo, row removal.**
- [ ] **Step 4: Run and look** (keyboard focus returns to the
  list after the dialog).
- [ ] **Step 5: Commit** — "Add Dismiss to opportunities, kept across rescans".

---

### Task O8: The Dashboard's tile, filter tab and lightbulb

**Feature:** F22 (R50-R56, D13; AC9)

**Why:** the Dashboard is where products with opportunities are noticed; F19 left the tile, tab and icon empty.

**Depends on:** O1, O5, O7; F19 13C-13E.

**User action:** the green **See opportunities** tile shows the count (9 here); clicking it lists only those products,
sorted by their count, biggest first, and clicking again returns to All; each row's Alert column shows the green
lightbulb "This URL has N possible opportunities." (hosts on this tab, rows elsewhere), linking to the product's tab.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Opportunities\OpportunityCounter` (`products_with_opportunities()`, `per_product( ids, by_hosts )`) | `DashboardController` (F19) |
| the list route's `filter=opportunities`, `orderby=opportunity_count`, `totals.opportunities`; saved views keep the filter | `dashboard.js` |
| the tile and the lightbulb in `dashboard-tiles.php`, `product-row.php` | the Dashboard |

**Files:** the above; tests F22 **AC9**, unit `OpportunityCounterTest.php`; F19's spec (tile and Alert column).

- [ ] **Step 1: Failing tests** (AC9: tile 9; the tab's rows; the tooltip's plural and singular; the link).
- [ ] **Step 2: Counter, route parameters.**
- [ ] **Step 3: Tile, tab, icon.**
- [ ] **Step 4: Run and look** (F19 specs green; 375 px).
- [ ] **Step 5: Commit** — "Show opportunities on the Dashboard".

---

### Task O9: Export CSV of content opportunities

**Feature:** F22 (R57-R63, D17, D19; AC10)

**Why:** Lasso's spreadsheet of unmonetized anchors is how an editor plans a monetizing pass.

**Depends on:** O3.

**User action:** on Content, **Export CSV** (disabled with "No content opportunities to export for the current
filters." when there is nothing) shows "Exporting", downloads `affibloom-content-opportunities-<date>.csv` for the
current search, filters and sort, then "Exported N anchors."; an error shows its message.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Opportunity\CsvRow` (R60 columns, normalising, R61 guard) | `ContentExport` |
| `Application\Opportunities\ContentExport::count()`, `stream()` (keyset batches of 100) | the controller, O3's route (`export_count`) |
| `OpportunityQueries::export_batch()`, anchor context through F12's preview finder | `ContentExport` |
| `Rest\ContentExportController`: `GET /opportunities/content/export` | the button |

**Files:** the above; tests F22 **AC10**, unit `CsvRowTest.php`, integration `ContentExportTest.php` (headers, row count
2,061-style on the fixture, order, guard, dismissed rows included).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Row, export, query.**
- [ ] **Step 3: Route and button.**
- [ ] **Step 4: Run and look** (open the file in a spreadsheet; a `=`-cell is text).
- [ ] **Step 5: Commit** — "Add Lasso's content opportunities CSV export".

---

### Task O10: Monetize and unmonetize at render time (N1-A)

**Feature:** F22 (R64-R73, N1-N3, D21, D22; AC11)

**Why:** the switch is what makes an opportunity pay; Lasso rewrites the post to do it, Affibloom prints the product's
link at render time instead (N1-A, pending the user).

**Depends on:** O1, O2, O4, O5; F21 35H (the render filter); F18 (Add a Link, quick-detail); F23 D10 (product search).

**User action:** turning a row's **Monetize** switch on opens **Monetize**: **Create a Link** (F18's Add a Link, the
row's URL prefilled for an Amazon row, N2), "Search URLs" ("Search N URLs"), six products a page with thumbnail, name,
URL and **Monetize**; closing without a choice turns the switch off. Choosing a product turns the row green, its URL
shows the product's link, and it leaves the opportunity lists; the post on the front end prints the product's link,
`target` and `rel` on that anchor; the stored post is unchanged. Turning it off clears the assignment (readers see the
stored link again). A link tied by its own URL is on and disabled, "This link is in the post's content; edit the post
to change it." (or, with an original URL, offers N3's override). Each row also has **Edit in post** (N1-B).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `assigned_product_id` (the column 35A created on F21's table; no migration here), kept by identity on replace; read first by `LinkAttributesFilter` and by the "tied" tests of F12 and D4 | the service; F21's indexer and filter |
| `PUT` / `DELETE /opportunities/locations/{row}/assignment` | `opportunities.js` |
| `OpportunityService::assign()`, `unassign()` (`edit_post` on the host; 409 for N3's case) | the routes |
| partial `monetize-modal.php`, the product search (F23 D10's route, six a page) | the four lists with a switch |
| the editor helper `assets/editor/select-location.js` (selects the anchor for `affibloom_location=<row>`) | Edit in post |

**Files:** the above; tests F22 **AC11** (with a byte comparison of `post_content` before and after), integration
`AssignmentTest.php` (render output, survives re-index, drops when the anchor's URL changes).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Carry-over and filter read** (the column is 35A's).
- [ ] **Step 3: Service, routes.**
- [ ] **Step 4: Dialog, switch states, Edit in post.**
- [ ] **Step 5: Run and look** (front end of #4375 before and
  after; the editor shows the stored link).
- [ ] **Step 6: Commit** — "Monetize opportunities at render time, without editing posts".

---

### Task C2A: The affiliate-network registry — `wp affibloom links detect <url>`

**Feature:** F35 (R2-R9, R16 data; D1, D2, D7, D8); C2 AC6

**Why:** every other piece — detection, the kept affiliate URL, sub IDs on redirects — asks one question: "is this an
affiliate link, of which network, with which IDs?". Lasso answers it from one data file; so does Affibloom.

**Depends on:** F21 35A (`Cli\LinksCommand` exists; this adds a subcommand). Nothing else.

**User action:** a shell user runs `wp affibloom links detect "https://goto.walmart.com/c/3512787/565706/9383?u=…"` and
sees "Impact (impact): affiliate 3512787, advertiser 9383, deep link https://www.walmart.com/…, domain walmart.com";
an Everflow link prints "Everflow (everflow): an affiliate link, not auto-detected"; an Amazon link "Amazon
Associates: not detected (Amazon is the Amazon import's)"; anything else "No known network."; no argument → WP-CLI's
usage error.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `resources/networks.php` (37 entries, 32 extraction rules, 805 Impact hosts, 21 Amazon hosts; Appendices A-B) | `NetworkRegistry` factory in `Plugin` |
| Domain `Monetize\{Network, NetworkRegistry, NetworkExtractor, DetectedLink}` (pure) | `LinksCommand::detect` |
| `Cli\LinksCommand::detect()` | WP-CLI |

**Files:** create the above; modify `src/Plugin.php`, `src/Interface/Cli/LinksCommand.php`; tests
`tests/Unit/Domain/Monetize/{NetworkRegistryTest,NetworkExtractorTest}.php`,
`tests/Integration/LinksDetectCommandTest.php`, `tests/E2E/acceptance/C2.spec.ts` (AC6).

- [ ] **Step 1: Failing tests.** E2E C2 AC6: the command on one URL per network family (Impact both shapes, Post
  Affiliate Pro by fragment, Awin, a registry-only network, Amazon, a plain link). Unit: a data provider over all 37
  entries with Lasso's example URLs (the class doc blocks, the regexes' own hosts), one non-match each, first-match
  order, the accidental matches of R6 pinned (CAKE `a=1&x&c=2`, FlexOffers `fxo.co` mid-URL, TradeTracker `?tt=1`),
  every Appendix B rule (case-insensitive keys, `&amp;`, `u0026`, Partnerize `/destination:`). Expected red: no
  subcommand / no class.
- [ ] **Step 2: The data file.** Transcribe Lasso's three JSON files and the 32 classes into `resources/networks.php` by a
  one-off script run by hand (not committed), then check the counts (37, 32, 805, 21) in a unit test.
- [ ] **Step 3: Domain classes** (R2's order, R3's two host forms, R4, R5, R9).
- [ ] **Step 4: The subcommand.**
- [ ] **Step 5: Run and look.** The 16 URLs of `wp_lasso_auto_monetize` give Lasso's stored network and IDs (15 Impact,
  1 Post Affiliate Pro); `wp affibloom links detect` over the site's 3,015 indexed URLs (a loop in `wp eval`) finds only
  the 29 Mizuno links and the 30 rows of the 16 products.
- [ ] **Step 6: Commit** — "Recognise every affiliate network Lasso knows".

---

### Task C2B: Detecting affiliate links when a post is indexed — `save_post`

**Feature:** F35 (R1 read, R10-R12; D3, D4, D23); C2 AC2 (recorded once), AC3, AC4, AC5, AC7, AC8 (rebuild)

**Why:** Lasso detects while it scans; Affibloom's scan is the content link index, so detection listens to it instead
of parsing posts a second time.

**Depends on:** C2A; F21 35A-35C (the index, `save_post`) and 35F (rebuild, daily pass); F9 9B (the switch,
`auto_detect_links`).

**User action:** an editor publishes a post with a network link: nothing visible changes in the editor or the post;
the link is recorded (seen with `wp db query` or C2C's product). With Auto-Detect off, nothing is recorded.
`wp affibloom links rebuild` records the links of every indexed post.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Tables\DetectedLinksTable` (F21's `Schema\Table`, migrator version +1) | `Schema\Migrator` |
| Domain `Monetize\DetectedLinkRepository` (interface); infra `Tables\DetectedLinksTable` repository (`record` INSERT IGNORE by `url_hash`, `set_product`) | `AffiliateDetector` |
| Application `Monetize\AffiliateDetector::detect( rows )` (untied External rows; ties a URL already a product) | `AutoDetectHooks` |
| the event `affibloom_link_index_indexed` (`$host`, `$rows`): fired by F21's `LinkIndexer::index()` since 35A, nothing to add here (this task's tests assert it fires) | `LinkIndexHooks`, rebuild, daily pass |
| `Hooks\AutoDetectHooks` (Module: listens to the event when `auto_detect_links`; schedules `affibloom_auto_detect` once, 5 s later, when a row has no product) | WordPress |

**Files:** create the above; modify `src/Plugin.php`; tests
`tests/Unit/Application/AffiliateDetectorTest.php`, `tests/Integration/AutoDetectHooksTest.php`,
`tests/E2E/acceptance/C2.spec.ts` (AC3, AC4, AC5, AC7; AC2's "recorded once").

- [ ] **Step 1: Failing tests.** E2E: a post with an Impact link → one row (`wp db query` through `wp eval`), content md5
  unchanged; saved again → still one row; a plain shop link, an internal link and an Amazon link → no row; setting off →
  no row and no event scheduled; a URL already a product → row with its `product_id`, nothing scheduled. Red: no table.
- [ ] **Step 2: Table and repository.**
- [ ] **Step 3: The detector and the hooks** (the event is 35A's).
- [ ] **Step 4: Run and look.** `wp affibloom links rebuild` on this copy: 16 rows, all tied to the migrated products,
  no event scheduled; content of the 5 posts unchanged (md5 before/after).
- [ ] **Step 5: Commit** — "Record affiliate links when a post is indexed".

---

### Task C2C: Products for detected links — WP-Cron `affibloom_auto_detect`

**Feature:** F35 (R13-R15; D5, D6, D7); C2 AC1, AC2 (no duplicate product), AC8 (daily run), AC9

**Why:** Lasso turns every detected link into a product without asking (U8 rejected); this is that step, in the
background, through the same add path as a pasted link.

**Depends on:** C2B; F18 `ProductService::create_from_url()` (Tasks 11-12, 17D); F21 35H (the render filter) and its
`ProductResolver`.

**User action:** seconds after publishing a post with a new Impact link, the Dashboard lists a new product named as a
pasted link would be (F18 D3; F35 N1), Primary URL = the link as written, Link Cloaking off, the site's other link
defaults; the post's front end shows the link with the product's `rel` and `target`; the post's content is unchanged.
A link whose product was deleted for good comes back as a product on the next daily run.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `AffiliateDetector::create_products( 100 )` (lock `affibloom_auto_detect_lock`, setting re-read per row) | `AutoDetectCron` |
| `Cron\AutoDetectCron` (Module: `affibloom_auto_detect` single, `affibloom_auto_detect_daily` scheduled while the setting is on, cleared when off) | WP-Cron |
| `ProductService::create_from_url( $input, bool $cloaking_off = false )` option | `AffiliateDetector` |
| `ProductResolver` lookup `url_hash` → `affibloom_detected_links.product_id` (after the stored-URL lookups) | F21's indexer and render filter |
| `DetectedLinkRepository::without_product( 100 )` | `create_products` |

**Files:** create the cron module; modify `AffiliateDetector`, `ProductService`, `ProductResolver`, `src/Plugin.php`;
tests `tests/Unit/Application/AffiliateDetectorCreateTest.php`, `tests/Integration/AutoDetectCronTest.php`,
`tests/E2E/acceptance/C2.spec.ts` (AC1, AC2, AC8, AC9; AC1's title check follows N1).

- [ ] **Step 1: Failing tests.** E2E AC1 (`wp cron event run affibloom_auto_detect`, then one product with the URL as
  written, cloaking 0, the F18 D3 name — HTTP stubbed), AC2 (second save and run: one product), AC8 (delete the product
  for good, run the daily event: a new one), AC9 (front end: the product's `rel`; md5 unchanged). Red: no event.
- [ ] **Step 2: The cloaking option on `create_from_url`** (unit: a new product stores `link_cloaking` false; an existing
  one is untouched).
- [ ] **Step 3: `create_products`, the cron module, the lock.**
- [ ] **Step 4: The resolver lookup.**
- [ ] **Step 5: Run and look.** On this copy after migration: the run creates nothing (16 tied); a test post with a new
  `*.sjv.io` link: one product, Dashboard row, front end attributes; switch Auto-Detect off: both events cleared.
- [ ] **Step 6: Commit** — "Turn detected affiliate links into products".

---

### Task AM1: The Amazon import — WP-Cron `affibloom_amazon_import`

**Feature:** F35 (R24, R25; D11, D12, D13 product side, D23); F35 AC1, AC2, AC8

**Why:** with Auto-Monetization on, Lasso makes a product of every Amazon link in the site's posts; those products are
what put the site's tag on readers' Amazon links (R26), at render time in Affibloom.

**Depends on:** F21 35A-35C, 35F, 35H; Tasks 12A-12E (`create_from_url` for Amazon, the tag rule); F1 retired ASINs;
F17 10F (`auto_monetize_amazon`, built).

**User action:** the site owner has Auto-Monetization on; within five minutes of publishing a post with a new Amazon
product link, the Dashboard lists an Amazon product for that ASIN and store (starting name, no data while C1 is
dormant), and the post's front end prints the product's link with the site's tag rule; the post is unchanged. A
retired ASIN is not imported. With the setting off, nothing runs.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| Application `Monetize\AmazonImport::run( 100 )` (lock, batch counters, setting re-read per link, retired skip) | `AmazonImportCron` |
| `LinkLocationRepository::untied_amazon( 100 )` (F21's table: Amazon host, not tied, text and image, the AAWP/ACF rows F21 indexes) | `AmazonImport` |
| `Cron\AmazonImportCron` (Module: `affibloom_amazon_import`, every 5 minutes while on) and `Monetize\AmazonImportSchedule` (start, stop) | WP-Cron; AM3 |
| `WordPress\OptionLock` | `AmazonImport`, C2C (shared on its second use: extract here) |

**Files:** create the above; modify `src/Plugin.php`, F21's `LinkLocationRepository` and its table class; tests
`tests/Unit/Application/AmazonImportTest.php`, `tests/Integration/AmazonImportCronTest.php`,
`tests/E2E/acceptance/F35.spec.ts` (AC1, AC2, AC8).

- [ ] **Step 1: Failing tests.** E2E AC1 (a post with `amazon.com/dp/B0F35AC1XX?tag=other-20`; run the event; one
  Amazon product; run again: one; a retired ASIN: none; md5 unchanged), AC2 (front end prints the product's link with
  `vbvault-20` unless the tag is whitelisted; `rel`, `target`), AC8 (setting off: the event is not scheduled and a manual
  run does nothing). Red: no event.
- [ ] **Step 2: The queue query, the service, the lock.**
- [ ] **Step 3: The cron module and schedule.**
- [ ] **Step 4: Run and look.** On staging (F35 N3): the first runs create about 91 products; list them; Dashboard
  count; three posts' front ends.
- [ ] **Step 5: Commit** — "Make products of the Amazon links in posts".

---

### Task AM2: Amazon short links — the import's short-link branch and the map

**Feature:** F35 (R28; D14, D20); F35 AC3

**Why:** 218 `amzn.to` rows here are untied; Lasso rewrote them in the post to the full tagged link. Affibloom resolves
them once, ties them to a product and lets the render filter print the full link.

**Depends on:** AM1; F2 §5's resolution rule (`PageFetcher` stops before Amazon, 11D); F21 35H and its resolver.

**User action:** a post with `https://amzn.to/xyz` — after the next import run the Dashboard has (or reuses) the product
of its destination and the front end prints that product's full Amazon link; the post still says `amzn.to/xyz`. A short
link that cannot be resolved is tried three times, then left as it is.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Tables\AmazonShortLinksTable` (migrator +1), Domain `Monetize\ShortLinkRepository` | `AmazonShortLinks` |
| Application `Monetize\AmazonShortLinks::resolve( url )` (map, else `PageFetcher` first Location into an Amazon store, ≤ 3 tries) | `AmazonImport` |
| `ProductResolver` lookup `short_url` → `product_id` | F21 |

**Files:** create the above; modify `AmazonImport`, `ProductResolver`; tests
`tests/Unit/Application/AmazonShortLinksTest.php`, `tests/Integration/AmazonShortLinksTableTest.php`,
`tests/E2E/acceptance/F35.spec.ts` (AC3, HTTP stubbed: `amzn.to` answers 301 to `amazon.com/dp/…`).

- [ ] **Step 1: Failing tests.** AC3 (map row, product, front end full link, md5 unchanged), three failed tries → no
  fourth request. Red: no table.
- [ ] **Step 2: Table, repository, resolver service.**
- [ ] **Step 3: The import branch and the resolver lookup.**
- [ ] **Step 4: Run and look.** Staging: the 218 rows tie within a few runs; count requests (one per distinct short link).
- [ ] **Step 5: Commit** — "Resolve Amazon short links once and tie them to their product".

---

### Task AM3: Switching Auto-Monetization on and off — Settings → Amazon

**Feature:** F35 (R21, R22, R23; D17, D18, D22, D23); F35 AC5, AC8 (stop)

**Why:** Lasso guards the switch (a tracking ID first, multiple IDs on, a backup first) and starts or stops the import
with it.

**Depends on:** F17 10F (the switch, the tracking-ID field and its format check), AM1 (`AmazonImportSchedule`).

**User action:** on Settings → Amazon the owner switches **Enable Amazon Auto-Monetization** on. No tracking ID: the
line under the switch reads "Tracking ID must be set to use Auto-Amazon." and the switch goes back off. With one:
**Allow Multiple Tracking IDs** turns on if it was off; the dialog "Enable Auto-Amazon" ("We recommend creating a
backup first. This will allow you to undo this action quickly and ensure everything runs exactly as you hoped.")
offers **Create a Backup First** (the switch goes back off, Multiple IDs back as it was, WordPress's backup guide opens
in a new tab) and **Enable**. Save Changes: on → the import starts at once; off → it stops. Changing the tracking ID
re-runs the check.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `assets/admin/amazon-auto-monetize.js` (R22's checks, the dialog; Aurora's dialog component) | the Amazon tab |
| `templates/admin/partials/amazon-enable-dialog.php`; `Messages` `amazon_auto_needs_tracking_id` | the Amazon tab |
| `SettingsService` after-save hook → `AmazonImportSchedule::start()` / `stop()` on a change of `auto_monetize_amazon` | `PUT /settings/amazon` |

**Files:** create the script and partial; modify `templates/admin/settings-amazon.php` (or the tab's template),
`src/Interface/Admin/AdminAssets.php`, `src/Application/Settings/SettingsService.php`, `src/Plugin.php`; tests
`tests/Unit/Application/SettingsServiceAmazonScheduleTest.php`, `tests/E2E/acceptance/F35.spec.ts` (AC5, AC8).

- [ ] **Step 1: Failing tests.** AC5's four paths with the keyboard and the mouse; AC8's stop (`wp cron event list`).
- [ ] **Step 2: The after-save hook.**
- [ ] **Step 3: Script, dialog, message.**
- [ ] **Step 4: Run and look** at 1280 and 375 px; dialog focus and Esc.
- [ ] **Step 5: Commit** — "Guard and schedule Amazon Auto-Monetization from its switch".

---

### Task AM4: The Amazon import notice — every Affibloom page

**Feature:** F35 (R29, R30, R31, R35; D19); F35 AC6

**Why:** Lasso tells the owner how far the import has got and lets them dismiss it until the numbers change.

**Depends on:** AM1 (status counters), F21 (links indexed), the admin header's notices area.

**User action:** while the import has work, every Affibloom page shows "Amazon import in progress", "N in Dashboard ·
~M unique Amazon products still queued", "Current batch: x / y" while a run is going, "P placements indexed" with the
info tip; it refreshes every 15 s; at completion it reads "Amazon import complete" / "N unique Amazon links in
Dashboard" and hides itself; **×** hides it for this user until the counts change.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| Domain `Monetize\AmazonImportStatus` (payload, `pending_<q>_<d>` / `complete_<d>` fingerprints; pure) | `AmazonImport::status()` |
| `AmazonImport::status()`, `dismiss( user )`; `WordPress\UserMetaDismissals` (`affibloom_amazon_import_dismissed`) | the controller, the notice |
| `Rest\AmazonImportController` (`GET /amazon-import`, `POST /amazon-import/dismiss`; `Capabilities::PRODUCTS`) | the script |
| `Admin\AmazonImportNotice` (banner), partials `amazon-import-status.php`, `amazon-import-notice.php` | Affibloom admin pages |
| `assets/admin/amazon-import-status.js` (poll 15 s, redraw `[data-affibloom-amazon-status]`, stop rules, dismiss) | the pages |

**Files:** create the above; modify `src/Plugin.php`, `AdminAssets`, the header partial; tests
`tests/Unit/Domain/Monetize/AmazonImportStatusTest.php`, `tests/Integration/AmazonImportControllerTest.php`,
`tests/E2E/acceptance/F35.spec.ts` (AC6, a seeded queue).

- [ ] **Step 1: Failing tests.** AC6 (seeded untied rows; the texts; a run; completion; ×; counts change → back).
- [ ] **Step 2: Status domain and service.**
- [ ] **Step 3: Routes.**
- [ ] **Step 4: Notice, partials, script.**
- [ ] **Step 5: Run and look** at 1280 and 375 px; screen reader announces updates (`aria-live="polite"`).
- [ ] **Step 6: Commit** — "Show the Amazon import's progress".

---

### Task AM5: The import status card, the Dashboard notice and the General stats

**Feature:** F35 (R32, R33, R34; D19, D21); F35 AC7

**Why:** Lasso keeps the status visible on the Amazon tab and explains index versus Dashboard counts in the first day.

**Depends on:** AM4 (status, partial, script); F21 35G (the Link Index card); F19 (the Dashboard).

**User action:** Settings → Amazon shows "Amazon import status" under the switch while it is on; in the first 24 hours
after activation, when placements outnumber Dashboard links and the import is idle or complete, the Dashboard shows the
status as a dismissible notice and Settings → General's Link Index card adds "Amazon import" / "Complete" and "Link
Index vs Dashboard" ("%1$s indexed placements · %2$s Dashboard links") with Lasso's explainer.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `AmazonImportStatus::shows_context( activated_at, now )` | the three surfaces |
| Option `affibloom_activated_at` (set on activation if absent) | `AmazonImportStatus` |
| partials `amazon-import-card.php`, `amazon-import-general-stats.php`; the Dashboard notice in `AmazonImportNotice` | Settings tabs, Dashboard |

**Files:** create the partials; modify the Amazon and General tab templates, `DashboardScreen`, `AmazonImportNotice`,
`amazon-import-status.js`; tests unit `AmazonImportStatusContextTest.php`, E2E F35 AC7.

- [ ] **Step 1: Failing tests.** AC7 (each surface shown and hidden by its rule; dismiss on the Dashboard).
- [ ] **Step 2: The rule.**
- [ ] **Step 3: The three surfaces.**
- [ ] **Step 4: Run and look** (1280/375).
- [ ] **Step 5: Commit** — "Show the Amazon import on Settings and the Dashboard".

---

### Task AM6: Tracking IDs found in posts join the whitelist — `save_post` through F21

**Feature:** F35 (R27, R24-R25's whitelist step; D16); F35 AC4

**Why:** with Multiple Tracking IDs on, Lasso keeps a partner's tag it finds in a post by whitelisting it, so the
site's tag rule does not overwrite it.

**Depends on:** F21 35A-35C (the indexer), F17 (whitelist, blacklist), AM1 (the import's call).

**User action:** an editor publishes a post with `amazon.com/dp/B0…?tag=partner-21`; with Multiple IDs on, Force changes
off and `partner-21` never removed by hand, Settings → Amazon's whitelist now lists `partner-21` and the front end keeps
that tag; otherwise nothing changes.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `TrackingIds::learn( tag, settings ): ?array` (pure, R27's conditions; no blacklist copy) | the hook, `AmazonImport` |
| `Hooks\TrackingIdLearning` (listens to `affibloom_link_index_indexed`; Amazon product links with a tag) | WordPress |

**Files:** modify `src/Domain/Amazon/TrackingIds.php`, `AmazonImport`, `src/Plugin.php`; create the hook; tests
`tests/Unit/Domain/Amazon/TrackingIdsLearnTest.php`, E2E F35 AC4.

- [ ] **Step 1: Failing tests.** AC4's five conditions.
- [ ] **Step 2: The rule.**
- [ ] **Step 3: The hook and the
  import's call.**
- [ ] **Step 4: Run and look** (this copy: no new ID; every tag is `vbvault-20`).
- [ ] **Step 5: Commit** — "Whitelist partner tracking IDs found in posts".

---

### Task T1: The Tables list, and `GET /tables`

**Feature:** F25 (R4-R9, R11, R55 data, R58; D1, D3, D13, D14, D16, D19; AC1)

**Why:** the list is where tables are found; its route also feeds the editor's "Choose a Table" (F4).

**Depends on:** F21 Tasks 35A-35C (the location counts); the header partial (13A).

**User action:** the header's **Tables** tab (before Groups) opens "Tables", **Create Table** ("Coming soon" until T2),
"Search All <n> Tables". The list: Name, Products ("No products in this table yet." when none), Theme, Style
(Horizontal / Vertical), Locations (the count), Clone ("Coming soon" until T11), ten a page, oldest first, "x-y of n".
Enter in the search redraws in place and keeps `search` and `paged` in the address; no match or no tables: "Create
Beautiful Comparison Tables" and its text.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `TablePostType` (Module) | `Plugin` |
| `Domain\Table\{Table, TableStyle, TableTheme, TableProduct, FieldGroup, TablePage, TableRepository, TableLocations}` | the service |
| `PostTypeTableRepository::page()`, `get()` | `TableService` |
| `Application\Tables\TableService::list()` | `TablesScreen`, `TablesController` |
| F21 index adapter `TableLocations::counts()` | `TableService` |
| `TablesScreen`, submenu `affibloom-tables`, the Tables tab in `header.php` | WordPress |
| `GET /tables` in `TablesController` (items, html, url; `per_page` 10 or 5; `order`) | `tables.js`, later F4's picker |
| `templates/admin/tables.php`, partials `tables-list.php`, `table-row.php` | the screen, the route |
| `assets/admin/tables.js` (search on Enter, pager in place) | the list |

**Files:** create the above, `tests/E2E/acceptance/F25.spec.ts` (AC1), `tests/Unit/Domain/Table/TableTest.php` (shape),
`tests/Unit/Application/TableServiceTest.php` (search arms); modify `src/Plugin.php`, `AdminMenu`, `AdminAssets`,
`templates/admin/partials/header.php`, `assets/admin/admin.css`, `docs/ui/design-system.md` (Tables list row).

- [ ] **Step 1: Failing tests.** AC1 with three tables made by a WP-CLI fixture (a product name search; the cleaning
  of R8; the empty state); unit tests for the search arms. Expected red: no Tables tab.
- [ ] **Step 2: Domain, repository, service, post type.**
- [ ] **Step 3: Screen, route, templates, script.**
- [ ] **Step 4: Run and look.** Checks; F25 AC1 and F19's header specs; by hand at 1280 and 375 px.
- [ ] **Step 5: Commit** — "Add Lasso's Tables list".

---

### Task T2: Create a table

**Feature:** F25 (R12; D1, D4; AC2)

**Why:** a table must exist before it can be built; Lasso makes it from one dialog.

**Depends on:** T1.

**User action:** **Create Table** (list, builder header, or `page=affibloom-tables&create=1`, which then drops the
parameter) opens the dialog: Table Name, the Vertical (ticked) and Horizontal tiles, **Create Table**. No name: 'Please
fill out "Table Name" field'. A good name shows the spinner and opens the new table's builder (until T3: the list with
"Table created.").

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `POST /tables` (`TableInvalid` → 422 `title` "Please fill out "Table Name" field") | `tables.js` |
| `TableService::create()` (Cactus, headers on, field names on, revision 1) | the route |
| `PostTypeTableRepository::save()` | the service |
| partial `table-create-modal.php` (Aurora dialog, the two tiles as radio cards) | `TablesScreen` (and T3's screen) |

**Files:** the above; F25 spec AC2; unit tests for create.

- [ ] **Step 1: Failing tests** (AC2: empty name refused, Vertical default, Horizontal chosen, `create=1`).
- [ ] **Step 2: Service and route.**
- [ ] **Step 3: The dialog.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Create a comparison table from Lasso's dialog".

---

### Task T3: The builder page and the table's settings

**Feature:** F25 (R13-R16, R34 layout shell; D4, D5, D16; AC3)

**Why:** the builder is where every later action happens; its settings save as Lasso's do, at once.

**Depends on:** T2.

**User action:** a table opens at `page=affibloom-table&id=N`: heading = Name (follows typing), Create Table, Copy
Shortcode (F31 18S13; "Coming soon" until then), Saved/Saving, **All Tables `<n>`**; Name, Options (**Show Field
Name**; **Show Headers**, Horizontal only), Style, Theme; Add Product, Add Group, Add Row/Add Column, Preview Table
(each "Coming soon" until its task); the empty state "Add a Product or Group to get started."; **Delete This Table**
("Coming soon" until T11). Leaving Name saves it (blank: not saved); Style, Theme, toggles save at once and redraw.
Without `id`: placeholder "Untitled Table" (or "Untitled Table k"); naming it creates the table and puts `id` in the
address. A table changed in another window: "This table was changed in another window. Reload to see the latest
version."

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `TableScreen` (hidden submenu `affibloom-table`) | WordPress |
| `PATCH /tables/{id}` (revision, `TableChanged` → 409) | `table-builder.js` |
| `TableService::get(), update()`; `Domain\Table\UntitledName` | the screen, the route |
| `templates/admin/table.php`, partial `table-builder.php` (both layouts' shells, theme class) | the screen, every write's `html` |
| `assets/admin/table-builder.js` (one request at a time, Saved/Saving, redraw from `html`) | the page |

**Files:** the above; F25 spec AC3; unit tests for `UntitledName`, update and the revision refusal; `admin.css`,
design-system row (builder band and settings row).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Service, route, revision.**
- [ ] **Step 3: Page and script.**
- [ ] **Step 4: Run and look** (both styles, every theme name, 375 px).
- [ ] **Step 5: Commit** — "Open a table in
  Lasso's builder and save its settings as you go".

---

### Task T4: Products in a table — add, starting layout, badge, remove

**Feature:** F25 (R17-R23, R34; D6, D7; AC4, AC5)

**Why:** a table compares products; Lasso's first product sets the table's layout.

**Depends on:** T3; F26's built-in table fields; F1's `ProductRepository`.

**User action:** **Add Product** → "Add Product to Table" ("Search <n> URLs", Enter, six a page, **Add Product**;
nothing found: "Looks like we're coming up empty, partner."). The first product brings Image | Product name, Primary
Rating, Price | Primary Button, filled from the product; later products the same fields, their own values; twice:
"This product already exists, please select another product". The header cell: **Badge** (saved on leaving), grip,
**Remove this product** ('Remove "<name>"?', "If removed, you won't be able to get its back.", Cancel / OK).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `POST /tables/{id}/products` (product), `PATCH …/products/{p}` (badge), `DELETE …/products/{p}` | `table-builder.js` |
| `Table::add_product(), remove_product(), set_badge()`; `Domain\Table\StartingLayout` | `TableService` |
| `Application\Tables\TableValues` (R22, D7) | `TableService` |
| partials `table-product-picker.php` (the product search, F23 D10's `GET /products?search=`, six a page) and the header cell in `table-builder.php` | the builder |

**Files:** the above; F25 spec AC4-AC5; unit tests (starting layout, later products, duplicates, initial values,
remove).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Domain and values.**
- [ ] **Step 3: Routes.**
- [ ] **Step 4: Picker and header cell.**
- [ ] **Step 5: Run and look.**
- [ ] **Step 6: Commit** — "Add and remove a
  table's products, with Lasso's starting layout".

---

### Task T5: Add a group to a table

**Feature:** F25 (R24; D6; AC6)

**Why:** Lasso fills a table from a group in one step.

**Depends on:** T4; F23 G1-G4 (`GroupService::members()`, the group search).

**User action:** **Add Group** → "Add group to Table" ("Search <n> Groups", rows name and "<n> link(s)", six a page,
**Add Group**): the group's published products join the table, last, in the group's order, each once.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `group` in `POST /tables/{id}/products` | `table-builder.js` |
| `TableService::add_group()` (skips members already in) | the route |
| partial `table-group-picker.php` (`GET /groups?search=&per_page=6`) | the builder |

**Files:** the above; F25 spec AC6; unit test for the skip.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Service and route.**
- [ ] **Step 3: Picker.**
- [ ] **Step 4: Run and
  look.**
- [ ] **Step 5: Commit** — "Add a group's products to a table".

---

### Task T6: Rows and columns, the Add Field dialog, plain cells

**Feature:** F25 (R2, R3, R25-R31, R33; D7, D11; AC7-AC9)

**Why:** a table's columns (or rows) are fields; most cells are plain values.

**Depends on:** T4; F26 (`POST /fields`, the library route with `exclude[]`).

**User action:** **Add Row / Add Column** adds an empty one ("Click to add a Field"; refusals R25). **+** opens "Add
Field": **Manage Fields**, "Create new" (Field Title, Field Type, Description, **Create Field**) and "Add from library"
(fields not in the table, six a page, "Find a field by searching here"); a pick fills the row/column for every
product. Text, product-name, copy, number, rating (1-5, one decimal, stars), long text and list cells save 500 ms after a
change. **×** asks 'Remove "<field>"?' ("No data is deleted and the Field can always be added back.", Remove); a
Vertical row's **Remove same field →** removes the whole row.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `POST /tables/{id}/groups`, `DELETE …/groups/{g}`, `POST …/groups/{g}/fields`, `DELETE …/groups/{g}/fields/{f}`, `PUT …/values/{p}/{f}` | `table-builder.js` |
| `Table::add_group(), remove_group(), add_field(), remove_field(), set_value()` | `TableService` |
| partials `table-field-modal.php` (reuses 14C's field form), `table-cell.php` (plain editors) | the builder |

**Files:** the above; F25 spec AC7-AC9; unit tests for every refusal and D7.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Domain.**
- [ ] **Step 3: Routes.**
- [ ] **Step 4: Dialog and
  cells.**
- [ ] **Step 5: Run and look.**
- [ ] **Step 6: Commit** — "Add rows, columns and fields to a table".

---

### Task T7: Image, price and description cells

**Feature:** F25 (R29 image/price/description, R32; D10, D11; AC10)

**Why:** these cells need the media library and rich text.

**Depends on:** T6; F1's editor settings (extracted here: second use).

**User action:** an image cell shows "Update Image" on hover and opens the media library ("Select an Image", "Select
Image"), saving the URL at once. A price cell: Amazon → the live price, read only ("Amazon prices are updated
automatically"); else a one-line rich editor (bold, italic, underline, strike, colour; "N/A"). A description cell's
edit icon opens "Description Editor" (Manage Fields, rich text, Cancel / Save).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Admin\RichEditorSettings` (price, description; extracted from the product page) | the product page, the builder |
| partial `table-description-modal.php`; image and price editors in `table-cell.php` | the builder |

**Files:** the above; `ProductScreen` / `templates/admin/product.php` use the extracted settings; F25 spec AC10; F1
AC for the product page's editors rerun.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Extract the settings.**
- [ ] **Step 3: Cells and modal.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Edit a table's images, prices and descriptions".

---

### Task T8: Button information

**Feature:** F25 (R29 buttons, R35; D11; AC11)

**Why:** a table's buttons carry their own text, and the Secondary its own URL.

**Depends on:** T6.

**User action:** a button cell's edit icon opens 'Edit "<field>"': Button Text, Button URL (Primary: the product's
link, read only; Secondary: editable); missing text or URL → Lasso's sentence; Save redraws the cell. A Secondary
without URL shows "No Secondary Button URL set.".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| button values in `PUT /tables/{id}/values/{p}/{f}` (`{button_text, url}`; the URL ignored for Primary) | the modal |
| partial `table-button-modal.php` | the builder |

**Files:** the above; F25 spec AC11; unit test for the Primary rule.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Value rule.**
- [ ] **Step 3: Modal.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Edit a table button's text and URL".

---

### Task T9: Reordering

**Feature:** F25 (R36-R39; D9; AC12)

**Why:** order is a table's meaning; Lasso drags products, rows/columns and fields.

**Depends on:** T6.

**User action:** drag a product's header cell, a row/column, or a field (within its cell, or to another row/column),
or use the Move buttons; the order is saved and holds after a reload; a field moved where it already is: "This field
already exists, please select another field".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `PUT /tables/{id}/order`, `POST …/groups/{g}/fields/{f}/move` | `table-builder.js` |
| `Table::order_products(), order_groups(), order_cell(), move_field()` | `TableService` |
| drag (pointer events) and Move buttons in `table-builder.js` | the builder |

**Files:** the above; F25 spec AC12; unit tests for each order and the move refusal.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Domain and routes.**
- [ ] **Step 3: Drag and buttons (both
  layouts).**
- [ ] **Step 4: Run and look** (mouse and keyboard only).
- [ ] **Step 5: Commit** — "Reorder a table's
  products, rows and fields".

---

### Task T10: Table Locations

**Feature:** F25 (R40; D13; AC13)

**Why:** an editor needs to see where a table is used before changing or deleting it.

**Depends on:** T1; F21 Tasks 35A-35C.

**User action:** a Locations count above 0 opens "Table Locations": Content ("This is the post or page this table was
found in.") and Action; title (its editor, new tab), address, view icon; six a page; the page is remembered per table.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `GET /tables/{id}/locations` | `tables.js` |
| `TableService::locations()`; `TableLocations::list()` | the route |
| partial `table-locations-modal.php` | `TablesScreen` |

**Files:** the above; F25 spec AC13 (an index row seeded for the draft).

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Route and adapter.**
- [ ] **Step 3: Dialog.**
- [ ] **Step 4: Run and
  look.**
- [ ] **Step 5: Commit** — "Show where a table is used".

---

### Task T11: Clone and delete

**Feature:** F25 (R10, R41; D8, D12; AC14, AC15)

**Why:** Lasso copies a table from the list and deletes one only when nothing uses it.

**Depends on:** T3, T10.

**User action:** Clone asks "Do you want to clone <name>" → "<name> - Copy" with everything, the list's last page.
**Delete This Table**: unused → 'Delete "<name>"' ("If deleted, you won't be able to get its back.", Cancel / OK) → the
Tables list; used → "Hold Up" (Got It), nothing deleted; a direct request is refused the same way.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `POST /tables/{id}/clone`, `DELETE /tables/{id}` (`TableInUse` → 409) | `tables.js`, `table-builder.js` |
| `TableService::clone(), delete()` | the routes |
| the dialogs (shared `delete-modal.php`, a "Hold Up" panel) | both screens |

**Files:** the above; F25 spec AC14-AC15; unit tests (clone copies settings; in-use refusal).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Service and routes.**
- [ ] **Step 3: Dialogs.**
- [ ] **Step 4: Run
  and look.**
- [ ] **Step 5: Commit** — "Clone a table and delete one only when unused".

---

### Task T12: The reader's Horizontal table, by shortcode

**Feature:** F25 (R43, R44, R46, R48-R52; D17; AC16). Replaces F31's 18S8.

**Why:** readers see tables; the site's three tables are Horizontal.

**Depends on:** T6-T8; Phase 3 Task 18A (renderers, `BoxAssets`); F31 18S1; F16 (`show_disclosure_table`); N2's
baselines.

**User action:** `[affibloom type="table" id="N"]` draws Lasso's wrapper, the header row of field names (Show Headers),
a row per product, the badge in the first cell, every field as R48, Lasso's link attributes; `theme` overrides,
`data_nosnippet="enable"` adds `data-nosnippet`; a missing table or one without products draws nothing; the disclosure
when customised settings and `show_disclosure_table` are on; the brag icon when enabled.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Front\TableDisplay::render()`; the `table` branch in `AffibloomShortcode` | the shortcode |
| `Domain\Table\TableView` (horizontal) | `TableDisplay` |
| `templates/front/table.php`, `table-horizontal.php`, `partials/table-field.php` | `TableDisplay` |
| `assets/front/table.css` (Lasso's `lasso-table-frontend.min.css`) in `BoxAssets` | WordPress |

**Files:** the above; F25 spec AC16; `tests/E2E/visual/table-horizontal.spec.ts` (tables 2 and 3 at 1280); unit tests
for `TableView`.

- [ ] **Step 1: Failing tests** (markup of table 2 against Lasso's captured HTML; the visual check).
- [ ] **Step 2: View and display.**
- [ ] **Step 3: Templates and CSS.**
- [ ] **Step 4: Run and look** (every theme).
- [ ] **Step 5: Commit** — "Draw a Horizontal comparison table as Lasso does".

---

### Task 18S13: A table's Copy Shortcode

**Feature:** F31 (R33, D9; AC13)

**Why:** F25's table builder page shows "Coming soon" for Copy Shortcode until this task (F25 T3, F31 R33).

**Depends on:** F25 T3 (the table builder page) and T12 (the table shortcode), both earlier in this phase; 18S10 (Phase 3). (F31's own 18S8 is superseded by F25 T12.)

**User action:** once a table is saved, **Copy Shortcode** copies `[affibloom type="table" id="<table>"]`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `ShortcodeText::table()` | the table builder page (F25 T3) |
| the Copy Shortcode control on the table screen | `affibloomCopy()` |

**Files:** the table screen's template and script; F31 **AC13**.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Control.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Copy a table's shortcode".

---

### Task T13: The reader's Vertical table

**Feature:** F25 (R45, R48; AC17)

**Why:** Lasso's other layout: a column per product.

**Depends on:** T12.

**User action:** a Vertical table: badge row first, a row per field, widths 100/n % up to four products, a scrolling
row past four.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `TableView` (vertical), `templates/front/table-vertical.php` | `TableDisplay` |

**Files:** the above; F25 spec AC17; `table-vertical.spec.ts` (N2).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: View.**
- [ ] **Step 3: Template.**
- [ ] **Step 4: Run and look** (2,
  4 and 5 products).
- [ ] **Step 5: Commit** — "Draw a Vertical comparison table".

---

### Task T14: Phone cards and breakpoints

**Feature:** F25 (R47, R53; AC18)

**Why:** on a phone Lasso shows one card per product.

**Depends on:** T12, T13.

**User action:** at 767 px and below, a card per product (badge, then every field); the desktop table hidden; at
1000 px a Vertical table hides its fourth product.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `templates/front/table-mobile.php`; the cards' items in `TableView` | both layouts |

**Files:** the above; F25 spec AC18; visual checks at 768 and 390 px.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Cards.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** —
  "Show a comparison table as cards on phones".

---

### Task T15: Preview Table

**Feature:** F25 (R54; D18; AC19)

**Why:** Lasso lets the builder show the reader's table without leaving the screen.

**Depends on:** T3, T12-T14.

**User action:** **Preview Table** swaps the builder for the reader's table, the button reads **Edit Table**, the Add
buttons hide; style, theme and toggle changes redraw the preview; **Edit Table** returns.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `GET /tables/{id}/preview` (`TableDisplay`) | `table-builder.js` |
| the front table CSS on the builder page (`AdminAssets`) | the page |

**Files:** the above; F25 spec AC19.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Route.**
- [ ] **Step 3: Toggle.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Preview a table from its builder".

---

### Task T16: The Dashboard's Comparison table filter

**Feature:** F25, F19 (R59; AC20)

**Why:** the filters panel's last placeholder that needs Tables.

**Depends on:** T4; F19 13E.

**User action:** **Comparison table** (Any, In a comparison table, Not in any table) narrows the list in place, counts
as a filter and is kept in saved views.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `comparison_table` in the list route, `DashboardUrl`, `SavedViews`, `PostTypeProductRepository::page()` | `dashboard.js` |
| `TableRepository::product_ids()` (every product in any table) | the product repository's condition |

**Files:** the above; `filters-panel.php`, `saved-views.php`; F25 spec AC20; F19 filter specs rerun.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Condition and route.**
- [ ] **Step 3: Panel.**
- [ ] **Step 4: Run
  and look.**
- [ ] **Step 5: Commit** — "Filter the Dashboard by comparison tables".

---

### Task T17: A deleted product leaves its tables

**Feature:** F25, F1 (R60; D15; AC21)

**Why:** Lasso removes a deleted product from its tables (F1 D43); Affibloom does it without orphans.

**Depends on:** T4; F1 17A.

**User action:** deleting a product for good removes it, and its values, from every table; the tables still draw.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `TableService::remove_product_everywhere()` | `ProductService::delete()` (17A) |

**Files:** the above; F25 spec AC21; F1's delete specs rerun.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Service and call.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4:
  Commit** — "Remove a deleted product from its tables".

---

### Task 23F: Table — "Choose a Table" (moved here from Phase 4)

**Feature:** F4 (R24; AC8)

**Why:** comparison tables are one of the seven display types.

**Depends on:** 23C; F25 T1 (`GET /tables`), T2 (create), T3 (the Tables screen, `create=1`), T12 (the table renderer).
**These are in Phase 7: see Cross-phase notes.** Until they exist, the Table tile is "Coming soon", this task's test is
written and skipped, and the task is closed in Phase 7.

**User action:** Table opens "Choose a Table": five tables a page, "Search tables", **Create Table** (the Tables screen
with `create=1`, in a new tab), **+ Add** inserts `type=table` for that table.

**Request path:**
```
display-modal.js (step "tables") → GET /affibloom/v1/tables?search=&page=&per_page=5  INSERT_BOXES (F25 T1)
  → setAttributes( { type: 'table', tableId } ) → POST /editor/preview
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the tables step in `display-modal.js` | the chooser |
| `tableId` in `DisplayRequest::from_attributes()` | preview and render callback |

**Files:** modify `assets/editor/display-modal.{js,css}`, `src/Domain/Display/DisplayRequest.php`;
tests `tests/E2E/acceptance/F4.spec.ts` (AC8, skipped until F25 T1/T12).

- [ ] **Step 1: Failing test** (AC8).
- [ ] **Step 2: Tables step.** Enable the tile when `GET /tables` exists.
- [ ] **Step 3: Run and look.** Checks; by hand at 1280 and 375 px.
- [ ] **Step 4: Commit** — "Insert a comparison table from the block".

---

Also builds 24D's table half (Edit Display for a table box), which Phase 4 left for this task.

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

### Task 27E: `[lasso type="table"]`

> Moved here from Phase 5 on 2026-11-14; runs after F25 T12 and Task 26I. It also builds the table branch of 27X1 (`[aawp table]`) and of 28D (converting `type="table"` boxes, if the conversion is kept).

**Feature:** F31 (A1 table, A50; AC19)

**Why:** the saved `[lasso type="table"]` shortcodes draw the migrated comparison table (the draft 9492 uses one).

**Depends on:** 27A, 26I (the tables migration) and F25 T12 (the table renderer), both in Phase 7.

**User action:** `[lasso type="table" id="2"]` (the draft 9492) draws the migrated table; `table_id` wins over `id`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LassoIdMap::table_for()`; the table row in `LegacyAddress` | `LassoShortcode` |

**Files:** F31 **AC19**. Create the classes named above; modify `src/Plugin.php` where a Module is registered.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Mapping.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Render [lasso] comparison tables".

---

### Task IM11: AAWP tables

> Moved here from Phase 5 on 2026-11-14; runs after F25's tables.

**Feature:** F33 (R51; AC18)

**Why:** AAWP's tables must become comparison tables (R51).

**Depends on:** IM9; F25 (tables), F26 (`FieldsService`); F31 27X1's table branch.

**User action:** each published `aawp_table` is a row (target `[amazon table="<id>"]`); Import makes a comparison table
(AAWP's title, column style, Cactus, no title, horizontal headers) of its active products with badges and AAWP's rows as
fields; `[amazon table]` draws it; Revert deletes the table.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Infrastructure\Import\AawpTableSource`, `Domain\Import\Source\AawpTableMap` | `ImportSources` |

**Files:** F33 **AC18**; `AawpTableMapTest`. Create the class(es) named above and register the source in `ImportSources` (`src/Plugin.php`).

- [ ] **Step 1: Failing tests.** AC18 (an `aawp_table`: a comparison table with badges and fields; `[amazon table]` draws it; revert deletes it) in `tests/E2E/acceptance/F33.spec.ts` and the integration or unit test named under Files, written first; expected red: the source is not offered on the Import screen.
- [ ] **Step 2: Implementation.** the class(es) in the table above, registered in `ImportSources`.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Import AAWP tables as comparison tables".

---

### Task IM20: WP All Import

> Moved here from Phase 5 on 2026-11-14; F11 31D (Phase 6) is done by now.

**Feature:** F33 (R57, D17; AC25)

**Why:** WP All Import files must fill Affibloom's products as they fill Lasso's links (R57).

**Depends on:** F26 (`FieldsService`); F11 D18 (the export columns).

**User action:** with WP All Import active, a CSV imported into Affibloom's products with `lasso_custom_redirect` and
`lasso_fields` sets the product's link (with its Amazon or store id) and its fields; a file from Export CSV comes back.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Integration\WpAllImportHook` (`pmxi_saved_post`) and the `lasso_fields` parser | WP All Import |

**Files:** Create the class named above; modify `src/Plugin.php`.

- [ ] **Step 1: Failing tests.** AC25 (skipped without WP All Import; a CSV with `lasso_custom_redirect` and `lasso_fields`; an Export CSV file comes back) in `tests/E2E/acceptance/F33.spec.ts` and the integration or unit test named under Files, written first; expected red: the control, notice or hook does not exist.
- [ ] **Step 2: Implementation.** the route, dialog or hook in the table above.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Fill products from WP All Import rows".

---

---

### Task IM18: See It — the locations of an imported product (moved here from Phase 5)

> **Waits for Phase 7** (F12 36B, the Locations query). See Cross-phase notes.

**Feature:** F33 (R37; AC8)

**Why:** an editor must see where an imported product is used before reverting it (R37).

**Depends on:** IM2; F12 36B (the Locations query).

**User action:** an imported row with locations shows **See It** → "Import Locations": Content (title to the editor,
address; tip "This is the post or page this table was found in."), Type (icon, "This is <type>."), six a page, the page
remembered per product.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `GET /import/items/{source}/{ref}/locations`; the dialog in `import-dialogs.php`; user meta `affibloom_import_locations_page` | `import.js` |

**Files:** F33 **AC8**. Modify `src/Interface/Rest/ImportController.php`, `templates/admin/partials/import-dialogs.php`, `assets/admin/import.js`.

- [ ] **Step 1: Failing tests.** AC8 (See It: "Import Locations", six a page, the page remembered per product) in `tests/E2E/acceptance/F33.spec.ts` and the integration or unit test named under Files, written first; expected red: the control, notice or hook does not exist.
- [ ] **Step 2: Implementation.** the route, dialog or hook in the table above.
- [ ] **Step 3: Run and look.** Run the user action above on Local (1280 and 375 px where something is drawn or shown) and see the result.
- [ ] **Step 4: Commit** — "Show where an imported product is used".

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

## Exit check

Phase 7 is done when every task above is ticked, `composer lint`, `composer analyse`, the unit tests and the E2E and parity suites that run on this machine pass, and the acceptance criteria its tasks own pass:

- **F12 (Locations):** AC2, AC3, AC20-AC26. **F12 (Clicks):** AC1, AC4-AC19 (AC11 runs in 13K; AC15 not built: decision E1).
- **C3:** AC1, AC2, AC4-AC10 (AC10 built, decision C6; AC3 replaced by "with no ID, no GA script").
- **F19:** AC21-AC23 (13K).
- **F22:** AC1-AC13.
- **C2:** AC1-AC9. **F35:** AC1-AC8.
- **F25:** AC1-AC21. **F31:** AC13 (18S13).
- **F10:** AC16 (26I), AC21 (26N), AC29, AC30 (29C), AC23 on every write task; **27E, IM11 and IM20** with their Phase 5 acceptance criteria.
- **C1:** AC6, AC10 (C1E), AC13 (C1H).
- **F21:** the Locations side of AC2, AC3 and the index-versus-inventory check: the published rows of the index match the Phase 1 inventory for all 34 guides (676 in-content links), and Clicks on a test page appear on the Dashboard.
- The user's sign-off for the phase, recorded in `progress.md`.

## Coverage

Every requirement or criterion of the phase's features that a task here owns. Items owned by other phases are named so no row is lost; **UNOWNED** marks a row without a task.

| R / AC | Task |
|---|---|
| F12 L1-L3, AC20 | 36A (sort part: 13K) |
| F12 L4-L9, L12, AC2, AC21, AC26 | 36B |
| F12 L10, L11, AC22, AC23 | 36C (preview, Clicks stub), 34C (Clicks) |
| F12 L13, AC24 | 36D |
| F12 L14-L16, AC3, AC25 | 36E |
| F21 R1-R6, R10-R25, R27-R33, R36-R61 (the index and its attributes), AC1-AC10 | Phase 5, Tasks 35A-35J (moved); R7, R9, R35, R50 not built; R34: 35E records whether a filter exists |
| F21 R59 | owners named: F12, F22, F25, C1 |
| F12 Clicks R1-R4 (cloud storage) | not built; replaced by 33A; no figures imported: decision E1 |
| F12 R5-R8 | 33A |
| F12 R9 | 33B |
| F12 R10 | 34A |
| F12 R11 | 34C |
| F12 R12 | 34B (sum); the CSV and click column: O9 and O3's Min link clicks filter (check at gate 4) |
| F12 R13 | nothing to build |
| F12 R14 | 33D |
| F12 R15-R20 | not built |
| F12 AC1, AC4, AC5, AC14, AC16-AC19 | 33A |
| F12 AC6, AC7 | 33B |
| F12 AC8 | 33C |
| F12 AC9 | 33D |
| F12 AC10, AC11 | 34A (AC11: 13K) |
| F12 AC12 | 34B |
| F12 AC13 | 34C |
| F12 AC15 | not built: decision E1 (34D and 26J dropped) |
| C3 R1-R6, AC4-AC7 | C3A |
| C3 R7-R10, R12, AC1, AC2, AC8, AC9 | C3B |
| C3 R11, AC3, AC10 | C3C (built, decision C6) |
| C3 R13 | Phase 8, ON7 (wizard's GA step) |
| C3 R14 | not built |
| F19 R1-R11, AC21-AC23 | 13K |
| F19 R6 count arm / clicks arm | 13K with 36A / 34A |
| F19 R5's `opportunity_count` sort, tile, tab, lightbulb | O8 (tile, tab, lightbulb, the `opportunity_count` sort arm through 13K); R5's substitution notice: 13K (assigned 2026-11-14) |
| F22 R1-R16, AC1, AC2, AC13 | O1 |
| F22 R17-R23, AC3 | O2 |
| F22 R24-R28, R32, R33, AC4 | O3 |
| F22 R29-R31, AC12 | O4 |
| F22 R34-R39, AC5, AC6 | O5 |
| F22 R40 | IM20 (WP All Import saves; assigned 2026-11-14); Lasso's importer value: 26N |
| F22 R41-R43, AC7 | O6 |
| F22 R44-R48, AC8 | O7 |
| F22 R49, R73, R74-R80 | not built (absence checked by O1, AC13) |
| F22 R50-R56, AC9 | O8 |
| F22 R57-R63, AC10 | O9 |
| F22 R64-R72, AC11 | O10 |
| F35 R2-R9, R16 data; C2 AC6 | C2A |
| F35 R10-R12; C2 AC2 (recorded), AC3, AC4, AC5, AC7, AC8 (rebuild) | C2B |
| F35 R13-R15; C2 AC1, AC2, AC8 (daily), AC9 | C2C |
| F35 R24, R25; AC1, AC2, AC8 | AM1 |
| F35 R28; AC3 | AM2 |
| F35 R21-R23; AC5, AC8 (stop) | AM3 |
| F35 R29-R31, R35; AC6 | AM4 |
| F35 R32-R34; AC7 | AM5 |
| F35 R27; AC4 | AM6 |
| F35 R1 | F9 9B (control), C2B (read) |
| F35 R17, R18, R20, R37 | not built (Lasso cloud) |
| F35 R19 | C2A (the 45 merchant hosts are rows of the same registry; assigned 2026-11-14) |
| F35 R26 | Phase 5, 35H (with F21 D14 corrected) |
| F35 R36, R38 | F19, Task 27A, C1 notice; F22 manual monetize |
| F25 R1, R2, R3 | T1, T6 |
| F25 R4-R9, R11, R58, AC1 | T1 |
| F25 R12, AC2 | T2 |
| F25 R13-R16, AC3 | T3 |
| F25 R17-R23, AC4, AC5 | T4 |
| F25 R24, AC6 | T5 |
| F25 R25-R31, R33, AC7-AC9 | T6 |
| F25 R29 (image, price, description), R32, AC10 | T7 |
| F25 R29 (buttons), R35, AC11 | T8 |
| F25 R34 | T3, T4 |
| F25 R36-R39, AC12 | T9 |
| F25 R40, AC13 | T10 |
| F25 R10, R41, AC14, AC15 | T11 |
| F25 R42 | T3 and each route |
| F25 R43, R44, R46, R48-R52, AC16 | T12 |
| F25 R45, AC17 | T13 |
| F25 R47, R53, AC18 | T14 |
| F25 R54, AC19 | T15 |
| F25 R55 | T1 (data); the editor screens are F4's |
| F25 R56 | Phase 5, F21 35B |
| F25 R57 | IM11 (F33) |
| F25 R59, AC20 | T16 |
| F25 R60, AC21 | T17 |
| F25 R61 | F26 (N1) |
| F25 R62 | T1 (left out) |
| F31 R33, AC13 | 18S13 |
| F31 27E (`[lasso type="table"]`) | 27E |
| F10 R14, AC16 | 26I |
| F10 R11, AC17 | not built: decision E1 (26J dropped) |
| F10 R18, AC21 | 26N (and the settings keys of F21, F22, F35, C3) |
| F10 AC29, AC30 | 29C |
| F10 AC23 | every write task (26I, 26N) |
| C1 R37-R39, AC6, AC10 | C1E |
| C1 R55-R57, AC13 | C1H |
| F33 AAWP tables, WP All Import | IM11, IM20 |
| F9 R20-R21, AC13 (Link Index card) | Phase 5, 9J (moved) |
| F38 R40-R43, AC7; R25, R26, AC8 | Phase 5, ON3, ON4 (moved) |

**UNOWNED:** none (the four rows reported by the assembly were assigned on 2026-11-14: F19 R5's notice → 13K; F22 R40 → IM20; F35 R19 → C2A; "Used in N posts" on the issue panel → 36A).
