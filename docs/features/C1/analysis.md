# C1 Amazon product data and everything Amazon: analysis
# (Intended path: wp-content/plugins/affibloom/docs/features/C1/analysis.md — the harness refused that write.)

Status: rewritten 2026-11-14 with `requirements.md` (the 2026-11-12 version's findings are carried into requirements
sections 2-4). Gate 4 for Tasks C1A-C1P: waiting.

**Tasks** (W = works without credentials; D = dormant until credentials, says so on screen):

- C1A The Product Data API card and Validate (W: card, states, saving; Validate needs Amazon to answer)
- C1B The catalogue client, behind `wp affibloom amazon get` (D)
- C1C Filling a new Amazon product on add (D; the fallback is today's)
- C1D Refresh on the product page (D)
- C1E The scheduled refresh job (D)
- C1F Price, discount and timestamp in the box and on the product page (W for display; Amazon prices per N3)
- C1G Prime by Lasso's rule (W)
- C1H Availability and not-found handed to link health (D)
- C1I Bulk Amazon sync (D; the banner shows the count)
- C1J Tracking-ID behaviours — no new task: owned by 12A, 10F, 14D and auto-monetization (requirements R59)
- C1K Keep Geni.us Formatting on add and save (W)
- C1L The cloud-only switches labelled (W)
- C1M The Amazon search tab in Add A New Link (D)
- C1N The SiteStripe notice (W)
- C1O Amazon data when the primary URL changes (D for data; W for the rule)
- C1P The geni.us clean-up job when the switch is turned off (W)

## Depends on

- F2 (Tasks 12A-12E: AmazonUrl, ASIN and country, the stored form), F17 (Task 10F), F1 (14B, 14D, 15A), F18 (11A;
  D5), F11 (Tasks 30-32: the issue store), F21 (content link index, locations-only rule), Phase 3 (Tasks 18-22; 22's
  Prime), Task 26 (the cache copy). ADR-4, ADR-7, ADR-8, decision 11.

## Data

| Where | What | From Lasso |
|---|---|---|
| Option `affibloom_amazon_credentials` (autoload no) | credential_id, secret, partner_tag, version, verified_signature (sha256 of the four + country) | lasso_settings.amazon_creators_*, OPTION_AMAZON_CREATORS_VERIFIED_SIGNATURE (empty here) |
| `affibloom_settings` | `default_country` (us) + F17's stored Amazon keys | amazon_default_tracking_country |
| Table `{prefix}affibloom_amazon_products` | PK (asin varchar20, country char2); name, price varchar32, base_url, monetized_url, image, out_of_stock, is_prime (history only), currency, savings_amount, savings_percent, savings_basis, features JSON, source (lasso/creators), last_updated UTC; index last_updated | lasso_amazon_products (rating, reviews dropped; is_manual→source) |
| Product meta | existing _affibloom_asin, _affibloom_amazon_country, _affibloom_image_url, _affibloom_price, _affibloom_show_price, _affibloom_prime; new _affibloom_amazon_synced, _affibloom_old_genius_url, _affibloom_old_genius_url2 | amazon_product_last_updated, old_genius_url, old_second_btn_genius_url |
| Transient `affibloom_amazon_token_<hash>` | token, max(60, expires_in-60) s | lasso_creators_token_* |
| Option `affibloom_amazon_bulk_sync` | run ids, done, counts, ≤200 failures, summary, lock | lasso_bulk_sync_amazon_* |
| User meta `affibloom_bulk_sync_dismissed` | pending fingerprint | lasso_dismiss_amazon_bulk_sync_banner_v2 |
| Option `affibloom_sitestripe_notice` | 0 = dismissed | lasso_enable_auto_amazon_notification |
| Cron | affibloom_amazon_refresh (hourly), affibloom_amazon_bulk_sync and affibloom_genius_cleanup (chained single events) | lasso_cron_update_amazon, bulk-sync process, lasso_cron_remove_genius_formatting |

## Entry points and request paths

```
GET admin.php?page=affibloom-settings&tab=amazon                                         (C1A, C1L)
  → SettingsScreen → settings-amazon.php + partials/amazon-api-card.php
       CredentialsService::state() → CredentialsState (not set up | saved | validated)
POST admin-post.php action=affibloom_save_settings tab=amazon (existing; gains default_country + 4 fields)
  → SettingsController → CredentialsService::save() (blank secret keeps stored) → validate if unverified (R23)
POST /affibloom/v1/amazon/validate {credential_id, secret?, partner_tag, version, country}      (C1A)
  → AmazonController::validate (SETTINGS) → CredentialsService::validate()
  ← 200 {message,state} | 422 affibloom_amazon_missing | 401 affibloom_amazon_rejected | 503 affibloom_amazon_unavailable
wp affibloom amazon get <asin> [--country=<cc>]                                           (C1B)
  → Cli\AmazonCommand → AmazonCatalog::item() → CreatorsApiCatalog getItems (R9, R11, R12)
POST /affibloom/v1/products {urls | selections} (existing)                               (C1C, C1M)
  → ProductService::add() → AmazonDataService::fill() (cache reuse or fetch; name/image R32; NotFound fallback)
POST /affibloom/v1/products/{id}/amazon/refresh                                           (C1D)
  ← 200 {image_url,name,price,discount_html,last_updated} | 409 affibloom_amazon_not_configured | 404 | 422 affibloom_not_amazon | 502 affibloom_amazon_failed
cron affibloom_amazon_refresh (hourly)                                                    (C1E, C1H)
  → AmazonRefreshCron → AmazonDataService::refreshDue() → RefreshPolicy (due, ceil(due/20) 100-500) → pace 1 s →
    upsert or bump → LinkHealth::record(200|000|404)
PATCH /affibloom/v1/products/{id} (existing), link → another ASIN                         (C1O)
GET/POST /affibloom/v1/amazon/bulk-sync, POST …/dismiss                                    (C1I)
  ← status | 202 | 409 "Bulk Amazon sync is already running." | 400 "No Amazon links need syncing." | 204
GET /affibloom/v1/amazon/search?q=&page= ← {items[{asin,title,image,price,url,product_id?}],page,has_more} (C1M)
box templates: price/discount/timestamp, Prime = Amazon && ASIN && show_prime            (C1F, C1G)
POST/PATCH products with geni.us link: follow redirect locally when keep_genius_formatting off (C1K)
settings save geni.us on→off → cron affibloom_genius_cleanup                               (C1P)
POST /affibloom/v1/notices/sitestripe/dismiss → 204                                        (C1N)
```

## Layers

- Domain `Amazon\{Credentials, CredentialsState, Marketplace, Asin, CatalogItem, Availability, AmazonProduct,
  RefreshPolicy, PriceFormat, DiscountPrice, PrimeRule}`, interfaces `AmazonProductRepository`, `CredentialsRepository`.
- Application `Amazon\{AmazonCatalog, CatalogError, CredentialsService, AmazonDataService, BulkSyncService,
  LinkHealth}`, `Products\GeniusLinks`.
- Infrastructure `Http\CreatorsApiCatalog`, `WordPress\{TableAmazonProductRepository, OptionCredentialsRepository}`,
  `WpCronBackgroundJob` (reused).
- Interface `Rest\AmazonController`, `Cron\{AmazonRefreshCron, AmazonBulkSyncCron, GeniusCleanupCron}`,
  `Cli\AmazonCommand`, `Admin\AmazonNotices`; partials amazon-api-card, amazon-bulk-sync, sitestripe-notice, the add
  modal's Amazon tab; scripts amazon-settings.js (exists), amazon-bulk-sync.js, admin.js (search).

## Risks

- No credentials yet, account may not qualify; first live call only when they arrive.
- Creators schema read from Lasso's client, not Amazon's docs; search pagination UNVERIFIED.
- Prime and price at cutover (N2, N3).
- Job load: 226 products, 1 s pacing — under 2 min a run.
- Secret at rest in wp_options (as Lasso), never printed, not autoloaded.

## Security

SETTINGS for card/Validate/notice dismissal; PRODUCTS for Refresh, bulk sync, search; REST nonce via wp.apiFetch;
admin-post nonce. Secret and Credential ID fields render empty. Amazon errors shown as plain text. Cache values
escaped, images https only. No post content written.

## Tests

Unit: Marketplace, Credentials::signature, Availability, PriceFormat (USD/EUR/SEK/INR), DiscountPrice, RefreshPolicy,
PrimeRule, CreatorsApiCatalog with recorded JSON (tests/fixtures/amazon/{token, getItems-ok, getItems-empty,
getItems-oos, searchItems, error-401, error-429}.json), AmazonDataService with an in-memory repository.
E2E: a test-only mu-plugin (tests/E2E/mu/) answers pre_http_request for creatorsapi.amazon and the token hosts when
AFFIBLOOM_TEST_AMAZON_STUB is set. AC1 C1C, AC2 C1C, AC3 C1A, AC4 C1D, AC5 C1A, AC6 C1E, AC7 C1A/C1D/C1M/C1I, AC8 C1A,
AC9 C1B, AC10 C1E, AC11 C1F, AC12 C1G, AC13 C1H, AC14 C1I, AC15 C1M, AC16 C1O, AC17 C1K, AC18 C1L, AC19 C1N, AC20 C1P.
