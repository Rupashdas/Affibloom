# F1 Product library: requirements

Status: gate 1 approved with Task 6 (2026-11-12). **The product page (Task 14) re-run under the full-scope rule on
2026-11-13** (progress deviations 24 and 57): sections 2-8 below, written from Lasso 369's source and this copy's data.
Waiting for the user's gate 4 on Tasks 14A-14E.

## 1. User stories and acceptance criteria

- As an editor, I add, edit, search and delete affiliate products in one place, so every box takes its links and
  text from one record.

- **AC1** A link added in "Add A New Link" becomes a product and its page opens.
- **AC2** Fields saved on the Details tab persist and show a toast. (Task 15)
- **AC3** Dashboard search finds a product by name.
- **AC4** A product not in use can be deleted. (Task 17)
- **AC5** A product's page shows every Details field as stored, in Lasso's order and with Lasso's labels; an empty
  button text or disclosure shows the site's default as its placeholder. (14A, 14B)
- **AC6** Adding a non-Amazon link that already exists opens the existing product's page with "This product already
  exists. Please update the Primary URL or add a new link." (14A)
- **AC7** An Amazon product's page has no Permalink row and no Link Cloaking toggle, labels its image field "Amazon
  Image", and shows its price read-only with "Amazon prices updated via Amazon API settings". (14A, 14B)
- **AC8** An address with no such product, or another post type's ID, lands on the Dashboard. (14A)
- **AC9** The Dashboard row's name and image open the product's page. (14A)
- **AC10** "Add Custom Field" → "Add from library" → Primary Rating adds the rating row; a value typed there and the
  row's toggles are still there after a reload; removing the row asks first. (14C)
- **AC11** The Share kit copies the destination URL and the disclosure, and shows "Copied!". (14D)

`tests/E2E/acceptance/F1.spec.ts` holds AC1-AC4; each task adds its own before it is built.

## 2. Evidence

- `admin/views/dashboard/url-details.php` (2,673 lines, all read): the form (171-527), the script (532-2660).
- `admin/views/dashboard/header.php` (328 lines): title, public link, Share kit, the four tabs.
- `Lasso_Affiliate_Link::get_lasso_url()` (`classes/class-lasso-affiliate-link.php` 105-520): every field's source and
  default.
- `pages/url-details/class-ajax.php` `upload_thumbnail()` (100-230); `admin/views/rows/field-details-row.php`;
  modals `field-create`, `url-field-delete`, `unsaved-changes`, `url-save`; `notifications/amazon-url-detected.php`;
  `models/class-field-mapping.php`.
- Lasso 370 on production: `tests/fixtures/baselines/admin/product-details-{1,2,3}.jpg`.
- This copy's data, queried 2026-11-13 (263 published records; the seeded Affibloom library of 254, `d401a43`).

## 3. The page

| Rule | Lasso | Source |
|---|---|---|
| Address | `edit.php?post_type=lasso-urls&page=url-details&post_id=N` | header.php 253 |
| Another post type's ID | redirect to the Dashboard | url-details.php 71-74 |
| No record | refresh to the add page | header.php 128-132 |
| Heading | the name; "Enter a URL Name" when empty | header.php 187-191 |
| Line under it | the public link: the cloaked URL when cloaking is on and the link is not Amazon, else the destination | header.php 209-218; `Lasso_Affiliate_Link` 460-466 |
| Tabs | Details, Locations `<n>`, Opportunities `<n>`, Variants `<n>`, each a page of its own | header.php 259-300 |
| Duplicate warning | `?is_duplicate=true` → "This product already exists. Please update the Primary URL or add a new link." | url-details.php 2597-2600 |
| Footer | "Delete This Link" left; "Learn About This Page" and "Save Changes" right | url-details.php 515-527 |

## 4. The Details form, in Lasso's order

**Left card**

| Field | Label and help text | Stored as (Lasso) | Live data |
|---|---|---|---|
| Name | `Name` — "This title will only be shown in displays"; placeholder "URL Name Goes Here" | post title | 263 |
| Permalink | `Permalink` — "This slug will be used to cloak the the original target URL"; hidden for Amazon; typing normalises the slug (`[\W_]+` → `-`, trimmed) and rewrites the line under the heading | post slug | 30 non-Amazon |
| Primary URL | `Primary Destination URL` — "The actual URL you want people to go to when they click a link"; placeholder `https://www.example.com/affiliate-id` | `lasso_url_details.redirect_url` | 254 |
| Button Text | `Button Text` — "This text will appear in the primary button"; placeholder = the site's primary button text | `buy_btn_text` | 230 set, 167 the site's `<span class="amazon-icon"></span> Buy Now` |
| toggles | `New Window / Tab` — "When enabled, users who click this link will have it loaded in a new tab."; `NoFollow / NoIndex` — "When enabled, this link will be set to nofollow. This indicates to Google that it's an affiliate link." | `open_new_tab`, `enable_nofollow` | |
| Secondary URL | `Secondary Destination URL` — "A secondary URL you want people to go to when they click an optional second button in displays"; placeholder `https://www.example.com/affiliate-id2` | `second_btn_url` | 60 |
| Button Text | "This text will appear in the optional secondary button"; placeholder = the site's secondary button text | `second_btn_text` | 183 |
| toggles | the same two | `open_new_tab2`, `enable_nofollow2` | 254 on, 254 on |
| Groups | `Groups` — "Group this URL for better organization and to use in group displays"; multi-select, placeholder "Select a group", typing a new name creates a group (select2 `tags: true`) | `lasso-cat` terms | 1 term, 0 assignments |
| Custom fields | the attached fields' rows, then "Add Custom Field" and "Learn" | `lasso_field_mapping` | Primary Rating on 5 records (3.5-4.5, all visible) |

