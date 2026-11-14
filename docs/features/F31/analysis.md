# F31 Shortcodes: analysis

Status: gate 2 written 2026-11-14 with `requirements.md`. Gate 4 for Tasks 18S1-18S14, 27A-27I, 27X1-27X8: waiting.

**Tasks.** Phase 3, after Tasks 18-22: 18S1 `[affibloom id|slug]` draws a product's box (entry point, addressing,
failure, anchor, theme) · 18S2 text and link overrides · 18S3 value overrides (price, Prime, rating, pros/cons, fields,
disclosure, style, tracking ID) · 18S4 `type="button"` and `type="image"` · 18S5 `type="link"`, SiteStripe,
`type="field"` · 18S6 `amazon_url` / `geni_url` addressing · 18S7 group displays by shortcode (after the group-display
task) · 18S8 table display by shortcode (after the Tables feature) · 18S9 widgets, AMP, feeds · 18S10 Dashboard row
**Shortcode** · 18S11 Share kit **Copy shortcode** · 18S12 group **Copy Shortcode** · 18S13 table **Copy Shortcode** ·
18S14 review and pros/cons schema in the page head. Phase 5, replacing Task 27 (after Task 26): 27A `[lasso]` alias,
single box · 27B `[lasso]` button, image, link, SiteStripe, fields · 27C `[lasso amazon_url|geni_url]` · 27D `[lasso]`
group displays · 27E `[lasso type="table"]` · 27F `[lasso prompt]` (N1) · 27G render-time content repairs · 27H
`[lasso]` in widgets and on AMP · 27I `[lasso-attr]` · 27X1 `[amazon]`/`[aawp]` · 27X2 `[amalinkspro]` · 27X3 EasyAzon
links · 27X4 EasyAzon image, CTA, info block · 27X5 Earnist · 27X6 Easy Affiliate Links · 27X7 ThirstyAffiliates ·
27X8 `[lasso]` back to the original plugin's shortcode.

> **Names settled 2026-11-14 (planning pass):** the per-box overrides class is `Domain\Box\BoxOverrides` (created by Task 18A), not `BoxOptions`; the structured-data builder is Task 20D's `Application\Box\SchemaBuilder`; 18S8 is superseded by F25 T12. Read `BoxOptions` below as `BoxOverrides`.

## Depends on

- Phase 3's box (Tasks 18-22: `BoxHtml`, `BoxBuilder`, `BoxOptions`, `BoxView`, `templates/box.php`, `BoxAssets`),
  F5-F8, F29, F28, F16 (themes per type, Elements, v2, disclosure per type, columns), F2/F17 (Amazon tagging,
  tracking-ID whitelist, A38), F1 (product, slug, `_affibloom_repointed_to`, the resolver of Task 18).
- F23 (`GroupService::members()`, D12) and the Phase 3 group-display task for 18S7, 18S12, 27D; the Tables feature for
  18S8, 18S13, 27E.
- Task 26 (`_affibloom_lasso_id`; groups by slug; tables by Lasso ID) for every 27 task; ADR-3; deviation 48.
- F19 Task 14 for 18S10; F1 D4 for 18S11; F23 D5 for 18S12.

## Data

Nothing is written (no option, meta or table). Read:

