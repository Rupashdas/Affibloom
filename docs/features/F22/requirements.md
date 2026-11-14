# F22 Opportunities: requirements

Status: gates 1-2 written 2026-11-14 from a sweep of Lasso 369's Opportunities (area 3 of
`docs/plans/archive/dashboard-completion-plan.md`). Gate 4 for Tasks O1-O10: given by the user 2026-11-14. The monetize action (O10) waits
for the user's answer to N1.

## 1. User stories and acceptance criteria

- As an editor, I see every raw link in my content that points at a domain I already have a product for, so I can turn
  it into a product link or dismiss it.
- As an editor, I see which domains and which posts carry links that earn nothing, and I can export the unmonetized
  anchors to a spreadsheet.
- As an editor, a product's page and the Dashboard tell me how many opportunities it has, and I can switch detection off
  for a product or for the whole site.

- **AC1** The header's **Opportunities** tab opens **Link Opportunities** (`page=affibloom-opportunities`): sub-tabs
  Links, Domains, Content; a "N Found" count; "Search"; the intro box (closable, gone for good once closed); the table
  URL, Content, Suggestion, Type, Monetize, one row per untied link × product sharing its domain, ten to a page; search
  matches the URL or the domain, spaces as wildcards; URL, Content and Suggestion sort; with none, "No opportunities
  found.". (O1, R1-R16, D1-D8)
- **AC2** A Link Opportunities row's hover links work: **Filter by URL** opens Detected Links for its domain, **Filter by
  Content** opens that post's links, **Filter by Link** shows only that product's opportunities under the title "Link ›
  <product>" with a back arrow; the URL and Content links open the destination and the post's editor in a new tab; the
  Type icon opens the preview of the link in its paragraph. (O1, R8-R11, R13)
- **AC3** **Domains** lists every domain with untied text links (not this site's), its link count, sorted by count,
  biggest first; a green lightbulb ("This domain has possible opportunities") marks a domain that has a product or is
  Amazon's; a row opens **Detected Links › <domain>**: URL, Content, Link, Type, Monetize, search by post title or
  anchor text, oldest first. (O2, R17-R23)
- **AC4** **Content** lists every published post, page or reusable block that holds indexed links: Last Updated,
  Content, Permalink, Links; Show (All content / Has unmonetized links), Post type, Category and Min link clicks narrow
  it; newest first; a row opens that post's links (AC12). (O3, R24-R28, R32, R33)
- **AC5** A product's **Opportunities `<n>`** tab lists that product's opportunities in the Link Opportunities columns,
  with search and Dismiss; the badge equals the list's total and updates after Save Changes. (O5, R34-R37)
- **AC6** The product page's **Detect Opportunities** switch ("When disabled, Link Opportunities will not show for this
  product.") is saved with the product; off, the product's opportunities leave every list, count and the Dashboard; a
  new product takes the General setting's value. (O5, R38-R40, D9)
- **AC7** Settings → General → **Detect Opportunities** is saved with the settings; changing it asks "Update Link
  Settings" — **Only New Links** keeps the products as they are, **Override All Links** sets every product to the new
  value. (O6, R41-R43)
- **AC8** The **×** on a Link Opportunities row or on the product's Opportunities tab asks "Dismiss This Opportunity"
  ("If you dismiss this, it will no longer appear as an opportunity."); **Dismiss** hides the row and removes that link
  from every opportunity list and count, also after the post is saved again or the index is rebuilt; the notice
  "Opportunity dismissed." offers **Undo**. (O7, R44-R49, D10-D12)
- **AC9** The Dashboard's **See opportunities** tile shows the number of products with opportunities; clicking it lists
  only those products (sorted by their opportunity count, biggest first); each such row's Alert column shows a green
  lightbulb, "This URL has N possible opportunities.", linking to the product's Opportunities tab. (O8, R50-R56, D13)
- **AC10** On Content, **Export CSV** (disabled, "No content opportunities to export for the current filters.", when
  there is nothing) downloads `affibloom-content-opportunities-<YYYY-MM-DD>.csv` with Lasso's 18 columns, one row per
  unmonetized text or image link of the filtered posts, in the list's order, formula-safe; "Exported N anchors." is
  shown. (O9, R57-R63)
- **AC11** (Form set by N1.) The **Monetize** switch on an untied row opens **Monetize** ("Search URLs", six products a
  page, **Create a Link**); choosing a product makes readers see that product's link, `target` and `rel` on that anchor,
  while the post's stored content is byte-identical; the row turns green and leaves the opportunity lists; switching it
  off brings the original link back for readers. (O10, R64-R73, N1-N3)
- **AC12** **Content › <post>** lists that post's text and image links, product links first: URL (hover Filter by
  URL), Link (opens the preview), Type, Monetize (on for tied links); search by URL or anchor text; a title with a back
  arrow to Content. (O4, R29-R31)
