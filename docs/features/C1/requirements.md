# C1 Amazon product data and everything Amazon: requirements

Status: rewritten 2026-11-14 from a sweep of Lasso 369's Amazon subsystem (beyond link parsing, which is F2). The
first version (2026-11-12) is kept in its decisions: C1 is an **upgrade** that needs the client's Creators API
credentials and ships dormant without them (the user, 2026-11-12; decision 11). Gate 4 for Tasks C1A-C1P: given by the user 2026-11-14.

**What this feature is:** where Amazon product data comes from (Lasso's two clients), when it is fetched (add, Refresh,
the scheduled job, bulk sync, the Marketplace search), how it is stored and shown (price, discount, timestamp, Prime,
availability), and the behaviours F17's section 4 hands to "Amazon data (C1)" plus the Amazon link forms (geni.us,
deep links, local store) and Amazon notices. Built already and not re-planned: parsing and storing Amazon links (F2,
Tasks 12A-12E), the Settings → Amazon controls (F17, Task 10F), the product page's Amazon Image field, read-only
price and tracking-ID notice (F1, Tasks 14B, 14D).

## 1. User stories and acceptance criteria

- As an editor, when I add an Amazon link, the product's name and image fill in from Amazon, so I only adjust them.
- As an editor, I refresh a product's Amazon image and price from its product page.
- As the site owner, I enter the Creators API credentials once in Settings → Amazon and can check them; until then
  every Amazon-data control says plainly that it is unavailable.
- As a reader, I see the same box as today: Prime logo, price, discount and "as of" time where Lasso showed them.

Existing criteria (numbers and meaning kept; `tests/E2E/acceptance/C1.spec.ts` refers to them):

- **AC1** With valid credentials, adding an Amazon link or ASIN creates a product whose name and image come from
  Amazon (corrected 2026-11-14: "and whose Prime flag matches Amazon's" is removed — Lasso 369 never fetches Prime;
  the Creators request asks for no Prime resource and no fetch path writes `is_prime`, R14, R44; the logo follows
  AC12). (C1C)
- **AC2** Without credentials, or when Amazon does not answer, adding still works: the product is created with the
  fallback name (F2 section 6) and no image, and no error stops the editor. (C1C)
- **AC3** `Validate` reports whether the credentials work, without saving anything else (on success Lasso stores the
  five credential fields it was given, and nothing else; R22). (C1A)
- **AC4** `Refresh` on the product page updates the image (and the cached price and availability) from Amazon and keeps
  the editor's name unless it is a placeholder ("Amazon", empty, "Add a Link Title") (corrected 2026-11-14: "and
  Prime flag" removed, as AC1; R33). (C1D)
- **AC5** The secret never appears in a REST response, the CSV export or the page source. (C1A)
- **AC6** Data taken from the API is refreshed at least every 24 hours, and an image from the API is kept only as a
  link; a name or image the editor set by hand is never overwritten by a refresh. (C1E)

New criteria:

- **AC7** With no credentials, Settings → Amazon's Product Data API card says "Not set up"; the product page's Refresh
  stays disabled with "Needs the Amazon API"; Add A New Link's Amazon tab and the bulk sync banner say the same; no
  request leaves the site. (C1A, C1D, C1M, C1I)
- **AC8** Validate with a field missing shows "Missing required fields: Credential ID, Secret, Version, Partner tag"
  (the missing ones); a stubbed success shows "Amazon Creators API credentials verified successfully." and the card
  says "Validated"; a stubbed rejection "Invalid Amazon Creators API credentials."; a stubbed 503/429 "Service
  temporarily unavailable." and the saved fields are kept. (C1A)
- **AC9** `wp affibloom amazon get <ASIN> [--country=<cc>]` prints the stubbed item's title, image, price, savings,
  availability and features, or the error code and message. (C1B)
- **AC10** The scheduled job refreshes only published Amazon products with an ASIN whose cache is older than 24 hours
  (or missing), oldest first, at most Lasso's batch, only those with Locations while "Only update products with
  Locations" is on, and nothing while "Update Amazon pricing daily" is off. (C1E)
- **AC11** An Amazon product's box shows the cached price formatted for its currency; with "Show Discount Pricing" on
  and a basis above the price, the basis struck through before it; the "as of" timestamp line with its tooltip shows
  exactly when Lasso's rule shows it; the product page's read-only price matches. (C1F)
- **AC12** The Prime logo shows on every Amazon product with an ASIN while "Show Prime Logo In Displays" is on, inside
  the price block even when the price is hidden, and on none while it is off (Lasso's rule, R46). (C1G)
- **AC13** A fetch that says out of stock records the product's out-of-stock issue (`000`) for link health (F11); one
  that says not found records a broken (`404`) issue; a later in-stock fetch resolves the out-of-stock issue. (C1H)
- **AC14** With Amazon products pending sync, Affibloom's pages show "Bulk Amazon sync" with "N Amazon links are ready
  to sync product data."; **Sync Amazon data** runs, shows "Syncing… N%", then "Sync complete." with Succeeded /
  Skipped / Failed counts and up to five failures; × hides it for this user until the pending set changes. (C1I)
- **AC15** Add A New Link's Amazon tab searches Amazon (stubbed), lists title, image and price, adds one or up to ten
  selected products with their data, and says "You can select up to 10 products." at the eleventh. (C1M)
- **AC16** Changing a product's link to another ASIN takes the new product's image, and its name only when "Keep Title
  When URL Is Replaced" is off or the name is a placeholder. (C1O)
- **AC17** With "Keep Geni.us Formatting" off, a pasted or saved `geni.us` link is stored as its destination and the
  geni.us link kept as the product's old link; on, it is stored as pasted. (C1K)
- **AC18** "Mobile Deep Linking", "Send Readers to Their Local Store" and "Commission Upgrades" show their stored value
  and a plain note that they did their work in Lasso's cloud and have no effect in Affibloom (per the user's answer,
  section 4). (C1L)
- **AC19** The "Migrating from Amazon SiteStripe?" notice shows while Auto-Monetization is off until dismissed, and its
  × keeps it dismissed for the site. (C1N)
- **AC20** Turning "Keep Geni.us Formatting" off and saving queues the job that re-saves every product whose link is a
  geni.us link as its destination; turning it on again stops it. (C1P)

## 2. Evidence

