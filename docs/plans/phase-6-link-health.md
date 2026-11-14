# Affibloom — Phase 6: Link health — Implementation Plan

> **How to run this plan:** inline, in one working session, task by task, in the order below. Do not hand tasks to subagents. When a step is finished, change its `- [ ]` to `- [x]`.
>
> **Explain as you go:** before each task, say in two or three sentences what the task builds, why it is needed now, and how it will be checked. After the task, say what changed and what the verification showed. No vibe coding: every line written must be one you can explain.
>
> **Shared rules:** the architecture, working method, decisions, global constraints, file structure and API contract are in [`00-overview.md`](00-overview.md). Read it first.

## Status

Planned; gate 4 given by the user 2026-11-14 for every open task. Phase 5's exit check must be met and signed off first.

## Scope and features

- **F37 (part): Tools and diagnostics** — TL1 and TL2 moved to Phase 2 (2026-11-14). TL3-TL7 are Phase 8. `wp-content/plugins/affibloom/docs/features/F37/{requirements,analysis}.md`
- **F11: Link health** — Tasks 30A-30C, 31A-31D, 32A-32E.
  `wp-content/plugins/affibloom/docs/features/F11/{requirements,analysis}.md`
- **F10 (part): Lasso's data, migrated** — Task 26K only (link issues and dismissals, which need F11's table). The
  other F10 tasks are in Phase 5 and Phase 7. `wp-content/plugins/affibloom/docs/features/F10/{requirements,analysis}.md`

## Decisions that change this phase

Decided 2026-11-12; reasons in `docs/decisions/task-6-decisions.md` in the plugin repository. Apply them when the task is worked, and record any code that differs from
the text below as a deviation in `progress.md`.

- U2: the broken-link filter shows the visible text "Broken links (n)" (Task 31B). No "Re-check now" and no
  `POST /products/{id}/check` (U4 rejected; the old Task 32A's U4 section is archived and not built).
- **U9 (the user, 2026-11-13):** Task 31B adds the Dashboard's `Broken links` overview tile (`.affibloom-tile`, danger
  tint, with the count the broken-link filter uses).
- **ADR-8 (the user, 2026-11-12):** the Alert column, its icons and the filter use the Affibloom design system
  (`docs/ui/design-system.md`, Task 9A): its danger/success colours and counter style, not Lasso's. Compare with
  `baselines/admin/dashboard.png` only for the column, its place and the labels.
- **Replaced (2026-11-14):** the old plan's table `affibloom_link_checks`, its `LinkChecker`/`LinkCheckClassifier`
  classes, "404/410 only" alerts and the second-button check are replaced by F11's `affibloom_link_issues`, D4's two
  checks and Lasso's primary-link-only rule (F11 N1-N4 are decided, below).
- **Decided (the user accepted the recommendation, 2026-11-14):** which HTTP answers raise an alert, Lasso's rule (every answer but 200, after the second check)
  or only 404/410 (F11 N1, recommended: Lasso's rule with D4's second check).
- **Decided (the user accepted the recommendation, 2026-11-14):** Extend products (7 Walmart here): plain HTTP check, broken only, no out of stock (F11 N2,
  recommended: the plain HTTP check).
- **Decided (the user accepted the recommendation, 2026-11-14):** Lasso's last Amazon results at cutover (109 out of stock, 3 broken, checked 2026-11-09)
  (F11 N3, recommended: migrate them with D7's line; C1's first check clears or confirms them; Task 26K carries them).
- **Decided (the user accepted the recommendation, 2026-11-14):** the second button: Lasso checks only the primary destination (F11 N4, recommended: Lasso's
  rule, as the 30A draft assumes).
- **Decided (the user, 2026-11-14), E14:** missed scheduled posts (inventory E13: a Lasso cron job, not affiliate-related) are not built; no Task 32F.
- **Decided (the user accepted the recommendation, 2026-11-14), A8:** "Uninstall Lasso" is not built; the Uninstall card holds Task 38's opt-in data removal. Lands in Phase 8 (TL5), not here.
- **Decided (the user, 2026-11-14), C3:** build Lasso's Permissions select (TL6, Phase 8) with the corrected Contributor/Subscriber mapping; default Administrator.
- **Decided (the user, 2026-11-14), B1:** Stop Lasso Analytics, licence activation, updates and the flag service are left out. Touches TL1 only in that activation stores no licence.
- **Decided (the user accepted the recommendation, 2026-11-14):** `SUPPORT_SITES` fixes (F37 N4, recommended: not built).
- F10's "Needs the user" items (conversion, click history, Amazon credentials, the 9 link-less records, duplicate
  destinations, stale Amazon values) do not change Task 26K; they are recorded in Phase 5.

