# F24 Link cloaking, redirects and permalinks: requirements

Status: gates 1-2 written 2026-11-14 from a sweep of Lasso 369 (area 7 of
`affibloom-docs/plans/archive/dashboard-completion-plan.md`). Gate 4 for Tasks CL1-CL11: waiting.

## 1. User stories and acceptance criteria

- As a reader, I open a product's link on the site's own domain (`/<slug>/` or `/<prefix>/<slug>/`) and land on the
  product's destination, so the links already published in posts keep working after Lasso is gone.
- As an editor, I choose a product's permalink and whether its links show the cloaked address or the destination.

- **AC1** `GET /<slug>/` of a published product answers **302** to the product's destination with
  `X-Redirect-By: Affibloom`, `Cache-Control: private, no-store, no-cache, must-revalidate`, and
  `X-Robots-Tag: noindex, nofollow` when the product has NoFollow or Link Cloaking on. On this copy: the five
  cloaked products (`/total-attack-volleyball-serving-machine/`, `/aircat/`,
  `/under-armour-official-store-free-shipping-available-2/`, `/wave-momentum-3-volleyball-shoe-mizuno-usa/`,
  `/mizuno-womens-wave-dimension-volleyball-shoes-mizuno-usa/`) and the retired
  `/mizuno-neo-jump-unisex-volleyball-shoe-for-sale/` answer with Lasso's destinations. (CL1, R15-R21, R24, D2-D6)
- **AC2** A post or page (published or draft) with the same slug wins and is shown as before; an unknown path is the
  site's 404; a trashed product's path is a 404; an Amazon product's slug (`/amzn-…/`) also answers, because Lasso's
  redirect does not read the cloaking flag. (CL1, R16, R17, R21, D3)
- **AC3** With the prefix `go`: `/go/<slug>/` answers, `/<slug>/` and `/other/<slug>/` do not; `/tag/<x>/` and
  `/category/<x>/` of existing terms are never taken; on a site in a subdirectory the subdirectory is ignored. (CL1, R17)
- **AC4** Query strings pass through: `?foo=1` is added unless the destination has `foo`; `utm_*` always replaces the
  destination's; `doing_wp_cron` and empty values are dropped; `{url}` in the destination becomes the referring page
  (without its query), `{url_full}` the same plus the referrer's parameters; `sid=x` with an off-site referrer becomes
  the network's first sub-ID parameter (CJ: `sid`, Impact: `subId1`, …) for a destination of a known network (never
  TradeTracker); `lcid` is removed. (CL2, R22, D7)
- **AC5** A non-Amazon product's page shows **Permalink** ("This slug will be used to cloak the original target URL",
  placeholder "affiliate-name") and the **Link Cloaking** toggle ("The destination of this URL will be redirected from
  a pretty url on your domain. All non-Amazon Affiliate links should have this enabled."); typing turns anything not a
  letter or digit into `-` and trims hyphens, and the line under the heading shows the address it will have,
  prefix included. After Save Changes and a reload the slug and the toggle are stored; the heading's link, the box
  preview's links and the Share kit's **Copy shareable URL** give the cloaked URL when Link Cloaking is on and the
  destination when it is off. An Amazon product has neither row (F1 AC7 unchanged). (CL3, R7-R11, R13, R14, D9-D12)
- **AC6** A slug another product has is saved as `<slug>-2`; a slug a post or page uses is saved with the warning
  "Permalink **<slug>** is being used by **<type>**. …" (wording N7); `shoe-2-2-2` is saved as `shoe` (then made
  unique); an empty Permalink takes the name's slug. (CL3, R4, R5, D11, D12)
- **AC7** Changing a saved product's slug keeps the old one; the old address keeps answering (N1). (CL3, CL10, R6, N1)
- **AC8** A product added from **Add Link** gets the slug of its name (`amzn-` in front for an Amazon product) and the
  General setting **Link Cloaking** as its own flag; later changes of the setting do not reach it. (CL4, R3, R12, D11)
- **AC9** Saving the General tab with a new **Cloaked Link Prefix** changes every cloaked URL the plugin prints at
  once, and the old prefix's addresses keep answering (N2). Switching **Link Cloaking** and choosing **Override All
  Links** sets the flag on every product (Amazon included, where it has no effect); **Only New Links** changes the
  default only. No post is written. (CL5, R30, R31, D15, N2)
- **AC10** On the front end, a cloaked product's box (title, image, buttons) and its text-link shortcode link to the
  cloaked URL; an Amazon product's to its Amazon URL; nothing in `post_content` changes. (CL6, R13, R14, D9)
- **AC11** The Dashboard row's link is the product's public link (cloaked or destination). (CL7, R14)
- **AC12** In the block editor's link search (and the Link block's suggestions) products are found by name and inserted
  with their public link; products are not in the site's search or sitemaps. (CL8, R27, R28, D16, D18)
