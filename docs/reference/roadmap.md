# Affiliate Plugin Development Roadmap

> Local copy for Claude Code, exported 2026-11-12 from Claude Docs:
> https://claude.ai/artifact/VbZoaqD53pxA5LqFivfyh1. **Since 2026-11-13 (deviations 14 and 21 in `progress.md`) this
> local copy is the working copy and the Claude Docs original is behind it:** update and tick here. The phase tracker
> below and the plans in `plans/` were rewritten for the full Lasso scope on 2026-11-14.
> One embedded widget was replaced by a text placeholder in the export.

Nov 9, 2026 · @Md Shahin Alam

The replacement plugin will be built in 10 phases, from a tested empty skeleton to a production cutover that retires Lasso. Each phase delivers one or a few of the 12 approved features, and nothing moves to the next phase until its exit criteria are met and you have signed it off. Admin screens, navigation and the editor workflow will match Lasso's, so the team keeps working the way it does now; any change from Lasso's interface waits for your approval in the UI/UX improvements register.

## How to use this roadmap

- **One feature at a time.** Each feature passes through 8 gates (see Per-feature workflow). Coding starts only after you approve that feature's implementation plan.
- **Outside-in.** Every feature starts from what a reader, editor or admin sees: the rendered box, the editor block, the admin screen or the REST endpoint. A failing acceptance test pins that behaviour down first. Work then moves inward through services, the domain model and storage.
- **Status lives here.** The phase tracker below holds each phase's status; the checklists inside each phase hold task status. Both are updated as work completes, never in bulk at the end.
- **No silent gaps.** A task that is deferred, blocked or dropped stays in the list with a note saying why. A phase cannot close with unticked tasks unless each is marked deferred and you have agreed.
- **Scope is everything Lasso does (the user, 2026-11-13).** The first scope was the 12 included features of the feature plan (F1 to F12, plus C1 to C3); the full-scope rule replaced it, and the feature catalogue with status is in `feature-plan.md`. Only what the user excludes (through `../open-decisions.md`) stays out. Feature numbers below refer to the catalogue.
- **Target platform.** Production runs WordPress 7.1.4, PHP 8.3 and MariaDB 10.11. The plugin will require PHP 8.1 and WordPress 6.5 or later, and will be tested on the production versions.

The step-by-step implementation plan for every phase is in `affibloom-implementation-plan.md` in the volleyballvault project. It lives in the repository as `docs/plans/`. It has 42 tasks, each with its request path, files, code, tests and checks. It also records the defaults it assumes for the open decisions below.

## Roadmap at a glance

Phases 0 and 1 build the ground everything else stands on; the six feature phases follow in dependency order. The roadmap itself is awaiting your approval before Phase 0 starts.

| Phase | Name | Features | Depends on | Status | Exit outcome |
| --- | --- | --- | --- | --- | --- |
| 0 | Foundation and tooling | None (infrastructure) | None | Done, signed off 2026-11-12 (CI never ran: progress.md, deviation 10) | Empty plugin activates cleanly; CI runs coding standards, static analysis and tests on every change. |
| 1 | Behaviour specs and baselines | None (specification) | 0 | Done, signed off 2026-11-12 | Approved interface spec, screen mockups, UI/UX decisions and architecture decisions; Lasso output captured as baselines; failing acceptance tests in place. |
| 2 | Settings and product library | F1, F2, F3, F9, F16-F19, F23, F26, F28, F29, F37 (part), F38 (part), C1 (part) | 1 | In progress (Tasks 8-17D done) | Editors can add, edit, search, group and delete Amazon and other products in wp-admin, with every Settings tab, Fields and Groups, on screens laid out like Lasso's in Affibloom's own design. |
| 3 | Product box rendering | F5-F8, F16 (preview), F28/F29 (in the box), F30, F31 (`[affibloom]`), C1 (price, Prime) | 2 | Planned in full 2026-11-14 | Every product and group display type renders as a box matching the Lasso baseline on desktop and mobile, by block markup and by shortcode. |
| 4 | Editor block | F4 | 2, 3 | Planned in full 2026-11-14 | Editors insert and configure every display type from the block editor, the Classic editor and Elementor. |
| 5 | Lasso compatibility and migration | F10, F21 (index), F24, F27, F31 (legacy), F33, F34, F9/F38 (parts) | 2, 3 | Planned in full 2026-11-14 | All 263 products and every other Lasso data family migrated, cloaked links answering, the link index built, and all 250 existing boxes rendering with Lasso switched off; import from other plugins and content history in place. |
| 6 | Link health | F11, F10 (26K) | 2 | Planned in full 2026-11-14 | Scheduled checks flag broken product links in the Dashboard's Alert column, with notices, export and the background-job indicator. |
| 7 | Link index, where-used, clicks, opportunities and tables | F12, F21 (Locations), F22, F25, F35, C2, C3, C1 (part), F19 (sorting) | 3, 5 | Planned in full 2026-11-14 | Clicks are counted and sent to Google Analytics; each product shows where it is used; Opportunities, auto-monetization and comparison tables work; every link is checked for parity. |
| 8 | Hardening and documentation | All; F37 (tools), F38 (wizard, tour, palette) | 4, 5, 6, 7 | Planned in full 2026-11-14 | Security, performance, accessibility and compatibility reviews passed; uninstall and export; admin and developer docs written; UX acceptance done. |
| 9 | Staging rollout and cutover | All | 8 | Planned in full 2026-11-14 | Client signs off on staging; Lasso is off in production; two weeks of monitoring are clean. |

## Phase dependencies

After the product library, work splits into three tracks: link health, the editor block, and the box with migration and clicks. All three must finish before hardening starts.

&#91;embedded content: phase dependencies · 10 phases, build order top to bottom\]

