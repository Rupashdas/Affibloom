# Affibloom — Phase 2: Settings and product library — Implementation Plan

> **How to run this plan:** inline, in one working session, task by task, in the order below. Do not hand tasks to subagents. When a step is finished, change its `- [ ]` to `- [x]`.
>
> **Explain as you go:** before each task, say in two or three sentences what the task builds, why it is needed now, and how it will be checked. After the task, say what changed and what the verification showed. No vibe coding: every line written must be one you can explain.
>
> **Shared rules:** the architecture, working method, decisions, global constraints, file structure and API contract are in [`00-overview.md`](00-overview.md). Read it first.

## Status

**Tasks 8 through 17D are done** (history in `progress.md`): Task 8 `aa4da3c`, Task 9 `8035d66`, Task 9A `6ec0f2e` +
Aurora `469829a`; 10A `a6ea427`, hex defaults `2049291`, 10B `87e6ccc`, 10C `a978948`, 10E `51a4584`, tips `3412dbd`,
10F `87620f1`; 11A `597fd4a`, 11B `8f41d04`, 11C `b89ae01`, 11D `f1067d7`, 11E `f830fea`; 12A `5dcfa7a`, 12B `b1db8c5`,
12C `a4b3c52`, 12D `5d1d634`, 12E `e839339`; 13A `99a4fba`, 13B `ad508f4`, 13C `b2ab7b3`, 13D `f32c89f`, 13E
`00dc1af`, spacing `ee6743c`, Add Link endings `6d9a5ef`; 14A `b3686ab`, 14F `7c69cf7`, 14G `c799ff5`, 14H `b50ace2`,
F19 fix `6a743ed`, 14B `9640685`, 14C `0a24290`, 14D `0ee6801`; 15A `59ee8da`, 15B `7e77a89`, 15C `0da4b16`, 15D
`b3202d7`; 16A `852cb00`, redesign `bf282a7`, 16B `674c24c`, 16C `6471087`; 17A `d7a0493`, 17B `f61e12c`, 17C
`c86378f`, 17D `82b6015`. The Lasso import button for the staging build (`447bf1b`, deviation 87) is recorded and is
retired by Task 26O. Tasks 10D and 14E moved to Phase 3.

**The remaining tasks (2X0-2X6, G1-G5, the F9 General-settings tasks, FL1-FL5, TL1-TL2, 13F-13J, ON1-ON2, C1A-C1P)
are planned; gate 4 given by the user 2026-11-14 for every open task** (the whole plan, with the answers in
`open-decisions.md`; for Tasks G1-G5 it was recorded in this file earlier as "Approve, start G1").

This phase's exit check, and the list of acceptance criteria that moved to later phases, is the "Exit check"
section at the end.

## Scope and features

- F1 product page, Dashboard row, delete (Tasks 14-17, done; the gaps the audits found: 2X1, 2X2) — `docs/features/F1/`
- F2 Amazon links (Task 12, done; 2X4) — `docs/features/F2/`
- F16 Display settings (Task 10, done; 2X3, 2X6) — `docs/features/F16/`
- F18 Add A New Link (Task 11, done; 2X5) — `docs/features/F18/`
- F19 Dashboard list (Task 13, done; second pass 13F-13J) — `docs/features/F19/`
- F23 Groups (G1-G5) — `docs/features/F23/`
- F9 Settings → General (Tasks 9B-9L; 9J moves to Phase 5) — `docs/features/F9/`
- F26 Fields (FL1-FL5) — `docs/features/F26/`
- F27 URL variants (V1-V5 moved to Phase 5 on 2026-11-14) — `docs/features/F27/`
- F37 (part) activation and the upgrade routine (TL1, TL2, moved here from Phase 6 on 2026-11-14) — `docs/features/F37/`
- F38 admin shell, navigation and notices (ON1, ON2 only; ON3-ON4 are in Phase 5, ON5-ON10 in Phase 8 (ON10 dropped)) — `docs/features/F38/`
- C1 Amazon product data (C1A-C1D, C1I-C1P; dormant where credentials are needed; C1E-C1H are in Phases 3 and 7) —
  `docs/features/C1/`

## Decisions from Task 6 that change this phase

Decided 2026-11-12; reasons in `docs/decisions/task-6-decisions.md` in the plugin repository. Apply them when the task is worked, and record any code that differs from
the text below as a deviation in `progress.md`.

- Task 8: `Capabilities::PRODUCTS = 'manage_options'` (decision 1); help text reachable by keyboard and screen reader in every screen built here (U3).
- Task 9: no `allow_duplicates` key, toggle or bypass (decision 2) (overtaken: F18 D2 — Lasso has the setting; built in
  9B and 9C); add C2's and C3's settings only when those tasks are written.
- Task 10: the Display button texts accept `<span class="…">` only (decision 5).
- Tasks 11 and 15: the duplicate check has no setting (decision 2) (overtaken: F18 D2, the setting is built in 9B and
  9C); the toast reads "Product saved." (U6).
- Tasks 14 and 15: the Details form adds `Sponsored` (`#affibloom-sponsored`) and `ProductInput` accepts it (decision 3); `button_text`, `button_text2` and `badge` accept `<span class="…">` only (decision 5); labels `Primary Rating` and `Disclosure` (interface spec).
- Tasks 13, 16, 17: the Dashboard row action is `Edit` (quick edit); delete moves to the product page, `Delete This Link` with the "Delete This Link?" dialog (decision 4) (partly overtaken: the row also carries a `Shortcode` button,
  disabled until the shortcode exists — F31, Phase 3; the user: the shortcode stays).
- Task 12 (decision 11, the user, 2026-11-12): a new Amazon product's starting name comes from the link's slug when it has
  one (`amazon.com/Mizuno-Wave-Momentum-3/dp/…` → "Mizuno Wave Momentum 3"), read by `AmazonUrl` without any request;
  otherwise "Amazon", as Lasso. A resolved short link counts as well. Acceptance: F2 AC5.
- Task 14 (decision 11): for Amazon products the image is an editable **Amazon Image** link field (`#affibloom-image-url`,
  Lasso's label), so an editor can paste the image link from Amazon; "Update Image" (media library) stays for every
  product. No "Refresh" until task C1.

**Overtaken since (2026-11-14):** the full-scope rule (2026-11-13) and later user decisions replaced three of the
bullets above, each marked "(overtaken: …)" in place: decision 2 (duplicates) by F18 D2, and the Dashboard-row
`Shortcode` omission of decision 4 by F31 (the shortcode stays). Decision 10, "Lasso Groups: stay out" (in
`docs/decisions/task-6-decisions.md`, not a bullet of this block), is overtaken too: Groups are built in Tasks G1-G5
(F23).

## Decision ADR-8 (the user, 2026-11-12): Affibloom's own admin design

Affibloom keeps Lasso's features, fields, labels, help text and workflow steps, but its admin has its **own visual
design**: colour palette, typography, layout and component styles come from the Affibloom design system
(`docs/ui/design-system.md` in the plugin repository, written and approved in **Task 9A**), not from Lasso. The
reader-facing product box is not affected (it keeps the site's box design, ADR-6). Apply it in every task of this phase:

- **CSS in this file is superseded.** The `admin.css` blocks in Tasks 8–17 (the Lasso-measured header, tabs, buttons,
  toggles, help tips, toasts, tables, modals) describe Lasso's look. Build each component from the approved design
  system instead; keep the class names, markup hooks and behaviour the plan gives, so tests and JavaScript are
  unchanged. Record the CSS that replaces the plan's as a deviation in `progress.md`, as before.
- **Templates:** labels, help texts, field order, tab order and workflow steps stay Lasso's (the interface spec and the
  acceptance tests depend on them). Page layout (where the title, tabs, cards and buttons sit) follows the design system.
- **"Run and look" steps:** check the screen against the design system for looks, and against the Lasso screenshot in
  `tests/fixtures/baselines/admin/` only for fields, labels, help text and their order. Never tune an admin screen
  towards Lasso's colours, fonts or sizes.
- **Tasks 8 and 9 are done in Lasso's look**; Task 9A restyles them to the design system before Task 10 starts.
- `docs/ui/tokens.md` stays a record of Lasso's measured values; it is no longer the source of admin styles.
- **Lasso's structure on the Display screen (the user, 2026-11-13, amending ADR-8):** the columns, the layout and
  the presentation of the settings and the preview follow Lasso exactly — the split card with the preview and its
  toolbar on the left and the 380px rail on the right, holding the theme row, the underline tabs, the accordions
  and the label-left / control-right rows. Only the colours and the typography are Affibloom's. See `progress.md`
  deviation 25 for Lasso's measurements. Tasks 10A-10E follow this structure.
- **Aurora (the user, 2026-11-13):** the design system is the Aurora direction (gradient band, floating cards,
  segmented tabs, icon chips). Every screen puts its title and a one-line subtitle (U10) in the band; screens with
  sections put them as segmented tabs in the band (product page: Details, Locations). **Task 13** adds the Dashboard's
  first overview tile, `Products` (U9, `.affibloom-tile`, the design system's "Overview tile"), above the products
  card; the other two come with Tasks 31 and 34. Icons come from `templates/admin/partials/icon.php` (add new ones
  there).

## Decisions that change this phase (the user, 2026-11-14)

Each item is the user's answer from the walkthrough of 2026-11-14 (`open-decisions.md`, "Answers"), or the
recommendation, which the user accepted for every item without an answer.

- **Decided (the user, 2026-11-14), B1:** every Lasso cloud, licence, account and marketing item is left out (License Key, Advanced Click Tracking, Segment, the settings sync).
- **Decided (the user accepted the recommendation, 2026-11-14):** "Uninstall Lasso" rewrites posts (F9 N2, recommended: not built; the Uninstall card holds
  Task 38's opt-in data removal and lists posts still using Affibloom; "Start Link Indexing" then never applies).
- **Decided (the user, 2026-11-14), C3:** build Lasso's Permissions role select (F37 TL6, Phase 8), default Administrator, fixing Lasso's Contributor/Subscriber swap; decision 1 becomes the default.
- **Decided (the user accepted the recommendation, 2026-11-14):** changing the Cloaked Link Prefix rewrites every post in Lasso (F9 N4, recommended, F24: no
  rewrite; old prefixed URLs keep redirecting).
- **Decided (the user, 2026-11-14), C5:** Smart CSS Loading is on by default for a fresh install and the migration switches it on (F5 N2); F9 N5's "import it off" is not taken.
- **Decided (the user, 2026-11-14), C6:** build Lasso's GA controls in full (ID, Click Tracking, Pageview, IP Anonymization, gtag.js loader; C3C) and import Lasso's values; event names keep "Lasso …".
- **Decided (the user, 2026-11-14), C7:** the unsaved-changes guard is on every Settings tab (General, Display, Amazon): Task 9E.
- **Decided (the user accepted the recommendation, 2026-11-14):** HTML in field values (F26 N1, recommended: keep plain text, escaped on output; allow
  `wp_kses_post` for Long Text only if formatting is wanted).
- **Decided (the user, 2026-11-14), B10:** the tour's Chat step (Intercom) is left out; F10 migrates `lasso_dashboard_tour_done`.
- **Decided (the user accepted the recommendation, 2026-11-14):** two Lasso sort behaviours that are defects are not copied (F19 N2, recommended: agree; one
  visible state).
- **Decided (the user accepted the recommendation, 2026-11-14):** the command palette finds more than Lasso's (name and link, not name only) (F19 N3,
  recommended: the Dashboard's rule).
- **Decided (the user accepted the recommendation, 2026-11-14):** the setup wizard's trigger (F38 N1, recommended: build without the Activate step; open once
  after a first activation with no settings and no Lasso data).
- **Decided (the user, 2026-11-14), B1:** licence and account content is left out.
- **Decided (the user, 2026-11-14), B2:** the Help menu, the Support item and the 29 Learn links are left out (boxes keep their text).
- **Decided (the user, 2026-11-14), B3:** the review card is left out.
- **Decided (the user, 2026-11-14), B1:** Intercom, Segment, PostHog and Sentry are left out.
- **Decided (the user accepted the recommendation, 2026-11-14):** WordPress's footer version (F38 N6, recommended: blank it on Affibloom's pages only; ON1
  prints "Version <x.y.z>" in Affibloom's own footer).
- **Decided (the user accepted the recommendation, 2026-11-14):** the wizard's "Commission Upgrades" step (F38 N7, recommended: omit, as C1 N1).
- **Decided (the user, 2026-11-14), B4:** the three switches are kept as stored values, labelled "no effect"; nothing is built behind them (Task C1L).
- **Decided (the user, 2026-11-14), C4:** Lasso's Prime rule: the logo on every Amazon product while the site setting is on; the per-product switch is an opt-out defaulting on (Task C1G is in Phase 3).
- **Decided (the user accepted the recommendation, 2026-11-14):** Amazon prices after cutover without credentials (C1 N3, recommended: show a price only from
  an API row younger than 24 hours; Task C1F is in Phase 3).
- **Decided (the user accepted the recommendation, 2026-11-14):** Amazon's 24-hour rules (C1 N4, recommended: re-read the policies and the Creators API
  terms when C1E starts, Phase 7).
- F23 (Groups) has no open item: nothing in it needed the user's answer.

## Tasks in this phase

Run order is this list. The task sections below follow the order of the file: the done tasks, Groups G1-G5 where
they were, then the new sections in the order listed after G5.

### Done

- [x] Task 8: Admin shell — Affibloom → Dashboard
- [x] Task 9: Settings → General — viewing and saving link defaults
- [x] Task 9A: Affibloom design system — proposal, approval, restyle of the Dashboard and Settings (ADR-8)
- [x] ~~Task 10: Settings → Display and Amazon tabs~~ superseded 2026-11-13 (deviation 24); gates 1-2 in
  `docs/features/F16/`. Done through:
  - [x] Task 10A: Settings → Display — type segment, theme select, Colors & labels, save
  - [x] Task 10B: Elements, density presets and the spacing/typography/image steppers
  - [x] Task 10C: Layout controls, orientation coercion, the phone override and its reset
  - [x] Task 10E: Disclosure & behavior, Custom CSS, Reset Settings
  - [x] Task 10F: Settings → Amazon in full (~20 controls)
  - Task 10D: The live preview — **moved to Phase 3** (listed there)
- [x] ~~Task 11: "Add A New Link"~~ superseded 2026-11-13 (deviation 34); gates 1-2 in `docs/features/F18/`; done through:
  - [x] Task 11A: The product exists — post type, domain, repository, `POST /products`, the modal in single mode
  - [x] Task 11B: Normalisation and duplicates (D1, D2)
  - [x] Task 11C: Bulk add — Shift+Enter and the header menu, ten links, a WP-Cron background run (D3b)
  - [x] Task 11D: Naming — embedded destination, page title, Lasso's fallbacks; geni.us setting
  - [x] Task 11E: The site's link defaults copied onto a new product
- [x] ~~Task 12: Adding an Amazon link or ASIN~~ superseded 2026-11-13 (deviation 42); done through 12A-12E (the user's
  F2 run: all eight passed); gates 1-2 in `docs/features/F2/`:
  - [x] Task 12A: An Amazon product link, stored as Lasso stores it
  - [x] Task 12B: The same Amazon product twice (ASIN + country lookup)
  - [x] Task 12C: Amazon search pages and other Amazon pages
  - [x] Task 12D: Amazon short links
  - [x] Task 12E: A bare ASIN, and the starting name of an Amazon product (decision 11)
- [x] ~~Task 13: Dashboard list — search and pages~~ superseded 2026-11-13 (deviation 48); gates 1-2 in
  `docs/features/F19/`; gate 4 given 2026-11-13 (Aurora's shell, Lasso's arrangement; the shortcode stays):
  - [x] Task 13A: The Dashboard lists its products, in pages
  - [x] Task 13B: Finding a product in the list
  - [x] Task 13C: The full table — every Lasso column, the tiles and the selection bar
  - [x] Task 13D: The list without page reloads
  - [x] Task 13E: The filters panel, the link-type filter and saved views
- [x] ~~Task 14: Product page — the Details tab~~ superseded 2026-11-13 (deviation 57); gates 1-2 in
  `docs/features/F1/`; gate 4 given 2026-11-13 (layout D1):
  - [x] Task 14A: The product page opens — band, tabs, the link card, and every way in
  - [x] Task 14F: Button icons — the icon set (F28)
  - [x] Task 14G: Button icons — the picker on every button and badge text field (F28)
  - [x] Task 14H: Any number of buttons — the product and its page (F29)
  - [x] Task 14B: The display card — theme, image, badge, price, description, disclosure and the toggles
  - [x] Task 14C: Custom fields on the product page — Primary Rating, Pros, Cons and the editor's own
  - [x] Task 14D: The Share kit and the Amazon tracking-ID notice
  - Task 14E: The live preview — **moved to Phase 3** (listed there)
- [x] ~~Task 15: Saving the Details tab~~ superseded 2026-11-13 (deviation 70); done through:
  - [x] Task 15A: Save Changes stores the Details form
  - [x] Task 15B: Changing the Primary URL
  - [x] Task 15C: The same link as another product — "This Product Already Exists" and the repoint
  - [x] Task 15D: Unsaved changes
- [x] ~~Task 16: Dashboard quick edit~~ superseded 2026-11-13 (deviation 76); done through:
  - [x] Task 16A: Quick edit — the row opens, saves and closes
  - [x] Task 16B: Quick edit — unsaved changes on the Dashboard
  - [x] Task 16C: Quick edit — the same link as another product
- [x] ~~Task 17: Deleting a product~~ superseded 2026-11-13; done through 17A-17D (deviation 82):
  - [x] Task 17A: Delete This Link — the product page deletes for good
  - [x] Task 17B: Delete from the Dashboard — the ticked rows go to the trash
  - [x] Task 17C: Retired products keep their own status
  - [x] Task 17D: A deleted product comes back when its link is added again
- [x] The Lasso import button on the Dashboard (`447bf1b`, deviation 87): recorded; retired by Task 26O.

### Remaining, in order (gate 4 given 2026-11-14 — see Status)

**Corrections from the code audits (2026-11-14) and the user's decisions**

- [ ] Task 2X0: PHP 8.3 and WordPress 7.1.4 everywhere (decision F7)
- [ ] Task 2X1: Product page — the gaps a done task left
- [ ] Task 2X2: The F1 AC2 test checks everything it claims
- [ ] Task 2X3: Display colours accept every CSS colour Lasso accepts
- [ ] Task 2X4: A kept redirect that ends at Amazon is an Amazon product
- [ ] Task 2X5: Bulk add never stalls on one bad link; "Continue Anyway"
- [ ] Task 2X6: Brag mode leaves the Display tab (decision B9)

**Groups (F23), area 0 of `plans/archive/dashboard-completion-plan.md`** (gate 4: given 2026-11-14)

- [ ] Task G1: The Groups list, and a new group
- [ ] Task G2: A group's Details — edit and delete
- [ ] Task G3: Groups on the product page, and the Dashboard's Groups column
- [ ] Task G4: A group's Links tab — order, remove, add
- [ ] Task G5: Groups on the Dashboard — quick edit, filter, Add to Group

**Settings → General (F9)** — 9J (Link Index card) moves to Phase 5

- [ ] Task 9B: The General tab in Lasso's structure, and Link Defaults in full
- [ ] Task 9E: Unsaved-changes guard on every Settings tab (decision C7)
- [ ] Task 9C: "Allow duplicate destinations" off
- [ ] Task 9D: "Update Link Settings": Only New Links / Override All Links
- [ ] Task 9F: Google Analytics card
- [ ] Task 9G: Cloaked Link Prefix and Smart CSS Loading
- [ ] Task 9H: Performance card
- [ ] Task 9I: Custom Link Detection card
- [ ] Task 9K: Notifications card
- [ ] Task 9L: Support view

**Fields (F26)**

- [ ] Task FL1: The Fields list, with the whole field model
- [ ] Task FL2: A new field from the Fields screen
- [ ] Task FL3: A field's Details — edit and delete
- [ ] Task FL4: A field's Links tab
- [ ] Task FL5: The product page's field rows, corrected to Lasso's rules

**Activation and the upgrade routine (F37 part)** — moved here from Phase 6; URL variants V1-V5 moved to Phase 5

- [ ] Task TL1: Activation and deactivation
- [ ] Task TL2: Upgrade routine

**Dashboard, second pass (F19)** — 13K (sorting) is in Phase 7; 13L (command palette) and 13M (tour) are dropped as
duplicates of F38 ON9 and ON8 (Phase 8)

- [ ] Task 13F: The list keeps every argument without JavaScript, and `?post_id=`
- [ ] Task 13G: The search finds added buttons' links, not their texts
- [ ] Task 13H: F19 AC4 and AC10 tests that run on a full library
- [ ] Task 13I: A stable order, and counts that follow the list
- [ ] Task 13J: Saved views and the Filters panel behave as Lasso's

**Admin shell (F38)** — ON3-ON4 are in Phase 5, ON5-ON9 in Phase 8; ON10 is dropped as a duplicate of 9E

- [ ] Task ON1: Navigation in Lasso's order — header, WordPress menu, titles, footer
- [ ] Task ON2: The notice region, foreign notices, and the update notice

**Amazon product data (C1)** — dormant where the Creators API credentials are needed; C1E and C1H are in Phase 7,
C1F and C1G in Phase 3

- [ ] Task C1A: The Product Data API card and Validate
- [ ] Task C1B: The catalogue client
- [ ] Task C1C: Filling a new Amazon product on add
- [ ] Task C1D: Refresh on the product page
- [ ] Task C1I: Bulk Amazon sync
- Task C1J (no code; listed for coverage only): Tracking-ID behaviours — no code (one line, below)
- [ ] Task C1K: Keep Geni.us Formatting on add and save
- [ ] Task C1L: The cloud-only switches labelled
- [ ] Task C1M: The Amazon search tab in Add A New Link
- [ ] Task C1N: The SiteStripe notice
- [ ] Task C1O: Amazon data when the primary URL changes
- [ ] Task C1P: The geni.us clean-up job

## Cross-phase notes

Earlier phases are done and named in each task's "Depends on". What waits on later phases, and how each part is
stubbed or deferred:

**Settled in the planning pass (2026-11-14):**

- **Resolved 2026-11-14 — the schema routine.** TL1 and TL2 (activation; the versioned upgrade routine and
  `Schema\{Migrator,Table}`) moved here from Phase 6 and stand where V1 stood, so they come before every custom table:
  C1C's `affibloom_amazon_products` step is added through TL2's `Migrator`, and so are V1's `affibloom_url_variants`
  (Phase 5), 30A's `affibloom_link_issues` (Phase 6) and 35A's `affibloom_link_locations` (Phase 5).
- **Settled 2026-11-14 — overlap with F21.** 9H, 9I and 9L build the controls and store them under F9 D3's keys
  (`performance_mode`, `cpu_threshold`, `scan_post_types`, `scan_custom_fields`, `keep_original_domains`,
  `cron_interval`, `manual_process_limit`, ...); F21's 35D and 35G build only the effect (the scan and the index read
  those keys) and 9J the Link Index card's figures: no second card, no second set of keys.
- **Resolved 2026-11-14 — Tasks 26H and 26L.** Both are in Phase 5 (26H right after V5, which moved there too; 26L in
  the F10 core).

**Groups (G1-G5), checked against F23 and the features decided since** (nothing in the G tasks is rewritten):

- **Coverage:** every R and AC of `F23/requirements.md` is owned: R1-R4 and AC1 by G1, R5-R11 and AC2-AC3 by G1-G2,
  R12-R14, R16 and AC4 by G3 (R12's order is built in G3, `GroupOrder` and `TermGroupRepository::order()`, and read
  by G4), R17-R20 and AC5-AC7 by G4, R15, R21-R23 and AC8-AC10 by G5. R24 (displays elsewhere) is not a G task: it is
  Phase 3's Group displays (F30 DT tasks and F31's group shortcode) and Phase 4 (the editor).
- **`GET /products?search=` (G4, F23 D10)** is also read by F4 23B (Phase 4: `per_page=5`, `orderby=modified`, which
  23B adds if G4 has not), F38 ON9 (Phase 8: name and link, ten results, newest change first) and F22 (Phase 7: six a
  page). G4 builds it with the Dashboard's search rule (`ProductRepository::search()`), so name, link, ASIN and ID
  all match. Settled 2026-11-14: `GET /affibloom/v1/products?search=&page=&group=<id>` — `group` is the request parameter;
  each item in the answer carries `in_group` (true when the product is in that group). F4 23B/23E, F22 and F38 ON9 use the
  same route.
- **The group picker (G3)** is reused by the quick edit and bulk Add to Group (G5), by 9I's multi-select (second
  use, F9), and by F4 23E (Phase 4) for creating a group from the editor.
- **F30 (Phase 3) vs F23 D12:** Lasso lists a group member that has no order row first; D12 lists it last. F30 D2
  keeps D12 (no product is grouped on this site, and Lasso's own paths always write an order row). G4's
  `GroupService::members()` is the single reader; F30's List, Grid and Gallery use it. Nothing in G changes.
- **Copy Shortcode (G2, F23 D5):** "Coming soon" until Affibloom's shortcode exists (F31, Phase 3).
- **Task 26 (Phase 5):** `lasso-cat` → `affibloom_group`, `lasso_category_order` → `_affibloom_order` (F10 26F).

**Later-phase parts of tasks in this phase:**

- **2X0-2X6:** none; 2X5 reads F18's requirements for "Continue Anyway".
- **9B, 9D:** `link_cloaking` is read by F24 (Phase 5, CL4-CL5), `detect_opportunities` by F22 (Phase 7, O5-O6),
  `auto_detect_links` by F35/C2 (Phase 7). The switches are shown, saved and kept now; the product-field arms of 9D's
  "Update Link Settings" key map for Link Cloaking and Detect Opportunities are added by F24's and F22's tasks.
- **9F:** C3 (Phase 7) reads the four GA keys. **9G:** F24 (Phase 5) reads `link_prefix`; F5 (Phase 3) reads
  `smart_css`. **9H:** F11 (Phase 6) reads `cpu_threshold`. **9I:** F21 (Phase 5, 35D) reads the scan keys (see the
  F21 overlap above). **9K:** F38's notices and education boxes (Phases 7-8) read the three switches; `admin.js`
  reads `disable_help_tips` now. **9L:** the Rebuild Database button appears only when F21's rebuild route exists
  (35G, Phase 5); until then it is not shown. Its keys are read by F18/F1, F11, F24, F28, C1 and F21 later.
- **9C:** none (`LinkResolver`, `DuplicateLookup` exist). **9J** (Link Index card) is Phase 5.
- **FL1-FL5:** the box's fields (F26 R32-R34, R36) are Phase 3; the comparison table's use of fields (R35) is F25 (Phase
  7); Task 26's migration of fields (R1, R2, R5, R6) is Phase 5; the editor's use (R37, R38) is F4 (Phase 4).
- **V1-V5** moved to Phase 5 (2026-11-14), after CL11 and 26G; their stubs are gone because F24's CL1 and CL3 come first. Lasso's variant rows are copied by Task 26H (Phase 5).
- **13F-13J:** none. 13K (Phase 7) takes over sorting (`orderby`, `order`, `ListOrder`); F11 31A (Phase 6), F12 34A and
  F21 36A (Phase 7) add their arms through it. The Dashboard's Opportunities parts (F19 R5's `opportunity_count`,
  R9's Opportunities part, R40's Opportunities tab, the Alert cell's lightbulb, the Opportunities tile) are F22 O8
  (Phase 7); R5's substitution notice is to be confirmed there.
- **ON1:** G1 (Groups tab and submenu pages) and FL1 (hidden Fields page) are built earlier in this phase by hand; ON1
  moves them into `AdminNavigation` and registers Opportunities, Tables, Import and History when those screens
  arrive (F22, F25, F33, F34). **ON2:** C1I's banner, F11, F21 and F33 notices join its `NoticeBoard` later; C1I
  (after ON2 in this phase) should use it. ON3-ON4 are Phase 5; ON5-ON9 Phase 8.
- **C1B-C1D:** dormant until credentials exist. **C1C:** its table's schema step goes through TL2's `Migrator` (TL2 is earlier in this phase). **C1I:**
  banner through ON2's region. **C1N:** reads the Auto-Monetization switch (behaviour C2, Phase 7). **C1M:** F18's
  modal (done); Phase 4's editor does not use it. C1E (scheduled refresh, Phase 7), C1F and C1G (price, Prime in the
  box, Phase 3) and C1H (availability to F11, Phase 7) are not in this phase.
- **Dropped as duplicates of other features' tasks:** F19 13L (command palette) → F38 ON9; F19 13M (Dashboard tour) →
  F38 ON8 (both Phase 8); F38 ON10 (unsaved changes on Settings) → F9 9E.

---


Every task in this phase starts only after you have approved Task 6 (gate 4 for F1, F2, F3 and F9).

### Task 8: Admin shell — Affibloom → Dashboard

**Roadmap:** 2.0 (admin shell and UI kit)

**Why:** every admin screen sits inside the same Lasso-style header bar and uses the same colours and type. They are built once here, around the first screen that needs them.

**User action:** an editor clicks "Affibloom" in the admin menu and sees the header bar with the plugin's name and a "Dashboard" tab, the page title "Dashboard", and an empty state.

**Request path:**
```
GET /wp-admin/admin.php?page=affibloom
  → admin_menu: AdminMenu::add_pages()   capability Capabilities::PRODUCTS
  → admin_enqueue_scripts: AdminAssets    assets/admin/admin.css, only on affibloom screens
  → DashboardScreen::render()
       Template::render( 'admin/dashboard' )
         → templates/admin/partials/header.php
  ← HTML
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Affibloom\Module` (interface, `src/Module.php`) | `Plugin::register()` loops over modules |
| `Plugin::modules()`, `Plugin::template()` | `Plugin::register()` |
| `Capabilities` | `AdminMenu` |
| `AdminMenu::add_pages()` | `admin_menu` |
| `AdminAssets::enqueue()` | `admin_enqueue_scripts` |
| `Template::render()` | `DashboardScreen`, and templates for their partials |
| `DashboardScreen::render()` | the menu page |
| `templates/admin/dashboard.php`, `partials/header.php` | `DashboardScreen` |
| `assets/admin/admin.css` (tokens, header, page, card) | `AdminAssets` |

**Files:**
- Create:
  - `src/Module.php`, `src/Interface/Template.php`
  - `src/Interface/Admin/{Capabilities,AdminMenu,AdminAssets,DashboardScreen}.php`
  - `templates/admin/dashboard.php`, `templates/admin/partials/header.php`
  - `assets/admin/admin.css`
  - `tests/E2E/admin-shell.spec.ts`
- Modify: `src/Plugin.php`, `phpcs.xml.dist`

- [x] **Step 1: Failing test**

`tests/E2E/admin-shell.spec.ts`:

```ts
import { test, expect } from '@playwright/test';

test( 'the Dashboard opens inside the Affibloom header', async ( { page } ) => {
	await page.goto( '/wp-admin/admin.php?page=affibloom' );

	const header = page.locator( '.affibloom-header' );
	await expect( header ).toContainText( 'Affibloom' );
	await expect( header.getByRole( 'link', { name: 'Dashboard' } ) ).toHaveAttribute( 'aria-current', 'page' );
	await expect( page.getByRole( 'heading', { level: 1, name: 'Dashboard' } ) ).toBeVisible();
} );

test( 'admin CSS loads only on Affibloom screens', async ( { page } ) => {
	await page.goto( '/wp-admin/index.php' );
	await expect( page.locator( 'link[href*="affibloom/assets/admin/admin.css"]' ) ).toHaveCount( 0 );
} );
```

```bash
npm run test:e2e -- admin-shell
```
Expected: the first test fails, because the page does not exist yet.

- [x] **Step 2: The module contract and the composition root**

`src/Module.php`. It sits at the root because modules exist in more than one layer: the post type registration in Infrastructure is a module too, and Infrastructure must not depend on Interface.

```php
<?php
/**
 * Something that hooks itself into WordPress.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom;

/**
 * Something that hooks itself into WordPress.
 */
interface Module {

	/**
	 * Adds the module's actions and filters. Called once, while the plugin boots.
	 */
	public function register(): void;
}
```

`src/Plugin.php`: add these `use` lines, change `register()`, and add the methods below it:

```php
use Affibloom\Interface\Admin\AdminAssets;
use Affibloom\Interface\Admin\AdminMenu;
use Affibloom\Interface\Admin\DashboardScreen;
use Affibloom\Interface\Template;
use Affibloom\Module;
```

```php
	/**
	 * Shared template renderer, built on first use.
	 *
	 * @var Template|null
	 */
	private ?Template $template = null;

	/**
	 * Hooks the plugin into WordPress.
	 */
	private function register(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		foreach ( $this->modules() as $module ) {
			$module->register();
		}
	}

	/**
	 * Everything that hooks into WordPress, with its collaborators.
	 *
	 * @return list<Module>
	 */
	private function modules(): array {
		return array(
			new AdminMenu( new DashboardScreen( $this->template() ) ),
			new AdminAssets( plugin_dir_url( $this->file ), AFFIBLOOM_VERSION ),
		);
	}

	/**
	 * The template renderer for templates/.
	 */
	private function template(): Template {
		$this->template ??= new Template( dirname( $this->file ) . '/templates' );
		return $this->template;
	}
```

This is the only place that creates objects. Each later task adds its module to `modules()`, and adds a private builder method when an object is shared.

- [x] **Step 3: Menu (WordPress calls this)**

`src/Interface/Admin/Capabilities.php`:

```php
<?php
/**
 * Who may do what. One place, so a change of policy is one edit.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

/**
 * Who may do what. One place, so a change of policy is one edit.
 */
final class Capabilities {

	/** Add, edit and delete products; see the Dashboard. Editors and administrators. */
	public const PRODUCTS = 'edit_others_posts';
}
```

`src/Interface/Admin/AdminMenu.php`:

```php
<?php
/**
 * The Affibloom admin menu.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Module;

/**
 * The Affibloom admin menu.
 */
final class AdminMenu implements Module {

	public const SLUG = 'affibloom';

	/**
	 * Builds the menu.
	 *
	 * @param DashboardScreen $dashboard The Dashboard screen.
	 */
	public function __construct( private DashboardScreen $dashboard ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_pages' ) );
	}

	/**
	 * Adds the top-level menu; its first item is renamed "Dashboard", as in Lasso.
	 */
	public function add_pages(): void {
		add_menu_page(
			__( 'Affibloom', 'affibloom' ),
			__( 'Affibloom', 'affibloom' ),
			Capabilities::PRODUCTS,
			self::SLUG,
			array( $this->dashboard, 'render' ),
			'dashicons-cart',
			26
		);
		add_submenu_page(
			self::SLUG,
			__( 'Dashboard', 'affibloom' ),
			__( 'Dashboard', 'affibloom' ),
			Capabilities::PRODUCTS,
			self::SLUG,
			array( $this->dashboard, 'render' )
		);
	}
}
```

- [x] **Step 4: Screen and template renderer (what the menu calls)**

`src/Interface/Admin/DashboardScreen.php`:

```php
<?php
/**
 * The Dashboard: Lasso's main list of products.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Interface\Template;

/**
 * The Dashboard: Lasso's main list of products.
 */
final class DashboardScreen {

	/**
	 * Builds the screen.
	 *
	 * @param Template $template Template renderer.
	 */
	public function __construct( private Template $template ) {}

	/**
	 * Prints the page. WordPress has already checked the capability.
	 */
	public function render(): void {
		$this->template->render( 'admin/dashboard' );
	}
}
```

`src/Interface/Template.php`. Admin screens and, from Task 18, the front-end box both use it, so it sits in `Interface`, not `Interface\Admin`:

```php
<?php
/**
 * Renders a PHP template from templates/ with a prepared $view array.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface;

use InvalidArgumentException;

/**
 * Renders a PHP template from templates/ with a prepared $view array.
 */
final class Template {

	/**
	 * Builds the renderer.
	 *
	 * @param string $dir Absolute path of templates/.
	 */
	public function __construct( private string $dir ) {}

	/**
	 * Prints a template. Inside it, $view holds the values and $this renders partials.
	 *
	 * @param string               $name Path below templates/, without ".php".
	 * @param array<string, mixed> $view Values the template prints. Escaping happens in the template.
	 * @throws InvalidArgumentException When the template does not exist.
	 */
	public function render( string $name, array $view = array() ): void {
		$file = $this->dir . '/' . $name . '.php';
		if ( ! is_readable( $file ) ) {
			throw new InvalidArgumentException( esc_html( "Template not found: $name" ) );
		}
		include $file;
	}

	/**
	 * Same as render(), but returns the HTML.
	 *
	 * @param string               $name Path below templates/, without ".php".
	 * @param array<string, mixed> $view Values the template prints.
	 */
	public function capture( string $name, array $view = array() ): string {
		ob_start();
		$this->render( $name, $view );
		return (string) ob_get_clean();
	}
}
```

- [x] **Step 5: Templates (the response)**

`templates/admin/partials/header.php`:

```php
<?php
/**
 * Lasso-style header bar with the plugin name and its tabs.
 *
 * @package Affibloom
 * @var array{current: string} $view
 */

defined( 'ABSPATH' ) || exit;

$header_tabs = array(
	'dashboard' => array( __( 'Dashboard', 'affibloom' ), admin_url( 'admin.php?page=affibloom' ) ),
);
?>
<header class="affibloom-header">
	<div class="affibloom-header__brand">
		<span class="affibloom-header__logo" aria-hidden="true">A</span>
		<span class="affibloom-header__name"><?php esc_html_e( 'Affibloom', 'affibloom' ); ?></span>
	</div>
	<nav class="affibloom-header__nav" aria-label="<?php esc_attr_e( 'Affibloom', 'affibloom' ); ?>">
		<?php foreach ( $header_tabs as $key => [ $label, $url ] ) : ?>
			<a class="affibloom-header__tab<?php echo $key === $view['current'] ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( $url ); ?>"
				<?php echo $key === $view['current'] ? 'aria-current="page"' : ''; ?>>
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>
</header>
```

`templates/admin/dashboard.php`:

```php
<?php
/**
 * Dashboard screen.
 *
 * @package Affibloom
 * @var array<string, mixed> $view
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header', array( 'current' => 'dashboard' ) ); ?>

	<div class="affibloom-page">
		<div class="affibloom-page__head">
			<h1 class="affibloom-page__title"><?php esc_html_e( 'Dashboard', 'affibloom' ); ?></h1>
		</div>

		<div class="affibloom-card affibloom-empty">
			<p><?php esc_html_e( 'No products yet.', 'affibloom' ); ?></p>
		</div>
	</div>
</div>
```

`phpcs.xml.dist`: add `<file>templates</file>` after `<file>src</file>`. Template variables are local to `Template::render()`, not globals, so exclude templates from the prefix rule:

```xml
	<rule ref="WordPress.NamingConventions.PrefixAllGlobals">
		<exclude-pattern>templates/*</exclude-pattern>
	</rule>
```

- [x] **Step 6: The UI kit's first layer: tokens and the header**

`src/Interface/Admin/AdminAssets.php`:

```php
<?php
/**
 * Admin CSS and JavaScript, on Affibloom screens only.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Module;

/**
 * Admin CSS and JavaScript, on Affibloom screens only.
 */
final class AdminAssets implements Module {

	/**
	 * Builds the module.
	 *
	 * @param string $plugin_url URL of the plugin folder, with a trailing slash.
	 * @param string $version    Plugin version, used to bust caches.
	 */
	public function __construct( private string $plugin_url, private string $version ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueues assets when the current screen is one of ours.
	 *
	 * @param string $hook_suffix Current admin page hook, e.g. "toplevel_page_affibloom".
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( ! str_contains( $hook_suffix, 'affibloom' ) ) {
			return;
		}
		wp_enqueue_style( 'affibloom-admin', $this->plugin_url . 'assets/admin/admin.css', array(), $this->version );
	}
}
```

`assets/admin/admin.css`. The values come from `docs/ui/tokens.md` (Task 6). Replace them with the measured ones if they differ.

```css
/* Affibloom admin UI kit. Colours and type follow Lasso's admin (docs/ui/tokens.md). */

.affibloom-admin {
	--affibloom-green: #22baa0;
	--affibloom-green-dark: #1b8f7b;
	--affibloom-purple: #5e36ca;
	--affibloom-purple-tint: #f2effb;
	--affibloom-ink: #1d1733;
	--affibloom-muted: #6c757d;
	--affibloom-border: #ced4da;
	--affibloom-border-light: #e2e2e2;
	--affibloom-bg: #fafafd;
	--affibloom-white: #fff;
	--affibloom-red: #e06470;
	--affibloom-radius: 8px;
	--affibloom-shadow: 0 1px 3px rgba(29, 23, 51, 0.08);
	--affibloom-font: "Helvetica Neue", Helvetica, Arial, sans-serif;

	margin: 0 0 0 -20px; /* full width inside wp-admin, as Lasso */
	color: var(--affibloom-ink);
	font-family: var(--affibloom-font);
	background: var(--affibloom-bg);
	min-height: calc(100vh - 32px);
}

/* Header bar */
.affibloom-header {
	display: flex;
	align-items: center;
	gap: 32px;
	padding: 0 32px;
	height: 64px;
	background: var(--affibloom-white);
	border-bottom: 1px solid var(--affibloom-border-light);
}
.affibloom-header__brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 18px; }
.affibloom-header__logo {
	display: inline-grid;
	place-items: center;
	width: 32px;
	height: 32px;
	border-radius: 50%;
	background: var(--affibloom-purple);
	color: var(--affibloom-white);
	font-size: 13px;
}
.affibloom-header__nav { display: flex; gap: 24px; height: 100%; }
.affibloom-header__tab {
	display: flex;
	align-items: center;
	height: 100%;
	border-bottom: 3px solid transparent;
	color: var(--affibloom-ink);
	font-size: 15px;
	font-weight: 600;
	text-decoration: none;
}
.affibloom-header__tab:hover,
.affibloom-header__tab:focus { color: var(--affibloom-purple); }
.affibloom-header__tab.is-active { border-bottom-color: var(--affibloom-purple); color: var(--affibloom-purple); }

/* Page */
.affibloom-page { padding: 32px; max-width: 1400px; }
.affibloom-page__head { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; }
.affibloom-page__title { margin: 0; padding: 0; font-size: 28px; font-weight: 700; color: var(--affibloom-ink); }

/* Card */
.affibloom-card {
	background: var(--affibloom-white);
	border: 1px solid var(--affibloom-border-light);
	border-radius: var(--affibloom-radius);
	box-shadow: var(--affibloom-shadow);
	padding: 24px;
}
.affibloom-empty { text-align: center; color: var(--affibloom-muted); }

/* Visible focus everywhere in the kit */
.affibloom-admin :focus-visible { outline: 2px solid var(--affibloom-purple); outline-offset: 2px; }
```

- [x] **Step 7: Run and look**

```bash
composer lint && composer analyse
npm run test:e2e -- admin-shell
```
Expected: `2 passed`. Open http://localhost:8888/wp-admin/admin.php?page=affibloom and put it beside `tests/fixtures/baselines/admin/dashboard.png`. The header bar's height, tab style and colours should match. Note any difference in `docs/ui/tokens.md` and adjust the CSS.

- [x] **Step 8: Commit**

```bash
git add -A
git commit -m "Add the Affibloom admin shell and its Dashboard page

The Lasso-style header bar and the colour and type tokens are built
around the first screen that uses them."
```

---

### Task 9: Settings → General — viewing and saving link defaults

**Roadmap:** 2.1 · **Feature:** F9 (AC3)

**Why:** new products take their link behaviour (new tab, nofollow, sponsored, disclosure) from site defaults, as in Lasso. The defaults need a screen first.

**User action:** an administrator opens Settings, sees the General tab with Lasso's "Link Defaults" toggles, changes one, and clicks "Save Changes". The page reloads with a toast, and the toggle keeps its new value.

**Request path:**
```
GET admin.php?page=affibloom-settings&tab=general
  → AdminMenu (submenu, Capabilities::SETTINGS) → SettingsScreen::render()
       SettingsService::current() → OptionSettingsRepository::get() → get_option( 'affibloom_settings' )
       Template 'admin/settings' → partials/toggle, partials/help, partials/toast

POST admin-post.php  action=affibloom_save_settings, tab=general, settings[...]
  → SettingsController::save()
       capability + nonce
       SettingsService::update_tab( 'general', $input ) → Settings::with() → OptionSettingsRepository::save()
  ← 302 admin.php?page=affibloom-settings&tab=general&affibloom_notice=saved
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Capabilities::SETTINGS` | `AdminMenu`, `SettingsController` |
| `Domain\Settings\Settings` (General keys only) | `SettingsService`, `OptionSettingsRepository` |
| `Domain\Settings\SettingsRepository` (interface) | `SettingsService` |
| `OptionSettingsRepository` | `Plugin::settings_repository()` |
| `SettingsService::current()`, `tabs()`, `update_tab()` | `SettingsScreen`, `SettingsController` |
| `SettingsScreen::render()` | the submenu page |
| `SettingsController::save()` | `admin_post_affibloom_save_settings` |
| templates `admin/settings.php`, `partials/toggle.php`, `partials/help.php`, `partials/toast.php` | `SettingsScreen` |
| "Settings" tab in the header | `partials/header.php` |

**Layers:** the Domain stays free of WordPress calls. The Application layer may use WordPress's pure helpers (`sanitize_text_field()`, `sanitize_hex_color()`), but never queries, options or hooks; those belong to Infrastructure.

**Files:**
- Create:
  - `src/Domain/Settings/{Settings,SettingsRepository}.php`
  - `src/Infrastructure/WordPress/OptionSettingsRepository.php`
  - `src/Application/Settings/SettingsService.php`
  - `src/Interface/Admin/{SettingsScreen,SettingsController}.php`
  - `templates/admin/settings.php`, `templates/admin/partials/{toggle,help,toast}.php`
  - `tests/Unit/Application/SettingsServiceTest.php`
  - `tests/E2E/acceptance/F9.spec.ts` (from Task 7)
- Modify:
  - `src/Interface/Admin/{Capabilities,AdminMenu}.php`
  - `src/Plugin.php`
  - `templates/admin/partials/header.php`
  - `assets/admin/admin.css`

- [x] **Step 1: Failing acceptance test**

```bash
npm run test:e2e -- acceptance/F9 -g AC3
```
Expected: fails, because `page=affibloom-settings` does not exist.

- [x] **Step 2: Route (the submenu and the save handler)**

`Capabilities.php`, add:

```php
	/** Change site-wide settings. Administrators. */
	public const SETTINGS = 'manage_options';
```

`AdminMenu.php`: add `private SettingsScreen $settings` to the constructor, after `$dashboard`, and at the end of `add_pages()`:

```php
		add_submenu_page(
			self::SLUG,
			__( 'Settings', 'affibloom' ),
			__( 'Settings', 'affibloom' ),
			Capabilities::SETTINGS,
			SettingsScreen::SLUG,
			array( $this->settings, 'render' )
		);
```

`src/Interface/Admin/SettingsController.php`:

```php
<?php
/**
 * Saves one Settings tab (admin-post.php).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Settings\SettingsService;
use Affibloom\Module;

/**
 * Saves one Settings tab (admin-post.php).
 */
final class SettingsController implements Module {

	public const ACTION = 'affibloom_save_settings';

	/**
	 * Builds the controller.
	 *
	 * @param SettingsService $settings Settings use cases.
	 */
	public function __construct( private SettingsService $settings ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'save' ) );
	}

	/**
	 * Validates, saves, and redirects back to the same tab with a notice.
	 */
	public function save(): void {
		if ( ! current_user_can( Capabilities::SETTINGS ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to change these settings.', 'affibloom' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION );

		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : '';
		if ( ! in_array( $tab, $this->settings->tabs(), true ) ) {
			wp_die( esc_html__( 'Unknown settings tab.', 'affibloom' ), '', array( 'response' => 400 ) );
		}

		// Each field is sanitised and validated by type in SettingsService.
		$input = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$refused = $this->settings->update_tab( $tab, $input );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'         => SettingsScreen::SLUG,
					'tab'          => $tab,
					'affibloom_notice' => $refused ? 'partly-saved' : 'saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
```

- [x] **Step 3: Screen**

`src/Interface/Admin/SettingsScreen.php`:

```php
<?php
/**
 * Settings: General, Display and Amazon tabs, as in Lasso.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Settings\SettingsService;
use Affibloom\Interface\Template;

/**
 * Settings: General, Display and Amazon tabs, as in Lasso.
 */
final class SettingsScreen {

	public const SLUG = 'affibloom-settings';

	/**
	 * Builds the screen.
	 *
	 * @param Template        $template Template renderer.
	 * @param SettingsService $settings Settings use cases.
	 */
	public function __construct( private Template $template, private SettingsService $settings ) {}

	/**
	 * Prints the current tab.
	 */
	public function render(): void {
		$tabs = $this->settings->tabs();
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chooses what to display only.
		if ( ! in_array( $tab, $tabs, true ) ) {
			$tab = 'general';
		}

		$this->template->render(
			'admin/settings',
			array(
				'tab'      => $tab,
				'tabs'     => $tabs,
				'settings' => $this->settings->current(),
				'action'   => SettingsController::ACTION,
				'notice'   => $this->notice(),
			)
		);
	}

	/**
	 * The toast message after a save, or '' when there is none.
	 */
	private function notice(): string {
		$code = isset( $_GET['affibloom_notice'] ) ? sanitize_key( wp_unslash( $_GET['affibloom_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.

		return match ( $code ) {
			'saved'        => __( 'Settings saved.', 'affibloom' ),
			'partly-saved' => __( 'Some values were not valid and kept their previous setting. Everything else was saved.', 'affibloom' ),
			default        => '',
		};
	}
}
```

- [x] **Step 4: Service, domain, storage (what the screen and controller call)**

`src/Domain/Settings/Settings.php`:

```php
<?php
/**
 * Site-wide settings. Unknown keys are dropped; missing keys take the default.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Settings;

use InvalidArgumentException;

/**
 * Site-wide settings. Unknown keys are dropped; missing keys take the default.
 */
final class Settings {

	/** Defaults for a fresh install. Task 26 imports the site's real values from Lasso. */
	public const DEFAULTS = array(
		'new_tab'          => true,
		'nofollow'         => true,
		'sponsored'        => false,
		'show_disclosure'  => true,
		'allow_duplicates' => false,
	);

	/**
	 * Use from_array().
	 *
	 * @param array<string, bool|string> $values Complete set of values.
	 */
	private function __construct( private readonly array $values ) {}

	/**
	 * Builds settings from whatever is stored.
	 *
	 * @param array<string, mixed> $stored Stored option value.
	 */
	public static function from_array( array $stored ): self {
		return new self( array_merge( self::DEFAULTS, array_intersect_key( $stored, self::DEFAULTS ) ) );
	}

	/**
	 * A boolean setting.
	 *
	 * @param string $key Setting key.
	 */
	public function bool( string $key ): bool {
		return (bool) $this->value( $key );
	}

	/**
	 * A text setting.
	 *
	 * @param string $key Setting key.
	 */
	public function string( string $key ): string {
		return (string) $this->value( $key );
	}

	/**
	 * A copy with some values changed. Unknown keys are ignored.
	 *
	 * @param array<string, bool|string> $changes New values.
	 */
	public function with( array $changes ): self {
		return new self( array_merge( $this->values, array_intersect_key( $changes, self::DEFAULTS ) ) );
	}

	/**
	 * All values, for storage.
	 *
	 * @return array<string, bool|string>
	 */
	public function to_array(): array {
		return $this->values;
	}

	/**
	 * One value.
	 *
	 * @param string $key Setting key.
	 * @throws InvalidArgumentException For a key that does not exist; always a programming error.
	 */
	private function value( string $key ): bool|string {
		if ( ! array_key_exists( $key, $this->values ) ) {
			throw new InvalidArgumentException( "Unknown setting: $key" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- never shown to users.
		}
		return $this->values[ $key ];
	}
}
```

`src/Domain/Settings/SettingsRepository.php`:

```php
<?php
/**
 * Where settings are kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Settings;

/**
 * Where settings are kept.
 */
interface SettingsRepository {

	/**
	 * The current settings.
	 */
	public function get(): Settings;

	/**
	 * Replaces the stored settings.
	 *
	 * @param Settings $settings New settings.
	 */
	public function save( Settings $settings ): void;
}
```

`src/Infrastructure/WordPress/OptionSettingsRepository.php`:

```php
<?php
/**
 * Settings stored in one autoloaded option, read once per request.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;

/**
 * Settings stored in one autoloaded option, read once per request.
 */
final class OptionSettingsRepository implements SettingsRepository {

	public const OPTION = 'affibloom_settings';

	/**
	 * Settings already read in this request.
	 *
	 * @var Settings|null
	 */
	private ?Settings $loaded = null;

	/**
	 * The current settings.
	 */
	public function get(): Settings {
		$this->loaded ??= Settings::from_array( (array) get_option( self::OPTION, array() ) );
		return $this->loaded;
	}

	/**
	 * Replaces the stored settings. Autoloaded, because every page with a box reads them.
	 *
	 * @param Settings $settings New settings.
	 */
	public function save( Settings $settings ): void {
		update_option( self::OPTION, $settings->to_array(), true );
		$this->loaded = $settings;
	}
}
```

`src/Application/Settings/SettingsService.php`:

```php
<?php
/**
 * Reading and saving settings, one tab at a time.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Settings;

use InvalidArgumentException;
use LogicException;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;

/**
 * Reading and saving settings, one tab at a time.
 */
final class SettingsService {

	/** The fields each tab owns, with the type that decides how a value is cleaned. */
	private const FIELDS = array(
		'general' => array(
			'new_tab'          => 'bool',
			'nofollow'         => 'bool',
			'sponsored'        => 'bool',
			'show_disclosure'  => 'bool',
			'allow_duplicates' => 'bool',
		),
	);

	/**
	 * Builds the service.
	 *
	 * @param SettingsRepository $repository Where settings are kept.
	 */
	public function __construct( private SettingsRepository $repository ) {}

	/**
	 * The current settings.
	 */
	public function current(): Settings {
		return $this->repository->get();
	}

	/**
	 * Tab keys, in display order.
	 *
	 * @return list<string>
	 */
	public function tabs(): array {
		return array_keys( self::FIELDS );
	}

	/**
	 * Saves the fields of one tab. Invalid values keep their old value.
	 *
	 * @param string               $tab   Tab key.
	 * @param array<string, mixed> $input Raw, unslashed form values.
	 * @return list<string> Keys whose values were refused.
	 * @throws InvalidArgumentException For an unknown tab.
	 */
	public function update_tab( string $tab, array $input ): array {
		if ( ! isset( self::FIELDS[ $tab ] ) ) {
			throw new InvalidArgumentException( 'Unknown settings tab.' );
		}

		$changes = array();
		$refused = array();
		foreach ( self::FIELDS[ $tab ] as $key => $type ) {
			$value = $this->clean( $type, $input[ $key ] ?? null );
			if ( null === $value ) {
				$refused[] = $key;
				continue;
			}
			$changes[ $key ] = $value;
		}

		$this->repository->save( $this->repository->get()->with( $changes ) );
		return $refused;
	}

	/**
	 * Cleans one value, or returns null when it cannot be accepted.
	 *
	 * @param string $type Field type from FIELDS.
	 * @param mixed  $raw  Raw value; a checkbox that is not ticked is absent (null).
	 * @throws LogicException For a type without a cleaner.
	 */
	private function clean( string $type, mixed $raw ): bool|string|null {
		return match ( $type ) {
			// A ticked checkbox sends "1"; an unticked one sends nothing. true/"true"/"on" cover other callers (Task 26).
			'bool'  => in_array( $raw, array( true, 1, '1', 'on', 'true', 'yes' ), true ),
			default => throw new LogicException( "No cleaner for type $type." ),
		};
	}
}
```

`src/Plugin.php`: add a builder for the shared settings, and the two new modules:

```php
	/**
	 * Settings storage, shared by every reader in a request.
	 *
	 * @var OptionSettingsRepository|null
	 */
	private ?OptionSettingsRepository $settings_repository = null;

	/**
	 * The settings repository.
	 */
	private function settings_repository(): OptionSettingsRepository {
		$this->settings_repository ??= new OptionSettingsRepository();
		return $this->settings_repository;
	}
```

and in `modules()`:

```php
		$settings = new SettingsService( $this->settings_repository() );

		return array(
			new AdminMenu(
				new DashboardScreen( $this->template() ),
				new SettingsScreen( $this->template(), $settings )
			),
			new AdminAssets( plugin_dir_url( $this->file ), AFFIBLOOM_VERSION ),
			new SettingsController( $settings ),
		);
```

with the matching `use` lines (`SettingsService`, `SettingsScreen`, `SettingsController`, `OptionSettingsRepository`).

- [x] **Step 5: Unit test of the service**

`tests/Unit/Application/SettingsServiceTest.php`:

```php
<?php
namespace Affibloom\Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use Affibloom\Application\Settings\SettingsService;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;

final class SettingsServiceTest extends TestCase {
	private function repository(): SettingsRepository {
		return new class() implements SettingsRepository {
			public Settings $stored;
			public function __construct() {
				$this->stored = Settings::from_array( array() );
			}
			public function get(): Settings {
				return $this->stored;
			}
			public function save( Settings $settings ): void {
				$this->stored = $settings;
			}
		};
	}

	public function test_an_unticked_checkbox_turns_a_setting_off(): void {
		$repo    = $this->repository();
		$refused = ( new SettingsService( $repo ) )->update_tab( 'general', array( 'new_tab' => '1' ) );

		self::assertSame( array(), $refused );
		self::assertTrue( $repo->get()->bool( 'new_tab' ) );
		self::assertFalse( $repo->get()->bool( 'nofollow' ) ); // was true by default, not sent
	}

	public function test_an_unknown_tab_is_refused(): void {
		$this->expectException( \InvalidArgumentException::class );
		( new SettingsService( $this->repository() ) )->update_tab( 'nope', array() );
	}
}
```

```bash
composer test:unit
```

- [x] **Step 6: Templates (the response)**

`templates/admin/partials/help.php`. This is Lasso's hover help, made reachable by keyboard focus too. U3, if approved, adds a visible line under key fields.

```php
<?php
/**
 * A "?" help tip.
 *
 * @package Affibloom
 * @var array{text: string} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<span class="affibloom-help" tabindex="0" role="img" aria-label="<?php echo esc_attr( $view['text'] ); ?>" data-tooltip="<?php echo esc_attr( $view['text'] ); ?>">?</span>
```

`templates/admin/partials/toggle.php`:

```php
<?php
/**
 * Lasso-style switch with its label and optional help tip.
 *
 * @package Affibloom
 * @var array{id: string, name: string, label: string, checked: bool, help?: string} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="affibloom-field affibloom-field--toggle">
	<label class="affibloom-toggle" for="<?php echo esc_attr( $view['id'] ); ?>">
		<input type="checkbox" class="affibloom-toggle__input" role="switch"
			id="<?php echo esc_attr( $view['id'] ); ?>"
			name="<?php echo esc_attr( $view['name'] ); ?>"
			value="1" <?php checked( $view['checked'] ); ?>>
		<span class="affibloom-toggle__track" aria-hidden="true"></span>
		<span class="affibloom-toggle__label"><?php echo esc_html( $view['label'] ); ?></span>
	</label>
	<?php if ( ! empty( $view['help'] ) ) : ?>
		<?php $this->render( 'admin/partials/help', array( 'text' => $view['help'] ) ); ?>
	<?php endif; ?>
</div>
```

`templates/admin/partials/toast.php`:

```php
<?php
/**
 * Lasso-style toast, bottom right. Fades out by itself (CSS only).
 *
 * @package Affibloom
 * @var array{message: string} $view
 */

defined( 'ABSPATH' ) || exit;

if ( '' === $view['message'] ) {
	return;
}
?>
<div class="affibloom-toast" role="status" aria-live="polite"><?php echo esc_html( $view['message'] ); ?></div>
```

`templates/admin/settings.php`:

```php
<?php
/**
 * Settings screen.
 *
 * @package Affibloom
 * @var array{tab: string, tabs: list<string>, settings: \Affibloom\Domain\Settings\Settings, action: string, notice: string} $view
 */

defined( 'ABSPATH' ) || exit;

$labels   = array(
	'general' => __( 'General', 'affibloom' ),
);
$settings = $view['settings'];
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header', array( 'current' => 'settings' ) ); ?>

	<div class="affibloom-page">
		<div class="affibloom-page__head">
			<h1 class="affibloom-page__title"><?php esc_html_e( 'Settings', 'affibloom' ); ?></h1>
		</div>

		<nav class="affibloom-tabs" aria-label="<?php esc_attr_e( 'Settings sections', 'affibloom' ); ?>">
			<?php foreach ( $view['tabs'] as $tab_key ) : ?>
				<a class="affibloom-tabs__tab<?php echo $tab_key === $view['tab'] ? ' is-active' : ''; ?>"
					href="<?php echo esc_url( admin_url( 'admin.php?page=affibloom-settings&tab=' . $tab_key ) ); ?>"
					<?php echo $tab_key === $view['tab'] ? 'aria-current="page"' : ''; ?>>
					<?php echo esc_html( $labels[ $tab_key ] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<form class="affibloom-card affibloom-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( $view['action'] ); ?>">
			<input type="hidden" name="tab" value="<?php echo esc_attr( $view['tab'] ); ?>">
			<?php wp_nonce_field( $view['action'] ); ?>

			<?php if ( 'general' === $view['tab'] ) : ?>
				<section class="affibloom-section">
					<h2 class="affibloom-section__title"><?php esc_html_e( 'Link Defaults', 'affibloom' ); ?></h2>
					<p class="affibloom-section__intro"><?php esc_html_e( 'Set the default attributes for new links.', 'affibloom' ); ?></p>
					<?php
					$toggles = array(
						'new_tab'          => array( __( 'New Window / Tab', 'affibloom' ), __( 'When enabled, users who click this link will have it loaded in a new tab.', 'affibloom' ) ),
						'nofollow'         => array( __( 'NoFollow / NoIndex', 'affibloom' ), __( "When enabled, this link will be set to nofollow. This indicates to Google that it's an affiliate link.", 'affibloom' ) ),
						'sponsored'        => array( __( 'Sponsored', 'affibloom' ), __( 'When enabled, this link will be set to sponsored.', 'affibloom' ) ),
						'show_disclosure'  => array( __( 'Show Disclosure', 'affibloom' ), __( 'When enabled, this link will show the disclosure.', 'affibloom' ) ),
						'allow_duplicates' => array( __( 'Allow duplicate destinations', 'affibloom' ), __( 'Allows you to have links from different networks to the same product.', 'affibloom' ) ),
					);
					foreach ( $toggles as $key => [ $label, $help ] ) {
						$this->render(
							'admin/partials/toggle',
							array(
								'id'      => 'affibloom-setting-' . $key,
								'name'    => 'settings[' . $key . ']',
								'label'   => $label,
								'checked' => $settings->bool( $key ),
								'help'    => $help,
							)
						);
					}
					?>
				</section>
			<?php endif; ?>

			<div class="affibloom-form__actions">
				<button type="submit" id="affibloom-settings-save" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
			</div>
		</form>
	</div>

	<?php $this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) ); ?>
</div>
```

The help texts are Lasso's own words (from `admin/views/settings/general.php`), because the team already knows them.

`templates/admin/partials/header.php`: add the Settings tab, for users who may see it:

```php
if ( current_user_can( \Affibloom\Interface\Admin\Capabilities::SETTINGS ) ) {
	$header_tabs['settings'] = array( __( 'Settings', 'affibloom' ), admin_url( 'admin.php?page=affibloom-settings' ) );
}
```

`assets/admin/admin.css`, append the kit's next layer:

```css
/* Tabs (product page and Settings) */
.affibloom-tabs { display: flex; gap: 4px; margin: 0 0 16px; border-bottom: 1px solid var(--affibloom-border-light); }
.affibloom-tabs__tab {
	padding: 10px 16px;
	border-bottom: 3px solid transparent;
	color: var(--affibloom-muted);
	font-weight: 600;
	text-decoration: none;
}
.affibloom-tabs__tab:hover { color: var(--affibloom-ink); }
.affibloom-tabs__tab.is-active { color: var(--affibloom-purple); border-bottom-color: var(--affibloom-purple); }

/* Form sections and fields */
.affibloom-section + .affibloom-section { margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--affibloom-border-light); }
.affibloom-section__title { margin: 0 0 4px; font-size: 18px; color: var(--affibloom-ink); }
.affibloom-section__intro { margin: 0 0 16px; color: var(--affibloom-muted); }
.affibloom-field { display: flex; align-items: center; gap: 8px; margin: 0 0 14px; }
.affibloom-form__actions { margin-top: 24px; }

/* Buttons */
.affibloom-button {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	padding: 8px 20px;
	border: 1px solid transparent;
	border-radius: 999px; /* Lasso's pill buttons */
	font: 600 14px/1.4 var(--affibloom-font);
	cursor: pointer;
	text-decoration: none;
}
.affibloom-button--primary { background: var(--affibloom-green); color: var(--affibloom-white); }
.affibloom-button--primary:hover { background: var(--affibloom-green-dark); color: var(--affibloom-white); }
.affibloom-button--secondary { background: var(--affibloom-white); border-color: var(--affibloom-border); color: var(--affibloom-ink); }
.affibloom-button--danger { background: var(--affibloom-red); color: var(--affibloom-white); }
.affibloom-button[disabled] { opacity: 0.6; cursor: not-allowed; }

/* Toggle switch */
.affibloom-toggle { position: relative; display: inline-flex; align-items: center; gap: 10px; cursor: pointer; }
.affibloom-toggle__input { position: absolute; opacity: 0; width: 1px; height: 1px; }
.affibloom-toggle__track {
	position: relative;
	width: 38px;
	height: 22px;
	border-radius: 999px;
	background: var(--affibloom-border);
	transition: background 0.15s;
}
.affibloom-toggle__track::after {
	content: "";
	position: absolute;
	top: 3px;
	left: 3px;
	width: 16px;
	height: 16px;
	border-radius: 50%;
	background: var(--affibloom-white);
	transition: transform 0.15s;
}
.affibloom-toggle__input:checked + .affibloom-toggle__track { background: var(--affibloom-green); }
.affibloom-toggle__input:checked + .affibloom-toggle__track::after { transform: translateX(16px); }
.affibloom-toggle__input:focus-visible + .affibloom-toggle__track { outline: 2px solid var(--affibloom-purple); outline-offset: 2px; }
.affibloom-toggle__label { font-weight: 600; }

/* Help tip */
.affibloom-help {
	position: relative;
	display: inline-grid;
	place-items: center;
	width: 18px;
	height: 18px;
	border-radius: 50%;
	background: var(--affibloom-purple-tint);
	color: var(--affibloom-purple);
	font-size: 11px;
	font-weight: 700;
	cursor: help;
}
.affibloom-help:hover::after,
.affibloom-help:focus::after {
	content: attr(data-tooltip);
	position: absolute;
	bottom: calc(100% + 8px);
	left: 50%;
	z-index: 10;
	width: max-content;
	max-width: 260px;
	transform: translateX(-50%);
	padding: 8px 10px;
	border-radius: 6px;
	background: var(--affibloom-ink);
	color: var(--affibloom-white);
	font-weight: 400;
	font-size: 12px;
	line-height: 1.4;
}

/* Toast */
.affibloom-toast {
	position: fixed;
	right: 24px;
	bottom: 24px;
	z-index: 100000;
	padding: 14px 20px;
	border-radius: var(--affibloom-radius);
	background: var(--affibloom-green);
	color: var(--affibloom-white);
	font-weight: 600;
	box-shadow: 0 6px 20px rgba(29, 23, 51, 0.2);
	animation: affibloom-toast 4s forwards;
}
@keyframes affibloom-toast {
	0%, 85% { opacity: 1; transform: none; }
	100% { opacity: 0; transform: translateY(8px); visibility: hidden; }
}
@media (prefers-reduced-motion: reduce) {
	.affibloom-toast { animation: none; }
}
```

- [x] **Step 7: Run and look**

```bash
composer lint && composer analyse && composer test:unit
npm run test:e2e -- acceptance/F9 -g AC3
```
Expected: the test passes. Open the General tab beside `baselines/admin/settings-general.png`. The Link Defaults section should show the same toggles in the same order, with the same help texts. The other sections of Lasso's General tab (licence, Google Analytics, cron and so on) are left out on purpose; see the Screen map.

- [x] **Step 8: Commit**

```bash
git add -A
git commit -m "Add Settings with the General tab for link defaults

New products take new-tab, nofollow, sponsored and disclosure from
these defaults, as in Lasso. Invalid values keep their old setting."
```

---

### Task 9A: Affibloom design system — proposal, approval, restyle (ADR-8)

**Roadmap:** 2.0 (design system item) · **Decision:** ADR-8 (the user, 2026-11-12)

**Why:** the user decided that Affibloom keeps Lasso's features, labels and workflow but gets its own visual identity.
Tasks 8 and 9 were built in Lasso's look, and Tasks 10–17 add most of the UI kit (tables, modals, forms, image picker).
The design system has to exist and be approved before those components are built, and the two existing screens are
restyled to it now, while only two screens use the kit.

**User action:** an administrator opens Affibloom → Dashboard and Settings → General and sees Affibloom's own design:
its palette, type, header, tabs, cards, buttons, toggles, help tips and toast. Every label, help text, field and the
save flow are unchanged.

**Request path:** the same as Tasks 8 and 9 (`GET admin.php?page=affibloom`, `GET …page=affibloom-settings`,
`POST admin-post.php`); this task changes only what comes back: `assets/admin/admin.css` and, where the approved layout
needs it, the markup of `templates/admin/{dashboard,settings}.php` and `partials/*`. No PHP class changes.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `docs/ui/design-system.md` (palette, type, spacing, components, layout, accessibility rules) | `admin.css`; every later admin task (10–17, 23–24, 31, 34, 36) |
| Design tokens as CSS custom properties in `admin.css` (replacing Lasso's measured ones) | the components in `admin.css` |
| `tests/E2E/admin-design.spec.ts` | `npm run test:e2e` |

**Files:**
- Create: `docs/ui/design-system.md`, `tests/E2E/admin-design.spec.ts`
- Modify: `assets/admin/admin.css`; `templates/admin/dashboard.php`, `templates/admin/settings.php`,
  `templates/admin/partials/{header,toggle,help,toast}.php` (layout and class hooks only; labels and help texts stay);
  `docs/ui/tokens.md` (header note only)

- [x] **Step 1: Proposal (no code)**

Write `docs/ui/design-system.md` with status **Proposed**, and show it to the user with a preview of the header, a
Settings card with toggles and help tip, buttons in every state, a notice and a table row (an HTML preview in the
scratchpad, not in the plugin). It covers:

- **Palette:** brand, accent, neutrals, success, warning, danger and info, each with its hex value and its role; the
  contrast ratio of every text/background pair it allows (WCAG 2.2 AA: 4.5:1 for text, 3:1 for UI parts and focus).
  Distinct from Lasso's purple/green.
- **Typography:** font stack (a system stack, or a font bundled in the plugin: no third-party requests), sizes for page
  title, section title, body, small and label text, weights and line heights.
- **Spacing, radius, borders, elevation:** one scale each.
- **Layout:** page width, header, where the page title and tabs sit, cards, form rows and the actions row; behaviour at
  wp-admin's 782 px and 960 px breakpoints.
- **Components:** header with Affibloom's logo mark, tabs, page title, card, buttons (primary, secondary, danger,
  disabled, focus), toggle, help tip (keyboard and screen reader, U3), form field and errors, notice/toast, table with
  search and pagination, modal, counters/badges, empty state.
- **Accessibility:** visible focus on every control, no meaning by colour alone, reduced motion.
- **What stays Lasso's:** labels, help texts, field and step order (interface spec), and nothing visual.

- [x] **Step 2: Approval (gate 4 for the design)**

The user approves the proposal or asks for changes. Record the approval (date, any changes) in `design-system.md`
(status **Approved**) and in `progress.md`. No CSS changes before this.

- [x] **Step 3: Failing test**

`tests/E2E/admin-design.spec.ts` checks the design system on the two existing screens by computed style, using the
approved token values: header background, page background, body font family, primary button colours, toggle on/off
colours, and a visible focus outline on a toggle and a help tip reached by the Tab key. It also checks that the help
tip's text is reachable by keyboard and linked to its field (`aria-describedby`).

```bash
npx.cmd playwright test admin-design
```
Expected: it fails, because the screens still have Lasso's values (run by the user: it signs in).

- [x] **Step 4: Tokens and components**

Replace the token block and the component rules in `assets/admin/admin.css` with the approved design system: header,
page, title, tabs, card, section, field, button, toggle, help tip, toast. Keep the class names the templates, tests and
later tasks use (`affibloom-header`, `affibloom-tabs__tab`, `affibloom-toggle__input`, `affibloom-help__button`,
`affibloom-toast`, `#affibloom-settings-save`, …). Every value traces to a token in `design-system.md`.

- [x] **Step 5: Templates (layout only)**

Change the markup of the Dashboard and Settings templates only where the approved layout differs (for example where
the page title, tabs and the Save button sit). Labels, help texts, ids, `name`s and the form's fields and order do not
change.

- [x] **Step 6: Run and look**

```bash
composer lint
composer analyse
composer test:unit
npx.cmd playwright test admin-shell admin-design
npx.cmd playwright test acceptance/F9 -g AC3
```
Expected: lint, analyse and unit green; `admin-shell`, `admin-design` and F9 AC3 pass (the user's run). Open both
screens at 1920, 1280 and 782 px wide: they match the approved preview; the Tab key reaches every control with a
visible focus; the toast shows after a save. Tick roadmap 2.0's design-system item.

- [x] **Step 7: Commit**

```bash
git add -A
git commit -m "Give the admin Affibloom's own design system

The user decided on an own visual identity: Lasso's labels and
workflow stay, the colours, type and layout are Affibloom's."
```

---

### Task 10A: Settings → Display — display type, theme, colours and labels

**Roadmap:** 2.1 · **Features:** F16 (gates 1–2: `docs/features/F16/requirements.md`, `analysis.md`)

**Why:** the Display screen edits one configuration per display type, and every later Display task adds another
panel to it. This task builds the screen, the stored model and the save path, with the panel the site already
depends on: theme, colours and button labels. Nothing here renders a box; Phase 3 consumes the model.

**User action:** an administrator opens Settings → Display, sees Single selected, switches to Grid or List,
picks a theme, changes a colour or a button label, saves, and the values come back after a reload.

**Request path:** `admin.php?page=affibloom-settings&tab=display[&type=grid]` → `SettingsScreen` →
`DisplayScreen::render()` → `DisplaySettingsService::current()` → `DisplaySettingsRepository::get()` (option
`affibloom_display`) → `DisplaySettings` → `templates/admin/settings-display.php`. Save:
`admin-post.php?action=affibloom_save_display` → `DisplayController::save()` → `DisplaySettingsService::update_type()`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Display\DisplayType` (single, grid, list) | `DisplaySettings`, `DisplayScreen` |
| `Domain\Display\Theme` (the 8 names, default `Lab`) | `DisplayConfig`, the theme select |
| `Domain\Display\Palette` (8 colours, 2 button texts, badge text) | `DisplayConfig`, the colours panel |
| `Domain\Display\DisplayConfig` (theme + palette; 10B and 10C add the rest) | `DisplaySettings` |
| `Domain\Display\DisplaySettings` (one config per type) | service, screen |
| `Domain\Display\DisplaySettingsRepository` (interface) | service |
| `Infrastructure\WordPress\OptionDisplaySettingsRepository` | composition root |
| `Application\Display\DisplaySettingsService` | screen, controller |
| `Interface\Admin\DisplayScreen`, `DisplayController` | `Plugin::modules()` |
| `templates/admin/settings-display.php` + `partials/color-field.php` | `DisplayScreen` |

**Files:** new `src/Domain/Display/*`, `src/Application/Display/DisplaySettingsService.php`,
`src/Infrastructure/WordPress/OptionDisplaySettingsRepository.php`, `src/Interface/Admin/DisplayScreen.php`,
`src/Interface/Admin/DisplayController.php`, `templates/admin/settings-display.php`,
`templates/admin/partials/color-field.php`, `tests/Unit/Domain/Display/*`. Modify `src/Plugin.php`,
`src/Domain/Settings/Settings.php`, `src/Application/Settings/SettingsService.php`,
`templates/admin/settings.php`, `assets/admin/admin.css`, `tests/E2E/acceptance/F16.spec.ts`.

- [x] **Step 1: The failing acceptance test**

`tests/E2E/acceptance/F16.spec.ts` (new, from requirements AC1 and AC8):

```ts
test( 'AC1 each display type keeps its own theme, colours and labels', async ( { page } ) => {
	await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=display' );
	await expect( page.getByRole( 'link', { name: 'Single' } ) ).toHaveAttribute( 'aria-current', 'page' );
	await page.getByLabel( 'Theme' ).selectOption( 'Llama' );
	await page.getByLabel( 'Primary button text', { exact: true } ).fill( 'Buy It' );
	await page.getByRole( 'button', { name: 'Save Changes' } ).click();

	await page.getByRole( 'link', { name: 'Grid' } ).click();
	await expect( page.getByLabel( 'Theme' ) ).toHaveValue( 'Cutter' );          // untouched
	await page.getByRole( 'link', { name: 'Single' } ).click();
	await expect( page.getByLabel( 'Theme' ) ).toHaveValue( 'Llama' );           // kept
	await expect( page.getByLabel( 'Primary button text', { exact: true } ) ).toHaveValue( 'Buy It' );
} );
```

Expected red: there is no `type` segment and no Theme field, so the first `expect` fails on a missing link.

- [x] **Step 2: Domain — the three types, the themes and one palette**

`DisplayType`: `SINGLE`, `GRID`, `LIST` constants, `all()`, `from( string $raw ): self` (unknown → single),
`label()`. `Theme`: `NAMES = array( 'Cactus', 'Cutter', 'Flow', 'Geek', 'Lab', 'Llama', 'Money', 'Splash' )`,
`DEFAULT = 'Lab'`, `from( string $raw )` (unknown → default). `Palette`: the keys `main`, `title`, `background`,
`button`, `secondary_button`, `button_text`, `pros`, `cons`, `primary_button_text`, `secondary_button_text`,
`badge_text`, with Lasso's defaults, `from_array()` that keeps any CSS colour string (edge case 7) and runs the
two button texts through the icon-markup allowlist, and `to_array()`.

- [x] **Step 3: Domain — `DisplayConfig` and `DisplaySettings`**

`DisplayConfig` holds a `Theme` and a `Palette` and is immutable (`with_theme()`, `with_palette()`).
`DisplaySettings::from_array()` builds one config per type, filling missing types from the defaults, and
`to_array()` writes `{single: {...}, grid: {...}, list: {...}}`. Malformed input never throws (edge case 4).

- [x] **Step 4: Repository and service**

`DisplaySettingsRepository` with `get(): DisplaySettings` and `save( DisplaySettings ): void`;
`OptionDisplaySettingsRepository` reads and writes the option `affibloom_display` with `autoload = yes`.
`DisplaySettingsService::current()`, `::config( DisplayType )`, `::update_type( DisplayType $type, array $input ): array`
(returns refused keys, as `SettingsService` does).

- [x] **Step 5: Screen, template and save**

`SettingsScreen` routes `tab=display` to `DisplayScreen`, which reads `type` from the query (default single).
The template keeps the Aurora card layout from Task 10 Step 4: a card with the type segment
(`.affibloom-tabs` reused — second use, so the partial is extracted now), the Theme select, and the ten
colour and label fields in `.affibloom-grid`, in Lasso 370's order (`Badge`, `Title`, `Background`,
`Button + Badge Text`, `Pros`, `Cons`, `Primary button text`, `Primary button color`, `Secondary button text`,
`Secondary button color`). `DisplayController` checks `Capabilities::SETTINGS` and the nonce, then saves and
redirects to the same type with the `saved`/`partly-saved` notice.

- [x] **Step 6: Move the per-type keys out of the flat settings**

`Settings::DEFAULTS` and `SettingsService::FIELDS['display']` lose `color_*`, `primary_button_text` and
`secondary_button_text` (they are per type now) and keep `disclosure_text` and `link_from_display_title`
(site-wide, Task 10E). Update `SettingsServiceTest` accordingly.

- [x] **Step 7: Run and look**

```bash
composer lint && composer analyse && composer test:unit
```
Then WP-CLI: read `affibloom_display` after a save and check the three buckets. In the browser pane: the three
type links, the theme select, the ten fields, keyboard order, 1440px and 782px.

- [x] **Step 8: Commit**

```bash
git add -A
git commit -m "Add the Display settings screen with themes, colours and labels

One configuration per display type, stored in affibloom_display, as Lasso keeps them."
```

---

### Task 10B: Settings → Display — elements and density

**Roadmap:** 2.1 · **Features:** F16 (requirements 2.2, 2.3; AC4, AC5)

**Why:** the live site's boxes are drawn with the `spacious` density preset and with Price, Pros & cons and
Timestamp switched off, so these two panels decide what a box contains and how much room it takes. They are the
second half of the "Layout & spacing" tab.

**User action:** an administrator opens the Elements accordion and turns Price off, then picks the Compact
density chip, then nudges "Card padding" down; the preset chip changes to Custom, the values survive a save, and
a stepper refuses to go past its limit.

**Request path:** as Task 10A, with `display_elements` and `display_density` added to the same form and the same
save handler.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Display\Elements` (11 flags) | `DisplayConfig` |
| `Domain\Display\Density` (preset + 10 values, the 3 preset maps, the limits) | `DisplayConfig` |
| `Density::with_value()` (clamp to min/max/step, preset → `custom`) | the steppers |
| `templates/admin/partials/accordion.php`, `toggle-row.php`, `stepper.php`, `clamp-group.php` | the template |
| `assets/admin/display-settings.js` | `DisplayScreen` enqueue |
| `tests/Unit/Domain/Display/DensityTest.php`, `ElementsTest.php` | `composer test:unit` |

- [x] **Step 1: Failing unit tests for the density rules**

```php
public function test_a_preset_applies_its_value_map(): void {
	$density = Density::preset( 'spacious' );
	self::assertSame( 32, $density->value( 'gap' ) );
	self::assertSame( 320, $density->value( 'imageHeight' ) ); // presets are not clamped (edge case 1)
}

public function test_editing_a_value_clamps_it_and_makes_the_preset_custom(): void {
	$density = Density::preset( 'balanced' )->with_value( 'padding', 99 );
	self::assertSame( 48, $density->value( 'padding' ) );  // max
	self::assertSame( 'custom', $density->preset() );
}

public function test_an_unknown_value_key_is_refused(): void {
	$this->expectException( InvalidArgumentException::class );
	Density::preset( 'balanced' )->with_value( 'nope', 1 );
}
```

Expected red: `Class "Affibloom\Domain\Display\Density" not found`.

- [x] **Step 2: Domain — `Density`**

The three preset maps exactly as Lasso has them (requirements 2.2), the limits
`gap 0–48/2`, `padding 0–48/2`, `spaceBetween 0–28/1`, `titleSize 11–40/1`, `descSize 10–22/1`,
`priceSize 10–26/1`, `buttonSize 10–22/1`, `imageHeight 48–280/4`, and `titleLines`/`descLines` as the clamp
set `0, 1, 2, 3` where `0` means "All". `with_value()` rounds to the step, clamps to the range and sets the
preset to `custom`; `preset( string )` replaces every value from the map.

- [x] **Step 3: Domain — `Elements`**

The 11 flags from requirements 2.3, all on by default, `with( string $key, bool $on )`, `visible( string $key )`,
`to_array()`. `disclosure` is **not** stored here: the template reads and writes the site-wide
`show_disclosure*` settings for that row (behaviour B5), which Task 10E owns; until then the row is rendered
from `Settings`.

- [x] **Step 4: `DisplayConfig` gains both, and the service saves them**

`DisplayConfig::with_elements()`, `::with_density()`; `DisplaySettingsService::update_type()` accepts
`elements` and `density` and returns the keys it refused.

- [x] **Step 5: Template and script**

The "Layout & spacing" panel gets the Density chips with their hint line, the Elements accordion with its
"n of 11 shown" summary, and the Spacing / Typography / Image accordions with steppers and clamp groups, in
Lasso's order and labels. The accordion, stepper and clamp-group partials are extracted here because each has
three or more callers. `display-settings.js` keeps the hidden JSON in step, applies the clamp in the browser,
switches the chip to Custom on a stepper edit, and updates the summaries; the form still saves without
JavaScript (each control also posts its own field).

- [x] **Step 6: Run and look**

Unit tests for clamping, the preset maps and the element flags; lint and analyse; then in the browser pane:
turn Price off, pick Compact, nudge a stepper, save, reload, and confirm through WP-CLI that
`affibloom_display` holds `preset: custom` with the edited value.

- [x] **Step 7: Commit**

```bash
git add -A
git commit -m "Add element visibility and density to the Display settings

Lasso's three spacing presets, its ten values with their limits, and the eleven element toggles."
```

---

### Task 10C: Settings → Display — layout, the coercion rules and the phone override

**Roadmap:** 2.1 · **Features:** F16 (requirements 2.4; AC6, AC7)

**Why:** layout is what makes the live Single box what it is — side by side, image left, buttons full width —
and it is the only part of the Display model with rules of its own: image position depends on orientation,
three controls appear only in certain states, and phones can keep their own layout.

**User action:** an administrator switches Card layout to Stacked and watches Image position change from
left/right to top/bottom; switches the preview to phone width, sets Text alignment to Center, and the panel says
"Custom mobile layout" with a "Use desktop layout" button that clears it; desktop keeps Left.

**Request path:** as Task 10A, with `display_layout` added to the form and the save handler.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Display\Layout` (7 desktop keys + optional phone override) | `DisplayConfig` |
| `Layout::with( string $key, string $value )` incl. the orientation coercion (B2) | the layout segments |
| `Layout::with_phone()`, `::phone()`, `::clear_phone()` | the phone note and its reset |
| `DisplaySettings::columns()` / `with_columns()` (grid only, 1–3) | the Columns row |
| `templates/admin/partials/segment.php` | the layout rows |
| `tests/Unit/Domain/Display/LayoutTest.php` | `composer test:unit` |

- [x] **Step 1: Failing unit tests for the layout rules**

```php
public function test_image_position_follows_the_orientation(): void {
	$layout = Layout::defaults( DisplayType::single() )->with( 'imagePosition', 'left' );
	self::assertSame( 'left', $layout->value( 'imagePosition' ) );

	$stacked = $layout->with( 'orientation', 'stacked' );
	self::assertSame( 'top', $stacked->value( 'imagePosition' ) );   // left → top
	self::assertSame( 'bottom', $stacked->with( 'imagePosition', 'right' )->value( 'imagePosition' ) );
}

public function test_an_unknown_value_falls_back_to_the_type_default(): void {
	self::assertSame( 'left', Layout::defaults( DisplayType::single() )->with( 'align', 'diagonal' )->value( 'align' ) );
}

public function test_the_phone_layout_only_holds_the_six_keys_and_can_be_cleared(): void {
	$layout = Layout::defaults( DisplayType::single() )->with_phone( 'align', 'center' )->with_phone( 'mobileOverflow', 'swipe' );
	self::assertSame( array( 'align' => 'center' ), $layout->phone() );          // mobileOverflow is not a phone key
	self::assertSame( 'left', $layout->value( 'align' ) );                       // desktop untouched
	self::assertSame( array(), $layout->clear_phone()->phone() );
}
```

Expected red: `Class "Affibloom\Domain\Display\Layout" not found`.

- [x] **Step 2: Domain — `Layout`**

Keys and options from requirements 2.4. Defaults per type: single and list
`side-by-side / right / left / spread / hug / stack / contain`; grid `stacked / top / center / spread / hug /
stack / contain`. `with()` validates against the option list (unknown → that type's default) and then applies
the coercion: side by side allows left/right (top→left, bottom→right, anything else→right); stacked allows
top/bottom (left→top, right→bottom, anything else→top). The phone override stores only `orientation`,
`imagePosition`, `align`, `buttonAlign`, `ctaWidth`, `imageFit`; a key that is absent follows desktop.

- [x] **Step 3: Columns on `DisplaySettings`**

`columns` is site-wide, not per type (Lasso stores it at the top of `display_layout`): an integer 1–3, default 3.

- [x] **Step 4: Template and script**

A Layout accordion with its summary line, and the rows in Lasso's order: Columns, Card layout, Image position,
Text alignment, Button alignment, Button width, Mobile overflow, Image fit. Visibility follows B6, which the
script applies: Columns only for Grid, Card layout hidden for Grid, Mobile overflow only for List at phone
preview width, the phone note and reset only at phone preview width. The segment partial is extracted here
(six callers). Image position rebuilds its options when the orientation changes.

- [x] **Step 5: Run and look**

Unit tests for coercion, fallbacks and the phone keys; lint and analyse; in the browser pane walk the rules:
Grid hides Card layout, Stacked switches Image position to top/bottom, a phone edit shows "Custom mobile
layout" and the reset clears it. Confirm the stored JSON with WP-CLI.

- [x] **Step 6: Commit**

```bash
git add -A
git commit -m "Add the Display layout controls and the phone override

Lasso's orientation, image position, alignment, button width, overflow and fit, with its coercion rules."
```

---

### Task 10D: Settings → Display — the live preview (superseded here: the task is in Phase 3)

**Roadmap:** 2.1 · **Features:** F16 (AC2)

> **Runs in Phase 3, not here.** Its entry point renders the real box template, which Task 18x builds. Written
> now so the Display feature is complete on paper; scheduled immediately after 18x so the preview and the front
> end can never drift. Until then the Display screen ships without a preview, and the panels are checked by
> their stored values and by the Phase 3 visual tests.

**User action:** an administrator changes any Display setting and sees a sample box redraw at desktop (1280px)
and phone (390px) width, before saving.

**Request path:** the screen posts the unsaved settings to `GET/POST /affibloom/v1/display-preview` →
`PreviewController` → `DisplayPresentation` → the box template with a stub product → HTML into the preview
frame.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Rest\DisplayPreviewController` (`manage_options`) | the preview frame |
| `Application\Display\PreviewProduct` (a stub product, no database) | the controller |
| preview frame markup, the width segment, the redraw in `display-settings.js` | `DisplayScreen` |

Steps follow the same shape: a failing E2E check that the preview redraws, the route, the stub, the frame, the
width segment, then the run-and-look at both widths. Lasso forces its advanced rendering on for previews
(behaviour B11); Affibloom passes the settings explicitly instead, so no global state is touched.

---

### Task 10E: Settings → Display — disclosure, behaviour, Custom CSS and Reset

**Roadmap:** 2.1 · **Features:** F16 (requirements 2.6, 2.7; AC9, AC10, AC11)

**Why:** the rest of the Display tab is site-wide rather than per type: the disclosure text and the three scopes
that decide where it appears, whether titles link, Site Stripe markup, brag mode, the editor's own CSS, and the
Reset that puts everything back. It also completes the Elements panel, whose Disclosure row writes these keys
(behaviour B5).

**User action:** an administrator edits the disclosure text, turns "Disclosure in Grids" on, pastes a CSS rule,
saves, and sees both come back after a reload; then presses Reset Settings, confirms, and every Display setting
returns to its default.

**Request path:** the same form and handler; the flat keys go through `SettingsService::update_tab( 'display' )`
and the structured ones through `DisplaySettingsService`. Reset:
`admin-post.php?action=affibloom_reset_display` → `DisplayController::reset()`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Settings` keys `show_price`, `show_disclosure_grid`, `show_disclosure_list`, `show_disclosure_table`, `keep_site_stripe_ui`, `enable_brag_mode`, `brag_url`, `custom_css` | `SettingsService::FIELDS['display']` |
| cleaners `url` and `css` in `SettingsService::clean()` | the two new field types |
| `Infrastructure\WordPress\CustomCss` (allowlist + `wp_add_inline_style`) | `BoxAssets` (Phase 3), the preview |
| `DisplaySettingsService::reset()` | `DisplayController::reset()` |
| `templates/admin/partials/confirm-dialog.php` | Reset, and Task 17's delete |

- [x] **Step 1: Failing tests**

A unit test that `clean( 'css', '…</style><script>…' )` keeps the rule and drops the markup; a unit test that
`reset()` restores the defaults for all three types; and an acceptance check (AC9) that the disclosure text
saved here is the one the box prints — marked `test.fixme` until Phase 3, with the reason in the test.

- [x] **Step 2: The eight site-wide keys**

Added to `Settings::DEFAULTS` with Lasso's defaults (`show_price` false, `show_disclosure_grid` false,
`show_disclosure_list` true, `show_disclosure_table` true, `keep_site_stripe_ui` false, `enable_brag_mode`
false, `brag_url` empty, `custom_css` empty) and to `SettingsService::FIELDS['display']` with their types.
`brag_url` uses a new `url` cleaner (`esc_url_raw`, empty allowed); `custom_css` a new `css` cleaner that keeps
Lasso's character allowlist (`format_css`), so `<` can never reach the page.

- [x] **Step 3: The Disclosure row in the Elements panel**

The Elements accordion's `disclosure` row now writes `show_disclosure` (single), `show_disclosure_grid` (grid)
or `show_disclosure_list` **and** `show_disclosure_table` (list), per behaviour B5 and requirements 2.6.

- [x] **Step 4: The "Disclosure & behavior" card**

Disclosure text (textarea), Show Price, Disclosure in Single Display, Disclosure in Grids, Disclosure in Lists
& Tables, Link from Display Title, Keep Site Stripe UI, Brag mode URL, Enable Brag Mode — Lasso's labels, help
texts and order — then a Custom CSS card with a monospace textarea.

- [x] **Step 5: Reset Settings**

A secondary button in the card footer opens the confirm dialog ("This returns every Display setting to its
default. Your products are not affected."); confirming posts to the reset action, which writes the default
`DisplaySettings` and the default flat keys, then redirects with a toast.

- [x] **Step 6: Run and look, then commit**

```bash
git add -A
git commit -m "Complete the Display settings with disclosure, behaviour, custom CSS and reset"
```

---

### Task 10F: Settings → Amazon, in full

**Roadmap:** 2.1 · **Features:** F2, F16's sibling audit (the Amazon tab, ~20 controls)

**Why:** every Amazon link on the site carries the tracking ID from this tab, and Lasso's live values decide how
links are rewritten: multiple tracking IDs with a whitelist, auto-monetization, commission upgrades, short
links, mobile deep linking, the local-store redirect and Geni.us formatting are all **on** today. The old Task
10 planned two of these controls.

**User action:** an administrator enters the tracking ID, adds a second ID to the whitelist, turns a preference
off, saves, and an invalid ID is refused while the rest is saved.

**Request path:** `tab=amazon` → `SettingsScreen` → `SettingsService` → `Settings` → option, as Task 9.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Settings` keys for the whole tab (list below) | `SettingsService::FIELDS['amazon']` |
| cleaners `tag_list` (the whitelist) and `int_range` (out-of-stock days) | `clean()` |
| `templates/admin/partials/tag-list.php` | the whitelist field |
| `Domain\Amazon\TrackingId` (the format rule, reused by `AmazonLinkService` in Task 12) | the cleaner |

Keys, with Lasso's defaults and the live values: `tracking_id` (`vbvault-20`), `allow_multiple_tracking_ids`
(on), `tracking_id_whitelist` (`[vbvault-20]`), `default_tracking_country` (`us`), `auto_monetize_amazon` (on),
`show_prime` (on), `show_discount_pricing` (off), `commission_upgrades` (on), `update_pricing_daily` (off),
`pricing_locations_only` (on), `force_change_from_link_details` (off), `keep_title_when_url_replaced` (on),
`shorten_amazon_links` (on), `mobile_deep_linking` (on), `international_click` (on), `keep_genius_formatting`
(on), `scan_sitestripe_in_post_content` (off), `days_before_out_of_stock_alerts` (0).

The **Amazon Product Data API** block (Default Tracking ID country, Credential ID, Secret, Partner tag,
Version, Validate) belongs to task C1 and is built there; this task leaves its card out rather than shipping
dead fields, and C1 adds it with its first caller.

Steps follow Task 9's shape: failing unit tests for the new cleaners (an invalid tag in the whitelist is
refused, the whole list is kept; `days_before_out_of_stock_alerts` clamps to 0–365), the keys, the fields per
section (Amazon Associates Accounts, Auto-Monetize Amazon Links, Amazon Preferences), the Aurora template, then
run-and-look with a save of `vbvault` (refused, partly-saved toast) and `vbvault-20` (saved).

```bash
git add -A
git commit -m "Add the Amazon settings tab in full

The tracking ID and its whitelist, auto-monetization and Lasso's thirteen Amazon preferences."
```

---
### Task 11A: "Add A New Link" — the product exists

**Roadmap:** 2.2, 2.4 · **Features:** F18 (gates 1–2: `docs/features/F18/requirements.md`, `analysis.md`), F1 AC1,
F3 AC1–AC2

**Why:** every product starts in "Add A New Link". This task makes the first product exist: the post type, the
product model, its storage, one REST route and the modal in Lasso's single-link mode. Normalisation and duplicates
(11B), bulk add (11C), naming (11D) and the link defaults (11E) each widen this path afterwards.

**User action:** on any Affibloom screen an administrator clicks **Add New Link** at the right of the header. The
dialog **Add A New Link** opens ("Enter the affiliate link you would like to track."), with the focus in the link
field. They paste `https://mizuno.pxf.io/c/123/456/789?u=https%3A%2F%2Fmizunousa.com%2Fwave` and press Enter. A
product named **Pxf** (Lasso's `get_title_by_url` rule) is created with the link exactly as pasted, and the browser
goes to the Dashboard with the toast **Link added.** An empty box shows **URL is incorrect.** under the field and
sends nothing; `not a link` shows **This is not a valid link.** Typing clears the message. Escape, the close
button or a click on the backdrop closes the dialog, clears it, and returns the focus to the button.

**Transitional, until Task 14:** Lasso opens the new product's page after an add. That page is Task 14, so until
then the route answers with `redirect_to` = the Dashboard with the toast; Task 14 changes only that URL. The
Dashboard's "No products yet." stays until Task 13 builds the list.

**Request path:**
```
header [Add New Link] → #affibloom-add-modal (admin.js)
  → POST /wp-json/affibloom/v1/products   { url: "…" }   (wp.apiFetch adds the REST nonce)
  → ProductsController::create()           permission Capabilities::PRODUCTS
  → ProductService::create_from_url()
       AffiliateUrl::from_string()         → InvalidField( url, empty | invalid_url )
       Product::draft( $url->site_name(), $url )
       ProductRepository::save()           → wp_insert_post( affibloom_product, meta_input )
  ← 201 { data: { id, name, url, redirect_to } }
  ← 422 affibloom_invalid_field { data: { status, fields: { url: message } } }
  → admin.js: location = redirect_to, or the message under the field
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\InvalidField` (field + reason code) | `AffiliateUrl`, then every later validator |
| `Domain\Product\AffiliateUrl` (`from_string`, `value`, `site_name`) | `ProductService`, `PostTypeProductRepository` |
| `Domain\Product\ProductType` (only `Other`; Task 12 adds `Amazon`) | `Product::draft()`, the repository |
| `Domain\Product\Product` (`id`, `name`, `url`, `type`; `draft()`, `with()`) | service, repository |
| `Domain\Product\ProductRepository` (`save()` only; 11B adds the lookup, 14 `get()`) | `ProductService` |
| `Infrastructure\WordPress\ProductPostType` | `Plugin::modules()` (`init`) |
| `Infrastructure\WordPress\PostTypeProductRepository::save()` | `ProductService` |
| `Application\Products\ProductService::create_from_url()` | `ProductsController::create()` |
| `Interface\Rest\ProductsController`, `ProductResource::summary()` | `rest_api_init`, the controller |
| `Interface\Messages::for_reason()` | the controller |
| `Interface\Admin\Notice` (`SettingsNotice` renamed: its third screen) and the `link-added` code | Settings, Display, Dashboard |
| the header's **Add New Link** button, `templates/admin/partials/add-modal.php` | `partials/header.php` |
| modal and add-link code in `assets/admin/admin.js`; `wp-api-fetch` dependency | `AdminAssets` |

Not created here, each with its own task: `_affibloom_url_key` and `find_by_url_key()` (11B), the textarea, hint
and counter (11C), page titles (11D), the link toggles (11E), `get()` (14), "Continue Anyway" (it opens the product
page, 14).

**Files:** new `src/Domain/InvalidField.php`, `src/Domain/Product/{AffiliateUrl,ProductType,Product,ProductRepository}.php`,
`src/Infrastructure/WordPress/{ProductPostType,PostTypeProductRepository}.php`,
`src/Application/Products/ProductService.php`, `src/Interface/Rest/{ProductsController,ProductResource}.php`,
`src/Interface/Messages.php`, `templates/admin/partials/add-modal.php`, `tests/E2E/acceptance/F18.spec.ts`,
`tests/Unit/Domain/Product/AffiliateUrlTest.php`, `tests/Unit/Application/ProductServiceTest.php`,
`tests/Integration/ProductsCreateTest.php`. Rename `src/Interface/Admin/SettingsNotice.php` → `Notice.php`. Modify
`src/Plugin.php`, `src/Interface/Admin/{AdminAssets,DashboardScreen,SettingsScreen,DisplayScreen}.php`,
`templates/admin/{dashboard.php,partials/header.php}`, `assets/admin/{admin.js,admin.css}`,
`docs/spec/interface.md` (sections 1 and 2).

- [x] **Step 1: The failing acceptance test**

`tests/E2E/acceptance/F18.spec.ts` (new, from F18 AC1, AC5 and the modal's behaviour):

```ts
test( 'AC1 a link pasted into Add A New Link becomes a product, kept exactly', async ( { page } ) => {
	const link = `https://mizuno.pxf.io/c/123/456/789?u=https%3A%2F%2Fmizunousa.com%2Fwave&subId1=${ unique( 'f18' ) }`;
	await page.goto( '/wp-admin/admin.php?page=affibloom' );
	await page.getByRole( 'button', { name: 'Add New Link' } ).click();
	const dialog = page.getByRole( 'dialog', { name: 'Add A New Link' } );
	await expect( dialog.getByLabel( 'URL' ) ).toBeFocused();
	await dialog.getByLabel( 'URL' ).fill( link );
	await dialog.getByLabel( 'URL' ).press( 'Enter' );
	await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Link added.' );
	const id = wp( 'post', 'list', '--post_type=affibloom_product', `--meta_key=_affibloom_url`, `--meta_value=${ link }`, '--field=ID' );
	expect( wp( 'post', 'get', id, '--field=post_title' ) ).toBe( 'Pxf' );
} );

test( 'AC5 an empty box is refused in the modal and nothing is sent', async ( { page } ) => { … } );
test( 'the dialog closes with Escape and gives the focus back', async ( { page } ) => { … } );
```

Expected red: there is no "Add New Link" button, so the first click times out.

- [x] **Step 2: Unit tests, one level in**

`AffiliateUrlTest`: a tracking link is kept exactly (after trimming); `''` → `empty`; `not a link`, `ftp://x.com/a`,
`javascript:alert(1)`, `https://` and `https://exa mple.com/` → `invalid_url`; `site_name()` gives `Pxf` for
`mizuno.pxf.io`, `Mizunousa` for `www.mizunousa.com`, `Under Armour` for `under-armour.com` (Lasso's rule: the label
before the suffix, hyphens to spaces, words capitalised) and `Example` for `example.co.uk` (Lasso's rule would give
"Co"; the two-letter-suffix case is the one improvement). `mizunousa.com/wave` is refused **here** and becomes
accepted in 11B with Lasso's repair. `ProductServiceTest` with an in-memory repository: a link is saved once with
its site name and type `other`; an invalid link throws `InvalidField` and saves nothing.

- [x] **Step 3: Route** — `ProductsController` registers `POST /affibloom/v1/products` with one required string
argument `url` (no length cap and no sanitising: the link is kept as pasted, the domain validates it), permission
`Capabilities::PRODUCTS`. `create()` returns 201 `{ data: summary + redirect_to }`; an `InvalidField` becomes
`WP_Error( 'affibloom_invalid_field', $message, { status: 422, fields: { url: $message } } )`.

- [x] **Step 4: Service** — `ProductService::create_from_url( string $input ): Product`: `AffiliateUrl::from_string()`,
`Product::draft( $url->site_name(), $url )`, `ProductRepository::save()`.

- [x] **Step 5: Domain** — `InvalidField extends DomainException` (readonly `field`, `reason`). `AffiliateUrl`: trim;
empty → `empty`; otherwise `parse_url` must give an `http`/`https` scheme and a host without whitespace, else
`invalid_url`; the value is stored exactly as trimmed. `ProductType::Other`. `Product`: readonly `id` (null until
saved), `name`, `url`, `type`; `draft()` sets type `Other`; `with()` returns a copy. `ProductRepository::save()`.

- [x] **Step 6: Infrastructure** — `ProductPostType` registers `affibloom_product` on `init`: not public, no core UI,
no REST, no rewrite, title only. `PostTypeProductRepository::save()` inserts (or updates when `id` is set) a
`publish` post with `meta_input` `_affibloom_url` and `_affibloom_type`, slashed with `wp_slash()`; a `WP_Error`
becomes a `RuntimeException`.

- [x] **Step 7: Messages, notice, modal** — `Messages::for_reason()`: `empty` → "URL is incorrect." (Lasso's words),
`invalid_url` → "This is not a valid link." `SettingsNotice` becomes `Notice` with `link-added` → "Link added.";
`DashboardScreen` renders the toast with it. The header gets the white **Add New Link** button (design system §5)
and renders `add-modal.php` after itself when the user can manage products. The modal follows the design system's
Modal (560px, 16px radius, toast shadow, navy backdrop; header with title, subtitle and close; body; footer with the
primary **Add Link**), the field the Text field spec (error below it, `aria-describedby`, `aria-invalid`), with a
visually hidden label **URL** and Lasso's placeholder `https://www.example.com/affiliate-id`. The words the script
needs come from `data-` attributes on the form, escaped in PHP. `admin.js`: open, focus the field, trap Tab, close
on Escape/close/backdrop and give the focus back; Enter or Add Link submits; empty → the `empty` message without a
request; while sending, the field and button are disabled and the button says "Adding…"; 201 → `location.assign(
redirect_to )`; 422 → `fields.url` under the field; any other failure → the server's message there. Typing clears the
message. `AdminAssets` adds the `wp-api-fetch` dependency, which carries the REST root and nonce in wp-admin.

- [x] **Step 8: Wire it up** — `Plugin::modules()` adds `new ProductPostType()` and
`new ProductsController( new ProductService( new PostTypeProductRepository() ), new ProductResource() )`, and passes
the one `Notice` to the Dashboard too.

- [x] **Step 9: Run and look** — `composer lint`, `composer analyse`, `composer test:unit`. Through WP-CLI: the route
as an Author answers 403, as an administrator creates the post with the link unchanged. In the browser pane: the
button on Dashboard and Settings, the dialog's focus, Escape, the empty and `not a link` messages, an Impact link
added and the toast, at 1440 and 782px. The user runs `npx.cmd playwright test tests/E2E/acceptance/F18.spec.ts`.
The test products are removed afterwards.

- [x] **Step 10: Commit** (`597fd4a`; the docs commit `13a01dc` before it)

```bash
git add -A
git commit -m "Add a product from one link in \"Add A New Link\"

The first piece of Lasso's add flow: the product post type and its
storage, POST /affibloom/v1/products, and the header's Add New Link
modal in single-link mode. Empty and non-URL input is refused with
Lasso's message; everything else is kept exactly as pasted."
```

---
### Task 11B: "Add A New Link" — Lasso's repair and the duplicate rule

**Roadmap:** 2.2 · **Features:** F18 AC2–AC4 (decisions D1, D2)

**Why:** two of the sweep's corrections live on the single-link path 11A built. Lasso repairs what it is given
rather than refusing it (`Lasso_Helper::add_https`), and it never makes a second product for a link it already has:
it sends the editor to the existing one with "This product already exists. Please update the Primary URL or add a
new link." (F18 requirements, sections 3 and 5).

**User action:** an administrator pastes `mizunousa.com/wave` into Add A New Link; a product is created with the link
`https://mizunousa.com/wave`. They paste the same link again, or `HTTPS://Mizunousa.com/wave/`: no second product
is made, and they land on the existing product's page with the warning — until Task 14, on the Dashboard with that
warning as an information toast.

**Lasso's rule, exactly** (`is_lasso_url_exist` with `check_duplicate_link` on, the default and the live value;
`get_lasso_post_id_by_url`; `Lasso_DB::get_url_details_by_url`): the repaired link is compared with the stored link
of **published** products, ignoring case (MySQL's collation) and one trailing slash, with `&amp;` read as `&`. A
product in the trash or a draft is not a match. Lasso's other lookup branches belong to the subsystems that build
them: a cloaked `/<prefix>/<slug>` link on the site's own domain (redirects and cloaking), the auto-monetize table
(auto-monetization), lasso.to short links (Lasso's cloud). Re-titling an Amazon "Sorry!" product on a match is
Task 12's.

**Moved, not dropped:** `check_duplicate_link` **off** compares the final destination after redirects, without its
query string (Lasso's exceptions: Amazon search pages, Amazon links without an ASIN, extend-product pages,
`DOMAIN_ALLOW_KEEP_FINAL_URL_PARAMS`). It is built with the General settings pass, together with its toggle, which
is the only way to switch it off; the key is added there with Lasso's default (on).

**Request path:** as Task 11A, with `AffiliateUrl::from_string()` repairing first, and
`ProductRepository::find_by_url_key()` before `save()`. A match answers 200 `{ data: { id, name, url, duplicate:
true, redirect_to } }`, `redirect_to` = the Dashboard with `affibloom_notice=duplicate` (Task 14 changes it to the
product page).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `AffiliateUrl` repair (Lasso's broken-prefix table, `https://` before a bare domain, `.html` hosts refused) | `from_string()` |
| `AffiliateUrl::key()` and `_affibloom_url_key` | `PostTypeProductRepository::save()`, `find_by_url_key()` |
| `ProductRepository::find_by_url_key()` (published only) | `ProductService::create_from_url()` |
| `Application\Products\AddedLink` (the product, and whether it already existed) | `ProductService`, `ProductsController` |
| `Notice` code `duplicate`, `Notice::kind()`; the toast's `info` variant | Dashboard |

**Files:** modify `src/Domain/Product/{AffiliateUrl,ProductRepository}.php`,
`src/Application/Products/ProductService.php`, new `src/Application/Products/AddedLink.php`,
`src/Infrastructure/WordPress/PostTypeProductRepository.php`, `src/Interface/Rest/ProductsController.php`,
`src/Interface/Admin/{Notice,DashboardScreen}.php`, `templates/admin/{dashboard.php,partials/toast.php}`,
`assets/admin/admin.css`, `docs/ui/design-system.md` (Toast row), tests `tests/E2E/acceptance/F18.spec.ts`,
`tests/Unit/Domain/Product/AffiliateUrlTest.php`, `tests/Unit/Application/ProductServiceTest.php`,
`tests/Integration/ProductsCreateTest.php`.

- [x] **Step 1: The failing acceptance tests** — F18 AC2 (`mizunousa.com/wave` → a product whose link is
`https://mizunousa.com/wave`), AC3 (the same link twice → one product, the "already exists" toast) and AC4 (the link
with a trailing slash and other capitals → the same product). Expected red: AC2 shows "This is not a valid link.",
AC3 and AC4 make a second product.

- [x] **Step 2: Unit tests** — the repair table (bare domain, `//host/path`, each broken prefix, a capitalised
scheme left alone, `example.html` refused, `not a link` still refused); `key()` ignores case, one trailing slash and
`&amp;`; `ProductService` returns the existing product for a key it already has and saves nothing.

- [x] **Step 3: Route** — a duplicate answers 200 with `duplicate: true` and the duplicate `redirect_to`.

- [x] **Step 4: Service** — `create_from_url()` returns `AddedLink`; it looks up the key before saving.

- [x] **Step 5: Domain** — the repair runs before the check; the stored value is the repaired link. `key()`.

- [x] **Step 6: Infrastructure** — `save()` writes `_affibloom_url_key`; `find_by_url_key()` is a meta query for
published products, one row.

- [x] **Step 7: Notice and toast** — `duplicate` → Lasso's sentence; `Notice::kind()` is `info` for it; the toast
partial takes `kind` and shows the `info` icon on a brand-300 circle for it (design system, Toast).

- [x] **Step 8: Run and look** — lint, analyse, unit; WP-CLI through the REST server (bare domain, the same link
twice, the trailing-slash variant, a trashed product's link makes a new one); the browser pane; the user runs F18.
Test products removed.

- [x] **Step 9: Commit** (`8f41d04`; F18 note `6121249`)

```bash
git commit -m "Repair pasted links and open the existing product for a duplicate

Lasso puts https:// before a bare domain and fixes its known broken
prefixes, and never makes a second product for a link it already has.
Until the product page exists, the Dashboard says which happened."
```

---
### Task 11C: "Add A New Link" — bulk add in the background

**Roadmap:** 2.2 · **Features:** F18 AC6–AC9 (decision D3b)

**Why:** Lasso's dialog has a second mode for up to ten links at once, created in the background so the editor can
leave the page (F18 requirements, sections 1, 2 and 8). It is the last mode of the dialog before naming (11D).

**User action:**
1. In the link field an administrator presses **Shift + Enter**. The field becomes a seven-row textarea holding what
   was typed. The title becomes **Bulk Add Links** and the subtitle "Enter one URL per line (up to 10). Links are
   created in the background—you can leave this page while they process." The hint disappears, and **Remaining
   links: 10** counts the non-blank lines down. Shift + Enter again goes back to the single field with the first
   link.
2. The same mode opens from the caret beside **Add New Link** in the header ("More ways to add links") →
   **Bulk add links** / "Import many URLs at once".
3. When the textarea loses the focus, it shows its links as a numbered list with a pencil to edit them again.
4. **Add Link** asks "Add these links in the background? You can stay on the dashboard while they are created." On
   OK the field is locked and a progress bar starts at 3%. It follows the run every 3 seconds; at 100% the browser
   goes to the Dashboard, with the toast **Bulk add finished.** until Task 13 shows the list.
5. If a run is still going when bulk mode opens, or when Add Link is pressed, the textarea is disabled (two rows)
   with "Your bulk add is still processing. Try again in a few minutes." and a **Reload** button replaces Add
   Link.

**Lasso's rules kept:**
- **The ceiling:** at most ten links, enforced in the box and again on the server; blank lines dropped.
- **Each link:** goes through the same add as a single link (repair, duplicate rule, name).
- **Failures:** invalid lines and duplicates are skipped silently, as in Lasso.
- **The 6-hour age-out:** a run older than that no longer blocks a new one.

**Differences, each removing nothing:**
- **Ten real links:** Lasso keeps the first ten *lines*, blanks included; here the first ten non-blank lines.
- **Escaped preview:** Lasso's preview inserts the typed text as HTML; here it is text.
- **Shift + Enter only in the dialog:** Lasso listens on the whole page and silently switches the hidden dialog;
  here it only acts in the dialog's field.
- **Polling stops on close:** closing the dialog stops the polling, where Lasso's interval keeps running.
- **Checked progress endpoint:** Lasso's progress endpoint checks no nonce and no capability; ours checks both.
- **Empty box:** an empty textarea gets the single field's "URL is incorrect.".

**The background run** (ADR-4, WP-Cron):
- **Stored:** the run is one option, `affibloom_bulk_add` (not autoloaded): pending links, total, done and the
  start time.
- **Started:** starting schedules a single event, `affibloom_bulk_add`, and calls `spawn_cron()`. That runs it at
  once even where `DISABLE_WP_CRON` is set, as on this copy (`spawn_cron()` ignores that constant).
- **Processed:** the handler takes links one at a time, saving after each, for at most 20 seconds (WP Background
  Processing's limit, which Lasso uses), and schedules itself again if links remain.
- **Nudged:** the progress route also calls `spawn_cron()` while links remain. A run that WordPress's 60-second
  spawn lock delayed, or whose chained event no visitor has woken, still finishes while the editor watches.
- **No lock of its own:** a link processed twice is a duplicate the second time, so it creates nothing.

**Request path:**
```
caret → Bulk add links, or Shift + Enter in the field → bulk mode (admin.js)
  → GET  /wp-json/affibloom/v1/products/bulk            running?  (on entering bulk mode)
  → POST /wp-json/affibloom/v1/products/bulk { urls: [...] }
       BulkAddController::start() → BulkAddService::start()  first 10 non-blank; running → 409; none → 422
       BulkRunRepository::save( BulkRun::start() ); BackgroundJob::dispatch()
  ← 202 { data: { total } }
  … WP-Cron: affibloom_bulk_add → BulkAddCron → BulkAddService::process()  ProductService::create_from_url() per link
  → GET  /wp-json/affibloom/v1/products/bulk every 3 s
  ← 200 { data: { running, percent, redirect_to } }
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Product\BulkRun` (`start`, `is_running`, `percent`, `next`, `advanced`) and `BulkRunRepository` | `BulkAddService` |
| `Application\Products\BulkAddService` (`start`, `progress`, `process`), `BulkAddRunning` | the controller, the cron hook |
| `Application\Products\BackgroundJob` (interface, `dispatch()`) | `BulkAddService` |
| `Infrastructure\WordPress\OptionBulkRunRepository`, `WpCronBackgroundJob` | composition root |
| `Interface\Cron\BulkAddCron` (the `affibloom_bulk_add` hook) | WP-Cron |
| `Interface\Rest\BulkAddController` (`POST`/`GET /products/bulk`) | `admin.js` |
| `Messages` code `bulk_running`, `Notice` code `bulk-added` | the controller, the Dashboard |
| the header's caret and menu, the dialog's textarea, counter, preview, progress bar and Reload | `admin.js` |
| icons `chevron-down`, `layers`, `pencil`, `refresh`; the menu and progress-bar components | the header, the dialog |

**Files:** new `src/Domain/Product/{BulkRun,BulkRunRepository}.php`,
`src/Application/Products/{BulkAddService,BulkAddRunning,BackgroundJob}.php`,
`src/Infrastructure/WordPress/{OptionBulkRunRepository,WpCronBackgroundJob}.php`, `src/Interface/Cron/BulkAddCron.php`,
`src/Interface/Rest/BulkAddController.php`, `tests/Unit/Domain/Product/BulkRunTest.php`,
`tests/Unit/Application/BulkAddServiceTest.php`, `tests/Integration/BulkAddTest.php`. Modify `src/Plugin.php`,
`src/Interface/{Messages,Admin/Notice}.php`, `templates/admin/partials/{header,add-modal,icon}.php`,
`assets/admin/{admin.js,admin.css}`, `docs/ui/design-system.md` (Menu and Progress bar rows),
`docs/spec/interface.md`, `tests/E2E/acceptance/F18.spec.ts`.

- [x] **Step 1: The failing acceptance tests**
  - **AC6:** Shift + Enter turns the field into the textarea, keeping the link; the counter reads 9, then 8.
  - **AC7 and AC8:** the menu opens bulk mode; eleven lines are added; the confirmation is accepted; the progress
    bar shows; the run ends on the Dashboard with "Bulk add finished."; ten products exist and the eleventh link has
    none.
  - **AC9:** with the progress route answering `running: true` (Playwright's `page.route`), bulk mode shows the
    disabled textarea, Lasso's sentence and Reload.

  Expected red: there is no caret, and Shift + Enter adds nothing.
- [x] **Step 2: Unit tests**
  - **`BulkRun`:** percent (done/total rounded, 100 with no run; the bar shows at least 5% while running, as Lasso's script), the 6-hour age-out, `advanced()`.
  - **`BulkAddService`:**
    - **Start:** keeps the first ten non-blank lines, refuses a second run (`BulkAddRunning`), refuses none
      (`InvalidField urls empty`), dispatches the job.
    - **Process:** creates each link, skips invalid lines and duplicates silently, stops at the time budget and
      dispatches again.
    - **Progress:** dispatches while links remain.
- [x] **Step 3: Routes** — `BulkAddController`, permission `Capabilities::PRODUCTS`, `urls` an array of strings.
  - **POST:** 202 `{ total }`; 409 `affibloom_bulk_running` with Lasso's sentence; 422 `affibloom_invalid_field`
    with `fields.urls`.
  - **GET:** `{ running, percent, redirect_to }`, `redirect_to` = the Dashboard with `affibloom_notice=bulk-added`.
- [x] **Step 4: Service** as above, with an injectable clock for the budget.
- [x] **Step 5: Domain** — `BulkRun`, `BulkRunRepository`.
- [x] **Step 6: Infrastructure and cron** — the option repository; `WpCronBackgroundJob::dispatch()` schedules the
  event if none is scheduled, then calls `spawn_cron()`; `BulkAddCron` hooks the event to `process()`.
- [x] **Step 7: The dialog**
  - **Header:** a split button. The caret has `aria-expanded` and opens a menu card under it; Escape or a click
    outside closes it. Each item has an icon tile, a title and a description (Lasso's #5719 "menu card"; the
    Marketplace item comes with C1).
  - **Dialog:** the textarea has a visually hidden label "URLs". The counter, the preview `<ol>` and pencil, the
    progress bar (`role="progressbar"`) and Reload are hidden until their mode. Each Lasso string comes from a
    `data-` attribute.
  - **`admin.js`:** the mode switches, the counter, the preview, the confirmation, the start and the polling, and
    the running state.
  - **Design system:** gains a **Menu** row (white card, 12px radius, card shadow, 8px padding, items with a 40px
    icon chip) and a **Progress bar** row (8px track `#E6ECF6`, brand gradient fill, full round).
- [x] **Step 8: Wire it up** — the repository, job, service, controller and cron module in `Plugin::modules()`.
- [x] **Step 9: Run and look**
  - **Checks:** lint, analyse, unit.
  - **WP-CLI:** start a run through the REST server; refuse a second; run the event with `wp cron event run
    affibloom_bulk_add`; read the progress.
  - **Browser pane:** the menu, Shift + Enter both ways, the counter, the preview and pencil, the confirmation (No
    changes nothing), a real run of three links ending on the Dashboard, the running state at 1440, 782 and 375px.
  - **The user:** runs F18.
  - **Afterwards:** test products removed, `affibloom_bulk_add` deleted.
- [x] **Step 10: Commit** (`b89ae01`)

```bash
git commit -m "Add up to ten links at once in the background

Lasso's bulk mode of Add A New Link: Shift+Enter or the header's
menu, a ten-link ceiling, a background run through WP-Cron with a
progress bar, and a refusal while a run is still going."
```

---
### Task 11D: "Add A New Link" — naming a new product as Lasso does

**Roadmap:** 2.2 · **Features:** F18 AC1 (decision D3), F17 (`keep_genius_formatting`)

**Why:** Lasso names a new product from where its link leads. 11A used only the last fallback of that rule, the
pasted link's site. For the site's Impact links Lasso uses the destination's site: "Mizunousa", not "Pxf". For
other links it reads the page's title (`Lasso_Helper::get_redirect_final_target` with the page title, `get_page_title`,
`get_final_url_from_url_param`).

**Lasso's rule, in order (each step stops the search):**
1. **Embedded destination in the pasted link** (no request): the first query parameter that is itself a valid
   http(s) link, except `referrer`. Network-specific rules come first:
   - ShareASale: `urllink`
   - `pntra.com` and `wordseed.com`: `url`
   - `titan.fitness`: `redirect`, put after `https://www.titan.fitness`
   - `prf.hn`: a `destination:` path segment

   The name is that destination's site (`get_title_by_url`).
2. **Amazon:** no request (the Associates licence forbids reading Amazon's pages; Lasso asks its cloud). The name
   is "Amazon", Lasso's `DEFAULT_AMAZON_NAME`; Task 12 replaces it.
3. **Otherwise one GET** with the editor's browser user agent (a fixed browser user agent in a bulk run), following
   redirects, WordPress's 5-second timeout:
   - **No answer, or 403:** Lasso asks its cloud. Affibloom uses the pasted link's site.
   - **Any other answer:** the final URL is where the redirects ended.
   - **The title:** from `<title>`, cleaned as Lasso does: whitespace collapsed, "–" as "-", trimmed, at most 200
     characters with "..." (the 200 includes the dots, so Task 15's limit holds).
   - **A placeholder title** ("Please Wait...", "Cloudflare", "Access Denied", "Just a moment...", "Robot Check")
     or an empty one becomes the final URL's site.
   - **An embedded destination in the final URL** names the product after that destination's site.
4. **No image:** for a non-Amazon link Lasso fetches none (only its cloud does, and only for Amazon). The image
   stays empty and the box shows the default thumbnail. F18 decision D3 is corrected accordingly.

**Two differences, each removing nothing:**
- **Entities and accents:** Lasso strips every non-ASCII character and stores the title HTML-escaped. Here
  entities are decoded and accented letters kept; the name is escaped when printed.
- **Affiliate-network pages:** for these Lasso prefers its cloud's answer to its own request; here its own request
  is the answer.

**geni.us** (F18 section 4, step 4): with `keep_genius_formatting` **off**, a `geni.us` link is stored as its final
URL, and the duplicate rule is then checked against that URL. The setting is on live and by default, so the link
stays as pasted. Lasso's `old_genius_url` meta has no reader yet and is not stored.

**The duplicate check stays first** (11B), so a known link costs no request.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Product\EmbeddedDestination::in()` (Lasso's parameter rules) | `LinkResolver` |
| `Domain\Product\PageTitle::from_html()` (Lasso's cleaning, the placeholder list) | `LinkResolver` |
| `Application\Products\LinkResolver` → `ResolvedLink { url, title }` | `ProductService::create_from_url()` |
| `Application\Products\PageFetcher` (interface) → `FetchedPage { status, final_url, body }` | `LinkResolver` |
| `Infrastructure\Http\WpPageFetcher` (`wp_remote_get`) | composition root |
| `ProductService` reads `keep_genius_formatting` (`SettingsRepository`) | itself |

**Not stored here, each with its first reader:** the final URL (`lasso_final_url`; the duplicate rule's off mode,
Task 30's link check) and the request's status (Task 30).

- [x] **Step 1: The failing acceptance test** — F18 AC1's name becomes "Mizunousa" (the Impact link's
  destination). Expected red: "Pxf".
- [x] **Step 2: Unit tests**
  - **`EmbeddedDestination`:** each network rule, the generic rule, `referrer` skipped, none.
  - **`PageTitle`:** whitespace, the dash, entities, the 200-character cut, each placeholder.
  - **`LinkResolver`** with a fake fetcher: embedded first and no fetch; Amazon no fetch; 200 with a title; 403 →
    the pasted link's site; an error → the same; a placeholder title → the final URL's site; a destination
    embedded in the final URL.
  - **`ProductService`:** geni.us replaced only with the setting off, then checked for a duplicate.
- [x] **Step 3: Service** — resolve after the duplicate check; use the title as the name; apply the geni.us rule.
- [x] **Step 4: Domain** — `EmbeddedDestination`, `PageTitle`.
- [x] **Step 5: Application** — `LinkResolver`, `ResolvedLink`, `PageFetcher`, `FetchedPage`.
- [x] **Step 6: Infrastructure** — `WpPageFetcher`: `wp_remote_get` with the user agent; returns null on a
  `WP_Error`; the final URL from the Requests response.
- [x] **Step 7: Wire it up.**
- [x] **Step 8: Run and look**
  - **Checks:** lint, analyse, unit.
  - **WP-CLI:** an Impact link → "Mizunousa"; a plain link on this copy (external HTTP is blocked) → its site name;
    a page title served from this site itself (`http://volleyballvault.local/…`, which the block allows) → its
    title.
  - **The user:** runs F18.
- [x] **Step 9: Commit** (`f1067d7`)

```bash
git commit -m "Name a new product from where its link leads

Lasso's rule: an embedded destination's site, else the page title
after redirects, with its placeholder titles and Amazon left out,
falling back to the link's site. geni.us links follow the setting."
```

---
### Task 11E: "Add A New Link" — the site's link defaults on a new product

**Roadmap:** 2.2, 2.4 · **Features:** F18 AC10, F9

**Why:** Lasso copies the General settings onto a new product when it is created (`save_lasso_url` reads
`get_lasso_url( 0 )`'s new-record values, lines 159-163, and writes them as meta, lines 1421-1435). A later change
of a site default does not reach products that already exist. The box and the product page read these values
(Tasks 14 and 18).

**User action:** with Settings → General's NoFollow off and Sponsored on, an administrator adds a link. The new
product is stored with NoFollow off, Sponsored on, and the site's New Window and Show Disclosure. Changing the
settings afterwards leaves that product as it was.

**Copied here,** the keys that exist (Task 9): `new_tab` → `_affibloom_new_tab`, `nofollow` → `_affibloom_nofollow`,
`sponsored` → `_affibloom_sponsored`, `show_disclosure` → `_affibloom_show_disclosure`, as `'1'`/`'0'`.

**Copied when their settings exist** (each added to this copy by that task):
- the secondary button's New Window and NoFollow (`open_new_tab2`, `enable_nofollow2`; the General settings pass)
- `is_opportunity` from Detect Opportunities (Opportunities)
- `link_cloaking` (redirects and cloaking)
- `boosting_enabled` from Commission Upgrades (its reader is Lasso's link boosting, a cloud service under the
  audit's Finding 7)

**Request path:** as 11A; `ProductService` reads the settings and `Product::draft()` takes them.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Product`'s `new_tab`, `nofollow`, `sponsored`, `show_disclosure`; `draft( name, url, Settings )` | `ProductService` |
| the four meta keys in `PostTypeProductRepository::save()` and `hydrate()` | the repository |

- [x] **Step 1: The failing acceptance test** — F18 AC10: `setSettings( { nofollow: false, sponsored: true } )`,
  add a link, read its meta: `_affibloom_nofollow` `0`, `_affibloom_sponsored` `1`, `_affibloom_new_tab` `1`,
  `_affibloom_show_disclosure` `1`; restore the settings. Expected red: the meta is missing.
- [x] **Step 2: Unit tests** — `Product::draft()` copies each setting; `ProductService` passes the stored settings.
- [x] **Step 3: Domain and service** — the four properties, `draft()` with `Settings`.
- [x] **Step 4: Infrastructure** — the four meta keys written and read back.
- [x] **Step 5: Run and look**
  - **Checks:** lint, analyse, unit.
  - **WP-CLI:** add with the defaults, then with NoFollow off; the meta follows the setting; changing the setting
    afterwards leaves the product unchanged.
  - **The user:** runs F18.
- [x] **Step 6: Commit** (`f830fea`)

```bash
git commit -m "Copy the site's link defaults onto a new product

As Lasso does when it creates a link: New Window, NoFollow, Sponsored
and Show Disclosure are taken from the General settings at that moment."
```

---
*(Superseded Task 11: its text is in `archive/phase-2-superseded.md`; replaced by Tasks 11A-11E above.)*

---

### Task 12A: An Amazon product link, stored as Lasso stores it

**Roadmap:** 2.3 · **Feature:** F2 (requirements sections 2-4; AC1, AC3, AC8)

**Why:** 242 of the 263 products are Amazon's, and the link stored is the one readers click. Lasso keeps the pasted
path, puts the site's tag first and keeps a short list of Amazon's own parameters (`get_amazon_product_url`); all
225 live product links have that form. The plan's `/dp/<ASIN>?tag=` form is not Lasso's (F2 requirements, section 9).

**User action:** the editor pastes `https://www.amazon.com/Mizuno-Wave/dp/B0C1234567/ref=sr_1_3?keywords=shoe&tag=someone-20&ref_=x`.
The product is stored with `https://www.amazon.com/Mizuno-Wave/dp/B0C1234567/ref=sr_1_3?tag=vbvault-20&ref_=x`,
typed Amazon, with ASIN `B0C1234567` and country `us`. A whitelisted tag is kept; a protected attribution link is
stored as pasted.

**Lasso's rules** (F2 requirements, sections 2-4): the ASIN pattern on the store's host, last marker wins, case kept;
the country table; `smile.amazon.` → `amazon.`; protected links untouched; the tag rule (whitelist while multiple IDs
is on, `maas` always allowed, the site's ID otherwise, the pasted tag when no ID is set); shorten on: path + `tag` +
`maas`, `ref_`, `s`, `aa_campaignid`, `aa_creativeid`, `aa_adgroupid`, `campaignId`, `linkCode`, `linkId` as parsed,
no tag with `maas`; shorten off: whole query, `tag` set, `rawurlencode`. Only links **with an ASIN** change here;
Amazon pages without one stay as they are until 12C, short links until 12D.

**Request path:**
```
POST /affibloom/v1/products { url }                       (unchanged)
  → ProductService::create_from_url()
       AffiliateUrl::from_string → stored-link lookup (11B) → LinkResolver::resolve (no request for Amazon)
       AmazonUrl::from( destination ) with an ASIN
         → monetized( TrackingIds::from( settings ), settings->bool( 'shorten_amazon_links' ) )
       Product::draft( name, url, settings )->with( type Amazon, asin, amazon_country )
  → PostTypeProductRepository::save()  _affibloom_type = amazon, _affibloom_asin, _affibloom_amazon_country
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Amazon\AmazonUrl` (`from`, `asin`, `country`, `is_protected`, `monetized`) | `ProductService` |
| `Domain\Amazon\TrackingIds` (`from_settings` values, `tag_for`) | `AmazonUrl::monetized()`, built by `ProductService` |
| `ProductType::Amazon` | `ProductService`, the repository |
| `Product::$asin`, `Product::$amazon_country`; meta `_affibloom_asin`, `_affibloom_amazon_country` | the service; the repository (12B reads them) |

**Files:**
- Create: `src/Domain/Amazon/AmazonUrl.php`, `src/Domain/Amazon/TrackingIds.php`,
  `tests/Unit/Domain/Amazon/AmazonUrlTest.php`, `tests/Unit/Domain/Amazon/TrackingIdsTest.php`
- Modify: `src/Domain/Product/{ProductType,Product}.php`, `src/Application/Products/ProductService.php`,
  `src/Infrastructure/WordPress/PostTypeProductRepository.php`, `tests/Unit/Application/ProductServiceTest.php`,
  `tests/Unit/Fakes/InMemoryProductRepository.php`, `tests/E2E/acceptance/F2.spec.ts`,
  `tests/Integration/ProductsCreateTest.php`

- [x] **Step 1: The failing acceptance tests** — rewrite `F2.spec.ts` for the requirements' AC list. The add goes
  through the dialog; the result is read from the newest product's meta with `wp()` (the product page arrives with
  Task 14). This task's tests: **AC1** (path kept, tag first, `ref_` kept, type `amazon`, ASIN, country `us`),
  **AC3** (another tag replaced; with `tracking_id_whitelist` `[vbvault-20, other-21]`, `tag=other-21` kept; the
  settings restored after), **AC8** (`?maas=x&tag=maas` stored as pasted). AC2, AC4-AC7 are written with their
  tasks. Expected red: the link is stored as pasted and `_affibloom_type` is `other`.
- [x] **Step 2: Unit tests**
  - `AmazonUrlTest`: every marker form (`/dp/`, `/gp/product/`, `/gp/aw/d/`, `/d/x/y/`, `/clp/`, `/ASIN/`,
    `/gp/video/detail/`), slug before `/dp/`, the last marker, lower case kept, no ASIN for `/s?k=x` and
    `/stores/x`, countries (`.com` us, `.co.uk` gb, `.de` de, `.com.au` au), `smile.`, the three protected forms;
    `monetized()` with shorten on (the live Mikasa link byte for byte; `keywords` dropped; kept parameters in
    Lasso's order whatever their pasted order; `maas` drops the tag; no tag set gives no `tag=`) and off (whole query,
    `tag` replaced in place or added last, values `rawurlencode`d).
  - `TrackingIdsTest`: whitelist with multiple IDs on and off, `maas`, no Tracking ID.
  - `ProductServiceTest`: an Amazon product link → type, ASIN, country, monetized link; a non-Amazon link is
    unchanged; the geni.us test (11D) now expects the destination monetized.
- [x] **Step 3: Domain** — `TrackingIds`, `AmazonUrl` (constants: Lasso's marker pattern, the country table
  from `get_aff_link_and_flag()`, the kept-parameter list, each with its source), `ProductType::Amazon`, the two
  `Product` properties (constructor defaults `''`, so existing callers need no change).
- [x] **Step 4: Service** — the Amazon branch after `resolve()`, reading `tracking_id`, `multiple_tracking_ids`,
  `tracking_id_whitelist`, `shorten_amazon_links` once per add.
- [x] **Step 5: Infrastructure** — the two meta keys written and read back; the in-memory fake keeps them.
- [x] **Step 6: Run and look**
  - **Checks:** lint, analyse, unit.
  - **Backup first:** `wp db export ../affibloom-backup-2026-11-13-task12a.sql`.
  - **WP-CLI through the REST server:** the live Mikasa link with `tag=someone-20` → the live stored form; a
    whitelisted tag kept; Shorten off → whole query; a `maas` link untouched; a non-Amazon link unchanged. Test
    products removed, settings restored.
  - **The user:** runs F2 (AC1, AC3, AC8).
- [x] **Step 7: Commit** (`5dcfa7a`; F2 docs `4fc8a28`)

```bash
git commit -m "Store an Amazon product link as Lasso stores it

The pasted path stays, the site's tag goes first and only Amazon's own
parameters are kept, so new links match the 225 Lasso made. The product
is typed Amazon with its ASIN and country."
```

---

### Task 12B: The same Amazon product twice

**Roadmap:** 2.3 · **Feature:** F2 (requirements section 7, steps 2 and 5; AC4)

**Why:** the stored-link lookup (11B) misses the same product pasted in another form, because the stored link is
monetized and the pasted one is not. Lasso then finds it by ASIN and country (`get_lasso_id_by_product_id_and_type`).

**User action:** the editor adds `https://www.amazon.com/dp/B0C1234567`, then `https://www.amazon.com/gp/product/B0C1234567?th=1`.
The second add opens the first product with "This product already exists. Please update the Primary URL or add a
new link." (11B's info toast until Task 14). `https://www.amazon.co.uk/dp/B0C1234567` is a new product (`gb`).

**Request path:** as 12A; after `resolve()`, for an Amazon link with an ASIN,
`ProductRepository::find_amazon( asin, country )`, published products only, before the new product is made.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `ProductRepository::find_amazon( string $asin, string $country ): ?Product` | `ProductService` |
| its `meta_query` in `PostTypeProductRepository`; the fake's version | the repository; unit tests |

- [x] **Step 1: The failing acceptance test** — **AC4**: add `/dp/<A>`, then `/gp/product/<A>?th=1`: the info toast,
  and `wp post list --meta_key=_affibloom_asin --meta_value=<A>` counts 1; then `amazon.co.uk/dp/<A>`: a second
  product, country `gb`. Also correct `createProduct()` in `utils.ts`: its `amazon:<asin>` URL key was the old
  plan's; an Amazon product's key is its link's, with `_affibloom_amazon_country` (`us` by default). Expected red:
  the second add makes a second product.
- [x] **Step 2: Unit tests** — `ProductServiceTest`: another form of the same ASIN → the existing product,
  `duplicate` true, no request; another country → new; a trashed or draft product with the ASIN → new; the ASIN in
  another case → the existing one (the fake compares without case, as MySQL's collation does).
- [x] **Step 3: Repository** — `find_amazon()`: `post_status` publish, `meta_query` AND on `_affibloom_asin` and
  `_affibloom_amazon_country`, lowest ID first (as `find_by_url_key`).
- [x] **Step 4: Service** — the lookup in Lasso's order (after the stored-link lookup, before the new product).
- [x] **Step 5: Run and look**
  - **Checks:** lint, analyse, unit.
  - **WP-CLI through the REST server:** `/dp/`, `/gp/product/`, `/gp/aw/d/` and a slugged link of one ASIN → one
    product; `.co.uk` → a second; trashing the first, then adding → a new one. Test products removed.
  - **Browser pane** (if signed in): the second add shows the info toast.
  - **The user:** runs F2 AC4.
- [x] **Step 6: Commit** (`b1db8c5`)

```bash
git commit -m "Find an Amazon product again by its ASIN and country

A product pasted in another form is the one that exists, as in Lasso;
the same ASIN on another Amazon store is a different product."
```

---

### Task 12C: Amazon search pages and other Amazon pages

**Roadmap:** 2.3 · **Feature:** F2 (requirements sections 2.5, 3.5, 7 steps 3, 4 and 6; AC6)

**Why:** four live products are Amazon search pages, kept with their whole query and the site's tag. Lasso finds a
search page again by its keywords, finds any other Amazon page again after applying the tag rule, and renames a
matched product Lasso's cloud had named "Sorry!…".

**User action:** the editor pastes `https://www.amazon.com/s?k=nike+kd14&crid=X&tag=someone-20`. It is stored with
`k`, `crid` and `tag=vbvault-20`; pasting `https://www.amazon.com/s?k=Nike%20KD14` again opens it.

**Request path:** as 12A; for an Amazon destination without an ASIN: `AmazonUrl::monetized()` keeps the whole
query; the duplicate steps 3 (search key) and 4 (retagged link) run after 12B's ASIN step; on a match,
`ProductRepository::rename()` when the name holds "Sorry!" or " wrong!".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `AmazonUrl::search_keywords()`, `search_key()`, `search_page_title()`; the page branch of `monetized()` | `ProductService` |
| `Product::$search_key`; meta `_affibloom_search_key` | the service; the repository |
| `ProductRepository::find_by_search_key()`; the single-meta lookup shared with `find_by_url_key()` (its second use) | `ProductService` |
| `ProductRepository::rename( int $id, string $name ): Product` | `ProductService` (the re-title) |

- [x] **Step 1: The failing acceptance test** — **AC6**: a search page with `crid` and another tag → stored with its
  query and `tag=vbvault-20`, type `amazon`, no ASIN; the same keywords written differently (`%20`, other case,
  other parameters) → the info toast, one product. A store page (`/stores/x/page/y?ref_=z`) gets the tag with its
  query kept. Expected red: the link is stored as pasted.
- [x] **Step 2: Unit tests** — `AmazonUrlTest`: keywords from `k`, then `field-keywords`, then `keywords`; `/s`,
  `/s/`, a slugged search (`/curry-flow-10/s?k=…`); the key normalised (decoded, trimmed, spaces collapsed; case
  kept for display, compared without case); pages with no query. `ProductServiceTest`: search key match; retagged
  match for a store page stored by an earlier add; the re-title of a "Sorry!" Amazon product, not of a non-Amazon
  " wrong!" one (requirements 7.6).
- [x] **Step 3: Domain** — the page branch of `monetized()`, the keyword and key methods, `search_page_title()`
  ("Amazon: <keywords>" or "Amazon"), `Product::$search_key`.
- [x] **Step 4: Service and repository** — the two lookups in Lasso's order, the re-title through `rename()`
  (`wp_update_post` title and `publish`, as Lasso).
- [x] **Step 5: Run and look**
  - **Checks:** lint, analyse, unit.
  - **WP-CLI through the REST server:** the four live search links (from `products.json`) re-added → stored
    byte for byte as live, and each a duplicate on the second add; a store page tagged; a product named
    "Sorry! Something went wrong!" with an ASIN, re-added → renamed "Amazon". Test products removed.
  - **The user:** runs F2 AC6.
- [x] **Step 6: Commit** (`a4b3c52`)

```bash
git commit -m "Keep Amazon search pages whole and find them again by keywords

Amazon pages that are not one product keep their query with the site's
tag. A search is found again by its keywords and store, any other page
by its tagged link, as Lasso does."
```

---

### Task 12D: Amazon short links

**Roadmap:** 2.3 · **Feature:** F2 (requirements section 5; AC7)

**Why:** Lasso follows `amzn.to`, `amzn.com` and `amzlink.to` links and stores where they end, so the product gets
its ASIN and is found again. Affibloom follows the short link's own redirect and stops before the Amazon store page,
which it never requests.

**User action:** the editor pastes `https://amzn.to/46fBGAJ`. With the network open, the product is stored with the
destination's product link, monetized (12A), with its ASIN; adding the same short link again opens it (12B). When
the short link cannot be followed, it is stored as pasted, typed Amazon, named "Amazon".

**Request path:**
```
ProductService::create_from_url( 'https://amzn.to/x' )
  → stored-link lookup (11B)
  → LinkResolver::resolve()        short link: PageFetcher::fetch() (11D), which requests amzn.to and stops at the
                                   first redirect into an Amazon store, returning it as final_url, no body
  → the destination as a pasted link: 12A-12C rules and lookups
  → no answer: the short link itself, type Amazon
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `AmazonUrl::is_short_link()` (Lasso's `is_amazon_shortened_url` text test) | `LinkResolver`, `WpPageFetcher` |
| `LinkResolver`: short links fetched instead of returned as final | `ProductService` |
| `WpPageFetcher`: stop at an Amazon **store** host, not at a short-link host | `LinkResolver` |

- [x] **Step 1: The failing acceptance test** — **AC7**: `https://amzn.to/<random>` with external HTTP blocked →
  stored as pasted, `_affibloom_type` `amazon`, no ASIN, named "Amazon". Expected red: type `other` (12A types only
  links with an ASIN).
- [x] **Step 2: Unit tests** — `LinkResolverTest`: a short link is fetched; its store destination is the result;
  no answer gives the short link. `ProductServiceTest` with `FakePageFetcher`: `amzn.to/x` → 301 to a slugged
  product link with another tag → stored monetized with its ASIN; the same short link to an existing ASIN → the
  existing product; a short link that leads to another short link is followed on.
- [x] **Step 3: Code** — `is_short_link()`, the resolver branch, the fetcher's stop condition; an unresolved Amazon
  link is typed Amazon.
- [x] **Step 4: Run and look**
  - **Checks:** lint, analyse, unit.
  - **WP-CLI through the REST server**, every outgoing request logged: with `WP_HTTP_BLOCK_EXTERNAL` as it is, the
    short link is kept, no request reaches Amazon; with a `pre_http_request` filter in the `wp eval` script answering
    `amzn.to` with a 301 to a live product link, the product is stored with that link monetized, and the log shows
    one request, to `amzn.to`, none to `www.amazon.com`. Test products removed.
  - **The user:** runs F2 AC7.
- [x] **Step 5: Commit** (`5d1d634`)

```bash
git commit -m "Follow Amazon short links to the product they open

amzn.to and its siblings are followed to the Amazon store link, which is
stored as if pasted; the store page itself is never requested. A short
link that cannot be followed is kept as pasted, as in Lasso."
```

---

### Task 12E: A bare ASIN, and the starting name of an Amazon product

**Roadmap:** 2.3 · **Feature:** F2 (requirements sections 6 and 8; AC2, AC5) · **Decision 11** (the user, Task 6)

**Why:** two editor helpers the approved records add to Lasso's behaviour. A bare ASIN saves building a link by
hand (Lasso refuses it as "This is not a valid link."). Without Lasso's cloud, a new Amazon product starts with a
name from its link instead of "Amazon": the slug words, or Lasso's own "Amazon: <keywords>" for a search page.

**User action:** the editor pastes `B0C1234567`: the product is `https://www.amazon.com/dp/B0C1234567?tag=vbvault-20`,
named "Amazon". `https://www.amazon.com/Mizuno-Wave-Momentum-3/dp/B0C7654321` is named "Mizuno Wave Momentum 3";
`https://www.amazon.com/s?k=nike+kd14` "Amazon: nike kd14".

**Request path:**
```
ProductService::create_from_url( input )
  AmazonUrl::from_bare_asin( input ) ?? AffiliateUrl::from_string( input )     before anything else
  … 12A-12D …
  name = AmazonUrl::starting_name()            for an Amazon destination; LinkResolver's "Amazon" goes
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `AmazonUrl::from_bare_asin()` (`B0` + 8, or an ISBN-10; the store `www.amazon.com` until C1's locale control) | `ProductService` (so bulk add gets it too) |
| `AmazonUrl::starting_name()` (slug → words, else `search_page_title()`) | `ProductService` |

- [x] **Step 1: The failing acceptance tests** — **AC2** (bare ASIN → the `/dp/` link with the tag) and **AC5** (the
  three names). Expected red: AC2 "This is not a valid link."; AC5 "Amazon" for the slug.
- [x] **Step 2: Unit tests** — bare ASIN: `B0` forms, ISBN-10 with `X`, lower case, surrounding spaces; not
  `BESTSHOE10`, not 11 characters, not a link. Starting name: hyphens and `+` as spaces, `%2C` decoded, case kept,
  no slug (`/dp/` first, `/gp/product/`) → "Amazon", a search page → "Amazon: …", a short link kept unresolved →
  "Amazon". `ProductServiceTest`: the bare ASIN through the service and through bulk add's call.
- [x] **Step 3: Code** — the two methods; `ProductService` uses them; `LinkResolver` loses its Amazon name
  (its tests move to the service).
- [x] **Step 4: Run and look**
  - **Checks:** lint, analyse, unit.
  - **WP-CLI through the REST server:** a bare ASIN, an ISBN-10, the three names. **Browser pane** (if signed in):
    a bare ASIN through the dialog; bulk add with a bare ASIN on one line. Test products removed.
  - **The user:** runs F2 (all eight) and F18.
- [x] **Step 5: Commit** (`e839339`)

```bash
git commit -m "Accept a bare ASIN and name new Amazon products from their link

A bare ASIN becomes the store's product link. A new Amazon product
starts with its link's slug as its name, or Lasso's \"Amazon: keywords\"
for a search, instead of \"Amazon\" (decision 11)."
```

---

*(Superseded Task 12: its text is in `archive/phase-2-superseded.md`; replaced by Tasks 12A-12E above.)*

---

### Task 13A: The Dashboard lists its products, in pages

**Roadmap:** 2.2 · **Feature:** F19 (requirements sections 2 and 5; AC1–AC4, AC10)

**Why:** the Dashboard is the screen editors live on, and since Task 8 it has shown an empty card. The 263
products this plugin will hold do not fit on one screen, so the list and its pages are one piece of work: Lasso
shows 10 at a time, most recently changed first, with a per-page select, a range line and page links in the
card's footer (`dashboard-2.jpg`, Lasso 370). The plan's text for this task has 20 per page, orders by creation
date and builds a name-and-host row; all three are wrong against Lasso (F19 requirements, section 7).

**User action:** an editor opens Affibloom → Dashboard. The products are in a table inside the card: the name,
and under it the destination link. The footer reads `10 ▾ per page · 1-10 of 263` with page links on the right.
Choosing 25 shows 25, and is remembered next time. Page 2 shows the next ones. An empty library shows the
"No products yet." card with the Add action.

**Lasso's rules** (F19 requirements, section 2): published only; `post_modified` DESC; page size 10 from
10/25/50, anything else falling back to 10; the range line with thousands separators and `0 of 0` when empty; no
page links when everything fits on one page; `1 2 3 … 26 27` either side of the current page.

**Decisions this task applies** (F19 requirements, section 6): **D1** the list is rendered by PHP, so the page,
the page size and (13B) the search are query arguments, not a hash and browser storage; **D2** most recently
changed first; **D3** 10 per page from 10/25/50, remembered in user meta rather than Lasso's sessionStorage.

**Request path:**
```
GET admin.php?page=affibloom[&paged=2][&per_page=25]
  → DashboardScreen::render()
       PerPage::from_request( $_GET )            user meta affibloom_per_page; 10 by default
       ProductService::list( $page, $per_page )
         ProductRepository::page( $page, $per_page ): ProductPage
           PostTypeProductRepository  WP_Query( affibloom_product, publish, paged,
                                       posts_per_page, orderby=modified, order=DESC )
  ← templates/admin/dashboard.php
       partials/product-row.php per product
       partials/pager.php        per-page select, range line, paginate_links()
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Product\ProductPage` (`items`, `total`, `page`, `per_page`, `pages()`, `first()`, `last()`, `is_empty()`) | `ProductRepository::page()`, `dashboard.php`, `pager.php` |
| `ProductRepository::page()` and its implementation | `ProductService::list()` |
| `ProductService::list()`, `PER_PAGE_DEFAULT`, `PER_PAGE_OPTIONS` | `DashboardScreen`, `PerPage`, `pager.php` |
| `Interface\Admin\PerPage::from_request()` | `DashboardScreen` |
| `templates/admin/partials/product-row.php`, `partials/pager.php` | `dashboard.php` |
| The products tile (U9) | `dashboard.php` |

**Files:**
- Create: `src/Domain/Product/ProductPage.php`, `src/Interface/Admin/PerPage.php`,
  `templates/admin/partials/product-row.php`, `templates/admin/partials/pager.php`,
  `tests/Unit/Domain/Product/ProductPageTest.php`, `tests/E2E/acceptance/F19.spec.ts`,
  `tests/Integration/ProductListTest.php`
- Modify: `src/Domain/Product/ProductRepository.php`,
  `src/Infrastructure/WordPress/PostTypeProductRepository.php`,
  `src/Application/Products/ProductService.php`, `src/Interface/Admin/DashboardScreen.php`, `src/Plugin.php`,
  `templates/admin/dashboard.php`, `assets/admin/admin.css`, `tests/Unit/Fakes/InMemoryProductRepository.php`

- [x] **Step 1: The failing acceptance tests** — a new `tests/E2E/acceptance/F19.spec.ts` with the requirements'
  AC list; this task's are **AC1** (a product changed just now is first), **AC2** (10 rows, `1-10 of <total>`,
  `&paged=2` shows the next rows), **AC3** (25 per page, remembered after leaving and coming back), **AC4** (one
  page: no page links, the range line still right) and **AC10** (an empty library shows the Add empty state).
  The products are made with `createProduct()` from `utils.ts`; AC10 needs a library with none, so it runs with
  every product trashed and restored afterwards, or — simpler and what this task does — asserts the empty state
  from a search that no product matches once 13B exists, and until then is marked `test.fixme` with that reason.
  Expected red: the Dashboard shows one empty card and no table at all.

- [x] **Step 2: Unit tests** — `ProductPageTest` (Brain Monkey, no WordPress): `pages()` rounds up and is never
  0; `first()`/`last()` give 1–10 of 263 on page 1, 11–20 on page 2, 261–263 on page 27, and `0`/`0` for an empty
  page; a page beyond the last clamps to the last page's range.

- [x] **Step 3: Domain** — `src/Domain/Product/ProductPage.php`:

```php
<?php
/**
 * One page of the Dashboard list, and the total behind it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * One page of the Dashboard list, and the total behind it.
 */
final class ProductPage {

	/**
	 * Builds the page.
	 *
	 * @param list<Product> $items    Products on this page, in the list's order.
	 * @param int           $total    Products behind the list, across all pages.
	 * @param int           $page     This page's number, from 1.
	 * @param int           $per_page Products per page.
	 */
	public function __construct(
		public readonly array $items,
		public readonly int $total,
		public readonly int $page,
		public readonly int $per_page,
	) {}

	/**
	 * Number of pages; 1 when there is nothing to show, as Lasso's pager draws one page.
	 */
	public function pages(): int {
		return max( 1, (int) ceil( $this->total / max( 1, $this->per_page ) ) );
	}

	/**
	 * Whether this page has nothing on it.
	 */
	public function is_empty(): bool {
		return array() === $this->items;
	}

	/**
	 * The first product's number in the whole list ("1" in "1-10 of 263"); 0 when there is none.
	 * A page beyond the last clamps to the last page, as Lasso's range function does.
	 */
	public function first(): int {
		if ( 0 === $this->total ) {
			return 0;
		}

		return ( min( $this->page, $this->pages() ) - 1 ) * $this->per_page + 1;
	}

	/**
	 * The last product's number in the whole list ("10" in "1-10 of 263"); 0 when there is none.
	 */
	public function last(): int {
		if ( 0 === $this->total ) {
			return 0;
		}

		return min( $this->first() + max( 1, count( $this->items ) ) - 1, $this->total );
	}
}
```

- [x] **Step 4: Service and repository**

`ProductService`:

```php
	/** Products per Dashboard page on a fresh install (Lasso's default). */
	public const PER_PAGE_DEFAULT = 10;

	/** The page sizes an editor may choose (Lasso_Helper::get_dashboard_page_size_options). */
	public const PER_PAGE_OPTIONS = array( 10, 25, 50 );

	/**
	 * One page of the Dashboard list, most recently changed first.
	 *
	 * @param int $page     Page number, from 1.
	 * @param int $per_page Products per page.
	 */
	public function list( int $page, int $per_page ): ProductPage {
		return $this->products->page( $page, $per_page );
	}
```

`ProductRepository`, add:

```php
	/**
	 * One page of the published products, most recently changed first (Lasso orders the Dashboard by
	 * post_modified DESC: Lasso_DB::get_dashboard_query_without_filter).
	 *
	 * @param int $page     Page number, from 1.
	 * @param int $per_page Products per page.
	 */
	public function page( int $page, int $per_page ): ProductPage;
```

`PostTypeProductRepository`, add (WP_Query primes the meta cache for the page, so hydrating costs no extra query;
the term cache is not primed because no column reads a taxonomy):

```php
	public function page( int $page, int $per_page ): ProductPage {
		$query = new WP_Query(
			array(
				'post_type'              => ProductPostType::NAME,
				'post_status'            => 'publish',
				'posts_per_page'         => $per_page,
				'paged'                  => max( 1, $page ),
				'orderby'                => 'modified',
				'order'                  => 'DESC',
				'ignore_sticky_posts'    => true,
				'update_post_term_cache' => false,
			)
		);

		return new ProductPage(
			array_values( array_map( fn( WP_Post $post ): Product => $this->hydrate( $post ), $query->posts ) ),
			(int) $query->found_posts,
			max( 1, $page ),
			$per_page
		);
	}
```

`tests/Unit/Fakes/InMemoryProductRepository.php` gets the same method over its array, so the unit suite still
builds the interface.

- [x] **Step 5: Screen** — `src/Interface/Admin/PerPage.php`:

```php
	/**
	 * The page size for this editor: the one just chosen (remembered), else the one remembered, else 10.
	 * Lasso keeps the choice in sessionStorage, which dies with the tab; user meta is how WordPress remembers
	 * a list's page size.
	 *
	 * @param array<string, mixed> $request Query arguments ($_GET).
	 */
	public function from_request( array $request ): int {
		$asked = isset( $request['per_page'] ) ? absint( $request['per_page'] ) : 0;
		if ( in_array( $asked, ProductService::PER_PAGE_OPTIONS, true ) ) {
			update_user_meta( get_current_user_id(), self::META_KEY, $asked );

			return $asked;
		}

		$saved = absint( get_user_meta( get_current_user_id(), self::META_KEY, true ) );

		return in_array( $saved, ProductService::PER_PAGE_OPTIONS, true ) ? $saved : ProductService::PER_PAGE_DEFAULT;
	}
```

`DashboardScreen`: the constructor takes `private ProductService $products` and `private PerPage $per_page` after
`$template`; `render()` reads the request and hands the view the page. The nonce rule is disabled for those lines
with the reason Task 8 uses: a read-only list filter.

```php
	public function render(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- a read-only list filter.
		$per_page = $this->per_page->from_request( wp_unslash( $_GET ) );
		$page     = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$this->template->render(
			'admin/dashboard',
			array(
				'products'    => $this->products->list( $page, $per_page ),
				'per_page'    => $per_page,
				'notice'      => $this->notice->message(),
				'notice_kind' => $this->notice->kind(),
			)
		);
	}
```

`Plugin::modules()`: `new DashboardScreen( $this->template(), $notice, $products, new PerPage() )` — `$products`
is already built on line 103 for the REST controller, so nothing new is constructed.

- [x] **Step 6: Templates and styles**

`templates/admin/partials/product-row.php` — Lasso's row: the name, and the destination link under it. The name
becomes a link to the product page in Task 14, which is where that page is built; until then it is plain, as the
add flow's redirect already is (`ProductsController`).

```php
<?php
/**
 * One Dashboard row: the product's name over its destination link, as Lasso's row has it
 * (admin/views/rows/url-row.php). The image, alert, locations, clicks and action cells arrive with the tasks
 * that own their data (14, 16, 31, 34, 36).
 *
 * @package Affibloom
 * @var array{product: \Affibloom\Domain\Product\Product} $view
 */

defined( 'ABSPATH' ) || exit;

$product = $view['product'];
?>
<tr data-product-id="<?php echo esc_attr( (string) $product->id ); ?>">
	<td class="affibloom-col-name">
		<span class="affibloom-row__name"><?php echo esc_html( $product->name ); ?></span>
		<a class="affibloom-row__url" href="<?php echo esc_url( $product->url->value() ); ?>" target="_blank"
			rel="noopener noreferrer"><?php echo esc_html( $product->url->value() ); ?></a>
	</td>
</tr>
```

`templates/admin/partials/pager.php` — the card's footer, as Lasso's: the per-page select and the range on the
left, the page links on the right. The select is a form that submits with the page's other arguments, so it needs
no JavaScript.

```php
<?php
/**
 * The list's footer: "<n> per page · 1-10 of 263" and the page links (Lasso's lasso-dashboard-pager).
 *
 * @package Affibloom
 * @var array{products: \Affibloom\Domain\Product\ProductPage} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Application\Products\ProductService;

$products = $view['products'];
$links    = paginate_links(
	array(
		'base'      => add_query_arg( 'paged', '%#%' ),
		'format'    => '',
		'current'   => min( $products->page, $products->pages() ),
		'total'     => $products->pages(),
		'end_size'  => 2,
		'mid_size'  => 1,
		'prev_text' => '<span class="screen-reader-text">' . esc_html__( 'Previous', 'affibloom' ) . '</span>',
		'next_text' => '<span class="screen-reader-text">' . esc_html__( 'Next', 'affibloom' ) . '</span>',
	)
);
?>
<div class="affibloom-pager">
	<form class="affibloom-pager__size" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="affibloom">
		<?php if ( '' !== ( $view['term'] ?? '' ) ) : ?>
			<input type="hidden" name="s" value="<?php echo esc_attr( $view['term'] ); ?>">
		<?php endif; ?>
		<label class="screen-reader-text" for="affibloom-per-page"><?php esc_html_e( 'Links per page', 'affibloom' ); ?></label>
		<select id="affibloom-per-page" class="affibloom-select" name="per_page" onchange="this.form.submit()">
			<?php foreach ( ProductService::PER_PAGE_OPTIONS as $option ) : ?>
				<option value="<?php echo esc_attr( (string) $option ); ?>" <?php selected( $option, $products->per_page ); ?>>
					<?php echo esc_html( number_format_i18n( $option ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<noscript><button type="submit" class="affibloom-button affibloom-button--secondary"><?php esc_html_e( 'Apply', 'affibloom' ); ?></button></noscript>
		<span class="affibloom-pager__per-page"><?php esc_html_e( 'per page', 'affibloom' ); ?></span>
		<span class="affibloom-pager__sep" aria-hidden="true">·</span>
		<span class="affibloom-pager__range">
			<?php
			echo esc_html(
				0 === $products->total
					/* translators: %s: number of products, always 0. */
					? sprintf( __( '%s of 0', 'affibloom' ), number_format_i18n( 0 ) )
					/* translators: 1: first product on this page, 2: last product on this page, 3: products in the whole list. */
					: sprintf(
						__( '%1$s-%2$s of %3$s', 'affibloom' ),
						number_format_i18n( $products->first() ),
						number_format_i18n( $products->last() ),
						number_format_i18n( $products->total )
					)
			);
			?>
		</span>
	</form>
	<?php if ( $links ) : ?>
		<nav class="affibloom-pagination" aria-label="<?php esc_attr_e( 'Products', 'affibloom' ); ?>">
			<?php echo wp_kses_post( $links ); ?>
		</nav>
	<?php endif; ?>
</div>
```

`templates/admin/dashboard.php`: the products tile (U9) above the card; then, when the library has products, the
card with the table and the pager; otherwise the empty card that is there now. The table head carries Link Name
only; the other columns arrive with their tasks, as the plan's column table says.

`assets/admin/admin.css`: the design system's **Table** (card-wide, header row on subtle, 12px padding, `line`
dividers, the name in 600 with the URL in ink-600 under it), the **Overview tile**, and the pager — a card footer
on subtle, the select and range on the left, the page links on the right, the current page in brand. At 782px the
footer stacks. No Lasso CSS is copied.

- [x] **Step 7: Run and look**
  - **Checks:** `composer lint`, `composer analyse`, `composer test:unit`. `tests/Integration/ProductListTest.php`
    is written (a seeded library, ordering, totals, a page beyond the last) but not run on this machine
    (deviation 10).
  - **Claude:** `bash tools/local-wp.sh post list --post_type=affibloom_product --post_status=publish --format=count`
    for the total, then the Dashboard in the browser pane at 10, 25 and 50 per page, page 1, page 2 and a page
    beyond the last; `touch` one product (`wp post update <id> --post_title=…`) and check it moves to the top;
    the empty state with every product trashed on a throwaway copy of the library, restored afterwards; the
    footer at 782px.
  - **The user:** runs F19 (AC1–AC4), and F1 to see AC3 still red for the search.

- [x] **Step 8: Commit**

```bash
git add -A
git commit -m "List the products on the Dashboard, ten to a page

Most recently changed first, as Lasso orders it, with Lasso's per-page
choice of 10, 25 or 50, its range line and its page links. The page and
the size are in the URL, so a list can be shared and reloaded."
```

---

### Task 13B: Finding a product in the list

**Roadmap:** 2.2 · **Feature:** F19 (requirements section 3; AC5–AC9), F1 (AC3)

**Why:** 263 products are 27 pages, so the search box is how an editor reaches one. Lasso's search is not a title
search: it cleans the typed text, turns its spaces into wildcards and matches the name, the destination link, the
ASIN, the second button's link, URL variants and the record's ID. On an Amazon-heavy library that is the point —
editors paste a link or an ASIN to find the product that already holds it. The plan's `WP_Query` `s` would have
found neither (F19 requirements, section 7).

**User action:** the editor types `Wave Momentum` in the search box and presses Enter: the list shows the
products whose name contains both, in that order. Pasting the whole destination link of a product finds it.
Typing an ASIN finds the Amazon product that carries it; typing a product's ID finds that product. A search that
matches nothing says so, and the search stays in the URL, so the page can be reloaded or shared.

**Lasso's rules** (F19 requirements, section 3): clean to `A-Za-z0-9+-.#` plus spaces, trim; spaces become `%`;
wrap in `%…%`; the OR arms; search on Enter, back to page 1.

**Decisions this task applies** (F19 requirements, section 6): **D4** the arms Affibloom has data for now (name,
link, ASIN, ID), the second button's link with Task 15; **D5** Lasso's cleaning and wildcards exactly; **D6** a
search that matches nothing says so, instead of Lasso's "Get Started by Adding a Link"; **D7** the placeholder
keeps counting every product.

**Request path:**
```
GET admin.php?page=affibloom&s=wave+momentum[&paged=2][&per_page=25]
  → DashboardScreen::render()
       SearchTerm::from_input( $_GET['s'] )       cleaning, wildcards, the shapes
       ProductService::list( $term, $page, $per_page )
         ProductRepository::page( $term, $page, $per_page )
           PostTypeProductRepository  the same WP_Query, with 's' and, for this query only,
                                      a posts_search filter carrying Lasso's OR clause
  ← dashboard.php: the search box holds the term; "No products match your search." when nothing does
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Product\SearchTerm` (`from_input`, `is_empty`, `text`, `like`, `post_id`, `asin`) | `DashboardScreen`, `PostTypeProductRepository` |
| `PostTypeProductRepository::search_sql()` (the `posts_search` filter) | `WP_Query`, during `page()` |
| The search form in `dashboard.php`, the "nothing found" state | `dashboard.php` |

`ProductRepository::page()` takes the term as its first argument from this task on; 13A's callers are changed
with it rather than a second method being added, so there is one way to ask for a page.

**Files:**
- Create: `src/Domain/Product/SearchTerm.php`, `tests/Unit/Domain/Product/SearchTermTest.php`
- Modify: `src/Domain/Product/ProductRepository.php`,
  `src/Infrastructure/WordPress/PostTypeProductRepository.php`,
  `src/Application/Products/ProductService.php`, `src/Interface/Admin/DashboardScreen.php`,
  `templates/admin/dashboard.php`, `templates/admin/partials/pager.php`, `assets/admin/admin.css`,
  `tests/Unit/Fakes/InMemoryProductRepository.php`, `tests/E2E/acceptance/F19.spec.ts`,
  `tests/Integration/ProductListTest.php`

- [x] **Step 1: The failing acceptance tests** — F19 **AC5** (a word in the name; the search survives a reload),
  **AC6** (the whole destination link, pasted with its `https://` and query), **AC7** (an ASIN; a product's ID),
  **AC8** (two words in order), **AC9** (nothing found says so; clearing brings the list back); and **AC10**'s
  `test.fixme` from 13A is turned into the real test. `tests/E2E/acceptance/F1.spec.ts` AC3 must go green with no
  change to it. Expected red: the search box does not exist.

- [x] **Step 2: Unit tests** — `SearchTermTest` (Brain Monkey): the cleaning (`https://www.amazon.com/dp/B0C1?tag=x`
  → `https www.amazon.com dp B0C1 tag x`; `Mizuno—Wave!` → `Mizuno Wave`; runs of punctuation collapse to one
  space; a term of punctuation only is empty; CJK survives); `like()` (`Wave Momentum` → `%Wave%Momentum%`, a
  single word → `%Wave%`); `post_id()` (digits yes, `12a` no, `0` no); `asin()` (`b0cph9npk8` → `B0CPH9NPK8`,
  `B0C` no, `Mizuno` no).

- [x] **Step 3: Domain** — `SearchTerm`, with Lasso's two regular expressions quoted in its docblock
  (`view-js.php` 1818 for the cleaning, `class-lasso-db.php` 57 and 127 for the wildcards and the ASIN shape).
  No WordPress call: `esc_like` belongs to the repository, which is where `$wpdb` is.

- [x] **Step 4: Infrastructure** — `page()` takes the term; when it is not empty the query carries
  `'s' => $term->text()` and `'affibloom_search' => $term`, and a `posts_search` filter is added just before the
  query and removed just after:

```php
	/**
	 * Lasso's Dashboard search instead of WordPress's word-by-word title and content search
	 * (Lasso_DB::build_dashboard_search_term_string): the name, the destination link, the ASIN, and — for a term
	 * that is all digits or shaped like an ASIN — the product's ID or that exact ASIN. Task 15 adds
	 * `_affibloom_url2` to the meta keys when the second button's link exists; 60 live Lasso records have one.
	 *
	 * @param string    $search The search SQL WordPress built, replaced here.
	 * @param \WP_Query $query  The query it was built for.
	 */
	public function search_sql( string $search, \WP_Query $query ): string {
		$term = $query->get( 'affibloom_search' );
		if ( ! $term instanceof SearchTerm || $term->is_empty() ) {
			return $search;
		}

		global $wpdb;

		$like   = $term->like( static fn( string $value ): string => $wpdb->esc_like( $value ) );
		$arms   = array( $wpdb->prepare( "{$wpdb->posts}.post_title LIKE %s", $like ) );
		$arms[] = $wpdb->prepare(
			"EXISTS ( SELECT 1 FROM {$wpdb->postmeta} AS m WHERE m.post_id = {$wpdb->posts}.ID
				AND m.meta_key IN ( '_affibloom_url', '_affibloom_asin' ) AND m.meta_value LIKE %s )",
			$like
		);

		$id = $term->post_id();
		if ( null !== $id ) {
			$arms[] = $wpdb->prepare( "{$wpdb->posts}.ID = %d", $id );
		}

		$asin = $term->asin();
		if ( null !== $asin ) {
			$arms[] = $wpdb->prepare(
				"EXISTS ( SELECT 1 FROM {$wpdb->postmeta} AS m_asin WHERE m_asin.post_id = {$wpdb->posts}.ID
					AND m_asin.meta_key = '_affibloom_asin' AND m_asin.meta_value = %s )",
				$asin
			);
		}

		return ' AND ( ' . implode( ' OR ', $arms ) . ' ) ';
	}
```

  `SearchTerm::like()` takes the escaper as an argument (`callable(string): string`), so `%` and `_` typed by an
  editor are escaped by `$wpdb` without the Domain layer knowing what `$wpdb` is; Lasso does not escape them at
  all. Whether the simpler shape — the repository escaping the words and the term returning them — reads better
  is settled while writing it, and whichever is chosen is explained in the docblock.

- [x] **Step 5: Screen and templates** — `DashboardScreen` reads `s` with `sanitize_text_field( wp_unslash( … ) )`
  and makes the `SearchTerm`; the view gets the cleaned text back for the box and the hidden fields. The form is
  the toolbar above the table (`docs/spec/interface.md`: `#affibloom-search`, `name="s"`, the label "Search"
  visually hidden, placeholder `Search all <n> links…`), carrying `page` and `per_page` as hidden fields so the
  page size survives a search; submitting resets to page 1 by leaving `paged` out. The empty-state card says
  "No products match your search." when a search found nothing, and keeps "No products yet." with the Add action
  when the library is empty.

- [x] **Step 6: Run and look**
  - **Checks:** `composer lint`, `composer analyse`, `composer test:unit`.
  - **Claude:** in the browser pane, search a name, a whole link, an ASIN and an ID against the 22 products on
    this copy; a search with `%` and `_` in it; a search that matches nothing; a search then page 2 then 25 per
    page, checking the term survives each; the URL after each.
  - **The user:** runs F19 and F1 (AC3 green).

- [x] **Step 7: Commit**

```bash
git add -A
git commit -m "Find a product by its name, its link, its ASIN or its ID

Lasso's Dashboard search, not a title search: the typed text is cleaned,
its spaces become wildcards, and the name, the destination link and the
ASIN are all matched, which is how an Amazon library is searched."
```

---
### Task 13C: The Dashboard's full table — every Lasso column, the tiles and the selection bar

**Roadmap:** 2.2 · **Feature:** F19 (requirements sections 4 and 8)

**Why:** the user, 2026-11-13: *"Complete the entire Dashboard UI first. It should look and feel like a finished
product, with all columns … Use placeholders only for functionality that will be implemented in future tasks.
Don't omit columns or UI elements just because their underlying functionality isn't ready yet."* Lasso's row has
eight parts; 13A drew one.

**User action:** the editor opens the Dashboard and sees Lasso's table: a select box, Image, Link Name, Groups,
Alert, Locations, Clicks and Actions. Images show for the 240 products that have one, a neutral tile for the
rest. Columns whose data arrives later carry a "Soon" chip in the header and a muted dash in each cell, read out as
"Coming soon". Edit and Shortcode are in every row, disabled, with "Coming soon". Ticking rows shows Lasso's
selection bar; its Delete and Add to Group are disabled until Tasks 17 and Groups. Five overview tiles: Products
(live), Broken links, Out of stock, Opportunities, Clicks (30 days).

**Placeholders, and where each one's data comes from:**

| UI | Placeholder until | Why not now |
|---|---|---|
| Groups column, Group filter, Add to Group | the Groups area (after Tasks 14-17) | no taxonomy yet |
| Alert column, Broken links tile | Tasks 30-31 | no link check yet |
| Out of stock tile | Amazon Creators API credentials (C1) | needs Amazon's stock data (deviation 52) |
| Opportunities tile | the Opportunities area | needs the content link index |
| Locations column | the content link index and Task 36 | no index yet |
| Clicks column, Clicks (30 days) tile | Tasks 33-34 | no click counting yet |
| Edit | Task 16 | quick edit |
| Shortcode | Task 18S | Affibloom's own shortcode |
| Delete (bulk) | Task 17 and the in-use rule (36) | no delete yet |
| Link Name as a link | Task 14 | the product page |

**Not read from Lasso's tables.** Lasso's own alerts, locations and clicks are on this copy, and `_affibloom_lasso_id`
would reach them, but Affibloom never depends on Lasso's tables at runtime (ADR-2): they are read once, by the
migration. Placeholders are honest; borrowed numbers would vanish the day Lasso is deleted.

**Files:** `templates/admin/dashboard.php`, `templates/admin/partials/{product-row,product-list,dashboard-tiles,
selection-bar,soon}.php`, `templates/admin/partials/icon.php` (new icons), `assets/admin/admin.css`,
`tests/E2E/acceptance/F19.spec.ts`.

- [x] **Step 1: Failing test** — F19 **AC11**: the table's header reads, in order, the select box, Image, Link
  Name, Groups, Alert, Locations, Clicks, Actions; a product with an image shows it; Edit and Shortcode are
  disabled. Expected red: one column.
- [x] **Step 2: Templates** — the columns, the placeholder partial, the tiles, the selection bar.
- [x] **Step 3: Styles** — Aurora's table with fixed column widths (Lasso's grid: 1 / 4 / 2 / 1 / 1 / 1 / 2 of 12),
  the thumbnail, the "Soon" chip, the disabled small buttons, five tiles in one row.
- [x] **Step 4: Selection** — `assets/admin/dashboard.js`: row boxes, the page's box, the bar with the count,
  Select all `<n>` and Clear selection.
- [x] **Step 5: Run and look**, **Step 6: Commit**.

---

### Task 13D: The list without page reloads

**Roadmap:** 2.2 · **Feature:** F19 (requirements sections 2-3)

**Why:** the user: *"search is already possible, but you haven't implemented it using AJAX. Make sure it works
properly without page reloads."* Lasso's list is an AJAX view (`lasso_report_urls`). This **reverses F19 decision
D1** (PHP page per request), and it is what ADR-7 allows: JavaScript where Lasso uses it. The URL still carries the
search, page and size (`history.pushState`), so reload, back and bookmarks keep working, and the page still works
without JavaScript.

**Request path:**
```
Enter in Search · a page link · the per-page select
  → dashboard.js: wp.apiFetch GET /affibloom/v1/dashboard/products?s=&paged=&per_page=
       DashboardController::products()         permission: Capabilities::PRODUCTS
         PerPage::choose(), SearchTerm::from_input(), ProductService::list()
         Template::fetch( 'admin/partials/product-list' )   the same partial the page prints
  ← { data: { html, total, library, url } }
  → the list region is replaced, the URL pushed, the result announced
```

**Decisions:** one template for both renders (the REST response carries the partial's HTML, so markup and escaping
stay in PHP and there is no second row template in JavaScript); stale responses are dropped (a request counter);
the region is `aria-busy` while loading; a failed load shows an inline "The list could not be loaded" with Try
again; `Ctrl+Shift+L` / `⌘⇧L` focuses the search box (Lasso's shortcut opens its command palette, which is a Phase
8 task; until then the shortcut does the part that exists).

**Files:** create `src/Interface/Rest/DashboardController.php`, `assets/admin/dashboard.js` (from 13C);
modify `src/Interface/Template.php` (`fetch()`), `src/Interface/Admin/{DashboardScreen,AdminAssets}.php`,
`src/Plugin.php`, the dashboard templates, `tests/E2E/acceptance/F19.spec.ts`.

- [x] **Step 1: Failing test** — F19 **AC12**: a search, a page change and a size change each update the list and
  the URL **without a navigation** (Playwright: no `load` event), and Back restores the previous list.
- [x] **Step 2: Route** — `GET /dashboard/products`, its permission, its arguments.
- [x] **Step 3: Script** — submit, pager and select handlers, history, stale-response guard, busy and error states.
- [x] **Step 4: Run and look**, **Step 5: Commit**.

---

### Task 13E: The filters panel, the link-type filter and saved views

**Roadmap:** 2.2 · **Feature:** F19 (requirements section 8)

**Why:** Lasso's Filters panel and Saved views (`Dashboard_Saved_Views`). Under the no-cloud rule (deviation 52)
both are buildable now, except the filters whose data comes later.

**User action:** the editor opens Filters: Group (Coming soon), **Link type: All types / Amazon / Non-Amazon**
(live), Placements (Coming soon), Comparison table (Coming soon), "Save as" with Lasso's help text, Reset filters,
"Filters apply as you toggle". The Filters button counts active filters. Naming the current list and pressing Save
adds a pill to the Saved view bar; clicking a pill restores its search, link type and page size; its × asks Lasso's
"Delete saved view?" first. "All links" clears everything.

**Lasso's rules** (`class-dashboard-saved-views.php`): per user, in user meta; at most **30**; a name of 1-80
characters; a view restores search, filters, sort and page size; a manual change clears the active pill; deleting
leaves the links alone. Link type is `url_details.product_type = amazon` or not; Affibloom's is `_affibloom_type`.

**Decision:** the panel opens as a card under the Filters button, not beside the table as in Lasso: with eight
columns in Aurora's 1080px column, a 280px side panel leaves the Link Name about 90px wide.

**Files:** create `src/Domain/Product/LinkKind.php`, `src/Domain/Dashboard/{SavedView,SavedViews}.php`,
`src/Application/Dashboard/SavedViewService.php`, `src/Infrastructure/WordPress/UserMetaSavedViewRepository.php`,
`src/Domain/Dashboard/SavedViewRepository.php`, `templates/admin/partials/{filters-panel,saved-views,
view-delete-modal}.php`, unit tests for `LinkKind` and `SavedViews`; modify the product repository (the link-type
clause), `ProductService::list()`, `DashboardController` (the views routes), `dashboard.js`, `admin.css`,
`tests/E2E/acceptance/F19.spec.ts`.

- [x] **Step 1: Failing tests** — F19 **AC13** (Link type Amazon shows only Amazon products, and the Filters button
  says 1), **AC14** (a view saved, restored after a reload, deleted after the confirm).
- [x] **Step 2: Unit tests** — `LinkKind::from()`; `SavedViews`: the 30 limit, the name rules, replace by id,
  remove.
- [x] **Step 3: Domain, service, repository, routes.**
- [x] **Step 4: Panel, pills, modal, script.**
- [x] **Step 5: Run and look**, **Step 6: Commit**.

---

*(Superseded Task 14: its text is in `archive/phase-2-superseded.md`; replaced by Tasks 14A-14H.)*

---

### Task 14A: The product page opens — band, tabs, the link card, and every way in

**Roadmap:** 2.2 · **Feature:** F1 (requirements 3, 4 left card; AC1, AC5 left card, AC6, AC7 first half, AC8, AC9)

**Why:** every product action Lasso offers starts on this page, and three places already wait for it: the add flow
sends a non-Amazon link to the Dashboard as a stand-in (deviation 56), the Dashboard row's name is plain text
(13A), and a duplicate's warning is a toast instead of Lasso's notice on the product's page.

**User action:** the editor clicks a product's name or image on the Dashboard, or adds a non-Amazon link, and lands
on its page. The band holds the name as the title (Lasso's "Enter a URL Name" when empty), the destination as a
link under it, and the segmented tabs Details, Locations, Opportunities, Variants (the last three "Coming soon").
Below, in Lasso's arrangement (D1, gate 4), the left card shows Name; Permalink (non-Amazon only, "Coming soon");
Primary Destination URL with its Button Text and the two toggles; the same four for the secondary button; Groups
("Coming soon"); and the custom-fields area, filled by 14C. The footer has "Delete This Link" (Task 17) on the left
and "Save Changes" (Task 15) on the right, both disabled with "Coming soon". Adding a link that already exists opens
the existing product's page with Lasso's warning.

**Request path:**
```
GET admin.php?page=affibloom-product&id=123[&is_duplicate=1]
  → AdminMenu: add_submenu_page( 'affibloom', …, Capabilities::PRODUCTS, 'affibloom-product', ProductScreen::render )
       hide_product_page() on admin_head (reachable, not listed; Dashboard stays highlighted)
  → load-<hook>: ProductScreen::guard() → ProductRepository::get( 123 ) → null, or not published
       → wp_safe_redirect( Dashboard ) + exit                                (before any output)
  → ProductScreen::render() → Template 'admin/product' { product, placeholders (Display palette), duplicate }
  ← band (title, link line, tabs), left card, right card shell (14B), footer
POST /affibloom/v1/products → redirect_to: non-Amazon → ProductScreen::url( id ) (+ is_duplicate=1 when found)
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `ProductScreen::{url, guard, render}`, `SLUG` | the hidden submenu and its load hook; `product-row.php`; `ProductsController` |
| `ProductRepository::get( int ): ?Product` | `ProductScreen::guard()` and `render()` |
| `Product` fields `button_text`, `url2` (string, as stored; Task 15 validates it), `button_text2`, `new_tab2`, `nofollow2` | the left card; Task 15 writes them |
| `hydrate()` for those five (a missing `_new_tab2`/`_nofollow2` falls back to the primary value) | `get()`, the list |
| `templates/admin/product.php`, `partials/product-band.php`, `partials/field-text.php`, `partials/field-toggle.php` | `ProductScreen::render()` |
| Dashboard: name and image as links to the page | `product-row.php` |
| `ProductsController`: the product page as `redirect_to` for non-Amazon links; `is_duplicate=1` | admin.js (unchanged: it follows `redirect_to`) |

`partials/field-text.php` and `field-toggle.php` are this screen's label-above / toggle-left rows (Lasso's product
page shape, unlike Settings' rows); 14B is their second caller.

**Files:** create `src/Interface/Admin/ProductScreen.php`, `templates/admin/product.php`, the three partials,
`tests/Unit/Interface/Admin/ProductScreenTest.php`; modify `src/Domain/Product/{Product,ProductRepository}.php`,
`src/Infrastructure/WordPress/PostTypeProductRepository.php`, `tests/Unit/Fakes/InMemoryProductRepository.php`,
`src/Interface/Admin/AdminMenu.php`, `src/Interface/Rest/ProductsController.php`, `src/Plugin.php`,
`templates/admin/partials/product-row.php`, `assets/admin/admin.css`, `tests/E2E/acceptance/F1.spec.ts`,
`tests/E2E/acceptance/F18.spec.ts` (its "until Task 14" endings).

- [x] **Step 1: Failing tests.** F1 AC1 (written in Task 7) expects `page=affibloom-product&id=`; add **AC5** (left
  card: a seeded product's name, both URLs, a stored button text and the placeholder of an empty one, the four
  toggles' states), **AC6** (the same non-Amazon link twice → the first product's page and Lasso's sentence),
  **AC8** (`id=999999999` and a post's ID → the Dashboard) and **AC9** (the row's name and image are links to the
  page). Expected red: AC1 lands on the Dashboard; AC5 finds no "Name" field; AC8 shows "Sorry, you are not allowed
  to access this page."; AC9 finds no link. Run AC1 and AC8 (unsigned) here; the rest are the user's run.
- [x] **Step 2: Route.** `AdminMenu` takes `ProductScreen`; adds the submenu before Settings, `hide_product_page()`
  on `admin_head`, and `load-<hook>` → `guard()`. `ProductScreen::url( $id )` builds
  `admin.php?page=affibloom-product&id=<id>`. Unit test: the hook names and that `guard()` redirects for null.
- [x] **Step 3: Domain and storage.** The five fields with "not set" defaults; `get()` = `get_post()` of type
  `affibloom_product`, published, through `hydrate()`; the in-memory fake gains `get()`. `url2` stays the stored
  string, as Lasso keeps `second_btn_url` as text, so a stored value that would not parse is still shown, never
  dropped or a fatal error.
- [x] **Step 4: Screen and template.** Band (`h1#affibloom-product-title` with the name or "Enter a URL Name", the
  link line `.affibloom-product-url` = the destination (D5), the tabs as `<a>`/`<span aria-disabled>` with the "Soon"
  chip); the left card in Lasso's order with Lasso's labels, help tips (the shared floating tips in `admin.js`),
  placeholders (`https://www.example.com/affiliate-id`, `…-id2`, the Single palette's two button texts, "URL Name
  Goes Here"); Permalink hidden for `ProductType::Amazon` and search pages; Groups and Permalink disabled with
  "Coming soon"; the footer. Every other input is editable and named as Task 15 will send it
  (`name`, `url`, `button_text`, `new_tab`, `nofollow`, `url2`, `button_text2`, `new_tab2`, `nofollow2`).
- [x] **Step 5: Ways in.** `product-row.php`: the name and the image link to the page (the destination stays the
  second line, opening in a new tab). `ProductsController`: non-Amazon → the page; a non-Amazon duplicate → the page
  with `is_duplicate=1`, where the template shows Lasso's warning (design system's warning notice, not a toast);
  Amazon unchanged. Remove the Dashboard's `duplicate` notice code that only that case used. Update F18's endings.
- [x] **Step 6: Styles** from the design system: the band's tabs, two cards (5 / 7 of the column, stacking below
  992px as Lasso's `col-lg`), label-above fields, toggles left of their labels, the footer row.
- [x] **Step 7: Run and look.** lint, PHPStan, unit; F1 AC1 and AC8. Browser pane (or a signed-out preview of the real
  markup if the pane is not signed in) at 1440, 992 and 375px beside `product-details-1/2.jpg`: fields, labels, help
  texts and order match Lasso; looks follow Aurora. Open an Amazon product (no Permalink), a non-Amazon one with a
  secondary URL, and a product whose button text holds `<span class="amazon-icon"></span>` (shown as text).
- [x] **Step 8: Commit** — "Open a product's own page from the Dashboard and after an add".

---

### Task 14F: Button icons — the icon set

**Roadmap:** 2.1 / 2.2 · **Feature:** F28 (requirements B1-B3, D1; AC1, AC2) · **Register:** U11 (an addition)

**Why:** the user, 2026-11-13: icons are typed as HTML today, and 17 live texts name a class the site's CSS does not
define. Before a picker can list icons, Affibloom needs the list: the six the site's Additional CSS defines, plus any an
editor adds without CSS.

**User action:** Settings → Display shows a "Button icons" card under the split card: each icon's picture, name and
class; imported ones say "From your site's CSS"; "Add icon" asks for a name and an image from the media library;
added ones have Remove, which asks first.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Icons\{Icon, IconSet}`, `IconRepository` | `IconService` |
| `Application\Icons\IconService` (`all`, `add`, `remove`), port `SiteIcons` | `DisplayScreen`, `IconController`, `IconStyles` |
| `Infrastructure\WordPress\{OptionIconRepository, SiteCssIcons}` | `Plugin` |
| `Interface\Admin\IconController` (admin-post add/remove) | the card's forms |
| `Interface\IconStyles` (inline CSS for added icons, site and admin) | `wp_enqueue_scripts`, `admin_enqueue_scripts` |
| `templates/admin/partials/icon-set.php`; media frame in `display-settings.js` | `settings-display.php` |

- [x] **Step 1: Failing test** — F28 **AC1** and **AC2** (new `tests/E2E/acceptance/F28.spec.ts`). Red: no "Button
  icons" card.
- [x] **Step 2: Domain** with unit tests: the class rule, unique slug (`Medal` twice → `medal-2-icon`), name 1-40,
  removing an imported icon refused, names from classes.
- [x] **Step 3: Import** with a unit test over the live Additional CSS block (copied into a fixture): six icons, the
  nested `@media` ignored, `alsogood`'s relative URL kept as written.
- [x] **Step 4: Storage, service, controller and the card**; WP-CLI: the option filled once, add and remove through
  admin-post with and without a nonce.
- [x] **Step 5: Styles for added icons** (B2), printed only when one exists; a test page with a text using the new
  class shows the image.
- [x] **Step 6: Run and look** (1440, 992, 375), **Step 7: Commit** — "Keep a set of button icons, the site's six and
  any an editor adds".

---

### Task 14G: Button icons — the picker on every button and badge text field

**Roadmap:** 2.1 / 2.2 · **Feature:** F28 (B4-B6, D2, D3; AC3-AC6)

**Why:** with the set in place, the fields that hold an icon get a picker, so nobody types `<span class="…">` again.

**User action:** each button text field (Settings → Display's two, the product page's two) shows a button left of
the text with the current icon; it opens a menu of "No icon" and every icon; the text box holds only the words. An
unknown class shows a warning glyph and "Unknown icon: <class>" and is kept until another is chosen. Badge Text (14B)
and quick edit (16) use the same field when they are built.

| New | Called by |
|---|---|
| `Domain\Icons\IconText` (`split`, `compose`) with unit tests | `partials/icon-text-field.php` (the server renders the split for the first paint) |
| `templates/admin/partials/icon-text-field.php` | `settings-display.php`, `product.php` |
| `assets/admin/icon-picker.js` (Menu component; keeps the real field's value composed) and its data | `AdminAssets` on Settings and the product page |

- [x] **Step 1: Failing tests** — F28 **AC3-AC6** (Display primary button text and the product page's Button Text;
  `javaScriptEnabled: false` for AC6). Red: no picker button.
- [x] **Step 2: `IconText`** (B5: exactly one leading empty span, plain text after; else not split).
- [x] **Step 3: Field partial and script**: the real input stays the named field (D3), hidden when enhanced; the picker
  and the words input write it on every change; `affibloom:change` fires so Save-when-changed sees it.
- [x] **Step 4: Use it** on the Display tab's two texts and the product page's two Button Text fields.
- [x] **Step 5: Run and look** — open products with `madel-icon` (unknown), `amazon-icon`, a plain text; F16, F1 and
  F18 still pass. **Step 6: Commit** — "Choose a button's icon from a list instead of typing HTML".

---

### Task 14H: Any number of buttons — the product and its page

**Roadmap:** 2.2 · **Feature:** F29 (requirements B1-B3, B7, D1, D4; AC1, AC2) · **Register:** U12 (an addition)

**Why:** the user, 2026-11-13: *"We should have option to add multiple buttons. Currently the lasso has option to add
only two buttons."* 60 products here already use the second button for a shoe's men's and women's versions or another
shop; a third has no place but a second box. The product and its page come first; Task 15 saves the list, Phase 3
draws it, C3 and Tasks 33-34 count it.

**User action (as changed by the user during the task, F29 D6-D7):** the product starts with one button, its own
link (Primary Destination URL, "Main link"). "Add Another Button" adds a block (Additional Destination URL, Button
Text with the icon picker, New Window / Tab, NoFollow / NoIndex). Every block, the default one too, has Move up and
Move down; an added one has Remove. The first in the list is the box's primary button. Saving is Task 15.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Product\{Button, Buttons}`; `Product::$buttons` replacing `url2`, `button_text2`, `new_tab2`, `nofollow2` (D4) | `hydrate()`, the template; Task 15 writes it |
| `hydrate()` for `_affibloom_buttons`, reading the old four keys as one button when the new key is absent | `get()`, the list |
| `templates/admin/partials/product-button.php` (one block; the secondary's labels or the additional ones) | `product.php` |
| `assets/admin/product-buttons.js` (add from a `<template>`, move, remove with a confirm, renumber `buttons[i]`) | the product page |
| Dashboard search over `_affibloom_buttons` (F19 D4, B7) | `PostTypeProductRepository::search_filter()` |
| `tools/seed-from-lasso.php` writes `_affibloom_buttons` | the Local seed |

- [x] **Step 1: Failing tests** — new F29 spec: **AC1** (a product with a secondary and two additional buttons shows
  three blocks in order, the icon picker on each text) and **AC2** (Add Another Button, Move up, Remove with its
  question; the posted names follow the shown order). F1 AC5 changes with D4 (its `url2` fields become the first
  button). Red: one secondary block, no "Add Another Button".
- [x] **Step 2: Domain** with unit tests: order kept, empty buttons dropped on `from_array`, the secondary is the first,
  the old four keys read as one button.
- [x] **Step 3: Storage and search** (`hydrate()`, the search arm), then the seed tool; re-seed the Local copy after a
  backup and check the 60 second buttons came across.
- [x] **Step 4: Template, script, styles** (design system: the block as a sub-card with a 1px line, the move and remove
  controls as small secondary buttons, Remove's confirm via the design system's dialog wording).
- [x] **Step 5: Run and look** at 1440, 992 and 375 px; F1, F18, F28 still pass. **Step 6: Commit** — "Give a product as
  many buttons as it needs".

**Carried to later tasks** (each task's text gains its part when it is worked): Task 15 saves and validates the list
(D5: Amazon handling as the secondary's); Phase 3's box tasks draw buttons 3 and up (B4, AC3, AC5); C3 and Tasks 33-34
count them (B5, AC4); Task 26 maps Lasso's `second_btn_*` to button 2 (B8).

---

### Task 14B: The display card — theme, image, badge, price, description, disclosure and the toggles

**Roadmap:** 2.2 · **Feature:** F1 (requirements 4 right card, D2, D4, D6-D9; AC5 right card, AC7 second half)

**Why:** the right half of Lasso's page holds everything a box shows besides the link: 198 badges, 198
descriptions, 186 prices and 240 images on this site. Phase 3's box reads them from the product, and Task 15 saves
them, so they must exist on the product and on the page first.

**User action:** the right card shows, top to bottom: the preview area (a "Coming soon" frame until 14E, holding the
current image with Lasso's "Update Image" overlay); Display Theme (`Default (<Single theme>)`, then Cactus, Cutter,
Flow, Geek, Lab, Llama, Money, Splash); the image link (`Amazon Image` or `Product Image`) with Apply, and for Amazon
a disabled Refresh, "Needs the Amazon API"; Badge Text and Price side by side (Price's show toggle on the right of
its label; non-Amazon: a one-line rich editor with bold, italic, underline, strike and text colour; Amazon:
read-only with "Amazon prices updated via Amazon API settings"); Description (rich editor with Lasso's toolbar);
Disclosure (placeholder: the site's text); Sponsored and Show Disclosure; Detect Opportunities and, for non-Amazon,
Link Cloaking (both "Coming soon"); for Amazon, Show Prime Logo. Clicking the image opens the media library; picking
one or applying a link updates the image and the field; nothing is stored until Save Changes (D2).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Product` fields `theme` (`?Theme`, null = the type's default), `badge`, `price`, `show_price`, `description`, `disclosure`, `prime`, `image_id` | the right card; Task 15 writes them; Phase 3 renders them |
| `hydrate()` for them; a disclosure equal to the site's text reads as '' (edge case 3) | `get()` |
| `ProductScreen` view data: the Single theme's name, the site disclosure, `show_price` default | the template |
| `assets/admin/product.js`: media frame, Apply's checks, the price toggle's state | the page |
| `AdminAssets`: `wp_enqueue_media()` and `product.js` on the product page only | the page |

**Files:** modify `Product.php`, `PostTypeProductRepository.php`, `ProductScreen.php`, `AdminAssets.php`,
`templates/admin/product.php`, `admin.css`; create `assets/admin/product.js`, `templates/admin/partials/product-display-card.php`;
tests `tests/Unit/Infrastructure/HydrateTest.php` (or the repository's existing test), F1 **AC5** (right card) and
**AC7**.

- [x] **Step 1: Failing tests.** AC5's right half (a seeded product's badge, description text, disclosure placeholder,
  theme option "Default (Lab)" when the Single theme is Lab) and AC7 (Amazon: no Permalink, no Link Cloaking,
  "Amazon Image", price read-only with the sentence). Expected red: no "Badge Text" field.
- [x] **Step 2: Domain and storage** with unit tests: `Theme::from_name()` for the stored value, unknown → null;
  `show_price` absent → the Display setting `show_price` (Lasso: `'' === meta` → the site's); the disclosure rule.
- [x] **Step 3: Template.** Lasso's labels and help texts (requirements 4). Description: `wp_editor( …,
  'affibloom_description', [ media_buttons false, quicktags false, tinymce: toolbar1 'bold,italic,underline,
  strikethrough,link,bullist,forecolor,backcolor,removeformat', toolbar2 '' ] )`, placeholder "Enter a description".
  Price: a second instance, one row, `bold,italic,underline,strikethrough,forecolor`, for non-Amazon; a read-only
  input for Amazon, linking "Amazon API settings" to Settings → Amazon.
- [x] **Step 4: Image.** The preview image (the stored link, else the no-image picture) with the "Update Image"
  overlay as a button; `wp.media` "Select an Image", images, one, "Select Image" → src, link field and a hidden
  `image_id`. Apply: Lasso's checks ("Image URL is required.", "Invalid image url.", `data:` refused) in the field's
  error spot, then the preview. Amazon's link field stays editable (D6).
- [x] **Step 5: Run and look.** As 14A Step 7, beside `product-details-2/3.jpg`; open the five longest live
  descriptions and check TinyMCE keeps their markup byte for byte when nothing is touched (`wp.editor.getContent()`
  against the stored meta in the console).
- [x] **Step 6: Commit** — "Show every display field on the product page".

---

### Task 14C: Custom fields on the product page — Primary Rating, Pros, Cons and the editor's own

**Roadmap:** 2.2 · **Feature:** F1 (requirements 4 custom fields, B6, B7, D3, D8; AC10)

**Why:** Lasso's rating is not a plain field: it is the built-in custom field "Primary Rating", attached to 5
products here (3.5-4.5, shown in their boxes), and Pros and Cons are the same kind of field. The product page is
where they are attached and filled.

**User action:** attached fields show as rows above "Add Custom Field": a grip to drag (Primary Rating stays first),
the field's name with its description as a help tip, a "Show this field in your display by default." toggle (and for
Primary Rating also "Show field name in your display."), the value (text, number, textarea or list, or for rating a
1-5 number with stars), and a remove button that asks first. "Add Custom Field" opens "Add Field" with the tabs
"Create new" (Field Title, Field Type, Description, "Create Field") and "Add from library" (search, ten per page, a
row per field with Add). "Manage Fields" shows "Coming soon" (the Fields area). Every change is stored as it is made
(D3); the page's Save Changes is not involved.

**Request path:**
```
GET    /affibloom/v1/fields?search=&page=   → FieldsController → FieldsService::library()  → built-ins + option
POST   /affibloom/v1/fields                  → FieldsService::create( name, type, description ) → 201, then attach
POST   /affibloom/v1/products/{id}/fields    → FieldsService::attach( id, field )   → 201 { rows }
PUT    /affibloom/v1/products/{id}/fields    → FieldsService::update( id, rows )    → 200 { rows }
DELETE /affibloom/v1/products/{id}/fields/{field} → FieldsService::detach()         → 200 { rows }
  each: Capabilities::PRODUCTS; 404 for a missing product or field; 422 affibloom_invalid_field
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Fields\{Field, FieldType, ProductField, ProductFields}` | `FieldsService`, the repository |
| `Domain\Product\Rating` (1-5, one decimal; empty → 3.5 shown, nothing stored until typed) | `ProductFields::set()` for `rating` fields |
| `Domain\Fields\FieldRepository` + `Infrastructure\WordPress\OptionFieldRepository` (`affibloom_fields`) | `FieldsService` |
| `ProductRepository::{fields, save_fields}` (`_affibloom_fields`) | `FieldsService` |
| `Application\Fields\FieldsService` | the two controllers |
| `Interface\Rest\{FieldsController, ProductFieldsController}` | `assets/admin/product-fields.js` |
| `templates/admin/partials/{product-fields,field-row,field-modal,field-remove-modal}.php` | `product.php` |

**Lasso's rules kept** (requirements B6-B7): Field Title 1-80 characters (Lasso's table column), one of the seven
types; a field already on the product is not offered again in the library; values are saved on leaving the box, a
toggle on change, the order on drop; the rating box clamps to 1-5 and one decimal as Lasso's script does; Primary
Rating keeps the first place. Built-ins have Lasso's IDs 1-3 (analysis), so the migration maps Lasso's rows as they are.

- [x] **Step 1: Failing test** — F1 **AC10** (library → Primary Rating → 4.5 → reload shows 4.5 and both toggles; ×
  asks, Remove removes). Expected red: no "Add Custom Field" button.
- [x] **Step 2: Domain** with unit tests (attach, duplicate attach refused, detach, reorder keeps Primary Rating
  first, rating clamp and format, name length, unknown type).
- [x] **Step 3: Storage and service**, then the routes; WP-CLI through the REST server: 403 for a subscriber, 404,
  422, the round trip.
- [x] **Step 4: Rows and modal** (templates and `product-fields.js`; drag with the keyboard as well: the grip takes
  ↑/↓, announced). Design-system Modal and the remove dialog: "Remove <field>?"-style wording from
  `url-field-delete.php`.
- [x] **Step 5: Run and look**, beside `product-details-2.jpg`'s rating card. **Step 6: Commit** — "Attach custom
  fields to a product, as Lasso's Primary Rating, Pros and Cons".

---

### Task 14D: The Share kit and the Amazon tracking-ID notice

**Roadmap:** 2.2 · **Feature:** F1 (requirements B9, B10, D4, D5; AC11), F17 (the tracking ID)

**Why:** Lasso 370's page opens with the Share kit, and an Amazon product on a site without a tracking ID shows
Lasso's offer to save the link's tag as the site's ID — a local setting this site already has (10F).

**User action:** under the band, a "Share kit" card: "Copy snippets for posts, email, and social." and five buttons.
Copy shareable URL and Copy destination URL copy the destination (D5); Copy disclosure copies the Disclosure field,
else the site's text; each label flashes "Copied!" for 1.5 seconds; Copy shortcode and Copy block are disabled,
"Coming soon" (Task 18S, Phase 4). On an Amazon product's page, when the site has no tracking ID, a notice above the
band says the site has no Amazon Tracking ID saved and offers the link's tag ("Would you like to set **<tag>** as
your primary tracking ID?" Yes / No), or, when the link has no tag, a "Set Amazon Tracking ID" link to Settings →
Amazon. Yes stores it by the Amazon tab's rule (an invalid tag is refused, deviation 32) and shows "Tracking ID
Saved!".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `templates/admin/partials/share-kit.php`; `product.js`: copy with the clipboard API and the `execCommand` fallback | the page |
| `Interface\Admin\TrackingIdController` (`admin-post.php?action=affibloom_tracking_id`, nonce, `PRODUCTS`) | the notice's Yes |
| `AmazonUrl::tag()` (the link's `tag` value), if 12A has none public | the notice |
| `templates/admin/partials/tracking-id-notice.php` | `product.php` |

- [x] **Step 1: Failing test** — F1 **AC11** (copy destination and disclosure; reads the clipboard with the
  `clipboard-read` permission; "Copied!"). Expected red: no "Share kit".
- [x] **Step 2: Share kit**, **Step 3: Notice** (with a unit test of the controller's refusal path), **Step 4: Run and
  look** (WP-CLI: tracking ID emptied on the backed-up copy, Yes, the setting back; restored after), **Step 5:
  Commit** — "Add the Share kit and the tracking-ID offer to the product page".

---

### Task 14E: The live preview (superseded here: the task is in Phase 3)

**Feature:** F1 (requirements B2-B4)

The right card's preview renders the real box for the product with the form's unsaved values, re-renders when the
theme or the Primary URL changes (without saving, D2), and follows typing in the name, button texts, badge, second
URL, price, disclosure and description. It needs the box template, so it is written in full and built in Phase 3
right after Task 18x, beside 10D, sharing its preview route.

---

*(Superseded Task 15: its text is in `archive/phase-2-superseded.md`; replaced by Tasks 15A-15D below.)*

---

### Task 15A: Save Changes stores the Details form

**Roadmap:** 2.2 · **Feature:** F1 (requirements 9 S1-S6, S14, S15, D10, D11, D19, D20; AC2, AC13), F29 (D5), F9 (AC1)

**Why:** everything 14A-14H shows (the buttons and their order, theme, image, badge, price, description, disclosure,
the switches) is read-only until it can be stored; Phase 3's box and Task 16's quick edit read and write the same
fields.

**User action:** the editor changes the name, a button's text and URL (typing `amazon.com/dp/B0C7654321` without a
scheme), moves a button up, picks an image, sets the badge and Show Price, and clicks Save Changes (or presses Enter
in a text field). "Updating Your Site" opens with a moving bar and "Just saving your URL changes" ("Saving your
changes." when only New Window or NoFollow changed) and closes about a second after the answer; the toast says
"Product saved."; the heading shows the new name and the button field shows `https://www.amazon.com/dp/B0C7654321?tag=vbvault-20`.
An empty Name shows "Name and Primary Destination URL are required." under it, the failure toast "Try again. For
some reason your product didn't save.", and nothing is saved. A description or price nobody touched is not sent.

**Request path:** requirements analysis, "Saving" (15A). The Primary URL is not sent in 15A (15B adds it); 15A and
15B are built back to back, so no release has a page that drops a changed Primary URL.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| route `PATCH /products/{id}` (`ProductsController::update()`), `ProductResource::full()` | `product-save.js` (Task 16 is the second caller) |
| `Application\Products\{ProductInput, ValidationFailed, ProductNotFound}` | `ProductService::update()` |
| `ProductService::update()` | the route |
| `Application\Products\LinkNormaliser::button()` — S4, extracted from `create_from_url()`'s geni.us and Amazon steps on their second use | `ProductInput` |
| `Domain\Product\PriceHtml` — S3's script/style removal and `rgb()`/`rgba()` → `#hex`; `wp_kses` with Lasso's price tags stays in `ProductInput` | `ProductInput` |
| `PostTypeProductRepository::save()` updating a product with an ID (`wp_insert_post` with `ID`), writing every field in analysis "Data written" | `ProductService::update()` |
| reason `required` in `Messages` | the 422 answer |
| `templates/admin/partials/save-modal.php` (the progress panel; 15C adds its second panel) | the product page |
| `assets/admin/product-save.js`: collect, the dialog, field errors, write-back, the JS toast | the product page |

**Files:** create `src/Application/Products/{ProductInput,ValidationFailed,ProductNotFound,LinkNormaliser}.php`,
`src/Domain/Product/PriceHtml.php`, `templates/admin/partials/save-modal.php`, `assets/admin/product-save.js`;
modify `ProductService.php`, `ProductsController.php`, `ProductResource.php`, `PostTypeProductRepository.php`,
`Messages.php`, `Plugin.php`, `AdminAssets.php`, `templates/admin/product.php` (no `data-not-saved`; Save Changes a
submit button), `admin.css`; tests `tests/Unit/Application/{ProductInputTest,ProductServiceUpdateTest,LinkNormaliserTest}.php`,
`tests/Unit/Domain/PriceHtmlTest.php`, the repository's unit test, F1 **AC2** (rewritten) and **AC13**.

- [x] **Step 1: Failing tests.** AC2: on a seeded product change the name, the secondary's text, move it above the
  default button, set the badge and Show Price, save; expect the dialog, "Product saved.", and after a reload every
  value. AC13: empty Name → the sentence under the field, the title unchanged after a reload; a button URL
  `amazon.com/dp/B0C7654321` comes back tagged; an untouched description is byte-identical in the meta (WP-CLI
  check in the spec's `afterAll`). Expected red: Save Changes is disabled ("Coming soon").
- [x] **Step 2: Route and controller.** `PATCH /products/(?P<id>\d+)`, `Capabilities::PRODUCTS`, args typed (strings,
  booleans, `buttons` an array of objects, `primary_position` an integer); only known fields reach the service;
  `ProductNotFound` → 404 `affibloom_not_found` ("This product does not exist."); `ValidationFailed` → 422
  `affibloom_invalid` with `data.fields` (the existing `FieldError` shape).
- [x] **Step 3: Service and input** with unit tests. `ProductInput::changes()`: only the fields present; S2 (Name
  required; nothing else refused); S3 per field (icon markup through the same cleaner as 14G's fields); buttons:
  each URL through `LinkNormaliser::button()`, empty blocks dropped (`Buttons`' rule), `primary_position` clamped;
  theme '' → `null` (the display type's theme), any other name read by `Theme::from()` as 14B does (an unknown one
  is Lasso's default theme), never refused; `show_price` absent → unchanged; `image_id` > 0 → `image_url` becomes the
  attachment's URL (S6), 0 → the typed link; price through `PriceHtml` then `wp_kses`; description `wp_kses_post`,
  `<p><br></p>` → '', `wp_encode_emoji`. `ProductService::update()`: get → changes → save. `LinkNormaliser::button()`:
  trim; geni.us → destination unless `keep_genius_formatting`; `https://` added; Amazon → `monetized()` unless short
  or protected; `esc_url_raw`. `create_from_url()` now calls the normaliser for its geni.us and Amazon steps
  (its tests stay green).
- [x] **Step 4: Storage** with the repository test: an ID updates the same post; every key written; a value read and
  written back unchanged is byte-identical (`wp_slash` on the way in).
- [x] **Step 5: The page.** `product-save.js` (enqueued on the product page only): collect every named field
  (`buttons[i][…]`, toggles as booleans); the description and price only when `tinymce.get(…).isDirty()` or their
  content differs from what was loaded; the dialog (S14's two texts); on 200 write back heading, Name, every button
  URL, image, price (S15) and show "Product saved."; on 422 the messages under their fields (Aurora's field error,
  `aria-invalid`, focus on the first) and the failure toast; any other error the failure toast with the server's
  message when it has one. Enter in a text field submits; Save Changes is disabled while saving.
- [x] **Step 6: Run and look.** `composer lint`, `composer analyse`, `composer test:unit`; the user runs F1 AC2 and
  AC13 in VS Code. By hand on Local: save a seeded product with three buttons in a new order and check the meta with
  WP-CLI; open one of the five longest live descriptions, change only the badge, save, and compare the description
  meta byte for byte; empty Name → the error and nothing stored.
- [x] **Step 7: Commit** — "Save the product page through PATCH /products/{id}".

---

### Task 15B: Changing the Primary URL

**Roadmap:** 2.2 · **Feature:** F1 (requirements 9 S7, S8, D12, D17, D18; AC14), F2

**Why:** a product's link changes when a deal moves or Amazon retires an ASIN; Lasso re-applies every add-flow rule
and decides whether the product keeps its name and image, and the stored link is what every box sends readers to.

**User action:** on a product named "Amazon" the editor pastes another product's Amazon link with a foreign tag and
saves: the field shows the link with the site's tag, the name becomes the new link's starting name, the image is
cleared to the default picture. Pasting an `amzn.to` link to the same ASIN keeps the short link, the name and the
image. Replacing an Amazon link with a non-Amazon one (or the reverse) saves and reloads the page with the toast, now
with the other type's rows. An empty Primary URL is refused with S2's sentence under the field.

**Request path:** analysis "Saving" (15B).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `url` in the PATCH args and in `ProductInput` | `product-save.js` |
| `LinkNormaliser::primary()` — the add flow's steps for one link (repair, geni.us, short Amazon links followed, Amazon tag rule, search pages), extracted from `create_from_url()` | `ProductInput`, `create_from_url()` |
| `Domain\Product\PlaceholderName` (S8's list) | `ProductService::update()` |
| same-ASIN rule and the name/image rule in `ProductService::update()` (reads `keep_title_when_url_replaced`, its first caller) | the route |
| `save()` deleting `_affibloom_asin`, `_amazon_country`, `_search_key` when empty | `update()` |
| `reload` in the PATCH answer when the type changed; the page's `?saved=1` toast after the reload | `product-save.js` |

**Files:** create `src/Domain/Product/PlaceholderName.php`; modify `LinkNormaliser.php`, `ProductInput.php`,
`ProductService.php`, `PostTypeProductRepository.php`, `ProductsController.php`, `ProductScreen.php` (the `saved`
notice), `product-save.js`; tests `LinkNormaliserTest`, `ProductServiceUpdateTest`, `PlaceholderNameTest`, F1
**AC14**.

- [x] **Step 1: Failing test.** AC14's three cases on seeded products (foreign tag and other ASIN on "Amazon"; same
  ASIN as `amzn.to` — this copy blocks external HTTP, so the case uses a seeded short-link answer as 12D's tests do;
  Amazon → non-Amazon reloads). Expected red: the URL is not sent.
- [x] **Step 2: Normaliser** with unit tests, moved out of `create_from_url()` without changing what it returns
  (11B-12E's tests stay green), plus D12: the tag rule on every save.
- [x] **Step 3: Service** with unit tests: unchanged key → the URL fields untouched; same ASIN and country → in place,
  a typed `amzn.to` kept (S8); another ASIN or a new type → type, ASIN, country and search key from the new link,
  the name replaced when `keep_title_when_url_replaced` is off or `PlaceholderName::is()` holds, the image and
  `image_id` cleared (D17); Amazon → other deletes the Amazon keys.
- [x] **Step 4: The page:** send `url`; write back the stored link; on `reload` go to the page with `saved=1`.
- [x] **Step 5: Run and look.** Checks as 15A; the three cases by hand, the meta read with WP-CLI.
- [x] **Step 6: Commit** — "Re-apply the add rules when a product's Primary URL changes".

---

### Task 15C: The same link as another product — "This Product Already Exists"

**Roadmap:** 2.2 · **Feature:** F1 (requirements 9 S10, D14, D15; AC15)

**Why:** two products for one link split a guide's boxes between two records; Lasso offers to move everything to the
one that already exists and retire the other, without touching a post.

**User action:** the editor sets the Primary URL to a link product B already has and saves. Nothing is saved; the
dialog turns into "This Product Already Exists": "You already use that link on your site. **Do you want to change
all Locations to that link?**", Current Link (this product's name and link, View) → Existing Link (B's, View), Cancel
and Confirm. Cancel closes it; the form keeps the typed link. Confirm retires this product and opens B's page with
"Success! Locations now show this link."

**Request path:** analysis "Saving" (15C).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Products\DuplicateLink` (the other product) and the 409 `affibloom_duplicate` answer with `from` and `to` (id, name, link, page) | `ProductService::update()`, the route |
| the duplicate lookup of the add flow, shared (11B's, 12B's and 12C's rules; same-ASIN in place is not a duplicate) | `create_from_url()`, `update()` |
| route `POST /products/{id}/repoint { to, url }`, `ProductService::repoint()` with Lasso's checks and messages ("Invalid link IDs for repoint.", "Source link is no longer active.", "Target link must be published. Refresh and pick an active link.", "That URL no longer matches the selected link. Refresh and try again.") | the dialog's Confirm |
| `ProductRepository::retire( Product, int $to )`: `_affibloom_repointed_to`, then `wp_trash_post()` | `repoint()` (Task 17's delete is the second user of the trash) |
| the save dialog's second panel; `repoint_merged=1` → the success toast on B's page | `product-save.js`, `ProductScreen` |

**Files:** create `src/Application/Products/DuplicateLink.php`; modify `ProductService.php`, `ProductRepository.php`,
`PostTypeProductRepository.php`, `ProductsController.php`, `ProductScreen.php`, `save-modal.php`, `product-save.js`,
`admin.css`; tests `ProductServiceUpdateTest`, `ProductServiceRepointTest`, F1 **AC15**. **Also written now:** the
pointer rule into Task 18 (a box for a retired ID draws the product it points to), Task 27 (`[lasso id]` of a
retired product, the same) and Tasks 35-36 (the index rows move to the target), so the code that reads the pointer
is planned with it (D14).

- [x] **Step 1: Failing test.** AC15: two seeded products; give the first the second's link; expect the panel with
  both names; Cancel → nothing saved; again, Confirm → B's page, the toast, the first product in the trash with
  `_affibloom_repointed_to` = B. Expected red: the save succeeds and two products share a link.
- [x] **Step 2: Service** with unit tests: the duplicate found by each of the add flow's rules; same product and same
  ASIN are not duplicates; `repoint()`'s four checks in Lasso's order; `retire()` called once.
- [x] **Step 3: Routes, panel, toast.** The 409's `data` carries both products; the panel fills from it (View opens
  each product's page in a new tab); Confirm disables itself while sending; an error shows the failure toast with
  the message.
- [x] **Step 4: Run and look.** Checks as 15A; the case by hand; the trashed product read with WP-CLI.
- [x] **Step 5: Commit** — "Offer the existing product when a Primary URL is already in use".

---

### Task 15D: Unsaved changes

**Roadmap:** 2.2 · **Feature:** F1 (requirements 5 B8, 9 S16; AC16); U7 (parity)

**Why:** an editor who leaves the page loses every edit without a word; Lasso asks first (U7 is parity, built
without a decision). Lasso's Settings ask too; Affibloom's Settings tabs do not yet (found by this sweep, recorded in
`progress.md` as an open item for the General settings pass).

**User action:** after editing, a click on an admin link (the menu, a tab, Back to the Dashboard) opens "Unsaved
Changes": "You have unsaved changes that will be lost if you leave this page. What would you like to do?" with "Leave
Without Saving" and "Save Changes" (and ×). Leave goes; Save Changes saves and then goes (a refused save stays and
shows its errors); × stays. Reloading or closing the tab gets the browser's question. Right after a save, or with
nothing changed, nothing asks. Custom field rows (they save themselves) never count as changes.

**Request path:**
```
any input in the form → product-save.js compares a snapshot with the baseline (taken on load and after each save)
click on a[href] (not #…, javascript:, _blank, inside a dialog) with changes → templates/admin/partials/unsaved-modal.php
beforeunload with changes, not while saving and not after "Leave Without Saving" → the browser's prompt
```

**What this task creates, and who calls it:** `templates/admin/partials/unsaved-modal.php` (the product page) and the
snapshot, guard and dialog in `product-save.js`. **Files:** those two, `templates/admin/product.php`, `admin.css`; test
F1 **AC16**.

- [x] **Step 1: Failing test.** AC16's four cases. Expected red: the link navigates at once.
- [x] **Step 2: Snapshot and guard:** every named field and switch outside the custom-field rows, the buttons' order,
  and the two editors' content; taken after TinyMCE has initialised (Lasso takes it twice, once at once and once on
  the next tick, for the same reason).
- [x] **Step 3: Dialog** in the design system's modal, Lasso's texts; "Save Changes" runs 15A's save and continues
  only on 200.
- [x] **Step 4: Run and look.** Checks as 15A; the four cases by hand, the reload prompt in Brave.
- [x] **Step 5: Commit** — "Ask before leaving the product page with unsaved changes".

---

*(Superseded Task 16: its text is in `archive/phase-2-superseded.md`; replaced by Tasks 16A-16C below.)*

---

### Task 16A: Quick edit — the row opens, saves and closes

**Roadmap:** 2.2 · **Feature:** F1 (requirements 10 Q1-Q8, D22-D28, D31; AC17, AC18)

**Why:** Lasso lets an editor fix a product's name, link or picture from its Dashboard row without opening the
product; it is the row's first action and the Edit button has been a placeholder since 13C.

**User action:** on a row the editor clicks **Edit**. The row becomes the form: Name, Primary Destination URL, Groups
("Coming soon"), the link (new tab), and the Image frame with "Update Image" on hover and focus. Clicking the frame
opens the media library ("Select an Image" / "Select Image"); the picked picture shows in the frame. **Save Changes**
(or Enter in a field) shows "Updating Your Site" with "Just saving your URL changes", then "Product saved."; the list
reloads at the same page and size, the product first with its new name and picture. **Cancel** brings the row back
as it was. **Edit** on another row closes the first. An empty Name shows "Name and Primary Destination URL are
required." under it and the failure toast; nothing is saved and the row stays open.

**Request path:** analysis "Dashboard quick edit" (16A).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the live Edit button and `<template data-quick-edit>` (the editing row) in `product-row.php` | the Dashboard, and the list REST answer (13D renders the same partial) |
| the save dialog and the toast template on `templates/admin/dashboard.php` (the unsaved dialog comes with its caller, 16B) | `dashboard.js` |
| in `admin.js`, **moved from `product-save.js` and `product.js` on their second use:** `affibloomToast()`, the save dialog's progress panel (open, bar, close a second after the answer), the field-error helpers, `affibloomMedia.pick()` (Lasso's media frame) | `product-save.js`, `product.js`, `dashboard.js` |
| the quick edit in `dashboard.js`: open (one row at a time), Cancel, the image, save, write the errors, reload, focus (D27) | the row's buttons |
| `wp_enqueue_media()` on the Dashboard in `AdminAssets` | the media frame |
| `.affibloom-row--editing` and the image frame in `admin.css`, from the design system (form fields, field error, image tile; the frame's hover and focus overlay); a design-doc row for it | the editing row |

**Files:** modify `templates/admin/partials/product-row.php`, `templates/admin/dashboard.php`,
`templates/admin/product.php` (the toast template moves to a partial on its second use),
`src/Interface/Admin/AdminAssets.php`, `assets/admin/{admin.js,product-save.js,product.js,dashboard.js,admin.css}`,
`docs/ui/design-system.md`; create `templates/admin/partials/toast-template.php`; tests F1 **AC17**, **AC18** in
`tests/E2E/acceptance/F1.spec.ts` (the old plan's `tests/E2E/quick-edit.spec.ts` is not created: AC17-AC18 are it).

- [x] **Step 1: Failing tests.** AC17: a seeded product; Edit → the row shows the four labels, Name holds the stored
  name and has the focus; Cancel → the row's name link is back; Edit on a second product closes the first. AC18: Edit,
  type a new name, pick an existing attachment through the media frame, Save Changes → the dialog, "Product saved.",
  the first row's name and picture are the new ones; WP-CLI: `_affibloom_image_id` is the attachment, the description
  meta byte-identical to before; empty Name → the sentence under it, the title unchanged. Expected red: Edit is
  `aria-disabled` ("Coming soon").
- [x] **Step 2: The row's template.** In `product-row.php`: Edit loses `aria-disabled` and gets `aria-expanded` and
  `aria-controls`; after the row a `<template data-quick-edit data-for="<id>">` with the editing row: one `<td>`
  spanning every column, a `<form novalidate>` with Name (`maxlength` none: S2 refuses nothing else), Primary
  Destination URL, the Groups placeholder (`soon` partial, D24), the link (`target="_blank"`, D25), hidden
  `image_url` and `image_id`, the frame (a button: the picture or the no-image tile, D31, and "Update Image"), Save
  Changes (submit) and Cancel. Labels, placeholders and order are Lasso's (Q2).
- [x] **Step 3: Shared pieces in `admin.js`.** Move the toast, the save dialog's progress panel, the field-error
  helpers and the media frame out of `product-save.js` and `product.js` unchanged in behaviour; the product page calls
  them. Run F1 AC2, AC13, AC16 (the user's run) to show the product page is unchanged.
- [x] **Step 4: The quick edit in `dashboard.js`.** State outside the rows (`open` id, the values at opening), since
  `load()` replaces the region. Edit: close an open row (16B guards it), put the template's row in place of the row,
  hide the row, focus Name. Cancel: remove the editing row, show the row, focus its Edit. The frame: `affibloomMedia.pick`
  → the picture, `image_url`, `image_id`. Submit: `{ name, url, image_url, image_id }` → PATCH with the dialog; a second
  submit while saving does nothing; 200 → close the row, "Product saved.", `load( current )`, then focus the saved
  row's Edit if it is on the page; 422 → errors under Name / Primary Destination URL (Lasso's sentence for
  `required`, 15B's for `invalid_url`), focus the first, the failure toast; anything else the failure toast with the
  server's message. A list load waits while a save is in flight.
- [x] **Step 5: Run and look.** `composer lint`, `composer analyse`, `composer test:unit`, `node --check` on the four
  scripts; the user runs F1 AC17, AC18 and the product page's AC2, AC13-AC16. By hand on Local: open a row, Cancel;
  open two rows in turn; rename and pick an image, save, check the meta with WP-CLI (only the four fields' keys
  changed: compare a `wp post meta list` before and after); empty Name; a 375 px wide window.
- [x] **Step 6: Commit** — "Edit a product's name, link and image from its Dashboard row".

---

### Task 16B: Quick edit — unsaved changes on the Dashboard

**Roadmap:** 2.2 · **Feature:** F1 (requirements 10 Q9, D29; AC19); U7 (parity)

**Why:** an open row with edits is lost without a word when the editor searches, pages or leaves; Lasso asks first,
for every control that would redraw the list and every link that leaves the page.

**User action:** with a changed name, link or picture in an open row, a search, a page link, the page size, the link
type, Reset filters, a saved view, Edit on another row, an admin link, or Back opens "Unsaved Changes" (Lasso's
words): **Leave Without Saving** drops the edits and does what was asked; **Save Changes** saves (16A) and then does
it, or stays with the errors when the save is refused; **×** or Escape stays (after Back, the address is put back).
Closing or reloading the tab gets the browser's question. With nothing changed, after Cancel or after a save, nothing
asks.

**Request path:** analysis "Dashboard quick edit" (16B).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `guarded( action )` in `dashboard.js`, Lasso's `run_page_action`: runs `action` at once, or after the dialog | search, page links, page size, link type, Reset, pills, Edit (and later the sort, Groups filter, tiles and bulk actions, whose tasks call it) |
| `hasChanges()`: the four fields against the values at opening | `guarded()`, the link and `beforeunload` handlers |
| the `a[href]` handler (not `#…`, `javascript:`, `_blank`, a modified or middle click, inside a dialog or the open row), the `popstate` handling (D29) and `beforeunload` (quiet while saving and right after Leave) | the page |

**Files:** `assets/admin/dashboard.js`, `templates/admin/dashboard.php` (the unsaved dialog); test F1 **AC19**.

- [x] **Step 1: Failing test.** AC19: open a row, change the name; search → the dialog, × → the row still open with
  the typed name; a page link → Leave → page 2 and no open row; again, an admin menu link → Save Changes → saved,
  then the link's page; Cancel then search → no dialog; a `beforeunload` listener is armed only with changes (checked
  through `page.on( 'dialog' )` on a reload). Expected red: the search redraws the list and the edits are gone.
- [x] **Step 2: Guard and dialog.** `guarded()` around every `load()` caller and Edit; the dialog through
  `affibloomModal` (focus trap, Escape, return); Save Changes runs 16A's save and continues only on 200 (a 409 opens
  16C's panel instead and does not continue).
- [x] **Step 3: Links, Back, the browser.** The `a[href]` rules above; `popstate` with changes: ask; × pushes the
  current list's address back; Leave loads the address's list; `beforeunload` as in 15D.
- [x] **Step 4: Run and look.** Checks as 16A; the user runs AC19; by hand every guarded control once and the reload
  prompt in Brave.
- [x] **Step 5: Commit** — "Ask before a Dashboard action drops a row's unsaved edits".

---

### Task 16C: Quick edit — the same link as another product

**Roadmap:** 2.2 · **Feature:** F1 (requirements 10 Q10, D21, D30; AC20)

**Why:** Lasso's server offers the repoint for a quick edit as it does for the product page, but its Dashboard
reports "saved" and drops the offer; the editor believes the link changed while nothing did.

**User action:** in an open row the editor pastes a link product B already has and saves. The dialog turns into "This
Product Already Exists" (15C's panel, both products, View in a new tab). **Cancel** closes it; the row stays open with
the typed link and nothing is saved. **Confirm** retires this product and opens B's page with "Success! Locations now
show this link."

**Request path:** analysis "Dashboard quick edit" (16C); the repoint route is 15C's.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the repoint panel's show, Cancel and Confirm, **moved from `product-save.js` into `admin.js` on their second use** | `product-save.js`, `dashboard.js` |
| the 409 branch of the quick-edit save | `dashboard.js` |

**Files:** `assets/admin/{admin.js,product-save.js,dashboard.js}`; test F1 **AC20**.

- [x] **Step 1: Failing test.** AC20: two seeded products; in A's row the link of B, Save Changes → the panel with
  both names; Cancel → the row open with the typed link, A's link unchanged (WP-CLI); again, Confirm → B's page, the
  success toast, A in the trash with `_affibloom_repointed_to` = B. Expected red: the 409 shows the failure toast.
- [x] **Step 2: Shared panel.** Move the panel's code out of `product-save.js` without changing the product page (F1
  AC15 stays green); the Dashboard calls it with the 409's `from`, `to` and the typed link.
- [x] **Step 3: Run and look.** Checks as 16A; the user runs AC20 and AC15; by hand the case once, the trashed product
  read with WP-CLI.
- [x] **Step 4: Commit** — "Offer the existing product when a quick edit uses its link".

---

*(Superseded Task 17: its text is in `archive/phase-2-superseded.md`; replaced by Tasks 17A-17D below.)*

---

### Task 17A: Delete This Link — the product page deletes for good

**Roadmap:** 2.2 · **Feature:** F1 (requirements 11 X1-X4, D33, D35, D37-D39; AC4)

**Why:** an editor must be able to remove a product the site no longer needs, from the place Lasso puts it: the
product page's footer, a placeholder since 14A.

**User action:** on a product's page the editor clicks **Delete This Link**. A dialog asks "**Delete This Link?**" —
"If deleted, you won't be able to get it back." — with **Cancel** and **Delete** (red). Cancel closes it and keeps
the focus on Delete This Link. Delete removes the product for good and opens the Dashboard with the toast "Product
deleted."; unsaved edits on the page do not make the browser ask (D38). A failure closes the dialog and shows the
failure toast with the server's message, else "Could not delete. Please try again.".

**Request path:** analysis "Deleting" (17A).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `DELETE /affibloom/v1/products/{id}` in `ProductsController::routes()`, `ProductsController::delete()` | `product-save.js` |
| `ProductService::delete( int $id )` (ProductNotFound for anything not a published product) | the route |
| `ProductRepository::delete( int $id )`; `PostTypeProductRepository::delete()`: `wp_delete_post( $id, true )`, a `RuntimeException` when WordPress refuses | `ProductService::delete()` |
| `templates/admin/partials/delete-modal.php`: Lasso's `url-delete` in Aurora's modal (title, text, Cancel, Delete) | `templates/admin/product.php` |
| the live **Delete This Link** button (no `aria-disabled`, `data-affibloom-open`) | the product page |
| the delete in `product-save.js`: Delete → the route; 200 → quiet the leave guard, go to the Dashboard with `affibloom_notice=deleted`; else the failure toast | the dialog's Delete |
| `Notice` code `deleted` → "Product deleted." | the Dashboard after the redirect |

**Files:** modify `src/Interface/Rest/ProductsController.php`, `src/Application/Products/ProductService.php`,
`src/Domain/Product/ProductRepository.php`, `src/Infrastructure/WordPress/PostTypeProductRepository.php`,
`src/Interface/Admin/Notice.php`, `templates/admin/product.php`, `assets/admin/product-save.js`; create
`templates/admin/partials/delete-modal.php`; tests: `tests/Unit/Application/ProductServiceDeleteTest.php` (or the
service's existing test file), F1 **AC4** rewritten in `tests/E2E/acceptance/F1.spec.ts`.

- [x] **Step 1: Failing tests.** AC4: a seeded product; its page; type a new name (unsaved); Delete This Link → the
  dialog by its name "Delete This Link?" with Lasso's sentence; Cancel → still on the page; again → Delete → the
  Dashboard with "Product deleted.", no browser question (a `dialog` handler that fails the test); WP-CLI
  `post exists <id>` fails. Unit: `delete()` of an unknown ID throws `ProductNotFound` and deletes nothing; of a
  product calls the repository once. Expected red: the button is `aria-disabled` ("Coming soon").
- [x] **Step 2: Route, service, repository** (outside-in, as the request path). The route takes `id` (absint),
  `permission_callback` `can_manage`; `ProductNotFound` → `WP_Error( 'affibloom_not_found', 'This product does not
  exist.', 404 )`; a `RuntimeException` → 500 with "Could not delete. Please try again."; 200
  `{ data: { id, deleted: true } }`.
- [x] **Step 3: The dialog and the page.** `delete-modal.php` with Lasso's words; rendered in `product.php`; the button
  opens it; Delete shows the busy state (disabled, "Deleting…") while the request runs; Escape and the backdrop
  close it as Aurora's other modals.
- [x] **Step 4: Run and look.** `composer lint`, `composer analyse`, `composer test:unit`, `node --check`; F1 AC4 and
  the page's AC2, AC13-AC16 (the user's run, or from Claude's shell on the saved sign-in). By hand on Local (backup
  first): the dialog at 1280 px and 375 px, focus and Escape; delete a seeded product; WP-CLI shows no post and no
  meta for it.
- [x] **Step 5: Commit** — "Delete a product for good from its page, as Lasso does".

---

### Task 17B: Delete from the Dashboard — the ticked rows go to the trash

**Roadmap:** 2.2 · **Feature:** F1, F19 (requirements 11 X5-X7, X11, D35-D40; AC21)

**Why:** Lasso's Dashboard deletes several products at once from its selection bar, where Delete has been a
placeholder since 13C. Its selection is the page's; 13C's "Select all across pages" is not Lasso's and must not reach
a delete (D36).

**User action:** the editor ticks rows (or **Select All**, which ticks the page's rows); the bar reads "1 link
selected" or "N links selected". **Delete** — after 16B's "Unsaved Changes" question when a quick edit has changes —
opens "**Delete the selected links?**" — "If deleted, you won't be able to get them back." — Cancel / **Delete**.
Delete moves them to the trash, clears the selection, closes the dialog and redraws the same page (the page before
when this one is now past the last), with "N products deleted.". Moving to another page, searching or filtering drops
the selection of rows no longer shown.

**Request path:** analysis "Deleting" (17B).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `DELETE /affibloom/v1/products` with `ids` (array of 1-50 integers), `ProductsController::delete_many()` | `dashboard.js` |
| `ProductService::trash( int[] $ids ): int[]` (published products only; returns those trashed) | the route |
| `ProductRepository::trash( int $id )`; `PostTypeProductRepository::trash()`: `wp_update_post( status trash )`, as Lasso (no 30-day mark, D40) | `ProductService::trash()` |
| `delete-modal.php` taking its id, title and text (**its second use**: the product page passes Lasso's single words, the Dashboard the plural ones) | `product.php`, `dashboard.php` |
| the selection bar's live Delete; `selection-bar.php`'s count in Lasso's words (`_n()`: "%s link selected" / "%s links selected") | `dashboard.js` |
| in `dashboard.js`: the per-page selection (Select All ticks the page; `load()` keeps only the IDs drawn, D36); Delete through `guarded()`; the request, the redraw, the toast | the bar |

**Files:** modify `ProductsController.php`, `ProductService.php`, `ProductRepository.php`,
`PostTypeProductRepository.php`, `templates/admin/dashboard.php`, `templates/admin/partials/{selection-bar,
delete-modal}.php`, `templates/admin/product.php`, `assets/admin/dashboard.js`, `assets/admin/admin.css` (only if
the bar's danger button needs it), `docs/ui/design-system.md` (the selection bar row); tests: unit for `trash()`;
F1 **AC21** new; F19's selection test updated for the per-page Select All (it is 13C's own behaviour that changes).

- [x] **Step 1: Failing tests.** AC21: three seeded products on page 1; tick two → "2 links selected"; Delete → the
  dialog by its title; Cancel → still selected; Delete → "2 products deleted.", the two rows gone, the bar hidden;
  WP-CLI: both `trash`, the third `publish`, neither has `_wp_trash_meta_time`; Select All → the count is the page's
  rows; the next page link → the bar hidden. Unit: `trash()` skips an unknown ID and a trashed one. Expected red: the
  bar's Delete is `aria-disabled`.
- [x] **Step 2: Route, service, repository.** `ids` validated as an array of positive integers, 1-50 after
  `array_unique` (400 `affibloom_invalid_ids` otherwise); 200 `{ data: { deleted: [ids] } }`.
- [x] **Step 3: The selection, Lasso's.** Remove the "all" mode and its exceptions; Select All ticks the page's boxes;
  after every `load()` the selection keeps only IDs whose rows are drawn; the count uses the `_n()` pair printed in
  data attributes.
- [x] **Step 4: The dialog and the delete.** `delete-modal.php` parameterised; the Dashboard renders it with the plural
  words; the bar's Delete runs through `guarded()`, opens the dialog; Delete → busy state → the route → clear, close,
  `load()` of the current list (or of the page before when the answer leaves it empty and it is not page 1), the
  toast; a failure keeps the selection and shows the failure toast.
- [x] **Step 5: Run and look.** Checks as 17A; F1 AC21, F19 (the user's run). By hand on Local (backup first): tick
  and delete two products; the bar and the dialog at 1280 px and 375 px; delete the only row of the last page.
- [x] **Step 6: Commit** — "Delete the ticked Dashboard rows into the trash, as Lasso does".

---

### Task 17C: Retired products keep their own status

**Roadmap:** 2.2 · **Feature:** F1 (requirements 11 X10, X11, D41; AC22)

**Why:** 15C retires a product by moving it to the trash, and WordPress deletes posts that `wp_trash_post()` trashed
more than 30 days ago; the retired product and its pointer would go, and every old box of it with them. Lasso gives
retired records their own status, `lasso_delete`.

**User action:** none new: the repoint's Confirm (AC15) works as before; the retired product is `affibloom_retired`.

**Request path:** analysis "Deleting" (17C).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `ProductPostType::RETIRED = 'affibloom_retired'` and its `register_post_status()` on `init` (not public, excluded from search, not in admin lists) | `PostTypeProductRepository::retire()` |
| `retire()`: the pointer first, then `wp_update_post( status affibloom_retired )` in place of `wp_trash_post()` | `ProductService::repoint()` (15C) |

**Files:** modify `src/Infrastructure/WordPress/ProductPostType.php`,
`src/Infrastructure/WordPress/PostTypeProductRepository.php`; tests: F1 **AC15**'s status check becomes **AC22**
(`affibloom_retired`, no `_wp_trash_meta_time`); the phase 3 note at Task 18 and the phase 5 note at Task 26 updated.

- [x] **Step 1: Failing test.** AC22 (AC15's run, then the status). Expected red: `trash`.
- [x] **Step 2: The status and `retire()`.**
- [x] **Step 3: Run and look.** Checks as 17A; F1 AC15 and AC22. By hand: a repoint on Local, then
  `wp post get <id> --field=post_status`; the product page of the retired ID lands on the Dashboard (14A's rule:
  not published).
- [x] **Step 4: Commit** — "Give retired products their own status, out of the trash's reach".

---

### Task 17D: A deleted product comes back when its link is added again

**Roadmap:** 2.2 · **Feature:** F1, F18 (requirements 11 X9, D42, D15, D30; AC23)

**Why:** Lasso's add and save lookups match a trashed or retired record and publish it again, so a link deleted from
the Dashboard by mistake comes back with all its fields; 15C and 16C left this case to Task 17 (D15, D30).

**User action:** the editor adds (single or bulk) the link of a product that is in the trash or retired: that product
is published again with its name and fields and opened as a new product would be (its page; the Dashboard for
Amazon). Saving another product with that link (page or quick edit) publishes it again and refuses the save with
"This link already exists. Please try again with a different URL." under Primary Destination URL; a second save then
gets 15C's "This Product Already Exists" offer.

**Request path:** analysis "Deleting" (17D).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `ProductRepository::find_deleted( array $url_keys, string $asin, string $country, string $search_key ): ?Product` (built with a list of keys: the typed link, where it leads and the stored form, deviation 86) (trash or retired, by the keys the three published lookups use; plain strings, since the Domain cannot take `NormalizedLink`) | `ProductService::create_from_url()`, `update()` |
| `ProductRepository::revive( Product $product ): Product` (status `publish`, `_affibloom_repointed_to` removed) | the same two |
| the revive branch in `create_from_url()` (answered as created, so the add modal and bulk add treat it as new) and in `update()` (revive, then `ValidationFailed { url: already_exists }`) | `POST /products`, the bulk run, `PATCH /products/{id}` |
| `Messages` reason `already_exists` → Lasso's sentence (D42) | `FieldError` on the page and in quick edit |

**Files:** modify `ProductRepository.php`, `PostTypeProductRepository.php`, `ProductService.php`,
`src/Interface/Messages.php`; tests: unit for both branches; F1 **AC23** new.

- [x] **Step 1: Failing tests.** AC23: a product trashed through the bulk route; add its link → the same ID's page,
  its name and badge as before, status `publish`; trash it again, save another product with its link → the sentence
  under Primary Destination URL, the other product unchanged, the trashed one `publish`; a retired product (17C):
  add its link → `publish`, no `_affibloom_repointed_to`. Expected red: a new product is created.
- [x] **Step 2: The lookup and `revive()`** in the repository (`post_status` `trash` and `affibloom_retired`, newest
  first, as Lasso's `ORDER BY … DESC`).
- [x] **Step 3: The service branches**, after the published lookups find nothing.
- [x] **Step 4: Run and look.** Checks as 17A; F1 AC1, AC6, AC14, AC15, AC23 and F18 (the user's run). By hand on
  Local: bulk-delete a product, add its link in "Add A New Link", see it back on its page.
- [x] **Step 5: Commit** — "Bring a deleted product back when its link is added again, as Lasso does".

After 17D: compare the whole Dashboard and product page with `baselines/admin/dashboard.png` and the product-details
baselines for their delete controls, labels and order; the look follows the design system (ADR-8).

---

### Tasks G1-G5: Groups (F23) — area 0 of the completion plan

> Written 2026-11-14 from a sweep of Lasso's Groups (`docs/features/F23/{requirements,analysis}.md`, gates 1-2;
> deviation 88). Lasso's groups are unused on this site (one empty group) and are built in full under the full-scope
> rule. **Gate 4: given (recorded in this file 2026-11-14 as "Approve, start G1", confirmed by the user with the whole plan).** Every task: outside-in, its acceptance test first and red, `composer lint` on the files it
> touches (the user's `affibloom.php` fails lint until the user settles it), `composer analyse`, unit tests, the check
> by hand at 1280 and 375 px, one commit, pushed.
>
> Cross-checks added in the 2026-11-14 assembly (every R and AC of F23 owned; the routes and the picker
> that later features reuse; F30's order-less members; nothing above is rewritten): see "Cross-phase notes",
> section "Groups".

---

### Task G1: The Groups list, and a new group

**Roadmap:** 2.2 · **Feature:** F23 (R1-R7, D1-D3, D9, D11, D13; AC1, AC2)

**Depends on:** Tasks 8 and 9A (admin shell, design system), 13A-13D (the list pattern it copies: pages, search rule, in-place loading). No custom table.

**Why:** groups must exist before a product can join one; Lasso's Groups screen is where they are made and found.

**User action:** the header's new **Groups** tab opens the list: "Groups", **Add New Group**, "Search Groups", the
table (Name, Description, Links) ten to a page, newest first, or Lasso's empty state. Search and page links redraw the
list in place. **Add New Group** opens "Add a New Group" (Name, Description, Save Changes); a good save shows
"Updating Group", creates the group and opens its Details tab with "Group saved."; an empty name or a name in use is
refused under Name.

**Request path:** analysis, G1 lines.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `GroupTaxonomy` (Module; `affibloom_group` on `init`) | `Plugin` |
| `Domain\Group\{Group, GroupPage, GroupRepository}`, `TermGroupRepository::page()`, `create()` | `GroupService` |
| `Application\Groups\{GroupService::list(), create()}`, `GroupInvalid` | `GroupsScreen`, `GroupsController` |
| `GroupsScreen`, `GroupScreen` (new-group mode), the Groups tab in `header.php`, two submenu pages in `AdminMenu` | WordPress |
| `GET /groups` (list partial), `POST /groups` in `GroupsController` | `groups.js` |
| `templates/admin/{groups,group}.php`, partials `groups-list.php`, `group-row.php` | the screens, the list route |
| `assets/admin/groups.js` (list in place; the new-group form with the save dialog and field errors from `admin.js`) | the two pages |
| `Messages`: `group_name_required` ("Name is required."), `group_name_taken` ("A group with this name already exists.") | `FieldError` |

**Files:** create the classes and templates above, `tests/E2E/acceptance/F23.spec.ts` (AC1, AC2),
`tests/Unit/Application/GroupServiceTest.php`; modify `src/Plugin.php`, `src/Interface/Admin/{AdminMenu,AdminAssets}.php`,
`templates/admin/partials/header.php`, `assets/admin/admin.css`, `docs/ui/design-system.md` (Groups list row).

- [ ] **Step 1: Failing tests.** AC1 (two groups made through WP-CLI `term create affibloom_group`, the list's order,
  columns, search, the empty state after deleting them) and AC2 (create, the two refusals). Unit: create refuses '' and
  a taken name; list's search arms. Expected red: no Groups tab.
- [ ] **Step 2: Taxonomy, domain, repository, service.**
- [ ] **Step 3: Screens, routes, templates, script.** The list row links to the group's Details tab until G4 adds the
  Links tab.
- [ ] **Step 4: Run and look.** Checks; F23 AC1-AC2 and F19 (the header changed); by hand: list, search, empty state,
  new group, both refusals, 375 px.
- [ ] **Step 5: Commit** — "Add Lasso's Groups list and new-group form".

---

### Task G2: A group's Details — edit and delete

**Roadmap:** 2.2 · **Feature:** F23 (R5-R11, D2-D5, D11; AC3)

**Depends on:** G1; the shared `delete-modal.php` (17A).

**Why:** a group's name and description change, and an empty group must be removable — but never one in use.

**User action:** a group's page: heading, tabs (**Links `<n>`**, shown from G4; **Details**), Copy Shortcode ("Coming
soon", D5). Details: Name, Slug (read only), Description, Save Changes ("Updating Group", "Group saved.", the slug kept).
**Delete This Group**: with products, "Hold Up" and nothing deleted; without, "Delete This Group?" → Delete → the Groups
list with "Successfully deleted the Group.".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `PATCH /groups/{id}`, `DELETE /groups/{id}` | `groups.js` |
| `GroupService::get(), update(), delete()` (`GroupNotFound`, `GroupInUse` from the published-member count, D4) | the routes, `GroupScreen` |
| `TermGroupRepository::get(), update(), delete(), count_members()` | the service |
| the Details tab in `group.php`; the two dialogs (the shared `delete-modal.php` for "Delete This Group?", a "Hold Up" panel) | `GroupScreen` |
| `Notice` codes `group-saved`, `group-deleted` | the redirects |

**Files:** the classes above; `templates/admin/group.php`, `templates/admin/partials/delete-modal.php` (unchanged
markup, its third user), `assets/admin/groups.js`; tests F23 **AC3**, unit tests for update and the in-use refusal.

- [ ] **Step 1: Failing tests** (AC3: edit keeps the slug; delete of an empty group; "Hold Up" for a group with a
  product attached through WP-CLI).
- [ ] **Step 2: Routes, service, repository.**  - [ ] **Step 3: The tab and the dialogs.**
- [ ] **Step 4: Run and look.**  - [ ] **Step 5: Commit** — "Edit and delete a group, never one in use".

---

### Task G3: Groups on the product page, and the Dashboard's Groups column

**Roadmap:** 2.2 · **Feature:** F23, F1 (R13, R14, R16, D8, D12; AC4)

**Depends on:** G1, G2; 14A and 15A (the product page and its PATCH), 13C (the Dashboard's Groups column placeholder).

**Why:** products join groups from their own page in Lasso; the field and the Dashboard column have been "Coming soon"
since 14A and 13C.

**User action:** on a product's page **Groups** is the group picker: chips for its groups, a box that lists the site's
groups as you type ("Select a group"), Enter or "Create “<name>”" for a new one, × or Backspace to drop one. Save
Changes stores the product in exactly those groups (new names become groups). The Dashboard's Groups column names them.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the group picker: `templates/admin/partials/group-picker.php`, `affibloomGroupPicker` in `admin.js`, its CSS and design-system row | the product page (and G5) |
| `groups` in `ProductsController::field_args()` and `ProductInput` (array of positive IDs or names) | PATCH |
| `GroupService::resolve(), set_for_product(), for_products()`; `TermGroupRepository::set_terms(), order(), save_order()` | `ProductService::update()`, the screens, `ProductResource` |
| `Domain\Group\GroupOrder` (append, remove, rank, D12) | the service |
| `groups` in `ProductResource::full()`; the column's names in `product-row.php` (one query for the page's products) | the page, the list |

**Files:** the above; `templates/admin/product.php` (the field replaces its placeholder), `product-save.js` (sends
`groups`, part of the unsaved snapshot), `templates/admin/partials/product-row.php`, `DashboardScreen` /
`DashboardController` (the page's groups); tests F23 **AC4**, F1 AC2 and AC16 (the snapshot), F19 AC11 (the column),
unit tests for resolve and `GroupOrder`.

- [ ] **Step 1: Failing tests.**  - [ ] **Step 2: Domain, service, repository.**  - [ ] **Step 3: PATCH and the
  resource.**  - [ ] **Step 4: The picker and the page.**  - [ ] **Step 5: The column.**
- [ ] **Step 6: Run and look** (keyboard only; screen reader names; 375 px).  - [ ] **Step 7: Commit** — "Choose a
  product's groups on its page, as Lasso does".

---

### Task G4: A group's Links tab — order, remove, add

**Roadmap:** 2.2 · **Feature:** F23 (R17-R20, D6, D7, D10, D12; AC5-AC7)

**Depends on:** G1-G3; 13B (the Dashboard's search rule, reused by `GET /products?search=`).

**Why:** Lasso manages a group from the group itself: its products in display order, taken out, or added from a search.

**User action:** a group opens on **Links `<n>`**: every product in the group's order (grip, Move up / Move down,
picture, Link Name, Description or "(blank)", **Remove**), the selection bar (Select All, Clear Selection, **Remove**),
**Add a Link** in the heading and **Delete This Group** in the footer. Dragging or the move buttons save the order.
Remove asks Lasso's question (one or the selected) and takes them out of the group only. **Add a Link** opens "Add to
Group": "Search URLs", ten a page, **Add** on each result (the row appears at once, last; "Link already exists." for a
member), **Done**.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `GET /products?search=&page=&group=` (D10; first caller), `POST` and `DELETE /groups/{id}/products`, `PUT /groups/{id}/order` | `group-members.js` |
| `GroupService::members(), add(), remove(), reorder()` | the routes, `GroupScreen` |
| `ProductService::search()`, `ProductRepository::search()` (the Dashboard's search rule, F19, ten a page) | the search route |
| the Links tab in `group.php`; partials `group-member-row.php`, `add-to-group-modal.php`; `assets/admin/group-members.js` (drag with pointer events, the move buttons, the selection, the dialogs) | `GroupScreen` |

**Files:** the above; the list rows (G1) and the Details tab (G2) now link to the Links tab; tests F23 **AC5-AC7**, unit
tests for `GroupOrder::move()` and the routes' refusals.

- [ ] **Step 1: Failing tests.**  - [ ] **Step 2: Service and routes.**  - [ ] **Step 3: The tab, the rows, the
  modal.**  - [ ] **Step 4: Run and look** (drag, keyboard moves, 375 px).  - [ ] **Step 5: Commit** — "Order, remove
  and add a group's products from its Links tab".

---

### Task G5: Groups on the Dashboard — quick edit, filter, Add to Group

**Roadmap:** 2.2 · **Feature:** F23, F19, F1 (R15, R21-R23; AC8-AC10)

**Depends on:** G3, G4; 16A-16B (quick edit and its guard), 13E (filters panel, saved views), 17B (the selection bar).

**Why:** the last three "Coming soon" group controls on the Dashboard (F1 D24, F19 13C/13E).

**User action:** the quick edit's **Groups** is the picker, saved with the row (F1 D28 gains `groups`). The filters
panel's **Group** (All groups, Not in Groups, each group) narrows the list in place, counts as a filter and is kept in
saved views. Ticked rows → **Add to Group** (through 16B's guard) shows the picker and **Save Changes**; the products
join the chosen groups and keep their others; none chosen → "Select at least one group."; then the page reloads in place.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `group` in the Dashboard list route, `DashboardUrl`, `SavedViews`, and `PostTypeProductRepository::page()` (`tax_query`) | `dashboard.js`, the filters panel |
| `POST /products/groups` and `GroupService::append()` | the bar's Add to Group |
| the picker in the quick edit's `<template>` and the bulk editor in `selection-bar.php` | `dashboard.js` |

**Files:** the above; `templates/admin/partials/{product-row,filters-panel,selection-bar,saved-views}.php`,
`assets/admin/dashboard.js`; tests F23 **AC8-AC10**, F1 AC17-AC19 and F19 AC13-AC14 rerun (the row, filters, views).

- [ ] **Step 1: Failing tests.**  - [ ] **Step 2: The filter (route, repository, URL, views).**  - [ ] **Step 3: Bulk
  Add to Group.**  - [ ] **Step 4: The quick edit's picker.**  - [ ] **Step 5: Run and look.**  - [ ] **Step 6: Commit**
  — "Group products from the Dashboard, as Lasso does".

---

### Tasks 2X1-2X5: Corrections from the code audits (2026-11-14)

> Source: the code audits (`audit-1.md` sections 3 and 6, `audit-2.md` sections 1-3). Findings owned elsewhere are not
> repeated here: 13E's no-JavaScript filter → 13F; 14H's search over-match → 13G; the F19 AC4 test gap → 13H; 14C's drag
> reorder → FL5; the `check_duplicate_link` off mode → 9C; the Settings unsaved-changes guard → 9E; the 14E and 10D
> previews → Phase 3. Each task is small, outside-in, one commit. Gate 4: given 2026-11-14. Every task: its test first and red
> (or, for 2X2, a test that can be seen to fail), `composer lint`, `composer analyse`, unit tests, the check by hand,
> one commit, pushed.

---

### Task 2X0: PHP 8.3 and WordPress 7.1.4 everywhere

**Feature:** F37 (activation; the plugin's requirements) · **Decision:** F7 (the user, 2026-11-14)

**Why:** the user changed `affibloom.php`'s header to `Requires at least: 7.1.4` and `Requires PHP: 8.3` and the change
stands: **PHP 8.3 and WordPress 7.1.4 are the plugin's minimums** (production runs WP 7.1.4, PHP 8.3). The rest of the
plugin still says 8.1 and 6.5: the runtime check and its notice, Composer's platform, PHPCS's `testVersion`,
PHPStan's `phpVersion`, and the documents. This task makes them agree. It comes first in the phase because TL1 adds
the activation hooks to `affibloom.php`, which must lint clean first.

**Depends on:** nothing in this phase. **A step for the user before it starts:** switch the Local site's PHP to 8.3
(Local → the site → PHP version) and restart it; Local runs 8.2 today, so the plugin would not re-activate there.

**User action:** none in the admin; on a site below PHP 8.3 or WordPress 7.1.4 the plugin does not start and shows
"Affibloom needs PHP 8.3 and WordPress 7.1.4 or newer. It has not started." On Local (PHP 8.3) it activates as before.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the runtime check at `affibloom.php` 43 compares against `'8.3'` and `'7.1.4'`; the notice at line 37 says so | the plugin bootstrap |
| `composer.json`: `require.php` `>=8.3`, `config.platform.php` `8.3.0`; `phpcs.xml.dist`: `testVersion` `8.3-`, `minimum_wp_version` `7.1.4`; `phpstan.neon.dist`: `phpVersion: 80300` | Composer, PHPCS, PHPStan |
| nothing else new in `src/` | |

**Files:** modify `affibloom.php` (the user's header change is committed with this task; fix its 31 lint errors, 30 of
them by `composer lint:fix`, the last by hand), `composer.json`, `phpcs.xml.dist`, `phpstan.neon.dist`; update the
constraints in `docs/plans/00-overview.md`, the conventions line in `CLAUDE.md` and `docs/environment.md`
(Local's PHP version). Tests: a unit test for the check where it is testable (the comparison in a small function), or a
manual check recorded in the task summary if the bootstrap cannot be loaded in unit tests.

- [ ] **Step 1: Failing check.** `composer lint` on the user's `affibloom.php` shows the 31 errors (record the list);
  a test or a by-hand run shows the old check still lets PHP 8.1 or WordPress 6.9 through (expected red).
- [ ] **Step 2: Make the check and the notice say 8.3 and 7.1.4.** Set the Composer, PHPCS and PHPStan versions as in
  the table. Run `composer update --lock` only as `environment.md` allows (Known issues: `composer.lock` is committed
  only when made from Packagist).
- [ ] **Step 3: Fix the lint errors in `affibloom.php`.** `composer lint:fix` (30), then the last by hand; never
  suppress an error.
- [ ] **Step 4: Update the three documents** named above.
- [ ] **Step 5: Run and look.** `composer lint`, `composer analyse`, the unit tests; deactivate and re-activate the
  plugin on Local (PHP 8.3 after the user's switch): no notice, the Dashboard opens.
- [ ] **Step 6: Commit** — "Require PHP 8.3 and WordPress 7.1.4", committing the user's `affibloom.php` change with it.

---

### Task 2X1: Product page — the gaps a done task left

**Feature:** F1 (14B Step 3; 14H; 14C)

**Why:** the audits found three things ticked done that are not as specified.

**Depends on:** 14B, 14C, 14H (done).

**User action:** the Description editor shows "Enter a description" while empty; the Amazon **Refresh** button's
reason ("Needs the Amazon API") is announced to screen readers (visible text or `aria-describedby`, not a `title`
only); **Remove** on an added button asks through the design-system dialog (`delete-modal.php` pattern), not
`window.confirm`; a custom-field save or remove that fails shows the field error / toast and closes nothing
(today silent: `product-fields.js` 307-323, 386-389).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| placeholder for the TinyMCE description (editor `placeholder` setting via `ProductEditors`) | the product page |
| `aria-describedby` reason text on the disabled Refresh | the product page |
| a "Remove this button?" dialog (reuses the shared dialog partial) | `product-buttons.js` |
| error paths for PUT/DELETE in `product-fields.js` (FieldError + toast) | the field rows |

**Files:** `src/Interface/Admin/ProductEditors.php`, `templates/admin/partials/product-display-card.php`,
`templates/admin/partials/product-button.php`, `assets/admin/product-buttons.js`, `assets/admin/product-fields.js`;
tests `tests/E2E/acceptance/F1.spec.ts` (new cases for the four behaviours), unit test for `ProductEditors`.

- [ ] **Step 1: Failing tests** (placeholder visible; Refresh reason in the accessible name/description; Remove dialog;
  a field save answered 500 by a route stub shows an error).
- [ ] **Step 2: Build the four fixes.**
- [ ] **Step 3: Run and look** (1280 and 375 px, keyboard, screen reader names).
- [ ] **Step 4: Commit** — "Finish the product page's description, Refresh, button and field details".

---

### Task 2X2: The F1 AC2 test checks everything it claims

**Feature:** F1 (AC2)

**Why:** audit-2: AC2 does not re-check image, price, Show Price, description, disclosure or the buttons' links and
toggles after the reload. Tests only.

**Depends on:** 15A (done).

**User action:** none (tests only).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| nothing in `src/`; the AC2 case sets and re-reads every field 14A-14H show | `tests/E2E/acceptance/F1.spec.ts` |

**Files:** modify `tests/E2E/acceptance/F1.spec.ts`.

- [ ] **Step 1: The extended test.** Extend F1 AC2 to set and re-read every field 14A-14H show; run it against the
  built code (expected green; a red result is a real bug: fix it in this task and record a deviation). To show it can
  fail, break one field's save by hand (for example, drop the price from the form) and see the case go red, then
  restore it.
- [ ] **Step 2: Run and look.** Run the whole F1 file twice in a row; both runs pass and the library's order is the
  same after both.
- [ ] **Step 3: Commit** — "Check every product field after a save in F1 AC2".

---

### Task 2X3: Display colours accept every CSS colour Lasso accepts

**Feature:** F16 (edge case 7)

**Why:** `DisplaySettingsService.php` 229-246 refuses `rgb()`/`rgba()` and 8-digit hex, which Lasso's colour fields
store.

**Depends on:** 10A (done).

**User action:** typing `rgba(0,0,0,.5)` or `#00000080` in a Display colour saves and shows in the picker's text field
(the native picker shows the nearest opaque colour, the text value is kept).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| a wider `clean_colour()` (`rgb`, `rgba`, `hsl` only if Lasso's sanitiser accepts it, 8-digit hex) | `DisplaySettingsService` on save |
| the text value kept when the native picker cannot show the colour | `display-settings.js` |

**Files:** modify `src/Application/Display/DisplaySettingsService.php`, `assets/admin/display-settings.js`; tests
`tests/Unit/Application/Display/DisplaySettingsServiceTest.php`, `tests/E2E/acceptance/F16.spec.ts` (a typed `rgba()`
value saves and shows again).

- [ ] **Step 1: Failing unit tests** in `DisplaySettingsServiceTest` (rgb, rgba, hsl if Lasso accepts it — check
  `admin/views/settings/display.php` and Lasso's save sanitiser first and follow it exactly; 8-digit hex).
- [ ] **Step 2:** widen `clean_colour()`; `display-settings.js` keeps the text value when the picker cannot show it.
- [ ] **Step 3: Run and look.** Type both forms in a Display colour on Local, save, reload; F16's Display spec reruns.
- [ ] **Step 4: Commit** — "Accept every colour form Lasso's Display settings accept".

---

### Task 2X4: A kept redirect that ends at Amazon is an Amazon product

**Feature:** F2 (section 4)

**Why:** `ProductService.php` 376-380 types a kept redirect link (geni.us, a shortener the user keeps) that resolves to
Amazon as `other`; Lasso types it Amazon (`class-lasso-affiliate-link.php` 1705), which decides the Prime logo, tag
handling and C1.

**Depends on:** 11D, 12D (done).

**User action:** a kept redirect link that ends at an Amazon product page, added in Add A New Link, makes an Amazon
product (type Amazon, its ASIN set); the stored link is the one pasted.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the final URL's Amazon type and ASIN used for a kept redirect link (the branch at `ProductService.php` 376-380) | `ProductService::add()` |

**Files:** modify `src/Application/Products/ProductService.php`; tests `tests/Unit/Application/ProductServiceTest.php`;
the F2 and F18 acceptance specs rerun.

- [ ] **Step 1: Failing unit test** in `ProductServiceTest` (kept geni.us → Amazon final URL → type `amazon`, ASIN set,
  stored URL unchanged).
- [ ] **Step 2: Fix.**
- [ ] **Step 3: Run and look.** F2 and F18 acceptance rerun; by hand on Local with a stubbed redirect, add a kept link
  that ends at Amazon and see the Amazon product page (Amazon Image field, no Permalink row).
- [ ] **Step 4: Commit** — "Type a kept redirect to Amazon as an Amazon product, as Lasso does".

---

### Task 2X5: Bulk add never stalls on one bad link; "Continue Anyway"

**Feature:** F18 (bulk queue; "Continue Anyway")

**Why:** `BulkAddService.php` 116 skips only two exception types, so any other error stalls the run; and the
"Continue Anyway" path F18 lists (`admin.js` 386) is unbuilt.

**Depends on:** 11C (done). Read F18's requirements for the exact wording and when Lasso offers it before building:
the submit button "becomes a spinner while in flight; after a server error becomes **'Continue Anyway'** with
`data-continue=1`", and for a server error "the message in `.js-error`; the button becomes 'Continue Anyway'"
(F18 requirements, the add modal's element table and the section on errors).

**User action:** one failing link in a bulk run no longer stops the run: the other links are added and the failed one
is reported. After a server error in the Add A New Link modal the button reads "Continue Anyway".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| a catch around each link in `BulkAddService` (any exception from one link is recorded as that link's failure and the run goes on) | the bulk cron |
| the "Continue Anyway" state of the submit button after a server error (`data-continue=1`) | `admin.js` (the add modal) |

**Files:** modify `src/Application/Products/BulkAddService.php`, `assets/admin/admin.js`; tests
`tests/Unit/Application/BulkAddServiceTest.php`, F18 acceptance spec.

- [ ] **Step 1: Failing unit test** (a `RuntimeException` on link 3 of 5: links 4-5 still added, 3 reported).
- [ ] **Step 2:** catch per link, record the failure in the run; build "Continue Anyway" as F18 specifies.
- [ ] **Step 3: Run and look** (bulk add of 10 with one failing stub URL; the modal after a server error).
- [ ] **Step 4: Commit** — "Keep a bulk add going past a failing link, as Lasso does".

---

### Task 2X6: Brag mode leaves the Display tab

**Feature:** F16 (the Disclosure & behavior accordion; Task 10E built these controls) · **Decision:** B9 (the user,
2026-11-14)

**Why:** Lasso's "Brag" mode shows Lasso's own logo, linking to its affiliate sign-up: it only advertises Lasso. The
user decided that Affibloom leaves it out (and has no Lasso branding). Task 10E built **Enable Brag Mode** and **Brag
URL** on Settings → Display; they are removed, with their keys and tests. Task 22D (the brag icon in the box) is
dropped for the same reason.

**Depends on:** 10E (done).

**User action:** Settings → Display → Disclosure & behavior no longer shows "Enable Brag Mode" or "Brag mode URL"; the
rest of the accordion is unchanged; a stored `enable_brag_mode` or `brag_url` is ignored and dropped on the next save.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| nothing new: the two controls are removed from `templates/admin/partials/display-behaviour-accordions.php`; `enable_brag_mode` and `brag_url` are removed from `Settings::DEFAULTS` and from `SettingsService`'s cleaner (`'brag_url' => 'url'`, `'enable_brag_mode' => 'bool'`) | the Display form, `DisplayController` |

**Files:** modify `src/Domain/Settings/Settings.php`, `src/Application/Settings/SettingsService.php`,
`templates/admin/partials/display-behaviour-accordions.php` (and the comments that mention the brag icon),
`src/Interface/Admin/DisplayController.php` (its docblock); tests `tests/Unit/Application/SettingsServiceTest.php`
(remove `test_a_brag_url_must_be_a_link_or_empty`; add: saving the Display tab with the two keys stores neither),
`tests/E2E/acceptance/F16.spec.ts` (remove the two keys from the fixture; add: the controls are absent). F16's
requirements rows for brag are marked "not built: B9". Task 26D (Display migration) does not import the two keys.

- [ ] **Step 1: Failing tests.** The E2E case "the Display tab has no brag control" and the unit case "a save with
  `enable_brag_mode` and `brag_url` stores neither"; both red because the controls and keys exist.
- [ ] **Step 2: Remove the controls, the keys and the old tests.**
- [ ] **Step 3: Run and look.** Settings → Display at 1280 and 375 px: the accordion is intact; `composer lint`,
  `composer analyse`, the unit tests, F16's acceptance spec.
- [ ] **Step 4: Commit** — "Remove brag mode, which only advertised Lasso".

---

### Tasks 9B-9L: Settings → General, the full Lasso pass (F9)

> Written 2026-11-14 from a sweep of Lasso's General tab (`docs/features/F9/requirements.md`, R1-R69, AC1-AC16,
> D1-D13, N1-N7; analysis in `F9/analysis.md`). Order: 9B, 9E, 9C, 9D, 9F, 9G, 9H, 9I, 9K,
> 9L. **9J (the Link Index card) is in Phase 5**, after F21's status route. Each task adds its own keys to
> `Settings::DEFAULTS` and `SettingsService::FIELDS['general']`. Gate 4: given 2026-11-14. N1-N7 are answered (see "Decisions that change this phase"); the
> drafts follow the answers. Every task: outside-in, its acceptance test first and red, `composer lint`,
> `composer analyse`, unit tests, the check by hand at 1280 and 375 px, one commit, pushed.

---

### Task 9B: The General tab in Lasso's structure, and Link Defaults in full

**Feature:** F9 R1-R3, R30-R34, R40, R42, D2-D4; AC4, AC5 (AC1, AC3 stay green)

**Why:** the General tab still has Task 9's single card of four switches; Lasso's tab has two columns of cards and
eight Link Defaults. Every later section task adds a card to this layout.

**Depends on:** Task 9, 9A, 10F (`data-save-when-changed`, `switch-row.php`, help tips).

**User action:** an administrator opens Settings → General and sees two columns: left (Google Analytics, Cloaked Link
Prefix, Smart CSS Loading, Permissions, Uninstall — empty "coming with Task x" cards are not shown), right (Link
Index, Custom Link Detection, Performance, Link Defaults, Notifications — only built cards shown). Link Defaults shows
New Window / Tab, NoFollow / NoIndex, Sponsored, Show Disclosure, Allow duplicate destinations, Link Cloaking, Detect
Opportunities, Auto-Detect Affiliate Links with Lasso's tips. Save Changes, bottom right, is disabled until a switch
changes; saving reloads with "Settings saved.".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| keys `allow_duplicates`, `link_cloaking`, `detect_opportunities`, `auto_detect_links`, `new_tab2`, `nofollow2`; `sponsored` default true | `SettingsService`, the template; readers: 11B (`allow_duplicates`), F24, F22, F35, 11E |
| `templates/admin/partials/settings-general-link-defaults.php` | `settings.php` |
| two-column General layout in `settings.php` | `SettingsScreen` |

**Files:** modify `Settings.php`, `SettingsService.php`, `templates/admin/settings.php`, `admin.css`; create the
partial; tests `tests/Unit/Application/SettingsServiceTest.php`, `tests/E2E/acceptance/F9.spec.ts` (AC4, AC5).

- [ ] **Step 1:** Failing AC4 (two columns, Save disabled until a change) and AC5 (eight switches, order, fresh defaults).
- [ ] **Step 2:** Failing unit tests: new keys default as Lasso (all true), `sponsored` true; `allow_duplicates` saved.
- [ ] **Step 3:** Domain and service: keys and FIELDS.
- [ ] **Step 4:** Template: columns, Link Defaults card with switch rows and tips (Lasso's texts, "Lasso" removed).
- [ ] **Step 5:** 11B reads `allow_duplicates` (true = today's behaviour; false waits for 9C, until then treated as true and noted).
- [ ] **Step 6:** Run and look at 1280 and 375 px; lint, analyse, unit; F9 acceptance.
- [ ] **Step 7:** Commit `Give Settings → General Lasso's layout and all eight link defaults`.

---

### Task 9E: Unsaved-changes guard on every Settings tab

**Feature:** R44-R46, D8; AC8

**Why:** Lasso warns before losing General changes (U7 parity, open item in progress.md). The user decided (C7, 2026-11-14) that Display and Amazon carry the same guard: one `data-unsaved-guard` attribute on each form, in this task.

**Depends on:** 9B; the unsaved modal partial (15D).

**User action:** with a changed field, clicking any link opens "Unsaved Changes"; × stays, "Leave Without Saving"
goes, "Save Changes" saves and then goes; closing the tab triggers the browser prompt; unchanged → nothing asked.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `admin.js` guard for forms with `data-unsaved-guard` (baseline, compare, link intercept, beforeunload) | `settings.php`, and the Display and Amazon forms (decision C7) |
| hidden `redirect_to` + `SettingsController` honours it (`wp_validate_redirect`, admin URLs) | the guard's Save Changes |

**Files:** modify `assets/admin/admin.js`, `templates/admin/settings.php` (include `partials/unsaved-modal.php`),
`SettingsController.php`; tests: E2E AC8, unit for the redirect validation.

- [ ] **Step 1:** Failing AC8 (three paths).
- [ ] **Step 2:** Controller `redirect_to` with a failing unit test for a foreign host (falls back to the tab).
- [ ] **Step 3:** Guard script; extract the comparison shared with `product-save.js` if identical (second use).
- [ ] **Step 4:** Run and look; keyboard only (Escape = ×); the same three paths on the Display and Amazon tabs.
- [ ] **Step 5:** Commit `Warn before leaving a Settings tab with unsaved changes`.

---

### Task 9C: "Allow duplicate destinations" off

**Feature:** R35-R36, F18 D2 and section 5; AC7

**Why:** the setting's off mode was moved here from 11B (deviation 36).

**Depends on:** 9B, 11B, F18's `LinkResolver`.

**User action:** with the switch off, adding `https://merchant.example/p?ref=a` when a product points (finally) to
`https://merchant.example/p?ref=b` opens that product with "This product already exists. Please update the Primary URL
or add a new link."

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Product\PlainUrlKey` (final URL without query; Lasso's exceptions: Amazon search/no ASIN, `brooksbrothers.com`, `mechanicalkeyboards.com`) | `DuplicateLookup` |
| meta `_affibloom_url_plain_key` written on save of non-Amazon products while off | `ProductService` |
| off branch in `DuplicateLookup` | `ProductService::add()` |

**Files:** create `PlainUrlKey.php` + unit test; modify `DuplicateLookup`, `ProductService`, `PostTypeProductRepository`;
E2E AC7.

- [ ] **Step 1:** Failing AC7 and `PlainUrlKeyTest`.
- [ ] **Step 2:** Domain key, then lookup order (plain key, then pasted key), then the meta on save.
- [ ] **Step 3: Run and look.** On Local, setting off, two links to one page; back on afterwards.
- [ ] **Step 4:** Commit `Compare final destinations when duplicate destinations are not allowed`.

---

### Task 9D: "Update Link Settings": Only New Links / Override All Links

**Feature:** R47-R50, D7; AC6

**Why:** Lasso lets the owner push a changed default to every existing product.

**Depends on:** 9B; product meta of 11E/15.

**User action:** switching New Window / Tab, NoFollow / NoIndex, Sponsored or Show Disclosure opens the dialog
"Update Link Settings" — "Affibloom will enable the option New Window / Tab"; Only New Links closes; Override All
Links updates every product at once (toast "All products updated.") — the setting itself still needs Save.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `POST /affibloom/v1/settings/link-defaults/apply` (`Rest\SettingsRestController`) | the dialog |
| `Application\Settings\LinkDefaultsService::apply_to_all()` | the controller |
| `PostTypeProductRepository::set_flag_for_all()` | the service |
| `partials/update-link-settings-modal.php`, `general-settings.js` | `settings.php` |

**Files:** as listed; tests: unit service, integration repository (all statuses), E2E AC6. DB backup before the run.

- [ ] **Step 1:** Failing AC6 and unit test (unknown key → 422).
- [ ] **Step 2:** Repository, service, route, dialog.
- [ ] **Step 3: Run and look.** On Local, 82 test products, count meta before/after.
- [ ] **Step 4:** Commit `Offer to apply a changed link default to every product`.
(Link Cloaking and Detect Opportunities are added to the key map by F24's and F22's product-field tasks.)

---

### Task 9F: Google Analytics card

**Feature:** F9 (R10-R14, D10; AC9)

**Why:** Lasso's GA controls; C3 reads them. Decision C6 (the user, 2026-11-14): the four controls are built in full and Lasso's values are imported (Task 26C/26N); C3C loads gtag.js.

**Depends on:** 9B.

**User action:** Tracking ID field; the three switches are disabled while it is empty; emptying it turns all three
off; Pageview asks "Enable Pageview"; clicking the disabled Click Tracking shows the warning.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| keys `ga_tracking_id` (text, comma list), `ga_click_tracking`, `ga_pageview`, `ga_anonymize_ip` | the card; reader C3 |
| `partials/settings-general-analytics.php`, `ga-pageview-modal.php`, rules in `general-settings.js` | `settings.php` |

**Files:** modify `Settings.php`, `SettingsService.php`, `templates/admin/settings.php`; create the two partials and
`assets/admin/general-settings.js`; tests `SettingsServiceTest.php`, `tests/E2E/acceptance/F9.spec.ts` (AC9).

- [ ] **Step 1: Failing tests.** AC9; unit: the three switches are stored off when the ID is empty (the server rule
  mirrors the browser's).
- [ ] **Step 2: Keys and card** (`Settings::DEFAULTS`, `SettingsService::FIELDS['general']`, the partials).
- [ ] **Step 3: The script** (`general-settings.js`: switches disabled while the ID is empty, the Pageview dialog, the
  warning).
- [ ] **Step 4: Run and look** at 1280 and 375 px; F9 AC3 and AC9; the Lasso screenshot for fields, labels and order only.
- [ ] **Step 5: Commit** — "Add the Google Analytics settings".

---

### Task 9G: Cloaked Link Prefix and Smart CSS Loading

**Feature:** F9 (R16-R19, D9; AC10; behaviour: F24, F5; N4, N5)

**Why:** F24 builds cloaked links from the prefix and F5 loads the box CSS by the switch; both need the stored
values, cleaned the way Lasso cleans them.

**Depends on:** 9B.

**User action:** typing "Re Commends!" shows "re-commends-"; it is saved as `re-commends`; the example line shows the
prefix; one switch, "Enable Smart CSS Loading".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| keys `link_prefix` (`slug` cleaner = `sanitize_title`), `smart_css` (default **on** for a fresh install: decision C5; the migration switches it on) | the cards; readers F24, F5 |
| two partials, the keyup rule | `settings.php`, `general-settings.js` |

**Files:** modify `Settings.php`, `SettingsService.php` (the `slug` cleaner), `templates/admin/settings.php`,
`assets/admin/general-settings.js`; create the two partials; tests `SettingsServiceTest.php`,
`tests/E2E/acceptance/F9.spec.ts` (AC10).

- [ ] **Step 1: Failing tests.** AC10 and a unit test for the slug cleaner ("Re Commends!" → `re-commends`).
- [ ] **Step 2: Keys and cards.**
- [ ] **Step 3: The keyup rule** (the typed text becomes the slug form live; the example line follows).
- [ ] **Step 4: Run and look** at 1280 and 375 px; F9 AC3 and AC10.
- [ ] **Step 5: Commit** — "Add the cloaked link prefix and Smart CSS settings".

---

### Task 9H: Performance card

> Settled 2026-11-14: 9H/9I/9L build the controls and store them under F9 D3's keys; 35D/35G build only the effect (the scan reads those keys) and 9J the Link Index card's figures — no second card.

**Feature:** F9 (R22-R24, D13; AC11; behaviour F11)

**Why:** Lasso's Performance card sets how hard background work may run; F11's checks read the CPU threshold.

**Depends on:** 9B.

**User action:** Performance Mode select; Power, Balanced and Light set the CPU threshold to 80, 65 and 50 (and the
support fields); Custom shows the `%` field; loading the page never changes a stored value.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Settings\PerformanceMode` (presets) | `SettingsService` (choice), the template (data attributes) |
| keys `performance_mode`, `cpu_threshold` (1-100) | the card; reader F11 |

**Files:** create `PerformanceMode.php`, the card's partial (`partials/settings-general-performance.php`), tests
`PerformanceModeTest.php`; modify `Settings.php`, `SettingsService.php`, `settings.php`, `general-settings.js`;
`F9.spec.ts` (AC11).

- [ ] **Step 1: Failing tests.** AC11 and `PerformanceModeTest` (presets; load never rewrites values, D13).
- [ ] **Step 2: Domain, keys, service.**
- [ ] **Step 3: Card and script.**
- [ ] **Step 4: Run and look** at 1280 and 375 px; this site keeps Balanced + 80 after a save.
- [ ] **Step 5: Commit** — "Add the performance mode and CPU threshold".

---

### Task 9I: Custom Link Detection card

> Settled 2026-11-14: 9H/9I/9L build the controls and store them under F9 D3's keys; 35D/35G build only the effect (the scan reads those keys) and 9J the Link Index card's figures — no second card.

**Feature:** F9 (R25-R27; AC12; behaviour F21)

**Why:** Lasso scans the post types and ACF fields the owner chooses; F21 reads the choice.

**Depends on:** 9B; G3's group-picker component (a second use, if its chips fit a plain multi-select; otherwise the
card writes its own). See Cross-phase notes: F21's Task 35D builds the same card.

**User action:** "Custom Post Types:" multi-select of the site's other post types (never `post`, `page`, `wp_block`,
Lasso's excluded list, `affibloom_product`); "Custom Fields (ACF):" only with ACF; nothing chosen = the defaults.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Infrastructure\WordPress\WpGeneralChoices` (post types, ACF fields) | `SettingsScreen`, `SettingsService` validation |
| keys `scan_post_types`, `scan_custom_fields` | the card; reader F21 |
| a plain multi-select component (chips, no select2; the group picker of F23 D8, second use) | the card |

**Files:** create `WpGeneralChoices.php`, the card's partial (`partials/settings-general-detection.php`); modify
`Settings.php`, `SettingsService.php`, `settings.php`, `general-settings.js`; tests unit (an unknown type is refused),
`F9.spec.ts` (AC12).

- [ ] **Step 1: Failing tests.** AC12 and a unit test (unknown type refused).
- [ ] **Step 2: Choices, keys, service.**
- [ ] **Step 3: Card.**
- [ ] **Step 4: Run and look** (this site has no ACF, so that field is hidden); 1280 and 375 px.
- [ ] **Step 5: Commit** — "Add the custom link detection settings".

---

### Task 9K: Notifications card

**Feature:** F9 (R51-R54, D12; AC14; notices: F38)

**Why:** Lasso's Notifications card switches off its notices and help tips; F38's notices and `admin.js` read the keys.

**Depends on:** 9B.

**User action:** three switches ("Disable Configure Amazon Notification", "Disable Help Tooltips", "Disable Import
Notification"); "Disable Help Tooltips" hides the "?" tips on Affibloom screens after save.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| keys `disable_amazon_notice`, `disable_help_tips`, `disable_import_notice` | the card; readers F38, `admin.js` help tips (body class) |
| body class `affibloom-no-tips` from `AdminAssets` | the help-tip CSS and `admin.js` |

**Files:** create the card's partial (`partials/settings-general-notifications.php`); modify `Settings.php`,
`SettingsService.php`, `settings.php`, `AdminAssets.php`, `admin.css`; tests `F9.spec.ts` (AC14), a unit test of the keys.

- [ ] **Step 1: Failing tests.** AC14 (the tips hide after a save with the switch on).
- [ ] **Step 2: Keys and card.**
- [ ] **Step 3: The body class** from `AdminAssets`.
- [ ] **Step 4: Run and look** at 1280 and 375 px; keyboard and screen reader still reach help when tips are shown (U3).
- [ ] **Step 5: Commit** — "Add the notification settings".

---

### Task 9L: Support view

> Settled 2026-11-14: 9H/9I/9L build the controls and store them under F9 D3's keys; 35D/35G build only the effect (the scan reads those keys) and 9J the Link Index card's figures — no second card.

**Feature:** F9 (R55-R62, R66, D11; AC15; behaviours: F18/F1, F11, F24, F28, C1, F21)

**Why:** Lasso keeps maintenance settings behind `&support=1`; the other features read their values.

**Depends on:** 9B; F21's rebuild route (35G, Phase 5) for the Rebuild Database button only.

**User action:** `&support=1` adds Keep Original URL (textarea, one domain a line, `http(s)://` stripped, invalid
dropped), Cron "Time interval (hours)" 0-3, "Maximum … cronjob at a time." 1-3, Optional Settings (Restrict Prefix,
Processes Execute Limit, Font Awesome JS Render SVG, Disable AAWP Description), and "Rebuild Database" with "Ready to
rebuild?" (Cancel, Confirm → F21's rebuild route; "Working... Please be patient."). Without `support=1` none of it
shows.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| keys `keep_original_domains`, `cron_interval`, `manual_process_limit`, `restrict_prefix`, `process_limit`, `fontawesome_svg`, `no_feature_description` | the support cards; readers F18/F1, F11, F24, F28, C1 |
| `domains` cleaner, the ranges | `SettingsService` |
| `rebuild-index-modal.php` | the support Link Index card (route from F21) |

**Files:** create the support cards' partials and `rebuild-index-modal.php`; modify `Settings.php`,
`SettingsService.php`, `settings.php`, `general-settings.js`; tests `SettingsServiceTest.php` (cleaners, ranges),
`F9.spec.ts` (AC15).

- [ ] **Step 1: Failing tests.** AC15 and the cleaner and range unit tests.
- [ ] **Step 2: Keys, cleaners, ranges.**
- [ ] **Step 3: The cards.** Rebuild only when F21's route exists (else the button is not shown).
- [ ] **Step 4: Run and look** with and without `&support=1`; 1280 and 375 px.
- [ ] **Step 5: Commit** — "Add the support-only General settings".

---

### Tasks FL1-FL5: Fields (F26) — area 9 of the completion plan

> Written 2026-11-14 from a sweep of Lasso's Fields (`docs/features/F26/requirements.md`, gates 1-2; analysis text in
> `F26/analysis.md`). The product page's half is Task 14C (deviation 68); these tasks add Lasso's
> Fields screens and correct 14C's half where it differs from Lasso. Gate 4: given 2026-11-14. Every task: outside-in, its
> acceptance test first and red, `composer lint`, `composer analyse`, unit tests, the check by hand at 1280 and 375 px,
> one commit, pushed.

---

### Task FL1: The Fields list, with the whole field model

**Feature:** F26 (R1-R4, R7-R11, D1-D3, D13-D16; AC1)

**Why:** "Manage Fields" has said "Coming soon" since 14C; Lasso's list shows all ten of this site's fields, and the
library 14C built knows only three built-ins.

**Depends on:** Task 14C; Groups G1 if built (its in-place list helper is reused, else this task writes it).

**User action:** in a product's "Add Field" dialog, **Manage Fields** opens a new tab: "Fields", **Add New Field**
(leads to FL2's form; until FL2 it is "Coming soon"), "Search Fields", the table Name (description under it), Type
(icon), Usage, ten a page, every field (nine built-ins, the editor's own); search and pages redraw in place; no match →
"Looks like we're coming up empty."; a row opens the field's page (FL4 adds the Links tab; until then its Details tab,
FL3; until then the row is plain text).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `FieldType` cases `label`, `button`, `image`, `editor`, `product_name`; `choosable()`, `icon()` | `FieldLibrary`, the list row, `field-modal.php` (types offered) |
| `FieldLibrary::built_in()` with the nine built-ins (`table_only`, `order`), `names` renames read, `search()` with wildcards, `for_products()` | `FieldsService` |
| `OptionFieldRepository` reading `{fields, names}` and 14C's flat list | `FieldsService` |
| `FieldUsage`, `PostTypeProductRepository::field_usage()` | `FieldsService::list()` |
| `FieldsService::list()` | `FieldsScreen`, `GET /fields/list` |
| `Admin\FieldsScreen` (hidden page `affibloom-fields`), `templates/admin/fields.php`, partials `fields-list.php`, `field-list-row.php` | `AdminMenu` |
| `GET /affibloom/v1/fields/list` in `FieldsController`; `assets/admin/fields.js` (list in place) | the page |
| the Manage Fields link in `field-modal.php` (new tab) | the product page |

**Files:** create the above, `tests/E2E/acceptance/F26.spec.ts` (AC1), `tests/Unit/Domain/FieldLibraryTest.php`,
`tests/Unit/Infrastructure/OptionFieldRepositoryTest.php`; modify `src/Plugin.php`, `AdminMenu`, `AdminAssets`,
`FieldsController::library()` (uses `for_products()`), `templates/admin/partials/field-modal.php`, `admin.css`,
`docs/ui/design-system.md` (Fields list row).

- [ ] **Step 1: Failing tests.** AC1 (the ten rows, search "pri rat", the empty state, Manage Fields opens the list).
  Unit: built-ins with a rename, table-only out of `for_products()`, the flat option shape. Expected red: no page.
- [ ] **Step 2: Domain and repository.**
- [ ] **Step 3: Service, usage count.**
- [ ] **Step 4: Screen, route, templates, script.**
- [ ] **Step 5: Run and look.** F26 AC1, F1 AC10 (the library still lists Primary Rating, Pros, Cons and the editor's
  own, not the table built-ins); by hand at 1280/375 px.
- [ ] **Step 6: Commit** — "Add Lasso's Fields list with every built-in field".

---

### Task FL2: A new field from the Fields screen

**Feature:** F26 (R12 new-field header, R13, R15 create path, D6, D8, D18; AC2)

**Why:** Lasso's **Add New Field** is the screen's way to define a field without opening a product.

**Depends on:** FL1.

**User action:** **Add New Field** opens "Add a New Field": Field Title, Field Type (the seven, Short Text first),
Description, Save Changes; no tabs, no delete. Save shows "Updating Field"; success lands on the Fields list with
"Field saved."; an empty title → "Title is required." under it; over 80 characters → 14C's message; nothing stored.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Admin\FieldScreen` (hidden page `affibloom-field`, new-field mode), `templates/admin/field.php`, partial `field-form.php` | `AdminMenu`, FL1's button |
| `redirect_to` in `POST /fields`'s answer; the "Title is required." refusal (`Messages`: `field_title_required`) and the description limit | `fields.js`, the product dialog (same route) |
| `Notice` code `field-saved` | the redirect |
| the form's save in `fields.js` (save dialog, field errors from `admin.js`) | the page |

**Files:** the above; tests F26 **AC2**, unit tests for the title and description rules; `product-fields.js` shows
"Title is required." for the dialog's empty title (same message).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Rules and route.**
- [ ] **Step 3: Screen and form.**
- [ ] **Step 4: Run and look** (F1 AC10 rerun).
- [ ] **Step 5: Commit** — "Create a field from the Fields screen".

---

### Task FL3: A field's Details — edit and delete

**Feature:** F26 (R12-R19, D4-D5, D8, D19; AC3, AC4)

**Why:** fields are renamed and retired from their own page; Lasso's built-in titles are editable and its delete rule
never ran (R18).

**Depends on:** FL2.

**User action:** a field's page: its name, tab **Details** (Links comes with FL4). Built-in: Field Type and Description
locked, "Note: This is a built-in field and only the title is editable.", no delete. Own field: all three editable,
**Delete This Field**. Save Changes → "Updating Field" → "Field saved." (the product page and the list show the new
name). Delete: in use → "Hold Up" (Ok); unused → "Delete This Field?" → Delete → "Deleting Field" → the Fields list with
"Successfully deleted the Field.".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `PATCH /fields/{id}`, `DELETE /fields/{id}` | `fields.js` |
| `FieldsService::get(), update(), delete()`; `FieldNotFound`, `FieldInUse`, `FieldBuiltIn`; `FieldLibrary::rename(), update(), delete()` | the routes, `FieldScreen` |
| the Details tab in `field.php`; the shared `delete-modal.php` ("Delete This Field?") and a "Hold Up" panel | `FieldScreen` |
| `Notice` code `field-deleted` | the redirect |

**Files:** the above; tests F26 **AC3-AC4** (Hold Up with a product attached through WP-CLI; a direct DELETE → 409; a
built-in rename keeps type and description; a type change keeps a product's value), unit tests for the refusals.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Domain and service.**
- [ ] **Step 3: Routes.**
- [ ] **Step 4: The tab and the dialogs.**
- [ ] **Step 5: Run and look.**
- [ ] **Step 6: Commit** — "Edit and delete a field, never a built-in or one in use".

---

### Task FL4: A field's Links tab

**Feature:** F26 (R20, R21, D7; AC5)

**Why:** Lasso shows where a field is used, and the list's rows open there.

**Depends on:** FL3.

**User action:** a field opens on **Links `<n>`**: the published products that have it, ten a page — Image (50 px), Link
Name (to the product's page), Permalink (`/slug/`); none → "Looks like we're coming up empty."; the list rows (FL1) now
open this tab.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `FieldsService::products_using( id, page )`; `PostTypeProductRepository::products_with_field()` | `FieldScreen` |
| the Links tab in `field.php`, partial `field-product-row.php`, the pager | `FieldScreen` |

**Files:** the above; FL1's row link; tests F26 **AC5**.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Query and service.**
- [ ] **Step 3: The tab.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "List the products that use a field".

---

### Task FL5: The product page's field rows, corrected to Lasso's rules

**Feature:** F26, F1 (R24-R29, D9-D12; AC6, AC7) — the corrections to 14C

**Why:** 14C's rows keep blank and padded lines, search without wildcards, take any length and move only by buttons;
Lasso cleans lines, matches words, stops at 1,000 characters and drags.

**Depends on:** FL1 (wildcard search, renamed built-ins).

**User action:** on a product's page a value is stored with its lines trimmed and blank lines gone; a value over 1,000
characters is refused under its row ("Keep this value under 1,000 characters."); the library finds "pri rat"; rows can
be dragged (Primary Rating stays first) as well as moved by the buttons; the order holds after a reload.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Fields\FieldValue` (clean, limit) | `ProductFields::update()` |
| the 422 field error from `PUT /products/{id}/fields` | `product-fields.js` |
| drag with pointer events in `product-fields.js` (the move buttons' save) | the product page |

**Files:** the above; `ProductFieldsController::update()`; tests F26 **AC6-AC7**, F1 AC10 rerun, `FieldValueTest`.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: `FieldValue` and the route.**
- [ ] **Step 3: Drag.**
- [ ] **Step 4: Run and look** (keyboard and pointer, 375 px).
- [ ] **Step 5: Commit** — "Clean field values and
  drag field rows, as Lasso does".

---

### Tasks TL1-TL2: Activation and the upgrade routine (F37, moved here from Phase 6 on 2026-11-14)

> Placed where V1 stood (V1 moved to Phase 5): the first custom tables (`affibloom_amazon_products` in C1C, then `affibloom_url_variants` in V1, `affibloom_link_issues` in 30A and `affibloom_link_locations` in 35A) all add their schema step through TL2's `Migrator`.

---

**Coordination point (TL1):** TL1 needs `register_activation_hook` and `register_deactivation_hook` in
`affibloom.php`, which has the user's uncommitted edit (it also fails lint until the user settles it). Before TL1's
Step 2, ask the user how to proceed (commit their edit first, or add the two hook lines on top of it); never overwrite it.

### Task TL1: Activation and deactivation

**Feature:** F37 (R17, R20-R22, D1; AC1)

**Why:** Affibloom has no lifecycle hooks; its bulk-add cron (`affibloom_bulk_add`) survives deactivation today.

**Depends on:** nothing (`BulkAddCron::HOOK` is the first registered hook). Coordination: `affibloom.php` has the
user's uncommitted edit; see Cross-phase notes.

**User action:** `wp plugin deactivate affibloom` → no `affibloom_*` event in `wp cron event list`; `activate` → version
and activation time stored, rewrite rules flushed; products and settings untouched.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `register_activation_hook` / `register_deactivation_hook` in `affibloom.php` → `Plugin::activate()/deactivate()` (note: `affibloom.php` has the user's uncommitted edit — coordinate) | WordPress |
| `Application\Lifecycle` | `Plugin` |
| `Infrastructure\WordPress\CronRegistry` (hooks from each cron module) | `Lifecycle::deactivate()` |

**Files:** create `src/Application/Lifecycle.php`, `src/Infrastructure/WordPress/CronRegistry.php`,
`tests/Integration/LifecycleTest.php`; modify `affibloom.php`, `src/Plugin.php`, `src/Interface/Cron/BulkAddCron.php`.

- [ ] **Step 1: Failing integration test** (deactivate leaves no Affibloom cron hook; activate writes the version and the
  activation time and leaves products and settings alone). Expected red: no hooks are registered.
- [ ] **Step 2: Registry, Lifecycle, hooks** (`CronRegistry`, `Lifecycle`, the two hooks in `affibloom.php`, `Plugin`,
  `BulkAddCron` lists its hook).
- [ ] **Step 3: Run and look.** On Local with WP-CLI: `wp plugin deactivate affibloom`, `wp cron event list` shows no
  `affibloom_*` event; `wp plugin activate affibloom`, the options exist; products and settings unchanged.
- [ ] **Step 4: Commit:** "Clean up schedules on deactivation and record activation".

---

### Task TL2: Upgrade routine

**Feature:** F37 (R26, R27, R30 built (settings cache), D2; AC2)

**Why:** the first custom tables (C1C's `affibloom_amazon_products` in this phase, V1's `affibloom_url_variants` in Phase 5) need versioned schema steps; Lasso's lock and version gate.

**Depends on:** TL1. Built before the first table; the first real step is C1C's `affibloom_amazon_products` (this phase), then V1's (Phase 5), 35A's (Phase 5) and 30A's (Phase 6).

**User action:** after uploading a new zip, the first admin page runs pending steps once; `wp affibloom upgrade` does
the same.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Upgrader` (lock option, steps from `affibloom_db_version`) | `admin_init` (not AJAX/cron), `Lifecycle::activate()`, CLI |
| `Infrastructure\Schema\{Migrator,Table}` | `Upgrader`, TL4 (Phase 8) |
| version watch (flush rewrite on `AFFIBLOOM_VERSION` change) | `init` |
| `Interface\Cli\UpgradeCommand` | WP-CLI |

**Files:** create `src/Application/Upgrader.php`, `src/Infrastructure/Schema/Migrator.php`,
`src/Infrastructure/Schema/Table.php`, `src/Interface/Cli/UpgradeCommand.php`, `tests/Integration/UpgraderTest.php`;
modify `src/Application/Lifecycle.php`, `src/Plugin.php`.

- [ ] **Step 1: Failing integration tests** (lock: a second request skips and a stale lock older than 5 minutes is
  cleared; steps run in order; a failed step keeps the version and releases the lock). Expected red: no `Upgrader`.
- [ ] **Step 2: Schema classes** (`Migrator`, `Table`; no table yet, the tests use a fake step).
- [ ] **Step 3: `Upgrader`, the version watch and the hooks** (`admin_init`, `Lifecycle::activate()`, `init`).
- [ ] **Step 4: `wp affibloom upgrade`.**
- [ ] **Step 5: Run and look.** Change the stored `affibloom_db_version` on Local, open an admin page: the step runs
  once and the version is recorded; run `wp affibloom upgrade` and see "nothing to do".
- [ ] **Step 6: Commit:** "Run schema steps once after an update".

---

### Tasks V1-V5: moved to Phase 5

Tasks V1-V5 (URL variants, F27) moved to Phase 5 on 2026-11-14, right after CL11 and Task 26G, because they depend on F24 CL1 and CL3.

---

### Tasks 13F-13J: the Dashboard, second pass (F19)

> From the 2026-11-14 sweep (F19 requirements section 10) and audit-2 sections 3-4. Corrections of built tasks and gaps,
> run now. **13K (sorting) is in Phase 7**, after F11 31A, F12 34A and F21 36A. **13L (command palette) and 13M (tour)
> are dropped as duplicates of F38 ON9 and ON8** (Phase 8). Gate 4: given 2026-11-14; F19 N1-N3 are answered (decisions B10, D7, D8). R, D
> and AC numbers are F19's (section 10 for R30 on and D8 on). Every task: outside-in, its acceptance test first and
> red, `composer lint`, `composer analyse`, unit tests, the check by hand at 1280 and 375 px, one commit, pushed.

---

### Task 13F: The list keeps every argument without JavaScript, and `?post_id=`

**Feature:** F19 (R30, R31; D17, D24; AC15) — corrects 13E (audit-2: link type lost without JavaScript)

**Why:** deviation 53 promised the page still works without JavaScript; the search form (dashboard.php 33-35) and
the size form (pager.php 33-37) drop `link_type`, and the Filters panel cannot even be opened without a script. Every
later filter (tab, group, placements, table, sort) will add an argument, so the forms must carry "all the others".

**Depends on:** 13D, 13E (built).

**User action:** with JavaScript off the Filters button is hidden and the panel shows open with **Apply** under Link
type; choosing Amazon and Apply reloads with `link_type=amazon`; a search or a new page size then keeps it; the
pills and page links work as links. `admin.php?page=affibloom&post_id=123` lists product 123 only (or the empty
search message when it is not a published product). With JavaScript on nothing changes.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `templates/admin/partials/list-args.php` (hidden inputs for every list argument except the ones named) | the search form, the size form, the filters form |
| `<form method="get">` around the Link type select, `<button class="affibloom-no-js">Apply</button>` | `filters-panel.php` |
| `.no-js` rules (panel shown, toggle hidden, Apply shown) | `admin.css` |
| `post_id` in `DashboardScreen`, the list route, `DashboardUrl`, `ProductRepository::page()` (`p` arg) | the address |

**Files:** create `templates/admin/partials/list-args.php`; modify `templates/admin/dashboard.php`,
`partials/{pager,filters-panel}.php`, `src/Interface/Admin/{DashboardScreen,DashboardUrl}.php`,
`src/Interface/Rest/DashboardController.php`, `PostTypeProductRepository.php`, `ProductService.php`,
`assets/admin/admin.css`; tests `F19.spec.ts` AC15 (a `javaScriptEnabled: false` context), `DashboardUrlTest`.

- [ ] **Step 1: Failing test.** AC15 with JavaScript off: Apply missing → fails for the right reason.
- [ ] **Step 2: Hidden arguments.** `list-args.php` takes the list and the names to leave out; the three forms use it.
- [ ] **Step 3: The panel without a script.** The GET form, Apply, `.no-js` CSS (wp-admin swaps `no-js` for `js`).
- [ ] **Step 4: `post_id`.** Screen, route (`integer`, minimum 0), `DashboardUrl`, repository (`p` + publish).
- [ ] **Step 5: Run and look** with JavaScript off and on, 1280 and 375 px; F19 AC12-AC14 still pass.
- [ ] **Step 6: Commit** — "Keep the Dashboard's filters without JavaScript".

---

### Task 13G: The search finds added buttons' links, not their texts

**Feature:** F19 (D4; AC16) — corrects 14H (audit-2: the arm over-matches)

**Why:** the search arm `meta_value LIKE` on the serialised `_affibloom_buttons` (PostTypeProductRepository.php 511)
also matches button texts and the PHP keys `url`/`text`/`new_tab`/`nofollow`, so "Buy Now" or "url" lists every
product with an added button. Lasso's arm is the second button's link only (`second_btn_url`).

**Depends on:** 14H (built), 15A (save path).

**User action:** searching a word that is only in an added button's link finds the product; searching "Buy Now" or
"url" no longer lists products for having added buttons.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| post meta `_affibloom_button_url`, one row per added button's URL, rewritten by `save()` | the search arm |
| `Buttons::urls()` (the added buttons' URLs, non-empty, in order) | `PostTypeProductRepository::save()` |
| the arm's key list `_affibloom_url`, `_affibloom_asin`, `_affibloom_button_url` | `search_filter()` |

**Files:** modify `src/Domain/Product/Buttons.php`, `PostTypeProductRepository.php`, `tools/seed-from-lasso.php`
(writes through `save()`; re-run on Local); tests `ButtonsTest` (`urls()`), `F19.spec.ts` AC16.

- [ ] **Step 1: Failing test.** AC16: a product with an added button "Buy Now"; searching "Buy Now" finds it → fails.
- [ ] **Step 2: Domain.** `Buttons::urls()` with its unit test.
- [ ] **Step 3: Storage and the arm.** `save()` deletes and adds the rows; the arm reads them; `_affibloom_buttons`
  leaves the IN list.
- [ ] **Step 4: Run and look.** Re-run the seed on Local; search a second button's link (60 live records have one).
- [ ] **Step 5: Commit** — "Search only the links of a product's added buttons".

---

### Task 13H: F19 AC4 and AC10 tests that run on a full library

**Feature:** F19 (AC4, AC10) — corrects the AC4 test gap (audit-2) and AC10's side effect

**Why:** AC4's test skips whenever the library holds more than 50 products (`F19.spec.ts` 74), which is always on the
seeded copy, so "a one-page list shows no page links" is never checked. AC10's test republishes every product with
`wp post update`, which rewrites every `post_modified` and reorders the library for every later test.

**Depends on:** 13A, 13B (built).

**User action:** none (tests only). The suite checks AC4 on this copy: a search matching exactly three new products,
at 10 per page, shows no page links and `1-3 of 3`. AC10 parks and restores the products without touching their
modified time.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| AC4 built on a unique search term (three products made for it) | `F19.spec.ts` |
| `parkLibrary()` / `restoreLibrary()` helpers using `wp eval` with `$wpdb->update( posts, post_status )` and `clean_post_cache()` | AC10 |

**Files:** modify `tests/E2E/acceptance/F19.spec.ts` (and its helper file if the helpers are shared).

- [ ] **Step 1: The new AC4** runs on this copy and passes on the built code (a test-only task: it must fail when the
  pager's single-page rule is broken — check by hand with `per_page` forced to 1 and expect the failure).
- [ ] **Step 2: AC10's park/restore** keeps every `post_modified` (compare a sample before and after).
- [ ] **Step 3: Run and look.** Run the whole F19 file twice in a row; the order of the library is the same after both.
- [ ] **Step 4: Commit** — "Check the one-page Dashboard on a full library".

---

### Task 13I: A stable order, and counts that follow the list

**Feature:** F19 (R19, R33; D9, D18; AC17, AC18) — closes the audit's stale tile/placeholder after a delete

**Why:** Lasso's order has no second key and this copy has products sharing a modified time, so a product can show on
two pages or on none. And Lasso refreshes its counts with every list answer; Affibloom's Products tile and the
`Search all <n> links…` placeholder stay stale after a bulk delete, a revive or a quick edit until a reload.

**Depends on:** 13D, 17B (built).

**User action:** paging through products that share a modified time shows each once; after **Delete** in the
selection bar the Products tile and the placeholder show the new number at once.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the last sort key `ID DESC` in `PostTypeProductRepository::page()` (`orderby` array `modified DESC, ID DESC`) | the list |
| `library` in the `GET /dashboard/products` answer (`ProductService::count()`) | `dashboard.js` |
| `counts( library )` in `dashboard.js`: the tile's number (`number_format` via `Intl.NumberFormat`) and the placeholder from a `data-placeholder` template (`Search all %s links…`) | `load()` |

**Files:** modify `PostTypeProductRepository.php`, `DashboardController.php`, `templates/admin/dashboard.php`
(`data-placeholder`), `partials/dashboard-tiles.php` (a hook on the Products number), `assets/admin/dashboard.js`;
tests `F19.spec.ts` AC17, AC18; `tests/Integration/ProductListTest.php` (ties).

- [ ] **Step 1: Failing tests.** AC17 (three products with one modified time across a page boundary at 10 per
  page, set with `wp eval`), AC18 (delete one, the tile drops by one without a reload).
- [ ] **Step 2: The order.** `ID DESC` after every key.
- [ ] **Step 3: The counts.** `library` in the answer; the script writes the tile and the placeholder after each load.
- [ ] **Step 4: Run and look** (delete, revive through 17D, quick edit; the numbers change; screen reader hears the
  range only).
- [ ] **Step 5: Commit** — "Keep the Dashboard's order stable and its counts current".

---

### Task 13J: Saved views and the Filters panel behave as Lasso's

**Feature:** F19 (R12-R17; D13-D16; AC19, AC20)

**Why:** 13E built the views and the panel but not Lasso's resets (the active pill clicked again, the active view
deleted), its messages ("View saved.", "View deleted.", "Name your view first."), the error for a missing view, or
the panel remembering that it was left open.

**Depends on:** 13E, 16B (guard), 13I (the list answer's counts, so a reset redraws them).

**User action:** clicking the marked pill again, or deleting the marked view, brings back All links (empty search,
All types, 10 per page, page 1). **Save** shows "View saved."; × then **Delete filter** shows "View deleted."; a
failed delete shows the server's message or "Could not delete." and keeps the pill; **Save** with an empty name says
"Name your view first." under the field and sends nothing. The panel opened with the Filters button is open again
after a reload; closed with × it stays closed; Escape and an outside click close it for this visit only.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `reset()` in `dashboard.js` (13E's Reset filters body, extracted on its second and third use) | Reset filters, the active pill, deleting the active view |
| toasts through `affibloomToast` (admin.js) with Lasso's words | save and delete |
| `ViewNotFound` from `SavedViewService::delete()`; 404 `affibloom_not_found` "Saved view not found." | `DashboardController::delete_view()` |
| `localStorage.affibloom_filters_open` read on load, written by the button and × (`try`/`catch`) | `openPanel()` |

**Files:** modify `assets/admin/dashboard.js`, `src/Application/Dashboard/SavedViewService.php`,
`src/Interface/Rest/DashboardController.php`, `src/Interface/Messages.php` (the five texts),
`templates/admin/partials/filters-panel.php` (data texts); tests `SavedViewServiceTest` (missing id), `F19.spec.ts`
AC19, AC20, AC14 rerun.

- [ ] **Step 1: Failing tests.** AC19 and AC20 fail on the built code (the pill reloads the same view; the panel
  opens closed).
- [ ] **Step 2: The route.** Unknown id → 404 with Lasso's text; unit test.
- [ ] **Step 3: The resets and messages** (through `guarded()`; the empty name checked before any request).
- [ ] **Step 4: The panel's memory.**
- [ ] **Step 5: Run and look** (keyboard only; toasts announced; 375 px).
- [ ] **Step 6: Commit** — "Reset and confirm saved views as Lasso does".

---

### Tasks ON1-ON2: Admin shell, navigation and notices (F38)

> Written 2026-11-14 from a sweep of Lasso 369's admin shell (`docs/features/F38/requirements.md`; analysis in `F38/analysis.md`). Only ON1 and ON2 are in this phase: ON3 and ON4 are in Phase 5, ON5-ON9 in
> Phase 8, and ON10 is dropped as a duplicate of 9E. Gate 4: given 2026-11-14. N1-N7 (requirements section 4) are answered
> (decisions D13, B1, B2, B3, B1, D12, B4); the drafts follow the answers. Every task: outside-in, its acceptance test first and red,
> `composer lint`, `composer analyse`, unit tests, the check by hand at 1280 and 375 px, one commit, pushed.

---

### Task ON1: Navigation in Lasso's order — header, WordPress menu, titles, footer

**Feature:** F38 (R1-R4, R6, R10-R14, R16, R77; D1-D7, D21; AC1-AC3)

**Why:** every later screen adds a tab and a menu entry; they need one ordered list to join, so the header and the
menu never drift from Lasso's order.

**Depends on:** the header, `AdminMenu` (built). Nothing else.

**Order note (assembly):** Groups (G1) and Fields (FL1) are built earlier in this phase and add their tab and menu
entries by hand; this task moves them into `AdminNavigation` (Lasso's order) and registers nothing else.

**User action:** on the Dashboard, a product page and Settings the header shows Dashboard and Settings (later tabs
slot in between as their screens arrive), the current group marked; under 992 px a **Menu** button folds the tabs.
The WordPress menu shows **Affibloom** after Pages (position 20) with Dashboard, Settings; on a product page
"Dashboard" is highlighted; the browser tab reads "Link Details ‹ …". Every page ends with "Version <x.y.z>".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Admin\AdminNavigation` (Lasso's order: dashboard, opportunities, tables, groups, settings for tabs; dashboard, opportunities, tables, groups, fields, import, settings, history for the menu; `register( NavItem )`, `tabs( current )`, `menu()`) and `NavItem` | `AdminMenu`, `header.php`, each screen's task |
| `AdminMenu` changes: position 20, submenu from `AdminNavigation::menu()`, `parent_file` / `submenu_file` filters, `admin_title` ("Link Details") | WordPress |
| `templates/admin/partials/footer.php` ("Version %s") | every screen template |
| Menu button in `header.php` + `admin.js` toggle (`aria-expanded`, `aria-controls`) | the header |

**Files:** create `src/Interface/Admin/{AdminNavigation,NavItem}.php`, `templates/admin/partials/footer.php`,
`tests/E2E/acceptance/F38.spec.ts` (AC1-AC3), `tests/Unit/Interface/AdminNavigationTest.php`; modify
`src/Interface/Admin/AdminMenu.php`, `src/Plugin.php`, `templates/admin/partials/header.php`, the four screen
templates, `assets/admin/{admin.js,admin.css}`, `docs/ui/design-system.md` (Menu button, footer).

- [ ] **Step 1: Failing tests.** AC1 (tabs and current state on Dashboard, product page, Settings tabs; Menu button
  at 375 px), AC2 (menu position after Pages, order, highlight on a product page, titles), AC3 (footer text). Unit:
  order, current group per page slug. Expected red: menu at 26, no footer.
- [ ] **Step 2: `AdminNavigation` and `NavItem`;** the Dashboard and Settings items registered in `Plugin`.
- [ ] **Step 3: `AdminMenu`, header, footer, titles.**
- [ ] **Step 4: Run and look.** F38 AC1-AC3 and F19/F1 specs (header changed); by hand 1280/375.
- [ ] **Step 5: Commit** — "Order the header and menu as Lasso's, add the footer version".

---

### Task ON2: The notice region, foreign notices, and the update notice

**Feature:** F38 (R15, R17-R22, R24, R39; D8-D12; AC4-AC6)

**Why:** C1, F11, F21 and F33 each have notices to show on every page; they need one place and one rule for
conditions and dismissal. The update notice is the region's first caller.

**Depends on:** ON1.

**User action:** another plugin's admin notice no longer appears above Affibloom's screens. When WordPress lists an
Affibloom update, every Affibloom page shows "There's a new version available. **Update now**." under the header;
the link starts WordPress's update. A script can add a warning there (Settings → General's GA warning, C3).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Admin\ForeignNotices` (Module: `admin_head` 99999 and `in_admin_header`, Affibloom pages only, core's admin bar kept) | `Plugin` |
| `Application\Shell\{NoticeProvider, NoticeBoard}`, `Domain\Shell\ShellNotice` | every screen's render |
| `Application\Shell\UpdateAvailableNotice`, `Infrastructure\WordPress\PluginUpdates` | `NoticeBoard` |
| `templates/admin/partials/notices.php` (region `#affibloom-notices`) and a `<template>` for script notices; `affibloomNotice( tone, message )` in `admin.js` (with the fallback "An unexpected error has occurred please try again later.") | screen templates; feature scripts |

**Files:** create the classes and partial; tests `tests/Unit/Application/Shell/{NoticeBoardTest,
UpdateAvailableNoticeTest}.php`, `tests/Integration/ForeignNoticesTest.php`, F38 AC4-AC6; modify `src/Plugin.php`,
screen templates, `assets/admin/admin.js`, `docs/ui/design-system.md` (Notice tones).

- [ ] **Step 1: Failing tests.** AC4 (a test plugin's `admin_notices` hidden on the Dashboard, shown on Posts), AC5
  (a script notice), AC6 (a faked `update_plugins` transient). Unit: a throwing provider is skipped.
- [ ] **Step 2: Domain and application.**
- [ ] **Step 3: Module, partial, script API.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Add the notice region and hide other plugins' notices".

---

### Tasks C1A-C1P: Amazon product data (C1)

> Written 2026-11-14 from a sweep of Lasso 369's Amazon subsystem (`docs/features/C1/requirements.md`; analysis in
> `docs/features/C1/analysis.md`). C1 is an upgrade: every task that needs the Creators API ships dormant and says
> so on screen. Gate 4: given 2026-11-14. C1E-C1H are in Phases 3 and 7 (see Cross-phase notes). Every task: outside-in, acceptance test first and red, `composer lint`, `composer
> analyse`, unit tests, by hand at 1280 and 375 px, one commit, pushed. Tests never call Amazon: recorded fixtures,
> the E2E stub mu-plugin (`AFFIBLOOM_TEST_AMAZON_STUB`).

---

### Task C1A: The Product Data API card and Validate

**Feature:** C1 (R16-R25, D2-D4, D16; AC3, AC5, AC7 card, AC8) · **Without credentials:** the card works; Validate needs Amazon (stub in tests)

**Why:** every other C1 task reads these credentials; Lasso's card is the place they are entered and checked.

**Depends on:** Task 10F (Amazon tab, `SettingsController`), F2 12A (`default_country` reader).

**User action:** Settings → Amazon, left column under Amazon Associates Accounts: "Amazon Product Data API" with the
state ("Not set up" / "Saved, not validated" / "Validated"), Default Tracking ID (Lasso's 18 countries, help "Select
your Amazon Associates locale."), the intro, Credential ID and Secret (empty, "Saved — leave blank to keep" when set),
Partner tag (`yourtag-20`), Version (`3.1`), **Validate**. Validate: spinner, then R20-R21's messages; success saves
and shows "Validated". Save Changes validates unverified credentials (R23 wording, no licence sentence).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Amazon\{Credentials, CredentialsState, Marketplace}` | the service, C1B |
| `Application\Amazon\{AmazonCatalog (token only for now), CredentialsService}` | the controller, the settings save |
| `Infrastructure\Http\CreatorsApiCatalog::token()`, `WordPress\OptionCredentialsRepository` | the service |
| `POST /amazon/validate` in `Rest\AmazonController` | `amazon-settings.js` |
| `partials/amazon-api-card.php`; `default_country` in `Settings` | `settings-amazon.php` |

**Files:** create the above, `tests/Unit/Domain/Amazon/{MarketplaceTest,CredentialsTest}.php`,
`tests/Unit/Application/CredentialsServiceTest.php`, `tests/E2E/mu/amazon-stub.php`, `tests/fixtures/amazon/token*.json`;
modify `Plugin.php`, `SettingsController.php`, `templates/admin/settings-amazon.php`, `assets/admin/amazon-settings.js`,
`tests/E2E/acceptance/C1.spec.ts` (AC3, AC5, AC7 card, AC8), `docs/ui/design-system.md` (state chip).

- [ ] **Step 1: Failing tests.** AC8's four messages and the state chip; AC5 (secret never in the source, REST, export);
  AC3 (nothing else saved). Expected red: no card.
- [ ] **Step 2: Domain and repository** (signature, missing fields, token endpoints by Version).
- [ ] **Step 3: Token client, service, route.**
- [ ] **Step 4: Card and script.**
- [ ] **Step 5: Run and look.**
- [ ] **Step 6: Commit** — "Add the Amazon Product Data API card and Validate, as Lasso has them".

---

### Task C1B: The catalogue client

**Feature:** C1 (R1, R7-R15, D1, D6; AC9) · **Without credentials:** dormant (the command says "Not set up")

**Why:** add, Refresh, the job, bulk sync and search all read one client; a WP-CLI command is its first caller.

**Depends on:** C1A.

**User action:** `wp affibloom amazon get B00JMB3FHS [--country=gb]` prints title, image, price, savings, availability,
features, or `NotFound: Product not found.` / `AuthFailed: …`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `CreatorsApiCatalog::item()` (getItems, R9; normalise R11; availability R12; errors R13) | `AmazonCommand`, later C1C-C1I |
| `Domain\Amazon\{Asin, CatalogItem, Availability}`, `Application\Amazon\CatalogError` | the client |
| `Interface\Cli\AmazonCommand` | WP-CLI |

**Files:** the above; `tests/fixtures/amazon/getItems-{ok,empty,oos}.json`, `error-{401,429}.json`;
`tests/Unit/Infrastructure/CreatorsApiCatalogTest.php`, `AvailabilityTest.php`; `Plugin.php`.

- [ ] **Step 1: Failing tests** (unit against fixtures; AC9 through WP-CLI with the stub).
- [ ] **Step 2: Client and value objects.**
- [ ] **Step 3: Command.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Fetch an Amazon item through the Creators API, behind wp affibloom amazon get".

---

### Task C1C: Filling a new Amazon product on add

**Feature:** C1 (R26-R28, R30, R32, R34, D5, D7; AC1, AC2) · **Without credentials:** today's fallback (F2 section 6)

**Why:** Lasso names and pictures a new Amazon product from its data.

**Depends on:** C1B; F18 11A; F2 12A-12E.

**Schema (see Cross-phase notes):** the table's schema step needs the versioned upgrade routine, which the map places
in Phase 2 now (TL2, before this task; settled 2026-11-14).

**User action:** Add A New Link with an Amazon link or ASIN: with credentials the product page opens with Amazon's
title and image link; without, or when Amazon fails, as today.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `{prefix}affibloom_amazon_products` (db version bump), `TableAmazonProductRepository` (upsert R27 with country) | the service |
| `Domain\Amazon\AmazonProduct`, `Application\Amazon\AmazonDataService::fill()` | `ProductService::add()` |

**Files:** the above; `ProductService.php`, `Plugin.php`, schema install; `C1.spec.ts` AC1 (un-fixme), AC2;
`tests/Unit/Application/AmazonDataServiceTest.php`.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Table and repository.**
- [ ] **Step 3: fill() in add.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Fill a new Amazon product's name and image from Amazon".

---

### Task C1D: Refresh on the product page

**Feature:** C1 (R35, R36, D7; AC4, AC7 page) · **Without credentials:** disabled, "Needs the Amazon API" (exists)

**Why:** Lasso's Refresh beside "Amazon Image" fetches a new image and price.

**Depends on:** C1C; F1 14B.

**User action:** Refresh → spinner → the image link, preview and read-only price update; a placeholder name is
replaced; failure shows R35's message.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `POST /products/{id}/amazon/refresh`, `AmazonDataService::refresh()` | `product.js` |

**Files:** `AmazonController.php`, `AmazonDataService.php`, `templates/admin/partials/product-display-card.php`,
`assets/admin/product.js`; `C1.spec.ts` AC4 (un-fixme), AC7 page.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Route and service.**
- [ ] **Step 3: The button.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Refresh an Amazon product's image and price from its page".

---

### Task C1I: Bulk Amazon sync

**Feature:** C1 (R40-R45, D12; AC7 banner, AC14) · **Without credentials:** banner with the count, button disabled, "Needs the Amazon API"

**Why:** Lasso's banner fills products that never got data (3 here).

**Depends on:** C1C.

**Order note (assembly):** the banner is a notice; it uses ON2's notice region (earlier in this phase).

**User action:** banner on Affibloom pages; Sync Amazon data; progress; summary; × per user until the set changes.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `BulkSyncService`, `AmazonBulkSyncCron`, routes `GET/POST /amazon/bulk-sync`, `POST …/dismiss` | `amazon-bulk-sync.js` |
| `partials/amazon-bulk-sync.php`, `Admin\AmazonNotices` | admin pages |

**Files:** the above; `C1.spec.ts` AC14.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Pending query and service.**
- [ ] **Step 3: Cron and routes.**
- [ ] **Step 4: Banner and script.**
- [ ] **Step 5: Run and look.**
- [ ] **Step 6: Commit** — "Sync Amazon data for products that have none".

---

### Task C1J: Tracking-ID behaviours — no code

No task to build (one line): the tag rules are 12A, the whitelist and blacklist 10F, the product-page notice and
`lasso_save_amazon_tracking_id` 14D, auto-whitelisting comes with content auto-monetization (C2); Lasso's per-country
tracking-ID table is unprinted UI (R58, D15); C1A adds the partner tag.

---

### Task C1K: Keep Geni.us Formatting on add and save

**Feature:** C1 (R62, D14; AC17) · **Without credentials:** works

**Why:** with the switch off, Lasso stores a geni.us link as its destination; Affibloom's switch (10F) must do the same.

**Depends on:** F18 11A, F1 15A, 11D's fetcher, Task 10F.

**User action:** with "Keep Geni.us Formatting" off, a pasted or saved `geni.us` link is stored as its destination (the
redirect is followed on the site), and the original is kept in `_affibloom_old_genius_url` (and `…_url2` for the second
button).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Products\GeniusLinks::resolve()` in `LinkResolver` | add and save |
| meta `_affibloom_old_genius_url`, `_affibloom_old_genius_url2` | `ProductService` |

**Files:** modify `LinkResolver.php`, `ProductService.php`; tests a unit test, `C1.spec.ts` AC17 (stubbed redirect).

- [ ] **Step 1: Failing tests** (unit; AC17 with a stubbed redirect).
- [ ] **Step 2: Resolver.**
- [ ] **Step 3: Run and look** (add and save a stubbed geni.us link with the switch off, then on).
- [ ] **Step 4: Commit** — "Store geni.us links as their destination when the setting says so".

---

### Task C1L: The cloud-only switches labelled

**Feature:** C1 (R64-R66, N1; AC18) · **Without credentials:** works

**Why:** Mobile Deep Linking, Send Readers to Their Local Store and Commission Upgrades worked only through Lasso's
cloud; they are kept and labelled so nobody expects an effect.

**Depends on:** Task 10F (the Amazon tab). The note's wording follows C1 N1's answer.

**User action:** under each of the three switches the Amazon tab says it worked through Lasso's cloud and has no effect
in Affibloom; the stored values still show.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| a note under each of the three switches | `settings-amazon.php` |

**Files:** modify `templates/admin/settings-amazon.php`; `C1.spec.ts` AC18.

- [ ] **Step 1: Failing test** (AC18: the three notes).
- [ ] **Step 2: The notes** (per N1's answer).
- [ ] **Step 3: Run and look** at 1280 and 375 px.
- [ ] **Step 4: Commit** — "Say which Amazon switches needed Lasso's cloud".

---

### Task C1M: The Amazon search tab in Add A New Link

**Feature:** C1 (R68-R71, D13; F18 D5 and section 9; AC7 tab, AC15) · **Without credentials:** the tab shows "Needs the Amazon API"

**Why:** Lasso's Add A New Link has an Amazon (Marketplace) tab that needs Lasso's cloud; F18 D5 replaces it with a
search through the Creators API.

**Depends on:** C1B, C1C; F18 11A (the modal).

**User action:** Add A New Link → Amazon tab: search keywords, see title, image and price, add one or up to ten selected
products (AC15); without credentials the tab says "Needs the Amazon API".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `AmazonCatalog::search()` (`searchItems`, ten a page) | `GET /amazon/search` |
| `GET /amazon/search`; `selections` in `POST /products` | `add-modal.php`, `admin.js` |
| the tab in `add-modal.php` and `admin.js` | the modal |

**Files:** the above; `searchItems.json` fixture, `C1.spec.ts` AC15, F18 spec rerun.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Search client and route.**
- [ ] **Step 3: Tab, selection, add.**
- [ ] **Step 4: Run and look** at 1280 and 375 px, with the E2E stub.
- [ ] **Step 5: Commit** — "Search Amazon from Add A New Link".

---

### Task C1N: The SiteStripe notice

**Feature:** C1 (R72, R73-R74 recorded; AC19) · **Without credentials:** works

**Why:** Lasso tells owners migrating from Amazon SiteStripe to turn on Auto Amazon; the notice is kept in words.

**Depends on:** C1I (`Admin\AmazonNotices`); ON2's notice region.

**User action:** "Migrating from Amazon SiteStripe? Turn on Auto Amazon …" shows while Auto-Monetization is off, until it
is dismissed (×); its link goes to the Amazon tab; the dismissal is site-wide.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `partials/sitestripe-notice.php` in `AmazonNotices` | the admin pages |
| `POST /notices/sitestripe/dismiss`; option `affibloom_sitestripe_notice` | the notice's × |

**Files:** the above; `C1.spec.ts` AC19.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Notice and route.**
- [ ] **Step 3: Run and look** at 1280 and 375 px.
- [ ] **Step 4: Commit** — "Show Lasso's SiteStripe notice".

---

### Task C1O: Amazon data when the primary URL changes

**Feature:** C1 (R33, D7, F17 "Keep Title When URL Is Replaced"; AC16) · **Without credentials:** title rule only

**Why:** Lasso takes the new Amazon product's image, and its name unless "Keep Title When URL Is Replaced" is on, when a
product's link changes ASIN.

**Depends on:** C1C; F1 15A and 15B.

**User action:** changing a product's link to another ASIN takes the new product's image, and its name only when "Keep
Title When URL Is Replaced" is off (AC16); without credentials only the title rule applies.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `AmazonDataService::onLinkChanged()` | `ProductService::update()` |

**Files:** modify `ProductService.php`, `AmazonDataService.php`; a unit test; `C1.spec.ts` AC16.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Rule.**
- [ ] **Step 3: Run and look** (change a stubbed product's ASIN with the setting on and off).
- [ ] **Step 4: Commit** — "Take the new Amazon product's data when a link changes".

---

### Task C1P: The geni.us clean-up job

**Feature:** C1 (R63, D14; AC20) · **Without credentials:** works

**Why:** turning "Keep Geni.us Formatting" off in Lasso queues a job that re-saves every product whose link is a
geni.us link; Affibloom does the same.

**Depends on:** C1K; Task 10F.

**User action:** turning "Keep Geni.us Formatting" off and saving queues the job that re-saves every geni.us product
as its destination, ten a run; turning it on clears the job (AC20).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `GeniusCleanupCron` (`affibloom_genius_cleanup`, chained, ten a run) | WP-Cron |
| queued by `SettingsController` when the switch goes off, cleared when it goes on | the Amazon tab's save |

**Files:** the above; `C1.spec.ts` AC20.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Job.**
- [ ] **Step 3: Run and look** (`wp cron event run`, on Local with test products).
- [ ] **Step 4: Commit** — "Remove geni.us formatting from products when the switch is off".

---

## Exit check

Phase 2 can no longer end on "all F1, F2, F3 and F9 acceptance tests pass": F9 AC2 ("Box links carry `rel` and
`target`") needs the box, which is Phase 3. This phase ends when **all of the following pass on Local** (against the
Amazon stub where C1 needs Amazon) and **the user signs it off**:

- **F1:** AC1-AC23 (AC2 as re-checked by 2X2). The live preview (F1 B2-B4, Task 14E) moves to Phase 3.
- **F2:** AC1-AC8. **F3:** AC1-AC2. **F18:** AC1-AC11.
- **F16 (what Settings → Display stores and shows):** AC1, AC4, AC7, AC8, AC11 (without the brag controls: 2X6). Moves to Phase 3: AC2 (the preview,
  Task 10D) and the front-end halves of AC3, AC5, AC6, AC8, AC9, AC10 and AC13 (they need the box). AC12 (settings
  migrated from Lasso) moves to Phase 5 (Task 26D).
- **F9:** AC1, AC3-AC12, AC14, AC15 (what is stored and shown). Moves: AC2 to Phase 3 (Task 18's box); the
  behaviour behind AC10 to Phase 5 (F24) and Phase 3 (F5), behind AC11 to Phase 6 (F11), behind AC12 to Phase 5
  (F21), the notices behind AC14 to Phases 7-8 (F38); AC13 (Link Index card, 9J) to Phase 5; AC16 (Uninstall card,
  F37 TL5) to Phase 8.
- **F19:** AC1-AC20. Moves: AC21-AC23 (sorting, 13K) to Phase 7; AC24-AC25 (command palette, ON9) and AC26 (tour,
  ON8) to Phase 8.
- **F23:** AC1-AC10. **F26:** AC1-AC7.
- **F27:** moved to Phase 5 with V1-V5 (AC1-AC8).
- **F37 (part):** AC1 (activate and deactivate: TL1) and AC2 (the upgrade runs once after an update: TL2) pass.
- **F38:** AC1-AC6. Moves: AC7 (ON3) and AC8 (ON4) to Phase 5; AC9 (ON5), AC10-AC11 (ON6-ON7), AC12 (ON8) and AC13
  (ON9) to Phase 8; AC14 (unsaved changes on Settings) is F9 AC8, 9E.
- **C1:** AC1-AC5, AC7 (card, page, banner, tab), AC8, AC9, AC14-AC20. Moves: AC6 and AC10 (C1E) to Phase 7, AC11
  (C1F) and AC12 (C1G) to Phase 3, AC13 (C1H) to Phase 7.
- The unit tests, `composer lint` and `composer analyse` of every task above pass.
- **Sign-off:** the user runs the Dashboard, the product page, Groups, Fields, Settings → General, Display
  and Amazon on Local and signs off, as Phases 0 and 1 were. Phase 3 does not start before that.

## Coverage

Every requirement or acceptance criterion the tasks of this phase own. Rows not built in this phase say where they
go.

| Feature | R / AC | Task |
|---|---|---|
| decision F7 | PHP 8.3 and WordPress 7.1.4 minimums | 2X0 |
| audits | 14B placeholder, Refresh reason; 14H Remove dialog; 14C silent failures | 2X1 |
| F1 | AC2 | 2X2 |
| F16 | edge case 7 (colours) | 2X3 |
| F2 | section 4 (kept redirect) | 2X4 |
| F18 | bulk queue exceptions; "Continue Anyway" | 2X5 |
| F16 | R (Enable Brag Mode, Brag URL) | not built: decision B9; 2X6 removes the controls 10E built |
| audits | smaller items (`Density::from_array`, `Layout::phone_effective()`, `AmazonUrl::COUNTRIES`, `disclosure_text`, the tracking-ID rule written three times) | recorded in `progress.md`; fixed in the task that next touches the file (18H/18I, C1A, 22B, 9B) |
| F23 | R1-R4, AC1 | G1 |
| F23 | R5-R7, AC2 | G1 (new group), G2 (existing) |
| F23 | R8-R11, AC3 | G2 (R10: the Delete button on the Links tab, G4) |
| F23 | R12-R14, R16, AC4 | G3 |
| F23 | R17-R20, AC5-AC7 | G4 |
| F23 | R15, R21-R23, AC8-AC10 | G5 |
| F23 | R24 | Phase 3 (group displays), Phase 4 (editor) |
| F9 | R1-R3, R30-R34, R40, R42, AC4, AC5 | 9B |
| F9 | R44-R46, AC8 | 9E |
| F9 | R35-R36, AC7 | 9C (R35 built in 11B) |
| F9 | R47-R50, AC6 | 9D (cloaking and opportunity keys: F24, F22 tasks) |
| F9 | R10-R14, AC9 | 9F; R15 → C3 |
| F9 | R16-R19, AC10 | 9G; behaviour F24, F5 |
| F9 | R22-R24, AC11 | 9H; R24 behaviour F11 |
| F9 | R25-R27, AC12 | 9I; behaviour F21 |
| F9 | R51-R54, AC14 | 9K; notices F38 |
| F9 | R55-R62, R66, AC15 | 9L |
| F9 | R20-R21, AC13 | 9J (Phase 5) |
| F9 | R41 | built (Task 9, 10F); D5 |
| F9 | R43 | 9I (`cpt_support` fallback), 9L (domains, clamp) |
| F9 | R37-R39 | F24, F22, F35/C2 |
| F9 | R4-R7, R59 | not built: decision B1 (cloud) |
| F9 | R8, R9 | built path |
| F9 | R63-R65, AC16 | F37 TL5 (Phase 8; decision A8) |
| F9 | R67 | F37 TL6 (Phase 8; decision C3: built) |
| F9 | R68 | F34 (Phase 5) |
| F9 | R69 | not copied (D12) |
| F9 | AC1 | built (Task 9, 11E) |
| F9 | AC2 | Task 18 (Phase 3) |
| F9 | AC3 | built; each 9x task extends it |
| F26 | R1-R4, R7-R11, AC1 | FL1 |
| F26 | R12 (new), R13, R15 (create), AC2 | FL2 |
| F26 | R12 (existing), R14, R15 (update), R16-R19, AC3, AC4 | FL3 |
| F26 | R20, R21, AC5 | FL4 |
| F26 | R22-R29, AC6, AC7 | FL5 (R22-R23, R25, R28-R29 are 14C's, rechecked there) |
| F26 | R5, R6 | FL5 (rows' rules; shape is 14C's) and Task 26 (migration, Phase 5) |
| F26 | R30, R31 | FL1-FL5, split by route: create FL2, update and delete FL3, product rows FL5 |
| F26 | R32-R34, R36 | Phase 3 (box, schema) |
| F26 | R35 | F25 (Phase 7) |
| F26 | R37, R38 | F4 (Phase 4) |
| F26 | R39 | not built (D17) |
| F27 | R1, R2, R4-R9, R17, R23; AC1, AC2 | V1 (Phase 5) |
| F27 | R3, R10-R16, R24, R25; AC3-AC5 | V2 (Phase 5) |
| F27 | R18; AC6 | V3 (Phase 5) |
| F27 | R19; AC7 | V4 (Phase 5) |
| F27 | R20; AC8 | V5 (Phase 5) |
| F37 | R17, R20-R22, AC1 | TL1 (R21 not copied: changes plugin order; R20's snippet: C) |
| F37 | R26-R27, AC2 | TL2 |
| F27 | R21 | no task: Lasso has no such matching (D8) |
| F27 | R22 | no task: unused code in Lasso |
| F27 | the copy of Lasso's rows | Task 26H (Phase 5) |
| F19 | AC1-AC3, AC5-AC9 | built 13A/13B |
| F19 | AC4, AC10 (tests) | 13H |
| F19 | AC11 / AC12 / AC13-AC14 | built 13C+17B / 13D / 13E |
| F19 | AC15 / AC16 / AC17-AC18 / AC19-AC20 | 13F / 13G / 13I / 13J |
| F19 | AC21-AC23; R1-R4, R6 (modified and detection_date arms), R7 (D10), R8, R10, R11 (sort part), R5 allowlist; D8-D12 | 13K (Phase 7) |
| F19 | R6 count arm / clicks arm | F21 36A / F12 34A (Phase 7) |
| F19 | R5's `opportunity_count` and notice, R9's Opportunities part, R40's Opportunities tab | F22 O8 (Phase 7; R5's notice to confirm there) |
| F19 | R9 tab resets Alert's direction | F11 31B (Phase 6) |
| F19 | R12-R16 | built 13E; gaps 13J |
| F19 | R17 | 13J |
| F19 | R18 | built 13E (+ G5, 36D, F25 T16 for their filters) |
| F19 | R19 | 13I (Products, placeholder); F11 31B (counters) |
| F19 | R20-R27, AC24-AC25, D19-D22 | F38 ON9 (Phase 8; 13L dropped) |
| F19 | R28, AC26, D23 | F38 ON8 (Phase 8; 13M dropped); the Chat step R29: not built (N1) |
| F19 | R30, D17, D24 | 13F |
| F19 | R31, R34, R35 | built 13D / 13D / 13A |
| F19 | R32 | not built (licence); F21 36A shows the real count |
| F19 | R33, D18, D9 | 13I |
| F19 | R36 / R37 | built 17B / built 17B + F21 36E |
| F19 | R38 / R39 / R40 | F31 18S10 / F11 31D / F11 31B |
| F38 | R1-R4, R6, R10-R13, R16, R14, R77, AC1-AC3 | ON1 (R14, R77: nothing to build, N2, D21) |
| F38 | R15, R17-R22, R24, R39, AC4-AC6 | ON2 |
| F38 | R23 | C1, F11, F33: their notices join ON2's region |
| F38 | R25, R26, AC8 | ON4 (Phase 5) |
| F38 | R40-R43, AC7 | ON3 (Phase 5) |
| F38 | R29-R36, AC9 | ON5 (Phase 8) |
| F38 | R45-R52, AC10; R5 | ON6 (Phase 8) |
| F38 | R53-R58, AC11 | ON7 (Phase 8) |
| F38 | R59-R64, AC12 | ON8 (Phase 8) |
| F38 | R65-R72, AC13 | ON9 (Phase 8) |
| F38 | R38, R73-R75, AC14 | 9E (ON10 dropped as a duplicate) |
| F38 | R37 | F9 9K and U3 (built help tips) |
| F38 | R44 | F11 |
| F38 | R76 | F19 |
| F38 | R7, R9; R8; R27-R28 | not built: decisions B2 (R7, R9), B1 (R8), B3 (R27-R28) |
| C1 | R1-R2, R16-R25, AC3, AC5, AC8, AC7 (card) | C1A |
| C1 | R7-R15, AC9 | C1B (R7-R8's token part built in C1A) |
| C1 | R26-R30, R32, R34, AC1, AC2 | C1C |
| C1 | R31, R35, R36, AC4, AC7 (page) | C1D |
| C1 | R37-R39, AC6, AC10 | C1E (Phase 7) |
| C1 | R48-R53, AC11 | C1F (Phase 3) |
| C1 | R46-R47, AC12 | C1G (Phase 3) |
| C1 | R55-R57, AC13 | C1H (Phase 7) |
| C1 | R40-R45, AC14, AC7 (banner) | C1I |
| C1 | R58-R59, R60-R61 | C1J (owned by 12A, 10F, 14D, link rewriting) |
| C1 | R62, AC17 | C1K |
| C1 | R64-R66, AC18 | C1L |
| C1 | R68-R71, AC15, AC7 (tab) | C1M |
| C1 | R72-R74, AC19 | C1N |
| C1 | R33, AC16 | C1O |
| C1 | R63, AC20 | C1P |
| C1 | R3-R6, R65, R70 (Lasso's cloud) | not built (cloud; never) |
| C1 | R54, R67, R75, R76 | homes elsewhere: import (Phase 5), link rewriting and auto-monetization (Phase 7), the wizard (F38 ON7, Phase 8, whose draft does not name C1A's card partial) |

**UNOWNED:** none. Items with a recorded decision (not unowned): F9 N2 (9J's "Start Link Indexing": never applies, decision A8), F9 N7 (9E's scope), F26 N1 (field values), C1 N1-N4 and the F38 and F19 questions are decided (see "Decisions that change this phase"); the
UNRESOLVED items are in Cross-phase notes.
