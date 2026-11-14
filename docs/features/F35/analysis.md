# F35 Auto-monetization: analysis

(To be saved as docs/features/F35/analysis.md.)

Status: written 2026-11-14 with `requirements.md`. Gate 4 for Tasks C2A-C2C and AM1-AM6: waiting.

**Tasks:** C2A (the network registry — `wp affibloom links detect <url>`), C2B (detection when a post is indexed —
`save_post` through F21), C2C (products for detected links — cron `affibloom_auto_detect`), AM1 (the Amazon import —
cron `affibloom_amazon_import`), AM2 (Amazon short links — the import's short-link branch and the map), AM3 (switching
Auto-Monetization on and off — Settings → Amazon), AM4 (the import notice, its status and dismiss routes — every
Affibloom page), AM5 (the status card, the Dashboard notice, the General stats), AM6 (tracking IDs found in posts join
the whitelist — `save_post` through F21). All after F21's 35A-35C and 35H (Phase 6, after Task 35); C2A can go first.

## Depends on

- **F21** (content link index): 35A (the index, the resolver, `Schema\Migrator`), 35C (`save_post`), 35F (rebuild and
  the daily pass), 35G (the Link Index card, for AM5), 35H (the render filter that prints tied links, for C2 AC9 and
  F35 AC2-AC3). F35 needs from F21: an "indexed" event, the resolver reading two more tables, and D14's gate (F35 D13).
