# F30 Display types: analysis

Status: gate 2 written 2026-11-14 with `requirements.md`. Gate 4 for Tasks DT1-DT10: waiting.

**Tasks:** DT1 (Lasso baselines for these types, from staging), DT2 (a group as a grid, customised settings), DT3
(the grid with default settings: rows, `columns`, `compact`), DT4 (a group as a list, with the phone swipe row), DT5
(a product as an image), DT6 (a group as a gallery), DT7 (a product as a button), DT8 (the SiteStripe card), DT9
(stored AI text), DT10 (the AI text generator — dormant, only if the user chooses N2 b). Phase 3, after Tasks 18-22,
18x/18y (the display contract and the 8 themes) and 18S (Affibloom's shortcode).

## Depends on

- **Task 18S** — Affibloom's shortcode is every task's entry point (D1). **Tasks 18, 18x, 18y** — the Single box
  (`BoxBuilder`, `BoxView`, `templates/box.php`), the display contract (wrapper attributes, custom properties, the
  phone swap, F16 B1/B11/B12) and the 8 themes: Grid cards are Single boxes in the Grid theme (Cutter on this site).
  Tasks 19-22 (second button and F29's extra buttons, per-box text, rating, Prime) for the card elements.
- **F23 Tasks G1-G4** — the `affibloom_group` taxonomy, `_affibloom_order`, `GroupService::members()` (D12).
- **F16 Tasks 10A-10E** — `DisplaySettings` per type (`src/Domain/Display/`), Show Price, the disclosure scopes,
  Keep Site Stripe UI, Link from Display Title, brag mode, Custom CSS.
- **F1** — product fields read per card (Show Price, Show Disclosure, disclosure text, Display Theme, secondary URL,
  rating, fields). **F12 / Task 18** — the link and click attributes (`LinkAttributes`, the location id).
- ADR-6 (own stylesheet and class names, pixel parity), ADR-7 (plain scripts), decision 5 (`span[class]` allowlist for
  button texts).

## Data

| Where | What | From Lasso |
|---|---|---|
| Taxonomy `affibloom_group`, term meta `_affibloom_order` (F23) | read: a group's members and order | `lasso-cat`, `lasso_category_order` |
| `affibloom_product` posts and meta (F1) | read per card: name, image, links, buttons, price, Show Price, Prime, rating, fields, description, Show Disclosure, disclosure text, theme | `lasso-urls` and their meta |
| Option `affibloom_settings` → `display` (F16) | read: `theme_name`, `display_colors`, `display_density`, `display_elements`, `display_layout` (Grid and List buckets, `columns`) | the same keys in `lasso_settings` |
| Option `affibloom_settings` (Task 10E) | read: `show_disclosure_grid`, `disclosure_text`, `keep_site_stripe_ui`, `link_from_display_title`, `enable_brag_mode`, brag URL, `custom_css` | the same keys |
| Option `affibloom_ai_texts` (new, DT9) | `{ "<format>:<md5(prompt)>": { prompt, format, html, updated } }`, autoload off; written by Task 26 (and by DT10's generator if N2 b) | transients `lasso_chatgpt_response_<format><md5(serialize(prompt))>` (none on this copy) |
| DT10 only: option `affibloom_settings` → `ai_provider_key` (stored as C1 stores secrets) | the user's AI key | — (Lasso uses its licence) |

No new table. Nothing is written to post content.

## Entry points and request paths

```
GET /some-page/ containing [affibloom type="grid" group="best-shoes" limit="4" latest="true" …]          (DT2, DT3)
  → wp_enqueue_scripts: BoxAssets::enqueue_for_page()   has_block(affibloom/product-box) || has_shortcode(affibloom)
       → affibloom-box CSS (display sections) + :root variables; phone-swap script when DisplaySettings customised
  → do_shortcode → AffibloomShortcode::render( atts )                                  (18S's handler)
       DisplayAttributes::parse( atts )  → DisplayRequest { type: grid|list|gallery|button|image|sitestripe|ai_text,
                                                             group?, product?, limit?, latest, columns?, bullets?,
                                                             compact, disclosure?, button_type, overrides, anchor }
       DisplayHtml::render( request )
         group types → GroupDisplayService::products( slug, limit, latest )
                         GroupService::members( term by slug )   (F23 D12: published, ordered)  → [] when unknown
                         ProductRepository::get_many( ids )      one read for the page's cards
         GridHtml   → for each product BoxBuilder::build( product, DisplayType::grid(), overrides ) → box.php
                      wrapper + rows/one grid + brag mark + grid disclosure        → templates/display/grid.php
         ListHtml   → ListCardBuilder per product (list bucket)                     → display/list.php, list-card.php
         GalleryHtml→ ImageHtml per product                                         → display/gallery.php, image.php
         ImageHtml / ButtonHtml / SiteStripeHtml → ProductRepository::get( id ) → null → ''   → display/*.php
         AiTextHtml → AiTextRepository::find( format, prompt ) → null → ''          → display/ai-text.php
  ← HTML in place of the shortcode

[lasso type="grid" category="…"] (Task 27) → LassoShortcode::render → maps to DisplayRequest (D1, D20) → DisplayHtml
Block affibloom/product-box with displayType (Phase 4, F4) → ProductBoxBlock::render → DisplayHtml
Settings → Display preview, Grid/List (Task 10D) → GET /affibloom/v1/display-preview?type=grid|list → DisplayHtml
  with the sample products and the unsaved settings (D23)

POST /affibloom/v1/ai-text { prompt (1-1000 chars), format: html|text, mode: generate|save, html? }       (DT10, N2 b)
  → AiTextController (Capabilities::INSERT_BOXES, REST nonce)
       AiTextService::generate()  → AiTextProvider (interface; NoProvider until a key) → AiTextRepository::save()
       AiTextService::save()      → kses → AiTextRepository::save()
  ← 200 { html }   422 affibloom_ai_prompt_required | affibloom_ai_prompt_too_long
                   409 affibloom_ai_no_provider ("Add an AI provider key in Settings to generate text.")
                   502 affibloom_ai_failed ("The AI service did not answer. Try again.")
```

## Layers

- **Domain** (`Domain\Display\`): `DisplayAttributes` (the per-type schema of R52/R53 and the parse rules: `limit`
  R6, `columns` R14, `bullets` R21, `button_type` R30, `compact` R13, `disclosure` R17, `latest`, overrides; N1's two
  modes), `DisplayRequest`, `GridColumns` (classic vs customised count), `ListBullets` (tag and class per value),
  `GridDisclosure` (R17), `AnchorId` (D16), `AiText` and `AiTextRepository` (interface). Existing `DisplayType`,
  `DisplaySettings`, `Layout`, `Elements` are reused unchanged (Grid and List already have buckets).
- **Application** (`Application\Display\`): `GroupDisplayService` (slug → ordered, limited products; `latest`),
  `ListCardBuilder` and `SiteStripeBuilder` (views from a product; extracted from `Box\BoxBuilder` only where the
  second use appears), `AiTextService` (DT9 read; DT10 generate/save), `AiTextProvider` (interface, DT10).
- **Infrastructure** (`Infrastructure\WordPress\`): `OptionAiTextRepository`; DT10 one provider adapter chosen with
  the user (N2) using `wp_remote_post`, never called on a page view.
- **Interface** (`Interface\Front\`): `DisplayHtml` (dispatch, R2's order), `GridHtml`, `ListHtml`, `GalleryHtml`,
  `ImageHtml`, `ButtonHtml`, `SiteStripeHtml`, `AiTextHtml`, `AnchorIds` (per-request counter), `BragMark` (from
  Task 10E/F5, reused); templates `templates/display/{grid,list,list-card,gallery,image,button,sitestripe,ai-text}.php`;
  CSS sections in `assets/front/box.css`; DT10 `Interface\Rest\AiTextController`.

## Risks

- **Grid cards need the themes.** The live Grid and List theme is Cutter; Task 18y's 8 themes must exist first, and
  each card is the real Single box, so a theme bug shows up in every grid.
- **The customised grid's CSS is the largest part** (Lasso's v2 sheet spends a large share of its 3,169 lines on grid
  and list rules: CTA footer rows, ribbon offsets, per-theme fixes). Affibloom writes its own rules against baselines
  (ADR-6); DT2 and DT4 budget most of their time here and are checked at 1280, 768 and 390 px.
- **No Lasso render exists for these types** on the site (no content uses them); DT1's staging baselines (N4) are a
  precondition.
- **`latest`** orders by product ID; migrated products get new IDs, so Task 26 must import in ascending Lasso ID.
- **Performance:** a large group without `limit` is one members read plus one batched product read; no per-card
  query. Phone swipe rows are checked by touch emulation and by keyboard (each card's links stay focusable).
- **Lasso's licence-date brag** (R56): if production's Lasso licence lapses (this copy: 2026-11-23), Lasso itself
  prints its brag icon on every live box two days later; Affibloom never does.

## Security

- Every attribute value is sanitised by `DisplayAttributes` (`sanitize_title` for `group`, `absint` for `limit` and
  `columns`, enums for `type`, `bullets`, `button_type`, `disclosure`, `esc_url_raw` for URL overrides, HTML-id
  sanitising for `anchor_id`); all output escaped at the template; button texts through decision 5's `span[class]`
  allowlist; descriptions and AI text through `wp_kses_post`; `sitestripe_style` through `safecss_filter_attr()`.
- No outside request on any page view (D14); the shortcode needs no capability (published data only).
- DT10: `Capabilities::INSERT_BOXES`, the REST nonce, prompt length 1000, the key stored as C1 stores secrets and
  never printed back; the provider is called only from the editor's request.

## Tests

- **Unit** (Brain Monkey): `DisplayAttributesTest` (R6 limit table, R13 compact values, R14 columns per mode and
  type, R21 bullets, R30 button type, R2 dispatch with a group and any type), `GroupDisplayServiceTest` (order from
  `members()`, `latest`, limit, unknown slug → []), `GridDisclosureTest`, `AnchorIdTest`, `ListBulletsTest`,
  `AiTextServiceTest`.
- **Integration** (WordPress suite): each renderer through `do_shortcode()` with factory products and a group;
  empty and unknown group; missing product; `keep_site_stripe_ui` on/off; stored and missing AI text; a page without
  a display enqueues nothing.
- **E2E acceptance** `tests/E2E/acceptance/F30.spec.ts`: AC1 (baselines present), AC2-AC14 one test each on the Local
  copy against draft pages made through WP-CLI, AC15 only with DT10.
- **Visual**: each type's desktop and phone screenshot within 1 % of its DT1 baseline (ADR-6, decision 9), Grid and
  List in both modes.
