# F35 Auto-monetization: requirements

Status: written 2026-11-14 from a sweep of Lasso 369's auto-monetization (detected affiliate links, Amazon
auto-monetization, the affiliate-program catalogue, "extend products", Commission Upgrades). Client point **C2** is
this feature's first slice (Tasks C2A-C2C, `docs/features/C2/requirements.md`, which keeps its AC numbers); the Amazon
half is Tasks AM1-AM6. Gate 4 for Tasks C2A-C2C and AM1-AM6: waiting.

## 1. User stories and acceptance criteria

- As the site owner, affiliate links from any network Lasso knows become products by themselves, so their clicks,
  health and locations are tracked like any other product, and no post is ever rewritten.
- As the site owner, with **Enable Amazon Auto-Monetization** on, every Amazon link in my posts becomes a product and
  readers' Amazon links carry my tracking ID, the way Lasso did it, without Affibloom editing a post.
- As an editor, I can see how far the Amazon import has got and dismiss its notice.

The affiliate-network ACs are **C2's AC1-AC9** (`docs/features/C2/requirements.md`). The Amazon ACs:

- **AC1** With Auto-Monetization on, a published post holding an Amazon product link (ASIN) that no product has gets
  a product for that ASIN and store at the next import run (every 5 minutes, at most 100 links a run); a second run
  creates nothing; a retired ASIN creates nothing; the post's `post_content` is unchanged. (AM1, R28-R29, D13)
- **AC2** The new product is an Amazon product with the site's tag rule applied to its stored link and the starting
  name of decision 11 (C1 dormant); after the run the index ties the post's link to it and the front end prints the
  product's link (tag, `rel`, `target`) through F21's render filter. (AM1, R29-R30, D13, D15)
- **AC3** An `amzn.to` / `amzn.com` / `amzlink.to` link is resolved once (the short link's host is asked, the first
  redirect into an Amazon store is the destination, no Amazon page is fetched), remembered in the short-link map,
  and turned into (or tied to) the product of its destination; the front end then prints the product's full Amazon
  link where the post says `amzn.to/…`; the post is unchanged. (AM2, R32, D14, D20)
- **AC4** An Amazon product link carrying a tag that is neither the site's nor whitelisted joins **Tracking ID
  Whitelist** when Allow Multiple Tracking IDs is on, Force changes from link details is off and the tag was never
  removed by hand (blacklist); otherwise the whitelist is unchanged. Found by the import and by the index on save.
  (AM6, R28, R31, D16)
