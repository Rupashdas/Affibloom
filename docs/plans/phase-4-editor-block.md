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

## Tasks in this phase

### F4 — Displays in the editor

- [ ] Task 23A: The block and "Choose a Display Type"
- [ ] Task 23B: "Choose a Link" — product search
- [ ] Task 23C: Insert Single, Button and Image; stored markup; the published page
- [ ] Task 23D: Grid, List and Gallery — "Choose a Group"
- [ ] Task 23E: Create Group and "Add to Group" from the modal
- Task 23F: Table — "Choose a Table" — **moved to Phase 7** (after F25 T12; settled 2026-11-14)
- [ ] Task 23G: Create a Link and the quick detail
- [ ] Task 24A: Customize Display sidebar
- [ ] Task 24B: Schema & Structured Data panel
- [ ] Task 24C: Shortcode box and Update Display
- [ ] Task 24D: Edit Display
- [ ] Task 24E: Preview queue on load
- [ ] Task ED1: Classic editor button
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

- [ ] **Step 1: Failing tests.** `tests/E2E/acceptance/F4.spec.ts`: AC3 (the empty panel, "Loading..." then ready;
  a blocked modal script gives "Retry Add a Display") and AC4 (the tiles, closing three ways, reopening at the
  chooser). Expected red: there is no chooser. Run `npm run test:e2e -- acceptance/F4`.
- [ ] **Step 2: Build tooling.** `npm install --save-dev @wordpress/scripts`; `package.json` scripts `build`
  (`wp-scripts build --webpack-src-dir=blocks --output-path=build`), `start`, `lint:js`, `test:js`. `build/` stays
  gitignored; the release zip (Task 39) is built from a fresh build. `ProductBoxBlock` is registered from the built
  folder (`dirname( $this->file ) . '/build/product-box'`). `block.json` gains `editorScript` and `editorStyle`
  (`affibloom-box`, the front-end stylesheet `BoxAssets` registered, then the block's own).
- [ ] **Step 3: Block registration, panel and `EditorAssets`.** The attributes of D3; the panel with the prompt text
  and the button states (R5, R6); the assets printed on post/page/CPT editor screens (R57, without Lasso's
  libraries).
- [ ] **Step 4: The modal and chooser.** Open, close, the Escape stack (R11), reset to the chooser on close (R10);
  disabled "Coming soon" tiles; tiles focusable. Aurora look (ADR-8), Lasso's labels.
- [ ] **Step 5: Run and look.** `npm run build`, `composer lint`, `composer analyse`, `npm run lint:js`, `npm run
  test:e2e -- acceptance/F4`. By hand in Brave at 1280 and 375 px: insert the block, open and close the modal all three
  ways, tab through the tiles. Compare labels and steps with `baselines/admin/block-display-type.png` (look follows
  the design system).
- [ ] **Step 6: Commit** — "Open Lasso's display-type chooser from the block". Body: why the chooser is back (all
  seven types are in scope) and that types without a renderer are disabled.

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

- [ ] **Step 1: Failing tests.** AC5's search half (five a page, order, Enter-only search, wildcards, the two
  messages); unit: the search arms. Expected red: no links step.
- [ ] **Step 2: Route args.** `per_page`, `orderby=modified`, `page`; published products only; `INSERT_BOXES`.
- [ ] **Step 3: The step.** Rows with a 44 px thumbnail, name, permalink (new tab, `noopener noreferrer`, unsafe
  schemes become `#`), **+ Add**; page links; the two messages.
- [ ] **Step 4: Run and look.** Checks as 23A; by hand: search "mizuno", an empty search, a failed load (offline), 375 px.
- [ ] **Step 5: Commit** — "Search products in the display modal".

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

- [ ] **Step 1: Failing tests.** AC1 (insert, chooser, Single, search, Add, the box shows), AC2 (publish, the page shows
  it), AC10 (stored markup holds type, target and overrides; editor HTML equals page HTML). Expected red: nothing
  renders in the canvas.
- [ ] **Step 2: Render callback and route.** One request type; invalid attributes answer 400; no arbitrary
  `do_shortcode` (analysis, Security).
- [ ] **Step 3: Insert and preview.** `+ Add` sets the attributes, closes the modal, draws the preview; the block does
  not write post content other than its own comment.