Arrows show only direct dependencies. Phase 5's optional block conversion (task 5.5) also needs Phase 4, but the rest of Phase 5 does not. Phases 4 and 6 can run alongside Phase 5 if there is capacity, but one feature is still in progress at a time.

## Per-feature workflow

Every feature passes the same 8 gates in order, and each gate ends with a written output kept in the repository under `docs/features/F<n>/`. Gate 4 is yours: no code is written before it.

| Gate | What happens | Output |
| --- | --- | --- |
| 1. Requirements | Restate the feature as user stories with acceptance criteria, drawn from the feature plan and the Lasso baseline. For a feature with a screen, name the Lasso screen whose fields, labels, help text and workflow it keeps; its look follows the Affibloom design system (task 2.0). | `requirements.md`: stories, acceptance criteria, out-of-scope notes |
| 2. Technical analysis | Identify dependencies, data touched, WordPress hooks and APIs, edge cases, risks and security concerns. | `analysis.md`: dependency list, risks, decisions needed |
| 3. Implementation plan | List the classes, files, hooks and tests in outside-in order, and estimate the work. | `plan.md`: build order, test list, estimate |
| 4. Approval | You review gates 1 to 3 and approve, request changes or reject. | Approval noted in the roadmap with the date |
| 5. Coding | Write the outermost failing test first, then code inward until it passes. Small commits on a feature branch. | Feature branch, passing tests |
| 6. Testing | Run unit, integration and functional tests, plus visual comparison with Lasso for front-end output and admin screens. | Test report in the pull request |
| 7. Code review | Review against the checklist in Testing and review; fix findings before merging. | Review notes, all findings closed |
| 8. Completion | Merge, update this roadmap, demo the feature on the local site, note anything deferred. | Ticked checklist, short completion note |

### Definition of Done

A feature counts as done only when all of these hold:

- Every acceptance criterion from gate 1 is covered by a passing test.
- Unit, integration and functional suites pass in CI, and coverage of new service classes is at least 80%.
- PHPCS (WordPress Coding Standards) and PHPStan report no new issues.
- All input is sanitised and validated, all output escaped, and every write is protected by a capability check and nonce.
- Front-end output (the product box) matches the Lasso baseline. Admin screens keep Lasso's fields, labels, help text and workflow steps, or the difference is an approved item in the UI/UX improvements register; their look follows the Affibloom design system, not Lasso's.
- User-facing strings are translatable, and admin screens are keyboard-accessible.
- Code review findings are closed, and the roadmap checklist is updated.

## Target architecture

The plugin is split into four layers, and dependencies point inward only. Interfaces call services, services work with domain objects, and only the infrastructure layer touches WordPress storage or the network. Outside-in work follows the same order: each feature starts in the top row and finishes in the bottom one.

| Layer | Responsibility | Planned components |
| --- | --- | --- |
| Interface (outer) | Everything a reader, editor, admin or script touches | Product box block; `[lasso]` compatibility shortcode; admin screens with Lasso's content and workflow (Dashboard, product page with Details and Locations tabs, Add A New Link modal, Settings tabs) in Affibloom's own design, on a shared admin UI kit; REST routes (product search for the block, click recording); WP-CLI commands (migration, link check) |
| Application | Use cases that coordinate the work, with no HTML and no SQL | ProductService, SettingsService, BoxRenderer (builds a view model, then picks a template), AmazonLinkService, LinkAttributeResolver, MigrationService, LinkHealthService, ClickTracker, UsageIndexer |
| Domain | Plain PHP objects and rules, with no WordPress calls | Product, Button, Rating, AffiliateUrl, AmazonAsin, LinkAttributes; repository interfaces |
| Infrastructure | WordPress and outside-world adapters | Product repository (post type and meta), settings repository (one option), custom tables for clicks, link checks and usage; read-only Lasso source adapter; scheduler adapter; HTTP client on `wp_remote_*` |

### Planned code layout

```text
affibloom/
  affibloom.php           bootstrap: version checks, autoload, container
  uninstall.php           removes data only if the admin opted in
  src/
    Interface/            Block/, Shortcode/, Admin/, Rest/, Cli/
    Application/          one service class per use case
    Domain/               entities, value objects, interfaces
    Infrastructure/       repositories, schema migrations, adapters
  templates/              box markup only, no logic
  assets/                 block and front-end source (@wordpress/scripts)
  tests/                  Unit/, Integration/, E2E/, Visual/, fixtures/
  docs/                   decisions/, features/F1 to F12/
```

### Coding standards

- **WordPress conventions:** WordPress Coding Standards via PHPCS, the `affibloom_` prefix for hooks, options and tables, and the `affibloom` text domain. The name Affibloom was confirmed on 11 Nov 2026.
- **Modern PHP:** PSR-4 autoloading through Composer, strict types, typed properties, and constructor injection from one small container. No globals and no static service lookups.
- **One registration point per module:** each module registers its hooks in a single `register()` method, so the plugin's behaviour can be read from a few files.
- **Security by default:** capability checks and nonces on every write; `sanitize_*` on input, `esc_*` on output, `$wpdb->prepare` on every query. The click route is rate-limited and stores no personal data.
- **Schema versioning:** custom tables are created and upgraded through numbered migrations keyed to a stored schema version.
- **Front-end weight:** box CSS and the small click script load only on pages that contain a box, with no third-party requests.

## UI and UX parity

**Decided 2026-11-12 (the user): Lasso's functions and workflow, Affibloom's own design.** The admin screens, navigation and editor workflow keep Lasso's features, fields, labels, help text and order of steps, so the team keeps working the way it does now. Their visual design is Affibloom's own: colours, typography, layout and component styles come from the Affibloom design system (task 2.0, plan Task 9A), not from Lasso's stylesheet, and Lasso's admin look is not copied. The reader-facing product box is not affected: it keeps the site's current box design (ADR-6, Phase 3). The reference for content and workflow is Lasso's own admin templates in the v369 source, plus the screenshots of each screen taken in Phase 1. Screens that belong to excluded features are left out, and fields for excluded features are removed from the screens that stay. Nothing else in content or workflow changes unless you approve it in the register below.

