# F26 Fields: analysis

Status: gate 2 written 2026-11-14 with `requirements.md`. Gate 4 for Tasks FL1-FL5: waiting.

**Tasks:** FL1 (the Fields list, the whole field model in the library, Manage Fields live), FL2 (a new field from the
Fields screen), FL3 (a field's Details: edit and delete), FL4 (a field's Links tab), FL5 (the product page's rows
corrected to Lasso's rules, and drag). They follow Task 17 and Groups in Phase 2 (dashboard-completion plan area 9),
before Comparison tables (F25).

## Depends on

- Task 14C (built): `Domain\Fields\*`, `FieldsService`, `OptionFieldRepository`, `FieldsController`,
  `ProductFieldsController`, `product-fields.js`, `field-modal.php`, `field-row.php`, `product-fields.php`.
- ADR-001 (products are posts), ADR-007 (PHP-rendered screens, plain scripts), ADR-008 (Aurora's look, Lasso's
  arrangement); `Capabilities::PRODUCTS`.
- Shared: `pager.php`, `delete-modal.php`, `save-modal.php`, `toast.php`, `Notice`, `admin.js` dialogs and field
  errors, `FieldError`, `Messages`. The in-place list pattern of Groups G1 (D15): if G1 lands first, its list script
  helper is reused here (second use → extract).

## Data

| Where | What | Lasso |
|---|---|---|
| option `affibloom_fields` (not autoloaded) | `{ fields: [ {id, name, type, description} ], names: { "<built-in id>": "<title>" } }`; an old flat list (14C) is read as `fields` with no `names` | `lasso_fields` user rows; built-in `field_name` |
| code `FieldLibrary::built_in()` | the nine built-ins: id, default name, type, description, `table_only` (99989-99994), table `order` (R2) | `lasso_fields` built-in rows |
| post meta `_affibloom_fields` | `[ {field, value, visible, show_name} ]` in display order (unchanged from 14C) | `lasso_field_mapping` + `lasso_metadata show_field_name_1` |

Usage counts read every product's `_affibloom_fields` in one query (`meta_key = '_affibloom_fields'`, any post status
except auto-draft) and count the field ids in PHP; 263 products make this cheap. A table can come later if F25 needs
cross-product queries.

## Entry points and request paths

```
GET admin.php?page=affibloom-fields[&s=…&paged=N]                                                     (FL1)
  → AdminMenu (hidden page, Capabilities::PRODUCTS) → FieldsScreen::render()
  → FieldsService::list( search, page, 10 ) → FieldLibrary::search() (spaces as wildcards, D11)
                                            + FieldUsage::counts() ← OptionFieldRepository / PostTypeProductRepository::field_usage()
  ← templates/admin/fields.php (band, Add New Field, search, table Name/Type/Usage or the empty state, pager)
GET /affibloom/v1/fields/list?search=&page=   → { data: { html, url } }   the same partial, drawn in place (D15)

GET admin.php?page=affibloom-field                         "Add a New Field" (FL2)
GET admin.php?page=affibloom-field&id=N&tab=details        Details (FL3); unknown id → the Fields list
GET admin.php?page=affibloom-field&id=N[&paged=M]          Links tab (FL4)
  → FieldScreen::render() → FieldsService::get( id ), products_using( id, page )
  ← templates/admin/field.php (heading, tabs Links <n> / Details, the tab's card, footer)

POST   /affibloom/v1/fields { name, type, description }          → 201 { data: field, redirect_to: list }   (FL2; 14C's route, + redirect_to)
         422 data.fields name: required ("Title is required.") | invalid_name; type: invalid_type; description: too_long
PATCH  /affibloom/v1/fields/{id} { name, type?, description? }   → 200 { data: field }                       (FL3)
         built-in: only name is read (D4); 404 affibloom_field_not_found; 422 as POST
DELETE /affibloom/v1/fields/{id}                                 → 200 { redirect_to: list }                (FL3)
         409 affibloom_field_in_use (Usage > 0) | affibloom_field_built_in; 404
  → FieldsController → FieldsService::create() / rename() / update() / delete()
       → FieldLibrary (pure rules) → OptionFieldRepository::save()

GET /affibloom/v1/fields?search=&page=   the product page's library (14C): search gains wildcards (FL5); table-only
                                         built-ins left out (FL1, D2)
PUT /affibloom/v1/products/{id}/fields { rows }   (14C) values cleaned (D10), 1,000 limit → 422
                                         data.fields "rows.<field>": too_long (FL5)
```

