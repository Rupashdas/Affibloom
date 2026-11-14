# F5 Single product display (the box engine): requirements

Status: rewritten 2026-11-14 from a sweep of Lasso 369's Single display (full-scope rule, deviation 24). The first
version (gates 1-2 approved with Task 6, 2026-11-12, `docs/decisions/task-6-decisions.md`) held AC1 and AC2 only; both
are kept with their numbers and meaning. **Gate 4 for Tasks 18A-18K, 19A, 20A-20D, 21A-21C, 22A-22E, 10D and 14E:
given by the user 2026-11-14 (22D dropped, decision B9).** F6 (buttons), F7 (per-box overrides) and F8 (rating) keep their own ACs and point here for the shared engine.

**What this feature is:** everything that turns one product into the "Single" display a reader sees: the 8 themes and
their 3 box layouts, every element, how the Display settings (F16) reach the markup and the CSS, the per-product
values, the per-box overrides, the link attributes, the structured data, where the CSS and JS load, and the two admin
previews that render the real box. Grid, List, Gallery, Table, Button, Image and AI text are other display types.

## 1. User stories and acceptance criteria

- As a reader, I see the same product box as today, on desktop and mobile, and its links behave as they do today.
- As the site owner, I choose any of Lasso's 8 themes and its layout, spacing, type, elements and colours, and every box
  follows them; pages without a box stay light.
- As an editor, I see the real box while I edit the Display settings or a product, before I save.

- **AC1** Each variant matches its baseline on desktop and mobile. (Kept from 2026-11-12. Owners: amazon, other and
  no-description 18B; second-button, badge and prime 19A; rating 21A.)
- **AC2** A page with no box loads no plugin CSS or JS. (Kept from 2026-11-12; corrected 2026-11-14: in Lasso this is
  the "Smart CSS Loading" mode, off by default, and the copy-to-clipboard script loads on every page in both modes, R44,
  R45. Holds with Smart CSS Loading on, Affibloom's default; see "Needs the user" N2. Owner 18C.)
- **AC3** A product box placed with the block prints Lasso's markup for its layout: the container, the display wrapper
  with its theme class, `lasso-url-<slug>` and, when advanced rendering is on, `lasso-ds-v2-frontend` with every
  `data-layout-*`, `data-density-*`, `data-layout-m-*`, `data-v2-hide-*` attribute and the inline custom properties;
  the elements in the layout's boxes and order. For the three Lab examples the DOM equals the captured `variants/*.html`
  (whitespace, `decoding`, `data-lasso-link` and `data-layout-d-*` ignored). (18A; R1-R12, R14-R16, R22-R24, R29)
- **AC4** Every link in the box (image, title, buttons) carries Lasso's attributes in Lasso's order (`target`, `href`,
  `data-lasso-box-trackable`, `data-lasso-id`, `data-lasso-lid`, `data-lasso-name`, `title`, `rel`) with `rel` and
  `target` by Lasso's rules. (18A; R37-R40)
- **AC5** The stylesheet, the colour properties and the editor's Custom CSS reach the page; with the site's settings the
  three Lab examples (amazon, other, no-description) match their baselines (AC1). (18B; R41-R43, R47)
- **AC6** Assets follow Lasso's loading rules: Smart CSS Loading off loads the box CSS on every front-end page; on, only
  where a box is detected or rendered (late enqueue as a safety net); the phone-layout script loads only when advanced
  rendering is on; AMP pages get the inline CSS. (18C; R44-R46, R48-R52)
- **AC7** Lasso's own Single boxes for all 8 themes, captured on staging for every variant in the capture matrix, sit
  in `tests/fixtures/baselines/themes/`. (18D; D10)
- **AC8** Cutter, Flow, Geek and Splash print the 2-box layout and match their baselines; the theme comes from the
  site's Single theme, then the product's Display Theme, then the box's `theme`; an unknown name draws Cactus. (18E;
  R3, R5, R17)
- **AC9** Cactus and Money print the 6-box layout (the disclosure box only when shown) and match their baselines. (18F;
  R7)
- **AC10** Llama prints the 3-box layout and matches its baselines. (18G; R6)
- **AC11** Every layout control (card layout, image position, text alignment, button alignment, button width, image
  fit) and the phone override change the box as Lasso's do, on desktop and below 768 px, within 1 % of Lasso's
  baselines. (18H; R54-R57)
- **AC12** The three density presets and custom values (gap, padding, space between, title, description, price and
  button size, image height, title and description line clamps) match Lasso's baselines. (18I; R58-R59)
- **AC13** Each element toggle hides its element on the front end as Lasso does (markup kept, hidden by CSS; the title
  not printed); a product's badge shows while the site hides badges; the rating stays visible when Pros & cons is
  off. (18J; R60-R63)
- **AC14** With every Display setting at its default (advanced rendering off) the box prints no v2 wrapper, loads no v2
  stylesheet, and the timestamp, price and disclosure follow Lasso's classic rules; it matches Lasso's classic
  baselines. (18K; R53, R64)
- **AC15** Every button of the product prints in its order (F29); the first is `lasso-button-1`, every other
  `lasso-button-2` with `data-lasso-button="<n>"`; texts default per type. (19A; F6 AC1-AC6)
- **AC16** Every Single override Lasso reads from a box reaches the box (section 3, R65-R89), each covered by F7's
  ACs. (20A-20C)
- **AC17** A post whose first box enables Review or Pro/Con schema prints Lasso's Product JSON-LD in `<head>`, once per
  post. (20D; R90-R93)
- **AC18** Custom fields print in the box by type: Pros and Cons with their icons and colours, bulleted and numbered
  lists, rating fields, short and long text, numbers; hidden fields do not print. (21B; R30-R34)
