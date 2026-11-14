# F16 Display system (Settings → Display): requirements

Status: **draft, gate 1**, 2026-11-13. Written under the full-Lasso scope rule (the user, 2026-11-13;
`progress.md` deviation 24), which replaced the earlier decision to build only the colour and label fields.
Evidence: `D:\lasso\lasso` (369 source, read only), the live `lasso_settings` option captured in Task 4
(`tests/fixtures/inventory/settings.json`), and the production screenshots `settings-display-*.jpg`.

**What this feature is:** the whole Settings → Display screen — its editing model, every control, the stored
settings and the rules that decide what the front end does with them. The box markup that consumes it is F5
(box rendering, to be rewritten); the Grid, List, Table, Gallery, Button, Image, AI-text and SiteStripe display
types are their own features, written next.

## User stories

1. As an editor I set how product boxes look site-wide, without writing CSS.
2. As an editor I keep a separate look for each display type (Single, Grid, List).
3. As an editor I see the box change as I edit it, at desktop (1280px) and phone (390px) width.
4. As an editor I give phones a different layout without touching the desktop one.
5. As an editor I hide any element of the box (badge, image, price, pros and cons, buttons, …).
6. As an editor I start from a spacing preset and then fine-tune individual numbers.
7. As an editor I undo everything with one Reset.
8. As an editor I add my own CSS when a control does not go far enough.
9. As a reader I see a box rendered with exactly those settings, on any device.

## 1. The editing model

- Three **display configurations**: `single`, `grid`, `list`. The screen edits one at a time, chosen with a
  segmented control that also switches the preview.
- Per configuration: **theme**, **density preset and values**, **element visibility**, **layout** (desktop and an
  optional phone override), **colours and labels**.
- Site-wide (not per type): grid **columns**, disclosure text, Show Price, the three disclosure scopes, Link from
  Display Title, Keep Site Stripe UI, Brag mode URL and toggle (not built: decision B9; removed in Task 2X6), Custom CSS, Reset Settings.

## 2. Controls

### 2.1 Theme (per type)

Select, 8 options: `Cactus`, `Cutter`, `Flow`, `Geek`, `Lab`, `Llama`, `Money`, `Splash`. **Default `Cactus`**
for all three types (`admin/lasso-constant.php` 179-183; an earlier draft of this file said `Lab`, which is this
site's chosen value, not the default). Live site: `{single: Lab, grid: Cutter, list: Cutter}`. Help: "Applies to the selected display type (Single,
Grid, or List)." A product may override the theme for its own box (product page field `Display Theme`).

### 2.2 Density (per type)

Preset chips `Compact`, `Balanced`, `Spacious`, plus a fourth state `custom` that the UI enters as soon as any
stepper is touched. Hint line per preset: "Tighter card spacing." / "Balanced is the default spacing preset." /
"More open card spacing." / "Custom spacing — stepper edits will keep this preset."

| Value | compact | balanced (default) | spacious | min | max | step |
|---|---|---|---|---|---|---|
| `gap` (gap between cards) | 12 | 20 | 32 | 0 | 48 | 2 |
| `padding` (card padding) | 8 | 16 | 26 | 0 | 48 | 2 |
| `spaceBetween` (space between elements) | 4 | 10 | 16 | 0 | 28 | 1 |
| `titleSize` | 24 | 26 | 30 | 11 | 40 | 1 |
| `titleLines` | 0 | 0 | 0 | clamp: 1, 2, 3, All (0) | | |
| `descSize` | 16 | 16 | 17 | 10 | 22 | 1 |
| `descLines` | 0 | 0 | 0 | clamp: 1, 2, 3, All (0) | | |
| `priceSize` | 14 | 16 | 19 | 10 | 26 | 1 |
| `buttonSize` | 15 | 15 | 15 | 10 | 22 | 1 |
| `imageHeight` | 140 | 280 | 320 | 48 | 280 | 4 |

The live site runs `spacious` on Single with the preset's own values (imageHeight 320, above the stepper's own
maximum of 280 — presets are not clamped to the stepper range; only stepper edits are).

