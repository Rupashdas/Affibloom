# F26 Fields: requirements

Status: gates 1-2 written 2026-11-14 from a sweep of Lasso 369's Fields (area 9 of
`affibloom-docs/plans/archive/dashboard-completion-plan.md`). The product page's half was built in Task 14C (F1, deviation
68); this document covers Lasso's whole field model, the Fields screens, and the corrections 14C's half needs.
Gate 4 for Tasks FL1-FL5: given by the user 2026-11-14.

## 1. User stories and acceptance criteria

- As an editor, I define fields once (Size, Weight, Pros…), see where each is used, rename or delete them, and fill
  them per product, so displays and comparison tables show the same facts everywhere.

- **AC1** The product page's "Add Field" dialog has a live **Manage Fields** link that opens Affibloom → **Fields** in
  a new tab: "Fields", **Add New Field**, "Search Fields" (spaces as wildcards), columns **Name** (the description under
  it), **Type** (an icon), **Usage** (products that have the field), ten a page, every field including the nine
  built-ins and the editor's own; with no match, "Looks like we're coming up empty.". A row opens that field's Links
  tab. (FL1, R7-R11, D2, D3, D13, D15)
- **AC2** **Add New Field** opens "Add a New Field" (Field Title, Field Type with the seven choosable types, Short Text
  first, Description, Save Changes, no tabs, no delete link); a good save shows "Updating Field", creates the field and
  lands on the Fields list with "Field saved."; an empty title is refused with "Title is required." and nothing is
  stored. (FL2, R12, R13, R15, D6, D8, D18)
- **AC3** A field's **Details** tab shows its title, type and description. For a built-in field the type and
  description are locked and "Note: This is a built-in field and only the title is editable." shows; saving a new
  title keeps the type and description, and the product page, the library and the Fields list show the new name. For
  the editor's own field all three change; a changed type keeps every product's stored value. Save shows "Updating
  Field", then "Field saved.". (FL3, R13-R17, D2, D4, D19)