## Tasks in this phase

### F37 (part): Tools and diagnostics

- Tasks TL1 and TL2: moved to Phase 2 (2026-11-14), before the first custom table.

### F11: Link health

- [ ] Task 30A: The daily link check pass
- [ ] Task 30B: The check on product save
- [ ] Task 30C: Amazon products through the product API (dormant until C1)
- [ ] Task 31A: The Alert column in every state, and its sort
- [ ] Task 31B: The Alert filter tabs, counters and the Broken links tile
- [ ] Task 31C: The product page's issue notice
- [ ] Task 31D: Export CSV of issue links
- [ ] Task 32A: `wp affibloom check-links`
- [ ] Task 32B: The broken-links admin notice
- [ ] Task 32C: Settings → General: Performance and Cron
- [ ] Task 32D: The WP-Cron fallback from admin pages
- [ ] Task 32E: The background activity indicator

### F10 (part): Lasso's data, migrated

- [ ] Task 26K: Link issues and dismissals — `wp affibloom migrate --only=issues`

## Cross-phase notes

**TL1 and TL2** moved to Phase 2 on 2026-11-14 (with the coordination point about `affibloom.php`'s uncommitted edit). 30A and later tasks here use TL1's `CronRegistry` and TL2's `Migrator`, `Table` and `Upgrader`.

**Earlier phases (built, or planned before this one):**
- 30A, 30B: `ProductRepository`, `ProductService::create()/update()` (Phase 2, Task 15A); ADR-4 (overview).
- 30C: the product lookup client is Phase 2's C1B (catalogue client; C1A holds the credentials card). Both are dormant
  without credentials, so 30C ships the port, a dormant adapter and a test fake.
- 31A: the Dashboard row (13C) and list route (13D); the grace-days field `days_before_out_of_stock_alerts` (F17,
  Task 10F). All Phase 2, done.
- 31B: filters panel and saved views (13E, Phase 2, done).
- 31C: the product page (14A, Phase 2, done).
- 32C: the Performance card (9H) and the support cards (9L) of Settings → General are Phase 2 tasks still to do; 32C
  runs after them and reuses their controls. **Keys (settled 2026-11-14 in the planning pass):** Affibloom stores
  F9 D3's names in `affibloom_settings` — `performance_mode`, `cpu_threshold`, `cron_interval`,
  `manual_process_limit`, `process_limit`. The names in F11's analysis (`general_performance_mode`,
  `cron_time_interval`, `manually_background_process_limit`, `processes_execute_limit`) are Lasso's source keys, which
  Task 26C maps one to one. 9H/9L build the controls and storage; 32C builds only their effect on the job runner
  (CPU pause, interval floor, execute limit, rescheduling on save).
- 32E: the bulk add job (11C) is the second job in the indicator.
- 26K: needs 26A (Phase 5, products and the Lasso ID map) and the migrate command (Phase 5, 25A); it comes last so F11's
  table exists.

**Inside this phase:** TL2 (Phase 2) builds `Schema\{Migrator, Table}` and the `Upgrader`. Task 30A's draft also lists
`Schema\{Table, Migrator}`; 30A does not create them, it adds its schema step (the `affibloom_link_issues` table) to
TL2's runner. `LinkCheckCron` (30A) registers its hooks with TL1's `CronRegistry` (Phase 2), so deactivation clears them.

**Later phases (what waits, and how it is stubbed):**
- F21 (Phase 7, 35A-35J): R5's "locations only" rule and the "used in content" query. Until then 30A checks all
  published non-Amazon products (D5).
- F22 (Phase 7, O*): the lightbulb slot in the Alert cell (31A) and the "Opportunities (n)" tab (31B) are left as empty
  slots; F22 fills them.
- C1E and C1H (Phase 7): the Amazon daily refresh and the availability hand-off call 30C's recorder
  (`LinkCheckService::record()`, `resolve_out_of_stock()`). 30C ships them first; until C1E/C1H, nothing calls them.