- **AC19** A Copy to Clipboard field shows its value and a button that copies it ("Copy to clipboard"). (21C; R35-R36)
- **AC20** Price, discount and Prime follow Lasso's rules: the product's Show Price (else the site's), the site's Price
  element in advanced rendering (a product's own Show Price on wins), the discount only with "Show Amazon discount
  pricing", Prime for Amazon products when "Show Prime" is on. (22A; R18-R21, R26-R28)
- **AC21** The disclosure follows Lasso's rules: the product's text, else the site's; shown when the product's Show
  Disclosure is on (the site's setting is only the default); a box's `disclosure_text` forces it on. (22B; R24-R25)
- **AC22** The timestamp shows Amazon's last update with Lasso's tooltip where Lasso shows it (never on basic links;
  classic: with a shown price; advanced: the Timestamp element). (22C; R29)
- **AC23** (not built: decision B9) With Brag mode on and a brag URL set, the box ends with the brag link. (22D; R44)
- **AC24** With "Keep Site Stripe UI" on, a box with `sitestripe="true"` prints Lasso's SiteStripe card. (22E; R13)
- **AC25** Settings → Display shows the sample box for the selected type and theme at desktop and phone width with the
  unsaved settings, every element drawn and hidden live, links inert. (10D; R94-R99)
- **AC26** A product's page shows its real box with the form's unsaved values; a theme change re-renders it without
  saving; typing updates the name, button texts, the second button, badge, price, disclosure and description at once.
  (14E; R100-R103)

## 2. Evidence

All read: `admin/views/displays/{single,layout-2-box,layout-3-box,layout-6-box,field-row,sitestripe,list-box}.php`;
`classes/class-lasso-affiliate-link.php` 1-1944 (whole of `get_lasso_url`, `save_lasso_url` 1085-1897), 2700-3730
(thumbnail, icon markup, price HTML, toggles, sanitizers); `classes/class-html-helper.php` (all);
`libraries/class-lasso-url.php` (all); `classes/class-helper.php` 1105-1142, 2355-2370, 2758-4560 (the whole display
engine), 5105-5370 (enqueue, smart CSS), 6018-6030 (`format_css`), 6428-6547 (brag, schema helpers);
`classes/class-post.php` 248-285; `pages/class-hook-frontend.php` (all); `pages/class-hook.php` 636-770 (front and
admin styles), 1335-1612 (body class, schema), 1633-1797 (event tracking, read for boundaries);
`classes/class-lasso-init.php` 30-214; `classes/class-lasso-shortcode.php` 60-508 (the `[lasso]` handler up to the
template), 1198-1261, 1780-1805; `pages/settings-display/class-ajax.php` (all); `pages/class-ajax.php` 740-820,
1470-1511 (`lasso_get_shortcode_content`), 1718-1744 (`lasso_get_display_html`); `admin/views/modals/display-preview.php`;
`admin/views/dashboard/url-details.php` 60-170, 290-330, 690-890, 1135-1195, 1480-1645; `admin/assets/js/settings.js`
1260-1300, 1446-1530, 1842-1880, 2706-2850, 3550-3674; `admin/assets/js/view-js.php` 2740-2805;
`admin/assets/js/lasso-copy-clipboard.min.js`; `admin/assets/css/lasso-live.min.css` (pretty-printed, 1,413 rules lines:
container, elements, the 8 themes, `.mobile`, tooltips, stars, fields); `lasso-display-v2-frontend.css` (3,169 lines:
section headings, the hide rules 1090-1121 and 1965-2120, the clamps 126-150, read by section);
`admin/lasso-constant.php` 90-103, 140-386 (defaults), 387-760 (the shortcode and schema attribute catalogues);
`classes/class-lasso-db.php` 5576-5598; `models/class-field-mapping.php` 160-185; `models/class-fields.php` 16-80;
`classes/class-lasso-amazon-api.php` 2218-2238; `admin/views/settings/general.php` 236-256 (Smart CSS Loading).

Affibloom documents checked: F5-F8, F16 requirements and analysis, F29, F1 (sections 4-9), `docs/spec/interface.md`,
`docs/decisions/ADR-006.md` and `task-6-decisions.md`, Phase 3 Tasks 18-22, Phase 2 Tasks 10D and 14E,
`tests/E2E/acceptance/F5-F8.spec.ts`, `tests/fixtures/baselines/` (`boxes.json`, `variants/*.html|png`).

**This copy, 2026-11-14:**

- **Settings** (`settings.json`): `theme_name` `{single: Lab, grid: Cutter, list: Cutter}`; Single density `spacious`;
  Single layout side by side, image **left**, align left, buttons spread, **full-width** buttons, image contain;
  Single elements: price, pros & cons and timestamp **off**, the rest on; Single palette `#000000`, `#000000`,
  `#ffffff`, buttons `#5e3f8c`, text `#ffffff`, pros `#0e4a0e`, cons `#4a0e0e`; button texts `<span class="amazon-icon">
  </span> Buy Now` and `<span class="pricetag-icon"></span> Check Price`; `show_price` false, `show_disclosure` true,
  `link_from_display_title` true, `enable_amazon_prime` true, `show_amazon_discount_pricing` false,
  `keep_site_stripe_ui` false, `smart_css_loading` false, `enable_brag_mode` false, custom CSS empty,
  `display_advanced_customization` false. **Advanced rendering is on** (R53: the spacious preset), and every live box
  carries `lasso-ds-v2-frontend` (all seven `variants/*.html`).
- **Products** (263 published): `custom_theme` empty on all; `show_price` `0` on 216, unset on 38; `show_disclosure` on
  all 254 with the meta; `show_description` 1 on 232, 0 on 22; badge 198; second URL 60; per-link `custom_css` 0;
  price 186; 226 Amazon products (url_details); `lasso_field_mapping`: only Primary Rating, 5 rows, all visible.
- **Content:** 35 posts hold `affiliate-plugin/lasso` blocks, each saving `<div class="wp-block-affiliate-plugin-lasso">
  [lasso …]</div>`; 250 boxes; box attributes used (`attributes.json`): `id`, `ref`, `link_id` 250, `description` 119,
  `badge` 113, `title` 34, `primary_url` 12, `image_url` 8, `title_url` 4, `secondary_url` 1, `title_type` 1. No
  `theme`, `price`, `prime`, `rating`, `pros`, `cons`, `sitestripe`, `anchor_id`, `tracking_id` in content. Schema:
  34 blocks store `schema_review: false`, none enables it. No link to `#lasso-anchor-id-…`.
- **Live boxes** (`boxes.json`, 250): all Lab; badge 249, Prime 242 (every Amazon product), second button 78, rating 3,
  no description 1, disclosure 250; no price, no timestamp, no pros/cons, no brag.
- **The site's Additional CSS** (11,933 bytes) holds 33 rules on Lasso's classes that shape today's box:
  `.lasso-display` (a box shadow), `.lasso-badge` (font size 0.8em, padding), `.lasso-description` (font size
  `calc(10px + 0.4vw)`), `.lasso-fields-single`, Cutter rules, the table rules, and the icon spans (`span.amazon-icon`,
  `pricetag-icon`, `medal-icon`, `full-review-icon`, `alsogood-icon`, `youtube-icon`). The baselines include them.
- `lasso_end_date` = 1795461195 (2026-11-23), in the future, so R44's licence rule does not fire today.

## 3. What Lasso does

**C** marks Lasso-cloud behaviour (never built). Sources: `single.php`, `layout-N-box.php`, `field-row.php` = the
display views; `ALL` = `class-lasso-affiliate-link.php`; `Helper` = `class-helper.php`; `Shortcode` =
`class-lasso-shortcode.php`.

### 3.1 Choosing the template and the product

| # | Rule | Source |
|---|---|---|
| R1 | A `[lasso]` box with no group (`category`/`categories`), whose `type` is not image, button, table, link, list (with a group) or gallery, and no AAWP `fields` value, renders `single.php`. It renders only when the product resolves (published `lasso-urls`) or `demo="true"`; otherwise nothing (empty string). The block `affiliate-plugin/lasso` saves the shortcode inside `<div class="wp-block-affiliate-plugin-lasso">`. | Shortcode 442-507; post content |
| R2 | The product is read once per request (`get_lasso_url`, cached while a shortcode renders) and **cloned per box**, so one box's overrides never reach another box of the same product. A non-published or missing ID gives the demo defaults: name "The Link Title Goes Here", price "$199.99", a lorem-ipsum description, the default thumbnail. | ALL 105-140, 154-165, 626-646; Shortcode 429-432 |
| R3 | **Theme:** the site's Single theme (`theme_name.single`; a scalar applies to all types), then the product's `custom_theme` when set, then the box's `theme` when set. Names compare case-insensitively against the 8; empty, `Array` or unknown → Cactus. Class `lasso-<theme>`. | single.php 19-59; Helper 3921-3981 |
| R4 | `sitestripe` (R13) and `type="list"` (a one-product List item, `list-box.php`) leave the Single layouts; every other Single box takes its theme's layout. | single.php 299-316 |
| R5 | **Layouts:** Cutter, Flow, Geek, Splash → 2-box; Lab, Llama → 3-box; Cactus, Money → 6-box. | single.php 304-315 |

### 3.2 Markup

| # | Rule | Source |
|---|---|---|
| R6 | **Shared frame:** `<div [id="<anchor>"] class="lasso-container">`, the comment `<!-- LASSO DISPLAY BOX (https://getlasso.co) -->`, then `<div class="lasso-display <theme> lasso-url-<slug> [mobile\|desktop] [lasso-ds-v2-frontend]" [v2 attributes]>` (class string trimmed, `esc_attr`). The badge comes first, the brag block last. **3-box (Lab, Llama):** box-1 = image link, title, stars, price, `<div class="clear">`, description, fields; box-2 = button 1, button 2; box-3 = disclosure, date. | layout-3-box.php |
| R7 | **6-box (Cactus, Money):** box-1 = title, stars, price, clear, description, fields; box-2 = image link; box-3 = button 1; box-4 = button 2 (the box is printed empty without one); box-5 = disclosure, **printed only when the disclosure shows**; box-6 = date. | layout-6-box.php |
| R8 | **2-box (Cutter, Flow, Geek, Splash):** box-1 = image link; box-2 = title, stars, price, clear, description, fields, button 1, button 2, `<div class="lasso-end">` (disclosure, date). | layout-2-box.php |
| R9 | **Anchor:** `id="lasso-anchor-id-<product>-<link_id>"`, or `-<uniqid()>` without `link_id`; the box's `anchor_id` replaces it; no `id` on a group box. | Shortcode 372-376; single.php 29 |
| R10 | **Badge** (when its text is not empty): `<div class="lasso-badge[ lasso-v2-link-badge-override]"><span class="lasso-badge-text">…</span></div>`; the text passes the icon allowlist (empty `<span class>` only; `<script>`/`<style>` removed). Text: the product's `badge_text`; the site's `badge_text` only for the demo product. | layouts; ALL 182, 284-285, 2927-2951; Helper 3325-3345 |
| R11 | **Image:** `<a class="lasso-image" {title link attributes}><img class="lasso-image-no-lazy" src="…" height="500" width="500" {lazy} alt="…"></a>`. Lazy: `loading="lazy"`; with Ezoic active `class="ezlazyload"`, with WP Rocket lazy images `class="rocket-lazyload"` (a second `class` attribute). `src` and `alt` printed unescaped; `alt` defaults to the name. | layouts; Html_Helper 238-248 |
| R12 | **Title** (when the name is not empty): wrapped in `<title_type>…</title_type>` when the box sets `title_type` (any value, unvalidated); with "Link from Display Title" on `<a class="lasso-title" {title link attributes}>`, off `<span class="lasso-title">`; the name printed through `html_entity_decode`, unescaped. The CSS styles `h1`-`h4` wrappers. | layouts; single.php 204, 221-224, 294-297; class-post.php 277-283; lasso-live `.lasso-box-1 h1 a.lasso-title` |
| R13 | **SiteStripe** (`sitestripe="true"` and the site's "Keep Site Stripe UI" on): `<div class="lasso-container sitestripe"><div class="lasso-display sitestripe lasso-url-<slug>" style="<sitestripe_style>">` (default `width:240px;height:400px;`), one `lasso-box-2` with image, title, price (with `title` attributes on the price spans), Prime after the price block, clear, button 1 only. | sitestripe.php; Shortcode 312-313, 413 |
| R14 | **Stars** (the Primary Rating, field 1, when it has a value): `<div class="lasso-stars" style="--rating: <v>">`, with `<label class="lasso-stars-label float-left mr-1"><strong><name>:</strong></label>` when "Show field name" is `'true'`, then `<span class="lasso-stars-value">` with one decimal. Only visible rows (`field_visible = 1`) are read. | layouts; ALL 488-498; Lasso_DB 5576-5598; Field_Mapping 169-171; Helper 2368-2370 |
| R15 | **Price block** when (a price is set and shown) or Prime: `<div class="lasso-price">` with, when the price shows, `<div class="lasso-price-value"><span class="discount-price">{discount}</span><span class="latest-price">{price}</span></div>`, then `<i class="lasso-amazon-prime"></i>` for Prime. The price prints unescaped (stored HTML). | layouts |
| R16 | **Description** when shown (R22): `<div class="lasso-description">` + `wp_kses_post( description )`. | layouts |

### 3.3 Product values the box reads

| # | Rule | Source |
|---|---|---|
| R17 | **Per-product values:** name (title, `format_post_title`), slug, image (custom thumbnail → featured image `large` → imported thumbnail URL or attachment → for Amazon the cached Amazon image → the default thumbnail; invalid URL → default; `http`→`https` on the site's own host; uploads converted to the CDN), `custom_theme`, `buy_btn_text` (empty → the site's primary text; an AAWP import without text takes AAWP's `button_text`), `second_btn_url`, `second_btn_text` (empty → the site's secondary text), `badge_text`, `price`, `show_price`, `affiliate_desc`, `show_description`, `disclosure_text`, `show_disclosure`, `open_new_tab(2)`, `enable_nofollow(2)`, `enable_sponsored`, `link_cloaking`, the fields. | ALL 217-427, 2795-2845 |
| R18 | **Price:** the `price` meta; an Amazon product shows the cached Amazon price (C1), an extended product its price when the meta is empty; `format_price`. | ALL 330-377, 536 |
| R19 | **Show Price:** the product's `show_price` (`1`/`0`); unset → the site's `show_price`. | ALL 316-319 |
| R20 | **Prime:** shown for **every Amazon product** (a non-empty Amazon ID) when the site's "Show Prime" (`enable_amazon_prime`) is on — not the product's real Prime status. | ALL 133, 577 |
| R21 | **Discount:** the struck basis price (`<strike>…</strike>`, built when the Amazon basis price is above the price), printed only when "Show Amazon discount pricing" is on. | ALL 134, 354, 578-579; Lasso_Amazon_Api 2218-2238 |
| R22 | **Description shows** when `show_description` is truthy (unset counts as 1; a description over 10 characters forces it); otherwise when the description is not '' / `<p><br></p>` / `<p> </p>` — and then **Lasso writes `show_description = 1` during the render**. `<p></p>` is removed. An Amazon product with no description takes Amazon's (C1); with `show_description` off it is emptied. | ALL 186-187, 324-340, 429; class-post.php 255-270 |
| R23 | **Fields:** the product's visible mappings in `field_order`, the Primary Rating taken out for R14, the rest printed in `<div class="lasso-fields">` (R30-R36). | ALL 488-519; Lasso_DB 5576-5598 |
| R24 | **Disclosure text:** the product's `disclosure_text` (trimmed), else the site's `disclosure_text`. `<div class="lasso-disclosure"><span>{esc_html}</span></div>`. | ALL 180-181, 281-282; layouts; Helper 3236-3241 |
| R25 | **Disclosure shows** (Single, both rendering modes) when the product's `show_disclosure` is on and the text is not empty. The product's flag falls back to the site's `show_disclosure` only when the meta is missing, so "Disclosure in Single Display" is the default for products, not a switch on boxes. In advanced rendering `data-v2-hide-disclosure="1"` is synced with the result. | ALL 184, 3442-3496; Helper 3141-3148, 3236-3268, 3277-3279; Shortcode 378-390; single.php 272, 285-288 |
| R26 | **The product page preview keeps a hidden disclosure** (`style="display: none !important;"`) when the text exists but does not show, so ticking Show Disclosure can reveal it (#6000). | single.php 273-277; layouts |
| R27 | **Classic rendering (advanced off) shows the timestamp** when the product is not a basic link, its price shows and the price is not empty. | single.php 234-246 |
| R28 | **Advanced rendering:** the price element shows when the site's Price element is on **or** the product's own `show_price` meta is `1` (#5997), unless the box says `price="hide"`; `data-v2-hide-price` is synced. | single.php 278-284; Helper 3289-3316 |
| R29 | **Timestamp:** never on basic links (`link_type` "Lasso"); advanced rendering: the Timestamp element; classic: R27. `<div class="lasso-date">` is always printed; when shown it holds the last update (`m/d/Y h:i a T`, UTC, from the Amazon or extended product; the demo shows now in the site's date format) and `<i class="lasso-amazon-info" data-tooltip="Price and availability are accurate as of the date and time indicated and are subject to change."></i>`; the tooltip is CSS (`[data-tooltip]`). | single.php 234-246; layouts; ALL 352, 375; Helper 1110-1128; lasso-live tooltip rules |

### 3.4 Fields in the box

| # | Rule | Source |
|---|---|---|
| R30 | **Pros (2) and Cons (3):** `<div class="lasso-fields-<lowercased name> lasso-fields-<id>"><strong><name>:</strong><ul>` one `<li>` per non-empty line, each prefixed by `<span class="lasso-check"><span class="lasso-check-content"></span></span>` (Pros) or `<span class="lasso-x"><span class="lasso-x-1"></span><span class="lasso-x-2"></span></span>` (Cons); nothing when empty. Colours `--lasso-pros`, `--lasso-cons`. | field-row.php 15-19; Html_Helper 428-454 |
| R31 | **Rating-type fields:** `<div class="lasso-fields-single"><strong><name>:</strong><div class="lasso-stars" style="--rating: v"><span class="lasso-stars-value">v.v</span></div></div>`. | field-row.php 20-28 |
| R32 | **Bulleted / numbered lists:** `.lasso-fields-single` > strong + `<div class="lasso-fields-<sanitize_title(name)> lasso-fields-<id>">` + `<ul class="list">` / `<ol class="list">`, one `<li>` per non-empty line. | field-row.php 29-35; Html_Helper 463-492 |
| R33 | **Every other type** (short text, long text, number, …): `.lasso-fields-single` > `<strong><name>:</strong>` + `nl2br( value )`, unescaped. | field-row.php 41-45 |
| R34 | Field names print unescaped (Copy to Clipboard escapes); a value line that is empty is skipped. | field-row.php |
| R35 | **Copy to Clipboard:** `.lasso-fields-single.lasso-field-copy-clipboard` > strong + `<span class="lasso-copy-clipboard-value">` (escaped) + `<button type="button" class="lasso-copy-clipboard-btn" aria-label="Copy to clipboard" data-copy-value="…">` with an inline 18 px SVG; nothing when empty. | field-row.php 36-40; Html_Helper 500-516 |
| R36 | **Copy script** (`lasso-copy-clipboard.min.js`, footer): a delegated click on `.lasso-copy-clipboard-btn` copies `data-copy-value` with `navigator.clipboard.writeText`, else a hidden textarea and `execCommand('copy')`; no visible confirmation. | lasso-copy-clipboard.min.js |

### 3.5 Links and buttons

| # | Rule | Source |
|---|---|---|
| R37 | **Button 1:** `<a class="lasso-button-1" {attributes}>` + the text through the icon allowlist. **Button 2** only when the second URL is not empty: `<a class="lasso-button-2" {attributes, data-lasso-button="2", target2, href = second URL, rel2}>`. | layouts; Lasso_URL 234-287 |
| R38 | **Attributes** in this order: `target`, `href` (the public link, or the given URL), `data-lasso-box-trackable="true"`, `data-lasso-id` (the box's `link_id`, else the product ID), `data-lasso-lid` (product ID), `data-lasso-name` (the name; empty → the capitalised domain name), `title` (the name), then the `rel` attribute. Values unescaped. | Lasso_URL 214-253; Html_Helper 606-612 |
| R39 | **rel / target:** primary `nofollow` when NoFollow, `noopener` added when New Window, `sponsored` added when Sponsored; target `_blank` / `_self`. Secondary: `nofollow` when NoFollow 2, `noopener` when New Window 2, `sponsored` only when Sponsored **and** NoFollow 2. A product's flag counts only when its meta is `1` (unset is off; the site's defaults apply to the demo product only). | ALL 159-164, 294-314, 442-458 |
| R40 | **Hrefs:** the public link (Amazon → the destination; not Amazon with cloaking off → the destination; else the cloaked permalink; kept affiliate and import-platform URLs as stored), the Amazon tag added; the title and image use the box's `title_url` or the public link; button 2 the second URL with the Amazon tag. The second button is dropped when its URL is the current post (`url_to_postid`); with an empty text it prints without `href` and `target`. | ALL 460-482, 500, 546; single.php 197-203, 226-229; Lasso_URL 262-287 |
| R41 | **Button texts per type:** when a product's text equals the site default, the Single bucket's label (`display_colors.single.primary_button_text` / `secondary_button_text`) replaces it. | single.php 195; Helper 4123-4139 |

### 3.6 CSS, scripts and where they load

| # | Rule | Source |
|---|---|---|
| R42 | **Stylesheets:** `lasso-live.min.css` (41 KB: container, elements, `.mobile`, tooltip, stars, fields, copy button, SiteStripe, the 8 themes and their phone rules at 767.98 px), `lasso-table-frontend.min.css` (dependency `lasso-live`), and in advanced rendering `lasso-display-v2-frontend.css` (246 KB, dependency `lasso-live`); versions are file times. | Helper 5129-5149, 5360-5369 |
| R43 | **`<head>` style block** (`wp_head`): `<style type="text/css">:root{--lasso-main…--lasso-cons (the Single bucket); in advanced rendering --lasso-density-* and --lasso-layout-columns}</style>` followed by the Custom CSS (`custom_css_default`), unescaped but stored through `format_css`'s character allowlist (no `<`). The same block prints on `admin_head`. | class-hook.php 59, 659-669; Helper 4337-4359, 4468-4480, 6023-6030 |
| R44 | **Not built (decision B9, 2026-11-14: brag mode left out).** **Brag:** shown when (Brag mode on and a brag URL set) or **(C)** 48 hours after `lasso_end_date` (Lasso's licence); `<div class="lasso-single-brag"><a class="lasso-brag" href="<url>?utm_source=brag" target="_blank" rel="nofollow noindex"><img src="…/lasso-icon-brag.png" {lazy} alt="Lasso Brag" width="30" height="30"></a></div>`, built once per request. The box's `brag` attribute is read but has no effect on Single. | layouts; Helper 6434-6450; Html_Helper 664-690; Shortcode 268, 427-428 |
| R45 | **Front-end assets** (`wp_enqueue_scripts`): Smart CSS Loading off → R42 on every front-end page; on → only when the page needs it (R46); `lasso-copy-clipboard.min.js` (footer) **in both cases**. | class-hook.php 641-652 |
| R46 | **"Needs the CSS"** (Smart CSS Loading on): non-singular views always; a singular view when its link locations hold a Lasso display or a competitor display, or its content holds `[lasso`, `affiliate-plugin/lasso`, `lasso-display`, `lasso-display-table`, `lasso-table` or `type="table"`, or its Elementor data holds `lasso_shortcode` / `[lasso`. When not, the `<head>` block (R43) is skipped too. Label "Smart CSS Loading", help "Skip display CSS on pages without Lasso displays or tables to improve page speed. Purge page cache after enabling.", toggle "Enable Smart CSS Loading" (Settings → General). | Helper 5227-5348; class-hook.php 659-669; general.php 236-256 |
| R47 | **Late safety net:** every box render enqueues the stylesheets (printed in the footer when late) and, when the `<head>` block was skipped, adds the `:root` block, the Custom CSS and in advanced rendering the WP-theme colour overrides (`article .lasso-display.lasso-ds-v2-frontend.<theme>` …, `!important`) inline on `lasso-live`; once per request. | Shortcode 321-324; Helper 5184-5218, 4405-4440 |
| R48 | **Phone-layout script** (advanced rendering only): an inline footer script; below 767.98 px it copies every `data-layout-m-<k>` onto `data-layout-<k>` (orientation, image-position, align, button-align, cta-width, image-fit), keeping the desktop values in `data-layout-d-<k>`, and restores them above; runs on load, DOMContentLoaded and media changes. Without JavaScript the desktop layout stays. | Helper 5151-5175 |
| R49 | **Font Awesome config** on every page's `<head>`: `window.FontAwesomeConfig = { autoReplaceSvg: <fontawesome_js_svg == 1> }` (General setting). | class-hook.php 671-680 |
| R50 | **AMP:** with an AMP plugin, `amp_post_template_css` prints the `:root` block, the Custom CSS and `lasso-amp.css` (relative URLs rewritten) once. **Jetpack:** `jetpack_photon_skip_for_url` returns true (original images). | Shortcode 208-219, 1204-1213; Helper 4485-4506 |
| R51 | **Body class** `lasso-v<version>` on the front end. | class-hook.php 1340-1344 |
| R52 | **Admin:** `lasso-live.min.css` on every admin page; `lasso-display-v2-frontend.css` on the product page in advanced rendering. | class-hook.php 689-744 |

### 3.7 The Display settings engine on the box

| # | Rule | Source |
|---|---|---|
| R53 | **Advanced rendering is on** when a saved Display value differs from Lasso's defaults: a density preset other than balanced/default/custom or any value off balanced, columns ≠ 3, any layout key or a phone override, any element toggle (the price element skipped when `display_elements` was never saved), or a Grid/List colour bucket that differs from Single (colours compared case- and spelling-blind); a preview forces it on; the filter `lasso_display_v2_frontend_enabled` decides last. | Helper 2839-3016 |
| R54 | **Wrapper** (advanced): class `lasso-ds-v2-frontend`; attributes `data-layout-orientation`, `-image-position`, `-align`, `-button-align`, `-cta-width`, `-mobile-overflow`, `-image-fit`, `data-density-title-lines`, `data-density-desc-lines`, `data-layout-m-*` (the six phone keys), `data-v2-hide-<element>="1"` per hidden element, `data-v2-hide-disclosure`; `style` with `--lasso-main`, `-title`, `-button`, `-secondary-button`, `-button-text`, `-background`, `-pros`, `-cons` (the Single bucket) and `--lasso-density-preset`, `-gap`, `-padding`, `-space-between`, `-title-size`, `-title-lines`, `-desc-size`, `-desc-lines`, `-price-size`, `-button-size`, `-image-height` (px), `--lasso-layout-columns`. | Helper 3197-3226, 3356-3431, 4301-4329 |
| R55 | **Layout values** come canonicalised (defaults, legacy flat layout, image position coerced to the orientation, Grid pinned stacked) — F16 B2, edge 9-11. | Helper 3520-3771 |
| R56 | **Phone layout** = the desktop keys with buttons centred, then the saved phone overrides, image position coerced; printed as `data-layout-m-*`. | Helper 3671-3755 |
| R57 | **Layout CSS:** `lasso-display-v2-frontend.css` keys every rule on the wrapper's `data-layout-*` attributes and the theme class (button alignment per 6-box / 3-box / 2-box family, stacked spacing, image order for both boxes, full-width CTAs, the badge offset, phone rules at 767.98 px). | v2 CSS 162-1121, 1308-1457, 2166-3169 |
| R58 | **Density:** a named preset prints its own map (not clamped), else the saved values over balanced; every value becomes a property (R54). | Helper 3438-3513 |
| R59 | **Line clamps:** `data-density-title-lines` / `-desc-lines` other than 0 clamp the title / description with `-webkit-line-clamp: var(--lasso-density-*-lines)`. | v2 CSS 126-150 |
| R60 | **Element visibility:** the Single element map (`badge`, `image`, `title`, `price`, `prime`, `desc`, `proscons`, `primary`, `secondary`, `timestamp`) plus the box's hides: `badge="hide"`, `title="hide"`, `description="hide"`, `price="hide"`, `fields`/`field="hide"`, `show_pros_cons` false, `image_url="hide"`, `primary_text="hide"`, `secondary_text="hide"`. | Helper 3092-3133 |
| R61 | **Hiding is CSS:** the markup is printed and `display: none !important` hides it (`.lasso-badge:not(.lasso-v2-link-badge-override)`, `.lasso-image`, the title, `.lasso-description`, `.lasso-fields` and pros/cons, the buttons, `.lasso-date`, `.lasso-disclosure`, the price value, `.lasso-amazon-prime`, the whole price block when both price and Prime hide; 6-box boxes collapse). **Exceptions:** the title is not printed at all when hidden; the Primary Rating stars are not hidden by Pros & cons. | single.php 295-297; v2 CSS 1090-1121, 1965-2120 |
| R62 | **A product badge beats a hidden Badge element:** the badge prints with `lasso-v2-link-badge-override`, which the hide rule skips. | Helper 3339-3345; v2 CSS 1090-1091 |
| R63 | **Description and fields gates (advanced):** `$is_show_description` follows the Description element; fields are always printed (hidden by CSS, #5667). | Shortcode 378-397 |
| R64 | **Classic rendering:** no v2 wrapper, attributes or v2 stylesheet; colours only through `:root`; elements shown by their own data (R14-R29). | single.php; Helper 3197-3210, 5143-5146 |

### 3.8 Per-box overrides (box attributes that reach Single)

| # | Rule | Source |
|---|---|---|
| R65 | `theme` (R3). | single.php 52-55 |
| R66 | `badge`: `hide` empties it; another value replaces it. | single.php 61-65 |
| R67 | `description`: `hide` empties it; another value replaces it **and forces the description on**; `lasso-br-code` → `<br>`. | single.php 67-72; Shortcode 258-259 |
| R68 | `title`: `hide` empties the name (no title, R12); another value replaces it (also in `data-lasso-name`, `title`, `alt`). | single.php 74-82 |
| R69 | `image_alt` (default: the name); `image_url` replaces the image (`hide` hides it in advanced rendering, R60). | single.php 80-82, 186-191, 201 |
| R70 | `rating`: `hide` empties the Primary Rating; a value sets it, creating one when the product has none. | single.php 84-91 |
| R71 | `pros` / `cons` (`-br-` → new line) replace or add the Pros / Cons field; `pros_label` / `cons_label` rename it (alone they only rename). | single.php 93-151; Shortcode 280-285 |
| R72 | `fields="hide"` or `field="hide"` removes every custom field (not the rating); `fields="demo"` with `show_pros_cons` true shows the demo Pros ("Will make you more effective." / "Quick read and highly actionable.") and Cons ("You have to be open minded."), false none. | single.php 153-175 |
| R73 | `disclosure_text`: replaces the text and forces the disclosure on. | single.php 177-180 |
| R74 | `basis_price`: the discount becomes `<strike><basis_price></strike>` (printed only per R21). | single.php 182-184 |
| R75 | `primary_url` (button 1 and, through the public link, the title and image), `primary_text`, `secondary_url`, `secondary_text`. | single.php 197-200 |
| R76 | `title_url`: the title and image link. | single.php 202 |
| R77 | `price`: `hide` hides, `show` shows, another value replaces the price and shows it. | single.php 206-214 |
| R78 | `prime`: any value shows Prime. | single.php 216-219 |
| R79 | `title_type` (R12). | single.php 221-224 |
| R80 | `style="mobile"` / `"desktop"` adds that class; a box inside a text widget (`origin="widget"`, added by Lasso's widget filter) is `mobile`. The `.mobile` CSS draws the phone layout at any width. | single.php 248-261; Shortcode 296, 1788-1804; lasso-live `.mobile` rules |
| R81 | `anchor_id` (R9), `link_id` (R9, R38). | Shortcode 243, 247, 372-376 |
| R82 | `tracking_id`: replaces the Amazon tag on the public link and the second URL. | Shortcode 434-440 |
| R83 | `sitestripe`, `sitestripe_style` (R13). | Shortcode 312-313 |
| R84 | `show_pros_cons` false hides Pros & cons (advanced, R60). | Helper 3119-3121 |
| R85 | `demo="true"` renders the demo product (R2) and, in the admin, forces every element visible. | Shortcode 266, 326-339, 499 |
| R86 | `for_url_details="true"`: the product page preview (R26; never the AAWP plain-link shortcut). | Shortcode 297, 487 |
| R87 | `ga` and `brag` are read and unused by Single; `title_url`'s computed `title_target` is unused (the title takes the product's target). | Shortcode 252, 268; single.php 203 |
| R88 | `slug` (resolves the product, before `id`), repoint resolution, `amazon_url`, `geni_url` choose the product (the shortcode sweep's rules). | Shortcode 344-359, 415-425 |
| R89 | Lasso's attribute catalogue for Single (the post editor's customise panel): title, title_url, title_type, description, badge, brag, price, field, rating, theme ("Supported theme values are: Cactus, Cutter, Lab, Llama, Money, Splash"), primary_url, primary_text, secondary_url, secondary_text, image_url, disclosure_text, anchor_id, with their help texts. | lasso-constant.php 387-474 |

### 3.9 Structured data

| # | Rule | Source |
|---|---|---|
| R90 | `wp_head`: the first `[lasso]` in the post's content (block JSON copies with `u0022` skipped) that has an `id` and `schema_review="enable"` or `schema_pros_cons="enable"` produces one `<script type="application/ld+json" class="lasso-schema-markup-output">` (`JSON_UNESCAPED_SLASHES`): `{"@context":"http://schema.org/","@type":"Product","name","image":{ImageObject url}}`. | class-hook.php 1367-1460, 1534-1539 |
| R91 | **Review schema:** `offers` {`@type` Offer, `price` (`schema_price` or the product price, `$` removed, two decimals), `priceCurrency` (`schema_price_currency` or the product's currency, USD default), `availability` InStock}; `review` {`@type` Review, `author` Person = the post author's display name, `reviewRating` {ratingValue} when the Primary Rating > 0}. | class-hook.php 1420-1507 |
| R92 | **Pro/Con schema:** `review.positiveNotes` / `negativeNotes` ItemLists (`numberOfItems`, ListItems with position and name) from the Pros / Cons field lines. | class-hook.php 1430-1532; Helper 6468-6505 |
| R93 | The catalogue also lists `schema_review_author`, `schema_rating`, `schema_pros`, `schema_cons`, which the output does not read; notice "Only allow one Lasso shortcode set of Review and Pro/Con schema per post and just supports for Single Displays." | lasso-constant.php 707-760 |

### 3.10 The two admin previews

| # | Rule | Source |
|---|---|---|
| R94 | **Settings → Display preview:** `refresh_display(type, theme)` posts `lasso_get_display_html_in_url_details` (nonce `lasso_ajax` as `nonce` or `security`; `edit_posts`; `edit_post` for a real product; errors `Invalid nonce.` 403, `Unauthorized.` 403, `Invalid Lasso post.` 400) with `display_type`, `width`, `number_of_column`, `custom_width`, `items`, `preview_style` (desktop/mobile), `display_advanced_customization` and the unsaved `display_elements`, `display_layout`, `display_density`, `display_colors` JSON. It redraws on type, theme, breakpoint, column and layout changes. (`lasso_get_display_html` is not this preview: it returns the post editor's display modal.) | settings.js 1267-1300, 1446-1530, 3550-3674; settings-display/class-ajax.php 31-108, 200-238; pages/class-ajax.php 1721-1744 |
| R95 | **The sample:** item `lasso_id -1`, title "Essentialism: The Disciplined Pursuit of Less", image `displays/essentialism.jpg`, price "$15.99", Prime, the description "The Way of the Essentialist isn't about getting more done in less time. …", badge "Our Pick", basis price "$18.99"; Grid and List add "Atomic Habits …" and "Effortless …" (badge hidden); a Grid preview shows at most the column count (max 3). The server builds `[lasso theme title price prime description image_url badge type show_pros_cons="true" basis_price style secondary_url="https://getlasso.co" fields="demo" id="0" demo="true" ga="false"]` (Grid: `show_pros_cons="false"`). | settings.js 3558-3607; settings-display/class-ajax.php 117-197 |
| R96 | **The overlay:** the unsaved settings apply for that request only, advanced rendering forced on, and every element drawn (wrapper attributes rebuilt with all visible). | Helper 2758-2828; Shortcode 319-339 |
| R97 | **Response** `{status: 1, html, shortcode, additional_info: {is_amazon_product, is_extend_product, is_amazon_page, image_src, price, name, public_link}}`. | settings-display/class-ajax.php 81-107 |
| R98 | **After drawing:** the links lose `href` (title, image, both buttons); button 2 is shown; the typed button texts, disclosure text, disclosure visibility, Link from Display Title (an `h3.lasso-title` when off), brag icon and colour/density properties apply live; the element toggles become `data-v2-hide-*` on the preview (the price block shown only when a shown price or a shown Prime remains). | settings.js 2706-2780, 1842-1880, 3633-3662 |
| R99 | **Loading state:** a spinner in `.image_loading`, the frame emptied; a failure shows the error screen. | settings.js 3627-3673 |
| R100 | **Product page preview, first draw:** the server prints `[lasso id="<id>" ga="false" for_url_details="true"]` (retried with the site's Single theme when empty); `#demo_display_box` has `lasso-url-details-v2-preview` in advanced rendering. | url-details.php 84-114, 300-312 |
| R101 | **Theme change:** Lasso first **saves the whole form** (`get_payload_to_save_url`), then posts `lasso_get_display_html_in_url_details` with `lasso_id`, `theme` (the select, lower-cased) and `default_theme`; the server prints `[lasso theme="…" price style id ga="false" for_url_details="true"]`; the answer redraws the box, updates the form from `additional_info`, re-applies the unsaved texts and restores the image editor. | url-details.php 750-882; settings-display/class-ajax.php 137-192 |
| R102 | **Live typing:** name → `.lasso-title`; description (Quill) → `.lasso-description` (added after `.clear` when missing); primary text → button 1 (the placeholder when empty); second URL/text → button 2 added (6-box in box-4, else after button 1), changed or removed; badge → `.lasso-badge` added, changed or removed; price and Show Price → the price block; disclosure text and Show Disclosure → the text and an inline `display: none !important`. | url-details.php 884-887, 1145-1186, 1487-1585, 1639-1644 |
| R103 | **The image** in the preview is the image editor ("Update Image" on hover) and opens the media library; links in the image lose `href`/`target`. | url-details.php 314-325, 748-749, 832-836 |

### 3.11 Lasso cloud and other boundaries

| # | Rule | Source |
|---|---|---|
| R104 | **C:** Lasso's tracking snippet (`js.codedrink.com`, the local snippet, `lasso.to`) reads `data-lasso-*` and adds `data-lasso-link` at runtime (seen in every captured box); the brag licence rule (R44); the comment's and the demo's `getlasso.co` links. | class-hook.php 1633-1797; variants/*.html |

## 4. Decisions

Settled by Claude from Lasso and the records (none drops anything Lasso does or reverses a user decision):

- **D1 One engine.** `Application\Box\BoxBuilder` turns a product, the Display settings (F16) and the box's overrides into
  a `BoxView`; `Interface\Front\BoxHtml` prints it with one template per layout (`box-3.php`, `box-6.php`, `box-2.php`,
  `box-sitestripe.php`) and shared element partials. The block, Affibloom's shortcode (18S), the `[lasso]` handler
  (Task 27), the two previews and the group displays all call it.
- **D2 Lasso's render order and rules exactly** (R3-R41, R53-R88), computed without side effects: Lasso's write of
  `show_description = 1` during a render (R22) is replaced by the same decision computed at render time, so nothing is
  written when a page is viewed.
- **D3 Link attributes as Lasso prints them** (R38-R40), including the `data-lasso-*` names and `data-lasso-button`
  (F29 B4), so the markup, any site script and the click tracking (F12/C3) see what they see today. `data-lasso-id` is
  the box's link id when one is known (migrated `[lasso link_id]`), else the product ID. `data-lasso-link` is Lasso's
  cloud (R104) and is not printed. Every value is escaped (Lasso prints several raw); the visible text is the same.
- **D4 Escaping everywhere:** the name, alt, image URL, field names and values, price (through Lasso's price allowlist,
  F1 S3), title tag (only `h1`-`h6`, `span`, `p`, `div`, `strong`; anything else is ignored) are escaped or allowlisted.
  Lasso prints them raw (R11, R12, R15, R33); only editors set them, and normal text looks the same.
- **D5 Advanced rendering:** R53 exactly (`Domain\Display\DisplaySettings::is_customised()`, F16 B1), with the filter
  `affibloom_display_advanced_enabled` (F16 B13). The phone script (R48) is a small file, `assets/front/box-layout.js`,
  loaded only in advanced rendering.
- **D6 Timestamp:** shown where Lasso shows it (R27, R29). Its value is the product's last Amazon update, which needs
  Amazon data (C1): until credentials exist an Amazon product has no update time and the date stays empty, as Lasso's
  does for a product it never fetched. The live site hides timestamps, so nothing visible changes.
- **D7 Prime:** the box shows Prime when the site's "Show Prime Logo In Displays" (`show_prime`) is on and the product's
  Prime flag is on. To match R20 at cutover the **migration sets the flag for every Amazon product** (Lasso shows Prime
  for all of them, 242 of 250 live boxes); the per-product switch (Affibloom's, interface spec) lets an editor turn it
  off. Correction for Task 26 (section 5).
- **D8 Brag** (R44; not built, decision B9: brag mode is left out, no brag icon, 22D dropped) without the licence rule (C): Brag mode on and a brag URL set (F16, 10E's `brag_url`). The icon and
  its alt text: "Needs the user" N3.
- **D9 Wording:** the comment prints `<!-- PRODUCT BOX -->`; the preview's sample secondary link is `#`; the brag alt
  follows N3; the tooltip and every other reader-facing text is Lasso's.
- **D10 Baselines for every theme.** The live site uses Lab only, so Lasso's look for the other 7 themes, the layout,
  density and element states, classic mode, SiteStripe and the Copy to Clipboard field is captured from Lasso itself on
  staging (Lasso active there) into `tests/fixtures/baselines/themes/`, before the tasks that build them (18D). Every
  reader-facing task passes at `maxDiffPixelRatio` 0.01 against those files (decision 9).
- **D11 The previews** render through REST routes that call the same `BoxHtml` with the unsaved values as data (no
  global overlay): `POST /affibloom/v1/display-preview` (10D) and `POST /affibloom/v1/products/{id}/preview` (14E). A
  theme change on the product page does not save (F1 D2).
- **D12 Smart CSS Loading.** The setting and both modes are built (R45-R47); Affibloom detects a box by the block, its
  own shortcode and `[lasso` in the queried post, its usage index (Task 35) and Elementor data, and always keeps the late
  enqueue. Default: "Needs the user" N2.
- **D13 Font Awesome config, AMP, Jetpack, body class** (R49-R51) are kept as Lasso does them; the body class is
  `affibloom-v<version>`, the AMP stylesheet is Affibloom's copy.
- **D14 Default thumbnail:** Affibloom ships its own copy of Lasso's no-thumbnail picture; the setting today points into
  Lasso's plugin folder, which disappears when Lasso is deleted.
- **D15 Grid/List items** reuse the element partials; their wrappers and layouts belong to the group displays.

**Needs the user**

- **N1 Markup and stylesheet: Lasso's class names and Lasso's CSS, not ADR-6's.** ADR-6 (decided 2026-11-12 under the
  user's delegation, for one theme) chose `affibloom-` class names and a new stylesheet tuned to the Lab screenshots.
  Under the full-scope rule the box is 8 themes × 3 layouts × the v2 engine (3,169 lines of compatibility rules), and the
  site's Additional CSS shapes today's box through 33 `.lasso-*` rules (box shadow, badge size, description size, Cutter
  rules). With `affibloom-` names those rules stop applying and the look changes at cutover unless the site's CSS is
  rewritten. Lasso is GPL v2+, so its CSS may be ported. **Recommendation (not taken: decision C1, ADR-6 kept):** print Lasso's markup and class names
  (`lasso-container`, `lasso-display`, `lasso-<theme>`, `lasso-box-N`, …) plus one `affibloom-box` class on the
  container, and ship Affibloom's port of Lasso's Single rules from `lasso-live.min.css` and
  `lasso-display-v2-frontend.css`. The F5-F8 specs' `.affibloom-*` selectors then move to Lasso's (the ACs keep their
  meaning; 18A changes the specs). If ADR-6 stays, 18B, 18E-18J and 22E each write their theme's CSS instead of porting
  it, and the site's Additional CSS rules are translated at cutover (Task 41).
  → **Decided 2026-11-14:** C1: ADR-6 is kept, so the recommendation is not taken: the box uses Affibloom's own class names and stylesheet; the site's 33 `.lasso-*` Additional CSS rules are translated at cutover (Task 41).
- **N2 Smart CSS Loading's default.** Lasso's default is off (CSS on every page; this site: off). F5 AC2 (approved
  2026-11-12) wants no CSS without a box. **Recommendation:** build both modes, default **on** in Affibloom, and let the
  migration set it on whatever Lasso stored; off stays available for themes that render boxes outside the content.
  → **Decided 2026-11-14:** C5: Smart CSS Loading on by default; the migration switches it on.
- **N3 The brag icon.** Lasso's brag link shows Lasso's logo (`lasso-icon-brag.png`, alt "Lasso Brag") to earn Lasso
  affiliate commissions. **Recommendation:** keep the mechanism (D8) with a neutral 30 × 30 link icon shipped by
  Affibloom and the alt text "Brag", the URL being the user's; the site has Brag mode off.
  → **Decided 2026-11-14:** B9: brag mode is left out; no brag icon.
- **N4 Baseline capture on staging** (D10, 18D) creates throwaway posts and products with Lasso on staging and changes
  their Display Theme; **Recommendation:** approve one staging session for it (the capture tool deletes what it
  created).
  → **Decided 2026-11-14:** F1: one approved staging session; what the tool creates is deleted after.

## 5. Scope that needs a home

- **Migration (Task 26):** the Prime flag for every Amazon product (D7; today `LassoRecordMapper` uses the cached
  `is_prime`, which would drop Prime from many boxes); `show_description` as Lasso decides it (R22); the per-type
  buttons labels; Smart CSS Loading (N2); `link_id` per converted box (D3).
- **Shortcode surface** (18S, Task 27): the attributes of R65-R88 arrive through Affibloom's shortcode and the `[lasso]`
  handler; `slug`, repoint, `amazon_url`, `geni_url`, `widget` origin, the AAWP description filter
  (`lasso_shortcode_attrs_filter`) and the plain-link shortcut belong to that sweep.
- **Editor (Phase 4, F4):** the block sidebar for every override; the editor's box preview
  (`lasso_get_shortcode_content`, also returning schema data); the schema controls (`lasso_get_gutenberg_schema_data`);
  `lasso_get_display_html` (the editor's display modal).
- **Settings → General:** the Smart CSS Loading and Font Awesome controls (R46, R49) — the front-end rules are 18C's.
- **Amazon data (C1):** price, last update (timestamp), description fallback, image, discount basis.
- **Links and cloaking area:** the public link (R40): cloaked permalinks for non-Amazon products with cloaking on (236
  here) — until it exists the box links to the destination, which differs from Lasso's `href` on those boxes.
- **Clicks (F12, C3):** reading `data-lasso-*` and the button number.
- **Locations area:** the "Single Link Display Preview" modal (`display-preview.php`) draws a location's stored box.
- **Add link modal (F18):** its preview uses the same route as 14E (`url-add.php` 1710).
- **Onboarding** (Lasso's install wizard draws the Display preview, `page=install`): no Affibloom owner found.
- **Group displays** (Phase 3, F23): `type="list"` single items (`list-box.php`), Grid and List wrappers.

## 6. Edge cases

1. A product deleted, drafted or retired: the box prints nothing (R1); a repoint is followed first (Task 18 note).
2. The same product boxed twice with different overrides: each box keeps its own (R2).
3. A badge or button text with `<script>` or other tags: only empty `<span class>` survives (R10).
4. Empty name: no title, `data-lasso-name` takes the domain name (R38, R12).
5. A second URL equal to the current post: no second button (R40); an empty second text: a button without a link.
6. A theme name with other casing (`lab`) or an unknown one (R3).
7. Settings missing or malformed: defaults (F16 AC13); advanced rendering off.
8. `title_type` with an unexpected tag: ignored (D4).
9. `image_url="hide"` in classic rendering: Lasso prints `src="hide"`; Affibloom hides the image instead (D4).
10. `primary_text="hide"` in classic rendering: Lasso prints the word "hide" as the text; Affibloom keeps Lasso's text
    (only advanced rendering hides the button), recorded as a known Lasso quirk.
11. Two boxes on a page: the brag icon and the inline CSS print once (R44, R47).
12. Smart CSS Loading on and a box rendered by a widget: the late enqueue prints the CSS in the footer (R47).
13. A price with Quill HTML: printed through the price allowlist (D4).
14. JavaScript off: the desktop layout stays on phones (R48); the copy button does nothing.