- **AC13** Nothing of keyword opportunities, Programs or Marketplace Opportunities appears: no such tab, screen, modal,
  icon or tooltip text (R77's list), and no request leaves for Lasso's hosts. (O1, R74-R80, D14, D15)

## 2. Evidence

All read in full: `admin/views/opportunities/{header,links,domain,domain-links,content,content-links,keywords,
programs}.php`; `admin/views/education/*` (11 files; the opportunity ones are `link-opportunities.php`,
`link-opportunities-row.php`, `domain.php`, `content.php`, `keywords.php`, `keywords-row.php`); `admin/views/rows/
{link-opportunity,url-opportunity,domain,domain-link,content-opportunity,content-link,keyword-opportunity,
program-opportunity,saved-keyword,monetize}-row.php`; `admin/views/modals/{link-monetize,dismiss-opportunity,
saved-keywords,link-preview,override-display}.php`; `admin/views/dashboard/url-opportunities.php`;
`admin/assets/js/lasso-monetize-modal.js`; `pages/keyword-opportunities/class-ajax.php`; `classes/class-keyword.php`;
`classes/class-content-opportunities-csv-export.php`; `models/{class-tracked-keywords,class-keyword-locations,
class-affiliate-programs}.php`; `classes/background_processing/class-lasso-process-scan-keyword.php`.
Read in the parts this subsystem uses: `pages/class-ajax.php` 60-103 (hooks), 105-862 (`lasso_report_urls`), 1017-1171
(monetize, unmonetize, Amazon), 1176-1240 (`lasso_search_attributes`), 1295-1317 (dismiss), 1749-1768 (education box),
2386-2445 (export), 2576-2660 (capability helpers); `classes/class-lasso-db.php` 42, 280-640, 640-1000 (the
Opportunities filter), 1026-1190 (All tab), 1197-1212, 1312-1694, 1880-1960, 3276-3297, 3458-3490, 5337-5354;
`admin/assets/js/view-js.php` 25-100, 1024-1060, 1536-1720, 1764-1800, 1850-1935, 1940-2070, 2140-2290, 2470-2960;
`admin/views/header-new.php` 30-60, 150-171; `admin/views/dashboard/header.php` 20-30, 125-140, 240-330;
`admin/views/dashboard/url-details.php` 478-495, 1760-1770, 1880-1895; `admin/views/dashboard/dashboard.php` 55-62;
`admin/views/rows/url-row.php` 110-140; `admin/views/settings/general.php` 60-66, 495-515, 600-660;
`pages/settings-general/class-ajax.php` 1040-1075; `models/class-url-details.php` 60-100, 160-178;
`models/class-link-locations.php` 105-215; `classes/class-lasso-cron.php` 4653-4712, 5305-5370, 5715-5780;
`classes/class-post.php` 225-248; `classes/class-html-helper.php` 614-659; `classes/class-helper.php` 707-727,
1149-1162, 1547-1600, 5442-5454; `classes/class-lasso-affiliate-link.php` 150-160, 1680-1725, 1985-2005;
`classes/class-wp-all-import-csv-export.php` 217-233; `classes/class-post-type.php` 300-345; `classes/class-setting.php`
40-115, 455-470, 1108; `admin/lasso-constant.php` 45, 59, 383, 786-787; `pages/class-hook.php` 1805-1831;
`admin/views/modals/url-add.php` 1460-1470. Callers found by grep for every page slug, AJAX action, `is_opportunity`,
`detect_opportunities`, `is_ignored`, `LASSO_AFFILIATE_PROGRAMS`, `opportunit`.

**This copy, 2026-11-14 (SELECT only; the Local site URL is `volleyballvault.local`):**

| What | Count |
|---|---|
| Untied index rows (`post_id` 0) | 2,094: Internal 1,304 (domain NULL), External Text 678, External Image-only 110, Lasso/Table 2 |
| Untied rows whose domain is some product's domain | 240: amazon.com 237 (excluded from link opportunities as an Amazon domain), usa.mizuno.com **3** |
| **Link Opportunities** (Lasso's query) | **30 rows** = 3 locations × 10 products with domain usa.mizuno.com, one of them deleted (`lasso_delete`, #14771); with published products only, 27; 1 host post (#4375 "8 Best Mizuno Volleyball Shoes …") |
| **Dashboard tile** (Lasso's rule) | **12** products: the 9 published Mizuno products (3 opportunities each, 1 host) and 3 Amazon products whose URL is an `amzn.to` short link (#9798, #10010, #10131) and so pass the tile's `redirect_url NOT LIKE '%amazon.%'` while their domain amazon.com is excluded from the lists: their lightbulb says 30, their tab lists 0 (R56) |
| **Domains** | 98 domains, 574 untied Text links; top amazon.com 225, amzn.to 129, en.wikipedia.org 13 |
| **Content** | 178 published hosts (134 posts, 13 pages, 31 reusable blocks); 169 with an unmonetized link; Content › links over all hosts 2,756 rows |
| **Export CSV** (no filters) | 2,061 anchors (1,991 in posts and pages) |
| Dismissed (`is_ignored` 1) | 0 link rows, 0 keyword rows |
| `lasso_url_details.is_opportunity` | on 254 (252 published, 1 trash, 1 lasso_delete), off 2; 9 published products have no `url_details` row |
| `lasso_settings` | `detect_opportunities` true, `general_disable_tooltip` false, no intro box hidden |
| Keywords | `wp_lasso_tracked_keywords` 0, `wp_lasso_keyword_locations` 0, no `<keyword>` tag and no `data-keyword-location-id` in any post |
| `wp_lasso_affiliate_programs` | **15,470** programmes (Lasso's catalogue: name, logo on getlasso.co for 15,410, commission, signup page, primary domain; 2,794 niches; created 2021-08-16 to 2026-11-07); filled by the daily cron from `https://lasso.link/data-sync/data` (last sync 2026-11-09, index 7043). The Programs screen would list 6 (top: "5 Stars United" on amazon.com, 237 links) |

## 3. What Lasso does

### Pages and navigation

| # | Rule | Source |
|---|---|---|
| R1 | **Pages:** `link-opportunities`, `domain-opportunities`, `domain-links`, `content-opportunities`, `content-links`, `keyword-opportunities`, `program-opportunities` (all under the Lasso menu), and the product page's `url-opportunities`. The header's **Opportunities** tab is active on the first seven and links to `program-opportunities`; the WordPress submenu entry "Opportunities" (position 4) is `program-opportunities` | header-new.php 37-55, 159; class-post-type.php 327-330; class-setting.php 47-114 |
| R2 | **Sub-tabs** (`opportunities/header.php`): **Programs**, **Keywords**, **Content**, **Marketplace Opportunities** (external, `https://app.getlasso.co/opportunities/`, new tab). **Links** and **Domains** tabs are commented out in 369: those pages are reached by URL and from row links. Programs is active on Programs and Detected Links | opportunities/header.php 104-155 |
| R3 | **Title:** `<Category> Opportunities` (Link, Domain, Content, Keyword); Programs → "Affiliate Program Opportunities"; with `post_id` → `<Category> › <title of post_id>`; with `filter` → `<Category> › <filter>` (Detected Links › <domain>); with `keyword` → `Keyword › "<keyword>"`; each filtered title has a back arrow to the list (`domain-links` → Programs) | opportunities/header.php 12-57 |
| R4 | **Header row:** the sub-tabs left, "N Found" (`#js-report-result`, thousands separated, "0 Found" when empty) in the middle, "Search" right; every list ends with "a-b of N" and page links | opportunities/header.php 157-171; view-js.php 2223-2235, 1024-1060 |
| R5 | **Intro boxes** (above the tabs, closable ×): Link — "Turn Any Existing Link Into a Lasso Link" / "These are all of your external links that point to the same domain as an existing Lasso Link you created. Choose to monetize the link with Lasso or dismiss the Suggestion." and a help link; Content — "Identify And Fix Your Under-Monetized Content" / "Publishing a post that gets traffic just isn’t enough. They need to drive income. Find out which posts are under-monetized and increase your monthly revenue."; Domain (on Programs and Detected Links) — "Find Site-Wide Monetization Opportunities" / "You’ll be shocked how many times you linked to a domain you didn’t know had an affiliate program. Sign up for that program, add your new affiliate URL to Lasso, and instantly monetize all those links."; Keyword — "Create New Links Fast With Keywords" | education/{link-opportunities,content,domain,keywords}.php |
| R6 | **Closing a box** (link, content, keyword, and the domain page's) posts `lasso_hide_education_box` (`edit_posts`, nonce `lasso_ajax`) and stores `lasso_settings[<page>] = 'hide'`; the domain box on Programs only hides until reload (its own script; the key read there is never written); `?reseteducation` clears the four keys; "Disable tooltips" (`general_disable_tooltip`) hides every box | opportunities/header.php 72-102; view-js.php 2908-2935; education/domain.php 23-30; pages/class-ajax.php 1749-1768 |
| R7 | **Lists load by AJAX** (`lasso_report_urls`, `edit_posts`, nonce `lasso_ajax`; `link_type` = the page): 10 a page; a page past the end falls back to page 1; `pageNumber`, `search` (spaces → `%`, wrapped in `%…%`), `order_by` (an allow-list per page; anything else → the page's default), `order_type` asc/desc; the search is written to the address (`search=`); a column header cycles none → asc (or its `data-order-init`) → desc → asc | pages/class-ajax.php 109-135, 369-506; class-helper.php 5442-5454; view-js.php 1764-1800, 1940-1955 |

### Link Opportunities

| # | Rule | Source |
|---|---|---|
| R8 | **The query** (`get_link_opportunities_query`): index rows `c` joined to `lasso_url_details d` on `d.base_domain = c.link_slug_domain`, joined to the product `p` (no status check) and the host `a`; where the domain is not this site's (`site_url` without scheme) and not an Amazon domain (Lasso's 23 Amazon domains minus `amzn.com`, `amzn.to`), `c.post_id = 0` (untied), `d.is_opportunity = 1`, `c.detection_slug` not empty and not `%__trashed%`, `c.is_ignored = 0`. **One row per location × product** with that domain; the total is the row count | class-lasso-db.php 1320-1394, 5337-5354, 42 |
| R9 | **Search** matches `c.link_slug LIKE` or `d.base_domain LIKE`; `post_id` in the address narrows to one product (`d.lasso_id`) | class-lasso-db.php 1324-1349; pages/class-ajax.php 369-378 |
| R10 | **Columns:** URL ("This is a URL found on your site that may be able to be monetized.", sort `link_slug_original`), Content ("Where we found this URL. Click link to edit.", sort `post_title`), Suggestion ("The name of a Lasso Link created with the same domain as this URL. Click link to edit.", sort `lasso_suggestion`), Type ("Click to see a snippit of this link as it appears on your site."), Monetize. No default order (the database's) | opportunities/links.php 22-46; pages/class-ajax.php 370-372 |
| R11 | **A row:** URL = the link (new tab), hover **Filter by URL** (→ `domain-links&filter=<domain>`) and **Open in New Window**; Content = host title to its editor (new tab), hover **Filter by Content** (→ `content-links&post_id=<host>`) and **Open in New Window** (permalink); Suggestion = the product's name to its page (new tab), hover **Filter by Link** (→ `link-opportunities&post_id=<product>`, not shown when filtered); Type = icon by R12, tooltip "This is <description>.", opens the preview; Monetize switch (R64); **×** dismiss (R44) | rows/link-opportunity-row.php |
| R12 | **Type icon:** an anchor holding `img` → image ("an image link"), a keyword row → key ("a keyword mention"), a group display → grid/list/gallery/table icon ("a group of links display"), a Lasso single display → pager ("a single link display"), else link ("a text link"); green when tied to a product, else light purple; the switch is on when green | class-html-helper.php 622-659; view-js.php 2479 |
| R13 | **Preview** (`#link-preview`): a text or image link shows the line of stored content holding `data-lasso-id="<location>"`; a display is drawn with `do_shortcode`; AAWP shows its text | pages/class-ajax.php 757-794; class-helper.php 1547-1600; view-js.php 2740-2790 |
| R14 | **Empty:** "No opportunities found." | education/link-opportunities-row.php; view-js.php 2578-2579 |
| R15 | The row's post link and `permalink` come from the host (`get_permalink(detection_id)`, `get_edit_post_link`) | pages/class-ajax.php 606-607, 845-847 |
| R16 | `lasso_report_urls` also returns `total.link_count` (all published products) on every list (unused here) | pages/class-ajax.php 581-582 |

### Domains and Detected Links

| # | Rule | Source |
|---|---|---|
| R17 | **Domain Opportunities** (`get_opportunities_query`): untied `Text` rows grouped by domain, not this site's (a NULL domain drops out), with their count; search `link_slug_domain LIKE`; **no `is_ignored` or trashed-host filter**; sort `link_slug_domain` or `count`, default `count desc` | class-lasso-db.php 1544-1567; pages/class-ajax.php 391-406 |
| R18 | A search containing the word "Opportunities" switches to `get_opportunities_only_query`: External `Text` and `Image-only` rows per domain, tied or not | pages/class-ajax.php 399-401; class-lasso-db.php 1660-1694 |
| R19 | **Columns:** Domain (sort), a lightbulb column, Links (sort, initial desc); a row links to `domain-links&filter=<domain>`; the green lightbulb ("This domain has possible opportunities") shows when `monetize_with` is set: the title of any product with that `base_domain` (any status), or "Amazon Associates" for an Amazon domain | opportunities/domain.php; rows/domain-row.php; pages/class-ajax.php 730-748; class-helper.php 1149-1162 |
| R20 | **Detected Links** (`domain-links&filter=<domain>`, `get_domain_link_query`): untied rows of that domain (any link and display type, no `is_ignored` or host filter); search post title or anchor text; sort URL (`link_slug`), Content (`post_title`); default `ll.ID asc` | class-lasso-db.php 1616-1652; pages/class-ajax.php 496-506; opportunities/domain-links.php |
| R21 | **Columns:** URL, Content (hover Filter by Content), Link (anchor text, image src, or the URL for a display; opens the preview), Type, Monetize; no dismiss | rows/domain-link-row.php |
| R22 | Without `filter` the page searches `example.com` (default) | opportunities/domain-links.php 15-19 |
| R23 | The Domain intro box (R5) shows on Programs and Detected Links | opportunities/header.php 95-96 |

### Content

| # | Rule | Source |
|---|---|---|
| R24 | **Content Opportunities** (`get_content_query`): every published host with index rows, grouped per host and its `detection_slug`: `monetized` = tied rows, `count` = all rows, last modified; `short_detection_slug` = the permalink without this site and scheme; `post_modified_string` like "Nov 9th, 2026"; search `detection_title LIKE` | class-lasso-db.php 1453-1490; pages/class-ajax.php 423-440 |
| R25 | **Filters** ("Show": All content / Has unmonetized links → `count > monetized`; "Post type": All types / Posts / Pages → host type post or page; "Category": All categories + every category, empty ones too, by name → the host is in it; "Min link clicks" number ≥ 0, default 0 → the sum of `url_details.clicks` of the products tied in the host ≥ N); each change reloads page 1 | opportunities/content.php 14-63; class-content-opportunities-csv-export.php (`build_content_list_where_sql`, `build_content_post_filters_where_sql`); view-js.php 1676-1699 |
| R26 | **Columns:** Last Updated (sort `post_modified`, default desc), Content ("Where we found this keyword. Click name to view its links.", sort `detection_title`), Permalink ("The slug for this piece of content. Click to view it on your site.", sort `detection_slug`; opens the post), Links ("Number of links discovered in this piece of content.", sort `count`, initial desc); a row opens `content-links&post_id=<host>` | opportunities/content.php 88-106; rows/content-opportunity-row.php; view-js.php 32-41 |
| R27 | **Export CSV** button in the filter bar (R57) | opportunities/content.php 58-60 |
| R28 | No empty state of its own: "Looks like we're coming up empty." with Lasso's cow icon | view-js.php 2600-2610 |
| R29 | **A post's links** (`content-links&post_id=<host>`, `get_content_link_query`): the host's `Text` and `Image-only` rows, tied (to a product that exists) or not, published host; search `link_slug` or the anchor text; default order link type desc then id (product links, then internal, then external); title "Content › <post>" | class-lasso-db.php 1498-1536; pages/class-ajax.php 448-494 |
| R30 | **Columns:** URL (hover Filter by URL), Link (anchor text or image src; preview), Type, Monetize (on for tied rows; disabled for a single-link display); the switch here may change links to the site's own posts (`allow_change_wp_post_link`) | opportunities/content-links.php; rows/content-link-row.php |
| R31 | Without `post_id`, Content › links lists every published host's rows | pages/class-ajax.php 132, 490 |
| R32 | Lasso's own commented-out "Content with at least N monetized links" control is not shown | opportunities/content.php 65-86 |
| R33 | Sorting a list re-reads the export count (R58) | view-js.php 2223-2227 |

### The product page's tab and Detect Opportunities

| # | Rule | Source |
|---|---|---|
| R34 | **Opportunities `<n>`** (third tab, `url-opportunities&post_id=<product>`): the badge = R8's row count for the product; refreshed after Save Changes from the save response | dashboard/header.php 125-137, 278-288; class-lasso-affiliate-link.php 1993-2003; url-details.php 1888-1894 |
| R35 | **The tab:** the product's header and tabs, "Search"; R10's columns; rows as R11 with the filter links hidden; Monetize and Dismiss; empty "No opportunities found." | dashboard/url-opportunities.php; rows/url-opportunity-row.php |
| R36 | Its search and paging are R7's with `link_type = url-opportunities` and R9's product filter | pages/class-ajax.php 369-378 |
| R37 | `get_lasso_link_counts` adds the keyword-location count to an "opportunities" figure that the header does not use | class-lasso-db.php 297-372 |
| R38 | **Detect Opportunities** (product page, the link-action toggles): switch `is_opportunity`, tooltip "When disabled, Link Opportunities will not show for this Lasso Link."; stored in `lasso_url_details.is_opportunity` (tinyint, default 1) | url-details.php 480-489, 1767; models/class-url-details.php 60-100 |
| R39 | **On save:** the posted value; else the old row's; else the setting `detect_opportunities` (default true); `base_domain` = the final URL's host without `www.` is saved beside it | class-lasso-affiliate-link.php 150-160, 1167-1177, 1699-1725; class-helper.php 707-727 |
| R40 | WP All Import saves set `is_opportunity` 0; Lasso's importer takes the import's value, default 1 | pages/class-hook.php 1805-1831; class-import.php 329 |
| R41 | **Settings → General → Detect Opportunities** (`detect_opportunities`, default true): tooltip "When off, Link Opportunities won’t show for affected links. Doesn’t change how Lasso finds opportunities in the background." | settings/general.php 60-66, 503-508; lasso-constant.php 383 |
| R42 | **Changing it** opens "Update Link Settings" — "Lasso will <enable/disable> the option Detect Opportunities"; **Only New Links** closes; **Override All Links** posts `lasso_override_display_option` (`option_name` `is_opportunity`) at once, which sets `is_opportunity` on every `url_details` row (no row is created) | settings/general.php 649-661; modals/override-display.php; pages/settings-general/class-ajax.php 1040-1063; models/class-url-details.php 163-177 |
| R43 | The setting itself is saved with the General form | settings/general.php; class-setting.php 1108 |

### Dismiss

| # | Rule | Source |
|---|---|---|
| R44 | **×** (top right of a Link Opportunities or product-tab row) opens "Dismiss This Opportunity" / "If you dismiss this, it will no longer appear as an opportunity." / Cancel, **Dismiss** (red) | rows/link-opportunity-row.php 60-69; modals/dismiss-opportunity.php |
| R45 | **Dismiss** posts `lasso_dismiss_opportunity` (`edit_posts`, nonce `lasso_ajax`; `link_type` link, `link_id` = the location) → `UPDATE lasso_link_locations SET is_ignored = 1 WHERE id = <id>` (the id concatenated unescaped); success: the dialog closes, the row flashes and collapses; failure: "Dismiss failed." | pages/class-ajax.php 1295-1317; class-lasso-db.php 3276-3297; view-js.php 2849-2905 |
| R46 | **What it filters:** R8 (every product pairing of that link), the product tab and its badge, the Dashboard tile, lightbulbs and Opportunities tab (all test `is_ignored = 0`). **Not** Domains, Detected Links, Content, a post's links, or the CSV | class-lasso-db.php 1365, 1909, 1945, 414, 618; 1544-1652; csv-export |
| R47 | **No undo** anywhere in Lasso's screens | (searched all views and scripts for `is_ignored`) |
| R48 | **A rescan forgets it:** the scan does not rewrite an ignored row, drops it from its kept list and deletes it; the next scan recreates it with `is_ignored` 0 (F21 R4) | class-lasso-cron.php 5195-5207, 5528-5543 (F21) |
| R49 | Keyword rows are dismissed the same way in `lasso_keyword_locations` | class-lasso-db.php 3283-3294 |

### The Dashboard

| # | Rule | Source |
|---|---|---|
| R50 | **Tile** "See opportunities" (green, lightbulb + count, always shown; a clock and "Totals may update for up to 24 hours while Lasso scans your links." during the stats warm-up): `get_dashboard_total_opportunities_filtered` = distinct **published** products with `is_opportunity` 1, domain not this site's, `redirect_url NOT LIKE '%amazon.%'`, having an untied, not-ignored row of that domain in a live host; the saved-view and search filters apply | dashboard.php 59-61; view-js.php 2140-2176; class-lasso-db.php 1925-1960; pages/class-ajax.php 253-263 |
| R51 | **Clicking the tile** toggles the `opportunities` filter (the broken and out-of-stock tabs switch off); the list total becomes the tile's count; the Alert column's sort becomes `opportunity_count` desc | view-js.php 1877-1930; pages/class-ajax.php 191-193, 265-268 |
| R52 | **The Opportunities filter list** (`get_dashboard_query_filter_by_opportunities`): R50's products, sorted by Last Updated, Alert (issue date), Opportunities count, Locations or Clicks; each row's `opportunities` = **distinct host posts** with such a link | class-lasso-db.php 640-1000, 402-432 |
| R53 | **On the All, Broken and Out-of-stock tabs** each row's `opportunities` = R8's **rows** for the product (Amazon domains excluded, not `redirect_url`) | class-lasso-db.php 552-556, 1123-1157 |
| R54 | **The lightbulb** in the Alert column when `opportunities` > 0: green, "This URL has N possible opportunity/opportunities.", links to the product's Opportunities tab | rows/url-row.php 131-136 |
| R55 | Sorting by Opportunities outside the tab: "Sorting by Opportunities is only available on the Opportunities tab. Showing All Links sorted by Last Updated instead." (F19 owns the notice) | pages/class-ajax.php 156-166 |
| R56 | **The rules disagree:** the tile and the tab (R50, R52) test `redirect_url`, the lists (R8, R53) test the domain against Amazon's; a product with an `amzn.to` URL and domain amazon.com is counted (here 3 products, 30 hosts each) yet its tab lists nothing. R8 has no product-status test, the tile has | class-lasso-db.php 1359 vs 1899-1902 |

### Export CSV

| # | Rule | Source |
|---|---|---|
| R57 | **Button** "Export CSV" (title "Export unmonetized anchors to CSV"), disabled with the title "No content opportunities to export for the current filters." while the export count is 0; while running a spinner and "Exporting"; then "Exported N anchor(s)." or the server's message | content.php 58-60; view-js.php 1540-1715 |
| R58 | **Export count** (`export_anchor_count`, returned with every Content page) = the rows R59 would write for the current search and filters | pages/class-ajax.php 442-446; csv-export `count_exportable_anchors` |
| R59 | **Rows:** every untied `Text` / `Image-only` index row of a published host that passes the Content list's search and filters (R24, R25); ordered by the list's sort (`post_modified`, `detection_title`, `detection_slug`, `count`, `anchor_text`, `destination_url`; default `post_modified desc`), then location id; read 100 at a time (keyset); dismissed rows are included | csv-export `build_export_posts_from_sql`, `get_export_order_context`, `stream_export_csv` |
| R60 | **Columns** (header row as written): `post_title, post_url, post_id, post_type, post_categories, post_modified (Y-m-d), content_link_clicks (R25's sum), anchor_text (tags stripped, entities decoded, spaces collapsed), anchor_context (the host's content line holding the link, normalised the same way), destination_url, link_domain, link_type, display_type, link_location_id, unmonetized_link_count (the post's count − monetized), suggested_product_title (the lowest-ID published product with that domain, else "Amazon Associates" for an Amazon domain), suggested_product_id, suggested_product_edit_url` | csv-export `get_headers`, `build_row_from_query_row`, `get_anchor_context` |
| R61 | **Formula guard:** a cell starting (after spaces) with `=`, `+`, `@`, or `-` not followed by a digit gets a leading tab | class-wp-all-import-csv-export.php 217-233 |
| R62 | **Request:** `lasso_export_content_opportunities_csv`, nonce `lasso_ajax`, `manage_options` or Lasso's permission level; a hidden form posted into a hidden frame; file `lasso-content-opportunities-<Y-m-d>.csv`, `text/csv; charset=utf-8`, no-cache; a bad request → 400 JSON message; no stream → 500 "Could not open export stream." | pages/class-ajax.php 2390-2445, 2645-2655 |
| R63 | The Post type filter offers posts and pages only; reusable blocks are listed under "All types" | csv-export `CONTENT_POST_TYPES` |

### Monetize and unmonetize (what Lasso writes)

| # | Rule | Source |
|---|---|---|
| R64 | **The switch** on Link Opportunities, the product tab, Detected Links and a post's links: on → if the row's URL contains `www.amazon.`, `//amazon.` or `amzn.` R70, else the **Monetize** dialog; off → R72 | view-js.php 2624-2655 |
| R65 | **Monetize dialog** (`link-monetize`): title "Monetize"; **Create a Link** (opens Add a Link; after it, the quick-detail modal of the new product, no redirect); "Search URLs" (placeholder becomes "Search N URLs"; Enter searches); rows of published products (`lasso_search_attributes`: title LIKE, six a page, newest modified first): thumbnail, name, URL (the product's link; an Amazon product's tagged URL), **Monetize**; empty "Looks like we're coming up empty, partner."; closing without a choice turns the switch off | modals/link-monetize.php; rows/monetize-row.php; lasso-monetize-modal.js 1-28, 160-248; pages/class-ajax.php 1176-1240; url-add.php 1466-1469, 2002-2008 |
| R66 | **Monetize** posts `lasso_popup_monetize_link` (`edit_posts` and `edit_post` on the host; `post_id` host, `lasso_id` = the location, `new_url` = the product URL shown, `link_type`, `allow_change_wp_post_link`); anything missing → `status` false and the switch goes off | pages/class-ajax.php 1020-1069; lasso-monetize-modal.js 61-123 |
| R67 | **What it rewrites (R row):** `update_link_in_post` reads the host's `post_content` (Thrive's meta when Thrive edits it), finds the first `<a data-lasso-id="<location>">`, skips it if it links to a post of the site unless allowed, sets **`href` = the product URL** and **`target="_blank"`** (or removes `target`) from New Window, and writes `post_content` back (`wpdb->update`, or Thrive's meta); records the change in `lasso_post_content_history`; then rescans the host, whose write-back re-stamps the anchor (`data-lasso-lid`, `data-lasso-name`, `rel`) and ties the row to the product | class-lasso-cron.php 4653-4712, 5305-5370 |
| R68 | **Also rewritten:** Elementor's `_elementor_data` for an Elementor host (`update_content_to_plugin`, MODE_MONETIZE) | pages/class-ajax.php 1053; classes/class-post.php 225-233 |
| R69 | **After it:** the row's URL text and link become the product URL, the row flashes green, its icons turn green | lasso-monetize-modal.js 99-118 |
| R70 | **An Amazon link** (`monetize_amazon_link`, same capabilities): an `amzn.` short link is followed to its final URL; no product ID → "Product id is invalid" and the switch is clicked back (which sends R72); finds the product by URL or **creates one** (`lasso_add_a_new_link`, which fetches Amazon data through Lasso's services); touches the product; rewrites the `href` to the product's tagged Amazon URL (R67, no Elementor) | pages/class-ajax.php 1112-1171; view-js.php 2697-2740 |
| R71 | **No Amazon row reaches Link Opportunities** (R8 excludes Amazon domains); the Amazon branch serves Detected Links and a post's links (amazon.com 225 rows here) | class-lasso-db.php 1359 |
| R72 | **Unmonetize** (`lasso_unmonetized_link`): the location's stored `original_link_slug` (the URL before Lasso's scan rewrote it) is written back as the `href` by R67 (its New Window read for a location id, not a product), Elementor too; the row shows the original URL and flashes purple | pages/class-ajax.php 1074-1107; view-js.php 2807-2847 |
| R73 | Monetize and unmonetize are tracked to Segment (`lasso_segment_tracking`) and Intercom | view-js.php 2700-2710, 2832, 2886; lasso-monetize-modal.js |

### Keyword opportunities (left out by the user; recorded in full)

| # | Rule | Source |
|---|---|---|
| R74 | **Storage:** `lasso_tracked_keywords (id, lasso_id, keyword)` and `lasso_keyword_locations (id, lasso_id, keyword, detection_id, is_ignored)` | models/class-tracked-keywords.php; models/class-keyword-locations.php |
| R75 | **Screen** `keyword-opportunities`: "Enter a keyword or phrase", **Add Keyword** (Enter too; "You didn't enter a keyword"; `lasso_add_keyword` → "Keyword already exists." 409 / "Internal Error." 500), **Saved Keywords** (modal: "Search N Keywords", six a page, **Delete** → "Keyword deleted." / "Delete keyword failed."; `lasso_get_keywords`, `lasso_delete_keyword`); columns "Keyword with Context" ("A keyword we discovered in your content. Click link to view context.") and Content ("Where we found this keyword. Click link to edit."), Monetize, ×; empty "No opportunities found. Add a keyword to get started."; intro "Create New Links Fast With Keywords" / "Enter in a keyword or phrase and Lasso will find every mention on your site. Then, turn those keywords into Lasso links." | opportunities/keywords.php; rows/keyword-opportunity-row.php, saved-keyword-row.php; modals/saved-keywords.php; education/keywords*.php; pages/keyword-opportunities/class-ajax.php; view-js.php 2937-3000; lasso-monetize-modal.js 126-157, 250-314 |
| R76 | **What it rewrites (R row):** adding a keyword scans published and draft posts of the supported types (MySQL `REGEXP` word match) and **wraps every match in `post_content` in `<keyword data-keyword-id="<location>">…</keyword>`** (outside links, scripts, shortcodes and unsupported blocks), one location row each; the link scan does the same for every post it scans; monetizing a keyword **replaces the tag with `<a data-keyword-location-id="<id>" href="<product URL>" rel="nofollow" target="_blank">`** and rescans; unmonetizing turns the link back into the tag (`lasso_unmonetized_keyword`, "Keyword unmonetized." / "Unmonetized keyword failed, please try again."); deleting a keyword strips its tags from every post | class-keyword.php 43-95, 105-325, 410-560; class-lasso-process-link-database.php 100; class-lasso-process-scan-keyword.php |
| R77 | **Every UI element that mentions keywords** (to omit): the Keywords sub-tab; the whole keyword screen and Saved Keywords modal; the keyword intro box and its empty text; the title form `Keyword › "<keyword>"`; the Type icon "a keyword mention" (key); the Content column's tooltip "Where we found this keyword. Click name to view its links." (on Content, R26); the `keyword` branches of Monetize, Unmonetize and Dismiss; the keyword count inside `get_lasso_link_counts` (R37); `keyword-opportunities` in the Add a Link and display modals' "no redirect" lists | R2, R3, R12, R26, R37, R49, R64-R66; url-add.php 1467; lasso-display-modal.js 198 |

### Programs (Lasso's cloud catalogue)

| # | Rule | Source |
|---|---|---|
| R78 | **Programs** ("Affiliate Program Opportunities", the Opportunities landing page): `get_program_opportunities_query` joins **all** index rows per domain (tied too) to `lasso_affiliate_programs` on its primary domain (scheme, `www.` and slash removed), not "Amazon Associates" or "Google AdSense"; columns Image (logo, 50 px), Affiliate Program (name to Lasso's programme page, description), Commission Rate (Lasso's partner rate, else the commission), Potential Links (the count → Detected Links), **Signup** (the programme's signup page); search domain or title (an unbracketed OR); default `count desc` | opportunities/programs.php; rows/program-opportunity-row.php; class-lasso-db.php 1575-1608; pages/class-ajax.php 408-421, 649-652 |
| R79 | **The data comes from Lasso's cloud:** the daily cron `lasso_daily_data_sync_affiliate_programs` GETs `https://lasso.link/data-sync/data?post_modified=<last>` with Lasso's licence headers and bulk-inserts the programmes, then GETs an encrypted id list and deletes the rest (`lasso_affiliate_programs_last_modified`, `lasso_sync_affiliate_programs_index`). **C — never (Lasso cloud).** The table also gives a new product its default image (the programme logo, F1 S13) | class-lasso-cron.php 301, 5718-5780; models/class-affiliate-programs.php; lasso-constant.php 59, 786-787; class-lasso-affiliate-link.php 1682-1689 |
| R80 | **Marketplace Opportunities** is a link to `app.getlasso.co/opportunities/`. **C — never** | opportunities/header.php 133-135 |

## 4. Decisions

Settled by Claude from Lasso and the records (none drops anything Lasso does or reverses a user decision):

- **D1 Pages:** one screen `admin.php?page=affibloom-opportunities` with `tab=links` (default), `tab=domains`,
  `tab=domain&domain=<d>` (Detected Links), `tab=content`, `tab=post&post=<id>` (a post's links); `&product=<id>` on
  links is Lasso's "Filter by Link". The product page's tab is `page=affibloom-product&id=<id>&tab=opportunities`.
  The header's **Opportunities** tab (Dashboard, Opportunities, Tables, Groups order, F23 D9) and a submenu entry open
  Links.
- **D2 Sub-tabs Links, Domains, Content.** Lasso 369's visible tabs are Programs, Keywords, Content and Marketplace
  (R2); Keywords is left out (the user, 2026-11-13), Programs and Marketplace are Lasso's cloud (R79, R80, the
  completion plan's bucket C). That leaves Content, and the two screens the user kept — Link and Domain — get back the
  tabs Lasso commented out. Links lands first because Programs is not built.
- **D3 Lists load in place** as the Dashboard and Groups do (13D, F23 D13): the page prints the first page; search,
  sort and page links fetch the same partial through REST and keep the address in step; without JavaScript the search
  is a form and the pages are links. Ten a page; a page past the end shows page 1 (R7).
- **D4 Opportunity rule (R8), on F21's index:** untied rows (`product_id` 0, not assigned under N1), `is_ignored` 0,
  host live (D8 of F21: trashed hosts have no rows), domain not the site's host, not one of Lasso's Amazon domains
  (the same 21: Lasso's 23 minus `amzn.com`, `amzn.to`), joined to **published** products whose stored domain equals
  the row's domain and whose Detect Opportunities is on. One row per location × product, as Lasso (the Suggestion and
  Filter by Link need it). Lasso joins products of any status (R56): Affibloom ties only published products everywhere
  (F21 D8), so this copy shows 27 rows, not 30.
- **D5 A product's domain** is stored as `_affibloom_domain` (the host of the product URL, `www.` removed, Lasso's
  `get_base_domain`) on every product save, and filled once for existing products by an upgrade step; Task 26 copies
  `url_details.base_domain`.
- **D6 Default order** where Lasso has none (R10): location id, then product id — what the database returns here.
- **D7 Wording** (U6, the wording rule): "Turn Any Existing Link Into a Product Link" / "These are all of your external
  links that point to the same domain as an existing product you created. Choose to monetize the link with a product or
  dismiss the Suggestion."; Suggestion "The name of a product with the same domain as this URL. Click link to edit.";
  "…add your new affiliate URL to Affibloom, and instantly monetize…"; "When disabled, Link Opportunities will not show
  for this product."; "…Doesn’t change how Affibloom finds opportunities in the background."; "Affibloom will
  <enable/disable> the option Detect Opportunities"; Content's tooltip "Where we found these links. Click name to view
  its links." (R77: Lasso's says "keyword"); "snippit" → "snippet"; the empty texts are Lasso's ("No opportunities found.",
  "Looks like we're coming up empty.", "…, partner.") with the design system's empty icon instead of the cow; help
  links to Lasso's site left out (deviation 52).
- **D8 Intro boxes** are kept with D7's words; a closed box is stored per site in `affibloom_settings['education_hidden']` (settled 2026-11-14 with F38 ON5; first written as the option `affibloom_hidden_intros`)
  (Lasso: `lasso_settings[<page>]`); the Domain box hides for good too (Lasso's forgets on reload, R6, a bug);
  Settings' "Disable tooltips" (F9 R53) hides them; `?reseteducation` becomes `&reset_intros=1` (support only).
- **D9 Detect Opportunities** is the product meta `_affibloom_detect_opportunities` ('1'/'0'; absent = the General
  setting at creation, then stored); the switch replaces F1's placeholder on the display card; the General setting is
  `detect_opportunities` (default on) in F9's Link Defaults; its "Update Link Settings" dialog is F9's (R47 there),
  here with `is_opportunity` meaning every product. WP All Import's 0 (R40) belongs to whatever feature owns that
  import (section 5).
- **D10 Dismissals survive a rescan** (F21 D10, owned here): `is_ignored` is kept while the row's identity is
  unchanged; Lasso forgets them (R48). Task 26 maps Lasso's dismissed location ids through `lasso_location_id`.
- **D11 Undo:** Lasso has none (R47) and its rescans undid dismissals by accident; Affibloom's keep, so the
  notice "Opportunity dismissed." offers **Undo** (`is_ignored` back to 0). Nothing else changes.
- **D12 Dismiss filters what Lasso's does** (R46): link lists, the product tab, the tile and lightbulbs; Domains,
  Detected Links, Content and the CSV still show the link, as in Lasso.
- **D13 One count rule for the Dashboard:** the tile, the Opportunities tab and every lightbulb use D4's rule; the tab's
  per-row figure is distinct hosts (R52), the All tab's is rows (R53), as Lasso. Lasso's `redirect_url` test (R56) makes
  its tile 12 here while its lists know 9 of them; Affibloom shows 9. (Recorded, not asked: no function is lost; a
  number that contradicted its own list is made to agree. The user can reverse it.)
- **D14 Programs, Marketplace and the programme sync are not built** (R78-R80, **C — never**). The table's other
  reader, a new product's default logo (R79), is F1's and is C there too.
- **D15 Keyword opportunities are not built** (the user, 2026-11-13): R74-R77 are the record; every element of R77 is
  omitted; F21 does not index `<keyword>` tags (F21 D11).
- **D16 Lasso's search quirk** (R18) is kept: a Domains search containing "Opportunities" lists External Text and
  Image-only links per domain.
- **D17 Content's Min link clicks and the CSV's `content_link_clicks`** read each tied product's total clicks from
  F12's click store (Lasso's `url_details.clicks`); until F12's clicks exist they are 0 and the filter is hidden.
- **D18 The Content filters' Post type** offers Posts and Pages as Lasso (R63); "All types" includes reusable blocks.
- **D19 Export** is `GET /affibloom/v1/opportunities/content/export` streamed with `_wpnonce` (no hidden frame); file
  `affibloom-content-opportunities-<Y-m-d>.csv`; Lasso's 18 headers unchanged (spreadsheets made from Lasso's export
  keep working); `suggested_product_edit_url` is Affibloom's product page; R61's guard.
- **D20 Who:** `Capabilities::PRODUCTS` for every screen and route (decision 1; Lasso: `edit_posts`, export
  `manage_options`); any action on a row also needs `edit_post` on its host (Lasso's `require_edit_post_capability`).
- **D21 The preview** is F12's (`GET /locations/{row}/preview`, 36C); the Monetize dialog's "Create a Link" is F18's
  Add a Link with its quick-detail step (F18's "monetize screen: no redirect"; F1 D32); the product search is F23 D10's
  `GET /products?search=` with six a page.
- **D22 Not built:** Segment and Intercom tracking (R73, Lasso cloud); `total.link_count` (R16, no reader).

**Needs the user** (each would replace part of Lasso):

- **N1 Monetize without rewriting.** Lasso's Monetize switch rewrites the post (R66-R68: `href`, `target`, Thrive meta,
  `_elementor_data`, a history row) and Unmonetize rewrites it back (R72). Affibloom never edits post content. Options:
  **(A) Render-time assignment** — the switch stores "location → product" on the index row (`assigned_product_id`,
  kept across rescans with D10's rule); F21's render filter (D13 there) prints the product's link, `target` and `rel`
  for that anchor; the row counts as tied everywhere (Locations, opportunities, clicks); switching it off clears the
  assignment and readers see the stored link again. The editor still shows the old URL; deactivating Affibloom shows
  readers the old URLs. **(B) Send the editor there** — each row gets **Edit in post**, opening the block editor with
  the anchor selected (a small editor script reads `affibloom_location=<row>`), and the editor changes the link by hand;
  the next save re-indexes and the opportunity goes. **(C) List only** — no action. **Recommendation: A, plus B's "Edit
  in post" as a row action**: A keeps Lasso's workflow (switch, dialog, unmonetize) and the reader's result exactly,
  and is fully reversible; B is the honest way to make the change permanent in the content. O10 is written for A.
  → **Decided 2026-11-14:** A3: monetize at render time (a stored "this link → this product" assignment) plus "Edit in post".
- **N2 The Amazon branch** (R70): Lasso expands `amzn.` short links (a request to Amazon's redirector) and creates a
  product through its cloud. **Recommendation:** on an Amazon row the switch opens the same Monetize dialog with
  **Create a Link** prefilled with the row's URL (F18's add flow, Amazon data dormant until C1); short links are not
  followed (Affibloom never fetches Amazon); raw Amazon links already get the site's tag at render time (F21 D14).
  → **Decided 2026-11-14:** E16: the user accepted the recommendation
- **N3 Unmonetize of a link that is tied by its own URL** (a post's links, R30, R72): Lasso writes the scan's
  `original_link_slug` back. Under A this would be a render-time "print the original URL" override for a tied
  anchor. **Recommendation:** offer it only when the row has an `original_url` different from its URL (450 rows here,
  F21), as an assignment with product 0; otherwise the switch of a tied row is shown on and disabled with the tooltip
  "This link is in the post's content; edit the post to change it."
  → **Decided 2026-11-14:** E17: the user accepted the recommendation
- **N4 Programs from a frozen catalogue?** The 15,470 programmes are Lasso's data, synced from its cloud (R79); a copy
  would never update and is Lasso's. **Recommendation:** do not build Programs (D14), as the completion plan says.
  → **Decided 2026-11-14:** B6: the user accepted the recommendation: Program Opportunities are not built.

## 5. Scope that needs a home

- **F21 (index):** columns F22 reads — `host_id`, `host_url`, `source`, `occurrence`, `product_id`, `link_type`,
  `display_type`, `anchor_text`, `url`, `original_url`, `domain`, `is_ignored`, `lasso_location_id`; the opportunity
  index `(product_id, is_ignored, domain)` (R1 there); D10 (keep `is_ignored` across a rescan) is F22's requirement on
  35C/35F; under N1-A also `assigned_product_id` (kept the same way) and 35H's filter reading it.
- **F12 (Locations, clicks):** the preview route (36C) is reused; under N1-A Locations count assigned rows as the
  product's; the click store gives D17's per-product totals.
- **F19 (Dashboard):** O8 fills the Alert column's lightbulb, the tile and the filter tab F19 left as placeholders; the
  sort notice (R55) stays F19's.
- **F9 (Settings):** the Detect Opportunities switch joins Link Defaults (F9 says "when F22 adds those fields"); F9's
  override dialog is reused.
- **F1 (product page):** the tab and the switch replace F1's placeholders (14A tabs, 14B display card); the save
  response returns the badge count.
- **F18:** Create a Link from the Monetize dialog, with the URL prefilled (N2).
- **Task 26 (migration):** `url_details.is_opportunity` → `_affibloom_detect_opportunities`, `base_domain` →
  `_affibloom_domain`, dismissed location ids → `is_ignored` by `lasso_location_id`, `detect_opportunities`, the
  hidden intro keys (F10 AC21 already names opportunities).
- **Unowned:** WP All Import's hook that sets `is_opportunity` 0 (R40) — whoever owns Lasso's WP All Import support;
  the product tour's step on the Opportunities tab (`class-product-tour.php` 165).

## 6. Edge cases

1. A product with no URL or an unparsable one has no domain: never a suggestion.
2. Two products with the same domain: a link appears once per product (R8); dismissing hides it for both.
3. A product trashed, retired or set Detect off: its pairings leave every list and count at once (queries read live).
4. A host trashed: F21 removes its rows; untrashing re-indexes and old dismissals come back with their identity (D10).
5. The site's own domain: `home_url()`'s host, `www.` removed, compared case-insensitively; Local's
   `volleyballvault.local` and production's `volleyballvault.com` both work.
6. A search with `%` or `_`: escaped with `$wpdb->esc_like` before the spaces become wildcards.
7. Dismiss of a row already gone (rescan, other tab): 404 `affibloom_location_not_found`, the row is removed and the
   list redrawn.
8. Export with tens of thousands of rows: streamed 100 at a time by keyset; no time limit hit on 2,061 here.
9. An anchor context that cannot be found (stamp gone, content changed): the column is empty, as Lasso's.
10. Two editors dismissing or assigning the same row: the last write wins; both are idempotent.
