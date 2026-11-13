# Affibloom admin design system: Aurora

Status: **Approved** by the user on 2026-11-13 (Task 9A). History: a first, plain proposal was approved with ocean
blue as the brand colour; the user then found it too basic, asked for four richer style directions and chose
**A — Aurora** (premium SaaS: a navy-to-blue gradient band, floating white cards, segmented tabs, icon chips).
Decision: ADR-008. Scope: every admin screen, modal and notice, and the block's editor UI. The reader-facing product
box is **not** covered: it keeps the site's box design (ADR-006).

**What stays Lasso's:** labels, help texts, fields, tab and column order, and the steps of every workflow
(`docs/spec/interface.md`). **What is ours:** everything visual below, plus two content additions the user approved
with Aurora: overview tiles on the Dashboard (U9) and a one-line subtitle under each page title (U10).

## 0. Amendment, 2026-11-13: Lasso's structure on the Display screen

The user narrowed ADR-8 for **Settings → Display**: its columns, layout and the presentation of the settings and
the preview follow **Lasso exactly** (split card, preview with its toolbar on the left, a 380px settings rail on
the right holding the theme row, underline tabs, accordions and label-left / control-right rows). **Only the
colour scheme and the typography below are Affibloom's.** Where this document and Lasso's structure disagree on
that screen, the structure wins and the tokens still apply. The other admin screens keep the Aurora layout until
the user says otherwise.

## 1. Principles

1. **Rich but calm.** One strong moment per screen (the gradient band); everything else is white cards on a cool grey
   page, with soft depth instead of borders.
2. **Scannable.** Every setting and every number has an icon; big numbers for counts; descriptions visible.
3. **Accessible by default.** WCAG 2.2 AA for every pair below, a visible focus ring on every control (white on navy,
   blue on light), help text read with its control (U3), status never by colour alone.

## 2. Colour

| Token | Hex | Role |
|---|---|---|
| `--affibloom-navy-900` | `#0B2A5B` | header bar, start of the band gradient |
| `--affibloom-brand-600` | `#1E5BB8` | end of the band gradient, primary buttons (bottom), toggles on, active tab bar, links, focus ring |
| `--affibloom-brand-500` | `#2563C9` | primary button gradient (top), toggle gradient end |
| `--affibloom-brand-300` | `#7FB0F5` | logo petals on navy (decorative) |
| `--affibloom-brand-50` | `#EAF1FC` | icon chips, brand tiles |
| `--affibloom-on-navy` | `#C9D8F0` | header navigation text on navy |
| `--affibloom-on-band` | `#D6E2F5` | subtitle and inactive band tabs on the band |
| `--affibloom-ink-900` | `#14213D` | headings, body text, toast background |
| `--affibloom-ink-700` | `#3D4A63` | secondary text, inactive segmented tabs |
| `--affibloom-ink-600` | `#5B6880` | descriptions, URLs, table headers, tile labels |
| `--affibloom-ink-400` | `#8A93A3` | toggle off |
| `--affibloom-line` | `#EDF1F7` | dividers inside cards (decorative) |
| `--affibloom-control-line` | `#D5DCE8` | secondary button and input borders |
| `--affibloom-page` | `#F3F6FB` | page background |
| `--affibloom-subtle` | `#FAFBFE` | card footers, table header |
| `--affibloom-white` | `#FFFFFF` | cards, white button on navy |
| `--affibloom-success-700` / `-50` | `#0B6B3A` / `#E8F7EF` | OK, saved |
| `--affibloom-danger-700` / `-50` | `#A1203A` / `#FDECEF` | broken links, errors, `Delete This Link` |
| `--affibloom-warning-700` / `-50` | `#8A4B00` / `#FFF4E0` | notices that need the editor's attention (Task 14A: "This product already exists."); 6.2:1 on its tint, 6.8:1 on white |
| `--affibloom-toast-icon` | `#22C55E` | the toast's check circle (with a `#06210F` tick) |

