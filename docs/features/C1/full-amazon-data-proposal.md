# C1 — Full Amazon product data: findings and tasks C1Q0-C1Q6 (approved 2026-11-15)

Written 2026-11-15 at the user's request ("fetch as much product information as the API supports … prepare an
implementation plan … do not start until I approve"). Nothing here is built. Sources: Affibloom's code at `4d34e9f`,
Lasso 369 (`D:\lasso\lasso`, C1 requirements R1-R57), Lasso's 501 rows in `wp_lasso_amazon_products` on Local, and
Amazon's Creators API reference (getItems, getVariations, ItemInfo, OffersV2, api-rates, error codes).

## 1. What exists today

| Piece | State |
|---|---|
| Credentials card, Validate, token cache (C1A) | built |
| `CreatorsApiCatalog::item()` — one ASIN per getItems (C1B) | built; asks Lasso's 7 resources only (below) |
| `CatalogItem` (normalised answer) | title, first image (large → small), display price, currency, display savings / percent / basis, availability, features, detail URL. **Not read:** price amount, `parentASIN` |
| `{prefix}affibloom_amazon_products` (C1C) | one row per ASIN + store: name, price, currency, links, image, out_of_stock, savings (display strings), features, source, last_updated |
| On add (C1C) | product takes **name and image**; row stored |
| Refresh (C1D) | image, row, placeholder name |
| Bulk sync banner (C1I) | fills products that never got Amazon's data, 1 s apart |
| Planned | C1E daily refresh job, C1F price/discount/timestamp in the box, C1G Prime, C1H stock → link health, C1M Amazon search tab, C1O data when the URL changes |