- **F18 / Tasks 11-12, 17D:** `ProductService::create_from_url()` (duplicates, naming F18 D3, link defaults, Amazon tag
  rule, revive) — both halves create through it; it gains an option "cloaking off" (Lasso's `link_cloaking 0`).
- **F2 §5:** the short-link resolution rule (stop at the first redirect into an Amazon store) — `PageFetcher` already
  stops before Amazon (11D).
- **F9 9B** (the Auto-Detect switch, key `auto_detect_links`); **F17 / 10F** (the Amazon switch and the tracking-ID
  settings, built); **F1** retired ASINs (repoint, Link_Updates' equivalent).
- ADR-007 (plain scripts, PHP-rendered screens), ADR-008 (Aurora), `Capabilities::PRODUCTS`.

## Data

| Where | What | From Lasso |
|---|---|---|
| Option `affibloom_settings` → `auto_detect_links` (bool, default **true**) | Auto-Detect Affiliate Links | `auto_monetize_affiliates` |
| Option `affibloom_settings` → `auto_monetize_amazon` (bool, default false; exists) | Enable Amazon Auto-Monetization | `auto_monetize_amazon` |
| `tracking_id_whitelist`, `tracking_id_blacklist` (exist) | AM6 adds learned IDs to the whitelist | `amazon_tracking_id_whitelist`, `lasso_amazon_tracking_id_blacklist` |
| Table `{prefix}affibloom_detected_links` | `id` bigint PK, `url` text, `url_hash` char(32) unique (md5 of the decoded URL), `network` varchar(40) (registry slug), `network_name` varchar(255), `affiliate_id`/`advertiser_id` varchar(64), `deep_link` text, `domain` varchar(255), `product_id` bigint NULL, `detected_at` datetime, `attempts` tinyint | `lasso_auto_monetize` (not copied, F35 D4) |
| Table `{prefix}affibloom_amazon_short_links` | `short_url` varchar(255) PK, `final_url` text, `product_id` bigint NULL, `resolved_at` datetime NULL, `attempts` tinyint | `lasso_amazon_shortened_url` (copied, Task 26) |
| User meta `affibloom_amazon_import_dismissed` | the dismissed fingerprint (`pending_<q>_<d>` / `complete_<d>`) | `lasso_dismiss_amazon_auto_monetize_banner` |
| Options `affibloom_auto_detect_lock`, `affibloom_amazon_import_lock` (timestamp), `affibloom_amazon_import_batch` (`{ total, done, started }`) | run locks and the batch counters of the status payload | the process tables' `_total_count`, `_start_time` |
| Option `affibloom_activated_at` | the 24-hour window of AM5 (if F21 or Plugin has none) | `lasso_plugin_activated_at` |
| PHP data `resources/networks.php` | the 37 registry entries, the 32 extraction rules, `impact-domains` (805), Amazon hosts (21) | `files/affiliates.json`, `impact-domains.json`, `amazon-associates-domains.json`, the 32 classes |

Not stored: `lasso_affiliate_programs` (cloud catalogue), `lasso_extend_products` (cloud data), boosting meta (C).

## Entry points and request paths

```
wp affibloom links detect <url>                                                                         (C2A)
  → Cli\LinksCommand::detect() → Domain\Monetize\NetworkRegistry::match( url )  (pure; data from resources/networks.php)
       first entry in Lasso's order: host lists (Impact, Amazon), the Post Affiliate Pro rule, else the regex
     → DetectedLink { slug, name, affiliate_id, advertiser_id, deep_link, domain } via NetworkExtractor (Appendix B)
  ← "Impact (impact): affiliate 3512787, advertiser 9383, deep link https://www.walmart.com/…, domain walmart.com"
     | "Everflow (everflow): an affiliate link, not auto-detected" | "No known network."

save_post (published post or page) → F21 Hooks\LinkIndexHooks → LinkIndexer::index( host )                 (C2B)
  → do_action( 'affibloom_link_index_indexed', host, rows )        (F21 adds the event; rebuild and daily pass too)
  → Hooks\AutoDetectHooks::on_indexed()  — only when settings.auto_detect_links
     → Application\Monetize\AffiliateDetector::detect( rows )   External rows not tied to a product
        NetworkRegistry::detect( url ) → DetectedLinkRepository::record( DetectedLink ) (INSERT IGNORE by url_hash)
        a recorded URL already a product (ProductRepository::find_by_url_key) → product_id set at once
     → any row without product → wp_schedule_single_event( time() + 5, 'affibloom_auto_detect' ) (once)

WP-Cron affibloom_auto_detect (single) and affibloom_auto_detect_daily (daily)                           (C2C)
  → Cron\AutoDetectCron → AffiliateDetector::create_products( 100 )   lock; setting re-read before each row
     DetectedLinkRepository::without_product( 100 )   product_id NULL, or no longer a product (deleted for good)
     → ProductService::create_from_url( url, cloaking: off ) → AddedLink → record product_id
  F21 ProductResolver gains: url_hash → detected_links.product_id (after the stored-URL lookups, F21 R25 order)
  → 35H prints the tied anchor with the product's link, rel, target

WP-Cron affibloom_amazon_import (every 5 minutes, scheduled only while auto_monetize_amazon)              (AM1, AM2)
  → Cron\AmazonImportCron → Application\Monetize\AmazonImport::run( 100 )   lock; batch counters
     queue = LinkLocationRepository::untied_amazon( 100 ) (F21 rows: Amazon host, not tied; text and image)
             + the AAWP/ACF rows F21 indexes
     per link: short link → AmazonShortLinks::resolve( url ) (map, else PageFetcher stop-at-Amazon, ≤ 3 tries) (AM2)
               TrackingIds::learn( tag ) for the final link's tag                                              (AM6)
               retired ASIN → skip
               ProductService::create_from_url( url ) → AddedLink (existing or new) → map row product_id
  F21 ProductResolver gains: short_url → amazon_short_links.product_id

save_post → F21 indexer → for each Amazon product link: TrackingIds::learn( tag ) → SettingsRepository  (AM6)

GET admin.php?page=affibloom-settings&tab=amazon                                                        (AM3, AM5)
  the switch (10F) + assets/admin/amazon-auto-monetize.js: R22's check, the dialog "Enable Auto-Amazon"
PUT /affibloom/v1/settings/amazon { auto_monetize_amazon, … }   (exists, 10F)
  → SettingsService::save() → after save: off→on → AmazonImportSchedule::start() (event now + every 5 min)
                                          on→off → AmazonImportSchedule::stop()

GET  /affibloom/v1/amazon-import          → 200 { running, queue_count, dashboard_count, links_indexed, complete,
                                                  batch_total, batch_completed, batch_percent }        (AM4)
POST /affibloom/v1/amazon-import/dismiss  → 200 { dismissed: fingerprint }                              (AM4)
  → Rest\AmazonImportController (Capabilities::PRODUCTS, REST nonce) → AmazonImport::status() / dismiss( user )
  ← 401/403 WP_Error affibloom_forbidden; 409 affibloom_nothing_to_dismiss when no fingerprint
admin_notices on Affibloom pages → Admin\AmazonImportNotice (banner) ; Dashboard → contextual notice;
  Settings → Amazon → status card; Settings → General Link Index card → two stat columns          (AM4, AM5)
  assets/admin/amazon-import-status.js polls every 15 s, redraws [data-affibloom-amazon-status]
```

## Layers

- **Domain** `Monetize\NetworkRegistry` (match, is_affiliate_link, detect; data injected), `Monetize\Network` (one
  entry), `Monetize\NetworkExtractor` (the 32 rules: query keys case-insensitive, `u0026` cut, Impact regex, Partnerize
  destination), `Monetize\DetectedLink`, `Monetize\DetectedLinkRepository` (interface), `Monetize\AmazonImportStatus`
  (payload and fingerprints, pure), `Monetize\ShortLinkRepository` (interface); `Amazon\TrackingIds::learn()` (exists:
  `TrackingIds`, gains the rule of F35 R27 without the blacklist slip).
- **Application** `Monetize\AffiliateDetector` (detect, create_products), `Monetize\AmazonImport` (run, status,
  dismiss), `Monetize\AmazonShortLinks` (resolve with `PageFetcher`), `Monetize\AmazonImportSchedule`;
  `ProductService::create_from_url()` gains its cloaking option.
- **Infrastructure** `Tables\DetectedLinksTable`, `Tables\AmazonShortLinksTable` (F21's `Schema\Table`, migrator
  versions), `WordPress\OptionLock`, `WordPress\UserMetaDismissals`; `resources/networks.php` (data, generated once from
  Lasso's files by hand-checked script, committed).
- **Interface** `Cli\LinksCommand::detect` (F21's command gains a subcommand), `Hooks\AutoDetectHooks` (Module),
  `Cron\AutoDetectCron`, `Cron\AmazonImportCron` (Modules), `Rest\AmazonImportController`, `Admin\AmazonImportNotice`
  (banner, contextual, card, General stats partials), templates `admin/partials/amazon-import-status.php`,
  `amazon-import-notice.php`, `amazon-enable-dialog.php`; scripts `amazon-auto-monetize.js` (AM3),
  `amazon-import-status.js` (AM4-AM5); `Messages` entries (R22, R31 texts).

## Risks

- **Loose Lasso patterns** (CAKE, Tune, TradeTracker, FirstPromoter, top-level alternations) can make junk products from
  ordinary links. Kept verbatim (F35 D2); on this copy they match none of the site's links (checked 2026-11-14); C2A's
  tests pin both the intended and the accidental matches so a later tightening is a visible decision.
- **The first Amazon import at cutover** (F35 N3): about 91 products and 218 short links on its first hour; run on staging
  first.
- **Request time:** creation and short-link resolution make HTTP requests; both run in cron batches with a lock and a
  time budget (25 s per run of WP-Cron, the rest next run), never in a save or a page view.
- **Resolver order** (F21 D8): the two new lookups go after the stored-URL lookups, so a product's own URL always wins.
- **F21 D14's retag gate** must match Lasso (F35 D13/D15) or untied links would change tag on a site with the setting
  off.
- **Data file size:** 805 Impact hosts + 37 patterns, about 20 KB of PHP, loaded only by the detector and the CLI.

## Security

- Settings, routes and screens behind `Capabilities::PRODUCTS` / `SETTINGS`; REST nonce through `wp.apiFetch`.
- URLs from post content are data: stored with `esc_url_raw` and length limits, never echoed unescaped; the CLI prints
  with `WP_CLI::log` after `esc_url`.
- Outgoing requests only to the short link's own host (AM2) and through the add path's fetcher (F18 D3); no Lasso host,
  no Amazon store page (C1).
- Cron handlers re-read the setting and hold a lock; no user input reaches them.
- No post content is written anywhere in this feature.

## Tests

- **Unit** (Brain Monkey / plain PHPUnit): `NetworkRegistryTest` (data provider: every Appendix A entry with Lasso's
  example URLs and the class doc-block examples, a non-match each, order of first match, Amazon excluded, the four
  registry-only entries), `NetworkExtractorTest` (all 32 rules, `&amp;`, `u0026`, upper-case keys, Impact both shapes,
  Partnerize), `AmazonImportStatusTest` (fingerprints, percent, complete), `TrackingIdsLearnTest` (R27's five
  conditions), `AffiliateDetectorTest` (record once, tie existing, schedule once), `AmazonImportTest` (queue order,
  retired skip, existing ASIN tie, setting off stops), `AmazonShortLinksTest` (map hit, resolve, three tries).
- **Integration** (WP test suite): saving a post records rows and creates nothing inline; the cron creates one product
  with cloaking off and the URL as written; `create_from_url` reuse; tables created by the migrator.
- **E2E acceptance:** `tests/E2E/acceptance/C2.spec.ts` AC1-AC9 (AC1 runs `wp cron event run affibloom_auto_detect`
  and checks the F18 D3 name — to be updated with N1's decision); `tests/E2E/acceptance/F35.spec.ts` AC1-AC8 (AC1-AC3
  with `wp cron event run affibloom_amazon_import`, external HTTP stubbed by the suite's `pre_http_request` mu-plugin;
  AC5 the dialog; AC6-AC7 the notice, card, Dashboard notice and General stats with a seeded queue; AC8 off).
- **By hand:** this copy, a dry run of the detector over the index (expect 16 tied, 0 new) and of the Amazon queue
  (expect 91 ASINs, 218 short links), screens at 1280 and 375 px.
