# Affibloom Implementation Plan — Overview (shared by every phase)

> Local working copy for Claude Code. Generated on 2026-11-12 from the claude.ai project doc
> `claude/affibloom-implementation-plan.md` (the combined plan). The per-phase task text lives in
> `phase-<n>-*.md` next to this file. Read this overview and the current phase file at the start of
> every session. Tick steps (`- [ ]` → `- [x]`) in the phase file, not here.

> **How to run this plan:** inline, in one working session, task by task, in the order below. Do not hand tasks to subagents. When a step is finished, change its `- [ ]` to `- [x]`.
>
> **Explain as you go:** before each task, say in two or three sentences what the task builds, why it is needed now, and how it will be checked. After the task, say what changed and what the verification showed. No vibe coding: every line written must be one you can explain.

**Goal:** build `affibloom`, a WordPress plugin that replaces Lasso on volleyballvault.com. **Scope (the user, 2026-11-13, re-planned in full 2026-11-14): everything Lasso does**, except what the user has excluded or will exclude through [`../open-decisions.md`](../open-decisions.md). The first scope was the 12 features F1–F12 plus C1–C3 (the feature plan); it grew by the full-scope rule. The feature folders (`docs/features/<ID>/` in the plugin; the catalogue with status is in `../reference/feature-plan.md`): F1 product library, F2 Amazon links, F3 other merchant links, F4 editors (block, display modal, Classic, Elementor), F5–F8 the product box (single display, buttons, overrides, rating), F9 Settings → General, F10 Lasso migration and compatibility, F11 link health, F12 clicks and where used, F16 Display settings, F17 Amazon settings, F18 Add A New Link, F19 Dashboard list, F21 content link index, F22 Opportunities, F23 Groups, F24 link cloaking and redirects, F25 comparison tables, F26 Fields, F27 URL variants, F28 button icons, F29 any number of buttons, F30 display types (grid, list, gallery, button, image, SiteStripe, AI text), F31 shortcodes (`[affibloom]`, `[lasso]` and the competitors'), F33 import from other plugins, F34 content history and repair tools, F35 auto-monetization (all 32 networks), F37 tools and diagnostics, F38 admin shell, C1 Amazon product data, C2 affiliate link detection, C3 Google Analytics events. Compatibility with the 250 existing Lasso boxes and migration of the 263 Lasso records remain the core. Admin screens keep Lasso's fields, labels, help text and workflows, in Affibloom's own visual design: colours, typography, layout and styling come from the Affibloom design system (decided 2026-11-12, Task 9A), not from Lasso. The reader-facing product box keeps the site's current box design (see the roadmap's "UI and UX parity" section).

**Architecture:** four layers, with dependencies pointing inward only.

- **Interface:** everything WordPress or a person touches: admin screens, REST routes, the block, the `[lasso]` compatibility shortcode, WP-CLI commands, cron hooks and the click script.
- **Application:** one service per use case. Services contain no HTML and no SQL.
- **Domain:** plain PHP objects and rules, with no WordPress calls, so they can be unit-tested without WordPress.
- **Infrastructure:** the custom post type and post meta, the settings option, custom tables, the read-only Lasso source, and the HTTP client.

Products are posts of a private custom post type, `affibloom_product`, with their fields in post meta. Pages are rendered in PHP. JavaScript is only used where Lasso uses it: modals, quick edit, saving without a reload, and the block editor. One composition root, `src/Plugin.php`, builds every object and passes its collaborators in through the constructor.

**Tech stack:**

- Plugin: PHP 8.3+, WordPress 7.1.4+ (decision F7, the user, 2026-11-14; Task 2X0). Production runs WordPress 7.1.4, PHP 8.3 and MariaDB 10.11.
- Block: `@wordpress/scripts`.
- Admin JavaScript: plain browser scripts using the `wp.apiFetch` global, with no build step.
- Tests: PHPUnit 9.6 with Brain Monkey for unit tests; the WordPress test suite for integration tests; Playwright for end-to-end and visual tests.
- Local environment: Local (Local by Flywheel) with the helper `bash tools/local-wp.sh` (deviation 7), not wp-env.

**Testing:** tests are part of every task, written outside-in. The task's outermost test is written first and fails; unit tests follow as the code moves inward. Each task also ends with a check by hand. This differs from the reference plan this one is modelled on, which dropped automated tests: the approved roadmap requires them.

**Working method: outside-in.** These rules apply to every task.

1. **Each task starts from a user action or an entry point:** a screen, a REST route, the block, the shortcode, a WP-CLI command or a cron event. "Create the tables" is never a task.
2. **Each task starts with its request path:** which hook, controller, service, domain object and storage the request passes through, and what comes back.
3. **Steps follow the request path:** test → entry point → controller → what the controller asks for (service, domain, repository, table) → template or response. Code in the middle of a task may not run yet; that is expected.
4. **Nothing is created before it has a caller.** No method, class, meta key, column or setting is added "because we will need it later". Each task has a table of what it creates and who calls it.
5. **Shared code is extracted on its second use,** never on the first.
6. **A task ends when its entry point has been run and the result seen:** the screen opened, the route called, the command run.
7. **One task covers one entry point.** Each task is committed on its own, so every step can be understood and checked separately.

**How this plan fits the roadmap's 8 gates:** each task section is gate 3, the implementation plan. Gates 1 and 2 (requirements and technical analysis) are written into `docs/features/F<n>/` in Task 6. Gate 4 is your approval of a feature's tasks before its first task starts. Steps carry out gates 5 (coding) and 6 (testing). Gate 7 (code review) is the pull request against the checklist in `.github/pull_request_template.md`. Gate 8 (completion) is ticking the roadmap.

**Sources:**

- Roadmap: *Affiliate Plugin Development Roadmap* (Claude Docs), including "UI and UX parity" and the UI/UX improvements register.
- Feature plan: *VolleyballVault Affiliate Plugin — Feature Plan* (Claude Docs).
- Analysis: *Lasso Plugin Analysis — VolleyballVault* (Claude Docs).
- Lasso v369 source: `D:\lasso\lasso`, read only. The files this plan relies on:
  - `admin/views/displays/single.php` and `layout-3-box.php`: the Lab theme box.
  - `classes/class-lasso-affiliate-link.php`: how a record becomes a box.
  - `libraries/class-lasso-url.php`: link attributes.
  - `classes/class-lasso-shortcode.php`: `[lasso]` attributes.
  - `models/*`: the Lasso tables.

## Decisions this plan assumes

The roadmap leaves several decisions open. This plan is written on the defaults below, so the work can proceed in order. Each default is recorded as an ADR in Task 6. If you decide differently, change the listed tasks before starting them.

| Decision | Default used in this plan | Tasks affected if changed |
|---|---|---|
| Plugin name, slug, prefix | Decided 11 Nov 2026: `Affibloom`; slug and text domain `affibloom`; prefix `affibloom_`; namespace `Affibloom\` | All |
| ADR-1 Product storage | Private post type `affibloom_product` with post meta | 11–17, 25–26 |
| ADR-2 Lasso records | Copied into new products; old ID kept in `_affibloom_lasso_id`; Lasso data left untouched for rollback | 25–29, 33, 35 |
| ADR-3 Existing boxes | **Revised 2026-11-14 (the user, A1):** legacy blocks are not converted and no post content is edited. Affibloom registers `affiliate-plugin/lasso` itself (Task ED6) and `[lasso]` keeps rendering through a compatibility handler (Task 27A) | 27, ED6, 41 |
| ADR-4 Scheduling | WP-Cron, a daily event plus chained batches, guarded by a lock | 30, 33 |
| ADR-5 Click storage | Daily totals per product, post and button; no IP address or other personal data | 33–36 |
| ADR-6 Box styling (**confirmed 2026-11-14 by the user, C1:** own class names and stylesheet; the site's 33 `.lasso-*` Additional CSS rules are translated at cutover, Task 41) | New stylesheet and `affibloom-` class names, tuned to the Lasso screenshots; no Lasso CSS copied | 18–22 |
| ADR-7 Admin UI build | PHP-rendered screens, a small CSS kit in the Affibloom design system (not Lasso's colours, since 2026-11-12), plain JavaScript for modals and saving | 8–17, 31, 36 |
| ADR-8 Admin visual design | **Decided 2026-11-12 (the user):** Affibloom's own design system for every admin screen, modal, notice and the block's editor UI: own colour palette, typography, layout and component styles. Lasso's features, fields, labels, help text and workflow steps stay. The front-end product box is not affected (ADR-6). The design system is proposed and approved in Task 9A before Task 10 | 8–17 (8–9 restyled in 9A), 23–24, 31, 34, 36, 37 |
| Service wiring | One composition root with constructor injection (`src/Plugin.php`), not a container library | All |
| Who may use the admin | **Decided 2026-11-12:** Products and Settings: `manage_options` (Administrators), as Lasso's Permissions on the live site (4 administrators, no other roles). Block product search: `edit_posts`, as Lasso's block. **Decided 2026-11-14 (the user, C3):** build Lasso's Permissions role select (F9 N3, Task TL6), default Administrator, fixing Lasso's Contributor/Subscriber swap; the line above becomes the default | 8–17, 23, TL6 |
| UI/UX improvements U1–U8 | **Decided 2026-11-12:** U1, U2, U3, U6 approved; U4, U5, U8 rejected; U7 is parity. **U1 is withdrawn** (the user, 2026-11-14, C2): Lasso's "Choose a Display Type" step is restored | 23, 31, 8–17 and 37, 11 and 15 |
| Client point 1: Amazon title and image fetch | **Yes** (the user, 2026-11-12), as an upgrade: through Amazon's Creators API once the client provides credentials. Without them: existing products keep Lasso's fetched data (migration), new ones get a starting name from the link's slug and an Amazon Image link field (decision 11) | 10, 12, 14, 26; task C1 |
| Client point 2: affiliate link auto-detect | **Yes** (2026-11-12): automatic, as in Lasso; "ask first" is register item U8. ~~Two networks (Impact, Post Affiliate Pro)~~ **Overtaken: all 32 networks Lasso detects (F35, Tasks C2A-C2C)** | 9, 26, 35; C2A-C2C, AM1-AM6 |
| Client point 3: Google Analytics click events | **Yes** (2026-11-12), with Lasso's GA controls built in full and gtag.js loaded (C6, 2026-11-14). **Event names revised 2026-11-14 (the user): Affibloom's own** (`affibloom_primary_click`, `affibloom_secondary_click`, `affibloom_link_click`; parameters `affibloom_title`, `affibloom_href`, `affibloom_text`, `affibloom_badge`; GA4 snake_case); no backward compatibility with Lasso's GA reports; GA data starts from zero | 9, 26, 33; task C3 |
| Lasso Groups | ~~Not built~~ **Overtaken: built in full (F23, Tasks G1-G5)** by the full-scope rule (reopened 2026-11-13, deviation 52; confirmed unused on this site: 1 term, 0 assignments) | G1-G5, 13, 14, 16 |
| **No Lasso cloud; credentials later** | **Decided 2026-11-13 (the user):** everything that needs neither API credentials nor Lasso's cloud is built now; what needs the Amazon Creators API (C1) is written now and ships dormant; **no code is written that runs on Lasso's cloud**. Buckets and sequence: `plans/archive/dashboard-completion-plan.md` (superseded 2026-11-14 by the phase files) | All from Phase 2 on |
| Smart CSS Loading | **Decided 2026-11-14 (the user, C5):** on by default for a fresh install, and the migration switches it on ("box CSS only where a box shows"); F9 N5's "import it off" is not taken | 9G, 18C, 26C |
| **Open decisions** | **Everything that would drop, replace or simplify part of Lasso, or reverse an earlier decision, is listed for the user in [`../open-decisions.md`](../open-decisions.md) (2026-11-14).** **Answered and accepted 2026-11-14 (gate 4 given for the whole plan):** each phase file records the decision as "Decided (the user, 2026-11-14)". **Standing rule: Affibloom works with no Lasso dependency and no Lasso branding** (F4, B9). Clicks and GA start from zero (E1) | See that file |

## Global constraints

- **Paths:**
  - Plugin repository: `wp-content/plugins/affibloom` inside the Local site (`C:\Users\Rupash\Local Sites\volleyballvault\app\public`; deviation 1), not `D:\affibloom`.
  - Lasso source: `D:\lasso\lasso`. Read only. Never write to it (Lasso writes logs into its own folder). The live Lasso in the Local site (`wp-content/plugins/lasso/`) is never edited either.
  - Local copy of production: `D:\vv-dev\` (gitignored backup files, database dump, copied plugins and theme).
- **Production is read only** until Task 42. Every script in Phases 0–8 runs against the local copy or staging.
- **Versions:** PHP `>=8.3`, WordPress `>=7.1.4` (decision F7, 2026-11-14; Task 2X0 aligned the runtime check, Composer, PHPCS and PHPStan). The local environment matches production: WordPress 7.1.4, PHP 8.3.
- **Names:**
  - Plugin `Affibloom`; text domain `affibloom`; PHP namespace `Affibloom\`.
  - Prefix `affibloom_` for hooks, options, meta and tables; constants start with `AFFIBLOOM_`; CSS classes start with `affibloom-`.
  - Post type `affibloom_product`; block `affibloom/product-box`; REST namespace `affibloom/v1`; WP-CLI command `wp affibloom`.
- **Admin pages:**
  - `admin.php?page=affibloom`: Dashboard.
  - `admin.php?page=affibloom-product&id=<id>[&tab=locations]`: product page.
  - `admin.php?page=affibloom-settings&tab=general|display|amazon`: Settings (the General tab also holds the Link Index, Google Analytics, Performance, Notifications, Uninstall and Permissions cards; `&support=1` shows the Support view).
  - Planned pages, as their feature analyses name them: `affibloom-groups` and `affibloom-group&id=` (F23); `affibloom-fields` and `affibloom-field&id=` (F26); `affibloom-tables` and `affibloom-table&id=` (F25); `affibloom-opportunities[&tab=links|domains|domain|content|post]` (F22); `affibloom-import` (F33); `affibloom-history[&post=&kind=]` (F34); `affibloom-logs` and `affibloom-db-status` (F37); `affibloom-setup` (F38 wizard); `affibloom-migrate` (F10); the product page's tabs `locations`, `variants`, `opportunities`.
- **REST responses:**
  - Success: `{ "data": … }`.
  - Errors: a `WP_Error` with an `affibloom_*` code, which WordPress returns as `{ "code", "message", "data": { "status" } }`. Field errors go in `data.fields: { <field>: <message> }`.
- **Capabilities:** constants in `src/Interface/Admin/Capabilities.php`: `PRODUCTS = 'manage_options'`, `SETTINGS = 'manage_options'`, `INSERT_BOXES = 'edit_posts'` (decided in Task 6).
- **Amazon:** the site's tracking ID is `vbvault-20`. Amazon product links follow the site's tag rule of F2 (a whitelisted tag is kept; see the F2 requirements and F17's whitelist), not "always replace".
- **Link attributes** follow Lasso's rules (`class-lasso-affiliate-link.php`):
  - Primary button: `rel` is `nofollow` if NoFollow is on, plus `noopener` if New Window is on, plus `sponsored` if Sponsored is on. `target` is `_blank` or `_self`.
  - Secondary button: the same, except that `sponsored` is added only when the secondary NoFollow is also on.
- **Assets:** box CSS and the click script load only on pages that show a box or a product link. Admin CSS and JavaScript load only on the plugin's own screens.
- **Production data in git:** never commit it. `D:\vv-dev\` and `.local/` are gitignored. Fixtures exported in Task 4 contain only product data, which is public on the site, and no user data.
- **Commits:** one per task, in the imperative mood, with a short body saying why.

---

## File structure

This is a short tree of what exists in the repository today (2026-11-14), then one line per area still to be built,
pointing at the feature analysis whose "Layers" section names its classes. No class names are listed here that are not
in the code or in an analysis.

```
wp-content/plugins/affibloom/
├── affibloom.php, composer.json, package.json, phpcs.xml.dist, phpstan.neon.dist, phpunit*.xml.dist, playwright.config.ts
├── src/
│   ├── Plugin.php                 composition root
│   ├── Module.php                 "something that hooks into WordPress"
│   ├── Interface/                 Admin/ (screens, controllers, menu, assets), Rest/ (controllers, resources),
│   │                              Cron/, IconStyles.php, Messages.php, Template.php
│   ├── Application/               Dashboard/, Display/, Fields/, Icons/, Migration/, Products/, Settings/
│   ├── Domain/                    Amazon/, Dashboard/, Display/, Fields/, Icons/, Migration/, Product/, Settings/, InvalidField.php
│   └── Infrastructure/            Http/, Lasso/, WordPress/
├── templates/admin/               dashboard.php, product.php, settings*.php, partials/
├── assets/admin/                  admin.css, admin.js and the per-screen scripts
├── tools/                         inventory, baselines, seed and local helper scripts; never shipped
├── tests/                         Unit/, Integration/, E2E/, fixtures/
└── docs/                          decisions/, spec/, ui/, features/<ID>/{requirements,analysis}.md
```

Planned areas, each described in its feature's `analysis.md` ("Layers" section):

| Area | Where it is planned |
|---|---|
| Product box engine, front-end templates, box assets, schema | F5 (`Box`, `Front`, `templates/front`, `assets/front`) |
| Display types and group displays | F30 (`DisplayHtml`, grid, list, gallery, button, image, SiteStripe, AI text) |
| Shortcodes, `[lasso]` and the competitors' (Legacy) | F31 |
| Editors: block, display modal, Classic, Elementor, AMP | F4 (`Editor`, `blocks/product-box`, `assets/editor`) |
| Groups, Fields, URL variants | F23, F26, F27 |
| Cloaking, redirects, old addresses | F24 (`Cloak`, `Front\CloakedLinkRedirect`) |
| Comparison tables | F25 (`Table`, `Tables`, front `table*.php`) |
| Content link index, render-time link attributes | F21 (`LinkIndex`, `Schema\Migrator`, `Tables\LinkLocationsTable`) |
| Opportunities | F22 |
| Auto-monetization, network registry | F35 (and C2) |
| Amazon product data (Creators API), refresh, bulk sync | C1 |
| Link health, background jobs | F11 |
| Clicks, Google Analytics events | F12, C3 |
| Lasso migration, legacy block conversion | F10 (`Migration`, `Cli`) |
| Import from other plugins | F33 (`Import`, `Infrastructure\Import`) |
| Content history and repair tools | F34 |
| Lifecycle, upgrade, logs, DB status, permissions | F37 |
| Navigation, notices, jobs indicator, wizard, tour, command palette | F38 |

Each file has one job. Controllers stay thin. Templates contain no logic beyond loops and conditionals over a prepared view array.

---

## Task index

One table per phase: every task, the feature that owns it, and its status (verified against the code 2026-11-14). The
phase file holds each task's text and its tick boxes; the order inside a phase is the order of the tables. Done tasks
keep their commit hash where `progress.md` or the Phase 2 file records one (Tasks 1-3 name none).


### Phase 0: Foundation and tooling

| Task | Feature | Status |
|---|---|---|
| 1 | — | done |
| 2 | — | done |
| 3 | — | done |

### Phase 1: Behaviour specs and baselines

| Task | Feature | Status |
|---|---|---|
| 4 | — | done `7d9d00e` |
| 5 | — | done `ebcbb62` |
| 6 | — | done `b0e7abc` |
| 7 | F1-F12 | done `ed934a9` |

### Phase 2: Settings and product library

| Task | Feature | Status |
|---|---|---|
| 8 | F38 | done `aa4da3c` |
| 9 | F9 | done `8035d66` |
| 9A | — | done `6ec0f2e` |
| 10 | F16 | superseded |
| 10A | F16 | done `a6ea427` |
| 10B | F16 | done `87e6ccc` |
| 10C | F16 | done `a978948` |
| 10E | F16 | done `51a4584` |
| 10F | F17 | done `87620f1` |
| 10D | F16 | open (moved to Phase 3) |
| 11 | F18 | superseded |
| 11A | F18 | done `597fd4a` |
| 11B | F18 | done `8f41d04` |
| 11C | F18 | done `b89ae01` |
| 11D | F18 | done `f1067d7` |
| 11E | F18 | done `f830fea` |
| 12 | F2 | superseded |
| 12A | F2 | done `5dcfa7a` |
| 12B | F2 | done `b1db8c5` |
| 12C | F2 | done `a4b3c52` |
| 12D | F2 | done `5d1d634` |
| 12E | F2 | done `e839339` |
| 13 | F19 | superseded |
| 13A | F19 | done `99a4fba` |
| 13B | F19 | done `ad508f4` |
| 13C | F19 | done `b2ab7b3` |
| 13D | F19 | done `f32c89f` |
| 13E | F19 | done `00dc1af` |
| 14 | F1 | superseded |
| 14A | F1 | done `b3686ab` |
| 14F | F28 | done `7c69cf7` |
| 14G | F28 | done `c799ff5` |
| 14H | F29 | done `b50ace2` |
| 14B | F1 | done `9640685` |
| 14C | F1 | done `0a24290` |
| 14D | F1 | done `0ee6801` |
| 14E | F1 | open (moved to Phase 3) |
| 15 | F1 | superseded |
| 15A | F1 | done `59ee8da` |
| 15B | F1 | done `7e77a89` |
| 15C | F1 | done `0da4b16` |
| 15D | F1 | done `b3202d7` |
| 16 | F1 | superseded |
| 16A | F1 | done `852cb00` |
| 16B | F1 | done `674c24c` |
| 16C | F1 | done `6471087` |
| 17 | F1 | superseded |
| 17A | F1 | done `d7a0493` |
| 17B | F1, F19 | done `f61e12c` |
| 17C | F1 | done `c86378f` |
| 17D | F1, F18 | done `82b6015` |
| 2X0 | F37 | done (decision F7) |
| 2X1 | F1 | open |
| 2X2 | F1 | open |
| 2X3 | F16 | open |
| 2X4 | F2 | open |
| 2X5 | F18 | open |
| 2X6 | F16 | open (decision B9) |
| G1 | F23 | open |
| G2 | F23 | open |
| G3 | F23 | open |
| G4 | F23 | open |
| G5 | F23 | open |
| 9B | F9 | open |
| 9E | F9 | open |
| 9C | F9, F18 | open |
| 9D | F9 | open |
| 9F | F9 | open |
| 9G | F9 | open |
| 9H | F9 | open |
| 9I | F9 | open |
| 9K | F9 | open |
| 9L | F9 | open |
| FL1 | F26 | open |
| FL2 | F26 | open |
| FL3 | F26 | open |
| FL4 | F26 | open |
| FL5 | F26, F1 | open |
| TL1 | F37 | open |
| TL2 | F37 | open |
| 13F | F19 | open |
| 13G | F19 | open |
| 13H | F19 | open |
| 13I | F19 | open |
| 13J | F19 | open |
| ON1 | F38 | open |
| ON2 | F38 | open |
| C1A | C1 | open |
| C1B | C1 | open |
| C1C | C1 | open |
| C1D | C1 | open |
| C1I | C1 | open |
| C1J | C1 | open |
| C1K | C1 | open |
| C1L | C1 | open |
| C1M | C1 | open |
| C1N | C1 | open |
| C1O | C1 | open |
| C1P | C1 | open |

### Phase 3: Product displays

| Task | Feature | Status |
|---|---|---|
| 18A | F5 | open |
| 18B | F5 | open |
| 18C | F5 | open |
| 18D | F5 | open |
| 18E | F5 | open |
| 18F | F5 | open |
| 18G | F5 | open |
| 18H | F5 | open |
| 18I | F5 | open |
| 19A | F5 | open |
| 20A | F5 | open |
| 21A | F5 | open |
| 18J | F5 | open |
| 22A | F5 | open |
| 22B | F5 | open |
| 22C | F5 | open |
| 18K | F5 | open |
| 20B | F5 | open |
| 20C | F5 | open |
| 21B | F5 | open |
| 21C | F5 | open |
| 20D | F5 | open |
| 22D | F5 | dropped (decision B9) |
| 22E | F5 | open |
| 10D | F16 | open |
| 14E | F1 | open |
| C1F | C1 | open |
| C1G | C1 | open |
| 18S1 | F31 | open |
| 18S2 | F31 | open |
| 18S3 | F31 | open |
| 18S4 | F31 | open |
| 18S5 | F31 | open |
| 18S6 | F31 | open |
| 18S9 | F31 | open |
| 18S10 | F31 | open |
| 18S11 | F31 | open |
| 18S14 | F31 | open |
| DT1 | F30 | open |
| DT2 | F30 | open |
| DT3 | F30 | open |
| DT4 | F30 | open |
| DT5 | F30 | open |
| DT6 | F30 | open |
| DT7 | F30 | open |
| DT8 | F30 | open |
| DT9 | F30 | open |
| DT10 | F30 | dropped (decision B5) |
| 18S7 | F31 | open |
| 18S12 | F31 | open |

### Phase 4: Editors

| Task | Feature | Status |
|---|---|---|
| 23A | F4 | open |
| 23B | F4 | open |
| 23C | F4 | open |
| 23D | F4 | open |
| 23E | F4 | open |
| 23F | F4 | open |
| 23G | F4 | open |
| 24A | F4 | open |
| 24B | F4 | open |
| 24C | F4 | open |
| 24D | F4 | open |
| 24E | F4 | open |
| ED1 | F4 | open |
| ED2 | F4 | open |
| ED3 | F4 | open |
| ED4 | F4 | open |
| ED5 | F4 | open |
| MCP1 | F4 | dropped (decision A10) |

### Phase 5: Lasso compatibility and migration

| Task | Feature | Status |
|---|---|---|
| 25A | F10 | open |
| 25B | F10 | open |
| 25C | F10 | open |
| 25D | F10 | open |
| 26A | F10 | open |
| 26B | F10 | open |
| 26C | F10 | open |
| 26D | F10 | open |
| 26E | F10 | open |
| 26F | F10 | open |
| 26L | F10 | open |
| 26M | F10 | open |
| 26O | F10 | open |
| 26P | F10 | open |
| 35A | F21 | open |
| 35B | F21 | open |
| 35C | F21 | open |
| 35D | F21 | open |
| 35E | F21 | open |
| 35F | F21 | open |
| 35G | F21 | open |
| 9J | F9 | open |
| ON3 | F38 | open |
| ON4 | F38 | open |
| 35I | F21 | open |
| 27A | F31 | open |
| ED6 | F4 | open (built: decision A1) |
| 27B | F31 | open |
| 27C | F31 | open |
| 27D | F31 | open |
| 27F | F31 | open |
| 27G | F31 | open |
| 27H | F31 | open |
| 27I | F31 | open |
| 27X1 | F31 | open |
| 27X2 | F31 | open |
| 27X3 | F31 | open |
| 27X4 | F31 | open |
| 27X5 | F31 | open |
| 27X6 | F31 | open |
| 27X7 | F31 | open |
| 27X8 | F31 | open |
| CL1 | F24 | open |
| CL2 | F24 | open |
| CL3 | F24, F1 | open |
| CL4 | F24, F18 | open |
| CL5 | F24, F9 | open |
| CL6 | F24, F5 | open |
| CL7 | F24, F19 | open |
| CL8 | F24 | open |
| CL9 | F24 | open |
| CL10 | F24, F10 | open |
| CL11 | F24, F18 | open |
| 26G | F10 | open |
| V1 | F27 | open |
| V2 | F27 | open |
| V3 | F27, F24 | open |
| V4 | F27, F2 | open |
| V5 | F27, F19 | open |
| 26H | F10 | open |
| 35H | F21 | open |
| 35J | F21 | open |
| 28A | F10 | dropped (decision A1) |
| 28B | F10 | dropped (decision A1) |
| 28C | F10 | dropped (decision A1) |
| 28D | F10 | dropped (decision A1) |
| 28E | F10 | dropped (decision A1) |
| H1 | F34 | dropped (decision A1) |
| H2 | F34 | dropped (decision A1) |
| H3 | F34 | dropped (decision A2) |
| H4 | F34 | dropped (decision A2) |
| IM1 | F33 | open |
| IM2 | F33 | open |
| IM3 | F33 | open |
| IM4 | F33 | open |
| IM5 | F33 | open |
| IM6 | F33 | open |
| IM7 | F33 | open |
| IM8 | F33 | open |
| IM9 | F33 | open |
| IM10 | F33 | open |
| IM12 | F33 | open |
| IM13 | F33 | open |
| IM14 | F33 | open |
| IM15 | F33 | open |
| IM16 | F33 | open |
| IM17 | F33 | open |
| IM19 | F33 | open |
| 29A | F10 | open |
| 29B | F10 | open |
| 29D | F10 | dropped (decision A1) |
| 29E | F10 | open |

### Phase 6: Link health

| Task | Feature | Status |
|---|---|---|
| 30A | F11 | open |
| 30B | F11 | open |
| 30C | F11 | open |
| 31A | F11, F19 | open |
| 31B | F11, F19 | open |
| 31C | F11 | open |
| 31D | F11, F19 | open |
| 32A | F11 | open |
| 32B | F11 | open |
| 32C | F11 | open |
| 32D | F11 | open |
| 32E | F11 | open |
| 26K | F10 | open |

### Phase 7: Link index, where-used, clicks, opportunities and tables

| Task | Feature | Status |
|---|---|---|
| 36A | F12 | open |
| 36B | F12 | open |
| 36C | F12 | open |
| 36D | F12 | open |
| 36E | F12 | open |
| 33A | F12 | open |
| 33B | F12 | open |
| 33C | F12 | open |
| 33D | F12 | open |
| 34A | F12 | open |
| 34B | F12 | open |
| 34C | F12 | open |
| 34D | F12 | dropped (decision E1) |
| C3A | C3 | open |
| C3B | C3 | open |
| C3C | C3 | open |
| 13K | F19 | open |
| 26J | F10 | dropped (decision E1) |
| C1E | C1 | open |
| C1H | C1 | open |
| O1 | F22 | open |
| O2 | F22 | open |
| O3 | F22 | open |
| O4 | F22 | open |
| O5 | F22 | open |
| O6 | F22 | open |
| O7 | F22 | open |
| O8 | F22 | open |
| O9 | F22 | open |
| O10 | F22 | open |
| C2A | F35 | open |
| C2B | F35 | open |
| C2C | F35 | open |
| AM1 | F35 | open |
| AM2 | F35 | open |
| AM3 | F35 | open |
| AM4 | F35 | open |
| AM5 | F35 | open |
| AM6 | F35 | open |
| 26N | F10 | open |
| T1 | F25 | open |
| T2 | F25 | open |
| T3 | F25 | open |
| T4 | F25 | open |
| T5 | F25 | open |
| T6 | F25 | open |
| T7 | F25 | open |
| T8 | F25 | open |
| T9 | F25 | open |
| T10 | F25 | open |
| T11 | F25 | open |
| T12 | F25 | open |
| 18S13 | F31 | open |
| T13 | F25 | open |
| T14 | F25 | open |
| T15 | F25 | open |
| T16 | F25, F19 | open |
| T17 | F25, F1 | open |
| 26I | F10 | open |
| 27E | F31 | open |
| IM11 | F33 | open |
| IM20 | F33 | open |
| IM18 | F33 | open |
| 29C | F10 | open |

### Phase 8: Hardening and documentation

| Task | Feature | Status |
|---|---|---|
| 37A | All | open |
| 37B | All | open |
| 37C | All | open |
| 37D | All | open |
| 38 | All, F37 | open |
| TL3 | F37 | open |
| TL4 | F37 | open |
| TL6 | F37 | open |
| TL7 | F37 | open |
| ON5 | F38 | open |
| ON6 | F38 | open |
| ON7 | F38 | open |
| ON8 | F38 | open |
| ON9 | F38 | open |
| 39 | All | open |
| 39A | All | open |

### Phase 9: Staging rollout and cutover

| Task | Feature | Status |
|---|---|---|
| 40 | All | open |
| 41 | All | open |
| 42 | All | open |

---

## Tasks C1–C3

Written in full as C1A-C1P (Phases 2, 3 and 7), C2A-C2C (Phase 7) and C3A-C3C (Phase 7); see
`docs/features/C1/`, `docs/features/C2/` and `docs/features/C3/` in the plugin. (The three outlines that stood here
until 2026-11-14 are replaced by those tasks; the two-network limit of C2 is overtaken by F35's 32 networks.)

---

## API and data contract

Rebuilt 2026-11-14 from every feature's `analysis.md` ("Data" and "Entry points"). **Status:** *built* means the route,
key or hook exists in `wp-content/plugins/affibloom/src` (checked by grep, 2026-11-14); everything else is planned and
owned by the task named. Capabilities: `PRODUCTS` and `SETTINGS` are `manage_options` today (decision pending, open
decision C3, TL6); `INSERT_BOXES` is `edit_posts`. Responses follow the global constraints above.

### REST (`/wp-json/affibloom/v1`)

| Method | Path | Capability | Purpose | Feature, task | Status |
|---|---|---|---|---|---|
| POST | `/products` `{ urls }` | PRODUCTS | Add A New Link; 201 `{ created, duplicates, invalid }`; later gains `selections` (Amazon search) | F18 11A; C1M | built |
| DELETE | `/products` `{ ids }` | PRODUCTS | move to the trash | F1 17B | built |
| PATCH | `/products/{id}` | PRODUCTS | product page and quick edit; later `groups`, `slug`, `cloak`, `detect_opportunities` | F1 15A; F23 G3; F24 CL3; F22 O5 | built (later keys planned) |
| DELETE | `/products/{id}` | PRODUCTS | delete for good; 409 `affibloom_in_use` | F1 17A; F12 36E | built (409 planned) |
| POST | `/products/{id}/repoint` | PRODUCTS | the "already exists" repoint | F1 15C | built |
| POST | `/products/bulk` | PRODUCTS | bulk add job | F18 11C | built |
| GET | `/products/bulk` | PRODUCTS | bulk add status | F18 11C | built |
| GET | `/products?search=&page=&per_page=&orderby=&group=` | INSERT_BOXES | block and palette product search; answer items carry `in_group` | F4 23B; F23 G4; F38 ON9 | planned |
| GET | `/products/{id}` | INSERT_BOXES | block sidebar | F4 24D | planned |
| POST | `/products/groups` `{ ids, groups }` | PRODUCTS | bulk Add to Group | F23 G5 | planned |
| POST | `/products/{id}/preview` | PRODUCTS | live preview of unsaved fields | F5 14E | planned |
| POST | `/products/{id}/amazon/refresh` | PRODUCTS | refresh Amazon data | C1D | planned |
| POST | `/products/{id}/alerts/dismiss` | PRODUCTS | hide the issue bar | F11 31C | planned |
| GET, PUT | `/products/{id}/variants` | PRODUCTS | variants list, replace | F27 V1, V2 | planned |
| GET | `/dashboard/products` | PRODUCTS | Dashboard list in place (filters, sort, group, placements, comparison table, opportunities) | F19 13A-13E; F11, F23, F27, F25, F22 | built (later arms planned) |
| POST, DELETE | `/dashboard/views`, `/dashboard/views/{id}` | PRODUCTS | saved views | F19 13E | built |
| GET | `/dashboard/export` | PRODUCTS | CSV of issue tabs | F11 31D | planned |
| GET, POST | `/fields` | PRODUCTS | field library, new field | F1 14C; F26 FL2, FL5 | built (FL changes planned) |
| GET | `/fields/list` | PRODUCTS | Fields list partial | F26 FL1 | planned |
| PATCH, DELETE | `/fields/{id}` | PRODUCTS | rename or edit, delete | F26 FL3 | planned |
| POST, PUT | `/products/{id}/fields` | PRODUCTS | attach a field; save values, toggles, order | F1 14C; F26 FL5 | built |
| DELETE | `/products/{id}/fields/{field}` | PRODUCTS | detach | F1 14C | built |
| GET, POST | `/groups` | PRODUCTS / INSERT_BOXES | list partial; create | F23 G1; F4 23E | planned |
| PATCH, DELETE | `/groups/{id}` | PRODUCTS | edit, delete (409 `affibloom_group_in_use`) | F23 G2 | planned |
| POST, DELETE, PUT | `/groups/{id}/products`, `/groups/{id}/order` | PRODUCTS | add, remove, order members | F23 G4 | planned |
| GET, POST | `/tables`, PATCH/DELETE `/tables/{id}`, POST `/tables/{id}/clone`, GET `/tables/{id}/preview`, `/locations` | PRODUCTS / INSERT_BOXES | the Tables list and builder | F25 T1-T15 | planned |
| POST, PATCH, DELETE, PUT | `/tables/{id}/products`, `/groups`, `/values`, `/order`, `/groups/{g}/fields/{f}[/move]` | PRODUCTS | the table builder's edits (revision-checked) | F25 T4-T9 | planned |
| POST | `/display-preview` | SETTINGS | Settings → Display preview | F16 10D | planned |
| POST | `/ai-text` | INSERT_BOXES | AI text (not built: decision B5) | F30 DT10 | dropped |
| POST | `/editor/preview`, GET `/editor/schema` | INSERT_BOXES / edit_post | block preview and schema panel | F4 23A, 24B | planned |
| PATCH | `/settings` (General arms: link defaults, link index, GA, performance), POST `/settings/link-defaults/apply`, `/settings/override`, `/settings/general` | SETTINGS | settings saved without reload, "Update Link Settings", override all | F9 9D-9L; F21 35D, 35G; C3A; F11 32C | planned |
| GET, POST | `/link-index`, `/link-index/rebuild` (and `/link-index/status`) | SETTINGS | link index status and rebuild | F21 35G; F9 9J, 9L | planned |
| POST | `/clicks` `{ product \| lasso, post, button }` | public | click counter; always 204 | F12 33A | planned |
| POST | `/amazon/validate` | SETTINGS | validate Creators credentials | C1A | planned |
| GET, POST | `/amazon/bulk-sync`, POST `/amazon/bulk-sync/dismiss`, GET `/amazon/search` | PRODUCTS | bulk Amazon sync; search tab of Add A New Link | C1I, C1M | planned |
| POST | `/notices/sitestripe/dismiss` | PRODUCTS | SiteStripe notice | C1N | planned |
| GET, POST | `/amazon-import`, `/amazon-import/dismiss` | PRODUCTS | auto-monetize import status and notice | F35 AM4 | planned |
| GET | `/jobs`; POST `/jobs/run-due` | PRODUCTS / SETTINGS | Automations popover; cron health check | F38 ON3; F11 32D, 32E | planned |
| GET | `/opportunities/links`, `/domains`, `/domains/{d}/links`, `/content`, `/posts/{id}/links`, `/content/export` | PRODUCTS | Opportunities lists in place; CSV | F22 O1-O9 | planned |
| POST, DELETE, PUT | `/opportunities/locations/{row}/dismiss`, `/assignment`; POST `/opportunities/intros/{box}` | PRODUCTS | dismiss, Undo, monetize at render time | F22 O7, O10 | planned |
| GET, POST | `/import/items`, `/import/items/{source}/{ref}`, `/import/bulk`, `/import/items/{source}/{ref}/locations` | PRODUCTS | Import screen, revert, bulk, See It | F33 IM1-IM3, IM18 | planned |
| GET, POST | `/history`, `/history/{post}/revert`, `/history/cleanup` | SETTINGS | content history, revert, clean-up (not built: decisions A1, A2) | F34 H1-H4 | dropped |
| POST | `/migration/run`, `/migration/convert` | SETTINGS | admin migration run (conversion not built: decision A1) | F10 26O | planned |
| POST | `/education/{key}/hide`, `/tour/done`, `/setup/done` | PRODUCTS / SETTINGS | education boxes, tour, wizard | F38 ON5-ON8 | planned |

Error codes follow the `affibloom_*` convention (examples: `affibloom_in_use`, `affibloom_group_in_use`,
`affibloom_field_in_use`, `affibloom_table_in_use`, `affibloom_import_already`, `affibloom_migration_running`);
each feature's analysis lists its own.

### admin-post, WP-CLI, cron, hooks

| Kind | Name | Purpose | Feature, task | Status |
|---|---|---|---|---|
| admin-post | `affibloom_save_settings` (SETTINGS) | save a Settings tab | F9, F17 | built |
| admin-post | `affibloom_save_display`, `affibloom_reset_display` | save, reset Display settings | F16 10A-10E | built |
| admin-post | `affibloom_add_icon`, `affibloom_remove_icon` | button icon set | F28 14F, 14G | built |
| admin-post | `affibloom_tracking_id` | save a tracking ID from the product page | F1 14D | built |
| admin-post | `affibloom_lasso_import` | one-time Lasso import button, retired by 26O | deviation 87 | built |
| admin-post | `affibloom_export`, `affibloom_migration_report` | product export (Task 38); migration report CSV | Task 38; F10 25D | planned |
| WP-CLI | `wp affibloom migrate [--dry-run] [--only=] [--update] [--force] [--report=]` | Lasso migration | F10 25A-26O | planned |
| WP-CLI | `wp affibloom convert-legacy-blocks`, `lasso-attributes strip` | block conversion and stamp clean-up (not built: decisions A1, A2) | F10 28A-28D; F34 H3 | dropped |
| WP-CLI | `wp affibloom check-links` | link check | F11 32A | planned |
| WP-CLI | `wp affibloom links rebuild\|status\|detect <url>` | link index, network detection | F21 35A; F35 C2A | planned |
| WP-CLI | `wp affibloom amazon get <asin>`, `wp affibloom upgrade` | catalogue probe; run the upgrade | C1B; F37 TL2 | planned |
| Cron | `affibloom_bulk_add` | bulk add job | F18 11C | built |
| Cron | `affibloom_daily` (03:00) | link check start, click retention, log pruning | F11 30A; F12 33C; F37 TL3 | planned |
| Cron | `affibloom_link_check_batch`, `affibloom_link_check_product` | link check batches | F11 30A, 30B | planned |
| Cron | `affibloom_link_index_batch`, `affibloom_link_index_daily` | link index | F21 35F | planned |
| Cron | `affibloom_amazon_refresh` (hourly), `affibloom_amazon_bulk_sync`, `affibloom_genius_cleanup` | Amazon data jobs | C1E, C1I, C1P | planned |
| Cron | `affibloom_auto_detect`, `affibloom_auto_detect_daily`, `affibloom_amazon_import` | auto-monetization | F35 C2C, AM1 | planned |
| Cron | `affibloom_import_batch` | bulk import | F33 IM3 | planned |
| Actions | `affibloom_link_issue_detected`, `affibloom_link_index_indexed`, `affibloom_before_link_redirect`, `affibloom_box_failed` (and the earlier `affibloom_legacy_box_failed`) | extension points | F11, F21, F24, F31 | planned |

### Data

| Where | What | Feature | Status |
|---|---|---|---|
| Post type `affibloom_product`; status `affibloom_retired` | the product; trash after the Dashboard delete | F1 | built |
| Product meta | `_affibloom_url`, `_url_key`, `_type`, `_asin`, `_amazon_country`, `_search_key`, `_button_text`, `_buttons`, `_primary_position`, `_new_tab`, `_nofollow`, `_sponsored`, `_badge`, `_description`, `_disclosure`, `_show_disclosure`, `_image_url`, `_image_id`, `_theme`, `_price`, `_show_price`, `_prime`, `_fields`, `_repointed_to`, `_lasso_id` (`_affibloom_buttons` replaced the `_url2` family, F29) | F1, F2, F18, F29 | built |
| Product meta (planned) | `_cloak`, `_old_slugs` (F24); `_domain`, `_detect_opportunities` (F22); `_url_plain_key` (F9 9C); `_amazon_synced`, `_old_genius_url`, `_amazon_updated` (C1); `_show_description` (F5); `_migrated` (F10); `_import_source`, `_import_ref`, `_import_old_url`, `_import_date`, `_import_adopted` (F33); `_dismiss_broken`, `_dismiss_out_of_stock` (F11) | as named | planned |
| Content post meta | `_affibloom_pre_conversion`, `_conversion`, `_pre_cleanup`, `_cleanup` (not built: decisions A1, A2) | F10, F34 | dropped |
| Taxonomy `affibloom_group` (+ term meta `_affibloom_order`) | Groups | F23 | planned |
| Post type `affibloom_table` (+ meta `_affibloom_table`, `_affibloom_lasso_table_id`) | comparison tables | F25 | planned |
| Option `affibloom_settings` | General (`new_tab`, `nofollow`, `sponsored`, `show_disclosure`, `allow_duplicates`, link-defaults and later General keys), Display, Amazon (`tracking_id`, `show_prime`, whitelist/blacklist, `auto_monetize_amazon`), GA keys (C3), `education_hidden`, link index and performance keys, `permission`, `enable_logs`, `delete_data_on_uninstall` | F9, F16, F17, F21, F35, F37, F38, C3 | built (first keys); rest planned |
| Options (built) | `affibloom_display`, `affibloom_fields`, `affibloom_icons`, `affibloom_bulk_add`, `affibloom_lasso_import`, `affibloom_lasso_import_lock` | F16, F26, F28, F18 | built |
| Options (planned) | `affibloom_db_version`, `affibloom_version`, `affibloom_activated_at`, `affibloom_upgrade_lock`, `affibloom_repair_*` (F37); `affibloom_migration`, `affibloom_migration_lock` (F10); `affibloom_link_index_state` (F21); `affibloom_link_check` (F11); `affibloom_amazon_credentials`, `affibloom_amazon_bulk_sync`, `affibloom_sitestripe_notice` (C1); `affibloom_amazon_import_*`, `affibloom_auto_detect_lock` (F35); `affibloom_import_run`, `affibloom_import_reindex_notice` (F33); `affibloom_cloak_prefix_history` (F24); `affibloom_ai_texts` (F30); `affibloom_tour_done`, `affibloom_setup` (F38) | as named | planned |
| User meta | `affibloom_saved_views`, `affibloom_per_page` (built); `affibloom_bulk_sync_dismissed` (C1), `affibloom_amazon_import_dismissed` (F35), `affibloom_import_locations_page` (F33) | F19, F1, C1, F35, F33 | built / planned |
| Custom tables | `affibloom_link_locations` (F21), `affibloom_link_issues` (F11; replaces the earlier `affibloom_link_checks`), `affibloom_clicks` (F12), `affibloom_url_variants` (F27), `affibloom_amazon_products` (C1), `affibloom_detected_links`, `affibloom_amazon_short_links` (F35) — all created by the Migrator (TL1, TL2); the earlier `affibloom_usage` is replaced by `affibloom_link_locations` | as named | planned |
| Transients | `affibloom_link_check_lock`, `affibloom_link_issue_counts`, `affibloom_link_index_lock`, `affibloom_amazon_token_<hash>`, `affibloom_c_<hash>` (click repeat guard) | F11, F21, C1, F12 | planned |
| Files | `uploads/affibloom-logs/<Y_m_d>_<type>.log` (F37); `data/network-subids.php` (F24); `resources/networks.php` (F35); `assets/front/*` (F5) | as named | planned |
| Blocks | `affibloom/product-box` (every display type; attributes in F4 and F5 analyses); `affiliate-plugin/lasso` kept readable (ED6, decision A1) | F4, F5 | planned |
| Shortcodes | `[affibloom …]` (all display types); `[lasso …]`, `[lasso-attr]` and the competitors' shortcodes only while Lasso is inactive | F31 | planned |
| Browser storage | `localStorage` `affibloom_table_locations`, `affibloom_jobs`, `affibloom_jobs_at` (per-viewer conveniences) | F25, F38 | planned |