- **AC13** The classic editor's "Insert/edit link" search does the same. (CL9, R28, D18)
- **AC14** After the migration (Task 26G), every cloaked address found in this copy's content answers with the
  destination Lasso gave it: the four above in posts 5428, 4436 and 4375 (seven links). (CL10, R6, D19)
- **AC15** Pasting the site's own cloaked URL (`https://<site>/aircat/`) into **Add Link** finds that product (the
  duplicate rule) instead of creating one. (CL11, R36)

## 2. Evidence

Read in full: `classes/class-redirect.php` (559), `pages/redirect/class-hook.php` (54), `classes/class-post-type.php`
(556), `pages/post-type/class-hook.php` (170), `admin/assets/js/custom-slug.js` (61), `classes/class-share-kit.php`
(127), `admin/views/modals/override-display.php`. Read in part: `admin/views/dashboard/url-details.php` 180-205,
480-505, 1595-1635, 1735-1925, 2395-2600; `admin/views/dashboard/header.php` 120-340; `admin/views/settings/general.php`
60-140, 220-350, 480-505, 590-700, 780-802; `pages/settings-general/class-ajax.php` 300-520, 1040-1115;
`classes/class-lasso-affiliate-link.php` 120-175, 290-560 (`get_lasso_url`), 763-807 (`is_lasso_url_exist`),
1140-1440 and 1660-1830 (`save_lasso_url`), 1905-1918 (`keep_affiliate_url`), 2537-2785
(`get_lasso_post_id_by_url`, `get_lasso_link_from_old_link`), 3270-3420 (`lasso_insert_post`), 3565-3620;
`classes/class-helper.php` 1300-1379, 1840-1880, 5635-5660, 6550-6580; `classes/class-lasso-db.php` 3965-3995,
5910-5990; `classes/class-lasso-db-script.php` 890-930, 1058-1110; `classes/class-lasso-cron.php` 1645-1700,
2660-2800, 2850-2930, 3025-3045, 6405-6420; `pages/fix/class-hook.php` 40-100, 540-640, 770-800;
`classes/class-affiliates.php` 1-80, 219-327; `files/affiliates.json` (37 networks, 33 with sub-IDs);
`classes/class-setting-enum.php` 90-136; `admin/lasso-constant.php` 370-385, 787; `pages/class-hook.php`
1088-1102, 2045-2098; `classes/background_processing/class-lasso-process-link-database.php` 522-582;
`admin/views/components/url-quick-detail.php` 95-104, 330-345; `admin/views/modals/url-add.php` 1255-1325;
`pages/class-ajax.php` 815-830, 1368-1415, 1721-1741; `admin/assets/js/view-js.php` 2470-2480. Greps over the whole
source for `cloak`, `public_link`, `rewrite_slug`, `restrict_prefix`, `permalink`, `lasso_old_slug`,
`lasso_before_link_redirect`, `lcid`.