| Where | What | Lasso's source |
|---|---|---|
| `affibloom_product` posts | the product (published only, R14), `post_name` = `slug`, `_affibloom_repointed_to` (A5), ASIN and country (A6), destination `_affibloom_url` (A7) | `lasso-urls`, `post_name`, `lasso_repointed_to_lasso_id`, `wp_lasso_url_details` |
| meta `_affibloom_lasso_id` | Lasso record → product (Task 26) | `lasso-urls` ID |
| taxonomy `affibloom_group`, term meta `_affibloom_order` | group by slug, members in order | `lasso-cat`, `wp_lasso_category_order` |
| the Tables feature's storage | table by ID, its Lasso ID (27E) | `wp_lasso_table_details`, `_mapping` |
| `affibloom_settings` (F16, F17) | themes, Elements, v2, `show_disclosure_grid`, `link_from_display_title`, SiteStripe switch, tracking IDs | `lasso_settings` |
| Lasso tables, read only, when present | `wp_lasso_link_locations` (A3 without `id`), `wp_lasso_revert` (R21, R26, AAWP tables) | same |
| other plugins' data, read only | `easyazon_item_<asin>` (R25), `_ta_destination_url` (R28), transient `lasso_chatgpt_response_*` (N1), postmeta `lasso_attributes_*` (R3) | same |

## Entry points and request paths

