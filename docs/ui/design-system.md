# Affibloom admin design system

Status: **Approved** by the user on 2026-11-13 (Task 9A, Step 2), with one change to the first proposal: the brand
colour is ocean blue `#1E5BB8` instead of raspberry pink `#BE185D`, and the info status moved to cyan.
Decision: ADR-008. Scope: every admin screen, modal and notice, and the block's editor UI. The reader-facing product
box is **not** covered: it keeps the site's box design (ADR-006).

**What stays Lasso's:** labels, help texts, fields, tab and column order, and the steps of every workflow
(`docs/spec/interface.md`). **What is ours:** everything visual below. Nothing here copies Lasso's look (purple header,
green buttons, Helvetica Neue at weight 800).

## 1. Principles

1. **Calm and readable.** White surfaces on a light grey page, one brand colour used sparingly, no heavy weights.
2. **At home in wp-admin.** The system font WordPress uses, sizes close to wp-admin's, nothing that fights the black
   admin menu.
3. **Accessible by default.** WCAG 2.2 AA contrast for every pair below, a visible focus ring on every control, help text
   that keyboards and screen readers reach (U3), no meaning carried by colour alone.

## 2. Colour

Brand: **Ocean blue** (chosen by the user on 2026-11-13 over the first proposal's raspberry pink), used for primary
actions, links, the active tab, toggles that are on and focus. Neutrals: **Ink**, a cool slate. Status colours are
only used for status. Brand tokens have neutral names (`brand-*`), so a later colour change touches values only.

| Token | Hex | Role |
|---|---|---|
| `--affibloom-brand-50` | `#EEF4FC` | brand tint: selected row, highlighted areas |
| `--affibloom-brand-100` | `#DCE8F9` | brand tint, stronger (badges) |
| `--affibloom-brand-500` | `#2F74D6` | logo mark only (decorative) |
| `--affibloom-brand-600` | `#1E5BB8` | primary buttons, links, active tab, toggle on, focus ring |
| `--affibloom-brand-700` | `#174A96` | hover/pressed on brand-600; text on brand tints |
| `--affibloom-ink-900` | `#1E2330` | headings, body text, tooltips' background |
| `--affibloom-ink-700` | `#434A59` | secondary text, tab text |
| `--affibloom-ink-600` | `#5B6372` | muted text: descriptions, URLs, table headers |
| `--affibloom-ink-400` | `#8A93A3` | input borders, toggle off |
| `--affibloom-ink-200` | `#E4E7EC` | dividers, card borders |
| `--affibloom-ink-100` | `#F2F4F7` | table header, hover rows, secondary button hover |
| `--affibloom-page` | `#F7F8FA` | page background |
| `--affibloom-white` | `#FFFFFF` | header, cards, modals, inputs |
| `--affibloom-success-700` / `-50` | `#067647` / `#ECFDF3` | saved, link OK |
| `--affibloom-warning-700` / `-50` | `#B54708` / `#FFFAEB` | warnings |
| `--affibloom-danger-600` / `-50` | `#B42318` / `#FEF3F2` | broken links, errors, `Delete This Link` |
| `--affibloom-info-700` / `-50` | `#0E7090` / `#ECFDFF` | neutral information (cyan, so it is not mistaken for the brand blue) |

**Contrast (computed, WCAG 2.2):** ink-900 on white 15.7:1, on page 14.8:1; ink-700 on white 8.9:1; ink-600 on white
6.1:1, on page 5.7:1, on ink-100 5.5:1; white on brand-600 6.5:1, on brand-700 8.6:1; brand-600 on white 6.5:1, on page
6.1:1, on brand-50 5.9:1; brand-700 on brand-50 7.7:1; white on danger-600 6.6:1; danger-600 on danger-50 6.1:1;
success-700 on white 5.7:1, on success-50 5.4:1; warning-700 on warning-50 5.2:1; info-700 on white 5.6:1, on info-50
5.4:1; white on ink-900 15.7:1. UI parts: ink-400 borders and the off toggle on white 3.1:1; the brand-600 focus ring
on white 6.5:1. Status never relies on colour alone: it always has an icon or a word ("Broken", "Delete This Link").

## 3. Typography

- **Font:** the system stack WordPress uses: `-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans,
  Ubuntu, Cantarell, "Helvetica Neue", sans-serif`. No web font, no third-party request.
- **Scale** (size / line height / weight):

| Role | Token | Value |
|---|---|---|
| Page title (`h1`) | `--affibloom-text-title` | 24px / 32px / 600 |
| Section title (`h2`) | `--affibloom-text-section` | 16px / 24px / 600 |
| Body, controls, labels | `--affibloom-text-body` | 14px / 20px / 400 (labels 500) |
| Small: descriptions, URLs, table headers | `--affibloom-text-small` | 13px / 18px / 400 |
| Wordmark | — | 18px / 24px / 600 |

Weights are 400, 500 and 600 only. No uppercase text, no letter-spacing tricks.

## 4. Space, shape, depth

- **Spacing scale (4px base):** 4, 8, 12, 16, 20, 24, 32, 40, 48 → `--affibloom-space-1` … `-12` (`space-n` = n × 4px).
- **Radius:** 6px controls and tooltips (`--affibloom-radius-sm`), 10px cards (`--affibloom-radius-md`), 14px modals
  (`--affibloom-radius-lg`), full round for toggles, counters and the logo (`--affibloom-radius-full`).
- **Borders:** 1px ink-200 on cards and dividers; 1px ink-400 on inputs.
- **Depth:** cards are flat (border only). Shadows only for things that float: tooltip and menu
  `0 4px 12px rgba(16, 24, 40, 0.12)`, toast and modal `0 12px 32px rgba(16, 24, 40, 0.18)`.
- **Motion:** 150 ms ease for hover and toggles; the toast slides in over 200 ms; everything off under
  `prefers-reduced-motion`.

## 5. Layout

- **Header:** a white bar, 64px tall, full width of the content area, 1px ink-200 bottom border. Left: the Affibloom
  logo mark (a five-petal bloom in brand-500, 28px) and wordmark. Next to it, left-aligned, the tabs (Dashboard,
  Settings): ink-700 14px/500, the current tab ink-900 with a 3px brand-600 bar under it. Right: the screen's main
  action when it has one (`Add New Link`, Task 11).
- **Page:** 32px padding, content up to 1200px wide, left-aligned under the header (not centred).
- **Page head:** the page title, with the screen's own tabs (Settings: General, Display, Amazon; product page:
  Details, Locations) as an underline tab row **below** the title, full width, with a 1px ink-200 line.
