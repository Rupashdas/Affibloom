# C2 Automatic affiliate link detection: analysis

Date: 2026-11-12. Sources: Lasso 369 source, the live Lasso 370 screens
(`tests/fixtures/baselines/admin/settings-general-2.jpg`), and the Local copy's data (production as of 2026-11-09).

## How Lasso does it

Setting: Settings → General → Link Defaults → `Auto-Detect Affiliate Links` ("Add all detected affiliate links
to your dashboard as individual products"), stored as `auto_monetize_affiliates`.

1. **Detection.** While Lasso's scanner reads post content (on save, and every 10 minutes by cron), each link
   passes the filter `auto_detection_affiliate_link` (`pages/fix/class-hook.php`). It tries 33 network
   classes (`classes/auto-monetize/`: Agoda, AvantLink, Awin, Bol, Booking, Cake, CJ, ClickBank, Daisycon, eBay,
   FinanceAds, FlexOffers, GetYourGuide, GoAffPro, iDevAffiliate, Impact, LinkConnector, MaxBounty, Partnerize,
   PartnerStack, Pepperjam, Post Affiliate Pro, Rakuten, Refersion, RentalCars, ShareASale, Skimlinks, Sovrn,
   Tradedoubler, TradeTracker, Tune, Webgains, …). A match is stored in `wp_lasso_auto_monetize` with the network,
   affiliate ID, advertiser ID, deep link and URL.
2. **Creation.** The cron process `Lasso_Process_Auto_Monetize` turns each stored link into a Lasso product
   through the normal "add link" path, with cloaking off, so **the post content is not changed**. Products are
   created without asking anyone.

Separate from this, and off on the live site: `Enable Amazon Auto-Monetization` (Amazon links), which stays
excluded.

## State on VolleyballVault

- **On** (live General screen, 2026-11-12), and **it has been working**: `wp_lasso_auto_monetize` holds 16
  detected links, every one turned into a product:
  - 2024-03-10: 7 Walmart links through Impact (`goto.walmart.com/c/…`) and 1 Rogue Fitness link through Post
    Affiliate Pro (`?a_aid=…`);
  - 2026-09-12 and 2026-09-24: 8 Mizuno links through Impact (`mizunousa.sjv.io/…`).
- The feature plan's "used once, for four Mizuno links" understated it: three rounds, two networks, 16 products.
  Most of the site's 21 non-Amazon products came in this way.

## What Affibloom needs

- **Setting:** `Auto-Detect Affiliate Links` in Settings → General → Link Defaults, Lasso's label and help text;
  imported from Lasso (on) in Task 26.
- **Detection:** on `save_post` for published posts and pages, read the links in the content. The usage indexer
  (Task 35) already parses them, so C2 follows it and reuses that parsing (extracted on its second use). Match
  each link against network patterns: Impact and Post Affiliate Pro first (the two in use), then the common
  networks in Lasso's list. Lasso's Impact pattern is loose (`[\d\w]+\.\w+(\/(\w{4,12}))|(\/c\/(\d+)\/(\d+)\/(\d+))`);
  Affibloom matches on known host names (`*.sjv.io`, `goto.walmart.com`, `*.pxf.io` …) plus the path, so it
  does not catch ordinary links.
- **Creation:** for each link with no product yet (`_affibloom_url_key`), create an `other` product with the site's
  link defaults. Name it from the link text, falling back to the merchant's domain. Remember where it was found.
  Never change the post content. Run in the request that saved the post, capped per save; the rest in a cron
  batch.
- **Tests:** unit tests per network pattern, built from the real URLs in `tests/fixtures/inventory/`
  (`products.json`, `links.json`); an integration test that saving a post with an Impact link creates exactly
  one product, and saving it again creates none.

**Placed after:** Task 35 (usage index). **Touches:** Task 9 (General settings), Task 26 (setting import).

## Choice to make at gate 4

Lasso creates the products **without asking**. The earlier plan outline (when C2 was optional) proposed a safer
variant: list the detected links on the post's edit screen and let the editor create them with one click. This
analysis follows Lasso, as the roadmap's parity rule asks; the "ask first" variant is proposed as register item
**U8** for the user to approve or reject.

## Risks

- False positives create junk products. Mitigation: host-based patterns and a test per network.
- A post with many links could slow its save. Mitigation: a cap per request, the rest in a cron batch.
- Detected short links (`sjv.io/…`) are not resolved; the product keeps the link as written, as Lasso does.
