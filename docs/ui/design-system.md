# Affibloom admin design system: Meadow

Status: **Approved** by the user on 2026-11-16 (deviation 142), built in Tasks MD1-MD6 (2026-11-17) and written up here
in Task MD7. Decision: ADR-008 and its Amendments. Scope: every admin screen, modal and notice, and the block's editor
UI. The reader-facing product box is **not** covered: it keeps the site's box design (ADR-006).

**What stays Lasso's:** labels, help texts, fields, tab and column order, and the steps of every workflow
(`docs/spec/interface.md`). **What is ours:** everything visual below, plus two content additions the user approved:
counts on the Dashboard (U9) and a one-line subtitle under each page title (U10).

## 0. Amendment, 2026-11-13: Lasso's structure on the Display screen

The user narrowed ADR-8 for **Settings → Display**: its columns, layout and the presentation of the settings and
the preview follow **Lasso exactly** (split card, preview with its toolbar on the left, a 380px settings rail on
the right holding the theme row, underline tabs, accordions and label-left / control-right rows). **Only the
colour scheme and the typography below are Affibloom's.** Where this document and Lasso's structure disagree on
that screen, the structure wins and the tokens still apply. The same holds for Settings → General and Amazon, which
have Lasso's two columns of cards and switch rows (deviation 96).

## 0b. History: Aurora, then Meadow

The first, plain design (ocean blue) was approved on 2026-11-12 and found too basic; on 2026-11-13 the user chose
**Aurora** of four directions (a navy header and navy-to-blue gradient band, floating white cards, icon chips). Aurora
felt heavy (a long product name made the band four lines tall, the Dashboard tiles wrapped to two rows, Save sat at the
foot of a 2,800px product page), so on 2026-11-16 the user compared five clickable directions (Meadow, Console, Split
View, Canvas, Nightshift) and **chose Meadow** with its four recommended answers: the 1320px column, bundled fonts, no
Discard button, the restyle in its own batches after Phase 3's exit check. Reference prototype:
https://claude.ai/artifact/VKuvkUctK992ethcke8ZMR (private to the user's claude.ai account). The user then made the
radii smaller (2026-11-17) and asked for one head on every screen (title left, search and buttons right) and for
skeletons instead of spinners (deviation 145). Aurora's sections are in git history before Task MD7.

## 1. Principles

1. **Calm and light.** A white top bar with underline navigation, no band, a botanical green brand, cards with a
   hairline border and a soft shadow; one strong element per screen (the title, the stat cards, the live preview).
2. **Always in reach.** The search under the Dashboard's head, the product preview sticky beside its form, Save
   Changes in a bar stuck to the foot of the window.
3. **Accessible by default.** WCAG 2.2 AA for every pair below, a visible focus ring on every control, help text read
   with its control (U3), status never by colour alone (a word, an icon or a tick goes with it).

## 2. Colour

Tokens are custom properties on `.affibloom-admin` (`assets/admin/admin.css` section 1).

| Token | Hex | Role |
|---|---|---|
| `--affibloom-brand-600` | `#1E6B47` | primary buttons, current nav item and tab, links, switches on, focus ring |
| `--affibloom-brand-700` | `#185A3B` | primary hover |
| `--affibloom-brand-800` | `#124A30` | primary pressed |
| `--affibloom-brand-50` | `#E3F0E7` | icon chips, selected rows, count pills, the selection row |
| `--affibloom-brand-25` | `#F1F7F2` | hover wash on quiet buttons, the quick-edit band |
| `--affibloom-brand-line` | `#C9E0D1` | border of tinted areas (selection row, quick-edit card, current view chip) |
| `--affibloom-brand-ring` | `rgba(30,107,71,.20)` | the 3px ring round a focused field |
| `--affibloom-ink-900` | `#16201A` | headings, body text, the toast |
| `--affibloom-ink-700` / `-600` | `#55625A` | secondary text, help texts, table headers, subtitles, inactive tabs |
| `--affibloom-ink-500` | `#6A766E` | URLs under names, meta, placeholders |
| `--affibloom-ink-400` | `#8A968C` | switch off, placeholder dashes |
| `--affibloom-line` | `#E3E8E1` | card borders, dividers |
| `--affibloom-control-line` | `#D3DBD1` | input and secondary button borders |
| `--affibloom-line-strong` | `#B6C1B4` | hover borders |
| `--affibloom-page` | `#F5F7F4` | page background |
| `--affibloom-subtle` | `#FAFBF9` | table header, card footers, the pager |
| `--affibloom-sunk` | `#F1F4F0` | stat chips waiting for their data, disabled fields |
| `--affibloom-segment-bg` | `#EAEFE9` | segmented control, progress track, pager hover |
| `--affibloom-skeleton` | `#E6EBE4` → `#F3F6F2` | loading shapes, a lighter band moving across |
| `--affibloom-success-700` / `-50` | `#1E6B47` / `#E3F0E7` | OK, saved |
| `--affibloom-danger-700` / `-800` / `-50` | `#B42318` / `#961C13` / `#FDECEA` | broken links, errors, Delete |
| `--affibloom-warning-700` / `-50` | `#86560F` / `#FBF2E1` | out of stock, needs attention (text) |
| `--affibloom-warning-icon` | `#B7791F` | warning glyphs, the "Unsaved changes" dot |
| `--affibloom-toast-icon` / `-tick` | `#9ED8B4` / `#124A30` | the toast's tick circle |

