# Affibloom — Phase 8: Hardening and documentation — Implementation Plan

> **How to run this plan:** inline, in one working session, task by task, in the order below. Do not hand tasks to subagents. When a step is finished, change its `- [ ]` to `- [x]`.
>
> **Explain as you go:** before each task, say in two or three sentences what the task builds, why it is needed now, and how it will be checked. After the task, say what changed and what the verification showed. No vibe coding: every line written must be one you can explain.
>
> **Shared rules:** the architecture, working method, decisions, global constraints, file structure and API contract are in [`00-overview.md`](00-overview.md). Read it first.


## Status

Planned; gate 4 given by the user 2026-11-14 for every open task. The
phase runs after Phases 0-7 are signed off; Task 37A-37D review everything built before them.

## Scope and features

- **Reviews of every feature** (Tasks 37A-37D, 39A): `docs/features/*/` (F1-F12, F16-F19, F21-F31, F33-F35, F37, F38,
  C1-C3), their "Entry points" and "Data" sections in `analysis.md`.
- **F37 tools and diagnostics, the parts that wait for the rest** (Tasks TL3, TL4, TL6, TL7, and TL5 inside Task 38):
  `docs/features/F37/requirements.md` and `analysis.md`. (TL1, TL2 are in Phase 2.)
- **F38 admin shell, the parts that need other screens** (Tasks ON5-ON9): `docs/features/F38/requirements.md` and
  `analysis.md`. (ON1, ON2 are in Phase 2; ON3, ON4 in Phase 7; ON10 is dropped, see below.)
- **F9 Uninstall card** (inside Task 38) and **F9 Permissions** (inside TL6): `docs/features/F9/`.
- **Uninstall and export** (Task 38) and **documentation and release candidate** (Tasks 39, 39A): roadmap 8.5-8.7.

## Decisions that change this phase

Decided 2026-11-12; reasons in `docs/decisions/task-6-decisions.md` in the plugin repository. Apply them when the task is worked, and record any code that differs from
the text below as a deviation in `progress.md`.

- Task 37C (was Task 37): the accessibility review checks U3 (help text by keyboard and screen reader) on every screen.
- **ADR-8 (the user, 2026-11-12):** the UX review (roadmap 8.7, Task 39A) compares admin screens with Lasso's screenshots only for
  fields, labels, help text and workflow steps, and with the Affibloom design system (`docs/ui/design-system.md`) for
  looks. Task 37C also checks the admin colour contrast against the design system's stated ratios (the admin palette is
  ours; the box's colours remain the client's). Task 39's admin guide shows Affibloom's screens and says where the
  familiar Lasso features moved, if anywhere.
- **Simple workflow, no CI (the user, 2026-11-12; `progress.md` deviation 10):** no job matrix, no GitHub Actions. The
  reviews use the runs this machine has: Local's PHP 8.3 (switched in Task 2X0), the machine's PHP 8.5 CLI (unit suite), and a smoke test on
  staging (PHP 8.3). The integration suite needs wp-env, which this machine lacks (`environment.md`).
- **WP-CLI (deviation 7):** every WP-CLI command in this phase is `bash tools/local-wp.sh …` from the plugin folder.
- **Duplicates settled 2026-11-14 (coordinator):** the command palette is ON9 (F19 13L dropped); the Dashboard tour is
  ON8 (F19 13M dropped); the Settings unsaved-changes guard is F9 9E (F38 **ON10 dropped**); Export CSV of issue tabs
  is F11 31D, and this phase's export (Task 38) is the whole-library product export.
- **Lasso has no uninstall routine** (`affiliate-plugin.php` 75-76: deleting Lasso removes nothing). The opt-in removal in
  Task 38 is Affibloom's own (F37 correction N1).
- **Decided (the user accepted the recommendation, 2026-11-14), A8:** "Uninstall Lasso" is not built; the Uninstall card holds Task 38's opt-in and the list of posts using Affibloom (F37 N1; F9 N2).
- **Decided (the user, 2026-11-14), C3:** build Lasso's Permissions select (Task TL6), default Administrator, with the corrected mapping (Contributor `edit_posts`, Subscriber `read`; Lasso swaps them); `PRODUCTS` and `SETTINGS` follow it; only administrators change it; decision 1 becomes the default.
- **Decided (the user, 2026-11-14), B1:** Lasso cloud items (Stop Lasso Analytics, licence activation, updates, the flag service) are not built (F37 N3).
- **Decided (the user accepted the recommendation, 2026-11-14), B7:** `SUPPORT_SITES` fixes are not built (F37 N4).
- **Decided (the user accepted the recommendation, 2026-11-14), D13:** the wizard is built without the Activate step at `admin.php?page=affibloom-setup`; it opens once after Affibloom's first activation on a site with no Affibloom settings and no Lasso data to import (so never by itself on this site) (F38 N1).
- **Decided (the user, 2026-11-14), B1:** licence and account content (Activate step, Update Account, Account, Analytics and Marketplace entries, update-checker row links) is omitted (F38 N2).
- **Decided (the user, 2026-11-14), B2:** the Help menu, the Support item, "Lasso affiliate program" and the 29 "Learn" links are omitted; boxes keep their text; the tour's Help step drops (F38 N3).
- **Decided (the user, 2026-11-14), B3:** the review card is omitted (F38 N4).
- **Decided (the user, 2026-11-14), B1 and B10:** Intercom, Segment, PostHog and Sentry are omitted with no replacement; the tour's Chat step drops (F38 N5).
- **Decided (the user accepted the recommendation, 2026-11-14), D12:** WordPress's footer version is blanked on Affibloom's pages only (F38 N6).
- **Decided (the user, 2026-11-14), B4:** the wizard's Amazon step omits "Commission Upgrades" (F38 N7).

## Tasks in this phase

### Reviews of everything built (roadmap 8.1-8.4)

- [ ] Task 37A: Security review
- [ ] Task 37B: Performance review
- [ ] Task 37C: Accessibility review
- [ ] Task 37D: Compatibility review

### Uninstall and export (roadmap 8.5; F37 TL5, F9)

- [ ] Task 38: Uninstall opt-in, the Uninstall card and the product export

### F37 Tools and diagnostics

- [ ] Task TL3: Logs
- [ ] Task TL4: DB Status and self-repair
- [ ] Task TL6: Permissions
- [ ] Task TL7: Version in the admin footer

### F38 Admin shell

- [ ] Task ON5: Education boxes: close, remember, reset, hide with help tips
- [ ] Task ON6: Setup wizard: first visit, Theme, Customize
- [ ] Task ON7: Setup wizard: Amazon, Analytics, Imports, Done
- [ ] Task ON8: The Dashboard tour
- [ ] Task ON9: Command palette (Ctrl+Shift+L)

### Documentation and acceptance (roadmap 8.6, 8.7)

- [ ] Task 39: Documentation and release candidate 1.0.0-rc.1
- [ ] Task 39A: UX acceptance

## Cross-phase notes

**Earlier phases (what this phase needs, by name):**

- Task 37A-37D need every other phase finished: Phases 2-7 tasks (all screens, routes, shortcodes, cron jobs) and Task 9A's
  `docs/ui/design-system.md`, `docs/ui/screen-inventory.md`, `docs/ui/workflows.md`, `tests/fixtures/baselines/admin/`.
- TL3: `FileLogger` is called by the error paths of bulk add (F18, Phase 2), link checks (30A-30C, Phase 6), the index
  (35A-35J, Phase 7) and import (IM*, Phase 5); `LogPruner` runs on F11's `affibloom_daily` (30A, Phase 6).
- TL4: needs TL2's `Migrator` and `Table` (Phase 2) and TL3 (this phase, before it). Every table-adding feature
  (F11, F12, F21, F27, F35, C1) registers with the `Migrator`; TL4 reads its list.
- TL6: 9B (left column cards, Phase 2); swaps every use of `Capabilities::PRODUCTS` / `SETTINGS`, including controllers
  and REST routes added in Phases 2-7.
- TL7: ON1's footer partial (Phase 2; its AC3 already asks for "Version <x.y.z>"): TL7 first checks what ON1 shipped and
  adds only what AC9 and F38 N6 still need (the `admin_footer_text` filter on Affibloom pages, blanking WordPress's
  version there). If ON1 already satisfies AC9, TL7 shrinks to its test and the N6 filter; record it.
- Task 38 (TL5): 9B layout (Phase 2), F21's index of posts that use Affibloom (35A-35J, Phase 5), F9 General settings
  route (9B-9L, Phase 2), F25's table post type (T1-T17, Phase 7).
- ON5: ON1 (Phase 2); F22's link opportunities screen (O*, Phase 7); F9 9K `disable_tooltips` (Phase 2).
- ON6: ON1; F16's eight themes, the display settings route, and the live preview 10D (Phase 3).
- ON7: ON6; C1 Amazon fields and Validate (C1A-C1D, Phase 2), C3 Google Analytics fields (C3A-C3C, Phase 7), F33's import
  list partial (IM*, Phase 5).
- ON8: ON1; F19's search box and filters panel (13E, Phase 2); only the tabs that exist then get a step.
- ON9: ON1; F23's `GET /affibloom/v1/products?search=` (G4, Phase 2: whichever of G4 / ON9 is built first owns it); F19's
  search box (Phase 2).
- Task 39 needs 37A-39A's findings closed, and every feature's "Needs the user" decisions (for the "not rebuilt" section; all decided 2026-11-14).

