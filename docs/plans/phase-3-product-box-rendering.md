# Affibloom — Phase 3: Product displays — Implementation Plan

> **How to run this plan:** inline, in one working session, task by task, in the order below. Do not hand tasks to subagents. When a step is finished, change its `- [ ]` to `- [x]`.
>
> **Explain as you go:** before each task, say in two or three sentences what the task builds, why it is needed now, and how it will be checked. After the task, say what changed and what the verification showed. No vibe coding: every line written must be one you can explain.
>
> **Shared rules:** the architecture, working method, decisions, global constraints, file structure and API contract are in [`00-overview.md`](00-overview.md). Read it first.

## Status

Planned; gate 4 given by the user 2026-11-14 for every open task. The previous Phase 3 text (Tasks 18-22 and the 18S placeholder) is in
`archive/phase-3-superseded.md`.

## Scope and features

The feature folders are in `wp-content/plugins/affibloom/docs/features/<ID>/` (`requirements.md`, `analysis.md`).

- **F5** Single product display, the box engine: `docs/features/F5/` (Tasks 18A-18K, 20A-20D, 21B-21C, 22A-22E, 10D, 14E).
- **F6** Second button and every further button: `docs/features/F6/` (Task 19A).
- **F7** Per-box overrides: `docs/features/F7/` (Tasks 20A-20C).
- **F8** Star rating in the box: `docs/features/F8/` (Task 21A).
- **F16** Display settings, preview part only: `docs/features/F16/` (Task 10D; the other F16 tasks are Phase 2).
- **C1** Amazon product data, the part that reaches the box (price, discount, timestamp, Prime): `docs/features/C1/` (Tasks C1F, C1G; the rest of C1 is Phases 2 and 7).
- **F31** Shortcodes, Affibloom's own `[affibloom]` and its copy buttons: `docs/features/F31/` (Tasks 18S1-18S7, 18S9-18S14; the `[lasso]` family is Phase 5).
- **F30** Display types (grid, list, gallery, button, image, SiteStripe, AI text): `docs/features/F30/` (Tasks DT1-DT9; DT10 dropped).

**Checks that apply to every task** (from the drafts): outside-in, its acceptance test written first and seen red;
`composer lint`; `composer analyse`; the unit tests the task touches; pixel checks at `maxDiffPixelRatio` 0.01
(1 %) against Lasso's baselines (`variants/`, and `themes/` from Task 18D; `displays/` from Task DT1); the check by
hand at 1280 and 390 px (and 768 px for F30); one commit on `main`, pushed. Tests never call Amazon or any AI
service. **ADR-6 stands (the user, 2026-11-14, C1):** the box uses Affibloom's own `affibloom-` class names and its own
stylesheet (written, not ported); wherever the drafts below name a Lasso class or markup (`lasso-*`,
`data-lasso-button`), the task builds the Affibloom equivalent, and its tests assert Affibloom's names. The pixel
checks against Lasso's baselines (1 %) are unchanged. The site's 33 `.lasso-*` Additional CSS rules are translated to
Affibloom's classes at cutover (Task 41 step 5; the list is prepared in Task 40 step 2).

## Decisions that change this phase

Decided 2026-11-12; reasons in `docs/decisions/task-6-decisions.md` in the plugin repository. Apply them when the task
is worked, and record any code that differs from the text below as a deviation in `progress.md`.

- **Task 6 decision 5:** button texts and badges are printed through a `span[class]` allowlist, so the site's icon
  spans show (Tasks 18A, 19A, 20A; the icon set itself is Tasks 14F-14G).
- **F29, any number of buttons (the user, 2026-11-13; register U12):** a product has an ordered list of buttons after
  the primary (`Product::$buttons`, Task 14H). Every box layout that draws the secondary draws buttons 3 and up after
  it, each `a.lasso-button-2` with `data-lasso-button="<n>"`, its own target and rel, hidden without a URL or on its
  own page; the "Secondary button" element hides them all. A product without extra buttons stays byte-for-byte Lasso's
  (F29 B4, AC3, AC5; Task 19A).
- **ADR-8 (the user, 2026-11-12) does not change this phase's box:** Affibloom's own design applies to the admin only.
  The product box readers see keeps the site's current box design and every pixel comparison with Lasso's baselines
  (ADR-6, maximum 1% difference). Only the frame around the box preview on the product page (Task 14E) and on
  Settings → Display (Task 10D) follows the admin design system; the preview itself is the real box. Box
  measurements recorded in Tasks 18A-22E go in a "Product box" section of `docs/ui/tokens.md`, apart from Lasso's admin
  values kept there for reference.
- **Kept from old Task 18A (Task 15C, 2026-11-13, F1 D14):** a product retired by a repoint (`_affibloom_repointed_to`
  set, the status `affibloom_retired` since Task 17C) is drawn as the product it points to, as Lasso's front end
  resolves `lasso_repointed_to_lasso_id`; follow the pointer until a published product (guard against a loop), else
  draw nothing. Applies to Task 18A and, through the shared resolver, to 18S1.
- **Kept from old Task 18A (the Task 17 sweep, 2026-11-13, F1 X10, D41, D43):** a retired product's status is
  `affibloom_retired` (17C), not the trash. Lasso follows the pointer whatever the status, up to 50 hops, and stops at a
  target that no longer exists; a product deleted for good (17A) may be such a target, so the resolver stops at the
  last product that exists and draws it only if it is published.
