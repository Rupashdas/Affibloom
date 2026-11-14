# F30 Display types (Grid, List, Gallery, Button, Image, SiteStripe, AI Text): requirements

Status: gates 1-2 written 2026-11-14 from a sweep of Lasso 369's display types other than Single and Table (the
Single box is F5/F16, Tasks 18-22; Table is its own sweep). Gate 4 for Tasks DT1-DT10: given by the user 2026-11-14.

## 1. User stories and acceptance criteria

- As an editor, I show a group's products as a grid of boxes, a numbered or bulleted list, or a gallery of images,
  in the group's order, and I can cap the number shown.
- As an editor, I show one product as a lone button or a lone image that links to it.
- As a reader, I see these displays exactly as Lasso drew them, on desktop and on a phone, with the site's Display
  settings for Grid and List applied.

- **AC1** Lasso's own render of every display type in this feature is captured as baselines (desktop 1280 px, phone
  390 px) from staging before any of them is built, next to `tests/fixtures/baselines/`. (DT1, N4)
- **AC2** `type="grid"` with a group shows the group's published products in group order as Single boxes drawn in the
  Grid theme, colours, labels, density, elements and layout; with the site's Display settings customised (F16 B1, the
  live state) the cards sit in one grid of up to the site's column count, never narrower than 240 px, one column below
  768 px; matches its baselines. (DT2, R13-R20, R54-R56)
- **AC3** `limit="n"` shows the first n (an empty limit shows all, `0` or junk shows 10); `latest="true"` shows the
  newest products first. (DT2, R6, R15)
- **AC4** The grid prints one disclosure after its cards when Disclosure in Grids is on or `disclosure="enable"`, and
  none when it is off or (customised settings) `disclosure="disable"`; a card's own disclosure never shows inside a
  grid. (DT2, R17)
- **AC5** With the Display settings at their defaults (classic mode) the grid is rows of `columns` cards (1-5; 2 when
  absent) and `compact="true"` hides each card's description, fields and disclosure. (DT3, R13, R14, R16, R20)
- **AC6** `type="list"` with a group shows one list card per product (title, image, rating, price, description,
  fields, buttons, disclosure, date; never a badge) inside `<ol>` (decimal by default; `alpha`, `roman`) or `<ul>`
  (`square`, `circle`, `hide`), in the List theme and settings; matches its baselines. (DT4, R21-R24)
- **AC7** With Mobile overflow "Swipe row" saved for List, below 768 px the list scrolls sideways one card (62 %) at a
  time; with "Stack" it stays a column; a grid always stacks. (DT4, R24)
- **AC8** `type="gallery"` with a group shows each product as a linked image, in rows of `columns` (2-5, default 4),
  one per row below 768 px; matches its baselines. (DT6, R25, R28)
- **AC9** `type="button"` shows the product's primary button alone (text and URL overridable); with
  `button_type="secondary"` it shows the secondary button when the product has a secondary URL, else the primary;
  the link carries the product's target and rel. (DT7, R29-R33)
- **AC10** `type="image"` (also `style="image"` and `type="image-link"`) shows the product's image linked to it, with
  `image_url` and `image_alt` overrides. (DT5, R26-R28)
- **AC11** `sitestripe="true"` shows the compact SiteStripe card (image, title, price, Prime, one button, 240 x 400 px
  unless `sitestripe_style` says otherwise) when Keep Site Stripe UI is on, and the ordinary Single box when it is off.
  (DT8, R34-R36, R38)
- **AC12** `prompt="…"` without a product shows the stored AI text for that prompt, and nothing when none is stored;
  a page view never calls an outside service. (DT9, R39-R43, D14)
