# F5 Single product display (the box engine): analysis

Status: rewritten 2026-11-14 with `requirements.md` (sweep of Lasso 369). The first version (approved with Task 6,
2026-11-12: Tasks 18 and 22, ADR-006's own stylesheet, the Lab theme only) is replaced; its facts are kept below,
corrected where the source disagrees. Gate 4: waiting.

**Tasks** (in the Phase 3 file; they replace Phase 3's old Tasks 18-22):
18A the box reaches a reader (block render, Lab 3-box markup, base elements, link attributes, v2 wrapper) · 18B the
stylesheet, colour properties, Custom CSS; Lab pixels · 18C where assets load (Smart CSS Loading, late enqueue, phone
script, Font Awesome config, AMP, Jetpack, body class) · 18D Lasso's baselines for every theme and state, captured on
staging · 18E the 2-box themes and theme resolution · 18F the 6-box themes · 18G Llama · 18H layout controls and the
phone override · 18I density and typography · 18J element visibility · 18K classic rendering · 19A every button (F6,
F29) · 20A overrides: text and image · 20B overrides: theme, buttons, tracking ID, disclosure, anchor, mobile · 20C
overrides: price, Prime, basis price, rating, pros/cons, fields · 20D structured data · 21A Primary Rating stars (F8) ·
21B custom fields and Pros & Cons · 21C Copy to Clipboard field · 22A price, discount, Prime · 22B disclosure · 22C
timestamp · 22D brag · 22E SiteStripe · 10D Settings → Display preview · 14E product page preview.

## Depends on

- F16 (built in 10A-10C, 10E): `Domain\Display\*`, `DisplaySettingsService`, option `affibloom_display`, the flat
  display keys in `affibloom_settings`.
- F1 / F29 (the product, 14A-15D), F1 14C custom fields (`_affibloom_fields`, `FieldLibrary`), F28 (icon spans), F9.
- Decisions N1-N4 (requirements section 4) before 18A, 18C, 22D and 18D.
- Later consumers: 18S (Affibloom's shortcode), Task 27 (`[lasso]`), Phase 4 (block editor), F12/C3 (clicks), the
  links/cloaking area (public link), C1 (Amazon data), group displays (F23).

## Data

| Where | What | Lasso's key |
|---|---|---|
| Option `affibloom_display` | theme, density, elements, layout (+ phone), palette per type; columns | `theme_name`, `display_density`, `display_elements`, `display_layout`, `display_colors` |
| Option `affibloom_settings` (display) | `primary_button_text`, `secondary_button_text`, `disclosure_text`, `link_from_display_title`, `show_price`, `show_disclosure(_grid/_list/_table)`, `keep_site_stripe_ui`, `enable_brag_mode`, `brag_url`, `custom_css`, `show_prime`; new: `show_amazon_discount_pricing` (22A), `smart_css_loading`, `fontawesome_js_svg` (18C), `default_thumbnail` (18A) | same; `lasso_affiliate_URL` → `brag_url`; `enable_amazon_prime` → `show_prime`; `custom_css_default` → `custom_css` |
| Product meta | `_affibloom_theme`, `_affibloom_button_text`, `_affibloom_primary_position`, `_affibloom_buttons`, `_affibloom_new_tab`, `_affibloom_nofollow`, `_affibloom_sponsored`, `_affibloom_badge`, `_affibloom_price`, `_affibloom_show_price`, `_affibloom_description`, `_affibloom_disclosure`, `_affibloom_show_disclosure`, `_affibloom_prime`, `_affibloom_image_url`, `_affibloom_image_id`, `_affibloom_fields`; new: `_affibloom_show_description` (R22), `_affibloom_amazon_updated` (C1) | `custom_theme`, `buy_btn_text`, `second_btn_*`, `open_new_tab(2)`, `enable_nofollow(2)`, `enable_sponsored`, `badge_text`, `price`, `show_price`, `affiliate_desc`, `show_description`, `disclosure_text`, `show_disclosure`, `lasso_custom_thumbnail`, `lasso_field_mapping`, Amazon `last_updated` |
| Block `affibloom/product-box` | `productId` plus every Single override, camelCase of R65-R86: `badge`, `hideBadge`, `description`, `hideDescription`, `title`, `hideTitle`, `titleUrl`, `titleType`, `imageUrl`, `hideImage`, `imageAlt`, `theme`, `primaryUrl`, `primaryText`, `secondaryUrl`, `secondaryText`, `trackingId`, `disclosureText`, `anchorId`, `linkId`, `style`, `price`, `prime`, `basisPrice`, `rating`, `pros`, `cons`, `prosLabel`, `consLabel`, `fields`, `showProsCons`, `sitestripe`, `sitestripeStyle`, `schemaReview`, `schemaProsCons`, `schemaPrice`, `schemaPriceCurrency` | `[lasso …]` attributes |
| Files | `assets/front/box.css`, `box-layout.css`, `box-layout.js`, `copy-clipboard.js`, `box-amp.css`, `assets/front/images/{no-thumbnail.webp, prime.png, info.svg, brag.png}` | Lasso's assets |
| Tests | `tests/fixtures/baselines/themes/<theme>-<state>-<desktop\|mobile>.{png,html}` (18D) | — |

No new table; nothing is written during a page view (D2).

## Entry points and request paths

```
GET /some-post/   content: <!-- wp:affibloom/product-box {"productId":123,…} /-->            18A-18K, 19A-22E
  → wp_enqueue_scripts: BoxAssets::enqueue()   Smart CSS on: BoxDetector::page_has_box()        (18C)
  → wp_head: BoxAssets::print_head()  :root block, Custom CSS, Font Awesome config              (18B, 18C)
             SchemaMarkup::print()    first box with schema enabled                              (20D)
  → render_block → ProductBoxBlock::render( attrs )
       BoxOverrides::from_block( attrs )                                       (Domain\Box)
       ProductRepository::get( id ) → repoint resolver → null → ''
       BoxBuilder::build( product, DisplaySettings, overrides, context{post_id, in_widget} ) → BoxView
            ThemeResolver (site → product → box) → LayoutFamily (2, 3, 6)
            DisplayPresentation::wrapper( config, visibility )  data-*, style (F16 contract)
            element rules: badge, image, title, stars, price/Prime, description, fields, buttons, disclosure, date, brag
            LinkAttributes::for_primary() / for_secondary()
       BoxHtml::render( view ) → templates/front/box-{3,6,2,sitestripe}.php + partials
       BoxAssets::late_enqueue()
  → wp_footer: box-layout.js (advanced rendering only), copy-clipboard.js

POST /affibloom/v1/display-preview { type, theme, breakpoint, settings }                       (10D)
  → DisplayPreviewController (Capabilities::SETTINGS) → DisplaySettings::from_array (not saved)
  → PreviewProduct::sample( type ) → BoxBuilder (every element drawn) → BoxHtml → 200 { html }
     403 rest_forbidden; malformed settings fall back to defaults (F16 AC13), never 500

POST /affibloom/v1/products/{id}/preview { …the form's unsaved fields… }                        (14E)
  → ProductPreviewController (Capabilities::PRODUCTS) → ProductService::get + ProductInput::changes (not saved)
  → BoxBuilder (product page: hidden disclosure kept, R26) → 200 { html, additional_info }
     404 affibloom_not_found; 422 affibloom_invalid (data.fields)

npx playwright test -c baselines.config.ts --project=themes   (staging, Lasso active; N4)      (18D)
```

## Layers

- **Domain** `Box\BoxOverrides`, `Box\ThemeResolver`, `Box\LayoutFamily`, `Box\ElementVisibility`, `Box\PriceRule`,
  `Box\DisclosureRule`, `Box\TimestampRule`, `Box\DescriptionRule`, `Link\LinkAttributes`; reuses `Display\*`,
  `Product\*`, `Fields\*`. Pure PHP, unit-tested against Lasso's rules.
- **Application** `Box\BoxBuilder`, `Box\BoxView` (+ `BoxButton`, `BoxField`), `Display\DisplayPresentation` (F16's
  contract, first caller 18A), `Display\PreviewProduct`, `Box\SchemaBuilder`.
- **Infrastructure** `WordPress\BoxDetector` (content markers, usage index, Elementor data), `WordPress\CustomCss`
  (planned in 10E, first caller 18B).
- **Interface** `Block\ProductBoxBlock`, `Front\BoxHtml`, `Front\BoxAssets`, `Front\SchemaMarkup`,
  `Rest\DisplayPreviewController`, `Rest\ProductPreviewController`; `templates/front/*`; the previews in
  `display-settings.js` and `product.js`.

## Facts from Phase 1, rechecked 2026-11-14

- All 250 boxes use Lab, **rendered by Lasso's advanced (v2) engine** (`lasso-ds-v2-frontend`, spacious density, image
  left, full-width buttons, price/pros-cons/timestamp hidden), not Lab's plain layout (corrected 2026-11-14).
- Button icons come from the site's Additional CSS (`span.amazon-icon` and others); the same CSS also restyles
  `.lasso-display`, `.lasso-badge`, `.lasso-description` and `.lasso-fields-single` (N1).

## Risks

- **The CSS port** (N1): ~1,400 lines of Lasso's live rules and 3,169 of v2 rules, ported by theme family across 18B,
  18E-18G, each task carrying only what its baselines check.
- **Baselines exist only for Lab** until 18D; 18E onward waits for it (N4).
- **Hrefs of cloaked products** differ from Lasso's until the cloaking area exists.
- **Fluid font sizes** in the site's CSS (`calc(10px + 0.4vw)`): tests use Lasso's capture widths (1280, 390).
- **Late enqueue** prints CSS in the footer when detection misses (widgets, theme templates) (N2).
- Font and image rendering differences: `maxDiffPixelRatio` 0.01 (decision 9). LiteSpeed's CSS optimisation must not
  drop the stylesheets.

## Security

- Public and read only; nothing written on view. Output escaped or allowlisted (D4): icon spans, `wp_kses_post`
  descriptions, the price allowlist, `esc_url` on every `href`/`src`, the `title_type` allowlist.
- Custom CSS through the `format_css` allowlist, printed with `wp_add_inline_style`. Inline scripts only Lasso's two
  (phone layout as a file instead, D5; Font Awesome config).
- Preview routes: capability and REST nonce; unsaved values never stored; the product's post type checked.
- JSON-LD through `wp_json_encode`.

## Tests

- **Unit:** `ThemeResolverTest`, `ElementVisibilityTest`, `PriceRuleTest`, `DisclosureRuleTest`, `TimestampRuleTest`,
  `DescriptionRuleTest`, `LinkAttributesTest`, `BoxOverridesTest`, `BoxBuilderTest`, `DisplayPresentationTest`,
  `SchemaBuilderTest`, `BoxDetectorTest`.
- **Integration:** `ProductBoxBlockTest` (markup per layout), the two preview controllers.
- **E2E acceptance:** `F5.spec.ts` AC1-AC26 (pixels for AC1, AC5, AC8-AC14, AC23-AC24 against `variants/` and
  `themes/`; DOM for AC3-AC4; assets AC2, AC6; JSON-LD AC17; previews AC25-AC26); `F6`-`F8` specs; `F29` AC3, AC5; the
  front-end halves of `F16` AC2, AC3, AC5-AC10, AC12, AC13.
