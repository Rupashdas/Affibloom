# C2 Automatic affiliate link detection: analysis

(To replace docs/features/C2/analysis.md.)

Date: 2026-11-12; **rewritten 2026-11-14** from a sweep of Lasso 369 (F35). C2 is the first slice of F35
Auto-monetization: its full rules, data and decisions are in `docs/features/F35/requirements.md` and `analysis.md`;
this file keeps C2's own history and the corrections.

**Tasks:** C2A (the network registry, `wp affibloom links detect <url>`), C2B (detection when a post is indexed),
C2C (products for detected links, cron `affibloom_auto_detect` and its daily run). Placed after Task 35's 35A-35C;
C2A can go before. Request paths, layers, data, risks, security and tests: F35 analysis (the C2 lines).

## How Lasso does it (corrected 2026-11-14)

Setting: Settings → General → Link Defaults → `Auto-Detect Affiliate Links` ("Add all detected affiliate links
to your dashboard as individual products"), stored as `auto_monetize_affiliates`, default on.

1. **Detection.** While Lasso's scanner reads post content (on save, every 10 minutes, rebuilds — and only while its
   licence is active), each anchor passes the filter `auto_detection_affiliate_link` (`pages/fix/class-hook.php`
   748-760), which tries **32** network classes (`classes/auto-monetize/`, `AFFILIATES_CLASSES`; the folder's 33rd file
   is their abstract base). Each class asks the registry `files/affiliates.json` (37 entries) whether the URL is its
   network. A match is stored once (by `md5(url)`) in `wp_lasso_auto_monetize` with the network name, affiliate ID,
   advertiser ID, deep link, domain and URL.
2. **Creation.** A **daily** cron (`lasso_auto_monetize`, `Lasso_Process_Auto_Monetize`, 100 a run) turns each stored
   link into a Lasso product through the normal "add link" path, with cloaking off. The product keeps the affiliate link
   as written as its Primary URL (`keep_affiliate_url`) and is named by the add path's fetch (page title).
3. **The post.** Detection itself changes nothing, but **the next scan does rewrite the anchor**: it resolves the URL to
   the new product through the auto-monetize table and stamps `data-lasso-id`, `data-lasso-lid`, `data-lasso-name`,
   the product's `rel` and `target` (F21 R37-R39). Products are created without asking anyone.

Separate from this, and **on** on the live site (not off, as this file said): `Enable Amazon Auto-Monetization`
(Amazon links). It is no longer excluded: it is F35's Amazon half (Tasks AM1-AM6).

## State on VolleyballVault (checked again 2026-11-14)

- **On**, and it has been working: `wp_lasso_auto_monetize` holds 16 detected links, every one a product:
  7 Walmart links through Impact (`goto.walmart.com/c/…`, deep link to walmart.com), 1 Rogue Fitness link through
  Post Affiliate Pro (`?a_aid=…`), 8 Mizuno links through Impact (`mizunousa.sjv.io/…`, no deep link).
- The products are "Basic Link", cloaking off, Primary URL = the link as written; their names show Lasso's fetch:
  4 "Walmart | Save Money. Live better.", 4 "add-a-link-title", 8 real page titles.
- 29 Impact anchors remain in 4 posts; Lasso's other patterns match none of the site's links.

## What Affibloom needs (corrected 2026-11-14)

- **Setting:** `Auto-Detect Affiliate Links` in Settings → General → Link Defaults, Lasso's label and help text (Task 9B);
  key `auto_detect_links`; imported from Lasso (on) in Task 26.
- **Detection:** when F21's index indexes a post (save, rebuild, daily pass), its untied External links are matched
  against **all of Lasso's networks** (F35 D1, D2; decision 8 superseded). The registry is one data file, so a network
  is one entry plus its test. **Lasso's Impact detection is by host** (805 hosts in `impact-domains.json`, e.g.
  `sjv.io`, `goto.walmart.com`); the loose regex `[\d\w]+\.\w+(\/(\w{4,12}))|(\/c\/(\d+)\/(\d+)\/(\d+))` that this file
  quoted only *extracts the IDs* — it never decides whether a link is Impact.
- **Storage:** `affibloom_detected_links` (F35 D4), Lasso's columns.
- **Creation:** for each row with no product, `ProductService::create_from_url()` with cloaking off, in a background run
  scheduled by the detection and a daily run (F35 D5) — not in the save request. The name follows the add path (F18 D3)
  unless the user prefers the link text (F35 N1). The post content is never changed; the anchor's attributes come from
  F21's render filter (35H).
- **Tests:** unit tests per network from Lasso's examples (C2A), an integration test that a save records and the cron
  creates exactly one product, E2E C2 AC1-AC9.

## Decided: automatic, as in Lasso

Lasso creates the products **without asking**. The "ask first" variant (register item U8: list the detected links on
the post's edit screen and let the editor create them) was **rejected** on 2026-11-12: on the live site all 16 detected
links became products the site uses, and the post content is never changed either way.

## Corrections of 2026-11-14

| This file said | Lasso 369 shows |
|---|---|
| "33 network classes" | 32 (`AFFILIATES_CLASSES`); 37 registry entries; 4 registry-only networks |
| Detection by "the two networks the site uses" (decision 8) | superseded by the full-scope rule: all networks |
| Impact matched by a loose regex | by host list; the regex extracts IDs |
| "Name it from the link text" | the add path's fetch (page title / placeholder); F35 N1 |
| "Run in the request that saved the post" | Lasso creates daily; Affibloom: a background run right after the save (F35 D5) |
| "the post content is not changed" (by Lasso) | the next scan rewrites the anchor (stamps, rel, target); Affibloom does it at render time |
| Amazon Auto-Monetization "off on the live site", "stays excluded" | on (live and this copy); F35 AM1-AM6 |
| "Detected short links (`sjv.io/…`) are not resolved" | correct for storage (the URL is kept as written, `keep_affiliate_url`); the add path still requests it once for its name (F18 D3) |

## Risks

- False positives create junk products: Lasso's patterns are kept verbatim, loose ones included (F35 R6, D2); none
  matches this site's links today; C2A's tests pin them.
- A post with many links does not slow its save: detection only records rows; creation runs in cron.
