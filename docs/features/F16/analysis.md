# F16 Display system: technical analysis

Status: **draft, gate 2**, 2026-11-13. Requirements: `requirements.md` in this folder.

## 1. How Lasso does it

### 1.1 Storage

One option, `lasso_settings`, holds six related keys (values below are the live site's):

| Key | Shape |
|---|---|
| `display_type` | `"Single"` — the site's default display type. **Not written by the Display tab**: the type segment only chooses which bucket is being edited |
| `theme_name` | `{single, grid, list}` (a legacy scalar is expanded to all three) |
| `display_colors` | `{single: {main, title, background, button, secondary_button, button_text, pros, cons, primary_button_text, secondary_button_text, badge_text}, grid: {…}, list: {…}}` |
| `display_density` | `{single: {preset, values: {gap, padding, spaceBetween, titleSize, titleLines, descSize, descLines, priceSize, buttonSize, imageHeight}}, grid: {…}, list: {…}}` |
| `display_elements` | `{single: {badge, image, title, price, prime, desc, proscons, primary, secondary, timestamp}, grid: {…}, list: {…}}` |
| `display_layout` | `{columns, single: {orientation, imagePosition, align, buttonAlign, ctaWidth, mobileOverflow, imageFit, mobile?: {…}}, grid: {…}, list: {…}}` |

Plus the flat legacy colour keys (`display_color_*`), the label keys (`primary_button_text`,
`secondary_button_text`, `badge_text`), `display_advanced_customization`, `show_price`, `show_disclosure`,
`show_disclosure_grid`, `show_disclosure_list`, `show_disclosure_table`, `link_from_display_title`,
`keep_site_stripe_ui`, `enable_brag_mode`, `lasso_affiliate_URL`, `custom_css`, `disclosure_text`.

The screen posts the five structured keys as **JSON in hidden inputs** (`display.php` lines 277-281);
`Lasso_Helper` canonicalizes each on read (`canonicalize_display_density/layout/colors`,
`canonicalize_theme_name`, `normalize_display_density_values`), so partial or legacy data always becomes a
complete structure.

### 1.2 Deciding whether the settings apply

`is_display_advanced_customization_enabled()` then `has_saved_display_v2_customization()`
(`class-helper.php` 2839-2930): on when the explicit flag is set, or when any density preset, density value,
column count, layout key, phone override or element toggle differs from the balanced defaults, or when the grid
or list colour bucket diverges from single. A filter (`lasso_display_v2_frontend_enabled`) can force it off;
admin previews set a per-request overlay that forces it on.

### 1.3 How it reaches the browser

`build_display_v2_frontend_wrapper_attributes()` (3356-3402) puts on the box wrapper:

- `data-layout-orientation|image-position|align|button-align|cta-width|mobile-overflow|image-fit`
- `data-layout-m-*` for the six phone keys, swapped into `data-layout-*` below 768px by a front-end script
- `data-density-title-lines`, `data-density-desc-lines`
- `data-v2-hide-<element>="1"` per hidden element, plus `data-v2-hide-disclosure`
- inline custom properties `--lasso-main|title|button|secondary-button|button-text|background|pros|cons`,
  `--lasso-density-preset|gap|padding|space-between|title-size|title-lines|desc-size|desc-lines|price-size|button-size|image-height`
  and `--lasso-layout-columns`

The CSS reading them is `lasso-display-v2-frontend.css` (~2,500 lines) layered over the theme CSS in
`lasso-live.min.css`. The 8 themes are three structural layouts — `views/displays/layout-2-box.php`,
`layout-3-box.php`, `layout-6-box.php` — with per-theme styling (Lab and Llama are 3-box; Cactus and Money are
6-box, each CTA in its own box; Splash and Geek reorder the boxes).

## 2. What Affibloom builds

Four layers, dependencies inward, as `00-overview.md` requires.

### 2.1 Domain (no WordPress calls)

- `Domain\Display\DisplayType` — single, grid, list (more as their features land).
- `Domain\Display\Theme` — the 8 names and the structural layout each uses.
- `Domain\Display\Density` — preset plus 10 values; the three preset maps; `with_value()` clamps to
  min/max/step and moves the preset to `custom`.
- `Domain\Display\Layout` — 7 desktop keys plus the optional phone override (6 keys), the orientation coercion
  rule (B2) and enum validation.
- `Domain\Display\Elements` — the 11 flags.
- `Domain\Display\Palette` — 8 colours and 3 label strings for one type.
- `Domain\Display\DisplayConfig` — one type's theme, density, layout, elements and palette.
- `Domain\Display\DisplaySettings` — the three configs, the grid column count and the site-wide flags;
  `is_customised()` implements B1.

Each is built from raw arrays by named factories that never throw on bad input: unknown enum goes to the
default, an out-of-range number is clamped, malformed JSON falls back to defaults (edge cases 1-4 and 7).

### 2.2 Application

- `Application\Display\DisplaySettingsService` — read for the screen, `update_type()` and `update_site_wide()`
  for saving, `reset()`; returns refused keys as `SettingsService` does today.
- `Application\Display\DisplayPresentation` — turns a `DisplayConfig` into the render contract: wrapper
  attributes, CSS custom properties, hidden-element flags. One place, used by the box, the preview and the
  tests.

### 2.3 Infrastructure

- The six structured keys live inside the existing `affibloom_settings` option under a `display` sub-array,
  written whole. No new table.
- `Infrastructure\WordPress\CustomCss` — stores the editor's CSS behind the same character allowlist Lasso
  uses (`format_css`) and prints it through `wp_add_inline_style()` on the box stylesheet (edge case 6).

### 2.4 Interface

- `Interface\Admin\DisplayScreen` — the screen in the Aurora design: type segment, preview, theme select, the
  Layout & spacing and Colors & labels panels, accordions, density chips, steppers, clamp groups, the phone
  note and its reset.
- `assets/admin/display-settings.js` — the first substantial admin script: keeps the hidden JSON in step with
  the controls, applies the coercion rules in the browser, switches type and preview width, re-renders the
  preview.
- The preview renders the **real box template** with a stub product through a REST route
  (`GET /affibloom/v1/display-preview`), so it cannot drift from the front end. This replaces Lasso's
  per-request overlay (B11).
- `Interface\Front\BoxAssets` prints the wrapper attributes and custom properties; a small front-end script
  does the 768px phone swap (B12).
- Filter `affibloom_display_advanced_enabled` for B13.

## 3. Risks and decisions to confirm

1. **The 8 themes are 8 visual designs.** Reproducing them is the largest single piece of this feature and each
   needs its own baselines. Only Lab (Single) and Cutter (Grid, List) are configured live; the other six are
   reachable from the select. Proposal: build all 8, in their own task after the engine, so the engine is
   testable first.
2. **Lasso's front-end CSS is ~2,500 lines** of compatibility rules between the layout engine and each theme.
   Affibloom writes its own against the same contract; parity is checked against new baselines per theme and
   layout, not against Lasso's CSS.
3. **Density presets exceed the stepper maximum** (`imageHeight` 320 > 280). Lasso does not clamp presets;
   Affibloom keeps that so migrated sites look identical, and clamps stepper edits only.
4. **Custom CSS.** Correction to an earlier draft of this file: Lasso does sanitise it. `Helper::format_css()`
   strips every character outside an allowlist, `<` included, so `</style>` cannot be injected, and the result
   is printed inside a `<style>` block. Affibloom prints it through `wp_add_inline_style()` on the box
   stylesheet, which is equivalent and needs no decision from the user.
5. **Grid and List are configured but unused in content.** Their configurations still migrate.

## 4. Proposed tasks (gate 3)

| Task | Entry point |
|---|---|
| 10A | Settings → Display: type segment, theme select, Colors & labels, save |
| 10B | Elements, density presets, spacing/typography/image steppers |
| 10C | Layout controls, orientation coercion, phone override and its reset |
| 10D | The live preview (REST route rendering the real box template), desktop and phone |
| 10E | Disclosure & behavior, Custom CSS, Reset Settings |
| 10F | Settings → Amazon in full (~20 controls) |
| 18x | Box rendering from the display contract (attributes, custom properties, phone swap) — rewrites Task 18 |
| 18y | The 8 themes and their three structural layouts, with baselines |

10A-10F keep the plan's numbering, so the rest of Phase 2 is unchanged; 18x and 18y replace Task 18's scope and
are written when Phase 3 is re-planned.
