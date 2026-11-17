# Affibloom — Phase 4: Editors — Implementation Plan

> **How to run this plan:** inline, in one working session, task by task, in the order below. Do not hand tasks to subagents. When a step is finished, change its `- [ ]` to `- [x]`.
>
> **Explain as you go:** before each task, say in two or three sentences what the task builds, why it is needed now, and how it will be checked. After the task, say what changed and what the verification showed. No vibe coding: every line written must be one you can explain.
>
> **Shared rules:** the architecture, working method, decisions, global constraints, file structure and API contract are in [`00-overview.md`](00-overview.md). Read it first.

## Status

Planned; gate 4 given by the user 2026-11-14 for every open task. The decisions N1-N6 of F4 are answered (N1 restores the chooser: 23A; N2 registers the legacy block: ED6; N3 accepted: ED5; N4 leaves the seventh tile out; N5 and N6: MCP1 dropped). The tasks of the old Phase 4 (23, 24) are in
[`archive/phase-4-superseded.md`](archive/phase-4-superseded.md).

## Scope and features

- **F4 Displays in the editor** (block, display modal, Classic editor, Elementor, Rank Math, AMP, core-block link
  attributes, MCP placeholder, dropped): `wp-content/plugins/affibloom/docs/features/F4/{requirements,analysis}.md`. The drafts
  were written 2026-11-14 from a sweep of Lasso 369's editor subsystem and replace the thin Task 6 version.

## Decisions that change this phase

Decided 2026-11-12 (Task 6; reasons in `docs/decisions/task-6-decisions.md` in the plugin repository) or since. Apply
them when the task is worked, and record any code that differs from the text below as a deviation in `progress.md`.

- **U1 (Task 6, 2026-11-12) is withdrawn (the user, 2026-11-14, C2):** Lasso's "Choose a Display Type" step is
  restored in the block; the Single-only start is not built (Task 23A builds the chooser; AC1's test gains the Single
  click).
- **ADR-8 (the user, 2026-11-12): Affibloom's own admin design.** The block's editor UI (the display modal, the
  sidebar panels, their notices) uses the Affibloom design system (`docs/ui/design-system.md`, Task 9A), built on
  `@wordpress/components` where they fit, not Lasso's modal styling. Labels (`Search URLs`, `Add`, "Choose a Display
  Type", …) and the steps stay as the requirements say. The **display the block renders** is not affected: it keeps
  the site's display design and must still match the Lasso baselines (ADR-6).
- **Full-scope rule (2026-11-13):** the block serves all seven of Lasso's display types; types whose renderer is not
  built yet are shown disabled with "Coming soon" in the chooser, so 23A can ship first.
- **Content rule:** no task in this phase writes `post_content`; the MCP insert route is not built (decision A10).
- **Decided (the user, 2026-11-14), C2:** U1 is withdrawn; Lasso's "Choose a Display Type" is restored (Task 23A).
- **Decided (the user, 2026-11-14), A1:** Affibloom registers the legacy block name `affiliate-plugin/lasso` and edits those blocks in place (Task ED6, moved to Phase 5); nothing is converted; ADR-003 revised; Tasks 28A-28E dropped.
- **Decided (the user accepted the recommendation, 2026-11-14):** keep Lasso's link-attribute filters for core image and button blocks, so 147 posts open
  without invalid-block notices (F4 N3, recommended: build ED5, editor-only, no content rewritten).
- **Decided (the user accepted the recommendation, 2026-11-14), B5:** the AI Text tile is left out of the chooser (seven types; no post uses `[lasso prompt]`).
- **Decided (the user, 2026-11-14), A10:** no MCP routes are built; Task MCP1 is dropped.
- **Decided (the user, 2026-11-14), A10:** Elementor `_elementor_data` rewriting is not built.
- **Decided (the user, 2026-11-16), deviation 142: Meadow replaces Aurora.** Tasks MD1-MD7 restyle the admin before
  F4's tasks start; the block's editor UI is then built in Meadow (`docs/ui/design-system.md` section 0b). Where an open
  task in any phase says "Aurora" (look, dialog, tile), read "the design system (Meadow)".

## Tasks in this phase

### Meadow restyle — runs first (the user, 2026-11-16; deviation 142)

- [x] Task MD1: Meadow's fonts and colours
- [x] Task MD2: The white header and notice cards
- [x] Task MD3: A page head instead of the band
- [x] Task MD4: The Dashboard: counts beside the title, search, list
- [x] Task MD5: The product page: head, sticky preview, save bar
- [x] Task MD6: Settings, Groups, Fields, dialogs and the toast
- [x] Task MD7: The design system rewritten for Meadow

### F4 — Displays in the editor

- [x] Task 23A: The block and "Choose a Display Type"
- [x] Task 23B: "Choose a Link" — product search
- [x] Task 23C: Insert Single, Button and Image; stored markup; the published page
- [x] Task 23D: Grid, List and Gallery — "Choose a Group"
- [x] Task 23E: Create Group and "Add to Group" from the modal
- Task 23F: Table — "Choose a Table" — **moved to Phase 7** (after F25 T12; settled 2026-11-14)
- [x] Task 23G: Create a Link and the quick detail
- [x] Task 24A: Customize Display sidebar
- [x] Task 24B: Schema & Structured Data panel
- [x] Task 24C: Shortcode box and Update Display
- [x] Task 24D: Edit Display
- [x] Task 24E: Preview queue on load
- [x] Task ED1: Classic editor button
- [ ] Task ED2: Elementor widget
- [ ] Task ED3: Rank Math content analysis
- [ ] Task ED4: AMP inline styles
- [ ] Task ED5: Keep Lasso's link attributes valid in core blocks (N3 accepted)
- Task ED6: moved to Phase 5, right after Task 27A (on 2026-11-14; it depends on 27A)
- Task MCP1: dropped (decision A10)

## Cross-phase notes

Names below use the phase map. Earlier phases are built before this one; later phases are not, so those parts are
stubbed or deferred as said.

**Earlier phases (named, available):**

- **Single renderer:** Tasks 18A-18K (the box, themes, layout, classic rendering), 19A (buttons), 20A-20D (per-box
  overrides and product structured data), 21A-21C, 22A-22E. 23C, 24A and 24B use them.
- **Shortcode `[affibloom]`:** Tasks 18S1-18S7 (parser, overrides, button/image, link, groups); 18S9 (widgets, AMP,
  feeds) overlaps ED4, see that task; 18S14 (review schema in the page head) is the JSON-LD output (R48) that 24B's
  panel drives. **18S8 (table by shortcode) is superseded by F25 T12** (phase map).
- **Other display types:** F30 DT2/DT3 (Grid), DT4 (List), DT5 (Image), DT6 (Gallery), DT7 (Button). 23C and 23D
  depend on them; a type whose DT task is not built stays "Coming soon".
- **Groups (Phase 2):** F23 G1 (`GET /groups`, `POST /groups`), G4 (`GET /products?search=` with `in_group`,
  `GET|POST|DELETE /groups/{id}/products`). 23B uses G4's product route; the route may be first built in 23B if G4
  has not made it.
- **Add A New Link (Phase 2):** F18 Tasks 11A-11E (`POST /affibloom/v1/products`, Task 11). 23G builds the editor
  variant of that dialog on the same route (F18 D6).
- **Product page (Phase 2):** F1 Tasks 14A-14C (form partials, display card, custom fields), 15A (`PATCH
  /products/{id}`). 23G reuses the form partials in a modal.
- **Settings (Phase 2):** 9D "Update Link Settings / Override All Links" owns the override modal of R85; not F4.
- **Design system:** Task 9A.

**Later phases (what waits, and how it is stubbed):**

