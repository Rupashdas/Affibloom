# F29 Any number of buttons: requirements

Status: gates 1-2 written 2026-11-13 at the user's request (*"We should have option to add multiple buttons. Currently
the lasso has option to add only two buttons."*). The user chose the count, the look and the analytics event the same
day (section 5). **Gate 4 given by the user 2026-11-13** ("Approve, start 14H"). Register item **U12** (an addition: Lasso has two).

## 1. User story and acceptance criteria

- As an editor, I give a product as many buttons as it needs (a men's, a women's and a kids' shoe; or three shops), so
  I no longer box the same product twice to show a third link.

- **AC1** The product page shows every button in its order as a group "Button <n>": the default one (Primary
  Destination URL, "Main link") where it was put, each added one with Additional Destination URL; each has Button Text
  (with the icon picker), New Window / Tab and NoFollow / NoIndex.
- **AC2** A product starts with one button. "Add Another Button" adds an empty block at the end; every block moves up
  and down, the default one too; an added block can be removed. Task 15 saves the order. (14H shows and edits.)
- **AC3** In a box, the buttons follow that order; the first is the primary button (`lasso-button-1`, primary colour),
  the rest look like the secondary; each shows only with a URL and when it does not link to the page itself. (Phase 3.)
- **AC4** A click on the first button sends `Lasso Display Primary Click`, on any other `Lasso Display Secondary
  Click`, labelled with its text; each counts as a click on the product. (C3, F12.)
- **AC5** A product whose default button is first and that has at most one added button renders exactly as Lasso's
  box does today (ADR-6 baselines).

## 2. Evidence

- **Lasso has exactly two buttons.** Every box template prints `a.lasso-button-1` and, when `secondary_url` is set,
  `a.lasso-button-2` (`admin/views/displays/single.php`, `layout-2-box.php`, `layout-3-box.php`, `layout-6-box.php`,
  `list-box.php`; `button.php` prints one of the two by `button_type`). The second is hidden when it links to the
  current page (`single.php` 226-229) and loses its link when its text is empty (`Lasso_URL::render_attributes_second`).
  Its attributes: `data-lasso-button="2"`, `target2`, `rel2`.
- **Shortcode overrides** `primary_url`, `primary_text`, `secondary_url`, `secondary_text`, `button_type`
  (`class-lasso-shortcode.php` 270-289). In content here: `primary_url` on 12 boxes, `secondary_url` on 1.
- **Elsewhere:** quick edit (`url-quick-detail.php`) has the secondary's URL and text; tables map "Primary Button" and
  "Secondary Button" (`class-table-mapping.php`); Display has two colours and two default texts per type, and the
  "Primary button" / "Secondary button" element toggles (F16).
- **Analytics:** `Lasso Display Primary Click` and `Lasso Display Secondary Click` (C3 AC1).
- **This site:** 60 of 254 products have a second button: men's / women's versions of a shoe (25 "Buy Now Men's
  Shoe", 21 "Buy Now Women's Shoe", 5 more), another shop (Mizuno, Walmart, "Check Price On All Volleyball"). Hosts:
  Amazon 51, ShareASale 4, Mizuno (Impact) 4, Walmart 1. Three posts box the same product twice.

## 3. Behaviours

- **B1 The default button is the product's own link** (Primary Destination URL: cloaking, link health, Amazon
  handling, search, the title and image link) wherever its button sits. The **added buttons** are an ordered list,
  and the default button has a place among them (`_affibloom_primary_position`, D6). Lasso's secondary is the first
  added button.
- **B2 An additional button** (3 and up) has a URL, a text (empty means the display type's secondary text; icon
  picker, F28), New Window / Tab and NoFollow / NoIndex, starting on as Lasso's secondary toggles do. Labels:
  "Additional Destination URL" (help: "Another URL you want people to go to when they click an extra button in
  displays"), "Button Text", the two toggles.
- **B3 Product page:** one block by default, the default button's, marked "Main link". "Add Another Button" under
  the last block adds an empty one; every block has Move up and Move down (disabled at the ends), an added one also
  Remove (asks first when its URL is filled). Without JavaScript one empty added block follows the list, so a button
  can still be added one save at a time (reordering needs the script).
- **B4 In a box** (Phase 3): the buttons in their order; the first is `lasso-button-1` with the type's primary colour
  and text default, every other `lasso-button-2` with the secondary's (the site's CSS styles them, D2, D7), each
  `data-lasso-button="<n>"` with its own target and rel; hidden without a URL or when linking to the current page; the
  "Primary button" element toggle shows or hides the first, "Secondary button" the rest. The "Button" display type's
  `button_type="primary"` means the first, `"secondary"` the second.
- **B5 Clicks:** each counts for the product (F12); GA gets `Lasso Display Primary Click` for the first button and
  `Lasso Display Secondary Click` for the others, with the button's text (D3, D7).
- **B6 Unchanged from Lasso:** quick edit keeps the default button and the first added one (Lasso's two); the "Button" display type and `button_type` choose
  between primary and secondary; the tables' "Secondary Button" column is the secondary; `secondary_url` /
  `secondary_text` shortcode overrides apply to button 2; link health checks the primary URL, as Lasso's.
- **B7 Search** (Dashboard, F19 D4): every button's link is searched, as the secondary's is.
- **B8 Migration** (Task 26): Lasso's `second_btn_*` become button 2; there is nothing else to migrate.

## 4. Out of scope

- A different colour or default text for buttons 3 and up (D2).
- More than one primary link per product: cloaking, Amazon tags and link health stay on the Primary Destination URL.

## 5. Decisions

- **D1 (the user, 2026-11-13): as many buttons as needed**, with add, remove and reorder.
- **D2 (the user): buttons 3 and up look like the secondary** — its colour, its default text, its element toggle.
- **D3 (the user): their clicks send `Lasso Display Secondary Click`**, labelled with their own text, so existing GA
  reports keep counting them.
- **D4 (Claude): one ordered list for buttons 2 and up** (`Product::$buttons`), replacing 14A's four `url2` fields,
  so the secondary and the extra buttons share one rule, one form row and one renderer. No Affibloom product exists on
  production yet; the Local seed tool and the migration write the new form.
- **D6 (the user, 2026-11-13, during 14H): one button by default, and the default one moves like the others.**
  *"By default there is two buttons. Instead keep one button. but that default button also be sortable using move
  up/move down."* Lasso's always-shown secondary block is gone; the secondary is added like any other button.
- **D7 (the user, 2026-11-13): the first button in the list is the box's primary**, whichever it is (primary colour,
  `Primary Click`); the default button's link stays the product's main link wherever it sits.
- **D5 (Claude): Amazon handling for button links** follows whatever Task 15's sweep finds Lasso doing for the
  secondary URL; every additional button follows the same rule.