- [ ] **Step 4: Run and look.** Checks; rerun the Phase 3 box tests (`box-variants`); by hand: insert, publish, view
  the page, compare with the Lasso baseline for the same product.
- [ ] **Step 5: Commit** — "Insert a product, button or image display from the block".

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

- [ ] **Step 1: Failing tests** (AC6: five groups a page, counts, Enter-only search, Add per type). Red: no groups step.
- [ ] **Step 2: Groups step.** Rows: the group's name, link count, **+ Add**; the failure and empty messages as 23B.
- [ ] **Step 3: Run and look.** Checks; by hand: Grid, List and Gallery each from a group, at 375 px.
- [ ] **Step 4: Commit** — "Insert a group display from the block".

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

- [ ] **Step 1: Failing tests** (AC7 incl. both refusals, Select/Added, Add Group, Done, counts; edge case 7).
- [ ] **Step 2: The two dialogs.** Messages verbatim from R19-R22; the save buttons show a spinner.
- [ ] **Step 3: Run and look.** Checks; by hand: create a group, add two products, insert it as a gallery, 375 px.
- [ ] **Step 4: Commit** — "Create and fill a group from the display modal".

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

- [ ] **Step 1: Failing tests** (AC9: the Single path and the "Add to Group" path; an invalid URL; Cancel; a user
  without `PRODUCTS`).
- [ ] **Step 2: The modal shells.** Add A New Link, quick detail; Escape closes the topmost first (edge case 6).
- [ ] **Step 3: Save and insert.** `PATCH`, errors, insert or add to group.
- [ ] **Step 4: Run and look.** Checks; by hand: create a product from a pasted URL, edit its badge in the preview,
  insert it, publish, 375 px.
- [ ] **Step 5: Commit** — "Create a product from the editor and insert it".

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

- [ ] **Step 1: Failing tests** (AC11; unit: toggle rules, quote stripping, anchor id, rebuild order R41). Red: no panel.
- [ ] **Step 2: Catalog.** Texts from Lasso minus the product name; the reference-guide link is left out (D5, deviation
  52); the brag field is kept but links nowhere.
- [ ] **Step 3: Panel.** Blur or Enter reloads; toggles reload at once.
- [ ] **Step 4: Run and look.** Checks; `npm run test:js`; by hand: type "Best Overall" in Badge, see the preview, publish.
- [ ] **Step 5: Commit** — "Override a display per placement from the sidebar".

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

- [ ] **Step 1: Failing tests** (AC12 incl. one-per-post, and the 3.5 rating; unit: `SchemaService`, `PostDisplays`).
- [ ] **Step 2: `GET /editor/schema`.** `edit_post` on the post; no writes.
- [ ] **Step 3: Panel.** Notice text; Pro/Con toggle only if the product has both; the rule of edge case 4.
- [ ] **Step 4: Run and look.** Checks; by hand: two blocks, turn schema on in the first, see the second disabled; view the page source.
- [ ] **Step 5: Commit** — "Set a display's review schema from the sidebar".

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

- [ ] **Step 1: Failing test** (AC13; unit: parse and format round trip).
- [ ] **Step 2: Parse/format.** Rebuild order R41.
- [ ] **Step 3: Run and look.** Checks; by hand: edit a shortcode, watch the sidebar follow.
- [ ] **Step 4: Commit** — "Edit a display as its shortcode in the block".

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

- [ ] **Step 1: Failing test** (AC14).
- [ ] **Step 2: Button and redraw on focus.**
- [ ] **Step 3: Run and look.** By hand: open, change the badge, close the tab, see the box change.
- [ ] **Step 4: Commit** — "Open a display's product or table from the block".

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

- [ ] **Step 1: Failing test** (AC15: a post with 12 blocks; never more than 3 requests in flight; fast typing, the
  latest wins).
- [ ] **Step 2: Queue (3 at once, latest wins).**
- [ ] **Step 3: Run and look.** By hand: open a 12-block post, watch the network list.
- [ ] **Step 4: Commit** — "Load many display previews without flooding the server".

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

- [ ] **Step 1: Failing test** (Classic Editor plugin activated in the test; skipped when it cannot be installed).
- [ ] **Step 2: `mce_external_plugins`, `mce_buttons`, insert at cursor.**
- [ ] **Step 3: Run and look.** By hand in the classic editor, 1280 and 375 px.
- [ ] **Step 4: Commit** — "Insert displays from the Classic editor".

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
