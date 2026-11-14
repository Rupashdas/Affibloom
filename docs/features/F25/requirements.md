# F25 Comparison tables: requirements

Status: gates 1-2 written 2026-11-14 from a sweep of Lasso 369's Tables (area 8 of
`affibloom-docs/plans/archive/dashboard-completion-plan.md`; the user's decision 2026-11-13: in scope). Gate 4 for Tasks
T1-T17: waiting.

## 1. User stories and acceptance criteria

- As an editor, I build a comparison table from my products (a row or a column per product, a column or a row per
  group of fields), so readers can compare them side by side and click through.
- As an editor, I find, copy and delete tables, and see where each table is used.
- As a reader, I see the table as Lasso drew it: same markup, same theme, a card per product on a phone.

- **AC1** Affibloom → **Tables** lists the tables, ten to a page, oldest first: Name (opens the builder), Products (each
  product's name, linked to its page, in table order; none: "No products in this table yet."), Theme, Style
  (Horizontal / Vertical), Locations, Clone. "Search All <n> Tables" + Enter finds a table by its name or a product's
  name (spaces as wildcards); the address keeps `search` and the page; "x-y of n" under the list; with no tables (or no
  match), Lasso's empty state. (T1; R4-R8, R58)
- **AC2** **Create Table** (on the list, on the builder, or `page=affibloom-tables&create=1`) opens the dialog: Table
  Name, the Vertical / Horizontal tiles (Vertical ticked); without a name, 'Please fill out "Table Name" field';
  **Create Table** makes a Cactus table with headers shown and field names shown and opens its builder. (T2; R12)
- **AC3** The builder shows the table's name as its heading (live while typing), Saved / Saving, **All Tables `<n>`**,
  and Name, Options (Show Field Name; Show Headers, Horizontal only), Style and Theme; leaving Name, or changing any of
  the others, saves at once and redraws the builder; an empty Name is not saved; Style changes the button to **Add
  Row** (Vertical) or **Add Column** (Horizontal). (T3; R13-R16)
- **AC4** **Add Product** opens "Add Product to Table" ("Search <n> URLs", six a page, **Add Product** on each); the first
  product gets Lasso's starting layout (Image | Product name, rating, Price | Primary Button), later products the same
  fields filled from their own data; a product already in the table: "This product already exists, please select
  another product". (T4; R17-R20, R22)
- **AC5** A product's header cell has **Badge** (saved on leaving it), a grip, **Remove this product** ('Remove
  "<name>"?' → the product and its cells leave the table) and **Add field**. (T4; R21, R23)
- **AC6** **Add Group** opens "Add group to Table" ("Search <n> Groups", "<n> link(s)", **Add Group**); every published
  product of the group joins the table, last, once each. (T5; R24, D6)
- **AC7** **Add Row / Add Column** adds an empty row or column ("Click to add a Field" in each cell); with no products:
  "Please add a Product before adding a field"; with an empty one already: "Please add a Field to the empty column
  before adding another". (T6; R25)
- **AC8** **Add field** (+) opens "Add Field" (Manage Fields, Create new / Add from library); the library lists every
  field not yet in the table, six a page, searchable; picking one adds it to that row/column for every product, filled
  from each product; a field already in the table: "This field already exists, please select another field". (T6;
  R26-R28)
- **AC9** Text, number, rating (1-5, one decimal, stars), long text, list, copy-to-clipboard and product-name cells save
  their value on change; a removed field ('Remove "<field>"?', Remove) leaves that row/column for every product, and a
  row/column left empty goes. (T6; R29-R31, R33-R34)
- **AC10** An image cell opens the media library ("Select an Image", "Select Image") and saves the picked URL; a price
  cell is a one-line rich editor (non-Amazon) or the live Amazon price (read only); a description cell opens
  "Description Editor" (Cancel / Save). (T7; R29, R32)
- **AC11** A button cell's edit icon opens 'Edit "<field>"': Button Text and Button URL (Primary: the product's link,
  read only; Secondary: editable); missing text or URL is refused with Lasso's sentence; Save redraws the cell. (T8; R35)
- **AC12** Products, rows/columns and fields reorder by drag and by Move buttons (keyboard); a field moved to another
  row/column moves for every product with its values; the order holds after a reload. (T9; R36-R39, D9)
- **AC13** A table used in content shows its count in Locations; clicking it opens "Table Locations" (Content, Action;
  title to its editor, address, view), six a page. (T10; R9, R40)
- **AC14** Clone asks "Do you want to clone <name>", makes "<name> - Copy" with the same products, fields, values and
  settings, and shows the list's last page. (T11; R10)
- **AC15** **Delete This Table**: unused → 'Delete "<name>"' ("If deleted, you won't be able to get its back.") → OK →
  the Tables list; used → "Hold Up" ("You can't delete a Table if it is in use on your site. Remove this Table from
  your content first.", Got It) and nothing deleted, also when the request is sent directly. (T11; R41)
- **AC16** `[affibloom type="table" id="<table>"]` draws a Horizontal table as Lasso does (header row of field names
  when Show Headers is on, a row per product, badge in the first cell, Lasso's link attributes on image, name and
  buttons, theme class, `theme` and `data_nosnippet` attributes); the Lasso render of tables 2 and 3 on staging and
  Affibloom's match pixel for pixel at 1280 px. (T12; R42-R44, R46-R52)
- **AC17** A Vertical table draws a column per product, a row per field, the badge row on top, set widths up to four
  products and a scrolling row past four. (T13; R45)
- **AC18** At 767 px and narrower, either layout shows one card per product (badge, then every field in order) and
  hides the desktop table; at 1000 px and narrower a Vertical table hides its fourth product. (T14; R46, R53)
- **AC19** **Preview Table** swaps the builder for the reader's table and the button for **Edit Table** (Add buttons
  hidden); style, theme and toggle changes redraw the preview; **Edit Table** returns. (T15; R54)