- **Cards:** white, 1px ink-200 border, 10px radius. A card has a header (section title and a one-line intro in
  ink-600), a body, and, on forms, a footer with the actions, right-aligned, separated by a 1px line.
- **Settings rows:** one row per setting: the label (500) with its description below it in ink-600 (the help text,
  visible, U3's "short line"), and the control on the right. Rows are separated by 1px ink-200 lines, 16px vertical
  padding. The description is linked to the control with `aria-describedby`.
- **Breakpoints:** at 960px and below the page padding drops to 20px; at 782px (wp-admin's mobile breakpoint) the
  header tabs scroll sideways, settings rows stack (control under the text), and card footers' buttons go full width.

## 6. Components

| Component | Spec |
|---|---|
| **Primary button** | brand-600 background, white 14px/500 text, 36px tall, 16px side padding, 6px radius; hover/pressed brand-700; focus ring. |
| **Secondary button** | white, 1px ink-400 border, ink-900 text; hover ink-100. |
| **Danger button** | danger-600 background, white text; only for destructive actions, always with an explicit label. |
| **Disabled** | 50% opacity, `cursor: not-allowed`, `aria-disabled` where a tooltip explains why. |
| **Focus ring** | 2px brand-600 outline, 2px offset, on every interactive element (`:focus-visible`). |
| **Toggle** | 36×20 track, ink-400 off, brand-600 on, 16px white knob, 150 ms. The real checkbox lies over the track (`role="switch"`). |
| **Help tip** | for dense places (form fields, table headers): an 18px round "?" button (ink-600 on white, 1px ink-400 ring); on hover and focus a tooltip, ink-900 background, white 13px text, max 280px, 6px radius, the floating shadow; its text is linked with `aria-describedby`. Settings rows show the text as a description instead. |
| **Text field** | 36px tall, 1px ink-400 border, 6px radius, 12px padding; focus: brand-600 border plus the focus ring; error: danger-600 border and a danger-600 message under the field, linked with `aria-describedby`. |
| **Tabs (header and page)** | underline style: ink-700 text, current tab ink-900 with a 3px brand-600 bar and `aria-current="page"`. |
| **Notice / toast** | white, 1px ink-200 border, a 4px status-colour bar on the left, an icon and text in ink-900, floating shadow; toasts sit bottom-right, slide in, disappear after 4 s, `role="status"`. |
| **Table** | white card; header row ink-100 with ink-600 13px/500 text; rows 56px, 1px ink-200 dividers, hover ink-100; the name cell is ink-900 500 with the URL under it in ink-600 small. Row actions are secondary buttons. |
| **Counter / badge** | full-round pill, 12px/500, status tint background with the status-700 text (for example "Broken links (3)" in danger-50/danger-600, U2). |
| **Modal** | 560px wide (full width under 782px), 14px radius, modal shadow, dimmed backdrop `rgba(16, 24, 40, 0.5)`; header with title (16px/600) and a close button; footer with actions right-aligned; focus is trapped and returns on close; Escape closes. |
| **Empty state** | inside a card: a 40px brand-50 circle with a brand-600 icon, a one-line message in ink-900 and, where there is one, the primary action. |
| **Logo mark** | five rounded petals around a circle, brand-500, drawn inline as SVG; our own, no Lasso artwork. |

## 7. Where Lasso and Affibloom differ (looks only)

| | Lasso | Affibloom |
|---|---|---|
| Header | purple bar, white tabs centred, 100px | white bar, ink tabs left-aligned with a blue underline, 64px |
| Brand colour | purple `#5E36CA`, actions green `#22BAA0` | ocean blue `#1E5BB8` for actions and accents |
| Type | Helvetica Neue, headings weight 800, 40px titles | system font, weight 600, 24px titles |
| Settings | two columns of cards, toggle left of the label, help in hover tooltips | one card per section, label and description left, toggle right, help visible |
| Buttons | 0.5rem radius, heavy shadow, drop on hover | 6px radius, flat, darker on hover |
| Toast | green box | white card with a status bar and icon |

## 8. Implementation notes

- Tokens are CSS custom properties on `.affibloom-admin` in `assets/admin/admin.css`; components use only tokens.
- Class names already in use stay (`affibloom-header`, `affibloom-tabs__tab`, `affibloom-toggle__input`,
  `affibloom-toast`, `#affibloom-settings-save`, …) so tests and later tasks keep working.
- Rules that must outrank wp-admin's (`.wrap h1`, `input[type=checkbox]`) are scoped under `.affibloom-admin`.
- `docs/ui/tokens.md` keeps Lasso's measured values as a reference only.