Read completely (Lasso 369, `D:\lasso\lasso`): `classes/class-lasso-amazon-api.php` (1-2611);
`classes/class-lasso-amazon-creators-api.php` (1-751); `models/class-amazon-{products,tracking-ids,shortened-url,
affiliate-plus}.php`; `classes/background_processing/class-lasso-process-{add-amazon,update-amazon,bulk-sync-amazon,
update-aff-plus,remove-genius-formatting}.php`; `classes/class-lasso-bulk-sync-amazon-admin.php`;
`admin/assets/js/amazon-bulk-sync-banner.js`; `classes/class-pro-marketplace-catalog.php`; `admin/views/settings/
amazon.php`; `pages/settings-amazon/class-ajax.php`; `admin/views/notifications/{amazon-bulk-sync-pending,
amazon-not-configured,amazon-credentials-update,auto-amazon,amazon-url-detected,amazon-auto-monetize-settings-card,
amazon-auto-monetize-contextual,amazon-auto-monetize-import}.php`; `admin/views/onboarding/amazon.php`;
`admin/views/partials/lasso-price-quill-palette.php`; `libs/amazon_api_v5/amazon-api-v5.php` (signatures only).

Read in part: `pages/settings-general/class-ajax.php` 255-1245 (save, Validate, cloud syncs);
`admin/assets/js/settings.js` 91-125, 2256-2445, 3441-3550; `classes/class-helper.php` 196-604 (status, BLS, redirects,
short-link cache), 1110-1128, 1440-1505, 2635-2665, 3085-3125, 4535-4660, 5005-5085, 6359-6378, 6951-6975;
`classes/class-lasso-db.php` 2005-2100, 3534-3750, 4315-4535, 5640-5660, 6040-6140; `classes/class-lasso-cron.php`
120-410, 805-880, 1055-1175, 4920-5170, 6470-6570, 6925-6988 (Amazon lines from a full grep); `classes/
class-lasso-affiliate-link.php` 125-600, 820-1045, 1140-1160, 1375-1400, 1470-1720, 1935-1955, 2790-3030, 3590-3620,
3865-4106 (from a full grep); `pages/class-ajax.php` 1100-1170, 1880-1925, 2222-2302, 2462-2520; `pages/class-hook.php`
1303-1333, 1630-1797; `pages/fix/class-hook.php` 945-990; `pages/url-details/class-ajax.php` 95-360, 490-506;
`admin/views/dashboard/url-details.php` 125-170, 326-412, 535-545, 1188-1290, 1487-1525, 1780-1795, 2620-2662;
`admin/views/displays/{single.php 25-260, layout-6-box.php 55-135}` and the Prime/price lines of every display;
`classes/class-lasso-shortcode.php` 250-345; `admin/views/footer.php` 60-140, 276-300; `admin/views/header-new.php`
78-135; `admin/views/settings/general.php` 518-534; `admin/lasso-constant.php` 36-160, 786; `classes/class-setting.php`
247-249; `admin/assets/js/connect-snippet.min.js` (searched for the deep-link, local-store and redirect logic).
Affibloom: `docs/features/{C1,F2,F17,F18}`, F1 requirements (Amazon rows), the C1 outline in `plans/00-overview.md`,
`reports/2026-11-12-{lasso-amazon-product-data,account-access-check}.md`, `plans/archive/dashboard-completion-plan.md`,
`docs/decisions/{client-and-ui,task-6-decisions}.md`, `tests/E2E/acceptance/C1.spec.ts`, `templates/admin/
settings-amazon.php`, `src/` (listing).

**This copy, 2026-11-14** (SELECT only):
- `wp_lasso_amazon_products`: **501 rows**, all `us`, all USD or empty (463 USD, 38 empty). Filled: name 501, image 501
  (all `m.media-amazon.com`), base and monetized URL 501, price 465, savings amount/percent 163, basis 168, features 231,
  out_of_stock = 1: 139, is_prime = 1: 110, `is_manual` = 1: 259; **rating and reviews 0 everywhere**. `last_updated`
  2023-08-31 06:20 … 2026-11-09 00:11 (166 rows on 2026-11-09; 335 rows older than 24 h before the newest).
- Published Amazon products 233, 226 with an ASIN, **every one with a cache row**; of those 214 have a price, 41 a
  discount basis, 109 are out of stock, **1 has `is_prime` = 1**; 60 are more than 24 h older than the newest refresh;
  186 have a Location (the locations-only rule). Open issues: 109 out of stock (`000`), 3 broken (`404`).
- Lasso's pending-sync predicate finds **3** products (the live banner's "3 Amazon links are ready to sync"); one user
  has dismissed a banner.
- Product meta: `price` set on 186 published products; `show_price` 0 on 216, empty on 38 (none 1); `boosting_enabled`
  112 rows; `is_aff_plus` = 1 none; `old_genius_url` none; geni.us links none. `lasso_custom_thumbnail` on Amazon
  products: 209 `m.media-amazon.com`, 24 local uploads. Tags in Amazon links: `vbvault-20` only (230).
