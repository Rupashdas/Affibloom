# How Lasso gets Amazon titles and images without API credentials (2026-11-12)

Question: Lasso's Creators API fields are empty on the live site, yet new Amazon products get a title and image. How,
and how can Affibloom do the same without asking the client for credentials first?

Sources: Lasso 369's source code (`D:\lasso\lasso`; the installed copy on the Local site is also 369), the Local copy's
database (production clone of 2026-11-09), production through the read-only connector, the live admin screens
captured today, the project's Lasso analysis, and Amazon's Associates Program Policies (read today, quoted verbatim).
Nothing was sent to Lasso's cloud or to Amazon during this investigation, and nothing on either site was changed.

## Findings

### 1. Lasso's cloud reads the Amazon product page for the site

When no Creators API credentials are set, `Lasso_Amazon_Api::fetch_product_info()` calls `fetch_product_from_bls()`.
"BLS" is Lasso's **Broken Link Service** (`Lasso_Helper::get_url_status_code_by_broken_link_service()`):

- **Request:** `GET https://lasso.link/link/status/?<AES-encrypted { url, ver?, force_bls?, refresh_image? }>`. The only
  product information sent is the **Amazon product URL**; no Amazon credentials of any kind.
- **Identification:** headers `license` (the Lasso licence key), `site_id` and `site_url` (`get_lasso_headers()`). The
  service is part of Lasso's paid platform and is tied to the licence.
- **Response:** `status`, `productName`, `pageTitle`, `imgUrl`, `price`, `quantity`, `finalUrl`, and `additionalData`
  (`currency_name`, `basis_price`, `saving_amount`, `saving_amount_percent`).
- **What the response shows about the method.** Lasso falls back to `pageTitle` when `productName` is empty, and strips
  a leading `"Amazon.com:"` from the name: that prefix is how an Amazon *web page's* HTML `<title>` starts. `finalUrl`
  is where a page load ends after redirects. The same service answers Lasso's broken-link checks. Neither the
  Creators API nor PA-API returns a page title or a final URL. Lasso's server code is not visible, so the exact
  mechanism on its side cannot be read; but everything the plugin sends and receives is that of **a server that loads
  the Amazon product page and extracts its title, main image and price**. The project's analysis (8 Nov) records the
  same: "Amazon product data from Lasso's cloud scraper (no Amazon API keys set)".
- **No reading of Amazon pages on the site's own server:** Lasso's code contains no Amazon page parsing
  (`productTitle`, `landingImage`, `og:image` and similar are absent). All extraction happens on Lasso's servers.

### 2. Lasso's cloud also pushes product data to the site

`Lasso_Process_Sync_Link_From_Cloud_To_WP` (the hourly cloud → site link sync) receives `amazon_data` (`name`, `price`,
`image`, `quantity`, `additional_data`) from Lasso's cloud and writes it into `wp_lasso_amazon_products`. Lasso's cloud
therefore keeps its own copy of each product and refreshes the site from it.

### 3. The stored data matches

- `wp_lasso_amazon_products` holds 501 products; **all 501** have a name and an image, every image on
  `m.media-amazon.com`; 465 have a price, 110 a Prime flag; **none** has a star rating or review count.
- The newest products (added on production 2026-11-05) carry the full Amazon titles (for example "Mizuno Wave Momentum
  3 Men's Volleyball Shoe | Men…"), refreshed 2026-11-09 00:08–00:11 by Lasso's nightly job; editors shortened the
  product names shown in boxes.
- `is_manual` marks the source path. **259 rows = 1**: written by the cloud paths (BLS, the cloud → site sync, Lasso's
  "Amazon sync"), newest 2026-11-09. **242 rows = 0**: the default, left by Lasso's official-API paths (the Creators API
  fetch and its enrichment, `class-lasso-amazon-api.php` lines 787 and 1922) or a fix routine (`class-fix.php`); saving a
  link keeps a row's existing flag. Their oldest fetch is 2023-08-31, which fits the time when the PA-API 5.0 keys still
  stored in the settings were in use by an older Lasso; that cannot be confirmed from the code, and what refreshed them
  last (2026-11-04) is not determined. With the Creators API empty and PA-API closed, **new** data today can only come
  from the cloud paths.

### 4. Every other source is closed