Grouped in the UI as **Spacing** (gap, padding, spaceBetween), **Typography** (titleSize, titleLines, descSize,
descLines, priceSize, buttonSize) and **Image** (imageHeight).

### 2.3 Elements (per type)

11 toggles, all on by default: `badge` Badge, `image` Image, `title` Title, `price` Price, `prime` Prime badge,
`desc` Description, `proscons` Pros & cons, `primary` Primary button, `secondary` Secondary button,
`disclosure` Disclosure, `timestamp` Timestamp. The accordion header shows a summary ("n of 11 shown").
`disclosure` is not stored in the element map: it reads and writes `show_disclosure` (single),
`show_disclosure_grid` (grid) or `show_disclosure_list` (list).
Live site, Single: price off, proscons off, timestamp off; the rest on.

### 2.4 Layout (per type, desktop and phone)

| Control | Options | Default (single/list) | Default (grid) | Live (single) |
|---|---|---|---|---|
| Columns (grid only) | 1, 2, 3 | 3 (site-wide key) | 3 | 3 |
| Card layout (`orientation`) | Stacked, Side by side | side-by-side | stacked | side-by-side |
| Image position (`imagePosition`) | left, right (side-by-side) · top, bottom (stacked) | right | top | left |
| Text alignment (`align`) | Left, Center, Right | left | center | left |
| Button alignment (`buttonAlign`) | Spread, Left, Center, Right | spread | spread | spread |
| Button width (`ctaWidth`) | Hug, Full width | hug | hug | full |
| Mobile overflow (`mobileOverflow`) | Stack, Swipe row | stack | stack | stack |
| Image fit (`imageFit`) | Contain, Fill (`cover`) | contain | contain | contain |

**Which layout controls are visible when** (verified in `settings.js` 1412-1424):

- **Columns** only while the Grid type is selected.
- **Card layout (orientation)** is hidden for Grid — a grid card is always stacked, because a side-by-side card
  collapses in a narrow grid cell. Grid's stored orientation stays `stacked`.
- **Mobile overflow** appears only when the preview is at mobile width **and** the type is List.
- The phone note and its reset appear only while the preview is at mobile width.

**Phone override:** when the preview is at mobile width, layout edits write a phone-only layout. Only six keys
may differ: `orientation`, `imagePosition`, `align`, `buttonAlign`, `ctaWidth`, `imageFit` (not
`mobileOverflow`, not `columns`). A key that was never touched follows the **phone base**, which is desktop's
six keys with two exceptions (`Helper::get_display_layout_mobile_base`): **Button alignment is `center`** on
phones whatever desktop says, and Grid is `stacked`. Grid has no orientation override. An image-position
override is stored in the vocabulary of the phone orientation (coerced as B2). The phone layout the front end
uses is the base plus the overrides, with image position coerced once more
(`Helper::get_display_layout_mobile_for_type`). Lasso's help text ("Settings you haven't changed here follow
desktop") skips the Button alignment exception; Affibloom keeps the behaviour and the help text both. The panel shows "Same as
desktop" or "Custom mobile layout" with a **Use desktop layout** reset, and the help text: "Changes here apply
to phones only and desktop edits won't change them. Settings you haven't changed here follow desktop."

### 2.5 Colors & labels (per type)

Badge (`main`), Title, Background, Button + Badge Text, Pros, Cons, Primary button text, Primary button color,
Secondary button text, Secondary button color. **Lasso's defaults** for every type
(`admin/lasso-constant.php` 184-224): `main` `black`, `title` `black`, `background` `white`, `button` `#22BAA0`,
`secondary_button` `#22BAA0`, `button_text` `white`, `pros` `#22BAA0`, `cons` `#E06470`,
`primary_button_text` `Buy Now`, `secondary_button_text` `Our Review`, `badge_text` empty. Lasso writes the
first four as the CSS keywords `black` and `white`; **Affibloom stores the same colours as hex** (the user,
2026-11-13), so the picker and the stored value always agree. Keywords stay valid input and migrate
unchanged (edge case 7). Colour fields are text inputs with a picker and a placeholder;
the two button texts accept the site's icon markup (`<span class="…">`, decision 5). A `badge_text` default is
stored in the same bucket. Hint: "Colors and button labels apply to the selected display type."