- **Kept from the old 18S placeholder (the user's gate 4 answer for F19, 2026-11-13, `progress.md` deviation 48):**
  "we are going to keep the shortcode functionalities as well." Affibloom's own shortcode (Tasks 18S1-18S14) is
  separate from the `[lasso]` compatibility handler (ADR-3, Phase 5 Task 27A), which exists only for content Lasso left
  behind. The roadmap's Screen map and `docs/spec/interface.md` still say Affibloom has no shortcode of its own; correct
  them when Task 18S1 starts (its gates 1-2 are F31's requirements and analysis).
- **Group displays (F23 R24, D5, section 5):** the List, Grid and Gallery display types draw a group's products in the
  group's order (`GroupService::members()`, F23 D12), for Affibloom's shortcode and for
  `[lasso type="grid|list|gallery" category="<slug>"]` (Phase 5). They are Tasks DT2, DT4 and DT6, entered through 18S7.

**Decisions (the user, 2026-11-14):** each item is the user's answer, or the recommendation, which the user accepted.

- **Decided (the user, 2026-11-14), C1 / F5 N1:** ADR-6 is kept: Affibloom's own `affibloom-` class names and its own stylesheet; Lasso's CSS is not ported (F5 N1's recommendation not taken). The site's 33 `.lasso-*` Additional CSS rules are translated before cutover (Task 41).
- **Decided (the user, 2026-11-14), C5:** Smart CSS Loading is on by default and the migration switches it on.
- **Decided (the user, 2026-11-14), B9:** brag mode is left out; no brag icon. Task 22D is dropped; the Display controls built in 10E are removed in Task 2X6.
- **Decided (the user, 2026-11-14), F1:** one staging session for the baseline capture of Task 18D; the test group, draft page and table clone are deleted after the capture.
- **Decided (the user accepted the recommendation, 2026-11-14), B5:** `[lasso prompt]` prints Lasso's cached text or nothing; `[affibloom]` offers no generator (Task 27F is Phase 5).
- **Decided (the user accepted the recommendation, 2026-11-14), C8:** accept `h1` too (Task 18S2).
- **Decided (the user accepted the recommendation, 2026-11-14), D1:** keep Lasso's behaviour for `[lasso]`; `[affibloom]` accepts `true` only (Task 18S7).
- **Decided (the user accepted the recommendation, 2026-11-14):** Easy Affiliate Links' REST and block takeover (F31 N4, recommended: build it as Lasso has it, active only when EAFL is; Task 27X6, Phase 5).
- **Decided (the user accepted the recommendation, 2026-11-14), D1:** Lasso's behaviour for `[lasso]`; Affibloom's shortcode and block do what the help texts say (Task DT3).
- **Decided (the user, 2026-11-14), B5:** show stored AI text only (DT9); the generator DT10 is not built.
- **Decided (the user accepted the recommendation, 2026-11-14):** SiteStripe iframes in content (F30 N3, recommended: replace a matched iframe at render time, owned by the Settings → Amazon / link-scan sweep, not a task here).
- **Decided (the user, 2026-11-14), F1:** DT1 creates one test group and one draft page on staging, captures, then deletes both (one approved staging session).
- **Decided (the user, 2026-11-14), B4:** the three switches are kept as stored values, labelled "no effect"; nothing is built behind them (Task C1L, Phase 2).
- **Decided (the user, 2026-11-14), C4:** Lasso's Prime rule: the logo on every Amazon product while the site setting is on; the per-product switch is an opt-out defaulting on (Task C1G).
- **Decided (the user accepted the recommendation, 2026-11-14):** Amazon prices after cutover without credentials (C1 N3, recommended: an Amazon price shows only from a `creators` cache row younger than 24 hours; Task C1F).
- **Decided (the user accepted the recommendation, 2026-11-14):** Amazon's 24-hour rules (C1 N4, recommended: re-read the policies and the Creators API terms when C1E starts; Task C1E is Phase 7).

Two decisions are settled together (B5 and D1, 2026-11-14): Affibloom's own shortcode honours `columns` and
`compact="true"` only; `[affibloom prompt="..."]` prints stored text only (DT9) and has no generator.

## Tasks in this phase

Tasks run in the order listed. The order was settled on 2026-11-14 so that every task's dependencies come before it
(the drafts' grouping by feature is kept in the task sections below).

**F5-F8, the Single box, with buttons, overrides, rating, fields, price, disclosure (run order):**

- [ ] Task 18A: The box reaches a reader — markup, elements and links
- [ ] Task 18B: The stylesheet — Lab, the site's settings, colours and Custom CSS
- [ ] Task 18C: Where the box's assets load
- [ ] Task 18D: Lasso's baselines for every theme and state
- [ ] Task 18E: The 2-box themes and theme resolution
- [ ] Task 18F: The 6-box themes (Cactus, Money)
- [ ] Task 18G: Llama
- [ ] Task 18H: Layout controls and the phone override on the box
- [ ] Task 18I: Density and typography on the box
- [ ] Task 19A: Every button of the product in the box
- [ ] Task 20A: Per-box overrides — text and image
- [ ] Task 21A: The Primary Rating stars
- [ ] Task 18J: Element visibility on the box
- [ ] Task 22A: Price, discount and Prime
- [ ] Task 22B: Disclosure
- [ ] Task 22C: Timestamp
- [ ] Task 18K: Classic rendering
- [ ] Task 20B: Per-box overrides — theme, buttons, tracking ID, disclosure, anchor, mobile
- [ ] Task 20C: Per-box overrides — price, Prime, rating, Pros & Cons, fields
- [ ] Task 21B: Custom fields and Pros & Cons in the box
- [ ] Task 21C: The Copy to Clipboard field
- [ ] Task 20D: Product structured data
- Task 22D: dropped (decision B9: brag mode left out)
- [ ] Task 22E: SiteStripe box

**F16, F5, F1, the live previews:**

- [ ] Task 10D: Settings → Display — the live preview
- [ ] Task 14E: The product page — the live preview

**C1, Amazon price and Prime in the box:**

- [ ] Task C1F: Price, discount and timestamp
- [ ] Task C1G: Prime by Lasso's rule

**F31, Affibloom's own shortcode (single-product parts):**

- [ ] Task 18S1: `[affibloom id]` draws a product's box
- [ ] Task 18S2: Text and link overrides
- [ ] Task 18S3: Value overrides
- [ ] Task 18S4: `type="button"` and `type="image"`
- [ ] Task 18S5: `type="link"`, SiteStripe and inline fields
- [ ] Task 18S6: Address a product by its Amazon URL or destination
- [ ] Task 18S9: Boxes in widgets, on AMP pages and in feeds
- [ ] Task 18S10: The Dashboard row's Shortcode button
- [ ] Task 18S11: The Share kit's Copy shortcode
- [ ] Task 18S14: Review and pros/cons schema in the page head

**F30, display types, then the group shortcode parts that need them:**

- [ ] Task DT1: Lasso's own render of the other display types, as baselines
- [ ] Task DT2: A group shown as a grid
- [ ] Task DT3: The grid with default Display settings — rows, `columns`, `compact`
- [ ] Task DT4: A group shown as a list, with the phone swipe row
- [ ] Task DT5: A product shown as an image
- [ ] Task DT6: A group shown as a gallery
- [ ] Task DT7: A product shown as a button
- [ ] Task DT8: The SiteStripe card
- [ ] Task DT9: Stored AI text
- Task DT10: dropped (decision B5: stored AI text only)
- [ ] Task 18S7: Group displays by shortcode
- [ ] Task 18S12: A group's Copy Shortcode

Task 18S8 (the table display by shortcode) is superseded by F25 Task T12 (Phase 7); the number is not reused.
**Task 18S13 (a table's Copy Shortcode) moved to Phase 7**, right after F25 T12 (it needs the table builder T3 and T12);
its section stays below for reference and is listed in Phase 7.

## Cross-phase notes


**Phase 2's list points here.** Phase 2's "Tasks in this phase" list still shows Task 10D (the Display live preview)
and Task 14E (the product page's live preview) as running in Phase 3 "right after Tasks 18A-18K". The Phase 2 file also still
holds older text for both. This file's Tasks 10D and 14E replace that text; Phase 2 was left untouched. "Tasks 18A-18K" in
Phase 2 means the box tasks 18A-18K, and "Task 18S" there means Tasks 18S1-18S14.

**Earlier phases (what this phase reads).**

- Phase 1: Task 5's baseline tooling (`tools/baselines.config.ts`) for 18D and DT1; the `variants/` baselines for 18A-18B.
- Phase 2: Tasks 10A-10C (the Display settings model and layout coercion), 10E (Custom CSS, Keep Site Stripe UI,
  brag, Disclosure in Grids) and 10F (Amazon tab, whitelist); 9B-9L (General settings: Task 18C adds the keys
  `smart_css_loading` and `fontawesome_js_svg`, the General sweep owns their controls); 11A-11E and 12A-12E (add a link,
  Amazon links, tracking IDs); 13C (the Dashboard row's Actions cell, 18S10); 14A-14D, 14F-14H (the product page, icons,
  buttons list; 14B read-only price, 14C rating field); 15A-15D (saving a product); 17C (retired status); FL1-FL5
  (custom fields, 21B); G1-G5 (groups and `GroupService::members()`, DT2, 18S7, 18S12); C1A-C1D (Amazon data and its
  table, read by 22C and C1F).

**Later phases (which part of which task waits, and how it is stubbed).**

- **Phase 4 (editors).** The block sidebar (Task 24A) shows the attribute catalogue and help texts (F5 R89); Tasks 20A-20D
  add the attributes to `block.json` and work without the sidebar. DT10 is dropped (decision B5). Elementor's widget is Task ED2.
- **Phase 5 (Lasso compatibility).** Task 27A's `[lasso]` handler (27A-27I) and the competitor shortcodes (27X1-27X8) are
  later callers of this phase's renderers; nothing here waits on them. `BoxOverrides::from_shortcode()` (20A) is built
  for them. DT3's N1 rule for `[lasso]`, DT9's store, which Tasks 26A-26P fills from Lasso's transients, and `[lasso]` grid,
  list and gallery (27D) are theirs; until Phase 5 the AI-text store is empty in production and the tests seed it.
- **Phase 7 (index, clicks, tables).** Task 18C's detection works without the usage index (Tasks 35A-35C) and uses it when it
  exists. Task 22C's real timestamps come from C1C (Phase 2) and C1E (Phase 7, scheduled refresh); the tests seed an
  update time. **Task 18S13 (a table's Copy Shortcode) waits for F25 T3 (the table builder page) and T12 (the table
  shortcode), both Phase 7.** It is listed here, stays unticked, and is carried to Phase 7 right after T12 if T3 is not
  built when this phase closes; F31 AC13 then leaves this phase's exit check. Until then the builder page keeps its
  "Coming soon" for Copy Shortcode.
- **Phase 9.** ADR-6 is kept (decided 2026-11-14): Task 41 translates the site's Additional CSS rules.

**Within-phase ordering:** settled 2026-11-14 — the checklist above is the dependency-valid run order (18J after 21A;
18K after 22A and 22C; 20B after 22B; 20C after 21A and 22A; 20D after 21A and 21B; 18S7 and 18S12 after DT2, DT4, DT6).

**Shared classes (settled 2026-11-14 in the planning pass; follow these names, extract nothing twice):**

- **Per-box overrides:** `Domain\Box\BoxOverrides`, created by 18A and extended by 20A-20C. The F31 drafts' name
  `BoxOptions` means this class; 18S1-18S3 add `BoxOverrides::from_shortcode()` attributes, Task 27A and the block
  (Phase 4) use the same class. One mapping from Lasso's attribute names (`title_url`, `price="hide"`, …) to the
  class's fields serves the shortcode, the block's stored attributes and `[lasso]`.
- **Structured data:** 20D creates `Application\Box\SchemaBuilder` and `Interface\Front\SchemaMarkup`; 18S14 does not
  create a second builder: it adds the page-head output for shortcode boxes through 20D's classes.
- **Display requests:** 18S1 creates `Domain\Shortcode\{ShortcodeAttributes, DisplayKind, DisplayRequest, AnchorId}`;
  the DT tasks extend them (no `Domain\Display\DisplayRequest`). Phase 4's block builds a `DisplayRequest` too.
- **Renderers:** 18S4 creates `Interface\Front\ButtonHtml` and `ImageHtml`; DT5 and DT7 reuse them and add their
  baselines, settings and parity checks. 22E creates `Interface\Front\SiteStripeHtml`; 18S5 and DT8 reuse it. DT2, DT4
  and DT6 create the group renderers (`GroupHtml` and its grid/list/gallery templates); 18S7 only dispatches
  `category=` to them.
- **Prime:** 22A prints Prime by F5 R20/AC20 (every Amazon product while Show Prime is on); C1G handles the
  per-product switch question (C1 N2, decided C4).

**Coverage owners assigned 2026-11-14:** F31 R34 (shortcode strings built by other screens) → F4 23C-23F (the
editor's insert strings) and 18S10-18S12 (copy buttons); F31 R35 (`lasso_get_shortcode_content`) → F4 24C; R2's
Elementor key filter → F4 ED2.

**Dependencies checked against the phase map.** Every task id named under "Depends on" exists in the map or in this
file: 10A-10C, 10E, 10F, 11A-11E, 12A-12E, 13C, 14A-14H, 15A-15D, 17C, FL1-FL5, G1-G5, C1A-C1E, Task 5, Task 24A, Tasks
26 and 27, Tasks 35A-35C, ED2, F25 T3 and T12 and F11 Tasks 30A-32E (not used by the tasks here). Where a draft used a name that the map does not
carry, it was mapped: "Tasks 18A-18K, 18y" to 18A-18K; "Task 14 (Actions cell)" to 13C; "F28 icon allowlist" to 14F-14G;
"F2/F17 tracking IDs" to 12A-12E and 10F; "the Phase 3 group-display task" to DT2, DT4, DT6. **UNRESOLVED:** none.


---

### Task 18A: The box reaches a reader — markup, elements and links

**Feature:** F5 (R1, R2, R6, R9-R12, R15-R17, R22, R37-R40, R54; D1-D4, D9, D14; AC3, AC4)

**Why:** nothing reader-facing exists yet; the live boxes are Lab (3-box) drawn by Lasso's advanced engine, so the
first box must print that markup with its wrapper attributes before any CSS can be compared.

**Depends on:** F16 domain (10A-10C), F1/F29 product (15A-15D), F28 icon allowlist (Tasks 14F-14G).

> **Decided (the user, 2026-11-14), ADR-6 kept:** the markup uses Affibloom's own `affibloom-` class names, not Lasso's `lasso-*` names. The site's 33 `.lasso-*` Additional CSS rules do not apply to this markup; they are translated to Affibloom's classes at cutover (Task 41 step 5, list prepared in Task 40 step 2). Keep a table of each Lasso class and the Affibloom class it becomes in `docs/ui/tokens.md` ("Product box"), so that translation can be done.

**User action:** a reader opens a post holding `<!-- wp:affibloom/product-box {"productId":N} /-->`: the box prints
Lasso's 3-box markup (badge, image link, title link or span, price block with Prime, description, button 1,
disclosure, empty date), the v2 wrapper with every attribute and property from the saved Display settings, and
Lasso's link attributes. A missing, draft or retired product prints nothing (a repoint is followed first). No CSS yet.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `blocks/product-box/block.json` (server side), `Interface\Block\ProductBoxBlock` | `init`, `render_block` |
| `Interface\Front\BoxHtml`, `templates/front/box-3.php` + partials (badge, image, title, price, description, buttons, end) | `ProductBoxBlock` |
| `Application\Box\{BoxBuilder, BoxView, BoxButton}`, `Application\Display\DisplayPresentation` | `BoxHtml` |
| `Domain\Box\{BoxOverrides (productId only for now), DescriptionRule, LayoutFamily}`, `Domain\Link\LinkAttributes` | `BoxBuilder` |
| `assets/front/images/no-thumbnail.webp`, setting `default_thumbnail` | `BoxBuilder` |

**Files:** create the above; `tests/Integration/ProductBoxBlockTest.php`, unit tests `LinkAttributesTest`,
`DescriptionRuleTest`, `DisplayPresentationTest`, `BoxBuilderTest`; modify `src/Plugin.php`, `F5.spec.ts` (AC3, AC4,
selectors to Lasso's classes plus `.affibloom-box`), `F6-F8.spec.ts` selectors.

- [ ] **Step 1: Failing tests.** AC3: build the amazon, other and no-description example products, compare the box DOM
  with `variants/*.html` (normalised; `decoding`, `data-lasso-link`, `data-layout-d-*` ignored). AC4: attribute order
  and rel/target tables. Expected red: the block is not registered.
- [ ] **Step 2: Domain** with unit tests: `LinkAttributes` (Lasso's rel tables, secondary sponsored only with
  NoFollow 2, unset flag = off), `DescriptionRule` (R22 without writing), `LayoutFamily`.
- [ ] **Step 3: `DisplayPresentation`** (R54: attributes, phone attributes, hide flags, inline properties).
- [ ] **Step 4: Builder, view, template, block.** Escaping per D4; comment `<!-- PRODUCT BOX -->`.
- [ ] **Step 5: Run and look** (view source beside `variants/amazon.html`).
- [ ] **Step 6: Commit** — "Render the Single product box with Lasso's markup".

---

### Task 18B: The stylesheet — Lab, the site's settings, colours and Custom CSS

**Feature:** F5 (R42, R43; D5; AC1 for amazon, other, no-description; AC5), F16 AC10

**Why:** with 18A's markup, Lasso's look comes from its CSS; Lab with the site's settings is what readers see today.

**Depends on:** 18A.

> **Decided (the user, 2026-11-14), ADR-6 kept:** the stylesheet is written for Affibloom's class names (Lasso's CSS is not ported) and tuned to the baselines (1 %). The `.lasso-*` Additional CSS on the site is translated at cutover (Task 41 step 5).

**User action:** the reader sees the three Lab examples as Lasso draws them, desktop and phone; the editor's Custom CSS
applies; the `<head>` holds the `:root` colour block.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `assets/front/box.css` (port: container, elements, tooltip, Lab, `.mobile`), `assets/front/box-layout.css` (port of the v2 rules Lab needs) | `BoxAssets` |
| `Interface\Front\BoxAssets::enqueue()`, `print_head()` (`:root`, Custom CSS) | `wp_enqueue_scripts`, `wp_head` |
| `Infrastructure\WordPress\CustomCss` (10E's plan; first caller) | `BoxAssets` |
| `assets/front/images/{prime.png, info.svg}` | `box.css` |

**Files:** create `assets/front/box.css`, `assets/front/box-layout.css`, `assets/front/images/{prime.png,info.svg}`; create or reuse `src/Infrastructure/WordPress/CustomCss.php` (Task 10E's plan); modify `src/Interface/Front/BoxAssets.php`, `tests/E2E/acceptance/F5.spec.ts` (AC1, AC5) and the F16 spec (AC10).

- [ ] **Step 1: Failing tests:** F5 AC1 (three variants), AC5. Red: unstyled box.
- [ ] **Step 2: Port** the rules, with a header naming Lasso 369's files and lines (GPL v2+).
- [ ] **Step 3: Assets and head block.**
- [ ] **Step 4: Run and look** at 1280 and 390 beside the PNGs.
- [ ] **Step 5: Commit** — "Style the box as Lasso's Lab display".

---

### Task 18C: Where the box's assets load

**Feature:** F5 (R45-R52; D12, D13; AC2, AC6)

**Why:** Lasso decides per page whether to load its CSS (Smart CSS Loading), always loads the copy script, adds the
phone script only in advanced rendering, and handles AMP, Jetpack, Font Awesome and a body class.

**Depends on:** 18B; N2; usage index (Tasks 35A-35C) optional (detection works without it); General settings screen gains
the two toggles (General sweep owns the controls; this task adds the keys and the front end).

**User action:** with Smart CSS Loading on, a page without a box loads no Affibloom CSS/JS (except the copy script, as
Lasso); off, every page loads it; a box in a widget still gets its CSS late; phones swap to the phone layout.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Infrastructure\WordPress\BoxDetector` | `BoxAssets::enqueue()` |
| `BoxAssets::late_enqueue()`, WP-theme colour override CSS | `BoxHtml` |
| `assets/front/box-layout.js` (R48), `assets/front/copy-clipboard.js` (R36's loader; behaviour 21C) | `BoxAssets` |
| settings `smart_css_loading`, `fontawesome_js_svg`; Font Awesome config print; body class `affibloom-v<version>`; AMP CSS (`box-amp.css`); Jetpack filter | `wp_head`, `body_class`, `amp_post_template_css`, `jetpack_photon_skip_for_url` |

**Files:** create `src/Infrastructure/WordPress/BoxDetector.php`, `assets/front/{box-layout.js,copy-clipboard.js,box-amp.css}`, `tests/Unit/Infrastructure/BoxDetectorTest.php`; modify `src/Interface/Front/{BoxAssets,BoxHtml}.php`, the settings class (`smart_css_loading`, `fontawesome_js_svg`), `src/Plugin.php`, `tests/E2E/acceptance/F5.spec.ts` (AC2, AC6).

- [ ] **Step 1: Failing tests:** AC2 (Smart on), AC6 (off loads everywhere; late enqueue in a widget; phone swap at
  390 px). Unit: `BoxDetectorTest`.
- [ ] **Step 2: Detector and late enqueue.**
- [ ] **Step 3: Scripts and the small hooks.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Load the box's assets by Lasso's rules".

---

### Task 18D: Lasso's baselines for every theme and state

**Feature:** F5 (D10; AC7)

**Why:** only Lab exists on the site; every other theme and state needs Lasso's own picture to compare against.

**Depends on:** N4 (staging session approved).

**User action:** the tool creates throwaway products and posts on staging (Lasso active), sets each product's Display
Theme and box attributes, captures desktop and phone PNG and HTML, and deletes what it made.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `tools/baselines-themes.spec.ts`, project `themes` in `baselines.config.ts`, the matrix file (8 themes × {plain, badge, two buttons, rating, Pros & Cons, all fields, no image}; layout keys; 3 presets + one custom; element toggles; classic mode; SiteStripe; Copy field) | the user runs it |
| `tests/fixtures/baselines/themes/*` | 18E-18K, 21B, 21C, 22A-22E |

**Files:** create `tools/baselines-themes.spec.ts`, the matrix file, `tests/fixtures/baselines/themes/*`; modify `tools/baselines.config.ts`, `tests/E2E/acceptance/F5.spec.ts` (AC7).

- [ ] **Step 1: Failing test.** AC7 asserts that every file the matrix lists exists in
  `tests/fixtures/baselines/themes/` and is a PNG or HTML file of the stated kind. Expected red: the folder is empty.
- [ ] **Step 2: Matrix and tool.** Write the matrix and the tool (dry run on Local without Lasso: it lists what it
  would make and touches nothing).
- [ ] **Step 3: The user runs it on staging;** files committed (PNG size recorded, guide PNGs stay out of git).
- [ ] **Step 4: Run and look.** AC7 green; look at each image for loading spinners and cookie banners.
- [ ] **Step 5: Commit** — "Capture Lasso's Single display for every theme and state".

---

### Task 18E: The 2-box themes and theme resolution

**Feature:** F5 (R3, R5, R8; AC8)

**Why:** Cutter, Flow, Geek and Splash share one layout; the theme also comes from the product and the box.

**Depends on:** 18B, 18D.

**User action:** choosing Cutter (site, product Display Theme, or box) draws Cutter's 2-box card; `lab` in any casing
works; an unknown name draws Cactus.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Box\ThemeResolver`; `templates/front/box-2.php`; the four themes' rules in `box.css` / `box-layout.css` | `BoxBuilder`, `BoxAssets` |

**Files:** create `src/Domain/Box/ThemeResolver.php`, `templates/front/box-2.php`, `tests/Unit/Domain/Box/ThemeResolverTest.php`; modify `src/Application/Box/BoxBuilder.php`, `src/Interface/Front/BoxHtml.php`, `assets/front/box.css`, `assets/front/box-layout.css`, `tests/E2E/acceptance/F5.spec.ts` (AC8).

- [ ] **Step 1: Failing tests** (AC8, pixels per theme; unit `ThemeResolverTest`).
- [ ] **Step 2: Resolver.**
- [ ] **Step 3: Template and CSS.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Draw Lasso's 2-box themes".

---

### Task 18F: The 6-box themes (Cactus, Money)

**Feature:** F5 (R7; AC9)

**Why:** Cactus and Money share Lasso's 6-box layout (F5 R7, R5), which no earlier task draws; the box-5 disclosure prints only when the disclosure shows.

**Depends on:** 18E.

**User action:** Cactus or Money draws six boxes; the disclosure box only when the disclosure shows; box-4 empty
without a second button.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `templates/front/box-6.php`; Cactus and Money rules | `BoxHtml` |

**Files:** create `templates/front/box-6.php`; modify `assets/front/box.css`, `assets/front/box-layout.css`, `src/Interface/Front/BoxHtml.php`, `tests/E2E/acceptance/F5.spec.ts` (AC9).

- [ ] **Step 1: Failing tests (AC9).**
- [ ] **Step 2: Template.**
- [ ] **Step 3: CSS.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Draw Lasso's 6-box themes".

---

### Task 18G: Llama

**Feature:** F5 (AC10)

**Why:** Llama uses the 3-box layout that 18A prints but has its own look (F5 R5, AC10).

**Depends on:** 18E (resolver), 18B (3-box template).

**User action:** Llama draws the 3-box card with its own look.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| Llama rules | `BoxAssets` |

**Files:** modify `assets/front/box.css`, `tests/E2E/acceptance/F5.spec.ts` (AC10).

- [ ] **Step 1: Failing test (AC10).**
- [ ] **Step 2: CSS.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Draw Lasso's Llama theme".

---

### Task 18H: Layout controls and the phone override on the box

**Feature:** F5 (R55-R57; AC11), F16 AC6, AC7 (front end)

**Why:** every layout key changes the reader's box through `data-layout-*` and the v2 rules.

**Depends on:** 18E-18G, 18C (phone script).

**User action:** saving card layout, image position, alignments, button width or image fit (desktop or phone) changes
every box accordingly; phones below 768 px show the phone layout.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| The remaining layout rules of `lasso-display-v2-frontend.css` for all three families | `BoxAssets` |

**Files:** modify `assets/front/box-layout.css`, `tests/E2E/acceptance/F5.spec.ts` (AC11) and the F16 spec's front-end rows (AC6, AC7).

- [ ] **Step 1: Failing tests** (AC11 matrix from `themes/`).
- [ ] **Step 2: CSS port.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Apply the Display layout to the box".

---

### Task 18I: Density and typography on the box

**Feature:** F5 (R58, R59; AC12), F16 AC4 (front end)

**Why:** the Display density presets, custom spacing and type sizes reach the reader's box only through the wrapper's custom properties and the line-clamp rules (F5 R58, R59).

**Depends on:** 18H.

**User action:** a preset or custom spacing and type sizes, and the title/description line clamps, change every box.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| density and clamp rules; `DisplayPresentation` density properties (exists, tested here end to end) | `BoxAssets` |

**Files:** modify `assets/front/box-layout.css`, `assets/front/box.css`, `src/Application/Display/DisplayPresentation.php`, `tests/E2E/acceptance/F5.spec.ts` (AC12) and the F16 spec's front-end row (AC4).

- [ ] **Step 1: Failing tests (AC12).**
- [ ] **Step 2: CSS.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Apply Display density and typography to the box".

---

### Task 18J: Element visibility on the box

**Feature:** F5 (R60-R63; AC13), F16 AC5 (front end), F6 AC6

**Why:** Lasso hides elements with CSS and keeps the markup, with exceptions for the title, a product's own badge and the rating (F5 R60-R63); the site's element toggles must reach every box.

**Depends on:** 18H, 21A.

**User action:** turning an element off hides it in every box (title not printed); a product badge still shows; the
rating stays with Pros & cons off.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Box\ElementVisibility`; hide rules | `BoxBuilder`, `BoxAssets` |

**Files:** create `src/Domain/Box/ElementVisibility.php`, `tests/Unit/Domain/Box/ElementVisibilityTest.php`; modify `src/Application/Box/BoxBuilder.php`, `assets/front/box-layout.css`, `tests/E2E/acceptance/F5.spec.ts` (AC13), the F6 spec (AC6) and the F16 spec's front-end row (AC5).

- [ ] **Step 1: Failing tests** (AC13; unit `ElementVisibilityTest`).
- [ ] **Step 2: Domain.**
- [ ] **Step 3: CSS.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Hide box elements by the Display settings".

---

### Task 18K: Classic rendering

**Feature:** F5 (R27, R53, R64; AC14), F16 B1, B13

**Why:** with every Display setting untouched Lasso draws the classic box, without the v2 wrapper or stylesheet, and its price and timestamp rules differ there (F5 R53, R64, R27); the site's boxes may return to that state after a Reset.

**Depends on:** 18J, 22A, 22C.

**User action:** with every Display setting at its default the box drops the v2 wrapper and stylesheet and shows
price and timestamp by Lasso's classic rules; the filter `affibloom_display_advanced_enabled` can switch the engine off.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the classic branch in `BoxBuilder` and `BoxAssets`; the filter | the block |

**Files:** modify `src/Application/Box/BoxBuilder.php`, `src/Interface/Front/BoxAssets.php`, `src/Plugin.php` (the filter), `tests/E2E/acceptance/F5.spec.ts` (AC14) and the F16 spec's rows for B1 and B13.

- [ ] **Step 1: Failing tests (AC14).**
- [ ] **Step 2: Branch.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Render the box classically when the Display settings are untouched".

---

### Task 19A: Every button of the product in the box

**Feature:** F5 (R41; AC15; AC1 second-button, badge, prime), F6 AC1-AC6, F29 AC3, AC5

**Why:** 78 live boxes have a second button; F29 adds more.

**Depends on:** 18A, 18B.

**User action:** the box shows the product's buttons in order: the first `lasso-button-1`, the rest `lasso-button-2`
with their numbers, texts (per-type default), rel and target; a button to the page itself or without URL is absent.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LinkAttributes::for_secondary()` use, `BoxButton` list, self-link check (`url_to_postid`) | `BoxBuilder` |

**Files:** modify `src/Application/Box/{BoxBuilder,BoxButton}.php`, `src/Domain/Link/LinkAttributes.php`, `templates/front/box-{2,3,6}.php`, `tests/E2E/acceptance/{F5,F6}.spec.ts`, the F29 spec (AC3, AC5), `tests/Unit/Application/BoxBuilderTest.php`.

- [ ] **Step 1: Failing tests** (F6 AC1-AC6, F5 AC1 three variants, F29 AC3/AC5).
- [ ] **Step 2: Builder.**
- [ ] **Step 3: Templates (all three families).**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Show every product button in the box".

---

### Task 20A: Per-box overrides — text and image

**Feature:** F5 (R66-R69, R76, R79; AC16), F7 AC1-AC4

**Why:** 113 boxes override the badge, 119 the description, 34 the title, 8 the image, 4 the title link.

**Depends on:** 18A; Phase 4 builds the sidebar later.

**User action:** a block with `badge`, `hideBadge`, `description`, `hideDescription`, `title`, `hideTitle`, `titleUrl`,
`titleType`, `imageUrl`, `hideImage` or `imageAlt` changes only that box.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| those attributes in `block.json`; `BoxOverrides::from_block()` (and `from_shortcode()` for Lasso names, used by 18S2 and Task 27A) | `ProductBoxBlock` |

**Files:** modify `blocks/product-box/block.json`, `src/Domain/Box/BoxOverrides.php`, `src/Interface/Block/ProductBoxBlock.php`, `src/Application/Box/BoxBuilder.php`; create `tests/Unit/Domain/Box/BoxOverridesTest.php`; modify `tests/E2E/acceptance/F7.spec.ts` (AC1-AC4).

- [ ] **Step 1: Failing tests (F7 AC1-AC4; unit `BoxOverridesTest`).**
- [ ] **Step 2: Domain.**
- [ ] **Step 3: Builder.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Override a box's text and image".

---

### Task 20B: Per-box overrides — theme, buttons, tracking ID, disclosure, anchor, mobile

**Feature:** F5 (R65, R73, R75, R80-R82, R87), F7 AC5-AC9

**Why:** a block (and later a shortcode) must change one box's theme, buttons, tracking ID, disclosure, anchor or phone layout without touching the product or other boxes (F5 R65, R73, R75, R80-R82, R87).

**Depends on:** 20A, 19A, 22B, 18E.

**User action:** `theme`, `primaryUrl`/`Text`, `secondaryUrl`/`Text` (`hide`), `trackingId`, `disclosureText`,
`anchorId`, `linkId`, `style`, and a box inside a text widget change only that box (`ga`/`brag` accepted, no effect).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the attributes; widget context (`widget_text` origin) | `ProductBoxBlock`, `BoxBuilder` |

**Files:** modify `blocks/product-box/block.json`, `src/Interface/Block/ProductBoxBlock.php`, `src/Application/Box/BoxBuilder.php`, `src/Domain/Box/BoxOverrides.php`, `tests/Unit/Domain/Box/BoxOverridesTest.php`, `tests/E2E/acceptance/F7.spec.ts` (AC5-AC9).

- [ ] **Step 1: Failing tests (F7 AC5-AC9).**
- [ ] **Step 2: Builder.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Override a box's theme, buttons and disclosure".

---

### Task 20C: Per-box overrides — price, Prime, rating, Pros & Cons, fields

**Feature:** F5 (R70-R72, R74, R77, R78, R84), F7 AC10-AC12

**Why:** a block must override one box's price, Prime, basis price, rating, Pros & Cons and fields only for that box (F5 R70-R72, R74, R77, R78, R84).

**Depends on:** 20A, 21A, 21B, 22A.

**User action:** `price` (hide/show/value), `prime`, `basisPrice`, `rating` (hide/value), `pros`, `cons`, labels,
`fields` hide, `showProsCons` change only that box.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the attributes | `BoxBuilder` |

**Files:** modify `blocks/product-box/block.json`, `src/Application/Box/BoxBuilder.php`, `src/Domain/Box/BoxOverrides.php`, `tests/E2E/acceptance/F7.spec.ts` (AC10-AC12).

- [ ] **Step 1: Failing tests (F7 AC10-AC12).**
- [ ] **Step 2: Builder.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Override a box's price, rating and fields".

---

### Task 20D: Product structured data

**Feature:** F5 (R90-R93; AC17)

**Why:** Lasso prints Product JSON-LD in the head for the first box that asks for it (F5 R90-R93), and 248 boxes on this site carry the schema attributes (F31 18S14).

**Depends on:** 20A, 21A, 21B.

**User action:** the first box with `schemaReview` or `schemaProsCons` on prints one Product JSON-LD in `<head>`
(offers, review with author and rating, pros/cons notes); none otherwise.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Box\SchemaBuilder`, `Interface\Front\SchemaMarkup`; the four schema attributes | `wp_head` |

**Files:** create `src/Application/Box/SchemaBuilder.php`, `src/Interface/Front/SchemaMarkup.php`, `tests/Unit/Application/SchemaBuilderTest.php`; modify `blocks/product-box/block.json` (the four schema attributes), `src/Plugin.php`, `tests/E2E/acceptance/F5.spec.ts` (AC17).

- [ ] **Step 1: Failing tests (AC17; unit `SchemaBuilderTest`).**
- [ ] **Step 2: Builder.**
- [ ] **Step 3: Print.**
- [ ] **Step 4: Run and look.** View the source of a post whose first box enables Review schema and one that does not; check the JSON-LD with Google's Rich Results Test by hand.
- [ ] **Step 5: Commit** — "Print Lasso's product schema for a box".

---

### Task 21A: The Primary Rating stars

**Feature:** F5 (R14; AC1 rating), F8 AC1-AC6

**Why:** the Primary Rating is the stars readers see in the box (F5 R14; F8).

**Depends on:** 18B, F1 14C.

**User action:** a product with a visible Primary Rating shows stars and the value (label when its toggle is on).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| stars partial, rules | `BoxBuilder` |

**Files:** create the stars partial under `templates/front/`; modify `src/Application/Box/BoxBuilder.php`, `assets/front/box.css`, `tests/E2E/acceptance/{F5,F8}.spec.ts` (F8 AC1-AC6).

- [ ] **Step 1: Failing tests (F8).**
- [ ] **Step 2: Builder and partial.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Show the rating in the box".

---

### Task 21B: Custom fields and Pros & Cons in the box

**Feature:** F5 (R23, R30-R34; AC18)

**Why:** the box prints a product's visible custom fields by type, among them Pros and Cons with their icons and colours (F5 R23, R30-R34).

**Depends on:** 21A, 18D.

**User action:** a product's visible fields print in order by type: Pros/Cons with icons and colours, lists, rating
fields, text, numbers.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `BoxField`, field partials, field rules | `BoxBuilder` |

**Files:** create `src/Application/Box/BoxField.php` and the field partials under `templates/front/`; modify `src/Application/Box/BoxBuilder.php`, `assets/front/box.css`, `tests/E2E/acceptance/F5.spec.ts` (AC18).

- [ ] **Step 1: Failing tests (AC18).**
- [ ] **Step 2: Builder.**
- [ ] **Step 3: Partials and CSS.**
- [ ] **Step 4: Run and look.** A product with Pros, Cons, a list, a rating field and text at 1280 and 390 px beside the `themes/` baselines.
- [ ] **Step 5: Commit** — "Show custom fields and Pros & Cons in the box".

---

### Task 21C: The Copy to Clipboard field

**Feature:** F5 (R35, R36; AC19)

**Why:** Lasso's Copy to Clipboard field needs its script and button to copy the value (F5 R35, R36).

**Depends on:** 21B, 18C.

**User action:** the field shows its value and a button ("Copy to clipboard") that copies it.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| copy partial; `copy-clipboard.js` behaviour | `BoxBuilder`, the page |

**Files:** create the copy partial under `templates/front/`; modify `assets/front/copy-clipboard.js`, `src/Application/Box/BoxBuilder.php`, `tests/E2E/acceptance/F5.spec.ts` (AC19).

- [ ] **Step 1: Failing test (AC19).**
- [ ] **Step 2: Partial and script.**
- [ ] **Step 3: Run and look.** Click the copy button on a post at 1280 px and paste the value elsewhere.
- [ ] **Step 4: Commit** — "Copy a field's value from the box".

---

### Task 22A: Price, discount and Prime

**Feature:** F5 (R18-R21, R28; D7; AC20)

**Why:** price, the struck discount and Prime each follow their own Lasso rule, and the Single box and the classic and advanced engines read them differently (F5 R18-R21, R28).

**Depends on:** 18J.

**User action:** a product's price shows by its Show Price (else the site's), and in advanced rendering when the Price
element is on or its own Show Price is on; the struck basis price only with "Show Amazon discount pricing" (new
Display/Amazon key); Prime for every Amazon product with Show Prime on (F5 R20, AC20; whether the product page keeps its own Prime switch is C1 N2).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Box\PriceRule`; setting `show_amazon_discount_pricing` | `BoxBuilder` |

**Files:** create `src/Domain/Box/PriceRule.php`, `tests/Unit/Domain/Box/PriceRuleTest.php`; modify `src/Application/Box/BoxBuilder.php`, the settings class (`show_amazon_discount_pricing`), `tests/E2E/acceptance/F5.spec.ts` (AC20).

- [ ] **Step 1: Failing tests (AC20; unit).**
- [ ] **Step 2: Rule.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Show price and Prime by Lasso's rules".

---

### Task 22B: Disclosure

**Feature:** F5 (R24, R25; AC21), F16 AC9 (single)

**Why:** the disclosure's text and visibility follow the product first and the site second (F5 R24, R25).

**Depends on:** 18A.

**User action:** the product's text or the site's; shown when the product's Show Disclosure is on.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Box\DisclosureRule` | `BoxBuilder` |

**Files:** create `src/Domain/Box/DisclosureRule.php`, `tests/Unit/Domain/Box/DisclosureRuleTest.php`; modify `src/Application/Box/BoxBuilder.php`, `tests/E2E/acceptance/F5.spec.ts` (AC21).

- [ ] **Step 1: Failing tests (AC21).**
- [ ] **Step 2: Rule.**
- [ ] **Step 3: Run and look.** A box with the disclosure on, off, and with its own text, at 1280 and 390 px.
- [ ] **Step 4: Commit** — "Show the disclosure by Lasso's rules".

---

### Task 22C: Timestamp

**Feature:** F5 (R29; D6; AC22)

**Why:** the "as of" line and its tooltip show only where Lasso shows them (F5 R29, R27).

**Depends on:** 22A; C1B-C1C (Phase 2) for real values, C1E (Phase 7) keeps them fresh; the tests seed a time.

**User action:** an Amazon product with a known update time shows it with the tooltip where Lasso does.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Box\TimestampRule`; `_affibloom_amazon_updated` read | `BoxBuilder` |

**Files:** create `src/Domain/Box/TimestampRule.php`, `tests/Unit/Domain/Box/TimestampRuleTest.php`; modify `src/Application/Box/BoxBuilder.php`, the date partial under `templates/front/`, `tests/E2E/acceptance/F5.spec.ts` (AC22).

- [ ] **Step 1: Failing tests (AC22, a seeded update time).**
- [ ] **Step 2: Rule and partial.**
- [ ] **Step 3: Run and look.** A seeded Amazon product at 1280 and 390 px; hover the info icon for the tooltip.
- [ ] **Step 4: Commit** — "Show the price timestamp".

---

### Task 22D: dropped (decision B9)

Brag mode is left out. The section is in `archive/phase-3-superseded.md`.

---

### Task 22E: SiteStripe box

**Feature:** F5 (R4, R13, R83; AC24)

**Why:** SiteStripe boxes are a separate, narrow card that Lasso draws only when Keep Site Stripe UI is on (F5 R13).

**Depends on:** 18B, 18D, 20A (the attribute).

**User action:** with Keep Site Stripe UI on, a box with `sitestripe` draws Lasso's narrow card.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `templates/front/box-sitestripe.php`; attributes `sitestripe`, `sitestripeStyle`; rules | `BoxHtml` |

**Files:** create `templates/front/box-sitestripe.php`; modify `blocks/product-box/block.json` (`sitestripe`, `sitestripeStyle`), `assets/front/box.css`, `src/Interface/Front/BoxHtml.php`, `tests/E2E/acceptance/F5.spec.ts` (AC24).

- [ ] **Step 1: Failing test (AC24).**
- [ ] **Step 2: Template and CSS.**
- [ ] **Step 3: Run and look.** Keep Site Stripe UI on and off, a box with `sitestripe` at 1280 and 390 px beside Lasso's baseline.
- [ ] **Step 4: Commit** — "Draw the SiteStripe box".

---

### Task 10D: Settings → Display — the live preview

**Feature:** F16 AC2; F5 (R85, R94-R99; D11; AC25)

**Why:** Settings → Display must show the real box with unsaved values; the box exists only now (F5 R94-R99; F16 AC2).

**Depends on:** 18A-18K, 19A, 21B, 22A-22C (22D dropped: brag mode, decision B9).

**User action:** every change on Settings → Display redraws the sample box (Lasso's sample: "Essentialism …", Prime,
badge "Our Pick", demo Pros/Cons; Grid/List three items) at desktop or phone width with the unsaved values; links
inert; a spinner while loading; an error toast on failure.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Rest\DisplayPreviewController` (`POST /display-preview`, `Capabilities::SETTINGS`), `Application\Display\PreviewProduct` | `display-settings.js` |
| the preview frame's redraw | `DisplayScreen` |

**Files:** create `src/Interface/Rest/DisplayPreviewController.php`, `src/Application/Display/PreviewProduct.php`; modify `src/Interface/Admin/DisplayScreen.php`, `assets/admin/display-settings.js`, `src/Plugin.php`, `tests/E2E/acceptance/F5.spec.ts` (AC25) and the F16 spec (AC2).

- [ ] **Step 1: Failing test (AC25).**
- [ ] **Step 2: Route and sample.**
- [ ] **Step 3: Script.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Preview the Display settings on the real box".

---

### Task 14E: The product page — the live preview

**Feature:** F1 B2-B4; F5 (R26, R86, R100-R103; D11; AC26)

**Why:** the product page must show the real box while the editor changes the form (F5 R100-R103; F1 B2-B4).

**Depends on:** 10D (shared pieces), 15A.

**User action:** the product page shows the real box; a theme change redraws it without saving; typing updates name,
button texts, second button, badge, price, disclosure and description at once; the image opens the media library.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Rest\ProductPreviewController` (`POST /products/{id}/preview`, `Capabilities::PRODUCTS`) | `product.js` |
| live-typing updates in `product.js` | the product page |

**Files:** create `src/Interface/Rest/ProductPreviewController.php`; modify `assets/admin/product.js`, `src/Plugin.php`, `tests/E2E/acceptance/F5.spec.ts` (AC26).

- [ ] **Step 1: Failing test (AC26).**
- [ ] **Step 2: Route.**
- [ ] **Step 3: Script.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Preview the product's box while editing".

---

### Task C1F: Price, discount and timestamp

**Feature:** C1 (R48-R53, D10, N3; AC11)

**Without credentials:** shows stored prices per N3's answer

**Why:** the box shows Amazon prices, the struck-through basis and the "as of" line exactly as Lasso.

**Depends on:** C1C; Phase 3 Tasks 18A-18K, 19A, 20A-20D (box); F1 14B (read-only price).

**User action:** reader sees the price block; editor's product page shows the same read-only price.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Amazon\{PriceFormat, DiscountPrice}`, `AmazonPriceView` in `BoxBuilder` | box templates, product page |

**Files:** box templates, `BoxBuilder.php`, `ProductScreen.php`; `PriceFormatTest.php`, `DiscountPriceTest.php`; `C1.spec.ts` AC11; baseline check.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Domain.**
- [ ] **Step 3: Box and page.**
- [ ] **Step 4: Run and look** (pixel baselines).
- [ ] **Step 5: Commit** — "Show Amazon price, discount and time as Lasso does".

---

### Task C1G: Prime by Lasso's rule

**Feature:** C1 (R46, R47, D9, N2; AC12)

**Without credentials:** works

**Why:** Lasso shows the Prime logo on every Amazon product with an ASIN while the setting is on.

**Depends on:** Task 22A (Prime) and Tasks 18A-18B (its markup and asset); N2's answer.

**User action:** a reader sees the Prime logo on every Amazon product that has an ASIN while Show Prime is on, and on no other product; the editor's product page follows N2's answer (drop the per-product switch, or keep it as an opt-out that defaults on).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Amazon\PrimeRule` (setting + Amazon + ASIN) | `BoxBuilder` |
| the product page's Prime switch, per N2 | `product-display-card.php` |

**Files:** `BoxBuilder.php`, `PrimeRuleTest.php`, `product-display-card.php` (per N2), `C1.spec.ts` AC12.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Rule and box.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Show the Prime logo by Lasso's rule".

---

### Task 18S1: `[affibloom id]` draws a product's box

**Feature:** F31 (R8, R14, R19, A1, A2, A5, A31, A39, D1, D2, D4, D5; AC1)

**Why:** the user keeps the shortcode (deviation 48); everything else in F31 builds on this entry point.

**Depends on:** Tasks 18A-18K, 19A, 20A-20D, 21A-21C, 22A-22E (the box: `BoxHtml`, `BoxOptions`, `BoxAssets`), Task 18A's repoint resolver (F1 D14), F16
(themes per type).

**User action:** an editor types `[affibloom id="123"]` in a post; the reader sees the same box the block draws.
`slug="<product slug>"` works too and wins over `id`. A retired product shows its target. A draft, trashed or missing
product shows nothing (also to the editor); the page is otherwise unchanged. The box's container has
`id="lasso-anchor-id-123-1"` (second box of the page: `-2`), or `anchor_id`'s value. `theme="Cactus"` draws Cactus's
layout.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Front\AffibloomShortcode` (Module; `add_shortcode( 'affibloom' )` on `init`) | WordPress, `Plugin` |
| `Domain\Shortcode\{ShortcodeAttributes, DisplayKind, Address, DisplayRequest, AnchorId}` (single kind only) | the shortcode |
| `Application\Shortcode\ShortcodeRenderer::render()` (single) | the shortcode |
| `ProductResolver::published()` gains `slug`; `PostTypeProductRepository::by_slug()` | the renderer |
| `BoxOptions::$theme`, `$anchor_id`; `BoxAssets::has_box()` sees `[affibloom` | `BoxHtml`, `wp_enqueue_scripts` |
| action `affibloom_box_failed` and the debug-log line (D4) | the renderer |

**Files:** create the classes above, `tests/E2E/acceptance/F31.spec.ts` (AC1), `tests/Unit/Domain/Shortcode/
{ShortcodeAttributesTest, DisplayRequestTest, AnchorIdTest, AddressTest}.php`, `tests/Integration/
AffibloomShortcodeTest.php`; modify `src/Plugin.php`, `BoxOptions.php`, `BoxBuilder.php`, `BoxAssets.php`.

- [ ] **Step 1: Failing tests.** AC1 (a product made through WP-CLI, a post with `[affibloom id]`, `[affibloom slug]`,
  a draft product, a trashed one, a retired one, two boxes for the anchor counter, `theme`). Unit: R4 positional repair,
  R5 `&quot;`, slug over id, anchor ids. Expected red: the shortcode prints as text.
- [ ] **Step 2: Domain** (attributes, request, address, anchor).
- [ ] **Step 3: Renderer and resolver.**
- [ ] **Step 4: The shortcode module and assets.**
- [ ] **Step 5: Run and look** (the box beside the same product's block box, both widths; view source for the id).
- [ ] **Step 6: Commit** — "Place a product box with [affibloom id]".

---

### Task 18S2: Text and link overrides

**Feature:** F31 (A18-A22, A32, A33, A35, A36, D8, N2; AC2)

**Why:** most of the site's boxes override their badge, description or title (113, 119, 34 of 250); `[affibloom]`
must say all Lasso says.

**Depends on:** 18S1; Task 19A (second button), Tasks 20A-20D (per-box badge, description and the rest), F29 (buttons 3+, Task 14H).

**User action:** `title="…"` / `title="hide"`; `title_url` links the title and the image (target and rel unchanged);
`title_type="h3"` wraps the title (anything outside `h2`-`h6`, or `h1` per N2, is ignored and logged);
`description="…"` (`lasso-br-code` → line break) / `hide`; `badge="…"` / `hide`; `image_url`, `image_alt`;
`primary_url`, `primary_text`; `secondary_url`, `secondary_text` (the first extra button, F29 B6; hidden on the page
it links to). Each changes this box only.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `BoxOptions` fields `title`, `title_url`, `title_type`, `image_url`, `image_alt`, `primary_url`, `primary_text`, `secondary_url`, `secondary_text` (from Task 27A's list) and `BoxOptions::from_request()` | the renderer |
| `BoxBuilder` applies them (A32: `primary_url` also becomes the default title/image link) | `BoxHtml` |
| `templates/box.php`: no title when the name is empty; `title_type` wrapper | `BoxHtml` |

**Files:** the above; `tests/Unit/Application/BoxOptionsTest.php` (one test per attribute), F31 **AC2**.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Options and builder.**
- [ ] **Step 3: Template.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Override a box's text and links from the shortcode".

---

### Task 18S3: Value overrides

**Feature:** F31 (A11 mobile/desktop, A23-A30, A37, A38; AC3)

**Why:** Lasso's shortcode overrides price, Prime, ratings, pros and cons, fields, disclosure, layout and tracking ID
per box.

**Depends on:** 18S2; Task 21A (rating), Task 22A (Prime), F2 Tasks 12A-12E and Task 10F (tracking IDs and the whitelist), custom fields (Tasks FL1-FL5, F26).

**User action:** `price="hide|show|$59"`, `prime="1"`, `basis_price="$80"` (struck through), `rating="4.5|hide"`,
`pros`/`cons` (`-br-` new line), `pros_label`/`cons_label`, `field="hide"`, `show_pros_cons`, `disclosure_text`,
`style="mobile|desktop"`, `tracking_id` (used only when whitelisted, else the site's).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `BoxOptions` fields for A23-A30, A37, A38, A11 | the renderer |
| `BoxBuilder`: pros/cons replace-or-add, labels, fields hidden, the layout class, Amazon re-tagging through `TrackingIds` | `BoxHtml` |
| the v2 hide attributes for these overrides (R13) | `BoxHtml` |

**Files:** the above; `BoxOptionsTest`, F31 **AC3**.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Options, builder, v2 hides.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Override a box's price, rating, pros and cons from the shortcode".

---

### Task 18S4: `type="button"` and `type="image"`

**Feature:** F31 (R10, A10 button/image/image-link, A11 image, A34; AC4)

**Why:** Lasso places a product as a lone button or a lone image.

**Depends on:** 18S1, 18S2 (overrides `primary_*`, `image_*`, `theme`).

**User action:** `[affibloom id type="button"]` shows the button in the product's theme (`button_type="secondary"`:
the second button when the product has one); `type="image"`, `type="image-link"` or `style="image"` shows its linked
image (500×500, `?src=image` on a non-Amazon permalink); both carry the anchor id.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `DisplayKind::Button`, `::Image` rows in `DisplayRequest` | the renderer |
| `Interface\Front\ButtonHtml`, `ImageHtml`; `templates/front/{button,image}.php` (Lasso's markup, R10) | the renderer |

**Files:** the above; F31 **AC4**; `DisplayRequestTest` rows.

- [ ] **Step 1: Failing tests** (markup equality with Lasso's for one product, both kinds).
- [ ] **Step 2: Kinds,
  views, templates.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Place a product as a button or an image".

---

### Task 18S5: `type="link"`, SiteStripe and inline fields

**Feature:** F31 (R11, A10 link, A12-A14, A40, D2; AC5)

**Why:** Lasso's last three single-product forms: a text link, the SiteStripe box, one value inline.

**Depends on:** 18S4; the SiteStripe switch in Amazon settings (Task 10E, Keep Site Stripe UI; F17, requirements section 5).

**User action:** `type="link" label="…"` prints a text link (else the title, else the name); `sitestripe="true"`
(with `sitestripe_style`) prints the SiteStripe box when the setting is on, else the normal box; `type="field"
value="price"` (any of the 15 parts, `thumb` with `image_size`, `image_class`, `image_align`, `image_width`,
`image_height`) prints that value in `span.lasso-aawp-fields`; an unknown part prints nothing.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `DisplayKind::Link`, `::SiteStripe`, `::Field`; `Domain\Shortcode\FieldPart` | `DisplayRequest` |
| `Interface\Front\{LinkHtml, SiteStripeHtml, FieldHtml}`; `templates/front/{sitestripe,field}.php` | the renderer |

**Files:** the above; F31 **AC5**; `FieldPartTest`.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Kinds and views.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Place a product as a text link, a SiteStripe box or one inline value".

---

### Task 18S6: Address a product by its Amazon URL or destination

**Feature:** F31 (A6, A7; AC6)

**Why:** Lasso's `amazon_url` and `geni_url` place a product without knowing its ID.

**Depends on:** 18S1; the product's ASIN and country (F2), `_affibloom_url`.

**User action:** `[affibloom amazon_url="https://www.amazon.com/dp/B0…"]` shows the published product with that
ASIN and country; `geni_url="<destination>"` the product with that destination; none → nothing.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Address` by ASIN and by destination | `ProductResolver` |
| `PostTypeProductRepository::by_asin()`, `by_destination()` (published only) | `ProductResolver` |
| `AmazonUrl` parsing (exists) | `ProductResolver` |

**Files:** the above; F31 **AC6**; `AddressTest`.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Lookups.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Find a box's product by its Amazon URL or destination".

---

### Task 18S7: Group displays by shortcode

**Feature:** F31 (R12, A8, A44-A49, D2; AC7)

**Why:** Lasso's grid, list and gallery of a group (F23 R24) are placed only by shortcode or block.

**Depends on:** 18S1-18S3; F23 (`GroupService::members()`, Tasks G2-G4); the group-display renderers DT2 (grid), DT4 (list) and DT6 (gallery), which come later in this phase (see Cross-phase notes).

**User action:** `[affibloom type="grid" group="<slug>"]` (`category=` and `categories=` too) shows the group's
products in its order; `latest="true"` newest first; `limit`, `columns` (grid 1-5, gallery 2-5), `compact="true"`,
`bullets`, `disclosure="enable|disable"`; every per-box override applies to every card; an empty or unknown group
shows the wrapper alone.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `DisplayKind::Grid`, `::List`, `::Gallery`; `Address` by group | the renderer |
| the call into the group-display renderers (F30 DT2 `GridHtml`, DT4 `ListHtml`, DT6 `GalleryHtml`; this draft calls them `GroupHtml`) | the renderer |

**Files:** F31 **AC7**; `DisplayRequestTest` rows.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Kinds and the call.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Place a group as a grid, list or gallery with [affibloom]".

---

### Task 18S9: Boxes in widgets, on AMP pages and in feeds

**Feature:** F31 (R16-R19, A17, D11; AC9)

**Why:** Lasso marks widget boxes for the phone layout and prints its CSS inline on AMP pages.

**Depends on:** 18S1.

**User action:** a text widget with `[affibloom id]` shows the box in the phone layout; an AMP page (AMP plugin or AMP
for WP) shows the box styled; the feed shows the box.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Front\WidgetShortcodes` (`widget_text`, 1; not with Elementor active; only `[affibloom ` / `[lasso `) | WordPress |
| `Interface\Front\AmpBoxCss` (`amp_post_template_css`, 1000; once) and the AMP detection | the renderer |
| Jetpack Photon skip for the box's images only | the renderer |

**Files:** the above; F31 **AC9**.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Modules.**
- [ ] **Step 3: Run and look** (a widget, `?amp=1`,
  `/feed/`).
- [ ] **Step 4: Commit** — "Draw boxes in widgets, AMP pages and feeds".

---

### Task 18S10: The Dashboard row's Shortcode button

**Feature:** F31 (R30, D9; AC10); F19 (Actions)

**Why:** the Actions cell has carried a placeholder since Task 14; the shortcode now exists.

**Depends on:** 18S1; Task 13C (the Dashboard row's Actions cell, which holds the Shortcode placeholder; the draft names it "F19 Task 14").

**User action:** **Shortcode** (icon, title "Copy shortcode") copies `[affibloom id="<id>"]`; the label reads "Copied"
for 1.6 s (announced); in quick edit it does nothing; a refused copy shows "Could not copy shortcode.".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\ShortcodeText::product()` | `templates/admin/partials/product-row.php` |
| the shared `affibloomCopy()` in `admin.js` (Clipboard API, textarea fallback) | `dashboard.js` |

**Files:** `templates/admin/partials/product-row.php`, `assets/admin/{admin,dashboard}.js`, `src/Interface/
ShortcodeText.php`; F31 **AC10**, F19 Actions rerun.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Text, button, script.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Copy a product's shortcode from its Dashboard row".

---

### Task 18S11: The Share kit's Copy shortcode

**Feature:** F31 (R31, D9; AC11); F1 (B9, D4)

**Why:** the product page's Share kit shows "Coming soon" for the shortcode (F1 D4).

**Depends on:** 18S10 (`ShortcodeText`, `affibloomCopy()`).

**User action:** **Copy shortcode** copies `[affibloom id="<id>"]`, "Copied!" for 1.5 s; disabled on a new product.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the Copy shortcode button in `templates/admin/product.php` | the product page's Share kit |
| a second caller of `affibloomCopy()` | `product-save.js` |

**Files:** the above; F31 **AC11**, F1 AC (Share kit) rerun.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: The button.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Copy a product's shortcode from its Share kit".

---

### Task 18S12: A group's Copy Shortcode

**Feature:** F31 (R32, D9; AC12); F23 (R5, D5)

**Why:** F23 D5 left Copy Shortcode "Coming soon" until the shortcode exists.

**Depends on:** 18S7 (group displays), 18S10, F23 G2.

**User action:** on a saved group, **Copy Shortcode** ("Copy this Display to your clipboard.") copies
`[affibloom type="grid" group="<slug>"]`; the tooltip reads "Copied!".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `ShortcodeText::group()` | `templates/admin/group.php` |
| the Copy Shortcode control in `templates/admin/group.php` | `groups.js` (calls `affibloomCopy()`) |

**Files:** the above; F31 **AC12**, F23 AC3 rerun.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Control.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Copy a group's display shortcode".

---

### Task 18S13: A table's Copy Shortcode (moved to Phase 7 — this copy is for reference only)

**Feature:** F31 (R33, D9; AC13)

**Why:** F25's table builder page shows "Coming soon" for Copy Shortcode until this task (F25 T3, F31 R33).

**Depends on:** F25 T3 (the table builder page) and T12 (the table shortcode), both Phase 7; 18S10. (F31's own 18S8 is superseded by F25 T12.)

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

### Task 18S14: Review and pros/cons schema in the page head

**Feature:** F31 (R29, A52, D12; AC14)

**Why:** Lasso prints Product JSON-LD for the first box that asks for it; 248 boxes on this site carry the block's
`schema_*` attributes.

**Depends on:** 18S1, 18S3 (pros, cons, rating); F4 (Phase 4; the block's attributes, read too).

**User action:** a post whose first box has `schema_review="enable"` (or `schema_pros_cons`) shows one
`script.lasso-schema-markup-output` in its head (Lasso's shape); others none.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Front\BoxSchema` | `wp_head` |
| `Application\Shortcode\SchemaBuilder` (pure: product, rating, price, currency, pros/cons split on `|||`) | `BoxSchema` |

**Files:** the above; F31 **AC14**; `SchemaBuilderTest`.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Builder and module.**
- [ ] **Step 3: Run and look** (Rich
  Results test by hand).
- [ ] **Step 4: Commit** — "Print a box's review schema in the page head".

---

### Task DT1: Lasso's own render of the other display types, as baselines

**Feature:** F30 (AC1; N4)

**Why:** pixel parity (ADR-6) needs Lasso's render, and no page on the site has ever shown these types. Lasso must not
be activated on the Local copy; staging runs it.

**Depends on:** the user's answer to N4 (a test group and a draft page on staging); Task 5's baseline tooling
(`tools/baselines.config.ts`).

**User action:** the user (or Claude with the user's go-ahead) makes on staging, through wp-admin, one Lasso group of
four existing products and one draft page holding: a grid (default and `columns="2" limit="3"`), a list with each
`bullets` value, a gallery, a button in both `button_type`s, an image, a SiteStripe display (Keep Site Stripe UI on
for the capture, then back off), and Grid and List under the site's settings. Claude runs the capture; both items are
then deleted.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `tests/fixtures/baselines/displays/<type>-<variant>-{desktop,mobile}.png` and `displays.json` (the shortcodes, product ids, settings used) | DT2-DT8's visual checks |
| a `displays` project in `tools/baselines.config.ts` (staging URL from the user's request, never a page found elsewhere) | `npx playwright test -c tools/baselines.config.ts --project displays` |

**Files:** modify `tools/baselines.config.ts`; create the fixture folder; test `tests/E2E/acceptance/F30.spec.ts`
(AC1: every listed baseline exists and is a PNG of the stated size).

- [ ] **Step 1: Failing test.** AC1 lists the expected files; red: none exist.
- [ ] **Step 2: The staging page.** With the user's go-ahead, make the group and the draft page; record the
  shortcodes in `displays.json`.
- [ ] **Step 3: Capture** at 1280 and 390 px; turn Keep Site Stripe UI back off; delete the page and the group.
- [ ] **Step 4: Run and look.** AC1 green; look at each image for loading spinners or cookie banners.
- [ ] **Step 5: Commit** — "Capture Lasso's grid, list, gallery, button, image and SiteStripe baselines".

---

### Task DT2: A group shown as a grid

**Feature:** F30 (R2 group dispatch, R5-R9, R11, R12, R15, R16 customised, R17-R19, R20 customised, R52-R56 grid
clauses, R61 grid; D1-D3, D5, D15-D18, D21, D22; AC2-AC4, AC13 groups, AC14)

**Why:** the Grid is Lasso's main multi-product display and the reason groups exist (F23 R3); the site's Grid settings
(Cutter, balanced, price hidden) are already stored.

**Depends on:** Tasks 18A-18K, 19A, 20A-20D, 21A-21C, 22A-22E (the Single box and its themes), 18S1 (the shortcode), F23 G1-G4
(`GroupService::members()`), F16 10A-10E (Grid bucket, Disclosure in Grids, brag), DT1.

**User action:** an editor puts `[affibloom type="grid" group="<slug>"]` (optionally `limit`, `latest`,
`disclosure`, the per-card overrides) in a post. A reader sees the group's published products in group order as boxes
in the Grid theme and settings, up to the site's column count, one column on a phone, the disclosure once below when
Disclosure in Grids is on. An unknown or empty group shows the empty wrapper (and the disclosure, as Lasso).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Display\{DisplayAttributes (grid clauses), DisplayRequest, GridDisclosure, AnchorId}` | `AffibloomShortcode` (18S), `DisplayHtml` |
| `Application\Display\GroupDisplayService::products( slug, limit, latest )` | `GridHtml` |
| `Interface\Front\{DisplayHtml, GridHtml, AnchorIds}`; `templates/display/grid.php` | 18S's shortcode handler |
| the grid section of `assets/front/box.css` (customised mode) and `has_shortcode()` in `BoxAssets` | `BoxAssets` |
| (no brag mark: decision B9) | `GridHtml` |

**Files:** create the classes and template above; modify 18S1's shortcode handler, `BoxAssets`, `box.css`,
`src/Plugin.php`; tests `tests/E2E/acceptance/F30.spec.ts` (AC2-AC4, AC13 groups, AC14),
`tests/Unit/Domain/Display/{DisplayAttributesTest,GridDisclosureTest,AnchorIdTest}.php`,
`tests/Unit/Application/GroupDisplayServiceTest.php`, `tests/Integration/GridDisplayTest.php`.

- [ ] **Step 1: Failing tests.** AC2-AC4, AC13, AC14 on draft pages made through WP-CLI (a group of four products via
  `term create affibloom_group` and G4's order); unit tables for limit (R6), disclosure (R17), anchor ids (D16),
  `latest`. Expected red: the shortcode prints nothing for `type="grid"`.
- [ ] **Step 2: Domain and service.** `DisplayAttributes` grid clauses; `GroupDisplayService` over `members()` and one
  batched product read.
- [ ] **Step 3: Renderer and template.** Wrapper, one grid of Single boxes built with `DisplayType::grid()` (theme,
  labels, density, elements, per-card price R18), brag mark, grid disclosure.
- [ ] **Step 4: CSS.** The customised grid (R20): auto-fill columns ≥ 240 px up to the site count, gap, one column
  below 768 px, row gap ≥ 30 px, ribbon room, equal-height cards with a shared button row; card disclosures hidden.
- [ ] **Step 5: Run and look.** Checks; visual diff with DT1's grid baselines at 1280/768/390 px; by hand: an unknown
  group, an empty group with Disclosure in Grids on, `limit="0"`, `latest="true"`, a trashed member.
- [ ] **Step 6: Commit** — "Show a group as a grid, as Lasso does".

---

### Task DT3: The grid with default Display settings — rows, `columns`, `compact`

**Feature:** F30 (R13, R14, R16 classic, R20 classic; N1; AC5)

**Why:** with untouched Display settings Lasso draws grids differently (rows of N, the `columns` and `compact`
attributes); a site that resets its settings must get that grid.

**Depends on:** DT2; the user's answer to N1.

**User action:** with the Display settings at their defaults, `[affibloom type="grid" group="…" columns="4"
compact="true"]` shows rows of four cards without descriptions, fields or disclosures; without `columns`, rows of
two. With the settings customised, Affibloom's shortcode honours `columns` 1-5 and `compact="hide"` too if the user
chose N1's recommendation (`[lasso]` keeps Lasso's behaviour, Task 27A).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Display\GridColumns` (R14 per mode; N1's rule for Affibloom's shortcode) and the `compact` rule in `DisplayAttributes` | `GridHtml` |
| the classic branch of `templates/display/grid.php` (rows of N) and the classic grid CSS (R20) | `GridHtml`, `BoxAssets` |

**Files:** the above; tests F30 **AC5**, `GridColumnsTest`, a `compact` table in `DisplayAttributesTest`.

- [ ] **Step 1: Failing tests.** AC5 with `affibloom_settings.display` reset to defaults; unit tables for columns
  (attribute, site count, default 2; gallery excluded) and compact values.
- [ ] **Step 2: Rule and branch.**
- [ ] **Step 3: Classic CSS** (50/33.3/25/20 %, 250 px images, theme title sizes
  at 1300/1200 px, full width below 768 px).
- [ ] **Step 4: Run and look** (both modes side by side; DT1's `columns="2"` baseline).
- [ ] **Step 5: Commit** — "Draw classic grids in rows and honour columns and compact".

---

### Task DT4: A group shown as a list, with the phone swipe row

**Feature:** F30 (R21-R24; D6, D7, D8; AC6, AC7)

**Why:** Lasso's List is the second group display, with its own card template, bullets and the only use of Mobile
overflow.

**Depends on:** DT2 (the dispatcher, `GroupDisplayService`), F16 10C (List layout and Mobile overflow), DT1.

**User action:** `[affibloom type="list" group="…" bullets="roman"]` shows a numbered (or lettered, Roman, bulleted,
plain) list of the group's products: title, image, rating, price, description, fields, buttons, the product's own
disclosure, date — never a badge — in the List theme and settings. With Mobile overflow "Swipe row", below 768 px the
list scrolls sideways a card at a time.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Display\ListBullets`; list clauses in `DisplayAttributes` | `ListHtml` |
| `Application\Display\ListCardBuilder` (from the Single box's view, list bucket, R22's element rules) | `ListHtml` |
| `Interface\Front\ListHtml`; `templates/display/{list,list-card}.php`; list CSS incl. the swipe row | `DisplayHtml`, `BoxAssets` |

**Files:** the above; tests F30 **AC6, AC7**, `ListBulletsTest`, `tests/Integration/ListDisplayTest.php`.

- [ ] **Step 1: Failing tests** (each `bullets` value and an unknown one; a product with Show Disclosure off; phone
  width with Stack and with Swipe row).
- [ ] **Step 2: Bullets, card builder, renderer, templates.**
- [ ] **Step 3: CSS** (classic list rules, customised
  flex column with the density gap, the swipe row: no wrap, scroll, snap, 62 % items).
- [ ] **Step 4: Run and look** (DT1's list baselines; swipe by touch emulation and by Tab through the cards).
- [ ] **Step 5: Commit** — "Show a group as a list, with Lasso's bullets and phone swipe row".

---

### Task DT5: A product shown as an image

**Feature:** F30 (R4, R26-R28; D11; AC10)

**Why:** Lasso's Image display (also reached from AmaLinks' `image-link` and EasyAzon images, Task 27A) is one linked
product image; the gallery (DT6) is built from it.

**Depends on:** Task 18A (product resolution, link attributes), 18S1, DT2 (dispatcher), DT1.

**User action:** `[affibloom type="image" product="<id>"]` shows the product's image, centred, linked to the product
with its target and rel; `image_url` and `image_alt` override the picture and the alt text. A missing product shows
nothing.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| image clauses in `DisplayAttributes` (`image_url`, `image_alt`; `style="image"` mapped by Task 27A) | `ImageHtml` |
| `Interface\Front\ImageHtml`; `templates/display/image.php`; image CSS; the lazy-load variants (R27) | `DisplayHtml` (and DT6) |

**Files:** the above; tests F30 **AC10**, `tests/Integration/ImageDisplayTest.php` (Ezoic / WP Rocket variants with
stubbed detection).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Renderer, template, CSS.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Show a product as a linked image".

---

### Task DT6: A group shown as a gallery

**Feature:** F30 (R25; D9; AC8)

**Why:** the gallery is the third group display: the group's images in columns.

**Depends on:** DT2 (group service), DT5 (the image renderer, its second caller), DT1.

**User action:** `[affibloom type="gallery" group="…" columns="3"]` shows the group's products as linked images in
rows of three (2-5; four when absent), one per row on a phone.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| gallery clauses in `DisplayAttributes` (columns 2-5, default 4) | `GalleryHtml` |
| `Interface\Front\GalleryHtml`; `templates/display/gallery.php`; gallery CSS (the classic grid widths) | `DisplayHtml` |

**Files:** the above; tests F30 **AC8**.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Renderer and template** (rows of N, the wrapper keeps the only
  id).
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Show a group as an image gallery".

---

### Task DT7: A product shown as a button

**Feature:** F30 (R29-R33; D10; AC9)

**Why:** Lasso's Button display places one product's call-to-action anywhere in text (EasyAzon CTAs map to it).

**Depends on:** Tasks 18A, 19A (buttons, link attributes, second button), 18S1, DT2, DT1.

**User action:** `[affibloom type="button" product="<id>"]` shows the product's primary button in the Single theme;
`primary_text`/`primary_url` override it; `button_type="secondary"` shows the secondary button (its URL overridable
with `primary_url`, its text with `secondary_text`) or, without a secondary URL, the primary.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| button clauses in `DisplayAttributes` | `ButtonHtml` |
| `Interface\Front\ButtonHtml`; `templates/display/button.php`; button CSS | `DisplayHtml` |

**Files:** the above; tests F30 **AC9**, a `button_type` table in `DisplayAttributesTest`.

- [ ] **Step 1: Failing tests** (both modes, the fallback, icon spans in the text, an unknown theme name).
- [ ] **Step 2: Renderer and template.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Show a product as a lone button".

---

### Task DT8: The SiteStripe card

**Feature:** F30 (R34-R36, R38 read; D12; AC11)

**Why:** `sitestripe="true"` displays (written by Lasso's SiteStripe scan, R37) draw a compact Amazon-style card when
Keep Site Stripe UI is on.

**Depends on:** Task 18A (Single box, its fallback), 10E (Keep Site Stripe UI), 18S1, DT2, DT1.

**User action:** with Keep Site Stripe UI on, `[affibloom product="<id>" sitestripe="true"]` shows a 240 x 400 px card
(image, title, price, Prime, one button), floated left; `sitestripe_style` changes its size. With the setting off, the
same shortcode shows the ordinary Single box.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `sitestripe`, `sitestripe_style` in `DisplayAttributes` (`safecss_filter_attr`) | `SiteStripeHtml` |
| `Application\Display\SiteStripeBuilder`, `Interface\Front\SiteStripeHtml`; `templates/display/sitestripe.php`; CSS | `DisplayHtml` |

**Files:** the above; tests F30 **AC11**.

- [ ] **Step 1: Failing tests** (setting on and off; a style with `expression(` or `url(` removed).
- [ ] **Step 2: Builder, renderer, template, CSS.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Draw SiteStripe cards when Keep Site Stripe UI is on".

---

### Task DT9: Stored AI text

**Feature:** F30 (R39, R40, R41-R42 not built, R43; D14; AC12)

**Why:** `[lasso prompt="…"]` content must keep showing its text without Lasso's cloud; Affibloom never calls a
service on a page view.

**Depends on:** 18S1, DT2 (dispatcher); Tasks 26A-26P (Phase 5) fills the store from Lasso's transients (none on this copy).

**User action:** a post with `[affibloom prompt="…"]` (or a migrated `[lasso prompt="…"]`) shows the stored text for
that prompt; with none stored it shows nothing. No outgoing request happens.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Display\{AiText, AiTextRepository}`; `Infrastructure\WordPress\OptionAiTextRepository` (`affibloom_ai_texts`) | `AiTextService` |
| `Application\Display\AiTextService::find()`; `Interface\Front\AiTextHtml`; `templates/display/ai-text.php`; CSS | `DisplayHtml` |

**Files:** the above; tests F30 **AC12** (a `pre_http_request` guard fails the test on any outgoing call),
`AiTextServiceTest`, `OptionAiTextRepositoryTest` (integration; kses on print).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Store, service, renderer.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Show stored AI text without calling any service".

---

### Task DT10: dropped (decision B5)

AI text is shown from stored text only. The section is in `archive/phase-3-superseded.md`.

---

## Exit check

The phase is done when every task above is ticked (18S13 may be carried to Phase 7, see Cross-phase notes), the user
has run each task's entry point and seen the result, and the following acceptance criteria hold:

- **F5:** AC1-AC26 (the Lab variants match their baselines on desktop and mobile; a page without a box loads no plugin CSS
  or JS with Smart CSS Loading on, AC2; AC3-AC26 as their tasks own them). F5 AC2 is the old phase's own exit criterion.
- **F6:** AC1-AC6 (second button and further buttons). **F7:** AC1-AC12 (per-box overrides). **F8:** AC1-AC6 (rating).
- **F16:** AC2 (the Display preview) and the front-end parts of AC4, AC5, AC6, AC7, AC9 (single) and AC10, plus B1 and B13.
- **F29:** AC3 and AC5 (the box draws buttons 3 and up).
- **C1:** AC11 (price, discount, timestamp) and AC12 (Prime).
- **F31:** AC1-AC7 and AC9-AC12, AC14 (AC13 with Task 18S13, Phase 7 if carried; AC8 is F25's AC16, Phase 7).
- **F30:** AC1-AC14 (AC15 not built: decision B5).

Every box variant passes within 1 % of Lasso's baselines on desktop and mobile; the product page and Settings → Display
show the box as readers will see it.

**Sign-off:** the user signs this phase off after the check by hand. The next phase (4) does not start before that.

## Coverage

Every requirement (R) and acceptance criterion (AC) of the phase's features, and the task that owns it. Rows from the
drafts, merged. **UNOWNED** marks a row with no task.

### F5, F6, F7, F8 (and F16, F29 clauses that these tasks claim)

| R / AC | Task |
|---|---|
| R1, R2, R6, R9-R12, R15-R17, R22, R37-R40, R54; AC3, AC4 | 18A |
| R42, R43; AC1 (amazon, other, no-description), AC5; F16 AC10 | 18B |
| R45-R52; AC2, AC6 | 18C |
| AC7 (D10) | 18D |
| R3, R5, R8; AC8 | 18E |
| R7; AC9 | 18F |
| AC10 | 18G |
| R55-R57; AC11; F16 AC6, AC7 | 18H |
| R58, R59; AC12; F16 AC4 | 18I |
| R60-R63; AC13; F16 AC5; F6 AC6 (with 19A) | 18J |
| R27, R53, R64; AC14; F16 B1, B13 | 18K |
| R41; AC15; AC1 (second-button, badge, prime); F6 AC1-AC6; F29 AC3, AC5 | 19A |
| R66-R69, R76, R79; F7 AC1-AC4 | 20A |
| R65, R73, R75, R80-R82, R87; F7 AC5-AC9 | 20B |
| R70-R72, R74, R77, R78, R84; F7 AC10-AC12; AC16 (with 20A, 20B) | 20C |
| R90-R93; AC17 | 20D |
| R14; AC1 (rating); F8 AC1-AC6 | 21A |
| R23, R30-R34; AC18 | 21B |
| R35, R36; AC19 | 21C |
| R18-R21, R28; AC20 | 22A |
| R24, R25; AC21; F16 AC9 (single) | 22B |
| R29; AC22 | 22C |
| R44; AC23 | not built: decision B9 (22D dropped) |
| R4, R13, R83; AC24 | 22E |
| R85, R94-R99; AC25; F16 AC2 | 10D |
| R26, R86, R100-R103; AC26 | 14E |
| R88 (product resolution by `slug`, repoint, `amazon_url`, `geni_url`) | 18S1, 18S6 (and Task 27A, Phase 5, for `[lasso]`) |
| R89 (the attribute catalogue with help texts) | Task 24A (Phase 4) |
| R104 (Lasso's cloud) | not built (C, never) |

AC3's text lists a few R numbers loosely; this table is the authority. F16's other ACs (AC1, AC3, AC8, AC11-AC13)
belong to Phase 2.

### C1 (Tasks C1F, C1G only)

| Requirement | Task |
|---|---|
| R48-R53, AC11 | C1F |
| R46-R47, AC12 | C1G |
| R1-R2, R7-R45, R55-R63, R64-R76, AC1-AC10, AC13-AC20 | Phase 2 (C1A-C1D, C1I, C1K-C1P) and Phase 7 (C1E, C1H); see those files |
| R3-R6, R65, R70 (Lasso's cloud) | not built (C, never) |

### F31 (Tasks 18S1-18S7, 18S9-18S14)

| Requirement | Task |
|---|---|
| R2 (widget filter) | 18S9 (27H for `[lasso]`, Phase 5) |
| R4, R5 | 18S1 (shared parser; re-tested in 27A, Phase 5) |
| R7 (A1-A2, A5, A31, A39) | 18S1 |
| A6, A7 | 18S6 |
| A8, A44-A49 | 18S7 |
| A10 single / button / image / link, A11-A14 | 18S1 / 18S4 / 18S4 / 18S5 / 18S3, 18S4 / 18S5 |
| A18-A22, A32, A33, A35, A36 | 18S2 |
| A23-A30, A37, A38 | 18S3 |
| A34 | 18S4 |
| A40 | 18S5 |
| A52 | 18S14 |
| R8 | 18S1 (single), each kind in its task |
| R9 | Tasks 18A-22E (F5 coverage) |
| R10 | 18S4 |
| R11 | 18S5 |
| R12 | 18S7 (entry), DT2, DT4, DT6 (markup) |
| R13 | 18S3 (overrides) with F16 (v2 context) |
| R14 | 18S1 |
| R16, R17, R18, R19 | 18S9 |
| R20 | 18S1-18S5 (each template) |
| R29 | 18S14 |
| R30 / R31 / R32 / R33 | 18S10 / 18S11 / 18S12 / 18S13 |
| AC1-AC7 | 18S1-18S7 in order |
| AC9-AC14 | 18S9-18S14 in order |
| AC8; A10 table, A31 table, A50, A51 | F25 T12 (Phase 7); supersedes 18S8 |
| R1, R3, R6, R15, R21-R28; A3, A4, A9, A15-A17, A41-A43, A53, A54; AC15-AC31 | Phase 5 (27A-27I, 27X1-27X8) |
| R34 (the builders in each screen: display modal F4, Display settings F16, product preview F1, tables, MCP) | F4 23C-23F; 18S10-18S12 (assigned 2026-11-14) |
| R35 | F4 24C (assigned 2026-11-14) |
| R2's Elementor integration (`builder_content_data` key filter, the "Lasso Shortcode" widget, `class-elementor.php`) | F4 ED2 (assigned 2026-11-14) |

### F30 (Tasks DT1-DT9; DT10 dropped, decision B5)

| Requirement | Task |
|---|---|
| AC1, N4 | DT1 |
| AC2, AC3, AC4, AC13 (group cases), AC14 | DT2 |
| AC5, N1 | DT3 |
| AC6, AC7 | DT4 |
| AC8 | DT6 |
| AC9 | DT7 |
| AC10, AC13 (missing product) | DT5 |
| AC11 | DT8 |
| AC12 | DT9 |
| AC15, N2 (b) | not built: decision B5 (DT10 dropped) |
| R2 (group dispatch, the grid default), R5, R6, R7, R8, R9, R11, R12, R15, R16 (customised), R17, R18, R19, R20 (customised), R54, R55, R56, R61 (grid), R63 (not reproduced, D3) | DT2 |
| R13, R14, R16 (classic), R20 (classic) | DT3 |
| R21, R22, R23, R24, R61 (list) | DT4 |
| R4, R26, R27, R28 | DT5 |
| R25, R61 (gallery) | DT6 |
| R29, R30, R31, R32, R33 | DT7 |
| R34, R35, R36, R38 (read) | DT8 |
| R39, R40, R41 and R42 (not built, asserted), R43, R61 (AI text) | DT9 |
| R44, R45, R46, R47, R48, R49 | not built: decision B5 (DT10 dropped) |
| R52, R53 (the schema `DisplayAttributes`) | grows per type: DT2 grid, DT3 compact and columns, DT4 list, DT5 image, DT6 gallery, DT7 button, DT8 SiteStripe, DT9 prompt and format |

**Owned outside F30 (stated plainly):** R1 is Tasks 18S1-18S7 (Affibloom's shortcode) and Task 27A (`[lasso]`); R3, R58,
R62, D20 are Task 27A; R10 (element visibility) is Task 18J; R37 and N3 are the Settings → Amazon / link-scan sweep (render
time); R50, R51, R52's panels and D19's editor messages are Phase 4 (F4); R57 is Tasks 35A-35C (where used); R59 is Tasks
10A-10E; R60 and D23 are Task 10D; R38's control is Task 10E. Nothing in F30 is unowned. R52 and R53 are split by type
clause, and R16, R20 and R61 by mode or type, so that each clause has exactly one owner.