- Task 38 (Phase 8) reuses 31D's CSV writer (`IssueLinksExport`); nothing in this phase waits for it.
- TL3-TL7 (Phase 8): TL4's DB Status lists the tables registered here (`affibloom_link_issues`); TL3's "Link Alerts" log
  is written by F11 once TL3 exists (not built here).
- F33 (Phase 5, IM*): F11's analysis assumed F33 migrates the dismiss meta; in this plan it is Task 26K.
- **UNRESOLVED:** none of the "Depends on" names is missing from the map. One reference was dangling: 30A named "the
  test stub, Phase 6 Step 6" of the old Task 30A. That step is archived, so the stub is now created in 30A itself.

---

> Tasks TL1 (activation and deactivation) and TL2 (upgrade routine, `Schema\{Migrator,Table}`) moved to Phase 2 on 2026-11-14, before the first custom table is created; 30A adds its schema step through TL2's Migrator.

---

### Task 30A: The daily link check pass

**Feature:** F11 (R1-R9, R11-R13, R18, R39; D1-D6, D9-D11, D24; AC1 detection, AC2, AC3-AC5)

**Why:** Lasso checks every product's link once a day; without it a dead merchant link stays in a guide until a reader
finds it.

**Depends on:** F1 (products, `ProductRepository`), `WpCronBackgroundJob`, TL1 (the cron registry, Phase 2) and TL2 (`Migrator`,
`Table`, the upgrade runner, Phase 2), ADR-4 approved at gate 4. The test stub (`affibloom-stub/v1/status/<code>`) is created in
this task (it was Step 6 of the superseded Task 30A; see below).

