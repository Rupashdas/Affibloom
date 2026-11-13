# F1 Product library: requirements

Status: gate 1 approved with Task 6 (2026-11-12). **The product page (Task 14) re-run under the full-scope rule on
2026-11-13** (progress deviations 24 and 57): sections 2-8 below, written from Lasso 369's source and this copy's data.
**Gate 4 given by the user 2026-11-13** for Tasks 14A-14E, with D1 answered: Lasso's two cards in Aurora's shell.
**Saving the page (Task 15) re-run the same way on 2026-11-13** (progress deviation 70): section 9, written from
Lasso's save path. Waiting for gate 4 on Tasks 15A-15D.

## 1. User stories and acceptance criteria

- As an editor, I add, edit, search and delete affiliate products in one place, so every box takes its links and
  text from one record.

- **AC1** A link added in "Add A New Link" becomes a product and its page opens.
- **AC2** Fields saved on the Details tab persist and show a toast: Save Changes shows "Updating Your Site", then
  "Product saved." (U6); after a reload the name, every button (URL, text, toggles, order), theme, image, badge,
  price and its toggle, description, disclosure and the switches are as saved. (15A)
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
- **AC12** On an Amazon product's page, when the site has no tracking ID, the link's tag is offered as the site's
  tracking ID (Yes saves it by the Amazon tab's rule and shows "Tracking ID Saved!"); a link without a tag offers
  "Set Amazon Tracking ID" instead; other products show nothing. (14D, B10)
- **AC13** An empty Name is refused with "Name and Primary Destination URL are required." under the field and nothing
  is saved; a button URL is never refused: `B0C7654321`-style Amazon links get the site's tag, a link without a scheme
  gets `https://`. A description or price nobody touched is stored byte for byte as it was. (15A, S2-S6)
- **AC14** A new Primary URL is stored by the add flow's rules: an Amazon link with another ASIN is retagged, its
  type, ASIN and country follow, and a placeholder name ("Amazon", empty) becomes the new link's starting name; a
  new link with the same ASIN keeps the editor's name and image. (15B, S7-S8)
- **AC15** A Primary URL another product already uses opens "This Product Already Exists" with both links;
  Cancel saves nothing; Confirm retires this product, opens the other one's page and shows "Success! Locations now
  show this link."; a box of the retired product shows the other one (Phase 3). (15C, S10)
- **AC16** With unsaved edits, a click on an admin link asks "Unsaved Changes": "Leave Without Saving" leaves,
  "Save Changes" saves and then leaves, × stays; reloading or closing the tab gets the browser's own question;
  after a save nothing asks. (15D, B8)

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
  shell — **the user's answer at gate 4, 2026-11-13** (the arrangement they chose for the Dashboard).
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
- ~~**Not built, it edits post content** (deviation 52): Lasso's "repoint" offer on save and its force delete, which
  rewrite posts. Tasks 15 and 17 take that up in their own sweeps.~~ **Corrected by the Task 15 sweep (section 9):**
  the repoint offer does not touch post content (`Lasso_Repoint_Merge::merge`: "Host post_content and shortcodes are
  not rewritten"), so it is built (15C). What does rewrite posts on save is the link scan (S9). Force delete: Task 17.

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

## 9. Saving the Details tab (Task 15 sweep, 2026-11-13)

**Evidence, all read:** `pages/url-details/class-ajax.php` (`save_lasso_url`, `lasso_confirm_repoint_merge`);
`Lasso_Affiliate_Link::save_lasso_url()` (`classes/class-lasso-affiliate-link.php` 1085-1897) with
`check_error_and_response_ajax()`, `apply_product_title_and_thumbnail_from_product_row()`,
`is_placeholder_link_title()`, `shares_same_amazon_asin()`, the `save_scalar_*` cleaners,
`sanitize_price_html_for_storage()`; `Lasso_Amazon_Api::get_amazon_product_url()` (1468-1585) and
`Lasso_DB::get_lasso_id_by_product_id_and_type()`; `classes/class-lasso-repoint-merge.php` `merge()`;
`Lasso_Process_Scan_Link` and `Lasso_Cron::scan_link_in_post()`; the page script (`url-details.php` 1290-1990:
`get_payload_to_save_url`, the save handler, the leave guard, the baseline); modals `url-save.php`,
`unsaved-changes.php`; notifications `save-success.php`, `save-fail.php`; `lasso-helper.js` 661-715.

### What a save does, in Lasso's order

| # | Rule | Lasso | Source |
|---|---|---|---|
| S1 | One AJAX post of the whole form (`settings[…]`, `thumbnail_id`, `permalink`); the capability is `edit_post` on that record | `lasso_save_lasso_url` | class-ajax.php 47-63 |
| S2 | **The only refusal:** an empty Name or Primary URL → "Name and Primary Destination URL are required." Nothing else is ever refused: no length limits, no URL check | `wp_send_json_error` | 1133-1140 |
| S3 | Name, Disclosure, theme: `sanitize_text_field`. Button texts and Badge: `<span class>` only, `<script>`/`<style>` removed (icon markup). Button URL: `esc_url_raw`. Description: `wp_kses_post`, emoji encoded, Lasso's empty `<p><br></p>` → ''. Price: `<script>`/`<style>` removed, `rgb()`/`rgba()` colours → `#hex`, then `wp_kses` with Lasso's price tags (`p b strong i em u s span br` with `style`/`class`) | `save_scalar_*`, `sanitize_price_html_for_storage` | 3020, 3627-3723 |
| S4 | **The secondary URL** (F29 D5): trimmed; a geni.us link becomes its destination unless "Keep geni.us formatting" is on; `https://` added; then `get_amazon_product_url( $url, true, false )`: an Amazon link always gets the site's tag rule (whitelist, shorten); short `amzn.to` links and protected links are left as typed | `second_btn_url` | 1152-1160, 1468-1585 |
| S5 | `show_description` is stored as "the description is not empty" (quick edit, which sends none, keeps both) | `show_description` | 1367-1381 |
| S6 | An image: an attachment chosen in the media library wins (`_thumbnail_id`, and its URL becomes the image link); otherwise the typed link is stored and any attachment is detached | `thumbnail_id` | 1788-1796 |
| S7 | **The Primary URL:** trimmed; geni.us as S4; `https://`; an Amazon link through `get_amazon_product_url( $url, true, true )` (D12); its redirects followed (`get_redirect_final_target`); `smile.amazon` → `amazon`; type, ASIN and country recomputed from the final link | `affiliate_url` | 1142-1240, 1341-1346, 1478-1582 |
| S8 | **Same ASIN and country as the record** → updated in place: an `amzn.to` link is kept as typed, name and image untouched. **Another ASIN** (or a link that turns Amazon): the name becomes the Amazon product's when "Keep title when URL replaced" is off or the name is a placeholder (`''`, "Amazon", "Add a Link Title", "Lasso", `add-a-link-title`); the image always becomes the product's (Lasso's default picture when it has none) | `shares_same_amazon_asin`, `apply_product_title_and_thumbnail_from_product_row` | 1242-1258, 1563-1570, 1944-1955, 2197-2210, 2493-2530 |
| S9 | **After the save, a link scan rewrites posts:** when New Window, NoFollow (either button), Sponsored, Link Cloaking, the Primary URL or the slug changed, `Lasso_Process_Scan_Link` re-parses every post that links to the record and **writes its `post_content`** (`update_post_content`) | `scan_post_page` | 1798-1812; class-lasso-cron.php 4733-4790 |
| S10 | **The Primary URL matches another published record** (the add flow's duplicate rules) → nothing is saved; the dialog turns into "This Product Already Exists": "You already use that link on your site. **Do you want to change all Locations to that link?**", Current Link → Existing Link (name, URL, View in a new tab), Cancel / Confirm. Confirm (`lasso_confirm_repoint_merge`) re-checks (both records Lasso's, this one still active, the other published, the URL still resolving to it), moves the link locations to the other record, stores `lasso_repointed_to_lasso_id` on this one and soft-deletes it; **posts are not rewritten** ("front-end resolves via meta"); then the other record's page opens with "Success! Locations now show this link." | `repoint_offer` | 1261-1303; class-lasso-repoint-merge.php 115-205; url-details.php 1354-1405, 2601-2608 |
| S11 | The matching record is **not published** (soft-deleted): Lasso publishes it again and answers "This link is already exists. Please try again with a different URL." with that record's data, which the page then writes into this record's fields | | 1261-1290; url-details.php 1856-1880 |
| S12 | A save re-checks the link's status when the URL changed or the record was last saved over an hour ago, and files a link issue; a failure answers "Saved but could not resolve the URL." | `check_issues` | 1305-1335, 1830-1842 |
| S13 | Also on save: `lasso_final_url`, the domain and the opportunity flag in `url_details`; the history record (`Link_Updates`); the slug and its conflict warning ("Permalink … is being used by …"); Groups and their order; link locations' URL; the default image from Lasso's affiliate-program logos; "extend product" data for other marketplaces | | 1337-1351, 1583-1694, 1699-1828 |
| S14 | **The page:** "Updating Your Site" with a moving bar and "Just saving your URL changes" ("Saving your changes." when only New Window or NoFollow changed; the other texts belong to keywords, left out by deviation 52); it closes 1 s after the answer. Then "**Yee haw!** Your link saved." or "**Try again.** For some reason your link didn't save." (or the server's message); a warning shows as an orange bar | | url-save.php; url-details.php 1311-1341, 1810-1990; save-success.php, save-fail.php |
| S15 | **After a good save** the page writes back what was stored: heading, Name, Primary URL, Permalink, image, the public link, the second button's URL, the price; the baseline for unsaved changes is taken again | | url-details.php 1880-1944 |
| S16 | **Unsaved changes** (B8): a snapshot of every field, switch and the description on load, after a preview refresh and after a save; a click on a link (not `#…`, `javascript:`, `_blank`, or inside the save, delete and unsaved dialogs) with changes opens "Unsaved Changes": "Leave Without Saving" goes, "Save Changes" saves and then goes, × stays; otherwise `beforeunload` asks, except while saving | | url-details.php 1420-1460, 1640-1720; unsaved-changes.php |

### Decisions (Claude, from Lasso and the records; none drops anything Lasso does or reverses a user decision)

- **D10 Success wording is "Product saved."** (U6, approved 2026-11-12). The failure keeps Lasso's words with
  Affibloom's noun: "Try again. For some reason your product didn't save."
- **D11 Refusals show under their field** (Aurora's field error) with Lasso's one sentence (S2), and the page also
  shows the failure toast. Lasso shows a red bar; the words and the rule are Lasso's.
- **D12 The site's tag rule applies on every save.** Lasso tags a *new* ASIN pasted on the page only on the second
  save: its first pass returns the link as typed when no record has that ASIN yet
  (`get_lasso_id_by_product_id_and_type`); the second save stores the tagged form. Affibloom stores that
  second-save result at once, which is also the overview's rule ("every Amazon product link carries exactly one tag,
  the site's").
- **D13 Nothing Affibloom saves rewrites posts (S9)**, the user's rule (deviation 52). What the scan achieves, posts'
  links following the product's attributes, comes from rendering instead: boxes are drawn from the product (Phase 3),
  and text links in posts belong to the content link index (area 1, F21), which takes "a product's New Window,
  NoFollow and Sponsored reach its text links at render time" into its gates.
- **D14 The repoint offer is built (S10, 15C).** It edits no post. Affibloom stores `_affibloom_repointed_to` on the
  retired product and moves it to the trash (Task 17's delete is the same move); everything that draws a product by
  its ID follows the pointer: the box (Task 18), `[lasso]` (Task 27), Locations (Tasks 35-36, which also move the
  index rows as Lasso moves its location rows).
- **D15 A matching product in the trash (S11) belongs to Task 17**, which creates trashed products; until then none
  exists. Lasso's writing of the other record's data into this record's fields is not copied (a later save would store
  another product's values here); Task 17's sweep takes the restore-and-refuse rule for both add and save.
- **D16 Owned elsewhere (S12, S13):** the status re-check → link health (F11, Task 32 adds it to the save); the
  history record → the history area; the slug, its warning and Link Cloaking → area 7; Groups → the Groups area;
  the opportunity flag → area 3; link locations → area 2. Left out by the cloud rule (deviation 52): the
  affiliate-program logo and "extend product" data. Lasso's own Amazon product table is C1's.
- **D17 Without Amazon data (C1 dormant), S8's "the product's name and image"** are what Affibloom has on adding:
  the starting name (decision 11) and no image (the box's default picture). With C1 they come from the API.
- **D18 After a save that changes the type** (Amazon ↔ other), the page reloads with the toast, because the
  Amazon-only rows (Permalink, image label, price, cloaking, Prime) differ; Lasso swaps them on leaving the field,
  which is the preview's job (14E).
- **D19 What is sent:** every field except Permalink, Groups, Detect Opportunities and Link Cloaking (placeholders,
  D4) and the custom fields (D3, their own routes). The description and the price are sent only when edited (14B:
  TinyMCE normalises some stored markup on opening); an Amazon product's read-only price never is.
- **D20 `show_description` is not stored:** it is "the description is not empty" on every page save (S5), so the box
  reads that. Task 26 maps a Lasso record whose `show_description` is 0 to an empty description.