### Screen map

> **Superseded where it conflicts by the full-scope rule (2026-11-13) and the feature requirements; see F38 for navigation.**

Each Lasso screen or element in scope, and what the new plugin does with it.

| Lasso screen or element | In the new plugin | Notes |
| --- | --- | --- |
| Header bar with tabs: Dashboard, Opportunities, Tables, Groups, Analytics, Marketplace | An Affibloom header in the design system's style, showing Dashboard and Settings | The other tabs belong to excluded features; Groups is pending verification (see Open decisions) |
| Dashboard list: Image, Link Name, Groups, Alert, Locations, Clicks, Actions; search; filter icons; row quick edit | Same columns, column order, search, quick edit and broken-link filter, without the Groups column, in Affibloom's table style | The out-of-stock and opportunities filters belong to excluded features |
| "Add A New Link" modal: paste one link, or several on separate lines, then Add Link | Same modal and flow, opening the new product's page | The Marketplace tab and the "Remaining links" licence counter are removed |
| Product page, Details tab | Same field order, labels and help text: Name, Primary Destination URL, Button Text, New Window / Tab, NoFollow / NoIndex, Secondary Destination URL and its button, image, Badge Text, Description, Rating, disclosure | Permalink (link cloaking), Display Theme, Price and the custom field builder are removed with their features |
| Product page, Locations tab: Content, Link with Context, Type, Clicks (30 days) | Same columns | Filled in by F12 |
| Product page, Opportunities and Variants tabs | Left out | Excluded feature, or not in use |
| Settings tabs: General, Display, Amazon, Logs, DB Status | General, Display and Amazon, with only the fields in scope | Licence, cron, performance and Lasso analytics settings, Logs and DB Status serve Lasso's own platform |
| Saving: "Updating Your Site" progress step and "This Product Already Exists" check | Saves without the progress step; the duplicate check stays as a notice linking to the existing product | The progress step exists because Lasso rewrites post content, which is excluded |
| Success toast after saving | A success notice in the design system's style | Wording: see U6 |
| Block editor: the Lasso block opens "Choose a Display Type" (Single, Grid, List, Table, Button, Image, Gallery, AI Text), then product search and Add | Same modal, search and Add button; Single is the only display type | See U1 |
| Help tooltips on almost every field | Same help text | See U3 |

### Design reference

- **Affibloom design system** (decided 2026-11-12; proposed and approved in task 2.0, plan Task 9A): Affibloom's own colour palette, typography, spacing, layout and component styles (header, tabs, buttons, toggles, help tips, tables, modals, notices), written in the plugin's `docs/ui/design-system.md` and approved by the user before any screen is restyled. It meets WCAG 2.2 AA (contrast, visible focus) and sits comfortably inside wp-admin.
- **Lasso's measured values** (`docs/ui/tokens.md`, task 1.7) stay as a record of Lasso's admin only. They are not used for Affibloom's admin screens.
- **Branding:** the header carries Affibloom's own name and logo. Neither Lasso's brand marks nor its look are copied.

### Proposed UI/UX improvements

These keep the same functions and familiar workflow, but differ from Lasso's content or steps in some way. Visual design is not a register item: it follows the Affibloom design system. Each one waits for your decision.

| ID | Proposed change | Why | Size | Status |
| --- | --- | --- | --- | --- |
| U1 | The block opens straight to product search, skipping "Choose a Display Type" | Single is the only display in scope, so the step is an extra click with one choice | Significant: removes a workflow step | **Approved** 2026-11-12; **in question 2026-11-14 (open decision C2)**: with all seven display types in scope F4 N1 recommends withdrawing it |
| U2 | Text labels and counts on the Dashboard filter icons, such as "Broken links (3)" | Lasso's filters are icons explained by hover tooltips; labels are faster to read and work for screen readers | Minor | **Approved** 2026-11-12 |
| U3 | Help text reachable by keyboard and screen readers, with a short line shown under the key fields | Lasso's help sits in hover tooltips; task 1.7 confirmed they open on mouse hover only, not by keyboard or touch | Minor | **Approved** 2026-11-12 |
| U4 | "Re-check now" action on a product flagged as broken | Confirms a fix straight away instead of waiting for the nightly check | Minor addition | **Rejected** 2026-11-12: not in the requirements; nightly check and `wp affibloom check-links --product` cover it |
| U5 | Period selector (7, 30 or 90 days) for the Dashboard's Clicks column | Lasso shows a fixed 30 days in the plugin, with longer views in its cloud Analytics, which is being removed | Significant: changes a screen | **Rejected** 2026-11-12: not in the requirements; Lasso's 30 days kept |
| U6 | Plain success wording, such as "Product saved" | Lasso's toast reads "Yee haw!"; plain wording suits a team tool; position and style stay the same | Minor (wording) | **Approved** 2026-11-12 |
| U7 | Warning when leaving the product page with unsaved changes | Task 1.7 found that Lasso already does this (product page and Settings), so it is parity | None | Built as parity; no approval needed |
| U8 | Detected affiliate links are offered to the editor ("Create products for these links") instead of being added automatically | Lasso adds them without asking (C2); asking first avoids junk products | Minor | **Rejected** 2026-11-12: Lasso adds them automatically and the site uses all 16 it detected |
| U9 | Overview tiles on the Dashboard: Products, Broken links, Clicks (30 days), each an icon, a large number and a label | Lasso's Dashboard shows small icon counters; tiles are part of the Aurora design and easier to scan | Minor (adds content) | **Approved** 2026-11-13 with Aurora; each tile is built by the task that provides its number (13, 31, 34) |
| U10 | A one-line subtitle under each page title | Says what the screen is for; part of the Aurora band | Minor (new wording) | **Approved** 2026-11-13 with Aurora; subtitle only, no eyebrow line |
| U11 | An icon picker on button and badge texts, and a "Button icons" set in Settings → Display (F28) | Lasso has no picker; editors type `<span class="…-icon">` and 17 live texts name undefined classes | Minor (adds a control; stored texts unchanged) | **Asked for by the user** 2026-11-13; source and broken-icon handling chosen by the user; gate 4 given 2026-11-13 for Tasks 14F-14G |
| U12 | Any number of buttons per product (F29): additional buttons after Lasso's secondary, styled like it | Lasso has two; this site uses the second for shoe variants and other shops, and boxes a product twice for a third | Moderate (adds content to boxes that use it; boxes without extra buttons unchanged) | **Asked for by the user** 2026-11-13; count, look and GA event chosen by the user; gate 4 given 2026-11-13 for Task 14H |

