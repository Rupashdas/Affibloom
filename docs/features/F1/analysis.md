# F1 Product library: analysis

Status: gate 2 approved with Task 6 (2026-11-12); **the product page re-run 2026-11-13** with the requirements'
sections 2-8. Gate 4 given by the user 2026-11-13. **Saving re-run 2026-11-13** with requirements section 9
(section "Saving" below); waiting for gate 4.

**Tasks:** 8, 11A-11E, 13A-13E, **14A-14E**, **15A-15D**, 16, 17.

## Depends on

- ADR-001 (post type), ADR-007 (PHP-rendered screens, JavaScript where Lasso uses it), ADR-008 as amended (Aurora's
  colours and type; Lasso's two-card layout, the user's gate 4 answer D1).
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

## Saving (Task 15, requirements section 9)

```
[Save Changes] or Enter in a field                                                        (15A)
  → product-save.js: snapshot of the form (D19: description and price only when edited)
       "Updating Your Site" dialog (S14) → wp.apiFetch PATCH /affibloom/v1/products/{id}
  → ProductsController::update()        Capabilities::PRODUCTS; id absint; only known fields kept
  → ProductService::update( id, input )
       ProductRepository::get( id )     → ProductNotFound → 404 affibloom_not_found
       ProductInput::changes( input )   → ValidationFailed { name: required } → 422 data.fields (S2, D11)
            texts S3; buttons[] and primary_position; button URLs → LinkNormaliser::button() (S4)
       ProductRepository::save( product->with( changes ) )   updates the post (ID set) and every meta key
  ← 200 { data: ProductResource::full( product ) } → the page writes back (S15), "Product saved." (D10)

  url sent and changed                                                                    (15B)
       ProductInput → LinkNormaliser::primary( url )   the add flow's steps (11B, 11D, 12A-12E), D12
       same ASIN + country → in place (S8); otherwise type/ASIN/country/search key from the new link,
       name by keep_title_when_url_replaced and the placeholder list, image cleared (S8, D17)
       the type changed → the response says reload: true (D18)

  the new link belongs to another published product                                       (15C)
       ProductService::update → DuplicateLink { other }  → 409 affibloom_duplicate { from, to } , nothing saved
  → the dialog's "This Product Already Exists" panel → Confirm
  → POST /affibloom/v1/products/{id}/repoint { to, url }
  → ProductService::repoint( id, to, url )   Lasso's four checks (S10) → 422 with Lasso's message
       ProductRepository::retire( from, to ): _affibloom_repointed_to = to, post to the trash
  ← 200 { redirect_to: ProductScreen::url( to ) + repoint_merged=1 } → "Success! Locations now show this link."

  leaving with changes                                                                    (15D)
  → product-save.js: snapshot ≠ baseline → "Unsaved Changes" dialog, or beforeunload (S16)
```

**Layers.** Application: `ProductInput` (the field rules; Task 26 is its second caller), `LinkNormaliser` (S4 and
S7, extracted from `ProductService::create_from_url()` on its second use), `ValidationFailed`, `ProductNotFound`,
`DuplicateLink`, `ProductService::update()` and `repoint()`. Domain: `PriceHtml` (S3's colour rule, pure PHP) and
`PlaceholderName` (S8's list). Infrastructure: `save()` updates when the product has an ID, writes every field and
deletes the Amazon keys when the type is no longer Amazon (Lasso's `delete_post_meta( 'amazon_product_id' )`);
`retire()`. Interface: the PATCH and repoint routes, `ProductResource::full()`, `Messages` for the reasons,
`templates/admin/partials/{save-modal,unsaved-modal}.php`, `assets/admin/product-save.js` with the JS twin of the
toast.

**Data written** (Lasso's keys in brackets): post title (`post_title`), `_affibloom_url`, `_url_key`, `_type`,
`_asin`, `_amazon_country`, `_search_key`, `_button_text` (`buy_btn_text`), `_new_tab`, `_nofollow`, `_buttons` and
`_primary_position` (`second_btn_*`, F29), `_theme` (`custom_theme`), `_image_url` (`lasso_custom_thumbnail`),
`_image_id` (`_thumbnail_id`), `_badge`, `_price`, `_show_price` (`''` = the site's), `_description`
(`affiliate_desc`), `_disclosure` (`disclosure_text`), `_sponsored`, `_show_disclosure`, `_prime`; new in 15C:
`_affibloom_repointed_to` (`lasso_repointed_to_lasso_id`). The post's modified date moves, so the Dashboard's order
(F19) puts a saved product first, as Lasso's.

**Risks.** A form with many buttons is one request: `buttons` is an array of at most the buttons on the page (no
limit, as F29). A save racing a custom-field save (D3) touches other meta keys only. TinyMCE's "only when edited"
uses `isDirty()` with a comparison against the loaded content as a fallback (14B's check of the five longest live
descriptions). The repoint pointer is read by code that does not exist yet (Tasks 18, 27, 35); their phase files get
the rule written in now.

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