- **F25 Tables, Phase 7 (T1 `GET /tables`, T2 create, T3 the table page, T12 the reader's horizontal table).** 23F
  (Table, `GET /tables`, **Create Table** link, the table renderer) and the table half of 24D (Edit Display opens
  `table-details`) wait for them. Until T1/T12 exist the chooser's **Table** tile is disabled with "Coming soon"; 23F's
  test is written and marked skipped, and 23F is closed after F25 T1/T2/T12, in Phase 7. 18S8 is superseded by T12.
- **Task ED6 moved to Phase 5 (2026-11-14), right after 27A.** It depends on 27A (the `[lasso]` alias) for "render
  unchanged on the site", and 27A is in Phase 5, a later phase than this one. Its editor half and its "renders
  unchanged" half (AC21) are both worked there. ED6 is built (decision A1).
- **Phase 5 28A-28E (conversion):** dropped (decision A1, 2026-11-14); ED6 (now in Phase 5) is built and ADR-003 is revised.
- **F24 CL8, CL9 (Phase 5):** core link search of products (R59), in the block editor and the classic link dialog.
  Not F4; named so it is not thought missing.
- **F38 ON* command palette (Phase 8):** R58 (Ctrl/Cmd+Shift+L on post editors). Not built here.
- **F25 T15, T16, F19:** nothing here depends on them.

**UNRESOLVED:** none. Every "Depends on" name below exists in the phase map. Two naming points (gate 4 given; follow them
as said), not missing tasks: the drafts call the shortcode "Task 18S" and the renderer "Task 18A" (read as 18S1-18S7 and
18A-18K); and F4 requirements text says "Tasks 23A-23H", while the map and the drafts end at 23G (the table of
coverage below is complete with 23A-23G).

---

### Tasks MD1-MD7: the Meadow restyle

> Written 2026-11-16 from `docs/ui/design-system.md` section 0b (the spec), the reference prototype
> (https://claude.ai/artifact/VKuvkUctK992ethcke8ZMR) and a read of the admin code. **Only the look changes:** no
> label, help text, field, `name`, id, tab or column order, workflow step, PHP logic or REST route changes. **Class
> names stay** where tests, scripts or later tasks use them (design system section 8, `.affibloom-tile*`,
> `.affibloom-page__title`, `.affibloom-page__subtitle`, `.affibloom-tabs__tab`, `#affibloom-save`, `#affibloom-search`);
> new classes are added beside them. Section 0 holds: Settings → Display keeps Lasso's structure.
>
> **Test approach (ADR-8):** the acceptance specs check fields, labels and order, not looks, so they need no change;
> `tests/E2E/admin-design.spec.ts` is the one look spec, and each task rewrites the part of it that the task changes
> (written, not run: batches rule 5). A task that moves markup greps the specs for `boundingBox` and layout checks on
> its screen and updates only those. **Order:** MD1 changes token values, so every screen turns Meadow's colours at
> once; the header and band look odd for one task until MD2 and MD3 replace them.

#### Task MD1: Meadow's fonts and colours

**Decision:** deviation 142; design system 0b.1-0b.3, 0b.5.

**Why:** every later task builds on the tokens. Changing their values first restyles every screen with no markup
change, and the bundled fonts must exist before the type scale can use them.

**Depends on:** Phase 3 signed off. Nothing else.

**User action:** any Affibloom screen shows Meadow's palette (green brand, green-tinted greys), Inter for text and Plus
Jakarta Sans for titles and big numbers; primary buttons and switches are flat green; cards have a hairline border.
The header and the band are still Aurora's shapes (MD2, MD3).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `assets/admin/fonts/` with five WOFF2 files (Inter 400, 500, 600; Plus Jakarta Sans 700, 800; Latin subset) and both families' `OFL.txt` | `fonts.css` |
| `assets/admin/fonts.css` (`@font-face`, `font-display: swap`; tokens `--affibloom-font` = Inter + the system stack, `--affibloom-font-display` = Plus Jakarta Sans + Inter + the system stack) | `AdminAssets` (style `affibloom-fonts`, a dependency of `affibloom-admin`) |
| Meadow's token values and new tokens in `admin.css` section 1 (table in 0b.1); flat primary button and switch; card border plus shadow; radii; focus ring and field ring; display face on page titles, card titles and tile numbers | every admin screen |

**Files:** create the fonts, `assets/admin/fonts.css`, `tests/Unit/Interface/Admin/AdminAssetsTest.php`; modify
`src/Interface/Admin/AdminAssets.php`, `assets/admin/admin.css` (sections 1, 4, 6, 8 and the type rules),
`tests/E2E/admin-design.spec.ts` (its colour constants and the token and type checks).

**Font files:** downloading them needs the user's OK first (name, source, size; global rules). Source: the families'
own releases (Inter: `rsms/inter` on GitHub; Plus Jakarta Sans: `tokotype/PlusJakartaSans`) or the Fontsource packages
of the same, Latin subset only, about 120 KB in all. They are copied into the plugin: no request to any other host
at run time, no npm dependency added.

- [x] **Step 1: Tests.** Unit: `AdminAssets::enqueue()` registers `affibloom-fonts` on an Affibloom screen and not on
  another admin page, and `affibloom-admin` depends on it. E2E (`admin-design.spec.ts`): page background `#F5F7F4`,
  the body font family starts with Inter, the page title's with Plus Jakarta Sans, the primary button flat
  `#1E6B47` with white text, a switch on `#1E6B47` and off `#8A968C`, a card's 1px border, the focus outline
  `#1E6B47` on a switch reached by Tab.
- [x] **Step 2: Fonts.** The files, `fonts.css`, the enqueue.
- [x] **Step 3: Tokens and components by value.** Section 1's values; retire `--affibloom-brand-500` (flat buttons
  and switches); keep the navy and band tokens until MD2/MD3 remove their last users.
- [x] **Step 4: Check and look.** Unit tests, `phpcs` on `AdminAssets.php`; Dashboard and Settings → General at
  1280 px.
- [x] **Step 5: Commit** — "Bundle Meadow's fonts and switch the admin to Meadow's colours".

*Note (2026-11-17):* `fonts.css` holds only the `@font-face` rules; the font tokens (`--affibloom-font`,
`--affibloom-font-display`, `--affibloom-font-system`) sit with the others in `admin.css` section 1. Files from
Fontsource 5.3.0 (the user approved the download). Seen at 1280 px: the Dashboard's table has a fixed 1076px
`min-width`, so its Actions column scrolls inside the card at 1280 px (not new); MD4 fixes it.

#### Task MD2: The white header and notice cards

**Decision:** deviation 142; design system 0b.4 (Header, Notices, Footer).

**Why:** the header is on every screen and the navy notice strip continues it; both go together.

**Depends on:** MD1.

**User action:** every Affibloom screen opens with a white 64px header: the flower mark in green and the wordmark,
the tabs as words (no icons) with a green underline on the current one, **Add New Link** as a green split button.
Below 992 px the Menu button opens a white panel with the tabs stacked. Notices (the update notice, the bulk Amazon
sync banner, script notices) show as white cards at the top of the page column, not in a navy strip. The footer's
version sits under a hairline.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `header.php` markup: the tab icons dropped, the logo's colours from tokens; classes kept | every screen template |
| `notices.php`: the region moves into the page column (`#affibloom-notices`, `aria-live`, the `<template>` and `affibloomNotice()` unchanged) | every screen template; `admin.js` |
| `admin.css` sections 2 (header, Menu button and its panel, the split button, Menu), the notice region and the footer | — |

**Files:** modify `templates/admin/partials/{header,notices,footer}.php`, `assets/admin/admin.css`,
`tests/E2E/admin-design.spec.ts` (the header part: white, 64px, current tab green with its underline, no icon in a
tab; a notice is a white card inside the column). F38 AC1-AC6 keep passing unchanged (tabs, order, `aria-current`,
Menu button, notices).

- [x] **Step 1: Test** (the header and notice part of `admin-design.spec.ts`).
- [x] **Step 2: Header and Menu panel.**
- [x] **Step 3: Notice cards and footer;** remove the navy tokens' last uses in these sections.
- [x] **Step 4: Check and look.** `phpcs` on the partials; Dashboard at 1280 px with a script notice
  (`affibloomNotice( 'success', 'Test' )` in the console) and the header at 375 px.
- [x] **Step 5: Commit** — "Draw the header white with underlined tabs, and notices as cards".

*Note (2026-11-17):* radii are the user's smaller ones (notices and the Menu 12px, menu items 6px; 21acb86), not 0b.3's
14px. The header, notices and footer line up with the 1320px column (the page body is content-box). Part of the header
CSS went out in 21acb86 (a parallel session staged the whole file); this commit holds the rest.

#### Task MD3: A page head instead of the band

**Decision:** deviation 142; design system 0b.4 (No band, Page head).

**Why:** seven templates print the gradient band; one partial replaces it, so the head is the same everywhere (shared
code extracted on its second use).

**Depends on:** MD2.

**User action:** on the Dashboard, a product page, Groups, a group, Fields, a field and every Settings tab, the title
(36px) and its subtitle sit on the page background with the screen's head actions on the right; the cards no longer
float up over a band. Settings' tabs are a segmented control under the title. The product page shows its name, URL and
tabs this way until MD5 finishes it.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `templates/admin/partials/page-head.php` (`title`, optional `title_id` and `title_class`, `subtitle`, optional `actions` as a partial name and its args, optional `below` partial for tabs) printing `.affibloom-page__title` and `.affibloom-page__subtitle` | `dashboard.php`, `product.php`, `groups.php`, `group.php`, `fields.php`, `field.php`, `settings-band.php` |
| `settings-band.php` reduced to the head and the segmented tabs (`.affibloom-tabs__tab`, `aria-current` kept) | `settings.php`, `settings-display.php`, `settings-amazon.php` |
| `admin.css`: section 3 (band) replaced by the page head; section 4's body no longer overlaps; the band's search and segmented rules move with their content | — |

**Files:** create `page-head.php`; modify the seven templates above, `settings-band.php`, `assets/admin/admin.css`,
`tests/E2E/admin-design.spec.ts` (no `.affibloom-band`; the title 36px/800 in ink on the page; the column centred,
at most 1320px; the subtitle present). Groups' search and **Add New Group** become the head's actions; the
Dashboard's search moves under the head (its form, `#affibloom-search` and the shortcut hint unchanged), MD4 sizes it.

- [x] **Step 1: Test.** - [x] **Step 2: `page-head.php` and the CSS.** - [x] **Step 3: The seven templates.**
- [x] **Step 4: Check and look.** `phpcs` on the templates; each screen once at 1280 px, Settings → Display too
  (its structure must be unchanged).
- [x] **Step 5: Commit** — "Replace the gradient band with a page head on every screen".

*Note (2026-11-17):* the section tabs are a `tabs` argument rendered by a shared `section-tabs.php` (Settings, a
product, a group, a field), not a `below` partial; the head actions are `list-head-actions.php` (Groups, Fields) and
`group-head-actions.php`; `below` holds only the Dashboard's search (`dashboard-search.php`). The navy, band and
`brand-300` tokens are gone; the modal backdrop's navy stays for MD6. Seen at 1280 px on all seven screens.

#### Task MD4: The Dashboard: counts beside the title, search, list

**Decision:** deviation 142; design system 0b.4 (Dashboard (U9), Quick edit).

**Why:** the Dashboard is the most used screen; its tiles wrapped to two rows in Aurora.

**Depends on:** MD3.

**User action:** Broken links, Out of stock and Opportunities show as stat cards right of the title (icon chip, big
number or "—", label and the placeholder note as today); Products and Clicks (30 days) form one quiet line under the
subtitle (Products is the live count). Under the head a full-width 48px search with the Ctrl+Shift+L hint. The list:
SAVED VIEW chips and Filters, the selection bar in green tint, 80px rows with 52px image tiles, sentence-case column
heads, numbers in the display face, Edit and Shortcode as small outlined buttons that strengthen on row hover, the
pager's current page as a white raised chip. Quick edit opens as a pale green band with a white card. Nothing new
filters: the stat cards become buttons in 31B and O8.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `dashboard-tiles.php`: the three attention tiles as stat cards in the head's actions, Products and Clicks as the summary line (classes `.affibloom-tile*` and `data-library-count` kept; `dashboard.js` rewrites the count as now) | `dashboard.php` (through `page-head.php`) |
| `admin.css`: stat cards, summary line, search, toolbar chips, table, selection bar, quick edit, pager | — |

**Files:** modify `templates/admin/dashboard.php`, `templates/admin/partials/dashboard-tiles.php`,
`assets/admin/admin.css`, `tests/E2E/admin-design.spec.ts` (the Dashboard part). Read F19's spec for layout checks
on the tiles and the toolbar (`boundingBox`) and update only those.

- [x] **Step 1: Test.** - [x] **Step 2: Head and search.** - [x] **Step 3: List, selection bar, quick edit, pager.**
- [x] **Step 4: Check and look.** `phpcs`; the Dashboard at 1280 px: a ticked row, a quick edit open, a search with
  no match.
- [x] **Step 5: Commit** — "Put the Dashboard's counts beside its title and restyle its list".

*Note (2026-11-17):* `page-head.php` gained a `meta` slot under the subtitle for the summary line; `dashboard-tiles.php`
prints either part (`part`: `stats` or `summary`). The toolbar and the list sit in one table card (`.affibloom-card--list`,
0b.4), the selection bar tinting the toolbar's row; thumbnails keep the 8px radius (the user's smaller radii, not 0b.3's
14px). The table header (sentence case) and the pager changed for every list; rows, columns and thumbnails only on the
Dashboard. Seen at 1280 px: all eight columns fit (no sideways scroll), 80px rows, ticked row, quick edit, no match.