**Later phases:** Phase 9 (Tasks 40-42) uses the reviews' findings (37D's LiteSpeed JS-delay step goes into the
cutover runbook, Task 41) and the release zip of Task 39. Nothing in this phase waits on Phase 9.

**Settled (was unresolved):** ON8 says "Tasks 26A-26P gains the copy of `lasso_dashboard_tour_done`", and ON5 says Tasks 26A-26P copies the
closed education boxes (`link-`, `content-`, `domain-opportunities` = 'hide'). The map lists Tasks 26A-26P only as 26A-26F,
26G, 26I, 26K, 26M, 26N, 26O, 26P; no sub-task is named for these two settings copies. Whoever assembles Phase 5
must give them a home (suggest 26M, which migrates the Dashboard's saved views). **Resolved 2026-11-14:** Phase 5's Task 26M copies both (the tour flag to `affibloom_tour_done`; the closed boxes to `affibloom_settings['education_hidden']`, which replaces F22's `affibloom_hidden_intros`).

**Dropped as duplicates:** ON10 (F9 9E owns the Settings unsaved-changes guard) and F19 13L / 13M (ON9 / ON8 own the
palette and tour).

---

### The reviews: what they cover (shared by Tasks 37A-37D)

The four reviews cover the whole plugin as built by Phases 2-7, not a sample. The scope, from the feature analyses
(`<plugin>\docs\features\*\analysis.md`, "Entry points"), is:

- **Every admin screen:** Dashboard (with the add modal, quick edit, saved views, filters, the Alert tabs), the product page
  (Details, Locations, Fields, Variants tabs and every modal), Settings General, Display and Amazon, Groups (list and
  group page), Fields, Tables (list and table page), Opportunities (links, domains, content), Import, History, Logs,
  DB Status, and the setup wizard. `docs/ui/screen-inventory.md` is the checklist; a screen missing from it is added
  to it first.
- **Every REST route and `admin-post.php` action** that the analyses list (for example `/products`, `/products/{id}`,
  `/products/groups`, `/products/{id}/locations`, `/products/{id}/variants`, `/products/{id}/repoint`,
  `/products/{id}/amazon/refresh`, `/dashboard/products`, `/dashboard/views`, `/dashboard/export`, `/dashboard/tour`,
  `/groups`, `/fields`, `/tables`, `/opportunities/*`, `/import/*`, `/history/*`, `/settings*`, `/amazon/*`,
  `/amazon-import/dismiss`, `/link-index/rebuild`, `/jobs`, `/jobs/run-due`, `/migration/run`, `/migration/convert`,
  `/display-preview`, `/editor/preview`, `/ai-text`, `/education/{key}/hide`, `/tour/done`, `/setup/done`; and the
  `admin-post.php` actions `affibloom_save_settings`, `affibloom_tracking_id`, `affibloom_add_icon`,
  `affibloom_remove_icon` and `affibloom_export`). The list is not typed from memory: step 1 of 37A generates it from
  the code and compares it with the analyses.
- **Every public entry point:** `POST /clicks` (F12), the cloaked-link redirect (F24), the shortcodes (F31: the
  Affibloom shortcode and the `[lasso]` compatibility shortcode), the render-time content filter (F21 35H), and the
  product box on a guide (F5-F8, F30).
- **Every cron job:** the events that Affibloom registers (`affibloom_daily`, `affibloom_link_check_batch`,
  `affibloom_link_check_product`, `affibloom_amazon_refresh`, `affibloom_amazon_bulk_sync`, `affibloom_genius_cleanup`,
  `affibloom_link_index_batch`, `affibloom_link_index_daily`, `affibloom_import_batch`, `affibloom_bulk_add`, F35's
  jobs; the authoritative list is `Infrastructure\WordPress\CronRegistry`, TL1) against **F11's inventory of Lasso's
  cron and background jobs** (`docs/features/F11/analysis.md`, "Cron and background-job inventory", E1-E31 and the
  process classes in table B).

Each review writes one document under `docs/reviews/` and is one commit. New code appears only as fixes, each with a
test. A finding is either fixed, or recorded with the reason it stands (and, where it is the user's call, sent to the
user).

---

### Task 37A: Security review

**Roadmap:** 8.1 · **Feature:** all features (reviews)

**Why:** each feature was reviewed on its own. This task reviews the plugin as a whole, with tools, before anything
leaves the local copy. The attack surface is now much larger than the product library the first plan assumed: groups,
tables, imports, a content filter, cloaked redirects, shortcodes and a dozen cron jobs.

**Depends on:** Phases 2-7 complete.

**User action:** none. Read `docs/reviews/security.md` and settle the findings it marks "for the user".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `docs/reviews/entry-points.md` (the generated inventory: every screen, route, action, public entry point, cron hook, with its capability, nonce and input checks) | the other three reviews, Task 39's developer guide |
| `tests/E2E/security.spec.ts` (walks the REST index and the `admin-post` list) | `npx playwright test security` |
| `docs/reviews/security.md` | the user, Task 39 |

New code appears only as fixes, each with a test.

**Files:** create the three files above; modify whatever the findings need.

- [ ] **Step 1: Inventory.** Build `docs/reviews/entry-points.md` from the code, not from the analyses:

  ```bash
  bash tools/local-wp.sh eval 'foreach ( rest_get_server()->get_routes() as $r => $h ) { if ( 0 === strpos( $r, "/affibloom/" ) ) { echo $r, " ", implode( ",", array_keys( $h[0]["methods"] ) ), "\n"; } }'
  grep -rn "register_rest_route\|admin_post_\|wp_ajax_\|add_shortcode\|add_filter( 'the_content'\|wp_schedule_\|add_rewrite\|template_redirect" src affibloom.php
  bash tools/local-wp.sh cron event list --format=table
  ```

  Compare it with the "Entry points" sections of `docs/features/*/analysis.md`. A route in the code and not in an
  analysis, or the reverse, is a finding.
- [ ] **Step 2: Failing test (the outermost).** `tests/E2E/security.spec.ts` reads the REST index
  (`/wp-json/affibloom/v1`) and, for every route and method except `POST /clicks`, asserts: anonymous gets 401; a
  logged-in Author (created in the test, removed after) gets 403; an administrator without the REST nonce gets 403
  (`rest_cookie_invalid_nonce`). For each `admin-post.php` action in the inventory: no nonce gets the "link expired"
  stop, an Author gets 403. Expected: red on any route whose `permission_callback` is `__return_true` or too weak. With
  TL6 (Permissions) built, the Author case also runs with the minimum role set to Author and expects 200 only where
  the role is allowed.
- [ ] **Step 3: Plugin Check.**

  ```bash
  bash tools/local-wp.sh plugin install plugin-check --activate
  bash tools/local-wp.sh plugin check affibloom --format=table
  ```

  Fix everything Plugin Check reports, except false positives, each explained in `security.md`. Deactivate and delete
  Plugin Check afterwards (`plugin deactivate plugin-check && plugin delete plugin-check`).
- [ ] **Step 4: By hand, per entry point.** Record each item as checked in `security.md`, per screen, route, action,
  public entry point and cron job from step 1:
  1. **Every write needs a capability check and a nonce:** the `admin_post_*` handlers, each REST route's
     `permission_callback`, the setup wizard, the tour and education routes, the import and migration routes, the
     content-converting routes (`/migration/convert`, `/history/{post}/revert`, `/history/cleanup`).
  2. **Public routes:** `POST /clicks` is the only public REST route; it writes only after validation (product exists
     and is published, button name allowed, post ID plausible), the 30-second repeat guard holds, and it never echoes
     data. The cloaked-link redirect (F24) sends readers only to a stored product URL (no open redirect through the
     slug or the prefix, no redirect for unpublished or trashed products, old slugs and prefixes follow the same
     rule). The shortcodes (F31) escape every attribute, render published products only, and ignore attributes that
     would reach other posts' private data. The render-time content filter (F21 35H) runs only on the front-end main
     content, never on feeds, REST, previews, excerpts or admin requests, and never writes to the database.
  3. **Every input is sanitised and validated:** `ProductInput`, `SettingsService`, group, field, table and import
     inputs, the REST `args`, the click args, CSV or file uploads (type, size), URLs (only `http`/`https`).
  4. **Every output is escaped.** Run `grep -rn "echo \$" templates src` and justify each `phpcs:ignore` in the review.
  5. **Every SQL query is prepared:** `grep -rn "\$this->db->\(query\|get_\)" src`, including the custom tables
     (`affibloom_link_locations`, `affibloom_link_issues`, `affibloom_clicks`, `affibloom_amazon_products`,
     `affibloom_url_variants`, `affibloom_detected_links`, `affibloom_amazon_short_links`) and `LIKE` clauses
     (`$wpdb->esc_like`).
  6. **Stored HTML is safe.** Description HTML goes through `wp_kses_post()` on save and on output; block attributes,
     per-box text and custom CSS (`custom_css`) are checked for who may set them.
  7. **Outbound requests:** only Amazon's API, the product and merchant URLs the link check visits, and nothing to
     Lasso's cloud (`grep -rn "getlasso\|lasso.link\|lasso.to" src` returns nothing). User-supplied URLs are fetched
     with `wp_safe_remote_*` (no private or local addresses).
  8. **Secrets:** Amazon credentials and the AI key are stored in options that are not autoloaded, never printed in
     HTML, REST responses or logs (read the log files from TL3), and never exported by Task 38's CSV.
  9. **Cron jobs:** no job can be started by an anonymous request; `POST /jobs/run-due` and the Dashboard fallback
     need the capability; locks stop two runs; a job's failure is logged and does not leak a path.
  10. **Uploads:** the log folder is not reachable from the web (TL3), the CSV export is not stored on disk.
  11. **A user without the capability gets 403:** the E2E test of step 2 is the proof; add the cases the generic walk
      cannot cover (an Author calling `PATCH /products/{id}` for a product they could not otherwise see).
- [ ] **Step 5: Fix what the review found,** each fix with a test (the failing case first), `composer lint`, `composer
  analyse`, the unit suite on the machine's PHP 8.5 CLI and the E2E suite against Local.
- [ ] **Step 6: Run and look.** `npx playwright test security`, green; open `docs/reviews/security.md` and check that
  every inventory row has a verdict.
- [ ] **Step 7: Commit** the review document, the inventory, the test and the fixes: "Review the security of every
  entry point

  Findings and their fixes are recorded in docs/reviews/security.md."

---

### Task 37B: Performance review

**Roadmap:** 8.2 · **Feature:** all features (reviews)

**Why:** Affibloom must be no slower than Lasso for readers and no heavier in the background, on a site with 263
products and 250 boxes. The comparison with Lasso is by Lasso's real list of jobs, not by a round number.

**Depends on:** Phases 2-7 complete; Task 37A's inventory (`docs/reviews/entry-points.md`).

**User action:** none. Read `docs/reviews/performance.md`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `tests/E2E/performance.spec.ts` (query-count and outbound-request budgets) | `npx playwright test performance` |
| `docs/reviews/performance.md` (the numbers, the cron comparison, the findings) | the user, Task 39 |

New code appears only as fixes, each with a test.

**Files:** create the two files; modify whatever the findings need (for example the meta cache priming).

- [ ] **Step 1: Failing test.** `tests/E2E/performance.spec.ts` asserts, with the query count read from Query Monitor's
  response headers (or, if this install does not send them, from a throwaway mu-plugin in `.local/`, gitignored, that
  adds `get_num_queries()` to a response header): a guide with 10 boxes adds no more than 3 queries over the same page
  without boxes; the cache warm-up of the 34 guides makes no request to an outside service; the click route and the
  cloaked-link redirect each run in a fixed small number of queries. Expected: red wherever a box costs a query.
- [ ] **Step 2: Reader-facing pages.** On the local copy, with Query Monitor active, measure with Lasso active and
  Affibloom inactive, then with Lasso off and Affibloom active:
  - `/best-volleyball-shoes-for-hitters/`, and two more guides;
  - one page with no box;
  - a page using the Affibloom shortcode and one using the `[lasso]` shortcode (F31);
  - a page with a table (F25) and one with a group (F23, F30);
  - the cloaked-link redirect and `POST /clicks`.

  Record the number of queries and the query time, PHP time, page weight, and script count and script bytes. Run
  Lighthouse three times per page (`npx lighthouse <url> --preset=desktop --only-categories=performance --output=json`)
  and record the median Largest Contentful Paint and Total Blocking Time. Include the render-time content filter (F21
  35H): time it on the longest guide.
- [ ] **Step 3: Admin screens.** Measure queries and time for the Dashboard (263 products, each page size, search,
  filters, the Alert tabs, saved views), the product page and its Locations tab, Groups, Fields (usage counts read
  every product's `_affibloom_fields`), Tables, Opportunities (links, domains, content), Import, History, Logs, DB
  Status and Settings General (the Link Index card and its refresh). A screen with a query count that grows with the
  number of rows is a finding.
- [ ] **Step 4: Background load against Lasso's real list.** Take the rows of F11's inventory (E1-E31, and the process
  classes in table B) as the baseline. On the local copy with Lasso active, `bash tools/local-wp.sh cron event list`
  gives Lasso's events present today; record how many fire per day and per hour. With Affibloom active alone, list its
  events (`CronRegistry`) and run each once (`bash tools/local-wp.sh cron event run <hook>`), recording time, queries
  and memory. Build a table in `performance.md` with one row per Lasso job (E1-E31): "replaced by <Affibloom hook>",
  "not built (cloud)", or "not built (reason)" (the rows marked cloud in the inventory are the point: Affibloom makes
  no request to Lasso). Expected:
  - Affibloom's background work is equal or smaller on every measure: events per day, rows scanned per run, memory,
    requests to outside hosts;
  - nothing runs at a high frequency without work queued (the chained single events stop when the queue is empty);
  - the link check, the index build and imports respect the CPU threshold and the performance mode (F9, F11 32C).
- [ ] **Step 5: Fix.** If a guide runs one query per box, add `update_postmeta_cache()` for all box product IDs,
  collected before the boxes render, and prove the gain with the numbers. Any other finding gets its fix and its test.
- [ ] **Step 6: Run and look.** `npx playwright test performance` green; re-measure the fixed pages and put the before
  and after numbers in `performance.md`. Expected: Affibloom equal or better on every measure.
- [ ] **Step 7: Commit:** "Review the performance of pages, screens and background jobs

  Numbers, the comparison with Lasso's job list and the fixes are recorded in docs/reviews/performance.md."

---

### Task 37C: Accessibility review

**Roadmap:** 8.3 · **Feature:** all features (reviews)

**Why:** the admin has grown from a Dashboard to a dozen screens with modals, a group picker, drag-and-move lists, a
command palette, a wizard and a tour. Each must work by keyboard and screen reader (U3), and meet the contrast ratios
the design system states.

**Depends on:** Phases 2-7 complete; ON5-ON9 (this phase) for the wizard, palette and tour; Task 9A's
`docs/ui/design-system.md`.

**User action:** none. Report any shortfall in the box's colours to the user; colours are the client's choice.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `tests/E2E/a11y.spec.ts` (axe on every screen, modal and public page; keyboard flows) | `npx playwright test a11y` |
| `docs/reviews/accessibility.md` | the user, Task 39 |

New code appears only as fixes, each with a test.

**Files:** create the two files; modify whatever the findings need. `npm install --save-dev @axe-core/playwright`.

> Order note: ON5-ON9 come later in this phase. 37C is therefore run after them, or re-run for the wizard, palette,
> tour and education boxes when they land; the review document lists what was checked when.

- [ ] **Step 1: Failing test.** `tests/E2E/a11y.spec.ts` runs
  `new AxeBuilder( { page } ).withTags( [ 'wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa' ] ).analyze()` and expects
  `violations` to be `[]` on:
  - the Dashboard, with the add modal open, the quick edit open, the filters panel open, an Alert tab, and the
    command palette open;
  - the product page (Details, Locations, Fields and Variants tabs, with the group picker open and each modal);
  - each Settings tab (General, Display, Amazon);
  - Groups (list, a group page with products and the add-products modal), Fields, Tables (list and a table page),
    Opportunities (links, domains, content), Import, History, Logs, DB Status;
  - the setup wizard on every step, and the Dashboard tour on every step;
  - the editor with the block picker open, and a guide with boxes.

  Expected: red on the first screen with a violation.
- [ ] **Step 2: By hand, with only the keyboard.** Record each as checked:
  - add a product, edit it, save, delete it, insert a box;
  - add a product to a group and remove it with the **group picker**;
  - **drag-and-move controls** (G4 products in a group, FL5 field order, T9 table columns and rows): each has a
    keyboard alternative (move up and down buttons or an equivalent), and the new position is announced
    (`aria-live`);
  - the **command palette** (ON9): opens with Ctrl+Shift+L, focus goes to the search field, the results are a
    listbox with the active option announced, arrows and Enter work, Escape closes it and returns focus to where it
    was;
  - the **setup wizard** (ON6, ON7): the step bar marks the current step (`aria-current="step"`), focus moves to the
    new step's heading on Save and Continue, Skip and Back, errors are announced;
  - the **Dashboard tour** (ON8): focus stays in the tooltip, steps are announced, the spotlight does not hide
    content from assistive technology;
  - the notice region and the **Automations** popover (ON2, ON3): announced politely, the popover is reachable and
    closable by keyboard, the Menu button (`aria-expanded`, `aria-controls`);
  - the education boxes' close button (ON5) has a name.

  In all of these check that:
  - focus is always visible;
  - modals trap focus and return it on close;
  - toasts are announced (`role="status"`);
  - help text is reachable by keyboard and read by a screen reader on every screen (U3).
- [ ] **Step 3: Contrast.** Check the box's colour contrast with the site's real colours. Report any shortfall to the
  user; colours are the client's choice. Check the admin screens' contrast against the ratios stated in
  `docs/ui/design-system.md` (ADR-8): text, controls, focus rings, the notice tones and the step bar; a shortfall
  there is ours to fix. Check 200% zoom and 375 px width on the Dashboard, the product page and the wizard.
- [ ] **Step 4: Fix,** each with a test (the failing axe or keyboard case first).
- [ ] **Step 5: Run and look.** `npx playwright test a11y` green; one pass with a screen reader (NVDA on this machine,
  if installed; otherwise record that no screen reader was available and what was checked instead) over the product
  page and the wizard.
- [ ] **Step 6: Commit:** "Review the accessibility of every admin screen and the box

  Findings and their fixes are recorded in docs/reviews/accessibility.md."

---

### Task 37D: Compatibility review

**Roadmap:** 8.4 · **Feature:** all features (reviews)

**Why:** the plugin must behave with the caching, SEO, layout and ad plugins the site runs and on the PHP and
WordPress versions it can meet, before the cutover.

**Depends on:** Phases 2-7 complete.

**User action:** the staging smoke test (step 5) needs a zip uploaded to staging through wp-admin (no SSH is
available); the user uploads it or says go. The PHP 8.3 result comes from there.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `tests/E2E/compat.spec.ts` (a cached guide renders boxes and counts a click; the cloaked link and shortcodes under cache) | `npx playwright test compat` |
| `docs/reviews/compatibility.md` | the user, Task 39, Task 41's runbook |

New code appears only as fixes, each with a test.

**Files:** create the two files; modify whatever the findings need.

- [ ] **Step 1: Failing test.** `tests/E2E/compat.spec.ts`: with LiteSpeed Cache active and purged, a guide loaded
  twice as a visitor renders its boxes from cache and a click is still counted; the cloaked link (F24) still
  redirects and counts; a page with each shortcode (F31) renders. Expected: red where cache changes the result.
- [ ] **Step 2: Caching and plugins on the local copy** (`bash tools/local-wp.sh plugin list` shows what is active; check
  each):
  - Activate LiteSpeed Cache with the production settings. Purge, and load a guide as a visitor twice. Check that the
    boxes render from cache and that a click is still counted.
  - If "Delay JavaScript" is on, check that `clicks.js` still runs on the first click; if not, add `affibloom-clicks`
    to LiteSpeed's JS delay exclusions, and write that step into the cutover runbook (Task 41).
  - Check Rank Math's sitemap and schema output, which should not change; and that the box's review schema (F4/F5)
    does not duplicate Rank Math's.
  - Check GenerateBlocks layouts around boxes.
  - Check Mediavine ad insertion next to boxes; ads must not land inside a box.
  - Check the render-time content filter (F21 35H) and the shortcodes with page caching and with the plugins above.
  - Check the admin: other plugins' notices are hidden on Affibloom screens only (ON2) and not elsewhere; the Affibloom
    scripts and styles are not removed or broken by other plugins' admin assets.
  - Check Affibloom and Lasso active together (the state during the cutover window): no fatal, no duplicate output of a
    box, and a recorded verdict on which plugin answers `[lasso]` (F31).
- [ ] **Step 3: PHP versions.** The matrix is what this machine and staging offer, not a CI matrix (deviation 10):
  - **Local's PHP 8.3:** the whole E2E suite against Local, and `composer analyse`;
  - **the machine's PHP 8.5 CLI:** `composer lint` and the unit suite; record deprecation notices (8.4 and 8.5
    deprecations such as implicit nullable parameters are findings);
  - **PHP 8.3 (production's version) on staging:** the smoke test of step 5;
  - PHP 8.3 is the plugin's minimum (decision F7, Task 2X0), so the Local and staging runs cover it; PHPStan's
    `phpVersion` is 80300.
- [ ] **Step 4: WordPress versions.** Local's WordPress version and staging's are the two that can run; the minimum
  (7.1.4, decision F7) and the integration suite need wp-env (Docker), which this machine lacks. Record the integration tests
  that have not run on this machine (list the files in `tests/Integration/`) and that they stay unrun until a machine with wp-env is
  available; Plugin Check's required-version and deprecated-function checks (Task 37A) stand in for 7.1.4.
- [ ] **Step 5: Staging smoke on PHP 8.3.** Build the zip as Task 39 step 5 does (a throwaway build, not the release),
  upload it to staging through wp-admin, and check: activation; the Dashboard opens; add a product; place a box;
  a guide renders it; a click is counted; the Logs and DB Status screens open; Plugin Check has no new finding.
  Staging's Lasso stays active (staging memory): note any conflict.
- [ ] **Step 6: Fix,** each with a test, and record the findings and the runs that did not happen.
- [ ] **Step 7: Run and look.** `npx playwright test compat` green; open `docs/reviews/compatibility.md` and check that
  every plugin, PHP version and WordPress version row has a verdict or a "not run" with its reason.
- [ ] **Step 8: Commit:** "Review the compatibility with caching, SEO and ad plugins and with PHP versions

  Findings, the runs available on this machine and what could not be run are recorded in docs/reviews/compatibility.md."

---
### Task 38: Uninstall opt-in, the Uninstall card and the product export

**Roadmap:** 8.5 · **Feature:** F37 (R14-R19, N1; AC6, with TL5), F9 (R63-R65; AC16)

**Why:** deleting the plugin must not destroy a library by accident, and an administrator who really wants it gone
needs one switch that removes everything. Lasso has no uninstall routine at all (deleting it removes nothing; F37 R14);
this opt-in is Affibloom's own, and it replaces Lasso's "Uninstall Lasso" card, which rewrites every post (not built,
F37 N1). The export gives the owner the whole product library as a file first. (The issue-tab export is F11 31D; this
one is the library-wide product export.)

**Depends on:** F9 9B (the General card layout, Phase 2) and its General settings route; F21 (the index of posts that
use Affibloom, Phase 5) for the list on the card; every store-adding feature, because the data inventory below must
cover them; F25 (tables), F23 (groups). If F37 N1 is answered otherwise, this
task is rewritten.

**User action:**
1. **Deactivating** the plugin leaves everything in place (TL1 only clears the cron hooks).
2. **Deleting** it removes its data only if an administrator has turned on the General → **Uninstall** card's
   "Delete all Affibloom data when the plugin is deleted" (help text "Products, click counts and link checks are
   removed for good. Export them first."). The card also lists "Posts that still use Affibloom" with edit links, so
   the owner sees what will stop rendering. Nothing rewrites a post.
3. **Exporting:** on the Dashboard, "Export CSV" downloads every product with every field.

**The data inventory.** Every store the feature analyses define (the "Data" sections, grepped 2026-11-14) is listed
here. The list lives in one class, `Domain\Lifecycle\DataInventory`, and a test fails when the code uses a name that is
not in it. Removal, when opted in, covers:

- **Options:** `affibloom_settings` (including its `display` key and `education_hidden`, the closed education boxes: the settled key, F38 ON5; Task 38 removes it), `affibloom_display`,
  `affibloom_db_version`, `affibloom_schema_version`, `affibloom_version`, `affibloom_activated_at`,
  `affibloom_upgrade_lock`, `affibloom_repair_count`, `affibloom_repair_next`, `affibloom_amazon_credentials`,
  `affibloom_amazon_bulk_sync`, `affibloom_amazon_import_lock`, `affibloom_amazon_import_batch`,
  `affibloom_sitestripe_notice`, `affibloom_link_check`, `affibloom_link_index_state`, `affibloom_fields`,
  `affibloom_migration`, `affibloom_migration_lock`, `affibloom_lasso_import` (the older name), `affibloom_import_run`,
  `affibloom_import_reindex_notice`, `affibloom_icons`, `affibloom_ai_texts`, `affibloom_cloak_prefix_history`,
  `affibloom_tour_done`, `affibloom_setup`, `affibloom_auto_detect_lock`, and any option
  the drift test finds.
- **Post types:** every `affibloom_product` and `affibloom_table` post in every status (trash included), with its post
  meta (`_affibloom_*`: url, type, asin, buttons, fields, cloak, old slugs, lasso id, import keys, the table
  definition, and so on).
- **Taxonomy:** `affibloom_group` terms, and their term meta (`_affibloom_order`, import keys). The taxonomy is
  registered temporarily during the removal, because WordPress is not running the plugin then and cannot delete terms
  of an unregistered taxonomy.
- **Custom tables:** `{prefix}affibloom_link_locations`, `{prefix}affibloom_link_issues`, `{prefix}affibloom_clicks`,
  `{prefix}affibloom_amazon_products`, `{prefix}affibloom_url_variants`, `{prefix}affibloom_detected_links`,
  `{prefix}affibloom_amazon_short_links`.
- **User meta:** `affibloom_per_page`, `affibloom_saved_views`, `affibloom_bulk_sync_dismissed`,
  `affibloom_amazon_import_dismissed`, `affibloom_import_locations_page`.
- **Transients:** `affibloom_amazon_token_*`, `affibloom_link_check_lock`, `affibloom_link_issue_counts`,
  `affibloom_link_index_lock`, `affibloom_c_*` (and their timeout rows).
- **Cron hooks:** `affibloom_daily`, `affibloom_link_check_batch`, `affibloom_link_check_product`,
  `affibloom_amazon_refresh`, `affibloom_amazon_bulk_sync`, `affibloom_genius_cleanup`, `affibloom_link_index_batch`,
  `affibloom_link_index_daily`, `affibloom_import_batch`, `affibloom_bulk_add`, and F35's job hooks (read from
  `docs/features/F35/analysis.md` when the task is worked).
- **Post meta on content posts:** none is written by design (no block conversion and no clean-up: decisions A1, A2,
  2026-11-14). A final sweep still deletes any remaining `_affibloom_%` post meta row (orphans).
- **Files:** the `uploads/affibloom-logs/` folder (TL3).
- **Not stored, so not removed:** localStorage keys (`affibloom_table_locations`, `affibloom_jobs`,
  `affibloom_jobs_at`) belong to the browser and are left.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `uninstall.php` (guard, the opt-in check, then the eraser) | WordPress, when the plugin is deleted |
| `Domain\Lifecycle\DataInventory` (the lists above, no WordPress calls) | `DataEraser`, the drift test |
| `Infrastructure\WordPress\DataEraser` (`run()`: posts in batches of 100, taxonomy terms, tables, options, user meta, transients, cron hooks, post meta, log folder) | `uninstall.php` |
| setting `delete_data_on_uninstall` (General → Uninstall card, default off; moved here from the "Data" section the first plan had) | the card, `uninstall.php` |
| `partials/settings-general-uninstall.php` (the card: the toggle, the help text, "Posts that still use Affibloom" with edit links) | `settings.php` |
| `Interface\Admin\ExportController` (`admin_post_affibloom_export`) | the "Export CSV" button |
| `ProductRepository::all()` (a generator, 100 at a time) | `ExportController` |

`uninstall.php` loads `vendor/autoload.php` and the eraser, instead of holding the long lists inline as the first plan
did (a deviation, recorded in `progress.md`: the lists are long, have to stay in step with the code, and are tested):

```php
<?php
/**
 * Runs when the plugin is deleted. Removes data only if the administrator opted in.
 *
 * @package Affibloom
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$affibloom_settings = (array) get_option( 'affibloom_settings', array() );
if ( empty( $affibloom_settings['delete_data_on_uninstall'] ) ) {
	return;
}

require_once __DIR__ . '/vendor/autoload.php';

( new Affibloom\Infrastructure\WordPress\DataEraser( $GLOBALS['wpdb'] ) )->run();
```

Add `<file>uninstall.php</file>` to `phpcs.xml.dist`. In `DataEraser`, table names come only from `DataInventory`
(fixed names, `// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --
fixed table names.`).

**Files:** create `uninstall.php`, `src/Domain/Lifecycle/DataInventory.php`,
`src/Infrastructure/WordPress/DataEraser.php`, `src/Interface/Admin/ExportController.php`,
`templates/admin/partials/settings-general-uninstall.php`, `tests/Unit/Lifecycle/DataInventoryTest.php`,
`tests/Unit/Lifecycle/DataInventoryCoverageTest.php`, `tests/Integration/UninstallTest.php`,
`tests/E2E/acceptance/F37-uninstall.spec.ts`. Modify `Settings.php`, `SettingsService.php`, `settings.php`,
`ProductRepository.php`, `PostTypeProductRepository.php`, `dashboard.php` (the export button, posting to
`admin-post.php` with a nonce), `Plugin.php`, `docs/ui/design-system.md` only if the card needs a new element.

- [ ] **Step 1: Failing tests, outside in.**
  - E2E `F37-uninstall.spec.ts` (F37 AC6, F9 AC16): the General tab has an Uninstall card with the toggle and the
    help text and the list of posts using Affibloom with edit links; the Dashboard's "Export CSV" downloads a file
    with a header row and one row per product, names in UTF-8. Expected: red, no card and no button.
  - Integration `UninstallTest` (written now; it runs only where wp-env is available, which this machine lacks): with
    the setting off nothing goes; with it on every item of the inventory goes, with a fixture that creates one of each
    store.
  - Unit `DataInventoryTest` (the lists are non-empty, unique, prefixed) and `DataInventoryCoverageTest`: scans
    `src/` for string literals that look like an option, table, user-meta, transient or cron name (`affibloom_…`,
    `_affibloom_…`, `{prefix}affibloom_…`) and fails for any that is neither in `DataInventory` nor in the test's
    short allow-list of names that are not stored data (REST namespace, nonce actions, script handles, hook names
    that are filters). This is the test that keeps the inventory true as features land. Runs on PHP 8.5 CLI.
- [ ] **Step 2: The setting and the card.** `delete_data_on_uninstall` in `Settings`, `SettingsService` and the General
  route (administrators only); the card partial in `settings.php`; the list of posts that use Affibloom from F21's
  index, with edit links.
- [ ] **Step 3: `DataInventory` and `DataEraser`,** then `uninstall.php`. Check `DataInventory` against the
  analyses' "Data" sections and the drift test; fix the list, not the test.
- [ ] **Step 4: `ExportController`:**
  - checks the capability and the nonce;
  - sends the `Content-Type: text/csv` and `Content-Disposition: attachment; filename=affibloom-products-<date>.csv`
    headers;
  - writes a header row, then one `fputcsv` per product, using `ProductResource::full()` with the booleans as `1`/`0`
    (its keys give the columns, tested so that a new field cannot be missed); no Amazon credential or AI key is ever a
    column;
  - exits.
  Add the Dashboard button.
- [ ] **Step 5: Run and look.**
  1. Export on the local copy and open the file. Expected: 263 rows, with UTF-8 names intact.
  2. Back up first: `bash tools/local-wp.sh db export "C:/Users/Rupash/Local Sites/volleyballvault/app/affibloom-backup-<date>.sql"`.
  3. With the toggle off, run the uninstall file by hand:
     `bash tools/local-wp.sh eval 'define( "WP_UNINSTALL_PLUGIN", "affibloom/affibloom.php" ); include WP_PLUGIN_DIR . "/affibloom/uninstall.php";'`;
     count products, tables and options before and after: nothing changes.
  4. Turn the toggle on and run it again: every store of the inventory is gone (`wp db query` for the tables,
     `wp option list --search=affibloom*`, `wp post list --post_type=affibloom_product`, `wp term list affibloom_group`).
  5. Restore: `bash tools/local-wp.sh db import <the backup>`, and open the Dashboard to see the library back.
  6. The card at 1280 and 375 px.
- [ ] **Step 6: Commit** — "Add an opt-in data removal on uninstall, the Uninstall card and a product CSV export

  Lasso removes nothing on delete; this removal is Affibloom's own and runs only when the administrator opts in."

---

### Task TL3: Logs

**Feature:** F37 (R1-R8, D3; AC3, AC4)

**Why:** every background job and import can fail quietly; Lasso gives its support a Logs page, and Affibloom's jobs
now exist to write to one.

**Depends on:** the jobs that log: bulk add (F18, Phase 2), link checks (30A-30C, Phase 6), the index (35A-35J,
Phase 7), imports (IM*, Phase 5), Amazon (C1, Phase 2); F11's `affibloom_daily` cron for pruning (Phase 6); ON1's
header (Phase 2).

**User action:** `admin.php?page=affibloom-logs` (hidden, Settings band): "Enable Logs" and the seven read-only boxes
with Lasso's titles and descriptions (product name removed): Import Log, Link Discovery, Amazon Processing, Amazon
Product API Errors, Link Alerts, Duplicate Slug Changes, SQL Errors; Save Changes stores the switch.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Infrastructure\Logs\FileLogger` (`uploads/affibloom-logs`, with `index.php` and `.htaccess`; forced entries written when logging is off) | the error and progress paths of bulk add, link check, link index, import and Amazon code (each gets its log call in this task) |
| `Application\Logs\LogService`, `FileLogReader` | `LogsScreen` |
| `Admin\LogsScreen`, `templates/admin/logs.php` | the hidden submenu |
| setting `enable_logs` | the screen, `FileLogger` |
| `LogPruner` (14 days) | F11's `affibloom_daily` cron |

**Files:** create the classes and template, `tests/E2E/acceptance/F37-logs.spec.ts`, `tests/Unit/Logs/*Test.php`;
modify `Plugin.php`, `AdminMenu`, `Settings.php`, and each job named above for its log call.

- [ ] **Step 1: Failing tests.** E2E AC3 (the page, the switch, the seven boxes), AC4 (a log file is not reachable
  from the web: a request for it is refused). Unit: newest per type, exact type match, forced write when the switch is
  off, pruning after 14 days. Expected: red, no page.
- [ ] **Step 2: Classes and the screen.** `FileLogger`, `LogService`, `FileLogReader`, `LogsScreen`, the setting.
- [ ] **Step 3: Wire the callers** (the jobs listed under Depends on) and the pruner.
- [ ] **Step 4: Run and look.** Open the page on Local; trigger a log entry (a failing import row, or a SQL error on a
  test table); read it on the page; check the folder's `index.php` and `.htaccess`; at 1280 and 375 px.
- [ ] **Step 5: Commit** — "Add the Logs screen"

---

### Task TL4: DB Status and self-repair

**Feature:** F37 (R9-R13, D4; AC5)

**Why:** a table that is missing or lacks a column after an update stops a feature; Lasso repairs itself and shows the
state on a page.

**Depends on:** TL2 (`Migrator`, `Table`, Phase 2), TL3 (the `sql_errors` log, this phase).

**User action:** `admin.php?page=affibloom-db-status` lists each table with "Table already exists." or what changed.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Admin\DbStatusScreen`, `templates/admin/db-status.php` (one row per table in the `Migrator`'s list) | the hidden submenu |
| `Application\Schema\TableRepair` (3 tries an hour, then hourly) | repositories, on a "doesn't exist", "Unknown column" or collation error |

**Files:** create the classes and template, `tests/E2E/acceptance/F37-db-status.spec.ts`,
`tests/Unit/Schema/TableRepairTest.php`; modify `Plugin.php`, `AdminMenu`, the repositories of the custom tables.

- [ ] **Step 1: Failing tests.** E2E AC5 (the page lists every table; a dropped test table is back after a request);
  unit: the limiter (3 repairs, then wait). Expected: red.
- [ ] **Step 2: Screen and repair.** `DbStatusScreen`, `TableRepair`, hooked into the repositories' error paths and
  writing to the SQL Errors log (TL3).
- [ ] **Step 3: Run and look.** On Local, after a backup (`bash tools/local-wp.sh db export …`), drop a test table
  (`bash tools/local-wp.sh db query "DROP TABLE wp_affibloom_url_variants"`), open the page and a screen that uses it:
  the table is recreated and the page says what changed; restore the backup if anything else changed.
- [ ] **Step 4: Commit** — "Add the database status screen and table self-repair"

---

### Task TL6: Permissions

**Feature:** F37 (R31-R35, N2; AC7), F9 (R67)

**Why:** Lasso lets the owner choose the lowest role that can use it; Affibloom's screens and routes are today
administrator-only, hard-coded.

**Depends on:** 9B (the left-column card, Phase 2); decision C3 (the user, 2026-11-14: build it).

**User action:** "Permissions" under General: "Select the minimum user role that can access Affibloom."
(Administrator, Editor, Author, Contributor, Subscriber); an Editor sees the menu when Editor is chosen; the select
changes nothing for non-administrators.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Settings\Permission` (Administrator `manage_options`, Editor `edit_others_posts`, Author `publish_posts`, Contributor `edit_posts`, Subscriber `read`; unknown falls back to administrators) | `Capabilities` |
| `Capabilities` reads the setting (`products()`, `settings()`; the constants kept for the fallback) | `AdminMenu`, every controller and REST route |
| setting `permission` (administrator-only field) | the card |

**Files:** create `Permission.php`, `tests/Unit/Settings/PermissionTest.php`, `tests/E2E/acceptance/F37-permissions.spec.ts`;
modify `Capabilities.php`, `AdminMenu.php`, every controller and REST route that used `Capabilities::PRODUCTS` or
`SETTINGS`, the General template, `SettingsService`.

- [ ] **Step 1: Failing tests.** Unit: the mapping, and that an unknown value fails closed. E2E (AC7) with an Editor
  user created in the test and removed after: with Editor chosen the Editor sees the menu and the Dashboard; with
  Administrator chosen they do not; an Editor cannot change the select. Task 37A's `security.spec.ts` is re-run with
  the role at Author. Expected: red.
- [ ] **Step 2: `Permission` and `Capabilities`;** swap every use of `Capabilities::PRODUCTS` / `SETTINGS` (grep for
  them; the list goes in the commit body).
- [ ] **Step 3: The card.**
- [ ] **Step 4: Run and look.** Log in as an Editor in a second browser profile; set the role; reload; the menu
  appears and disappears as chosen; set it back to Administrator.
- [ ] **Step 5: Commit** — "Let the owner choose which role can use Affibloom"

---

### Task TL7: Version in the admin footer

**Feature:** F37 (R37, D7; AC9), F38 (R16, N6)

**Why:** Lasso shows its version in every admin footer and blanks WordPress's own text on its pages; support and the
owner read the version there.

**Depends on:** ON1's footer partial (Phase 2), which already asks for "Version <x.y.z>" (F38 AC3). Step 1 checks what
ON1 shipped; this task does only what remains.

**User action:** every Affibloom screen's footer reads "Version <x.y.z>" (the plugin's version); WordPress's own
footer version text does not show on Affibloom pages (the rest of wp-admin is untouched).

| New | Called by |
|---|---|
| `admin_footer_text` and `update_footer` filters on Affibloom screens only (if ON1 did not already add them) | `AdminAssets` (or `AdminMenu`) |

**Files:** modify `AdminMenu.php` or `AdminAssets.php`; test `tests/E2E/acceptance/F37-footer.spec.ts`. No new class.

- [ ] **Step 1: Failing test.** E2E AC9 on every Affibloom screen (including the hidden Logs and DB Status): the footer
  reads "Version <x.y.z>" and WordPress's version text is absent; on Posts it is still shown. If ON1's test already
  passes for the version, only the WordPress-text part is red; say so in the task summary.
- [ ] **Step 2: The filters.**
- [ ] **Step 3: Run and look.** Open three screens and Posts.
- [ ] **Step 4: Commit** — "Show the plugin version in the admin footer"

---
### Task ON5: Education boxes: close, remember, reset, hide with help tips

**Feature:** F38 (R29-R36; D15, D16; AC9)

**Why:** the opportunities screens open with an explanation the editor closes once; Disable Help Tooltips must hide
them as Lasso does.

**Depends on:** ON1 (Phase 2); F22's link opportunities screen (O*, Phase 7: the first box with a close); F9 9K
(`disable_tooltips`, Phase 2).

**User action:** on Link Opportunities, a "Turn Any Existing Link Into a Product Link"-style box (Lasso's words without
"Lasso") with ×; closed, it stays closed after reload; `&reseteducation=1` brings it back; with Disable Help Tooltips
on, no box. The empty-state screens of Groups, Locations, Tables and the opportunities reuse the box without ×.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Shell\EducationBoxes` (`visible`, `hide`, `reset`), `Domain\Shell\EducationKey` (allow-list: link, domain, content opportunities) | the opportunities screens, `EducationController` |
| `Interface\Rest\EducationController` `POST /affibloom/v1/education/{key}/hide` | `admin.js` |
| `templates/admin/partials/education-box.php` (image, heading, text, optional action, optional ×); the empty-state screens reuse it without × | F22, F23, F12, F25 screens |

**Files:** create the classes and partial; tests `tests/Unit/Application/Shell/EducationBoxesTest.php`,
`tests/E2E/acceptance/F38.spec.ts` (AC9); modify F22's screen template, `assets/admin/admin.js`,
`docs/ui/design-system.md`.

- [ ] **Step 1: Failing tests.** E2E AC9 (close, reload, `&reseteducation=1`, tooltips off); unit: visible, hide,
  reset, an unknown key refused. Expected: red, no box.
- [ ] **Step 2: Service and key** (`EducationBoxes`, `EducationKey`).
- [ ] **Step 3: Route, partial, script.**
- [ ] **Step 4: Run and look.** The three opportunities screens on Local at 1280 and 375 px; close one, reload.
- [ ] **Step 5: Commit** — "Let editors close education boxes, as in Lasso"

---

### Task ON6: Setup wizard: first visit, Theme, Customize

**Feature:** F38 (R5, R45-R52; D17; N1; AC10)

**Why:** a new site gets its box defaults in two minutes; Lasso's wizard is how.

**Depends on:** ON1 (Phase 2); F16 (the eight themes and the display settings route, Phase 2; the live preview 10D,
Phase 3); N1 decided (D13).

**User action:** after the first activation on an empty site, the first Affibloom page opens **Setup**: step bar
(Theme, Customize, Amazon, Analytics, [Imports,] Done); "Choose a Default Display Box Theme" / "This is an example of
how a product will look on your site." with Cactus, Flow, Lab, Money, Cutter, Geek, Llama, Splash; a pick opens
"Customize Your Displays" (live box, Lasso's fields and tips); **Save and Continue →** saves and moves on, **Skip →**
moves on, **← Change Themes** goes back, a done step in the bar returns to it.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Shell\SetupState`, `Infrastructure\WordPress\OptionSetupState` | `ActivationRedirect`, `SetupScreen` |
| `Interface\Admin\{SetupScreen, ActivationRedirect}` (page `affibloom-setup`, SETTINGS; once) | WordPress |
| `templates/admin/setup.php`, `partials/setup-steps.php`, `assets/admin/setup.js` (steps, back via the bar) | `SetupScreen` |
| the activation hook's "pending" mark (only with no `affibloom_settings` and no Lasso data) | `affibloom.php` (through TL1's `Lifecycle::activate()`) |

**Files:** create the classes and templates; tests `tests/Unit/Application/Shell/SetupStateTest.php`, an activation
integration test (wp-env only), `tests/E2E/acceptance/F38.spec.ts` (AC10); modify `affibloom.php`, `Plugin.php`,
`AdminAssets.php`, `docs/ui/design-system.md` (step bar).

- [ ] **Step 1: Failing tests.** E2E AC10 (on a fresh state the first visit opens Setup; the Theme and Customize steps
  work; Skip and Back; the step bar); unit: `SetupState` (pending only without settings and without Lasso data; once).
  Expected: red.
- [ ] **Step 2: State and redirect** (`SetupState`, `OptionSetupState`, `ActivationRedirect`).
- [ ] **Step 3: Screen, two steps, saves through F16's route** (`SetupScreen`, templates, `setup.js`).
- [ ] **Step 4: Run and look.** Reset the Setup option on Local (`bash tools/local-wp.sh option delete affibloom_setup`,
  after a backup of the options) and walk the first two steps at 1280 and 375 px; the Lasso screenshots are the
  reference for fields and words, the design system for looks.
- [ ] **Step 5: Commit** — "Add the setup wizard's theme and display steps"

---

### Task ON7: Setup wizard: Amazon, Analytics, Imports, Done

**Feature:** F38 (R53-R58; D17; N7; AC11)

**Why:** completes Lasso's wizard with the steps whose settings C1, C3 and F33 build.

**Depends on:** ON6; C1 / F2 (Amazon fields, Validate; C1A-C1D, Phase 2), C3 (GA fields; C3A-C3C, Phase 7), F33 (the
import list partial; IM*, Phase 5).

**User action:** "Amazon Associates" (Tracking ID for This Site, auto-monetize, Product Data API with Validate,
Preferences without Commission Upgrades), "Google Analytics" (ID, Click Tracking, Pageview, IP Anonymization; the help text says events are `affibloom_*` and start from zero),
"Imports" (only when sources exist; Continue →), "You're Ready" / "That's it! You're now ready to add displays to your
site." / **Go to Dashboard**; the wizard never opens by itself again.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the three step partials in `setup.php` (C1's, C3's and F33's fields and list partials reused) | `SetupScreen` |
| `POST /affibloom/v1/setup/done` → `SetupState::finish()` | `setup.js` |

**Files:** the partials and the route; tests `tests/E2E/acceptance/F38.spec.ts` (AC11), a route unit test; modify
`setup.php`, `setup.js`.

- [ ] **Step 1: Failing tests.** E2E AC11 (the Amazon, Analytics and Imports steps show Lasso's fields and save as AC10;
  "You're Ready" then **Go to Dashboard** ends the wizard; it never opens again). Expected: red.
- [ ] **Step 2: The three steps,** reusing the feature partials (second use: extract where the first draft copied).
- [ ] **Step 3: Done** (`/setup/done`, `SetupState::finish()`).
- [ ] **Step 4: Run and look.** The whole wizard on Local with the Amazon credentials left empty (the Validate button
  is dormant without credentials, C1) at 1280 and 375 px.
- [ ] **Step 5: Commit** — "Complete the setup wizard"

---

### Task ON8: The Dashboard tour

**Feature:** F38 (R59-R64; D18; AC12)

**Why:** Lasso introduces its Dashboard once to a new team.

**Depends on:** ON1 (Phase 2); F19's search box and filters panel (13E, Phase 2); the tabs that exist when it runs (a
step whose target is missing is dropped).

**User action:** first Dashboard visit on a site that has not done it: a spotlight with Lasso's step texts (without
"Lasso"), Back / Next / Done / Skip and dots; Filters opens before its two steps; Escape and backdrop clicks do
nothing; Done or Skip ends it for everyone. (The Help and Chat steps drop: decisions B2, B1, B10.)

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Shell\TourState` + `Infrastructure\WordPress\OptionTourState` | `DashboardScreen`, `TourController` |
| `Interface\Rest\TourController` `POST /affibloom/v1/tour/done` (PRODUCTS) | `assets/admin/tour.js` |
| `assets/admin/tour.js` (spotlight, focus kept in the tooltip, steps whose target is missing skipped) | `AdminAssets` on the Dashboard while not done |

**Files:** the classes and script; tests `tests/Unit/Application/Shell/TourStateTest.php`,
`tests/E2E/acceptance/F38.spec.ts` (AC12); modify `DashboardScreen.php`, `AdminAssets.php`, `assets/admin/admin.css`,
`docs/ui/design-system.md` (tour tooltip). The migration copies `lasso_dashboard_tour_done` into `affibloom_tour_done`
(Phase 5's Task 26M owns that copy).

- [ ] **Step 1: Failing tests.** E2E AC12 (first visit shows the tour; Next/Back/Done/Skip; Filters opens; Escape does
  nothing; ended for another user too); unit: `TourState`. Expected: red.
- [ ] **Step 2: State and route** (`TourState`, `TourController`).
- [ ] **Step 3: Script** (`tour.js`, focus kept in the tooltip).
- [ ] **Step 4: Run and look.** Reset the tour option on Local and walk it at 1280 and 375 px, with the keyboard too.
- [ ] **Step 5: Commit** — "Add the Dashboard tour"

---

### Task ON9: Command palette (Ctrl+Shift+L)

**Feature:** F38 (R65-R72; D19; AC13)

**Why:** Lasso's fastest way to any product, from any admin screen and the post editor (deviation 52, bucket A).

**Depends on:** ON1 (Phase 2); F23 D10's `GET /affibloom/v1/products?search=` (built by whichever of G4 / ON9 comes
first; the second reuses it; G4 is in Phase 2); F19's search box for the shortcut badge (Phase 2).

**User action:** Ctrl+Shift+L (⌘⇧L) opens "Search links": ten recent products, typing filters after a pause, arrows,
Enter or click opens the product page, Escape or the shortcut closes, focus returns; "Loading…", "No links found.";
"Shortcut: Ctrl+Shift+L. Press again to close."; not while typing in a field; the Dashboard search box shows the
shortcut.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `assets/admin/palette.js` (dialog from a `<template>`, the shared `trapTab`) | `AdminAssets` on Affibloom pages and editors for users with PRODUCTS |
| `templates/admin/partials/palette.php` | `palette.js` (as a `<template>`) |
| localized labels (`Ctrl+Shift+L` / `⌘⇧L`); `[data-affibloom-shortcut]` in the Dashboard search box | `palette.js` |

**Files:** `assets/admin/palette.js`, `templates/admin/partials/palette.php`; tests
`tests/E2E/acceptance/F38.spec.ts` (AC13); modify `AdminAssets.php`, `templates/admin/dashboard.php`,
`docs/spec/interface.md` (shortcut row), `docs/ui/design-system.md`.

- [ ] **Step 1: Failing tests.** E2E AC13 on the Dashboard and in the block editor (opens, filters, arrows, Enter,
  Escape, focus returns, not while typing in a field). Expected: red.
- [ ] **Step 2: The route, if G4 has not built it yet** (reuse it otherwise).
- [ ] **Step 3: Script and dialog.**
- [ ] **Step 4: Run and look.** On Local, the Dashboard, a product page, Settings and the editor of a post, with the
  keyboard only; Mac label by checking the localized string.
- [ ] **Step 5: Commit** — "Add the Ctrl+Shift+L product search"

---

### Task 39: Documentation and release candidate 1.0.0-rc.1

**Roadmap:** 8.6 · **Feature:** all features (documentation)

**Why:** the owner and the editors have to know what Affibloom does, where Lasso's features went and what it needs
from them; a developer has to be able to build, test and release it. The first release candidate is built from a clean
tree after the reviews are closed.

**Depends on:** Tasks 37A-37D, 38, TL3-TL7, ON5-ON9 done; every feature's "Needs the user" decisions (all decided 2026-11-14, recorded in `open-decisions.md`).

**User action:** review the documents (they are part of gate 7); create a fresh Local site or use staging for the zip
check (step 5); approve the GitHub pre-release.

**What this task creates, and who calls it:**
- **`docs/admin-guide.md`**, with screenshots from the local copy, covering every feature now planned:
  - adding products (Amazon, other merchants, several at once; the Add Link modal and bulk add);
  - the product page: Details, buttons (more than one), custom fields, Locations, URL variants, groups, cloaking and
    the link prefix;
  - placing a box: the block, the shortcodes (Affibloom's and the `[lasso]` compatibility shortcode), display types
    (single, grid, list, table), themes, per-box text and star ratings, the icon library;
  - Groups, Fields, comparison Tables;
  - the Dashboard: search, filters, saved views, quick edit, the command palette, the tour;
  - link health, including what "inconclusive" means for Amazon (30A-30C) and the Alert tabs;
  - **clicks and Google Analytics start from zero at cutover** (decisions E1 and C6: no Lasso figures are imported, and
    the events are renamed): the Dashboard's Clicks count and GA events start from zero; the client's GA reports and any
    GA4 conversions must use the new names `affibloom_primary_click`, `affibloom_secondary_click`, `affibloom_link_click`
    and the parameters `affibloom_title`, `affibloom_href`, `affibloom_text`, `affibloom_badge` (Lasso's "Lasso …" events
    stop at cutover); bot filtering differs from Lasso's cloud (E2);
  - clicks, Locations and the link index; Link Opportunities, and auto-detect / auto-monetize of Amazon links;
  - settings: General (link defaults, link index, performance, permissions, uninstall), Display, Amazon;
  - Import from other plugins and the Lasso migration, legacy block support (no block conversion, no content history: decisions A1, A2);
  - Logs and DB Status; the setup wizard; export and the opt-in uninstall (Task 38);
  - **What needs Amazon credentials (C1):** the product-data features that stay dormant until the Creators API
    credentials are entered (prices, Prime, image refresh, bulk sync, availability alerts, Validate in the wizard),
    and what works without them;
  - **What Lasso features were deliberately not rebuilt:** the Lasso cloud items (licence and account, Analytics and
    Marketplace, the data syncs and the click-count sync, the JS-domain and snippet downloads, Mobile Deep Linking,
    Send Readers to Their Local Store, Commission Upgrades, auto-upgrade of eligible links, the affiliate-programs
    catalogue, Intercom and the other third-party services, brag mode, Lasso's historical click figures and its "Lasso …" GA event names, the review card, Lasso's "Uninstall Lasso" rewrite, the
    update checker), each with its reason. The list is assembled from F11's cron inventory (E15-E28), F37 N3, F38
    N2-N5, C1 N1 and every feature's "Needs the user" with the user's answers, and says where Affibloom's own
    behaviour replaces them;
  - where the familiar Lasso features moved, if anywhere (ADR-8).
- **`docs/developer.md`:**
  - the architecture (layers, composition root);
  - the ADRs;
  - every hook, REST route, `admin-post` action, shortcode, CLI command, cron event, table and option (the contract at
    the end of `00-overview.md`, checked against Task 37A's `docs/reviews/entry-points.md` and Task 38's
    `DataInventory`);
  - how to run every test level on this machine, and which need wp-env (the integration suite);
  - how to make a release.
- **`README.md`:** setup, and links to the two guides.
- **`readme.txt`:** WordPress-style.
- **`languages/affibloom.pot`.**
- **`CHANGELOG.md`:** 1.0.0-rc.1.
- **`.distignore`.**
- **`files` in `package.json`.**

**Files:** create the documents and files above; modify the plugin header, `AFFIBLOOM_VERSION`, `package.json`.

- [ ] **Step 1: Failing check.** Write the check first: a small script or test (`tests/Unit/Docs/DocsCoverageTest.php`)
  that fails if a REST route, shortcode, cron hook or option found in the code (Task 37A's inventory, Task 38's
  inventory) is missing from `docs/developer.md`, and if a screen in `docs/ui/screen-inventory.md` is missing from
  `docs/admin-guide.md`. Expected: red until the documents are written.
- [ ] **Step 2: Write the documents.** You review them; they are part of gate 7. Screenshots from the local copy, with
  placeholder data only (no production data, no credentials in any image).
- [ ] **Step 3: Translations.**

  ```bash
  bash tools/local-wp.sh i18n make-pot . languages/affibloom.pot --exclude=tests,tools,node_modules,vendor
  ```

  (run from the plugin folder; see the path rule in `environment.md`).
- [ ] **Step 4: Version** `1.0.0-rc.1` in the plugin header, `AFFIBLOOM_VERSION` and `package.json`; `package.json`:

  ```json
    "files": [ "affibloom.php", "uninstall.php", "readme.txt", "src", "templates", "assets", "build", "vendor", "languages" ]
  ```

  with the script `"plugin-zip": "wp-scripts plugin-zip"`. (`data/` and `resources/` hold PHP data files that F24 and
  F35 read, so add them to `files` if they exist when this task is worked.)
- [ ] **Step 5: Build the release.**

  ```bash
  rm -rf vendor && composer install --no-dev --optimize-autoloader --classmap-authoritative
  npm ci && npm run build
  npm run plugin-zip
  composer install   # dev tools back for local work
  ```

  Expected: `affibloom.zip`. Unzip it into an empty folder and check that `tests`, `tools`, `docs`, `node_modules` and
  `blocks` (the source; `build` holds the compiled block) are absent, and that `uninstall.php` is present. Install the
  zip on a **fresh Local site** (the user creates an empty site in Local) or on **staging** (uploaded through
  wp-admin). Check that it activates, that the setup wizard opens on the empty site, and that adding a product and a
  box works there.
- [ ] **Step 6: Run and look.** The documents' coverage check green; open each guide page against Local; the fresh-site
  check of step 5 recorded.
- [ ] **Step 7: Tag:** `git tag v1.0.0-rc.1 && git push --tags`. Attach the zip to a GitHub release, marked pre-release
  (the release is public content on the user's repository: the user says go first).
- [ ] **Step 8: Commit** — "Document the plugin and build 1.0.0-rc.1"

---

### Task 39A: UX acceptance

**Roadmap:** 8.7 · **Feature:** all features (UX review; ADR-8)

**Why:** the roadmap's UX review (8.7) had no task. Admin screens must match Lasso's fields, labels, help texts and
workflow steps (the users' editors know Lasso) and carry Affibloom's own look, and the editors must be able to do the
top tasks without guidance.

**Depends on:** every other task of Phases 2-8; `tests/fixtures/baselines/admin/` (Lasso's screenshots),
`docs/ui/design-system.md`, `docs/ui/screen-inventory.md`, `docs/ui/workflows.md`.

**User action:** the user's editors run the top tasks in `docs/ui/workflows.md` (add a product; edit a product; find
and fix a broken link; place a box; see where a product is used) on Local or staging, without guidance, while the
user (or Claude, watching) records where they hesitate. The user signs the result off.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `tests/E2E/ux-screens.spec.ts` (opens every screen and modal of `screen-inventory.md`, saves a screenshot to `.local/ux/`, and fails for a screen with no Lasso baseline and no recorded "no Lasso counterpart") | `npx playwright test ux-screens` |
| `docs/reviews/ux.md` (the table and the editors' results) | the user, Task 39 |

New code appears only as fixes, each with a test.

**Files:** create the two files; modify whatever the findings need.

- [ ] **Step 1: Failing test.** `ux-screens.spec.ts` as above. Expected: red for every screen without a baseline or a
  recorded reason, and for any screen of the inventory that cannot be opened.
- [ ] **Step 2: Compare every admin screen and modal with its Lasso screenshot** (`tests/fixtures/baselines/admin/`):
  Dashboard, the Add Link modal, quick edit, the product page and its tabs, Settings General, Display and Amazon,
  Groups, Fields, Tables, Opportunities, Import, History, Logs, DB Status, the setup wizard, the tour, the palette, the
  Automations popover, notices and education boxes. Per screen, in `ux.md`, record: fields, labels, help texts and
  workflow steps match Lasso's (or the recorded, user-approved deviation and its number in `progress.md`); the looks
  follow `docs/ui/design-system.md` (colours, type, spacing, states) and not Lasso's look.
- [ ] **Step 3: Editors' run.** Each editor runs the five top tasks in `workflows.md` without guidance; record the
  steps taken against Lasso's counted steps, each hesitation, error and question. A task they cannot finish alone is
  a finding.
- [ ] **Step 4: Fix** what the comparison and the run found, each fix with a test; or record it for the user to accept.
- [ ] **Step 5: Run and look.** `npx playwright test ux-screens` green; re-run the editors' tasks that failed; the user
  looks at the screens next to the baselines and signs `ux.md` off.
- [ ] **Step 6: Commit** — "Record the UX acceptance review

  Every admin screen against its Lasso screenshot and the design system; the editors' run of the top tasks."

---

## Exit check

Phase 8 is done when:

- **Reviews:** no open security or accessibility finding (37A, 37C); performance equal or better on every measure,
  against Lasso's real job list (37B); compatibility findings fixed or recorded, with the runs that this machine cannot
  do listed (37D); the four review documents and `docs/reviews/ux.md` are in `docs/reviews/`.
- **F37 (by acceptance criterion):** AC3 and AC4 (Logs, TL3); AC5 (DB Status, TL4); AC6 (Deleting the plugin, Task 38
  with the Uninstall card); AC7 (Permissions, TL6); AC9 (footer version, TL7). (AC1, AC2: Phase 6; AC8: built in Task 1.)
- **F9:** AC16 (the Uninstall card, Task 38); R67 (Permissions, TL6).
- **F38:** AC9 (education boxes, ON5); AC10 (wizard, ON6); AC11 (wizard steps, ON7); AC12 (tour, ON8); AC13 (command
  palette, ON9). (AC1-AC3: Phase 2; AC4-AC6: Phase 2; AC7, AC8: Phase 7; AC14: F9 9E.)
- **Uninstall and export:** the removal of Task 38 covers every store in `DataInventory`, verified by the drift test and
  by the Local run with a backup restored afterwards; the export opens with 263 rows.
- **Documentation and release candidate:** the admin guide, developer guide, README, `readme.txt`, `.pot`,
  `CHANGELOG.md` and `.distignore` are written and reviewed by the user; `affibloom.zip` installs and activates on a
  fresh Local site or staging; tag `v1.0.0-rc.1` pushed.
- **UX acceptance:** every admin screen compared with its Lasso screenshot and the design system; the user's editors run
  the top tasks in `docs/ui/workflows.md` without guidance (39A).
- **The user's sign-off** on the phase.

## Coverage

| R / AC | Task |
|---|---|
| F37 R1-R8, AC3, AC4 | TL3 (R7: none, as Lasso) |
| F37 R9-R13, AC5 | TL4 (R11 dead, not copied) |
| F37 R14-R19, AC6; F9 R63-R65, AC16 | Task 38 (TL5 Uninstall card) (R15-R16 rewriting and cloud: N1, N3; R18 onboarding: F38 ON6; R19 n/a) |
| F37 R31-R35, AC7; F9 R67 | TL6 |
| F37 R37, AC9; F38 R16 (footer text, N6) | TL7 (F38 R16's version text is also ON1) |
| F37 R17, R20-R22, AC1; R26-R27, AC2 | TL1, TL2 (Phase 2) |
| F37 R36, AC8 | built (Task 1) |
| F37 R23, R24, R28, R29, R38, R39 (flag service) | C, never (N3) |
| F37 R39 flagged features | F34 (audit), F30 (AI Text), F11 (lean: not built) |
| F37 R25, R30 | built |
| F37 R40, R41 | F11 / F18 / C1 (other phases) |
| F38 R29-R36, AC9 | ON5 (R37: F9 9K and U3, built help tips) |
| F38 R5, R45-R52, AC10 | ON6 |
| F38 R53-R58, AC11 | ON7 (R53's Commission Upgrades: N7) |
| F38 R59-R64, AC12 | ON8 (R60, R64 Chat and Help steps: N3, N5) |
| F38 R65-R72, AC13 | ON9 |
| F38 R38, R73-R75, AC14 | dropped here: F9 9E owns the guard (ON10 dropped) |
| F38 R1-R4, R6, R10-R14, R16, R77, AC1-AC3; R15, R17-R22, R24, R39, AC4-AC6; R25-R26, AC8; R40-R43, AC7; R44; R76 | ON1, ON2, ON3, ON4, F11, F19 (other phases) |
| F38 R7-R9, R23, R27, R28 | N3-N5 (not built if approved); R23 is the notices of C1, F11, F33 on ON2's region |
| Roadmap 8.1 security review (every screen, route, action, public entry point, cron job) | 37A |
| Roadmap 8.2 performance review (against F11's job list E1-E31) | 37B |
| Roadmap 8.3 accessibility review (group picker, drag-and-move G4/FL5/T9, palette, wizard) | 37C |
| Roadmap 8.4 compatibility review | 37D |
| Roadmap 8.5 uninstall opt-in and export | Task 38 |
| Roadmap 8.6 documentation and release candidate | Task 39 |
| Roadmap 8.7 UX acceptance | 39A |

UNOWNED: none. (F38's analysis notes that Lasso's plugin-conflict code has no owner and needs none unless a conflict
appears; 37D checks for one.)