- **AC20** The Dashboard filters panel's **Comparison table** (Any / In a comparison table / Not in any table) narrows
  the list, counts as a filter and is kept in a saved view. (T16; R59)
- **AC21** A product deleted for good leaves every table it was in; its cells go with it; the tables still draw. (T17;
  R60, D15)

## 2. Evidence

All read completely: `pages/table-details/class-ajax.php` (1-1050); `classes/class-table-detail.php`;
`models/class-table-details.php`, `class-table-mapping.php`, `class-table-field-group.php`,
`class-table-field-group-detail.php`, `class-table-title-field.php`; `libraries/table/class-table-field-group.php`,
`class-table-field-group-detail.php`; `admin/views/tables/{tables,header,table-details}.php`,
`components/{table-row,table-comparision}.php`; `admin/views/displays/{table,table-vertical,table-horizontal,
table-mobile}.php`; `admin/views/modals/{table-create,table-edit-button-information,table-product-group,
table-product-link,field-create,field-description,url-field-delete}.php`; `admin/views/education/{table-details-empty,
list-table-empty}.php`; `admin/views/fields/{field-mapping,field-details-row}.php`; `admin/views/rows/{field-row,
monetize-row}.php`; `admin/assets/js/lasso-tables.js` (1-2809; the horizontal drag handler 1811-1990 mirrors the
vertical one 1639-1806 and was read for its differences only), `table-product-link.js`.
Shared code read where tables call it: `classes/class-lasso-shortcode.php` 205-505 (attributes, the `table` branch);
`classes/class-helper.php` 2997-3020, 3158-3227 (customised settings, table disclosure), 5129-5150 (front-end CSS);
`classes/class-html-helper.php` 579-596, 664-690; `libraries/class-lasso-url.php` 214-289 (link attributes);
`models/class-fields.php` 16-103, 157-302; `models/class-model.php` 226-231, 517-530 (`__call`);
`pages/class-ajax.php` 290-360 (the field library for `table-details`), 1176-1290 (product and group search);
`classes/class-lasso-db.php` 1236-1261, 1753-1858, 5558-5568; `classes/class-lasso-cron.php` 2095-2160, 2325-2340,
5545-5590; `classes/class-dashboard-saved-views.php` 95-120, 360-367; `admin/views/dashboard/dashboard.php` 180-190;
`pages/url-details/class-ajax.php` 355-385; `admin/views/dashboard/url-details.php` 2120-2160; `classes/class-import.php`
80-90, 715-830, 1265-1300; `classes/class-lasso-db-script.php` 740-780; `pages/class-hook.php` 740-760, 1165-1200;
`admin/views/header-new.php` 60-70, 155-170; `admin/assets/js/lasso-helper.js` 86, 170-260, 675-716;
`admin/assets/js/view-js.php` 100-125, 1955-2030, 2480-2490 (library rows in the builder);
`admin/views/modals/display-add.php` 30-45, 130-160, 420-440, 895-935, 1000-1035; `admin/assets/js/
lasso-gutenberg-block.js` 605-720; `classes/class-setting-enum.php` 19-52; CSS `lasso-table-frontend.min.css` (37 KB:
the 8 theme blocks, breakpoints) and `lasso-tables.css` 150-210, 390-400.

**This copy, 2026-11-14 (SELECT only):**

| Table | Style | Theme | Products (order, badge) | Rows of fields (columns, in order) |
|---|---|---|---|---|
| 2 "Volleyball Shoe" | Row (Horizontal) | Cactus | ASICS Sky Elite FF 3 "Best Overall Shoe", Mizuno Wave Momentum 2 "Runner Up", Nike Lebron 18 "Best Basketball Shoe", Nike React Hyperset "Most Popular", Adidas Dame 7 "Best Budget Shoe (Performance Tested)" | Image + Product name (one heading group) · Description · Primary Button ("Check Best Price") |
| 3 "Test Table" | Row | Cutter | SKLZ Volleyball Rebounder "Runner Up", Nike KD14 "Also Good", Mizuno LR6 Knee Pads "Best Overall" (orders 2-4: one product was removed) | Image · Product name · Description · Primary Button ("Check Current Price") |
| 4 "Best Volleyball" | Row | Cactus | Mikasa V200W Volleyball "Volleyball" | Image · Product name + Primary Rating (empty) + Price ("$84.90") · Primary Button ("Buy Now") |