**Contrast (computed, WCAG 2.2):** white on navy-900 14.0:1, on brand-600 6.5:1, on brand-500 5.7:1; on-navy on
navy-900 9.7:1; on-band on navy-900 10.7:1 and on brand-600 4.95:1 (the band's lightest end); ink-900 on page
14.8:1; ink-700 on white 8.9:1; ink-600 on white 5.6:1, on subtle 5.4:1, on page 5.2:1; brand-600 on brand-50 5.7:1;
white on ink-900 16.0:1; success-700 on success-50 6.0:1; danger-700 on danger-50 6.6:1; navy-900 on white 14.0:1.
UI parts: toggle off (ink-400) on white 3.1:1; focus ring brand-600 on page 6.0:1, white on navy 14.0:1.

## 3. Typography

- **Font:** the system stack WordPress uses: `-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans,
  Ubuntu, Cantarell, "Helvetica Neue", sans-serif`. No web font, no third-party request.

| Role | Value |
|---|---|
| Page title (`h1`, on the band, white) | 30px / 38px / 700 |
| Card title (`h2`) | 18px / 26px / 700 |
| Tile number | 26px / 30px / 700, tabular figures |
| Body, controls | 14px / 20px / 400; labels and setting names 600 |
| Small: descriptions, URLs, subtitles, tile labels | 13px / 18px / 400 (subtitle 14px) |
| Table header | 12px / 16px / 600, uppercase, 0.04em letter spacing |
| Wordmark | 17px / 24px / 700 |

## 4. Space, shape, depth

- **Spacing (4px base):** 4, 8, 12, 16, 20, 24, 32, 40, 48.
- **Room (the user, 2026-11-13: "make all admin pages spacious"):** every screen gets Lasso's breathing room, which
  uses 24px inside a row and 48px around a section. Three tokens carry it: `--affibloom-space-gutter` 40px (the page's
  side gutters; 20px from 960px down), `--affibloom-space-section` 28px (between cards and sections),
  `--affibloom-space-card` 32px (a card's inner padding). Table rows have 20px padding and 48px images, so a row is
  about 80px tall, as Lasso's is; settings rows 20px. The Display screen's rail keeps Lasso's measurements (section 0).
- **Radius:** 16px cards and tiles; 12px icon chips; 10px buttons and the segmented control; 7px segments; 8px
  header navigation items; full round for toggles and status pills.
- **Depth:** cards and tiles `0 1px 2px rgba(16,24,40,.06), 0 8px 24px rgba(16,24,40,.08)`, no border; toast
  `0 12px 32px rgba(16,24,40,.25)`; the active segment `0 1px 3px rgba(16,24,40,.12)`.
- **Motion:** 150 ms ease on hover and toggles; the toast slides in over 200 ms and leaves after 4 s; all off under
  `prefers-reduced-motion`.

## 5. Layout

- **Header:** navy-900 bar, 60px. Left: the logo mark (five petals in brand-300 around a navy ring and a white dot,
  28px) and the wordmark in white. Then the navigation (Dashboard, Settings), each an icon plus label in on-navy text,
  8px radius; the current one white text on `rgba(255,255,255,.12)`. Right: the screen's main action as a white button
  with navy text (`Add New Link`, from Task 11A), split as in Lasso: its caret opens a Menu of other ways to add (Task 11C).
- **Band:** under the header, full width, `linear-gradient(120deg, navy-900, brand-600)`, padding 28px 32px 72px. It
  holds the page title, the subtitle (U10) and, on screens with sections, the section tabs (segmented control on the
  band: inactive on-band text on `rgba(255,255,255,.12)`, active white segment with ink-900 text).
- **One centred column** (the user, 2026-11-13: "centred, like Lasso"): the band's content and the body share a column
  of at most 1080px (`--affibloom-content-width`), centred in the content area, with 32px gutters (20px from 960px
  down). **The Dashboard is wider, 1320px** (`.affibloom-page--wide`; the user, 2026-11-13): its table has eight
  columns, and 1320px is Lasso's own container width on large screens. Settings keep 1080px. The band's gradient still runs the full width; the header stays full width with the logo on the left.
- **Body:** the first row of tiles or the first card overlaps the band by 48px, so content floats over it. Cards fill
  the column's width (forms included).
- **Dashboard:** a row of overview tiles (U9), then the products card. Each tile appears with the task that provides
  its number (products: Task 13, broken links: Task 31, clicks over 30 days: Task 34).
- **Cards:** the full width of the column. Header (title and intro), body, footer (subtle background,
  actions right-aligned: secondary then primary).
- **Settings rows:** a 40px icon chip, the setting's label (600) with its description below it in ink-600 (Lasso's
  help text, visible, linked with `aria-describedby`), the switch on the right; rows divided by `line`.
- **Breakpoints:** 960px and below: side padding 20px. 782px (wp-admin's mobile breakpoint): the header navigation
  shows icons only, rows stack (switch under the text), tiles stack, card footer buttons go full width.

## 6. Components

| Component | Spec |
|---|---|
| **Primary button** | `linear-gradient(180deg, brand-500, brand-600)`, white 14px/600 text, 38px tall, 18px side padding, 10px radius, `0 1px 2px rgba(16,24,40,.12)` plus a 1px inner highlight; hover: brand-600 flat; focus ring. |
| **White button (on navy)** | white background, navy-900 text; used for the header's main action. |
| **Secondary button** | white, 1px control-line border, ink-900 text; hover subtle background. |
| **Danger button** | danger-700 background, white text, only for destructive actions, always explicitly labelled. |
| **Small button** | 32px tall, 12px padding, 8px radius (table row actions). |
| **Focus ring** | 2px brand-600 outline, 2px offset; on the navy header and band 2px white. |
| **Toggle** | 44×24 track, ink-400 off, `linear-gradient(120deg, brand-600, brand-500)` on, 18px white knob with a small shadow; the real checkbox lies over the track (`role="switch"`). |
| **Icon chip** | 40×40, 12px radius, brand-50 background, brand-600 icon (18px, 1.6px stroke); danger or success tints for status. |
| **Overview tile** | white card (16px radius, card depth), icon chip, number (26px/700) and label (13px ink-600). |
| **Segmented tabs** | 4px padding, 10px radius, `aria-current="page"` on the active segment; on light pages ink-700 on `#E6ECF6` with a white active segment; on the band as above. |
| **Help tip** | for dense places (form fields, table headers): an 18px round "?" button; on hover and focus an ink-900 tooltip with white 13px text, max 280px, 8px radius; text linked with `aria-describedby`. Settings rows show the text as a description instead. |
| **Text field** | 38px tall, 1px control-line border, 10px radius; focus: brand-600 border and focus ring; error: danger-700 border and message below, linked with `aria-describedby`. |
| **Table** | inside a card; header row on subtle with the table-header type; rows with 12px padding and `line` dividers; the name 600 with the URL below in ink-600; 40px rounded thumbnails. |
| **Status pill** | full round, 12px/600, tint background with the status-700 text and an icon (`OK`, `Broken`). |
| **Toast** | ink-900 card, white text, 12px radius, toast shadow, a 24px toast-icon circle with a dark tick; bottom right; `role="status"`. (Its "info" variant went with the Dashboard's duplicate toast in Task 14A: the warning now shows on the product's page, as Lasso's does.) |
| **Icon tile** | a 36px ink-900 square (8px radius) holding a 20px button icon, so the site's white glyphs show as they do on a button (Settings → Display, "Button icons"; Task 14F). |
| **Notice** | above a page's cards, as Lasso's notifications: a white card (16px radius, card shadow) with a 4px warning-700 left edge, a 40px icon chip in warning-50 / warning-700, and the message (600, ink-900); `role="status"` (Task 14A). |
| **Modal** | 560px, 16px radius, toast shadow, backdrop `rgba(11,42,91,.45)`; header, body, footer like cards; focus trapped and returned; Escape closes. |
| **Menu** | a white card under its button (12px radius, card shadow, 8px padding, 300px wide, at most the screen less 32px); items 8px padded with an 8px radius, a 40px icon chip, a 600 title and a 13px ink-600 description, subtle background on hover. The button has `aria-expanded` and `aria-controls`; choosing an item, Escape or a click elsewhere closes it (Task 11C). |
| **Progress bar** | 8px track in `#E6ECF6`, full round; the fill a `brand-500` to `brand-600` gradient that widens over 300 ms; `role="progressbar"` with `aria-valuenow` (Task 11C). |
| **Empty state** | inside a card: a 40px icon chip, a one-line message (600) and, where there is one, the primary action. |
| **Icons** | 20×20 outline set drawn for Affibloom (stroke 1.6, round caps), inline SVG from one partial; never Lasso's icons. |

## 7. Where Lasso and Affibloom differ (looks only)

| | Lasso | Affibloom (Aurora) |
|---|---|---|
| Header | purple bar, logo, centred white tabs, 100px | navy bar, petal logo, icon-plus-label navigation on the left, 60px |
| Page head | title on the page, tabs to the right | navy-to-blue gradient band with title, subtitle and segmented tabs; content floats over it |
| Brand | purple `#5E36CA`, actions green `#22BAA0` | navy `#0B2A5B` and ocean blue `#1E5BB8` |
| Type | Helvetica Neue, weight 800, 40px titles | system font, 700 titles at 30px |
| Settings | two columns of cards, toggle left of the label, hover-only help | one card per section, icon chip, label and visible description, switch on the right |
| Toast | green box | dark card with a check icon |

## 8. Implementation notes

- Tokens are CSS custom properties on `.affibloom-admin` in `assets/admin/admin.css`; components use only tokens.
- `admin.css` holds the components that screens already use; each later task adds the components it first needs from
  this document.
- Class names the tests and later tasks rely on stay: `affibloom-header`, `affibloom-header__tab`,
  `affibloom-page__title`, `affibloom-tabs__tab`, `affibloom-toggle__input`, `affibloom-toggle__track`,
  `affibloom-toast`, `#affibloom-settings-save`.
- Rules that must outrank wp-admin's (`.wrap h1`, `input[type=checkbox]`) are scoped under `.affibloom-admin`.
- `docs/ui/tokens.md` keeps Lasso's measured values as a reference only.