- **Creators API:** fields empty on the live site (admin screen, 2026-11-12, and the database).
- **PA-API 5.0:** the keys an older Lasso stored still exist, but Lasso 369 never reads them, and the one approved test
  today returned **403 `AccessDenied`, "Product Advertising API is deprecated"**.
- Production runs **Lasso 370**, whose code is not available here. Its settings screen shows the same empty Creators API
  fields, and products added on the live site still receive data, so it behaves as above; that is an inference from
  behaviour, not from 370's code.

**Answer:** Lasso does not use an Amazon API with shared or hidden credentials on this site. It sends each product URL
to its own paid cloud service, which loads the Amazon page and sends back the title, image and price. That service
cannot be used without a Lasso licence and is not a public API.

### 5. What Amazon's Associates terms say (verbatim, Associates Program Policies, read 2026-11-12)

- License, IP License and Usage section: "This license does not include any downloading, copying or other use of
  Program Content for the benefit of any third party, or any use of data mining, robots, or similar data gathering and
  extraction tools."
- "You may obtain Product Advertising Content by making calls to Creators API or PA API."
- Storage: "You will not store or cache Product Advertising Content consisting of an image, but you may store a link
  to Product Advertising Content consisting of an image for up to 24 hours." Other Product Advertising Content may be
  cached "for up to 24 hours", then must be refreshed by a new API call.
- Participation Requirements 2(b): a Site "may only show prices and availability if" the link is served by Amazon or
  the data comes "via Creators API or PA API".
- The Amazon.com Conditions of Use could not be read (amazon.com answered 503 to the automated fetch), so they are not
  quoted here.

## Options for Affibloom without credentials up front

| # | Option | Gives | Problems | Verdict |
|---|---|---|---|---|
| 1 | **Migrate what Lasso already fetched** (Task 26, ADR-2) | Name and image for all 226 Amazon products Lasso has cached; the other 7 keep their record title and thumbnail | Nothing new: covers existing products only | **Do it** (already planned) |
| 2 | **Editor-assisted entry, no network** (the plan's fallback, improved) | New Amazon products get a starting name from the link's slug when there is one (`amazon.com/Mizuno-Wave-Momentum-3/dp/…`), the ASIN, a "View on Amazon" link, and an image field that takes an image link | The editor confirms the name and supplies the image; a link pasted without a slug gives no name | **Do it** for new products |
| 3 | Read the Amazon page from the site's own server (what Lasso's cloud does) | Automatic title and image | Excluded by the Associates license ("data mining, robots, or similar data gathering and extraction tools"), which puts the client's Associates account at risk; Amazon also blocks server requests (`amazon.com` answered our own fetch today with 503) | **Do not build** |
| 4 | A third-party product-data service | Automatic title and image | A paid outside dependency, which the feature plan removes; the same terms problem, at one remove | **Do not build** |
| 5 | Keep using Lasso's cloud | Same as today | Needs a Lasso licence; private, undocumented interface; disappears with Lasso | **Not possible** |
| 6 | **Creators API (task C1)** once the client sends credentials | Automatic title, image and Prime, within Amazon's terms | Needs credentials and 10+ qualifying sales in 30 days; images may be stored as links for at most 24 hours and other data refreshed within 24 hours, so C1 needs a refresh job | **Keep as the upgrade** |

**Recommended:** 1 + 2 now, so Affibloom works fully without any credentials; 6 when the client provides credentials.
3, 4 and 5 are not recommended.

## What this would change in the plan (only after the user agrees)

- **Task 12 (Amazon links) or Task 14 (Details tab):** add option 2's helpers: a starting name from the URL slug, a
  "View on Amazon" link, and an image field that also takes an image link.
- **C1:** add the 24-hour rules: store API image links for at most 24 hours and refresh API data daily (a cron batch, like
  the link check), or fetch at display time with a 24-hour cache.
- **Feature plan, client point 1:** the client no longer needs to send credentials before launch; C1 is an upgrade.

## Decision

**Adopted by the user on 2026-11-12:** options 1 and 2 now, option 6 (C1) as an upgrade when credentials arrive.
Recorded as decision 11 in the plugin's `docs/decisions/task-6-decisions.md`; the tasks it changes (12, 14, 26, C1) carry
notes in their plan files.
