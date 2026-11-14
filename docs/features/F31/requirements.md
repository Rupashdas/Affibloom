# F31 Shortcodes: requirements

Status: gates 1-2 written 2026-11-14 from a sweep of Lasso 369's shortcode surface (`[lasso]`, `[lasso-attr]`, the
other plugins' shortcodes Lasso takes over, and every place Lasso's admin offers a shortcode to copy). Gate 4 for
Tasks 18S1-18S14, 27A-27I and 27X1-27X8: waiting. Tasks 27A-27I replace Phase 5's Task 27, whose attribute map is
superseded by section 3's A1-A54 (it called `ref`, `link_id` and `theme` ignored and every `type` but single
unsupported; the source shows otherwise).

## 1. User stories and acceptance criteria

- As an editor, I place a product, a group or a table anywhere a shortcode works (classic editor, a text widget, a
  page builder's shortcode box) with Affibloom's own shortcode, copied from the Dashboard, the product page, a group or
  a table, and change what one box shows with attributes, as Lasso's shortcode lets me.
- As the site owner, every `[lasso …]` already in the site's content, and every other plugin's shortcode Lasso took
  over, keeps drawing what it draws today once Lasso is switched off, without one post being rewritten.

**Affibloom's own shortcode, `[affibloom …]` (Phase 3)**

- **AC1** `[affibloom id="<product>"]` draws the same box the block draws for that product (the product's theme, the
  site's Display settings); `slug="<product slug>"` addresses it too and wins over `id`; a retired product draws the
  product it points to; a missing, draft or trashed product draws nothing, for editors as for readers, and the rest of
  the page is unchanged; the box's container carries `anchor_id`, else `lasso-anchor-id-<id>-<n>`; `theme="<name>"`
  draws that theme's layout. (18S1; R7 A1, A2, A5, A31, A39; R8, R14)
- **AC2** Text and link overrides change one box only: `title` (`hide` removes the title), `title_url` (the title's and
  the image's link), `title_type` (`h2`-`h6` wrap the title), `description` (`hide`; `lasso-br-code` is a line break),
  `badge` (`hide`), `image_url`, `image_alt`, `primary_url`, `primary_text`, `secondary_url`, `secondary_text`.
  (18S2; A18-A22, A32, A33, A35, A36)
- **AC3** Value overrides change one box only: `price` (`hide`, `show`, or a text), `prime`, `basis_price`, `rating`
  (`hide`), `pros`, `cons` (`-br-` is a new line), `pros_label`, `cons_label`, `field="hide"`, `show_pros_cons`,
  `disclosure_text`, `style="mobile|desktop"`, `tracking_id`. (18S3; A23-A30, A37, A11 mobile/desktop, A38)
- **AC4** `type="button"` draws the product's button alone (`button_type="secondary"` the second button; `primary_url`,
  `primary_text`, `theme` apply); `type="image"`, `type="image-link"` and `style="image"` draw its linked image alone.
  (18S4; A10 image/button, A11 image, A34; R10 image and button rows)
- **AC5** `type="link"` draws a plain text link (`label`, else `title`, else the name); `sitestripe="true"` draws the
  SiteStripe box when the setting allows it, else the normal box; `type="field" value="<part>"` prints one value of the
  product inline (title, image, thumb, button, price, list_price, amount_saved, percentage_saved, rating, star_rating,
  description, reviews, url, link, last_update). (18S5; A10 link, A12-A14, A40; R11)
- **AC6** `amazon_url="<Amazon URL>"` and `geni_url="<destination URL>"` address the published product with that ASIN
  or that destination; nothing found draws nothing. (18S6; A6, A7)
- **AC7** `[affibloom type="grid|list|gallery" group="<slug>"]` draws the group's published products in the group's
  order (`latest="true"`: newest first), `limit`, `columns`, `compact`, `bullets`, `disclosure`, and the per-box
  overrides of AC2-AC3 applied to every card. (18S7; A8, A44-A49; R12)
- **AC8** `[affibloom type="table" id="<table>"]` draws that comparison table; `theme` and `data_nosnippet` apply.
  (18S8; A10 table, A50, A51)
- **AC9** In a text widget the box takes the phone layout; on an AMP page its CSS is printed inline; in a feed it is
  drawn as on the page. (18S9; R9, R16, R17, R19)
- **AC10** The Dashboard row's **Shortcode** button copies `[affibloom id="<id>"]`, its label reads "Copied" for 1.6 s,
  it does nothing while the row is in quick edit, and a refused copy says "Could not copy shortcode.". (18S10; R30)
- **AC11** The product page's Share kit **Copy shortcode** copies `[affibloom id="<id>"]` and reads "Copied!" for
  1.5 s; it is disabled on a product not yet saved. (18S11; R31)
- **AC12** A group's **Copy Shortcode** ("Copy this Display to your clipboard.") copies `[affibloom type="grid"
  group="<slug>"]` and its tooltip reads "Copied!". (18S12; R32)
- **AC13** A table's **Copy Shortcode** copies `[affibloom type="table" id="<table>"]`; it appears once the table is
  saved. (18S13; R33)
- **AC14** A page whose first box with `schema_review="enable"` or `schema_pros_cons="enable"` prints one
  `script.lasso-schema-markup-output` (Product, with Review and Offer, and pros and cons as positive and negative notes).
  (18S14; R29)

**`[lasso]` and the other plugins' shortcodes, only while Lasso is inactive (Phase 5)**

- **AC15** With Lasso inactive, every `[lasso]` box in the 34 guides draws as it did with Lasso (Task 29's pixel run
  and every link's `href`, `rel`, `target`, `data-lasso-id`): `id`, `link_id`, `ref` and `slug` reach the migrated
  product, a repointed record its target, and every attribute of AC1-AC3 means what it means in Lasso; with Lasso
  active Affibloom registers nothing for `[lasso]`. (27A; R1, R4-R6, R7 A1-A5, A15-A17, A53, A54, R8, R14, R20)
- **AC16** `[lasso type="button|image|image-link|link"]`, `style="image"`, `sitestripe="true"` and `fields="…"
  value="…"` draw as in Lasso. (27B; R7 A41-A43, R10, R11)
- **AC17** `[lasso amazon_url="…"]` and `[lasso geni_url="…"]` draw the product they name. (27C)
- **AC18** `[lasso type="grid|list|gallery" category="<slug>"]` (and `categories=`) draw the migrated group. (27D)
- **AC19** `[lasso type="table" id="<Lasso table>"]` draws the migrated table (this copy: the draft post 9492).
  (27E)
- **AC20** `[lasso prompt="…"]` draws the text Lasso cached for that prompt, or nothing; no request leaves the site.
  (27F; R15)
- **AC21** With Lasso inactive, a post's rendered content gets Lasso's two content repairs (`[lasso ref=">[lasso
  ref="`, and `href=""` → `href="#"`); the stored content is never changed. (27G; R22)
- **AC22** `[lasso]` in a text widget and on an AMP page behaves as AC9. (27H; R16, R17)
- **AC23** `[lasso-attr id="<post>" attr_name="<name>"]` prints that post's stored attribute value with line breaks,
  or nothing. (27I; R3)
- **AC24** `[amazon …]` / `[aawp …]` (`link`, `box`, `fields`, `table`): a migrated product draws its box (or link, or
  field); with AAWP active, AAWP draws the rest; without it, a plain Amazon link. (27X1; R23)
- **AC25** `[amalinkspro apilink=… asin=…]`. (27X2; R24)  **AC26** `[easyazon_link]`, `[easyazon-link]`,
  `[simpleazon-link]`. (27X3; R25)  **AC27** `[easyazon_image]` and its 3 aliases, `[easyazon_cta]` and its alias,
  `[easyazon_infoblock]` and its 2 aliases. (27X4; R25)  **AC28** `[earnist id]`, `[earnist_link id]…`. (27X5; R26)
  **AC29** `[eafl id text]` and the Easy Affiliate Links block, only while that plugin is active. (27X6; R27)
  **AC30** `[thirstylink ids|linkid]…` and `<ta linkid href>` tags. (27X7; R28)
- **AC31** A `[lasso]` that carries another plugin's identifiers and whose product was never migrated draws that
  plugin's original shortcode, as Lasso does at render time. (27X8; R21)

## 2. Evidence

All read completely: `classes/class-lasso-shortcode.php` (1-1889); `admin/views/displays/{single,image,button,
sitestripe,aitext,grid,list,gallery,table,list-box}.php`; `admin/views/displays/aawp/fields.php` and
`aawp/fields/{button,description,star-rating,thumb}.php`; `classes/class-lasso-repoint-merge.php`;
`classes/class-share-kit.php`; `libraries/class-lasso-url.php`; `admin/views/rows/url-row.php`;
`admin/views/groups/header.php`; `admin/views/tables/header.php`; `admin/views/dashboard/header.php` 195-328 (Share
kit); `admin/lasso-constant.php` 388-807 (`LASSO_LINK_CUSTOMIZE_DISPLAY`, `LASSO_LINK_SCHEMA_DISPLAY`, `LASSO_LINK`,
`LASSO_BR_CODE`).

Read in part (the functions this subsystem calls): `class-lasso-affiliate-link.php` 82-97 (`build_shortcode`),
105-646 (`get_lasso_url`, `clone_lasso_url_obj`), 2031-2076 (`search_attributes`), 2941-2951
(`render_icon_markup_field`); `class-helper.php` 110-122, 1422-1437, 1642-1697, 2104-2168, 2346-2359, 2713-2746,
2818-2828, 2997-3402, 3950-3981, 4123-4139, 4275-4291, 4485-4518, 4733-4759, 5129-5218, 5412-5428, 6434-6450,
6514-6547; `class-html-helper.php` 224-248, 606-690; `class-lasso-db.php` 1753-1881, 3936-3965, 5215-5282, 5410-5426,
5951-5963; `class-import.php` 1114-1178; `class-lasso-amazon-api.php` 1468-1560; `class-lasso-cron.php` 44-71,
1760-1775, 2236-2280, 3268-3290, 6425-6455; `class-lasso-init.php` 160-206; `class-post.php` 255-283;
`pages/class-ajax.php` 690-770, 1176-1240, 1478-1511 (`lasso_get_shortcode_content`), 1721-1744
(`lasso_get_display_html`), 2133-2171; `pages/class-hook.php` 1367-1600 (schema); `pages/settings-display/
class-ajax.php` 60-239; `admin/views/dashboard/url-details.php` 80-119, 2398-2580; `admin/assets/js/view-js.php`
925-998; `admin/assets/js/lasso-tables.js` 770-780, 2140-2155; `admin/assets/js/lasso-gutenberg-block.js` 1-180 and
the attribute lists (grep); `libs/elementor/widgets/lasso-shortcode.php` 95-145; grep for every attribute key read as
`$atts['…']` across the plugin, for `title_target`, `is_feed`, `lasso_attributes_`, `LASSO_LINK_CUSTOMIZE_DISPLAY`.

Affibloom documents checked against the source: `docs/features/F10/{requirements,analysis}.md`,
`docs/decisions/ADR-003.md`, `docs/decisions/task-6-decisions.md` (decision 7), Phase 5 Task 27 (whole),
Phase 3's task list and the Task 18S note, `docs/spec/interface.md` (rows 37, 62), `progress.md` deviations 11 and 48,
`docs/features/F1/requirements.md` (B9, D4), `docs/features/F19/requirements.md` (Actions), `docs/features/F23/
requirements.md` (R5, R24, D5), `tests/fixtures/inventory/{attributes,boxes}.json`.

**This copy, 2026-11-14** (SELECT only; `tools/local-wp.sh eval`):

- `[lasso` appears in 34 published posts, 1 draft (9492 `what-ever-title-2`) and 5 revisions. Outside revisions:
  **253 shortcodes, 250 of them in published posts** (inside 34 `affiliate-plugin/lasso` blocks, plus the 2 bare ones
  of the inventory); 301 more copies sit JSON-escaped (`"`) in the blocks' comments.
- Attributes, published (= `attributes.json`): `id` 250, `link_id` 250, `ref` 250, `description` 119, `badge` 113,
  `title` 34, `primary_url` 12, `image_url` 8, `title_url` 4, `title_type` 1 ("Best Lightweight"), `secondary_url` 1.
  The draft adds 3 (`id`, `link_id`; `type="table"` twice, `id="2"`, the table "Volleyball Shoe"). Types: none set on
  the 250 (all single). 13 descriptions use `lasso-br-code`; 44 values contain `'`; none holds `&quot;` or another
  entity. 172 distinct product IDs, every one a published Lasso record.
- No `[lasso-attr]` and no `lasso_attributes_*` meta; no `[amazon]`, `[aawp]`, `[amalinkspro]`, `[earnist…]`,
  `[easyazon…]`, `[simpleazon…]`, `[eafl]`, `[thirstylink]` in any post; no `prompt=`, `sitestripe`,
  `lasso-anchor-id` (no page links to an anchor), `origin="widget"`; no `[lasso` in widgets or post meta (no Elementor
  data); no `href=""` in published posts; no cached AI responses; `wp_lasso_revert` is empty (nothing imported from
  another plugin).
- **ThirstyAffiliates leftovers:** 5 hidden Nelio test variants (`nab_hidden`, e.g. 9235 "8 Best Volleyball Shoes For
  2023 (Tested By Pros)") contain `<ta linkid="6695|9244" href="…/recommends/…">` tags; 6695 and 9244 are published
  `thirstylink` posts; ThirstyAffiliates is not active.
- 3 Lasso tables (2 "Volleyball Shoe", 3 "Test Table", 4 "Best Volleyball"); link locations by display type: Single
  223, Table 2, Image-only anchor text 172, Text 2618. One repoint meta (14771 → 14770). Lasso records: 263 published,
  1 trash, 1 `lasso_delete`.
- Settings: `cpt_support` post, page; `keep_site_stripe_ui` off; `show_disclosure_grid` off; `enable_brag_mode` off;
  `link_from_display_title` on; `theme_name` single Lab, grid Cutter, list Cutter; advanced display customization off.
- `active_plugins` on this copy lists `lasso/affiliate-plugin.php` (Lasso loaded under WP-CLI), none of AAWP,
  EasyAzon, ThirstyAffiliates, Easy Affiliate Links, AmaLinks Pro, Earnist, AMP, Jetpack, Elementor.

## 3. What Lasso does

| # | Rule | Source |
|---|---|---|
| R1 | **Registration** when the plugin loads (front end and admin): `lasso-attr`, `lasso`, `amalinkspro`, `amazon`, `aawp`, `earnist`, `earnist_link`, `easyazon_link`/`easyazon-link`/`simpleazon-link`, `easyazon_image`/`easyazon-image`/`easyazon-image-link`/`simpleazon-image`, `easyazon_cta`/`easyazon-cta`, `easyazon_infoblock`/`easyazon_block`/`easyazon-block`, unconditionally; `eafl` only when `EAFL_Blocks` and `EAFL_POST_TYPE` exist (and EAFL's block render and REST data are replaced, R27); `thirstylink` is removed and re-added on `init` (ThirstyAffiliates' own taken over) | shortcode 73-130, 169-173; init 194-206 |
| R2 | **Filters:** `widget_text` (1, R16), `the_content` (1, Shopify CDATA markers `/*<![CDATA[*/` `/*]]>*/` removed from content holding `shopifycdn.com`), `the_content` (10, R21-R22), `elementor/frontend/builder_content_data` (5: R21 on every `generated_shortcode` / `shortcode` key), `lasso_core_shortcode_attrs_filter` (R6) | shortcode 110-129, 138-164, 1881-1888 |
| R3 | **`[lasso-attr id attr_name]`:** postmeta `lasso_attributes_%` of post `id` whose value matches `attr_name` (LIKE); the first JSON row whose `name` equals `attr_name` prints `nl2br( value )` (not escaped); else ''. Lasso 369 never writes these keys (legacy) | shortcode 180-199; affiliate-link 2031-2076 |
| R4 | **Positional repair:** when the parsed attributes hold an integer key, its value (when it contains `"`) is split on `=`: key = the part before the first `=`, value = the part after the last, quotes removed; the integer key dropped | shortcode 224-226; helper 2104-2168 |
| R5 | Every value loses a leading and trailing `&quot;` | shortcode 228-234 |
| R6 | **AAWP description:** with `id` and `link_id` and no `description`, the link location's original shortcode, when it was `[amazon …box…]` or `[aawp …box…]`, gives its enclosed content as `description` | shortcode 1227-1261 |
| R7 | **Attributes:** the table A1-A54 below | |
| R8 | **Order of work in `[lasso]`:** AMP and Jetpack hooks (R17, R18) → per-request flag (R19) → R4, R5, R6 → read A1-A54 → `limit` normalised → `image-link` → `image` → settings (with the admin preview overlay) and, off admin, the front-end styles and CSS variables → the v2 context (R13) → `slug` → id/link_id/ref + repoint → no product and `prompt` → AI text (R15) → anchor id → visibility gates (R13) → columns → SiteStripe gate → `amazon_url`, `geni_url` → the product (`get_lasso_url`, cloned) → `tracking_id` → **group display** when `category`/`categories` (list, gallery, else grid) → else template: `style`/`type` `image` → image; `button` → button; `table` → table; `fields` + an AAWP `value` → inline field; else single → the plain-link shortcut (A40-A43) → render when the product exists, or `demo="true"`, or `type="table"`; else '' | shortcode 207-508 |
| R9 | **Templates for one product:** single picks SiteStripe (A12), else the list box when `type="list"` without a group, else the theme's layout: Cutter/Flow/Geek/Splash → layout-2, Lab/Llama → layout-3, Cactus/Money → layout-6 (markup: the box sweep, F5) | single 299-316 |
| R10 | **Image display:** `div#<anchor>.lasso-container.lasso-image-container` > comment `LASSO IMAGE (https://getlasso.co)` > `a.lasso-image.image-style#css-<id>` with the product's link attributes > `img.lasso-image-no-lazy` (src: `image_url` or the product's, lazy-load attribute, 500×500, alt: `image_alt` or the name), then the brag icon; a non-Amazon product's permalink gets `?src=image`. **Button display:** `div#<anchor>.lasso-container.lasso-button-container.lasso-<theme>` > comment `LASSO BUTTON` > `a.lasso-button-1` (primary link/text, icon spans allowed) or, with `button_type="secondary"` and a secondary URL, `a.lasso-button-2` (`data-lasso-button="2"`, target2, rel2), then the brag icon; the theme is the shortcode's, else the product's, else the Single type's setting, lowercased without validation | image.php 11-45; button.php 16-74; lasso-url 214-287 |
| R11 | **Inline field** (`fields` non-empty, `value` one of title, image, thumb, button, price, list_price, amount_saved, percentage_saved, rating, star_rating, description, reviews, url, link, last_update): `span.lasso-aawp-fields` with the name; the image URL; `thumb` = linked `img` (`image_size` small `._SL75_` / medium (default) `._SL160_` on Amazon images, `image_class`, `image_align` center/left/right → `aligncenter`/`alignleft`/`alignright`, `image_width` + `image_height` both numeric → size); `button` = `span.aawp-fields-button.lasso-<theme>` > `a.lasso-button-1`; the price; savings basis, amount, percent; the Amazon rating; `star_rating` = `a.lasso-stars` (`--rating`, one decimal) to the Amazon review URL; the description (when shown, `wp_kses_post`); the public URL; `link` = `a` to `title_url` with the name; "N Reviews"; the last update (only with a shown price); `title` attr overrides the name. A `value` outside the list → AAWP renders when installed, else '' | fields.php 1-108; aawp/fields/* |
| R12 | **Group displays** (`category`): the group's published products, `term_order` ascending (or `p.ID` descending with `latest="true"`), `LIMIT 0, <limit>` when `limit` is set; each card is the single template with the same shortcode variables (so every per-box override applies to every card) and `image_alt` = the card's post title; grid: `div#<anchor>.lasso-grid-wrap` with rows of `columns` (`lasso-grid-row lasso-grid-<n>`; v2: one row), brag icon, then `div.lasso-disclosure` with the site's disclosure text when the grid disclosure is on; list: `ol`/`ul` per `bullets`; gallery: rows of image displays (markup: the group-display sweep) | grid.php; list.php; gallery.php; db 1753-1794 |
| R13 | **Display v2** (advanced customization on, saved settings not the defaults, or the filter `lasso_display_v2_frontend_enabled`): wrapper class `lasso-ds-v2-frontend` and `data-layout-*`, `data-density-*`, `data-v2-hide-<element>` attributes; per type (single, grid, list) the site's Elements, then the attribute hides: `badge`, `title`, `description`, `price`, `fields`/`field`, `image_url`, `primary_text`, `secondary_text` = `hide`, `show_pros_cons` false; description and fields follow the Elements; grid disclosure from `disclosure`, else `show_disclosure_grid`; in the admin with `demo="true"` everything is shown. Off v2, `compact` on a grid hides description, disclosure and fields | shortcode 325-403; helper 2997-3402 |
| R14 | **No product:** `get_lasso_url` gives real data only for a published `lasso-urls` post; for a missing ID, another post type, draft, pending, private, trash or `lasso_delete` (when no repoint leads on) the product ID is 0 and `[lasso]` returns '' — readers and editors alike, no message, no log; `demo="true"` draws the placeholder product; `type="table"` draws its table whatever the product | affiliate-link 105-217; shortcode 499-507 |
| R15 | **AI text** (`prompt`, no product; **C — never (Lasso cloud)**): the transient `lasso_chatgpt_response_<format><md5(serialize(prompt))>` when its text is not empty; else POST (encrypted, Lasso headers) to `LASSO_LINK . '/chat'` = `https://lasso.link/chat` with instructions appended; a 200 answer is cached without expiry and printed in `div.lasso-container.lasso-ai > div.lasso-ai-text-container > div.lasso-ai-text`; nothing when empty. The editor's `lasso_get_response_from_prompt` (`edit_posts`, nonce `lasso_ajax`, behind the LaunchDarkly flag `enable_ai_display`, else 403 "Not allow") stores an edited answer as the cache when its hash differs or `is_add_shortcode` | shortcode 361-370, 1814-1872; aitext.php; ajax 2133-2171; constant 787 |
| R16 | **Widgets:** unless Elementor is active, a widget text containing `[lasso` gets `origin="widget"` inserted after every `[lasso` (which also turns `[lasso-attr` into `[lasso origin="widget"-attr`, breaking it); a single box from a widget gets the `mobile` layout class | shortcode 1788-1804; single 250-253 |
| R17 | **AMP** (AMP for WP with the `amp` query var, or the AMP plugin with the query var or an `amp` path segment): on `amp_post_template_css` (1000) print once the display CSS variables, the custom CSS and `lasso-amp.css` (relative `url('..` made absolute); the GA footer is not added | shortcode 208-214, 1204-1206; helper 2713-2736, 4485-4506 |
| R18 | **Jetpack** active: `jetpack_photon_skip_for_url` returns true (every image on the page skips Photon) | shortcode 216-219, 1211-1213 |
| R19 | A per-request flag while a shortcode renders lets `get_lasso_url` reuse a product loaded earlier in the request; group queries may be served from the per-process cache. **Feeds:** no special case (`is_feed` is not used): a feed's content runs `the_content`, so boxes render there as on the page | shortcode 222, 456, 494, 502, 506; affiliate-link 113-117, 626-629; grep |
| R20 | **Escaping:** Lasso prints names, URLs and attribute values into HTML without escaping in most templates (`data-lasso-name`, `title`, `href`, `alt`, `src`); button texts and badges pass `render_icon_markup_field` (`span[class]` only); descriptions `wp_kses_post`; enclosed content of `[lasso]…[/lasso]` is ignored | single, layouts, image, lasso-url 214-287; affiliate-link 2941-2951 |
| R21 | **Render-time conversion** (`the_content` 10, posts of `cpt_support` types): for each shortcode in the content — another plugin's shortcode (R1 list) whose ASIN (AAWP `link`/`box`/`fields`, AmaLinks `apilink`/`asin`, EasyAzon `identifier`/`asin`) is a published record that `wp_lasso_revert` marks imported → replaced by `[lasso … id ref]` (EasyAzon link/image/cta get `type` link/image/button; empty `keywords` take the enclosed text); `[amazon/aawp table=N]` imported → `[lasso … id=<Lasso table> type="table"]`; a `[lasso]` grid/list/gallery whose `category` exists is re-serialised; `thirstylink ids` imported → `[lasso … id ref]`; a single `[lasso]` whose record is missing or not imported → `covert_lasso_shortcode_to_original_shortcode` (AAWP `[amazon …]` or `<!-- wp:aawp/aawp-block … /-->`, EasyAzon link/image/cta/infoblock by `content`/`src`/`key`/`align`/`type`, `[thirstylink]` by `ids`, `[amalinkspro]` with "Buy on Amazon" or the product name; a plain `[lasso]` is rebuilt with its attributes sorted). Values pass `get_attributes` (`"` → `"`; `\`, `u0022`, `&quot;` removed; `u0026` → `&`; HTML entities decoded) | shortcode 956-1133, 1185-1199, 1272-1435; helper 1642-1697; import 1114-1178 |
| R22 | **Content repairs** in the same pass, on every post's content: `[lasso ref=">[lasso ref="` → `">[lasso ref="`; `href=""` → `href="#"`; `<ta linkid href>` tags → R28; the result trimmed | shortcode 964-965, 1135-1172 |
| R23 | **`[amazon]` / `[aawp]`** (`link`, else `box`, else `fields` = the ASIN; `title`/`link_title`/`button_text` for the fallback text): AAWP installed (`AAWP_Core`) → AAWP renders, always; else no ASIN → ''; a published record with that ASIN (country from the default Amazon domain) → `[lasso]` with its `id` (a `box`'s enclosed content as `description`); else `<a href="<Amazon URL for the ASIN, tagged>" target="_blank" rel="nofollow">` title or "Amazon product" `</a>` | shortcode 613-671 |
| R24 | **`[amalinkspro apilink asin]`:** the ASIN from the tagged `apilink`; with a cached Amazon product and a published record → `[lasso id]`; else AmaLinks Pro renders when installed; else the enclosed content | shortcode 911-940 |
| R25 | **EasyAzon** (`asin`, else `identifier`): no ASIN, or no `easyazon_item_<asin>` option → ''; a published record with that ASIN → `[lasso id]` (link: attributes kept; image: `type="image"`; cta: `type="button"`; infoblock: single; `asin`/`identifier` removed for the last three); else the EasyAzon class renders when present; else link: `<a href="<item url, tagged>" target="_blank" rel="nofollow">` content or item title; image/cta/infoblock: **the shortcode text itself** `[easyazon_image|easyazon_cta|easyazon_infoblock k="v" …]` | shortcode 718-903; db 5410-5426 |
| R26 | **Earnist:** `[earnist id]` → `wp_lasso_revert` row where `post_data` or `lasso_id` equals the id → `[lasso]` with that record; none → ''. `[earnist_link id]content` → the record's Amazon product URL, else its permalink: `<a rel="nofollow" target="_BLANK" href>content</a>`; none → '' | shortcode 515-557; db 1863-1881 |
| R27 | **Easy Affiliate Links** (only when active): `[eafl id text]` — an EAFL post → EAFL renders; a Lasso record → `<span class="lasso">` + `[lasso id type="link" title=<text or content>]` + `</span>`; else the content. The block `easy-affiliate-links/easy-affiliate-link` is re-registered (attributes id, type "text", text, textAlign "left", className, updated 0): a Lasso record → `<div class="lasso">…</div>`, else EAFL's render. REST: EAFL's `link` field kept, and `GET`/`POST wp/v2/<EAFL type>/<id>` (read: `read` capability; write: `edit_post`; "Sorry, you are not allowed to do that."; 404 "Invalid post ID.") serve Lasso records too, the write saving EAFL meta through EAFL's filters | shortcode 113-123, 679-710, 1440-1780 |
| R28 | **ThirstyAffiliates:** `[thirstylink ids|linkid]content` — `linkid`, else one of `ids` at random (`wp_rand`); a published Lasso record → `<a href=<public link> data-lasso-id data-lasso-name>content</a>`; else ThirstyAffiliates renders when installed; else `<a href=<_ta_destination_url> target="_blank" rel="nofollow"><post title></a>`. `<ta linkid href>` tags: a Lasso record → `<a href data-lasso-id data-lasso-name target rel>inner</a>` (rel only with nofollow); else, when ThirstyAffiliates is off → `<a href=<tag href> data-lasso-id=<linkid> target="_blank">inner</a>` | shortcode 565-605, 1141-1170 |
| R29 | **Schema** (`wp_head`): in the current post's `[lasso]` shortcodes (not the `"` copies), the first with an `id` and `schema_review="enable"` or `schema_pros_cons="enable"` and a published record prints `<script type="application/ld+json" class="lasso-schema-markup-output">` — Product (name, ImageObject); review: Review by the post author with Rating (the primary rating, one decimal, when > 0) and Offer (`schema_price` else the price, two decimals; `schema_price_currency` else the record's currency; InStock); pros/cons: Pros/Cons field values split on `|||` as `positiveNotes`/`negativeNotes` ItemLists. The block's help: "Only allow one Lasso shortcode set of Review and Pro/Con schema per post and just supports for Single Displays." | hook 1367-1540; frontend 35; constants 716-781 |
| R30 | **Dashboard row "Shortcode"** (`button.js-dashboard-copy-shortcode`, title "Copy shortcode", icon `fa-copy`): copies `[lasso ref="<slug>" id="<id>"]` (`build_shortcode`, slug `esc_attr`ed; empty when id ≤ 0 or no slug → disabled, title "Shortcode unavailable for this link"); ignored while the row is in quick edit; Clipboard API, else a hidden textarea and `execCommand('copy')`; the label reads "Copied" for 1.6 s (`aria-live="polite"`); a failed copy → error "Could not copy shortcode." | url-row.php 178; ajax 708-711; affiliate-link 89-97; view-js 936-998 |
| R31 | **Share kit "Copy shortcode"** (product page, `Share kit` / "Copy snippets for posts, email, and social."): `[lasso rel="<slug>" id="<id>"]`, the slug normalised (`[\W_]+` → `-`, trimmed of `-`), rebuilt live from the Permalink field; disabled when id ≤ 0 and no slug; "Copy block" wraps the canonical shortcode in `<!-- wp:affiliate-plugin/lasso {"show_short_code":true,"short_code":…,"button_text":"Select a New Display","link_id"?,"id"?,"ref"?} -->` + `div.wp-block-affiliate-plugin-lasso`; labels read "Copied!" for 1.5 s. `rel` is not read by `[lasso]` (it renders by `id`); Lasso's scans rewrite `[lasso rel="` to `[lasso ref="` in content | dashboard/header.php 214-238; url-details.php 2398-2580; share-kit 1-127; cron 1769 |
| R32 | **Group "Copy Shortcode"** (`a#copy-shortcode`, `fa-pager`, tooltip "Copy this Display to your clipboard." → "Copied!", a bounce): `[lasso type="grid" category="<slug>"]` from a hidden input (`select()` + `execCommand('copy')`); only for a saved group | groups/header.php 41-58, 97-102 |
| R33 | **Table "Copy Shortcode"** (same control, beside "Saved"/"Saving"): `[lasso type="table" id="<table id>"]`, hidden until the table has an ID, set after creation | tables/header.php 49-62; lasso-tables.js 777-778, 2141-2155 |
| R34 | **Strings Lasso builds elsewhere** (owned by their screens): the post editor's display modal inserts `[lasso ref="<slug>" id="<id>"]`, `[lasso type="button\|image" ref id]`, `[lasso type="grid\|list\|gallery" category="<slug>"]`, `[lasso type="table" id]`, `[lasso prompt="…"]`; admin previews render `[lasso id="0" title description="" badge="" ga="false"]`, `[lasso id ga="false" for_url_details="true"]` (and with `theme`), the Display settings' `[lasso theme price type style id="0" demo="true" ga="false"]` and the demo with `secondary_url="https://getlasso.co" fields="demo"`, tables' `[lasso type="table" table_id="N" ]`; the Elementor widget "Lasso Shortcode" (placeholder `[lasso id="123" rel="lasso-rel"]`); MCP `build_shortcode` | display-add.php 321-467; url-details.php 94-112; settings-display/class-ajax.php 117-197; table-details/class-ajax.php 638-640; elementor widget 95-145; mcp-insert-display 615 |
| R35 | **`lasso_get_shortcode_content`** (GET, nonce `lasso_ajax` as `security`, `edit_posts`, else 403 "Unauthorized."): `do_shortcode( stripslashes( shortcode ) )` → `{ shortcode, html, schema }` (schema from the first `[lasso]`: lasso_id, price as a float without `$`, currency, pros and cons joined by `\|`, whether both exist, primary rating); callers: the Gutenberg block and Elementor previews. **`lasso_get_display_html`** (same guard) returns the display modal, the add-link modal (`is_from_editor`) and the quick-detail modal HTML | ajax 1478-1511, 1721-1744; helper 6514-6547 |

**Attributes of `[lasso]`** (all strings; '' when absent unless a default is given)

| # | Attribute | Default · values | Honoured by · effect | Source |
|---|---|---|---|---|
| A1 | `id` | Lasso record ID | all; the table ID for `type="table"` (`table_id` wins) | shortcode 240; table 21-27 |
| A2 | `slug` | record `post_name` | looked up whatever the status, **wins over `id`**; not found → `id` | 239, 344-348; db 5951-5963 |
| A3 | `link_id` | link-location ID | with no `id`: the location's record; printed as `data-lasso-id` on every link (else the record ID); the default anchor's suffix; R6 | 243; repoint 79-85; lasso-url 160, 219 |
| A4 | `ref` | record slug | with no `id` and no usable `link_id`: the record named so, any status | repoint 87-101 |
| A5 | (repoint) | — | the resolved ID follows `lasso_repointed_to_lasso_id` up to 50 hops, stopping at a loop or a target that is not a Lasso post (status not checked; R14 then applies) | 350-359; repoint 29-57 |
| A6 | `amazon_url` | Amazon URL (trimmed) | with no product: the published record with that ASIN and country | 294, 415-419; db 5215-5255 |
| A7 | `geni_url` | URL (trimmed) | with no product: the published record whose `redirect_url` equals it | 295, 421-425; db 5262-5282 |
| A8 | `category`, `categories` | group slug (`categories` wins) | switches to a group display (R12); the default anchor uses it | 260-261, 372-376, 443-457 |
| A9 | `prompt`, `format` | text · `html` (or `text`) | no product → AI text (R15) | 241-242, 361-370 |
| A10 | `type` | '' · `image`, `image-link` (= image), `button`, `table` (any case), `link`, `list`, `grid`, `gallery`; anything else = single | R8; `list` without a group = one list box | 249, 317, 449-471, 487-497; single 301 |
| A11 | `style` | '' · `image` = image display; `mobile`/`desktop` (any case) = that layout class on the single box | 248, 458; single 255-261 |
| A12 | `sitestripe` | `true` | SiteStripe template, only when the setting `keep_site_stripe_ui` is on | 312, 413; single 299-300 |
| A13 | `sitestripe_style` | `width:240px;height:400px;` | inline style of the SiteStripe box | 313; sitestripe.php 16 |
| A14 | `fields` + `value` | any + R11's list | inline field (R11) | 277-278, 466-467 |
| A15 | `for_url_details` | `false` · `true` | admin preview: never the plain-link shortcut; a hidden disclosure is kept, hidden | 297, 487; single 274-277 |
| A16 | `demo` | `false` · `true` | draws without a product: "The Link Title Goes Here", "$199.99", a Lorem ipsum description, link `#`, the default thumbnail; in the admin with v2 every element shown | 267, 326-339, 499; affiliate-link 140-165 |
| A17 | `origin` | `widget` | added by R16; single box → `mobile` | 296; single 250-253 |
| A18 | `title` | text · `hide` | the name (single, cards, link, field); `hide` → no title | 253; single 74-78; post 277-283 |
| A19 | `title_url` | URL | the title's and the image's `href` (default: the public link, after `primary_url`); `target` and `rel` stay the product's (`title_target` is set but never printed) | 254; single 202-203; layouts 33-43, 94 |
| A20 | `title_type` | any text | `<x>` … `</x>` around the title, unvalidated (help: "Common values are: H1, H2, H3, H4"); the list box also gets it lowercased as a class | 255; single 221-224; list-box 17 |
| A21 | `description` | text · `hide` | `lasso-br-code` → `<br>`; text forces the description shown | 258-259; single 67-72 |
| A22 | `badge` | text · `hide` | the badge text, or none | 263; single 61-65 |
| A23 | `price` | `hide`, `show`, or text | hide / show the price; text replaces it and shows it | 256; single 206-214, 282 |
| A24 | `prime` | any non-empty | the Prime logo shown | 257; single 217-219 |
| A25 | `basis_price` | text | `<strike>text</strike>` as the discount price | 291; single 182-184 |
| A26 | `rating` | number · `hide` | the primary rating (made when missing), or none | 276; single 84-91 |
| A27 | `pros`, `cons` | text (`-br-` → new line) | replace or add the Pros / Cons field | 280-284; single 100-151 |
| A28 | `pros_label`, `cons_label` | `Pros`, `Cons` | the field's label, also without `pros`/`cons` | 282, 285; single 94-99, 124-129 |
| A29 | `field`, `fields` | `hide` (`fields="demo"` with A30) | no custom fields | 277-279; single 153-175 |
| A30 | `show_pros_cons` | `true`/`false` | with `fields="demo"`: the demo Pros ("Will make you more effective." / "Quick read and highly actionable.") and Cons ("You have to be open minded.") or none; v2: false hides the element | 290; single 156-175 |
| A31 | `theme` | Cactus, Cutter, Flow, Geek, Lab, Llama, Money, Splash (any case; unknown → Cactus in single) | wins over the product's and the site's theme; button, table (`lasso-table-<theme>-theme`), field button | 251; single 48-59; button 16-26; table 29-34 |
| A32 | `primary_url`, `primary_text` | URL, text | button 1 (and so the default title/image link) | 270-271; single 197-202; button 29-32 |
| A33 | `secondary_url`, `secondary_text` | URL, text | button 2; for `type="button" button_type="secondary"` the URL comes from `primary_url`; button 2 is hidden on the page it links to | 272, 287-289; single 199-200, 226-229 |
| A34 | `button_type` | `primary` · `secondary` | button display (R10) | 286; button 41, 63-72 |
| A35 | `image_url` | URL | the image | 273; single 186-191; image 27-32 |
| A36 | `image_alt` | text | default: the (overridden) name; group cards: the post title | 274; single 80-82; grid 66 |
| A37 | `disclosure_text` | text | the disclosure shown with this text | 275; single 177-180 |
| A38 | `tracking_id` | Amazon tag | the Amazon public link (and button 2) re-tagged — kept only when the tag is whitelisted with multiple tracking IDs on, or a variant's; otherwise the site's tracking ID | 292, 434-440; amazon-api 1468-1560 |
| A39 | `anchor_id` | default `lasso-anchor-id-<category or record ID>-<link_id or uniqid()>` | `id` of the single container (not of cards in a group), image, button, SiteStripe, table, grid/list/gallery wrapper | 247, 372-376; single 29; image 39; button 61; grid 50; list 46-52; gallery 36; table 67 |
| A40 | `label` | text | the plain link's text (else the name) | 298, 495 |
| A41 | `link` | ASIN (AAWP) | equal to the record's Amazon ID (country suffix handled) → plain link `<a href target rel data-lasso-id=<link_id> data-lasso-lid data-lasso-name>text</a>` | 244, 473-497 |
| A42 | `identifier` | ASIN (EasyAzon) | equal to the Amazon ID, and no `align` → plain link | 245, 489 |
| A43 | `align` | any | stops A42's shortcut | 489 |
| A44 | `columns` | grid `1`-`5`, else v2 layout, else 2; gallery `2`-`5`, else 4 | rows | 405-409; helper 4275-4291 |
| A45 | `compact` | boolean (grid, v2 off) | hides each card's description, disclosure, fields; help says "by `hide` value" but `hide` is false → no effect | 398-403; constants 538 |
| A46 | `limit` | '' (all) · number; 0 or not a number → 10 | first N | 262, 300-310 |
| A47 | `latest` | `false` · `true` | newest first | 266; grid 28-32 |
| A48 | `bullets` | decimal, alpha, roman (`ol`); square, circle, hide (`ul`); else `ol.lasso-list-ol.lasso-list-style-decimal` | list | 250; list 46-54 |
| A49 | `disclosure` | `enable`/`disable` | grid disclosure (else `show_disclosure_grid`) | 293, 315-316; helper 3165-3176 |
| A50 | `table_id` | table ID | table, wins over `id` | table 21-27 |
| A51 | `data_nosnippet` | `enable` | `data-nosnippet` on the table | table 38-42 |
| A52 | `schema_review`, `schema_pros_cons`, `schema_price`, `schema_price_currency` (the block also writes `schema_review_author`, `schema_rating`, `schema_pros`, `schema_cons`, unread) | `enable`; number; ISO 4217 | R29 | hook 1403-1421 |
| A53 | `ga`, `width`, `show_toc`, `hide_header`, `brag` | — | read, **no effect** (the brag icon follows the settings only); enclosed content ignored | 252, 264-269, 427-428; html-helper 664-690 |
| A54 | `rel` | slug | not read by `[lasso]` (R31) | grep |

## 4. Decisions

Settled by Claude from Lasso and the records (none drops anything Lasso does or reverses a user decision):

- **D1 Affibloom's own shortcode is `[affibloom …]`** (the user decided there is one, deviation 48): the plugin's
  slug, so it never collides with Lasso's and works whether Lasso is active or not.
- **D2 It speaks Lasso's attribute language.** Every attribute in A1-A52 keeps its name, values, defaults and effect,
  so the box sweep, Lasso's help texts and the `[lasso]` alias share one parser. Only addressing differs: `id` is an
  Affibloom product (or table) ID, `slug` the product's slug, `group` the group's slug (`category` and `categories`
  accepted as Lasso's synonyms), and `type="field" value="<part>"` replaces Lasso's `fields="<anything>" value=…`
  (in `[affibloom]` `field`/`fields` mean only "hide"). `link_id`, `ref`, `link`, `identifier`, `align`, `origin`,
  `for_url_details`, `rel` belong to `[lasso]` only (Lasso's location IDs and other plugins' identifiers); `demo` stays
  for the admin previews.
- **D3 `[lasso]` is an alias**, registered on `init` (20) only while Lasso is inactive (`LASSO_PLUGIN_PATH` undefined
  and no `lasso` shortcode registered; ADR-3): it turns Lasso's addressing (A1-A7) into an Affibloom product through
  the migration's ID map (records, groups by slug, tables by ID), then calls the same renderer as `[affibloom]`. The
  other plugins' shortcodes (R1) follow the same rule (Phase 5, 27X).
- **D4 Failure is silent for readers**, as Lasso's (R14): '' and, as Task 27 planned, a line in the debug log and the
  action `affibloom_legacy_box_failed` (for `[affibloom]`: `affibloom_box_failed`). No editor-only message: Lasso has
  none, and a box that shows only to logged-in users would make the logged-in page differ from the reader's.
- **D5 The default anchor is deterministic:** `lasso-anchor-id-<group slug or product ID>-<link_id or the box's
  number on the page>`. With `link_id` (all 250 boxes) it equals Lasso's; Lasso's `uniqid()` changes on every load,
  so nothing can depend on it. The reader-facing markup keeps Lasso's classes and ids (ADR-6).
- **D6 Output is escaped**, with the same visible result: Affibloom's templates escape names, URLs and attributes
  where Lasso does not (R20); icon spans keep `span[class]`, descriptions `wp_kses_post`.
- **D7 No post is rewritten.** R21 (render-time conversion) and R22 (repairs) change only the rendered content in
  Lasso too; Affibloom does the same at render time while Lasso is inactive (27G, 27X8). Lasso's stored-content
  rewrites around shortcodes (scans turning `[lasso rel="` into `ref`, SiteStripe iframes into `[lasso sitestripe …]`,
  imports turning `[amazon …]` into `[lasso id …]`, reverts back) are not built here; they belong to the scan and
  import sweeps under the no-rewrite rule.
- **D8 `title_type` keeps decision 7** (`h2`-`h6`, anything else ignored and logged) for both shortcodes; see "Needs
  the user" for `h1`.
- **D9 The copied strings** are `[affibloom id="<id>"]` (Dashboard row, Share kit), `[affibloom type="grid"
  group="<slug>"]` (group), `[affibloom type="table" id="<id>"]` (table): the ID alone addresses a product; Lasso's
  `ref`/`rel` were fallbacks for its own scans. Labels, timings and messages are Lasso's (R30-R33); copying uses the
  Clipboard API with Lasso's textarea fallback; no jQuery. These replace the "Coming soon" placeholders (F1 D4, F19
  Actions, F23 D5).
- **D10 Competitor handlers** keep Lasso's order: a migrated product draws Affibloom's box; else the other plugin's
  renderer when it is active; else Lasso's fallback, including EasyAzon's echo of its own shortcode text (R25), so
  nothing the reader sees changes at cutover.
- **D11 Widgets, AMP, feeds** as Lasso (R16-R19), without Lasso's `[lasso-attr` breakage: `origin="widget"` is
  inserted only after `[lasso ` and `[affibloom ` (a space or `]` following). Jetpack's Photon skip is limited to the
  box's own images (Lasso's skips every image of the page once a box renders).
- **D12 The schema output** (R29) reads both shortcodes and the block (the box attributes), one per post, Lasso's
  JSON shape; it sits in Phase 3 beside the box (18S14).

**Needs the user**

- **N1 `[lasso prompt]` (R15) depends on Lasso's cloud** (`https://lasso.link/chat`, LaunchDarkly `enable_ai_display`).
  Recommendation: `[lasso prompt]` prints the answer Lasso cached in its transient (none on this copy) and otherwise
  nothing; `[affibloom]` offers no `prompt`. Classified C.
- **N2 `title_type="h1"`.** Lasso accepts any tag and its help names H1; decision 7 accepts `h2`-`h6`. Recommendation:
  accept `h1` too (no box on the site uses it; a page with two `h1` is the editor's choice, as in Lasso).
- **N3 `compact="hide"`** (A45) does nothing in Lasso although its help text says so. Recommendation: keep Lasso's
  behaviour for `[lasso]` (no visible change at cutover) and let `[affibloom]` accept `true` only, with help text that
  says `true`.
- **N4 Easy Affiliate Links' REST and block takeover** (R27) changes another plugin's API while it is active.
  Recommendation: build it as Lasso has it (27X6), active only when EAFL is; the site does not use EAFL.

## 5. Scope that needs a home

- **The box's markup for every theme and the group, list and gallery markup** (R9, R12): the box sweep (F5, Tasks
  18-22) and the group-display task (F23 section 5). 18S7 is that task's shortcode entry and waits for it.
- **Comparison tables** (R33's screen, `table.php`, `table-horizontal/vertical/mobile.php`): the Tables sweep; 18S8
  and 18S13 are their shortcode entry and copy button.
- **The editor's display modal, its product and group search** (`lasso_search_attributes`, `_group`,
  `lasso_get_display_html`, `display-add.php`) and **the shortcode preview route** (`lasso_get_shortcode_content`,
  R35): F4 (Phase 4); Affibloom's route renders `[affibloom]` for the block's shortcode view.
- **Elementor:** the "Lasso Shortcode" widget, `class-elementor.php`'s scans, `builder_content_data` conversion:
  unowned (page builders); `[affibloom]` works in Elementor's own Shortcode widget.
- **SiteStripe** (`keep_site_stripe_ui`, the iframe scan): the setting with F17 or the scan sweep; 18S5 draws it.
- **Display settings previews** (`settings-display/class-ajax.php`) and the product page preview: F16, F1 (they call
  the renderer with `demo` / `for_url_details`).
- **Stored-content rewrites around shortcodes** (D7): scan, import and revert sweeps (none built).
- **Click tracking's `data-lasso-id`** (A3): the click-script feature decides what Affibloom's links carry.

## 6. Edge cases

1. `[lasso]` and `[affibloom]` with no attributes, or `type` unknown: a single box needs a product, so ''.
2. `slug` that names a draft or trashed product: found, then R14 → ''. `ref` likewise.
3. A repoint loop (A → B → A): stops at the first repeat; that product drawn only if published.
4. `limit="0"` or `limit="abc"` → 10; `limit=""` → all; `columns="6"` → the default.
5. A group that does not exist or is empty: the wrapper alone (grid prints its wrapper and, when on, the disclosure).
6. Two boxes of one product on a page: each takes its own overrides (Lasso clones the product per shortcode).
7. Values with quotes or `]`: WordPress's parser rules; `&quot;` around a value removed (R5); attributes saved
   positionally repaired (R4).
8. A shortcode inside a JSON block comment (`"`): never rendered and never read for schema.
9. `secondary_url` equal to the current page: the second button hidden.
10. `tracking_id` not whitelisted: the site's tracking ID used (A38).
11. Lasso reactivated after cutover: Affibloom unregisters nothing it did not register; `[lasso]` is Lasso's again.