**This copy, 2026-11-14 (SELECT only):** `rewrite_slug` = `''` (no prefix), `restrict_prefix` false,
`link_cloaking` true, `keep_original_url` empty, permalinks `/%postname%/`. `lasso-urls` posts: 263 published
(236 with `link_cloaking` 1, 18 with 0, 9 with none), 1 `lasso_delete` (flag 1), 1 trash (flag 1). By destination:
233 Amazon, 32 other. **Rendered cloaked (non-Amazon, flag 1): 5 published** (9999, 10001, 10134, 14772, 14773) plus
the retired 14771; none is a "kept affiliate" domain, none `is_import_platform`. The other 231 published flags of 1
are Amazon, which Lasso's rule ignores. **Cloaked hrefs in content: 7 links to 4 addresses in 3 posts** — post 5428
(`/total-attack-volleyball-serving-machine/` ×2, `/aircat/` ×2), post 4436
(`/under-armour-official-store-free-shipping-available-2/` ×2), post 4375
(`/mizuno-neo-jump-unisex-volleyball-shoe-for-sale/`, the **retired** product 14771, which Lasso still redirects).
Every one is a `lasso-urls` slug; no `/lasso-urls/` paths in content; no post or page shares a slug with a product.
`lasso_old_slug`: 17 rows, 2 with a real old value (10001 `aircat-volleyball-serving-machine` → `aircat`, not in
content). `lasso_revert`: 0 rows. `lasso_url_variants`: 0 rows. 22 posts/pages carry `_wp_old_slug` (WordPress's own
old-slug redirects, which Lasso switches off, R6). Rank Math active, its Redirections module off; Yoast and Pretty
Link absent. Outside Lasso: post 3297 (published) links to `/recommends/adidas-dame-8-all-volleyball/`, a
ThirstyAffiliates address (49 `thirstylink` posts, plugin not installed) — not a Lasso URL (section 5).

## 3. What Lasso does