#### Task MD5: The product page: head, sticky preview, save bar

**Decision:** deviation 142; design system 0b.4 (Page head, Product page).

**Why:** the product page is the longest screen (about 2,800px); the preview scrolled away and Save sat at the
foot.

**Depends on:** MD4.

**User action:** a product page opens with "‹ Dashboard", a 76px image tile, the name (two lines at most, the full
name as a tooltip) and the URL; underline tabs with count pills ("Soon" chips stay); the Share kit as one row. Two
columns, 58% / 42%: on the right the live preview stays in view while the form scrolls (it still opens the Media
Library for Update Image); on the left the form in cards: *Links & buttons*, *Organize*, *Display content* (the
fields that sat beside the preview), *Disclosure & link attributes*, *Amazon Data*. The footer (Delete This Link,
Save Changes) is a bar stuck to the foot of the screen; while the form has unsaved changes it says "Unsaved changes".
Every field, its order of reading, Save, Enter-to-save and the leave-guard work as before.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `product.php`: the head through `page-head.php`, the 58/42 grid, the aside, the form's cards; the fields keep their ids, `name`s and reading order (Lasso's left column, then its right column) | `ProductScreen` |
| `product-display-card.php` split: the preview frame into the aside, the display fields into the *Display content* and *Disclosure & link attributes* cards | `product.php` |
| `product-save.js`: sets `data-dirty` on the form when the guard counts a change and clears it after a save; the bar's status reads it | the product page |
| `admin.css`: product head, grid, sticky aside (top = admin bar + header + 16px; static below 992px, where the preview moves above the form), cards, save bar | — |

**Files:** modify `templates/admin/product.php`, `templates/admin/partials/{product-display-card,product-fields,
share-kit,amazon-data-card}.php` (markup only), `assets/admin/{product-save.js,admin.css}`,
`tests/E2E/admin-design.spec.ts` (the product part: one Save Changes button, the bar stuck to the foot, the aside
sticky, "Unsaved changes" after typing in Name). Grep the product specs (F1, F7, F8, F28, F29, C1) for layout checks
and update only those. **One Save Changes:** `#affibloom-save` stays the only one (specs use
`getByRole( 'button', { name: 'Save Changes' } )`).

- [x] **Step 1: Test.** - [x] **Step 2: Head, tabs, Share kit.** - [x] **Step 3: Grid, aside, cards.**
- [x] **Step 4: Save bar and the dirty state.**
- [x] **Step 5: Check and look.** Unit tests, `phpcs`; a long-named Amazon product and a plain one at 1280 px:
  scroll (the preview stays), edit Name (preview and "Unsaved changes"), save (toast, the status clears).
- [x] **Step 6: Commit** — "Give the product page a sticky preview and a save bar".

*Note (2026-11-17):* `page-head.php` gained `back`, `image` and `tooltip` for the product's head; the tabs are its `below`
partial, so they run across the column. `product-display-card.php` prints `part` `preview` (the aside's *Live preview*
card) or `fields` (two cards); the Share kit and the Amazon card changed by CSS only. The head's image follows a save
(product-save.js). Seen at 1280 px: 585/427px columns, the aside held at 112px while scrolling, the bar on the window's
foot, "Unsaved changes" on an edit and gone after the save.

#### Task MD6: Settings, Groups, Fields, dialogs and the toast

**Decision:** deviation 142; design system 0b.4 (Settings, Dialogs, Toast, other screens).

**Why:** what is left of Aurora after MD1-MD5: the settings rows' icon chips, the list screens' cards, the dialogs'
navy backdrop and the toast's place.

**Depends on:** MD5.

**User action:** Settings → General and Amazon show each section as a card of rows (label and visible help left,
control right, no icon chips); Display keeps its structure in Meadow's colours and type. Groups, a group, Fields and a
field use the Dashboard's table card and form cards. Dialogs have 28px padding, a 44px icon chip (red for deleting),
a 22px title and a dark green-grey backdrop; the toast shows at the bottom centre.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `toggle.php`: the icon chip dropped from settings rows (label, help, `aria-describedby` and the switch unchanged) | the Settings tabs |
| `admin.css`: settings rows, the list screens, the group member rows, modal (backdrop `rgba(22,32,26,.45)`), toast (bottom centre), state chips | — |

**Files:** modify `templates/admin/partials/toggle.php`, `assets/admin/{admin.css,fields.css,
settings-general.css}`, `tests/E2E/admin-design.spec.ts` (Settings and Display parts: no chip in a row, the 380px rail
unchanged, the dialog and the toast).