- `wp_lasso_amazon_tracking_ids` **13 rows** (Lasso's seed), `tracking_id` empty in all; `wp_lasso_amazon_shortened_url`
  **0**; `wp_lasso_amazon_affiliate_plus` **0**; about 30 `amzn_to_<code>` / `_page_title` options (Lasso's per-short-link
  cache, R60); no Creators token transient; `lasso_amazon_valid` = 1.
- Settings (`settings.json` and `lasso_settings`): Creators fields all empty; PA-API 5.0 keys present (20/40 characters,
  dead, 403); tracking ID `vbvault-20`, whitelist `[vbvault-20]`, multiple IDs on, **update pricing daily off**,
  locations only on, default country `us`, Prime on, discount off, auto-monetize on, Commission Upgrades on, force
  changes off, keep title on, shorten on, deep linking on, local store on, out-of-stock days 0, geni.us formatting on,
  SiteStripe scan off, site **Show Price off**, `general_disable_amazon_notifications` off,
  `amazon_add_tracking_id_to_attribution` on (a Lasso 370 key; no reader in 369).

## 3. What Lasso does

### 3.1 Clients, credentials, hosts

| # | Rule | Source |
|---|---|---|
| R1 | **Two clients.** `fetch_product_info( asin, store, updated_at, link, ver, force_bls, refresh_image )` strips any `_country` suffix, then uses the **Creators API** when its credentials are *configured* (not necessarily verified) and the link is empty or Amazon's; otherwise, or when Creators fails with anything but NotFound, **Lasso's cloud ("BLS")** | amazon-api 755-817, 1798-1843 |
| R2 | **Configured** = Credential ID, Secret, Version and Partner tag all non-empty after trim. **Verified** = configured and `sha256( json{credential_id, secret, credential_version, partner_tag, country} )` equals the option `OPTION_AMAZON_CREATORS_VERIFIED_SIGNATURE` (`hash_equals`) | amazon-api 1660-1750 |
| R3 | **Creators supplement from the cloud:** after a Creators success with quantity 0 or no positive amount, Lasso asks BLS and takes its quantity and price when BLS answered 200 with a title, storing again | amazon-api 776-790, 1761-1786 |
| R4 | **After every Creators success** the action `lasso_fetch_amazon_product_api` sends `url` (tag removed), name, image, price, quantity, features, categories, savings to **`PUT https://lasso.link/link/amazon`** (AES-encrypted, licence headers), unless price is empty or quantity 0 | amazon-api 792; class-hook 78, 1303-1333; setting-enum 102 |
| R5 | **BLS request:** `GET https://lasso.link/link/status/?<AES{url, ver?, force_bls?, refresh_image?}>` with `get_lasso_headers()`, timeout 25 s (20 s in background), paced 0-499 ms in cron; a URL that returned 404 more than the limit is not asked again (`get_total_404_request_to_bls`). Response `status, productName, pageTitle, imgUrl, price, quantity, finalUrl, additionalData{currency_name, basis_price, saving_amount, saving_amount_percent}`; "Amazon.com:" stripped from the name; stored `is_manual` = 1 | helper 271-330; amazon-api 830-972; cron 805-860; constant 787 |
| R6 | **Other cloud calls in this subsystem:** short links and geni.us via `GET lasso.link/link/final-url/` when the local request fails, is 403 or the host is cloud-only (helper 437-513); **daily `POST lasso.link/encrypt/amazon` sending the verified Creators Credential ID, Secret, partner tag, country and version** (cron 6483-6525, scheduled only while verified, 387-391); Commission Upgrades `POST lasso.link/affiliate-plus/wp/validate-links` daily (update-aff-plus 47-123; cron 6932); tracking ID and whitelist pushed to `lasso.link/users/metadata` and site settings after every Amazon save (settings-general 589, 1116-1245) and daily (cron 6540-6560); the hourly cloud → site link sync writing `amazon_data` into the cache (sync-link-from-cloud 74-77); the Marketplace search `GET lasso.link/pro/marketplace/products` (R70); the front-end snippet `js.codedrink.com`, beacon `codedrink.com/js/e`, Amazon clicks redirected through `https://lasso.to/amazon?url=…` (R65) | as cited |
| R7 | **Amazon directly:** token endpoints by Version: 2.1 `creatorsapi.auth.us-east-1.amazoncognito.com/oauth2/token`, 2.2 `…eu-south-2…`, 2.3 `…us-west-2…` (form body, scope `creatorsapi/default`); 3.1 and anything else `https://api.amazon.com/auth/o2/token`, 3.2 `api.amazon.co.uk`, 3.3 `api.amazon.co.jp` (JSON body, scope `creatorsapi::default`); all `grant_type=client_credentials` with client id and secret | creators-api 16-20, 365-423, 607-633 |
| R8 | **Token cache:** transient `lasso_creators_token_<md5(client_id|version|secret)>`, lifetime `max( 60, expires_in - 60 )` (expires_in default 3600) | creators-api 340-357 |
| R9 | **Item request:** `POST https://creatorsapi.amazon/catalog/v1/getItems`, headers `Authorization: Bearer <token>, Version <v>` (2.x) or `Bearer <token>` (3.x), `Content-Type: application/json`, `x-marketplace: <www.amazon.xx>`; body `{ itemIds:[ASIN], itemIdType:"ASIN", marketplace, partnerTag, resources }`; resources `images.primary.{large,medium,small}`, `itemInfo.title`, `itemInfo.features`, `offersV2.listings.{price,availability}`, `parentASIN`; **one ASIN per call** (the client takes a list; every caller passes one); timeout 10 s; ASIN upper-cased | creators-api 22-31, 77-122, 220-269, 593-599, 640-663 |
| R10 | **Marketplace:** the country from the link's host (`get_aff_link_and_flag` code) else the setting `amazon_default_tracking_country`, mapped by `get_amazon_api_countries()` to `www.amazon.xx`, else `www.amazon.com` | amazon-api 208-427, 1688-1704; creators-api 460-477 |
| R11 | **Normalised item:** title `itemInfo.title.displayValue`; features `itemInfo.features.displayValues`; image the first of large/medium/small `url`; price `offersV2.listings[0].price.money.displayAmount` (or `price.displayAmount`), amount `money.amount`; url `detailPageURL`; `parent_asin`; marketplace | creators-api 486-552 |
| R12 | **Availability → quantity:** `OUT_OF_STOCK`, `UNAVAILABLE`, `UNKNOWN` → 0; `IN_STOCK`, `IN_STOCK_SCARCE`, `AVAILABLE_DATE`, `LEADTIME`, `PREORDER` → 200; otherwise a message containing "out" or "unavailable" → 0; no listing → 0; else 200 | creators-api 560-584 |
| R13 | **Errors:** `CredentialsMissing` "Amazon Creators API credentials are incomplete. Configure them in Lasso Amazon settings or pass client_id, client_secret, version, and partner_tag."; `InvalidAsin` "ASIN is required."; `AuthFailed` "Could not obtain Creators API access token."; `HttpError` (transport message); `ApiError` (Amazon's `message` or "Creators API getItems request failed."); `NotFound` "Product not found." (no items). Lasso treats `ItemNotAccessible, InvalidParameterValue, AccessDenied(Exception), TooManyRequests(Exception), ThrottlingException, RequestThrottled, AWS.*, UnrecognizedClient` as "not an issue" (status 200) | creators-api 77-122, 431-451, 698-706; amazon-api 157-172, 1943-1983 |
| R14 | **No Prime from any current path:** the resources hold no Prime/delivery field, `normalize_item` returns no `is_prime`, and BLS's stored data has none; `is_prime` is written only from an existing row (add, save) or imports (AAWP, Lite) | creators-api 22-31, 503-514; amazon-api 879-911, 993; affiliate-link 951, 1551; import 607, 1247 |
| R15 | **Rate limits:** no limiter; 1 s `sleep` before each scheduled refresh ("amazon api will response an error when we request continuously") and per bulk-sync item (filter `lasso_bulk_sync_amazon_task_pacing_seconds`); throttling codes ignored (R13) | cron 5116-5120; bulk-sync 352-355 |
| R16 | PA-API 5.0: `libs/amazon_api_v5` (`LassoAwsV5` signer) is loaded but never called; `amazon_access_key_id` / `amazon_secret_key` are read by views and never used; `settings.js` hides a PA-API mode toggle that always shows Creators | init 121; amazon.php 22-23; settings.js 2256-2332 |

### 3.2 Settings → Amazon: the Product Data API card and Validate

| # | Rule | Source |
|---|---|---|
| R17 | **Card** (left column, under Amazon Associates Accounts): "Amazon Product Data API", "Learn" (Lasso's help site); **Default Tracking ID** (select, help "Select your Amazon Associates locale.", key `amazon_default_tracking_country`, default `us`; options the 18 two-letter countries of `get_amazon_api_countries()` by name: United States, Australia, Brazil, Canada, China, France, Germany, India, Italy, Japan, Mexico, Netherlands, Sweden, Singapore, Spain, Turkey, United Arab Emirates, United Kingdom); intro "If you want to use the Amazon Creators API for product data, here's how to get your Creators API credentials from Amazon." (link to Lasso's help) | amazon.php 114-128; helper 1445-1462 |
| R18 | **Fields:** Credential ID (`type=password`, placeholder `amzn1.application-oa2-client.xxxxx`, key `amazon_creators_credential_id`); Secret (`type=password`, `autocomplete=new-password`, placeholder `client-secret`, `amazon_creators_secret`); Partner tag (text, placeholder `yourtag-20`, `amazon_creators_partner_tag`); Version (text, placeholder `3.1`, `amazon_creators_version`); button **Validate** right. All default empty. **Lasso prints the stored secret and Credential ID into the fields' `value`** | amazon.php 130-183; constant 111-117 |
| R19 | **Validate** (`lasso_verify_amazon_creators_credentials`): nonce `lasso_settings_save`, capability `manage_options` ("You do not have permission to validate Creators API credentials.", 403); posts the four fields and the country as typed (not saved first); button disabled with a spinner, notifications cleared | settings-general 610-620; settings.js 2334-2378 |
| R20 | Missing fields → "Missing required fields: " + the missing of Credential ID, Secret, Version, Partner tag, comma-separated; **no Lasso licence → "Add a valid Lasso license before validating Creators API credentials."** (Lasso's gate on a direct Amazon call) | settings-general 631-659 |
| R21 | The check is **a token request only** (no item call): token → "Amazon Creators API credentials verified successfully."; status ≥ 500, 0 or 429 → `HttpError` "Service temporarily unavailable." (HTTP 503 to the browser); otherwise `AuthFailed` "Invalid Amazon Creators API credentials." (401); `CredentialsMissing` 400; else 422 | creators-api 130-152; settings-general 893-936 |
| R22 | **On success** the five values are saved, the "credentials updated" flag set, the signature stored; success screen with the message. **On a definitive failure** (not 0/5xx/429/empty/throttle) the four fields **and the default country** are cleared, the signature and flag reset; error screen with the message (fallback "Unable to verify Creators API credentials.") | settings-general 669-706, 771-783, 834-881; settings.js 2363-2376 |
| R23 | **Save Changes** also validates when the saved fields are configured and not yet verified: failure → warning "Settings were saved, but Creators API credentials could not be validated. <msg>" + (" Your saved credentials were kept; try again when the service is available." or the fields cleared); without a licence "Settings were saved, but Creators API credentials could not be validated because a valid Lasso license is required." | settings-general 509-514, 714-764; settings.js 2428-2434 |
| R24 | After any Amazon save with a tracking ID or verified credentials: `lasso_amazon_valid` = true (it means only "tracking ID set or verified"; read by Lasso's analytics identify and stats); Amazon Associates Link Builder's store IDs copied into `amazon_associates_link_builder` when that plugin is active | amazon-api 1587-1603; settings-general 486-507; header-new 85-126; setting 1249 |
| R25 | Segment tracking of a settings save redacts the credential fields | settings.js 91-125, 2417 |

### 3.3 Storage

| # | Rule | Source |
|---|---|---|
| R26 | **`lasso_amazon_products`** (PK `amazon_id, country`): `amazon_id` varchar(20), `country` char(2) 'us', `default_product_name` varchar(2000), `latest_price` varchar(32), `base_url` (link without tag), `monetized_url` (with tag), `default_image` varchar(10000), `out_of_stock` tinyint, `is_prime` tinyint, `currency` varchar(5) 'USD', `savings_amount` varchar(10), `savings_percent` int, `savings_basis` varchar(10), `features` JSON text, `is_manual` tinyint (1 = cloud path), `rating` varchar(10) 0, `reviews` int 0 (**never written**), `last_updated` datetime (UTC) | amazon-products 28-89 |
| R27 | **Upsert** `update_amazon_product_in_db`: refuses a row without ASIN, title or image, or with an image that is not a URL or `data:image`; price "0" → ''; `out_of_stock` = quantity 0; on update keeps the old price and image when the new ones are empty and replaces the rest; resolves the product's 404 issue and, when in stock, its out-of-stock issue; a BLS "gift" answer (no title, no image, out of stock) only sets `out_of_stock` = 1 | amazon-api 981-1120; lasso-db 4322-4348 |
| R28 | **Read** `get_amazon_product_from_db( asin, url, country )`: by ASIN + country (from the URL, else `us`), filter `filter_amazon_product`, URLs re-formed by `get_amazon_product_url`, features decoded, price formatted (R50) | amazon-api 1130-1176 |
| R29 | `update_amazon_field( asin, field, value )` updates **every country's** row of that ASIN (no country condition); the pending-sync and `get_url_details_by_product_id` joins also match on ASIN only | lasso-db 5646-5655, 3559-3576 |
| R30 | Product meta written with Amazon data: `amazon_product_id`, `amazon_country`, `amazon_product_last_updated`, `affiliate_link_type` = "Amazon Product", `lasso_custom_redirect`, `lasso_final_url`, `lasso_custom_thumbnail` (the image **link**; never downloaded) | affiliate-link 1572-1583, 1951-1954; url-details ajax 225-232 |
| R31 | Thumbnail order in displays: `lasso_custom_thumbnail`, else the WordPress featured image, else imported image meta; Amazon products without a custom thumbnail use the cache image; invalid → default thumbnail. **A refreshed cache image does not reach a box whose custom thumbnail is set** | affiliate-link 2795-2850 |

### 3.4 When data is fetched

| # | Rule | Source |
|---|---|---|
| R32 | **On add** (`lasso_add_a_new_link`, Amazon link with ASIN): a cache row for ASIN + country is reused (and re-upserted with the new link); otherwise fetched and stored. NotFound → name "Amazon", the default thumbnail, the link as monetized URL, add status 404; else name = title, image = image. The product gets that name and image; the link is the tagged form (F2) | affiliate-link 929-980 |
| R33 | **Save** (`save_lasso_url`, Amazon ASIN): no row → fetch (NotFound → issue 404; quantity 0 → `000`); a row and the same ASIN kept (or a cloud sync) → fetch again and refresh title, image, out-of-stock; otherwise the row's `out_of_stock` sets the status. The row is upserted with `is_manual` = 1 | affiliate-link 1478-1561 |
| R34 | **Reading a product** renames a post titled exactly "Amazon" to the cached name (writes `post_title` while reading) | affiliate-link 417-427 |
| R35 | **Refresh** (product page `#lasso-render-image`, action `upload_thumbnail`, nonce `lasso_ajax`, `edit_posts` + `edit_post`): for an Amazon product fetches with `ver` (no forced BLS); success → `lasso_custom_thumbnail` = image, featured image removed, the post renamed only when its title is a placeholder; returns product (price, url, savings, `show_discount_pricing`, `discount_pricing_html`), and the page fills Price and the link. Failure messages: the fetch's message, "Amazon fetch failed (HTTP n).", "Amazon fetch failed (<code>).", "Could not fetch Amazon product image." Non-Amazon: Apply with "Image URL is required." / "Invalid image url." | url-details ajax 100-255; url-details.php 1188-1290 |
| R36 | The image control: label "Amazon Image" (Amazon) / "Product Image"; tooltip "Click the button below to grab an updated image from Amazon." / "Paste an image URL and click Apply, or click the preview image to choose from your Media Library."; Amazon field read-only, button "Refresh"; others "Apply"; hidden on a new link | url-details.php 154-165, 339-345 |
| R37 | **Scheduled refresh:** cron `lasso_cron_update_amazon` hourly, scheduled only while `amazon_update_pricing_hourly` ("Update Amazon pricing daily") is on; the run queues `ceil( due / 20 )` clamped to **100-500** items | cron 150, 194, 286, 373-378, 4944-4952; update-amazon 71-170 |
| R38 | **Due:** published Lasso links, `product_type` amazon, ASIN set, cache older than 24 h or missing, not an open 404 unless resolved or older than 72 h, and (while "Only update products with Locations" is on) present in `lasso_link_locations` (same ASIN, country or none, a non-trashed post); oldest first | lasso-db 4389-4446 |
| R39 | **Each item:** repair `base_url` / `monetized_url` when their ASIN differs; `sleep(1)`; fetch with `updated_at`; NotFound or out of stock → `last_updated` bumped (skip 24 h); then every product with that ASIN gets its issue checked: NotFound `404`, in stock `200` (resolve), out of stock `000`; `amazon_product_id` / `amazon_country` meta repaired from `url_details`. The job never changes a title, description or `lasso_custom_thumbnail` | update-amazon 44-202; cron 5105-5160, 1062-1170 |
| R40 | **Bulk sync** — pending: published Amazon links whose ASIN has no cache row or an empty name, or no `amazon_product_last_updated` meta; or an ASIN-less redirect on `amzn.to`, `amzn.com`, `/dp/`, `/gp/product/`, `/ASIN/` without that meta | lasso-db 3559-3750 |
| R41 | **Banner** on Lasso pages while pending > 0 or running and not dismissed for the current pending fingerprint (`md5` of the pending IDs, user meta `lasso_dismiss_amazon_bulk_sync_banner_v2`): "Bulk Amazon sync" / "%d Amazon link(s) is/are ready to sync product data." / **Sync Amazon data** (disabled when nothing pending or running) / × ("Dismiss") | bulk-sync-admin 47-117; amazon-bulk-sync-pending.php |
| R42 | **AJAX** (nonce `lasso_ajax`, `edit_posts`): `lasso_bulk_sync_amazon_start` (409 "Bulk Amazon sync is already running.", 400 "No Amazon links need syncing.", else status), `_status` (running, percent, total, completed, skipped, failed, failures, summary, pending_count), `_tick` (only when `wp_get_environment_type()` is `local`: runs one batch), `_dismiss`; heartbeat `bulk_sync_amazon` returns the status | pages/class-ajax 98-101, 1886-1910, 2230-2297 |
| R43 | **Script:** Start → "Starting…", poll every 3 s → "Syncing… N%"; done → "Sync Amazon data", summary "Sync complete." / "Succeeded: n" / "Skipped: n" / "Failed: n" / "Failures: #id: message | …" (first 5); the banner fades when nothing is pending. A start lock option prevents two starts; at most 500 posts a run (filter `lasso_bulk_sync_amazon_batch_size`); counters and up to 200 failures in options; summary on completion | amazon-bulk-sync-banner.js; bulk-sync 216-527 |
| R44 | **Per post** (`sync_amazon_metadata_for_post`): messages "Invalid Lasso post ID.", "Not an Amazon link." (skipped), "Amazon search title applied.", "Could not resolve Amazon ASIN.", "Amazon data already present." (success, meta stamped), "Amazon product not found.", "Amazon sync failed.", "Amazon sync returned incomplete product data.", "Amazon sync failed to persist product data.", "Amazon sync failed to verify persisted product data.", "Amazon data synced."; sets the title only when empty and the thumbnail only when there is no featured image and the custom one is empty or the default | affiliate-link 3867-4106 |
| R45 | **Live state:** the live Dashboard shows the banner stuck at 0% for 3 links (report 2026-11-12); on this copy the predicate gives 3 | report; section 2 |

### 3.5 Display: price, discount, timestamp, Prime

| # | Rule | Source |
|---|---|---|
| R46 | **Decision C4, 2026-11-14: Lasso's rule; the per-product switch is an opt-out defaulting on.** **Prime:** the box's `is_prime` is `enable_amazon_prime && ASIN set` — **every Amazon product with an ASIN, whatever the cache's `is_prime`**; the shortcode's `prime=` (any value) forces it on. Markup: `<div class="lasso-price">` … `<i class="lasso-amazon-prime"></i></div>`, printed when `(price && show_price) || is_prime`; v2 displays can hide it (`data-v2-hide-prime`) | affiliate-link 577; single.php 216-219; layout-*-box.php; list-box 48-58; sitestripe 35-47; display-v2-frontend.css 1114 |
| R47 | Help: "Show the blue Prime logo beside the price for all eligible Amazon products in your Lasso Displays." | amazon.php 221 |
| R48 | **Price of an Amazon product** is the cache's `latest_price` (it replaces the `price` meta when the product reads); others use `price` meta. **Show price** per product = meta `show_price` (1/0) else the site's `show_price`; the shortcode `price="hide|show|<text>"` overrides | affiliate-link 316-343; single.php 207-214 |
| R49 | **Product page price:** Amazon → read-only text input `$99.99` placeholder with "Amazon prices updated via Amazon API settings" (link to the Amazon tab); others → Quill one-line editor with bold, italic, underline, strike and the 35-colour palette of `lasso-price-quill-palette.php`; tooltip "This price can be any text or amount that'll only be shown in displays. Prices automatically update every 24 hours with integrations like Amazon."; show toggle "Turn this on to show the price in this display." | url-details.php 350-408; palette 10-44 |
| R50 | **Formatting** (`format_price`): the ISO code from the stored currency or found in the text (USD AUD CAD EUR MXN CNY JPY INR SEK BRL TRY GBP PLN EGP SGD AED); SEK `1 234,50 kr`; €, R$, TL, kr, zł, kn, Kč, Ft, lei, ₴ as `1.234,50 €`; others `$1,234.50` (₹ in front); BLS prices re-formatted from `currency_name` | amazon-api 2248-2355, 862-867; helper 4541-4660 |
| R51 | **Discount** (`show_amazon_discount_pricing`, help "Show the base Amazon price as strikethrough text, followed by the discounted price in bold."): `<strike>basis</strike>` when basis > price (2 decimals, the price's currency style), in `<span class="discount-price">` before `<span class="latest-price">` | amazon-api 2218-2238; layout-6-box 57-63; amazon.php 231 |
| R52 | **Timestamp:** `last_updated` as `m/d/Y h:i a T` (UTC) + `<i class="lasso-amazon-info" data-tooltip="Price and availability are accurate as of the date and time indicated and are subject to change.">` in `.lasso-date`; shown for Amazon products (not basic links) when show price is on and the price is not empty (v2 customised displays: the Timestamp element); table displays show the oldest | affiliate-link 352; single.php 237-246; layout-6-box 122-131; table.php 56-106 |
| R53 | Savings fields also carried for tables and fields: `savings_basis`, `savings_amount` (formatted), `savings_percent` + "%"; `rating`/`reviews` (always 0) | affiliate-link 355-359, 580-584 |
| R54 | AAWP features become the description only for products imported from AAWP (5 items, 200 characters, AAWP's options) | amazon-api 2009-2053 |

### 3.6 Availability and link health

| # | Rule | Source |
|---|---|---|
| R55 | **Check of an Amazon link** (`check_amazon_link_status`): when nothing is configured (no verified credentials and no tracking ID) → 200; else fetch: success → `000` when quantity 0 else 200; NotFound → 404; ignored codes → 200; an empty code → the product's status code (404 from a scrape failure → 200) | amazon-api 1931-1983 |
| R56 | The issue check (`check_issues`) for an Amazon product fetches and records `404` (NotFound) or `000` / `200` in `lasso_url_issues`; 200 resolves; `days_before_out_of_stock_alerts` (0-30) hides an out-of-stock issue from the counts until it is that many days old | cron 1062-1170; lasso-db 2012-2036, 2094-2110 |
| R57 | A cache upsert resolves the product's 404 issue and (in stock) its out-of-stock issue; `resolve_product_out_of_stock` also sets `out_of_stock` = 0 | amazon-api 1034-1037; lasso-db 4322-4348 |

### 3.7 Tracking IDs and link forms

| # | Rule | Source |
|---|---|---|
| R58 | **`lasso_amazon_tracking_ids`** (id, `country_code` int (dialling code), `country_name`, `amazon_domain`, `pa_endpoint`, `tracking_id`; unique on the first four) seeded with 13 countries; read by the Amazon settings and onboarding views, where `get_tracking_id_fields()` builds per-country inputs **that neither view prints**; nothing writes `tracking_id` | tracking-ids 13-109; amazon.php 19-20, 62; onboarding 10-11; helper 1480-1505 |
| R59 | Tag rules (whitelist, blacklist, `maas`, URL variants), the product-page tracking-ID notice and `lasso_save_amazon_tracking_id` (nonce `lasso_ajax`, `edit_posts` + `manage_options`, saves `amazon_tracking_id` with `sanitize_text_field`; "Tracking ID Saved!" / "Failed to save Tracking ID") are F2/F17/F1 (built: 12A, 10F, 14D) | amazon-api 1492-1521; url-details ajax 336-356; url-details.php 2626-2657 |
| R60 | **Short links:** destination cached in options `<host_with_underscores>_<code>` and `…_page_title`, and in `lasso_amazon_shortened_url (shortened_url, final_url, lasso_id)` when the destination differs and is Amazon's | helper 368-373, 577-604; shortened-url 348-390 |
| R61 | **Shorten Amazon affiliate links** — the stored and rendered form (F2 section 3; Task 12A); the box's links pass through `get_amazon_product_url` again when read | affiliate-link 342, 500, 546 |
| R62 | **Keep Geni.us Formatting** off: an added `geni.us` link is replaced by its destination (redirect followed; cloud when the answer is not 200/404); a saved one too, keeping the original in `old_genius_url` / `old_second_btn_genius_url`; help "Turn this off to remove geni.us cloaking from all Lasso links." | affiliate-link 827, 852-856, 1145-1158, 3602-3616; helper 466-469; amazon.php 321 |
| R63 | Turning it **off** on save runs `Lasso_Process_Remove_Genius_Formatting` (scheduled, 10 links a page: every `url_details` row with `geni.us` re-saved through `save_lasso_url` with its name, link, slug, second link, price, description, image) and schedules the daily `lasso_cron_remove_genius_formatting`; turning it **on** clears that cron | settings-general 424-475; remove-genius 43-121; cron 139, 311-314, 369-371, 6984-6987; url-details model 369 |
| R64 | **Decision B4, 2026-11-14: the stored value is kept and labelled "no effect"; nothing is built behind it.** **Mobile Deep Linking** ("Open clicks in the Amazon app.") and **Send Readers to Their Local Store** ("Readers will be redirected to the Amazon store closest to their detect country.") are read only to pass `mobile_deep_linking` and `international_click` to Lasso's cloud snippet's `init` and to the cloud's site settings; no PHP or local JS acts on them | class-hook 1648, 1755-1762; cron 6553; connect-snippet.min.js (`params.mobile_deep_linking`, `params.international_click`) |
| R65 | **Decision B4, 2026-11-14: the stored value is kept and labelled "no effect"; nothing is built behind it.** The snippet (loaded from `js.codedrink.com`) sends Amazon product, Amazon short and geni.us clicks to **`https://lasso.to/amazon?url=<link>&matching=<0|1>&…`**; what that server does with deep linking and the reader's country is not in Lasso's code (UNVERIFIED: looked for `intent:`, `com.amazon`, `onelink`, `geo`, `country` in the snippet; none) | connect-snippet.min.js (`LASSO_REDIRECT_AMAZON_URL`, `buildLeanRedirectAmazonUrl`, `isLeanRedirect`) |
| R66 | **Decision B4, 2026-11-14: the stored value is kept and labelled "no effect"; nothing is built behind it.** **Commission Upgrades** (`auto_upgrade_eligible_links`): the cloud decides better Amazon links (R6); results set `is_aff_plus`, rewrite `monetized_url` and fill `lasso_amazon_affiliate_plus (id, new_id, created_at)` (ASIN_country pairs, 13 characters, loop guard); a content-scan filter replaces old-ASIN hrefs **in post content** with the new ASIN's `/dp/` link; the Dashboard shows "Marketplace" on such rows | update-aff-plus; affiliate-plus 454-488; pages/fix/class-hook 951-990; affiliate-link 1846-1868; url-row.php 39 |
| R67 | **Force changes from link details**, the blacklist rewrite, SiteStripe scanning and `monetize_amazon_link` act on post content (link rewriting, auto-monetization): `monetize_amazon_link` (nonce `lasso_ajax`, `edit_posts`) follows `amzn.` links, adds the product if missing and **rewrites the link in the post** (`update_link_in_post`) | pages/class-ajax 1112-1170; cron 2816-2828, 1704-1745 |

### 3.8 Marketplace and Amazon search

| # | Rule | Source |
|---|---|---|
| R68 | `Lasso_Amazon_Api::search_product( keyword )` uses Creators `POST /catalog/v1/searchItems` (`keywords`, `searchIndex` All, `itemCount` 10, resources images, title, price, availability); messages "Amazon Creators API credentials are required for product search.", "Amazon product search is temporarily unavailable." (HttpError/AuthFailed), "No products found."; **no caller in Lasso 369** | amazon-api 672-722; creators-api 33-40, 162-332 |
| R69 | The Marketplace tab (F18 section 9) calls `lasso_pro_marketplace_search` (nonce `lasso_ajax`, `edit_posts`; `search`, `store` us, `page`, `limit` 10) | pages/class-ajax 2473-2509 |
| R70 | …which needs a Lasso licence ("A valid Pro license is required for Marketplace search.") and calls **`GET lasso.link/pro/marketplace/products?search&store&page&limit`**; rows `label, image (https), asin, targetURL, marketplaceEligible`; errors "Marketplace search is unavailable." or the cloud's message | marketplace-catalog 15-146, 261-296 |
| R71 | A selection is written to the cache before the add (name = label or "Amazon Product", image or the default thumbnail, price '', quantity 200, `is_manual` 0) and the add skips the fetch (`marketplace_fast_add`); the URL is `targetURL` or `https://www.amazon.com/dp/<ASIN>` | marketplace-catalog 154-253; affiliate-link 930-935, 845-847 |

### 3.9 Notices and onboarding

| # | Rule | Source |
|---|---|---|
| R72 | **"Migrating from Amazon SiteStripe?"** — "Migrating from Amazon SiteStripe? **Turn on Auto Amazon** to auto-migrate all your SiteStripe links within 24-48 hours!" (link to the Amazon tab), × ; on every Lasso page while `auto_monetize_amazon` is off and option `lasso_enable_auto_amazon_notification` ≠ 0; × → `lasso_disable_auto_amazon_notification` (nonce `lasso_settings_save`, `manage_options`, "You do not have permission to dismiss this notification.") sets it 0, site-wide | auto-amazon.php; footer.php 95-116; settings-amazon ajax 28-48 |
| R73 | **"Amazon has updated their API requirements. Please update your API credentials."** — off by default (filter `lasso_show_amazon_credentials_notice` false); when on: not on the Amazon tab, until verified or "credentials updated", dismissed in two steps (`…_dismissed`, then `…_dismissed_final`) by `lasso_dismiss_amazon_credentials_notice` (same nonce and capability) | amazon-credentials-update.php; footer 118-138; helper 6366-6378; settings-amazon ajax 53-80 |
| R74 | `amazon-not-configured.php` ("**Enter your Amazon API keys** to add and update products automatically.") is included nowhere; the General setting **"Disable Configure Amazon Notification"** (`general_disable_amazon_notifications`) is read by nothing else | grep; general.php 523-528; setting 1072 |
| R75 | **Onboarding step 4 "Amazon Associates"** ("Learn"): Tracking ID for This Site ("This is an invalid Tracking ID"), Auto-Monetize ("Automatically monetize all current and future Amazon links with your Tracking ID and and added to your affiliate dashboard."), the Product Data API card (R17-R18, Validate), Preferences (Prime, Discount, Commission Upgrades checked, Update pricing daily); **Skip →**, **Save and Continue →** | onboarding/amazon.php |
| R76 | Auto-monetize notices (`amazon-auto-monetize-*`) belong to the auto-monetization sweep; they touch product data only through `Lasso_Process_Add_Amazon`, which fetches each new ASIN (R32) and backfills `link_locations` | add-amazon 130-139, 188-250 |

## 4. Decisions

Settled from Lasso and the records:

- **D1 Creators API only** (decision 11; brief). No call to `lasso.link`, `lasso.to`, `codedrink.com` or any cloud of
  Lasso's (R3-R6, R65, R70 are bucket C); no PA-API (R16); Amazon pages are never fetched (F2's fetcher stops at
  Amazon). Everything that needs the API is built and dormant without credentials.
- **D2 Credentials in their own option** `affibloom_amazon_credentials` (not autoloaded): `credential_id`, `secret`,
  `partner_tag`, `version`, plus `verified_signature`. The Default Tracking ID country is an ordinary setting,
  `default_country` (`amazon_default_tracking_country`, default `us`), because F2's bare-ASIN rule reads it. The option
  is never in a REST response, export or log; the Secret and Credential ID fields render **empty** with "Saved —
  leave blank to keep" when a value exists (Lasso prints them, R18: that is the AC5 fix).
- **D3 Validate is Lasso's token check without the licence gate** (R20's licence rule exists only for Lasso's cloud):
  messages R20-R21; on success the four fields and the country are saved and the signature stored (R22); on a
  definitive rejection the four fields are cleared, **the country is kept** (Lasso blanking it is a slip: blank falls
  back to `us` anyway, R10). Save Changes validates unverified credentials as R23, without the licence sentence.
- **D4 Creators is used when configured** (R1), as Lasso; the card shows the state: "Not set up" / "Saved, not
  validated" / "Validated".
- **D5 Cache table** `{prefix}affibloom_amazon_products` with Lasso's columns and key (ASIN + country), minus `rating`
  and `reviews` (never written, R26) and `is_manual` → `source` (`lasso`, `creators`); every write and lookup carries the
  country (fixing R29). Task 26 copies Lasso's 501 rows with `source = lasso`.
- **D6 One ASIN per request, paced** as Lasso (R9, R15): 1 s between scheduled items, token cached as R8 (transient
  keyed on a hash of id, version and secret), 10 s timeout; throttling and access codes are "not an issue" (R13) and
  leave the row as it was.
- **D7 What a fetch changes** (Lasso's rules): add — name and image (R32); Refresh — image link, price, availability,
  the name only when it is a placeholder (R35); the job — the cache only, never a name, description or image link
  (R39); URL replaced — image always, name per Keep Title (R33, C1O). AC6's "an editor's name or image is never
  overwritten by a refresh" holds because the job writes only the cache (R31, R39).
- **D8 The scheduled job** follows R37-R39: hourly event `affibloom_amazon_refresh`, batch `ceil(due/20)` clamped
  100-500, due rule R38 (the locations-only arm reads F21's content link index), on only while "Update Amazon pricing
  daily" is on and credentials are configured.
- **D9 Prime follows Lasso's display rule** (R46): see "Needs the user" N2 for the per-product switch.
- **D10 Price, discount, timestamp** render as R48-R52 in the box (Phase 3 templates), with Lasso's classes and
  tooltip text, the timestamp in UTC as Lasso.
- **D11 Availability goes to link health** (F11): a fetch records `000` / `404` / resolves exactly as R55-R57, through
  F11's issue store; `days_before_out_of_stock_alerts` is F11's filter.
- **D12 Bulk sync** as R40-R44 with Affibloom's REST routes and WP-Cron instead of AJAX and Lasso's queue; the `_tick`
  local-only worker is not needed (WP-Cron's spawn already works on Local, ADR-4). Without credentials the banner
  shows the pending count with the button disabled and "Needs the Amazon API".
- **D13 The Amazon search tab** (F18 D5, the user): Creators `searchItems` (R68's client), ten a page, F18 section 9's
  strings; a selection fills the cache from the search result (R71) so the add needs no second call.
- **D14 Geni.us formatting** (R62-R63) is built: the redirect is followed on the site (geni.us is not Amazon), stopping
  at Amazon as F2's fetcher does; the off-switch job re-saves products, never posts.
- **D15 Not rebuilt:** the per-country tracking-ID table and its unprinted inputs (R58); `lasso_amazon_valid` and the
  analytics identify (R24); Amazon Associates Link Builder copy (R24, Import's); the AAWP description (R54, Import's);
  the dead `amazon-not-configured` view and the readerless "Disable Configure Amazon Notification" (R74) — recorded,
  nothing to build; the credentials-update notice (R73, off by default; Affibloom's AC7 states replace it).
- **D16 Wording:** "Lasso Displays" → "Displays", "Lasso Amazon settings" → "Amazon settings", plain success words.

**Needs the user** (each would drop or replace part of Lasso, or depends on credentials):

- **N1 Mobile Deep Linking, Send Readers to Their Local Store, Commission Upgrades** (R64-R66) worked only through
  Lasso's cloud (the snippet and `lasso.to/amazon`; the affiliate-plus service). At cutover readers' Amazon clicks go
  straight to `amazon.com` with `vbvault-20`. **Recommendation:** keep the three stored values, show them with "This
  worked through Lasso's cloud and has no effect in Affibloom" (C1L), build nothing; a local-store feature would need
  per-country tracking IDs (none set: 13 empty rows) and reader geolocation — revisit only if the client asks.
  → **Decided 2026-11-14:** B4: keep the three stored values, label them "no effect", build nothing behind them.
- **N2 Prime per product:** Lasso shows the logo on every Amazon product with an ASIN (R46; 7 of 7 baseline variants
  carry it), but Affibloom's product page has a "Show Prime Logo" switch (F1 D7) and the migration maps the cache's
  `is_prime` (1 published product), which would remove the logo from about 225 boxes at cutover. **Recommendation:**
  Lasso's rule (setting + ASIN) for the box; drop the per-product switch (or keep it as an opt-out that defaults on).
  → **Decided 2026-11-14:** C4: Lasso's rule, the Prime logo on every Amazon product while the site setting is on; the per-product switch becomes an opt-out defaulting on.
- **N3 Amazon prices after cutover without credentials:** today prices are hidden (site Show Price off; per-product 0
  on 216, none on); Amazon's Participation Requirements allow prices only from the API (report 2026-11-12, quoted).
  **Recommendation:** an Amazon product's price shows only from a `creators` cache row younger than 24 h; Lasso's
  migrated prices stay in the cache but are not shown (no visible change on this site).
  → **Decided 2026-11-14:** E6: the user accepted the recommendation
- **N4 Amazon's 24-hour rules** (images as links for at most 24 h; other content refreshed within 24 h) come from the
  Associates Program Policies as quoted in `reports/2026-11-12-lasso-amazon-product-data.md`; **not re-read in this
  sweep and not stated anywhere in Lasso's code** (Lasso's 24 h due rule, R38, is practice). Migrated image links from
  Lasso's cloud are kept by decision 11 (the user). **Recommendation:** re-read the policies and the Creators API terms
  when C1E starts; no change before that.
  → **Decided 2026-11-14:** E7: the user accepted the recommendation

## 5. Scope that needs a home

- **Link health (F11, Tasks 30-32):** the Amazon half of the broken check and out-of-stock detection takes C1H's
  results; `days_before_out_of_stock_alerts`; the Alert icon and tab.
- **Content link index (F21):** the locations-only arm of C1E's due rule; SiteStripe scanning (R67).
- **Link rewriting / auto-monetization:** Force changes, the blacklist rewrite, `monetize_amazon_link` (rewrites
  posts: not built, deviation 52's rule), the short-link map (R60), Auto-Monetize and its notices (R76).
- **Migration (Task 26):** Lasso's cache rows (D5), `default_country`, the credentials (none), not the PA-API keys.
- **Onboarding wizard (R75):** whichever feature rebuilds Lasso's setup wizard reuses C1A's card partial; no owner yet.
- **Phase 3 box (Tasks 18-22):** C1F and C1G change the box templates and Task 22's Prime rule.

## 6. Edge cases

1. Credentials saved but Amazon down: fetches fail quietly, the add falls back (AC2), Refresh shows the message, the job
   leaves rows and retries next hour.
2. A 429 or throttle answer: no change, no issue recorded (R13).
3. Two products with one ASIN: one cache row; both read it; the job checks both (R39).
4. An ASIN on `amazon.co.uk`: its own row (`gb`), its own marketplace (R10).
5. A token expiring mid-run: the next call asks for a new one (R8's 60 s margin).
6. Validate with the stored secret left blank: the stored secret is used (D2).
7. A product trashed while queued: skipped (published only, R38).
8. Bulk sync started twice: the second gets 409 "Bulk Amazon sync is already running.".
9. A fetch with title but no image: not stored (R27), reported as "incomplete product data" in bulk sync.
10. Price text with HTML (migrated): printed through the price allowlist (F1 S3).
