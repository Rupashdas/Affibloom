# F4 Displays in the editor (block, display modal, Classic, Elementor, integrations): requirements

Status: gates 1-2 rewritten 2026-11-14 from a sweep of Lasso 369's editor subsystem (the Gutenberg block, the display
modals, the Classic editor, Elementor, Rank Math, AMP and the MCP routes). Replaces the thin Task 6 version
(approved 2026-11-12, `docs/decisions/task-6-decisions.md`); AC1-AC2 keep their numbers and meaning. Gate 4 for
Tasks 23A-23G, 24A-24E, ED1-ED6, MCP1: waiting. Analysis: `analysis.md`. Tasks: the phase files in `affibloom-docs/plans/` (task index in `00-overview.md`).

## 1. User stories and acceptance criteria

- As an editor, I place any of Lasso's displays (a product, a button, an image, a group as a grid, list or gallery,
  a comparison table) into a post from the block editor, the Classic editor or Elementor, see it as readers will,
  and change it per placement without touching the product.

- **AC1** Insert block → search → Add shows the box in the editor. (23A-23C; corrected 2026-11-14: the block first
  shows "Choose a Display Type" and **Single** leads to the search, unless the user keeps U1, N1. The meaning,
  insert-search-Add-see, is unchanged.)
- **AC2** The published page shows the box. (23C)
- **AC3** A new block shows the empty panel ("Choose a Display Type" prompt text, **Add a Display**); while the modal
  loads the button reads "Loading..." and is disabled; if it fails, "Retry Add a Display" retries. (23A, R5-R7)
- **AC4** **Add a Display** opens "Choose a Display Type" with Single, Grid, List, Table, Button, Image, Gallery (and
  AI Text per N4); closing the modal (×, Escape, backdrop) and opening it again starts at the chooser. (23A, R9-R11)
- **AC5** Single, Button and Image open "Choose a Link": five products a page, newest changed first, each with its
  picture, name, link and **Add**; "Search URLs" searches on Enter (spaces as wildcards); page links page; **Add**
  closes the modal and the block shows the display (`type` button/image as chosen). (23B-23C, R12-R16)
- **AC6** Grid, List and Gallery open "Choose a Group": five groups a page with "N links"; "Search groups" on Enter;
  **Add** inserts that group as the chosen type. (23D, R17-R18)
- **AC7** **Create Group** opens "Add a New Group" (Name, Description, Save Changes); an empty name says "Name is
  required."; a saved group opens "Add to Group" for it. A group's name in the list opens "Add to Group" too:
  "Search URLs", **Select** / "Added", **Add Group** inserts the group display, **Done** closes and the group list
  shows the new count. (23E, R19-R23)
- **AC8** Table opens "Choose a Table": five tables a page, "Search tables", **Create Table** (the Tables screen in a
  new tab), **Add** inserts the table. (23F, R24)
- **AC9** **Create a Link** (in "Choose a Link" and in "Add to Group") opens "Add A New Link" (F18's editor
  variant); a created product opens its quick detail; **Add** saves it and inserts it as the chosen Single, Button or
  Image display, or, when it came from "Add to Group", adds it to that group and returns to "Add to Group". (23G,
  R25-R30)
- **AC10** The block's saved markup holds the display type and its target and every override set, and it renders
  the same HTML in the editor preview and on the published page. (23C, R31-R34, D3)
- **AC11** The block's settings sidebar shows **Customize Display** with the fields of the block's display type,
  labels and help texts as Lasso's; a changed field updates the preview on leaving the field or on Enter; a cleared
  field removes the override; toggles store only their non-default value. (24A, R35-R42)
- **AC12** For a Single display the sidebar's **Schema & Structured Data** panel (closed at first) shows Review
  author, Review schema, Price currency, Price, Rating value, Pro/Con schema, Pros, Cons; only one block in a post
  can turn schema on, the others' panels are disabled; turning Review schema on for a product without a primary
  rating gives it one (3.5). (24B, R43-R48)
- **AC13** The block shows its equivalent shortcode in a text box; typing a shortcode there and leaving the box (or
  Enter) changes the display and the sidebar follows. **Update Display** re-renders the preview. (24C, R7, R49-R50)
- **AC14** **Edit Display** (Single, Button, Image, Table) opens the product's or table's page in a new tab; when
  that tab is closed the preview is drawn again. (24D, R51)
- **AC15** A post with many blocks loads every preview, at most three at a time, the latest request winning. (24E,
  R52-R54)
- **AC16** With the Classic Editor (or Disable Gutenberg) plugin active, the toolbar has **Add A Display**; it opens
  the same modal and inserts the display's shortcode at the cursor. (ED1, R60-R64)