### 2.6 Disclosure & behavior (site-wide)

Disclosure text (textarea), Show Price, Disclosure in Single Display, Disclosure in Grids, Disclosure in Lists &
Tables, Link from Display Title, Keep Site Stripe UI, ~~Brag mode URL, Enable Brag Mode~~ (not built: decision B9; Task 2X6 removes the controls 10E built).

**Disclosure in Lists & Tables is one visible toggle writing two stored keys**, `show_disclosure_list` and
`show_disclosure_table` (two hidden checkboxes, `display.php` 676-677). Affibloom keeps both keys, so a migrated
site and any future Table display agree.

### 2.7 Page actions

Custom CSS (textarea, applied to the admin preview and the front end), **Reset Settings**, **Save Changes**
(disabled until something changes).

Saving writes `custom_css` **and** `custom_css_default` with the same value (Lasso keeps a second copy).
Affibloom stores one value.

### 2.8 The complete set of keys this tab writes

From Lasso's own save handler (`pages/settings-general/class-ajax.php` 544-583) the Display tab writes exactly
these keys. This list is the completeness check for the feature:

`theme_name`, `display_colors`, `display_density`, `display_elements`, `display_layout`,
`display_color_main`, `display_color_title`, `display_color_background`, `display_color_button`,
`display_color_secondary_button`, `display_color_button_text`, `display_color_pros`, `display_color_cons`,
`primary_button_text`, `secondary_button_text`, `show_price`, `show_disclosure`, `show_disclosure_grid`,
`show_disclosure_list`, `show_disclosure_table`, `link_from_display_title`, `keep_site_stripe_ui`,
`disclosure_text`, `enable_brag_mode` (not built: B9), `lasso_affiliate_URL`, `custom_css`, `custom_css_default`.

Two keys belong to the display model but are **not** written here: `display_type` (the site's default display
type, set where a display is created — the type segment only chooses which bucket is being edited) and
`display_advanced_customization` (derived, see B1; no control on the screen sets it).

## 3. Behaviours

- **B1 — advanced rendering turns itself on.** The front end renders from these settings when *any* of the
  following differs from the balanced defaults: a density preset other than balanced/default/custom, any density
  value, the grid column count, any layout key, any phone override, any element toggle, or a colour bucket where
  grid or list diverges from single. The explicit `display_advanced_customization` flag only forces it on.
  **The live site qualifies** (`spacious` density on Single), so its boxes are rendered by this engine today.
- **B2 — image position follows orientation.** Side by side allows left/right (top→left, bottom→right);
  stacked allows top/bottom (left→top, right→bottom). Anything else falls back to the type's default.
- **B3 — phone override.** As 2.4. Writing a phone value never changes desktop; the reset clears the whole
  phone layout.
- **B4 — touching a stepper sets the preset to `custom`** and keeps the edited numbers.
- **B5 — the Disclosure element toggle writes the matching scope setting** (single / grid / list+table).
- **B5a — the Price element's default follows Show Price.** In Lasso's Display view the single type's `price`
  element defaults to the site-wide `show_price` setting (`display.php` 189), and the "is the layout customised"
  check skips that inherited value unless `display_elements` was actually saved (B1). Task 10E builds Show Price
  and must keep both.
- **B6 — control visibility** follows 2.4: Columns for Grid only, orientation hidden for Grid, mobile overflow
  only for List at mobile preview width.
- **B7 — a legacy scalar `theme_name`** (one theme for everything) is read as the same theme in all three
  buckets.
- **B8 — legacy flat colours** (`display_color_*`) are copied into all three buckets and, on their own, do
  **not** turn advanced rendering on; only per-type divergence does.
