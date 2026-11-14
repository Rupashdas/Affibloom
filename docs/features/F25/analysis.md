# F25 Comparison tables: analysis

Status: gate 2 written 2026-11-14 with `requirements.md`. Gate 4 for Tasks T1-T17: waiting.

**Tasks:** T1 (the Tables list and `GET /tables`), T2 (Create Table), T3 (the builder page and the table's settings),
T4 (products: add, starting layout, badge, remove), T5 (Add Group), T6 (rows/columns, the Add Field dialog, plain
cells, removing fields), T7 (image, price and description cells), T8 (button information), T9 (reordering), T10
(Locations), T11 (clone and delete), T12 (the reader's Horizontal table by shortcode), T13 (Vertical), T14 (phone cards
and breakpoints), T15 (Preview Table), T16 (the Dashboard's Comparison table filter), T17 (a deleted product leaves its
tables). Area 8 of the dashboard-completion plan, after the dashboard areas and after Phase 3's box (Task 18) and F31's
18S1.

## Depends on

- ADR-001 (posts and meta), ADR-007 (PHP-rendered screens, plain scripts, `wp.apiFetch`), ADR-008 (Aurora look,
  Lasso's arrangement).
- F1: `ProductRepository` (a product's name, image, price, description, buttons, badge, Amazon flag), the product
  delete path (17A) for T17, the rich editor settings (price, description) for T7, the media library pick (B5).
- F26 Fields: the field library with Lasso's IDs, including the six table-only built-ins (requirements §5); `POST
  /fields` for "Create new" (built in 14C).
- F23 Groups: `GroupService::members()` (T5).
- Phase 3 Task 18 (F5): the box engine's field renderers, `BoxAssets`, the link attributes; F31 18S1: the
  `[affibloom]` shortcode and its dispatcher (T12 adds `type="table"`); F21 Task 35: the content link index's
  `display_ref` (T10, T11).
- F19: the filters panel, `DashboardUrl`, saved views (T16). F16: `show_disclosure_table`, customised settings (T12).
- `Capabilities::PRODUCTS`, `Capabilities::INSERT_BOXES`.

## Data

| Where | What | Lasso's key |
|---|---|---|
| Post type `affibloom_table` | a table; `post_title` = Name; `post_status` publish; not public, `show_ui` false, no REST, no rewrite | `lasso_table_details.id, title` |
| Meta `_affibloom_table` | `{ style: "row"\|"column", theme: "Cactus"…, show_headers: bool, show_field_name: bool, products: [ {id, badge} ], groups: [ {id: "g_<hex>", fields: [field ids]} ], values: { "<product>": { "<field>": string } }, revision: int }` | `style`, `theme`, `show_headers_horizontal`, metadata `show_field_name`, `lasso_table_mapping` (lasso_id, order, badge_text), `lasso_table_field_group` (field_group_id, order), `…_detail` (field_id, order, field_value) |
| Meta `_affibloom_lasso_table_id` | a migrated table's Lasso ID (D2) | `lasso_table_details.id` |
| User meta of saved views (13E) | a view gains `comparison_table` ('' \| in_table \| not_in_table) | `dashboard_comparison_table` |
| Browser localStorage `affibloom_table_locations` | the Locations dialog's page per table (R40; per-viewer convenience) | `table_locations` |
| F21's index rows | `display_ref = table:<id>` (read) | `lasso_metadata (Link_Locations, Table)` |

Buttons' values are `{"button_text": …, "url": …}` JSON strings, as Lasso's (the migration copies them).

## Entry points and request paths

```
GET admin.php?page=affibloom-tables[&search=…&paged=N][&create=1]                              (T1, T2)
  → AdminMenu (PRODUCTS) → TablesScreen::render() → TableService::list( search, page, 10 )
       → TableRepository::page()   name LIKE or a product's name LIKE, spaces as wildcards (R8); ID ascending
       → TableLocations::counts( ids )   F21's index (Task 35); T1 prints the count, T10 makes it open the dialog
  ← templates/admin/tables.php (band, search, list partial or empty state, pager "x-y of n")
GET /affibloom/v1/tables?search=&page=&per_page=10|5   (PRODUCTS or INSERT_BOXES)
  → TablesController::index() → { items: [ {id, title} ], total, page, pages, html, url }   html = the list partial
                                 (T1; F4's picker reads items, newest first when `order=desc`)

POST /affibloom/v1/tables { title, style }                → 201 { id, redirect_to }   422 title required   (T2)
GET admin.php?page=affibloom-table&id=N                  → TableScreen::render() → TableService::get()      (T3)
     no table → the Tables list with a notice; no id → "Untitled Table"/"Untitled Table k", saving creates (R15)
PATCH /affibloom/v1/tables/{id} { revision, title?, style?, theme?, show_headers?, show_field_name? }
                                                          → 200 { revision, html }   422, 404, 409 changed  (T3)

POST   /tables/{id}/products { revision, product | group }  → 200 { revision, html, product_count }        (T4, T5)
       409 affibloom_table_product_exists, 422 affibloom_table_product_invalid
PATCH  /tables/{id}/products/{product} { revision, badge } → 200 { revision }                              (T4)
DELETE /tables/{id}/products/{product}?revision=           → 200 { revision, html }                        (T4)
POST   /tables/{id}/groups { revision }                    → 200 { revision, html }   409 no_products|empty_group (T6)
DELETE /tables/{id}/groups/{group}?revision=               → 200 { revision, html }                        (T6, R33)
POST   /tables/{id}/groups/{group}/fields { revision, field } → 200 …   409 field_exists, 409 no_products   (T6)
DELETE /tables/{id}/groups/{group}/fields/{field}?revision=   → 200 …                                       (T6)
PUT    /tables/{id}/values/{product}/{field} { revision, value } → 200 { revision }   404 no such cell      (T6-T8)
PUT    /tables/{id}/order { revision, products? | groups? | cell: { group, fields } } → 200 { revision, html } (T9)
POST   /tables/{id}/groups/{group}/fields/{field}/move { revision, to_group, position } → 200 …  409 field_exists (T9)
GET    /tables/{id}/locations?page=                        → { items: [ {title, edit_url, view_url} ], total, page, pages } (T10)
POST   /tables/{id}/clone                                  → 201 { id, page }                               (T11)
DELETE /tables/{id}                                        → 200 { redirect_to }   409 affibloom_table_in_use, 404 (T11)
GET    /tables/{id}/preview                                → { html }   the reader's table (T15)
  → TablesController → TableService (revision check, D4) → Domain\Table\Table (pure rules: add/remove product, group,
    field; move; order; R2 invariants; R19/R20 layouts) → TableRepository::save()
  Initial values: TableValues::for( product, field ) ← ProductRepository + FieldsService (R22, D7)
  html: TableBuilderView (templates/admin/partials/table-builder.php) from the saved table

[affibloom type="table" id="N" theme="" data_nosnippet="" anchor_id=""]                                    (T12-T14)
  → Interface\Front\AffibloomShortcode (F31 18S1) → TableDisplay::render( id, atts )
  → TableService::get() → TableView (Domain: rows per layout; R45/R46 orders) → templates/front/table.php,
    table-horizontal.php, table-vertical.php, table-mobile.php, partials/table-field.php (R48, calling the box's
    renderers) ; BoxAssets enqueues assets/front/table.css ; disclosure and brag from DisplaySettings (R51)

GET /affibloom/v1/dashboard/products?…&comparison_table=in_table|not_in_table                            (T16)
  → PostTypeProductRepository::page() gains "in a table" (the products of all tables, one query)
Product delete (17A) → TableService::remove_product_everywhere( id )                                      (T17)
```

## Layers

- **Domain** `Table\Table` (id, title, style, theme, show options, products, groups, values, revision; methods
  add_product, remove_product, add_group, remove_group, add_field, remove_field, move_field, order_products,
  order_groups, order_cell, set_value, set_badge; throws `Table\TableRuleBroken` with Lasso's codes), `Table\TableStyle`
  (enum Row, Column; labels Horizontal, Vertical), `Table\TableTheme` (enum, 8), `Table\TableProduct`,
  `Table\FieldGroup`, `Table\StartingLayout` (R19), `Table\UntitledName` (R14), `Table\TableView` (the reader's rows:
  vertical = one row per field; horizontal = one row per product), `Table\TablePage`, `Table\TableRepository`,
  `Table\TableLocations` (interfaces). Pure PHP, unit-tested.
- **Application** `Tables\TableService` (list, get, create, update, products, groups, fields, values, order, move,
  clone, delete, locations, remove_product_everywhere), `Tables\TableValues` (R22, D7), `Tables\TableNotFound`,
  `Tables\TableInUse`, `Tables\TableChanged`, `Tables\TableInvalid`.
- **Infrastructure** `WordPress\TablePostType` (Module), `WordPress\PostTypeTableRepository`, an adapter of F21's
  index for `TableLocations`.
- **Interface** `Admin\TablesScreen`, `Admin\TableScreen`, the header's Tables tab; `Rest\TablesController`;
  `Front\TableDisplay`; templates `admin/tables.php`, `admin/table.php`, partials `tables-list.php`, `table-row.php`,
  `table-builder.php`, `table-cell.php`, `table-create-modal.php`, `table-product-picker.php`, `table-field-modal.php`,
  `table-button-modal.php`, `table-description-modal.php`, `table-locations-modal.php`; front `table*.php`; scripts
  `assets/admin/tables.js` (list), `table-builder.js` (builder, queue, drag and move buttons, editors).

## Risks

- **The builder is the largest single screen** in the project; its tasks are cut by entry point (T3-T9) so each can be
  checked alone. Drag across products in two layouts (R34) is the hardest part (T9).
- **Pixel parity** (AC16-AC18) needs Lasso baselines; only Horizontal exists on the site (N2).
- **Field IDs**: tables use Lasso's 999xx built-ins; F26 must give them stable IDs before T4.
- **The F21 index must know Affibloom tables and migrated Lasso tables** (D2, D13), or delete would be allowed for a
  used table. T11's acceptance test seeds an index row.
- **One meta per table** is rewritten on every keystroke-save; tables are small, so the cost does not matter.

## Security

- `Capabilities::PRODUCTS` on screens and writes; `INSERT_BOXES` may read `GET /tables`; REST nonce through
  `wp.apiFetch`.
- IDs `absint`; a product ID is used only after it is found as an `affibloom_product`; a field ID only from the field
  library; group IDs matched against the table's; the button route checks the cell belongs to the table (Lasso does
  not, R35).
- Values cleaned on save (D11) and escaped on output except the allowed HTML of badges, buttons, descriptions and
  prices (`wp_kses_post` / the price allow-list).
- No post content is written.

## Tests

- **Unit:** `Table` (every rule and refusal: R18, R25, R28, R31, R33, R38, R39; starting layout; later products;
  invariants), `StartingLayout`, `UntitledName`, `TableView` (vertical and horizontal orders), `TableValues` (R22, D7),
  `TableService` (revision refusal, in-use refusal, clone copies all).
- **Integration:** `PostTypeTableRepository` round trip; `TablesController` routes and errors.
- **E2E acceptance** `tests/E2E/acceptance/F25.spec.ts`: AC1 (T1), AC2 (T2), AC3 (T3), AC4-AC5 (T4), AC6 (T5), AC7-AC9
  (T6), AC10 (T7), AC11 (T8), AC12 (T9), AC13 (T10), AC14-AC15 (T11), AC16 (T12), AC17 (T13), AC18 (T14), AC19 (T15),
  AC20 (T16), AC21 (T17). Visual: `tests/E2E/visual/table-*.spec.ts` against N2's baselines at 1280/768/390 px.