- **AC13** An unknown group, a group without published products, or a missing product shows readers what Lasso shows
  (an empty wrapper, or nothing) and raises no PHP notice. (DT2 for groups, DT5 for a missing product; each later
  type's task adds its case, R61)
- **AC14** A page with any of these displays loads the display stylesheet (and the phone-layout script when the
  settings are customised); a page without one loads neither. (DT2, R9)
- **AC15** (not built: decision B5) The AI Text generator: Prompt, Generate, the editor, Refresh and Add
  behave as Lasso's tab, through the user's AI provider; without a key it says so and generates nothing. (DT10,
  R44-R49)

## 2. Evidence

All read, Lasso 369 (`D:\lasso\lasso`): `admin/views/displays/{grid,list,list-box,gallery,button,image,aitext,
sitestripe,single,layout-2-box,field-row}.php` (whole files); `classes/class-lasso-shortcode.php` 1-508 (dispatch and
data), 956-1173 (the content filter's group guard), 1788-1872 (`lasso_widget_text`, `get_chatgpt_response`);
`classes/class-helper.php` 935-1020 (`get_lasso_headers`, `send_request`), 2120-2168, 2357-2370, 2818-3431 (the v2
context, visibility, disclosure, wrapper attributes, density presets), 3520-4330 (layout, colours, themes, labels,
density, columns), 4337-4506, 5129-5218 (assets), 5412-5428 (`compose_limit_clause`), 6434-6450 (brag);
`classes/class-html-helper.php` 54-128, 238-248, 606-690; `classes/class-lasso-db.php` 1702-1794
(`get_groups_query`, `get_urls_in_group`), 3458-3488 (`set_order`); `classes/class-setting-enum.php`;
`classes/class-launch-darkly.php`; `classes/class-post.php` 250-283; `classes/class-link-location.php` 200-305;
`classes/class-lasso-cron.php` 1698-1746, 3060-3286, 6429-6459 (SiteStripe scan); `classes/class-lasso-affiliate-link.php`
2941-2951, 3493-3496; `libraries/class-lasso-url.php` (whole); `pages/class-ajax.php` 1478-1511
(`lasso_get_shortcode_content`), 1721-1744 (`lasso_get_display_html`), 2133-2171 (`lasso_get_response_from_prompt`);
`pages/settings-display/class-ajax.php` (whole); `admin/views/settings/display.php` 150-280, 655-704;
`admin/views/modals/display-add.php` (whole); `admin/lasso-constant.php` 95-386 (defaults), 388-714 (Customize
Display attributes), 783-808; `admin/assets/js/lasso-gutenberg-block.js` 1-120, 489-860, 1840-1907;
`admin/assets/js/settings.js` 3550-3627; `admin/assets/css/lasso-live.min.css` (pretty-printed for reading,
1,829 lines; line numbers below are of that copy); `admin/assets/css/lasso-display-v2-frontend.css` 1-165,
1060-1260 and its section map. Groups and their order: F23 (`class-group.php`, `class-category-order.php` were read
there).

Affibloom documents checked against the source: `docs/features/F16/{requirements,analysis}.md`, `src/Domain/Display/`,
`docs/features/F23/requirements.md` (R24, D5, D12, section 5) and `analysis.md`, the "From the Groups sweep" note in
`docs/plans/phase-3-product-box-rendering.md`, `docs/reports/2026-11-13-lasso-full-scope-audit.md`
(Findings 1, 5, 7), `docs/reference/feature-plan.md` (withdrawn exclusions).

**This copy, 2026-11-14:** no post or page uses Grid, List, Gallery, Button, Image, SiteStripe or AI Text: 35 posts
hold about 251 `[lasso]` displays, all Single except 2 Tables in one draft (no `category`, `categories`, `prompt`,
`sitestripe`, `columns`, `bullets`, `compact`, `latest`, `limit` or `button_type` attribute anywhere; the one
`type="button"` match is a calculator's HTML button in post 3001). One Lasso group (`best-volyball-shoe`) with no
products; no `lasso_category_order` rows; no SiteStripe embed (`amazon-adsystem.com/widgets/q`) in any content; no
`lasso_chatgpt_response_*` transient; no `lasso-ai-display` flag cached (only `lasso-lean`, value off); no `[lasso]`
in widgets. Link locations: 223 Single, 2 Table, 172 image-only, 2,618 text; none Grid/List/Gallery/Button/Image.
**Stored Grid and List configuration** (`tests/fixtures/inventory/settings.json`): `theme_name` grid `Cutter`, list
`Cutter`; `display_colors` grid and list both `main` black, `title` black, `background` white, `button` and
`secondary_button` `#5e3f8c`, `button_text` white, `pros` `#0e4a0e`, `cons` `#4a0e0e`, primary label
`<span class="amazon-icon"></span> Buy Now`, secondary label `<span class="pricetag-icon"></span> Check Price On All
Volleyball`, no badge text; `display_density` grid and list `balanced`; `display_elements` grid and list all on except
`price`; `display_layout` columns 3, grid stacked / top / center / spread / hug / stack / contain, list side-by-side /
right / left / spread / hug / stack / contain, no phone overrides; `show_disclosure_grid` false,
`show_disclosure_list` true, `show_disclosure_table` true, `keep_site_stripe_ui` false, `enable_brag_mode` false,
`lasso_affiliate_URL` Lasso's default. The Single density is `spacious`, so the site's settings count as customised
(F16 B1) and Lasso would draw Grid and List in the customised ("v2") mode. `lasso_end_date` on this copy is
2026-11-23 19:13 UTC (see R56).

## 3. What Lasso does

| # | Rule | Source |
|---|---|---|
| R1 | **Entry:** the `[lasso]` shortcode (`lasso_core_shortcode`); broken attributes are repaired, a leading/trailing `&quot;` is trimmed from every value, then the `lasso_core_shortcode_attrs_filter` filter runs. Widgets' `[lasso` gains `origin="widget"` (not Elementor) | class-lasso-shortcode.php 207-237, 1788-1804 |
| R2 | **Dispatch order:** (1) no product id and a `prompt` → AI text, before anything else; (2) a non-empty `category` or `categories` → `type="list"` list, `type="gallery"` gallery, **any other type (none, grid, table, button, image …) a grid**; (3) `style="image"` or `type="image"` → image; (4) `type="button"` → button; (5) `type="table"`; (6) AAWP fields; (7) single.php, which draws SiteStripe when R34 holds, a list card when `type="list"` (R62), else the theme box. `type="image-link"` (AmaLinks) is read as `image` | class-lasso-shortcode.php 317, 361-370, 443-471; single.php 299-316 |
| R3 | `categories` is used as one slug, not split on commas (`categories="a,b"` matches no group) | class-lasso-shortcode.php 443-446 |
| R4 | **Single-product types (button, image, SiteStripe)** resolve the product as Single does (`slug` over `id`, repoint, `amazon_url`, `geni_url`) and draw only when it exists (`lasso_id > 0`) or `demo="true"`; otherwise the output is empty | class-lasso-shortcode.php 344-359, 415-432, 499-507 |
| R5 | `tracking_id` replaces the Amazon tag on the product's link and secondary URL for single-product types. Group displays reload every product afresh, so `tracking_id` does not reach their items | class-lasso-shortcode.php 434-440; grid.php 64; list.php 62; gallery.php 42 |
| R6 | **`limit`:** empty → no limit; otherwise `absint`, and `0` (or anything that is not a number) → 10; the query gets ` LIMIT 0, n`. Applies to grid, list and gallery | class-lasso-shortcode.php 300-310; class-helper.php 5412-5428; grid.php 35-36 |
| R7 | **Anchor id:** `anchor_id` if given, else `lasso-anchor-id-<category or product id>-<link_id or uniqid()>` (a new random suffix on every render) | class-lasso-shortcode.php 372-376 |
| R8 | **Settings for the render:** the saved settings merged with the admin preview overlay; on the front end the display styles are enqueued (`lasso-live.min.css`, `lasso-table-frontend.min.css`, and with customised settings `lasso-display-v2-frontend.css` plus the inline phone-layout script) and the `:root` colour/density variables plus Custom CSS are added inline, once per request | class-lasso-shortcode.php 319-324; class-helper.php 5129-5218, 4337-4359, 4468-4480 |
| R9 | **Customised ("v2") context** (F16 B1): wrapper class `lasso-ds-v2-frontend` and wrapper attributes (`data-layout-*`, `data-layout-m-*`, `data-density-title-lines`/`desc-lines`, `data-v2-hide-<element>`, inline `--lasso-*` colours, density and `--lasso-layout-columns`) built from **the type's bucket**: `grid` for Grid, `list` for List, `single` for every other type. Button, image, gallery, SiteStripe and AI text print no wrapper attributes, so the buckets reach them only through the `:root` variables (Single's colours) | class-helper.php 3070-3080, 3197-3226, 3356-3431 |
| R10 | **Element visibility** = the type's `display_elements` bucket, then shortcode hides: `badge="hide"`, `title="hide"`, `description="hide"`, `price="hide"`, `fields="hide"`/`field="hide"`, `show_pros_cons` false, `image_url="hide"`, `primary_text="hide"`, `secondary_text="hide"`. An admin preview with `demo="true"` shows every element | class-helper.php 3092-3133; class-lasso-shortcode.php 326-339 |
| R11 | **Theme:** Grid cards use `theme_name.grid`, List cards `theme_name.list`; the product's own Display Theme wins over the setting and the `theme` attribute over both; anything outside the 8 themes becomes Cactus. The button display uses `theme_name.single`, then the product's theme, then `theme`, **without** the known-theme check (an unknown name prints `lasso-<name>`, which no CSS styles). Image, gallery, SiteStripe and AI text print no theme class | single.php 19-59; grid.php 46; list.php 44; button.php 16-26 |
| R12 | **Button labels per type:** a card whose product still uses the default button texts takes its type bucket's primary/secondary labels (Grid cards the grid bucket, List cards the list bucket); the button display captures its texts before that call, so the call does not change them | class-helper.php 4123-4139; single.php 195-200; button.php 28-58 |
| R13 | **Grid flags:** customised: description follows the Description element, disclosure follows R17, fields always shown (the Pros & cons element hides them by CSS); classic with `compact`: `compact` is read with PHP's boolean filter (`true`, `1`, `yes`, `on`), and when true hides description, disclosure and fields. **`compact="hide"`, which the attribute's help text names, is not true and does nothing** | class-lasso-shortcode.php 378-403; class-helper.php 2357-2359; lasso-constant.php 535-539 |
| R14 | **Columns:** gallery: `columns` 2, 3, 4 or 5, else 4. Grid: `columns` 1-5; without it, the site's `display_layout.columns` (1-3) when customised, else 2. **When customised the grid's CSS reads `--lasso-layout-columns`, the site's count, so the `columns` attribute has no visible effect** (the attribute's help: "Sets the Grid to display three products per row. Ideal for wide layouts. This can also be set to 1 for a single display per-row and the limit can be set is 5.") | class-lasso-shortcode.php 405-409; class-helper.php 4275-4291, 4327; grid.php 43-58; lasso-constant.php 530-534; v2 css 32-44 |
| R15 | **Group query** (grid, list, gallery): published `lasso-urls` posts in the `lasso-cat` term with that slug, LEFT JOIN `lasso_category_order` on (slug, item); `ORDER BY o.term_order ASC` with no tie-breaker (MySQL sorts a member without an order row, NULL, **first**); `latest="true"` → `ORDER BY p.ID DESC`; then R6's limit. A member with two order rows for the same group would be listed twice (UNVERIFIED whether Lasso can write such duplicates; F23 R12 inserts one row per join) | class-lasso-db.php 1753-1794, 3458-3488; grid.php 20-38 |
| R16 | **Grid markup:** `<div id="<anchor>" class="lasso-grid-wrap[ lasso-ds-v2-frontend]" [wrapper attributes without data-v2-hide-price]>`; customised: one `<div class="lasso-grid-row">` holding every card (only when there are cards); classic: rows `<div class="lasso-grid-row lasso-grid-<N>">` of N cards, a row per card when N is 1, an open row closed at the end; each card is the Single box (layout-2/3/6 by theme) without its own id; then the brag icon (R56); then the grid disclosure (R17); `</div>` | grid.php 41-90; single.php 29, 304-316 |
| R17 | **Grid disclosure:** classic: on when `disclosure="enable"` or Disclosure in Grids (`show_disclosure_grid`) is on (`disclosure="disable"` ignored); customised: `disclosure="enable"`/`"disable"` win, else `show_disclosure_grid`. When on and the site's `disclosure_text` is not empty, `<div class="lasso-disclosure">` + the text **unescaped**, printed once after the cards, **also for an empty group**. Card-level disclosures and brag icons inside `.lasso-grid-row` are hidden by `display:none !important` | class-lasso-shortcode.php 293, 315-316, 384-397; class-helper.php 3165-3176; grid.php 25-26, 87-89; lasso-live 622-624 |
| R18 | **Grid card price (customised):** shown when `price` is not `hide` and either the Grid Price element is on or the product's own Show Price (`show_price` meta = 1) is on; each card's `.lasso-display` repeats the wrapper's v2 class and attributes with its own price and disclosure flags | single.php 278-289; class-helper.php 3289-3316; layout-2-box.php 22 |
| R19 | **Grid card elements** are the Single box's (badge ribbon, image linked to the title URL, title with `title_type`, rating, price/Prime, description, fields, buttons 1 and 2, disclosure, date with tooltip, brag) — F5's markup, drawn with the Grid settings | layout-2-box.php 20-133 (and layout-3/6-box) |
| R20 | **Grid CSS.** Classic: `.lasso-grid-row` a centred wrapping flex row (margin 0 -15px); `.lasso-grid-2/3/4/5 .lasso-container` 50 / 33.333 / 25 / 20 % (3 columns pad 10 px and the card 15 px); grid images 250 px wide; below 768 px every card 100 % wide, centred, 30 px apart; per-theme title/description sizes in grids, reduced at 1300 px and 1200 px; per-theme box rules `.lasso-grid-row .lasso-<theme>` and `.lasso-grid-<n> .lasso-<theme>`. Customised: `display:grid` with `repeat(auto-fill, minmax(min(100%, max(240px, (100% - (cols-1)·gap)/cols)), 1fr))`, gap = density gap, one column below 768 px, row gap at least 30 px, 30 px top padding for the badge ribbon (ribbon at -18 px, left -10 px; Lab's on the right), equal-height cards with the buttons in a shared footer row | lasso-live 598-667, 776-1740 (grid rules per theme), 1608-1740; v2 css 32-79, 1192-1249, 722-848, 2121-2166, 2255-2470, 2555-2777 |
| R21 | **List markup:** no or unknown `bullets` → `<ol id="<anchor>" class="lasso-list-ol lasso-list-style-decimal…">`; `decimal`, `alpha`, `roman` → `<ol class="lasso-list lasso-list-style-<b>…">` (class `lasso-list`, not `lasso-list-ol`); `square`, `circle`, `hide` → `<ul class="lasso-list lasso-list-style-<b>…">`; plus the v2 class and the list wrapper attributes with the disclosure and price hides removed; one card per product (R22) in the List theme; `</ol>`/`</ul>`, then the brag icon. No list-level disclosure | list.php 17-70 |
| R22 | **List card:** `<li><div class="lasso-container lasso-list <theme> lasso-url-<slug> <title_type lowercased>"[ data-v2-hide-price="1"]>` + comment; `.ls-list-title` with the `title_type` tags around the title (a link to the title URL when Link from Display Title is on, else a span), or an empty `<div class="lasso-title">` when the name is empty — shown whatever the Title element says (CSS hides it); `.lasso-list-content`: image link and `<img … loading="lazy" height="500" width="500" alt="<name>">`; stars (`--rating`, value to one decimal) when fields show and the primary rating has a value; price block (discount + latest price when there is a price and Show Price; Prime icon); description (`wp_kses_post`) or an empty `.lasso-description`; fields (field-row.php); button 1; button 2 when there is a secondary URL; `<div class="lasso-disclosure"><p>text</p></div>` when shown; `.lasso-date` with "Price and availability are accurate as of the date and time indicated and are subject to change." when the timestamp shows. **No badge** | list-box.php 14-114; field-row.php |
| R23 | **List disclosure** is per card: the product's own Show Disclosure and a text (the product's, else the site's). Disclosure in Lists & Tables (`show_disclosure_list`) does **not** gate the front end (customised mode forces the list wrapper's disclosure visible; classic mode never reads it) | single.php 271-277; class-helper.php 3214-3217, 3236-3268; list.php 39-43 |
| R24 | **List CSS.** `.lasso-container.lasso-list` 40 px margins; titles 30 px, 70 % wide; image floated right, at most 50 % / 230 x 300 px, pulled up 80 px; description 70 %; list-style classes (`hide` none, `decimal`, `circle`, `square`, `alpha` lower-alpha, `roman` lower-roman); `.lasso-list-ol` padding-left 1 rem; `h1`-`h4` title variants; below 768 px full widths and the image unfloated. Customised: the list is a flex column with the density gap; **below 768 px with `data-layout-mobile-overflow="swipe"` a horizontal scroll row** (no wrap, `overflow-x:auto`, density gap, 6 px bottom padding, `scroll-snap-type:x mandatory`, no markers, each item `flex:0 0 62%`, snap start) | lasso-live 37-52, 76-85, 124-199, 288-314, 392-436, 1565-1580; v2 css 46-51, 1068-1088, 2511-2531, 2784-3060 |
| R25 | **Gallery markup:** the group query (R15, R6); `<div id="<anchor>" class="lasso-grid-wrap">`; rows `<div class="lasso-grid-row lasso-grid-<columns>">` of `columns` items; each item is the image display (R26); brag icon; `</div>`. No customised mode, no theme, no disclosure | gallery.php 16-65 |
| R26 | **Image markup:** `<div id="<anchor>" class="lasso-container lasso-image-container">` + comment; `<a class="lasso-image image-style" id="css-<product id>" <link attributes>>`; `<img class="lasso-image-no-lazy" src="<image>" <lazy-load> height="500" width="500" class="faux-noscript" alt="<alt>">` (two `class` attributes; browsers keep the first); `</a>`; brag icon. `image_url` replaces the image; `image_alt` the alt text, else the name. In a gallery every item gets the gallery's `image_url`, if given, and the **same id** as the wrapper. For a non-Amazon product `?src=image` is appended to its permalink, which the output does not use | image.php 8-45; gallery.php 42-51 |
| R27 | **Lazy loading:** `loading="lazy"`; with Ezoic active `class="ezlazyload"`, with WP Rocket image lazy-load `class="rocket-lazyload"` instead | class-html-helper.php 238-248 |
| R28 | **Image/gallery CSS:** `.lasso-image.image-style img` auto size, `max-height: inherit`; `.lasso-container.lasso-image-container` centred, no padding; in a grid row 20 px below each; gallery rows use R20's classic column widths | lasso-live 585-597, 628-667 |
| R29 | **Button markup:** `<div id="<anchor>" class="lasso-container lasso-button-container lasso-<theme>">` + comment; one link: `<a class="lasso-button-1" …>` with `primary_text` (else the product's text) and `primary_url` (else the product's link), or in secondary mode `<a class="lasso-button-2" …>` with `secondary_text` (else the product's) and the secondary URL; texts through the icon-markup filter (R33); brag icon | button.php 28-74 |
| R30 | **Secondary mode:** `button_type="secondary"` (anything else is primary). For `type="button"` in secondary mode the secondary URL comes from the **`primary_url`** attribute (else the product's secondary URL); with no secondary URL the primary button is drawn. Customize Display: Theme, Button type ("Set this value to show either the Secondary(secondary) or Primary(primary) button. This defaults to \"primary\"."), Button url, Button text, Anchor id | class-lasso-shortcode.php 286-289; button.php 34-41, 63-72; lasso-constant.php 479-509 |
| R31 | **Button CSS:** links in `.lasso-button-container` bold, no underline, full opacity, 0.25 s hover; `a.lasso-button-1/2` 15 px, 10 x 15 px padding, `--lasso-button` / `--lasso-secondary-button` backgrounds, `--lasso-button-text` colour, 0 10 px 10 px 0 margin; icon children pulled up 1 em | lasso-live 56-62, 319-341 |
| R32 | **Link attributes** (every link in these displays): `target`, `href` (the product's public link or the override), `data-lasso-box-trackable="true"`, `data-lasso-id` (the `link_id` attribute; an empty string when it is absent), `data-lasso-lid` (product id), `data-lasso-name` (name, else the domain capitalised), `title` (name), then the product's rel string. Button 2: `data-lasso-button="2"`, the secondary URL, `target2` and `rel2`; no `href`/`target` when the secondary text is empty | class-lasso-url.php 143-287 |
| R33 | **Icon markup in texts:** `<script>`/`<style>` removed, then `wp_kses` with Lasso's icon allowlist | class-lasso-affiliate-link.php 2941-2951 |
| R34 | **SiteStripe dispatch:** `sitestripe="true"` and Keep Site Stripe UI on → single.php → sitestripe.php; with the setting off the shortcode draws the ordinary Single box. `sitestripe_style` defaults to `width:240px;height:400px;` and is printed **unescaped** into the card's `style` | class-lasso-shortcode.php 312-313, 413; single.php 299-300 |
| R35 | **SiteStripe markup:** `<div [id] class="lasso-container sitestripe">` + comment; `<div class="lasso-display sitestripe lasso-url-<slug>" style="<sitestripe_style>">`; `.lasso-box-2`: image link (to the title URL) and image; the title (when it has one; `title_type`; linked per Link from Display Title); price block (`.discount-price` and `.latest-price`, each with a `title`); Prime icon in a span; `<div class="clear">`; `a.lasso-button-1`. No badge, description, fields, secondary button, disclosure, date or brag | sitestripe.php 14-56 |
| R36 | **SiteStripe CSS:** container unset and floated left; card floated left, block; title and price one line with ellipsis, 15 px; image block, square, at most the card's height; buttons full width; the Lasso block wrapper becomes inline-block around a SiteStripe card | lasso-live 92-123 |
| R37 | **Who writes SiteStripe shortcodes:** Lasso's link scan, with Settings → Amazon "Scan SiteStripe in post content" (`enable_site_stripe_post_content_scan`) on, **rewrites post content**: a SiteStripe iframe (`ws-na.amazon-adsystem.com/widgets/q`, `ws-eu…`) matched to a product becomes `[lasso sitestripe="true" sitestripe_scan="true" id="…" ref="…" link_id="…"]`; a SiteStripe image gets the product's Amazon image as `src`; a site-specific filter for one other site (giangiskitchen.com) adds `sitestripe="true"` after "Shop This Post". Nothing in the display modal makes a SiteStripe display | class-lasso-cron.php 208-209, 1698-1746, 3060-3286, 6429-6459 |
| R38 | **Keep Site Stripe UI** (Settings → Display, Disclosure & behavior): toggle, help "When enabled, Lasso renders Amazon SiteStripe-style displays using the SiteStripe iframe layout on the frontend.", key `keep_site_stripe_ui`, default off, saved as a boolean | display.php 692-698; lasso-constant.php 176; class-setting.php 1081; settings-general/class-ajax.php 571 |
| R39 | **AI text dispatch:** no product and a `prompt` → `get_chatgpt_response( prompt, format )`, `format` default `html` (or `text`); no capability or flag check on the front end | class-lasso-shortcode.php 241-242, 361-370 |
| R40 | **AI text storage:** only a transient `lasso_chatgpt_response_<format><md5(serialize(trimmed prompt))>`, **no expiry**, used when its text is not empty after stripping tags. Nothing is stored in the post | class-lasso-shortcode.php 1814-1829, 1866-1869 |
| R41 | **AI text request — C, never (Lasso cloud):** `POST https://lasso.link/chat` (`LASSO_LINK . '/chat'`), headers `license`, `site_id`, `site_url`, body AES-encrypted `{ message }` = the prompt plus Lasso's style instructions (sound authoritative; casual, short sentences; with `html` wrap paragraphs in `<p>`; no conclusion); any status but 200 → empty; the reply's `message` is cached | class-lasso-shortcode.php 1844-1871; lasso-constant.php 786-788; class-helper.php 943-1020 |
| R42 | **A visitor can trigger it:** with no cached text, the page view itself calls Lasso's cloud (R41) | class-lasso-shortcode.php 361-362, 1828-1860 |
| R43 | **AI text markup:** only when the text is not empty: `<div class="lasso-container lasso-ai"><div class="lasso-ai-text-container"><div class="lasso-ai-text">` + the stored HTML **unescaped** + `</div></div></div>`; CSS `.lasso-container.lasso-ai { padding: 0 !important }`; in the block editor `.wp-block .lasso-container.lasso-ai {padding: 0 15px !important;}` | aitext.php; lasso-live 53-55; class-post-type.php 195 |
| R44 | **AI text gate — C, never (Lasso cloud):** LaunchDarkly flag `lasso-ai-display` fetched from `https://lasso.link/api/launch-darkly/flags?<encrypted>` (licence email), cached one hour in a site transient, default off. Off: the modal hides the AI Text tile and the AJAX action answers 403 `Not allow` | class-launch-darkly.php 39-77, 107-110, 126-148; display-add.php 64-72; pages/class-ajax.php 2140-2147 |
| R45 | **Not built (decision B5, 2026-11-14: the generator is not built).** **`wp_ajax_lasso_get_response_from_prompt`:** nonce `lasso_ajax` in `security`; `edit_posts` else 403 "Unauthorized."; R44's flag; `prompt`, `result` (editor HTML), `is_from_modal`, `is_add_shortcode`; success `{ response }` (empty for an empty prompt) | pages/class-ajax.php 79, 2133-2171 |
| R46 | **Not built (decision B5, 2026-11-14: the generator is not built).** **The AI Text tab** (display modal): a response panel (hidden until the first answer) with a loader, a rich-text editor (bold, italic, underline, strike, link, bullet list, text colour, background, clear formatting) and **Add**; Lasso's logo; "Example Prompts" (Lasso's help site); "Prompt:" textarea (2 rows, 1000 characters, required); **Generate**, which reads **Refresh** after an answer. An empty prompt sends nothing. Closing the modal resets the button to "Generate" and clears the prompt and response | display-add.php 156-195, 301-316, 483-561 |
| R47 | **Not built (decision B5, 2026-11-14: the generator is not built).** **Generate / Refresh:** the first Generate asks without editor text → the cached text, or a new one. Refresh sends the editor's HTML: if it differs from the cache it **becomes** the cached text and comes back (no new text); if unchanged, a new text is generated and cached | class-lasso-shortcode.php 1827-1842; display-add.php 483-512 |
| R48 | **Not built (decision B5, 2026-11-14: the generator is not built).** **Add:** sends the editor's HTML with `is_add_shortcode` → it overwrites the cached text (with no cached text a new one is generated instead); then inserts `[lasso prompt="<prompt>"]` (the prompt is not escaped; a `"` in it breaks the shortcode) into the block, Elementor or the classic editor | display-add.php 447-481 |
| R49 | **Not built (decision B5, 2026-11-14: the generator is not built).** **Editing an AI text block:** the block's "Edit Display" button shows only for single, button, image and table shortcodes, so an AI text (and a grid, list or gallery) block has none; the prompt branch of `open_url_detail_window` (reopen the tab with the prompt) is reached by no control found — UNVERIFIED (searched `lasso-gutenberg-block.js` for its callers: only the Edit Display button) | lasso-gutenberg-block.js 626-638, 679-734, 1870-1896 |
| R50 | **Display modal for these types** (owned by F4): tiles Single, Grid, List, Table, Button, Image, Gallery and (R44) AI Text; Button and Image open "Choose a Link" (Create a Link, "Search URLs", five a page, **Add** inserts `[lasso type="button" ref="…" id="…"]` / `[lasso type="image" …]`); Grid, List and Gallery open "Choose a Group" (Create Group, "Search groups", five a page, "1 link"/"n links", a group's name opens "Add to Group"; **Add** inserts `[lasso type="grid\|list\|gallery" category="<slug>"]`; "Add Group" in Add to Group inserts the same, or alerts "Use a Grid, List, or Gallery display to insert a group.") | display-add.php 13-155, 337-427, 694-1073, 1150-1174 |
| R51 | **What an editor sees:** the block preview is the shortcode's HTML (`lasso_get_shortcode_content`, `edit_posts`); an empty group or a missing product previews as nothing above the shortcode field, with no message; no Edit Display for group displays | pages/class-ajax.php 1478-1511; lasso-gutenberg-block.js 489-531, 626-638 |
| R52 | **Customize Display attributes** (the block sidebar; labels quoted): Grid — Columns, Compact, Limit ("Show just the top X Lasso Links in a group."), Product title, Product description, Badge, Brag, Show/Hide the price, Show/Hide all Fields, Show/Hide the Primary Rating, Theme, Title tag, Anchor id, Show/Hide the Disclosure; List — Bullet ("Override the bullet type. To hide the bullet use `hide`. All supported values are: decimal, alpha, roman, square, circle, hide"), Product description, price, Fields, Primary Rating, Theme, Title tag, Anchor id; Gallery — Columns ("Sets the Gallery to display five products per row. Ideal for wide layouts. This can also be set 2, 3, 4, and 5."), Limit, Anchor id; Button (R30); Image — Image url, Anchor id. Toggles: price, field, rating, disclosure | lasso-constant.php 479-661, 678-711 |
| R53 | **Attributes read but not in that list:** `latest`, `categories`, `link_id`, `tracking_id`, `image_alt`, `secondary_text`/`secondary_url` (button), `style="image"`, `type="image-link"`, `sitestripe`, `sitestripe_style`, `prompt`, `format`, `demo`, `ga` | class-lasso-shortcode.php 239-313 |
| R54 | **Group display item data** (each card): the product as Single loads it (name, image, price, Prime, rating, fields, buttons, disclosure, theme), `image_alt` = the product title | grid.php 60-66; list.php 58-64; gallery.php 38-44 |
| R55 | **Per-card overrides from the shortcode** apply to every card of a group display (`title`, `description`, `badge`, `price`, `rating`, `primary_url`, `primary_text`, `image_url`, …) because each card runs single.php with the same variables | single.php 61-262 |
| R56 | **Brag icon:** after the grid's cards, after the list, after the gallery's rows, inside the button and image containers (in grid cards hidden by CSS): `<a class="lasso-brag" href="<Brag mode URL>?utm_source=brag" target="_blank" rel="nofollow noindex"><img src="…/lasso-icon-brag.png" … alt="Lasso Brag" width="30" height="30"></a>`, shown when Enable Brag Mode is on with a Brag mode URL, **or always once 48 hours have passed after `lasso_end_date` (Lasso's licence end)**. The `brag` attribute is read into a variable no template uses | class-html-helper.php 664-690; class-helper.php 6434-6450; class-lasso-shortcode.php 268, 427-428 |
| R57 | **Where used:** link locations record `Button`, `Image`, `Grid`, `List`, `Gallery` from a shortcode's `type`; Grid, List, Gallery and Table are "group" displays, shown with the icons `fa-border-all`, `fa-list`, `fa-images`, `fa-columns` and "a group of links display" | class-link-location.php 37-45, 226-305; class-html-helper.php 622-659 |
| R58 | **Content filter guard:** at render time, a competitor shortcode carrying `type` grid/list/gallery and an existing `category` is replaced by an equivalent `[lasso …]` in the rendered content (not stored) | class-lasso-shortcode.php 956-994 |
| R59 | **Settings → Display for Grid and List** (F16 owns): per-type theme, density, elements, layout (Columns for Grid only; orientation pinned stacked for Grid; Mobile overflow for List at phone width), colours and labels; Disclosure in Grids ("Turn this on to show the disclosure in Displays by default. You can override this per display.") and Disclosure in Lists & Tables ("Turn this on to show the disclosure in List and Table displays by default. You can override this per display."); the Elements count includes the type's disclosure scope; the Price element's default follows Show Price for all three types | display.php 157-262, 666-682 |
| R60 | **Settings preview for Grid and List:** sample items (the first with badge "Our Pick", the others `badge="hide"`; title, image, price, basis price, Prime, description); Grid gets as many as the column count (1-3) with pros and cons hidden, List three; the server wraps Grid cards in `<div class="lasso-grid-wrap"><div class="lasso-grid-row <width class> lasso-grid-<n>">…</div>` + a disclosure with the site text (preview only) and List cards in `<ol class="lasso-list-ol lasso-list-style-decimal">`; `edit_posts` and nonce `lasso_ajax`; errors "Invalid nonce.", "Unauthorized.", "Invalid Lasso post." | settings.js 3550-3627; settings-display/class-ajax.php 31-238; class-html-helper.php 54-128 |
| R61 | **Empty and failure states:** unknown slug or no published members → grid: the wrapper (and brag, and R17's disclosure when on); list: an empty `<ol>`/`<ul>` (and brag); gallery: the empty wrapper (and brag). Missing product (button, image, SiteStripe) → nothing. AI text without text → nothing. No message to readers or editors | grid.php 54-90; list.php 56-70; gallery.php 36-65; class-lasso-shortcode.php 499-507; aitext.php 10 |
| R62 | `[lasso type="list" id="…"]` without a group draws one list card — an `<li>` with no surrounding list | single.php 301-302 |
| R63 | Every template prints an HTML comment naming Lasso (`<!-- LASSO BUTTON (https://getlasso.co) -->`, `LASSO IMAGE`, `LASSO LIST BOX`, `LASSO DISPLAY BOX`) | button.php 62; image.php 40; list-box.php 18; sitestripe.php 15 |

## 4. Decisions

Settled by Claude from Lasso and the records (none drops anything Lasso does or reverses a user decision):

- **D1 One renderer, three callers.** Each display type is a renderer behind `Interface\Front\DisplayHtml`. Its first
  caller is Affibloom's own shortcode (Task 18S; written here `[affibloom type="grid" group="<slug>"]` until 18S names
  it), its second the block's display types (Phase 4, F4), its third the `[lasso]` compatibility handler (Task 27),
  which maps `category`/`categories` to `group`, `type="image-link"` and `style="image"` to `image`, and keeps R2's
  dispatch order. Groups are "groups" in Affibloom (F23), hence `group`.
- **D2 Group members** are `GroupService::members()` (F23 D12): published members in group order; `latest="true"`
  orders by product ID, newest first, as Lasso's `p.ID DESC` (Task 26 must create products in Lasso's ID order, see
  Risks). Lasso lists a member that has no order row **first** (R15) and D12 lists it last; D12 is approved, no
  product is grouped on this site, and Lasso's own paths always write an order row (F23 R12, R14, R23), so the two
  differ only for data Lasso does not make. Kept.
- **D3 Markup and CSS (ADR-6).** Affibloom's own stylesheet and `affibloom-` class names, matching Lasso's visible
  result against baselines (AC1), in Lasso's element order and nesting. Not reproduced because the browser shows the
  same either way: the HTML comments naming Lasso (R63), the image's second `class` attribute (R26), the gallery's
  repeated id (only the wrapper keeps it), the unused `?src=image` (R26).
- **D4 Both rendering modes.** Classic (default settings) and customised (F16 B1, the live state) are both built for
  Grid and List, as Lasso: classic rows of N, customised one CSS grid. Gallery, button, image, SiteStripe and AI text
  have one mode (R25-R43).
- **D5 Grid disclosure** as R17, once after the cards, also for an empty group; the site text is printed through the
  same escaping as the Single box's disclosure (Lasso prints it raw; for plain text the output is identical).
- **D6 List disclosure** per product as R23; `show_disclosure_list` is not read by the list renderer (Lasso parity;
  the setting drives the settings preview and Table, F16/Table sweep).
- **D7 Prices per card** as R18 and R22 (the product's own Show Price beats a hidden Price element).
- **D8 Phones:** the List's swipe row as R24 (`mobileOverflow` of the List bucket); grids always stack; the phone-layout
  swap below 768 px is F16 B12's script, shared with Single (Task 18x).
- **D9 Gallery** as R25-R28: columns 2-5, default 4, no customised mode.
- **D10 Button** as R29-R31, including R30's fallback to the primary button and the secondary URL taken from
  `primary_url` in secondary mode. A theme name outside the 8 prints no theme class (Lasso prints a class nothing
  styles: the same look).
- **D11 Image** as R26-R28, with R27's lazy-load variants.
- **D12 SiteStripe display** as R34-R36, gated by Keep Site Stripe UI (the toggle is Task 10E's; F30 reads it);
  `sitestripe_style` goes through WordPress's `safecss_filter_attr()` (Lasso prints it raw; ordinary width/height
  values come out the same).
- **D13 No SiteStripe scan:** R37 rewrites post content, so it is not built (standing decision). See N3.
- **D14 AI text renders stored text only.** A page view never calls an outside service (R42 is not built). The text
  is kept in Affibloom's own store, not a transient (R40's transients vanish when a cache is flushed, which in Lasso
  silently regenerates the text on the next visit): option `affibloom_ai_texts`, keyed by format and prompt hash, the
  HTML through `wp_kses_post` on save and on print. A prompt with no stored text renders nothing, as Lasso does when
  its call fails. Lasso's cached texts migrate into it (Task 26; this copy has none). Generating text: N2.
- **D15 Brag:** the displays call the one brag mark that F5/Task 10E build, where Lasso prints its icon (R56); what it
  shows is the audit's open decision (Finding 7.1), not re-asked here. It is never forced on by a date (Affibloom has
  no licence). The `brag` attribute stays unread (Lasso reads it into an unused variable).
- **D16 Anchor ids:** `anchor_id` is kept as given (sanitised as an HTML id); a generated one is
  `affibloom-anchor-<group slug or product id>-<n>` with a per-page counter instead of `uniqid()` (nobody can link to a
  random id; a stable one keeps page caches and tests steady).
- **D17 Shortcode overrides and `tracking_id`** behave as R5 and R55: per-card overrides reach every card;
  `tracking_id` reaches single-product types only.
- **D18 Click attributes:** every link carries the location id (`link_id`), product id and name that Task 18 and F12
  define for the Single box (R32), with the same empty location id when `link_id` is absent.
- **D19 Empty states** for readers exactly as R61. **Addition, editor only:** the block preview (Phase 4) of an empty
  or unknown group says "This group has no published products." and of a missing product "This product no longer
  exists." (Lasso shows a blank preview; the reader-facing output is unchanged).
- **D20 Lasso's oddities kept for `[lasso]` content** (Task 27), so migrated pages look identical: R2 (a group with any
  other type is a grid), R3 (`categories` is one slug), R6 (limit rules), R62 (a lone list card).
- **D21 Assets:** the display CSS is part of Affibloom's box stylesheet, loaded only on pages that contain a display
  (Task 18's `BoxAssets`, extended to the shortcode by 18S); no extra script except D8's.
- **D22 One attribute schema** (`Domain\Display\DisplayAttributes`): per type, the attributes of R52 and R53 with their
  allowed values and defaults; the shortcode parser uses it now, F4's Customize Display panel later.
- **D23 What Task 10D renders for Grid and List** (Settings → Display live preview, another sweep's): the real Grid
  and List renderers fed with R60's sample products (Grid: one per column, 1-3, pros and cons hidden; List: three;
  first badge "Our Pick", the rest none), wrapped as D3's grid/list wrappers with the editor's unsaved settings, the
  grid disclosure with the site text, at 1280 and 390 px; the Disclosure element toggles the grid disclosure (Grid)
  and the card disclosures (List). 10D calls `DisplayHtml` with a preview flag; it does not build its own markup.

**Needs the user:**

- **N1 Two attributes that do not do what their help text says.** In Lasso `columns` has no visible effect on a grid
  when the Display settings are customised (R14, the live state), and `compact="hide"` does nothing (R13, only
  `true`/`1`/`yes`/`on`). No content on the site uses either. *Recommendation:* keep Lasso's behaviour for `[lasso]`
  content (Task 27), so migrated pages look the same, and let Affibloom's own shortcode and block do what the help
  texts say (`columns` 1-5 always applies; `compact` also takes `hide`).
  → **Decided 2026-11-14:** D1: the user accepted the recommendation
- **N2 AI Text needs an outside AI service** (R39-R49 depend on Lasso's cloud, C). Options: (a) render stored text
  only (D14) and offer no AI Text tile; (b) also build the generator (R46-R48: Prompt, Generate/Refresh, the editor,
  Add) through an AI provider and API key the user chooses, shipped dormant until a key is entered, like C1.
  *Recommendation:* (a) now — the site has no AI text block, no cached text, and Lasso's flag keeps the tile hidden
  unless Lasso enables it — with DT10 kept as a drafted, dormant task to start only if the user chooses (b).
  → **Decided 2026-11-14:** B5: render stored AI text only (DT9); the generator (DT10) is not built.
- **N3 SiteStripe embeds in content.** Lasso's scan turns SiteStripe iframes into SiteStripe displays by rewriting
  posts (R37), which Affibloom never does. *Recommendation:* when "Scan SiteStripe in post content" is on, replace a
  matched SiteStripe iframe at render time (a `the_content` filter calling F30's SiteStripe or Single renderer) — the
  same visible result without editing content; owned by the sweep for Settings → Amazon / the link scan. The setting
  is off and no post has an embed, so nothing changes on this site.
  → **Decided 2026-11-14:** A4: the user accepted the recommendation
- **N4 Baselines need Lasso's own render** of display types the site never used. Lasso may not be activated on the
  Local copy; staging runs Lasso. *Recommendation:* the user approves DT1 creating, on staging, one test group of four
  existing products and one draft page holding one of each display type (through wp-admin), screenshots taken at 1280
  and 390 px, then both deleted.
  → **Decided 2026-11-14:** F1: one approved staging session; the test group and draft page are deleted after the capture.

## 5. Scope that needs a home

- **The display modal and the block's display types** (R50, R51, R52, D19's previews): Phase 4, F4 — tiles, Choose a
  Link/Group with their searches, Create Group, Add to Group, Customize Display panels from D22's schema, AI Text tab if
  N2 (b).
- **`[lasso]` compatibility** for these types (D1, D20, R58): Task 27.
- **Settings → Display preview for Grid and List**: Task 10D (D23). **Keep Site Stripe UI** and the disclosure
  scopes: Task 10E. Note for F16 (its AC9 and B5 imply all three disclosure scopes gate the box): only the Grid scope
  gates the front end (R17, R23).
- **SiteStripe scan at render time** (N3): the Settings → Amazon / link-scan sweep.
- **Where used** (R57): the link-locations feature (Task 35/F12) records Grid, List, Gallery, Button and Image
  displays and shows their icons.
- **Migration** (Task 26): `lasso_chatgpt_response_*` transients → `affibloom_ai_texts`; groups and order are F23's.
- **`type="link"`** (a plain text link from a shortcode, class-lasso-shortcode.php 486-497) and **AAWP fields**
  (`aawp/fields.php`) are not part of this feature and were not seen in another sweep's scope list.

## 6. Edge cases

1. A group with hundreds of products and no `limit`: every member is drawn (Lasso has no cap); the query stays one
   member read plus one product read per card, as the Single box.
2. A product in a group that is trashed or retired (F23 D12): not listed; one restored returns to its place.
3. `limit="abc"`, `limit="-3"`, `limit="0"`: 10, 3, 10 (R6, `absint`).
4. `columns="7"` or `columns="0"`: grid falls back to the site count (customised) or 2 (classic); gallery to 4.
5. `bullets="ROMAN"`: Lasso compares case-sensitively, so it is the default decimal `<ol>` (R21); kept.
6. Two group displays of the same group on one page get different generated anchor ids (D16's counter).
7. `sitestripe="true"` with Keep Site Stripe UI off draws the Single box (R34); with a product missing, nothing.
8. A prompt containing `"` cannot be stored in a `[lasso prompt]` shortcode (R48); Affibloom's generator (if N2 b)
   stores the prompt in the block's attributes, never by building shortcode text.
9. Stored AI text with `<script>` or event attributes: removed by `wp_kses_post` (Lasso prints it raw, R43).
10. Group displays inside widgets: as the Single box (`origin="widget"` affects only Single's mobile class, R1).
11. The grid disclosure with an empty site text prints nothing even when the setting is on (R17).
12. Two editors reordering a group while a page is cached: the page shows the order of its last render (no extra
    cache layer in Affibloom).
