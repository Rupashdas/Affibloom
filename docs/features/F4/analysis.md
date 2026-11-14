# F4 Displays in the editor: analysis

Status: gate 2 written 2026-11-14 with requirements.md. Gate 4 for 23A-23G, 24A-24E, ED1-ED6: waiting (N1-N6).

**Tasks:** 23A chooser and block · 23B product search · 23C insert Single/Button/Image, markup, published page ·
23D groups · 23E create/fill a group · 23F tables · 23G create a link + quick detail · 24A Customize Display ·
24B schema panel · 24C shortcode box · 24D Edit Display · 24E preview queue · ED1 Classic · ED2 Elementor ·
ED3 Rank Math · ED4 AMP · ED5 core-block link attributes (N3) · ED6 legacy blocks (N2) · MCP1 placeholder (N5).

## Depends on

- Task 18 (Single renderer), 18S (`[affibloom]` shortcode, parser/formatter); F30 DT tasks (Grid, List, Gallery,
  Button, Image renderers); F25 T tasks (tables, `GET /tables`, table page); F23 G1, G4 (groups routes, picker data,
  `in_group`); F18 D6 (editor Add A New Link); F1 (product form partials, PATCH, fields for schema); Task 27 (`[lasso]`
  compatibility, needed by ED6); ADR-007 (plain scripts), ADR-008 (Aurora); ADR-003 (to revise per N2).

## Data

| Where | What | From Lasso |
|---|---|---|
| Block `affibloom/product-box` comment JSON | `type`, `productId`, `groupId`, `tableId`, overrides under Lasso's names (title, title_url, title_type, description, badge, brag, price, field, rating, theme, primary_url, primary_text, secondary_url, secondary_text, image_url, disclosure_text, button_type, columns, limit, bullets, compact, anchor_id, disclosure, data_nosnippet), schema_* (8) | `affiliate-plugin/lasso` attrs + shortcode text |
| Block `affiliate-plugin/lasso` (ED6) | unchanged; read as stored | same |
| Product meta | primary rating field set to 3.5 when review schema is enabled (R46) | `lasso_add_field_to_page` |
| No options, tables, transients | — | MCP receipts/secret not built |

## Entry points and request paths

```
Block editor (post.php / post-new.php, post|page|CPT)                                   (23A-24E)
  EditorAssets: enqueue_block_editor_assets → block script, display-modal.js, localized OverrideCatalog
                enqueue_block_assets → display CSS vars + display stylesheet in the canvas
  block render_callback → DisplayRenderer::render( DisplayRequest from attributes )      (Task 18 / F30 / F25)

GET  /affibloom/v1/products?search=&page=&per_page=5&orderby=modified[&group=N]  INSERT_BOXES  → { items, pages }
GET  /affibloom/v1/groups?search=&page=&per_page=5                               INSERT_BOXES  (F23)
POST /affibloom/v1/groups { name, description }                                  PRODUCTS      (F23 G1) 201|422
GET|POST /affibloom/v1/groups/{id}/products                                      PRODUCTS      (F23 G4) 201|409
GET  /affibloom/v1/tables?search=&page=&per_page=5                               INSERT_BOXES  (F25)
POST /affibloom/v1/products { url }                                              PRODUCTS      (F18)
PATCH /affibloom/v1/products/{id}                                                PRODUCTS      (15A)
POST /affibloom/v1/editor/preview { attributes }      → { html, schema }   400 affibloom_invalid_display  INSERT_BOXES
  → EditorController → DisplayRequest::from_attributes() (Domain, validates type/ids/overrides)
  → DisplayRenderer::render() ; SchemaInfo::for_product()
GET  /affibloom/v1/editor/schema?post_id=             → { using_schema_id, products: {id: {...}} }  edit_post on post_id
  → EditorController → PostDisplays::from_content( post_content ) (read only) → SchemaInfo

Classic: mce_external_plugins / mce_buttons (only with Classic Editor or Disable Gutenberg)          (ED1)
Elementor: elementor/widgets/register → AffibloomWidget (render → DisplayRenderer via shortcode)     (ED2)
Rank Math: rank_math_content (JS filter, only when rank-math-analyzer is registered)                (ED3)
AMP: amp_post_template_css → inline display CSS                                                     (ED4)
Editor filters: blocks.getBlockAttributes / getSaveContent.extraProps / getSaveElement              (ED5)
Legacy block: register_block_type( 'affiliate-plugin/lasso', supports.inserter=false )              (ED6)
```

## Layers

- **Domain** `Display\DisplayType` (enum of seven), `Display\DisplayRequest` (type + target + overrides, the rules
  of R39-R41), `Display\OverrideCatalog` (R36-R38), `Display\ShortcodeFormat` (18S's, reused by 24C), `Display\
  PostDisplays` (parse a post's displays for schema, read only).
- **Application** `Editor\EditorPreview` (render one request), `Editor\SchemaService` (one-per-post rule, R44-R47).
- **Infrastructure** existing repositories (products, groups, tables).
- **Interface** `Editor\EditorAssets` (Module), `Editor\ProductBoxBlock` (registration + render callback),
  `Editor\LegacyLassoBlock` (ED6), `Editor\ClassicButton` (ED1), `Editor\ElementorWidget` (ED2), `Editor\AmpStyles`
  (ED4); `Rest\EditorController`; `ProductsController` (per_page/orderby); scripts `blocks/product-box/*`,
  `assets/editor/{display-modal,quick-detail,rank-math,core-link-attributes}.js`.

## Risks

- **Build order:** 23C-23F each wait on a renderer from another feature; types without one are shown "Coming soon"
  in the chooser so 23A can ship first.
- **Legacy blocks (N2) and core-block attributes (N3):** if not built before Lasso is deactivated, 147 posts open
  with invalid blocks in the editor (the site still renders).
- **Server-rendered previews** of every block on a long post: the queue (24E) caps load.
- **Quick detail** reuses the product form; the form's scripts must work inside a modal (F1 partials).

## Security

- `INSERT_BOXES` for reads and preview; `PRODUCTS` for creating products and groups (D8); `edit_post` on the post for
  the schema route; REST nonce via `wp.apiFetch`.
- The preview route renders only Affibloom displays from validated attributes (no arbitrary `do_shortcode`, unlike
  Lasso's R32).
- No route writes post content; MCP routes are not built.

## Tests

- Unit: `DisplayRequest` (toggles, quotes, anchor id, order), `OverrideCatalog`, `SchemaService` (one per post),
  `PostDisplays`.
- Integration: render callback = shortcode HTML; preview route refusals (400, 403); schema route `edit_post`.
- E2E acceptance `F4.spec.ts`: AC1-AC21 (AC16-AC17 skipped when the plugin under test is not installed; AC20-AC21 on
  fixture copies of posts 2622 and one GenerateBlocks post).