- [x] **Step 1: Test.** - [x] **Step 2: Settings.** - [x] **Step 3: Groups and Fields.** - [x] **Step 4: Dialogs and
  toast.**
- [x] **Step 5: Check and look.** `phpcs`; each screen once at 1280 px, a Delete dialog open, a save toast.
- [x] **Step 6: Commit** — "Restyle Settings, Groups, Fields, dialogs and the toast for Meadow".

*Note (2026-11-17):* Settings → General has used Lasso's switch rows (`switch-row.php`) since deviation 96, so no row
had an icon chip: `toggle.php` and its CSS were dead and are removed; the spec checks the switch rows instead. Groups,
a group, Fields and a field already wore the shared head and table card after MD3-MD4. The 44px chip is on the dialogs
it means something on (`delete-modal.php`, `view-delete-modal.php`: danger and bin; `unsaved-modal.php`: warning); the
toast sits above the product page's save bar. Seen at 1280 px: each screen, the delete dialog, a toast (centred).

#### Task MD7: The design system rewritten for Meadow

**Decision:** deviation 142.

**Why:** sections 1-8 of `design-system.md` still describe Aurora; after MD6 nothing draws it.

**Depends on:** MD6.

**User action:** none on screen. The design system reads as one document for Meadow.

**Files:** modify `docs/ui/design-system.md` (sections 1-8 rewritten from 0b and from what MD1-MD6 built: tokens,
components and their measured values; section 0 and the class-name list kept; 0b shortened to its decision and
history), `assets/admin/admin.css` (drop the retired tokens if any is left: `grep -n "navy\|on-band\|on-navy\|brand-300\|brand-500"`).