**User action:** none. At 03:00 site time the pass starts; batches of up to 100 run until every due product is checked;
a failure is re-checked an hour later and only then opens an issue; a blank destination is broken at once; Amazon
products are skipped.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| Version 1's schema step (the `affibloom_link_issues` table), added to TL2's `Infrastructure\Schema\{Table, Migrator}` | `Upgrader` (TL2) |
| `Infrastructure\Tables\LinkIssueTable` (schema, `due()`, `get()`, `save()`, `delete_orphans()`) | `LinkCheckService` |
| `Domain\LinkHealth\{IssueType, LinkIssue, CheckResult, LinkHealthPolicy, IssueDefinitions, LinkIssueRepository}` | the service |
| `Application\LinkHealth\{LinkCheckService, LinkStatusChecker}` | `LinkCheckCron` |
| `Infrastructure\Http\HttpLinkStatusChecker` | the service through the port |
| `Interface\Cron\LinkCheckCron` (`affibloom_daily`, `affibloom_link_check_batch`; registered with TL1's `CronRegistry`, so deactivation clears them) | WP-Cron |
| `tests/E2E/stub/affibloom-stub.php` (a must-use plugin, test environments only; never ships) | the F11 acceptance tests |

**Files:** create the above; `tests/Unit/Domain/LinkHealthPolicyTest.php`, `IssueDefinitionsTest.php`,
`tests/Unit/Application/LinkCheckServiceTest.php`, `tests/Integration/LinkIssueTableTest.php`,
`tests/E2E/acceptance/F11.spec.ts` (AC1-AC5); modify `src/Plugin.php`, `.wp-env.json` (stub mapping).

**The test stub** (kept from the superseded Task 30A, Step 6). `tests/E2E/stub/affibloom-stub.php` is a must-use plugin,
mapped into both wp-env sites through `.wp-env.json` `"mappings": { "wp-content/mu-plugins/affibloom-stub.php":
"./tests/E2E/stub/affibloom-stub.php" }`. It never ships.

```php
<?php
/**
 * Test-only: answers /wp-json/affibloom-stub/v1/status/<code> with that status,
 * and lets the link checker reach localhost.
 *
 * @package Affibloom
 */

add_filter( 'http_request_host_is_external', static fn( $external, $host ) => 'localhost' === $host ? true : $external, 10, 2 );

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'affibloom-stub/v1',
			'/status/(?P<code>\d{3})',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => static fn( WP_REST_Request $r ) => new WP_REST_Response( array( 'stub' => true ), (int) $r['code'] ),
			)
		);
	}
);
```

The acceptance tests use `http://localhost/wp-json/affibloom-stub/v1/status/404` and `/200` as product links and run
`wp cron event run affibloom_link_check_batch`. Step 1 below moves time "through the stub's clock option"; this stub has
no clock option, so add one (an option the checker's clock reads in test environments) when that test is written, and
record it in `progress.md`.

- [ ] **Step 1: Failing tests.** E2E: products pointing at the stub's 200, 404 and blank; `wp cron event run
  affibloom_daily` then `affibloom_link_check_batch`; read the table through `wp eval`; a second failure an hour later
  (time moved through the stub's clock option). Unit: every policy transition. Expected red: no event.
- [ ] **Step 2: Domain** (`IssueType`, `LinkHealthPolicy` with D4, `IssueDefinitions`).
- [ ] **Step 3: Infrastructure** (`Migrator`, `LinkIssueTable` with R6's order and D4's due rule, the HTTP checker with
  D6/D9).
- [ ] **Step 4: Service and cron** (`start_pass` once a day, `run_batch` with 10 s / 100 and the 60 s lock, re-dispatch,
  `affibloom_link_issue_detected`).
- [ ] **Step 5: Run and look.** Checks; on Local, a pass records nothing for external links (D6); stub links give 200 /
  pending / 404.
- [ ] **Step 6: Commit:** "Check product links once a day, in batches, as Lasso does".

---

### Task 30B: The check on product save

**Feature:** F11 (R15; D12; AC6)

**Why:** Lasso checks a link when its product is saved with a new destination, so a fix shows at once.

**Depends on:** 30A; F1's save (`ProductService::create()/update()`, 15A).

**User action:** the editor saves a product with a new link → within the minute the Dashboard shows the tick, or after
the hour's confirmation the red icon.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LinkCheckService::check_product( id )` | `LinkCheckCron` (`affibloom_link_check_product`) |
| `affibloom_link_check_product` [id] event in `LinkCheckCron` | WP-Cron |
| `ProductService` schedules it when the URL changed or the last save is over an hour old (D12) | the save routes |

**Files:** modify `ProductService.php`, `LinkCheckCron.php`, `LinkCheckService.php`, `Plugin.php`; tests F11 **AC6**,
`ProductServiceTest` (scheduled or not).

- [ ] **Step 1: Failing tests** (save with a stub 404, run the event, confirm an hour later).
- [ ] **Step 2: The event and `check_product`.** - [ ] **Step 3: The save's rule.** - [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit:** "Check a product's link after its destination changes".

---

### Task 30C: Amazon products through the product API (dormant until C1)

**Feature:** F11 (R10, R16, R17; D7, D8; AC7)

**Why:** most of this library is Amazon; Lasso asks Amazon's product API, never Amazon's pages. Built now, switched on
by C1's credentials.

**Depends on:** 30A; C1's product lookup client (C1's tasks). Until it exists, this task ships the port, a dormant
adapter (`is_configured()` false) and the test fake.

**User action:** with credentials: an Amazon product Amazon no longer has turns red, one with no stock orange; without
them nothing is checked and the screens say so (D7, shown by 31A-31C).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\LinkHealth\AmazonProductCheck` (port: `is_configured()`, `status( asin, country )` → 200 / 404 / 000 with R16's rule) | `LinkCheckService` |
| `Infrastructure\Amazon\CreatorsProductCheck` (adapter over C1's client) | `Plugin` |
| `LinkCheckService::record( product_id, url, IssueType )` and `resolve_out_of_stock( asin, country )` | C1's daily refresh |

**Files:** the above; tests F11 **AC7** (fake client through a test-only filter in the stub), unit tests for R16.

- [ ] **Step 1: Failing tests.** - [ ] **Step 2: Port and fake.** - [ ] **Step 3: The pass's Amazon branch and the recorder.**
- [ ] **Step 4: Run and look** (without C1: Amazon products untouched). - [ ] **Step 5: Commit:** "Check Amazon products
  through the product API once credentials exist".

---

### Task 31A: The Alert column in every state, and its sort

**Feature:** F11, F19 (R19-R22, R27; D7, D13, D14; AC1 column, AC8, AC9, AC11)

**Why:** editors look at the Alert column first; F19 left it as a placeholder.

**Depends on:** 30A; F19 13C (row), 13D (list route); F17's grace-days field.

**User action:** the Dashboard's Alert cell shows the red unlink, the orange box, the tick, the clock or nothing, with
Lasso's tooltips and the days line; clicking "Alert" sorts asc, desc, off.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\LinkHealth\{AlertState, AlertWindow}` | `LinkIssueQuery` |
| `Application\LinkHealth\LinkIssueQuery::for_products()` | `DashboardController`, `DashboardScreen` |
| `alert` in the row data; `order_by=detection_date` in the list route and repository | `dashboard.js` |
| `templates/admin/partials/alert-cell.php`, CSS and design-system row | `product-row.php` |

**Files:** the above; modify `DashboardController.php`, `DashboardScreen.php`, `PostTypeProductRepository.php`,
`product-row.php`, `dashboard.js`, `admin.css`, `docs/ui/design-system.md`; tests F11 **AC8, AC9, AC11**, AC1's column
step, `AlertStateTest`, `AlertWindowTest`.

- [ ] **Step 1: Failing tests.** - [ ] **Step 2: Domain.** - [ ] **Step 3: Query and route.** - [ ] **Step 4: Cell and sort.**
- [ ] **Step 5: Run and look** (every state, keyboard tooltips, 375 px). - [ ] **Step 6: Commit:** "Show Lasso's Alert column
  in every state".

---

### Task 31B: The Alert filter tabs, counters and the Broken links tile

**Feature:** F11, F19 (R23-R26; D7, D15, D16; AC1 filter, AC10)

**Why:** Lasso's three counters are how editors reach the broken and out-of-stock products.

**Depends on:** 31A; F19 13E (filters panel, saved views).

**User action:** "Broken links (3)" and "Out of stock (109)" above the list; a click filters from page 1, oldest first;
again to clear; counts follow search and filters; the tile shows the broken count; the warm-up clock in the first 24
hours; without C1 the out-of-stock tab carries D7's line.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LinkIssueQuery::counts( search, filters )`, `filter` in the list route, `DashboardUrl`, `SavedViews` | `DashboardController`, `dashboard.js` |
| `templates/admin/partials/alert-tabs.php`, the Broken links tile | `dashboard.php` |

**Files:** the above; tests F11 **AC10**, AC1's filter step; F19 AC13-AC14 rerun.

- [ ] **Step 1: Failing tests.** - [ ] **Step 2: Counts and the filter.** - [ ] **Step 3: Tabs, tile, warm-up.**
- [ ] **Step 4: Run and look.** - [ ] **Step 5: Commit:** "Filter the Dashboard by broken and out-of-stock products".

---

### Task 31C: The product page's issue notice

**Feature:** F11 (R29; D7, D14, D17; AC12)

**Why:** Lasso tells the editor on the product's own page.

**Depends on:** 31A (`LinkIssueQuery`); F1's product page (14A).

**User action:** a red "Your URL is broken." or an orange "This product may be out of stock." bar at the top; × hides it
for 7 days; an Amazon product without C1 shows D7's muted line.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LinkIssueQuery::notices( id )` | `ProductScreen` |
| `POST /affibloom/v1/products/{id}/alerts/dismiss` in `Rest\ProductAlertsController` | `product-save.js` |
| `templates/admin/partials/issue-notice.php` | `product.php` |

**Files:** the above; tests F11 **AC12**.

- [ ] **Step 1: Failing tests.** - [ ] **Step 2: Query and route.** - [ ] **Step 3: Bars and dismissal.** - [ ] **Step 4: Run and
  look.** - [ ] **Step 5: Commit:** "Warn on a product's page when its link is broken or out of stock".

---

### Task 31D: Export CSV of issue links

**Feature:** F11, F19 (R28; D18; AC13)

**Why:** editors fix broken and out-of-stock products in bulk in a spreadsheet, as Lasso's export allows.

**Depends on:** 31B (filters, counts).

**User action:** on either issue tab, **Export CSV** downloads the filtered list; disabled with Lasso's title when
empty; "Exported N links." on success.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\LinkHealth\IssueLinksExport` (24 columns, formula guard, pages of 100) | the route (and Task 38) |
| `GET /affibloom/v1/dashboard/export` in `Rest\DashboardExportController` | the button |
| the button in `dashboard.php`, its states in `dashboard.js` | the Dashboard |

**Files:** the above; tests F11 **AC13**, `IssueLinksExportTest`.

- [ ] **Step 1: Failing tests.** - [ ] **Step 2: Export service.** - [ ] **Step 3: Route and button.** - [ ] **Step 4: Run and look**
  (open in a spreadsheet). - [ ] **Step 5: Commit:** "Export the broken and out-of-stock lists as CSV".

---

### Task 32A: `wp affibloom check-links`

**Feature:** F11 (D19; AC14)

**Why:** a developer checks right after the migration or after fixing a link, without waiting for the night.

**Depends on:** 30A (30C for Amazon).

**User action:** `wp affibloom check-links --product=123` or `--all` prints a table.

| New | Called by |
|---|---|
| `Interface\Cli\CheckLinksCommand` | WP-CLI |
| `LinkCheckService::run_all()` | the command |

**Files:** the above; `Plugin.php`; tests F11 **AC14**.

- [ ] **Step 1: Failing test.** - [ ] **Step 2: Command.** - [ ] **Step 3: Run and look** (all products on Local).
- [ ] **Step 4: Commit:** "Add wp affibloom check-links".

---

### Task 32B: The broken-links admin notice

**Feature:** F11 (R30; D20; AC15)

**Why:** editors who do not open the Dashboard daily still learn about broken links (Phase 6 addition).

**Depends on:** 31B.

**User action:** "3 product links look broken. See broken URLs" on the WordPress Dashboard and Affibloom screens.

| New | Called by |
|---|---|
| `Interface\Admin\BrokenLinksNotice` (transient `affibloom_link_issue_counts`, deleted on each write) | `admin_notices` |

**Files:** the above; `Plugin.php`; tests F11 **AC15**.

- [ ] **Step 1: Failing test.** - [ ] **Step 2: Notice.** - [ ] **Step 3: Run and look.** - [ ] **Step 4: Commit:** "Tell editors
  when product links look broken".

---

### Task 32C: Settings → General: Performance and Cron

**Feature:** F11 (R7 CPU / execute limit, R31-R33; D21; AC16)

**Why:** Lasso lets a busy site slow its background work and support widen cron spacing.

**Depends on:** 30A; the existing Settings → General screen and route.

**User action:** Performance mode and CPU % always; with `&support`, Cron interval, manual limit, Processes Execute
Limit; saving the interval reschedules.

| New | Called by |
|---|---|
| the fields in the settings template and `SettingsController` validation (clamps) | Settings → General |
| `Application\Jobs\{CpuGuard, JobSchedule}`, `Infrastructure\WordPress\WpCpuLoad` | `LinkCheckCron` |

**Files:** the above; tests F11 **AC16**, `CpuGuardTest`.

- [ ] **Step 1: Failing tests.** - [ ] **Step 2: Fields and presets.** - [ ] **Step 3: Effects on the batch.** - [ ] **Step 4:
  Run and look.** - [ ] **Step 5: Commit:** "Let a busy site slow the link check".

---

### Task 32D: The WP-Cron fallback from admin pages

**Feature:** F11 (R34, R35; D22; AC17)

**Why:** on hosts where WP-Cron cannot spawn, Lasso's admin pages run due jobs themselves.

**Depends on:** 32C (manual limit).

**User action:** on a host where WP-Cron cannot spawn, opening any Affibloom screen still runs the due link checks; the
editor sees nothing different.

| New | Called by |
|---|---|
| `POST /affibloom/v1/jobs/run-due` in `Rest\JobsController`; `Application\Jobs\DueJobs`; `WordPress\WpCronHealth` | the poll in `admin.js` |

**Files:** the above; tests F11 **AC17** (spawn test forced to fail by a test filter), `DueJobsTest`.

- [ ] **Step 1: Failing tests.** - [ ] **Step 2: Health and due jobs.** - [ ] **Step 3: The poll.** - [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit:** "Run due jobs from admin pages when WP-Cron cannot".

---

### Task 32E: The background activity indicator

**Feature:** F11 (R36; D23; AC18)

**Why:** Lasso shows what is running in the header.

**Depends on:** 30A; F1's bulk add (a second job to list).

**User action:** while the link check or a bulk add runs, the header shows a running count and "Checking for broken
links (completed/total)" (AC18); when nothing runs it shows Lasso's idle line.

| New | Called by |
|---|---|
| `GET /affibloom/v1/jobs`; `Application\Jobs\JobStatus` | `admin.js` |
| `templates/admin/partials/jobs-indicator.php` | `header.php` |

**Files:** the above; tests F11 **AC18**.

- [ ] **Step 1: Failing test.** - [ ] **Step 2: Status and route.** - [ ] **Step 3: Indicator.** - [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit:** "Show running background jobs in the header".

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

## Exit check

Phase 6 is done when:

- **F37:** AC1 (TL1) and AC2 (TL2) moved to Phase 2 with their tasks.
- **F11:** AC1-AC18 pass (30A: AC1 detection, AC2-AC5; 30B: AC6; 30C: AC7; 31A: AC1 column, AC8, AC9, AC11; 31B: AC1
  filter, AC10; 31C: AC12; 31D: AC13; 32A: AC14; 32B: AC15; 32C: AC16; 32D: AC17; 32E: AC18).
- **F10:** AC18 (26K) passes, and Lasso's fingerprint is unchanged (AC23).
- On Local, a pass records nothing for external links (the Local copy blocks them); the stub's 200, 404 and blank
  links give a tick, a red alert after the second check, and a red alert at once.
- `composer lint`, `composer analyse`, unit and integration tests, and the E2E acceptance files for F11 pass; every
  screen checked at 1280 and 375 px.
- The user has signed Phase 6 off (decisions F11 N1-N5 are decided: E15, E14).

## Coverage

**F37 (part)**

| R / AC | Task |
|---|---|
| R17, R20-R22, AC1 | TL1 (Phase 2; R21 not copied: changes plugin order; R20's snippet: C) |
| R26-R27, AC2 | TL2 (Phase 2) |
| R30 | built (`OptionSettingsRepository` caches) |
| R25 | built (text domain loaded in `Plugin`) |
| R36, AC8 | built (Task 1) |
| R1-R8, AC3, AC4 | TL3 (Phase 8; R7: none, as Lasso) |
| R9-R13, AC5 | TL4 (Phase 8; R11 dead, not copied) |
| R14-R19, AC6 | TL5 + Task 38 (Phase 8; R15-R16: N1, N3; R18 onboarding: F38; R19 n/a) |
| R31-R35, AC7 | TL6 (Phase 8) |
| R37, AC9 | TL7 (Phase 8) |
| R23, R24, R28, R29, R38, R39 (flag service) | not built, never (N3) |
| R39 flagged features | F34 (audit), F30 (AI Text), F11 (lean: not built) |
| R40 | F11 (process names, Automations list: 32E, 32D); SUPPORT_SITES: N4 |
| R41 | F18 (bulk add progress), C1 (bulk sync); `lasso_get_status`: dead, not copied |

(Rows owned by Phase 8 or by other features are listed for completeness; they are not owned by Phase 6.)

**F11**

| R / AC | Task |
|---|---|
| R1-R6, R8, R9, R11-R13, R18 | 30A |
| R7 | 30A (batches, lock, budget); 32C (CPU, execute limit) |
| R10, R16, R17 | 30C |
| R14, R39 | not built (cloud, D24); 30A records the decision |
| R15 | 30B |
| R19-R22, R27 | 31A |
| R23-R26 | 31B |
| R28 | 31D |
| R29 | 31C |
| R30 | 32B |
| R31-R33 | 32C |
| R34, R35 | 32D |
| R36 | 32E |
| R37 | F21 (Phase 7, not F11) |
| R38 | F33 / F21 / F1 / C1 (other features) |
| R40 | analysis (inventory); E13 not built: decision E14 |
| AC1 | 30A (detection), 31A (column), 31B (filter) |
| AC2-AC5 | 30A |
| AC6 | 30B |
| AC7 | 30C |
| AC8, AC9, AC11 | 31A |
| AC10 | 31B |
| AC12 | 31C |
| AC13 | 31D |
| AC14 | 32A |
| AC15 | 32B |
| AC16 | 32C |
| AC17 | 32D |
| AC18 | 32E |

**F10 (part)**

| R / AC | Task |
|---|---|
| R16, AC18 | 26K |
| AC23 (fingerprint, on every write task) | 26K (and 26A, in Phase 5) |
| every other F10 R and AC | Phase 5 and Phase 7 (see their files) |

**Not built (decision E14, the user, 2026-11-14):** inventory E13, missed scheduled posts (F11 N5). Everything else has a task or an
owner named above.
