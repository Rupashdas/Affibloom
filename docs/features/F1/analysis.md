# F1 Product library: analysis

Status: gate 2 approved with Task 6 (2026-11-12); **the product page re-run 2026-11-13** with the requirements'
sections 2-8. Waiting for the user's gate 4 on Tasks 14A-14E.

**Tasks:** 8, 11A-11E, 13A-13E, **14A-14E**, 15, 16, 17.

## Depends on

- ADR-001 (post type), ADR-007 (PHP-rendered screens, JavaScript where Lasso uses it), ADR-008 as amended (Aurora's
  colours and type; the layout is the gate 4 question D1).
- F9: new products take the site's link defaults (11E).
- F16: the Single type's theme names the "Default (…)" option; the Display palette's button texts are the
  placeholders. F17: the tracking ID and its rule (14D's notice).
- `Capabilities::PRODUCTS` = `manage_options` (decision 1).

## Data touched

| Data | Today | Product page |
|---|---|---|
| Post `affibloom_product` | title, slug, status | title = Name; slug = Permalink (shown with cloaking, area 7) |
| `_affibloom_url`, `_type`, `_asin`, `_amazon_country`, `_search_key`, `_new_tab`, `_nofollow`, `_sponsored`, `_show_disclosure` | read by `hydrate()` | shown (14A, 14B) |
| `_affibloom_button_text`, `_url2`, `_button_text2`, `_new_tab2`, `_nofollow2` | written by the seed only | read (14A) |
| `_affibloom_image_url`, `_badge`, `_description`, `_disclosure` | image read since 13C; the rest seed only | read (14B) |
| `_affibloom_theme`, `_price`, `_show_price`, `_prime` | — | new, read (14B); Task 15 writes them |
| `_affibloom_fields` | — | new (14C): the attached custom fields, `[{field, value, visible, show_name}]` in display order |
| option `affibloom_fields` | — | new (14C): the editor's own field definitions `{id, name, type, description}`; ids from 100 up |

Why `_affibloom_fields` is one meta array, not a table: a product's fields are always read and written together
with the product (the page, the box), Lasso's live data is 5 rows, and the comparison tables read them product by
product. A table can come with the Tables area if it ever needs a query across products.

Built-in field definitions live in code (`Domain\Fields\Field`): **1 Primary Rating** (`rating`, "Editor Rating.
Position in display is fixed."), **2 Pros** and **3 Cons** (`textarea`, "A list of all of the Pros. One per line." /
"… Cons …"), with Lasso's IDs, so migrated mappings keep their meaning. Lasso's 99989-99995 rows are table columns,
not product fields; they belong to the Tables area.

## Entry points and request paths

```
GET admin.php?page=affibloom-product&id=N                       (14A)
  → AdminMenu: hidden submenu, Capabilities::PRODUCTS → ProductScreen::render()
       ProductRepository::get( N ) → null / not published → wp_safe_redirect( Dashboard )   (load-<hook>, before output)
  ← templates/admin/product.php (band: title, link line, tabs; Share kit 14D; form; footer)

POST /affibloom/v1/products { url }  → 201/200 { …, redirect_to }      (14A changes only redirect_to)
  non-Amazon, new        → ProductScreen::url( id )
  non-Amazon, duplicate  → ProductScreen::url( id ) + is_duplicate=1  → the page shows Lasso's warning
  Amazon, new or found   → the Dashboard, as today (deviation 56)

GET  /affibloom/v1/fields?search=&page=            → library: built-ins + the editor's, ten a page     (14C)
POST /affibloom/v1/fields { name, type, description } → a new definition
POST   /affibloom/v1/products/{id}/fields { field }            → attach (Lasso: lasso_add_field_to_page)
PUT    /affibloom/v1/products/{id}/fields [ {field, value, visible, show_name} … ] → values, toggles, order
DELETE /affibloom/v1/products/{id}/fields/{field}               → detach

POST admin-post.php?action=affibloom_tracking_id { tracking_id }  → the Amazon tab's rule, then back (14D)
```

The Details form itself is saved by `PATCH /products/{id}` in Task 15.

## Layers

- Domain: `Product` gains the fields above (readonly, defaults meaning "not set"); `Rating` (1-5, one decimal);
  `Fields\Field`, `Fields\FieldType` (Lasso's text, copy_clipboard, textarea, number, rating, bulleted_list,
  numbered_list), `Fields\ProductFields` (attach, detach, reorder, set; Primary Rating kept first).
- Application: `FieldsService` (library search, create, attach/detach/update for a product).
- Infrastructure: `PostTypeProductRepository::hydrate()` reads the new meta; `OptionFieldRepository`; the product
  fields meta read/write.
- Interface: `ProductScreen`, `FieldsController`, `ProductFieldsController`, `TrackingIdController`;
  `templates/admin/product.php` and partials; `assets/admin/product.js`.

## Risks

- **Rich text without Quill.** Lasso's editors are Quill; Affibloom uses WordPress's own `wp_editor()` (TinyMCE) with
  Lasso's toolbar (`bold italic underline strikethrough link bullist forecolor backcolor removeformat`), no media
  button, no Text tab. TinyMCE keeps Quill's `<span style>` markup. The price editor is a second, one-line instance
  with `bold italic underline strikethrough forecolor`. Checked by opening the five longest live descriptions (14B).
- **Two places edit custom fields' values** (the rows now, the box's fields later): the rows are the only writer.
- **A media-library pick** gives an attachment; Lasso stores its ID (`_thumbnail_id`) and the URL. Affibloom keeps the
  URL in `_affibloom_image_url` and the attachment ID in `_affibloom_image_id` (Task 15 writes both), so a later
  image-size choice is possible.
- The Single type's theme drives "Default (…)": a change of that setting relabels the option on every product.

## Security

- `Capabilities::PRODUCTS` on the screen and every route; REST nonce through `wp.apiFetch`; the tracking-ID form uses
  `check_admin_referer`.
- The `id` is `absint`; the page never prints a product that is not `affibloom_product`.
- Every value escaped in the templates (`esc_attr` in fields, `esc_html` in the heading, `wp_kses_post` for the
  description shown in the editor); custom field values are text, escaped; field names and descriptions are
  `sanitize_text_field`, 1-80 characters.