- [x] **Step 1: Rewrite** sections 1-8; remove the retired tokens.
- [x] **Step 2: Check.** `grep` the plugin for the retired token names (none left); `composer lint`.
- [x] **Step 3: Commit** — "Rewrite the design system for Meadow".
- [ ] **Step 4 (recommended, the user's call):** the restyle touches every admin screen, so ask whether to run the
  admin specs once now (F1, F9, F18, F19, F23, F26, F38, C1 and `admin-design.spec.ts`) instead of waiting for
  Phase 4's exit check.

*Note (2026-11-17):* the retired tokens had already gone in MD3 (none left in code). Section 0 now also names General
and Amazon's Lasso structure (deviation 96); 0b keeps the decision and the history. Step 4 is asked at the end of the
Phase 4 run (the user asked for every batch at once).

---

### Task 23A: The block and "Choose a Display Type"

**Feature:** F4 (R1, R2, R4-R6, R8-R11, D1, D3, D8, R57; AC3, AC4; N1)

**Why:** the block's entry point; every insert flow starts at the chooser (N1 withdraws U1). It also sets up the
`@wordpress/scripts` build the whole phase needs.

**Depends on:** Tasks 18A-18K (Single renders), N1 answered, Task 9A (design system).

**User action:**
1. The editor opens a post and inserts "Product box" from the inserter: the empty panel appears with the prompt text and
   **Add a Display**.
2. While the modal script boots the button reads "Loading..." and is disabled; if it fails it reads "Retry Add a
   Display" and a click retries.
3. **Add a Display** opens the modal "Choose a Display Type": Single, Grid, List, Table, Button, Image, Gallery (AI
   Text only if N4 says so). Types whose renderer is not built yet are shown disabled with "Coming soon".
4. ×, Escape or a click on the backdrop closes it; reopening starts at the chooser again. Keyboard: the tiles are
   focusable, Escape closes the topmost modal.

**Request path:**
```
Editor → blocks/product-box/edit.js (panel) → assets/editor/display-modal.js (open, step "type")
  EditorAssets (enqueue_block_editor_assets) prints the script, the stylesheet and the display types it may offer
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `@wordpress/scripts` build; `blocks/product-box/{index.js,edit.js,editor.css}` | the block editor |
| `blocks/product-box/block.json` with the attributes of D3 (`type`, `productId`, `groupId`, `tableId`, the overrides, `schema_*`) | WordPress |
| `Interface\Editor\EditorAssets` (Module: `enqueue_block_editor_assets`, `enqueue_block_assets`) | `Plugin` |
| `assets/editor/display-modal.js` (open, close, Escape stack, the chooser step), `display-modal.css` | the block (and ED1, ED2) |

**Files:**
- Create: `blocks/product-box/{index.js,edit.js,editor.css}`, `src/Interface/Editor/EditorAssets.php`,
  `assets/editor/display-modal.{js,css}`, `tests/E2E/acceptance/F4.spec.ts` (AC3, AC4; AC1 gains the chooser click)
- Modify: `package.json`, `blocks/product-box/block.json`, `ProductBoxBlock.php` (registers from `build/product-box`),
  `src/Plugin.php`, `docs/ui/design-system.md` (modal, chooser tiles)

- [x] **Step 1: Failing tests.** `tests/E2E/acceptance/F4.spec.ts`: AC3 (the empty panel, "Loading..." then ready;
  a blocked modal script gives "Retry Add a Display") and AC4 (the tiles, closing three ways, reopening at the
  chooser). Expected red: there is no chooser. Run `npm run test:e2e -- acceptance/F4`.
- [x] **Step 2: Build tooling.** `npm install --save-dev @wordpress/scripts`; `package.json` scripts `build`
  (`wp-scripts build --webpack-src-dir=blocks --output-path=build`), `start`, `lint:js`, `test:js`. `build/` stays
  gitignored; the release zip (Task 39) is built from a fresh build. `ProductBoxBlock` is registered from the built
  folder (`dirname( $this->file ) . '/build/product-box'`). `block.json` gains `editorScript` and `editorStyle`
  (`affibloom-box`, the front-end stylesheet `BoxAssets` registered, then the block's own).
- [x] **Step 3: Block registration, panel and `EditorAssets`.** The attributes of D3; the panel with the prompt text
  and the button states (R5, R6); the assets printed on post/page/CPT editor screens (R57, without Lasso's
  libraries).
- [x] **Step 4: The modal and chooser.** Open, close, the Escape stack (R11), reset to the chooser on close (R10);
  disabled "Coming soon" tiles; tiles focusable. Aurora look (ADR-8), Lasso's labels.
- [x] **Step 5: Run and look.** `npm run build`, `composer lint`, `composer analyse`, `npm run lint:js`, `npm run
  test:e2e -- acceptance/F4`. By hand in Brave at 1280 and 375 px: insert the block, open and close the modal all three
  ways, tab through the tiles. Compare labels and steps with `baselines/admin/block-display-type.png` (look follows
  the design system).
- [x] **Step 6: Commit** — "Open Lasso's display-type chooser from the block". Body: why the chooser is back (all
  seven types are in scope) and that types without a renderer are disabled.

*Note (2026-11-17):* `@wordpress/scripts` 37.1.0 installed at the user's word; `eslint.config.cjs` tells the import
rules that `@wordpress/*` are WordPress's scripts. The block loads `display-modal.js` on first use (`modal-loader.js`),
so R6's states are real; the modal's markup is printed by `EditorAssets` (`templates/editor/display-modal.php`) on
block-editor screens with `admin.css` for Meadow's tokens. A tile is enabled once its step exists (`EditorAssets::STEPS`:
23B links, 23D groups), so until then all seven say "Soon"; Table stays "Soon" until F25. "Loading…" uses the ellipsis
character (WordPress's i18n rule). `affibloom-box` is not in `editorStyle` yet: no box is drawn in the editor before
23C. block.json keeps the existing override attributes (camelCase, F5) beside D3's `type`, `groupId`, `tableId`.
Checked: unit, phpcs, `npm run lint:js`, build; at 1280 and 375 px the panel, the chooser, × / Escape / backdrop, the
focus returning to the button, and a failed load retried. The Playwright ACs are written, not run.

---

### Task 23B: "Choose a Link" — product search

**Feature:** F4 (R12-R15, D2; AC5 search half)

**Why:** Single, Button and Image all pick a product here.

**Depends on:** 23A; F23 G4's `GET /products?search=` (first caller here if G4 has not built it); F23 D10.

**User action:** Single, Button or Image in the chooser opens "Choose a Link": five products a page (picture, name,
link, **+ Add**), newest changed first. "Search URLs" searches on Enter, spaces as wildcards. Page links move between
pages. An empty result says "No links found."; a failed load says "Could not load links. Please try again.".

**Request path:**
```
display-modal.js (step "links") → GET /affibloom/v1/products?search=&page=&per_page=5&orderby=modified
  INSERT_BOXES → ProductsController::index() → ProductService::search() → { items, pages }
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `per_page`, `orderby=modified` on `GET /products` (`ProductsController`, `INSERT_BOXES`) | `display-modal.js` |
| the links step in `display-modal.js` | the chooser |
| `image_url` and permalink in `ProductResource::summary()` (if G4 did not add them) | the rows |

**Files:** modify `src/Interface/Rest/ProductsController.php`, `src/Application/Products/ProductService.php`,
`src/Interface/Rest/ProductResource.php`, `assets/editor/display-modal.{js,css}`; tests `tests/E2E/acceptance/F4.spec.ts`
(AC5 search), `tests/Unit/Application/ProductSearchTest.php` (search arms).

- [x] **Step 1: Failing tests.** AC5's search half (five a page, order, Enter-only search, wildcards, the two
  messages); unit: the search arms. Expected red: no links step.
- [x] **Step 2: Route args.** `per_page`, `orderby=modified`, `page`; published products only; `INSERT_BOXES`.
- [x] **Step 3: The step.** Rows with a 44 px thumbnail, name, permalink (new tab, `noopener noreferrer`, unsafe
  schemes become `#`), **+ Add**; page links; the two messages.
- [x] **Step 4: Run and look.** Checks as 23A; by hand: search "mizuno", an empty search, a failed load (offline), 375 px.
- [x] **Step 5: Commit** — "Search products in the display modal".

*Note (2026-11-17):* the route, its search rule (name or link, spaces as wildcards, `SearchTermTest`) and the newest-first
order already existed (F23 G4, Task 13I), so 23B adds only `per_page` and opens the search to `INSERT_BOXES`
(`edit_posts`, new in `Capabilities`); no `orderby`, no new unit test. The JSON's name is decoded (a stored "&amp;"
showed in the rows; Add to Group reads the same route). The rows' **Add** is wired in 23C. Seen at 1280 and 375 px.

---

### Task 23C: Insert Single, Button and Image; stored markup; the published page

**Feature:** F4 (R3, R16, R32-R34, D3; AC1, AC2, AC5 insert half, AC10)

**Why:** the first display actually placed; fixes the block's stored format (a dynamic block: the server renders the
same HTML in the editor preview and on the page).

**Depends on:** 23B; Tasks 18A-18K (Single); F30 DT5 (Image) and DT7 (Button) (those tiles stay "Coming soon" until
built); 18S1 and 18S4 (the shared render request).

**User action:** **+ Add** closes the modal; the block shows the display, server-rendered in the canvas with the
site's display CSS; the published page shows the same HTML. The block's markup holds the display type, its target and
every override set.

**Request path:**
```
display-modal.js → setAttributes( { type, productId } ) → POST /affibloom/v1/editor/preview { attributes }
  INSERT_BOXES → EditorController → DisplayRequest::from_attributes() → DisplayRenderer::render() → { html, schema }
Published page: block render_callback → DisplayRenderer::render( same request ) → the same HTML
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `render_callback` of the block → `DisplayRenderer` (Task 18A / F30) | WordPress |
| `POST /editor/preview { attributes }` → `{ html, schema }` (`Interface\Rest\EditorController`, `INSERT_BOXES`; 400 `affibloom_invalid_display`) | the block's preview |
| `Domain\Display\DisplayRequest::from_attributes()` (validates type, ids, overrides) | the route, the render callback |
| canvas styles (display CSS vars, display stylesheet) in `EditorAssets` (R34) | WordPress |

**Files:** create `src/Interface/Rest/EditorController.php`, `src/Domain/Display/DisplayRequest.php` (reuse the
shortcode feature's request if 18S already holds the shape; extract on this second use);
modify `ProductBoxBlock.php`, `EditorAssets.php`, `blocks/product-box/edit.js`, `src/Plugin.php`;
tests `tests/E2E/acceptance/F4.spec.ts` (AC1, AC2, AC10), `tests/Integration/EditorPreviewTest.php`
(callback = preview HTML; the 400 and 403 answers).

- [x] **Step 1: Failing tests.** AC1 (insert, chooser, Single, search, Add, the box shows), AC2 (publish, the page shows
  it), AC10 (stored markup holds type, target and overrides; editor HTML equals page HTML). Expected red: nothing
  renders in the canvas.
- [x] **Step 2: Render callback and route.** One request type; invalid attributes answer 400; no arbitrary
  `do_shortcode` (analysis, Security).
- [x] **Step 3: Insert and preview.** `+ Add` sets the attributes, closes the modal, draws the preview; the block does
  not write post content other than its own comment.
- [x] **Step 4: Run and look.** Checks; rerun the Phase 3 box tests (`box-variants`); by hand: insert, publish, view
  the page, compare with the Lasso baseline for the same product.
- [x] **Step 5: Commit** — "Insert a product, button or image display from the block".

*Note (2026-11-17):* the request is the shortcode's `DisplayRequest` (new `from_block()`, extracted on this second use),
not a new `Domain\Display` class; Button and Image render through `AffibloomShortcode`, Single keeps the block's own path
(F5's tests). The preview route renders through the registered block type, so editor and page share the callback; the
box styles reach the canvas through `BoxAssets::enqueue_for_editor()`. No integration test (that suite has never run);
`DisplayRequestTest` covers the request. AC10 compares the display's anchor, classes and product: on Local, wptexturize
and the active Lasso plugin rewrite the page's text and links. Seen at 1280 px: the three previews, publish, the page.

---

### Task 23D: Grid, List and Gallery — "Choose a Group"

**Feature:** F4 (R17, R18; AC6)

**Why:** groups are shown as a grid, list or gallery; the editor needs to pick one.

**Depends on:** 23C; F23 G1 (`GET /groups`); F30 DT2-DT4, DT6 (Grid, List, Gallery); 18S7 (groups by shortcode).

**User action:** Grid, List or Gallery opens "Choose a Group": five groups a page, "1 link" / "N links", "Search groups"
on Enter, **+ Add** inserts the group as the chosen type.

**Request path:**
```
display-modal.js (step "groups") → GET /affibloom/v1/groups?search=&page=&per_page=5  INSERT_BOXES (F23 G1)
  → setAttributes( { type, groupId } ) → POST /editor/preview
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the groups step in `display-modal.js` | the chooser |
| `groupId` in `DisplayRequest::from_attributes()` for the three group types | the preview and render callback |

**Files:** modify `assets/editor/display-modal.{js,css}`, `src/Domain/Display/DisplayRequest.php`;
tests `tests/E2E/acceptance/F4.spec.ts` (AC6).

- [x] **Step 1: Failing tests** (AC6: five groups a page, counts, Enter-only search, Add per type). Red: no groups step.
- [x] **Step 2: Groups step.** Rows: the group's name, link count, **+ Add**; the failure and empty messages as 23B.
- [x] **Step 3: Run and look.** Checks; by hand: Grid, List and Gallery each from a group, at 375 px.
- [x] **Step 4: Commit** — "Insert a group display from the block".

*Note (2026-11-17):* `GET /groups` gained `per_page` and `items` (id, decoded name, count) beside its HTML, and reads
with `INSERT_BOXES`. The block keeps `groupId`; the render callback looks up the slug (`GroupService::get`) for
`DisplayRequest::from_block()`, and `names_display()` validates the preview (a gone group: 200 with nothing to draw).
The links step's code became `searchStep()`, shared by both steps. Seen at 1280 px: Grid, List and Gallery from a group.

---

### Task 23E: Create Group and "Add to Group" from the modal

**Feature:** F4 (R19-R23, D8; AC7)

**Why:** an editor building a grid often needs a new group or to add links to it without leaving the post.

**Depends on:** 23D; F23 G1 (`POST /groups`), G4 (`POST /groups/{id}/products`, `in_group`).

**User action:** **Create Group** opens "Add a New Group" (Name placeholder "Group Name Goes Here", Description, Save
Changes). An empty name says "Name is required."; a name in use says "A group with this name already exists." (F23
D3). A saved group opens "Add to Group" for it. A group's name in the list opens the same dialog. There: **Add
Group**, **Create a Link**, "Search URLs" (Enter), five a page; rows with a picture, name, link and **Select** or
"✓ Added" (disabled; members and this session's adds). **Done** closes; the group list redraws with the new counts.
**Add Group** inserts the group display; without a slug, or outside a group tab, the messages of R22 appear. Create
buttons are hidden without the `PRODUCTS` capability (D8).

**Request path:**
```
display-modal.js → POST /groups (PRODUCTS) → "Add to Group" → GET /products?search=&page=… (in_group)
  → POST /groups/{id}/products (PRODUCTS) → Done → GET /groups (counts)
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the "Add a New Group" and "Add to Group" dialogs in `display-modal.js` | the groups step |
| capability flag for the Create buttons (localised by `EditorAssets`) | `display-modal.js` |

**Files:** modify `assets/editor/display-modal.{js,css}`, `src/Interface/Editor/EditorAssets.php`;
tests `tests/E2E/acceptance/F4.spec.ts` (AC7; a user without `PRODUCTS` sees no Create buttons).

- [x] **Step 1: Failing tests** (AC7 incl. both refusals, Select/Added, Add Group, Done, counts; edge case 7).
- [x] **Step 2: The two dialogs.** Messages verbatim from R19-R22; the save buttons show a spinner.
- [x] **Step 3: Run and look.** Checks; by hand: create a group, add two products, insert it as a gallery, 375 px.
- [x] **Step 4: Commit** — "Create and fill a group from the display modal".

*Note (2026-11-17):* the two dialogs are stacked modals in the same template, printed only with `PRODUCTS` (no localised
flag needed); `searchList()` now serves the steps and "Add to Group". R22's two messages are not built: the dialog opens
only from the groups step, where the type is a group type and the group's ID is known. "Create a Link" in "Add to Group"
comes with 23G. Edge case 7's test makes its own author with WP-CLI. Seen at 1280 and 375 px: create (the empty-name
refusal), two Selects, Done with the count, Add Group as a gallery.

---

### Task 23F: moved to Phase 7, after F25 T12 (settled 2026-11-14)

The table display needs F25's tables (Phase 7). 24D's table half is built in 23F there.

---

### Task 23G: Create a Link and the quick detail

**Feature:** F4 (R25-R31, D7, D8; AC9)

**Why:** editors add a product while writing; Lasso's editor shows a quick detail of the new product before inserting.

**Depends on:** 23C, 23E; F18 Tasks 11A-11E (`POST /products`; this task builds the editor variant of the dialog, F18
D6, without the Marketplace tab, F18 D5); F1 Tasks 14A-14C (product form partials), 15A (`PATCH /products/{id}`).

**User action:** **Create a Link** (in "Choose a Link" and in "Add to Group") closes the display modal and opens "Add A
New Link" (URL tab only; "Enter the destination URL your Affibloom link will redirect to."). **Add Link** creates the
product and opens the quick detail: the product's fields with their help texts, a live preview (name, buttons, badge,
price, disclosure and description update as typed; theme change reloads it), footer **+ Add** and **Cancel**. **+ Add**
saves (errors under the form: "Save failed." or the server's text) and inserts the product as the chosen Single,
Button or Image display, or adds it to the pending group and reopens "Add to Group". **Cancel** returns to "Choose a
Link". R31's unused action is not built. Needs `PRODUCTS`.

**Request path:**
```
display-modal.js → POST /products { url } (PRODUCTS, F18) → quick detail (form partials)
  → PATCH /products/{id} (15A) → setAttributes( … ) | POST /groups/{id}/products → "Add to Group"
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the "Add A New Link" editor variant and the quick-detail modal (`assets/editor/quick-detail.js`, the F1 form partials in a modal) | `display-modal.js` |
| `GET /products/{id}` form partial for the modal, if F1 has no partial route yet | `quick-detail.js` |

**Files:** create `assets/editor/quick-detail.{js,css}`; modify `assets/editor/display-modal.js`, `EditorAssets.php`,
the F1 form template (only to make it usable inside a modal); tests `tests/E2E/acceptance/F4.spec.ts` (AC9, both paths).

- [x] **Step 1: Failing tests** (AC9: the Single path and the "Add to Group" path; an invalid URL; Cancel; a user
  without `PRODUCTS`).
- [x] **Step 2: The modal shells.** Add A New Link, quick detail; Escape closes the topmost first (edge case 6).
- [x] **Step 3: Save and insert.** `PATCH`, errors, insert or add to group.
- [x] **Step 4: Run and look.** Checks; by hand: create a product from a pasted URL, edit its badge in the preview,
  insert it, publish, 375 px.
- [x] **Step 5: Commit** — "Create a product from the editor and insert it".

*Note (2026-11-17):* the quick detail is the product page itself in a frame (`?affibloom_frame=1`;
`AdminAssets::framed()` adds the body class, admin.css shows only the head, the form and the live preview), so every
field of R26 with its help text, the preview, theme reload and the Media Library are the product page's; no
`quick-detail.js` or form-partial route. `product-save.js` tells the frame's parent `affibloom:product-saved` /
`-save-failed`; the dialog's + Add submits the framed form. The frame is removed on close, so a half-edited form never
asks to be saved. The display modal stays under the dialogs (Cancel returns to "Choose a Link", R30). Seen at 1280 px:
an invalid link, a new product named in the quick detail and inserted.

---

### Task 24A: Customize Display sidebar

**Feature:** F4 (R35-R42, D5; AC11); F5 (the per-box overrides it sets)

**Why:** per-box text and settings need controls in the editor.

**Depends on:** 23C (per type: the type's insert task); Tasks 20A-20C (the overrides the renderer honours).

**User action:** the sidebar's **Customize Display** (open) shows the fields of the block's type with Lasso's labels
and help texts (Single: title, title URL, title tag, description, badge, brag, price/field/rating toggles, theme, the
two buttons' url and text, image URL, disclosure text, anchor id; Button, Image, Grid, List, Gallery, Table as R37-R38).
Text fields update the preview on blur or Enter; toggles at once; a cleared field drops the override; toggles store only
their non-default value (R39); double quotes are removed, line breaks in `description` kept, spaces in `anchor_id`
become `-` (R40).

**Request path:**
```
edit.js → InspectorControls → setAttributes( { badge, … } ) → POST /editor/preview → the preview redraws
OverrideCatalog (localised by EditorAssets) → which fields, toggles and textareas per type
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Display\OverrideCatalog` (types → fields, toggles, textareas; pure PHP, unit-tested), localised to the editor | the sidebar, ED2 |
| the Customize Display panel in `edit.js` | the block |

**Files:** create `src/Domain/Display/OverrideCatalog.php`, `tests/Unit/Domain/Display/{OverrideCatalogTest,DisplayRequestTest}.php`;
modify `blocks/product-box/edit.js`, `EditorAssets.php`, `DisplayRequest.php`, `blocks/product-box/edit.test.js` (Jest: chooser start, sidebar fields);
tests `tests/E2E/acceptance/F4.spec.ts` (AC11).

- [x] **Step 1: Failing tests** (AC11; unit: toggle rules, quote stripping, anchor id, rebuild order R41). Red: no panel.
- [x] **Step 2: Catalog.** Texts from Lasso minus the product name; the reference-guide link is left out (D5, deviation
  52); the brag field is kept but links nowhere.
- [x] **Step 3: Panel.** Blur or Enter reloads; toggles reload at once.
- [x] **Step 4: Run and look.** Checks; `npm run test:js`; by hand: type "Best Overall" in Badge, see the preview, publish.
- [x] **Step 5: Commit** — "Override a display per placement from the sidebar".

*Note (2026-11-17):* the catalogue is split by layer: `Domain\Display\OverrideCatalog` (fields per type, toggle and
cleaning rules, unit-tested) and `Interface\Editor\OverrideTexts` (the translatable labels and help texts, Lasso's
with its site's examples as example.com, "Lasso Links" as products, the brag line linking nowhere). The block keeps
its overrides in one `overrides` attribute under Lasso's names; a block with a `type` now renders through the shortcode
with them (blocks with none keep F5's camelCase path). No Jest test: wp-scripts 37 needs Jest or Vitest installed
apart, a download not asked for; the rules are unit-tested in PHP and the server cleans the same way. Seen at 1280 px:
Single's and Grid's fields, Badge "Best Overall" in the preview, the price toggle stored as `hide`.

---

### Task 24B: Schema & Structured Data panel

**Feature:** F4 (R43-R47, D6; AC12)

**Why:** the editor chooses which block's review schema the page prints.

**Depends on:** 24A; F1 Task 14C (primary rating, pros, cons fields); 20D and 18S14 (the JSON-LD output, R48).

**User action:** for a Single display the **Schema & Structured Data** panel (closed at first) shows Review author,
Review schema, Price currency, Price, Rating value, Pro/Con schema, Pros, Cons. Only one block per post can turn schema
on; the others' panels are disabled. Turning Review schema on for a product without a primary rating gives it 3.5 (R46).

**Request path:**
```
edit.js → GET /affibloom/v1/editor/schema?post_id=  (edit_post) → EditorController → PostDisplays::from_content() (read only) → SchemaInfo
toggle → schema_review="enable" → POST /editor/preview ; rating 3.5 → PATCH /products/{id}
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `GET /editor/schema?post_id=` → `{ using_schema_id, products }` | the panel |
| `Domain\Display\PostDisplays` (read-only parse of a post's displays), `Application\Editor\SchemaService` (one-per-post rule) | the route |
| the schema panel in `edit.js` | the block |

**Files:** modify `EditorController.php`, `edit.js`; create `PostDisplays.php`, `SchemaService.php`,
`tests/Unit/Application/SchemaServiceTest.php`; tests `tests/E2E/acceptance/F4.spec.ts` (AC12).

- [x] **Step 1: Failing tests** (AC12 incl. one-per-post, and the 3.5 rating; unit: `SchemaService`, `PostDisplays`).
- [x] **Step 2: `GET /editor/schema`.** `edit_post` on the post; no writes.
- [x] **Step 3: Panel.** Notice text; Pro/Con toggle only if the product has both; the rule of edge case 4.
- [x] **Step 4: Run and look.** Checks; by hand: two blocks, turn schema on in the first, see the second disabled; view the page source.
- [x] **Step 5: Commit** — "Set a display's review schema from the sidebar".

*Note (2026-11-17):* the panel sets the block's existing schema attributes (`schemaReview`, `schemaProsCons`,
`schemaPrice`, `schemaPriceCurrency`, F5), which SchemaMarkup already reads. The one-per-post rule reads the editor's
own block list (the post as edited, not as saved), so `PostDisplays` and `SchemaService` are not built; `GET
/editor/schema?product=&post=` answers one product's facts, and `POST /editor/schema/rating` gives Lasso's 3.5 through
`FieldsService::attach()`. The rating, Pros and Cons reading moved into `ProductFields` (`shown_rating()`, `lines()`),
shared with SchemaMarkup. Not built: R47's copying of the preview's schema into a new Single's attributes (empty
attributes already mean the product's values). Seen at 1280 px: two blocks, 3.5 given, the second disabled, the JSON-LD.

---

### Task 24C: Shortcode box and Update Display

**Feature:** F4 (R7, R49, D4; AC13)

**Why:** editors paste and edit shortcodes; the block shows the equivalent shortcode and keeps both in step.

**Depends on:** 24A; Tasks 18S1-18S7 (the shortcode parser).

**User action:** once a display is set, the block shows its `[affibloom …]` shortcode in a text box; typing there and
leaving the box (or Enter) changes the display and the sidebar follows (absent attributes cleared). **Update Display**
re-renders the preview. A shortcode that does not parse leaves the preview empty and the sidebar unchanged (edge
case 2).

**Request path:**
```
edit.js (shortcode box) → parse (18S's parser) → setAttributes → POST /editor/preview
attributes → ShortcodeFormat → the box's text
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| shortcode box and **Update Display** in `edit.js` | the block |
| `Domain\Display\ShortcodeFormat` (attributes to text), or 18S's formatter if it exists | the box |

**Files:** modify `edit.js`, `EditorAssets.php` (localised parser rules, D4); create `tests/Unit/Domain/Display/ShortcodeFormatTest.php`;
tests `tests/E2E/acceptance/F4.spec.ts` (AC13).

- [x] **Step 1: Failing test** (AC13; unit: parse and format round trip).
- [x] **Step 2: Parse/format.** Rebuild order R41.
- [x] **Step 3: Run and look.** Checks; by hand: edit a shortcode, watch the sidebar follow.
- [x] **Step 4: Commit** — "Edit a display as its shortcode in the block".

*Note (2026-11-17):* writing and reading the text happen on the server (`Domain\Display\ShortcodeFormat`, unit-tested
round trip; the preview answer carries `shortcode`, `POST /editor/shortcode` reads typed text with WordPress's
`shortcode_parse_atts`), so no parser rules are localised; a group is written by slug and read back to its ID
(`GroupService::id_by_slug`). R41's "the edited attribute first" is not kept: the target, then the catalogue's order.
Update Display gives the box a fresh copy of the display's text. Seen at 1280 px: a typed change, a refused text, Update.

---

### Task 24D: Edit Display

**Feature:** F4 (R51, D9; AC14)

**Why:** editors need a quick way from a display to its product or table.

**Depends on:** 23C; F1 Task 14A (the product page); the table half is built by Task 23F in Phase 7 (settled 2026-11-14).

**User action:** **Edit Display** (Single, Button, Image, Table) opens the product's or table's page in a new tab
(closing an earlier one); when that tab is closed, the previews are drawn again on the editor's next `focus` (D9).

**Request path:**
```
edit.js → window.open( edit_url ) → focus event → POST /editor/preview
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the **Edit Display** button in `edit.js`; the `edit_url` in the preview response | the block |

**Files:** modify `edit.js`, `EditorController.php`; tests `tests/E2E/acceptance/F4.spec.ts` (AC14; the table half skipped until F25 T3).

- [x] **Step 1: Failing test** (AC14).
- [x] **Step 2: Button and redraw on focus.**
- [x] **Step 3: Run and look.** By hand: open, change the badge, close the tab, see the box change.
- [x] **Step 4: Commit** — "Open a display's product or table from the block".

*Note (2026-11-17):* the preview answer's `edit_url` is the product page for a Single, Button or Image display and a
user with `PRODUCTS` (the page needs it), else '' and no button; the table half waits for F25 (23F, Phase 7). One named
tab (`affibloom-edit-display`); its closing is noticed on the editor's next `focus`, which redraws every block. Seen
at 1280 px: the button row, a rename in the tab shown after it closed.

---

### Task 24E: Preview queue on load

**Feature:** F4 (R52-R54; AC15)

**Why:** a long post with many blocks must not flood the server.

**Depends on:** 23C.

**User action:** a post with twelve displays loads every preview, at most three at once, 40 ms apart, and only the
latest request of a block draws (R52); the canvas, iframe or not, is previewed once the title appears (R53); a block's
preview redraws when its shortcode changes (R54).

**Request path:**
```
edit.js → previewQueue (3 at once, per-block token) → POST /editor/preview → the block draws
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `previewQueue` (module in the block's script) | every block's preview, ED2 |

**Files:** create `blocks/product-box/preview-queue.js`, `preview-queue.test.js`; modify `edit.js`; tests
`tests/E2E/acceptance/F4.spec.ts` (AC15).

- [x] **Step 1: Failing test** (AC15: a post with 12 blocks; never more than 3 requests in flight; fast typing, the
  latest wins).
- [x] **Step 2: Queue (3 at once, latest wins).**
- [x] **Step 3: Run and look.** By hand: open a 12-block post, watch the network list.
- [x] **Step 4: Commit** — "Load many display previews without flooding the server".

*Note (2026-11-17):* `preview-queue.js` keeps three requests in flight, starts them 40 ms apart, and a block's waiting
request is replaced by its newer one (its own `current` flag already drops a late answer). R53's polling for the title
is not needed: a block asks once it is drawn. No `preview-queue.test.js` (Jest is not installed, as in 24A). Seen:
twelve displays in a new post, twelve requests, three at most in flight.

---

### Task ED1: Classic editor button

**Feature:** F4 (R60-R64, D11; AC16)

**Why:** sites with the Classic Editor or Disable Gutenberg plugin need the same modal.

**Depends on:** 23C (shares the modal); Tasks 18S1-18S7 (the shortcode it inserts).

**User action:** with the Classic Editor (or Disable Gutenberg) plugin active, the toolbar has **Add A Display**
(loading: disabled, "Loading Display..."; error: "Retry Add A Display"); it opens the same modal and inserts the
shortcode at the cursor; no preview. The undefined grid button and the keyword element of Lasso are not built (D11).
Not loaded on this site today (no such plugin), so the test activates the plugin.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Editor\ClassicButton` (Module: `mce_external_plugins`, `mce_buttons`, only when the Classic Editor plugin or Disable Gutenberg is active) | `Plugin` |
| `assets/editor/classic-button.js`; the modal's `mceInsertContent` insert | TinyMCE |

**Files:** create the above; modify `Plugin.php`, `display-modal.js`; tests `tests/E2E/acceptance/F4.spec.ts` (AC16).

- [x] **Step 1: Failing test** (Classic Editor plugin activated in the test; skipped when it cannot be installed).
- [x] **Step 2: `mce_external_plugins`, `mce_buttons`, insert at cursor.**
- [x] **Step 3: Run and look.** By hand in the classic editor, 1280 and 375 px.
- [x] **Step 4: Commit** — "Insert displays from the Classic editor".

*Note (2026-11-17):* Classic Editor 1.7.0, Elementor 4.4.1 and AMP 2.5.5 installed on Local at the user's word (zips from
downloads.wordpress.org: WP-CLI could not reach wordpress.org's API), inactive; the AC16 test switches Classic Editor
on and off. The button loads the same modal lazily (its words and apiFetch from EditorAssets on classic screens) and
inserts the shortcode the preview route writes, so groups go in by slug. Seen at 1280 px: the button, Single, the
shortcode at the cursor.

---

### Task ED2: Elementor widget

**Feature:** F4 (R65-R71, D11; AC17; N6: R72 not built)

**Why:** pages built in Elementor need the same insert and preview flow.

**Also owns (assigned 2026-11-14):** F31 R2's Elementor parts — the "Lasso Shortcode" widget's equivalent and the
`builder_content_data` key filter (`classes/class-elementor.php`), read-only: Affibloom renders Elementor's stored
shortcodes and never rewrites `_elementor_data` (F4 N6).

**Depends on:** 23C, 24A (the catalog), 18S1-18S7.

**User action:** with Elementor active, the widget "Affibloom" (Basic) shows the panel, the modal and the preview; its
controls are the display type's overrides; the saved widget renders the display on the page. Assets load only inside
the Elementor editor.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Editor\ElementorWidget` (`elementor/widgets/register`) and its widget class | Elementor |
| `assets/editor/elementor.js`, `elementor.css` | Elementor editor |

**Files:** create the above; modify `Plugin.php`; tests `tests/E2E/acceptance/F4.spec.ts` (AC17, skipped when Elementor
is absent from the test site).

- [ ] **Step 1: Failing test** (Elementor installed in a test site; skipped when absent).
- [ ] **Step 2: Widget, controls, template.** Render with `do_shortcode( shortcode_unautop( setting ) )`; plain content prints the shortcode.
- [ ] **Step 3: Run and look.** By hand in Elementor.
- [ ] **Step 4: Commit** — "Place displays with an Elementor widget".

---

### Task ED3: Rank Math content analysis

**Feature:** F4 (R73, R74; AC18)

**Why:** Rank Math is active on this site; its analysis should count the rendered display text.

**Depends on:** 23C.

**User action:** with Rank Math active, its content analysis counts each rendered display's text and refreshes a second
after a preview renders.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `assets/editor/rank-math.js` (`rank_math_content` filter, debounced refresh), registered when `rank-math-analyzer` exists | the block editor |

**Files:** create `assets/editor/rank-math.js`; modify `EditorAssets.php`; tests `tests/E2E/acceptance/F4.spec.ts` (AC18).

- [ ] **Step 1: Failing test** (Rank Math is active on this site).
- [ ] **Step 2: `rank_math_content` filter, debounced refresh.**
- [ ] **Step 3: Run and look.** By hand: the analysis word count rises with a display.
- [ ] **Step 4: Commit** — "Let Rank Math read rendered displays".

---

### Task ED4: AMP inline styles

**Feature:** F4 (R75; AC19)

**Why:** AMP pages cannot load external stylesheets or scripts.

**Depends on:** Tasks 18A-18K (the renderer and its stylesheet); 18S9 (`AmpBoxCss`, `amp_post_template_css`). **Overlap
to settle at gate 4:** 18S9 already draws boxes on AMP pages; ED4 verifies that behaviour for every display type and
adds only what 18S9 leaves out.

**User action:** on an AMP page a display is styled by inline CSS (no external stylesheet, no script).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the display types' CSS in the AMP output (printed once), reusing `Interface\Front\AmpBoxCss` | the renderer |

**Files:** modify `AmpBoxCss.php` only if needed; test `tests/Integration/AmpStylesTest.php`; `tests/E2E/acceptance/F4.spec.ts` (AC19).

- [ ] **Step 1: Failing integration test** (simulated AMP request).
- [ ] **Step 2: `amp_post_template_css`.** The AMP guard disables the analytics footer (R75).
- [ ] **Step 3: Run and look.** By hand with an AMP request (`?amp=1`).
- [ ] **Step 4: Commit** — "Style displays on AMP pages".

---

### Task ED5: Keep Lasso's link attributes valid in core blocks (N3 accepted, 2026-11-14)

**Feature:** F4 (R80-R84; AC20)

**Why:** 147 published posts have image, GenerateBlocks button and button blocks with `data-lasso-*`; without these
filters they fail validation when opened in the editor. No content is rewritten.

**Depends on:** nothing (editor script only); must be built before Lasso is deactivated.

**User action:** posts whose image, button and list blocks carry Lasso's link attributes open without "This block
contains unexpected or invalid content", and saving keeps those attributes. Skips kadence/, yoast/, tph/,
editor-blocks/ blocks (R82).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `assets/editor/core-link-attributes.js` (`blocks.registerBlockType` attributes, `blocks.getBlockAttributes`, `blocks.getSaveContent.extraProps`, `blocks.getSaveElement`) | the block editor |
| enqueue in `EditorAssets` (waits for Stackable's script when present, R83) | WordPress |

**Files:** create `assets/editor/core-link-attributes.js`; modify `EditorAssets.php`; test fixtures from
`tests/fixtures/inventory`; `tests/E2E/acceptance/F4.spec.ts` (AC20).

- [ ] **Step 1: Failing test** (open a fixture post with an image and a GenerateBlocks button carrying `data-lasso-*`;
  no invalid-block notice; save keeps the attributes).
- [ ] **Step 2: parse/save filters.**
- [ ] **Step 3: Run and look.** By hand with a real post of the 147.
- [ ] **Step 4: Commit** — "Keep stored link attributes valid in the block editor".

---

### Task ED6: moved to Phase 5

Task ED6 (edit legacy `affiliate-plugin/lasso` blocks; decided A1) moved to Phase 5 on 2026-11-14, right after Task 27A, which it depends on.

---

### Task MCP1: dropped (decision A10)

No MCP routes are built. The section is in `archive/phase-4-superseded.md`.

---

## Exit check

The phase is done when F4's acceptance criteria that these tasks own pass, and a box inserted with the block matches
the Lasso baseline for the same product (rerun `box-variants`, which uses the same render path).

- **F4:** AC1, AC2 (23A-23C); AC3, AC4 (23A); AC5 (23B, 23C); AC6 (23D); AC7 (23E); AC8 (23F; closes in Phase 7 with
  F25 T1/T12); AC9 (23G); AC10 (23C); AC11 (24A); AC12 (24B); AC13 (24C); AC14 (24D; the table half with F25 T3);
  AC15 (24E); AC16 (ED1); AC17 (ED2; skipped when Elementor is absent); AC18 (ED3); AC19 (ED4); AC20 (ED5);
  AC21 (ED6; moved to Phase 5 with ED6).
- Every task: `composer lint`, `composer analyse`, the unit and integration tests it touches, `npm run build`, `npm run
  lint:js`, `npm run test:js`, E2E `acceptance/F4`, and the check by hand at 1280 and 375 px.
- Sign-off: the user signs off the phase before Phase 5 starts.

## Coverage

| R / AC | Task |
|---|---|
| R1, R2, R4-R6, R8-R11, AC3, AC4 | 23A |
| R57 (editor assets, without Lasso's libraries) | 23A |
| R12-R15 | 23B |
| R3, R16, R32-R34, AC1, AC2, AC5, AC10 | 23C |
| R17, R18, AC6 | 23D |
| R19-R23, AC7 | 23E |
| R24, AC8 | 23F (closes in Phase 7) |
| R25-R31, AC9 | 23G (R31's action is not built, D7) |
| R35-R42, AC11 | 24A |
| R43-R47, AC12 | 24B |
| R7, R49, AC13 | 24C |
| R51, AC14 | 24D |
| R52-R54, AC15 | 24E |
| R60-R64, AC16 | ED1 |
| R65-R71, AC17 | ED2 |
| R73, R74, AC18 | ED3 |
| R75, AC19 | ED4 |
| R80-R84, AC20 | ED5 |
| R50, AC21 | ED6 (moved to Phase 5, after 27A; R1-R4 of the legacy block) |
| R76-R79 | not built: decision A10 (MCP1 dropped) |
| R48 | the display renderer, 18S14 (not F4) |
| R55, R56, R72, R86 | not built (D10, N4, N6, D12) |
| R58 | F38 ON* command palette (Phase 8) |
| R59 | F24 CL8, CL9 (Phase 5) |
| R85 | F9 Task 9D (Override All Links; Phase 2) |

UNOWNED: none in F4. R58, R59, R85 are owned by the tasks named above in other features; checked 2026-11-14: those
tasks cover them (the F4 drafts listed them as unowned).