35 `lasso_table_field_group` rows, 37 `…_detail` rows, 9 mappings, all products published; every badge but table 4's
and every button text holds HTML (`<span class="medal-icon"></span> …`, `<span class="amazon-icon"></span> …`, the
site's icon classes); table 2's product-name cells differ from the products' names ("Asics Sky Elite FF 2", "Nike
Lebron 18s"); description cells hold `<p>` HTML with links. No `Table_Details` metadata (Show Field Name = default on
for all three); `show_title` 0, `show_headers_horizontal` 1 on all; `lasso_table_title_field` does not exist. Field
1 is named "Primary Rating" here (Lasso's default "Our Rating"); a custom field 99995 "DETAILS" (text) exists.
**Use:** the only content using a table is the draft post 9492 "sweet new post": a Lasso block `[lasso id="2"
link_id="2520" type="table"]` and a shortcode block `[lasso id="3" link_id="2503" type="table"]`; no published post,
revision or post meta holds one. Location metadata: table 2 → 1 location, table 3 → 1, table 4 → 0. The feature
plan's "not used" is right for published content; the tables exist and one draft uses two of them.

## 3. What Lasso does

| # | Rule | Source |
|---|---|---|
| R1 | **Storage:** `lasso_table_details (id, title varchar 255, style 'Row'\|'Column', theme, show_title, show_headers_horizontal default 1)`; `lasso_table_mapping (id, title, table_id, lasso_id, order, title_visible default 1, badge_text varchar 255)` = a product in a table; `lasso_table_field_group (table_id, lasso_id, field_group_id varchar 30 'group_'+uniqid, field_id, order)` = a product's cell in one row/column (field_id = its first field, 0 when empty); `lasso_table_field_group_detail (lasso_id, field_id, field_group_id, order, field_value text)` = one field's value in one product's cell; `lasso_metadata (type Table_Details, key show_field_name)`; `lasso_table_title_field` deprecated (folded into the product-name field by a DB upgrade) | class-table-details.php 28-70, 362-377; class-table-mapping.php 25-71; class-table-field-group.php 19-63; class-table-field-group-detail.php 23-67; class-table-title-field.php; class-lasso-db-script.php 740-780 |
| R2 | **Shape:** a field group is shared by every product (same ID); the lowest-order group is the **heading** group (the product's header cell); each product's cell in a group holds the same fields in the same order (adds, removes and in-cell sorts are applied to all products); a field appears **at most once per table** | class-table-mapping.php 231-284; class-ajax.php 257-285, 493-526, 650-678; class-table-detail.php 307-312 |
| R3 | **Fields usable in a table:** every `lasso_fields` row: the built-ins Primary Rating (1, rating), Pros (2) and Cons (3) (textarea), Description (99989, editor, "Add rich text to describe a product."), Product name (99990, "Product name."), Product Image (99991, "Feature image."), Primary Button (99992, "Button uses the Primary URL."), Secondary Button (99993, "Button uses the Secondary URL."), Price (99994, label, "Prices automatically update every 24 hours with integrations like Amazon."), and every custom field (text, copy clipboard, textarea, number, rating, bulleted list, numbered list). The six 999xx fields are table-only: the product page's library leaves them out | class-fields.php 16-103 |
| R4 | **Nav:** "Tables" in the header (after Opportunities, before Groups), active on both table screens; pages `edit.php?post_type=lasso-urls&page=tables` and `&page=table-details&id=N`, Lasso's permission level | header-new.php 64-68, 162; class-hook.php 144-156 (menu) |
| R5 | **Tables list header:** "Tables", "Learn" (Lasso's help site), **Create Table**; search box "Search All <n> Tables" (n = all tables) | tables/header.php 17-47 |
| R6 | **Columns:** Name; Products (tooltip "These are the Lasso Links used in the table."); Theme; Style (tooltip "Is your product data displayed across rows or in columns."); Locations (tooltip "Where this table is used on your site."); Clone (tooltip "Make a copy of this table.") | tables.php 22-30 |
| R7 | **Row:** the name (bold) links to the builder; Products: every product's name, linked to its page, in table order, or "No products in this table yet." (tooltip "Click the table to add a product"); the theme's name; Style "Horizontal" (Row) / "Vertical" (Column); Locations: the count, clickable when above 0, else "0"; Clone icon (tooltip "This will create a copy of this table.") | table-row.php |
| R8 | **List request** `lasso_get_search_list_table` {search_term, page_number, limit 10}: `SELECT *` with no ORDER BY (in practice primary-key order, oldest first); search = title, a product's post title or a mapping's title LIKE `%term%` with spaces as `%`; returns rows' HTML or the empty state, total, page. Search runs on **Enter** only; the term is cleaned (`[^a-zA-Z0-9+-_.#CJK]` → space, trimmed) into `?search=`; the page lives in `#page-N`; pager "x-y of n" (thousands with commas; "0 of 0"), hidden with one page | class-ajax.php 748-792; class-table-details.php 97-126, 231-245; lasso-tables.js 156-165, 2105-2138, 2389-2536 |
| R9 | **Empty list** (and a search with no match): "Create Beautiful Comparison Tables" — "Compare similar products and share alternatives. These tables are great way to drive conversions across multiple products and views of related reviews." and "Learn How to Create Comparison Tables" (help site) | education/list-table-empty.php |
| R10 | **Clone:** "Do you want to clone <name>" (no text), Cancel / OK → `clone_table`: a new table "<title> - Copy" with every mapping, field group (new group IDs) and value; `show_field_name` (metadata) is **not** copied (the copy falls back to on); then the list opens on its last page (ceil(total/10)); none → 409 "Table not found" | lasso-tables.js 46-53, 553-560, 2170-2207; class-ajax.php 841-857; class-table-details.php 151-200 |
| R11 | **Locations count** = link-location rows of any post status whose metadata `Table` = the table's id, joined to existing posts (drafts count) | class-table-details.php 253-296 |
| R12 | **Create Table dialog:** "Table Name" (placeholder "Table Name", required); tiles "Vertical" (ticked by default, `Column`) and "Horizontal" (`Row`); empty name → 'Please fill out "Table Name" field' (no request); **Create Table** shows a spinner → `lasso_create_comparison_table` {table_name, table_style, table_theme}: no name → 409 "Table name is not found"; stores theme Cactus (the handler reads the key `$table_theme`, so the sent theme is never used), show_title 0, show_headers_horizontal 1 → opens the builder; a failure shows the server's text and restores the button. Opened by Create Table on either screen and by `page=tables&create=1` (the editor's link), which then drops the parameter | table-create.php; class-ajax.php 862-889; lasso-tables.js 177-180, 273-275 |
| R13 | **Builder header:** heading = the table's title (updated on every key in Name); "Learn"; Create Table; **Copy Shortcode** (`[lasso type="table" id="N"]`, tooltip "Copy this Display to your clipboard." → "Copied!", hidden until the table has an id); "Saved" / "Saving" (spinner) during every request; **All Tables `<n>`** | tables/header.php 8-71; lasso-tables.js 421-423, 2141-2167 |
| R14 | **Settings row:** Name (tooltip "Use this to identify your tables", placeholder = the title, or "Untitled Table" / "Untitled Table <k>" when new); Options: **Show Field Name** toggle (tooltip "Show field name in a Table"), **Show Headers** toggle (tooltip "Hide the column header in a Horizontal Table", shown for Horizontal only); **Style** (tooltip "This changes the orientation of your table": Horizontal = Row, Vertical = Column); **Theme** (tooltip "Change the look and feel of your table": Cactus, Cutter, Flow, Geek, Lab, Llama, Money, Splash) | table-details.php 19-104; class-table-detail.php 321-336; class-setting-enum.php 29-52 |
| R15 | **Autosave of settings** (`add_or_update_table` {table_id, title, style, theme, show_headers_horizontal, show_field_name}): on leaving Name (nothing is sent when Name is blank), on Style, Theme and either toggle; the server refuses a blank title with 409 "Please input table name"; without a table id it creates one and puts `id` in the address and the shortcode in the header; afterwards the builder (or the preview) is redrawn. Style also switches the button label and the Show Headers row | lasso-tables.js 125-143, 182-191, 414-438, 734-798; class-ajax.php 405-427 |
| R16 | **Builder actions:** Add Product, Add Group, Add Row (Vertical) / Add Column (Horizontal), Preview Table (right), shown once the script runs; an empty table shows "Add a Product or Group to get started."; **Delete This Table** (red, below) | table-details.php 105-157; education/table-details-empty.php |
| R17 | **"Add Product to Table":** search "Search <n> URLs" (Enter), rows thumbnail, name, address, button **Add Product**; six a page; nothing found: "Looks like we're coming up empty, partner."; loaded on first opening only; Add closes it and adds | table-product-link.php; table-product-link.js 1-67, 138-150; lasso-tables.js 75-99, 537-548, 565-568 |
| R18 | **Adding a product** (`add_product`): no table id → 409 "Please input the table name"; already in → 409 "This product already exists, please select another product"; not a Lasso link → 409 "Invalid Lasso post"; placed last; its badge starts as the product's own badge text | class-ajax.php 295-320; class-table-mapping.php 165-221 |
| R19 | **The first product's layout:** heading group = Product Image; second = Product name, Primary Rating, Price; third = Primary Button | class-table-mapping.php 202-218 |
| R20 | **Later products** get the first product's groups and fields (group order kept), each filled from their own data; the product-name cell = the product's name | class-table-mapping.php 168-188 |
| R21 | **Header cell** (heading group): **Badge** (label "Badge", placeholder "Badge"), saved trimmed on leaving it (`update_product_mapping` {badge_text}); a grip; trash (tooltip "Remove this product"); + (tooltip "Add field") | table-comparision.php 100-165, 352-412; lasso-tables.js 2627-2669; class-ajax.php 685-707 |
| R22 | **Initial cell values** (`link_field_group_detail`): the product's own value of that field when it has one; else Description → its description, Product name → its title, Image → its image, Price → its price, Primary Button → {button_text: its primary text, url: its public link}, Secondary → {its secondary text, secondary URL}; else empty. The values are **copies**: later product edits do not reach them | class-table-mapping.php 294-340 |
| R23 | **Remove a product:** 'Remove "<name>"?' / "If removed, you won't be able to get its back." / Cancel, OK → its mapping, cells and values deleted → redraw | lasso-tables.js 38-44, 443-449, 828-855; lasso-helper.js 213-220; class-ajax.php 434-451 |
| R24 | **"Add group to Table":** "Search <n> Groups", rows name and "<n> link(s)", **Add Group**, six a page; adds every **published** product of the group (`get_urls_in_group`), each through `add_product` **without** the duplicate check (a product already in the table is added twice) | table-product-group.php; table-product-link.js 69-135; class-ajax.php 329-342, 1245-1290; class-lasso-db.php 1753-1794 |
| R25 | **Add Row / Add Column** (`add_col_field`): no products → 409 "Please add a Product before adding a field"; an all-empty group exists → 409 "Please add a Field to the empty column before adding another"; a 2-second lock against double clicks; an empty group (field 0) for every product, last; spinner on the button, scroll to the new row/column; empty cells show "Click to add a Field" | class-ajax.php 84-104, 201-248; lasso-tables.js 1151-1210; table-comparision.php 20-51 |
| R26 | **"Add Field" dialog:** "Add Field", **Manage Fields** (the Fields screen, new tab); tabs "Create new" and "Add from library" (opens on the library); Create new: Field Title (placeholder "Size, Weight, Year, etc...", tooltip "Ideal title for use in things like a comparison table header."), Field Type, Description ("Describe how you use this field."), **Create Field** (then the library is searched for the new title) | field-create.php; lasso-tables.js 101-104, 495-532 |
| R27 | **Library in the builder:** every field not already in the table (its heading cells and first products' cells are read), ordered by the field's order then ID, six a page, search "Find a field by searching here" (name LIKE); rows name, description, type icon | pages/class-ajax.php 313-360; view-js.php 112-114, 1966-2010; rows/field-row.php |
| R28 | **Adding a field** (`add_field_to_product` {field_id, lasso_id, field_group_id, order}): no product → 409 "Please add a Product before adding a field"; in the table already → 409 "This field already exists, please select another field"; added to that group for every product (values per R22), appended in the cell; the first field of an empty group becomes the group's field | class-ajax.php 78-83, 257-285; class-table-mapping.php 231-284; lasso-tables.js 297-354 |
| R29 | **Cell editors** (per field): a label with the field's name (no tooltip, but an Amazon price says "Amazon prices are updated automatically"); text, product name, copy clipboard: one-line input (placeholder = the field's description); long text and lists: three-line box; number: number input; rating: number 1-5, step 0.1, default 3.5, with stars, shown to one decimal and capped at 5 while typing; image: the picture with "Update Image" on hover → the media library ("Select an Image", images, one, "Select Image") → saved at once; Primary / Secondary Button: the button with its text (a Secondary without URL: "No Secondary Button URL set.") and an edit icon; Price: Amazon → the live price, read only; else a one-line rich editor (bold, italic, underline, strike, text colour from Lasso's palette; placeholder "N/A"); Description: its HTML and an edit icon; every field: a remove (×) icon | field-details-row.php; lasso-tables.js 375-385, 1368-1445, 2404-2434 |
| R30 | **Saving a cell** (`update_field` {table_id, lasso_id, field_id, field_group_id, field_value}): 500 ms after a change (number inputs also on click), empty lines removed; no such cell → 409 "Unexpected error. Please contact your developer"; the value passes the filter `lasso_filter_table_field_value`; Price is cleaned by the price allow-list; **every other value is stored as sent** | lasso-tables.js 397-409, 690-726; class-ajax.php 349-396 |
| R31 | **Removing a field:** × → 'Remove "<field>"?' / "No data is deleted and the Field can always be added back." / Cancel, Remove → `remove_field`: the field leaves that group **for every product** (its values deleted, despite the sentence); a group left empty is deleted | lasso-tables.js 28-35, 359-370, 1319-1366; url-field-delete.php; class-ajax.php 650-678 |
| R32 | **"Description Editor"** (tooltip "Add rich text to describe a product.", Manage Fields link): rich text (bold, italic, underline, strike, link, bulleted list, text and background colour, clear; placeholder "Enter a description"), Cancel / Save (spinner) → `update_field` → the cell shows the new HTML | field-description.php; lasso-tables.js 221-268, 2214-2300 |
| R33 | **Vertical builder, "Remove same field →"** (trash on the first product's cell of each row): `remove_fields` deletes the whole row (all its fields, every product) and redraws | table-comparision.php 513-516; lasso-tables.css 394-400; class-ajax.php 458-469; lasso-tables.js 862-901 |
| R34 | **Builder layout:** Horizontal = a row per product (header cell left, groups as columns); Vertical = a column per product (header cell on top, groups as rows); widths split evenly up to five, a scroll bar past five; heights equalised; the theme's class on the builder | table-comparision.php 53-77; lasso-tables.js 906-1027 |
| R35 | **'Edit "<field>"' (button):** Button Text (required), Button URL (Primary: the product's public link, read only; Secondary: editable, its stored URL); errors 'Missing the "Field Group Detail Id" information', 'Missing the "Button Text" information', 'Missing the "Button URL" information'; Cancel / Save (spinner) → `lasso_edit_button_field_information` {field_group_detail_id, button_text, button_url} stores {button_text, url} in that one cell (no table or product check) → redraw | table-edit-button-information.php; lasso-tables.js 2538-2622; class-ajax.php 990-1014 |
| R36 | **Reorder products:** drag a header cell (Vertical: along the row; Horizontal: up and down) → `sort_products` [{lasso_id, order}] → redraw | lasso-tables.js 1034-1079, 1664-1690, 1836-1853; class-ajax.php 476-486 |
| R37 | **Reorder rows/columns:** drag a row (Vertical) / column (Horizontal, along the row) → `sort_field_group` [{field_group_id, order}], the heading group kept first; no redraw | lasso-tables.js 1471-1566, 1649-1662, 1820-1834; class-ajax.php 714-732 |
| R38 | **Reorder fields in a cell** → `sort_field_inside_group` [{lasso_id, field_id, field_group_id, order}], applied to that field for every product; the same field twice → 409 "Field already existed in this cell"; nothing sent → 409 "Invalid data"; then redraw | lasso-tables.js 1088-1144; class-ajax.php 493-526 |
| R39 | **Move a field to another row/column** → `update_field_to_other_group` {from/to lasso_id, from/to group, field_id}: moved for every product with its values; the target already has it → 409 "This field already exists, please select another field"; a group left empty goes; a drag never starts from an input, editor, image, edit or remove icon | lasso-tables.js 3, 1220-1268, 1692-1805; class-ajax.php 533-624 |
| R40 | **"Table Locations"** (large dialog, no buttons): heading row "Content" (tooltip "This is the post or page this table was found in.") and "Action"; rows: the post title (its editor, new tab), its address (new tab), an eye (view); six a page (`link_location_limit`); the page is remembered per table in local storage; the list's count is refreshed | lasso-tables.js 65-73, 2671-2794; lasso-helper.js 86; class-ajax.php 1019-1049 |
| R41 | **Delete This Table:** `lasso_get_table_location_count` → 0: 'Delete "<name>"' / "If deleted, you won't be able to get its back." / Cancel, OK → `lasso_delete_table` deletes values, groups, mappings and the table → `/wp-admin/edit.php?post_type=lasso-urls&page=tables` (a root path); in use: "Hold Up" / "You can't delete a Table if it is in use on your site. Remove this Table from your content first." / Got It; the server refuses too (status 0, 'Delete unsuccessful since <title> are using', shown 3 s); missing → 409 "Table is not found"; the `show_field_name` metadata stays | lasso-tables.js 55-63, 2302-2387; class-ajax.php 897-985 |
| R42 | **Every builder action** checks the nonce `lasso_ajax` (field `security`; 403 "Invalid nonce.") and `edit_posts` (403 "Unauthorized."); the builder's one action `lasso_get_table_comparison_view` dispatches on `sub_action` and answers {status, lasso_id, table_id, product_count, html}; a failure shows the server's text or "An unexpected error has occurred please try again later." in a red notice | class-ajax.php 49-159; lasso-helper.js 679-702 |
| R43 | **Shortcode:** `[lasso type="table" id="N"]` (the builder's preview passes `table_id`); attributes `theme` (overrides the table's; empty → the table's), `data_nosnippet="enable"` (adds `data-nosnippet`), `anchor_id` (else `lasso-anchor-id-<id>-<link_id or uniqid>`); a missing table draws nothing; a table without products draws no table (only R51); `type="table"` renders without a product | class-lasso-shortcode.php 241-276, 371-375, 464-505; displays/table.php 21-47 |
| R44 | **Wrapper:** `<div id="<anchor>" class="lasso-display-table ls-dp lasso-table-<theme>-theme [lasso-ds-v2-frontend]" [data-nosnippet] [v2 attrs]>`, then the layout, then the timestamp (R50), the disclosure (R51) and the brag icon | displays/table.php 43-121 |
| R45 | **Vertical (Column):** `div.table-vertical.table-vertical-desktop.template-1[.include-badge][.table-vertical-scroll]` > `table.lasso-table.table-borderless.table-title`; **one `tr` per field** (every field of the table, by group order then field order), one `td.text-center.field.field-group-detail` per product (width `100/n %` up to four products; past four no width and the scroll class); the first row's cells start with `div.badge-text-wrapper > span.badge-text` (badge or `&nbsp;`); `div.cell[.has-badge-text][.no-border-left]` (no left border after a badged cell) > `div.cell-content` | displays/table-vertical.php |
| R46 | **Horizontal (Row):** `div.row-style.template-1.horizontal-table` > `table.lasso-table[.hide-column-headers]`; with Show Headers a `thead` row: per group a `th scope="col"` > `span` of its fields' names joined " / ", words capitalised (unnamed fields skipped); a `tr` per product, a `td.td-template-1 > div.cell` per group, the first with the badge (`div.badge-text-wrapper > span.badge-text`) when there is one, then `ul.field-group > li` per field | displays/table-horizontal.php |
| R47 | **Phone cards** (both layouts): `div.table-vertical.table-vertical-mobile.tb-vt.tb-vt-mb.template-1` (style `box-shadow: none !important;`), per product `div.mobile-box[.has-badge-mobile.has-bm]` > optional `div.badge` > `div.mobile-item-wrapper.mb-i-wp` > `div.mobile-item-child.mb-i-chl.field` per field, in the layout's order | displays/table-mobile.php |
| R48 | **Field output:** rating → `div[data-tooltip=name] > div.lasso-stars[style=--rating: v] > span.lasso-stars-value` (empty → 3.5), its name label when (not "Primary Rating" and Show Headers off) or Show Field Name; product name → `a.product-name.lasso-title` with the link attributes when the site's "link from display title" is on, else `h3.product-name.lasso-title`, plus the Prime icon when the table has no Price field and the product is Prime; image → `div.img-container > a > img.lasso-image-no-lazy` (lazy-load attributes, alt = product name; a missing .webp falls back to the product's webp/custom image or Lasso's default); Primary Button → `div.btn-container > a.btn.lasso-button-1` (cell text, the **live** public link); Secondary → `a.btn.lasso-button-2` with the cell's URL and second-button attributes, nothing when no URL; Pros/Cons → `div.lasso-fields-<slug>.lasso-fields-<id>` and Lasso's pros/cons list; Description → `div.description-content > div.lasso-fields-single > div.field-value > span` (HTML); bulleted/numbered list and copy-to-clipboard → Lasso's list and copy controls; Price → its label when Vertical, `div.lasso-price > div.lasso-price-value > span.discount-price + span.latest-price` (Amazon: the live price; else the cell; blank → "N/A"), Prime icon; any other → `div.lasso-fields-single > div.field-value > span[.green\|.red]` ("Yes" green, "No" red, case-insensitive), lines kept (`nl2br`); every non-rating field is inside `div.lasso-fields` | fields/field-mapping.php |
| R49 | **Field-name labels:** `<div class="field-name"><strong><name>:</strong></div>`, never for a field named "Price"; for pros/cons, description, lists, copy and custom fields only when (Horizontal and Show Headers) or Show Field Name, **and** Show Field Name | class-html-helper.php 584-588; field-mapping.php 35-37, 102-170; class-table-details.php 324-333 |
| R50 | **Link attributes** on image, name and Primary Button: `target`, `href`, `data-lasso-box-trackable="true"`, `data-lasso-id` (the link location, which tables do not pass: empty), `data-lasso-lid`, `data-lasso-name` (product name, else the domain), `title`, then the product's `rel`; Secondary: `data-lasso-button="2"`, `target2`, `rel2` | class-lasso-url.php 214-287; field-mapping.php 33 |
| R51 | **Under the table:** `span.oldest-updated` (an Amazon product's "last updated" text) when the table has a Price field — which product's is chosen is UNVERIFIED (the comparison sets the newest-or-first by comparing a date string with an object, displays/table.php 52-63); `div.lasso-disclosure` with the site's disclosure text only when customised display settings are on and `show_disclosure_table` is on; then the brag icon (`enable_brag_mode`) | displays/table.php 36-63, 95-121; class-helper.php 3158-3187 |
| R52 | **Front-end CSS:** `lasso-table-frontend.min.css` (37 KB, all 8 themes) enqueued with `lasso-live.min.css` once per request where a display renders, in the block editor's canvas and the Elementor widget; the builder adds `lasso-tables.css` | class-helper.php 5129-5150; class-post-type.php 197-200; class-hook.php 750-756 |
| R53 | **Responsive:** ≤767 px the desktop table and the Horizontal table hide and the cards show; ≤1000 px a Vertical table hides its fourth product's cells | lasso-table-frontend.min.css (@media 767px, 767.98px, 1000px) |
| R54 | **Preview Table:** saves pending edits, swaps the builder for `do_shortcode('[lasso type="Table" table_id="N" ]')` after one second, button "Edit Table", Add buttons hidden; changes to style, theme and toggles redraw the preview; "Edit Table" redraws the builder. The text 'Vertical style limit display <n> products only' is written to `.table-info`, which no screen has (never shown) | lasso-tables.js 461-490, 1273-1312; class-ajax.php 633-643 |
| R55 | **Editor** (F4 owns the screens): the display modal's Table tile opens "Choose a Table" (Create Table link to `page=tables&create=1`, "Search tables", **Add** inserts `[lasso type="table" id="N"]`) fed by `lasso_get_tables` {search_key, limit, page}: id, title, newest first, search as R8; the block's Edit Display opens the builder in a new tab and redraws the block when it closes | display-add.php 38-41, 133-154, 429-445, 906-918, 1002-1033; lasso-gutenberg-block.js 612-617, 626-638, 679-705; class-ajax.php 797-834 |
| R56 | **Locations come from the content scan:** a `[lasso type="table" id]` found in content gets link-location metadata `Table = id`; the table's published products are mapped to that location; after each batch, metadata of vanished rows is deleted (F21 owns the scan; the scan also writes `link_id` into the stored shortcode) | class-lasso-cron.php 2149-2160, 2333-2336, 5551-5590 |
| R57 | **AAWP tables:** importing an AAWP table makes a Vertical Cactus table (its products, badges from AAWP highlights, its rows mapped to fields); reverting deletes it, skipping the location check (F33 owns) | class-import.php 80-90, 729-830, 1271-1300 |
| R58 | **Unused data:** `show_title` (always 0, never read), `title_visible` (always 1), the mapping's `title` (no control writes it; only search reads it) | class-table-details.php; class-ajax.php 685-707; table.php 54 |
| R59 | **Dashboard filter "Comparison table":** Any / In a comparison table (`EXISTS` a mapping) / Not in any table, kept in saved views as `dashboard_comparison_table` | dashboard.php 182-189; class-dashboard-saved-views.php 101-110, 360-367; view-js.php 1079-1090, 1207-1266, 1336-1407 |
| R60 | **Deleting a product** (forced delete of a link in use) first deletes its table mappings (`lasso_delete_post_in_table`); its cells and values stay behind as orphans | url-details/class-ajax.php 361-385; url-details.php 2120-2160; class-table-mapping.php 420-429 |
| R61 | **Deleting a field** on the Fields screen deletes only the definition; its table cells stay, and the reader render reads a missing field (`$field->is_type_rating()` on an empty result — whether this errors is UNVERIFIED: `get_one` was not traced to its empty return) | class-lasso-db.php 5558-5568; field-mapping.php 32-41 |
| R62 | **Cloud:** none in this subsystem except the "Learn" links to support.getlasso.co | tables/header.php 33; list-table-empty.php 10 |

## 4. Decisions

Settled by Claude from Lasso and the records (none drops anything Lasso does or reverses a user decision):

- **D1 Storage.** A post type `affibloom_table` (not public, no UI, no REST, no rewrite; title = the table's name) and
  one meta `_affibloom_table` holding the whole table: `style` (row|column), `theme`, `show_headers`, `show_field_name`,
  `products` [{id, badge}], `groups` [{id, fields: [field ids]}] (first = heading), `values` {product: {field: value}},
  `revision`. R2's invariants (same fields per group for every product, a field once per table) make one value per
  product and field exact; Lasso's four tables repeat that structure per product. A table is always read and written
  whole and is small (the largest here: 5 products × 4 fields). `show_title`, `title_visible` and the mapping title
  (R58) are not kept; the migration reads them and finds nothing to carry.
- **D2 Lasso's table IDs stay findable** (F10 §5): a migrated table has `_affibloom_lasso_table_id`; F31's `[lasso
  type="table" id]` alias resolves through it.
- **D3 Who:** `Capabilities::PRODUCTS` for both screens and every write; `GET /tables` (the list and the editor's
  picker) also for `INSERT_BOXES`, as F4 needs it.
- **D4 Save model: Lasso's.** Every action saves at once (no Save button) and the builder shows Saving / Saved. Each
  write sends the table's `revision`; a stale one is refused (409 `affibloom_table_changed`, "This table was changed in
  another window. Reload to see the latest version.") instead of Lasso's silent overwrite; the builder's requests are
  queued one at a time.
- **D5 The builder is drawn by the server** (ADR-7): each write answers with the builder's HTML (Lasso's `load_table`),
  so the screen never drifts from the store.
- **D6 Add Group skips products already in the table** (R24's duplicate is a defect: R18 refuses the same product
  added alone, and a duplicate breaks R2).
- **D7 A removed field keeps its values** in `values` (until the product or table goes), so Lasso's own sentence "No
  data is deleted and the Field can always be added back." becomes true; adding the field back uses a kept value
  before R22's default.
- **D8 Clone copies everything**, Show Field Name included (R10 loses it only because Lasso keeps it apart).
- **D9 No jQuery UI.** Drag with pointer events; every draggable (product, row/column, field) also has Move up / Move
  down buttons, and a field has "Move to previous/next row (column)" (keyboard, WCAG 2.1.1; F23 D7's pattern).
- **D10 Rich text without Quill:** WordPress's editor (TinyMCE) as the product page uses it (F1 analysis), the price
  editor's one-line settings for Price cells (one instance, on the cell being edited), the description editor's settings
  in "Description Editor". Second use of F1's editor settings: extract them then.
- **D11 Values are cleaned on save:** badge, button text and text values through `wp_kses_post` (keeps the site's
  `<span class="medal-icon">` icons), Price through F1's price allow-list, Description through `wp_kses_post`, URLs
  `esc_url_raw`, ratings clamped 1-5 to one decimal; printed as stored. Lasso stores most values unfiltered (R30).
- **D12 Delete returns to the list through `admin_url()`** (R41's root path breaks a sub-folder install); the server
  checks the location count at the moment of the click (as Lasso's server does).
- **D13 Locations read F21's index:** count and list of posts whose rows have `display_ref = table:<id>` (any status,
  as R11); the list's six a page and the remembered page are Lasso's.
- **D14 Help links left out** ("Learn", "Learn How to Create Comparison Tables": Lasso's help site; deviation 52, F23
  D9). The empty state keeps its heading and text.
- **D15 A product deleted for good leaves its tables with its values** (no orphans, R60), through the product delete
  path (17A); a product in the trash stays in the table but is not drawn (as a retired product, F1).
- **D16 Pages:** `admin.php?page=affibloom-tables[&search=…&paged=N][&create=1]` and
  `admin.php?page=affibloom-table&id=N` (hidden submenu, as the product page); the header gains **Tables** before
  Groups (Lasso's order). Search and paging redraw in place and keep the address (F23 D13).
- **D17 The reader's table keeps Lasso's markup and CSS** (R43-R53): `lasso-table-frontend.min.css` is carried as
  `assets/front/table.css` and enqueued with the box CSS (Phase 3's `BoxAssets`); class names, element order and the
  link attributes are Lasso's, `data-lasso-id` empty as in Lasso.
- **D18 The vertical "products only" notice** (R54) is not shown, as in Lasso.
- **D19 Wording:** messages that name Lasso say the same without it ("These are the products used in the table.");
  success stays silent (Saved), as in Lasso.

**Needs the user:**

- **N1 A field deleted on the Fields screen while a table uses it** (R61): Lasso leaves broken cells. Recommendation:
  the Fields screen (F26) names the tables that use it in its delete question and the delete removes the field from
  them (values kept as D7). Alternative: refuse the delete while a table uses it.
  → **Decided 2026-11-14:** D11: the user accepted the recommendation
- **N2 Lasso baselines for the pixel checks** (AC16-AC18): the site's three tables are all Horizontal and only in a
  draft. Recommendation: capture tables 2 and 3 from staging's preview of the draft 9492 (read only, logged in) at
  1280, 768 and 390 px; for Vertical, let Claude clone table 2 on staging and switch the clone to Vertical (a write on
  staging), capture it, then delete the clone. Without that, Vertical is checked against Lasso's markup only.
  → **Decided 2026-11-14:** F1: one approved staging session captures the baselines; the cloned Vertical table is deleted after.
- **N3 Tables are used by no published page.** They are built in full (the user's 2026-11-13 decision). Confirm that
  Tables stays the last area (dashboard-completion plan) and is not moved earlier for this draft.
  → **Decided 2026-11-14:** F2: the user accepted the recommendation: Tables stays the last area.

## 5. Scope that needs a home

- **Fields (F26):** the field definitions, "Create new" in the Add Field dialog (`POST /fields`, already built in 14C
  for the product page), Manage Fields, N1; the six table-only built-ins (R3: Description, Product name, Product
  Image, Primary Button, Secondary Button, Price) must exist in Affibloom's field library with Lasso's IDs (today's
  `FieldLibrary` has 1-3 and starts created fields at 100, while this site's custom field is 99995).
- **Shortcodes (F31):** 18S8 (table by shortcode) becomes T12's entry (F31's text to correct at gate 4); 18S13 (Copy
  Shortcode) stays F31's and lives in T3's header; 27E (`[lasso type="table"]` alias) reads D2.
- **Display engine (Phase 3 Task 18, F5):** the field renderers R48 shares with the single box (stars, pros/cons,
  lists, copy controls, price with Prime and discount) and the link attributes (F21's render-time attributes); T12
  calls them, adding the table-only ones.
- **Content link index (F21, Task 35):** `display_ref = table:<id>` for `[affibloom type="table" id]` and for migrated
  `[lasso type="table" id]` (mapped to Affibloom's id through D2) — D13 depends on it; the table's products as the
  location's products (R56).
- **Editor (F4):** "Choose a Table" and Edit Display, calling `GET /tables` (T1).
- **Groups (F23):** `GroupService::members()` for Add Group (T5).
- **Migration (Task 26, F10):** Lasso's four tables and `show_field_name` → `affibloom_table` posts with D2's map; D6's
  duplicates folded; table 3's gaps in order normalised.
- **Import (F33):** AAWP tables (R57).
- **Filters panel (F19):** the Comparison table filter's place in the panel and saved views (T16).

## 6. Edge cases

1. A table whose products are all trashed or retired draws nothing (R43), its builder still lists them.
2. Two windows on one table: D4's revision refuses the second write.
3. A badge or button text with HTML (this site's icons) is kept and printed (D11); a `<script>` is removed.
4. A table with one product and one field still draws (table 4); a table with products but no fields draws empty
   cells, as Lasso.
5. Add Row twice quickly: one empty row (R25's lock; the queue of D4).
6. A field moved onto a row that has it: refused, the builder redrawn as stored.
7. A product name cell emptied: the reader shows an empty `h3`, as Lasso.
8. A Secondary Button without URL: nothing in that cell for readers, the builder's warning for editors.
9. A table referenced by content and then deleted on another site copy: the shortcode draws nothing.
10. Search with only stripped characters ("%%%"): the full list.
