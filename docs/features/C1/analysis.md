# C1 Amazon product data on adding: analysis

Date: 2026-11-12. Sources: Lasso 369 source (`D:\lasso\lasso`), the live Lasso 370 screens
(`tests/fixtures/baselines/admin/settings-amazon-*.jpg`), and the Local copy's data (production as of 2026-11-09).

## How Lasso does it

When a link is added, Lasso reads the ASIN and fetches the product (`Lasso_Amazon_Api::fetch_product_info()`):

1. **Amazon Creators API**, if its credentials are set (Settings → Amazon → `Amazon Product Data API`:
   `Credential ID`, `Secret`, `Partner tag`, `Version`, locale; `Validate` tests them). Lasso gets an OAuth2
   token from Amazon's regional sign-in service (`creatorsapi.auth.<region>.amazoncognito.com/oauth2/token`), then
   calls `POST https://creatorsapi.amazon/catalog/v1/getItems` (`class-lasso-amazon-creators-api.php`).
2. Otherwise, or to fill gaps, **Lasso's own cloud** ("BLS"): `GET https://lasso.link/link/status/?<encrypted>`,
   where Lasso's servers read the Amazon page and return title, image, price and Prime.

The result is cached in `wp_lasso_amazon_products` (`default_product_name`, `default_image`, `is_prime`,
`latest_price`, `last_updated`) and gives the new link its starting title and image. A nightly job refreshes it,
and the product page has `Refresh` beside `Amazon Image`.

## State on VolleyballVault

- **On and working, through Lasso's cloud.** The Creators API fields are empty on the live Amazon settings, so
  every fetch goes through `lasso.link`. The newest Amazon records (added 2026-11-05) all have a fetched name and
  image; the cache was refreshed on 2026-11-09 00:08–00:11.
- 226 of 233 Amazon records have cached data. The 7 without it date from 2023–2025 and have no ASIN stored.
- Editors shorten the fetched titles ("Mizuno Wave Momentum 3 Men's Volleyball Shoe | Men…" became "Mizuno
  Wave Momentum 3"), so the fetched title is a starting value, not the shown name.
- The live Dashboard shows "Bulk Amazon sync · 3 Amazon links are ready to sync product data", stuck at 0%.

## What Affibloom needs

Lasso's cloud is not an option: it is Lasso's paid service, and the feature plan removes every dependency on
it. The only legitimate source is **Amazon's Creators API**. Reading Amazon's pages directly would break the
Associates terms.

- **Prerequisite (the client):** Creators API credentials for the `vbvault-20` Associates account. Amazon
  decides which accounts qualify. Without credentials the feature stays dormant and Affibloom works without it
  (decision 11 in `docs/decisions/task-6-decisions.md`): existing products keep the data Lasso fetched, new Amazon
  products get a starting name from the link's slug and an editable Amazon Image link field. C1 is an upgrade, not a
  launch requirement.
- **Settings → Amazon:** Lasso's `Amazon Product Data API` section: locale, `Credential ID`, `Secret` (password
  field, never returned by REST or exported), `Partner tag`, `Version`, `Validate`.
- **Application:** an `AmazonCatalog` interface. **Infrastructure:** `CreatorsApiCatalog`: client-credentials
  token cached until it expires; `getItems` for up to 10 ASINs per call; title, primary image and Prime
  eligibility; results cached 24 hours; time-outs and Amazon's rate limit respected.
- **On adding an Amazon link:** name, `image_url` and `prime` come from the catalogue when it answers; otherwise
  the current fallback applies. **Product page:** `Refresh` beside the image, as in Lasso.
- **Amazon's rules** (Associates Program Policies, read 2026-11-12): "You will not store or cache Product Advertising
  Content consisting of an image, but you may store a link to Product Advertising Content consisting of an image for up
  to 24 hours"; other Product Advertising Content may be cached "for up to 24 hours", then refreshed by a new API call.
  So C1 refreshes every product that shows API data at least daily (a cron batch, like the link check), images stay
  hot-linked from Amazon (`m.media-amazon.com`), and a refresh never overwrites a name or image the editor set by hand.
  Read the Creators API terms again when the task is written.
- **Tests:** recorded API responses as fixtures; never live calls in tests.

**Placed after:** Task 12 (Amazon links). **Touches:** Tasks 10 (Amazon settings), 12, 14 (image control).

## Risks

- The account may not qualify for API access; the feature then has no effect until it does.
- Amazon changes or retires APIs; keep the catalogue behind its interface. Confirmed 2026-11-12: Lasso's settings still
  hold Product Advertising API 5.0 keys from an older Lasso, and one test request with them returned 403
  `AccessDenied`, "Product Advertising API is deprecated. Please migrate to Creators API". C1 uses the Creators API
  only, and the migration (Task 26) does not import those keys.
- The secret is stored in `wp_options` in plain text, as Lasso stores it. Keep it out of REST responses, the
  CSV export and logs.
