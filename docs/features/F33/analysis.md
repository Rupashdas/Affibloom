# F33 Import from other plugins: analysis

(Intended path: docs/features/F33/analysis.md — saved here because subagent writes of analysis files are refused.)

Status: gate 2 written 2026-11-14 with `requirements.md`. Gate 4 for Tasks IM1-IM20: waiting.

**Tasks:** IM1 (the Import screen, ThirstyAffiliates rows), IM2 (import one / revert one, the record, the old address),
IM3 (Import All / Revert All in the background), IM4 (Pretty Links), IM5 (Lasso Lite / Simple URLs), IM6 (Earnist),
IM7 (Affiliate URLs), IM8 (Easy Affiliate Links), IM9 (AAWP products), IM10 (AAWP lists), IM11 (AAWP tables),
IM12 (EasyAzon), IM13 (AmaLinks Pro), IM14 (SiteStripe), IM15 (WP Recipe Maker), IM16 (Skimlinks, Genius, Tinyurl,
Go.), IM17 (Lasso as a source), IM18 (See It: the locations dialog), IM19 (the notices and the first-build lock),
IM20 (WP All Import). IM1-IM3 come first (ThirstyAffiliates is the site's real data); each source task after them
stands alone with its own fixture.

## Depends on

- ADR-002 (copy, never adopt), ADR-007 (PHP-rendered screens, plain scripts), ADR-008 (Aurora's look, Lasso's
  arrangement); `Capabilities::PRODUCTS`.
- F1/F18: `ProductService::create()` and the product's fields (name, slug, URL, description, price, buttons, toggles,
  badge, image, groups). F23: groups (`GroupService::resolve()`). F19: the screen header and list patterns.
- **F24** (cloaking): the old-address list and its 301 (IM2 needs `OldAddresses::add()/remove()`). Not yet written; IM2
  waits for F24's first task, or IM2 lands the list's storage and F24 adopts it (decided at F24's gate 4).
- **F31** 27X1-27X8: the other plugins' shortcodes; F33 adds `ImportedProducts` to `CompetitorProduct` (IM2 for
  Thirsty, then each source task for its shortcode).