- **AC17** With Elementor active, the widget "Affibloom" (Basic) shows the same panel, modal and preview; its
  controls are the display type's overrides; the saved widget renders the display on the page. (ED2, R65-R72)
- **AC18** With Rank Math active, its content analysis counts the rendered displays' text and refreshes a second after
  a preview renders. (ED3, R73-R74)
- **AC19** On an AMP page a display is styled by inline CSS (no external stylesheet, no script). (ED4, R75)
- **AC20** Posts whose image, button and list blocks carry Lasso's link attributes open in the block editor without
  "This block contains unexpected or invalid content", and saving them keeps those attributes. (ED5, R80-R84; N3)
- **AC21** Existing `affiliate-plugin/lasso` blocks open in the editor as working blocks (preview, sidebar, Edit
  Display) and render unchanged on the site. (ED6, R1-R4; N2)

## 2. Evidence

Read in full: `admin/assets/js/lasso-gutenberg-block.js` (1-3446); `lasso-display-modal.js`; `lasso-modal.js`;
`lasso-monetize-modal.js`; `lasso-elementor.js`; `rank-math-integration.js`; `edit-lasso.js`;
`lasso-post-edit-segment-analytic.js`; `command-palette.js`; `admin/views/modals/{display-add,display-preview,
override-display,link-monetize,link-preview,url-quick-detail}.php`; `admin/views/components/url-quick-detail.php`;
`admin/views/modals/url-add.php` 1-240, 300-370, 900-2330 (the editor variant and the quick detail); `classes/
class-elementor.php`; `libs/elementor/widgets/lasso-shortcode.php`; `classes/class-post-type.php`; `pages/post-type/
class-hook.php`; `classes/{class-mcp-write,class-mcp-insert-display,class-lasso-mcp-post-read,class-lasso-init}.php`;
`pages/url-details/class-hook.php`; CSS `lasso-display-classic-editor.css`, `lasso-elementor.css`, `lasso-amp.css`
(minified, 14 KB).
Read in part: `pages/class-hook.php` 49-262 (hooks), 641-1296 (styles, scripts, `get_lasso_options_data_for_js`,
Elementor), 1367-1612 (schema JSON-LD, schema data); `pages/class-ajax.php` 1176-1290, 1368-1511, 1721-1744,
1916-1935, 2133-2171, 2576-2636; `pages/settings-general/class-ajax.php` 1042-1107; `admin/lasso-constant.php`
388-781 (`LASSO_LINK_CUSTOMIZE_DISPLAY`, `LASSO_LINK_SCHEMA_DISPLAY`); `classes/class-helper.php` (`is_classic_editor`
6457, `is_amp_plugin_loaded` 2713, `render_css_inline`, `get_schema_info_by_lasso_atts` 6514, Elementor helpers
6130-6150, `get_external_sync_secret` 6191); `classes/class-setting.php` 495-546, 632-654; `Lasso_DB::
get_monetize_modal_query` 1197; `Lasso_Affiliate_Link::build_shortcode` 89; `class-lasso-shortcode.php` 208-215, 1218.