**Right card**

| Field | Label and help text | Stored as | Live data |
|---|---|---|---|
| Preview | the real box, rendered by the shortcode with `for_url_details`; typing updates it; the image in it opens the media library | — | |
| Display Theme | `Display Theme` — "Choose the default display theme for this link."; `Default (<the site's Single theme>)` then Cactus … Splash | `custom_theme` | 0 set |
| Image | `Amazon Image` for Amazon, else `Product Image`; Amazon: read-only + `Refresh` ("Click the button below to grab an updated image from Amazon."); others: editable + `Apply` ("Paste an image URL and click Apply, or click the preview image to choose from your Media Library.") | `lasso_custom_thumbnail`, `_thumbnail_id` | 240 image links, 18 attachments |
| Badge Text | `Badge Text` — "This text will appear as a badge in displays for this URL."; placeholder "Our Pick" | `badge_text` | 198 |
| Price | `Price` — "This price can be any text or amount that'll only be shown in displays. Prices automatically update every 24 hours with integrations like Amazon."; show toggle ("Turn this on to show the price in this display."); non-Amazon: a one-line rich editor (bold, italic, underline, strike, text colour), placeholder `$99.99`; Amazon: read-only, "Amazon prices updated via Amazon API settings" | `price`, `show_price` | 186 prices, show_price 0 on 216 (site default off) |
| Description | `Description` — "Share more details about the product or service you're linking to. A short paragraph of 1-4 sentences typically performs best."; rich text: bold, italic, underline, strike, link, bulleted list, text colour, highlight, clear; placeholder "Enter a description" | `affiliate_desc` | 198 |
| Disclosure | `Disclosure` — "This disclosure will only be shown in displays"; placeholder = the site's text; a value equal to the site's text is shown empty | `disclosure_text` | 74 (67 equal to the site's) |
| Sponsored | "When enabled, this link will be set to sponsored." | `enable_sponsored` | 5 on |
| Show Disclosure | "Turn this on to show the disclosure in Displays for this Lasso Link." | `show_disclosure` | 254 on |
| Detect Opportunities | "When disabled, Link Opportunities will not show for this Lasso Link." | `lasso_url_details.is_opportunity` | 254 on, 2 off |
| Link Cloaking | "The destination of this URL will be redirected from a pretty url on your domain. All non-Amazon Affiliate links should have this enabled."; hidden for Amazon | `link_cloaking` | 236 on |
| Commission Upgrades | Lasso's Brand Deals upgrades | `boosting_enabled` | 110 on |

## 5. Behaviours

- **B1 Placeholders show the site's defaults** (button texts, disclosure); an empty value means "use the default".
- **B2 Typing updates the preview** (name → heading and box title; button texts; badge; secondary URL adds or removes
  the second button; price and its toggle; disclosure and Show Disclosure; description).
- **B3 Changing the theme re-renders the preview.** Lasso does this by **saving the whole form** first
  (`refresh_display` posts `get_payload_to_save_url()`), so a theme change silently saves every unsaved edit.
- **B4 Changing the Primary URL** (on leaving the field, when it is a valid, different link) shows "Fetching new
  data" and does the same save-then-render.
- **B5 Image:** clicking the preview opens the media library ("Select an Image", images only, one, "Select Image");
  Apply checks the link ("Image URL is required.", "Invalid image url.", no `data:`) and **writes it at once**, before
  Save; Refresh asks Lasso's cloud or the Amazon API for the image and, for a placeholder name ("Amazon", empty …),
  the product's name.
- **B6 Custom fields save as they are edited**, not with Save Changes: a value on leaving its box, a toggle on change,
  the order on drop (`lasso_save_field_positions`), adding and removing at once. The rating box clamps to 1-5 with one
  decimal (empty → 3.5); only Primary Rating has the second toggle, "Show field name in your display."; Primary Rating
  keeps its position ("Position in display is fixed.").
- **B7 Add Custom Field** opens "Add Field": tab "Create new" (Field Title, Field Type, Description, "Create Field")
  and tab "Add from library" (search "Find a field by searching here", ten per page); "Manage Fields" opens the Fields
  screen. Removing asks first (`url-field-delete`).
- **B8 Unsaved changes:** leaving with changes asks "Unsaved Changes" — "You have unsaved changes that will be lost if
  you leave this page. What would you like to do?" — "Leave Without Saving" / "Save Changes", plus the browser's own
  prompt. (Task 15)
- **B9 Share kit** ("Copy snippets for posts, email, and social."): Copy shareable URL (the public link; disabled when
  there is none), Copy destination URL (the field as typed), Copy disclosure (the field, else its placeholder), Copy
  shortcode (`[lasso rel="<slug>" id="<id>"]`), Copy block (the Lasso block's markup); each flashes "Copied!" for
  1.5 s.
- **B10 Notices above the page:** broken link and out of stock (dismissable), Lasso's bulk Amazon sync, and — for an
  Amazon product when the site has no tracking ID — "… doesn't have your Amazon Tracking ID saved. Would you like to
  set **<tag>** as your primary tracking ID?" with Yes / No (the tag read from the link), or a "Set Amazon Tracking
  ID" button when the link has none.

## 6. Decisions

Settled from Lasso and the records (none drops anything Lasso does or reverses a user decision):

- **D1 Layout.** Lasso's two cards (left: the link; right: preview and display fields) and its footer, in Aurora's
  shell — **asked at gate 4** (deviation 25 leaves the other screens' layout open; the user chose this arrangement for
  the Dashboard).
- **D2 A theme change, a new Primary URL and Apply do not save.** They update the preview and the fields; everything
  is stored by Save Changes, guarded by B8. Lasso's save-on-theme-change stores edits the editor has not asked to
  store, and its immediate Apply contradicts its own media-library pick, which waits for Save. Nothing Lasso stores is
  lost.
- **D3 Custom fields keep Lasso's save-as-you-edit (B6)**: the rows talk to their own routes, so they also work
  before Task 15.
- **D4 Placeholders, never borrowed data** (deviation 53): Groups (the Groups area), Permalink and Link Cloaking
  (link cloaking, area 7 of the completion plan), Detect Opportunities and the Opportunities tab (area 3), the
  Locations tab (area 2), the Variants tab (a new area, section 7), Copy shortcode (Task 18S) and Copy block (Phase 4)
  show "Coming soon"; Refresh and the Amazon price say "Needs the Amazon API" (C1).
- **D5 The public link** is the destination until link cloaking exists (Lasso's own rule when cloaking is off), so
  neither the heading's link nor "Copy shareable URL" offers an address that would not answer.
- **D6 Amazon Image stays editable with Apply** (the user's decision 11, 2026-11-12); Refresh waits for C1.
- **D7 Show Prime Logo stays** on Amazon products (interface spec: without Amazon data the editor sets it).
- **D8 Primary Rating takes Lasso's range, 1 to 5 with one decimal**, not the old plan's 0-5; the interface spec's
  `invalid_rating` message follows.
- **D9 The image field's label** follows Lasso: "Amazon Image" for Amazon products, "Product Image" for the others.

Settled by the user's earlier rules:

- **Left out, Lasso's cloud** (deviation 52, bucket C): Commission Upgrades (Brand Deals), Refresh through Lasso's
  cloud, the bulk Amazon sync notice, opportunity "flips", "Learn About This Page" and "Learn" (Lasso's help site),
  the Marketplace label.
- **Not built, it edits post content** (deviation 52): Lasso's "repoint" offer on save and its force delete, which
  rewrite posts. Tasks 15 and 17 take that up in their own sweeps.

## 7. Scope that needs a home

- **Custom fields beyond the product page:** Lasso's Fields screen (rename, delete, reorder definitions; the
  `DETAILS` field on this copy) and the box rendering Pros, Cons and other fields. 14C builds the definitions and the
  product page's rows; **a Fields area** is added to `plans/dashboard-completion-plan.md` for the screen, and Phase 3's
  box tasks render the fields.
- **URL variants** (`lasso_url_variants`, 0 rows here): the Variants tab, its add/search toolbar and the Dashboard
  search arm (F19 D4). Local, no credentials: **a Variants area** is added to the completion plan.
- **The live preview** (B2-B4) renders the real box: **Task 14E runs in Phase 3 after Task 18x**, as 10D does.

## 8. Edge cases

1. A draft or trashed product's ID: Lasso shows an empty form for anything not published; Affibloom sends it to the
   Dashboard, like a missing one (it cannot be listed, so no link leads there).
2. A product with no image: the preview shows Lasso's no-thumbnail picture; the image field is empty.
3. A stored disclosure equal to the site's text shows empty (header.php 210-212), so a later change of the site's
   text reaches it once saved.
4. HTML in button and badge texts (`<span class="amazon-icon"></span>`) shows as text in the fields, as Lasso shows
   it, and is printed escaped.
5. A description written by Lasso's Quill editor (`<p><span style="color: rgb(0,0,0); …">`) opens unchanged.
6. An Amazon search page (`_affibloom_search_key`) is Amazon: no Permalink, no cloaking; its image label is "Amazon
   Image".