- **F21** (35A-35J, 35I's repoint) for IM13-IM16, IM18, IM19's lock and D15; **F12** Locations (36B) for IM18.
- **F25** (tables) and **F26** (`FieldsService`) for IM11 and IM20. **F10** 26A (products step, `LassoIdMap`) for IM17.
- **F9** 9K (`disable_import_notice`) for IM19. **C1** for refreshing Amazon items (D9), not for importing them.

## Data

| Where | What | Lasso's key |
|---|---|---|
| Product meta `_affibloom_import_source` | the source slug (D5) | `lasso_revert.plugin` |
| Product meta `_affibloom_import_ref` | the source's id (post ID, ASIN, list ID, location id, md5) | `lasso_revert.post_data` / `old_uri` |
| Product meta `_affibloom_import_old_url` | the old address or the old id | `lasso_revert.old_uri` |
| Product meta `_affibloom_import_date`, `_affibloom_import_adopted` | when; 1 when an existing product was linked (D8) | `revert_dt`; — |
| Table meta (F25's table store) the same five keys | an AAWP table import | `old_uri = [amazon table="<id>"]` |
| Term meta on `affibloom_group` the same keys | an AAWP list import | the group made from `keywords` |
| F24's old-address list | the old address → product (D2) | `lasso_revert.old_uri` read by `get_lasso_link_from_old_link` |
| Option `affibloom_import_run` | the bulk job: `action` (import/revert), `source` (or ''), `queue` (refs), `done`, `failed`, `total`, `started`, `finished` | `lasso_import_all_enable`, `lasso_import_all_filter_plugin`, `lasso_import_all_last_page`, `lasso_import_all_process_completed` and the `revert` set |
| Option `affibloom_import_reindex_notice` | 1 after a bulk import until F21's re-resolve ends (R40) | `enable_scan_notice_after_import` |
| Cron `affibloom_import_batch` (five minutes, only while a job runs) | the next batch | `lasso_import_all` / `lasso_revert_all` crons |
| User meta `affibloom_import_locations_page` (per product) | See It's remembered page | `localStorage` `import_locations` |
| Read only: `thirstylink` posts + `_ta_*` meta, `thirstylink-category`, `ta_link_prefix*`, `ta_show_cat_in_slug`; `pretty-link` posts, `prli_links`, `prlipro_options`, `pretty-link-category/-tag`; `surl` posts, `_surl_redirect`, Lite meta, `lasso_lite_url_details`, `lasso_lite_amazon_products`, `lasso_lite_revert`, Lite settings; `earnist` posts, `_earn_*`; `affiliate_url`, `_affiliate_url_redirect`; `easy_affiliate_link`, `eafl_*`, `eafl_settings`, `eafl_category`; `aawp_products`, `aawp_lists`, `aawp_table` posts, `_aawp_table_products`, `_aawp_table_rows`, `aawp_output`, `aawp_api`; `easyazon_item_*` options; F21's index rows (AmaLinks, SiteStripe, WPRM, platforms); Lasso records via F10's `WpLassoSource` | the sources | as named |

## Entry points and request paths

```
GET admin.php?page=affibloom-import[&source=…&s=…&paged=N][&bulk-revert=1]                          (IM1)
  → AdminMenu (submenu "Import", Capabilities::PRODUCTS) → ImportScreen::render()
  → ImportService::list( ImportQuery{ source, search, page, 10 } )
       ImportSources (the registered ImportSource adapters; IM1 has Thirsty only)
       each source → rows( search ) pending + imported (ImportedProducts::for_source())
       ImportList::sort_and_page()   pending first, then source label, then title (R4)
  ← templates/admin/import.php (band, Bulk Import, Bulk Revert when &bulk-revert, source select, search, the card,
     pager, the four dialogs, IM19's lock)
GET /affibloom/v1/import/items?source=&search=&page=   → 200 { html, url, sources[], total, pending }       (IM1)

POST   /affibloom/v1/import/items { source, ref }           → 201 { product_id, html: row }                  (IM2)
         409 affibloom_import_already "Already imported."   404 affibloom_import_missing   422 affibloom_import_failed
  → ImportController → ImportService::import( source, ref )
       ImportSource::item( ref ) → ImportedLink (Domain: name, slug, url, description, price, buttons, toggles,
                                   badge, image id or URL, group names, asin, old_url)   null → 404
       ImportedLink::validate()  name, url, old_url required (R15)                   → 422
       ProductMatch (same URL or ASIN, D8) → adopt, else ProductService::create( ProductInput from ImportedLink )
       GroupService::resolve( names ) → set_for_product()
       ImportRecordRepository::save( product, record )         (meta, D5)
       OldAddresses::add( product, old_url )                   (F24, D2)
       LinkIndex::repoint( old_url | asin → product )          (F21 35I, D15)
DELETE /affibloom/v1/import/items/{source}/{ref}            → 200 { html: row }                              (IM2)
         404 affibloom_import_missing   409 affibloom_product_in_use (N2)
  → ImportService::revert() → record → adopted ? remove record : ProductService::delete_permanently()
       OldAddresses::remove(), LinkIndex::repoint( back )

GET  /affibloom/v1/import/bulk                → 200 { running, action, source, done, failed, total }           (IM3)
POST /affibloom/v1/import/bulk { action: import|revert, source }  → 202 { running… }   409 affibloom_import_running
  → BulkImportService::start() → OptionImportRunRepository (one job; a new one replaces the other, R30/R35)
       WpCronBackgroundJob('affibloom_import_batch')::dispatch()
WP-Cron affibloom_import_batch → BulkImportService::batch( 100 ) → ImportService::import()/revert() per ref
       done → the job closes, affibloom_import_reindex_notice 1, F21 re-resolve queued

GET /affibloom/v1/import/items/{source}/{ref}/locations?page=   → 200 { items: [ { title, edit_url, url, type,
       icon } ], count, page }   (six a page)                                                               (IM18)
  → LocationsService (F12 36B's query) for the record's product

admin_notices on Affibloom screens → ImportNotices (IM19): suggestion (R38, D16), reindex (R40), Lite-from-Lasso (R41)
pmxi_saved_post( post_id, xml, is_update ) for affibloom_product → WpAllImportHook → ProductService (link) +
       FieldsService (lasso_fields)                                                                         (IM20)
template_redirect (F24's handler) → OldAddresses::find( host+path ) → 301                                   (F24)
F31's handlers → CompetitorProduct → ImportedProducts::by_source_ref( source, ref|asin )                    (IM2…)
```

## Layers

- **Domain** `Import\ImportedLink` (the source-neutral item; validation as R15), `Import\ImportRow` (title, target,
  source label, status, ref), `Import\ImportList` (sort and page, R4), `Import\ImportRecord` (D5), `Import\ImportRun`
  (the bulk job: queue, done, failed; pure), `Import\ImportSource` (interface: `slug()`, `label()`, `available()`,
  `rows( search )`, `item( ref )`), `Import\ImportRecordRepository` (interface), `Import\OldAddress`
  (normalise host+path as R21: scheme, `www.`, slashes). Source rules as pure classes where they are rules:
  `Import\Source\ThirstyAddress` (D7), `PrettyLinkAddress` (R44), `EaflTarget` (R48 defaults), `AawpTableMap` (R51's
  field types and numbered labels), `EasyAzonItem` (R52), `SiteStripeTitle` (R54), `PlatformTitle` (R56). No
  WordPress calls.
- **Application** `Import\ImportService` (list, import, revert; D8 adopt; groups; the record; old addresses; repoint),
  `Import\BulkImportService` (start, status, batch), `Import\ImportedProducts` (F31's lookup), `Import\ImportNotices`
  (which notice when), `Import\LassoImportSource` (IM17: wraps F10's `WpLassoSource`, `LassoRecordMapper`, `LassoIdMap`).
- **Infrastructure** one adapter per family in `Infrastructure\Import\`: `ThirstySource`, `PrettyLinksSource`,
  `SimpleUrlsSource`, `EarnistSource`, `AffiliateUrlsSource`, `EaflSource`, `AawpProductSource`, `AawpListSource`,
  `AawpTableSource`, `EasyAzonSource`, `AmaLinksSource`, `SiteStripeSource`, `WprmSource`, `PlatformSource` (SELECT
  only; a missing table reads as empty, as Lasso's `table_exists` checks); `Infrastructure\WordPress\
  PostMetaImportRecordRepository`, `OptionImportRunRepository`; `WpCronBackgroundJob` (exists) for the hook.
- **Interface** `Admin\ImportScreen` (submenu "Import"), `Rest\ImportController`, `Admin\ImportNoticesModule`,
  `Integration\WpAllImportHook`; templates `import.php`, partials `import-list.php`, `import-row.php`,
  `import-dialogs.php` (the four confirms, the processing dialog, the progress dialog, Import Locations);
  `assets/admin/import.js` (list in place, dialogs, bulk polling every 5 s while running).

## Risks

- **F24 is not written yet**: the old-address 301 (AC4) is the reason imports keep the site's links working. IM2's
  gate must name F24's API; if F24 comes later, IM2's AC4 test is the first caller of F24's list.
- **Amazon items** without credentials are thin (D9); their boxes show "Amazon" and no price until C1.
- **Two plugins answering one address** while the source is active ("Needs the user" 3): the 301 must not loop
  (R21's same-path rule) and must not shadow a WordPress post.
- **AAWP tables** need F25's table model and F26's fields: IM11 is the largest source task and comes after both.
- **Bulk on a large Pretty Links install**: thousands of rows; the queue holds refs, batches of 100, one job.
- **Revert deletes** (N2): permanent; the in-use refusal relies on F21's index being current.

## Security

- `Capabilities::PRODUCTS` on the screen and every route; REST nonce through `wp.apiFetch`; `pmxi_saved_post` acts only
  on `affibloom_product` posts and only when WP All Import runs.
- Source refs are validated per source (`absint` post IDs that must be of the source's post type, ASINs
  `^[A-Z0-9]{10}$`, md5 `^[a-f0-9]{32}$`, list/table IDs from their tables); `source` is one of the registered slugs.
- All source reads are SELECTs with `$wpdb->prepare`; values become product fields through the product's own
  validation and sanitising; titles and addresses printed escaped. No post content and no other plugin's data written.
- The 301 goes only to the product's own address (never to a URL taken from the request).

## Tests

- **Unit:** `ImportedLinkTest` (R15), `ImportListTest` (R4), `ImportRunTest` (batches, one job, failures counted,
  no loop), `OldAddressTest` (R21 normalising), `ThirstyAddressTest` (D7), `PrettyLinkAddressTest`, `EaflTargetTest`,
  `AawpTableMapTest`, `EasyAzonItemTest`, `SiteStripeTitleTest`, `PlatformTitleTest`, `ImportServiceTest` (adopt,
  refusal, revert of adopted vs created, in-use), `BulkImportServiceTest`.
- **Integration (WordPress test suite):** each source adapter against a fixture (posts, meta, options, tables created
  in the test) — one test class per source task.
- **E2E acceptance `tests/E2E/acceptance/F33.spec.ts`:** AC1-AC2 (IM1), AC3-AC5, AC10 (IM2, the site's 49 Thirsty
  links: list, import 6741, `/recommends/adidas-dame-8-all-volleyball/` → 301, revert), AC6-AC7 (IM3), AC11-AC23 (one
  block per source task, fixtures through WP-CLI), AC24 (IM17), AC8 (IM18), AC9 and AC12's notice (IM19), AC25 (IM20,
  only when WP All Import is installed in the test site; skipped otherwise with a note).