- **AC4** **Delete This Field** (the editor's own fields only): a field that a product uses shows "Hold Up" ("You
  can't delete a Field if there are Links using it. Remove all Links using this Field first.", Ok) and nothing is
  deleted, also when the request is sent directly (409); an unused field asks "Delete This Field?" ("If deleted, you
  won't be able to get it back.", Cancel / Delete), shows "Deleting Field" and lands on the Fields list with
  "Successfully deleted the Field.". (FL3, R18, R19, D5)
- **AC5** A field's **Links `<n>`** tab lists the published products that have the field, ten a page: Image (50 px),
  Link Name (to the product's page), Permalink (`/slug/`); `<n>` is the number listed; with none, "Looks like we're
  coming up empty.". (FL4, R20, R21, D7)
- **AC6** On a product's page a value with blank lines and padded lines is stored with each line trimmed and blank
  lines dropped; the library search "pri rat" finds Primary Rating; a value over 1,000 characters is refused under its
  row; a renamed built-in field shows its new name. (FL5, R26, R24, D9-D11)
- **AC7** On a product's page the field rows can be dragged into a new order (Primary Rating stays first) as well as
  moved with Move up / Move down; the order is kept after a reload. (FL5, R25, R26, D12)

## 2. Evidence

All read completely: `pages/fields/class-ajax.php` (225); `models/class-fields.php` (330); `models/class-field-mapping.php`
(185); `libraries/field/class-lasso-object-field.php` (342), `class-field-mapping.php` (288); `admin/views/fields/`
`{fields, header, field-details, field-urls, field-mapping, field-details-row}.php`; `admin/views/rows/{add-field-row,
field-row, field-row-count, field-url-row, field-details-row}.php`; `admin/views/modals/{field-create, field-delete,
field-description, field-edit, url-field-delete}.php`; `admin/views/displays/field-row.php`. Read in part:
`classes/class-lasso-db.php` 1231-1310 (`get_fields_query`, `get_field`, `get_urls_in_field`), 5490-5655
(`remove_field_from_page`, `create_new_field`, `update_field`, `delete_field`, `get_fields_by_lasso_id`);
`pages/class-ajax.php` 108-130 (`lasso_report_urls` head), 312-370 (the product-page and table libraries), 548-564
(`fields`, `field-urls`), 600-612; `admin/assets/js/view-js.php` 32-41, 83-116, 1843-1850, 1940-2060, 2270-2360,
2470-2600, 3004-3045; `admin/assets/js/settings.js` 3191-3283 (`fieldSave`, `deleteField`); `admin/assets/js/
lasso-helper.js` 5-30 (nonce prefilter), 534-551; `admin/views/dashboard/url-details.php` 270-293, 885-1110, 2040-2075,
2663-2665; `admin/views/modals/url-add.php` 990-1250 (quick detail); `classes/class-html-helper.php` 385-560;
`classes/class-helper.php` 2368, 3100-3121, 5442-5455, 6494-6525; `classes/class-lasso-affiliate-link.php` 488-520,
618-623; `admin/views/displays/single.php` 60-175 and the `layout-*-box.php` / `list-box.php` field blocks;
`classes/class-lasso-shortcode.php` 375-405; `models/class-metadata.php` 20-60; `models/class-table-mapping.php`
285-320; `pages/class-hook.php` 395-421 (the pages), 1415-1430, 1545-1610 (schema), 1807-1880 (WP All Import);
`classes/class-import.php` 760-800, 1316-1333 (AAWP); `classes/class-lasso-db-script.php` 160-185, 710-730. Grepped:
`LASSO_FIELDS`, `LASSO_FIELD_MAPPING`, `Field_Mapping`, every `*_FIELD_ID`, `page=fields`, `#field-edit`,
`custom_fields_support`, `proscons`.

Affibloom checked: `src/Domain/Fields/*`, `src/Application/Fields/FieldsService.php`,
`src/Infrastructure/WordPress/OptionFieldRepository.php`, `PostTypeProductRepository::fields()/save_fields()`,
`src/Interface/Rest/{FieldsController, ProductFieldsController}.php`, `src/Interface/Messages.php` 44-46,
`templates/admin/partials/{field-modal, field-row, product-fields}.php`, `assets/admin/product-fields.js`;
`docs/features/F1/requirements.md` B6, B7, D3 and section 5, `analysis.md` 29-39; progress deviation 68; the completion
plan's area 9.

**This copy, 2026-11-14:** `wp_lasso_fields` has **10 rows**: the nine built-ins (1 "Primary Rating" `rating` — renamed
from Lasso's default "Our Rating"; 2 Pros, 3 Cons `textarea`; 99989 Description `editor`; 99990 Product name
`product_name`; 99991 Product Image `image`; 99992 Primary Button, 99993 Secondary Button `button`; 99994 Price `label`)
and **one field of the editor's own, 99995 "DETAILS"** (`text`, no description, order 99999, used by no product).
`wp_lasso_field_mapping` has **5 rows, all field 1** (Primary Rating), all visible: 9701 ASICS Sky Elite FF 3 (4.5,
order 0), 9746 Mizuno Wave Momentum 2, 9800 Adidas Dame 7, 9831 Adidas Dame 8, 10498 Mizuno Wave Lightning Z6 (3.5
each; orders 10, 10, 0, 10); every product is published. `wp_lasso_metadata` has two `show_field_name_1` rows (9701,
9831), both `'false'`. No product uses Pros, Cons or DETAILS. Affibloom's option `affibloom_fields` holds one leftover
test field (id 100, "F1 AC10 Sizes-…", `bulleted_list`). The Display settings' Single element "Pros & cons" is **off**
(`display_elements.single.proscons` false), so Single boxes on this site show no fields and no rating today.

## 3. What Lasso does

| # | Rule | Source |
|---|---|---|
| R1 | **Definitions table** `lasso_fields`: `id` (auto increment), `field_name` varchar(80), `field_type` varchar(50), `field_description` varchar(1000), `order` int default 99999 | models/class-fields.php 108-123 |
| R2 | **Nine built-ins** with fixed ids, names, types, descriptions and `order`: 1 "Our Rating" `rating` "Editor Rating. Position in display is fixed." 6; 2 "Pros" `textarea` "A list of all of the Pros. One per line." 7; 3 "Cons" `textarea` "A list of all of the Cons. One per line." 8; 99989 "Description" `editor` "Add rich text to describe a product." 9; 99990 "Product name" `product_name` "Product name." 1; 99991 "Product Image" `image` "Feature image." 2; 99992 "Primary Button" `button` "Button uses the Primary URL." 3; 99993 "Secondary Button" `button` "Button uses the Secondary URL." 4; 99994 "Price" `label` "Prices automatically update every 24 hours with integrations like Amazon." 5. They are (re)inserted on install and update `ON DUPLICATE KEY UPDATE` type, description and order — **the name is kept**, so a renamed built-in stays renamed; `restore_fields_type` does the same without the name | class-fields.php 16-24, 77-87, 128-150, 260-283; class-lasso-db-script.php 718-729 |
| R3 | **Types.** Choosable (create and Details), in this order, with icons: Short Text `text` (default selected), Copy to Clipboard `copy_clipboard`, Long Text `textarea`, Number `number`, Rating `rating`, Bulleted List `bulleted_list`, Numbered List `numbered_list`. Built-in only, shown on Details only for the field that has it: Hyperlink `button`, Image `image`, Price `label`, Rich text `editor`; `product_name` has no option | class-fields.php 26-39; class-html-helper.php 521-560; field-details.php 69-88 |
| R4 | **Special fields are recognised by** id (1 rating, 2 Pros, 3 Cons, 99992/99993 buttons), by id and type (99989 + `editor` = Description), by type and exact description (`label` + the Price sentence = Price), and in tables also by **name** (`rating` named "Primary Rating" = the primary rating cell) | class-fields.php 238-255, 296-303; class-lasso-object-field.php 289-340; field-mapping.php 36 |
| R5 | **Values table** `lasso_field_mapping`: `lasso_id`, `field_id` (primary key together), `field_value` varchar(1000), `field_order` int default 10, `field_visible` bool default true | models/class-field-mapping.php 48-63 |
| R6 | **Show field name** is stored only for field 1, in `lasso_metadata` (`type` "Field_Mapping", `meta_key` "show_field_name_1", value the string 'true'/'false') | class-field-mapping.php 169-184; pages/fields/class-ajax.php 133-135; class-metadata.php 20-60 |
| R7 | **Fields list** (`edit.php?post_type=lasso-urls&page=fields`, a hidden page, not in the header nav): title "Fields", "Learn" (Lasso's help site), **Add New Field** (→ the Details page without `post_id`). Reached from "Manage Fields" in the Add Field dialog, which opens it in a **new tab** | fields.php 21-42; class-hook.php 406-421; header-new.php; field-create.php 17 |
| R8 | **Search** "Search Fields": `field_name LIKE %…%`, spaces become `%`; ten a page with Lasso's pager ("N-M of T"); the query has no ORDER BY (database order, in practice by id — UNVERIFIED beyond this copy's 10 rows) | fields.php 36-40, 59-63; pages/class-ajax.php 122-125, 548-558; view-js.php 83; class-helper.php 5442-5455 |
| R9 | **Columns:** Name (bold) with the description under it (small); **Type** — an icon by rule: id 99990 text, 99991 image, 99994 dollar, 99989 paragraph; type `button` rectangle, `text` text, `copy_clipboard` clipboard, `textarea` paragraph, `number` hashtag, `rating` star (the two list types get **no icon**); **Usage** = `count(distinct posts)` joined through `lasso_field_mapping`, any post type and status (trash included) | field-row-count.php; class-lasso-db.php 1236-1262 |
| R10 | A row links to the field's Links tab: `page=field-urls&post_id=<id>&urls=<usage>` | field-row-count.php 5 |
| R11 | **Empty list:** the generic "Looks like we're coming up empty." (skull icon); a failed request "Failed to load data." | view-js.php 2590-2600, 2296-2302 |
| R12 | **A field's page header:** h1 = the field's name, or "Add a New Field" without `post_id`; tabs **Links** (a badge with the `urls` address value when > 0) and **Details**, both hidden for a new field; a `copy_shortcode` script with no shortcode on the page (dead) | fields/header.php |
| R13 | **Details form:** **Field Title** (tooltip "Ideal title for use in things like a comparison table header.", placeholder "Size, Weight, Year, etc..."); **Field Type** (select, same tooltip as the title); **Description** (textarea, 3 rows, tooltip and placeholder "Describe how you use this field."); footer **Save Changes** right, **Delete This Field** left | field-details.php 57-118 |
| R14 | **Built-ins** (the nine ids): type and description are `disabled`; "Note: This is a built-in field to Lasso and only the title is editable."; no Delete link | field-details.php 29-43, 96-100, 113-117 |
| R15 | **Save Changes:** dialog "Updating Field" / "Saving your changes now." with a bar; an empty title → "Title is required." (error screen; nothing sent); `field_id` 0 → `create_new_field` then the browser goes to the Fields list; otherwise `update_field` sets name, type and description → the "save-success" toast (field); `status` false → the "save-fail" toast | settings.js 3191-3241; pages/fields/class-ajax.php 152-195; class-lasso-db.php 5517-5551 |
| R16 | **Bug:** a disabled control still sends its value, so saving a built-in's title also writes its type from the select; for Product name (no matching option) the browser's first option, `text`, is written — the type is wrong until the next plugin update's restore (R2) | field-details.php 69-88; settings.js 3210; class-lasso-db.php 5538-5551 |
| R17 | Changing a user field's type touches no stored value (values are read under the new type) | class-lasso-db.php 5538-5551 |
| R18 | **Delete This Field** (shown for every non-built-in, **also on the new-field form**): when the `urls` address value > 0 the script opens `#field_not_delete`, but the page's "Hold Up" dialog is `#group_not_delete` ("Hold Up" / "You can't delete a Field if there are Links using it. Remove all Links using this Field first." / Ok), so **nothing appears**; otherwise "Delete This Field?" / "If deleted, you won't be able to get it back." / Cancel, Delete → "Deleting Field" → `lasso_delete_field` → "Successfully deleted the Field." → after 1.2 s the `redirect_link`, which is malformed (`page=` holds a whole URL). The server checks nothing | field-details.php 113-151; modals/field-delete.php; settings.js 3244-3283; pages/fields/class-ajax.php 200-224 |
| R19 | Delete removes only the `lasso_fields` row; its `lasso_field_mapping` rows stay and are no longer shown (every read joins the definitions) | class-lasso-db.php 5558-5569, 5576-5600; class-fields.php 311-329 |
| R20 | **Links tab** (`page=field-urls`): columns **Image** (thumbnail 50×50), **Link Name** (to the product's page), **Permalink** (`/<post_name>/`); the products with a mapping that are published `lasso-urls`; ten a page; **no add or remove**; empty → the generic empty state | field-urls.php; field-url-row.php; class-lasso-db.php 1283-1310; pages/class-ajax.php 560-564, 700-752 |
| R21 | The tab's badge is the address's `urls` (Usage: every status) while the list shows published products, so the two can differ | fields/header.php 9-16; R9; R20 |
| R22 | **Product page:** the custom fields' rows, then **Add Custom Field** and "Learn" | url-details.php 276-293 |
| R23 | **Add Field → Create new:** Field Title, Field Type (R3's seven, Short Text first), Description, **Create Field**: `lasso_create_new_field`; an empty title → `status` false and the dialog returns to Create new **silently**; success → clears the inputs, opens "Add from library" and reloads it; the field is **not** added to the product | field-create.php; view-js.php 3004-3045; pages/fields/class-ajax.php 85-104 |
| R24 | **Add from library:** "Find a field by searching here" (R8's search), rows name, description and type icon, the built-ins 99989-99994 left out, ten a page; a click → `lasso_add_field_to_page` (rating starts "3.5", others ""; `field_order` takes the column default 10; already attached → nothing changes), the dialog closes, the rows reload, the preview refreshes | rows/field-row.php; pages/class-ajax.php 312-354; url-details.php 919-964; class-field-mapping.php (model) 103-126 |
| R25 | **A field row:** a grip (Primary Rating is pinned to the first place, `static-0`); the name with the description as tooltip; switch "Show this field in your display by default." (`field_visible`); Primary Rating also "Show field name in your display."; the input by type — text and copy → text box, long text and lists → textarea (3 rows), number → number box, rating → number 1-5 step 0.1, placeholder 3.5, empty shown as 3.5, with stars; the placeholder is the description; × removes | rows/field-details-row.php |
| R26 | **Saved as edited:** leaving a value, turning a switch or dropping a row sends **every row** `[field_id, lasso_id, value, visible, show_name]` to `lasso_save_field_positions`; each value's lines are trimmed and blank lines dropped first; `field_order` = the row's position from 0; rows are upserted; `show_name` is stored for field 1 only | url-details.php 890-905, 1041-1100; lasso-helper.js 534-551; pages/fields/class-ajax.php 109-147 |
| R27 | **Rating input** (as typed): 0 or not a number → 1, ≥ 5 → 5, one decimal; the stars follow | url-details.php 906-917 |
| R28 | **Remove:** 'Remove "<name>"?' / "If removed, you won't be able to get its value back." / Cancel, Remove → `lasso_remove_field_from_page` deletes the mapping; an unsaved product → "Unsaved url can not be deleted."; the row goes, the preview refreshes | modals/url-field-delete.php; url-details.php 1033-1039, 2042-2075; class-lasso-db.php 5497-5510 |
| R29 | The product's rows load with every attached field in `field_order`; none → "Looks like we're coming up empty."; failure "Failed to load data." | url-details.php 966-1030; class-fields.php 311-329 |
| R30 | **AJAX contract** (all six): nonce `lasso_ajax` in `security` (added by the jQuery prefilter), capability `edit_posts`, else 403 `{ message: "Unauthorized." }`. `lasso_add_field_to_page` { field_id, post_id } → { status, post }; `lasso_remove_field_from_page` { field_id, post_id } → { status, post }; `lasso_create_new_field` { title, type, description } → { status, post }; `lasso_save_field_positions` { data: rows } → { data: 1, post }; `lasso_store_field` { field_id, field_title, field_type, field_description } → { status, new_field, link (new only), post }; `lasso_delete_field` { post_id } → { result, post, redirect_link } | pages/fields/class-ajax.php 23-224; lasso-helper.js 5-30 |
| R31 | Nothing is validated on the server: names may repeat, any type string is stored, values are not sanitized and are printed raw in displays (HTML renders); no product or field existence check | pages/fields/class-ajax.php 48-147; class-lasso-db.php 5517-5530; displays/field-row.php 44 |
| R32 | **Displays read** the visible mappings in `field_order`; field 1 is taken out as the primary rating (with its show-name flag), the rest are "user created" | class-lasso-affiliate-link.php 488-520, 618-623; class-lasso-db.php 5576-5600 |
| R33 | **Single display markup** per field: Pros/Cons `<div class="lasso-fields-{lowercase name} lasso-fields-{id}"><strong>{name}:</strong><ul><li><span class="lasso-check|lasso-x">…` (blank lines skipped); a non-primary rating → stars with `lasso-stars-value`; lists `<ul|ol class="list">`; copy → value and a copy button; others `nl2br(value)`; primary rating: stars, with "<name>:" when show-name is 'true' | displays/field-row.php; class-html-helper.php 428-518; layout-2-box.php 53-95 |
| R34 | **Shortcode and settings:** `rating`, `pros`, `cons`, `pros_label`, `cons_label`, `field`/`fields`="hide", `fields`="demo" with `show_pros_cons`; the Display element "Pros & cons" gates every field and the rating | single.php 84-175; class-helper.php 3100-3121; class-lasso-shortcode.php 378-396 |
| R35 | **Tables use fields:** the six table built-ins, the cell renderer, the "Description Editor" dialog (`field-description.php`, Quill), the library without fields already in the table, ordered by `order` then id, six a page | pages/class-ajax.php 312-354; field-mapping.php; field-description.php; lasso-tables.js 146-231; fields/field-details-row.php |
| R36 | **Schema** JSON-LD reads Pros (2) and Cons (3), lines joined by separators | class-hook.php 1415-1430, 1545-1610; class-helper.php 6494-6525 |
| R37 | **Imports write fields:** WP All Import's `lasso_fields` ("type>>name>>value", "||" between) creates a field by name and type when missing; AAWP import maps shortcode→text, custom_button→button, custom_text/custom_html→textarea, reviews→number, star_rating→field 1 | class-hook.php 1807-1880; class-import.php 760-800, 1316-1333 |
| R38 | The post editor's **quick detail** dialog loads, adds, removes and orders a product's fields with the same actions | modals/url-add.php 990-1250 |
| R39 | **Dead:** `modals/field-edit.php` ("Edit Field", "Update Field") and `rows/add-field-row.php` (a sample "Material" row) are included nowhere | grep `field-edit.php`, `add-field-row.php`, `#field-edit` |

## 4. Decisions

Settled by Claude from Lasso and the records:

- **D1 Storage stays 14C's:** the option `affibloom_fields` holds the editor's own definitions and gains a `names` map
  for renamed built-ins; each product's rows stay in `_affibloom_fields`. Ids of the editor's fields are kept from
  Lasso by Task 26 (99995 "DETAILS" keeps 99995); a new one gets max(id) + 1, at least 100.
- **D2 The nine built-ins live in code** with Lasso's ids, types and descriptions; their names are Lasso's defaults
  except field 1, "Primary Rating" (14C, this site's name); every built-in's title can be changed (R2, R14). The six
  table built-ins (99989-99994) are in the library with a `table_only` flag: listed on the Fields screens, left out of
  the product page's library (R24) — they are filled by tables (F25).
- **D3 Types:** `FieldType` gains `label` (Price), `button` (Hyperlink), `image` (Image), `editor` (Rich text),
  `product_name` (Product name), with `choosable()` false; create and Details offer only the seven (R3).
- **D4 A built-in's type and description are never changed by a save:** the server takes only the title (fixes R16).
- **D5 The delete rule is the server's:** a field any product has (any status, the Usage count) → 409
  `affibloom_field_in_use` and the "Hold Up" panel; a built-in → 409 `affibloom_field_built_in`; otherwise the definition
  goes, so no orphan rows remain (R19). The dialog Lasso meant to show (R18) is shown; the redirect lands on the list.
- **D6 No Delete on the new-field form** (there is nothing to delete; R18).
- **D7 Links `<n>` counts the products the tab lists** (published), from the server, not from the address (R21).
- **D8 Titles:** required, "Title is required." (Lasso's Details sentence; now also in the dialog, under the field,
  where Lasso is silent); at most 80 characters (R1; 14C's message); names may repeat (R31). Description at most 1,000.
  The type must be a choosable type (14C's "Choose a field type.").
- **D9 Values:** at most 1,000 characters (R5), refused under the row with "Keep this value under 1,000 characters.".
- **D10 Lines are cleaned on the server:** each line trimmed, blank lines dropped, for every value (R26's rule).
- **D11 Search:** case-insensitive, spaces as wildcards, on the Fields list and the library (R8, R24).
- **D12 Order:** 14C's Move up / Move down stay and a drag (pointer events) is added, as Groups' D7; Primary Rating
  stays first; a new field goes last (Lasso's default order 10 is renumbered at the next save anyway).
- **D13 Pages:** `admin.php?page=affibloom-fields` (the list) and `admin.php?page=affibloom-field&id=N[&tab=details]`
  (Links by default, as Lasso's row link); without `id`, "Add a New Field". Not in the header nav (Lasso's); "Manage
  Fields" opens the list in a new tab. "Learn" is left out (deviation 52).
- **D14 Who:** `Capabilities::PRODUCTS` on both screens and every route (decision 1); REST nonce through `wp.apiFetch`.
- **D15 The lists load in place** (Groups' D13): the page prints the first list; search and page links fetch the same
  partial; the address follows; without JavaScript the search is a form and the pages are links.
- **D16 Icons:** Lasso's rule (R9) with Aurora's icon set; the two list types get list icons (Lasso shows none).
- **D17 Dead markup (R39) is not built** — it is unreachable in Lasso, so nothing is lost.
- **D18 After creating on the Fields screen** the list opens (Lasso) with "Field saved.".
- **D19 After saving Details** the tab stays, with the toast "Field saved." (U6's plain wording).

**Needs the user:**

- **N1 HTML in field values.** Lasso stores values unfiltered and prints them raw, so HTML in a value renders (R31);
  14C strips HTML (one-line values) and keeps plain lines. Recommendation: **keep plain text**, escaped on output —
  no value on this site holds HTML (five ratings), and raw HTML is an injection path. If formatting is wanted, allow
  `wp_kses_post` for Long Text only.
  → **Decided 2026-11-14:** C9: the user accepted the recommendation: plain text, escaped on output.

## 5. Scope that needs a home

- **Fields in displays** (R32-R34: the markup per type, the primary rating and its label, the Pros & cons element,
  the shortcode attributes, the copy button's script) → **Phase 3**, the Single box tasks (18-22), then List/Grid.
- **Fields in tables** (R35, the six table built-ins, the cell renderer, the Description Editor, the "Primary Rating"
  name rule of R4) → **F25** (Comparison tables). F25 reads `FieldsService::all()` and `_affibloom_fields`.
- **Schema** (R36) → the schema task of Phase 3 (reads fields 2 and 3).
- **Migration** → **Task 26**: `lasso_fields` → `affibloom_fields` (user rows with their ids; built-in names into
  `names`), `lasso_field_mapping` → `_affibloom_fields` (ordered by `field_order`, `field_visible`, values cleaned as
  D10), `show_field_name_1` → `show_name`; orphans (R19) are skipped.
- **Imports that write fields** (R37, WP All Import and AAWP) and the post editor's quick detail (R38) → the imports
  area and Phase 4 (F4); both call `FieldsService` (create by name and type, attach, update).
- The Display colours `display_color_pros` / `display_color_cons` are Display settings (built, 10x) read by the box.

## 6. Edge cases

1. A title with HTML or quotes: `sanitize_text_field`, printed escaped everywhere (Lasso prints raw).
2. A field deleted while a product page is open: the next save drops its row silently (14C's `update()` skips unknown
   ids); D5 makes this impossible for a used field.
3. A type changed from text to rating: stored values stay; the rating box shows them clamped (R27) and the next save
   stores the clamped value.
4. Two editors renaming one field: the last save wins.
5. A trashed product keeps its rows: counted in Usage (R9), not listed on the Links tab (R20), so deletion is refused
   until it is deleted for good.
6. Search with `%` or `_`: escaped (`$wpdb->esc_like` in the count query; plain matching in PHP).
7. An option written by an older Affibloom without `names`: read as no renames.