- **AC5** Settings → Amazon: switching **Enable Amazon Auto-Monetization** on with no tracking ID shows "Tracking ID
  must be set to use Auto-Amazon." and leaves it off; with one, it switches **Allow Multiple Tracking IDs** on if it
  was off and opens "Enable Auto-Amazon" ("We recommend creating a backup first. …") with **Create a Backup First**
  (switch back off, Multiple IDs restored, WordPress's backup guide opens) and **Enable**; saving it on starts an
  import at once; saving it off stops the import job. (AM3, R25-R27, D17, D18, D26)
- **AC6** While an import has work (queue > 0 or running), every Affibloom page shows the import notice ("Amazon import
  in progress", "N in Dashboard · ~M unique Amazon products still queued", "Current batch: x / y" while running,
  "P placements indexed", the info tip) refreshed every 15 seconds; on completion it reads "Amazon import complete" /
  "N unique Amazon links in Dashboard" and hides itself; **×** dismisses it for this user until the counts change.
  (AM4, R33-R34, R38-R40, D19)
- **AC7** With Auto-Monetization on, Settings → Amazon shows the "Amazon import status" card under the switch; the
  Dashboard shows the complete/idle notice in the first 24 hours after activation when placements outnumber Dashboard
  links, dismissible; Settings → General's Link Index card shows "Amazon import" (or "Complete") and "Link Index vs
  Dashboard" with Lasso's explainer under the same rule. (AM5, R35-R37, D19, D21)
- **AC8** With Auto-Monetization off, nothing above runs or shows, and turning it off mid-import stops after the
  current link. (AM1, AM3, R27, D26)

## 2. Evidence

Read in full: `classes/auto-monetize/class-auto-monetize.php` and all 32 network classes beside it
(`class-agoda.php` … `class-webgains.php`); `files/affiliates.json`, `files/impact-domains.json` (805 hosts),
`files/amazon-associates-domains.json` (21); `classes/class-affiliates.php`; `models/class-auto-monetize.php`;
`models/class-affiliate-programs.php`; `classes/class-extend-product.php`; `models/class-extend-products.php`;
`classes/background_processing/class-lasso-process-auto-monetize.php`, `class-lasso-process-add-amazon.php` (680);
`classes/class-lasso-amazon-auto-monetize-admin.php`; `admin/assets/js/amazon-auto-monetize-banner.js`;
`admin/views/notifications/{amazon-auto-monetize-import,-contextual,-settings-card,-status-inner,amazon-url-detected,
auto-amazon}.php`; `admin/views/modals/auto-monetize-flip.php`; `admin/views/rows/monetize-row.php` (the manual
picker's row: Opportunities, F22); `pages/settings-amazon/class-ajax.php`.

Read in part: `classes/class-lasso-cron.php` 120-170 (`CRONS`), 222-300 (registration: licence gate, save_post,
the auto-monetize and add-amazon actions), 365-400 (unscheduling by setting), 2589-3030 (the anchor loop: the
detection filter, the tag rule, the write-back), 4933, 4955-4962, 5028-5097 (`uncloak_amazon_link`), 5719-5776
(affiliate-program sync), 6520-6560; `pages/fix/class-hook.php` 50-75, 740-760; `classes/class-lasso-affiliate-link.php`
443-482, 800-1060 (`lasso_add_a_new_link`), 1100-1120, 1170-1185, 1650-1720, 1900-1920 (`keep_affiliate_url`),
2537-2666 (`get_lasso_post_id_by_url`), 3455-3480 (boosting); `classes/class-lasso-amazon-api.php` 31 (short-link
hosts), 656-665, 1460-1580 (`get_amazon_product_url`); `classes/class-helper.php` 230-240, 410-470, 1078-1093,
5013-5086 (whitelist and blacklist), 5830-5850, 6170 (option prefix), `get_base_domain`, `get_argument_from_url`,
`get_redirect_final_target`, `get_url_status_code_by_broken_link_service`; `classes/class-lasso-db.php` 181-205,
1585-1605, 4369-4381, 4945-5010, 6100-6140; `classes/class-redirect.php` 425-450, 500-515;
`classes/class-link-location.php` 350-375; `classes/class-setting-enum.php` 98-136; `classes/class-setting.php`
1080-1140; `classes/class-activator.php` 35-50, 60-71, 125-140; `classes/class-lasso-license.php` 244-253;
`classes/class-launch-darkly.php` 75-95; `pages/class-ajax.php` 270-300, 1015-1170, 2300-2330;
`pages/settings-general/class-ajax.php` 260-540, 1194-1225; `admin/views/settings/general.php` 40-80, 250-270,
450-520; `admin/views/settings/amazon.php` (all); `admin/views/onboarding/amazon.php` 30-100, 165-175;
`admin/views/dashboard/url-details.php` 90-110, 495-512, 2620-2660; `admin/views/footer.php` 60-130, 290-339;
`admin/assets/js/settings.js` 3436-3560; `admin/lasso-constant.php` 34-160, 783-790.

**This copy, 2026-11-14** (read only):

| What | Value |
|---|---|
| Settings | `auto_monetize_affiliates` **on**, `auto_monetize_amazon` **on**, tracking ID `vbvault-20`, multiple IDs on, whitelist `[vbvault-20]`, option `lasso_amazon_tracking_id_blacklist` `[vbvault-20]` (the slip of R31), `keep_original_url` `[]`, keep geni.us on, SiteStripe scan off, Commission Upgrades on, force changes off, link cloaking on, detect opportunities on; `lasso_enable_auto_amazon_notification` 0 (the SiteStripe notice dismissed) |
| `wp_lasso_auto_monetize` | **16 rows**: 15 Impact (7 `goto.walmart.com/c/3512787/565706/9383?…u=…` with deep links to walmart.com, 8 `mizunousa.sjv.io/<code>` with no deep link), 1 Post Affiliate Pro (`roguefitness.com/…?a_aid=62ad4e6eb812c`); every row tied to a published product (12005-12012, 14694-14801) |
| The 16 products | all `Basic Link`, `link_cloaking` 0, Primary URL = the affiliate link as written, `is_opportunity` 1; names: 4 "Walmart \| Save Money. Live better.", 4 "add-a-link-title" (the add path's fallback), 8 real page titles; 30 link-location rows in 5 posts point at them |
| Network links in content | 29 Impact hrefs (`mizunousa.sjv.io`) in 4 posts; Lasso's regex patterns match **0** of the site's other hrefs (no false positives today) |
| Amazon in content | 1,060 Amazon hrefs in 51 published posts, 183 of them `amzn.to`; every full link carries `tag=vbvault-20`, none another tag or none |
| Amazon index | 1,129 Amazon link-location rows: 899 tagged `vbvault-20`, 230 without a tag value; **455 untied** (`post_id` 0, 354 text, 101 image) = **91 ASINs that are no product** + 218 `amzn.to` rows; `lasso_amazon_shortened_url` empty; the add-amazon job last completed **2025-03-28** (option `wp_lasso_add_amazon_link_completed_time`), the auto-monetize job 2026-09-24 |
| `wp_lasso_affiliate_programs` | **15,470 rows** (first: "Affiliate+", getlasso.co), `lasso_affiliate_programs_last_modified` 2026-11-09 01:04 — Lasso's cloud catalogue (R18) |
| `wp_lasso_extend_products` | 4 Walmart rows (138705582 "Nike Essential Volleyball Knee Pads" $24.99, 224023001 "Men's Nike KD 14 NRG …" $164.95, 349496450 and 986820676 named "Walmart \| Save Money. Live better.", no price), all `is_manual` 1, updated 2026-09-24 |
| Boosting | `boosting_enabled` = 1 on 112 products; user meta `lasso_dismiss_amazon_auto_monetize_banner`: none |

## 3. What Lasso does

### 3.1 The affiliate-network registry

| # | Rule | Source |
|---|---|---|
| R1 | **Setting** "Auto-Detect Affiliate Links" (switch, Settings → General → Link Defaults, last row, wrapper `#auto-monetize-affiliates-toggle`), tooltip "Add all detected affiliate links to your dashboard as individual products."; key `auto_monetize_affiliates`, default **true**, stored as a boolean; "Stop Lasso Analytics" (licence) turns it and `auto_monetize_amazon` off | general.php 42, 510-516; lasso-constant.php 152; class-setting.php 1086; settings-general/class-ajax.php 271-290 |
| R2 | **The registry** is `files/affiliates.json`: 37 entries `{ name, slug, is_affiliate_function, matches, sub_ids }` in file order (Appendix A). `is_affiliate_link( $url, $include_amazon = true, $slug = false )` walks them in order, skips Amazon when asked, skips other slugs when a slug is given, and returns the first slug whose **method** exists and returns true, else whose `matches` regex (non-empty) matches the URL; else false. Only three named methods exist (`is_impact`, `is_amazon`, `is_post_affiliate_pro`); every other entry is decided by its regex. Data cached in the object cache for 3,600 s | class-affiliates.php 18-115, 186-210 |
| R3 | **Impact:** the URL's host without `www.` (`get_base_domain`, which removes every "www." substring), or that host without its first label, is in `impact-domains.json` (805 hosts, e.g. `sjv.io`, `7eer.net`, `evyy.net`, `pxf.io`, `goto.walmart.com`) | class-affiliates.php 124-133; class-helper.php `get_base_domain` |
| R4 | **Post Affiliate Pro:** the fragment matches `(a_aid=.+)\|(tr_aid=.+)`, or the host contains `repfitness.com` or `.ivacy.com`, or the path matches `((\.postaffiliatepro)\|(affiliates\.xeroshoes)).com\/scripts\/`, or the query matches `a_aid\|tr_aid\|a_bid` | class-affiliates.php 160-179 |
| R5 | **Amazon Associates:** the host without `www.` is one of 21 (`amazon.com`, `.com.au`, `.com.br`, `.ca`, `.cn`, `.fr`, `.de`, `.in`, `.it`, `.co.jp`, `.com.mx`, `.nl`, `.pl`, `.se`, `.sg`, `.es`, `.com.tr`, `.ae`, `.co.uk`, `amzn.com`, `amzn.to`); detection always excludes it (`$include_amazon` false) | class-affiliates.php 142-150; amazon-associates-domains.json |
| R6 | **Lasso's regexes are used as written**, including top-level alternations that are not anchored (FlexOffers `(fxo\.co)`, Sovrn, Skimlinks `(id=[0-9A-Z]+&xs=[0-9]&url=(.+))`, ClickBank, MaxBounty, Tune, AvantLink, eBay, TradeTracker `((&\|\?)tt=[0-9_]+)`) and very loose ones (CAKE `a=\d+(.)+c=\d+`, FirstPromoter `(\?\|&)(fp_ref=\|fpr=)`) | affiliates.json |
| R7 | **Auto-detected networks** are the 32 classes of `AFFILIATES_CLASSES` (Appendix B): each has a display name stored as `integration` (e.g. "Commission Junction", "MaxbBunty" — Lasso's typo, "TUNE (HasOffers)", "RentalCarsCom"), a slug that must equal its registry slug, and rules for affiliate ID, advertiser ID and deep link | class-auto-monetize.php 67-100; the 32 class files |
| R8 | **Registry-only networks** (Everflow, Aragon, Commission Factory, FirstPromoter) are affiliate links for R14, R16 and the redirect's sub IDs, but are never auto-detected (no class) | affiliates.json; class-auto-monetize.php 67-100 |
| R9 | **Extraction:** the URL is HTML-entity-decoded; query values are read case-insensitively by key and cut at a literal `u0026`; the deep link is the named parameter, HTML-decoded, and sets `domain` to its host; Partnerize's deep link is everything after `/destination:`, decoded; Impact's IDs come from `~[\d\w]+\.\w+(\/(\w{4,12}))\|(\/c\/(\d+)\/(\d+)\/(\d+))~` (affiliate ID = group 4, else group 2; advertiser ID = group 6) and its deep link is `u`. With no deep link, `domain` stays empty | class-auto-monetize.php 107-113, 256-266; class-impact.php; class-partnerize.php; class-helper.php `get_argument_from_url` |

### 3.2 Detection and creation

| # | Rule | Source |
|---|---|---|
| R10 | **Recording:** for a network whose `is_valid_url()` holds (`is_affiliate_link( url, false, slug ) === slug`), a row `{ integration, affiliate_id, advertiser_id, url, deep_link, domain, url_encrypt = md5(url) }` is inserted unless one with that `url_encrypt` exists; errors are swallowed | class-auto-monetize.php 133-169 |
| R11 | **Table** `lasso_auto_monetize`: `id` bigint PK, `domain` varchar(100), `deep_link` varchar(1024), `integration` varchar(20), `affiliate_id`/`advertiser_id` varchar(64), `url` varchar(2048), `lasso_id` bigint, `url_encrypt` varchar(32), unique index on `url_encrypt` (added by an update) | models/class-auto-monetize.php 26-61, 94-111; class-update-db.php 308-340 |
| R12 | **When detection runs:** the filter `auto_detection_affiliate_link` is added at priority 120 to `filter_atag_at_first` only if the setting is on at load; the scan applies it to every anchor with an `href` that is not inside an unsupported block and has no ignore class, before every other check, and tries all 32 classes on it. The scan runs on `save_post` (priority 1000), the 10-minute scan, rebuilds and the other triggers of F21 R10-R20, **only while Lasso's licence is active** (`lasso_license_status` 1) | fix/class-hook.php 68-71, 748-760; class-lasso-cron.php 270-292, 2605-2613 |
| R13 | **Creation, daily:** the cron `lasso_auto_monetize` (daily) runs `Lasso_Process_Auto_Monetize` only with the setting on: if its batch aged out or runs, stop; take up to 100 `url`s whose `lasso_id` is empty, 0, or not a Lasso post; skip invalid URLs; per URL (stopping if a force-scan runs) call `lasso_add_a_new_link( url, { link_cloaking: '0' } )` ("Does not change the original URL on post content."); on success store `lasso_id` (and the product's target URL and domain as deep link when the row has none — the code reads the model's property by mistake, so it never fills); logs "START Import process for: …", "Imported id: …" in `auto_monetize` | class-lasso-cron.php 132, 6526-6531; class-lasso-process-auto-monetize.php 44-121 |
| R14 | **The product made:** Lasso's add path (F18): an existing product with the URL is re-published and used; else a new one. Because the URL is an affiliate link (`keep_affiliate_url`: any non-Amazon registry match, or a domain in Keep Original URL + the built-in list), its **Primary URL is the link as written** and its type "Basic Link"; **link cloaking off** whatever the site default; the other link defaults; `is_opportunity` = Detect Opportunities; the name from the add path's fetch (page title, else the "add-a-link-title" placeholder slug, else the domain); when no image, the affiliate-program logo of the final URL's domain (R18) | class-lasso-affiliate-link.php 815-1060, 1650-1690, 1900-1918; class-setting-enum.php 111-124 |
| R15 | **Written back into the post** (by the next scan, not by detection): the anchor's URL resolves to the product through the auto-monetize table (`url` → `lasso_id`, after the cloaked slug, redirect URI and `url_details` lookups); as an uncloaked non-Amazon product its `href` is set to the product's target URL (= the link as written), and it gets `data-lasso-id` (its row), `data-lasso-lid`, `data-lasso-name`, `rel` replaced by the product's nofollow/sponsored (+ `noopener` on `_blank`) and the product's `target` (F21 R37-R39) | class-lasso-affiliate-link.php 2629-2635; class-lasso-cron.php 2747-2787, 2850-2860, 2975-3027 |
| R16 | **Other readers of the registry:** status checks of affiliate links go to Lasso's cloud (`get_url_status_code`, F11); an affiliate URL's duplicate key keeps its path and query (F18); a cloaked redirect puts an outside `sid` into the network's first sub ID and adds the click ID to its sub IDs, except TradeTracker (`DISALLOWED_CHANGE_SUBID_AFFILIATES`), and reports the click to `lasso.link/events/tracking` (F24, F12) | class-helper.php 459-464, 5844-5850; class-redirect.php 425-450, 500-515; class-affiliates.php 21, 219-327 |
| R17 | **Lasso cloud around detection:** `lasso_sync_metadata` (daily) sends `auto_monetize_affiliates`, `auto_upgrade_eligible_links` and the tracking-ID payload to Lasso; the Launch Darkly flag `auto_monetize` is read by no code; process label "Auto Monetize"; DB-status repair actions `create_auto_monetize_table`, `create_affiliate_programs_table` | class-lasso-cron.php 6533-6560; class-launch-darkly.php 82-85; class-verbiage.php 32; db-status.php 32, 39 |
| R18 | **The affiliate-program catalogue** `lasso_affiliate_programs` (id, post_title, permalink, niche, type, commission_rate, lasso_partner_rate, lasso_partner_badge, signup_page, cookie_duration, description, rating, additional_links, primary_domain, post_modified, created_at, image_url) is **downloaded from Lasso's cloud** daily: `GET https://lasso.link/data-sync/data?post_modified=<last>` with the licence headers, bulk-inserted; then `GET lasso.link/data-sync/data?<encrypted get_id>` lists IDs to keep and the rest of the range is deleted; options `lasso_affiliate_programs_last_modified`, `lasso_sync_affiliate_programs_index`. Read for the default logo (R14) and the program-opportunity query (domains in the index joined to the catalogue, excluding "Amazon Associates" and "Google AdSense") | models/class-affiliate-programs.php; class-lasso-cron.php 126, 170, 301, 5719-5776; class-lasso-affiliate-link.php 1681-1689; class-lasso-db.php 1585-1605; lasso-constant.php 787 |

### 3.3 Extend products

| # | Rule | Source |
|---|---|---|
| R19 | **Recognition:** 45 merchant hosts map to a product type (Chewy, Wayfair, Walmart, Target, Home Depot, Groupon, Best Buy US/CA, Fanatics ×5, Fansedge, Lids US/CA, NFL Shop ×4, NHL ×4, NBA ×3, NBA Store ×6, MLS ×2, MLB ×5, WWE ×4, bol.com); a URL's type is its host's, else the host of the destination embedded in its query; the product ID comes from a per-type path pattern (e.g. Walmart `/ip(/slug)?/<digits>`, Target `/p(/slug)?/-/<A-<digits>>`, Fanatics `o-…+t-…+p-…`, Best Buy CA prefixed `ca-`); country `nl` for bol.com, else `us` | class-extend-product.php 20-64, 72-290, 580-593 |
| R20 | **Data:** table `lasso_extend_products` (product_id, product_type, default_product_name, latest_price, base_url, default_image, country, out_of_stock, is_manual, last_updated; PK product_id+type+country) filled from **Lasso's cloud** (`lasso.link/link/status/?<encrypted>`, the broken-link service: productName/pageTitle, imgUrl, price, quantity, finalUrl); a 404 stamps `last_updated` and clears `out_of_stock`. Read by the add path (name, image), the product page and quick detail, `url_details` (product_id/type) and the stock check | class-extend-product.php 298-578; models/class-extend-products.php; class-helper.php `get_url_status_code_by_broken_link_service`; class-lasso-cron.php 1109-1112 |

### 3.4 Amazon auto-monetization

| # | Rule | Source |
|---|---|---|
| R21 | **Setting** `auto_monetize_amazon`: card "Auto-Monetize Amazon Links" ("Learn"), "Automatically monetize all current and future Amazon links with your Tracking ID, migrate your SiteStripe displays, and add them to your affiliate dashboard.", switch "Enable Amazon Auto-Monetization", an error line `.amazon-error`; default false, but **activation sets it true**; the onboarding step has the same switch ("… with your Tracking ID and and added to your affiliate dashboard.") | amazon.php 196-212; lasso-constant.php 124; class-activator.php 46, 137-139; onboarding/amazon.php 77-95 |
| R22 | **Switching it on:** without a tracking ID → "Tracking ID must be set to use Auto-Amazon." and the switch goes off; with one → Allow Multiple Tracking IDs is switched on if it was off (remembered in `data-old-checked`), the error cleared, then the modal "Enable Auto-Amazon": "We recommend creating a backup first. This will allow you to undo this action quickly and ensure everything runs exactly as you hoped." — **Create a Backup First** (switch off, Multiple IDs switched back if this turned it on, Lasso's backup article in a new tab), **Enable** (on only if the tracking ID is not empty). A tracking-ID change re-runs the check and the format check; Save validates the format first | settings.js 3441-3545; auto-monetize-flip.php |
| R23 | **On save** (Amazon tab): off → on with a tracking ID starts `Lasso_Process_Build_Link::link_database_limit()` (a scan); any save carrying `auto_monetize_amazon` reschedules the crons; `lasso_cron_add_amazon` (every 5 minutes) is scheduled only while it is on, `uncloak_amazon_link` (hourly) returns at once while off | settings-general/class-ajax.php 381-386; class-lasso-cron.php 147, 225-260, 380-385, 5036-5038 |
| R24 | **The import queue** (`Lasso_Process_Add_Amazon::import`, at most 100, by product ID): first the short-link map is copied to link locations; then the untied Amazon link-location rows, Amazon links in the chosen ACF fields not yet products, and `[amazon …box=…]` shortcodes; per row: a short-link host → the raw link; a SiteStripe URL → its product URL; an ASIN with a domain → `https://www.<domain>/dp/<ASIN>[?tag=<row's tag>]`; an AAWP block → the domain from AAWP's country and AAWP's associate tag; anything else → its embedded destination; not Amazon → AAWP shortcode IDs are queued as `https://amazon.<country>/dp/<id>`; the rows' tags that are not blacklisted join the whitelist | class-lasso-process-add-amazon.php 271-462; class-lasso-db.php 4945-5010 |
| R25 | **Per queued link:** stop if a force scan runs; a short link is resolved (Lasso Hub `lasso.to` from Lasso's data, else the redirect is followed) and skipped if it does not end on Amazon, its final tag joining the whitelist unless blacklisted; a **retired** ASIN is skipped; the ASIN's data is imported from AAWP's table if present, else fetched (`fetch_product_info`, Amazon API or Lasso's cloud); an existing product (by URL, then by ASIN + country) gets the short link mapped or the untied rows pointed at it; else `lasso_add_a_new_link( url )` with the site defaults, the rows pointed at the new product, "Amazon" renamed to the product's name, a search page titled by its keywords, a product without ID deleted; one second's sleep per link | class-lasso-process-add-amazon.php 64-264 |
| R26 | **Tag rule in content** (the scan): every Amazon link gets `get_amazon_product_url()`: protected (sponsored, `maas`, Creator Connections) links untouched; the site's tag replaces a tag that is not whitelisted (the whitelist counts only with Multiple IDs on; `maas` and URL-variant tags always allowed); **only when a product exists for the ASIN, or the link is a search page**; the shortened form when Shorten is on; with Multiple IDs on and Force changes off the original tag is kept. So with no products the links keep their tags: auto-monetization is what makes the scan retag them | class-amazon-api.php 1468-1580; class-lasso-cron.php 2629-2640, 2829-2846 |
| R27 | **Auto-whitelist on every scan** (not gated by the setting): an Amazon product link with a tag, Multiple IDs on, Force changes off, the tag neither blacklisted nor whitelisted → whitelisted. `update_amazon_whitelist_ids` also appends the same IDs to the blacklist (a slip: the blacklist ends up holding every whitelisted ID; it is read only by this rule and the cloud payload) | class-helper.php 5013-5086; class-lasso-cron.php 2638 |
| R28 | **Uncloak short links** (hourly, with the setting on): up to 50 link-location rows on `amzn.com`/`amzn.to`/`amzlink.to`/`lasso.to`; each is resolved; a `lasso.to` link whose ASIN + country match its product is kept; else the final URL gets the tag rule without the product check and **`update_link_in_post` rewrites the anchor in the post**, the row is updated, and a tied product's `lasso_custom_redirect` becomes that URL | class-lasso-cron.php 4933, 5028-5097; class-lasso-db.php 4369-4381; class-amazon-api.php 31 |
| R29 | **Status payload:** `running`, `queue_count` (distinct ASIN + country, or link + domain), `dashboard_count` (published Amazon products), `links_indexed` (the index's total placements), `complete` (queue 0 and not running), `batch_total`, `batch_completed`, `batch_percent` (5 while running without a total); an idle batch with no backlog is cleared | class-lasso-process-add-amazon.php 471-529, 637-677; class-lasso-db.php 181-205, 4945-4985; class-helper.php 1078-1093 |
| R30 | **Banner** (`#lasso-amazon-auto-monetize-banner`, in the notifications stack of every Lasso page): only with the setting on, queue > 0 or running, and the user's dismissed fingerprint ≠ `pending_<queue>_<dashboard>`; holds the status block and a × ("Dismiss") | class-lasso-amazon-auto-monetize-admin.php 20-50, 194-230; amazon-auto-monetize-import.php; class-lasso-process-add-amazon.php 536-606 |
| R31 | **Status block** texts: "Amazon import in progress" / "Amazon import complete"; "%1$s in Dashboard · ~%2$s unique Amazon products still queued" / "%s unique Amazon links in Dashboard"; "Current batch: %1$s / %2$s" (only while running with a total); "%s placements indexed" and an info tip "Link Index counts every placement on your site. Dashboard lists unique managed links (Amazon products are deduped by ASIN + country)." The script's own strings differ while polling: "N in Dashboard · ~M still queued" | amazon-auto-monetize-status-inner.php; class-lasso-amazon-auto-monetize-admin.php 267-281; banner.js 27-108 |
| R32 | **Contextual notice** (Dashboard only, `#lasso-amazon-auto-monetize-contextual`): when queue 0 and not running, inside the first 24 hours after activation (or Lasso's Pro warm-up), `links_indexed` > `dashboard_count`, and the dismissed fingerprint ≠ `complete_<dashboard>`; × dismisses it | class-lasso-amazon-auto-monetize-admin.php 58-68, 238-260; amazon-auto-monetize-contextual.php; class-activator.php 65-71; class-lasso-license.php 244-253 |
| R33 | **Settings → Amazon card** "Amazon import status" (the status block) after `.amazon-error` while the setting is on | amazon-auto-monetize-settings-card.php; class-lasso-amazon-auto-monetize-admin.php 141-149; banner.js 110-123 |
| R34 | **Settings → General stats** appended to `.lasso-stats` under R32's rule: "Amazon import" with "%1$s in Dashboard · ~%2$s queued", or "Complete" with "%s in Dashboard"; "Link Index vs Dashboard" with "%1$s indexed placements · %2$s Dashboard links"; the explainer sentence | class-lasso-amazon-auto-monetize-admin.php 76-133 |
| R35 | **Polling and dismissal:** every 15 s `lasso_amazon_auto_monetize_status` (nonce `lasso_ajax` in `security`, `edit_posts`, else 403 "Unauthorized.") redraws every status block; the banner fades out at completion and back in if work returns (unless dismissed); polling stops when complete and no card or notice remains; `lasso_amazon_auto_monetize_dismiss` (same checks) saves user meta `lasso_dismiss_amazon_auto_monetize_banner` = the pending fingerprint, else the complete one | banner.js 125-221; pages/class-ajax.php 2303-2326; class-lasso-process-add-amazon.php 613-629 |
| R36 | **Elsewhere:** the Dashboard's totals carry the status payload and count the import in its header clocks; the "import from another plugin" suggestion is hidden while the Amazon backlog > 0; the "Migrating from Amazon SiteStripe?" notice shows while the setting is off (C1 R72); Intercom is told `auto_amazon` | pages/class-ajax.php 280-295; footer.php 72-120, 304-330 |
| R37 | **Commission Upgrades ("link boosting")**: the site-wide "Commission Upgrades" (`auto_upgrade_eligible_links`, tooltip "Site-wide: when a brand raises commission on products you already link to, Lasso routes clicks to the higher payout at click time—including Amazon Associates and Lasso brand deals."), a per-product "Commission Upgrades" (`boosting_enabled`, "For this link only: when disabled, Lasso will not auto-upgrade this link to higher Brand Deals commissions …"), an "apply to all links" modal, a cloud sync that reverts the setting on failure, `Lasso_Process_Update_Aff_Plus` and `lasso_amazon_affiliate_plus`, the crons validate-affiliate-URL and sync-link-from-cloud (unscheduled when off), and "Boosting is disabled for this link. Hub opportunity flips are blocked." — all decided by Lasso's cloud | amazon.php 232-240, 379-391; url-details.php 95-104, 495-505; class-lasso-affiliate-link.php 1110-1120, 1176-1182, 3455-3480; class-lasso-db.php 6100-6140; class-lasso-cron.php 394-400; settings-general/class-ajax.php 388-400 |
| R38 | **Manual monetize** (`monetize_amazon_link`, `lasso_popup_monetize_link`, `lasso_unmonetized_link`: rewrite one anchor through `update_link_in_post`) belongs to Opportunities (F22); the product page's "doesn't have your Amazon Tracking ID saved" notice and `lasso_save_amazon_tracking_id` to F1 / C1 R59 | pages/class-ajax.php 1015-1170; url-details.php 2626-2657; amazon-url-detected.php |

## 4. Decisions

Settled from Lasso and the records:

- **D1 One feature, two halves.** F35 is Lasso's auto-monetization. Its affiliate-network half is client point C2
  (Tasks C2A-C2C, `docs/features/C2/`), its Amazon half Tasks AM1-AM6. **Task 6 decision 8** (only Impact and Post
  Affiliate Pro) was written before the full-scope rule of 2026-11-13; **the later rule governs**: every network Lasso
  knows is built.
- **D2 The registry is data, copied verbatim** (R2-R9): one PHP data file holding Lasso's 37 entries (name, slug,
  kind, pattern, sub IDs, and for the 32 auto-detected ones the display name and extraction rule) plus the Impact and
  Amazon host lists, read by one pure class. Lasso's regexes are kept exactly, loose ones included (R6): what Lasso
  detected, Affibloom detects, and each network has its own test from Lasso's example URLs. A network is one entry and
  one test.
- **D3 Detection rides on the content link index (F21)**, not a second parser: when F21 indexes a post (save, rebuild,
  daily pass), the detector receives that post's External anchors not tied to a product (Lasso's skips — ignore class,
  unsupported blocks — are F21's). Only with "Auto-Detect Affiliate Links" on.
- **D4 Storage:** table `{prefix}affibloom_detected_links` (Lasso's columns: `id`, `url`, `url_hash` unique, `network`,
  `affiliate_id`, `advertiser_id`, `deep_link`, `domain`, `product_id`, `detected_at`), made by F21's schema migrator.
  F21's resolver reads it in Lasso's place (R15's lookup order). Lasso's 16 rows need not be copied: the first rebuild
  re-detects them and ties them to the migrated products by URL.
- **D5 Creation in the background, through the add path.** A detection schedules a single cron run
  (`affibloom_auto_detect`, a few seconds later); a daily run (`affibloom_auto_detect_daily`, Lasso's daily job) catches
  the rest and rows whose product was deleted; at most 100 a run. Each row goes through `ProductService::create_from_url()`
  (F18: an existing product is reused, the name as F18 D3, the link defaults), with **cloaking forced off** (Lasso's
  `link_cloaking 0`). Never in the save request: the add path may make one HTTP request per link (F18 D3).
- **D6 No post is written.** Lasso's R15 write-back is replaced by F21's render filter (35H): the resolver ties the
  anchor to its product and the product's link, `rel` and `target` are printed at render time.
- **D7 The affiliate URL is kept.** A network link (any non-Amazon registry match, R8's four included) or a Keep
  Original URL domain is stored as written, not as its redirect's end (R14) — the registry's `is_affiliate_link()` is
  the rule F18's add path, F1's public link and F21 R40 read.
- **D8 Sub IDs** are carried by the registry for the cloaked redirect (F24) and the click counter (F12), which own their
  use; the click report to `lasso.link` is not built (cloud).
- **D9 Cloud items — C, never:** the affiliate-program catalogue and its daily download (R18; not migrated, its logo
  default is F1 D16's left-out item), the program-opportunity join (F22 gets no catalogue), the metadata sync and
  Launch Darkly flag (R17), Lasso Hub `lasso.to` resolution (R25), extend-product data (R20), Commission Upgrades
  (R37; C1 N1), Intercom (R36). Lasso's licence gate on scans (R12) has no Affibloom equivalent.
- **D10 Extend products:** their data is cloud (D9). Their URL recognition (R19) is local and pure; it is not part of
  creation here, because F18 D3 names any non-Amazon link the same way; it is handed to F11/F18 (section 5).
- **D11 Amazon import (R24-R25)** runs as cron `affibloom_amazon_import` every 5 minutes while the setting is on, 100
  links a run, one at a time, no sleep (the next link waits for the next run when the time budget is spent). Its queue
  is F21's untied Amazon rows (text and image), ACF fields F21 indexes and AAWP shortcodes/blocks F21 indexes (Task 27
  draws them). Each link goes through `create_from_url()` (Tasks 12A-12E: tag rule, existing ASIN + store, revived
  products), so a new product has the starting name of decision 11 and no data until C1 has credentials. A retired ASIN
  (F1 repoint) is skipped. AAWP's own product table is read only if Task 27 reads it.
- **D12 Product without ID deleted** (R25's last step) is not needed: `create_from_url()` never makes one.
- **D13 Amazon links carry the tag through the product, at render time.** AM1 makes the products; F21's resolver ties
  the anchors; 35H prints the product's link, which already holds the tag rule. That is R26's visible result. Untied
  Amazon links are printed by F21 D14 **with Lasso's gate**: retagged only when a product exists for the ASIN, or on a
  search page (correction to F21 D14, section 5).
- **D14 The short-link map:** table `{prefix}affibloom_amazon_short_links` (`short_url` unique, `final_url`,
  `product_id`, `resolved_at`), Lasso's `lasso_amazon_shortened_url`. A short link is resolved by F2 §5's rule (its own
  host asked, the first redirect into an Amazon store is the destination, Amazon pages never fetched); an unresolved
  link is retried on the next run, at most three times. F21's resolver reads the map, so `amzn.to/…` anchors tie to the
  product of their destination. Migration copies Lasso's table (empty here).
- **D15 R26's "with products only" rule** is Lasso's and kept: a site with Auto-Monetization off sees its Amazon links
  with the tags as written, except search pages (F21 D14).
- **D16 Auto-whitelist (R24, R25, R27)** is one domain rule, `TrackingIds::learn( tag )`, called by the import and by
  F21's indexer for each Amazon product link it indexes. Affibloom's blacklist holds only IDs removed by hand (F17 B1);
  Lasso's slip of copying whitelisted IDs into it has no visible effect and is not repeated.
- **D17 The enable flow (R22)** is built on F17's switch: Lasso's validation text, the automatic Multiple IDs switch,
  the dialog with Lasso's title, body and buttons; **Create a Backup First** opens WordPress's own backup guide
  (`https://wordpress.org/documentation/article/wordpress-backups/`) instead of Lasso's article. Activation does **not**
  switch the setting on: Affibloom's fresh-install default is Lasso's constant (off, F17 AC6), and the live value comes
  over in migration (F10 S17). The onboarding switch belongs to onboarding (C1 R75).
- **D18 Turning it on** schedules an import run at once; F21's rebuild runs only if the index is empty (Lasso's
  `Build_Link` scan is F21 R20's trigger).
- **D19 Status and notice (R29-R35):** `GET /affibloom/v1/amazon-import` (the payload) and `POST
  /affibloom/v1/amazon-import/dismiss` (user meta `affibloom_amazon_import_dismissed`, Lasso's fingerprints); a plain
  script polls every 15 s and redraws every status block with **one** set of texts (the template's; R31's second set is
  Lasso's inconsistency); the notice lives in Affibloom's notices area (Aurora's notice component). Capability
  `Capabilities::PRODUCTS` (Lasso: `edit_posts`; every Affibloom page needs it anyway). The 24-hour window counts from
  Affibloom's activation (Lasso's Pro warm-up is licence-bound).
- **D20 No uncloak job (R28).** Its visible result — readers see the full tagged Amazon link — comes from D14 + D13;
  the stored short link stays in the post. A product created from a short link stores the destination (F2 §5), which
  is R28's `lasso_custom_redirect` change.
- **D21 Settings → General stats (R34)** are drawn in F21's Link Index card (35G) by AM5.
- **D22 Messages:** Lasso's strings without the product name; "Tracking ID must be set to use Auto-Amazon." and the
  dialog title "Enable Auto-Amazon" kept (they name Lasso's feature, not Lasso).
- **D23 Turning it off** (R23, AC8) unschedules `affibloom_amazon_import`; a running batch stops after its current
  link. Turning Auto-Detect off unschedules both detection events and stops recording.

**Needs the user** (each would replace part of Lasso):

- **N1 The name of a detected product.** Lasso names it by its add path (page title; here 4 of 16 came out as
  "add-a-link-title" and 4 as "Walmart | Save Money. Live better."); C2's AC1 (2026-11-12) said "named from the link
  text". **Recommendation:** Lasso's way through the shared add path (F18 D3, embedded destination's site name when there
  is one), so a detected product is named like a pasted one; C2 AC1 is corrected so and `tests/E2E/acceptance/C2.spec.ts`
  AC1's title check must follow. Alternative: link text first, then F18 D3.
- **N2 Amazon short links stay `amzn.to` in posts (R28).** Readers get the full link through the render filter; feeds,
  e-mails and builders the filter does not reach (F21 N1) keep `amzn.to` (183 anchors here, all already on the site's
  tag via Amazon's redirect). Resolving needs one request to Amazon's short-link host (no store page). **Recommendation:**
  accept.
- **N3 The first import at cutover.** Lasso's Amazon job stalled here after 2025-03-28 (UNVERIFIED why; the setting is
  on): 91 ASINs in posts are no product and 218 `amzn.to` rows are untied. With the live value carried over, AM1 will
  create about 91 Amazon products (starting names, no data until C1) and resolve the short links within an hour or two.
  **Recommendation:** keep Lasso's behaviour (it is what the site's setting asks for), run it on staging first and
  review the list; alternative: carry the setting over as off and let the user switch it on.

## 5. Scope that needs a home

- **F21:** D3's hook (an "indexed" event with the post's untied External and Amazon anchors), the resolver reading D4's
  table and D14's map, D16's `learn()` call, and **D14 of F21 corrected**: untied Amazon links are retagged at render
  time only on search pages or when a product has the ASIN (R26). 35H prints AC2's and AC3's links.
- **F9 (Task 9B):** the "Auto-Detect Affiliate Links" switch and its tooltip (R1); this feature reads `auto_detect_links`.
- **F17 (Task 10F, built):** the Amazon switch; AM3 adds R22's behaviour to it.
- **F18 / F11:** extend-product recognition (R19) for the add path's duplicate key and the stock check (F11 N2); its data
  is cloud (D9).
- **F24 / F12:** sub IDs on cloaked redirects and the `sid` pass-through (R16), read from the registry (D8).
- **F22:** the manual monetize actions (R38) and the program opportunities without the catalogue (D9).
- **F19:** the Dashboard header clocks counting the import (R36).
- **Import (Task 27):** AAWP's product table and shortcodes as import sources (R24); the import suggestion hidden while
  the backlog is not empty (R36).
- **Migration (Task 26):** `auto_monetize_affiliates` → `auto_detect_links`, `auto_monetize_amazon` (already F10 S12, S17),
  `lasso_amazon_shortened_url` → D14's table, the blacklist; not the catalogue, the extend products' cloud data or the
  auto-monetize rows (D4).
- **Tools / DB status:** Lasso's repair actions for its tables (R17) have Affibloom equivalents only for D4's and D14's
  tables, through F21's migrator.

## 6. Edge cases

1. A URL matching two registry entries: the first in Lasso's order wins (R2), and the detected network is the class
   whose slug equals it — so a URL whose first match is a registry-only network (R8) is not detected.
2. HTML entities in hrefs (`&amp;`) are decoded before matching and extraction (R9); the stored URL is the decoded one,
   as Lasso's.
3. The same URL in many posts: one row (unique hash), one product.
4. A product deleted for good: the row loses its product; the daily run creates a new one if the link is still in a
   post (Lasso's `get_all_except_lasso`). A trashed product is still a product: its row keeps it and nothing is made
   (Lasso: the row's `lasso_id` is still a Lasso post).
5. An add that fails (fetch error): the product is still made with the fallback name (F18 D3); a storage error leaves the
   row without product for the next daily run.
6. Turning a setting off mid-run: the run checks the setting before each link (D23).
7. Two runs at once: a lock option per job (`affibloom_auto_detect_lock`, `affibloom_amazon_import_lock`, 10-minute
   expiry, Lasso's "aged out") makes the second return.
8. A short link that never resolves: three tries, then left untied and counted out of the queue (D14); an ASIN Amazon
   no longer sells is created anyway (no data to know otherwise, C1 dormant); a retired one is skipped.
9. A detected link whose network pattern is loose (R6) in an ordinary URL: a product is made, as in Lasso; the editor can
   trash it (edge 4: it stays trashed); deleting it for good while the link is still in a post makes it again, as in
   Lasso.
10. Polling after logout or a lost nonce: the status route answers 401/403 and the script stops polling.

## Appendix A — the registry (Lasso's `files/affiliates.json`, in order)

| Slug | Name | Rule | Sub IDs |
|---|---|---|---|
| awin | Awin | `/^https?:\/\/(www\.)?((awin1\.com\/(awclick\|cread\|pclick)\.php)\|(tidd\.ly))/` | clickref, clickref2-6 |
| cj | CJ | `/^https?:\/\/(www\.)?((anrdoezrs\.net)\|(jdoqocy\.com)\|(tqlkg\.com)\|(tkqlhce\.com)\|(dpbolvw\.net)\|(jqoqocy\.com)\|(kqzfj\.com)\|(kqzyfj\.com)\|(ftjcfx\.com)\|(lduhtrp\.net)\|(qksrv\.net)\|(cj\.dotomi\.com))/` | sid |
| financeads | FinanceAds | `/^https?:\/\/(www\.)?financeads\.net\/tc\.php/` | subid |
| impact | Impact | host list (R3) | subId1-3 |
| shareasale | ShareASale | `/^https?:\/\/(www\.)?shareasale(-analytics)?\.com\/(.)+\.cfm/` | afftrack |
| amazon_associates | Amazon Associates | host list (R5) | — |
| partnerize | Partnerize | `/^https?:\/\/(www\.)?((partnerize\.com)\|([a-z0-9]*\.?prf\.hn))/` | pubref |
| flexoffers | FlexOffers | `/^https?:\/\/(www\.)?(track\.flexlinks(pro)?\.com)\|(fxo\.co)\|(pav04trk\.com)/` | fobs |
| refersion | Refersion | `/^https?:\/\/(.)*refersion\.com/` | subid |
| postaffiliatepro | Post Affiliate Pro | method (R4) | data1, data2 |
| avantlink | AvantLink | `/^https?:\/\/((www\|classic)\.avantlink\.com\/click\.php)\|(snp\.link)\|(alnk\.to)/` | ctc |
| pepperjam | Pepperjam (Ascend) | `/^https?:\/\/(www\.)?((pepperjam\.com)\|(pepperjamnetwork\.com)\|(pjtra\.com)\|(gopjn\.com)\|(pjatr\.com)\|(pntra\.com)\|(pntrs\.com)\|(pntrac\.com))/` | sid |
| everflow | Everflow | `/^https?:\/\/(www\.)?everflow\.io/` | sub1-3 |
| tune | Tune (HasOffers) | `/((\?\|&\|\/)(aff_c\|offer_id)=)\|^https?:\/\/(redirect\.outdoorsy\.com)\|(offer\.zenmate\.com)\|(privateinternetaccess\.com)\|(cybertool\.co)\|(go2cloud\.org)/` | aff_sub, aff_sub2-5 |
| partnerstack | PartnerStack | `/^https?:\/\/link\.technologyadvice\.com/` | sid1, sid2 |
| goaffpro | GoAffPro | `/^https?:\/\/(www\.)?goaffpro\.(.)*\.com(.)*ref=/` | — |
| aragon | Aragon | `/^https?:\/\/((www\.)?((aragon-advertising\.com)\|(jwclicks\.com)))/` | — |
| sovrn | Sovrn | `/https?:\/\/(redirect\.viglink\.com)\|(sovrn\.co\/[0-9a-z]+)/` | — |
| rakuten | Rakuten | `/^https:\/\/(www\.)?(click\.)?linksynergy(\.walmart)?\.com/` | u1 |
| skimlinks | Skimlinks | `/^https?:\/\/(www\.)?(fave\.co)\|(go\.skimresources\.com)\|(go\.redirectingat\.com)\|(go\.effortlessgent\.com)\|(id=[0-9A-Z]+&xs=[0-9]&url=(.+))/` | xcust |
| agoda | Agoda | `/^https?:\/\/www.agoda.com\/partners\/(.+)cid=\d+/` | tag |
| bol | Bol.com | `/^https?:\/\/(partner\|partnerprogramma)\.bol\.com\/click\/click/` | subid |
| booking | Booking.com | `/^https?:\/\/(www.)?booking\.com(.+)aid=/` | label |
| cake | CAKE | `/a=\d+(.)+c=\d+/` | s1-s3 |
| clickbank | ClickBank | `/^https?:\/\/((.+)?hop\.clickbank\.net)\|(www\.rocketlanguages\.com(.+)(\?\|&)aff=(.+))/` | tid |
| commission_factory | Commission Factory | `/^https:\/\/t\.cfjump\.com\/\d+\/(.+)/` | UniqueId |
| daisycon | Daisycon | `/^https?:\/\/[a-z0-9]{3,5}\.(net\|nl)\/c\/\?si=[0-9]{1,}/` | ws |
| ebay | eBay Partner Network | `/^(https:\/\/.*ebay(\.[\w]{2,3})?(\.[\w]{2,3})\/(.+)campid=)\|(https:\/\/ebay\.us)/` | customid |
| firstpromoter | FirstPromoter | `/(\?\|&)(fp_ref=\|fpr=)/` | fp_sid |
| get_your_guide | GetYourGuide | `/^https?:\/\/(www.)?getyourguide\.(com\|de\|ch\|fr\|nl\|no\|pl\|com\.mx\|fi\|pt\|com\.br\|se\|ru\|com\.tw\|co\.kr\|com\.tr\|co.jp\|at\|dk\|co\.uk\|es\|it)(.+)(\?\|&partner_id=)/` | cmp |
| idevaffiliate | iDevAffiliate | `/(\/idevaffiliate\.php\?(.)*id=[\d]+)\|(\.idevaffiliate\.com\/[\d\/]+\.html)/` | tid1, tid2 |
| linkconnector | LinkConnector | `/^https?:\/\/www\.linkconnector\.com\/ta\.php/` | atid |
| maxbounty | MaxBounty | `/^https?:\/\/(www\.)?mb[0-9]{2,3}\|afflat3[a-z][1-2]\.com\/lnk\.asp/` | s1, s2 |
| rentalcarscom | RentalCars.com | `/^https?:\/\/www\.rentalcars\.com(.+)(\?\|&)affiliateCode=/` | adcamp |
| tradedoubler | Tradedoubler | `/clk(.)*\.tradedoubler\.com\/click\?(.)*p=\d/` | epi |
| tradetracker | TradeTracker | `/(https?:\/\/tc\.tradetracker\.net\/\?c=[0-9]{1,})\|((&\|\?)tt=[0-9_]+)/` | r |
| webgains | Webgains | `/^https:\/\/track\.webgains\.com/` | clickref |

(`\|` is a regex `|` escaped for the table.)

## Appendix B — the 32 auto-detected networks (`classes/auto-monetize/`)

Stored name (`integration`) → affiliate ID / advertiser ID / deep link (query keys read case-insensitively, R9):

| Class | Stored name | Affiliate ID | Advertiser ID | Deep link |
|---|---|---|---|---|
| Agoda | Agoda | — | — | — |
| AvantLink | AvantLink | — | `mi`, else `merchant_id` | `url` |
| Awin | Awin | `awinaffid` | `awinmid` | `ued` |
| Bol | Bol | — | — | `url` |
| Booking | Booking | `aid` | — | — |
| Cake | CAKE | — | — | — |
| CJ | Commission Junction | — | — | `url` |
| ClickBank | ClickBank | — | — | — |
| Daisycon | Daisycon | — | — | — |
| Ebay | eBay Partner Network | — | — | `mpre` |
| FinanceAds | FinanceAds | — | — | — |
| FlexOffers | FlexOffers | — | — | `url` |
| GetYourGuide | GetYourGuide | — | — | — |
| GoAffPro | GoAffPro | — | — | — |
| IDevaffiliate | IDevAffiliate | — | — | — |
| Impact | Impact | regex group 4, else 2 (R9) | regex group 6 | `u` |
| LinkConnector | LinkConnector | — | — | `url` |
| MaxBounty | MaxbBunty | — | — | — |
| Partnerize | Partnerize | — | — | after `/destination:`, URL-decoded |
| PartnerStack | PartnerStack | — | — | — |
| Pepperjam | Pepperjam (Ascend) | — | — | `url` |
| PostAffiliatePro | Post Affiliate Pro | — | — | — |
| Rakuten | Rakuten | `id` | `mid` | `murl` |
| Refersion | Refersion | — | — | — |
| Rentalcars | RentalCarsCom | — | — | — |
| ShareASale | ShareASale | `u`, else `userid` | `m`, else `merchantid` | `urllink` |
| Skimlinks | Skimlinks | `id` | — | `url` |
| Sovrn | Sovrn | — | — | `u` |
| Tradedoubler | Tradedoubler | `a` | — | `url` |
| TradeTracker | Tradetracker | `a` | `m` | — |
| Tune | TUNE (HasOffers) | `aff_id` | — | — |
| Webgains | Webgains | `wglinkid` | `wgprogramid` | `wgtarget` |

`integration` is `varchar(20)`: "eBay Partner Network" (20) fits; Affibloom's column is wider (255).