**This copy, 2026-11-14:** 250 stored displays in 34 published posts: 248 `affiliate-plugin/lasso` blocks and 2 bare
shortcodes; **all Single** (no `type`, `category`, `prompt` or table). Block JSON attributes: `show_short_code`,
`short_code`, `id`, `link_id`, `ref` 248; `button_text` ("Select a New Display") 248; `schema_review`,
`schema_pros_cons` (stored `false`), `schema_review_author` 231; `schema_price` 210; `schema_price_currency` 173;
`description` 130; `badge` 124; `title` 38; `primary_url` 18; `image_url` 13; `title_url` 6; `secondary_url` 5;
`schema_rating` 2; `title_type` 1. Active plugins include Rank Math, GenerateBlocks + GP Premium, TinyMCE Advanced
(no Classic Editor or Disable Gutenberg, so Lasso's TinyMCE button does not load), no Elementor, no AMP, no Stackable.
`_elementor_data` rows exist on 131 published posts but no `_elementor_edit_mode` (leftovers; not Elementor-built).
**147 published posts** carry `data-lasso-id` in their HTML: 24 `core/image` blocks, 49 `generateblocks/button`
blocks, 1 `core/button` (N3). `lasso_external_sync_secret` is set; no MCP insert receipts.
Example stored block (post 2622):
`<!-- wp:affiliate-plugin/lasso {"show_short_code":true,"short_code":"[lasso badge=\"Best Overall\" description=\"…\"
id=\"14722\" link_id=\"13137\" ref=\"amzn-…\"]","button_text":"Select a New Display","description":"…","badge":"Best
Overall","schema_price":"130.84","schema_price_currency":"USD","schema_review_author":"Harvey Meale",
"schema_review":false,"schema_pros_cons":false,"id":"14722","link_id":"13137","ref":"amzn-…"} --><div
class="wp-block-affiliate-plugin-lasso">[lasso badge="Best Overall" … ref="amzn-…"]</div><!-- /wp:… -->`
(`tests/fixtures/inventory/boxes.json` holds all 250).

## 3. What Lasso does

| # | Rule | Source |
|---|---|---|
| R1 | **Block** `affiliate-plugin/lasso`: apiVersion 3, title "Lasso", category `common`, keywords link, affiliate, lasso, Lasso icon; registered server-side on `init` with no render callback (the saved shortcode is rendered by `[lasso]` in `the_content`) | lasso-gutenberg-block.js 848-854; class-post-type.php 229-243 |
| R2 | **Attributes:** `show_short_code` (bool, false), `short_code` (""), `button_text` ("Add a Display"), `button_update_text` ("Update Display"), `button_edit_display` ("Edit Display"), the 22 customize attributes and the 8 schema attributes (all string, "") | lasso-gutenberg-block.js 3357-3402; lasso-constant.php 678-701, 765-774 |
| R3 | **Saved markup:** `<div class="wp-block-affiliate-plugin-lasso">` + the shortcode text; the comment JSON holds every non-default attribute (the shortcode's attributes are duplicated there). Save canonicalises: attribute order kept, `rel` dropped when equal to `ref` | 1793-1906, 801-846; this copy's data |
| R4 | Insert sets `show_short_code` true, `button_text` "Select a New Display", update/edit texts | 548-565 |
| R5 | **Block panel:** purple (#5E36CA) rounded panel; a white preview area; the Lasso mark; "Choose a Lasso Link to display." when empty; then the shortcode input and buttons | 1686-1897 |
| R6 | **Add a Display** button: label = `button_text`; while the modal HTML loads "Loading..." with a spinner, disabled; on failure "Retry Add a Display" (amber) and clickable | 916-924, 1821-1869 |
| R7 | **Shortcode input** (shown once set): edits `short_code`, re-syncs the sidebar from the typed attributes (absent ones cleared); preview reloads on blur and on Enter. **Update Display** (shown when a shortcode exists) reloads the preview | 926-988, 1768-1820 |
| R8 | The modal HTML is fetched once per page (`lasso_get_display_html`) and appended to `#wpcontent`; state idle/loading/ready/error broadcast to every block | 127-209; class-ajax.php 1721-1744 |
| R9 | **"Choose a Display Type":** two rows of four: Single, Grid, List, Table / Button, Image, Gallery, AI Text (AI Text only when `Launch_Darkly::enable_ai_display()`); each opens its tab: Single/Button/Image → links, Grid/List/Gallery → groups, Table → tables, AI Text → prompt | display-add.php 13-74, 733-736, 1059-1073 |
| R10 | Closing resets the modal to the chooser, clears lists, resets the AI prompt and "Generate" | display-add.php 301-316 |
| R11 | Modal behaviour: Escape closes the topmost modal; a backdrop click closes | lasso-modal.js 1-138 |
| R12 | **"Choose a Link":** header "Choose a Link", **Create a Link**, "Search URLs" (one input per type) | display-add.php 76-103 |
| R13 | Product search `lasso_search_attributes` (POST; nonce `lasso_ajax` in `security`; `edit_posts`, else 403 "Unauthorized."): `search_key` spaces → `%`, `post_title LIKE`, published links only, order `post_modified` desc, `limit` (modal 5), `page`; returns `post_id, edit_url, name, thumbnail, permalink` (Amazon links: the monetized URL), `slug, in_group`, `count, page` | class-ajax.php 1176-1240; class-lasso-db.php 1197-1212 |
| R14 | Search runs on **Enter** only; pages of five with prev/next page links; each page change refetches | display-add.php 564, 738-749, 934-954 |
| R15 | Link rows: 44 px thumbnail, name (title attribute), permalink link (new tab, `noopener noreferrer`), **+ Add**; values escaped; unsafe URL schemes become `#` | 784-851, 250-299 |
| R16 | **Add** inserts `[lasso ref="<slug>" id="<id>"]` (Single), `[lasso type="button" ref id]`, `[lasso type="image" ref id]` | 318-373 |
| R17 | **"Choose a Group":** header, **Create Group**, "Search groups"; rows: the group's name (a button that opens "Add to Group"), "1 link" / "N links", **+ Add**; data from `lasso_get_groups` (F23), five a page | 105-131, 852-905, 968-999 |
| R18 | Group **Add** inserts `[lasso type="grid|list|gallery" category="<slug>"]` | 375-427 |
| R19 | **"Add a New Group"** (`#lasso-create-group-modal`): Name (placeholder "Group Name Goes Here"), Description, **Save Changes** (spinner); empty → alert "Name is required."; save → `lasso_store_category`; refused → "Could not create the group. It may already exist."; failed → "Could not create the group. Please try again."; success (in a group tab) opens "Add to Group" for it | 199-217, 1096-1145 |
| R20 | **"Add to Group"** (`#lasso-add-links-to-group-modal`): **Add Group**, **Create a Link**, "Search URLs" (Enter), five a page; rows: 48 px picture, name, link, **Select** or "✓ Added" (disabled; members and this session's adds); **Done** | 219-239, 584-706 |
| R21 | Select → `lasso_add_lasso_to_group` → "Added" with a flash; failure alerts the server's text or "Could not add link to this group." / "…Please try again."; load failure "Could not load links. Please try again." (alert and in the list); empty "No links found." | 1188-1239, 605-691 |
| R22 | **Add Group** inserts the group as the current tab's type; without a slug "Could not determine this group. Close this dialog and open it again from the group list."; outside a group tab "Use a Grid, List, or Gallery display to insert a group." | 1150-1174 |
| R23 | Closing "Add to Group" redraws the group list (counts) | 718-722 |
| R24 | **"Choose a Table":** header, **Create Table** (link to `page=tables&create=1`, new tab), "Search tables"; rows: title, **+ Add** → `[lasso type="table" id="<id>"]`; data `lasso_get_tables` (F25), five a page | 133-154, 429-445, 906-918, 1002-1033 |
| R25 | **Create a Link** closes the display modal and opens `#url-add` (editor variant: "Add A New Link", "Enter the destination URL your Lasso link will redirect to.", tabs URL / Marketplace, **Add Link**) | display-add.php 1091-1095; url-add.php 127-206 |
| R26 | After a link is created in the editor the **quick detail** opens (`lasso_get_link_quick_detail`, nonce + `edit_post` on the link): the product's fields (Name, Permalink, Primary Destination URL, Button Text, New Window / Tab, NoFollow / NoIndex, Secondary Destination URL and its Button Text and toggles, Groups, custom fields with Add Custom Field and Learn, live preview, Display Theme, image Refresh/Apply, Badge Text, Price (+toggle), Description, Disclosure, Sponsored, Show Disclosure, Detect Opportunities, Link Cloaking), each with Lasso's help text; footer **+ Add** and **Cancel** | url-add.php 1469, 1615-1616, 1979-2058; url-quick-detail.php 63-365; class-ajax.php 1368-1435 |
| R27 | Quick detail preview is live: name, button texts, badge, price, disclosure and description update the box as typed; theme change reloads it (`lasso_get_display_html_in_url_details`); the picture opens the media library | url-add.php 1678-1977; url-quick-detail.php 366-417 |
| R28 | **+ Add** saves through `lasso_save_lasso_url` (the product page's save); errors show in the footer ("Save failed." or the server's text); success inserts the product as the current tab's display (single/button/image) and closes | url-add.php 1252-1326, 2125-2227 |
| R29 | From "Add to Group": the pending group gets the new link (`lasso_add_lasso_to_group`); failure alerts "The link was saved but could not be added to the group." (or "…Please try again."); then the display modal and "Add to Group" reopen | display-add.php 724-731, 1175-1180; url-add.php 2171-2210 |
| R30 | **Cancel** closes the quick detail and returns to "Choose a Link" | display-add.php 1075-1090 |
| R31 | `lasso_save_link_quick_detail` (title, description, custom thumbnail, button text, badge; nonce + `edit_post`) is registered but **no script calls it** | class-ajax.php 70, 1440-1473; grep |
| R32 | **Preview** `lasso_get_shortcode_content` (GET `shortcode`; nonce; `edit_posts`): `do_shortcode`, plus the schema info of the first `[lasso]`; returns `html, schema` | class-ajax.php 1478-1511 |
| R33 | The preview draws inside the block; then the hook `lasso_after_rendering_shortcode_in_gutenberg` fires | lasso-gutenberg-block.js 489-531 |
| R34 | Editor canvas styles (`enqueue_block_assets`, post/page/CPT screens): display CSS variables + custom CSS, `lasso-live.min.css`, table CSS, bootstrap grid, AI padding | class-post-type.php 181-201 |
| R35 | **Customize Display** panel (open): Lasso's notice linking its Shortcode Reference Guide; the fields of the display type from `LASSO_LINK_CUSTOMIZE_DISPLAY` (name, input, help HTML) | 1255-1408; lasso-constant.php 388-713 |
| R36 | **Single** fields: Product title (`title`), Title url (`title_url`), Title tag (`title_type`), Product description (`description`, textarea), Badge (`badge`), Brag (`brag`), Show/Hide the price (`price`), Show/Hide all Fields (`field`), Show/Hide the Primary Rating (`rating`), Theme, First button url/text (`primary_url`, `primary_text`), Second button url/text (`secondary_url`, `secondary_text`), Image url (`image_url`), Disclosure text (`disclosure_text`), Anchor id (`anchor_id`); help texts verbatim in the constant | lasso-constant.php 391-478 |
| R37 | **Button:** Theme, Button type (`button_type`: primary/secondary), Button url, Button text, Anchor id. **Image:** Image url, Anchor id | 479-525 |
| R38 | **Grid:** Columns (1-5), Compact (`hide`), Limit, Product title, Product description, Badge, Brag, price/field/rating toggles, Theme, Title tag, Anchor id, Show/Hide the Disclosure (`disclosure`). **List:** Bullet (`bullets`: decimal, alpha, roman, square, circle, hide), Product description, price/field/rating, Theme, Title tag, Anchor id. **Gallery:** Columns (2-5), Limit, Anchor id. **Table:** Anchor id, data-nosnippet HTML attribute (`data_nosnippet`) | 526-677 |
| R39 | Toggles: `price`, `field`, `rating` are on unless the shortcode says `hide` and write only `hide`; `data_nosnippet`, `disclosure` are on only for `enable` and write only `enable` | 1285-1316, 3296-3319 |
| R40 | Text fields: double quotes removed; `description` keeps line breaks as `lasso-br-code`; `anchor_id` spaces → `-` | 1004-1033 |
| R41 | Rebuild order: `ref, id, link_id, type, category` and the edited attribute first, then the other overrides; empty values dropped | 3279-3355 |
| R42 | Preview reloads when a field's value changed on blur, or on Enter; toggles reload at once | 1222-1253, 1201-1220 |
| R43 | **Schema & Structured Data** panel (closed; Single only): notice (Google's product structured data; "Only allow one Lasso shortcode set of Review and Pro/Con schema per post and just supports for Single Displays."); fields Review author (disabled; the post author's display name), Review schema (toggle), Price currency (default the product's), Price (product price, 2 decimals), Rating value (disabled; primary rating), Pro/Con schema (toggle, shown only if the product has both pros and cons), Pros, Cons (disabled) | 1410-1634; lasso-constant.php 716-780 |
| R44 | Only one block per post may enable schema: once one has, every other block's schema panel is disabled | 1636-1666, 1089-1148 |
| R45 | Schema toggles write `schema_review="enable"`, `schema_pros_cons="enable"` (nothing when off) without reloading | 3320-3330, 1089-1148 |
| R46 | Review schema on, product without primary rating → `lasso_add_field_to_page` (field 1) with value "3.5"; preview reloads | 1099-1199 |
| R47 | Schema data: localized `lassoUrlDetailsSchemaData` (post's `[lasso]` atts → price, currency, pros/cons, rating; which id uses schema) and `lasso_get_gutenberg_schema_data` (GET `post_id`; nonce; `edit_posts` + `edit_post`) on load; adding a Single sets `schema_*` from the preview's schema | class-hook.php 1074-1080, 1549-1612; class-ajax.php 1916-1935; js 131-160, 640-677 |
| R48 | Front end: one JSON-LD `Product` (+Review, Offer, positive/negativeNotes) for the first `[lasso]` with schema enabled | class-hook.php 1367-1540 |
| R49 | Shortcode attributes are parsed from the text with a DOM parse of `[lasso …]` | 3256-3277 |
| R50 | The 2 bare shortcodes are not blocks and get no editor UI | this copy |
| R51 | **Edit Display** (single, button, image, table): opens `url-details&post_id=<id>` or `table-details&id=<id>` in a new window (closing an earlier one); on that window's unload the previews redraw; for an AI prompt it reopens the modal on AI Text with the prompt and regenerates | 626-638, 679-734 |
| R52 | Preview queue: at most 3 requests at once, 40 ms apart, a per-block token so only the latest result draws; loader in the block | 211-294 |
| R53 | On load all blocks in the canvas (iframe or not) are previewed once the title appears (500 ms polls, 5 min cap) | 413-467 |
| R54 | A block's preview also redraws when its `short_code` changes | 904-914 |
| R55 | **Segment analytics** (cloud): events "Open/Close \"Choose a Display Type\" Popup", "Click Lasso cactus icon", "Get Lasso urls of …", "Display Added", "Lasso Link Created"; the snippet loads `cdn.segment.com` on post edit screens when `segment_analytics` is on | js 545, 3245-3254; lasso-post-edit-segment-analytic.js; class-hook.php 1198-1200 |
| R56 | **AI Text:** "Prompt:" (1000 chars), **Generate** → `lasso_get_response_from_prompt` → `get_chatgpt_response`; Quill editor of the answer; "Refresh"; **+ Add** inserts `[lasso prompt="…"]`; "Example Prompts" link; refused unless the AI flag | display-add.php 156-195, 447-561; class-ajax.php 2133-2171 |
| R57 | Editor assets on post/page/CPT screens: bootstrap(+grid, select), jQuery UI, select2, Quill, simplePagination, Font Awesome, media; `lassoOptionsData` (nonces, URLs, display constants) | class-hook.php 689-770, 906-934, 955-1072 |
| R58 | **Command palette** (Ctrl/⌘+Shift+L) on post editors too: "Search Lasso links", ten results via `lasso_search_attributes`, Enter opens the link's edit page | command-palette.js; class-hook.php 1054-1071 |
| R59 | Core link search: `wp_link_query` and `/wp/v2/search` results of Lasso links use the link's public URL (Amazon: the product URL) | class-post-type.php 29-67; post-type/class-hook.php 29-30 |
| R60 | **Classic editor:** only when Classic Editor or Disable Gutenberg is active and on a post/page/CPT screen: TinyMCE plugins `lasso_tc_button` (lasso-display-modal.js) and `lasso_modal_js` | class-post-type.php 154-162; class-helper.php 6457 |
| R61 | Toolbar button "Add A Lasso Display" (icon `lasso-icon-tinymce.svg`); `mce_buttons` also adds `lasso_grid_button`, which no script defines | class-post-type.php 169-174; lasso-display-modal.js 170-195 |
| R62 | Button states: loading (disabled, "Loading Lasso Display..."), error ("Retry Add A Lasso Display"), ready | lasso-display-modal.js 9-53, 103-168 |
| R63 | Insert in Classic: `mceInsertContent` of the shortcode at the cursor; the modal closes; no preview (shortcode text) | display-add.php 329-330; lasso-display-modal.js 219-225 |
| R64 | Classic CSS: backdrop opacity, `#url-add` / `#url-quick-detail` z-index; `tiny_mce_before_init` adds a `keyword` element (keyword opportunities, excluded); Divi `#et_pb_layout` pre-appends the modal | lasso-display-classic-editor.css; class-post-type.php 293-302; lasso-display-modal.js 206-208 |
| R65 | **Elementor:** on `elementor/init` with Elementor active: widget `lasso_shortcode`, title "Lasso", icon `eicon-lasso`, category basic, keywords lasso, shortcode, code, help URL the reference guide | class-hook.php 1261-1272; lasso-shortcode.php 9-33 |
| R66 | Controls: section "Lasso Shortcode": "Enter your shortcode" (textarea, placeholder `[lasso id="123" rel="lasso-rel"]`); then for every display type every customize attribute as `<attr>_<type>` (switcher for toggles, default yes; else text), with its help | lasso-shortcode.php 92-139 |
| R67 | Panel opened: controls of other types hidden; an edited control rewrites the shortcode in the textarea and model | lasso-elementor.js 464-508 |
| R68 | Editor template: the block's panel (preview, "Choose a Lasso Link to display.", shortcode input, Update Display, Add a Display / Select a New Display, Edit Display) | lasso-shortcode.php 170-203 |
| R69 | Insert sets the control and model, closes the modal, asks Elementor to re-render; previews via the same queue and `lasso_get_shortcode_content` | lasso-elementor.js 222-359 |
| R70 | Render: `do_shortcode( shortcode_unautop( setting ) )`; plain content prints the shortcode | lasso-shortcode.php 141-159 |
| R71 | Assets load only when the referrer has `action=elementor`; editor styles `lasso-elementor.css` | lasso-shortcode.php 40-82, 210-216; class-hook.php 1233-1245 |
| R72 | After an Elementor save, Lasso's link scan of that post runs; `class-elementor.php` rewrites `_elementor_data` (monetize, remove, link attributes, SiteStripe, shortcode replacement) | class-hook.php 1292-1296; class-elementor.php 174-552 |
| R73 | **Rank Math** (when `rank-math-analyzer` is registered): `rank_math_content` filter appends every `.lasso-container`'s HTML | rank-math-integration.js; class-post-type.php 277-285 |
| R74 | Rank Math refreshes 1 s (debounced) after a preview renders | rank-math-integration.js 24-35 |
| R75 | **AMP:** when an AMP plugin is detected, `[lasso]` hooks `amp_post_template_css` to print the display CSS vars, custom CSS and `lasso-amp.css` inline once; the AMP GA footer is disabled | class-lasso-shortcode.php 208-215; class-helper.php render_css_inline, 2713 |
| R76 | **MCP routes** (`lasso/v1`, REST, not the Abilities API; registered on public REST): `GET /mcp/posts?limit` (≤100 recent published posts), `GET /mcp/posts/{id}/links` (`[lasso]` ids and Amazon URLs) | class-lasso-mcp-post-read.php; url-details/class-hook.php 228-357 |
| R77 | `POST /mcp-write/{lasso_id}/preview|confirm`, `/mcp-write/undo`: compare-and-swap of a link's destination (`lasso_custom_redirect`), undo receipt 24 h | class-mcp-write.php |
| R78 | `POST /mcp/posts/{id}/insert-display/preview|confirm`, `/mcp/insert-display/undo`: **writes post_content**: appends (or places after the Nth heading) one `affiliate-plugin/lasso` block for a link; refuses Elementor/Thrive posts, HTML in params, a link already present; saves a revision, bumps `post_modified`; requires an active Lasso licence | class-mcp-insert-display.php |
| R79 | MCP auth: HMAC-SHA256 of timestamp+body with `lasso_external_sync_secret` in `x-lasso-signature`, `x-lasso-timestamp` within 5 min; no user capability | url-details/class-hook.php 435-459 |
| R80 | **Core block filters** (Lasso's link index): `core/image` gains `data-lasso-id/name/lid`, `rel`, `target`, `all-links`, `data-lasso-id-img`; `core/button`, `generateblocks/button`, `stackable/button|icon-button` gain the anchor attributes | lasso-gutenberg-block.js 296-411, 1912-1931 |
| R81 | `blocks.getBlockAttributes`: hydrate those attributes from the stored HTML; drop ones only in the JSON | 2251-2364, 2947-2970 |
| R82 | `blocks.getSaveContent.extraProps` and `blocks.getSaveElement`: write them back onto `<a>`/`<img>` so saved output matches the stored HTML (GenerateBlocks `htmlAttrs`, list items' entity normalisation, Stackable raw HTML); skips kadence/, yoast/, tph/, editor-blocks/ | 2972-3240, 2103-2199, 1936-2035 |
| R83 | Script waits for Stackable's script when present | class-post-type.php 252-257 |
| R84 | These filters only preserve markup; they do not change content unless the editor saves | same |
| R85 | `display-preview.php` ("Single Link Display Preview") is not included anywhere; `link-preview.php` is the opportunities/reports preview; `override-display.php` ("Update Link Settings": "Only New Links" / "Override All Links" → `lasso_override_display_option`, nonce `lasso_settings_save`, bulk postmeta update) belongs to Settings General/Amazon; `link-monetize.php` + `lasso-monetize-modal.js` load on opportunities and group pages only, not in the editor | grep; settings-general/class-ajax.php 1042-1107; class-hook.php 1105-1108 |
| R86 | Lasso has **no** "select text → make it a product link" tool in the editor (no `registerFormatType` / RichText button) | grep of admin/assets/js |

## 4. Decisions

Settled by Claude from Lasso and the records:

- **D1 One display modal** (plain script, `wp.apiFetch`, Aurora look, Lasso's steps, labels and messages) serves the
  block, the Classic button and the Elementor widget; it is printed on editor screens, not fetched as HTML (R8's
  states stay for the block button only while the script boots).
- **D2 Routes replace the AJAX actions:** `GET /products?search=&page=&per_page=5` (F23 D10's route), `GET /groups`
  (F23 G1), `POST /groups` (F23 G1), `GET/POST/DELETE /groups/{id}/products` (F23 G4), `GET /tables` (F25 T),
  `POST /products` (F18 D6), `PATCH /products/{id}` (15A), `POST /editor/preview` (render one display) and
  `GET /editor/schema?post_id=`.
- **D3 Affibloom's block** keeps the name `affibloom/product-box` (tests use it) as one block for every type:
  attributes `type`, `productId`, `groupId`, `tableId`, every override under Lasso's attribute names, the schema
  attributes; a dynamic block (save returns nothing; the server renders the same HTML as the shortcode, Task 18S).
- **D4 Shortcode box (R7):** the block shows the equivalent Affibloom shortcode (Task 18S); edits parse back into
  attributes. Until 18S exists the box is hidden.
- **D5 Overrides and toggles** follow R36-R42 exactly; texts are Lasso's minus the product name; the reference-guide
  link (Lasso's site) is left out (deviation 52's rule) and the brag field is kept but its help links nowhere.
- **D6 Schema** panel and the one-per-post rule as R43-R47; the JSON-LD output (R48) belongs to the display renderer
  (section 5). The 3.5 primary rating (R46) is kept: it writes product data, not post content.
- **D7 Create a Link** from the editor is F18 D6's editor variant (Marketplace tab omitted, F18 D5); the quick
  detail is the product form (F1) in a modal, saved with `PATCH /products/{id}`; R31's unused action is not built.
- **D8 Capabilities:** reads, search and preview `INSERT_BOXES`; Create a Link, Create Group, Add to Group need
  `PRODUCTS` (decision 1; Lasso allows `edit_posts`) and are hidden without it.
- **D9 Edit Display** opens Affibloom's product or table page in a new tab; previews redraw on the editor's next
  `focus` after that tab closes.
- **D10 Segment, the command palette's Segment events, Lasso's help links, the AI flag** (cloud) are left out (R55).
- **D11 Classic, Elementor, Rank Math, AMP** are built to R60-R75 with Affibloom names ("Add A Display", widget
  "Affibloom"); `lasso_grid_button` (R61, undefined) and the `keyword` element (R64, excluded feature) are not built.
- **D12 No text-to-link tool** (R86): Lasso has none.

**Needs the user:**

- **N1 U1 no longer holds.** U1 (approved 2026-11-12) skipped "Choose a Display Type" because Single was the only
  display in scope. Under the full-scope rule all seven types exist, so the chooser is a real choice again.
  Recommendation: withdraw U1 and build Lasso's chooser (23A); AC1's test gains the Single click.
- **N2 Existing `affiliate-plugin/lasso` blocks.** ADR-003 converts them to Affibloom's block at cutover, which
  rewrites `post_content` of 34 posts and is now forbidden. Without Lasso's script the 248 blocks show as
  "unsupported" in the editor (they still render through Task 27's `[lasso]` handler). Recommendation: Affibloom
  registers the `affiliate-plugin/lasso` block name itself, hidden from the inserter, with Lasso's attributes and
  save format and this feature's panel, sidebar and preview (ED6); no conversion; ADR-003 is revised.
- **N3 Lasso's link attributes in core blocks.** 147 posts (24 image, 49 GenerateBlocks button, 1 button block) carry
  `data-lasso-*`; without R80-R82 those blocks fail validation when opened. Recommendation: keep editor-only filters
  that preserve the attributes (ED5); no content is rewritten.
- **N4 AI Text** depends on Lasso's AI service and flag (R56; host of `get_chatgpt_response` UNVERIFIED). Recommend
  leaving it out of the chooser (seven types); no post uses `[lasso prompt]`.
- **N5 MCP routes** (R76-R79) are called by Lasso's cloud with its signing secret; insert-display writes post content.
  Recommend building none (C — never); a future AI-agent integration would be a new, read-only design (MCP1 is a
  placeholder, not scheduled).
- **N6 Elementor content rewriting** (R72: after-save scan, `_elementor_data` monetize/replace) is not built (content
  rule; the link scan is the content-index feature's).

## 5. Scope that needs a home

- The display types' rendering (Grid, List, Gallery, Button, Image: F30 "DT" tasks; Table: F25 "T" tasks; Single:
  Task 18) and the shortcode `[affibloom …]` (Task 18S): every insert task depends on them.
- Front-end schema JSON-LD (R48) → the display renderer (F5/F30).
- `[lasso]` compatibility for the legacy blocks and bare shortcodes → Task 27.
- Core link search using products' public URLs (R59) → F1 (products in `/wp/v2/search`); unowned today.
- Command palette (R58) → F19 Dashboard; unowned today.
- `override-display.php` / `lasso_override_display_option` (R85) → Settings (F2/F3 general settings); check owned.
- `link-preview.php`, `link-monetize.php` (R85) → opportunities (F-opportunities) and F23.

## 6. Edge cases

1. A product, group or table deleted after insertion: the preview shows the renderer's empty state; the published
   page prints nothing (as the renderer decides); the sidebar still opens.
2. A typed shortcode that does not parse: the preview is empty and the sidebar keeps its values.
3. Double quotes in an override are removed (R40) so the shortcode box never breaks.
4. Two blocks enabling schema (paste): the first keeps it; the other's panel is disabled (R44).
5. Preview requests racing (fast typing): only the latest draws (R52).
6. The modal opened while another (quick detail, Add to Group) is open: Escape closes the topmost first.
7. A user without `PRODUCTS`: no Create buttons; search and Add work.
8. Session expired: routes answer 401/403; the modal shows "Could not load links. Please try again." and keeps state.