**Blocker found:** the Local site's credentials are **empty** (all four fields and the signature), so no live call is
possible here. That shape is written by one path only: `CredentialsService::check()` when Amazon refuses a token on
Validate or Save Changes (Lasso's R22, "a rejection clears the stored credentials"). Deviation 112 saw the same,
cause unknown. Task Q0 finds the cause before anything else.

## 2. How Lasso fills its Amazon records

- **Creators path** (credentials configured): resources `images.primary.{large,medium,small}`, `itemInfo.title`,
  `itemInfo.features`, `offersV2.listings.{price,availability}`, `parentASIN` (R9). Stored: name, image, price,
  stock, features, currency (R26-R27).
- **Everything else came from Lasso's cloud** (R3-R5): savings, list price and missing prices. Not used by Affibloom
  (deviation 52).
- **Never written by any path:** `rating`, `reviews` (0 in all 501 rows here), Prime (R14), brand, description.
- On add a product takes only the **name and image** (R32); its description stays empty.

## 3. What the Creators API offers (official reference)

| Wanted | API | Notes |
|---|---|---|
| Title | `itemInfo.title` | have |
| Images | `images.primary.*`, **`images.variants.*`** | variants = the other photos |
| Current price | `offersV2.listings.price` (`money.amount`, `displayAmount`, `pricePerUnit`) | have display; amount not read |
| List / was price | `price.savingBasis` (+ `savingBasisType`: LIST_PRICE, WAS_PRICE …), `price.savings` | read as display strings only |
| Description | **none.** ItemInfo has no description attribute; `features` is the only product copy | |
| Features / bullets | `itemInfo.features` | stored, shown nowhere |
| URL | `detailPageURL` | read; Amazon asks that its parameters are kept |
| ASIN, parent | `asin`, **`parentASIN`** | parent not read |
| Brand, manufacturer | **`itemInfo.byLineInfo`** | |
| Model, part no., warranty | **`itemInfo.manufactureInfo`** | |
| Colour, size, dimensions, unit count | **`itemInfo.productInfo`** | |
| EAN / UPC / ISBN | **`itemInfo.externalIds`** | |
| Category, sales rank | **`browseNodeInfo.*`** | |
| Availability | `listings.availability` (type, message, min/max order) | have |
| Deal | **`listings.dealDetails`** (badge, start/end, Prime access) | |
| Seller, condition, buy box | **`listings.merchantInfo`, `condition`, `isBuyBoxWinner`** | today listing[0] is taken, not the buy-box winner |
| Ratings, review count | **not in Amazon's official resource list** (only a third-party page lists `customerReviews.*`) | verify live (Q1); Lasso never had them |
| Variations | **getVariations** (child ASINs, `variationAttributes`, `variationSummary` lowest/highest price, dimensions; 10 per page) | separate operation |

**Limits:** 1 request/s and 8,640/day to start (more with sales); one getItems call takes **up to 10 ASINs** and counts
once; throttling is HTTP 429 `ThrottleException` with `retryAfterSeconds`; the token endpoint's 429 says Retry-After
300 s; access is withdrawn after 30 days without qualified sales. HTTP 200 can carry per-item errors
(`ItemNotAccessible`).

## 4. Decisions (the user, 2026-11-15; deviation 117)

1. **Scope:** fetch every relevant field the Creators API offers (section 3) on add and Refresh; no Lasso cloud, no
   other API or service.
2. **Description and features are separate.** Features are never copied into the description; with no description
   from Amazon (there is none, section 3) it stays as it is. A new product field **Features** (post meta
   `_affibloom_features`, a JSON list of bullets) is filled from Amazon on add and Refresh and editable on the product
   page. No front-end display of features for now.
3. **Automatic price updates are needed now:** a scheduled refresh (C1E's job, brought forward from Phase 7) keeps
   the stored price and stock current, stores prices as numbers, keeps the last good data when a call fails, honours
   throttling and retry times and stays inside the daily quota. C1E's "Only update products with Locations" arm needs
   the link index (F21, Phase 5) and stays with C1E.
4. **Ratings:** Amazon does not return them (C1Q1, 2026-11-15: `customerReviews` accepted, nothing returned), so the box's stars stay the Primary Rating field (F8). No
   new rating system.
5. **Variations (Q7) excluded,** for a later phase.
6. **Order:** now, before Batch 2.2's C1K and C1L: Q0 → Q1 → Q2 → Q3 → Q4 → Q5 → Q6, one task at a time, each with its
   own tests, records and commit; the user approves each next step.

## 5. Tasks (written in full in `plans/phase-2-settings-and-product-library.md`)

| Task | Entry point | What it does | Tests |
|---|---|---|---|
| **C1Q0** Credentials kept | Settings → Amazon Save / Validate | Find why the Local credentials are empty (deviation 112); fix the cause; every clear says why. The user re-enters them if needed | the cause reproduced in a test, red then green; C1 card tests |
| **C1Q1** Real fixtures | `wp affibloom amazon get <asin> --raw` | Every getItems resource for 3 real ASINs; answers saved, partner tag scrubbed; settles reviews and the image shape | the recorded files |
| **C1Q2** Read everything | the same command | Full resource list; `CatalogItem` reads amounts, list price, the buy-box listing, deal, seller, condition, brand, manufacturer, model, warranty, product info, external IDs, category and rank, parent ASIN, all images | unit per group from Q1's files, partial answers |
| **C1Q3** Store it | upgrade step 3 | Row: numeric price / list price / savings, brand, deal badge, parent ASIN, images, and a JSON `details`; mapping from `CatalogItem` | round trip; run-state restore |
| **C1Q4** Fetch and refresh | Add A New Link, Refresh, product page, cron | Features field (meta JSON, editor list); add and Refresh fill name, image, features and the row; scheduled price refresh (hourly event, due rule, numeric prices, last good data kept) | C1 AC1/AC4 extended, new ACs, unit |
| **C1Q5** See it | product page | Read-only **Amazon Details** card (all stored fields, last updated) | E2E AC; looks 375/1280 |
| **C1Q6** Limits | every Creators call | Throttle back-off from `retryAfterSeconds` / Retry-After, a daily-quota guard, 10 ASINs per getItems in bulk sync and the job, per-item errors in a 200 answer | unit with 429 / partial fixtures |