| # | Rule | Source |
|---|---|---|
| R1 | **The cloaked URL** of a product is `home_url()` + (`/<rewrite_slug>` if set) + `/<post_name>/`, given by the `post_type_link` filter for **published** `lasso-urls` posts only; other statuses get WordPress's default link | class-post-type.php 540-555; post-type/class-hook.php 40 |
| R2 | **Post type:** `public` = not `restrict_prefix`, `publicly_queryable` and `query_var` true, `exclude_from_search` true, `show_in_rest` true; `rewrite` = `{ slug: rewrite_slug, with_front: false }` when a prefix is set (else WordPress's default `lasso-urls`). Rewrite rules are flushed only on activation, plugin update and version change — **not** when the prefix changes; serving does not rely on them (R16-R17) | class-post-type.php 91-130; affiliate-plugin.php 60, 72; pages/class-hook.php 2045-2098 |
| R3 | **A new link's slug** is `sanitize_title( title )`, lower-cased; when the final URL is Amazon the slug gets `amzn-` in front (cut to the column length), except for links from import platforms (geni.us and the like); WordPress makes it unique within the post type (`wp_unique_post_slug`) | class-lasso-affiliate-link.php 1015-1032, 3294-3299, 3579-3594 |
| R4 | **On save** the slug is the posted `permalink`, else the stored slug; a trailing repeat like `-2-2-2` (`/(-\d+)(-\d+)+$/`) is cut back to the base; an empty slug on a published post becomes `wp_unique_post_slug( sanitize_title( title ) )` | 1351-1365, 3294-3299, 3378-3383 |
| R5 | **Slug conflict warning** (update, final URL not Amazon): when any other post (any type but `nav_menu_item`; attachments ignored; not `lasso_delete`) has the slug, the save goes on and the page shows the warning "Permalink **<slug>** (linked to that post's edit screen) is being used by **<post type>**. We updated the permalink to avoid a conflict." The slug is in fact changed only when another `lasso-urls` post has it (R3's uniqueness); against a post or page it is kept, and the post or page wins at R16 | 1692-1694; class-helper.php 1348-1379; url-details.php 1876-1880 |
| R6 | **Slug change** (non-Amazon product, posted slug differs): the old slug is stored in `lasso_old_slug` (only the last), and the posts linking to the product are rescanned and **rewritten** (S9 of F1). Nothing reads `lasso_old_slug` for serving (its only reader, `fix_import_revert_href`, is commented out), and Lasso **removes WordPress's `wp_old_slug_redirect` for the whole site** — so an old address is a 404 | 1798-1816; redirect/class-hook.php 24, 33; fix/class-hook.php 62, 586; class-lasso-db-script.php 898-915 |
| R7 | **Product page, Permalink** (after Name): label **Permalink**, tooltip "This slug will be used to cloak the the original target URL", input `#permalink` with the slug, placeholder "affiliate-name"; hidden when the Primary URL is Amazon; on a URL change (`update_ui`) hidden for an Amazon product or an Amazon search/category page, shown otherwise | url-details.php 193-197, 1604-1624 |
| R8 | **Typing in Permalink** (keyup): the value becomes `trim( value ).replace( /[\W_]+/g, '-' )` without leading or trailing hyphens (the same as `Share_Kit::normalize_slug`), is written back into the field, and the line under the heading reads `site_url + '/' + slug + '/'` — **without the prefix**; its `href` changes only after a save; the Share kit is re-synced | url-details.php 2400-2405, 2582-2592; class-share-kit.php 30-33 |
| R9 | **Save** sends `permalink` inside `settings` and at the top level (plus `samplepermalinknonce`); after a good save the field takes `post.slug`, and the heading link, the preview's title, image and first button take `post.public_link` | url-details.php 1742-1805, 1896-1920 |
| R10 | **Link Cloaking toggle** in the toggles row (beside Detect Opportunities): tooltip "The destination of this URL will be redirected from a pretty url on your domain. All non-Amazon Affiliate links should have this enabled."; hidden for Amazon (R7's rule); checked when the meta is 1; sent as 1/0 | url-details.php 491-497, 1766 |
| R11 | **Saving the flag:** the posted value, else the stored one; forced to 0 when the URL is a lasso.to short link; stored as meta `link_cloaking` 1/0; a change rescans and rewrites the linking posts | 1205-1209, 1233-1238, 1425, 1798-1812 |
| R12 | **A new link's flag** is the General setting `link_cloaking` (default true) through `get_lasso_url( 0 )`, stored at creation; later setting changes reach existing links only through R31. Auto-monetized links are added with 0 ("Does not change the original URL on post content"); a duplicate import-platform link gets the setting's value | 164, 1203-1209, 1293-1299; lasso-constant.php 382; process-auto-monetize.php 63 |
| R13 | **The public link:** Amazon destination → the destination; non-Amazon with the flag off → the destination; non-Amazon with the flag on → the cloaked URL (R1). Then, when the stored URL is "kept" (`keep_affiliate_url`: its domain is in geni.us, primalvid.io, rstyle.me, howl.me, go.skimresources.com, awin1.com, shareasale.com, refer.link, lvnta.com, link.archeraffiliates.com, lasso.link, lasso.to or the `keep_original_url` setting, or it is a known network's link in `affiliates.json`) or comes from an import platform: the destination is the stored URL, and the public link is the cloaked URL only if the flag is on and (the public link is not Amazon **or** the link is from an import platform), else the stored URL. Finally the Amazon URL formatter runs over it. **So an Amazon link is cloaked only in the import-platform case** | class-lasso-affiliate-link.php 460-482, 546, 1905-1918; class-setting-enum.php 111-124 |
| R14 | **Readers of the public link:** the heading's link (shown only when not empty and not `#`); the Share kit's Copy shareable URL (the heading's text, else `site_url/slug/`; disabled when neither); the Dashboard row's link (`link_slug`); displays (single box title and buttons, button display, AAWP fields, field rows); the `[lasso]` text link; the editors' link search (R28); the content scanner, which **writes it into posts**; WP Coupons' discount URL meta; comparison-table mappings; the importer | header.php 195-210; url-details.php 2406-2416; pages/class-ajax.php 823, view-js.php 2474-2479; displays/single.php 197, 202, button.php 32, aawp/fields.php 88; class-lasso-shortcode.php 496, 588; class-lasso-cron.php 2687-2792, 2859-2862, 2905-2913, 5934, 6237; models/class-table-mapping.php 318; class-import.php 130, 233 |
| R15 | **The redirect's hooks:** `template_redirect` priority 5 (`redirect`, and `lasso_template_redirect` for R18); `redirect_canonical` filtered (R24); `wp_old_slug_redirect` removed; Pretty Link's `prli-check-if-slug` filtered (R29); Rank Math's `rank_math/redirection/pre_search` (R25); priority 0 also serves Lasso's own tracking script and beacon (Lasso's cloud) | redirect/class-hook.php 21-37; class-redirect.php 31-166 |
| R16 | **Which paths are looked at:** the request path (`REQUEST_URI`, else `REDIRECT_URL`, query removed); none containing `/favicon.ico` or `/admin`; the path without slashes is the "post name"; **a post or page, published or draft, whose slug equals it wins** (Lasso does nothing) | class-redirect.php 171-183; class-helper.php 1846-1857, 5643-5654; class-lasso-db.php 5972-5985 |
| R17 | **Matching:** the site's subdirectory is removed; a two-part path `tag/<x>` or `category/<x>` of an existing term is never a product; with a prefix a two-part path must start with it, and a one-part path is refused; without a prefix a two-part path is refused; the **last part** is looked up as the `post_name` of a `lasso-urls` post **of any status**. Paths of three or more parts are not refused (`/a/b/<slug>/` matches without a prefix, `/<prefix>/a/<slug>/` with one) | class-helper.php 1313-1340, 6556-6567; class-lasso-db.php 3972-3994 |
| R18 | **Old links from imported plugins** (`lasso_revert.old_uri`, host and path compared without scheme, `www.` and slashes; newest first): a match **301**s to the product's permalink unless that is the same path; and on a 404 an exact `old_uri` match 301s | class-redirect.php 187-203, 312-341; class-lasso-affiliate-link.php 2752-2785 |
| R19 | **Order:** a product whose slug is the path wins over a URL variant with that key (F27); then a variant; else a lookup of the whole URL | class-redirect.php 205-232 |
| R20 | **Not a product** (or a search request): when Yoast Premium's `wpseo-premium-redirects-base` has exactly one redirect whose origin is the path and whose type is 3xx, Lasso performs it (`/<url>/`, that type); otherwise WordPress goes on | class-redirect.php 241-265 |
| R21 | **The response:** destination = the product's `target_url` (Amazon: the monetized URL; kept URLs: the stored URL); `X-Robots-Tag: noindex, nofollow` when NoFollow **or** Link Cloaking is on; query pass-through (R22) outside admin; `lcid` removed; action `lasso_before_link_redirect( post ID, referrer )` (nothing in Lasso listens); `wp_redirect( destination, 302, 'Lasso' )`, or 302 to the home page when the destination is empty; then `Cache-Control: private, no-store, no-cache, must-revalidate`; exit. **No check of the cloaking flag or the post status:** an uncloaked or Amazon product's slug, a draft or a `lasso_delete` product all redirect; a trashed one only at its `__trashed` slug | class-redirect.php 267-297 |
| R22 | **Pass-through:** parameters from the URI's query, `QUERY_STRING` and `$_GET` (first value of arrays; empty values dropped); `doing_wp_cron` skipped; a request parameter fills one the destination lacks, a `utm_*` one always replaces; `sid` with a referrer of another domain, for a destination of a known network (not TradeTracker) with sub-IDs, becomes that network's first sub-ID and is removed, **and a click is posted to Lasso's cloud**; destination values `{url}` / `{url_full}` become the referrer without its query (else the site's domain), `{url_full}` adds the referrer's parameters; the URL is rebuilt with `urldecode( http_build_query() )`, fragment kept; when nothing changed the destination is returned as it was; on an exception, too. `lcid` (put on links by Lasso's tracking script) is appended to a network sub-ID | class-redirect.php 384-522; class-affiliates.php 18-80, 219-249, 298-327; files/affiliates.json |
| R23 | **Lasso's cloud in this path:** `track_click()` posts to `https://lasso.link/events/tracking` (`LASSO_LINK`); `lcid` comes from Lasso's `connect-snippet.min.js`; data sync sends `public_link` and `link_cloaking` | class-affiliates.php 219-249; lasso-constant.php 787; data_sync/class-lasso-data-sync-lasso-links.php 315, 331 |
| R24 | **Canonical guessing:** on a 404, a guessed redirect whose path is a product's is cancelled | class-redirect.php 531-544 |
| R25 | **Rank Math Redirections:** for a product's path Lasso's redirect runs inside Rank Math's pre-search | class-redirect.php 552-558 |
| R26 | **Main query:** `pre_get_posts` turns the main query for a product's (or variant's) path into that `lasso-urls` post, so WordPress does not 404 first; products are left off author archives | class-post-type.php 423-533 |
| R27 | **Sitemaps and caches:** Yoast and Rank Math sitemaps exclude the post type and `lasso-cat`; WP Rocket's critical CSS excludes both | post-type/class-hook.php 47-55, 69-141 |
| R28 | **Editor link search:** the classic dialog's `wp_link_query` and Gutenberg's `/wp/v2/search` (type post) return Lasso links with the public link as their URL | class-post-type.php 29-67; post-type/class-hook.php 28-30 |
| R29 | **Pretty Link:** its slug check ignores posts that are not Pretty Link's own (links imported from it) | redirect/class-hook.php 32, 47-53 |
| R30 | **General settings:** Cloaked Link Prefix (F9 R16-R18; keyup lower-case and `[\W_]+` → `-`; stored `sanitize_title( value, '' )`; a change starts a background job that **rewrites the cloaked links in every published and draft post**); Link Cloaking default toggle ("Toggle on to brand your link with your domain. Toggle off to show readers your Primary Destination URL.", default on); Restrict Prefix (support view only; `public` false). Defaults: `rewrite_slug` '', `restrict_prefix` false, `link_cloaking` true | general.php 64, 75, 133, 227-237, 334-346, 494-500, 786-789; settings-general/class-ajax.php 414-422; process-link-database.php 522-582; lasso-constant.php 372-382 |
| R31 | **Override dialog** on switching Link Cloaking: "Update Link Settings" / "Lasso will **enable|disable** the option **Link Cloaking**" / **Only New Links** (closes) / **Override All Links** → `lasso_override_display_option` (nonce `lasso_settings_save`; `manage_options` or Lasso's permission level, else 403 "You do not have permission to override display options."): the meta is updated on every `lasso-urls` post and inserted where missing — **Amazon products included** — and no post is rescanned | general.php 630-647; override-display.php; settings-general/class-ajax.php 1042-1110 |
| R32 | **The quick-detail panel** (Opportunities' and the editor's display modal: `lasso_get_link_quick_detail`) and the Add Link modal carry the same Permalink and Link Cloaking controls and the same save | url-quick-detail.php 99-102, 336-342; url-add.php 1258-1324; pages/class-ajax.php 1368-1415, 1721-1741 |
| R33 | **lasso.to short links** never get cloaked (R11), and a database fix turns the flag off on published links whose URL is `lasso.to/amazon/` or `/a/` | class-lasso-db-script.php 1058-1100 |
| R34 | **Content fix without a prefix:** `/lasso-urls/` is removed from links in post content during scans | class-lasso-cron.php 6412-6416; fix/class-hook.php 73-75, 778-786 |
| R35 | **Uninstall "Remove all Lasso link cloaking."** rewrites content back to destinations | remove-attributes-confirm.php 30-33; general.php 598-625 |
| R36 | **Duplicate detection** of a pasted own-domain URL: its path's last part, under R17's prefix rule, is found as a product's slug (also as a whole-path lookup), so pasting a cloaked URL finds its product | class-lasso-affiliate-link.php 763-807, 2591-2648 |
| R37 | `custom-slug.js` (enqueued on the add-link page) targets `#custom-url`, which no view has: dead code | custom-slug.js; pages/class-hook.php 1094-1102 |

## 4. Decisions

Settled from Lasso and the records:

- **D1 Storage.** The slug is the `post_name` of `affibloom_product`; the flag is meta `_affibloom_cloak` (`'1'`/`'0'`,
  missing = off, as Lasso's `intval`); settings `cloak_prefix` (Lasso's `rewrite_slug`), `link_cloaking`,
  `restrict_prefix` (keys and cards are F9's: 9B, 9G, 9L). Old addresses (N1, N2): meta `_affibloom_old_slugs`
  (list) and option `affibloom_cloak_prefix_history` (list).
- **D2 Served without a public post type.** `affibloom_product` stays private. A Module `CloakedLinkRedirect` on
  `template_redirect` priority 5 resolves the path itself (R16-R17) and redirects before any template; no rewrite
  rule, query var or flush is needed (Lasso's serving does not use its rules either, R2), so a new prefix works at
  once. `redirect_canonical` is filtered as R24; Rank Math's pre-search as R25. R26's main-query swap is not needed:
  the redirect exits before WordPress's 404 template.
- **D3 Which products answer:** published and retired (`affibloom_retired`, Lasso's `lasso_delete`: post 4375 links to
  one). Trash does not (its slug carries `__trashed`, so its published path already fails in Lasso); Affibloom has no
  drafts (Lasso always publishes).
- **D4 Matching** is R16-R17 exactly for `/<slug>/` and `/<prefix>/<slug>/`, with the post/page, tag and category
  guards and the subdirectory rule (N6 for longer paths).
- **D5 The response** is R21: 302, `X-Redirect-By: Affibloom`, the robots header, the cache header after the redirect,
  the home page for an empty destination, and the action `affibloom_before_link_redirect( product ID, referrer )`.
- **D6 The destination** is the one the product's box links to (F2's Amazon formatting with the tag rule; kept URLs as
  stored), computed by the same domain service as the public link (D9).
- **D7 Pass-through** is R22 without its cloud call: the sub-ID names come from a plugin data file holding the 33
  networks' parameter names from Lasso's `affiliates.json` (local data); `lcid` is removed and never appended (it only
  exists with Lasso's script).
- **D8 Old links from imported plugins** (R18) belong to the import feature (section 5); this copy has none.
- **D9 The public link** is one domain rule, `PublicLink` (R13 in full, including the import-platform case and
  `keep_original_url`), used by every reader in R14 that Affibloom has: product page, Share kit, Dashboard, displays,
  text links, editor link search, F21's render-time links.
- **D10 The Permalink field and toggle** are Lasso's (labels, help, placeholder, hidden for Amazon, R8's typing rule);
  the help text is printed without the doubled "the" (progress, deviation list). The line under the heading includes
  the prefix (Lasso's preview omits it and is wrong whenever a prefix is set).
- **D11 Slug rules:** `sanitize_title`; R4's repeat cleanup; unique among products (`wp_unique_post_slug`); `amzn-` for
  a new Amazon product (R3); an Amazon product's slug is never edited (no field).
- **D12 An empty Permalink** gives the name's slug (R4), not an error.
- **D13 No post is written** (the standing rule): a slug or flag change, a prefix change and Override All Links write
  only Affibloom's data; F21's render-time pass (Task 35H) prints the current public link for indexed links, and the
  old addresses keep answering (N1-N3).
- **D14 One partial** for Permalink and Link Cloaking, used by the product page (CL3) and later by the quick-detail
  panel's owner (section 5).
- **D15 Override All Links** for Link Cloaking writes `_affibloom_cloak` on every product, Amazon included (Lasso's
  result; the rule ignores it there), through F9's dialog (9D).
- **D16 Sitemaps and search:** a private post type is in no WordPress, Rank Math or Yoast sitemap and in no search, so
  R27's exclusions have nothing to do; the redirect's robots header stays.
- **D17 Not copied, same visible result:** R20 (Lasso performs Yoast Premium's redirects only because its own hook
  runs first; Affibloom returns for any non-product path, so Yoast's runs), R29 (Pretty Link's slug conflict arises
  only for links imported from it), R37 (dead code), R34 (no `/lasso-urls/` addresses exist here; a private type has
  no such path).
- **D18 Editor link search:** products join Gutenberg's search through a REST search handler and the classic dialog
  through `wp_link_query`, with the public link as URL (R28); the post type stays private.
- **D19 Migration (Task 26G, F10)** copies slugs exactly (no re-prefixing; collisions reported), flags exactly (231
  Amazon `1`s included; missing → 0), `rewrite_slug` → `cloak_prefix`, `link_cloaking`, `restrict_prefix`, and the
  real `lasso_old_slug` values (≠ current) → `_affibloom_old_slugs`. **It never turns a flag on.**
- **D20 lasso.to:** a stored lasso.to URL never gets the flag (R11, R33); the short-link service itself is Lasso's cloud.

**Needs the user:**

- **N1 Old slugs keep answering.** Lasso 404s an old slug (R6) because it rewrites the posts; Affibloom does not
  rewrite posts, so a renamed slug would break published links. Recommendation: an old slug answers exactly as the
  current one (302 to the destination); 2 real old slugs here.
- **N2 Prefix change.** Lasso rewrites every post (R30); Affibloom cannot. Recommendation: old prefixes stay in a
  history and keep answering; F21's render-time pass prints the new address.
- **N3 Flag and slug changes reach stored content only at render time** (F21 35H), not in places F21 does not filter
  (other plugins' stored HTML); those links keep the stored address, which still answers (R21 ignores the flag).
  Recommendation: accept.
- **N4 `wp_old_slug_redirect`.** Lasso switches WordPress's old-slug redirects off for the whole site (22 posts and
  pages have old slugs). Recommendation: do not copy that; WordPress's own redirects come back at cutover.
- **N5 Lasso's cloud in the redirect** (R23: the `sid` click post, `lcid`, data sync): left out (C — never); the local
  `sid` → sub-ID rewrite is kept.
- **N6 Longer paths** (`/a/b/<slug>/`, R17) are not matched. Recommendation: accept; no such link exists here.
- **N7 The conflict warning's wording.** Lasso says "We updated the permalink to avoid a conflict." when it did not
  (R5). Recommendation: "Permalink **<slug>** is being used by **<type>**. This product's link will not open until
  you change it."

## 5. Scope that needs a home

- **Old links of imported plugins** (`lasso_revert`, R18) and Pretty Link's filter (R29): the Import feature (0 rows).
- **The quick-detail panel** (R32) with Permalink and Link Cloaking: its owner (Opportunities F22; the editor's modal,
  F4) reuses CL3's partial.
- **Content rewrites** (R6, R11, R30, R34, R35): F21's render-time pass (35H) gives the visible result; the uninstall
  option (R35) belongs to the uninstall/settings feature (F9 R64).
- **Clicks on the redirect:** F12 can count at `affibloom_before_link_redirect` (Lasso counts nothing locally).
- **ThirstyAffiliates leftovers:** `/recommends/…` links in post 3297 (published) and six hidden or draft posts point
  to a plugin that is not installed; no Lasso data covers them. The broken-links feature (F11) or the Import feature.
- **Lasso's tracking script and beacon** (R15, priority 0): Lasso's cloud, never built.

## 6. Edge cases

1. A slug equal to a page's: the page is shown (R16); the editor saw the warning (N7).
2. A product's slug equal to a variant key: the product wins (R19).
3. Two products cannot share a slug (`wp_unique_post_slug`); a migrated collision is reported, not merged.
4. The destination empty or invalid: 302 to the home page (R21).
5. A HEAD request is answered like GET; a POST to a product path is redirected as well (Lasso does not look at the
   method).
6. Query values with arrays: the first value only; empty values dropped (R22).
7. A prefix equal to an existing page's slug: `/<prefix>/<slug>/` is still a product path (the post/page guard
   compares the whole path).
8. Cache plugins: the redirect sends `no-store`; nothing is cached by Affibloom.
9. A product retired by a repoint answers with its own stored destination (Lasso's result for 14771).
10. A request in admin (`is_admin()`) is never redirected.