```
the_content → do_shortcode
[affibloom …] → Interface\Front\AffibloomShortcode::render(atts)              (18S1-18S9)
  → Domain\Shortcode\ShortcodeAttributes::from_raw   R4, R5, A10, A44, A46
  → Domain\Shortcode\DisplayRequest::from            R8 dispatch: kind + Address + overrides
  → Application\Shortcode\ShortcodeRenderer::render
       product → ProductResolver::published(id|slug|asin|destination) (repoint, R14)
       group → GroupService::members(slug) · table → TableService::get(id)
       BoxOptions::from_request (A18-A38) · AnchorId (D5)
       → BoxHtml | ButtonHtml | ImageHtml | LinkHtml | SiteStripeHtml | FieldHtml | GroupHtml | TableHtml
  ← HTML, or '' + do_action('affibloom_box_failed') + debug log (D4)
BoxAssets::has_box() also sees [affibloom] (Lasso off: [lasso] and the 27X names)
[lasso …] (init 20, only without LASSO_PLUGIN_PATH and an existing 'lasso' shortcode)        (27A-27H)
  → Interface\Legacy\LassoShortcode → ShortcodeAttributes → Application\Legacy\LegacyAddress
       slug (any status) > id > link_id (LassoLocationMap) > ref; amazon_url, geni_url; category|categories; table → LassoIdMap
  → same DisplayRequest / ShortcodeRenderer (+ A41-A43 plain link, A15, A17)
  prompt, no product → Infrastructure\Lasso\LassoAiCache → ai-text | ''                   (27F)
[lasso-attr] → Interface\Legacy\LassoAttrShortcode → LassoAttributeMeta                  (27I)
[amazon|aawp], [amalinkspro], [easyazon_*], [earnist|earnist_link], [eafl] (EAFL active), [thirstylink]
  → Interface\Legacy\{Aawp,AmaLinks,EasyAzon,Earnist,Eafl,Thirsty}Shortcode → Application\Legacy\CompetitorProduct
  → renderer | that plugin's renderer | Lasso's fallback (R23-R28)                         (27X1-27X7)
the_content 1/10, Lasso off → Interface\Legacy\LegacyContentFilter: R22 repairs, <ta> (R28), R21 back-conversion; rendered content only (27G, 27X7, 27X8)
widget_text 1 → Interface\Front\WidgetShortcodes (origin="widget" after `[affibloom ` / `[lasso `)     (18S9, 27H)
amp_post_template_css 1000 → Interface\Front\AmpBoxCss (once)                                       (18S9, 27H)
wp_head → Interface\Front\BoxSchema (first box with schema_* = enable)                             (18S14)
Copy controls (no server call): product-row.php → dashboard.js; product.php Share kit → product-save.js; group.php → groups.js; the table screen → its script; strings from Interface\ShortcodeText::{product,group,table}; admin.js affibloomCopy(text, button, {label, ms})   (18S10-18S13)
```

## Layers

- **Domain** `Shortcode\ShortcodeAttributes`, `DisplayKind` (Single, Button, Image, Link, SiteStripe, Field, Grid,
  List, Gallery, Table, AiText), `Address`, `DisplayRequest` (R8 as a table-driven function), `AnchorId`, `FieldPart`,
  `OriginalShortcode` (27X8); pure PHP.
- **Application** `Shortcode\ShortcodeRenderer`, `Shortcode\SchemaBuilder`, `Box\BoxOptions` (A18-A38; replaces Task
  27's `LegacyBoxOptions`), `Products\ProductResolver::published()`, `Legacy\LegacyAddress`, `Legacy\CompetitorProduct`.
- **Infrastructure** `Lasso\LassoIdMap` (Task 26), `Lasso\LassoLocationMap`, `Lasso\LassoRevertLookup`,
  `Lasso\LassoAiCache`, `Lasso\LassoAttributeMeta`; `WordPress\PostTypeProductRepository::by_slug()`, `by_asin()`,
  `by_destination()`.
- **Interface** `Front\{AffibloomShortcode, WidgetShortcodes, AmpBoxCss, BoxSchema, ButtonHtml, ImageHtml, LinkHtml,
  SiteStripeHtml, FieldHtml}`; `Legacy\{LassoShortcode, LassoAttrShortcode, LegacyContentFilter, AawpShortcode,
  AmaLinksShortcode, EasyAzonShortcodes, EarnistShortcodes, EaflShortcode, ThirstyShortcode}`; `ShortcodeText`;
  `templates/front/{button,image,sitestripe,field,ai-text}.php`. Each a `Module` in `Plugin.php`.

## Risks

- Parity of the 250 boxes rests on Phase 3's box; Task 29 is the check. `data-lasso-id` must be settled (click
  feature) before 27A.
- `link_id` without `id` needs `wp_lasso_link_locations`, which Lasso's uninstall may drop; no site box needs it. Task
  26 could copy the map.
- Generic names (`[amazon]`): registered only with Lasso off; Affibloom delegates to AAWP as Lasso does. Who wins when
  Lasso and AAWP are both active: UNVERIFIED.
- Group displays apply per-box overrides to every card (R12); 18S7 tests it.
- Performance: per-request product cache, as Lasso's flag does (R19).
- Plan text to correct at gate 4: Phase 5 Task 27; Phase 3's "Task 18S (to be written)"; overview lines 16, 136, 336;
  `interface.md` rows 37 and 62; ADR-003 Consequences; F10 analysis task list.

## Security

- Shortcodes run for anonymous readers on content any `edit_posts` user wrote: URLs go through `esc_url`;
  `title_type`, `theme`, `bullets`, `columns`, `button_type`, `type`, `value` are allowlisted; `limit` is an integer;
  `sitestripe_style` and `anchor_id` go through `esc_attr` (Lasso prints `sitestripe_style` raw); texts are escaped,
  icon spans limited to `span[class]`, descriptions through `wp_kses_post`; `[lasso-attr]` output goes through
  `wp_kses_post` (Lasso prints it raw).
- `slug` and `ref` find any status, but only a published product is drawn; `demo` draws only the placeholder.
- The copy controls send nothing to the server. No request leaves the site (N1). No post content is written (D7).

## Tests

- **Unit** `ShortcodeAttributesTest`, `DisplayRequestTest` (one test per R8 / A10-A14 row), `AnchorIdTest`,
  `AddressTest`, `BoxOptionsTest` (A18-A38), `LegacyAddressTest`, `CompetitorProductTest`, `FieldPartTest`,
  `SchemaBuilderTest`, `LassoAiCacheTest`, `OriginalShortcodeTest`.
- **Integration** `do_shortcode` with factory products, groups and tables, one test per task's AC;
  `LassoShortcodeTest` keeps Task 27's two tests.
- **E2E** `tests/E2E/acceptance/F31.spec.ts`: AC1-AC14 with `[affibloom]` pages made through WP-CLI; AC15-AC31 with
  Lasso deactivated (AC15 points at Task 29); AC10-AC13 read the clipboard (`clipboard-read`).