**Contrast (computed, WCAG 2.2):** white on brand-600 6.5:1; brand-600 on white 6.5:1, on brand-50 5.5:1; ink-900 on
page 15.5:1; ink-600 on white 6.4:1, on page 5.9:1, on brand-50 5.5:1; ink-500 on white 4.7:1, on subtle 4.6:1;
warning-700 on warning-50 5.6:1; danger-700 on danger-50 5.8:1; white on danger-700 6.6:1; white on ink-900 16.7:1.
UI parts: switch off on white 3.1:1; warning glyph on its tint 3.3:1; focus ring on page 6.0:1.

## 3. Typography

**Faces:** "Plus Jakarta Sans" 700/800 for titles, numbers and the wordmark; "Inter" 400/500/600 for everything else.
Both are bundled (WOFF2, Latin subset, `assets/admin/fonts/`, `fonts.css`, `font-display: swap`; Fontsource 5.3.0,
SIL OFL); loaded only on Affibloom's pages; no request to any other host. Tokens: `--affibloom-font`,
`--affibloom-font-display`, with `--affibloom-font-system` (WordPress's stack) as the fallback.

| Role | Value |
|---|---|
| Page title | Plus Jakarta Sans 36px / 1.12 / 800, −0.025em (28px from 782px down); a product's name two lines at most |
| Card title | Plus Jakarta Sans 18px / 26px / 700 (15px in the Share kit and the preview card) |
| Dialog title | Plus Jakarta Sans 22px / 1.25 / 800, −0.015em |
| Stat number | Plus Jakarta Sans 26px / 1 / 800, tabular figures |
| Table numbers (Locations, Clicks) | Plus Jakarta Sans 20px / 1 / 700, tabular figures |
| Wordmark | Plus Jakarta Sans 19px / 800 |
| Body, controls | Inter 14px / 20px / 400; labels and setting names 13–14px / 600 |
| Subtitle (U10) | Inter 15px / 22px, ink-600, at most 640px wide |
| Help text, URLs, small | Inter 12–13px / 1.45 |
| Table header | Inter 12px / 16px / 600, ink-600, sentence case |
| Eyebrow ("SAVED VIEW") | Inter 11px / 600, uppercase, 0.08em |

## 4. Space, shape, depth

- **Spacing:** 4px base. `--affibloom-space-gutter` 40px (the page's side gutters; 20px from 960px down); 32px between
  the head and the body and between sections; cards 24px above their title, 28px at the sides and foot; 22px between
  fields.
- **Rows and images:** Dashboard rows 80px with 52px image tiles; Groups and Fields rows keep their own height.
- **Radius (the user, 2026-11-17):** cards, stat cards, notices, dialogs, the save bar and icon cells 12px
  (`--affibloom-radius-card`); buttons, fields, thumbnails and icon chips 8px (`-control`, `-chip`); small buttons,
  menu items, page links and tooltips 6px (`-nav`); segments 5px (`-segment`); pills, view chips, search fields and
  switches fully round. A link styled as a button keeps its corners when focused or pressed.
- **Depth:** cards have a 1px `line` border and `--affibloom-shadow-card` (`0 1px 2px rgba(22,32,26,.04), 0 6px 16px
  -10px rgba(22,32,26,.10)`); menus, dialogs and the toast `--affibloom-shadow-toast` (`0 18px 40px -12px
  rgba(22,32,26,.24), 0 2px 6px rgba(22,32,26,.06)`); the save bar a shadow upwards.
- **Focus:** a 2px brand-600 outline with a 2px offset (inside tabs: −2px); fields take a brand-600 border and the
  3px brand-ring.
- **Motion:** 150ms on hover and switches, 140ms menu fade-in, 160ms quick-edit fade-in; skeletons move over 1.4s;
  all off under `prefers-reduced-motion`.

## 5. Layout

- **Header:** white, 64px, a 1px `line` bottom border, sticky under the WordPress toolbar. Left: the flower mark (five
  brand-600 petals round a white centre) and the wordmark in ink-900, linking to the Dashboard. Then ON1's navigation
  as words (14px/500 ink-600), the current one brand-600 with a 3px underline. Right: **Add New Link** as a primary
  split button whose caret opens the Menu. Below 992px the tabs fold behind the **Menu button**.
- **Notices:** white cards at the top of the column, under the header (see Components).
- **Page head** (`partials/page-head.php`): on the page background, in the column; the title with what belongs under
  it (subtitle, the product's link, the summary line, Settings' segmented tabs) on the left; the screen's head actions
  on the right, level with the title (the Dashboard's stat cards; Groups' and Fields' search and Add New; a group's
  Add a Link and Copy Shortcode). Under the row, full width: the Dashboard's search, the product page's tabs. A
  product page adds "‹ Dashboard" above and its 76px image tile left of the name.
- **Column:** centred, at most 1320px (`--affibloom-content-width`) inside the gutters; head, notices, body and footer
  line up with it.
- **Footer:** "Version x.y.z" under a hairline, 12px ink-600.
- **Dashboard (U9):** Broken links, Out of stock and Opportunities are stat cards right of the title (the filters come
  with 31B and O8; `.affibloom-tile*` stays); Products and Clicks (30 days) a quiet line under the subtitle; a
  full-width 48px search with the Ctrl+Shift+L hint; then one table card: the toolbar (SAVED VIEW chips, Filters) as
  its top row, which turns brand-50 while the selection bar takes the views' place; 80px rows; the pager in its foot.
  All eight columns fit a 1280px window.
- **Product page:** the head as above, underline tabs; the Share kit as one row; two columns, 58% form and 42%
  sticky aside (under the toolbar and header, 112px from the top; static and above the form from 991px down). The form
  is in cards in Lasso's order: *Links & buttons*, *Organize*, *Display content*, *Disclosure & link attributes*,
  then the read-only *Amazon Data*. The aside holds the *Live preview* card (the real box; its picture opens the Media
  Library). **Save bar:** Delete This Link left, Save Changes right, stuck to the foot of the window; "Unsaved changes"
  beside Save Changes while the form differs from what is stored. One Save Changes button.
- **Groups, a group, Fields, a field:** the same head, segmented tabs and table or form cards.
- **Settings:** General / Display / Amazon as a segmented control under the title; Lasso's structure (section 0).
- **Breakpoints:** 960px and below: gutters 20px. 991px (Lasso's 992px point): the header's tabs fold behind the Menu
  button, the product page stacks. 782px (wp-admin's mobile point): Menu and Add New Link show icons only, the head's
  actions go under the title, stat cards two a row, the Dashboard's table scrolls inside its card, footers stack.

## 6. Components

| Component | Spec |
|---|---|
| **Primary button** | brand-600, flat, white 14px/600, 38px tall, 18px side padding, 8px radius, `0 1px 2px rgba(18,74,48,.25)`; hover brand-700, pressed brand-800. |
| **Secondary button** | white, 1px control-line border, ink-900 text; hover subtle. |
| **Danger button** | danger-700, white text; hover danger-800; destructive actions only, always labelled. |
| **Small button** | 32px tall, 12px padding, 6px radius, 13px; 16px icons. In Dashboard rows (Edit, Shortcode) outlined in `line` with ink-600 text, `line-strong` and ink-900 on row hover, brand-600 on brand-25 on its own hover. |
| **Busy button** | `.is-busy`: disabled, a 14px spinner before the words. The only spinner (deviation 145). |
| **Switch** | 44×24 track, ink-400 off, brand-600 on, flat; the real checkbox lies over the track (`role="switch"`). |
| **Icon chip** | 40×40, 8px radius, brand-50 with a brand-600 icon; tints `--warning`, `--danger`; 36px in notices, 42px in stat cards, 44px in dialogs. |
| **Stat card** | (`.affibloom-tile`) white, `line` border, 12px radius, card depth, 12px 20px 12px 12px padding, at least 168px; a 42px chip, the 26px number (or a dash) over its 13px/500 label and an 11px note. Waiting for its data: the chip on `sunk`, the dash ink-400. |
| **Summary line** | (`.affibloom-summary`) 14px ink-600 label and 600 ink-900 number, items split by a 4px `line-strong` dot. |
| **Segmented tabs** | 3px padding on `segment-bg`, 8px radius, segments 5px; the current one white with ink-900 text and a soft shadow, `aria-current="page"`; scrolls sideways on a phone. |
| **Underline tabs** | (the product page) 30px apart on a 1px `line`, 14px/600 ink-600; the current one ink-900 with a 3px brand-600 bar; a later tab disabled with its "Soon" chip. |
| **Help tip** | an 18px round "?" button; on hover and focus an ink-900 tooltip with white 13px text, at most 280px, 6px radius; linked with `aria-describedby`. |
| **Text field** | 38px, control-line border, 8px radius; focus brand-600 border and the brand-ring; error danger-700 border and message below; placeholder ink-500. |
| **Search** | fully round; 42px in a head's actions; the Dashboard's 48px, 15px text, card shadow, the shortcut drawn as a key (2px bottom border). |
| **Table** | inside a card; the header row on subtle in the table-header type, 44px; rows divided by `line`; the name 15px/600 (brand-600 underlined on hover) with its URL in ink-500 under it; thumbnails with a `line` border and 8px radius. |
| **View chip** | (saved views) 32px, fully round, control-line border, ink-600 13px/500; hover `line-strong`; the current one brand-50 with a brand-line border, brand-600 600 text and a tick. |
| **Selection bar** | Lasso's links-selected bar: in the Dashboard's toolbar it takes the views' place and the row turns brand-50 (brand-line under it); the count in brand-600, then Select All, Clear Selection, Delete (danger) and Add to Group. On a group's page it is a brand-50 pill with a brand-line border. |
| **Quick edit row** | Lasso's editing mode (Task 16A): a brand-25 band holding a white card (brand-line border, 12px radius, 24px 28px padding, a 160ms fade-in); fields 2/3, the image panel 1/3 with its dashed frame and "Update Image" overlay; Save Changes and Cancel right under a divider. Sized to the visible part of the scrolling table; one column below 720px of it. |
| **Pager** | the card's foot on subtle: page size and range (ink-900, tabular) left, page links right: 32px, transparent, ink-600, `segment-bg` on hover; the current page a white raised chip with a control-line border. |
| **Group picker** | a field-sized box holding brand-50 chips (brand-600 13px/600, a round ×) and the entry; a white listbox under it (card shadow) ending with "Create “name”". Combobox over listbox. |
| **Group member row** | grip, tick, a 50px picture, name, description, Remove; drags by its grip; a ticked row brand-50. |
| **Status pill / state chip** | fully round, 12px/600 with a 6px dot or an icon: neutral (ink-700 on segment-bg), warning (warning-700 on warning-50), success (success-700 on success-50). |
| **Notice** | a white card (12px radius, `line` border, card shadow, 12px 12px 12px 14px padding), a 36px chip in the tone's tint, the message 600 ink-900; tones warning, success, error, update; a 32px round ×; `aria-live` region `#affibloom-notices`, `affibloomNotice( tone, message )`. |
| **Toast** | the ink-900 card (12px radius, toast shadow), a 24px tick circle (toast-icon / toast-tick); bottom centre, above the product page's save bar; the error variant a white cross on danger-700, shown longer; `role="status"`. |
| **Modal** | 560px (the editor's display modal 640px), 12px radius, toast shadow, backdrop `rgba(22,32,26,.45)`; head 28px padding with an optional 44px chip (danger on deletes, warning on Unsaved Changes) above the 22px title; body 16px 28px 28px; foot on subtle, actions right; focus trapped and returned; Escape closes. |
| **Type tile** | (the editor's "Choose a Display Type", Task 23A) four to a row (two on a phone), 12px apart: a white tile, `line` border, 12px radius, at least 112px tall, a 44px chip over the type's name (14px/600); hover brand-600 border on brand-25 with the card shadow. A type whose step is not built is on subtle with ink-500 text and a "Soon" chip in its corner (`aria-disabled`). |
| **Block panel** | (the Product box block in the editor canvas, Task 23A) a white card (`line` border, 12px radius, card shadow, 28px 24px padding), centred: the 36px flower, the prompt in 15px ink-600 and the primary button ("Loading…" with a spinner, disabled; "Add a Display"; "Retry Add a Display" in the warning tint with a warning-700 border). The canvas has no admin tokens, so the block's stylesheet carries Meadow's values. |
| **Menu** | a white card under its button (12px radius, `line` border, toast shadow, 8px padding, 300px); items with a chip, a 600 title and a 13px ink-600 line; brand-25 on hover. |
| **Menu button** | the header's, below 992px: 38px, a three-line icon and "Menu" (icon only from 782px); opens a white panel under the bar with the tabs stacked. |
| **Save bar** | the product page's footer: white, `line` border without a bottom edge, 12px top radius, 12px 20px padding, a shadow upwards, sticky at the foot; "Unsaved changes" (13px/600 with an 8px warning-icon dot) while the form has `data-dirty="true"`. |
| **Progress bar** | 8px track on segment-bg, fully round; a brand-600 fill that widens over 300ms; `role="progressbar"`. |
| **Skeleton** | content that waits shows its own shape in `--affibloom-skeleton`, a band moving across in 1.4s (still under reduced motion): list rows from `affibloomSkeleton()`, the box previews' `partials/box-skeleton.php`, reloading tables' cells. |
| **Empty state** | inside a card: a 40px chip, a 600 message, a 14px line and, where there is one, the primary action. |
| **Icon tile, icon grid** | an ink-900 square holding a button icon (48px in Settings → Display's grid, 28px in the picker); grid cells of at least 160px with a `line` border and 12px radius. |
| **Icons** | 20×20 outline set drawn for Affibloom (stroke 1.6, round caps), inline SVG from `partials/icon.php`; never Lasso's icons. |

## 7. Where Lasso and Affibloom differ (looks only)

| | Lasso | Affibloom (Meadow) |
|---|---|---|
| Header | purple bar, logo, centred white tabs, 100px | white 64px bar, green flower and wordmark, underlined word tabs on the left, green split button |
| Page head | title on the page, tabs to the right | title and subtitle left, the screen's search, buttons or stat cards right, on the page background |
| Brand | purple `#5E36CA`, actions green `#22BAA0` | botanical green `#1E6B47` on green-tinted greys |
| Type | Helvetica Neue, weight 800, 40px titles | Plus Jakarta Sans 800 titles at 36px, Inter for text |
| Product page | two cards, Save at the foot | 58/42 with a sticky preview, form in cards, a save bar stuck to the window's foot |
| Toast | green box | ink card with a tick, bottom centre |

## 8. Implementation notes

- Tokens are CSS custom properties on `.affibloom-admin` in `assets/admin/admin.css`; components use only tokens.
  The fonts are in `assets/admin/fonts.css`, enqueued before `admin.css` (`AdminAssets`).
- `admin.css` holds the components that screens already use; each later task adds the components it first needs from
  this document.
- Class names the tests and later tasks rely on stay: `affibloom-header`, `affibloom-header__tab`,
  `affibloom-page__title`, `affibloom-page__subtitle`, `affibloom-tabs__tab`, `affibloom-tile*`,
  `affibloom-toggle__input`, `affibloom-toggle__track`, `affibloom-toast`, `#affibloom-save`, `#affibloom-search`,
  `#affibloom-settings-save`, `.affibloom-card--list`, `.affibloom-product__footer`.
- Rules that must outrank wp-admin's (`.wrap h1`, `input[type=checkbox]`, `a:focus`) are scoped under `.affibloom-admin`.
- `tests/E2E/admin-design.spec.ts` checks the design by computed style, one test per screen part.
- `docs/ui/tokens.md` keeps Lasso's measured values as a reference only.