Nothing in this register is built until you approve it, and a rejected item keeps Lasso's content and steps (in Affibloom's design). Significant items, meaning anything that adds, removes or reorders a screen or a workflow step, are also shown as a mockup at gate 4 of their feature before coding. Ideas that come up during development are added here as Proposed; they are never built directly.

## Phase 0: Foundation and tooling

Goal: a plugin that does nothing yet, but is built, checked and tested automatically on every change. Depends on: nothing. All work happens locally; production is not touched.

- [ ] **0.1 Repository and workflow**
  - [ ] Git repository with a protected main branch and one branch per feature
  - [ ] Pull-request template containing the code review checklist
  - [ ] `CHANGELOG.md` and semantic version numbering
- [ ] **0.2 Local environment**
  - [ ] wp-env (Docker) pinned to WordPress 7.1 and PHP 8.3, matching production
  - [ ] Import a copy of the production database and uploads with Lasso still active, with user emails and passwords scrubbed
  - [ ] One-command reset script to return the local site to that snapshot
- [ ] **0.3 Tooling**
  - [ ] Composer: PHPCS with WordPress Coding Standards, PHPStan with WordPress stubs, PHPUnit, Brain Monkey
  - [ ] npm: @wordpress/scripts, Jest, Playwright
- [ ] **0.4 Continuous integration**
  - [ ] Pipeline running standards, static analysis, unit, integration and end-to-end tests on every pull request
- [ ] **0.5 Plugin skeleton**
  - [ ] Plugin header, PHP and WordPress version checks with an admin notice
  - [ ] Autoloader, service container and module registry
  - [ ] Activation, deactivation and uninstall handlers (empty for now)
  - [ ] Schema migration runner with no migrations yet; translation loading
- [ ] **0.6 Smoke tests**
  - [ ] Integration test: plugin activates and deactivates without errors
  - [ ] End-to-end test: activating the plugin beside Lasso changes nothing on the front end

Exit criteria: CI is green on the skeleton, and the plugin activates beside Lasso on the local copy with no errors, notices or visible change.

## Phase 1: Behaviour specs and baselines

Goal: pin down every outside behaviour the plugin must reproduce before any feature code exists. This is the outermost layer of the outside-in approach. Depends on: Phase 0. Production is read only.