## Layers

- **Domain** `Fields\Field` (+ `table_only`, `order`), `Fields\FieldType` (+ five built-in-only cases,
  `choosable()`, `icon()`), `Fields\FieldLibrary` (built-ins with renames, `rename()`, `update()`, `delete()`,
  `search()` with wildcards, `for_products()` without table-only), `Fields\FieldValue` (clean lines, the 1,000 limit;
  unit-tested), `Fields\FieldUsage` (counts by field id), `Fields\FieldRepository`.
- **Application** `Fields\FieldsService` (list, get, create, rename/update, delete with the in-use and built-in
  refusals, products_using), `Fields\FieldNotFound`, `Fields\FieldInUse`, `Fields\FieldBuiltIn`.
- **Infrastructure** `WordPress\OptionFieldRepository` (the `{fields, names}` shape, reading 14C's flat list),
  `PostTypeProductRepository::field_usage()`, `products_with_field( id, page, 10 )` (published, name, slug, image).
- **Interface** `Admin\FieldsScreen`, `Admin\FieldScreen` (hidden submenu pages); `Rest\FieldsController` (list
  partial, PATCH, DELETE); `ProductFieldsController::update()` (field errors); templates `fields.php`, `field.php`,
  partials `fields-list.php`, `field-list-row.php`, `field-product-row.php`, `field-form.php`; script
  `assets/admin/fields.js` (list in place, Details save and delete); `product-fields.js` (drag, field errors);
  `field-modal.php` (Manage Fields becomes a link).

## Risks

- **The option's shape changes** (flat list → `{fields, names}`): the reader accepts both; the first save writes the
  new shape. Unit-tested with 14C's stored form.
- **Renaming a built-in** changes the name everywhere it is read (product rows, library, tables later); F25's "Primary
  Rating" name rule (R4) must use id 1, not the name — noted for F25.
- **Usage over trashed products** (edge 5) can puzzle: the Hold Up text is Lasso's; the Links tab shows published only.
- Drag on the product page shares rows with Move up / Move down; both save the same PUT.

## Security

- `Capabilities::PRODUCTS` on both screens and every route; REST nonce through `wp.apiFetch`.
- Titles `sanitize_text_field`, descriptions `sanitize_textarea_field`, both escaped on output; ids `absint` and looked
  up in the library before use; types only from `FieldType::choosable()`; values as 14C (N1 pending).
- No post content is written.

## Tests

- **Unit:** `FieldLibraryTest` (built-ins with renames, table-only excluded from `for_products()`, wildcard search,
  next id, delete refusals), `FieldValueTest` (trim, blank lines, 1,000), `OptionFieldRepositoryTest` (14C's flat
  shape), `FieldsServiceTest` (in use, built-in, rename keeps type and description).
- **E2E acceptance** `tests/E2E/acceptance/F26.spec.ts`: AC1 (FL1: ten rows of the nine built-ins and one created
  field, search, empty, row link, Manage Fields), AC2 (FL2), AC3-AC4 (FL3, with a product attached through WP-CLI for
  Hold Up and a direct DELETE for 409), AC5 (FL4), AC6-AC7 (FL5); F1 AC10 rerun (14C's rows).
- **By hand:** 1280 and 375 px, keyboard only (list, form, dialogs, drag alternative).