- **B9 — a product may override the theme** for its own box.
- **B10 — Reset Settings** returns every Display setting to its default.
- **B11 — the admin preview always renders with advanced rendering on**, through a per-request overlay, so the
  editor sees the result before saving.
- **B12 — phone values are swapped in by a front-end script below 768px**, so every rule keyed on the desktop
  attributes applies unchanged.
- **B13 — a filter may switch front-end rendering off** (`lasso_display_v2_frontend_enabled`); Affibloom needs
  its own equivalent.

## 4. Edge cases

1. Stepper edits clamp to the min/max/step in 2.2; preset values are not clamped (imageHeight 320 > max 280).
2. An unknown enum value (orientation, align, imageFit, …) falls back to that type's default.
3. `titleLines` / `descLines` `0` means "All" — no clamping of the text.
4. Settings travel as JSON in hidden inputs; malformed JSON must fall back to defaults, never fatal.
5. A configuration may exist for a display type that no post uses yet (grid and list on the live site).
6. Custom CSS is injected into both the admin preview and the front end, unescaped by Lasso — Affibloom must
   store it safely and print it as a stylesheet, never as HTML.
7. Colour fields accept any CSS colour string in Lasso (the live grid/list buckets hold `black` / `white`, not
   hex), so the stored value is not always a hex colour.
8. The density `imageHeight` applies per type; a theme may ignore it (2-box themes draw the image differently).
9. **Grid's orientation is pinned to `stacked` on the server** (`Helper::canonicalize_display_layout`), and its
   image position is coerced to match, whatever was stored or posted.
10. Lasso's server validates only `orientation`, `imagePosition` and `buttonAlign` in the desktop layout and lets
    any string through for `align`, `ctaWidth`, `mobileOverflow` and `imageFit`; the phone overrides validate all
    six. Affibloom validates every key in both (an unknown value falls back to the type's default), since an
    unknown value has no meaning to the box.
11. **Legacy flat layout** (keys at the top level instead of per type, from older Lasso versions) applies to
    Single and List, and Grid takes its defaults; when it is Lasso's old factory default (1 column, side by side,
    image right, align left) the column count becomes 3. Migration (Task 26) must apply this rule.
12. `columns` is clamped to 1-3 on the server; anything else becomes the nearest bound (default 3).

## 5. Acceptance criteria

- **AC1** Settings → Display edits Single, Grid and List separately; switching type switches every control and
  the preview to that type's stored values.
- **AC2** The preview shows the current settings at desktop and phone width, before saving.
- **AC3** Each of the 8 themes can be chosen per type and changes the preview and the front end.
- **AC4** The three density presets apply their value maps; a stepper edit moves the preset to `custom`, clamps
  to the documented range, and survives a save and reload.
- **AC5** Turning any of the 11 element toggles off removes that element from the preview and the front-end box;
  the Disclosure toggle writes the matching scope setting.
- **AC6** Every layout control changes the rendered box, and image position coerces as in B2.
- **AC7** A phone override applies below 768px only; untouched keys follow desktop; the reset clears it.
- **AC8** Colours and labels apply per type; the button texts keep `<span class="…">` markup.
- **AC9** Disclosure text, Show Price, the three disclosure scopes and Link from Display Title apply to the box.
- **AC10** Custom CSS reaches the preview and the front end, and cannot inject markup or script.
- **AC11** Reset Settings restores every default after a confirmation.
- **AC12** Settings migrated from Lasso reproduce the live boxes: Single = Lab, spacious, side by side, image
  left, full-width buttons, price/pros-cons/timestamp hidden.
- **AC13** A box renders correctly when the stored settings are missing, partial or malformed.

## 6. Depends on / feeds

- **Migration (Task 26)** must import every key in 2.1–2.7, not the 12 in the old plan.
- **Box rendering (F5)** consumes the settings: wrapper attributes, CSS custom properties and the phone swap.
- **Per-product overrides** (theme, price, badge text) belong to the product feature (F1) and are listed there.
- The **other display types** (Grid, List, Table, Gallery, Button, Image, AI text, SiteStripe) are separate
  features; this one defines the configuration they each get.