- [x] **1.1 Inventory current usage** (Task 4, 2026-11-12)
  - [x] Export all 250 Lasso blocks with their attributes, as test fixtures
  - [x] Export the 263 Lasso records and the meta fields actually in use (233 Amazon, 21 other, 9 with no link)
  - [x] List the 1,504 links tied to Lasso records and the posts they sit in (measured: 676 in 39 posts; 1,504 is not in the analysis, see the plugin's `tools/README.md`)
- [x] **1.2 Capture Lasso baselines** (Task 5, 2026-11-12)
  - [x] Catalogue box variants: Amazon, other merchant, second button, badge, star rating, no description (plus Prime; `boxes.json` holds all 250 boxes)
  - [x] Save the rendered HTML of one box per variant
  - [x] Save desktop and mobile screenshots of all 34 buying guides (kept out of git, 287 MB; re-created by one command)
  - [x] Screenshots of every Lasso admin screen, tab, modal and notice in the Screen map, captured on the local copy at desktop and laptop widths (taken from production, Lasso 370, view only, 1920 px: Lasso is unlicensed on Local; editor modal, toast and duplicate notice not captured, wording from source)
- [x] **1.3 Interface specification** (Task 6, approved 2026-11-12)
  - [x] Block name, attributes and editor controls
  - [x] `[lasso]` compatibility shortcode: every attribute found in 1.1 and how each maps (Task 27's map, plus `title_url` and `title_type`)
  - [x] Product fields and settings fields, with validation rules
  - [x] (No drawn mockups: the screen inventory annotates each live Lasso screenshot as kept, left out or changed, and the interface spec fixes every label and selector.) Screen specification: an annotated mockup of every admin screen and modal in the Screen map, using Lasso's layout, labels and help text, with improvements shown only where approved
  - [x] REST routes, WP-CLI commands, and the public hooks and filters
- [x] **1.4 Architecture decision records** (one short document each, approved by you) (approved 2026-11-12, decided under the user's delegation)
  - [x] ADR-1 Product storage: custom post type with meta, or a custom table
  - [x] ADR-2 Lasso records: copy into a new post type with an ID map, or adopt the `lasso-urls` posts in place
  - [x] ADR-3 Existing boxes: keep rendering the old shortcode, convert to the new block, or both
  - [x] ADR-4 Scheduling: WP-Cron or Action Scheduler
  - [x] ADR-5 Click storage: daily totals per product, or one row per click
  - [x] ADR-6 Box styling: port Lasso's Lab CSS, or rewrite to match the baseline
  - [x] ADR-7 Admin UI build: PHP-rendered screens with a small shared UI kit, close to how Lasso builds its own screens, or a React app on @wordpress/components themed to match Lasso
- [ ] **1.5 Acceptance test suite** (Task 7, `ed934a9`, 2026-11-12; open item below)
  - [x] One end-to-end scenario per acceptance criterion for F1 to F12, written from the spec and failing (plus C1–C3)
  - [x] Visual tests comparing each box variant with its baseline (F5 AC1, all 7 variants)
  - [ ] Checks of each admin screen and modal against its Lasso screenshot for fields, order, labels and help text, not for looks (written with each admin screen in Tasks 8–17, as the plan places them; since 2026-11-12 the look follows the Affibloom design system)
- [x] **1.6 Client decisions** (2026-11-12)
  - [x] Record answers to the three open points in the feature plan and adjust affected tasks (all three in scope, the user's decision: Lasso supports each; tasks C1–C3)
- [x] **1.7 UI/UX audit (runs alongside 1.2 and feeds 1.3)**
  - [x] Screen inventory: every Lasso screen, tab, modal and message in scope, with its fields, labels, help text, empty states and error messages
  - [x] Navigation map, and a step-by-step workflow map for each top task: add a product, edit a product, find and fix a broken link, place a box, see where a product is used
  - [x] Design tokens measured from Lasso's admin stylesheet and screenshots: colours, type, spacing, borders, and button, toggle, table and modal styles (since 2026-11-12 a record of Lasso only; Affibloom's admin uses its own design system, task 2.0)
  - [x] Each item in the UI/UX improvements register written up with a before and after, and your decision recorded in the register (2026-11-12)

Exit criteria: specification, screen mockups and ADRs approved; every UI/UX improvement approved or rejected; baselines and Lasso admin screenshots stored in the repository; the acceptance suite runs in CI and fails for the right reasons.

## Phase 2: Settings and product library

**Planned in full 2026-11-14:** tasks in `plans/phase-2-settings-and-product-library.md`; the checklist below is the original outline, kept for history.

Goal: editors can manage every affiliate product in wp-admin. Starts with the shared admin shell and UI kit (2.0), then covers F9, F1, F2 and F3 in that order, because each needs the one before it. Depends on: Phase 1. Inside every feature, tasks run outside-in: gates, interface, application, domain, infrastructure, then tests and review.

- [ ] **2.0 Admin shell and UI kit**
  - [x] Gates 1 to 4 approved, built as ADR-7 decides
  - [x] Affibloom design system proposed and approved by the user: colour palette, typography, spacing, layout and component styles, WCAG 2.2 AA contrast (plan Task 9A; decided 2026-11-12)
  - [ ] Header bar in Affibloom's own design with its name and logo, and tabs for Dashboard and Settings only
  - [ ] Shared components styled with the Affibloom design system: list table with search and filters, tabs, toggle, help tooltip, modal, toast notice, image picker and buttons
  - [ ] Tests and review: component tests; keyboard and screen-reader checks on modal, tabs and toggle; review against the design system for looks and against the Lasso screenshots for fields, labels and order
- [ ] **2.1 Settings and link defaults (F9)**
  - [x] Gates 1 to 4 approved
  - [ ] Interface: Settings screen with Lasso's tabs, order, labels and help text: General (link defaults for new tab, nofollow and sponsored), Display (default button text, default disclosure and the display settings the current box uses) and Amazon (tracking ID for this site); fields for excluded features are left out
  - [ ] Application: SettingsService with defaults and validation
  - [ ] Domain: LinkAttributes value object; resolver applying site defaults, then per-product overrides
  - [ ] Infrastructure: one settings option
  - [ ] Tests and review: resolver unit tests; settings save, nonce and capability tests
- [ ] **2.2 Product library (F1)**
  - [ ] Gates 1 to 4 approved
  - [ ] Interface: Dashboard list with Lasso's columns in Lasso's order (Image, Link Name, Alert, Locations, Clicks, Actions), in the Affibloom design system, with search, filters and row quick edit for name, destination URL and image; Alert, Locations and Clicks are filled in by Phases 6 and 7
  - [ ] Interface: "Add A New Link" modal that takes one link, or several on separate lines, and then opens the new product's page, as in Lasso
  - [ ] Interface: product page with Lasso's Details tab layout and field order: name, primary link and button text, second link and button, new-tab and nofollow overrides, image, badge, description, rating and disclosure; success toast after saving
  - [ ] Application: ProductService for create, update, delete, find and search, with validation
  - [ ] Domain: Product, Button, Rating and AffiliateUrl objects
  - [ ] Infrastructure: product repository as decided in ADR-1, with caching
  - [ ] Tests and review: unit tests for validation; repository integration tests; end-to-end create, edit, search and delete
- [ ] **2.3 Amazon links (F2)**
  - [ ] Gates 1 to 4 approved
  - [ ] Interface: the same "Add A New Link" modal accepts an Amazon link or product ID (ASIN), with no separate Amazon screen, as in Lasso; Prime badge toggle on the Details tab
  - [ ] Application: AmazonLinkService that reads the ASIN from every Amazon link format, builds a clean product link and applies the site's tag, replacing any other tag
  - [ ] Domain: AmazonAsin value object
  - [ ] Tests and review: unit tests across the URL formats found in the 242 existing products
  - [ ] Client point 1 (yes, 2026-11-12; task C1): fetch title, image and Prime through Amazon's Creators API once the client provides credentials, refreshed within Amazon's 24-hour limits; until then a starting name from the link and an Amazon Image link field (decision 11)
- [ ] **2.4 Other merchant links (F3)**
  - [ ] Gates 1 to 4 approved
  - [ ] Any https link accepted as-is, keeping its tracking parameters (such as Impact links)
  - [ ] Tests and review: validation unit tests; end-to-end for a Mizuno-style link

Exit criteria: the Phase 1 acceptance tests for F1, F2, F3 and F9 pass.

## Phase 3: Product box rendering

**Planned in full 2026-11-14:** tasks in `plans/phase-3-product-box-rendering.md`; the checklist below is the original outline, kept for history.

Goal: any product renders as a box that matches today's Lasso box. Covers F5, F6, F7 and F8. Depends on: Phase 2.

- [ ] **3.1 Box design (F5)**
  - [ ] Gates 1 to 4 approved
  - [ ] Interface: box template reproducing the baseline layout (image, title, badge, description, Prime badge, button, disclosure) and the mobile layout, using CSS rather than script where possible
  - [ ] Interface: box CSS and the Prime image load only on pages that render a box
  - [ ] Application: BoxRenderer building a view model from product, per-box values and settings, then rendering the template
  - [ ] Tests and review: visual comparison with every baseline variant; HTML structure snapshots; escaping tests
- [ ] **3.2 Second button (F6)**
  - [ ] Gates 1 to 4 approved
  - [ ] Second button with its own label, link and link attributes; hidden when empty
  - [ ] Tests and review: variant matches the shoe-post baseline
- [ ] **3.3 Per-box text (F7)**
  - [ ] Gates 1 to 4 approved
  - [ ] Badge and description set per box, falling back to the product's values; description allows safe formatting only
  - [ ] Tests and review: fallback unit tests; sanitising tests
- [ ] **3.4 Star rating (F8)**
  - [ ] Gates 1 to 4 approved
  - [ ] Rating from 0 to 5 in half steps, with a text label for screen readers
  - [ ] Tests and review: variant matches the best-black-volleyball-shoes baseline

Exit criteria: every baseline variant matches within the agreed visual tolerance, and pages without a box load no plugin CSS or script.

## Phase 4: Editor block

**Planned in full 2026-11-14:** tasks in `plans/phase-4-editor-block.md`; the checklist below is the original outline, kept for history.

Goal: editors add and configure product boxes in the block editor. Covers F4. Depends on: Phases 2 and 3.

- [ ] **4.1 Block definition (F4)**
  - [ ] Gates 1 to 4 approved
  - [ ] Block registered from `block.json` as a dynamic block rendered on the server by BoxRenderer
  - [ ] Attributes: product, badge, description and any overrides agreed in the Phase 1 spec
- [ ] **4.2 Editor experience**
  - [ ] Insert flow modelled on Lasso's: adding the block opens a modal with product search and an Add button, with the "Choose a Display Type" step kept unless U1 is approved; search runs on a REST route open only to users who can edit posts
  - [ ] Sidebar controls for badge and description; live preview of the real box
  - [ ] Link from the block to the product's edit screen
- [ ] **4.3 Tests and review**
  - [ ] Jest tests for the editor component; REST permission tests
  - [ ] End-to-end: insert block, choose product, set badge, publish, check the front end

Exit criteria: the F4 acceptance tests pass, and a new box made with the block matches the Lasso baseline for the same product.

## Phase 5: Lasso compatibility and migration

**Planned in full 2026-11-14:** tasks in `plans/phase-5-lasso-compatibility-and-migration.md`; the checklist below is the original outline, kept for history.

Goal: switching Lasso off changes nothing a reader sees. Covers F10, the highest-risk feature, so it gets the most testing. Depends on: Phases 2 and 3; task 5.5 also needs Phase 4.

- [ ] **5.1 Gates 1 to 4 approved**, including the ADR-2 and ADR-3 outcomes
- [ ] **5.2 Compatibility rendering (outer)**
  - [ ] `[lasso]` shortcode handler that maps every attribute found in Phase 1 to the new renderer
  - [ ] Missing or deleted products: nothing shown to readers, a visible notice for editors
  - [ ] Old `affiliate-plugin/lasso` blocks open in the editor without an "unsupported block" warning, if ADR-3 keeps them
  - [ ] Handler switches on only when Lasso is inactive, so the two never clash
- [ ] **5.3 Migration service**
  - [ ] Read-only adapter for Lasso's data: records and meta, ratings, Amazon product rows, deleted status
  - [ ] Mapping to the new Product model, with a stored map from old Lasso IDs to new products
  - [ ] Dry run that changes nothing and produces a report; safe to re-run without duplicates
  - [ ] WP-CLI command and an admin screen showing the report
- [ ] **5.4 Data clean-up rules**
  - [ ] Four untitled placeholder records, one soft-deleted record and about 68 unreferenced records: migrate and flag for review
  - [ ] Icon markup inside badge and button text (medal and Amazon icons) carried over safely
- [ ] **5.5 Optional block conversion** (only if ADR-3 chooses it)
  - [ ] Convert the 250 old blocks to the new block, in batches, with a backup and a revert command
  - [ ] Per-post before-and-after report for review
- [ ] **5.6 Tests and review**
  - [ ] Integration tests against fixtures of all 263 records
  - [ ] Visual comparison of all 34 buying guides: Lasso on versus Lasso off with the new plugin
  - [ ] Re-run test proving the migration creates no duplicates

Exit criteria: on the local copy with Lasso deactivated, all 34 guides match their baselines, and the migration report accounts for all 263 records.

## Phase 6: Link health

**Planned in full 2026-11-14:** tasks in `plans/phase-6-link-health.md`; the checklist below is the original outline, kept for history.

Goal: broken product links are caught before readers find them. Covers F11. Depends on: Phase 2.

- [ ] **6.1 Gates 1 to 4 approved**
- [ ] **6.2 Interface**
  - [ ] Alert column and broken-link filter on the Dashboard, as in Lasso; the product's page shows the issue, the last check time and the posts affected; a "Re-check now" action only if U4 is approved
  - [ ] Admin notice showing the count of broken links; WP-CLI command for a manual run
- [ ] **6.3 Application**
  - [ ] LinkHealthService that checks links in small batches and sorts results into OK, broken, redirected or inconclusive
  - [ ] Amazon often blocks automated checks, so a blocked response counts as inconclusive rather than broken, to avoid false alarms
- [ ] **6.4 Infrastructure**
  - [ ] HTTP client with timeouts and per-site rate limits; results table; nightly schedule as decided in ADR-4
- [ ] **6.5 Tests and review**
  - [ ] Unit tests on recorded responses for each result type; integration test of a scheduled run with a mocked network

Exit criteria: a nightly run completes for all products within the agreed time, with no false "broken" results on the current product set.

## Phase 7: Click counts and where-used

**Planned in full 2026-11-14:** tasks in `plans/phase-7-click-counts-and-where-used.md`; the checklist below is the original outline, kept for history.

Goal: see which products get clicked and where each product appears. Covers F12. Depends on: Phase 3, and Phase 5 for the ID map that ties old links to new products.

- [ ] **7.1 Click counts**
  - [ ] Gates 1 to 4 approved
  - [ ] Interface: a small script on pages with product links that reports clicks through `sendBeacon` to a REST route; Clicks column on the Dashboard and clicks per location on the Locations tab, both for the last 30 days as in Lasso; a 7, 30 or 90-day period selector only if U5 is approved
  - [ ] Counts box buttons and the existing in-content links Lasso tied to a product
  - [ ] Application: ClickTracker that validates the product, filters bots and limits repeat clicks, with no IP address or personal data stored
  - [ ] Infrastructure: click table as decided in ADR-5, with a retention period
  - [ ] Client point 3 (yes, 2026-11-12; task C3): send each click to Google Analytics as an event, with Lasso's event names
  - [ ] Tests and review: route permission, rate-limit and bot-filter tests; end-to-end click recorded and shown in the report
- [ ] **7.2 Where-used**
  - [ ] Gates 1 to 4 approved
  - [ ] Interface: Locations tab on the product page with Lasso's columns (Content, Link with Context, Type, Clicks) and the Locations count on the Dashboard; deleting a product that is in use is blocked (Lasso warns with "Hold Up" but allows it; blocking is safer, F12 AC3)
  - [ ] Application: UsageIndexer that reads blocks, shortcodes and product links when a post is saved, with a full rebuild command
  - [ ] Infrastructure: usage table
  - [ ] Tests and review: index updates on save, trash and delete; full rebuild matches the Phase 1 inventory
  - [ ] Client point 2 (yes, 2026-11-12; task C2): affiliate links from known networks in saved posts are added as products, as Lasso does

Exit criteria: clicks on a test page appear in the report, and the where-used index matches the Phase 1 inventory for all 34 guides.

## Phase 8: Hardening and documentation

**Planned in full 2026-11-14:** tasks in `plans/phase-8-hardening-and-documentation.md`; the checklist below is the original outline, kept for history.

Goal: a release candidate that is secure, fast, accessible and documented. Covers all features as a whole. Depends on: Phases 4 to 7.

- [ ] **8.1 Security review**
  - [ ] Audit every input, output, query, REST route, admin action and capability check
  - [ ] Run the WordPress Plugin Check tool and PHPCS security sniffs; fix all findings
- [ ] **8.2 Performance review**
  - [ ] Database queries per page with Query Monitor, on a buying guide and on a page with no boxes
  - [ ] Page speed on three buying guides compared with Lasso: server response time, largest content load, total script weight
  - [ ] Background job load compared with Lasso's 26 scheduled events
- [ ] **8.3 Accessibility review**
  - [ ] Boxes and admin screens checked against WCAG 2.2 AA: keyboard use, labels, colour contrast
  - [ ] UI kit, modals and toasts: focus moves into and back out of modals, Escape closes them, and notices are announced to screen readers
- [ ] **8.4 Compatibility review**
  - [ ] Works with LiteSpeed Cache (including delayed scripts), Rank Math, GenerateBlocks and Mediavine ads
  - [ ] Tested on PHP 8.1 to 8.4 and WordPress 6.5 to 7.1
- [ ] **8.5 Data safety**
  - [ ] Deactivating leaves content and products intact; uninstall removes data only if the admin opted in
  - [ ] Product export to CSV or JSON
- [ ] **8.6 Documentation**
  - [ ] Admin guide: adding products, placing boxes, link health, reports
  - [ ] Developer guide: architecture, ADRs, hooks and filters, running tests
  - [ ] Changelog and version 1.0.0 release candidate
- [ ] **8.7 UX acceptance**
  - [ ] Review of every admin screen and modal against its Lasso screenshot for fields, labels, help text and workflow steps (each difference is an approved register item or fixed), and against the Affibloom design system for looks
  - [ ] Editors complete the 1.7 top tasks in the new plugin without guidance, in no more steps than in Lasso

Exit criteria: no open security or accessibility findings; performance equal to or better than Lasso on every measure; documentation reviewed; UX acceptance passed.

## Phase 9: Staging rollout and cutover

**Planned in full 2026-11-14:** tasks in `plans/phase-9-staging-rollout-and-cutover.md`; the checklist below is the original outline, kept for history.

Goal: replace Lasso on the live site safely, with a tested way back. Depends on: Phase 8.

- [ ] **9.1 Staging rehearsal**
  - [ ] Fresh staging copy of production; install the release candidate
  - [ ] Run the migration dry run, then the real migration; switch Lasso off
  - [ ] Visual comparison of all 34 guides, and a check that every Amazon link carries the right tag
- [ ] **9.2 Client acceptance**
  - [ ] Walkthrough with the client; client adds a product and a box unaided
  - [ ] Written sign-off
- [ ] **9.3 Cutover plan**
  - [ ] Full database and file backup; low-traffic time window
  - [ ] Step list, verification checklist and rollback plan (Lasso's data stays untouched, so reactivating it restores the old state)
- [ ] **9.4 Production cutover**
  - [ ] Run the steps, purge the LiteSpeed cache, run the verification checklist
- [ ] **9.5 Two weeks of monitoring**
  - [ ] PHP error log, link health results and click counts reviewed daily
  - [ ] Client compares Amazon Associates clicks and earnings with the previous period
- [ ] **9.6 Lasso retirement**
  - [ ] Remove the Lasso plugin after sign-off; keep its database tables archived for 90 days, then drop them
  - [ ] Optional: remove leftover `data-lasso-*` attributes from post content, with a backup
  - [ ] Client cancels the Lasso subscription

Exit criteria: Lasso removed from production, two weeks of monitoring with no regressions, and client sign-off on the project.

## Testing and review

Tests are written outside-in: the acceptance test fails first, then each layer gets its own tests as the code moves inward. The five automated levels run in CI on every pull request; the UX check runs at the end of each feature with a screen, and again in Phase 8.

| Level | What it proves | Tools | Examples |
| --- | --- | --- | --- |
| Acceptance and functional | A user-visible behaviour works end to end | Playwright against wp-env | Editor inserts a box and publishes; a guide renders with Lasso off; a click shows in the report |
| Visual regression | Front-end output matches the Lasso baseline | Playwright screenshots, pixel diff with an agreed tolerance | Every box variant; all 34 guides on desktop and mobile. Admin screens are not pixel-compared with Lasso (own design since 2026-11-12) |
| UX check | Editors can do their usual tasks the way they do in Lasso | Task walkthroughs with an editor; keyboard and screen-reader checks | Add a product, place a box, find a broken link, see where a product is used |
| Integration | Code works with real WordPress and the database | PHPUnit with the WordPress test suite | Repositories, migration on fixtures, shortcode, REST routes, scheduled jobs |
| Unit | Each class follows its rules in isolation | PHPUnit with Brain Monkey; Jest for block code | Amazon link parsing, link attribute resolution, view model building, link-check classification |
| Static checks | Style, types and common security mistakes | PHPCS (WordPress Coding Standards), PHPStan, ESLint | Run before every commit and in CI |

### Code review checklist

Used at gate 7 for every pull request:

- **Correctness:** meets every acceptance criterion; edge cases from the analysis are handled and tested.
- **Architecture:** code sits in the right layer; dependencies point inward; no business logic in templates or hooks.
- **Security:** capability checks and nonces on writes; input sanitised, output escaped; queries prepared; REST routes have permission callbacks.
- **Performance:** no queries inside loops; results cached where reused; assets load only where needed; background work batched.
- **Consistency:** naming, prefixes and file layout match the standards; no duplicated logic.
- UI/UX: Lasso's fields, labels, help text and workflow steps; looks follow the Affibloom design system and its shared UI kit; any difference in content or steps is an approved register item.
- **Maintainability:** small focused classes, typed signatures, docblocks on public methods; no dead code.
- **Tests:** new code covered at the right level; tests are readable and independent.
- **Roadmap:** checklist updated, decisions recorded, anything deferred noted with a reason.

## Open decisions and assumptions

Eight decisions are needed before or during the early phases; the rest of the roadmap does not depend on them.

| Decision | Needed by | Notes |
| --- | --- | --- |
| Plugin name, slug and code prefix | Phase 0 | Decided 11 Nov 2026: Affibloom. Slug and prefix: `affibloom` and `affibloom_`. |
| Where the code lives and which CI service runs it | Phase 0 | GitHub with GitHub Actions is assumed. |
| A staging site on the host | Phase 0 for the local copy, Phase 9 for rehearsal | Needed for the cutover rehearsal; a local copy covers everything before that. |
| Who approves gate 4 for user-facing features | Phase 2 | The user. For Phase 1's documents the user delegated the decisions (2026-11-12) to the project records and Lasso research; see `docs/decisions/task-6-decisions.md`. |
| Visual match tolerance against the Lasso baseline | Phase 1 | **Decided 2026-11-12:** `maxDiffPixelRatio` 0.01 for the product box and guides; larger differences are reviewed against the baseline. Admin screens are not pixel-compared (own design). |
| The client's three open points from the feature plan | Phase 1 | **Decided 2026-11-12:** all three in scope (tasks C1–C3). |
| The proposed UI/UX improvements, U1 to U7 | Phase 1 | Approve or reject each one in the UI and UX parity section. A rejected item keeps Lasso's content and steps. |
| Admin visual design | Phase 2 | **Decided 2026-11-12 (the user):** Affibloom's own design system for the admin (colours, typography, layout, styling); Lasso's features, labels, help text and workflow stay; the product box keeps the site's current design. The design system is proposed and approved in plan Task 9A. |
| Lasso Groups | Phase 1 | ~~Checked 2026-11-12: unused (`lasso-cat`: 1 term, 0 assignments), so Groups stay out.~~ **Reversed 2026-11-13 by the full-scope rule: built in full (F23, Tasks G1-G5, Phase 2).** |

Further open decisions (2026-11-14), each with a recommendation: see [`../open-decisions.md`](../open-decisions.md).

Assumptions behind this plan:

- Lasso stays active in production until the Phase 9 cutover, and nothing on the live site changes before then.
- Editors may keep adding products in Lasso during development, so the local copy is refreshed before Phase 5 and the migration is re-run during Phase 9.
- Time estimates are made per feature at gate 3, once each feature's analysis is done, rather than for the whole project up front.
