# F21 Content link index and render-time link attributes: analysis

Status: gate 2 written 2026-11-14 with `requirements.md`. Gate 4 for Tasks 35A-35J: waiting.

**Tasks** (they replace Phase 7's Task 35; F12's Locations Tasks 36A-36E follow them):

- 35A The index of anchors in post content — `wp affibloom links rebuild`
- 35B Displays in the index: shortcodes and blocks — `wp affibloom links rebuild`
- 35C Saving, trashing and deleting a post updates its rows — `save_post` and friends
- 35D Custom Link Detection: custom post types and ACF fields — Settings → General
- 35E Links in builders and recipe plugins — `wp affibloom links rebuild --post`
- 35F The background rebuild and the daily pass — WP-Cron `affibloom_link_index_batch`
- 35G Settings → General: Link Index and Performance — the Settings screen
- 35H Render-time link attributes — `the_content`
- 35I A product's save and repoint keep its rows — `PATCH /products/{id}`
- 35J SiteStripe, image-block and builder displays at render time — `render_block`

## Depends on

- ADR-1 (products are posts), ADR-2 (the Lasso ID map), ADR-4 (WP-Cron batches with a lock), ADR-7 and ADR-8 (the
  General tab's look), deviation 52 / F1 D13 (no content writes).
- F1: the product's public link and flags (`Product`, `ProductRepository`), the site's Amazon tag rule (F1 D12), the
  repoint (15C, F1 D14). F24: the cloaked permalink (for D8 and D13; until F24, cloaked links resolve by slug only).
- F23: group members (35B's `display_ref = group:<id>` is counted by F12 36A through F23's taxonomy).
- F10 / Task 26: products exist before the first rebuild; the migration copies the settings (requirements §5).
- The Settings screen and its General tab (Phase 2, `SettingsScreen`, `SettingsController`).

## Data

| Where | What | From Lasso |
|---|---|---|
| Table `{prefix}affibloom_link_locations` | one row per link or display (D1): `id` bigint PK; `host_id` bigint; `host_url` varchar 1000; `source` varchar 40; `occurrence` int; `product_id` bigint (0 untied); `link_type` varchar 20; `display_type` varchar 50; `display_ref` varchar 40 NULL; `anchor_text` varchar 1500 NULL; `url` text; `original_url` text; `domain` varchar 150 NULL; `url_md5` char 32; `asin` varchar 150 NULL; `tracking_id` varchar 150 NULL; `country` char 2 default 'us'; `no_follow`, `new_window`, `is_ignored` tinyint; `affiliate_platform` varchar 64 NULL; `lasso_location_id` bigint NULL; `detected_at` datetime. Keys: `(host_id, source, url_md5, occurrence)` unique, `product_id`, `(product_id, host_id)`, `(product_id, is_ignored, domain)`, `domain`, `display_type`, `lasso_location_id` | `lasso_link_locations` (R1); map + metadata → `display_ref` (D2) |
| Settings (the existing `affibloom_settings`) | `link_index_post_types` (array, default []), `link_index_acf_fields` ([]), `keep_original_url` ([]), `performance_mode` (balanced), `cpu_threshold` (65), `cron_interval_hours` (0, 0-3), `catch_up_limit` (2, 1-3), `sitestripe_scan` (false) | `cpt_support`, `custom_fields_support`, `keep_original_url`, `general_performance_mode`, `cpu_threshold`, `cron_time_interval`, `manually_background_process_limit`, `enable_site_stripe_post_content_scan` |
| Option `affibloom_link_index_state` | `{ status: idle|running, run: full|daily, page, total, done, started_at, finished_at, last: { hosts, rows, seconds } }` | the process options, `lasso_scan_all_posts`, `Background_Process_Runs` (D11) |
| Option `affibloom_schema_version` | the Migrator's version (first table: 1) | — |
| Transient `affibloom_link_index_lock` | 60 s batch lock | the process lock (R17) |
| Cron events | `affibloom_link_index_batch` (single, chained), `affibloom_link_index_daily` (daily) | `lasso_force_scan_all_links`, `lasso_scan_all_links`, `lasso_daily_clean_link_locations` |

Not stored: `lasso_content`, `lasso_link_syncing` (C), the map table, `lasso_metadata` (D2, D11).

## Entry points and request paths

```
wp affibloom links rebuild [--post=<id>] [--dry-run]                                  (35A, 35B, 35E)
  → LinksCommand::rebuild() → LinkIndexRebuild::run_inline( post_ids | all )
       HostQuery::hosts( post types D7, statuses D6 ) → for each host: LinkIndexer::index( host )
  LinkIndexer::index( host )
    ContentSources::for( host ) → [ PostContentSource, AcfFieldsSource, ElementorSource, … ] → (source, html)
    LinkScanner::scan( html, site_host ) → LinkCandidate[]  (anchors 35A; shortcodes, blocks 35B; img/iframe 35E)
       skip rules R24-R25; LinkClassifier → link type, display type, domain, flags, platform; LocationKey occurrence
    ProductResolver::resolve( candidate ) → product id | 0  (D8)
    LinkLocationRepository::replace_host( host, rows )   one transaction; keeps is_ignored by key (D10)
  ← "Indexed 180 posts: 3,015 links (921 tied to 200 products); 0 posts changed." (--dry-run: no writes)
wp affibloom links status  → the state option as text

save_post (20) / transition_post_status / trashed_post / untrashed_post / deleted_post            (35C)
  → LinkIndexHooks → indexed status and type ? LinkIndexer::index( host ) : repository->delete_host( host )
    once per post per request; skips revisions and autosaves; Elementor's after_save re-indexes (35E)

GET  /affibloom/v1/link-index           → 200 { status, done, total, eta, last_updated, links_indexed }      (35G)
POST /affibloom/v1/link-index/rebuild   → 202 { status: running }   409 affibloom_rebuild_running             (35G)
  → LinkIndexController (Capabilities::SETTINGS, REST nonce) → LinkIndexRebuild::start() → schedules the batch
POST /affibloom/v1/settings { general: { link_index_post_types, link_index_acf_fields, performance_mode,
      cpu_threshold, keep_original_url, cron_interval_hours, catch_up_limit } }                          (35D, 35G)
  → SettingsController → SettingsService (validation R56) → a changed post-type or field list starts a rebuild

affibloom_link_index_batch (WP-Cron, single, chained)                                                (35F)
  → LinkIndexCron::batch() → lock → CPU check (D12) → LinkIndexRebuild::next_batch( page, size )
       → index each host → state → schedule the next batch or finish (state idle, last run figures)
affibloom_link_index_daily → re-index hosts changed since last run; delete rows of hosts that no longer exist

the_content (20), widget_block_content, widget_text, acf/format_value, elementor/frontend/the_content     (35H)
  → LinkAttributesFilter::filter( html, post ) → no "<a " ? return
       RenderedLinks::for_host( post ) (rows by LocationKey, one query) → for each anchor: product → attributes
       (public link, target, rel D13; untied: noopener, https, Amazon tag D14); data-affibloom-product/-location
render_block (core/image, kadence/singlebtn), the_content for SiteStripe markup                          (35J)
  → DisplayRenderFilter (setting sitestripe_scan; the product box from Phase 3)

PATCH /affibloom/v1/products/{id} (F1 15A) and the repoint confirm (15C)                                    (35I)
  → ProductService::update() → LinkLocationRepository::set_url_for_product( id, url ) when the URL changed
  → ProductService::repoint()  → LinkLocationRepository::move_product( from, to )
```

## Layers

- **Domain** `LinkIndex\LinkLocation` (one row), `LinkIndex\LocationKey` (host, source, url_md5, occurrence),
  `LinkIndex\LinkType`, `LinkIndex\DisplayType` (Lasso's strings, R27), `LinkIndex\LinkClassifier` (R2's flags, R25's
  types and domain, given the site host; pure), `LinkIndex\AffiliatePlatform` (R2's patterns; pure),
  `LinkIndex\LinkCandidate`, `LinkIndex\LinkLocationRepository` (interface), `LinkIndex\RenderedAttributes` (D13-D14:
  given an anchor's attributes and a product's link and flags, the attributes to print; pure, the heart of 35H).
- **Application** `LinkIndex\LinkIndexer`, `LinkIndex\LinkIndexRebuild` (state, batches, inline run),
  `LinkIndex\ProductResolver` (D8; uses `ProductRepository`, the Lasso ID map, `AmazonUrl`), `LinkIndex\ContentSource`
  (interface), `LinkIndex\LinkIndexStatus` (the card's figures); `ProductService` gains the 35I calls.
- **Infrastructure** `Schema\Migrator`, `Schema\Table`, `Tables\LinkLocationsTable` (the `$wpdb` repository),
  `Html\LinkScanner` (`WP_HTML_Tag_Processor`, `parse_blocks`, `get_shortcode_regex`), `Content\PostContentSource`,
  `Content\AcfFieldsSource`, `Content\ElementorSource`, `Content\BeaverSource`, `Content\ThriveSource`,
  `Content\TablePressSource`, `Content\WprmSource`, `Content\MvCreateSource`, `WordPress\OptionLinkIndexState`,
  `WordPress\HostQuery`, `System\CpuLoad` (`sys_getloadavg`).
- **Interface** `Cli\LinksCommand`, `Hooks\LinkIndexHooks` (Module), `Cron\LinkIndexCron` (Module),
  `Front\LinkAttributesFilter` (Module), `Front\DisplayRenderFilter` (Module), `Rest\LinkIndexController`,
  `Admin\SettingsScreen` (the General tab's three sections), `SettingsController` (the keys); template partials
  `settings/link-index.php`, `settings/custom-link-detection.php`, `settings/performance.php`; script `settings.js`
  gains the 5-second status poll and the rebuild dialog (the shared `confirm-modal`).

## Risks

- **Parser differences.** Lasso uses simple_html_dom; Affibloom uses `WP_HTML_Tag_Processor`. Counts can differ on
  broken markup; 35A compares with Lasso's 3,015 rows per host (`--dry-run` prints the differences) and records any.
- **Render-time cost.** One indexed query per rendered post (rows by host) plus the tag walk; the cheap path skips
  content without `<a `. Page caches store the result, as they stored Lasso's rewritten HTML.
- **Index freshness at render.** A link added by a plugin's filter (not stored) has no row: the filter falls back to
  the resolver (D8), cached per request, so attributes are still right; only its location ID is empty.
- **Elementor leftovers.** 160 posts keep `_elementor_data` with Elementor inactive: `ElementorSource` reads it only
  when Elementor is active and the post is built with it (Lasso's rule, R32), else those posts would double-count.
- **Lasso active during the build.** On this copy Lasso is active; its scan is off only because the licence status is
  0 (R10). Nothing in F21 calls Lasso's code.
- **Time.** The rebuild runs inline from WP-CLI (fine for 180 hosts) and in batches from cron; 35F tests a stopped
  batch resuming.

## Security

- REST: `Capabilities::SETTINGS` on the status and rebuild routes and the General settings; REST nonce via
  `wp.apiFetch`. WP-CLI only for shell users.
- The index stores URLs and anchor text from content: printed escaped (F12's tab); `anchor_text` cut to 1,500 bytes;
  every query prepared; IDs `absint`; setting lists sanitised (`sanitize_key` for post types, `sanitize_text_field`
  for field names, R56's rule for Keep Original URL).
- The render filter only rewrites `href`, `target`, `rel` and adds two `data-` attributes, with `esc_url` /
  `esc_attr`; it never injects markup. No post content, meta or third-party table is written (D3).

## Tests

- **Unit** (Brain Monkey): `LinkClassifier` (R2 flags exactly, R25 types, domains), `AffiliatePlatform` (R2's four),
  `LocationKey` occurrence counting, `RenderedAttributes` (every R38-R39 case: nofollow/sponsored/noopener order, `_self`
  removal, sponsored Amazon URL kept, post links kept, query string kept), `ProductResolver` order (D8) with fakes,
  `LinkIndexRebuild` batch sizing and state transitions, settings validation (R56).
- **Integration** (WordPress test suite): `LinkIndexerTest` (a post with text, image, internal, external, ignored and
  skipped links; a `[lasso]`, a Lasso block, an Affibloom block, a grid with a group; rows and `post_content`
  unchanged), `LinkIndexHooksTest` (save, draft, trash, untrash, delete), `LinkLocationsTableTest` (replace keeps
  `is_ignored`, transaction), `LinkAttributesFilterTest`.
- **E2E acceptance** `tests/E2E/acceptance/F21.spec.ts`: AC1 (CLI rebuild on a fixture post, rows read with `wp eval`,
  content hash), AC2 (displays), AC3 (save/trash/delete), AC4 (a test CPT and the setting), AC5 (only what can be
  installed in the test site: Elementor data skipped when inactive; the rest by integration tests), AC6 (cron batch via
  `wp cron event run`), AC7 (the card, the dialog, the poll), AC8 (a post's front end shows the product's current
  attributes after a product change; stored content unchanged), AC9 (repoint moves rows), AC10 (SiteStripe and image
  block render).
- **Against this copy** (35A Step "Run and look"): after Task 26's products exist, `links rebuild --dry-run` reports
  per type the counts in requirements §2 (Internal 1,304, product 923, External 788; 921 tied to 200 products),
  with differences listed.
