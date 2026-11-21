# Accessibility review (Task 37C, roadmap 8.3)

Reviewed 2026-11-22 on the Local copy, Phases 2-7 as built. Tools: `tests/E2E/a11y.spec.ts` (6 tests, green) with
axe-core 4 (`@axe-core/playwright`, tags `wcag2a`, `wcag2aa`, `wcag21aa`, `wcag22aa`), keyboard runs in the same
spec, and a read of the code for focus, live regions and names.

**Checked when.** This review ran before ON5-ON9 (the phase's order note): the setup wizard (ON6, ON7), the
Dashboard tour (ON8), the command palette (ON9) and the education boxes (ON5) do not exist yet. Each of those tasks
adds its screen to `a11y.spec.ts` (axe and its keyboard flow, as listed in the plan's step 2) and a line to this file.

## What axe checks (only Affibloom's part of a page: `#wpbody-content` and its dialogs)

- Every admin screen and tab: the Dashboard (All, Broken links, Opportunities), the product page (Details, Locations,
  Opportunities, Variants), Settings (General, Display, Amazon), Groups and a group page, Fields and a field page,
  Tables and a table page, Opportunities (Links, Domains, Content).
- The Dashboard's Filters panel and Add New Link modal; the product page's group picker and Add Field modal; the group
  page's Add to Group modal; the editor's Product box block with its display modal open.
- A guide's box on the front end.

Left out, with the reason: WordPress's own admin menu and bar, and its TinyMCE toolbar in the description field
(`.mce-container`: core's markup, its buttons have no names and nest controls, which only WordPress can change);
the box previews in the admin (`.affibloom-preview-frame`), which draw the reader's box in the client's colours and
are checked on a real page instead.

## Findings

| # | Finding | WCAG | Verdict |
|---|---|---|---|
| A1 | The help "?" buttons were 18 px; in the Content table's headers they sat closer than 24 px to each other. | 2.5.8 Target size (2.2 AA) | **Fixed:** every help button is a 24 px target; a −3 px margin keeps the 18 px layout and `::before` draws the same 18 px circle (design system unchanged). |
| A2 | The saved views' × was 22 px and drawn over its pill's link (overlapping targets). | 2.5.8 | **Fixed:** a pill with × is drawn by its list item, the link and the 24 px × side by side; it looks as before. |
| A3 | The Dashboard row's name link (22 px line) and the URL link under it (18 px, 2 px apart). | 2.5.8 | **Fixed:** each has a 24 px line (48 px together, against 42 before). Also for Groups and Variants rows, which share the class. |
| A4 | The Tables list's product links were 17 px lines, 2 px apart. | 2.5.8 | **Fixed:** 24 px lines. |
| A5 | The table builder's price cell (`role="button"`) had no name when empty. | 4.1.2 Name, Role, Value | **Fixed:** `aria-labelledby` = a hidden "Edit <field>" and the cell itself, so it reads "Edit Price, $99", or "Edit Price" when empty. |
| A6 | The table page's "Saved" status was ink-500 on the page background, 4.4:1 (the design system gives ink-500 on white and subtle only). | 1.4.3 Contrast | **Fixed:** ink-600 (5.9:1 on the page, design system). |
| A7 | A row moved with Arrow Up or Down (field order FL5, a product's buttons and fields) moved and kept focus, but said nothing; the table builder already announced its moves. | U3; 4.1.3 Status messages | **Fixed:** `drag-rows.js` says "Moved to position N of M." in one polite live region (`wp.i18n`, translated with Task 39's files). Test: "a row moved from the keyboard says where it went". |
| A8 | **The box's buttons: white on #22BAA0 is 2.44:1** (4.5:1 needed for 14 px bold text). | 1.4.3 | **For the user:** the box's colours are the client's (Settings → Display, Lasso's same default); not changed. A darker teal such as #12806E (4.8:1) would pass. |
| A9 | WordPress's TinyMCE toolbar (description field): buttons without names, nested controls. | 4.1.2 | Stands: WordPress core's editor, not Affibloom's markup. |

## By hand (plan step 2)

- **Add, edit, save, delete a product; insert a box:** every control is a native button, link or field; the
  modals trap Tab and give focus back on close (`trapTab` in `admin.js`); toasts are `role="status"`.
- **Group picker:** opened from its field by keyboard focus and click; axe clean while open.
- **Drag-and-move:** group members have Move up and Move down buttons (G4); field and product rows move with Arrow
  Up/Down on their grip (FL5), announced since A7; the table builder's columns and rows move from the keyboard and are
  announced (T9, `table-builder-drag.js`).
- **Header:** the Menu button and the Automations popover carry `aria-expanded` and `aria-controls`; list updates are
  announced through `role="status"` paragraphs on each list screen.
- **Help text (U3):** every help tip is a button with `aria-describedby`, shown on keyboard focus and hover, closed by
  Escape.
- **Focus:** 43 `:focus-visible` rules in `admin.css`; the brand-600 ring on every control checked.
- **Wizard, tour, palette, education boxes:** with ON5-ON9.

## Contrast and reflow (plan step 3)

- Admin: axe's contrast rule passes on every screen after A6 (the design system's stated ratios hold for text,
  controls, focus rings, notice tones; there is no step bar until ON6).
- The box: A8, for the user.
- Reflow: the Dashboard and the product page lay out without a horizontal scroll at 640 px (a 1280 px window at 200%)
  and at 375 px (spec test "reflow"); the wizard comes with ON6.

## Screen reader

NVDA is not installed on this machine, and no screen reader was run. What stood in: axe's name, role and value rules
on every screen and modal; the accessible names the tests find by role (`getByRole` with names, as every acceptance
test does); and the live regions above, read from the DOM. A pass with NVDA over the product page and the wizard is
left for 39A's run with the user's editors, or the user's machine.

## Runs

- `tests/E2E/a11y.spec.ts`: 6 passed (2026-11-22).
